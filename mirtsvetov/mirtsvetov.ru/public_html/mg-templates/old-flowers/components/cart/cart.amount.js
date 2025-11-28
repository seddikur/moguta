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
                if (response.data) {
                    response.data.dataCart.forEach(function (element, index, arr) {
                        var varBlock = '';
                        if (parseInt(element.variantId) > 0) {
                            varBlock = '[data-variant=' + (element.variantId) + ']';
                        }
                        var tr = $('td .deleteItemFromCart[data-delete-item-id=' + element.id + '][data-property=' + element.property + ']' + varBlock).closest('tr');
                        if (tr.length) {
                            tr.find('.js-smallCartAmount').text(element.countInCart);
                            tr.find('.js-cartPrice').text(element.priceInCart);
                            if (Number(tr.find('.cart_form input[name="item_' + element.id + '[]"]').val()) >= Number(element.count) && Number(element.count) != -1) {
                                tr.find('.cart_form .maxCount').detach();
                                tr.find('.cart_form').append('<span class="maxCount" style="display:block;text-align:center;">' + locale.MAX + ': ' + element.count + '</span>');
                                tr.find('.cart_form input[name="item_' + element.id + '[]"]').data('max-count', element.count);
                            } else {
                                tr.find('.cart_form .maxCount').detach();
                            }
                        }
                    });
                    $('.total .total-sum span:last-child').text(response.data.cart_price_wc);
                    $('.pricesht').text(response.data.cart_price);
                    $('.countsht').text(response.data.cart_count);
                    $('.cart-wrapper .total-sum strong').text(response.data.cart_price_wc);
                }
                buttons.prop('disabled', false).removeClass('disabled');
                $('.payment-details-list input:checked').click();
            }
        });
    }