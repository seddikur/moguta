 
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
$(document).ready(function () {
    // c-switcher
    // ------------------------------------------------------------
    function rememberView() {
        var className = localStorage['class'];
        var productWrap = $('.js-products-container');
        var switchBtn = $('.js-switch-view');

        //localStorage.clear();
        $('.js-switch-view[data-type="' + className + '"]')
            .addClass('c-switcher__item--active')
            .siblings().removeClass('c-switcher__item--active');

        productWrap.addClass(className);

        switchBtn.on('click', function () {
            var currentView = $(this).data('type');

            productWrap.removeClass('c-goods--grid c-goods--list').addClass(currentView);

            switchBtn.removeClass('c-switcher__item--active');
            $(this).addClass('c-switcher__item--active');

            localStorage.setItem('class', $(this).data('type'));
            return false;
        });
    }

    rememberView();
}); 
$(document).ready(function () {
    var sizeMapObject;
    

    // функция кликов по размерной сетке (страница товара и миникарточка)
    function sizeMapShow(id, search) {
        if (sizeMapObject == undefined) return false;

        var show = 'color';
        if (search == 'color') {
            show = 'size';
        }

        sizeMapObject.find('.' + show).hide();
        var toCheck = '';
        sizeMapObject.find('.variants-table .variant-tr').each(function () {
            if ($(this).data(search) == id) {
                if (sizeMapObject.find(this).data('size') != '') {
                    const dataIdAttribute = sizeMapObject.find(this).data(show)
                        ? '.' + show + '[data-id=' + sizeMapObject.find(this).data(show) + ']'
                        : '.' + show + '[data-id]';
                    sizeMapObject.find(dataIdAttribute).show();
                    if ($(this).data('count') == 0) {
                        sizeMapObject.find(dataIdAttribute).addClass('inactive');
                    } else {
                        sizeMapObject.find(dataIdAttribute).removeClass('inactive');
                    }
                    if (toCheck == '') {
                        toCheck = sizeMapObject.find(dataIdAttribute);
                    }
                }
            }
        });
        if (toCheck != '') {
            toCheck.click();
        }
    }

    // функция выбора варианта после клика по размерной сетке (страница товара и миникарточка)
    function choseVariant() {
        if (sizeMapObject == undefined) return false;
        var color = '';
        var size = '';
        if (sizeMapObject.find('.color').length != 0) {
            color = '[data-color=' + sizeMapObject.find('.color.active').data('id') + ']';
        }
        if (sizeMapObject.find('.size').length != 0) {
            size = '[data-size=' + sizeMapObject.find('.size.active').data('id') + ']';
        }
        sizeMapObject.find('.variants-table .variant-tr' + color + size + ' input[type=radio]').click();
    }


    $(document.body).on('change', '.block-variants input[type=radio]', function (e) {
        //подстановка картинки варианта вместо картинки товара (страница товара и миникарточка)
        //changeMainImgToVariant($(this));

        $(this).parents('tbody').find('tr label').removeClass('active');
        $(this).parents('tr').find('label').addClass('active');

        if (!$('.mg-product-slides').length) {
            var obj = $(this).parents('.js-catalog-item');
            var count = $(this).data('count');
            if (!obj.length) {
                obj = $(this).parents('.mg-compare-product');
            }

            var form = $(this).parents('form');

            if (form.hasClass('actionView')) {
                return false;
            }

            var buttonbuy = $(obj).find('.js-product-controls a:visible').hasClass('js-add-to-cart');

            if (count != '0' && !buttonbuy) {
                if ('false' == window.actionInCatalog) {
                    $(obj).find('.js-product-more').show();
                    $(obj).find('.js-add-to-cart').hide();
                } else {
                    $(obj).find('.js-product-more').hide();
                    $(obj).find('.js-add-to-cart').show();
                }
            } else if (count == '0' && buttonbuy == true) {
                $(obj).find('.js-product-more').show();
                $(obj).find('.js-add-to-cart').hide();
            }
        }
    });


    // делает активными нужные элементы размерной сетки при изменении варианта товара (страница товара и миникарточка)
    $(document.body).on('click', '.variants-table tr input[type=radio]', function () {
        sizeMapObject = $(this).closest('form');
        sizeMapObject.find('.color').removeClass('active');
        sizeMapObject.find('.size').removeClass('active');

        var tmp = $(this).closest('tr').data('color');
        if (tmp != undefined && tmp != '') {
            sizeMapObject.find('.color[data-id=' + $(this).closest('tr').data('color') + ']').addClass('active');
        }
        tmp = $(this).closest('tr').data('size');
        if (tmp != undefined && tmp != '') {
            sizeMapObject.find('.size[data-id=' + $(this).closest('tr').data('size') + ']').addClass('active');
        }
    });

    // обработчик кликов по размерной сетке (страница товара и миникарточка)
    $(document.body).on('click', '.color', function () {
        sizeMapObject = $(this).parents('form');
        if (typeof (sizeMapMod) != undefined && sizeMapMod !== 'size') {
            sizeMapObject.find('.color').removeClass('active');
            $(this).addClass('active');
            sizeMapShow($(this).data('id'), 'color');
            if (sizeMapObject.find('.size').length == 0) {
                choseVariant();
            }
        } else {
            sizeMapObject.find('.color').removeClass('active');
            $(this).addClass('active');
            choseVariant();
        }
    });

    $(document.body).on('click', '.size', function () {
        sizeMapObject = $(this).parents('form');
        if (typeof (sizeMapMod) != undefined && sizeMapMod === 'size') {
            sizeMapObject.find('.size').removeClass('active');
            $(this).addClass('active');
            sizeMapShow($(this).data('id'), 'size');
            if (sizeMapObject.find('.color').length == 0) {
                choseVariant();
            }
        } else {
            sizeMapObject.find('.size').removeClass('active');
            $(this).addClass('active');
            choseVariant();
        }
    });

    $('.variants-table').each(function () {
        var tmp = $(this).closest('form').attr('data-product-color');
        if (tmp == undefined || tmp == '') {
            tmp = $(this).find('tr:eq(0)').data('color');
        }
        if (tmp != undefined && tmp != '') {
            $(this).parents('form').find('.color[data-id=' + tmp + ']').addClass('active');
        } 
        var tmp = $(this).closest('form').attr('data-product-size');
        if (tmp == undefined || tmp == '') {
        tmp = $(this).find('tr:eq(0)').data('size');
        }
        if (tmp != undefined && tmp != '') {
            $(this).parents('form').find('.size[data-id=' + tmp + ']').addClass('active');
        }
    });

    $('.color-block .color.active').click();
    $('.size.active').click();
    // костыль для верстки чекбокса выбранного варианта в таблице вариантов товара без размерной сетки (страница товара и миникарточка)
    $('.variant__column input[name=variant][checked=checked]').each(function () {
        const form = $(this).closest('form'); 
        form.attr("data-product-variant")
        form.find('.variant__column input[name=variant][value="'+form.attr("data-product-variant")+'"]').parents('.c-form').addClass('active');
        //$(this).parents('.c-form').addClass('active');
    });

    // для выбора варианта по якорю
    if (varHashProduct === 'true' || varHashProduct === true) {
        if (location.hash != '') {
            code = location.hash.replace('#', '');
            code = decodeURI(code);
            if (typeof (sizeMapMod) != undefined && sizeMapMod == 'size' && $('[data-code="' + code + '"]:eq(0)').closest('tr[data-size!=\'\']').length) {
                size = $('[data-code="' + code + '"]:eq(0)').closest('tr').data('size');
                $('.size[data-id=' + size + ']').trigger('click');
            } else if ($('[data-code="' + code + '"]:eq(0)').closest('tr[data-color!=\'\']').length) {
                color = $('[data-code="' + code + '"]:eq(0)').closest('tr').data('color');
                $('.color[data-id=' + color + ']').trigger('click');
            }
            $('[data-code="' + code + '"]:eq(0)').click();
        }

        // подстановка якоря в url
        $(document.body).on('click', '.variants-table tr input[type=radio]', function () {
            data = $(this).data('code');
            if (data != undefined) location.hash = data;
        });
    }

});


