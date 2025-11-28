/*
* Модуль подключается на странице настроек плагина.
*/

var mgYmlImportCore = (function() {

    return {
        lang: [], // локаль плагина
        fileUrlInput: document.querySelector('.js-insert-yml-file-url'),
        clearUrlBtn: document.querySelector('.js-uploaded-file'),
        fileNameSpan: document.querySelector('.js-file-name'),
        inputRestProduct: document.querySelector('.js-rest-product-input'),
        init: function() {

            // установка локали плагина
           /* admin.ajaxRequest({
                    mguniqueurl: 'action/seLocalesToPlug',
                    pluginName: mgYmlImportCore.pluginName,
                },
                function(response) {
                    mgYmlImportCore.lang = response.data;
                },
            );*/

            // Сохраняет базовые настройки запись
            $('.admin-center').on('click', '.section-mg-yml-import-core .js-start-import-yml', function() {
                if ($('input[name="clearCatalog"]').is(':checked')) {
                    mgYmlImportCore.clearCatalog();
                } else {
                    mgYmlImportCore.importCatalog(null, 0);
                }

            });

            // Импорт изображений
            $('.admin-center').on('click', '.section-mg-yml-import-core .base-import-images', function () {
                $('.section-mg-yml-import-core .block-console textarea').text('');
                mgYmlImportCore.importCatalog(null, 5);
            });

            $('.js-browse-image').on('click', function(e) {
                e.preventDefault();
                admin.openUploader('mgYmlImportCore.getUmlFile');
            });

            mgYmlImportCore.clearUrlBtn.addEventListener('click', mgYmlImportCore.clearFileInput);

        },
        /**
         * Callback-функция для получения ссылки на XML-файл из аплоадера
         */
        getUmlFile: function(file) {
            const fileSizeLimit = 512;
            const fileSizeBytes = file.size;
            const fileSizeMB = fileSizeBytes / (1024 * 1024);

            if (fileSizeMB > fileSizeLimit) {
                if (!confirm('Файл очень большой (больше 512MB)! Возможны проблемы с загрузкой. Продолжить?')) {
                    return false;
                }
            }

            const fileExpansion = file.name.match(/\.[^/.]+$/)[0];
            if (file.mime !== 'application/xml' && fileExpansion !== '.yml' && fileExpansion !== '.xml') {
                alert('Неправильный формат файла. Требуется XML или YML!');
                return false;
            }

            mgYmlImportCore.clearFileInput();
            mgYmlImportCore.fileUrlInput.value = file.url;
            mgYmlImportCore.fileNameSpan.textContent = file.name;
            mgYmlImportCore.clearUrlBtn.classList.remove('file-name_hidden');
        },
        clearFileInput: function(evt) {
            if (evt) evt.preventDefault();
            mgYmlImportCore.clearUrlBtn.classList.add('file-name_hidden');
            mgYmlImportCore.fileUrlInput.value = '';
            mgYmlImportCore.fileNameSpan.textContent = '';
        },
        clearCatalog: function() {
            const catId = $('.base-settings .list-option li select[name="import_in_category"]').val();
            mgYmlImportCore.printLog(lang.CLEAR_CATALOG_START);

            setTimeout(function() {
                $('.mailLoader').before('<div class="my-view-action">' + lang.CLEAR_CATALOG_PROCESS + '</div>');
            }, 300);

            admin.ajaxRequest({
                    mguniqueurl: 'action/clearCatalogYML', // действия для выполнения на сервере
                 //   pluginHandler: mgYmlImportCore.pluginName, // плагин для обработки запроса
                    catId: catId,
                },
                function(response) {
                    $('.my-view-action').remove();
                    mgYmlImportCore.printLog(response.msg);
                    admin.indication(response.status, response.msg);
                    mgYmlImportCore.importCatalog(null, 0);
                });
        },
        uploadFileToImport: function() {
            $('.mailLoader').before('<div class="view-action" style="margin-top:-2px;">' + lang.LOADING + '</div>');
            // отправка файла на сервер
            mgYmlImportCore.printLog(lang.UPLOAD_FILE_PROCESS);

            $('.yml-upload-form').ajaxForm({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                cache: false,
                dataType: 'json',
                data: {
                    mguniqueurl: 'action/uploadFileToImportYML',
                   // pluginHandler: mgYmlImportCore.pluginName, // плагин для обработки запроса
                },
                error: function(response) {
                    mgYmlImportCore.printLog(lang.UPLOAD_FILE_SIZE_ERROR);
                    $('.view-action').remove();
                },
                success: function(response) {
                    admin.indication(response.status, response.msg);
                    if (response.status == 'success') {
                        $('.block-upload-сsv').hide();
                        $('.mgYmlImportCore-importer').show();
                        mgYmlImportCore.printLog(lang.UPLOAD_FILE_SUCCESS);
                    } else {
                        mgYmlImportCore.fileUrlInput.val('');
                    }
                    $('.upload_file_success').show();
                    $('.view-action').remove();
                },
            }).submit();
        },
        importCatalog: function(completed, step, total = 0, disableFileLoad = false) {
            var obj = '{';
            $('.list-option input, .list-option select').each(function() {
                obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
            });
            obj += '}';


            //преобразуем полученные данные в JS объект для передачи на сервер
            var data = eval('(' + obj + ')');

            // Если мы уже загрузили файл, то делать это повторно не нужно
            if (disableFileLoad) {
                delete data.url_data_file;
            }

            // Вывод потребления памяти скриптом и скорость импорта
            let debug = localStorage.getItem('debugYmlImport');
            if (debug === undefined) {
                debug = false;
            }
            if (debug) {
                data.debugMode = debug
            }

            // Лимит импорта товаров (Для дебага импорта товаров и изображений)
            let maxProductsImport = localStorage.getItem('debugYmlMaxProducts');
            if (maxProductsImport === undefined) {
                maxProductsImport = false;
            }
            if (maxProductsImport) {
                data.maxProductsImport = maxProductsImport;
            }

            data.new_file_structure = 'true';
            if (step == 0 && completed == 0)  {
                mgYmlImportCore.printLog(lang.START_IMPORT_LOG);
            }

            if (step < 3) {
                $('.section-mg-yml-import-core .base-import-images').attr('disabled', true);
            }

            if (total) {
                data.total = total;
            }

            admin.ajaxRequest({
                    mguniqueurl: 'action/importCatalogYML', // действия для выполнения на сервере
                  //  pluginHandler: mgYmlImportCore.pluginName, // плагин для обработки запроса
                    completed: completed, // id записи
                    step: step,
                    data: data,
                },
                function(response) {
                    $('.my-view-action').remove();
                    if (response.status == 'error') {
                        mgYmlImportCore.printLog(response.msg);
                        admin.indication(response.status, response.msg);
                        return false;
                    }
                    if (
                        response.data.enableImagesImport !== undefined
                    ) {
                        if (response.data.enableImagesImport === true) {
                            $('.section-mg-yml-import-core .base-import-images').removeAttr('disabled');
                        } else {
                            $('.section-mg-yml-import-core .base-import-images').attr('disabled', true);
                        }
                    }
                    if (response.data.step !== undefined) {
                        if (response.data.message.length) {
                            mgYmlImportCore.printLog(response.data.message);
                        }
                        let total = 0;
                        if (response.data.total !== undefined && response.data.total) {
                            total = response.data.total;
                        }

                        if (response.data.step == 5) {
                            mgYmlImportCore.printLog(lang.PROCESSING_NOT_DONE);
                        }
                        mgYmlImportCore.importCatalog(response.data.completed, response.data.step, total, true);
                    } else {
                        admin.indication(response.status, response.msg);
                        if (response.data.message.length) {
                            mgYmlImportCore.printLog(response.data.message);
                            return;
                        }
                        mgYmlImportCore.printLog(response.msg);
                    }
                });
        },
        printLog: function(text) {
            const textarea = $('.section-mg-yml-import-core .block-console textarea');
            textarea.append('\r\n' + text);
            if(textarea.length) {
                textarea.scrollTop(textarea[0].scrollHeight - textarea.height());
            }
        },
    };
})();

mgYmlImportCore.init();
