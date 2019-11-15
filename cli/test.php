<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://chaturbate.com/");

curl_setopt($ch, CURLOPT_COOKIEJAR, '/home/stat/php/cli/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/home/stat/php/cli/cookies.txt');

curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_HTTPHEADER,
	array(
		"Upgrade-Insecure-Requests: 1",
		"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36",
		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",
		"Accept-Language: en-US,en;q=0.9"
	));

$output = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

var_dump($output);
var_dump($info);
