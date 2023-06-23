<?php
require_once('/var/www/statbate/root/private/init.php');

function showRoomList(){
	global $dbname;
	//if(isset($_GET['list'])){
		$arr = json_decode(cacheResult('getList', [], 30), true);
		$debug = json_decode(cacheResult('getDebug', [], 30), true);


		//$users = getStatUsers();
		//$clicks = getStatUsers('clickUsers');

		echo "<title>tracking ".count($arr)." rooms</title>";
		//echo "<meta http-equiv='refresh' content='60'>";
		echo "<style>{body background-color: #eeeeee;}table, th, td {border: 1px solid black;border-collapse: collapse;} td {min-width: 100px; height: 25px; text-align: center; vertical-align: middle;} a { color: #333; text-decoration: none;} a:hover { color: #333; text-decoration: underline;} a:active { color: #333;} </style>";
		echo "<pre>";
		
		echo "<a href='./' style='text-decoration: underline; color: darkgreen;'>statbate.com</a> сollects data from open sources\n";
		echo "- room name or nickname\n";
		echo "- chat log\n\n";
		echo "excluded from rating\n";
		echo "- rooms with an average tips of more than 50$\n";
		echo "- donators with an average tips of more than 1000$\n\n";

		echo "Tracks rooms where online more than 50 viewers\n";
		echo "Stop if the online becomes below 25\n\n";

		echo "This is a technical page. We use it for debugging\n";
		echo "Also for you it is proof that the statistics are trust\n\n";
		
		//echo "Today we have {$users['0']} uniq users and {$users['1']} hits\n\n";
		//echo "{$clicks['0']} uniq users followed links {$clicks['1']} times\n\n";
		
		echo "Debug: <a href='/info'>Chaturbate</a> | <a href='/info/bonga'>BongaCams</a> | <a href='/info/strip' >StripChat</a> \n\n";
		echo "<table>";
		echo "<tr><td>Database</td> <td>$dbname</td>";
		foreach($debug as $key => $val){
			switch($key){
				case 'Alloc':
				case 'HeapSys':
					$val = formatBytes($val);
				break;


				case 'Process':
				continue 2;
				
				case 'Uptime':
					$val =  get_time_ago($val);
				default:
				break;
			}
			echo "<tr><td>$key</td> <td>$val</td>";
		}
		echo "</table> \n\n";
		
		$xdb=1;
		$a = getListArr();

		if($dbname == 'bongacams'){
			$xdb=2;
		}
			
		if($dbname == 'stripchat'){
			$xdb=3;
		}
		
		if($a){
			$count = [0, 0, 0, 0];
			foreach($a as $val){
				if($val['num_users'] > 100){
					$count['0']++;
				}
				if($val['num_users'] > 50){
					$count['1']++;
				}
				if($val['num_users'] > 25){
					$count['2']++;
				}
				$count['3'] += $val['num_users'];
			}
			echo "<table><tr><td>online more</td><td>25</td><td>50</td><td>100</td></tr><tr><td>rooms</td><td>{$count['2']}</td><td>{$count['1']}</td><td>{$count['0']}</td></tr><tr><td>total rooms</td><td colspan='3'>".count($a)."</td></tr><tr><td>total online</td><td colspan='3'>{$count['3']}</td></tr></table>\n";
		}
		
		echo "Click on the name of the room to view detailed statistics or click to ID to view logs\n\n";
		
		echo "<table><tr><td></td><td>room</td> <td>$ income</td> <td title='In minutes'>duration</td> <td>online</td> <td>proxy</td> </tr>";
		$i=0;
		$time = time();
		
		uasort($arr, function($a, $b){
			return $a['income'] < $b['income'];
		});
		
		foreach($arr as $key => $val){
			$i++; 
			

			if (array_key_exists($key, $a)) {
				$val['online'] = $a[$key]['num_users'];
			}else{
				$val['online'] = 'n/a';
			}

			$val['last'] = $time-$val['last'];

			$td = '';
			if($val['last'] > 600) {
				$td = "<td style='background: #ff9800;'>{$val['last']}</td>";
			}
			
			$url = "<a href='https://statbate.com/public/log.php?name=$key&base=$dbname' target='_blank'>$i</a>";
			$key = "<a href='https://statbate.com/search.php?name=$key&db=$xdb' target='_blank'>$key</a>";
			

			echo "<tr><td>$url</td><td>$key</td> <td> ".toUSD($val['income'])."  </td> <td> ".round(($time - $val['start'])/60)."</td> <td>{$val['online']}</td> <td>{$val['proxy']}</td>   $td </tr>";
		}
		
		echo "</table>";
		echo "</pre>";
		die;
	//}
}

switch(@$_GET['b']){
	case 'bonga': $clname = $dbname = 'bongacams'; break;
	case 'strip': $clname = $dbname = 'stripchat'; break;
	default: break;
}

showRoomList();
