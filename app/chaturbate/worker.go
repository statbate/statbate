package main

import (
	"fmt"
	"math/rand"
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

type InputArgs struct {
	Type   string `json:"type"`
	Name   string `json:"username"`
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

func randString(n int) string {
	const alphanum = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
	var bytes = make([]byte, n)
	rand.Read(bytes)
	for i, b := range bytes {
		bytes[i] = alphanum[b%byte(len(alphanum))]
	}
	return string(bytes)
}

func reconnectRoom(room, server, proxy string) {
	rand.Seed(time.Now().UnixNano())
	n := rand.Intn(30-10+1) + 10
	fmt.Printf("Sleeping %d seconds...\n", n)
	time.Sleep(time.Duration(n) * time.Second)
	fmt.Println("reconnect:", room, server, proxy)
	http.Get("https://statbate.com/cmd/?room=" + room + "&server=" + server + "&proxy=" + proxy)
}

func statRoom(chQuit chan struct{}, room, server, proxy string, info *tID, u url.URL) {

	fmt.Println("Start", room, "server", server, "proxy", proxy)

	now := time.Now().Unix()
	workerData := Info{room: room, Server: server, Proxy: proxy, Start: now, Last: now, Online: "0", Income: 0}

	rooms.Add <- workerData

	defer func() {
		rooms.Del <- room
	}()

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

	c, _, err := Dialer.Dial(u.String(), nil)
	if err != nil {
		fmt.Println(err.Error(), room)
		return
	}
	defer c.Close()

	leave := false
	var timeout int64

	for {

		select {
		case <-chQuit:
			fmt.Println("Exit room:", room)
			return
		default:
		}

		c.SetReadDeadline(time.Now().Add(30 * time.Minute))
		_, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println(err.Error(), room)
			return
		}

		now = time.Now().Unix()

		m := string(message)
		slog <- saveLog{info.Id, now, m}

		if leave && now > timeout {
			fmt.Println("room_leave exit:", room)
			return
		}

		if now > workerData.Last+60*20 {
			fmt.Println("no_mes exit:", room)
			return
		}

		if m == "o" {
			anon := "__anonymous__" + randString(9)
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"`+anon+`\",\"password\":\"anonymous\",\"room\":\"`+room+`\",\"room_password\":\"12345\"}}"]`)); err != nil {
				fmt.Println(err.Error(), room)
				return
			}
			continue
		}

		if m == "h" {
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"`+room+`\",\"private_room\":\"false\"}}"]`)); err != nil {
				fmt.Println(err.Error(), room)
				return
			}
			continue
		}

		// remove a[...]
		if len(m) > 3 && m[0:2] == "a[" {
			m, _ = strconv.Unquote(m[2 : len(m)-1])
		}

		input := Input{}
		if err := json.Unmarshal([]byte(m), &input); err != nil {
			fmt.Println(err.Error(), room)
			continue
		}

		if input.Method == "onAuthResponse" {
			if err = c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"`+room+`\"}}"]`)); err != nil {
				fmt.Println(err.Error(), room)
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
			}
			workerData.Online = input.Args[0]
			rooms.Add <- workerData
			continue
		}

		if input.Method == "onPersonallyKicked" {
			fmt.Println("onPersonallyKicked room:", room)
			go reconnectRoom(room, server, proxy)
			return
		}

		if input.Method == "onNotify" {
			workerData.Last = now
			rooms.Add <- workerData

			arg := InputArgs{}

			if err := json.Unmarshal([]byte(input.Args[0]), &arg); err != nil {
				fmt.Println(err.Error(), room)
				continue
			}

			if arg.Type == "clear_app" {
				leave = true
				timeout = now + 60*10
				continue
			}

			if arg.Type == "room_leave" && room == arg.Name {
				leave = true
				timeout = now + 60*10
				//fmt.Println("room_leave:", room)
				continue
			}

			if arg.Type == "room_entry" && room == arg.Name {
				leave = false
				//fmt.Println("room_entry:", room)
				continue
			}

			if arg.Type == "tip_alert" && len(arg.From) > 3 && arg.Amount > 0 {
				save <- saveData{room, arg.From, info.Id, arg.Amount, now}
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
