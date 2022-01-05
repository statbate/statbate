package main

import (
	"fmt"
	"net/http"
	"net/url"
	"runtime"
	"strings"
	"sync"
//	"time"
)

type Info struct {
	Room   string `json:"room"`
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
	Uptime     int64
}

type Worker struct {
	chQuit chan struct{}
	ch     chan Info
}

type Workers struct {
	sync.RWMutex
	Map map[string]*Worker
}

type Rooms struct {
	sync.RWMutex
	Map map[string]*Info
}

var memInfo runtime.MemStats
var rooms = &Rooms{Map: make(map[string]*Info)}
var chWorker = &Workers{Map: make(map[string]*Worker)}

func removeRoom(room string) {
	if checkRoom(room) {
		
		chWorker.Lock()
		//fmt.Printf("%v remove %v from chWorker.Map \n", time.Now().UnixMilli(), room )
		delete(chWorker.Map, room)
		chWorker.Unlock()
				
		rooms.Lock()
		//fmt.Printf("%v remove %v from rooms.Map \n", time.Now().UnixMilli(), room )
		delete(rooms.Map, room)
		rooms.Unlock()
		

		//rooms.RLock()
		//fmt.Printf("%v %v \n", time.Now().UnixMilli(), rooms.Map[room])
		//rooms.RUnlock()
		
		//chWorker.RLock()
		//fmt.Printf("%v %v \n", time.Now().UnixMilli(), chWorker.Map[room])
		//chWorker.RUnlock()
	}
}

func checkRoom(room string) bool {
	chWorker.RLock()
	defer chWorker.RUnlock()
	if _, ok := chWorker.Map[room]; ok {
		return true
	}
	return false
}

func listRooms() string {
	rooms.RLock()
	defer rooms.RUnlock()
	t := rooms.Map
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
	if len(params["room"]) > 0 && len(params["server"]) > 0 {
		room := params["room"][0]
		server := params["server"][0]
		if !checkRoom(room) {

			info, ok := getRoomInfo(room)
			if !ok {
				fmt.Println("No room in MySQL:", room)
				return
			}

			ch, chQuit := chMap, make(chan struct{})

			chWorker.Lock()
			chWorker.Map[room] = &Worker{chQuit: chQuit, ch: ch}
			chWorker.Unlock()

			proxy := false
			if len(params["proxy"]) > 0 {
				proxy = true
			}

			go statRoom(ch, chQuit, room, server, proxy, info, url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"})

		}
	}
	if len(params["exit"]) > 0 {
		room := strings.Join(params["exit"], "")
		close(chWorker.Map[room].chQuit) // exit gorutine (work and remove from map)
	}
}
