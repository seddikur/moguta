/**
 * Модуль для  раздела "Пользователи".
 */
var user = (function () {
  return {
    checkBlockInterval: null,
    init: function() {
      // Инициализация обработчиков
      user.initEvents();
		
      // для блокировки при редактировании
      user.checkBlockInterval = setInterval(user.checkBlockIntervalFunction, 1000);
	  
	    newbieWay.checkIntroFlags('usersScenario', false);
    },
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    initEvents: function() {
      // Вызов модального окна при нажатии на кнопку добавления пользователя.
      $('.section-user').on('click', '.add-new-button', function() {
        user.openModalWindow('add');
      });

      $('.section-user').on('click', '.users-group', function() {
        $('.ruleGroup').val(0);
        $('.ruleGroup[name=name]').val('');
        user.getDataUserGroup($('.select-group').val());
        admin.openModal('#user-group-modal');
      });

      $('.section-user').on('change', '.select-group', function() {
        if(JSON.stringify(admin.createObject('.ruleGroup')) != JSON.stringify(user.tmpRuleParam)) {
          if(!confirm('Изменения не сохранены, при продолжении настройки будут потеряны!')) {
            $('.select-group').val(user.tmpRuleRole);
            return false;
          }
        }
        user.getDataUserGroup($(this).val());
      });

      $('.section-user').on('click', '.save-user-group', function() {
        $('#user-group-modal input[type=text]').each(function() {
            $(this).val(admin.htmlspecialchars($(this).val()));
        });
        admin.ajaxRequest({
          mguniqueurl:"action/saveUserGroup",
          data: admin.createObject('.ruleGroup'),
          id: $(this).attr('id')
        },
        function(response) {
          admin.indication(response.status, 'Сохранено');
          $('.section-user .select-group option[value='+admin.htmlspecialchars_decode(user.tmpRuleRole)+']').text(admin.htmlspecialchars_decode($('.ruleGroup[name=name]').val()));
          user.tmpRuleParam = admin.createObject('.ruleGroup');
          $('.role[name=role]').html($('.section-user .select-group').html());
          $('.ruleGroup[name=name]').val(admin.htmlspecialchars_decode($('.ruleGroup[name=name]').val()));
        });
      });

      $('.section-user').on('click', '.add-group', function() {
        admin.ajaxRequest({
          mguniqueurl:"action/addGroup"
        },
        function(response) {
          admin.indication(response.status, 'Группа добавлена');
          $('.section-user .select-group').append('<option value="'+response.data.id+'">Новая группа</option>');
          $('.role[name=role]').html($('.section-user .select-group').html());
          $('.section-user .select-group').val(response.data.id);
          user.getDataUserGroup(response.data.id);
        });
      });

      $('.section-user').on('click', '.delete-group', function() {
        admin.ajaxRequest({
          mguniqueurl:"action/dropGroup",
          id: user.tmpRuleRole
        },
        function(response) {
          admin.indication(response.status, response.msg);
          if(response.data == 1) $('.section-user .select-group option[value='+user.tmpRuleRole+']').detach();
          $('.role[name=role]').html($('.section-user .select-group').html());
          $('.section-user .select-group').val(1);
        });
      });

      // Вызов модального окна при нажатии на кнопку изменения пользователя.
      $('.section-user').on('click', '.edit-row', function() {
        user.openModalWindow('edit', $(this).attr('id'));
      });


      // Удаление пользователя.
      $('.section-user').on('click', '.delete-order', function(){
        user.deleteUser($(this).attr('id'));
      });

      // Сохранение пользователя при нажатии на кнопку сохранить в модальном окне.
      $('.section-user').on('click', '#add-user-modal .save-button', function(){
        user.saveUser($(this).attr('id'), !admin.keySave);
      });

      $('.section-user').on('click', '.editPass', function(){
        user.editPassword();
      });
      
      $('.section-user').on('click', '.editLogin', function(){
        user.editLogin();
      });
      
      $('.section-user').on('click', '.cancelEmail', function(){
        user.cancelEmail();
      });

      // Выделить все страницы
      $('.section-user').on('click', '.check-all-page', function () {
        $('.section-user .main-table tbody input').prop('checked', 'checked');
        $('.section-user .main-table tbody input').val('true');
        $('.section-user .main-table tbody tr').addClass('selected');

        $(this).addClass('uncheck-all-page');
        $(this).removeClass('check-all-page');
      });
      // Снять выделение со всех  страниц.
      $('.section-user').on('click', '.uncheck-all-page', function () {
        $('.section-user .main-table tbody input').prop('checked', false);
        $('.section-user .main-table tbody input').val('false');
        $('.section-user .main-table tbody tr').removeClass('selected');
        
        $(this).addClass('check-all-page');
        $(this).removeClass('uncheck-all-page');
      });

      // Устанавливает количество выводимых записей в этом разделе.
      $('.section-user').on('change', '.countPrintRowsUser', function(){
        var count = $(this).val();
        user.printCountRow(count);
      });
      
      // Показывает панель с фильтрами.
      $('.section-user').on('click', '.show-filters', function () {
        $('.filter-container').slideToggle(function () {
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
      
       // Применение выбранных фильтров
      $('.section-user').on('click', '.filter-now', function () {
        user.getUserByFilter();
        // admin.refreshPanel();
        return false;
      });
      
       // Сброс фильтров.
      $('.section-user').on('click', '.refreshFilter', function () {
        admin.clearGetParam();
        // admin.refreshPanel();
        admin.show("users.php", "adminpage", "refreshFilter=1", user.init);
        return false;
      });

      // Выполнение выбранной операции с выделенными пользователями
      $('.section-user').on('click', '.run-operation', function() {
        if ($('.user-operation').val() == 'fulldelete') {
          admin.openModal('#user-remove-modal');
        } else {
          user.runOperation($('.user-operation').val());
        }
      });
      //Проверка для массового удаления
      $('.section-user').on('click', '#user-remove-modal .confirmDrop', function () {
        if ($('#user-remove-modal input').val() === $('#user-remove-modal input').attr('tpl')) {
          $('#user-remove-modal input').removeClass('error-input');
          admin.closeModal('#user-remove-modal');
          user.runOperation($('.user-operation').val(),true);
        } else {
          $('#user-remove-modal input').addClass('error-input');
        }
      });

      $('.section-user').on('click', '.get-order-content', function () {
        user.getOrderContent($(this).data('id'));
      });

      $('.section-user').on('click', '.enter-in-user', function () {
        user.enterInUser($(this).parents('tr').attr('id'));
      });

      $('.section-user').on('change', '.user-operation', function () {
        if($(this).val() == 'changeowner') {
          $('.forChangeOwner').show();
        } else {
          $('.forChangeOwner').hide();
        }
      });

      $('.section-user').on('click', '#add-user-modal .closeModal', function() {
        admin.unlockEntity('#add-user-modal', 'user');
      });

      $('.section-user').on('change', '.ruleGroup[name=order]', function() {
        var value = $(this).val();
        
        if (value > 0) {
          $('.order_status').show();
        } else {
          $('.order_status').hide();
        }
      });
	  
	  $('.section-user').on('click', '.section-hits', function(){
           newbieWay.showHits('usersScenario');
           introJs().start();
       });
    },
    checkBlockIntervalFunction: function() {
      if (!$('#add-user-modal').length) {
        clearInterval(user.checkBlockInterval);
      }
      if(admin.blockTime == 0) {
        admin.setAndCheckBlock('#add-user-modal', 'user');
      }
      admin.blockTime -= 1;
    },
    enterInUser: function(id) {
      admin.ajaxRequest({
        mguniqueurl: "action/enterInUser",
        id: id,
      },      
      function(response) {
        if(response.status == 'success') {
          location.href = response.data;
        }
      });
    },
    getOrderContent: function(id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getUserOrderContent",
        id: id
      },
      (function(response) {
        if(response.data.products != undefined) {
          if(response.data.products.length > 0) {
            $('.orderContentList').html('');
            for(i = 0; i < response.data.products.length; i++) {
              prod = response.data.products[i];
              $('.orderContentList').append('<tr><td>'+prod.code+'</td><td>'+prod.name+'</td><td>'+prod.count+'</td><td>'+prod.number+'</td><td>'+prod.add_date+'</td><td>'+prod.price+'</td></tr>');
            }
            $('.orderContentList').append('<tr><td colspan="5" class="text-right"><b>Итого:</b></td><td><b>'+response.data.summ+'</b></td></tr>');
          } else {
            $('.orderContentList').html('<tr><td colspan="6" class="text-center">Нет приобретённых товаров</td></tr>');
          }
        } else {
          $('.orderContentList').html('<tr><td colspan="6" class="text-center">Нет приобретённых товаров</td></tr>');
        }
        admin.openModal($('#userOrderContentModal'));
      })
      );
    },
    getDataUserGroup: function(id) {
      $('.order_status').hide();
      admin.ajaxRequest({
        mguniqueurl: "action/getDataUserGroup",
        id: id
      }, 
      (function(response) {
        $('.ruleGroup[name=name]').val(admin.htmlspecialchars_decode(response.data.name));
        $('.ruleGroup[name=admin_zone]').val(response.data.admin_zone);
        $('.ruleGroup[name=product]').val(response.data.product);
        $('.ruleGroup[name=page]').val(response.data.page);
        $('.ruleGroup[name=category]').val(response.data.category);
        $('.ruleGroup[name=order]').val(response.data.order);
        if (response.data.order > 0) {
          $('.order_status').show();
        }
        $('.ruleGroup[name=ignore_owners]').val(response.data.ignore_owners);
        $('.ruleGroup[name=order_status]').val(response.data.order_status);
        admin.reloadComboBoxes();
        $('.ruleGroup[name=user]').val(response.data.user);
        $('.ruleGroup[name=plugin]').val(response.data.plugin);
        $('.ruleGroup[name=setting]').val(response.data.setting);
        $('.ruleGroup[name=wholesales]').val(response.data.wholesales);
        $('.section-user .save-user-group').attr('id', response.data.id);
        user.tmpRuleParam = admin.createObject('.ruleGroup');
        user.tmpRuleRole = $('.select-group').val();
      })
      );
    },
    printCountRow: function(count) {
      admin.ajaxRequest({
        mguniqueurl: "action/setCountPrintRowsUser",
        count: count
      },
      (function(response) {
        admin.refreshPanel();
      })
      );
    },
    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового пользователя, либо для редактирования старого.
     */
    openModalWindow: function(type, id) {

      switch (type) {
        case 'edit':{
          $('.user-table-icon').text(lang.TITLE_USER_EDIT);
          $('.user-table-icon').parent().find('i').attr('class', 'fa fa-pencil');
          user.editUser(id);
          $('.editorPas').css('display','none');
          $('.controlEditorPas').css('display','block');
          $('.controlEditorLogin').css('display','block');
          break;
        }
        case 'add':{
          $('.user-table-icon').text(lang.TITLE_USER_NEW);
          $('.user-table-icon').parent().find('i').attr('class', 'fa fa-plus-circle');
          user.clearFileds();
          $('.controlEditorPas').css('display','none');
          $('.controlEditorLogin').css('display','none');
          $('.editorPas').css('display','block');
          $('.editorLogin').css('display','block');
          $('#add-user-modal input[name=email]').parent().parent().hide();
          $('#add-user-modal input[name=phone]').parent().parent().hide();
          
          $('#add-user-modal select[name=tipusers]').val('1');
          var userGroupTip = ['nameyur','adress','inn','kpp','bank','bik','ks','rs'];
          $.each(userGroupTip,function(index,value){
            $('#add-user-modal input[name='+value+']').parent().parent().hide();
          });
          $('#add-user-modal').on('change', 'select[name=tipusers]', function() {
            if($('#add-user-modal select[name=tipusers]').val() == 1)
                $.each(userGroupTip,function(index,value){
                    $('#add-user-modal input[name='+value+']').parent().parent().hide();
                });
            else{
                $.each(userGroupTip,function(index,value){
                    $('#add-user-modal input[name='+value+']').parent().parent().show();
                });
            }  
          });
          break;
        }
        default:{
          user.clearFileds();
          break;
        }
      }

      // Вызов модального окна.
      admin.openModal('#add-user-modal');

    },
    /**
     *  Проверка заполненности полей, для каждого поля прописывается своё правило.
     */
    checkRulesForm: function() {
      $('.errorField').css('display','none');
      $('input').removeClass('error-input');
      var error = false;
      // проверка контактного email только при редактировании пользователя.
      if ($('.controlEditorLogin').css('display')=='block') {
        if($('input[name=email]').val() && !/^[-._а-яА-ЯёЁ\w0-9]+@(?:[а-яА-ЯёЁ\w0-9][-а-яА-ЯёЁ\w0-9]{0,61}\.)+[-а-яА-ЯёЁ\w]{2,20}$/.test($('input[name=email]').val())){
        $('input[name=email]').parent(".columns").find('.errorField').css('display','block');
        $('input[name=email]').css('border-color','red');
        error = true;
        } else {
          $('input[name=email]').css('border-color','#ccc');
        }
      }
      // проверка login_email.
      if(!/^[-._а-яА-ЯёЁ\w0-9]+@(?:[а-яА-ЯёЁ\w0-9][-а-яА-ЯёЁ\w0-9]{0,61}\.)+[-а-яА-ЯёЁ\w]{2,20}$/.test($('input[name=login_email]').val()) || !$('input[name=login_email]').val()){
        // Если нажали (Не использовать Email), то пропускаем проверку Email
        if ($('input[name=login_email]').attr('value')!='cancelEmail' && $('input[name=login_email]').val()!=$('input[name=login_email]').attr('value') || !$('input[name=login_email]').attr('value')) {
          $('input[name=login_email]').attr('value','');
          $('#add-user-modal .editorLogin').css('display','block');
          $('input[name=login_email]').parent(".columns").find('.errorField').css('display','block');
          $('input[name=login_email]').css('border-color','red');
          error = true;
        }
        // Но тогда обязательно проверяем заполенение номера телефона
        if((/^[0-9]$/.test($('input[name=login_phone]').val()) || !$('input[name=login_phone]').val()) && $('input[name=login_email]').attr('value')=='cancelEmail'){
          $('input[name=login_phone]').parent(".columns").find('.errorField').css('display','block');
          $('input[name=login_phone]').css('border-color','red');
          error = true;
        }
        else{
          $('input[name=login_phone]').css('border-color','#ccc');
        }
      } else {
        $('input[name=login_email]').css('border-color','#ccc');
      }

      // если активен блок смены пароля
      if($('.editorPas').css('display')=='block'){
        // проверка пароля, в нем не должно быть спец. символов и он должен быть не менее 5 символов.
        if(!admin.regTest(1,$('input[name=pass]').val()) || !$('input[name=pass]').val() || $('input[name=pass]').val().length<5){
          $('input[name=pass]').parent(".columns").find('.errorField').css('display','block');
          $('input[name=pass]').css('border-color','red');
          error = true;
        } else {
          $('input[name=pass]').css('border-color','#ccc');
        }

        // повторение пароля.
        if($('input[name=passconfirm]').val()!=$('input[name=pass]').val()){
          $('input[name=passconfirm]').parent(".columns").find('.errorField').css('display','block');
          $('input[name=passconfirm]').css('border-color','red');
          error = true;
        } else {
          $('input[name=passconfirm]').css('border-color','#ccc');
        }
      }

      if(error == true){
        return false;
      }

      return true;
    },
    /**
     * Получает данные из формы фильтров и перезагружает страницу
     */
    getUserByFilter: function () {
      var request = $("form[name=filter]").formSerialize();
      admin.show("users.php", "adminpage", request + '&applyFilter=1', user.init);
      return false;
    },
    /**
     * Убирает проверку email_login при создании и редактировании пользователя
     */
    cancelEmail: function(){
      if ($('.controlEditorLogin').css('display')=='block') {
        admin.ajaxRequest({
          mguniqueurl:"action/getUserData",
          id: $('#add-user-modal .save-button').attr('id'),
          },
          function(response) {
            if($('#add-user-modal .save-button').attr('id') == ""){
              $('#add-user-modal input[name=login_email]').val(admin.htmlspecialchars_decode(response.data.login_email));
              $('#add-user-modal input[name=login_email]').attr('value',admin.htmlspecialchars_decode(response.data.login_email));
            }
          }
        );
      } else {
        $('input[name=login_email]').val('');
      }
      $('input[name=login_email]').attr('value','cancelEmail');
      $('.editorLogin .errorField').css('display','none');
      $('.editorLogin input').removeClass('error-input');
    },
    /**
     * Сохранение изменений в модальном окне пользователя.
     * Используется и для сохранения редактированных данных и для сохранения нового продукта.
     * id - идентификатор пользователя, может отсутствовать если производится добавление нового товара.
     */
    saveUser: function(id, closeModal) {
      closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
      // Если поля не верно заполнены, то не отправляем запрос на сервер.
      if(!user.checkRulesForm()){
        return false;
      }

      $('#add-user-modal input[type=text]').each(function() {
          $(this).val(admin.htmlspecialchars($(this).val()));
      });
      // Для автозаполнения полей Email и телефон при добавлении пользователя
      if ($('#add-user-modal .controlEditorLogin').css('display')=='none') {
        $('#add-user-modal input[name=email]').val($('#add-user-modal input[name=login_email]').val());
        $('#add-user-modal input[name=phone]').val($('#add-user-modal input[name=login_phone]').val());
      }
      let birthdayValue = $('#add-user-modal input[name=birthday]').val();
      const birthday = birthdayValue.replace(/\./gi, '-');
      // Пакет характеристик пользователя.
      var packedProperty = {
        mguniqueurl:"action/saveUser",
        id: id,
        login_phone: $('#add-user-modal input[name=login_phone]').val(),
        login_email: $('#add-user-modal input[name=login_email]').val(),
        name: $('#add-user-modal input[name=name]').val(),
        birthday: birthday,
        sname: $('#add-user-modal input[name=sname]').val(),
        pname: $('#add-user-modal input[name=pname]').val(),
        address: $('#add-user-modal textarea[name=address]').val(),
        address_index: $('#add-user-modal input[name=address_index]').val(),
        address_country: $('#add-user-modal input[name=address_country]').val(),
        address_region: $('#add-user-modal input[name=address_region]').val(),
        address_city: $('#add-user-modal input[name=address_city]').val(),
        address_street: $('#add-user-modal input[name=address_street]').val(),
        address_house: $('#add-user-modal input[name=address_house]').val(),
        address_flat: $('#add-user-modal input[name=address_flat]').val(),
        email: $('#add-user-modal input[name=email]').val(),
        phone: $('#add-user-modal input[name=phone]').val(),
        blocked: $('#add-user-modal select[name=blocked]').val(),
        activity: $('#add-user-modal select[name=activity]').val(),
        nameyur: $('#add-user-modal input[name=nameyur]').val(),
        adress: $('#add-user-modal input[name=adress]').val(),
        inn: $('#add-user-modal input[name=inn]').val(),
        kpp: $('#add-user-modal input[name=kpp]').val(),
        bank: $('#add-user-modal input[name=bank]').val(),
        bik: $('#add-user-modal input[name=bik]').val(),
        ks: $('#add-user-modal input[name=ks]').val(),
        rs: $('#add-user-modal input[name=rs]').val(),
        role: $('#add-user-modal select[name=role]').val(),
        op: admin.createObject('#add-user-modal .userOpFields'),
      };

      if ($('#add-user-modal [name="pass"]:visible').length && $('#add-user-modal [name="passconfirm"]:visible').length) {
        packedProperty.pass = $('#add-user-modal input[name=pass]').val();
      }

      // отправка данных на сервер для сохранения
      admin.ajaxRequest(packedProperty,
        (function(response) {
          admin.indication(response.status, response.msg);

          if(response.status=='error'){

            return false;
          }

          // Закрываем окно
          if (closeModal) {
            admin.closeModal('#add-user-modal');
            admin.refreshPanel();
          } else {
            $('#add-user-modal').attr('data-refresh', 'true');
          }
        })
      );
    },
    /**
     * Получает данные о пользователе с сервера и заполняет ими поля в окне.
     */
    editUser: function(id) {
      admin.ajaxRequest({
        mguniqueurl:"action/getUserData",
        id: id
      },
      user.fillFields(),
      $('.widget-table-body .add-user-form')
      );
    },
    /**
     * Удаляет пользователя из БД сайта и таблицы в текущем разделе
     */
    deleteUser: function(id) {
      if(confirm(lang.DELETE+'?')){
        admin.ajaxRequest({
          mguniqueurl:"action/deleteUser",
          id: id
        },
        function(response) {
          admin.indication(response.status, response.msg);
          if (response.status == 'success') {
            admin.refreshPanel();
          }
        }
        );
      }
    },
   /**
    * Заполняет поля модального окна данными
    */
    fillFields:function() {
      return (function(response) {
        $('#add-user-modal input').removeClass('error-input');
        $('#add-user-modal input[name=email]').val(admin.htmlspecialchars_decode(response.data.email));
        $('#add-user-modal input[name=login_email]').val(admin.htmlspecialchars_decode(response.data.login_email));
        $('#add-user-modal input[name=login_email]').attr('value',admin.htmlspecialchars_decode(response.data.login_email));
        $('#add-user-modal input[name=login_phone]').val(admin.htmlspecialchars_decode(response.data.login_phone));
        $('#add-user-modal input[name=login_phone]').attr('value',admin.htmlspecialchars_decode(response.data.login_phone));
        $('#add-user-modal input[name=name]').val(admin.htmlspecialchars_decode(response.data.name));
        $('#add-user-modal input[name=sname]').val(admin.htmlspecialchars_decode(response.data.sname));
        $('#add-user-modal input[name=pname]').val(admin.htmlspecialchars_decode(response.data.pname));
        $('#add-user-modal input[name=birthday]').val('');
        if (response.data.birthday && response.data.birthday != '00-00-0000' ) {
          var date = response.data.birthday.split('-');
          date = new Date(date[0], date[1]-1, date[2]);
          var day = date.getDate();
          day = (day < 10) ? '0' + day : day;
          var month = date.getMonth()+1;
          month = (month < 10) ? '0' + month : month;
          var year = date.getFullYear();
          var formattedDate = day + '.' + month + '.' + year;
          $('input[name=birthday]').val(formattedDate);
        }
        $('#add-user-modal input[name=phone]').val(admin.htmlspecialchars_decode(response.data.phone));
        $('#add-user-modal textarea[name=address]').val(response.data.address);
        $('#add-user-modal input[name=address_index]').val(response.data.address_index);
        $('#add-user-modal input[name=address_country]').val(response.data.address_country);
        $('#add-user-modal input[name=address_region]').val(response.data.address_region);
        $('#add-user-modal input[name=address_city]').val(response.data.address_city);
        $('#add-user-modal input[name=address_street]').val(response.data.address_street);
        $('#add-user-modal input[name=address_house]').val(response.data.address_house);
        $('#add-user-modal input[name=address_flat]').val(response.data.address_flat);
        $('#add-user-modal input[name=nameyur]').val(admin.htmlspecialchars_decode(response.data.nameyur));
        $('#add-user-modal input[name=adress]').val(admin.htmlspecialchars_decode(response.data.adress));
        $('#add-user-modal input[name=inn]').val(admin.htmlspecialchars_decode(response.data.inn));
        $('#add-user-modal input[name=kpp]').val(admin.htmlspecialchars_decode(response.data.kpp));
        $('#add-user-modal input[name=bank]').val(admin.htmlspecialchars_decode(response.data.bank));
        $('#add-user-modal input[name=bik]').val(admin.htmlspecialchars_decode(response.data.bik));
        $('#add-user-modal input[name=ks]').val(admin.htmlspecialchars_decode(response.data.ks));
        $('#add-user-modal input[name=rs]').val(admin.htmlspecialchars_decode(response.data.rs));
        
        var userGroupTip = ['nameyur','adress','inn','kpp','bank','bik','ks','rs'];
        if(response.data.nameyur||response.data.adress||response.data.inn
                ||response.data.kpp||response.data.bank||response.data.bik
                ||response.data.ks||response.data.rs){
            $.each(userGroupTip,function(index,value){
                $('#add-user-modal select[name=tipusers]').val(0);
                $('#add-user-modal input[name='+value+']').parent().parent().show();
            });
        } else {
            $.each(userGroupTip,function(index,value){
                $('#add-user-modal select[name=tipusers]').val(1);
                $('#add-user-modal input[name='+value+']').parent().parent().hide();
            });
        }
        $('#add-user-modal').on('change', 'select[name=tipusers]', function() {
            if($('#add-user-modal select[name=tipusers]').val() == 1)
                $.each(userGroupTip,function(index,value){
                    $('#add-user-modal input[name='+value+']').parent().parent().hide();
                });
            else{
                $.each(userGroupTip,function(index,value){
                    $('#add-user-modal input[name='+value+']').parent().parent().show();
                });
            }  
        });

        $('#add-user-modal .activity option[value="'+response.data.activity+'"]').prop("selected", "selected");
        $('#add-user-modal select[name=role] option[value="'+response.data.role+'"]').prop("selected", "selected");
        $('#add-user-modal select[name=blocked] option[value="'+response.data.blocked+'"]').prop("selected", "selected");
        $('#add-user-modal .ip-registration').html('');
        if (response.data.ip != '') {
          $('#add-user-modal .ip-registration').html('<p>ip: '+response.data.ip+'</p>');
        }
        $('#add-user-modal .save-button').attr('id',response.data.id);
        $('#add-user-modal .errorField').css('display','none');
        $('#add-user-modal .editPass').text('Изменить');

        $('#add-user-modal .op-user-modal').html(response.data.htmlOp);

        admin.blockTime = 0;
        admin.setAndCheckBlock('#add-user-modal', 'user');
      });
    },
   /**
    * Чистит все поля модального окна
    */
    clearFileds:function() {
      $('#add-user-modal input[name=email]').val('');
      $('#add-user-modal input[name=login_phone]').val('');
      $('#add-user-modal input[name=login_email]').val('');
      $('#add-user-modal input[name=pass]').val('');
      $('#add-user-modal input[name=name]').val('');
      $('#add-user-modal input[name=passconfirm]').val('');
      $('#add-user-modal input[name=sname]').val('');
      $('#add-user-modal input[name=pname]').val('');
      $('#add-user-modal input[name=birthday]').val('');
      $('#add-user-modal textarea[name=address]').val('');
      $('#add-user-modal input[name=address_index]').val('');
      $('#add-user-modal input[name=address_country]').val('');
      $('#add-user-modal input[name=address_region]').val('');
      $('#add-user-modal input[name=address_city]').val('');
      $('#add-user-modal input[name=address_street]').val('');
      $('#add-user-modal input[name=address_house]').val('');
      $('#add-user-modal input[name=address_flat]').val('');
      $('#add-user-modal input[name=nameyur]').val('');
      $('#add-user-modal input[name=adress]').val('');
      $('#add-user-modal input[name=inn]').val('');
      $('#add-user-modal input[name=kpp]').val('');
      $('#add-user-modal input[name=bank]').val('');
      $('#add-user-modal input[name=bik]').val('');
      $('#add-user-modal input[name=ks]').val('');
      $('#add-user-modal input[name=rs]').val('');
      $('#add-user-modal input[name=phone]').val('');
      $('#add-user-modal select[name=blocked]').val('');
      $('#add-user-modal select[name=activity]').val('');
      $('#add-user-modal select[name=role]').val('');
      $('#add-user-modal .ip-registration').html('');
      $('#add-user-modal .save-button').attr('id','');
      $('#add-user-modal .editorPas').css('display', 'none');
      $('#add-user-modal .role option[value="2"]').prop("selected", "selected");
      $('#add-user-modal select[name=blocked] option[value="0"]').prop("selected", "selected");
      $('#add-user-modal select[name=activity] option[value="1"]').prop("selected", "selected");
      // Стираем все ошибки предыдущего окна, если они были
      $('#add-user-modal .errorField').css('display','none');
      $('#add-user-modal .error-input').removeClass('error-input');
      $('#add-user-modal .op-user-modal').html($('.opFieldsForNewUser').html());
      includeJS(mgBaseDir+'/mg-core/script/jquery.maskedinput.min.js');
    },
   /**
    * открывает блок для смены пароля
    */
    editPassword: function() {
      $('#add-user-modal .editorPas').slideToggle('show', function() {

        $('#add-user-modal .editorPas').css('display')=='block'
          ? $('#add-user-modal .editPass').text(lang.USER_PASS_NO_EDIT)
          : $('#add-user-modal .editPass').text(lang.USER_PASS_EDIT);
        }
      );
    },
    /**
    * открывает блок для смены авторизационных данных
    */
    editLogin: function() {
      $('#add-user-modal .editorLogin').slideToggle('show', function() {
        if ($('#add-user-modal .editorLogin').css('display')=='block') {
          $('#add-user-modal .editLogin').text(lang.USER_PASS_NO_EDIT)
        } else {
          $('#add-user-modal input[name=login_email]').val($('#add-user-modal input[name=login_email]').attr('value'));
          $('#add-user-modal input[name=login_phone]').val($('#add-user-modal input[name=login_phone]').attr('value'));
          $('#add-user-modal .editLogin').text(lang.USER_PASS_EDIT);
        }
      });
    },
    /**
     * Выполняет выбранную операцию со всеми отмеченными пользователями
     * operation - тип операции.
     */
    runOperation: function(operation, skipConfirm) { 
      if(typeof skipConfirm === "undefined" || skipConfirm === null){skipConfirm = false;}
      var users_id = [];
      $('.main-table tbody tr').each(function() {              
        if($(this).find('input').prop('checked')) {  
          users_id.push($(this).attr('id'));
        }
      });  

      var param;
      if(operation == 'changeowner') {
        param = $('.forChangeOwner').val();
      }

      if (skipConfirm || confirm(lang.RUN_CONFIRM)) {        
        admin.ajaxRequest({
          mguniqueurl: "action/operationUser",
          operation: operation,
          users_id: users_id,
          param: param
        },
        function(response) {     
          admin.indication(response.status, response.msg);
          if(response.data.filecsv) {
            setTimeout(function() {
              if (confirm('Файл с выгрузкой создан в временной директории сайта под именем: '+response.data.filecsv+'. Желаете скачать сейчас?')) {
              location.href = mgBaseDir+'/'+response.data.filecsvpath;
            }}, 2000);            
           }
          admin.refreshPanel();  
         
        }
        );
      }
    }
  };
})();