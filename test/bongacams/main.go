package main

import (
	"os"
	"fmt"
	"net/url"
)

func main() {
	if len(os.Args) < 4 {
		fmt.Println("./test room server proxy")
		return
	}
	
	room := os.Args[1]
	server := os.Args[2]
	proxy := os.Args[3]

	u := url.URL{Scheme: "wss", Host: server + ".bcccdn.com", Path: "/websocket"}

	startRoom(room, server, proxy, u)
}