// Функция получает изображение варианта и подменяет его в карусели
function changeMainImgToVariant(response, page) {
    var secondarySlider = $('.js-secondary-img-slider');
    var activeLink = $('.js-main-img-slider .active .js-images-link');
    var firstSlide = secondarySlider.find('[data-slide-index=0]');
    // Если пришла не заглушка, то продолжаем
    if (response.data.image_orig !== '' && !response.data.image_orig.includes('no-img.jpg')) {
        // Если это миникарточка
        if (page.hasClass('js-catalog-item')) {
            // Находим изображение товара в миникарточке
            var itemImg = page.find('.js-catalog-item-image');
            // Заменяем его
            changeImgSrc(
                itemImg,
                '30',
                response.data.image_thumbs,
            );
        }

        // Если это страница товара
        else {
            // Если у товара не одно изображение
            if (secondarySlider.length) {
                // Открываем первый слайд
                firstSlide.click();

                // меняем первое изображение товара в карусели миниатюр
                changeImgSrc(
                    firstSlide.find('.js-img-preview'),
                    '30',
                    response.data.image_thumbs,
                );

                // Меняем первое изображение товара в основном слайдере
                activeLink = $('.js-main-img-slider .active .js-images-link');
                changeImgSrc(
                    activeLink.find('.js-product-img'),
                    '70',
                    response.data.image_thumbs,
                );

            } else {
                // Меняем единственное изображение товара
                changeImgSrc(
                    $('.js-product-img'),
                    '70',
                    response.data.image_thumbs,
                );
            }

            // Меняем изображение в ссылке на fancybox
            activeLink.attr('href', response.data.image_orig);

            // Меняем изображение показыващееся при наведении на основное (лупа)
            activeLink = $('.js-main-img-slider .active .js-images-link');
            activeLink.find('.js-zoom-img').attr('style', 'background-image: url("' + response.data.image_orig + '")');
        }
    }
    //Смена остатков по складам если они выводятся
    if (typeof (response.data.storage) != 'undefined') {
        for (const key in response.data.storage) {
            const countOnStorageElement = document.querySelector('.a-storage .count-on-storage[data-id="' + key + '"]');
            if (countOnStorageElement) {
                countOnStorageElement.innerHTML = response.data.storage[key];
            }
        }
    }
}

