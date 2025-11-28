var settings_interface = (function () {
	return {
		schemes: ['colorMain', 'colorLink', 'colorSave', 'colorBorder', 'colorSecondary', 'adminBar','adminBarFontColor'],

		init: function() {
			admin.includeColorPicker();

			//Инициализируем все пикеры
			for(i = 0; i < settings_interface.schemes.length; i++) {
				admin.clickPickr(settings_interface.schemes[i], true);
			}

			settings_interface.initEvents();
			$('#interface-settings .background-settings li[data-name="'+$('input[name="themeBackground"]').val()+'"]').addClass('textures__item_active');
		},
		initEvents: function() {
			// смена фона
			$('#interface-settings').on('click', '.background-settings .color-list li', function(){
				$('#interface-settings .background-settings li').removeClass('textures__item_active');
				$(this).addClass('textures__item_active');

				var bgContainer = $('.js-apply-bg');

				if ($(this).hasClass('customBackground')) {
					bgContainer.css({'backgroundImage':'url("'+$(this).attr('img')+'")'});
					$('input[name="themeBackground"]').val('customBackground');
				} else {
					var background = $(this).data('name');
					bgContainer.css({'backgroundImage':'url('+admin.SITE+'/mg-admin/design/images/bg_textures/'+background+')'});
					$('input[name="themeBackground"]').val(background);
				}
			});

			// сохранение и применение стилей
			$('#interface-settings').on('click', '.save-interface', function() {  
				settings_interface.save();
			});

			$('#interface-settings').on('click', '.default-interface', function() {  
				settings_interface.default();
			});

			//загрузка фона
			$('#interface-settings').on('change', 'input[name="customBackground"]', function() {
				var img_container = $(this).parents('.upload-img-block');
						
				if($(this).val()) {          
				  img_container.find('.imageform').ajaxForm({
					type:"POST",
					url: "ajax",
					data: {
					  mguniqueurl:"action/updateCustomAdmin"
					},
					cache: false,
					dataType: 'json',
					success: function(response) {
					  if (response.status=='error') {
						admin.indication(response.status, response.msg);
					  } 
					  else {
						var src = admin.SITE+'/uploads/'+response.data.img;
						var img = response.data.img.substring(12);

						img_container.find('#customBackground').attr('src', src).attr('fileName', img);
						$('.customBackground').css('backgroundImage', 'url("'+src+'")').attr('img', src).show();
					  }
					 }
				  }).submit();
				}
			});

			//загрузка логотипа
			$('#interface-settings').on('change', 'input[name="customAdminLogo"]', function() {
				var img_container = $(this).parents('.upload-img-block');
						
				if($(this).val()) {          
				  img_container.find('.imageform').ajaxForm({
					type:"POST",
					url: "ajax",
					data: {
					  mguniqueurl:"action/updateCustomAdmin"
					},
					cache: false,
					dataType: 'json',
					success: function(response) {
					  if (response.status=='error') {
						admin.indication(response.status, response.msg);
					  } 
					  else {
						var src = admin.SITE+'/uploads/'+response.data.img;
						var img = response.data.img.substring(12);

						img_container.find('#customAdminLogo').attr('src', src).attr('fileName', img);
						$('.customAdminLogoTrash').show();
						
					  }
					 }
				  }).submit();
				}
			});

			//сброс логотипа
			$('#interface-settings').on('click', '.customAdminLogoTrash', function() {
				$('.customAdminLogoTrash').hide();
				$('#customAdminLogo').attr('src', admin.SITE+'/mg-admin/design/images/logo-normal.png').attr('fileName', '');
			});
		},

		save: function() {
			var data = {};
			for(i = 0; i < settings_interface.schemes.length; i++) {
				data[settings_interface.schemes[i]] = $('.option[name="'+settings_interface.schemes[i]+'"]').val();
			}

			admin.ajaxRequest({
			  mguniqueurl:"action/saveInterface",
			  data: data,
			  bg: $('#bg').val(),
			  customBG: $('#customBackground').attr('fileName'),
			  customLogo: $('#customAdminLogo').attr('fileName'),
			  fullscreen: $('#bgfullscreen').prop('checked'),
			  languageLocale: $('select[name=languageLocale]').val()
			},
			function(response) {
			  location.reload();
			});
		},

		default: function() {
			admin.ajaxRequest({
			  mguniqueurl:"action/defaultInterface",
			},
			function(response) {
			  location.reload();
			});
		},
	};
})();