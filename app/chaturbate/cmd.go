package main

import (
	"fmt"
	"net/http"
	"net/url"
	"os"
	"runtime"
	"strings"
	"sync"
	"time"
)

type Info struct {
	room   string
	Server string `json:"server"`
	Proxy  string `json:"proxy"`
	Online string `json:"online"`
	Rid    int64  `json:"rid"`
	Start  int64  `json:"start"`
	Last   int64  `json:"last"`
	Income int64  `json:"income"`
	Dons   int64  `json:"dons"`
	Tips   int64  `json:"tips"`
}

type Debug struct {
	Goroutines int
	WebSocket  int
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

func updateFileRooms() string {
	for {
		rooms.Json <- ""
		s := <-rooms.Json
		err := os.WriteFile(conf.Conn["start"], []byte(s), 0644)
		if err != nil {
			fmt.Println(err)
		}
		time.Sleep(10 * time.Second)
	}
}

func listHandler(w http.ResponseWriter, _ *http.Request) {
	dat, err := os.ReadFile(conf.Conn["start"])
	if err != nil {
		fmt.Println(err)
		return
	}
	fmt.Fprint(w, string(dat))
}

func debugHandler(w http.ResponseWriter, _ *http.Request) {
	chWorker.RLock()
	tmp := chWorker.Map
	chWorker.RUnlock()

	x := []string{}
	for k, _ := range tmp {
		x = append(x, k)
	}

	ws.Count <- 0
	l := <-ws.Count

	runtime.ReadMemStats(&memInfo)
	j, err := json.Marshal(Debug{Goroutines: runtime.NumGoroutine(), Alloc: memInfo.Alloc, HeapSys: memInfo.HeapSys, Uptime: uptime, WebSocket: l, Process: x})
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
		now := time.Now().Unix()
		workerData := Info{
			room:   params["room"][0],
			Server: params["server"][0],
			Proxy:  params["proxy"][0],
			Online: "0",
			Start:  now,
			Last:   now,
			Rid:    0,
			Income: 0,
			Dons:   0,
			Tips:   0,
		}
		startRoom(workerData)
	}
	if len(params["exit"]) > 0 {
		room := strings.Join(params["exit"], "")
		stopRoom(room)
	}
	fmt.Fprint(w, string("ok"))
}

func startRoom(workerData Info) {
	if checkWorker(workerData.room) {
		fmt.Println("Already track:", workerData.room)
		return
	}

	rid, ok := getRoomInfo(workerData.room)
	if !ok {
		fmt.Println("No room in MySQL:", workerData.room)
		return
	}

	workerData.Rid = rid

	chQuit := make(chan struct{})

	chWorker.Lock()
	chWorker.Map[workerData.room] = &Worker{chQuit: chQuit}
	chWorker.Unlock()

	go xWorker(chQuit, workerData, url.URL{Scheme: "wss", Host: workerData.Server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"})
}

func stopRoom(room string) {
	if checkWorker(room) {
		chWorker.Lock()
		close(chWorker.Map[room].chQuit) // exit gorutine
		chWorker.Unlock()
		removeRoom(room)
	}
}
