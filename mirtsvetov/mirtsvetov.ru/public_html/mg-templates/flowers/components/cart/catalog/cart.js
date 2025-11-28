$(document).ready(function() {

    $('body').on('click', '.js-c-cart-delete', async function (e) {
        e.preventDefault();

        const $button = $(this);

        AmountUI.disableButtons($button);

        await CatalogCart.delete($button);

        AmountUI.enableButtons($button);
    });

});

const CatalogCart = (function() {

    return {

        template: null,
        html: null,

        /**
         * 
         * Инициализация шаблона корзины
         * 
         */
        init: function() {
            this.template = document.querySelector('.js-c-cart-template')
                .content.querySelector('.js-c-cart-item');
        },

        /**
         * 
         * Обновляет содержимое корзины
         * 
         * @param {Object} response - Ответ от сервера с данными корзины
         * 
         * @return void
         * 
         */
        update: function(response) {

            if (!response || !response.data) {
                console.error('Invalid response data', response);
                return;
            }

            this.html = '';

            if (response.data.cart_count) {
                response.data.count = response.data.dataCart.reduce((count, item, index) => {
                    return count + this.renderItem(item, index);
                }, 0);
            }

            this.updateUI(response);
        },

        /**
         * 
         * Рендерит товар
         * 
         * @param {Object} item - Данные товара
         * @param {number} index - Позиция товара в массиве
         * 
         * @returns {number} - сумма количества товара
         * 
         */
        renderItem: function(item, index) {
            const html = $(this.template.cloneNode(true));

            html.attr('data-position', index);
            html.css('order', item.id +''+ item.variantId);

            const [productName, variantName] = this.splitTitle(item.title);

            html.find('.js-c-cart-name').text(productName + ', ' + variantName);
            html.find('.js-c-cart-count').text(item.countInCart + ' × ' + item.opf_21 + ' шт.');
            html.find('.js-c-cart-price-in-cart').text(item.priceInCart);
            html.find('.js-c-cart-delete').attr('data-product', item.id)
                                          .attr('data-variant', item.variantId)
                                          .attr('data-property', item.property);

            this.html += html.prop('outerHTML');

            return this.calculateCount(item);
        },


        maxCount: function(products)
        {
            products.forEach(function (product, index, array) {
                
                item = $('.js-c-cart-delete[data-product='+ product.id +'][data-property='+ product.property +'][data-variant='+ product.variantId +']').closest('.js-c-cart-item');

                $wrapper = item.find('.js-c-cart-count');

                let maxCount = product.count;

                if (item.length) {
                    if (
                        Number(product.countInCart) >= 
                        Number(maxCount) && 
                        Number(maxCount) != -1
                    ) {
                        $wrapper.find('.js-c-cart-max-count').detach();
                        $wrapper.append('\
                            <div class="js-c-cart-max-count">Max: '+ product.countInCart +'</div>\
                        ');
                    } else {
                        $wrapper.find('.js-c-cart-max-count').detach();
                    }
                }
            });
        },

        /**
         * 
         * Обновляет корзину
         * 
         * @param {Object} data - Данные корзины
         * 
         * @return void
         * 
         */
        updateUI(response) {
            const {cart_count = 0, cart_price_wc = 0, count = 0} = response.data;

            $('.js-c-cart-footer-count').text(`${cart_count} (${count} шт.)`);
            $('.js-c-cart-footer-price-in-cart').text(cart_price_wc);
            
            if (cart_count) {
               $('.js-c-cart-header, .js-c-cart-footer').show();
            } else {
                $('.js-c-cart-header, .js-c-cart-footer').hide();
                this.html = this.empty;
            }

            $('.js-c-cart-body').html(this.html);
        },

        /**
         * 
         * Вычисляет количество товара
         * 
         * @param {Object} item - Данные товара
         * 
         * @returns {number} 80 * 2 = 160
         * 
         */
        calculateCount(item) {
            return Number(item.opf_21 * item.countInCart) || 0;
        },

        /**
         * 
         * Разделяет название товара на имя и вариант
         * 
         * @param {string} title - Полное название товара (Avalanche 50 см)
         * 
         * @returns {[string, string]}
         * 
         */
        splitTitle(title) {
            const parts = title.split(" ").map(part => part.trim()).filter(Boolean);
            const variantName = parts.slice(-2).join(" ");
            const name = parts.slice(0, -2).join(" ");

            return [name, variantName];
        },

        empty() {   
            const html = '<tr class="c-cart__empty"><td>В корзине нет товара</td></tr>';
            
            return html;
        },

        /**
         * 
         * Удаляет товар из корзины
         * 
         * @param {jQuery} $button - Кнопка удаления.
         * 
         * @returns void
         * 
         */
        async delete($button) {
             const data = {
                productId: $button .data('product'),
                variantId: $button .data('variant')
            }

            const $input = $('.amount__input[data-product="'+ data.productId +'"][data-variant="'+ data.variantId +'"]');
            const $tr = $input.closest('tr');
            const $wrapper = $('.products-wrapper');
            
            response = await cartAPI.delete(data);

            if (response.status === 'success') {
                this.update(response);
                if (response.data.dataCart) {
                    this.maxCount(response.data.dataCart);
                } 

                $wrapper.find($input).val(0);
                $wrapper.find($input).closest('.amount').find('.js-max-count').remove();
                $tr.removeClass('active');
            }
        }
    }
})();

CatalogCart.init();