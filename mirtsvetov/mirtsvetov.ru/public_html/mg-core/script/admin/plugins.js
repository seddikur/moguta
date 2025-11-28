/**
 * Модуль для  раздела "Плагины".
 */
var plugin = (function () {
  return {

    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init:function() {
      // обрабатывает клик по кнопке настроек в таблице плагинов
      $('.section-plugins').on('click', '.main-table .plugSettings', function () {
        var pluginName = $(this).parents('tr').attr('id');
        var pluginTitle = $('tr[id='+pluginName+'] .p-name').text();        
        $(".plugins-menu-wrapper").hide();
        plugin.openPagePlugin(pluginName, pluginTitle); 
        $('#tiptip_holder').hide();
        $('#tiptip_holder').css('left','230px');
      });
	  
	 newbieWay.checkIntroFlags('pluginsScenario', false); 

      // Обработчик для загрузки нового плагина
      $('.section-plugins').on('change', '#addPlugin', function() {

        plugin.addNewPlugin();
      });

      // Удаление плагина
      $('.section-plugins').on('click', '.delete-plugin', function() {
        if(!$(this).parents('tr').find('.switch-input').prop( "checked" )) {
          var id = $(this).parents('tr').attr('id');
          plugin.deletePlugin(id);
        } else {
          alert(lang.PLUG_NEED_DEACTIVE);
        }
      });
      //Проверка обновлений плагинов
      $('.section-plugins').on('click', '#checkPluginsUpdate', function() {
        plugin.checkPluginsUpdate();
      });
      
      //Обновление плагина
      $('.section-plugins').on('click', '.main-table .update-plugin', function() {
        var id = $(this).parents('tr').attr('id');
        if(id.length > 0) {
          plugin.updatePlugin(id);
        }
      });

      //изменение активности плагина
      $('.section-plugins').on('click', '.main-table .plugins-active', function() {
        var pluginName = $(this).parents('tr').attr('id');
        if($('#'+$(this).attr('for')).prop('checked')) {
          $(this).parents('tr').find('td').attr('data-sortval','1');
          plugin.deactivatePlugin(pluginName);
        } else {
          $(this).parents('tr').find('td').attr('data-sortval','0');
          plugin.activatePlugin(pluginName);
        }
      });

      // Переход в маркетплэйс (фильтр = плагины)
      $('.section-plugins').on('click', '.js-go-to-market-plug', function () {
        includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
        admin.show("marketplace.php", cookie("type"), "mpFilter=all&mpFilterType=p", marketplaceModule.init);
      });
      // Поиск плагина
      $('.section-plugins .search-plugins').on('keyup',function(evt) {
          plugin.searchPlugins(evt.target.value.toLowerCase());
      });
      // Обработчик на нажатия клавиш ctrl + f
      $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.keyCode == 70 || e.keyCode == 70 && e.keyCode == 17) {
          e.preventDefault();
          $('input[name="searchPlugins"]').focus().select();
        }
      });
	  
	   $('.section-plugins').on('click', '.section-hits', function () {
          newbieWay.showHits('pluginsScenario');
          introJs().start();
       });
      $('.main-table').find('th.js-sortable').removeClass('asc desc');
    },
    // скрывает плагины не соответствуйющие условию в строке поиска
    searchPlugins: function(searchPlugins) {
      $('.main-table__plugins tr').each(function() {
        var pluginName = $(this).find('.plugins-name-wrapper .p-name').text().toLowerCase();
        if (pluginName === '') return;
        var pluginDesk = $(this).find('.plugin-desc').text().toLowerCase();
        if(pluginName.match(searchPlugins) || pluginDesk.match(searchPlugins)){
            $(this).show();
        }
        else {
            $(this).hide();
        }
      });
    },

    // открывает страницу настроек плагина, если она существует
    openPagePlugin: function(pluginName, pluginTitle, havePage) {
      if(havePage || $('tr[id='+pluginName+']').hasClass('plugin-settings-on')) {
        admin.show(pluginName, "plugin",'&pluginTitle='+pluginTitle,function() {
          admin.CURENT_PLUG_TITLE = pluginTitle;
        });
      } else {
        alert(lang.PLUGIN_NOT_HAVE_SETTING);
      }
    },

    // активирует плагин
    activatePlugin: function(pluginName) {
      var searchPlugins = $(".section-plugins input[name='searchPlugins']").val().toLowerCase();
      var pluginTitle = $('tr[id='+pluginName+'] .p-name').text();
      admin.ajaxRequest({
        mguniqueurl:"action/activatePlugin",
        pluginFolder: pluginName,
        pluginTitle: pluginTitle
      },
      (function(response) {
        admin.indication(response.status, response.msg);

        if(response.data.havePage){
          if($('.plugins-list-menu .sub-list').html() == undefined) {
            $('.plugins-list-menu').append('<ul class="sub-list"></ul>');
            $('.plugins-list-menu').addClass('has-menu');
          }
          $('.plugins-list-menu .sub-list').html('<li><a href="#" class="'+pluginName+'">'+pluginTitle+'</li>'+$('.plugins-list-menu .sub-list').html());
        }

        $('.info-panel .button-list').html('');
        $('.desktopInformers').html(response.data.desktopInformer);
        $('.mobileInformers').html(response.data.mobileInformer);  
        $('.mobileInformerCount').html(response.data.mobileInformersSumm);
        //Обновляем список плагинов в контекстном меню
        admin.updatePluginsDropdownMenu();
        if(admin.PLUGGINS_SORT_MODE){
          $(this).closest('table').find('th.js-sortable').removeClass('asc desc');
          let dir = admin.sortTable($('.main-table'));
          $(this).closest('table').find('th.js-sortable').addClass(dir);
          // $('.main-table').addClass(admin.PLUGGINS_SORT_MODE);
          let topPos = $('#'+ pluginName).position().top;
          let winHalf = $(window).height()/2.8;
          window.scrollTo(0, topPos - winHalf);
        }
        if(response.data.settings_btn){
          $('tr[id='+pluginName+'] .action-list').prepend(response.data.settings_btn);
          $('#'+ pluginName).removeClass('plugin-settings-off');
          $('#'+ pluginName).addClass('plugin-settings-on');
          response.data.settings_btn = false;
        }
        $('#'+ pluginName).removeClass('color-marcker-d');
        $('#'+ pluginName).addClass('color-marcker-a');
        setTimeout(() => {
          $(".section-plugins input[name='searchPlugins']").val(searchPlugins);
          plugin.searchPlugins(searchPlugins);
        }, 450);
      })
      );

    },

    // деактивирует плагин
    deactivatePlugin: function(pluginName) {
      var searchPlugins = $(".section-plugins input[name='searchPlugins']").val().toLowerCase();
      var pluginTitle = $('tr[id='+pluginName+'] .p-name').text();
      admin.ajaxRequest({
        mguniqueurl:"action/deactivatePlugin",
        pluginFolder: pluginName,
        pluginTitle: pluginTitle
      },
      (function(response) {
        admin.indication(response.status, response.msg)
        $('.plugins-list-menu .sub-list .'+pluginName).parent('li').remove();
        if($('.plugins-list-menu .sub-list').html() == '') {
          $('.plugins-list-menu .sub-list').detach();
          $('.plugins-list-menu').removeClass('has-menu');
        }
        admin.hideWhiteArrowDown();
        $('tr[id='+pluginName+'] .action-list .plugin-settings-large').remove();        
        $('.info-panel .button-list a[rel='+pluginName+']').parents('li').remove(); 
        //Обновляем список плагинов в контекстном меню
        admin.updatePluginsDropdownMenu();
        if(admin.PLUGGINS_SORT_MODE){
          $(this).closest('table').find('th.js-sortable').removeClass('asc desc');
          let dir = admin.sortTable($('.main-table'));
          $(this).closest('table').find('th.js-sortable').addClass(dir);
          let winHalf = $(window).height()/2.8;//2.8 коэффицент для нахождения нужного сдвига, найдено подбором
          window.scrollTo(0, $('#'+ pluginName).position().top - winHalf);
        }
        if($('#'+ pluginName).hasClass('plugin-settings-on')){
          $('#'+ pluginName).removeClass('plugin-settings-on');
          $('#'+ pluginName).addClass('plugin-settings-off');
        }
        $('tr[id='+pluginName+'] .action-list').find('a.plugSettings').parent().hide();
        $('#'+ pluginName).removeClass('color-marcker-a');
        $('#'+ pluginName).addClass('color-marcker-d');
        response.data.settings_btn = false;
        setTimeout(() => {
          $(".section-plugins input[name='searchPlugins']").val(searchPlugins);
          plugin.searchPlugins(searchPlugins);
        }, 450);
      })
      );
      
    },

    addNewPlugin:function() {
     $('.img-loader').show();

      // установка плагина
      $(".newPluginForm").ajaxForm({
        type:"POST",
        url: "ajax",
        data: {
          mguniqueurl:"action/addNewPlugin"
        },
        cache: false,
        dataType: 'json',
        success: function(response) {
          admin.indication(response.status, response.msg);
          admin.refreshPanel();
          $('.img-loader').hide();
        }
      }).submit();
    },

    /**
     * Удаляет плагин из системы
     */

    deletePlugin: function(id) {
      if(confirm(lang.DELETE+'?')) {
        admin.ajaxRequest({
          mguniqueurl:"action/deletePlugin",
          id: id
        },
        (function(response) {
          admin.indication(response.status, response.msg);
          if(response.status == 'error') return false;
          admin.refreshPanel();
         })
        );
      }
    },
    
    /*
     * Проверяет наличие обновления для плагинов
     */
    checkPluginsUpdate: function(fromPayment = false) {
      admin.ajaxRequest({
        mguniqueurl:"action/checkPluginsUpdate",
      },function(response) {
        admin.indication(response.status, response.msg);
        if (fromPayment) {
          admin.refreshPanel();
          return;
        }
        $('.mg-admin-mainmenu-item[data-section=plugins]').trigger('click');
      });
    },
    
    /*
     * Обновляет плагин
     */
    updatePlugin: function(id, forPayment = false) {
      admin.ajaxRequest({
        mguniqueurl:"action/updatePlugin",
        pluginName: id
      },function(response) {
        if(!response.data['last_version'] && response.status != 'error') {
          plugin.updatePlugin(id, forPayment);
        } else {
          admin.indication(response.status, response.msg);
          if(response.status != 'error') {
            if (forPayment) {
              admin.refreshPanel();
              return;
            }
            $('.mg-admin-mainmenu-item[data-section=plugins]').trigger('click');
          }
        }
      });
    },
    //Скролл для плагина
    scrollToPlug: function() {
      var plId = cookie("pluginScrollEl");
      var scrollTop = $(plId).offset().top -100;
      $(document).scrollTop(scrollTop);
      plugin.init();
    }
  };
})();