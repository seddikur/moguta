$(document).ready(function() {
    // добавление в избранное
    var counter = $('.js-favourite-count'),
        informer = $('.js-favorites-informer'),
        informerOpenedClass = 'favourite--open',
        btnAddClass = '.js-add-to-favorites',
        btnRemoveClass = '.js-remove-to-favorites';


    $('body').on('click', btnAddClass, function () {
        obj = $(this);
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/favorites/",
            data: {'addFav': '1', 'id': $(this).data('item-id')},
            dataType: "json",
            cache: false,
            success: function (response) {
                obj.hide();
                obj.parent().find(btnRemoveClass).show();
                counter.show();
                counter.html(response);
                informer.fadeOut('normal').fadeIn('normal');
                informer.removeClass(informerOpenedClass);

                setTimeout(function () {
                    informer.addClass(informerOpenedClass);
                }, 0);
            }
        });
    });

// удаление из избранного
    $('body').on('click', btnRemoveClass, function () {
        obj = $(this);
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/favorites/",
            data: {'delFav': '1', 'id': $(this).data('item-id')},
            dataType: "json",
            cache: false,
            success: function (response) {
                obj.hide();
                informer.fadeOut('normal').fadeIn('normal');
                obj.parent().find(btnAddClass).show();
                counter.html(response);
            }
        });
    });
});


