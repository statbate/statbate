package main

import (
	"os"
	"fmt"
	"net/url"
	"math/rand"
)

func randInt(min int, max int) int {
    return min + rand.Intn(max-min)
}

func main() {
	
	if len(os.Args) < 3 {
		fmt.Println("./test room server")
		return
	}
	
	room := os.Args[1]
	server := os.Args[2]
	
	u := url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"}
	
	statRoom(room, server, u)
}
