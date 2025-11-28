$(document).ready(function () {
    var changeAmountBtnClass = '.js-cart-amount-change',
        cartFormClass = '.js-cart-form',
        changeAmountInputClass = '.js-amount-input';

    // Пересчет цены и количества (используется в корзине и странице заказа)
    $('body').on('click', changeAmountBtnClass, function () {
        var request = $(cartFormClass).formSerialize();
        var buttons = $(this).parent().find(changeAmountBtnClass);
        updateCartCount(request, buttons);
        buttons.prop('disabled', true).addClass('disabled');
        return false;
    });

    // ввод количества покупаемого товара в корзине, пересчет корзины (используется в корзине и странице заказа)
    $('body').on('blur', changeAmountInputClass, function () {
        var count = $(this).val();
        var buttons = $(this).parent().find(changeAmountBtnClass);
        if (count == 0) {
            $(this).parents('tr').find('.js-delete-from-cart').trigger('click');
        } else {
            var request = $(cartFormClass).formSerialize();
            updateCartCount(request, buttons);
        }
        buttons.prop('disabled', true).addClass('disabled');
        return false;
    });

});

    // функция пересчёта корзины при изменении количества товаров (используется в корзине и странице заказа)
    function updateCartCount(request, buttons) {
        if (typeof storage == 'undefined') {
            window.storage = {
                counterToChangeCountProduct: 0,
                changeNameButtonOrder: false,
            };
        }
        storage.counterToChangeCountProduct++;
        storage.changeNameButtonOrder = true;

        setTimeout(function () {
            if (storage.changeNameButtonOrder) {
                $('[name=toOrder]').val(locale.waitCalc);
                storage.changeNameButtonOrder = false;
            }
        }, 500);

        $.ajax({
            type: "POST",
            url: mgBaseDir + "/cart",
            data: "refresh=1&count_change=1&" + request,
            dataType: "json",
            cache: false,
            success: function (response) {
                if (response.deliv && response.curr) {
                    var i = 0;
                    response.deliv.forEach(function (element, index, arr) {
                        $('.delivery-details-list li:eq(' + i + ') .deliveryPrice').html('&nbsp;' + element);
                        if ($('.delivery-details-list input[type=radio]:eq(' + i + ')').is(':checked')) {
                            if (element == 0) {
                                $('.summ-info .delivery-summ').html('');
                            } else {
                                $('.summ-info .delivery-summ').html(locale.delivery + '<span class="order-delivery-summ">' + element + ' ' + response.curr + '</span>');
                            }
                        }
                        i++;
                    });
                }
                storage.counterToChangeCountProduct--;
                if (storage.counterToChangeCountProduct == 0) {
                    $('[name=toOrder]').val(locale.checkout);
                    storage.changeNameButtonOrder = false;
                }

                let sumBak = 0;
                
                if (response.data) {
                    response.data.dataCart.forEach(function (element, index, arr) {

                        sumBak = sumBak + Number(element.opf_21 * element.countInCart);

                        var varBlock = '';
                        if (parseInt(element.variantId) > 0) {
                            varBlock = '[data-variant=' + (element.variantId) + ']';
                        }

                        var tr = $('td .deleteItemFromCart[data-delete-item-id=' + element.id + '][data-property=' + element.property + ']' + varBlock).closest('tr');

                        $wrapper = tr.find('.count-cell');
                        
                        if (tr.length) {
                            tr.find('.js-cartPrice').text(element.priceInCart);

                            maxCount = element.count;
                            if (
                                Number(element.countInCart) >= 
                                Number(maxCount) && 
                                Number(maxCount) != -1
                            ) {
                                $wrapper.find('.js-c-cart-max-count').detach();
                                $wrapper.append('\
                                    <div class="js-c-cart-max-count">Max: '+ element.countInCart +'</div>\
                                ');
                            } else {
                                $wrapper.find('.js-c-cart-max-count').detach();
                            }
                        }
                    });

                    $('.total .total-sum span:last-child').text(response.data.cart_price_wc);
                    $('.pricesht').text(response.data.cart_price);
                    $('.countsht').text(response.data.cart_count);

                    $('.cart_bak_total').text(response.data.cart_count);
                    $('.cart_qty_total').text(sumBak);

                    $('.cart-wrapper .total-sum strong').text(response.data.cart_price_wc);
                }
                buttons.prop('disabled', false).removeClass('disabled');
                $('.payment-details-list input:checked').click();
            }
        });
    }