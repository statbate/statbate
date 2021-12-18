<?php
require_once('../private/init.php');

if(empty($_POST['room']) || !ctype_digit($_POST['room'])){
	die;
}

echo dotFormat(cacheResult('getAllIncome', ['rid' => $_POST['room']], 3600));
