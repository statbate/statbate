```
apt-get install php-cli php-fpm 
apt-get install php-redis php-mysql php-xml php-json php-gd php-igbinary php-curl php-mbstring php-xml
```
```
# nano /etc/php/7.3/fpm/pool.d/www.conf

[www]
listen = /run/php/php7.3-fpm.sock

user = www-data
group = www-data

listen.owner = www-data
listen.group = www-data

pm = static
pm.max_children = 50
pm.max_requests = 10000

chdir = /

php_admin_value[error_log] = /var/log/fpm-php.www.log
php_admin_flag[log_errors] = On
php_admin_flag[report_memleaks] = On

php_admin_value[memory_limit] = 128M
php_admin_value[max_execution_time] = 30
php_admin_value[date.timezone] = "Europe/Moscow"
php_admin_value[upload_max_filesize] = 4M
php_admin_value[post_max_size] = 4M
php_admin_flag[display_errors] = Off
php_admin_flag[expose_php] = Off

php_admin_value[upload_tmp_dir] = "/tmp"

php_admin_value[opcache.enable] = 1
php_admin_value[opcache.interned_strings_buffer] = 8
php_admin_value[opcache.max_accelerated_files] = 4000

php_admin_value[session.gc_probability] = 1
php_admin_value[session.gc_divisor] = 1000
php_admin_value[session.gc_maxlifetime] = 2592000
php_admin_value[session.use_only_cookies] = 1
php_admin_value[session.save_handler] = redis
php_admin_value[session.serialize_handler] = igbinary
php_admin_value[session.save_path] = "unix:///var/run/redis/redis-server.sock?persistent=1&weight=1&database=0"

php_admin_value[disable_functions] = "apache_setenv, chown, chgrp, closelog, define_syslog_variables, dl, exec, ftp_exec, openlog, passthru, pcntl_exec, popen, posix_getegid, posix_geteuid, posix_getpwuid, posix_kill, posix_mkfifo, posix_setpgid, posix_setsid, posix_setuid, posix_uname, proc_close, proc_get_status, proc_nice, proc_open, proc_terminate, syslog, system, pcntl_alarm, pcntl_fork, pcntl_waitpid, pcntl_wait, pcntl_wifexited, pcntl_wifstopped, pcntl_wifsignaled, pcntl_wexitstatus, pcntl_wtermsig, pcntl_wstopsig, pcntl_signal, pcntl_signal_dispatch, pcntl_get_last_error, pcntl_strerror, pcntl_sigprocmask, pcntl_sigwaitinfo, pcntl_sigtimedwait, pcntl_exec, pcntl_getpriority, pcntl_setpriority, shell_exec"
```

```
systemctl restart php7.3-fpm
systemctl status php7.3-fpm
```
