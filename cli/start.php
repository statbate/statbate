#!/usr/bin/env php
<?php

require_once('/var/www/statbate/root/private/init.php');

function isJson($string) {
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}

function getPage($url){
	$command = escapeshellcmd("/home/stat/python/cloudscraper.py $url");
	return shell_exec($command);
}

function stopBot($name){
	if(empty($name)){
		echo "empty name\n";
		return false;
	}
	echo "[".date('H:i:s', time())."] stop $name\n";
	echo "https://statbate.com/cmd/?exit=$name\n";
	file_get_contents("https://statbate.com/cmd/?exit=$name");
}

function startBot($name, $server){
	if(empty($name) || empty($server)){
		echo "empty name or server\n";
		return false;
	}
	echo "[".date('H:i:s', time())."] start $name $server\n";
	echo "https://statbate.com/cmd/?room=$name&server=$server\n";
	file_get_contents("https://statbate.com/cmd/?room=$name&server=$server");
}

function getRoomParams($room){
	/*
	$content = getPage('https://chaturbate.com/'.$room);
	$doc = new DOMDocument();
	$doc->loadHTML($content, LIBXML_NOERROR |  LIBXML_ERR_NONE);
	$sxml = simplexml_import_dom($doc);
	foreach ($sxml->xpath('//script') as $script) {
		$text = (string)$script;
		if(strpos($text, 'window.initial')) {
			preg_match('/window.initialRoomDossier = \"(.*?)\"/', $text, $matches);
			$str = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
				return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
			}, $matches[1]);
			$params = json_decode($str, true);
			if(!is_array($params)){
				
				var_dump($params);
				
				return false;
			}
			return $params;
		}
	}
	file_put_contents('/home/stat/php/page.html', $content);
	*/
	$json = getPage("https://chaturbate.com/api/chatvideocontext/$room/");
	$params = json_decode($json, true);
	if(!is_array($params)){
		var_dump($params);
		return false;
	}
	return $params;
}

function getServerWS($params){
	$host = $params['wschat_host'];
	if(empty($host)){
		echo "server empty 1\n";
		return false;
	}
	$host = str_replace("https://", "", $host);
	$host = str_replace("/ws", "", $host);
	$server = explode('.', $host);
	if(empty($server['0'])){
		echo "server empty 2\n";
		return false;
	}
	return $server['0'];
}

function updateGender($id, $params){
	global $db;
	
	$arr = ['male', 'female', 'trans', 'couple'];
	$gender = array_search($params['broadcaster_gender'], $arr);
	if(empty($gender)){
		var_dump($params['broadcaster_gender']);
		return;
	}
	$query = $db->prepare("UPDATE `room` SET `gender` = :gender WHERE `id` = :id");
	$query->bindParam(':id', $id);
	$query->bindParam(':gender', $gender);
	$query->execute();
}

function updateFollowers($name, $num){
	global $db;
	if(empty($num)){
		return false;
	}
	$query = $db->prepare("UPDATE `room` SET `fans` = :fans WHERE `name` = :name");
	$query->bindParam(':name', $name);
	$query->bindParam(':fans', $num);
	$query->execute();
}

function getAPIList(){
	global $redis;
	$stat = [];
	$json = getPage('https://chaturbate.com/affiliates/api/onlinerooms/?format=json&wm=50xHQ');
	randSleep();
	if(!isJson($json)){
		return false;
	}
	$arr = json_decode($json, true);
	if(!is_array($arr) || empty($arr)){
		return false;
	}
	$redis->setex('chaturbateList', 86400, $json);
	usort($arr, function($a, $b){
		return $a['num_users'] < $b['num_users'];
	});
	$viewers = 0;	
	foreach($arr as $val){
		cacheResult('getRoomInfo', ['name' => $val['username']], 3600, true);
		updateFollowers($val['username'], $val['num_followers']);
		$viewers += $val['num_users'];
		if($val['num_users'] < 10){
			continue;
		}
		$stat[] = $val['username'];
	}
	if(empty($stat) || !is_array($stat)){
		return false;
	}
	return $stat;
}

function randSleep(){
	$t = mt_rand(10,15);
	echo "wait $t seconds...\n";
	sleep($t);
}

function getFromPages(){
	$pages = 3;
	echo "get list from pages";
	for($i=1; $i<=$pages; $i++){
		$html = getPage("https://chaturbate.com/?page=$i");
		preg_match_all('/alt="(.*)\'s/', $html, $tmp);
		foreach($tmp[1] as $k => $v){
			$pos = stripos($v, ' ');
			if($pos === false){
				$rooms[] = $v;
			}
		}
		echo " $i";
		sleep(mt_rand(10,15));
	}
	echo "\n";
	if(empty($rooms) || !is_array($rooms)){
		return false;
	}
	return $rooms;
}

function sendStart($room){
	global $onlineList, $timeEnd;
	if(time() > $timeEnd){
		die("Stop task\n");
	}
	if(array_key_exists($room, $onlineList)){
		echo $room." already online\n";
		return true;
	}
	echo "start add $room\n";
	randSleep();
	$info = cacheResult('getRoomInfo', ['name' => $room], 3600, true);
	if(!$info){
		echo "cant getRoomInfo\n";
		return false;
	}
	$params = getRoomParams($room);
	if(!$params){
		echo "cant getRoomParams\n";
		return false;
	}
	updateGender($info['id'], $params);
	$server = getServerWS($params);
	if(!$server){
		echo "cant getServerWS\n";
		return false;
	}
	startBot($room, $server);
	//die;
}

function importList(){
	global $redis, $onlineList;
	$online = file_get_contents('https://statbate.com/list/');
	if(isJson($online)){
		$onlineList = json_decode($online, true);
		if(!empty($onlineList) && count($onlineList) > 100){
			$redis->setex('importList', 3600, $online);
			return;
		}
	}
	$online = $redis->get('importList');
	if($online !== false && isJson($online)){
		$onlineList = json_decode($online, true);
		foreach($onlineList as $key => $val){
			echo "import from importList $key {$val['server']}\n";
			startBot($key, $val['server']);
		}
	}
}

$timeEnd = time() + 590;

$onlineList = [];
importList();

$arr100 = cacheResult('getTop', [], 600, true);
$arrPagesList =  getFromPages();
$arrApiList = getAPIList();

if(!empty($arrApiList) && !empty($arrPagesList)){ // Stop offline rooms
	foreach($onlineList as $key => $val){
		if(!in_array($key, $arrApiList) && $val['last'] < time()+60*15){
			if(in_array($key, $arrPagesList)){
				continue;
			}
			stopBot($key);
		}
	}
}

echo "Top100 \n";
foreach($arr100 as $val){ // Start top 100	
	if(in_array($val['name'], $arrPagesList) || in_array($val['name'], $arrApiList)){
		sendStart($val['name']);
	}
}

echo "PagesList \n";
foreach($arrPagesList as $val){ // Start hiden rooms
	if(!in_array($val, $arrApiList)){
		sendStart($val);
	}
}

echo "ApiList \n";
foreach($arrApiList as $val){ // Start api list by num_users
	sendStart($val);
}
