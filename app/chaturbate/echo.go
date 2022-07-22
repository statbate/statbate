package main

import (
//	"fmt"
	"net/http"
	"sync"

	"github.com/gorilla/websocket"
)

type Client struct {
	sync.RWMutex
	Map map[(chan []byte)]struct{}
}

var wsClients = &Client{Map: make(map[(chan []byte)]struct{})}

func broadcast(b []byte) {
	wsClients.RLock()
	for k := range wsClients.Map {
		k <- b
	}
	wsClients.RUnlock()
}

func wsHandler(w http.ResponseWriter, r *http.Request) {
	conn, err := websocket.Upgrade(w, r, w.Header(), 1024, 1024)
	if err != nil {
		return
	}
	defer conn.Close()

	ch := make(chan []byte)

	wsClients.Lock()
	wsClients.Map[ch] = struct{}{}
	wsClients.Unlock()

	//fmt.Println(wsClients.Map)

	defer func() {
		wsClients.Lock()
		delete(wsClients.Map, ch)
		wsClients.Unlock()

		//fmt.Println(wsClients.Map)
	}()

	for {
		select {
		case message := <-ch:
			if err := conn.WriteMessage(1, message); err != nil {
				return
			}
		}
	}
}
