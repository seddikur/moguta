$(document).ready(function () {

    positionsСharacteristicsWithNames();
});

/**
* Функция позиционирует названия характеристик напротив самих характеристик
*/
function positionsСharacteristicsWithNames() {
    const items = document.querySelectorAll('.js-compare-item');
    if (items.length < 1) {
        return;
    }
    let maxHeightArr = [];
    const charactersiticsFirst = items[0].querySelectorAll('.c-compare__props');
    charactersiticsFirst.forEach((character, index) => {
        maxHeightArr[index] = character.offsetHeight;
    })
    items.forEach((item) => {
        const charactersitics = item.querySelectorAll('.c-compare__props');
        charactersitics.forEach((character, index) => {
            if (maxHeightArr[index] < character.offsetHeight) {
                maxHeightArr[index] = character.offsetHeight;
            }
        });
    });
    items.forEach((item) => {
        const charactersitics = item.querySelectorAll('.c-compare__props');
        charactersitics.forEach((character, index) => {
            character.style.height = maxHeightArr[index] + 'px';
        });
    });

}

var swiper = new Swiper(".js-compare-swiper", {
    slidesPerView: 'auto',
    watchSlidesProgress: true,
    scrollbar: {
        el: ".swiper-scrollbar",
        hide: false,
        draggable: true,
    },
    breakpoints: {
        320: {
            slidesPerView: 'auto',
        }
    }
});

var swiperTwo = new Swiper('.js-compare-props-swiper', {
    breakpoints: {
        320: {
            slidesPerView: 'auto',
        }
    }
});

swiper.controller.control = swiperTwo;
swiperTwo.controller.control = swiper;
const compareTopElement = document.querySelector('.c-compare__items-container');
if (compareTopElement) {
const compareTopPosition = $('.c-compare__items-container').offset().top;
const compareTopWidth = $('.c-compare__items-container').innerWidth();
const compareTopHeight = $('.c-compare__top').innerHeight();

    $('.c-compare__top').height(compareTopHeight);
    $(window).on('scroll', (e) => {
        if (compareTopPosition < window.pageYOffset) {
            $('body').addClass('compare-sticky');
            compareTopElement.style.width = compareTopWidth + 'px';
            compareTopElement.style.position = 'fixed';
            compareTopElement.style.top = '0';
        } else {
            $('body').removeClass('compare-sticky');
            compareTopElement.style.width = '';
            compareTopElement.style.position = '';
            compareTopElement.style.top = '';
        }
    });
}
