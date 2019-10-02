<?php

try {
	$db = new PDO("mysql:host=localhost;dbname=db", "user", "passwd");
	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$db->exec("set names utf8");
}
catch(PDOException $e) {
	die('MySQL ERROR'.PHP_EOL);
}

try {
	$sphinx = new PDO("mysql:host=127.0.0.1;port=9306;", '', '');
	$sphinx->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$sphinx->exec("set names utf8");
}
catch(PDOException $e) {
	die('sphinx ERROR'.PHP_EOL);
}

$redis = new Redis();
$redis->connect('/var/run/redis/redis-server.sock');

function getRoomInfo($name){
	global $db;
	$query = $db->prepare('SELECT * FROM `room` WHERE `name` = :name');
	$query->bindParam(':name', $name);
	$query->execute();
	if($query->rowCount() == 0){
		return false;	
	}
	return $query->fetch();
}

function createRoom($name){
	global $db;
	$query = $db->prepare('SELECT `id` FROM `room` WHERE `name` = :name');
	$query->bindParam(':name', $name);
	$query->execute();
	if($query->rowCount() == 0){
		$query = $db->prepare('INSERT INTO `room` (`name`) VALUES (:name)');
		$query->bindParam(':name', $name);
		$query->execute();
		return $db->lastInsertId();
	}
}

function getRoomServer($room, $id){
	global $db;
    $html = file_get_contents("https://chaturbate.com/$room/");
    preg_match('/chatws(.*)\.highwebmedia/', $html, $server);
    if(empty($server['1'])){
		return false;
	}
	$gender = ['male', 'female', 'trans', 'couple'];
	preg_match('/broadcaster_gender: \'(.*)\',/', $html, $sex);
	if(!empty($sex['1']) && in_array($sex['1'], $gender)){
		$gender = array_search($sex['1'], $gender);
		$query = $db->prepare("UPDATE `room` SET `gender` = :gender WHERE `id` = :id");
		$query->bindParam(':id', $id);
		$query->bindParam(':gender', $gender);
		$query->execute();
	}
	return $server['1'];
}


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

function getChartData($start){
	global $sphinx;
	$end = date('Ymd', strtotime("+1 day", strtotime($start)));	
	$start = strtotime($start);
	$end = strtotime($end);
	$query = $sphinx->query("SELECT YEARMONTHDAY(time) as ndate, SUM(token) as total FROM stat WHERE `time` > $start AND `time` < $end GROUP BY ndate ORDER BY ndate DESC");
	if($query->rowCount() == 0){
		return false;
	}
	$row = $query->fetch();
	return [$row['ndate'], $row['total']];
}

function updateCache(){
	if($_SERVER['REMOTE_ADDR'] == '159.69.67.28'){
		return true;
	}
	return false;
}

function getCharts(){
	global $db, $sphinx, $redis;

	$stat = $redis->get('mainGraph');
	if($stat !== false || updateCache()){
		return $stat;
	}

	$k = [];
	$arr = [];
	$cur = strtotime(date('Y-m-d', time()));
	
	$query = $db->query("SELECT * FROM `cache`");
	while($row = $query->fetch()){
		$arr[$row['date']] = $row['amount'];
	}
	
	$begin = '20190829';
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

	foreach($arr as $key => $val){
		$k[] = [strtotime($key).'000', round($val*0.05)];
	}
		
	usort($arr, function ($a, $b) {
		return $a['0'] <=> $b['0'];
	});
	
	$stat = json_encode($k, JSON_NUMERIC_CHECK);
	$redis->setex('mainGraph', 360, $stat);
	return $stat;
}

function format_interval(DateInterval $interval) {
    if ($interval->y) { return $interval->format("%y years "); }
    if ($interval->m) { return $interval->format("%m months "); }
    if ($interval->d) { return $interval->format("%d days "); }
    if ($interval->h) { return $interval->format("%h hours "); }
    if ($interval->i) { return $interval->format("%i minutes "); }
    if ($interval->s) { return $interval->format("%s seconds "); }
}

