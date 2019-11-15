<?php

if(php_sapi_name() != "cli"){
	die;
}

$pid = getmypid();

require_once('autoload.php');
require_once('/var/www/chaturbate100.com/func.php');

use CloudflareBypass\CFCurlImpl;
use CloudflareBypass\Model\UAMOptions;

function getPage($url){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER,
		array(
			"Upgrade-Insecure-Requests: 1",
			"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36",
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",
			"Accept-Language: en-US,en;q=0.9"
		));
	$cfCurl = new CFCurlImpl();
	$cfOptions = new UAMOptions();
	$cfOptions->setVerbose(true);
	try {
		$page = $cfCurl->exec($ch, $cfOptions);
		return $page;
	} catch (ErrorException $ex) {
		return $ex->getMessage();
	}
}

function getRoomServer($room, $id){
	global $db;
    $html = getPage("https://chaturbate.com/$room/");
    preg_match('/chatws(.*)\.highwebmedia/', $html, $server); 
    if(empty($server['1'])){
		return false;
	}
	$server['1'] = (int) filter_var($server['1'], FILTER_SANITIZE_NUMBER_INT);
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

function startBot($name, $server){
	$server = 'chatws'.$server;
	$room = getRoomInfo($name);
	if($room['last']+60*10 > time()){
		return;
	}
	echo "start $name $server\n";
	file_get_contents("https://chaturbate100.com/cmd/?room=$name&server=$server");
}

function checkPID($pid){
	global $redis;
	$x = $redis->get('startLock');
	if($x === false || $x != $pid){
		return false;
	}
	return true;
}

function checkWorker($room){
	global $workerList;
	if (array_key_exists($room, $workerList)) {
		return true;
	}
	return false;
}

function randSleep(){	
	usleep(random_int(1000000, 3000000));
}

$lock = $redis->get('startLock');
if($lock !== false){
	die;
}
$redis->setex('startLock', 600, $pid);

$workerList = json_decode(file_get_contents("https://chaturbate100.com/list/"), true);

$html = getPage("https://chaturbate.com/");
preg_match_all('/page=(.*)</', $html, $pages);
$pages = (int) $pages[1][array_search(max($pages[1]), $pages[1])];

if($pages > 10){
	$pages = 10;
}

randSleep();
for($i=1; $i<=$pages; $i++){
	$html = getPage("https://chaturbate.com/?page=$i");
	preg_match_all('/alt="(.*)\'s/', $html, $tmp);
	foreach($tmp[1] as $k => $v){
		$rooms[] = $v;
	}
	randSleep();
}
$rooms = array_unique($rooms);

foreach($rooms as $key => $val){
	if(!checkPID($pid)){
		die;
	}
	if(checkWorker($val)){
		continue;
	}	
	$room = getRoomInfo($val);
	if(empty($room)){
		$room['id'] = createRoom($val);
		$room['last'] = 0;
	}
	if($room['last']+60*10 > time()){
		continue;
	}
	$id = getRoomServer($val, $room['id']);
	if($id){
		startBot($val, $id);
	}
	randSleep();
}

$redis->delete('startLock');
