<?php
// example: cacheResult('getList', [], 30)
function cacheResult($name, $params = [], $time = 600, $json = false){
	global $redis, $dbname;
	$key = hash('xxh3', $dbname.$name.implode('.',$params));
	$result = $redis->get($key);
	if($result === false || (php_sapi_name() == "cli" && $redis->ttl($key) < 120)){
		$result = call_user_func($name, $params);
		if(!empty($result)){
			if($json){
				$result = json_encode($result);
			}
			$save = true;
			if(php_sapi_name() != "cli" && $time > 120) {
				$count = 1;
				$count_key = hash('xxh3', $key."count");
				$count = $redis->get($count_key);
				if($count !== false){
					$count++;
				}
				$redis->setex($count_key, 120, $count);
				if($count < 3){
					$save = false;
				}
			}
			if($save) {
				$redis->setex($key, $time, $result);
			}
		}
	}
	if($json){
		$result = json_decode($result, true);
	}
	return $result;
}

function getList(){
	global $dbname;
	return file_get_contents("https://statbate.com/$dbname/list/");
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

function getListArr(){
	global $redis, $dbname;
	$cb_list = $redis->get($dbname.'List');
	if($cb_list !== false){
		return json_decode($cb_list, true);
	}
	return false;
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
	if($dbname == 'camsoda'){
		$i = 'c';
	}
	$name = strip_tags($name);
	return "<a href='/{$i}/{$name}' target='_blank' rel='nofollow'>{$name}</a>";
}

function getApiChart(){
	global $redis, $dbname;
	$json = $redis->get($dbname.'List');
	if($json === false){
		return;
	}

	$arr = json_decode($json, true);

	$gender = ['m' => 'Male', 'f' => 'Female', 's' => 'Trans', 'c' => 'Couple'];
	if($dbname == "bongacams"){
		$gender = ['male' => 'Male', 'female' => 'Female', 'transsexual' => 'Trans', 'couple' => 'Couple'];
	}
	if($dbname == "stripchat"){
		$gender = ['male' => 'Male', 'female' => 'Female', 'tranny' => 'Trans', 'group' => 'Couple'];
	}
	if($dbname == "camsoda"){
		$gender = ['m' => 'Male', 'f' => 'Female', 't' => 'Trans', 'c' => 'Couple'];
	}
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

function sanitize_output($buffer){    
    $search = [
		'/\t+/',			// Remove tabs
		'/\n+/',			// Remove extra lines
		'/>\\s+</',			// Remove spaces between opening and closing HTML tags
		'/<!--(.|\s)*?-->/' // Remove HTML comments
    
    ];
    $replace = ['', '', '><', ''];    
    $buffer = preg_replace($search, $replace, trim($buffer));
    return $buffer;
}