function getTopDons(){
	global $db, $sphinx, $redis;
	$result = '';
	$stat = $redis->get('topDons');
	if($stat !== false || updateCache()){
		return $stat;
	}
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $sphinx->prepare("SELECT did, SUM(token) as total, AVG(token) as avg FROM stat WHERE time > $date GROUP BY did ORDER BY total DESC LIMIT 20");
	$query->execute();
	$row =  $query->fetchAll();
	foreach($row as $val) {
		$select = $db->prepare('SELECT `name` FROM `donator` WHERE `id` = :id');
		$select->bindParam(':id', $val['did']);
		$select->execute();
		$info = $select->fetch();
		$arr[$info['name']] = toUSD($val['total']);
		$result .= "<tr>
			<td><a href='https://chaturbate.com/{$info['name']}/' target='_blank'>{$info['name']}</a></td>
			<td>".toUSD($val['total'])."</td>
			<td>".toUSD($val['avg'])."</td>
		</tr>";
	}
	$redis->setex('topDons', 360, $result);
	return $result;
}

function getStat(){
	global $db, $sphinx, $redis;
	
	$stat = $redis->get('topStat');
	if($stat !== false || updateCache()){
		return $stat;
	}
	
	$data = [];
	
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $sphinx->prepare("SELECT rid, SUM(token) as total FROM stat WHERE time > $date GROUP BY rid ORDER BY total DESC LIMIT 100");
	$query->execute();
	$row =  $query->fetchAll();
		
	foreach($row as $val) {
		$select = $db->prepare('SELECT `name`, `gender`, `last` FROM `room` WHERE `id` = :rid');
		$select->bindParam(':rid', $val['rid']);
		$select->execute();
		
		$room = $select->fetch();
		
		$data[$room['name']]['last'] = $room['last'];
		$data[$room['name']]['gender'] = $room['gender'];
		$data[$room['name']]['token'] = $val['total'];
		
		$select = $db->prepare("SELECT AVG(`online`) FROM `online` WHERE `rid` = :rid AND `time` > $date");
		$select->bindParam(':rid', $val['rid']);
		$select->execute();
		
		$data[$room['name']]['online'] = round($select->fetch()['0']);
	}
	
	$i = 0; $all = 0; $stat = '';
	$gender = ['boy', 'girl', 'trans', 'couple'];
	foreach($data as $key => $val){
		$i++;

		$val['token'] = toUSD($val['token']);
		$all += $val['token'];

		if($val['last']+60*10 > time()){
			$val['last'] = '<font color=green>online</font>';
		}else{
			$first_date = new DateTime(date('Y-m-d H:m:s', $val['last']));
			$second_date = new DateTime(date('Y-m-d H:m:s', time()));
			$difference = $first_date->diff($second_date);
			$val['last'] = format_interval($difference);
		}
		
		$stat .= "
			<tr>
				<td>$i</td>
				<td><a href=\"https://chaturbate.com/".htmlspecialchars($key)."\" target=\"_blank\">".htmlspecialchars($key)."</a> $st</td>
				<td>{$gender[$val['gender']]}</td>
				<td>{$val['last']}</td>
				<td>{$val['online']}</td>
				<td>{$val['token']}</td>
			</tr>
		";
	}
	
	$redis->setex('topStat', 360, $stat);
	
	return $stat;
}

function toUSD($v, $k = 0){
	return round($v*0.05, $k); // One TK = 0.05 USD
}

function getFinStat(){
	global $sphinx, $redis;
	$stat = $redis->get('topIncome');
	if($stat !== false || updateCache()){
		$stat = json_decode($stat, true);
		return ['total' => toUSD($stat['0']), 'avg' => toUSD($stat['1'], 2)];	
	}
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $sphinx->prepare("SELECT SUM(token), AVG(token) FROM stat WHERE time > $date");
	$query->execute();
	$row =  $query->fetch();
	
	$redis->setex('topIncome', 360, json_encode($row));	
	return ['total' => toUSD($row['0']), 'avg' => toUSD($row['1'], 2)];
}
