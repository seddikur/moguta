var protocol = '',
  sessionLifeTime = 0,
  currency = '';
// массив подключенных скриптов, для избежания дублей
var javascripts = [];

// главный модуль админки, управляет всем остальным, включает в себя ряд полезных функций используемых повсеместно.
var admin = (function () {

  return {
    SITE: "null", // домен сайта
    SECTION: "null", // страница по умолчанию
    WAIT_PROCESS: false, // процесс загрузки
    WAIT_DELAY: 300, // количество милисекунд, которое должно пройти между запросом и получением ответа чтобы показать лоадер
    CURENT_PLUG_TITLE: null, // название плагина, страница настроек которого открыта
    CURRENCY: "", // валюта
    CURRENCY_ISO: "", // валюта магазина
    PROTOCOL: "", // домен сайта
    AJAXCALLBACK: null, // стек функций отложенного вызова посе аяксовой подгрузки данных
    PULIC_MODE: false, // становится true когда включен режим редактирования на сайте
    searcharray: [], //массив найденных товаров в строке поиска
    DIR_FILEMANAGER: 'uploads',
    PRICE_FORMAT: '',
    PLUGGINS_SORT_MODE: '',
    SESSION_LIFE_TIME: 0,
    TIME_WITHOUT_USER: 0,
    SESSION_CHECK_INTERVAL: null,
    SCROLL_TABLE: null, // для скрола таблиц при наведении на серый блок скрола сбоку
    TABLE_TO_SCROLL: null,
    BLOCK_ENTITY: false,
    ARROWS_ENABLED: false,
    PICKR_STORAGE: [],
    INTRO_FLAG: "", //Флаг главной подсказки в шапке, при заходе первый раз в админку
    /**
     * Инициализация компонентов админки
     */
    init: function () {
      // Настройки из вёрстки D:
      this.SITE = mgBaseDir;
      this.CURRENCY = $.trim($("#currency").html()) || this.CURRENCY;
      this.CURRENCY_ISO = $.trim($("#currency-iso").html()) || this.CURRENCY_ISO;
      this.BLOCK_ENTITY = $.trim($("#block-entity").html()) || this.BLOCK_ENTITY;
      this.PRICE_FORMAT = $.trim($("#priceFormat").html()) || this.PRICE_FORMAT;
      this.PROTOCOL = $.trim($("#protocol").html()) || this.PROTOCOL;

      this.safariDetect();

      if ($('.admin-top-menu .exit-list .site-edit').hasClass('enabled')) {
        admin.resetAdminCurrency();
      }
      if (this.CURRENCY == undefined || this.CURRENCY == null || this.CURRENCY == '') {
        this.CURRENCY = this.CURRENCY_ISO;
      }
      if (!this.PROTOCOL) {
        this.PROTOCOL = protocol;
      }

      //Подключаем файл, в котором содержится обработка частых ошибок.
      includeJS(admin.SITE + '/mg-core/script/admin/errors.js');

      // блокируем стандартные горячие клавиши ctrl+s для сохранения модалок в админке
      document.onkeydown = this.ctrlKeyUpgrade;

      // Отключение :hover-состояний при прокрутке страницы для улучшения производительности
      var body = document.body,
        timer;

      window.addEventListener('scroll', function () {
        clearTimeout(timer);
        if (!body.classList.contains('disable-hover')) {
          body.classList.add('disable-hover');
        }

        timer = setTimeout(function () {
          body.classList.remove('disable-hover');
        }, 500);
      }, false);

      admin.hideWhiteArrowDown();

      //Кастомный скрипт для фиксации меню при скроле
      var $menu = $(".admin-top-menu");

      // обработчик скрола страницы и перемещения меню
      $(window).scroll(function () {
        if ($(this).scrollTop() > 70 && $menu.hasClass("default")) {
          $('.info-panel').css('height', '118px');
          $menu.removeClass("default").addClass("fixed");
        } else if ($(this).scrollTop() <= 70 && $menu.hasClass("fixed")) {
          $('.info-panel').css('height', '70px');
          $menu.removeClass("fixed").addClass("default");
        }
      });

      admin.fixedMenu($('#staticMenu').text());

      if (sessionLifeTime > 0 && window.sessionUpdateActive !== true) {
        window.sessionUpdateActive = true;
        setInterval(function () {
          admin.ajaxRequest({
            mguniqueurl: "action/updateSession",
          },
            function (response) {
            });
        }, (sessionLifeTime / 2 * 1000));
      }

      setTimeout(function () {
        if ($('.newNotification').length) {
          admin.openModal($('.newNotification:first'));
        }
      }, 1000);

      // для обновления базы
      // if($('#updateDb').val() == 'true') {
      setTimeout(function () {
        admin.updateDB();
      }, 1000);
      // }
      if (typeof activeTab === 'undefined') { activeTab = true; }
      setInterval(function () {
        if (typeof window.configPublic !== 'undefined' || admin.PULIC_MODE) { return 0; }
        // для того, чтобы обновление шапки работало, только если пользователь в админке
        $(window).blur(function () { activeTab = false; });
        $(window).focus(function () { activeTab = true; });
        if (!activeTab) return false;
        if (typeof Backup === 'undefined' || Backup.block === false) {
          $.ajax({
            url: mgBaseDir + '/mg-admin/?informerUpdate=true',
            method: 'GET',
            success: function (response) {
              $('.desktopInformers').html($(response).find('.desktopInformers').html());
              $('.mobileInformerCount').html($(response).find('.mobileInformerCount').html());
              $('.mobileInformers').html($(response).find('.mobileInformers').html());
            }
          });
        }
      }, 60 * 1000);

      //папка загрузки ckeditor'а в публичке в режиме редактирования
      if ($('.admin-top-menu .right-side .exit-list .site-edit').hasClass('enabled')) {
        setInterval(function () {
          if ($('.admin-top-menu .right-side .exit-list .site-edit').hasClass('enabled')) {
            var ckNames = [];

            if ($('#add-product-wrapper').is(":visible")) {
              ckNames = ['html_content', 'html_content-textarea'];
            }
            if ($('#add-category-modal').is(":visible")) {
              ckNames = ['html_content', 'html_content-seo'];
            }
            if ($('#add-page-modal').is(":visible")) {
              ckNames = ['html_content'];
            }

            for (var ckname in CKEDITOR.instances) {
              if ($.inArray(ckname, ckNames) != -1) {
                CKEDITOR.instances[ckname].config.filebrowserUploadUrl = admin.SITE + '/ajax?mguniqueurl=action/upload_tmp';
              }
            }
          }
        }, 1000);
      }

      // Инициализация обработчиков
      admin.initEvents();

      // Восстанавливаем  последний открытый раздел
      this.refreshPanel();

    },
    initEvents: function () {
      //тыкалка для меню разделов админки
      $('.mg-admin-body').on('click', '.mg-admin-mainmenu-item', function () {
        var section = $(this).data('section');
        var callback = false;
        admin.SECTION = section;
        $(this).parent().addClass('not-pointer');
        switch (section) {
          case 'catalog':
            includeJS(admin.SITE + '/mg-core/script/admin/catalog.js');
            callback = catalog.init;
            break;
          case 'category':
            includeJS(admin.SITE + '/mg-core/script/admin/category.js');
            callback = category.init;
            break;
          case 'page':
            includeJS(admin.SITE + '/mg-core/script/admin/page.js');
            callback = page.init;
            break;
          case 'orders':
            includeJS(admin.SITE + '/mg-core/script/admin/orders.js');
            callback = order.init;
            break;
          case 'users':
            includeJS(admin.SITE + '/mg-core/script/admin/users.js');
            callback = user.init;
            break;
          case 'plugins':
            includeJS(admin.SITE + '/mg-core/script/admin/plugins.js');
            callback = plugin.init;
            break;
          case 'settings':
            includeJS(admin.SITE + '/mg-core/script/admin/settings.js');
            callback = settings.init;
            break;
          case 'marketplace':
            cookie(admin.SECTION + "_getparam", '');
            includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
            callback = marketplaceModule.init;
            break;
          case 'statistic':
            includeJS(admin.SITE + '/mg-core/script/chart.js');
            includeJS(admin.SITE + '/mg-core/script/admin/statistic.js');
            callback = statistic.callBack;
            break;
          case 'saasinfo':
            includeJS(admin.SITE + '/mg-core/script/chart.js');
            includeJS(admin.SITE + '/mg-core/script/admin/saasinfo.js');
            callback = dashboard.callBack;
            break;
        }

        admin.show(section + ".php", "adminpage", cookie(section + "_getparam"), callback);
      });

      // сортировка таблиц по верстке
      $('.admin-center').on('click', 'table th.js-sortable', function (e) {
        $(this).closest('table').find('th.js-sortable').removeClass('asc desc');
        let dir = 'asc';
        if (admin.PLUGGINS_SORT_MODE) {
          admin.PLUGGINS_SORT_MODE = admin.PLUGGINS_SORT_MODE == 'asc' ? 'desc' : 'asc';
          dir = admin.sortTable($(this));
        }
        else {
          dir = admin.sortTable($(this));
          admin.PLUGGINS_SORT_MODE = dir;
        }
        $(this).addClass(dir);
      });

      ///////////////////// редактирование колонок таблиц /////////////////////
      $('.admin-center').on('click', '.open-col-config-modal', function () {
        var type = $(this).data('type');
        if (type) {
          admin.openModal('#' + type + '-col-config-modal');
          setTimeout(function () {
            $(
              "#" + type + "-col-config-modal .left-side:first ul:first," +
              "#" + type + "-col-config-modal .right-side:first ul:first"
            ).sortable({
              connectWith: ".colFieldsList",
              placeholder: 'jquery-ui-sorter-placeholder',
            }).disableSelection();
          }, 1);
        }
      });

      $('.admin-center').on('click', '.col-config-modal .save-button', function () {
        var type = $(this).closest('.col-config-modal').data('type');
        if (type) {
          var list = $(this).closest('.col-config-modal').find('.right-side ul:first');
          var activeColumns = [];
          list.find('li').each(function (index, element) {
            activeColumns.push($(this).data('id'));
          });
          admin.ajaxRequest({
            mguniqueurl: "action/saveAdminColumns",
            data: {
              type: type,
              activeColumns: activeColumns
            }
          },
            function (response) {
              admin.indication(response.status, response.msg);
              admin.closeModal("#" + type + "-col-config-modal");
              admin.refreshPanel();
            });
        }
      });

      $('.admin-center').on('click', '.col-config-modal .moveToInactive', function () {
        var container = $(this).closest('.col-config-modal').find('.left-side:first ul:first');
        $($(this).closest('li').detach()).appendTo(container);
      });

      $('.admin-center').on('click', '.col-config-modal .moveToActive', function () {
        var container = $(this).closest('.col-config-modal').find('.right-side:first ul:first');
        $($(this).closest('li').detach()).appendTo(container);
      });
      ///////////////////// редактирование колонок таблиц /////////////////////

      // вешаем класс на выбранный пункт меню
      $('.mg-admin-body').on('click', '.header-nav .mg-admin-mainmenu-item', function () {
        if (($("#admin-curr-check").data('db') != $("#admin-curr-check").data('eng')) || admin.CURRENCY_ISO != $("#admin-curr-check").data('db') || admin.CURRENCY_ISO != $("#admin-curr-check").data('eng')) {
          admin.resetAdminCurrency();
        }
        $('.header-nav .mg-admin-mainmenu-item').removeClass('active');
        $(this).addClass('active');
      });

      // меню разделов
      $('.mg-admin-body').on('click', '.nav-list.main-list li a', function () {
        if ($('.top-menu').hasClass('open')) {
          $('.menu-toggle').click();
        }
      });

      // Сохранение старых значений в попапах
      $('.mg-admin-body').on('focus', '.custom-popup input, .custom-popup select, .custom-popup textarea', function () {
        if (typeof $(this).data('cancelValue') === 'undefined' || $(this).data('cancelValue') === null) {
          $(this).data('cancelValue', $(this).val());
        }
      });

      $('.mg-admin-body').on('click', '.js-show-pass', function () {
        let input = $(this).closest('.js-show-pass-container').find('input')
        let attr = $(input).attr('type');
        if (attr == 'password') {
          $(input).attr('type', 'text');
        } else {
          $(input).attr('type', 'password');
        }
      });

      // Восстановление старых значений попапов при отмене
      $('.mg-admin-body').on('click', '.custom-popup .cancelPopup', function () {
        $(this).closest('.custom-popup').find('input, select, textarea').each(function () {
          if (typeof $(this).data('cancelValue') !== 'undefined' && $(this).data('cancelValue') !== null) {
            $(this).val($(this).data('cancelValue'));
          }
        });
        $(this).closest('.custom-popup').hide();
      });

      // универсальный закрыватель модалок на foundation ( для отмены или простого закрытия окна )
      $('.mg-admin-body').on('click', '.reveal-overlay .closeModal', function () {
        admin.closeModal($(this).parents('.reveal-overlay'), true);
      });

      //ездилка для аккордионов и фикс стрелок
      $('.mg-admin-body').on('click', '.accordion-title', function () {
        if (!$(this).closest('.accordion-item').hasClass('is-active') && $(this).closest('ul.accordion').data('multi-expand') === false) {
          $(this).closest('ul.accordion').find('.accordion-content').slideUp();
          $(this).closest('ul.accordion').find('.accordion-item').removeClass('is-active');
        }
        $(this).closest('.accordion-item').find('.accordion-content').slideToggle();
        $(this).closest('.accordion-item').toggleClass('is-active');
        admin.showSideArrow();
      });

      //Открываем аккордион в групповых настройках
      $('.mg-admin-body').on('click', '.accordion-title-group-prop', function () {
        $(this).parent().find('.accordion-content-group-prop').slideToggle();
        $(this).parent().toggleClass('is-active');
      })

      // Закрывалка напоминалки вверху админки
      $(document.body).on('click', '#timeInfo .close-timeinfo', function () {
        var expires = new Date();
        expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));//1 день
        document.cookie = 'timeinfo_closed=1;expires=' + expires.toUTCString() + "; path=/";
        $('#timeInfo').hide();
      });


      // обработчик для нажатий на навигацию страниц
      // вешается на тело страницы, и отрабатывает с любого раздела
      $('.admin-center').on('click', '.linkPage', function () {
        var pageId = admin.getIdByPrefixClass($(this), 'page');

        // если пагинация нажата внутри плагина, то указываем тип и не прибавляем расширение .php к названию плагина
        if (cookie("type") == 'plugin') {
          var tab = '';
          // если на странице присутствуют фильтры учитываем их в пагинации
          if (cookie("section")) {
            var pluginFilter = cookie("section").split('&');
            if (pluginFilter.length > 1 && pluginFilter[0] == admin.SECTION) {
              tab = pluginFilter[1];
            }
          }
          var request = $(tab + " form[name=filter]").formSerialize();
          var getparam = "page=" + pageId + "&" + request;
          var displayFilter = '';
          if ($(".filter-container").css('display') == 'block') {
            displayFilter = '&displayFilter=1';
          }
          cookie(admin.SECTION + "_getparam", getparam);
          admin.show(admin.SECTION, cookie("type"), "&pluginTitle=" + admin.CURENT_PLUG_TITLE + "&" + getparam + displayFilter, null, tab);
        } else {
          // если на странице присутствуют фильтры учитываем их в пагинации
          var request = $("form[name=filter]").formSerialize();
          var getparam = "page=" + pageId + "&" + request;
          var displayFilter = '';
          if ($(".filter-container").css('display') == 'block') {
            displayFilter = '&displayFilter=1';
          }
          cookie(admin.SECTION + "_getparam", getparam);
          var insideCat = '';
          if ($(".filter-container").find('input[name=insideCat]').length) {
            insideCat = "&insideCat=" + $(".filter-container").find('input[name=insideCat]').is(':checked');
          }

          var callback = '';
          switch (admin.SECTION) {
            case 'catalog':
              callback = catalog.init;
              break;
            case 'orders':
              callback = order.init;
              break;
            case 'users':
              callback = user.init;
              break;
          }
          admin.show(admin.SECTION + ".php", cookie("type"), getparam + displayFilter + insideCat, callback);
        }
      });

      // экспорт в CSV по фильтру
      $('.admin-center').on('click', '.filter-container .csvExport', function () {
        var section = cookie("section");
        var request = $("form[name=filter]:visible").formSerialize();
        var type = cookie("type");
        if (type == "adminpage") {
          data = admin.SITE + "/ajax?mguniqueurl=" + section + ".php&mguniquetype=" + type + "&" + request + "&csvExport=1";
        } else {
          data = admin.SITE + "/ajax?mguniqueurl=" + section + "&mguniquetype=" + type + "&" + request + "&csvExport=1";
        }
        window.open(data, '_blank');
      });

      $('.mg-admin-body').on('click', '.logout-button', function () {
        window.location = admin.SITE + "/enter?logout=1";
      });

      $('.mg-admin-body').on('click', '.js-show-setting-char', function() {
        cookie('setting-active-tab', '#tab-userField-settings');
        includeJS(admin.SITE + '/mg-core/script/admin/settings.js');
        callback = settings.init;
        admin.show("settings.php", "adminpage", cookie("settings_getparam"), callback);
    });


      // при смене значения чекбокса записываем в value, чтобы потом получать данные (актуально для всех чекбоксов на странице в админке)
      $('.mg-admin-body').on('click', 'input[type="checkbox"]', function () {
        // если чекбокс находится в админке то применяем к нему наше правило
        if (!admin.PULIC_MODE && !$(this).hasClass('mg-filter-prop-checkbox')) {
          $(this).val($(this).prop('checked'));
        }
      });

      // клик по панеле информера, и переход на страницу плагина или раздела
      $('.mg-admin-body').on('click', '.desktopInformers a:not(.not-use), .mobileInformers a:not(.not-use)', function () {
        // // если указан раздел то переходим в раздел
        if ($(this).hasClass('notPlugin')) {
          $('.mg-admin-mainmenu-item[data-section=' + $(this).data('section') + ']').click();
          return false;
        }
        // // если указан плагин то переходим в плагины
        if ($('.plugins-dropdown-menu .' + $(this).data('section')).length == 0) {
          $('.mg-admin-mainmenu-item[data-section=plugins]').click();
        }

        $('.plugins-dropdown-menu .' + $(this).data('section')).click();

        $('.cd-side-nav').removeClass('nav-is-visible');
        $('.cd-nav-trigger').removeClass('nav-is-visible');
      });

      // клик по сообщению о доступной новой версии
      $('.mg-admin-body').on('click', '#newVersion, #fakeKey', function () {
        $('.mg-admin-mainmenu-item[data-section=settings]').click();
        // после перехода выполняем два отложенных метода
        admin.AJAXCALLBACK = [
          { callback: 'settings.openSystemTab', param: null },
        ];
      });

      // клик по сообщению об отчистке временных файлов системы
      $('.mg-admin-body').on('click', '#tempDirSize a', function () {
        if (confirm(lang.CLEAR_TEMP_CONFIRM)) {
          admin.ajaxRequest({
            mguniqueurl: "action/logDataСlear",         
          },
          function(response) {
            admin.indication(response.status, response.msg);
            if(response.status == 'success') {
              $('#tempDirSize').remove();
            }
          });
        }
      });

      // автотранслит заголовка в URL. При клике, или табе, на поле URL, если оно пустое то будет автозаполненно транслитироированным заголовком
      $('.mg-admin-body').on('click, focus', 'input[name=url]', function () {
        if ($('input[name=url]').val() == '') {
          var text = $('input[name=title]').val();
          if (text) {
            text.replace('%', '-');
            text = admin.urlLit(text, 1);
            $(this).val(text);
          }
        }
      });

      // считаем количество символов в метатегах и подсвечиваем их при изменении инпута и при открытии аккордеона
      $('.mg-admin-body').on('blur keyup change', '.js-meta-data', function () {
        admin.countMeta($(this));
      });

      $('.mg-admin-body').on('click', '.js-auto-meta', function () {
        $('.js-meta-data:visible').each(function () {
          admin.countMeta($(this));
        });
      });

      // защита для ввода в числовое поле символов
      $('.mg-admin-body').on('keyup', '.numericProtection', function () {
        if (isNaN($(this).val()) || $(this).val() < 0) {
          $(this).val('1');
        }
      });

      // обработчик закрытия старых модалок
      $('.mg-admin-body').on('click', '.b-modal_close', function () {
        admin.closeModal($(this).closest('.b-modal'));
      });

      // обработчик закрытия окна uploader
      $('.mg-admin-body').on('click', '.uploader-modal_close', function () {
        admin.closeModal($(this).closest('.uploader-modal'));
      });

      // обработка клика по переключателю режима редактирования
      if ($('.js-admin-edit-site').length) {
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
      }

      // обработка клика по кнопке - сбросить кэш
      $(document.body).on('click', '.js-admin-clear-cash', function () {
        admin.ajaxRequest({
          mguniqueurl: "action/clearСache",
        },
          function (response) {
            location.reload();
          }
        );
      });

      // обработка клика по кнопке - завершить все сессии пользователей
      $(document.body).on('click', '.js-admin-clear-session', function () {
        admin.ajaxRequest({
          mguniqueurl: "action/clearSessionActive",
        },
          function (response) {
            location.reload();
          }
        );
      });

      // Применение сортировки таблицы (обязательно должна присутствовать форма фильтров)
      $('.admin-center').on('click', '.field-sorter', function () {
        if ($('.filter-container input[name=sorter]').length) {
          $('.filter-container input[name=sorter]').val($(this).data('field') + '|' + $(this).data('sort'));
        } else {
          var val = $(this).data('field') + '|' + $(this).data('sort');
          $('.filter-container select[name=sorter]').append('<option value="' + val + '">' + val + '</option>').val(val);
        }
        var request = $("form[name=filter]").formSerialize();
        var type = cookie("type");
        if (type == "adminpage") {
          switch (admin.SECTION) {
            case 'catalog':
              callback = catalog.init;
              break;
            case 'orders':
              callback = order.init;
              break;
            case 'users':
              callback = user.init;
              break;
          }
          admin.show(admin.SECTION + ".php", "adminpage", request, callback);
        } else {
          admin.show(admin.SECTION, type, request, admin.sliderPrice);
        }
        return false;
      });

      //кликалка в таблицах с шифтом (у лейбла рядом с чекбоксом должен быть класс shiftSelect)
      $('.admin-center').on('click', '.main-table label.shiftSelect', function (e) {
        var clickedRow = $(this).closest('tr');
        var clickedIndex = clickedRow.index();
        if (e.shiftKey) {
          document.getSelection().removeAllRanges();
          if (admin.lastShiftRow && admin.lastShiftIndex != clickedIndex) {
            var currTable = $(clickedRow).closest('table');
            var lastTable = $(admin.lastShiftRow).closest('table');

            if (currTable.is(lastTable)) {
              var min = Math.min(admin.lastShiftIndex, clickedIndex);
              var max = Math.max(admin.lastShiftIndex, clickedIndex);
              var state = $(this).parent().find('input[type=checkbox]').prop('checked');

              $(currTable).find('tbody tr').not('tr tr').each(function (index, element) {
                if (index >= min && index <= max) {
                  $(element).find('label.shiftSelect').parent().find('input[type=checkbox]').prop('checked', !state);
                  if (!state) {
                    $(element).addClass('selected');
                  } else {
                    $(element).removeClass('selected');
                  }
                }
              });
              $(this).parent().find('input[type=checkbox]').prop('checked', state);
            }
          }
        } else {
          admin.lastShiftRow = clickedRow;
          admin.lastShiftIndex = clickedIndex;
        }
      });

      //кликалка в мультиселектовом комбобоксе с шифтом
      $('.admin-center').on('click', '.SumoSelect .multiple ul.options li.opt', function (e) {
        var clickedRow = $(this);
        var clickedIndex = clickedRow.index();
        if (e.shiftKey) {
          document.getSelection().removeAllRanges();
          if (admin.lastShiftRow && admin.lastShiftIndex != clickedIndex) {
            var currSelect = $(clickedRow).closest('ul.options');
            var lastSelect = $(admin.lastShiftRow).closest('ul.options');

            if (currSelect.is(lastSelect)) {
              var min = Math.min(admin.lastShiftIndex, clickedIndex);
              var max = Math.max(admin.lastShiftIndex, clickedIndex);
              var state = $(this).hasClass('selected');

              $(currSelect).find('li.opt').each(function (index, element) {
                if (index >= min && index <= max) {
                  if (
                    (state && !$(element).hasClass('selected')) ||
                    (!state && $(element).hasClass('selected'))
                  ) {
                    $(element).click();
                  }
                }
              });
            }
          }
        } else {
          admin.lastShiftRow = clickedRow;
          admin.lastShiftIndex = clickedIndex;
        }
      });

      // кнопка расширения модалки
      $('.mg-admin-body').on('click', '.reveal .expandModal', function () {
        var modal = $(this).closest('.reveal');
        var icon = $(this).find('i');
        if (modal.hasClass('fullscreen')) {
          modal.removeClass('fullscreen');
          icon.addClass('fa-expand').removeClass('fa-compress');
        } else {
          modal.addClass('fullscreen');
          icon.removeClass('fa-expand').addClass('fa-compress');
        }
      });

      // Фиксируем футер главной таблицы, если выбрана хотя бы одна строка
      $('.mg-admin-body').on('change', '.main-table [name=product-check],.main-table .checkbox [type="checkbox"]', function () {
        let footer = $('.widget-footer');
        let fixClass = 'widget-footer--fixed';
        if ($('.main-table [type="checkbox"]:checked').length > 0) {
          footer.addClass(fixClass);
        } else {
          footer.removeClass(fixClass);
        }
      });

      $('.mg-admin-body').on('click', '.js-scroll-anchor', function (e) {
        admin.scrollToElem(e);
      });

      // применение класса selected для строки, которой ставят галочку выделения
      $('.admin-center').on('click', '.main-table .checkbox label', function () {
        var tr = $(this).closest('tr');
        var checkbox = tr.find('td:first input[type=checkbox]:first');
        if (checkbox.length) {
          if (checkbox.prop('checked')) {
            tr.addClass('selected');
          } else {
            tr.removeClass('selected');
          }
        }
      });

      // Клик по активным плагинам из выпадающего меню, открывает страницу настроек
      $('.mg-admin-body').on('click', '.plugins-dropdown-menu li a', function () {
        var pluginName = $(this).attr('class');
        if (pluginName != 'all-plugins-settings') {
          var pluginTitle = $(this).text();
          admin.show(pluginName, "plugin", '&pluginTitle=' + pluginTitle, function () {
            admin.CURENT_PLUG_TITLE = pluginTitle;
          });
        } else {
          $('.mg-admin-mainmenu-item[data-section=plugins]').click();
        }

        $('.admin-top-menu-list > li > a').removeClass('active-item');
        $('.mg-admin-mainmenu-item[data-section=plugins]').addClass('active-item');
        $(".plugins-menu-wrapper").hide();
      });

      $('.mg-admin-body').on('click', '.show-global-hints', function () {
        newbieWay.showHits('main');
        introJs().start();
      });

      $('.admin-bar').on('click', '.admin-bar__saas-elem .js-adminbar-link.admin-bar-link--enter, .inform-progress', function (e) {
        e.preventDefault();
        cookie("section", "saasinfo");
        cookie("type", "adminpage");
        window.location.href = this.href;
      });

    },
    safariDetect: function () {
      var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
      if (isSafari) document.body.classList.add('safari-browser');
    },
    // Открытие инструкции плагина
    openPluginReadme: function (obj) {
      $.ajax({
        url: obj.data('src'),
      }).done(function (responce) {
        responce = responce.split('#SITE#').join(mgBaseDir);
        responce = responce.split('<?php').join('&lt;?php');
        responce = responce.split('?>').join('?&gt;');
        $('#plugin-read-modal .reveal-body').html(responce);
        $('#plugin-read-modal .pluginName').html(obj.data('title'));
        admin.openModal('#plugin-read-modal');
      });
    },
    // проверяет сущность на блокировку или продлевает блокировку для данного пользователя
    setAndCheckBlock: function (modalSelector, table) {
      if (admin.BLOCK_ENTITY != 'true') return false;
      // if(!$('#add-user-modal').is(':visible')) {
      if (!$(modalSelector).is(':visible')) {
        admin.blockTime = -1;
        return false;
      }
      id = $(modalSelector + ' .save-button').attr('id');
      if (!id) return false;
      admin.ajaxRequest({
        mguniqueurl: "action/setAndCheckBlock",
        data: {
          // table:'user',
          table: table,
          id: id
        }
      },
        function (response) {
          if (response.data == true) {
            $(modalSelector + ' .save-button').show();
            $('.blockPageUser').detach();
          } else {
            $('.blockPageUser').detach();
            $(modalSelector + ' .save-button').hide()
              .parent().append('<span class="blockPageUser badge alert fl-right">' + lang.ENTITY_BLOCKED_BY_USER + ' ' + response.data + '</span>');
          }
          admin.blockTime = 25;
        });
    },
    closeNotificationModal: function (obj) {
      var id = obj.data('id');
      obj.parents('.newNotification').removeClass('newNotification');

      admin.ajaxRequest({
        mguniqueurl: "action/confirmNotification",
        id: id
      },
        function (response) {
          obj.parents('.reveal-overlay').css('display', 'none');
          $('body').css('overflow', 'auto');
          $('body').removeClass('modal--opened');
          setTimeout(function () {
            if ($('.newNotification').length) {
              admin.openModal($('.newNotification:first'));
            }
          }, 500);
        });
    },
    unlockEntity: function (modalSelector, table) {
      if (admin.BLOCK_ENTITY != 'true') return false;
      admin.blockTime = -1;
      id = $(modalSelector + ' .save-button').attr('id');
      if (!id) return false;
      admin.ajaxRequest({
        mguniqueurl: "action/unlockEntity",
        data: {
          // table:'user',
          table: table,
          id: id
        }
      },
        function (response) {

        });
    },

    deleteUnBindProp: function () {
      $('.updateDbLoader, .updateDbLoaderPlace').hide();
      $.ajax({
        type: "POST",
        url: mgBaseDir + "/ajax",
        data: {
          mguniqueurl: "action/deleteUnBindProp"
        },
        cache: false,
        async: true,
        dataType: "json",
        success: function (response) {
          admin.indication(response.status, response.msg);
        }
      });
    },

    updateDB: function (data) {
      if (typeof Backup === 'undefined' || Backup.block === false) {
        if ($('.js-admin-edit-site').attr('aria-pressed') == 'true') {
          $.ajax({
            type: "POST",
            url: mgBaseDir + "/ajax",
            data: {
              mguniqueurl: "action/updateDB",
              data: data
            },
            cache: false,
            async: true,
            dataType: "json",
            success: function (response) {
              defaultHeight = 8;
              lineHeight = 22;
              data = response.data;
              if (data.process) {
                setTimeout(function () {
                  admin.updateDB(data);
                }, 2000);
              }
              // console.log(data);
              if (data.removeMessage) {
                $('.updateDbLoader, .updateDbLoaderPlace').hide();
              } else {
                $('.updateDbLoader').html(data.message);
                $('.updateDbLoader, .updateDbLoaderPlace').show().css('height', +defaultHeight + +lineHeight * +data.line + 'px');
              }
            }
          });
        }
      }
    },
    // функционал для работы боковых стрелок скролла для больших таблиц
    showSideArrow: function () {
      $('.table-wrapper').each(function () {
        //Если таблица шире врапера
        if ($(this).width() <= $(this).find('.table-with-arrow:visible').width()) {
          if ($(this).scrollLeft() === 0) {
            $(this).find('.table-arrow-left').hide();
          } else {
            $(this).find('.table-arrow-left').show();
          }
          if ($(this).width() - $(this).find('.table-with-arrow:visible').width() + $(this).scrollLeft() >= -1) {
            $(this).find('.table-arrow-right').hide();
          } else {
            $(this).find('.table-arrow-right').show();
          }
          //Добавляем оверфло
          $(this).removeClass('table-wrapper--overflow_visible');
          $(this).addClass('table-wrapper--overflow_auto');
          //Запоминаем, что стрелки показаны
          admin.ARROWS_ENABLED = true;
        } else {
          $(this).find('.table-arrow-left').hide();
          $(this).find('.table-arrow-right').hide();
          //Убираем оверфло
          $(this).removeClass('table-wrapper--overflow_auto');
          $(this).addClass('table-wrapper--overflow_visible');
          //Запоминаем, что стрелки убраны
          admin.ARROWS_ENABLED = false;
        }
      });
    },
    scrollEventInit: function () {
      $('.table-wrapper').scroll(function () {
        admin.showSideArrow();
      });

      // для скролла вправо
      $('.table-arrow-right').hover(function () {
        admin.TABLE_TO_SCROLL = $(this).parents('.table-wrapper');
        clearInterval(admin.SCROLL_TABLE);
        admin.SCROLL_TABLE = setInterval(function () {
          admin.scrollTableRight();
        }, 20);
      }, function () {
        clearInterval(admin.SCROLL_TABLE);
      });
      // для скролла влево
      $('.table-arrow-left').hover(function () {
        admin.TABLE_TO_SCROLL = $(this).parents('.table-wrapper');
        clearInterval(admin.SCROLL_TABLE);
        admin.SCROLL_TABLE = setInterval(function () {
          admin.scrollTableLeft();
        }, 20);
      }, function () {
        clearInterval(admin.SCROLL_TABLE);
      });
    },

    scrollTableRight: function () {
      oldPos = admin.TABLE_TO_SCROLL.scrollLeft()
      admin.TABLE_TO_SCROLL.scrollLeft(admin.TABLE_TO_SCROLL.scrollLeft() + 10);
      newPos = admin.TABLE_TO_SCROLL.scrollLeft()
      if (oldPos == newPos) {
        clearInterval(admin.SCROLL_TABLE);
      }
    },

    scrollTableLeft: function () {
      oldPos = admin.TABLE_TO_SCROLL.scrollLeft()
      admin.TABLE_TO_SCROLL.scrollLeft(admin.TABLE_TO_SCROLL.scrollLeft() - 10);
      newPos = admin.TABLE_TO_SCROLL.scrollLeft()
      if (oldPos == newPos) {
        clearInterval(admin.SCROLL_TABLE);
      }
    },

    addScrollArrow: function () {
      $('.table-wrapper').each(function () {
        if ($(this).parents('.reveal-overlay').html() == undefined) {
          // if($(this).find('.table-arrow-left').html() == undefined) {
          $(this).append('<div class="table-arrow-left table-arrow" style="display:none;"><i class="fa fa-chevron-left" aria-hidden="true"></i></div>\
                            <div class="table-arrow-right table-arrow" style="display:none;"><i class="fa fa-chevron-right" aria-hidden="true"></i></div>');
          // }
          admin.initTableArrows()
        }
      });

      $(window).resize(function () {
        admin.showSideArrow();
      });
    },

    scrollInit: function () {
      $('.table-arrow-left, .table-arrow-right').detach();
      admin.addScrollArrow();
      admin.showSideArrow();
      admin.scrollEventInit();
    },
    // функционал для работы боковых стрелок скролла для больших таблиц конец
    resetAdminCurrency: function () {
      $.ajax({
        type: "POST",
        url: mgBaseDir + "/ajax",
        data: {
          mguniqueurl: "action/resetAdminCurrency"
        },
        cache: false,
        async: false,
        dataType: "json",
        success: function (response) {
          admin.CURRENCY = response.data.currency;
          admin.CURRENCY_ISO = response.data.currencyISO;
        }
      });
    },

    getSettingFromDB: function (setting) {
      let result = null;
      $.ajax({
        type: "POST",
        url: mgBaseDir + "/ajax",
        data: {
          mguniqueurl: 'action/getSetting',
          setting: setting,
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (response) {
          result = response.data[setting];
        }
      });
      return result;
    },
    /**
     * фиксация меню при скроле
     */
    fixedMenu: function (staticMenu) {
      if (staticMenu != 'false') {
        $('.admin-top-menu').addClass('default');
      } else {
        $('.admin-top-menu').removeClass("default").removeClass("fixed");
      }
    },
    /**
     * При обновлении страницы, восстанавливает открытый раздел
     */
    refreshPanel: function () {
      // открыть последний активный раздел, считывается с куков
      if (cookie("section")) {
        var sections = cookie("section") ? cookie("section").split('&') : cookie("section");
        this.SECTION = sections[0];
      }
      this.SECTION = cookie("section");

      // если куки пусты то открывается первый раздел - заказы
      if (!this.SECTION) {
        this.SECTION = "orders";
      }


      // особый выбор, когда необходимо в разделе открыть еще подраздел, как в плагинах и настройках
      var paramChoose = false;

      if (cookie("type") == 'plugin') {
        $('.plugins-dropdown-menu a[class=' + this.SECTION + ']').click();

        paramChoose = true;
      }

      if (!paramChoose) {
        //debugger;
        $('#' + this.SECTION + ':first').click();
        $('.mg-admin-mainmenu-item[data-section=' + this.SECTION + ']:first').click();
      }

      // делаем пункт выбранным
      $('#' + this.SECTION).addClass('active');
      $('.mg-admin-mainmenu-item[data-section=' + this.SECTION + ']').addClass('active');
    },

    /**
     * Индикатор сообщений
     * Функция выводит информацию об успешности или ошибки
     * различных действия администратора в админке.
     */
    indication: function (status, text) {

      $('.message-error').remove();
      $('.message-succes').remove();
      var object = "";
      switch (status) {
        case 'success':
          {
            $('body').append('<div class="message-succes"></div>');
            object = $('.message-succes');
            break;
          }
        case 'error':
          {
            $('body').append('<div class="message-error"></div>');
            object = $('.message-error');
            text = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + text;
            break;
          }
        default:
          {
            $('body').append('<div class="message-error"></div>');
            object = $('.message-error');
            text = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + text;
            break;
          }
      }

      object.addClass("up");
      object.html(text);
      setTimeout(function () {
        object.remove();
      }, object.hasClass('message-error') ? 6000 : 3000);
    },
    /**
     * Обертка для всех аякс запросов админки
     * необходимо для оптимизации вывода процесса загрузки
     * и унификации всех аякс вызовов
     */
    ajaxRequest: function (data, callBack, loader, dataType, noAlign) {

      if (!dataType)
        dataType = 'json';
      if (!loader)
        loader = $('.mailLoader');

      $.ajax({
        type: "POST",
        url: "ajax",
        data: data,
        cache: false,
        dataType: dataType,
        success: callBack,
        beforeSend: function () {
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
        complete: function () {

          // завершился процесс
          admin.WAIT_PROCESS = false;
          //прячем лоадер если он успел появиться
          admin.waiting(false);

          if ($('[data-tooltip]').length) {
            if ($('.tooltip').length != 0) {
              $('.tooltip').remove();
            }

            admin.foundationInit();
          }

          // инициализация стрелок скролла
          admin.scrollInit();

          loader.show();
          $('.view-action').remove();

          if ($('.b-modal').length > 0 && !noAlign) {
            admin.centerPosition($('.b-modal'));
          }

          // выполнение стека отложенных функций после AJAX вызова
          if (admin.AJAXCALLBACK) {
            //debugger;
            var tmpAJAXCALLBACK = admin.AJAXCALLBACK;
            admin.AJAXCALLBACK = null;
            tmpAJAXCALLBACK.forEach(function (element, index, arr) {
              eval(element.callback).apply(this, element.param);
            });
          }
        },
        error: function (request, status, error) {
          if (error == 'Internal Server Access denied') {
            location.reload();
            return false;
          }

          if ($('.session-info').html()) {
            sessInfoText = "<div class='help-text'>" + lang.USER_NOT_ACTIVE + " "
              + "<strong>" + Math.round(admin.SESSION_LIFE_TIME / 60) + "</strong> " + lang.MINUTES + " <br />"
              + lang.SESSION_CLOSED + "</div>"
              + "<form method='POST' action='" + admin.SITE + "/enter'>"
              + "<ul class='form-list'><li><span>" + lang.EMAIL + ":</span>"
              + "<input type='text' name='email'></li>"
              + "<li><span>" + lang.PASS + ":</span>"
              + "<input type='password' name='pass'></li></ul>"
              + "<button type='submit' class='default-btn'>" + lang.LOGIN + "</button></form>";
            $('.session-info').html(sessInfoText);
            $('.session-info').removeClass('alert');
            clearInterval(admin.SESSION_CHECK_INTERVAL);
            return false;
          }

          errors.showErrorBlock(request.responseText);
        }
      });

    },

    downimg: function () {
      includeJS(mgBaseDir + '/mg-core/script/html2canvas.js');
      html2canvas($('body'), {
        onrendered: function (canvas) {
          var img = canvas.toDataURL('image/png');
          admin.ajaxRequest({
            mguniqueurl: "action/sendBugReport",
            screen: img,
            text: $('.error-box .text-error').text()
          },
            function (response) {
              $('.error-box').remove();
              alert(lang.ADMIN_LOCALE_2);
              window.location.reload();

            });

        }
      });
    },

    /**
     * Открывает выбранный раздел админки
     * url - задает контролер, который будет обрабатывать запрос
     * type - тип (adminpage|plugin) разделяющий запросы по логике обработки движком. Запросы со страниц плагинов обрабатываются иначе.
     * request - сериализованные данные с форм использующихся в плагинах
     * tab - класс открытой вкладки, если вызов из плагина в котором две таблицы с фильтрами
     */
    show: function (url, type, request, callback, tab) {
      for (var ckname in CKEDITOR.instances) {
        CKEDITOR.instances[ckname].destroy(true);
      }
      // Устанавливаем в куки название открываемого раздела
      var sect = url.split('.');
      if (tab) {
        sect[0] += '&' + tab;
      }
      cookie("section", sect[0]);
      cookie("type", type);
      cookie(admin.SECTION + "_getparam", request);
      // подготвка параметров для отправки
      var data = "mguniqueurl=" + url + "&mguniquetype=" + type + (typeof request === 'undefined' ? '' : "&" + request);

      admin.ajaxRequest(
        data,
        function (data) {
          //debugger;
          //вывод полученной верстки страниы
          // if(admin.echoOff == true) {
          //   admin.echoOff = false;
          //   window.location.href = admin.SITE + "/csv.csv";
          //   return true;
          // }
          $(".admin-center .data").html(data);
          //выполнение отложенной функции после открытия страницы
          if (callback) {
            callback.call();
          }
          if ($('.not-pointer')) {
            $('.not-pointer').removeClass('not-pointer');
          }

          admin.foundationInit();

          // нужно для установки контента формы в нужное место, если есть с этим проблемы
          $('.move-form').each(function () {
            $('#' + $(this).data('target')).html($(this).html());
            $(this).detach();
            $('#' + $(this).data('target')).removeAttr('id');
          });

          admin.initCustom();


          if ($('.admin-center .data').hasClass('debugging-mode')) {
            $('.updateAccordion .accordion-title').click();
            $('.debugAccordion .accordion-title').click();
            $('.admin-center .data').removeClass('debugging-mode')
          };


          if ('plugin' == type) {
            admin.SECTION = url;

            //Добавляем для пользовательских форм (они же формы плагинов) отличительные атрибуты
            $("form").each(function () {

              // если у формы плагина стоит атрибут noengine = true,
              // то такая форма не будет обработана движком,
              // а произведет обычную отправку данных (необходимо для плагинов)
              if (!$(this).attr('noengine')) {
                $(this).attr("plugin", url);
                $(this).attr("ajaxForm", 'true');
              }

            });
            // сбор данных с формы и сериализация их в строку
            $(".mg-admin-body form[ajaxForm=true]").submit(function () {
              var request = $(this).formSerialize();
              admin.show(url, type, request, null, tab);
              return false;
            });
          }

          //Показ подсказок при первой загрузке
          if (admin.INTRO_FLAG != "true") {
            admin.ajaxRequest({
              mguniqueurl: "action/getHitsFlag",
            },
              function (response) {
                if (response['data'] != 'true') {
                  introJs().exit();
                  newbieWay.showHits('main');
                  introJs().start();
                }
                admin.INTRO_FLAG = "true";
              });
          }

        },
        false,
        "html"
      );

    },
    /**
     * Чистит куки хранящие в себе гет параметры для раздела
     */
    clearGetParam: function (run) {
      cookie(admin.SECTION + "_getparam", null);
    },
    /**
     * Если спустя секунду данные не были получены, то выводим лоадер.
     */
    waiting: function (run) {
      var cont = $(".view-action");
      run ? cont.show() : cont.hide();
    },
    // Включение подсказок, добавление перетягивания всплывающих окон, включение комбобоксов
    initCustom: function () {

      admin.initDraggable();
      admin.initComboBoxes();
      admin.initTableArrows();
    },
    //Стрелки скролла таблиц
    initTableArrows: function () {

      $(window).scroll(function () {
        if (admin.ARROWS_ENABLED) {
          scrolling();
        }
      });

      $(window).resize(function () {
        scrolling();
      });

      scrolling();

      function scrolling() {
        if ($('.table-with-arrow:visible tbody tr:first-child').length > 0) {
          $('.table-arrow').addClass('table-arrow--fixed')
          var windowHeight = $(window).height();
          var scrollTop = $(window).scrollTop();
          var offsetTop = $('.table-with-arrow:visible tbody tr:first-child').offset().top;
          var offsetHeight = $('.table-with-arrow:visible tbody tr:first-child')[0].offsetHeight;
          var offsetWidth = $('.table-with-arrow:visible tbody tr:first-child')[0].offsetWidth;


          var top = offsetTop - scrollTop;
          top = top < offsetTop - 100 ? '50%' : top;

          var bottom = (scrollTop + windowHeight) - (offsetHeight + offsetTop);
          bottom = bottom < 0 ? 0 : bottom;

          $('.table-arrow').css('top', top);
          $('.table-arrow').css('bottom', bottom);
        }
      }
    },

    isMobile: function () {
      var nativeOnDevice = ["Android", "BlackBerry", "iPhone", "iPad", "iPod", "Opera Mini", "IEMobile", "Silk"];
      for (var e = navigator.userAgent || navigator.vendor || window.opera, t = 0; t < nativeOnDevice.length; t++)
        if (e.toString().toLowerCase().indexOf(nativeOnDevice[t].toLowerCase()) > 0) return nativeOnDevice[t];
      return !1;
    },
    // инициализация селектов с поиском
    initComboBoxes: function () {
      if (!admin.isMobile()) {
        setTimeout(function () {
          try {
            let catsList = $('select.js-comboBox option');
            if (catsList) {
              catsList.each(function (index2, element2) {
                let oldValue = $(this).text();
                $(this).text(admin.htmlspecialchars(oldValue).replaceAll('&quot;', '"'));
              });
            }
            $('select.js-comboBox:not(.SumoUnder)').each(function () {
              $(this).SumoSelect({
                okCancelInMulti: true,
                clearAll: true,
                selectAll: true,
                search: true,
                searchText: lang.SUMOSELECT_SEARCH,
                noMatch: lang.SUMOSELECT_NOMATCH,
                up: ((typeof $(this).attr('data-up') != "undefined") ? true : false)
              });
            });
          } catch (err) {
            console.log(err);
          }
        }, 0);
        setTimeout(function () {
          $('.SumoSelect').each(function (index, element) {
            var originalSelect = $(this).find('select.js-comboBox:first');
            if (originalSelect) {
              $(this).css('marginBottom', originalSelect.css('marginBottom')).css('width', originalSelect.data('width'));
            }
            originalSelect.hide();
          });
        }, 1);
      }
    },
    reloadComboBoxes: function () {
      $('.SumoSelect').each(function (index, element) {
        const select = $(this).find('select.js-comboBox:first')[0];
        if (!select) return;
        select.sumo.unload();
      });
      admin.initComboBoxes();
    },
    tooltipEOL: function () {
      $('.tooltop--with-line-break').each(function (index, element) {
        $(this).attr('tooltip', $(this).attr('tooltip').replace(/\[EOL\]/g, '\u000D\u000A'));
      });
    },
    /**
     * инициализация всплывающих подсказок, для всех  дом элементов,
     * у которых есть класс tool-tip-bottom, tool-tip-top, tool-tip-right, tool-tip-left
     */
    initToolTip: function () { },
    /**
     * Инициализация тягабельности
     */
    initDraggable: function () {
      $(".uploader-modal").draggable({ handle: ".widget-table-title" });
    },
    /**
     *  @deprecated
     *  Получает id по префиксу класса.
     *  Например есть такой элемент:
     *  <a class='linkPage navButton page_501 link_2' href='#' >
     *  Задача: получить число 501 (идентификатор страницы)
     *  Вызова данной функции getIdByPrefixClass('.linkPage', 'page')
     *  вернет число 501
     */
    getIdByPrefixClass: function (obj, prefix) {
      var result = null;
      var classList = obj.attr('class').split(/\s+/);
      var reg = new RegExp(prefix + '_(.*)');
      $.each(classList, function (index, item) {
        var id = item.match(reg);
        if (id !== null)
          result = id[1];
      });
      return result;
    },
    /**
     * Скрывает белую стрелку в пункте плагинов, если нет активных плагинов с настройками
     */
    hideWhiteArrowDown: function (obj, prefix) {
      if ($('.plugins-dropdown-menu li').length == 1) {
        $('.plugins-icon').parents('li').find('.white-arrow-down').hide();
        $(".plugins-menu-wrapper").hide();
      }
    },
    /**
     * Транслитирирует строку
     */
    urlLit: function (string, lower) {
      var dictionary = { 'А': 'a', 'Б': 'b', 'В': 'v', 'Г': 'g', 'Д': 'd', 'Е': 'e', 'Ё': 'yo', 'Ж': 'j', 'З': 'z', 'И': 'i', 'Й': 'y', 'К': 'k', 'Л': 'l', 'М': 'm', 'Н': 'n', 'О': 'o', 'П': 'p', 'Р': 'r', 'С': 's', 'Т': 't', 'У': 'u', 'Ф': 'f', 'Х': 'h', 'Ц': 'ts', 'Ч': 'ch', 'Ш': 'sh', 'Щ': 'sch', 'Ъ': '', 'Ы': 'y', 'Ь': '', 'Э': 'e', 'Ю': 'yu', 'Я': 'ya', 'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'j', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya', '1': '1', '2': '2', '3': '3', '4': '4', '5': '5', '6': '6', '7': '7', '8': '8', '9': '9', '0': '0', 'І': 'i', 'Ї': 'i', 'Є': 'e', 'Ґ': 'g', 'і': 'i', 'ї': 'i', 'є': 'e', 'ґ': 'g' };
      // старый вариант
      //var dictionary = {'а':'a', 'б':'b', 'в':'v', 'г':'g', 'д':'d', 'е':'e', 'ж':'g', 'з':'z', 'и':'i', 'й':'y', 'к':'k', 'л':'l', 'м':'m', 'н':'n', 'о':'o', 'п':'p', 'р':'r', 'с':'s', 'т':'t', 'у':'u', 'ф':'f', 'ы':'i', 'э':'e', 'А':'A', 'Б':'B', 'В':'V', 'Г':'G', 'Д':'D', 'Е':'E', 'Ж':'G', 'З':'Z', 'И':'I', 'Й':'Y', 'К':'K', 'Л':'L', 'М':'M', 'Н':'N', 'О':'O', 'П':'P', 'Р':'R', 'С':'S', 'Т':'T', 'У':'U', 'Ф':'F', 'Ы':'I', 'Э':'E', 'ё':'yo', 'х':'h', 'ц':'ts', 'ч':'ch', 'ш':'sh', 'щ':'shch', 'ъ':'', 'ь':'', 'ю':'yu', 'я':'ya', 'Ё':'YO', 'Х':'H', 'Ц':'TS', 'Ч':'CH', 'Ш':'SH', 'Щ':'SHCH', 'Ъ':'', 'Ь':'',	'Ю':'YU', 'Я':'YA','і':'i', 'ї':'i', 'є':'e', 'ґ':'g', 'І':'i', 'Ї':'i', 'Є':'e', 'Ґ':'g' };
      var result = string.replace(/[\s\S]/g, function (x) {
        if (dictionary.hasOwnProperty(x))
          return dictionary[x];
        return x;
      });
      result = result.replace(/\W/g, '-').replace(/[-]{2,}/gim, '-').replace(/^\-+/g, '').replace(/\-+$/g, '');
      if (lower) {
        result = result.toLowerCase();
      }
      return result;
    },
    /*
     * альтернатива htmlspecialchars
     */
    htmlspecialchars: function (text) {
      if (text) {
        return text
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;");
      }
      return text;
    },
    /**
     * альтернатива htmlspecialchars_decode
     */
    htmlspecialchars_decode: function (text) {
      if (text) {
        return text
          .replace(/&amp;/g, "&")
          .replace(/&lt;/g, "<")
          .replace(/&gt;/g, ">")
          .replace(/&quot;/g, "\"")
          .replace(/&#039;/g, "\'")
          .replace(/&#91;/g, "[")
          .replace(/&#93;/g, "]");
      }
      return text;
    },
    /**
     * Позиционирует элемент по центру окна браузера
     */
    centerPosition: function (object) {
      object.css('position', 'absolute');
      var top = ($(window).height() - object.height()) / 2;
      if (top < 0) {
        top = 20;
      }
      object.css('left', ($(document).width() - object.width()) / 2 + 'px');
      object.css('top', top + (document.body.scrollTop || document.documentElement.scrollTop) + 'px');
    },
    /**
     * Открывает модальное окно
     */
    openModal: function (object) {
      if (!$('.reveal-overlay:visible').length) {
        var offset = document.documentElement.scrollTop;
        var BODY = document.querySelector('.mg-admin-body');
        if ($('.js-admin-edit-site.active').length > 0) {
          BODY = document.body;
        }
        if (BODY) {
          if (!BODY.classList.contains('section-plugin-settings')) {
           
            BODY.style.top = (offset * -1) + 'px';
          }
          BODY.classList.add('modal--opened');
        }
      }
      if (typeof TemplateSettings == "object") {
        TemplateSettings.blockSidebar = true;
        $('.js-design-popup').addClass('template-design_hidden');
        $(".js-design-popup-close").removeClass('template-design__close--able');
      }
      $(object).closest('.reveal-overlay').css('display', 'block');
      $(object).css('display', 'block');
      $(object).find('.accordion-content').hide();
      $(object).find('.accordion-item').removeClass('is-active');
      // $('body').css('overflow','hidden');
      // admin.hideScroll();
      setTimeout(function () {
        $(object).find('.custom-popup input, .custom-popup textarea, .custom-popup select').find('input').data('cancelValue', null);
      }, 2);
      // для отрубания стилей шаблона в публичке
      $('link').each(function () {
        if ($(this).attr('href').indexOf('mg-templates') != -1 && admin.PULIC_MODE != false) {
          $(this).prop('disabled', true);
        }
      });
    },

    /**
     * Закрывает модальное окно
     * object - объект модального окна
     * publicReload - флаг для перезагрузки страницы после сохранения сущности в режиме редактирования из публички
     */
    closeModal: function (object, publicReload = false) {
      $(object).closest('.reveal-overlay').css('display', 'none');
      $(object).css('display', 'none');
      if ($(object).find('.reveal').attr('data-refresh') == 'true') {
        admin.refreshPanel();
      }
      // отображение блока настроек шаблона
      if (typeof TemplateSettings == "object") {
        TemplateSettings.blockSidebar = false;
        if ($('div').hasClass('section-configeditor') && TemplateSettings.sidebarActive) {
          $('.js-design-popup').removeClass('template-design_hidden');
          $(".js-design-popup-close").addClass('template-design__close--able');
        }
      }

      if (!$('.reveal-overlay:visible').length) {
        var offset = parseInt(document.body.style.top, 10);
        document.body.style.top = null;
        document.body.classList.remove('modal--opened');
        // отображение блока настроек шаблона
        if ($('div').hasClass('section-configeditor')) {
          $('.js-design-popup').removeClass('template-design_hidden');
          $(".js-design-popup-close").addClass('template-design__close--able');
        }
        if (offset) {
          document.documentElement.scrollTop = -offset;
        }
        // уделание шлака после ../elfinder/js/elfinder.min.js
        $("audio").attr("style", "display:none").each(function (i, elem) {
          if ($(elem).css("display") == "none" && $(elem).text() == "") {
            $(elem).remove();
          }
        });
      }

      // для возврата стилей шаблона в публичке
      $('link').each(function () {
        if ($(this).attr('href').indexOf('mg-templates') != -1) {
          $(this).prop('disabled', false);
          // перезагружаем страницу после сохранения сущности в публичке (из режима редактирования) 
          if (publicReload != true && object[0].id != 'modal-elfinder' && admin.PULIC_MODE) {
            location.reload();
            return false;
          }
        }
      });
    },

    otherOpen: function () {
      return Array.from(document.querySelectorAll('.reveal-overlay:not([style*="display:none"])'));
    },


    /**
     * Фон для заднего плана при открытии всплывающего окна
     */
    overlay: function () {
      $("body").append("<div id='overlay' class='no-print'></div>");
    },

    /**
     * Шаблоны регулярных выражений для проверки ввода в поля
     * admin.regTest(4,'текст')
     */
    regTest: function (regId, text) {
      switch (regId) {
        case 1:
          {
            return /^[-0-9a-zA-Zа-яА-ЯёЁїЇєЄґҐ&`'іІ«»()$%\s_\"\.,!?:]+$/.test(text);
            break;
          }
        case 2:
          {
            return /^[-0-9a-zA-Zа-яА-ЯёЁїЇєЄґҐ&`'іІ«»()$%\s_]+$/.test(text);
            break;
          }
        case 3:
          {
            return /^[,\s]+$/.test(text);
            break;
          }
        case 4:
          {
            return /["']/.test(text);
            break;
          }
        case 5: // проверка email
          {
            return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(text);
            break;
          }
      }
    },
    /**
     * отсечение символа по краям строки
     */
    trim: function (s, simb) {
      if (!simb) {
        s = s.replace(/\s+$/g, '');
        s = s.replace(/^\s+/g, '');
      } else {
        s = s.replace(eval("/^\\" + simb + "+/g"), '');
        s = s.replace(eval("/\\" + simb + "+$/g"), '');
      }
      return s;
    },
    /**
     * выводит ползунок цены для фильтров цены в "заказах" и "товарах"
     */
    sliderPrice: function () {
      $("#price-slider").slider({
        min: $("input#minCost").data("fact-min"),
        max: $("input#maxCost").data("fact-max"),
        values: [$("input#minCost").val(), $("input#maxCost").val()],
        step: 100,
        range: true,
        stop: function (event, ui) {
          $("input#minCost").val(ui.values[0]);
          $("input#maxCost").val(ui.values[1]);
        },
        slide: function (event, ui) {
          $("input#minCost").val(ui.values[0]);
          $("input#maxCost").val(ui.values[1]);
        }
      });

      $("input#minCost").change(function () {
        var value1 = $("input#minCost").val();
        var value2 = $("input#maxCost").val();

        if (parseInt(value1) > parseInt(value2)) {
          value1 = value2;
          $("input#minCost").val(value1);
        }
        $("#price-slider").slider("values", 0, value1);
      });

      $("input#maxCost").change(function () {
        var value1 = $("input#minCost").val();
        var value2 = $("input#maxCost").val();

        if (parseInt(value1) > parseInt(value2)) {
          value2 = value1;
          $("input#maxCost").val(value2);
        }
        $("#price-slider").slider("values", 1, value2);
      });

      $("input#maxCost").keyup(function () {
        var value = $("input#maxCost").val();

        if (value == '') {
          $("input#maxCost").val($("input#maxCost").data("fact-max"));
        }
      });
    },
    /**
     * разрешает менять местами строки таблицы, для сортировки элементов
     * tableSelector - селектор объекта , таблица в которой  доступна сортировка
     * tablename - название таблицы в базе данных (обязательно должна иметь поля id и sort)
     * у строк таблицы обязательно должен быть атрибут data-id
     * @returns {undefined}
     */
    sortable: function (tableSelector, tablename, pageSort) {
      if (typeof pageSort === 'undefined') {
        pageSort = false;
      }
      // исправляет баг с ломающейся строкой таблицы
      var fixHelper = function (e, ui) {
        ui.children().each(function () {
          $(this).width($(this).width());
        });
        return ui;
      };

      // создает массив позиций с маркером
      function createArray(ui, marker) {
        var strItems = [];
        $(tableSelector).children().each(function (i) {
          var tr = $(this);
          if (tr.data("id") == ui.item.data("id")) {
            strItems.push(marker);
          } else {
            if (tr.data("id") != undefined) {
              strItems.push(tr.data("id"));
            }
          }

        });
        return strItems;
      }

      var listIdStart = [];
      var listIdEnd = [];


      if ($(tableSelector).hasClass('ui-sortable')) {
        $(tableSelector).sortable('destroy');
        $(tableSelector).unbind();
      }

      $(tableSelector).sortable({
        helper: fixHelper,
        handle: '.mover i',
        placeholder: 'jquery-ui-sorter-placeholder',
        start: function (event, ui) {
          listIdStart = createArray(ui, 'start');
          if (pageSort) {
            $('.pageSort').show();
            $(tableSelector).closest('.table-wrapper').css('overflow', 'visible');
            $('.pageSort td').attr('colspan', $(tableSelector + ' tr td').length);
          }
        },
        update: function (event, ui) {
          listIdEnd = createArray(ui, 'end');

          var $thisId = ui.item.data("id");
          var sequence = getSequenceSort(listIdStart, listIdEnd, $thisId);
          if (sequence.length > 0) {
            sequence = sequence.join();
            admin.ajaxRequest({
              mguniqueurl: "action/changeSortRow",
              switchId: $thisId,
              sequence: sequence,
              tablename: tablename,
            },
              function (response) {
                admin.indication(response.status, response.msg);
                if ($(tableSelector).data('refresh') == true) {
                  admin.refreshPanel();
                }
                // для страничной сортировки
                if (pageSort) {
                  if (!$(tableSelector + ' tr:eq(0)').hasClass('pageSort') && $('.pageSort').length == 2 && $('.pageSortOff').val() != 'true') admin.refreshPanel();
                  if (!$(tableSelector + ' tr:eq(' + ($(tableSelector + ' tr').length - 1) + ')').hasClass('pageSort') && $('.pageSortOff').val() != 'true') admin.refreshPanel();
                }

              }
            );
          }
        },
        stop: function (event, ui) {
          if (pageSort) {
            $('.pageSort').hide();
            $(tableSelector).closest('.table-wrapper').css('overflow', 'auto');
          }
        }
      });


      /**
       * Вычисляет последовательность замены порядковых индексов
       * Получает для массива
       * ["1", "start", "9", "2", "10"]
       * ["1", "9", "2", "end", "10"]
       * и ID перемещенной категории
       */
      function getSequenceSort(arr1, arr2, id) {
        var startPos = '';
        var endPos = '';

        // вычисляем стартовую позицию элемента
        arr1.forEach(function (element, index, array) {
          if (element == "start") {
            startPos = index;
            arr1[index] = id;
            return false;
          }
        });

        // вычисляем конечную позицию элемента
        arr2.forEach(function (element, index, array) {
          if (element == "end") {
            endPos = index;
            arr2[index] = id;
            return false;
          }
        });

        // вычисляем индексы категорий с которым и надо поменяться местами
        var result = [];

        // направление переноса, сверху вниз
        if (endPos > startPos) {
          arr1.forEach(function (element, index, array) {
            if (index > startPos && index <= endPos) {
              result.push(element);
            }
          });
        }

        // направление переноса, снизу вверх
        if (endPos < startPos) {
          arr2.forEach(function (element, index, array) {
            if (index > endPos && index <= startPos) {
              result.unshift(element);
            }
          });
        }

        return result;
      }
      ;
    },

    sortableMini: function (tableSelector, callback) {
      // исправляет баг с ломающейся строкой таблицы
      var fixHelper = function (e, ui) {
        ui.children().each(function () {
          $(this).width($(this).width());
        });
        return ui;
      };

      // создает массив позиций с маркером
      function createArray(ui, marker) {
        var strItems = [];
        $(tableSelector).children().each(function (i) {
          var tr = $(this);
          if (tr.data("id") == ui.item.data("id")) {
            strItems.push(marker);
          } else {
            if (tr.data("id") != undefined) {
              strItems.push(tr.data("id"));
            }
          }

        });
        return strItems;
      }

      var listIdStart = [];
      var listIdEnd = [];


      if ($(tableSelector).hasClass('ui-sortable')) {
        $(tableSelector).sortable('destroy');
        $(tableSelector).unbind();
      }

      $(tableSelector).sortable({
        helper: fixHelper,
        handle: '.mover i',
        start: function (event, ui) {
          listIdStart = createArray(ui, 'start');
        },
        update: function (event, ui) {
          listIdEnd = createArray(ui, 'end');

          var $thisId = ui.item.data("id");
          var sequence = getSequenceSort(listIdStart, listIdEnd, $thisId);
          if (sequence.length > 0) {
            sequence = sequence.join();
          }

          if (typeof callback !== 'undefined') {
            eval(callback);
          }

        }
      });


      /**
       * Вычисляет последовательность замены порядковых индексов
       * Получает для массива
       * ["1", "start", "9", "2", "10"]
       * ["1", "9", "2", "end", "10"]
       * и ID перемещенной категории
       */
      function getSequenceSort(arr1, arr2, id) {
        var startPos = '';
        var endPos = '';

        // вычисляем стартовую позицию элемента
        arr1.forEach(function (element, index, array) {
          if (element == "start") {
            startPos = index;
            arr1[index] = id;
            return false;
          }
        });

        // вычисляем конечную позицию элемента
        arr2.forEach(function (element, index, array) {
          if (element == "end") {
            endPos = index;
            arr2[index] = id;
            return false;
          }
        });

        // вычисляем индексы категорий с которым и надо поменяться местами
        var result = [];

        // направление переноса, сверху вниз
        if (endPos > startPos) {
          arr1.forEach(function (element, index, array) {
            if (index > startPos && index <= endPos) {
              result.push(element);
            }
          });
        }

        // направление переноса, снизу вверх
        if (endPos < startPos) {
          arr2.forEach(function (element, index, array) {
            if (index > endPos && index <= startPos) {
              result.unshift(element);
            }
          });
        }

        return result;
      }
      ;
    },
    /**
     * Сохраняет html контент из inline редактора в разделе товаров
     * @param string table - название таблицы в которую пойдет запись
     * @param string field - название поля в таблице для перезаписи
     * @param int id - идентификатор записи для обновления
     * @param string content
     * @returns {undefined}
     */
    fastSaveField: function (table, field, id, content, dir = '', cleanImages = false) {
      // отправка данных на сервер для сохранения
      const ajaxData = {
        mguniqueurl: "action/fastSaveContent",
        table: table,
        id: id,
        field: field,
        content: content,
        dir: dir,
        lang: langP
      };

      if (cleanImages) {
        ajaxData.cleanImages = 1;
      }
      admin.ajaxRequest(ajaxData,
        function (response) {
          admin.indication('success', lang.ACT_SAVE_PAGE);
        }
      );
    },
    /**
     * Для открытия модалки в публичной части вытаскивает
     * только модалку с необходимого раздела админки
     * @returns {undefined}
     */
    cloneModal: function (section) {
      $('.mg-admin-html').remove();
      $('body').append('<div class="mg-admin-html section-' + section + ' mg-admin-body"></div>');
      $('.mg-admin-html').append($('.reveal-overlay'));
      $('.admin-center').remove();
    },
    /**
     * Показывает модальное окно файлового менеджера для загрузки файлов
     * @returns {undefined}
     */
    openUploader: function (callback, param, dir, universal = false) {
      includeJS(mgBaseDir + '/mg-core/script/elfinder/js/elfinder.min.js');
      includeJS(mgBaseDir + '/mg-core/script/elfinder/js/i18n/elfinder.ru.js');
      includeJS(mgBaseDir + '/mg-core/script/admin/uploader.js');
      if (dir) {
        admin.DIR_FILEMANAGER = dir;
      }
      if (admin.DIR_FILEMANAGER == 'template') {
        uploader.open();
      }
      if (admin.DIR_FILEMANAGER == 'uploads') {
        uploader.open(callback, param, universal);
      }
      if (admin.DIR_FILEMANAGER == 'temp') {
        uploader.open();
      }

      admin.DIR_FILEMANAGER = 'uploads';

    },
    numberFormat: function (str) {
      var result = '';
      var priceFormat = admin.PRICE_FORMAT;
      //без форматирования
      if (priceFormat == '1234.56') {
        result = str;
      } else
        //разделять тысячи пробелами, а копейки запятыми
        if (priceFormat === '1 234,56') {
          result = admin.number_format(str, 2, ',', ' ');
        } else
          //разделять тысячи запятыми, а копейки точками
          if (priceFormat === '1,234.56') {
            result = admin.number_format(str, 2, '.', ',');
          } else
            //без копеек, без форматирования
            if (priceFormat == '1234') {
              result = Math.round(str);
            } else
              //без копеек, разделять тысячи пробелами
              if (priceFormat == '1 234') {
                result = admin.number_format(Math.round(str), 0, ',', ' ');
              } else
                //без копеек, разделять тысячи запятыми
                if (priceFormat == '1,234') {
                  result = admin.number_format(Math.round(str), 0, '.', ',');
                } else {
                  result = admin.number_format(Math.round(str), 0, ',', ' ');
                }

      result += '';
      var cent = result.substr(result.length - 3);

      if (cent === '.00' || cent === ',00') {
        result = result.substr(0, result.length - 3);
      }

      return result;
    },
    // форматирует строку в соответствии с форматом
    number_format: function (number, decimals, dec_point, thousands_sep) {

      var i, j, kw, kd, km;

      if (isNaN(decimals = Math.abs(decimals))) {
        decimals = 2;
      }
      if (dec_point == undefined) {
        dec_point = ",";
      }
      if (thousands_sep == undefined) {
        thousands_sep = ".";
      }

      i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

      if ((j = i.length) > 3) {
        j = j % 3;
      } else {
        j = 0;
      }

      km = (j ? i.substr(0, j) + thousands_sep : "");
      kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);

      kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


      return km + kw + kd;
    },
    sizeFormat: function (float) {
      return admin.size_format(float);
    },
    size_format: function (float) {
      float += '';
      if (float == undefined || float == '') { return false; }

      result = parseFloat(float);

      if (result > 1024) {
        result = result / 1024;
        if (result > 1024) {
          result = result / 1024;
          if (result > 1024) {
            result = result / 1024;
            resultText = Math.round(result, 3) + ' GB';
          } else {
            resultText = Math.round(result) + ' MB';
          }
        } else {
          resultText = Math.round(result) + ' KB';
        }
      } else {
        resultText = Math.round(result) + ' B';
      }
      return resultText;
    },

    numberDeFormat: function (str) {
      return admin.number_de_format(str);
    },
    // Отменяет форматирование цены, и приводит к числу
    number_de_format: function (str) {
      str += '';
      if (str == undefined || str == '') { return false; }

      result = str;

      cent = false;
      thousand = false;

      existpoint = str.lastIndexOf('.');
      existcomma = str.lastIndexOf(',');

      // 1,320.50
      if (existpoint > 0 && existcomma > 0) {
        result = str.replace(/,/g, '.');
        firstpoint = result.indexOf('.');
        lastpoint = result.lastIndexOf('.');

        if (firstpoint != lastpoint) {
          str1 = result.substr(0, lastpoint);
          str2 = result.substr(lastpoint);
          str1 = str1.replace(/\./g, '');
          result = str1 + str2;
        }

        return result;
      }

      // 1,234 или 1 234,56
      if (existpoint < 0 && existcomma > 0) {
        //определяем, что отделяется запятой, тысячи или копейки
        str2 = str.substr(existcomma);
        if (str2.length - 1 == 2) {
          cent = true;
        } else {
          thousand = true;
        }
      }

      if (thousand) {
        result = str.replace(/,/g, '');
      }

      if (cent) {
        result = str.replace(/,/g, '.');
        firstpoint = result.indexOf('.');
        lastpoint = result.lastIndexOf('.');
        if (firstpoint != lastpoint) {
          str1 = result.substr(0, lastpoint);
          str2 = result.substr(lastpoint);
          str1 = str1.replace('.', '');
          result = str1 + str2;
        }
      }

      result = result.replace(/ /g, '');

      return result;
    },
    sizeFormat: function (float) {
      return admin.size_format(float);
    },
    size_format: function (float) {
      float += '';
      if (float == undefined || float == '') { return false; }

      result = parseFloat(float);

      if (result > 1024) {
        result = result / 1024;
        if (result > 1024) {
          result = result / 1024;
          if (result > 1024) {
            result = result / 1024;
            resultText = Math.round(result, 3) + ' GB';
          } else {
            resultText = Math.round(result) + ' MB';
          }
        } else {
          resultText = Math.round(result) + ' KB';
        }
      } else {
        resultText = Math.round(result) + ' B';
      }
      return resultText;
    },
    /**
      * Заменяет BB-коды соответствующими HTML-тегами.
    */
    replaceBBcodes: function (text) {
      text = text.replace(/\[\/?br\]/g, '</br>');
      text = text.replace(/\[b\](.*?)\[\/b\]/g, '<b>$1</b>');
      text = text.replace(/\[i\](.*?)\[\/i\]/g, '<i>$1</i>');
      text = text.replace(/\[u\](.*?)\[\/u\]/g, '<span style="text-decoration:underline;">$1</span>');
      text = text.replace(/\[quote\](.*?)\[\/quote\]/g, '<pre>$1</pre>');
      text = text.replace(/\[size=(.*?)\](.*?)\[\/size\]/g, '<span style="font-size:$1px;">$2</span>');
      text = text.replace(/\[color=(.*?)\](.*?)\[\/color\]/g, '<span style="color:$1;">$2</span>');
      text = text.replace(/\[url\](?:ftp|http(s?):\/\/)?([a-z0-9-.]+\.\w{2,4})\[\/url\]/g, '<a href="http$1://$2">$2</a>');
      text = text.replace(/\[url\s?=\s?([\'"]?)(?:http(s?):\/\/)?([a-z0-9-.]+\.\w{2,4}.*?)\1\](.*?)\[\/url\]/g, '<a href="http$2://$3" target="blank">$4</a>');
      text = text.replace(/\[img\](https?:\/\/.*?\.(?:jpg|jpeg|gif|png|bmp))\[\/img\]/g, '<img src="$1" alt="" />');
      return text;
    },
    // Выводит выпадающий список продуктов по заданному запросу
    searchProduct: function (text, fastResult, searchCats, adminOrder, useVariants, excludeIds, userCurrency) {
      if (typeof searchCats === "undefined" || searchCats === null) {
        searchCats = -1;
      }
      if (typeof text === "undefined" || text === null) {
        text = '';
      }
      if (typeof adminOrder === "undefined" || adminOrder === null) {
        adminOrder = 'nope';
      }
      if (typeof useVariants === "undefined" || useVariants === null) {
        useVariants = false;
      }
      if (typeof excludeIds === "undefined" || excludeIds === null) {
        excludeIds = [];
      }
      if (typeof userCurrency === "undefined" || userCurrency === null) {
        userCurrency = '';
      }

      if (text.length >= 2) {
        admin.ajaxRequest({
          mguniqueurl: "action/getSearchData",
          search: text,
          searchCats: searchCats,
          adminOrder: adminOrder,
          useVariants: useVariants,
          currency: userCurrency
        },
          function (response) {
            admin.searcharray = [];
            var html = '<ul class="fast-result-list" style="padding:0;">';
            var currency = response.currency;
            var mgBaseDir = $('#thisHostName').text();
            var mgNoImageStub = $('#thisNoImageStub').text();

            function buildElements(element, index, array) {
              if (jQuery.inArray(element.id, excludeIds) === -1) {
                admin.searcharray.push(element);
                html +=
                  '<li style="list-style-type:none;"><a role="button" href="javascript:void(0)" style="width:100%;" data-element-index="' +
                  index + '" data-id="' + element.id + '" data-code="' +
                  element.code + '" data-price="' + element.price + '"> \n\
                  <div class="fast-result-img" style="float:left;">' +
                  '<img src="' + element.image_url
                  + '" ' + 'alt="' + element.title + '"/>' +
                  '</div><div class="fast-result-info"><div class="search-prod-name">'
                  + element.title +
                  '</div><span class="product-code">' + element.code +
                  '</span><br><span class="product-price">' + element.price + ' ' + currency +
                  '</span></div></a></li>';
              }
            }

            if ('success' == response.status && response.item.items.catalogItems.length > 0) {
              response.item.items.catalogItems.forEach(buildElements);
              html += '</ul>';
              $(fastResult).html(html);
              $(fastResult).show();
            } else {
              $(fastResult).hide();
            }
          },
          false,
          "json",
          true
        );
      } else {
        $('.fastResult').hide();
      }
    },
    // быстрое сохранение всплывающих окон
    ctrlKeyUpgrade: function (e) {
      var forbiddenKeys = new Array('s');
      //var forbiddenKeys = new Array('s','x','c'); пример запрета на использование клавишей в сочетании с ctrl
      var key;
      var isCtrl;
      var isSave = false;
      if (window.event) {
        key = window.event.keyCode;
        if (window.event.ctrlKey || window.event.metaKey)
          isCtrl = true;
        else
          isCtrl = false;
      } else {
        key = e.which;
        if (e.ctrlKey || e.metaKey)
          isCtrl = true;
        else
          isCtrl = false;
      }
      if (isCtrl) {
        for (i = 0; i < forbiddenKeys.length; i++) {
          if (forbiddenKeys[i].toLowerCase() == String.fromCharCode(key).toLowerCase()) {
            admin.keySave = true;
            $('.admin-center .save-file-template:visible').click();
            $('.admin-center .save-settings:visible').click();
            $('.save-button:visible').click();
            admin.keySave= false;
            return false;
          }
        }
      }
      return true;
    },
    /**
     * Метод для выборки данных с указанных полей
     * формирования объекта из названий и знаечений элементов формы таких как input, textarea, checkbox и т.п.
     * каждый из этих элементов должен содержать класс
     * пример: fields = '.option'
     * элементы формы обязательно должны иметь атрибут name
     */
    createObject: function (fields) {
      var data = {};
      $(fields).each(function () {
        if ($(this).attr('name') === undefined) {
          return;
        }
        switch ($(this).attr('type')) {
          // обработка радио кнопок
          case 'radio':
            if ($(this).prop('checked')) {
              data[$(this).attr('name')] = $(this).val();
            }
            break;
          // обработка чекбоксов
          case 'checkbox':
            data[$(this).attr('name')] = $(this).prop('checked');
            break;
          // обработка текстовых полей
          default:
            data[$(this).attr('name')] = $(this).val();
            break;
        }
      });
      return data;
    },
    /**
     * Метод для выборки данных из инпутов/селектов/etc внутри контейнера
     * пример: res = admin.createObjectFromContainer($('#container'));
     * элементы обязательно должны иметь атрибут name
     */
    createObjectFromContainer: function (container) {
      var data = {};
      var fields = container.find(':input');
      $(fields).each(function () {
        if (typeof $(this).attr('name') != 'undefined') {
          if ($(this).attr('type') == 'checkbox') {
            data[$(this).attr('name')] = $(this).prop('checked');
          } else {
            data[$(this).attr('name')] = $(this).val();
          }
        }
      });
      return data;
    },
    /**
     * Метод для конвертации данных в строку GET параметров
     * @param {*} data
     */
    serializeParamsFromObject: function (data) {
      var params = Object.keys(data).map(function (k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
      }).join('&');
      return params;
    },
    /**
     * инициализая foundation
     */
    foundationInit: function () {
      // if ($('[data-tooltip]').length) {
      //   if($('.tooltip').length != 0) {
      //     $('.tooltip').remove();
      //   }
      //   $(".admin-center .data").foundation();
      //   $("[data-tooltip]").foundation("tooltip");
      // }

      // $(".admin-center .data").foundation();
      // не работает на странице пользователей, позже разобраться
      // $(".admin-center").foundation();

    },
    /**
     * Метод для редактирования контента в публичной части для администратора
     */
    publicAdmin: function () {
      admin.PULIC_MODE = true;

      if ($(".admin-top-menu").length > 0) {
        $("body").addClass("admin-on-site");
      }
      else {
        $("body").removeClass("admin-on-site");
      }
      // клик по элементу открывающему модалку
      //$('.modalOpen').on('click', admin.modalOpenAction);
      // ------>
      $('.modalOpen').on('click', function (e) {
        if (!$(this).hasClass('disabled')) {
          admin.showLoaderModal($(this)); //Блокируем кнопку, чтобы не кликали много раз
          e.stopPropagation();
          e.preventDefault();
          $('.admin-center .reveal-overlay').remove();

          var section = $(this).data('section');
          $('body').append('<div class="admin-center" ><div class="data"></div></div>');
          includeJS(admin.SITE + '/mg-core/script/admin/' + section + '.js');

          // перечень функций выполняемых после  получения ответа от сервера
          // (вырезаем только модалку из полученного контента, и открываем ее с нужными параметрами)

          admin.AJAXCALLBACK = [
            { callback: 'admin.cloneModal', param: [section] },
            { callback: section + '.openModalWindow', param: eval($(this).data('param')) },
            { callback: 'admin.hideLoaderModal', param: [$(this)] }, //Разблокируем кнопку
            { callback: section + '.init', param: null },
            { callback: 'admin.initEvents', param: null }
          ];

          // открываем раздел из которого вызовем модалку
          admin.ajaxRequest(
            "mguniqueurl=" + section + ".php&mguniquetype=adminpage&",
            function (data) {
              $(".admin-center .data").html(data);
              admin.initCustom();
            },
            false,
            "html");
        }
        $(this).addClass('disabled');
      });
      // <------
      // контекстное меню при наведении на элемент в публичной части
      $('body').on({
        mouseenter: function () {
          $(this).find('.admin-context').show();
        },
        mouseleave: function () {
          $(this).find('.admin-context').hide();
        }
      }, '.exist-admin-context');
      $(".exist-admin-context").parent().css({ display: "block" });

    },

    modalOpenAction: function (e) {
      if (!$(this).hasClass('disabled')) {
        admin.showLoaderModal($(this)); //Блокируем кнопку, чтобы не кликали много раз
        e.stopPropagation();
        e.preventDefault();
        $('.admin-center .reveal-overlay').remove();

        var section = $(this).data('section');
        $('body').append('<div class="admin-center" ><div class="data"></div></div>');
        includeJS(admin.SITE + '/mg-core/script/admin/' + section + '.js');

        // перечень функций выполняемых после  получения ответа от сервера
        // (вырезаем только модалку из полученного контента, и открываем ее с нужными параметрами)

        admin.AJAXCALLBACK = [
          { callback: 'admin.cloneModal', param: [section] },
          { callback: section + '.openModalWindow', param: eval($(this).data('param')) },
          { callback: 'admin.hideLoaderModal', param: [$(this)] }, //Разблокируем кнопку
          { callback: section + '.init', param: null },
          { callback: 'admin.initEvents', param: null }
        ];

        // открываем раздел из которого вызовем модалку
        admin.ajaxRequest(
          "mguniqueurl=" + section + ".php&mguniquetype=adminpage&",
          function (data) {
            $(".admin-center .data").html(data);
            admin.initCustom();
          },
          false,
          "html");
      }
    },
    showLoaderModal: function (element) {
      element.addClass('disabled');
    },
    hideLoaderModal: function (element) {
      element.removeClass('disabled');
    },
    getSetting: function (setting) {
      admin.ajaxRequest({
        mguniqueurl: "action/getSetting",
        setting: setting,
      }, function (response) {
        admin.PRICE_FORMAT = response.data.priceFormat;
      });
    },
    //переводит цвет из RGB формата в хеш
    rgb2hex: function (orig) {
      var rgb = orig.replace(/\s/g, '').match(/^rgba?\((\d+),(\d+),(\d+)/i);
      return (rgb && rgb.length === 4) ? "#" +
        ("0" + parseInt(rgb[1], 10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[2], 10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[3], 10).toString(16)).slice(-2) : orig;
    },

    translitIt: function (text, engToRus) {
      var rus = "щ   ш  ч  ц  ю  я  ё  ж  ъ  ы э а б в г д е з и й к л м н о п р с т у ф х ь".split(/ +/g);
      var eng = "shh sh ch cz yu ya yo zh `` y e a b v g d e z i j k l m n o p r s t u f h `".split(/ +/g);
      var x;
      for (x = 0; x < rus.length; x++) {
        text = text.split(engToRus ? eng[x] : rus[x]).join(engToRus ? rus[x] : eng[x]);
        text = text.split(engToRus ? eng[x].toUpperCase() : rus[x].toUpperCase()).join(engToRus ? rus[x].toUpperCase() : eng[x].toUpperCase());
      }
      return text;
    },

    // использование Math.round() даст неравномерное распределение!
    getRandomInt: function (min, max) {
      return Math.floor(Math.random() * (max - min + 1)) + min;
    },

    toFloat: function (number) {
      return parseFloat((number).toFixed(10));
    },

    tryJsonParse: function (str) {
      var res;
      try {
        res = JSON.parse(str);
        return res;
      } catch (e) {
        return str;
      }
    },
    // Считает количество символов в метатегах и подсвечивает при переполнении
    countMeta: function (elem) {
      var metaInputCount = elem.val().length,
        maxMetaCount = parseInt(elem.parent().find('.js-count-max').text()),
        counterText = elem.parent().find('.js-count-meta');

      counterText.text(metaInputCount).css('color', (metaInputCount > maxMetaCount) ? '#d35d5d' : '#888');
    },
    //Метод для обновления списка плагинов в контекстном меню
    updatePluginsDropdownMenu: function () {
      admin.ajaxRequest({
        mguniqueurl: "action/getPluginsMenu"
      }, function (response) {
        $('.sub-list.plugins-dropdown-menu').html(response.data.html);
      });
    },
    /*
    * Метод плавного скролла до якоря
    * */
    scrollToElem: function (e) {
      var blockID = e.target.getAttribute('href').substr(1);
      e.preventDefault();
      try {
        document.getElementById(blockID).scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        })
      }
      catch (e) {
        console.log('Ошибка! Якорь с id ' + blockID + ' не найден!');
      }
    },

    // сортировка таблицы по ячейкам (сначала по дата атрибуту sortval, при его отсутствии - по html)
    sortTable: function (obj) {
      var n = obj.index();
      var table = $(obj).closest('table')[0];
      var rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
      switching = true;
      if (admin.PLUGGINS_SORT_MODE) {
        dir = admin.PLUGGINS_SORT_MODE;
      } else {
        dir = "asc";
      }
      while (switching) {
        switching = false;
        rows = table.getElementsByTagName("TR");
        for (i = 1; i < (rows.length - 1); i++) {
          shouldSwitch = false;
          x = rows[i].getElementsByTagName("TD")[n];
          y = rows[i + 1].getElementsByTagName("TD")[n];
          if (dir == "asc") {
            if (typeof x.dataset.sortval !== 'undefined' && typeof y.dataset.sortval !== 'undefined') {
              if ($.isNumeric(x.dataset.sortval) && $.isNumeric(y.dataset.sortval)) {
                if (parseFloat(x.dataset.sortval) > parseFloat(y.dataset.sortval)) {
                  shouldSwitch = true;
                  break;
                }
              } else {
                if (x.dataset.sortval.toLowerCase() > y.dataset.sortval.toLowerCase()) {
                  shouldSwitch = true;
                  break;
                }
              }
            } else {
              if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                shouldSwitch = true;
                break;
              }
            }
          }
          else if (dir == "desc") {
            if (typeof x.dataset.sortval !== 'undefined' && typeof y.dataset.sortval !== 'undefined') {
              if ($.isNumeric(x.dataset.sortval) && $.isNumeric(y.dataset.sortval)) {
                if (parseFloat(x.dataset.sortval) < parseFloat(y.dataset.sortval)) {
                  shouldSwitch = true;
                  break;
                }
              } else {
                if (x.dataset.sortval.toLowerCase() < y.dataset.sortval.toLowerCase()) {
                  shouldSwitch = true;
                  break;
                }
              }
            } else {
              if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                shouldSwitch = true;
                break;
              }
            }
          }
        }
        if (shouldSwitch) {
          rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
          switching = true;
          switchcount++;
        } else {
          if (admin.PLUGGINS_SORT_MODE) {
            if (switchcount == 0 && dir != admin.PLUGGINS_SORT_MODE) {
              dir = admin.PLUGGINS_SORT_MODE == 'asc' ? 'desc' : 'asc';
              switching = true;
            }
            if (switchcount == 0 && dir == admin.PLUGGINS_SORT_MODE) {
              dir = admin.PLUGGINS_SORT_MODE;
              break;
            }
          }
          else {
            if (switchcount == 0 && dir == "asc") {
              dir = "desc";
              switching = true;
            }
          }
        }
      }
      return dir;
    },
    closeBannerUnused: function () {
      days = 7;
      myDate = new Date();
      myDate.setTime(myDate.getTime() + (days * 24 * 60 * 60 * 1000));
      document.cookie = 'BannerUnused=closed; expires=' + myDate.toGMTString() + '; path=/';
      $('.updateDbLoader, .updateDbLoaderPlace').hide();
    },
    includePickr: function () {
      // Выполняется повторное подключение при открытии Настроек
      //TODO исправить на нормальную инициализацию

      includeJS(mgBaseDir + '/mg-core/script/pickr/pickr.min.js');
      includeJS(mgBaseDir + '/mg-core/script/pickr/pickr.es5.min.js');
      if ($('body').hasClass('mg-admin-body')) {
        $('.pickr-style').detach();
        $('body').append('<div class="pickr-style"></div>');
        $('.pickr-style').append('<link rel="stylesheet" type="text/css" href="' + mgBaseDir + '/mg-core/script/pickr/themes/classic.min.css" />');
      }
      //Цикл по всем доступным пикерам
      for (i = 0; i < admin.PICKR_STORAGE.length; i++) {
        //Возвращаем div'ы, вместо которых вставал пикер
        admin.PICKR_STORAGE[i]._root.root.after(admin.PICKR_STORAGE[i].options.el);
        //Сносим пикеры
        admin.PICKR_STORAGE[i].destroyAndRemove();
      }
      //Отчищаем список пикеров
      admin.PICKR_STORAGE = [];
    },
    includeColorPicker: function () {
      includeJS(mgBaseDir + '/mg-core/script/colorPicker/js/colorpicker.js');
      includeJS(mgBaseDir + '/mg-core/script/colorPicker/js/eye.js');
      includeJS(mgBaseDir + '/mg-core/script/colorPicker/js/utils.js');
    },
    includeCodemirror: function () {
      includeJS(mgBaseDir + '/mg-core/script/codemirror/lib/codemirror.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/javascript/javascript.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/xml/xml.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/php/php.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/css/css.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/clike/clike.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/search.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/searchcursor.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/jump-to-line.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/match-highlighter.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/matchesonscrollbar.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/dialog/dialog.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/scroll/annotatescrollbar.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/scroll/scrollpastend.js');
      includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/scroll/simplescrollbars.js');
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
    initPickr: function (name, opacity, show_now) {
      if ($('#' + name).length === 0) {
        return false;
      }

      if (typeof opacity === "undefined" || opacity === null) {
        opacity = false;
      }

      if (typeof show_now === "undefined" || show_now === null) {
        show_now = false;
      }

      var val = $('[name="' + name + '"]').val();

      tmpPickr = new Pickr.create({
        el: '#' + name,
        theme: 'classic',
        default: val != '' ? val : '#FFFFFF',
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
      }).on('save', function (color, instance) {
        //При сохранение цвета записываем его в инпут
        $('[name="' + name + '"]').val(color.toHEXA().toString());
        instance.hide();
      }).on('init', function (instance) {
        //Запоминаем пикер
        admin.PICKR_STORAGE.push(instance);
        //Открываем его, если флажок стоит
        if (show_now) {
          instance.show();
        }
      }).setColorRepresentation(val != '' ? val : '#000000') //Ставим дефолтное знаечение

      return tmpPickr;
    },
    clickPickr: function (name, opacity) {
      $('#' + name)
        .css({
          'background-color': $('[name=' + name + ']').val(),
          'width': '28px',
          'height': '28px'
        })
        .click(function () {
          admin.initPickr(name, opacity, true);
        });
    },
    callFromString: function (callback) {
      var cb = '';
      if (callback) {
        if (callback.indexOf('.') > -1) {
          var parts = callback.split('.');
          if (typeof (window[parts[0]]) !== 'undefined') {
            cb = window[parts[0]][parts[1]];
          }
        } else {
          if (typeof (window[callback]) !== 'undefined') {
            cb = window[callback];
          }
        }
        if (typeof cb === "function") {
          cb();
        }
      }
    },
  };
})();

//функция для работы с куками
function cookie(name, value, options) {
  if (name !== 'PHPSESSID') {
    if (typeof value != 'undefined') {
      if (value === null) {
        value = '';
      }
      window.sessionStorage[name] = value;
    }
    else {
      if (null !== window.sessionStorage[name]) {
        return window.sessionStorage[name];
      }
    }
  }
  if (typeof value != 'undefined') {
    options = options || {};
    if (value === null) {
      value = '';
      options.expires = -1;
    }
    var expires = '';
    if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
      var date;
      if (typeof options.expires == 'number') {
        date = new Date();
        date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
      } else {
        date = options.expires;
      }
      expires = '; expires=' + date.toUTCString();
    }

    // var path = options.path ? '; path=' + (options.path) : '';
    var path = "; path=/";
    var domain = options.domain ? '; domain=' + (options.domain) : '';
    var secure = options.secure ? '; secure' : '';
    document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
  } else {
    var cookieValue = null;
    if (document.cookie && document.cookie != '') {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = jQuery.trim(cookies[i]);
        if (cookie.substring(0, name.length + 1) == (name + '=')) {
          cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
          break;
        }
      }
    }
    return cookieValue;
  }
}

/*
* Метод  для отладки, открывает полностью разел Система в Saas
*/
function support() {
  document.cookie = "debugMod=mogutaCloud";
}

/**
 * подключает javascript файл и выполняет его
 * заносит название файла в реестр подключенных,
 * дабы не дублировать
 */
function includeJS(path, callback) {
  for (var i = 0; i < javascripts.length; i++) {
    if (path == javascripts[i]) {
      // alert('JavaScript: ['+path+'] уже был подключен ранее!');
      admin.callFromString(callback);
      return false;
    }
  }
  javascripts.push(path);
  var version = $('.mg-version').html();
  if (version) { version = version.trim(); }
  $.ajax({
    url: path + '?v=' + version,
    dataType: "script", // при типе script JS сам инклюдится и воспроизводится без eval
    async: false
  });
  admin.callFromString(callback);
}

/**
 * подключет CSS файл
 */
function includeCSS(name) {
  if (!$('link[href="' + name + '"]').length) {
    $('head').append('<link rel="stylesheet" href="' + name + '" type="text/css" />');
  }
}

//Закрываем модалки при клике вне их
$(document).mousedown(function (e) {
  var popupWrap = $(".custom-popup");
  if (
    !popupWrap.is(e.target) &&
    popupWrap.has(e.target).length === 0 &&
    !popupWrap.hasClass('noAutoClose')
  ) {
    popupWrap.hide();
  }
});
// для конвертации кириллического домена, чтобы IE понимал ссылки
(function (u) {
  var I, e = typeof define == 'function' && typeof define.amd == 'object' && define.amd && define, J = typeof exports == 'object' && exports, q = typeof module == 'object' && module, h = typeof require == 'function' && require, o = 2147483647, p = 36, i = 1, H = 26, B = 38, b = 700, m = 72, G = 128, C = '-', E = /^xn--/, t = /[^ -~]/, l = /\x2E|\u3002|\uFF0E|\uFF61/g, s = { overflow: 'Overflow: input needs wider integers to process', 'not-basic': 'Illegal input >= 0x80 (not a basic code point)', 'invalid-input': 'Invalid input' }, v = p - i, g = Math.floor, j = String.fromCharCode, n;
  function y(K) {
    throw RangeError(s[K])
  }
  function z(M, K) {
    var L = M.length;
    while (L--) {
      M[L] = K(M[L])
    }
    return M
  }
  function f(K, L) {
    return z(K.split(l), L).join('.')
  }
  function D(N) {
    var M = [], L = 0, O = N.length, P, K;
    while (L < O) {
      P = N.charCodeAt(L++);
      if ((P & 63488) == 55296 && L < O) {
        K = N.charCodeAt(L++);
        if ((K & 64512) == 56320) {
          M.push(((P & 1023) << 10) + (K & 1023) + 65536)
        } else {
          M.push(P, K)
        }
      } else {
        M.push(P)
      }
    }
    return M
  }
  function F(K) {
    return z(K, function (M) {
      var L = '';
      if (M > 65535) {
        M -= 65536;
        L += j(M >>> 10 & 1023 | 55296);
        M = 56320 | M & 1023
      }
      L += j(M);
      return L
    }).join('')
  }
  function c(K) {
    return K - 48 < 10 ? K - 22 : K - 65 < 26 ? K - 65 : K - 97 < 26 ? K - 97 : p
  }
  function A(L, K) {
    return L + 22 + 75 * (L < 26) - ((K != 0) << 5)
  }
  function w(N, L, M) {
    var K = 0;
    N = M ? g(N / b) : N >> 1;
    N += g(N / L);
    for (; N > v * H >> 1; K += p) {
      N = g(N / v)
    }
    return g(K + (v + 1) * N / (N + B))
  }
  function k(L, K) {
    L -= (L - 97 < 26) << 5;
    return L + (!K && L - 65 < 26) << 5
  }
  function a(X) {
    var N = [], Q = X.length, S, T = 0, M = G, U = m, P, R, V, L, Y, O, W, aa, K, Z;
    P = X.lastIndexOf(C);
    if (P < 0) {
      P = 0
    }
    for (R = 0; R < P; ++R) {
      if (X.charCodeAt(R) >= 128) {
        y('not-basic')
      }
      N.push(X.charCodeAt(R))
    }
    for (V = P > 0 ? P + 1 : 0; V < Q;) {
      for (L = T, Y = 1, O = p; ; O += p) {
        if (V >= Q) {
          y('invalid-input')
        }
        W = c(X.charCodeAt(V++));
        if (W >= p || W > g((o - T) / Y)) {
          y('overflow')
        }
        T += W * Y;
        aa = O <= U ? i : (O >= U + H ? H : O - U);
        if (W < aa) {
          break
        }
        Z = p - aa;
        if (Y > g(o / Z)) {
          y('overflow')
        }
        Y *= Z
      }
      S = N.length + 1;
      U = w(T - L, S, L == 0);
      if (g(T / S) > o - M) {
        y('overflow')
      }
      M += g(T / S);
      T %= S;
      N.splice(T++, 0, M)
    }
    return F(N)
  }
  function d(W) {
    var N, Y, T, L, U, S, O, K, R, aa, X, M = [], Q, P, Z, V;
    W = D(W);
    Q = W.length;
    N = G;
    Y = 0;
    U = m;
    for (S = 0; S < Q; ++S) {
      X = W[S];
      if (X < 128) {
        M.push(j(X))
      }
    }
    T = L = M.length;
    if (L) {
      M.push(C)
    }
    while (T < Q) {
      for (O = o, S = 0; S < Q; ++S) {
        X = W[S];
        if (X >= N && X < O) {
          O = X
        }
      }
      P = T + 1;
      if (O - N > g((o - Y) / P)) {
        y('overflow')
      }
      Y += (O - N) * P;
      N = O;
      for (S = 0; S < Q; ++S) {
        X = W[S];
        if (X < N && ++Y > o) {
          y('overflow')
        }
        if (X == N) {
          for (K = Y, R = p; ; R += p) {
            aa = R <= U ? i : (R >= U + H ? H : R - U);
            if (K < aa) {
              break
            }
            V = K - aa;
            Z = p - aa;
            M.push(j(A(aa + V % Z, 0)));
            K = g(V / Z)
          }
          M.push(j(A(K, 0)));
          U = w(Y, P, T == L);
          Y = 0;
          ++T
        }
      }
      ++Y;
      ++N
    }
    return M.join('')
  }
  function r(K) {
    return f(K, function (L) {
      return E.test(L) ? a(L.slice(4).toLowerCase()) : L
    })
  }
  function x(K) {
    return f(K, function (L) {
      return t.test(L) ? 'xn--' + d(L) : L
    })
  }
  I = { version: '1.2.0', ucs2: { decode: D, encode: F }, decode: a, encode: d, toASCII: x, toUnicode: r };
  if (J) {
    if (q && q.exports == J) {
      q.exports = I
    } else {
      for (n in I) {
        I.hasOwnProperty(n) && (J[n] = I[n])
      }
    }
  } else {
    if (e) {
      define('punycode', I)
    } else {
      u.punycode = I
    }
  }
}(this));

$(document).ready(function () {
  // js переменные из движка
  document.cookie.split(/; */).forEach(function (cookieraw) {
    if (cookieraw.indexOf('mg_to_script') === 0) {
      var cookie = cookieraw.split('=');
      var name = cookie[0].substr(13);
      var value = decodeURIComponent(decodeURI(cookie[1]));
      window[name] = admin.tryJsonParse(value.replace(/&nbsp;/g, ' '));
    }
  });

  window.CKEDITOR_BASEPATH = mgBaseDir + "/mg-core/script/ckeditor/";

  // все скрипты в админке нужно подключать через функцию includeJS,

  if (!activeLang) {
    activeLang = 'ru_RU'; // TODO, удали этот if и включи нотисы, исправление не засчитано!
  }
  includeJS(mgBaseDir + `/mg-admin/locales/${activeLang}.js`); // lang -> ru_RU

  if (!admin.PULIC_MODE || cookie("publicAdmin") === "true") {
    includeJS(mgBaseDir + '/mg-core/script/jquery-ui.min.js');
  }
  includeJS(mgBaseDir + '/mg-core/script/toggles.js');
  includeJS(mgBaseDir + '/mg-core/script/jquery.form.js');
  includeJS(mgBaseDir + '/mg-core/script/ckeditor/ckeditor.js');
  includeJS(mgBaseDir + '/mg-core/script/ckeditor/adapters/jquery.js');
  includeJS(mgBaseDir + '/mg-core/script/sumoselect.min.js');
  includeJS(mgBaseDir + '/mg-core/script/intro/intro.js');
  includeJS(mgBaseDir + '/mg-core/script/intro/newbieWay.js');
  includeCSS(mgBaseDir + '/mg-core/script/intro/css/introjs.css');
  includeCSS(mgBaseDir + '/mg-admin/design/css/sumoselect.min.css');
  admin.init();
});