// Минифункция меняющая src и srcset у тега img
function changeImgSrc(imgElem, size, thumbsArr) {
    if (thumbsArr[size]) {
        imgElem.attr('src', thumbsArr[size]);
    }
    if (thumbsArr['2x' + size]) {
        imgElem.attr('srcset', thumbsArr['2x' + size] + ' 2x');
    }
}
 
//пересчет цены товара аяксом (страница товара, миникарточка)
$(document.body).on("change", ".js-onchange-price-recalc", function () {
  var form = ".js-product-form";
  var request = $(form).formSerialize();
  var productId = $(form).data("product-id");

  var priceBlock = ".js-change-product-price";
  var productList = $(".js-product-page");
  var miniProduct = ".js-catalog-item";

  if ($(this).parents(miniProduct).length) {
    // для вызова из каталога
    productList = $(this).parents(miniProduct);
    form = productList.find(form);
    productId = form.data("product-id");
    request = productList.find(form).formSerialize();
    priceBlock = productList.find(priceBlock);
  }

  if ($(this).parents(".mg-compare-product").length) {
    // для вызова из сравнений
    priceBlock = $(this).parents(".mg-compare-product").find(priceBlock);
    request = $(this)
      .parents(".mg-compare-product")
      .find(".property-form")
      .formSerialize();
    request += "&remInfo=false";
    productList = $(this).parents(".mg-compare-product");
  }

  // для вызова из карточки товара на странице товара
  if ($(this).parents(".js-product-page")) {
    priceBlock = productList.find(priceBlock);
  }

  var tempThis = $(this);

  // Пересчет цены
  $.ajax({
    type: "POST",
    url: mgBaseDir + "/product/",
    data: "calcPrice=1&inCartProductId=" + productId + "&" + request,
    dataType: "json",
    cache: false,
    success: function (response) {
      // функция подстановки картинки варианта вместо картинки товара (на странице товара или миникарточке)
      if(tempThis.parents('.block-variants').length) {
        changeMainImgToVariant(response, productList);
      }

      if (response.data.wholesalesTable != undefined) {
        $(".wholesales-data").html(response.data.wholesalesTable);
      }

      if (response.data.productOpFields != undefined) {
        tempThis
          .parents(".property-form")
          .parents(".product-details-block,.product-wrapper")
          .find(".product-opfields-data")
          .html(response.data.productOpFields);
      }

      window.actionInCatalog = response.data.actionInCatalog;

      productList.find(".rem-info").hide();

      productList.find(".buy-container.product .hidder-element").hide();
      if (productList.find(".buy-block .count").length > 0 || response.data.count == 0) {
        productList.find(".js-product-controls").hide();
        productList.find(".c-product__message").show();
      }

      if (response.status === "success") {
        $('.c-button[rel="nofollow"]').attr(
          "href",
          response.data.buttonMessage
        );
        if ($(priceBlock).find(".product-default-price").length) {
          $(priceBlock)
            .find(".product-default-price")
            .html(response.data.price);
        } else {
          $(priceBlock).html(response.data.price);
        }         
        $(priceBlock).find(".product-default-price").html(response.data.price);
        productList.find(".code").text(response.data.code);
        var message = "";

        if (response.data.title) {
          message =
            locale.countMsg1 +
            response.data.title.replace("'", '"') +
            locale.countMsg2 +
            response.data.code +
            locale.countMsg3;
        }

        productList
          .find(".rem-info a")
          .attr("href", mgBaseDir + "/feedback?message=" + message);
        productList.find(".code-msg").text(response.data.code);

        var val = response.data.count;

        if (val != 0) {
          $(".depletedLanding").hide();
          $(".addToOrderLanding").show();

          productList.find(".rem-info").hide();
          productList.find(".js-product-controls").show();
          if (productList.find(".buy-block .count").length > 0) {
            productList.find(".js-product-controls").show();
            productList.find(".c-product__message").hide();
          }
          productList.find(".buy-container.product").show();
          if (
            !productList
              .find(".js-product-controls a:visible")
              .hasClass("js-add-to-cart")
          ) {
            if ("false" == window.actionInCatalog) {
              if ($('.js-product-page').length != 0) {
                productList.find(".js-product-more").hide();
                productList.find(".js-add-to-cart").show();
              } else {
                productList.find(".js-product-more").show();
                productList.find(".js-add-to-cart").hide();
              }
            } else {
              productList.find(".js-product-more").hide();
              productList.find(".js-add-to-cart").show();
            }

            productList.find(".js-product-controls").show();
          }
        } else {
          $(".depletedLanding").show();
          $(".addToOrderLanding").hide();
          productList.find(".js-product-controls").show();
          productList.find(".rem-info").show();
          if (productList.find(".buy-block .count").length > 0) {
            //$('.js-product-controls').hide();
          }
          productList.find(".buy-container.product").hide();
          if (
            productList
              .find(".js-product-controls a:visible")
              .hasClass("js-add-to-cart")
          ) {
            productList.find(".js-product-more").show();
            productList.find(".js-add-to-cart").hide();
            // productList.find('.js-product-controls:first').hide();
          }
        }
        if (response.data.count_layout) {
          if (productList.find(".count").length > 0) {
            productList
              .find(".count")
              .parent()
              .html(response.data.count_layout);
          } else {
            productList
              .find(".in-stock")
              .parent()
              .html(response.data.count_layout);
          }
        } else {
          if (val == "\u221E" || val == "" || parseFloat(val) < 0) {
            val =
              '<span itemprop="availability" class="count"><span class="sign">&#10004;</span>' +
              locale.countInStock +
              "</span>";
            productList.find(".rem-info").hide();
          } else {
            val =
              locale.remaining +
              ': <span itemprop="availability" class="label-black count">' +
              val +
              "</span> " +
              locale.pcs;
          }
          productList.find(".count").parent().html(val);
        }

        val = response.data.old_price;
        
        const oldPrice = parseFloat(response.data.old_price.split(' ').join(''));
        const currentPrice = parseFloat(response.data.price.split(' ').join(''))
        if (oldPrice > currentPrice) {
            productList.find('.js-discount-sticker').show()
            let sale = Math.round((oldPrice - currentPrice) / (oldPrice / 100));
            sale = '-'+ sale + ' %';
            productList.find('.js-discount-sticker').html(sale);
            productList.find('.old-price').text(response.data.old_price);
            productList.find('.old-price').show();
            productList.find('.js-old-price-container').show();
        }
        else {
            productList.find('.js-discount-sticker').hide()
            productList.find('.old-price').text('');
            productList.find('.old-price').hide();
            productList.find('.js-old-price-container').hide();
        }

        productList
          .find(".amount_input")
          .data("max-count", response.data.count);

        productList.find(".weight").text(response.data.weightCalc);

        if (
          parseFloat(productList.find(".amount_input").val()) >
          parseFloat(response.data.count)
        ) {
          val = response.data.count;
          if (val == "\u221E" || val == "" || parseFloat(val) < 0) {
            val = productList.find(".amount_input").val();
          }
          if (val == 0) {
            val = 1;
          }

          productList.find(".amount_input").val(val);
        }
      }

      if (
        response.data.storage != undefined &&
        response.data.storage.length > 0
      ) {
        maxStorageCount = 0;
        for (var i in response.data.storage) {
          $(".count-on-storage[data-id=" + i + "]").html(
            response.data.storage[i]
          );
          if (response.data.storage[i] > maxStorageCount)
            maxStorageCount = response.data.storage[i];
        }
        productList.find(".actionBuy .amount_input").data("max-count", maxStorageCount);
      }
    },
  });

  return false;
});

 
$(document).ready(function() {
    // добавление в избранное
    var counter = $('.js-favourite-count'),
        informer = $('.js-favorites-informer'),
        informerOpenedClass = 'favourite--open',
        btnAddClass = '.js-add-to-favorites',
        btnRemoveClass = '.js-remove-to-favorites';


    $('body').on('click', btnAddClass, function () {
        obj = $(this);
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/favorites/",
            data: {'addFav': '1', 'id': $(this).data('item-id')},
            dataType: "json",
            cache: false,
            success: function (response) {
                obj.hide();
                obj.parent().find(btnRemoveClass).show();
                counter.show();
                counter.html(response);
                informer.fadeOut('normal').fadeIn('normal');
                informer.removeClass(informerOpenedClass);

                setTimeout(function () {
                    informer.addClass(informerOpenedClass);
                }, 0);
            }
        });
    });

