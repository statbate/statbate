package main

import (
	"net/http"
	"math/rand"
    "time"
)

var saveStat = &Save{donate: make(chan *saveData), online: make(chan *saveData)}

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

	http.ListenAndServe("localhost:8080", nil)
}
