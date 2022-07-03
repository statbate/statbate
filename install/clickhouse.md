https://clickhouse.com/docs/en/getting-started/install/

```
apt-get install -y apt-transport-https ca-certificates dirmngr
apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 8919F6BD2B48D754

echo "deb https://packages.clickhouse.com/deb stable main" | sudo tee \
    /etc/apt/sources.list.d/clickhouse.list

apt-get update

apt-get install -y clickhouse-server clickhouse-client
systemctl start clickhouse-server
```

Disable logs.
```
# nano /etc/clickhouse-server/config.d/z_log_disable.xml
<?xml version="1.0"?>
<yandex>
    <asynchronous_metric_log remove="1"/>
    <metric_log remove="1"/>
    <query_thread_log remove="1" />
    <query_log remove="1" />
    <query_views_log remove="1" />
    <part_log remove="1"/>
    <session_log remove="1"/>
    <text_log remove="1" />
    <trace_log remove="1"/>
</yandex>
```
```
# nano /etc/clickhouse-server/config.xml
<level>warning</level>
```
