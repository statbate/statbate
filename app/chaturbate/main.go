package main

import (
	"fmt"
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
	hub               = newHub()
	Mysql, Clickhouse *sqlx.DB
	json              = jsoniter.ConfigCompatibleWithStandardLibrary
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
	if err := os.Remove(SOCK); err != nil {
		logErrorf("socket err: %v", err)
		return
	}
	unixListener, err := net.Listen("unix", SOCK)
	if err != nil {
		logErrorf("socket err: %v", err)
		return
	}
	defer func() {
		if err = unixListener.Close(); err != nil {
			logErrorf("socker err: %v", err)
			return
		}
	}()

	if err = os.Chmod(SOCK, os.FileMode((0o777))); err != nil {
		logErrorf("socket err: %v", err)
		return
	}
	if err = http.Serve(unixListener, nil); err != nil {
		logErrorf("socket err: %v", err)
		return
	}
}

func initMysql() {
	db, err := sqlx.Connect("mysql", conf.Conn["mysql"])
	if err != nil {
		logFatalf("database mysql err: %v", err)
	}
	Mysql = db
}

func initClickhouse() {
	db, err := sqlx.Connect("clickhouse", conf.Conn["click"])
	if err != nil {
		logFatalf("database clickhouse err: %v", err)
	}
	Clickhouse = db
}

func wJson(s string) {
	if err := os.WriteFile("/tmp/fastStart.txt", []byte(s), os.FileMode(0o644)); err != nil {
		logErrorf("fs err: %v", err)
	}
}

func fastStart() {
	val, err := os.ReadFile("/tmp/fastStart.txt")
	if err != nil {
		logErrorf("fs err: %v", err)
		return
	}
	list := make(map[string]*Info)
	if err := json.Unmarshal(val, &list); err != nil {
		logErrorf("json err: %v", err)
		return
	}
	for k, v := range list {
		fmt.Println("fastStart:", k, v.Server, v.Proxy)
		rsp, err := http.Get("https://statbate.com/cmd/?room=" + k + "&server=" + v.Server + "&proxy=" + v.Proxy)
		if err != nil || rsp.StatusCode >= 400 {
			logErrorf("http err: %v", err)
		}
		time.Sleep(100 * time.Millisecond)
	}
}
