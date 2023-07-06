<?php

if(!empty($_GET['base'])){
	if($_GET['base'] == 'bonga'){
		$clname = $dbname = 'bongacams';
	}
	
	if($_GET['base'] == 'strip'){
		$clname = $dbname = 'stripchat';
	}
	
	if($_GET['base'] == 'soda'){
		$clname = $dbname = 'camsoda';
	}
}

require_once('/var/www/statbate/root/private/init.php');

$track = trackCount();
$bestTips = cacheResult('getBestTips', [], 3600);
$arr = json_decode(cacheResult('getList', [], 30), true);

$xdb = 1;
$a = getListArr(); 
$statbateConf = '{"page": "list", "redirect": "https://statbate.com/l/", "platform": "chaturbate", "speed_gauge": 60}';
$title = "Chaturbate";
$canonical = 'https://statbate.com/online'; 

if($dbname == 'bongacams'){
	$xdb = 2;
	$statbateConf = '{"page": "list", "redirect": "https://statbate.com/b/", "platform": "bongacams", "speed_gauge": 10}';
	$title = "BongaCams";
	$canonical = 'https://statbate.com/bonga/online'; 
}

if($dbname == 'stripchat'){
	$xdb = 3;
	$statbateConf = '{"page": "list", "redirect": "https://statbate.com/s/", "platform": "stripchat", "speed_gauge": 30}';
	$title = "Stripchat";
	$canonical = 'https://statbate.com/strip/online'; 
}

if($dbname == 'camsoda'){
	$xdb = 4;
	$statbateConf = '{"page": "list", "redirect": "https://statbate.com/c/", "platform": "camsoda", "speed_gauge": 10}';
	$title = "CamSoda";
	$canonical = 'https://statbate.com/soda/online'; 
}

if($a){
	$count = [0, 0, 0, 0, 0];
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
		if($val['num_users'] > 10){
			$count['3']++;
		}
		$count['4'] += $val['num_users'];
	}
}

uasort($arr, function($a, $b){
	return $a['income'] < $b['income'];
});

$time = time(); $i=0; $tr = "";
foreach($arr as $key => $val){
	if($val['income'] < 400){
		continue;
	}
	
	if($time > $val['last']+60*60){
		continue;
	}
	
	$i++;
	
	$avg = toUSD($val['income']/$val['tips']);
	
	$duration = round(($val['last'] - $val['start'])/60);
	
	$hour = 0;
	if($val['income'] > 0 && $duration > 0){
		$hour = toUSD($val['income']/($duration/60));
	}
	
	$income = toUSD($val['income']);
	if($hour > $income){
		$hour = $income;
	}
	
	$url = "<a href='/log/$dbname/$key' target='_blank'>$i</a>";
	$key = "<a href='/search/$xdb/$key' target='_blank'>$key</a>";
	$tr .= "<tr>
		<td class=\"d-none d-sm-table-cell\">$url</td>
		<td>$key</td>
		<td class=\"d-none d-sm-table-cell\">".round(($time - $val['start'])/60)."</td>
		<td>$hour</td>
		<td class=\"d-none d-sm-table-cell\">$avg</td>
		<td class=\"d-none d-sm-table-cell\">{$val['dons']}</td>
		<td>$income</td>
	</tr>";
}
//<meta name="robots" content="noindex, nofollow" />

