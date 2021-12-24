package main

import (
	"fmt"
	"github.com/gorilla/websocket"
	"net/url"
	"time"
)

type Worker struct {
	arg, method        string
	hello, join, count []byte
	delay, timeout     int64
}

func statRoom(room, server string, u url.URL) {
	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil)
	if err != nil { // dial
		fmt.Println(err.Error())
		return
	}
	//args := make(map[string]interface{})
	worker := &Worker{
		arg:    "",
		method: "",
		hello:  []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__777\",\"password\":\"anonymous\",\"room\":\"` + room + `\",\"room_password\":\"12345\"}}"]`),
		join:   []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"` + room + `\"}}"]`),
		count:  []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"` + room + `\",\"private_room\":\"false\"}}"]`),
		delay:  time.Now().Unix() + 20,
	}

	for {
		_, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println("Read error room:", room)
			fmt.Println(err.Error())
			break
		}
		//log.Printf("recv: %s", message)

		//fmt.Println("----message ----")
		//
		//fmt.Println(string(message))
		//fmt.Println(string(msgType))
		//fmt.Println("----message end ----")

		if len(string(message)) < 2 {
			if string(message) == "o" {
				fmt.Println("----send hello ----")
				c.WriteMessage(websocket.TextMessage, worker.hello)
				continue
			}

			if string(message) == "h" {
				fmt.Println("----got heartbeat ----")
				c.WriteMessage(websocket.TextMessage, worker.count)
				continue
			}

			fmt.Println("----skip it ----")

			continue
		}

		result, donInfo := parseMes(string(message))

		switch result.Method {

		case "o":
			c.WriteMessage(websocket.TextMessage, worker.hello)

		case "onAuthResponse":
			c.WriteMessage(websocket.TextMessage, worker.join)

		case "onNotify":
			if donInfo.Amount > 0 {
				donator := donInfo.From
				amount := donInfo.Amount
				worker.timeout = time.Now().Unix() + 60*60
				//fmt.Println(string(message))
				fmt.Println("Room[", room, "]", donator, "donate", amount, "tokens")
			}
		}
	}
	c.Close()
}
