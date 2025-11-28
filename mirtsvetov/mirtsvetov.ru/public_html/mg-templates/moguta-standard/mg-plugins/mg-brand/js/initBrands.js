// ВНИМАНИЕ! Данный код 16.03.2022 был интегрирован непосредственно в библиотеку myowl.carousel.js
// для инициализации при смене макета на mg-cloud-air. Данный скрипт подключался в файле
// shortcode.php, строчка подключения скрипта там закомментирована

var InitBrands = function() {
    let config = {};
    if (typeof brandSliderConfig === "undefined") {
      config = {
        autoplay: false,
        items: 3,
        loop: false,
        margin: 10,
        mouseDrag: true,
        nav: true,
        responsive: {
          "0": {
              "items": 1
          },
          "600": {
              "items": 3
          },
          "1000": {
              "items": 5
          }
        }
      }
    } else {
      config = {...brandSliderConfig};
    }
    
    let leftArrow = '<div class="brands__arrow brands__arrow-left"><svg viewBox="0 0 512 512"><path d="M354.1 512l59.8-59.7L217.6 256 413.9 59.7 354.1 0l-256 256"></svg></div>';
    let rightArrow = '<div class="brands__arrow brands__arrow-right"><svg viewBox="0 0 512 512"><path d="M157.9 0L98.1 59.7 294.4 256 98.1 452.3l59.8 59.7 256-256"></path></svg></div>';
    $('.brands__carousel').addClass('owl-carousel').owlMyCarousel({
      items: config.items,
      responsive: config.responsive,
      nav: config.nav,
      margin: config.margin,
      mouseDrag: config.mouseDrag,
      touchDrag: config.touchDrag,
      loop: config.loop,
      autoplay: config.autoplay,
      navText     : [leftArrow, rightArrow],
      dots		: "true"
    });
}
  
$(document).ready(function () {
  InitBrands();
});