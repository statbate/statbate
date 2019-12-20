<?php
if(isset($_GET['list'])){
	echo "<pre><a href='/'>main page</a>\n\n";
	$arr = json_decode(file_get_contents("https://chaturbate100.com/list/"), true);
	ksort($arr);
	echo "track ".count($arr)." rooms<br/><br/>";
	foreach($arr as $key => $val){
		echo $key."<br/>";
	}
	die;
}
require_once('func.php');
$topDon = getTopDons();
$fin = getFinStat();
$track = trackCount();
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Chaturbate Top 100</title>
		<meta name="description" content="How much do webcam models make?" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/dataTables.bootstrap4.min.css">
		<link rel="stylesheet" href="/css/metricsgraphics.min.css">
		<link rel="stylesheet" href="/css/main.css?900">
		<script src="/js/jquery.js"></script>
		<script src="/js/d3.v4.min.js"></script>
		<script src="/js/metricsgraphics.min.js"></script>
		<script src="/js/popper.min.js"></script>
		<script src="/js/bootstrap.min.js"></script>
		<script src="/js/jquery.dataTables.min.js"></script>
		<script src="/js/dataTables.bootstrap4.min.js"></script>		
		<script src="/js/main.js?900"></script>
		
		<style>
			
			.x11 {
				opacity: 0.5;
			}

			.x11:hover {
				opacity: 1.0;
			}
			
		</style>
		
	</head>
	<body>
		<div class="content-box">
			<div class="content-info box-shadow--2dp">
				<div class="content-text" >
					
					<div style="position:relative;">
						<div style="position: absolute;z-index: 100;left: 330px; top: 30px;">
							<a href="" data-toggle="modal" data-target="#trendsModal"><img style="height:18px;" src="/img/trends.png"></a>
						</div>
						<div style="position: absolute;z-index: 100;left: 5px; top: -15px;">
							<a href="https://www.youtube.com/watch?v=Gc2en3nHxA4" target="_blank"><img class="x11" src="/img/bitcoin.png"></a>
						</div>
						<div style="position: absolute;z-index: 100; top: 25px;">
							<a href="https://web.getmonero.org/" target="_blank"><img class="x11" src="/img/monero.png"></a>
						</div>
						<div style="position: absolute;z-index: 100;right: 7px; top: 5px;">
							<h6><span id="trackCount" class="badge badge-secondary" style="font-weight:normal; padding: 5px 10px; border: 1px solid #25639a; background: transparent; box-shadow: 0px 0px 1px 0px #000; color: #333;" ><a href="/?list">track <?php echo $track; ?> rooms</a></span></h6>
						</div>
						<div style="position: absolute;z-index: 100;left: 280px; top: 5px; font-size:19.2px; color:rgb(51, 51, 51);">
							Chaturbate daily income
						</div>
						<div id="container"></div>
						<script>
							var hcData = <?php echo getCharts(); ?>;
							function showStat() {
								data = MG.convert.date(hcData, 'date');
								MG.data_graphic({
									//title: "Chaturbate daily income",		
									// 426A8E
									// 3A2CF8
									// 2B84F8
									// 076CF0
									// 0559C6
									data: data,
									width: 770,
									height: 225,
									right: 0,
									target: document.getElementById('container'),
									x_accessor: 'date',
									y_accessor: 'value',
									color: ['#25639a']
								});
							}
							showStat();
						</script>
					</div>
					
					<div class="clear"></div>
					
					<hr style="margin-top: 0;"/>
					
					<div style="height: 145px;">
						<div class="wslog">
							<div class="wstext"></div>
						</div>
						<div class="rinfo">		
							<center>
								Statistics for the last 30 days<br/>
								<table class="table table-bordered" style=" margin-bottom: 0px; margin-top: 7px;" >
									<tbody>
										<tr>
											<td style="padding: 6px 0px;">Total income</td>
											<td style="padding: 6px 12px;">&#36;<?php echo $fin['total']; ?></td>
										</tr>
										<tr>
											<td style="padding: 6px 0px;">Average income</td>
											<td style="padding: 6px 12px;">&#36;<?php echo round($fin['total']/$fin['count']); ?></td>
										</tr>
										<tr>
											<td style="padding: 6px 0px;">Average tip</td>
											<td style="padding: 6px 12px;">&#36;<?php echo $fin['avg']; ?></td>
											</tr>
										<tr>
											<td style="padding: 6px 0px;">One token</td>
											<td style="padding: 6px 12px;"><a href="https://support.chaturbate.com/customer/en/portal/articles/2743888-how-do-i-convert-tokens-to-cash-">&#36;0.05</a></td>
										</tr>
									</tbody>
								</table>
							</center>
						</div>
					</div>

					<div class="clear"></div>
					<hr/>
					
					<div id="donTopLink" style="display: none; height:20px; width: 200px; position: absolute; margin-left: 245px; margin-top: 7px; z-index: 99;">
						<font color="#006400">&gt;&gt;</font> <a href="" data-toggle="modal" data-target="#donModal">TOP 20 DONATORS</a> <font color="#006400">&lt;&lt;</font>
					</div>
					
					<table id="main" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
						<thead>
							<tr>
								<th style="width:1px;"></th>
								<th>room</th>
								<th style="width:1px;">gender</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th data-toggle="tooltip" data-placement="top" title="Avarage online">online</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per 30 days">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo getStat(); ?>
						</tbody>
					</table>		
				</div>
			</div>	
		</div>
		
		<div class="modal fade" id="trendsModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog" style="max-width: 800px;">
				<div class="modal-content">
					<div class="modal-body">
						<script type="text/javascript" src="https://ssl.gstatic.com/trends_nrtr/1937_RC01/embed_loader.js"></script> <script type="text/javascript"> trends.embed.renderExploreWidget("TIMESERIES", {"comparisonItem":[{"keyword":"Chaturbate","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"MyFreeCams","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"Stripchat","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"BongaCams","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"LiveJasmin","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"}],"category":0,"property":""}, {"exploreQuery":"date=today%205-y&q=Chaturbate,MyFreeCams,Stripchat,BongaCams,LiveJasmin","guestPath":"https://trends.google.com:443/trends/embed/"}); </script> 
						<hr/>
						<script type="text/javascript" src="https://ssl.gstatic.com/trends_nrtr/1937_RC01/embed_loader.js"></script> <script type="text/javascript"> trends.embed.renderExploreWidget("GEO_MAP", {"comparisonItem":[{"keyword":"Chaturbate","geo":"","time":"today 5-y"},{"keyword":"MyFreeCams","geo":"","time":"today 5-y"},{"keyword":"Stripchat","geo":"","time":"today 5-y"},{"keyword":"BongaCams","geo":"","time":"today 5-y"},{"keyword":"LiveJasmin","geo":"","time":"today 5-y"}],"category":0,"property":""}, {"exploreQuery":"date=today%205-y&q=Chaturbate,MyFreeCams,Stripchat,BongaCams,LiveJasmin","guestPath":"https://trends.google.com:443/trends/embed/"}); </script> 
					</div>
				</div>
			</div>
		</div>
		
		<div class="modal fade" id="donModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog" style="max-width: 400px;">
				<div class="modal-content">
					<div class="modal-body">
						<table class="table table-striped DonTable">
							<thead>
								<tr>
									<th>TOP 20 DONATORS</th>
									<th>USD</th>
									<th data-toggle="tooltip" data-placement="right" title="Average tip">AVG</th>
								</tr>
							</thead>
							<tbody>
								<?php echo $topDon; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		
		<div class="modal fade" id="donRoomModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog" style="max-width: 400px; min-height: 680px;">
				<div class="modal-content">
					<div class="modal-body">		
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
						<hr/>
						<div id="modelChart" style="margin-top: -25px;"></div>
						<div id="allIncome"></div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
