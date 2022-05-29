package main

import (
	"fmt"
	"net/http"
	"net/url"
	"strconv"
	"time"

	"github.com/gorilla/websocket"
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

func mapRooms() {
	data := make(map[string]*Info)

	for {
		select {
		case m := <-rooms.Add:
			data[m.room] = &Info{Server: m.Server, Proxy: m.Proxy, Start: m.Start, Last: m.Last, Online: m.Online, Income: m.Income}

		case s := <-rooms.Json:
			j, err := json.Marshal(data)
			if err == nil {
				s = string(j)
			}
			rooms.Json <- s

		case <-rooms.Count:
			rooms.Count <- len(data)

		case key := <-rooms.Del:
			delete(data, key)
			removeRoom(key)
		}
	}
}

func announceCount() {
	for {
		time.Sleep(30 * time.Second)
		rooms.Count <- 0
		l := <-rooms.Count
		msg, err := json.Marshal(AnnounceCount{Count: l})
		if err == nil {
			hub.broadcast <- msg
		}
	}
}

func statRoom(chQuit chan struct{}, room, server, proxy string, info *tID, u url.URL) {
	fmt.Println("Start", room, "server", server, "proxy", proxy)

	Dialer := *websocket.DefaultDialer

	proxyMap := make(map[string]string)
	proxyMap["fr"] = "ip:port"
	proxyMap["ca"] = "ip:port"
	proxyMap["fi"] = "ip:port"

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

	now := time.Now().Unix()
	workerData := Info{room: room, Server: server, Proxy: proxy, Start: now, Last: now, Online: "0", Income: 0}

	timeout := now

	for {

		select {
		case <-chQuit:
			fmt.Println("Exit room:", room)
			rooms.Del <- room
			return
		default:
		}

		_, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println(err.Error())
			rooms.Del <- room
			return
		}

		now = time.Now().Unix()

		if now > workerData.Last+60*15 || now > timeout+60*60*2 {
			fmt.Println("Timeout room:", room)
			rooms.Del <- room
			return
		}

		m := string(message)
		slog <- saveLog{info.Id, now, m}

		if m == "o" {
			err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__777\",\"password\":\"anonymous\",\"room\":\"`+room+`\",\"room_password\":\"12345\"}}"]`))
			logError(err)
			continue
		}

		if m == "h" {
			err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"`+room+`\",\"private_room\":\"false\"}}"]`))
			logError(err)
			continue
		}

		// remove a[...]
		if len(m) > 3 && m[0:2] == "a[" {
			m, _ = strconv.Unquote(m[2 : len(m)-1])
		}

		input := Input{}
		if err := json.Unmarshal([]byte(m), &input); err != nil {
			logError(err)
			continue
		}

		if input.Method == "onAuthResponse" {
			err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"`+room+`\"}}"]`))
			logError(err)
			continue
		}

		if input.Method == "onRoomMsg" {
			workerData.Last = now
			rooms.Add <- workerData
		}

		if input.Method == "onRoomCountUpdate" {
			online, err := strconv.Atoi(input.Args[0])
			if err == nil {
				if online < 25 {
					fmt.Println("few viewers room:", room)
					rooms.Del <- room
					return
				}
			}
			workerData.Online = input.Args[0]
			rooms.Add <- workerData
			continue
		}

		if input.Method == "onPersonallyKicked" {
			fmt.Println("onPersonallyKicked room:", room)
			rooms.Del <- room
			return
		}

		donate := Donate{}
		if input.Method == "onNotify" {
			workerData.Last = now
			rooms.Add <- workerData
			if err := json.Unmarshal([]byte(input.Args[0]), &donate); err != nil {
				logError(err)
				continue
			}
			if len(donate.From) > 3 && donate.Amount > 0 {
				save <- saveData{room, donate.From, info.Id, donate.Amount, now}

				workerData.Income += donate.Amount
				rooms.Add <- workerData

				timeout = now

				// fmt.Println(donate.From)
				// fmt.Println(donate.Amount)
			}
		}
	}
}
