<?php

function getFinStat(){ // cache done
	global $clickhouse;
	
	$query = $clickhouse->query("SELECT SUM(token) FROM stat WHERE time > today() - toIntervalMonth(1)");
	$row =  $query->fetch();
	$total = $row['0'];
	
	$query = $clickhouse->query("SELECT AVG(token), count(DISTINCT rid) FROM stat WHERE time > today() - toIntervalMonth(1) AND rid not in ( SELECT rid FROM stat WHERE time > today() - toIntervalMonth(1) GROUP BY rid HAVING SUM(token) < 2000 )");
	$row =  $query->fetch();
	return ['total' => toUSD($total), 'avg' => toUSD($row['0'], 2), 'count' => $row['1']];
}

function getModalCharts($a){ // cache done
	global $clickhouse; $arr = [];
	$c = 'did';
	if($a['type'] == 'income'){
		$c = 'rid';
	}
	$query = $clickhouse->query("SELECT time as ndate, SUM(token) as total FROM stat WHERE $c = {$a['id']} AND `time` > today() - toIntervalMonth(1) GROUP BY ndate ORDER BY ndate DESC");
	if($query->rowCount() == 0){
		return false;
	}
	while($row = $query->fetch()){
		$arr[] = ['date' => $row['ndate'], 'value' => toUSD($row['total'])];
		$last = $row['total'];
	}
	return json_encode($arr, JSON_NUMERIC_CHECK);
}

function getModalTable($room) {
	global $clickhouse; $result = '';
	$c1 = 'rid'; $c2 = 'did'; $c3 = 'getRoomName';
	if($room['type'] == 'income'){
		$c1 = 'did'; $c2 = 'rid'; $c3 = 'getDonName';
	}
	$tmpl = '<tr><td>{URL}</td><td>{TOTAL}</td><td>{AVG}</td></tr>';
	$query = $clickhouse->query("SELECT $c1, SUM(token) as total, AVG(token) as avg FROM stat WHERE $c2 = {$room['id']} AND time > today() - toIntervalMonth(1) GROUP BY $c1 ORDER BY total DESC LIMIT 20");
	$row =  $query->fetchAll();
	
	foreach($row as $val) {
		$tr = str_replace('{URL}', createUrl(cacheResult($c3, ['id' => $val[$c1]], 86000)), $tmpl);
		$tr = str_replace('{TOTAL}', toUSD($val['total']), $tr);
		$tr = str_replace('{AVG}', toUSD($val['avg'], 2), $tr);
		$result .= $tr;
	}	
	return $result;
}

function getTopDons(){ // cache done
	global $clickhouse; $result = ''; 
	$tmpl = '<tr><td class="d-none d-sm-table-cell">{ID}</td><td>{URL}</td><td class="d-none d-sm-table-cell">{LAST}</td><td class="d-none d-sm-table-cell">{COUNT}</td><td>{AVG}</td><td>{TOTAL}</td></tr>';
	$query = $clickhouse->query("SELECT did, COUNT(DISTINCT rid) as count, MAX(time) as max, SUM(token) as total, AVG(token) as avg FROM stat WHERE time > today() - toIntervalMonth(1) GROUP BY did HAVING avg < 20000 ORDER BY total DESC LIMIT 100");
	$row =  $query->fetchAll();
	$i = 0;
	$arr = [];
	$today = date('Y-m-d', time());
	foreach($row as $val) {
		$i++;
		$d = '<font color="green">today</font>';
		if($val['max'] != $today){
			$d = get_time_ago(strtotime($val['max']));
		}
		$name = cacheResult('getDonName', ['id' => $val['did']], 86000);
		$tr = str_replace('{ID}', $i, $tmpl);
		$tr = str_replace('{URL}', createUrl($name), $tr);
		$tr = str_replace('{LAST}', $d, $tr);
		$tr = str_replace('{COUNT}', $val['count'], $tr);
		$tr = str_replace('{AVG}', toUSD($val['avg'], 2), $tr);
		$tr = str_replace('{TOTAL}', "<a href='#' data-modal-info data-modal-id={$val['did']} data-modal-type=spend data-modal-name=$name>".toUSD($val['total'])."</a>", $tr);
		$result .= $tr;
	}	
	
	return $result;
}

