<?php

function getFinStat(){ // cache done
	global $clickhouse;
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $clickhouse->query("SELECT SUM(token), AVG(token), count(DISTINCT rid) FROM stat WHERE time > $date");
	$row =  $query->fetch();
	return ['total' => toUSD($row['0']), 'avg' => toUSD($row['1'], 2), 'count' => $row['2']];
}

function getModelChartData($a){ // cache done
	global $clickhouse; $arr = [];
	$start = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $clickhouse->query("SELECT FROM_UNIXTIME(time, '%Y-%m-%d') as ndate, SUM(token) as total FROM stat WHERE `rid` = {$a['rid']} AND `time` > $start GROUP BY ndate ORDER BY ndate DESC");
	if($query->rowCount() == 0){
		return false;
	}
	while($row = $query->fetch()){
		$arr[] = ['date' => $row['ndate'], 'value' => toUSD($row['total'])];
		$last = $row['total'];
	}
	return json_encode($arr, JSON_NUMERIC_CHECK);
}

function getTopDons($room){ // cache done
	global $clickhouse;
	$result = ''; $a = ''; $b = '';
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$tmpl = '<tr><td>{URL}</td><td>{TOTAL}</td><td>{AVG}</td></tr>';
	if(!empty($room['rid'])){
		$a = "rid = {$room['rid']} AND time > $date"; 
	}else{
		$a = "time > $date";
		$b = "HAVING avg < 2000"; // 100 USD
	}
	$query = $clickhouse->query("SELECT did, SUM(token) as total, AVG(token) as avg FROM stat WHERE $a GROUP BY did $b ORDER BY total DESC LIMIT 20");
	$row =  $query->fetchAll();
	foreach($row as $val) {
		$tr = str_replace('{URL}', createUrl(cacheResult('getDonName', ['id' => $val['did']], 86000)), $tmpl);
		$tr = str_replace('{TOTAL}', toUSD($val['total']), $tr);
		$tr = str_replace('{AVG}', toUSD($val['avg'], 2), $tr);
		$result .= $tr;
	}	
	return $result;
}

function getAllIncome($arr){ // cache done
	global $clickhouse;
	$query = $clickhouse->query("SELECT SUM(token) as total FROM stat WHERE rid = {$arr['rid']}");
	return toUSD($query->fetch()['0']);
}

function getDonName($arr){ // cache done
	global $db;
	$select = $db->prepare('SELECT `name` FROM `donator` WHERE `id` = :id');
	$select->bindParam(':id', $arr['id']);
	$select->execute();
	return $select->fetch()['name'];
}

function getRoomInfo($arr){ // cache done
	global $db;
	$query = $db->prepare('SELECT * FROM `room` WHERE `name` = :name');
	$query->bindParam(':name', $arr['name']);
	$query->execute();
	if($query->rowCount() == 1){
		return $query->fetch();
	}
	$query = $db->prepare('INSERT INTO `room` (`name`) VALUES (:name)');
	$query->bindParam(':name', $arr['name']);
	$query->execute();
	$id = $db->lastInsertId();
	return ['id' => $id, 'name' => $arr['name'], 'gender' => 0, 'last' => 0];		
}

function getTop(){ // cache done
	global $db, $clickhouse;
	$data = [];
	$date = strtotime(date('d-m-Y', time()).' -1 months'); 
	$query = $clickhouse->query("SELECT rid, SUM(token) as total FROM stat WHERE time > $date AND did not in (SELECT did FROM stat WHERE time > $date GROUP BY did HAVING AVG(token) > 20000) GROUP BY rid HAVING AVG(token) < 1000 ORDER BY total DESC LIMIT 100");
	$row =  $query->fetchAll();
	foreach($row as $val) {
		$data[$val['rid']]['token'] = $val['total'];
	}		
	$in = implode(', ', array_column($row, 'rid'));
	$query = $db->query("SELECT `id`, `name`, `gender`, `fans`, `last` FROM `room` WHERE `id` IN ($in)");
	while($row = $query->fetch()){
		$data[$row['id']]['name'] = $row['name'];
		$data[$row['id']]['last'] = $row['last'];
		$data[$row['id']]['gender'] = $row['gender'];
		$data[$row['id']]['fans'] = round($row['fans']/1000);
	}
	return $data;
}

function prepareTable(){ // cache done
	$i = 0; $stat = ''; $list = [];
	$gender = ['boy', 'girl', 'trans', 'couple'];
	$data = cacheResult('getTop', [], 600, true);
	$online = json_decode(cacheResult('getList', [], 60), true);
	$tmpl = '<tr><td>{ID}</td><td>{URL}</td><td>{GENDER}</td><td>{LAST}</td><td>{FANS}</td><td>{USD}</td></tr>';
	foreach($data as $key => $val){
		$i++;
		$list[] = $val['name'];
		$val['token'] = toUSD($val['token']);
		$val['last'] = get_time_ago($val['last']);
		if(array_key_exists($val['name'], $online)){
			$val['last'] = '<font color="green">online</font>';
		}
		$tr = str_replace('{ID}', $i, $tmpl);
		$tr = str_replace('{URL}', createUrl($val['name']), $tr);
		$tr = str_replace('{GENDER}', $gender[$val['gender']], $tr);
		$tr = str_replace('{LAST}', $val['last'], $tr);
		$tr = str_replace('{FANS}', $val['fans'], $tr);
		$tr = str_replace('{USD}', "<a href=\"#\" data-show-room-stat=$key data-room-name=".strip_tags($val['name']).">{$val['token']}</a>", $tr);
		$stat .= $tr;
	}
	return $stat;
}

