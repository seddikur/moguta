/**
 * Модуль для раздела "Дашбоард".
 */
var dashboard = {

    // для инициализации всех необходимых скриптов
    init: function() {
        // Инициализация обработчиков
        dashboard.initEvents();
    },

    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    initEvents: function() {
        $('.section-saasinfo').on('click', '.js-check-status-step', function() {
            dashboard.updateStatusAllSteps($(this));
            dashboard.updateStatusDashboard();
        });

        $('.section-saasinfo').on('click', '.js-show-setting-payment', function() {
            dashboard.showSettingSection('#tab-paymentMethod-settings');
        });

        $('.section-saasinfo').on('click', '.js-click-add-product', function() {
            includeJS(admin.SITE + '/mg-core/script/admin/catalog.js');
            callback = catalog.init;
            admin.show('catalog.php', 'adminpage',  cookie("catalog_getparam"), callback);
        });

        $('.section-saasinfo').on('click', '.js-click-add-category', function() {

            includeJS(admin.SITE + '/mg-core/script/admin/category.js');
            callback = category.init;
            admin.show('category.php', 'adminpage',  cookie("category_getparam"), callback);
        });


        $('.section-saasinfo').on('click', '.js-show-setting-delivery', function() {
            dashboard.showSettingSection('#tab-deliveryMethod-settings');
        });

        $('.section-saasinfo').on('click', '.js-show-setting-templates', function() {
            dashboard.showSettingSection('#tab-template-settings');
        });

        $('.js-bind-domain-step').on('click', function() {
            admin.openModal('#bind-domain-modal');
        });

        $('#bind-domain-modal .js-send-domain-button').on('click', function() {
            dashboard.submitDomainName();
        });

        $('#bind-domain-modal .js-close-domain-modal').on('click', function() {
            $('#bind-domain-modal .closeModal').click();
        });

        $('#bind-domain-modal .closeModal').on('click', function() {
            $('#bind-domain-modal .product-text-inputs').show();
            $('#bind-domain-modal .js-send-domain-button').show();
            $('#bind-domain-modal .js-close-domain-modal').hide();
            $('#bind-domain-modal .js-response-message').hide();
        });

        $('.js-end-learning').on('click', function() {
            dashboard.handleCompleteLearning();
        });

        $('.js-start-learning').on('click', function() {
            dashboard.handleStartLearning();
        });

        $('.js-go-to-orders').on('click', function() {
            $('.mg-admin-mainmenu-item[data-section="orders"]').trigger('click');
        });
    },

    /**
     * Открывает страницу "Настройки" на необходимом разделе
     * @param {string} section - id раздела, который необходимо открыть на странице "Настройки". Начинаться должен с символа решетки
     */
    showSettingSection: function(section) {
        cookie('setting-active-tab', section);
        includeJS(admin.SITE + '/mg-core/script/admin/settings.js');
        callback = settings.init;
        admin.show("settings.php", "adminpage", cookie("settings_getparam"), callback);
    },

    updateStatusDashboard: function() {
        let noviceList = [];
        $('.section-saasinfo .js-progress-steps-item.done .js-check-status-step').each(function(index, element) {
            noviceList.push($(this).data('step'));
        });

        admin.ajaxRequest({
                mguniqueurl: "action/updateStatusDashboard",
                noviceList: noviceList,
            },
            function(response) {
                admin.indication(response.status, response.msg);
                if ($('.js-progress-steps-item.done').length === $('.js-progress-steps-item').length) {
                    $('.button.js-end-learning').removeClass('disabled');
                } else {
                    $('.button.js-end-learning').addClass('disabled');
                }
                dashboard.updateStateProgressbar();
            }
        );
    },

    /**
     * Изменяет статус всех этапов в соответствии с изменением конкретного чекбокса
     * @param {HTMLElement} selectedCheckbox - обернутый в объект Jquerry ($) чекбокс, статус которого изменяется
     */
    updateStatusAllSteps(selectedCheckbox, mode) {
        $('.js-progress-steps-item').removeClass('last');
        if (mode === 'startLearning') {
            // selectedCheckbox.html('<i class="fa fa-check-square-o" aria-hidden="true"></i>');
        }
        const thisCompletedStep = selectedCheckbox.closest('.js-progress-steps-item.done').length;
        let thisElemIsDisable = false; // true, когда статус итерируемого объекта надо изменить на "Недоступен"
        let thisElemIsWait = false; // true, когда статус итерируемого объекта надо изменить на "Ожидает выполнения"
        $('.js-progress-steps-item').each(function() {
            const thisSelectedCheckbox = selectedCheckbox.closest('.js-progress-steps-item')[0] === this;
            if (thisSelectedCheckbox && thisCompletedStep) {
                // selectedCheckbox.html('<i class="fa fa-square-o" aria-hidden="true"></i>');
                $(this).removeClass('done');
                $(this).addClass('wait');
                $(this).find('.js-checkbox-description').text('Ожидает выполнения');
                $(this).closest('.js-progress-steps-item').prev().addClass('last');
                thisElemIsDisable = true;
                return;
            } else if (thisSelectedCheckbox && !thisCompletedStep) {
                // selectedCheckbox.html('<i class="fa fa-check-square-o" aria-hidden="true"></i>');
                $(this).removeClass('wait');
                $(this).addClass('done');
                $(this).find('.js-checkbox-description').text('Выполнено');
                $(this).addClass('last');
                thisElemIsWait = true;
                return;
            }
            if (thisElemIsWait) {
                $(this).removeClass('disable');
                $(this).removeClass('done');
                $(this).addClass('wait');
                $(this).find('.js-checkbox-description').text('Ожидает выполнения');
                thisElemIsDisable = true;
                thisElemIsWait = false;
                $(this).find('.js-progress-steps-status').attr('tooltip', '');
                return;
            }
            if (thisElemIsDisable) {
                // $(this).find('.js-check-status-step').html('<i class="fa fa-square-o" aria-hidden="true"></i>');
                $(this).removeClass('done');
                $(this).removeClass('wait');
                $(this).addClass('disable');
                $(this).find('.js-checkbox-description').text('Недоступно');
                $(this).find('.js-progress-steps-status').attr('tooltip', 'Для разблокировки данного этапа выполните предыдущий');
            }
        });

    },

    updateStateProgressbar: function() {
        let stepsCount = $('.js-check-status-step').length;
        let stepsCompleted = 0;
        $('.js-progress-steps-item').each(function() {
            if ($(this).hasClass('done')) {
                stepsCompleted += 1;
            }
        });

        const newValue = Math.round((100 / stepsCount) * stepsCompleted);
        $('.js-progress-settings-completed').css('width', `${newValue}%`);
        $('.js-progress-settings-value').html(`<span>${newValue}</span> %`);
        if (newValue >= 45 && newValue !== 50) {
            $('.js-progress-settings').addClass('white');
            $('.js-progress-settings').removeClass('white-blue');
        } else if (newValue === 50) {
            $('.js-progress-settings').add('white');
            $('.js-progress-settings').addClass('white-blue');
        } else {
            $('.js-progress-settings').removeClass('white');
            $('.js-progress-settings').removeClass('white-blue');
        }
    },

    callBack: function() {
        dashboard.init();
        dashboard.widgetOrderStatistic();
    },

    /*
     * Отрисовывает график для виджета статистики заказов
     */
    widgetOrderStatistic: function() {

        /**
         * Модуль для  раздела "Статистика".
         */
        $(".ui-autocomplete").css('z-index', '1000');
        $.datepicker.regional['ru'] = {
            closeText: lang.CLOSE,
            prevText: lang.PREV,
            nextText: lang.NEXT,
            currentText: lang.TODAY,
            monthNames: [lang.MONTH_1, lang.MONTH_2, lang.MONTH_3, lang.MONTH_4, lang.MONTH_5, lang.MONTH_6, lang.MONTH_7, lang.MONTH_8, lang.MONTH_9, lang.MONTH_10, lang.MONTH_11, lang.MONTH_12],
            monthNamesShort: [lang.MONTH_SHORT_1, lang.MONTH_SHORT_2, lang.MONTH_SHORT_3, lang.MONTH_SHORT_4, lang.MONTH_SHORT_5, lang.MONTH_SHORT_6, lang.MONTH_SHORT_7, lang.MONTH_SHORT_8, lang.MONTH_SHORT_9, lang.MONTH_SHORT_10, lang.MONTH_SHORT_11, lang.MONTH_SHORT_12],
            dayNames: [lang.DAY_1, lang.DAY_2, lang.DAY_3, lang.DAY_4, lang.DAY_5, lang.DAY_6, lang.DAY_7],
            dayNamesShort: [lang.DAY_SHORT_1, lang.DAY_SHORT_2, lang.DAY_SHORT_3, lang.DAY_SHORT_4, lang.DAY_SHORT_5, lang.DAY_SHORT_6, lang.DAY_SHORT_7],
            dayNamesMin: [lang.DAY_MIN_1, lang.DAY_MIN_2, lang.DAY_MIN_3, lang.DAY_MIN_4, lang.DAY_MIN_5, lang.DAY_MIN_6, lang.DAY_MIN_7],
            dateFormat: 'dd.mm.yy',
            firstDay: 1,
            isRTL: false
        };
        $.datepicker.setDefaults($.datepicker.regional['ru']);

        $('.widget-order-statistic').on('click', '.changeData', function() {
            var request = $(".paramToChart").formSerialize();
            admin.show("saasinfo.php", cookie("type"), request, dashboard.callBack);
        });


        $('.widget-order-statistic').on('click', '.open-more-statistic', function() {
            $('.target-' + $(this).data('target')).show();
            $(this).hide();
        });

        // подпись во всплывающем окне при наведении
        if ($('.widget-order-statistic [name=typeView]').val() == 'day') {
            label = lang.EARNED_SUM_DAY;
        } else {
            label = lang.EARNED_SUM_MONTH;
        }

        var ctx = document.getElementById("statisticChart").getContext('2d');

        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: admin.tryJsonParse($('#info-for-chart-days').val()),
                datasets: [{
                    label: label,
                    data: admin.tryJsonParse($('#info-for-chart').val()),
                    borderColor: [
                        $('.header-top').css('background-color')
                    ],
                    backgroundColor: [
                        // '#fff',
                        'rgba(0,0,0,0)'
                    ],
                    borderWidth: 2,
                    pointBorderWidth: 3,
                    pointRadius: 2
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                legend: {
                    display: false
                }
            }
        });

        $('.widget-order-statistic .to-date').datepicker({ dateFormat: "dd.mm.yy" });
        $('.widget-order-statistic .from-date').datepicker({ dateFormat: "dd.mm.yy" });
    },
    /**
     * Отправляет доменное имя на сервер
     */
    submitDomainName: function() {
        const domainName = $('#bind-domain-modal .js-bind-domain-input').val().toLowerCase();
        if (domainName.length === 0) return;
        if (!dashboard.validateDomainName(domainName)) {
            $('#bind-domain-modal .js-error-input-message').slideDown();
            $('#bind-domain-modal .js-bind-domain-input').on('keydown', function() {
                $('#bind-domain-modal .js-error-input-message').slideUp();
            });
            return;
        }
        $('#bind-domain-modal .js-send-domain-button').css('pointer-events', 'none');

        $.ajax({
            type: "POST",
            url: mgBaseDir + "/ajaxrequest",
            data: {
                action: "createTicketVerification",
                saasHandler: "design-editor",
                domain: domainName
            },
            dataType: "json",
            success: function(response) {
                if (typeof response !== 'object') {
                    dashboard.showResponseMessage(false);
                    return;
                }
                setTimeout(() => {
                    $('#bind-domain-modal .js-send-domain-button').css('pointer-events', 'all');
                }, 500);
                if (response.data.responseFromMoguta != null) {
                    if (response.data.responseFromMoguta.error) {
                        // При успешном ответе с сервера и отрицательном результате:
                        dashboard.showResponseMessage(response.data.responseFromMoguta.msg, false);
                    } else {
                        // При успешном ответе с сервера и положительном результате:
                        dashboard.showResponseMessage(response.data.responseFromMoguta.msg, true);
                    }
                } else {
                    // Ошибка при ответе сервера:
                    dashboard.showResponseMessage(false);
                }
            },
            error: function() {
                dashboard.showResponseMessage(false);
                $('#bind-domain-modal .js-send-domain-button').css('pointer-events', 'all');
            },
        });
    },

    /**
     * Валидация доменного имени
     * @param {string} domain - проверяемое доменное имя
     * @returns {boolean} - прошло доменное имя валидацию или нет
     */
    validateDomainName: function(domain) {
        if (/[а-яё]/i.test(domain) && !/\s/g.test(domain)) {
            domain = punycode.ToASCII(domain);
        }
        return /\b((?=[a-z0-9-]{1,63}\.)(xn--)?[a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,63}\b/gm.test(domain);
    },

    showResponseMessage: function(message, success) {
        const responseMessage = $('#bind-domain-modal .js-response-message');
        if (success === true) {
            responseMessage.removeClass('alert');
            responseMessage.addClass('success');
            responseMessage.html(message);
        } else if (success === false) {
            responseMessage.removeClass('success');
            responseMessage.addClass('alert');
            responseMessage.html('Ошибка: ' + message);
        } else {
            responseMessage.removeClass('success');
            responseMessage.addClass('alert');
            responseMessage.html(`
                <p> Фатальная ошибка! Не удалось получить ответ с сервера moguta.cloud </p>
                <p> Повторите попытку позже. При повторном появлении ошибки обратитесь в нашу техническую поддержку </p>
            `);
        }
        $('#bind-domain-modal .product-text-inputs').hide();
        $('#bind-domain-modal .js-send-domain-button').hide();
        $('#bind-domain-modal .js-close-domain-modal').show();
        responseMessage.show();
    },



    handleCompleteLearning: function() {
        if (!confirm('Завершить базовую настройку?')) return;
        $('.js-progress-steps').hide();
        $('.js-infoblock').addClass('without-learning');
        $('.inform-progress').slideUp();
        $('.js-completed-learning-block').slideDown();

        admin.ajaxRequest({
                mguniqueurl: "action/progressBarNoviceDisable",
            },
            function(response) {
                admin.indication(response.status, response.msg);
            });

    },

    handleStartLearning: function() {
        if (!confirm('Приступить к базовой настройке?')) return;

        admin.ajaxRequest({
                mguniqueurl: "action/progressBarNoviceEnable",
            },
            function(response) {
                dashboard.resetProgressValues();
                $('.js-progress-steps').slideDown();
                $('.js-infoblock').removeClass('without-learning');
                $('.inform-progress').slideDown();
                $('.inform-progress').css('display', 'flex');
                $('.js-completed-learning-block').slideUp();
                admin.indication(response.status, response.msg);
                $("body,html").animate({
                    scrollTop: 0
                }, 800);
            });
    },

    resetProgressValues: function() {
        dashboard.updateStatusAllSteps($('.js-check-status-step').eq(0), 'startLearning');
        $('.js-progress-settings-completed').css('width', '0');
        $('.js-progress-settings').removeClass('white');
        $('.js-progress-settings-value').html('<span>0</span> %');
    },
};