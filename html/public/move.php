<!DOCTYPE html>
<html>
	<head>
		<script>
			function sleep (time) {
				return new Promise((resolve) => setTimeout(resolve, time));
			}
			sleep(100).then(() => {
				/*window.location.replace("https://chaturbate.com/in/?track=default&tour=dT8X&campaign=50xHQ&room=<?php echo strip_tags($_GET['room']); ?>");*/
				window.location.replace("https://chaturbate.com/<?php echo strip_tags($_GET['room']); ?>");
			});
		</script>
	</head>
</html>
