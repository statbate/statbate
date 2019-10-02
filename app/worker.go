package main

import (
	"fmt"
	"time"
	"net/url"
	"strconv"
	"github.com/gorilla/websocket"
)

type Worker struct {
	arg, method, online string
	hello, join, count []byte
	delay, timeout int64
}

func getMethod(msg string) (string, string, bool) {
	arg := ""
	if len(msg) < 2 { // o, h, g
		return msg, arg, true
	}
	data, ok := parseMes(msg)
	if !ok {
		return "", "", false
	}
	if len(data[0].Args) > 0 {
		arg = data[0].Args[0]
	}	
	return data[0].Method, arg, true
}

func statRoom(room, server string, u url.URL) {
	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil)
	if err != nil {
		//fmt.Println("dial:", err)
		return
	}
	addRoom(room, server)
	args := make(map[string]interface{})
	worker := &Worker{
		arg: "", 
		method: "", 
		online: "",
		hello: []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__777\",\"password\":\"anonymous\",\"room\":\"` + room + `\",\"room_password\":\"12345\"}}"]`),
		join: []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"` + room + `\"}}"]`),
		count: []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"` + room + `\",\"private_room\":\"false\"}}"]`),
		delay: time.Now().Unix() + 10,
		timeout: time.Now().Unix() + 60*10,
	}
	
	Loop:
	for {
		msgType, message, err := c.ReadMessage()
		if err != nil {
			//fmt.Println("Read error room:", room)
			break
		}
		
		if !checkRoom(room) {
			//fmt.Println("Exit room:", room)
			break
		}
		
		if time.Now().Unix() > worker.timeout {
			//fmt.Println("Timeout room:", room)
			break
		}
		
		ok := true
		worker.method, worker.arg, ok = getMethod(string(message))
		if !ok {
			//fmt.Println("Wrong getMethod:", room)
			continue
		}

		switch worker.method {

		case "o":
			c.WriteMessage(websocket.TextMessage, worker.hello)

		case "onAuthResponse":
			c.WriteMessage(websocket.TextMessage, worker.join)

		case "onNotify":
			args, ok = parseArg(worker.arg)
			if ok && args["amount"] != nil {
				donator := fmt.Sprintf("%v", args["from_username"])
				amount  := fmt.Sprintf("%v", args["amount"])
				sendPost(room, donator, amount, "0")
				worker.timeout = time.Now().Unix() + 60*30
				fmt.Println("Room[", room, "]", donator, "donate", amount, "tokens")
			}

		case "onRoomCountUpdate":
			if randInt(1, 5) == 1 { 
				o, _ := strconv.ParseInt(worker.arg, 10, 64)
				x := &saveData{room: room, donator: "", token: 0, online: o}
				saveStat.online <- x
			}

		default:
			if worker.delay < time.Now().Unix() {
				worker.delay = time.Now().Unix() + 120
				err := c.WriteMessage(msgType, worker.count)
				if err != nil {
					//fmt.Println("Write error room:", room)
					break Loop
				}
			}
		}
	}
	
	if checkRoom(room) {
		removeRoom(room)
	}
	
	c.Close()
}
