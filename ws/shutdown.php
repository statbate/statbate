<?php

try {
	$db = new PDO("mysql:host=localhost;dbname=cam", "cam", "YqUG3M8No0gXBjcg");
	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$db->exec("set names utf8");
}
catch(PDOException $e) {
	die('MySQL ERROR');
}

function roomStatus($room, $x = 'passwd'){
	$html = file_get_contents("https://chaturbate.com/$room/");
	preg_match('/chatws(.*)\.highwebmedia/', $html, $id);
	if(empty($id[1])) return false;
	return true;
}

$query = $db->query("SELECT * FROM `room` WHERE `pid` IS NOT NULL");
$query->execute();
while($row = $query->fetch()){
	echo $row['name'].PHP_EOL;
	if(!roomStatus($row['name'])){
		//if(file_exists('/proc/'.$row['pid'])){
		//	echo "Room {$row['name']}, PID {$row['pid']}\n";
		//	shell_exec(escapeshellcmd("kill -9 ".$row['pid']));
		//}
		$update = $db->prepare("UPDATE `room` SET `pid` = :null WHERE `id` = :id");
		$update->bindValue(':null', null, PDO::PARAM_INT);
		$update->bindParam(':id', $row['id'], PDO::PARAM_STR);
		$update->execute();
	}
}
