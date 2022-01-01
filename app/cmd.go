package main

import (
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"sync"
)

type Rooms struct {
	sync.Mutex
	Name map[string]*Info
}

type Info struct {
	Server string `json:"server"`
	Start  int64  `json:"start"`
	Last   int64  `json:"last"`
	Online string `json:"online"`
	Income int64  `json:"income"`
}

var rooms = &Rooms{Name: make(map[string]*Info)}

func addRoom(room, server string, now int64) {
	rooms.Lock()
	defer rooms.Unlock()
	rooms.Name[room] = &Info{server, now, now, "0", 0}
}

func updateRoomLast(room string, now int64) {
	if checkRoom(room) {
		rooms.Lock()
		defer rooms.Unlock()
		rooms.Name[room].Last = now
	}
}

func updateRoomOnline(room string, val string) {
	if checkRoom(room) {
		rooms.Lock()
		defer rooms.Unlock()
		rooms.Name[room].Online = val
	}
}

func updateRoomIncome(room string, val int64) {
	if checkRoom(room) {
		rooms.Lock()
		defer rooms.Unlock()
		rooms.Name[room].Income += val
	}
}

func removeRoom(room string) {
	rooms.Lock()
	defer rooms.Unlock()
	delete(rooms.Name, room)
}

func checkRoom(room string) bool {
	rooms.Lock()
	defer rooms.Unlock()
	if _, ok := rooms.Name[room]; ok {
		return true
	}
	return false
}

func listRooms() string {
	rooms.Lock()
	defer rooms.Unlock()
	j, err := json.Marshal(rooms.Name)
	if err != nil {
		return ""
	}
	return string(j)
}

func listHandler(w http.ResponseWriter, r *http.Request) {
	fmt.Fprint(w, listRooms())
}

func cmdHandler(w http.ResponseWriter, r *http.Request) {
	params := r.URL.Query()
	if len(params["room"]) > 0 && len(params["server"]) > 0 {
		room := params["room"][0]
		server := params["server"][0]
		if !checkRoom(room) {
			fmt.Println("Start", room, "server", server)
			go statRoom(room, server, url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"})
		}
	}
	if len(params["exit"]) > 0 {
		room := strings.Join(params["exit"], "")
		if checkRoom(room) {
			removeRoom(room)
		}
	}
}
