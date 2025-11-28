/**
 * Модуль для бэкапов
 */
var Backup = (function () {
	return {
		block: false,
		createBlock: false,
		coreFilesFound: false,
		totalTables: 0,
		totalFiles: 0,
		zipName: '',
		partSizes: [],
		callback: '',

		init: function() {
			Backup.callback = '';
			Backup.initEvents();
		},
		initEvents: function() {

			$('#tab-system-settings').on('click', '.calcDumpSize', function() {
				if (Backup.block) {return false;}
				$('#tab-system-settings .backup .dumpSizePH').hide();
				$('#tab-system-settings .backup .dumpSizeCalculating').show();
				admin.ajaxRequest({
					mguniqueurl: "action/callBackupMethod",
					func: 'getDumpSize',
				},
				function (response) {
					$('#tab-system-settings .backup .dumpSizeResult').show().find('.number').html(response.data+' MB');
					$('#tab-system-settings .backup .dumpSizeCalculating').hide();
					$('#tab-system-settings .calcDumpSize').removeClass('calcDumpSize');
				});
			});

			$('#tab-system-settings').on('click', '.stopNewBackup', function() {
				Backup.createBlock = true;
			});

			$('#tab-system-settings').on('click', '.createNewBackup', function(e, skipConfirm) {
				if (Backup.block) {return false;}
				if (!skipConfirm && !confirm(lang.BACKUP_CREATE_CONFIRM)) {return false;}
				Backup.createBlock = false;
				Backup.blockAll(true);
				Backup.progressbar(0);
				Backup.type = $(this).data('type');
				$('#tab-system-settings .backup .backupLog').html('').show();
				$('#tab-system-settings .backup .progress').show();
				$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_START);

				var prefixTables = 0;
				if (Backup.type == 'core') {
					prefixTables = 1;
				}

				admin.ajaxRequest({
					mguniqueurl: "action/callBackupMethod",
					func: 'getDBtables',
					prefixTables: prefixTables
				},
				function (response) {
					if (response.data.errors != null) {
						$('#tab-system-settings .backup .backupLog').append(response.data.errors);
						$('#tab-system-settings .backupTable').closest('.accordion-item').find('.accordion-content').slideDown();
						Backup.blockAll(false);
					}
					else{
						$('#tab-system-settings .stopNewBackup').show();
						$('#tab-system-settings .backup .header_create').show();
						Backup.progressbar(0);
						Backup.totalTables = response.data.length;
						Backup.dumpTables(response.data, 0);
					}
				});
			});

			$('#tab-system-settings').on('click', '.backupTable .drop', function() {
				if (Backup.block) {return false;}
				if (!confirm(lang.BACKUP_DROP)) {return false;}
				admin.ajaxRequest({
					mguniqueurl: "action/callBackupMethod",
					func: 'dropBackup',
					zip: $(this).attr('zip'),
				},
				function (response) {
					$('#tab-system-settings .backupTable tbody').html(response.data);
				});
			});

			$('#tab-system-settings').on('click', '.backupTable .unpack', function() {
				if (Backup.block) {return false;}
				var edition = $(this).parents('tr').find('td:eq(3)').text();
				var version = $(this).parents('tr').find('td:eq(4)').text();
				var time = $(this).parents('tr').find('td:eq(5)').text();
				if (!confirm(lang.BACKUP_RESTORE_CONFIRM_1+edition+' '+version+lang.BACKUP_RESTORE_CONFIRM_2+time+lang.BACKUP_RESTORE_CONFIRM_3)) {return false;}
				Backup.blockAll(true);
				Backup.progressbar(0);
				$('#tab-system-settings .backup .warnings').hide();
				$('#tab-system-settings .backup .backupLog').html('').show();
				$('#tab-system-settings .backup .progress').show();
				Backup.zipName = $(this).attr('zip');
				$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_START);

				admin.ajaxRequest({
					mguniqueurl: "action/callBackupMethod",
					func: 'getZipType',
					zip: Backup.zipName,
				},
				function (response) {
					if (response.data.errors != null) {
						Backup.blockAll(false);
						$('#tab-system-settings .backup .backupLog').append(response.data.errors);
						$('#tab-system-settings .backup .warnings').show();
					}
					else{
						Backup.coreFilesFound = false;
						Backup.unpackType = response.data.type;
						Backup.unpackDir = response.data.dir;
						if (response.data.type == 'imploded') {
							Backup.implodedPartNum = 0;
							Backup.partSizes = response.data.partSizes;
							Backup.explodedZip = response.data.explodedZip;
							$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_FILES_START);
							Backup.explodeZip();
						}
						if (response.data.type == 'normal') {
							$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_FILES_START);
							admin.ajaxRequest({
								mguniqueurl: "action/callBackupMethod",
								func: 'getZipArrays',
								zip: Backup.zipName,
								dir: Backup.unpackDir,
							},
							function (response) {
								if (response.data.errors != null) {
									Backup.blockAll(false);
									$('#tab-system-settings .backupTable').closest('.accordion-item').find('.accordion-content').slideDown();
									$('#tab-system-settings .backup .backupLog').append(response.data.errors);
									$('#tab-system-settings .backup .warnings').show();
								}
								else{
									Backup.coreFilesFound = true;
									$('#tab-system-settings .backup .header_restore').show();
									Backup.totalFiles = response.data.miscfiles;
									Backup.restoreFromZipMisc();
								}
							});
						}
						if (response.data.type == 'db') {
							Backup.progressbar(0);
							$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_BASE_START);
							Backup.restoreDB(0);
						}
					}
				});				
			});

			$('#tab-system-settings').on('click', '.backupTable .download', function() {
				location.href = mgBaseDir+'/backups/'+$(this).attr('zip');
			});
			$('#tab-system-settings').on('click', '.backup .uploadNewBackup', function() {
				if (Backup.block) {return false;}
				$('#tab-system-settings .backup .backupInput').trigger('click');
			});
			$('#tab-system-settings').on('click', '.backup .restoreRecentBackup', function() {
				$("#tab-system-settings .backupTable .unpack:first").click();
			});

			$('#tab-system-settings').on('change', '.backup .backupInput', function() {
				if (Backup.block) {return false;}
				$(".backupInputForm").ajaxForm({
					type:"POST",
					url: "ajax",
					data: {
						mguniqueurl:"action/callBackupMethod",
						func: 'addNewBackup',
					},
					cache: false,
					dataType: 'json',
					success: function(response){
						if(response.status == 'error'){
							admin.indication(response.status, response.msg);
						}
						else{
							$('#tab-system-settings .backupTable tbody').html(response.data);
						}
					},
					error: function(XMLHttpRequest, textStatus, errorThrown) { 
						var maxSize = $('#tab-system-settings .maxUploadSize').text();
						admin.indication('error', lang.BACKUP_UPLOAD_ERROR+maxSize+" MB)");
					}
				}).submit();
			});

		},

		explodeZip: function() {
			if (Backup.implodedPartNum >= Backup.partSizes.length) {
				Backup.progressbar(1);
				$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_FILES_FINISH);
				Backup.restoreDB(0);
				$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_BASE_START);
			} else {
				admin.ajaxRequest({
					mguniqueurl: "action/callBackupMethod",
					func: 'explodeZip',
					zip: Backup.zipName,
					partSizes: Backup.partSizes,
					dir: Backup.unpackDir,
					partNum: Backup.implodedPartNum,
				},
				function (response) {
					if (response.data.corefiles > 0) {
						Backup.coreFilesFound = true;
					}

					if (response.data.miscfiles > 0) {
						Backup.restoreFromZipMisc();
					} else if(Backup.coreFilesFound) {
						Backup.restoreDB();
					}
				});
			}
		},
		createStop: function() {
			$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CANCEL);  
			Backup.blockAll(false);
			$('#tab-system-settings .stopNewBackup').hide();
			if (Backup.zipName != '') {
				admin.ajaxRequest({
					mguniqueurl: "action/callBackupMethod",
					func: 'dropBackup',
					zip: Backup.zipName,
				},
				function (response) {
					$('#tab-system-settings .backupTable tbody').html(response.data);
				});
			}
		},
		blockAll: function(state) {
			if (state) {
				$('#tab-system-settings .backup .header_table').hide();
				Backup.block = true;
				$('.button').prop('disabled', true);
				// $('#tab-system-settings .updateAccordion').hide();
				$('body').css('pointer-events', 'none');
				$('#tab-system-settings .backupTable').hide();
			}
			else{
				$('#tab-system-settings .backup .header_table').show();
				$('#tab-system-settings .backup .header_create').hide();
				$('#tab-system-settings .backup .header_restore').hide();
				$('#tab-system-settings .backup .stopNewBackup').hide();
				Backup.block = false;
				$('.button').prop('disabled', false);
				$('#tab-system-settings .updateAccordion').show();
				$('#tab-system-settings .backupTable').show();

				//Для обновления движка
				$('body').css('pointer-events', 'auto');
				$(".loading-update-step-3").hide();
			}
			$('#tab-system-settings .stopNewBackup').prop('disabled', false);
		},
		progressbar: function(percent) {
			$('#tab-system-settings .backup .echoPercent').html(percent+'%');
			$('#tab-system-settings .backup .percentWidth').css('width', percent+'%');
			$("#tab-system-settings .backup .backupLog").animate({scrollTop:$("#tab-system-settings .backup .backupLog")[0].scrollHeight - $("#tab-system-settings .backup .backupLog").height()},1,function(){});
			//Для обновления
			$('#tab-system-settings .js-progressBackupUpdate').html(percent+'%');
		},
		restoreFromZipCore: function() {
			var zip = Backup.zipName;
			if (Backup.partSizes.length) {
				zip = Backup.explodedZip;
			}
			admin.ajaxRequest({
				mguniqueurl: "action/callBackupMethod",
				func: 'restoreFromZip',
				zip: zip,
				mode: 'core',
				dir: Backup.unpackDir,
			},
			function (response) {
				$('#tab-system-settings .backup .backupLog').append(response.data.errors);
				if (response.data.remainingFiles > 0) {
					Backup.restoreFromZipCore();
				} else {
					Backup.progressbar(100);
					Backup.blockAll(false);
					$('#tab-system-settings .backup .warnings').show();
					$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_FINISH);
				}
			});
		},
		restoreFromZipMisc: function(currentAttempt = 0) {
			var zip = Backup.zipName;
			if (Backup.partSizes.length) {
				zip = Backup.explodedZip;
			}
			$.ajax({
				url: "ajax",
				type: "POST",
				data: {
					mguniqueurl: "action/callBackupMethod",
					func: 'restoreFromZip',
					zip: zip,
					mode: 'misc',
					dir: Backup.unpackDir,
				},
				dataType: 'json',
				success: function(response){
					var percent = 0;
					$('#tab-system-settings .backup .backupLog').append(response.data.errors);
					if (response.data.remainingFiles > 0) {
						percent = (Backup.totalFiles - response.data.remainingFiles) / (Backup.totalFiles / 100);
						if (Backup.partSizes.length) {
							percent = (Backup.implodedPartNum / Backup.partSizes.length)*100;
						}
						percent = Math.round(percent * 100) / 100;
						Backup.progressbar(percent);
						Backup.restoreFromZipMisc();
					}
					else{
						if (Backup.partSizes.length) {
							percent = (Backup.implodedPartNum / Backup.partSizes.length)*100;
							percent = Math.round(percent * 100) / 100;
							Backup.progressbar(percent);
							Backup.implodedPartNum++;
							Backup.explodeZip();
						} else {
							Backup.progressbar(1);
							$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_FILES_FINISH);
							Backup.restoreDB(0);
							$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_BASE_START);
						}
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) { 
					let nextAttempt = 0;
					Backup.timer = setTimeout(function () {Backup.restoreFromZipMisc(nextAttempt); }, 2000);
				}
			});
		},
		restoreDB: function(lineNum) {
			admin.ajaxRequest({
				mguniqueurl: "action/callBackupMethod",
				func: 'restoreDBbackup',
				lineNum: lineNum,
				dir: Backup.unpackDir,
			},
			function (response) {
				if (response.data.remaining > 0) {
					var percent = (response.data.total - response.data.remaining) / (response.data.total / 100);
					percent = Math.round(percent * 100) / 100;
					Backup.progressbar(percent);
					Backup.restoreDB(response.data.currentLine);
				} else {
					$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_BASE_FINISH);
					if (Backup.coreFilesFound) {
						Backup.progressbar(99);
						Backup.restoreFromZipCore();
					} else {
						Backup.progressbar(100);
						Backup.blockAll(false);
						$('#tab-system-settings .backup .warnings').show();
						$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_RESTORE_FINISH);
					}
				}
			});
		},
		dumpTables: function(tables, startingLine) {
			if (Backup.createBlock) {Backup.createStop();return false;}
			if (startingLine == 0) {
				$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_BASE_START);
			}
			admin.ajaxRequest({
				mguniqueurl: "action/callBackupMethod",
				func: 'createDBbackup',
				tables: tables,
				startingLine: startingLine,
			},
			function (response) {
				if (response.data.remaining > 0) {
					var percent = (Backup.totalTables - response.data.remaining) / (Backup.totalTables / 100);
					percent = Math.round(percent * 100) / 100;
					Backup.progressbar(percent);

					Backup.dumpTables(response.data.tables, response.data.startingLine);
				}
				else{
					if (Backup.type == 'full' || Backup.type == 'core') {
						$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_BASE_FINISH);
						Backup.progressbar(1);
						Backup.getFileList(1, 0);
					} else {
						admin.ajaxRequest({
							mguniqueurl: "action/callBackupMethod",
							func: 'packDB',
						},
						function (response) {
							$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_FINISH);
							Backup.progressbar(100);  
							Backup.drawTable();
							Backup.blockAll(false);
						});
					}
				}
			});
		},
		getFileList: function(buildFolderList, zipName) {
			if (Backup.createBlock) {Backup.createStop();return false;}
			if (buildFolderList == 1) {
				$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_FILES_START);
			}
			$.ajax({
				url: "ajax",
				type: "POST",
				data: {
					mguniqueurl: "action/callBackupMethod",
					func: 'getFileList',
					backupType: Backup.type,
					buildFolderList: buildFolderList,
					zipName: zipName,
				},
				dataType: 'json',
				success: function(response){
					if (response.status == 'success') {
						if (response.data.foldersRemaining > 0) {
							Backup.getFileList(0, response.data.zipName);
						} else {
							Backup.totalFiles = response.data.totalFiles;
							Backup.zipName = response.data.zipName;
							Backup.zipFolder = response.data.zipFolder;
							Backup.addDumpToZip();
						}
					} else {
						$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_FILELIST_ERROR);
						Backup.blockAll(false);
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) { 
					$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_FILELIST_ERROR);
					Backup.blockAll(false);
				}
			});
		},
		addDumpToZip: function() {
			$.ajax({
				url: "ajax",
				type: "POST",
				data: {
					mguniqueurl: "action/callBackupMethod",
					func: 'addDumpToZip',
					backupType: Backup.type,
					zipFolder: Backup.zipFolder,
				},
				dataType: 'json',
				success: function(response){
					if (response.status == 'success') {
						Backup.zipCoreFiles();
					} else {
						$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_ADD_DB_ERROR);
						Backup.blockAll(false);
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) { 
					Backup.timer = setTimeout(function () {Backup.addDumpToZip(); },2000);
				}
			});
		},
		zipCoreFiles: function() {
			if (Backup.createBlock) {Backup.createStop();return false;}
			var zipName = Backup.zipName;
			$.ajax({
				url: "ajax",
				type: "POST",
				data: {
					mguniqueurl: "action/callBackupMethod",
					zipFolder: Backup.zipFolder,
					func: 'zipCoreFiles',
				},
				dataType: 'json',
				success: function(response){
					$('#tab-system-settings .backup .backupLog').append(response.data.errors);
					var percent = (Backup.totalFiles - response.data.remainingFiles) / (Backup.totalFiles / 100);
					percent = Math.round(percent * 100) / 100;
					Backup.progressbar(percent);
					Backup.zipFiles();
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) { 
					Backup.timer = setTimeout(function () {Backup.zipCoreFiles(); },2000);
				}
			});
		},
		zipFiles: function() {
			if (Backup.createBlock) {Backup.createStop();return false;}
			var zipName = Backup.zipName;
			$.ajax({
				url: "ajax",
				type: "POST",
				data: {
					mguniqueurl: "action/callBackupMethod",
					func: 'zipFiles',
					zipName: Backup.zipName,
					zipFolder: Backup.zipFolder,
					backupType: Backup.type,
				},
				dataType: 'json',
				success: function(response){
					$('#tab-system-settings .backup .backupLog').append(response.data.errors);
					if (response.data.remainingFiles > 0) {
						var percent = (Backup.totalFiles - response.data.remainingFiles) / (Backup.totalFiles / 100);
						percent = Math.round(percent * 100) / 100;
						Backup.progressbar(percent);

						Backup.zipFiles();
					}
					else{
						$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_FILES_FINISH);
						$('#tab-system-settings .backup .backupLog').append(lang.BACKUP_CREATE_FINISH);
						Backup.progressbar(100);  
						Backup.drawTable();
						Backup.blockAll(false);
						admin.callFromString(Backup.callback);
						Backup.callback = '';
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) { 
					Backup.timer = setTimeout(function () {Backup.zipFiles(); },2000);
				}
			});
		},
		drawTable: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/callBackupMethod",
				func: 'drawTable'
			},
			function (response) {
				$('#tab-system-settings .backupTable tbody').html(response.data);
			});
		}
	};
})();