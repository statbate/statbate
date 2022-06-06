package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"net/url"
	"strconv"
	"strings"
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

func statRoomBongocams(chat string, room string, u *url.URL) {
	// curl -vvv -X POST -H "Content-Type: application/x-www-form-urlencoded; charset=UTF-8" -H "X-Requested-With: XMLHttpRequest" -d "method=getRoomData" -d "args[]=Icehotangel"   "https://rt.bongocams.com/tools/amf.php?res=771840&t=1654437233142"

	type AuthResponse struct {
		Status    string `json:"status"`
		LocalData struct {
			DataKey string `json:"dataKey"`
		} `json:"localData"`
		UserData struct {
			Username    string `json:"username"`
			DisplayName string `json:"displayName"`
			Location    string `json:"location"`
			Chathost    string `json:"chathost"`
			IsRu        bool   `json:"isRu"`
		} `json:"userData"`
	}

	type ServerResponse struct {
		TS   int64           `json:"ts"`
		Type string          `json:"type"`
		Body json.RawMessage `json:"body"`
	}

	type DonateResponse struct {
		F struct {
			Username string `json:"username"`
		} `json:"f"`
		A int `json:"a"`
	}

	req, err := http.NewRequest(http.MethodPost, "https://rt.bongocams.com/tools/amf.php?res=771840&t=1654437233142", strings.NewReader(`method=getRoomData&args[]=`+chat))
	if err != nil {
		logError(err)
		return
	}
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
	req.Header.Add("X-Requested-With", "XMLHttpRequest")
	req.Header.Add("Accept", "application/json")
	req.Header.Add("Referrer", "https://bongacams.com")
	req.Header.Add("User-agent", "curl/7.79.1")

	rsp, err := http.DefaultClient.Do(req)
	if err != nil {
		logError(err)
		return
	}
	defer rsp.Body.Close()

	v := &AuthResponse{}

	if err = json.NewDecoder(rsp.Body).Decode(v); err != nil {
		logError(err)
		return
	}

	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil)
	if err != nil {
		logError(err)
		return
	}

	if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":1,"name":"joinRoom","args":["%s",{"username":"%s","displayName":"%s","location":"%s","chathost":"%s","isRu":%t,"isPerformer":false,"hasStream":false,"isLogged":false,"isPayable":false,"showType":"public"},"%s"]}`, v.UserData.Chathost, v.UserData.Username, v.UserData.DisplayName, v.UserData.Location, v.UserData.Chathost, v.UserData.IsRu, v.LocalData.DataKey))); err != nil {
		logError(err)
		return
	}

	timeout := time.Now().Unix() + 60*60
	var pid int64
	ticker := time.NewTicker(30 * time.Second)
	defer ticker.Stop()
	for {
		select {
		case <-ticker.C:
			pid++
			if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":%d,"name":"ping"}`, pid))); err != nil {
				logError(err)
				return
			}
		default:
			_, message, err := c.ReadMessage()
			if err != nil {
				logError(err)
				break
			}

			if time.Now().Unix() > timeout {
				fmt.Println("Timeout room:", room)
				break
			}

			m := &ServerResponse{}
			if err = json.Unmarshal(message, m); err == nil {
				switch m.Type {
				case "ServerMessageEvent:PERFORMER_STATUS_CHANGE":
					if bytes.Contains(m.Body, []byte(`offile`)) {
						err = c.Close()
						logError(err)
						return
					}
				case "ServerMessageEvent:ROOM_CLOSE":
					err = c.Close()
					logError(err)
					return
				case "ServerMessageEvent:INCOMING_TIP":
					d := &DonateResponse{}
					if err = json.Unmarshal(m.Body, d); err != nil {
						c.Close()
						logError(err)
						return
					}
					fmt.Println(d.F.Username, " send ", d.A, "tokens")
				}
			} else {
				status := make(map[string]interface{})
				if err = json.Unmarshal(message, &status); err == nil {
					if fmt.Sprintf("%d", status["id"]) == fmt.Sprintf("%d", pid) && status["error"] == nil {
						continue
					} else {
						fmt.Printf("unknown message received: %v", status)
						c.Close()
						return
					}
				}
			}
		}
	}
}

func statRoomChaturbate(room string, server string, u *url.URL) {
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
