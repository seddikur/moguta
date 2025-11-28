var LegalEntityAdressOrder = (function() {

    return {
        
        init: function()
        {
			$('body').on('change', '.select-legal-entity-address-order', function() {
				const selectedOption = $(this).find('option:selected');
				const optionId = selectedOption.attr('id');
				LegalEntityAdressOrder.selectEntity(optionId);
			});
        },

		selectEntity: function(optionId)
		{
			$.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerAddress',
                    action: 'selectAddressOrder',
                    id: optionId,
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    if (response.status === 'success') {
                        let $content = $('.js-order-address');
                        $content.empty().append(response.data.html);
                    }
                }
            });
		}
    }
})();

LegalEntityAdressOrder.init();