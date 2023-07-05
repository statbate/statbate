<?php

switch(@$_GET['db']){
	case '2': 
		$xdb = 2;
		$clname = $dbname = 'bongacams';
	break;
	case '3': 
		$xdb = 3; 
		$clname = $dbname = 'stripchat';
	break;
	case '4': 
		$xdb = 4; 
		$clname = $dbname = 'camsoda';
	break;
	default: $xdb = 1; break;
}

require_once('/var/www/statbate/root/private/init.php');
require_once('/var/www/statbate/root/private/func/search.php');

$track = trackCount();

$lastSearch = $redis->get('lastSearch');
if($lastSearch !== false){
	$lastSearch = json_decode($lastSearch, true);
}

$info = false;

if(!empty($_GET['name'])){
	$info = getRoomInfo(['name' => strtolower($_GET['name']), 'return' => true]);
}

if(!$info){
	$arrFilter = array_filter($lastSearch, function($item) use ($xdb){
		if($item['db'] == $xdb){
			return $item;
		}
	});
	$lname = key(array_slice($arrFilter, -1, 1, true)); // get last search	
	if(empty($lname)){
		$lname = 'ehotlovea';
		if($xdb == 2){
			$lname = 'misstake';
		}
		if($xdb == 3){
			$lname = 'jessicakay288';
		}
		if($xdb == 4){
			$lname = 'lolabunniii';
		}
	}
	$info = getRoomInfo(['name' => $lname, 'return' => true]);
}

$info['total'] = getModalAmount(['id' => $info['id'], 'type' => 'income']);

$lastSearch[$info['name']] = ['time' => time(), 'db' => $xdb];

uasort($lastSearch, function($a, $b){
	return $a['time'] > $b['time'];
});

$lastSearch = array_slice($lastSearch, -20);

if($info['last'] > time()-60*60*24*30){ 
	$redis->setex('lastSearch', 86400*30, json_encode($lastSearch));
}

if($xdb == 1) {
	$list_link = '/online';
	$statbateConf = '{"page": "search", "redirect": "https://statbate.com/l/", "platform": "chaturbate"}';
}
		
if($xdb == 2) {
	$list_link = '/bonga/online';
	$statbateConf = '{"page": "search", "redirect": "https://statbate.com/b/", "platform": "bongacams"}';
}
		
if($xdb == 3) {
	$list_link = '/strip/online';
	$statbateConf = '{"page": "search", "redirect": "https://statbate.com/s/", "platform": "stripchat"}';
}

if($xdb == 4) {
	$list_link = '/soda/online';
	$statbateConf = '{"page": "search", "redirect": "https://statbate.com/c/", "platform": "camsoda"}';
}

