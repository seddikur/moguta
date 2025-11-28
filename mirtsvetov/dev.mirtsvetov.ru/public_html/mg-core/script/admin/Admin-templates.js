function AdminTemplates() {
    const
        body = document.body;

    let templatesCards = null,
        templateInfoCard = null,
        iframe = null;

    const adminTemplates = {
        /** 
         * Метод запускает функции, необходимые для работы модального окна со сменой шаблона
        */
        init: function () {
            templatesCards = document.querySelector('.js-templates-cards');
            if (!templatesCards) return;
            templateInfoCard = templatesCards.querySelector('.js-template-active-info');
            iframe = document.querySelector('.js-templates-block__iframe');
            adminTemplates.bindEvents();
            adminTemplates.changeWidthTemplateInfoCard();
        },

        /** 
         * Метод вешает события на HTML-элементы, необходимые для работы модального окна
        */
        bindEvents: function () {
            // Делегирование события клика всему блока с шаблонами
            templatesCards.onclick = (e) => {
                // Если нажали на кнопку "Демо"
                if (e.target.classList.contains('js-templates-block__demo-button')) {
                    e.preventDefault();
                    adminTemplates.openDemo(e.target);
                }
                // Если нажали на кнопку "Закрыть" в модальном окне демо
                if (e.target.classList.contains('js-templates-iframe__close')) {
                    e.preventDefault();
                    adminTemplates.closeDemo();
                }
                // Если нажали на кнопку "Применить"
                if (e.target.classList.contains('js-templates-block__activation')) {
                    e.preventDefault();
                    adminTemplates.activateTemplateFromAdmin(e);
                }
                // Если нажали на кнопку "Цветовая схема"
                if (e.target.classList.contains('js-settings-template')) {
                    e.preventDefault();
                    e.target.classList.toggle('_active');
                    $(e.target).parent().find('.setting-template-container__wrapper').slideToggle();
                }
                // Если нажали внутри блока со цветовой схемой или вне него
                if (e.target.classList.contains('applyCustomColor') || e.target.classList.contains('color-scheme') && !e.target.classList.contains('popup-holder')) {
                    $(e.target).closest('.setting-template-container__wrapper').slideUp();
                    e.target.closest('.setting-template-container').querySelector('.js-settings-template').classList.remove('_active');
                } else if (!e.target.closest('.setting-template-container')) {
                    $('.setting-template-container__wrapper').slideUp();
                    document.querySelector('.setting-template-container .js-settings-template').classList.remove('_active');
                }
            };
            window.addEventListener('resize', () => {
                adminTemplates.changeWidthTemplateInfoCard();
            });
            const checkUpdateTmplBtn = document.querySelector('.js-loadVersions');
            if (!checkUpdateTmplBtn) return;
            checkUpdateTmplBtn.onclick = () => {
                settings_template.getVersionTemplate();
            };
        },

        changeWidthTemplateInfoCard: function () {
            const tabTemplateBtn = document.querySelector('#tab-template');
            if (!tabTemplateBtn) return;
            if (!tabTemplateBtn.classList.contains('is-active')) {
                return;
            }
            if (window.innerWidth < 830) {
                templateInfoCard.style.maxWidth = '100%';
                return;
            }
            const distanceBetweenCards = adminTemplates.calcDistanceBetweenCards();
            const cardWidth = templatesCards.querySelector('.js-templates-card').offsetWidth;
            const widthTemplateInfoCard = cardWidth * 2 + distanceBetweenCards;
            templateInfoCard.style.maxWidth = `${widthTemplateInfoCard}px`;

            adminTemplates.changeDistanceBetweenBottomCards(distanceBetweenCards);
        },

        calcDistanceBetweenCards: function () {
            const templateCards = templatesCards.querySelectorAll('.js-templates-card');
            let firstCardBorderRight = null;
            let secondCardBorderLeft = null;
            let distanceBetweenCards = null;
            const templateInfoCardBorderBottom = templateInfoCard.getBoundingClientRect().bottom;
            let counter = 1;
            for (let i = 0; i < templateCards.length; i++) {
                if (templateCards[i].getBoundingClientRect().top < templateInfoCardBorderBottom) {
                    continue;
                }
                if (counter === 1) {
                    firstCardBorderRight = templateCards[i].getBoundingClientRect().right;
                }
                if (counter === 2) {
                    secondCardBorderLeft = templateCards[i].getBoundingClientRect().left;
                }
                counter++;
                if (counter > 2) {
                    distanceBetweenCards = secondCardBorderLeft - firstCardBorderRight;
                    break;
                }
            }
            return distanceBetweenCards;
        },

        changeDistanceBetweenBottomCards: function (distance) {
            const templateCards = templatesCards.querySelectorAll('.js-templates-card');
            const templateCardsBorderBottom = templatesCards.getBoundingClientRect().bottom;
            let counter = 1;
            for (let i = 0; i < templateCards.length; i++) {
                const cardBorderBottom = templateCards[i].getBoundingClientRect().bottom;
                if (templateCardsBorderBottom - cardBorderBottom > 200) continue;
                if (counter === 1) {
                    templateCards[i].style.marginRight = `${distance / 2}px`;
                    counter++;
                    continue;
                }
                if (i === templateCards.length - 1) {
                    templateCards[i].style.marginLeft = `${distance / 2}px`;
                    break;
                }
                templateCards[i].style.marginRight = `${distance / 2}px`;
                templateCards[i].style.marginLeft = `${distance / 2}px`;
            }
        },

        /** 
         * Метод открывает демо-сайт в iframe
         * @param {HTMLElement} button - кнопка "Демо", по которой произошел клик
        */
        openDemo: function (button) {
            if (!button.dataset.srcDemo) return;
            const iframeWrap = templatesCards.querySelector('.js-templates-iframe');
            adminTemplates.preloader(true, lang['TEMPLATE_INSTALLING_DEMO'], iframeWrap);
            templatesCards.classList.add('_open-demo');
            document.querySelector('.js-templates-iframe__name').textContent = button.dataset.tempName;
            setTimeout(() => {
                body.classList.add('lock');
                iframe.src = button.dataset.srcDemo;
                $(iframe).on('load', () => {
                    adminTemplates.preloader(false);
                    document.addEventListener('keydown', adminTemplates.closeDemoPressEscape);
                });
            }, 500);
            
        },

        /** 
         * Метод закрывает демо-сайт в iframe
        */
        closeDemo: function () {
            body.classList.remove('lock');
            templatesCards.classList.remove('_open-demo');
            document.querySelector('.js-templates-iframe__name').textContent = '';
            setTimeout(() => {
                iframe.src = '';
                document.removeEventListener('keydown', adminTemplates.closeDemoPressEscape);
            }, 500);
        },

        closeDemoPressEscape: function (e) {
            if (e.key === 'Escape') {
                adminTemplates.closeDemo();
            }
        },

        /**
         * Метод отправляет запрос на страницу. Необходим для отправки запроса перед активацией шаблона-конструктора, чтобы сформировалось дерево нод
         */
        pingMainPage: function () {
            $.ajax({
                type: "GET",
                cache: false,
                url: mgBaseDir
            });
        },

        /** 
         * Метод отправляет запрос на сервер на смену шаблона
         * @param {event} event - событие клика по кнопке "Применить"
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
            const activeTemplate = templatesCards.querySelector('.js-templates-card._active');
            const selectedTemplateCard = event.target.closest('.js-templates-card');
            const adminCard = document.querySelector('.js-admin-template-card');
            if (activeTemplate) {
                activeTemplate.classList.remove('_active');
                activeTemplate.querySelector('.js-templates-block__activation').textContent = lang['APPLY'];
            }
            if (selectedTemplateCard) {
                selectedTemplateCard.classList.add('_active');
                selectedTemplateCard.querySelector('.js-templates-block__activation').innerHTML = `<i class="fa fa-check" aria-hidden="true"></i> ${lang['marketplace_installed']}`;
            }
            const cardInfoImgContainer = adminCard.querySelector('.templates-cards__card-image');
            let noImgClass = event.target.closest('.js-installed-template');
            if (noImgClass) noImgClass = noImgClass.querySelector('.no-img');
            if (noImgClass) {
                cardInfoImgContainer.classList.add('no-img');
            } else {
                cardInfoImgContainer.classList.remove('no-img');
            }
            adminCard.querySelector('.js-admin-template-card__title').textContent = event.target.dataset.tempName;
            adminCard.querySelector('.js-getVersion').dataset.tempName = event.target.dataset.tempName;
            adminCard.querySelector('img').src = selectedTemplateCard.querySelector('img').src;
            adminCard.querySelector('img').srcset = selectedTemplateCard.querySelector('img').srcset;
            // Скрываем из карточки активного шаблона информацию о версии шаблона
            setTimeout(() => {
                adminCard.querySelector('.templates-cards__card-image').classList.remove('_mini', '_mini-upd');
                $('.js-installed-template .templates-cards__card-image').removeClass('_mini-upd');
            }, 500);
            $('.js-admin-template-card .js-admin-template-card__update').slideUp();
            $('.js-admin-template-card .js-getVersion').slideUp();
            $('.js-installed-template .js-admin-template-card__update').slideUp();
            $('.js-installed-template .js-getVersion').slideUp();
            $('.js-installed-template .js-templates-block__activation').removeClass('_hide');
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
    };

    return adminTemplates;
}

const adminTemplates = Object.assign({}, AdminTemplates());

setTimeout(adminTemplates.init, 0);