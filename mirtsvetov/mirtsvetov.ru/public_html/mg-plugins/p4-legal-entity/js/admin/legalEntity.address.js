var LegalEntityAddress = (function() {

    return {

        add: '',
        edit: '',
        delete: '',

        init: function()
        {
            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-add-address', function() {
                LegalEntityAddress.add = $(this);
                let $button = $(this),
                    action = 'getModalAddWindow';
                LegalEntityAddress.openModalWindow($button, action);
			});

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-edit-address', function() {
                LegalEntityAddress.edit = $(this);
                let $button = $(this),
                    action = 'getModalEditWindow';
                LegalEntityAddress.openModalWindow($button, action);
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-save-address', function() {
                let $button = $(this);
                LegalEntityAddress.saveEntity($button);
                return false;
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-delete-address', function() {
                LegalEntityAddress.delete = $(this);
                let $button = $(this),
                    action = 'getModalDeleteWindow';
                LegalEntityAddress.openModalWindow($button, action);
            });
            
            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-confirm-delete-address', function() {
                let $button = $(this);
                LegalEntityAddress.deleteEntity($button);
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-default-address', function() {
                let $button = $(this);
                LegalEntityAddress.defaultEntity($button);
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

            let id = $button.data('id'),
                legal_id = $button.data('legal-id'),
                user_id = $button.data('user-id');

            admin.ajaxRequest({
                pluginHandler: PluginLegalEntity.pluginName,
                actionerClass: 'PactionerAddress',
                action: action,
                id: id,
                legal_id: legal_id,
                user_id: user_id
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

            let inputs = $button.parents('form').serializeArray();

            if (!Validation.validation(inputs)) {
                PluginLegalEntity.enableButton($button);
                return false;
            }

            let id = $button.data('id'),
                legal_id = $button.data('legal-id'),
                user_id = $button.data('user-id');

            $button.parents('form').ajaxSubmit({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerAddress',
                    action: 'saveEntity',
                    id: id,
                    legal_id: legal_id,
                    user_id: user_id
                },
                dataType: 'json',
                cache: false,
                success: function(response) {

                    if (response.status === 'success') {
                       if (response.data.action === 'add') {
                            let $content = LegalEntityAddress.add.parent().siblings('.js-address-content');
                            $content.append('<div class="element js-address-item">' + response.data.html + '</div>');
                            PluginLegalEntity.closeModalWindow($button);
                        }
                        if (response.data.action === 'edit') {
                            $item = LegalEntityAddress.edit.parents('.js-address-item');
                            $item.empty().append(response.data.html);
                            PluginLegalEntity.closeModalWindow($button);
                        }
                    } 
                    
                    if (response.status === 'error') {
                        let $section = $('.section-'+ PluginLegalEntity.pluginName),
                            html = response.data.html;

                        if (typeof html !== 'undefined') {
                            $section.append(response.data.html);
                            $section.find('.js-modal-close').focus();
                        }

                        let errors = response.data.errors;
                        if (typeof errors !== 'undefined') {
                            Validation.printErrors(errors);
                        } 

                        PluginLegalEntity.enableButton($button);
                    }

                    admin.indication(response.status, response.msg);
                }
            });
        },

        /**
         * 
         * Удаляет запись.
         * 
         * @param {object} $button
         * 
         */
        deleteEntity: function($button)
        {
            if (!PluginLegalEntity.disableButton($button)) {
                return false;
            }

            let id = $button.data('id'),
                legal_id = $button.data('legal-id'),
                user_id = $button.data('user-id');

            admin.ajaxRequest({
                pluginHandler: PluginLegalEntity.pluginName,
                actionerClass: 'PactionerAddress',
                action: 'deleteEntity',
                id: id,
                legal_id: legal_id,
                user_id: user_id
            }, function(response) {
                
                let $section = $('.section-'+ PluginLegalEntity.pluginName);

                if (response.status === 'success') {
                    let $item = LegalEntityAddress.delete.parents('.js-address-item');
                    $item.remove();
                    PluginLegalEntity.closeModalWindow($button);
                }
                
                if (response.status === 'error') {
                    $section.append(response.data.html);
                    $section.find('.js-modal-close').focus();
                    PluginLegalEntity.enableButton($button);
                }

                admin.indication(response.status, response.msg);
            });
        },

        /**
         * 
         * Устанавливает запись по умолчанию.
         * 
         * @param {object} $button
         * 
         */
        defaultEntity: function($button)
        {
            if (!PluginLegalEntity.disableButton($button)) {
                return false;
            }

            let id = $button.data('id'),
                legal_id = $button.data('legal-id'),
                user_id = $button.data('user-id'),
                page = PluginLegalEntity.getCurrentPage();

            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerAddress',
                    action: 'defaultAddress',
                    id: id,
                    legal_id: legal_id,
                    user_id: user_id,
                    page: page
                },
                dataType: 'json',
                cache: false,
                success: function(response) {

                    let $section = $('.section-'+ PluginLegalEntity.pluginName);

                    if (response.status === 'success') {
                        $section.find('.js-legals-content').empty();
                        $section.find('.js-legals-content').append(response.data.html);
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

LegalEntityAddress.init();