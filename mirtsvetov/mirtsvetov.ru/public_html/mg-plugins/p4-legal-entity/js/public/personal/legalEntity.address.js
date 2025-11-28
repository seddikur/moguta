var LegalEntityAddressPersonal = (function() {

    return {

        init: function()
        {
            $('body').on('click', '.js-default-address', function() {
                let $button = $(this);
                LegalEntityAddressPersonal.defaultEntity($button);
            });
        },

		defaultEntity: function($button)
		{
			if (!PluginLegalEntity.disableButton($button)) {
				return false;
			}

            let id = $button.data('id'),
                legal_id = $button.data('legal-id');

			$.ajax({
				type: 'POST',
				url: mgBaseDir + '/ajaxrequest',
				data: {
					pluginHandler: PluginLegalEntity.pluginName,
					actionerClass: 'PactionerAddress',
					action: 'defaultAddress',
                    id: id,
					legal_id: legal_id
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

LegalEntityAddressPersonal.init();