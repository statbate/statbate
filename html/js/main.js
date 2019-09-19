$(document).ready(function() {
    var table = $("#main").DataTable({
        order: [
            [5, "desc"]
        ],
        "iDisplayLength": 10,
        "columns": [{
                "searchable": false,
                "orderable": false
            },
            {
                "orderable": false
            },
            {
                "orderable": false
            },
            {
                "orderable": false
            },
            {
                "searchable": false
            },
            {
                "searchable": false
            },
        ]
    });
});

function bStat() {
    var sock = new WebSocket('wss://stat.poiuty.com/ws/');
    sock.onopen = function() {
        console.log('open');
        setTimeout(function wsPing() {
            sock.send('o')
            setTimeout(wsPing, 10000);
        }, 10000);
        $(".wstext").prepend('<center>------------------------------------ last big tips ------------------------------------</center>');
    };
    sock.onmessage = function(evt) {
        //console.log(evt.data);			
        var date = new Date();
        xMin = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes()
        xSec = (date.getSeconds() < 10 ? '0' : '') + date.getSeconds()
        var time = date.getHours() + ":" + xMin + ":" + xSec;
        $(".wstext").prepend('[' + time + '] ' + evt.data + '<br/>');
    };
    sock.onclose = function() {
        console.log('close');
    };
}
bStat();

$(document).ready(function() {
    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });
    Highcharts.chart('container', {
        navigation: {
            buttonOptions: {
                enabled: false
            }
        },
        credits: {
            enabled: false
        },
        chart: {
            zoomType: 'x'
        },
        title: {
            text: 'Chaturbate income'
        },
        subtitle: {
            text: document.ontouchstart === undefined ?
                'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'
        },
        xAxis: {
            type: 'datetime'
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Income'
            }
        },
        legend: {
            enabled: false
        },
        plotOptions: {
            area: {
                fillColor: {
                    linearGradient: {
                        x1: 0,
                        y1: 0,
                        x2: 0,
                        y2: 1
                    },
                    stops: [
                        [0, 'rgb(255, 255, 255)'],
                        [1, '#89AAC8']
                    ]
                },
                marker: {
                    radius: 2
                },
                lineWidth: 1,
                states: {
                    hover: {
                        lineWidth: 1
                    }
                },
                threshold: null
            }
        },
        series: [{
            type: 'area',
            name: 'USD',
            color: '#26639A',
            data: hcData
        }]
    });
    
    $( "#donTopLink" ).show();
});
