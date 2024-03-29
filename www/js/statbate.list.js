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

			yAxis: {
				stops: [
					[0.1, '#55BF3B'],
					[0.5, '#DDDF0D'],
					[0.9, '#DF5353']
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
				data: [containerActivity],
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

		$('desc').text('');
