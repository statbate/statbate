<?php
require_once('/var/www/statbate/root/private/init.php');

function getRoomFromUrl(){
	if(!isset($_GET['room'])){
		return false;
	}
	$room = strip_tags($_GET['room']);
	if(empty($room)){
		return false;
	}
	return $room;
}

function getJSRedirect(){
	if(empty($_GET['c'])){
		return false;
	}
	$room = getRoomFromUrl();
	if($_GET['c'] == 'chaturbate' && $room){
		// "window.location.replace('https://chaturbate.com/in/?track=default&tour=dT8X&campaign=TFxTZ&room=$room');";
		return "window.location.replace('https://chaturbate.com/$room');";
	}
	
	if($_GET['c'] == 'bongacams' && $room){
		return "window.location.replace('https://bongacams10.com/track?v=2&c=764864&csurl=https://bongacams.com/$room');";
	}
	
	if($_GET['c'] == 'stripchat'){
		$url = "https://go.xlirdr.com?userId=fbf2e3e9124b04c3a70a80b2370c86d7da74c5a6d2b130caa3c0f68121b2e3e1";
		if($room){
			$url .= "&path=%2F$room";
		}
		return "window.location.replace('$url');";
	}
	
	if($_GET['c'] == 'camsoda'){
		return "window.location.replace('https://www.camsoda.com/enter.php?id=statbate&type=REV&model=$room');";
	}
	
	return false;
}

$js = getJSRedirect();
if(!$js){
	die('error');
}

//header("Location: url");
//die();

?>

<!DOCTYPE html>
<html>
	<head>
		<title>
			Redirect
		</title>
		<script>
			function sleep (time) {
				return new Promise((resolve) => setTimeout(resolve, time));
			}
			sleep(100).then(() => {<?php echo $js; ?>});
		</script>
	</head>
	<body>
		redirect...
	</body>
</html>
