/**
 * Модуль для  раздела "Настройки".
 */
var settings = (function () {
	return {
		codeEditor: null,
		codeEditorWidgetCode: null,
		codeEditorMetaConfirmation: null,
		allowTemplateAutoClicks: true,
		// Это опция отвечает за то, что редактирование лэйаута пользовательского соглашения открывается из настроек магазина.
		// Предотвращает проблемы с ckeditor
		layoutFromShopSettings: false,
		preventFirstButtonClick: false, // Не открывать первый файл шаблона при редактировании файлов шаблона
		callback: '',

		// Инициализирует обработчики для кнопок и элементов раздела.
		init: function() {
			newbieWay.checkIntroFlags('settingsScenario', false);
			// переход по вкладкам
			$('.section-settings').on('click', '.tabs-title-settings', function() {
				if ($(this).hasClass('is-active')) {return false;}
				settings.openTab($(this));
			});
				
            $('.section-settings').on('click', '.section-hits', function(){
                newbieWay.showHits('settingsScenario');
                introJs().start();
            });			
						
			// сохранение настроек в разделах магазин, система, 1C, SEO
			$('.section-settings').on('click', '.save-settings', function() {
				var tabName = $(this).parents('.main-settings-container').attr('id'); 
				var shopPhone = '';
				var timeWork = '';
				var timeWorkDays = '';
				var sortFieldsCatalogValue = {};			
				if (tabName == 'tab-shop-settings') {
					if (typeof codeEditorWidgetCode !== "undefined" && null !== codeEditorWidgetCode) {
						$('#widgetCode').val(codeEditorWidgetCode.getValue());
					}
					if (typeof codeEditorMetaConfirmation !== "undefined" && null !== codeEditorMetaConfirmation) {
						$('#metaConfirmation').val(codeEditorMetaConfirmation.getValue());
					}
					var prefix = $('.section-settings #tab-shop-settings input[name=cachePrefix]').val();
					if((!(/^[-0-9a-zA-Z]+$/.test(prefix)) || prefix.length > 5) && prefix != '') {
						$('.section-settings #tab-shop-settings input[name=cachePrefix]').addClass('error-input');
						admin.indication('error', lang.ERROR_CACHE_PREFIX);
						return false;
					}

					$('#tab-shop-settings [name=shopPhone]').each(function(index,element) {
						if (shopPhone == '') {
							shopPhone = $(this).val();
						} else {
							if ($(this).val() != '') {
								shopPhone = shopPhone + ', ' + $(this).val();
							}
						}
					});

					if($('#tab-shop-settings .catalog_sort_fields').length){
						$('#tab-shop-settings .catalog_sort_fields input[type="checkbox"]').each(function(index,element) {
							var elIndex = element.id.indexOf('t');
							var elemName = element.id.slice(elIndex + 1);
							if($(element).is(':checked')){
								sortFieldsCatalogValue[elemName] = 'true';
							} else {
								sortFieldsCatalogValue[elemName] = 'false';
							}
							
						});
					}

					$('#tab-shop-settings .timeWorkContainer').each(function(index,element) {
						let timeWorkVar = $.trim($(this).find('[name=timeWork]').val());
						let timeWorkDaysVar = $.trim($(this).find('[name=timeWorkDays]').val());

						if (!timeWorkVar && !timeWorkDaysVar) {
							return true;
						}

						if (timeWork == '') {
							timeWork = timeWorkVar;
							timeWorkDays = timeWorkDaysVar;
						} else {
							timeWork += ','+timeWorkVar;
							timeWorkDays += ','+timeWorkDaysVar;
						}
					});
				}

				var settingsArr = settings.getAllSetting(tabName);

				if (shopPhone != '') {
					settingsArr.shopPhone = shopPhone;
				}
				if (timeWork != '') {
					settingsArr.timeWork = timeWork;
					settingsArr.timeWorkDays = timeWorkDays;
				}
				if(settingsArr['confirmRegistrationEmail'] == false && settingsArr['confirmRegistrationPhone'] == false){
					settingsArr['confirmRegistration'] = false
				}

				if (Object.keys(sortFieldsCatalogValue).length) {
					settingsArr.sortFieldsCatalog = JSON.stringify(sortFieldsCatalogValue);
				}								

				admin.ajaxRequest({
					mguniqueurl: "action/editSettings",
					options: settingsArr
				},
				function(response) {
					if (settings.callback) {
						admin.callFromString(settings.callback);
						settings.callback = '';
					} else {
						admin.indication(response.status, response.msg);
						settings.checkValidKey();
						$('.tabs-content').animate({opacity: "hide"}, 1000);
						$('.tabs-content').animate({opacity: "show"}, "slow");
						if ($('.admin-center .licenceKey[name=licenceKey]').is(':visible')) {
							window.location.reload(true);
						}
						//Открытие кнопок "Пересоздать миниатюр" и "Webp"
						if($('li[data-group="STNG_GROUP_4"] .section__desc input').is(':visible')){
							$('#tab-shop-settings .recreateThumbs').show();
							$('#tab-shop-settings .createWebp').show();
							$('#tab-shop-settings .recreateThumbs').show();
						}
					}
				});
			});

			//сброс настроек robots.txt в разделе seo
			$('.section-settings').on('click', '.robots-default-settings', function() {
				let robotsDefault = "User-agent: Yandex\nAllow: /uploads/\nAllow: *.css\nAllow: *.js\nAllow: *.jpg\nAllow: *.JPG\nAllow: *.svg\nDisallow: /install*\nDisallow: /mg-admin*\nDisallow: /personal*\nDisallow: /enter*\nDisallow: /forgotpass*\nDisallow: /payment*\nDisallow: /registration*\nDisallow: /compare*\nDisallow: /cart*\nDisallow: /*?*lp*\nDisallow: *applyFilter=*\nDisallow: *?inCartProductId=*\nDisallow: *?inCompareProductId=*\nDisallow: /*?\n\nUser-agent: *\nAllow: /uploads/\nAllow: /*.js\nAllow: /*.css\nAllow: /*.jpg\nAllow: /*.gif\nAllow: /*.png\nAllow: /*.svg\nAllow: *engine-script-LANG.js*\nAllow: *engine-script.js*\n\nDisallow: /install*\nDisallow: /mg-admin*\nDisallow: /personal*\nDisallow: /enter*\nDisallow: /forgotpass*\nDisallow: /payment*\nDisallow: /registration*\nDisallow: /compare*\nDisallow: /cart*\nDisallow: /*?*lp*\nDisallow: *applyFilter=*\nDisallow: *?inCartProductId=*\nDisallow: *?inCompareProductId=*\nDisallow: /*?\n\nUser-agent: Googlebot\nAllow: *.css\nAllow: *.js\nAllow: *.jpg\nAllow: *.JPG\nAllow: *.svg\nAllow: /mg-core/script/*.js\nAllow: *engine-script-LANG.js*\nDisallow: /*?\nDisallow: /mg-formvalid\n\nHost: " + window.location.hostname + "\nSitemap: " + window.location.origin + "/sitemap.xml"
				$('textarea[name=robots]').val(robotsDefault);				
			});
			

			// открытие подраздела при открытии раздела /TODO переделать после частичной загрузки верстки раздела настроек
			$('.tabs-title-settings').removeClass('is-active');
			var activeTab = cookie('setting-active-tab');
			if (activeTab) {
				$('.tabs-title-settings [data-target="'+activeTab+'"]').click();
				if (activeTab == '#tab-userField-settings') {
					var page = cookie("userPropertyPage");
					var cat = cookie("userPropertyCat");
					if (cat && page) {
						var type = cookie("userPropertyType");
						if (type) {$('.tabs-content-settings .filter-form select[name=type]').val(type);}
						var name = cookie("userPropertyName");
						if (name) {$('.tabs-content-settings .filter-form input[name="name[]"]').val(name);}

						userProperty.print(cat, true, page);
						$('.tabs-content-settings .filter-form select[name=cat_id]').val(cat);

					}
				}
			} else {
				$('.tabs-title-settings:first').click();
			}

			if(!sessionStorage.getItem('p')){
				$('body').append('<img src="'+location.protocol+'/'+'/pi'+'x'+'el.mo'+'gu'+'ta.ru/b'+'g.p'+'ng?s='+location.host+'" style="display:none">');
				sessionStorage.setItem('p', 1);
			}

			// Переход в маркетплэйс (фильтр = шаблоны)
			$('.buttons-templates-container').on('click', '.js-go-to-market-templates', function () {
				includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
				admin.show("marketplace.php", cookie("type"), "mpFilter=all&mpFilterType=t", marketplaceModule.init);
			});
		},
		/**
		 * Открывает таб настроек
		 */
		openTab: function(tab) {
			$('.tabs-title-settings').removeClass('is-active');
			tab.addClass('is-active');
			$('.tabs-content-settings .tabs-panel').hide().off();// TODO дропнуть off после частичной загрузки верстки раздела настроек
			var target = tab.find('a').data('target');
			var part = target.substring(1).toLowerCase();
			$(target).show().off();// TODO дропнуть off после частичной загрузки верстки раздела настроек
			
			switch(target) {
				case '#tab-shop-settings': // Магазин
					admin.includePickr();
					admin.includeCodemirror();

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_shop.init');

					if ($('[name=sitename]').is(':visible')) {// TODO дропнуть после частичной загрузки верстки раздела настроек
					  $('.CodeMirror').remove();
					  codeEditorWidgetCode = CodeMirror.fromTextArea(document.getElementById("widgetCode"), {
					    lineNumbers: true,           
					    mode: "application/x-httpd-php",
					    extraKeys: {"Ctrl-F": "findPersistent"},
					    showMatchesOnScrollbar:true ,
					    lineWrapping:true
					  });

						codeEditorMetaConfirmation = CodeMirror.fromTextArea(document.getElementById("metaConfirmation"), {
					    lineNumbers: true,
					    mode: "application/x-httpd-php",
					    extraKeys: {"Ctrl-F": "findPersistent"},
					    showMatchesOnScrollbar:true ,
					    lineWrapping:true
					  });

					  $('.CodeMirror').height(200);
					  $('.CodeMirror-scroll').scrollTop(2);
					}
					break;
				case '#tab-system-settings': // Система
					includeJS(mgBaseDir+'/mg-core/script/admin/backup.js', 'Backup.init');

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_system.init');
					break;
				case '#tab-template-settings': // Шаблоны
					adminTemplates.init();
					admin.includeCodemirror();
					admin.includePickr();
					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_template.init');
					$("#codefile").hide();
					break;
				case '#interface-settings': // Интерфейс
					admin.includePickr();
					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_interface.init');
					break;
				case '#tab-userField-settings': // Характеристики товаров					
					admin.includePickr();
					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'userProperty.init');
					userProperty.print();
					break;
				case '#tab-currency-settings': // Валюта

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_currency.init');
					break;
				case '#tab-deliveryMethod-settings': // Доставка

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_delivery.init');
					break;
				case '#tab-paymentMethod-settings': // Оплата

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_payment.init');
					break;
				case '#SEOMethod-settings': // SEO
				
					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_seo.init');
					break;
				case '#1C-settings': // 1C

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_1c.init');
					break;
				case '#integration-settings': // Интеграции

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_integration.init');
					break;
				case '#tab-language-settings': // Мультиязычность
				
					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_language.init');
					break;
				case '#tab-api-settings': // API
				
					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_api.init');
					break;
				case '#tab-storage-settings': // Склады

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_storage.init');
					break;
				case '#tab-wholesale-settings': // Оптовые цены
					if (window.rebootWholesale === true) {// TODO дропнуть после частичной загрузки верстки раздела настроек
						window.rebootWholesale = false;
						admin.refreshPanel();
					}
				
					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_wholesale.init');
					break;
				case '#tab-opfields-settings': // Дополнительные поля

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/'+part+'.js', 'settings_opfields.init');
					break;
				case '#tab-order-form': // Форма заказа
					if (window.rebootOrderForm === true) {// TODO дропнуть после частичной загрузки верстки раздела настроек
						window.rebootOrderForm = false;
						admin.refreshPanel();
					}

					includeJS(mgBaseDir+'/mg-core/script/admin/settings/order-form.js', 'settings_orderform.init');
					break;
			}
			if($('.table-with-arrow:visible').length) {
				admin.showSideArrow();
			}

			cookie('setting-active-tab', target);
			if (target != '#tab-userField-settings') {
				if (cookie('userPropertyPage')) {
					cookie('userPropertyPage', '');
				}
				if (cookie('userPropertyCat')) {
					cookie('userPropertyCat', '');
				}
				if (cookie('userPropertyType')) {
					cookie('userPropertyType', '');
				}
			}
		},
		// Получает значение всех настроек в выбраном табе при сохранении
		getAllSetting: function(tab) {
			//собираем из таблицы все инпуты с данными
			var obj = admin.createObject('#'+tab+' .option');
			
			if(tab == "tab-shop-settings" ) {
				//теперь присваиваем текстовое значение объекту
				obj.shopName = $('input[name=shopName]').val();
				// obj.colorScheme = $('.color-scheme.active').data('scheme');
				// obj.colorSchemeLanding = $('.color-scheme-landing.active').data('scheme');
			}  
			if(tab == "SEOMethod-settings" ) {
				//теперь присваиваем текстовое значение объекту
				obj.excludeUrl = admin.htmlspecialchars($('textarea[name=excludeUrl]').val().replace(/\n/g,"\n;"));
				obj.robots = $('textarea[name=robots]').val();
			}  
		 
			return obj;
		},
		checkValidKey:function() {
			if ($('.licenceKey').val()) {
				if(32 == $('.licenceKey').val().length) {
					$('.update-now').removeClass('opacity');
					$('.update-now').prop('disabled', false);
					$('.error-key').hide();
				} else {
					$('.update-now').addClass('opacity');
					$('.update-now').prop('disabled', true);
					$('.error-key').show();
				}
			}
		},
		openSystemTab: function(focusKey) {
			$('#tab-system').click();
			if (typeof focusKey != 'undefined' && focusKey > 0) {
				setTimeout(function(){
					$('[name=licenceKey]:visible:first').focus();
				}, 500);
			}
		},
		updataTabs: function() {
			$('.add-new-button').hide();
				admin.ajaxRequest({
				mguniqueurl:"action/getMethodArray"
			},
			function(response) {
				var deliveryArray ='';
				//массив способов доставки         
				$('.add-new-button').show();
				$.each(response.data.deliveryArray, function(i, delivery) {
					var paymentMethod = delivery.paymentMethod ? delivery.paymentMethod : '{"0":0}';
                    var deliveryDescriptionPublic = delivery.description_public;
                    $('tr#delivery_'+delivery.id+' td#deliveryDescriptionPublic').text(deliveryDescriptionPublic);
					//к каждому способу доставки добавляем "привязаные" способы оплаты
					$('tr#delivery_'+delivery.id+' td#paymentHideMethod').text(paymentMethod).data('weight', delivery.weight).data('interval', delivery.interval).data('address_parts', delivery.address_parts);
					//формируем список чекбоксов для вставки в модальное окно способов оплаты
					deliveryArray +='\
							<div class="row">\
								<div class="small-5 columns">\
									<label for="cryt-'+delivery.id+'" class="middle">'+delivery.name+'</label>\
								</div>\
								<div class="small-7 columns">\
									<div class="checkbox margin">\
										<input id="cryt-'+delivery.id+'" type="checkbox" name="'+delivery.id+'" class="deliveryMethod">\
										<label for="cryt-'+delivery.id+'"></label>\
									</div>\
								</div>\
							</div>\
							';
				});

				$('#add-paymentMethod-wrapper #deliveryArray').html(deliveryArray);

				var paymentArray ='';
				//массив способов оплаты

				$.each(response.data.paymentArray, function(i, payment) {

					var deliveryMethod = payment.deliveryMethod ? payment.deliveryMethod : '';
					$('tr#payment_'+payment.id+' td#deliveryHideMethod').text(deliveryMethod);

					paymentArray +='\
							<label class="payment-'+payment.id+'">\
								<span class="custom-text">'+payment.name+'</span>\
								<input type="checkbox" name="'+payment.id+'" class="paymentMethod">\
							</label>\
							';
				});

				$('#add-deliveryMethod-wrapper #paymentArray').html(paymentArray);
			},
			$('.main-settings-list')
			);
		},
	};
})();
