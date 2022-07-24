package main

import (
	"fmt"
	"log"
	"math/rand"
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
	Check chan string
	Stop  chan string
	Del   chan string
	Add   chan Info
}

var (
	Mysql, Clickhouse *sqlx.DB

	json = jsoniter.ConfigCompatibleWithStandardLibrary

	save = make(chan saveData, 100)
	slog = make(chan saveLog, 100)

	rooms = &Rooms{
		Count: make(chan int),
		Json:  make(chan string),
		Check: make(chan string),
		Stop:  make(chan string),
		Del:   make(chan string),
		Add:   make(chan Info),
	}
)

func main() {
	rand.Seed(time.Now().UnixNano())

	startConfig()

	initMysql()
	initClickhouse()

	go mapRooms()
	go announceCount()
	go saveDB()
	go saveLogs()
	go broadcast()

	http.HandleFunc("/ws/", wsHandler)
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

func randInt(min int, max int) int {
	return min + rand.Intn(max-min)
}

func randString(n int) string {
	const alphanum = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
	var bytes = make([]byte, n)
	rand.Read(bytes)
	for i, b := range bytes {
		bytes[i] = alphanum[b%byte(len(alphanum))]
	}
	return string(bytes)
}

func fastStart() {
	defer func() {
		go updateFileRooms()
	}()
	dat, err := os.ReadFile(conf.Conn["start"])
	if err != nil {
		fmt.Println(err)
		return
	}
	list := make(map[string]Info)
	if err := json.Unmarshal(dat, &list); err != nil {
		fmt.Println(err.Error())
		return
	}
	now := time.Now().Unix()
	for k, v := range list {
		if now > v.Last+60*20 {
			continue
		}
		fmt.Println("fastStart:", k, v.Server, v.Proxy)
		workerData := Info{
			room:   k,
			Server: v.Server,
			Proxy:  v.Proxy,
			Online: v.Online,
			Start:  v.Start,
			Last:   now,
			Rid:    v.Rid,
			Income: v.Income,
			Dons:   v.Dons,
			Tips:   v.Tips,
		}
		startRoom(workerData)
		time.Sleep(100 * time.Millisecond)
	}
}
