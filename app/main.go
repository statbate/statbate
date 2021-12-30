package main

import (
	"net/http"
	"math/rand"
    "time"
    "net"
    "os"
	"log"
    jsoniter "github.com/json-iterator/go"
)

var json = jsoniter.ConfigCompatibleWithStandardLibrary

var saveStat = &Save{donate: make(chan *saveData)}

func randInt(min int, max int) int {
    return min + rand.Intn(max-min)
}

func main() {
	
	rand.Seed(time.Now().UTC().UnixNano())
	
	hub := newHub()
	go hub.run()
	go saveBase(saveStat, hub)
	
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
