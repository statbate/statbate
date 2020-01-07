<?php
if(php_sapi_name() != "cli"){
	die;
}
require_once('/var/www/chaturbate100.com/func.php');
sendHistory(true);
