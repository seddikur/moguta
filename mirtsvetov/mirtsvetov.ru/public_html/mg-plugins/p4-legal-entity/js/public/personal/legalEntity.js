var LegalEntityPersonal = (function() {

    return {
        
        init: function()
        {
            $('body').on('click', '.js-element-tab', function(e) {
				e.preventDefault();
				let anchor = $(this).data('anchor');
				$('.dropdown[dropdown-group="'+ anchor +'"]').addClass('dropdown_active');
				$('.dropdown[dropdown-group="'+ anchor +'"]').siblings().removeClass('dropdown_active');
				$(this).closest('.element').addClass('element_active');
				$(this).closest('.element').siblings().removeClass('element_active');
			});

			$('body').on('click', '.js-default-legal', function() {
                let $button = $(this);
                LegalEntityPersonal.defaultEntity($button);
            });
        },

		defaultEntity: function($button)
		{
			if (!PluginLegalEntity.disableButton($button)) {
				return false;
			}

			let id = $button.data('id');

			$.ajax({
				type: 'POST',
				url: mgBaseDir + '/ajaxrequest',
				data: {
					pluginHandler: PluginLegalEntity.pluginName,
					actionerClass: 'PactionerLegalEntity',
					action: 'defaultLegalEntity',
					id: id
				},
				dataType: 'json',
				cache: false,
				success: function(response) {
					if (response.status === 'success') {
						$('.legals').remove();
						$('.tab[data-tab="legal"]').append(response.data.html);
					}
					if (response.status === 'error') {
						PluginLegalEntity.setPosition();
						$('body').append(response.data.html);
						$('body').find('.js-modal-close').focus();
						PluginLegalEntity.enableButton($button);
					}
				}
			});
		}
    }
})();
    
LegalEntityPersonal.init();