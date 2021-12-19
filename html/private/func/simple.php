<?php
// example: cacheResult('getList', [], 30)
function cacheResult($name, $params = [], $time = 600, $json = false){
	global $redis;
	$key = md5($name.implode('.',$params));
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
	return file_get_contents('https://statbate.com/list/');
}

function dotFormat($v){
	return number_format($v, 0, ',', ',');
}

function toUSD($v){
	$x = $v*0.05; // One TK = 0.05 USD
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

function showRoomList(){
	if(isset($_GET['list'])){
		echo "<title>Chaturbate100 Track List</title>";
		echo "<meta http-equiv='refresh' content='60'>";
		echo "<pre>";
		echo "<a href='/'>main page</a>\n\n";
		echo "statbate.com сollects data from open sources:\n";
		echo "- room name or nickname (public information)\n";
		echo "- chat log (public information)\n\n";
		echo "Tracks rooms where online more than 50 viewers.\n\n";
		echo "excluded from rating (top 100):\n";
		echo "- donators with an average tips of more than 20000 (1000 USD)\n";
		echo "- rooms with an average tips of more than 1000 (50 USD)\n\n";
		$arr = json_decode(cacheResult('getList', [], 30), true);
		ksort($arr);
		echo "now tracked ".count($arr)." rooms\n\n";
		foreach($arr as $key => $val){
			echo $key."\n";
		}
		echo "</pre>";
		die;
	}
}

function get_ads(){
	$ads = [
		'<a href="http://wallet.advcash.com/referral/2d41167a-bda8-4403-a3e0-cb32bd49412a" rel="nofollow" target="_blank"><img class="z11" style="border-radius: 4px;" width="340" height="40" src="/img/cash.gif"></a>' => '1',
		//'<a href="https://www.lovense.com/r/3xe6vd" rel="nofollow" target="_blank"><img class="z11" style="border-radius: 4px;" width="340" height="40" src="/img/lv1.png" /></a>' => '1',
	];
	$link = [];
	foreach($ads as $key => $val){
		$link = array_merge($link, array_fill(0, $val, $key));
	}
	shuffle($link);
	return $link[random_int(0, count($link) - 1)];
}

// https://www.w3schools.in/php-script/time-ago-function/
function get_time_ago($time){
    $time_difference = time() - $time;
    if($time_difference < 1) { return '1 second'; }
    $condition = [ 12 * 30 * 24 * 60 * 60 =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                24 * 60 * 60            =>  'day',
                60 * 60                 =>  'hour',
                60                      =>  'minute',
                1                       =>  'second'
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
	$name = strip_tags($name);
	return "<a href='https://chaturbate.com/{$name}' target='_blank' rel='nofollow'>{$name}</a>";
}
