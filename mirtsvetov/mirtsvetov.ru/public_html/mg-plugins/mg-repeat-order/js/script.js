var repeatOrderModule = (function(){
  return {
    init: function(){
      // повтор заказа полностью - товары, доставка, оплата 
      $('body').on('click', '.mg-plugin-repeat-order .mg-plugin-repeat-order', function(){
        var id = $(this).data('id');
        var wrap = $(this).parents('.mg-plugin-repeat-order');
        if (id) {
          $.ajax({
          type: "POST",
          url: mgBaseDir + "/ajaxrequest" ,
          dataType: 'json',
          data: {
            mguniqueurl: "action/repeatOrder", // действия для выполнения на сервере
            pluginHandler: 'mg-repeat-order',
            id: id
          },
          success: function (response) {
            if (response.status == 'error') {
              $(wrap).find('.modal-repeat-order').html(response.data.error);              
              $(wrap).find('.mg-plugin-repeat-order-result').show(300);
              $(wrap).find('.mg-plugin-repeat-order-result').addClass('open');
              $(wrap).find('.mg-plugin-repeat-order-result').css('z-index', 200);
              overlay();
            } else {
              $(wrap).find('.mg-plugin-repeat-order-result').html(response.data.success);              
              $(wrap).find('.mg-plugin-repeat-order-result').show(300);
              $(wrap).find('.mg-plugin-repeat-order-result').addClass('open reload');
              $(wrap).find('.mg-plugin-repeat-order-result').css('z-index', 200);
              overlay();
            }
          }
        });
        }
        return false;
      });
      // добавление в корзину товаров из заказа, то что есть
      $('body').on('click', '.mg-plugin-repeat-order .mg-plugin-edit-order', function(){
        var id = $(this).data('id');
        var inform = $(this).data('inform');
        var wrap = $(this).parents('.mg-plugin-repeat-order');
        if (id) {
          $.ajax({
          type: "POST",
          url: mgBaseDir + "/ajaxrequest" ,
          dataType: 'json',
          data: {
            mguniqueurl: "action/addOrderToCart", // действия для выполнения на сервере
            pluginHandler: 'mg-repeat-order',
            id: id,
            inform: inform 
          },
          success: function (response) {
            if (response.status == 'error') {
              $(wrap).find('.mg-plugin-repeat-order-result .header-modal-repeat-order h3').html(response.data.result);
              $(wrap).find('.mg-plugin-repeat-order-result .modal-repeat-order').html(response.data.error);  
              if (response.data.empty) {
                $(wrap).find('.mg-plugin-repeat-order-result .modal-repeat-order-actions').hide();
              }
              $(wrap).find('.mg-plugin-repeat-order-result').show(300);
              $(wrap).find('.mg-plugin-repeat-order-result').addClass('open');
              $(wrap).find('.mg-plugin-repeat-order-result').css('z-index', 200);
              overlay();
            } else {
              window.location.href = mgBaseDir +"/order"
            }
          }
        });
        }
        return false;
      });
      // закрыть всплывающее окно
      $('body').on('click', '.mg-plugin-repeat-order-result .close', function(){
        $(this).parents('.mg-plugin-repeat-order-result').fadeOut(300);
        $(this).parents('.mg-plugin-repeat-order-result').removeClass('open');
        $("#overlay-repeat-order").remove();
      });
      // нажатие на фон
      $('body').on('click', '#overlay-repeat-order', function(){
        if ($('.mg-plugin-repeat-order .mg-plugin-repeat-order-result.open').hasClass('reload')) {
          location.reload();
        }
        $('.mg-plugin-repeat-order .mg-plugin-repeat-order-result.open').fadeOut(300);
        $('.mg-plugin-repeat-order .mg-plugin-repeat-order-result.open').removeClass('open');
        $("#overlay-repeat-order").remove();
      });
        
       /**
       * Фон для заднего плана при открытии всплывающего окна
       */
      function overlay() {
        var docHeight = $(document).height();
        $("body").append("<div id='overlay-repeat-order'></div>");
        $("#overlay-repeat-order").height(docHeight);
      }
    }
  }
}) ();

$(document).ready(function () {
  repeatOrderModule.init();
});