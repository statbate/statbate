<?php
require_once('/var/www/statbate/root/private/init.php');

if(empty($_GET['room'])){
	die;
}

$room = strip_tags($_GET['room']);
if(empty($room)){
	die;
}


$js = "window.location.replace('https://chaturbate.com/in/?track=default&tour=dT8X&campaign=TFxTZ&room=$room');";
//$js = "window.location.replace('https://www.live-cam.top/$room');";

logUsers('clickUsers');

//header("Location: https://www.live-cam.top/$room");
//die();

?>

<!DOCTYPE html>
<html>
	<head>
		<script>
			function sleep (time) {
				return new Promise((resolve) => setTimeout(resolve, time));
			}
			sleep(100).then(() => {
				<?php echo $js; ?>
			});
		</script>
		<style>
			/* body {background-color: #eeeeee;}
			.x11 {opacity: 0.5;}
			.x11:hover {opacity: 1.0;} */
		</style>
	</head>
	<body>
		redirect...
		<!--<div class="x11" style="display: flex; justify-content: center; align-items: center; font-size: 44px; padding-top: 10px; text-shadow: 1px 1px 1px black, 0 0 1em grey;">
			<b>don`t buy <p style="color:#ff6600; display:inline;">Monero</p> and <p style="color:#f7931a; display:inline;">Bitcoin</p></b>
		</div> -->
	</body>
</html>
