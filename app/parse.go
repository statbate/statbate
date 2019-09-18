package main

import (
	"fmt"
	"encoding/json"
)

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
