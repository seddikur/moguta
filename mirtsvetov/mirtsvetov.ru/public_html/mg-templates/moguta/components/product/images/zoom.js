$(document).ready(function () {
    if ($('.js-zoom-img').length > 0) {
        $('.js-zoom-img').on('mousemove', zoom);
    }
});

// Увеличение главного изображения при наведении
function zoom(e) {
    var zoomer = e.currentTarget;
    var offsetX, offsetY, x, y;
    offsetX = e.offsetX;
    offsetY = e.offsetY;
    // e.offsetX ? offsetX = e.offsetX : offsetX = e.touches[0].pageX;
    // e.offsetY ? offsetY = e.offsetY : offsetX = e.touches[0].pageX;

    x = offsetX / zoomer.offsetWidth * 100;
    y = offsetY / zoomer.offsetHeight * 100;

    zoomer.style.backgroundPosition = x + '% ' + y + '%';
}