<?php
require_once('../private/init.php');

if(empty($_POST['id']) || !ctype_digit($_POST['id'])){
	die;
}

$arr = ['id' => $_POST['id'], 'type' => $_POST['type']];

echo json_encode([	
	'table' => cacheResult('getModalTable', $arr, 3600),
	'amount' => number_format(cacheResult('getModalAmount', $arr, 3600)),
	'chart' => cacheResult('getModalCharts', $arr, 3600)
]);
