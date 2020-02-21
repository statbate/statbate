#!/usr/bin/env php
<?php

if(php_sapi_name() != "cli"){
	die;
}

$pid = getmypid();

require_once('/var/www/chaturbate100.com/func.php');

function newCookie(){
	global $redis;
	$command = escapeshellcmd('/home/stat/python/cookie.py');
	$json = shell_exec($command);
	$arr = json_decode($json, true);
	$cookie = '';
	foreach($arr as $k => $v){
		if($k != 'ua'){
			$cookie .= "$k=$v;";
		}
	}
	$result = ['cookie' => $cookie, 'agent' => $arr['ua']['User-Agent']];
	$cname = getCacheName('pageCookie');
	$redis->setex($cname, 60*60*24*30, json_encode($result));
	return $result;
}

function getCookie(){
	global $redis;
	$cname = getCacheName('pageCookie');
	$result = $redis->get($cname);
	if($result === false){
		return newCookie();
	}
	return json_decode($result, true);
}

function checkCloudflare($html){
	$res = preg_match("/<title>(.*)<\/title>/siU", $html, $title_matches);
	if(!empty($title_matches[1])){
		$title = preg_replace('/\s+/', ' ', $title_matches[1]);
		$title = trim($title);
		if (strpos($title, 'Cloudflare') !== false) {
			return true;
		}
	}
	return false;
}

function getPage($url){
	$h = getCookie();
	$cookie_file = '/home/stat/php/cli/cookies.txt';
	
	$headers = [
		"User-Agent: {$h["agent"]}",
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_COOKIESESSION, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
	curl_setopt($ch, CURLOPT_COOKIE, "{$h["cookie"]}");
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	
	if(checkCloudflare($output)){
		newCookie();
		die;
	}
	
	return $output;
}

function getOnlineList(){
	global $db, $redis;
	$gender = ['m', 'f', 's', 'c'];
	$stat = []; $d = ['rooms' => 0, 'viewers' => 0];
	$arr = json_decode(getPage('https://chaturbate.com/affiliates/api/onlinerooms/?format=json&wm=50xHQ'), true);
	
	if(!is_array($arr) || empty($array)){
		die;
	}
	
	$d['rooms'] = count($arr);
	usort($arr, function($a, $b) {
		return $a['num_users'] < $b['num_users'];
	});
	
	$avg = round(array_sum(array_column($arr, 'num_users'))/count($arr));
	
	foreach($arr as $val){
		$d['viewers'] += $val['num_users'];
		$stat['list'][$val['username']] = $val['num_users'];
		if($val['num_users'] < $avg){
			continue;
		}
		$stat['main'][$val['username']] = $val['num_users'];
		$room = getRoomInfo($val['username']);
		if(!empty($room)){
			//save online
			$query = $db->prepare("INSERT INTO `online` (`rid`, `online`, `time`) VALUES (:rid, :online, unix_timestamp(now()))");
			$query->bindParam(':rid', $room['id']);
			$query->bindParam(':online', $val['num_users']);
			$query->execute();
			
			//update gender
			$g = array_search($val['gender'], $gender);
			if($room['gender'] != $g){
				$query = $db->prepare("UPDATE `room` SET `gender` = :gender WHERE `id` = :id");
				$query->bindParam(':id', $room['id']);
				$query->bindParam(':gender', $g);
				$query->execute();
			}
		}		
	}
	$redis->setex(getCacheName('apiOnline'), 900, json_encode($d));
	$redis->setex(getCacheName('apiList'), 900, json_encode($stat));
	return $stat;
}

function getRoomServer($room, $id){
	global $db;
    $html = getPage("https://chaturbate.com/$room/");
    preg_match('/chatws(.*)\.highwebmedia/', $html, $server); 
    if(empty($server['1'])){
		return false;
	}
	$server['1'] = (int) filter_var($server['1'], FILTER_SANITIZE_NUMBER_INT);
	return $server['1'];
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

$var['api'] = getOnlineList();
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
