$(document).ready(function () {
    var currBtn = $('.js-currency-select');

    currBtn.change(function (e) {
        $.ajax({
            type: "GET",
            url: mgBaseDir + "/ajaxrequest",
            data: {
                userCustomCurrency: $(e.target).val()
            },
            success: function (response) {
                window.location.reload();
            }
        });
    });
});
