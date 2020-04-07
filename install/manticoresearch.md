https://manticoresearch.com/downloads/

```
wget https://github.com/manticoresoftware/manticoresearch/releases/download/3.4.0/manticore_3.4.0-200326-0686d9f0-release.buster_amd64-bin.deb
dpkg -i manticore_3.4.0-200326-0686d9f0-release.buster_amd64-bin.deb
```

```
# nano /etc/

index stat {
	type = rt
	rt_mem_limit = 1024M
	rt_attr_uint = did
	rt_attr_uint = rid
	rt_attr_uint = token
	rt_attr_timestamp = time
	rt_field = tmp
	path = /var/lib/manticore/data/stat
}

searchd {
    listen = 127.0.0.1:9312
    listen = 127.0.0.1:9306:mysql41
    log = /var/log/manticore/searchd.log
    query_log = /var/log/manticore/query.log
    pid_file = /var/run/manticore/searchd.pid
    binlog_path = /var/lib/manticore/data
}
```

```
systemctl enable manticore.service
systemctl start manticore.service
systemctl status manticore.service 
```
