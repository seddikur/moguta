$(document).ready(function () {
    // Синхронизация Главного изображения и превью
    // Главное изображение
    var sync1 = $(".js-main-img-slider");
    // Превью
    var sync2 = $(".js-secondary-img-slider");

    if (sync2.length) {
        // Слайдов на странице
        var slidesPerPage = 3;
        var syncedSecondary = true;

        sync1.owlCarousel({
            items: 1,
            slideSpeed: 2000,
            nav: false,
            autoplay: false,
            dots: false,
            loop: false,
            responsiveRefreshRate: 200,
        }).on('changed.owl.carousel', syncPosition);

        sync2
            .on('initialized.owl.carousel', function () {
                sync2.find(".owl-item").eq(0).addClass("current");
            })
            .owlCarousel({
                items: slidesPerPage,
                dots: false,
                nav: true,
                smartSpeed: 200,
                slideSpeed: 500,
                slideBy: 1,
                margin: 10,
                responsiveRefreshRate: 100,
                navText: [
                    '<div class="c-carousel__arrow c-carousel__arrow--left"><svg class="icon icon--arrow-left"><use xlink:href="#icon--arrow-left"></use></svg></div>',
                    '<div class="c-carousel__arrow c-carousel__arrow--right"><svg class="icon icon--arrow-right"><use xlink:href="#icon--arrow-right"></use></svg></div>'
                ]
            }).on('changed.owl.carousel', syncPosition2);

        function syncPosition(el) {
            var count = el.item.count - 1;
            var current = Math.round(el.item.index - (el.item.count / 2) - .5);

            if (count === 1) {
                current--;
            }

            if (current < 0) {
                current = count;
            }
            if (current > count) {
                current = 0;
            }


            sync2
                .find(".owl-item")
                .removeClass("current")
                .eq(el.item.index)
                .addClass("current");
            var onscreen = sync2.find('.owl-item').length - 1;
            var start = sync2.find('.owl-item').first().index();
            var end = sync2.find('.owl-item').last().index();

            if (current > end) {
                sync2.data('owl.carousel').to(current, 100, true);
            }
            if (current < start) {
                sync2.data('owl.carousel').to(current - onscreen, 100, true);
            }
        }

        function syncPosition2(el) {
            if (syncedSecondary) {
                var number = el.item.index;
                sync1.data('owl.carousel').to(number, 100, true);
            }
        }

        sync2.on("click", ".owl-item", function (e) {
            e.preventDefault();
            var number = $(this).index();
            sync1.data('owl.carousel').to(number, 300, true);
        });
    }

});
