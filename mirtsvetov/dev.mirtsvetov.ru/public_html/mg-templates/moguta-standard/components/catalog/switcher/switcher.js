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