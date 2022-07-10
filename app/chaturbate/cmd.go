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
	room   string
	Server string `json:"server"`
	Proxy  string `json:"proxy"`
	Online string `json:"online"`
	Start  int64  `json:"start"`
	Last   int64  `json:"last"`
	Income int64  `json:"income"`
}

type Debug struct {
	Goroutines int
	Uptime     int64
	Alloc      uint64
	HeapSys    uint64
	Process    []string
}

type Worker struct {
	chQuit chan struct{}
}

type Workers struct {
	sync.RWMutex
	Map map[string]*Worker
}

var (
	memInfo  runtime.MemStats
	chWorker = &Workers{Map: make(map[string]*Worker)}
)

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

func listRooms() string {
	rooms.Json <- ""
	s := <-rooms.Json
	wJson(s)
	return s
}

func listHandler(w http.ResponseWriter, _ *http.Request) {
	fmt.Fprint(w, listRooms())
}

func debugHandler(w http.ResponseWriter, _ *http.Request) {

	chWorker.RLock()
	tmp := chWorker.Map
	chWorker.RUnlock()

	x := []string{}
	for k, _ := range tmp {
		x = append(x, k)
	}

	runtime.ReadMemStats(&memInfo)
	j, err := json.Marshal(Debug{Goroutines: runtime.NumGoroutine(), Alloc: memInfo.Alloc, HeapSys: memInfo.HeapSys, Uptime: uptime, Process: x})
	if err == nil {
		fmt.Fprint(w, string(j))
	}
}

func cmdHandler(w http.ResponseWriter, r *http.Request) {

	if !conf.List[r.Header.Get("X-REAL-IP")] {
		fmt.Fprint(w, "403")
		return
	}

	params := r.URL.Query()
	if len(params["room"]) > 0 && len(params["server"]) > 0 && len(params["proxy"]) > 0 {
		room := params["room"][0]
		server := params["server"][0]
		proxy := params["proxy"][0]
		if checkWorker(room) {
			fmt.Println("Already track:", room)
			return
		}

		info, ok := getRoomInfo(room)
		if !ok {
			fmt.Println("No room in MySQL:", room)
			return
		}

		chQuit := make(chan struct{})

		chWorker.Lock()
		chWorker.Map[room] = &Worker{chQuit: chQuit}
		chWorker.Unlock()

		go statRoom(chQuit, room, server, proxy, info, url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"})
	}
	if len(params["exit"]) > 0 {
		room := strings.Join(params["exit"], "")
		if checkWorker(room) {
			chWorker.Lock()
			close(chWorker.Map[room].chQuit) // exit gorutine
			chWorker.Unlock()
			removeRoom(room)
		}
	}
}
