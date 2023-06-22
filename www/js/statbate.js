var mobile = false;
var income_chart = $('.income_chart').width();
var tables = [];

window.addEventListener("resize", function() {
	if($('.income_chart').width() != income_chart){
		showStat();
	}
	if(window.innerWidth > 805){
		if(mobile){
			mobile = false;
			$(".icon-menu").removeClass("_active");
			$(".header_mobile_nav").hide();
		}
	}
	for (const [key] of Object.entries(tables)) {
		if(window.innerWidth > 805 && tables[key]["status"] == "mobile"){
			tables[key]["status"] = "desktop";
			tables[key].page.len(10).draw();
		}
		
		if(window.innerWidth < 805 && tables[key]["status"] == "desktop"){
			tables[key]["status"] = "mobile";
			tables[key].page.len(100).draw();
		}
	}
});

$('.dropdown-menu').on( 'click', 'a', function() {
	text = $(this).html();
	htmlText = text + ' <span class="caret"></span>';
	$(this).closest('.dropdown').find('.dropdown-toggle').html(htmlText);
});

$(document).on("click", "[data-modal-info]", function (e) {
	$(this).blur();
	e.preventDefault();

	var id = $(this).data('modal-id');
	var name = $(this).data('modal-name');
	var type = $(this).data('modal-type');

	$.post("/public/info.php", {'type': type, 'id': id, 'cam': statbateConf.platform}, function(json){
		data = JSON.parse(json);
		if (data.table.length != 0) {
			$("#donRoomTable tr:first th:first").html(name);
			$("#donRoomTable tbody").html(data.table);
			$('#donRoomModal').modal('show');
		}

		if (data.amount.length != 0) {
			$("#allIncome").html('<hr />');
			$("#allIncome").append("<center><b>All time " + type + ": " + data.amount + " USD</b></center><hr/>");
		}

		if (data.chart.length != 0) {
			xx11 = JSON.parse(data.chart);
			var xx22 = MG.convert.date(xx11, 'date');
			MG.data_graphic({
				data: xx22,
				width: 380,
				height: 120,
				right: 10,
				missing_is_zero: true,
				top: 30,
				bottom: 0,
				left: 40,
				target: document.getElementById('modelChart'),
				x_accessor: 'date',
				y_accessor: 'value',
				x_axis: false,
			});
		}

	});
});

function choose() {
	if(mobile) {
		$(".icon-menu").removeClass("_active");
		$(".header_mobile_nav").hide();
		mobile = false;
		return;
	}
	$(".icon-menu").addClass("_active");
	$(".header_mobile_nav").show();
	mobile = true;
}

function showStat() {
	if($('.income_chart').css('display') == 'none' || statbateConf.page != "main"){
		return;
	}
	income_chart = $('.income_chart').width();
	var data = [];
	for (var i = 0; i < hcData.length; i++) {
		//hcData[i] = hcData[i].slice(1, hcData[i].length); // remove first day
		const clone = JSON.parse(JSON.stringify(hcData[i]));
		data[i] = MG.convert.date(clone, 'date');
	}
	MG.data_graphic({
		title: false,
		data: data,
		full_width: true,
		full_height: true,
		//bottom: 32,
		right: 36,
		// x_axis: screen.width >= 568,
		top: 0,
		target: '.income_chart',
		x_accessor: 'date',
		y_accessor: 'value',
		color: ['green', '#25639a', 'brown'],
		legend: ['Girls', 'All', 'Other',],
		area: [false, true, false],
	});
}

function printWsText(text) {
	if ($('.wstext').length == 0 || text.length == 0) {
		return;
	}
	date = new Date();
	xMin = (date.getUTCMinutes() < 10 ? '0' : '') + date.getUTCMinutes()
	xSec = (date.getUTCSeconds() < 10 ? '0' : '') + date.getUTCSeconds()
	time = date.getUTCHours() + ":" + xMin + ":" + xSec;

	message = text;
	if($('.wstext').width() > 450){
		message = '[' + time + '] ' + message;
	}
	
	message = '<div class="message">' + message + '</div>'

	if(msgs.arr.length > 64) {
		msgs.arr.pop()
	}
	msgs.arr.unshift(message)
	document.querySelector('.wstext').innerHTML = msgs.arr.join('');
}

