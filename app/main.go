package main

import (
	"net/http"
    "net"
    "os"
	"log"
    jsoniter "github.com/json-iterator/go"
    _ "github.com/ClickHouse/clickhouse-go"
	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
)

var hub = newHub()
var Mysql, Clickhouse *sqlx.DB
var json = jsoniter.ConfigCompatibleWithStandardLibrary

func initMysql(){
	db, err := sqlx.Connect("mysql", "user:passwd@unix(/var/run/mysqld/mysqld.sock)/stat?interpolateParams=true"); if err != nil {
		panic(err)
	}
	Mysql = db
}

func initClickhouse(){
	db, err := sqlx.Connect("clickhouse", "tcp://127.0.0.1:9000/?database=statbate&compress=true&debug=false"); if err != nil {
		panic(err)
	}
	Clickhouse = db
}

func main() {
	initMysql()
	initClickhouse()
	
	go hub.run()
	go announceCount()
	
	http.HandleFunc("/ws/", hub.wsHandler)
	http.HandleFunc("/cmd/", cmdHandler)
	http.HandleFunc("/list/", listHandler)

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