?><!DOCTYPE html>
<html lang="en">
	<head>
		<title>Statbate • <?php echo $info['name']; ?> •</title>
		<meta charset="UTF-8">
		<meta name="description" content="How much do webcam models make? Now you know the answer!">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<meta name="format-detection" content="telephone=no">
		<meta name="robots" content="noindex, nofollow" />
		<link rel="shortcut icon" type="image/webp" href="/img/favicon.webp" />
		
		<!-- CSS -->
		<link rel="stylesheet" href="/css/normalize.css">
		<link rel="stylesheet" href="/css/font-awesome.slim.min.css">
		<link rel="stylesheet" href="/css/bootstrap.slim.min.css" >
		<link rel="stylesheet" href="/css/dataTables.bootstrap5.min.css" >
		<link rel="stylesheet" href="/css/simplebar.css" >
		<link rel="stylesheet" href="/css/statbate.css?11">
	</head>
	
	<body>
	<div class="statbate">
		<!-- header start -->
		<div class="header">
			<div class="header_menu">
					<a href="/" aria-label="Move to main page" class="header_logo"></a>
					<ul class="header_navbar">
						<li><a href="/" >Chaturbate</a></li>
						<li><a href="/bonga">BongaCams</a></li>
						<li><a href="/strip">Stripchat</a></li>
						<li><a href="/soda">CamSoda</a></li>
						<li><a href="/search" class="nav_active">Search</a></li>
					</ul>
					<a class="header_track trackCount" href="<?php echo $list_link; ?>">track <?php echo $track; ?> rooms</a>
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
			<a class="header_track trackCount color_first" href="<?php echo $list_link; ?>"><?php echo $track; ?> rooms</a>
		</div>
		<!-- header mobile end -->
		
		<!-- content start -->
		<div class="content">
			<div class="content_info">
				<div class="search_info">
					<div class="input-group input-group-sm">									
						<select class="form-select" style="background-size: 10px 12px; padding: 0.25rem 8px; max-width: 48px; background-position: right 8px center;" id="searchBase">
							<option value="1" <?php if($xdb==1){ echo "selected"; } ?>>cb</option>
							<option value="2" <?php if($xdb==2){ echo "selected"; } ?>>bc</option>
							<option value="3" <?php if($xdb==3){ echo "selected"; } ?>>sc</option>
							<option value="4" <?php if($xdb==4){ echo "selected"; } ?>>cs</option>
						</select>
						<input type="text" class="form-control" style="text-align: center;" id="searchName" name="name" placeholder="Name" aria-label="Name" aria-describedby="button-addon2" value="<?php echo $info['name']; ?>">
						<button class="btn btn-secondary" data-submit-search="" type="submit" id="button-addon2" style="color: #000; background-color: #f5f5f5; border-color: #dee2e6;">send</button>			
					</div>
					<?php echo getSearchInfo($info); ?>
				</div>
							
				<div class="search_resent">
					<div class="search_info">
						<div class="search_header">Recent Searches</div>
						<div class="search_result" data-simplebar>
							<?php echo showSearchLast($lastSearch); ?>
						</div>
					</div>
				</div>
				
				<div class="search_similar">
					<div class="search_info">
						<div class="search_header">Similar</div>
						<div class="search_result" data-simplebar>
							<?php echo cacheResult('getSimilar', ['id' => $info['id']], 600); ?>
						</div>
					</div>
				</div>
			
			</div>
			
			
			
					
			
			<div class="content_nav">
				<ul class="nav nav-tabs justify-content-center" id="nav-tab">
					<li class="nav-item">
						<button class="nav-link active" id="tab5-tab" data-bs-toggle="tab" data-bs-target="#tab5">Total</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1">Income</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3">Month</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="tab111-tab" data-bs-toggle="tab" data-bs-target="#tab111">Details</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2">Donators</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="tab4-tab" data-bs-toggle="tab" data-bs-target="#tab4">Top100</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="tab8-tab" data-bs-toggle="tab" data-bs-target="#tab8">All time</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" id="tab7-tab" data-bs-toggle="tab" data-bs-target="#tab7">Tips</button>
					</li>
					<li class="nav-item">
						<button class="nav-link" data-submit-profile="">Profile page</button>
					</li>
				</ul>
			</div>
			
			<div class="content_nav_mobile">
				<div class="dropdown select-tab">
					<button class="col btn btn-dark btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Total
					</button>
					<div class="dropdown-menu nav nav-tabs collapse" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item " href="#tab5" data-bs-toggle="tab" role="tab" aria-controls="tab5-tab" aria-selected="false">Total</a>
						<a class="dropdown-item" href="#tab1" data-bs-toggle="tab" role="tab" aria-controls="tab1-tab" aria-selected="false">Income</a>
						<a class="dropdown-item" href="#tab3" data-bs-toggle="tab" role="tab" aria-controls="tab3-tab" aria-selected="false">Month</a>
						<a class="dropdown-item" href="#tab111" data-bs-toggle="tab" role="tab" aria-controls="tab111-tab" aria-selected="false">Details</a>
						<a class="dropdown-item" href="#tab2" data-bs-toggle="tab" role="tab" aria-controls="tab2-tab" aria-selected="false">Donators</a>
						<a class="dropdown-item" href="#tab4" data-bs-toggle="tab" role="tab" aria-controls="tab4-tab" aria-selected="false">Top100</a>
						<a class="dropdown-item" href="#tab8" data-bs-toggle="tab" role="tab" aria-controls="tab8-tab" aria-selected="false">All time</a>
						<a class="dropdown-item" href="#tab7" data-bs-toggle="tab" role="tab" aria-controls="tab7-tab" aria-selected="false">Tips</a>
						<a class="dropdown-item" data-submit-profile="">Profile page</a>
					</div>
				</div>
			</div>
			
			<div class="tab-content">
				
				<div aria-labelledby="tab5-tab" role="tabpanel active" class="tab-pane fade active show" id="tab5">
					<div class="grap_cont" id="container-lineIncome"></div>
				</div>
				
				<div aria-labelledby="tab1-tab" role="tabpanel" class="tab-pane fade" id="tab1">
					<div class="grap_cont" id="container-income"></div>
				</div>
				
				<div aria-labelledby="tab2-tab" role="tabpanel" class="tab-pane fade" id="tab2">
					<div class="grap_cont" id="container-dons"></div>
				</div>
				
				<div aria-labelledby="tab7-tab" role="tabpanel" class="tab-pane fade" id="tab7">
					<div class="grap_cont" id="container-lineTips"></div>
					<table id="search_income" class="table table-striped table-bordered dataTable no-footer" cellspacing="0"  role="grid" aria-describedby="supportList_info" style="width: 100%; margin-top: 0 !important;">
						<thead>
							<tr>
								<th class="d-none d-sm-table-cell" style="width: 5%; font-weight: 400;"></th>
								<th class="d-none d-sm-table-cell" style="width: 25%; font-weight: 400;">date</th>
								<th style="width: 40%; font-weight: 400; font-style: oblique;">donator</th>
								<th style="width: 15%; font-weight: 400;">tokens</th>
								<th style="width: 15%; font-weight: 400;">usd</th>
							</tr>
						</thead>
						<tbody>
							<?php echo cacheResult('getTop100Tips', ['id' => $info['id']], 600); ?>
						</tbody>
					</table>
				</div>
				
				<div aria-labelledby="tab3-tab" role="tabpanel" class="tab-pane fade" id="tab3">
					<div class="table-neresponsive">
						<table id="search_income" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" aria-describedby="supportList_info" style="margin-bottom: 0px !important;">
							<thead>
								<tr>
									<th>date</th>
									<th>dons</th>
									<th>tips</th>
									<th class="d-none d-sm-table-cell">avg</th>
									<th>usd</th>
								</tr>
							</thead>
							<tbody>
								<?php echo cacheResult('getSearchIncome', ['id' => $info['id']], 600); ?>
								</tbody>
							</table>
						</div>
					</div>
					
					<div aria-labelledby="tab8-tab" role="tabpanel" class="tab-pane fade" id="tab8">
						<table id="search_income" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" aria-describedby="supportList_info" style="margin-bottom: 0px !important;">
							<thead>
								<tr>
									<th class="d-none d-sm-table-cell" style="width: 5%;"></th>
									<th style="width: 50%;">donator</th>
									<th class="d-none d-sm-table-cell" style="width: 15%;">tips</th>
									<th style="width: 15%;">AVG</th>
									<th style="width: 15%;">USD</th>
								</tr>
							</thead>
							<tbody>
								<?php echo cacheResult('getDonsTop100', ['id' => $info['id'], 'time' => 'all'], 600); ?>
							</tbody>
						</table>
					</div>
					
					<div aria-labelledby="tab111-tab" role="tabpanel" class="tab-pane fade" id="tab111">
						<?php echo cacheResult('incomeDetails', ['id' => $info['id']], 600); ?>
					</div>
					
					<div aria-labelledby="tab4-tab" role="tabpanel" class="tab-pane fade" id="tab4">
						
						<table id="search_income" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" aria-describedby="supportList_info" style="margin-bottom: 0px !important;">
							<thead>
								<tr>
									<th class="d-none d-sm-table-cell" style="width: 5%;"></th>
									<th style="width: 50%;">donator</th>
									<th class="d-none d-sm-table-cell" style="width: 15%;">tips</th>
									<th style="width: 15%;">AVG</th>
									<th style="width: 15%;">USD</th>
								</tr>
							</thead>
							<tbody>
								<?php echo cacheResult('getDonsTop100', ['id' => $info['id'], 'time' => 'month'], 600); ?>
							</tbody>
						</table>
						
					</div>
				
				
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
	<script>
		var statbateConf = <?php echo $statbateConf; ?>;
	</script>
	<script src="/js/jquery.min.js"></script>
	<script src="/js/jquery.dataTables.min.js"></script>
	<script src="/js/bootstrap.bundle.min.js"></script>
	<script src="/js/dataTables.bootstrap5.min.js"></script>
	<script src="/js/highstock.js"></script>
	<script src="/js/data.js"></script>
	<script src="/js/simplebar.js"></script>
	<script src="/js/statbate.js?9"></script>
	<script>
		$(document).on("click","[data-submit-profile]", function(e){
			$(this).blur();
			e.preventDefault();
			window.open(statbateConf.redirect+$('input[id=searchName]').val(), '_blank');
		});
		
		function movePage(){
			window.location.href = "/search/"+ $('#searchBase option:selected').val() + "/" + $('input[id=searchName]').val();
		}
				
		$(document).on("click","[data-submit-search]", function(e){
			$(this).blur();
			e.preventDefault();
			movePage();
		});
				
		document.addEventListener('keypress', function (e) {
			if (e.keyCode === 13 || e.which === 13) {
				e.preventDefault();
				movePage();
			}
		});
		
		Highcharts.stockChart('container-lineIncome', {
			title: {text: ''},
			chart: { height: 325 },
			rangeSelector: {selected: 5},
			credits: { enabled: false },
			scrollbar: { enabled: false },
			accessibility: { enabled: false },
			navigator: {maskFill: 'rgba(230, 230, 230, 0.45)'},
			series: [{
				name: 'Total USD',
				color: '#009E60',
				data: <?php echo cacheResult('getIncomeLine', ['id' => $info['id']], 600); ?>,
				type: 'line',
				tooltip: {
					valueDecimals: 0
				}
			}]
		});
		
		Highcharts.stockChart('container-income', {
			title: {text: ''},
			chart: { height: 325 },
			chart: {alignTicks: false},
			credits: { enabled: false },
			scrollbar: {enabled: false},
			accessibility: { enabled: false },
			rangeSelector: {selected: 0},
			navigator: {maskFill: 'rgba(230, 230, 230, 0.45)'},
			plotOptions: {column: {stacking: 'normal',borderRadius: 3}},
				series: [{
					type: 'column',
					name: 'Income',
					color: '#7393B3',
					data: <?php echo cacheResult('getIncomeCharts', ['id' => $info['id']], 600); ?>,					
					dataGrouping: {
						units: [['month',[1]]]
					}
				}]
		});
		
		Highcharts.stockChart('container-dons', {
			title: {text: ''},
			chart: { height: 325 },
			credits: { enabled: false },
			scrollbar: { enabled: false },
			accessibility: { enabled: false },
			rangeSelector: {selected: 5},
			navigator: {maskFill: 'rgba(230, 230, 230, 0.45)'},
			series: [{
				name: 'Uniq Count',
				color: '#DAA520',
				data: <?php echo cacheResult('getDonsLine', ['id' => $info['id']], 600); ?>,
				type: 'line',
				tooltip: {
					valueDecimals: 0
				}
			}]
		});
		
		Highcharts.stockChart('container-lineTips', {
			title: {text: ''},
			chart: {height: 275},
			credits: { enabled: false },
			rangeSelector: {selected: 5},
			scrollbar: {enabled: false},
			accessibility: { enabled: false },
			navigator: {maskFill: 'rgba(230, 230, 230, 0.45)'},
			series: [{
				name: 'Count Tips',
				color: '#009E60',
				data: <?php echo cacheResult('getIncomeTips', ['id' => $info['id']], 600); ?>,
				type: 'line',
				tooltip: {
					valueDecimals: 0
				}
			}]
		});
	</script>
	
	</body>
</html>
