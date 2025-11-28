function Preloader() {
    _preloaderTextTimer = null;

    const preloader = {
        /** 
         * Создание прелоадера
         * @param textList - Массив с текстом, который должен выводится во время прелоадера
         * @param element - Элемент в который будет вставлятся прелоадер
         * @param isContent - Проверка, является ли контейнер макетом-контентом
         * @param {boolean} isContent - Проверка, является ли контейнер макетом-шапкой или макетом-подвалом
        */
        create(textList = [], element = "", isContent = false, isHeaderOrFooter = false) {
            const preloader = this.returnPreloaderHtml(isContent, element, isHeaderOrFooter);

            if (element) {
                let preloaderWrapTemplate = this.returnPreloaderWrap();
                element.classList.add('preloader-mode');
                element.insertAdjacentHTML("beforeend", preloaderWrapTemplate);
                const preloaderWrap = element.querySelector('.preloader-wrap');
                preloaderWrap.insertAdjacentHTML("beforeend", preloader);
                if (textList.length) this.createTextList(textList);
            } else {
                document.body.insertAdjacentHTML("beforeend", preloader);
                if (textList.length) this.createTextList(textList);
            }
        },

        /**
         * Возвращает обертку прелоадера
         * @returns {string} html-верстка обертки
         */
        returnPreloaderWrap() {
            return `<div class="preloader-wrap"></div>`;
        },

        /**
         * Возвращает верстку прелоадера
         * @param isElement - Элемент в который будет вставлятся прелоадер
         * @param isContent - Проверка, является ли контейнер макетом-контентом
         * @returns {string} html-верстка прелоадера
         */
        returnPreloaderHtml(isContent, isElement, isHeaderOrFooter) {
            const preloaderContentClass = isContent ? ' content' : '';
            const preloaderElemClass = isElement ? ' element' : '';
            const preloaderHeaderOrFooterClass = isHeaderOrFooter ? ' header-or-footer' : '';
            const preloaderClass = preloaderContentClass + preloaderElemClass + preloaderHeaderOrFooterClass;
            let preloaderStyle;
            const elementPosition = isElement ? isElement.getBoundingClientRect().left : null;
            if (elementPosition && preloaderHeaderOrFooterClass !== '') {
                preloaderStyle = ' style="left:-' + elementPosition + 'px;"';
            }

            const preloaderHtml = `<div class="render-preloader${preloaderClass}"${preloaderStyle}>
                <div class="render-preloader-text js-preloader">
                </div>
                <div class="render-preloader-element">
                    <div class="render-preloader-child"></div>
                    <div class="render-preloader-child"></div>
                    <div class="render-preloader-child"></div>
                </div>
                <div class="render-preloader-text-list js-text-list">
            
                </div>
            </div>`;
            return preloaderHtml;
        },

        /** 
         * Если был передан массив с текстом - генерируем фразы, указанные в массиве
         * @param textList - Массив с текстом, который должен выводится во время прелоадера
        */
        createTextList(textList) {
            const textListBlock = document.querySelector('.render-preloader .js-text-list');
                textList.map((text,index) => {
                    const textElement = `<div class="render-preloader-text-item ${(index === 0) ? "active" : ""}" 
                                            data-preloader-index="${index}">
                                            <span>${text}</span>
                                        </div>`;
                    textListBlock.insertAdjacentHTML("beforeend", textElement);
                });
                //Переменная, отвечающая за активную надпис по дата-атрибуту preloader-index
                let listCount = 0;
                //Кол-во блоков
                const maxLength = textList.length - 1;
                //Предыдущий элемент, который перестанет быть активным
                let prevElement = document.querySelector(`.render-preloader-text-item[data-preloader-index="${listCount}"]`);
                //Запускаем интервал, который будет менять текст
                _preloaderTextTimer = setInterval(() => {
                    listCount++
                    //Если кол-во больше максимума - обнуляем кол-во
                    if (listCount > maxLength) listCount = 0;
                    //Деактивируем предыдущий элемент
                    if (prevElement) prevElement.classList.remove('active');
                        
                    let preloaderItem = document.querySelector(`.render-preloader-text-item[data-preloader-index="${listCount}"]`);
                    if (!preloaderItem) {
                        clearInterval(_preloaderTextTimer);
                        return;
                    }
                    preloaderItem.classList.add('active');
                    // preloaderItem.scrollIntoView({behavior: "smooth"});
                    prevElement = preloaderItem;
                }, 1000);
    
        }
    }

    return preloader;
}

var preloader = Object.assign({}, Preloader());
