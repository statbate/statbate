<?php

header('Access-Control-Allow-Origin: *');

if(empty($_POST['id']) || !ctype_digit($_POST['id'])){
	die;
}

$arr = ['id' => $_POST['id'], 'type' => $_POST['type']];

if(!empty($_POST['cam']) && $_POST['cam'] == 'bongacams'){
	$clname = $dbname = 'bongacams';
}

if(!empty($_POST['cam']) && $_POST['cam'] == 'stripchat'){
	$clname = $dbname = 'stripchat';
}

if(!empty($_POST['cam']) && $_POST['cam'] == 'camsoda'){
	$clname = $dbname = 'camsoda';
}

require_once('../private/init.php');

echo json_encode([	
	'table' => cacheResult('getModalTable', $arr, 3600),
	'amount' => number_format(cacheResult('getModalAmount', $arr, 3600)),
	'chart' => cacheResult('getModalCharts', $arr, 3600)
]);
