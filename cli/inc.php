<?php

function newCookie(){
	global $redis;
	$command = escapeshellcmd('/home/stat/python/cookie.py');
	$json = shell_exec($command);
	$arr = json_decode($json, true);
	$cookie = '';
	foreach($arr as $k => $v){
		if($k != 'ua'){
			$cookie .= "$k=$v;";
		}
	}
	$result = ['cookie' => $cookie, 'agent' => $arr['ua']['User-Agent']];
	$cname = getCacheName('pageCookie');
	$redis->setex($cname, 60*60*24*30, json_encode($result));
	return $result;
}

function getCookie(){
	global $redis;
	$cname = getCacheName('pageCookie');
	$result = $redis->get($cname);
	if($result === false){
		return newCookie();
	}
	return json_decode($result, true);
}

function checkCloudflare($html){
	$res = preg_match("/<title>(.*)<\/title>/siU", $html, $title_matches);
	if(!empty($title_matches[1])){
		$title = preg_replace('/\s+/', ' ', $title_matches[1]);
		$title = trim($title);
		if (strpos($title, 'Cloudflare') !== false) {
			return true;
		}
	}
	return false;
}

function getPage($url){
	$h = getCookie();
	$cookie_file = '/home/stat/php/cli/cookies.txt';
	
	$headers = [
		"User-Agent: {$h["agent"]}",
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_COOKIESESSION, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
	curl_setopt($ch, CURLOPT_COOKIE, "{$h["cookie"]}");
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	
	if(checkCloudflare($output)){
		newCookie();
		die;
	}
	
	return $output;
}
