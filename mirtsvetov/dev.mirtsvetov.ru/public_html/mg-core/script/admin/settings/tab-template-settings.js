var settings_template = (function() {
    return {
        supportCkeditor: null,
        adminSectionNode: $('.admin-center'),
        tabTemplateNode: $('#tab-template-settings'),
        changeLetterTemplateNode: $('.change-letter-template'),
        templateOptionsActions: [],
        init: function() {
            settings_template.initEvents();
        },
        initEvents: function() {

            $('#tab-template-settings').on('click', '.openQuickstartModal', function() {
                $('#quickstart-modal .button.closeModal').hide();
                $('#quickstart-modal .quickstartLog').html('').hide();
                $('#quickstart-modal .save-button').show().prop('disabled', false);
                $('#quickstart-modal span.templName').html($('#tab-template-settings [name=templateName]:visible').val());
                $('#quickstart-modal .js-quickstartWarning').hide();
                $('#quickstart-modal .js-resetWarning').hide();
                admin.openModal('#quickstart-modal');
            });

            $('#tab-template-settings').on('change', '#templQuickstart', function() {
                if ($(this).prop('checked')) {
                    $('#quickstart-modal .js-quickstartWarning').show();
                } else {
                    $('#quickstart-modal .js-quickstartWarning').hide();
                }
            });
            $('#tab-template-settings').on('change', '#templReset', function() {
                if ($(this).prop('checked')) {
                    $('#quickstart-modal .js-resetWarning').show();
                } else {
                    $('#quickstart-modal .js-resetWarning').hide();
                }
            });

            $('#tab-template-settings').on('click', '#quickstart-modal .save-button', async function() {
                $('#quickstart-modal .save-button').attr('disabled', true);
                await settings_template.runOptions();
            });

            // клик по кнопкам отсутствующих файлов шаблона, копирует файл в папку шаблона
            $('#tab-template-settings').on('click', '.file-template-missing', function() {
                var path = $(this).data('path');
                var type = 'layout';
                if ($(this).data('type')) {
                    type = $(this).data('type');
                }
                var file = $(this).data('filename');
                var obj = $(this);

                admin.ajaxRequest({
                        mguniqueurl: 'action/copyTemplateFile',
                        path: path,
                        type: type,
                        file: file,
                    },

                    function(response) {
                        if (response.status == 'success') {
                            obj.removeClass('file-template-missing').addClass('file-template').addClass('tool-tip-bottom').click();
                        } else {
                            admin.indication(response.status, lang.TEMPLATE_COPY_FAIL);
                        }
                    });
            });

            // клик по кнопкам файлов шаблона, загружает содержание файла с сервера
            $('#tab-template-settings').on('click', '.file-template', function() {
                $('#codeBaseForLetter').hide();
                $('.save-file-template').data('editfilename', $(this).data('path'));
                $('.file-template').removeClass('editing-file');
                $(this).addClass('editing-file');
                var path = $(this).data('path');
                var type = $('.template-tabs-menu').find('.is-active').find('a').attr('data-target');
                var letterLangNode = $('#letterLang');
                var language = letterLangNode.find('option:selected').val();
                var descriptionLettersNode = $('#descriptionLetters');
                var codeFileNode = $('#codefile');
                var codeEmailNode = $('#codeBaseForLetter');

                admin.ajaxRequest({
                        mguniqueurl: 'action/getTemplateFile',
                        path: path,
                        type: type,
                        lang: language,
                    },

                    function(response) {
                        settings_template.supportCkeditor = null;

                        $('.save-file-template').hide();
                        $('.CodeMirror').remove();
                        // каждому файлу свою схему
                        if (response.status != 'error') {
                            var mode = 'application/x-httpd-php';
                            if (path.indexOf('.js') > 0) {
                                mode = 'text/javascript';
                            }
                            if (path.indexOf('.css') > 0) {
                                mode = 'text/css';
                            }
                            settings_template.supportCkeditor = '';

                            descriptionLettersNode.html('');
                            settings_template.changeLetterTemplateNode.html('');
                            let fileName = path.substr(8);

                            if (type == '#ttab2' && response.data.description) {

                                codeEditor = CodeMirror.fromTextArea(document.getElementById('codefile'), {
                                    lineNumbers: true,
                                    mode: mode,
                                    extraKeys: { 'Ctrl-F': 'findPersistent' },
                                    showMatchesOnScrollbar: true,
                                });

                                settings_template.setEditWithCkeditor(
                                    codeEmailNode,
                                    codeFileNode,
                                    response.data.filecontent
                                );

                                descriptionLettersNode.show();
                                if (response.data.warning) {
                                    descriptionLettersNode.append('<li style="color:darkred">' + response.data.warning + '</li>');
                                }
                                
                                descriptionLettersNode.append('<li><b>Список доступных замен:</b></li>');
                                $.each(response.data.description, function(key, value) {
                                    descriptionLettersNode.append('<li>{' + key + '}: ' + value + '</li>');
                                });

                                settings_template.changeLetterTemplateNode.removeClass('change-letter-template_hidden');
                                settings_template.changeLetterTemplateNode.append(settings_template.getLetterWarningHtml('create', fileName));

                                letterLangNode.attr('data-path', path).show();

                            } else {
                                settings_template.setEditWithCodeMirror(
                                    codeEmailNode,
                                    codeFileNode,
                                    response.data.filecontent,
                                    mode
                                );
                                if (
                                    type == '#ttab2' &&
                                    path.indexOf('email_template') == -1 &&
                                    path.indexOf('email_order_admin') == -1
                                ) {
                                    settings_template.changeLetterTemplateNode.removeClass('change-letter-template_hidden');
                                    settings_template.changeLetterTemplateNode.append(settings_template.getLetterWarningHtml('delete', fileName));
                                }
                                descriptionLettersNode.hide();
                                letterLangNode.hide().attr('data-path', '');
                            }


                            $('.error-not-tpl').hide();
                            $('.save-file-template').show();
                        } else {
                            $('.error-not-tpl').show();
                        }

                        var height = $($('.template-tabs-menu').find('.is-active a').data('target')).height();
                        if (height < 550) {
                            height = 550;
                        }
                        $('.CodeMirror').height(height - 5);
                        $('.CodeMirror-scroll').scrollTop(2);
                        settings_template.supportCkeditor = response.data.filecontent;
                    });
            });

            $('#tab-template-settings').on('change', '#letterLang', function() {
                var path = $(this).attr('data-path');
                var language = $('#letterLang option:selected').val();
                admin.ajaxRequest({
                        mguniqueurl: 'action/getLocaleLetter',
                        lang: language,
                        path: path,
                    },

                    function(response) {
                        if (response.status == 'success') {
                            $('#codeBaseForLetter').val(response.data);
                        }
                    });
            });

            // переключение вкладок шаблона
            $('#tab-template-settings').on('click', '.template-tabs', function() {


                settings_template.changeLetterTemplateNode.addClass('change-letter-template_hidden');
                $('#codefile').val('');
                $('.template-tabs').removeClass('is-active');
                $(this).addClass('is-active');

                $('.template-tabs-content .tabs-panel').hide();
                $($(this).find('a').data('target')).show();

                var height = $($('.template-tabs-menu').find('.is-active a').data('target')).height();
                if (height < 550) {
                    height = 550;
                }
                if (settings.allowTemplateAutoClicks) {
                    $('.CodeMirror').height(height - 5);
                    $('.CodeMirror-scroll').scrollTop(2);
                    if ($('.file-template:visible:first').length) {
                        $('.file-template:visible:first').click();
                    } else {
                        $('.CodeMirror').remove();
                        $('.save-file-template').hide();
                    }
                }
            });

            $('#tab-template-settings').on('click', '#newLetterFile', function() {
                var tab = $('.tab-email-layout.editing-file');
                var path = tab.data('path');
                var type = 'layout';
                var file = path.substr(path.indexOf('email_'));
                var obj = tab;
                var codeFileNode = $('#codefile');
                var codeEmailNode = $('#codeBaseForLetter');

                admin.ajaxRequest({
                        mguniqueurl: 'action/copyTemplateFile',
                        path: path,
                        type: type,
                        file: file,
                        userAction: true,
                    },

                    function(response) {
                        if (response.status == 'success') {
                            obj.removeClass('file-template-missing').addClass('file-template').addClass('tool-tip-bottom').click();

                            settings_template.setEditWithCodeMirror(
                                codeEmailNode,
                                codeFileNode,
                                response.data
                            );
                        } else {
                            admin.indication(response.status, lang.TEMPLATE_COPY_FAIL);
                        }
                    });
            });

            $('#tab-template-settings').on('click', '#deleteLetterFile', function() {
                var fileName = $(this).data('file');
                if (confirm(lang.FILE + ' ' + fileName + ' ' + lang.REALLY_WANT_TO_DELETE)) {
                    var tab = $('.tab-email-layout.editing-file');
                    var path = tab.data('path');
                    var obj = tab;
                    var codeFileNode = $('#codefile');
                    var codeEmailNode = $('#codeBaseForLetter');
                    admin.ajaxRequest({
                            mguniqueurl: 'action/deleteLetterFile',
                            path: path,
                            userAction: true,
                        },
                        function(response) {
                            if (response.status == 'success') {
                                obj.addClass('file-template-missing');

                                settings_template.setEditWithCkeditor(
                                    codeEmailNode,
                                    codeFileNode,
                                    response.data.filecontent
                                );

                                $('#descriptionLetters').show();
                                $('#descriptionLetters').append('<li><b>Список доступных замен:</b></li>');
                                $.each(response.data.description, function(key, value) {
                                    $('#descriptionLetters').append('<li>{' + key + '}: ' + value + '</li>');
                                });

                                settings_template.changeLetterTemplateNode.removeClass('change-letter-template_hidden');
                                settings_template.changeLetterTemplateNode.html('');
                                settings_template.changeLetterTemplateNode.append(settings_template.getLetterWarningHtml('create', fileName));
                                settings_template.changeLetterTemplateNode.find('#deleteLetterFile').data('file', fileName);
                            } else {
                                admin.indication(response.status, 'Не удалось удалить файл');
                            }
                        });
                } else {
                    return false;
                }
            });

            // Выбор картинки
            $('#tab-template-settings').on('click', '.browseImage', function() {
                admin.openUploader(null, null, 'template');
            });

            // открытие модалки с уведомлениями
            $('#tab-template-settings').on('click', '.browseMessages', function() {
                settings_template.updateMessageModal();
            });

            // открытие модалки со стилями
            $('#tab-template-settings').on('click', '.template-tabs', async function(e) {
                $('#setting-templates-code .tabs-panel .file-template__component_name').show();
                $('#setting-templates-code .tabs-panel .file-template').show();
                $('.template-files-search__labels div').hide();
                $('.template-files-search__input').val('');
                await admin.openModal($('#setting-templates-code'));
                const modalSettings = document.getElementById('setting-templates-code');
                const btnsPanels = modalSettings.querySelectorAll('.tabs-panel');
                let firstButton = null;
                for (const btnsPanel of btnsPanels) {
                    if (btnsPanel.style.display === 'block') {
                        firstButton = btnsPanel.querySelector('[data-path]');
                        break;
                    }
                }

                if (settings.preventFirstButtonClick) {
                    settings.preventFirstButtonClick = false;
                } else {
                    firstButton.click();
                }
            });

            // смена языка в модалке с уведомлениями
            $('#tab-template-settings').on('change', '#messages-modal .select-lang', function() {
                settings_template.updateMessageModal();
            });

            // сброс разделов в модалке с уведомлениями
            $('#tab-template-settings').on('click', '#messages-modal .clearfix a', function() {
                if ($(this).attr('part')) {
                    if (!confirm($(this).text().trim() + '?')) {
                        return false;
                    }
                    admin.ajaxRequest({
                            mguniqueurl: 'action/resetMsgs',
                            type: $(this).attr('part'),
                        },
                        function(response) {
                            $.each(response.data, function(index) {
                                $('#messages-modal .clearfix [name=' + index + ']').val(response.data[index]);
                            });
                        });
                }
            });

            // сохранение модалки с уведомлениями
            $('#tab-template-settings').on('click', '#messages-modal .save-button', function() {
                var fields = [];

                $('#messages-modal .clearfix input').each(function(index, element) {
                    fields.push({ 'id': $(this).attr('ajdi'), 'name': $(this).attr('name'), 'val': $(this).val() });
                });

                admin.ajaxRequest({
                        mguniqueurl: 'action/saveMsgs',
                        lang: $('#messages-modal .select-lang').val(),
                        fields: fields,
                    },
                    function(response) {
                        admin.indication(response.status, 'Сохранено');
                        admin.closeModal('#messages-modal');
                    });
            });

            // открытие вкладки шаблона
            $('#tab-template-settings').on('click', '.openTemplate', function() {
                $('.templateContainer').slideToggle();
                $('.landingContainer').slideUp();
                $('.resetContainer').slideUp();
            });

            // открытие вкладки лендинга
            $('#tab-template-settings').on('click', '.openLanding', function() {
                $('.templateContainer').slideUp();
                $('.resetContainer').slideUp();
                $('.landingContainer').slideToggle();
            });

            // открытие обновления шаблонов
            $('#tab-template-settings').on('click', '.openReset', function() {
                if ($('.resetContainer select').length) {
                    $('.templateContainer').slideUp();
                    $('.landingContainer').slideUp();
                    $('.resetContainer').slideToggle();
                } else {
                    admin.ajaxRequest({
                            mguniqueurl: 'action/mpGetResetSelect',
                        },

                        function(response) {
                            if (response.status == 'success') {
                                $('.resetContainer').html(response.data);
                                $('.templateContainer').slideUp();
                                $('.landingContainer').slideUp();
                                $('.resetContainer').slideToggle();
                                if($('.resetContainer option:selected').attr("data-version") == ""){
                                    $('.section-settings .resetContainer .alert-block.warning').html('Вы используете актуальную версию шаблона').slideToggle();
                                } else if($('.resetContainer option:selected').attr("data-version")){
                                    var currentTemplate = $('.section-settings .resetContainer option:selected');
                                    $('.section-settings .resetContainer .alert-block.warning').html('Текущая версия шаблона <strong>'+currentTemplate.attr("name")+'</strong> '
                                    + currentTemplate.attr("data-current-version")+' <button class="getVersion link"><span>обновить шаблон до версии '
                                    + currentTemplate.attr("data-version")+'</span></button>').addClass("getVersion").slideToggle();
                                }
                            } else {
                                admin.indication(response.status, 'Ошибка получения данных');
                            }
                        });
                }
            });

            $('#tab-template-settings').on('change', '.resetContainer select', function() {
                $(".resetContainer button.getVersion").removeClass("getVersion");
                if(!$('.resetContainer option:selected').attr("data-version")){
                    $('.section-settings .resetContainer .alert-block.warning').html('Вы используете актуальную версию шаблона');
                } else {
                    var currentTemplate = $('.section-settings .resetContainer option:selected');
                    $('.section-settings .resetContainer .alert-block.warning').html('Текущая версия шаблона <strong>'+currentTemplate.attr("name")+'</strong> '
                    + currentTemplate.attr("data-current-version")+' <button class="getVersion link"><span>обновить шаблон до версии '
                    + currentTemplate.attr("data-version")+'</span></button>').addClass("getVersion");
                }
            });
            
            // проверка актуальных версий шаблона
            $('#tab-template-settings').on('click', '.resetContainer button.loadVersions', function() {
                settings_template.getVersionTemplate();
            });
            // обновление шаблона
            $('#tab-template-settings').on('click', '.js-getVersion', function (e) {
                if (!confirm('Все пользовательские изменения в коде шаблона будут утрачены. Обновить?')) {
                    return false;
                }
                const templateName = e.target.dataset.tempName;
                const templateCode = document.querySelector(`.resetContainer [name="${templateName}"]`).value;
                admin.ajaxRequest({
                    mguniqueurl: 'action/mpUpdateTemplate',
                    code: templateCode,
                },
                    function (response) {
                        if (response.status == 'success') {
                            admin.indication(response.status, 'Шаблон обновлен');
                            var templates = $('.section-settings .resetContainer option:selected');
                            templates.attr("data-version", '').text(templates.attr("name"));
                            let templateCard = e.target.closest('.js-installed-template');
                            if (!templateCard) {
                                templateCard = e.target.closest('.js-admin-template-card');
                                const imageContainer = templateCard.querySelector('.templates-cards__card-image');
                                imageContainer.classList.add('_mini');
                                imageContainer.classList.remove('_mini-upd');
                            }
                            const applyBtn = templateCard.querySelector('.js-templates-block__activation');
                            if (applyBtn) applyBtn.classList.remove('_hide');
                            templateCard.querySelector('.js-getVersion').classList.add('_hide');
                            templateCard.querySelector('.js-admin-template-card__update').classList.remove('_yellow');
                            templateCard.querySelector('.js-admin-template-card__update').classList.add('_green');
                            const newVersion = templateCard.querySelector('.js-new-version span').textContent;
                            templateCard.querySelector('.js-version').innerHTML = lang['TEMPLATE_VERSION'] + ' ' + newVersion;
                            templateCard.querySelector('.js-new-version').innerHTML = lang['TEMPLATE_VERSION_ACTUAL'];;
                        } else {
                            admin.indication(response.status, 'При обновлении произошла ошибка');
                        }
                    });
            });

            $('#tab-template-settings').on('change', 'select[name="landingName"]', function() {
                settings_template.drawColorShemesLanding($(this).find('option:selected').data('schemes'));
            });
            $('#tab-template-settings').on('click', '.dropTemplate', function () {
                if (!confirm(lang.dropTemplate)) {
                    return false;
                }
                admin.ajaxRequest({
                    mguniqueurl: 'action/dropTemplate',
                    template: $(this).closest('.js-installed-template').find('.js-templates-block__activation').data('temp-name'),
                },
                    function (response) {
                        admin.indication(response.status, response.msg);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    });
            });
            // Выбор цветовой схемы шаблона
            $('#tab-template-settings').on('click', '.color-scheme', function(e) {
                if ($('.color-scheme').is(e.target)) {
                    $(this).parents('ul').find('.color-scheme').removeClass('active');
                    $(this).addClass('active');
                    $('#tab-template-settings #templateCustomColorPopup').hide();
                    if ($(this).hasClass('popup-holder')) return;
                    // Сохранение настроек шаблона
                    settings_template.selectTemplate();
                }
            });

            // Выбор цветовой схемы шаблона лэндинга
            $('#tab-template-settings').on('click', '.color-scheme-landing', function() {
                $(this).parents('ul').find('.color-scheme-landing').removeClass('active');
                $(this).addClass('active');
            });

            // Обработчик для загрузки нового шаблона
            $('#tab-template-settings').on('change', '#addTempl', function() {
                settings_template.addNewTemplate('newTemplateForm');
            });

            // Обработчик для загрузки нового шаблона лэндинга
            $('#tab-template-settings').on('change', '#addLanding', function() {
                settings_template.addNewTemplate('newLandingForm');
            });

            // сохранение файла шаблона
            $('#tab-template-settings').on('click', '.save-file-template', function() {
                var filename = $(this).data('editfilename');
                var type = $('.template-tabs-menu').find('.is-active').find('a').attr('data-target');
                var content = codeEditor.getValue();
                var letterToDB = false;
                var lang = 'default';
                if (type === '#ttab2' && $('.CodeMirror').length < 1) {
                    content = $('.tabs-content #codeBaseForLetter').val();
                    letterToDB = true;
                    if ($('#letterLang').css('display') == 'block') {
                        lang = $('#letterLang option:selected').val();
                    }
                }

                admin.ajaxRequest({
                        mguniqueurl: 'action/saveTemplateFile',
                        content: content,
                        filename: filename,
                        type: type,
                        letterToDB: letterToDB,
                        lang: lang,
                    },
                    function(response) {
                        admin.indication(response.status, response.msg);
                    });
            });

            if (settings.allowTemplateAutoClicks) {
                $('#templateCustomColorPopup').hide();
            }

            // открытие попапа с настройками цвета шаблона
            $('#tab-template-settings').on('click', '[data-scheme=user_defined]', function(e) {
                if ($('[data-scheme=user_defined]').is(e.target)) {
                    $(this).find('.customTemplateColor').each(function() {
                        admin.initPickr($(this).attr('name'));
                    });
                    $('#tab-template-settings #templateCustomColorPopup').show();
                }
            });

            // закрытие попапа с настройками цвета шаблона
            $('#tab-template-settings').on('click', '[data-scheme=user_defined] .applyCustomColor', function(e) {
                $('#tab-template-settings #templateCustomColorPopup').hide();

                // Сохранение настроек шаблона
                settings_template.selectTemplate();
            });

            $('#tab-template-settings').on('click', '#useTemplatePlugins', function(e) {
                $(e.target).attr('disabled', 'true');
                admin.ajaxRequest(
                    {
                        mguniqueurl: 'action/toggleUseTemplatePluginsSetting',
                    },
                    function(response) {
                        admin.indication(response.status, response.msg);
                        if (response.data.newValue !== undefined) {
                            if (response.data.newValue === '1') {
                                $(e.target).prop("checked", true);
                            } else {
                                $(e.target).prop("checked", false);
                            }
                        }
                        $(e.target).removeAttr('disabled');
                    }
                );
            });

            $('#tab-template-settings').on('click', '.template-files-search__button', settings_template.startTemplateFilesSearch);
            $('#tab-template-settings').on('change', '.template-files-search__input', settings_template.startTemplateFilesSearch);


        },
        startTemplateFilesSearch: function() {
            const searchString = $('.template-files-search__input:visible').val();
            if (!searchString) {
                settings_template.clearTemplateFilesSearch();
                return;
            }
            settings_template.searchTemplateFiles(searchString);
            return;
        },
        selectTemplate: function () {
            var template = $('#tab-template-settings [name=templateName]').val();
            var colorScheme = $('#tab-template-settings .color-scheme.active').data('scheme');
            var userColors = {};
            if (colorScheme === 'user_defined') {
                $('#templateCustomColorPopup .customTemplateColor').each(function() {
                    userColors[$(this).data('key')] = $(this).val();
                });
            }
            admin.ajaxRequest({
                mguniqueurl: 'action/saveTemplateSettings',
                settings: {
                    templateName: template,
                    colorScheme: colorScheme,
                    userColors: userColors,
                    landingName: $('#tab-template-settings [name=landingName]:visible').val(),
                    colorSchemeLanding: $('#tab-template-settings .color-scheme-landing.active:visible').data('scheme'),
                },
            },
            function(response) {
                admin.indication(response.status, response.msg);
                if (response.data.showModal) {
                    $('#quickstart-modal #templPlugins').prop('checked', true).data('plugins', '').closest('.row').hide();
                    $('#quickstart-modal #templQuickstart').prop('checked', false).data('template', '').closest('.row').hide();
                    $('#quickstart-modal #templSettings').prop('checked', false).data('template', '').closest('.row').hide();
                    if (response.data.plugins && response.data.plugins.length) {
                        $('#quickstart-modal #templPlugins').data('plugins', response.data.plugins).closest('.row').show();
                    }
                    if (response.data.quickstart) {
                        $('#quickstart-modal #templQuickstart').data('template', template).closest('.row').show();
                    }
                    if (response.data.settings) {
                        $('#quickstart-modal #templSettings').data('template', template).closest('.row').show();
                    }
                    $('#tab-template-settings .openQuickstartModal').show();
                } else {
                    $('#tab-template-settings .openQuickstartModal').hide();
                }
                // обновление контента и восстановление таба
                var saveTab = $('#tab-template-settings .template-tabs-menu .template-tabs.is-active a').data('target');
                var saveFile = $('#tab-template-settings .template-tabs-content .editing-file:visible').data('path');
                $.ajax({
                    url: mgBaseDir + '/mg-admin/ajax',
                    method: 'POST',
                    data: {
                        mguniqueurl: 'settings.php',
                        mguniquetype: 'adminpage',
                    },
                    success: function(response) {
                        $('ul.template-tabs-menu').html($(response).find('ul.template-tabs-menu').html());
                        $('.template-tabs-content').html($(response).find('.template-tabs-content').html());
                        $('#quickstart-modal').html($(response).find('#quickstart-modal').html());

                        settings.allowTemplateAutoClicks = false;
                        settings.allowTemplateAutoClicks = true;
                        setTimeout(function() {
                            if ($('#tab-template-settings .template-tabs-content .file-template[data-path="' + saveFile + '"]').length) {
                                $('#tab-template-settings .template-tabs-content .file-template[data-path="' + saveFile + '"]').click();
                            } else {
                                $('.file-template:visible:first').click();
                            }
                        }, 0);
                    },
                });
            });
        },

        /**
         * Функция выполнения опций шаблона
         */
        runOptions: async function(arrayFunctions = []) {
            if (arrayFunctions.length < 1) {
                $('#quickstart-modal .quickstartLog').html('').show();
                const installQuickstartChecked = $('#quickstart-modal .row:visible #templQuickstart').prop('checked');
                const installTemplatePluginsChecked = (
                    $('#quickstart-modal .row:visible #templPlugins').prop('checked') &&
                    $('#quickstart-modal .row:visible #templPlugins').data('plugins').length
                );
                const applySettingsChecked = $('#quickstart-modal .row:visible #templSettings').prop('checked');
                const reinstallMaketsChecked = $('#quickstart-modal .row:visible #templReset').prop('checked');
                const resetUserCssChecked = $('#quickstart-modal .row:visible #templUserCss').prop('checked');
                const resetLocalesChecked = $('#quickstart-modal .row:visible #templLocales').prop('checked');
                
                this.templateOptionsActions = [];

                if (installQuickstartChecked) {
                    this.templateOptionsActions.push('installQuickstart');
                }

                if (applySettingsChecked) {
                    this.templateOptionsActions.push('applySettings');
                }
                if (installTemplatePluginsChecked) {
                    this.templateOptionsActions.push('installTemplatePlugins');
                }
            } else {
                this.isPublic = true;
                this.templateOptionsActions = arrayFunctions;
            }

            this.runTemplateOptionsActions();
        },
        runTemplateOptionsActions: function() {
            if (this.templateOptionsActions.length) {
                const method = this.templateOptionsActions.shift();
                this[method]();
                return;
            }
            this.quickstartLog('Опции шаблона успешно применены!');
            if (this.isPublic === true) {
                window.location.href = window.location.origin; // Перезагружаем страницу с редиректом на главную
            }
            $('#quickstart-modal .save-button').hide();
            $('#quickstart-modal .save-button').removeAttr('disabled');
            $('#quickstart-modal .closeModal').removeAttr('disabled');
            $('#quickstart-modal .closeModal').show();
        },
        /**
         * Опция "Применить рекомендуемые настройки магазина"
         */
        applySettings: async function() {
            $('#quickstart-modal .save-button').prop('disabled', true);
            let tempName = $('#quickstart-modal .row:visible #templSettings').data('template');
            if (!tempName) tempName = $('.js-templates-options__install-demo').data('temp-name');
            if (!tempName) tempName = window.selectedTemplate;
            await admin.ajaxRequest(
                {
                    mguniqueurl: 'action/applyTemplateSettings',
                    template: tempName,
                },
                function(response) {
                    settings_template.quickstartLog(response.msg);
                    settings_template.runTemplateOptionsActions();
                }
            );
        },
        /**
         * Опция "Удалить все с сайта и установить демо-контент"
         */
        installQuickstart: async function() {
            const isAdmin = document.querySelector('.mg-admin-html');
            let tempName = $('#quickstart-modal .row:visible #templQuickstart').data('template');
            let noConfirm = false;
            if (!tempName) tempName = $('.js-templates-options__install-demo').data('temp-name');
            if (!tempName) {
                tempName = window.selectedTemplate;
                noConfirm = true;
            }
            if (!noConfirm && !confirm('Установить демонстрационные данные?\nТекущий каталог и настройки будут перезаписаны!')) {
                if (isAdmin) return false;
                modalTemplates.reloadPageWithRedirect();
                return false;
            }
            if (!noConfirm) {
                $('.render-preloader .js-text-list span').html('Идёт установка демонстрационных товаров <br> Шаблон подойдёт для любой тематики магазина, демонстрационный контент вы сможете изменить.');
            } else {
                $('.render-preloader .js-text-list span').html('Идёт установка шаблона <br> После окончания установки страница будет автоматически перезагружена. <br>  ');
            }
            $('#quickstart-modal .save-button').prop('disabled', true);
            $('#quickstart-modal .quickstartLog').html('').show();
            settings_template.quickstartLog('Начат процесс установки демонстрационных данных');
            await admin.ajaxRequest(
                {
                    mguniqueurl: 'action/quickstartDownload',
                    template: tempName,
                },
                function(response) {
                    if (response.status == 'success') {
                        settings_template.quickstartLog('Загружен архив с данными');
                        settings_template.quickstartClear();
                    } else {
                        admin.indication(response.status, response.msg);
                        settings_template.quickstartLog(response.msg);
                        $('#quickstart-modal .save-button').removeAttr('disabled');
                    }
                }
            );
        },
        /**
         * Опция "Переустановить макеты конструктора на исходные"
         */
        reinstallMakets: async function() {
            if (this.isPublic !== true && !confirm('Удалить все секции и макеты относящиеся к конструктору сайта!')) {
                return false;
            }
            await settings_template.resetConfigCloud('config');
        },
        /**
         * Опция "Удалить все пользовательские стили конструктора"
         */
        resetUserCss: async function() {
            if (this.isPublic !== true && !confirm('Удалить все пользовательские стили относящиеся к конструктору сайта!')) {
                return false;
            }
            await settings_template.resetConfigCloud('usercss');
        },
        /**
         * Опция "Установить исходные локали шаблона"
         */
        resetLocales: async function() {
            if (this.isPublic !== true && !confirm('Удалить все пользовательские стили относящиеся к конструктору сайта!')) {
                return false;
            }
            await settings_template.resetConfigCloud('locale');
        },
        /**
         * Опция "Установить плагины шаблона"
         */
        installTemplatePlugins: async function() {
            settings_template.quickstartLog('Установка плагинов шаблона...');
            $('#useTemplatePlugins').prop('checked', true);
            admin.ajaxRequest(
                {
                    mguniqueurl: 'action/activateTemplatePlugins',
                },
                function(response) {
                    admin.indication(response.status, response.msg);
                    admin.updatePluginsDropdownMenu();
                    settings_template.quickstartLog('Плагины шаблона установлены!');
                    settings_template.runTemplateOptionsActions();
                }
            );
        },
        /**
         * функция получает актуальные версии доступных шаблонов c сервера обновлений
         */
        getVersionTemplate: function () {
            admin.ajaxRequest({
                mguniqueurl: 'action/mpVersionTemplate',
            },
                function (response) {
                    if (response.status == 'success') {
                        window.newVersionTemplates = response.data;
                        if (response.data == true) {
                            admin.indication(response.status, 'Вы используете актуальные версии шаблонов');
                        } else {
                            admin.indication(response.status, 'Доступны обновления шаблонов');
                        }
                    } else {
                        admin.indication(response.status, 'При получении обновлений произошла ошибка');
                    }
                });
            const awaitResponse = setInterval(() => {
                if (window.newVersionTemplates) {
                    clearInterval(awaitResponse);
                    admin.ajaxRequest({
                        mguniqueurl: 'action/mpGetResetSelect',
                    },
                        function (response) {
                            if (response.status == 'success') {
                                $('.resetContainer').html(response.data);
                                $('.resetContainer').hide();
                                const selectedTemplate = document.querySelector('select[name="templateName"]').value; // Название установленного шаблона
                                if (!window.newVersionTemplates || !window.newVersionTemplates[selectedTemplate] && $('.js-admin-template-card__title').text().trim() !== 'moguta-standard' && $(`.resetContainer option[name="${selectedTemplate}"]`).data('current-version') != undefined) {
                                    // Сообщение, что шаблон актуален (старая форма)
                                    $('.section-settings .resetContainer .alert-block.warning').html('Вы используете актуальную версию шаблона').show();
                                    // Добавляем информацию также в новую форму
                                    $('.js-admin-template-card .js-admin-template-card__update .js-version').html(
                                        lang['TEMPLATE_VERSION'] + ' ' + $(`.resetContainer option[name="${selectedTemplate}"]`).data('current-version')
                                    );
                                    $('.js-admin-template-card .js-admin-template-card__update .js-new-version').html(
                                        lang['TEMPLATE_VERSION_ACTUAL']
                                    );
                                    $('.js-admin-template-card .js-admin-template-card__update').removeClass('_yellow');
                                    $('.js-admin-template-card .js-admin-template-card__update').addClass('_green');
                                    $('.js-admin-template-card .templates-cards__card-image').addClass('_mini');
                                    setTimeout(() => {
                                        $('.js-admin-template-card .js-admin-template-card__update').slideDown();
                                    }, 100);
                                } else if (window.newVersionTemplates[selectedTemplate] && $(`.resetContainer option[name="${selectedTemplate}"]`).data('current-version') != undefined) {
                                    // Сообщение, что есть новая версия (старая форма)
                                    var currentTemplate = $('.section-settings .resetContainer option:selected');
                                    $('.section-settings .resetContainer .alert-block.warning').html('Текущая версия шаблона <strong>' + currentTemplate.attr("name") + '</strong> '
                                        + currentTemplate.attr("data-current-version") + ' <button class="getVersion link"><span>обновить шаблон до версии '
                                        + currentTemplate.attr("data-version") + '</span></button>').addClass("getVersion").show();
                                    // Добавляем информацию также в новую форму
                                    $('.js-admin-template-card .js-admin-template-card__update').removeClass('_green');
                                    $('.js-admin-template-card .js-admin-template-card__update').addClass('_yellow');
                                    $('.js-admin-template-card .js-admin-template-card__update .js-version').html(
                                        lang['TEMPLATE_VERSION'] + ' ' + $(`option[name="${selectedTemplate}"]`).data('current-version')
                                    );
                                    $('.js-admin-template-card .js-admin-template-card__update .js-new-version').html(
                                        lang['TEMPLATE_VERSION_AVAILABLE'] + ' <span>' + $(`option[name="${selectedTemplate}"]`).data('version') + '</span>'
                                    );
                                    $('.js-admin-template-card .templates-cards__card-image').addClass('_mini-upd');
                                    setTimeout(() => {
                                        $('.js-admin-template-card .js-getVersion').slideDown();
                                    }, 300);
                                    setTimeout(() => {
                                        $('.js-admin-template-card .js-admin-template-card__update').slideDown();
                                    }, 100);
                                }

                                // Добавляем кнопки обновления шаблонов в карточки шаблонов
                                const templateCards = document.querySelectorAll('.js-installed-template');
                                for (const templateCard of templateCards) {
                                    let templateName = templateCard.querySelector('.js-templates-block__activation');
                                    templateName = templateName.dataset.tempName;
                                    if (templateCard.classList.contains('_active') || templateName === 'moguta-standard') continue;
                                    const dataCurrentVersion = $(`option[name="${templateName}"]`).data('current-version');
                                    if (!window.newVersionTemplates || !window.newVersionTemplates[templateName] && dataCurrentVersion != undefined && dataCurrentVersion != '') {
                                        const buttonsContainer = templateCard.querySelector('.templates-cards__card-buttons');
                                        const applyBtn = templateCard.querySelector('.js-templates-block__activation');
                                        buttonsContainer.innerHTML = `
                                        <div class="update-container">
                                            <div class="admin-template-card__update _yellow js-admin-template-card__update">
                                                <div class="js-version"></div>
                                                <div class="js-new-version"></div>
                                            </div>
                                        </div>`;
                                        const updateContainer = templateCard.querySelector('.update-container');
                                        updateContainer.insertAdjacentElement('beforeend', applyBtn);

                                        const version = templateCard.querySelector('.js-version');
                                        const newVersion = templateCard.querySelector('.js-new-version');
                                        const update = templateCard.querySelector('.js-admin-template-card__update');
                                        version.innerHTML = lang['TEMPLATE_VERSION'] + ' ' + dataCurrentVersion;
                                        newVersion.innerHTML = lang['TEMPLATE_VERSION_ACTUAL'];
                                        templateCard.querySelector('.templates-cards__card-image').classList.add('_mini-upd');
                                        update.classList.add('_green');
                                        update.classList.remove('_yellow');
                                        setTimeout(() => {
                                            $(templateCard.querySelector('.js-getVersion')).slideDown();
                                        }, 300);
                                        setTimeout(() => {
                                            $(update).slideDown();
                                        }, 100);
                                    } else if (window.newVersionTemplates[templateName] && dataCurrentVersion != undefined && dataCurrentVersion != '') {
                                        const buttonsContainer = templateCard.querySelector('.templates-cards__card-buttons');
                                        const applyBtn = templateCard.querySelector('.js-templates-block__activation');
                                        applyBtn.classList.add('_hide');
                                        buttonsContainer.innerHTML = `
                                        <div class="update-container">
                                            <div class="admin-template-card__update _yellow js-admin-template-card__update">
                                                <div class="js-version"></div>
                                                <div class="js-new-version"></div>
                                            </div>
                                            <button class="button primary templates-cards__upd-button js-getVersion" data-temp-name="${templateName}">
                                                <i class="fa fa-refresh" aria-hidden="true"></i>
                                                ${lang['TEMPLATE_UPDATE']}
                                            </button>
                                        </div>
                                        `;
                                        const updateContainer = templateCard.querySelector('.update-container');
                                        updateContainer.insertAdjacentElement('beforeend', applyBtn);
                                        const version = templateCard.querySelector('.js-version');
                                        const newVersion = templateCard.querySelector('.js-new-version');
                                        const update = templateCard.querySelector('.js-admin-template-card__update');
                                        version.innerHTML = lang['TEMPLATE_VERSION'] + ' ' + dataCurrentVersion;
                                        newVersion.innerHTML = lang['TEMPLATE_VERSION_AVAILABLE'] + ' <span>' + window.newVersionTemplates[templateName] + '</span>';
                                        templateCard.querySelector('.templates-cards__card-image').classList.add('_mini-upd');
                                        update.classList.remove('_green');
                                        update.classList.add('_yellow');
                                        setTimeout(() => {
                                            $(templateCard.querySelector('.js-getVersion')).slideDown();
                                        }, 300);
                                        setTimeout(() => {
                                            $(templateCard.querySelector('.js-admin-template-card__update')).slideDown();
                                        }, 100);
                                    }
                                }
                            } else {
                                admin.indication(response.status, 'Ошибка получения данных');
                            }
                        });
                }
            }, 500);
        },
        setEditWithCkeditor: function(ckEditorField, codeMirrorField, content) {
            $('.CodeMirror').remove();
            settings_template.ckEditorDestroy('codeBaseForLetter');
            ckEditorField.val(content);
            codeMirrorField.hide();
            ckEditorField.show();
            ckEditorField.ckeditor();
        },
        setEditWithCodeMirror: function(ckEditorField, codeMirrorField, content, mode) {

            settings_template.ckEditorDestroy('codeBaseForLetter');
            ckEditorField.hide();
            codeMirrorField.val(content);
            codeMirrorField.show();
            codeEditor = CodeMirror.fromTextArea(document.getElementById('codefile'), {
                lineNumbers: true,
                mode: mode,
                extraKeys: { 'Ctrl-F': 'findPersistent' },
                showMatchesOnScrollbar: true,
            });
            const searchString = $('.template-files-search__input:visible').val();
            if (searchString) {
                codeEditor.state.search = {
                    posFrom: null,
                    posTo: null,
                    lastQuery: searchString,
                    query: searchString,
                    overlay: null,
                };
                setTimeout(function() {codeEditor.execCommand('findNext');codeEditor.execCommand('findPersistent');}, 0);
            }
        },
        ckEditorDestroy: function(instanceName) {
            if (CKEDITOR.instances[instanceName]) {
                CKEDITOR.instances[instanceName].destroy();
            }
            settings_template.supportCkeditor = '';
        },
        getLetterWarningHtml: function(type, fileName) {
            switch (type) {
                case 'create':
                    return '<button class="link" id="newLetterFile"><span>' + lang.CREATE_FILE + ' ' + fileName + '</span></button>' +
                        ' ' + lang.LETTER_EDIT_BOTTOM_WARNING +
                        '<span tooltip="' + lang.LETTER_EDIT_BOTTOM_WARNING_TOOLTIP + '"><i class="fa fa-question-circle" aria-hidden="true"></i></span>';
                case 'delete':
                    return '<button class="link" id="deleteLetterFile" data-file="' + fileName + '"><span>' + lang.DELETE_FILE + ' ' + fileName + '</span></button>' + ' ' + lang.LETTER_EDIT_BOTTOM_WARNING_REMOVE;
                default:
                    throw new Error('Неправильный параметр «type» функции getLetterWarningHtml(). Доступные варианты: create, delete');
            }
        },
        updateMessageModal: function() {
            admin.ajaxRequest({
                    mguniqueurl: 'action/getEngineMessages',
                    lang: $('#messages-modal .select-lang').val(),
                },
                function(response) {
                    msghtml = '<ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true">';
                    $.each(response.data, function(gindex) {
                        var grp = gindex;
                        switch (grp) {
                            case 'order':
                                msghtml += '\
									<li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">Заказы</a>\
									<a part="order"><i class="fa fa-refresh"></i>' + lang.RETURN_DEFAULT + '</a>\
										<div class="accordion-content" data-tab-content="">';
                                break;
                            case 'product':
                                msghtml += '\
									</div>\
								</li>\
								<li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">Товары</a>\
									<a part="product"><i class="fa fa-refresh"></i>' + lang.RETURN_DEFAULT + '</a>\
										<div class="accordion-content" data-tab-content="">';
                                break;
                            case 'register':

                                msghtml += '\
									</div>\
								</li>\
								<li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">Регистрация и авторизация</a>\
									<a part="register"><i class="fa fa-refresh"></i>' + lang.RETURN_DEFAULT + '</a>\
										<div class="accordion-content" data-tab-content="">';
                                break;
                            case 'feedback':

                                msghtml += '\
									</div>\
								</li>\
								<li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">Обратная связь</a>\
									<a part="feedback"><i class="fa fa-refresh"></i>' + lang.RETURN_DEFAULT + '</a>\
										<div class="accordion-content" data-tab-content="">';
                                break;
                            case 'status':

                                msghtml += '\
									</div>\
								</li>\
								<li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">Статусы заказов</a>\
									<a part="status"><i class="fa fa-refresh"></i>' + lang.RETURN_DEFAULT + '</a>\
										<div class="accordion-content" data-tab-content="">';
                                break;

                            default:
                                break;
                        }
                        $.each(response.data[grp], function(index) {
                            msghtml += '\
							<div class="row">\
								<div class="small-12 medium-4 columns">\
									<label>' + response.data[grp][index].title + '<i class="fa fa-question-circle tip fl-right" aria-hidden="true" title="' + response.data[grp][index].tip + '"></i></label>\
								</div>\
								<div class="small-12 medium-8 columns">\
									<div class="input-with-text">\
										<input type="text" ajdi="' + response.data[grp][index].id + '" name="' + index + '" class="messages" value=\'' + response.data[grp][index].text + '\'>\
									</div>\
								</div>\
							</div>';
                        });
                    });

                    msghtml += '\
						</div>\
					</li>\
				</ul>';

                    $('#messages-modal .reveal-body .clearfix').html(msghtml);
                    admin.openModal($('#messages-modal'));
                });
        },
        /**
         * функция строит список цветовых схем
         */
        drawColorShemes: function(obj) {
            var arrayColor = obj.data('schemes');

            if (arrayColor.length == 0) {
                $('.template-schemes').hide();
                $('.js-settings-template').hide();
                $('.template-schemes .color-scheme').remove();
                return false;
            }

            var html = '<ul class="color-list">';
            var active = 'active';
            $.each(arrayColor, function(index, value) {
                var schemeName = value;
                var schemeColor = value;
                if (typeof value != 'string') {
                    schemeName = index;
                    if (schemeName == 'colorTitles') {
                        return true;
                    }
                    $.each(value, function(key, val) {
                        schemeColor = val.replace('#', '');
                        return false;
                    });
                }
                html += '<li class="color-scheme ' + active + '" data-scheme="' + schemeName + '" style="background:#' + schemeColor + ';"></li>';
                active = '';
            });

            var usercolors = obj.data('usercolors');
            if (usercolors) {

                var i = 1;
                html += '<li class="color-scheme popup-holder tooltip--center" tooltip="Назначить свои цвета" data-colors=\'' + JSON.stringify(usercolors) + '\' data-scheme="user_defined" \
                            style="background-image: url(' + mgBaseDir + '/mg-admin/design/images/palette.png);">\
	                <div class="custom-popup noAutoClose templ-user-colors" id="templateCustomColorPopup">';

                $.each(usercolors, function(key, value) {
                    var colorTitle = key;
                    if (arrayColor.colorTitles && arrayColor.colorTitles[key]) {
                        colorTitle = arrayColor.colorTitles[key];
                    }
                    html += '<div class="templ-user-colors__item">\
	                          <div class="templ-user-color__picker">\
	                            <div id="customTemplateColor_' + i + '"></div>\
	                            <input type="hidden" class="customTemplateColor" data-key="' + key + '" name="customTemplateColor_' + i + '" value="' + value + '">\
	                          </div>\
	                          <span class="templ-user-color__var-name"> – ' + colorTitle + '</span>\
	                      </div>';
                    i++;
                });
                html += '<div class="row">\
	                          <div class="large-12 columns">\
	                              <a class="button success fl-right applyCustomColor" href="javascript:void(0);"><i class="fa fa-check"></i> Применить</a>\
	                          </div>\
	                      </div>\
	                  </div>\
	            </li>';
            }

            html += '</ul>';

            $('.template-schemes .color-list').replaceWith(html);
            $('.template-schemes').show();
            $('.js-settings-template').show();
            $('#tab-template-settings #templateCustomColorPopup').hide();

            return html;
        },
        /**
         * функция строит список цветовых схем
         */
        drawColorShemesLanding: function(arrayColor) {
            if (arrayColor.length == 0) {
                $('.landing-schemes').hide();
                $('.landing-schemes .color-list').html('');
                return false;
            }

            var html = '<ul class="color-list">';
            var active = 'active';
            arrayColor.forEach(function(element) {
                html += '<li class="color-scheme-landing ' + active + '" data-scheme="' + element + '" style="background:#' + element + ';"></li>';
                active = '';
            });
            html += '</ul>';

            $('.landing-schemes .color-list').replaceWith(html);
            $('.landing-schemes').show();

            return html;
        },
        // установка шаблона
        addNewTemplate: function(type) {
            $('.' + type).ajaxForm({
                type: 'POST',
                url: 'ajax',
                data: {
                    mguniqueurl: 'action/addNewTemplate',
                },
                cache: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 'error') {
                        admin.indication(response.status, response.msg);
                    } else {
                        admin.indication(response.status, response.msg);
                        admin.refreshPanel();
                    }
                },
            }).submit();
        },
        quickstartLog: function(text) {
            const preloaderText = document.querySelector('.render-preloader .render-preloader-text-item');
            if ($('#quickstart-modal .quickstartLog').length < 1) {
                if (!preloaderText) console.log(text);
                const quickstartLog = preloaderText.querySelector('.quickstart-log');
                if (quickstartLog) quickstartLog.remove();
                preloaderText.insertAdjacentHTML('beforeEnd', `<span class="quickstart-log">${text}</span>`);
                return;
            }
            $('#quickstart-modal .quickstartLog').append(text + '\n').animate({ scrollTop: $('#quickstart-modal .quickstartLog')[0].scrollHeight - $('#quickstart-modal .quickstartLog').height() }, 1, function() {
            });
        },
        quickstartClear: function() {
            settings_template.quickstartLog('Очистка базы данных и удаление файлов (товары, характеристики, категории, страницы, деактивация плагинов)...');
            admin.ajaxRequest({
                    mguniqueurl: 'action/quickstartClear',
                },
                function(response) {
                    if (response.status == 'success') {
                        settings_template.quickstartLog('Завершена очистка данных');
                        settings_template.quickstartUnpackTables();
                    } else {
                        admin.indication(response.status, response.msg);
                        settings_template.quickstartLog(response.msg);
                        $('#quickstart-modal .save-button').prop('disabled', false);
                    }
                });
        },
        quickstartUnpackTables: function(tables) {
            if (typeof tables == 'undefined') {
                tables = 'scan';
                settings_template.quickstartLog('Заполнение базы демонстрационными данными...');
            }
            let tempName = $('#quickstart-modal .row:visible #templQuickstart').data('template');
            if (!tempName) tempName = $('.js-templates-options__install-demo').data('temp-name');
            if (!tempName) tempName = window.selectedTemplate;
            admin.ajaxRequest({
                    mguniqueurl: 'action/callBackupMethod',
                    func: 'unpackQuickstartTables',
                    template: tempName,
                    tables: tables,
                },
                function(response) {
                    if (response.status == 'success') {
                        if (response.data.remaining > 0) {
                            settings_template.quickstartLog(
                                'Обработано ' + (response.data.totalTables - response.data.remaining) + ' таблиц из ' + response.data.totalTables,
                            );

                            settings_template.quickstartUnpackTables(response.data.tables);
                        } else {
                            settings_template.quickstartLog('Заполнение базы демонстрационными данными завершено');
                            settings_template.quickstartCopyProducts();
                        }
                    } else {
                        admin.indication(response.status, response.msg);
                        settings_template.quickstartLog(response.msg);
                        $('#quickstart-modal .save-button').prop('disabled', false);
                    }
                });
        },
        quickstartCopyProducts: function() {
            settings_template.quickstartLog('Распаковка изображений товаров в папку uploads/...');
            let tempName = $('#quickstart-modal .row:visible #templQuickstart').data('template');
            if (!tempName) tempName = $('.js-templates-options__install-demo').data('temp-name');
            if (!tempName) tempName = window.selectedTemplate;
            admin.ajaxRequest({
                    mguniqueurl: 'action/quickstartCopyFiles',
                    template: tempName,
                    type: 'product',
                },
                function(response) {
                    if (response.status == 'success') {
                        settings_template.quickstartLog('Распаковка изображений товаров завершена');
                        settings_template.quickstartCopyRest();
                    } else {
                        admin.indication(response.status, response.msg);
                        settings_template.quickstartLog(response.msg);
                        $('#quickstart-modal .save-button').prop('disabled', false);
                    }
                });
        },
        quickstartCopyRest: function() {
            settings_template.quickstartLog('Распаковка дополнительных файлов...');
            let tempName = $('#quickstart-modal .row:visible #templQuickstart').data('template');
            if (!tempName) tempName = $('.js-templates-options__install-demo').data('temp-name');
            if (!tempName) tempName = window.selectedTemplate;
            admin.ajaxRequest({
                    mguniqueurl: 'action/quickstartCopyFiles',
                    template: tempName,
                    type: 'misc',
                },
                function(response) {
                    if (response.status == 'success') {
                        settings_template.quickstartLog('Распаковка дополнительных файлов завершена');
                        settings_template.quickstartRegPlugins();
                    } else {
                        admin.indication(response.status, response.msg);
                        settings_template.quickstartLog(response.msg);
                        $('#quickstart-modal .save-button').prop('disabled', false);
                    }
                });
        },
        quickstartRegPlugins: function() {
            settings_template.quickstartLog('Завершение...');
            let tempName = $('#quickstart-modal .row:visible #templQuickstart').data('template');
            if (!tempName) tempName = $('.js-templates-options__install-demo').data('temp-name');
            if (!tempName) tempName = window.selectedTemplate;
            admin.ajaxRequest({
                    mguniqueurl: 'action/quickstartRegPlugins',
                    template: tempName,
                    dropFolder: 1,
                },
                function(response) {
                    if (response.status == 'success') {
                        admin.indication(response.status, 'Демонстрационные данные установлены');
                        settings_template.quickstartLog('Успешно завершен процесс установки демонстрационных данных');
                        settings_template.runTemplateOptionsActions();
                    } else {
                        admin.indication(response.status, response.msg);
                        settings_template.quickstartLog(response.msg);
                        $('#quickstart-modal .save-button').prop('disabled', false);
                    }
                });
        },
        
        /**
         * Отправляет запрос для фиксации в БД первое создание сайта (чтоб после установки демо-базы не открывать повторно окно с выбором варианта установки шаблона)
         */
        isNoFirstAjax: function () {
            $.ajax({
                type: "POST",
                cache: false,
                url: mgBaseDir + "/ajaxrequest",
                data: {
                action: "getOptionsBusy",
                saasHandler: "design-editor",
                }
            });
        },
        isNoFirstChangeTemplateAjax: function () {
            $.ajax({
                type: "POST",
                cache: false,
                url: mgBaseDir + "/ajaxrequest",
                data: {
                action: "NoFirstChangeTemplate",
                saasHandler: "design-editor",
                }
            });
        },
        searchTemplateFiles: function (searchString) {
            const currentTab = $('#setting-templates-code .tabs-panel:visible').attr('id');
            if (!currentTab) {
                return false;
            }
            if (currentTab === 'ttab3') {
                admin.ajaxRequest(
                    {
                        mguniqueurl: 'action/searchTemplateFiles',
                        searchString: searchString,
                    },
                    function (response) {
                        admin.indication(response.status, response.msg);
                        $('#setting-templates-code .tabs-panel:visible .file-template__component_name').hide();
                        $('#setting-templates-code .tabs-panel:visible .file-template').hide();
                        $('.template-files-search__labels div').hide();
                        if (response.data.layouts) {
                            if ($(response.data.layouts).length) {
                                $('#setting-templates-code .tabs-panel:visible .template-files-search__found').show();
                            } else {
                                $('#setting-templates-code .tabs-panel:visible .template-files-search__notfound').show();
                            }
                            for (layoutKey in response.data.layouts) {
                                const layout = response.data.layouts[layoutKey];
                                if (layout[2]) {
                                    const component = layout[2];
                                    $('#setting-templates-code .tabs-panel:visible .file-template__component_name[data-component="'+component+'"]').show();
                                }
                                $('#setting-templates-code .tabs-panel:visible .file-template[data-path="'+layout[0]+'"]').show();
                            }
                            if ($('#setting-templates-code .tabs-panel:visible .file-template:visible').length) {
                                $('#setting-templates-code .tabs-panel:visible .file-template:visible')[0].click();
                            }
                        }
                    }
                );
            } else {
                admin.ajaxRequest(
                    {
                        mguniqueurl: 'action/searchTemplateFiles',
                        searchString: searchString,
                        views: 1,
                    },
                    function (response) {
                        admin.indication(response.status, response.msg);
                        $('#setting-templates-code .tabs-panel:visible .file-template').hide();
                        $('.template-files-search__labels div').hide();
                        if (response.data.views) {
                            if ($(response.data.views).length) {
                                $('#setting-templates-code .tabs-panel:visible .template-files-search__found').show();
                            } else {
                                $('#setting-templates-code .tabs-panel:visible .template-files-search__notfound').show();
                            }
                            for (viewKey in response.data.views) {
                                const view = response.data.views[viewKey];
                                $('#setting-templates-code .tabs-panel:visible .file-template[data-path="'+view[0]+'"]').show();
                            }
                            if ($('#setting-templates-code .tabs-panel:visible .file-template:visible').length) {
                                $('#setting-templates-code .tabs-panel:visible .file-template:visible')[0].click();
                            }
                        }
                    }
                );
            }
        },
        clearTemplateFilesSearch: function() {
            $('#setting-templates-code .tabs-panel .file-template__component_name').show();
            $('#setting-templates-code .tabs-panel .file-template').show();
            $('.template-files-search__input').val('');
            $('.template-files-search__labels div').hide();
            if ($('#setting-templates-code .tabs-panel:visible .file-template:visible').length) {
                $('#setting-templates-code .tabs-panel:visible .file-template:visible')[0].click();
            }
        }
    };
})();
