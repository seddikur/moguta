var tabs = {
    controlBtns: document.querySelectorAll('.js-open-tab'),
    contentBlocks: document.querySelectorAll('.js-tab-content'),
    init: function () {
        this.controlBtns.forEach(function (controlBtn) {
            controlBtn.addEventListener('click', tabs.openTab);
        });
    },
    openTab: function (e) {
        var btnClicked = e.currentTarget;
        var tabToOpenId = btnClicked.getAttribute('aria-controls');
        var tabToOpen = document.getElementById(tabToOpenId);

        tabs.controlBtns.forEach(function (btn) {
            var isSelected = btn.getAttribute('aria-selected');
            if (isSelected === 'true') {
                btn.setAttribute('aria-selected', 'false');
                btn.setAttribute('tabindex', '0');
                btn.classList.remove('c-tab__link--active');
            }
        });

        btnClicked.setAttribute('aria-selected', 'true');
        btnClicked.setAttribute('tabindex', '1');
        btnClicked.classList.add('c-tab__link--active');

        tabs.contentBlocks.forEach(function (tab) {
            if (!tab.hasAttribute('hidden')) {
                tab.setAttribute('hidden', true);
            }
            if (tab.id === tabToOpenId) {
                tab.removeAttribute('hidden');
            }
        });

        tabToOpen.removeAttribute('hidden');
    }
};
document.addEventListener("DOMContentLoaded", function () {
    tabs.init();
});