function getModalAmount($arr){ // cache done
	global $clickhouse;
	$c = 'did';
	if($arr['type'] == 'income'){
		$c = 'rid';
	}
	$query = $clickhouse->query("SELECT SUM(token) as total FROM stat WHERE $c = {$arr['id']}");
	if($query->rowCount() == 0){
		return 0;
	}
	return toUSD($query->fetch()['0']);
}

function getDonName($arr){ // cache done
	global $db;
	$select = $db->prepare('SELECT `name` FROM `donator` WHERE `id` = :id');
	$select->bindParam(':id', $arr['id']);
	$select->execute();
	if($select->rowCount() == 1){
		return $select->fetch()['name'];
	}
	return false;
}

function getRoomName($arr){ // cache done
	global $db;
	$select = $db->prepare('SELECT `name` FROM `room` WHERE `id` = :id');
	$select->bindParam(':id', $arr['id']);
	$select->execute();
	if($select->rowCount() == 1){
		return $select->fetch()['name'];
	}
	return false;
}

function getRoomInfo($arr){ // cache done
	global $db;
	$query = $db->prepare('SELECT * FROM `room` WHERE `name` = :name');
	$query->bindParam(':name', $arr['name']);
	$query->execute();
	if($query->rowCount() == 1){
		return $query->fetch();
	}
	if(array_key_exists('return', $arr)){
		return false;
	}
	$query = $db->prepare('INSERT INTO `room` (`name`) VALUES (:name)');
	$query->bindParam(':name', $arr['name']);
	$query->execute();
	$id = $db->lastInsertId();
	return ['id' => $id, 'name' => $arr['name'], 'gender' => 0, 'last' => 0];		
}

function getTop($arr){ // cache done
	global $db, $clickhouse;
	$data = [];
	$gender = $arr['0'];
	if($gender === 'all'){
		$query = $clickhouse->query("SELECT rid, SUM(token) as total, count(DISTINCT did) as dons, count(DISTINCT time) as days FROM stat WHERE time > today() - toIntervalMonth(1) AND did not in (SELECT did FROM stat WHERE time > today() - toIntervalMonth(1) GROUP BY did HAVING AVG(token) > 20000) GROUP BY rid HAVING AVG(token) < 1000 ORDER BY total DESC LIMIT 100");
	}else{
		$query = $clickhouse->query("SELECT rid, SUM(token) as total, count(DISTINCT did) as dons, count(DISTINCT time) as days FROM stat LEFT JOIN room ON stat.rid = room.id WHERE gender = $gender AND time > today() - toIntervalMonth(1) AND did not in (SELECT did FROM stat WHERE time > today() - toIntervalMonth(1) GROUP BY did HAVING AVG(token) > 20000) GROUP BY rid, gender HAVING AVG(token) < 1000 ORDER BY total DESC LIMIT 100");
	}
	$row =  $query->fetchAll();
	foreach($row as $val) {
		$data[$val['rid']]['token'] = $val['total'];
		$data[$val['rid']]['dons'] = $val['dons'];
		$data[$val['rid']]['days'] = $val['days'];
	}		
	$in = implode(', ', array_column($row, 'rid'));
	
	if(empty($in)){
		return false;
	}
	
	$query = $db->query("SELECT `id`, `name`, `gender`, `last` FROM `room` WHERE `id` IN ($in)");
	while($row = $query->fetch()){
		@$data[$row['id']]['name'] = $row['name'];
		$data[$row['id']]['last'] = $row['last'];
		$data[$row['id']]['gender'] = $row['gender'];
	}
	return $data;
}

