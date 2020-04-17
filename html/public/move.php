<?php
// [15/04/2020] Chaturbate has banned affiliate account (rev-share) and did not pay the balance.
// 
// Move traffic to random popular room.
//
// https://chaturbate.com/realhousewifexxx/		=> 8uHNv
// https://chaturbate.com/you_are_my_sunshine/	=> zl8g8
// https://chaturbate.com/kaileeshy/			=> Wu1wo
// https://chaturbate.com/mashayang/			=> H0dyQ
//
require_once('../func.php');

function hashIP($ip){
	$ip = inet_pton($ip);
	return hash('whirlpool', password_hash(hash('sha512', $ip), PASSWORD_BCRYPT, ['cost' => 10, 'salt' => hash('sha256', $ip)]));
}

$arr = ['8uHNv', 'zl8g8', 'Wu1wo', 'H0dyQ'];
if(empty($_GET['room'])){
	die;
}
$room = strip_tags($_GET['room']);
if(empty($room)){
	die;
}
$af = $arr[array_rand($arr)];
$js = "window.location.replace('https://chaturbate.com/in/?track=default&tour=dT8X&campaign=$af&room=$room');";

$ip = hashIP($_SERVER['REMOTE_ADDR']);
$time = time();

$query = $db->prepare('INSERT INTO `redirect` (`ip`, `affiliate`, `time`) VALUES (:ip, :af, :time)');
$query->bindParam(':ip', $ip);
$query->bindParam(':time', $time);
$query->bindParam(':af', $af);
$query->execute();
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
	</head>
</html>
