<?php
if(php_sapi_name() != "cli"){
	die;
}
require_once('/var/www/chaturbate100.com/func.php');

$query = $db->query("select min(id) as min, max(id) as max from `stat`");
if($query->rowCount() == 0){
	die("no data\n");
}
$row = $query->fetch();

$min = $row['min'];
$max = $row['max'];

$query = $sphinx->query("SELECT max(id) as max FROM stat");
if($query->rowCount() != 0){
	$min = $query->fetch()['max']+1;
}

$step = 2048;

for($i = $min; $i < $max; $i+=$step){
	$sql = '';
	$start = $i;
	$end = $start+$step;
	$query = $db->query("SELECT * FROM `stat` WHERE `id` >= $start AND `id` < $end");
	if($query->rowCount() == 0){
		continue;
	}
	while($row = $query->fetch()){
		$sql .= "({$row['id']}, '', {$row['did']}, {$row['rid']}, {$row['token']}, {$row['time']}),";
	}
	$sql = rtrim($sql, ',');
	$insert = $sphinx->prepare("INSERT INTO stat VALUES $sql");	
	$insert->execute();
}
