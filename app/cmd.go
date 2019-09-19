package main

import (
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"sync"
)

var mutex sync.Mutex
var rooms = make(map[string]string)

func addRoom(room, server string) {
	mutex.Lock()
	rooms[room] = server
	mutex.Unlock()
}

func removeRoom(room string) {
	mutex.Lock()
	delete(rooms, room)
	mutex.Unlock()
}

func checkRoom(room string) bool{
	result := false
	mutex.Lock()
	if _, ok := rooms[room]; ok {
		result = true
	}
	mutex.Unlock()
	return result
}

func cmdHandler(w http.ResponseWriter, r *http.Request){
	msg := ""
	
	mutex.Lock()
	tmp := rooms 
	mutex.Unlock()
	
	for room, server := range tmp {
		msg += server + " " + room + " \n"
	}
	fmt.Fprint(w, msg)
	
	params := r.URL.Query()	
	if len(params["room"]) > 0 && len(params["server"]) > 0 {		
		room := params["room"][0]
		server := params["server"][0]
		if !checkRoom(room) {
			u := url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"}
			fmt.Println("Start", room, "server", server)
			go statRoom(room, server, u)
		}
	}
	
	if len(params["exit"]) > 0 {
		room := strings.Join(params["exit"], "")
		removeRoom(room)
	}
}
