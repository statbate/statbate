package main

import (
	"log"
	"os"
)

func logErrorf(format string, args ...interface{}) {
	log.Printf(format, args...)
}

func logFatalf(format string, args ...interface{}) {
	log.Printf(format, args...)
	os.Exit(1)
}