// удаление из избранного
    $('body').on('click', btnRemoveClass, function () {
        obj = $(this);
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/favorites/",
            data: {'delFav': '1', 'id': $(this).data('item-id')},
            dataType: "json",
            cache: false,
            success: function (response) {
                obj.hide();
                informer.fadeOut('normal').fadeIn('normal');
                obj.parent().find(btnAddClass).show();
                counter.html(response);
            }
        });
    });
});


 
var smallCartTemplate = document.querySelector(".smallCartRowTemplate");
smallCartTemplate = smallCartTemplate ? smallCartTemplate.content.querySelector("tr") : '';
if (popup = document.querySelector(".popupCartRowTemplate")) {
  var popUpTemplate = popup.content.querySelector("tr");
}

// Заполнение корзины аяксом
$("body").on("click", ".js-add-to-cart", function (e) {
  var productId = $(this).data("item-id");
  transferEffect(productId, $(this), ".js-catalog-item");

  var request =
    "inCartProductId=" + $(this).data("item-id") + "&amount_input=1";
  if ($(this).parents(".js-product-form").length) {
    request = $(this).parents(".js-product-form").formSerialize();
    if (!$(".js-amount-wrap").length) {
      request += "&amount_input=1";
    }
  }

  $.ajax({
    type: "POST",
    url: mgBaseDir + "/cart",
    data: "updateCart=1&inCartProductId=" + productId + "&" + request,
    dataType: "json",
    cache: false,
    success: function (response) {
      if (popup) {
        $("#js-modal__cart").addClass("c-modal--open");
        $("html").addClass("c-modal--scroll");

        if ($("#c-modal__cart").length > 0) {
          $("#c-modal__cart").addClass("c-modal--open");
          if ($(document).height() > $(window).height()) {
            $("html").addClass("c-modal--scroll");
          }
        }
      }
      
      if ("success" == response.status) {
        dataSmalCart = "";
        dataPopupCart = "";
        response.data.dataCart.forEach(printSmalCartData);

        $(".mg-desktop-cart .small-cart-table").html(dataSmalCart);

        if ($(".js-popup-cart-table").length) {
          $(".js-popup-cart-table").html(dataPopupCart);
        }
        $(".total .total-sum span.total-payment").text(response.data.cart_price_wc);
        $(".pricesht").text(response.data.cart_price);
        let cartCount = Number(response.data.cart_count).toFixed(2) * 100 % 100 > 0 ? Number(response.data.cart_count).toFixed(2).replace('.', ',') : response.data.cart_count;
        $(".countsht").text(cartCount);
        $(".small-cart").show();
      }
    },
  });

  return false;
});

