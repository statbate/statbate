package main

import "log"

func logError(err error) {
	if err != nil {
		log.Printf("err: %s\n", err.Error())
	}
}
