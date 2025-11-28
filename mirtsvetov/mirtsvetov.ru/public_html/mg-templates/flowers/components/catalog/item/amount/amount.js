$(document).ready(function() {

    // Обработка нажатия кнопок "увеличить" или "уменьшить"
    $('body').on('click', '.amount__up, .amount__down', async function (e) {
        e.preventDefault();

        const $button = $(this);
        const $buttons = $('.amount__change');
        const isUp = $button.hasClass('amount__up');

        AmountUI.disableButtons($buttons);

        await (isUp ? Amount.up($button) : Amount.down($button));
 
        AmountUI.enableButtons($buttons);
    });

    // Обработка ручного ввода
    $('body').on('blur', '.amount__input', async function (e) {
        e.preventDefault();

        const $input = $(this);
        const $buttons = $('.amount__change');

        AmountUI.disableButtons($buttons);

        await Amount.input($input);

        AmountUI.enableButtons($buttons);
    });
    
    // Обработка ввода с заменой "," на "."
    $('body').on('bind change keyup input click', '.amount__input', function (e) {
        e.preventDefault();
        this.value = this.value.replace(/,/g, '.');
        if (!/^\d*\.?\d*$/.test(this.value)) {
            this.value = this.value.slice(0, -1);
        }
    });
});

const Amount = {

    getData: ($input) => {
        return {
            productId: $input.data('product'),
            variantId: $input.data('variant'),
        };
    },
    
    /**
     * 
     * Увеличивает значение товара.
     * 
     * @param {jQuery} $button - Кнопка увеличения.
     * 
     * @returns void
     * 
     */
    async up($button)
    {
        const $input = $button.siblings('.amount__input');
        const data = this.getData($input);
        const result = this.adjustQuantity($input, 'up');

        // Повторение действия с одинаковым (макс) значением.
        if (result.disabled) return;

        // Прибавление остатка
        data.request = `variant=${data.variantId}&amount_input=${result.incrementCount}`;
        response = await cartAPI.update(data);

        if (response.status == 'success') {
            CatalogCart.update(response);
            if (response.data.dataCart) {
                CatalogCart.maxCount(response.data.dataCart);
            } 

            if (result.val > 0) $input.closest('tr').addClass('active');
            this.maxCount(result, $input.closest('.amount'));
        }
    },

    /**
     * 
     * Уменьшает значение товара.
     * 
     * @param {jQuery} $button - Кнопка уменьшения.
     * 
     * @returns void
     * 
     */
    async down($button)
    {
        const $input = $button.siblings('.amount__input');
        const data = this.getData($input);
        const result = this.adjustQuantity($input, 'down');

        let response;
        if (result.val) {
            // Уменьшение остатка
            data.request = `variant=${data.variantId}&amount_input=-${result.incrementCount}`;
            response = await cartAPI.update(data);
        } else {
            // Повторение действия с одинаковым (мин) значением.
            if (result.disabled) return;
            // Удаление товара
            response = await cartAPI.delete(data);
            response.delete = true;
        }

        if (response.status == 'success') {
            CatalogCart.update(response);
            if (response.data.dataCart) {
                CatalogCart.maxCount(response.data.dataCart);
            }
            if (result.val == 0) $input.closest('tr').removeClass('active');
            this.maxCount(result, $input.closest('.amount'));
        }
    },

    /**
     * 
     * Ввод вручную значение товара.
     * 
     * @param {jQuery} $input - input ввода.
     * 
     * @returns void
     * 
     */
    async input($input)
    {
        const result = this.adjustQuantity($input, 'input');
        const data = { ...this.getData($input), count: result.val };

        let response;
        if (result.val) {
            // Если значения одинаковые, то ничего не делаем
            response = await cartAPI.updateCartItem(data);
            if (result.val == response.preVal) return;
            // Обновление остатка
            if (response.refresh) {
                data.request = response.request;
                response = await cartAPI.refreshCartItems(data);
            // Добавление товара
            } else {
                data.request = `variant=${data.variantId}&amount_input=${data.count}`;
                response = await cartAPI.update(data);
            }
        // Удаление товара
        } else {
            response = await cartAPI.delete(data);
            response.delete = true;
        }

        if (response.status == 'success') {
            CatalogCart.update(response);
            if (response.data.dataCart) {
                CatalogCart.maxCount(response.data.dataCart);
            } 
            if (result.val > 0) {
                $input.closest('tr').addClass('active');
            } else if (result.val == 0) {
                $input.closest('tr').removeClass('active');
            }
            this.maxCount(result, $input.closest('.amount'));
        }
    },

    /**
     * 
     * Функция корректировки количества.
     * Увеличивает или уменьшает значение в поле ввода на заданный шаг,
     * ограничивая значение минимальным и максимальным допустимым диапазоном.
     * Возвращает итоговое значение и флаг блокировки кнопок.
     * 
     * @param object $input 
     * @param string  changeType 
     * 
     * @returns object
     * 
     */
    adjustQuantity($input, changeType)
    {
        let currentQuantity  = parseFloat($input.val()) || 0,
            isDisabled = false;

        const step = parseFloat($input.data('increment-count')) > 0 
                            ? parseFloat($input.data('increment-count')) 
                            : 1;
                            
        let maxQuantity = parseFloat($input.data('max-count'));
        if (maxQuantity < 0) {
            maxQuantity = 9999999;
        }

        if (changeType == 'up') {
            if (currentQuantity == maxQuantity) isDisabled = true;
            currentQuantity += step;
        }

        if (changeType == 'down') {
            if (currentQuantity == 0) isDisabled = true;
            currentQuantity -= step;
        }

        // Нормализация значения
        currentQuantity = this.normalizeAmount(currentQuantity , step, maxQuantity);

        // Устанавливаем итоговое значение в поле
        $input.val(currentQuantity);

        return {
            'incrementCount': step,
            'maxCount': maxQuantity,
            'val': currentQuantity,
            'disabled': isDisabled
        };
    },

    /**
     * 
     * Функция нормализации значения.
     * Приводит к кратному указанному шагу.
     * 
     * @param number currentQuantity
     * @param number step
     * @param number maxQuantity
     * 
     * @returns number
     * 
     */
    normalizeAmount(currentQuantity, step, maxQuantity)
    {
        if (currentQuantity > 0 && currentQuantity < step) {
            currentQuantity = step;
        }

        // Если значение меньше 0: -1 -> 0
        if (currentQuantity <= 0) {
            currentQuantity = 0;
        }

        // Если значение превышает максимальное 100 -> 10
        if (currentQuantity >= maxQuantity) {
            currentQuantity = maxQuantity;
        }
    
        if (currentQuantity > step) {
            let decimalPlaces = 0,
                stepString = step.toString();
            if (stepString) {
                let arrayStr = stepString.split('.');
                if (arrayStr && arrayStr.length == 2) {
                    decimalPlaces = arrayStr[1].length;
                }
            }
            let count = (currentQuantity / step).toFixed(decimalPlaces);
            count = Math.floor(count);
            currentQuantity = (count * step).toFixed(decimalPlaces);
        }

        return parseFloat(currentQuantity);
    },

    maxCount: function(data, $wrapper)
    {
        if (Number(data.val) >= Number(data.maxCount) && Number(data.maxCount) != -1) {
            $wrapper.find('.js-max-count').detach();
            $wrapper.append('\
                <div class="js-max-count">Max: '+ data.maxCount +'</div>\
            ');
        } else {
            $wrapper.find('.js-max-count').detach();
        }
    },
};

const AmountUI = {
    disableButtons: ($buttons) => {
        $buttons.prop('disabled', true).addClass('disabled');
    },
 
    enableButtons: ($buttons) => {
        $buttons.prop('disabled', false).removeClass('disabled');
    },
};