function statbate() {
	var ws = new WebSocket("wss://statbate.com/ws/");
	
	ws.onopen = function () {
		printWsText('Socket is open. Here is the log of big tips.');
		console.log('websocket open');
		ws.send(statbateConf.platform);
	};
	
	window.onbeforeunload = function() {
		ws.onclose = function () {}; // disable onclose handler first
		ws.close(1000);
	};
  
	ws.onclose = function (e) {
		printWsText('Socket is closed. Reconnect will be attempted in 1 second.');
		console.log('Socket is closed. Reconnect will be attempted in 1 second.', e.code);
		setTimeout(function () {
			statbate();
		}, 1000);
		return;
	};
	
	ws.onmessage = function (e) {
		if(e.data == "ping") {
			ws.send("pong");
			return;
		}
		j = JSON.parse(e.data);
		if (j.count) {
			$(".trackCount").text("track " + j.count + " rooms");
			return;
		}
		if(typeof chartActivity !== "undefined" && chartActivity && j.index){
			point = chartActivity.series[0].points[0];
			if(j.index < 10)
				point.update(parseFloat(j.index.toFixed(2)));
			else
				point.update(Math.round(j.index));
			return;
		}
		if(j.donator){
			text = "<a href='"+ statbateConf.redirect + j.donator + "' rel='nofollow' target='_blank'>" + j.donator + "</a> send " + j.amount + " tokens to <a href='" + statbateConf.redirect + j.room + "' rel='nofollow' target='_blank'>" + j.room + "</a>";
			if (j.amount > 499) {
				text = '<font color="#ae8d0b"><b>' + text + '</b></font>';
			}
			printWsText(text);
			return;
		}
	};
}

function createTables(){
	dataTableOptions = {
		bAutoWidth: false,
		oLanguage: {
			sLengthMenu: "Show _MENU_ entries",
			sSearch: "",
			sSearchPlaceholder: "Search",
		},
		pagingType: 'simple_numbers', // or numbers
		iDisplayLength: 10,
		order: [[5, "desc"]],
	};
	aoColumns = [
		{ "orderable": false, "searchable": false, "sWidth": "5%" },
		{ "orderable": false, "sWidth": "35%" },
		{ "orderable": false, "sWidth": "15%" },
		{ "searchable": false, "sWidth": "15%" },
		{ "searchable": false, "sWidth": "15%" },
		{ "searchable": false, "sWidth": "15%" },
	];
	
	tables["main"] = $('#main').DataTable({...dataTableOptions, aoColumns: aoColumns,});
	tables["couple_table"] = $('#couple_table').DataTable({...dataTableOptions, aoColumns: aoColumns,});
	tables["boys_table"] = $('#boys_table').DataTable({...dataTableOptions, aoColumns: aoColumns,});
	tables["trans_table"] = $('#trans_table').DataTable({...dataTableOptions, aoColumns: aoColumns,});
	
	
	aoColumns[2]["searchable"] = false;
	tables["top100dons"] = $("#top100dons").DataTable({...dataTableOptions, aoColumns: aoColumns,});
	
	dataTableOptions.order = [[6, "desc"]];
	aoColumns[1]["sWidth"] = "36%";
	aoColumns[2]["sWidth"] = "11%";
	aoColumns[3]["sWidth"] = "11%";
	aoColumns[4]["sWidth"] = "11%";
	aoColumns[5]["sWidth"] = "11%";
	aoColumns[6] = { "searchable": false, "sWidth": "15%" };	
	tables["list1"] = $("#list1").DataTable({...dataTableOptions, aoColumns: aoColumns,});
	
	for (const [key] of Object.entries(tables)) {
		tables[key]["status"] = (window.innerWidth > 805) ? "desktop" : "mobile";
		if(tables[key]["status"] == "mobile"){
			tables[key].page.len(100).draw();
		}
	}
}

msgs = {arr: [],};

statbate();
showStat();

console.log('Debug https://statbate.com/debug.php');
console.log('Statbate is open source project (https://github.com/statbate)');

$(document).ready(function () {
	createTables();
	$('[data-toggle="tooltip"]').tooltip();
});
