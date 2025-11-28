 
if(typeof locale == 'undefined'){locale={};}
$.extend(locale, {
    'cartRemove': 'Удалить',
    'fancyNext': 'Вперед',
    'fancyPrev': 'Назад',
    'countMsg1': 'Здравствуйте, меня интересует товар ',
    'countMsg2': ' с артикулом ',
    'countMsg3': " , но его нет в наличии. Сообщите, пожалуйста, о поступлении этого товара на склад.",
    'countInStock': 'Есть в наличии',
    'remaining': 'Остаток',
    'pcs': 'шт.',
    'paymentNone': 'нет доступных способов оплаты',
    'filterNone': 'Не нашлось подходящих товаров!',
    'delivery': '+ доставка: ',
    'waitCalc': 'Подождите, идет пересчет...',
    'checkout': 'Оформить заказ',
    'RecentlyViewed': 'Вы недавно смотрели',
    'MAX': 'Максимум',
    'productSearch': 'Поиск товаров...',
    'availibleVariants': 'Есть варианты',
    'ShowInVarious': 'Показывать в нескольких категориях',
    'deliverySum': 'доставка: ',
    'totalSum' : 'Общая стоимость: ',
}); 
// js переменные из движка
var mgBaseDir = '',
    mgNoImageStub = '',
    protocol = '',
    phoneMask = '',
    sessionToDB = '',
    sessionAutoUpdate = '',
    sessionLifeTime = 0,
    timeWithoutUser = 0,
    agreementClasses = '',
    langP = '',
    requiredFields = '',
    varHashProduct = '';

document.cookie.split(/; */).forEach(function (cookieraw) {
    if (cookieraw.indexOf('mg_to_script') === 0) {
        var cookie = cookieraw.split('=');
        var name = cookie[0].substr(13);
        var value = decodeURIComponent(cookie[1]);
        window[name] = tryJsonParse(value.replace(/&nbsp;/g, ' '));
    }
});

// продление пхп сессии
if (sessionLifeTime > 0 && window.sessionUpdateActive !== true) {
    window.sessionUpdateActive = true;
    setInterval(function () {
        let dataObj = {
            actionerClass: 'Ajaxuser',
            action: 'updateSession'
        };

        let data = Object.keys(dataObj).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(dataObj[k])
        }).join('&');

        const request = new XMLHttpRequest();

        request.open('POST', mgBaseDir + '/ajaxrequest', true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        request.addEventListener("readystatechange", () => {
            if (request.status < 200 && request.status >= 400) {
                console.log('Session update error!');
                console.log(request);
            }
        });

        request.send(data);

    }, (sessionLifeTime / 2 * 1000));
}


