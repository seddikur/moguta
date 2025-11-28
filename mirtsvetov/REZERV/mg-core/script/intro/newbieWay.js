/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*
 * Объект с массивам подсказок
 * globalHites - подсказки в меню админки
 * Состав массива: 
 *  description - описание атрибуда по которому ищем элемент
 *  attrType - тип атрибута (класс, id и т.д.)
 *  message - сообщение внутри подсказки 
 *  position - позиционирование подсказки по отоношению к элементу
 * Подсказки в массиве должны идти в той последоваетльности, в какой мы хотим их отобразить
 *   */

var newbieWay = {
    first_load: true, //Флаг, чтобы при первой загрузке аяксам цеплял массив показанных ранее подсказок
    INTRO_FLAG_ARR: [], //Сам массив подсказок, чтобы лишний раз не запрашивать сервер
	checkIntroFlags(hitesType, updateOption){
      if(newbieWay.INTRO_FLAG_ARR.length != 0 && newbieWay.INTRO_FLAG_ARR.indexOf(hitesType) != -1){
        if(hitesType != 'main'){
          $('.section-hits__icon.pulse').removeClass('pulse');
        }
      }else if(updateOption == true || hitesType == 'first_check' || newbieWay.first_load == true) {
        newbieWay.first_load = false;
        admin.ajaxRequest({
            mguniqueurl: "action/checkIntroFlags",
            updateOption: updateOption,
            hitesType: hitesType
          },
          function (response){
            newbieWay.INTRO_FLAG_ARR = response['data'];
            if (response['status'] == 'success') {
              if (updateOption == true) {
                if(hitesType != 'main'){
                  $('.section-hits__icon.pulse').removeClass('pulse');
                }
              }
            } else {
              if (updateOption == false) {
                if(hitesType != 'main'){
                  $('.section-hits__icon.pulse').removeClass('pulse');
                }
              }
            }
          });
      }
    },
      
    showHits:function(hitesType){
		newbieWay.checkIntroFlags(hitesType, true);
        var locale = newbieWay.getCookie("mg_to_script_lang");
        if(!locale){
           locale = 'ru_RU';
        }
        if(hitesType == 'main'){
          includeJS(mgBaseDir + '/mg-core/script/intro/scenario/'+locale+'/mainScenario.js');  
          var hits = allHits.globalHites;  
        } else {
          //Подключаем нужный файл со сценарием и берем из него объект с массивом
          includeJS(mgBaseDir + '/mg-core/script/intro/scenario/'+locale+'/'+hitesType+'.js');  
          hits = eval(hitesType+'.hits');
        }
        //Удаляем старые дата-атрибуты
        newbieWay.deleteOldHits();
        var countOfHits = hits.length;
        var j=0;
        //Устанавливаем новые дата-атрибуты
        for(var i=0; i<countOfHits; i++){   
            var elem = $(hits[i]['selector']);
            if(($(elem).length) && ($(elem).is(':visible'))){
                $(elem).attr('data-step', j+1);
                $(elem).attr('data-intro', hits[i]['message']);
				        $(elem).addClass('not-clickable');
                if(hits[i]['position']){
                    $(elem).attr('data-position', hits[i]['position']);
                }
                ++j;
            }
        }
    },
    
    deleteOldHits:function(){
        var elem = $('[data-step]');
        $(elem).removeAttr('data-step');
        elem = $('[data-intro]');
        $(elem).removeAttr('data-intro');
        elem = $('[data-position]');
        $(elem).removeAttr('data-position');
    },
    
    getCookie:function(name) {
        let matches = document.cookie.match(new RegExp(
          "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
      }
    
    
}





