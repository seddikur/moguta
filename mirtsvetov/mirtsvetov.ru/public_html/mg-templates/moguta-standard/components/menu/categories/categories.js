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