function tryJsonParse(str) {
    try {
        var res = JSON.parse(str);
        return res;
    } catch (e) {
        return str;
    }
} 
var InCartModule = (function() {
	return {
		pluginInCartText: 'В корзине',
		init: function() { 

			if (typeof locale != 'undefined' && locale.pluginInCartText) {
				InCartModule.pluginInCartText = locale.pluginInCartText;
			}

			var initialBuyButton = $('.product-wrapper:first .addToCart').text();
			if (typeof initialBuyButton === "undefined" || initialBuyButton === null || !initialBuyButton) {
				initialBuyButton = $('.property-form:first .addToCart').text();
			}

			$('.deleteItemFromCart').each(function(index,element) {
				$('.addToCart[data-item-id='+$(this).data('delete-item-id')+']').text(InCartModule.pluginInCartText).addClass('alreadyInCart');
			});

			$('body').on('click', '.addToCart', function() {
				$(this).text(InCartModule.pluginInCartText).addClass('alreadyInCart');
			});

			$('body').on('click', '.deleteItemFromCart', function() {
				$('.addToCart[data-item-id='+$(this).data('delete-item-id')+']').text(initialBuyButton).removeClass('alreadyInCart');
			});
		},
	};
})();
$(document).ready(function() {
	InCartModule.init();
}); 
var orderForm = (function () {
 return {
   init: function() {
     $('body').on('change', 'form[action*="/order?creation=1"] input[name="delivery"], form[action*="/order?creation=1"] [name=customer]', function() {
       orderForm.redrawForm();
     });
     $('form[action*="/order?creation=1"] *').removeAttr('data-delivery-address');
     orderForm.redrawForm();
   },
   redrawForm: function() {
     var delivId = 0;
     if ($('form[action*="/order?creation=1"] input[name=delivery]:checked').length) {
       delivId = $('form[action*="/order?creation=1"] input[name=delivery]:checked').val();
     }
     if($.inArray(parseInt(delivId), [0,1,2]) !== -1) {//address
       $('form[action*="/order?creation=1"] [name=address]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=address]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=address]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=address]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_nameyur
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_adress
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_inn
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_kpp
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_bank
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_bik
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_ks
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_rs
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').closest('.js-orderFromItem').hide();
     }
		},
  //Методы для даты доставки
 disableDateMonthForDatepicker:function(monthWeek){
   let disableDateMonth = [];
    for(key in monthWeek){
      let days = monthWeek[key].split(',');
      let month = '';
      switch (key){
        case 'jan' :
         month = '01';
         break;
        case 'feb' :
         month = '02';
         break;     
        case 'mar' :
         month = '03';
         break;
        case 'aip' :
         month = '04';
         break; 
        case 'may' :
         month = '05';
         break; 
        case 'jum' :
         month = '06';
         break;  
        case 'jul' :
         month = '07';
         break;
        case 'aug' :
         month = '08';
         break;     
        case 'sep' :
         month = '09';
         break;
        case 'okt' :
         month = '10';
         break; 
        case 'nov' :
         month = '11';
         break; 
        case 'dec' :
         month = '12';
         break;         
      }
      days.forEach(function(item){
        if(item !== ''){
          if(item < 10){
            item = '0'+item.toString();
          }
          disableDateMonth.push(month+"-"+item);
        }
      });
    }
    return disableDateMonth;
  },
  disableDateWeekForDatepicker:function(daysWeek){
    let disableDateWeek = [];
    for(key in daysWeek){
      if(daysWeek[key] != true){
        let numberOfWeekDay = '';
        switch (key){
          case 'su' : numberOfWeekDay = 0;
            break;
          case 'md' : numberOfWeekDay = 1;
            break;
         case 'tu' : numberOfWeekDay = 2;
           break;
         case 'we' : numberOfWeekDay = 3;
            break;
          case 'thu' : numberOfWeekDay = 4;
            break;  
          case 'fri' : numberOfWeekDay = 5;
            break;
          case 'sa' : numberOfWeekDay = 6;
            break;        
          }
        disableDateWeek.push(numberOfWeekDay);
      }
    }
    return disableDateWeek;
  },
  disableDateForDatepicke: function(day, stringDay, monthWeek, daysWeek){  
    let isDisabledDaysMonth = ($.inArray(stringDay, orderForm.disableDateMonthForDatepicker(monthWeek)) != -1);
    let isDisabledDaysWeek = ($.inArray(day, orderForm.disableDateWeekForDatepicker(daysWeek)) != -1);
    return [(!isDisabledDaysWeek && !isDisabledDaysMonth)];
  }
 };
})();
$(document).ready(function() {
 if (location.pathname.indexOf('/order') > -1) {
   orderForm.init();
 }
}); 
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
$(document).ready(function(){if(phoneMask){maskAll=phoneMask;mask='+# (###) ### ##-##';savePos=1;tmpInputVal='';tmpMask=maskAll.split(',');$('[name=phone]').attr('placeholder',tmpMask[0].replace(/#/g,'_'));delete tmpMask
	$('body').on('focus','[name=phone]',function(){if($(this).val().indexOf('_')!=-1||$(this).val()==''){tmpMask=maskAll.split(',');$(this).val(tmpMask[0].replace(/#/g,'_'));delete tmpMask}});$('body').on('blur','[name=phone]',function(){if($(this).val().indexOf('_')!=-1){$(this).val('')}});$('body').on('input','[name=phone]',function(){input=$(this);if(tmpInputVal.length<input.val().length){add=!0}else{add=!1}
		savePos=input.get(0).selectionStart;phone=input.val().replace(/[^0-9]/g,'');newPhone='';masks=maskAll.split(',');for(i=0;i<masks.length;i++){mask='+# (###) ### ##-##';maskNumber=masks[i].replace(/[^0-9]/g,'');if(maskNumber==phone.substring(0,maskNumber.length)){mask=masks[i].replace(/[0-9]/g,'#');i=1000000}}
		setCursor=!0;for(i=0,counter=0;i<mask.length;i++){if(mask[i]=='#'){if(phone[counter]==undefined){newPhone+='_';if(add&&setCursor){savePos=i;setCursor=!1}}else{newPhone+=phone[counter]}
			counter++}else{newPhone+=mask[i]}}
		input.val(newPhone);input.get(0).setSelectionRange(savePos,savePos);tmpInputVal=newPhone})}}) 
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
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
        typeof define === 'function' && define.amd ? define(factory) :
            (global = global || self, global.dialogPolyfill = factory());
}(this, function () { 'use strict';

    // nb. This is for IE10 and lower _only_.
    var supportCustomEvent = window.CustomEvent;
    if (!supportCustomEvent || typeof supportCustomEvent === 'object') {
        supportCustomEvent = function CustomEvent(event, x) {
            x = x || {};
            var ev = document.createEvent('CustomEvent');
            ev.initCustomEvent(event, !!x.bubbles, !!x.cancelable, x.detail || null);
            return ev;
        };
        supportCustomEvent.prototype = window.Event.prototype;
    }

    /**
     * @param {Element} el to check for stacking context
     * @return {boolean} whether this el or its parents creates a stacking context
     */
    function createsStackingContext(el) {
        while (el && el !== document.body) {
            var s = window.getComputedStyle(el);
            var invalid = function(k, ok) {
                return !(s[k] === undefined || s[k] === ok);
            };

            if (s.opacity < 1 ||
                invalid('zIndex', 'auto') ||
                invalid('transform', 'none') ||
                invalid('mixBlendMode', 'normal') ||
                invalid('filter', 'none') ||
                invalid('perspective', 'none') ||
                s['isolation'] === 'isolate' ||
                s.position === 'fixed' ||
                s.webkitOverflowScrolling === 'touch') {
                return true;
            }
            el = el.parentElement;
        }
        return false;
    }

    /**
     * Finds the nearest <dialog> from the passed element.
     *
     * @param {Element} el to search from
     * @return {HTMLDialogElement} dialog found
     */
    function findNearestDialog(el) {
        while (el) {
            if (el.localName === 'dialog') {
                return /** @type {HTMLDialogElement} */ (el);
            }
            el = el.parentElement;
        }
        return null;
    }

    /**
     * Blur the specified element, as long as it's not the HTML body element.
     * This works around an IE9/10 bug - blurring the body causes Windows to
     * blur the whole application.
     *
     * @param {Element} el to blur
     */
    function safeBlur(el) {
        if (el && el.blur && el !== document.body) {
            el.blur();
        }
    }

    /**
     * @param {!NodeList} nodeList to search
     * @param {Node} node to find
     * @return {boolean} whether node is inside nodeList
     */
    function inNodeList(nodeList, node) {
        for (var i = 0; i < nodeList.length; ++i) {
            if (nodeList[i] === node) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param {HTMLFormElement} el to check
     * @return {boolean} whether this form has method="dialog"
     */
    function isFormMethodDialog(el) {
        if (!el || !el.hasAttribute('method')) {
            return false;
        }
        return el.getAttribute('method').toLowerCase() === 'dialog';
    }

    /**
     * @param {!HTMLDialogElement} dialog to upgrade
     * @constructor
     */
    function dialogPolyfillInfo(dialog) {
        this.dialog_ = dialog;
        this.replacedStyleTop_ = false;
        this.openAsModal_ = false;

        // Set a11y role. Browsers that support dialog implicitly know this already.
        if (!dialog.hasAttribute('role')) {
            dialog.setAttribute('role', 'dialog');
        }

        dialog.show = this.show.bind(this);
        dialog.showModal = this.showModal.bind(this);
        dialog.close = this.close.bind(this);

        if (!('returnValue' in dialog)) {
            dialog.returnValue = '';
        }

        if ('MutationObserver' in window) {
            var mo = new MutationObserver(this.maybeHideModal.bind(this));
            mo.observe(dialog, {attributes: true, attributeFilter: ['open']});
        } else {
            // IE10 and below support. Note that DOMNodeRemoved etc fire _before_ removal. They also
            // seem to fire even if the element was removed as part of a parent removal. Use the removed
            // events to force downgrade (useful if removed/immediately added).
            var removed = false;
            var cb = function() {
                removed ? this.downgradeModal() : this.maybeHideModal();
                removed = false;
            }.bind(this);
            var timeout;
            var delayModel = function(ev) {
                if (ev.target !== dialog) { return; }  // not for a child element
                var cand = 'DOMNodeRemoved';
                removed |= (ev.type.substr(0, cand.length) === cand);
                window.clearTimeout(timeout);
                timeout = window.setTimeout(cb, 0);
            };
            ['DOMAttrModified', 'DOMNodeRemoved', 'DOMNodeRemovedFromDocument'].forEach(function(name) {
                dialog.addEventListener(name, delayModel);
            });
        }
        // Note that the DOM is observed inside DialogManager while any dialog
        // is being displayed as a modal, to catch modal removal from the DOM.

        Object.defineProperty(dialog, 'open', {
            set: this.setOpen.bind(this),
            get: dialog.hasAttribute.bind(dialog, 'open')
        });

        this.backdrop_ = document.createElement('div');
        this.backdrop_.className = 'backdrop';
        this.backdrop_.addEventListener('click', this.backdropClick_.bind(this));
    }

    dialogPolyfillInfo.prototype = {

        get dialog() {
            return this.dialog_;
        },

        /**
         * Maybe remove this dialog from the modal top layer. This is called when
         * a modal dialog may no longer be tenable, e.g., when the dialog is no
         * longer open or is no longer part of the DOM.
         */
        maybeHideModal: function() {
            if (this.dialog_.hasAttribute('open') && document.body.contains(this.dialog_)) { return; }
            this.downgradeModal();
        },

        /**
         * Remove this dialog from the modal top layer, leaving it as a non-modal.
         */
        downgradeModal: function() {
            if (!this.openAsModal_) { return; }
            this.openAsModal_ = false;
            this.dialog_.style.zIndex = '';

            // This won't match the native <dialog> exactly because if the user set top on a centered
            // polyfill dialog, that top gets thrown away when the dialog is closed. Not sure it's
            // possible to polyfill this perfectly.
            if (this.replacedStyleTop_) {
                this.dialog_.style.top = '';
                this.replacedStyleTop_ = false;
            }

            // Clear the backdrop and remove from the manager.
            this.backdrop_.parentNode && this.backdrop_.parentNode.removeChild(this.backdrop_);
            dialogPolyfill.dm.removeDialog(this);
        },

        /**
         * @param {boolean} value whether to open or close this dialog
         */
        setOpen: function(value) {
            if (value) {
                this.dialog_.hasAttribute('open') || this.dialog_.setAttribute('open', '');
            } else {
                this.dialog_.removeAttribute('open');
                this.maybeHideModal();  // nb. redundant with MutationObserver
            }
        },

        /**
         * Handles clicks on the fake .backdrop element, redirecting them as if
         * they were on the dialog itself.
         *
         * @param {!Event} e to redirect
         */
        backdropClick_: function(e) {
            if (!this.dialog_.hasAttribute('tabindex')) {
                // Clicking on the backdrop should move the implicit cursor, even if dialog cannot be
                // focused. Create a fake thing to focus on. If the backdrop was _before_ the dialog, this
                // would not be needed - clicks would move the implicit cursor there.
                var fake = document.createElement('div');
                this.dialog_.insertBefore(fake, this.dialog_.firstChild);
                fake.tabIndex = -1;
                fake.focus();
                this.dialog_.removeChild(fake);
            } else {
                this.dialog_.focus();
            }

            var redirectedEvent = document.createEvent('MouseEvents');
            redirectedEvent.initMouseEvent(e.type, e.bubbles, e.cancelable, window,
                e.detail, e.screenX, e.screenY, e.clientX, e.clientY, e.ctrlKey,
                e.altKey, e.shiftKey, e.metaKey, e.button, e.relatedTarget);
            this.dialog_.dispatchEvent(redirectedEvent);
            e.stopPropagation();
        },

        /**
         * Focuses on the first focusable element within the dialog. This will always blur the current
         * focus, even if nothing within the dialog is found.
         */
        focus_: function() {
            // Find element with `autofocus` attribute, or fall back to the first form/tabindex control.
            var target = this.dialog_.querySelector('[autofocus]:not([disabled])');
            if (!target && this.dialog_.tabIndex >= 0) {
                target = this.dialog_;
            }
            if (!target) {
                // Note that this is 'any focusable area'. This list is probably not exhaustive, but the
                // alternative involves stepping through and trying to focus everything.
                var opts = ['button', 'input', 'keygen', 'select', 'textarea'];
                var query = opts.map(function(el) {
                    return el + ':not([disabled])';
                });
                // TODO(samthor): tabindex values that are not numeric are not focusable.
                query.push('[tabindex]:not([disabled]):not([tabindex=""])');  // tabindex != "", not disabled
                target = this.dialog_.querySelector(query.join(', '));
            }
            safeBlur(document.activeElement);
            target && target.focus();
        },

        /**
         * Sets the zIndex for the backdrop and dialog.
         *
         * @param {number} dialogZ
         * @param {number} backdropZ
         */
        updateZIndex: function(dialogZ, backdropZ) {
            if (dialogZ < backdropZ) {
                throw new Error('dialogZ should never be < backdropZ');
            }
            this.dialog_.style.zIndex = dialogZ;
            this.backdrop_.style.zIndex = backdropZ;
        },

        /**
         * Shows the dialog. If the dialog is already open, this does nothing.
         */
        show: function() {
            if (!this.dialog_.open) {
                this.setOpen(true);
                this.focus_();
            }
        },

        /**
         * Show this dialog modally.
         */
        showModal: function() {
            if (this.dialog_.hasAttribute('open')) {
                throw new Error('Failed to execute \'showModal\' on dialog: The element is already open, and therefore cannot be opened modally.');
            }
            if (!document.body.contains(this.dialog_)) {
                throw new Error('Failed to execute \'showModal\' on dialog: The element is not in a Document.');
            }
            if (!dialogPolyfill.dm.pushDialog(this)) {
                throw new Error('Failed to execute \'showModal\' on dialog: There are too many open modal dialogs.');
            }

            if (createsStackingContext(this.dialog_.parentElement)) {
                console.warn('A dialog is being shown inside a stacking context. ' +
                    'This may cause it to be unusable. For more information, see this link: ' +
                    'https://github.com/GoogleChrome/dialog-polyfill/#stacking-context');
            }

            this.setOpen(true);
            this.openAsModal_ = true;

            // Optionally center vertically, relative to the current viewport.
            if (dialogPolyfill.needsCentering(this.dialog_)) {
                dialogPolyfill.reposition(this.dialog_);
                this.replacedStyleTop_ = true;
            } else {
                this.replacedStyleTop_ = false;
            }

            // Insert backdrop.
            this.dialog_.parentNode.insertBefore(this.backdrop_, this.dialog_.nextSibling);

            // Focus on whatever inside the dialog.
            this.focus_();
        },

        /**
         * Closes this HTMLDialogElement. This is optional vs clearing the open
         * attribute, however this fires a 'close' event.
         *
         * @param {string=} opt_returnValue to use as the returnValue
         */
        close: function(opt_returnValue) {
            if (!this.dialog_.hasAttribute('open')) {
                throw new Error('Failed to execute \'close\' on dialog: The element does not have an \'open\' attribute, and therefore cannot be closed.');
            }
            this.setOpen(false);

            // Leave returnValue untouched in case it was set directly on the element
            if (opt_returnValue !== undefined) {
                this.dialog_.returnValue = opt_returnValue;
            }

            // Triggering "close" event for any attached listeners on the <dialog>.
            var closeEvent = new supportCustomEvent('close', {
                bubbles: false,
                cancelable: false
            });

            // If we have an onclose handler assigned and it's a function, call it
            if(this.dialog_.onclose instanceof Function) {
                this.dialog_.onclose(closeEvent);
            }

            // Dispatch the event as normal
            this.dialog_.dispatchEvent(closeEvent);

        }

    };

    var dialogPolyfill = {};

    dialogPolyfill.reposition = function(element) {
        var scrollTop = document.body.scrollTop || document.documentElement.scrollTop;
        var topValue = scrollTop + (window.innerHeight - element.offsetHeight) / 2;
        element.style.top = Math.max(scrollTop, topValue) + 'px';
    };

    dialogPolyfill.isInlinePositionSetByStylesheet = function(element) {
        for (var i = 0; i < document.styleSheets.length; ++i) {
            var styleSheet = document.styleSheets[i];
            var cssRules = null;
            // Some browsers throw on cssRules.
            try {
                cssRules = styleSheet.cssRules;
            } catch (e) {}
            if (!cssRules) { continue; }
            for (var j = 0; j < cssRules.length; ++j) {
                var rule = cssRules[j];
                var selectedNodes = null;
                // Ignore errors on invalid selector texts.
                try {
                    selectedNodes = document.querySelectorAll(rule.selectorText);
                } catch(e) {}
                if (!selectedNodes || !inNodeList(selectedNodes, element)) {
                    continue;
                }
                var cssTop = rule.style.getPropertyValue('top');
                var cssBottom = rule.style.getPropertyValue('bottom');
                if ((cssTop && cssTop !== 'auto') || (cssBottom && cssBottom !== 'auto')) {
                    return true;
                }
            }
        }
        return false;
    };

    dialogPolyfill.needsCentering = function(dialog) {
        var computedStyle = window.getComputedStyle(dialog);
        if (computedStyle.position !== 'absolute') {
            return false;
        }

        // We must determine whether the top/bottom specified value is non-auto.  In
        // WebKit/Blink, checking computedStyle.top == 'auto' is sufficient, but
        // Firefox returns the used value. So we do this crazy thing instead: check
        // the inline style and then go through CSS rules.
        if ((dialog.style.top !== 'auto' && dialog.style.top !== '') ||
            (dialog.style.bottom !== 'auto' && dialog.style.bottom !== '')) {
            return false;
        }
        return !dialogPolyfill.isInlinePositionSetByStylesheet(dialog);
    };

    /**
     * @param {!Element} element to force upgrade
     */
    dialogPolyfill.forceRegisterDialog = function(element) {
        if (window.HTMLDialogElement || element.showModal) {
            console.warn('This browser already supports <dialog>, the polyfill ' +
                'may not work correctly', element);
        }
        if (element.localName !== 'dialog') {
            throw new Error('Failed to register dialog: The element is not a dialog.');
        }
        new dialogPolyfillInfo(/** @type {!HTMLDialogElement} */ (element));
    };

    /**
     * @param {!Element} element to upgrade, if necessary
     */
    dialogPolyfill.registerDialog = function(element) {
        if (!element.showModal) {
            dialogPolyfill.forceRegisterDialog(element);
        }
    };

    /**
     * @constructor
     */
    dialogPolyfill.DialogManager = function() {
        /** @type {!Array<!dialogPolyfillInfo>} */
        this.pendingDialogStack = [];

        var checkDOM = this.checkDOM_.bind(this);

        // The overlay is used to simulate how a modal dialog blocks the document.
        // The blocking dialog is positioned on top of the overlay, and the rest of
        // the dialogs on the pending dialog stack are positioned below it. In the
        // actual implementation, the modal dialog stacking is controlled by the
        // top layer, where z-index has no effect.
        this.overlay = document.createElement('div');
        this.overlay.className = '_dialog_overlay';
        this.overlay.addEventListener('click', function(e) {
            this.forwardTab_ = undefined;
            e.stopPropagation();
            checkDOM([]);  // sanity-check DOM
        }.bind(this));

        this.handleKey_ = this.handleKey_.bind(this);
        this.handleFocus_ = this.handleFocus_.bind(this);

        this.zIndexLow_ = 100000;
        this.zIndexHigh_ = 100000 + 150;

        this.forwardTab_ = undefined;

        if ('MutationObserver' in window) {
            this.mo_ = new MutationObserver(function(records) {
                var removed = [];
                records.forEach(function(rec) {
                    for (var i = 0, c; c = rec.removedNodes[i]; ++i) {
                        if (!(c instanceof Element)) {
                            continue;
                        } else if (c.localName === 'dialog') {
                            removed.push(c);
                        }
                        removed = removed.concat(c.querySelectorAll('dialog'));
                    }
                });
                removed.length && checkDOM(removed);
            });
        }
    };

    /**
     * Called on the first modal dialog being shown. Adds the overlay and related
     * handlers.
     */
    dialogPolyfill.DialogManager.prototype.blockDocument = function() {
        document.documentElement.addEventListener('focus', this.handleFocus_, true);
        document.addEventListener('keydown', this.handleKey_);
        this.mo_ && this.mo_.observe(document, {childList: true, subtree: true});
    };

    /**
     * Called on the first modal dialog being removed, i.e., when no more modal
     * dialogs are visible.
     */
    dialogPolyfill.DialogManager.prototype.unblockDocument = function() {
        document.documentElement.removeEventListener('focus', this.handleFocus_, true);
        document.removeEventListener('keydown', this.handleKey_);
        this.mo_ && this.mo_.disconnect();
    };

    /**
     * Updates the stacking of all known dialogs.
     */
    dialogPolyfill.DialogManager.prototype.updateStacking = function() {
        var zIndex = this.zIndexHigh_;

        for (var i = 0, dpi; dpi = this.pendingDialogStack[i]; ++i) {
            dpi.updateZIndex(--zIndex, --zIndex);
            if (i === 0) {
                this.overlay.style.zIndex = --zIndex;
            }
        }

        // Make the overlay a sibling of the dialog itself.
        var last = this.pendingDialogStack[0];
        if (last) {
            var p = last.dialog.parentNode || document.body;
            p.appendChild(this.overlay);
        } else if (this.overlay.parentNode) {
            this.overlay.parentNode.removeChild(this.overlay);
        }
    };

    /**
     * @param {Element} candidate to check if contained or is the top-most modal dialog
     * @return {boolean} whether candidate is contained in top dialog
     */
    dialogPolyfill.DialogManager.prototype.containedByTopDialog_ = function(candidate) {
        while (candidate = findNearestDialog(candidate)) {
            for (var i = 0, dpi; dpi = this.pendingDialogStack[i]; ++i) {
                if (dpi.dialog === candidate) {
                    return i === 0;  // only valid if top-most
                }
            }
            candidate = candidate.parentElement;
        }
        return false;
    };

    dialogPolyfill.DialogManager.prototype.handleFocus_ = function(event) {
        if (this.containedByTopDialog_(event.target)) { return; }

        if (document.activeElement === document.documentElement) { return; }

        event.preventDefault();
        event.stopPropagation();
        safeBlur(/** @type {Element} */ (event.target));

        if (this.forwardTab_ === undefined) { return; }  // move focus only from a tab key

        var dpi = this.pendingDialogStack[0];
        var dialog = dpi.dialog;
        var position = dialog.compareDocumentPosition(event.target);
        if (position & Node.DOCUMENT_POSITION_PRECEDING) {
            if (this.forwardTab_) {
                // forward
                dpi.focus_();
            } else if (event.target !== document.documentElement) {
                // backwards if we're not already focused on <html>
                document.documentElement.focus();
            }
        }

        return false;
    };

    dialogPolyfill.DialogManager.prototype.handleKey_ = function(event) {
        this.forwardTab_ = undefined;
        if (event.keyCode === 27) {
            event.preventDefault();
            event.stopPropagation();
            var cancelEvent = new supportCustomEvent('cancel', {
                bubbles: false,
                cancelable: true
            });
            var dpi = this.pendingDialogStack[0];
            if (dpi && dpi.dialog.dispatchEvent(cancelEvent)) {
                dpi.dialog.close();
            }
        } else if (event.keyCode === 9) {
            this.forwardTab_ = !event.shiftKey;
        }
    };

    /**
     * Finds and downgrades any known modal dialogs that are no longer displayed. Dialogs that are
     * removed and immediately readded don't stay modal, they become normal.
     *
     * @param {!Array<!HTMLDialogElement>} removed that have definitely been removed
     */
    dialogPolyfill.DialogManager.prototype.checkDOM_ = function(removed) {
        // This operates on a clone because it may cause it to change. Each change also calls
        // updateStacking, which only actually needs to happen once. But who removes many modal dialogs
        // at a time?!
        var clone = this.pendingDialogStack.slice();
        clone.forEach(function(dpi) {
            if (removed.indexOf(dpi.dialog) !== -1) {
                dpi.downgradeModal();
            } else {
                dpi.maybeHideModal();
            }
        });
    };

    /**
     * @param {!dialogPolyfillInfo} dpi
     * @return {boolean} whether the dialog was allowed
     */
    dialogPolyfill.DialogManager.prototype.pushDialog = function(dpi) {
        var allowed = (this.zIndexHigh_ - this.zIndexLow_) / 2 - 1;
        if (this.pendingDialogStack.length >= allowed) {
            return false;
        }
        if (this.pendingDialogStack.unshift(dpi) === 1) {
            this.blockDocument();
        }
        this.updateStacking();
        return true;
    };

    /**
     * @param {!dialogPolyfillInfo} dpi
     */
    dialogPolyfill.DialogManager.prototype.removeDialog = function(dpi) {
        var index = this.pendingDialogStack.indexOf(dpi);
        if (index === -1) { return; }

        this.pendingDialogStack.splice(index, 1);
        if (this.pendingDialogStack.length === 0) {
            this.unblockDocument();
        }
        this.updateStacking();
    };

    dialogPolyfill.dm = new dialogPolyfill.DialogManager();
    dialogPolyfill.formSubmitter = null;
    dialogPolyfill.useValue = null;

    /**
     * Installs global handlers, such as click listers and native method overrides. These are needed
     * even if a no dialog is registered, as they deal with <form method="dialog">.
     */
    if (window.HTMLDialogElement === undefined) {

        /**
         * If HTMLFormElement translates method="DIALOG" into 'get', then replace the descriptor with
         * one that returns the correct value.
         */
        var testForm = document.createElement('form');
        testForm.setAttribute('method', 'dialog');
        if (testForm.method !== 'dialog') {
            var methodDescriptor = Object.getOwnPropertyDescriptor(HTMLFormElement.prototype, 'method');
            if (methodDescriptor) {
                // nb. Some older iOS and older PhantomJS fail to return the descriptor. Don't do anything
                // and don't bother to update the element.
                var realGet = methodDescriptor.get;
                methodDescriptor.get = function() {
                    if (isFormMethodDialog(this)) {
                        return 'dialog';
                    }
                    return realGet.call(this);
                };
                var realSet = methodDescriptor.set;
                methodDescriptor.set = function(v) {
                    if (typeof v === 'string' && v.toLowerCase() === 'dialog') {
                        return this.setAttribute('method', v);
                    }
                    return realSet.call(this, v);
                };
                Object.defineProperty(HTMLFormElement.prototype, 'method', methodDescriptor);
            }
        }

        /**
         * Global 'click' handler, to capture the <input type="submit"> or <button> element which has
         * submitted a <form method="dialog">. Needed as Safari and others don't report this inside
         * document.activeElement.
         */
        document.addEventListener('click', function(ev) {
            dialogPolyfill.formSubmitter = null;
            dialogPolyfill.useValue = null;
            if (ev.defaultPrevented) { return; }  // e.g. a submit which prevents default submission

            var target = /** @type {Element} */ (ev.target);
            if (!target || !isFormMethodDialog(target.form)) { return; }

            var valid = (target.type === 'submit' && ['button', 'input'].indexOf(target.localName) > -1);
            if (!valid) {
                if (!(target.localName === 'input' && target.type === 'image')) { return; }
                // this is a <input type="image">, which can submit forms
                dialogPolyfill.useValue = ev.offsetX + ',' + ev.offsetY;
            }

            var dialog = findNearestDialog(target);
            if (!dialog) { return; }

            dialogPolyfill.formSubmitter = target;

        }, false);

        /**
         * Replace the native HTMLFormElement.submit() method, as it won't fire the
         * submit event and give us a chance to respond.
         */
        var nativeFormSubmit = HTMLFormElement.prototype.submit;
        var replacementFormSubmit = function () {
            if (!isFormMethodDialog(this)) {
                return nativeFormSubmit.call(this);
            }
            var dialog = findNearestDialog(this);
            dialog && dialog.close();
        };
        HTMLFormElement.prototype.submit = replacementFormSubmit;

        /**
         * Global form 'dialog' method handler. Closes a dialog correctly on submit
         * and possibly sets its return value.
         */
        document.addEventListener('submit', function(ev) {
            if (ev.defaultPrevented) { return; }  // e.g. a submit which prevents default submission

            var form = /** @type {HTMLFormElement} */ (ev.target);
            if (!isFormMethodDialog(form)) { return; }
            ev.preventDefault();

            var dialog = findNearestDialog(form);
            if (!dialog) { return; }

            // Forms can only be submitted via .submit() or a click (?), but anyway: sanity-check that
            // the submitter is correct before using its value as .returnValue.
            var s = dialogPolyfill.formSubmitter;
            if (s && s.form === form) {
                dialog.close(dialogPolyfill.useValue || s.value);
            } else {
                dialog.close();
            }
            dialogPolyfill.formSubmitter = null;

        }, false);
    }

    return dialogPolyfill;

}));
 
$(document).ready(function () {
    if (window.agremmentAdd) {
        return;
    }
    window.agremmentAdd = true;
    // Полифилл для тега dialog
    var dialogs = document.querySelectorAll('.agreement');
    dialogs.forEach(element => {
        const dialog = element.querySelector('.js-agreement-modal');
        if (!dialog) return;
         dialogPolyfill.registerDialog(dialog);

        var btnOpenSelector = '.js-open-agreement';
        var btnCloseSelector = '.js-close-agreement';

        // открытие модалки с соглашением на обработку пользовательских данных
        $(element).find(btnOpenSelector).on('click', function (e) {
            e.preventDefault();

            if (dialog.length < 1) {
                $.ajax({
                    type: "GET",
                    url: mgBaseDir + "/ajaxrequest",
                    data: {
                        layoutAgreement: 'agreement'
                    },
                    dataType: "HTML",
                    success: function (response) {
                        $('body').append(response);
                    }
                });
            } else {
                // modalOverlay.show();
                dialog.showModal();
            }
        });

        // закрытие модалки с соглашением на обработку пользовательских данных
        $(element).find(btnCloseSelector).on('click', function (e) {
            e.preventDefault();

            // modalOverlay.hide();
            dialog.close();
        });
    });

}); 
/*!
 * css-vars-ponyfill
 * v2.1.2
 * https://jhildenbiddle.github.io/css-vars-ponyfill/
 * (c) 2018-2019 John Hildenbiddle <http://hildenbiddle.com>
 * MIT license
 */
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e=e||self).cssVars=t()}(this,function(){"use strict";function e(){return(e=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var r=arguments[t];for(var n in r)Object.prototype.hasOwnProperty.call(r,n)&&(e[n]=r[n])}return e}).apply(this,arguments)}function t(e){return function(e){if(Array.isArray(e)){for(var t=0,r=new Array(e.length);t<e.length;t++)r[t]=e[t];return r}}(e)||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}()}function r(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},r={mimeType:t.mimeType||null,onBeforeSend:t.onBeforeSend||Function.prototype,onSuccess:t.onSuccess||Function.prototype,onError:t.onError||Function.prototype,onComplete:t.onComplete||Function.prototype},n=Array.isArray(e)?e:[e],o=Array.apply(null,Array(n.length)).map(function(e){return null});function s(){return!("<"===(arguments.length>0&&void 0!==arguments[0]?arguments[0]:"").trim().charAt(0))}function a(e,t){r.onError(e,n[t],t)}function c(e,t){var s=r.onSuccess(e,n[t],t);e=!1===s?"":s||e,o[t]=e,-1===o.indexOf(null)&&r.onComplete(o)}var i=document.createElement("a");n.forEach(function(e,t){if(i.setAttribute("href",e),i.href=String(i.href),Boolean(document.all&&!window.atob)&&i.host.split(":")[0]!==location.host.split(":")[0]){if(i.protocol===location.protocol){var n=new XDomainRequest;n.open("GET",e),n.timeout=0,n.onprogress=Function.prototype,n.ontimeout=Function.prototype,n.onload=function(){s(n.responseText)?c(n.responseText,t):a(n,t)},n.onerror=function(e){a(n,t)},setTimeout(function(){n.send()},0)}else console.warn("Internet Explorer 9 Cross-Origin (CORS) requests must use the same protocol (".concat(e,")")),a(null,t)}else{var o=new XMLHttpRequest;o.open("GET",e),r.mimeType&&o.overrideMimeType&&o.overrideMimeType(r.mimeType),r.onBeforeSend(o,e,t),o.onreadystatechange=function(){4===o.readyState&&(200===o.status&&s(o.responseText)?c(o.responseText,t):a(o,t))},o.send()}})}function n(e){var t={cssComments:/\/\*[\s\S]+?\*\//g,cssImports:/(?:@import\s*)(?:url\(\s*)?(?:['"])([^'"]*)(?:['"])(?:\s*\))?(?:[^;]*;)/g},n={rootElement:e.rootElement||document,include:e.include||'style,link[rel="stylesheet"]',exclude:e.exclude||null,filter:e.filter||null,useCSSOM:e.useCSSOM||!1,onBeforeSend:e.onBeforeSend||Function.prototype,onSuccess:e.onSuccess||Function.prototype,onError:e.onError||Function.prototype,onComplete:e.onComplete||Function.prototype},s=Array.apply(null,n.rootElement.querySelectorAll(n.include)).filter(function(e){return t=e,r=n.exclude,!(t.matches||t.matchesSelector||t.webkitMatchesSelector||t.mozMatchesSelector||t.msMatchesSelector||t.oMatchesSelector).call(t,r);var t,r}),a=Array.apply(null,Array(s.length)).map(function(e){return null});function c(){if(-1===a.indexOf(null)){var e=a.join("");n.onComplete(e,a,s)}}function i(e,t,o,s){var i=n.onSuccess(e,o,s);(function e(t,o,s,a){var c=arguments.length>4&&void 0!==arguments[4]?arguments[4]:[];var i=arguments.length>5&&void 0!==arguments[5]?arguments[5]:[];var l=u(t,s,i);l.rules.length?r(l.absoluteUrls,{onBeforeSend:function(e,t,r){n.onBeforeSend(e,o,t)},onSuccess:function(e,t,r){var s=n.onSuccess(e,o,t),a=u(e=!1===s?"":s||e,t,i);return a.rules.forEach(function(t,r){e=e.replace(t,a.absoluteRules[r])}),e},onError:function(r,n,u){c.push({xhr:r,url:n}),i.push(l.rules[u]),e(t,o,s,a,c,i)},onComplete:function(r){r.forEach(function(e,r){t=t.replace(l.rules[r],e)}),e(t,o,s,a,c,i)}}):a(t,c)})(e=void 0!==i&&!1===Boolean(i)?"":i||e,o,s,function(e,r){null===a[t]&&(r.forEach(function(e){return n.onError(e.xhr,o,e.url)}),!n.filter||n.filter.test(e)?a[t]=e:a[t]="",c())})}function u(e,r){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:[],s={};return s.rules=(e.replace(t.cssComments,"").match(t.cssImports)||[]).filter(function(e){return-1===n.indexOf(e)}),s.urls=s.rules.map(function(e){return e.replace(t.cssImports,"$1")}),s.absoluteUrls=s.urls.map(function(e){return o(e,r)}),s.absoluteRules=s.rules.map(function(e,t){var n=s.urls[t],a=o(s.absoluteUrls[t],r);return e.replace(n,a)}),s}s.length?s.forEach(function(e,t){var s=e.getAttribute("href"),u=e.getAttribute("rel"),l="LINK"===e.nodeName&&s&&u&&"stylesheet"===u.toLowerCase(),f="STYLE"===e.nodeName;if(l)r(s,{mimeType:"text/css",onBeforeSend:function(t,r,o){n.onBeforeSend(t,e,r)},onSuccess:function(r,n,a){var c=o(s,location.href);i(r,t,e,c)},onError:function(r,o,s){a[t]="",n.onError(r,e,o),c()}});else if(f){var d=e.textContent;n.useCSSOM&&(d=Array.apply(null,e.sheet.cssRules).map(function(e){return e.cssText}).join("")),i(d,t,e,location.href)}else a[t]="",c()}):n.onComplete("",[])}function o(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:location.href,r=document.implementation.createHTMLDocument(""),n=r.createElement("base"),o=r.createElement("a");return r.head.appendChild(n),r.body.appendChild(o),n.href=t,o.href=e,o.href}var s=a;function a(e,t,r){e instanceof RegExp&&(e=c(e,r)),t instanceof RegExp&&(t=c(t,r));var n=i(e,t,r);return n&&{start:n[0],end:n[1],pre:r.slice(0,n[0]),body:r.slice(n[0]+e.length,n[1]),post:r.slice(n[1]+t.length)}}function c(e,t){var r=t.match(e);return r?r[0]:null}function i(e,t,r){var n,o,s,a,c,i=r.indexOf(e),u=r.indexOf(t,i+1),l=i;if(i>=0&&u>0){for(n=[],s=r.length;l>=0&&!c;)l==i?(n.push(l),i=r.indexOf(e,l+1)):1==n.length?c=[n.pop(),u]:((o=n.pop())<s&&(s=o,a=u),u=r.indexOf(t,l+1)),l=i<u&&i>=0?i:u;n.length&&(c=[s,a])}return c}function u(t){var r=e({},{preserveStatic:!0,removeComments:!1},arguments.length>1&&void 0!==arguments[1]?arguments[1]:{});function n(e){throw new Error("CSS parse error: ".concat(e))}function o(e){var r=e.exec(t);if(r)return t=t.slice(r[0].length),r}function a(){return o(/^{\s*/)}function c(){return o(/^}/)}function i(){o(/^\s*/)}function u(){if(i(),"/"===t[0]&&"*"===t[1]){for(var e=2;t[e]&&("*"!==t[e]||"/"!==t[e+1]);)e++;if(!t[e])return n("end of comment is missing");var r=t.slice(2,e);return t=t.slice(e+2),{type:"comment",comment:r}}}function l(){for(var e,t=[];e=u();)t.push(e);return r.removeComments?[]:t}function f(){for(i();"}"===t[0];)n("extra closing bracket");var e=o(/^(("(?:\\"|[^"])*"|'(?:\\'|[^'])*'|[^{])+)/);if(e)return e[0].trim().replace(/\/\*([^*]|[\r\n]|(\*+([^*\/]|[\r\n])))*\*\/+/g,"").replace(/"(?:\\"|[^"])*"|'(?:\\'|[^'])*'/g,function(e){return e.replace(/,/g,"‌")}).split(/\s*(?![^(]*\)),\s*/).map(function(e){return e.replace(/\u200C/g,",")})}function d(){o(/^([;\s]*)+/);var e=/\/\*[^*]*\*+([^\/*][^*]*\*+)*\//g,t=o(/^(\*?[-#\/*\\\w]+(\[[0-9a-z_-]+\])?)\s*/);if(t){if(t=t[0].trim(),!o(/^:\s*/))return n("property missing ':'");var r=o(/^((?:\/\*.*?\*\/|'(?:\\'|.)*?'|"(?:\\"|.)*?"|\((\s*'(?:\\'|.)*?'|"(?:\\"|.)*?"|[^)]*?)\s*\)|[^};])+)/),s={type:"declaration",property:t.replace(e,""),value:r?r[0].replace(e,"").trim():""};return o(/^[;\s]*/),s}}function p(){if(!a())return n("missing '{'");for(var e,t=l();e=d();)t.push(e),t=t.concat(l());return c()?t:n("missing '}'")}function m(){i();for(var e,t=[];e=o(/^((\d+\.\d+|\.\d+|\d+)%?|[a-z]+)\s*/);)t.push(e[1]),o(/^,\s*/);if(t.length)return{type:"keyframe",values:t,declarations:p()}}function v(){if(i(),"@"===t[0]){var e=function(){var e=o(/^@([-\w]+)?keyframes\s*/);if(e){var t=e[1];if(!(e=o(/^([-\w]+)\s*/)))return n("@keyframes missing name");var r,s=e[1];if(!a())return n("@keyframes missing '{'");for(var i=l();r=m();)i.push(r),i=i.concat(l());return c()?{type:"keyframes",name:s,vendor:t,keyframes:i}:n("@keyframes missing '}'")}}()||function(){var e=o(/^@supports *([^{]+)/);if(e)return{type:"supports",supports:e[1].trim(),rules:y()}}()||function(){if(o(/^@host\s*/))return{type:"host",rules:y()}}()||function(){var e=o(/^@media([^{]+)*/);if(e)return{type:"media",media:(e[1]||"").trim(),rules:y()}}()||function(){var e=o(/^@custom-media\s+(--[^\s]+)\s*([^{;]+);/);if(e)return{type:"custom-media",name:e[1].trim(),media:e[2].trim()}}()||function(){if(o(/^@page */))return{type:"page",selectors:f()||[],declarations:p()}}()||function(){var e=o(/^@([-\w]+)?document *([^{]+)/);if(e)return{type:"document",document:e[2].trim(),vendor:e[1]?e[1].trim():null,rules:y()}}()||function(){if(o(/^@font-face\s*/))return{type:"font-face",declarations:p()}}()||function(){var e=o(/^@(import|charset|namespace)\s*([^;]+);/);if(e)return{type:e[1],name:e[2].trim()}}();if(e&&!r.preserveStatic){var s=!1;if(e.declarations)s=e.declarations.some(function(e){return/var\(/.test(e.value)});else s=(e.keyframes||e.rules||[]).some(function(e){return(e.declarations||[]).some(function(e){return/var\(/.test(e.value)})});return s?e:{}}return e}}function h(){if(!r.preserveStatic){var e=s("{","}",t);if(e){var o=/:(?:root|host)(?![.:#(])/.test(e.pre)&&/--\S*\s*:/.test(e.body),a=/var\(/.test(e.body);if(!o&&!a)return t=t.slice(e.end+1),{}}}var c=f()||[],i=r.preserveStatic?p():p().filter(function(e){var t=c.some(function(e){return/:(?:root|host)(?![.:#(])/.test(e)})&&/^--\S/.test(e.property),r=/var\(/.test(e.value);return t||r});return c.length||n("selector missing"),{type:"rule",selectors:c,declarations:i}}function y(e){if(!e&&!a())return n("missing '{'");for(var r,o=l();t.length&&(e||"}"!==t[0])&&(r=v()||h());)r.type&&o.push(r),o=o.concat(l());return e||c()?o:n("missing '}'")}return{type:"stylesheet",stylesheet:{rules:y(!0),errors:[]}}}function l(t){var r=e({},{parseHost:!1,store:{},onWarning:function(){}},arguments.length>1&&void 0!==arguments[1]?arguments[1]:{}),n=new RegExp(":".concat(r.parseHost?"host":"root","(?![.:#(])"));return"string"==typeof t&&(t=u(t,r)),t.stylesheet.rules.forEach(function(e){"rule"===e.type&&e.selectors.some(function(e){return n.test(e)})&&e.declarations.forEach(function(e,t){var n=e.property,o=e.value;n&&0===n.indexOf("--")&&(r.store[n]=o)})}),r.store}function f(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"",r=arguments.length>2?arguments[2]:void 0,n={charset:function(e){return"@charset "+e.name+";"},comment:function(e){return 0===e.comment.indexOf("__CSSVARSPONYFILL")?"/*"+e.comment+"*/":""},"custom-media":function(e){return"@custom-media "+e.name+" "+e.media+";"},declaration:function(e){return e.property+":"+e.value+";"},document:function(e){return"@"+(e.vendor||"")+"document "+e.document+"{"+o(e.rules)+"}"},"font-face":function(e){return"@font-face{"+o(e.declarations)+"}"},host:function(e){return"@host{"+o(e.rules)+"}"},import:function(e){return"@import "+e.name+";"},keyframe:function(e){return e.values.join(",")+"{"+o(e.declarations)+"}"},keyframes:function(e){return"@"+(e.vendor||"")+"keyframes "+e.name+"{"+o(e.keyframes)+"}"},media:function(e){return"@media "+e.media+"{"+o(e.rules)+"}"},namespace:function(e){return"@namespace "+e.name+";"},page:function(e){return"@page "+(e.selectors.length?e.selectors.join(", "):"")+"{"+o(e.declarations)+"}"},rule:function(e){var t=e.declarations;if(t.length)return e.selectors.join(",")+"{"+o(t)+"}"},supports:function(e){return"@supports "+e.supports+"{"+o(e.rules)+"}"}};function o(e){for(var o="",s=0;s<e.length;s++){var a=e[s];r&&r(a);var c=n[a.type](a);c&&(o+=c,c.length&&a.selectors&&(o+=t))}return o}return o(e.stylesheet.rules)}a.range=i;var d="--",p="var";function m(t){var r=e({},{preserveStatic:!0,preserveVars:!1,variables:{},onWarning:function(){}},arguments.length>1&&void 0!==arguments[1]?arguments[1]:{});return"string"==typeof t&&(t=u(t,r)),function e(t,r){t.rules.forEach(function(n){n.rules?e(n,r):n.keyframes?n.keyframes.forEach(function(e){"keyframe"===e.type&&r(e.declarations,n)}):n.declarations&&r(n.declarations,t)})}(t.stylesheet,function(e,t){for(var n=0;n<e.length;n++){var o=e[n],s=o.type,a=o.property,c=o.value;if("declaration"===s)if(r.preserveVars||!a||0!==a.indexOf(d)){if(-1!==c.indexOf(p+"(")){var i=h(c,r);i!==o.value&&(i=v(i),r.preserveVars?(e.splice(n,0,{type:s,property:a,value:i}),n++):o.value=i)}}else e.splice(n,1),n--}}),f(t)}function v(e){return(e.match(/calc\(([^)]+)\)/g)||[]).forEach(function(t){var r="calc".concat(t.split("calc").join(""));e=e.replace(t,r)}),e}function h(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},r=arguments.length>2?arguments[2]:void 0;if(-1===e.indexOf("var("))return e;var n=s("(",")",e);return n?"var"===n.pre.slice(-3)?0===n.body.trim().length?(t.onWarning("var() must contain a non-whitespace string"),e):n.pre.slice(0,-3)+function(e){var n=e.split(",")[0].replace(/[\s\n\t]/g,""),o=(e.match(/(?:\s*,\s*){1}(.*)?/)||[])[1],s=Object.prototype.hasOwnProperty.call(t.variables,n)?String(t.variables[n]):void 0,a=s||(o?String(o):void 0),c=r||e;return s||t.onWarning('variable "'.concat(n,'" is undefined')),a&&"undefined"!==a&&a.length>0?h(a,t,c):"var(".concat(c,")")}(n.body)+h(n.post,t):n.pre+"(".concat(h(n.body,t),")")+h(n.post,t):(-1!==e.indexOf("var(")&&t.onWarning('missing closing ")" in the value "'.concat(e,'"')),e)}var y="undefined"!=typeof window,g=y&&window.CSS&&window.CSS.supports&&window.CSS.supports("(--a: 0)"),S={group:0,job:0},b={rootElement:y?document:null,shadowDOM:!1,include:"style,link[rel=stylesheet]",exclude:"",variables:{},onlyLegacy:!0,preserveStatic:!0,preserveVars:!1,silent:!1,updateDOM:!0,updateURLs:!0,watch:null,onBeforeSend:function(){},onWarning:function(){},onError:function(){},onSuccess:function(){},onComplete:function(){}},E={cssComments:/\/\*[\s\S]+?\*\//g,cssKeyframes:/@(?:-\w*-)?keyframes/,cssMediaQueries:/@media[^{]+\{([\s\S]+?})\s*}/g,cssUrls:/url\((?!['"]?(?:data|http|\/\/):)['"]?([^'")]*)['"]?\)/g,cssVarDeclRules:/(?::(?:root|host)(?![.:#(])[\s,]*[^{]*{\s*[^}]*})/g,cssVarDecls:/(?:[\s;]*)(-{2}\w[\w-]*)(?:\s*:\s*)([^;]*);/g,cssVarFunc:/var\(\s*--[\w-]/,cssVars:/(?:(?::(?:root|host)(?![.:#(])[\s,]*[^{]*{\s*[^;]*;*\s*)|(?:var\(\s*))(--[^:)]+)(?:\s*[:)])/},w={dom:{},job:{},user:{}},C=!1,O=null,A=0,x=null,j=!1;function k(){var r=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},o="cssVars(): ",s=e({},b,r);function a(e,t,r,n){!s.silent&&window.console&&console.error("".concat(o).concat(e,"\n"),t),s.onError(e,t,r,n)}function c(e){!s.silent&&window.console&&console.warn("".concat(o).concat(e)),s.onWarning(e)}if(y){if(s.watch)return s.watch=b.watch,function(e){function t(e){return"LINK"===e.tagName&&-1!==(e.getAttribute("rel")||"").indexOf("stylesheet")&&!e.disabled}if(!window.MutationObserver)return;O&&(O.disconnect(),O=null);(O=new MutationObserver(function(r){r.some(function(r){var n,o=!1;return"attributes"===r.type?o=t(r.target):"childList"===r.type&&(n=r.addedNodes,o=Array.apply(null,n).some(function(e){var r=1===e.nodeType&&e.hasAttribute("data-cssvars"),n=function(e){return"STYLE"===e.tagName&&!e.disabled}(e)&&E.cssVars.test(e.textContent);return!r&&(t(e)||n)})||function(t){return Array.apply(null,t).some(function(t){var r=1===t.nodeType,n=r&&"out"===t.getAttribute("data-cssvars"),o=r&&"src"===t.getAttribute("data-cssvars"),s=o;if(o||n){var a=t.getAttribute("data-cssvars-group"),c=e.rootElement.querySelector('[data-cssvars-group="'.concat(a,'"]'));o&&(L(e.rootElement),w.dom={}),c&&c.parentNode.removeChild(c)}return s})}(r.removedNodes)),o})&&k(e)})).observe(document.documentElement,{attributes:!0,attributeFilter:["disabled","href"],childList:!0,subtree:!0})}(s),void k(s);if(!1===s.watch&&O&&(O.disconnect(),O=null),!s.__benchmark){if(C===s.rootElement)return void function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:100;clearTimeout(x),x=setTimeout(function(){e.__benchmark=null,k(e)},t)}(r);if(s.__benchmark=T(),s.exclude=[O?'[data-cssvars]:not([data-cssvars=""])':'[data-cssvars="out"]',s.exclude].filter(function(e){return e}).join(","),s.variables=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},t=/^-{2}/;return Object.keys(e).reduce(function(r,n){return r[t.test(n)?n:"--".concat(n.replace(/^-+/,""))]=e[n],r},{})}(s.variables),!O)if(Array.apply(null,s.rootElement.querySelectorAll('[data-cssvars="out"]')).forEach(function(e){var t=e.getAttribute("data-cssvars-group");(t?s.rootElement.querySelector('[data-cssvars="src"][data-cssvars-group="'.concat(t,'"]')):null)||e.parentNode.removeChild(e)}),A){var i=s.rootElement.querySelectorAll('[data-cssvars]:not([data-cssvars="out"])');i.length<A&&(A=i.length,w.dom={})}}if("loading"!==document.readyState)if(g&&s.onlyLegacy){if(s.updateDOM){var d=s.rootElement.host||(s.rootElement===document?document.documentElement:s.rootElement);Object.keys(s.variables).forEach(function(e){d.style.setProperty(e,s.variables[e])})}}else!j&&(s.shadowDOM||s.rootElement.shadowRoot||s.rootElement.host)?n({rootElement:b.rootElement,include:b.include,exclude:s.exclude,onSuccess:function(e,t,r){return(e=((e=e.replace(E.cssComments,"").replace(E.cssMediaQueries,"")).match(E.cssVarDeclRules)||[]).join(""))||!1},onComplete:function(e,t,r){l(e,{store:w.dom,onWarning:c}),j=!0,k(s)}}):(C=s.rootElement,n({rootElement:s.rootElement,include:s.include,exclude:s.exclude,onBeforeSend:s.onBeforeSend,onError:function(e,t,r){var n=e.responseURL||_(r,location.href),o=e.statusText?"(".concat(e.statusText,")"):"Unspecified Error"+(0===e.status?" (possibly CORS related)":"");a("CSS XHR Error: ".concat(n," ").concat(e.status," ").concat(o),t,e,n)},onSuccess:function(e,t,r){var n=s.onSuccess(e,t,r);return e=void 0!==n&&!1===Boolean(n)?"":n||e,s.updateURLs&&(e=function(e,t){return(e.replace(E.cssComments,"").match(E.cssUrls)||[]).forEach(function(r){var n=r.replace(E.cssUrls,"$1"),o=_(n,t);e=e.replace(r,r.replace(n,o))}),e}(e,r)),e},onComplete:function(r,n){var o=arguments.length>2&&void 0!==arguments[2]?arguments[2]:[],i={},d=s.updateDOM?w.dom:Object.keys(w.job).length?w.job:w.job=JSON.parse(JSON.stringify(w.dom)),p=!1;if(o.forEach(function(e,t){if(E.cssVars.test(n[t]))try{var r=u(n[t],{preserveStatic:s.preserveStatic,removeComments:!0});l(r,{parseHost:Boolean(s.rootElement.host),store:i,onWarning:c}),e.__cssVars={tree:r}}catch(t){a(t.message,e)}}),s.updateDOM&&e(w.user,s.variables),e(i,s.variables),p=Boolean((document.querySelector("[data-cssvars]")||Object.keys(w.dom).length)&&Object.keys(i).some(function(e){return i[e]!==d[e]})),e(d,w.user,i),p)L(s.rootElement),k(s);else{var v=[],h=[],y=!1;if(w.job={},s.updateDOM&&S.job++,o.forEach(function(t){var r=!t.__cssVars;if(t.__cssVars)try{m(t.__cssVars.tree,e({},s,{variables:d,onWarning:c}));var n=f(t.__cssVars.tree);if(s.updateDOM){if(t.getAttribute("data-cssvars")||t.setAttribute("data-cssvars","src"),n.length){var o=t.getAttribute("data-cssvars-group")||++S.group,i=n.replace(/\s/g,""),u=s.rootElement.querySelector('[data-cssvars="out"][data-cssvars-group="'.concat(o,'"]'))||document.createElement("style");y=y||E.cssKeyframes.test(n),u.hasAttribute("data-cssvars")||u.setAttribute("data-cssvars","out"),i===t.textContent.replace(/\s/g,"")?(r=!0,u&&u.parentNode&&(t.removeAttribute("data-cssvars-group"),u.parentNode.removeChild(u))):i!==u.textContent.replace(/\s/g,"")&&([t,u].forEach(function(e){e.setAttribute("data-cssvars-job",S.job),e.setAttribute("data-cssvars-group",o)}),u.textContent=n,v.push(n),h.push(u),u.parentNode||t.parentNode.insertBefore(u,t.nextSibling))}}else t.textContent.replace(/\s/g,"")!==n&&v.push(n)}catch(e){a(e.message,t)}r&&t.setAttribute("data-cssvars","skip"),t.hasAttribute("data-cssvars-job")||t.setAttribute("data-cssvars-job",S.job)}),A=s.rootElement.querySelectorAll('[data-cssvars]:not([data-cssvars="out"])').length,s.shadowDOM)for(var g,b=[s.rootElement].concat(t(s.rootElement.querySelectorAll("*"))),O=0;g=b[O];++O)if(g.shadowRoot&&g.shadowRoot.querySelector("style")){var x=e({},s,{rootElement:g.shadowRoot});k(x)}s.updateDOM&&y&&M(s.rootElement),C=!1,s.onComplete(v.join(""),h,JSON.parse(JSON.stringify(d)),T()-s.__benchmark)}}}));else document.addEventListener("DOMContentLoaded",function e(t){k(r),document.removeEventListener("DOMContentLoaded",e)})}}function M(e){var t=["animation-name","-moz-animation-name","-webkit-animation-name"].filter(function(e){return getComputedStyle(document.body)[e]})[0];if(t){for(var r=e.getElementsByTagName("*"),n=[],o=0,s=r.length;o<s;o++){var a=r[o];"none"!==getComputedStyle(a)[t]&&(a.style[t]+="__CSSVARSPONYFILL-KEYFRAMES__",n.push(a))}document.body.offsetHeight;for(var c=0,i=n.length;c<i;c++){var u=n[c].style;u[t]=u[t].replace("__CSSVARSPONYFILL-KEYFRAMES__","")}}}function _(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:location.href,r=document.implementation.createHTMLDocument(""),n=r.createElement("base"),o=r.createElement("a");return r.head.appendChild(n),r.body.appendChild(o),n.href=t,o.href=e,o.href}function T(){return y&&(window.performance||{}).now?window.performance.now():(new Date).getTime()}function L(e){Array.apply(null,e.querySelectorAll('[data-cssvars="skip"],[data-cssvars="src"]')).forEach(function(e){return e.setAttribute("data-cssvars","")})}return k.reset=function(){for(var e in C=!1,O&&(O.disconnect(),O=null),A=0,x=null,j=!1,w)w[e]={}},k});
 
$(document).ready(function () {
// c-nav (mobile menu)
// ------------------------------------------------------------

    $("#c-nav__catalog .c-nav__menu").mouseover(function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').addClass('active');
                $('#c-nav__catalog').addClass('c-nav--open');
            }
        );
    });

    $(".l-header__block .c-catalog").mouseover(function (e) {
        if (e.target === this) {
            MenuOpenCloseTimer(
                function () {
                    $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').addClass('active');
                    $('#c-nav__catalog').addClass('c-nav--open');
                }
            );
        }
    });

    $("#c-nav__catalog .c-nav__menu>.c-nav__dropdown").mouseout(function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').removeClass('active');
                $('#c-nav__catalog').removeClass('c-nav--open');
            }
        );
    });

    $(".l-header__block .c-catalog, #c-nav__catalog .c-nav__menu>.c-nav__dropdown li").hover(function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').addClass('active');
                $('#c-nav__catalog').addClass('c-nav--open');
            }
        );
    }, function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').removeClass('active');
                $('#c-nav__catalog').removeClass('c-nav--open');
            }
        );
    });

    $(".l-header__top .l-header__block .c-button, .l-header__top .l-header__block #c-nav__menu .c-nav__menu").hover(function () {
        MenuOpenCloseTimer(
            function () {
                $('.l-header__top .l-header__block #c-nav__menu').addClass('c-nav--open');
            }
        );
    }, function () {
        MenuOpenCloseTimer(
            function () {
                $('.l-header__top .l-header__block #c-nav__menu').removeClass('c-nav--open');
            }
        );
    });

    function MenuOpenCloseTimer(funct) {
        if (typeof this.delayTimer == "number") {
            clearTimeout(this.delayTimer);
            this.delayTimer = '';
        }
        this.delayTimer = setTimeout(function () {
            funct();
        }, 300);
    }

    $('body').on('click', 'a[href^="#c-nav"]', function (a) {
        a.preventDefault();
        var b = $(this).attr('href');
        $(b).addClass('c-nav--open');

    }), $('body').on('click', '.c-nav', function () {
        $('.c-nav').removeClass('c-nav--open');

    }), $('body').on('click', '.c-nav__menu', function (a) {
        a.stopPropagation()
    });


    $('body').on('click', 'a[href^="#c-nav__menu"]', function (a) {
        a.preventDefault();
        var b = $(this).attr('href');
        $(b).addClass('c-nav--open');
        $('body').addClass('fixed__body')

    }), $('body').on('click', '.c-nav', function () {
        $('.c-nav').removeClass('c-nav--open');
        $('body').removeClass('fixed__body')

    }), $('body').on('click', '.c-nav__menu', function (a) {
        a.stopPropagation()
    });


    $(".c-menu").click(function () {
        $('.c-nav--open').toggle().removeAttr('style');
    });

    // $(document).on('click', function (e) {
    //     if (!$(e.target).closest(".c-nav--open").length) {
    //         $('.c-nav.c-nav--open').hide();
    //     }
    //
    //     e.stopPropagation();
    // });


    $('body').on('click', '.c-nav__level--1', function () {
        var a = $(this).siblings();

        if ($(window).width() < 1025) {
            a.find('.c-nav__dropdown--2').slideUp('fast');
            $(this).find('.c-nav__dropdown--2').slideToggle('fast');
        }
        a.find('.c-nav__icon').removeClass('rotate');
        $(this).find('.c-nav__icon').toggleClass('rotate');
    });
});
 
