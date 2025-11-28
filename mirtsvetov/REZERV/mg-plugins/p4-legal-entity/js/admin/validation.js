var Validation = (function() {

	return {

		init: function()
        {
            $('.admin-center').on(
                'bind change keyup input click', 
                '.section-'+ PluginLegalEntity.pluginName +' input[name=legal_inn], input[name=legal_kpp]', 
                function() {
                    if (this.value.match(/[^0-9]/g)) {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    }
                }
            );

            $('.admin-center').on('focus', '.section-'+ PluginLegalEntity.pluginName +' input, textarea', function() {
                if ($(this).hasClass('input_invalid')) {
                    $(this).removeClass('input_invalid');
                }
            });

            $('.admin-center').on('click', '.section-'+ PluginLegalEntity.pluginName +' .day .checkbox input', function() {
                let $this = $(this);
                if ($this.is(':checked')) {
                    $this.parents('.day').find('.day__date input').prop('disabled', false);
                } else {
                    $this.parents('.day').find('.day__date input').prop('disabled', true);
                    if ($this.parents('.day').find('.day__date input').hasClass('input_invalid')) {
                        $this.parents('.day').find('.day__date input').removeClass('input_invalid');
                    }
                }
			});
		}, 

        /**
         * 
         * Валидация.
         * 
         * @param {array} inputs
         * 
         * @returns bool
         * 
         */
        validation: function(inputs)
        {
            let errors = [];

            inputs.forEach(function(input) {
                switch (input['name']) {

                    case 'legal_name':  
                        if (Validation.isEmpty(input['value'])) {
                            errors.push({type: 'input', name: input['name']});  
                        }
                    break;

                    case 'legal_inn': 
                        if (Validation.isEmpty(input['value'])) {
                            errors.push({type: 'input', name: input['name']});
                        }
                    break;

                    case 'legal_address': 
                        if (Validation.isEmpty(input['value'])) {
                            errors.push({type: 'textarea', name: input['name']});
                        }
                    break;
                }
            });

            if (errors.length) {
                Validation.printErrors(errors);
                return false;
            }

            return true;
        },

        /**
         * 
         * Проверка привязки пользователя к организации.
         * 
         * @param {string|null} id 
         * 
         * @returns bool
         * 
         */
        user: function(id)
        {
            if (!id) {
                $('.section-'+ PluginLegalEntity.pluginName +' .user__empty').addClass('user__empty_invalid');
                return false;
            }

            return true;
        },

        /**
         * 
         * Проверка времени сессии.
         * 
         * @param {string|null} id 
         * 
         * @returns bool
         * 
         */
        time: function(inputs)
        {
            let errors = [];

            inputs.forEach(function(input) {
                if (Validation.isEmpty(input['value'])) {
                    errors.push({type: 'input', name: input['name']});  
                }
            });

            if (errors.length) {
                Validation.printErrors(errors);
                return false;
            }

            return true;
        },

        /**
         * 
         * Проверка на пустоту.
         * 
         * @param {string} string 
         * 
         * @returns bool
         * 
         */
        isEmpty: function(string)
        {
            if (string.trim() === '') {
                return true;
            } else {
                return false;
            } 
        },

        /**
         * 
         * Вывод ошибок.
         * 
         * @param {array} errors 
         * 
         */
        printErrors: function(errors)
        {
            errors.forEach(function(error) {
                if (error['type'] === 'input') {
                    $('.section-'+ PluginLegalEntity.pluginName +' input[name='+ error['name'] +']').addClass('input_invalid');
                } else {
                    $('.section-'+ PluginLegalEntity.pluginName +' textarea[name='+ error['name'] +']').addClass('input_invalid');
                }
            });
        }

    }
})();

Validation.init();