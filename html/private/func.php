<?php

function getRoomStat($id, $table, $x){
	global $db;
	$query = $db->prepare("SELECT $x FROM `$table` WHERE `room` = :room AND `time` >= UNIX_TIMESTAMP(date_sub(now(), interval 30 day))");
	$query->bindParam(':room', $id, PDO::PARAM_STR);
	$query->execute();
	$row = $query->fetch();
	if(empty($row[$x]))
		$row[$x] = 0;
	return round($row[$x]);
}

function getStat(){
	global $db;
	$i = 0;
	$all = 0;
	$stat = '';
	$data = [];
	if($_SERVER['REMOTE_ADDR'] == '178.32.144.122'){
	$query = $db->query("SELECT * FROM `room`");
	$query->execute();
	while($row = $query->fetch()){
		@$data[$row['name']]['income'] = getRoomStat($row['id'], 'stat', 'SUM(amount)');
		@$data[$row['name']]['pid'] = $row['pid'];
		@$data[$row['name']]['id'] = $row['id'];
		@$data[$row['name']]['online'] = getRoomStat($row['id'], 'online', 'AVG(online)');
	}
	uasort($data, function($a, $b){
		return ($b['income'] - $a['income']);
	});
	$data = array_slice($data, 0, 100);
	$cache = json_encode($data);
	$update = $db->prepare("UPDATE `cache` SET `data` = :data WHERE `name` = 'stat'");
	$update->bindParam(':data', $cache, PDO::PARAM_STR);
	$update->execute();
	}else{
		$query = $db->query("SELECT * FROM `cache` WHERE `name` = 'stat'");
		$query->execute();
		$row = $query->fetch();
		$data = json_decode($row['data'], true);
	}
	foreach($data as $key => $val){
		$i++;
		$st = (!empty($val['pid'])) ? '<font color=green>Online</font>' : 'Offline';
		$val['income'] = round($val['income']*0.05); // One TK = 0.05 USD
		$all += $val['income'];
		$stat .= "<tr><td>$i</td><td><a href=\"https://chaturbate.com/in/?track=default&tour=dT8X&campaign=C0Jsr&room=".htmlspecialchars($key)."\">".htmlspecialchars($key)."</a></td><td>$st</td><td>{$val['online']}</td><td><a href=\"/stat.php?id={$val['id']}\">{$val['income']}</a></td></tr>";
	}
	return ['stat' => $stat, 'sum' => $all];
}

function getDonName($id){
	global $db;
	$query = $db->prepare("SELECT name FROM `donators` WHERE `id` = :id");
	$query->bindParam(':id', $id, PDO::PARAM_STR);
	$query->execute();
	$row = $query->fetch();
	return $row['name'];
}

function getDons($id){
	global $db;
	$i = 0;
	$stat = '';
	$data = [];
	$time = time()-60*60*24*30;
	$query = $db->prepare("SELECT * FROM `stat` WHERE `room` = :id AND `time` >= UNIX_TIMESTAMP(date_sub(now(), interval 30 day))");
	$query->bindParam(':id', $id, PDO::PARAM_STR);
	$query->execute();
	while($row = $query->fetch()){
		@$data[$row['user']] += $row['amount'];	
	}
	arsort($data);
	$data = array_slice($data, 0, 100, TRUE);
	foreach($data as $key => $val){
		$i++;
		$val = $val*0.05;
		if($val > 10){
			$val = round($val);
		}
		$stat .= "<tr><td>$i</td><td>".htmlspecialchars(getDonName($key))."</td><td>$val</td></tr>";
	}
	return $stat;
}

function getCharts($id){
	global $db; $data = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]; $arr = [];
	$year = date('Y', time());
	$month = date('n', time());
	for($i=1; $i<=$month; $i++){
		$arr[] = strtotime("$year-$i");
	}
	$arr[] = strtotime(($year+1).'-1');
	foreach($arr as $k => $v){
		if(empty($arr[$k+1])) continue;
		$query = $db->prepare("SELECT sum(amount) from `stat` WHERE `room` = :id AND `time` > $v AND `time` <  ".$arr[$k+1]);
		$query->bindParam(':id', $id, PDO::PARAM_STR);
		$query->execute();
		$row = $query->fetch();
		if(!empty($row[0])){
			$data[$k] = round($row[0]*0.05);
		}
	}
	return implode(",",$data);
}