function prepareTable($g){ // cache done
	
	global $dbname;
	
	$xdb = 1;
	$arr = getListArr();
	
	if($dbname == 'bongacams'){
		$xdb = 2;
	}
	
	if($dbname == 'stripchat'){
		$xdb = 3;
	}
	
	if($dbname == 'camsoda'){
		$xdb = 4;
	}
	
	$i = 0; $stat = ''; $list = [];
	$gender = ['boy', 'girl', 'trans', 'couple'];
	$data = cacheResult('getTop', [$g], 600, true);

	$tmpl = '<tr><td class="d-none d-sm-table-cell">{ID}</td><td>{URL}</td><td>{LAST}</td><td class="d-none d-sm-table-cell">{PERDAY}</td><td class="d-none d-sm-table-cell">{DONS}</td><td>{USD}</td></tr>';

	foreach($data as $key => $val){
		$i++;
		$list[] = $val['name'];
		$val['token'] = toUSD($val['token']);
		
		//print_r( array_keys($arr));
		
		if((in_array($val['name'], $arr) || array_key_exists($val['name'], $arr)) && $val['last'] > time()-60*20){
			$tr = str_replace('{ID}', "<a href='/search/$xdb/{$val['name']}' style='color:#A52A2A'>$i</a>", $tmpl);
		}else{
			$tr = str_replace('{ID}', "<a href='/search/$xdb/{$val['name']}'>$i</a>", $tmpl);
		}
		
		if(in_array($val['name'], $arr) || !empty($arr[$val['name']])){
			$val['last'] = '<font color="green">online</font>';
		}elseif($val['last'] > time()-60*15){
			$val['last'] = '<font color="green">online</font>';
		}else{
			$val['last'] = get_time_ago($val['last']);
		}
		
		$tr = str_replace('{URL}', createUrl($val['name']), $tr);
		$tr = str_replace('{DONS}', $val['dons'], $tr);
		$tr = str_replace('{LAST}', $val['last'], $tr);
		$tr = str_replace('{PERDAY}', round($val['token']/$val['days']), $tr);
		$tr = str_replace('{USD}', "<a href=\"#\" data-modal-info data-modal-id=$key data-modal-type=income data-modal-name=".strip_tags($val['name']).">{$val['token']}</a>", $tr);
		$stat .= $tr;
	}
	return xTrim($stat);
}

function genderIncome($a){ // cache done
	global $clickhouse;
	$arr = []; $t = [];
	$query = $clickhouse->query("SELECT room.gender, SUM(token) as total, time as ndate FROM `stat` LEFT JOIN `room` ON stat.rid = room.id WHERE time > today() - toIntervalMonth({$a[0]}) GROUP by `gender`, ndate ORDER BY ndate ASC");
	while($row = $query->fetch()){
		$arr[$row['gender']][$row['ndate']] = toUSD($row['total']);
	}
	foreach($arr as $key => $val){
		foreach($val as $k => $v){
			@$t['4'][$k] += $v; // All
			if($key != 1){
				@$t['5'][$k] += $v; // Other
			}
		}
	}
	$arr['4'] = $t['4'];
	$arr['5'] = $t['5'];
	ksort($arr);
	return $arr;
}

function getCharts(){ // cache done
	$data = [];
	$arr = cacheResult('genderIncome', [2], 900, true);
	foreach($arr as $key => $val){
		if($key == 0 || $key == 2 || $key == 3){
			continue;
		}
		foreach($val as $k => $v){
			$data[$key][] = ['date' => $k, 'value' => $v];
		}
	}
	$data = array_values($data);
	$stat = json_encode($data, JSON_NUMERIC_CHECK);
	return $stat;
}

