<?php
require_once('../private/init.php');

// получаем онлайн из базы 

$d = $redis->get(getCacheName('apiOnline'));
if($d !== false){
	$d = json_decode($d, true);
	echo "<font color='#541550'><b>online {$d['rooms']} rooms and {$d['viewers']} viewers</b></font>";
}
