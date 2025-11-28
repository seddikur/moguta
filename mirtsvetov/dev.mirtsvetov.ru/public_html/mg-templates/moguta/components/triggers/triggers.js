var carouselTriggers = $('.js-triggers-carousel');
setTimeout(() => {
    carouselTriggers.owlCarousel({
        nav: false,
        margin: 16,
        dots: false,
        loop: true,
        mouseDrag: false,
        autoplay: true,
        autoplayTimeout: 5000,
        responsive: {
            0: {
                items: 1,
                margin: 10,
            },
            450: {
                items: 2,
            },
            800: {
                items: 3,
            },
            1200: {
                items: 4,
            },
            1450: {
                items: 4,
            }
        },
    });

    // Если owlCarousel запустился, показываем карусель
    if (carouselTriggers.hasClass('owl-loaded')) {
        carouselTriggers.parent().addClass('c-carousel--active');
    }
}, 0)