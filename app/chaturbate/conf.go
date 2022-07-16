package main

type Conf struct {
	Conn  map[string]string
	Proxy map[string]string
	List  map[string]bool
}

var conf = &Conf{
	Conn:  make(map[string]string),
	Proxy: make(map[string]string),
	List:  make(map[string]bool),
}

func startConfig() {
	// DB
	conf.Conn = map[string]string{
		"mysql": "user:passwd@unix(/var/run/mysqld/mysqld.sock)/base?interpolateParams=true",
		"click": "tcp://127.0.0.1:9000/base?compress=true&debug=false",
		"start": "/tmp/fastStart.txt",
	}

	// 3proxy
	conf.Proxy = map[string]string{
		"us": "ip:port",
		"fi": "ip:port",
	}

	// allow ips
	conf.List = map[string]bool{
		"::1":       true,
		"127.0.0.1": true,
	}
}
