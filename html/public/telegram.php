<?php
require_once('../func.php');

// https://api.telegram.org/bot1199215929:AAE-BSlOEiok4fWxHUs6FvRGsihdVYk7LYw/setWebhook?url=https://chaturbate100.com/public/telegram.php?ej0=SkoickPaborcaubwujCukgagobugFuld
if($_GET['ej0'] != 'SkoickPaborcaubwujCukgagobugFuld'){
	die;
}

function botGetInfo($name){
	$x = getRoomInfo($name, false);
	if(!$x){
		return false;
	}
	return $x['id'];
}

function checkSubscriptions($rid){
	global $db, $chatID;
	$query = $db->prepare("SELECT * FROM `subscriptions` WHERE `cid` = :cid AND `rid` = :rid");
	$query->bindParam(':cid', $chatID);
	$query->bindParam(':rid', $rid);
	$query->execute();
	if($query->rowCount() == 0){
		return false;
	}
	return $query->fetch()['id'];
}

function botSubscribe($name, $do){
	global $db, $chatID;
	$id = botGetInfo($name);
	if(!$id){
		return "Wrong room";
	}
	if($do == 'subscribe'){
		if(!checkSubscriptions($id)){
			$query = $db->prepare('INSERT INTO `subscriptions` (`cid`, `rid`) VALUES (:cid, :rid)');
			$query->bindParam(':cid', $chatID);
			$query->bindParam(':rid', $id);
			$query->execute();
			return "Done! You are subscribed to $name";
		}
		return "You are already subscribed to $name";
	}
	if($do == 'unsubscribe'){
		$x = checkSubscriptions($id);
		if($x){
			$query = $db->prepare("DELETE FROM `subscriptions` WHERE `id` = :id");
			$query->bindParam(':id', $x);
			$query->execute();
			return "Done! Unsubscribed $name";
		}
		return "You are not subscribed to $name";
	}
}

function botGetList(){
	global $db, $chatID;
	$query = $db->prepare("SELECT * FROM `subscriptions` WHERE `cid` = :cid");
	$query->bindParam(':cid', $chatID);
	$query->execute();
	if($query->rowCount() == 0){
		return "You have no subscriptions.";
	}
	$text = "Your subscriptions:\n\n";
	while($row=$query->fetch()){
		$select = $db->prepare('SELECT `name` FROM `room` WHERE `id` = :id');
		$select->bindParam(':id', $row['rid']);
		$select->execute();
		$name = $select->fetch()['name'];
		$text .= "$name\n";
	}
	return rtrim($text, "\n");
}

function botRemoveAll(){
	global $db, $chatID;
	$query = $db->prepare("DELETE FROM `subscriptions` WHERE `cid` = :cid");
	$query->bindParam(':cid', $chatID);
	$query->execute();
	return "Done! All subscriptions are deleted.";
}

$request = file_get_contents('php://input');
$arr = json_decode($request, true);

$chatID = $arr["message"]["chat"]["id"];
$data = explode(' ', $arr["message"]["text"]);
switch($data['0']){
	case 'sub':
		if(empty($data['1'])){
			$text = "Empty room.";
		}else{
			$text = botSubscribe($data['1'], 'subscribe');
		}
	break;
	case 'unsub':
		if(empty($data['1'])){
			$text = "Empty room.";
		}else{
			$text = botSubscribe($data['1'], 'unsubscribe');
		}
	break;
	case 'list':
		$text = botGetList();
	break;
	case 'remove':
		$text = botRemoveAll();
	break;
	default:
		$text .= "How to use:\n\n";
		$text .= "To subscribe to notifications: 'sub room'\n\n";
		$text .= "To unsubscribe from a notification: 'unsub room'\n\n";
		$text .= "View list of subscriptions: list\n\n";
		$text .= "Delete all subscriptions: remove all\n\n";
	break;
}

send('sendMessage', $chatID, $text);