// строит содержимое маленькой и всплывающей корзины в выпадащем блоке
function printSmalCartData(element, index, array) {
  var html = $($.parseHTML("<table><tbody></tbody></table>"));
  html.find("tbody").html(smallCartTemplate.cloneNode(true));
  html
    .find(".js-smallCartImg")
    .attr("src", element.image_thumbs[30])
    .attr("alt", element.title)
    .attr("srcset", element.image_thumbs["2x30"] + " 2x");

  var prodUrl =
    mgBaseDir +
    "/" +
    (element.category_url || element.category_url == ""
      ? element.category_url
      : "catalog/") +
    element.product_url;
  html.find(".js-smallCartImgAnchor").attr("href", prodUrl);
  html
    .find(".js-smallCartProdAnchor")
    .attr("href", prodUrl)
    .text(element.title);

  html.find(".js-smallCartProperty").html(element.property_html);
  let cartCount = Number(element.countInCart).toFixed(2) * 100 % 100 > 0 ? Number(element.countInCart).toFixed(2).replace('.', ',') : element.countInCart;
  html.find(".js-smallCartAmount").text(cartCount);
  html.find(".js-cartPrice").text(element.priceInCart);

  html
    .find(".js-delete-from-cart")
    .attr("data-delete-item-id", element.id)
    .attr("data-property", element.property)
    .attr("data-variant", element.variantId);

  window.dataSmalCart += html.find("tr:first").parent().html();

  if ($(".popup-body .small-cart-table").length) {
    html = $(
      $.parseHTML(
        "<table><tbody></tbody></table>"
      )
    );

    html.find("tbody").html(smallCartTemplate.cloneNode(true));

    html.find(".js-smallCartImgAnchor").attr("href", prodUrl);
    html
      .find(".js-smallCartProdAnchor")
      .attr("href", prodUrl)
      .text(element.title);

    html
      .find(".js-smallCartImg")
      .attr("src", element.image_thumbs[30])
      .attr("alt", element.title)
      .attr("srcset", element.image_thumbs["2x30"] + " 2x");

    html.find(".js-smallCartProperty").html(element.property_html);
    let cartCount = Number(element.countInCart).toFixed(2) * 100 % 100 > 0 ? Number(element.countInCart).toFixed(2).replace('.', ',') : element.countInCart;
    html.find(".js-smallCartAmount").text(cartCount);
    html.find(".js-cartPrice").text(element.priceInCart);

    html
      .find(".js-delete-from-cart")
      .attr("data-delete-item-id", element.id)
      .attr("data-property", element.property)
      .attr("data-variant", element.variantId);

    dataPopupCart += html.find("tr:first").parent().html();
  }
}

