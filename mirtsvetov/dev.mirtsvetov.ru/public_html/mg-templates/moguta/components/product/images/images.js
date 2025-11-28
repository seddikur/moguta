$(document).ready(function () {
  // fancybox
  // ------------------------------------------------------------
  var tempLocalHtml = $("div.main-product-slide .owl-stage").html();
  var sync1 = $("div.main-product-slide");
  var sync2 = $(".js-secondary-img-slider");
  Startfancybox();
  function Startfancybox() {
    $('.mg-product-slides .fancy-modal').fancybox({
      'overlayShow': false,
      animationEffect: "zoom",
      buttons: [
        "zoom",
        "slideShow",
        "fullScreen",
        "download",
        "close"
      ],
      afterClose: function (elem) {
        syncPositionFancybox(elem);
        $("div.main-product-slide .owl-stage").html(tempLocalHtml);
        changeImgFirstSlide(elem);
        Startfancybox();
        showCloseImage(elem);
        if ($('.js-zoom-img').length > 0) {
          $('.js-zoom-img').on('mousemove', zoom);
        }
      }
    });
  }

  function syncPositionFancybox(elem) {
    var src = elem.slides[elem.currIndex].thumb;
    var srcSmall = src.split('/').pop();
    src = src.replace(srcSmall, '30_' + srcSmall.replace('70_', ''));
    var current = sync2.find('img[src="' + src + '"]').parent().data('slide-index');
    sync2
      .find(".owl-item")
      .removeClass("current")
      .eq(current)
      .addClass("current");
    var start = sync2.find('.owl-item').first().index();
    var end = sync2.find('.owl-item').last().index();
    if (current > end) {
      sync2.data('owl.carousel').to(current, 100, true);
    }
    if (current < start) {
      sync2.data('owl.carousel').to(current, 100, true);
    }
    sync1.find('.owl-item').removeClass('active');
    $(sync1.find('.owl-item').not('.cloned ')[current]).addClass('active');

    if (!sync2.data('owl.carousel')) return;
    sync2.data('owl.carousel').to(current, 100, true);
  }

  function showCloseImage(elem) {
    const index = elem.currIndex;
    const images = sync2.find('.owl-item');
    if (images.length === 0) return;
    setTimeout(() => {
      images[index].click();
    }, 0);
  }

  // Функция изменяет первое изображение большого слайдера (костыль против костыля с подстановкой tempLocalHtml после закрытия fancybox)
  function changeImgFirstSlide(elem) {
    const firstMainSlide = sync1.find('.owl-item')[0];
    if (!firstMainSlide) return;
    const image = firstMainSlide.querySelector('.js-product-img');
    const figure = firstMainSlide.querySelector('.js-zoom-img');
    const link = firstMainSlide.querySelector('.js-images-link');
    link.dataset.url = elem.group[0].src;
    link.href = elem.group[0].src;
    image.src = elem.group[0].src;
    image.srcset = elem.group[0].src;
    if (figure) {
      figure.style.backgroundImage = 'url("' + elem.group[0].src + '")';
    }
  }
}); // end ready
