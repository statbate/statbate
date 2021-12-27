package main

import (
	"fmt"
	"time"
	"net/url"
	"strconv"
	"github.com/gorilla/websocket"
)

type Worker struct {
	arg, method string
	hello, join, count []byte
	delay, timeout int64
}

func getMethod(msg string) (string, string, bool) {
	if len(msg) < 2 { // o, h, g
		return msg, "", true
	}
	data, ok := parseMes(msg)
	if !ok {
		return "", "", false
	}
	arg := ""
	if len(data.Args) > 0 {
		arg = data.Args[0]
	}
	return data.Method, arg, true
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
			fmt.Println("Wrong getMethod:", room)
			continue 
		}
		
		switch worker.method {

		case "o":
			c.WriteMessage(websocket.TextMessage, worker.hello)

		case "onAuthResponse":
			c.WriteMessage(websocket.TextMessage, worker.join)
			
		case "onRoomCountUpdate":
			updateRoom(room, "online", worker.arg)			

		case "onNotify":
			args, ok = parseArg(worker.arg)
			if ok && args["amount"] != nil {
				donator := args["from_username"].(string)
				amount  := int64(args["amount"].(float64))
				if len(donator) > 3 { // Skip empty from_username
					sendPost(room, donator, amount)
				}
				//fmt.Println(string(message))
				//fmt.Println("Room[", room, "]", donator, "donate", amount, "tokens")
			}
			worker.timeout = time.Now().Unix() + 60*60
			updateRoom(room, "last", strconv.FormatInt(time.Now().Unix(), 10))
		}
	}
	
	if checkRoom(room) {
		removeRoom(room)
	}
	
	c.Close()
}
