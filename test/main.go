package main

import (
	"encoding/json"
	"fmt"
	"math/rand"
	"net/url"
	"os/exec"
	"runtime"
	"time"
)

type roomInfo struct {
	Status string `json:"room_status"`
	Host   string `json:"wschat_host"`
}

type room struct {
	Username string `json:"username"`
	Users    int    `json:"num_users"`
}

func randInt(min int, max int) int {
	return min + rand.Intn(max-min)
}

func getRooms() []room {
	cmd := exec.Command("../cli/cloudscraper.py", "https://chaturbate.com/affiliates/api/onlinerooms/?format=json&wm=50xHQ")
	stdout, err := cmd.Output()

	if err != nil {
		fmt.Println(err.Error())
	}

	var roomData []room
	if err := json.Unmarshal(stdout, &roomData); err != nil {
		fmt.Println("[getRooms] There was an error:", err)
	}

	return roomData
}

func getRoomInfo(name string) roomInfo {

	cmd := exec.Command("../cli/cloudscraper.py", "https://chaturbate.com/api/chatvideocontext/"+name)
	stdout, err := cmd.Output()

	if err != nil {
		fmt.Println(err.Error())
	}

	//// Print the output
	//fmt.Println(string(stdout))
	//
	//resp, err := http.Get("https://chaturbate.com/api/chatvideocontext/" + name)
	//if err != nil {
	//	log.Fatalln("getRoomInfo err " + err.Error())
	//}
	//body, err := ioutil.ReadAll(resp.Body)
	//if err != nil {
	//	log.Fatalln("getRoomInfo body " + err.Error())
	//}

	var roomData roomInfo
	if err := json.Unmarshal(stdout, &roomData); err != nil {
		fmt.Println("[getRoomInfo] There was an error:", err)
		//fmt.Println(string(stdout))
	}

	return roomData
}

func connectToRoom(room string) {
	roomInfo := getRoomInfo(room)

	var parsedUrl, _ = url.Parse(roomInfo.Host)
	u := url.URL{Scheme: "wss", Host: parsedUrl.Host, Path: "/ws/555/gz2nfluw/websocket"}

	go statRoom(room, u)
}

func main() {
	//
	//if len(os.Args) < 3 {
	//	fmt.Println("./test room server")
	//	return
	//}
	//

	ticker := time.NewTicker(5 * time.Second)
	quit := make(chan struct{})
	go func() {
		for {
			select {
			case <-ticker.C:
				fmt.Println("Rooms monitored ", runtime.NumGoroutine())
				fmt.Println("Rooms monitored ", runtime.NumGoroutine())
				fmt.Println("Rooms monitored ", runtime.NumGoroutine())
				fmt.Println("Rooms monitored ", runtime.NumGoroutine())
				fmt.Println("Rooms monitored ", runtime.NumGoroutine())

			case <-quit:
				ticker.Stop()
				return
			}
		}
	}()

	var rooms = getRooms()
	for _, room := range rooms {
		if room.Users > 100 {
			fmt.Println(room.Username + " connect")
			connectToRoom(room.Username)
			//time.Sleep(0.1 * time.Second)
		}
	}

	//connectToRoom("cheeseburgerjesus")
	//connectToRoom("effyloweell")
	//connectToRoom("kalisa_pearl")
	select {} // block forever
}
