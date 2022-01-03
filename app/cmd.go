package main

import (
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"sync"
	"runtime"
)

type Rooms struct {
	sync.RWMutex
	Name map[string]*Info
}

type Info struct {
	Server string `json:"server"`
	Start  int64  `json:"start"`
	Last   int64  `json:"last"`
	Online string `json:"online"`
	Income int64  `json:"income"`
}

type Debug struct {
	Goroutines int
	Alloc      uint64
	HeapSys    uint64
	Uptime	   int64 
}

var memInfo runtime.MemStats
var chWorker = map[string]chan struct{}{}
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
	
	//m := &sync.RWMutex{}
	//m.Lock()
	delete(chWorker, room)
	//m.Unlock()
}

func checkRoom(room string) bool {
	rooms.RLock()
	defer rooms.RUnlock()
	if _, ok := rooms.Name[room]; ok {
		return true
	}
	return false
}

func listRooms() string {
	rooms.RLock()
	defer rooms.RUnlock()
	t := rooms.Name
	j, err := json.Marshal(t)
	if err == nil {
		return string(j)
	}
	return ""
}

func listHandler(w http.ResponseWriter, r *http.Request) {
	fmt.Fprint(w, listRooms())
}

func debugHandler(w http.ResponseWriter, r *http.Request) {
	runtime.ReadMemStats(&memInfo)
	j, err := json.Marshal(Debug{runtime.NumGoroutine(), memInfo.Alloc, memInfo.HeapSys, uptime})
	if err == nil {
		fmt.Fprint(w, string(j))
	}
}

func cmdHandler(w http.ResponseWriter, r *http.Request) {
	params := r.URL.Query()
	if len(params["room"]) > 0 && len(params["server"]) > 0 && len(params["proxy"]) > 0 {
		room := params["room"][0]
		server := params["server"][0]
		proxy := params["proxy"][0]
		if !checkRoom(room) {
			fmt.Println("Start", room, "server", server)
			
			done := make(chan struct{})
			
			//m := &sync.Mutex{}
			
			//m.Lock()
			chWorker[room] = done
			//m.Unlock()
			
			go statRoom(done, room, server, proxy, url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"})
		
		}
	}
	if len(params["exit"]) > 0 {
		room := strings.Join(params["exit"], "")
		if checkRoom(room) {
			//m := &sync.RWMutex{}
	
			//m.RLock()
			close(chWorker[room]) // exit gorutine
			//m.RUnlock()
			
			removeRoom(room)
		}
	}
}
