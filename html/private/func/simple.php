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

function getDebug(){
	return file_get_contents('https://statbate.com/debug/');
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

function showRoomList(){
	if(isset($_GET['list'])){
		$arr = json_decode(cacheResult('getList', [], 30), true);
		$debug = json_decode(cacheResult('getDebug', [], 30), true);
		uasort($arr, function($a, $b){
			return $a['online'] < $b['online'];
		});
		
		$users = getStatUsers();
		
		echo "<title>tracking ".count($arr)." rooms</title>";
		//echo "<meta http-equiv='refresh' content='60'>";
		echo "<style>{body background-color: #eeeeee;}table, th, td {border: 1px solid black;border-collapse: collapse;} td {min-width: 100px; height: 25px; text-align: center; vertical-align: middle;} a { color: #333; text-decoration: none;} a:hover { color: #333; text-decoration: underline;} a:active { color: #333;} </style>";
		echo "<pre>";
		echo "<a href='/' style='text-decoration: underline; color: darkgreen;'>main page</a>\n\n";	
		echo "statbate.com сollects data from open sources\n";
		echo "- room name or nickname\n";
		echo "- chat log\n\n";
		echo "excluded from rating\n";
		echo "- rooms with an average tips of more than 50$\n";
		echo "- donators with an average tips of more than 1000$\n\n";

		echo "Tracks rooms where online more than 50 viewers\n";
		echo "Stop if the online becomes below 25\n\n";
		
		echo "This is a technical page. We use it for debugging\n";
		echo "Also for you it is proof that the statistics are trust\n\n";
		echo "We keep logs for six hours\n";
		echo "Click on the name of the room to view\n\n";
		echo "Today we have {$users['0']} uniq users and {$users['1']} hits\n\n";		
		echo "<table>";
		foreach($debug as $key => $val){
			switch($key){
				case 'Alloc':
				case 'HeapSys':
					$val = formatBytes($val);
				break;
				
				case 'Uptime':
					$val =  get_time_ago($val);
				default:
				break;
			}
			echo "<tr><td>$key</td> <td>$val</td>";
		}
		echo "</table> \n\n";
		$a = getCbArr();
		if($a){
			$count = [0, 0, 0, 0];
			foreach($a as $val){
				if($val['num_users'] > 100){
					$count['0']++;
				}
				if($val['num_users'] > 50){
					$count['1']++;
				}
				if($val['num_users'] > 25){
					$count['2']++;
				}
				$count['3'] += $val['num_users'];
			}
			echo "<table><tr><td>online more</td><td>25</td><td>50</td><td>100</td></tr><tr><td>rooms</td><td>{$count['2']}</td><td>{$count['1']}</td><td>{$count['0']}</td></tr><tr><td>total rooms</td><td colspan='3'>".count($a)."</td></tr><tr><td>total online</td><td colspan='3'>{$count['3']}</td></tr></table>\n\n";
		}
		echo "<table><tr><td></td><td>room</td> <td>proxy</td> <td>online</td><td>$ income</td><td title='In minutes'>duration</td> </tr>";
		$i=0;
		$time = time();
		foreach($arr as $key => $val){
			$i++;
			if($val['online'] == 0){
				$val['online'] = 'new';
			}


			$val['last'] = $time-$val['last'];

			$td = '';
			if($val['last'] > 600) {
				$td = "<td style='background: #ff9800;'>{$val['last']}</td>";
			}
			
			$key = "<a href='https://statbate.com/public/log.php?name=$key' target='_blank'>$key</a>";
			
			echo "<tr><td>$i</td><td>$key</td> <td>{$val['proxy']}</td> <td>{$val['online']}</td>  <td> ".toUSD($val['income'])." </td> <td> ".round(($time - $val['start'])/60)."</td> $td </tr>";
		}
		echo "</table></pre>";
		die;
	}
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

function getGoogleTrends(){
	$s = "";
	$time = "2020-01-01 ".date("Y-m-d", time());
	$arr = ["Chaturbate", "Stripchat", "BongaCams", "LiveJasmin", "CamSoda"];
	foreach($arr as $val){
		$s .= "{\"keyword\":\"$val\",\"geo\":\"\",\"time\":\"$time\"},";
	}
	return $s;
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

function logDayUsers(){
	global $redis;
	$hash = sha1($_SERVER['REMOTE_ADDR']);
	$key = 'statbateUsers'.date("Ymd", time());
	$json = $redis->get($key);
	if($json === false){
		$arr[$hash] = 1;
		$result = json_encode($arr);
		$redis->setex($key, 86400, $result);
		die;
	}
	$arr = json_decode($json, true);
	if(!empty($arr[$hash])){
		$arr[$hash]++;
	}else{
		$arr[$hash] = 1;
	}
	$result = json_encode($arr);
	$redis->setex($key, 86400, $result);
}

function getStatUsers(){
	global $redis;
	$key = 'statbateUsers'.date("Ymd", time());
	$json = $redis->get($key);
	if($json === false){
		return [0, 0];
	}
	$arr = json_decode($json, true);
	return [count($arr), array_sum($arr)];
}
