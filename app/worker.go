package main

import (
	"fmt"
	"time"
	"net/url"
	"strconv"
	"github.com/gorilla/websocket"
)

type Input struct {
	Args   []string `json:"args"`
	Method string   `json:"method"`
}

type Donate struct {
	From   string `json:"from_username"`
	Amount int64  `json:"amount"`
}

type AnnounceDonate struct {
    Room string     `json:"room"`
    Donator string  `json:"donator"`
    Amount int64    `json:"amount"`
}

func statRoom(room, server string, u url.URL) {
	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil); if err != nil {
		fmt.Println(err.Error())
		return
	}
	info, ok := getRoomInfo(room); if !ok {
		fmt.Println("No room in MySQL:", room)
		return
	}
	addRoom(room, server)
	timeout := time.Now().Unix() + 60*60
	for {
		
		_, message, err := c.ReadMessage(); if err != nil {
			fmt.Println(err.Error())
			break 
		}
		
		if !checkRoom(room) { 
			fmt.Println("Exit room:", room)
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
			updateRoomOnline(room, input.Args[0])
			continue;
		}

		donate := Donate{}
		if(input.Method == "onNotify"){
			
			timeout = time.Now().Unix() + 60*60
			updateRoomLast(room)
			
			if err := json.Unmarshal([]byte(input.Args[0]), &donate); err != nil {
				fmt.Println(err.Error())
				continue;
			}
			if(len(donate.From) > 3){
				saveStat.donate <- &saveData{rid: info.Id, donator: donate.From, token: donate.Amount}
				updateRoomIncome(room, donate.Amount)
				
				if donate.Amount > 99 {
					msg, err := json.Marshal(AnnounceDonate{Room: room, Donator: donate.From, Amount: donate.Amount}); if err == nil {
						hub.broadcast <- msg
					}
				}
				
				//fmt.Println(donate.From)
				//fmt.Println(donate.Amount)
			}
		}
	}
	if checkRoom(room) {
		removeRoom(room)
	}
	c.Close()
}
