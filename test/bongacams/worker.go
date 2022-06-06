package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"time"

	"github.com/gorilla/websocket"
)

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

func getAMF(room string) (bool, *AuthResponse) {
	
	v := &AuthResponse{}
	
	req, err := http.NewRequest(http.MethodPost, "https://rt.bongocams.com/tools/amf.php?res=771840&t=1654437233142", strings.NewReader(`method=getRoomData&args[]=`+room))
	if err != nil {
		fmt.Println(err.Error())
		return false, v
	}
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
	req.Header.Add("X-Requested-With", "XMLHttpRequest")
	req.Header.Add("Accept", "application/json")
	req.Header.Add("Referrer", "https://bongacams.com")
	req.Header.Add("User-agent", "curl/7.79.1")

	rsp, err := http.DefaultClient.Do(req)
	if err != nil {
		fmt.Println(err.Error())
		return false, v
	}
	defer rsp.Body.Close()

	if err = json.NewDecoder(rsp.Body).Decode(v); err != nil {
		fmt.Println(err.Error())
		return false, v
	}
	
	return true, v
}

func startRoom(room, server, proxy string, u url.URL) {
	// curl -vvv -X POST -H "Content-Type: application/x-www-form-urlencoded; charset=UTF-8" -H "X-Requested-With: XMLHttpRequest" -d "method=getRoomData" -d "args[]=Icehotangel"   "https://rt.bongocams.com/tools/amf.php?res=771840&t=1654437233142"

	fmt.Println("Start", room, "server", server, "proxy", proxy)

	ok, v := getAMF(room)
	if !ok {
		fmt.Println("exit: no amf parms")
		return
	}	
	
	Dialer := *websocket.DefaultDialer

	proxyMap := make(map[string]string)
	proxyMap["us"] = "aaa:port"
	proxyMap["fi"] = "bbb:port"

	if _, ok := proxyMap[proxy]; ok {
		Dialer = websocket.Dialer{
			Proxy: http.ProxyURL(&url.URL{
				Scheme: "http", // or "https" depending on your proxy
				Host:   proxyMap[proxy],
				Path:   "/",
			}),
			HandshakeTimeout: 45 * time.Second, // https://pkg.go.dev/github.com/gorilla/websocket
		}
	}
	
	c, _, err := Dialer.Dial(u.String(), nil)
	if err != nil {
		fmt.Println(err.Error())
		return
	}

	pid := 1	
	now := time.Now().Unix()
	ping := now+30
	
	if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":%d,"name":"joinRoom","args":["%s",{"username":"%s","displayName":"%s","location":"%s","chathost":"%s","isRu":%t,"isPerformer":false,"hasStream":false,"isLogged":false,"isPayable":false,"showType":"public"},"%s"]}`, pid, v.UserData.Chathost, v.UserData.Username, v.UserData.DisplayName, v.UserData.Location, v.UserData.Chathost, v.UserData.IsRu, v.LocalData.DataKey))); err != nil {
		fmt.Println(err.Error())
		return
	}
	
	
	defer c.Close()
	for {
		_, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println("return")
			return
		}

		if pid == 1 {
			pid++
			fmt.Println("send ChatModule.connect")
			if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":%d,"name":"ChatModule.connect","args":["public-chat"]}`, pid))); err != nil {
				fmt.Println(err.Error())
				return
			}
			continue
		}
		
		now = time.Now().Unix()
			
		if(now > ping){
			pid++
			fmt.Println("send ping", pid)
			if err = c.WriteMessage(websocket.TextMessage, []byte(fmt.Sprintf(`{"id":%d,"name":"ping"}`, pid))); err != nil {
				return
			}
			ping = now+30
		}
			
		//fmt.Println(string(message))d

		m := &ServerResponse{}

		if err = json.Unmarshal(message, m); err != nil {
			fmt.Println(err.Error())
			continue
		}
		
		if m.Type == "ServerMessageEvent:PERFORMER_STATUS_CHANGE" && bytes.Contains(m.Body, []byte(`offile`)) {
			return
		}
		
		if m.Type == "ServerMessageEvent:ROOM_CLOSE" {
			return
		}
		
		if m.Type == "ServerMessageEvent:INCOMING_TIP" {
			d := &DonateResponse{}
			if err = json.Unmarshal(m.Body, d); err == nil {
				fmt.Println(d.F.Username, " send ", d.A, "tokens")
			}
		}
	}
}
