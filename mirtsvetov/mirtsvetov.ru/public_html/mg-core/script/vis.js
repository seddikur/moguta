// const url = window.location.href;
// $.ajax({
//     type: "POST",
//     url: "/mg-visor",
//     data: {
//         type: 'loadPage',
//         data: url,
//     }
// });

// setInterval(function() {
//     $.ajax({
//         type: "POST",
//         url: "/mg-visor",
//         data: {
//             type: 'userOnPage',
//         }
//     });
// }, 60000);

// OLD LISTENER
// TODO body
// $('body').on('click', '[data-vis-click]', function(event) {
//     const visAction = $(event.target).data('vis-click');
//     if (visAction) {
//         $.ajax({
//             type: "POST",
//             url: "/mg-visor",
//             data: {
//                 type: 'click',
//                 data: visAction,
//             }
//         });
//     }
// });

// NEW LISTENTER
// TODO body
document.querySelector('body').addEventListener('click', function(event) {
    if (event.target.dataset.visClick) {
        const action = event.target.dataset.visClick;
        $.ajax({
            type: "POST",
            url: "/mg-visor",
            data: {
                type: 'click',
                data: action,
            }
        });
    }
}, true);
