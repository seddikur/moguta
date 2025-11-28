var javascripts = [];

function includeJS(path) {

  for (var i = 0; i < javascripts.length; i++) {
    if (path == javascripts[i]) {
      // alert('JavaScript: ['+path+'] уже был подключен ранее!');
      return false;
    }
  }
  javascripts.push(path);
  $.ajax({
    url: path,
    dataType: "script", // при типе script JS сам инклюдится и воспроизводится без eval
    async: false
  });
}

var admin = (function () {

  return {
    PICKR_STORAGE: [],
  	init: function() {
  		// обработка клика по переключателю режима редактирования
  		$('body').on('click', '.js-admin-edit-site', function () {
            $(this).toggleClass('admin-bar-link_edit_on');
            var enabled = false;
            if ($(this).hasClass('admin-bar-link_edit_on')) {
              enabled = true;
            }
            admin.ajaxRequest({
              mguniqueurl: "action/setSiteEdit",
              enabled: enabled
            },
            function (response) {
              location.reload();
            });
          });

  		// обработка клика по кнопки - сбросить кэш
  		$('body').on('click', '.js-admin-clear-cash', function () {
  		  admin.ajaxRequest({
  		    mguniqueurl: "action/clearСache",
  		  },
  		    function (response) {
  		      location.reload();
  		    }
  		  );
  		});

      $('body').on('click', '.closeClass', function() {
        $(this).parent().parent().detach();
      });

  	},

		ajaxRequest: function (data, callBack, loader, dataType, noAlign) {
		  if (!dataType)
		    dataType = 'json';
		  $.ajax({
		    type: "POST",
		    url: "ajax",
		    data: data,
		    cache: false,
		    dataType: dataType,
		    success: callBack,
		    beforeSend: function () {},
		    complete: function () {
		      // выполнение стека отложенных функций после AJAX вызова    
		      if (admin.AJAXCALLBACK) {
		        //debugger;
		        admin.AJAXCALLBACK.forEach(function (element, index, arr) {
		          eval(element.callback).apply(this, element.param);
		        });
		        admin.AJAXCALLBACK = null;
		      }
		    },
		    error: function (request, status, error) {}
		  });
		},

    getSettingFromDB: function (setting) {
      return $.ajax({
        type: "POST",
        url: mgBaseDir+"/ajaxrequest",
        data:{
          actionerClass: 'Ajaxuser', // класс Pactioner в Pactioner.php - в папке плагина
          action: 'getSetting',
          setting: setting,
        },
        cache: false,
        dataType: 'json',
      });
    },
    /**
     * Иницилезирует Pickr. Ищет div с id="%name%" и input c class="option" и name="%name%".
     * Цвет берёт из значения input'а.
     * 
     * Пример:
     * <div id="colorMain"></div>
     * <input type="hidden" class="option" name="colorMain" value="#00FF00">
     * @param string name Название для элементов 
     * @param boolean opacity Вкл/Выкл настройки прозрачности
     */
    initPickr: function(name, opacity, show_now) {
      if ($('#'+name).length == 0) {
        return false;
      }

      if (typeof opacity === "undefined" || opacity === null) {
        opacity = false;
      }

      if (typeof show_now === "undefined" || show_now === null) {
        show_now = false;
      }

      var val = $('[name="'+name+'"]').val();

      tmpPickr = new Pickr.create({
        el: '#'+name,
        theme: 'classic',
        default: val != ''
                  ? val
                  : '#FFFFFF',
      
        swatches: [
          'rgb(244, 67, 54)',
          'rgb(233, 30, 99)',
          'rgb(156, 39, 176)',
          'rgb(103, 58, 183)',
          'rgb(63, 81, 181)',
          'rgb(33, 150, 243)',
          'rgb(3, 169, 244)',
          'rgb(0, 188, 212)',
          'rgb(0, 150, 136)',
          'rgb(76, 175, 80)',
          'rgb(139, 195, 74)',
          'rgb(205, 220, 57)',
          'rgb(255, 235, 59)',
          'rgb(255, 193, 7)'
        ],
        showAlways: false,
      
        components: {
          preview: true,
          opacity: opacity,
          hue: true,
      
          interaction: {
            hex: true,
            rgba: true,
            hsva: true,
            input: true,
            save: true
          }
        },
        strings: {
          save: 'Сохранить',
          clear: 'Очистить',
          cancel: 'Отменить'
       }
      }).on('save', function (color, instance){
        //При сохранение цвета записываем его в инпут
        $('[name="'+name+'"]').val(color.toHEXA().toString());
        instance.hide();
      }).on('init', function (instance){
        //Запоминаем пикер
        admin.PICKR_STORAGE.push(instance);
        //Открываем его, если флажок стоит
        if (show_now) {
          instance.show();
        }
      }).setColorRepresentation(val != ''? val: '#000000') //Ставим дефолтное знаечение

      return tmpPickr;
    },

	};
})();

admin.init();