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
	Proxy  string `json:"proxy"`
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
var chWorker = &Workers{Map: make(map[string]*Worker)}

func removeRoom(room string) {
	if checkWorker(room) {
		chWorker.Lock()
		//fmt.Printf("%v remove %v from chWorker.Map \n", time.Now().UnixMilli(), room )
		delete(chWorker.Map, room)
		chWorker.Unlock()
	}
}

func checkWorker(room string) bool {
	chWorker.RLock()
	defer chWorker.RUnlock()
	if _, ok := chWorker.Map[room]; ok {
		return true
	}
	return false
}

func getRoomMap() map[string]*Info {
	chWorker.RLock()
	t := chWorker.Map
	chWorker.RUnlock()

	rooms := make(map[string]*Info)
	for key, _ := range t {
		if checkWorker(key){
			chWorker.Map[key].ch <- Info{"", "", "", 0, 0, "", 0}
			m := <-chWorker.Map[key].ch
			rooms[key] = &Info{m.Room, m.Server, m.Proxy, m.Start, m.Last, m.Online, m.Income}
		}
		//fmt.Printf("send %v get %v\n", key, m)
	}
	return rooms
}

func listRooms() string {
	m := getRoomMap()
	j, err := json.Marshal(m)
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
		if !checkWorker(room) {

			info, ok := getRoomInfo(room)
			if !ok {
				fmt.Println("No room in MySQL:", room)
				return
			}

			ch := make(chan Info)
			chQuit := make(chan struct{})

			chWorker.Lock()
			chWorker.Map[room] = &Worker{ch: ch, chQuit: chQuit}
			chWorker.Unlock()

			go statRoom(ch, chQuit, room, server, proxy, info, url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"})

		}
	}
	if len(params["exit"]) > 0 {
		room := strings.Join(params["exit"], "")
		if checkWorker(room) {
			close(chWorker.Map[room].chQuit) // exit gorutine (work and remove from map)
		}
	}
}
