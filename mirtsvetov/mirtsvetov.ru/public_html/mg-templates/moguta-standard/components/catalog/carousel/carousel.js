// с-carousel
// ------------------------------------------------------------
var carouselWrap = $('.js-catalog-top-carousel');

carouselWrap.owlCarousel({
    nav: true,
    margin: 16,
    dots: false,
    mouseDrag: false,
    responsive: {
        0: {
            items: 1,
            margin: 10,
        },
        360: {
            items: 2
        },
        768: {
            items: 3
        },
        990: {
            items: 4
        }
    },
    navText: [
        '<div class="c-carousel__arrow c-carousel__arrow--left"><svg class="icon icon--arrow-left"><use xlink:href="#icon--arrow-left"></use></svg></div>',
        '<div class="c-carousel__arrow c-carousel__arrow--right"><svg class="icon icon--arrow-right"><use xlink:href="#icon--arrow-right"></use></svg></div>'
    ]
});

// Если owlCarousel запустился, показываем карусель
if (carouselWrap.hasClass('owl-loaded')) {
    carouselWrap.parent().addClass('c-carousel--active');
}