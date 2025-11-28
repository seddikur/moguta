/**
 * Модуль для  раздела "Категории".
 */
var category = (function() {
  return {
    wysiwyg: null, // HTML редактор для   редактирования страниц
    supportCkeditor: null, 
    supportCkeditorSeo: null, 
    clickedId: [],
    checkBlockInterval: null,
    init: function() {
      // Инициализация обработчиков
      category.initEvents();
      // восстанавливаем массив открытых категорий из куков
      category.openedCategoryAdmin = eval(cookie("openedCategoryAdmin"));
      if (!category.openedCategoryAdmin) {
        category.openedCategoryAdmin = [];
      }

      // для блокировки категорий при редактировании
      category.checkBlockInterval = setInterval(category.checkBlockIntervalFunction, 1000);

      category.sortableInit();
      category.clickedId = [];
      category.hidePageRows();
	  newbieWay.checkIntroFlags('categoryScenario', false);
    },
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    initEvents: function() {
      // смена языка
      $('.section-category').on('change','.select-lang', function() {
        category.editCategory($('#add-category-modal .save-button').attr('id'));     
      });

      $('.section-category').on('click', '.set-seo', function() {
        $(this).siblings('#seoImgCat').children('.custom-popup').show();
      });

      $('.section-category').on('click', '.imgPrimary .apply-seo-image', function() {
        $('.imgPrimary #seoImgCat .custom-popup').hide();
      });

      /*Инициализирует CKEditior*/
      $('.section-category').on('click', '#add-category-modal .html-content-edit', function() {
        $('#add-category-modal textarea[name=html_content]').ckeditor(function() {});
        CKEDITOR.instances['html_content'].config.filebrowserUploadUrl = admin.SITE + '/ajax?mguniqueurl=action/upload_tmp';
      });

      $('.section-category').on('click', '#add-category-modal .seo-content-edit', function() {
        $('#add-category-modal textarea[name=html_content-seo]').ckeditor(function() {});
        CKEDITOR.instances['html_content-seo'].config.filebrowserUploadUrl = admin.SITE + '/ajax?mguniqueurl=action/upload_tmp';
      }); 

      // Вызов модального окна при нажатии на кнопку добавления категории.      
      $('.section-category').on('click', '.add-new-button', function() {
        category.openModalWindow('add');
      });

      // Вызов модального окна при нажатии на пункт изменения категории.
      $('.section-category').on('click', '.edit-sub-cat', function() {
        category.openModalWindow('edit', $(this).parents('tr').data('id'));
      });

      // Вызов модального окна при нажатии на пункт добавления подкатегории.
      $('.section-category').on('click', '.add-sub-cat', function() {
        category.openModalWindow('addSubCategory', $(this).parents('tr').data('id'));
      });

      // Удаление категории.
      $('.section-category').on('click', '.delete-sub-cat', function() {
        category.deleteCategory($(this).parents('tr').data('id'));
      });

      // Сохранение продукта при нажатии на кнопку сохранить в модальном окне.
      $('.section-category').on('click', '#add-category-modal .save-button', function() {
        category.saveCategory($(this).attr('id'), !admin.keySave);
      });      
      
      // Выбор картинки категории
      $('.section-category').on('click', '#add-category-modal .imgPrimary .add-image-to-category, #add-category-modal .imgPrimary .additional_uploads_container label', function() {
        admin.openUploader('category.getFile');

      });

      // Выбор иконки меню категории
      $('.section-category').on('click', '#add-category-modal .imgSecondary .add-image-to-category, #add-category-modal .imgSecondary .additional_uploads_container label', function() {
        admin.openUploader('category.getFileSecondary');

      });  
      
      // Удаление картинки категории
      $('.section-category').on('click', '#add-category-modal .imgPrimary .del-image-to-category', function() {
        if (!confirm(lang.DELETE_IMAGE+'?')) {return false;}
        category.delImgs.push($('#add-category-modal .imgPrimary input[name=image_url]').val());
        $('#add-category-modal .imgPrimary input[name=image_url]').val('');
        $('#add-category-modal .imgPrimary .category-img-block').hide();
        $('#add-category-modal .imgPrimary .add-image-to-category').show();
        $('#add-category-modal .imgPrimary .del-image-to-category').hide();       
      });  

      //открытие всплывалки для ввода ссылки
      $('.section-category').on('click', '#add-category-modal .imgPrimary .additional_uploads_container .from_url', function() {
        $('#add-category-modal .upload-form .custom-popup').hide();
        $('#add-category-modal .imgPrimary .upload-form .custom-popup').show();
      });

      //закрытие всплывалки для ввода ссылки
      $('.section-category').on('click', '#add-category-modal .upload-form .custom-popup .cancel-url', function() {
        $('#add-category-modal .upload-form .custom-popup').hide();
      });

      //применение всплывалки для ввода ссылки
      $('.section-category').on('click', '#add-category-modal .imgPrimary .upload-form .custom-popup .apply-url', function() {
        var imgUrl = $('#add-category-modal .imgPrimary .upload-form .custom-popup input').val();

        admin.ajaxRequest({
          mguniqueurl:"action/addImageUrl",
          imgUrl: imgUrl,
          isCatalog: 'false'
        },

        function(response) {
          admin.indication(response.status, response.msg);

          if (response.status == 'success') {
            category.delImgs.push($('#add-category-modal .imgPrimary input[name=image_url]').val());
            $('#add-category-modal .imgPrimary  input[name="image_url"]').val(admin.SITE+'/uploads/'+response.data);
            $('#add-category-modal .imgPrimary .category-image').attr('src', admin.SITE+'/uploads/'+response.data);
            $('#add-category-modal .imgPrimary .category-img-block').show();
            $('#add-category-modal .imgPrimary .category-image').show();
            $('#add-category-modal .imgPrimary .add-image-to-category').hide();
            $('#add-category-modal .imgPrimary .del-image-to-category').show(); 
          }
        });

        $('#add-category-modal .imgPrimary .upload-form .custom-popup').hide();
      });
      
      // Удаление картинки категории
      $('.section-category').on('click', '#add-category-modal .imgSecondary .del-image-to-category', function() {
        if (!confirm(lang.DELETE_IMAGE+'?')) {return false;}
        category.delImgs.push($('#add-category-modal .imgSecondary input[name=image_url]').val());
        $('#add-category-modal .imgSecondary input[name=image_url]').val('');
        $('#add-category-modal .imgSecondary .category-img-block').hide();
        $('#add-category-modal .imgSecondary .add-image-to-category').show();
        $('#add-category-modal .imgSecondary .del-image-to-category').hide();       
      });  

      //открытие всплывалки для ввода ссылки
      $('.section-category').on('click', '#add-category-modal .imgSecondary .additional_uploads_container .from_url', function() {
        $('#add-category-modal .upload-form .custom-popup').hide();
        $('#add-category-modal .imgSecondary .upload-form .custom-popup').show();
      });

      //применение всплывалки для ввода ссылки
      $('.section-category').on('click', '#add-category-modal .imgSecondary .upload-form .custom-popup .apply-url', function() {
        var imgUrl = $('#add-category-modal .imgSecondary .upload-form .custom-popup input').val();

        admin.ajaxRequest({
          mguniqueurl:"action/addImageUrl",
          imgUrl: imgUrl,
          isCatalog: 'false'
        },

        function(response) {
          admin.indication(response.status, response.msg);

          if (response.status == 'success') {
            category.delImgs.push($('#add-category-modal .imgSecondary input[name=image_url]').val());
            $('#add-category-modal .imgSecondary  input[name="image_url"]').val(admin.SITE+'/uploads/'+response.data);
            $('#add-category-modal .imgSecondary .category-image').attr('src', admin.SITE+'/uploads/'+response.data);
            $('#add-category-modal .imgSecondary .category-img-block').show();
            $('#add-category-modal .imgSecondary .category-image').show();
            $('#add-category-modal .imgSecondary .add-image-to-category').hide();
            $('#add-category-modal .imgSecondary .del-image-to-category').show(); 
          }
        });

        $('#add-category-modal .imgSecondary .upload-form .custom-popup').hide();
      });

      $('.section-category').on('click', '.imgSecondary .set-seo', function() {
        $('.imgSecondary #seoImgCat').show();
      });

      $('.section-category').on('click', '.imgSecondary .apply-seo-image', function() {
        $('.imgSecondary #seoImgCat .custom-popup').hide();
      });

      // Выделить все категории.
      $('.section-category').on('click', '.check-all-cat', function() {       
        $('.category-tree input[name=category-check]').prop('checked', 'checked');
        $('.category-tree input[name=category-check]').val('true');
        $('.category-tree tr').addClass('selected');

        $(this).addClass('uncheck-all-cat');
        $(this).removeClass('check-all-cat');
      });
            
      // Сортировать все категории по алфавиту
      $('.section-category').on('click', '.sort-all-cat', function() {
        category.sortToAlphabet();
      });
      
      // Снять выделение со всех  категорий.
      $('.section-category').on('click', '.uncheck-all-cat', function() {        
        $('.category-tree input[name=category-check]').prop('checked', false);
        $('.category-tree input[name=category-check]').val('false');
        $('.category-tree tr').removeClass('selected');
        
        $(this).addClass('check-all-cat');
        $(this).removeClass('uncheck-all-cat');
      });
      
      // Выполнение выбранной операции с категориями
      $('.section-category').on('click', '.run-operation', function() {
        if ($('.category-operation').val() == 'fulldelete') {
          admin.openModal('#category-remove-modal');
        } else {
          category.runOperation($('.category-operation').val());
        }
      });

      //Проверка для массового удаления
      $('.section-category').on('click', '#category-remove-modal .confirmDrop', function () {
        if ($('#category-remove-modal input').val() === $('#category-remove-modal input').attr('tpl')) {
          $('#category-remove-modal input').removeClass('error-input');
          admin.closeModal('#category-remove-modal');
          category.runOperation($('.category-operation').val(),true);
        } else {
          $('#category-remove-modal input').addClass('error-input');
        }
      });

      $('.section-category').on('click', '.prod-sub-cat', function() {
        includeJS(admin.SITE + '/mg-core/script/admin/catalog.js');
        admin.SECTION = 'catalog';
        admin.show("catalog.php", cookie("type"), "page=0&insideCat=true&applyFilter=1&displayFilter=1&cat_id=" + $(this).parents('tr').data('id'), catalog.init);
        $('#category').removeClass('active');
        $('#catalog').addClass('active');
      });

      $('.section-category').on('click', '.link-to-site', function() {
        window.open($(this).data('href'));
      });

      // Разворачивание подпунктов по клику
      $('.section-category').on('click', '.show_sub_menu', function(e, isProgrammClick) {
        var object = $(this).parents('tr');
        var id = $(this).parents('tr').data('id');
        var level = $(this).parents('tr').data('level');
        var group = 'group-'+$(this).parents('tr').data('id');
        level++;

        // thisSortNumber = $(this).parents('tr').data('sort');
        thisSortNumber = 0;
        isFindeSorte = false;
        $('.section-category .main-table tbody tr').each(function() {
          if($(this).data('id') == id) {
            isFindeSorte = true;
          }
          if(!isFindeSorte) {
            thisSortNumber++;
          }
        });

        if ($(this).hasClass('opened')) {
          category.delCategoryToOpenArr($(this).data('id'), level);

          category.group = $(this).parents('tr').data('group');

          var trCount = $('.section-category .main-table tbody tr').length;

          var startDel = false;
          $('.section-category .main-table tbody tr').each(function() {
            if($(this).data('level') >= level) {
              if($(this).data('group') == group) {
                startDel = true;
              }
            }
            if(startDel) {
              if($(this).data('level') >= level) {
                category.delCategoryToOpenArr($(this).data('id'), $(this).data('level')+1);
                $(this).detach();
              } else {
                startDel = false;
              }
            }
          });

          $(this).removeClass('opened');
        } else {
          category.addCategoryToOpenArr(id, level);
          object.after('\
            <tr id="loader-'+id+'">\
              <td><div class="checkbox"><input type="checkbox" name="category-check"><label class="select-row shiftSelect"></label></div></td>\
              <td class="sort">\
                <a class="mover"\
                   tooltip="Нажмите и перетащите категорию для изменения порядка сортировки в каталоге"\
                   flow="leftUp"\
                   href="javascript:void(0);"\
                   aria-label="Сортировать"\
                   role="button">\
                   <i class="fa fa-arrows" aria-hidden="true"></i>\
                </a>\
              </td>\
              <td class="number"></td>\
              <td style="padding-left:40px;"><img src="'+admin.SITE+'/mg-admin/design/images/loader-small.gif"></td>\
              <td colspan="2"></td>\
              <td class="text-right actions">\
                <ul class="action-list">\
                  <li><a class="fa fa-pencil tip edit-sub-cat" href="javascript:void(0);" tabindex="0" title="'+lang.EDIT+'"></a></li>\
                  <li><a class="fa fa-plus-circle tip add-sub-cat" href="javascript:void(0);" aria-hidden="true" title="'+lang.ADD_SUBCATEGORY+'"></a></li>\
                  <li><a class="fa fa-lightbulb-o tip activity" href="javascript:void(0);" aria-hidden="true" title="'+lang.DISPLAY+'"></a></li>\
                  <li><a class="fa fa-list tip visible" href="javascript:void(0);" aria-hidden="true" title="'+lang.SHOW_PRODUCT+'"></a></li>\
                  <li><a class="fa fa-search tip prod-sub-cat" href="javascript:void(0);" aria-hidden="true" title="'+lang.LOOK_AT_PROD_IN_CAT+'"></a></li>\
                  <li><a class="fa fa-trash tip delete-sub-cat" href="javascript:void(0);" aria-hidden="true" title="'+lang.DELETE+'"></a></li>\
                </ul>\
              </td>\
            </tr>');
          admin.ajaxRequest({
            mguniqueurl: "action/showSubCategory",
            id: id,
            level: level
          },
          function(response) {      
            $('#loader-'+id).detach();
            object.after(response.data);
            category.sortableInit();
            if(isProgrammClick) {
              category.hidePageRows();
            }
          });

          $(this).addClass('opened');
        }
      });

      // Клик на иконку меню, делает невидимой категорию в меню      
      $('.section-category').on('click', '.visible', function() {
        var id = $(this).parents('tr').data('id');

        if ($(this).hasClass('active')) {
          category.invisibleCat(id, 1);
          $(this).attr('tooltip', lang.ACT_V_CAT);
        } else {
          category.invisibleCat(id, 0);
          $(this).attr('tooltip', lang.ACT_UNV_CAT);
        }
        admin.initToolTip();
      });

      // Клик на иконку лампочки, делает неактивной категорию и товары этой категории     
      $('.section-category').on('click', '.activity', function() {
        var id = $(this).parents('tr').data('id');

        if (!$(this).hasClass('active')) {
          category.activityCat(id, 1);
          $(this).attr('tooltip', lang.ACT_V_CAT_ACT);
        } else {
          category.activityCat(id, 0);
          $(this).attr('tooltip', lang.ACT_UNV_CAT_ACT);
          
          $(this).attr('tooltip', lang.ACT_UNV_CAT_ACT + ' ' + lang.ACT_UNV_CAT_ACT2);
        }
        admin.initToolTip();
      });


      // Клик по кнопке для редактирования скидки
      $('.section-category').on('click', '#add-category-wrapper .discount-edit', function() {
        $('.discount-rate-control .discount-state').hide();
        $('.discount-rate-control .discount-rate-edit').show();
      });  
      
      //Клик по стикеру в меню для отмены скидки/наценки
      $('.section-category').on('click', '.sticker-menu .discount-cansel', function() {
        var obj = $(this).parents('.sticker-menu');
        obj.hide();       
        admin.ajaxRequest({
          mguniqueurl: "action/clearCategoryRate",
          id: obj.data('cat-id')   
        },
        function(response) {
          admin.indication(response.status, response.msg);
        });
      });   
      
      //Клик по стикеру в меню для применения скидки/наценки к вложенным подкатегориям
      $('.section-category').on('click', '.sticker-menu .discount-apply-follow', function() {
        var obj = $(this).parents('.sticker-menu');
        admin.ajaxRequest({
          mguniqueurl: "action/applyRateToSubCategory",
          id: obj.data('cat-id')
        },
        function(response) {
          admin.refreshPanel();
        });
      });
      
      //Клик по ссылке для установки скидки/наценки
      $('.section-category').on('click', '#add-category-modal .discount-setup-rate', function() {
        $(this).hide();
        $('.discount-rate-control').show();
        $('.discount_apply_follow').show();
				$('.wholesales-warning').show();
      });
      
      //Клик по отмене скидки/наценки 
      $('.section-category').on('click', '#add-category-modal .cancel-rate', function() {
        $('.discount-setup-rate').show();
        $('.discount-rate-control').hide();
				$('.wholesales-warning').hide();
        $('.discount-rate-control input[name=rate]').val(0);
        $('.discount_apply_follow').show();
      });
      
      // Клик по кнопке для смены скидки/наценки
      $('.section-category').on('click', '#add-category-modal .change-rate', function() {
        $('.select-rate-block').show();
        $('.discount_apply_follow').show();
      });

      // Клик по кнопке для  отмены модалки смены скидки/наценки
      $('.section-category').on('click', '#add-category-modal .cancel-rate-dir', function() {
        $('.select-rate-block').hide();  
        if($('.rate-dir').text()=="+") {
          $('.select-rate-block select[name=change_rate_dir] option[value=up]').prop('selected','selected');
        }
        if($('.rate-dir').text()=="-") {
          $('.select-rate-block select[name=change_rate_dir] option[value=down]').prop('selected','selected');
        }        
      });  
      
      // Клик по кнопке для применения скидки/наценки
      $('.section-category').on('click', '#add-category-modal .apply-rate-dir', function() {
        $('.select-rate-block').hide();
        if($('#add-category-modal .select-rate-block select[name=change_rate_dir]').val()=='up') {
          category.setupDirRate(1);
        } else {
          category.setupDirRate(-1);
        }
      });

      //Инициализирует CKEditior и раскрывает поле для заполнения seo описания категории
      $('.section-category').on('click', '.category-desc-wrapper-seo .html-content-edit-seo', function() {
        var link = $(this);
        if (!link.hasClass('init')) {
          //   $('#add-category-modal textarea[name=html_content-seo]').ckeditor(function() {  
          //   $('#html-content-wrapper-seo').show();
          //   link.addClass('init');
          // });
            $('#html-content-wrapper-seo').show();
        } else {
          $('#html-content-wrapper-seo').slideToggle();
        }        
      });
      
      $('.section-category').on('focus', '.discount-rate input[name=rate]', function() {
        $('.discount_apply_follow').show();
      }); 
      
      //Обработка клика по кнопке "Загрузить из CSV"
      $('.section-category').on('click', '.import-csv', function() {
        $('.import-container').slideToggle(function() {
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
      
      // Обработчик для загрузки файла импорта
      $('.section-category').on('change', 'input[name="upload"]', function() {

        category.uploadCsvToImport();
      });
      
      // Обработчик для загрузки файла импорта из CSV
      $('.section-category').on('click', '.repeat-upload-csv', function() {
        $('.import-container input[name="upload"]').val('');
        $('.import-container .block-upload-csv').show();
        $('.block-importer').hide();
        $('.repat-upload-file').show();
        $('.cancel-importing').hide();
        $('.message-importing').text('');
        category.STOP_IMPORT=false;

      });

      // Выбор ZIP архива на сервере
      $('.section-category').on('click', '.block-upload-images .browseImage', function () {
        admin.openUploader('category.getFileArchive');
      });

      // Обработчик для загрузки архива с изображениями
      $('.section-category').on('change', '.block-upload-images input[name="uploadImages"]', function () {
        console.log('test');
        category.uploadImagesArchive();
      });

      // Обработчик для загрузки изображения на сервер, сразу после выбора.
      $('.section-category').on('click', '.start-import', function() {
        if(!confirm(lang.CATALOG_LOCALE_1)) {
          return false;
        }
        $('.repat-upload-file').hide();
        $('.block-importer').hide();
        $('.cancel-importing').show();
        category.startImport($('.block-importer .uploading-percent').text());

      });

      $('.section-category').on('click', '.block-upload-images .startGenerationProcess', function () {
        $(this).hide();
        $('.message-importing').html(lang.CATALOG_LOCALE_6 + 0
          + '%<div class="progress-bar"><div class="progress-bar-inner" style="width:' + 0
          + '%;"></div></div>');
        $('.message-importing').show();
        category.startGenerationImageFunc();
      });

      // Останавливает процесс загрузки товаров.
      $('.section-category').on('click', '.cancel-import', function() {
        category.canselImport();
      });
      
      $('.section-category').on('click', '.get-csv', function() {
        category.exportToCsv(0);
        return false;
      });
      
      $('.section-category').on('click', '#add-category-modal .seo-gen-tmpl', function() {
        category.generateSeoFromTmpl();
      });

      $('.section-category').on('click', '.calcCountProd', function() {
        $('.catsCountInDoNothing').hide();
        $('.catsCountInProgress').show();
        category.calcCount();
      });

      $('.section-category').on('click', '#add-category-modal .closeModal', function() {
        admin.unlockEntity('#add-category-modal', 'category');
      });

      /*Инициализирует CKEditior и раскрывает поле для заполнения описания товара*/
      $('.section-category').on('click', '.category-desc-wrapper .html-content-edit', function(){
        var link = $(this);
        $('textarea[name=cat_desc]').ckeditor(function() {
          $('#html-content-wrapper').show();
          link.hide();
        });
      });
      /*Инициализирует CKEditior и раскрывает поле для заполнения seo описания товара*/
      $('.section-category').on('click', '.category-desc-wrapper .html-content-edit-seo', function(){
        var link = $(this);
        $('textarea[name=seo_content]').ckeditor(function() {
          $('#html-content-wrapper-seo').show();
          link.hide();
        });
      });

      $('.section-category').on('change', '.category-operation', function() {
        $('.categoryOperationParam').hide();
        $('.categoryOperationParam[data-operation="'+$(this).val()+'"]').show();
        if($(this).val() === 'changeProductCategory'){
          $('.changeProductCategoryOption').show();
        } else {
          $('.changeProductCategoryOption').hide();
        }
      });

      $('.section-category').on('change', '.main-table .checkbox [type="checkbox"], .category-operation', function () {
          setTimeout(function(){
            obj = $('.categoryOperationParam[data-operation="move"] select:first');
            if($('.categoryOperationParam[data-operation="changeProductCategory"]').css('display') !== 'none'){
              obj = $('.categoryOperationParam[data-operation="changeProductCategory"] select:first');
            }
            if (obj.is(':visible')) {
              obj.val(0);
              obj.find('option').show();

              $('.section-category .main-table .checkbox [type="checkbox"]:checked').each(function(index,element) {
                var id = $(this).closest('tr').data('id');
                var option = obj.find('option[value='+id+']');
                option.hide();
                if($('.changeProductCategoryOption').css('display') !== "none"){
                    return true;
                }          
                var childIds = option.data('childids');
                childIds.forEach(function(item) {
                  obj.find('option[value='+item+']').hide();
                });
              });
            }
          }, 1);
      });
	  
	  $('.section-category').on('click', '.section-hits', function () {
           newbieWay.showHits('categoryScenario');
           introJs().start();
       });
    },
    checkBlockIntervalFunction: function() {
      if (!$('#add-category-modal').length) {
        clearInterval(category.checkBlockInterval);
      }
      if(admin.blockTime == 0) {
        admin.setAndCheckBlock('#add-category-modal', 'category');
      }
      admin.blockTime -= 1;
    },
    calcCount: function() {
      admin.ajaxRequest({
        mguniqueurl:"action/calcCountProdCat",
      },
      function(response) {
        if(response.data < 100) {
          $('.catsCountInDoNothing').hide();
          $('.catsCountInProgress').show();
          $('.catsCountPerc').html(response.data);
          setTimeout(function() {
            category.calcCount();
          }, 2000);
        } else {
          $('.catsCountInDoNothing').show();
          $('.catsCountInProgress').hide();
          admin.indication(response.status, response.msg);
        }
      });
    },
    /**
      * Генерируем мета описание
      */
    generateMetaDesc: function(description) {
      if (!description) {return '';}
      var short_desc = description.replace(/<\/?[^>]+>/g, '');
      short_desc = admin.htmlspecialchars_decode(short_desc.replace(/\n/g, ' ').replace(/&nbsp;/g, '').replace(/\s\s*/g, ' ').replace(/"/g, ''));

      if (short_desc.length > 150) {
        var point = short_desc.indexOf('.', 150);
        short_desc = short_desc.substr(0, (point > 0 ? point : short_desc.indexOf(' ',150)));
      }

      return short_desc;
    },
    /**
    * Генерируем ключевые слова для категории
    * @param string title
    */
    generateKeywords: function(title) {
      if (!$('#add-category-modal input[name=meta_keywords]').val()) {
        var keywords = title;
        var keyarr = title.split(' ');
        
        if(keyarr.length == 1) {
          $('#add-category-modal input[name=meta_keywords]').val(keywords);
          return;
        }
        
        for ( var i=0; i < keyarr.length; i++) {
          var word = keyarr[i].replace('"','');
          
          if (word.length > 3) {
            keywords += ', ' + word;
          } else {
            if(i!==keyarr.length-1) {
               keywords += ', '+ word + ' ' + keyarr[i+1].replace(/"/g,'');
               i++; 
            } else {
                keywords += ', '+ word
            } 
          }         
        }
        
        $('#add-category-modal input[name=meta_keywords]').val(keywords);
      }
    },
    /**
    * Запускаем генерацию метатегов по шаблонам из настроек
    */
    generateSeoFromTmpl: function() {
      if (!$('.section-category .js-meta-block input[name=meta_keywords]').val()) {
        category.generateKeywords($('#add-category-modal input[name=title]').val());
      }
      
      if (!$('.section-category .js-meta-block input[name=meta_title]').val()) {
        $('.section-category .js-meta-block input[name=meta_title]').val($('#add-category-modal input[name=title]').val());
      }

      if (!$('.section-category .js-meta-block textarea[name=meta_desc]').val()) {
        var short_desc = category.generateMetaDesc($('#add-category-modal textarea[name=html_content]').val());
        $('#add-category-modal .section-category .js-meta-block textarea[name=meta_desc]').val($.trim(short_desc));
      }
      
      var data = {
        titeCategory: $('input[name=title]').val(),
        cat_desc: $('#add-category-modal textarea[name=html_content]').val(),
        meta_title: $('input[name=meta_title]').val(),
        meta_keywords: $('input[name=meta_keywords]').val(),
        meta_desc: $('textarea[name=meta_desc]').val(),
      };

      admin.ajaxRequest({
        mguniqueurl:"action/generateSeoFromTmpl",
        type: 'catalog',
        data: data
      }, function(response) {
        $.each(response.data, function(key, value) {
          if (value) {
            if (key == 'meta_desc') {
              $('.section-category .js-meta-block textarea[name='+key+']').val(value);
            } else {
              $('.section-category .js-meta-block input[name='+key+']').val(value);
            }
          }
        });
        
        $('#add-category-modal .js-meta-data').trigger('blur');
        admin.indication(response.status, response.msg);
      });
    },
    /**
     * Загружает CSV файл на сервер для последующего импорта
     */
    uploadCsvToImport:function() {
      // отправка файла CSV на сервер
      $('.message-importing').text(lang.MESSAGE_WAIT);
      $('.upload-csv-form').ajaxForm({
        type:"POST",
        url: "ajax",
        data: {
          mguniqueurl:"action/uploadCsvToImport"
        },
        cache: false,
        dataType: 'json',
        error: function() {alert(lang.CATALOG_MESSAGE_5);},
        success: function(response) {
          admin.indication(response.status, response.msg);
          if(response.status=='success') {
            $('.import-container .block-upload-csv').hide();
            $('.block-importer').show();
            $('.section-catalog select.importScheme').removeAttr('disabled');
            $('.message-importing').text(lang.FILE_READY_IMPORT_CATEGORY);
          } else {
            $('.message-importing').text('');
            $('.import-container input[name="upload"]').val('');
          }
        },

      }).submit();

    },
    /**
     * Контролирует процесс импорта, выводит индикатор в процентах обработки каталога.
     */
    startImport:function(rowId, percent) {
      var delCatalog=null;
      
      if(!rowId) {
        if(!$('.loading-line').length) {
          $('.process').append('<div class="loading-line"></div>');
        }
        
        rowId = 0;
        delCatalog = $('input[name=no-merge]').val();
      }
      
      if(!percent) {
        percent = 0;
      }
      
      $('.message-importing').html(lang.IMPORT_CATEGORY_PROGRESS+percent+'%<div class="progress-bar"><div class="progress-bar-inner" style="width:'+percent+'%;"></div></div>');
      
      // отправка файла CSV на сервер
      admin.ajaxRequest({
        mguniqueurl:"action/startImportCategory",
        rowId:rowId,
        delCatalog:delCatalog,
      },
      function(response) {
        if(response.status=='error') {
          admin.indication(response.status, response.msg);
        }

        if(response.data.percent < 100) {
          if(response.data.status == 'canseled') {
            $('.message-importing').html(lang.IMPORT_STOP+response.data.rowId+' '+lang.LOCALE_GOODS+' '+'[<a role="button" href="javascript:void(0);" class="repeat-upload-csv">'+lang.UPLOAD_ANITHER+'</a>]' );
            $('.block-importer').hide();
            $('.loading-line').remove();
          } else {
            category.startImport(response.data.rowId,response.data.percent);
          }
        } else {
          $('.message-importing').html(lang.IMPORT_CATEGORY_FINISHED+'\
            <a class="refresh-page custom-btn" href="'+mgBaseDir+'/mg-admin/">\n\
              <span>'+lang.CATALOG_REFRESH+'</span>\n\
            </a>');
          $('.block-importer').hide();
          $('.loading-line').remove();
        }

      });
    },
    exportToCsv: function(rowCount) {   
      if(!rowCount) {
        rowCount = 0;
      }
      loader = $('.mailLoader');
      $.ajax({
        type: "POST",
        url: mgBaseDir + "/mg-admin/",
        data: {
          category_csv: 1,
          rowCount: rowCount
        },
        dataType: "json",
        cache: false,
        beforeSend: function() {
          // флаг, говорит о том что начался процесс загрузки с сервера
          admin.WAIT_PROCESS = true;
          loader.hide();
          loader.before('<div class="view-action" style="display:none; margin-top:-2px;">' + lang.LOADING + '</div>');
          // через 300 msec отобразится лоадер.
          // Задержка нужна для того чтобы не мерцать лоадером на быстрых серверах.

          setTimeout(function () {
            if (admin.WAIT_PROCESS) {
              admin.waiting(true);
            }
          }, admin.WAIT_DELAY);
        },
        success: function(response) {
          admin.WAIT_PROCESS = false;
          admin.waiting(false);
          loader.show();
          $('.view-action').remove();

          if(!response.success) {
            admin.indication('success', lang.INDICATION_INFO_EXPORTED+' '+response.percent+'%');
            category.exportToCsv(response.nextPage, response.rowCount);
          } else {
            admin.indication('success', lang.INDICATION_INFO_EXPORTED+' 100%');
            $('body').append('<iframe src="'+mgBaseDir+'/'+response.file+'" style="display: none;"></iframe>');
          }
        }
      });
    },
    /** 
     * меняет местами две категории oneId и twoId
     * oneId - идентификатор первой категории
     * twoId - идентификатор второй категории
     */
    changeSortCat: function(oneId, twoId) {
      admin.ajaxRequest({
        mguniqueurl: "action/changeSortCat",
        oneId: oneId,
        twoId: twoId
      },
      function(response) {
        admin.indication(response.status, response.msg)
      });
    },
    /** 
     * Делает категорию  видимой/невидимой в меню
     * oneId - идентификатор первой категории
     * twoId - идентификатор второй категории
     */
    invisibleCat: function(id, invisible) {
      loader = $('.mailLoader');
      loader.before('<div class="view-action" style="display:block; margin-top:-2px;">' + lang.LOADING + '</div>');
      $.ajax({
        type: "POST",
        url: mgBaseDir + "/ajax",
        data: {
          mguniqueurl: "action/invisibleCat",
          id: id,
          invisible: invisible
        },
        dataType: "json",
        cache: false,
        success: function(response) {
          admin.indication(response.status, response.msg);
          admin.refreshPanel();
        }
      });
    },
    /** 
     * Делает категорию  активной/неактивной в меню
     * oneId - идентификатор первой категории
     * twoId - идентификатор второй категории
     */
    activityCat: function(id, activity) {
      admin.ajaxRequest({
        mguniqueurl: "action/activityCat",
        id: id,
        activity: activity
      },
      function(response) {
        admin.indication(response.status, response.msg);
        admin.refreshPanel();
      });
    },

    // добавляет ID открытой категории в массив, записывает в куки для сохранения статуса дерева
    addCategoryToOpenArr: function(id, level) {
      level = typeof level !== 'undefined' ? level : 0;
      if(category.openedCategoryAdmin == undefined) 
        category.openedCategoryAdmin = [];

      var addId = true;
      category.openedCategoryAdmin.forEach(function(item) {
        if (item == id) {
          addId = false;
        }
      });

      if (addId) {
        category.openedCategoryAdmin.push(id);
      }

      cookie("openedCategoryAdmin", JSON.stringify(category.openedCategoryAdmin));
    },
    // удаляет ID закрытой категории из массива, записывает в куки для сохранения статуса дерева
    delCategoryToOpenArr: function(id, level) {
      level = typeof level !== 'undefined' ? level : 0;
      if(category.openedCategoryAdmin == undefined) 
        category.openedCategoryAdmin = [];

      var dell = false;
      var i = 0;
      var spliceIndex = 0;
      category.openedCategoryAdmin.forEach(function(item) {
        if (item == id) {
          dell = true;
          spliceIndex = i;
        }
        i++;
      });

      if (dell) {
        category.openedCategoryAdmin.splice(spliceIndex, 1);
      }

      cookie("openedCategoryAdmin", JSON.stringify(category.openedCategoryAdmin));
    },
    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового товара, либо для редактирования старого.
     * id - редактируемая категория, если это не создание новой
     */
    openModalWindow: function(type, id) {
      category.delImgs = [];
     try{        
        if(CKEDITOR.instances['html_content']) {
          CKEDITOR.instances['html_content'].destroy();
        }      
      } catch(e) { }   
      try{        
        if(CKEDITOR.instances['html_content-seo']) {
          CKEDITOR.instances['html_content-seo'].destroy();
        }      
      } catch(e) { } 
      switch (type) {
        case 'edit':
          {
            category.clearFileds();
            $('.html-content-edit').show();
            $('.category-desc-wrapper #html-content-wrapper').hide();
            $('.category-desc-wrapper-seo #html-content-wrapper-seo').hide();
            $('#modalTitle').text(lang.EDIT_CAT);
            $('#modalTitle').parent().find('i').attr('class', 'fa fa-pencil');
            category.editCategory(id);
            break;
          }
        case 'add':
          {
            $('#modalTitle').text(lang.ADD_CATEGORY);
            $('#modalTitle').parent().find('i').attr('class', 'fa fa-plus-circle');
            category.clearFileds();
            // $('.html-content-edit').hide();
            $('.category-desc-wrapper #html-content-wrapper').show();
            $('.category-desc-wrapper-seo #html-content-wrapper-seo').hide();
            break;
          }
        case 'addSubCategory':
          {
            $('#modalTitle').text(lang.ADD_SUBCATEGORY);
            $('#modalTitle').parent().find('i').attr('class', 'fa fa-plus-circle');
            category.clearFileds();
            $('.html-content-edit').show();
            $('.category-desc-wrapper #html-content-wrapper').hide();
            $('.category-desc-wrapper-seo #html-content-wrapper-seo').hide();
            $('select[name=parent] option[value="' + id + '"]').prop("selected", "selected");
            admin.reloadComboBoxes();
            break;
          }
        default:
          {
            category.clearFileds();
            break;
          }
      }

      // Вызов модального окна.
      admin.openModal('#add-category-modal');
    },
    /**
     *  Проверка заполненности полей, для каждого поля прописывается свое правило.
     */
    checkRulesForm: function() {
      $('.errorField').css('display', 'none');
      $('input').removeClass('error-input');

      var error = false;
      // наименование не должно иметь специальных символов.
      if (!$('input[name=title]').val()) {
        $('input[name=title]').parent("div").find('.errorField').css('display', 'block');
        $('input[name=title]').addClass('error-input');
        error = true;
      }

      // артикул обязательно надо заполнить.
      if (!admin.regTest(1, $('input[name=url]').val()) || !$('input[name=url]').val()) {
        $('input[name=url]').parent("div").find('.errorField').css('display', 'block');
        $('input[name=url]').addClass('error-input');
        error = true;
      }
      
      var url = $('input[name=url]').val();
      var reg = new RegExp('([^/-a-z\.\d])','i');
      
      if (reg.test(url)) {
        $('input[name=url]').parent("div").find('.errorField').css('display','block');
        $('input[name=url]').addClass('error-input');
        $('input[name=url]').val('');
        error = true;
      }

      if(isNaN(parseFloat($('.discount-rate-control input[name=rate]').val()))) {
        $('.discount-error.errorField').css('display','block');
        $('.product-text-inputs input[name=price]').addClass('error-input');
        error = true;
      }

      if (error == true) {
        return false;
      }

      return true;
    },
    /**
     * Сохранение изменений в модальном окне категории.
     * Используется и для сохранения редактированных данных и для сохранения нового продукта.
     * id - идентификатор продукта, может отсутствовать если производится добавление нового товара.
     */
    saveCategory: function(id, closeModal) {
      closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
      // Если поля неверно заполнены, то не отправляем запрос на сервер.
      if (!category.checkRulesForm()) {
        return false;
      }

      if($('#add-category-modal textarea[name=html_content]').val() == '' && $('#add-category-modal textarea[name=html_content]').text() != '') {
        if(!confirm(lang.ACCEPT_EMPTY_DESC+'?')) {
          return false;
        }
      }

      var validFormats = ['jpeg', 'jpg', 'png', 'gif', 'svg', 'webp'];
      var ext = '';
      if ($('#add-category-modal .imgPrimary input[name="image_url"]').val()) {
        ext = $('#add-category-modal .imgPrimary input[name="image_url"]').val();
        ext = ext.split('.');
        ext = ext.pop();
        ext = ext.toLowerCase();
        if (jQuery.inArray(ext, validFormats) < 0) {
          admin.indication('error', lang.ACT_IMG_NOT_UPLOAD2);
          return false;
        }
      }
      
      if ($('#add-category-modal .imgSecondary input[name="image_url"]').val()) {
        ext = $('#add-category-modal .imgSecondary input[name="image_url"]').val();
        ext = ext.split('.');
        ext = ext.pop();
        ext = ext.toLowerCase();
        if (jQuery.inArray(ext, validFormats) < 0) {
          admin.indication('error', lang.ACT_IMG_NOT_UPLOAD2);
          return false;
        }
      }

      $('#add-category-modal input[type=text]').each(function() {
        $(this).val(admin.htmlspecialchars($(this).val()));
      });

      var rate = $('#add-category-modal .discount-rate-control input[name=rate]').val();
      if(rate!=0) {
        rate = rate/100;
      }
      if($('#add-category-modal .rate-dir').text()!='+') {
        rate = -1*rate;
      }

      // Пакет характеристик категории.
      var packedProperty = {
        mguniqueurl: "action/saveCategory",
        id: id,
        unit: $('#add-category-modal input[name=unit]').val(),
        weight_unit: $('#add-category-modal select[name=weightUnit]').val(),
        title: $('#add-category-modal input[name=title]').val(),
        menu_title: $('#add-category-modal input[name=menu_title]').val(),
        url: $('#add-category-modal input[name=url]').val(),
        parent: $('#add-category-modal select[name=parent]').val(),
        html_content: $('#add-category-modal textarea[name=html_content]').val(),
        meta_title: $('#add-category-modal input[name=meta_title]').val(),
        meta_keywords: $('#add-category-modal input[name=meta_keywords]').val(),
        meta_desc: $('#add-category-modal textarea[name=meta_desc]').val(),
        image_url: $('#add-category-modal .imgPrimary input[name="image_url"]').val(),
        menu_icon: $('#add-category-modal .imgSecondary input[name="image_url"]').val(),
        invisible: $('#add-category-modal input[name=invisible]').prop('checked') ? 1 : 0,
        rate: rate,
        seo_content: $('#add-category-modal textarea[name=html_content-seo]').val(),
        lang: $('.select-lang').val(),
        seo_alt: $('#add-category-modal .imgPrimary [name=image_alt]').val(),
        seo_title: $('#add-category-modal .imgPrimary [name=image_title]').val(),
        menu_seo_alt: $('#add-category-modal .imgSecondary [name=image_alt]').val(),
        menu_seo_title: $('#add-category-modal .imgSecondary [name=image_title]').val(),
        delImgs: category.delImgs,
      };
      packedProperty.opFields = {};
      $('#add-category-modal .categoryOpFields').each(function() {
        packedProperty.opFields['op_'+$(this).attr('name')] = $(this).val();
      });
      // Отправка данных на сервер для сохранения.
      admin.ajaxRequest(packedProperty,
        function(response) {
          admin.indication(response.status, response.msg);                
          if (response.status !== 'success') {
            return false;
          }
          if ($('input[name=discount_apply_follow]').val()== 'true') {
            admin.ajaxRequest({
            mguniqueurl: "action/applyRateToSubCategory",
            id: id
            },
              function(response) {  
                if (closeModal) {
                  admin.closeModal($('#add-category-modal'));
                  admin.refreshPanel();
                } else {
                  $('#add-category-modal').attr('data-refresh', 'true');
                }
              });
          } else {
            // Закрываем окно.
            if (closeModal) {
              admin.closeModal($('#add-category-modal'));
              admin.refreshPanel();
            } else {
              $('#add-category-modal').attr('data-refresh', 'true');
            }
          }                
        }
      );
    },
    /**
     * Получает данные о категории с сервера и заполняет ими поля в окне.
     */
    editCategory: function(id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getCategoryData",
        id: id,
        lang: $('.select-lang').val()
      },
      category.fillFields(),
              $('.add-product-form-wrapper .add-category-form')
              );
    },
    /**
     * Удаляет категорию из БД сайта
     */
    deleteCategory: function(id) {
      if (!confirm(lang.SUB_CATEGORY_DELETE + '?')) {return false;}
      var dropProducts = 'false';
      if (confirm(lang.SUB_CATEGORY_DELETE_PROD)) {dropProducts = 'true';}

        admin.ajaxRequest({
          mguniqueurl: "action/deleteCategory",
          id: id,
          dropProducts: dropProducts
        },
        function(response) {
          admin.indication(response.status, response.msg);
          admin.refreshPanel();
        });
      
    },
    /**
     * Заполняет поля модального окна данными.
     */
    fillFields: function() {
      return (function(response) {

        $('.accordion-item').removeClass('is-active');
        $('.accordion-content').hide();
        
        $('input').removeClass('error-input');
        $('input[name=unit]').val(admin.htmlspecialchars_decode(response.data.unit));
        $('select[name=weightUnit]').val(response.data.weight_unit);
        $('input[name=title]').val(response.data.title);
        $('input[name=menu_title]').val(admin.htmlspecialchars_decode(response.data.menu_title));
        $('input[name=url]').val(response.data.url);
        $('select[name=parent] option').each(function (index, element){
          let oldText = $(this).text();
          $(this).text(admin.htmlspecialchars_decode(oldText));
        });
        $('select[name=parent]').val(response.data.parent);
        $('select[name=parent]').val(response.data.parent);
        $('input[name=invisible]').prop('checked', false);
        $('input[name=invisible]').val('false');
        if (response.data.invisible == 1) {
          $('input[name=invisible]').prop('checked', true);
          $('input[name=invisible]').val('true');
        }
        $('input[name=meta_title]').val(response.data.meta_title);  
        category.supportCkeditor = response.data.html_content;  
        category.supportCkeditorSeo = response.data.seo_content;  
        $('#add-category-modal textarea[name=html_content]').val(response.data.html_content);
        $('#add-category-modal textarea[name=html_content-seo]').val(response.data.seo_content);
        $('#add-category-modal textarea[name=html_content]').text(response.data.html_content);
        $('#add-category-modal textarea[name=html_content-seo]').text(response.data.seo_content);
        $('input[name=meta_keywords]').val(response.data.meta_keywords);
        $('textarea[name=meta_desc]').val(response.data.meta_desc);
        $('.symbol-count').text($('textarea[name=meta_desc]').val().length);

        $('#add-category-modal .imgPrimary [name=image_title]').val('');
        $('#add-category-modal .imgPrimary [name=image_alt]').val('');
        $('#add-category-modal .imgPrimary [name=image_title]').val(response.data.seo_title);
        $('#add-category-modal .imgPrimary [name=image_alt]').val(response.data.seo_alt);

        $('#add-category-modal .imgSecondary [name=image_title]').val('');
        $('#add-category-modal .imgSecondary [name=image_alt]').val('');
        $('#add-category-modal .imgSecondary [name=image_title]').val(response.data.menu_seo_title);
        $('#add-category-modal .imgSecondary [name=image_alt]').val(response.data.menu_seo_alt);
                
        if(response.data.image_url) {
          $('#add-category-modal .imgPrimary .category-image').attr('src', admin.SITE+response.data.image_url);
          $('#add-category-modal .imgPrimary .category-image').show();
          $('#add-category-modal .imgPrimary .category-img-block').show();          
          $('#add-category-modal .imgPrimary .del-image-to-category').show();  
          $('#add-category-modal .imgPrimary .add-image-to-category').hide();
          $('#add-category-modal .imgPrimary  input[name="image_url"]').val(admin.SITE+response.data.image_url);
        } else {
          $('#add-category-modal .imgPrimary  input[name="image_url"]').val('');
          $('#add-category-modal .imgPrimary .category-image').hide();
          $('#add-category-modal .imgPrimary .category-img-block').hide();     
          $('#add-category-modal .imgPrimary .del-image-to-category').hide();  
          $('#add-category-modal .imgPrimary .add-image-to-category').show();
        }  

        if(response.data.menu_icon) {
          $('#add-category-modal .imgSecondary .category-image').attr('src', admin.SITE+response.data.menu_icon);
          $('#add-category-modal .imgSecondary .category-image').show();
          $('#add-category-modal .imgSecondary .category-img-block').show();          
          $('#add-category-modal .imgSecondary .del-image-to-category').show();  
          $('#add-category-modal .imgSecondary .add-image-to-category').hide();
          $('#add-category-modal .imgSecondary  input[name="image_url"]').val(admin.SITE+response.data.menu_icon);
        } else {
          $('#add-category-modal .imgSecondary  input[name="image_url"]').val('');
          $('#add-category-modal .imgSecondary .category-image').hide();
          $('#add-category-modal .imgSecondary .category-img-block').hide();     
          $('#add-category-modal .imgSecondary .del-image-to-category').hide();  
          $('#add-category-modal .imgSecondary .add-image-to-category').show();
        }
        if (response.data.opFields) {
          $('#add-category-modal .opFields').show().find('.accordion-content').html(response.data.opFields);
        } else {
          $('#add-category-modal .opFields').hide().find('.accordion-content').html('');
        }
        $('.discount-rate-control input[name=rate]').val(response.data.rate == 0 ? '0' : ((response.data.rate)*100).toFixed(4));
        if(response.data.rate!=0) {
          $('.discount-setup-rate').hide();
          $('.wholesales-warning').show();
          $('.discount-rate-control').show();
          category.setupDirRate(response.data.rate);  
        }
        
        $('.save-button').attr('id', response.data.id);
        //$('#add-category-modal textarea[name=html_content]').ckeditor(function() {});

        admin.reloadComboBoxes();
        admin.blockTime = 0;
        admin.setAndCheckBlock('#add-category-modal', 'category');
      });
    },
    /**
     * Чистит все поля модального окна.
     */
    clearFileds: function() {
      $('.select-lang').val('default');
      $('#add-category-modal .category-img-block').hide();
      $('#add-category-modal .category-image').hide();
      $('#add-category-modal .add-image-to-category').show();
      $('#add-category-modal .del-image-to-category').hide(); 
      $('#add-category-modal .category-img-block img').attr('src', admin.SITE+'/mg-admin/design/images/100x100.png');
      
      $('input[name=unit]').val('шт.');
      $('select[name=weightUnit]').val('kg');
      $('input[name=title]').val('');
      $('input[name=url]').val('');
      $('select[name=parent]').val('0');
      $('input[name=invisible]').prop('checked', false).val('false');
      $('textarea').val('');
      $('input[name=meta_title]').val('');
      $('input[name=meta_keywords]').val('');
      $('textarea[name=meta_desc]').val('');
      $('.symbol-count').text('0');
      $('#add-category-modal  input[name="image_url"]').val('');
      $('#add-category-modal .category-image').hide();
      $('#add-category-modal .category-img-block').hide();     
      $('#add-category-wrapper .del-image-to-category').hide();  
      $('#add-category-wrapper .add-image-to-category').show();
      $('.save-button').attr('id', '');
      // Стираем все ошибки предыдущего окна если они были.
      $('.errorField').css('display', 'none');
      $('.error-input').removeClass('error-input');
      $('.discount-setup-rate').show();
      $('.wholesales-warning').hide();
      $('.discount-rate-control input[name=rate]').val(0);
      $('.discount-rate-control').hide();
      $('#add-category-wrapper .discount-rate').removeClass('color-down').addClass('color-up');
      category.setupDirRate(0);
      $('.category-desc-wrapper-seo .html-content-edit-seo').removeClass('init');
      $('input[name=discount_apply_follow]').prop('checked', false);
      $('input[name=discount_apply_follow]').val('false');
      $('.discount_apply_follow').hide();
      category.supportCkeditor = "";
      category.supportCkeditorSeo = "";
      $('#add-category-modal [name=image_title]').val('');
      $('#add-category-modal [name=image_alt]').val('');
    },      
    setupDirRate: function(rate) {    
      if(rate>=0) {
        $('#add-category-modal select[name=change_rate_dir] option[value=up]').prop('selected','selected');
        // $('#add-category-modal .discount-rate').removeClass('color-down').addClass('color-up');
        $('#add-category-modal .rate-dir').text('+');
        $('#add-category-modal .rate-dir-name span').text(lang.DISCOUNT_UP);
        $('#add-category-modal .discount-rate-control input[name=rate]').val(Math.abs($('#add-category-modal .discount-rate-control input[name=rate]').val()));
      } else {
        $('#add-category-modal select[name=change_rate_dir] option[value=down]').prop('selected','selected');  
        $('#add-category-modal .rate-dir-name span').text(lang.DISCOUNT_DOWN);
        // $('#add-category-modal .discount-rate').removeClass('color-up').addClass('color-down');
        $('#add-category-modal .rate-dir').text('-');
        $('#add-category-modal .discount-rate-control input[name=rate]').val(Math.abs($('#add-category-modal .discount-rate-control input[name=rate]').val()));
      }
    },        
    /**
     * устанавливает для каждой категории в списке возможность перемещения
     */
    draggableCat: function() {
      var listIdStart = [];
      var listIdEnd = [];

      $('.category-tree li').each(function() {

        $(this).addClass('ui-draggable');

        $(this).draggable({
          scroll: true,
          cursor: "move",
          handle: "div[class=mover]",
          snapMode: 'outer',
          snapTolerance: 0,
          start: function(event, ui) {
            $(this).css('width', '50%');
            $(this).parent('UL').addClass('editingCat');
            $(this).css('opacity', '0.5');
            $(this).css('height', '1px');
            var li = $(this).parent('UL').find('li');

            // составляем список ID категорий в текущем UL.
            listIdStart = [];
            var $thisId = $(this).find('a').attr('id');
            li.each(function(i) {
              if ($(this).parent('ul').hasClass('editingCat')) {
                var id = $(this).find('a').attr('id');
                if ($thisId == id) {
                  listIdStart.push('start');
                } else {
                  listIdStart.push($(this).find('a').attr('id'));
                }
              }
            });

            $(this).before('<li class="pos-element" style="display:none;"></li>'); // чтобы можно было вернуть на тоже место           
            $(this).parent('UL').append('<li class="end-pos-element"></li>'); // чтобы можно было вставить в конец списка  

          },
          stop: function(event, ui) {

            // найдем выделенный объект поместим перед ним тот который перетаскивался
            $(this).attr('style', 'style=""');
            $('.afterCat').before($(this));


            var li = $(this).parent('UL').find('li');

            // составляем список ID категорий в текущем UL.
            listIdEnd = [];
            var $thisId = $(this).find('a').attr('id');
            li.each(function(i) {
              if ($(this).parent('ul').hasClass('editingCat')) {
                var id = $(this).find('a').attr('id');
                if (id) {
                  if ($thisId == id) {
                    listIdEnd.push('end');
                  } else {
                    listIdEnd.push($(this).find('a').attr('id'));
                  }
                }
              }
            });


            $(this).parent('UL').removeClass('editingCat');
            $(this).parent('UL').find('li').removeClass('afterCat');
            $('.pos-element').remove();
            $('.end-pos-element').remove();


            var sequence = category.getSequenceSort(listIdStart, listIdEnd, $(this).find('a').attr('id'));
            if (sequence.length > 0) {
              sequence = sequence.join();
              admin.ajaxRequest({
                mguniqueurl: "action/changeSortCat",
                switchId: $thisId,
                sequence: sequence
              },
              function(response) {
                admin.indication(response.status, response.msg)
              }
              );
            }

          },
          drag: function(event, ui) {
            var dragElementTop = $(this).offset().top;
            var li = $(this).parent('UL').find('li');
            li.removeClass('afterCat');

            // проверяем, существуют ли LI ниже  перетаскиваемого.
            li.each(function(i) {
              $('.end-pos-element').removeClass('afterCat');
              if ($(this).offset().top > dragElementTop
                      && !$(this).hasClass('pos-element')
                      && $(this).parent('ul').hasClass('editingCat')
                      ) {
                $(this).addClass('afterCat');
                return false;
              } else {
                $('.end-pos-element').addClass('afterCat');
              }
            });
          }

        });
      });
    },
    /**
     * Вычисляет последовательность замены порядковых индексов 
     * Получает  для массива
     * ["1", "start", "9", "2", "10"]
     * ["1", "9", "2", "end", "10"]
     * и ID перемещенной категории
     */
    getSequenceSort: function(arr1, arr2, id) {
      var startPos = '';
      var endPos = '';

      // вычисляем стартовую позицию элемента
      arr1.forEach(function(element, index, array) {
        if (element == "start") {
          startPos = index;
          arr1[index] = id;
          return false;
        }
      });

      // вычисляем конечную позицию элемента      
      arr2.forEach(function(element, index, array) {
        if (element == "end") {
          endPos = index;
          arr2[index] = id;
          return false;
        }
      });

      // вычисляем индексы категорий с которым и надо поменяться пместами     
      var result = [];

      // направление переноса, сверху вниз
      if (endPos > startPos) {
        arr1.forEach(function(element, index, array) {
          if (index > startPos && index <= endPos) {
            result.push(element);
          }
        });
      }

      // направление переноса, снизу вверх
      if (endPos < startPos) {
        arr2.forEach(function(element, index, array) {
          if (index > endPos && index <= startPos) {
            result.unshift(element);
          }
        });
      }

      return result;
    },

    /**
     * Загружает Архив с изображениями на сервер для последующего импорта
     */
    uploadImagesArchive: function () {
      // отправка архива с изображениями на сервер
      $('.upload-goods-image-form').ajaxSubmit({
        type: "POST",
        url: "ajax",
        cache: false,
        dataType: 'json',
        data: {
          mguniqueurl: "action/uploadImagesArchive",
        },
        error: function (q, w, r) {
          console.log(q);
          console.log(w);
          console.log(r);
          // comerceMlModule.printLog("Ошибка: Загружаемый вами файл превысил максимальный объем и не может быть передан на сервер из-за ограничения в настройках файла php.ini");
          admin.indication('error', lang.CATALOG_LOCALE_12);
          $('.view-action').remove();
        },
        success: function (response) {
          if (response.msg) admin.indication(response.status, response.msg);
          if (response.status == 'success') {
            $('.upload-images').hide();
            $('.start-generate').show();
          } else {
            $('.import-container input[name="upload"]').val('');
          }
          $('.view-action').remove();
        },
      });
    },
  
    /**
    * функция для приема файла из аплоадера
    */         
    getFileArchive: function (file) {
      $.ajax({
        type: "POST",
        url: "ajax",
        data: {
          mguniqueurl: "action/selectImagesArchive",
          data: {
            filename: file.url,
          }
        },
        dataType: 'json',
        success: function (response) {
          admin.indication(response.status, response.msg);
          if (response.status == 'success') {
            $('.upload-images').hide();
            $('.start-generate').show();
          }
        }
      });
    },

    startGenerationImageFunc: function (nextItem, total_count, imgCount) {
      nextItem = typeof nextItem !== 'undefined' ? nextItem : 0;
      admin.ajaxRequest({
          mguniqueurl: "action/startGenerationImageCategory",
          nextItem: nextItem,
          total_count: total_count,
          imgCount: imgCount
        },
        function (response) {
          admin.indication(response.status, response.msg);

          if (response.data.percent < 100) {
            $('.message-importing').html(lang.CATALOG_LOCALE_6
              + response.data.percent
              + '%<div class="progress-bar"><div class="progress-bar-inner" style="width:'
              + response.data.percent + '%;"></div></div>');
            category.startGenerationImageFunc(response.data.nextItem, response.data.total_count, response.data.imgCount);
          } else {
            $('.message-importing').html(lang.CATALOG_LOCALE_6
              + response.data.percent
              + '%<div class="progress-bar"><div class="progress-bar-inner" style="width:'
              + '100%;"></div></div>');
              $('.loger').append('Импорт категорий успешно завершен!' + ' \
			   <a class="refresh-page custom-btn" href="' + mgBaseDir + '/mg-admin/">\n\
				 <span>' + lang.CATALOG_REFRESH + '</span>\
			  </a>');
//          admin.refreshPanel();
          }
        });
    },
  
    /**
    * функция для приема файла из аплоадера
    */         
    getFile: function(file) {
      category.delImgs.push($('#add-category-modal .imgPrimary input[name=image_url]').val());
      $('#add-category-modal .imgPrimary input[name="image_url"]').val(file.url);
      $('#add-category-modal .imgPrimary .category-image').attr('src',file.url);
      $('#add-category-modal .imgPrimary .category-img-block').show();
      $('#add-category-modal .imgPrimary .category-image').show();
      $('#add-category-modal .imgPrimary .add-image-to-category').hide();
      $('#add-category-modal .imgPrimary .del-image-to-category').show();  
    }, 

    /**
    * функция для приема файла из аплоадера
    */         
    getFileSecondary: function(file) {
      category.delImgs.push($('#add-category-modal .imgSecondary input[name=image_url]').val());
      $('#add-category-modal .imgSecondary  input[name="image_url"]').val(file.url);
      $('#add-category-modal .imgSecondary .category-image').attr('src',file.url);
      $('#add-category-modal .imgSecondary .category-img-block').show();
      $('#add-category-modal .imgSecondary .category-image').show();
      $('#add-category-modal .imgSecondary .add-image-to-category').hide();
      $('#add-category-modal .imgSecondary .del-image-to-category').show();  
    }, 
    
    /**
     * Выполняет выбранную операцию со всеми отмеченными категориями
     * operation - тип операции.
     */
    runOperation: function(operation, skipConfirm) { 
      if(typeof skipConfirm === "undefined" || skipConfirm === null){skipConfirm = false;}
      var category_id = [], nestedCategory = null, url = null, currentRootUrl = '/';
      if($('.changeProductCategoryOption').css('display') !== 'none'){
        nestedCategory = $('.changeProductCategoryOption select').val();
    }
    
      $('.category-tree input[name=category-check]').each(function() {              
        if($(this).prop('checked')) {  
          category_id.push($(this).parents('tr').data('id'));
          if(nestedCategory){       
             url = $(this).parents('tr').find('.tip').first().text();
             if (url.indexOf(currentRootUrl + '/') === 0) {
               category_id.pop();
             } else {
              currentRootUrl = url;
             }
          }
        }
      });
      
      if($('.changeProductCategoryOption').css('display') !== "none"){
        if($('.categoryOperationParam[data-operation="changeProductCategory"] select').val() == 0){
          alert(lang.CHECK_SELECTED_CATEGORY);
          return;
        }
      }

      var confirm_message = (operation == 'deleteproduct') ? (lang.SUB_CATEGORY_DELETE_PRODUCT) : (lang.RUN_CONFIRM);
      if (skipConfirm || confirm(confirm_message)) {
        var dropProducts = 'false';
        if (operation == 'deleteproduct') {dropProducts = 'true';}
        var param;
        if($('.categoryOperationParam select:visible').length) {
            param = $('.categoryOperationParam select:visible').val();
        }

        admin.ajaxRequest({
          mguniqueurl: "action/operationCategory",
          operation: operation,
          category_id: category_id,
          dropProducts: dropProducts,
          param: param,
          nestedCategory: nestedCategory
        },
        function(response) { 
          admin.refreshPanel();  
        });
      }
    },
    /**
     * 
     * Упорядочивает всё дерево категорий по алфавиту 
     */
    sortToAlphabet: function() {
      if (confirm(lang.ARRANGE_TREE_QUEST)) {        
        admin.ajaxRequest({
          mguniqueurl: "action/sortToAlphabet",      
        },
        function(response) {         
          admin.refreshPanel();  
        }
        );
      }
    },
    sortableInit: function() {
      $(".section-category .main-table tbody tr").hover( 
        function() {
          group = $(this).data('group');

          var trCount = $('.section-category .main-table tbody tr').length;
          for(i = 0; i < trCount; i++) {
            if($('.section-category .main-table tbody tr:eq('+i+')').hasClass(group)) {
              $('.section-category .main-table tbody tr:eq('+i+')').removeClass('disableSort');
            } else {
              $('.section-category .main-table tbody tr:eq('+i+')').addClass('disableSort');
            }
          }
        }
      );

      $(".section-category .main-table tbody").sortable({
        handle: '.mover',
        start: function(event, ui) {
          group = $(ui.item).data('group');

          var trCount = $('.section-category .main-table tbody tr').length;
          for(i = 0; i < trCount; i++) {
            if(!$('.section-category .main-table tbody tr:eq('+i+')').hasClass(group)) {
              $('.section-category .main-table tbody tr:eq('+i+')').addClass('disabled');
            } else {
              $('.section-category .main-table tbody tr:eq('+i+')').removeClass('disabled');
            }
          }
        },
        sort: function(e) {
          var Y = e.pageY; // положения по оси Y
          $('.ui-sortable-helper').offset({ top: (Y-10)});
        },
        items: 'tr:not(.disableSort)',
        helper: fixHelperCategory,
        stop: function() {
          category.saveSort();
        }
      }).disableSelection();
    },
    // сохранение порядка сортировки
    saveSort: function() {
      data = [];   

      // составление массива строк с индефикаторами, для отправки на сервер, для сохранения позиций
      $.each( $('.section-category .main-table tbody tr'), function() {
        data.push($(this).data('id'));
      });         

      admin.ajaxRequest({
        mguniqueurl: "action/saveSortableTable",
        data: data,
        type: 'category'
      },
      function (response) {
        admin.indication(response.status, response.msg);
        admin.refreshPanel();
      });
    },
    // скрывает спрятанные пункты
    hidePageRows: function() {
      if(category.openedCategoryAdmin == undefined) {
        category.openedCategoryAdmin = [];
      }

      for(var i = 0; i < category.openedCategoryAdmin.length; i++) {
        if(category.openedCategoryAdmin[i] != undefined) {
          if($('#toHide-'+category.openedCategoryAdmin[i]).html() != undefined) {
            if(category.clickedId.indexOf(category.openedCategoryAdmin[i]) == -1) {
              $('#toHide-'+category.openedCategoryAdmin[i]).trigger('click', ['isProgrammClick']);
              category.clickedId.push(category.openedCategoryAdmin[i]);
            }
          }
        }
      }
    },
  };
})();

var fixHelperCategory = function(e, ui) {
  trStyle = "color:#1585cf!important;background-color:#fff!important;";

  // берем id текущей строки
  var id = $(ui).data('id');
  // достаем уровень вложенности данной строки
  var level = $(ui).data('level');
  level++;

  // берем порядковый номер текущей строки
  // thisSortNumber = $(ui).data('sort');
  $('.section-category .main-table tbody tr').each(function(index) {
    if($(this).data('id') == id) {
      thisSortNumber = index;
      return false;
    }
  }); 

  // фикс скрола
  $('.section-category .table-wrapper').css('overflow', 'visible');

  // поиск ширины для жесткой записи, чтобы не разебывалось
  width = $('.section-category .main-table').width();
  width *= 0.9;

  uiq = '<div style="width:'+width+'px;position:fixed;"><table style="width:100%;"><tr style="'+trStyle+'">'+$(ui).html()+'</tr>';

  group = $(ui).data('group');

  var trCount = $('.section-category .main-table tbody tr').length;
  for(i = thisSortNumber+1; i < trCount; i++) {
    if(($('.section-category .main-table tbody tr:eq('+i+')').hasClass(group)) || (($('.section-category .main-table tbody tr:eq('+i+')').data('level') < level))) {
      break;
    } else {
      if(($('.section-category .main-table tbody tr:eq('+i+')').data('level') >= level)) {
        uiq += '<tr style="'+trStyle+'display:'+$('.section-category .main-table tbody tr:eq('+i+')').css('display')+'">'+$('.section-category .main-table tbody tr:eq('+i+')').html()+'</tr>';
        $('.section-category .main-table tbody tr:eq('+i+')').css('display','none');
      }
    }
  }

  uiq += '</table></div>';

  return uiq;
};