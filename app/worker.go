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

func announceCount() {
	for {
		time.Sleep(30 * time.Second)
		msg, err := json.Marshal(AnnounceCount{Count: len(getRoomMap())})
		if err == nil {
			hub.broadcast <- msg
		}
	}
}

func statRoom(ch chan Info, chQuit chan struct{}, room, server, proxy string, info *tID, u url.URL) {
	fmt.Println("Start", room, "server", server, "proxy", proxy)

	local := make(chan Info)

	now := time.Now().Unix()
	workerData := Info{room, server, proxy, now, now, "0", 0}

	Dialer := *websocket.DefaultDialer

	proxyMap := make(map[string]string)
	proxyMap["fr"] = "ip:port"
	proxyMap["ca"] = "ip:port"

	if _, ok := proxyMap[proxy]; ok {
		Dialer = websocket.Dialer{
			Proxy: http.ProxyURL(&url.URL{
				Scheme: "http", // or "https" depending on your proxy
				Host:   proxyMap[proxy],
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

	go func(local chan Info, room string, workerData Info, c *websocket.Conn) {

		timeout := time.Now().Unix() + 60*60

		for {
			_, message, err := c.ReadMessage()
			if err != nil {
				fmt.Println(err.Error())
				if checkWorker(room) {
					close(chWorker.Map[room].chQuit)
				}
				break
			}

			now = time.Now().Unix()
			if now > timeout {
				fmt.Println("Timeout room:", room)
				if checkWorker(room) {
					close(chWorker.Map[room].chQuit)
				}
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
				local <- workerData
				continue
			}

			donate := Donate{}
			if input.Method == "onNotify" {

				timeout = now + 60*60

				workerData.Last = now
				local <- workerData

				if err := json.Unmarshal([]byte(input.Args[0]), &donate); err != nil {
					fmt.Println(err.Error())
					continue
				}
				if len(donate.From) > 3 && donate.Amount > 0 {
					save <- saveData{room, donate.From, info.Id, donate.Amount, now}

					workerData.Income += donate.Amount
					local <- workerData

					//fmt.Println(donate.From)
					//fmt.Println(donate.Amount)
				}
			}
		}
	}(local, room, workerData, c)

	for {
		select {
		case <-chQuit:
			fmt.Println("Exit room:", room)
			removeRoom(room)
			return

		case <-ch:
			//fmt.Println("get request:", room)
			chWorker.Map[room].ch <- workerData

		case m := <-local:
			//fmt.Println("update workerData: ", m)
			workerData = m
		}
	}
}
