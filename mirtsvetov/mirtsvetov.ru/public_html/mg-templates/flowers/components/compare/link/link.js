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
        $('.informer-compare').addClass('active');

        // Убираем уведомление
        setTimeout(function () {
            $('.informer-compare').removeClass('active');
        }, 2000);

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