function getPieStat(){
	$r = []; $x = [];
	$names = ['Boys', 'Girls', 'Trans', 'Couple'];
	$arr = cacheResult('genderIncome', [1], 900, true);
	foreach($arr as $key => $val){
		if($key == 4 || $key == 5){
			continue;
		}
		foreach($val as $k => $v){
			@$x[$key] += $v;
		}
	}
	foreach($x as $k => $v){
		$r[] = ['name' => $names[$k], 'y' => $v];
	}
	return json_encode($r);
}

function dateRange($first, $last, $step = '+1 day', $format = 'Y-m-d') {
    $dates = [];
    $current = strtotime( $first );
    $last = strtotime( $last );

    while( $current <= $last ) {

        $dates[] = date( $format, $current );
        $current = strtotime( $step, $current );
    }

    return $dates;
}

function getDonsRoomCharts($id){
	global $clickhouse;
	$data = []; $x = [];
	$query = $clickhouse->query("SELECT `time` as ndate, COUNT(DISTINCT `did`) as dons FROM `stat` WHERE `rid` = $id AND `time` > today() - toIntervalMonth(12) GROUP BY ndate ORDER BY ndate DESC");
	while($row = $query->fetch()){
		$data[$row['ndate']] = $row['dons'];
	}
	$arr = dateRange(array_key_last($data), array_key_first($data));
	foreach($arr as $val){
		if(empty($data[$val])){
			$x[] = [strtotime($val)."000", 0];
			continue;
		}
		$x[] = [strtotime($val)."000", $data[$val]];
	}
	return json_encode($x, JSON_NUMERIC_CHECK);
}

function getHeatMap(){
	global $clickhouse;
	$monday = date("Y-m-d", strtotime("last week monday"));
	$sunday = date("Y-m-d", strtotime("last week sunday 23 hours 59 minutes 59 seconds"));
	$query = $clickhouse->query("SELECT toStartOfHour(toDateTime(`unix`)) as date, SUM(`token`) as sum FROM `stat` WHERE time >= '$monday' AND time <= '$sunday' GROUP BY date ORDER BY date ASC");
	$x = [
		 0 => 0,  1 => 0,  2 => 0, 
		 3 => 1,  4 => 1,  5 => 1, 
		 6 => 2,  7 => 2,  8 => 2,
		 9 => 3, 10 => 3, 11 => 3,
		12 => 4, 13 => 4, 14 => 4,
		15 => 5, 16 => 5, 17 => 5,
		18 => 6, 19 => 6, 20 => 6,
		21 => 7, 22 => 7, 23 => 7
	];
	$arr = []; 
	while($row = $query->fetch()){
		$time = strtotime($row['date']);
		$h = date('G', $time);
		$d = date('N', $time)-1;
		@$arr[$d][$x[$h]] += $row['sum'];
	}
	$data = [];
	foreach($arr as $key => $val){
		foreach($val as $k => $v){
			$data[] = [$key, $k, toUSD($v/1000)];
		}
		
	}
	return json_encode($data, JSON_NUMERIC_CHECK);
}

function getHourIncome(){
	global $clickhouse;
	$query = $clickhouse->query("SELECT toStartOfHour(toDateTime(`unix`)) as date, SUM(`token`) as sum FROM `stat` WHERE time = today() GROUP BY date ORDER BY date DESC LIMIT 2");
	$row = $query->fetchAll();
	$time = time() - strtotime(date("m/d/Y H:00:00", time()));
	if($time < 60*5){
		return toUSD($row['1']['sum']/1000);
	}
	return toUSD($row['0']['sum']/$time*3600/1000);
}

function getBestTips(){
	global $clickhouse; $text = "";
	$query = $clickhouse->query("SELECT token, rid, did FROM stat ORDER BY token DESC LIMIT 10");
	while($row = $query->fetch()){
		$text .= "<tr><td>".createUrl(getDonName(['id' => $row['did']]))."</td><td>".toUSD($row['token'])."</td><td>".createUrl(getRoomName(['id' => $row['rid']]))."</td></tr>";
	}
	return $text;
}
