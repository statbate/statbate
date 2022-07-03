<p align="center"> 
<img src="https://raw.githubusercontent.com/poiuty/statbate/master/www/img/github.jpg">
</p>

```
apt-get update
apt-get upgrade
apt-get install htop bwm-ng strace lsof iotop git build-essential screen
adduser --disabled-login stat
```

```
git clone https://github.com/poiuty/statbate.git

mkdir /home/stat/go
mkdir /home/stat/php
mkdir /home/stat/python
mkdir /var/www/statbate

cp -r /statbate/app /home/stat/go
cp -r /statbate/cli/*.php /home/stat/php
cp -r /statbate/cli/*.py /home/stat/python
cp -r /statbate/html/* /var/www/statbate

chown -R stat:stat /home/stat
chown -R www-data:www-data /var/www/statbate
```

1. <a href="https://github.com/poiuty/statbate/blob/master/install/mariadb.md">Mariadb</a><br/>
2. <a href="https://github.com/poiuty/statbate/blob/master/install/clickhouse.md">ClickHouse</a><br/>
3. <a href="https://github.com/poiuty/statbate/blob/master/install/nginx.md">Nginx</a><br/>
4. <a href="https://github.com/poiuty/statbate/blob/master/install/php.md">PHP</a><br/>
5. <a href="https://github.com/poiuty/statbate/blob/master/install/python.md">Python</a><br/>
6. <a href="https://github.com/poiuty/statbate/blob/master/install/redis.md">Redis</a><br/>
7. <a href="https://github.com/poiuty/statbate/blob/master/install/app.md">App</a>
8. Add <a href="https://github.com/poiuty/statbate/blob/master/install/conf/cron">cron</a>
```
# nano /etc/cron.d/php

*/10 * * * *  stat   php    /home/stat/php/start2.php  > /home/stat/php/log.txt 2>&1
* * * * *  www-data   php    /var/www/statbate/root/index.php  >/dev/null 2>&1

# systemctl restart cron
# systemctl status cron
```
