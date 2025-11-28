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