<?php
if(php_sapi_name() != "cli"){
	die;
}

header('Content-Type: text/plain; charset=utf-8');
require_once('/var/www/chaturbate100.com/func.php');

function startBot($name, $server){
	$server = 'chatws'.$server;
	$room = getRoomInfo($name);
	if($room['last']+60*10 > time()){
		return;
	}
	echo "start $name $server\n";
	file_get_contents("https://chaturbate100.com/cmd/?room=$name&server=$server");
}

$html = file_get_contents("https://chaturbate.com/");
preg_match_all('/page=(.*)</', $html, $pages);
$pages = (int) $pages[1][array_search(max($pages[1]), $pages[1])];

if($pages > 5){
	$pages = 5;
}

for($i=1; $i<=$pages; $i++){
	$html = file_get_contents("https://chaturbate.com/?page=$i");
	preg_match_all('/alt="(.*)\'s/', $html, $tmp);
	foreach($tmp[1] as $k => $v){
		$rooms[] = $v;
	}
}
$rooms = array_unique($rooms);

foreach($rooms as $key => $val){
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
	sleep(1);
}