document.addEventListener("DOMContentLoaded", function() {
    var langSelect = document.getElementById('js-lang-select');

    var changeLang = function(event) {
        var select = event.target;

        window.location.href = select.options[select.selectedIndex].value;
    };
    if (langSelect) {
    	langSelect.addEventListener('change', changeLang);
    }
}); 
$(document).ready(function () {
    var addToCompareBtn = '.js-add-to-compare', // Класс кнопки добавления товара к сравнению, кнопка должна содержать ID товара в атрибуте «data-item-id»
        compareInformer = $('.js-compare-informer'), // Уведомление о добавлении товара к сравнению
        inCompareCounter = $('.js-compare-count'), // Счётчик количества товаров в сравнении
        toCompareLink = $('.js-to-compare-link'); // Ссылка на страницу сравнения

    // Обработчик клика по кнопке добавления к сравнению «.js-add-to-compare»
    $('body').on('click', addToCompareBtn, addToCompare);

    // Функция добавления товара к сравнению
    function addToCompare() {
        // Показываем уведомление
        compareInformer.slideDown('fast');

        // Убираем уведомление
        setTimeout(function () {
            compareInformer.slideUp('fast')
        }, 1000);

        // Отправлем запрос на добавление товара к сравнению
        var request = 'inCompareProductId=' + $(this).data('item-id');

        $.ajax({
            type: "GET",
            url: mgBaseDir + "/compare",
            data: "updateCompare=1&" + request,
            dataType: "json",
            cache: false,
            success: function (response) {

                // Меняем количество товаров в счётчике
                inCompareCounter.html(response.count).fadeIn('normal');

                // «Мигаем» кнопкой перехода к сравнению
                toCompareLink.fadeOut('normal').fadeIn('normal');
            }
        });

        return false;
    }
}); 
// c-modal
// ------------------------------------------------------------
$('body').on('click', 'a[href^="#js-modal"]', function (a) {
    a.preventDefault();
    var b = $(this).attr('href');
    $(b).addClass('c-modal--open');
    if ($(document).height() > $(window).height()) {
        $('html').addClass('c-modal--scroll');
    }

}), $('body').on('click', '.c-modal, .c-modal__close, .c-modal__cart', function () {
    $('.c-modal').removeClass('c-modal--open');
    $('html').removeClass('c-modal--scroll');

}), $('body').on('click', '.c-modal__content', function (a) {
    a.stopPropagation()
}); 
/*!
 * hoverIntent v1.8.1 // 2014.08.11 // jQuery v1.9.1+
 * http://briancherne.github.io/jquery-hoverIntent/
 *
 * You may use hoverIntent under the terms of the MIT license. Basically that
 * means you are free to use hoverIntent as long as this header is left intact.
 * Copyright 2007, 2014 Brian Cherne
 */

