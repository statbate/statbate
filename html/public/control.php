<?php
require_once('/var/www/statbate/root/private/init.php');

function updateFollowers($id, $num){
	global $db;
	$query = $db->prepare("UPDATE `room` SET `fans` = :fans WHERE `id` = :id");
	$query->bindParam(':id', $id);
	$query->bindParam(':fans', $num);
	$query->execute();
	//echo "update $id fans $num<br/>";
}

function updateGender($id, $v){
	global $db;
	$arr = ['m' => 0, 'f' => 1, 's' => 2, 'c' => 3];
	if(!array_key_exists($v, $arr)){
		return;
	}
	$query = $db->prepare("UPDATE `room` SET `gender` = :gender WHERE `id` = :id");
	$query->bindParam(':id', $id);
	$query->bindParam(':gender', $arr[$v]);
	$query->execute();
	//echo "update $id gender {$arr[$v]}<br/>";
}

if(empty($_GET['key']) || $_GET['key'] != 's'){
	die;
}

if(empty($_GET['username']) || empty($_GET['gender']) || empty($_GET['fans'])){
	die;
}

$info = cacheResult('getRoomInfo', ['name' => $_GET['username']], 3600, true);

updateGender($info['id'], $_GET['gender']);
updateFollowers($info['id'], $_GET['fans']);
