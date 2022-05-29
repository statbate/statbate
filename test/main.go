package main

import (
	"flag"
	"log"
	"net/http"
	"net/url"
	"strings"

	"golang.org/x/net/html"
)

var servers = map[string]string{
	"bongacams":  "wss://%s.bcccdn.com/websocket",
	"chaturbate": "https://chaturbate.com",
}

func getServers() string {
	res := make([]string, len(servers))
	for k := range servers {
		res = append(res, k)
	}
	return strings.Join(res, ", ")
}

var (
	roomFlag   = flag.String("room", "all", "room on server to track")
	serverFlag = flag.String("server", "", "server to track")
)

func main() {
	flag.Parse()

	if *serverFlag == "" {
		log.Fatalf("must choose the server %v", getServers())
	}

	if _, ok := servers[*serverFlag]; !ok {
		log.Fatalf("unknown server specified: %v", *serverFlag)
	}

	wss, err := getWSS(*serverFlag, *roomFlag)
	if err != nil {
		log.Fatalf("failed to get wss addr: %v", err)
	}

	u := url.URL{Scheme: "wss", Host: wss, Path: "/ws/555/kmdqiune/websocket"}

	statRoom(*roomFlag, *serverFlag, u)
}

// get wss addr
func getWSS(server string, room string) (string, error) {
	var wss string
	addr := servers[*serverFlag]
	rsp, err := http.Get(addr + "/" + room + "/")
	if err != nil {
		return "", err
	}
	defer rsp.Body.Close()

	var found bool
	var parse func(*html.Node)
	parse = func(n *html.Node) {
		if n.Type == html.ElementNode && n.Data == "script" {
			for _, a := range n.Attr {
				if a.Key == "type" && a.Val == "text/javascript" {
					found = true
				}
			}
			if found && n.NextSibling != nil {
				parse(n.NextSibling)
			}
		}
		if found && strings.Contains(n.Data, "initialRoomDossier") {
			n.Data = strings.ReplaceAll(n.Data, `\u0022`, `"`)
			n.Data = strings.ReplaceAll(n.Data, `\u002D`, `-`)
			idx := strings.Index(n.Data, "wschat_host")
			if idx == -1 {
				return
			}
			n.Data = n.Data[idx+len("wschat_host")+12:]
			idx = strings.Index(n.Data, `/ws"`)
			if idx == -1 {
				return
			}
			wss = n.Data[:idx]
			return
		}

		for c := n.FirstChild; c != nil; c = c.NextSibling {
			parse(c)
		}
	}

	node, err := html.Parse(rsp.Body)
	if err != nil {
		return "", err
	}

	parse(node)

	return wss, nil
}
