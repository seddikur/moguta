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
