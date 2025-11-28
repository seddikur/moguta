var PluginLegalEntity = (function() {

	return {

		pluginName: 'p4-legal-entity',
		lang: [],
		timepickerRu: null,

		init: function() {

			admin.ajaxRequest({
				mguniqueurl: "action/seLocalesToPlug",
				pluginName: PluginLegalEntity.pluginName
			}, function(response) {
				PluginLegalEntity.lang = response.data;
			});

			PluginLegalEntity.timepickerRu = {
				timeOnlyTitle: 'Выберите время',
				timeText: 'Время:',
				hourText: 'Часы:',
				minuteText: 'Миниты:',
				secondText: 'Секунды:',
				currentText: 'Сейчас',
				closeText: 'Применить',
				showSeconds: false,
				timeFormat: 'hh:mm',
				range: true,
				isRTL: false,
				//При использование на одной странице Uploader'а и DateTimePicker'а происходит конфликт стилей
				//Для исправления конфликта при запуске DateTimePicker'а отключаем подключенный стиль, а при открытие
				//Uploader'а включаем обратно (в script.js)
				beforeShow: function(input, inst) {
					$('#ui-datepicker-div').next().prop('disabled', true);
				}
			};

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-tabs-link', function() {
				if (!$(this).parent('li').hasClass('is-active')) {
					let $tab = $('.tabs-title'),
						$parent = $(this).parent('li'),
						count = $tab.index($parent) + 1;
					cookie(PluginLegalEntity.pluginName + '_tab', count);
					admin.refreshPanel();
				}
			});

			$('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-modal-close, .js-modal-cancel', function() {
				let $button = $(this);
				PluginLegalEntity.closeModalWindow($button);
            });

			$('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .show-filters', function() {
				$('.section-'+ PluginLegalEntity.pluginName).find('.filter-container').slideToggle();
			});

			$('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .filter-now', function() {
				PluginLegalEntity.getByFilter();
				return false;
			});

			$('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .refreshFilter', function() {
				admin.show(PluginLegalEntity.pluginName, "plugin", "refreshFilter=1");
				return false;
			});

			$('.admin-center').on('change', '.section-'+ PluginLegalEntity.pluginName +' .countPrintRowsEntity', function() {
				var count = $(this).val();
				admin.ajaxRequest({
					mguniqueurl: "action/setCountPrintRowsEnity",
					pluginHandler: PluginLegalEntity.pluginName,
					option: PluginLegalEntity.pluginName + '-countPrintRows',
					count: count
				}, function(response) { 
					admin.indication(response.status, response.msg);
					if (response.status === "success") {
						admin.refreshPanel();
					}
				})
			});
		},

		/**
		 *
		 * Функция для применения фильтра
		 *
		 * @return {bool}
		 * 
		 */
		getByFilter: function() {
			let request = $('.section-'+ PluginLegalEntity.pluginName +' form[name=filter]').formSerialize();
			admin.show(PluginLegalEntity.pluginName, "plugin", request + '&applyFilter=1');
			return false;
		},

		/**
		 * 
		 * Закрывает модальное окно.
		 * 
		 * @param {object} $button 
		 * 
		 */
		closeModalWindow: function($button) {
			$button.parents('.p-modal').remove();
			PluginLegalEntity.removePosition();
		},

		/**
         * 
         * Блокирует body при открытии модального окна.
         * 
         * <body data-pos="600" style="top: -600px; left: 0px; right: 0px; position: fixed;">
         * 
		 * @return {bool}
		 * 
         */
		setPosition: function()
		{
			if (typeof $('body').attr('data-position') !== 'undefined') {
				return false;
			}
			
			$('body').attr('data-position', $(window).scrollTop());
			$('body').css({
				'top' : '-' + $(window).scrollTop() + 'px',
				'left': '0px',
				'right': '0px',
				'position':'fixed'
			});
		},

		/**
		 * 
		 * Разблокирует body при закрытии модального окна.
		 * 
		 * @return {bool}
		 * 
		 */
		removePosition: function()
		{
			let $section = $('.section-'+ PluginLegalEntity.pluginName);

			if ($section.find('.p-modal').length) {
				return false;
			}

			$('body').removeAttr('style');
			$(window).scrollTop($('body').attr('data-position'));
			$('body').removeAttr('data-position');
		},

		/**
		 * 
		 * Блокирует кнопку от повторного действия.
		 * 
		 * @param {object} $button
		 * 
		 * @return {bool}
		 * 
		 */
		disableButton: function($button)
		{
			if ($button.hasClass('button-disabled')) {
				return false;
			}

			$button.addClass('button-disabled');

			return true;
		},
 
		/**
		 * 
		 * Разблокирует кнопку.
		 * 
		 * @param {object} $button
		 * 
		 * @return {bool}
		 * 
		 */
		enableButton: function($button)
		{
			$button.removeClass('button-disabled');
			return true;
		},

		/**
		 * 
		 * Получить текущую страницу плагина.
		 * 
		 * @return {string}
		 * 
		 */
		getCurrentPage: function()
		{	
			let page;

			if ($('.js-page-users').length) {
                page = 'users';
            } else if ('.js-page-legal-entities') {
                page = 'legalEntities';
            }

			return page;
		}
		
	}
})();

PluginLegalEntity.init();