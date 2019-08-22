package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"log"
	"math/rand"
	"net/http"
	"net/url"
	"os"
	"runtime"
	"strconv"
	"time"

	"github.com/gorilla/websocket"
	"golang.org/x/net/proxy"
)

var pid int

type Data []struct {
	Args   []string `json:"args"`
	Method string   `json:"method"`
}

func isJSONString(s string) bool {
	var js string
	return json.Unmarshal([]byte(s), &js) == nil
}

func parseArg(s string) map[string]interface{} {
	arg := make(map[string]interface{})
	if err := json.Unmarshal([]byte(s), &arg); err != nil {
		fmt.Println("There was an error:", err)
	}
	return arg
}

func parseMes(str string) Data {
	var x interface{}
	var data = Data{}
	if err := json.Unmarshal([]byte(str[1:]), &x); err != nil {
		fmt.Println("There was an error:", err)
	}
	if err := json.Unmarshal([]byte(fmt.Sprintf("%v", x)), &data); err != nil {
		fmt.Println("There was an error:", err)
	}
	return data
}

func RandomString(n int) string {
	var letter = []rune("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789")

	b := make([]rune, n)
	for i := range b {
		b[i] = letter[rand.Intn(len(letter))]
	}
	return string(b)
}

func sendPost(room, name, token, online string) {
	v := map[string]string{"room": room, "name": name, "token": token, "pid": strconv.Itoa(pid), "online": online}
	j, _ := json.Marshal(v)
	post, _ := http.Post("https://site/index.php", "application/json", bytes.NewBuffer(j))

	body, _ := ioutil.ReadAll(post.Body)
	if string(body) == "exit" {
		fmt.Println("exit command")
		os.Exit(1)
	}
}

func wsProxy(u url.URL, p string) *websocket.Conn {
	netDialer, err := proxy.SOCKS5("tcp", p, nil, proxy.Direct)
	if err != nil {
		fmt.Fprintln(os.Stderr, "can't connect to the proxy:", err)
		os.Exit(1)
	}
	dialer := websocket.Dialer{NetDial: netDialer.Dial}
	c, _, err := dialer.Dial(u.String(), nil)
	if err != nil {
		log.Fatal("dial:", err)
	}
	return c
}

func wsConnect(u url.URL) *websocket.Conn {
	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil)
	if err != nil {
		log.Fatal("dial:", err)
	}
	return c
}

func statRoom(room string, c *websocket.Conn) {

	defer c.Close()

	sendHelo := `["{\"method\":\"connect\",\"data\":{\"user\":\"__anonymous__` + RandomString(8) + `\",\"password\":\"anonymous\",\"room\":\"` + room + `\",\"room_password\":\"12345\"}}"]`
	joinRoom := `["{\"method\":\"joinRoom\",\"data\":{\"room\":\"` + room + `\"}}"]`
	roomCount := `["{\"method\":\"updateRoomCount\",\"data\":{\"model_name\":\"` + room + `\",\"private_room\":\"false\"}}"]`

	arg := ""
	method := ""
	online := ""
	delay := time.Now().Unix() + 10
	args := make(map[string]interface{})

	timeOut := time.Now().Unix() + 60*10

	for {
		_, message, err := c.ReadMessage()
		if err != nil {
			fmt.Println("read:", err)
			return
		}

		if time.Now().Unix() > timeOut {
			fmt.Println("timeout")
			os.Exit(1)
		}

		msg := string(message)

		if len(msg) < 2 { // o, h, g
			method = msg
		} else {
			data := parseMes(msg)
			method = data[0].Method
			arg = data[0].Args[0]
		}

		switch method {

		case "o":
			c.WriteMessage(websocket.TextMessage, []byte(sendHelo))

		case "onAuthResponse":
			c.WriteMessage(websocket.TextMessage, []byte(joinRoom))
			sendPost(room, "0", "0", "0") // register bot

		case "onNotify":
			args = parseArg(arg)
			if args["amount"] != nil {
				fmt.Println("Room[", room, "]", args["from_username"], "donate", args["amount"], "tokens")
				sendPost(room, fmt.Sprintf("%v", args["from_username"]), fmt.Sprintf("%v", args["amount"]), online)
				timeOut = time.Now().Unix() + 60*60
			}

			//if args["type"] == "room_leave" && args["username"] == room {
			//	fmt.Println("broadcast end")
			//	os.Exit(1)
			//}

		case "onRoomCountUpdate":
			online = arg
			fmt.Println("Room[", room, "] Online:", online)

		default:
			if delay < time.Now().Unix() {
				delay = time.Now().Unix() + 120
				c.WriteMessage(websocket.TextMessage, []byte(roomCount))
			}
		}
	}
}

func main() {
	runtime.GOMAXPROCS(1)
	pid = os.Getpid()

	useProxy := false
	proxyAddr := "1.1.1.1:18191"

	if len(os.Args) < 3 {
		fmt.Println("use: ./bot room server")
		os.Exit(1)
	}

	room := os.Args[1]
	server := os.Args[2]

	u := url.URL{Scheme: "wss", Host: server + ".stream.highwebmedia.com", Path: "/ws/555/kmdqiune/websocket"}
	log.Printf("connecting to %s", u.String())

	if useProxy {
		fmt.Println("use proxy socks5", proxyAddr)
		statRoom(room, wsProxy(u, proxyAddr))
	} else {
		statRoom(room, wsConnect(u))
	}
}
