List of repositories https://downloads.mariadb.org/mariadb/repositories/

```
apt-get install software-properties-common dirmngr
apt-key adv --fetch-keys 'https://mariadb.org/mariadb_release_signing_key.asc'
add-apt-repository 'deb [arch=amd64] http://mirror.rackspace.com/mariadb/repo/10.4/debian buster main'

apt-get update
apt-get install mariadb-server
mysql_secure_installation
mysql -uroot -p
```

MariaDB config
```
# nano /etc/mysql/my.cnf

[client]
port = 3306
socket = /var/run/mysqld/mysqld.sock
default-character-set = utf8mb4

[mysqld_safe]
socket = /var/run/mysqld/mysqld.sock
nice = 0
malloc-lib = /usr/lib/x86_64-linux-gnu/libjemalloc.so.1

[mysqld]
user = mysql
pid-file = /var/run/mysqld/mysqld.pid
socket = /var/run/mysqld/mysqld.sock
port = 3306
basedir = /usr
datadir = /var/lib/mysql
tmpdir = /tmp
skip-networking
skip-name-resolve

# Other
default-storage-engine = INNODB
character-set-server = utf8mb4
max_connections = 100
wait_timeout = 7200
max_allowed_packet = 16M
skip-external-locking
open_files_limit = 16000

# MyISAM settings
key_buffer_size = 128M

# InnoDB settings
innodb_buffer_pool_size = 32G
innodb_buffer_pool_instances = 32
innodb_log_file_size = 1G
innodb_flush_log_at_trx_commit = 0
innodb_log_buffer_size = 16M
innodb_log_files_in_group = 2
innodb_flush_method = O_DIRECT
innodb_thread_concurrency = 16

# Buffer settings
join_buffer_size = 2M

# TMP & memory settings
tmp_table_size = 32M
max_heap_table_size = 32M

# Try off https://community.centminmod.com/threads/mysqltuner.6779/
query_cache_type = 0 # for OFF
query_cache_size = 0 # to ensure QC is NOT USED

# Slowlog settings
slow_query_log = 1
long_query_time = 5
slow_query_log_file = /var/log/mysql/mariadb-slow.log

#Set General Log
#general_log = on
#general_log_file = /var/log/mysql/full.log

[mysqldump]
# Do not buffer the whole result set in memory before writing it to
# file. Required for dumping very large tables
quick

max_allowed_packet = 32M
default-character-set = utf8mb4

[mysql]
no-auto-rehash
default-character-set = utf8mb4

[isamchk]
key_buffer_size = 8M
sort_buffer_size = 8M
read_buffer = 8M
write_buffer = 8M
default-character-set = utf8mb4

#
# * IMPORTANT: Additional settings that can override those from this file!
#   The files must end with '.cnf', otherwise they'll be ignored.
#
!include /etc/mysql/mariadb.cnf
!includedir /etc/mysql/conf.d/
```
