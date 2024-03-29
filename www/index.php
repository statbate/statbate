<?php

$title = 'Chaturbate Top 100';
$statbateConf = '{"page": "main", "redirect": "https://statbate.com/l/", "platform": "chaturbate", "heatmap": 60}';
$token_cost = 0.05;
$urlOnline = 'https://statbate.com/online';
$pageDesc = 'Unleash Your Chaturbate Journey: Discover the Ultimate Collection of Top Models.';
$canonical = 'https://statbate.com'; 

if(!empty($argv[1])){
	$_GET['base'] = $argv[1];
}

if(!empty($_GET['base'])){
	
	if($_GET['base'] == 'bonga'){
		$urlOnline = 'https://statbate.com/bonga/online';
		$clname = $dbname = 'bongacams';
		$title = 'BongaCams Top 100';
		$statbateConf = '{"page": "main", "redirect": "https://statbate.com/b/", "platform": "bongacams", "heatmap": 6}';
		$token_cost = 0.025;
		$canonical = 'https://statbate.com/bonga'; 
		$pageDesc = 'How much do webcam models make? Now you know the answer!';
	}
	
	if($_GET['base'] == 'strip'){
		$urlOnline = 'https://statbate.com/strip/online';
		$clname = $dbname = 'stripchat';
		$title = 'Stripchat Top 100';
		$statbateConf = '{"page": "main", "redirect": "https://statbate.com/s/", "platform": "stripchat", "heatmap": 30}';
		$canonical = 'https://statbate.com/stip'; 
		$pageDesc = 'Find the best Stripchat models';
	}
	
	if($_GET['base'] == 'soda'){
		$urlOnline = 'https://statbate.com/soda/online';
		$clname = $dbname = 'camsoda';
		$title = 'CamSoda Top 100';
		$statbateConf = '{"page": "main", "redirect": "https://statbate.com/c/", "platform": "camsoda", "heatmap": 4}';
		$canonical = 'https://statbate.com/soda'; 
		$pageDesc = 'Only best CamSoda models';
	}

}

require_once('/var/www/statbate/root/private/init.php');

$topDon = cacheResult('getTopDons', [], 3600);
$heatMap = cacheResult('getHeatMap', [], 3600);
$bestTips = cacheResult('getBestTips', [], 3600);

