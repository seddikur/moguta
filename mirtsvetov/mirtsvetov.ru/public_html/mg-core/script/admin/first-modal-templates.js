function FirstModalTemplates() {
    const
        body = document.body,
        popup = document.querySelector('#first-templates-popup'),
        iframe = document.querySelector('.js-first-templates-popup__iframe');

    const firstModalTemplates = {
        /** 
         * Метод запускает функции, необходимые для работы модального окна со сменой шаблона
        */
        initModal: function () {
            if (!popup) {
                return;
            }
            firstModalTemplates.bindEventsModal();
            // Показываем модалку с выбором шаблона
            setTimeout(() => {
                popup.classList.add('_show');
                document.body.classList.add('lock');
                const helpLabelBtn = document.querySelector('.js-assistant-label');
                if (helpLabelBtn) helpLabelBtn.classList.add('_hide');
            }, 2000);
        },

        /** 
         * Метод вешает события на HTML-элементы, необходимые для работы модального окна
        */
        bindEventsModal: function () {
            // Делегирование события клика всему модальному окну
            popup.addEventListener('click', (e) => {
                // Если нажали на элементы, закрывающие попап
                if (e.target && e.target.classList.contains('js-first-templates-popup__close')) {
                    e.preventDefault();
                    firstModalTemplates.closeModal();
                    return;
                }
                // Если нажали на кнопку "Применить"
                if (e.target && e.target.classList.contains('js-first-templates-popup__activation')) {
                    e.preventDefault();
                    firstModalTemplates.activateWithDemo(e);
                    return;
                }
                // Если нажали на кнопку "Закрыть" в модальном окне демо
                if (e.target && e.target.classList.contains('js-firsttemplates-iframe__close')) {
                    e.preventDefault();
                    firstModalTemplates.closeDemo();
                    return;
                }
                // Если нажали на кнопку "Демо"
                if (e.target && e.target.classList.contains('js-first-templates-popup__demo') || e.target.closest('.js-first-templates-popup__card')) {
                    e.preventDefault();
                    demoButton = e.target.closest('.js-first-templates-popup__card').querySelector('.js-first-templates-popup__demo');
                    firstModalTemplates.openDemo(demoButton);
                    return;
                }
            });

            // Закрываем попап при нажатии на клавишу Esc
            document.addEventListener('keydown', function (e) {
                const preloaderWrap = popup.querySelector('.preloader-wrap');
                if (preloaderWrap) return;
                const popupActive = document.querySelector('#first-templates-popup._show');
                if (e.key == 'Escape' && popupActive) {
                    if (popupActive.classList.contains('_open-demo')) {
                        firstModalTemplates.closeDemo();
                        return;
                    }
                }
            });
        },

        closeModal: function () {
            const helpLabelBtn = document.querySelector('.js-assistant-label');
            if (helpLabelBtn) helpLabelBtn.classList.remove('_hide');
            popup.classList.remove('_show');
            document.body.classList.remove('lock');
            if (window.innerWidth > 768) {
                assistant.toggleAssistantMainBlock();
            }
            firstModalTemplates.checkFirstStepUsCompleted();
            // Отправляем запрос на сервер, чтобы окно выбора шаблона при первом создании сайта больше не показывалось пользователю
            settings_template.isNoFirstAjax();
        },

        /** 
         * Метод открывает демо-сайт в iframe
         * @param {HTMLElement} button - кнопка "Демо", по которой произошел клик
        */
        openDemo: function (button) {
            if (!button.dataset.srcDemo) return;
            const iframeWrap = popup.querySelector('.js-first-templates-iframe');
            firstModalTemplates.preloader(true, 'Загружается демонстрационный сайт', iframeWrap);
            setTimeout(() => {
                firstModalTemplates.preloader(false);
            }, 5000);
            popup.classList.add('_open-demo');
            document.querySelector('.js-first-templates-iframe__name').textContent = button.dataset.tempName;
            setTimeout(() => {
                iframe.src = button.dataset.srcDemo;
            }, 500);
        },

        /** 
         * Метод закрывает демо-сайт в iframe
        */
        closeDemo: function () {
            popup.classList.remove('_open-demo');
            document.querySelector('.js-first-templates-iframe__name').textContent = '';
            setTimeout(() => {
                iframe.src = '';
            }, 500);
        },

        /** 
         * Метод отправляет запрос на сервер на смену шаблона (с переустановкой демо-базы)
         * Используется в публичке
         * @param {event} event - событие клика по кнопке "Установить демо-базу"
        */
        activateWithDemo: function (event) {
            window.selectedTemplate = event.target.dataset.tempName;
            firstModalTemplates.preloader(true, 'Подготовка к установке шаблона', popup);
            admin.ajaxRequest({
                mguniqueurl: 'action/saveTemplateSettings',
                settings: {
                    templateName: event.target.dataset.tempName,
                    colorScheme: event.target.dataset.color
                },
            },
                function (response) {
                    firstModalTemplates.checkFirstStepUsCompleted();
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
         * Метод показывает прелоадер на указанном блоке и блокирует клики по элементам на странице
         * @param {boolean} isBlock - Если true - показываем прелоадер, false - скрываем все прелоадеры на странице
         * @param {string} text - Текст, который будет выводиться под прелоадером
         * @param {HTMLElement} element - элемент, который будет закрывать прелоадер
         */
        preloader: function (isBlock, text, element) {
            if (isBlock === true) {
                preloader.create([text], element);
                body.classList.add('_block-click');
                return;
            }
            body.classList.remove('_block-click');
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

        /**
         * Отмечает в БД 1 этап обучения как выполненный
         */
        checkFirstStepUsCompleted: function() {
            admin.ajaxRequest({
                mguniqueurl: "action/updateStatusDashboard",
                noviceList: [1],
            });
        },
    };

    return firstModalTemplates;
}

const firstModalTemplates = Object.assign({}, FirstModalTemplates());

setTimeout(firstModalTemplates.initModal, 0);