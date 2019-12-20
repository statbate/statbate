<?php
require_once('/var/www/chaturbate100.com/func.php');

if(empty($_POST['room']) || !ctype_digit($_POST['room'])){
	die;
}

echo getModelChartData($_POST['room']);
