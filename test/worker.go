package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/url"
	"strconv"
	"time"

	"github.com/gorilla/websocket"
)

type Input struct {
	Method string   `json:"method"`
	Args   []string `json:"args"`
}

type Donate struct {
	From   string `json:"from_username"`
	Amount int64  `json:"amount"`
}

func statRoom(room string, server string, u url.URL) {
	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil)
	if err != nil {
		logError(err)
		return
	}
	timeout := time.Now().Unix() + 60*60
	for {

		_, message, err := c.ReadMessage()
		if err != nil {
			logError(err)
			break
		}

		if time.Now().Unix() > timeout {
			fmt.Println("Timeout room:", room)
			break
		}

		m := string(message)

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

		if input.Method == "onRoomCountUpdate" {
			fmt.Println(input.Args[0], "online")
			continue
		}

		donate := Donate{}
		if input.Method == "onNotify" {

			timeout = time.Now().Unix() + 60*60

			if err := json.Unmarshal([]byte(input.Args[0]), &donate); err != nil {
				logError(err)
				continue
			}
			if len(donate.From) > 3 {
				fmt.Println(donate.From, " send ", donate.Amount, "tokens")
			}
		}
	}
	err = c.Close()
	logError(err)
}

func logError(err error) {
	if err != nil {
		log.Printf("err: %s\n", err.Error())
	}
}
