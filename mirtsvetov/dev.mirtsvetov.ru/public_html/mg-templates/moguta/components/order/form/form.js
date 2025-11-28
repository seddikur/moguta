$(document).ready(function () {
    // после сабмита формы скрывает кнопку, чтобы избежать дублирования заказов при повторном нажатии
    $('form[action*="/order?creation=1"]').on('submit', function (e) {
        
        // перед отправкой данных, чистим дату доставки если она не предусмотрена способом доставки
        if($('.delivery-date').css('display') == "none"){
            $('input[name="date_delivery"]').val('');
        }

        let submitButton = $(this).find('input[type="submit"]');
        let fakeCopyButton = submitButton.clone();
        submitButton.hide();
        fakeCopyButton.addClass("disabled-btn");
        fakeCopyButton.attr("disabled", "true");
        fakeCopyButton.appendTo(submitButton.parent());
    });
    // проверка складов при попытке оформления заказа
    $(document.body).on('click', '[name=toOrder]', function (e) {
        if (typeof storage != 'undefined' && storage.counterToChangeCountProduct > 0) {
            e.stopPropagation();
            e.preventDefault();
        }
    });

// делает поля почты и телефона обязательными, если задано в настройках
    if (edition != 'gipermarket' && edition != 'saas') {
        if ((requiredFields === 'true' || requiredFields === true) && location.pathname.indexOf('/order') > -1) {
            $('[name=email], [name=phone]').attr('required', true);
        } else {
            $('[name=email], [name=phone]').attr('required', false);
        }
    }

// костыль для верстки выбранного чекбокса доставки при перезагрузке страницы
    $('[name="delivery"][checked]').parents('label').addClass('active');
    $(".ui-autocomplete").css('z-index', '1000');
    $.datepicker.regional['ru'] = {
        closeText: 'Закрыть',
        prevText: '&#x3c;Пред',
        nextText: 'След&#x3e;',
        currentText: 'Сегодня',
        monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
        monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
            'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        dayNames: ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
        dayNamesShort: ['вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт'],
        dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        isRTL: false
    };
    $.datepicker.setDefaults($.datepicker.regional['ru']);
    $('.delivery-date input[name=date_delivery]').datepicker({dateFormat: "dd.mm.yy", minDate: 0});

    if ($('input[name=toOrder]').prop("disabled")) {
        disabledToOrderSubmit(true);
    }

    if ($('.delivery-details-list input[name=delivery]:checked').val()) {
        disabledToOrderSubmit(false);
    }

    if ($('.payment-details-list input[name=payment]:checked').val()) {
        disabledToOrderSubmit(false);
    }
    var dataDelivery = $('.delivery-details-list input[name=delivery]:checked').parent().attr('data-delivery-date');
    if (dataDelivery == '1') {
        $('.delivery-date').show();
    }
    var intervalDelivery = $('.delivery-details-list input[name=delivery]:checked').parent().attr('data-delivery-intervals');
    if (intervalDelivery) {
        $('.delivery-interval [name=delivery_interval] option').remove();
        if (!$.isArray(intervalDelivery)) {
            intervalDelivery = intervalDelivery.replace('["', "").replace('"]', "").split('","');
        }
        for (var i = 0; i < intervalDelivery.length; i++) {
            if (intervalDelivery[i] != '') {
                intervalDelivery[i] = intervalDelivery[i].replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                $('.delivery-interval [name=delivery_interval]').append("<option value='" + intervalDelivery[i] + "'>" + intervalDelivery[i] + "</option>");
            }
        }
        $('.delivery-interval').show();
    }
    var addressParts = $('.delivery-details-list input[name=delivery]:checked').parent().attr('data-delivery-address');
    if (addressParts && $.type(addressParts) == 'string') {
        addressParts = $.parseJSON(addressParts);
    }
    if (addressParts) {
        $('[name="address"]').hide();
        $('.addressPartsContainer').hide().find('input').hide();
        for (var i = 0; i < addressParts.length; i++) {
            if (addressParts[i] != '') {
                $('.addressPartsContainer [name=address_' + addressParts[i] + ']').show().closest('.addressPartsContainer').show();
            }
        }
        $('.addressPartsTitle').show();
    }

    $('body').on('change', 'form[action*="/order?creation=1"] [name=customer]', function () {
        setTimeout(function () {
            $('form[action*="/order?creation=1"] [name=delivery]:checked').trigger('change');
        }, 3);
    });

    var deliverySumm = 0;

    // условие при единственном варианте доставки с выбором даты
    // инициализация необходимых настроек для dataPicker
    if ($('.delivery-details-list input').length === 1 && 
    $('.delivery-details-list li .active').data('delivery-date') == '1') {
        let date_settings = $('.delivery-details-list .active span.date_settings').text();
        if(date_settings.trim() !== ''){
            date_settings = JSON.parse(date_settings);
            $('.delivery-date input[name=date_delivery]').datepicker("option", "dateFormat", "dd.mm.yy");
            $('.delivery-date input[name=date_delivery]').datepicker("option", "minDate", date_settings.dateShift);
            if(typeof orderForm !== 'undefined'){
                $('.delivery-date input[name=date_delivery]').datepicker("option", "beforeShowDay", function(date) {
                    let day = date.getDay();
                    let stringDay = jQuery.datepicker.formatDate('mm-dd', date);
                    return orderForm.disableDateForDatepicke(day, stringDay, date_settings.monthWeek, date_settings.daysWeek)
                });
            }   
        }
    }


    // действия при оформлении заказа
    $('body').on('change', '.delivery-details-list input', function () {
        if ($(this).attr('type') == 'text') {
            return false;
        }
        $("p#auxiliary").html('');
        $('.delivery-details-list input[name=delivery]').parent().addClass('noneactive');
        $('.delivery-details-list input[name=delivery]').parent().removeClass('active');

        $('.delivery-details-list input[name=delivery]:checked').parent().removeClass('noneactive');
        $('.delivery-details-list input[name=delivery]:checked').parent().addClass('active');
        if ($('.delivery-details-list li .active').data('delivery-date') == '1') {
            $('.delivery-date').show();
            let date_settings = $('.delivery-details-list li .active span.date_settings').text();
            if(date_settings.trim() !== ''){
                date_settings = JSON.parse(date_settings);
                $('.delivery-date input[name=date_delivery]').datepicker("option", "dateFormat", "dd.mm.yy");
                $('.delivery-date input[name=date_delivery]').datepicker("option", "minDate", date_settings.dateShift);
                $('.ui-datepicker').hide();
                if(typeof orderForm !== 'undefined'){
                    $('.delivery-date input[name=date_delivery]').datepicker("option", "beforeShowDay", function(date) {
                        let day = date.getDay();
                        let stringDay = jQuery.datepicker.formatDate('mm-dd', date);
                        return orderForm.disableDateForDatepicke(day, stringDay, date_settings.monthWeek, date_settings.daysWeek)
                    });
                }   
            }
            $('#ui-datepicker-div').hide();
        } else {
            $('.delivery-date').hide();
        }

        if ($('.delivery-details-list li .active').data('delivery-use-storage') == '1') {
            $('.order-storage').show();
        } else {
            $('.order-storage').hide();
        }

        $('.delivery-interval [name=delivery_interval] option').remove();
        var intervalDelivery = $('.delivery-details-list li .active').data('delivery-intervals');
        if (intervalDelivery) {
            if (!$.isArray(intervalDelivery)) {
                intervalDelivery = intervalDelivery.replace('["', "").replace('"]', "").split('","');
            }
            for (var i = 0; i < intervalDelivery.length; i++) {
                if (intervalDelivery[i] != '') {
                    intervalDelivery[i] = intervalDelivery[i].replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    $('.delivery-interval [name=delivery_interval]').append("<option value='" + intervalDelivery[i] + "'>" + intervalDelivery[i] + "</option>");
                }
            }
            $('.delivery-interval').show();
        } else {
            $('.delivery-interval').hide();
        }
        var addressParts = $('.delivery-details-list li .active').data('delivery-address');
        if (addressParts !== undefined) {
            if (addressParts) {
                $('[name="address"]').hide();
                $('.addressPartsContainer').hide().find('input').hide();
                for (var i = 0; i < addressParts.length; i++) {
                    if (addressParts[i] != '') {
                        $('.addressPartsContainer [name=address_' + addressParts[i] + ']').show().closest('.addressPartsContainer').show();
                    }
                }
                $('.addressPartsTitle').show();
            } else {
                $('[name="address"]').show();
                $('.addressPartsContainer').hide().find('.address-area-part').hide();
                $('.addressPartsTitle').hide();
            }
        }

        var deliveryId = $('.delivery-details-list input[name=delivery]:checked').val();

        $('.payment-details-list').before('<div class="loader"></div>');
        disabledToOrderSubmit(true);
        $('.summ-info .delivery-summ').html('');
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/order",
            data: {
                action: "getPaymentByDeliveryId",
                deliveryId: deliveryId,
                customer: $('.form-list select[name="customer"]').val(),
                lang: langP
            },
            dataType: "json",
            cache: false,
            success: function (response) {
                var paymentTable = response.paymentTable;
                deliverySumm = response.summDelivery;

                if ('' == paymentTable || null == paymentTable) {
                    paymentTable = locale.paymentNone;
                    disabledToOrderSubmit(false);
                }

                if (response.summDelivery < 0) {
                    paymentTable = response.error;
                    disabledToOrderSubmit(false);
                }

                $('.payment-details-list').html(paymentTable);
                $('.loader').remove();
                $('.payment-details-list input[name=payment]').prop("checked", false);
                if ($('.payment-details-list input[name=payment]').length >= 1) {
                    disabledToOrderSubmit(false);
                    $('.payment-details-list input[name=payment]').first().trigger('click');
                    $('.payment-details-list input[name=payment]').first().click();
                }
                if (!response.error) {
                    if (response.summDelivery != 0) {
                        $('.summ-info .delivery-summ').html(locale.delivery + ' <span class="order-delivery-summ">' + response.summDelivery + ' </span> ');
                    }
                } else {
                    $('ul.payment-details-list').empty();
                    $('ul.payment-details-list').append('<li>' + response.error + '</li>');
                }

                //проверка на Apple Pay
                if (!window.ApplePaySession) {
                    $('input[name="payment"][value="25"]').parents('li').hide()
                }

                // Сбрасываем подытог (например, была наценка на оплату и выбрали другую доставку, надо убирать наценку)
                const totalPriceString = document.querySelector('.total-sum:not(.order-summ) span:last-child');
                const totalPrice = Number(totalPriceString.textContent.replace(/[^\d,]+/g, '').replace(/,/, '.'));
                const totalPriceFormated = totalPrice.toLocaleString();
                const underResult = document.querySelector('.order-summ.total-sum>span');
                underResult.textContent = `${totalPriceFormated} ${window.currency}`;
            }
        });
    });

    $('body').on('click', '.payment-details-list input', function () {
        var paymentId = $(this).val();

        $.ajax({
            type: "POST",
            url: mgBaseDir + "/order",
            data: {
                action: "setPaymentRate",
                paymentId: paymentId
            },
            dataType: "json",
            cache: false,
            success: function (response) {
                $('.summ-info .order-summ:first span').text(response.summ);
                if (deliverySumm) {
                    if (response.enableDeliveryCur == 'true') {
                        num = parseFloat(deliverySumm.replace(',', '.').replace(/[^0-9\.]/g, ''));
                        sm = +num * (+1 + +response.rate);
                        $('.order-delivery-summ').html(roundPlus(sm, 2) + ' ' + response.cur);
                    }
                }
            }
        });
    });

    $('.form-list select[name="customer"]').change(function () {
        if (typeof orderForm == 'undefined') {
            if ($(this).val() == 'fiz') {
                $('.form-list.yur-field').hide();
                $('.payment-details-list input[name=payment]').parents('li').show();
                $('.payment-details-list input[name=payment][value=7]').parents('li').hide();
            }
            if ($(this).val() == 'yur') {
                $('.form-list.yur-field').show();
                $('.payment-details-list input[name=payment]').parents('li').hide();
                $('.payment-details-list input[name=payment][value=7]').parents('li').show();
            }

            $('.delivery-details-list input[name=delivery]').trigger('change');
        }
    });

    $('body').on('click', '.payment-details-list input[name=payment]:checked', function () {
        $("p#auxiliary").html('');
        $('.payment-details-list input[name=payment]').parent().addClass('noneactive');
        $('.payment-details-list input[name=payment]').parent().removeClass('active');
        $('.payment-details-list input[name=payment]:checked').parent().removeClass('noneactive');
        $('.payment-details-list input[name=payment]:checked').parent().addClass('active');
        disabledToOrderSubmit(false);
    });

    $('body').on('click', '.agreement-data-checkbox-checkout-btn', function () {
        if (
            $(this).prop('checked') &&
            (!$('.delivery-details-list input[name=delivery]').length || $('.delivery-details-list input[name=delivery]:checked').length) &&
            (!$('.payment-details-list input[name=payment]').length || $('.payment-details-list input[name=payment]:checked').length)
        ) {
            disabledToOrderSubmit(false);
        } else {
            disabledToOrderSubmit(true);
        }
    });

    function disabledToOrderSubmit(flag) {
        if (flag == false && $('.agreement-data-checkbox-checkout-btn').length && !$('.agreement-data-checkbox-checkout-btn:checked').length) {
            flag = true;
        }

        if (flag == false) {
            $('input[name=toOrder]').prop("disabled", false);
            $('input[name=toOrder]').removeClass('disabled-btn');
        } else {
            $('input[name=toOrder]').prop("disabled", true);
            $('input[name=toOrder]').addClass('disabled-btn');
        }
    }

    if ($('.payment-details-list input[name=payment]').length >= 1) {
        $('.payment-details-list input[name=payment]').first().trigger('click');
        $('.payment-details-list input[name=payment]').first().click();
    }

    function roundPlus(x, n) {
        if (isNaN(x) || isNaN(n)) return false;
        var m = Math.pow(10, n);
        return Math.round(x * m) / m;
    }

});