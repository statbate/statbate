Install Golang (https://golang.org/dl/).
```
wget https://dl.google.com/go/go1.14.1.linux-amd64.tar.gz
tar -xf go1.14.1.linux-amd64.tar.gz
ln -s /usr/local/go/bin/go /usr/local/bin/
ln -s /usr/local/go/bin/gofmt /usr/local/bin/
go version
```

Build app.
```
su stat
mkdir ~/go
cd ~/go
go get https://github.com/gorilla/websocket
go get github.com/go-sql-driver/mysql
go get github.com/jmoiron/sqlx
cd app
go build -ldflags "-s -w"
```
