package main

import (
	"fmt"
	"encoding/json"
	"strconv"
)

type Data struct {
	Args   []string `json:"args"`
	Method string   `json:"method"`
}

func parseArg(s string) (map[string]interface{}, bool) {
	arg := make(map[string]interface{})
	if err := json.Unmarshal([]byte(s), &arg); err != nil {
		fmt.Println("There was an error:", err)
		return arg, false
	}
	return arg, true
}

func parseMes(s string) (Data, bool) {
    var data = Data{}
    if s[0:2] == "a[" {
		s = s[1:len(s)]
	}
	if s[0:1] == "[" {
		s = s[1:len(s)-1]
	}	
    a, _ := strconv.Unquote(s)
    if err := json.Unmarshal([]byte(a), &data); err != nil {
		fmt.Println(a)
		fmt.Println("There was an error:", err)
		return data, false
	}	
	return data, true
}
