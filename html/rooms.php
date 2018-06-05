<?php

if($_SERVER['REMOTE_ADDR'] != '178.32.144.122') die;
header('Content-Type: text/plain; charset=utf-8');

function safeExec($command){
	$exec = escapeshellcmd($command);
	return shell_exec($exec);
}

$html = file_get_contents("https://chaturbate.com/");

preg_match_all('/page=(.*)</', $html, $pages);
$pages = (int) $pages[1][array_search(max($pages[1]), $pages[1])];

$pages = 1;

for($i=1; $i<=$pages; $i++){
	$html = file_get_contents("https://chaturbate.com/?page=$i");
	preg_match_all('/alt="(.*)\'s/', $html, $tmp);	
	$rooms[] = $tmp[1]; 
}


$rooms = array_unique($rooms[0]);

foreach($rooms as $key => $val){
	shell_exec("php /var/www/ws/run.php ".escapeshellarg($val)." > /dev/null 2>&1 &");
}
var_dump($rooms);
