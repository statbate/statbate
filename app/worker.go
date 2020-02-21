package main

import (
	"fmt"
	"time"
	"net/url"
	"github.com/gorilla/websocket"
)

type Worker struct {
	arg, method string
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
	if err != nil { // dial
		return
	}
	addRoom(room, server)
	args := make(map[string]interface{})
	worker := &Worker{
		arg: "", 
		method: "", 
		hello: []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__777\",\"password\":\"anonymous\",\"room\":\"` + room + `\",\"room_password\":\"12345\"}}"]`),
		join: []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"` + room + `\"}}"]`),
		count: []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"` + room + `\",\"private_room\":\"false\"}}"]`),
		delay: time.Now().Unix() + 20,
		timeout: time.Now().Unix() + 60*60,
	}
	
	for {
		msgType, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println("Read error room:", room)
			fmt.Println(err.Error())
			break 
		}
		
		if !checkRoom(room) { 
			fmt.Println("Exit room:", room)
			break
		}
		
		if time.Now().Unix() > worker.timeout { 
			fmt.Println("Timeout room:", room)
			break 
		}
		
		if worker.delay < time.Now().Unix() {
			err := c.WriteMessage(msgType, worker.count)
			if err != nil { // Write error room
				fmt.Println("Write error room:", room)
				break
			}
			worker.delay = time.Now().Unix() + 60
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
				sendPost(room, donator, amount)
				worker.timeout = time.Now().Unix() + 60*60
				//fmt.Println("Room[", room, "]", donator, "donate", amount, "tokens")
			}
		}
	}
	
	if checkRoom(room) {
		removeRoom(room)
	}
	
	c.Close()
}
