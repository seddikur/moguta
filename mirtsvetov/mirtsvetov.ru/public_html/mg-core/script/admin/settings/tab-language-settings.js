var settings_language = (function () {
	return {
		init: function() {
			admin.sortableMini('.language-tbody');
			settings_language.initEvents();
		},
		initEvents: function() {
			// сохранение языков
			$('#tab-language-settings').on('click', '.save-button', function() {
				$('.section-settings #tab-language-settings [name=short]').each(function(index) {
					$(this).val($(this).val().replace(/(^\s+|[^a-zA-Z0-9 -]+|\s+$)/g,"").replace(/\s+/g, "-"));
				});
				settings_language.save();
			});

			// удаление языка
			$('#tab-language-settings').on('click', '.delete-lang', function() {
				if(!confirm('Удалить? Будут удалены все переводы!')) {return false;}
				$(this).parents('tr').remove();
			}); 

			// добавление нового языка
			$('#tab-language-settings').on('click', '.add-new-lang', function() {
				$('#tab-language-settings tbody').append($('#tab-language-settings .lang-template').val());
				$('#tab-language-settings tbody .toDel').remove();
			});

			// переключение активности и вывода в публичке
			$('#tab-language-settings').on('click', '.js-lang-active, .js-lang-enabled', function() {
				$(this).toggleClass('active');
			});

			// изменение ссылки при вводе
			$('#tab-language-settings').on('input', '[name=short]', function() {
				var url = mgBaseDir+'/'+$(this).val()+'/';
				$(this).closest('tr').find('.js-langUrl').attr('href', url).text(url);
			});

			// общий переключатель вывода в публичке
			$('#tab-language-settings').on('click', '#printMultiLangSelector', function() {
				admin.ajaxRequest({
					mguniqueurl: "action/onOffMultiLang",
					data: $('#tab-language-settings #printMultiLangSelector').prop('checked')
				},
				function(response) {
					admin.indication(response.status, response.msg);
				});
			});

			// переход в настройки локалей шаблона
			$('#tab-language-settings').on('click', '.toLocales', function() {
				settings.allowTemplateAutoClicks = false;
				$('.section-settings #tab-template a').click();
				$('.section-settings .template-tabs-menu a[data-target="#ttab9"]').click();
				setTimeout(function() {
					if ($('.file-template:visible:first').length) {
						$('.file-template:visible:first').click();
					} else{
						$('.CodeMirror').remove();
						$('.save-file-template').hide();
						$('#codefile').hide();
					}
					settings.allowTemplateAutoClicks = true;
				}, 0);
			});
		},
		save: function() {
			var error = false;
			var data = [];
			var shorts = [];
			for(i = 0; i < $('#tab-language-settings tbody tr').length; i++) {
				var val = $('#tab-language-settings tbody tr:eq('+i+') [name=short]').val();
				if(val == '' || val == 'default' || val == 'lang' || val == 'none' || $.inArray(val.toLowerCase(), shorts) > -1) {
					$('#tab-language-settings tbody tr:eq('+i+') [name=short]').addClass('error');
					error = true;
					continue;
				} else {
					$('#tab-language-settings tbody tr:eq('+i+') [name=short]').removeClass('error');
					shorts.push(val.toLowerCase());
				}
				
				data[i] = {
					'short':   val,
					'full':    $('#tab-language-settings tbody tr:eq('+i+') [name=full]').val(),
					'active':  $('#tab-language-settings tbody tr:eq('+i+') .js-lang-active').hasClass('active')?'true':'false',
					'enabled': $('#tab-language-settings tbody tr:eq('+i+') .js-lang-enabled').hasClass('active')?'true':'false',
				};
			}
			if(error) {
				$([document.documentElement, document.body]).animate({
					scrollTop: $("#tab-language-settings .error:first").offset().top
				}, 1000);
				return false
			};
			admin.ajaxRequest({
				mguniqueurl: "action/saveLang",
				data: data
			},
			function(response) {
				admin.indication(response.status, response.msg);
			});
		},
	};
})();