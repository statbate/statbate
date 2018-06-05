<?php
require_once('vendor/autoload.php');
require_once('func.php');

try {
	$db = new PDO("mysql:host=localhost;dbname=cam", "cam", "x");
	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$db->exec("set names utf8");
}
catch(PDOException $e) {
	die('MySQL ERROR'.PHP_EOL);
}

if(empty($argv[1])){
	die('Usage: php run.php room'.PHP_EOL);
}

$room = $argv[1];
$cTime = time();
$pid = getmypid();
$info = [];

$roomInfo = getRoomAccess($room);
if(!$roomInfo['id']) die('Offline room');


$msgLogin = prepMsg([ 'method' => "connect", 'data' => [ "user" => "__anonymous__".genRandStr(), "password" => "anonymous", "room" => $room, "room_password" => $roomInfo['passwd'] ]]);
$msgJoin = prepMsg([ 'method' => "joinRoom", 'data' => [ "room" => $room ]]);
$msgCount = prepMsg([ 'method' => "updateRoomCount", 'data' => [ "model_name" => $room, "private_room" => "false" ]]);


\Ratchet\Client\connect("wss://chatws".$roomInfo['id'].".stream.highwebmedia.com/ws/405/$room/websocket")->then(function($conn) {
	$conn->on('message', function($msg) use ($conn) {
		readMsg($msg, $conn);
	});

	$conn->on('close', function($code = null, $reason = null) {
		echo "Connection closed ({$code} - {$reason})\n";
	});

	global $info, $msgLogin, $msgJoin;

    $conn->send($msgLogin);
    $conn->send($msgJoin);
    
    $info = regRoom();
	   
}, function ($e) {
    echo "Could not connect: {$e->getMessage()}\n";
});
