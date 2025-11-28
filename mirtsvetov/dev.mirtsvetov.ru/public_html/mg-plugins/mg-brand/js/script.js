 /* 
 * Модуль  BrandModule, подключается на странице настроек плагина.
 */

 var BrandModule = (function() {
  
  return { 
    lang: [], // локаль плагина 
    pluginName: "mg-brand",
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: BrandModule.pluginName
        },
        function(response) {
          BrandModule.lang = response.data;        
        }
      );  

      //Показать попап с адаптивностю
      $('.admin-center').on('click' , '.section-'+BrandModule.pluginName+' .list-option .openPopup', function() {
        $(this).parent().find('.field-adaptive-popup').toggle();
      }); 
      
      //Добавить поле в попап
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .field-adaptive-popup .add-field', function() {
        BrandModule.addAdaptItem()
      })

      //Удаление поля в попап
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .field-adaptive-popup .delete-item', function() {
        $(this).parent().remove()
      })

      //Закрыть попап
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .field-adaptive-popup .close-popup', function() {
        $('.field-adaptive-popup').hide();
      })

      //Применение попапа
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .field-adaptive-popup .popup-accept', function() {
        BrandModule.saveResponsive()
        $('.field-adaptive-popup').hide();
      })
        
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .add-entity', function() {        
        BrandModule.clearField()
        BrandModule.hideShortUrlAndLang()
        admin.openModal($('#plug-modal'))  
      });
      
      // Выводит модальное окно для редактирования
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .edit-row', function() {       
        var id = $(this).data('id');
        BrandModule.showShortUrlAndLang()
        BrandModule.clearField()
        BrandModule.fillField(id);            
      });
      
       // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' #plug-modal .save-button', function() { 
        var id = $('#plug-modal input[name="id"]').val();    
        BrandModule.saveField(id);        
      });
      
      // Сброс фильтров.
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .refreshFilter', function(){
        admin.show(BrandModule.pluginName,"plugin","refreshFilter=1",BrandModule.callbackBackRing);
        return false;
      });
      
       // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-'+BrandModule.pluginName+' .countPrintRowsEntity', function(){
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsEnity",
          pluginHandler: BrandModule.pluginName,
          option: 'countPrintRows',
          count: count
        },
        function(response) {  
          admin.indication(response.status, response.msg);       
          if (response.status == "success") {
            admin.refreshPanel()
          }
        }
        );

      });

      //Переход на страницу СЕО
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .show-seo', function() {
        if (confirm('Текущее окно редактора будет закрыто! Изменения не будут сохранены! Вы уверены?'))
        admin.show('settings.php', 'adminpage')
      })

      //Сгенерировать СЕО
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .generate_seo', function() {
        $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_title"]').val($('.admin-center .section-'+BrandModule.pluginName+' input[name="brand"]').val())
        $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_keywords"]').val($('.admin-center .section-'+BrandModule.pluginName+' input[name="brand"]').val())

        $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="seo_desc"]').val(CKEDITOR.instances['html-desc'].getData().replace(/<\/?[^>]+>/gi, ''))
        CKEDITOR.instances['html-desc-seo'].setData(CKEDITOR.instances['html-desc'].getData())
        
      })

      
      $('.admin-center').on('click', '.reveal-body .html-content-edit', function (){
        $('textarea[id=html-desc]').ckeditor(function (){});
      });
      $('.admin-center').on('click', '.reveal-body .html-content-edit', function (){
        $('textarea[id=html-desc-seo]').ckeditor(function (){});
      });

      // автоподстчет количества символов
      $('.admin-center').on('blur keyup', '.section-'+BrandModule.pluginName+' textarea[name="seo_desc"]', function () {
        $('.symbol-count').text($(this).val().length);
      });

      // SEO для картинки
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .set-seo', function() {
        $('.admin-center .section-'+BrandModule.pluginName+' #seoImgBrand').toggle()
      })

      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .apply-seo-image', function() {
        $('.admin-center .section-'+BrandModule.pluginName+' #seoImgBrand').toggle()
      })

      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .seo-image-block-close', function() {
        $('.admin-center .section-'+BrandModule.pluginName+' #seoImgBrand').toggle()
        $('.admin-center .section-'+BrandModule.pluginName+' [name="image_title"]').val('')
        $('.admin-center .section-'+BrandModule.pluginName+' [name="image_alt"]').val('')
      })

      //Смена языка
      $('.admin-center').on('change', '.section-'+ BrandModule.pluginName + ' .js-select-lang .select-lang', function(){
        var id = $('#plug-modal input[name="id"]').val();
        admin.ajaxRequest({
          mguniqueurl: "action/getTranslate",
          pluginHandler: BrandModule.pluginName,
          locale: $('.js-select-lang .select-lang').val(),
          id: id 
        }, function(response){
          var entity = response.data
          $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_title"]').val(entity.seo_title)
          $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_keywords"]').val(entity.seo_keywords)
          $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="seo_desc"]').val(entity.seo_desc)
          $('.admin-center .section-'+BrandModule.pluginName+' [name="image_title"]').val(entity.img_title)
          $('.admin-center .section-'+BrandModule.pluginName+' [name="image_alt"]').val(entity.img_alt)
          
          CKEDITOR.instances['html-desc'].setData(entity.desc)
          CKEDITOR.instances['html-desc-seo'].setData(entity.cat_desc_seo)
        })
      })

      
     // Нажатие на кнопку - активности
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .visible', function(){    
        $(this).toggleClass('active');  
        $(this).find("a").toggleClass('active'); 
        var id = $(this).data('id');

        if($(this).hasClass('active')) { 
          BrandModule.visibleEntity(id, 1)
          $(this).attr('tooltip', lang.ACT_V_ENTITY);
        }
        else {
          BrandModule.visibleEntity(id, 0)
          $(this).attr('tooltip', lang.ACT_UNV_ENTITY);
        }
      });
      
      // Удаляет запись
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .delete-row', function() {
        var id = $(this).data('id');
        admin.ajaxRequest({
          mguniqueurl: "action/deleteEntity",
          pluginHandler: BrandModule.pluginName,
          id: id
        }, function(response) {
          admin.indication(response.status, response.msg);
          if (response.status == "success") {
            admin.refreshPanel()
          }
        })
      });

      // Обновляет запись
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .refresh-row', function() {
        var id = $(this).data('id');
        admin.ajaxRequest({
          mguniqueurl: "action/refreshEntity",
          pluginHandler: BrandModule.pluginName,
          id: id
        }, function(response) {
          admin.indication(response.status, response.msg);
          if (response.status == "success") {
            admin.refreshPanel()
          }
        })
      });

      // Удалить свойство
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .delete-no-brand', function() {
        var id = $(this).data('id');
        admin.ajaxRequest({
          mguniqueurl: "action/deleteProp",
          pluginHandler: BrandModule.pluginName,
          id: id
        }, function(response) {
          admin.indication(response.status, response.msg);
          if (response.status == "success") {
            admin.refreshPanel()
          }
        })
      });

      // Добавить запись на основе свойства
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .add-no-brand', function() {
        var id = $(this).data('id');
        admin.ajaxRequest({
          mguniqueurl: "action/addBrand",
          pluginHandler: BrandModule.pluginName,
          id: id
        }, function(response) {
          admin.indication(response.status, response.msg);
          if (response.status == "success") {
            admin.refreshPanel()
          }
        })
      });
      
       // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .base-setting-save', function() {
       
        var data =  {
          propertyId: $('.section-'+BrandModule.pluginName+' .base-setting .list-option select[name="property_id"]').val(),
          slider_options: {
            items: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="sl_items"]').val(),
            nav: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="sl_nav"]').prop("checked"),
            mouseDrag: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="sl_mousedrag"]').prop("checked"),
            autoplay: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="sl_autoplay"]').prop("checked"),
            loop: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="sl_loop"]').prop("checked"),
            responsive: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="responsive"]').val(),
            head: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="sl_head"]').val(),
            margin: $('.section-'+BrandModule.pluginName+' .base-setting .list-option input[name="sl_margin"]').val(),
          }
        }

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: BrandModule.pluginName, // плагин для обработки запроса
          data: data 
        },

        function(response) {
          admin.indication(response.status, response.msg);     
          if (response.status == "success") {
            admin.refreshPanel()
          }
        }

        );
        
      });  
      
      //Экспорт из старого плагина
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .export-old', function() {
        if (confirm(BrandModule.lang['EXPORT_CONFIRM'])) {
          admin.ajaxRequest({
            mguniqueurl: "action/export",
            pluginHandler: BrandModule.pluginName
          }, function(response) {
            admin.indication(response.status, response.msg);
            if (response.status == "success") {
              admin.refreshPanel()
            }
          })
        }
      });
      
      // Применение выбраных фильтров
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .filter-now', function() {
        BrandModule.getProductByFilter();
        return false;
      });
      
      // Выбор картинки
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .browseImage', function() {
        admin.openUploader('BrandModule.getFile');
      });
      
      // Удаление картинки
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .del-image-to-brand', function() {
        BrandModule.clearPrevImg()
      })
      
      // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .show-property-order', function() {
        $('.property-order-container').slideToggle(function() {
          $('.filter-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
            
      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+'  .show-filters', function() {
        $('.filter-container').slideToggle(function() {
          $('.property-order-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });

      // Выбор действия над отмеченными строками
      $('.admin-center').on('change', '.section-'+BrandModule.pluginName+' select[name="action"]', function() {
        switch ($(this).val()) {
          case 'delete':
            BrandModule.showActionDelete()
            break;
          case 'refresh':
            BrandModule.showActionRefresh()
            break;
          case 'add':
            BrandModule.showActionAdd()
            break;
          default:
            break;
        }
      })

      //Действие над отмеченными строками
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .j-run-action', function() {
        switch ($('select[name="action"]').val()) {
          case 'delete':
            BrandModule.actionDelete()
            break;
          case 'refresh':
            BrandModule.actionRefresh()
            break;
          case `add`:
            BrandModule.actionAdd()
            break;
          default:
            break;
        }
      })

      //Выделить все чекбоксы
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' #all-rows', function() {
        $('.select-row').prop('checked', $(this).prop('checked'))
        if ($(this).prop('checked')) {
          $('tr').addClass('selected')
        } else {
          $('tr').removeClass('selected')
        }
      })

      //================[Для no_found.php]===========

      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .generate-new', function() {
        admin.ajaxRequest({
          mguniqueurl: "action/generateNew",
          pluginHandler: BrandModule.pluginName
        },
        function(response){ 
          admin.indication(response.status, response.msg);
            if (response.status == "success") {
              admin.refreshPanel()
            }
        })
      })

      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .select-property', function() {
        admin.ajaxRequest({
          mguniqueurl: "action/selectPropertyId",
          pluginHandler: BrandModule.pluginName,
          data: {
            propertyId: $('.section-'+BrandModule.pluginName+' #property-id').val()
          }
        },
        function(response){
        admin.indication(response.status, response.msg);
          if (response.status == "success") {
          admin.refreshPanel()
        }
        })
      })
      
      //=============[Для дебага]===========
      $('.admin-center').on('click', '.section-'+BrandModule.pluginName+' .take-snapshot', function() {
        admin.ajaxRequest({
          mguniqueurl: "action/takeSnapshot",
          pluginHandler: BrandModule.pluginName,
          comment: $('input[name="debug_comment"]').val()
        },
        function(response){
          $('input[name="debug_comment"]').val('')
        })
      })
    },

    showActionDelete: function() {
      $('.admin-center .section-'+BrandModule.pluginName+' .j-for-action').html('')
    },

    showActionRefresh: function() {
      $('.admin-center .section-'+BrandModule.pluginName+' .j-for-action').html('')
    },

    showActionAdd: function() {
      $('.admin-center .section-'+BrandModule.pluginName+' .j-for-action').html('')
    },

    actionDelete: function() {
      admin.ajaxRequest({
        mguniqueurl: "action/actionDelete",
        pluginHandler: BrandModule.pluginName,
        list: BrandModule.getCheckedRows()
      },
      function(response){ 
        var request = $("form[name=filter]").formSerialize();
        admin.show(BrandModule.pluginName, "plugin", request);
      })
    },

    actionRefresh: function() {
      admin.ajaxRequest({
        mguniqueurl: "action/actionRefresh",
        pluginHandler: BrandModule.pluginName,
        list: BrandModule.getCheckedRows()
      },
      function(response){ 
        var request = $("form[name=filter]").formSerialize();
        admin.show(BrandModule.pluginName, "plugin", request);
      })
    },

    actionAdd: function() {
      admin.ajaxRequest({
        mguniqueurl: "action/actionAdd",
        pluginHandler: BrandModule.pluginName,
        list: BrandModule.getCheckedRows()
      },
      function(response){ 
        admin.indication(response.status, response.msg);
          if (response.status == "success") {
            var request = $("form[name=filter]").formSerialize();
            admin.show(BrandModule.pluginName, "plugin", request);
        }
      })
    },

    getCheckedRows: function() {
      var checkboxs = $('.admin-center .section-'+BrandModule.pluginName+' .select-row:checked')
      var result = []
      $.each(checkboxs, function(key, value) {
        result.push(value.getAttribute('data-id'))
      })
      return result
    },

   /**
    * функция для приема файла из аплоадера
    */         
    getFile: function(file) {
      BrandModule.setPrevImg(file.path)
    }, 
    
    saveField: function(id) {
      
      var locale = $('.admin-center .section-'+BrandModule.pluginName+' .js-select-lang .select-lang').val();
      if (locale == "default" || locale === undefined) {
        data = {
          id: id,
          data_id: $('.admin-center .section-'+BrandModule.pluginName+' select[name="data_id"]').val(),
          brand: $('.admin-center .section-'+BrandModule.pluginName+' input[name="brand"]').val(),
          desc: $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc"]').val(),
          //desc: CKEDITOR.instances['html-desc'].getData(),
          url: $('.admin-center .section-'+BrandModule.pluginName+' input[name="url"]').val(),
          seo_title: $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_title"]').val(),
          seo_keywords: $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_keywords"]').val(),
          seo_desc: $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="seo_desc"]').val(),
          img_alt: $('.admin-center .section-'+BrandModule.pluginName+' [name="image_alt"]').val(),
          img_title: $('.admin-center .section-'+BrandModule.pluginName+' [name="image_title"]').val(),
          short_url: $('.admin-center .section-'+BrandModule.pluginName+' input[name="short_url"]').val(),
          //cat_desc_seo: CKEDITOR.instances['html-desc-seo'].getData(),
          cat_desc_seo: $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc_seo"]').val(),
        }
        admin.ajaxRequest({
          mguniqueurl: "action/saveEntity",
          pluginHandler: BrandModule.pluginName,
          data: data
        }, function(response){
          admin.indication(response.status, response.msg);
          if (response.status == "success") {
              admin.closeModal($('#plug-modal'));
              admin.refreshPanel();
          }
        })
      } else {
        data = {
          desc: $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc"]').val(),
          //desc: CKEDITOR.instances['html-desc'].getData(),
          seo_title: $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_title"]').val(),
          seo_keywords: $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_keywords"]').val(),
          seo_desc: $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="seo_desc"]').val(),
          img_alt: $('.admin-center .section-'+BrandModule.pluginName+' [name="image_alt"]').val(),
          img_title: $('.admin-center .section-'+BrandModule.pluginName+' [name="image_title"]').val(),
          //cat_desc_seo: CKEDITOR.instances['html-desc-seo'].getData(),
          cat_desc_seo: $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc_seo"]').val(),
        }
        admin.ajaxRequest({
          mguniqueurl: "action/saveTranslate",
          pluginHandler: BrandModule.pluginName,
          id: id,
          data: data,
          locale: locale
        }, function(response){
          admin.indication(response.status, response.msg);
          if (response.status == "success") {
            admin.refreshPanel()
          }
        })
      }
    },

    fillField: function(id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getEntity",
        pluginHandler: BrandModule.pluginName,
        id: id
      }, function(response){
        var entity = response.data
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal input[name="brand"]').val(entity.brand)
        if (entity.url) BrandModule.setPrevImg(entity.url)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal input[name="id"]').val(id)
        $('.admin-center .section-'+BrandModule.pluginName+' #data_id').show()
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal select[name="data_id"]').val(entity.data_id)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal textarea[name="html_desc"]').val(entity.desc)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal input[name="seo_title"]').val(entity.seo_title)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal input[name="seo_keywords"]').val(entity.seo_keywords)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal textarea[name="seo_desc"]').val(entity.seo_desc)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal textarea[name="html_desc_seo"]').val(entity.cat_desc_seo)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal input[name="short_url"]').val(entity.short_url)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal [name="image_title"]').val(entity.img_title)
        $('.admin-center .section-'+BrandModule.pluginName+' #plug-modal [name="image_alt"]').val(entity.img_alt)
        
        /*CKEDITOR.instances['html-desc'].setData(entity.desc)
        CKEDITOR.instances['html-desc-seo'].setData(entity.cat_desc_seo)*/
        
        admin.openModal($('#plug-modal'))
      })
    },
    clearField: function() {
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="brand"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' #data_id').hide()
      $('.admin-center .section-'+BrandModule.pluginName+' .product-text-inputs input[name="date"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="url"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="id"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_title"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="seo_keywords"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="seo_desc"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc_seo"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="short_url"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' [name="image_title"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' [name="image_alt"]').val('')
      $('.admin-center .section-'+BrandModule.pluginName+' .js-select-lang .select-lang').val('default')

      BrandModule.clearPrevImg()
      
      /*if (!CKEDITOR.instances['html-desc']) {
        
        //$('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc"]').val('')
        CKEDITOR.replace('html_desc')
        CKEDITOR.instances['html-desc'].setData('')
      } else {
        CKEDITOR.instances['html-desc'].setData('')
      }

      if (!CKEDITOR.instances['html-desc-seo']) {
        
        //$('.admin-center .section-'+BrandModule.pluginName+' textarea[name="html_desc_seo"]').val('')
        CKEDITOR.replace('html_desc_seo')
        CKEDITOR.instances['html-desc-seo'].setData('')
      } else {
        CKEDITOR.instances['html-desc-seo'].setData('')
      }

      $('.admin-center .section-'+BrandModule.pluginName+' textarea[name="seo_desc"]').val('')*/
    },

    visibleEntity: function(id, visible) {
      admin.ajaxRequest({
        mguniqueurl: 'action/visibleEntity',
        pluginHandler: BrandModule.pluginName,
        id: id,
        invisible: visible
      })
    },
             
    getProductByFilter: function() {
      var request = $("form[name=filter]").formSerialize(); 
      admin.show(BrandModule.pluginName, "plugin", request + '&applyFilter=1',BrandModule.callbackBlanck);
      return false;
    },

    //Отобразить превью изображения в модалке редактирования
    setPrevImg: function(src) {
      if (src.indexOf('http') == -1) {
        $('.brand-image').attr('src', admin.SITE + '/' + src);
      } else {
        $('.brand-image').attr('src', src);
      }
      $('.brand-img').css('display', 'inline-block')
      $('.browseImage').hide()
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="url"]').val(src);
    },

    //Скрыть превью изображения в модалке редактирования
    clearPrevImg: function() {
      $('.brand-image').attr('src', admin.SITE + "/mg-admin/design/images/100x100.png")
      $('.brand-img').css('display', 'none')
      $('.browseImage').show()
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="url"]').val('');
    }, 

    addAdaptItem: function() {
      a_width = $('<input type="text" name="a_width" placeholder="Ширина">')
      a_items = $('<input type="text" name="a_items" placeholder="Кол. карточек">')
      html = $('<div class="adaptive-item">').append(a_width)
                                             .append(" ")
                                             .append(a_items)
                                             .append(" ")
                                             .append('<a role="button" href="javascript:void(0);" class="button secondary delete-item"><i class="fa fa-trash"></i></a>')


      $('.admin-center .section-' + BrandModule.pluginName +' .list-option .list-adaptive').append(html)
    },

    saveResponsive: function() {
      var responsive = [];
      items = $('.list-adaptive .adaptive-item')
      items.each(function() {
        width = $(this).children('input[name="a_width"]').val()
        items = $(this).children('input[name="a_items"]').val()
        responsive.push(width+":"+items)
      })
      result = responsive.join(',')
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="responsive"]').val(result)
    },
            
    /**
     *Пакет выполняемых действий после загрузки раздела товаров
     */
    callbackBlank:function() { 
    admin.AJAXCALLBACK = [      
      {callback:'admin.sortable', param:['.entity-table-tbody',BrandModule.pluginName]},       
    ]; 
    },

    hideShortUrlAndLang: function() {
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="short_url"]').parents('li').hide()
      $('.admin-center .section-'+BrandModule.pluginName+' .js-select-lang').hide()
    },

    showShortUrlAndLang: function() {
      $('.admin-center .section-'+BrandModule.pluginName+' input[name="short_url"]').parents('li').show()
      $('.admin-center .section-'+BrandModule.pluginName+' .js-select-lang').show()
    }
    
  }
})();

BrandModule.init();
admin.sortable('.entity-table-tbody', BrandModule.pluginName);