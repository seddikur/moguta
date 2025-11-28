var LegalEntityOrder = (function() {

    return {
        
        init: function()
        {
			$('body').on('change', '.select-legal-entity-order', function() {
				const selectedOption = $(this).find('option:selected');
				const optionId = selectedOption.attr('id');
				LegalEntityOrder.selectEntity(optionId);
			});
        },

		selectEntity: function(optionId)
		{
			$.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerLegalEntity',
                    action: 'selectLegalEntityOrder',
                    id: optionId
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    if (response.status === 'success') {
                        const $content = $('.js-legal');
                        $content.empty().append(response.data.html.content);
						
						const $wrapper = $('.status-block').parent();

                        $wrapper.find('.status-block').remove();
						$wrapper.prepend(response.data.html.statusBlock);

                        $wrapper.find('.manager-info').remove();
                        $wrapper.find('.status-block').append(response.data.html.manager_info);
                    }
                }
            });
		}
    }
})();
    
LegalEntityOrder.init();