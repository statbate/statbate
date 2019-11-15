<?php
if(php_sapi_name() != "cli"){
	die;
}
require_once('/var/www/chaturbate100.com/func.php');
$query = $db->query("select min(id), max(id) from `stat`");
$x = $query->fetch();
for($i = $x['0']; $i < $x['1']; $i++){
	$query = $db->query("SELECT * FROM `stat` WHERE `id` = $i");
	if($query->rowCount() == 0){
		continue;
	}
	$row = $query->fetch();
	$query = $sphinx->prepare("INSERT INTO stat VALUES (:id, '', :did, :rid, :token, :time)");
	$query->bindParam(':id', $row['id']);
	$query->bindParam(':did', $row['did']);
	$query->bindParam(':rid', $row['rid']);
	$query->bindParam(':token', $row['token']);
	$query->bindParam(':time', $row['time']);
	$query->execute();
}
