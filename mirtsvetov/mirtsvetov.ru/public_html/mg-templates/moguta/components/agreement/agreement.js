$(document).ready(function () {
    if (window.agremmentAdd) {
        return;
    }
    window.agremmentAdd = true;
    // Полифилл для тега dialog
    var dialogs = document.querySelectorAll('.agreement');
    dialogs.forEach(element => {
        const dialog = element.querySelector('.js-agreement-modal');
        if (!dialog) return;
         dialogPolyfill.registerDialog(dialog);

        var btnOpenSelector = '.js-open-agreement';
        var btnCloseSelector = '.js-close-agreement';

        // открытие модалки с соглашением на обработку пользовательских данных
        $(element).find(btnOpenSelector).on('click', function (e) {
            e.preventDefault();

            if (dialog.length < 1) {
                $.ajax({
                    type: "GET",
                    url: mgBaseDir + "/ajaxrequest",
                    data: {
                        layoutAgreement: 'agreement'
                    },
                    dataType: "HTML",
                    success: function (response) {
                        $('body').append(response);
                    }
                });
            } else {
                // modalOverlay.show();
                dialog.showModal();
            }
        });

        // закрытие модалки с соглашением на обработку пользовательских данных
        $(element).find(btnCloseSelector).on('click', function (e) {
            e.preventDefault();

            // modalOverlay.hide();
            dialog.close();
        });
    });

});