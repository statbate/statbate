$(function () {
	$('[data-toggle="tooltip"]').tooltip()


	$.extend( $.fn.DataTable.ext.classes, {
		sWrapper: "dataTables_wrapper dt-bootstrap4",
		// sFilter: "",
		sLength: isTabletOrDesktop ? "dataTables_length":'',
	} );
})
let isTabletOrDesktop = screen.width >= 568;
if(navigator.userAgent.toLocaleLowerCase().indexOf('iphone') !== -1) {
	isTabletOrDesktop = false
}
console.log('isTabletOrDesktop', isTabletOrDesktop, screen)
let dataTableOptions = {
	bAutoWidth: false,
	oLanguage: {
		sLengthMenu: isTabletOrDesktop ? "Show _MENU_ entries":"_MENU_",
		sSearch: "",
		sSearchPlaceholder: "Search",
	},
	bInfo: isTabletOrDesktop,
	paging: isTabletOrDesktop,
	pagingType: isTabletOrDesktop ? 'simple_numbers':'numbers',
	iDisplayLength: isTabletOrDesktop ? 10:100,
	order: [[5, "desc"]],

	dom: isTabletOrDesktop ? "<'row'<'col-6'l><'col-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5 d-none d-sm-block'i><'col-sm-12 col-md-7 col-12'p>>":"<'row'<'col-12'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5 d-none d-sm-block'i><'col-sm-12 col-md-7 col-12'p>>"
};

$(document).ready(function() {
	$('.select-tab').on('shown.bs.tab', 'a', function(e) {
		console.log(e.target);
		$('.select-tab .dropdown-toggle').text($(this).text());
		if (e.target) {
			$(e.target).removeClass('active');
		}
	})

    var table = $("#main").DataTable({
		...dataTableOptions,
		aoColumns: [
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
		...dataTableOptions,
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
		...dataTableOptions,
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
		...dataTableOptions,
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
		...dataTableOptions,
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
			$("#allIncome").html(isTabletOrDesktop ? '<hr />':'');
			$("#allIncome").append("<center><b>All time "+type+": "+data.amount+" USD</b></center><hr/>");
		}

		if(data.chart.length != 0 && isTabletOrDesktop){
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

	let message = text;
	if(isTabletOrDesktop) {
		message = '[' + time + '] ' + message;
	}

	$(".wstext").prepend(`<div class="message">${message}</div>`);
	msg = $('.wstext .message');
	if (msg.length > 8) {
		msg.last().remove();
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
        $(".wstext").prepend('<div class="message text-center">----------------------------------- last big tips -----------------------------------</div>');
    };
    sock.onmessage = function(evt) {
		j = JSON.parse(evt.data);
		if(j.count){
			$(".trackCount").text("track "+j.count+" rooms");
			return;
		}
        text = "<a href='https://statbate.com/l/"+j.donator+"' rel='nofollow' target='_blank'>"+j.donator+"</a> send "+j.amount+" tokens to <a href='https://statbate.com/l/"+j.room+"' rel='nofollow' target='_blank'>"+j.room+"</a>";
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
