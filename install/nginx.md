```
apt-get install nginx certbot
mkdir /etc/nginx/ssl/
openssl dhparam -out /etc/nginx/ssl/dhparam.pem 2048
chown www-data:www-data /etc/nginx/ssl/dhparam.pem
chmod 400 /etc/nginx/ssl/dhparam.pem
```

Create SSL certificate.
```
certbot certonly --webroot -w /var/www/html -d chaturbate100.com -m test@test.com --agree-tos
```

Update config files.
```
wget -O /etc/nginx/nginx.conf https://raw.githubusercontent.com/poiuty/chaturbate100.com/master/conf/nginx.conf
wget -O /etc/nginx/sites-available/default https://raw.githubusercontent.com/poiuty/chaturbate100.com/master/conf/default
```

```
systemctl restart nginx
systemctl status nginx
```
