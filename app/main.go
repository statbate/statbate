package main

import (
	"net/http"
	"math/rand"
    "time"
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

	http.ListenAndServe("127.0.0.1:8080", nil)
}
