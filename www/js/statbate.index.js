		Highcharts.chart('pieStat', {
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: 'pie'
			},
			credits: {
				enabled: false
			},
			accessibility: {
				enabled: false
			},
			title: {
				text: screen.width >= 568 ? 'Income distribution for the current month' : 'Income distribution'
			},
			tooltip: {
				pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			},
			plotOptions: {
				pie: {
					colors: ["#434348", "#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"],
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: true,
						format: screen.width >= 568 ? '<b>{point.name}</b>: {point.percentage:.1f} %' : '{point.percentage:.1f} %'
					},
					showInLegend: screen.width < 568
				}
			},
			legend: {
				itemDistance: 10
			},
			series: [{
				name: 'Income',
				colorByPoint: true,
				data: pieStat,
			}]
		});

		Highcharts.chart('pieRooms', {
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: 'pie'
			},
			accessibility: {
				enabled: false
			},
			credits: {
				enabled: false
			},
			title: {
				text: 'Rooms distribution'
			},
			tooltip: {
				pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			},
			plotOptions: {
				pie: {
					colors: ["#434348", "#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"],
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: true,
						format: screen.width >= 568 ? '<b>{point.name}</b>: {point.percentage:.1f} %' : '{point.percentage:.1f} %'
					},
					showInLegend: screen.width < 568
				}
			},
			legend: {
				itemDistance: 10
			},
			series: [{
				nodeWidth: 20,
				name: 'Income',
				colorByPoint: true,
				 data: pieRooms,
			}]
		});

		Highcharts.chart('pieViewers', {
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: 'pie'
			},
			accessibility: {
				enabled: false
			},
			credits: {
				enabled: false
			},
			title: {
				text: 'Viewers distribution'
			},
			tooltip: {
				pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			},
			plotOptions: {
				pie: {
					colors: ["#434348", "#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"],
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: true,
						format: screen.width >= 568 ? '<b>{point.name}</b>: {point.percentage:.1f} %' : '{point.percentage:.1f} %'
					},
					showInLegend: screen.width < 568
				}
			},
			legend: {
				itemDistance: 10
			},
			series: [{
				name: 'Income',
				colorByPoint: true,
				data: pieViewers,
			}]
		});

		function getPointCategoryName(point, dimension) {
			var series = point.series,
				isY = dimension === 'y',
				axis = series[isY ? 'yAxis' : 'xAxis'];
			return axis.categories[point[isY ? 'y' : 'x']];
		}

		Highcharts.chart('container-map', {
			chart: {
				type: 'heatmap',
				/* marginTop: 6, */
				/* marginBottom: 20, */
				plotBorderWidth: 1
			},
			credits: {
				enabled: false
			},
			accessibility: {
				enabled: false
			},
			title: {
				text: ''
			},
			xAxis: {
				categories: ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
			},
			yAxis: {
				categories: ['3', '6', '9', '12', '15', '18', '21', '24'],
				title: null,
				reversed: true
			},
			colors : ['#bebebe'],
			colorAxis: {
				min: statbateConf.heatmap,
				minColor: '#FFFFFF',
				maxColor: '#F8B4A6',
			},

			legend: {
				align: 'right',
				layout: 'vertical',
				margin: 0,
				verticalAlign: 'top',
				y: 25,
				x: 15,
				symbolHeight: 320
			},
			tooltip: {
				formatter: function () {
					return '<b>' +
						this.point.value + 'K USD</b> ';
				}
			},
			series: [{
				name: 'Income in two hours',
				borderWidth: 1,
				data: heatMap,
				dataLabels: {
					enabled: true,
					color: '#000000'
				}
			}],
			responsive: {
				rules: [{
					condition: {
						maxWidth: 500,
						minHeight: 500
					},
					chartOptions: {
						yAxis: {
							labels: {
								formatter: function () {
									return this.value.charAt(0);
								}
							}
						}
					}
				}]
			}
		});

		$('desc').text('');
