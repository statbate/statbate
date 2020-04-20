<?php

require_once('/var/www/chaturbate100.com/func.php');

function sendAnnounce(){
	global $db, $redis; $arr = [];
	$json = $redis->get(getCacheName('apiList'));
	$online =  json_decode($json, true);
	$query = $db->query('SELECT DISTINCT `rid` FROM `subscriptions`');
	while($row=$query->fetch()){
		$select = $db->prepare('SELECT `name` FROM `room` WHERE `id` = :id');
		$select->bindParam(':id', $row['rid']);
		$select->execute();
		$arr[$row['rid']] = $select->fetch()['name'];
	}
	foreach($arr as $key => $val){
		$xname = "subscriptions".$val;
		if(array_key_exists($val, $online['list'])){
			echo "$val online!\n";
			$stat = $redis->get($xname);
			if($stat === false){
				$query = $db->prepare("SELECT `cid` FROM `subscriptions` WHERE `rid` = :rid");
				$query->bindParam(':rid', $key);
				$query->execute();
				while($row=$query->fetch()){
					send('sendMessage', $row['cid'], "$val now online!\nhttps://chaturbate100.com/room/".$val);
				}
			}
			$redis->setex($xname, 1200, $stat);
		}else{
			$redis->delete($xname);
		}
	}
}

sendAnnounce();
