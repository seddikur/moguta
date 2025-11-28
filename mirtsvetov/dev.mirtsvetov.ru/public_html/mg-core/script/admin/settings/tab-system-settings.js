var settings_system = (function () {
	return {
		backupBeforeUpdate: 'false',
		init: function() {
			// Загрузка таблицы с бэкапами
			$('#tab-system-settings').on('click', '.loadBackups', function() {
				$('#tab-system-settings .backupTable tbody').html(lang.LOADING);
				$('#tab-system-settings .loadBackups').removeClass('loadBackups');
				Backup.drawTable();
			});

			// Загрузка информации о сервере
			$('#tab-system-settings').on('click', '.loadServerInfo', function() {
				var container = $(this).closest('.accordion-item').find('.accordion-content');
				admin.ajaxRequest({
					mguniqueurl: "action/callBackupMethod",
					func: 'checkSystem',
				},
				function (response) {
					container.html(response.data);
					$('#tab-system-settings .loadServerInfo').removeClass('loadServerInfo');
				});
			});

			// Сохранение настроек отладки

			$('#tab-system-settings').on('click', ' .debugSave', function() {
                                var debugDisablePlugin = '';
                                var debugDisablePluginName = '';
                                $('.section-settings #debugDisablePlugin option:selected').each(function(){
                                    debugDisablePlugin += this.value + '|';
                                    debugDisablePluginName += this.text + ', ';
                                });
				admin.ajaxRequest({
						mguniqueurl: "action/setDebugVars",
						data: {
							debugDisablePlugin: debugDisablePlugin,
							debugDisablePluginName: debugDisablePluginName,
							debugDisableTemplate: $('.section-settings #debugDisableTemplate').prop('checked'),
							debugDisableUserCss: $('.section-settings #debugDisableUserCss').prop('checked'),
							debugLogSQL: $('.section-settings #debugLogSQL').prop('checked'),
							debugLogger: $('.section-settings #debugLogger').prop('checked')
						}
					},
					function(response) {
						if(response.data == 'false_log'){
							admin.indication('error', 'ERROR');
							$('.section-settings #debugLogger').prop('checked', false);
						}else {
							admin.indication(response.status, lang.SUCCESS_SAVE);
						}
					});

			});
                        
            // Снять выделения всех плагинов в настройках отладки
			$('#tab-system-settings').on('click', '.debugPluginCheckNO', function() { 
                $('#debugDisablePlugin option').attr("selected", "selected");
                $('#debugDisablePlugin').parent().find("li.opt").addClass('selected');
                $('#debugDisablePlugin').parent().find(".CaptionCont.SelectBox span").removeClass('placeholder').text('('+$('#debugDisablePlugin option').length+') Выбрано');
			});

            // Выбрать все плагины в настройках отладки
			$('#tab-system-settings').on('click', '.debugPluginCheckAll', function() { 
            	$('#debugDisablePlugin option').attr("selected", false);
            	$('#debugDisablePlugin').parent().find("li.opt").removeClass('selected');
            	$('#debugDisablePlugin').parent().find(".CaptionCont.SelectBox span").addClass('placeholder').text('Не выбрано');
			});
                        
			// Редактирование ключа
			$('#tab-system-settings').on('click', '.edit-key', function() {
				$('.section-settings input[name="licenceKey"]').fadeIn();
				$('.section-settings .save-settings-system').fadeIn();        
				$(this).hide();
			});

			// Сохранение ключа
			$('#tab-system-settings').on('click', '.setKey', function() {
				admin.ajaxRequest({
					mguniqueurl: "action/setKey",
					key: $('.licenseKey').val(),
				},
				function(response) {
					if(response == true) {
						location.reload();
					} else {
						$('.keyError').show();
					}
				});
				return false;
			});

			// для клика по чекбоксу - закрытия сайта от посетителей, особый обрабочик
			$('#tab-system-settings').on('click', '.downtime-check', function() {        
				var tabName = $(this).parents('.main-settings-container').attr('id');      
				
				var obj ={downtime: "false", downtime1C: "false", consentData: "false"};
				if($('[name=downtime1C]').prop('checked')) {
					obj.downtime1C = "true";
				}
				if($('[name=downtime]').prop('checked')) {
					obj.downtime = "true";
				}
				if($('[name=consentData]').prop('checked')) {
					obj.consentData = "true";
				}
					 
				admin.ajaxRequest({
					mguniqueurl: "action/editSettings",
					options: obj
				},
				function(response) {
					admin.indication(response.status, response.msg);        
					$('.tabs-content').animate({opacity: "hide"}, 1000);
					$('.tabs-content').animate({opacity: "show"}, "slow");
					admin.refreshPanel();
				});
			});

			//Обработка нажатия кнопки проверить версию
			$('#tab-system-settings').on('click', '.clearLastUpdate', function() {         
				admin.ajaxRequest({
					mguniqueurl: "action/clearLastUpdate",         
				},
				function(response) {
					admin.indication(response.status, response.msg);
					if(response.status == 'success') {
						window.location = mgBaseDir+"/mg-admin/";
					}
				});
			});

			//Обработка нажатия кнопки показать файлы логов
			$('#tab-system-settings').on('click', '.js-logData-view', function() {         
				admin.openUploader(null, null, 'temp');
			});

			//Обработка нажатия кнопки удалить все файлы логов
			$('#tab-system-settings').on('click', '.js-logData-clear', function() {         
				admin.ajaxRequest({
					mguniqueurl: "action/logDataСlear",         
				},
				function(response) {
					admin.indication(response.status, response.msg);
					if(response.status == 'success') {
						$('#tempDirSize').remove();
						window.location = mgBaseDir+"/mg-admin/";
					}
				});
			});

			$('#tab-system-settings').on('click', '.checkEngine', function() {
				$('#tab-system-settings .checkEngineDiv').html('Идет загрузка..');
				admin.ajaxRequest({
					mguniqueurl: "action/checkEngine",         
				},
				function(response) {

					html = '';
					for(var key in response.data.checkEngine){
						html += '<div class="row sett-line js-settline-toggle server-info__row">'
						html += '<div class="large-4 small-4 columns">'
						html +='<div class="dashed"><span>'+settings_system.renameKey(key)+'</span></div>'
						html +='</div>'
						html += '<div class="large-8 small-8 columns">'+settings_system.renameVal(response.data.checkEngine[key])+'</div>'
						html += '</div>';
					}
					//html += '</ul>';
					//html += '<div class="checkEngineDiv-header"><span>Результат диагностики</span></div>';
					//html += '<hr>';
					$('#tab-system-settings .checkEngineDiv').html(html);
					$('#tab-system-settings .checkEngineDiv').append(response.data.settingsEngine);
				});	
			});

			$('#tab-system-settings').on('click', '.resetSettings', function() {
				if(confirm('Восстановить стандартные настройки?')){
					admin.ajaxRequest({
						mguniqueurl: "action/resetSettings",         
					},
					function(response){
						if(response.status == "success") location.reload();
					});
				}else{
					return false;
				}
			});

			$('#tab-system-settings').on('click', '.customSettings', function() {
				if(confirm('Восстановить кастомные настройки?')){
					admin.ajaxRequest({
						mguniqueurl: "action/customSettings",         
					},
					function(response){
						if(response.status == "success") location.reload();
					});
				}else{
					return false;
				}
			});


			//Обработка нажатия кнопки Приступить к обновлению
			$('#tab-system-settings').on('click', '.update-now', function() {
				$(".loading-update-step-1").show();
				$(".step-1-info").hide();

				var buttonDownload = $(this);
				buttonDownload.hide();

				$('.start-update').hide();
				$(".step-process-info").show();
				$(".step-process-info").text(lang.WHAITING_UPDATE);
				$('.step-eror-info').hide();
				var version = $("#lVer").text();

				admin.ajaxRequest({
					mguniqueurl: "action/preDownload",
					version: version
				},

				function(response) {
					$(".loading-update-step-1").hide();
					$(".step-process-info").hide();
					$(".step-1-info").show();

					if('error'==response.status) {
						admin.indication(response.status, response.msg);

						$('.step-eror-info').show();
						$('.step-eror-info').text(response.msg);
						buttonDownload.show();
						$('.start-update').show();
					} else {
						admin.indication(response.status, response.msg);

						$('.step-update-li-1').addClass('current');
						$('.step-update-li-1').addClass('completed');
						$('.step-update-li-2').removeClass('current');
						$('.update-archive').show();
						$("#lVer").html(version);
						$(".step-block .step1").hide();
						$(".step-block .step2").show();

						settings_system.backupBeforeUpdate = response.data.backupBeforeUpdate;
						if (settings_system.backupBeforeUpdate == 'true') {
							$('#tab-system-settings .regularUpdate').hide();
							$('#tab-system-settings .backupAndUpdate').show();
						} else {
							$('#tab-system-settings .regularUpdate').show();
							$('#tab-system-settings .backupAndUpdate').hide();
						}
					}
					admin.initToolTip();
				});
			});
			//Обработка нажатия кнопки Установить обновление
			$('#tab-system-settings').on('click', '.update-archive', function() {
				if (settings_system.backupBeforeUpdate == 'true') {
					Backup.callback = 'settings_system.continueUpdate';					
					$('#tab-system-settings .createNewBackup[data-type=core]').trigger('click', ['skipConfirm']);
					$(".loading-update-step-3").show();
					$('#tab-system-settings .backupTable').closest('.accordion-item').find('.accordion-content').slideUp();
				} else {
					var version = $("#lVer").text();
					$(".loading-update-step-2").show();
					$(".step-2-info").hide();
					var buttonArchive = $(this);
					buttonArchive.hide();
					$(".step-process-info").show();
					$(".step-process-info").text(lang.CHANGES_IN_PROGRESS);

					admin.ajaxRequest({
						mguniqueurl: "action/postDownload",
						version: version
					},
					function(response) {
						admin.indication(response.status, response.msg);
						$(".loading-update-step-2").hide();
						if('error'==response.status) {
							admin.indication(response.status, response.msg);
							$('.error-update').remove();
							
							$('.step-2-info .step-eror-info').show();
							$('.step-2-info .step-eror-info').html(response.msg+ ' <a href="'+admin.SITE+'/mg-admin'+'">'+lang.START_UPDATE+'</a>');
							
							$('.step2 .step-process-info').html(response.msg+ ' <a href="'+admin.SITE+'/mg-admin'+'">'+lang.START_UPDATE+'</a>');      
						} else {              
							admin.indication(response.status, response.msg);
							$('.step-update-li-2').addClass('current');
							// $('.step-update-li-2').addClass('completed');
							$('.step-update-li-3').removeClass('current');
							$(".step-info").hide();
							$(".step-process-info").text(lang.ENDING_UPDATE);
							$(".loading-update-step-2").show();
							setTimeout(function() { 
								window.location = mgBaseDir+"/mg-admin/";
							}, 3000);
						}
					});
					return false;
				}
			}); 
		},
		continueUpdate: function() {
			settings_system.backupBeforeUpdate = 'false';
			$('#tab-system-settings .update-archive:visible').click();
		},
		renameKey:function(key){
			switch(key){
				case 'ZIP':
					key = 'Создание Zip архива:';
					break;
				case 'GD':
					key = 'Подключение библиотеки GD:';
					break;
  				case 'xmlwriter':
					key = 'Запись в xml';
					break;						
				case 'xmlreader':
					key = 'Чтение xml';
					break;
				case 'uploads':
					key = 'Права на папку "uploads"';
					break;	
				case 'mg-cache':
					key = 'Права на папку "mg-cache"';
					break;	
				case 'file-create':
					key = 'Права на файл при создании';
					break;						
				case 'chmod':
					key = 'Можно ли изменять права на сервере';
					break;	
				case 'create-dir':
					key = 'Можно ли создать директорию';
					break;
				case 'file-write':
					key = 'Можно ли создать файл';
					break;
				case 'file-chmod':
					key = 'Можно ли изменять права на файл';
					break;	
				case 'file-del':
					key = 'Можно ли иудалить файлы';
					break;	
				case 'dir-del':
					key = 'Можно ли изменять удалить директорию';
					break;																															
			}
			return key;
		},

		renameVal:function(val){
			switch(val){
				case 1:
					val = '<span style="color:green">Да</span>';
					break;
				case 0:
					val = '<span style="color:red">Нет</span>';
					break;					
			}
			return val;
		}
	};
})();