// TODO add cache //

function genderIncome($start){
	global $clickhouse;
	$arr = [];
	$result = [0, 0, 0, 0];
	$end = strtotime("+1 day", strtotime($start));
	$start = strtotime($start);
	$query = $clickhouse->query("SELECT room.gender, SUM(token) as total FROM `stat` LEFT JOIN `room` ON stat.rid = room.id WHERE `time` BETWEEN $start AND $end GROUP by `gender`");
	while($row = $query->fetch()){
		$result[$row['gender']] += $row['total'];
	}
	ksort($result);
	return $result;
}

function getChartData($start){	
	global $clickhouse;
	$end = date('Ymd', strtotime("+1 day", strtotime($start)));	
	$start = strtotime($start);
	$end = strtotime($end);
	$query = $clickhouse->query("SELECT FROM_UNIXTIME(time, '%Y%m%d') as ndate, SUM(token) as total FROM stat WHERE `time` > $start AND `time` < $end GROUP BY ndate ORDER BY ndate DESC");
	if($query->rowCount() == 0){
		return false;
	}
	$row = $query->fetch();
	return [$row['ndate'], $row['total']];
}

function getCharts(){
	global $db, $clickhouse, $redis;
	
	$cname = getCacheName('mainGraph');
	$stat = getCache($cname);
	if($stat !== false){
		return $stat;
	}
	
	$k = [];
	$arr = [];
	$cur = strtotime(date('Y-m-d', time()));
	
	$query = $db->query("SELECT * FROM `cache`");
	while($row = $query->fetch()){
		$arr[$row['date']] = $row['amount'];
	}
	
	$begin = '20211125';
	$end   = date('Ymd', time());

	while(1){
		if(!array_key_exists($begin, $arr)){
			$ok = getChartData($begin);
			if($ok){
				$arr[$ok['0']] = $ok['1'];
				if($begin != $end){
					saveCharts($ok['0'], $ok['1']);
				}
			}
		}
		if($begin == $end){
			break;
		}
		$begin = date('Ymd', strtotime("+1 day", strtotime($begin)));
	}

	$last = 0;
	$bl = ['2021-11-23', '2021-11-24'];
	foreach($arr as $key => $val){
		$d = date('Y-m-d', strtotime($key));
		if($end != $key){
			if($last/2 > $val || in_array($d, $bl)){
				continue;
			}
		}
		$k[] = ['date' => $d, 'value' => round($val*0.05)];
		$last = $val;
	}
		
	usort($arr, function ($a, $b) {
		return $a['0'] <=> $b['0'];
	});
	
	$arr = getChartsLines();
	$arr[] = $k;
	
	foreach($arr['2'] as $key => $val){
		$dates[] = $val["date"];
	}

	foreach($arr as $key => $val){
		if($key != 2){
			foreach($val as $k => $v){
				if(!in_array($v["date"], $dates)){
					unset($arr["$key"]["$k"]);
				}
			}
		}
	}

	$arr[0] = array_values($arr[0]);
	$arr[1] = array_values($arr[1]);
		
	$stat = json_encode($arr, JSON_NUMERIC_CHECK);
	$redis->setex($cname, 600, $stat);
	return $stat;
}

// No need cache //

function saveCharts($date, $amount){
	global $db;
	$select = $db->prepare("SELECT `amount` FROM `cache` WHERE `date` = :date");
	$select->bindParam(':date', $date);
	$select->execute();
	if($select->rowCount() == 0){
		$query = $db->prepare("INSERT INTO `cache` (`date`, `amount`) VALUES (:date, :amount)");
		$query->bindParam(':date', $date);
		$query->bindParam(':amount', $amount);
		$query->execute();
	}
}

function genderIncomeSave($date, $arr){
	global $db;
	$json = json_encode($arr);
	$select = $db->prepare("SELECT `amount` FROM `cache` WHERE `date` = :date");
	$select->bindParam(':date', $date);
	$select->execute();
	if($select->rowCount() == 1){
		$query = $db->prepare("UPDATE `cache` SET `info` = :json WHERE `date` = :date");
		$query->bindParam(':json', $json);
		$query->bindParam(':date', $date);
		$query->execute();
	}	
}

function getChartsLines(){
	global $db;
	$result = [];
	$today = date('Ymd', time());
	$query = $db->query("SELECT * FROM `cache`");
	while($row = $query->fetch()){
		if(empty($row['info'])){
			$a = genderIncome($row['date']);
			genderIncomeSave($row['date'], $a);
			$arr[$row['date']] = sumOther($a);
		}else{
			$arr[$row['date']] = sumOther(json_decode($row['info'], true));	
		}
	}
	$arr[$today] = sumOther(genderIncome($today));
	foreach($arr as $k => $v){
		foreach($v as $key => $val){
			$result[$key][] = ['date' => date('Y-m-d', strtotime($k)), 'value' => round($val*0.05)];
		}
	}
	return $result;
}

function getPieStat(){
	global $db; $x = []; $arr = [0, 0, 0, 0];
	$names = ['Boys', 'Girls', 'Trans', 'Couple'];
	$date = date('Ymd', strtotime("-1 month", time()));
	$query = $db->query("SELECT * FROM `cache` WHERE `date` >= $date");
	while($row = $query->fetch()){
		$a = json_decode($row['info']);
		foreach($a as $k => $v){
			$arr[$k] += $v;
		}
	}
	foreach($arr as $k => $v){
		$x[] = ['name' => $names[$k], 'y' => $v];
	}
	return json_encode($x);
}
