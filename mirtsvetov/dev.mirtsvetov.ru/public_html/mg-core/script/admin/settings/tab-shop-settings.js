var settings_shop = (function () {
	return {
		init: function() {
			settings_shop.initEvents();

			// Добавляем стили колорпикера
			$('.colorpicker-style').detach();
			$('body').append('<div class="colorpicker-style"></div>');
			$('.colorpicker-style').append('<link rel="stylesheet" type="text/css" href="' + admin.SITE + '/mg-core/script/colorPicker/css/colorpicker.css" />'+
				'<link rel="stylesheet" media="screen" type="text/css" href="' + admin.SITE + '/mg-core/script/colorPicker/css/layout.css" />'+
				'<link rel="stylesheet" type="text/css" href="' + admin.SITE + '/mg-core/script/pickr/themes/classic.min.css" />');
			$('.colorpicker').detach();

			admin.clickPickr('backgroundColorSite');

			$('input[name="smtp"]').trigger('change');// TODO дропнуть после частичной загрузки верстки раздела настроек

			//Ищем текущую текстуру
			var bg = $('input[name="backgroundTextureSite"]').val();
			if (bg.length == 0) {
				$('#tab-shop-settings .background-texture-settings li[data-name="bg_none"]').addClass('textures__item_active');
			} else {
				$('#tab-shop-settings .background-texture-settings li[data-name="'+bg+'"]').addClass('textures__item_active');
			}
			settings_shop.checkImportWebp();
		},
		initEvents: function() {

			// смена фона публички
			$('#tab-shop-settings').on('click', '.background-texture-settings .color-list li', function(){
				$('#tab-shop-settings .background-texture-settings li').removeClass('textures__item_active');
				$(this).addClass('textures__item_active');

				var bg = $(this).data('name');
				if (bg == 'bg_none') {
					$('input[name="backgroundTextureSite"]').val('');
				} else {
					$('input[name="backgroundTextureSite"]').val(bg);
				}
			});

			$('#tab-shop-settings').on('click', '.viewAgreement', function() {
				settings.preventFirstButtonClick = true;
				$('.section-settings #tab-template a').click();
				$('.section-settings .template-tabs-menu a[data-target="#ttab3"]').click();
				$('.section-settings .template-tabs-content #ttab3 a:contains("layout_agreement.php")').click();
				$('.section-settings .template-tabs-content #ttab3 a:contains("agreement.php")').click();
			});

			$('#tab-shop-settings').on('click', '.accordion .accordion-item:nth-child(1) .accordion-title', function() {
				$('.CodeMirror').remove();
				setTimeout(() => {

					codeEditorWidgetCode = CodeMirror.fromTextArea(document.getElementById("widgetCode"), {
						lineNumbers: true,
						mode: "text/javascript",
						extraKeys: {"Ctrl-F": "findPersistent"},
						showMatchesOnScrollbar:true ,
						lineWrapping:true
					});

					codeEditorMetaConfirmation = CodeMirror.fromTextArea(document.getElementById("metaConfirmation"), {
						lineNumbers: true,
						mode: "text/javascript",
						extraKeys: {"Ctrl-F": "findPersistent"},
						showMatchesOnScrollbar:true ,
						lineWrapping:true
					});

					$('.CodeMirror').height(200);
					$('.CodeMirror-scroll').scrollTop(2);
				}, 0);
			});

			//скрывалка полей при загрузке
			$('#tab-shop-settings').on('click', '.accordion .accordion-item .accordion-title', function() {
				$('[name=cacheMode]').trigger('change');
				$('[name=printAgreement]').trigger('change');
				if ($('[name=backgroundSite]').val() == '') {
					$('[name=backgroundSiteLikeTexture]').closest('.js-settline-toggle').hide();
					$('[name=backgroundTextureSite]').closest('.js-settline-toggle').show();
				} else {
					$('[name=backgroundSiteLikeTexture]').closest('.js-settline-toggle').show();
					$('[name=backgroundTextureSite]').closest('.js-settline-toggle').hide();
				}
			});

			//скрывалка полей при клике
			$('#tab-shop-settings').on('change',
				'#tuseCaptcha, #tcaptchaOrder, #tuseReCaptcha, [name=searchType], #tusePhoneMask, #tmainPageIsCatalog, #tprintAgreement, input[name="smtp"], select[name="cacheMode"], input[name=sessionToDB], #trememberLogins, #tconfirmRegistration, #tlockAuthorization, #twaterMark, input[name="orderOwners"], input[name="ownerRotation"], input[name="ownerRemember"]',
			 function() {
				if ($('#tuseCaptcha').prop('checked') || $('#tcaptchaOrder').prop('checked')) {
					$('[name=useReCaptcha]').closest('.js-settline-toggle').show();
				} else{
					$('#tuseReCaptcha').prop('checked',false);
					$('[name=useReCaptcha]').closest('.js-settline-toggle').hide();
					$('[name=reCaptchaKey]').closest('.js-settline-toggle').hide();
					$('[name=reCaptchaSecret]').closest('.js-settline-toggle').hide();
				}
				if ($('#tuseReCaptcha').prop('checked')) {
					$('[name=reCaptchaKey]').closest('.js-settline-toggle').show();
					$('[name=reCaptchaSecret]').closest('.js-settline-toggle').show();
					$('[name=invisibleReCaptcha]').closest('.js-settline-toggle').show();
				} else{
					$('[name=reCaptchaKey]').closest('.js-settline-toggle').hide();
					$('[name=reCaptchaSecret]').closest('.js-settline-toggle').hide();
					$('[name=invisibleReCaptcha]').closest('.js-settline-toggle').hide();
				}
				if ($('[name=searchType]').val() == 'sphinx') {
					$('[name=searchSphinxHost]').closest('.js-settline-toggle').show();
					$('[name=searchSphinxPort]').closest('.js-settline-toggle').show();
					$('[name=sphinxLimit]').closest('.js-settline-toggle').show();
				} else{
					$('[name=searchSphinxHost]').closest('.js-settline-toggle').hide();
					$('[name=searchSphinxPort]').closest('.js-settline-toggle').hide();
					$('[name=sphinxLimit]').closest('.js-settline-toggle').hide();
				}
				if ($('#tusePhoneMask').prop('checked')) {
					$('[name=phoneMask]').closest('.js-settline-toggle').show();
				} else{
					$('[name=phoneMask]').closest('.js-settline-toggle').hide();
				}
				if ($('#tprintAgreement').prop('checked')) {
					$('.viewAgreement').show();
				} else{
					$('.viewAgreement').hide();
				}
				if ($('#tmainPageIsCatalog').prop('checked')) {
					$('[name=randomProdBlock]').closest('.js-settline-toggle').show();
					$('[name=countNewProduct]').closest('.js-settline-toggle').show();
					$('[name=countRecomProduct]').closest('.js-settline-toggle').show();
					$('[name=countSaleProduct]').closest('.js-settline-toggle').show();
				} else{
					$('[name=randomProdBlock]').closest('.js-settline-toggle').hide();
					$('[name=countNewProduct]').closest('.js-settline-toggle').hide();
					$('[name=countRecomProduct]').closest('.js-settline-toggle').hide();
					$('[name=countSaleProduct]').closest('.js-settline-toggle').hide();
				}
				if($('input[name="smtp"]').prop('checked')) {
					$('input[name=smtpSsl]').closest('.js-settline-toggle').show();
					$('input[name=smtpHost]').closest('.js-settline-toggle').show();
					$('input[name=smtpLogin]').closest('.js-settline-toggle').show();
					$('input[name=smtpPass]').closest('.js-settline-toggle').show();
					$('input[name=smtpPort]').closest('.js-settline-toggle').show();
				} else {
					$('input[name=smtpSsl]').closest('.js-settline-toggle').hide();
					$('input[name=smtpHost]').closest('.js-settline-toggle').hide();
					$('input[name=smtpLogin]').closest('.js-settline-toggle').hide();
					$('input[name=smtpPass]').closest('.js-settline-toggle').hide();
					$('input[name=smtpPort]').closest('.js-settline-toggle').hide();
				}
				if($('select[name="cacheMode"]').val()=="MEMCACHE") {
					$('.memcache-conection').show();
					$('input[name="cacheHost"]').closest('.js-settline-toggle').show();
					$('input[name="cachePort"]').closest('.js-settline-toggle').show();
					$('input[name="cachePrefix"]').closest('.js-settline-toggle').show();
				} else {
					$('.memcache-conection').hide();
					$('input[name="cacheHost"]').closest('.js-settline-toggle').hide();
					$('input[name="cachePort"]').closest('.js-settline-toggle').hide();
					$('input[name="cachePrefix"]').closest('.js-settline-toggle').hide();
				}
				if($('input[name=sessionToDB]').prop('checked')) {
					$('.section-settings #tab-shop-settings input[name=sessionLifeTime]').closest('.js-settline-toggle').show();
				} else {
					$('.section-settings #tab-shop-settings input[name=sessionLifeTime]').closest('.js-settline-toggle').hide();
				}
				if ($('#trememberLogins').prop('checked')) {
					$('[name=rememberLoginsDays]').closest('.js-settline-toggle').show();
				} else{
					$('[name=rememberLoginsDays]').closest('.js-settline-toggle').hide();
				}
				if ($('#tconfirmRegistration').prop('checked')) {
                    $('#tconfirmRegistration').prop({'readonly': true,'disabled': true});
                    $('[name=confirmRegistrationEmail]').closest('.js-settline-toggle').show();
                    $('[name=confirmRegistrationPhone]').closest('.js-settline-toggle').show();
				} else {
					$('[name=confirmRegistrationEmail]').closest('.js-settline-toggle').hide();
                    $('[name=confirmRegistrationPhone]').closest('.js-settline-toggle').hide();
                    $('#tconfirmRegistrationPhone').prop('checked', false);
                    $('#tconfirmRegistrationEmail').prop('checked', false);
				}
                $('#tconfirmRegistrationEmail,#tconfirmRegistrationPhone').click(function(event){
					const id = event.target.id;
					if (id === 'tconfirmRegistrationEmail') {
						if ($('#tconfirmRegistrationPhone').prop('checked') === true && $('#tconfirmRegistrationEmail').prop('checked') === true) {
							$('#tconfirmRegistrationPhone').prop('checked', false);
							$('#tconfirmRegistrationPhone').attr('checked', false);
						}
					} else {
						if ($('#tconfirmRegistrationEmail').prop('checked') === true && $('#tconfirmRegistrationPhone').prop('checked') === true) {
							$('#tconfirmRegistrationEmail').prop('checked', false);
							$('#tconfirmRegistrationEmail').attr('checked', false);
						}
					}
					if ($('#tconfirmRegistrationEmail').prop('checked') === false && $('#tconfirmRegistrationPhone').prop('checked') === false) {
						$('#tconfirmRegistration').closest('.js-settline-toggle').show();
						$('[name=confirmRegistrationEmail]').closest('.js-settline-toggle').hide();
						$('[name=confirmRegistrationPhone]').closest('.js-settline-toggle').hide();
						$('#tconfirmRegistration').prop({'checked':false,'readonly': false,'disabled': false});
						}
					if($('#tconfirmRegistrationPhone').prop('checked') === false){
						$('[name=confirmRegistrationPhoneType]').closest('.js-settline-toggle').hide();
					}
					else{
						$('[name=confirmRegistrationPhoneType]').closest('.js-settline-toggle').show();
					}
				
                });
				if ($('#tlockAuthorization').prop('checked')) {
					$('[name=loginAttempt]').closest('.js-settline-toggle').show();
					$('[name=loginBlockTime]').closest('.js-settline-toggle').show();
				} else{
					$('[name=loginAttempt]').closest('.js-settline-toggle').hide();
					$('[name=loginBlockTime]').closest('.js-settline-toggle').hide();
				}
				if ($('#twaterMark').prop('checked')) {
					$('#upload-watermark').closest('.js-settline-toggle').show();
					$('[name=waterMarkPosition]').closest('.js-settline-toggle').show();
				} else{
					$('#upload-watermark').closest('.js-settline-toggle').hide();
					$('[name=waterMarkPosition]').closest('.js-settline-toggle').hide();
				}
				if ($('input[name="orderOwners"]').prop('checked')) {
					$('input[name="ownerRotation"]').closest('.js-settline-toggle').show();
					$('input[name="ownerRemember"]').closest('.js-settline-toggle').show();
				} else {
					$('input[name="ownerRotation"]').closest('.js-settline-toggle').hide();
					$('input[name="ownerRemember"]').closest('.js-settline-toggle').hide();
				}
				if ($('input[name="orderOwners"]').prop('checked') && $('input[name="ownerRemember"]').prop('checked')) {
          $('input[name="ownerRememberPhone"]').closest('.js-settline-toggle').show();
          $('input[name="ownerRememberEmail"]').closest('.js-settline-toggle').show();
					$('input[name="ownerRememberDays"]').closest('.js-settline-toggle').show();
				} else {
          $('input[name="ownerRememberPhone"]').closest('.js-settline-toggle').hide();
          $('input[name="ownerRememberEmail"]').closest('.js-settline-toggle').hide();
					$('input[name="ownerRememberDays"]').closest('.js-settline-toggle').hide();
				}

				if ($('input[name="orderOwners"]').prop('checked') && $('input[name="ownerRotation"]').prop('checked')) {
					$('select[name="ownerList"]').closest('.js-settline-toggle').show();
				} else {
					$('select[name="ownerList"]').closest('.js-settline-toggle').hide();
				}
			});

			$('#tab-shop-settings').on('click', '.addShopPhone', function() {
				if ($('#tab-shop-settings [name=shopPhone]').length > 1) {
					$('#tab-shop-settings [name=shopPhone]:first').clone().insertAfter($('#tab-shop-settings [name=shopPhone]:last'));
				} else{
					$('#tab-shop-settings [name=shopPhone]:first').clone().insertBefore($(this));
				}
				$('#tab-shop-settings [name=shopPhone]:last').val('');
			});

			$('#tab-shop-settings').on('click', '.addTimeWork', function() {
				$('#tab-shop-settings .timeWorkContainer:first').clone().insertAfter($('#tab-shop-settings .timeWorkContainer:last'));

				$('#tab-shop-settings .timeWorkContainer:last [name=timeWork]').val('');
				$('#tab-shop-settings .timeWorkContainer:last [name=timeWorkDays]').val('');
			});

			// проверка соединения с memcache
			$('#tab-shop-settings').on('click', '.memcache-conection', function() {
			 admin.ajaxRequest({
					mguniqueurl: "action/testMemcacheConection",
					host: $('input[name=cacheHost]').val(),
					port: $('input[name=cachePort]').val()
				},
				function(response) {
					admin.indication(response.status, response.msg);
				});
			});

			// Выбор картинки для логотипа сайта
			$('#tab-shop-settings').on('click', '.browseImageLogo', function() {
				admin.openUploader('settings_shop.getFile');
				$('.logo-img').show();
			});
			// Выбор заглушки изображений
			$('#tab-shop-settings').on('click', '.browseNoImgStub', function() {
				admin.openUploader('settings_shop.getNoImageStub');
				$('.no-img-stub').show();
			});
			// Выбор картинки для фона сайта
			$('#tab-shop-settings').on('click', '.browseBackgroundSite', function() {
				admin.openUploader('settings_shop.getBackground');
				$('.background-img').show();
			});
			 // Выбор фавиконки для  сайта
			$('#tab-shop-settings').on('change', 'input[name="favicon"]', function() {
				var img_container = $(this).parents('.upload-img-block');
				if($(this).val()) {
					settings_shop.addFavicon(img_container);
				}
			});

			$('#tab-shop-settings').on('change', '.watermarkform', function() {
				settings_shop.addWatermark();
			});

			// удаление фонового рисунка сайта
			$('#tab-shop-settings').on('click', '.remove-added-background', function() {
				$(this).hide();
				$('input[name="backgroundSite"]').val('');
				$('.background-img img').removeAttr().hide();
				$('.background-img').hide();
				$('[name=backgroundSiteLikeTexture]').closest('.js-settline-toggle').hide();
				$('[name=backgroundTextureSite]').closest('.js-settline-toggle').show();
			});

			// удаление заглушки изображений
			$('#tab-shop-settings').on('click', '.remove-added-no-img-stub', function() {
				const defaultStub = mgBaseDir + '/uploads/no-img.jpg';
				$(this).hide();
				$('input[name="noImageStub"]').val('');
				$('.section-settings .no-img-stub img').attr('src', defaultStub);
				$('.section-settings .no-img-stub').hide();
			});

			// удаление логотипа сайта
			$('#tab-shop-settings').on('click', '.remove-added-logo', function() {
				$(this).hide();
				$('input[name="shopLogo"]').val('');
				$('.logo-img img').removeAttr().hide();
				$('.logo-img').hide();
			});

			// проверка на ограничение количества выводимых товаров
			$('#tab-shop-settings').on('keyup', '.main-settings-list input[name=countRecomProduct],.main-settings-list input[name=countNewProduct],.main-settings-list input[name=countSaleProduct]', function() {
				if ($(this).val()>30) {
					$(this).val(30);
					admin.indication('error',lang.MAX_SHOWN_PROD);
				}
			});

			// отправка тестового письма
			$('#tab-shop-settings').on('click', '.email-conection', function() {
				admin.ajaxRequest({
					mguniqueurl: "action/testEmailSend",
				},
				function(response) {
					admin.indication(response.status, response.msg);
				});
			});

			// проверка префикса для мемкэш - длина не более 5 символов, и отсутствие спецсимволов и русских букв
			$('#tab-shop-settings').on('keyup', 'input[name=cachePrefix]', function() {
				// наименование не должно иметь специальных символов.
				if((!(/^[-0-9a-zA-Z]+$/.test($(this).val())) || $(this).val().length > 5) && $(this).val() != '') {
					$('input[name=cachePrefix]').addClass('error-input');
					admin.indication('error', lang.ERROR_CACHE_PREFIX);
				} else {
					$('input[name=cachePrefix]').removeClass('error-input');
				}
			});

			//Показываем поле ввода времени жизни сесии если выбрано сохранение в БД.
			$('#tab-shop-settings').on('change', 'input[name=sessionToDB]', function() {
				if ($(this).prop('checked')) {
					$('input[name=sessionLifeTime]').closest('.js-settline-toggle').show();
					settings_shop.checkInnoSuport();
				} else {
					$('input[name=sessionLifeTime]').closest('.js-settline-toggle').hide();
				}
			});

			// обработчик срабатывает при потере фокуса с поля с временем хранения сессии
			$('#tab-shop-settings').on('change', 'input[name="sessionLifeTime"]', function () {
				if($('input[name="sessionLifeTime"]').val() < 1440) $('input[name="sessionLifeTime"]').val(1440);
			});

			// начало пересоздания миниатюр
			$('#tab-shop-settings').on('click', '.recreateThumbs', function () {
				if (!confirm("Процесс создания миниатюр может занять длительное время. При остановке или прерывании процесса, необработанная часть изображений останется без изменений.\nПересоздать миниатюры?")) {return false;}
				$(this).removeClass('recreateThumbs').addClass('recreateThumbsDisabled');
				$('#tab-shop-settings #recreateThumbsLog').html('').closest('.sett-line').show();
				settings.callback = 'settings_shop.recreateThumbs';
				$('.section-settings .save-settings:visible').click();
				settings_shop.recreateThumbsLog("Запущен процесс создания миниатюр. Не уходите со страницы до завершения процесса.\n");
			});
			// начало удаление миниатюр
			$('#tab-shop-settings').on('click', '.destroyThumbs', function() {
				if (
					!confirm('Вы действительно хотите удалить все миниатюры товаров?')
				) {
					return false;
				}
				$('#tab-shop-settings #recreateThumbsLog').html('').closest('.sett-line').show();
				settings.callback = 'settings_shop.destroyThumbs';
				$('.section-settings .save-settings:visible').click();
				settings_shop.recreateThumbsLog('Запущен процесс удаления миниатюр. Не уходите со страницы до завершения процесса. \n');
			});
			//Начало создания фалов webp
			$('#tab-shop-settings').on('click', '.createWebp', function(){
				if (!confirm("Все изображения WEBP будут сохранены в папку uploads/webp/\nЕсли процесс будет прерван пользователем или сбоем на сервере, то при следующем запуске он продолжит работу именно с того изображения, на котором был остановлен. \nИзображения в формате WEBP, весят в среднем вдвое меньше оригиналов. Поэтому если все изображения в папке uploads/ весят 4ГБ, то для хранения их WEBP копий вам потребуется еще 2Гб свободного места на сервере!")) {return false;}
				$('#tab-shop-settings #recreateThumbsLog').html('').closest('.sett-line').show();
				settings.callback = 'settings_shop.countWebp';
				$('.section-settings .save-settings:visible').click();
				settings_shop.recreateThumbsLog("Запущен процесс создания WEBP изображений. Пожалуйста, не уходите со страницы до завершения процесса.\n");
			});

			//Удаление файлов Webp
			$('#tab-shop-settings').on('click', '.deleteWebp', function(){
				if(confirm('Вы действительно хотите удалить все изображения?')){
					admin.ajaxRequest({
						mguniqueurl: "action/deleteWebpDir",
					}, function(response){
						admin.indication(response.status, response.msg);
						location.reload(); 
					});
				}
			});
			//Скрытие кнопок "Пересоздать миниатюр" и "Webp"
			$('li[data-group="STNG_GROUP_4"] .section__desc input').on('change', function(){
				$('#tab-shop-settings .recreateThumbs').hide();
				$('#tab-shop-settings .createWebp').hide();
				$('#tab-shop-settings .recreateThumbs').hide();
			});

			//Скрытие селекта с выбором тем сайта 
			$('#tab-shop-settings select[name=sitetheme_select]').change( function() {
				if($(this).val() == 'Другое'){
					$(this).hide();
					$('#tab-shop-settings input[name=sitetheme_input]').show();
					$('#tab-shop-settings a.show_settings_select').show();
				}
			});

			//Показ селекта с выбором тематики сайта
			$('#tab-shop-settings').on('click', 'a.show_settings_select', function(){
				$(this).hide();
				$('#tab-shop-settings input[name=sitetheme_input]').val('');
				$('#tab-shop-settings input[name=sitetheme_input]').hide();
				$('#tab-shop-settings select[name=sitetheme_select]').show();
			});

			$('#tab-shop-settings').on('click', '.addCountStr', () => {
				$('.settings-count-str-container').append('<div class="settings-count-str"><input class="settings-count-str-num" placeholder="' + lang.COUNT_PRODUCT + '" type="number"><input class="settings-count-str-string" placeholder="' + lang.PLACEHOLDER_CONVERT_COUNT_TO_HR + '" type="text"><a class="removeCountStr link"><i class="fa fa-minus-circle" aria-hidden="true"></i>Удалить</a></div>')
			});
			$('#tab-shop-settings').on('click', '.removeCountStr', function() {
				$(this).closest('.settings-count-str').remove();
				createCountStr();
			});
			$('#tab-shop-settings').on('change', '.settings-count-str input', function (e) {
				if ($(this).val().indexOf(',') >= 0 || $(this).val().indexOf(':') >= 0) {
					$(this).val($(this).val().replace(',', ''));
					$(this).val($(this).val().replace(':', ''));
				}
				createCountStr();
			});
			const createCountStr = function () {
				result = '';
				$('#tab-shop-settings').find('.settings-count-str').each((index, item) => {

					const num = $(item).find('.settings-count-str-num').val();
					const str = $(item).find('.settings-count-str-string').val()

					if (num !== '' && str != '') {
						result += num + ':' +  str + ',';
					}
				});
				$('.settings-count-str-input').val(result.slice(0, -1));
			}

		},
		// функция удаления миниатюр
		destroyThumbs:  function(nextItem, total_count) {
			if (typeof nextItem == 'undefined') {
				nextItem = 0;
			}
			admin.ajaxRequest(
				{
					mguniqueurl: 'action/destroyThumbs',
					nextItem: nextItem,
					total_count: total_count,
				},
				function (response) {
					settings_shop.recreateThumbsLog(response.data.log);
					if (response.data.percent < 100) {
						settings_shop.recreateThumbsLog('Обработано '+response.data.percent+'% товаров \n');
						settings_shop.destroyThumbs(response.data.nextItem, response.data.total_count);
					} else {
						settings_shop.recreateThumbsLog('Удаление миниатюр завершено.');
					}
				}
			);
		},
		// функция пересоздания миниатюр
		recreateThumbs: function (nextItem, total_count, imgCount) {
			if (typeof nextItem == 'undefined') {
				nextItem = 0;
			}
			admin.ajaxRequest({
				mguniqueurl: "action/startGenerationImagePreview",
				nextItem: nextItem,
				total_count: total_count,
				imgCount: imgCount,
				productFolder: 1
			},
			function (response) {
				settings_shop.recreateThumbsLog(response.data.log);
				if (response.data.percent < 100) {
					settings_shop.recreateThumbsLog("Обработано "+response.data.percent+"% товаров.\n");
					settings_shop.recreateThumbs(response.data.nextItem, response.data.total_count, response.data.imgCount);
				} else {
					settings_shop.recreateThumbsLog("Обработка изображений товаров завершена.");
					$('#tab-shop-settings .recreateThumbsDisabled').removeClass('recreateThumbsDisabled').addClass('recreateThumbs');
				}
			});
		},
		// Функция подсчета кол-ва изображений для webp
		countWebp: function(reset = true) {
			admin.ajaxRequest(
				{
					mguniqueurl: "action/startGenerationImageWebp",
					reset: reset,
				},
				function (response) {
					admin.indication(response.status, response.msg);
					if (response.status === 'success') {
						if (response.data.errorImage) {
							settings_shop.recreateThumbsLog('Во время предыдущего создания webp не удалось обработать изображение: '+response.data.errorImage+'\n');
						}
						if (response.data.total) {
								settings_shop.recreateThumbsLog("Всего "+response.data.total+" изображений.\n");
								settings_shop.recreateThumbsLog("Подсчет окончен\n");
								settings_shop.recreateThumbsLog("Начинаем перевод изображений...\n");
								return settings_shop.convertImgToWebp();
						}
						settings_shop.recreateThumbsLog('Подсчёт изображений...\n');
						return settings_shop.countWebp(false);
					}
				}
			);	
		},
		// Функция проверки, на случай неуспешного импорта изображений в webp
		checkImportWebp:function(){
			if(cookie('countWebpStatus') == 'false_status'){
				if(confirm('Произошла ошибка при конвертации изображений в Webp на этапе подсчета. Продолжить конвертацию?')){
					$('#tab-shop-settings .accordion-item[data-group="STNG_GROUP_4"] .accordion-content').show();
					$('#tab-shop-settings #recreateThumbsLog').html('').closest('.sett-line').show();
					settings_shop.recreateThumbsLog("Продолжается подсчет...\n");
					settings_shop.countWebp();
				}else{
					cookie('countWebpStatus', 'true_status');
					cookie('countWebpDir', 'true_status');
				}
			}
			if(cookie('convertImgToWebpStatus') == 'false_status'){
				if(confirm('Произошла ошибка при конвертации изображений в Webp на этапе конвертации. Продолжить конвертацию?')){
					$('#tab-shop-settings .accordion-item[data-group="STNG_GROUP_4"] .accordion-content').show();
					$('#tab-shop-settings #recreateThumbsLog').html('').closest('.sett-line').show();
					settings_shop.recreateThumbsLog("Продолжается перевод изображений...\n");
					settings_shop.convertImgToWebp(cookie('convertImgToWebpDir'));
				}else{
					cookie('convertImgToWebpStatus', 'true_status');
					cookie('convertImgToWebpDir', 'true_status');
				}
			}
		},
		// Функция перевода изображений в webp
		convertImgToWebp:function(reset = true){
			admin.ajaxRequest({
				mguniqueurl: "action/convertImgToWebp",
				reset: reset,
			},
			function(response){
				if (response.data.log) {
					settings_shop.recreateThumbsLog(response.data.log);
				}
				if(typeof(response.data.percent) === 'undefined'){
					settings_shop.recreateThumbsLog("Перевод изображений окончен.\n");
					return;
				}
				settings_shop.convertImgToWebp(false);
			});
		},
		// лог пересоздания миниатюр
		recreateThumbsLog: function (text) {
			$("#tab-shop-settings #recreateThumbsLog").append(text).animate({scrollTop:$("#tab-shop-settings #recreateThumbsLog")[0].scrollHeight - $("#tab-shop-settings #recreateThumbsLog").height()},1,function(){});
		},
		/**
		 * функция для приема файла из аплоадера, для сохранения в путь логотипа сайта
		 */
		getFile: function(file) {
			var dir = file.url;
			$('.section-settings input[name="shopLogo"]').val(dir);
			$('.section-settings .logo-img img').attr('src',dir).show();
			$('.section-settings .logo-img .remove-added-logo').show();
		},
		/**
		 * функция для приема заглушки изображений
		 */
		getNoImageStub: function(file) {
			const dir = file.path;
			$('.section-settings input[name="noImageStub"]').val('/' + dir);
			$('.section-settings .no-img-stub img').attr('src', mgBaseDir + '/' + dir).show();
			$('.section-settings .no-img-stub .remove-added-no-img-stub').show();
		},
		/**
		 * функция для приема фонового изобрадения для сайта
		 */
		getBackground: function(file) {
			var dir = file.url;
			$('.section-settings input[name="backgroundSite"]').val(dir);
			$('.section-settings .background-img img').attr('src',dir).show();
			$('.section-settings .background-img .remove-added-background').show();
			$('[name=backgroundSiteLikeTexture]').closest('.js-settline-toggle').show();
			$('[name=backgroundTextureSite]').closest('.js-settline-toggle').hide();
		},
		/**
		 * функция замены фавикона
		 */
		addFavicon: function(img_container) {
			// отправка картинки на сервер
			img_container.find('.imageform').ajaxForm({
				type:"POST",
				url: "ajax",
				data: {
					mguniqueurl:"action/updateFavicon"
				},
				cache: false,
				dataType: 'json',
				success: function(response) {
					if (response.status=='error') {
						admin.indication(response.status, response.msg);
					} else {
						img_container.find('#favicon-image').attr('src', admin.SITE+'/'+response.data.img+'?t='+Math.random());
						img_container.find('.option').val(response.data.img); 
					}
				 }
			}).submit();
		},
		/**
		 * Загружает водяной знак
		 */
		addWatermark: function() {
			$('.watermarkform').ajaxForm({
				type:"POST",
				url: "ajax",
				data: {
					mguniqueurl:"action/updateWaterMark"
				},
				cache: false,
				dataType: 'json',
				success: function(response) {
					admin.indication(response.status, response.msg);
					$('.watermark-img').html("");
					$('.watermark-img').html("<img src='"+admin.SITE+'/uploads/watermark/watermark.png?='+parseInt(new Date().getTime()/1000)+"'/>");
				}
			}).submit();
		},
		checkInnoSuport: function() {
			$.ajax({
				type: "POST",
				url: mgBaseDir + "/ajax",
				data: {
					mguniqueurl: "action/checkInnoSuport",
				},
				cache: false,
				dataType: "json",
				success: function (response) {
					$('.innoDbError').detach();
					if(response.data != true) {
						$('[name=sessionToDB]').parent().parent().append('<span class="innoDbError" style="font-size:11px;display:block;line-height:1.4;">Для сохранения сессий в базу данных, необходим InnoDB.<br>\
							Ваша база данных не поддерживает движок InnoDB.<br>\
							Обратитесть к вашему хостингу, для включения поддержки InnoDB вашей базой данных.</span>');
						$('[name=sessionToDB]').prop('checked', false).trigger('change');
						$('[name=sessionLifeTime]').closest('.row').hide();
					}
				}
			});
		},
	};
})();
