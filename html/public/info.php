<?php
require_once('../private/init.php');

if(empty($_POST['room']) || !ctype_digit($_POST['room'])){
	die;
}

echo json_encode([
	'table' => cacheResult('getTopDons', ['rid' => $_POST['room']], 3600),
	'income' => dotFormat(cacheResult('getAllIncome', ['rid' => $_POST['room']], 3600)),
	'chart' => cacheResult('getModelChartData', ['rid' => $_POST['room']], 3600)
]);
