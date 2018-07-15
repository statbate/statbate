<?php
function query($sql, $params = null){
	global $db;
	$query = $db->prepare($sql);
	if(is_array($params)){
		foreach($params as $key => &$val){
			$query->bindParam($key, $val);
		}
	}
	$query->execute();
	if(mb_strtolower(substr($sql, 0, 6 )) === "select"){
		return ['row' => $query->fetch(), 'count' => $query->rowCount(), 'all' => $query->fetchAll()];
	}
	return 1;
}

function getRoomStat($id, $table, $x){
	$result = query("SELECT $x FROM `$table` WHERE `room` = :room AND `time` >= UNIX_TIMESTAMP(date_sub(now(), interval 30 day))", ['room' => $id]);
	if(empty($result['row']["$x"]))
		$result['row']["$x"] = 0;
	return round($result['row']["$x"]);
}

function getStat(){
	global $db;
	$i = 0;
	$all = 0;
	$stat = '';
	$data = [];
	if($_SERVER['REMOTE_ADDR'] == '178.32.144.122'){
	$result = query("SELECT * FROM `room`");
	foreach($result['all'] as $row){
		@$data[$row['name']]['income'] = getRoomStat($row['id'], 'stat', 'SUM(amount)');
		@$data[$row['name']]['pid'] = $row['pid'];
		@$data[$row['name']]['id'] = $row['id'];
		@$data[$row['name']]['online'] = getRoomStat($row['id'], 'online', 'AVG(online)');
	}
	uasort($data, function($a, $b){
		return ($b['income'] - $a['income']);
	});
	$data = array_slice($data, 0, 100);
	query("UPDATE `cache` SET `data` = :data WHERE `name` = 'stat'", ['data' => json_encode($data)]);
	}else{
		$result = query("SELECT * FROM `cache` WHERE `name` = 'stat'");
		$data = json_decode($result['row']['data'], true);
	}
	foreach($data as $key => $val){
		$i++;
		$st = (!empty($val['pid'])) ? '<font color=green>Online</font>' : 'Offline';
		$val['income'] = round($val['income']*0.05); // One TK = 0.05 USD
		$all += $val['income'];
		$stat .= "<tr><td>$i</td><td><a href=\"https://chaturbate.com/".htmlspecialchars($key)."\" target=\"_blank\">".htmlspecialchars($key)."</a></td><td>$st</td><td>{$val['online']}</td><td><a href=\"/stat.php?id={$val['id']}\">{$val['income']}</a></td></tr>";
	}
	return ['stat' => $stat, 'sum' => $all];
}

function getDonName($id){
	$result = query("SELECT name FROM `donators` WHERE `id` = :id", ['id' => $id]);
	return $result['row']['name'];
}

function getDons($id){
	$i = 0;
	$stat = '';
	$data = [];
	$time = time()-60*60*24*30;
	$result = query("SELECT * FROM `stat` WHERE `room` = :id AND `time` >= UNIX_TIMESTAMP(date_sub(now(), interval 30 day))", ['id' => $id]);
	foreach($result['all'] as $row ){
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

function getCharts($id = null){
	$arr = [];
	$year = date('Y', time());
	$month = date('n', time());
	$data = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
	for($i=1; $i<=$month; $i++){
		$arr[] = strtotime("$year-$i");
	}
	$arr[] = strtotime(($year+1).'-1');
	if(!empty($id)){
		$data = modelCharts($id, $arr, $data);
	}else{
		$data = allCharts($arr, $data);
	}
	return implode(",",$data);
}

function modelCharts($id, $arr, $data){
	foreach($arr as $k => $v){
		if(empty($arr[$k+1])) continue;
		$result = query("SELECT sum(amount) from `stat` WHERE `room` = :id AND `time` > $v AND `time` < ".$arr[$k+1], ['id' => $id]);
		if(!empty($result['row'][0])){
			$data[$k] = round($result['row'][0]*0.05);
		}
	}
	return $data;
}

function allCharts($arr, $data){
	if($_SERVER['REMOTE_ADDR'] == '178.32.144.122'){
		foreach($arr as $k => $v){
			if(empty($arr[$k+1])) continue;
			$result = query("SELECT sum(amount) from `stat` WHERE  `time` > $v AND `time` < ".$arr[$k+1]);
			if(!empty($result['row'][0])){
				$data[$k] = round($result['row'][0]*0.05);
			}
		}
		query("UPDATE `cache` SET `data` = :data WHERE `name` = 'all'", ['data' => json_encode($data)]);
	}else{
		$result = query("SELECT * FROM `cache` WHERE `name` = 'all'");
		$data = json_decode($result['row']['data'], true);
	}
	return $data;
}
