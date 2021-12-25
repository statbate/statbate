package main

import (
	"encoding/json"
	"fmt"
	"unicode/utf8"
)

type Data struct {
	Args   []string `json:"args"`
	Method string   `json:"method"`
}

type DonateMessage struct {
	From   string `json:"from_username"`
	Amount int    `json:"amount"`
}

type Message struct {
	Type string `json:"type"`
}

func trimFirstRune(s string) string {
	_, i := utf8.DecodeRuneInString(s)
	return s[i:]
}

func parseMes(message string, room string) Data {
	var parsedData []string
	var parsedData2 Data
	var _data = trimFirstRune(message)
	if err := json.Unmarshal([]byte(_data), &parsedData); err != nil {
		fmt.Println("[1] There was an error:", err)
	}
	if err := json.Unmarshal([]byte(parsedData[0]), &parsedData2); err != nil {
		fmt.Println("[2] There was an error:", err)
	}

	switch parsedData2.Method {
	case "onRoomCountUpdate":
		var response int
		if err := json.Unmarshal([]byte(parsedData2.Args[0]), &response); err != nil {
			fmt.Println("[onRoomCountUpdate] There was an error:", err)
		}

		fmt.Println("["+room+"]Online:", response)
		break
	case "onNotify":
		var response Message
		if err := json.Unmarshal([]byte(parsedData2.Args[0]), &response); err != nil {
			fmt.Println("[onNotify] There was an error:", err)
		}

		switch response.Type {
		default:
			//fmt.Println("Unknown notify type", response.Type, " - Message: ", parsedData2.Args[0])
			break
		case "tip_alert":
			var donateInfo DonateMessage
			if err := json.Unmarshal([]byte(parsedData2.Args[0]), &donateInfo); err != nil {
				fmt.Println("[onNotify] There was an error:", err)
			}

			fmt.Println("["+room+"]tip_alert: ", donateInfo.Amount, donateInfo.From)
			break
		}
		break
	}

	return parsedData2
}
