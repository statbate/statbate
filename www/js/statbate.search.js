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
				data: lineIncome,
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
					data: containerIncome,					
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
				data: containerDons,
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
				data: lineTips,
				type: 'line',
				tooltip: {
					valueDecimals: 0
				}
			}]
		});

		$('desc').text('');
