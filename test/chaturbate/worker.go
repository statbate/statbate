package main

import (
	"fmt"
	"time"
	"net/url"
	"strconv"
	"encoding/json"
	"github.com/gorilla/websocket"
)

type Input struct {
	Args   []string `json:"args"`
	Method string   `json:"method"`
}

type Donate struct {
	From   string `json:"from_username"`
	Amount int64   `json:"amount"`
}

func statRoom(room, server string, u url.URL) {
	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil); if err != nil {
		fmt.Println(err.Error())
		return
	}
	timeout := time.Now().Unix() + 60*60
	for {
		
		_, message, err := c.ReadMessage(); if err != nil {
			fmt.Println(err.Error())
			break 
		}
		
		if time.Now().Unix() > timeout { 
			fmt.Println("Timeout room:", room)
			break 
		}
		
		m := string(message)
		
		if m == "o"{
			c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__777\",\"password\":\"anonymous\",\"room\":\"` + room + `\",\"room_password\":\"12345\"}}"]`))
			continue
		}
		
		if m == "h"{
			c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"` + room + `\",\"private_room\":\"false\"}}"]`))
			continue
		}

		// remove a[...]
		if len(m) > 3 && m[0:2] == "a[" {
			m, _ = strconv.Unquote(m[2 : len(m)-1])
		}
		
		input := Input{}
		if err := json.Unmarshal([]byte(m), &input); err != nil {
			fmt.Println(err.Error())
			continue;
		}

		if(input.Method == "onAuthResponse"){
			c.WriteMessage(websocket.TextMessage, []byte(`["{\"method\":\"joinRoom\",\"data\":{\"room\":\"` + room + `\"}}"]`))
			continue
		}
		
		if(input.Method == "onRoomCountUpdate"){
			fmt.Println(input.Args[0], "online")
			continue;
		}

		donate := Donate{}
		if(input.Method == "onNotify"){
			
			timeout = time.Now().Unix() + 60*60
			
			if err := json.Unmarshal([]byte(input.Args[0]), &donate); err != nil {
				fmt.Println(err.Error())
				continue;
			}
			if(len(donate.From) > 3){
				fmt.Println(donate.From, " send ", donate.Amount, "tokens")
			}
		}
	}
	c.Close()
}
