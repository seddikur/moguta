var settings_1c = (function () {
	return {
		init: function() {
			$('#1C-settings').on('change', '#qqvariantToSize1c', function() { 
				if($('#qqvariantToSize1c').prop('checked')) {
					$('input[name="colorName1c"]').closest('.row').show();
					$('input[name="sizeName1c"]').closest('.row').show();
				} else {
					$('input[name="colorName1c"]').closest('.row').hide();
					$('input[name="sizeName1c"]').closest('.row').hide();
				}
			});
			$('#1C-settings #qqvariantToSize1c').trigger('change');

			/////////////////// Логи ///////////////////
			//Удаление файла
			$('#1C-settings').on('click', '.logerTable .drop', function() {
				admin.ajaxRequest({
					mguniqueurl: "action/dropLog",
					name: $(this).attr('log'),
				},
				function (response) {
					if(response.status == 'error'){
						admin.indication(response.status, response.msg);
					} else {
						$('.logerTable tbody').html(response.data);
						settings_1c.logSize();
					}
				});
			});

			//Удаление всего
			$('#1C-settings').on('click', '.delete-all.link', function(){
				if (confirm('Уверены, что хотите удалить все файлы?')) {
					admin.ajaxRequest({
						mguniqueurl: "action/deleteAllLogs",
						pluginHandler: settings_1c.pluginName
					},
					function(response){
						admin.refreshPanel();
					});
				}
			});
			
			//Открытие аккордиона
			$('#1C-settings').on('click', '.js-open-loger', function() {
				//Определяем размер логов
				settings_1c.logSize();
				//Получаем список дней, 
				// берем первый день и получаем его папки по времени
				//   и выводим таблицу
				admin.ajaxRequest({
					mguniqueurl: "action/getDays",
					pluginHandler: settings_1c.pluginName
				},
				function(response){
					if (response.status == 'error') {
						$('.logerTable tbody').html(response.data);
						$('.log-day').html('');
						$('.log-time').html('');
						$('.loger').hide();
					} else {
						$('.loger').show();
						var daysList = response.data;
						var days = '';
						for (const key in daysList) {
							if (daysList.hasOwnProperty(key)) {
								const element = daysList[key];
								days += '<div class="log-one day"><i class="fa fa-calendar" style="margin-right:5px"></i> '+element+'</div>';
							}
						}
						$('.log-day').html(days);
						var first = $('.log-one.day').first();
						first.addClass('active');
						settings_1c.getTimes(first.text());
					}
				});
			});

			//Кнопка обновить
			$('#1C-settings').on('click', '.js-refresh', function() {
				//Определяем размер логов
				settings_1c.logSize();
				//Получаем список дней, 
				// берем первый день и получаем его папки по времени
				//   и выводим таблицу
				admin.ajaxRequest({
					mguniqueurl: "action/getDays",
					pluginHandler: settings_1c.pluginName
				},
				function(response){
					if (response.status == 'error') {
						$('.logerTable tbody').html(response.data);
						$('.log-day').html('');
						$('.log-time').html('');
						$('.loger').hide();
					} else {
						$('.loger').show();
						var daysList = response.data;
						var days = '';
						for (const key in daysList) {
							if (daysList.hasOwnProperty(key)) {
								const element = daysList[key];
								days += '<div class="log-one day"><i class="fa fa-calendar" style="margin-right:5px"></i> '+element+'</div>';
							}
						}
						$('.log-day').html(days);
						var first = $('.log-one.day').first();
						first.addClass('active');
						settings_1c.getTimes(first.text());
					}
				});
			});

			//Клик по дню
			$('#1C-settings').on('click', '.log-one.day', function() {
				var day = $(this).text();
				settings_1c.getTimes(day);
				$('.log-one.day.active').removeClass('active');
				$(this).addClass('active');
			});

			//Клик по времени
			$('#1C-settings').on('click', '.log-one.time', function() {
				var time = $(this).text();
				var day = $(this).attr('data-day');
				settings_1c.drawTable(day, time);
				$('.log-one.time.active').removeClass('active');
				$(this).addClass('active');
			});

			//Переключение типа доп. поля
			$('#1C-settings').on('change', '.js-opfield-type', function() {
				var index = $(this).data('op');
				var wholesales = $('.commerceml-wholesales-settings-subgroup[data-op-whole="'+index+'"]');

				if (typeof(wholesales.data('op-whole')) == 'undefined') {
					return false;
				}

				if ($(this).val() == 'fromPrice') {
					wholesales.show();
				} else {
					wholesales.hide();
				}
			});
		},
		logSize: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/getSizeLogs",
				pluginHandler: settings_1c.pluginName
			},
			function(response){
				$('.log-size').text(response.data);
			});
		},
		getTimes: function(day) {
			admin.ajaxRequest({
				mguniqueurl: "action/getTimes",
				pluginHandler: settings_1c.pluginName,
				day: day
			},
			function(response){
				var timesList = response.data;
				var times = '';
				for (const key in timesList) {
					if (timesList.hasOwnProperty(key)) {
						const element = timesList[key];
						times += '<div class="log-one time" data-day="'+day+'"><i class="fa fa-clock-o" style="margin-right:5px"></i> '+element+'</div>';
					}
				}
				$('.log-time').html(times);
				var first = $('.log-one.time').first();
				first.addClass('active');
				settings_1c.drawTable(day, first.text());
			});
		},
		drawTable: function(day, time) {
			$('.logerTable').fadeOut(10);
			admin.ajaxRequest({
				mguniqueurl: "action/getFiles",
				day: day,
				time: time
			},
			function (response) {
				if(response.status == 'error'){
					admin.indication(response.status, response.msg);
				} else {
					$('.logerTable tbody').html(response.data);
					$('.logerTable').fadeIn(200);
				}
			});
		},
	};
})();