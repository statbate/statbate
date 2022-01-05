package main

import (
	"fmt"
	"github.com/gorilla/websocket"
	"net/http"
	"net/url"
	"strconv"
	"time"
)

var uptime = time.Now().Unix()

type Input struct {
	Args   []string `json:"args"`
	Method string   `json:"method"`
}

type Donate struct {
	From   string `json:"from_username"`
	Amount int64  `json:"amount"`
}

type AnnounceCount struct {
	Count int `json:"count"`
}

type AnnounceDonate struct {
	Room    string `json:"room"`
	Donator string `json:"donator"`
	Amount  int64  `json:"amount"`
}

func mapRooms(ch chan Info) {
	for {
		select {
		case m := <-ch:
			//fmt.Println("map channel:", len(ch), cap(ch))
			if checkRoom(m.Room) {
				rooms.Lock()	
				//fmt.Printf("%v channel add %v in rooms.Map \n", time.Now().UnixMilli(), m.Room )
				rooms.Map[m.Room] = &Info{m.Room, m.Server, m.Start, m.Last, m.Online, m.Income}
				rooms.Unlock()
			}
		}
	}
}

func countRooms() int {
	rooms.RLock()
	defer rooms.RUnlock()
	return len(rooms.Map)
}

func announceCount() {
	for {
		time.Sleep(30 * time.Second)
		msg, err := json.Marshal(AnnounceCount{Count: countRooms()})
		if err == nil {
			hub.broadcast <- msg
		}
	}
}

func statRoom(ch chan Info, chQuit chan struct{}, room, server string, proxy bool, info *tID, u url.URL) {
	//fmt.Println("Start", room, "server", server)

	Dialer := *websocket.DefaultDialer

	if proxy {
		Dialer = websocket.Dialer{
			Proxy: http.ProxyURL(&url.URL{
				Scheme: "http", // or "https" depending on your proxy
				Host:   "ip:port",
				Path:   "/",
			}),
			HandshakeTimeout: 45 * time.Second, // https://pkg.go.dev/github.com/gorilla/websocket
		}
	}

	c, _, err := Dialer.Dial(u.String(), nil)
	if err != nil {
		fmt.Println(err.Error())
		return
	}
	defer c.Close()

	now := time.Now().Unix()
	workerData := Info{room, server, now, now, "0", 0}
	ch <- workerData
	timeout := now + 60*60

	for {

		select {
		case <-chQuit:
			//fmt.Println("Exit room:", room)
			fmt.Println("removeRoom channel", room)
			removeRoom(room)
			return
		default:
		}

		_, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println(err.Error())
			break
		}

		now = time.Now().Unix()
		if now > timeout {
		//if now < timeout {
			fmt.Println("Timeout room:", room)
			//fmt.Printf("%v Timeout room %v \n", time.Now().UnixMilli(), room )
			break
		}

		m := string(message)

		if m == "o" {
			c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__777\",\"password\":\"anonymous\",\"room\":\"`+room+`\",\"room_password\":\"12345\"}}"]`))
			continue
		}

		if m == "h" {
			c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"`+room+`\",\"private_room\":\"false\"}}"]`))
			continue
		}

		// remove a[...]
		if len(m) > 3 && m[0:2] == "a[" {
			m, _ = strconv.Unquote(m[2 : len(m)-1])
		}

		input := Input{}
		if err := json.Unmarshal([]byte(m), &input); err != nil {
			fmt.Println(err.Error())
			continue
		}

		if input.Method == "onAuthResponse" {
			c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"`+room+`\"}}"]`))
			continue
		}

		if input.Method == "onRoomCountUpdate" {
			workerData.Online = input.Args[0]
			ch <- workerData
			continue
		}

		donate := Donate{}
		if input.Method == "onNotify" {

			timeout = now + 60*60
			workerData.Last = now
			ch <- workerData

			if err := json.Unmarshal([]byte(input.Args[0]), &donate); err != nil {
				fmt.Println(err.Error())
				continue
			}
			if len(donate.From) > 3 && donate.Amount > 0 {
				save <- saveData{room, donate.From, info.Id, donate.Amount, now}

				workerData.Income += donate.Amount
				ch <- workerData

				//fmt.Println(donate.From)
				//fmt.Println(donate.Amount)
			}
		}
	}
	//fmt.Printf("%v end func removeRoom %v \n", time.Now().UnixMilli(), room )
	removeRoom(room)
}
