```
apt-get update
apt-get upgrade
apt-get install htop bwm-ng strace lsof iotop git build-essential screen
adduser --disabled-login stat
```

```
git clone https://github.com/poiuty/chaturbate100.com.git

mkdir /home/app/go
mkdir /home/app/php
mkdir /home/app/python
mkdir /var/www/chaturbate100.com

cp -r /chaturbate100.com/app /home/app/go
cp -r /chaturbate100.com/cli/*.php /home/app/php
cp -r /chaturbate100.com/cli/*.py /home/app/python
cp -r /chaturbate100.com/html/* /var/www/chaturbate100.com

chown -R stat:stat /home/app
chown -R www-data:www-data /var/www/chaturbate100.com
```


<img src="https://raw.githubusercontent.com/poiuty/chaturbate100.com/master/html/img/github.jpg">
