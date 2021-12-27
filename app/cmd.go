package main

import (
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"sync"
    "time"
    "strconv"
	"encoding/json"
)

type Rooms struct {
    sync.Mutex
    name map[string]map[string]string
}

var rooms = &Rooms{name: make(map[string]map[string]string) }

func addRoom(room, server string) {
	rooms.Lock()
    defer rooms.Unlock()
	rooms.name[room] = map[string]string{
            "server": server,
            "last": strconv.FormatInt(time.Now().Unix(), 10),
            "online": "0",
    }
}

func updateRoom(room, key, val string){
	if checkRoom(room) {
		rooms.Lock()
		defer rooms.Unlock()
		rooms.name[room][key] = val
	}
}

func removeRoom(room string) {
	rooms.Lock()
    defer rooms.Unlock()
	delete(rooms.name, room)
}

func checkRoom(room string) bool{
	rooms.Lock()
    defer rooms.Unlock()
	if _, ok := rooms.name[room]; ok {
		return true
	}
	return false
}

func listRooms() string {
	rooms.Lock()
    defer rooms.Unlock()
    j, err := json.Marshal(rooms.name)
    if err != nil {
		return ""
	}
    return string(j)
}

func listHandler(w http.ResponseWriter, r *http.Request){
	fmt.Fprint(w, listRooms())
}

func cmdHandler(w http.ResponseWriter, r *http.Request){
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
