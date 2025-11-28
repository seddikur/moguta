$(document).ready(function () {
    // Блокируем скролл body
    $('body').addClass('l-body_overflow_hidden');

    var compareMain = $('.js-compare-page'), // Главный контейнер
        compareItem = $('.js-compare-item'), // Блок товара в сравнении
        scrollContainer = $('.js-scroll-container'); // Блок, к которому будем добавлять двойную прокрутку

    // Если в сравнении нет ни одного товара, скрываем главный контейнер
    if (compareItem.length === 0) {
        compareMain.hide();
    }

    // Добавляем скролл-бар и сверху и снизу блока сравнения
    function DoubleScroll(element) {
        var scrollbar = document.createElement('div');
        scrollbar.setAttribute("class", "second-scroll");
        scrollbar.appendChild(document.createElement('div'));
        scrollbar.style.overflow = 'auto';
        scrollbar.style.overflowY = 'hidden';
        scrollbar.firstChild.style.width = element.scrollWidth + 'px';
        scrollbar.firstChild.style.paddingTop = '1px';
        scrollbar.firstChild.appendChild(document.createTextNode('\xA0'));
        scrollbar.onscroll = function () {
            element.scrollLeft = scrollbar.scrollLeft;
        };
        element.onscroll = function () {
            scrollbar.scrollLeft = element.scrollLeft;
        };
        var scrollbarWrap = document.createElement('div');
        scrollbarWrap.classList.add('second-scroll__wrap');
        scrollbarWrap.appendChild(scrollbar);
        element.parentNode.insertBefore(scrollbarWrap, element);
    }

    DoubleScroll(scrollContainer[0]);
    positionsСharacteristicsWithNames();
});

/**
* Функция позиционирует названия характеристик напротив самих характеристик
*/
function positionsСharacteristicsWithNames() {
    const namesBlock = document.querySelector('.c-compare__table__left');
    const goodsBlock = document.querySelector('.c-compare__table__right');
    if (!namesBlock || !goodsBlock) return;
    const nameRows = namesBlock.querySelectorAll('.c-compare__column');
    const goodRows = goodsBlock.querySelectorAll('.c-compare__row');

    for (let i = 0; i < nameRows.length; i++) {
        if (i === 0) {
            nameRows[i].style.height = goodRows[i].getBoundingClientRect().height - 1 + 'px';
            continue;
        }
        nameRows[i].style.height = goodRows[i].getBoundingClientRect().height + 'px';
    }

    namesBlock.style.height = goodsBlock.getBoundingClientRect().height + 'px';
}