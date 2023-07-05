<?php
function getDonsLine($arr){
	global $clickhouse;
	$query = $clickhouse->query("SELECT `did`, min(`time`) as ndate FROM `stat` WHERE `rid` = {$arr['id']} AND `time` > today() - toIntervalMonth(12) GROUP BY `did` ORDER BY ndate ASC");
	while($row = $query->fetch()){
		@$o[$row['ndate']]++;
	}
	$z = 0;
	foreach($o as $k => $v){
		$z += $v;
		@$d[] = [strtotime($k)."000", $z];
	}
	return json_encode($d, JSON_NUMERIC_CHECK);
}

function getIncomeLine($arr){
	global $clickhouse;
	$query = $clickhouse->query("SELECT `time` as ndate, sum(`token`) as sum FROM `stat` WHERE `rid` = {$arr['id']} GROUP BY ndate ORDER BY ndate ASC");
	while($row = $query->fetch()){
		@$j += toUSD($row['sum']);
		@$d[] = [strtotime($row['ndate'])."000", $j];
	}
	return json_encode($d, JSON_NUMERIC_CHECK);
}

function getIncomeTips($arr){
	global $clickhouse;
	$query = $clickhouse->query("SELECT `time` as ndate, count(`token`) as count FROM `stat` WHERE `rid` = {$arr['id']} GROUP BY ndate ORDER BY ndate ASC");
	while($row = $query->fetch()){
		@$j += $row['count'];
		@$d[] = [strtotime($row['ndate'])."000", $j];
	}
	return json_encode($d, JSON_NUMERIC_CHECK);
}

function getTop100Tips($arr){
	global $clickhouse; $text = ""; $i = 0;
	$query = $clickhouse->query("SELECT token, did, unix FROM stat WHERE `rid` = {$arr['id']} ORDER BY token DESC LIMIT 100");
	while($row = $query->fetch()){
		$i++;
		$color = "f5f5f5";
		if($i % 2 === 0){
			$color = "ffffff";
		}
		$text .= "<tr style='background: #$color;'>
		<td class='d-none d-sm-table-cell'>$i</td>
		<td class='d-none d-sm-table-cell'>".date("j M Y" ,$row['unix'])."</td>
		<td>".getDonName(['id' => $row['did']])."</td>
		<td>{$row['token']}</td>
		<td>".toUSD($row['token'])."</td>
		</tr>";
	}
	return xTrim($text);
}

function incomeDetails($arr){
	global $clickhouse; $text = "";	
	$query = $clickhouse->query("SELECT did, SUM(token) as total, time FROM stat WHERE rid = {$arr['id']} AND time > today() - toIntervalMonth(1) GROUP BY did, time ORDER BY time DESC, total DESC");
	if($query->rowCount() > 0){
		$xarr = [];
		while($row = $query->fetch()){
			$xarr[$row['time']][] = [cacheResult('getDonName', ['id' => $row['did'], 86000]), $row['total']];		
		}
		$i = 0;
		$text .= "<table class='table table-striped table-bordered dataTable no-footer' style='margin-bottom: 0px !important;'><tr><td>date</td><td>donator</td> <td class='d-none d-sm-table-cell'>token(s)</td> <td>$.spend</td> </tr>";
		foreach($xarr as $key => $val){
			$rs = count($xarr["$key"]);
			$i++;
			$color = "f5f5f5";
			if($i % 2 === 0){
				$color = "ffffff";
			}
			foreach($val as $k => $v){
				$text .= "<tr style='background: #$color;'>";
				if ($k === array_key_first($val)) {
					$text .= "<td rowspan='$rs'>".date("F d", strtotime($key))."</td>";
				}	
				$text .= "<td>".$v[0]."</td>";
				$text .= "<td class='d-none d-sm-table-cell'>".$v[1]."</td>";
				$text .= "<td>".toUSD($v[1])."</td>";
				$text .= "</tr>";
			}
		}
		$text .= "</table>";
	}
	return $text;
}

function getSearchIncome($arr){
	global $clickhouse; $s = ""; $i = 0;
	$query = $clickhouse->query("SELECT time as ndate, COUNT(DISTINCT did) as count, count(token) as tips, SUM(token) as total, AVG(token) as avg FROM stat WHERE rid = {$arr['id']} AND time > today() - toIntervalMonth(1) GROUP BY ndate ORDER BY ndate DESC");
	if($query->rowCount() > 0){
		while($row = $query->fetch()){
			$i++;
			$color = "f5f5f5";
			if($i % 2 === 0){
				$color = "ffffff";
			}
			$s .= "<tr style='background: #$color;'>
				<td>".date("M j", strtotime($row['ndate']))."</td>
				<td>".$row['count']."</td>
				<td>".$row['tips']."</td>
				<td class='d-none d-sm-table-cell'>".toUSD($row['avg'])."</td>
				<td>".toUSD($row['total'])."</td>";
		}
	}
	
	return xTrim($s);
}

