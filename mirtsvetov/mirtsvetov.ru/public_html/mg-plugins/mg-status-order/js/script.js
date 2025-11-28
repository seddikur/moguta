/* 
 * Модуль  statusOrder, подключается на странице настроек плагина.
 */

var statusOrder = (function () {

  includeJS(admin.SITE + '/mg-core/script/colorPicker/js/colorpicker.js');
  includeJS(admin.SITE + '/mg-core/script/colorPicker/js/eye.js');
  includeJS(admin.SITE + '/mg-core/script/colorPicker/js/utils.js');

  return {
    lang:[],
    init: function(){

      // установка локали плагина 
      admin.ajaxRequest({
        mguniqueurl: "action/seLocalesToPlug",
        pluginName: 'mg-status-order'
      },
      function (response) {
        statusOrder.lang = response.data;
      });

      // Добавление нового статуса 
      $('.admin-center').on('click', '.section-mg-status-order .addNewStatus', function () {
        $('.status-tbody').find('.no-results').remove();
        $('.status-table tbody tr:first').before(statusOrder.addNewRow());
        admin.initToolTip();
        
        statusOrder.initColorPicker($('.status-table tbody tr:first .bgColor .colorSelector'));
        statusOrder.initColorPicker($('.status-table tbody tr:first .textColor .colorSelector'));
      });

       // если что-то изменено, то появляется сообщение об изменении, чтобы не забыть сохранить 
      $('.admin-center').on('keyup change', '.section-mg-status-order table td input', function () {
        $(this).parents('tr').find('.actions .change.error').css('display', 'block');
        $(this).parents('tr').find('.actions .change.success').hide();
        $(this).parents('tr').find('.preview').find('span').text($(this).val()).val($(this).val());
      });

      //сброс цветов
      $('.admin-center').on('click', '.section-mg-status-order table .reset-row', function () {
        $(this).parents('tr').find('.preview').find('span').css('backgroundColor', '').css('color', '').attr('bgColor', '').attr('textColor', '');
        $(this).parents('tr').find('.bgColor').find('.colorSelector').find('div').css('backgroundColor', $(this).parents('tr').find('.preview').find('span').css('backgroundColor'));
        $(this).parents('tr').find('.textColor').find('.colorSelector').find('div').css('backgroundColor', $(this).parents('tr').find('.preview').find('span').css('color'));
        $(this).parents('tr').find('.actions .change.error').css('display', 'block');
        $(this).parents('tr').find('.actions .change.success').hide();
      });

       // Сохранение изменений и добавление новых записей
      $('.admin-center').on('click', '.section-mg-status-order table .save-row', function () {
        $(this).parents('tr').find('input').removeClass('error-input');
        var tr = $(this).parents('tr');
        var id = $(this).data('id');
        var status = $(this).parents('tr').find('input[name=status]').val();
        var bg = $(this).parents('tr').find('.preview').find('span').attr('bgColor');
        var txt = $(this).parents('tr').find('.preview').find('span').attr('textColor');
        admin.ajaxRequest({
            mguniqueurl: "action/saveEntity",
            pluginHandler: "mg-status-order",
            id: id,
            status: admin.htmlspecialchars(status),
            bgColor: bg,
            textColor: txt
          },
            function (response) {
              admin.indication(response.status, response.msg);
              if (response.status == 'success') {                
                $(tr).find().attr('data-id', response.data.id);
                $(tr).find('.actions .change.error').hide();
                $(tr).find('input').removeClass('error-input');
                if (response.data.id_status) {
                  $(tr).data('id', response.data.id);
                  $(tr).find('td:first').html('<i class="fa fa-arrows"></i>');
                  $(tr).find('.newId').text(response.data.id_status);
                  $(tr).find('.delete-row, .save-row').attr('data-id', response.data.id);
                }
              }
            }
          )
          
      });
      
      // Удаляет запись из таблицы
      $('.admin-center').on('click', '.section-mg-status-order .delete-row', function () {
        var id = $(this).data('id');
        if (!id) {
          $(this).parents('tr').remove();
          return false;
        }
        statusOrder.deleteEntity(id);
      });
    },
     /**    
     * Отрисовывает  строку сущности в таблице скидочных диапозонов
     * @param {type} data - данные для вывода в строке таблицы
     */
    addNewRow: function () {
      var tr = '\
      <tr data-id><td class="mover"></td><td class="newId"></td> \
      <td > <input type="text" name="status" value=""></td>\
      <td class="bgColor">\
        <div class="colorSelector">\
          <div></div>\
        </div>\
      </td>\
      <td class="textColor">\
        <div class="colorSelector">\
          <div></div>\
        </div>\
      </td>\
      <td class="preview">\
      <span class="badge"></span>\
      </td>\
        <td class="actions">\
         <ul class="action-list">\
           <li class="save-row tool-tip-bottom" title="' + statusOrder.lang['SAVE_MODAL'] + '"><a role="button" href="javascript:void(0);"><i class="fa fa-floppy-o"></i></a></li>\
           <li class="delete-row"><a class="  fa fa-trash tool-tip-bottom" href="javascript:void(0);"  title="' + statusOrder.lang['DELETE'] + '"></a></li>\
           </ul>\
           <span class="change error">' + statusOrder.lang['DONT_SAVED'] + '</span>\
           <span class="change success">' + statusOrder.lang['SAVED'] + '</span>\
        </td>\
     </tr>';
      return tr;
    },
    initColorPicker: function (self) {
      self = self.find('div');
      var parentClass = self.parent().parent().prop('className');
      if (parentClass == 'bgColor'){
        self.css('backgroundColor', self.parents('tr').find('.preview').find('span').css('backgroundColor'));
      }
      if (parentClass == 'textColor'){
        self.css('backgroundColor', self.parents('tr').find('.preview').find('span').css('color'));
      }
      
      var selfColor = statusOrder.rgb2hex(self.css('backgroundColor'));

      selfColor = selfColor.replace('#', '');
      self.ColorPicker({
        color: selfColor,
        onShow: function (colpkr) {
          $(colpkr).fadeIn(50);
          return false;
        },
        onHide: function (colpkr) {
          $(colpkr).fadeOut(50);
          return false;
        },
        onChange: function (hsb, hex, rgb) {
          self.css('backgroundColor', '#' + hex);
          var parentClass = self.parent().parent().prop('className');
          if (parentClass == 'bgColor'){
            self.parents('tr').find('.preview').find('span').css('backgroundColor', '#' + hex).attr('bgColor', '#' + hex);
          }
          if (parentClass == 'textColor'){
            self.parents('tr').find('.preview').find('span').css('color', '#' + hex).attr('textColor', '#' + hex);
          }
          self.parents('tr').find('.actions .change.error').css('display', 'block');
          self.parents('tr').find('.actions .change.success').hide();
        }
      });
    },


    //Function to convert hex format to a rgb color
    rgb2hex: function (orig){
     var rgb = orig.replace(/\s/g,'').match(/^rgba?\((\d+),(\d+),(\d+)/i);
     return (rgb && rgb.length === 4) ? "#" +
      ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
      ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
      ("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : orig;
    },


    /**    
     * Удаляет  строку сущности в таблице 
     * @param {type} data - данные для вывода в строке таблицы
     */
    deleteEntity: function (id, name) {
      if (id) {
        if (!confirm(lang.DELETE + '?')) {
          return false;
        }
        admin.ajaxRequest({
          mguniqueurl: "action/deleteEntity", // действия для выполнения на сервере
          pluginHandler: 'mg-status-order', // плагин для обработки запроса
          id: id,
        },
        function (response) {
          admin.indication(response.status, response.msg);
          admin.refreshPanel();
          
        }
        );
      }
      
      ;
    },
  }
})();
$(document).ready(function() {
  statusOrder.init();
});

