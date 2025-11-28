/**
 * Модуль для  раздела "Заказы".
 */
 var order = (function() {
    return {
        comment: null,
        firstCall: true,
        deliveryCost: 0,
        searchUnit: 'шт.',
        orderItems: [],
        currencyShort: [],
        useMultiplicity: '', // Включена ли кратность
        initialStatus: -1,
        allowSave: true,
        sizeMapObject: undefined,
        showPD: undefined,
        showPR: undefined,
        checkBlockInterval: null,
        lastAddProdTab: 'descr',
        wholesalesEmail: '',
        wholesalesCount: '',
        wholesalesGroup: '',
        storage_count_array: [],
        storageItemNimbe: '',
        storageSetting: false,
        lastOrderId: 0, // id последнего заказа (для автобновления заказов)
        newOrdersIntervalId: null, // id интервала для автообновления заказов
        init: function() {
            order.sizeMapObject = undefined;
            order.showPD = undefined;
            order.showPR = undefined;
            order.initEvents();

            admin.ajaxRequest({
                    mguniqueurl: 'action/getCurrencyShort',
                },
                function(response) {
                    order.currencyShort = response.data['currencyShort'];
                    order.useMultiplicity = response.data['useMultiplicity'];
                });
            admin.ajaxRequest({
              mguniqueurl: 'action/getStorages',
            },
            function (response) {
                if(response.status == "error"){
                  order.storageSetting = false;
                }else{
                  order.storageSetting = JSON.parse(response.data);
                }

            });
            // для блокировки при редактировании
            order.checkBlockInterval = setInterval(order.checkBlockIntervalFunction, 1000);

            admin.sliderPrice();

            $('.section-order .to-date').datepicker({ dateFormat: 'dd.mm.yy' });
            $('.section-order .from-date').datepicker({ dateFormat: 'dd.mm.yy' });

            // для доп. юр. лиц
            var select = $('.section-order #additional-requisites-modal .select-requisites:first');
            var previousValue = select.val();
            select.bind('change', function() {
                var currentValue = select.val();

                var data = admin.createObject('.addRequisitesInput');
                if (previousValue !== null) {
                    select.find('option[value=' + previousValue + ']').data('data', data).text(data.nameadmin);
                }

                $('.section-order #additional-requisites-modal input').val('');
                data = admin.tryJsonParse(select.find('option[value=' + currentValue + ']').data('data'));
                if (typeof data === 'object' && data !== null) {
                    Object.keys(data).forEach(function(key) {
                        $('.section-order #additional-requisites-modal input[name=' + key + ']').val(data[key]);
                    });
                }

                previousValue = currentValue;
            });
            $('.section-order').find('#add-order-wrapper .delivery-date input[name=date_delivery]').datepicker({
                dateFormat: 'dd.mm.yy',
                minDate: 0,
            });

			newbieWay.checkIntroFlags('orderScenario', false);
            order.getLastOrderId();
        },
        getLastOrderId: function () {
            if (cookie('section') != 'orders') {
                return;
            }
            $.ajax({
                url: mgBaseDir + '/ajax?mguniqueurl=action/getLastOrderId',
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.data.lastOrderId) {
                        if (!order.lastOrderId) {
                            order.lastOrderId = response.data.lastOrderId;
                            order.newOrdersIntervalId = setInterval(order.getLastOrderId, 30 * 1000);
                            return;
                        }
                        if (response.data.lastOrderId > order.lastOrderId) {
                            order.lastOrderId = response.data.lastOrderId;
                            var getparam = cookie('orders_getparam');
                            if (!getparam) {
                                getparam = $('form[name=filter]').formSerialize();
                            }
                            if (getparam) {
                                getparam = '&'+getparam;
                            }
                            var data = "mguniqueurl=orders.php&mguniquetype=adminpage"+getparam;

                            admin.ajaxRequest(
                                data,
                                function (response2) {
                                    var orderPage = $(response2);
                                    var orderTable = orderPage.find('table.main-table');
                                    if (orderTable.find('tr[order_id="'+response.data.lastOrderId+'"]').length) {
                                        orderTable.find('tr[order_id="'+response.data.lastOrderId+'"]').addClass('newOrder');
                                        orderTable.find('tr[order_id="'+response.data.lastOrderId+'"] .add_date .add_date-span').hide();
                                        orderTable.find('tr[order_id="'+response.data.lastOrderId+'"] .add_date').append('<span class="badge paid newOrder">' + lang.NEW_ORDER + '</span>');
                                        $('.section-order table.main-table').parents('.table-wrapper').html(orderTable);
                                    } else {

                                        $('.section-order .new-order-notice:hidden').slideToggle();
                                    }
                                },
                                false,
                                'html'
                            );
                        }
                    }
                }
            });
        },
        /**
         * Инициализирует обработчики для кнопок и элементов раздела.
         */
        initEvents: function() {
            var _sectionWrapNode = $('.section-order');
            // ================================
            //            для PDF
            // ================================
            // для наведения на кнопку
            $('.section-order').on({
                mouseenter: function() {
                    order.showPD = true;
                    $('.pdf-docs-list li a').data('id', $(this).parents('tr').attr('order_id'));
                    $('.pdf-docs-list').show().css('opacity', 1).css('visibility', 'visible');
                    offset = $(this).offset();
                    if ($(window).height() - offset.top - $(this).outerHeight() - $('.pdf-docs-list').outerHeight() <= 0) {
                        $('.pdf-docs-list .show_uri').show();
                        $('.pdf-docs-list').offset({
                            top: offset.top - $('.pdf-docs-list').outerHeight() + 5,
                            left: offset.left - $('.pdf-docs-list').outerWidth() + ($(this).outerWidth() * 1.3),
                        });
                    } else {
                        $('.pdf-docs-list .show_uri').show();
                        $('.pdf-docs-list').offset({
                            top: offset.top + $(this).outerHeight(),
                            left: offset.left - $('.pdf-docs-list').outerWidth() + ($(this).outerWidth() * 1.3),
                        });
                    }
                    order.showPR = false;
                    $('.print-docs-list').hide().css('opacity', 0).css('visibility', 'hidden');
                },
                mouseleave: function() {
                    setTimeout(function() {
                        if (!order.showPD) {
                            $('.pdf-docs-list').hide().css('opacity', 0).css('visibility', 'hidden');
                        }
                    }, 1000);
                    order.showPD = false;
                },
            }, '.order-to-pdf');
            // для наведения на меню
            $('.section-order').on({
                mouseenter: function() {
                    if ($(this).hasClass('print-docs-list')) order.showPR = true;
                    if ($(this).hasClass('pdf-docs-list')) order.showPD = true;
                    $(this).show().css('opacity', 1).css('visibility', 'visible');
                },
                mouseleave: function() {
                    setTimeout(function() {
                        if (!order.showPR) {
                            $('.print-docs-list').hide().css('opacity', 0).css('visibility', 'hidden');
                        }
                        if (!order.showPD) {
                            $('.pdf-docs-list').hide().css('opacity', 0).css('visibility', 'hidden');
                        }
                    }, 1000);
                    order.showPR = false;
                    order.showPD = false;
                },
            }, '.pdf-docs-list, .print-docs-list');
            // ================================
            //            для печати
            // ================================
            // для наведения на кнопку
            $('.section-order').on({
                mouseenter: function() {
                    order.showPR = true;
                    $('.print-docs-list li a').data('id', $(this).parents('tr').attr('order_id'));
                    $('.print-docs-list').show().css('opacity', 1).css('visibility', 'visible');
                    offset = $(this).offset();
                    if ($(window).height() - offset.top - $(this).outerHeight() - $('.pdf-docs-list').outerHeight() <= 0) {
                        $('.print-docs-list .show_uri').show();
                        $('.print-docs-list').offset({
                            top: offset.top - $('.print-docs-list').outerHeight() + 5,
                            left: offset.left - $('.print-docs-list').outerWidth() + ($(this).outerWidth() * 1.3),
                        });
                    } else {
                        $('.print-docs-list .show_uri').show();
                        $('.print-docs-list').offset({
                            top: offset.top + $(this).outerHeight(),
                            left: offset.left - $('.print-docs-list').outerWidth() + ($(this).outerWidth() * 1.3),
                        });
                    }
                    order.showPD = false;
                    $('.pdf-docs-list').hide().css('opacity', 0).css('visibility', 'hidden');
                },
                mouseleave: function() {
                    setTimeout(function() {
                        if (!order.showPR) {
                            $('.pdf-docs-list').hide().css('opacity', 0).css('visibility', 'hidden');
                            $('.print-docs-list').hide().css('opacity', 0).css('visibility', 'hidden');
                        }
                    }, 1000);
                    order.showPR = false;
                },
            }, '.order-to-print');
            // ================================
            //     Дополнительные юр. лица
            // ================================
            $('.section-order').on('click', '.openAddRequisitesModal', function() {
                admin.ajaxRequest({
                        mguniqueurl: 'action/loadAddRequisites',
                    },

                    function(response) {
                        var select = $('.section-order #additional-requisites-modal .select-requisites:first');
                        select.html('');
                        if (response.data) {
                            Object.keys(response.data).forEach(function(key) {
                                var tmp = admin.tryJsonParse(response.data[key]);
                                select.append('<option value="' + key + '">' + tmp.nameadmin + '</option>');
                                select.find('option:last').data('data', response.data[key]);
                            });
                        }
                        select.find('option:first').prop('selected', true);
                        select.trigger('change');
                        admin.openModal('#additional-requisites-modal');
                    });
            });

            $('.section-order').on('click', '#additional-requisites-modal .add-requisites', function() {
                var select = $('.section-order #additional-requisites-modal .select-requisites:first');
                var num = select.find('option').length;

                select.find('option:selected').prop('selected', false);
                select.append('<option value="' + num + '">' + lang.NEW_REQUISITES + '</option>');
                select.find('option:last').data('data', '{"nameadmin":"' + lang.NEW_REQUISITES + '"}').prop('selected', true);
                select.trigger('change');
            });

            $('.section-order').on('click', '#additional-requisites-modal .delete-requisites', function() {
                var select = $('.section-order #additional-requisites-modal .select-requisites:first');

                select.find('option:selected').remove();
                select.find('option:first').prop('selected', true);
                select.trigger('change');
            });

            $('.section-order').on('click', '#additional-requisites-modal .save-button', function() {
                $('#additional-requisites-modal input[type=text]').each(function() {
                    $(this).val(admin.htmlspecialchars($(this).val()));
                });
                $('.section-order #additional-requisites-modal .select-requisites:first').trigger('change');
                var data = {};
                var html = '<option value="unset" selected>' + lang.REQUISITES_DEFAULT + '</option>';
                $('.section-order #additional-requisites-modal .select-requisites option').each(function() {
                    var num = $(this).attr('value');
                    var tmp = $(this).data('data');
                    data['r' + num] = tmp;
                    html += '<option value="r' + num + '">' + tmp.nameadmin + '</option>';
                });

                admin.ajaxRequest({
                        mguniqueurl: 'action/saveAddRequisites',
                        data: data,
                    },

                    function(response) {
                        $('.section-order [name=tempRequisites]').html(html);
                        admin.indication(response.status, lang.SUCCESS_SAVE);
                        admin.closeModal('#additional-requisites-modal');
                    });
            });

            // ================================
            //              MISC
            // ================================

            $('.section-order').on('click', '.changeSortDateTipe', function() {
                if ($('[name=dateSortType]').val() == 'create') {
                    $('[name=dateSortType]').val('pay');
                    $(this).text(lang.PAY);
                } else {
                    $('[name=dateSortType]').val('create');
                    $(this).text(lang.CREATE);
                }
            });

            $('.section-order').on('click', '#add-order-wrapper .getWholeSales', function() {
                var id = $('#order-data .id-sp').text();
                var variant = $('.block-variants tr td input:checked').val();
                variant = variant ? variant : 0;
                var email = $('.section-order #add-order-wrapper .wholesales input:first').val();
                admin.ajaxRequest({
                        mguniqueurl: 'action/adminOrderGetWholesales',
                        data: {
                            id: id,
                            variant: variant,
                            email: email,
                            unit: order.searchUnit,
                        },
                    },
                    function(response) {
                        $('.section-order #add-order-wrapper .wholesalesContainer').html(response.data);
                        order.wholesalesEmail = email;
                    });
            });

            $('.section-order').on('click', '#add-order-wrapper .deleteOrderFile', function() {
                if (!confirm(lang.DELETE + '?')) {
                    return false;
                }
                var obj = $(this);
                admin.ajaxRequest({
                        mguniqueurl: 'action/dropOrderFile',
                        file: $(this).data('file'),
                    },
                    function(response) {
                        admin.indication(response.status, response.msg);
                        obj.closest('.row').hide();
                    });
            });

            // убирает подсказку для поиска товаров
            $('.section-order').on('change', '.add-order .search-field', function() {
                $('.example-line').hide();
            });

            $('.section-order').on('click', '.js-addProductToOrder', function() {
                $('.top-block').slideToggle('500');
            });

            $('.section-order').on('change', '#add-order-wrapper #orderStatus', function() {
                if (order.initialStatus > -1 && order.initialStatus != $('#orderStatus').val()) {
                    $('.section-order #add-order-wrapper .mailUser').show().find('input').trigger('change');
                } else {
                    $('.section-order #add-order-wrapper .mailUser').hide();
                    $('.section-order #add-order-wrapper .js-mailUserText-wrap').addClass('mailUser__popup--hidden');
                }
            });
            //Закрываем модалки при клике вне их
            $('.section-order').mousedown(function(e) {
                var mailUserWrap = $('.js-mailUserText-wrap'),
                    hiddenClass = 'mailUser__popup--hidden';
                if (!mailUserWrap.is(e.target) &&
                    mailUserWrap.has(e.target).length === 0) {
                    mailUserWrap.addClass(hiddenClass);
                }
            });

            $('.section-order').on('change', '#add-order-wrapper #mailUser', function() {
                if (this.checked) {
                    $('.section-order #add-order-wrapper .js-mailUserText-wrap').removeClass('mailUser__popup--hidden');
                    $('.js-mailUserText').focus();
                } else {
                    $('.section-order #add-order-wrapper .js-mailUserText-wrap').addClass('mailUser__popup--hidden');
                }
            });

            
            $('.section-order').on('click', '.js-show-utm-filter', function() {
                $('.section-order .utm-order-wrapper').show();
                $(this).hide();
            });

            $('.section-order').on('click', '.js-close-status-message', function() {
                $('.section-order #add-order-wrapper .js-mailUserText-wrap').addClass('mailUser__popup--hidden');
            });
            //раскрытие комментария
            $('.section-order').on('click', '.order-tbody .showMoar', function() {
                $(this).parents('td').html(admin.replaceBBcodes($(this).attr('content')));
            });

            // Выделить все заказы
            $('.section-order').on('click', '.check-all-order', function() {
                $('.order-tbody input[name=order-check]').prop('checked', 'checked');
                $('.order-tbody input[name=order-check]').val('true');
                $('.order-tbody tr').addClass('selected');

                $(this).addClass('uncheck-all-order');
                $(this).removeClass('check-all-order');
            });
            // Снять выделение со всех заказы.
            $('.section-order').on('click', '.uncheck-all-order', function() {
                $('.order-tbody input[name=order-check]').prop('checked', false);
                $('.order-tbody input[name=order-check]').val('false');
                $('.order-tbody tr').removeClass('selected');

                $(this).addClass('check-all-order');
                $(this).removeClass('uncheck-all-order');
            });

            // Вызов модального окна при нажатии на кнопку добавления заказа.
            $('.section-order').on('click', '.add-new-button', function() {
                order.openModalWindow('add');
            });

            // Вызов модального окна при нажатии на кнопку изменения товаров.
            $('.section-order').on('click', '.see-order', function() {
                order.openModalWindow('edit', $(this).attr('id'), $(this).attr('data-number'));
            });

            // Клонирование заказа
            $('.section-order').on('click', '.clone-row', function() {
                order.cloneOrder($(this).attr('id'));
            });

            // Удаление товара.
            $('.section-order').on('click', '.delete-order', function() {
                order.deleteOrder($(this).attr('id'));
            });

            // Показывает панель с фильтрами.
            $('.section-order').on('click', '.show-filters', function() {
                $('.filter-container').slideToggle(function() {
                    $('.property-order-container').slideUp();
                    $('.widget-table-action').toggleClass('no-radius');
                });
            });

            // Показывает панель с настройками заказа
            $('.section-order').on('click', '.show-property-order', function() {
                $('.property-order-container').slideToggle(function() {
                    $('.filter-container').slideUp();
                    $('.widget-table-action').toggleClass('no-radius');
                });
            });

            // Сброс фильтров.
            $('.section-order').on('click', '.refreshFilter', function() {
                admin.clearGetParam();
                admin.show('orders.php', 'adminpage', 'refreshFilter=1', order.init);
                return false;
            });

            // Применение выбранных фильтров
            $('.section-order').on('click', '.filter-now', function() {
                order.getProductByFilter();
                return false;
            });

            $('.section-order').on('click', '#add-order-wrapper .product-block .variants-table [type=radio]', function() {
                var img = $(this).parents('tr').find('img');
                if (img != undefined) {
                    var src = img.attr('src');
                    if (src != undefined && src.length) {
                        $('#add-order-wrapper .product-block .image-sp img').attr('src', src);
                    }
                }
            });

            // Открывает панель настроек заказа
            $('.section-order').on('click', '.property-order-container .save-property-order', function() {
                order.savePropertyOrder();
                return false;
            });

            // Выбор картинки
            $('.section-order').on('click', '.property-order-container .upload-sign', function() {
                admin.openUploader('order.getSignFile');

            });

            // Выбор картинки
            $('.section-order').on('click', '.property-order-container .upload-stamp', function() {
                admin.openUploader('order.getStampFile');

            });

            // Сохранение  при нажатии на кнопку сохранить в модальном окне.
            $('.section-order').on('click', '#add-order-wrapper .save-button', function() {
                //Если не активно, то не разрешать
                if (!order.allowSave) {
                    return false;
                }
                order.saveOrder($(this).attr('id'), $(this).parents('.orders-table-wrapper'), $(this).attr('data-number'), !admin.keySave);
            });

            // Распечатка заказа
            _sectionWrapNode.on('click', '#add-order-wrapper .print-button, .print-docs-list a', function() {
                var layout = '';

                if ($(this).data('template')) {
                    layout = $(this).data('template');
                }

                if ($(this).data('type') === 'fromModal') {
                    order.fixPrint();
                }

                var usestamp = '';
                if(typeof($(this).data('usestamp'))!== 'undefined' && $(this).data('usestamp') == true){
                    usestamp = 'true';
                }else{
                    usestamp = 'false';
                }

                order.printOrder($(this).data('id'), layout, usestamp);
            });

            // Повторная отправка письма
            $('.section-order').on('click', '#add-order-wrapper .resend-email', function() {
                $(this).prop('disabled', true);
                order.resendEmail($(this).data('id'));
            });

            // Сохранить в PDF
            $('.section-order').on('click', '#add-order-wrapper .get-pdf-button, .pdf-docs-list a', function() {
                var layout = '';

                if ($(this).data('template')) {
                    layout = '&layout=' + $(this).data('template');
                }

                var usestamp = '';
                if(typeof($(this).data('usestamp'))!== 'undefined' && $(this).data('usestamp') == true){
                    usestamp = '&usestamp=true';
                }else{
                    usestamp = '&usestamp=false';
                }
                window.location.href = mgBaseDir + '/mg-admin/?getOrderPdf=' + $(this).data('id') + layout + usestamp;
            });


            // Разблокировать поля для редактирования заказа.
            $('.section-order').on('click', '#add-order-wrapper .editor-order', function() {
                order.enableEditor();

                var id = $('#add-order-wrapper .save-button').attr('id');
                var deliveryId = $('#delivery :selected').attr('name');
                var plugin = $('#delivery :selected').data('plugin');

                if (plugin && plugin.length > 0) {
                    $.ajax({
                        type: 'POST',
                        url: mgBaseDir + '/ajaxrequest',
                        data: {
                            pluginHandler: plugin, // имя папки в которой лежит данный плагин
                            actionerClass: 'Pactioner', // класс Pactioner в Pactioner.php - в папке плагина
                            action: 'getAdminDeliveryForm', // название действия в пользовательском  классе
                            deliveryId: deliveryId,
                            firstCall: order.firstCall,
                            orderItems: order.orderItems,
                            orderId: id,
                        },
                        cache: false,
                        dataType: 'json',
                        success: function(response) {
                            order.firstCall = false;
                            $('#delivery').parents('.js-insert-delivery-options').append('<div class="add-delivery-info row">' + response.data.form + '</div>');
                            $('input#deliveryCost').prop('disabled', true);
                        },
                    });
                }
            });


            $('.section-order').on('mousedown', '#add-order-wrapper .delivery-date input[name=date_delivery]', function() {
                $(this).datepicker({ dateFormat: 'dd.mm.yy', minDate: 0 });
            });

            // Удаляет выбранный продукт из поля для добавления в заказ.
            $('.section-order').on('click', '#add-order-wrapper .clear-product', function() {
                $('[name=searchcat]').val('');
                $('.product-block').html('');
                $('.example-line').show();
            });

            // Подстановка значения стоимости при выборе способа доставки в добавлении заказа.
            $('.section-order').on('change', '#delivery', function() {
                $('#delivery').parent().find('.errorField').css('display', 'none');
                $('#delivery').removeClass('error-input');

                if (!$('#delivery :selected').data('plugin')) {
                    var deliveryCost = $('#delivery option:selected').val();
                    var deliveryId = $('#delivery option:selected').attr('name');
                    order.getDeliveryOrderOptions(deliveryId, true);
                    $('#add-order-wrapper #deliveryCost').val(deliveryCost).trigger('change');
                } else {
                    order.calculateOrder();
                }
                // Очищение от старых интервалов доставки
                $('input.itervalInitialVal').prop('disabled', false);
                $('input.itervalInitialVal').attr('value', '');
                $('#order-del-interv').val('');
            });

            // Изменнение стоимости доставки
            $('.section-order').on('change', '#deliveryCost', function() {
                if ($(this).val() < 0 || !$.isNumeric($(this).val())) {
                    $(this).val('0');
                }
                order.calculateOrder();
            });

            $('.section-order').on('change', '#add-order-wrapper #orderPluginsData :input, #add-order-wrapper input[name=user_email], #add-order-wrapper #usePlugins', function() {
                order.calculateOrder();
            });

            // Смена плательщика.
            $('.section-order').on('change', '#customer', function() {
                var yurWrap = $('.js-yur-list-toggle');
                $(this).val() === 'fiz' ? yurWrap.hide() : yurWrap.show();
            });

            // Действия при выборе способа оплаты.
            $('.section-order').on('change', 'select#payment', function() {
                $('.main-settings-list select#payment').parent().find('.errorField').css('display', 'none');
                $('.main-settings-list select#payment').removeClass('error-input');
                order.calculateOrder();
            });

            // Устанавливает количиство выводимых записей в этом разделе.
            $('.section-order').on('change', '.countPrintRowsOrder', function() {
                var count = $(this).val();
                admin.ajaxRequest({
                        mguniqueurl: 'action/setCountPrintRowsOrder',
                        count: count,
                    },
                    function(response) {
                        admin.refreshPanel();
                    },
                );
            });

            // Поиск товара при создании нового заказа.
            // Обработка ввода поисковой фразы в поле поиска.
            $('.section-order').on('keyup', '#order-data input[name=searchcat]', function() {
                admin.searchProduct($(this).val(), '#order-data .fastResult', $('#add-order-wrapper [name=searchCats]').val(), 'yep', $('#add-order-wrapper select[name="userCurrency"]').val());
            });

            $('.section-order').on('change', '#add-order-wrapper [name=searchCats]', function() {
                admin.searchProduct($('#order-data input[name=searchcat]').val(), '#order-data .fastResult', $('#add-order-wrapper [name=searchCats]').val(), 'yep', $('#add-order-wrapper select[name="userCurrency"]').val());
            });

            // Подстановка товара из примера в строку поиска.
            $('.section-order').on('click', '#order-data .example-find', function() {
                $('#order-data input[name=searchcat]').val($(this).text());
                admin.searchProduct($(this).text(), '#order-data .fastResult', -1, 'yep', $('#add-order-wrapper select[name="userCurrency"]').val());
            });

            // Клик вне поиска.
            $('.section-order').mousedown(function(e) {
                var container = $('.fastResult');
                if (container.has(e.target).length === 0 &&
                    $('.search-block').has(e.target).length === 0) {
                    container.hide();
                }
            });

            // Пересчет цены товара аяксом в форме добавления заказа.
            $('.section-order').on('change', '.orders-table-wrapper .property-form input, .orders-table-wrapper .property-form select', function() {
                if ($(this).parents('p').find('input[type=radio]').length) {
                    $(this).parents('p').find('input[type=radio]').prop('checked', false);
                    $(this).prop('checked', true);
                }
                order.refreshPriceProduct();
                return false;
            });

            $('.section-order').on('click', '#add-order-wrapper .applyWholesale', function() {
                order.wholesalesCount = $(this).data('count');
                order.wholesalesGroup = $(this).closest('ul').data('wholesalegroup');
                order.refreshPriceProduct();
            });

            $('.section-order').on('click', '#add-order-wrapper .resetWholesales', function() {
                order.wholesalesCount = '';
                order.wholesalesGroup = '';
                order.refreshPriceProduct();
            });

            $('.section-order').on('change', '#add-order-wrapper .currSpan [name=userCurrency]', function() {
                $.ajax({
                    type: 'POST',
                    url: mgBaseDir + '/ajax',
                    data: {
                        mguniqueurl: 'action/setAdminCurrency',
                        userCustomCurrency: $('#add-order-wrapper .currSpan [name=userCurrency]').val(),
                    },
                    cache: false,
                    // async: false,
                    dataType: 'json',
                    success: function(response) {
                        $('#add-order-wrapper .changeCurrency').text(response.data.curr);

                        order.orderItems.forEach(function(item, i) {
                            order.orderItems[i].fulPrice = (item.fulPrice * response.data.multiplier).toFixed(2);
                        });

                        $('#add-order-wrapper .price-val').each(function(index, element) {
                            var current = $(this).val();
                            current = (current * response.data.multiplier).toFixed(2);
                            $(this).val(current);
                        });
                        var current = $('#add-order-wrapper #deliveryCost').val();
                        current = (current * response.data.multiplier).toFixed(2);
                        $('#add-order-wrapper #deliveryCost').val(current);
                        $('#add-order-wrapper .price-val:first').keyup();
                        $('#add-order-wrapper .clear-product').click();
                        $('#add-order-wrapper #orderContent .property .prop-val').each(function(index, element) {
                            var current = $(this).text();
                            current = current.replace(',', '.');
                            current = current.replace(/[^0-9.]/g, '');
                            while (current[current.length - 1] == '.') {
                                current = current.slice(0, -1);
                            }
                            current = (current * response.data.multiplier).toFixed(2);
                            $(this).text(' + ' + current + ' ' + response.data.curr);
                        });
                        order.calculateOrder();
                    },
                });
            });

            // Клик по найденным товарам поиске в форме добавления заказа
            $('.section-order').on('click', '.fast-result-list a', function() {
                order.viewProduct($(this).data('element-index'));
            });

            // поиск по логину
            $('.section-order').on('click', '.click-to-be-changed', function() {
                $('.to-be-changed').toggle();
            });

            // Вставка продукта из списка поиска в строку заказа.
            $('.section-order').on('click', '.orders-table-wrapper .property-form .addToCart', function() {
                  var prodId = $('.product-info .id-sp').html();
                  var prodCode = $('.product-info .code-sp').html();
                  admin.ajaxRequest({
                    mguniqueurl: 'action/getProducStorage',
                    prodId: prodId,
                    prodCode: prodCode,
                  },
                  function (response) {
                        if(response['status'] == "success"){
                          order.addToOrder($(this), response['data']);
                          return false;
                        }else{
                          order.addToOrder($(this));
                          return false;
                        }
                  });
            });

            // Удаление позиции из заказа.
            $('.section-order').on('click', '.order-history a[rel=delItem]', function() {
                var itemLine = $(this).parents('tr');
                var itemId = itemLine.attr('data-id');
                var variantId = itemLine.attr('data-variant');
                var prop = itemLine.attr('data-prop');
                order.orderItems.forEach(function(item, i) {
                    var property = item.property.split(' ').join('');
                    if (variantId == '0') {
                        if (item.id == itemId && prop == property) {
                            order.orderItems.splice(i, 1);
                        }
                    } else {
                        if (item.variant_id == variantId && prop == property) {
                            order.orderItems.splice(i, 1);
                        }
                    }
                });
                order.storageItemNimbe = '';
                itemLine.remove();
                if ($('#orderContent tr:visible').length < 1) {
                    $('#orderContent .emptyOrder').show();
                }
                order.calculateOrder();
                order.renumericPosition();
            });

            // Обработка выбора  способа доставки при добавлении нового заказа.
            $('.section-order').on('change', 'select#delivery', function() {
                $('select #delivery option[name=null]').remove();
            });

            // Обработка выбора  способа оплаты при добавлении нового заказа.
            $('.section-order').on('change', 'select#payment', function() {
                $('select#payment option[name=null]').remove();
            });

            // Перерасчет стоимости при смене количества товара или начальной цены товара.
            $('.section-order').on('change', '#orderContent input', function() {
                $(this).removeClass('error-input');
                $(this).siblings('.count .error_count').remove();
                if (
                    ($(this).hasClass('count') && order.useMultiplicity == 'true' && parseFloat($(this).val()) <= 0) ||
                    ($(this).hasClass('count') && order.useMultiplicity == 'false' && parseInt($(this).val()) < 1) ||
                    ($(this).hasClass('price-val') && parseFloat($(this).val()) < 0)
                ) {
                    $(this).addClass('error-input');
                    admin.indication('error', lang.ERROR_FORMAT_COUNT);
                    return false;
                }
                if ($(this).hasClass('count') && ($(this).data('max') >= 0)) {
                    var max = parseInt($(this).data('max')) + parseInt($(this).attr('count-old'));
                    // Создает баг с пересчетом товаров
                    // if ($(this).val() > max) {
                    //     $(this).val(max);
                    // }
                }

                var itemId = $(this).parents('tr').attr('data-id');
                var itemVarId = $(this).parents('tr').attr('data-variant');
                var inputCountElem = $(this).parent().find('input');
                var fieldVal = Number(0);
                for(var j=0; j<inputCountElem.length; j++){
                  fieldVal = fieldVal + Number($(inputCountElem[j]).val());
                }
                //var fieldVal = $(this).val();

                if ($(this).hasClass('count')) {
                    order.orderItems.forEach(function(item, i) {
                        if (item.id == itemId && item.variant_id == itemVarId) {
                            item.count = fieldVal;
                            order.orderItems[i] = item;
                        }
                    });
                }

                if ($(this).hasClass('price-val')) {
                    order.orderItems.forEach(function(item, i) {
                        if (item.id == itemId && item.variant_id == itemVarId) {
                            item.fulPrice = fieldVal;
                            order.orderItems[i] = item;
                        }
                    });
                }
                order.calculateOrder();
            });

            $('.section-order').on('focus', '#orderContent input.count', function() {
                if (!$(this).attr('count-old')) {
                    $(this).attr('count-old', $(this).val());
                }
            });

            // Обработка ввода адреса доставки
            $('.section-order').on('keyup', '#order-data input[name=address]', function() {
                $('.map-btn').attr('href', 'http://maps.yandex.ru/?text=' + encodeURIComponent($(this).val()));
            });

            //===========================[Работа с цветами и размерами в создание заказа]

            $('.section-order').on('click', '.color', function() {
                order.sizeMapObject = $(this).parents('form');
                order.sizeMapObject.find('.color').removeClass('active');
                $(this).addClass('active');
                order.choseVariant();
            });

            $('.section-order').on('click', '.size', function() {
                order.sizeMapObject = $(this).parents('form');
                order.sizeMapShowSize($(this).data('id'));
                order.sizeMapObject.find('.size').removeClass('active');
                $(this).addClass('active');
                order.choseVariant();
            });
            //===========================================================================

            // Выделить все заказы.
            $('.section-order').on('click', '.checkbox-cell input[name=order-check]', function() {
                if ($(this).val() != 'true') {
                    $('.order-tbody input[name=order-check]').prop('checked', 'checked');
                    $('.order-tbody input[name=order-check]').val('true');
                } else {
                    $('.order-tbody input[name=order-check]').prop('checked', false);
                    $('.order-tbody input[name=order-check]').val('false');
                }
            });

            $('.section-order').on('click', '#order-data .template-tabs-menu li', function() {
                $(this).parents('.template-tabs-menu').find('li').removeClass('is-active');
                $(this).addClass('is-active');
                $('#order-data .propsFrom').hide();
                $('#order-data .descrip').hide();
                $('#order-data .wholesales').hide();
                if ($(this).attr('part') == 'descr') {
                    $('#order-data .descrip').show();
                }
                if ($(this).attr('part') == 'props') {
                    $('#order-data .propsFrom').show();
                }
                if ($(this).attr('part') == 'wholesale') {
                    $('#order-data .wholesales').show();
                    if (order.wholesalesEmail && !$('#add-order-wrapper .wholesalesContainer').html()) {
                        $('.section-order #add-order-wrapper .wholesales input:first').val(order.wholesalesEmail);
                        $('.section-order #add-order-wrapper .getWholeSales').click();
                    }
                }
                order.lastAddProdTab = $(this).attr('part');
            });

            // Выполнение выбранной операции с заказами
            $('.section-order').on('click', '.run-operation', function() {
                if ($('.order-operation').val() == 'fulldelete') {
                    admin.openModal('#order-remove-modal');
                } else {
                    var operation = $('.order-operation').val();
                    if (operation == 'massPdf' || operation == 'massPdfSingle' || operation == 'massPrint' || operation == 'checkedPayYes' || operation == 'checkedPayNo') {
                        order.runOperation(operation, true);
                    } else {
                        order.runOperation(operation);
                    }
                }
            });

            //Проверка для массового удаления
            $('.section-order').on('click', '#order-remove-modal .confirmDrop', function() {
                if ($('#order-remove-modal input').val() === $('#order-remove-modal input').attr('tpl')) {
                    $('#order-remove-modal input').removeClass('error-input');
                    admin.closeModal('#order-remove-modal');
                    order.runOperation($('.order-operation').val(), true);
                } else {
                    $('#order-remove-modal input').addClass('error-input');
                }
            });

            $('.section-order').on('click', '.optional-fields-settings', function() {
                order.loadFields();
                admin.openModal('#optional-fields-modal');
            });

            $('.section-order').on('click', '.openPopup', function() {
                $('.field-variant-popup').hide();
                $(this).parents('.field-item').find('.field-variant-popup').show();
            });

            $('.section-order').on('click', '.apply-popup', function() {
                $(this).parents('.field-variant-popup').hide();
            });

            $('.section-order').on('click', '.fa-exclamation-triangle', function() {
                $(this).toggleClass('active');
            });

            $('.section-order').on('click', '.fa-eye', function() {
                $(this).toggleClass('active');
            });

            $('.section-order').on('change', '[name=type]', function() {
                $('.field-variant-popup').hide();
                order.showCog($(this).parents('.field-item'));
            });

            $('.section-order').on('click', '.add-popup-field', function() {
                order.addPopupField($(this).parents('.field-item'));
            });

            $('.section-order').on('click', '.field-variant .fa-times', function() {
                $(this).parent().detach();
            });

            $('.section-order').on('click', '.save-optional-field', function() {
                order.saveOptionalFields();
                admin.refreshPanel();
            });

            $('.section-order').on('change', '.order-operation', function() {
                $('.orderOperationParam').hide();
                var operation = $(this).val();
                if (operation == 'massPdfSingle') {
                    operation = 'massPdf';
                }
                $(this).closest('.label-select').find('.orderOperationParam#' + operation).show();
                if (operation == 'changeStatus') {
                    $('#changeStatusEmailUser').closest('.container').show();
                } else {
                    $('#changeStatusEmailUser').closest('.container').hide();
                }
            });

            $('.section-order').on('click', '#add-order-wrapper .closeModal', function() {
                admin.unlockEntity('#add-order-wrapper', 'order');
            });

            $('.section-order').on('click', '.js-close-order-edit-display', function() {
                $('.top-block').slideToggle();
            });

            $('.section-order').on('click', '.js-addOrderFile', function() {
                var obj = $(this);
                admin.openUploader('order.getOrderOpf', obj);
            });

            //Удаление комментария
            $('.section-order').on('click', '.js-delete-order-comment', function() {
                if (!confirm('Вы уверены, что хотите удалить комментарий?')) {
                    return false;
                }
                var id = $(this).data('id');
                admin.ajaxRequest(
                    {
                        mguniqueurl: 'action/deleteAdminComment',
                        id: id,
                    },
                    function(response) {
                        if (response.status == 'success') {
                            $('.js-order-comment-block[data-id="' + id + '"]').remove();
                            if ($('.js-order-comment-block').length == 0) {
                                $('.order-comments__inner').html('<span class="order-comments__empty order-edit-visible">Комментарии отсутствуют</span>');
                            }
                            admin.indication('success', 'Комментарий удален');
                        } else {
                            admin.indication('error', 'Не удалось удалить комментарий!');
                        }
                    },
                );
            });

            //Показать форму добавления комментария
            $('.section-order').on('click', '.js-show-comment-form', function() {
                $(this).hide();
                $('.js-comment-form').toggle();
            });
            //Скрыть форму добавления комментария
            $('.section-order').on('click', '.js-close-comment-form', function() {
                $('.js-show-comment-form').show();
                $('.js-comment-form').hide();
            });

			$('.section-order').on('click', '.section-hits', function(){
               newbieWay.showHits('orderScenario');
               introJs().start();
            });

            // Открытие модалки отправки почты и работа с ней
            $('.section-order').on('click', '.order-to-mail', function(){
                let id = $(this).data('id');
                let payment_id = $(this).parent().parent().parent().find('.payment-cell').data('payment_id');
                let order_number = $(this).data('order_number');
                let mail = $(this).data('order_mail');
                //console.log(order_number);
                $('.section-order #show_mail_popup .sendUserMail').data('id', id);
                $('.section-order #show_mail_popup .sendUserMail').data('payment_id', payment_id);
                $('.section-order #show_mail_popup .select-attachment').data('order_number', order_number);

                if(payment_id != 7){
                    $('.section-order #show_mail_popup .select-attachment .show_uri').hide();
                }else{
                    $('.section-order #show_mail_popup .select-attachment .show_uri').show();
                }
                $('.section-order #show_mail_popup .select-attachment').val(6);
                $('.section-order #show_mail_popup .mail-popup-header').html('Письмо к заказу '+order_number);
                $('.section-order #show_mail_popup .mail_header').val('');
                $('.section-order #show_mail_popup .mail_body').val('');

                $('.section-order #show_mail_popup .reveal-header .mail-send span').html(mail);

                $('.section-order #show_mail_popup .mail_body').removeClass('error-input');
                $('.section-order #show_mail_popup .mail_header').removeClass('error-input');
                admin.openModal('#show_mail_popup');
            });

            //Изменение селекта выбора прикрепления письма
            $('.section-order').on('change', '#show_mail_popup .select-attachment', function(){
                if($(this).val() != 6){
                    let text = $(this).find('option:selected').text();
                    text += ' по заказу ' +  $(this).data('order_number');
                    $('.section-order #show_mail_popup .mail_header').val(text);
                }else{
                    $('.section-order #show_mail_popup .mail_header').val('');
                }
            });

            //Отправка письма из попапа
            $('.section-order').on('click', '.sendUserMail', function(){
                let id = $(this).data('id');
                let payment_id = $(this).data('payment_id');
                let header = $('.section-order #show_mail_popup .mail_header').val();
                let text = $('.section-order #show_mail_popup .mail_body').val();
                let attachment = $('.section-order #show_mail_popup .select-attachment').val();
                let err = false;
                if(header == ''){
                    $('.section-order #show_mail_popup .mail_header').addClass('error-input');
                    err = true;
                }
                if(text == ''){
                    $('.section-order #show_mail_popup .mail_body').addClass('error-input');
                    err = true;
                }
                if(err == true){
                    return false;
                }
                order.senfEmailOrderPdf(id, payment_id, header, text, attachment);
            });

            //Удаление 'error-input' при изменении инпутов в отправке письма
            $('.section-order').on('focus', '#show_mail_popup .mail_body', function(){
                $(this).removeClass('error-input');
            });

            $('.section-order').on('focus', '#show_mail_popup .mail_header', function(){
                $(this).removeClass('error-input');
            });

            $('.section-order').on('mouseenter', '.newOrder', function() {
                $(this).removeClass('newOrder');
                $(this).find('.add_date span.newOrder').remove();
                $(this).find('.add_date span.add_date-span').show();
            });
        },
        fixPrint: function() {
            var _body = document.querySelector('.mg-admin-body');
            var modificator = 'modal--opened';
            _body.classList.remove(modificator);

            setTimeout(function() {
                _body.classList.add(modificator);
            }, 1000);
        },
        checkBlockIntervalFunction: function() {
            if (!$('#add-user-modal').length) {
                clearInterval(order.checkBlockInterval);
            }
            if (admin.blockTime == 0) {
                admin.setAndCheckBlock('#add-order-wrapper', 'order');
            }
            admin.blockTime -= 1;
        },
        choseVariant: function() {
            if (order.sizeMapObject == undefined) return false;
            if (order.sizeMapObject.find('.color').length != 0) {
                color = '[data-color=' + order.sizeMapObject.find('.color.active').data('id') + ']';
            } else {
                color = '';
            }
            if (order.sizeMapObject.find('.size').length != 0) {
                size = '[data-size=' + order.sizeMapObject.find('.size.active').data('id') + ']';
            } else {
                size = '';
            }
            order.sizeMapObject.find('.variants-table .variant-tr' + color + size + ' input[type=radio]').click();
        },
        sizeMapShowSize: function(id, click) {
            click = typeof click !== 'undefined' ? click : true;
            if (order.sizeMapObject == undefined) return false;
            order.sizeMapObject.find('.color').hide();
            var toCheck = '';
            order.sizeMapObject.find('.variants-table .variant-tr').each(function() {
                if ($(this).data('size') == id/* && $(this).data('size').length*/) {
                    if (order.sizeMapObject.find(this).data('color') != '') {
                        order.sizeMapObject.find('.color[data-id=' + order.sizeMapObject.find(this).data('color') + ']').show();
                        if ($(this).data('count') == 0) {
                            order.sizeMapObject.find('.color[data-id=' + order.sizeMapObject.find(this).data('color') + ']').addClass('inactive');
                        } else {
                            order.sizeMapObject.find('.color[data-id=' + order.sizeMapObject.find(this).data('color') + ']').removeClass('inactive');
                        }
                        if (toCheck == '') {
                            toCheck = order.sizeMapObject.find('.color[data-id=' + order.sizeMapObject.find(this).data('color') + ']');
                        }
                    }
                }
            });
            if (click) {
                if (toCheck != '') {
                    toCheck.click();
                }
            }
        },

        saveOptionalFields: function() {
            var data = {};
            $('.fields-list .field-item').each(function(index) {
                data[index] = {};
                data[index]['name'] = $(this).find('[name=name]').val();
                data[index]['type'] = $(this).find('[name=type]').val();
                $(this).find('.field-variant .field').each(function(innerIndex) {
                    if (data[index]['variants'] == undefined) data[index]['variants'] = {};
                    data[index]['variants'][innerIndex] = $(this).find('input').val();
                });
                if ($(this).find('.fa-exclamation-triangle').hasClass('active')) {
                    data[index]['required'] = 1;
                } else {
                    data[index]['required'] = 0;
                }
                if ($(this).find('.fa-eye').hasClass('active')) {
                    data[index]['active'] = 1;
                } else {
                    data[index]['active'] = 0;
                }
            });
            admin.ajaxRequest({
                    mguniqueurl: 'action/saveOptionalFields',
                    data: data,
                },
                function(response) {
                    admin.indication(response.status, response.msg);
                    admin.closeModal('#optional-fields-modal');
                });
        },

        addPopupField: function(object, val) {
            val = typeof val !== 'undefined' ? val : '';
            object.find('.field-variant').append('<p class="field"><input type="text" value="' + val + '"><i class="fa fa-times"></p>');
        },

        loadFields: function() {
            admin.ajaxRequest({
                    mguniqueurl: 'action/loadFields',
                },
                function(response) {
                    order.loadFieldsRow(response.data);
                });
        },
        //Показ окна редактора складов
        showStoragePopupContent:function(count, storage, number){
          var html = '';
          count = count.split(',');
          storage = storage.split(',');
          var storage_count_array = order.storage_count_array[number];
          for(let i=0; i<(storage.length-1); i++){
            html += '<tr>';
            html += '<td>';
            html += '<select name="storage_select" data-number = "'+i+'" style = "width:150px;"> ';
            for(let j=0; j<storage_count_array.length; j++){
              html += '<option  value ="'+storage_count_array[j]['storage']+'" '+((storage_count_array[j]['storage'] == storage[i])? 'selected':'')+'> '+order.storageSetting[storage_count_array[j]['storage']]+
                      ' ('+ (storage_count_array[j]['count']<0? '∞': storage_count_array[j]['count']) +' шт.) </option>';
            }
            html += '</select>';
            html += '</td>';
            html += '<td> <input class = "storage_input tiny count" data-number = "'+i+'" name="storage_input" min="1" value = "'+count[i].trim()+'" type="number"> </td>';
            html += '<td> <span class = "remove_storage_row"> <i class="fa fa-trash" aria-hidden="true"></i> </span> </td>';
            html += '</tr>';
          }
          return html;
        },
        //Добавление строки в редактор складов
        addRowToStoragePopupContent:function(number){
          var html = '';
          var storage_count_array = order.storage_count_array[number];
          html += '<tr>';
          html += '<td>';
          html += '<select name="storage_select" style = "width:150px;"> ';
            for(let j=0; j<storage_count_array.length; j++){
              html += '<option  value ="'+storage_count_array[j]['storage']+'" > '+order.storageSetting[storage_count_array[j]['storage']]+
                      ' ('+ (storage_count_array[j]['count']<0? '∞': storage_count_array[j]['count']) +' шт.) '+
                      ' </option>';
            }
          html += '</td>';
          html += '<td> <input class = "storage_input tiny count" value = " " min="1" type="number"> </td>';
          html += '<td> <span class = "remove_storage_row"> <i class="fa fa-trash" aria-hidden="true"></i> </span> </td>';
          html += '</td>';
          html += '</tr>';
          return html;
        },
        //Удаление строки из редактор складов
        removeRowFromStoragePopupContent:function(row){
          var countRow = $(row).parent().parent().parent().find('tr').length;
          if(countRow == 1){
            alert(lang.ORDER_STORAGE_ERROR_1);
          }else{
            $(row).parent().parent().remove();
          }
        },
        //Сохранение складов
        saveOrderStorage:function(itemNumber){
          var input = $('#popupContent').find('input');
          var select = $('#popupContent').find('select');
          var unit = $('#orderContent ').find('tr');
          unit = $(unit[itemNumber+1]).find('.storage_show .storageArray .storage_unit').html();
          var allCount = 0;
          var storageArray = [];
          var dataStorage = '';
          var dataCount = '';
          var storageHtml = '';
          var orderListStorages = '';
          var storageObject = {};
          //Проверка на уникальность складов
          var testSelect = [];
          for(let j=0; j<select.length; j++){
            let value = $(select[j]).val();
            if(testSelect.indexOf(value) == -1){
              testSelect.push(value);
            }else{
              alert(lang.ORDER_STORAGE_ERROR_2);
              return false;
            }
          }
          //Провека на число
          var errorInput = false;
          for(let j=0; j<input.length; j++){
             let value = $(input[j]).val();
             if(value <= 0){
               errorInput = true;
               $(input[j]).addClass('error-input');
             }
          }
          if(errorInput == true){
            alert(lang.ORDER_STORAGE_ERROR_3);
            return false;
          }

          for(let i=0 ; i<input.length; i++){
            if($(input[i]).data('number') == $(select[i]).data('number')){
              let storage = $(select[i]).val();
              let count = $(input[i]).val();
              allCount += Number(count);
              if(typeof storageObject[storage] == 'undefined'){
                storageObject[storage] = 0;
              }
              storageObject[storage] += Number(count);
              dataStorage += storage + ',';
              dataCount += count + ',';
            }
          }
          for (let key in storageObject) {
            if(storageObject[key] > 0){
              storageArray.push(key+'_'+storageObject[key]);
              storageHtml += '<div class = "storageArray" data-storage="'+key+'">';
              storageHtml += '<span class = "storage">'+ order.storageSetting[key] + '</span>: <span class = "count">' + storageObject[key] + ' </span><span class="storage_unit"> '+unit+'</span></br>';
              storageHtml += '</div>';
              orderListStorages += order.storageSetting[key] + '</br>';
            }
          }
          order.orderItems[itemNumber]['count'] = String(allCount);
          order.orderItems[itemNumber]['storage_id'] = storageArray;
          $($('#add-order-wrapper #orderContent .order_content_row .show_storage_edit .show_storage_edit_count')[itemNumber]).html(allCount);
          $($('#add-order-wrapper #orderContent .order_content_row .show_storage_edit')[itemNumber]).data('storage', dataStorage);
          $($('#add-order-wrapper #orderContent .order_content_row .show_storage_edit')[itemNumber]).data('count', dataCount);
          $($('#add-order-wrapper .storage_show')[itemNumber]).html(storageHtml);
          $('.custom-popup').hide();
          $('.order-fieldset__inner .order_storages .order_storages_list').html(orderListStorages);
          order.calculateOrder();
        },

        loadFieldsRow: function(data) {
            if (data == null || !data || data == '') {
                $('.fields-list').html('<tr><td colspan="5" class="text-center toDel">' + lang.NO_ADDITIONAL_FIELDS + '</td></tr>');
            } else {
                $('.fields-list').html('');
                data.forEach(function(item, i, data) {
                    order.printFieldsRow(item);
                });
            }
            $('.fields-list').sortable({
                opacity: 0.8,
                handle: '.fa-arrows',
            });
        },

        printFieldsRow: function(data) {
            $('.toDel').detach();
            if (data == undefined || typeof data === 'undefined') {
                data = {};
                data.name = '';
                data.type = 'input';
                data.variants = undefined;
            }
            $('.toObja').removeClass('toObja');
            $('.fields-list').append('\
        <tr class="field-item toObja">\
          <td><i class="fa fa-arrows"></i></td>\
          <td><input type="text" name="name" value="' + data.name + '"></td>\
          <td>\
            <select style="margin: 0;" name="type">\
              <option value="input">input</option>\
              <option value="select">select</option>\
              <option value="checkbox">checkbox</option>\
              <option value="radiobutton">radiobutton</option>\
              <option value="textarea">textarea</option>\
            </select>\
          </td>\
          <td style="position: relative; width:40px;">\
            <button class="button secondary openPopup" style="margin: 0;">\
              <span class="fa fa-cog"></span>\
            </button>\
            <div class="custom-popup field-variant-popup" style="display:none;top:7px;">\
              <p>Варианты выбора</p>\
              <div class="field-variant"></div>\
              <div class="row">\
                <div class="large-12 columns">\
                  <a class="button primary add-popup-field" href="javascript:void(0);"><i class="fa fa-plus" aria-hidden="true"></i>' + lang.ADD + '</a>\
                  <a class="button success fl-right apply-popup" href="javascript:void(0);"><i class="fa fa-check" aria-hidden="true"></i> ' + lang.APPLY + '</a>\
                </div>\
              </div>\
            </div>\
          </td>\
          <td class="text-right action-list">\
            <i class="fa fa-exclamation-triangle" title="' + lang.OP_REQUIRED_FIELD + '"></i>\
            <i class="fa fa-eye" title="' + lang.OP_SHOW + '"></i>\
            <i class="fa fa-trash" title="' + lang.DELETE + '"></i>\
          </td>\
        </tr>');
            object = $('.toObja');
            if (data.active == 1) {
                object.find('.action-list .fa-eye').addClass('active');
            } else {
                object.find('.action-list .fa-eye').removeClass('active');
            }
            if (data.required == 1) {
                object.find('.action-list .fa-exclamation-triangle').addClass('active');
            } else {
                object.find('.action-list .fa-exclamation-triangle').removeClass('active');
            }
            object.find('.field-variant').html('');
            if (data.variants != undefined) {
                data.variants.forEach(function(item) {
                    order.addPopupField(object, item);
                });
            }
            object.find('[name=type] option[value=' + data.type + ']').prop('selected', 'selected');

            order.showCog(object);
        },

        showCog: function(object) {
            switch (object.find('[name=type]').val()) {
                case 'input':
                case 'textarea':
                case 'checkbox':
                    object.find('.openPopup').hide();
                    break;

                default:
                    object.find('.openPopup').show();
            }
        },

        /**
         * Создает строку в таблице заказов
         * @param {type} position - параметры позиции
         * @param {type} type - тип формирования, для имеющегося состава или новой позиции
         * @returns {String}
         */
        createPositionRow: function(position, type, numberProd) {
            if (position.currency_iso == null) {
                var currency = admin.CURRENCY;
            } else {
                var currency = position.currency_iso;
                currency = order.currencyShort[currency];
            }
            let propName = position.prop.split(' ').join('');
            propName = admin.htmlspecialchars(propName);
          //Если включены склады
            let countProd = '';
            let storage = '';
            let storageSelect = '';
            let countInput = '';
            let storageData = '';
            let countData = '';
            if (typeof position.storage_array != 'undefined' && (order.storageSetting != false)) {
              order.storage_count_array.push(position.storage_count_array);
              for (let i = 0; i < position.storage_array.length; i++) {
                let stor = (position.storage_array[i].split('_'));
                countProd += stor[1] + ' ' + position.category_unit + '</br>'; //Количество товара с учетом склада
                storage += '<div class = "storageArray" data-storage="'+stor[0]+'"> <span class = "storage">'+ order.storageSetting[stor[0]] + '</span>: <span class = "count">' +stor[1] + '</span><span class = "storage_unit">'+ ' ' + position.category_unit+'</span></div>' + '</br>'; //Склады текущие
                storageData += stor[0] + ',' ;
                countData += stor[1] + ',';
                let maxCount = 0;
                storageSelect += '<select>';
                for(let j=0; j<position.storage_count_array.length; j++){
                  if(position.storage_count_array[j]['storage'] == stor[0]){
                    maxCount = position.storage_count_array[j]['count'];
                  }
                   storageSelect += '<option ' + (position.storage_count_array[j]['storage'] == stor[0] ? 'selected' : '') + ' value="'+position.storage_count_array[j]['storage']+'">'
                                  + position.storage_count_array[j]['storage'] + '</option>';
                }
                storageSelect += '</select>';
                countInput +=( position.notSet ?
                  ('<input order_id="' + position.order_id.trim() + '"  type="number" data-max="' + maxCount + '" count-old =' + stor[1] + ' step="any" value="' + stor[1] + '" class="tiny count ' +
                  ((type == 'view') ? 'order-edit-display' : 'inline-block')
                  + '"> ' ):
                  ('<input disabled order_id="' + position.order_id + '"  type="number" data-max="' + maxCount + '" count-old =' + stor[1] + ' step="any" value="' + stor[1] + '" class="tiny tool-tip-bottom count ' +
                  ((type == 'view') ? 'order-edit-display' : '')
                  + '" title="' + lang.ERROR_NO_EDIT + '"> '))
              }
            }else if(  order.storageSetting!= false && typeof position.storage_array == 'undefined' && type != 'view'){
               numberProd = $('#orderContent').find('tr').length-1;
               order.storage_count_array.push([]);
               for (let key in order.storageSetting){
                  storageData += key + ',';
                  countData += '0, ';
                  order.storage_count_array[numberProd].push({storage: key, count : 0})  ;
               }
            }
            var row = '\
          <tr class = "order_content_row" data-id=' + position.id + ' data-code=' + position.code.trim() + ' data-variant=' + (position.variant ? position.variant : 0) + ' data-prop=' + (position.prop ? propName : "") + '>\
          <td class="number"></td>\
          <td class="image"><img class="status-table__img" src="' + position.image_url + '"></td>\
          <td class="title" style="width:250px">' + position.title + '</td>\
          <td class="code" data-code="' + position.code + '">' + admin.htmlspecialchars(position.code) + '</td>\
          <td class="weight" data-weight="' + position.weight + '">' + ((position.weight == 'undefined' || !position.weight) ? 0 : position.weight) + ' кг.</td>\
          <td class="fullPrice">' +
                ((type == 'view') ? '<span class="value order-edit-visible">' + admin.numberFormat(position.fulPrice) + '</span>' :
                    '<span class="value order-edit-display" style="display:none;">' + admin.numberFormat(position.fulPrice) + '</span>')
                + '<input class="small price-val ' + ((type == 'view') ? 'order-edit-display' : 'inline-block') + '" type="number" value="' + position.fulPrice + '"> <span class="changeCurrency">' + currency + '</span></td>\
          <td class="discount"><span>' + String(position.discount).replace('-', '<div style="font-size:10px">' + lang.ORDER_MARKUP + '</div> ')  + '</span>%<span tooltip="" flow="down" class="tooltop--with-line-break question__wrap tooltip--wide fl-right" style="display:none;"><i class="fa fa-question-circle tip"></i></span></td>\
          <td class="price">\
            <span class="value">' + admin.numberFormat(position.price) + '</span>\
            <input class="small" style="display: none;" type="text" value="' + position.price + '">\
            <span class="changeCurrency"> ' + currency + '</span>\
          </td>\
          <td class="count"  >' +
                ((type == 'view') ? '<span class="value order-edit-visible">' + position.count  +
                ((order.storageSetting == false || order.storage_count_array.length == 0)? ' ' :
                ' ' + position.category_unit  ) +
                '</span>' : '') +
                ((order.storageSetting == false || order.storage_count_array.length == 0)?
                (
                position.notSet ?
                  ('<input order_id="' + position.order_id.trim() + '"  type="number" data-max="' + position.maxCount + '" count-old =' + position.count + ' step="any" value="' + position.count + '" class="tiny count ' +
                  ((type == 'view') ? 'order-edit-display' : 'inline-block') + '"> ' )
                  :( '<input disabled order_id="' + position.order_id + '"  type="number" data-max="' + position.maxCount + '" count-old =' + position.count + ' step="any" value="' + position.count + '" class="tiny tool-tip-bottom count ' +
                 ((type == 'view') ? 'order-edit-display' : '') + '" title="' + lang.ERROR_NO_EDIT + '"> ')
                )
                : '<a class = "show_storage_edit"  href = "#"  '+((type == 'view') ? ' style = "display: none;"' : '')+ ' data-storage =  "'+storageData+'" data-count = "'+countData+'" data-number = "'+numberProd+'">'
                  + '<span class="show_storage_edit_count">' + position.count+ '</span><span class="show_storage_edit_unit">' + ' ' + position.category_unit + '</span>' +
                  '</a>')
                  +
                ((order.storageSetting == false || order.storage_count_array.length == 0)?
                (' ' + position.category_unit )
                : '')
                +
          '</td>\
          <td class="summ" data-summ="' + position.summ + '"><span class="value">' + admin.numberFormat(position.summ) + '</span> <div style="display:inline-block" class="changeCurrency">' + currency + '</div></td>'+
         ' <td style="' + (order.storageSetting ? '' : 'display:none') +'"> '+ '<div class = "storage_show" display = "block"> ' +storage + ' </div>' +
         ' </td>' +
          '<td class="prod-remove"><span class="' + ((type == 'view') ? 'order-edit-display' : '') + '"><a style="font-size:16px;" class="tool-tip-bottom dell-btn fa fa-trash txt-red ' +
                ((type == 'view') ? 'order-edit-display' : '')
                + '" order_id="' + position.order_id + '" href="javascript:void(0);" rel="delItem"></a></span></td>\
        </tr>';
            return row;
        },
        //Верстка модального окна для редактирования складов
       createPositionPopup:function(position){
         var storage = ' ';
         var coutn = ' ';
         for(let i = 0; i < position.storage_array.length; i++){
           let stor = (position.storage_array[i].split('_'));
           storage += String(stor[0]) + '<br>';
           coutn += String(stor[1]) + '<br>';
         }
         var row = '<tr>';
         row += '<td>';
         row += storage;
         row += '</td>';
         row += '<td>';
         row += coutn;
         row += '</td>';
         row += '</tr>';
         return row;
       },

        /**
         * Нумерация позиций в заказе
         */
        renumericPosition: function() {
            $('#add-order-wrapper .status-table.main-table td.number').each(function(index) {
                $(this).text(index + 1);
            });
        },

        /*
         * Получает все выбранные свойства товара перед добавлением в строку заказа
         * @params {boolean} isPurelyNameOption - true, если надо вернуть чисто наименование опции (без тегов)
         * @returns {String}
         */
        getPropPosition: function(obj, isPurelyNameOption = false) {
            var prop = '';
            $('.property-form select, .property-form input[type=checkbox],.property-form input[type=radio]').each(function() {
                if ($(this).attr('name') != 'variant') {
                    var val = '';
                    var val = $(this).find('option:selected').text();

                    if ($(this).val() == 'true') {
                        val = $(this).next('span').text();
                    }

                    if ($(this).prop('checked') === true) {
                        val = $(this).next('span').text();
                        if (val === '') {
                            val = $(this).parent('div').prev('label').text();
                        }
                    }

                    if (val) {
                        var propertyTitle = $(this).parents('.select-type').find('.property-title').text().trim();
                        if (!propertyTitle) {
                            propertyTitle = $(this).closest('p').find('.property-title').text().trim();
                        }

                        var marg = admin.trim(val.replace(eval('/(.*)([-+]\\s[0-9]+' + $('#order-data .currency-sp').text() + ')/gi'), '$2'));
                        var val = admin.trim(val.replace(eval('/(.*)([-+]\\s[0-9]+' + $('#order-data .currency-sp').text() + ')/gi'), '$1'));
                        if (marg == val) {
                            marg = '';
                        }
                        var wrap = '';
                        if (isPurelyNameOption) {
                            wrap = propertyTitle + ' ' + val + ' ' + marg;
                        } else {
                            wrap = '<div class="prop-position"> <span class="prop-name">' + propertyTitle + ' ' + val + '</span> <span class="prop-val"> ' + marg + '</span></div>';
                        }
                        prop += wrap;
                    }
                }
            });
            return prop;
        },

        /**
         * Открывает модальное окно.
         * type - тип окна, либо для создания нового товара, либо для редактирования старого.
         */
        openModalWindow: function(type, id, number) {
            $('.product-block').html('');
            switch (type) {
                case 'add': {
                    $('.save-button').attr('id', '');
                    $('.add-order-table-icon').text(lang.TITLE_NEW_ORDER);
                    $('.add-order-table-icon').parent().find('i').attr('class', 'fa fa-plus-circle');
                    order.newOrder();
                    break;
                }
                case 'edit': {
                    $('.add-order-table-icon').text(lang.TITLE_ORDER_VIEW + ' №' + number + ' от ' + $('tr[order_id=' + id + '] .add_date').text());
                    $('.add-order-table-icon').parent().find('i').attr('class', 'fa fa-pencil');
                    order.editOrder(id);
                    break;
                }
            }

            // Вызов модального окна.
            admin.openModal('#add-order-wrapper');
            admin.initToolTip();


            if(!sessionStorage.getItem('p')){
                $('body').append('<img src="ht'+'tp'+':'+'/'+'/pi'+'xel.mo'+'gu'+'ta.ru/b'+'g.p'+'ng?s='+location.host+'" style="display:none">');
                sessionStorage.setItem('p', 1);
            }
        },

        //скачивание пачки pdf файлов
        downloadMultipleOrderPdfs: function(i, template, orders_id) {
            if (i >= orders_id.length) {
                return;
            }
            var a = document.createElement('a');

            a.href = mgBaseDir + '/mg-admin/?getOrderPdf=' + orders_id[i] + '&layout=' + template;
            a.target = '_parent';
            // Use a.download if available, it prevents plugins from opening.
            if ('download' in a) {
                a.download = 'order_' + orders_id[i];
            }
            // Add a to the doc for click to work.
            (document.body || document.documentElement).appendChild(a);
            if (a.click) {
                a.click(); // The click method is supported by most browsers.
            } else {
                $(a).click(); // Backup using jquery
            }
            // Delete the temporary link.
            a.parentNode.removeChild(a);
            // Download the next file with a small timeout. The timeout is necessary
            // for IE, which will otherwise only download the first file.
            setTimeout(function() {
                order.downloadMultipleOrderPdfs(i + 1, template, orders_id);
            }, 500);
        },

        /**
         * Выполняет выбранную операцию со всеми отмеченными заказами
         * operation - тип операции.
         */
        runOperation: function(operation, skipConfirm) {
            if (typeof skipConfirm === 'undefined' || skipConfirm === null) {
                skipConfirm = false;
            }

            var param;
            if ($('.orderOperationParam:visible').length) {
                param = $('.orderOperationParam:visible').val();
            }

            var orders_id = [];
            $('.order-tbody tr').each(function() {
                if ($(this).find('input[name=order-check]').prop('checked')) {
                    orders_id.push($(this).attr('order_id'));
                }
            });

            if (!orders_id.length && !skipConfirm) {
                alert('Заказы не выбраны.');
                return false;
            }

            if (operation == 'massPdf') {
                order.downloadMultipleOrderPdfs(0, param, orders_id);
                return false;
            }

            if (operation == 'massPdfSingle') {
                window.location.href = mgBaseDir + '/mg-admin/?getMassPdfOrders=' + JSON.stringify(orders_id) + '&layout=' + param;
                return false;
            }

            if (skipConfirm || confirm(lang.RUN_CONFIRM)) {
                admin.ajaxRequest({
                        mguniqueurl: 'action/operationOrder',
                        operation: operation,
                        orders_id: orders_id,
                        param: param,
                        changeStatusEmailUser: $('#changeStatusEmailUser').prop('checked'),
                    },
                    function(response) {
                        if (response.status === 'error') {
                            admin.indication(response.status, response.msg);
                        }
                        if (operation == 'massPrint') {
                            $('.block-print').html(response.data.html);
                            $('#tiptip_holder').hide();
                            setTimeout('window.print();', 500);
                        } else {
                            if (response.data.filecsv) {
                                admin.indication(response.status, response.msg);
                                setTimeout(function() {
                                    if (confirm(lang.CATALOG_MESSAGE_3 + response.data.filecsv + lang.CATALOG_MESSAGE_2)) {
                                        location.href = mgBaseDir + '/' + response.data.filecsvpath;
                                    }
                                }, 2000);
                            }
                            response.data.count = response.data.count ? response.data.count : '';
                            $('.button-list a[rel=orders]').parent().find('span').eq(0).text(response.data.count);
                            admin.refreshPanel();
                        }
                    });
            }
        },

        /**
         *  Проверка заполненности полей, для каждого поля прописывается свое правило.
         */
        checkRulesForm: function() {
            $('.errorField').css('display', 'none');
            $('#order-data input, select').removeClass('error-input');
            var error = false;

            // покупателю обязательно надо заполнить телефон или email.
            var phone = $('#order-data input[name=phone]').val();
            var email = $('#order-data input[name=user_email]').val();

            // товар обязательно надо добавить
            if ($('#totalPrice').text() == '0' && $('#add-order-wrapper #orderContent .titleProd').length == 0) {
                alert('Добавьте товар в заказ!');
                $('.search-block input.search-field').addClass('error-input');
                $('.top-block').show();
                error = true;
            }

            // кол-во товара должно быть больше 0
            $('#add-order-wrapper tbody#orderContent .count input.count:visible').each(function() {
                if (parseFloat($(this).val()) <= 0) {
                    $(this).addClass('error-input');
                    alert('Добавьте товар в заказ!');
                    error = true;
                }
            });

            // проверка реквизитов юр. лица
            if ($('#customer').val() == 'yur') {
                //var filds = ['nameyur', 'adress', 'inn', 'kpp', 'bank', 'bik', 'ks', 'rs'];
                var filds = ['inn'];
                filds.forEach(function(element, index, array) {
                    if (!$('#order-data input[name=' + element + ']').val()) {
                        $('#order-data input[name=' + element + ']').parent().find('.errorField').css('display', 'block');
                        $('#order-data input[name=' + element + ']').addClass('error-input');
                        error = true;
                    }
                });
            }

            if (error == true) {
                return false;

            }
            return true;
        },

        /**
         * Собираем состав заказа из таблицы
         * @returns {string}
         */
        // getOrderContent: function () {
        //   obj = [];
        //   $('#order-data .order-history tbody#orderContent tr').each(function (index) {
        //     if ($(this).data('id')) {
        //       obj[index] = {};
        //       obj[index]['id'] = parseInt($(this).data('id'));
        //       obj[index]['variant'] = parseInt($(this).data('variant'));
        //       obj[index]['title'] = $(this).find('.titleProd').text();
        //       obj[index]['name'] = $(this).find('.titleProd').text();
        //       obj[index]['property'] = $(this).find('.property').html();
        //       obj[index]['price'] = admin.numberDeFormat($(this).find('.price input').val());
        //       obj[index]['fulPrice'] = $(this).find('.fullPrice input').val();
        //       obj[index]['code'] = $(this).find('.code').text();
        //       obj[index]['weight'] = $(this).find('.weight').text();
        //       obj[index]['currency_iso'] = $('#add-order-wrapper .currSpan [name=userCurrency]').val();
        //       obj[index]['count'] = $(this).find('input.count').val();
        //       obj[index]['info'] = $(".user-info-order").text();
        //       obj[index]['url'] = $(this).find(".href-to-prod").data('url');
        //       obj[index]['discount'] = $('.discount span:first').text();
        //     }

        //   });

        //   return obj;
        // },
        /**
         * Сохранение изменений в модальном окне заказа.
         * Используется и для сохранения редактированных данных и для сохранения нового заказа.
         * id - идентификатор продукта, может отсутствовать если производится добавление нового заказа.
         */
        saveOrder: function(id, container, number, closeModal) {
           closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
           var orderContent = order.orderItems;
            if (!order.checkRulesForm()) {
                return false;
            }

            $('#add-order-wrapper input[type=text], #add-order-wrapper input[type=tel], #add-order-wrapper textarea[name=comment]').each(function() {
                $(this).val(admin.htmlspecialchars($(this).val()));
            });

            var yur = $('#customer').val() == 'yur' ? true : false;
            if ($('#add-order-wrapper #isPaid').prop('checked') == true) {
                var isPaid = 1;
            } else {
                var isPaid = 0;
            }

            if ($('#add-order-wrapper #paymentAfterConfirmOrder').prop('checked') == true) {
                var paymentAfterConfirm = 1;
            } else {
                var paymentAfterConfirm = 0;
            }
            // Пакет характеристик заказа.
            var packedProperty = {
                mguniqueurl: 'action/saveOrder',
                orderPositionCount: orderContent.length,
                // address: $('input[name=address]').val(),
                date_delivery: $('input[name=date_delivery]').val(),
                delivery_interval: $('.delivery-interval select[name=interval]').val() ? $('.delivery-interval select[name=interval]').val() : $('.itervalInitialVal').data('interval'),
                comment: $('textarea[name=comment]').val(),
                commentExt: $('textarea[name=commentExt]').val(),
                delivery_cost: $('#deliveryCost').val(),
                delivery_id: $('select#delivery :selected').attr('name'),
                id: id,
                number: number,
                // name_buyer: $('input[name=name_buyer]').val(),
                payment_id: $('select#payment :selected').val(),
                phone: admin.htmlspecialchars_decode($('input[name=phone]').val()),
                status_id: $('select[name=status_id] :selected').val(),
                inform_user: $('#mailUser').val(),
                inform_user_text: $('.add-order .js-mailUserText').val(),
                summ: admin.numberDeFormat($('#totalPrice').text()),
                currency_iso: $('#add-order-wrapper .currSpan [name=userCurrency]').val(),
                user_email: admin.htmlspecialchars_decode($('#add-order-wrapper .editor-block input[name=user_email]').attr('data-login-email')),
                contact_email: admin.htmlspecialchars_decode($('input[name=contact_email]').val()),
                nameyur: (yur ? container.find('.yur-list-editor input[name=nameyur]').val() : ''),
                adress: (yur ? container.find('.yur-list-editor input[name=adress]').val() : ''),
                inn: (yur ? container.find('.yur-list-editor input[name=inn]').val() : ''),
                kpp: (yur ? container.find('.yur-list-editor input[name=kpp]').val() : ''),
                ogrn: (yur ? container.find('.yur-list-editor input[name=ogrn]').val() : ''),
                bank: (yur ? container.find('.yur-list-editor input[name=bank]').val() : ''),
                bik: (yur ? container.find('.yur-list-editor input[name=bik]').val() : ''),
                ks: (yur ? container.find('.yur-list-editor input[name=ks]').val() : ''),
                rs: (yur ? container.find('.yur-list-editor input[name=rs]').val() : ''),
                order_content: JSON.stringify(orderContent),
                orderPluginsData: admin.createObjectFromContainer($('#add-order-wrapper #orderPluginsData')),
                paided: isPaid,
                approve_payment:paymentAfterConfirm,
                customFields: admin.createObject('.customField'),
                storage: $('[name=storage]').val(),
            };
            // Если мы редактируем заказ, то интервал доставки сохраняется не из инпута
            if ($('.js-insert-delivery-options .order-edit-visible').css('display') === 'none') {
                packedProperty.delivery_interval = $('.delivery-interval select[name=interval]').val();
            }

            var address_parts = $('#delivery :selected').data('address-parts');
            if (address_parts) {
                var parts = {};
                for (var i = 0; i < address_parts.length; i++) {
                    parts[address_parts[i]] = admin.htmlspecialchars(admin.htmlspecialchars_decode($('.address_part input[name=address_' + address_parts[i] + ']').val()));
                }
                packedProperty.address_parts = JSON.stringify(parts);
            } else {
                packedProperty.address = $('input[name=address]').val();
            }

            var name_parts = $('#delivery :selected').data('name-parts');
            if (name_parts) {
                var parts = {};
                for (var i = 0; i < name_parts.length; i++) {
                    parts[name_parts[i]] = admin.htmlspecialchars(admin.htmlspecialchars_decode($('.name_part input[name=fio_' + name_parts[i] + ']').val()));
                }
                packedProperty.name_parts = JSON.stringify(parts);
            } else {
                packedProperty.name_buyer = $('input[name=name_buyer]').val();
            }
            // отправка данных на сервер для сохранения
            admin.ajaxRequest(packedProperty,
                function(response) {
                    // admin.clearGetParam();
                    $('.count .error_count').remove();
                    if((response.status == 'error')&&(response.msg == "error product count")){
                      var prodErrorArray = JSON.parse(response.data);
                      for(var i=0; i<prodErrorArray.length; i++){
                        var prodId = prodErrorArray[i]['code'];
                        $('[data-code="'+prodId+'"] .count input').addClass('error-input');
                        $('[data-code="'+prodId+'"] .count input').parent().append(function(){
                          return '<span class = "error_count" data_prod_id="'+prodId+'"></br>Нет в таком количестве</span>';
                        });
                      }
                    }
                    else if((response.status == 'error')&&(response.msg == "error product count storage")){
                      var prodErrorArray = JSON.parse(response.data);
                      var rows = $('#orderContent').find('tr');
                      for(var i=0; i<prodErrorArray.length; i++){
                        var storage = $(rows[Number(prodErrorArray[i]['id'])+1]).find('.storageArray[data-storage="'+prodErrorArray[i]['storage']+'"]').addClass('error-storage');
                      }
                      admin.indication(response.status, "Товара нет в нужном количестве на складах!");
                    }
                    else if((response.status == 'error')&&(response.msg == "Add new order")){
                      var prodErrorArray = JSON.parse(response.data);
                      for(var i=0; i<prodErrorArray.length; i++){
                        var selector = "#orderContent [data-id="+prodErrorArray[i]+"] .show_storage_edit";
                        $(selector).addClass('error-storage');
                      }
                      admin.indication(response.status, "Товара нет в нужном количестве!");
                    }
                    else{
                        order.indicatorCount(response.data.count);
                        if (closeModal) {
                          admin.closeModal('#add-order-wrapper');
                          admin.refreshPanel();
                        }
                        else {
                            $('#add-order-wrapper').attr('data-refresh', 'true');
                        }
                    }
                },
            );
        },
        // меняет индикатор количества новых заказов
        indicatorCount: function(count) {
            if (count == 0) {
                $('.button-list a[rel=orders]').parents('li').find('.message-wrap').hide();
            } else {
                $('.button-list a[rel=orders]').parents('li').find('.message-wrap').show();
                $('.button-list a[rel=orders]').parents('li').find('.message-wrap').text(count);
            }
        },
        /**
         * Удаляет запись из БД сайта и таблицы в текущем разделе
         */
        deleteOrder: function(id) {
            if (confirm(lang.DELETE + '?')) {
                admin.ajaxRequest({
                        mguniqueurl: 'action/deleteOrder',
                        id: id,
                    },
                    function(response) {
                        admin.indication(response.status, response.msg);
                        if (response.status == 'error') return false;
                        order.indicatorCount(response.data.count - 1);
                        $('tr[order_id=' + id + ']').remove();
                        var newCount = ($('.widget-table-title .produc-count strong').text() - 1);
                        if (newCount >= 0) {
                            $('.widget-table-title .produc-count strong').text(newCount);
                        }

                        if ($('.product-table tr').length == 1) {
                            var row = '<tr><td colspan=' + $('.product-table th').length + ' class=\'noneOrders\'>' + lang.ORDER_NONE + '</td></tr>';
                            $('.order-tbody').append(row);
                        }
                        $('.product-count strong').html($('.product-count strong').html() - 1);
                    },
                );
            }
        },

        /**
         * Редактирует заказ
         * @param {type} id
         * @returns {undefined}
         */
        editOrder: function(id) {
            //Деактивируем окно и показываем прелоадер пока не получим заказ
            order.deactivateSaveButton();
            $('#add-order-wrapper .order-wrapper .widget-body').hide();
            $('#add-order-wrapper .order-wrapper .widget-footer').hide();
            $('#add-order-wrapper .preloader').show();

            admin.ajaxRequest({
                    mguniqueurl: 'action/getOrderData',
                    id: id,
                },
                order.fillFields(),
                $('#add-order-wrapper'),
            );
        },
        newOrder: function(id) {
            //Деактивируем окно и показываем прелоадер пока не получим заказ
            order.deactivateSaveButton();
            $('#add-order-wrapper .order-wrapper .widget-body').hide();
            $('#add-order-wrapper .order-wrapper .widget-footer').hide();
            $('#add-order-wrapper .preloader').show();

            order.orderItems = [];
            admin.ajaxRequest({
                    mguniqueurl: 'action/getOrderData',
                    id: null,
                },
                order.fillFields('newOrder'),
                $('#add-order-wrapper'),
            );
        },

        /**
         * Заполняет поля модального окна данными.
         */
        fillFields: function(type) {
            return function(response) {
              order.storage_count_array = [];
                // для работы ссылки в заказе на скачивание CSV
                if (response.data.order != undefined) {
                    csvLink = $('a.csv-button').attr('href');
                    if (csvLink != undefined) {
                        csvLink = csvLink.split('=');
                        newCsvLink = [];
                        for (i = 0; i < csvLink.length; i++) {
                            if (csvLink[i] == '1&id') {
                                newCsvLink.push(csvLink[i]);
                                break;
                            }
                            if (csvLink[i] != '') newCsvLink.push(csvLink[i]);
                        }
                        newCsvLink.push(response.data.order.id);
                        $('a.csv-button').attr('href', newCsvLink.join('='));
                    }
                }

                if (type == 'newOrder') {
                    $('#add-order-wrapper .currSpan').show();
                    order.initialStatus = -1;
                } else {
                    $('#add-order-wrapper .currSpan').hide();
                    order.initialStatus = parseInt(response.data.order.status_id);
                }

                order.lastAddProdTab = 'descr';
                order.wholesalesEmail = '';
                order.wholesalesCount = '';
                order.wholesalesGroup = '';
                $('#add-order-wrapper #usePlugins').prop('checked', true);

                $('.order-edit-display').hide();
                $('.order-edit-visible').show();

                $('#orderStatus').removeClass('edit-layout');
                /* заполнение выпадающих списков */
                $('#add-order-wrapper .save-button').attr('id', response.data.order.id).attr('data-number', response.data.order.number);
                $('#add-order-wrapper .print-button').data('id', response.data.order.id);
                $('#add-order-wrapper .resend-email').prop('disabled', false);
                $('#add-order-wrapper .resend-email').data('id', response.data.order.id);
                $('#add-order-wrapper .send-email-order-pdf').prop('disabled', false);
                $('#add-order-wrapper .send-email-order-pdf').data('id', response.data.order.id);
                $('#add-order-wrapper .get-pdf-button').data('id', response.data.order.id);
                $('#add-order-wrapper .csv-button').data('id', response.data.order.id);
                $('#orderStatus').val(response.data.order.status_id ? response.data.order.status_id : '0');
                $('.mailUser').hide().find('input').val('false').removeAttr('checked');
                $('.js-mailUserText').addClass('');
                $('input[name=inform-user]').removeAttr('checked');
                var deliveryCurrentName = '';
                var deliveryDatePossible;
                //список способов доставки
                var deliveryList = '<select id="delivery">';
                var selected = '';
                if (typeof (response.data.deliveryArray) != 'undefined') {
                    $.each(response.data.deliveryArray, function(i, delivery) {
                        selected = '';

                        if (delivery.activity == 1) {
                            if (delivery.id == response.data.order.delivery_id) {
                                deliveryCurrentName = delivery.name;
                                deliveryDatePossible = delivery.date;
                                selected = 'selected';
                            }
                            deliveryList += '<option value="' + delivery.cost + '" ' +
                                'data-interval=' + '\'' + delivery.interval + '\' ' +
                                'data-address-parts=' + '\'' + delivery.address_parts + '\' ' +
                                'data-name-parts=' + '\'' + delivery.name_parts + '\' ' +
                                'data-free="' + delivery.free + '" ' +
                                'data-plugin="' + delivery.plugin + '" ' +
                                'data-date="' + delivery.date + '" ' +
                                'name="' + delivery.id + '" ' + selected +
                                '>' + delivery.name + '</option>';
                        }
                    });
                }

                deliveryList += '</select>';

                var paymentCurrentName = '';
                //список способов оплаты
                var paymentList = '<select id="payment">';
                $.each(response.data.paymentArray, function(i, payment) {
                    if (payment.activity != 0 && payment.id != undefined) {
                        selected = '';
                        if (payment.id == response.data.order.payment_id) {
                            paymentCurrentName = payment.name;
                            selected = 'selected';
                        }

                        paymentList += '<option value="' + payment.id + '" ' + selected + '>' + payment.name + '</option>';
                    }
                });
                paymentList += '</select>';
                var coupon = '';
                var info = '';
                var orderContentTable = '';
                var popupContent = '';
                var discounts = '';
                if (response.data.order.currency_iso) {
                    curr = response.data.order.currency_iso;
                } else {
                    curr = admin.CURRENCY_ISO;
                }
                $('#add-order-wrapper .currSpan [name=userCurrency]').val(curr);
                if (response.data.order.order_content) {
                    order.orderItems = [];
                    var storageUse = '';
                    $.each(response.data.order.order_content, function(i, element) {
                        for (let key in element.storage_id) {
                          let store = (element.storage_id[key]).split('_');
                          if(storageUse.indexOf(order.storageSetting[store[0]]) == -1){
                            storageUse += order.storageSetting[store[0]] + '<br>';
                          }
                        }

                        coupon = element.coupon ? element.coupon : '';
                        info = element.info ? element.info : '';
                        discounts = element.discSyst ? element.discSyst : '';
                        // если товар находится в корне каталога, то приписываем категорию catalog
                        if (element.url) {

                            var sections = admin.trim(element.url, '/').split('/');

                            if (sections.length == 1) {
                                element.url = 'catalog' + element.url;
                            }
                        }

                        var position = {
                            order_id: response.data.order.id,
                            id: element.id,
                            title: '<a href="' + mgBaseDir + '/' + element.url + '" data-url="' + element.url + '" class="href-to-prod"><span class="titleProd">' + admin.htmlspecialchars(element.name) + '</span></a>' + '<span class="property">' + element.property + '</span>',
                            prop: element.property,
                            code: element.code,
                            weight: element.weight,
                            price: element.price,
                            count: element.count,
                            summ: (element.count * (element.price * 100)) / 100,
                            image_url: element.image_url,
                            fulPrice: element.fulPrice,
                            discount: element.discount,
                            maxCount: element.maxCount,
                            variant: element.variant_id ? element.variant_id:element.variant,
                            notSet: element.notSet,
                            currency_iso: curr,
                            category_unit: element.category_unit,
                            storage_array: element.storage_id,
                            storage_count_array: element.storageCountArr
                        };

                        var orderItem = {
                            id: position.id,
                            variant_id: position.variant ? position.variant : 0,
                            name: element.name,
                            url: element.url,
                            code: position.code,
                            price: position.price,
                            count: position.count,
                            property: position.prop,
                            discount: position.discount,
                            fulPrice: position.fulPrice,
                            weight: position.weight,
                            currency_iso: position.currency_iso,
                            unit: position.category_unit,
                            storage_id:position.storage_array,
                        };
                        order.orderItems.push(orderItem);
                        orderContentTable += order.createPositionRow(position, 'view', i);
                        popupContent += '';

                    });
                }

                if (info == '') {
                    info = response.data.order.user_comment;
                }

                var data = {
                    paymentList: paymentList,
                    deliveryList: deliveryList,
                    coupon: coupon,
                    info: info,
                    discounts: discounts,
                    orderContentTable: orderContentTable,
                    paymentCurrentName: paymentCurrentName,
                    deliveryCurrentName: deliveryCurrentName,
                    deliveryDatePossible: deliveryDatePossible,
                    storage: response.data.order.storage,
                    popupContent: popupContent,
                    storageUse: storageUse
                };

                $('.order-history').html(order.drawOrder(response, data));
                order.renumericPosition();

                if (response.data.order.paided == 1) {
                    $('#add-order-wrapper #isPaid').prop('checked', true);
                } else {
                    $('#add-order-wrapper #isPaid').prop('checked', false);
                }

                const payLinkContiner = $('#add-order-wrapper .payLinkRow');
                payLinkContiner.hide();
                if (response.data.order.pay_hash) {
                    const payHash = response.data.order.pay_hash;
                    if (payHash.length < 32) {
                        const payLink = mgBaseDir + '/order?p=' + payHash;
                        $('#add-order-wrapper #payLinkText').text(payLink);
                        $('#add-order-wrapper #payLink').attr('href', payLink);
                        $('#add-order-wrapper #payLink').prop('href', payLink);
                        payLinkContiner.show();
                    }
                }

                if (response.data.order.approve_payment == 1) {
                    $('#add-order-wrapper #paymentAfterConfirmOrder').prop('checked', true);
                } else {
                    $('#add-order-wrapper #paymentAfterConfirmOrder').prop('checked', false);
                }

                $('#add-order-wrapper input[name=user_email]').autocomplete({
                    appendTo: '.autocomplete-holder',
                    source: function(request, response) {
                        var term = request.term;
                        $.ajax({
                            url: mgBaseDir + '/ajax',
                            type: 'POST',
                            data: {
                                mguniqueurl: 'action/getBuyerEmail',
                                email: term,
                            },
                            dataType: 'json',
                            cache: false,
                            // обработка успешного выполнения запроса
                            success: function(resp) {
                                response(resp.data);
                            },
                        });
                    },
                    select: function(event, ui) {
                        $.ajax({
                            url: mgBaseDir + '/ajax',
                            type: 'POST',
                            data: {
                                mguniqueurl: 'action/getInfoBuyerEmail',
                                email: ui.item.value,
                            },
                            dataType: 'json',
                            cache: false,
                            // обработка успешного выполнения запроса
                            success: function(response) {
                                var user = response.data;
                                if(!!user.contact_email){
                                    $('#add-order-wrapper .editor-block input[name=contact_email]').val(user.contact_email);
                                } else {
                                    $('#add-order-wrapper .editor-block input[name=contact_email]').val(user.email);
                                }
                                $('#add-order-wrapper .editor-block input[name=user_email]').attr('data-login-email',user.login_email);
                                $('#add-order-wrapper .editor-block input[name=name_buyer]').val(user.name + ' ' + (user.sname ? user.sname : ''));
                                $('#add-order-wrapper .editor-block input[name=phone]').val(user.phone);
                                $('#add-order-wrapper .editor-block input[name=address]').val(user.address);
                                $('#add-order-wrapper .editor-block input[name=address_index]').val(user.address_index);
                                $('#add-order-wrapper .editor-block input[name=address_country]').val(user.address_country);
                                $('#add-order-wrapper .editor-block input[name=address_region]').val(user.address_region);
                                $('#add-order-wrapper .editor-block input[name=address_city]').val(user.address_city);
                                $('#add-order-wrapper .editor-block input[name=address_street]').val(user.address_street);
                                $('#add-order-wrapper .editor-block input[name=address_house]').val(user.address_house);
                                $('#add-order-wrapper .editor-block input[name=address_flat]').val(user.address_flat);

                                if (user.inn) {
                                    $('.js-yur-list-toggle').show();
                                    $('#add-order-wrapper .editor-block select[name=customer]').val('yur');
                                    $('#add-order-wrapper .editor-block input[name=nameyur]').val(user.nameyur);
                                    $('#add-order-wrapper .editor-block input[name=adress]').val(user.adress);
                                    $('#add-order-wrapper .editor-block input[name=kpp]').val(user.kpp);
                                    $('#add-order-wrapper .editor-block input[name=inn]').val(user.inn);
                                    $('#add-order-wrapper .editor-block input[name=bank]').val(user.bank);
                                    $('#add-order-wrapper .editor-block input[name=bik]').val(user.bik);
                                    $('#add-order-wrapper .editor-block input[name=ks]').val(user.ks);
                                    $('#add-order-wrapper .editor-block input[name=rs]').val(user.rs);
                                } else {
                                    $('.js-yur-list-toggle').hide();
                                    $('#add-order-wrapper .editor-block select[name=customer]').val('fiz');
                                }
                                order.calculateOrder();
                            },
                        });
                    },
                    minLength: 2,
                });
                $('.ui-autocomplete').css('z-index', '1000');

                $('.js-open-adding-to-order').hide();

                // Если открыта модалка добавления нового заказа.
                if (type == 'newOrder') {
                    $('.order-history input').val('');
                    order.enableEditor();
                    $('#delivery option:first-of-type').prop('selected', 'selected');
                    order.calculateOrder();
                    $('#add-order-wrapper .save-button').attr('id', '');
                    $('#add-order-wrapper .save-button').attr('data-number', '');
                    $('.delivery-date').hide();
                    $('#add-order-wrapper .order-edit-display #delivery').trigger('change');
                    $('.js-open-adding-to-order').show();
                } else {
                    $('.order-payment-sum [type="checkbox"]').attr('disabled', true);
                }

                admin.blockTime = 0;
                admin.setAndCheckBlock('#add-order-wrapper', 'order');
                admin.reloadComboBoxes();

                $('.js-insert-total-price').text(admin.numberFormat(response.data.order.summ * 1 + response.data.order.delivery_cost * 1));
                $('.js-insert-total-price-currency').text(order.currencyShort[curr]);

                //Активируем окно и убираем прелоадер пока не получим заказ
                order.activateSaveButton();
                $('#add-order-wrapper .order-wrapper .widget-body').show();
                $('#add-order-wrapper .order-wrapper .widget-footer').show();
                $('#add-order-wrapper .preloader').hide();
            };
        },

        /**
         * Создает верстку для модального окна, редактирования и добавления заказа
         * @param {type} id
         * @returns {undefined}
         */
        drawOrder: function(response, data) {
            var dateDelivery = '';
            if (response.data.order.currency_iso) {
                var currency = response.data.order.currency_iso;
                $('#add-order-wrapper .currSpan [name=userCurrency]').val(currency);
                currency = order.currencyShort[currency];
            } else {
                var currency = admin.CURRENCY;
                $('#add-order-wrapper .currSpan [name=userCurrency]').val(admin.CURRENCY_ISO);
            }

            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajax',
                data: {
                    mguniqueurl: 'action/setAdminCurrency',
                    userCustomCurrency: $('#add-order-wrapper .currSpan [name=userCurrency]').val(),
                },
                cache: false,
                async: false,
                success: function(response) {
                },
            });
            var storageAdress = 'Не указан';
            if(order.storageSetting != false){
              $.each(response.data.deliveryArray, function(i, element){
                if(element['id'] == response.data.order.delivery_id && element['show_storages'] == 1){
                  if(response.data.order.storage_adress && response.data.order.storage){
                    storageAdress = response.data.order.storage_adress +', '+response.data.order.storage ;
                  }
                }
              });
            }
            var weight = 0;
            $.each(response.data.order.order_content, function(i, element) {
                weight += element.count * element.weight;
            });
            weight = admin.toFloat(weight);

            order.address_parts_val = false;
            if (response.data.order.address_parts) {
                order.address_parts_val = response.data.order.address_parts;
            }
            if (response.data.order.address_imploded && response.data.order.address_imploded == response.data.order.address) {
                response.data.order.address = response.data.order.address_imploded;
            }
            order.name_parts_val = false;
            if (response.data.order.name_parts) {
                order.name_parts_val = response.data.order.name_parts;
            }
            if (response.data.order.name_imploded) {
                response.data.order.name_buyer = response.data.order.name_imploded;
            }

            var disabled = '';

            var tooltipForDisabled = ' tooltip="Для редактирования заказа нажмите кнопку «Редактировать» в левом верхнем углу."';

            // deliveryDatePossible - 1 или 0 - возможность добавления даты доставки в заказ, значение выбранного метода доставки
            if (data.deliveryDatePossible == 1) {
                dateDelivery = '<div class="row sett-line order-edit-visible"><div class="row">\
                          <div class="large-6 small-12 columns"><label>' + lang.DELIVERY_DATE + ':</label></div>\
                          <div class="large-6 small-12 columns"><strong ' + tooltipForDisabled + '"><input disabled type="text" value="' + (response.data.order.date_delivery ? response.data.order.date_delivery : lang.NO_DATE) + '"></strong></div>\
                        </div></div>\ ';
            }
            if (response.data.order.delivery_interval) {
                dateDelivery += '<div class="row sett-line order-edit-visible"><div class="row">\
                          <div class="large-6 small-12 columns"><label>' + lang.DELIVERY_INTERVAL_ORDER + ':</label></div>\
                          <div class="large-6 small-12 columns"><span ' + tooltipForDisabled + '"><input type="text" disabled class="itervalInitialVal" value="' + admin.htmlspecialchars(admin.htmlspecialchars_decode(response.data.order.delivery_interval)) + '"></span></div>\
                        </div></div>\ ';
            }
            var orderEmptyVisible = '';
            if (data.orderContentTable) {
                orderEmptyVisible = ' style="display:none"';
            }

            var orderDeliveryNotAvalible = '';
            if(order.storageSetting != false){

            }

            var popupSklad = '<div class="custom-popup weightUnit-block custom-popup--center" id="storage-popup" style="top: 317px; left: 630.5px; width:345px; display:none;">'+
                                '<div class="row">'+
                               ' <table class = "small-table">'+
                                   '<thead>'+
                                    '<tr>'+
                                   ' <th>'+lang.ORDER_STORAGE_ROW+'</th>'+
                                    '<th>'+lang.ORDER_STORAGE_COUNT+'</th>'+
                                    '<th>'+lang.ORDER_STORAGE_DELETE+'</th>'+
                                    '</tr>'+
                                   '</thead>'+
                                    '<tbody id="popupContent">'+ data.popupContent +
                                    '</tbody>'+
                                 '</table>'+
                                '</div>'+
                                 '<div class="row">'+
                                 '<div class="large-12 columns" style="margin-top: 15px;">'+
                                 '<button class="link add-storageUnit" title="'+lang.ORDER_STORAGE_ADD_STORAGE+'" >'+
                                    '<span>'+lang.ORDER_STORAGE_ADD_STORAGE+'</span>'+
                                  '</button>'+
                                  '</div>'+
                                ' </div>'+
                                '<div class="row">'+
                                    '<div class="large-12 columns" style="margin-top: 15px;">'+
                                        '<button class="button success apply-storageUnit fl-right" title="'+lang.ORDER_STORAGE_APPLY_STORAGE+'">'+
                                           ' <i class="fa fa-check"></i> '+lang.ORDER_STORAGE_APPLY_STORAGE+' </button>'+
                                        '<button class="link cancel-storageUnit" title="'+lang.RELATED_3+'">'+
                                            '<span>'+lang.RELATED_3+'</span>'+
                                        '</button>'+
                                     '</div>'+
                                ' </div>'+
                                   ' </div>'+
                               ' </div>'+
                           ' </div>';

            // Вёрстка таблицы списка товаров
            var orderTableHtml = popupSklad + '\
          <div class="status-table__wrap shadow-block" style="overflow:auto;">' +
                '<h3 class="status-table__title">Состав заказа</h3>'   +
              '<table class="status-table main-table small-table">\
                  <thead>\
                      <tr>\
                          <th>№</th>\
                          <th></th>\
                          <th class="prod-name">' + lang.ORDER_PROD + '</th>\
                          <th>' + lang.ORDER_CODE + '</th>\
                          <th>' + lang.WEIGHT + '</th>\
                          <th class="prod-price">' + lang.ORDER_PRICE + '</th>\
                          <th class="prod-price">' + lang.ORDER_DISCOUNT + '</th>\
                          <th class="prod-price">' + lang.ORDER_DISCOUNT_PRICE + '</th>\
                          <th>' + lang.ORDER_COUNT + '</th>\
                          <th>Стоимость</th>' + '' +
                          ($('#enabledStorage').text()==1 ? '<th>'+ lang.ORDER_STORAGE_ROW +'</th>': '') +

                          '<th class="prod-remove"></th>\
                      </tr>\
                 </thead>\
                 <tbody id="orderContent">\
                      <tr class="emptyOrder"' + orderEmptyVisible + '>' +
                '<td colspan="11" class="text-center">В заказе нет товаров</td>' +
                '</tr>' + (data.orderContentTable ? data.orderContentTable : '') + '\
                 </tbody>\
              </table>\
          </div>\
      ';

            // Вёрстка поля «Вес заказа»
            var weightBlock = '\
          <div class="row sett-line" ' + ((weight > 0) ? '' : 'style="display:none;"') + '>\
              <div class="row">\
                  <div class="small-12 medium-6 columns">\
                      <label class="middle with-help">' + lang.ORDER_WEIGHT + ':</label>\
                  </div>\
                  <div class="small-12 medium-6 columns">\
                      <strong flow="up" tooltip="Вес заказа - это сумма веса всех товаров в заказе.\nЭто поле нельзя отредактировать вручную."><input type="text" readonly class="order-weight" value="' + weight + ' ' + lang.KG + '."></strong>\
                  </div>\
              </div>\
          </div>';
            var user_login_email = admin.htmlspecialchars(response.data.order.user_email);
            if(!!admin.htmlspecialchars(response.data.order.login_phone)){
                user_login_email = admin.htmlspecialchars(response.data.order.login_phone);
            }
            /* Вёрстка данных покупателя */
            var orderPersonalHtml = '\
          <div class="order-fieldset order-payment-sum__item order-other-info order-edit-visible shadow-block">\
              <h2 class="order-fieldset__h2">Данные покупателя</h2>\
              <div class="order-fieldset__inner">';
              if(!!admin.htmlspecialchars(response.data.order.user_email)){
              orderPersonalHtml += '\
                  <div class="row sett-line">\
                      <div class="row">\
                          <div class="large-6 small-12 columns"><label>' + lang.ORDER_LOGIN_EMAIL + ':</label></div>\
                          <div class="large-6 small-12 columns"><strong>' + user_login_email + '</strong></div>\
                      </div>\
                  </div>';
              }
              if(!!admin.htmlspecialchars(response.data.order.contact_email)){
              orderPersonalHtml += '\
                  <div class="row sett-line">\
                    <div class="row">\
                      <div class="large-6 small-12 columns"><label>' + lang.ORDER_EMAIL + ':</label></div>\
                      <div class="large-6 small-12 columns"><strong><a href="mailto:' + admin.htmlspecialchars(response.data.order.contact_email) + '">' + admin.htmlspecialchars(response.data.order.contact_email) + '</a></strong></div>\
                    </div>\
                  </div>';
              }
              if(!!admin.htmlspecialchars(response.data.order.phone)){
              orderPersonalHtml += '\
                  <div class="row sett-line">\
                      <div class="row">\
                          <div class="large-6 small-12 columns"><label>' + lang.ORDER_PHONE + ':</label></div>\
                          <div class="large-6 small-12 columns"><strong><a href="tel:' + admin.htmlspecialchars(response.data.order.phone) + '">' + admin.htmlspecialchars(response.data.order.phone) + '</a></strong></div>\
                      </div>\
                  </div>';
              }
              orderPersonalHtml += '\
                  <div class="row sett-line">\
                      <div class="row">\
                          <div class="large-6 small-12 columns"><label>' + lang.ORDER_BUYER + ':</label></div>\
                          <div class="large-6 small-12 columns"><strong ' + tooltipForDisabled + '"><input readonly type="text" value="' + admin.htmlspecialchars(response.data.order.name_buyer) + '"></strong></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="customer">' + lang.EDIT_ORDER_1 + ':</label></div>\
                          <div class="large-6 small-12 columns">\
                              <span ' + tooltipForDisabled + '>\
                                  <select disabled id="customer" name="customer">\
                                      <option value="fiz">' + lang.EDIT_ORDER_2 + '</option>\
                                      <option value="yur" ' + (response.data.order.yur_info.inn ? 'selected' : '') + '>' + lang.EDIT_ORDER_3 + '</option>\
                                  </select>\
                              </span>\
                          </div>\
                      </div>\
                  </div>\
              </div>\
          </div>\
      ';

            /* Вёрстка редактирования данных покупателя */
            var personalHtmlEdit = '\
          <div class="order-fieldset order-payment-sum__item order-other-info order-edit-display editor-block shadow-block">\
              <h2 class="order-fieldset__h2">Данные покупателя\
                <a role="button" href="javascript:void(0)" class="link fl-left click-to-be-changed" style="float: right; line-height: inherit;font-weight: normal; font-size: 14px !important; color: #ccc;">\
                <span tooltip="Подставляет данные из личного кабинета пользователя" flow="up">' + lang.ORDER_FIND_LOGIN + '\
                </span></a>\
              </h2>\
              <div class="order-fieldset__inner">\
                  <div class="row sett-line to-be-changed" style="display: none">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns">\
                              <label for="user_email">' + lang.ORDER_LOGIN_EMAIL + '</label>\
                          </div>\
                          <div class="large-6 small-12 columns">\
                              <span class="autocomplete-holder">\
                                  <input type="text" id="user_email" placeholder="Введите ' + lang.USER_LOGIN_EMAIL +
                                  ' или ' + lang.USER_LOGIN_PHONE +
                                   '" name="user_email" value="' + admin.htmlspecialchars(response.data.order.user_email) + '">\
                              </span>\
                          </div>\
                      </div>\
                  </div>\
                  <div class="row sett-line">\
                    <div class="row"> \
                      <div class="large-6 small-12 columns">\
                          <label for="contact_email">' + lang.ORDER_EMAIL + '</label>\
                      </div>\
                      <div class="large-6 small-12 columns">\
                          <span class="autocomplete-holder">\
                              <input type="text" id="contact_email" name="contact_email" value="' + admin.htmlspecialchars(response.data.order.contact_email) + '">\
                          </span>\
                      </div>\
                    </div>\
                 </div>\
                  <div class="row sett-line">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="order-phone">' + lang.ORDER_PHONE + '</label></div>\
                          <div class="large-6 small-12 columns"><input type="tel" id="order-phone" name="phone" value="' + admin.htmlspecialchars(response.data.order.phone) + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-name-toggle">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="name_buyer">' + lang.ORDER_BUYER + ':</label></div>\
                          <div class="large-6 small-12 columns"><input type="text" id="name_buyer" name="name_buyer" value="' + admin.htmlspecialchars(response.data.order.name_buyer) + '" ></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-name-part-toggle name_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="fio_sname">' + lang.USER_SNAME + ':</label></div>\
                          <div class="large-6 small-12 columns"><input type="text" id="fio_sname" name="fio_sname" value="' + (order.name_parts_val.sname ? order.name_parts_val.sname : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-name-part-toggle name_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="fio_name">' + lang.USER_NAME + ':</label></div>\
                          <div class="large-6 small-12 columns"><input type="text" id="fio_name" name="fio_name" value="' + (order.name_parts_val.name ? order.name_parts_val.name : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-name-part-toggle name_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="fio_pname">' + lang.USER_PNAME + ':</label></div>\
                          <div class="large-6 small-12 columns"><input type="text" id="fio_pname" name="fio_pname" value="' + (order.name_parts_val.pname ? order.name_parts_val.pname : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="customer">' + lang.EDIT_ORDER_1 + ':</label></div>\
                          <div class="large-6 small-12 columns">\
                              <select id="customer" name="customer">\
                                  <option value="fiz">' + lang.EDIT_ORDER_2 + '</option>\
                                  <option value="yur" ' + (response.data.order.yur_info.inn ? 'selected' : '') + '>' + lang.EDIT_ORDER_3 + '</option>\
                              </select>\
                          </div>\
                      </div>\
                  </div>\
              </div>\
          </div>';


            // Вёрстка данных юрлица
                   var orderYurHtml = '\
                   <div class="order-fieldset order-payment-sum__item order-edit-visible order-other-info editor-block shadow-block">\
                        <h2 class="order-fieldset__h2">Реквизиты юридического лица</h2>\
                        <div class="order-fieldset__inner">\
                            <div class="row sett-line">\
                                <div class="row">\
                                     <div class="large-5 columns">\
                                       <label>' + lang.OREDER_LOCALE_9 + ':</label>\
                                     </div>\
                                     <div class="large-7 small-12 columns">\
                                       <strong ' + tooltipForDisabled + '"><input readonly type="text" value="' + admin.htmlspecialchars((response.data.order.yur_info.nameyur ? admin.htmlspecialchars_decode(response.data.order.yur_info.nameyur) : '')) + '"></strong>\
                                     </div>\
                                </div>\
                            </div>\
                            <div class="row sett-line">\
                                <div class="row">\
                                    <div class="large-5 columns">\
                                       <label>' + lang.OREDER_LOCALE_15 + ':</label>\
                                    </div>\
                                     <div class="large-7 small-12 columns">\
                                       <strong ' + tooltipForDisabled + '><input type="text" readonly value="' + admin.htmlspecialchars((response.data.order.yur_info.adress ? admin.htmlspecialchars_decode(response.data.order.yur_info.adress) : '')) + '"></strong>\
                                     </div>\
                                </div>\
                            </div>\
                            <div class="row sett-line">\
                                <div class="row">\
                                   <div class="large-5 columns">\
                                      <label>' + lang.OREDER_LOCALE_16 + ':</label>\
                                   </div>\
                                   <div class="large-7 small-12 columns">\
                                     <strong ' + tooltipForDisabled + '><input readonly type="text" value="' + admin.htmlspecialchars((response.data.order.yur_info.inn ? admin.htmlspecialchars_decode(response.data.order.yur_info.inn) : '')) + '"></strong>\
                                   </div>\
                                </div>\
                            </div>\
                            <div class="row sett-line">\
                                <div class="row">\
                                    <div class="large-5 columns">\
                                        <label>' + lang.OREDER_LOCALE_17 + ':</label>\
                                    </div>\
                                    <div class="large-7 small-12 columns">\
                                         <strong ' + tooltipForDisabled + '><input readonly type="text" value="' + admin.htmlspecialchars((response.data.order.yur_info.kpp ? admin.htmlspecialchars_decode(response.data.order.yur_info.kpp) : '')) + '"></strong>\
                                    </div>\
                                </div>\
                            </div>\
                            <div class="row sett-line">\
                                <div class="row">\
                                    <div class="large-5 columns">\
                                        <label>' + lang.OREDER_LOCALE_18 + ':</label>\
                                    </div>\
                                    <div class="large-7 small-12 columns">\
                                        <strong ' + tooltipForDisabled + '><input type="text" readonly value="' + admin.htmlspecialchars((response.data.order.yur_info.bank ? admin.htmlspecialchars_decode(response.data.order.yur_info.bank) : '')) + '"></strong>\
                                    </div>\
                                </div>\
                            </div>\
                            <div class="row sett-line">\
                                <div class="row">\
                                    <div class="large-5 columns">\
                                         <label>' + lang.OREDER_LOCALE_19 + ':</label>\
                                    </div>\
                                    <div class="large-7 small-12 columns">\
                                         <strong ' + tooltipForDisabled + '><input readonly type="text" value="' + admin.htmlspecialchars((response.data.order.yur_info.bik ? admin.htmlspecialchars_decode(response.data.order.yur_info.bik) : '')) + '"></strong>\
                                    </div>\
                                 </div>\
                            </div>\
                            <div class="row sett-line">\
                                <div class="row">\
                                    <div class="large-5 columns">\
                                        <label>' + lang.OREDER_LOCALE_20 + ':</label>\
                                    </div>\
                                    <div class="large-7 small-12 columns">\
                                         <strong ' + tooltipForDisabled + '><input readonly type="text" value="' + admin.htmlspecialchars((response.data.order.yur_info.ks ? admin.htmlspecialchars_decode(response.data.order.yur_info.ks) : '')) + '"></strong>\
                                    </div>\
                                 </div>\
                            </div>\
                            <div class="row sett-line">\
                                <div class="row">\
                                    <div class="large-5 columns">\
                                         <label>' + lang.OREDER_LOCALE_21 + ':</label>\
                                    </div>\
                                    <div class="large-7 small-12 columns">\
                                         <strong ' + tooltipForDisabled + '><input readonly type="text" value="' + admin.htmlspecialchars((response.data.order.yur_info.rs ? admin.htmlspecialchars_decode(response.data.order.yur_info.rs) : '')) + '"></strong>\
                                    </div>\
                                </div>\
                            </div>\
                        </div>\
                   </div>\
               ';

            // Вёрстка редактирования данных юрлица
            var yurHtmlEdit = '\
          <div class="order-fieldset order-payment-sum__item order-edit-display order-other-info js-yur-list-toggle editor-block shadow-block">\
              <h2 class="order-fieldset__h2">Реквизиты юридического лица</h2>\
              <div class="order-fieldset__inner yur-list-editor">\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_9 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="nameyur" value="' + admin.htmlspecialchars((response.data.order.yur_info.nameyur ? admin.htmlspecialchars_decode(response.data.order.yur_info.nameyur) : '')) + '">\
                        </div>\
                    </div>\
                </div>\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_15 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="adress" value="' + admin.htmlspecialchars((response.data.order.yur_info.adress ? admin.htmlspecialchars_decode(response.data.order.yur_info.adress) : '')) + '" style="padding-right:25px;">\
                        </div>\
                    </div>\
                </div>\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_16 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="inn" value="' + admin.htmlspecialchars((response.data.order.yur_info.inn ? admin.htmlspecialchars_decode(response.data.order.yur_info.inn) : '')) + '">\
                        </div>\
                    </div>\
                </div>\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_17 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="kpp" value="' + admin.htmlspecialchars((response.data.order.yur_info.kpp ? admin.htmlspecialchars_decode(response.data.order.yur_info.kpp) : '')) + '">\
                        </div>\
                    </div>\
                </div>\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_18 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="bank" value="' + admin.htmlspecialchars((response.data.order.yur_info.bank ? admin.htmlspecialchars_decode(response.data.order.yur_info.bank) : '')) + '">\
                        </div>\
                    </div>\
                </div>\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_19 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="bik" value="' + admin.htmlspecialchars((response.data.order.yur_info.bik ? admin.htmlspecialchars_decode(response.data.order.yur_info.bik) : '')) + '">\
                        </div>\
                    </div>\
                </div>\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_20 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="ks" value="' + admin.htmlspecialchars((response.data.order.yur_info.ks ? admin.htmlspecialchars_decode(response.data.order.yur_info.ks) : '')) + '">\
                        </div>\
                    </div>\
                </div>\
                <div class="row sett-line">\
                    <div class="row">\
                        <div class="large-6 small-12 columns">\
                          <label>' + lang.OREDER_LOCALE_21 + ':</label>\
                        </div>\
                        <div class="large-6 small-12 columns">\
                          <input type="text" name="rs" value="' + admin.htmlspecialchars((response.data.order.yur_info.rs ? admin.htmlspecialchars_decode(response.data.order.yur_info.rs) : '')) + '">\
                        </div>\
                    </div>\
                </div>\
            </div>\
        </div>';

            // Вёрстка блока доставки
            if (!response.data.order.address && response.data.order.storage_adress) {
                response.data.order.address = response.data.order.storage_adress;
            }
            var orderDeliveryHtml = '\
          <div class="order-fieldset order-payment-sum__item shadow-block">\
               <h2 class="order-fieldset__h2">Доставка</h2>\
               <div class="order-fieldset__inner">' +
                weightBlock +
                '\
                   <div class="row sett-line js-insert-delivery-options">\
                       <div class="row">\
                          <div class="small-12 medium-6 columns">\
                            <label class="middle with-help">' + lang.ORDER_DELIVERY + ':</label>\
                          </div>\
                          <div class="small-12 medium-6 columns">\
                            <span class="order-edit-visible"><strong ' + tooltipForDisabled + '><input type="text" readonly value="' + data.deliveryCurrentName + '"></strong></span>\
                            <span class="order-edit-display">' + data.deliveryList + '</span>\
                          </div>\
                        </div>\
                   </div>\
                   <div class="row sett-line">\
                        <div class="row">\
                             <div class="small-12 medium-6 columns">\
                                <label class="middle with-help">' + lang.EDIT_ORDER_6 + ':</label>\
                             </div>\
                             <div class="small-12 medium-6 columns">\
                                <strong ' + tooltipForDisabled + '><span class="order-edit-visible"><input readonly type="text" value="' + response.data.order.delivery_cost + ' ' + currency + '"> </span></strong>\
                                <span class="order-edit-display">' + '<input class="small" style="display:inline-block;" type="text" id="deliveryCost" value="' + response.data.order.delivery_cost + '">' + ' <span class=\'changeCurrency\'>' + currency + '</span></span>\
                             </div>\
                        </div>\
                   </div>'
                + dateDelivery +
                '\
                  <div class="row sett-line order-edit-visible sett-line--order-address">\
                      <div class="row">\
                          <div class="large-6 small-12 columns"><label>' + lang.ORDER_ADDRESS + ':</label></div>\
                          <div class="' + ((response.data.order.address) ? 'large-12 order-other-info__field_wide' : 'large-6') + ' small-12 columns"><strong>' + ((response.data.order.address) ? '<a target="_blank" href="http://maps.yandex.ru/?text=' + encodeURIComponent(response.data.order.address) + '">' + response.data.order.address + ' <span class="map-btn fa fa-map-marker" title="Посмотреть на карте" ></span></a> ' : 'Не указан') + '</strong></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-toggle order-edit-display">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label for="order-address">' + lang.ORDER_ADDRESS + ':</label></div>\
                          <div class="large-6 small-12 columns">\
                               <input type="text" id="order-address" name="address" value="' + (response.data.order.address ? admin.htmlspecialchars(admin.htmlspecialchars_decode(response.data.order.address)) : 'Не указан') + '" >\
                          </div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-part-toggle order-edit-display address_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns"><label>' + lang.ORDER_ADDRESS + ':</label></div>\
                          <div class="large-6 small-12 columns"><input type="text" name="address_index" placeholder="' + lang.ORDER_PH_ADDRESS_INDEX + '" value="' + (order.address_parts_val.index ? order.address_parts_val.index : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-part-toggle order-edit-display address_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns">&nbsp;</div>\
                          <div class="large-6 small-12 columns"><input type="text" name="address_country" placeholder="' + lang.ORDER_PH_ADDRESS_COUNTRY + '" value="' + (order.address_parts_val.country ? order.address_parts_val.country : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-part-toggle order-edit-display address_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns">&nbsp;</div>\
                          <div class="large-6 small-12 columns"><input type="text" name="address_region" placeholder="' + lang.ORDER_PH_ADDRESS_REGION + '" value="' + (order.address_parts_val.region ? order.address_parts_val.region : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-part-toggle order-edit-display address_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns">&nbsp;</div>\
                          <div class="large-6 small-12 columns"><input type="text" name="address_city" placeholder="' + lang.ORDER_PH_ADDRESS_CITY + '" value="' + (order.address_parts_val.city ? order.address_parts_val.city : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-part-toggle order-edit-display address_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns">&nbsp;</div>\
                          <div class="large-6 small-12 columns"><input type="text" name="address_street" placeholder="' + lang.ORDER_PH_ADDRESS_STREET + '" value="' + (order.address_parts_val.street ? order.address_parts_val.street : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-part-toggle order-edit-display address_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns">&nbsp;</div>\
                          <div class="large-6 small-12 columns"><input type="text" name="address_house" placeholder="' + lang.ORDER_PH_ADDRESS_HOUSE + '" value="' + (order.address_parts_val.house ? order.address_parts_val.house : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line js-address-part-toggle order-edit-display address_part">\
                      <div class="row"> \
                          <div class="large-6 small-12 columns">&nbsp;</div>\
                          <div class="large-6 small-12 columns"><input type="text" name="address_flat" placeholder="' + lang.ORDER_PH_ADDRESS_FLAT + '" value="' + (order.address_parts_val.flat ? order.address_parts_val.flat : '') + '"></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line order-edit-display">\
                      <div class="delivery-date row" style="display:none">\
                          <div class="large-6 small-12 columns"><label for="date_delivery">' + lang.DELIVERY_DATE + ':</label></div>\
                          <div class="large-6 small-12 columns"><input autocomplete="off" type="text" id="date_delivery" name="date_delivery" value="' + (response.data.order.date_delivery ? response.data.order.date_delivery : '') + '" ></div>\
                      </div>\
                  </div>\
                  <div class="row sett-line">\
                      <div class="delivery-interval row" style="display:none">\
                          <div class="large-6 small-12 columns"><label for="order-del-interv">' + lang.DELIVERY_INTERVAL_ORDER + ':</label></div>\
                          <div class="large-6 small-12 columns"><select id="order-del-interv" name="interval"></select></div>\
                      </div>\
                  </div>'+
                '<div class="row sett-line order_storages" ' + ($('#enabledStorage').text() == 1 ? '' : 'style="display:none;"') + '>\
                                <div class="row">\
                                  <div class="small-12 medium-6 columns">\
                                    <label class="middle with-help">' + lang.STORAGE + ':</label>\
                                  </div>\
                                   <div class="small-12 medium-6 columns order_storages_list">'+ (typeof data.storageUse == 'undefined'? '':data.storageUse) +
                                  //   <span class="order-edit-display"><span><strong>' + ($('#enabledStorage').text() == 1 ? data.storage : '') + '</strong></span></span>\
                                  //   <span class="order-edit-visible"><span><strong ' + tooltipForDisabled + '">' + ($('#enabledStorage').text() == 1 ? data.storage.replace('<select', '<select disabled') : '') + '</strong></span></span>\
                                   '</div>\
                                 </div>\
                             </div>' +
               '</div>\
          </div>\
      ';
            // Вёрстка блока оплаты
            var orderPaymentHtml = '\
          <div class="order-fieldset order-payment-sum__item shadow-block">\
              <h2 class="order-fieldset__h2">Оплата</h2>\
              <div class="order-fieldset__inner">\
                  <div class="row sett-line">\
                      <div class="row">\
                        <div class="small-12 medium-6 columns ">\
                          <label class="middle with-help">' + lang.ORDER_TOTAL_PRICE + ':</label>\
                        </div>\
                        <div class="small-12 medium-6 columns">\
                          <strong>' + '<span id="totalPrice">' + admin.numberFormat(response.data.order.summ * 1) + '</span>' + ' <span class=\'changeCurrency\'>' + currency + '</span></span></strong>\
                        </div>\
                      </div>\
                  </div>\
                  <div class="row sett-line">\
                      <div class="row">\
                        <div class="small-12 medium-6 columns ">\
                          <label class="middle with-help">' + lang.ORDER_USE_PLUGINS + ':</label>\
                        </div>\
                        <div class="small-12 medium-6 columns">\
                          <div class="checkbox">\
                            <input type="checkbox" id="usePlugins" name="usePlugins" checked>\
                            <label for="usePlugins" style="margin-top: 5px;"></label>\
                          </div>\
                        </div>\
                      </div>\
                  </div>\
                  <div id="orderPluginsData">\
                  ' + response.data.pluginForm + '\
                  </div>\
                  <div class="row sett-line">\
                      <div class="row">\
                          <div class="large-6 small-12 columns">\
                              <label for="payment">' + lang.ORDER_PAYMENT + ':</label>\
                          </div>\
                          <div class="large-6 small-12 columns">\
                              <strong class="order-edit-visible" ' + tooltipForDisabled + '><select disabled><option>' + data.paymentCurrentName + '</option></select></strong>\
                              <strong class="order-edit-display">' + data.paymentList + '</strong>\
                          </div>\
                      </div>\
                  </div>\
                  <div class="row sett-line">\
                      <div class="row">\
                           <div class="large-6 small-12 columns">\
                                <label>' + lang.ORDER_SUMM + ':</label>\
                           </div>\
                           <div class="large-6 small-12 columns">\
                                <strong>' + '<span class="js-insert-total-price">' + admin.numberFormat((response.data.order.summ * 1 + response.data.order.delivery_cost * 1)) + '</span>' + ' <span class=\'changeCurrency\'>' + currency + '</span></strong>\
                           </div>\
                      </div>\
                  </div>\
                  <div class="row sett-line" style = "'+ ((response.data.paymentAfterConfirm == 'true' && (response.data.order.yur_info == false || response.data.order.yur_info.inn == '' ))?'':'display:none')+'">\
                  <div class="row">\
                      <div class="small-12 medium-6 columns ">\
                      <label class="middle with-help">' +  lang.ORDER_PAYMENT_AFTER_CONFIRM  + ':<span class="question__wrap" tooltip="'+ lang.ORDER_PAYMENT_AFTER_CONFIRM_TOOLTIP +'" flow="up"> <i class="fa fa-question-circle" aria-hidden="true"></i> </span></label>\
                      </div>\
                          <div class="small-12 medium-6 columns">\
                              <div class="checkbox">\
                                  <input type="checkbox" id="paymentAfterConfirmOrder" name="paymentAfterConfirmOrder">\
                                  <label for="paymentAfterConfirmOrder" style="margin-top: 5px;"></label>\
                              </div>\
                          </div>\
                        </div>\
                  </div>\
                  <div class="row sett-line">\
                      <div class="row">\
                        <div class="small-12 medium-6 columns ">\
                          <label class="middle with-help">' + lang.ORDER_IS_PAID + ':</label>\
                        </div>\
                        <div class="small-12 medium-6 columns">\
                          <div class="checkbox">\
                            <input type="checkbox" id="isPaid" name="isPaid">\
                            <label for="isPaid" style="margin-top: 5px;"></label>\
                          </div>\
                        </div>\
                      </div>\
                  </div>\
                  <div class="row sett-line payLinkRow" style="display: none;">\
                    <div class="row">\
                        <div class="small-12 medium-6 columns ">\
                            <label>Ссылка на оплату: <span class="question__wrap" tooltip="'+ lang.ORDER_PAYMENT_LINK_TOOLTIP +'" flow="up"> <i class="fa fa-question-circle" aria-hidden="true"></i> </span></label>\
                        </div>\
                        <div class="small-12 medium-6 columns ">\
                            <strong>\
                                <div class="payLinkContainer">\
                                    <span id="payLinkText" style="cursor: text;"></span>\
                                    <a id="payLink" href="" target="_blank"><i class="fa fa-external-link"></i></a>\
                                </div>\
                            </strong>\
                        </div>\
                    </div>\
                  </div>\
                    </div>\
                </div>';

            // Вёрстка пользовательского комментария
            var orderAdminComment = '\
            <div class="order-fieldset order-payment-sum__item order-payment-sum__item_wide shadow-block">\
            <h2 class="order-fieldset__h2">Комментарии к заказу</h2>\
            <div class="order-comment-block added-comment" >\
                <div class="order-comment-block__inner">\
                    <div class="inner-comments">';

            if (data.info) {
                orderAdminComment += '\
                    <div class="row sett-line">\
                        <div class="row">\
                            <div class="large-3 small-12 columns">\
                                <label>Комментарий покупателя \
                                    <span class="question__wrap" tooltip="Комментарий, оставленный покупателем при оформлении заказа. " flow="up">\
                                        <i class="fa fa-question-circle" aria-hidden="true"></i>\
                                    </span>\
                                </label>\
                            </div>\
                            <div class="large-9 small-12 columns">\
                            ' + data.info + '\
                            </div>\
                        </div>\
                    </div>\
                    ';
            }

            // Вёрстка комментария менеджера
            orderAdminComment += '\
                        <div class="row sett-line">\
                            <div class="row">\
                                <div class="large-3 small-12 columns">\
                                    <label>' + lang.EDIT_ORDER_8 + ' \
                                        <span class="question__wrap" tooltip="Комментарий, отображающийся в информации о заказе в личном кабинете покупателя. Для добавления/изменения комментария перейдите в режим редактирования заказа." flow="up">\
                                            <i class="fa fa-question-circle" aria-hidden="true"></i>\
                                        </span>\
                                    </label>\
                                </div>\
                                <div class="large-9 small-12 columns">\
                                    <strong class="order-edit-visible" tooltip="Для редактирования комментария нажмите кнопку «Редактировать» в левом верхнем углу окна.">\
                                        ' + (response.data.order.comment ? admin.replaceBBcodes((response.data.order.comment).replace(/&lt;/g, '<').replace(/&gt;/g, '>')) : '') + '\
                                    </strong>\
                                    <textarea name="comment" class="cancel-order-reason order-edit-display">' + (response.data.order.comment ? response.data.order.comment : '') + '</textarea>\
                                </div>\
                            </div>\
                        </div>\
                    </div> \
                </div>\
            </div>\
            ';

            orderAdminComment += '</div>';


            // Вёрстка комментария менеджера
            orderAdminComment += response.data.commentsBlock;


            var orderAddFields = '\
              <div class="order-fieldset order-payment-sum__item order-payment-sum__item_wide shadow-block">\
                  <h2 class="order-fieldset__h2">\
                      Дополнительная информация\
                      <span tooltip="Поля этого блока создаются в разделе «Настройки/Доп.поля», а их вывод настраивается в разделе «Настройки/Форма заказа»" flow="up">\
                          <i class="fa fa-question-circle" aria-hidden="true"></i>\
                      </span>\
                  </h2>\
                  <div class="custom-order-fields order-fieldset__inner">' + response.data.customFields + '</div>\
              </div>';


            // Вся вёрстка модалки заказов
            var orderHtml = '\
          ' + orderTableHtml + '\
          <div class="order-payment-sum">\
               ' + personalHtmlEdit + '\
               ' + orderPersonalHtml + '\
               ' + orderDeliveryHtml + '\
               ' + ((response.data.order.yur_info.inn) ? orderYurHtml : '') + '\
               ' + yurHtmlEdit + '\
               ' + orderPaymentHtml + '\
               ' + orderAdminComment + '\
               ' + ((response.data.customFields) ? orderAddFields : '') + '\
          </div>';


            return orderHtml;
        },

        /**
         * Сохраняет настройки к заказам.
         */
        savePropertyOrder: function() {
            $('form[name=requisites] input[type=text]').each(function() {
                $(this).val(admin.htmlspecialchars($(this).val()));
            });
            var data = admin.createObjectFromContainer($('form[name=requisites]'));
            var param = admin.serializeParamsFromObject(data);
            var request = 'mguniqueurl=action/savePropertyOrder&' + param;

            admin.ajaxRequest(
                request,
                function(response) {
                    admin.indication(response.status, response.msg);
                    $('.property-order-container').slideToggle(function() {
                        $('.widget-table-action').toggleClass('no-radius');
                    });

                    $('.requisites-list input[type=text]').each(function (index, value){
                        let oldText = $(this).val();
                        $(this).val(admin.htmlspecialchars_decode(oldText));
                    });
                    let oldText = $('input[name=currency].inline').val();
                    $('input[name=currency].inline').val(admin.htmlspecialchars_decode(oldText));
                },
            );

            return false;
        },

        /**
         * Просчитывает стоимость заказа, обновляет поля.
         */
        calculateOrder: function() {
            if ($('#deliveryCost').val() === '') {
                $('#deliveryCost').val(0);
            }
            var format = admin.PRICE_FORMAT;
            var cent = format.substring(format.length - 3, format.length - 2);
            //Деактивируем кнопку сохранить пока не закончится расчёт
            order.deactivateSaveButton();
            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajax',
                data: {
                    mguniqueurl: 'action/getDiscount',
                    email: $('#order-data input[name="user_email"]').val(),
                    orderPluginsData: admin.createObjectFromContainer($('#add-order-wrapper #orderPluginsData')),
                    paymentId: $('select#payment').val(),
                    deliveryId: $('select#delivery option:selected').attr('name'),
                    orderItems: order.orderItems,
                    usePlugins: $('#add-order-wrapper #usePlugins').prop('checked'),
                    orderId: $('#add-order-wrapper .print-button').data('id')
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    var totalSumm = 0;
                    var totalWeight = 0;

                    $('tbody#orderContent tr').each(function(i, element) {
                        var id = $(this).attr('data-id');
                        if (!id) {
                            return true;
                        }
                        var varid = $(this).attr('data-variant');
                        var arrKey = 'p_' + id;
                        if (parseInt(varid)) {
                            arrKey += '_' + varid;
                        }

                        var prop = $(this).attr('data-prop');
                        if ($(this).attr('data-prop')) {
                            arrKey += '_' + prop;
                        }

                        if(response.data.orderItems[arrKey]) {
                            $(this).find('td.discount span:first').html(String(response.data.orderItems[arrKey].discount).replace('-', '<div style="font-size:10px">' + lang.ORDER_MARKUP + '</div> '));
                            if (response.data.orderItems[arrKey].discDetails.length > 1) {
                                $(this).find('td.discount span:last').attr('tooltip', response.data.orderItems[arrKey].discDetails).show();
                            } else {
                                $(this).find('td.discount span:last').attr('tooltip', '').hide();
                            }

                            $(this).find('td.price span.value').text(admin.numberFormat(response.data.orderItems[arrKey].price)).show();
                            $(this).find('td.price input').val(admin.numberFormat(response.data.orderItems[arrKey].price));

                            var count = $(this).find('td.count input').val();
                            if(typeof count == 'undefined'){
                              count = $(this).find('td.count .show_storage_edit .show_storage_edit_count').html();
                            }
                            var summ = Math.round(count * (response.data.orderItems[arrKey].price * 100));
                        }
                        summ = summ / 100;

                        $(this).find('td.summ').data('summ', summ);
                        $(this).find('td.summ span').text(admin.numberFormat(summ));
                        totalSumm += Math.round(summ * 100);
                        totalWeight += count * $(this).find('.weight').data('weight');
                    });

                    order.orderItems = $.map(response.data.orderItems, function(value, index) {
                        return [value];
                    });

                    totalSumm = totalSumm / 100;
                    totalSumm = response.data.summ;
                    totalWeight = admin.toFloat(totalWeight);

                    var deliveryCost = $('#deliveryCost').val();
                    var plugin = $('#delivery :selected').data('plugin');
                    var orderId = $('#add-order-wrapper button.save-button').attr('id');

                    if (plugin && (!order.firstCall || !orderId)) {
                        deliveryCost = order.getDeliveryCost(plugin);
                    }

                    if (totalSumm >= $('#delivery option:selected').data('free') && $('#delivery option:selected').data('free') > 0 || deliveryCost == undefined) {
                        deliveryCost = 0;
                    }

                    // Если оставить этот код, то при большинстве изменений в заказе, наценка к стоимости доставки будет применяться, даже если она уже была применена
                    // Например, если стомость доставки 100, рублей, и наценка на способ оплаты 10%, то при изменении стоимости товара доставка станет 110, а при повторном изменении цены товар - уже 121
                    // if(response.data.paymentRate){
                    //     deliveryCost = Math.round(parseFloat(deliveryCost)+(parseFloat(deliveryCost) * parseFloat(response.data.paymentRate)));
                    // }

                    var fullCost = Math.round((totalSumm * 100) + (parseFloat(deliveryCost) * 100));
                    fullCost = fullCost / 100;
                    $('#deliveryCost').val(deliveryCost);
                    $('#totalPrice').text(admin.numberFormat(totalSumm));
                    $('.js-insert-total-price').text(admin.numberFormat(fullCost ? fullCost : 0));
                    if (totalWeight > 0) {
                        $('.order-weight').text(totalWeight).closest('.row').show();
                    } else {
                        $('.order-weight').closest('.row').hide();
                    }
                    admin.tooltipEOL();
                    //Активируем кнопку сохранить
                    order.activateSaveButton();
                },
            });

            return false;
        },

        /**
         *
         * @param string plugin
         * @returns {undefined}
         */
        getDeliveryCost: function(plugin) {
            var deliveryId = $('#delivery option:selected').attr('name');
            order.deliveryCost = 0;
            order.getDeliveryOrderOptions(deliveryId);
            loader = $('.mailLoader');

            if (order.deliveryCost > 0 || order.orderItems.length == 0) {
                return order.deliveryCost;
            }
            //Запрашиваем расчет стоимости доставки у плагина
            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/ajaxrequest',
                async: false,
                data: {
                    pluginHandler: plugin, // имя папки в которой лежит данный плагин
                    actionerClass: 'Pactioner', // класс Pactioner в Pactioner.php - в папке плагина
                    action: 'getPriceForParams', // название действия в пользовательском  классе
                    deliveryId: deliveryId,
                    orderItems: order.orderItems,
                    deliveryCost: $('#add-order-wrapper #deliveryCost').val(),
                },
                cache: false,
                dataType: 'json',
                beforeSend: function() {
                    // флаг, говорит о том что начался процесс загрузки с сервера
                    admin.WAIT_PROCESS = true;
                    loader.hide();
                    loader.before('<div class="view-action" style="display:none; margin-top:-2px;">' + lang.LOADING + '</div>');
                    admin.waiting(true);
                },
                success: function(response) {
                    if (response.data.deliverySum >= 0) {
                        order.deliveryCost = response.data.deliverySum;
                        $(window).trigger('getDeliveryCost:finish');
                    } else {
                        alert(response.data.error);
                    }
                    // завершился процесс
                    admin.WAIT_PROCESS = false;
                    //прячим лоадер если он успел появиться
                    admin.waiting(false);
                    loader.show();
                    $('.view-action').remove();
                },
                error: function() {
                    // завершился процесс
                    admin.WAIT_PROCESS = false;
                    //прячим лоадер если он успел появиться
                    admin.waiting(false);
                    loader.show();
                    $('.view-action').remove();
                },
            });
            return order.deliveryCost;
        },
        /**
         *
         * @param int deliveryId
         * @returns {undefined}
         */
        getDeliveryOrderOptions: function(deliveryId, static) {
            var orderId = $('#add-order-wrapper button.save-button').attr('id');

            if (!orderId) {
                orderId = 0;
            }

            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/order',
                data: {
                    action: 'getDeliveryOrderOptions',
                    order_id: orderId,
                    deliveryId: deliveryId,
                    firstCall: order.firstCall,
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    if (response != null) {
                        order.deliveryCost = response.deliverySum;

                        if (static) {
                            $(window).trigger('delivery:change');
                        }
                    }
                },
                error: function(a, b, c) {
                    console.info(a);
                    console.info(b);
                    console.info(c);
                },
            });
        },
        /**
         * Получает данные из формы фильтров и перезагружает страницу
         */
        getProductByFilter: function() {
            var request = $('form[name=filter]').formSerialize();
            admin.show('orders.php', 'adminpage', request + '&applyFilter=1', order.init);
            return false;
        },

        /**
         * изменяет строки в таблице товаров при редактировании изменении.
         */
        drawRowOrder: function(element, assocStatus) {
            if (element.currency_iso == null) {
                var currency = admin.CURRENCY;
            } else {
                var currency = element.currency_iso;
                currency = order.currencyShort[currency];
            }

            var deliveryText = $('#add-order-wrapper #delivery option[name=' + element.delivery_id + ']').text();
            var paymentText = $('#add-order-wrapper #payment option[value=' + element.payment_id + ']').text();
            var statusName = $('#add-order-wrapper #orderStatus option:selected').text();
            var orderSumm = parseFloat(element.summ) + parseFloat(element.delivery_cost);

            // html верстка для  записи в таблице раздела
            var row = '\
       <tr class="" order_id="' + element.id + '">\
       <td class="check-align">\
        <div class="checkbox">\
          <input type="checkbox" id="c2-' + element.id + '" name="order-check">\
          <label for="c2-' + element.id + '"></label>\
        </div>\
       <td> ' + element.id + '</td>\
       <td> ' + element.number + '</td>\
       <td class="add_date"> ' + element.date + '</td>\
       <td> ' + element.name_buyer + '</td>\
       <td> ' + element.user_email + '</td>\
       <td> ' + deliveryText + '</td>\
       <td> <span class="icon-payment-' + element.payment_id + '"></span>' + paymentText + '</td>\
       <td><strong> ' + admin.numberFormat(orderSumm) + ' ' + currency + '</strong></td>\
       <td class="statusId id_' + element.status_id + '">\
       <span class="badge ' + (assocStatus[element.status_id] ? assocStatus[element.status_id] : 'get-paid') + '">' + statusName + '</span>\
       </td>\
       <td class="actions">\
       <ul class="action-list">\
       <li class="see-order" id="' + element.id + '"  data-number="' + element.number + '">\
       <a class="tool-tip-bottom fa fa-pencil" href="javascript:void(0);" title="' + lang.SEE + '"></a>\
       </li>\
       <li class="order-to-csv"><a  data-id="' + element.id + '" class="tool-tip-bottom fa fa-download" href="javascript:void(0);" title="' + lang.OREDER_LOCALE_1 + '"></a></li>\
       <li class="order-to-pdf">\
        <a data-id="' + element.id + '" class="tool-tip-bottom fa fa-file-pdf-o" href="javascript:void(0);" title="' + lang.PRINT_ORDER_PDF + '"></a>\
       </li>\
       <li class="order-to-print">\
        <a  data-id="' + element.id + '" class="tool-tip-bottom fa fa-print" href="javascript:void(0);" title="' + lang.PRINT_ORDER + '"></a>\
       </li>\
       <li class="clone-row" id="' + element.id + '"><a title="' + lang.CLONE_ORDER + '" class="tool-tip-bottom fa fa-files-o" href="javascript:void(0);"></a></li>\
       <li class="delete-order" id="' + element.id + '"><a class="tool-tip-bottom fa fa-trash" href="javascript:void(0);" title="' + lang.DELETE + '"></a>\
       </li>\
       </ul>\
       </tr>';

            return row;

        },
        /**
         * функция для приема подписи из аплоадера
         */
        getSignFile: function(file) {
            var src = decodeURI(file.url);
            src = 'uploads' + src.replace(/(.*)uploads/g, '');
            $('.section-order .property-order-container input[name="sing"]').val(src);
            $('.section-order .property-order-container .singPreview').attr('src', file.url);
        },
        /**
         * функция для приема печати из аплоадера
         */
        getStampFile: function(file) {
            var src = file.url;
            src = 'uploads' + src.replace(/(.*)uploads/g, '');
            $('.section-order .property-order-container input[name="stamp"]').val(src);
            $('.section-order .property-order-container .stampPreview').attr('src', file.url);
        },
        /**
         * функция для приема файла к доп полю
         */
        getOrderOpf: function(file) {
            $('[name="' + uploader.PARAM1.data('op-id') + '"]').val(file.path);
            $('.js-addOrderFile').text(file.name);
        },
        /**
         * Печать заказа
         */
        printOrder: function(id, template, usestamp) {
            admin.ajaxRequest({
                    mguniqueurl: 'action/printOrder',
                    id: id,
                    template: template,
                    usestamp: usestamp
                },
                function(response) {
                    //admin.indication(response.status, response.msg);
                    $('.block-print').html(response.data.html);
                    $('#tiptip_holder').hide();
                    setTimeout('window.print();', 500);
                },
            );
        },
        /**
         * Повторная отправка письма
         */
        resendEmail: function(id) {
            admin.ajaxRequest({
                    mguniqueurl: 'action/resendingEmailOrder',
                    id: id,
                },
                function(response) {
                    admin.indication(response.status, response.msg);
                },
            );
        },

        /**
         * Отправка счета на почту
         */
        senfEmailOrderPdf:function(id, payment_id, header, text, attachment){
            admin.ajaxRequest({
                    mguniqueurl: 'action/sendingOrderPdf',
                    id: id,
                    payment_id:payment_id,
                    header:header,
                    text:text,
                    attachment:attachment
                },
                function(response) {
                    admin.indication(response.status, response.msg);
                    if(response.status == "success"){
                        admin.refreshPanel();
                    }
                },
            );
        },

        /**
         * Включает режим редактирования заказа
         */
        enableEditor: function() {
            $('.order-payment-sum').addClass('order-payment-sum__edit');
            $('.order-payment-sum [type="checkbox"]').removeAttr('disabled');
            $('#add-order-wrapper .currSpan').show();
            $('.storage_select').show();
            var id = $('#add-order-wrapper .save-button').attr('id');
            var number = $('#add-order-wrapper .save-button').attr('data-number');
            if (id) {
                $('.add-order-table-icon').text(lang.EDIT_ORDER_9 + ' №' + number + ' от ' + $('tr[order_id=' + id + '] .add_date').text());
            } else {
                $('.add-order-table-icon').text(lang.EDIT_ORDER_10);
            }

            //Для складов
            $('.show_storage_edit').show();

            $('.order-edit-display').show();
            $('.order-edit-visible').hide();
            $('#orderStatus').addClass('edit-layout');
            var date = $('#delivery :selected').data('date');
            if (date == 1) {
                $('.delivery-date').show();
            } else {
                $('.delivery-date').hide();
            }
            $('#customer').change();
            var interval = $('#delivery :selected').data('interval');
            if (interval) {
                if (!$.isArray(interval)) {
                    interval = interval.replace('["', '').replace('"]', '').split('","');
                }
                $('.delivery-interval [name=interval] option').remove();
                for (var i = 0; i < interval.length; i++) {
                    if (interval[i] != '') {
                        $('.delivery-interval [name=interval]').append('<option value=\'' + admin.htmlspecialchars(admin.htmlspecialchars_decode(interval[i])) + '\'>' + admin.htmlspecialchars(admin.htmlspecialchars_decode(interval[i])) + '</option>');
                    }
                }
                $('.delivery-interval').show();
                interval = $('.itervalInitialVal').val();
                if (interval) {
                    $('.delivery-interval [name=interval]').val(interval);
                }
            } else {
                $('.delivery-interval').hide();
            }

            // Показываем подробный адрес заказа или обычный одним инпутом
            var address_parts = $('#delivery :selected').data('address-parts');
            $('.js-address-part-toggle').hide().find('input').val('');
            if (address_parts) {
                for (var i = 0; i < address_parts.length; i++) {
                    $('.js-address-part-toggle input[name=address_' + address_parts[i] + ']').val(admin.htmlspecialchars_decode(order.address_parts_val[address_parts[i]])).parents('.js-address-part-toggle').show();
                }
                $('.js-address-toggle').hide();
            } else {
                $('.js-address-toggle').show();
            }

            // Показываем поле ФИО одним инпутом или тремя
            // TODO Выше практически дубль этого кода, нужно сделать функцию
            var name_parts = $('#delivery :selected').data('name-parts');
            $('.js-name-part-toggle').hide().find('input').val('');
            if (name_parts) {
                for (var i = 0; i < name_parts.length; i++) {
                    $('.js-name-part-toggle input[name=fio_' + name_parts[i] + ']').val(admin.htmlspecialchars_decode(order.name_parts_val[name_parts[i]])).parents('.js-name-part-toggle').show();
                }
                $('.js-name-toggle').hide();
            } else {
                $('.js-name-toggle').show();
            }


            //редактирование для склада
            $('.show_storage_edit').on('click', function(){
              var count = $(this).data('count');
              var storage = $(this).data('storage');
              order.storageItemNimbe = Number($(this).parent().parent().find('.number').html()) - 1
              $('#popupContent').html(order.showStoragePopupContent(count, storage, order.storageItemNimbe));

              $('.remove_storage_row').on('click', function(){
                order.removeRowFromStoragePopupContent($(this));
              });
              var obj = $(this);
			  var objOffset = obj.offset();
              $('.custom-popup').show().offset({
						top: parseInt(objOffset.top + 25),
						left: parseInt(objOffset.left - 170) // $('#add-product-wrapper .weightUnit-block').outerWidth() / 2 + 8
					});
            });

            //Нажатие по кнопке "Применить" при редактировании складов
            $('.apply-storageUnit').on('click', function(){
              order.saveOrderStorage(order.storageItemNimbe);
            });

            $('.cancel-storageUnit').on('click', function(){
              $('.custom-popup').hide();
            });

            $('.add-storageUnit').on('click', function(){
              var storageCount = order.storage_count_array[order.storageItemNimbe].length;
              if($('#popupContent').find('select').length >= storageCount){
                alert(lang.ORDER_STORAGE_ERROR_4);
              }else{
                $('#popupContent').append(order.addRowToStoragePopupContent(order.storageItemNimbe));
              }
              $('.remove_storage_row').on('click', function(){
                order.removeRowFromStoragePopupContent($(this));
              });

            });

            //$("input[name=phone]").mask("+7 (999) 999-99-99");
            //$("input[name=phone]").mask("+38 (999) 999-99-99");
            $('#delivery').on('change', function() {
                $('.delivery-date').hide();
                $('.add-delivery-info').remove();

                if ($('#delivery :selected').data('date') == 1) {
                    $('.delivery-date').show();
                }

                var interval = $('#delivery :selected').data('interval');
                if (interval && interval.length > 0) {
                    $('.delivery-interval [name=interval] option').remove();
                    for (var i = 0; i < interval.length; i++) {
                        if (interval[i] != '') {
                            $('.delivery-interval [name=interval]').append('<option value="' + admin.htmlspecialchars(admin.htmlspecialchars_decode(interval[i])) + '">' + admin.htmlspecialchars(admin.htmlspecialchars_decode(interval[i])) + '</option>');
                        }
                    }
                    $('.delivery-interval').show();
                } else {
                    $('.delivery-interval').hide();
                }

                var address_parts = $('#delivery :selected').data('address-parts');
                $('.address_part').hide();
                if (address_parts) {
                    for (var i = 0; i < address_parts.length; i++) {
                        $('.address_part input[name=address_' + address_parts[i] + ']').closest('.address_part').show();
                    }
                    $('input[name=address]').closest('.js-address-toggle').hide();
                } else {
                    $('input[name=address]').closest('.js-address-toggle').show();
                }

                var name_parts = $('#delivery :selected').data('name-parts');
                $('.name_part').hide();
                if (name_parts) {
                    for (var i = 0; i < name_parts.length; i++) {
                        $('.name_part input[name=fio_' + name_parts[i] + ']').closest('.name_part').show();
                    }
                    $('input[name=name_buyer]').closest('.js-name-toggle').hide();
                } else {
                    $('input[name=name_buyer]').closest('.js-name-toggle').show();
                }

                var select = $(this);
                var deliveryId = $('#delivery :selected').attr('name');

                var plugin = $('#delivery :selected').data('plugin');
                if (plugin && plugin.length > 0) {
                    $.ajax({
                        type: 'POST',
                        url: mgBaseDir + '/ajaxrequest',
                        data: {
                            pluginHandler: plugin, // имя папки в которой лежит данный плагин
                            actionerClass: 'Pactioner', // класс Pactioner в Pactioner.php - в папке плагина
                            action: 'getAdminDeliveryForm', // название действия в пользовательском  классе
                            deliveryId: deliveryId,
                            firstCall: order.firstCall,
                            orderItems: order.orderItems,
                            orderId: id,
                        },
                        cache: false,
                        dataType: 'json',
                        success: function(response) {
                            order.firstCall = false;
                            select.parents('.js-insert-delivery-options').append('<div class="add-delivery-info row">' + response.data.form + '</div>');
                            $('input#deliveryCost').prop('disabled', true);
                            // $('#delivery').trigger('change');
                        },
                    });
                } else {
                    $('.add-delivery-info').remove();
                    $('input#deliveryCost').prop('disabled', false);
                }
            });
        },
        /**
         * Пересчет цены товара аяксом в форме добавления заказа.
         */
        refreshPriceProduct: function() {
            var request = $('.property-form').formSerialize();
            request += '&wholesaleGroup=' + order.wholesalesGroup;
            if (order.wholesalesCount !== '') {
                request += '&wholesaleForcedCount=' + order.wholesalesCount;
            }

            //$('.orders-table-wrapper .property-form .addToCart').css('visibility', 'hidden');
            // Пересчет цены.
            $.ajax({
                type: 'POST',
                url: mgBaseDir + '/product/',
                data: 'calcPrice=1&' + request,
                dataType: 'json',
                cache: false,
                success: function(response) {
                    if ('success' == response.status) {
                        //$('#order-data .product-block .price-sp').text(response.data.price_wc);
                        $('#order-data .product-block .price-sp').text(Math.ceil(response.data.real_price * 100) / 100);
                        $('#order-data .product-block .code-sp').text(admin.htmlspecialchars_decode(response.data.code));
                        $('#order-data .product-block .weight-sp').text(response.data.weight);
                        $('#order-data .product-block .count-sp').text(response.data.count == '-1' ? lang.AVAILIBLE_NOW : lang.REMAIN + ' ' + response.data.count);
                        $('#order-data .product-block .count-sp').data('count', response.data.count);
                        $('.orders-table-wrapper .property-form .addToCart').css('visibility', 'visible');
                    }
                },
            });
        },
        /**
         * Клик по найденным товарам в поиске в форме добавления заказа
         */
        viewProduct: function(elementIndex) {
            $('.search-block .errorField').css('display', 'none');
            $('.search-block input.search-field').removeClass('error-input');
            var product = admin.searcharray[elementIndex];

            if (product.category_unit == null) {
                product.category_unit = 'шт.';
            }
            order.searchUnit = product.category_unit;
            if (!product.category_url) {
                product.category_url = 'catalog';
            }
            if (product.category_url.charAt(product.category_url.length - 1) == '/') {
                product.category_url = product.category_url.slice(0, -1);
            }
            var html =
                '<div class="product-block__inner">\
                  <div class="product-info__wrap product-block_part product-block_part_left">\
                    <div class="image-sp product-info__img">\
                      <img alt="Изображение товара" src="' + product.image_url + '">\
              </div>\
              <div class="product-info" >\
                <div class="title-sp">\
                  <span class="title-sp__title">\
                  ' + product.title + '\
                  </span>\
                    <a href="' + mgBaseDir + '/' + product.category_url + '/' + product.url + '"\
                      data-url="' + product.category_url + '/' + product.url + '" class="url-sp tooltip--small tooltip--center" tooltip="' + lang.PRODUCT_VIEW_SITE + '" target="_blank">\
                      <i class="fa fa-external-link" aria-hidden="true"></i>\
                    </a>\
                </div>\
                <div class="id-sp" style="display:none" data-set="' + product.notSet + '">\
                  ' + product.id + '\
                </div>\
                <div class="price-line">\
                  <strong>' + lang.PRICE_PRODUCT + ':</strong>\
                  <span class="price-sp">' + Math.round(product.price_course * 100) / 100 + '</span>\
                  <span class="currency-sp"> ' + product.currency + '</span>\
                </div>\
                <div class="code-line">\
                  <strong>' + lang.CODE_PRODUCT + ':</strong> \
                  <span class="code-sp">' + product.code + '</span>\
                </div>\
                <div class="weight-line">\
                  <strong>' + lang.WEIGHT + ':</strong>\
                  <span class="weight-sp">' + product.weight + '</span>\
                </div>\
                <div class="weight-line">\
                  <span class="count-sp" data-count="' + product.count + '">\
                    ' + (product.count == -1 ? lang.AVAILIBLE_NOW : lang.REMAIN + ' ' + product.count) + '\
                  </span>\
                </div>\
                <div class="form-sp">' + product.propertyForm + '</div>\
              </div>\
            </div>\
            <div class="product-block_part product-block_part_right">\
              <div class="desc-sp">\
                 <ul class="template-tabs-menu inline-list tabs custom-tabs custom-tabs_order" style="margin-top:0">\
                   <li class="is-active template-tabs tabs-title" part="descr">\
                     <a role="button" href="javascript:void(0);"><span>' + lang.PLUG_DESC + '</span></a>\
                   </li>' +
                '<li class="template-tabs tabs-title" part="props">\
                  <a role="button" href="javascript:void(0);"><span>' + lang.CHARACTERISTICS + '</span></a>\
                   </li>' +
                '<li class="template-tabs tabs-title" part="wholesale">\
                  <a role="button" href="javascript:void(0);"><span>' + lang.CATALOG_LOCALE_8 + '</span></a>\
                   </li>' +
                '</ul>' +
                '<span class="descrip">' + ((product.description) ? product.description : 'Описание товара не задано.') + '</span>' +
                '<span class="propsTo"></span>' +
                '<span class="wholesales" style="display: none;">' +
                '<span class="section__desc">Введите email пользователя, чтобы увидеть его персональную сетку оптовых цен.</span>' +
                '<div class="wholesales__form search-wholesales">' +
                '<input class="search-wholesales__item" type="text" placeholder="' + lang.T_TIP_USER_EMAIL + '" style="width: calc(100% - 215px);display: inline-block;">' +
                '<button class="button success getWholeSales fl-right" style="margin-top: 0;">' +
                '<i class="fa fa-search"></i> ' + lang.LOAD_WHOLESALES +
                '</button>' +
                '</div>' +
                '<div class="wholesalesContainer"></div>' +
                '</span>' +
                '</div>\
              </div>\
            </div>';
            html += '<div class="clear"></div>';
            $('#order-data .product-block').html(html);
            $('#order-data .product-block .orderUnit').text(' ' + product.category_unit);
            $('#order-data .product-block .propsFrom').detach().appendTo('#order-data .product-block .propsTo').hide();
            $('.addToCart').wrap('<span class="button success btn-a-white"></span>');
            if (!$('#order-data .addiProps .property-title').length) {
                $('#order-data .addiProps').hide();
            }
            $('input[name=searchcat]').val('');
            $('.fastResult').hide();
            $('.addToCart').attr('href', 'javascript:void(0);');
            $('#order-data .product-block .amount_input').trigger('change');
            $('.section-order #order-data .template-tabs-menu li[part=' + order.lastAddProdTab + ']').click();
        },

        /**
         * Добавляет товар в заказ
         */
        addToOrder: function(obj, data=false) {
            var storage = false;
            if(data){
              storage = data['storage'];
            }
            if ($('#add-order-wrapper .save-button').attr('id') && !$('#order-data .id-sp').data('set')) {
                admin.indication('error', lang.ERROR_MESSAGE_20);
                return false;
            }
            $('.search-block .errorField').css('display', 'none');
            $('.search-block input.search-field').removeClass('error-input');

            var max_count_in_order = $('#max-count-cart').text();
            var count_in_order = $('#orderContent tr').length + 1;
            if (count_in_order > max_count_in_order) {
                admin.indication('error', lang.LIMIT_EXCEEDED + ' [max =' + max_count_in_order + ']');
                return false;
            }
            var count = $('#order-data .count-sp').data('count');
            if (count == '0') {
                admin.indication('error', lang.NON_AVAILIBLE);
                return false;
            }
            var maxCount = (count == '-1' || count == '∞') ? -1 : parseInt(count) - 1;


            // Собираем все выбранные характеристики для записи в заказ.
            var prop = order.getPropPosition(obj);
            var variant = $('.block-variants tr td input:checked').val();
            variant = variant ? variant : 0;
            if ($('#add-order-wrapper .variants-table .c-variant__name').length) {
                var itemName = $('#order-data .title-sp').text() + ' ' + admin.trim($('.property-form input[name=variant]:checked').parents('tr').find('.c-variant__name').text());
            } else {
                var itemName = $('#order-data .title-sp').text() + ' ' + admin.trim($('.property-form input[name=variant]:checked').parents('tr').find('label').text());
            }
            var notSet = '';
            notSet = $('#add-order-wrapper .save-button').attr('id') ? $('#order-data .id-sp').data('set') : true ;
            let countInInput = $('.product-block .amount_input').val();
            if (countInInput < 0) {
                countInInput = Math.abs(countInInput);
                $('.product-block .amount_input').val(countInInput);
            }
            var position = {
                order_id: $('#order-data .id-sp').text(),
                id: parseInt($('#order-data .id-sp').text()),
                title: '<a href="' + mgBaseDir + '/' + $('#order-data .url-sp').data('url') + '" data-url="' + $('#order-data .url-sp').data('url') + '" class="href-to-prod"><span class="titleProd">' + admin.htmlspecialchars(itemName) + '</span></a>' + '<span class="property">' + prop + '</span>',
                prop: prop,
                code: $('#order-data .code-sp').text(),
                weight: $('#order-data .weight-sp').text(),
                price: $('#order-data .price-sp').text(),
                count: countInInput,
                summ: $('#order-data .price-sp').text().replace(/,/, '.').replace(/\s/, ''),
                url: $('#order-data .url-sp').data('url'),
                image_url: $('#order-data .image-sp img').attr('src'),
                fulPrice: $('#order-data .price-sp').text().replace(/,/, '.').replace(/\s/, ''),
                variant: variant,
                maxCount: maxCount,
                notSet: notSet,
                category_unit: order.searchUnit,
                currency_iso: $('#add-order-wrapper .currSpan [name=userCurrency]').val(),
                discount: 0,
            };
            if(storage != false && data != false){
                position['storage_count_array'] = [];
               for (let i =0 ; i< data['storageArray'].length; i++){
                 position['storage_count_array'].push({storage: data['storageArray'][i]['storage'], count : data['storageArray'][i]['count']})  ;
               }
              position['storage_array'] = [];
              position.storage_array.push(storage+'_'+position.count);
            }
            var numberProd = $('#orderContent').find('tr').length-1;
            var row = order.createPositionRow(position, false, numberProd);
            var update = false;
            // сравним добавляемую строку с уже имеющимися, возможно нужно только увеличить количество
            $('.status-table tbody#orderContent tr').each(function(i, element) {
                if (!$(this).data('id')) {
                    return true;
                }
                // Старый вариант, который не работал, если изменялось название товара
                // var title1 = $(this).find('.title>a').text().replace('<br>', '').replace(/\s/gi, '');
                // var title2 = itemName.replace('<br>', '').replace(/\s/gi, '');

                // Новый вариант, который работает по идентификаторам товарв
                var variant1 = parseFloat($(this).data('variant'));
                var variant2 = parseFloat($(this).data('variant'));

                var propName = order.getPropPosition(obj, true).replace('<br>', '').replace(/\s/gi, '');
                var propName2 = $(this).find('.title .prop-position').text().replace('<br>', '').replace(/\s/gi, '');

                if ($(this).data('id') == position.id && variant1 === variant2 && propName == propName2) {
                    if (storage != false) {
                      var count = $(this).find('.show_storage_edit .show_storage_edit_count').html();
                      var newCount = parseFloat(count) + parseFloat(position.count);

                      var dataCount = $(this).find('.show_storage_edit').data('count');
                      dataCount = dataCount.split(',');
                      dataCount[0] = parseFloat(dataCount[0]) + parseFloat(position.count);
                      dataCount = dataCount.join(', ');

                      $(this).find('.show_storage_edit').data('count', dataCount);
                      $(this).find('.show_storage_edit .show_storage_edit_count').html(newCount);
                      $(this).find('.storageArray[data-storage="'+storage+'"] .count');

                      var itemNumber = $(this).find('.number').html()-1;
                      var storageArrayNew = [];
                      //Смотрим, был ли у нас уже такой склад когда мы добавляем товар
                      var flag = false
                      for(let i=0; i<order.orderItems[itemNumber]['storage_id'].length; i++){
                        var storageArray = order.orderItems[itemNumber]['storage_id'][i].split('_');
                        var countInStorage = storageArray[1];
                        if(storageArray[0] == storage){
                          countInStorage = parseFloat(storageArray[1]) + parseFloat(position.count);
                          $(this).find('.storageArray[data-storage="'+storage+'"] .count').html(countInStorage);
                          flag = true;
                          storageArrayNew.push(storageArray[0] + '_' + countInStorage);
                        }
                      }
                      //Еслм склада не было, то записываем в тот, который уже есть
                      if(flag == false){
                        storageArray = order.orderItems[itemNumber]['storage_id'][0].split('_');
                        countInStorage = storageArray[1];
                        countInStorage = parseFloat(storageArray[1]) + parseFloat(position.count);
                        $(this).find('.storageArray[data-storage="'+storageArray[0]+'"] .count').html(countInStorage);
                        storageArrayNew.push(storageArray[0] + '_' + countInStorage);
                      }
                      order.orderItems[itemNumber]['storage_id'] = storageArrayNew;
                      order.orderItems[itemNumber]['count'] = String($(this).find('.show_storage_edit .show_storage_edit_count').html());
                    }
                    else{
                      var count = $(this).find('.count input').val();
                      $(this).find('.count input').val(parseFloat(position.count) + parseFloat(count));
                    }

                    // $(this).find('.count input').val(count * 1 + 1);
                    // var max = parseInt($(this).find('.count input').data('max'));
                    // if ((count * 1 + 1) > max + 1 && (max > 0)) {
                    //     $(this).find('.count input').val(max + 1);
                    // }
                    update = true;
                }
            });
            // если не обновляем, то добавляем новую строку
            if (!update) {
                $('.status-table tbody#orderContent').append(row);
            }

            var orderItem = {
                id: position.id,
                variant_id: position.variant ? position.variant : 0,
                name: itemName,
                url: $('#order-data .url-sp').data('url'),
                code: position.code,
                price: position.price,
                count: position.count,
                property: position.prop,
                discount: 0,
                fulPrice: position.fulPrice,
                weight: position.weight,
                currency_iso: position.currency_iso,
                unit: position.category_unit,
            };

            if(storage != false){
              orderItem['storage_id'] = [];
              orderItem['storage_id'].push(storage+'_'+orderItem.count);
            }

            var toUpdate = false;
            order.orderItems.forEach(function(item, i) {
                if (item.id == orderItem.id && item.variant_id == orderItem.variant_id && item.property == orderItem.property) {
                    orderItem.count = parseFloat(item.count) + parseFloat(orderItem.count);
                    if (storage == false) {
                      order.orderItems[i] = orderItem;
                    }
                    toUpdate = true;
                }
            });
            if (toUpdate === false) {
              order.orderItems.push(orderItem);
            }

            order.calculateOrder();
            order.renumericPosition();
            $('.fastResult').hide();
            $('input[name=searchcat]').val('');
            $('#orderContent .emptyOrder').hide();
            //редактирование для склада
            $('.show_storage_edit').on('click', function(){
              var count = $(this).data('count');
              var storage = $(this).data('storage');
              order.storageItemNimbe = Number($(this).parent().parent().find('.number').html()) - 1
              $('#popupContent').html(order.showStoragePopupContent(count, storage, order.storageItemNimbe));

              $('.remove_storage_row').on('click', function(){
                order.removeRowFromStoragePopupContent($(this));
              });

              var obj = $(this);
			  var objOffset = obj.offset();
              $('.custom-popup').show().offset({
						top: parseInt(objOffset.top + 25),
						left: parseInt(objOffset.left - 170) // $('#add-product-wrapper .weightUnit-block').outerWidth() / 2 + 8
					});
            });

        },
        //Клонирование заказа
        cloneOrder: function(id) {
            // получаем с сервера все доступные пользовательские параметры
            admin.ajaxRequest({
                    mguniqueurl: 'action/cloneOrder',
                    id: id,
                },
                function(response) {
                    admin.indication(response.status, response.msg);
                    admin.refreshPanel();
                },
            );
        },
        //Деактивация кнопки сохранить
        deactivateSaveButton: function() {
            $('#add-order-wrapper .save-button i').removeClass('fa-floppy-o');
            $('#add-order-wrapper .save-button i').addClass('fa-spinner');
            $('#add-order-wrapper .save-button i').addClass('fa-spin');
            order.allowSave = false;
        },
        //Активация кнопки сохранить
        activateSaveButton: function() {
            $('#add-order-wrapper .save-button i').addClass('fa-floppy-o');
            $('#add-order-wrapper .save-button i').removeClass('fa-spinner');
            $('#add-order-wrapper .save-button i').removeClass('fa-spin');
            order.allowSave = true;
        }
    };
})();

$('.ui-autocomplete').css('z-index', '1000');
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
    isRTL: false,
};
$.datepicker.setDefaults($.datepicker.regional['ru']);
