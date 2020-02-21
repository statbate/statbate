<?php
require_once('/var/www/chaturbate100.com/func.php');

$d = $redis->get(getCacheName('apiOnline'));
if($d !== false){
	$d = json_decode($d, true);
	echo "<font color='#541550'><b>online {$d['rooms']} rooms and {$d['viewers']} viewers</b></font>";
}
