#!/usr/bin/env php
<?php

require_once('/var/www/chaturbate100.com/func.php');
require_once('/home/stat/php/inc.php');

function saveOnline($id, $online){
	global $db;
	$query = $db->prepare("INSERT INTO `online` (`rid`, `online`, `time`) VALUES (:rid, :online, unix_timestamp(now()))");
	$query->bindParam(':rid', $id);
	$query->bindParam(':online', $online);
	$query->execute();
}

function updateGender($id, $gender){
	global $db;
	$query = $db->prepare("UPDATE `room` SET `gender` = :gender WHERE `id` = :id");
	$query->bindParam(':id', $id);
	$query->bindParam(':gender', $gender);
	$query->execute();
}

function getFromPages(){
	$pages = 5;
	for($i=0; $i<=$pages; $i++){
		$html = getPage("https://chaturbate.com/?page=$i");
		preg_match_all('/alt="(.*)\'s/', $html, $tmp);
		foreach($tmp[1] as $k => $v){
			$pos = stripos($v, ' ');
			if($pos === false){
				$rooms[] = $v;
			}
		}
	sleep(1);
	}
	return $rooms;
}

function listUpdateRoom($name, $gender, $online){
	$room = getRoomInfo($name);
	if(!empty($room)){
	saveOnline($room['id'], $online);
	if($room['gender'] != $gender){
		updateGender($room['id'], $gender);
		}
	}
}

function getHideInfo($room){
	$gender = ['male', 'female', 'trans', 'couple'];
	$html = getPage("https://chaturbate.com/$room/");
	preg_match('/window.initialRoomDossier = \"(.*)\";/', $html, $a);
	$json = str_replace("\u0022", '"', $a['1']);
	$json = str_replace("\u005C", '\\', $json);
	$x = json_decode($json, true);
	return ['gender' => array_search($x["broadcaster_gender"], $gender), 'viewers' => $x["num_viewers"]];
}

function getOnlineList(){
	global $redis;
	$gender = ['m', 'f', 's', 'c'];
	$stat = []; $d = ['rooms' => 0, 'viewers' => 0];
	$arr = json_decode(getPage('https://chaturbate.com/affiliates/api/onlinerooms/?format=json&wm=50xHQ'), true);
	if(!is_array($arr) || empty($arr)){
		die;
	}
	
	usort($arr, function($a, $b) {
		return $a['num_users'] < $b['num_users'];
	});
	foreach($arr as $val){
		$stat[$val['username']] = $val['num_users'];
		$g = array_search($val['gender'], $gender);
		listUpdateRoom($val['username'], $g, $val['num_users']);
	}
	$x = getFromPages(); // shows not all rooms
	foreach($x as $key => $val){
		if(!array_key_exists($val, $stat)){
			echo $val."\n";
			$b = getHideInfo($val);
			listUpdateRoom($val, $b['gender'], $b['viewers']);
			$stat[$val] = $b['viewers'];
			sleep(1);
		}
	}
	arsort($stat);
	$main = [];
	$avg = 10;
	$d['rooms'] = count($stat);
	foreach($stat as $key => $val){
		$d['viewers'] += $val;
		if($val > $avg){			
			$main[$key] = $val;
		}
	}
	$result['list'] = $stat;
	$result['main'] = $main;
	$redis->setex(getCacheName('apiOnline'), 900, json_encode($d));
	$redis->setex(getCacheName('apiList'), 900, json_encode($result));
	return $result;
}

getOnlineList();