// Эффект полёта товара в корзину
function transferEffect(productId, buttonClick, wrapperClass) {
  var $css = {
    height: "100%",
    opacity: 0.5,
    position: "relative",
    "z-index": 100,
  };

  var $transfer = {
    to: $(".small-cart-icon"),
    className: "transfer_class",
  };

  //если кнопка на которую нажали находится внутри нужного контейнера.
  if (
    buttonClick
      .parents(wrapperClass)
      .find("img[data-transfer=true][data-product-id=" + productId + "]").length
  ) {
    // даем способность летать для картинок из слайдера новинок и прочих.
    var tempObj = buttonClick
      .parents(wrapperClass)
      .find("img[data-transfer=true][data-product-id=" + productId + "]");
    tempObj.effect("transfer", $transfer, 600);
    $(".transfer_class").html(tempObj.clone().css($css));
  } else {
    //Если кнопка находится не в контейнере, проверяем находится ли она на странице карточки товара.
    if ($(".product-details-image").length) {
      // даем способность летать для картинок из галереи в карточке товара.
      $(".product-details-image").each(function () {
        if ($(this).css("display") != "none") {
          $(this).find(".mg-product-image").effect("transfer", $transfer, 600);
          $(".transfer_class").html($(this).find("img").clone().css($css));
        }
      });
    } else {
      // даем способность летать для всех картинок.
      var tempObj = $(
        "img[data-transfer=true][data-product-id=" + productId + "]"
      );
      tempObj.effect("transfer", $transfer, 600);
    }
  }

  if (tempObj) {
    $(".transfer_class").html(tempObj.clone().css($css));
  }
}
 
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
