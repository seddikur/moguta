function ModalTemplates() {
    const
        body = document.querySelector('body'),
        adminBar = document.querySelector('.admin-bar'),
        popup = document.querySelector('.js-templates-popup'),
        iframe = document.querySelector('.js-templates-popup__iframe'),
        timeout = 300,
        popupInstallTemplate = document.querySelector('.js-templates-options'),
        installTemplateButton = document.querySelector('.js-templates-options__install'),
        installTemplateWithDemoButton = document.querySelector('.js-templates-options__install-demo');
    let unlock = true;

    const modalTemplates = {
        reloadPageWithRedirect: null,

        /** 
         * Метод запускает функции, необходимые для работы модального окна со сменой шаблона
        */
        initModal: function () {
            if (!popup) return;
            const editModeButton = document.querySelector('.js-admin-edit-site:not(.js-admin-edit-template)');
            if (editModeButton && editModeButton.classList.contains('admin-bar-link_edit_on')) {
                modalTemplates.toggleModalTemplatesButton();
            }
            modalTemplates.bindEventsModal();
            popup.classList.remove('_hide');

            (function () {
                // проверяем поддержку
                if (!Element.prototype.closest) {
                    // реализуем
                    Element.prototype.closest = function (css) {
                        var node = this;
                        while (node) {
                            if (node.matches(css)) return node;
                            else node = node.parentElement;
                        }
                        return null;
                    };
                }
            })();
            (function () {
                // проверяем поддержку
                if (!Element.prototype.matches) {
                    // определяем свойство
                    Element.prototype.matches = Element.prototype.matchesSelector ||
                        Element.prototype.mozMatchesSelector ||
                        Element.prototype.msMatchesSelector;
                }
            })();
        },

        /** 
         * Метод вешает события на HTML-элементы, необходимые для работы модального окна
        */
        bindEventsModal: function () {
            // При клике на кнопку, открывающую модальное окно выбора шаблона
            this.bindEventOpenModal();

            // Делегирование события клика всему модальному окну
            popup.addEventListener('click', (e) => {
                // Если нажали на элементы, закрывающие попап
                if (e.target && e.target.classList.contains('js-templates-popup__close')) {
                    e.preventDefault();
                    modalTemplates.popupClose();
                }
                // Если нажали на кнопку "Демо"
                if (e.target && e.target.classList.contains('js-templates-popup__demo-button')) {
                    e.preventDefault();
                    modalTemplates.openDemo(e.target);
                }
                // Если нажали на кнопку "Закрыть" в модальном окне демо
                if (e.target && e.target.classList.contains('js-templates-iframe__close')) {
                    e.preventDefault();
                    modalTemplates.closeDemo();
                }
                // Если нажали на кнопку "Применить"
                if (e.target && e.target.classList.contains('js-templates-popup__activation')) {
                    e.preventDefault();
                    modalTemplates.activateTemplate(e);
                }
            });

            // Закрываем попап при нажатии на клавишу Esc
            document.addEventListener('keydown', function (e) {
                const preloaderWrap = popup.querySelector('.preloader-wrap');
                if (preloaderWrap) return;
                const popupActive = document.querySelector('.js-templates-popup.open');
                const popupOptions = document.querySelector('.js-templates-options._open');
                if (e.key == 'Escape' && popupActive) {
                    if (popupActive.classList.contains('_open-demo')) {
                        modalTemplates.closeDemo();
                        return;
                    }
                    if (popupOptions) {
                        modalTemplates.closeOptionsInstallationTemplate();
                        return;
                    }
                    modalTemplates.popupClose();
                }
            });

            // Кнопки "Установить демо-базу" и "Cохранить текущую базу данных" при выборе варианта установки шаблона
            installTemplateButton.addEventListener('click', modalTemplates.activateWithoutDemo);
            installTemplateWithDemoButton.addEventListener('click', modalTemplates.activateWithDemo);

            popupInstallTemplate.addEventListener('click', (e) => {
                if (!e.target.closest('.js-templates-options__content') || e.target.classList.contains('js-templates-options__close')) {
                    modalTemplates.closeOptionsInstallationTemplate();
                }
            });
        },

        bindEventOpenModal: function () {
            const modalTemplatesButtons = document.querySelectorAll('.js-modal-templates-button');
            if (modalTemplatesButtons && modalTemplatesButtons.length < 1) return;
            for (const modalTemplatesButton of modalTemplatesButtons) {
                modalTemplatesButton.addEventListener('click', () => {
                    modalTemplates.loadImage();
                    modalTemplates.popupOpen(popup);
                });
            }
        },

        toggleModalTemplatesButton: function () {
            const modalTemplatesButton = document.querySelector('.js-modal-templates-button');
            if (!modalTemplatesButton) return;
            modalTemplatesButton.classList.toggle('_hide');
        },

        /**
         * Метод открывает модальное окно
         * @param {HTMLElement} curentPopup - модальное окно
        */
        popupOpen: function (curentPopup) {
            if (!curentPopup || !unlock) return;
            const popupActive = document.querySelector('.js-templates-popup.open');
            if (popupActive) {
                modalTemplates.popupClose(false);
            } else {
                this.bodyLock();
            }
            curentPopup.classList.add('open');
            curentPopup.addEventListener("click", function (e) {
                if (!e.target.closest('.js-templates-popup__content')) {
                    modalTemplates.popupClose();
                }
            });
        },

        /** 
         * Метод закрывает открытое модальное окно
         * @param {boolean} doUnlock - надо ли разблокировать скролл страницы
        */
        popupClose: function (doUnlock = true) {
            const popupActive = document.querySelector('.js-templates-popup.open');
            if (unlock && popupActive) {
                popupActive.classList.remove('open');
                if (doUnlock) {
                    this.bodyUnLock();
                }
            }
        },

        /** 
         * Метод блокирует скролл страницы при открытом модальном окне
        */
        bodyLock: function () {
            const lockPaddingValue = window.innerWidth - body.offsetWidth;

            if (adminBar) {
                adminBar.style.paddingRight = lockPaddingValue + 15 + 'px';
            }
            body.style.paddingRight = lockPaddingValue + 'px';
            body.classList.add('lock');

            unlock = false;
            setTimeout(function () {
                unlock = true;
            }, modalTemplates.timeout);
        },

        /** 
         * Метод разблокирует скролл страницы при открытом модальном окне
        */
        bodyUnLock: function () {
            setTimeout(function () {
                if (adminBar) {
                    adminBar.style.paddingRight = '15px';
                }
                body.style.paddingRight = '0px';
                body.classList.remove('lock');
            }, timeout);

            unlock = false;
            setTimeout(function () {
                unlock = true;
            }, timeout);
        },

        /** 
         * Метод открывает демо-сайт в iframe
         * @param {HTMLElement} button - кнопка "Демо", по которой произошел клик
        */
        openDemo: function (button) {
            if (!button.dataset.srcDemo) return;
            const iframeWrap = popup.querySelector('.js-templates-iframe');
            modalTemplates.preloader(true, 'Загружается демонстрационный сайт', iframeWrap);
            popup.classList.add('_open-demo');
            document.querySelector('.js-templates-iframe__name').textContent = button.dataset.tempName;
            document.querySelector('.js-iframe-template-btn').dataset.tempName = button.dataset.tempName
            setTimeout(() => {
                iframe.src = button.dataset.srcDemo;
                $(iframe).on('load', () => {
                    modalTemplates.preloader(false);
                });
            }, 500);
        },

        /** 
         * Метод закрывает демо-сайт в iframe
        */
        closeDemo: function () {
            popup.classList.remove('_open-demo');
            document.querySelector('.js-templates-iframe__name').textContent = '';
            setTimeout(() => {
                iframe.src = '';
            }, 500);
        },

        /** 
         * Метод загружает изображения модального окна
        */
        loadImage: function () {
            const imagesSrc = popup.querySelectorAll('[data-src]');
            const imagesSrcset = popup.querySelectorAll('[data-srcset]');

            for (const imageSrc of imagesSrc) {
                if (!imageSrc.dataset.src || imageSrc.dataset.src === '') continue;
                imageSrc.src = imageSrc.dataset.src;
                imageSrc.dataset.src = '';
            }

            for (const imageSrcset of imagesSrcset) {
                if (!imageSrcset.dataset.srcset || imageSrcset.dataset.srcset === ' 2x') continue;
                imageSrcset.srcset = imageSrcset.dataset.srcset;
                imageSrcset.dataset.srcset = '';
            }
        },

        /** 
         * Метод активирует шаблон при нажатии на кнопку "Применить" в модальном окне
         * @param {event} event - событие клика по кнопке "Применить"
        */
        activateTemplate: function (event) {
            if (document.querySelector('.mg-admin-html')) {
                this.activateTemplateFromAdmin(event);
            } else {
                this.openOptionsInstallationTemplate(event);
            }
        },

        /**
         * Метод открывает модальное окно с выбором способа установки шаблона (с переустановкой демо-базы или нет)
         * Используется в публичке
         * @param {event} event - событие клика по кнопке "Применить"
         */
        openOptionsInstallationTemplate: function (event) {
            if (!popupInstallTemplate) return;
            popupInstallTemplate.classList.add('_open');

            installTemplateButton.dataset.tempName = event.target.dataset.tempName;
            installTemplateButton.dataset.color = event.target.dataset.color;
            installTemplateWithDemoButton.dataset.tempName = event.target.dataset.tempName;
            installTemplateWithDemoButton.dataset.color = event.target.dataset.color;
        },

        /**
        * Метод закрывает модальное окно с выбором способа установки шаблона
        */
        closeOptionsInstallationTemplate: function () {
            popupInstallTemplate.classList.remove('_open');
        },

        /** 
         * Метод отправляет запрос на сервер на смену шаблона (без переустановки демо-базы)
         * Используется в публичке
         * @param {event} event - событие клика по кнопке "Cохранить текущую базу данных"
        */
        activateWithoutDemo: function (event) {
            modalTemplates.selectedTemplate = event.target.dataset.tempName;
            modalTemplates.preloader(true, 'Установка шаблона <br> Cтраница будет автоматически перезагружена', popup);
            admin.ajaxRequest({
                mguniqueurl: 'action/saveTemplateSettings',
                settings: {
                    templateName: event.target.dataset.tempName,
                    colorScheme: event.target.dataset.color
                },
            },
                function (response) {
                    modalTemplates.reloadPageWithRedirect();
                });
        },

        /**
         * Метод перезагружает страницу с редиректом на главную. Если это конструктор, предварительно пингует страницу
         */
        reloadPageWithRedirect: function () {
            if (modalTemplates.selectedTemplate === 'mg-cloud-air') {
                modalTemplates.pingMainPage();
                return;
            }
            window.location.href = window.location.origin;
        },

        /**
         * Метод пингует главную страницу для отрисовки дерева нод на конструкторе перед загрузкой сайта.
         * Промис ждет ответа и только после этого перезагружает страницу. Если ответа с сервера нет,
         * то запрос рекурсивно выполняется до тех пор, пока не будет выполнен успешно
         */
        pingMainPage: async function () {
            let request = await $.ajax({
                type: "GET",
                cache: false,
                url: mgBaseDir,
                success: function (response) {
                    if (!response) {
                        return false;
                    }
                    return true;
                },
                error: function () {
                    return false;
                }
            });
            if (!request) {
                modalTemplates.pingMainPage();
                return;
            }
            window.location.href = window.location.origin; // Перезагружаем страницу с редиректом на главную
        },

        /** 
         * Метод отправляет запрос на сервер на смену шаблона (с переустановкой демо-базы)
         * Используется в публичке
         * @param {event} event - событие клика по кнопке "Установить демо-базу"
        */
        activateWithDemo: function (event) {
            modalTemplates.selectedTemplate = event.target.dataset.tempName;
            modalTemplates.preloader(true, 'Шаблон установлен <br> Подтвердите установку демонстрационных данных', popup);
            admin.ajaxRequest({
                mguniqueurl: 'action/saveTemplateSettings',
                settings: {
                    templateName: event.target.dataset.tempName,
                    colorScheme: event.target.dataset.color
                },
            },
                function (response) {
                    if (response.data.plugins) settings_template.pluginsTemplateList = response.data.plugins;
                    admin.ajaxRequest({
                        mguniqueurl: 'action/getTemplateOptions',
                        templateName: event.target.dataset.tempName
                    },
                        function (response) {
                            const arrayFunctions = [];
                            for (let key in response.data) {
                                if (key === 'installTemplatePlugins' && !settings_template.pluginsTemplateList) continue;
                                if (response.data[key] === 1) arrayFunctions.push(key);
                            }
                            settings_template.runOptions(arrayFunctions);
                        });
                });
        },

        /** 
         * Метод отправляет запрос на сервер на смену шаблона (если модалку открывали из публички)
         * @param {HTMLElement} event - событие клика по кнопке "Применить"
        */
        activateTemplateFromAdmin: function (event) {
            // Выбираем в скрытом селекте (в старой форме) выбранный шаблон
            const select = document.querySelector('select[name="templateName"]');
            select.value = event.target.dataset.tempName;
            // Меняем цветовую схему, доступную для выбранного шаблона
            const selectedOption = select.querySelector('[value="' + event.target.dataset.tempName + '"]');
            settings_template.drawColorShemes($(selectedOption));
            $('#tab-template-settings .openQuickstartModal').hide();
            // Применяем измененные настройки шаблона
            settings_template.selectTemplate();
        },

        /**
         * Метод показывает прелоадер на указанном блоке и блокирует клики по элементам на странице
         * @param {boolean} isBlock - Если true - показываем прелоадер, false - скрываем все прелоадеры на странице
         * @param {string} text - Текст, который будет выводиться под прелоадером
         * @param {HTMLElement} element - элемент, который будет закрывать прелоадер
         */
        preloader: function (isBlock, text, element) {
            if (isBlock === true) {
                preloader.create([text], element);
                body.classList.add('_block-click');
                popupInstallTemplate.classList.add('_block-click');
                return;
            }
            body.classList.remove('_block-click');
            popupInstallTemplate.classList.remove('_block-click');
            // Удаляем все прелоадеры
            const renderPreloader = document.querySelector('.render-preloader'),
                preloaderWrap = document.querySelector('.preloader-wrap'),
                node = document.querySelector('.preloader-mode'),
                bodyPreloader = document.querySelector('body > .render-preloader');
            if (renderPreloader) renderPreloader.remove();
            if (preloaderWrap) preloaderWrap.remove();
            if (node) node.classList.remove("preloader-mode");
            if (bodyPreloader) {
                setTimeout(() => {
                    bodyPreloader.remove();
                }, 2000);
            }
        },
    };

    return modalTemplates;
}

const modalTemplates = Object.assign({}, ModalTemplates());

setTimeout(modalTemplates.initModal, 0);