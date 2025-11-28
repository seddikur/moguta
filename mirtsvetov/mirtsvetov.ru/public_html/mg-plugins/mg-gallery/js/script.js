//Инициализация fancybox
  $("a.pic").fancybox({
    'overlayShow': false,
    tpl: {
      next: '<a title="Вперед" class="fancybox-nav fancybox-next" href="javascript:;"><span></span></a>',
      prev: '<a title="Назад" class="fancybox-nav fancybox-prev" href="javascript:;"><span></span></a>'
    }
  });
  
function resizeWidthMgGallery(){
  const countBlock = Number($('span.gal').attr('data-line'));
  let marginBlock = 1;
  if ($('#mg-gallery .mg-gallery-list li').length) {
    marginBlock = Number($('#mg-gallery .mg-gallery-list li').css("margin").replace(/px/g, ''));
  }
  const widthBOX = $('#mg-gallery').width() - 20;
  const widthBlock = widthBOX/countBlock;
  const widthBlockPersent = 100*widthBlock/widthBOX;
  $('#mg-gallery ul.mg-gallery-list li').css("width",'calc(' + widthBlockPersent + "% - " + (marginBlock*2 + 1) + "px) ");
};
