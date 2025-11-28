$(document).ready(function () {
    var currBtn = $('#js-currency-select');

    currBtn.change(function () {
        $.ajax({
            type: "GET",
            url: mgBaseDir + "/ajaxrequest",
            data: {
                userCustomCurrency: currBtn.val()
            },
            success: function (response) {
                window.location.reload();
            }
        });
    });
});
