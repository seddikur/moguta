var marketplaceModule = (function() {
	
	return { 
		init: function() {

			$('.section-marketplace').on('mouseover', '.templatetta', function() {
				$(this).find('img').attr('src', $(this).data('img'));
			});
			$('.section-marketplace').on('click', '.js-do-search', function() {
				marketplaceModule.applyFilter();
			});
			$('.section-marketplace').on('change', '[name=mpFilter], [name=mpFilterType]', function() {
				if ($(this).attr('name') == 'mpFilter' && $(this).val() == 'main') {
					$('.section-marketplace [name=mpFilterType]').val('all');
					$('.section-marketplace [name=mpFilterName]').val('');
				}
				if (($(this).attr('name') == 'mpFilterType' && $('.section-marketplace [name=mpFilter]').val() == 'main') && $(this).val() != 'all') {
					$('.section-marketplace [name=mpFilter]').val('all');
				}

				setTimeout(function() {
					marketplaceModule.applyFilter();
				}, 1);
			});
			$('.section-marketplace').on('keypress', 'input[name=mpFilterName]', function(e) {
				if(e.keyCode==13) {
					marketplaceModule.applyFilter();
				}
			});

			$('.section-marketplace').on('click', '.showMore', function() {
				$('.section-marketplace [name=mpFilter]').val($(this).data('tagid')).trigger('change');
				window.scrollTo(0, 0);
			});

			$('.section-marketplace').on('click', '.resetMpCache', function() {
				admin.ajaxRequest({
					mguniqueurl:"action/resetMpCache"
				},
				function(response) {
					if (response.status == 'success') {
						admin.indication(response.status, 'Каталог обновлен');
					}
					else{
						admin.indication(response.status, 'При обновлении каталога произошла ошибка');
					}
					marketplaceModule.applyFilter();
				});
			});
			$('.section-marketplace').on('click', '.startTrial, .installPlugin, .addFreePlugin', function() {
				var trial = 'no';
				if ($(this).hasClass('startTrial')) {trial = 'yes';}
				var type = $(this).data('mptype');
				admin.ajaxRequest({
					mguniqueurl:"action/mpInstallPlugin",
					code: $(this).closest('.item-container').data('mpcode'),
					trial: trial
				},
				function(response) {
					if (response.status == 'success') {
						if (trial == 'yes') {
							admin.indication(response.status, 'Установлена пробная версия, включить можно в разделе "Плагины"');
						}
						else{
							if (type == 'p') {
								admin.indication(response.status, 'Плагин установлен, вы можете включить его в разделе "Плагины"');
							}
							if (type == 't') {
								admin.indication(response.status, 'Шаблон установлен, вы можете включить его в разделе "Настройки"->"Шаблоны"');
							}
						}
						admin.closeModal('.section-marketplace #mp-descr-modal');
						if ($('.section-marketplace select[name="mpFilter"]').val() == 'main') {
							admin.show("marketplace.php", cookie("type"), "mpFilter=main", marketplaceModule.init);
						}
						else{
							marketplaceModule.applyFilter();
						}
					}
					else{
						admin.closeModal('.section-marketplace #mp-descr-modal');
						admin.indication(response.status, 'При установке произошла ошибка');
						window.scrollTo(0, 0);
						setTimeout(function () {
							window.location.reload(true);
						}, 1500);
					}
				});
			});

			$('.section-marketplace').on('click', '.showMpDescr', function() {
				var tr = $(this).closest('tr');
				var title = tr.find('.title').html();
				var price = tr.find('td.price').html();
				var button = tr.find('.actions .buttons').html();
				
				admin.ajaxRequest({
					mguniqueurl:"action/mpGetDescr",
					code: $(this).closest('.item-container').data('mpcode')
				},
				function(response) {
					if (response.status == 'error') {return false;}
					$('.section-marketplace #mp-descr-modal .reveal-header h2 span').html(title);
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container').data('mpcode', tr.data('mpcode'));
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container').data('folder', tr.data('folder'));
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container img').attr('src', response.data.img).attr('alt', title);
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container .price').html('').html(price);
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container .buttons').html('').html(button);
					$('.section-marketplace #mp-descr-modal .reveal-body .descrContainer').html(response.data.description);
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container .buttons .sellPlugin').append('Выключить');
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container .buttons .deactivePlugin').append('Выключить');
					$('.section-marketplace #mp-descr-modal .reveal-body .item-container .buttons a').addClass('button');
					if (tr.hasClass('plugin-settings-on')) {
						$('.section-marketplace #mp-descr-modal .reveal-body .item-container').addClass('plugin-settings-on');
					}
					admin.openModal('.section-marketplace #mp-descr-modal');
				});
			});
			
			// Переход в маркетплэйс (фильтр = все плагины)
			$('#show-all-available-plugins').on('click', function () {
				includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
				admin.show("marketplace.php", cookie("type"), "mpFilter=all", marketplaceModule.init);
			});
		},

		applyFilter: function() {
			var mpFilter = $('.section-marketplace [name=mpFilter]').val();
			var mpFilterName = '';
			var mpFilterType = '';

			if ($('.section-marketplace [name=mpFilterName]').val()) {
				mpFilterName = '&mpFilterName='+$('.section-marketplace [name=mpFilterName]').val();
				if (mpFilter == 'main') {mpFilter = 'all';}
			}
			if ($('.section-marketplace [name=mpFilterType]').val()) {
				mpFilterType = '&mpFilterType='+$('.section-marketplace [name=mpFilterType]').val();
			}
			admin.show("marketplace.php", cookie("type"), "mpFilter="+mpFilter+mpFilterName+mpFilterType, marketplaceModule.init);
		}
	};
})();