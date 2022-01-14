$(function () {
	$('[data-toggle="tooltip"]').tooltip()
})

$(document).ready(function() {

	$('.select-tab').on('shown.bs.tab', 'a', function(e) {
		console.log(e.target);
		$('.select-tab .dropdown-toggle').text($(this).text());
		if (e.target) {
			$(e.target).removeClass('active');
		}
	})

    var table = $("#main").DataTable({
		"bAutoWidth": false,
		"iDisplayLength": 10,
		order: [[5, "desc"]],
		"aoColumns": [
			{"orderable": false, "searchable": false,  "sWidth": "5%" },
			{ "orderable": false, "sWidth": "35%" },
			{ "orderable": false, "searchable": false, "sWidth": "15%" },
			{ "orderable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
		],
	});
});

$(document).ready(function() {
	var table = $("#couple_table").DataTable({
		"bAutoWidth": false,
		"iDisplayLength": 10,
		order: [[5, "desc"]],
		"aoColumns": [
			{"orderable": false, "searchable": false,  "sWidth": "5%" },
			{ "orderable": false, "sWidth": "35%" },
			{ "orderable": false, "searchable": false, "sWidth": "15%" },
			{ "orderable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
		],
	});
});

$(document).ready(function() {
	var table = $("#boys_table").DataTable({
		"bAutoWidth": false,
		"iDisplayLength": 10,
		order: [[5, "desc"]],
		"aoColumns": [
			{"orderable": false, "searchable": false,  "sWidth": "5%" },
			{ "orderable": false, "sWidth": "35%" },
			{ "orderable": false, "searchable": false, "sWidth": "15%" },
			{ "orderable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
		],
	});
});

$(document).ready(function() {
	var table = $("#trans_table").DataTable({
		"bAutoWidth": false,
		"iDisplayLength": 10,
		order: [[5, "desc"]],
		"aoColumns": [
			{"orderable": false, "searchable": false,  "sWidth": "5%" },
			{ "orderable": false, "sWidth": "35%" },
			{ "orderable": false, "searchable": false, "sWidth": "15%" },
			{ "orderable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
		],
	});
});

$(document).ready(function() {
    var table = $("#top100dons").DataTable({
		"bAutoWidth": false,
		"iDisplayLength": 10,
		order: [[5, "desc"]],
		"aoColumns": [
			{ "orderable": false, "searchable": false, "sWidth": "5%" },
			{ "orderable": false, "sWidth": "35%" },
			{ "orderable": false, "searchable": false,"sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
			{ "searchable": false, "sWidth": "15%" },
		],
	});
});

$(document).on("click", "[data-modal-info]", function(e) {
	$(this).blur();
	e.preventDefault();

	var id = $(this).data('modal-id');
	var name = $(this).data('modal-name');
	var type = $(this).data('modal-type');

	$.post("/public/info.php", {'type': type, 'id': id}, function(json){
		data = JSON.parse(json);
		if(data.table.length != 0){
			$("#donRoomTable tr:first th:first").html(name);
			$("#donRoomTable tbody").html(data.table);
			$('#donRoomModal').modal('show');
		}

		if(data.amount.length != 0){
			$("#allIncome").html("<hr/><center><b>All time "+type+": "+data.amount+" USD</b></center><hr/>");
		}

		if(data.chart.length != 0){
			xx11 = JSON.parse(data.chart);
			//if(xx11.length > 28){
			//	xx11.pop();
			//}
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
				//color: ['#25639a'],
				x_axis: false,
			});
		}

	});
});

function printWsText(text){
	if(text.length == 0){
		return;
	}
	date = new Date();
	xMin = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes()
	xSec = (date.getSeconds() < 10 ? '0' : '') + date.getSeconds()
	time = date.getHours() + ":" + xMin + ":" + xSec;
	$(".wstext").append('<div class="message">[' + time + '] ' + text + '</div>');
	msg = $('.wstext .message');
	if (msg.length > 8) {
		msg.first().remove();
	}
}

function bStat() {
    var sock = new WebSocket('wss://statbate.com/ws/');
    sock.onopen = function() {
        console.log('open');
        setTimeout(function wsPing() {
            sock.send('h')
            setTimeout(wsPing, 60000);
        }, 60000);
        $(".wstext").prepend('<div class="message text-center">------------------------------------ last big tips ------------------------------------</div>');
    };
    sock.onmessage = function(evt) {
		j = JSON.parse(evt.data);
		if(j.count){
			$("#trackCount").text("track "+j.count+" rooms");
			return;
		}
        text = "<a href='https://chaturbate.com/"+j.donator+"' rel='nofollow' target='_blank'>"+j.donator+"</a> send "+j.amount+" tokens to <a href='https://chaturbate.com/"+j.room+"' rel='nofollow' target='_blank'>"+j.room+"</a>";
        if(j.amount > 499){
			text = '<font color="#ae8d0b"><b>' +  text + '</b></font>';
		}
		printWsText(text);
    };
    sock.onclose = function(e) {
		console.log('Socket is closed. Reconnect will be attempted in 1 second.', e.reason);
		setTimeout(function() {
			bStat();
		}, 1000);
    };
}
bStat();