function showSearchLast($lastSearch, $l = -10){
	if(!empty($lastSearch)){
		$s = "";
		foreach(array_reverse(array_slice($lastSearch, $l)) as $k => $v){
			$s .= "<span><a href='/search/{$v['db']}/$k'>$k</a></span>";
		}
		return $s;
	}
}

function getDonsTop100($arr){
	$donsTable = ''; $and = '';
	global $clickhouse;
	if($arr['time'] == 'month'){
		$and = "AND time > today() - toIntervalMonth(1)";
	}
	$query = $clickhouse->query("SELECT did, SUM(token) as total, AVG(token) as avg, count(token) as tips FROM stat WHERE rid = {$arr['id']} $and GROUP BY did ORDER BY total DESC LIMIT 100");
	if($query->rowCount() > 0){
		$i = 0;
		while($row = $query->fetch()){
			$i++;
			$color = "f5f5f5";
			if($i % 2 === 0){
				$color = "ffffff";
			}
			$donsTable .= "<tr style='background: #$color;'>
			<td class='d-none d-sm-table-cell'>$i</td>  
			<td>".cacheResult('getDonName', ['id' => $row['did'], 86000])."</td>
			<td class='d-none d-sm-table-cell'>".$row['tips']."</td>
			<td>".toUSD($row['avg'])."</td>
			<td>".toUSD($row['total'])."</td>
			</tr>";
		}
	}
	return $donsTable;
}

function getSimilar($arr){
	global $clickhouse, $dbname;
	$similar = ''; $dons = []; $xdb = 1;
	$query = $clickhouse->query("SELECT did, SUM(token) as total FROM stat WHERE rid = {$arr['id']} AND time > today() - toIntervalMonth(1) GROUP BY did ORDER BY total DESC LIMIT 100");
	while($row = $query->fetch()){
		$dons[] = $row['did'];	
	}
	
	if(empty($dons)){
		return "<span>no data</span>";
	}
	
	if($dbname == 'bongacams'){
		$xdb = 2;
	}
	
	if($dbname == 'stripchat'){
		$xdb = 3;
	}
	
	if($dbname == 'camsoda'){
		$xdb = 4;
	}
	
	$query = $clickhouse->query("SELECT rid, SUM(token) as total FROM stat WHERE did IN (".implode(",", $dons).") AND time > today() - toIntervalMonth(1) GROUP BY rid ORDER BY total DESC LIMIT 11");
	while($row = $query->fetch()){
		if ($row['rid'] == $arr['id']){
			continue;
		}
		$xname = getRoomName(['id' => $row['rid']]);
		$similar .=  "<span><a href='/search/$xdb/$xname'>$xname</a></span>";
	}
	return $similar;
}

function getSearchInfo($info){
	global $clickhouse;
	$gender = ['boy', 'girl', 'trans', 'couple'];
	$query = $clickhouse->query("SELECT SUM(token) as total FROM stat WHERE rid = {$info['id']} AND time > today() - toIntervalMonth(1)");
	$info['current'] = toUSD($query->fetch()['0']);

	return "<table style='border-spacing: 0;'>
	<tr style='background: #f5f5f5; font-size: 15px;'><td>type</td><td>{$gender[$info['gender']]}</td></tr>
	<tr style='background: #ffffff; font-size: 15px;'><td>online</td><td>".get_time_ago($info['last'])."</td></tr>
	<tr style='background: #f5f5f5; font-size: 15px;'><td>income</td><td>\$".dotFormat($info['current'])."</td></tr>
	<tr style='background: #ffffff; font-size: 15px;'><td>total</td><td>\$".dotFormat($info['total'])."</td></tr>
	</table> \n\n";
}

function getIncomeCharts($a){
	global $clickhouse;
	global $clickhouse;
	$data = []; $x = [];
	$query = $clickhouse->query("SELECT `time` as ndate, SUM(`token`) as sum FROM `stat` WHERE `rid` = {$a['id']} AND `time` > today() - toIntervalMonth(12) GROUP BY ndate ORDER BY ndate DESC");
	while($row = $query->fetch()){
		$data[$row['ndate']] = toUSD($row['sum']);
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
