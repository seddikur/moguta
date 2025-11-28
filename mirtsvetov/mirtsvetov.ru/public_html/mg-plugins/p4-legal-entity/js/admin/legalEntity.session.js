var LegalEntitySession = (function() {

    return {

        init: function()
        {
            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-add-session', function() {
                let $button = $(this),
                    action = 'getModalAddWindow';
                LegalEntitySession.openModalWindow($button,action);
			});

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-edit-session', function() {
                let $button = $(this),
                    action = 'getModalEditWindow';
                LegalEntitySession.openModalWindow($button, action);
			});

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-save-session', function() {
                let $button = $(this);
                LegalEntitySession.saveEntity($button);
                return false;
            });
        },

		/**
         * 
         * Открывает модальное окно.
         * 
         * @param {object} $button
         * @param {string} action
         * 
         */
        openModalWindow: function($button, action)
        {
            if (!PluginLegalEntity.disableButton($button)) {
                return false;
            }

            PluginLegalEntity.setPosition();

            let id = $button.data('id');
                user_id = $button.data('user-id');

            admin.ajaxRequest({
                pluginHandler: PluginLegalEntity.pluginName,
                actionerClass: 'PactionerSession',
                action: action,
                id: id,
                user_id: user_id
            }, function(response) {

                let $section = $('.section-'+ PluginLegalEntity.pluginName);

                $section.append(response.data.html);
                $section.find('.js-modal-close').focus();

                if (response.status === 'success') {
                    $('.date-from-input').timepicker(PluginLegalEntity.timepickerRu);
                    $('.date-before-input').timepicker(PluginLegalEntity.timepickerRu);
                }

                if (response.status === 'error') {
                    admin.indication(response.status, response.msg);
                }

                PluginLegalEntity.enableButton($button);
            });
		},

        /**
         * 
         * Сохраняет запись.
         * 
         * @param {object} $button
         * 
         */
        saveEntity: function($button)
        {
            if (!PluginLegalEntity.disableButton($button)) {
                return false;
            }

            let inputs = $button.parents('form').serializeArray();

            if (!Validation.time(inputs)) {
                PluginLegalEntity.enableButton($button);
                return false;
            }

            let id = $button.data('id'),
                user_id = $button.data('user-id');

            $button.parents('form').ajaxSubmit({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerSession',
                    action: 'saveEntity',
                    id: id,
                    user_id: user_id
                },
                dataType: 'json',
                cache: false,
                success: function(response) {

                    if (response.status === 'success') {
                        PluginLegalEntity.closeModalWindow($button);
                        admin.refreshPanel();
                    }

                    if (response.status === 'error') {
                        PluginLegalEntity.enableButton($button);
                    }

                    admin.indication(response.status, response.msg);
                }
            });
        }
    }
})();

LegalEntitySession.init();