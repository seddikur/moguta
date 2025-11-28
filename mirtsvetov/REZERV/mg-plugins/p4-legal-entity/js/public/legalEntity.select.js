var LegalEntitySelect = (function() {

    return {

        init: function()
        {
            $('body').on('change', 'select[name="legal_entity"]', function() {
                let $select = $(this);
                LegalEntitySelect.selectHeaderLegalEntity($select);
            });
        },

        selectHeaderLegalEntity: function($select)
        {
            let id = $select.val();
            
            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                data: {
                    pluginHandler: PluginLegalEntity.pluginName,
                    actionerClass: 'PactionerLegalEntity',
                    action: 'selectHeaderLegalEntity',
                    id: id
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    }
                    if (response.status === 'error') {
                        PluginLegalEntity.setPosition();
                        $('body').append(response.data.html);
                        $('body').find('.js-modal-close').focus();
                    }
                }
            });
        }
    }
})();
    
LegalEntitySelect.init();