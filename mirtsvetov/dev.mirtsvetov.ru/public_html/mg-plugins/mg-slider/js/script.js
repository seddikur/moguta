/**
 /*
 * Модуль  sliderActionModule, подключается на странице настроек плагина.
 */


var sliderModule = (function() {

    return {
        lang: [], // локаль плагина
        pluginName: 'mg-slider',
        slides: [],
        currentImg: '',
        fontSelect: document.querySelector('.js-font-select'),
        loop: 0,
        autoplay: 0,
        codeEditor: null,
        init: function() {
            admin.includePickr();

            // установка локали плагина
            admin.ajaxRequest({
                    mguniqueurl: 'action/seLocalesToPlug',
                    pluginName: sliderModule.pluginName,
                },
                function(response) {
                    sliderModule.lang = response.data;
                },
            );

            //Переключение между слайдерами
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-select-slider', function() {
                var id = $(this).data('id');
                $('.js-save-slider').data('id', id);

                $('.slider-tabs__item_active').removeClass('slider-tabs__item_active');
                $(this).addClass('slider-tabs__item_active');
                sliderModule.clearData();

                var shortCodeBtn = document.querySelector('.js-short-code-copy');
                shortCodeBtn.addEventListener('click', function(event) {
                    sliderModule.copyFromInput('js-short-code-copy-from', event);
                });

                if (id === 'new') {
                    $('.js-delete-slider').hide();
                    $('.js-delete-slide').hide();
                    $('.js-edit-slide').hide();
                    $('.js-copy-slide').hide();
                    $('.js-slider-empty-shortcode').show();
                    $('.js-slider-shortcode').hide();

                } else {
                    cookie('lastSlider', id);
                    $('.js-delete-slider').show().data('id', id);
                    var shortCode = '[mgslider id=\'' + id + '\']';
                    $('.js-short-code-copy-from').val(shortCode);
                    $('.js-slider-empty-shortcode').hide();
                    $('.js-slider-shortcode').show();
                    sliderModule.fillData(id);
                }
            });

            //Сохранение слайдера
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-save-slider', function() {
                sliderModule.saveData($(this).data('id'), true);
            });

            //Удаление слайдера
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-delete-slider', function() {
                if (!confirm(sliderModule.lang['CONFIRM_DELETE_SLIDER'])) {
                    return false;
                }
                var id_slider = $(this).data('id');
                admin.ajaxRequest({
                        mguniqueurl: 'action/deleteSlider',
                        pluginHandler: sliderModule.pluginName,
                        id: id_slider,
                    },
                    function(response) {
                        admin.indication(response.status, response.msg);
                        if (response.status == 'success') {
                            admin.refreshPanel();
                        }
                    });
            });

            //Сброс слайдера
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-reset-slider', function() {
                if (!confirm('Сбросить настройки слайдера?')) {
                    return false;
                }
                sliderModule.clearData(false);
                sliderModule.reloadPreview();
            });

            //Редактировать слайд
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-edit-slide', function() {
                sliderModule.clearModal();
                var slide_id = $('.swiper-slide-active').data('slide-id');
                $('.js-save-slide').data('id', slide_id);
                sliderModule.fillModal(slide_id);
                admin.openModal('#slide-modal');
                $('[name=type]').change();
                //Останавливаем слайдер
                if (typeof swiper !== 'undefined') {
                    swiper.autoplay.stop();
                }
            });

            //Новый слайд
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-new-slide', function() {
                if (sliderModule.slides.length > 9) {
                    admin.indication('error', sliderModule.lang.COUNT_WARNING);
                    return;
                }
                sliderModule.clearModal();
                $('.js-save-slide').data('id', 'new');
                admin.openModal('#slide-modal');
                //Останавливаем слайдер
                if (typeof swiper !== 'undefined') {
                    swiper.autoplay.stop();
                }

                $('#slide-modal .option').each(function() {
                    admin.initPickr($(this).attr('name'));
                });
            });

            //Закрытие модалки
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-close-slide-modal', function() {
                sliderModule.startSliderAfterCloseModal();
            });

            //Удаление слайда
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-delete-slide', function() {
                if (!confirm(sliderModule.lang['CONFIRM_DELETE_SLIDE'])) {
                    return false;
                }
                var slide_id = $('.swiper-slide-active').data('slide-id');
                sliderModule.slides.splice(slide_id, 1);
                if (sliderModule.slides.length == 0) {
                    $('.js-slider-sort').html('');
                    $('.js-preview-container').html('<a role="button" href="javascript:void(0)" class="preview-slide__empty js-new-slide">Добавьте слайд</a>');
                }
                sliderModule.reloadPreview();
            });

            //Переключение типа слайда в модалке
            $('.admin-center').on('change', '.section-' + sliderModule.pluginName + ' [name="type"]', function() {
                sliderModule.hideAllTypeSlideModal();
                var type = $(this).val();
                $('.js-slide-' + type + '-options').show();
            });

            //Сохранение слайда
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-save-slide', function() {
                sliderModule.startSliderAfterCloseModal();
                sliderModule.saveSlide($(this).data('id'));
                admin.closeModal('#slide-modal');
                if ($('.js-save-slider').data('id') != 'new') {
                    sliderModule.saveData($('.js-save-slider').data('id'), false);
                } else {
                    sliderModule.reloadPreview();
                }
            });

            //Копирование слайда
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-copy-slide', function() {
                if (sliderModule.slides.length > 9) {
                    admin.indication('error', sliderModule.lang.COUNT_WARNING);
                    return;
                }
                var id = $('.swiper-slide-active').data('slide-id');
                sliderModule.slides.push(sliderModule.slides[id]);
                sliderModule.reloadPreview();
            });

            //Выбор изображения
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-browseImage', function() {
                sliderModule.currentImg = $(this).data('name');
                admin.openUploader('sliderModule.getFile');
            });

            //Выбор изображения (шаблон)
            $('.admin-center').on('click', '.section-' + sliderModule.pluginName + ' .js-browseImage-template', function() {
                admin.openUploader('sliderModule.getFileTemplate');
            });

            //Реалтайм обновление слайдера
            $('.admin-center').on('change', '.section-' + sliderModule.pluginName + ' .js-slider-opt  input, .js-slider-opt select', function() {
                sliderModule.reloadPreview();
            });

            $('.admin-center').on('change', '#invisible', sliderModule.checkInvisibleOnAllDevices);

            setTimeout(function() {
                $('.js-font-select').on('sumo:closed', sliderModule.fontPreview);
            }, 0);

            var shortCodeBtn = document.querySelector('.js-short-code-copy');
            shortCodeBtn.addEventListener('click', function(event) {
                sliderModule.copyFromInput('js-short-code-copy-from', event);
            });
        },
        startSliderAfterCloseModal: function() {
            if (sliderModule.autoplay == 1) {
                swiper.autoplay.start();
            }
        },
        fillModal: function(id) {
            var data = sliderModule.slides[id];

            $('[name="type"]').val(data.type);
            Object.keys(main.modal_template).forEach(function(key) {
                var group = main.modal_template[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    $('[name="' + key + '"]').val(data[key]);
                    if (option.type == 'checkbox') {
                        $('[name=' + key + ']').prop('checked', (data[key] == 'true' || data[key] == true));
                    }
                    if (option.type == 'img') {
                        $('.js-image-preview-' + key).attr('src', mgBaseDir + '/' + decodeURIComponent(data[key]));
                        if (data[key]) {
                            $('.js-image-preview-' + key).show();
                        }
                    }
                    if (option.type == 'color') {
                        admin.initPickr(key, option.opacity);
                    }
                });
            });

            Object.keys(main.modal_image).forEach(function(key) {
                var group = main.modal_image[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    $('[name="' + key + '"]').val(data[key]);
                    if (option.type == 'textarea' && option.ckeditor == 'true') {
                        setTimeout(() => {
                            CKEDITOR.instances[key].setData(data[key]);
                        }, 500);
                    }
                    if (option.type == 'img') {
                        $('.js-image-preview-' + key).attr('src', mgBaseDir + '/' + data[key]);
                        $('.js-image-preview-' + key).show();
                    }
                    if (option.type == 'checkbox') {
                        $('[name=' + key + ']').prop('checked', data[key] == true);
                    }
                });
            });

            Object.keys(main.modal_html).forEach(function(key) {
                var group = main.modal_html[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    $('[name="' + key + '"]').val(data[key]);
                    if (option.type == 'textarea' && option.ckeditor == 'true') {
                        setTimeout(() => {
                            CKEDITOR.instances[key].setData(data[key]);
                        }, 500);
                    }
                    if (option.type == 'textarea' && option.codemirror == 'true') {
                        setTimeout(() => {
                            sliderModule.initCodeMirror(key);
                        }, 500);
                    }
                    if (option.type == 'img') {
                        $('.js-image-preview-' + key).attr('src', mgBaseDir + '/' + data[key]);
                        $('.js-image-preview-' + key).show();
                    }
                    if (option.type == 'checkbox') {
                        $('[name=' + key + ']').prop('checked', data[key] == true);
                    }
                });
            });
            admin.reloadComboBoxes();
            sliderModule.initPositionSelectAction();
        },
        initPositionSelectAction: function() {
            let container = document.querySelector('.js-slide-template-options');
            // Префексы селектов для каждого размера экрана
            let selectPositionPrefix = ['', 'md_', 't_', 'm_'];

            selectPositionPrefix.forEach(selectPrefix => {
                // Находим селект под определенный размер экрана
                let positionSelect = container.querySelector(`#${selectPrefix}position_content`);
                // Находим инпут куда вводится отступ от центра для определенной ширины экрана
                let inputName = `${selectPrefix}position_offset_center`;
                if (positionSelect) {
                    // Выполняем проверку на значение селекта
                    sliderModule.selectAction(positionSelect.value, inputName);
                    //Привязываем событие на изменение селекта
                    positionSelect.addEventListener('change', () => {
                        sliderModule.selectAction(positionSelect.value, inputName);
                    })
                }
                
            });
        },
        selectAction: function(value, inputId) {
            // Находим контейнер, в котором вложен инпут
            let inputContainer = document.querySelector(`input[name=${inputId}]`).parentNode;
            if (value === 'center') {
                inputContainer.style.display = '';
            } else {
                inputContainer.style.display = 'none';
            }
        },
        saveSlide: function(id) {
            var data = {};
            data.type = $('[name="type"]').val();
            Object.keys(main.modal_template).forEach(function(key) {
                var group = main.modal_template[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    data[key] = $('[name="' + key + '"]').val();
                    if (option.type == 'textarea' && option.ckeditor == 'true') {
                        if (CKEDITOR.instances[key]) {
                            data[key] = CKEDITOR.instances[key].getData();
                        }
                    }
                    if (option.type == 'checkbox') {
                        data[key] = (true == $('[name=' + key + ']').prop('checked'));
                    }
                });
            });

            Object.keys(main.modal_html).forEach(function(key) {
                var group = main.modal_html[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    data[key] = $('[name="' + key + '"]').val();
                    if (option.type == 'textarea' && option.ckeditor == 'true') {
                        if (CKEDITOR.instances[key]) {
                            data[key] = CKEDITOR.instances[key].getData();
                        }
                    }
                    if (option.type == 'textarea' && option.codemirror == 'true') {
                        data[key] = sliderModule.codeEditor.getValue();
                    }
                    if (option.type == 'checkbox') {
                        data[key] = (true == $('[name=' + key + ']').prop('checked'));
                    }
                });
            });

            Object.keys(main.modal_image).forEach(function(key) {
                var group = main.modal_image[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    data[key] = $('[name="' + key + '"]').val();
                    if (option.type == 'textarea' && option.ckeditor == 'true') {
                        if (CKEDITOR.instances[key]) {
                            data[key] = CKEDITOR.instances[key].getData();
                        }
                    }
                    if (option.type == 'checkbox') {
                        data[key] = (true == $('[name=' + key + ']').prop('checked'));
                    }
                });
            });

            if (id === 'new') {
                if (sliderModule.slides === null || sliderModule.slides === false || sliderModule.slides.length == 0) {
                    sliderModule.slides = [data];
                } else {
                    sliderModule.slides.push(data);
                }
            } else {
                sliderModule.slides[id] = data;
            }
        },
        checkRelation: function(name, value) {
            if (relation[name] !== undefined) {
                if ($(this).prop('checked') == true) {
                    $('.admin-center [name="' + value + '"]').parents('.js-slider-option').slideDown();
                } else {
                    $('.admin-center [name="' + value + '"]').parents('.js-slider-option').slideUp();
                }
            }
        },
        clearModal: function() {
            var colorKeys = [];
            
            //Вывод всех настроек слайда
            Object.keys(main.modal_template).forEach(function(key) {
                var group = main.modal_template[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    if (option.value === undefined) {
                        $('[name=' + key + ']').val('');
                    } else {
                        $('[name=' + key + ']').val(option.value);
                    }
                    if (option.type == 'color') {
                        if (admin.PICKR_STORAGE) {
                            admin.PICKR_STORAGE.forEach(function(item) {
                                if (item.options && item.options.el.id === key) {
                                    item.destroyAndRemove();
                                    $('[name="' + key + '"]').parent().prepend('<div id="' + key + '"></div>');
                                }
                            });
                        }
                    }
                    if (option.type == 'img') {
                        $('.js-image-preview-' + key).attr('src', '');
                        $('.js-image-preview-' + key).hide();
                    }
                    // if (option.type == 'textarea' && option.ckeditor == 'true') {

                    // }
                    if (option.type == 'checkbox') {
                        if (option.value === undefined) {
                            $('[name=' + key + ']').prop('checked', '');
                            // sliderModule.checkRelation(key, value);
                        } else {
                            $('[name=' + key + ']').prop('checked', option.value == 'true');
                        }
                    }
                });
            });

            Object.keys(main.modal_image).forEach(function(key) {
                var group = main.modal_image[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    if (option.value === undefined) {
                        $('[name=' + key + ']').val('');
                    } else {
                        $('[name=' + key + ']').val(option.value);
                    }
                    if (option.type == 'img') {
                        $('.js-image-preview-' + key).attr('src', '');
                        $('.js-image-preview-' + key).hide();
                    }
                    if (option.type == 'textarea' && option.ckeditor == 'true') {
                        if (!CKEDITOR.instances[key]) {
                            CKEDITOR.replace(key);
                        } else {
                            CKEDITOR.instances[key].setData('');
                        }
                    }
                    if (option.type == 'checkbox') {
                        if (option.value === undefined) {
                            $('[name=' + key + ']').prop('checked', '');
                            // sliderModule.checkRelation(key, value);
                        } else {
                            $('[name=' + key + ']').prop('checked', option.value == 'true');
                            // sliderModule.checkRelation(key, value);
                        }
                    }
                });
            });

            Object.keys(main.modal_html).forEach(function(key) {
                var group = main.modal_html[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    if (option.value === undefined) {
                        $('[name=' + key + ']').val('');
                    } else {
                        $('[name=' + key + ']').val(option.value);
                    }
                    if (option.type == 'img') {
                        $('.js-image-preview-' + key).attr('src', '');
                        $('.js-image-preview-' + key).hide();
                    }
                    if (option.type == 'textarea' && option.ckeditor == 'true') {
                        if (!CKEDITOR.instances[key]) {
                            CKEDITOR.replace(key);
                        } else {
                            CKEDITOR.instances[key].setData('');
                        }
                    }
                    if (option.type == 'textarea' && option.codemirror == 'true') {
                        setTimeout(() => {
                            sliderModule.initCodeMirror(key);
                        }, 500);
                    }
                    if (option.type == 'checkbox') {
                        if (option.value === undefined) {
                            $('[name=' + key + ']').prop('checked', '');
                        } else {
                            $('[name=' + key + ']').prop('checked', option.value == 'true');
                        }
                    }
                });
            });

            sliderModule.hideAllTypeSlideModal();
            admin.reloadComboBoxes();
            $('[name="type"]').val('template');
            $('[name="type"]').change();

            var fontPreviewWrap = document.querySelector('.js-font-preview');
            if (fontPreviewWrap !== null) {
                fontPreviewWrap.remove();
            }
        },
        hideAllTypeSlideModal: function() {
            $('.js-slide-template-options').hide();
            $('.js-slide-image-options').hide();
            $('.js-slide-html-options').hide();
        },
        fillData: function(id_slider) {
            admin.ajaxRequest({
                    mguniqueurl: 'action/getSlider',
                    pluginHandler: sliderModule.pluginName,
                    id: id_slider,
                },
                function(response) {
                    // admin.indication(response.status, response.msg);
                    if (response.status == 'success') {
                        $('[name="name_slider"]').val(response.data.name_slider);
                        if (response.data.options) {
                            Object.keys(main.options).forEach(function(key) {
                                var group = main.options[key];
                                
                                Object.keys(group).forEach(function(key) {
                                    var option = group[key];
                                    if (key == 'name_slider') {
                                        return;
                                    }
                                    $('[name=' + key + ']').val(response.data.options[key]);

                                    if (option.type == 'checkbox') {
                                        $('[name=' + key + ']').prop('checked', response.data.options[key] == 'true');
                                    }

                                    if (option.type == 'color') {
                                        admin.initPickr(key, option.opacity);
                                    }
                                });
                            });
                        }
                        sliderModule.slides = response.data.slides;
                        if (response.data.slides === null || response.data.slides === false) {
                            $('.js-edit-slide').hide();
                            $('.js-delete-slide').hide();
                        } else {
                            $('.js-edit-slide').show();
                            $('.js-delete-slide').show();
                        }
                        sliderModule.reloadPreview();
                    }
                });
        },
        getData: function() {
            var data = {
                'options': {},
                'slides': sliderModule.slides,
                'name_slider': sliderModule.strip($('[name=name_slider]').val()),
            };

            Object.keys(main.options).forEach(function(key) {
                var group = main.options[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    if (key == 'name_slider') {
                        return;
                    }
                    data.options[key] = $('[name=' + key + ']').val();
                    if (option.type == 'checkbox') {
                        data.options[key] = (true == $('[name=' + key + ']').prop('checked'));
                    }
                });
            });

            return data;
        },
        saveData: function(id, refresh) {
            var id_slider = id;
            if (sliderModule.slides === null || sliderModule.slides.length == 0) {
                admin.indication('error', 'Для сохранения необходим хотя бы один слайд');
                return false;
            }
            var data = sliderModule.getData();
            admin.ajaxRequest({
                    mguniqueurl: 'action/saveSlider',
                    pluginHandler: sliderModule.pluginName,
                    id: id_slider,
                    data: data,
                },
                function(response) {
                    admin.indication(response.status, response.msg);
                    if (response.status == 'success') {
                        if (refresh) {
                            admin.refreshPanel();
                        } else {
                            sliderModule.reloadPreview();
                        }
                    }
                });
        },
        reloadPreview: function() {
            var id = $('.swiper-slide-active').data('slide-id');
            var data = sliderModule.getData();
            if (data.slides === null || data.slides === false || data.slides.length == 0) {
                $('.js-delete-slide').hide();
                $('.js-edit-slide').hide();
                $('.js-copy-slide').hide();
                return false;
            } else {
                $('.js-delete-slide').show();
                $('.js-edit-slide').show();
                $('.js-copy-slide').show();
            }
            admin.ajaxRequest({
                    mguniqueurl: 'action/getSliderPreview',
                    pluginHandler: sliderModule.pluginName,
                    data: data,
                },
                function(response) {
                    // admin.indication(response.status, response.msg);
                    if (response.status == 'success' && response.data) {
                        $('.js-preview-container').html(response.data);

                        //Запоминаем чтобы стопорить слайдер при редактирование слайда
                        var autoplay = data.options.autoplay;
                        if (autoplay != '') {
                            sliderModule.autoplay = 1;
                        } else {
                            sliderModule.autoplay = 0;
                        }

                        //Если включено зацикливание, то в slideTo нужно прибавлять 1 к индексу
                        var loop = data.options.loop;
                        if (loop == true) {
                            sliderModule.loop = 1;
                        } else {
                            sliderModule.loop = 0;
                        }

                        if (id === undefined) {
                            id = 0;
                        }
                        var to = id + sliderModule.loop;

                        sliderModule.initSortSlides(id);
                        setTimeout(() => {
                            //Переходим на активный слайд
                            swiper.slideTo(to, 0);

                            //Выделаяем активную превьюху
                            swiper.on('slideChangeTransitionEnd', function() {
                                var id = $('.swiper-slide-active').data('slide-id');
                                $('.slides-sorter__item_active').removeClass('slides-sorter__item_active');
                                $('.js-slide-sort[data-id="' + (id) + '"]').addClass('slides-sorter__item_active');
                            });
                        }, 0);

                    }
                });
        },
        //Только для одного текст ареа!!!
        initCodeMirror: function(name) {
            includeJS(mgBaseDir + '/mg-core/script/codemirror/lib/codemirror.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/php/php.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/xml/xml.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/mode/javascript/javascript.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/search.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/searchcursor.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/jump-to-line.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/match-highlighter.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/search/matchesonscrollbar.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/dialog/dialog.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/scroll/annotatescrollbar.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/scroll/scrollpastend.js');
            includeJS(mgBaseDir + '/mg-core/script/codemirror/addon/scroll/simplescrollbars.js');
            $('.CodeMirror').remove();
            sliderModule.codeEditor = CodeMirror.fromTextArea(document.querySelector('[name=' + name + ']'), {
                lineNumbers: true,
                mode: 'application/x-httpd-php',
                extraKeys: { 'Ctrl-F': 'findPersistent' },
                showMatchesOnScrollbar: true,
                lineWrapping: true,
            });

            $('.CodeMirror').height(200);
            $('.CodeMirror-scroll').scrollTop(2);
        },
        initSortSlides: function(currentSlide) {
            //Чистим сорт
            $('.js-slider-sort').html('');
            //Если слайдов нет, ту выходим
            if (sliderModule.slides === null || sliderModule.slides.length == 0) {
                return false;
            }
            //Проходимся по всем слайдам
            sliderModule.slides.forEach((value, index, any) => {
                //Дефолтное обозначение
                var body = 'slide' + index;
                //Если  тип слайда картинка, то вставляем картинку
                if (value['type'] == 'image') {
                    if (value['src'] != null) {
                        body = '<img class="slide-preview__img" src="' + mgBaseDir + '/' + decodeURIComponent(value['src']) + '">';
                    }
                    //Если тип слайда "Шаблон", то берем либо картинку, либо текст
                } else if (value['type'] == 'template') {
                    if (value['img_path'] != '') {
                        value['img_path'] = decodeURIComponent(value['img_path']);
                        body = '<img class="slide-preview__img" src="' + mgBaseDir + '/' + value['img_path'] + '">';
                    } else if (value['title'] != '') {
                        body = value['title'];
                    } else if (value['content'] != '') {
                        body = value['content'];
                    } else if (value['button'] != '') {
                        body = value['button'];
                    }
                } else if (value['type'] == 'html') {
                    body = sliderModule.searchAnyInHTML(value['html']);
                }

                //Отмечаем спрятанный слайд
                var invisible = '';
                if (value['invisible'] !== undefined && (value['invisible'] == 'true' || value['invisible'] == true)) {
                    invisible = ' slides-sorter__item_hidden';
                }

                //Отмечаем активный слайд
                var active = '';
                if (index == currentSlide) {
                    active = ' slides-sorter__item_active';
                }

                //Создаем блок для списка сортировка
                $('.js-slider-sort').append('<li onclick="sliderModule.onClickPreview(' + index + ');" class="js-slide-sort slides-sorter__item slide-preview' + invisible + active + '" data-id="' + index + '" style="width:100px;height:100px;border:1px grey solid;display: inline-flex;">' + body + '</li>');
            });

            //Создаем сортбл и перезагружаем превью слайдера если поменяли порядок
            $('.js-slider-sort').sortable({
                animation: 150,
                placeholder: 'slide-preview-ghost',
                update: function() {
                    var newSlides = [];
                    $('.js-slide-sort').each(function(index) {
                        newSlides.push(sliderModule.slides[$(this).data('id')]);
                    });
                    sliderModule.slides = newSlides;
                    sliderModule.reloadPreview();
                },
            });
        },
        onClickPreview: function(index) {
            setTimeout(() => {
                swiper.slideTo(index + sliderModule.loop, 0);
            }, 10);
            $('.slides-sorter__item_active').removeClass('slides-sorter__item_active');
            $('.js-slide-sort[data-id="' + (index + sliderModule.loop) + '"]').addClass('slides-sorter__item_active');
        },
        searchAnyInHTML: function(body) {
            const parser = new DOMParser();
            result = parser.parseFromString(body, 'text/html');
            var images = result.getElementsByTagName('img');
            if (images.length) {
                return '<img class="slide-preview__img" src="' + images[0].getAttribute('src') + '">';
            }
            var pTags = result.getElementsByTagName('p');
            if (pTags.length) {
                return pTags[0].innerText;
            }
            var spanTags = result.getElementsByTagName('span');
            if (spanTags.length) {
                return spanTags[0].innerText;
            }
            return 'html';
        },
        clearData: function(withSlides) {
            withSlides = typeof withSlides !== 'undefined' ? withSlides : true;
            Object.keys(main.options).forEach(function(key) {
                var group = main.options[key];
                Object.keys(group).forEach(function(key) {
                    var option = group[key];
                    if (option.value === undefined) {
                        $('[name=' + key + ']').val('');
                    } else {
                        $('[name=' + key + ']').val(option.value);
                    }
                    if (option.type == 'checkbox') {
                        if (option.value === undefined) {
                            $('[name=' + key + ']').prop('checked', '');
                        } else {
                            $('[name=' + key + ']').prop('checked', option.value == 'true');
                        }
                    }
                });
            });

            if (withSlides) {
                sliderModule.slides = [];
                $('.js-slider-sort').html('');
                $('.js-preview-container').html('<a role="button" href="javascript:void(0)" class="preview-slide__empty js-new-slide">Добавьте слайд</a>');
            }
        },
        getFile: function(file) {
            $('.admin-center .section-' + sliderModule.pluginName + ' input[name="' + sliderModule.currentImg + '"]').val(file.path.replace(/\\/g, '/'));
            $('.js-image-preview-' + sliderModule.currentImg).attr('src', file.url);
            $('.js-image-preview-' + sliderModule.currentImg).show();
        },
        fontPreview: function() {
            var fontValue = sliderModule.fontSelect.value,
                fontLinkUrl = 'https://fonts.googleapis.com/css?family=' + fontValue.replace(/ /g, '+') + '&subset=cyrillic',
                fontLink = document.createElement('link'),
                fontSelect = document.querySelector('.js-font-select'),
                previewHtml = document.createElement('span'),
                lorem = 'Съешь же ещё этих мягких французских булок, да выпей чаю.',
                previewWrapClass = 'js-font-preview',
                previewWrap = document.querySelector('.' + previewWrapClass),
                title = document.querySelector('.js-input-title').value,
                subTitleFull = '',
                btnText = document.querySelector('.js-input-button').value;

            if (!CKEDITOR.instances['content']) {
                subTitleFull = document.querySelector('.js-input-content').value;
            } else {
                subTitleFull = CKEDITOR.instances['content'].getData();
            }
            var subTitle = sliderModule.strip(subTitleFull);

            var titleHtml = (title) ? '<span class="font-preview__item font-preview__item_title">' + title + '</span>' : '',
                subTitleHtml = (subTitle) ? '<span class="font-preview__item">' + subTitle + '</span>' : '',
                btnHtml = (btnText) ? '<span class="font-preview__item">' + btnText + '</span>' : '';

            var previewHtmlInner = titleHtml + subTitleHtml + btnHtml;

            setAttributes(previewHtml, {
                class: previewWrapClass + ' font-preview',
                style: 'font-family:' + fontValue + ';',
            });


            previewHtml.innerHTML = (previewHtmlInner) ? previewHtmlInner : lorem;

            setAttributes(fontLink, {
                rel: 'stylesheet',
                href: fontLinkUrl,
            });

            previewHtml.prepend(fontLink);

            if (previewWrap) {
                previewWrap.remove();
            }

            fontSelect.parentElement.after(previewHtml);

            function setAttributes(el, attrs) {
                Object.keys(attrs).forEach(key => el.setAttribute(key, attrs[key]));
            }

        },
        copyFromInput: function(className, event) {
            var input = document.querySelector('.' + className),
                copyBtn = event.target;

            input.focus();
            input.select();

            try {
                var successful = document.execCommand('copy');
                copyBtn.setAttribute('tooltip', '✓ Скопировано');
                copyBtn.classList.add('tooltip--green');

                setTimeout(function() {
                    copyBtn.removeAttribute('tooltip');
                }, 3000);

            } catch (err) {
                throw 'Невозможно скопировать';
            }
        },
        strip: function(html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = html;

            return tmp.textContent || tmp.innerText;
        },
        checkInvisibleOnAllDevices: function(elem) {
            var mobDevicesCheckboxes = $('#md_invisible, #t_invisible, #m_invisible');
            if (elem.currentTarget.checked) {
                if (confirm('Не выводить слайд на всех устройствах?')) {
                    mobDevicesCheckboxes.prop('checked', true);
                }
            } else {
                if (confirm('Выводить слайд на всех устройствах?')) {
                    mobDevicesCheckboxes.prop('checked', false);
                }
            }
        },
    };
})();

sliderModule.init();
