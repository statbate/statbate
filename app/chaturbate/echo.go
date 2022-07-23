package main

import (
	//	"fmt"
	"net/http"
	"sync"

	"github.com/gorilla/websocket"
)

type Client struct {
	sync.RWMutex
	Map map[*websocket.Conn]struct{}
}

var wsClients = &Client{Map: make(map[*websocket.Conn]struct{})}

func broadcast(b []byte) {
	wsClients.Lock()
	for conn := range wsClients.Map {
		if err := conn.WriteMessage(1, b); err != nil {
			delete(wsClients.Map, conn)
			conn.Close()
		}
	}
	wsClients.Unlock()
}

func wsHandler(w http.ResponseWriter, r *http.Request) {
	conn, err := websocket.Upgrade(w, r, w.Header(), 1024, 1024)
	if err != nil {
		return
	}
	wsClients.Lock()
	wsClients.Map[conn] = struct{}{}
	wsClients.Unlock()
}
