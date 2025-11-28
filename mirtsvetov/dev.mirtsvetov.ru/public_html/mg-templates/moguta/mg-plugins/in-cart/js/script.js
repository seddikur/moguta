var InCartModule = (function() {
	return {
		pluginInCartText: 'В корзине',
		init: function() { 

			if (typeof locale != 'undefined' && locale.pluginInCartText) {
				InCartModule.pluginInCartText = locale.pluginInCartText;
			}

			var initialBuyButton = $('.product-wrapper:first .addToCart').text();
			if (typeof initialBuyButton === "undefined" || initialBuyButton === null || !initialBuyButton) {
				initialBuyButton = $('.property-form:first .addToCart').text();
			}

			$('.deleteItemFromCart').each(function(index,element) {
				$('.addToCart[data-item-id='+$(this).data('delete-item-id')+']').text(InCartModule.pluginInCartText).addClass('alreadyInCart');
			});

			$('body').on('click', '.addToCart', function() {
				$(this).text(InCartModule.pluginInCartText).addClass('alreadyInCart');
			});

			$('body').on('click', '.deleteItemFromCart', function() {
				$('.addToCart[data-item-id='+$(this).data('delete-item-id')+']').text(initialBuyButton).removeClass('alreadyInCart');
			});
		},
	};
})();
$(document).ready(function() {
	InCartModule.init();
});