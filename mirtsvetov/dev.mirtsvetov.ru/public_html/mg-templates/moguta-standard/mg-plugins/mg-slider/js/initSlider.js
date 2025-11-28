function initSliderConstructor(slider) {
    if (!slider) return;
    const sliderId = slider.dataset.sliderConfig;
    const sliderSelector = sliderId === 'default' 
        ? '.swiper-container'
        : '.swiper-container-' + sliderId;
    const swiperAnimationSlider = new SwiperAnimation();
    // конфиг по умолчанию, если не был получен с сервера
    const defaultSliderConfig = {
        direction: "horizontal",
        speed: 500,
        autoplay: {
            delay: 60000,
            disableOnInteraction: true,
        },
        effect: "slide",                
        loop: false,
        autoHeight: false,
        pagination: {
            el: '.swiper-pagination',
            type: "bullets",
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },     
        on: {
            init: function () {
                swiperAnimationSlider.init(this).animate();
            },
            slideChange: function () {
                swiperAnimationSlider.init(this).animate();
            }
        }
    };
    let sliderConfig = window[`sliderConfig_${sliderId}`];
    let swiperSlider = null;

    if (sliderConfig) {
        // используем готовый конфиг
        swiperSlider = new Swiper(sliderSelector, sliderConfig);
    } else {
        // запрашиваем опции плагина и  строим конфиг
        admin.ajaxRequest({
            mguniqueurl: 'action/getSlider',
            pluginHandler: 'mg-slider',
            id: sliderId
        },
        function(response) {
            if (response.status == 'success' && response.data) {
                const sliderOptions = response.data.options
                sliderConfig = {
                    direction: sliderOptions.direction,
                    speed: sliderOptions.speed,
                    effect: sliderOptions.effect,
                    loop: sliderOptions.loop,
                    on: {
                        init: function () {
                            swiperAnimationSlider.init(this).animate();
                        },
                        slideChange: function () {
                            swiperAnimationSlider.init(this).animate();
                        }
                    }
                };
                if (sliderOptions.navigation === 'true') {
                    sliderConfig.navigation = {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    }
                }
                if (sliderOptions.pagination) {
                    sliderConfig.pagination = {
                        el: '.swiper-pagination',
                        type: sliderOptions.pagination_type,
                        clickable: true,
                    }
                }
                swiperSlider = new Swiper(sliderSelector, sliderConfig);
            } else {
                // запускаем с дефолтным конфигом
                swiperSlider = new Swiper(sliderSelector, defaultSliderConfig);
            }
        });
    }
};