/* hoverIntent is similar to jQuery's built-in "hover" method except that
 * instead of firing the handlerIn function immediately, hoverIntent checks
 * to see if the user's mouse has slowed down (beneath the sensitivity
 * threshold) before firing the event. The handlerOut function is only
 * called after a matching handlerIn.
 *
 * // basic usage ... just like .hover()
 * .hoverIntent( handlerIn, handlerOut )
 * .hoverIntent( handlerInOut )
 *
 * // basic usage ... with event delegation!
 * .hoverIntent( handlerIn, handlerOut, selector )
 * .hoverIntent( handlerInOut, selector )
 *
 * // using a basic configuration object
 * .hoverIntent( config )
 *
 * @param  handlerIn   function OR configuration object
 * @param  handlerOut  function OR selector for delegation OR undefined
 * @param  selector    selector OR undefined
 * @author Brian Cherne <brian(at)cherne(dot)net>
 */

;(function(factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else if (jQuery && !jQuery.fn.hoverIntent) {
        factory(jQuery);
    }
})(function($) {
    'use strict';

    // default configuration values
    var _cfg = {
        interval: 100,
        sensitivity: 6,
        timeout: 0
    };

    // counter used to generate an ID for each instance
    var INSTANCE_COUNT = 0;

    // current X and Y position of mouse, updated during mousemove tracking (shared across instances)
    var cX, cY;

    // saves the current pointer position coordinates based on the given mousemove event
    var track = function(ev) {
        cX = ev.pageX;
        cY = ev.pageY;
    };

    // compares current and previous mouse positions
    var compare = function(ev,$el,s,cfg) {
        // compare mouse positions to see if pointer has slowed enough to trigger `over` function
        if ( Math.sqrt( (s.pX-cX)*(s.pX-cX) + (s.pY-cY)*(s.pY-cY) ) < cfg.sensitivity ) {
            $el.off(s.event,track);
            delete s.timeoutId;
            // set hoverIntent state as active for this element (permits `out` handler to trigger)
            s.isActive = true;
            // overwrite old mouseenter event coordinates with most recent pointer position
            ev.pageX = cX; ev.pageY = cY;
            // clear coordinate data from state object
            delete s.pX; delete s.pY;
            return cfg.over.apply($el[0],[ev]);
        } else {
            // set previous coordinates for next comparison
            s.pX = cX; s.pY = cY;
            // use self-calling timeout, guarantees intervals are spaced out properly (avoids JavaScript timer bugs)
            s.timeoutId = setTimeout( function(){compare(ev, $el, s, cfg);} , cfg.interval );
        }
    };

    // triggers given `out` function at configured `timeout` after a mouseleave and clears state
    var delay = function(ev,$el,s,out) {
        delete $el.data('hoverIntent')[s.id];
        return out.apply($el[0],[ev]);
    };

    $.fn.hoverIntent = function(handlerIn,handlerOut,selector) {
        // instance ID, used as a key to store and retrieve state information on an element
        var instanceId = INSTANCE_COUNT++;

        // extend the default configuration and parse parameters
        var cfg = $.extend({}, _cfg);
        if ( $.isPlainObject(handlerIn) ) {
            cfg = $.extend(cfg, handlerIn);
            if ( !$.isFunction(cfg.out) ) {
                cfg.out = cfg.over;
            }
        } else if ( $.isFunction(handlerOut) ) {
            cfg = $.extend(cfg, { over: handlerIn, out: handlerOut, selector: selector } );
        } else {
            cfg = $.extend(cfg, { over: handlerIn, out: handlerIn, selector: handlerOut } );
        }

        // A private function for handling mouse 'hovering'
        var handleHover = function(e) {
            // cloned event to pass to handlers (copy required for event object to be passed in IE)
            var ev = $.extend({},e);

            // the current target of the mouse event, wrapped in a jQuery object
            var $el = $(this);

            // read hoverIntent data from element (or initialize if not present)
            var hoverIntentData = $el.data('hoverIntent');
            if (!hoverIntentData) { $el.data('hoverIntent', (hoverIntentData = {})); }

            // read per-instance state from element (or initialize if not present)
            var state = hoverIntentData[instanceId];
            if (!state) { hoverIntentData[instanceId] = state = { id: instanceId }; }

            // state properties:
            // id = instance ID, used to clean up data
            // timeoutId = timeout ID, reused for tracking mouse position and delaying "out" handler
            // isActive = plugin state, true after `over` is called just until `out` is called
            // pX, pY = previously-measured pointer coordinates, updated at each polling interval
            // event = string representing the namespaced event used for mouse tracking

            // clear any existing timeout
            if (state.timeoutId) { state.timeoutId = clearTimeout(state.timeoutId); }

            // namespaced event used to register and unregister mousemove tracking
            var mousemove = state.event = 'mousemove.hoverIntent.hoverIntent'+instanceId;

            // handle the event, based on its type
            if (e.type === 'mouseenter') {
                // do nothing if already active
                if (state.isActive) { return; }
                // set "previous" X and Y position based on initial entry point
                state.pX = ev.pageX; state.pY = ev.pageY;
                // update "current" X and Y position based on mousemove
                $el.off(mousemove,track).on(mousemove,track);
                // start polling interval (self-calling timeout) to compare mouse coordinates over time
                state.timeoutId = setTimeout( function(){compare(ev,$el,state,cfg);} , cfg.interval );
            } else { // "mouseleave"
                // do nothing if not already active
                if (!state.isActive) { return; }
                // unbind expensive mousemove event
                $el.off(mousemove,track);
                // if hoverIntent state is true, then call the mouseOut function after the specified delay
                state.timeoutId = setTimeout( function(){delay(ev,$el,state,cfg.out);} , cfg.timeout );
            }
        };

        // listen for mouseenter and mouseleave
        return this.on({'mouseenter.hoverIntent':handleHover,'mouseleave.hoverIntent':handleHover}, cfg.selector);
    };
});
 
