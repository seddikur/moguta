var settings_payment = (function () {
	return {
		init: function() {
			settings_payment.initEvents();
			settings.updataTabs();
			admin.sortable('.paymentMethod-tbody','payment');
		},
		initEvents: function() {
			// установка активности для способов оплаты
			$('#tab-paymentMethod-settings').on('click', '.activity', function() {
				const plugin = $(this).data('plugin');
				const pluginCode = $(this).data('plugin-code');
				const pluginTitle = $(this).data('plugin-title');
				$(this).find('a').toggleClass('active');
				if($(this).attr('status') == 1) $(this).attr('status', 0); else $(this).attr('status', 1);
				settings_payment.changeActivity($(this).attr('id'), $(this).find('a').hasClass('active'), plugin, pluginCode, pluginTitle);
			});

			// Вызов модального окна при нажатии на кнопку изменения способа оплаты.
			$('#tab-paymentMethod-settings').on('click', '.edit-row', function() {
				admin.ajaxRequest({
					mguniqueurl: "action/loadPayment",
					id: $(this).parents('tr').data('id'),
					lang: $('#tab-paymentMethod-settings .select-lang').val()
				},
				function (response) {
					settings_payment.openPaymentModalWindow(response.data);
				});
			});

			$('#tab-paymentMethod-settings').on('change', '.select-lang', function() {
				admin.ajaxRequest({
					mguniqueurl: "action/loadPayment",
					id: $('#tab-paymentMethod-settings .save-button').attr('id'),
					lang: $('#tab-paymentMethod-settings .select-lang').val()
				},
				function (response) {
					settings_payment.openPaymentModalWindow(response.data);
				});
			});

			// Сохранение при нажатии на кнопку сохранить в модальном окне способа оплаты
			$('#tab-paymentMethod-settings').on('click', '.save-button', function() {
				const id = $(this).attr('id');
				if (!$(this).data('newpayment')) {
					return settings_payment.savePaymentMethodOld(id);
				}
				return settings_payment.savePaymentMethod(id);
			});

			// Очистка логов
			$('#tab-paymentMethod-settings').on('click', '.clearLogs', function() {
				const code = $('#tab-paymentMethod-settings .save-button').data('code');
				if (code) {
					admin.ajaxRequest(
						{
							mguniqueurl: 'action/clearPaymentLogs',
							code: code,
						},
						function(response) {
							admin.indication(response.status, response.msg);
							if (response.status === 'success') {
								$('#add-paymentMethod-wrapper .paymentLogs .logsActions').hide();
								$('tr[data-code="'+code+'"]').data('logs-exists', false);
							}
						}
					);
				}
			});

			$('#tab-paymentMethod-settings').on('click', '.downloadLogs', function() {
				const code = $('#tab-paymentMethod-settings .save-button').data('code');
				if (!code) {
					return false;
				}
				const url = admin.SITE + '/payment?downloadLogs=1&code='+code;
				window.open(url);
			});

			// Скачивание логов

			//Клик по ссылке для установки скидки/наценки способа оплаты
			$('#tab-paymentMethod-settings').on('click', '#add-paymentMethod-wrapper .discount-setup-rate', function() {
				$(this).hide();
				$('.discount-rate-control').show();
			});

			//Клик по отмене скидки/наценки 
			$('#tab-paymentMethod-settings').on('click', '#add-paymentMethod-wrapper .cancel-rate', function() {
				$('.discount-setup-rate').show();
				$('.discount-rate-control').hide();
				$('.discount-rate-control input[name=rate]').val(0);
			});

			// Клик по кнопке для смены скидки/наценки
			$('#tab-paymentMethod-settings').on('click', '#add-paymentMethod-wrapper .discount-change-rate', function() {
				$('.select-rate-block').show();
			});

			// Клик по кнопке для  отмены модалки смены скидки/наценки
			$('#tab-paymentMethod-settings').on('click', '#add-paymentMethod-wrapper .cancel-rate-dir', function() {
				$('.select-rate-block').hide();  
				if($('.rate-dir').text()=="+") {
					$('.select-rate-block select[name=change_rate_dir] option[value=up]').prop('selected','selected');
				}
				if($('.rate-dir').text()=="-") {
					$('.select-rate-block select[name=change_rate_dir] option[value=down]').prop('selected','selected');
				}
			});

			// Клик по кнопке для применения скидки/наценки
			$('#tab-paymentMethod-settings').on('click', '#add-paymentMethod-wrapper .apply-rate-dir', function() {
				$('.select-rate-block').hide();        
				if($('.select-rate-block select[name=change_rate_dir]').val()=='up') {
					settings_payment.setupDirRate(1);
				} else {
					settings_payment.setupDirRate(-1);
				}
			});

			$('#tab-paymentMethod-settings').on('click', '.createNewPaymentMethod', function() {
				if ($(this).data('newpayment')) {
					settings_payment.createPaymentModalOpenNew();
				} else {
					settings_payment.createPaymentModalOpen();
				}
			});

			$('#tab-paymentMethod-settings').on('click', '.deletePayment', function() {
				if(!confirm('Удалить способ оплаты?')) return false;
				settings_payment.deletePayment($(this).data('id'));
			});

			$('#tab-paymentMethod-settings').on('click', '.deletePluginPayment', function() {
				if (!confirm('Удалить способ оплаты?')) return false;
				const code = $(this).data('code');
				const pluginCode = $(this).data('plugin-code');
				if (!code) {
					return false;
				}
				settings_payment.deletePluginPayment(code, pluginCode);
			});

			$('#tab-paymentMethod-settings').on('click', '.remove-added-logo', function() {
				$(this).hide();
				$('#tab-paymentMethod-settings input[name="icon"]').val('');
				$('#tab-paymentMethod-settings .uploaded-img img').prop('src', '');
			});

			$('#tab-paymentMethod-settings').on('click', '.browseImageLogo', function() {
				admin.openUploader('settings_payment.getFile');
			});

			// Клик по кнопке установки плагина оплаты
			$('#tab-paymentMethod-settings').on('click', '.installPaymentPlugin', function() {
				const code = $(this).data('code');
				const folder = $(this).data('folder');
				const action = $(this).data('action');
				const title = $(this).data('title');
				if (!action || !code || !folder) {
					admin.indication('error', 'Не удалось активировать оплату. Непредвиденная ошибка.');
				}

				switch (action) {
					case 'rent':
						admin.ajaxRequest(
							{
								mguniqueurl:"action/mpEnablePlugin",
								code: code,
								pluginFolder: folder
							},
							function(response) {
								admin.indication(response.status, response.msg);
								if (response.status === 'success') {
									admin.refreshPanel();
								}
							}
						);
						break;
					case 'activate':
						admin.ajaxRequest(
							{
								mguniqueurl: 'action/activatePlugin',
								pluginTitle: title,
								pluginFolder: folder,
							},
							function(response) {
								admin.indication(response.status, response.msg);
								if (response.status === 'success') {
									admin.refreshPanel();
								}
							}
						);
						break;
					case 'download':
						admin.ajaxRequest(
							{
								mguniqueurl: 'action/mpInstallPlugin',
								code: code,
								trial: 'no',
							},
							function(response) {
								if (response.status !== 'success') {
									admin.indication(response.status, response.msg);
								} else {
									admin.ajaxRequest(
										{
											mguniqueurl: 'action/activatePlugin',
											pluginTitle: title,
											pluginFolder: folder,
										},
										function(response) {
											admin.indication(response.status, response.msg);
											if (response.status === 'success') {
												admin.refreshPanel();
											}
										}
									);
								}
							}
						);
						break;
					case 'marketplace':
						includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
						callback = marketplaceModule.init;
						admin.show('marketplace.php', 'adminpage', 'mpFilter=all&mpFilterName='+title+'&mpFilterType=all', callback);
						break;
					default: 
						admin.indication('error', 'Не удалось активировать оплату. Непредвиденная ошибка.');
						break;
				}
			});

			// проверка наличия обновлений для плагинов оплат
			$('#tab-paymentMethod-settings').on('click', '#checkPluginsUpdate', function() {
				includeJS(admin.SITE + '/mg-core/script/admin/plugins.js');
				plugin.checkPluginsUpdate(true);
			});

			// обновление плагинов оплат
			$('#tab-paymentMethod-settings').on('click', '.updatePlugin', function() {
				const pluginFolder = $(this).data('plugin');
				if (!pluginFolder) {
					return false;
				}
				includeJS(admin.SITE + '/mg-core/script/admin/plugins.js');
				plugin.updatePlugin(pluginFolder, true);
			});

			// обновление плагинов оплат
			$('#tab-paymentMethod-settings').on('click', '.show-mp-desc', function() {
				const code = $(this).data('code');
				if (code) {
					admin.ajaxRequest(
						{
							mguniqueurl: 'action/mpGetDescr',
							code
						},
						function (response) {
							if (response.data.description) {
								$('#tab-paymentMethod-settings #mp-descr-modal .reveal-body').html(response.data.description);
								$('#tab-paymentMethod-settings #add-paymentMethod-wrapper').addClass('reveal-double-left');
								$('#tab-paymentMethod-settings #mp-descr-modal').addClass('reveal-double-right');
								$('#tab-paymentMethod-settings #mp-descr-modal').show();
							} else {
								admin.indication('error', 'Инструкция не найдена');
							}
						}
					);
				}
			});

			$('#tab-paymentMethod-settings').on('click', '.closeDoubledModal', function() {
				$(this).parents('.reveal').hide();
				$('#tab-paymentMethod-settings .reveal-double-left,.reveal-double-right').removeClass('reveal-double-left reveal-double-right');
			});

		},
		getFile: function(file) {
			const dir = file.url;
			$('#tab-paymentMethod-settings input[name="icon"]').val(dir);
			$('#tab-paymentMethod-settings .uploaded-img img').prop('src', dir);
			if (!(dir.endsWith('/mg-admin/design/images/icons/cash.png'))) {
				$('#tab-paymentMethod-settings .remove-added-logo').show();
			}
		},
		/**
		 * Открывает модальное окно способа оплаты.
		 */
		openPaymentModalWindow: function(data) {  
			if (!data.code || data.code.startsWith('old#')) {
				return this.openPaymentModalWindowOld(data);
			} else {
				return this.openPaymentModalWindowNew(data);
			}
		},
		openPaymentModalWindowNew: function(data) {
			// Параметры оплаты
			const paramArray = JSON.parse($('tr[id=payment_'+data.id+'] td#paramHideArray').text());
			// Поддерживается ли логирование и включено ли оно и есть ли логи вообще
			const isLogsAvailable = $('tr[id=payment_'+data.id+']').data('logs-available'); // логирование доступно
			const isLogsEnabled = $('tr[id=payment_'+data.id+']').data('logs'); // логирование включено
			const isLogsExists = $('tr[id=payment_'+data.id+']').data('logs-exists'); // имеются логи
			//проверка ниличия сопособов доставки для данного метода      
			let deliveryMethod;
			if('' != $('tr[id=payment_'+data.id+'] td#deliveryHideMethod').text()) {
				deliveryMethod = JSON.parse($('tr[id=payment_'+data.id+'] td#deliveryHideMethod').text());
			}

			// Очищаем поля
			settings_payment.clearFileds();

			const pluginCode = $('tr[id=payment_'+data.id+']').data('plugin-code');
			// Переключение видимости кнопки инструкции
			$('#add-paymentMethod-wrapper .show-mp-desc').hide();
			if (pluginCode) {
				$('#add-paymentMethod-wrapper .show-mp-desc').data('code', pluginCode);
				$('#add-paymentMethod-wrapper .show-mp-desc').show();
			}

			$('#add-paymentMethod-wrapper .payment-table-icon').text(lang.TITLE_EDIT_PAYMENT);

			// Добавляем кнопке сохранения code оплаты
			$('#add-paymentMethod-wrapper .save-button').data("code", data.code);
			$('#add-paymentMethod-wrapper .save-button').attr("id", data.id);

			// Нстройки имени оплаты
			$('#add-paymentMethod-wrapper span#paymentName').html('<span><input class="name-payment" name="name" type="text" value="'+data.name+'">'+'</span>');
			$('#add-paymentMethod-wrapper span#paymentPublicName').html('<span><input class="public-name-payment" name="publicName" type="text" value="'+data['public_name']+'">'+'</span>');

			// Отрисовка ссылок для оплаты
			if('' != $('tr[id=payment_'+data.id+'] td#urlArray').text()) {
				const urlArray = $.parseJSON($('tr[id=payment_'+data.id+'] td#urlArray').text());
				if (urlArray.length) {
					$('#add-paymentMethod-wrapper #urlParam').show();
					let urlParam = '<div class="custom-text links-text" style="margin-bottom:7px;"><strong>'+lang.LINKS_SERVICE+''+$('tr[id=payment_'+data.id+'] td#paymentName').text()+':</strong></div>';
					for (linkIndex in urlArray) {
						const link = urlArray[linkIndex];
						urlParam += `
							<p class="alert-block ${link.type}">
								<span>${link.title}:</span><span> ${mgBaseDir + link.link}</span>
							</p>
						`;
					}
					$('#add-paymentMethod-wrapper #urlParam').html(urlParam);
				} else {
					$('#add-paymentMethod-wrapper #urlParam').hide();
				}
			}

			// Отрисовка настроек оплаты
			$('#paymentParam').html('');
			if (paramArray.length) {
				// Отрисовка настроек оплаты
				let paramsHtml = '';
				for (let paramKey in paramArray) {
					let optionsHtml = '';
					paramsHtml += '<div class="row">';
					const param = paramArray[paramKey];
					let tipHtml = '';
					if (param.tip) {
						tipHtml = `
							<a class="tool-tip-right desc-property fl-right" href="javascript:void(0);" tooltip="${param.tip}" flow="up">
								<i class="fa fa-question-circle tip"></i>
							</a>
						`;
					}
					switch (param.type) {
						case 'text':
							paramsHtml += `
								<div class="small-5 columns">
									<label class="middle">${param.title} ${tipHtml}</label>
								</div>
								<div class="small-7 columns">
									<input type="text" name="${param.name}" class="product-name-input" value="${param.value}">
								</div>
							`;
							break;
						case 'crypt':
							paramsHtml += `
								<div class="small-5 columns">
									<label class="middle">${param.title} ${tipHtml}</label>
								</div>
								<div class="small-7 columns">
									<div class="relative js-show-pass-container">
										<button class="show-pass js-show-pass"><i class="fa fa-eye"></i></button>
										<input type="password" name="${param.name}" class="product-name-input" value="${param.value}">
									</div>
								</div>
							`;
							break;
						case 'select':
							optionsHtml = '';
							for (let optionKey in param.options) {
								const option = param.options[optionKey];
								let selected = '';
								if (option.value.toString() === param.value.toString()) {
									selected = ' selected="selected"';
								}
								optionsHtml += `<option value="${option.value}"${selected}>${option.title}</option>`;
							}
							paramsHtml += `
								<div class="small-5 columns">
									<label class="middle">${param.title} ${tipHtml}</label>
								</div>
								<div class="small-7 columns">
									<select name="${param.name}">
										${optionsHtml}
									</select>
								</div>
							`;
							break;
						case 'multiselect':
							optionsHtml = '';
							for (let optionKey in param.options) {
								const option = param.options[optionKey];
								let selected = '';
								if (param.values.includes(option.value)) {
									selected = ' selected="selected"';
								}
								optionsHtml += `<option value="${option.value}"${selected}>${option.title}</option>`;
							}
							paramsHtml += `
								<div class="small-5 columns">
									<label class="middle">${param.title} ${tipHtml}</label>
								</div>
								<div class="small-7 columns">
									<select name="${param.name}" multiple>
										${optionsHtml}
									</select>
								</div>
							`;
							break;
						case 'checkbox':
							let checked = '';
							if (param.value) {
								checked = ' checked="checked"';
							}
							paramsHtml += `
								<div class="small-5 columns">
									<label class="middle">${param.title} ${tipHtml}</label>
								</div>
								<div class="small-7 columns">
									<div class="checkbox margin">
										<input id="${param.name}" type="checkbox" name="${param.name}" value="${param.value ? 'true' : 'false'}" ${checked}>
										<label for="${param.name}"></label>
									</div>
								</div>
							`;
							break;
						default:
							paramsHtml += 'Неизвестный тип настройки'
							break;
					}
					paramsHtml += '</div>';
				}
				$('#paymentParam').html(paramsHtml);
			}

			// Отрисовка блока выбора иконки
			const hideRemoveIcon = data.icon ? '' : ' style="display: none";';
			const iconHtml = `
				<div class="row">
					<div class="small-5 columns">
						<div class="uploaded-img logo-img squareImg" style="min-width: 50px; max-width:100px;">
							<img src="${mgBaseDir + data.icon}">
							<a class="fa fa-times tip remove-added-logo" href="javascript:void(0);" title="Убрать иконку" data-id="${data.id}"${hideRemoveIcon}></a>
						</div>
						<input type="hidden" name="icon" class="settings-input option" value="${data.icon}">
					</div>
					<div class="small-7 columns">
						<a role="button" href="javascript:void(0);" class="link add-logo browseImageLogo button--noindent">
							<i class="fa fa-upload" aria-hidden="true"></i>
							<span>Выбрать иконку</span>
						</a>
					</div>
				</div>
			`;
			$('#paymentIcon').html(iconHtml);

			//ниличие сопобов доставки для данного метода
			if(!$.isEmptyObject(deliveryMethod)) {
				//выбор способов доставки применительно к данному способу оплаты
				$.each(deliveryMethod, function(deliveryId, active) {
					if(1 == active) {
						$('#add-paymentMethod-wrapper #deliveryCheckbox input[name='+deliveryId+']').prop('checked', true);
					} else {
						$('#add-paymentMethod-wrapper #deliveryCheckbox input[name='+deliveryId+']').prop('checked', false);
					}
				});
			}

			//выбор активности данного способа оплаты
			if(1 == $('tr[id=payment_'+data.id+'] td .activity').attr('status')) {
				$('input[name=paymentActivity]').prop('checked', true);
			}

			// Отображение опций логирования
			if (isLogsAvailable) {
				$('#add-paymentMethod-wrapper .paymentLogs').show();
				if (isLogsEnabled) {
					$('#add-paymentMethod-wrapper .paymentLogs input').prop('checked', true);
				}
				if (isLogsExists) {
					$('#add-paymentMethod-wrapper .paymentLogs .logsActions').show();
				}
			} else {
				$('#add-paymentMethod-wrapper .paymentLogs').hide();
				$('#add-paymentMethod-wrapper .paymentLogs .logsActions').hide();
				$('#add-paymentMethod-wrapper .paymentLogs input').prop('checked', false);
			}
			
			var rate = $('tr[id=payment_'+data.id+'] td#paymentRate').text();      
			$('.discount-rate-control input[name=rate]').val(rate*100);
			if(rate != 0) {
				$('.discount-setup-rate').hide();
				$('.discount-rate-control').show();
				settings_payment.setupDirRate(rate);  
			}

			// Добавляем настройку типа оплаты (физ, юр)
			$('#add-paymentMethod-wrapper #paymentParam').append(
				'<div class="row">\
					<div class="small-5 columns">\
						<label class="middle">'+lang.PAYMENT_PERMISSION+':</label>\
					</div>\
					<div class="small-7 columns">\
						<select class="medium permission">\
							<option value="all">'+lang.PAYMENT_ALL+'</option>\
							<option value="fiz">'+lang.PAYMENT_FIZ+'</option>\
							<option value="yur">'+lang.PAYMENT_YUR+'</option>\
						</select>\
					</div>\
				</div>'
			);
			$('#add-paymentMethod-wrapper .permission').val(data.permission);

			// Вызов модального окна.
			$('#mp-descr-modal').hide();
			$('#tab-paymentMethod-settings .reveal-double-left,.reveal-double-right').removeClass('reveal-double-left reveal-double-right');
			admin.openModal('#add-paymentMethod-wrapper');
			$('#add-paymentMethod-wrapper').parents('.reveal-overlay').css('display', 'flex');
		},
		openPaymentModalWindowOld: function(data) {
			var paramArray = JSON.parse($('tr[id=payment_'+data.id+'] td#paramHideArray').text());     
			//проверка ниличия сопособов доставки для данного метода      
			if('' != $('tr[id=payment_'+data.id+'] td#deliveryHideMethod').text()) {
				var deliveryMethod = $.parseJSON($('tr[id=payment_'+data.id+'] td#deliveryHideMethod').text());
			}

			settings_payment.clearFileds();
			$('#add-paymentMethod-wrapper .payment-table-icon').text(lang.TITLE_EDIT_PAYMENT);
			$('#add-paymentMethod-wrapper .save-button').attr("id", data.id);
			//подстановка классов иконок
			switch (data.id) {
				case "1":
					var iconClass = 'wm_icon';
					break;
				case "2":
					var iconClass = 'ym_icon';
					break;
				case "5":
					var iconClass = 'robo_icon';
					break;
				case "6":
					var iconClass = 'qiwi_icon';
					break;
				case "8":
					var iconClass = 'sci_icon';
					break;
				case "9":
					var iconClass = 'payanyway_icon';
					break;
				case "10":
					var iconClass = 'paymenmaster_icon';
					break;
				case "11":
					var iconClass = 'alfabank_icon';
					break;      
				default:
					var iconClass = 'default_icon';
			}
			$('#add-paymentMethod-wrapper span#paymentName').html('<span class="'+iconClass+'">'+'<input class="name-payment" name="name" type="text" value="'+data.name+'">'+'</span>');
			
			if('' != $('tr[id=payment_'+data.id+'] td#urlArray').text()) {
				var urlArray = $.parseJSON($('tr[id=payment_'+data.id+'] td#urlArray').text());
				var urlParam = '<div class="custom-text links-text" style="margin-bottom:7px;"><strong>'+lang.LINKS_SERVICE+''+$('tr[id=payment_'+data.id+'] td#paymentName').text()+':</strong></div>';
				var k=1;
				$.each(urlArray, function(name, val) {
					if(k==1) {urlParam += '<p class="alert-block warning">'}
					if(k==2) {urlParam += '<p class="alert-block success">'}
					if(k==3) {urlParam += '<p class="alert-block alert">'}
					if(k==4) {urlParam += '<p class="alert-block refund">'}
					urlParam += '<span>'+name+'</span>\
											'+admin.SITE+val+'\
										</p>';
					k++;
				});
				$('#add-paymentMethod-wrapper #urlParam').html(urlParam);
			}
			//создание списка изменения параметров для данного способа оплаты
			var input = '';
			var algorithm = new Array('md5', 'sha256', 'sha1');
				$('#add-paymentMethod-wrapper #paymentParam').html('');
			var yandexNDS = new Array('без НДС', '0%', '10%','20%');

			if(data.id == 23) {
				$('#paymentParam').html('<div class="alert-block info credit-info">\
					 Данный способ оплаты является дополнительным для способа оплаты "Ю.Касса".\
					 Задать настройки необходимо в редактировании способа оплаты "Ю.Касса"<br>\
					 Чтобы сделать доступным данный способ оплаты, вам так же необходимо иметь договор с "Ю.Кассой"</div>');
			} else {
				$('.credit-info').detach();
			}
			
			var comepayPayattributs = [];
				comepayPayattributs[1] ='Полная предварительная оплата до момента передачи предмета расчёта';
				comepayPayattributs[2] ='Частичная предварительная оплата до момента передачи предмета расчёта';
				comepayPayattributs[3] ='Аванс';
				comepayPayattributs[4] ='Полная оплата, в том числе с учётом аванса (предварительной оплаты) в момент передачи предмета расчёта';
				comepayPayattributs[5] ='Частичная оплата предмета расчёта в момент его передачи с последующей оплатой в кредит';
				comepayPayattributs[6] ='Передача предмета расчёта без его оплаты в момент его передачи с последующей оплатой в кредит';
				comepayPayattributs[7] ='Оплата предмета расчёта после его передачи с оплатой в кредит (оплата кредита). Этот признак должен быть единственным в документе и документ с этим признаком может содержать только одну строку';

			var comepayVats = [];
				comepayVats[1] = 'НДС не облагается';
				comepayVats[2] = 'НДС 10%';
				comepayVats[3] = 'НДС 20%';

			var cloudpaymentsSCHEME = {
				'charge': 'Одностадийная',
				'auth': 'Двухстадийная',
			};
		
				var cloudpaymentsSKIN = {
				'classic': 'Classic',
				'modern': 'Modern',
				'mini': 'Mini',
			};

			var cloudpaymentsTS = {
				'ts_0': 'Общая система налогообложения',
				'ts_1': 'Упрощенная система налогообложения (Доход)',
				'ts_2': 'Упрощенная система налогообложения (Доход минус Расход)',
				'ts_3': 'Единый налог на вмененный доход',
				'ts_4': 'Единый сельскохозяйственный налог',
				'ts_5': 'Патентная система налогообложения',
			};

			var cloudpaymentsVat = {
				'vat_none': 'НДС не облагается',
				'vat_0': 'НДС 0%',
				'vat_10': 'НДС 10%',
				'vat_20': 'НДС 20%',
				'vat_110': 'Расчетный НДС 10/110',
				'vat_120': 'Расчетный НДС 20/120',
			};

			var cloudpaymentsLang = {
				'ru-RU': 'Русский (MSK)',
				'en-US': 'Английский (CET)',
				'lv': 'Латышский (CET)',
				'az': 'Азербайджанский (AZT)',
				'kk': 'Русский (ALMT)',
				'kk-KZ': 'Казахский (ALMT)',
				'uk': 'Украинский (EET)',
				'pl': 'Польский (CET)',
				'pt': 'Португальский (CET)'
			};
			var cloudpaymentsMethod = {
				'0': 'Неизвестный способ расчета',
				'1': 'Предоплата 100%',
				'2': 'Предоплата',
				'3': 'Аванс',
				'4': 'Полный расчёт',
				'5': 'Частичный расчёт и кредит',
				'6': 'Передача в кредит',
				'7': 'Оплата кредита'
			};
			var cloudpaymentsObject = {
				'0': 'Неизвестный предмет оплаты',
				'1': 'Товар',
				'2': 'Подакцизный товар',
				'3': 'Работа',
				'4': 'Услуга',
				'5': 'Ставка азартной игры',
				'6': 'Выигрыш азартной игры',
				'7': 'Лотерейный билет',
				'8': 'Выигрыш лотереи',
				'9': 'Предоставление РИД',
				'10': 'Платеж',
				'11': 'Агентское вознаграждение',
				'12': 'Составной предмет расчета',
				'13': 'Иной предмет расчета',
			};
            
           var cloudpaymentsStatus = {
				'0': 'Не подтвержден',
				'1': 'Ожидает оплаты',
				'2': 'Оплачен',
				'3': 'В доставке',
				'4': 'Отменен',
				'5': 'Выполнен',
				'6': 'В обработке'
			};
            
			var sberNDS = [];
				sberNDS[0] = 'без НДС';
				sberNDS[1] = 'НДС по ставке 0%';
				sberNDS[2] = 'НДС чека по ставке 10%';
				sberNDS[3] = 'НДС чека по ставке 20%';
				sberNDS[4] = 'НДС чека по расчетной ставке 10/110';
				sberNDS[5] = 'НДС чека по расчетной ставке 18/118';

			var sberTaxSystem = [];
				sberTaxSystem[0] = 'общая';
				sberTaxSystem[1] = 'упрощённая, доход';
				sberTaxSystem[2] = 'упрощённая, доход минус расход';
				sberTaxSystem[3] = 'единый налог на вменённый доход';
				sberTaxSystem[4] = 'единый сельскохозяйственный налог';
				sberTaxSystem[5] = 'патентная система налогообложения';

			var intellectmoneyTax = [];
				intellectmoneyTax[1] = 'ставка НДС 20%';
				intellectmoneyTax[2] = 'ставка НДС 10%';
				intellectmoneyTax[3] = 'ставка НДС расч. 20/120';
				intellectmoneyTax[4] = 'ставка НДС расч. 10/110';
				intellectmoneyTax[5] = 'ставка НДС 0%';
				intellectmoneyTax[6] = 'НДС не облагается';

			var tinkoffTaxSystem = {
				'osn':'Общая СН',
				'usn_income':'Упрощенная СН (доходы)',
				'usn_income_outcome':'Упрощенная СН (доходы минус расходы)',
				'envd':'Единый налог на вмененный доход',
				'esn':'Единый сельскохозяйственный налог',
				'patent':'Патентная СН',
			};

			var tinkoffNDS = {
				'none':'Без НДС',
				'vat0':'НДС 0%',
				'vat10':'НДС 10%',
				'vat20':'НДС 20%',
			};

			var payAnyWayNDS = {
				'1105': 'НДС не облагается',
				'1104':'0%',
				'1103':'10%',
				'1102':'20%',
			};

			var payKeeperTax = {
				'none':'НДС не облагается',
				'vat0':'НДС 0%',
				'vat10':'НДС 10%',
				'vat20':'НДС 20%',
				'vat110':'НДС 10/110',
				'vat120':'НДС 20/120'
			}

			var yandexKassaTaxSystem = {
				'1':'Общая система налогообложения',
				'2':'Упрощенная (УСН, доходы)',
				'3':'Упрощенная (УСН, доходы минус расходы)',
				'4':'Единый налог на вмененный доход (ЕНВД)',
				'5':'Единый сельскохозяйственный налог (ЕСН)',
				'6':'Патентная система налогообложения'
			}

			var numberOfCheckbox = 0;
			$.each(paramArray, function(name, val) {  
				var inpType = "text";
				if(name.indexOf('ароль') + 1) {
					inpType = "password";
				}
				if(name.indexOf('екретн') + 1) {
					inpType = "password";
				}
				if(name.indexOf('од проверки ') + 1) {
					inpType = "password";
				}
				if(name.indexOf('естовый') + 1) {
					 inpType = "checkbox";
				}
				if(name.indexOf('пользовать онлайн кас') + 1) {
					 inpType = "checkbox";
				}
				if(name.indexOf('естовый режим') + 1) {
					inpType = "checkbox";
			   }

				/*COMEPAY*/
				if('Callback Password' === name) {
						inpType = "password";
				}

				if ('Разрешить печать чеков в ККТ' === name) {
						inpType = "checkbox";
				}

				if ('НДС на товары' === name || 'НДС на доставку' === name) {
						var options = '';
						if (data.id == '9') {
							$.each(payAnyWayNDS, function(key, val) {
								options += '<option value="'+key+'">'+val+'</option>'
							});
						}
						if (data.id == '17' || data.id == '11') {
							sberNDS.forEach(function(arr, i, e) {
								options += '<option value="'+i+'">'+arr+'</option>';
							});
						}
						if (data.id == '18') {
							$.each(tinkoffNDS, function(key, val) {
								options += '<option value="'+key+'">'+val+'</option>';
							});
						}
						if (data.id == '20') {
							comepayVats.forEach(function(arr, i, e) {
								options += '<option value="'+i+'">'+arr+'</option>';
							});
						}

						if(data.id == '29'){
							intellectmoneyTax.forEach(function(arr, i, e) {
								options += '<option value="'+i+'">'+arr+'</option>';
							});
						}
						
						$('#add-paymentMethod-wrapper #paymentParam').append(
								'<div class="row">\
									<div class="small-5 columns">\
										<label class="middle">'+name+'</label>\
							</div>\
							<div class="small-7 columns">\
							<div class="select medium">\
								<select name="'+name+'">'+options+'</select>\
							</div>\
						</div>');
						val = admin.htmlspecialchars_decode(val);
						$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
						return;
				}

				if ('Признак способа расчёта' === name) {
						var options = '';
						comepayPayattributs.forEach(function(arr, i, e) {
								options += '<option value="'+i+'">'+arr+'</option>';
						});
						$('#add-paymentMethod-wrapper #paymentParam').append(
								'<div class="row">\
									<div class="small-5 columns">\
										<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
							<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
						val = admin.htmlspecialchars_decode(val);
						$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
						return;
				}
				/* END COMEPAY*/

				if(name.indexOf('С, включенный в це') + 1 && data.id == '14') {
					var options = '';
					yandexNDS.forEach(function(arr, i, e) {
						options += '<option value="'+arr+'">'+arr+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
								<label class="middle">'+name+'</label>\
							</div>\
							<div class="small-7 columns">\
							<div class="select medium">\
								<select name="'+name+'">'+options+'</select>\
							</div>\
						</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return; 
				}
				if(name.indexOf(lang.CRIPT_METHOD) + 1) {
					var options = '<option value="0">'+lang.CHOOSE+':</option>';
					algorithm.forEach(function(arr, i, e) {
						options += '<option value="'+arr+'">'+arr+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
								<label class="middle">'+name+'</label>\
							</div>\
							<div class="small-7 columns">\
							<div class="select medium">\
								<select name="'+name+'">'+options+'</select>\
							</div>\
						</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return; 
				}

				// cloudPayment
				if(name.indexOf('С, включенный в це') + 1) {
					var options = '';
					yandexNDS.forEach(function(arr, i, e) {
						options += '<option value="'+arr+'">'+arr+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
								<label class="middle">'+name+'</label>\
							</div>\
							<div class="small-7 columns">\
							<div class="select medium">\
								<select name="'+name+'">'+options+'</select>\
							</div>\
						</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return; 
				}
				if(name.indexOf(lang.CRIPT_METHOD) + 1) {
					var options = '<option value="0">'+lang.CHOOSE+':</option>';
					algorithm.forEach(function(arr, i, e) {
						options += '<option value="'+arr+'">'+arr+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
								<label class="middle">'+name+'</label>\
							</div>\
							<div class="small-7 columns">\
							<div class="select medium">\
								<select name="'+name+'">'+options+'</select>\
							</div>\
						</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return; 
				}
				if(name.indexOf('истема налогообложения') + 1) {
						var options = '';
						if (data.id == '17') {
							sberTaxSystem.forEach(function(arr, i, e) {
								options += '<option value="'+i+'">'+arr+'</option>';
							});
						}
						if (data.id == '18') {
							$.each(tinkoffTaxSystem, function(key, val) {
								options += '<option value="'+key+'">'+val+'</option>';
							});
						}
						if (data.id == '22') {
							$.each(cloudpaymentsTS, function(key, val) {
								options += '<option value="'+key+'">'+val+'</option>';
							});
						}
						if (data.id == '21') {
							$.each(payKeeperTax, function(key, val) {
								options += '<option value="'+key+'">'+val+'</option>';
							});
						}
						var yandexKassaPaymentsIds = [
							'24',
							'14',
						];
						if (yandexKassaPaymentsIds.includes(data.id)) {
							$.each(yandexKassaTaxSystem, function(key, val) {
								options += '<option value="'+key+'">'+val+'</option>';
							});
						}

						$('#add-paymentMethod-wrapper #paymentParam').append(
								'<div class="row">\
									<div class="small-5 columns">\
										<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
							<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
						val = admin.htmlspecialchars_decode(val);
						$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
						return;
				}
				if(name.indexOf('тавка НДС') + 1) {
					var options = '';
					$.each(cloudpaymentsVat, function(key, val) {
						options += '<option value="'+key+'">'+val+'</option>';
					});
					// console.log(cloudpaymentsVat);
					// console.log(options);
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
								<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
							<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return;
				}
				if(name.indexOf('зык виджета') + 1) {
					var options = '';
					$.each(cloudpaymentsLang, function(key, val) {
						options += '<option value="'+key+'">'+val+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
								<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
							<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return;
				}

				if(name.indexOf('изайн виджета') + 1) {
					var options = '';
					$.each(cloudpaymentsSKIN, function(key, val) {
						options += '<option value="'+key+'">'+val+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
							<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
						<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return;
				}

				if(name.indexOf('хема проведения платежа') + 1) {
				var options = '';
				$.each(cloudpaymentsSCHEME, function(key, val) {
					options += '<option value="'+key+'">'+val+'</option>';
				});
				$('#add-paymentMethod-wrapper #paymentParam').append(
					'<div class="row">\
						<div class="small-5 columns">\
						<label class="middle">'+name+'</label>\
					</div>\
					<div class="small-7 columns">\
					<div class="select medium">\
					<select name="'+name+'">'+options+'</select>\
					</div>\
				</div>');
				val = admin.htmlspecialchars_decode(val);
				$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
				return;
				}
				
				if(name.indexOf('пособ расчета') + 1) {
					var options = '';
					$.each(cloudpaymentsMethod, function(key, val) {
						options += '<option value="'+key+'">'+val+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
							<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
						<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return;
				}
				
				if(name.indexOf('редмет расчета') + 1) {
					var options = '';
					$.each(cloudpaymentsObject, function(key, val) {
						options += '<option value="'+key+'">'+val+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
							<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
						<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return;
				}
				
				if(name.indexOf('татус заказа для печати второго чека') + 1) {
					var options = '';
					$.each(cloudpaymentsStatus, function(key, val) {
						options += '<option value="'+key+'">'+val+'</option>';
					});
					$('#add-paymentMethod-wrapper #paymentParam').append(
						'<div class="row">\
							<div class="small-5 columns">\
							<label class="middle">'+name+'</label>\
						</div>\
						<div class="small-7 columns">\
						<div class="select medium">\
						<select name="'+name+'">'+options+'</select>\
						</div>\
					</div>');
					val = admin.htmlspecialchars_decode(val);
					$('#add-paymentMethod-wrapper #paymentParam select[name="'+name+'"]').val(val);
					return;
				}
				// cloudPayment end
				
				switch(inpType) {
					case 'checkbox':
						numberOfCheckbox++;
						input = '<div class="checkbox margin">\
											<input id="cr' + numberOfCheckbox + '" type="checkbox" name="'+name+'">\
											<label for="cr' + numberOfCheckbox + '"></label>\
										</div>';
						break;
					default:
						input = '<input type="'+inpType+'" name="'+name+'" class="product-name-input" value="">';
						break;
				}

				var tooltipNumber = '';
				if (data.id == 12 || data.id == 13) {
					tooltipNumber = `
						<a class="tool-tip-right desc-property fl-right" href="javascript:void(0);" tooltip='Укажите в данном поле номер телефона, на который пользователю необходимо перевести деньги для оплаты заказа, либо иную информацию, если вы измените текст выводимого сообщения на странице оплаты. Например, "+7(999)999-99-99 (Сбербанк, по системе быстрых платежей)"' flow="up">
							<i class="fa fa-question-circle tip"></i>
						</a>
					`;
				}

				$('#add-paymentMethod-wrapper #paymentParam').append(
					'<div class="row">\
						<div class="small-5 columns">\
							<label class="middle">'+name+' '+tooltipNumber+'</label>\
						</div>\
						<div class="small-7 columns">\
							'+input+'\
						</div>\
					</div>'
				);
				val = admin.htmlspecialchars_decode(val);
				$('#add-paymentMethod-wrapper #paymentParam input[name="'+name+'"]').val(val);
				if (inpType=='checkbox'&&val=='true') {
					$('#add-paymentMethod-wrapper #paymentParam input[name="'+name+'"]').attr('checked', 'checked');
				}
			});
			
			// вешаем текстовый редактор на поле в реквизитах
			$('textarea[class=product-name-input]').ckeditor();  
			//ниличие сопобов доставки для данного метода
			if(!$.isEmptyObject(deliveryMethod)) {
				//выбор способов доставки применительно к данному способу оплаты
				$.each(deliveryMethod, function(deliveryId, active) {
					if(1 == active) {
						$('#add-paymentMethod-wrapper #deliveryCheckbox input[name='+deliveryId+']').prop('checked', true);
					} else {
						$('#add-paymentMethod-wrapper #deliveryCheckbox input[name='+deliveryId+']').prop('checked', false);
					}
				});
			} else {
				// $('#add-paymentMethod-wrapper #deliveryArray').html(lang.NONE_DELIVERY);
			}
			//выбор активности данного способа оплаты
			if(1 == $('tr[id=payment_'+data.id+'] td .activity').attr('status')) {
				$('input[name=paymentActivity]').prop('checked', true);
			}
			
			var rate = $('tr[id=payment_'+data.id+'] td#paymentRate').text();      
			$('.discount-rate-control input[name=rate]').val(rate*100);
			if(rate != 0) {
				$('.discount-setup-rate').hide();
				$('.discount-rate-control').show();
				settings_payment.setupDirRate(rate);  
			}

			$('#add-paymentMethod-wrapper #paymentParam').append(
				'<div class="row">\
					<div class="small-5 columns">\
						<label class="middle">'+lang.PAYMENT_PERMISSION+':</label>\
					</div>\
					<div class="small-7 columns">\
						<select class="medium permission">\
							<option value="all">'+lang.PAYMENT_ALL+'</option>\
							<option value="fiz">'+lang.PAYMENT_FIZ+'</option>\
							<option value="yur">'+lang.PAYMENT_YUR+'</option>\
						</select>\
					</div>\
				</div>'
			);

			$('#add-paymentMethod-wrapper .permission').val(data.permission);

			// Вызов модального окна.
			admin.openModal('#add-paymentMethod-wrapper');
		},

		/**
		 * сохранение способа оплаты (Новай алгоритм)
		 */
		savePaymentMethod:function(id) {
			$('.img-loader').show();
		 
			//названия оплаты
			const name = admin.htmlspecialchars($('.name-payment').val());   
			const publicName  = admin.htmlspecialchars($('.public-name-payment').val());   
			
			
			//параметры оплаты
			const paymentParams = [];
			$('#paymentParam input,#paymentParam select').each(function() {
					if(!$(this).hasClass('name-payment') && !$(this).hasClass('permission')) {
						if ($(this).val()!=null) {
							paymentParams.push({
								name: $(this).attr('name'),
								value: $(this).val()
							});
						}
					}
			});
			
			// Доставки, подключенные к оплате
			const deliveryMethod = [];
			if(0 != $('#deliveryCheckbox #deliveryArray').find('input').length) {
				$('#deliveryCheckbox input').each(function() {
					if($(this).prop('checked')) {
						deliveryMethod.push($(this).attr('name'));
					}
				});
			}            
			
			// Скидка/Наценка оплаты
			var rate = $('.discount-rate-control input[name=rate]').val();
			if(rate!=0) {
				rate = rate/100;
			}
			if($('.rate-dir').text()!='+') {
				rate = -1*rate;
			}
			
			//активность метода оплаты
			var paymentActivity = 0;
			if($('input[name=paymentActivity]').prop('checked')) {
				paymentActivity = 1;
			}

			// активность логирования метода оплаты
			let logs = 0;
			if ($('input[name=paymentLogs]').prop('checked')) {
				logs = 1;
			}

			let description = null;
			let pluginCode = null;
			let pluginTitle = null;
			// Примечание (если есть)
			if (!id) {
				description = $('input[name="Примечание"]').val();
			// Код плагина (если есть)
			} else {
				pluginCode = $('tr[id=payment_'+id+']').data('plugin-code');
				pluginTitle = $('tr[id=payment_'+id+']').data('plugin-title');
			}


			let icon = $('input[name="icon"]').val();
			if (icon) {
				icon = icon.replace(mgBaseDir, '');
			}
			
			admin.ajaxRequest(
				{
					mguniqueurl: "action/savePaymentMethod",
					paymentParam: paymentParams,
					deliveryMethod: deliveryMethod,
					activity: paymentActivity,
					name: name,
					publicName: publicName,
					id: id,
					rate: rate,
					permission: $('#tab-paymentMethod-settings .permission').val(),
					lang: $('#tab-paymentMethod-settings .select-lang').val(),
					icon: icon,
					description: description,
					logs: logs,
					pluginCode: pluginCode,
				},
				function(response) {
					$('.img-loader').hide();
					if('success' == response.status) {
						admin.closeModal('#add-paymentMethod-wrapper');
						admin.refreshPanel();
					} else {
						if (response.data.toMarketplace !== undefined &&  response.data.toMarketplace) {
							includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
							callback = marketplaceModule.init;
							admin.show('marketplace.php', 'adminpage', 'mpFilter=all&mpFilterName='+pluginTitle+'&mpFilterType=all', callback);
						}
					}
					admin.indication(response.status, response.msg);
				}
			);
		},

		/**
		 * сохранение способа оплаты (Старый алгоритм)
		 */
		savePaymentMethodOld:function(id, closeModal) {     
			closeModal = typeof closeModal !== 'undefined' ? closeModal : true; 
			$('.img-loader').show();
		 
			//обрабатываем параметры методов оплаты
			var name = admin.htmlspecialchars($('.name-payment').val());
			
			
			//обрабатываем параметры методов оплаты
			var paymentParam ='{';
			$('#paymentParam input,#paymentParam select').each(function() {

					if(!$(this).hasClass('name-payment') && !$(this).hasClass('permission')) {
					 // paymentParam+='"'+$(this).attr('name')+'":"'+$(this).val().replace(/\\/g, '\\\\\\\\').replace(/"/g, '\\\\$&')+'",';
						if ($(this).val()!=null) {
							paymentParam += '"' + $(this).attr('name') + '":"' + admin.htmlspecialchars($(this).val().replace(/'/g, '"')) + '",';
						}
					}
			});   
			
			paymentParam = paymentParam.substr(0, paymentParam.length-1); //удаляем последнюю запятую в конце списка
			paymentParam+='}';
			
			var deliveryMethod='';
			if(0 != $('#deliveryCheckbox #deliveryArray').find('input').length) {
				//обрабатываем доступные методы доставки для данного метода оплаты

				deliveryMethod='{';
				$('#deliveryCheckbox input').each(function() {

					if($(this).prop('checked')) {
						deliveryMethod += '"'+admin.htmlspecialchars($(this).attr('name'))+'":1,';
					} else {
						deliveryMethod += '"'+admin.htmlspecialchars($(this).attr('name'))+'":0,';
					}
				});

				deliveryMethod = deliveryMethod.substr(0, deliveryMethod.length-1); //удаляем последнюю запятую в конце списка
				deliveryMethod +='}';
			}            
			
			var rate = $('.discount-rate-control input[name=rate]').val();
			
			if(rate!=0) {
				rate = rate/100;
			}
			
			if($('.rate-dir').text()!='+') {
				rate = -1*rate;
			}
			
			//активность метода оплаты
			var paymentActivity = 0;
			if($('input[name=paymentActivity]').prop('checked')) {
				paymentActivity = 1;
			}
			
			admin.ajaxRequest({
				mguniqueurl: "action/savePaymentMethodOld",
				paymentParam: paymentParam,
				deliveryMethod: deliveryMethod,
				paymentActivity: paymentActivity,
				name: name,
				rate: rate,
				paymentId: id,
				permission: $('#tab-paymentMethod-settings .permission').val(),
				lang: $('#tab-paymentMethod-settings .select-lang').val()
			},
			function(response) {
				$('.img-loader').hide();
				admin.indication(response.status, response.msg);
				if('success' == response.status) {
					if (closeModal) {

						admin.closeModal('#add-paymentMethod-wrapper');
						admin.refreshPanel();
					} else {
						$('#add-paymentMethod-wrapper').attr('data-refresh', 'true');
					}
				}
			}
			);
		},
		setupDirRate: function(rate) {         
			if(rate>=0) {
				$('#add-paymentMethod-wrapper select[name=change_rate_dir] option[value=up]').prop('selected','selected');
				$('#add-paymentMethod-wrapper .discount-rate').removeClass('color-down').addClass('color-up');
				$('.rate-dir').text('+');
				$('.rate-dir-name span').text(lang.DISCOUNT_UP);
				$('.discount-rate-control input[name=rate]').val(Math.abs($('.discount-rate-control input[name=rate]').val()));
			} else {
				$('#add-paymentMethod-wrapper select[name=change_rate_dir] option[value=down]').prop('selected','selected');  
				$('.rate-dir-name span').text(lang.DISCOUNT_DOWN);
				$('#add-paymentMethod-wrapper .discount-rate').removeClass('color-up').addClass('color-down');
				$('.rate-dir').text('-');
				$('.discount-rate-control input[name=rate]').val(Math.abs($('.discount-rate-control input[name=rate]').val()));
			}
		},
		createPaymentModalOpenNew: function() {
			$('#add-paymentMethod-wrapper span#paymentName').html('<span class="default_icon"><input class="name-payment" name="name" type="text" value=""></span>');
			$('#add-paymentMethod-wrapper span#paymentPublicName').html('<span><input class="public-name-payment" name="publicName" type="text" value=""></span>');
			settings_payment.clearFileds();
			$('#add-paymentMethod-wrapper .payment-table-icon').text(lang.TITLE_NEW_PAYMENT);
			$('#paymentParam').replaceWith('<div id="paymentParam"><div class="row"><div class="small-5 columns"><label class="middle">Примечание</label></div><div class="small-7 columns"><input type="text" name="Примечание" class="product-name-input" value=""></div></div><div class="row"><div class="small-5 columns"><label class="middle">Способ оплаты доступен для:</label></div><div class="small-7 columns"><select class="medium permission"><option value="all">всех</option><option value="fiz">физических лиц</option><option value="yur">юридических лиц</option></select></div></div></div>');
			// Вызов модального окна.
			$('#add-paymentMethod-wrapper .show-mp-desc').hide();
			$('#mp-descr-modal').hide();
			$('#tab-paymentMethod-settings .reveal-double-left,.reveal-double-right').removeClass('reveal-double-left reveal-double-right');
			const iconHtml = `
				<div class="row">
					<div class="small-5 columns">
						<div class="uploaded-img logo-img squareImg" style="min-width: 50px; max-width:100px;">
							<img src="${mgBaseDir}/mg-admin/design/images/icons/cash.png">
							<a class="fa fa-times tip remove-added-logo" href="javascript:void(0);" title="Убрать иконку"></a>
						</div>
						<input type="hidden" name="icon" class="settings-input option" value="/mg-admin/design/images/icons/cash.png">
					</div>
					<div class="small-7 columns">
						<a role="button" href="javascript:void(0);" class="link add-logo browseImageLogo button--noindent">
							<i class="fa fa-upload" aria-hidden="true"></i>
							<span>Выбрать иконку</span>
						</a>
					</div>
				</div>
			`;
			$('#paymentIcon').html(iconHtml);
			admin.openModal('#add-paymentMethod-wrapper');
			$('#add-paymentMethod-wrapper').parents('.reveal-overlay').css('display', 'flex');
		},
		createPaymentModalOpen: function() {
			$('#add-paymentMethod-wrapper span#paymentName').html('<span class="default_icon">'+'<input class="name-payment" name="name" type="text" value="">'+'</span>');
			settings_payment.clearFileds();
			$('#add-paymentMethod-wrapper .payment-table-icon').text(lang.TITLE_NEW_PAYMENT);
			$('#paymentParam').replaceWith('<div id="paymentParam"><div class="row"><div class="small-5 columns"><label class="middle">Примечание</label></div><div class="small-7 columns"><input type="text" name="Примечание" class="product-name-input" value=""></div></div><div class="row"><div class="small-5 columns"><label class="middle">Способ оплаты доступен для:</label></div><div class="small-7 columns"><select class="medium permission"><option value="all">всех</option><option value="fiz">физических лиц</option><option value="yur">юридических лиц</option></select></div></div></div>');
			// Вызов модального окна.
			admin.openModal('#add-paymentMethod-wrapper');
		},
		deletePluginPayment: function(code, pluginCode = null) {
			admin.ajaxRequest(
				{
					mguniqueurl: 'action/deletePluginPayment',
					code: code,
					pluginCode: pluginCode,
				},
				function (response) {
					admin.indication(response.status, response.msg);
					if (response.status === 'success') {
						admin.refreshPanel();
					}
				}
			);
		},
		deletePayment: function(id) {
			$.ajax({
				type: "POST",
				url: mgBaseDir + "/ajax",
				data: {
					mguniqueurl: "action/deletePayment",
					id: id,
				},
				cache: false,
				// async: false,
				dataType: "json",
				success: function (response) {
					admin.refreshPanel();
				}
			});
		},
		clearFileds:function() {
			$('#tab-paymentMethod-settings input[name=paymentActivity]').prop('checked', false);
			$('#tab-paymentMethod-settings .deliveryMethod').prop('checked', false);
			$('#tab-paymentMethod-settings #add-paymentMethod-wrapper #urlParam').html('');
			$('#tab-paymentMethod-settings .discount-setup-rate').show();
			$('#tab-paymentMethod-settings .discount-rate-control input[name=rate]').val(0);
			$('#tab-paymentMethod-settings .discount-rate-control').hide();
			$('#tab-paymentMethod-settings #add-paymentMethod-wrapper .discount-rate').removeClass('color-down').addClass('color-up');
			$('#tab-paymentMethod-settings .save-button').attr('id','');
			$('#tab-paymentMethod-settings input').removeClass('error-input');
			$('#tab-paymentMethod-settings .errorField').css('display','none');
		},
		changeActivity: function(id, status, plugin, pluginCode = null, pluginTitle = null) {
			if(status) status = 1; else status = 0;
			admin.ajaxRequest({
				mguniqueurl: "action/changeActivityDP",
				tab: 'payment',
				id: id,
				status: status,
				plugin: plugin,
				pluginCode: pluginCode,
			},
			function(response) {
				if (response.data.toMarketplace !== undefined && response.data.toMarketplace && pluginTitle) {
					includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
					callback = marketplaceModule.init;
					admin.show('marketplace.php', 'adminpage', 'mpFilter=all&mpFilterName='+pluginTitle+'&mpFilterType=all', callback);
				}
				admin.indication(response.status, response.msg);
			});
		},
	};
})();
