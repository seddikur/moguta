$(document).ready(function() {
    // Удаление товара из корзины аяксом
    $('body').on('click', '.js-delete-from-cart', function() {

        var $this = $(this);
        var itemId = $this.data('delete-item-id');
        var property = $this.data('property');
        var $vari = $this.data('variant');
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/cart",
            data: {
                action: "cart", // название действия в пользовательском класса Ajaxuser
                delFromCart: 1,
                itemId: itemId,
                property: property,
                variantId: $vari
            },
            dataType: "json",
            cache: false,
            success: function(response) {
                if ('success' == response.status) {
                    if (response.deliv && response.curr) {
                        var i = 0;
                        response.deliv.forEach(function(element, index, arr) {
                            $('.delivery-details-list li:eq(' + i + ') .deliveryPrice').html('&nbsp;' + element);
                            if ($('.delivery-details-list input[type=radio]:eq(' + i + ')').is(':checked')) {
                                if (element == 0) {
                                    $('.summ-info .delivery-summ').html('');
                                } else {
                                    $('.summ-info .delivery-summ').html(locale.delivery + ' <span class="order-delivery-summ">' + element + ' ' + response.curr + '</span>');
                                }
                            }
                            i++;
                        });
                    }
                    if (!$vari) $vari = 0;
                    var table = $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"][data-variant="' + $vari + '"]').parents('table');
                    if ($vari) {
                        $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"][data-variant="' + $vari + '"]').parents('tr').remove();
                    } else {
                        $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"]').parents('tr').remove();
                    }

                    var i = 1;
                    table.find('.index').each(function() {
                        $(this).text(i++);
                    });
                    $('.total-sum strong,.total .total-sum span.total-payment,.mg-desktop-cart .total-sum span.total-payment,.mg-fake-cart .total-sum span.total-payment').text(response.data.cart_price_wc);
                    response.data.cart_price = response.data.cart_price ? response.data.cart_price : 0;
                    response.data.cart_count = response.data.cart_count ? response.data.cart_count : 0;
                    $('.pricesht').text(response.data.cart_price);
                    $('.countsht').text(response.data.cart_count);
                    $('.cart-table .total-sum-cell strong').text(response.data.cart_price_wc);

                    if ($('.small-cart-table tr').length == 0) {

                        $('html').removeClass('c-modal--scroll');
                        $('#js-modal__cart').removeClass('c-modal--open');
                        $('.product-cart, .checkout-form-wrapper, .small-cart').hide();
                        $('.empty-cart-block').show();

                    }
                }
            }
        });
        return false;
    });

    if ($('.small-cart-table tr').length == 0) {
        $('.product-cart, .checkout-form-wrapper, .small-cart').hide();
        $('.empty-cart-block').show();
    }
});