// c-catalog
// ------------------------------------------------------------
$('.c-catalog .c-button').on('click', function () {
    if ($(window).width() < 1025) {
        $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').toggleClass('active');
    }

}), $('body').on('click', function () {
    if ($(window).width() < 1025) {
        $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').removeClass('active');
    }

}), $('body').on('click', '.c-catalog', function (a) {
    a.stopPropagation()
});

$('.c-catalog__level').hoverIntent({
    sensitivity: 3,
    interval: 100,
    timeout: 200,
    over: function () {
        $(this).find('> .c-catalog__dropdown').addClass('active');
    },
    out: function () {
        $(this).find('.c-catalog__dropdown').removeClass('active');
    }
}); 
$(document).ready(function () {
    // Обработка ввода поисковой фразы в поле поиска
    $('body').on('keyup', 'input[name=search]', function () {

        var text = $(this).val();
        if (text.length >= 2) {
            $.ajax({
                type: "POST",
                url: mgBaseDir + "/catalog",
                data: {
                    fastsearch: "true",
                    text: text
                },
                dataType: "json",
                cache: false,
                success: function (data) {
                    if ('success' == data.status && data.item.items.catalogItems.length > 0) {
                        $('.fastResult').html(data.html);
                        $('.fastResult').show();
                        $('.wraper-fast-result').show();
                    } else {
                        $('.fastResult').hide();
                    }
                }
            });
        } else {
            $('.fastResult').hide();
        }
    });

    // клик вне поиска
    $(document).mousedown(function (e) {
        var container = $(".wraper-fast-result");
        if (container.has(e.target).length === 0 && $(".search-block").has(e.target).length === 0) {
            container.hide();
        }
    });

}); 
"use strict";function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function _defineProperties(e,t){for(var i=0;i<t.length;i++){var s=t[i];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}function _createClass(e,t,i){return t&&_defineProperties(e.prototype,t),i&&_defineProperties(e,i),e}var Spoiler=function(){function t(e){_classCallCheck(this,t),this._spoiler=e,this._spoilerTitle=this._spoiler.querySelector(".spoiler-title"),this._spoilerContent=this._spoiler.querySelector(".spoiler-content"),this._spolierIsClosed=!0}return _createClass(t,[{key:"init",value:function(){this._spoilerTitle.addEventListener("click",this._slideToggleSpoilerContent.bind(this))}},{key:"_slideToggleSpoilerContent",value:function(){var e;this._spolierIsClosed?(this._spoilerContent.style.display="block",e=this._spoilerContent.scrollHeight,this._spoilerContent.style.height="".concat(e,"px"),this._spolierIsClosed=!1):(this._spoilerContent.style.height=0,this._spolierIsClosed=!0)}}]),t}();document.querySelectorAll(".spoiler").forEach(function(e){return new Spoiler(e).init()}); 
// Polyfill for css vars
cssVars();

$(document).ready(function () {
    // add active link
    // ------------------------------------------------------------
    $('nav a').each(function () {
        var location = window.location.href;
        var link = this.href;
        if (location == link) {
            $(this).addClass('active');
        }
    });

    // plugin "slider-action"
    // ------------------------------------------------------------
    $(document).ready(function () {
        $('.m-p-slider-wrapper').addClass('show');
    });


    // plugin "product-slider"
    // ------------------------------------------------------------
    $(document).ready(function () {
        $('.mg-advise').addClass('mg-advise--active');
    });


    // agreement
    // ------------------------------------------------------------
    $('.l-body').on('change', '[type="checkbox"]', function () {
        if ($(this).prop('checked')) {
            $(this).closest('label').removeClass('nonactive').addClass('active');
        }
        else {
            $(this).closest('label').removeClass('active').addClass('nonactive');
        }
    });

    // op-field-check
    // ------------------------------------------------------------
    $('.l-body').on('change', '.op-field-check [type="radio"]', function () {
        $('.op-field-check [name='+$(this).attr('name')+']').closest('label').removeClass('active').addClass('nonactive');
        if ($(this).prop('checked')) {
           $(this).closest('label').removeClass('nonactive').addClass('active');
        }
        else{
            $(this).closest('label').removeClass('active').addClass('nonactive');
        }
    });

    // order
    // ------------------------------------------------------------
    $('.c-order__checkbox label').on('click', function () {
        if ($(this).children('[type="checkbox"]').is(':checked')) {
            $(this).removeClass('nonactive').addClass('active');
        } else {
            $(this).removeClass('active').addClass('nonactive');
        }
    });
    $('.c-order__radiobutton label, .order-storage label').on('click', function () {
        if ($(this).children('[type="radio"]').is(':checked')) {
            $(this).removeClass('nonactive').addClass('active');
            $(this).siblings('label').removeClass('active');
        }
    });

    //эмуляция радиокнопок в форме характеристик продукта (страница товара, миникарточка, корзина, страница заказа)
    var form = $('.js-product-form');
    $(form).on('change', '[type=radio]', function () {
        $(this).parents('p').find('input[type=radio]').prop('checked', false);
        $(this).prop('checked', true);
        $(this).parents('p').find('label').removeClass('active');
        if ($(this).parents('p').length) {
            $(this).parent().addClass('active');
        }
    });

    //эмуляция чекбоксов в форме характеристик продукта (страница товара, миникарточка, корзина, страница заказа)
    $(form).on('change', '[type=checkbox]', function () {
        $(this).parent().toggleClass('active');
    });


    $('.spoiler-title').on('click', function () {
        $(this).parents('.spoiler').toggleClass('_active');
    });
}); // end ready

$('input, textarea').each(function () {
    var $elem = $(this);
    if ($elem.attr('placeholder') && !$elem[0].placeholder) {
        var $label = $('<label class="placeholder"></label>').text($elem.attr('placeholder'));
        $elem.before($label);
        $elem.blur();
        if ($elem.val() === '') {
            $label.addClass('visible');
        }
        $label.click(function () {
            $label.removeClass('visible');
            $elem.focus();
        });
        $elem.focus(function () {
            if ($elem.val() === '') {
                $label.removeClass('visible');
            }
        });
        $elem.blur(function () {
            if ($elem.val() === '') {
                $label.addClass('visible');
            }
        });
    }
});
 
$(document).ready(function () {
  let amountWrap = '.js-amount-wrap',
    amountInput = '.js-amount-input';

  // проводит все проверки введенного количества (num)
  // запрещается вписывать не кратное минимальному количеству
  // округляем в меньшую сторону, до допустимого кратнго значения (minAmount)
  // не больше чем есть на складе
  function prepareAmount(num, minAmount, maxAmount) {
    //console.log(num+','+ minAmount+','+  maxAmount);
    if (isNaN(num)) {
      num = 0;
    }
    if (isNaN(minAmount)) {
      minAmount = 0;
    }
    if (isNaN(minAmount)) {
      minAmount = 0;
    }
    if (num > 0 && num < minAmount) {
      num = minAmount;
    }
    if (num <= 0) {
      num = minAmount;
    }
    if (num >= maxAmount) {
      num = maxAmount;
    }

    // если значение в поле больше минимально допустимого,
    // то вычисляем сколько раз в это значение входит минимально допустимое
    if (num > minAmount) {
      // точность после запятой
      let fix = 0;
      let str = minAmount.toString();
      if (str) {
        let val = str.split('.');
        if (val && val.length == 2) {
          fix = val[1].length;
        }
      }
      let count = (num / minAmount).toFixed(fix);
      count = Math.floor(count);
      num = (count * minAmount).toFixed(fix);
    }
    return num;
  }

  //увеличение количества товара (страница товара, миникарточка, корзина, страница заказа)
  $('body').on('click', '.js-amount-change-up', function () {
    let obj = $(this).parents(amountWrap).find(amountInput);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    val = val + minAmount;
    val = prepareAmount(val, minAmount, maxAmount);
    obj.val(val).trigger('change');
    return false;
  });

  //уменьшение количества товара (страница товара, миникарточка, корзина, страница заказа)
  $(document.body).on('click', '.js-amount-change-down', function () {
    let obj = $(this).parents(amountWrap).find(amountInput);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    val = val - minAmount;
    val = prepareAmount(val, minAmount, maxAmount);
    obj.val(val).trigger('change');
    return false;
  });

  // Исключение ввода в поле выбора количества недопустимых значений. (страница товара, миникарточка, корзина, страница заказа)
  $(document.body).on('change', amountInput, function () {
    let obj = $(this);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    obj.val(prepareAmount(val, minAmount, maxAmount));
    return false;
  });

  const amountInputs = document.querySelectorAll(amountInput);
  for (const inputAmount of amountInputs) {
    inputAmount.addEventListener('keydown', (e) => {
      if (e.keyCode === 13) {
        e.preventDefault();
        let obj = e.target;
        let val = 1 * obj.value;
        let minAmount = obj.dataset.incrementCount != 0 ? obj.dataset.incrementCount : 1;
        let maxAmount = obj.dataset.maxCount > 0 ? obj.dataset.maxCount :9999999;

        obj.value = prepareAmount(val, minAmount, maxAmount);
        return false;
      }
    });
  }
});
 
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
                        let propertyStr = ''
                        if (element.property) {
                            propertyStr = '[data-property=' + element.property + ']'
                        }
                        var tr = $('td .deleteItemFromCart[data-delete-item-id=' + element.id + ']' + propertyStr + varBlock).closest('tr');
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