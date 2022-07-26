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

func mapRooms() {

	data := make(map[string]*Info)

	for {
		select {
		case m := <-rooms.Add:
			data[m.room] = &Info{Server: m.Server, Proxy: m.Proxy, Start: m.Start, Last: m.Last, Online: m.Online, Income: m.Income, Dons: m.Dons, Tips: m.Tips, ch: m.ch}

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

		case room := <-rooms.Check:
			if _, ok := data[room]; !ok {
				room = ""
			}
			rooms.Check <- room

		case room := <-rooms.Stop:
			if _, ok := data[room]; ok {
				close(data[room].ch)
			}
		}
	}
}

func announceCount() {
	for {
		time.Sleep(30 * time.Second)
		rooms.Count <- 0
		l := <-rooms.Count
		msg, err := json.Marshal(struct {
			Count int `json:"count"`
		}{Count: l})
		if err == nil {
			ws.Send <- msg
		}
	}
}

func reconnectRoom(workerData Info) {
	n := randInt(10, 30)
	fmt.Printf("Sleeping %d seconds...\n", n)
	time.Sleep(time.Duration(n) * time.Second)
	fmt.Println("reconnect:", workerData.room, workerData.Server, workerData.Proxy)
	workerData.Last = time.Now().Unix()
	startRoom(workerData)
}

func xWorker(workerData Info, u url.URL) {

	fmt.Println("Start", workerData.room, "server", workerData.Server, "proxy", workerData.Proxy)

	rooms.Add <- workerData

	defer func() {
		rooms.Del <- workerData.room
	}()

	Dialer := *websocket.DefaultDialer

	if _, ok := conf.Proxy[workerData.Proxy]; ok {
		Dialer = websocket.Dialer{
			Proxy: http.ProxyURL(&url.URL{
				Scheme: "http", // or "https" depending on your proxy
				Host:   conf.Proxy[workerData.Proxy],
				Path:   "/",
			}),
			HandshakeTimeout: 45 * time.Second, // https://pkg.go.dev/github.com/gorilla/websocket
		}
	}

	c, _, err := Dialer.Dial(u.String(), nil)
	if err != nil {
		fmt.Println(err.Error(), workerData.room)
		return
	}
	defer c.Close()

	leave := false
	var timeout int64

	dons := make(map[string]struct{})

	for {

		select {
		case <-workerData.ch:
			fmt.Println("Exit room:", workerData.room)
			return
		default:
		}

		c.SetReadDeadline(time.Now().Add(30 * time.Minute))
		_, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println(err.Error(), workerData.room)
			if workerData.Income > 1 && !leave {
				go reconnectRoom(workerData)
			}
			return
		}

		now := time.Now().Unix()

		m := string(message)
		slog <- saveLog{Rid: workerData.Rid, Now: now, Mes: m}

		if leave && now > timeout {
			fmt.Println("room_leave exit:", workerData.room)
			return
		}

		if now > workerData.Last+60*20 {
			fmt.Println("no_mes exit:", workerData.room)
			return
		}

		if m == "o" {
			anon := "__anonymous__" + randString(9)
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"`+anon+`\",\"password\":\"anonymous\",\"room\":\"`+workerData.room+`\",\"room_password\":\"12345\"}}"]`)); err != nil {
				fmt.Println(err.Error(), workerData.room)
				return
			}
			continue
		}

		if m == "h" {
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"`+workerData.room+`\",\"private_room\":\"false\"}}"]`)); err != nil {
				fmt.Println(err.Error(), workerData.room)
				return
			}
			continue
		}

		// remove a[...]
		if len(m) > 3 && m[0:2] == "a[" {
			m, _ = strconv.Unquote(m[2 : len(m)-1])
		}

		input := struct {
			Method string   `json:"method"`
			Args   []string `json:"args"`
		}{}

		if err := json.Unmarshal([]byte(m), &input); err != nil {
			fmt.Println(err.Error(), workerData.room)
			continue
		}

		if input.Method == "onAuthResponse" {
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"`+workerData.room+`\"}}"]`)); err != nil {
				fmt.Println(err.Error(), workerData.room)
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
					fmt.Println("few viewers room:", workerData.room)
					return
				}
			}
			workerData.Online = input.Args[0]
			rooms.Add <- workerData
			continue
		}

		if input.Method == "onPersonallyKicked" {
			fmt.Println("onPersonallyKicked room:", workerData.room)
			go reconnectRoom(workerData)
			return
		}

		if input.Method == "onNotify" {
			workerData.Last = now
			rooms.Add <- workerData

			arg := struct {
				Type   string `json:"type"`
				Name   string `json:"username"`
				From   string `json:"from_username"`
				Amount int64  `json:"amount"`
			}{}

			if err := json.Unmarshal([]byte(input.Args[0]), &arg); err != nil {
				fmt.Println(err.Error(), workerData.room)
				continue
			}

			if arg.Type == "clear_app" {
				leave = true
				timeout = now + 60*10
				continue
			}

			if arg.Type == "room_leave" && workerData.room == arg.Name {
				leave = true
				timeout = now + 60*10
				//fmt.Println("room_leave:", workerData.room)
				continue
			}

			if arg.Type == "room_entry" && workerData.room == arg.Name {
				leave = false
				//fmt.Println("room_entry:", workerData.room)
				continue
			}

			if arg.Type == "tip_alert" && len(arg.From) > 3 && arg.Amount > 0 {
				workerData.Tips++
				if _, ok := dons[arg.From]; !ok {
					dons[arg.From] = struct{}{}
					workerData.Dons++
				}
				save <- saveData{Room: workerData.room, From: arg.From, Rid: workerData.Rid, Amount: arg.Amount, Now: now}
				workerData.Income += arg.Amount
				rooms.Add <- workerData
				if leave {
					timeout = now + 60*20
				}

				// fmt.Println(donate.From)
				// fmt.Println(donate.Amount)
			}
		}
	}
}
