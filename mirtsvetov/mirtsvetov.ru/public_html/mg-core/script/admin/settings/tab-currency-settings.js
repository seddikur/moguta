var settings_currency = (function() {
	return {
		init: function() {
			admin.sortableMini('.currency-tbody', "settings_currency.save('123')");
			settings_currency.initEvents();
		},
		initEvents: function() {
			// редактирование
			$('#tab-currency-settings').on('click', '.edit-currency ', function() {
				var tr = $(this).parents('tr');
				$('.currency-tbody .actions').find('.save-row, .cancel-row').hide();
				$('.currency-tbody .actions').find('.edit-row').show();
				$(this).parents('.actions').find('.save-row, .cancel-row').show();
				$(this).parent().hide();
				settings_currency.editRow(tr);
			});

			// сохранение
			$('#tab-currency-settings').on('click', '.save-currency ', function() {
				$('.currency-field').hide();
				settings_currency.save();
			});
		 
			// добавление новой валюты.
			$('#tab-currency-settings').on('click', '.add-new-currency', function(){
				$('.currency-tbody .actions').find('.save-row, .cancel-row').hide();
				$('.currency-tbody .actions').find('.edit-row').show();	
				settings_currency.addRow();
			});
			
			// удаление 
			$('#tab-currency-settings').on('click', '.delete-row', function() {
				if (confirm(lang.DELETE_CURRENCY+' '+$(this).attr('id')+'?')) {
					$(this).parents('tr').remove();
					settings_currency.save();
				}
			});

			// отмена редактирования 
			$('#tab-currency-settings').on('click', '.cancel-row', function() {
				admin.refreshPanel();
			});

			// сохранение изменений	
			$('#tab-currency-settings').on('click', '.save-row', function() {
				var tr = $(this).parents('tr');
				var iso = $(tr).data('iso');
				var error = false;
				$(tr).find('input').each(function(){
					if ($(this).val()=='') {
						$(this).addClass('error-input');
						error = true;
					}
				});
				if (error) {
					admin.indication('error', lang.ERROR_EMPTY);
				} else {
					settings_currency.save(iso);
				}
			});

			$('#tab-currency-settings').on('click', '.visible', function() {
				$(this).children('.fa').toggleClass('active');
				var tr = $(this).parents('tr');
				var iso = $(tr).data('iso');
				var error = false;
				$(tr).find('input').each(function(){
					if ($(this).val()=='') {
						$(this).addClass('error-input');
						error = true;
					}
				});
				if (error) {
					admin.indication('error', lang.ERROR_EMPTY);
				} else {
					settings_currency.save(iso);
				}
			});

			// Сохранение настроек 
			$('#tab-currency-settings').on('click', '#printCurrencySelector', function() {
				admin.ajaxRequest({
					mguniqueurl: "action/onOffCurrency", // действия для выполнения на сервере
					data: $('#tab-currency-settings #printCurrencySelector').prop('checked')
				},
				function(response) {
					admin.indication(response.status, response.msg);
					admin.refreshPanel();
				});
			});
		},
				
		editRow: function(tr) {		
			$('.currency-tbody .currency-field').hide();
			$('.currency-tbody .view-value-curr').show();
			
			$(tr).find('.currency-field').show();
			$(tr).find('.view-value-curr').hide();
			if ($(tr).hasClass('none-edit')) {
			$(tr).find('.currency-field').hide();
			$(tr).find('.view-value-curr').show();
			$(tr).find('input[name=currency_short]').show();
			$(tr).find('input[name=currency_short]').parents('td').find('.view-value-curr').hide();
			}
		},
		addRow: function() {
			var activeLi = '';
			if ($('#tab-currency-settings #printCurrencySelector').prop('checked')) {
				activeLi = '<li class="visible"><button><i class="fa fa-lightbulb-o"></i></button></li>';
			}
			var row = '<tr data-iso="NEW">\
					<td class="mover"></td>\
					<td data-iso="">\
						<input type="text" name="currency_iso" value="" class="currency-field" style="display:none;margin-bottom:0;" placeholder="USD">\
					</td>'+
					'<td class="currency-rate">\
						<input type="text" name="currency_rate" value="" class="currency-field" style="display:none;margin-bottom:0;" placeholder="30">\
					</td>'+
					'<td class="currency-short">\
						<input type="text" name="currency_short" value="" class="currency-field" style="display:none;margin-bottom:0;" placeholder="$">\
					</td>\
					<td class="actions">\
						<ul class="action-list text-right">\
							<li class="save-row" id="NEW"><button class="tooltip--small tooltip--center" flow="rightUp" tooltip="'+lang.SAVE+'"><i class="fa fa-floppy-o"></i></button></li>\
							<li class="cancel-row" id=""><button class="tooltip--small tooltip--center" flow="rightUp" tooltip="'+lang.CANCEL+'"><i class="fa fa-times"></i></button></li>\
							<li class="edit-row" style="display:none" id="NEW"><button class="edit-currency tooltip--small tooltip--center" flow="rightUp" tooltip="Редактировать"><i class="fa fa-pencil"></i></button></li>\
							'+activeLi+'\
							<li class="delete-row" style="display:none" id=""><button class="tooltip--small tooltip--center" flow="rightUp" tooltip="'+lang.DELETE+'"><i class="fa fa-trash"></i></button></li>\
						</ul>\
					</td>\
				</tr>';
			$('.currency-tbody').prepend(row);
			var tr = $('.currency-tbody tr[data-iso="NEW"]');
			admin.initToolTip();
			settings_currency.editRow(tr);     
		},
		// сохраняет все валюты и их соотношения
		save: function(iso) {
			var data = [];
			$('.currency-tbody tr').each(function(index, row) {
				if ($(this).data('iso')==iso) {
					var pack = {
						iso: $(row).find('input[name=currency_iso]').val(),
						rate: $(row).find('input[name=currency_rate]').val().replace(/,/, '.').replace(/[^\.0-9]+/, ''),
						short: $(row).find('input[name=currency_short]').val(),
						active: $(row).find('.visible').find('.fa').hasClass('active')
					};					 
				} else {
					var pack = {
						iso: $(row).find('input[name=currency_iso]').data('value'),
						rate: $(row).find('input[name=currency_rate]').data('value'),
						short: $(row).find('input[name=currency_short]').data('value'),
						active: $(row).find('.visible').find('.fa').hasClass('active')
					};
				}
				data.push(pack);
			});

			// получаем с сервера все доступные пользовательские параметры
			admin.ajaxRequest({
				mguniqueurl: "action/saveCurrency",
				data: data
			},
			function(response) {
				admin.indication(response.status, response.msg);
				admin.refreshPanel();
			});
		}
	};
})();