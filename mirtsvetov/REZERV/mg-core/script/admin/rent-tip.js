$('body').on('click', '.admin-list__tip .admin_tip__close', function () {
    var expires = new Date();
    expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));//1 день
    document.cookie = 'admintip_closed=1;expires=' + expires.toUTCString()+"; path=/";
    $('.admin-list__tip').hide();
});
$(document).ready(function() {
    $('.admin-list__tip').delay(2000).fadeIn('slow');
});