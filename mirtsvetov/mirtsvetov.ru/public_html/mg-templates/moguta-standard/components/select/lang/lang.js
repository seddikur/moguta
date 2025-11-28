document.addEventListener("DOMContentLoaded", function() {
    var changeLang = function(event) {
        var select = event.target;
        
        window.location.href = select.options[select.selectedIndex].value;
    };
    $('.js-lang-select').on('change', changeLang)
});