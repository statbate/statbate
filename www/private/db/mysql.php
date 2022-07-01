<?php

try {
	$db = new PDO("mysql:host=localhost;dbname=statbate", "statbate", "passwd");
	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$db->exec("set names binary");
}
catch(PDOException $e) {
	die('MySQL ERROR'.PHP_EOL);
}
