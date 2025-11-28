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