$fin = cacheResult('getFinStat', [], 3600, true);
$track = trackCount();
$apiCharts = getApiChart();
ob_start("sanitize_output");
?><!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo $title; ?></title>
		<meta charset="UTF-8">
		<meta name="description" content="<?php echo $pageDesc; ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<meta name="format-detection" content="telephone=no">
		<link rel="canonical" href="<?php echo $canonical;?>" />
		<link rel="shortcut icon" type="image/webp" href="/img/favicon.webp" />
		
		<!-- CSS -->
		<link rel="stylesheet" href="/css/normalize.min.css">
		<link rel="stylesheet" href="/css/font-awesome.slim.min.css">
		<link rel="stylesheet" href="/css/metricsgraphics.min.css">
		<link rel="stylesheet" href="/css/bootstrap.slim.min.css" >
		<link rel="stylesheet" href="/css/dataTables.bootstrap5.min.css" >
		<link rel="stylesheet" href="/css/simplebar.min.css" >
		<link rel="stylesheet" href="/css/statbate.min.css?21">
		
		<!-- JS -->
		<script>
			var statbateConf = <?php echo $statbateConf; ?>;
			var hcData = <?php echo getCharts(); ?>;
			var pieStat = <?php echo getPieStat(); ?>;
			var pieRooms = <?php echo $apiCharts[0]; ?>;
			var pieViewers = <?php echo $apiCharts[1]; ?>;
			var heatMap = <?php echo $heatMap; ?>;	
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
					<script>
						document.getElementById(statbateConf.platform + "_link").classList.add("nav_active");
					</script>
					<a class="header_track trackCount" href="<?php echo $urlOnline; ?>">track <?php echo $track; ?> rooms</a>
			</div>
		</div>
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
			<a class="header_track trackCount color_first" href="<?php echo $urlOnline; ?>"><?php echo $track; ?> rooms</a>
		</div>
		<!-- header mobile end -->
		
		<!-- content start -->
		<div class="content">
			
			
			<div class="income_chart"></div>
			
			<div class="content_center">
				<div class="content_wslog" data-simplebar>
					<div class="wstext"></div>
				</div>
				<div class="content_table">
					<table class="fin_table">
						<tbody class="fin_table_hide">
							<tr>
								<th colspan="2">Statistics for the last month</th>
							</tr>
						</tbody>
						<tbody>
							<tr>
								<td>Income</td>
								<td>$<?php echo dotFormat($fin['total']); ?></td>
							</tr>
							<tr>
								<td>Average</td>
								<td>$<?php echo round($fin['total'] / $fin['count']); ?></td>
							</tr>
							<tr>
								<td>Average tip</td>
								<td>$<?php echo $fin['avg']; ?></td>
							</tr>
							<tr>
								<td>One token</td>
								<td>$<?php echo $token_cost; ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
	
			<div class="content_nav">
				<ul class="nav nav-tabs justify-content-center" id="nav-tab">
					<li class="nav-item">
						<button class="nav-link active" id="cams-tab" data-bs-toggle="tab" data-bs-target="#cams">Rooms</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="dons-tab" data-bs-toggle="tab" data-bs-target="#dons">Donators</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="boys-tab" data-bs-toggle="tab" data-bs-target="#boys">Boys</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="trans-tab" data-bs-toggle="tab" data-bs-target="#trans">Trans</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="couple-tab" data-bs-toggle="tab" data-bs-target="#couple">Couple</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="incomeCharts-tab" data-bs-toggle="tab" data-bs-target="#incomeCharts">Income</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="roomsCharts-tab" data-bs-toggle="tab" data-bs-target="#roomsCharts">Streamers</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="viewersCharts-tab" data-bs-toggle="tab" data-bs-target="#viewersCharts">Viewers</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="heatmap-tab" data-bs-toggle="tab" data-bs-target="#heatmap">Heat map</button>
					</li>
				</ul>
			</div>
			
			<div class="content_nav_mobile">
				<div class="dropdown select-tab">
					<button class="col btn btn-dark btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Rooms
					</button>
					<div class="dropdown-menu nav nav-tabs collapse" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item color_first" data-bs-toggle="tab" aria-controls="cams" aria-selected="false">Rooms</a>
						<a class="dropdown-item color_second" data-bs-toggle="tab" aria-controls="dons" aria-selected="false">Donators</a>
						<a class="dropdown-item color_first" data-bs-toggle="tab" aria-controls="boys" aria-selected="false">Boys</a>
						<a class="dropdown-item color_second" data-bs-toggle="tab" aria-controls="trans" aria-selected="false">Trans</a>
						<a class="dropdown-item color_first" data-bs-toggle="tab" aria-controls="couple" aria-selected="false">Couple</a>
						<a class="dropdown-item color_second" data-bs-toggle="tab" aria-controls="incomeCharts" aria-selected="false">Income</a>
						<a class="dropdown-item color_first" data-bs-toggle="tab" aria-controls="roomsCharts" aria-selected="false">Streamers</a>
						<a class="dropdown-item color_second" data-bs-toggle="tab" aria-controls="viewers-charts" aria-selected="false">Viewers</a>
						<a class="dropdown-item color_first" data-bs-toggle="tab" aria-controls="heatmap" aria-selected="false">Heat map</a>
					</div>
				</div>
			</div>
			
			<div class="tab-content">
				<div aria-labelledby="cams-tab" role="tabpanel active" class="tab-pane fade active show fixload" id="cams">	
					<div class="promo-block">
						<a href="https://www.getmonero.org" target="_blank"><img src="/img/xmr.webp" width="380" height="31" alt="monero banner"></a>
					</div>
					<table id="main" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" role="grid" aria-describedby="supportList_info" style="display: none">
						<thead>
							<tr>
								<th class="d-none d-sm-table-cell"></th>
								<th>room</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Avarage income per day">$.day</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Uniq donators per month">dons</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable('all'); ?>
						</tbody>
					</table>

				</div>
				<div aria-labelledby="dons-tab" role="tabpanel" class="tab-pane fade" id="dons">
					<div class="promo-block">
						<a href="https://www.getmonero.org" target="_blank"><img src="/img/xmr.webp" width="380" height="31" alt="banner monero"></a>
					</div>
					<table id="top100dons" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" role="grid" aria-describedby="supportList_info">
						<thead>
							<tr>
								<th class="d-none d-sm-table-cell"></th>
								<th>donator</th>
								<th class="d-none d-sm-table-cell">last</th>
								<th class="d-none d-sm-table-cell">rooms</th>
								<th data-toggle="tooltip" data-placement="top" title="Average tip">avg</th>
								<th data-toggle="tooltip" data-placement="top" title="Spend per month">USD</th>
							</tr>
						</thead>
						<tbody>
							 <?php echo $topDon; ?>
						</tbody>
					</table>
				</div>
				
				<div aria-labelledby="couple-tab" role="tabpanel" class="tab-pane fade" id="couple">
					<div class="promo-block">
						<a href="https://www.getmonero.org" target="_blank"><img src="/img/xmr.webp" width="380" height="31" alt="monero banner"></a>
					</div>
					<table id="couple_table" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" role="grid" aria-describedby="supportList_info">
						<thead>
							<tr>
								<th class="d-none d-sm-table-cell"></th>
								<th>room</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Avarage income per day">$.day</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Uniq donators per month">dons</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable(3); ?>
						</tbody>
					</table>
				</div>
				
				
				<div aria-labelledby="boys-tab" role="tabpanel" class="tab-pane fade" id="boys">
					<div class="promo-block">
						<a href="https://www.getmonero.org" target="_blank"><img src="/img/xmr.webp" width="380" height="31" alt="monero banner"></a>
					</div>
					<table id="boys_table" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" role="grid" aria-describedby="supportList_info">
						<thead>
							<tr>
								<th class="d-none d-sm-table-cell"></th>
								<th>room</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Avarage income per day">$.day</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Uniq donators per month">dons</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable(0); ?>
						</tbody>
					</table>
				</div>
				
				<div aria-labelledby="trans-tab" role="tabpanel" class="tab-pane fade" id="trans">
					<div class="promo-block">
						<a href="https://www.getmonero.org" target="_blank"><img src="/img/xmr.webp" width="380" height="31" alt="monero banner"></a>
					</div>
					<table id="trans_table" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" role="grid" aria-describedby="supportList_info">
						<thead>
							<tr>
								<th class="d-none d-sm-table-cell"></th>
								<th>room</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Avarage income per day">$.day</th>
								<th class="d-none d-sm-table-cell" data-toggle="tooltip" data-placement="top" title="Uniq donators per month">dons</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable(2); ?>
						</tbody>
					</table>
				</div>
				
				<div aria-labelledby="incomeCharts-tab" role="tabpanel" class="tab-pane fade" id="incomeCharts">
					<div id="pieStat"></div>
				</div>
				
				<div aria-labelledby="roomsCharts-tab" role="tabpanel" class="tab-pane fade" id="roomsCharts">
					<div id="pieRooms"></div>
				</div>

				<div aria-labelledby="viewersCharts-tab" role="tabpanel" class="tab-pane fade" id="viewersCharts">
					<div id="pieViewers"></div>
				</div>
				
				<div aria-labelledby="heatmap-tab" role="tabpanel" class="tab-pane fade" id="heatmap">				
					<figure class="highcharts-figure">
						<div id="container-map"></div>
					</figure>
				</div>
					
			</div>
		</div>
		<!-- content end -->
		
		<!-- footer start -->
		<div class="footer">
			<div class="footer_banner">
				<?php echo showBanner(); ?>
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
	
	<!-- Modal -->
	<div class="modal fade" id="donRoomModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog" style="max-width: 400px;">
			<div class="modal-content">
				<div style="padding: 10px 10px 0px 10px;">
					<button type="button" class="btn btn-dark" style="width: 100%;color: black !important;background-color:#e8e8e8 !important;" data-bs-dismiss="modal">Close</button>
				</div>
				<div class="modal-body" style="padding: 10px;">
					<table id="donRoomTable" class="table table-striped DonTable">
						<thead>
							<tr>
								<th></th>
								<th>USD</th>
								<th data-toggle="tooltip" data-placement="right" title="Average tip">AVG</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
					<div id="modelChart" class="d-none d-md-block" style="margin-top: -30px; margin-bottom: 10px;"></div>
					<div id="allIncome" style="padding: 0 10px 10px 10px; text-align: center;"></div>
				</div>
			</div>
		</div>
	</div>
	<!-- Modal -->
	
	<!-- JS -->
	<script src="/js/jquery.min.js"></script>
	<script src="/js/d3.min.js"></script>
	<script src="/js/metricsgraphics.min.js"></script>
	<script src="/js/jquery.dataTables.min.js"></script>
	<script src="/js/bootstrap.bundle.min.js"></script>
	<script src="/js/dataTables.bootstrap5.min.js"></script>
	<script src="/js/highcharts.js"></script>
	<script src="/js/heatmap.js"></script>
	<script src="/js/simplebar.js"></script>
	<script src="/js/statbate.min.js?16"></script>
	<script src="/js/statbate.index.min.js?2"></script>
	</body>
</html>
<?php ob_end_flush(); ?>
