$(document).ready(function () {
	$('.mobile-search input').on('input', function () {
		var search = $(this).val();
		var grid = $('.products-wrapper .grid');
		if( search != "")
		{
			$(grid).find(".product-wrapper").hide();
			$(grid).find(".product-wrapper:contains-ci('" + search + "')").show();
		}
		else
		{
			$(grid).find('.product-wrapper').show();
		}		
	});
	
	$.extend($.expr[":"], 
	{
		"contains-ci": function(elem, i, match, array) 
		{
			return (elem.textContent || elem.innerText || $(elem).text() || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
		}
	});
	
	
	
	
	
	
	var top = $(window).scrollTop()
	if (top > 50) {
		$('header').addClass('fixed')
	} else {
		$('header').removeClass('fixed')
	}
	
	$(window).scroll(function () {
		var top = $(window).scrollTop()
		if (top > 50) {
			$('header').addClass('fixed')
		} else {
			$('header').removeClass('fixed')
		}
	})
	
	
	$('.logo').on('click', function () {
		$('.mobile-menu').addClass('show');
		$('.logo').addClass('active').fadeOut(300);
		$('.menu-close').addClass('show');
	});
	$('.menu-close').on('click', function () {
		$('.mobile-menu').removeClass('show');
		$('.menu-close').removeClass('show');
		$('.logo').fadeIn(300).removeClass('active');
	});
	

	$('.spoiler-title').on('click', function () {
		$(this).closest('.spoiler').toggleClass('active')
		$(this).next('.spoiler-content').slideToggle(250)
	});
	
	$('body').on('click', '.tab-link', function() {
		var tab = $(this).data('tab');
		$('.tab-link, .tab').removeClass('active');
		$('.tab-link[data-tab="'+ tab +'"], .tab[data-tab="'+ tab +'"]').addClass('active');
	});
	
	$('.scroll-link').click(function () {
		$('html, body').animate(
			{
				scrollTop: $($(this).attr('href')).offset().top + 50 + 'px',
			},
			800
		)
		return false
	})
	
	$('.mobile-search .clear').on('click', function () {
		$('.mobile-search input').val('');
		$('.products-wrapper .grid .product-wrapper').show();
	});

	$('body').on('click', '.show-modal', function () {
		var modal = $(this).attr('data-modal');
		$('.modal[data-modal=' + modal + ']').addClass('show');
		$('body').addClass('lock');
		$('.overlay').fadeIn(250);
	});

	$('body').on('click', '.modal .close, .overlay, .filter-form .close', function () {
		$('.modal').removeClass('show');
		$('.filter-form').removeClass('show');
		$('body').removeClass('lock');
		$('.overlay').fadeOut(250);
	});
	
});
