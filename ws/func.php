<?php

function genRandStr($length = 8, $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') { // when upgrade to php 7 => use random_bytes
	$rnd = openssl_random_pseudo_bytes($length);
	for ($i = 0; $i < $length; $i++) {
		@$str .= $chars[ord($rnd[$i]) & strlen($chars)-1];
	}
	return $str;
}

function prepMsg($data) {
	return json_encode([json_encode($data)]);
}

function getRoomAccess($room){
    $html = file_get_contents("https://chaturbate.com/$room/");
    preg_match('/room_password: \'(.*)\',/', $html, $passwd);
    preg_match('/chatws(.*)\.highwebmedia/', $html, $id);
    if(empty($id[1])){
		$id[1] === false;
	}
	return ['passwd' => $passwd[1], 'id' => $id[1]];
}

function getDonId($name){
	global $db;
	$query = $db->prepare("SELECT * FROM `donators` WHERE `name` = :name");
	$query->bindParam(':name', $name, PDO::PARAM_STR);
	$query->execute();
	$row = $query->fetch();
	if(empty($row['id'])){
		$insert = $db->prepare("INSERT INTO `donators` (`name`) VALUES (:name)");
		$insert->bindParam(':name', $name, PDO::PARAM_STR);
		$insert->execute();
		$row['id'] = $db->lastInsertId();
	}
	return $row['id'];
}

function regRoom(){
	global $db, $room, $pid;
	$query = $db->prepare("SELECT * FROM `room` WHERE `name` = :name");
	$query->bindParam(':name', $room, PDO::PARAM_STR);
	$query->execute();
	$row = $query->fetch();
	if(!empty($row['pid'])){
		if (file_exists('/proc/'.$row['pid'])){
			die("Another worker {$row['pid']}".PHP_EOL);
		}
	}
	if($query->rowCount() == 0){
		$insert = $db->prepare("INSERT INTO `room` (`name`, `pid`) VALUES (:name, :pid)");
		$insert->bindParam(':name', $room, PDO::PARAM_STR);
		$insert->bindParam(':pid', $pid, PDO::PARAM_STR);
		$insert->execute();
		$row = ['id' => $db->lastInsertId(), 'name' => $room, 'pid' => $pid];
	}else{
		$update = $db->prepare("UPDATE `room` SET `pid` = :pid WHERE `name` = :name");
		$update->bindParam(':name', $room, PDO::PARAM_STR);
		$update->bindParam(':pid', $pid, PDO::PARAM_STR);
		$update->execute();
	}
	return $row;
}

function readMsg($data, $conn) {
	global $db, $info, $room, $msgCount, $cTime, $roomInfo;
	$first = json_decode(substr($data, 1), true);
	$second = json_decode($first[0], true);
	switch($second['method']) {
		default:
			//echo "\033[32m".$second['method']."\033[0m\n".PHP_EOL;
			if(time() > $cTime){
				$conn->send($msgCount);
				$cTime += 60*10;
				$tmp = getRoomAccess($room);				
				if(empty($tmp['id'])) die('Offline room'.PHP_EOL);
				if($tmp['id'] != $roomInfo['id']) die('Change ws server'.PHP_EOL);
			}
		break;
		case 'onNotify':
			$third = json_decode($second['args'][0], true);
			if(!empty($third['from_username']) && !empty($third['amount'])){
				echo 'DONATE: '.$third['from_username'].': '.$third['amount'].PHP_EOL;
				$donid = getDonId($third['from_username']);
				$query = $db->prepare("INSERT INTO `stat` (`room`, `user`, `amount`, time) VALUES ( :room, :user, :amount, unix_timestamp(now()))");
				$query->bindParam(':room', $info['id'], PDO::PARAM_STR);
				$query->bindParam(':user', $donid, PDO::PARAM_STR);
				$query->bindParam(':amount', $third['amount'], PDO::PARAM_STR);
				$query->execute();
			}
		break;
		case 'onRoomMsg':
			// $third = json_decode($second['args'][1], true);
			// echo $second['args'][0].': '.$third['m'].PHP_EOL;
		break;
		case 'onRoomCountUpdate':
			$query = $db->prepare("INSERT INTO `online` (`room`, `online`, `time`) VALUES (:room, :online, unix_timestamp(now()))");
			$query->bindParam(':room', $info['id'], PDO::PARAM_STR);
			$query->bindParam(':online', $second['args'][0], PDO::PARAM_STR);
			$query->execute();
			echo "Online: {$second['args'][0]}".PHP_EOL;
		break;
		case 'onNotifyPrivateShowApprove':
			die('Start private show, exit'.PHP_EOL);
		break;
	}
}
