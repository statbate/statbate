```
apt-get update
apt-get upgrade
apt-get install htop bwm-ng strace lsof iotop nginx redis-server python-pip git build-essential screen
apt-get install php-cli php-fpm php-redis php-mysql php-xml php-json php-gd php-igbinary php-curl php-mbstring php-xml
```
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

<img src="https://raw.githubusercontent.com/poiuty/chaturbate100.com/master/html/img/github.jpg">
