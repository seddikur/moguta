var PluginUserBind = (function() {

    return {

        edit: '',
        searchText: null,
        
        init: function()
        {
            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-user-bind', function() {
                let $button = $(this);
                PluginUserBind.openModalWindow($button);
			});

			$('.admin-center').on('keyup', '.section-' + PluginLegalEntity.pluginName +' input[name=search_user]', function() {
                let value = $(this).val();
				PluginUserBind.searchUser(value);
			});

            $('.admin-center').on('click', '.section-' + PluginLegalEntity.pluginName +' .js-select-user', function() {
                let $this = $(this);
				PluginUserBind.selectUser($this);
			});

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .js-save-user-bind', function() {
                let $button = $(this);
                PluginUserBind.saveUserBind($button);
                return false;
            });
        },

        /**
         * 
         * Возвращает модельное окно привязки пользователя к оргиназиции.
         * 
         * @param {object} $button
         * @param {string} action
         * 
         */
        openModalWindow: function($button)
        {
            if (!PluginLegalEntity.disableButton($button)) {
                return false;
            }

            PluginLegalEntity.setPosition();
            
            let legal_id = $button.data('legal-id'),
                user_id = $button.data('user-id');

            admin.ajaxRequest({
                pluginHandler: PluginLegalEntity.pluginName,
                actionerClass: 'PactionerBind',
                action: 'getModalUserBind',
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
         * Возвращает результат поиска пользователя.
         * 
         * @param {string} text
         * 
         * @return {boolean}
         * 
         */
        searchUser: function(text)
        {
            if (PluginUserBind.searchText === text) {
                return false;
            }

            PluginUserBind.searchText = text;

            let $section = $('.section-'+ PluginLegalEntity.pluginName);

            if (text.length >= 3) {
                admin.ajaxRequest({
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerBind',
                    action: 'searchUser',
                    text: text,
                }, function(response) {

                    let html = '';

                    function buildElements(element, index, array) {
                        html += '\
                            <div class="user__search-item js-select-user" data-user-id="'+ element.id +'">\
                                <div class="user__search-title">'+ element.sname +' '+ element.name +' '+ element.pname +', '+ element.login_email +'</div>\
                            </div>\
                        ';
                    }

                    if (response.status === 'success' && response.data) {
                        response.data.forEach(buildElements);
                        $section.find('.js-user-search-result').html(html).show();
                    }

                });
                
            } else {
                $section.find('.js-user-search-result').hide().empty();
            }
            
           return true;
        },

        /**
         * 
         * Выбор пользователя для последующей привязки.
         * 
         * @param {object} $this
         * 
         */
        selectUser: function($this)
        {
            let $section = $('.section-'+ PluginLegalEntity.pluginName);
            $section.find('.js-user-search-result').hide().empty();
            $section.find('input[name=search_user]').val('');

            let user_id = $this.data('user-id'),
                user = PluginUserBind.getUserData(user_id);

            let html = $(
				'<div class="user__selected info">\
					<div class="info__item"><span><b>'+ PluginLegalEntity.lang['MODAL_USER_ID'] +'</b> '+ user.id +'</span></div>\
                    <div class="info__item"><span><b>'+ PluginLegalEntity.lang['MODAL_USER_LOGIN_EMAIL'] +'</b> '+ user.login_email +'</span></div>\
                    <div class="info__item"><span><b>'+ PluginLegalEntity.lang['MODAL_USER_INITIALS'] +'</b> '+ user.sname +' '+ user.name +' '+ user.pname +'</span></div>\
					<div style="display: none">\
						<input type="hidden" name="user_id" value="'+ user.id +'" />\
					</div>\
				</div>'
			);
			$section.find('.js-user-container').empty().append(html);
        },

        /**
		 * 
		 * Возвращает данные пользователя.
		 * 
         * @param {number} user_id
         * 
         * @returns {array}
         * 
		 */
        getUserData(user_id)
        {
            let result = null;

            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerBind',
                    action: 'getUserData',
                    user_id: user_id
                },
                dataType: 'json',
                cache: false,
                async: false,
                success: function(response) {
                    result = response.data;
                }
            });

            return result;
        },

        /**
         * 
         * Сохраняет привязку пользователя к организации и адресам доставки.
         * 
         * @param {object} $button
         * 
         */
        saveUserBind: function($button)
        {
            if (!PluginLegalEntity.disableButton($button)) {
                return false;
            }

            let user_id = $button.parents('form').find('input[name=user_id]').val();

            if (!Validation.user(user_id)) {
                PluginLegalEntity.enableButton($button);
                return false;
            }

            let legal_id = $button.data('legal-id'),
                page = PluginLegalEntity.getCurrentPage();

            $button.parents('form').ajaxSubmit({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerBind',
                    action: 'saveUserBind',
                    legal_id: legal_id,
                    page: page
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    
                    let $section = $('.section-'+ PluginLegalEntity.pluginName),
                        $tr = $section.find('tr[data-id='+ response.data.legal.id +']');

                    if (response.status === 'success') {
                        if ($section.find('.js-modal-view').length) {
                            $section.find('.js-info-user-content').empty().append(response.data.html.user);
                            $section.find('.js-legals-content').empty().append(response.data.html.legal);
                        }
                        $tr.html(response.data.html.td);
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

PluginUserBind.init();