<?php

try {
	$sphinx = new PDO("mysql:host=127.0.0.1;port=9306;", '', '');
	$sphinx->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$sphinx->exec("set names utf8");
}
catch(PDOException $e) {
	die('sphinx ERROR'.PHP_EOL);
}
