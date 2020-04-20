#!/usr/bin/env php
<?php
if(php_sapi_name() != "cli"){
	die;
}

$pid = getmypid();

require_once('/var/www/chaturbate100.com/func.php');
require_once('/home/stat/php/inc.php');

function getRoomServer($room, $id){
	global $db;
    $html = getPage("https://chaturbate.com/$room/");
    preg_match('/chatws(.*)\.highwebmedia/', $html, $server); 
    if(empty($server['1'])){
		return false;
	}
	$server['1'] = str_replace("\u002D", "-", $server['1']);
	$result = preg_replace("/[^0-9-]/", "", $server['1']);
	if(empty($result)){
		return false;
	}
	return $result;
}

function startBot($name, $server){
	$server = 'chatws'.$server;
	$date = date('H:i:s', time());
	echo "[$date] start $name $server\n";
	file_get_contents("https://chaturbate100.com/cmd/?room=$name&server=$server");
}

function checkPID($pid){
	global $redis;
	$x = $redis->get('startLock');
	if($x === false || $x != $pid){
		return false;
	}
	return true;
}

function checkWorker($room){
	global $workerList;
	if(!empty($workerList) && array_key_exists($room, $workerList)){
		return true;
	}
	return false;
}

function addTaks($key){
	global $redis;
	if(checkWorker($key)){
		return;
	}
	$room = getRoomInfo($key);
	$c = getCacheName('server'.$key);
	$id = getCache($c);
	if($id === false){ // cache for fast startup
		$id = getRoomServer($key, $room['id']);
		if($id == false){
			return;
		}
		$redis->setex($c, 1200, $id);
		sleep(1);
	}
	startBot($key, $id);
}

function cacheList(){
	global $redis;
	$json = file_get_contents('https://chaturbate100.com/list/');
	$arr = json_decode($json, true);
	foreach($arr as $k => $v){
		$id = preg_replace('/[^0-9.]+/', '', $v);
		if(!empty($id)){
			$c = getCacheName('server'.$k);
			$redis->setex($c, 1200, $id);
			//echo "add cache: $k = $c = $id\n";
		}
	}
	return $arr;
}

$z = $redis->get(getCacheName('apiList'));
if($z === false){
	die("Empty list\n");
}

$var['api'] = json_decode($z, true);
$workerList = cacheList();

// add top 100 first
$top100 = $redis->get(getCacheName('top100list'));
if($top100 !== false){
	$top100 = json_decode($top100, true);
	foreach($top100 as $val){
		addTaks($val);
	}
}

$lock = $redis->get('startLock');
if($lock !== false){
	die;
}

$redis->setex('startLock', 285, $pid);
foreach($var['api']['main'] as $key => $val){
	if(!checkPID($pid)){
		die;
	}
	addTaks($key);
}
$redis->delete('startLock');
