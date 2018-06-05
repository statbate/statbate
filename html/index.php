<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/private/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/private/init/mysql.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/private/func.php');

$info = getStat();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<!-- Global site tag (gtag.js) - Google Analytics -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-120358212-1"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());

			gtag('config', 'UA-120358212-1');
		</script>
		<!-- Global site tag (gtag.js) - Google Analytics -->
		<title>Chaturbate Top 100</title>
		<meta name="description" content="Chaturbate Top 100" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="/css/dataTables.bootstrap4.min.css">
		<script src="/js/jquery.js"></script>
		<script src="/js/jquery.dataTables.min.js"></script>
		<script src="/js/dataTables.bootstrap4.min.js"></script>
		<script> 
			$(document).ready(function(){$("#main").DataTable({order:[[4,"desc"]]})});
		</script>
		<style>
			body { font:15px 'Arial'; background: #EFEFEF; }
			a { color: #0055cc; }
			td,th { text-align: center; }
			.content-box { position: absolute; margin: auto; top: 0; right: 0; bottom: 0; left: 0; width:800px; height: 85%; }
			.content-info { min-height:283px; margin:0px auto; background: #FFF; border-radius: 8px; }
			.content-text { padding:10px 15px; }
			.box-shadow--2dp { box-shadow: 0 2px 2px 0 rgba(0, 0, 0, .14), 0 2px 1px -2px rgba(0, 0, 0, .2), 0 1px 5px 0 rgba(0, 0, 0, .12) }
			.page-title { color:#333333; font:15pt 'Times New Roman'; padding-bottom:5px; }
			.page-item.active .page-link { background-color: #337ab7 !important; border-color: #337ab7 !important; }
		</style>
	</head>
	<body>
		<div class=" content-box">
			<div class="content-info box-shadow--2dp">
				<div class="content-text">
				<div class="page-title">Chaturbate Top 100</div>
					<hr/>
					Statistics for 30 days. One token costs <a href="https://support.chaturbate.com/customer/en/portal/articles/2743888-how-do-i-convert-tokens-to-cash-">0.05 cents</a>.
					Average income <?php echo round($info['sum']/100); ?> USD, total <?php echo $info['sum']; ?>.
					<hr/>
					<table id="main" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
						<thead>
							<tr>
								<th>#</th>
								<th>Room</th>
								<th>Status</th>
								<th>Online</th>
								<th>USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo $info['stat']; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</body>
</html>
