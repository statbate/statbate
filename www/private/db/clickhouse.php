<?php

try {
	$clickhouse = new PDO("mysql:host=127.0.0.1;port=9004;dbname=statbate", 'default', '');
	$clickhouse->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch(PDOException $e) {
	die('ClickHouse ERROR'.PHP_EOL);
}
