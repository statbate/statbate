<?php
// example: cacheResult('getList', [], 30)
function cacheResult($name, $params = [], $time = 600, $json = false){
	global $redis, $dbname;
	$key = md5($dbname.$name.implode('.',$params));
	$result = $redis->get($key);
	if($result === false || (php_sapi_name() == "cli" && $redis->ttl($key) < 120)){
		$result = call_user_func($name, $params);
		if(!empty($result)){
			if($json){
				$result = json_encode($result);
			}
			$redis->setex($key, $time, $result);
		}
	}
	if($json){
		$result = json_decode($result, true);
	}
	return $result;
}

function getList(){
	global $dbname;
	if($dbname == 'chaturbate'){
		$url = 'https://statbate.com/list/';
	}
	if($dbname == 'bongacams'){
		$url = 'https://statbate.com/bongacams/list/';
	}
	if($dbname == 'stripchat'){
		$url = 'https://statbate.com/stripchat/list/';
	}
	return file_get_contents($url);
}

function getDebug(){
	global $dbname;
	if($dbname == 'chaturbate'){
		$url = 'https://statbate.com/debug/';
	}
	if($dbname == 'bongacams'){
		$url = 'https://statbate.com/bongacams/debug/';
	}
	if($dbname == 'stripchat'){
		$url = 'https://statbate.com/stripchat/debug/';
	}
	return file_get_contents($url);
}

function dotFormat($v){
	return number_format($v, 0, ',', ',');
}

function toUSD($v){
	global $dbname;
	$u = 0.05;
	if($dbname == 'bongacams'){
		$u = 0.025;
	}
	$x = $v*$u; // One TK = 0.05 USD
	if($x < 10){
		return round($x, 2);
	}
	return round($x);
}

function trackCount(){
	if(!$list = cacheResult('getList', [], 180)){
		return 0;
	}
	return count(json_decode($list, true));
}

function formatBytes($size, $precision = 2){
	if(empty($size)){
		return 0;
	}
    $base = log($size, 1024);
    $suffixes = ['', 'KB', 'MB', 'GB', 'TB', 'PB'];
    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function getCbArr(){
	global $redis;
	$cb_list = $redis->get('chaturbateList');
	if($cb_list !== false){
		return json_decode($cb_list, true);
	}
	return false;
}

function getBgArr(){
	global $redis;
	$bg_list = $redis->get('bongacamsList');
	if($bg_list !== false){
		return json_decode($bg_list, true);
	}
	return false;
}

function getStArr(){
	global $redis;
	$st_list = $redis->get('stripchatList');
	if($st_list !== false){
		return json_decode($st_list, true);
	}
	return false;
}

function getCbList(){
	$a = getCbArr();
	if(!$a){
		return [];
	}
	$list = [];
	foreach($a as $key => $val){
		$list[] = $val['username'];
	}
	return $list;
}

// https://www.w3schools.in/php-script/time-ago-function/
function get_time_ago($time){
    $time_difference = time() - $time;
    if($time_difference < 1) { return '1 second'; }
    $condition = [ 12 * 30 * 24 * 60 * 60 =>  'year',
                30 * 24 * 60 * 60       =>  'mth',
                24 * 60 * 60            =>  'day',
                60 * 60                 =>  'hr',
                60                      =>  'min',
                1                       =>  'sec'
    ];
    foreach($condition as $secs => $str){
        $d = $time_difference/$secs;
        if($d >= 1){
            $t = round($d);
            return $t.' '.$str.($t > 1 ? 's' : '');
        }
    }
}

function createUrl($name){
	global $dbname;
	$i = 'l';
	if($dbname == 'bongacams'){
		$i = 'b';
	}
	if($dbname == 'stripchat'){
		$i = 's';
	}
	$name = strip_tags($name);
	return "<a href='/{$i}/{$name}' target='_blank' rel='nofollow'>{$name}</a>";
}

function getApiChartStrip(){ // dub
	global $redis;
	$json = $redis->get('stripchatList');
	$arr = json_decode($json, true);
	$gender = ['male' => 'Male', 'female' => 'Female', 'tranny' => 'Trans', 'group' => 'Couple'];
	$data = ['Male' => [0, 0], 'Female' => [0, 0], 'Trans' => [0, 0], 'Couple' => [0, 0]];
	
	foreach($arr as $val){

		if(!array_key_exists($val['gender'], $gender)){
			continue;
		}
		
		$key = $gender[$val['gender']];
		$data[$key][0]++;
		$data[$key][1] += $val['num_users'];
	}
	
	$a = $b = [];

	foreach($data as $k => $v){
		$a[] = ['name' => $k, 'y' => $v[0]];
		$b[] = ['name' => $k, 'y' => $v[1]];
	}
	return [json_encode($a), json_encode($b)];	
}

function getApiChartBonga(){ // dub
	global $redis;
	$json = $redis->get('bongacamsList');
	$arr = json_decode($json, true);
	$gender = ['male' => 'Male', 'female' => 'Female', 'transsexual' => 'Trans', 'couple' => 'Couple'];
	$data = ['Male' => [0, 0], 'Female' => [0, 0], 'Trans' => [0, 0], 'Couple' => [0, 0]];
	
	foreach($arr as $val){

		if(!array_key_exists($val['gender'], $gender)){
			continue;
		}
		
		$key = $gender[$val['gender']];
		$data[$key][0]++;
		$data[$key][1] += $val['num_users'];
	}
	
	$a = $b = [];

	foreach($data as $k => $v){
		$a[] = ['name' => $k, 'y' => $v[0]];
		$b[] = ['name' => $k, 'y' => $v[1]];
	}
	return [json_encode($a), json_encode($b)];	
}

function getApiChart(){
	global $redis;
	$json = $redis->get('chaturbateList');
	if($json === false){
		return;
	}

	$arr = json_decode($json, true);

	$gender = ['m' => 'Male', 'f' => 'Female', 's' => 'Trans', 'c' => 'Couple'];
	$data = ['Male' => [0, 0], 'Female' => [0, 0], 'Trans' => [0, 0], 'Couple' => [0, 0]];

	foreach($arr as $val){

		if(!array_key_exists($val['gender'], $gender)){
			continue;
		}
		
		$key = $gender[$val['gender']];
		$data[$key][0]++;
		$data[$key][1] += $val['num_users'];
	}

	$a = $b = [];

	foreach($data as $k => $v){
		$a[] = ['name' => $k, 'y' => $v[0]];
		$b[] = ['name' => $k, 'y' => $v[1]];
	}
	return [json_encode($a), json_encode($b)];
}
