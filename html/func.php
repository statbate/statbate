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

function showRedirectStat(){
	if(isset($_GET['rstat'])){
		echo "<title>Buy chaturbate referral traffic</title>";
		echo "<meta http-equiv='refresh' content='60'>";
		echo "<pre>";
		echo "<a href='/'>main page</a>\n\n";
		echo "Chaturbate has banned affiliate account (<a href='https://chaturbate100.com/f/revshare_transactions.csv' target='_blank' style='color: #472000;'>rev-share</a>)\n\n";
		echo "At your own risk, you can buy this traffic.\nPrice per month 0.01 BTC, chaturbate100@protonmail.com\n\n\n";
		echo "> How does it work?\n\n";
		echo "All links that lead to chaturbate.com are referral.\n\n";
		echo "For example, a link to room wetdream111\n";
		echo "chaturbate100.com/public/move.php?room=wetdream111\n\n";
		echo "redirect to\n";
		echo "chaturbate.com/in/?track=default&<u>tour=dT8X</u>&<u>campaign=CODE</u>&room=wetdream111\n\n";
		echo "where\n";
		echo "tour - affiliate program choice\n";
		echo "dT8X - constant is Revshare: 20% of Money Spent + $50 per broadcaster + 5% Referred Affiliate Income\n";
		echo "ZQAI - constant is $1.00 Pay Per Registration + $50.00 Per Broadcaster + 5% Referred Affiliate Income\n";
		echo "campaign - your affiliate code\n\n";
		echo "chaturbate.com/affiliates/linkcodes/\n\n\n";
		echo "> Statistics, unique in 30 days, not in a day.";
		echo "\n\n";
		global $db; $arr = []; $time = strtotime(date('d-m-Y', time()).' -1 months');
		$query = $db->query("SELECT * FROM `redirect` WHERE `time` > $time ORDER BY `time` DESC");
		while($row = $query->fetch()){
			$ip = $row['ip'];
			$date = date('d/m/Y', $row['time']);
			$arr[$date][] = $ip;
		}
		$text = ''; $a = [0, 0];
		foreach($arr as $key => $val){
			$c = count($val);
			$q = count(array_unique($val));
			$text .= "[$key]\tunique $q\tclicks $c\n";
			$a['0'] += $q;
			$a['1'] += $c;
		}
		echo "Total\t\tunique {$a['0']}\tclicks {$a['1']}\n\n";
		echo $text;
		echo "\n\nBy collecting statistics, chaturbate100.com does not store your IP address.";
		echo "</pre>";
		die;
	}
}

function showRoomList(){
	if(isset($_GET['list'])){
		echo "<title>Chaturbate100 Track List</title>";
		echo "<meta http-equiv='refresh' content='60'>";
		echo "<pre>";
		echo "<a href='/'>main page</a>\n\n";
		$arr = json_decode(getList(), true);
		ksort($arr);
		echo "track ".count($arr)." rooms\n\n";
		foreach($arr as $key => $val){
			echo $key."\n";
		}
		echo "</pre>";
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
	
	$name = $select->fetch()['name'];
	
	if(empty($name)){
		var_dump($id);
	}
	
	return $name;
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

function genderIncome($start){
	global $db;
	$arr = [];
	$result = [0, 0, 0, 0];
	$end = strtotime("+1 day", strtotime($start));
	$start = strtotime($start);	
	$query = $db->query("SELECT DISTINCT rid FROM stat WHERE `time` BETWEEN $start AND $end");
	while($row = $query->fetch()){
		$select = $db->prepare("SELECT `gender` FROM `room` WHERE `id` = :id");
		$select->bindParam(':id', $row['rid']);
		$select->execute();
		$arr[] = ['id' => $row['rid'], 'gender' => $select->fetch()['gender']];
	}
	foreach($arr as $key => $val){	
		$query = $db->prepare("SELECT SUM(token) as total FROM `stat` WHERE `rid` = :rid AND `time` BETWEEN $start AND $end");
		$query->bindParam(':rid', $val['id']);
		$query->execute();
		$result[$val['gender']] += $query->fetch()['total'];
	}
	ksort($result);
	return $result;
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

function sumOther($arr){
	$result = []; $v = 0;
	foreach($arr as $key => $val){
		if($key != 1) $v += $val;
	}
	return ['0' => $v, '1' => $arr['1']];
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
		$b = "HAVING avg < 2000"; // 100 USD
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

function getBlackList($date){
	global $sphinx; $bl = []; $sql = '';
	$query = $sphinx->query("SELECT did, AVG(token) as avg FROM stat WHERE time > $date GROUP BY did HAVING avg > 20000 ORDER BY avg DESC LIMIT 10000");
	while($row = $query->fetch()){
		$bl[] = $row['did'];
	}
	if(!empty($bl)){
		$sql = "AND did NOT IN (".implode(",", $bl).")";
	}
	return $sql;
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
	$query = $sphinx->prepare("SELECT rid, SUM(token) as total, AVG(token) as avg FROM stat WHERE time > $date ".getBlackList($date)." GROUP BY rid HAVING avg < 1000 ORDER BY total DESC LIMIT 100");
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

function get_ads(){
	$ads = [
		//'<a href="http://wallet.advcash.com/referral/2d41167a-bda8-4403-a3e0-cb32bd49412a" target="_blank"><img class="z11" src="/img/1.gif"></a>' => '1',
		//'<a target="_blank" href="https://www.bestchange.com/" onclick="this.href=\'https://www.bestchange.com/?p=1082361\'"><img class="z11" src="/img/2.jpg"></a>' => '1',
		'<a href="https://www.lovense.com/r/3xe6vd" target="_blank"><img class="z11" src="/img/lv1.png" /></a>' => '1',
		'<a href="https://www.lovense.com/r/3xe6vd" target="_blank"><img class="z11" src="/img/lv2.png" /></a>' => '1'
	];
	$link = [];
	foreach($ads as $key => $val){
		$link = array_merge($link, array_fill(0, $val, $key));
	}
	shuffle($link);
	return $link[random_int(0, count($link) - 1)];
}

function dotFormat($v){
	return number_format($v, 0, ',', ',');
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
