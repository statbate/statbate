package main

import (
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
)

type Rooms struct {
	Count chan int
	Json  chan string
	Add   chan Info
	Del   chan string
}

var (
	Mysql, Clickhouse *sqlx.DB

	hub = newHub()

	json = jsoniter.ConfigCompatibleWithStandardLibrary

	save = make(chan saveData, 100)
	slog = make(chan saveLog, 100)

	rooms = &Rooms{
		Count: make(chan int),
		Json:  make(chan string),
		Add:   make(chan Info),
		Del:   make(chan string),
	}
)

func main() {
	startConfig()

	initMysql()
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
	os.Remove(SOCK)
	unixListener, err := net.Listen("unix", SOCK)
	if err != nil {
		log.Fatal("Listen (UNIX socket): ", err)
	}
	defer unixListener.Close()
	os.Chmod(SOCK, 0777)
	log.Fatal(http.Serve(unixListener, nil))
}

func initMysql() {
	db, err := sqlx.Connect("mysql", conf.Conn["mysql"])
	if err != nil {
		panic(err)
	}
	Mysql = db
}

func initClickhouse() {
	db, err := sqlx.Connect("clickhouse", conf.Conn["click"])
	if err != nil {
		panic(err)
	}
	Clickhouse = db
}

func wJson(s string) {
	os.WriteFile("/tmp/fastStart.txt", []byte(s), 0644)
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
		http.Get("https://statbate.com/cmd/?room=" + k + "&server=" + v.Server + "&proxy=" + v.Proxy)
		time.Sleep(100 * time.Millisecond)
	}
}
