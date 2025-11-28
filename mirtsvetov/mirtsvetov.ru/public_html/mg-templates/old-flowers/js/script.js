$(document).ready(function () {
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
	
	
	if ($(window).width() > 767) {
		$('.logo').on('click', function () {
			$('.mobile-menu').addClass('show');
			$('.logo').addClass('active').fadeOut(300);
			$('.menu-close').addClass('show');
			return false;
		});
		$('.menu-close').on('click', function () {
			$('.mobile-menu').removeClass('show');
			$('.menu-close').removeClass('show');
			$('.logo').fadeIn(300).removeClass('active');
		});
	};
	

	$('.spoiler-title').on('click', function () {
		$(this).closest('.spoiler').toggleClass('active')
		$(this).next('.spoiler-content').slideToggle(250)
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

	$('body').on('click', '.show-modal', function () {
		var modal = $(this).attr('data-modal');
		$('.modal[data-modal=' + modal + ']').addClass('show');
		$('body').addClass('lock');
		$('.overlay').fadeIn(250);
	})

	$('body').on('click', '.modal .close, .overlay, .filter-form .close', function () {
		$('.modal').removeClass('show');
		$('.filter-form').removeClass('show');
		$('body').removeClass('lock');
		$('.overlay').fadeOut(250);
	})
	
});
