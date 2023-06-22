<?php

$dir = '/var/www/statbate/root';

if(empty($dbname)){
	$dbname = 'chaturbate';
}

if(empty($clname)){
	$clname = 'statbate';
}

require_once($dir.'/private/db/mysql.php');
require_once($dir.'/private/db/clickhouse.php');
require_once($dir.'/private/db/redis.php');
require_once($dir.'/private/func/simple.php');
require_once($dir.'/private/func/db.php');
