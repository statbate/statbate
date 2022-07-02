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
	Method string   `json:"method"`
	Args   []string `json:"args"`
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
			} else {
				logErrorf("json err: %v", err)
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

	now := time.Now().Unix()
	workerData := Info{room: room, Server: server, Proxy: proxy, Start: now, Last: now, Online: "0", Income: 0}

	timeout := now

	rooms.Add <- workerData

	Dialer := *websocket.DefaultDialer

	if _, ok := conf.Proxy[proxy]; ok {
		Dialer = websocket.Dialer{
			Proxy: http.ProxyURL(&url.URL{
				Scheme: "http", // or "https" depending on your proxy
				Host:   conf.Proxy[proxy],
				Path:   "/",
			}),
			HandshakeTimeout: 45 * time.Second, // https://pkg.go.dev/github.com/gorilla/websocket
		}
	}

	defer func() {
		rooms.Del <- room
	}()

	c, _, err := Dialer.Dial(u.String(), nil)
	if err != nil {
		logErrorf("dial err: %v room: %v", err, room)
		return
	}
	defer func() {
		if err = c.Close(); err != nil {
			logErrorf("socket err: %v room: %v", err, room)
		}
	}()

	for {

		select {
		case <-chQuit:
			fmt.Println("Exit room:", room)
			return
		default:
		}

		_, message, err := c.ReadMessage()
		if err != nil {
			logErrorf("websocket err: %v room: %v", err, room)
			return
		}

		now = time.Now().Unix()

		if now > workerData.Last+60*15 || now > timeout+60*60*2 {
			fmt.Println("Timeout room:", room)
			return
		}

		m := string(message)
		slog <- saveLog{Rid: info.Id, Now: now, Mes: m}

		if m == "o" {
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__777\",\"password\":\"anonymous\",\"room\":\"`+room+`\",\"room_password\":\"12345\"}}"]`)); err != nil {
				logErrorf("websocket err: %v room: %v", err, room)
				return
			}
			continue
		}

		if m == "h" {
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"`+room+`\",\"private_room\":\"false\"}}"]`)); err != nil {
				logErrorf("websocket err: %v room: %v", err, room)
				return
			}
			continue
		}

		// remove a[...]
		if len(m) > 3 && m[0:2] == "a[" {
			m, err = strconv.Unquote(m[2 : len(m)-1])
			if err != nil {
				logErrorf("unquote err: %v room: %v", err, room)
			}
		}

		input := Input{}
		if err := json.Unmarshal([]byte(m), &input); err != nil {
			logErrorf("json err: %v room: %v", err, room)
			continue
		}

		if input.Method == "onAuthResponse" {
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"`+room+`\"}}"]`)); err != nil {
				logErrorf("websocket err: %v room: %v", err, room)
				return
			}
			continue
		}

		if input.Method == "onRoomMsg" {
			workerData.Last = now
			rooms.Add <- workerData
			continue
		}

		if input.Method == "onRoomCountUpdate" {
			online, err := strconv.Atoi(input.Args[0])
			if err == nil {
				if online < 10 {
					fmt.Println("few viewers room:", room)
					return
				}
			} else {
				logErrorf("atoi err: %v", err)
			}
			workerData.Online = input.Args[0]
			rooms.Add <- workerData
			continue
		}

		if input.Method == "onPersonallyKicked" {
			fmt.Println("onPersonallyKicked room:", room)
			return
		}

		donate := Donate{}
		if input.Method == "onNotify" {
			workerData.Last = now
			rooms.Add <- workerData
			if err := json.Unmarshal([]byte(input.Args[0]), &donate); err != nil {
				logErrorf("json err: %v room: %v", err, room)
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