?><!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo $title; ?> Pulse</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<meta name="format-detection" content="telephone=no">
		<meta name="description" content="List of live cams and estimated income per hour">
		<link rel="canonical" href="<?php echo $canonical;?>" />
		<link rel="shortcut icon" type="image/webp" href="/img/favicon.webp" />
		
		<!-- CSS -->
		<link rel="stylesheet" href="/css/normalize.css">
		<link rel="stylesheet" href="/css/font-awesome.slim.min.css">
		<link rel="stylesheet" href="/css/bootstrap.slim.min.css" >
		<link rel="stylesheet" href="/css/dataTables.bootstrap5.min.css" >
		<link rel="stylesheet" href="/css/simplebar.css" >
		<link rel="stylesheet" href="/css/statbate.css?12">
		
		<!-- JS -->
		<script>
			var statbateConf = <?php echo $statbateConf; ?>;
		</script>
	</head>
	
	<body>
	<div class="statbate">
		<!-- header start -->
		<div class="header">
			<div class="header_menu">
					<a href="/" aria-label="Move to main page" class="header_logo"></a>
					<ul class="header_navbar">
						<li><a href="/" id="chaturbate_link">Chaturbate</a></li>
						<li><a href="/bonga" id="bongacams_link">BongaCams</a></li>
						<li><a href="/strip" id="stripchat_link">Stripchat</a></li>
						<li><a href="/soda" id="camsoda_link">CamSoda</a></li>
						<li><a href="/search">Search</a></li>
					</ul>
					<a class="header_track trackCount" href="./online">track <?php echo $track; ?> rooms</a>
			</div>
		</div>
		<script>
			document.getElementById(statbateConf.platform + "_link").classList.add("nav_active");
		</script>
		<!-- header end -->
		
		<!-- header mobile start -->
		<div class="header_mobile">
			<div class="header_menu">
				<a href="/" class="header_logo"></a>
				<div class="icon-menu-wrap">
					<div class="icon-menu" onclick="choose()">
						<span></span>
						<span></span>
						<span></span>
					</div>
				</div>	
			</div>
		</div>
		<div class="header_mobile_nav">
			<a href="/" class="color_first">Chaturbate</a>
			<a href="/bonga" class="color_second">BongaCams</a>
			<a href="/strip" class="color_first">Stripchat</a>
			<a href="/search" class="color_second">Search</a>
			<a class="header_track trackCount color_first" href="./online"><?php echo $track; ?> rooms</a>
		</div>
		<!-- header mobile end -->
		
		<!-- content start -->
		<div class="content">
			
			<div class="list_info">
				
				<div class="online">
					<table class="table_online">
						<tbody>
							<tr>
								<td>Online more</td>
								<td>10</td>
								<td>25</td>
								<td>50</td>
								<td>100</td>
							</tr>
							<tr>
								<td>Rooms</td>
								<td><?php echo $count['3']; ?></td>
								<td><?php echo $count['2']; ?></td>
								<td><?php echo $count['1']; ?></td>
								<td><?php echo $count['0']; ?></td>
							</tr>
							<tr>
								<td>Total rooms</td>
								<td colspan="4"><?php echo count($a); ?></td>
							</tr>
							<tr>
								<td>Total online</td>
								<td colspan="4"><?php echo $count['4']; ?></td>
							</tr>

						</tbody>
					</table>
				</div>
				
				<div class="content_activity">
					<figure class="highcharts-figure">
							<div id="container-activity" class="chart-container"></div>
					</figure>
				</div>
			
			</div>
			
			<div class="tab-content fixload">
					<div class="promo-block">
						<a href="https://www.getmonero.org" target="_blank"><img src="/img/xmr.webp" width="380" height="31" alt="monero banner"></a>
					</div>
				
				<!-- test data start -->
					<table id="list1" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" style="display: none">
						<thead>
							<tr>
								<th class="d-none d-md-table-cell"></th>
								<th data-toggle="tooltip">room</th>
								<th data-toggle="tooltip" title="Stream duration in minutes" class="d-none d-sm-table-cell">time</th>
								<th data-toggle="tooltip" title="Income per hour">hour</th>
								<th data-toggle="tooltip" title="Average tip" class="d-none d-sm-table-cell">avg</th>
								<th data-toggle="tooltip" title="Uniq donators" class="d-none d-md-table-cell">dons</th>
								<th data-toggle="tooltip" title="Income per stream">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo xTrim($tr); ?> 
						</tbody>
					</table>
					<!-- test data end -->
				
				</div>

		</div>
		<!-- content end -->
		
		<!-- footer start -->
		<div class="footer">
			<div class="footer_banner">
				<a href="/s/" rel="nofollow"><img alt="banner" width="770" height="94" class="banner" src="/img/strip.webp"></a>
			</div>
			<div class="footer_bottom">
				<div class="footer_social">
					<a href="https://twitter.com/statbate" aria-label="Subscribe to our twitter" target="_blank" rel="nofollow">
						<i class="fa fa-twitter" aria-hidden="true"></i>
					</a>
					<a href="https://github.com/statbate" aria-label="Subscribe to our github" target="_blank" rel="nofollow">
						<i class="fa fa-github" aria-hidden="true"></i>
					</a>
					<a href="https://t.me/statbate" aria-label="Subscribe to our telegram" target="_blank" rel="nofollow">
						<i class="fa fa-telegram" aria-hidden="true"></i>
					</a>
				</div>
			</div>
		</div>
		<!-- footer end -->
		
	</div>
		
	
	<!-- JS -->
	<script src="/js/jquery.min.js"></script>
	<script src="/js/jquery.dataTables.min.js"></script>
	<script src="/js/bootstrap.bundle.min.js"></script>
	<script src="/js/dataTables.bootstrap5.min.js"></script>
	<script src="/js/highcharts.js"></script>
	<script src="/js/highcharts-more.js"></script>
	<script src="/js/solid-gauge.js"></script>
	<script src="/js/simplebar.js"></script>
	<script src="/js/statbate.js?11"></script>
	<script>
		// Solid gauge
		var gaugeOptions = {
			chart: {
				type: 'solidgauge'
			},

			title: null,
				
			accessibility: {
				enabled: false
			},

			pane: {
				center: ['50%', '85%'],
				size: '140%',
				startAngle: -90,
				endAngle: 90,
				background: {
					backgroundColor:
						Highcharts.defaultOptions.legend.backgroundColor || '#EEE',
					innerRadius: '60%',
					outerRadius: '100%',
					shape: 'arc'
				}
			},

			exporting: {
				enabled: false
			},

			tooltip: {
				enabled: false
			},

			// the value axis
			yAxis: {
				stops: [
					[0.1, '#55BF3B'], // green
					[0.5, '#DDDF0D'], // yellow
					[0.9, '#DF5353'] // red
				],
				lineWidth: 0,
				tickWidth: 0,
				minorTickInterval: null,
				tickAmount: 2,
				title: {
					y: -45
				},
				labels: {
					y: 16
				}
			},

			plotOptions: {
				solidgauge: {
					dataLabels: {
						y: 5,
						borderWidth: 0,
						useHTML: true
					}
				}
			}
		};
		
		// The speed gauge
		var chartActivity = Highcharts.chart('container-activity', Highcharts.merge(gaugeOptions, {
			yAxis: {
				min: 0,
				max: statbateConf.speed_gauge,
				title: {
					text: 'Income per hour'
				}
			},
			
			chart: {
				height: 123,
				backgroundColor: '#f3f3f5',
			},

			credits: {
				enabled: false
			},

			series: [{
				name: 'Activity',
				data: [<?php echo getHourIncome(); ?>],
				dataLabels: {
					format:
						'<div style="text-align:center">' +
						'<span style="font-size:25px">{y}</span><span style="opacity:0.4">k</span><br/>' +
						'<span style="font-size:12px;opacity:0.4"> usd/h</span>' +
						'</div>'
				},
				tooltip: {
					valueSuffix: 'usd/h'
				}
			}]
		}));
	</script>
	</body>
</html>
