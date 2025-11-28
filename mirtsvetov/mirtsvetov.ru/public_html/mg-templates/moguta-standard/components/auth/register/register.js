$('.js-agreement-checkbox-register-btn').on('change', function () {
  $(this).toggleClass('active');
  if ($(this).parents('.registration__form').length) {
    
      const regButton = $(this).parents('.registration__form').find('.register-btn');
      if ($(this).hasClass('active'))
          regButton.attr('disabled', false);
      else
          regButton.attr('disabled', true);

      return;
  };
});