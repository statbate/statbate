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

func trimFirstRune(s string) string {
	_, i := utf8.DecodeRuneInString(s)
	return s[i:]
}

func parseMes(message string) (Data, DonateMessage) {
	var parsedData []string
	var parsedData2 Data
	var _data = trimFirstRune(message)
	if err := json.Unmarshal([]byte(_data), &parsedData); err != nil {
		fmt.Println("[1] There was an error:", err)
	}
	if err := json.Unmarshal([]byte(parsedData[0]), &parsedData2); err != nil {
		fmt.Println("[2] There was an error:", err)
	}
	var donMessage DonateMessage

	switch parsedData2.Method {
	case "onNotify":
		if err := json.Unmarshal([]byte(parsedData2.Args[0]), &donMessage); err != nil {
			fmt.Println("[onNotify] There was an error:", err)
		}
		break
	}

	return parsedData2, donMessage
}
