<img src="https://raw.githubusercontent.com/poiuty/chaturbate100.com/master/html/img/github.jpg">

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

1. <a href="https://github.com/poiuty/chaturbate100.com/blob/master/install/mariadb.md">Mariadb</a><br/>
2. <a href="https://github.com/poiuty/chaturbate100.com/blob/master/install/manticoresearch.md">ManticoreSearch</a><br/>
3. <a href="https://github.com/poiuty/chaturbate100.com/blob/master/install/nginx.md">Nginx</a><br/>
4. <a href="https://github.com/poiuty/chaturbate100.com/blob/master/install/php.md">PHP</a><br/>
5. <a href="https://github.com/poiuty/chaturbate100.com/blob/master/install/python.md">Python</a><br/>
6. <a href="https://github.com/poiuty/chaturbate100.com/blob/master/install/redis.md">Redis</a><br/>
7. <a href="https://github.com/poiuty/chaturbate100.com/blob/master/install/app.md">App</a>
