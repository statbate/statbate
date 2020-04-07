```
apt-get install redis-server
```
```
# nano /etc/redis/redis.conf

...
unixsocket /var/run/redis/redis-server.sock
unixsocketperm 777
...

```
