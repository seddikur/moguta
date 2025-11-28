var LegalEntityDebt = (function() {

    return {

        edit: '',

        init: function()
        {
            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-edit-debt', function() {
                LegalEntityDebt.edit = $(this);
                let $button = $(this),
                    action = 'getModalEditWindow';
                LegalEntityDebt.openModalWindow($button, action);
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-save-debt', function() {
                let $button = $(this);
                LegalEntityDebt.saveEntity($button);
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
                legal_id = $button.data('legal-id');

            admin.ajaxRequest({
                pluginHandler: PluginLegalEntity.pluginName,
                actionerClass: 'PactionerDebt',
                action: action,
                id: id,
                legal_id: legal_id
            }, function(response) {

                let $section = $('.section-'+ PluginLegalEntity.pluginName);

                $section.append(response.data.html);
                $section.find('.js-modal-close').focus();

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

            let id = $button.data('id'),
                legal_id = $button.data('legal-id');

            $button.parents('form').ajaxSubmit({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerDebt',
                    action: 'saveEntity',
                    id: id,
                    legal_id: legal_id
                },
                dataType: 'json',
                cache: false,
                success: function(response) {

                    let $section = $('.section-'+ PluginLegalEntity.pluginName);

                    if (response.status === 'success') {
                        $item = LegalEntityDebt.edit.parents('.js-debt-item');
                        $item.empty().append(response.data.html);
                        PluginLegalEntity.closeModalWindow($button);
                    }
                    
                    if (response.status === 'error') {
                        $section.append(response.data.html);
                        $section.find('.js-modal-close').focus();
                        PluginLegalEntity.enableButton($button);
                    }

                    admin.indication(response.status, response.msg);
                }
            });
        }
    }
})();

LegalEntityDebt.init();