/**
 * Модуль для  раздела "работы с загружаемыми файлами".
 */
var uploader = (function () {
  return { 


    CALLBACK: null, // отложенная функция которая будет вызвана после выбора файла из выплывающего окна менеджера
    PARAM1: null, // параметр для передачи в отложенную функцию
    universal: false, // открывать фм без колбэка (будет срабатывать стандартная функция открытыя файлов во всплывающем окне)
    
    /**
     * Инициализирует экземпляр файлового менеджера
     */
    init: function() {
      var elf = $('#elfinder').elfinder({
        url : mgBaseDir+'/ajax?mguniqueurl=action/elfinder&dir=uploads',
        useBrowserHistory: false,
        lang: 'ru',
        getFileCallback : function(file) { // после выбора файла передаем его в отложенную функцию                
           eval(uploader.CALLBACK).call(null,file);
           admin.closeModal($('#modal-elfinder'));     //закрываем окно        
           $('.cke_dialog_background_cover').css('z-index', '96');  
        },      
        closeOnEditorCallback: function() { 
           admin.closeModal( $('#modal-elfinder'));     //закрываем окно        
           $('.cke_dialog_background_cover').css('z-index', '96');  
        },        
        resizable: false,
        defaultView: 'list',
      }).elfinder('instance');
      $('#elfinderTemplate').elfinder({
        url : mgBaseDir+'/ajax?mguniqueurl=action/elfinder&dir=template',
        useBrowserHistory: false,
        lang: 'ru',              
        closeOnEditorCallback: function() { 
           admin.closeModal($('#modal-elfinder'));     //закрываем окно        
           $('.cke_dialog_background_cover').css('z-index', '96');  
        },        
        resizable: false,
        defaultView: 'list',
      });
      $('#elfinderTemp').elfinder({
        url : mgBaseDir+'/ajax?mguniqueurl=action/elfinder&dir=temp',
        useBrowserHistory: false,
        lang: 'ru',              
        closeOnEditorCallback: function() { 
           admin.closeModal($('#modal-elfinder'));     //закрываем окно        
           $('.cke_dialog_background_cover').css('z-index', '96');  
        },        
        resizable: false,
        defaultView: 'list',
      });
      $('#elfinderUniversal').elfinder({
        url : mgBaseDir+'/ajax?mguniqueurl=action/elfinder&dir=uploads',
        useBrowserHistory: false,
        lang: 'ru',              
        closeOnEditorCallback: function() { 
           admin.closeModal($('#modal-elfinder'));     //закрываем окно        
           $('.cke_dialog_background_cover').css('z-index', '96');  
        },        
        resizable: false,
        defaultView: 'list',
      });
    },
    
   
       
    /*
     * этот метод отрабатывает при вызове файлового менеджера из CKEditor
     */
    getFileCallbackCKEDITOR: function(file) {        
      CKEDITOR.tools.callFunction(uploader.PARAM1, file.url);     
    },   
    
    /**
     * открывает окно менеджера файлов, сохраняет  параметры для вызова отложенной функции 
     * @param {type} callback
     * @param {type} param1
     * @returns {undefined}
     */        
    open: function(callback,param1,universal = false) {
      uploader.PARAM1 = param1;
      uploader.CALLBACK = callback;
      uploader.universal = universal;

      if($('#modal-elfinder').length==0){    
        var uploaderHtml =  '\
          <link href="'+mgBaseDir+'/mg-admin/design/css/jquery-ui.css" rel="stylesheet" type="text/css">\
          <link rel="stylesheet" type="text/css" media="screen" href="'+mgBaseDir+'/mg-core/script/elfinder/css/elfinder.min.css">\
          <link rel="stylesheet" type="text/css" media="screen" href="'+mgBaseDir+'/mg-core/script/elfinder/css/theme.css">\
          <link rel="stylesheet" type="text/css" media="screen" href="'+mgBaseDir+'/mg-core/script/elfinder/css/fixElfinderStyle.css">\
          <div class="reveal-overlay" style="display:none;">\
            <div class="reveal xssmall uploader-modal" id="modal-elfinder" style="height:440px; display:block;">\
              <div class="product-table-wrapper">\
                <div class="widget-table-title" style="padding: 10px 0 0 10px;">\
                  <h4 class="category-table-icon" id="modalTitle">'+lang.FILE_MANAGER+'</h4>\
                  <button class="close-button uploader-modal_close" data-close="" type="button" style="top:6px;right:10px;"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>\
                </div>\
                <div id="elfinder"></div>\
                <div id="elfinderTemplate"></div>\
                <div id="elfinderTemp"></div>\
                <div id="elfinderUniversal"></div>"\
              </div>\
            </div>\
          </div>';
        if(!admin.PULIC_MODE || cookie("publicAdmin") === "true") {
          $('body').append(uploaderHtml);
        } else {
          if(callback == 'uploader.getFileCallbackCKEDITOR'){
            $('body').append(uploaderHtml);
          } else {
            $('.mg-admin-html').append(uploaderHtml);
          }
        }
        
        uploader.init();

        $('#modal-elfinder').parent().css('z-index',11000);

        // удаление лишних кнопок интерфейса загрузчика
        $('.elfinder-button-icon-resize').parent().detach();
        $('.elfinder-button-icon-pixlr').parent().detach();
        $('.elfinder-button-icon-netmount').parent().parent().detach();
        $('.elfinder-button-icon-help').parent().parent().detach();
        $('.elfinder-button-icon-getfile').parent().parent().detach();

        $('.elfinder-buttonset:eq(3) .elfinder-toolbar-button-separator:eq(2)').detach();

        $('.elfinder-buttonset:eq(6) .elfinder-toolbar-button-separator:eq(2)').detach();
        $('.elfinder-buttonset:eq(6) .elfinder-toolbar-button-separator:eq(2)').detach();
        // конец удаления кнопок

        $( "#modal-elfinder").draggable({ handle: ".widget-table-title" });
        $('body').on('click', '.uploader-modal_close', function() {  
          //console.log('uploader.js');
          $('.cke_dialog_background_cover').css('z-index', '96');  
        });
      }
      if(admin.DIR_FILEMANAGER=='template'){
        $('#elfinderTemplate').show();
        $('#elfinder').hide();
        $('#elfinderTemp').hide();
        $('#elfinderUniversal').hide();
      }
      if(admin.DIR_FILEMANAGER=='uploads'){
        $('#elfinderTemplate').hide();
        $('#elfinder').show();
        $('#elfinderTemp').hide();
        $('#elfinderUniversal').hide();
      }
      if(admin.DIR_FILEMANAGER=='temp'){
        $('#elfinderTemplate').hide();
        $('#elfinder').hide();
        $('#elfinderTemp').show();
        $('#elfinderUniversal').hide();
      }
      if (universal) {
        $('#elfinderTemplate').hide();
        $('#elfinder').hide();
        $('#elfinderTemp').hide();
        $('#elfinderUniversal').show();
      }
      admin.openModal($('#modal-elfinder'));
      // $('.cke_dialog ').css('z-index', '100'); 
      // $('.cke_dialog_background_cover').css('z-index', '150');  
      $('#modal-elfinder').css('z-index', '1200');   
    },            
    
            
    }
  
})();

// инициализация модуля при подключении
uploader.init();