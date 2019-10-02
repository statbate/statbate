<?php
require_once('func.php');
$topDon = getTopDons();
$fin = getFinStat();
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Chaturbate Top 100</title>
		<meta name="description" content="Chaturbate Top 100" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/dataTables.bootstrap4.min.css">
		<link rel="stylesheet" href="/css/main.css">
		<script src="/js/jquery.js"></script>
		<script src="/js/bootstrap.min.js"></script>
		<script src="/js/jquery.dataTables.min.js"></script>
		<script src="/js/dataTables.bootstrap4.min.js"></script>
		<script src="/js/highcharts.js"></script>
		<script src="/js/exporting.js"></script>
		<script src="/js/export-data.js"></script>
		<script>
			var hcData = <?php echo getCharts(); ?>;
		</script>
		<script src="/js/main.js"></script>
	</head>
<body>
	<div class="content-box">
		<div class="content-info box-shadow--2dp">
			<div class="content-text" >
				<div id="container" style="min-width: 310px; height: 225px; margin: 0 auto"></div>
					<hr/>
					<div class="wslog">
						<div class="wstext">
						</div>
					</div>	
					<div class="rinfo">
						<center>Statistics for last month<br/>
						One token costs <a href="https://support.chaturbate.com/customer/en/portal/articles/2743888-how-do-i-convert-tokens-to-cash-">0.05 cents</a><br/>
						<hr/>
						<?php echo "Total income {$fin['total']} USD<br/> Average tip {$fin['avg']} USD"; ?>
						<hr/>
						<a href="" data-toggle="modal" data-target="#trendsModal"><img src="/img/trends.png"></a>
						</center>
					</div>
					<div class="clear"></div>
					<hr/>					
					<div id="donTopLink" style="display: none; height:20px; width: 200px; position: absolute; margin-left: 245px; margin-top: 7px; z-index: 99;">
						<font color="#006400">>></font> <a href="" data-toggle="modal" data-target="#donModal">TOP 20 DONATORS</a> <font color="#006400"><<</font>
					</div>
					<table id="main" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
						<thead>
							<tr>
								<th style="width:1px;"></th>
								<th>room</th>
								<th style="width:1px;">gender</th>
								<th title="Use search online">last</th>
								<th title="Avarage online">online</th>
								<th title="Income per month">USD</th>
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
						<script type="text/javascript" src="https://ssl.gstatic.com/trends_nrtr/1937_RC01/embed_loader.js"></script> <script type="text/javascript"> trends.embed.renderExploreWidget("TIMESERIES", {"comparisonItem":[{"keyword":"Chaturbate","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"MyFreeCams","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"Stripchat","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"BongaCams","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"},{"keyword":"Streamate","geo":"","time":"2011-07-01 <?php echo date("Y-m-d", time()); ?>"}],"category":0,"property":""}, {"exploreQuery":"date=today%205-y&q=Chaturbate,MyFreeCams,Stripchat,BongaCams,Streamate","guestPath":"https://trends.google.com:443/trends/embed/"}); </script> 
						<hr/>
						<script type="text/javascript" src="https://ssl.gstatic.com/trends_nrtr/1937_RC01/embed_loader.js"></script> <script type="text/javascript"> trends.embed.renderExploreWidget("GEO_MAP", {"comparisonItem":[{"keyword":"Chaturbate","geo":"","time":"today 5-y"},{"keyword":"MyFreeCams","geo":"","time":"today 5-y"},{"keyword":"Stripchat","geo":"","time":"today 5-y"},{"keyword":"BongaCams","geo":"","time":"today 5-y"},{"keyword":"Streamate","geo":"","time":"today 5-y"}],"category":0,"property":""}, {"exploreQuery":"date=today%205-y&q=Chaturbate,MyFreeCams,Stripchat,BongaCams,Streamate","guestPath":"https://trends.google.com:443/trends/embed/"}); </script> 
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
									<th></th>
									<th>USD</th>
									<th title="Average tip">AVG</th>
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
	</body>
</html>
