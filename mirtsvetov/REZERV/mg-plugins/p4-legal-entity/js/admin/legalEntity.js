var LegalEntity = (function() {

    return {

        edit: '',
        delete: '',
        
        init: function()
        {
            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-element-tab', function(e) {
				e.preventDefault();
				let anchor = $(this).data('anchor');
				$('.dropdown[dropdown-group="'+ anchor +'"]').addClass('dropdown_active');
				$('.dropdown[dropdown-group="'+ anchor +'"]').siblings().removeClass('dropdown_active');
				$(this).closest('.element').addClass('element_active');
				$(this).closest('.element').siblings().removeClass('element_active');
			});

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-view-legal', function() {
                let $button = $(this),
                    action = 'getModalViewLegalEntities';
				LegalEntity.openModalWindow($button, action);
			});

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-add-legal', function() {
                let $button = $(this),
                    action = 'getModalAddLegalEntity';
				LegalEntity.openModalWindow($button, action);
			});

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-edit-legal', function() {
                LegalEntity.edit = $(this);
                let $button = $(this),
                    action = 'getModalEditLegalEntity';
				LegalEntity.openModalWindow($button, action);
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-save-legal', function() {
                let $button = $(this);
                LegalEntity.saveEntity($button);
                return false;
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-delete-legal', function() {
                LegalEntity.delete = $(this);
                let $button = $(this),
                    action = 'getModalDeleteLegalEntity';
                LegalEntity.openModalWindow($button, action);
            });
            
            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-confirm-delete-legal', function() {
                let $button = $(this);
                LegalEntity.deleteEntity($button);
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-default-legal', function() {
                let $button = $(this);
                LegalEntity.defaultEntity($button);
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
                user_id = $button.data('user-id'),
                page = PluginLegalEntity.getCurrentPage();

            admin.ajaxRequest({
                pluginHandler: PluginLegalEntity.pluginName,
                actionerClass: 'PactionerLegalEntity',
                action: action,
                id: id,
                user_id: user_id,
                page: page
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
                user_id = $button.data('user-id'),
                page = PluginLegalEntity.getCurrentPage();
            
            $button.parents('form').ajaxSubmit({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerLegalEntity',
                    action: 'saveLegalEntity',
                    id: id,
                    user_id: user_id,
                    page: page
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    let $section = $('.section-'+ PluginLegalEntity.pluginName);

                    if (response.status === 'success') {
                       if (response.data.action === 'add') {
                            $section.find('.js-legals-content').empty();
                            $section.find('.js-legals-content').append(response.data.html);
                            PluginLegalEntity.closeModalWindow($button);
                        }
                        if (response.data.action === 'edit') {
                            let $item = LegalEntity.edit.parents('.js-legal-item');
                            $item.empty().append(response.data.html);
                            PluginLegalEntity.closeModalWindow($button);
                            if (page === 'legalEntities') {
                                let = $tr = $section.find('tr[data-id='+ response.data.legal.id +']');
                                $tr.html(response.data.html_td); 
                            }
                        }
                    } 
                    
                    if (response.status === 'error') {
                        let html = response.data.html;
                        if (typeof html !== "undefined") {
                            $section.append(response.data.html);
                            $section.find('.js-modal-close').focus();
                        }
                        let errors = response.data.errors;
                        if (typeof errors !== "undefined") {
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
         * Удалить запись
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
                user_id = $button.data('user-id'),
                page = PluginLegalEntity.getCurrentPage();

            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerLegalEntity',
                    action: 'deleteLegalEntity',
                    id: id,
                    user_id: user_id,
                    page: page
                },
                dataType: 'json',
                cache: false,
                success: function(response) {

                    let $section = $('.section-'+ PluginLegalEntity.pluginName);

                    if (response.status === 'success') {

                        if (page === 'legalEntities') {
                            PluginLegalEntity.closeModalWindow($button);
                            PluginLegalEntity.closeModalWindow(LegalEntity.delete);
                            admin.refreshPanel();
                        }

                        if (page === 'users') {
                            $section.find('.js-legals-content').empty();
                            $section.find('.js-legals-content').append(response.data.html);
                            PluginLegalEntity.closeModalWindow($button);
                        }
                    }
                    
                    if (response.status === 'error') {
                        $section.append(response.data.html);
                        $section.find('.js-modal-close').focus();
                        PluginLegalEntity.enableButton($button);
                    }

                    admin.indication(response.status, response.msg);
                }
            });
        },

        /**
         * 
         * По умолчанию
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
                user_id = $button.data('user-id'),
                page = PluginLegalEntity.getCurrentPage();

            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerLegalEntity',
                    action: 'defaultLegalEntity',
                    id: id,
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

LegalEntity.init();