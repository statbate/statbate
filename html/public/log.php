<?php
require_once('/var/www/statbate/root/private/init.php');

function getLog($name){
	global $db;
	$query = $db->prepare("SELECT `id` FROM `room` WHERE name = :name");
	$query->bindParam(':name', $name);
	$query->execute();
	if($query->rowCount() == 0){
		die('Empty log');
	}
	$id = $query->fetch()['id'];
	$query = $db->prepare("SELECT * FROM `logs` WHERE `rid` = :id ORDER BY `id` DESC");
	$query->bindParam(':id', $id);
	$query->execute();
	if($query->rowCount() == 0){
		die('Empty log');	
	}
	echo "<title>$name</title><style>body {background-color: #eeeeee;}table, th, td {border: 1px solid black;border-collapse: collapse;} td {min-width: 100px; height: 25px; text-align: center; vertical-align: middle;}</style><pre>\n\n";
	while($row = $query->fetch()){
		echo "[".date('H:i:s', $row['time'])."] {$row['mes']}\n";
	}
}

if(empty($_GET['name'])){
	die('empty name');
}

getLog($_GET['name']);
