var PluginLegalEntity = (function() {

    return {

		pluginName: 'p4-legal-entity',
        
        init: function()
        {
            $('body').on('click', '.js-modal-close, .js-modal-cancel', function() {
                let $button = $(this);
                PluginLegalEntity.closeModalWindow($button);
            });
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
         */
        removePosition: function()
        {
            if ($('body').find('.p-modal').length) {
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
        }
    }
})();
    
PluginLegalEntity.init();