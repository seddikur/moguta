var settings_storage = {
	editMode: undefined,
	item: undefined,

	init: function() {
		// Сохранение настроек storage
		$('#tab-storage-settings').on('click', '.save-button', function() {
			settings_storage.saveStorage(true);
		});

		// Сохранение настроек storage
		$('#tab-storage-settings').on('click', '.on-off-storage', function() {
			settings_storage.onOffStorage($('#tab-storage-settings .on-off-storage').prop('checked'));
		});

		// Открытие модали для создания storage
		$('#tab-storage-settings').on('click', '.addStorage', function() {
			settings_storage.editMode = 'add';
			$('#tab-storage-settings input, #tab-storage-settings textarea').val('');
			$('#tab-storage-settings #storage-edit-modal input').removeClass('error-input');
			admin.openModal('#storage-edit-modal');
		});

		// Перерасчёт общего количества товара по складам
		$('#tab-storage-settings').on('click', '.recalculateStorages', function() {
			settings_storage.recalculate();
		});
        
        //Сохранение выводить ли в публичку склад
        $('#tab-storage-settings').on('click', '.show-public', function() {
          var status = $(this).attr('status');
          if(status == 'false'){
            $(this).attr('status', 'true');
            $(this).addClass('active');
          }else if(status == 'true'){
            $(this).attr('status', 'false');
            $(this).removeClass('active');
          } 
          var data = {
            showPublic : $(this).attr('status'),
            storageId : $(this).attr('id')
          }
          admin.ajaxRequest({
			mguniqueurl: "action/saveStorageShowPublic", // действия для выполнения на сервере    
			data: data         
            }, function (response) {
                if(response.status == "success"){
                  admin.indication(response.status, response.msg);  
                }
              } 
            ); 
        });
                
        //Модалка для редактирования настроек списывания со складов
        $('#tab-storage-settings').on('click', '.settingStorage', function () {
          admin.openModal('#storage-settings-modal');
          admin.sortableMini(".storage-table", "settings_storage.renuminationStorageList()");
        });
        
        // Сохранение настроек списывания со складов
		$('#tab-storage-settings').on('click', '.save-storage-settings', function() {
          settings_storage.saveStorageSettigs();
		});
        
        $('#write-off-procedure').on('change', function(){
          var writeOffProc = $(this).val();
          if(writeOffProc == 2){
            $('#write_off_main_storage').hide();
            $('#main_storage_item').hide();
            $('#storage_order').show();
          }else if(writeOffProc == 3){
            $('#main_storage_item').show();
            $('#write_off_main_storage').show();
            if($('#write_off_main_storage-select').val() == 2){
              $('#storage_order').show();
            }else{
              $('#storage_order').hide(); 
            }
          }else{
            $('#main_storage_item').hide();
            $('#write_off_main_storage').hide();
            $('#storage_order').hide();
          }
        });
        
        $('#write_off_main_storage-select').on('change', function(){
          if($(this).val() == 1){
            $('#storage_order').hide(); 
          }else if($(this).val() == 2){
            $('#storage_order').show();
          }
        });
      
		// удаление storage
		$('#tab-storage-settings').on('click', '.fa-trash', function() {
			if(confirm(lang.ADMIN_LOCALE_3)) {
				$(this).parents('.storage-item').detach();
				settings_storage.saveStorage(false);
			}
		});

		// редактирование storage
		$('#tab-storage-settings').on('click', '.fa-pencil', function() {
			settings_storage.editMode = 'edit';
			settings_storage.item = $(this).parents('.storage-item');
			$('#tab-storage-settings input').val('');
			$('#tab-storage-settings [name=id]').val(settings_storage.item.find('.id').html());
			$('#tab-storage-settings [name=name]').val(admin.htmlspecialchars_decode(settings_storage.item.find('.name').html()));
			$('#tab-storage-settings [name=adress]').val(admin.htmlspecialchars_decode(settings_storage.item.find('.adress').html()));
			$('#tab-storage-settings [name=desc]').val(admin.htmlspecialchars_decode(settings_storage.item.find('.desc').html()));
			var checkboxVal = admin.htmlspecialchars_decode(settings_storage.item.find('.pickupPoint').html());
			if(checkboxVal == 'true'){
				$('#tab-storage-settings [name=pickupPoint]').prop('checked', true);
			}else{
				$('#tab-storage-settings [name=pickupPoint]').prop('checked', false);
			}
			admin.openModal('#storage-edit-modal');
		});
	},
    //Перенумирация таблици при изменении позиций складов
    renuminationStorageList(){
      var storageTable = $('.storage-table').find('tr');
      for(let i=0; i<storageTable.length; i++){
        $(storageTable[i]).find('.numeration').html(i+1);
      }
    },
    //Сохранение настроек складов
	saveStorage: function(errorCheck, closeModal) {
		closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
		errorCheck = typeof errorCheck !== 'undefined' ? errorCheck : true;
		if(errorCheck) {
			var error = false;
			$('#tab-storage-settings #storage-edit-modal input:not([name=id])').each(function() {
				if($(this).attr('type') !== 'checkbox'){
					if($(this).val() == '' ) {
						$(this).addClass('error-input');
						error = true;
					} else {
						$(this).removeClass('error-input');
					}
				}
			});
			if(error) return false;
		}
		if($('#tab-storage-settings [name=id]').val() == '') {
			$('#tab-storage-settings [name=id]').val(Date.now());
		}
	  	var data = {};
	  	if(settings_storage.editMode == 'add') {
	  		$('#tab-storage-settings .toDel').detach();
	  		settings_storage.addRow();
	  	} 
	  	if(settings_storage.editMode == 'edit') {
	  		settings_storage.item.find('.id').html($('#tab-storage-settings [name=id]').val());
	  		settings_storage.item.find('.name').html(admin.htmlspecialchars($('#tab-storage-settings [name=name]').val()));
	  		settings_storage.item.find('.adress').html(admin.htmlspecialchars($('#tab-storage-settings [name=adress]').val()));
	  		settings_storage.item.find('.desc').html(admin.htmlspecialchars($('#tab-storage-settings [name=desc]').val()));
				settings_storage.item.find('.pickupPoint').html(admin.htmlspecialchars($('#tab-storage-settings [name=pickupPoint]').val()));
	  	}
	  	$('.storages-list .storage-item').each(function(index) {
	  		if(($(this).find('.name').html() != undefined)&&($(this).find('.name').html() != '')) {
	  			data[index] = {};
	  			data[index]['id'] = $(this).find('.id').html();
	  			data[index]['name'] = admin.htmlspecialchars_decode($(this).find('.name').html());
	  			data[index]['adress'] = admin.htmlspecialchars_decode($(this).find('.adress').html());
	  			data[index]['desc'] = admin.htmlspecialchars_decode($(this).find('.desc').html());
				data[index]['pickupPoint'] = admin.htmlspecialchars_decode($(this).find('.pickupPoint').html());
                data[index]['showPublic'] = admin.htmlspecialchars_decode($(this).find('.show-public').attr('status'));
	  		}
		  });
		admin.ajaxRequest({
			mguniqueurl: "action/saveStorage", // действия для выполнения на сервере    
			data: data         
		},      
		function(response) {
			if (jQuery.isEmptyObject(data)) {
				settings_storage.onOffStorage(false);
			} else {
                if(response.data == 'delete'){
                  alert('Вы удалили склад. Настройки списывания со складов сброшены до дефолтных. Рекомендуем проверить раздел "Настройки складов"');
                }else if(response.data == 'add'){
                  alert('Вы добавили склад. Настройки списывания со складов сброшены до дефолтных. Рекомендуем проверить раздел "Настройки складов"');
                } 
				admin.indication(response.status, response.msg);
				if (closeModal) {
					admin.closeModal('#storage-edit-modal');
					admin.refreshPanel();     
				} else {
					$('#storage-edit-modal').attr('data-refresh', 'true');
				}
			}
		});
	},
	addRow: function() {
		$('.storages-list').append('\
			<tr class="storage-item">\
				<td class="id" style="display:none;">'+$('#tab-storage-settings [name=id]').val()+'</td>\
				<td class="desc" style="display:none;">'+admin.htmlspecialchars($('#tab-storage-settings [name=desc]').val())+'</td>\
				<td class="pickupPoint" style="display:none;">'+admin.htmlspecialchars($('#tab-storage-settings [name=pickupPoint]').val())+'</td>\
			  	<td class="name">'+admin.htmlspecialchars($('#tab-storage-settings [name=name]').val())+'</td>\
			  	<td class="adress">'+admin.htmlspecialchars($('#tab-storage-settings [name=adress]').val())+'</td>\
			  	<td class="text-right action-list">\
			  	  	<a role="button" href="javascript:void(0);" class="fa fa-pencil" style="color:#444;margin-right:5px;"></a>\
			  	  	<a role="button" href="javascript:void(0);" class="fa fa-trash"></a>\
			  	</td>\
			</tr>');
	},
	onOffStorage: function(flag) {
		admin.ajaxRequest({
		  	mguniqueurl: "action/onOffStorage", // действия для выполнения на сервере    
		  	data: flag
		},      
		function(response) {
		  	admin.indication(response.status, response.msg);  
		  	location.reload();
		});
	},
	recalculate: function(reset = 1) {
		const recalculateWrapper = $('#tab-storage-settings .recalculate');
		const recalculateProgressWrapper = recalculateWrapper.find('.recalculate-progress');
		const recalculateInnerBar = recalculateProgressWrapper.find('.recalculate-progress__bar-inner');
		const recalculatePercentsText = recalculateProgressWrapper.find('.recalculate-progress__text');
		const recalculateButton = recalculateWrapper.find('.recalculate__button');
		const recalculateSpinnerIcon = recalculateButton.find('i.fa-refresh');
		if (reset) {
			admin.indication('warning', 'Не уходите со страницы до завершения процесса');
			recalculatePercentsText.html('0%');
			recalculateInnerBar.css('width', '0%');
			recalculateButton.prop('disabled', true);
			recalculateSpinnerIcon.addClass('recalculateStoragesSpin');
			recalculateProgressWrapper.show();
		}
		$.ajax(
			{
				type: 'POST',
				url: 'ajax',
				data: {
					mguniqueurl: 'action/recalculateStorages',
					reset,
				},
				cache: false,
				dataType: 'json',
				success: function(response) {
					if (response.status === 'success') {
						if (response.data.complete !== undefined && response.data.complete) {
							recalculateWrapper.hide();
							admin.indication(response.status, response.msg);
							return;
						}

						if (response.data.percents) {
							recalculatePercentsText.html(response.data.percents+'%');
							recalculateInnerBar.css('width', response.data.percents+'%');
							return settings_storage.recalculate(0);
						}
					}
					recalculatePercentsText.html('0%');
					recalculateInnerBar.css('width', '0%');
					recalculateButton.prop('disabled', false);
					recalculateSpinnerIcon.removeClass('recalculateStoragesSpin');
					recalculateProgressWrapper.hide();
					admin.indication(response.status, response.msg);
					return;
				},
				error: function (request, status, error) {
					recalculatePercentsText.html('0%');
					recalculateInnerBar.css('width', '0%');
					recalculateButton.prop('disabled', false);
					recalculateSpinnerIcon.removeClass('recalculateStoragesSpin');
					recalculateProgressWrapper.hide();
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
			}
		);
	},
    saveStorageSettigs:function(){
      var writeOffProc = $('#write-off-procedure').val(); //Какой использется алгоритм списывания
      var storagesOrderArray = []; //Массив порядка списывания со складов
      var storagesAlgorithmWithoutMain = ''; //Какой алгоритм использовать, если на списание происходит с основного склада, а на нем нету товара
      var mainStorageItem = ''; //Основной склад для списания
      if(writeOffProc == 2){
        var storages = $('.storage_order');
        for(var i=0; i<storages.length; i++){
          storagesOrderArray.push({
            'storagId': $(storages[i]).data('stroge-id'),
            'storageNumver' : i
          }); 
        }
      }
      if (writeOffProc == 3) {
        storagesAlgorithmWithoutMain = $('#write_off_main_storage-select').val();
        mainStorageItem = $('#main_stroage').val();
        if (storagesAlgorithmWithoutMain == 2) {
          var storages = $('.storage_order');
          for(var i=0; i<storages.length; i++){
            storagesOrderArray.push({
              'storagId': $(storages[i]).data('stroge-id'),
              'storageNumver' : i+1
            }); 
          }
        }
	  }
      var data = {
        writeOffProc:writeOffProc,
        storagesAlgorithmWithoutMain:storagesAlgorithmWithoutMain,
        storagesOrderArray:storagesOrderArray,
		mainStorage:mainStorageItem,
		useOneStorage:$('#useOneStorage').prop('checked')
      }         
      admin.ajaxRequest({
        mguniqueurl: "action/saveStorageSettigs",
        data:data
        },
        function(response){
          admin.closeModal('#storage-settings-modal');
        });
      
    }
};