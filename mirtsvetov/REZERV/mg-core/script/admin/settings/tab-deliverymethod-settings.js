var settings_delivery = (function () {
	return {
		init: function() {
			settings_delivery.initEvents();
			settings.updataTabs();
			admin.sortable('.deliveryMethod-tbody','delivery');
		},
		initEvents: function() {
			// установка активности для способов доставки
			$('#tab-deliveryMethod-settings').on('click', '.activity', function() {    
				$(this).find('a').toggleClass('active');
				if($(this).attr('status') == 1) $(this).attr('status', 0); else $(this).attr('status', 1);
				settings_delivery.changeActivity($(this).attr('id'), $(this).find('a').hasClass('active'));
			});

			// смена языка
			$('#tab-deliveryMethod-settings').on('change','.select-lang', function() {
				id = $('#tab-deliveryMethod-settings .save-button').attr('id');
				if(id < 1) {
					type = 'add';
				} else {
					type = 'edit';
				}
				settings_delivery.openDeliveryModalWindow(type, id);
			});
			
			// Вызов модального окна при нажатии на кнопку добавления способа доставки.
			$('#tab-deliveryMethod-settings').on('click', '.add-new-button', function() {
				settings_delivery.openDeliveryModalWindow('add');
			});
					
			// Вызов модального окна при нажатии на кнопку изменения способа доставки.
			$('#tab-deliveryMethod-settings').on('click', '.edit-row', function() {
				settings_delivery.openDeliveryModalWindow('edit', $(this).attr('id'));
			});
			
			// Сохранение при нажатии на кнопку сохранить в модальном окне способа доставки.
			$('#tab-deliveryMethod-settings').on('click', '.save-button', function() {
				settings_delivery.saveDeliveryMethod($(this).attr('id'));
			});
			
			// Удаление способа доставки.
			$('#tab-deliveryMethod-settings').on('click', '.delete-row', function() {
				settings_delivery.deleteDelivery($(this).attr('id'));
			});

			$('#tab-deliveryMethod-settings').on('change', '#add-deliveryMethod-wrapper input[name=useIntervals]', function() {
				if ($(this).prop('checked')) {
					$('#add-deliveryMethod-wrapper .deliveryIntervals').show();
				} else {
					$('#add-deliveryMethod-wrapper .deliveryIntervals').hide();
				}
			});
			$('#tab-deliveryMethod-settings').on('change', '#add-deliveryMethod-wrapper input[name=deliveryDate]', function() {
				if ($(this).prop('checked')) {
					$('#add-deliveryMethod-wrapper #delivery_setting').show();
				} else {
					$('#add-deliveryMethod-wrapper #delivery_setting').hide();
				}
			});

			$('#tab-deliveryMethod-settings').on('click', '#add-deliveryMethod-wrapper .useWeight', function() {
				if ($(this).data('active') == 'fals') {
					$(this).data('active','tru').text(lang.DELIVERY_WEIGHT_INACTIVE);
					$('#add-deliveryMethod-wrapper .deliveryWeight').show();
				} else {
					$(this).data('active','fals').text(lang.DELIVERY_WEIGHT_ACTIVE);
					$('#add-deliveryMethod-wrapper .deliveryWeight').hide();
				}
			});

			$('#tab-deliveryMethod-settings').on('click', '#add-deliveryMethod-wrapper .addDeliveryWeight', function() {
				settings_delivery.addDeliveryWeightRow();
			});

			$('#tab-deliveryMethod-settings').on('click', '#add-deliveryMethod-wrapper .deliveryWeight .accordion-title', function() {
				if (!$('#add-deliveryMethod-wrapper .deliveryWeight [name=deliveryWeight]').length) {
					settings_delivery.addDeliveryWeightRow();
				}
			});

			$('#tab-deliveryMethod-settings').on('click', '#add-deliveryMethod-wrapper .addDeliveryInterval', function() {
				settings_delivery.addDeliveryIntervalRow();
			});

			$('#tab-deliveryMethod-settings').on('click', '#add-deliveryMethod-wrapper .deliveryIntervals .accordion-title', function() {
				if (!$('.section-settings #add-deliveryMethod-wrapper .deliveryIntervals [name=deliveryInterval]').length) {
					settings_delivery.addDeliveryIntervalRow();
				}
			});

			//Затираем ноль в бесплатной доставке
			$('#tab-deliveryMethod-settings').on('blur', '#add-deliveryMethod-wrapper input#free', function() {
				if ($(this).val() == 0) {
					$(this).val('');
				}
			});
		},
		/**
		 * Открывает модальное окно способа доставки.
		 * type - тип окна, либо для создания нового, либо для редактирования старого.
		 */
		openDeliveryModalWindow: function(type, id) {
			settings_delivery.clearFileds();   
			switch (type) {
				case 'edit': {
					$('#add-deliveryMethod-wrapper .delivery-table-icon').text(lang.TITLE_EDIT_DELIVERY);
					$('#add-deliveryMethod-wrapper .save-button').attr("id", id);
					var paymentMethod = $.parseJSON($('tr[id=delivery_'+id+'] td#paymentHideMethod').text());
					weights = '';
					if ($('tr[id=delivery_'+id+'] td#paymentHideMethod').data('weight')) {
						weights = $.parseJSON($('tr[id=delivery_'+id+'] td#paymentHideMethod').data('weight'));
					}
					intervals = '';
					if ($('tr[id=delivery_'+id+'] td#paymentHideMethod').data('interval')) {
						intervals = $('tr[id=delivery_'+id+'] td#paymentHideMethod').data('interval');
					}
                    $('textarea[name=deliveryDescriptionPublic]').val($('tr[id=delivery_'+id+'] td#deliveryDescriptionPublic').text());
                                        
					$('input[name=deliveryName]').val($('tr[id=delivery_'+id+'] td#deliveryName').text());
					$('input[name=deliveryCost]').val($('tr[id=delivery_'+id+'] td#deliveryCost span.costValue').text());
					$('input[name=deliveryDescription]').val($('tr[id=delivery_'+id+'] td#deliveryDescription').text());
					free = $('tr[id=delivery_'+id+'] td.free .costFree').text();
					$('input[name=free]').val(free);
					$('input#free').val(free);

					if (weights) {
						$('.section-settings #add-deliveryMethod-wrapper .useWeight').click();
						for (var i = 0; i < weights.length; i++) {
							settings_delivery.addDeliveryWeightRow();
							$('.section-settings #add-deliveryMethod-wrapper input[name=deliveryWeight]:last').val(weights[i].w);
							$('.section-settings #add-deliveryMethod-wrapper input[name=deliveryWeightPrice]:last').val(weights[i].p);
						}
					}

					if (intervals) {
						if (!$.isArray(intervals)) {
							intervals = intervals.replace('["',"").replace('"]',"").split('","');
						}
						$('.section-settings #add-deliveryMethod-wrapper [name=useIntervals]').click();
						for (var i = intervals.length-1; i >= 0 ; i--) {
							if (intervals[i] != '') {
								settings_delivery.addDeliveryIntervalRow();
								$('.section-settings #add-deliveryMethod-wrapper input[name=deliveryInterval]:first').val(admin.htmlspecialchars_decode(intervals[i]));
							}
						}
					}
					
					if(1 == $('tr[id=delivery_'+id+'] td .activity').attr('status')) {
						$('input[name=deliveryActivity]').prop('checked', true);
					}
					if(1 == $('tr[id=delivery_'+id+'] td .activity').data('delivery-date')) {
						$('input[name=deliveryDate]').prop('checked', true);
						$('#add-deliveryMethod-wrapper #delivery_setting').show();
					}else{
						$('#add-deliveryMethod-wrapper #delivery_setting').hide();
					}
					if(1 == $('tr[id=delivery_'+id+'] td .edit-row').data('delivery-use-storages')) {
						$('input[name=showStorages]').prop('checked', true);
					}else{
						$('input[name=showStorages]').prop('checked', false);
					}
					if('' != $('tr[id=delivery_'+id+'] td#paymentHideMethod').data('address_parts')) {
						$('input[name=useAddressParts]').prop('checked', true);
					}     
					let date_settings = $('tr[id=delivery_'+id+'] td#date_settings').text();
					// Вывод настроек для даты доставки
					if(date_settings !== ''){
						date_settings = JSON.parse(date_settings);
						// Настройки дня смещения доставки
						if(typeof date_settings.dateShift !== 'undefined'){
							$('input[name=dateShift]').val(date_settings.dateShift);
						}
						// Настройки дней недели доставки
						if(typeof date_settings.daysWeek == 'object'){
							for(key in date_settings.daysWeek){
								if(date_settings.daysWeek[key] == true){
									$('input[name='+key+']').prop('checked', true);
								}else{
									$('input[name='+key+']').prop('checked', false);
								}
							}
						}else{

						}
						// Настройки праздничных дней в месяце (по которым нет доставки)
						if(typeof date_settings.monthWeek == 'object'){
							for(key in date_settings.monthWeek){
								$('input[name='+key+']').val(date_settings.monthWeek[key]);
							}
						}else{

						}
						// Настройка обязательности валидации поля "дата доставки"
						if(typeof date_settings.requiredDate !== 'undefined'){
							if(date_settings.requiredDate == true){
								$('input[name=requiredDate]').prop('checked', true);
							}else{
								$('input[name=requiredDate]').prop('checked', false);
							}
						}
					}else{
						$('input[name=dateShift]').val('');
						settings_delivery.clearDeliverySettingsMonthDays();
						settings_delivery.clearDeliverySettingsWeekDays();
					}
					//выбор способов оплаты применительно к данному способу доставки
					$.each(paymentMethod, function(paymentId, active) {
						if(1 == active) {
							$('#add-deliveryMethod-wrapper #paymentCheckbox input[name='+paymentId+']').prop('checked', true);
						} else {
							$('#add-deliveryMethod-wrapper #paymentCheckbox input[name='+paymentId+']').prop('checked', false);
						}
					});
					settings_delivery.loadLocaleDelivery(id);
					break;
				}
				case 'add': {
					$('#add-deliveryMethod-wrapper .delivery-table-icon').text(lang.TITLE_NEW_DELIVERY);
					break;
				}
				default: {
					user.clearFileds();
					break;
				}
			}

			// Вызов модального окна.
			admin.openModal($('#add-deliveryMethod-wrapper'));
		},
		/**
		* сохранение способа доставки
		*/
		saveDeliveryMethod:function(id, closeModal) {
			closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
			// Если поля не верно заполнены, то не отправляем запрос на сервер.
			if(!settings_delivery.validForm()) {
				return false;
			}
			
			$('.img-loader').show();
			var status="createDelivery";
			//обрабатываем доступные методы оплаты для данного метода доставки
			var paymentMethod='{';
			
			$('#paymentCheckbox input').each(function() {
				
				if($(this).prop('checked')) {
					paymentMethod += '"'+$(this).attr('name')+'":1,';
				} else {
					paymentMethod += '"'+$(this).attr('name')+'":0,';
				}
			});
			
			paymentMethod = paymentMethod.substr(0, paymentMethod.length-1); //удаляем последнюю запятую в конце списка
			paymentMethod +='}';
			
			if(id) {
				status="editDelivery";
			}
			
			var deliveryName = $('input[name=deliveryName]').val();
            var deliveryDescriptionPublic = $('textarea[name=deliveryDescriptionPublic]').val();
			var deliveryCost = $('input[name=deliveryCost]').val();
			var deliveryDescription = $('input[name=deliveryDescription]').val();
			var free = $('input[name=free]').val();
			var requiredDate = 0;
			var deliveryActivity = 0;
			var deliveryDate = 0;
			var useAddressParts = 0;
			var showStorages = 0;
			var weights = [];
			var intervals = [];
			var dateShift = '';
			if($('input[name=deliveryActivity]').prop('checked')) {
				deliveryActivity = 1;
			}      
			if($('input[name=deliveryDate]').prop('checked')) {
				deliveryDate = 1;
				dateShift = $('input[name=dateShift]').val();
			}
			if($('input[name=showStorages]').prop('checked')) {
				showStorages = 1;
			}
			if($('input[name=useAddressParts]').prop('checked')) {
				useAddressParts= 1;
			}
			if($('input[name=requiredDate]').prop('checked')) {
				requiredDate = 1;
			}    
			let daysWeek = {}
			$('#daysWeek input').each(function(i){
				daysWeek[$(this).attr('id')] = $(this).prop('checked');
			});
			let monthWeek = {}
			$('#monthWeek input').each(function(i){
				monthWeek[$(this).attr('id')] = $(this).val().replace(/[^0-9,\,]/gm, '')
			});
			if ($('.section-settings #add-deliveryMethod-wrapper .useWeight').data('active') == 'tru') {
				$('#add-deliveryMethod-wrapper .weights').each(function(index,element) {
					var weight = $(this).find('input[name=deliveryWeight]').val();
					var weightPrice = $(this).find('input[name=deliveryWeightPrice]').val();
					if (weight != '' && weightPrice != ''){
						weights.push({'w':weight,'p':weightPrice});
					}
				});
			}

			if ($('.section-settings #add-deliveryMethod-wrapper [name=useIntervals]').prop('checked')) {
				$('#add-deliveryMethod-wrapper .intervals').each(function(index,element) {
					var interval = $(this).find('input[name=deliveryInterval]').val();
					if (interval != ''){
						intervals.push(admin.htmlspecialchars(interval));
					}
				});
			}
			admin.ajaxRequest({
				mguniqueurl: "action/saveDeliveryMethod",
				status: status,
				deliveryName: deliveryName,
                deliveryDescriptionPublic: deliveryDescriptionPublic,
				deliveryCost: deliveryCost,
				deliveryDescription: deliveryDescription,
				deliveryActivity: deliveryActivity,
				deliveryDate: deliveryDate,
				requiredDate: requiredDate,
				showStorages: showStorages,
				useAddressParts: useAddressParts,
				paymentMethod: paymentMethod,
				dateShift: dateShift,
				daysWeek: JSON.stringify(daysWeek),
				monthWeek: JSON.stringify(monthWeek),
				deliveryId: id,
				free: free,
				weight: weights,
				intervals: intervals,
				lang: $('.section-settings #tab-deliveryMethod-settings .select-lang').val()
			},
			function(response) {
				$('.img-loader').hide();
	
				admin.indication(response.status, response.msg);
				if('success' == response.status) {
					if (closeModal) {
						admin.refreshPanel();
						admin.closeModal($('#add-deliveryMethod-wrapper'));
					} else {
						$('#add-deliveryMethod-wrapper').attr('data-refresh', 'true');
					}
				} 
			});
		},
		/**
		 * Удаляет способ доставки из БД сайта и таблицы в текущем разделе
		 */
		deleteDelivery: function(id) {
			if(confirm(lang.DELETE+'?')) {
				admin.ajaxRequest({
					mguniqueurl:"action/deleteDeliveryMethod",
					id: id
				},
				(function(response) {
					admin.indication(response.status, response.msg);
					admin.refreshPanel();          
				 })
				);
			}
		},
		addDeliveryWeightRow: function() {
			var html = '<div class="row weights">\
					<div class="small-12 medium-6 columns">\
						<input placeholder="'+lang.DELIVERY_WEIGHT_W+'" type="text" name="deliveryWeight">\
					</div>\
					<div class="small-12 medium-6 columns">\
						<input placeholder="'+lang.DELIVERY_WEIGHT_P+'" type="text" name="deliveryWeightPrice">\
					</div>\
				</div>';
			$(html).insertBefore($('#add-deliveryMethod-wrapper .addDeliveryWeight').closest('.row'));
		},
		addDeliveryIntervalRow: function() {
			var html = '<div class="row intervals">\
					<div class="small-12 medium-12 columns">\
						<input placeholder="'+lang.DELIVERY_INTERVAL+'" type="text" name="deliveryInterval">\
					</div>\
				</div>';
			$(html).insertAfter($('#add-deliveryMethod-wrapper .interv-descr'));
		},
		loadLocaleDelivery: function(id) {
			$.ajax({
				type: "POST",
				url: mgBaseDir + "/ajax",
				data: {
					mguniqueurl: "action/loadLocaleDelivery",
					id: id,
					lang: $('.section-settings #tab-deliveryMethod-settings .select-lang').val(),
				},
				cache: false,
				// async: false,
				dataType: "json",
				success: function (response) {
					if(response.data.name != '')
						$('.section-settings #tab-deliveryMethod-settings [name=deliveryName]').val(response.data.name);
					if(response.data.description != '')
						$('.section-settings #tab-deliveryMethod-settings [name=deliveryDescription]').val(response.data.description);
				}
			});
		},
		validForm : function() {
			$('.errorField').css('display','none');
			$('input').removeClass('error-input');
			var error;
			var cost = $('input[name=deliveryCost]').val();
			cost = admin.numberDeFormat(cost); //отменяем форматирование цены
			if (cost == false) {
				cost = 0;
			}
			$('input[name=deliveryCost]').val(cost); //помещаем новое значение в поле ввода перед отправкой

			var free = $('input#free').val();
			free = admin.numberDeFormat(free); //отменяем форматирование цены
			if (free == false) {
				free = 0;
			}
			$('input[name=free]').val(free); //помещаем новое значение в поле ввода перед отправкой
			
			if('' == $('input[name=deliveryName]').val()) {
				$('input[name=deliveryName]').addClass('error-input');
				$('input[name=deliveryName]').parent("label").find('.errorField').css('display','block');
				error = true;
			}
			
			if('' == $('input[name=deliveryDescription]').val()) {
				$('input[name=deliveryDescription]').val($('input[name=deliveryName]').val());
			}
			
			// Проверка поля для бесплатной доставки, является ли текст в него введенный числом.    
			if(isNaN(parseFloat($('input[name=free]').val()))) {
				$('input[name=free]').addClass('error-input');
				$('input[name=free]').parent("div.input-with-text").find('.errorField').css('display','block');
				error = true;
			}
			
			if(error == true) {
				return false;
			}

			return true;
		},
		clearFileds:function() {
			$('#tab-deliveryMethod-settings #add-deliveryMethod-wrapper input[name=deliveryWeight]').remove();
			$('#tab-deliveryMethod-settings #add-deliveryMethod-wrapper input[name=deliveryWeightPrice]').remove();
			$('#tab-deliveryMethod-settings #add-deliveryMethod-wrapper input[name=deliveryInterval]').remove();
			$('#tab-deliveryMethod-settings #add-deliveryMethod-wrapper .useWeight').data('active','fals').text(lang.DELIVERY_WEIGHT_ACTIVE);
			$('#tab-deliveryMethod-settings .deliveryWeight').hide();
			$('#tab-deliveryMethod-settings .deliveryIntervals').hide();
			$('#tab-deliveryMethod-settings input').removeClass('error-input');
			$('#tab-deliveryMethod-settings .errorField').css('display','none');
			$('#tab-deliveryMethod-settings input[name=deliveryName]').val('');
			$('#tab-deliveryMethod-settings input[name=deliveryCost]').val('');
			$('#tab-deliveryMethod-settings input[name=free]').val('0');
			$('#tab-deliveryMethod-settings input[name=deliveryDescription]').val('');
			$('#tab-deliveryMethod-settings #add-deliveryMethod-wrapper input[name=useAddressParts]').prop('checked', false);
			$('#tab-deliveryMethod-settings #add-deliveryMethod-wrapper [name=useIntervals]').prop('checked', false);
			$('#tab-deliveryMethod-settings input[name=deliveryActivity]').prop('checked', false);
			$('#tab-deliveryMethod-settings input[name=deliveryDate]').prop('checked', false);
			$('#tab-deliveryMethod-settings .paymentMethod').prop('checked', false);
			$('#tab-deliveryMethod-settings .save-button').attr('id','');
			$('input[name=dateShift]').val('');
			settings_delivery.clearDeliverySettingsMonthDays();
			settings_delivery.clearDeliverySettingsWeekDays();
		},
		changeActivity: function(id, status) {
			if(status) status = 1; else status = 0;
			admin.ajaxRequest({
				mguniqueurl: "action/changeActivityDP",
				tab: 'delivery',
				id: id,
				status: status
			},
			function(response) {
				admin.indication(response.status, response.msg);
			});
		},
		//Очистка дней доставки по дням недели
		clearDeliverySettingsWeekDays:function(){
			$('input[name=md]').prop('checked', false);
			$('input[name=tu]').prop('checked', false);
			$('input[name=we]').prop('checked', false);
			$('input[name=thu]').prop('checked', false);
			$('input[name=fri]').prop('checked', false);
			$('input[name=sa]').prop('checked', false);
			$('input[name=su]').prop('checked', false);
		},
		//Очистка дней доставки по месяцам
		clearDeliverySettingsMonthDays:function(){
			$('input[name=jan]').val('');
			$('input[name=feb]').val('');
			$('input[name=mar]').val('');
			$('input[name=aip]').val('');
			$('input[name=may]').val('');
			$('input[name=jum]').val('');
			$('input[name=jul]').val('');
			$('input[name=aug]').val('');
			$('input[name=sep]').val('');
			$('input[name=okt]').val('');
			$('input[name=nov]').val('');
			$('input[name=dec]').val('');
		}
	};
})();