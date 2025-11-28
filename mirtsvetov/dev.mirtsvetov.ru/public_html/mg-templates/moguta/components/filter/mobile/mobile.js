$(document).ready(function() {
    // c-filter
// ------------------------------------------------------------
    $('body').on('click', 'a[href^="#c-filter"]', function (a) {
        a.preventDefault();
        var b = $(this).attr('href');
        $(b).addClass('c-filter--active');
        $('body').addClass('fixed__body');

    }), $('body').on('click', '.c-filter', function () {
        $('.c-filter').removeClass('c-filter--active');
        $('body').removeClass('fixed__body');

    }), $('body').on('click', '.c-filter__content', function (a) {
        a.stopPropagation()
    });
});