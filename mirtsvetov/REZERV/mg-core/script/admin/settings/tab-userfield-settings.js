/**
 * Модуль работы с пользовательскими полями в админке
 */
var userProperty = (function() {

	var savedDataRow = {}; // данные редактируемой строки
	var tmpCount = 0; // для характеристик для товаров, которые еще не записаны в базу
	var cansel = false; // использовать возврат значений при отмене

	return {
		delimetr: "|",
		init: function() {
			// Показ типа фильтра
			$('#tab-userField-settings').on('change', '#propInFilter', function () {
				if ($(this).prop('checked') && $('#user-property-edit [name=type]') != 'size' && $('#user-property-edit [name=type]') != 'color') {
					$('#user-property-edit .hideOnsize').show();
				} else {
					$('#user-property-edit .hideOnsize').hide();
				}
			});

			if (cookie('showUserPropertyFilter') == "1") {
				$('.filters').show();
			}
			// Выделить все страницы
			$('#tab-userField-settings').on('click', '.check-all-page', function () {
				$('.main-table tbody input[name=property-check]').prop('checked', 'checked');
				$('.main-table tbody input[name=property-check]').val('true');
				$('.main-table tbody tr').addClass('selected');

				$(this).addClass('uncheck-all-page');
				$(this).removeClass('check-all-page');
			});

			// Снять выделение со всех страниц.
			$('#tab-userField-settings').on('click', '.uncheck-all-page', function () {
				$('.main-table tbody input[name=property-check]').prop('checked', false);
				$('.main-table tbody input[name=property-check]').val('false');
				$('.main-table tbody tr').removeClass('selected');

				$(this).addClass('check-all-page');
				$(this).removeClass('uncheck-all-page');
			});

			// клик по заголовкам настроек в первой вкладке
			$('#tab-userField-settings').on('click', '.group-property h3', function() {
				if($(this).parent().hasClass("open")) {
					$(this).parent().removeClass("open");
					$(this).next().slideUp("fast");
				} else{
					$('.group-property .group-property-list').slideUp("fast");
					$('.group-property .group-property-list').parent().removeClass("open");
					$(this).next().slideDown("fast");
					$(this).parent().addClass("open");
				}
			});

			// при выборе категории переформировать талицу характеристик
			$('#tab-userField-settings').on('change', 'select[name=cat_id]', function() {
				var cat_id = $(this).val();
				cookie("userPropertyType", $('#tab-userField-settings form[name="filter"] select[name="type"]').val());
				cookie("userPropertyCat", cat_id);
				cookie("userPropertyPage", 0);
				userProperty.print(cat_id, true, 0);
			});

			// при выборе страницы в таблице характеристик
			$('#tab-userField-settings').on('click', '.propLinkPage', function() {
				var page = admin.getIdByPrefixClass($(this), 'page');
				var cat_id = $('#tab-userField-settings select[name=cat_id]').val();
				cookie("userPropertyCat", cat_id);
				cookie("userPropertyPage", page);
				userProperty.print(cat_id, true, page);
			});

			// при выборе страницы в таблице характеристик
			$('#tab-userField-settings').on('click', '.filter-now', function() {
				var cat_id = $('#tab-userField-settings select[name=cat_id]').val();
				cookie("userPropertyType", $('#tab-userField-settings form[name="filter"] select[name="type"]').val());
				cookie("userPropertyCat", cat_id);
				cookie("userPropertyName", $('#tab-userField-settings form[name="filter"] input[name="name[]"]').val());
				userProperty.print(cat_id, true, true);
			});

			// открытия фильтров
			$('#tab-userField-settings').on('click', '.mg-panel-toggle', function() {
				$('.filters').slideToggle(function () {
					let showFilter = cookie('showUserPropertyFilter');
					if (showFilter === undefined || showFilter == 0) {
						cookie('showUserPropertyFilter', "1");
					} else {
						cookie('showUserPropertyFilter', "0");
					}
				});
			});

			// смена языка
			$('#tab-userField-settings').on('change','#user-property-edit .select-lang', function() {
				userProperty.fillFields($('#user-property-edit .save-button').attr('id'));
			});

			// обработчик клика на кнопку сохранить в модальном окне редактирования характеристики
			$('#tab-userField-settings').on('click', '#user-property-edit .save-button', function() {
				const typeCharacteristic = $(this).data('type') === 'color' || $(this).data('type') === 'size';
				const missingCharacteristic = $('#user-property-edit .accordion-item.is-active .ui-sortable tr[data-id]').length === 0;
				if (typeCharacteristic && missingCharacteristic) {
					return;
				}
				userProperty.saveFields();
			});

			// открытия настроек групп
			$('#tab-userField-settings').on('click', '.mg-panel-group-toggle', function() {
				admin.openModal($('#edit-property-group'));
				userProperty.getTablePropertyGroup();
			});

			// Добавление группы характеристик
			$('#tab-userField-settings').on('click', '.add-property-group', function() {
				var name = $('#edit-property-group input[name=name-group]').val();
				admin.ajaxRequest({
					mguniqueurl: "action/addPropertyGroup",
					name: name
				},
				function(response) {
					$('.userPropertyGroupTable tbody').append(userProperty.buildRowPropGroup({'id':response.data, 'name': name}));
					$('#edit-property-group input[name=name-group]').val('');
				});
			});

			$('#tab-userField-settings').on('click', '#user-property-edit .add-property', function() {
				$('#user-property-edit .new-added-properties input').val('');
				// берем тип поля
				type = $('#user-property-edit [name=type]').val();
				switch (type) {
					case 'assortmentCheckBox':
					case 'color':
						$('#user-property-edit [name=margin-name]').attr('placeholder', lang.EXAMPLE_1);
						$('#user-property-edit .mayby-hide').hide();
						break;
					case 'size':
						$('#user-property-edit [name=margin-name]').attr('placeholder', lang.EXAMPLE_2);
						$('#user-property-edit .mayby-hide').hide();
						break;
					default:
						$('#user-property-edit [name=margin-name]').attr('placeholder', lang.EXAMPLE_3);
						$('#user-property-edit .mayby-hide').show();
						break;
				}
				$('#user-property-edit .new-added-properties').show();
			});

			$('#tab-userField-settings').on('click', '#user-property-edit .cancel-new-prop', function() {
				$('#user-property-edit .new-added-properties').hide();
			});

			$('#tab-userField-settings').on('click', '#user-property-edit .apply-new-prop', function() {
				userProperty.addPropertyMargin();
			});

			$('#tab-userField-settings').on('change', '#user-property-edit [name=type]', function() {
				$('#user-property-edit .new-added-properties').hide();
				userProperty.showPropertyMargin($(this).val());
			});

			$('#tab-userField-settings').on('click', '#user-property-edit .fa-trash', function() {
				if(confirm('ВНИМАНИЕ!!!!\nПри удалении значения характеристики, она так же будет удалена и у самого товара! \nУдалить значение?')) {
					userProperty.deletePropertyMargin($(this).parents('tr').data('id'));
				}
			});

			$('#tab-userField-settings').on('click', '#user-property-edit .fa-repeat', function() {
				if(confirm('Вы действительно хотите применить новое значение наценки для всех товаров, на которые эта наценка распростарняется?')) {
					var propValueId = $(this).parents('tr').data('id');
					if (propValueId) {
						var propNewValue = $(this).parents('tr').find('input[name="prop-margin"]').val();
						userProperty.applyPropValueToAllProducts(propValueId, propNewValue);
					}
				}
			});

			// редактирования строки свойства
			$('#tab-userField-settings').on('click', '.userPropertyTable .edit-row', function() {
				var id = $(this).parents('tr').attr('id');
				userProperty.clearFields();
				userProperty.fillFields(id);
				admin.openModal('#user-property-edit');
			});

			// Показывает все доступные значения характеристик.
			$('#tab-userField-settings').on('click', '.userPropertyTable .show-all-prop', function() {
				userProperty.showOptions($(this));
			});

			$('#tab-userField-settings').on('click', '.close-edit-property-group', function() {
				admin.closeModal('#edit-property-group');
			});

			$('#tab-userField-settings').on('click', '.delete-property', function() {
				if (confirm(lang.WARNINF_MESSAGE_1)) {
					$(this).parents('tr').remove();
					userProperty.deletePropertyGroup($(this).parents('tr').data('id'));
				}
			});

			$('#tab-userField-settings').on('click', '.save-group-prop', function() {
				userProperty.savePropertyGroup();
			});

			// смена языка в модалке с группами характеристик
			$('#tab-userField-settings').on('change', '#edit-property-group .select-lang', function() {
				userProperty.getTablePropertyGroup();
			});

			// удалить характеристику
			$('#tab-userField-settings').on('click', '.userPropertyTable .delete-property', function() {
				var id = $(this).parents('tr').attr('id');
				userProperty.deleteRow(id);
			});

			// добавить характеристику
			$('#tab-userField-settings').on('click', '.addProperty', function() {
				userProperty.addRow();
			});

			// Нажатие на кнопку - выводить/Не выводить в карточке товара
			$('#tab-userField-settings').on('click', '.userPropertyTable .visible', function() {
				$(this).find('a').toggleClass('active');
				var id = $(this).data('id');

				if($(this).find('a').hasClass('active')) {
					userProperty.visibleProperty(id, 1);
					$(this).find('a').attr('tooltip', lang.ACT_V_PROP);
				}
				else {
					userProperty.visibleProperty(id, 0);
					$(this).find('a').attr('tooltip', lang.ACT_UNV_PROP);
				}
				$('#tiptip_holder').hide();
				admin.initToolTip();
			});

			// Нажатие на кнопку - выводить/не выводить в фильтрах
			$('#tab-userField-settings').on('click', '.userPropertyTable .filter-prop-row', function() {
				var id = $(this).parents('tr').data('id');
				if ($('.userPropertyTable tr[id=' + id + '] td.type span select').length) {
					var type = $('.userPropertyTable tr[id=' + id + '] td.type span select').val();
				} else {
					var type = $('.userPropertyTable tr[id=' + id + '] td.type span').attr('value');
				}
				if (type=='textarea' || type=='file') {
					$(this).find('a').removeClass('active')
					admin.indication('error', lang.ERROR_MESSAGE_21);
					return false;
				}
				$(this).find('a').toggleClass('active');

				if($(this).find('a').hasClass('active')) {
					userProperty.filterVisibleProperty(id, 1);
					$(this).find('a').attr('tooltip', lang.ACT_FILTER_PROP);
				}
				else {
					userProperty.filterVisibleProperty(id, 0);
					$(this).find('a').attr('tooltip', lang.ACT_UNFILTER_PROP);
				}
				$('#tiptip_holder').hide();
				admin.initToolTip();
			});

			// Выделить все характеристики.
			$('#tab-userField-settings').on('click', '.userField-settings-list .checkbox-cell input[name=property-check]', function() {
				if($(this).val()!='true') {
					$('.userPropertyTable input[name=property-check]').prop('checked','checked');
					$('.userPropertyTable input[name=property-check]').val('true');
				}else{
					$('.userPropertyTable input[name=property-check]').prop('checked', false);
					$('.userPropertyTable input[name=property-check]').val('false');
				}
			});

			// Выполнение выбранной операции с характеристиками
			$('#tab-userField-settings').on('click', '.run-operation', function() {
				var operation = $('#tab-userField-settings .property-operation').val();
				if (operation == 'fulldelete') {
					admin.openModal('#prop-remove-modal');
				} else if(operation == 'setCats' || operation == 'addCats') {
					$('#edit-category .category-select').val([]);
					admin.openModal('#edit-category');
					admin.reloadComboBoxes();
				} else {
					userProperty.runOperation(operation);
				}
			});

			//Проверка для массового удаления
			$('#tab-userField-settings').on('click', '#prop-remove-modal .confirmDrop', function () {
				if ($('#prop-remove-modal input').val() === $('#prop-remove-modal input').attr('tpl')) {
					$('#prop-remove-modal input').removeClass('error-input');
					admin.closeModal('#prop-remove-modal');
					userProperty.runOperation($('#tab-userField-settings .property-operation').val(),true);
				} else {
					$('#prop-remove-modal input').addClass('error-input');
				}
			});

			$('#tab-userField-settings').on('click', '#edit-category .save-button', function () {
				admin.closeModal('#edit-category');
				userProperty.runOperation($('#tab-userField-settings .property-operation').val(),true,$('#edit-category .category-select').val());
			});

			// Устанавливает количество выводимых записей в этом разделе.
			$('#tab-userField-settings').on('change', '.countPrintRowsProperty', function () {
				var count = $(this).val();
				admin.ajaxRequest({
					mguniqueurl: "action/countPrintRowsProperty",
					count: count
				},
				function (response) {
					var cat_id = $('.section-settings #tab-userField-settings select[name=cat_id]').val();
					cookie("userPropertyType", $('#tab-userField-settings form[name="filter"] select[name="type"]').val());
					userProperty.print(cat_id, true, true);
				}
				);
			});
			// Показывает панель с фильтрами.
			$('#tab-userField-settings').on('click', '.show-filters', function () {
				$('.filter-container').slideToggle(function () {
					$('.property-order-container').slideUp();
					$('.widget-table-action').toggleClass('no-radius');
				});
			});
			// Сброс фильтров.
			$('#tab-userField-settings').on('click', '.refreshFilter', function () {
				admin.refreshPanel();
				return false;
			});

			$('#tab-userField-settings').on('change', '.imageFormToProp [name=propImg]', function() {
				userProperty.addImageToProp($(this).parents('tr').data('id'), $(this).parents(".imageFormToProp"));
			});

			$('#tab-userField-settings').on('click', '.deleteImg', function() {
				var id = $(this).parents('tr').data('id');
				userProperty.deleteImgMargin(id);
			});

			$('#tab-userField-settings').on('click', '.js-cancelSelect', function() {
				$('select[name=listCat] option').prop('selected', false);
				admin.reloadComboBoxes();
			});
			$('#tab-userField-settings').on('click', '.js-select-all-cats', function() {
				$('select[name=listCat] option').prop('selected', true);
				admin.reloadComboBoxes();
			});

			$('#tab-userField-settings').on('change', 'select[name="operation"]', function() {
				$('.settingsOperationParam').hide();
				$('.settingsOperationParam[data-operation="'+$(this).val()+'"]').show();
			});

			admin.sortable('#user-property-edit .main-table tbody', 'property_data');
		},

		setDefaultMarginToEmptyMarginProduct: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/setDefaultMarginToEmptyMarginProduct"
			},
			function(response) {
				admin.indication(response.status, response.msg);
			});
		},

		createdSizeVariant: function() {
			// более умная проверка на удаление строк
			var variantRowA = [];
			var rowDataToDel = [];
			$('.variant-row').each(function() {
				variantRowA.push('sizeCheck-'+$(this).data('color')+'-'+$(this).data('size'));
				rowDataToDel.push($(this).find('.del-variant'));
			});
			var checked = [];
			$('.size-map .checkbox input').each(function() {
				if($(this).prop('checked')) {
					checked.push($(this).attr('id'));
				}
			});

			// запоминаем первую строку, чтобы потом заполнить данные по шаблону
			var price = $('.variant-table .variant-row:eq(0) input[name=price]').val();
			var old_price = $('.variant-table .variant-row:eq(0) input[name=old_price]').val();
			var count = $('.variant-table .variant-row:eq(0) input[name=count]').val();
			var weight = $('.variant-table .variant-row:eq(0) input[name=weight]').val();
			var code = $('.variant-table .variant-row:eq(0) input[name=code]').val();

			// проверяем строки на счет удаления
			for(row = 0; row < variantRowA.length; row++) {
				toDel = true;
				for(checVar = 0; checVar < checked.length; checVar++) {
					if(variantRowA[row] == checked[checVar]) {
						toDel = false;
					}
				}
				if(toDel) {
					if (variantRowA.length > 0) {
						if ($('.variant-row').length > 1) {
							rowDataToDel[row].parents('.variant-row').remove();
						} else {
							$('.variant-table').data('have-variant', '0');
							rowDataToDel[row].parents('.variant-row').data('color', '');
							rowDataToDel[row].parents('.variant-row').data('size', '');
							rowDataToDel[row].parents('.variant-row').attr('data-color', '');
							rowDataToDel[row].parents('.variant-row').attr('data-size', '');
							rowDataToDel[row].parents('.variant-row').prop('data-color', '');
							rowDataToDel[row].parents('.variant-row').prop('data-size', '');
						}
					}
					if (checked.length > 0) {
						rowDataToDel[row].parents('.variant-row').remove();
					}
				}
			}

			currentVariantLength = $('.variant-row').length;
			// для добавления новых строк
			for(i = 0; i < checked.length - currentVariantLength; i++) {
				$('.add-position').click();
			}

			// отвязываем размерную сетку если нет чеков
			if(checked.length == 0) {
				$('.variant-row').data('color', '');
				$('.variant-row').data('size', '');
				$('[name=title_variant]').parents('td').remove();
				$('.varTitle').hide();
			} else {
				$('.varTitle').show();
			}

			$('.variant-table .variant-row:eq(0) input[name=price]').val(price);
			$('.variant-table .variant-row:eq(0) input[name=old_price]').val(old_price);
			$('.variant-table .variant-row:eq(0) input[name=count]').val(count);
			$('.variant-table .variant-row:eq(0) input[name=weight]').val(weight);
			$('.variant-table .variant-row:eq(0) input[name=code]').val(code);

			var checked = [];
			$('.size-map .checkbox input').each(function() {
				if($(this).prop('checked')) {
					var add = true;
					for(i = 0; i < $('.variant-row').length; i++) {
						if(($('.variant-row:eq('+i+')').data('color') == $(this).data('color'))&&
							($('.variant-row:eq('+i+')').data('size') == $(this).data('size'))) {
							add = false;
						}
					}
					if(add) {
						checked.push($(this).attr('id'));
					}
				}
			});

			// автозаполнение новых строк вариантов
			var codes = [];
			var prefix = 1;
			$('.variant-row').each(function() {
				codeThis = $(this).find('input[name=code]').val().split('-');
				if(codeThis[0] == 'undefined') {
					var prefix = 1;
					while($.inArray(+prefix, codes) != -1) {
						prefix++;
					}
					const codeParts = code.split('-');
					codeParts.push(prefix);
					const newCode = codeParts.join('-');
					$(this).find('input[name=code]').val(newCode);
					codes.push(+prefix);
				} else {
					if(codeThis[1] != undefined) codes.push(+codeThis[1]);
				}
				// для цены
				if($(this).find('input[name=price]').val() == '') $(this).find('input[name=price]').val(price);
				// для старой цены
				if($(this).find('input[name=old_price]').val() == '') $(this).find('input[name=old_price]').val(old_price);
				// для количества
				if($(this).find('input[name=count]').val() == '') $(this).find('input[name=count]').val(count);
				// для веса
				if($(this).find('input[name=weight]').val() == '') $(this).find('input[name=weight]').val(weight);
				// для названия
				if(($(this).data('color') == '')&&($(this).data('size') == '')) {
					color = $('#'+checked[0]).data('color') != 'none' ? true : false;
					size = $('#'+checked[0]).data('size') != 'none' ? true : false;

					$(this).attr('data-color', color ? $('#'+checked[0]).data('color') : '');
					$(this).attr('data-size', size ? $('#'+checked[0]).data('size') : '');
					//
					space = $('#'+checked[0]).parents('td').find('.size').val()?' ':'';
					$(this).find('input[name=title_variant]').val((size ? $('#'+checked[0]).parents('td').find('.size').val() : '') + space + (color ? $('#'+checked[0]).parents('td').find('.color').val() : ''));
					if($(this).find('input[name=title_variant]').val() == 'undefinedundefined') $(this).find('input[name=title_variant]').val('');
					checked.shift();
				}
			});
		},

		createSizeMap: function(data) {
			$('.add-position').show();
			$('.size-map tbody').html('');
			$('.size-map').data('type', '');

			var color = '';
			var size = '';
			for(i = 0; i < data.length; i++) {
				if(data[i].type == 'color') {
					color = data[i];
				}
				if(data[i].type == 'size') {
					size = data[i];
				}
			}

			table = '';

			// полная сетка
			if((color != '')&&(size != '')) {
				$('.add-position').hide();
				for(i = -1; i <= color.data.length; i++) {
					table += '<tr>';
					for(j = -1; j <= size.data.length; j++) {
						var sizeD = '';
						if(j == size.data.length) {
							if(i == -1) {
								sizeD = 'Нет размера';
							} else {
								sizeD = '';
							}
						} else {
							if((j >= 0)&&(i == -1)) {
								sizeD = size.data[j].name;
							}
						}

						var colorD = '';
						if(i == color.data.length) {
							if(j == -1) {
								sizeD = '<span style="display:block;margin:0 4px;">Нет цвета</span>';
							} else {
								sizeD = '';
							}
						} else {
							if((i >= 0)&&(j == -1)) {
								colorD = color.data[i].name;
							}
						}

						if((i == -1)&&(j == -1)) {
							sizeText = '<span class="fl-right">Размеры:</span>';
						} else {
							sizeText = '';
						}
						// if((i == color.data.length)&&(j == size.data.length)) {
						//	 sizeText = 'Нет размера';
						// } else {
						//	 sizeText = '';
						// }
						var checkbox = '';
						if((i >= 0)&&(j >= 0)&&(j != size.data.length)&&(i != color.data.length)) {
							checkbox = '\
								<input class="color" value="'+color.data[i].name+'" style="display:none;">\
								<input class="size size-'+size.data[j].id+'" value="'+size.data[j].name+'" style="display:none;">\
								<input class="color-'+color.data[i].id+'" value="'+color.data[i].color+'" style="display:none;">\
								<div style="margin-left: calc(50% - 9px);"><div class="checkbox tip">\
									<input type="checkbox" id="sizeCheck-'+color.data[i].id+'-'+size.data[j].id+'"\
										data-color="'+color.data[i].id+'" data-size="'+size.data[j].id+'" name="size-map-checkbox">\
									<label for="sizeCheck-'+color.data[i].id+'-'+size.data[j].id+'"></label>\
								</div></div>';
						}
						if((i >= 0)&&(j < 0)&&(i != color.data.length)) {
							if(color.data[i].img != '') {
								table += '<td class="color color-td" style="padding:2px;">\
										<div class="nowrap flex-jcb" style="padding:3px;"><span>'+color.data[i].name+'</span>\
										<div class="color-img flex-jcb" style="background:url('+admin.SITE+'/'+color.data[i].img+');background-size:cover;"></div></div>\
									</td>';
							} else {
								table += '<td class="color color-td" style="padding:2px;">' +
										'<div class="nowrap flex-jcb" style="padding:3px;">' +
										'<span>'+color.data[i].name+'</span>' +
										'<div class="color-img flex-jcb" style="background-color: '+color.data[i].color+';background-image: url('+admin.SITE+'/'+color.data[i].img+');"></div>'+
										'</div>' +
										'</td>';
							}
						} else {
							if ((i < 0) && (j >= 0) && j !== size.data.length) {
								table += '<td class="size-td" style="padding:5px 9px;">'+sizeText+sizeD+colorD+checkbox+'</td>';
							} else if ((i < 0) && (j < 0)) {
								table += '<td style="padding:5px 9px;">'+sizeText+sizeD+colorD+checkbox+'</td>';
							} else {
								if((i >= 0)&&(j == size.data.length)&&(i != color.data.length)) {
									checkbox = '\
									<input class="color" value="'+color.data[i].name+'" style="display:none;">\
									<input class="size size-none" value="none" style="display:none;">\
									<input class="color-'+color.data[i].id+'" value="'+color.data[i].color+'" style="display:none;">\
									<div style="margin-left: calc(50% - 9px);"><div class="checkbox tip">\
										<input type="checkbox" id="sizeCheck-'+color.data[i].id+'-none"\
											data-color="'+color.data[i].id+'" data-size="none" name="size-map-checkbox">\
										<label for="sizeCheck-'+color.data[i].id+'-none"></label>\
									</div></div>';
									table += '<td class="color-none" style="padding:5px 9px;">'+checkbox+'</td>';
								}
								if((j >= 0)&&(i == color.data.length)&&(j != size.data.length)) {
									checkbox = '\
									<input class="color" value="none" style="display:none;">\
									<input class="size size-'+size.data[j].id+'" value="'+size.data[j].name+'" style="display:none;">\
									<input class="color-none" value="none" style="display:none;">\
									<div style="margin-left: calc(50% - 9px);"><div class="checkbox tip">\
										<input type="checkbox" id="sizeCheck-none-'+size.data[j].id+'"\
											data-color="none" data-size="'+size.data[j].id+'" name="size-map-checkbox">\
										<label for="sizeCheck-none-'+size.data[j].id+'"></label>\
									</div></div>';
									table += '<td class="size-none" style="padding:5px 9px;">'+checkbox+'</td>';
								}
								if ((j == -1)&&(i == color.data.length)) {
									table += '<td class="not-color" style="padding:5px 9px;">'+sizeText+sizeD+colorD+checkbox+'</td>';
								}

								if ((i == -1)&&(j == size.data.length)) {
									table += '<td class="not-size" style="padding:5px 9px;">'+sizeText+sizeD+colorD+checkbox+'</td>';
								}

								if(((j < size.data.length)&&(i < color.data.length))) {
									table += '<td class="color-size-td" style="padding:5px 9px;">'+sizeText+sizeD+colorD+checkbox+'</td>';
								}
							}
						}
					}
					table += '</tr>';
				}
				$('.size-map tbody').html(table);
				$('.set-size-map').show();

				// копирование левого столбца
				var leftCol = '';
				$('.leftCol').detach();
				$('.size-map table:eq(0) tr').each(function(index) {
					if(index == 0) {
						height = 34;
					} else {
						height = 32;
					}
					leftCol += '<tr style="height:'+height+'px;"><td class="color" style="padding:2px;min-width:78px;">'+$(this).find('td:eq(0)').html()+'</td></tr>';
				});

				$('.size-map .row').append('<table class="leftCol" style="width:auto;position:absolute;left:0;top:0;">'+leftCol+'</table>');
				// копирование верхней строки
				var topRow = '';
				$('.topRow').detach();
				topRow = '<tr>'+$('.size-map tr:eq(0)').html()+'</tr>';
				$('.size-map .row').append('<table class="topRow" style="width:auto;position:absolute;left:0;top:0;">'+topRow+'</table>');
				$('.size-map .row').css('position', 'relative').css('max-height','500px');

				$('.set-size-map').click(function() {
					setTimeout(function() {
						$('.topRow td:eq(0)').css('min-width', $('.leftCol').outerWidth() + 'px');
					}, 10);
				});

				$('.topRow td').css('min-width', '31px').css('text-align', 'center');

				$('.size-map .row').scroll(function() {
					offset = $('.size-map .row').offset();
					scrollTop = $('.size-map .row').scrollTop();
					scrollLeft = $('.size-map .row').scrollLeft();
					$('.leftCol').offset({top:offset.top - scrollTop - 1, left:offset.left});
					$('.topRow').offset({top:offset.top, left:offset.left - scrollLeft});

					$('.topRow td:eq(0)').css('min-width', $('.leftCol').outerWidth() + 'px');
				});

				$('.size-map .row').trigger('scroll');

				//
				$('.size-map').data('type', 'only-all');
				$('.del-variant').hide();
				return;
			}

			// сетка только размерная
			if(size != '') {
				$('.add-position').hide();
				for(i = -1; i < 1; i++) {
					table += '<tr>';
					for(j = 0; j < size.data.length; j++) {
						var sizeD = '';
						if((j >= 0)&&(i == -1)) {
							sizeD = size.data[j].name;
						}
						var checkbox = '';
						if((i >= 0)&&(j >= 0)) {
							checkbox = '\
								<input class="color" value="" style="display:none;">\
								<input class="size size-'+size.data[i].id+'" value="'+size.data[j].name+'" style="display:none;">\
								<div class="checkbox tip">\
									<input type="checkbox" id="sizeCheck--'+size.data[j].id+'"\
										data-color="" data-size="'+size.data[j].id+'" name="size-map-checkbox">\
									<label for="sizeCheck--'+size.data[j].id+'"></label>\
								</div>';
						}
						table += '<td>'+sizeD+checkbox+'</td>';
					}
					table += '</tr>';
				}
				$('.size-map tbody').html(table);
				$('.set-size-map').show();
				$('.size-map').data('type', 'only-size');
				$('.del-variant').hide();
				return;
			}

			// сетка цветов
			if(color != '') {
				$('.add-position').hide();
				for(i = 0; i < 1; i++) {
					table += '<tr>';
					for(j = 0; j < color.data.length; j++) {
						var colorD = '';
						if((i >= 0)&&(j == -1)) {
							colorD = color.data[i].name;
						}
						var checkbox = '';
						if((i >= 0)&&(j >= 0)) {
							checkbox = '\
								<input class="color" value="'+color.data[j].name+'" style="display:none;">\
								<input class="size" value="" style="display:none;">\
								<input class="color-'+color.data[j].id+'" value="'+color.data[j].color+'" style="display:none;">\
								<div class="checkbox tip">\
									<input type="checkbox" id="sizeCheck-'+color.data[j].id+'-"\
										data-color="'+color.data[j].id+'" data-size="" name="size-map-checkbox">\
									<label for="sizeCheck-'+color.data[j].id+'-"></label>\
								</div>';
							checkbox += '<label class="bigLabel" for="sizeCheck-'+color.data[j].id+'-"></label>';
						}
						if(color.data[j].img) {
							table += '<td class="color" style="background:url('+admin.SITE+'/'+color.data[j].img+');background-size:cover;" title="'+color.data[j].name+'">\
							'+colorD+checkbox+'</td><td style="border:0!important;width:10px;background:#f5f5f5;"></td>';
						} else {
							table += '<td class="color" style="background:'+color.data[j].color+';border:1px solid #e6e6e6;" title="'+color.data[j].name+'">\
							'+colorD+checkbox+'</td><td style="border:0!important;width:10px;background:#f5f5f5;"></td>';
						}
					}
					table += '</tr>';
				}
				$('.size-map tbody').html(table);
				$('.set-size-map').show();
				$('.size-map').data('type', 'only-color');
				$('.del-variant').hide();
				return;
			}

			$('.set-size-map').hide();
			$('.size-map').hide();
			$('.del-variant').show();
		},

		/**
		 * Выполняет выбранную операцию со всеми отмеченными характеристиками
		 * operation - тип операции.
		 */
		runOperation: function(operation, skipConfirm, param) {
			if(typeof skipConfirm === "undefined" || skipConfirm === null){skipConfirm = false;}
			if(typeof param === "undefined" || param === null){param = false;}
			var property_id = [];
			$('#tab-userField-settings .main-table tbody tr').each(function() {
				if($(this).find('input[name=property-check]').prop('checked')) {
					property_id.push($(this).attr('id'));
				}
			});

			if (skipConfirm || confirm(lang.RUN_CONFIRM)) {
				if (operation == 'resetMargins') {
					userProperty.setDefaultMarginToEmptyMarginProduct();
				} else {
					if($('.settingsOperationParam select:visible').length) {
			            param = $('.settingsOperationParam select:visible').val();
			        }
					admin.ajaxRequest({
						mguniqueurl: "action/operationProperty",
						operation: operation,
						property_id: property_id,
						param: param,
					},
					function(response) {
						var cat_id = $('.section-settings #tab-userField-settings select[name=cat_id]').val();
						cookie("userPropertyType", $('#tab-userField-settings form[name="filter"] select[name="type"]').val());
						page = $('.mg-pager .active').text();
						userProperty.print(cat_id, true, page);
					});
				}
			}
		},

		getTablePropertyGroup: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/getTablePropertyGroup",
				lang: $('#edit-property-group .select-lang').val()
			},
			function(response) {
				var html = '<thead class="yellow-bg">\
					<tr><th colspan=3></th></tr></thead><tbody class="table-group-property">';

				if (response.data.length != 0) {
					response.data.forEach(function (element, index, array) {
						html += userProperty.buildRowPropGroup(element);
					});
				}

				html += '</tbody>';

				$('.userPropertyGroupTable').html(html);
				admin.sortable('.table-group-property','property_group');
			});
		},
		//TODO[shevch] локали
		buildRowPropGroup: function(element) {
			return '<tr id=' + element.id + ' data-id=' + element.id + '>\
				<td class="mover"><i class="fa fa-arrows" aria-hidden="true"></i></td>\
				<td class="name"><input placeholder="Введите название группы" title="Название группы" type="text" value="'+element.name+'"></td>\
				<td class="actions">\
				<ul class="action-list text-right">\
				 <li class="delete-property"><a class="fa fa-trash tip " href="javascript:void(0);" aria-hidden="true" title="' + lang.DELETE + '"></a></li>\
				</ul>\
				</td>\
			</tr>';
		},

		deletePropertyGroup: function(id) {
			admin.ajaxRequest({
				mguniqueurl: "action/deletePropertyGroup",
				 id:id
				},
				function(response) {});
		 },


		savePropertyGroup: function() {
			$('#edit-property-group input[type=text]').each(function() {
				$(this).val(admin.htmlspecialchars($(this).val()));
			});
			var fields = [];
			$('.userPropertyGroupTable input').each(function(index,element) {
				fields.push({'id':$(this).parents('tr').data('id'), 'val':$(this).val()});
			});

			admin.ajaxRequest({
				mguniqueurl: "action/savePropertyGroup",
				lang: $('#edit-property-group .select-lang').val(),
				fields: fields
			},
			function(response) {
				admin.indication(response.status, 'Сохранено');
				admin.closeModal('#edit-property-group');
			})
		},

		// очищает модалку характеристик
		clearFields: function() {
			$('#user-property-edit [name=name]').val('');
			$('#user-property-edit [name=description]').val('');
			$('#user-property-edit [name=unit]').val('');
			$('#user-property-edit [name=mark]').val('');
			$('#user-property-edit [name=propInProduct]').prop('checked', false);
			$('#user-property-edit [name=propInFilter]').prop('checked', false);
			$('#user-property-edit .propInFilterContainer').show();
			$('#user-property-edit table tbody').html('');

			$('#user-property-edit .add-property-field').hide();
			$('#user-property-edit .main-table').hide();
			$('#user-property-edit .category-select').val([]);
			$('#user-property-edit .alert-block.warning').hide();
			$('#user-property-edit ul.accordion').hide();
		},

		// заполняет модалку данными
		fillFields: function(id) {
			$('#user-property-edit .save-button').attr('id', id);

			admin.ajaxRequest({
				mguniqueurl: "action/getProperty",
				id: id,
				lang: $('#tab-userField-settings .select-lang').val()
			},
			function(response) {
				$('.category-select option').each(function (index, value){
					let oldValue = $(this).text();
					$(this).text(admin.htmlspecialchars_decode(oldValue));
				});
				$('#user-property-edit .category-select').val(response.data.selectedCatIds);
				userProperty.initialCats = $('#user-property-edit .category-select').val();
				var property = response.data.property;
				if(property.type == 'none') {
					$('#user-property-edit [name=type]').prop('disabled', false);
				} else {
					$('#user-property-edit [name=type]').prop('disabled', true);
				}
				userProperty.loadMargin(id, property.type);
				$('#user-property-edit .save-button').data('id', id);
				// выставляем загруженные параметры для характеристики в модалке
				$('#user-property-edit [name=name]').val(admin.htmlspecialchars_decode(property.name));
				$('#user-property-edit [name=description]').val(property.description);
				$('#user-property-edit [name=unit]').val(admin.htmlspecialchars_decode(property.unit));
				$('#user-property-edit [name=mark]').val(admin.htmlspecialchars_decode(property.mark));
				$('#user-property-edit [name=group]').val(property.group_id);

				if (parseInt(property.activity) > 0) {
					$('#user-property-edit [name=propInProduct]').prop('checked', true);
				}
				if (parseInt(property.filter) > 0) {
					$('#user-property-edit [name=propInFilter]').prop('checked', true);
				}

				$('#user-property-edit .select-group').html('');
				$('#user-property-edit .select-group').append('<option value="0">'+lang.NO_SELECT+'</option>');
				property.selectGroup.forEach(function (element, index, array) {
					$('#user-property-edit .select-group').append('<option value="'+element.id+'">'+element.name+'</option>');
				});
				$('#user-property-edit .select-group option[value='+property.group_id+']').prop('selected', true);
				// подключаем селекты
				var is_string = false;
				if (property.type == 'string') {
					is_string = true
				}
				typefilter =	'<option value="checkbox">'+lang.BY_CHECKBOX+'</option>';
				typefilter += '<option value="select">'+lang.BY_LIST+'</option>';
				typefilter += '<option value="slider" style="display:'+(is_string?'auto':'none')+'">'+lang.SLIDER+'</option>';
				$('#user-property-edit [name=type-filter]').html(typefilter);
				//
				type =	'<option value="none">' + lang.NO_SELECT + '</option>';
				type += '<option value="string">' + lang.STRING + '</option>';
				type += '<option value="assortment">' + lang.ASSORTMENT + '</option>';
				// type += '<option value="select">' + lang.SELECT + '</option>';
				type += '<option value="assortmentCheckBox">' + lang.ASSORTMENTCHECKBOX + '</option>';
				type += '<option value="textarea">'+lang.TEXTAREA+'</option>';

				type += '<option value="color">' + lang.COLOR + '</option>';
				type += '<option value="size">' + lang.SIZE + '</option>';

				type += '<option value="file">' +lang.PROP_FILE+ '</option>';

				type += '<option value="diapason">Диапазон</option>';

				$('#user-property-edit [name=type]').html(type);

				$('#user-property-edit [name=type] option[value='+property.type+']').prop('selected', 'selected');
				if (property.type_filter && property.type_filter != '') {
					$('#user-property-edit [name=type-filter] option[value='+property.type_filter+']').prop('selected', 'selected');
				}

				// если нужны наценки, показываем их
				userProperty.showPropertyMargin(property.type);
				admin.reloadComboBoxes();
			});
		},

		// показыввает наценки, если по типу подходят
		showPropertyMargin: function(type) {
			$('#user-property-edit input#propInFilter').parentsUntil('.reveal-body', 'div.row').show();
			$('#user-property-edit .marginColumtTitle').parent().show();
			$('#user-property-edit .save-button').data('type', type);
			$('#user-property-edit ul.accordion').hide();
			$('#user-property-edit .add-property-field').hide();
			$('#user-property-edit .main-table').hide();
			$('#user-property-edit .alert-block.warning').hide();
			$('.hideOnColor').show();
			$('.marginColumtTitle').html(lang.DISCOUNT_UP);
			switch(type) {
				case 'assortmentCheckBox':
					$('#user-property-edit .marginColumtTitle').parent().hide();
				case 'assortment':
					$('.marginColumtTitle').html(lang.DISCOUNT_UP);
					$('#user-property-edit ul.accordion').show();
					$('#user-property-edit .add-property-field').show();
					$('#user-property-edit .main-table').show();
					$('#user-property-edit .alert-block.warning').show();
					$('.hideOnColor').show();
					break;
				case 'color':
					$('#user-property-edit ul.accordion').show();
					$('#user-property-edit .add-property-field').show();
					$('#user-property-edit .main-table').show();
					$('#user-property-edit .alert-block.warning').show();
					$('.hideOnColor').hide();
					$('.marginColumtTitle').html(lang.COLOR);
					break;
				case 'size':
					$('#user-property-edit ul.accordion').show();
					$('#user-property-edit .add-property-field').show();
					$('#user-property-edit .main-table').show();
					$('#user-property-edit .alert-block.warning').show();
					$('.hideOnColor').show();
					$('.marginColumtTitle').html('');
					break;
				case 'diapason':
					$('#user-property-edit ul.accordion').hide();
					$('#user-property-edit .add-property-field').hide();
					$('#user-property-edit .main-table').hide();
					$('#user-property-edit .alert-block.warning').hide();
					$('.hideOnColor').show();
					$('.marginColumtTitle').html('');
					break;
				case 'textarea':
					$('#user-property-edit input#propInFilter').parentsUntil('.reveal-body', 'div.row').hide();
					break;
				case 'file':
					$('#user-property-edit input#propInFilter').parentsUntil('.reveal-body', 'div.row').hide();
					$('.hideOnColor').hide();
					break;

				default:
					$('#user-property-edit ul.accordion').hide();
					$('#user-property-edit .add-property-field').hide();
					$('#user-property-edit .main-table').hide();
					$('#user-property-edit .alert-block.warning').hide();
					$('.hideOnColor').show();
					$('.marginColumtTitle').html(lang.DISCOUNT_UP);
			}

			if(type == 'string') {
				$('#user-property-edit [name=type-filter] option[value=slider]').show();
				$('#user-property-edit [name=type-filter] option[value=checkbox]').show();
				$('#user-property-edit [name=type-filter] option[value=select]').show();
			} else if(type == 'diapason') {
				$('#user-property-edit [name=type-filter] option[value=slider]').show();
				$('#user-property-edit [name=type-filter] option[value=checkbox]').hide();
				$('#user-property-edit [name=type-filter] option[value=select]').hide();
				$('#user-property-edit [name=type-filter]').val('slider');
			} else {
				$('#user-property-edit [name=type-filter] option[value=slider]').hide();
				$('#user-property-edit [name=type-filter] option[value=checkbox]').show();
				$('#user-property-edit [name=type-filter] option[value=select]').show();
			}

			propId = $('#user-property-edit .save-button').data('id');
			userProperty.loadMargin(propId, type);
			$('#propInFilter').trigger('change');
			if (!$('#user-property-edit .accordion-content:visible').length) {
				$('#user-property-edit .accordion-title').click();
			}
		},

		// сохраняет редактируемую характеристику
		saveFields: function(close) {
			close = typeof close !== 'undefined' ? close : true;
			$('#user-property-edit input[type=text]').each(function() {
				$(this).val(admin.htmlspecialchars($(this).val()));
			});
			// собираем данные
			var id = $('#user-property-edit .save-button').data('id');
			var name = $('#user-property-edit [name=name]').val();
			var description = $('#user-property-edit [name=description]').val();
			var unit = $('#user-property-edit [name=unit]').val();
			var mark = $('#user-property-edit [name=mark]').val();
			var group = $('#user-property-edit .select-group').val();
			var activity = 0;
			if ($('#user-property-edit [name=propInProduct]').prop('checked')) {
				activity = 1;
			}
			var filter = 0;
			if ($('#user-property-edit [name=propInFilter]').prop('checked')) {
				filter = 1;
			}

			var type = $('#user-property-edit [name=type]').val();
			var typefilter = $('#user-property-edit [name=type-filter]').val();
			if (type == 'none') {
				$('#user-property-edit [name=type]').addClass('error-input');
				return false;
			} else {
				$('#user-property-edit [name=type]').removeClass('error-input');
			}

			var dataProp = {};
			var count = 1;
			$('#user-property-edit .main-table tbody tr').each(function() {
				dataProp[count] = {};
				dataProp[count]['id'] = $(this).data('id');
				dataProp[count]['name'] = $(this).find('[name=prop-name]').val();
				dataProp[count]['margin'] = $(this).find('[name=prop-margin]').val();
				// dataProp[count]['color'] = $(this).find('[name=color]').data('color');
				dataProp[count]['color'] = $(this).find('.color-containt').val();
				count++;
			});

			var toCompare = [];
			var category = '';
			$('#user-property-edit select[name=listCat] option').each(function() {
				if ($(this).prop('selected')) {
					category += $(this).val() + userProperty.delimetr;
					toCompare.push($(this).val());
				}
			});
			category = category.slice(0, -1);

			var removed = [];
			var cats = $('#user-property-edit select[name=listCat]').val();
			var showConfirm = false;

			$.each(userProperty.initialCats, function(index, item){
				if($.inArray(item, cats) == -1){
					showConfirm = true;
					removed.push(item);
				}
			});
			if (!close) {
				$('#user-property-edit').attr('data-refresh', 'true');
			}

			if (!close && showConfirm) {
				var confirmText = lang.CONFIRM_MESSAGE_1+name+lang.TO_CATEGORY;
				$.each(removed, function(index, item){
					var catText = $('#user-property-edit select[name=listCat] option[value='+item+']').text();
					while (catText.slice(0,6) == '	--	') {
							catText = catText.substr(6);
					}
					confirmText += catText+',\n';
				});
				confirmText = confirmText.slice(0, -2)+'?';

				if (!confirm(confirmText)) {return false;}
			}

			if (!close && !category && !confirm(lang.PROP_NO_CATS_CONFIRM)) {return false;}

			admin.ajaxRequest({
				mguniqueurl: "action/saveUserProperty",
				id: id,
				name: name,
				type: type,
				description: description,
				type_filter: typefilter,
				unit: unit,
				mark: mark,
				group_id:group,
				dataProp: JSON.stringify(dataProp),
				lang: $('#tab-userField-settings .select-lang').val(),
				category: category,
				activity: activity,
				filter: filter,
			},
			function(response) {
				if(close) {
					admin.indication(response.status, response.msg);
					admin.closeModal('#user-property-edit');

					var cat_id = $('.section-settings #tab-userField-settings select[name=cat_id]').val();
					cookie("userPropertyType", $('#tab-userField-settings form[name="filter"] select[name="type"]').val());
					page = $('.mg-pager:visible .active').text();
					if (!page) {page=1;}
					userProperty.print(cat_id, true, page);
				} else {
					userProperty.loadMargin(propId);
				}
			});
		},

		deleteImgMargin: function(id) {
			propId = $('#user-property-edit .save-button').data('id');
			admin.ajaxRequest({
				mguniqueurl: "action/deleteImgMargin",
				id: id,
			},
			function(response) {
				$('#user-property-edit .new-added-properties').hide();
				userProperty.loadMargin(propId)
			});
		},

		// добавляет новое поле для наценок
		addPropertyMargin: function(id) {
			propId = $('#user-property-edit .save-button').data('id');
			name = $('#user-property-edit [name=margin-name]').val();
			margin = $('#user-property-edit [name=margin-value]').val();
			admin.ajaxRequest({
				mguniqueurl: "action/addPropertyMargin",
				propId: propId,
				name: name,
				margin: margin
			},
			function(response) {
				$('#user-property-edit .new-added-properties').hide();
				userProperty.saveFields(false)
			});
		},

		// загружает наценки для характеристик
		loadMargin: function(id, type) {
			admin.ajaxRequest({
				mguniqueurl: "action/loadPropertyMargin",
				id: id,
				lang: $('#tab-userField-settings .select-lang').val()
			},
			function(response) {

				$('#user-property-edit table tbody').html(userProperty.htmlMargin(response.data, type));

				if($('#user-property-edit table tbody tr').length > 0 && type != 'diapason') {
					$('#user-property-edit table').show();
				} else {
					$('#user-property-edit table').hide();
				}

				const typeCharacteristic = $('#user-property-edit .save-button').data('type') === 'color' || $('#user-property-edit .save-button').data('type') === 'size';
				const missingCharacteristic = $('#user-property-edit .accordion-item.is-active .ui-sortable tr[data-id]').length === 0;
				if (typeCharacteristic && missingCharacteristic) {
					$('#user-property-edit .alert-message').slideDown();
				} else {
					$('#user-property-edit .alert-message').slideUp();
				}
			});
		},

		// создает верстку для наценок харктеристик
		htmlMargin: function(data, type) {
			type = typeof type !== 'undefined' ? type : $('#user-property-edit [name=type]').val();
			var html = '';
			var colorList = [];
			if(data != null) {
				for(i = 0; i < data.length; i++) {
					html += '<tr data-id="'+data[i].id+'">';
					html += '<td class="mover" style="width: 40px;"><i class="fa fa-arrows ui-sortable-handle" aria-hidden="true"></i></td>';
					html += '<td>';
					html += '<input type="text" name="prop-name" value="'+data[i].name+'" style="margin:0;">';
					html += '</td>';
					
					var applyToAllProductsAvailable = false;
					switch(type) {
						case 'assortment':
							applyToAllProductsAvailable = true;
							html += '<td>';
							html += '<input type="text" name="prop-margin" value="'+data[i].margin+'" style="margin:0;">';
							html += '</td>';
							break;
						case 'size':
						case 'assortmentCheckBox':
							break;
						case 'color':
							html += '<td>';
							name = 'color'+i;
							colorList.push(name);
							html += '\
								<div class="option-color-wrap">\
								  <div class="color" data-color="'+data[i].color+'" style="display:inline-block;">\
									<div id="'+name+'"></div>\
								  </div>\
								  <input type="hidden" name="'+name+'" class="color-containt" value="'+data[i].color+'">\
								  <form class="imageFormToProp" method="post" noengine="true" enctype="multipart/form-data" style="display:inline-block;position:relative;">\
									<label class="img-iploader-to-prop tip fl-left" for="'+data[i].id+'" data-hasqtip="64" title="'+(data[i].img==''?lang.UPLOAD_PROP_IMG:lang.UPLOAD_PROP_IMG_DEL)+'" \
									  style="background-image:url('+admin.SITE+'/'+data[i].img+');">\
										<i style="margin: 7px 6px; '+(data[i].img==''?'':'display:none;')+'" class="fa fa-image" ></i>\
									</label>\
									<i style="position:absolute;top:-5px;right:-5px;color:red;cursor:pointer; '+(data[i].img!=''?'':'display:none;')+'" class="fa fa-times deleteImg" ></i>\
									<input type="file" id="'+data[i].id+'" name="propImg" class="add-img tool-tip-top" style="display:none;">\
								  </form>\
								</div>';
								html += '</td>';
							break;
						default:
							html += '<td>';
							html += '<input type="text" name="prop-margin" value="'+data[i].margin+'" style="margin:0;">';
							html += '</td>';
							break;
					}
					html += '</td>';
					html += '<td class="action" style="text-align: right;font-size: 16px;">';
					if (applyToAllProductsAvailable) {
						html += '<a role="button" href="javascript:void(0)" style="color:#333;padding:2px;margin-right:5px;" class="fa fa-repeat" title="'+ lang.APPLY_TO_ALL_PRODUCTS+'"></a>';
					}
					html += '<a role="button" href="javascript:void(0)" style="color:#333;padding:2px;" class="fa fa-trash"></a>';
					html += '</td>';
					html += '</tr>';
				}
			}

			setTimeout(function() {
				if(type == 'string') {
					$('[name=prop-name]').attr('disabled', true);
					$('[name=prop-margin], #user-property-edit .fa-trash, .marginColumtTitle').hide();
				} else {
					$('[name=prop-name]').attr('disabled', false);
					$('[name=prop-margin], #user-property-edit .fa-trash, .marginColumtTitle').show();
				}
			}, 100);

			if (colorList.length > 0) {
				admin.includePickr();
				setTimeout(function() {
				for (i = 0; i < colorList.length; i++) {
					admin.clickPickr(colorList[i]);
				}
				}, 100);
			}

			return html;
		},

		// загружает картинку к характеристике
		addImageToProp:function(id, form) {
		 $('.img-loader').show();
			$(form).ajaxForm({
				type:"POST",
				url: "ajax",
				data: {
					mguniqueurl:"action/addImageToProp",
					propDataId:id
				},
				cache: false,
				dataType: 'json',
				success: function(response) {
					admin.indication(response.status, response.msg);
					$('.img-loader').hide();
					propId = $('#user-property-edit .save-button').data('id');
					userProperty.loadMargin(propId);
				}
			}).submit();
		},

		// удаляет поле с наценкой
		deletePropertyMargin: function(id) {
			userProperty.saveFields(false);
			admin.ajaxRequest({
				mguniqueurl: "action/deletePropertyMargin",
				id: id
			},
			function(response) {
				propId = $('#user-property-edit .save-button').data('id');
				userProperty.loadMargin(propId);
			});
		},

		/**
		 * Получает все значения свойств из модального окна для сохранения в БД
		 */
		getUserFields: function() {
			var data = {};

			// собираем всю информацию о пользовательских характеристиках
			$('.userField .property, .addedProperty .property').each(function() {
				var name = $(this).attr('name');
				// определяем тип характеристики по типу тэга для ее описания
				switch($(this)[0].tagName) {
					// считываем селекты
					case 'SELECT':
						var typeView = "";
						data[name] = {};
						data[name]['type'] = 'select';
						$(this).parents('.price-settings').find('.setup-type').each(function() {

							if($(this).hasClass('selected')) {
								// data[name]['type-view'] = $(this).data('type');
								typeView = $(this).data('type');
							}
						});
						$(this).find('option').each(function() {
							if(data[name][$(this).data('id')] == undefined) data[name][$(this).data('id')] = {};
							data[name][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
							data[name][$(this).data('id')]['prop-data-id'] = $(this).data('prop-data-id');
							data[name][$(this).data('id')]['val'] = $(this).val();
							if($(this).prop('selected')) {
								data[name][$(this).data('id')]['active'] = 1;
								del = false;
							} else {
								data[name][$(this).data('id')]['active'] = 0;
								del = true;
							}
							data[name][$(this).data('id')]['type-view'] = typeView;
							if(del) delete data[name][$(this).data('id')];
						});
						break;
					// считываем инпуты
					case 'INPUT':
						// для считываения чекбоксов и селектов
						if($(this).attr('type') == 'checkbox') { // TODO
							var propId = $(this).parents('.assortmentCheckBox').data('property-id');
							if(data[propId] == undefined) data[propId] = {};
							if(data[propId][$(this).data('id')] == undefined) data[propId][$(this).data('id')] = {};
							data[propId]['type'] = 'checkbox';
							data[propId][$(this).data('id')]['val'] = $(this).parents('.checkbox').find('span').html();
							data[propId][$(this).data('id')]['prop-data-id'] = $(this).data('prop-data-id');
							if($(this).prop('checked')) {
								data[propId][$(this).data('id')]['active'] = 1;
								del = false;
							} else {
								data[propId][$(this).data('id')]['active'] = 0;
								del = true;
							}
							data[propId][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
							if(del) delete data[propId][$(this).data('id')];
						}
						// для диапазонов
						else if($(this).data('type') == 'diapason' && $(this).data('from') == 'min') {
							propId = $(this).attr('name');
							data[propId] = {};
							data[propId]['type'] = 'diapason';
							data[propId]['min'] = {};
							data[propId]['max'] = {};
							data[propId]['min']['val'] = $('[data-type=diapason][name='+propId+'][data-from=min]').val();
							data[propId]['max']['val'] = $('[data-type=diapason][name='+propId+'][data-from=max]').val();
							data[propId]['max']['id'] = $('[data-type=diapason][name='+propId+'][data-from=max]').data('id');
							data[propId]['max']['id'] = $('[data-type=diapason][name='+propId+'][data-from=max]').data('id');
						}
						else if($(this).data('type') == 'diapason' && $(this).data('from') == 'max') {
							break;
						}
						else if($(this).attr('type') == 'hidden') {
							data[name] = {};
							data[name]['type'] = 'file';
							if(data[name][$(this).data('id')] == undefined) data[name][$(this).data('id')] = {};
							data[name][$(this).data('id')]['val'] = $(this).val();
							data[name][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
							break;
						}
						// для полей типа строка
						else {
							data[name] = {};
							data[name]['type'] = 'input';
							if(data[name][$(this).data('id')] == undefined) data[name][$(this).data('id')] = {};
							data[name][$(this).data('id')]['val'] = $(this).val();
							data[name][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
						}
						break;
					// считываем поля типа textarea
					case 'A':
						data[name] = {};
						data[name]['type'] = 'textarea';
						if(data[name][$(this).data('id')] == undefined) data[name][$(this).data('id')] = {};
						data[name][$(this).data('id')]['val'] = $(this).parent().find('.value').html();
						data[name][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
						break;
				}
			});
			return data;
		},

		// преобразует системные записи типов в понятные пользователю
		typeToRead: function(type) {
			switch (type) {
				case 'none':
					{
						return lang.NO_SELECT
						break;
					}
				case 'string':
					{
						return lang.STRING
						break;
					}
				case 'select':
					{
						return lang.SELECT
						break;
					}
				case 'assortment':
					{
						return lang.ASSORTMENT
						break;
					}
				case 'assortmentCheckBox':
					{
						return lang.ASSORTMENTCHECKBOX
						break;
					}
				case 'textarea':
					{
						return lang.TEXTAREA
						break;
					}
				case 'diapason':
					{
						return lang.DIAPASON
						break;
					}
				case 'file':
					{
						return lang.PROP_FILE;
						break;
					}
				case 'color':
					{
						return lang.DIMENSION_GRID_COLOR
						break;
					}
				case 'size':
					{
						return lang.DIMENSION_GRID_SIZE
						break;
					}
			}
		},

		/**
		 * Вывод имеющихся настроек в	разделе пользовательские характеристики
		 */
		print: function(cat_id,update, page, filter) {
			//если список была ранее загружен, то не повторяем этот процесс
			if ($('.userField-settings-list').text() != "" && !update) {
				return false;
			}
			// получаем с сервера все доступные пользовательские параметры
			admin.ajaxRequest(							{
				mguniqueurl: "action/getUserProperty",
				cat_id: cat_id,
				page: page,
				name: $('#tab-userField-settings form[name="filter"] input[name="name[]"]').val(),
				type: $('#tab-userField-settings form[name="filter"] select[name="type"]').val(),
				group_id: $('#tab-userField-settings form[name="filter"] select[name="group_id"]').val(),
			},
			function(response) {

				var html = '<table id="userPropertySetting" class="main-table">\
						<thead class="yellow-bg">\
							<th class="border-top checkbox-cell" style="width:30px;">\
								<div class="checkbox" tooltip="'+lang.CHOOSE_ALL_ROWS+'" flow="leftUp">\
									<input type="checkbox" id="c-all">\
									<label for="c-all" class="check-all-page" aria-label=" '+lang.CHOOSE_ALL_ROWS+'"></label>\
								</div>\
							</th>\
							<th class="border-top" style="width: 20px;"> id </th>\
							<th class="border-top" style="width: 20px;"></th>\
							<th class="border-top" style="width: 180px;">' + lang.STNG_USFLD_TYPE + '</th>\
							<th class="border-top">' + lang.STNG_USFLD_NAME + '</th>\
							<th style="width:200px;" class="border-top text-right">' + lang.ACTIONS + '</th>\
						</thead><tbody class="userPropertyTable">';
				function buildRowsUserField(element, index, array) {
					var is_string = false;
					if (element.type == 'string') {
						is_string = true
					}

					var activity = element.activity==='1'?'active':'';
					// var titleActivity = element.activity==='1'?lang.ACT_V_PROP:lang.ACT_UNV_PROP;
					var titleActivity = lang.ACT_V_PROP;

					var filter = element.filter==='1'?'active':'';
					// var titleFilter = element.filter==='1'?lang.ACT_FILTER_PROP:lang.ACT_UNFILTER_PROP;
					var titleFilter = lang.ACT_FILTER_PROP;

					if(typeof element.mark != 'undefined' && element.mark != '') {
						var mark = ' <span class="badge">'+element.mark+'</span>';
					} else {
						var mark = '';
					}

					html += '<tr id=' + element.id + ' data-id=' + element.id + '>\
							<td class="check-align" style="cursor:move;">\
								<div class="checkbox">\
									<input type="checkbox" id="c' + element.id + '" name="property-check">\
									<label for="c' + element.id + '" class="shiftSelect"></label>\
								</div>\
							</td>\
							<td class="id">'+element.id+'</td>\
							<td class="mover"><i class="fa fa-arrows" aria-hidden="true"></i></td>\
							<td class="type"><span value="' + element.type + '">' + userProperty.typeToRead(element.type) + '</span></td>\
							<td class="name">' + admin.htmlspecialchars(admin.htmlspecialchars_decode(element.name)) + mark + '</td>\
							<td class="actions">\
								<ul class="action-list text-right">\
									<li class="edit-row"><a class="tooltip--center tooltip--small" flow="rightUp" href="javascript:void(0);" aria-hidden="true" tooltip="' + lang.EDIT + '"><i class="fa fa-pencil" aria-hidden="true"></i></a></li>\
									<li class="visible tool-tip-bottom" data-id="'+element.id+'" ><a role="button" flow="rightUp" tooltip="'+titleActivity+'" href="javascript:void(0);" class="'+activity+' tooltip--center"><i class="fa fa-eye" aria-hidden="true"></i></a></li>\
									<li class="filter-prop-row"><a class="'+filter+' tooltip--center" flow="rightUp" href="javascript:void(0);" aria-hidden="true" tooltip="'+titleFilter+'"><i class="fa fa-filter" aria-hidden="true"></i></a></li>\
									<li class="delete-property"><a class="tooltip--xs tooltip--center" flow="rightUp" href="javascript:void(0);" aria-hidden="true" tooltip="' + lang.DELETE + '"><i class="fa fa-trash" aria-hidden="true"></i></a></li>\
								</ul>\
							</td>\
						</tr>';
				};

				if (response.data.allProperty.length != 0) {
					if(response.data.pageSort != undefined && response.data.pageSort.min != null) html += '<tr id="'+response.data.pageSort.min+'" data-id="'+response.data.pageSort.min+'" class="pageSort"><td colspan="6">Переместить на предыдущую страницу (Перенесите за эту строку)</td></tr>';
					response.data.allProperty.forEach(buildRowsUserField);
					if(response.data.pageSort != undefined && response.data.pageSort.max != null) html += '<tr id="'+response.data.pageSort.max+'" data-id="'+response.data.pageSort.max+'" class="pageSort"><td colspan="6">Переместить на следующую страницу (Перенесите за эту строку)</td></tr>';
				} else {
					html += '<tr class="tempMsg">\
							<td colspan="6" align="center">' + (response.data.displayFilter ? lang.USER_NONE : lang.STNG_USFLD_MSG) + '</td>\
						 </tr>';
				}
				html += '</tbody></table>';
				$('.userField-settings-list').html(html);
				$('#tab-userField-settings .filter-container').html(response.data.filter);
				if (response.data.displayFilter) {
					 $('#tab-userField-settings .filter-container').show();
				}
				if ($('.section-settings #tab-userField-settings .mg-pager').length > 0) {
					$('.section-settings #tab-userField-settings .mg-pager').remove();
				}
				$('#tab-userField-settings .to-paginator').html(response.data.pagination);
				if (page) {
					$('.tabs-list li').removeClass('ui-state-active');
					$('.main-settings-container').css('display', 'none');
					$('#tab-userField').parent('li').addClass('ui-state-active');
					$('#tab-userField-settings').css('display', 'block');
				}
				admin.sortable('.userPropertyTable','property', true);
				admin.initToolTip();


			},
							$('.userField-settings-list')
							);
		},
		//Добавляет новую строку
		addRow: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/addUserProperty"
			},
			function(response) {
				admin.indication(response.status, response.msg);

				var html = '<tr id=' + response.data.allProperty.id + '>\
								<td class="check-align" style="cursor:move">\
									<div class="checkbox">\
										<input type="checkbox" id="c' + response.data.allProperty.id + '" name="property-check">\
										<label for="c' + response.data.allProperty.id + '" class="shiftSelect"></label>\
									</div>\
								</td>\
								<td class="id">' + response.data.allProperty.id + '</td>\
								<td class="mover"><i class="fa fa-arrows ui-sortable-handle" aria-hidden="true"></i></td>\
								<td class="type"><span value="' + response.data.allProperty.type + '">' + lang.NO_SELECT + '</span></td>\
								<td class="name">' + response.data.allProperty.name + '</td>\
									<td class="actions text-right">\
										<ul class="action-list" style="width:100%;">\
											<li class="edit-row" id="' + response.data.allProperty.id + '"><a class="tool-tip-bottom" flow="rightUp" href="javascript:void(0);" tooltip="' + lang.EDIT + '"><i class="fa fa-pencil" aria-hidden="true"></i></a></li>\
											<li class="visible tool-tip-bottom" data-id="'+response.data.allProperty.id+'" ><a role="button" flow="rightUp" tooltip="'+lang.ACT_V_PROP+'" href="javascript:void(0);" class="active tooltip--center"><i class="fa fa-eye"></i></a></li>\
											<li class="filter-prop-row" data-id="'+response.data.allProperty.id+'" ><a role="button" flow="rightUp" tooltip="'+lang.ACT_UNFILTER_PROP+'" href="javascript:void(0);"><i class="fa fa-filter" aria-hidden="true"></i></a></li>\
											<li class="delete-property" id="' + response.data.allProperty.id + '"><a class="tool-tip-bottom" flow="rightUp" href="javascript:void(0);" tooltip="' + lang.DELETE + '"><i class="fa fa-trash" aria-hidden="true"></i></a></li>\
										</ul>\
									</td>\
							</tr>';

				if ($(".userField-settings-list tr[class=tempMsg]").length != 0) {
					$(".userPropertyTable").html('');
				}

				$('.userPropertyTable').prepend(html);

				$('.userField-settings-list tr:eq(1) .edit-row').click();
			});
		},
		deleteRow: function(id) {
			if (confirm(lang.DELETE + '?')) {
				admin.ajaxRequest({
					mguniqueurl: "action/deleteUserProperty",
					id: id
				},
				(function (response) {
					admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						$('.userPropertyTable tr[id=' + id + ']').remove();
						if ($(".userPropertyTable tr").length == 0) {
							var html = '<tr class="tempMsg">\
									<td colspan="5" align="center">' + lang.STNG_USFLD_MSG + '</td>\
								 </tr>';
							$('.userPropertyTable').append(html);
						}
					};
				}));
			}
		},
		/**
		 * сортировка свойств по алфавиту
		 */
		propertySort: function(arr) {
			return arr.sort(function(a, b) {

				var compA = a.toLowerCase();
				var compB = b.toLowerCase();
				return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
			})
		},
		/*
		 * Возвращает значение наценки из характеристики, которое отделяется от названия #Цена#
		 * пример красный#700# получим 700 и название красный.
		 */
		getMarginToProp: function(str) {
			// str = admin.htmlspecialchars(str);
			var margin = /#((-|\+)?\d*(.|,)?\d*%?)#/g.exec(str);
			var parseString = {name: str, margin: 0}
			if (margin != null) {
				parseString = {name: str.slice(0, margin.index), margin: margin[1]}
			}
			return parseString;
		},

		// Сохраняет установленные наценки для каждого пункта характеристик
		// hiddenData - скрытое значение пунктов, записанное в одну строку с разделителями,
		// propId - номер характеристики
		saveMagrin: function(propId, type) {
			var hiddenData = $('.userPropertyTable tr[id=' + propId + '] .hiddenPropertyData');

			var hiddenDataText = hiddenData.text();
			if(type=='string') {
				hiddenDataText = hiddenData.html();
			}

			if($('.userPropertyTable tr[id=' + propId + '] .itemData ').length!=0) {
				hiddenDataText = "";

				$('.userPropertyTable tr[id=' + propId + '] .itemData ').each(function() {

					//если в поле введено число болье нуля то записываем его к характеристикам
					var margin = $(this).find('.setMargin input[type=text]').val();
					if (margin * 1 != 0 && !isNaN(margin)) {
						margin = '#' + margin + '#';
					} else {
						margin = "";

						if(type=='select') {
							margin = "#0#";
						}

					}
					if (type=='assortmentCheckBox'&&$('.propertyDataNameEdit input[name=valuenew]').is(':visible')) {
						hiddenDataText += $(this).find('.propertyDataNameEdit input[name=valuenew]').val() + margin + userProperty.delimetr;
					} else {
						hiddenDataText += $(this).find('.propertyDataName').text() + margin + userProperty.delimetr;
					}

				});
				hiddenDataText = hiddenDataText.slice(0, -1);
			}

			hiddenData.text(hiddenDataText);
		},

		/**
		 *
		 * @param {type} propId - id характеристики
		 * @param {type} val - значение	по умолчанию
		 * @returns {undefined}
		 */
		setDefVal: function(propId, val) {
			var data = $('.userPropertyTable tr[id=' + propId + '] td[class=default]').text(val);
		},
		/**
		 * Панель для настройки наценок к каждому товару
		 * select - объект содержащий все доступные значения характеристики
		 */
		panelMargin: function(select) {

			var html = '<div class = "panelMargin custom-popup"><h4>Наценки значений характеристик:</h4>';
			var counter = 0;
			select.find('option').each(function() {
				counter++;
				var parseProp = userProperty.getMarginToProp($(this).val());
				var selected = '';
				if ($(this).attr('selected') == 'selected' || $(this).prop('selected')) {
					selected = ' selected="selected" ';
				}
				var currency = $('#add-product-wrapper .currency-block select[name=currency_iso] option:selected').text();
				html += '<div class="row sett-line">\
									<div class="panelMargin-unit small-6 columns">\
										<label for="unit_'+counter+'" class="'+parseProp.id+'">' + parseProp.name + ':</label>\
									</div>\
									<div class="small-6 columns">\
										<input id="unit_'+counter+'" placeholder="Например, 500 или 30%" type="text" '+ selected + " value='" + parseProp.margin + "' data-propname='" + parseProp.name + "' class='price-input'/>"+currency+
									'</div>\
								</div>';
			});
			html += '<div class="panelMargin__footer clearfix">\
								<a role="button" href="javascript:void(0);" class="apply-margin tool-tip-bottom button success fl-right" title="'+lang.APPLY+'">'+lang.APPLY+'</a>\
							</div></div>';
			return html;
		},
		/**
		 * Применяет установленные в panelMargin наценки
		 * tr - строка таблицы полей в которой хранятся наценки и список
		 */
		applyMargin: function(tr) {
			var i = 0;
			// формируем новый список из данных в панели наценок
			tr.find('.panelMargin input[type=text]').each(function() {
				let marginValue = $(this).val();
				const origMarginValue = marginValue;
				if(marginValue.indexOf("%") > 0){
					marginValue = marginValue.slice(0, -1);
				}
				if(isNaN(marginValue)) {
					$(this).val('0');
				}
				marginValue = origMarginValue;
				if(marginValue != '') {
					if (admin.isoHuman == undefined) {
						margHtml = ' ('+marginValue+' '+catalog.getShortIso(admin.CURRENCY_ISO)+')';
					} else {
						margHtml = ' ('+marginValue+' '+admin.isoHuman+')';
					}
				} else {
					margHtml = '';
				}
				tr.find('select option:eq('+i+')').text($(this).data('propname')+margHtml);
				tr.find('select option:eq('+i+')').val($(this).data('propname') + '#' + marginValue + '#');
				i++;
			});
			// вставляем сформированный список	на место
		},
		/**
		 * Заполняет поля модального окна продуктов данными
		 * allProperty - объект содержащий все доступные пользовательские характеристики
		 * userFields - объект содержит	значения пользовательских характеристик для текущего продукта
		 */
		createUserFields: function(container, userFields, allProperty, propertyGroup) {

			if (!allProperty)
				return false;
			var htmlOptions = '';
			var htmlOptionsSelected = '';
			var htmlOptionsSetup = ''; // установленные наценки для текущего продукта
			var htmlUserField = '';
			var htmlCheckBox = '';
			var curentProperty = '';
			//строит html элементы из полученных данных
			function printToLog(element, index, array) {
				// console.log("a[" + element.id + "] = " +
				//				 " - " + element.name +
				//				 " - " + element.type +
				//				 " - " + element.default +
				//				 " - " + element.data
				//				 );
			}

			// Проверяет,
			// было ли уже установлено пользовательское свойство,
			// и возвращает его значение
			// propertyId - идентификатор свойства
			function getUserValue(propertyId) {
				var userValue = false;
				if (!userFields) {
					return;
				}
				userFields.forEach(function(element, index, array) {
					if (element.property_id == propertyId) {
						userValue = {value: element.value, product_margin: element.product_margin, type_view:element.type_view};
					}
				});
				return userValue;
			}


			function buildCheckBox(element, index, array) {
				var checked = '';

				// для мульти списка проверяем наличие	значения в массиве htmlOptionsSelected
				// if (htmlOptionsSelected instanceof Array) {
				//	 if (htmlOptionsSelected.indexOf(element+'#0#') != -1 || htmlOptionsSelected.indexOf(element) != -1) {
				//		 checked = 'checked="checked"';
				//	 }
				// } else {
					// для простого селекта соответствие значению htmlOptionsSelected
					// if (htmlOptionsSelected == element) {
					//	 checked = 'checked="checked"';
					// }
				// }
				var random = Math.random();
				if(element.id) {
					id = element.id;
				} else {
					id = 'temp-'+tmpCount;
					tmpCount++;
				}
				if(element.active == 1) {
					checked = 'checked="checked"';
				} else {
					checked = '';
				}

				htmlCheckBox += '<label for="' + element.name + random + '" class="admin-options">\
													<div class="checkbox admin-options__inner">\
														<input type="checkbox" data-id="'+id+'" data-prop-id="'+element.prop_id+'" data-prop-data-id="'+element.prop_data_id+'" id="' + element.name + random + '" class="admin-options__checkbox propertyCheckBox property" ' + checked + ' name="' + admin.htmlspecialchars(element.name) + '"/>\
														<label class="admin-options__label" for="' + element.name + random + '"></label>\
														<span class="admin-options__title">' + admin.htmlspecialchars(element.name) + '</span>\
													</div>\
												</label>';
			}


			function buildOption(element, index, array) {
				if(element.active == 1) {
					selected = 'selected="selected"';
				} else {
					selected = '';
				}
				if(element.id) {
					id = element.id;
				} else {
					id = 'temp-'+tmpCount;
					tmpCount++;
				}

				if(element.margin) {
					if (admin.isoHuman == undefined) {
						margHtml = ' ('+element.margin+' '+catalog.getShortIso(admin.CURRENCY_ISO)+')';
					} else {
						let isPercent = false;
						if (element.margin.indexOf('%') >= 0) {
							isPercent = true;
						}
						margHtml = ' ('+element.margin+(isPercent ? '' : ' '+admin.isoHuman)+')';
					}
				} else {
					margHtml = '';
				}
				htmlOptions += '<option data-id="'+id+'" data-prop-data-id="'+element.prop_data_id+'" data-margin="'+element.prop_id+'" ' + selected + ' value="' + element.name + '#' + element.margin + '#">' + element.name + margHtml +'</option>';
			}


			//строит html элементы из полученных данных
			function buildElements(property, index, array) {
				// если наименование не задано то не выводить характеристику
				if(property.name==null) {
					return false;
				}

				var html = '';
				var created = false;

				if (property.type == 'diapason') {
					var userValue = getUserValue(property.id);

					if(!property['data'][0]['id']) {
						property['data'][0]['id'] = 'temp-'+tmpCount;
						if(property['data'][1] == undefined) property['data'][1] = {};
						property['data'][1]['id'] = 'temp-'+tmpCount;
						property['data'][0]['name'] = '';
						property['data'][1]['name'] = '';
						tmpCount++;
					}
					var value = (userValue.value) ? userValue.value : '';

					html = '<div class="new-added-prop">' +
								'<div class="new-added-prop__innerwrap">' +
									'<div class="medium-5 small-12 columns">' +
										'<label class="dashed">' +
											property.name +
							 				(property.unit ? '('+property.unit+')' : '') + ': ' +
										'</label>' +
									'</div>' +
									'<div class="medium-7 small-12 columns">' +
										'<input class="property custom-input" data-type="diapason" data-from="min" data-id="' + property['data'][0]['id'] + '" style="margin:0;width:calc(50% - 9px);display:inline-block;" name="' + property.id + '" type="number" value="' + admin.htmlspecialchars(property['data'][0]['name']) + '">' +
										'<span>&nbsp;-&nbsp;</span>' +
										'<input class="property custom-input" data-type="diapason" data-from="max" data-id="'+property['data'][1]['id']+'" style="margin:0;width:calc(50% - 9px);display:inline-block;" name="' + property.id + '" type="number" value="' + admin.htmlspecialchars(property['data'][1]['name']) + '">' +
									'</div>' +
								'</div>' +
							'</div>';

					created = true;
				}

				// для пользовательского поля типа string
				if (property.type == 'string') {
					// console.warn(property);
					var userValue = getUserValue(property.id);
					if(!property['data'][0]['id']) {
						property['data'][0]['id'] = 'temp-'+tmpCount;
						tmpCount++;
					}
					var value = (userValue.value) ? userValue.value : '';

					html = '<div class="new-added-prop">' +
								'<div class="new-added-prop__innerwrap">' +
									'<div class="medium-5 small-12 columns">' +
										'<label class="dashed">' +
											property.name + (property.unit ? '('+property.unit+')' : '') + ': ' +
										'</label>' +
									'</div>' +
									'<div class="medium-7 small-12 columns">' +
										'<input class="property custom-input" data-id="'+property['data'][0]['id']+'" data-margin="'+property.prop_id+'" style="margin:0;" name="' + property.id + '" type="text" value="' + admin.htmlspecialchars(property['data'][0]['name']) + '">' +
									'</div>' +
								'</div>' +
							'</div>';
					created = true;
				}
					// для пользовательского поля типа текстовое поле
				if (property.type == 'textarea') {
					if(!property['data'][0]['id']) {
						property['data'][0]['id'] = 'temp-'+tmpCount;
						tmpCount++;
					}
					var userValue = getUserValue(property.id);
					var value = (userValue.value) ? userValue.value : '';

					html = '' +
						'<div class="new-added-prop">' +
							'<div class="new-added-prop__innerwrap">' +
								'<div class="medium-5 small-12 columns">' +
									'<label class="dashed">' + property.name + ': </label>' +
								'</div>' +
								'<div class="medium-7 small-12 columns">' +
									'<a role="button" href="javascript:void(0);" class="property custom-textarea link" data-id="' + property['data'][0]['id'] + '" data-margin="' + property.prop_id + '" data-name="' + property.id + '" name="' + property.id + '">Открыть редактор</a>' +
									'<span class="value" style="display:none">' + admin.htmlspecialchars(property['data'][0]['name']) + '</span>' +
								'</div>' +
							'</div>' +
						'</div>';
					created = true;
				}

				// для пользовательского поля типа assortment или select
				if (property.type == 'select' || property.type == 'assortment') {
					var multiple = (property.type == 'assortment')?'multiple':'';// определяем будет ли строиться мульти список или обычный
					html = '<div class="new-added-prop">' +
								'<div class="new-added-prop__innerwrap">' +
									'<div class="medium-5 small-12 columns">' +
										'<label class="dashed">' + property.name + ': </label>' +
									'</div>' +
									'<div class="medium-7 small-12 columns">' +
										'<div class="price-settings">' +
							 				'<div class="price-body">' +
												'<select class="property last-items-dropdown select" name="' + property.id + '"'+multiple+' style="max-height:none;">';
					// обнуляем список опций
					htmlOptions = '';

					// получаем	настройки характеристики (выбранные пункты и их стоимости в текущем товаре)
					var userValue = getUserValue(property.id);

					var arrayValues = null;
					// если ранее настройки небыли установлены в товаре, то берутся дефолтные, заданные в разделе характеристик
					// if (userValue) {
						// arrayValues = userValue.value.split(userProperty.delimetr);
					// } else {
					//	 arrayValues = property.default.split(userProperty.delimetr);
					// }

					htmlOptionsSelected = []; // массив выделенных пунктов списка БЕЗ ЦЕН, чтобы можно было сравнить с дефолтным пунктами и выделить нужные
					property.data.forEach(function(element, index, array) {
						var dataProp = userProperty.getMarginToProp(element);
						htmlOptionsSelected.push(dataProp);
					});


					htmlOptionsSetup = []; // массив остановленных ранее настроек для текущего товара значений
					if(userValue.product_margin) {
						userValue.product_margin.split(userProperty.delimetr).forEach(function(element, index, array) {
							var dataProp = userProperty.getMarginToProp(element);
							htmlOptionsSetup.push(dataProp);
						});
					}

					// генерируем список опций
					property.data.forEach(buildOption);

					// присоединяем список опций к основному контенту
					html += htmlOptions;
					var options = property.data;
					// закрываем селект
					html += '</select></div>';
						// формируем панель кнопок устанавливающих тот или иной тип вывода характеристики
					html += '<div class="price-footer">' +
								'<div class="link-holder clearfix">' +
									'<a role="button" href="javascript:void(0);" class="toggle-properties link fl-left" style="display:'+(options.length > 4 ? 'inline-block': 'none')+'">' +
										'<i class="fa fa-expand" aria-hidden="true"></i>' +
										'<span>' + lang.PROD_OPEN_CAT + '</span>' +
									'</a>' +
									'<a role="button" href="javascript:void(0);" class="clear-select-property link fl-right">' +
										'<span>'+lang.PROD_CLEAR_CAT+'</span>' +
									'</a>' +
								'</div>' +
								'<div class="buttons-holder clearfix">' +
									'<div class="popup-holder fl-left">' +
										'<a role="button" href="javascript:void(0);" class="link setup-margin-product" flow="up" tooltip="'+lang.T_TIP_SETUP_MARGIN+'" >' +
											'<span>'+lang.SETUP_MARGIN+'</span>' +
										'</a>' +
									'</div>';


					// формирование панели настроек вывода в публичной части
					var selected,selectedType = '';
					html +='<div class="icon-buttons clearfix fl-right">';

					//	вывод чекбоксами доступен только для мульти селекта
					if (multiple === 'multiple') {
						selected = '';
						for (i = 0; i < property.data.length; i++) {
						if (property.data[i].type_view === "checkbox") {
							selected = "selected";
							selectedType = "checkbox";
							break;
						}
						}
						html += '<a role="button" href="javascript:void(0);" aria-label="' + lang.T_TIP_PIRNT_CHECK + '" tooltip="' + lang.T_TIP_PIRNT_CHECK + '" flow="rightUp" class="type-checkbox setup-type ' + selected + '" data-type="checkbox"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
					}

					selected = "";
					for (i = 0; i < property.data.length; i++) {
						if (property.data[i].type_view === "radiobutton") {
						selected = "selected";
						selectedType = "radiobutton";
						break;
						}
					}
					html += '<a role="button" href="javascript:void(0);" aria-label="'+lang.T_TIP_PIRNT_RADIO+'" tooltip="'+lang.T_TIP_PIRNT_RADIO+'" flow="rightUp" class="type-radiobutton setup-type '+selected+'" data-type="radiobutton"><i class="fa fa-dot-circle-o" aria-hidden="true"></i></a>';
			
					selected = "";
					for (i = 0; i < property.data.length; i++) {
						if ((property.data[i].type_view === "select" || !property.data[i].hasOwnProperty("type_view")) && selectedType == '') {
							selected = "selected";
							break;
						}
					}
					html +='<a role="button" href="javascript:void(0);" aria-label="' + lang.T_TIP_PRINT_SELECT + '" tooltip="' +lang.T_TIP_PRINT_SELECT + '" flow="rightUp" class="type-select setup-type ' + selected + '" data-type="select"><i class="fa fa-list" aria-hidden="true"></i></a>';
	
					html += '</div>';

					html += '</div></div></div></div></div></div>';
					created = true;
				}

				// для пользовательского поля типа assortmentCheckBox
				if (property.type == 'assortmentCheckBox') {
					let searchHtml = '';
					if (property.data.length > 4) {
						searchHtml = '<div class="assortmentCheckBox_btn"><span class="assortmentCheckBox_btn-text">' + lang.SEARCH_BTN + '</span><i class="fa fa-search"></i></div><div class="assortmentCheckBox__search"><input class="assortmentCheckBox__input" type="text" placeholder="' + lang.PLACEHOLDER_SEARCH_PROPERTY + '" /></div>';
					}
					html = '' +
						'<div class="new-added-prop">' +
							'<div class="new-added-prop__innerwrap">' +
								'<div class="medium-5 small-12 columns">' +
									'<label class="dashed">' + property.name + ':</label>' +
								'</div>' +
							 	'<div class="medium-7 small-12 columns">' +
									'<div class="assortmentCheckBox" data-property-id="' + property.id + '">' + searchHtml;

					// обнуляем список опций
					htmlCheckBox = '';

					// устанавливаем выбранный элемент, чтобы отловить
					// его при построении опций и выделить его в списке
					var userValue = getUserValue(property.id);
					htmlOptionsSelected = (userValue.value) ? userValue.value.split(userProperty.delimetr) : (property.default ? property.default.split(userProperty.delimetr) : '');

					curentProperty = property.id;
					// генерируем список чекбоксов
					property.data.forEach(buildCheckBox);

					// присоединяем список опций к основному контенту
					html += htmlCheckBox;

					// закрываем селект
					html += '</div></div></div></div>';
					created = true;
				}

				// для пользовательского поля типа string
				if (property.type == 'file') {
					var userValue = getUserValue(property.id);
					if(!property['data'][0]['id']) {
						property['data'][0]['id'] = 'temp-'+tmpCount;
						tmpCount++;
					}
					var value = admin.htmlspecialchars(property['data'][0]['name']);
					let fileSelectStyle = '';
					if (value) {
						fileSelectStyle = 'style="display: none;"';
					}					
					let htmlFileProp = '';
					if(property['data'] && property['data'][0]['name']){
						let arrFiles = property['data'][0]['name'].split('|');						
						arrFiles.forEach(function(el, index){
							let name = admin.htmlspecialchars(el);	
							let linkFile = mgBaseDir + name;
							let fileName = name.split('/').pop();
							htmlFileProp += userProperty.getHtmlFieldFileProp(index + 1, linkFile, fileName, name);
						});
					}
					html = '<div class="new-added-prop">' +
								'<div class="new-added-prop__innerwrap">' +
									'<div class="medium-5 small-12 columns">' +
										'<label class="dashed">' +
											property.name + ': ' +
										'</label>' +
									'</div>' +
									'<div class="medium-7 small-12 columns">' +
										// кнопка выбора файла
										'<a ' +
											'type="button" ' +
											'href="javascript:void(0);" ' +
											'class="link property-file-select" ' +
											'data-id="'+ property['data'][0]['id'] + '" ' +
											fileSelectStyle +
										'><i class="fa fa-upload" aria-hidden="true"></i> <span>Выбрать файл</span></a>' +
										// Выбранный файл с кнопкой удаления
										'<div ' + 
											'class="property-file-name-container" ' +
											'data-id="'+ property['data'][0]['id'] +'" ' +
											'> ' + 	htmlFileProp +
										'</div>' +
										'<input class="property custom-input" data-id="'+property['data'][0]['id']+'" data-margin="'+property.prop_id+'" style="margin:0;" name="' + property.id + '" type="hidden" value="' + admin.htmlspecialchars(property['data'][0]['name']) + '">' +
									'</div>' +
								'</div>' +
							'</div>';
					created = true;
				}

					/*Дублирует к каждой характеристике по одному пустом блоку*/
					htmlUserField += '<div class="userfd bt">' + html + '</div>';

			}
			
			htmlUserField = '';
			let htmlUserFieldGroup = '';
			//Массив отрисованных харакеристик
			if(propertyGroup !== undefined && propertyGroup.length > 0){
				let drowPropArray = [];
				htmlUserField += '<ul class="accordion accordion-group-prop" data-accordion="" data-multi-expand="true" data-allow-all-closed="true">'
				propertyGroup.forEach(function(group, i, a){
					htmlUserField += '<li class="accordion-item accordion-item-group-prop" data-accordion-item="">';
					htmlUserField += '<button class="accordion-title-group-prop" title="'+group.name+'">'+group.name+'</button>';
					htmlUserField += '<div style="display: none;" class="accordion-content-group-prop" data-tab-content="" >';
					htmlUserField += '<br>';
					allProperty.forEach(function(property, index, array){
						if(group.id == property.group_id){
							//console.log(property);
							drowPropArray.push(property.id);
							buildElements(property, index, array)
						}
					})
					htmlUserField += '</div>';
					htmlUserField += '</li>';
				});
				htmlUserField += '</ul>';
				htmlUserFieldGroup = htmlUserField;
				htmlUserField = '';
				if(drowPropArray.length > 0){
					allProperty.forEach(function(property, index, array){
						if(drowPropArray.indexOf(property.id) == -1){
							buildElements(property, index, array);
						}
					});
				}
				htmlUserField += htmlUserFieldGroup;
			}else{
				allProperty.forEach(buildElements);
			}
			function openSearchProperties() {
				$(this).closest('.assortmentCheckBox').find('.assortmentCheckBox__search').show();
				$(this).closest('.assortmentCheckBox').find('.assortmentCheckBox__input').focus();
				$(this).hide();
			}
			function searchProperties() {
				if ($(this).val().length > 0) {
					$(this).closest('.assortmentCheckBox').find('.admin-options .admin-options__title').each((index, element) => {
						const strArray = $(element).text().toLowerCase().split('');
						const searchStrArray = $(this).val().toLowerCase().split('');
						let counter = 0;
						let findFlag = false;
						strArray.forEach((char, index) => {
							if (searchStrArray[counter] == char) {
								counter += 1;
								if (counter == searchStrArray.length) {
									findFlag = true;
									return;
								}
							}
						});
						if (findFlag) {
							$(element).closest('.admin-options').show();
						} else {
							$(element).closest('.admin-options').hide();
						}
					});
				} else {
					$(this).closest('.assortmentCheckBox').find('.admin-options .admin-options__title').each((index, element) => {
						$(element).closest('.admin-options').show();
					});
				}
			}
			container.html(htmlUserField);
			$('.assortmentCheckBox__input').on('keyup', searchProperties);
			$('.assortmentCheckBox_btn').on('click', openSearchProperties);
		},

		/**
		 * Разворачивает список доступных значений в таблице характеристик
		 * button - объект клик по которому открывает список
			*/
		showOptions: function(button) {
			if(button.data('visible')=='hide') {
				button.parents('td').find('.itemData').each(function(i,element) {
					if(i > 3) {
						$(this).hide();
					}
				});
				button.text('Показать все');
				button.data('visible','show');
			} else {
				button.parents('td').find('.itemData').show();
				button.text('Свернуть список');
				button.data('visible','hide');
			}

		},

		 // Устанавливает статус - видимый
		 visibleProperty:function(id, val) {
			admin.ajaxRequest({
				mguniqueurl:"action/visibleProperty",
				id: id,
				activity: val,
			},
			function(response) {
				admin.indication(response.status, response.msg);
			}
			);
		},

		 // Устанавливает статус - выводить в фильтрах
		 filterVisibleProperty:function(id, val) {
			admin.ajaxRequest({
				mguniqueurl:"action/filterVisibleProperty",
				id: id,
				filter: val,
			},
			function(response) {
				admin.indication(response.status, response.msg);
			}
			);
		},

		applyPropValueToAllProducts: function(propValueId, propNewValue) {
			admin.ajaxRequest(
				{
					mguniqueurl: 'action/applyPropValueToAllProducts',
					propValueId: propValueId,
					propNewValue: propNewValue
				},
				function(response) {
					admin.indication(response.status, response.msg);
				}
			);
		},

		getHtmlFieldFileProp: function(id, url, name, path){
			return '<div class="filePropValue" data-id="'+ id +'" data-val="'+ path +'">' +
						'<a ' +
							'href="'+ url +'" ' +
							'target="_blank" ' +
							'class="link property-file-name" ' +
							'data-id="'+ id +'"' +
						'><span>'+ name +'&nbsp;</span></a>' +
						'<a ' +
							'type="button" ' +
							'href="javascript:void(0);" ' +
							'class="link property-file-remove" ' +
							'data-id="'+ id+'" ' +
						'>&nbsp;<i class="fa fa-times" aria-hidden="true"></i></a>' +
					'</div>'; 
		},

		addFileToProp: function(file, id) {
			let separator = '|';
			let propInput = $('input.property[data-id="'+id+'"');
			let container = $('.property-file-name-container[data-id="'+id+'"]');
			let htmlBlock = '';
			let filesPaths = null;
			container.find('.filePropValue').remove();
			if(Array.isArray(file)){
				filesPaths = file.map(function(el, index) {
					htmlBlock = userProperty.getHtmlFieldFileProp(index + 1, el.url, el.name, '/' + el.path);
					container.append(htmlBlock);
					const filePath = '/' + el.path;
					return filePath;
				});
			}
			if(filesPaths){
				propInput.val(filesPaths.join(separator));
			} else {
				admin.indication('error', 'Ошибка при выборе файлов');
				return false;
			}
			$('.property-file-select[data-id="'+id+'"]').hide();
		},

		removeFileFromProp: function(id, propId) {
			let container = $('.property-file-name-container[data-id="'+propId+'"]');
			let propInpt = $('input.property[data-id="'+propId+'"');
			let propInptValArr = [];
			let propInptNewVal = [];
			let removedValues = [];
			container.find('.filePropValue').each(function(el){
				if($(this).attr('data-id') == id){
					$(this).remove();
					removedValues.push($(this).attr('data-val'));
				}
			});
			if(propInpt.val()){
				propInptValArr = propInpt.val().split('|');
				propInptNewVal = propInptValArr.filter(function(inptVal){
					if(!removedValues.includes(inptVal)){
						return true
					}
				}).join('|');
			}
			if(propInptNewVal.length){
				propInpt.val(propInptNewVal);
			} else {
				propInpt.val('');
			}
			if(container.find('.filePropValue').length == 0) {
				container.siblings('.property-file-select').show();
			}			
		}

	}
})();
