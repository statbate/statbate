<?php
if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])){
	die;
}

try {
	$db = new PDO("mysql:host=localhost;dbname=db", "user", "passwd");
	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$db->exec("set names binary");
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

function getCacheName($val){
	return md5($val);
}

function createUrl($name){
	$name = strip_tags($name);
	return "<a href='/public/move.php?room={$name}' target='_blank' rel='nofollow'>{$name}</a>";
}

function toUSD($v){
	$x = $v*0.05; // One TK = 0.05 USD
	if($x < 10){
		return round($x, 2);
	}
	return round($x); 
}

function cleanData(){
	global $db;
	if($_SERVER['REMOTE_ADDR'] == '127.0.0.1' && random_int(1, 100) == 10){
		$db->query('DELETE FROM `online` WHERE `time` < UNIX_TIMESTAMP(DATE(NOW() - INTERVAL 1 MONTH))');
	}
}

function updateCache($key){
	global $redis;
	if((php_sapi_name() == "cli" || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') && $redis->ttl($key) < 90){
		return true;
	}
	return false;
}

function getCache($key){
	global $redis;
	$stat = $redis->get($key);
	if($stat === false){
		return false;
	}
	if(updateCache($key)){
		return false;
	}
	return $stat;
}

function getRoomInfo($name){
	global $db;
	$query = $db->prepare('SELECT * FROM `room` WHERE `name` = :name');
	$query->bindParam(':name', $name);
	$query->execute();
	if($query->rowCount() > 0){
		return $query->fetch();
	}
	$query = $db->prepare('INSERT INTO `room` (`name`) VALUES (:name)');
	$query->bindParam(':name', $name);
	$query->execute();
	$id = $db->lastInsertId();
	return ['id' => $id, 'name' => $name, 'gender' => 0, 'last' => 0];		
}

function getList(){
	global $redis;
	$cname = getCacheName('onlineList');
	$stat = getCache($cname);
	if($stat !== false){
		return $stat;
	}
	if(!$stat = file_get_contents('https://chaturbate100.com/list/')){
		return false;
	}
	$redis->setex($cname, 600, $stat);
	return $stat;
}

function trackCount(){
	if(!$list = getList()){
		return 0;
	}
	return count(json_decode($list, true));
}

function showRoomList(){
	if(isset($_GET['list'])){
		echo "<pre><a href='/'>main page</a>\n\n";
		$arr = json_decode(getList(), true);
		ksort($arr);
		echo "track ".count($arr)." rooms<br/><br/>";
		foreach($arr as $key => $val){
			echo $key."<br/>";
		}
		die;
	}
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

function format_interval(DateInterval $interval) {
    if ($interval->y) { return $interval->format("%y years "); }
    if ($interval->m) { return $interval->format("%m months "); }
    if ($interval->d) { return $interval->format("%d days "); }
    if ($interval->h) { return $interval->format("%h hours "); }
    if ($interval->i) { return $interval->format("%i minutes "); }
    if ($interval->s) { return $interval->format("%s seconds "); }
}

function telegram_send($msg){
	$arr = [
		'chat_id' => '@chaturbate100',
		'text' => $msg
	];
	file_get_contents("https://api.telegram.org/botX/sendMessage?".http_build_query($arr));
}

function getDonName($id){
	global $db;
	$select = $db->prepare('SELECT `name` FROM `donator` WHERE `id` = :id');
	$select->bindParam(':id', $id);
	$select->execute();
	return $select->fetch()['name'];
}

function getModelChartData($id){
	global $db; $arr = [];
	$start = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $db->query("SELECT FROM_UNIXTIME(time, '%Y-%m-%d') as ndate, SUM(token) as total FROM stat WHERE `rid` = $id AND `time` > $start GROUP BY ndate ORDER BY ndate DESC");
	if($query->rowCount() == 0){
		return false;
	}
	while($row = $query->fetch()){
		$arr[] = ['date' => $row['ndate'], 'value' => toUSD($row['total'])];
		$last = $row['total'];
	}
	return json_encode($arr, JSON_NUMERIC_CHECK);
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


function getCharts(){
	global $db, $sphinx, $redis;
	
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

	$last = 0;
	$bl = ['2020-01-09'];
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
	
	$stat = json_encode($k, JSON_NUMERIC_CHECK);
	$redis->setex($cname, 600, $stat);
	return $stat;
}

function getTopDons($room = ''){
	global $db, $sphinx, $redis;
	$cname = getCacheName('topDons'.$room);
	$stat = getCache($cname);
	if($stat !== false){
		return $stat;
	}
	$result = ''; $a = ''; $b = '';
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$tmpl = '<tr><td>{URL}</td><td>{TOTAL}</td><td>{AVG}</td></tr>';
	if(!empty($room)){
		$handler = $db;
		$a = "rid = $room AND time > $date"; 
	}else{
		$handler = $sphinx;
		$a = "time > $date";
		$b = "HAVING avg < 2000"; 
	}
	$query = $handler->query("SELECT did, SUM(token) as total, AVG(token) as avg FROM stat WHERE $a GROUP BY did $b ORDER BY total DESC LIMIT 20");
	$row =  $query->fetchAll();
	foreach($row as $val) {		
		$tr = str_replace('{URL}', createUrl(getDonName($val['did'])), $tmpl);
		$tr = str_replace('{TOTAL}', toUSD($val['total']), $tr);
		$tr = str_replace('{AVG}', toUSD($val['avg'], 2), $tr);
		$result .= $tr;
	}
	$redis->setex($cname, 600, $result);
	return $result;
}

function getAllIncome($id){
	global $db;
	$query = $db->query("SELECT SUM(token) as total FROM stat WHERE rid = $id");
	return toUSD($query->fetch()['0']);
}

function getStat(){
	global $db, $sphinx, $redis;
	
	$cname = getCacheName('topStat');
	$stat = getCache($cname);
	if($stat !== false && getCache(getCacheName('top100list')) !== false){
		return $stat;
	}
	
	$data = [];
	
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $sphinx->prepare("SELECT rid, SUM(token) as total, MAX(token) as max FROM stat WHERE time > $date GROUP BY rid HAVING max < 20000 ORDER BY total DESC LIMIT 100");
	$query->execute();
	$row =  $query->fetchAll();
		
	foreach($row as $val) {
		$select = $db->prepare('SELECT `name`, `gender`, `last` FROM `room` WHERE `id` = :rid');
		$select->bindParam(':rid', $val['rid']);
		$select->execute();
		
		$room = $select->fetch();
		
		$data[$room['name']]['id'] = $val['rid'];
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
	$tmpl = '<tr><td>{ID}</td><td>{URL}</td><td>{GENDER}</td><td>{LAST}</td><td>{ONLINE}</td><td>{USD}</td></tr>';
	$list = [];
	$apiList = json_decode(getCache(getCacheName('apiList')), true);
	foreach($data as $key => $val){
		$i++;
		$val['token'] = toUSD($val['token']);
		$all += $val['token'];
		
		if(array_key_exists($key, $apiList['list'])){
			$val['last'] = '<font color="green">online</font>';
			$list[] = $key;
		}else{
			$first_date = new DateTime(date('Y-m-d H:m:s', $val['last']));
			$second_date = new DateTime(date('Y-m-d H:m:s', time()));
			$difference = $first_date->diff($second_date);
			$val['last'] = format_interval($difference);
		}
		$tr = str_replace('{ID}', $i, $tmpl);
		$tr = str_replace('{URL}', createUrl($key), $tr);
		$tr = str_replace('{GENDER}', $gender[$val['gender']], $tr);
		$tr = str_replace('{LAST}', $val['last'], $tr);
		$tr = str_replace('{ONLINE}', $val['online'], $tr);
		$tr = str_replace('{USD}', "<a href=\"#\" data-show-room-stat={$val['id']} data-room-name=".strip_tags($key).">{$val['token']}</a>", $tr);
		$stat .= $tr;
	}
	$redis->setex(getCacheName('top100list'), 1800, json_encode($list));
	$redis->setex($cname, 600, $stat);	
	return $stat;
}

function getFinStat(){
	global $sphinx, $redis;
	$cname = getCacheName('topIncome');
	$stat = getCache($cname);
	if($stat !== false){
		$stat = json_decode($stat, true);
		return ['total' => toUSD($stat['0']), 'avg' => toUSD($stat['1']), 'count' => $stat['2']];	
	}
	$date = strtotime(date('d-m-Y', time()).' -1 months');
	$query = $sphinx->prepare("SELECT SUM(token), AVG(token), count(DISTINCT rid) FROM stat WHERE time > $date");
	$query->execute();
	$row =  $query->fetch();
	
	$redis->setex($cname, 600, json_encode($row));	
	return ['total' => toUSD($row['0']), 'avg' => toUSD($row['1'], 2), 'count' => $row['2']];
}

function sendHistory($all = false){
	global $sphinx, $db; 
	$sql = '';
	$msg = "💰 All time income 💰\n\n";
	
	if(!$all){
		$time = strtotime(date('Y-m', time()));
		$start = strtotime(date('d-m-Y', $time).' -1 months');
		$end = strtotime(date('d-m-Y', $start).' +1 months');
		$sql = "WHERE time >= $start AND time <= $end";
		$msg = "💰 ".date('F Y', $start)." 💰\n\n";
	}

	$tmpl = "$%income%\t\t-\t\t%name%\n";
	$query = $sphinx->prepare("SELECT rid, SUM(token) as total, MAX(token) as max FROM stat $sql GROUP BY rid HAVING max < 20000 ORDER BY total DESC LIMIT 10");
	$query->execute();
	$row =  $query->fetchAll();
	foreach($row as $val) {
		$select = $db->prepare('SELECT `name` FROM `room` WHERE `id` = :rid');
		$select->bindParam(':rid', $val['rid']);
		$select->execute();
		$name = $select->fetch()['name'];
		$result = str_replace('%name%', $name, $tmpl);
		$msg .= str_replace('%income%', toUSD($val['total']), $result);
	}
	telegram_send($msg);
}
