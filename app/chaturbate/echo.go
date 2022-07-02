package main

import (
	"net/http"
	"sync"

	"github.com/gorilla/websocket"
)

func newHub() *Hub {
	return &Hub{
		broadcast:  make(chan []byte),
		register:   make(chan *Client),
		unregister: make(chan *Client),
		clients:    make(map[*Client]bool),
	}
}

type Hub struct {
	sync.RWMutex
	clients    map[*Client]bool
	broadcast  chan []byte
	register   chan *Client
	unregister chan *Client
}

type Client struct {
	hub  *Hub
	conn *websocket.Conn
	send chan []byte
}

func (h *Hub) run() {
	for {
		select {
		case client := <-h.register:
			h.Lock()
			h.clients[client] = true
			h.Unlock()
		case client := <-h.unregister:
			h.Lock()
			if _, ok := h.clients[client]; ok {
				delete(h.clients, client)
				close(client.send)
			}
			h.Unlock()
		case message := <-h.broadcast:
			h.Lock()
			// fmt.Println("map channel:", len(h.broadcast), cap(h.broadcast))
			for client := range h.clients {
				select {
				case client.send <- message:
				default:
					delete(h.clients, client)
					close(client.send)
				}
			}
			h.Unlock()
		}
	}
}

func (c *Client) writePump() {
	defer func() {
		if err := c.conn.Close(); err != nil {
			logErrorf("socket err: %v", err)
		}
	}()

	for {
		message, ok := <-c.send
		if !ok {
			// The hub closed the channel.
			if err := c.conn.WriteMessage(websocket.CloseMessage, []byte{}); err != nil {
				logErrorf("websocket err: %v", err)
			}
			return
		}
		if err := c.conn.WriteMessage(websocket.TextMessage, message); err != nil {
			logErrorf("websocket err: %v", err)
			return
		}
	}
}

func (c *Client) readPump() {
	for {
		// Client close connection
		_, _, err := c.conn.ReadMessage()
		if err != nil {
			logErrorf("socket err: %v", err)
			break
		}
	}
	c.hub.unregister <- c
	if err := c.conn.Close(); err != nil {
		logErrorf("socket err: %v", err)
	}
}

func (hub *Hub) wsHandler(w http.ResponseWriter, r *http.Request) {
	conn, err := websocket.Upgrade(w, r, w.Header(), 1024, 1024)
	if err != nil {
		return
	}
	client := &Client{hub: hub, conn: conn, send: make(chan []byte)}
	client.hub.register <- client
	go client.readPump()
	go client.writePump()
}
