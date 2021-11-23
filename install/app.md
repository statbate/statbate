Install Golang (https://golang.org/dl/).
```
wget https://dl.google.com/go/go1.14.1.linux-amd64.tar.gz
tar -xf go1.14.1.linux-amd64.tar.gz
ln -s /usr/local/go/bin/go /usr/local/bin/
ln -s /usr/local/go/bin/gofmt /usr/local/bin/
go version
```

Add user and service.
```
# adduser --disabled-login stat
# nano /etc/systemd/system/app.service
[Unit]
Description=Stat Daemon
After=network.target manticore.service

[Service]
LimitNOFILE=65535
Type=simple
GuessMainPID=no
ExecStart=/home/stat/go/app/app
Restart=always
User=stat
StandardOutput=syslog
StandardError=syslog

[Install]
WantedBy=multi-user.target

# systemctl daemon-reload
# systemctl enable app
```

Build app.
```
su stat
mkdir ~/go
cd ~/go
go get github.com/gorilla/websocket
go get github.com/go-sql-driver/mysql
go get github.com/jmoiron/sqlx
cd app
go build -ldflags "-s -w"
```

Start service.
```
exit
systemctl start app
systemctl status app
```
