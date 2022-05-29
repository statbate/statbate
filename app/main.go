package main

import (
	"flag"
	"fmt"
	"log"
	"net"
	"net/http"
	"os"
	"time"

	_ "github.com/ClickHouse/clickhouse-go"
	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	jsoniter "github.com/json-iterator/go"
	_ "modernc.org/sqlite"
)

type Rooms struct {
	Count chan int
	Json  chan string
	Add   chan Info
	Del   chan string
}

var (
	hub             = newHub()
	Sql, Clickhouse *sqlx.DB
	json            = jsoniter.ConfigCompatibleWithStandardLibrary
	sqlDriver       = flag.String("sqldriver", "mysql", "sql driver (mysql/sqlite)")
)

var (
	save = make(chan saveData, 100)
	slog = make(chan saveLog, 100)
)

var rooms = &Rooms{
	Count: make(chan int),
	Json:  make(chan string),
	Add:   make(chan Info),
	Del:   make(chan string),
}

func main() {
	flag.Parse()

	initSql()
	initClickhouse()

	go hub.run()
	go mapRooms()
	go announceCount()
	go saveDB()
	go saveLogs()

	http.HandleFunc("/ws/", hub.wsHandler)
	http.HandleFunc("/cmd/", cmdHandler)
	http.HandleFunc("/list/", listHandler)
	http.HandleFunc("/debug/", debugHandler)

	go fastStart()

	const SOCK = "/tmp/statbate.sock"
	if err := os.Remove(SOCK); err != nil {
		log.Fatal("remove failed: ", err)
	}
	unixListener, err := net.Listen("unix", SOCK)
	if err != nil {
		log.Fatal("Listen (UNIX socket): ", err)
	}
	defer unixListener.Close()
	if err = os.Chmod(SOCK, 0o777); err != nil {
		log.Fatal("chmod failed: ", err)
	}
	log.Fatal(http.Serve(unixListener, nil))
}

func initSql() {
	var db *sqlx.DB
	var err error

	switch *sqlDriver {
	case "sqlite":
		db, err = sqlx.Connect("sqlite", "database.sqlite")
	case "mysql":
		db, err = sqlx.Connect("mysql", "user:passwd@unix(/var/run/mysqld/mysqld.sock)/stat?interpolateParams=true")
	}
	if err != nil {
		log.Fatal("initSql err: ", err.Error())
	}
	Sql = db
}

func initClickhouse() {
	db, err := sqlx.Connect("clickhouse", "tcp://127.0.0.1:9000/?database=statbate&compress=true&debug=false")
	if err != nil {
		log.Fatal("initClickhouse err: ", err.Error())
	}
	Clickhouse = db
}

func wJson(s string) {
	if err := os.WriteFile("/tmp/fastStart.txt", []byte(s), 0o644); err != nil {
		log.Fatal("write file err: ", err.Error())
	}
}

func fastStart() {
	val, err := os.ReadFile("/tmp/fastStart.txt")
	if err != nil {
		fmt.Println(err)
		return
	}
	list := make(map[string]*Info)
	if err := json.Unmarshal(val, &list); err != nil {
		fmt.Println(err.Error())
		return
	}
	for k, v := range list {
		fmt.Println("fastStart:", k, v.Server, v.Proxy)
		_, err = http.Get("https://statbate.com/cmd/?room=" + k + "&server=" + v.Server + "&proxy=" + v.Proxy)
		logError(err)
		time.Sleep(100 * time.Millisecond)
	}
}
