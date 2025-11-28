/**
 * Модуль для  раздела "Товары".
 */
 var catalog = (function () {
	return {
		errorVariantField: false,
		parseSeparator: ';',
		memoryVal: null, // HTML редактор для редактирования страниц
		supportCkeditor: null,
		deleteImage: '', // список картинок помеченных на удаление при сохранении товара, данный список передается на сервер и картинки удаляются физически
		tmpImage2Del: '',
		dragActive: false,
		selectedStorage: 'all',
		modalUnit: 'шт.',
		undefinedColorName: 'не определён', //TODO: загрузка локали
		exportType: '', // Тип экспорта из екселя (загрухка или обновление);
		updateMod: 'title', // Тип обновления (по заголовку, по артикулу)
		init: function() {
			includeJS(mgBaseDir + '/mg-core/script/admin/settings/tab-userfield-settings.js');
			includeJS(admin.SITE + '/mg-core/script/jquery.bxslider.min.js');
			catalog.initEvents();
			admin.sliderPrice();
			catalog.initMovers();

			// для блокировки при редактировании
			catalog.checkBlockInterval = setInterval(catalog.checkBlockIntervalFunction, 1000);

			let opFieldsList = $('.product-table th.hide-for-small-only');
			opFieldsList.each(function (index, element) {
				let oldValue = $(this).html();
				if (oldValue.indexOf('field-sorter') == -1) {
					$(this).html(admin.htmlspecialchars(admin.htmlspecialchars_decode(oldValue)));
				}
			});
			newbieWay.checkIntroFlags('catalogScenario', false);
		},
		/**
		 * Инициализирует обработчики для кнопок и элементов раздела.
		 * TODO: $('.section-catalog') нужно вынести в константу, чтобы не искать его каждый раз заново!!
		 */
		initEvents: function() {
			$('.section-catalog').on('click', '.js-show-inside-cats', function () {
				$('.js-inside-category').slideToggle();
				if ($(this).text().trim() === '+') {
					$(this).text('—');
				} else if ($(this).text().trim() === '—') {
					$(this).text('+');
				}
			});

			$('.section-catalog').on('change', '#productCategorySelect', function () {
				if ($('#productCategorySelect').val() == 0) {
					$('.add-property-field').hide();
				} else {
					$('.add-property-field').show();
				}
			});

			$('.section-catalog').on('change', '.tipo-radio input', function () {
				if ($(this).prop('checked')) {
					$('.tipo-radio[data-group=salo] input').prop('checked', false);
					$(this).prop('checked', true);
				} else {
					$('.tipo-radio[data-group=salo] input').prop('checked', false);
				}
			});

			$('.section-catalog').on('click', '.js-accordion-related-open', function () {
				let prodName = $(this).parents('.product-text-inputs').find('[name="title"]').val();
				$(this).siblings('.accordion-content').find('.js-insert-name').text(prodName);
			});

			// для показа картинок вариантов
			$('.section-catalog').on({
				mouseenter: function () {
					$(this).parents('tr').find('.img-this-variant').show();
				},
				mouseleave: function () {
					$(this).parents('tr').find('.img-this-variant').hide();
				}
			}, ".del-img-variant"); //pass the element as an argument to .on

			$('.section-catalog').on('click', '.showAllVariants', function () {
				$(this).parents('tr').find('.second-block-varians').show();
				$(this).parents('tr').find('.moreVariantsCount').removeClass('moreVariantsCount');
				$(this).hide();
			});

			$('.section-catalog').on('click', '.btn-selected-typeGroupVar', function () {
				var obj = $(this);
				var objOffset = obj.offset();
				$('.select-typeGroupVar option[value="color"]').prop('selected', true)
				$('.select-typeGroupVar').show().offset({
					top: parseInt(objOffset.top - 14),
					left: parseInt(objOffset.left + 17 + obj.outerWidth())
				});
			});

			$('.section-catalog').on('click', '.cancel-typeGroupVar', function () {
				$('.select-typeGroupVar').hide();
			});

			$('.section-catalog').on('click', '.add-short-desc', function () {
				$('.shortDesc').css('display', 'inline-block');
				$('.add-short-desc').hide();
			});

			$('.section-catalog').on('click', '.apply-typeGroupVar', function () {
				catalog.saveVarTable = $('.variant-table .variant-row, .variant-table .text-left').clone();
				catalog.saveTypeGroupVar = $('.select-typeGroupVar select').val();
				catalog.buildGroupVarTable();
				$('.select-typeGroupVar').hide();
			});

			// открытие выгрузок
			$('.section-catalog').on('click', '.catalog_uploads_container_wrapper .additional_catalog_uploads_container div', function () {
				if ($(this).attr('part') == 'Csv') {
					//catalog.exportToCsv();
					return false;
				} else {
					admin.SECTION = 'integrations';
					cookie('setting-active-tab', '#' + 'integration-settings');
					cookie("integrationPart", $(this).attr('part'));
					admin.show("integrations.php", "adminpage", cookie(admin.SECTION + "_getparam"));
				}
			});

			// открытие выгрузок
			$('.section-catalog').on('click', '.catalog_downloads_container_wrapper .additional_catalog_downloads_container div', function () {
				if ($(this).attr('part') == 'Csv') {
					$('.filter-container').slideUp();
					$('.import-container-yml').slideUp();
					$('.import-container').slideToggle(function () {
						$('.widget-table-action').toggleClass('no-radius');
					});
					return false;
				}
				if ($(this).attr('part') == 'YandexMarket') {
					$('.filter-container').slideUp();
					$('.import-container').slideUp();
					$('.import-container-yml').slideToggle(function () {
						$('.widget-table-action').toggleClass('no-radius');

            // Проверяет, есть ли недоимпортированные изображенияс прошлого раза
            // Если есть выводит об этом сообщение и делает доступной кнопку импорта изображений
							admin.ajaxRequest({
								mguniqueurl: 'action/getImagesCountYML', // действия для выполнения на сервере
						},
						function(response) {
								const imagesCount = parseInt(response.data.imagesCount);
							if (imagesCount) {
								$('.section-mg-yml-import-core .block-console textarea').text('');
				
										mgYmlImportCore.printLog('Найдены неимпортированные изображения для ' + imagesCount + ' товаров.\nИмпортировать их можно по нажатию на кнопку "Импорт изображений"');
										$('.section-mg-yml-import-core .base-import-images').removeAttr('disabled');
								}
						});
						});
						return false;
					/*admin.SECTION = 'integrations';
					cookie('setting-active-tab', '#' + 'tab-Integration');
					cookie("integrationPart", $(this).attr('part'));
					admin.show("integrations.php", "adminpage", cookie(admin.SECTION + "_getparam"));*/
				}

				if ($(this).attr('part') == '1cMoySklad') { 
			
				

					cookie('setting-active-tab', '#1C-settings');
					includeJS(admin.SITE + '/mg-core/script/admin/settings.js');
					callback = settings.init;
					admin.show("settings.php", "adminpage", cookie("settings_getparam"), callback);

					/*admin.SECTION = 'integrations';
					cookie('setting-active-tab', '#' + 'tab-Integration');
					cookie("integrationPart", $(this).attr('part'));
					admin.show("integrations.php", "adminpage", cookie(admin.SECTION + "_getparam"));*/
				}
				
			});

			// смена языка товара
			$('.section-catalog').on('change', '.select-lang', function () {
				if ($('#add-product-wrapper .save-button').attr('id') == '') return false;
				$('#add-product-wrapper .related-block').html('');
				catalog.editProduct($('#add-product-wrapper .save-button').attr('id'));
			});

			// смена единиц измерения товара - открытие окна
			$('.section-catalog').on('click', '#add-product-wrapper .btn-selected-unit', function () {
				var obj = $(this);
				var objOffset = obj.offset();
				$('#add-product-wrapper .input-unit-block').show().offset({
						top: parseInt(objOffset.top + 25),
						left: parseInt(objOffset.left - $('#add-product-wrapper .input-unit-block').outerWidth() / 2 + 8)
					}
				);
			});

			// смена единиц измерения товара - сохранение
			$('.section-catalog').on('click', '#add-product-wrapper .input-unit-block .apply-unit', function () {
				$('#add-product-wrapper .input-unit-block').hide();
				var unit = $('#add-product-wrapper .input-unit-block .unit-input').val();
				$('#add-product-wrapper .btn-selected-unit').attr('realunit', unit);
				if (unit == '') {
					unit = catalog.realCatUnit;
				}
				if (!unit) {
					unit = lang.EMPTY_UNIT;
				}
				$('#add-product-wrapper .btn-selected-unit').text(unit);
			});

			// смена единиц измерения товара - отмена
			$('.section-catalog').on('click', '#add-product-wrapper .input-unit-block .cancel-unit', function () {
				$('#add-product-wrapper .input-unit-block').hide();
				var unit = $('#add-product-wrapper .btn-selected-unit').attr('realunit');
				$('#add-product-wrapper .input-unit-block .unit-input').val(unit).text(unit);
			});

			// смена единиц измерения веса товара - открытие окна
			$('.section-catalog').on('click', '#add-product-wrapper .btn-select-weightUnit', function () {
				var obj = $(this);
				var objOffset = obj.offset();
				$('#add-product-wrapper .weightUnit-block').show().offset({
						top: parseInt(objOffset.top + 25),
						left: parseInt(objOffset.left - $('#add-product-wrapper .weightUnit-block').outerWidth() / 2 + 8)
					}
				);
			});

			// смена единиц измерения веса товара - сохранение
			$('.section-catalog').on('click', '#add-product-wrapper .weightUnit-block .apply-weightUnit', function () {
				$('#add-product-wrapper .weightUnit-block').hide();
				var unit = $('#add-product-wrapper .weightUnit-block [name=weightUnit]').val();
				$('.weightUnit-block').data('product', unit).data('last', unit);
				var unitText = $('#add-product-wrapper .weightUnit-block [name=weightUnit] option:selected').data('short');
				$('#add-product-wrapper .btn-select-weightUnit').text(unitText);
			});

			// смена единиц измерения веса товара - отмена
			$('.section-catalog').on('click', '#add-product-wrapper .weightUnit-block .cancel-weightUnit', function () {
				$('#add-product-wrapper .weightUnit-block').hide();
				var unit = $('.weightUnit-block').data('last');
				$('#add-product-wrapper .weightUnit-block [name=weightUnit]').val(unit);
			});

			$('.section-catalog').on('change', '[name=importScheme]', function () {
				if ($('[name=importScheme]').val() != 'none') {
					$('.start-import').prop('disabled', false);
				} else {
					$('.start-import').prop('disabled', true);
				}
			});

			// Вызов модального окна при нажатии на кнопку добавления товаров.
			$('.section-catalog').on('click', '.add-new-button', function () {
				$('.landingLink').attr('href', 'javascript:void(0);').text('');

				catalog.openModalWindow('add');
			});

			// выбор файла для характеристики типа "Файл"
			$('.section-catalog').on('click', '.property-file-select', function() {
				const propId = $(this).data('id');
				if (!propId) {
					admin.indication('error', 'Неизвестная ошибка');
					return false;
				}
				admin.openUploader(function(file) {userProperty.addFileToProp(file, propId);}, null, 'uploads');
			});

			$('.section-catalog').on('click', '.property-file-remove', function() {
				const propId = $(this).data('id');
				if (!propId) {
					admin.indication('error', 'Неизвестная ошибка');
					return false;
				}
				userProperty.removeFileFromProp(propId);
			});
			
			// НАЧАЛО ЭКСПОРТ CSV
			// Элементы модального окна
			const categoriesBlock = $('#exportCsvCategoriesBlock');
			const exportCsvCheckbox = $('#exportCsvFromAllCats');
			const exportCsvSelectAllButton = $('#exportCsvCategoriesAll');
			const exportCsvSelectNoButton = $('#exportCsvCategoriesNo');
			const exportCsvSelect = $('#exportCsvCategoriesSelect');
			const exportCsvSelectOptions = $('#exportCsvCategoriesSelect option');
			const exportCsvCloseModal = $('#exportCsvCloseModal');
			const exportCsvModalButton = $('#csv-export-modal-button');

			// Вызов модального окна при нажатии на кнопку выгрузки в CSV.
			exportCsvModalButton.click(function () {
				admin.openModal($('#csv-export-modal'));
			})
			// При нажатии на кнопку выгрузки (Выгрузка в CSV по умолчанию)
			$('.section-catalog').on('click', '.js-downl-catalog', function () {
				admin.openModal($('#csv-export-modal'));
			});

			// Обработка чекбокса "Выгрузить из всех категорий"
			// Скрывает выбор категорий, если checked и наоборот
			exportCsvCheckbox.click(function () {
				if (exportCsvCheckbox.is(':checked')) {
					categoriesBlock.hide();
					return;
				}
				categoriesBlock.show();
			})

			// Обработка опций "Выбрать все" и "Отменить все" в выборе категорий
			exportCsvSelectAllButton.click(function () {
				exportCsvSelectOptions.prop("selected", true);
				exportCsvSelect.parent().find("li.opt").addClass('selected');
				exportCsvSelect.parent().find(".CaptionCont.SelectBox span").removeClass('placeholder').text('(' + exportCsvSelectOptions.length + ') Выбраны все');
			})
			exportCsvSelectNoButton.click(function () {
				exportCsvSelectOptions.prop("selected", false);
				exportCsvSelect.parent().find("li.opt").removeClass('selected');;
				exportCsvSelect.parent().find(".CaptionCont.SelectBox span").addClass('placeholder').text('Не выбрано');
			})

			// Нажатие на кнопку "Выгрузить"
			$('#exportCsvButton').click(function () {
				const exportCsvEncoding = $('#exportCsvEncoding').val();
				const fromAllCats = exportCsvCheckbox.is(':checked');
				const catIds = exportCsvSelect.val();
				if (!fromAllCats && !catIds.length) {
					admin.indication('error', lang.IN_CSV_ERROR);
					return;
				}
				// Собираем данные
				const data = {
					fromAllCats: exportCsvCheckbox.is(':checked'),
					catIds: exportCsvSelect.val(),
					encoding: exportCsvEncoding,
				}
				// Закрываем модалку
				exportCsvCloseModal.click();
				catalog.exportToCsv(data);
			})
			// КОНЕЦ ЭКСПОРТ CSV

			/*Инициализирует CKEditior*/
			$('.section-catalog').on('click', '#add-product-wrapper .html-content-edit', function () {
				if (catalog.initSupportCkeditor) {
					$('textarea[name=html_content]').ckeditor(function () {
						this.setData(catalog.supportCkeditor);
					});
				}
				catalog.initSupportCkeditor = false;
				setTimeout(function () {
					CKEDITOR.instances['html_content'].config.filebrowserUploadUrl = admin.SITE + '/ajax?mguniqueurl=action/upload_tmp';
				}, 1500);
			});

			// Показывает панель с фильтрами.
			$('.section-catalog').on('click', '.show-filters', function () {
				$('.import-container').slideUp();
				$('.import-container-yml').slideUp();
				$('.filter-container').slideToggle(function () {
					$('.widget-table-action').toggleClass('no-radius');
				});
			});

			$('.section-catalog').on('click', '#add-product-wrapper .set-size-map', function () {
				$('.size-map').slideToggle();
				$('.size-overflow').scrollTop(0);
				$('.size-overflow').scrollLeft(0);
				if ($('.size-map .leftCol tr').length > 1) {
					for (var i = 0; i < $('.size-map tr:eq(0) td').length; i++) {
						var w = $('.size-map tr:eq(0) td:eq(' + i + ')').outerWidth();
						$('.topRow td:eq(' + i + ')').outerWidth(w).css("min-width", w + "px");
					}
				} else {
					$('.topRow').remove();
				}
			});

			// Выделить все страницы
			$('.section-catalog').on('click', '.check-all-page', function () {
				$('.product-tbody input[name=product-check]').prop('checked', 'checked');
				$('.product-tbody input[name=product-check]').val('true');
				$('.product-tbody tr').addClass('selected');

				$(this).addClass('uncheck-all-page');
				$(this).removeClass('check-all-page');
			});

			// Снять выделение со всех  страниц.
			$('.section-catalog').on('click', '.uncheck-all-page', function () {
				$('.product-tbody input[name=product-check]').prop('checked', false);
				$('.product-tbody input[name=product-check]').val('false');
				$('.product-tbody tr').removeClass('selected');

				$(this).addClass('check-all-page');
				$(this).removeClass('uncheck-all-page');
			});

			// Применение выбранных фильтров
			$('.section-catalog').on('click', '.filter-now', function () {
				catalog.getProductByFilter();
				return false;
			});

			// показывает все фильтры в заданной характеристике
			$('.section-catalog').on('click', '.mg-filter-item .mg-viewfilter', function () {
				$(this).parents('ul').find('li').fadeIn();
				$(this).hide();
			});

			// показывает все группы фильтров
			$('.section-catalog').on('click', '.mg-viewfilter-all', function () {
				$(this).hide();
				$('.js-filter-item-toggle').fadeIn();
			});

			// Чистит все инпуты диапазонов, чтобы корректно отфильтровать товары
			$('.section-catalog').on('click', '.js-clear-diapazone-filter', function () {
				$('.mg-filter__options input[type=number]').val('');
			});
	
			// Вызов модального окна при нажатии на кнопку изменения товаров.
			$('.section-catalog').on('click', '.clone-row', function () {
				catalog.cloneProd($(this).attr('id'), $(this).parents('.product-row'));
			});

			// показывает настроки импорта csv
			$('.section-catalog').on('click', '.import-csv', function () {
				$('.filter-container').slideUp();
				$('.import-container').slideToggle(function () {
					$('.widget-table-action').toggleClass('no-radius');
				});
			});

			// Обработчик для загрузки файла импорта из CSV
			$('.section-catalog').on('change', 'input[name="upload"]', function () {
				catalog.uploadCsvToImport();
			});

			// Обработчик для смены категории
			$('.section-catalog').on('change', '.filter-container select[name="cat_id"]', function () {
				var cat_id = $('.section-catalog .filter-container select[name="cat_id"]').val();
				if (cat_id == "null") {
					cat_id = 0;
				}
				admin.show("catalog.php", cookie("type"), "page=0&cat_id=" + cat_id + '&displayFilter=1', catalog.init);
			});

			// Обработчик для переключения вывода товаров подкатегорий
			$('.section-catalog').on('change', '.filter-container input[name="insideCat"]', function () {
				var cat_id = $('.section-catalog .filter-container select[name="cat_id"]').val();
				if (cat_id == "null") {
					cat_id = 0;
				}
				var request = $("form[name=filter]").formSerialize();
				var insideCat = $(this).prop('checked');
				admin.show("catalog.php", cookie("type"), request + "&page=0&insideCat=" + insideCat + "&cat_id=" + cat_id + '&displayFilter=1', catalog.init);
			});

			// Обработчик для загрузки файла импорта из CSV
			$('.section-catalog').on('click', '.repeat-upload-csv', function () {
				$('.import-container input[name="upload"]').val('');
				$('.repeat-upload-file').hide();
				$('.upload-btn').show();
				$('.cancel-importing').hide();
				$('select[name=importScheme]').attr('disabled', 'disabled');
				$('select[name=identifyType]').attr('disabled', 'disabled');
				$('input[name=no-merge]').removeAttr("checked").val(false).attr('disabled', 'disabled');
				$('.message-importing').text('');
				catalog.STOP_IMPORT = false;
			});

			$('.section-catalog').on('click', '.columnComplianceModal .closeModal', function () {
				$('.section-catalog input[name="upload"]').val('');
				$('.repeat-upload-file').hide();
				$('.block-upload-сsv, .upload-btn').show();
			});

			// Обработчик для загрузки изображения на сервер, сразу после выбора.
			$('.section-catalog').on('click', '.start-import', function () {
				//Проверка на обязательные поля для файла
				let requiredFieldNotValues = [];
				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
					let required_field = $(this).find('.required_field').length;
					if(required_field != 0){
						let value = $(this.cells[1]).find('select').val();
						if(value == 'none'){
							requiredFieldNotValues.push($(this.cells[0]).find('b').html());
						}
					}

				});
				if(requiredFieldNotValues.length != 0){
					let alertText = 'Заполните обязательные поля: ';
					requiredFieldNotValues.forEach(function(currentValue){
						alertText += currentValue +' ';
					});
					alert(alertText);
					return false;
				}
				if (!confirm(lang.CATALOG_LOCALE_1)) {
					$('.section-catalog input[name="upload"]').val('');
					$('.repeat-upload-file').hide();
					$('.block-upload-сsv, .upload-btn').show();
					return false;
				} else {
					if (!catalog.startImport($('.block-importer .uploading-percent').text())) {
						admin.closeModal($('.columnComplianceModal'));
						$('.repat-upload-file').hide();
						$('.block-upload-сsv').hide();
						$('.cancel-import').show();
						$('.get-example-csv').hide();
					}
				}
			});

			// Останавливает процесс загрузки товаров.
			$('.section-catalog').on('click', '.cancel-import', function () {
				catalog.canselImport();
			});

			$('.section-catalog').on('click', '.backToCsv', function () {
				$('.block-upload-images').hide();
				$('.block-upload-сsv').show();
				$('.csv-import-title').show();
				$('.img-import-title').hide();
			});

			// снимает выделение со всех опций в характеристике
			$('.section-catalog').on('click', '#add-product-wrapper .clear-select-property', function () {
				$(this).parents('.price-settings').find('select option').prop('selected', false);
			});

			// для рекомендованных категорий
			// разворачивает список всех дополнительных категорий
			$('.section-catalog').on('click', '#add-product-wrapper .full-size-select-cat.closed-select-cat', function () {
				$('select[name=related_cat]').attr('size', $('select[name=related_cat] option').length);
				$(this).removeClass('closed-select-cat').addClass('opened-select-cat');
				$(this).children('span').text(lang.PROD_CLOSE_CAT);
			});
			// сворачивает список всех дополнительных категорий
			$('.section-catalog').on('click', '#add-product-wrapper .full-size-select-cat.opened-select-cat', function () {
				$('select[name=related_cat]').attr('size', 4);
				$(this).removeClass('opened-select-cat').addClass('closed-select-cat');
				$(this).children('span').text(lang.PROD_OPEN_CAT);
			});
			// снимает выделение со всех дополнительных категорий
			$('.section-catalog').on('click', '#add-product-wrapper .clear-select-cat-related', function () {
				$('select[name=related_cat] option').prop('selected', false);
			});

			// Вызов формы для выбора валют.
			$('.section-catalog').on('click', '#add-product-wrapper .btn-selected-currency', function () {
				var obj = $(this);
				var objOffset = obj.offset();
				$('#add-product-wrapper .select-currency-block').show().offset({
					top: parseInt(objOffset.top - 14),
					left: parseInt(objOffset.left + 17 + obj.outerWidth())
				});
			});

			// применение выбраной валюты
			$('.section-catalog').on('click', '#add-product-wrapper .apply-currency', function () {
				catalog.changeIso();
			});

			// применение выбраного склада
			$('.section-catalog').on('change', '#add-product-wrapper .storageToView', function () {
				// catalog.selectedStorage = $(this).val();
				catalog.saveProduct($('.save-button').attr('id'), false);
				catalog.changeStorage = true;
			});

			// Вызов модального окна при нажатии на кнопку изменения товаров.
			$('.section-catalog').on('click', '.edit-row', function () {
				let link = $(this).closest('tr').find('.js-external-link').attr('href') + '?lp';
				$('#lndAcc').show();
				$('.landingLink').attr('href', link).text(link);

				catalog.openModalWindow('edit', $(this).attr('id'));
			});

			// Удаление товара.
			$('.section-catalog').on('click', '.delete-order', function () {
				catalog.deleteProduct(
					$(this).attr('id'),
					$('tr[id=' + $(this).attr('id') + '] .uploads').attr('src'),
					false,
					$(this)
				);
			});

			// Нажатие на кнопку - рекомендуемый товар
			$('.section-catalog').on('click', '.recommend', function () {
				$(this).find('a').toggleClass('active');
				var id = $(this).data('id');

				if ($(this).find('a').hasClass('active')) {
					catalog.recomendProduct(id, 1);
					$(this).find('a').attr('tooltip', lang.PRINT_IN_RECOMEND);
				} else {
					catalog.recomendProduct(id, 0);
					$(this).find('a').attr('tooltip', lang.PRINT_NOT_IN_RECOMEND);
				}
				$('#tiptip_holder').hide();
				admin.initToolTip();
			});

			// Нажатие на кнопку - активный товар
			$('.section-catalog').on('click', '.visible', function () {
				$(this).find('a').toggleClass('active');
				var id = $(this).data('id');

				if ($(this).find('a').hasClass('active')) {
					catalog.visibleProduct(id, 1);
					$(this).find('a').attr('tooltip', lang.ACT_V_PROD);
				} else {
					catalog.visibleProduct(id, 0);
					$(this).find('a').attr('tooltip', lang.ACT_UNV_PROD);
				}
				$('#tiptip_holder').hide();
				admin.initToolTip();
			});

			// Нажатие на кнопку - новый товар
			$('.section-catalog').on('click', '.new', function () {
				$(this).find('a').toggleClass('active');
				var id = $(this).data('id');

				if ($(this).find('a').hasClass('active')) {
					catalog.newProduct(id, 1);
					$(this).find('a').attr('tooltip', lang.PRINT_IN_NEW);
				} else {
					catalog.newProduct(id, 0);
					$(this).find('a').attr('tooltip', lang.PRINT_NOT_IN_NEW);
				}
				$('#tiptip_holder').hide();
				admin.initToolTip();
			});

			// Выделить все товары.
			$('.section-catalog').on('click', '.checkbox-cell input[name=product-check]', function () {
				if ($(this).val() != 'true') {
					$('.product-tbody input[name=product-check]').prop('checked', 'checked');
					$('.product-tbody input[name=product-check]').val('true');
				} else {
					$('.product-tbody input[name=product-check]').prop('checked', false);
					$('.product-tbody input[name=product-check]').val('false');
				}
			});

			// Сброс фильтров.
			$('.section-catalog').on('click', '.refreshFilter', function () {
				admin.clearGetParam();
				admin.show("catalog.php", "adminpage", "refreshFilter=1", catalog.init);
				return false;
			});

			// Обработка выбранной категории (перестраивает пользовательские характеристики).
			$('.section-catalog').on('change', '#productCategorySelect', function () {
				//достаем id редактируемого продукта из кнопки "Сохранить"
				var product_id = $(this).parents('#add-product-wrapper').find('.save-button').attr('id');
				var category_id = $(this).val();

				if (category_id == 0) {
					$('.addedProperty').html('<span class="addedProperty__empty">Для указания характеристик выберите категорию</span>');
				} else {
					$('.addedProperty').html('');
				}

				catalog.generateUserProreprty(product_id, category_id);
				$('.size-map').hide();

				admin.ajaxRequest({
						mguniqueurl: "action/getWeightFromCat",
						catId: category_id,
					},
					function (response) {
						var unit = response.data;
						$('.weightUnit-block').data('category', unit).data('product', unit).data('last', unit);
						$('#add-product-wrapper .weightUnit-block [name=weightUnit]').val(unit);
						$('#add-product-wrapper .btn-select-weightUnit').text($('#add-product-wrapper .weightUnit-block [name=weightUnit] option[value='+unit+']').data('short'));
					});
			});

			// Обработчик для загрузки изображения на сервер, сразу после выбора.
			$('.section-catalog').on('change', '.add-img-block input[name="photoimg"]', function () {
				var currentImg = '';
				var img_container = $(this).parents('.parent');

				if (!img_container.attr('class')) {
					img_container = $(this).parents('.variant-row');
				}

				if (img_container.find('img').length > 0) {
					currentImg = img_container.find('img').attr('alt');
				} else {
					currentImg = img_container.find('img').attr('filename');
				}

				//Пишем в поле deleteImage имена изображений, которые необходимо будет удалить при сохранении
				if (catalog.deleteImage) {
					catalog.deleteImage += '|' + currentImg;
				} else {
					catalog.deleteImage = currentImg;
				}
				if ($(this).val()) {
					catalog.addImageToProduct(img_container);
				}
			});

			//открытие файлового менеджера
			$('.section-catalog').on('click', '#add-product-wrapper .additional_uploads_container .js-upload-from-file', function () {
				admin.openUploader('catalog.uploaderCallback');
			});

			//открытие файлового менеджера (варианты)
			$('.section-catalog').on('click', '#add-product-wrapper .catalog_uploads_container_variants_wrapper .js-upload-from-file', function () {
				catalog.lastVariant = $(this);
				admin.openUploader('catalog.uploaderCallbackVariant');
			});

			//открытие всплывалки для ввода ссылки
			$('.section-catalog').on('click', '#add-product-wrapper .additional_uploads_container .js-open-url-popup', function () {
				$('#add-product-wrapper .main-image .url-popup').show();
			});

			//открытие окна для выбора с компа (варианты)
			$('.section-catalog').on('click', '#add-product-wrapper .catalog_uploads_container_variants_wrapper .from_pc', function () {
				$(this).closest('form').find('input[type=file]').click();
			});

			//открытие всплывалки для ввода ссылки (варианты)
			$('.section-catalog').on('click', '#add-product-wrapper .catalog_uploads_container_variants_wrapper .js-open-url-popup', function () {
				$('#add-product-wrapper .catalog_uploads_container_variants_wrapper .url-popup').hide();
				$('#add-product-wrapper .catalog_uploads_container_variants_wrapper .existing-popup').html('').hide();
				$(this).parents('form').find('.url-popup').show();
			});

			//закрытие всплывалки для ввода ссылки
			$('.section-catalog').on('click', '#add-product-wrapper .cancel-url', function () {
				setTimeout(function () {
					$('.url-popup').hide();
				}, 1);
			});

			//применение всплывалки для ввода ссылки
			$('.section-catalog').on('click', '#add-product-wrapper .url-popup .apply-url', function () {
				var imgUrl = $('#add-product-wrapper .url-popup input').val();

				admin.ajaxRequest({
						mguniqueurl: "action/addImageUrl",
						imgUrl: imgUrl,
						isCatalog: 'true'
					},

					function (response) {
						admin.indication(response.status, response.msg);
						var mainurl = $('.main-image').find('img').attr('src').substr(-12).toLowerCase();

						if (response.status == 'success') {
							if (mainurl.indexOf('no-img.') >= 0) {
								var src = admin.SITE + '/uploads/' + response.data;
								$('.main-image').find('img').attr('src', src);
								// if ($('#add-product-wrapper input[name="title"]').val().length) {
								//   $('.images-block img:last').attr('alt', $('#add-product-wrapper input[name="title"]').val());
								// }
								// else{
								$('.main-image').find('img').attr('alt', response.data);
								// }
							}
							if (mainurl.indexOf('no-img.') < 0) {
								var src = admin.SITE + '/uploads/' + response.data;
								var ttle = response.data.replace('prodtmpimg/', '');
								var row = catalog.drawControlImage(src, true, '', '', '');
								$('.sub-images').append(row);
								// if ($('#add-product-wrapper input[name="title"]').val().length) {
								//   $('.images-block img:last').attr('alt', $('#add-product-wrapper input[name="title"]').val());
								// }
								// else{
								$('.images-block img:last').attr('alt', response.data);
								// }
							}
							$('#add-product-wrapper .main-image .url-popup input').val('');
							catalog.subImgPopup('open');
						}
					});

				$('#add-product-wrapper .main-image .url-popup').hide();
			});

			//применение всплывалки для ввода ссылки (варианты)
			$('.section-catalog').on('click', '#add-product-wrapper .catalog_uploads_container_variants_wrapper .url-popup .apply-url', function () {

				var imgUrl = $(this).parents('form').find('.url-popup').find('input').val();
				var obje = $(this);

				admin.ajaxRequest({
						mguniqueurl: "action/addImageUrl",
						imgUrl: imgUrl,
						isCatalog: 'true'
					},

					function (response) {
						admin.indication(response.status, response.msg);

						if (response.status == 'success') {
							var src = admin.SITE + '/uploads/' + response.data;
							obje.parents('ul').find('.img-this-variant').find('img').attr('src', src).attr('alt', src).data('filename', src);
							$('#add-product-wrapper .catalog_uploads_container_variants_wrapper .url-popup').hide();
							obje.parents('tr').find('.img-button').hide();
							obje.parents('tr').find('.del-img-variant').show();
							catalog.updateImageVar();
						}
					});

				$('#add-product-wrapper .main-image .url-popup').hide();
			});

			//открытие всплывалки для выбора из старых картинок (варианты)
			$('.section-catalog').on('click', '#add-product-wrapper .catalog_uploads_container_variants_wrapper .from_existing', function () {
				var srcs = [];
				var src = '';
				var html = '';
				var foundImg = false;
				$('#add-product-wrapper .catalog_uploads_container_variants_wrapper .url-popup').hide();
				$('#add-product-wrapper .catalog_uploads_container_variants_wrapper .existing-popup').html('').hide();
				$('#add-product-wrapper .add-img-block img').each(function (index, element) {

					src = $(this).attr('src');
					if (src.indexOf('no-img') >= 0) {
						src = '';
					}

					if (src != undefined && src != null && src != '') {
						html += '<div class="img-holder-variant"><img src="' + src + '"></div>';
						foundImg = true;
					}
				});
				html += '<div class="row">';
				if (!foundImg) {
					html += '<div class="large-12 columns">' + lang.PRODUCT_EXISTING_IMG_EMPTY + '</div>';
				}
				html += '<button class="link fl-left cancel-existing"><span>' + lang.CANCEL + '</span></button></div>';

				$(this).parents('form').find('.existing-popup').html(html).show();
				catalog.updateImageVar();
			});

			//применение всплывалки для выбора из старых картинок (варианты)
			$('.section-catalog').on('click', '#add-product-wrapper .catalog_uploads_container_variants_wrapper .existing-popup .img-holder-variant', function () {
				var src = $(this).find('img').attr('src');
				$(this).parents('ul').find('.img-this-variant').find('img').attr('src', src).attr('alt', src).data('filename', src);
				$('#add-product-wrapper .catalog_uploads_container_variants_wrapper .existing-popup').hide();
				$(this).parents('tr').find('.img-button').hide();
				$(this).parents('tr').find('.del-img-variant').show();
				catalog.updateImageVar();
			});

			//закрытие всплывалки для выбора из старых картинок (варианты)
			$('.section-catalog').on('click', '#add-product-wrapper .catalog_uploads_container_variants_wrapper .existing-popup .cancel-existing', function () {
				// timeout для того, чтобы hide сработал после другого обработчика, который формирует html этого окна
				setTimeout(()=>{$('#add-product-wrapper .catalog_uploads_container_variants_wrapper .existing-popup').hide()},0);
			});

			//появление дропзоны
			$('.section-catalog').on('drag dragstart dragover dragenter', function (e) {
				$('.mg-admin-html #add-product-wrapper .main_img_input').addClass('dragover');
				$('.mg-admin-html #add-product-wrapper .img-dropzone').addClass('img-dropzone--visible');
				catalog.dragActive = true;
			});

			//исчезновение дропзоны
			$('.section-catalog').on('dragend drop mouseleave mouseout', function (e) {
				$('.mg-admin-html #add-product-wrapper .main_img_input').delay(1000).removeClass('dragover');
				catalog.dragActive = false;
				$('.mg-admin-html #add-product-wrapper .img-dropzone').delay(1000).removeClass('img-dropzone--visible');
			});

			//появление дропзоны
			$('.section-catalog').on('mouseover', '#add-product-wrapper .main-image .img-holder', function () {
				if (catalog.dragActive == false) {
					$('.mg-admin-html #add-product-wrapper .img-dropzone').addClass('img-dropzone--visible');
				}
			});

			//исчезновение дропзоны
			$('.section-catalog').on('mouseout mouseleave', '#add-product-wrapper .main-image .img-holder', function () {
				catalog.dragActive = false;
				$('.mg-admin-html #add-product-wrapper .img-dropzone').removeClass('img-dropzone--visible');
				$('.mg-admin-html #add-product-wrapper .main_img_input').delay(1000).removeClass('dragover');
			});

			// Обработчик для загрузки изображений на сервер, сразу после выбора.
			$('.section-catalog').on('change', '.add-img-block .main_img_input', function () {
				var mainurl = $('.main-image').find('img').attr('src').substr(-12).toLowerCase();
				var filesAmount = this.files.length;

				if (mainurl.indexOf('no-img.') >= 0 && filesAmount == 1) {
					var act = 'replace';
				}
				if (mainurl.indexOf('no-img.') < 0) {
					var act = 'add';
				}
				if (mainurl.indexOf('no-img.') >= 0 && filesAmount > 1) {
					var act = 'replace and add';
				}

				loader = $('.mailLoader');
				$(this).parents('.imageform').ajaxSubmit({
					type: "POST",
					url: "ajax",
					data: {
						mguniqueurl: "action/addImageMultiple"
					},
					cache: false,
					dataType: 'json',
					beforeSubmit: function () {
						admin.WAIT_PROCESS = true;
						loader.hide();
						loader.before('<div class="view-action" style="display:none; margin-top:-2px;">' + lang.LOADING + '</div>');
						setTimeout(function () {
							if (admin.WAIT_PROCESS) {
								admin.waiting(true);
							}
						}, admin.WAIT_DELAY);
					},
					success: function (response) {
						admin.WAIT_PROCESS = false;
						admin.waiting(false);
						loader.show();
						$('.view-action').remove();

						admin.indication(response.status, response.msg);
						if (response.data.length) {
							var imgCount = response.data.length;

							if (act == 'replace' && imgCount > 0) {
								var src = admin.SITE + '/uploads/' + response.data[0];
								$('.main-image').find('img').attr('src', src);
								$('.main-image').find('img').attr('alt', response.data[0]);
							}

							if (act == 'add' && imgCount > 0) {
								for (var i = 0; i < imgCount; i++) {
									var src = admin.SITE + '/uploads/' + response.data[i];
									var ttle = response.data[i].replace('prodtmpimg/', '');
									var row = catalog.drawControlImage(src, true, '', '', '');
									$('.sub-images').append(row);
									$('.images-block img:last').attr('alt', response.data[i]);
								}
							}

							if (act == 'replace and add' && imgCount > 0) {
								var src = admin.SITE + '/uploads/' + response.data[0];
								$('.main-image').find('img').attr('src', src);
								$('.main-image').find('img').attr('alt', response.data[0]);

								for (var i = 1; i < imgCount; i++) {
									var src = admin.SITE + '/uploads/' + response.data[i];
									var ttle = response.data[i].replace('prodtmpimg/', '');
									var row = catalog.drawControlImage(src, true, '', '', '');
									$('.sub-images').append(row);
									$('.images-block img:last').attr('alt', response.data[i]);
								}
							}
							catalog.subImgPopup('open');
						}
					}
				});

			});

			// Добавляет ссылку на электронный товар
			$('.section-catalog').on('click', '.add-link-electro', function () {
				admin.openUploader('catalog.getFileElectro');
				$('#overlay:last').css('z-index', '100');
			});

			// Удаляет ссылку на электронный товар
			$('.section-catalog').on('click', '.del-link-electro', function () {
				$('.section-catalog input[name="link_electro"]').val('');
				$('.del-link-electro').hide();
				$('.add-link-electro').show();
			});

			// Удаление изображения товара, как из БД таи физически с сервера.
			$('.section-catalog').on('click', '.js-remove-prod-img', function () {
				var img_container = $(this).parents('.parent');
				catalog.delImageProduct($(this).attr('id'), img_container);
			});

			// Сохранение продукта при нажатии на кнопку сохранить в модальном окне.
			$('.section-catalog').on('click', '#add-product-wrapper .save-button', function () {
				catalog.saveProduct($(this).attr('id'), !admin.keySave);
			});

			// Нажатие ентера при вводе в строку поиска товара
			$('.section-catalog').on('keypress', '.widget-panel input[name=search]', function (e) {
				if (e.keyCode == 13) {
					catalog.getSearch($(this).val());
					$(this).blur();
				}
			});

			// Нажатие лупы при вводе в строку поиска товара
			$('.section-catalog').on('click', '.js-do-search', function () {
				let seachInput = $('.widget-panel input[name=search]');
				catalog.getSearch(seachInput.val());
				seachInput.blur();
			});

			// Нажатие пагинации при поиске товара
			$('.section-catalog').on('click', '.mg-pager .linkPageCatalog', function () {
				var pageId = admin.getIdByPrefixClass($(this), 'page');
				catalog.getSearch($('.widget-panel input[name=search]').val(), pageId, 'nope');
			});

			// Добавить вариант товара
			$('.section-catalog').on('click', '.variant-table-wrapper .add-position', function () {
				catalog.addVariant($('.variant-table'));

				$('.variant-table th:eq(0)').show();
				$('.variant-table tr').each(function () {
					$(this).find('td:eq(0)').show();
				});

				if (($('.storageToView').html() != undefined) && ($('.storageToView').val() == 'all')) {
					$('.variant-table [name=count]').prop('disabled', true);
				} else {
					$('.variant-table [name=count]').prop('disabled', false);
				}
			});

			// Удалить вариант товара
			$('.section-catalog').on('click', '#add-product-wrapper .del-variant', function () {
				if (!userProperty.sizeMapCreatedProcess) {
					if (!confirm(lang.CATALOG_LOCALE_4)) return false;
				}

				if ($('.variant-table tr').length == 2) {
					$('.variant-table .hide-content').hide();
					$('.variant-table').data('have-variant', '0');
				} else {
					$(this).parents('tr').remove();
				}

				var imgFile = $(this).parents('tr').find('.img-this-variant img').attr('src');

				if (catalog.deleteImage) {
					catalog.deleteImage += '|' + imgFile;
				} else {
					catalog.deleteImage = imgFile;
				}

				return false;
			});

			// при ховере на иконку картинки варианта  показывать  имеющееся изображение
			$('.section-catalog').on('mouseover mouseout', '.product-table-wrapper .img-variant, .product-table-wrapper .del-img-variant', function (event) {
				if (event.type == 'mouseover') {
					$(this).parents('td').find('.img-this-variant').show();
				} else {
					$(this).parents('td').find('.img-this-variant').hide();
				}
			});

			// При получении фокуса в поля для изменения значений, запоминаем каким было исходное значение
			$('.section-catalog').on('focus', '.fastsave', function () {
				catalog.memoryVal = $(this).val();
			});

			// сохранение параметров товара прямо из общей таблицы товаров при потере фокуса
			$('.section-catalog').on('blur', '.fastsave', function () {
				//если введенное отличается от  исходного, то сохраняем.
				if (catalog.memoryVal != $(this).val()) {
					catalog.fastSave($(this).data('packet'), $(this).val(), $(this));
				}
				catalog.memoryVal = null;
			});

			// сохранение параметров товара прямо из общей таблицы товаров при нажатии ентера
			$('.section-catalog').on('keypress', '.fastsave', function (e) {
				if (e.keyCode == 13) {
					$(this).blur();
				}
			});

			// показывает сроку поиска для связанных товаров
			$('.section-catalog').on('click', '#add-product-wrapper .add-related-product', function () {
				$('.select-product-block').show();
			});

			// Удаляет связанный товар из списка связанных
			$('.section-catalog').on('click', '#add-product-wrapper .add-related-product-block .remove-added-product', function () {
				$(this).parents('.product-unit').remove();
				catalog.widthRelatedUpdate();
				catalog.msgRelated();
			});
			// Удаляет связанную категорию товар из списка связанных
			$('.section-catalog').on('click', '#add-product-wrapper .add-related-product-block .remove-added-category', function () {
				$(this).parents('.category-unit').remove();
				catalog.widthRelatedUpdate();
				catalog.msgRelated();
			});

			// Закрывает выпадающий блок выбора связанных товаров
			$('.section-catalog').on('click', '#add-product-wrapper .add-related-product-block .cancel-add-related', function () {
				$('.select-product-block').hide();
			});

			// Поиск товара при создании связанного товара.
			// Обработка ввода поисковой фразы в поле поиска.
			$('.section-catalog').on('keyup', '#add-product-wrapper .search-block input[name=searchcat]', function () {
				admin.searchProduct($(this).val(), '#add-product-wrapper .search-block .fastResult', null, 'yep');
			});

			$('.section-catalog').on('keyup', '.search-field-create-var', function () {
				var excludeProdIds = [];
				$('.product-tbody tr').each(function () {
					if ($(this).find('input[name=product-check]').prop('checked')) {
						excludeProdIds.push($(this).attr('id'));
					}
				});
				admin.searchProduct($(this).val(), '.search-field-create-var-zone', -1, 'nope', false, excludeProdIds);
			});
			$('.section-catalog').on('click', '.search-field-create-var-zone a', function () {
				$('.addToIdVarCreate').val($(this).data('id'));
				$('.search-field-create-var').val($(this).find('.search-prod-name').text());
				$('.search-field-create-var-zone').html('');
			});

			// подбор случайного товара
			$('.section-catalog').on('click', '#add-product-wrapper .random-add-related', function () {
				admin.ajaxRequest({
						mguniqueurl: "action/getRandomProd"
					},
					function (response) {
						admin.indication(response.status, response.msg);
						if (response.status != 'error') {
							catalog.addrelatedProduct(0, response.data.product);
						}
					},
					false,
					false,
					true
				);
			});

			// Подстановка товара из примера в строку поиска связанного товара.
			$('.section-catalog').on('click', '#add-product-wrapper .search-block  .example-find', function () {
				$('.section-catalog .search-block input[name=searchcat]').val($(this).text());
				admin.searchProduct($(this).text(), '#add-product-wrapper .search-block .fastResult', null, 'yep');
			});

			// Клик по найденым товарам поиска в форме добавления связанного товара.
			$('.section-catalog').on('click', '#add-product-wrapper .fast-result-list a', function () {
				catalog.addrelatedProduct($(this).data('element-index'));
			});

			// Выполнение выбранной операции с товарами
			$('.section-catalog').on('click', '.run-operation', function () {
				if ($('.product-operation').val() == 'fulldelete') {
					admin.openModal('#catalog-remove-modal');
				} else {
					catalog.runOperation($('.product-operation').val());
				}
			});

			//Проверка для массового удаления
			$('.section-catalog').on('click', '#catalog-remove-modal .confirmDrop', function () {
				if ($('#catalog-remove-modal input').val() === $('#catalog-remove-modal input').attr('tpl')) {
					$('#catalog-remove-modal input').removeClass('error-input');
					admin.closeModal('#catalog-remove-modal');
					catalog.runOperation($('.product-operation').val(), true);
				} else {
					$('#catalog-remove-modal input').addClass('error-input');
				}
			});
			$('.section-catalog').on('change', 'select[name="operation"]', function () {
				if ($(this).val() == 'move_to_category') {
					$('select#moveToCategorySelect').show(1);
				} else {
					$('select#moveToCategorySelect').hide(1);
				}
				if ($(this).val() == 'add_to_category') {
					$('select#addToCategorySelect').parent().parent().show(1);
				} else {
					$('select#addToCategorySelect').parent().parent().hide(1);
				}
				if ($(this).val() == 'createvar') {
					$('.search-field-create-var-zone-gloabal').css('display', 'inline-block');
				} else {
					$('.search-field-create-var-zone-gloabal').hide(1);
				}
			});
			// Изменение типа каталога для импорта из CSV
			$('.section-catalog').on('change', ".block-upload-сsv select[name=importType]", function () {
				$('.block-upload-сsv .example-csv').hide();
				$('input[name=upload]').val('');

				if ($(this).val() != 0) {
					$('input[name=upload]').removeAttr('disabled');
					$('.block-upload-сsv .view-' + $(this).val()).show();
					$('select[name=importScheme]').attr('disabled', 'disabled');
					$('select[name=identifyType]').attr('disabled', 'disabled');
					$('.upload-csv-form').removeClass('disabled');
					$('input[name=no-merge]').attr('disabled', 'disabled');
					$('input[name=no-merge]').removeAttr("checked");
					$('input[name=no-merge]').val(false);
					$('.upload-btn').show();
					$('.repeat-upload-file').hide();
					$('.message-importing').text('');
				} else {
					$('input[name=upload]').attr('disabled', 'disabled');
					$('.upload-csv-form').addClass('disabled');
				}

				if ($(this).val() === 'MogutaCMSUpdate') {
					$('.identifyType').hide();
					$(".delete-all-products-btn").hide();
				} else {
					$('.identifyType').show();
					$(".delete-all-products-btn").show();
				}
			});

			// Обработчик для загрузки изображения на сервер, сразу после выбора.
			$('.section-catalog').on('change', '.catalog_uploads_container_variants_wrapper input[name="photoimg"]', function () {
				// отправка картинки на сервер
				var imgContainer = $(this).parents('td');

				$(this).parents('form').ajaxSubmit({
					type: "POST",
					url: "ajax",
					data: {
						mguniqueurl: "action/addImage"
					},
					cache: false,
					dataType: 'json',
					success: function (response) {
						admin.indication(response.status, response.msg);
						if (response.status != 'error') {
							var src = admin.SITE + '/uploads/' + response.data.img;
							imgContainer.find('img').attr('src', src).attr('filename', response.data.img);
							imgContainer.find('.del-img-variant').show();
							imgContainer.find('.img-button').hide();
							catalog.updateImageVar();
						} else {
							var src = admin.SITE + mgNoImageStub;
							imgContainer.find('img').attr('src', src).attr('filename', 'no-img.png');
							catalog.updateImageVar();
						}
					}
				});
			});

			// Устанавливает количество выводимых записей в этом разделе.
			$('.section-catalog').on('change', '.countPrintRowsProduct', function () {
				var count = $(this).val();
				admin.ajaxRequest({
						mguniqueurl: "action/setCountPrintRowsProduct",
						count: count
					},
					function (response) {
						admin.refreshPanel();
					}
				);
			});

			// Подобрать продукты по поиску
			$('.section-catalog').on('click', '.searchProd', function () {
				var keyword = $('input[name="search"]').val();
				catalog.getSearch(keyword);
			});

			//Закрыть раздел с дополнительными картинками
			$('.section-catalog').on('click', '#add-product-wrapper .close-images-wrap', function () {
				catalog.subImgPopup('close');
			});

			//Добавить изображение для продукта
			$('.section-catalog').on('click', '#add-product-wrapper .add-image', function () {
				var src = admin.SITE + mgNoImageStub;
				var row = catalog.drawControlImage(src, true, '', '', '');
				$('.sub-images').append(row).find('.sub-images__empty').remove();
				admin.initToolTip();
			});

			//Сделать основной картинку продукта
			$('.section-catalog').on('click', '.set-main-image', function () {
				var obj = $(this).parents('.parent');
				catalog.upMainImg(obj);
			});

			//Показать окно с настройками title и alt для картинки
			$('.section-catalog').on('click', '.js-open-img-seo', function () {
				var obj = $(this).parents('.image-item'),
					objIndex = obj.index() + 1,
					containerWidth = parseInt($('#add-product-wrapper .sub-images').innerWidth()),
					imgWidth = parseInt($('#add-product-wrapper .sub-images .image-item').outerWidth()) + 10,
					imgPerRow = Math.floor(containerWidth / imgWidth),
					imgNum = objIndex % imgPerRow;

				if (imgNum === 0 || imgNum == (imgPerRow - 1)) {
					$(this).parents('.parent').find('.seo-img-popup').addClass('right').show();
				} else {
					$(this).parents('.parent').find('.seo-img-popup').removeClass('right').show();
				}
				$('#add-product-wrapper .main-image .seo-img-popup').removeClass('right');
			});

			//Спрятать  окно с настройками title и alt для картинки
			$('.section-catalog').on('click', '#add-product-wrapper .apply-seo-image', function () {
				$(this).closest('.custom-popup').hide();
			});

			$('.section-catalog').on('click', '#add-product-wrapper .close-sub-images', function () {
				$(this).closest('.custom-popup').hide();
			});

			$('.section-catalog').on('click', '.yml-link-was-formed .edit-link', function () {
				$(this).parents('.product-table-wrapper').find('.link-name').show();
				$(this).parents('.product-table-wrapper').find('.link').hide();
			});

			// выводит путь родительских категорий при наведении мышкой
			$('.section-catalog').on('mouseover', 'tbody tr.product-row .cat_id', function () {
				if (!$(this).find('.parentCat').hasClass('categoryPath') && $(this).attr('id') != 0) {
					$(this).find('.parentCat').addClass('categoryPath');
					var cat_id = $(this).attr('id');
					var path = '';
					var parent = $('.section-catalog #add-product-wrapper select[name=cat_id] option[value=' + cat_id + ']').data('parent');
					if (parent) {
						while (parent != 0) {
							path = $('.section-catalog #add-product-wrapper select[name=cat_id] option[value=' + parent + ']').text() + '/' + path;
							parent = $('.section-catalog #add-product-wrapper select[name=cat_id] option[value=' + parent + ']').data('parent');
						}
						path = path.replace(/-/g, '');
						$(this).find('.parentCat').attr('tooltip', '/' + path);

						$('#tiptip_holder').hide();
						admin.initToolTip();
					}
				}
			});

			// открытие текстового редактора для ввода значения текстовой характеристики, замена вхождения <br> на перенос строки /n
			$('.section-catalog').on('click', '.property.custom-textarea', function () {
				var id = $(this).data('name');
				var html = $('.userField .custom-textarea[data-name=' + id + ']').parent().find('.value').text();
				// html = html.replace(/&lt;br\s*\/*&gt;/g, '\n');
				$('#textarea-property-value textarea[name=html_content-textarea]').val(admin.htmlspecialchars_decode(html));
				var offset = (window.pageYOffset);
				admin.openModal("#textarea-property-value");
				$('#textarea-property-value textarea[name=html_content-textarea]').ckeditor();
				setTimeout(function () {
					CKEDITOR.instances['html_content-textarea'].config.filebrowserUploadUrl = admin.SITE + '/ajax?mguniqueurl=action/upload_tmp';
				}, 1500);
				$('#textarea-property-value .save-button-value').data('id', id);
			});

			// если поле изменено и не сохранено - перед закрытием выводит сообщение
			$('.section-catalog').on('click', '#textarea-property-value .proper-modal_close', function () {
				// if ($(this).hasClass('edited')) {
				//   if (!confirm('Изменения не сохранены. Закрыть окно характеристики?')) {
				//     return false;
				//   }
				// }
				$(this).removeClass('edited');
				admin.closeModal("#textarea-property-value");
				$('#textarea-property-value textarea').val('');
				$('#textarea-property-value .save-button-value').data('id', '');
			});

			// добавление класса на кнопку закрытия при изменении
			$('.section-catalog').on('click', '#textarea-property-value .custom-textarea-value', function () {
				$('#textarea-property-value .proper-modal_close').addClass('edited');
			});

			// сохранение значения текстовой характеристики
			$('.section-catalog').on('click', '#textarea-property-value .save-button-value', function () {
				var id = $(this).data('id');
				var value = $('#textarea-property-value textarea').val();
				$('#add-product-wrapper .userField .custom-textarea[data-name=' + id + ']').parent().find('.value').text(admin.htmlspecialchars(value));
				admin.indication('success', lang.CATALOG_LOCALE_5);
				admin.closeModal("#textarea-property-value");
				$('#textarea-property-value textarea').val('');
				$('#textarea-property-value .save-button-value').data('id', '');
				$('#textarea-property-value .proper-modal_close').removeClass('edited');
			});

			// добавление "своего" артикула
			$('.section-catalog').on('keyup', '.variant-table .default-code', function () {
				$(this).removeClass('default-code');
			});

			/* Добавляет новую характеристику для товара */
			$('.section-catalog').on('click', '#add-product-wrapper .add-property', function () {
				$('#add-product-wrapper .new-added-properties').show();
			});

			/* Добавляет новую характеристику для товара */
			$('.section-catalog').on('click', '#add-product-wrapper .apply-new-prop', function () {
				var name = $(this).parents('.custom-popup').find('input[name=name]').val();
				var value = $(this).parents('.custom-popup').find('input[name=value]').val();
				if (name == '') {
					$(this).parents('.custom-popup').find('input[name=name]').addClass('error-input');
					$('#add-product-wrapper .new-added-properties .errorField').show();
					return false;
				} else {
					catalog.addNewProperty(admin.htmlspecialchars(name), admin.htmlspecialchars(value));
				}
			});

			/* Отменяет создание новой характеристики */
			$('.section-catalog').on('click', '#add-product-wrapper .cancel-new-prop', function () {
				catalog.closeAddedProperty();
			});

			/* Удаляет вновь созданную характеристику */
			$('.section-catalog').on('click', '#add-product-wrapper .remove-added-property', function () {
				var id = $(this).parents('.new-added-prop').data('id');
				$(this).parents('.new-added-prop').remove();
				admin.ajaxRequest({
					mguniqueurl: "action/deleteUserProperty",
					id: id
				});
			});

			// Удалить фотографию варианта товара
			$('.section-catalog').on('click', '#add-product-wrapper .del-img-variant', function () {
				if (confirm(lang.DELETE_IMAGE + '?')) {
					var src = admin.SITE + mgNoImageStub;
					var currentImg = $(this).parents('tr').find('.img-this-variant img').data('filename');
					if (!currentImg) {
						currentImg = $(this).parents('tr').find('.img-this-variant img').attr('filename');
					}
					if (!currentImg) {
						currentImg = $(this).parents('tr').find('.img-this-variant img').attr('src');
					}
					$(this).parents('tr').find('.img-this-variant img').attr('src', src).data('filename', '').attr('filename', '');
					$(this).hide();
					$(this).parents('tr').find('.img-button').show();
					//Пишем в поле deleteImage имена изображений, которые необходимо будет удалить при сохранении
					if (catalog.deleteImage) {
						catalog.deleteImage += '|' + currentImg;
					} else {
						catalog.deleteImage = currentImg;
					}
				}
				catalog.updateImageVar();
				return false;
			});

			// переключение табов в попапе для добаления рекомендованного товарар или категории
			$('.section-catalog').on('click', '#add-related-product-tabs .tabs-title', function () {
				$('#add-related-product-tabs .tabs-title').removeClass('is-active');
				$(this).addClass('is-active');

				$('#add-related-product-tabs-content .tabs-panel').removeClass('is-active');
				$('#add-related-product-tabs-content #' + $(this).data('target')).addClass('is-active');
			});

			$('.section-catalog').on('click', '.get-csv', function () {
				catalog.exportToCsv();

				return false;
			});

			$('.section-catalog').on('change', 'select[name=importScheme]', function () {
				switch ($(this).val()) {
					case 'last':
						catalog.showSchemeSettings('last');
						break;
					case 'new':
						catalog.showSchemeSettings('auto');
						break;
					default:
						return false;
				}
			});

			// Сохраняет изменения в модальном окне
			$('.section-catalog').on('click', '.columnComplianceModal .save-button', function () {
				var data = {};
				data['compliance'] = {};
				$('.section-catalog .columnComplianceModal select').each(function () {
					data['compliance'][$(this).attr('name')] = admin.htmlspecialchars($(this).val());
				});

				data['not_update'] = {};
				$('.section-catalog .columnComplianceModal input[type="checkbox"]').each(function () {
					if ($(this).prop('checked')) {
						data['not_update'][$(this).attr('name')] = '1';
					}
				});

				admin.ajaxRequest({
						mguniqueurl: "action/setCsvCompliance", // действия для выполнения на сервере
						data: data,
						importType: $('.columnComplianceModal button.save-button').attr('importType'),
						parseSeparator: catalog.parseSeparator
					},
					function (response) {
						// admin.indication(response.status, response.msg);
						$('.start-import').click();
					});
			});
			
			$('.section-catalog').on('click', '.columnComplianceModal .import-addition-settings__confirm', function () {
				catalog.parseSeparator = $('.import-addition-settings__separator-celect').val();
				var importType = $('.section-catalog select[name="importType"]').val();
				admin.ajaxRequest({
					mguniqueurl: "action/setCsvCompliance",
					importType: importType,
					parseSeparator: catalog.parseSeparator,
				}, function (response) {
					catalog.showSchemeSettings('auto', false, catalog.parseSeparator);
				});
				$(this).hide();
			});
			$('.section-catalog').on('click', '.columnComplianceModal .b-modal_close', function () {
				admin.closeModal($('.columnComplianceModal'));
			});

			$('.section-catalog').on('change', '.columnComplianceModal .import-addition-settings__separator-celect', function () {
				if(catalog.parseSeparator !== $(this).val()){
					$('.import-addition-settings__confirm').show();
				}
				else{
					$('.import-addition-settings__confirm').hide();
				}
			});

			//Пропустить шаг импорта товаров и перейти к загрузке изображений
			$('.section-catalog').on('click', '.csv_skip_step', function () {
				$('.block-upload-сsv').hide();
				$('.block-upload-images').show();
				$('.csv-import-title').hide();
				$('.img-import-title').show();
			});

			// Выбор ZIP архива на сервере
			$('.section-catalog').on('click', '.block-upload-images .browseImage', function () {
				admin.openUploader('catalog.getFile');
			});
			$('.section-catalog').on('click', '.selectCSV', function () {
				admin.openUploader('catalog.selectCSV');
			});

			// Обработчик для загрузки архива с изображениями
			$('.section-catalog').on('change', '.block-upload-images input[name="uploadImages"]', function () {
				catalog.uploadImagesArchive();
			});

			$('.section-catalog').on('click', '.block-upload-images .startGenerationProcess', function () {
				$(this).hide();
				$('.message-importing').html(lang.CATALOG_LOCALE_6 + 0
					+ '%<div class="progress-bar"><div class="progress-bar-inner" style="width:' + 0
					+ '%;"></div></div>');
				$('.message-importing').show();
				catalog.startGenerationImageFunc();
			});

			$('.section-catalog').on('click', 'a.gotoImageUpload', function () {
				$('.message-importing').hide();
				$('.import-container h3.title').text(lang.BLOCK_UPLOAD_IMAGES_TITLE);
				$('.block-upload-images').show();
				return false;
			});

			$('.section-catalog').on('click', '#overlay', function () {
				if ($('.section-catalog .yml-link-was-formed').is(":visible")) {
					$('.section-catalog .yml-link-was-formed .save-namelinkyml').removeClass('save-button');
					admin.closeModal($('.section-catalog .yml-link-was-formed'));
				}
			});

			// сохранение ссылки для yml - название файла надо переименовать после изменений.
			$('.section-catalog').on('click', ".yml-link-was-formed .save-namelinkyml", function () {
				var name = $(this).parents('.yml-link-was-formed').find('input[name=getyml]').val();
				admin.ajaxRequest({
						mguniqueurl: "action/renameYmlLink",
						name: name
					},
					function (response) {
						admin.indication(response.status, response.msg);
						if (response.status == 'success') {
							$('.section-catalog .yml-link-was-formed .yml-link').attr('href', admin.SITE + '/' + name);
							$('.section-catalog .yml-link-was-formed .yml-link').text(admin.SITE + '/' + name);
							$('.section-catalog .yml-link-was-formed .link').show();
							$('.section-catalog .yml-link-was-formed .link-name').hide();
						}
					});
			});

			// выбор рекомендуемых товаров или категорий
			$('.section-catalog').on('click', '#add-product-wrapper .related-type li', function () {
				if ($(this).hasClass('ui-state-active')) {
					return false;
				}
				var type = $(this).data('type');
				$(this).parent().find('.ui-state-active').removeClass('ui-state-active');
				$(this).addClass('ui-state-active');
				$('#add-product-wrapper .search-block').hide();
				$('#add-product-wrapper .search-block.' + type).show();
			});

			// добавление выбранных категорий в список рекомендуемых товаров save-add-related
			$('.section-catalog').on('click', '#add-product-wrapper .save-add-related', function () {
				var related = $('#add-product-wrapper select[name=related_cat]').val();
				if (related != null) {
					if (related.length > 0) {
						admin.ajaxRequest({
								mguniqueurl: "action/getRelatedCategory",
								cat: related
							},
							function (response) {
								if (response.status != 'error') {
									catalog.addrelatedCategory(response.data);
								}
							});
					}
				}
			});

			// раскрывает список опций в админке в карточке товара
			$('.section-catalog').on('click', '#add-product-wrapper .toggle-properties', function () {
				if ($(this).hasClass('open')) {
					var size = 4;
					$(this).text(lang.PROD_OPEN_CAT);
				} else {
					var size = $(this).parents('.price-settings').find('select.property option').length;
					$(this).text(lang.PROD_CLOSE_CAT);
				}
				$(this).toggleClass('open');
				$(this).parents('.price-settings').find('select.property').attr("size", size);
			});

			$('.section-catalog').on('click', '#add-product-wrapper .seo-gen-tmpl', function () {
				catalog.generateSeoFromTmpl('userClick');
			});

			$('.section-catalog').on('change', '.columnComplianceModal .multiColumnParamInput', function () {
				if ($(this).prop('checked')) {
					$('.multiColumnParam').show();
				} else {
					$('.multiColumnParam').hide();
				}
			});

			//выбор шаблона лендинга
			$('.section-catalog').on('change', 'select[name=landingIndividualTemplate]', function () {

				var colors = $(this).find('option:selected').data('schemes');
				var html = '';
				$('.landingColors span').text('');
				if (typeof colors !== catalog.undefinedColorName && colors.length > 0) {
					$('.landingColors span').text(lang.CATALOG_LOCALE_7);
					var active = 'active';
					colors.forEach(function (element) {
						html += '<li class="color-scheme-landing ' + active + '" data-scheme="' + element + '" style="background:#' + element + ';"></li>';
						active = '';
					});
				}
				$('.landingColors .color-list').html(html);
			});

			// Выбор цветовой схемы шаблона лендинга
			$('.section-catalog').on('click', '#add-product-wrapper .color-scheme-landing', function () {
				$(this).parents('ul').find('.color-scheme-landing').removeClass('active');
				$(this).addClass('active');
			});

			// Сброс картинки лендинга
			$('.section-catalog').on('click', '#add-product-wrapper .remove-added-background', function () {
				$('.landing-img-block img').attr('src', admin.SITE + mgNoImageStub);
				$('.landing-img-block img').attr('alt', 'no-img.jpg');
				$('.remove-added-background').hide();
			});

			// Обработчик для загрузки изображения для лендинга на сервер, сразу после выбора.
			$('.section-catalog').on('change', '#add-product-wrapper .upload-form-landing input[name="landingBackground"]', function () {
				var currentImg = '';
				var img_container = $('.landing-img-block .uploaded-img');

				if ($(this).val()) {
					$('.landingBackground').ajaxForm({
						type: "POST",
						url: "ajax",
						data: {
							mguniqueurl: "action/addImage"
						},
						cache: false,
						dataType: 'json',
						success: function (response) {
							admin.indication(response.status, response.msg);
							if (response.status != 'error') {
								var src = admin.SITE + '/uploads/' + response.data.img;
								catalog.tmpImage2Del += '|' + response.data.img;
								img_container.find('img').attr('src', src);
								img_container.find('img').attr('alt', response.data.img);
								$('.remove-added-background').show();
							} else {
								var src = admin.SITE + mgNoImageStub;
								img_container.find('img').attr('src', src);
								img_container.find('img').attr('alt', response.data.img);
							}
						}
					}).submit();
				}
			});

			$('.section-catalog').on('change', '.columnComplianceModal .widget-table-body .complianceHeaders tbody tr select', function () {
				if ($(this).val() == 'none') {
					$(this).addClass('none-selected');
				} else {
					$(this).removeClass('none-selected');
				}
			});

			//Открытие настроек импорта
			$('.section-catalog').on('click', '.columnComplianceModal .js-open-setting-csv', function(){
				$('.columnComplianceModal .update').show();
				$(this).hide();
			});

			//Выбор типа загрузки (Загрузка или обновление) CSV
			$('.section-catalog').on('change', '.columnComplianceModal #chooseUploadsType', function(){
				catalog.chooseUploadsType();
			});

			// изменение селекта типа обновления (по артикулу/по названию)
			$('.section-catalog').on('change', '.add-product-form-wrapper .update_type select', function () {
				if($(this).val() == 1){
					catalog.showFullModCsv();
					catalog.clearSelectValues();
					$('.widget-table-body .addColum').hide();
				}else{
					catalog.showtUpdateModCsv();
				}
			});

			//Добавление поля для импорта
			$('.section-catalog').on('click', '.addColumButton', function(){
				let id = $('#addColumSelect').val();
				if(id == 'null' || id == null){
					return false;
				}
				let trRow = $('.complianceHeaders #'+id);
				$(trRow[0].cells[0]).append('<span class="delCsvRow fl-right" style = "display:none;"></span>');
				$(trRow[0]).show();
				catalog.clearSelectValues();
				catalog.addToSelectValues();
				catalog.delCsvRowInit();
			});

			$('.section-catalog').on('click', '.columnComplianceModal .setFullModCsv', function () {
				$(this).hide();
				$('.columnComplianceModal .setUpdateModCsv').show();
				$('.columnComplianceModal .delete-all-products-btn').show();

				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
					$(this).show();
				});
			});

			$('.section-catalog').on('click', '.columnComplianceModal .setUpdateModCsv', function () {
				$(this).hide();
				$('.columnComplianceModal .setFullModCsv').show();
				$('.columnComplianceModal .delete-all-products-btn').hide();
				$('.columnComplianceModal .delete-all-products-btn input').prop('checked', false);

				requiredFields = ['Артикул', 'Цена', 'Старая цена', 'Количество', 'Оптовые цены', 'Склады', 'Валюта'];
				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
					if ($.inArray($(this).find('td:eq(0) b').text(), requiredFields) === -1) {
						$(this).hide().find('select').val('none').addClass('none-selected');

					}
				});
				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
					if ($(this).find('td:eq(0) b').text() == 'Оптовые цены') {
						$(this).find('select option').each(function () {
							if ($(this).text().indexOf('[оптовая цена]') > -1) {
								$(this).parent().val($(this).val()).removeClass('none-selected');
								return false;
							}
						});
					}
					if ($(this).find('td:eq(0) b').text() == 'Склады') {
						$(this).find('select option').each(function () {
							if ($(this).text().indexOf('[склад') > -1) {
								$(this).parent().val($(this).val()).removeClass('none-selected');
								return false;
							}
						});
					}
				});
			});

			$('.section-catalog').on('click', '.group-row:not(.show) input', function () {
				$(this).parents('.group-row').find('.showGroupVar').click();
			});

			$('.section-catalog').on('click', '.showGroupVar', function () {
				var type = $(this).parents('tr').data('type');
				var id = $(this).parents('tr').data('id');
				$(this).parents('tr').nextUntil(".group-row").addClass('variant-row-' + id);
				$('.variant-row-' + id + ':last').addClass('variant-row-last');
				$(this).parents('tr').find('.js-color-toggle').toggle();
				$(this).parents('tr').toggleClass('show');
				if ($(this).parents('tr').hasClass('show')) {
					$('.variant-table tr').each(function () {
						if ($(this).data(type) == id) $(this).show();
						if ($(this).data('id') == id) {
							$(this).find('.tmplApply').show();
							$(this).find('.tmpl-code').hide();
							$(this).find('.tmplPreview').hide();
							$(this).find('.tmplEdit').show();
							$(this).find('.tmplApply').parent('td').attr('colspan', '2');
						}
					});
				} else {
					$('.variant-table tr').each(function () {
						if ($(this).data(type) == id) $(this).hide();
						if ($(this).data('id') == id) {
							$(this).find('.tmplApply').hide();
							$(this).find('.tmpl-code').show();
							$(this).find('.tmplPreview').show();
							$(this).find('.tmplEdit').hide();
							$(this).find('.tmplApply').parent('td').attr('colspan', '1');
						}
					});
				}
			});

			$('.section-catalog').on('click', '.tmplApply', function () {
				var type = $(this).parents('tr').data('type');
				var id = $(this).parents('tr').data('id');
				var obj = $(this).parents('tr');
				var src = $(this).parents('tr').find('.action-list').parent().html();
				$('.variant-table tr').each(function () {
					if ($(this).data(type) == id) {
						$(this).find('[name=price]').val(obj.find('.tmpl-price').val());
						$(this).find('[name=old_price]').val(obj.find('.tmpl-old_price').val());
						$(this).find('[name=weight]').val(obj.find('.tmpl-weight').val());
						if (!$(this).find('[name=count]').prop('disabled')) $(this).find('[name=count]').val(obj.find('.tmpl-count').val());

						$(this).find('.action-list').parent().html(src);

						var row = $(this);
						opf = JSON.parse($('#product-op-fields-data').val());
						$.each(opf, function (i, val) {
							row.find('[name=opf_' + val.id + ']').val(obj.find('.tmpl-opf_' + val.id).val());
						});
					}
				});
				$('.variant-table .variant-row .deleteGroupVar').addClass('del-variant').removeClass('deleteGroupVar');
			});

			$('.section-catalog').on('keyup', '.group-row input', function () {
				if ($(this).parents('tr').hasClass('show')) return false;
				var type = $(this).parents('tr').data('type');
				var id = $(this).parents('tr').data('id');
				var obj = $(this).parents('tr');
				$('.variant-table tr').each(function () {
					if ($(this).data(type) == id) {
						$(this).find('[name=code]').val(obj.find('.tmpl-code').val());
						$(this).find('[name=price]').val(obj.find('.tmpl-price').val());
						$(this).find('[name=old_price]').val(obj.find('.tmpl-old_price').val());
						$(this).find('[name=weight]').val(obj.find('.tmpl-weight').val());
						if (!$(this).find('[name=count]').prop('disabled')) $(this).find('[name=count]').val(obj.find('.tmpl-count').val());
						return false;
					}
				});
			});

			$('.section-catalog').on('mousedown', '.group-row input', function () {
				$(this).parents('tr').find('.tmplApply').addClass('tmplChange');
			});

			$('.section-catalog').on('click', '.group-row .deleteGroupVar', function () {
				if (!confirm(lang.DELETE_GROUP_VAR)) return false;
				var type = $(this).parents('tr').data('type');
				var id = $(this).parents('tr').data('id');
				var obj = $(this).parents('tr');
				$('.variant-table tr').each(function () {
					if ($(this).data(type) == id) $(this).detach();
				});
				$(this).parents('tr').detach();
			});

			$('.section-catalog').on('click', '.back-category', function () {
				$('#add-product-wrapper [name=cat_id]').val(catalog.saveCategory).change();
				admin.reloadComboBoxes();
			});

			$('.section-catalog').on('click', '.drop-restart-variant', function () {
				userProperty.sizeMapCreatedProcess = true;
				$('.variant-row:not(:eq(0)) .del-variant').click();
				userProperty.sizeMapCreatedProcess = false;
				$('.variant-row:eq(0)').data('color', '').data('size', '');
				catalog.saveVarTable = $('.variant-table .variant-row, .variant-table .text-left').clone();
				$('#add-product-wrapper [name=cat_id]').change();
				catalog.saveCategory = $('#add-product-wrapper [name=cat_id]').val();
			});

			$('.section-catalog').on({
				mouseenter: function () {
					$('.js-show-upload-variants').addClass('show-upload-variants--visible');
				},
				mouseleave: function () {
					$('.js-show-upload-variants').removeClass('show-upload-variants--visible');
				}
			}, ".js-upload-image, .js-show-upload-variants"); //pass the element as an argument to .on

			$('.section-catalog').on('click', '.js-toggle-images', function () {
				catalog.subImgPopup('toggle');
			});

			$('.section-catalog').on('click', '#add-product-wrapper .closeModal', function () {
				admin.unlockEntity('#add-product-wrapper', 'product');
				catalog.subImgPopup('close');
			});

			$('.section-catalog').on('click', '.field-sorter[data-field="sort"]', function () {
				admin.AJAXCALLBACK = [
					{callback: 'admin.sortable', param: ['.product-table > tbody', 'product', true]},
				];
			});

			///////////// из userProperty.js ////////////////
			//обработчик применения установленных наценок в редактировании продукта
			$('.section-catalog').on('click', '.userField .apply-margin', function() {
				var property = $(this).parents('.price-settings');
				userProperty.applyMargin(property);
				property.find('.setup-margin-product').show();
				property.find('.panelMargin').remove();
			});

			//обработчик нажатия на ссылку: установить наценки
			$('.section-catalog').on('click', '.userField .setup-margin-product', function() {
				var select = $(this).parents('.price-settings').find('select');
				if ($('.panelMargin').length > 0) {
					var property = $(this).parents('.price-settings');
					property.find('.panelMargin').remove();
				} else {
					$(this).after(userProperty.panelMargin(select));
				}
				admin.initToolTip();
			});

			//обработчик нажатия на ссылки: установить тип вывода
			$('.section-catalog').on('click', '.userField .setup-type', function() {
				var option = $(this).parents('.price-settings');
				option.find('.setup-type').removeClass('selected');
				option.find('.setup-type').removeClass('active');
				$(this).addClass('selected');
				$(this).addClass('active');
			});

			$('.section-catalog').on('click', '#add-product-wrapper .aply-size-map', function () {
				if($(this).data('active') != 'disabled') {

					// сохраняем валюту и единицу измерения
					saveCurrency = $('#add-product-wrapper .btn-selected-currency').html();
					saveShortCurrency = $('#add-product-wrapper [name=currency_iso]').val();
					saveUnit = $('#add-product-wrapper .unit-input').val();

					userProperty.sizeMapCreatedProcess = true;
					$(this).data('active', 'disabled');
					$('#add-product-wrapper .aply-size-map').html('<i style="vertical-align: middle;margin-right: 5px;" class="fa fa-spinner fa-spin" aria-hidden="true"></i>'+lang.PROCESSING_WAIT);
					setTimeout(function() {
						if($('#add-product-wrapper .save-button').attr('id') == '') {
							$('#add-product-wrapper .group-row').detach();
						} else {
							//$('.variant-table').html(catalog.saveVarTable);
						}

						userProperty.createdSizeVariant();
						userProperty.createdSizeVariant();
						// для вариантов с размерами, чтобы единичные работали
						$('.add-position').click();
						$('.variant-row:eq('+($('.variant-row').length-1)+') .del-variant').click();
						$('.del-variant').hide();
						//
						catalog.saveVarTable = $('.variant-table .variant-row, .variant-table .text-left').clone();
						if($('#add-product-wrapper.save-button').attr('id') != "") {
							if($('.variant-table .variant-row').length > 1) {
								catalog.buildGroupVarTable();
								// $('.btn-selected-typeGroupVar').click();

								if(catalog.saveTypeGroupVar == 'default') {
									$('.variant-table th:eq(0)').hide();
									$('.variant-table tr').each(function() {
										$(this).find('td:eq(0)').hide();
									});
								} else {
									$('.variant-table th:eq(0)').show();
									$('.variant-table tr').each(function() {
										$(this).find('td:eq(0)').show();
									});
								}
							}
						}
						$('#add-product-wrapper .aply-size-map').data('active', 'active');
						$('#add-product-wrapper .aply-size-map').html('<i class="fa fa-check" aria-hidden="true"></i>'+lang.APPLY+'</a>');
						userProperty.sizeMapCreatedProcess = false;

						countCheck = 0;
						$('.size-map input[type=checkbox]').each(function() {
							if($(this).prop('checked')) countCheck++;
						});
						if(countCheck == 0) {
							catalog.saveTypeGroupVar = 'default';
						} else {
							$('.typeGroupVar').closest('th').css('width', '150px').css('position','relative');
						}

						if(catalog.saveTypeGroupVar == 'default') {
							$('.variant-table th:eq(0)').hide();
							$('.variant-table tr').each(function() {
								$(this).find('td:eq(0)').hide();
							});
							if(countCheck == 0) {
								//$('.variant-table th:eq(1)').hide();
								$('.variant-table .hide-content').hide();
								// $('.variant-table tr').each(function() {
								// 	$(this).find('td:eq(1)').hide();
								// });
								//$('.variant-table tr:not(tr:eq(0), tr:eq(1))').detach();
								$('.variant-table tr').show();
								$('.group-row').detach();
							} else {
								$('.variant-table th:eq(1)').show();
								$('.variant-table tr').each(function() {
									$(this).find('td:eq(1)').show();
								});
								if(
									$('.size-map__table tbody tr:first-child td').length != $('.size-map__table tbody tr:last-child td').length &&
									($('.size-map__table input[name=size-map-checkbox]:checked').attr('data-size')=='none' ||
										$('.size-map__table input[name=size-map-checkbox]:checked').attr('data-color')=='none')
								){
									$('.group-row').detach();
								}
							}
						} else {
							$('.variant-table th:eq(0)').show();
							$('.variant-table tr').each(function() {
								$(this).find('td:eq(0)').show();
							});
						}

						// $('.variant-table tr').each(function() {
						//   $(this).find('[name=color]').parents('td').hide();
						// });

						// возвращаем валюту и единицу измерения на место
						$('#add-product-wrapper .btn-selected-currency').html(saveCurrency);
						$('#add-product-wrapper [name=currency_iso]').val(saveShortCurrency);
						$('#add-product-wrapper .unit-input').val(saveUnit);
						$('#add-product-wrapper .btn-selected-unit').html(saveUnit).attr('realunit', saveUnit);
					},1);
				}
			});

			$('.section-catalog').on('click', '#add-product-wrapper .aply-size-map-check-all', function () {
				$('.size-map .checkbox input').each(function() {
					if($(this).attr('id').indexOf('none') != -1) return true;
					$(this).prop('checked', true);
				});
				$('.size-map .checkbox input').each(function() {
					if($(this).data('color') != 'none' && $(this).data('size') != 'none') return true;
					$(this).prop('checked', false);
				});
				$('.select-typeGroupVar select').val('color');
				catalog.saveTypeGroupVar = 'color';
			});

			$('.section-catalog').on('click', '#add-product-wrapper .aply-size-map-uncheck-all', function () {
				$('.size-map .checkbox input').each(function() {
					$(this).prop('checked', false);
				});
			});

			$('.section-catalog').on('change', '.size-map input', function() {
				//
				if($(this).data('color') == 'none') {
					$('.size-map .checkbox input').each(function() {
						if($(this).data('color') == 'none') return true;
						$(this).prop('checked', false);
					});
					$('.select-typeGroupVar select').val('default');
					catalog.saveTypeGroupVar = 'default';
					return false;
				}
				//
				if($(this).data('size') == 'none') {
					$('.size-map .checkbox input').each(function() {
						if($(this).data('size') == 'none') return true;
						$(this).prop('checked', false);
					});
					$('.select-typeGroupVar select').val('default');
					catalog.saveTypeGroupVar = 'default';
					return false;
				}
				//
				$('.select-typeGroupVar select').val('color');
				catalog.saveTypeGroupVar = 'color';
				$('.size-map .checkbox input').each(function() {
					if($(this).data('color') != 'none' && $(this).data('size') != 'none') return true;
					$(this).prop('checked', false);
				});
			});

			$('.section-catalog').on('click', '.filter-fix-checkbox', function() {
				ob = $(this);
				setTimeout(function() {
					if(ob.prop('checked')) {
						ob.val(1);
					} else {
						ob.val(0);
					}
				}, 100);
			});

			// для количественного поля в админке будем прописывать знак бесконечности при пустом значении или минусовом
			$('.section-catalog').on('blur', '.product-text-inputs input[name=count], .product-text-inputs .tmpl-count, .table-wrapper .count .fastsave.js-fast-count , .product-text-inputs input[name=multiplicity]', function () {
				// округляем до двух символов после запятой, если получилось целое число, то убираем нули после запятой
				let v = parseFloat($(this).val()).toFixed(2).replace(/\.00/g, '');
				if (isNaN(v)) {
					v = '-1';
				}else{

					if(v.toString().search(/(\.)|(\,)/)!==-1){
						// если последний ноль, то убираем
						if(v.toString().slice(-1)==='0'){
							v = v.slice(0, -1);
						}
					}
				}
				$(this).val(v);

				if ($(this).val() < 0 || $(this).val() == "") {
					$(this).val('∞');
				}
			});

			$('.section-catalog').on('click', '.section-hits', function(){
				newbieWay.showHits('catalogScenario');
				introJs().start();
			});

		},
		checkBlockIntervalFunction: function() {
			if (!$('#add-product-wrapper').length) {
				clearInterval(catalog.checkBlockInterval);
			}
			if(admin.blockTime == 0) {
				admin.setAndCheckBlock('#add-product-wrapper', 'product');
			}
			admin.blockTime -= 1;
		},
		updateImageVar: function () {
			$('.variant-table .group-row').each(function () {
				if ($(this).hasClass('show')) return true;
				var type = $(this).data('type');
				var id = $(this).data('id');
				var src = $(this).find('.action-list').parent().html();
				$('.variant-table tr').each(function () {
					if ($(this).data(type) == id) {
						$(this).find('.action-list').parent().html(src);
					}
				});
				$('.variant-table .variant-row .deleteGroupVar').addClass('del-variant').removeClass('deleteGroupVar');
			});
		},
		/**
		 * Генерируем мета описание
		 */
		generateMetaDesc: function (description) {
			if (!description) {
				return '';
			}
			if (description == undefined) description = '';
			var short_desc = description.replace(/<\/?[^>]+>/g, '');
			short_desc = admin.htmlspecialchars_decode(short_desc.replace(/\n/g, ' ').replace(/&nbsp;/g, '').replace(/\s\s*/g, ' ').replace(/"/g, ''));

			if (short_desc.length > 150) {
				var point = short_desc.indexOf('.', 150);
				short_desc = short_desc.substr(0, (point > 0 ? point : short_desc.indexOf(' ', 150)));
			}

			return short_desc;
		},
		/**
		 * Генерируем ключевые слова для товара
		 * @param string title
		 */
		generateKeywords: function (title) {
			if (!$('#add-product-wrapper input[name=meta_keywords]').val()) {
				var code = $('input[name=code]').val();
				if (code) {
					code = ', ' + code;
				}
				var keywords = title + ' ' + lang.META_BUY + code;
				var keyarr = title.split(' ');
				for (var i = 0; i < keyarr.length; i++) {
					var word = keyarr[i].replace('"', '');
					if (word.length > 3) {
						keywords += ', ' + word;
					} else {
						if (i !== keyarr.length - 1) {
							keywords += ', ' + word + ' ' + keyarr[i + 1].replace(/"/g, '');
							i++;
						} else {
							keywords += ', ' + word;
						}
					}
				}
				$('#add-product-wrapper input[name=meta_keywords]').val(keywords);
			}
		},
		/**
		 * Запускаем генерацию метатегов по шаблонам из настроек
		 */
		generateSeoFromTmpl: function (who) {

			var data = {
				title: $('.product-text-inputs input[name=title]').val(),
				category_name: $('.product-text-inputs select#productCategorySelect option:selected').text(),
				code: $('.product-text-inputs input[name=code]').val(),
				description: $('textarea[name=html_content]').val(),
				meta_title: $('#add-product-wrapper input[name=meta_title]').val(),
				meta_keywords: $('#add-product-wrapper input[name=meta_keywords]').val(),
				meta_desc: $('#add-product-wrapper textarea[name=meta_desc]').val(),
				userProperty: userProperty.getUserFields(),
				price: $('.variant-table [name=price]').val(),
				currency: $('.btn-selected-currency').text(),
			};

			if (who == 'userClick') {
				admin.ajaxRequest({
					mguniqueurl: "action/generateSeoFromTmpl",
					type: 'product',
					data: data
				}, function (response) {
					$.each(response.data, function (key, value) {
						if (value) {
							$('#add-product-wrapper [name=' + key + ']').val(value);
						}
					});

					$('#add-product-wrapper .js-meta-data').trigger('blur');
					admin.indication(response.status, response.msg);
				});
			}
		},

		startGenerationImageFunc: function (nextItem, total_count, imgCount) {
			nextItem = typeof nextItem !== 'undefined' ? nextItem : 0;
			admin.ajaxRequest({
					mguniqueurl: "action/startGenerationImagePreview",
					nextItem: nextItem,
					total_count: total_count,
					imgCount: imgCount
				},
				function (response) {
					admin.indication(response.status, response.msg);

					if (response.data.percent < 100) {
						$('.message-importing').html(lang.CATALOG_LOCALE_6
							+ response.data.percent
							+ '%<div class="progress-bar"><div class="progress-bar-inner" style="width:'
							+ response.data.percent + '%;"></div></div>');
						catalog.startGenerationImageFunc(response.data.nextItem, response.data.total_count, response.data.imgCount);
					} else {
						$('.message-importing').html(lang.CATALOG_LOCALE_6
							+ response.data.percent
							+ '%<div class="progress-bar"><div class="progress-bar-inner" style="width:'
							+ '100%;"></div></div>');
						if (catalog.startGenerationImage) {
							$('.loger').append(lang.CATALOG_LOCALE_10 + ' \
			   <a class="refresh-page custom-btn" href="' + mgBaseDir + '/mg-admin/">\n\
				 <span>' + lang.CATALOG_REFRESH + '</span>\
			  </a>\n\
			  <br><a href="' + admin.SITE + '/uploads/temp/data_csv/import_csv_log.txt" target="blank">' + lang.CATALOG_VIEW_LOG + '</a>');
						}
//          admin.refreshPanel();
					}
					$('.log').text($('.log').text() + response.data.log);
					$('.log').text($('.log').text() + response.msg);
					$('.loger').show();
				});
		},
		/**
		 * Загружает Архив с изображениями на сервер для последующего импорта
		 */
		uploadImagesArchive: function () {
			$('.section-comerceml input[name="upload"]').hide();
			// $('.mailLoader').before('<div class="view-action" style="margin-top:-2px;">' + lang.LOADING + '</div>');
			// отправка архива с изображениями на сервер
			// comerceMlModule.printLog('Идет передача файла на сервер. Подождите, пожалуйста...');
			$('.upload-goods-image-form').ajaxSubmit({
				type: "POST",
				url: "ajax",
				cache: false,
				dataType: 'json',
				data: {
					mguniqueurl: "action/uploadImagesArchive",
				},
				error: function (q, w, r) {
					console.log(q);
					console.log(w);
					console.log(r);
					// comerceMlModule.printLog("Ошибка: Загружаемый вами файл превысил максимальный объем и не может быть передан на сервер из-за ограничения в настройках файла php.ini");
					admin.indication('error', lang.CATALOG_LOCALE_12);
					$('.section-comerceml input[name="upload"]').show();
					$('.view-action').remove();
				},
				success: function (response) {
					if (response.msg) admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						$('.upload-images').hide();
						$('.start-generate').show();
					} else {
						$('.import-container input[name="upload"]').val('');
					}
					$('.view-action').remove();
				},
			});
		},
		/**
		 * функция для приема файла из аплоадера
		 */
		getFile: function (file) {
			$('.section-comerceml .b-modal input[name="src"]').val(file.url);
			$.ajax({
				type: "POST",
				url: "ajax",
				data: {
					mguniqueurl: "action/selectImagesArchive",
					data: {
						filename: file.url,
					}
				},
				dataType: 'json',
				success: function (response) {
					admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						$('.upload-images').hide();
						$('.start-generate').show();
					}
				}
			});
		},
		/*
	 * Открывает модальное окно для установки соответствия полей импорта
	 * @param string scheme
	 * @requiredFields bool Масси обязательных полей
	 * @returns void
	 */
		showSchemeSettings: function (scheme, requiredFields=false,parseSeparator = ';') {
			$('.columnComplianceModal .widget-table-body ul').empty();
			var importType = $('.section-catalog select[name="importType"]').val();

			admin.ajaxRequest({
					mguniqueurl: "action/getCsvCompliance", // действия для выполнения на сервере
					scheme: scheme,
					importType: importType,
					parseSeparator: parseSeparator
				},
				catalog.fillCsvCopliance(importType, requiredFields));
			$('.columnComplianceModal button.save-button').attr('importType', importType);

			//
			setTimeout(function () {
				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
					if ($(this).find('td:eq(0) b').text() == 'ID товара') {
						$(this).find('select option').each(function () {
							if ($(this).text().indexOf('ID товара') > -1) {
								$(this).parent().val($(this).val()).removeClass('none-selected');
								return false;
							}
						});
					}
					if ($(this).find('td:eq(0) b').text() == 'Оптовые цены') {
						$(this).find('select option').each(function () {
							if ($(this).text().indexOf('[оптовая цена]') > -1) {
								$(this).parent().val($(this).val()).removeClass('none-selected');
								return false;
							}
						});
					}
					if ($(this).find('td:eq(0) b').text() == 'Склады') {
						$(this).find('select option').each(function () {
							if ($(this).text().indexOf('[склад') > -1) {
								$(this).parent().val($(this).val()).removeClass('none-selected');
								return false;
							}
						});
					}
				});
			}, 500);
			//

			admin.openModal($('.columnComplianceModal'));
		},
		/*
	 * Заполнение модального окна выбора соответствия полей данными
	 * @returns {Function}
	 */
		fillCsvCopliance: function (importType, requiredFields) {
			return function (response) {
				var titleList = '';
				var compList = '';
				var fieldContinue = -1;

				if (importType === 'MogutaCMS') {
					fieldContinue = 2;

					if ($(".block-upload-сsv select[name=identifyType]").val() == 'article') {
						fieldContinue = 8;
					}
				}

				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody').html('');

				response.data.titleList.forEach(function (item, i, arr) {
					titleList += '<option value="' + i + '">' + item + '</option>';
				});

				var typeWork = 'array';
				var count = response.data.maskArray.length;
				var keys = undefined;
				if (count == undefined) {
					count = Object.keys(response.data.maskArray).length;
					typeWork = 'object';
					keys = [];
					for (var key in response.data.maskArray) {
						keys.push(key);
					}
				}
				for (i = 0; i < count; i++) {

					// response.data.maskArray.forEach(function(item, i, arr) {
					var notUpdate = '';
					var disabled = '';

					if (i == fieldContinue) {
						disabled = 'disabled="disabled"';
					} else if (response.data.notUpdate[i] == 1) {
						notUpdate = 'checked="checked"';
					}

					if ($.inArray(i, response.data.requiredFields) !== -1) {
						required = 'required';
					} else {
						required = '';
					}

					if (typeWork == 'array') {
						rowName = response.data.maskArray[i];
						dataFields = response.data.fieldsInfo[i];
					} else {
						rowName = response.data.maskArray[keys[i]];
						dataFields = response.data.fieldsInfo[keys[i]];
					}

					if (typeWork == 'array') {
						index = i;
					} else {
						index = keys[i];
					}
					let reqFild = false ;
					if(requiredFields != false){
						if ($.inArray(rowName, requiredFields) != -1) {
							reqFild = true;
						}
					}

					compList = '\
			<tr id = "row_'+i+'">\
			  <td>\
				<b>' + rowName + '</b>\
				<span class="fl-right" flow="up" tooltip="' + dataFields + '" class=""><i class="fa fa-question-circle" style="cursor:pointer;" aria-label="' + dataFields + '"></i></span>';
					if(reqFild == true){
						compList += '<span class="required_field" tooltip="Обязательное поле">*</span>';
					}
					compList += '\ </td>\
			  <td style="padding-right:0;"><select name="colIndex' + index + '" style="margin:0;width:calc(100% + 1px);" ' + required + '>\
				<option value="none">' + lang.NO_SELECT + '</option>\
				' + titleList + '\
			  </select></td>\
			</tr>';
					$('.columnComplianceModal .widget-table-body .complianceHeaders tbody').append(compList);
					$('.columnComplianceModal .widget-table-body .complianceHeaders tbody select[name=colIndex' + i + '] option[value=' + response.data.compliance[i] + ']').attr('selected', 'selected');
				}

				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
					if ($(this).find('select').val() == 'none') {
						$(this).find('select').addClass('none-selected');
					}
				});

				$('.csvPreview').html(response.data.csvPreview);
			}
		},

		exportToCsv: function ({page = 1, rowCount = 0, fromAllCats = true, catIds = [], encoding = 'win1251'}) {
			if (!page) {
				page = 1;
			}
			if (!rowCount) {
				rowCount = 0;
			}
			loader = $('.mailLoader');
			$.ajax({
				type: "POST",
				url: mgBaseDir + "/mg-admin/",
				data: {
					csv: 1,
					page,
					rowCount,
					fromAllCats,
					catIds,
					encoding
				},
				dataType: "json",
				cache: false,
				beforeSend: function () {
					// флаг, говорит о том что начался процесс загрузки с сервера
					admin.WAIT_PROCESS = true;
					loader.hide();
					loader.before('<div class="view-action" style="display:none; margin-top:-2px;">' + lang.LOADING + '</div>');
					// через 300 msec отобразится лоадер.
					// Задержка нужна для того чтобы не мерцать лоадером на быстрых серверах.

					setTimeout(function () {
						if (admin.WAIT_PROCESS) {
							admin.waiting(true);
						}
					}, admin.WAIT_DELAY);
				},
				success: function (response) {
					admin.WAIT_PROCESS = false;
					admin.waiting(false);
					loader.show();
					$('.view-action').remove();

					if (!response.success) {
						admin.indication('success', lang.INDICATION_INFO_EXPORTED + ' ' + response.percent + '%');
						setTimeout(function () {
							catalog.exportToCsv({
								page: response.nextPage,
								rowCount: response.rowCount,
								fromAllCats,
								catIds
							});
						}, 2000);
					} else {
						admin.indication('success', lang.INDICATION_INFO_EXPORTED + ' 100%');
						setTimeout(function () {
							if (confirm(lang.CATALOG_MESSAGE_1 + response.file + lang.CATALOG_MESSAGE_2)) {
								location.href = mgBaseDir + '/' + response.file;
							}
						}, 100);
//            $('body').append('<iframe src="'+mgBaseDir+'/'+response.file+'" style="display: none;"></iframe>');
					}
				}
			});
		},

		/**
		 * Открывает модальное окно.
		 * type - тип окна, либо для создания нового товара, либо для редактирования старого.
		 */
		openModalWindow: function (type, id) {
			if (admin.CURRENCY == undefined || admin.CURRENCY == null || admin.CURRENCY == '') {
				admin.CURRENCY = admin.getSettingFromDB('currency');
			}

			if (admin.CURRENCY_ISO == undefined || admin.CURRENCY_ISO == null || admin.CURRENCY_ISO == '') {
				admin.CURRENCY_ISO = admin.getSettingFromDB('currencyShopIso');
			}

			try {
				if (CKEDITOR.instances['html_content']) {
					CKEDITOR.instances['html_content'].destroy();
				}
				if (CKEDITOR.instances['html_content-textarea']) {
					CKEDITOR.instances['html_content-textarea'].destroy();
				}
			} catch (e) {
			}

			switch (type) {
				case 'edit': {

					catalog.clearFields();
					$('.html-content-edit').show();
					$('.product-desc-wrapper #html-content-wrapper').hide();
					$('.add-product-table-icon').text(lang.CATALOG_PRODUCT_EDIT);
					$('.add-product-table-icon').parent().find('i').attr('class', 'fa fa-pencil');
					catalog.editProduct(id);

					break;
				}
				case 'add': {
					$('.add-product-table-icon').text(lang.CATALOG_PRODUCT_ADD);
					$('.add-product-table-icon').parent().find('i').attr('class', 'fa fa-plus-circle');
					catalog.clearFields();
					//$('textarea[name=html_content]').ckeditor();

					$('.related-block').html('');
					$('.related-block').hide();

					// получаем с сервера все доступные пользовательские параметры
					admin.ajaxRequest({
							mguniqueurl: "action/getUserProperty"
						},
						function (response) {
							// выводим поля для редактирования пользовательских характеристик
							userProperty.createUserFields(null, response.data.allProperty);
						},
						$('.error-input').removeClass('error-input')
					);

					catalog.msgRelated();
					var src = admin.SITE + mgNoImageStub;
					var row = catalog.drawControlImage(src, false, '', '', '');
					$('.main-image').html(row);
					// $('.main-img-prod .main-image').hide();

					if (typeof id !== "undefined") {
						$('.product-text-inputs select[name=cat_id]').val(id);
					}

					var catId = $('.filter-container select[name=cat_id]').val();
					if (catId == 'null' || catId === undefined) {
						catId = 0;
					}

					// получаем набор общих характеристик и выводим их
					catalog.generateUserProreprty(0, catId);

					if ($('.storageToView').html() != undefined) $('.variant-table [name=count]').prop('disabled', true);

					$('.add-position').show();

					$('.variant-table tr').each(function () {
						if ($(this).find('[name=count]').val() == '') $(this).find('[name=count]').val('∞');
					});

					catalog.saveVarTable = undefined;
					catalog.saveCategory = "0";

					break;
				}
				default: {
					catalog.clearFields();
					break;
				}
			}

			if ($('#productCategorySelect').val() == 0) {
				$('.add-property-field').hide();
			} else {
				$('.add-property-field').show();
			}

			// Вызов модального окна.
			admin.openModal('.product-desc-wrapper');

		},

		/**
		 *  Изменяет список пользовательских свойств для выбранной категории в редактировании товара
		 */
		generateUserProreprty: function (produtcId, categoryId) {

			admin.ajaxRequest({
					mguniqueurl: "action/getProdDataWithCat",
					produtcId: produtcId,
					categoryId: categoryId
				},
				function (response) {
					userProperty.createUserFields($('.userField'), response.data.thisUserFields, response.data.allProperty, response.data.propertyGroup);
					userProperty.createSizeMap(response.data.allProperty);
					catalog.buildGroupVarTable();
				},
			);

		},

		/**
		 *  Проверка заполненности полей, для каждого поля прописывается свое правило.
		 */
		checkRulesForm: function () {
			$('.errorField').css('display', 'none');
			$('.product-text-inputs input').removeClass('error-input');
			var error = false;

			// наименование не должно иметь специальных символов.
			if (!$('.product-text-inputs input[name=title]').val()) {
				$('.product-text-inputs input[name=title]').parent("label").find('.errorField').css('display', 'block');
				$('.product-text-inputs input[name=title]').addClass('error-input');
				error = true;
			}

			// наименование не должно иметь специальных символов.
			if (!admin.regTest(2, $('.product-text-inputs input[name=url]').val()) || !$('.product-text-inputs input[name=url]').val()) {
				$('.product-text-inputs input[name=url]').parent("label").find('.errorField').css('display', 'block');
				$('.product-text-inputs input[name=url]').addClass('error-input');
				error = true;
			}

			// артикул обязательно надо заполнить.
			if (!$('.product-text-inputs input[name=code]').val()) {
				$('.product-text-inputs input[name=code]').parent("label").find('.errorField').css('display', 'block');
				$('.product-text-inputs input[name=code]').addClass('error-input');
				error = true;
			}

			// Проверка поля для стоимости, является ли текст в него введенный числом.
			if (isNaN(parseFloat($('.product-text-inputs input[name=price]').val()))) {
				$('.product-text-inputs input[name=price]').parent("label").find('.errorField').css('display', 'block');
				$('.product-text-inputs input[name=price]').addClass('error-input');
				error = true;
			}

			var url = $('.product-text-inputs input[name=url]').val();
			var reg = new RegExp('([^/-a-z\.\d])', 'i');

			if (reg.test(url)) {
				$('.product-text-inputs input[name=url]').parent("label").find('.errorField').css('display', 'block');
				$('.product-text-inputs input[name=url]').addClass('error-input');
				$('.product-text-inputs input[name=url]').val('');
				error = true;
			}

			// Проверка поля для старой стоимости, является ли текст в него введенный числом.
			$('.product-text-inputs input[name=old_price]').each(function () {
				var val = $(this).val();
				if (isNaN(parseFloat(val)) && val != "") {
					$(this).parent("label").find('.errorField').css('display', 'block');
					$(this).addClass('error-input');
					error = true;
				}
			});

			// Проверка поля количество, является ли текст в него введенный числом.
			$('.product-text-inputs input[name=count]').each(function () {
				var val = $(this).val();
				if (val == '\u221E' || val == '' || parseFloat(val) < 0) {
					val = "-1";
					$(this).val('∞');
				}
				if (isNaN(parseFloat(val))) {
					$(this).parent("label").find('.errorField').css('display', 'block');
					$(this).addClass('error-input');
					error = true;
				}
			});
			if (error == true) {
				var $container = $("#add-product-wrapper").parent();
				var $scrollTo = $('.error-input:first');

				$container.animate({
					scrollTop: $scrollTo.offset().top - $container.offset().top + $container.scrollTop() - 25,
					scrollLeft: 0
				}, 300);
				return false;
			}

			return true;
		},


		/**
		 * Сохранение изменений в модальном окне продукта.
		 * Используется и для сохранения редактированных данных и для сохранения нового продукта.
		 * id - идентификатор продукта, может отсутствовать если производится добавление нового товара.
		 */
		saveProduct: function (id, closeModal) {
			closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
			// Если поля неверно заполнены, то не отправляем запрос на сервер.
			if (!catalog.checkRulesForm()) {
				return false;
			}

			$('#add-product-wrapper input[type=text]').each(function() {
				$(this).val(admin.htmlspecialchars($(this).val()));
			});

			var recommend = $('.save-button').data('recommend');
			var activity = $('.save-button').data('activity');
			var newprod = $('.save-button').data('new');
			//определяем имеются ли варианты товара
			if (!id) $('.variant-row').data('id', '');
			var variants = catalog.getVariant();

			if (catalog.errorVariantField) {
				admin.indication('error', lang.ERROR_VARIANT);
				return false;
			}

			if (closeModal) {
				if ($('textarea[name=html_content]').val() == '' && $('textarea[name=html_content]').text() != '') {
					if (!confirm(lang.ACCEPT_EMPTY_DESC + '?')) {
						return false;
					}
				}
			}

			if ($('.addedProperty .new-added-prop').length > 0) {
				catalog.saveAddedProperties();
			}

			$('.userField .userfd').each(function () {
				if ($(this).find('.price-body').html() != undefined) {
					var check = false;
					$('.userField .userfd .price-body .price-footer .setup-type').each(function () {
						if ($(this).hasClass('selected')) {
							check = true;
						}
					});
					if (!check) {
						$(this).find('.price-body .price-footer .setup-type:eq(0)').click();
					}
				}
			});

			if (catalog.deleteImage != undefined && catalog.deleteImage != null && catalog.deleteImage != '') {
				var imgs = catalog.deleteImage.split('|');
				catalog.deleteImage = '';

				imgs.forEach(function (element, index, array) {
					var eleme = element.replace('thumbs/', '');
					var splitIndex = eleme.lastIndexOf('/') + 1;
					var fpart = eleme.slice(0, splitIndex);
					var spart = eleme.slice(splitIndex);
					var clearSpart = spart.replace('30_', '');
					clearSpart = clearSpart.replace('70_', '');
					var fullSrc = fpart + clearSpart;
					var miniSrc70 = fpart + 'thumbs/70_' + clearSpart;
					var miniSrc30 = fpart + 'thumbs/30_' + clearSpart;
					if (
						$('#add-product-wrapper .reveal-body>.collapse img[src="' + fullSrc + '"]').length ||
						$('#add-product-wrapper .reveal-body>.collapse img[src="' + miniSrc70 + '"]').length ||
						$('#add-product-wrapper .reveal-body>.collapse img[src="' + miniSrc30 + '"]').length ||
						spart.indexOf('no-img.') >= 0
					) {
					} else {
						if (catalog.deleteImage) {
							catalog.deleteImage += '|' + fullSrc;
						} else {
							catalog.deleteImage = fullSrc;
						}
					}
				});
			}
			var imgs = '';


			if (!variants) {

				// Пакет характеристик товара.
				var packedProperty = {
					id: id,
					title: $('#add-product-wrapper .product-text-inputs input[name=title]').val(),
					link_electro: $('#add-product-wrapper .product-text-inputs input[name=link_electro]').val(),
					url: $('#add-product-wrapper .product-text-inputs input[name=url]').val(),
					code: $('#add-product-wrapper .product-text-inputs input[name=code]').val(),
					price: $('#add-product-wrapper .product-text-inputs input[name=price]').val(),
					old_price: $('#add-product-wrapper .product-text-inputs input[name=old_price]').val(),
					image_url: catalog.createFieldImgUrl(),
					image_title: catalog.createFieldImgTitle(),
					image_alt: catalog.createFieldImgAlt(),
					delete_image: catalog.deleteImage,
					count: $('#add-product-wrapper .product-text-inputs input[name=count]').val(),
					weight: $('#add-product-wrapper .product-text-inputs input[name=weight]').val(),
					cat_id: $('#add-product-wrapper .product-text-inputs select[name=cat_id]').val(),
					inside_cat: catalog.createInsideCat(),
					description: $('textarea[name=html_content]').val(),
					short_description: $('textarea[name=short_html_content]').val(),
					meta_title: $('#add-product-wrapper input[name=meta_title]').val(),
					meta_keywords: $('#add-product-wrapper input[name=meta_keywords]').val(),
					meta_desc: $('#add-product-wrapper textarea[name=meta_desc]').val(),
					currency_iso: $('#add-product-wrapper select[name=currency_iso]').val(),
					recommend: recommend,
					activity: activity,
					unit: $('#add-product-wrapper .btn-selected-unit').attr('realunit'),
					new: newprod,
					userProperty: userProperty.getUserFields(),
					related: catalog.getRelatedProducts(),
					variants: null,
					yml_sales_notes: $('.yml-wrapper input[name=yml_sales_notes]').val(),
					related_cat: catalog.getRelatedCategory(),
					multiplicity: $('.product-text-inputs input[name=multiplicity]').val(),
					lang: $('.select-lang').val(),
					landingTemplate: $('select[name=landingIndividualTemplate]').val(),
					landingColor: $('.landingSettings .color-list').find('.active').attr('data-scheme'),
					ytp: $('[name=ytpText]').val(),
					landingImage: $('.landing-img-block img').attr('alt'),
					landingSwitch: $('select[name=landingSwitch]').val(),
					storage: catalog.selectedStorage,
				}
			} else {

				var packedProperty = {
					id: id,
					title: $('#add-product-wrapper .product-text-inputs input[name=title]').val(),
					link_electro: $('#add-product-wrapper .product-text-inputs input[name=link_electro]').val(),
					code: $('#add-product-wrapper .variant-table tr').eq(1).find('input[name=code]').val(),
					price: $('#add-product-wrapper .variant-table tr').eq(1).find('input[name=price]').val(),
					old_price: $('#add-product-wrapper .variant-table tr').eq(1).find('input[name=old_price]').val(),
					count: $('#add-product-wrapper .variant-table tr').eq(1).find('input[name=count]').val(),
					weight: $('#add-product-wrapper .variant-table tr').eq(1).find('input[name=weight]').val(),
					url: $('#add-product-wrapper .product-text-inputs input[name=url]').val(),
					image_url: catalog.createFieldImgUrl(),
					image_title: catalog.createFieldImgTitle(),
					image_alt: catalog.createFieldImgAlt(),
					delete_image: catalog.deleteImage,
					cat_id: $('#add-product-wrapper .product-text-inputs select[name=cat_id]').val(),
					inside_cat: catalog.createInsideCat(),
					description: $('#add-product-wrapper textarea[name=html_content]').val(),
					short_description: $('#add-product-wrapper textarea[name=short_html_content]').val(),
					meta_title: $('#add-product-wrapper input[name=meta_title]').val(),
					meta_keywords: $('#add-product-wrapper input[name=meta_keywords]').val(),
					meta_desc: $('#add-product-wrapper textarea[name=meta_desc]').val(),
					currency_iso: $('#add-product-wrapper select[name=currency_iso]').val(),
					recommend: recommend,
					activity: activity,
					unit: $('#add-product-wrapper .btn-selected-unit').attr('realunit'),
					new: newprod,
					userProperty: userProperty.getUserFields(),
					related: catalog.getRelatedProducts(),
					variants: variants,
					yml_sales_notes: $('.yml-wrapper input[name=yml_sales_notes]').val(),
					related_cat: catalog.getRelatedCategory(),
					multiplicity: $('.product-text-inputs input[name=multiplicity]').val(),
					lang: $('.select-lang').val(),
					landingTemplate: $('select[name=landingIndividualTemplate]').val(),
					landingColor: $('.landingSettings .color-list').find('.active').attr('data-scheme'),
					ytp: $('[name=ytpText]').val(),
					landingImage: $('.landing-img-block img').attr('alt'),
					landingSwitch: $('select[name=landingSwitch]').val(),
					storage: catalog.selectedStorage,
				}

			}


			if ($('.weightUnit-block').data('product') && $('.weightUnit-block').data('product') != $('.weightUnit-block').data('category')) {
				packedProperty.weight_unit = $('.weightUnit-block').data('product');
			} else {
				packedProperty.weight_unit = null;
			}
			packedProperty.weight_unit_calc = $('.weightUnit-block').data('product');

			opf = JSON.parse($('#product-op-fields-data').val());
			$.each(opf, function (i, val) {
				packedProperty['opf_' + val.id] = $('#add-product-wrapper .variant-table tr').eq(1).find('input[name=opf_' + val.id + ']').val();
			});

			catalog.deleteImage = '';

			// отправка данных на сервер для сохранения
			admin.ajaxRequest({mguniqueurl: "action/saveProduct", data: JSON.stringify(packedProperty)},
				function (response) {
					admin.clearGetParam();
					if (closeModal) {
						admin.indication(response.status, response.msg);
					}
					if (response.status == 'error') return false;
					var row = response.data.html;

					// Вычисляем, по наличию характеристики 'id',
					// какая операция производится с продуктом, добавление или изменение.
					// Если id есть значит надо обновить запись в таблице.
					if (packedProperty.id) {
						$('.product-tbody tr[id=' + packedProperty.id + ']').replaceWith(row);
					} else {
						// Если id небыло значит добавляем новую строку в начало таблицы.
						if ($('.product-tbody tr:first').length > 0) {
							$('.product-tbody tr:first').before(row);
						} else {
							$('.product-tbody').append(row);
						}

						var newCount = $('.widget-table-title .produc-count strong').text() - 0 + 1;
						if (response.status == 'success') {
							$('.widget-table-title .produc-count strong').text(newCount);
						}

						$('.product-count strong').html(+$('.product-count strong').html() + 1);
					}

					$('.no-results').remove();

					// Закрываем окно
					if (closeModal) {
						admin.closeModal('#add-product-wrapper');
						admin.initToolTip();
						catalog.subImgPopup('close');
					}

					if (catalog.changeStorage) {
						$('.selected-storage-name').data('name', $('#add-product-wrapper .storageToView').val());
						catalog.selectedStorage = $('#add-product-wrapper .storageToView').val();
						catalog.editProduct(response.data.id);
					}
					catalog.initMovers();
				}
			);
		},

		cloneProd: function (id, prod) {
			// получаем с сервера все доступные пользовательские параметры
			admin.ajaxRequest({
					mguniqueurl: "action/cloneProduct",
					id: id
				},
				function (response) {
					admin.indication(response.status, response.msg);
					if (response.status == 'error') return false;
					var row = response.data.html;

					// добавляем новую строку в начало таблицы.
					if ($('.product-tbody tr:first').length > 0) {
						$('.product-tbody tr:first').before(row);
					} else {
						$('.product-tbody ').append(row);
					}

					var newCount = $('.widget-table-title .produc-count strong').text() - 0 + 1;
					if (response.status == 'success') {
						$('.widget-table-title .produc-count strong').text(newCount);
					}

					$('.product-count strong').html(+$('.product-count strong').html() + 1);
					catalog.initMovers();
				});
		},

		/**
		 * Получает данные о продукте с сервера и заполняет ими поля в окне.
		 */
		editProduct: function (id) {
			$('#add-product-wrapper .product-text-inputs').hide();
			$('#add-product-wrapper .preloader').show();
			admin.ajaxRequest({
					mguniqueurl: "action/getProductData",
					id: id,
					lang: $('.select-lang').val(),
					storage: $('#add-product-wrapper .storageToView').val(),
				},
				catalog.fillFields()
			);
		},

		/**
		 * Удаляет продукт из БД сайта и таблицы в текущем разделе
		 */
		deleteProduct: function (id, imgFile, massDel, obj) {
			var confirmed = false;
			if (!massDel) {
				if (confirm(lang.DELETE + '?')) {
					confirmed = true;
				}
			} else {
				confirmed = true;
			}
			if (confirmed) {
				admin.ajaxRequest({
						mguniqueurl: "action/deleteProduct",
						id: id,
						imgFile: imgFile,
						msgImg: true
					},
					function (response) {
						if (!massDel) {
							admin.indication(response.status, response.msg);
						}
						if (response.status == 'error') return false;
						$(obj).parents('tr').detach();
						$('.product-count strong').html($('.product-count strong').html() - 1);
					}
				);
			}

		},


		/**
		 * Выполняет выбранную операцию со всеми отмеченными товарами
		 * operation - тип операции.
		 */
		runOperation: function (operation, skipConfirm) {
			if (typeof skipConfirm === "undefined" || skipConfirm === null) {
				skipConfirm = false;
			}
			var products_id = [];
			$('.product-tbody tr').each(function () {
				if ($(this).find('input[name=product-check]').prop('checked')) {
					products_id.push($(this).attr('id'));
				}
			});

			//Объект для передачи дополнительных данных, необходимых при выполнения действия
			var data = {};

			if ($('select#moveToCategorySelect').is(':visible')) {
				data.category_id = $('select#moveToCategorySelect').val();
			}

			if ($('#addToCategorySelect').parent().is(':visible')) {
				data.category_id = $('#addToCategorySelect').val();
			}

			if ($('select.product-operation').val() == "createvar") {
				data.toProdId = $('.addToIdVarCreate').val();
			}

			if (operation == 'createvar' && data.toProdId == '') {
				return false;
			}

			var notice = (operation.indexOf('changecur') != -1) ? lang.RUN_NOTICE : '';


			if (skipConfirm || confirm(lang.RUN_CONFIRM + notice)) {
				admin.ajaxRequest({
						mguniqueurl: "action/operationProduct",
						operation: operation,
						products_id: products_id,
						data: data
					},
					function (response) {
						if (response.status == 'error') {
							admin.indication(response.status, response.msg);
							return false;
						}
						if (response.data.clearfilter) {
							admin.show("catalog.php", "adminpage", "", catalog.init);
						} else {
							if (response.data.filecsv) {
								admin.indication(response.status, response.msg);
								setTimeout(function () {
									if (confirm(lang.CATALOG_MESSAGE_3 + response.data.filecsv + lang.CATALOG_MESSAGE_2)) {
										location.href = mgBaseDir + '/' + response.data.filecsvpath + '?q=' + Date.now();
									}
								}, 2000);
							}
							if (response.data.fileyml) {
								admin.indication(response.status, response.msg);
								setTimeout(function () {
									if (confirm(lang.CATALOG_MESSAGE_1 + response.data.fileyml + lang.CATALOG_MESSAGE_2)) {
										location.href = mgBaseDir + '/mg-admin/?yml=1&filename=' + response.data.fileymlpath;
									}
								}, 2000);
							}
							admin.refreshPanel();
						}
					}
				);
			}


		},

		uploaderCallbackVariant: function (file) {
			admin.ajaxRequest({
					mguniqueurl: "action/addImageUploader",
					imgType: file.mime,
					imgSize: file.size,
					imgName: file.name,
					imgUrl: file.url
				},

				function (response) {
					admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						var src = admin.SITE + '/uploads/' + response.data;
						catalog.lastVariant.parents('ul').find('.img-this-variant').find('img').attr('src', src).attr('alt', src).data('filename', src);
						catalog.lastVariant.parents('tr').find('.img-button').hide();
						catalog.lastVariant.parents('tr').find('.del-img-variant').show();
						catalog.updateImageVar();
					}
				});
		},
		// Открывает/закрывает блок с доп изображениями и заменяет текст на кнопке
		subImgPopup: function (action) {
			let popupBtn = $('.js-toggle-images')[0];
			let popup = $('.sub-images-popup')[0];
			let closeTxt = 'Свернуть';
			let openTxt = 'Раскрыть';
			if (action === 'open') {
				$(popup).slideDown();
				$(popupBtn).text(closeTxt);
			} else if (action === 'close') {
				$(popup).slideUp();
				$(popupBtn).text(openTxt);
			} else if (action === 'toggle') {
				$(popup).slideToggle();
				if ($(popupBtn).text().trim() === openTxt) {
					$(popupBtn).text(closeTxt);
				} else if ($(popupBtn).text().trim() === closeTxt) {
					$(popupBtn).text(openTxt);
				} else {
					console.log('Неправильное значение текста кнопки');
				}
			} else {
				console.log('Неправильный параметр функции');
			}
		},

		uploaderCallback: function (file) {
			admin.ajaxRequest({
					mguniqueurl: "action/addImageUploader",
					imgType: file.mime,
					imgSize: file.size,
					imgName: file.name,
					imgUrl: file.url
				},

				function (response) {
					admin.indication(response.status, response.msg);
					var mainurl = $('.main-image').find('img').attr('src').substr(-12).toLowerCase();

					if (response.status == 'success') {
						if (mainurl.indexOf('no-img.') >= 0) {
							var src = admin.SITE + '/uploads/' + response.data;
							$('.main-image').find('img').attr('src', src).attr('alt', response.data);

						}
						if (mainurl.indexOf('no-img.') < 0) {
							var src = admin.SITE + '/uploads/' + response.data;
							var ttle = response.data.replace('prodtmpimg/', '');
							var row = catalog.drawControlImage(src, true, '', '', '');
							$('.sub-images').append(row);
							$('.images-block img:last').attr('alt', response.data);
						}
						catalog.subImgPopup('open');
					}
				});
		},

		/**
		 * Формирует HTML для настроек изображений товаров
		 */
		drawControlImage: function (url, main, filename, title, alt) {
			var mainclass = "main-img-prod";
			if (main == true) {
				mainclass = 'small-img';
			}
			var seoModalHtml = ' \
				<div class="custom-popup seo-img-popup" style="display:none;margin-left: 9px;top: 9px;">\
					<div class="row">\
						<div class="large-12 columns">\
							<p class="seo-img-popup__desc">\
								Атрибуты ниже используются поисковиками, чтобы понять, что изображено на картинке. <br>Их значения могут быть одинаковыми.\
							</p>\
							<label for="image_title">\
								<span>\
								Title:\
								<span tooltip="Дополнительная информация о картинке. Текст, заключенный в этом атрибуте, появляется при наведении курсора на картинку."\
													flow="right">\
									<i class="fa fa-question-circle tip"\
													aria-hidden="true"></i>\
								</span>\
								</span>\
							</label>\
						</div>\
					</div>\
					<div class="row">\
						<div class="large-12 columns">\
							<input type="text" id="image_title" name="image_title" value="' + title + '">\
						</div>\
					 </div>\
					<div class="row">\
						<div class="large-12 columns">\
							<label for="image_alt">\
								<span>Alt:\
								<span tooltip="Текст, показывающийся вместо картинки, если она по какой-либо причине не загрузится."\
														flow="right">\
									<i class="fa fa-question-circle tip"\
									aria-hidden="true"></i>\
								 </span></span>\
							</label>\
						 </div>\
					</div>\
					<div class="row">\
						<div class="large-12 columns">\
						<input type="text" id="image_alt" name="image_alt" value="' + alt + '">\
					</div>\
				</div>\
				<div class="row">\
					<div class="large-12 columns">\
						<button tooltip="Закрыть без сохранения" \
								aria-label="Закрыть без сохранения" \
								flow="down" \
								class="link fl-left tooltip--center cancelPopup">\
								<span>' + lang.CANCEL + '</span>\
						</button>\
						<button tooltip="Сохранить введённые атрибуты" \
								aria-label="Сохранить введённые атрибуты" \
								flow="down" class="button success fl-right apply-seo-image tooltip--center">\
							<i class="fa fa-check" aria-hidden="true"></i> ' + lang.APPLY + '\
						</button>\
					</div>\
				</div>\
			</div>';
			//TODO[shevch] локали
			if (!main) {
				// Вёрстка модалки SEO настроек изображения

				return '<div class="img-holder" data-filename="' + filename + '" style="height: 180px;">\
					<button aria-label="' + lang.DELETE_IMAGE + '" \
							class="img-holder__icon remove-prod-img js-remove-prod-img tooltip--small tooltip--center"\
							tooltip="' + lang.DELETE_IMAGE + '"\
							flow="rightDown">\
							<span class="remove-prod-img__cross">+</span>\
					</button>\
					<button class="img-holder__icon img-holder__icon--left js-open-img-seo "\
							tooltip="SEO-настройки изображения"\
							flow="leftDown">\
							SEO\
					</button>\
				  <div class="img-dropzone">' + lang.CATALOG_DRAG_IMG + '</div>\
					<img class="main_product_image" src="' + url + '" title="' + filename + '" alt="' + filename + '">\
					<div class="custom-popup url-popup" style="display:none">\
					  <div class="row">\
						<div class="large-12 columns">\
						  <label for="input-img-link">' + lang.CATALOG_IMG_LINK + ':</label>\
						</div>\
					  </div>\
					  <div class="row">\
						<div class="large-12 columns">\
						  <input id="input-img-link" type="text" placeholder="https://site.ru/image.jpg">\
						</div>\
					  </div>\
					  <div class="row">\
						<div class="large-12 columns">\
						  <button class="link fl-left cancel-url" title="' + lang.CANCEL + '"><span>' + lang.CANCEL + '</span></button>\
						  <button class="button success fl-right apply-url" title="' + lang.APPLY + '"><i aria-hidden="true" class="fa fa-check"></i> ' + lang.APPLY + '</button>\
						</div>\
					  </div>\
					</div>\
				  </div>\
				  <div class="img-actions clearfix">\
					<div class="upload-form fl-left" style="position:fixed;top:-99999999px;">\
					  <form class="imageform" method="post" noengine="true" enctype="multipart/form-data">\
						<label class="button tip">\
						  <i class="fa fa-picture-o" aria-hidden="true"></i> Загрузить\
						  <input class="main_img_input" id="main_img_input" type="file" name="photoimg_multiple[]" multiple>\
						</label>\
					  </form>\
					</div>\
				  </div>\
				 <div >' + seoModalHtml + '</div>';
			} else {
				return '' +
					'' +
					'<div class="image-item parent" data-filename="' + filename + '">\
				  ' + seoModalHtml + '\
				  <div class="img-holder">\
					<img src="' + url + '" alt="' + filename + '">\
				  </div>\
				  <div class="img-actions clearfix">\
					<div class="upload-form fl-left">\
					</div>\
				  </div>\
				  <div class="img-action-hover">\
					<div class="elem">\
					  <button tooltip="' + lang.VALUE_DEFAULT + '" flow="leftUp" class="top set-main-image btn" aria-label="' + lang.VALUE_DEFAULT + '" >\
					   <i class="fa fa-check" aria-hidden="true"></i>\
					  </button>\
					</div>\
					<div class="elem">\
					  <button class="btn tip icon fl-right js-remove-prod-img tooltip--center tooltip--small" tooltip="' + lang.DELETE_IMAGE + '" flow="up" aria-label="' + lang.DELETE_IMAGE + '">\
						<span class="remove-prod-img__cross remove-prod-img__cross--subimg">+</span>\
					  </button>\
					</div>\
					<div class="elem">\
					  <form class="imageform" method="post" noengine="true" enctype="multipart/form-data">\
						<label class="btn tip icon file-upload-btn tooltip--small tooltip--center"" \
								tooltip="' + lang.UPLOAD_IMG + '" \
								flow="leftUp" \
								aria-label="' + lang.UPLOAD_IMG + '">\
						  <svg class="file-upload-icon">\
						  	<use class="symbol" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon--file-upload"></use>\
						  </svg>\
						  <input type="file" name="photoimg" style="display:none;">\
						</label>\
					  </form>\
					</div>\
					<div class="elem">\
					  <button tooltip="' + lang.SEO_SET + '" flow="up" class="top js-open-img-seo btn tooltip--small tooltip--center" aria-label="' + lang.SEO_SET + '">\
						SEO\
					  </button>\
					</div>\
					<div class="img-drag" flow="leftUp" tooltip="Зажмите левую кнопку мыши и перетащите для изменения порядка вывода">\
						<svg class="img-drag__icon">\
							<use class="symbol" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon--move"></use>\
						</svg>\
					</div> \
				  </div>\
				</div>\
				';
			}
		},

		/**
		 * Заполняет поля модального окна данными
		 */
		fillFields: function () {

			return function (response) {

				$('select[name=cat_id] option').each(function (index, element){
					let oldText = $(this).text();
					$(this).text(admin.htmlspecialchars_decode(oldText));
				});

				$('select.storageToView option').each(function (index, element){
					let oldText = $(this).text();
					$(this).text(admin.htmlspecialchars_decode(oldText));
				});

				var iso = response.data.currency_iso ? response.data.currency_iso : admin.CURRENCY_ISO;
				admin.isoHuman = catalog.getShortIso(iso);

				$('.size-map tbody').html('');
				catalog.modalUnit = response.data.category_unit;
				catalog.realCatUnit = response.data.real_category_unit;
				if (response.data.product_unit == undefined || response.data.product_unit == null) {
					response.data.product_unit = '';
				}
				catalog.realUnit = response.data.product_unit;
				if (catalog.modalUnit == null) {
					catalog.modalUnit = 'шт.';
				}
				$('.weightUnit-block').data('category', response.data.category_weightUnit);
				$('.weightUnit-block').data('product', response.data.weightUnit).data('last', response.data.weightUnit);
				$('#add-product-wrapper .weightUnit-block [name=weightUnit]').val(response.data.weightUnit);
				var imageDir = Math.floor(response.data.id / 100) + '00/' + response.data.id + '/';

				//лендинги
				if (typeof response.data.landingTemplate != 'undefined') {
					if (response.data.landingTemplate.length > 0) {
						$('select[name=landingIndividualTemplate]').val(response.data.landingTemplate);
					}
					$('select[name=landingIndividualTemplate]').trigger('change');
					$('[name=ytpText]').val(response.data.ytp);
					if (response.data.landingImage != '') {
						$('.landing-img-block img').attr('src', admin.SITE + '/uploads/' + response.data.landingImage);
						$('.landing-img-block img').attr('alt', response.data.landingImage);
						$('.remove-added-background').show();
					}
					if (response.data.landingSwitch.length > 0) {
						$('select[name=landingSwitch]').val(response.data.landingSwitch);
					}
					if (response.data.landingSwitch == 'true') {
						$("input[name=landingUseForm]").prop('checked', true);
					}
					$('.landingColors .color-list .color-scheme-landing').removeClass('active');
					if (response.data.landingColor != '') {
						$('.landingColors .color-list').find('[data-scheme=' + response.data.landingColor + ']').addClass('active');
					}
				}

				catalog.supportCkeditor = response.data.description;
				$('.product-desc-wrapper textarea[name=html_content]').text(response.data.description);
				$('.product-desc-wrapper textarea[name=short_html_content]').text(response.data.short_description);
				$('.product-text-inputs input').removeClass('error-input');
				$('.product-text-inputs input[name=title]').val(response.data.title);
				$('.product-text-inputs input[name=link_electro]').val(response.data.link_electro);
				$('.product-text-inputs input[name=multiplicity]').val(response.data.multiplicity);
				if (response.data.link_electro) {
					$('.section-catalog .del-link-electro').text(response.data.link_electro.substr(0, 50));
				}

				$('.section-catalog .del-link-electro').attr('title', response.data.link_electro);
				if (response.data.link_electro) {
					$('.section-catalog .del-link-electro').show();
					$('.section-catalog .add-link-electro').hide();
				}
				$('.product-text-inputs select[name=cat_id]').val(response.data.cat_id);
				$('.product-text-inputs input[name=url]').val(response.data.url);

				catalog.selectCategoryInside(response.data.inside_cat);
				catalog.cteateTableVariant(response.data.variants, imageDir);

				if (!response.data.variants) {
					$('.product-text-inputs input[name=code]').val(admin.htmlspecialchars_decode(response.data.code));
					$('.product-text-inputs input[name=price]').val(response.data.price);
					$('.product-text-inputs input[name=old_price]').val(response.data.old_price);
					$('.product-text-inputs input[name=weight]').val(response.data.weight);
					//превращаем минусовое значение в знак бесконечности
					var val = response.data.count;
					if ((val == '\u221E' || val === '' || parseFloat(val) < 0)) {
						val = '∞';
					}
					$('.product-text-inputs input[name=count]').val(val);

					// доп поля
					opf = JSON.parse($('#product-op-fields-data').val());
					$.each(opf, function (i, val) {
						$('.product-text-inputs input[name=opf_' + val.id + ']').val(response.data['opf_' + val.id]);
					});
				}

				var rowMain = '';
				var rows = '';
				var noImgText = '<span class="sub-images__empty">Дополнительные изображения не заданы</span>'
				response.data.images_product.forEach(
					function (element, index, array) {
						var title = response.data.images_title[index] ? response.data.images_title[index] : '';
						var alt = response.data.images_alt[index] ? response.data.images_alt[index] : '';
						var src = admin.SITE + mgNoImageStub;
						if (element) {
							var src = element;
						}

						if (index != 0) {
							rows += catalog.drawControlImage(src, true, element, title, alt);
						} else {
							rowMain = catalog.drawControlImage(src, false, element, title, alt);
						}

					}
				);

				$('.main-image').html(rowMain);
				$('.sub-images').html((rows) ? rows : noImgText);

				// Удаление изображения товара
				$('body').on('click', '.js-remove-prod-img', function () {
					if ($('.image-item.parent').length == 0) {
						$('.sub-images').html(noImgText);
					}
				});

				$('.main-img-prod .main-image').hide();
				$('textarea[name=html_content]').val(response.data.description);
				$('textarea[name=short_html_content]').val(response.data.short_description);
				$('#add-product-wrapper input[name=meta_title]').val(response.data.meta_title);
				$('#add-product-wrapper input[name=meta_keywords]').val(response.data.meta_keywords);
				$('#add-product-wrapper textarea[name=meta_desc]').val(response.data.meta_desc);
				$('.yml-wrapper input[name=yml_sales_notes]').val(response.data.yml_sales_notes);
				catalog.drawRelatedProduct(response.data.relatedArr);
				catalog.addrelatedCategory(response.data.relatedCat);
				$('.save-button').attr('id', response.data.id);
				$('.save-button').data('recommend', response.data.recommend);
				$('.save-button').data('activity', response.data.activity);
				$('.save-button').data('new', response.data.new);
				$('.b-modal_close').attr('item-id', response.data.id);
				$('.js-remove-prod-img').attr('id', response.data.id);
				$('.userField').html('');
				$('.addedProperty').html('');

				$('.shortDesc').hide();
				$('.add-short-desc').show();

				try {
					$('.js-count-meta--title').text($('#add-product-wrapper [name=meta_title]').val().length);
					$('.js-count-meta--desc').text($('#add-product-wrapper textarea[name=meta_desc]').val().length);
					$('.js-count-meta--keyw').text($('#add-product-wrapper [name=meta_keywords]').val().length);
				} catch (e) {

					$('.js-count-meta--title').text('0');
					$('.js-count-meta--desc').text('0');
					$('.js-count-meta--keyw').text('0');
				}
				var userField = $('.userField');
				userProperty.createUserFields(userField, response.data.prodData.thisUserFields, response.data.prodData.allProperty, response.data.prodData.propertyGroup);

				$('.userField tr td .value').each(function () {
					var value = $(this).text();
					if (value) {
						$(this).text(admin.htmlspecialchars(value));
					}
				});

				userProperty.createSizeMap(response.data.prodData.allProperty);

				var iso = response.data.currency_iso ? response.data.currency_iso : admin.CURRENCY_ISO;

				$('#add-product-wrapper .btn-selected-currency').text(catalog.getShortIso(iso));

				$('#add-product-wrapper select[name=currency_iso] option[value=' + JSON.stringify(iso) + ']').prop('selected', 'selected');

				$('.variant-row').each(function () {
					if ($(this).data('color') != '') {
						color = '[data-color=' + $(this).data('color') + ']';
					} else {
						color = '';
					}
					if ($(this).data('size') != '') {
						size = '[data-size=' + $(this).data('size') + ']';
					} else {
						size = '';
					}

					switch ($('.size-map').data('type')) {
						case 'only-color':
							if (color != '') $('.size-map .checkbox input' + color).prop('checked', true);
							break;
						case 'only-size':
							if (size != '') $('.size-map .checkbox input' + size).prop('checked', true);
							break;
						case 'only-all':
							if (color != '' && size != '') $('.size-map .checkbox input' + color + size).prop('checked', true);
							if (color == '' && size != '') $('.size-map .checkbox input[data-color=none]' + size).prop('checked', true);
							if (color != '' && size == '') $('.size-map .checkbox input' + color + '[data-size=none]').prop('checked', true);
							break;
					}

				});

				// Проверка на наличии поля в возвращаемом результате, для вывода предупреждения,
				// если этот товар является комплектом товаров, созданным в плагине "Комплект товаров"
				if (response.data.plugin_message) {
					$('#add-product-wrapper .add-product-table-icon').append(response.data.plugin_message);
				}
				//$('textarea[name=html_content]').ckeditor(function() {});

				$('.sub-images').sortable({
					sort: function (e) {
						var Y = e.pageY; // положения по оси Y
						var X = e.pageX; // положения по оси Y
						$('.ui-sortable-helper').offset({top: (Y - 50)});
						$('.ui-sortable-helper').offset({left: (X - 60)});
						$(this).find('.img-action-hover').css('opacity', '0');
					},
					stop: function () {
						$(this).find('.img-action-hover').attr('style', '');
					}
				});

				$('.variant-table input').each(function () {
					$(this).attr('value', $(this).val());
				});
				catalog.saveVarTable = $('.variant-table').html();
				catalog.saveTypeGroupVar = 'color';

				if (response.data.variants && response.data.variants.length > 1 && $('.size-map table tbody').html() != '' && $('.size-map table tbody').html() != undefined) {

					$('.size-map tbody .checkbox input').each(function () {
						if ($(this).prop('checked')) {
							if ($(this).data('size') == 'none' || $(this).data('color') == 'none') {
								$('.select-typeGroupVar select').val('default');
								catalog.saveTypeGroupVar = 'default';
							}
						}
					});

					catalog.buildGroupVarTable();

					if (catalog.saveTypeGroupVar == 'default') {
						$('.variant-table th:eq(0)').hide();
						$('.variant-table tr').each(function () {
							$(this).find('td:eq(0)').hide();
						});
					} else {
						$('.variant-table th:eq(0)').show();
						$('.variant-table tr').each(function () {
							$(this).find('td:eq(0)').show();
						});
					}
				} else {
					$('.typeGroupVar').hide();
					$('.variant-table-wrapper .add-position').show();
					$('.variant-row .fa-arrows').show();
				}

				if ($('.size-map table tbody').html() != '' && $('.size-map table tbody').html() != undefined) {
					$('.variant-table-wrapper .add-position').hide();
				} else {
					$('.variant-table-wrapper .add-position').show();
				}

				if (($('.storageToView').html() != undefined) && ($('.storageToView').val() == 'all')) {
					$('.variant-table [name=count],.tmpl-count').prop('disabled', true)
						.parent().attr({
						'tooltip' : 'Выберите склад, в котором вы хотите изменить количество',
						'flow' : 'left'
					});
				} else {
					$('.variant-table [name=count],.tmpl-count').prop('disabled', false)
						.parent().removeAttr('tooltip');
				}

				$('#add-product-wrapper .product-text-inputs').show();
				$('#add-product-wrapper .preloader').hide();

				if ($('#productCategorySelect').val() == 0) {
					$('.addedProperty').html('<span class="addedProperty__empty">Для указания характеристик выберите категорию</span>');
					$('.add-property-field').hide();
				} else {
					$('.add-property-field').show();
				}
				$('#add-product-wrapper select[name=currency_iso]').val(iso);

				admin.reloadComboBoxes();
				admin.blockTime = 0;
				admin.setAndCheckBlock('#add-product-wrapper', 'product');
			}
		},

		buildGroupVarTable: function () {
			if ($('.size-map').data('type') == '') {
				$('.variant-table tr').show();
				$('.variant-table .group-row').detach();
				$('.variant-table th:eq(0)').hide();
				$('.variant-table tr').each(function () {
					$(this).find('td:eq(0)').hide();
				});

				$('.left-line').replaceWith('<i class="fa fa-arrows"></i>');
				$('.hor-line').detach();
			} else {
				if ($('.variant-table .variant-row').length > 1) {
					$('.variant-table th:eq(0)').show();
					$('.variant-table tr').each(function () {
						$(this).find('td:eq(0)').show();
					});
				} else {
					$('.variant-table th:eq(0)').hide();
					$('.variant-table tr').each(function () {
						$(this).find('td:eq(0)').hide();
					});
				}
			}

			if ($('.variant-row:eq(0)').data('color') != '' && $('.variant-row:eq(0)').data('size') != '') {
				if ($('#sizeCheck-' + $('.variant-row:eq(0)').data('color') + '-' + $('.variant-row:eq(0)').data('size')).length == 0) {
					$('.variant-table').hide();
					$('.add-position, .set-size-map').hide();
					$('.category-change-alert-size-map').detach();
					$('.variant-table-wrapper').append('<div class="category-change-alert-size-map" style="text-align:center;">\
			В выбранной категории отсутствуют характеристики размерной сетки, необходимые для существующих вариантов товара<br>\
			<a role="button" href="javascript:void(0);" class="link back-category"><span>Вернуть категорию</span></a>&nbsp;&nbsp;&nbsp;\
			<a role="button" href="javascript:void(0);" class="link drop-restart-variant"><span>Заполнить варианты заново</span></a></div>');
					$('.addedProperty').html('');
					return false;
				} else {
					$('.variant-table').show();
					$('.category-change-alert-size-map').detach();
				}
			}

			catalog.saveCategory = $('#add-product-wrapper [name=cat_id]').val();

			$('.variant-table').show();
			$('.category-change-alert-size-map').detach();

			if ($('.variant-row').length <= 1) {
				catalog.saveTypeGroupVar = 'default';
			} else {
				if ($('.variant-row:eq(0)').data('color') == '' && $('.variant-row:eq(0)').data('size') == '') {
					$('.variant-table tr th:first').show();
					$('.variant-table tr').each(function () {
						$(this).find('td:first').show();
					});
				}
			}

			var storage = $('#add-product-wrapper .storageToView').val();
			if ($('.size-map').data('type') != 'only-all') {
				$('.typeGroupVar').hide();
				return false;
			}
			setTimeout(function () {
				switch ($('.size-map').data('type')) {
					case 'only-all':
						$('.typeGroupVar').show();
						$('.typeGroupVar option').show();
						$('.variant-table-wrapper .add-position').hide();
						$('.variant-row .fa-arrows').hide();
						$('.variant-row .fa-arrows').replaceWith('<div class="left-line"></div><div class="hor-line"></div>');
						break;
					case 'only-size':
						$('.typeGroupVar').show();
						$('.typeGroupVar option').show();
						$('.typeGroupVar option[value=color]').hide();
						if (catalog.saveTypeGroupVar == 'color') catalog.saveTypeGroupVar = 'size';
						$('.variant-table-wrapper .add-position').hide();
						$('.variant-row .fa-arrows').hide();
						$('.variant-row .fa-arrows').replaceWith('<div class="left-line"></div><div class="hor-line"></div>');
						break;
					case 'only-color':
						$('.typeGroupVar').show();
						$('.typeGroupVar option').show();
						$('.typeGroupVar option[value=size]').hide();
						if (catalog.saveTypeGroupVar == 'size') catalog.saveTypeGroupVar = 'color';
						$('.variant-table-wrapper .add-position').hide();
						$('.variant-row .fa-arrows').hide();
						$('.variant-row .fa-arrows').replaceWith('<div class="left-line"></div><div class="hor-line"></div>');
						break;
					default:
						catalog.saveTypeGroupVar = 'default';
						$('.typeGroupVar').hide();
						$('.variant-table-wrapper .add-position').show();
						$('.variant-row .fa-arrows').show();
						break;
				}

				// для отключения логики
				vAll = true;
				$('.variant-row').each(function () {
					if ($(this).data('color') == '' || $(this).data('size') == '') {
						vAll = false;
						return false;
					}
				});
				if (vAll) {
					$('.typeGroupVar').show();
				} else {
					$('.typeGroupVar').hide();
				}

				$('.select-typeGroupVar').hide();

			}, 0);

			$('.variant-table').html(catalog.saveVarTable);
			if (storage == 'all') {
				$('.variant-row [name=count]').prop('disabled', true);
			} else {
				$('.variant-row [name=count]').prop('disabled', false);
			}
			tmplType = catalog.saveTypeGroupVar;
			if (tmplType == 'default') {
				$('.left-line, .hor-line').hide();
			} else {
				$('.left-line, .hor-line').show();
			}
			$('.variant-table .variant-row').css('background', 'none');
			$('.variant-table .variant-row').show();
			if (tmplType == 'default') {
				return false;
			}
			var variantTableSort = {};
			var counter = 0;
			// группы
			$('.variant-table tr.variant-row').each(function () {
				if ($(this).data(tmplType) != undefined) {
					counter = 0;
					for (var key in variantTableSort[$(this).data(tmplType)]) {
						counter++;
					}
					if (variantTableSort[$(this).data(tmplType)] == undefined) variantTableSort[$(this).data(tmplType)] = {};
					variantTableSort[$(this).data(tmplType)][counter + 1] = $(this).clone();
				}
			});
			// console.log(variantTableSort);,
			$('.variant-table tr').not('.text-left').detach();
			var tmp = $('.variant-table .text-left').parent().html();
			if ($('.variant-table tbody').html() == undefined) {
				appendTbody = true;
			} else {
				appendTbody = false;
			}
			$('.variant-table').html('');
			$('.variant-table').html(tmp);
			if (appendTbody) $('.variant-table').append('<tbody></tbody>');
			$('#add-product-wrapper .storageToView option[value=' + storage + ']').prop('selected', 'selected');

			if (tmplType == 'color') {
				var varTitle = lang.COLOR;
			} else {
				var varTitle = lang.SIZE;
			}

			for (var key in variantTableSort) {
				tmp = '';
				// доп поля
				opf = JSON.parse($('#product-op-fields-data').val());
				$.each(opf, function (i, val) {
					tmp += '<td><input class="tmpl-opf_' + val.id + '" type="text" value="' + admin.htmlspecialchars(variantTableSort[key][1].find('[name=opf_' + val.id + ']').val()) + '"></td>';
				});
				let tmpColorName = $('.size-map .' + tmplType + '-' + key).parent().find('.' + tmplType).val();
				$('.variant-table tbody').append('\
				  <tr data-id="' + key + '" data-type="' + tmplType + '" class="group-row">\
					<td class="showGroupVar__td" style="text-align: center;"><button tooltip="Открыть/закрыть варианты" flow="leftUp" style="background-color:' + $('.size-map .color-' + key).val() + ';" aria-label="Открыть/закрыть варианты" class="showGroupVar" tabindex="0"><i aria-hidden="true" class="fa fa-chevron-down"></i></button></td>\
					<td class="showGroupVar__td" >\
					  <span class="tmplPreview">' + varTitle + ': ' + (tmpColorName == undefined? catalog.undefinedColorName : tmpColorName) + '</span>\
					  <button class="tmplApply button" flow="down" tooltip="' + lang.APPLY_TMPL_VAR_GROUP + '">' + lang.APPLY_TO_VAR_GROUP + '</button>\
					</td>\
					<td class="tmplPreview">\
					  <input class="tmpl-code" type="text" value="' + admin.htmlspecialchars(variantTableSort[key][1].find('[name=code]').val()) + '">\
					</td>\
					<td><input class="tmpl-price" type="text" value="' + admin.htmlspecialchars(variantTableSort[key][1].find('[name=price]').val()) + '"></td>\
					<td><input class="tmpl-old_price" type="text" value="' + admin.htmlspecialchars(variantTableSort[key][1].find('[name=old_price]').val()) + '"></td>\
					<td><input class="tmpl-weight" type="text" value="' + admin.htmlspecialchars(variantTableSort[key][1].find('[name=weight]').val()) + '"></td>\
					<td><input class="tmpl-count" type="text" value="' + admin.htmlspecialchars(variantTableSort[key][1].find('[name=count]').val()) + '"></td>\
					' + tmp + '\
					<td class="tmpl-actions">\
					  ' + variantTableSort[key][1].find('.action-list').parent().html() + '\
					</td>\
				  </tr>');
				// console.log(key);
				for (var keyIn in variantTableSort[key]) {
					// console.log(key+'/'+keyIn);
					$('.variant-table tbody').append(variantTableSort[key][keyIn]);
				}
			}
			// $('.variant-table .variant-row').css('background', '#fff');
			$('.variant-table .variant-row').hide();
			$('.typeGroupVar select option[value=' + tmplType + ']').prop('selected', 'selected');
			$('.variant-table .group-row .del-variant').addClass('deleteGroupVar').removeClass('del-variant');

			$('.typeGroupVar').closest('th').css('width', '150px').css('position', 'relative');
		},

		/**
		 * Чистит все поля модального окна
		 */
		clearFields: function () {
			if ($("#admin-curr-check").data('db') != $("#admin-curr-check").data('eng') || admin.CURRENCY_ISO != $("#admin-curr-check").data('db') || admin.CURRENCY_ISO != $("#admin-curr-check").data('eng')) {
				admin.resetAdminCurrency();
			}
			$('.select-lang').val('default');
			$("#related-draggable").sortable({
				revert: true,
				handle: ".move-handle"
			});
			$("#related-cat-draggable").sortable({
				revert: true,
				handle: ".move-handle"
			});
			catalog.initSupportCkeditor = true;
			catalog.modalUnit = lang.UNIT;
			$('select[name=landingSwitch]').val(-1);
			$('[name=ytpText]').val('');
			$('.remove-added-background').click();
			$('select[name=landingIndividualTemplate]').val('noLandingTemplate');
			$('select[name=landingIndividualTemplate]').trigger('change');

			$('.product-text-inputs input[name=title]').val('');
			$('.product-text-inputs input[name=link_electro]').val('');
			$('.product-text-inputs input[name=url]').val('');
			$('.product-text-inputs input[name=code]').val('');
			$('.product-text-inputs input[name=price]').val('');
			$('.product-text-inputs input[name=old_price]').val('');
			$('.product-text-inputs input[name=count]').val('');
			$('#add-product-wrapper select[name=inside_cat]').val([]);
			$('.product-text-inputs input[name=multiplicity]').val('1');
			catalog.selectedStorage = 'all';
			catalog.selectCategoryInside('');

			var catId = $('.filter-container select[name=cat_id]').val();
			if (catId == 'null' || catId === undefined) {
				catId = 0;
			}

			$('select[name=inside_cat]').attr('size', 4);
			$('.full-size-select-cat').removeClass('opened-select-cat').addClass('closed-select-cat');
			$('.full-size-select-cat').children('span').text(lang.PROD_OPEN_CAT);


			$('.product-text-inputs select[name=cat_id]').val(catId);

			// $('.prod-gallery').html('<div class="small-img-wrapper"></div>');
			$('textarea[name=html_content]').val('');
			$('textarea[name=short_html_content]').val('');
			$('#add-product-wrapper input[name=meta_title]').val('');
			$('#add-product-wrapper input[name=meta_keywords]').val('');
			$('#add-product-wrapper textarea[name=meta_desc]').val('');
			$('.yml-wrapper input[name=yml_sales_notes]').val(''),
				$('.product-text-inputs .variant-table').html('');
			$('.added-related-product-block').html('').css('width', "800px");
			$('.added-related-category-block').html('').css('width', "800px");
			$('.userField').html('');
			$('.symbol-count').text('0');
			$('.save-button').attr('id', '');
			$('.save-button').data('recommend', '0');
			$('.save-button').data('activity', '1');
			$('.save-button').data('new', '0');
			$('.select-product-block').hide();
			catalog.cteateTableVariant(null);
			catalog.deleteImage = '';

			$('.del-link-electro').hide();
			$('.add-link-electro').show();
			// Стираем все ошибки предыдущего окна если они были.
			$('.errorField').css('display', 'none');

			$('#add-product-wrapper .select-currency-block').hide();

			var currency_iso = admin.getSettingFromDB('currencyShopIso');
			var short = catalog.getShortIso(currency_iso);
			$('#add-product-wrapper .btn-selected-currency').text(short);
			$('#add-product-wrapper select[name=currency_iso] option[value=' + currency_iso + ']').prop('selected', 'selected');
			$('.error-input').removeClass('error-input');

			catalog.supportCkeditor = '';
			$('.addedProperty').html('<span class="addedProperty__empty">Для указания характеристик выберите категорию</span>');

			$('#add-product-wrapper .custom-popup').css('display', 'none');
			$('#add-product-wrapper .product-desc-field').css('display', 'none');
			$('#add-product-wrapper .add-category').removeClass('open');

			$('.set-size-map').hide();
			$('.size-map').hide();

			$('.sub-images').html('');

			$('.weightUnit-block').data('category', 'kg').data('product', 'kg').data('last', 'kg');
			$('#add-product-wrapper .weightUnit-block [name=weightUnit]').val('kg');
			$('#add-product-wrapper .btn-select-weightUnit').text($('#add-product-wrapper .weightUnit-block [name=weightUnit] option[value=kg]').data('short'));

			$('.js-inside-category').hide();

			admin.reloadComboBoxes();
		},


		/**
		 * Добавляет изображение продукта
		 */
		addImageToProduct: function (img_container) {
			var currentImg = '';
			img_container.find('.img-loader').show();

			if (img_container.find('.prev-img img').length > 0) {
				currentImg = img_container.find('.prev-img img').attr('alt');
			} else {
				currentImg = img_container.find('img').attr('data-filename');
			}

			//Пишем в поле deleteImage имена изображений, которые необходимо будет удалить при сохранении
			// if(catalog.deleteImage) {
			//   catalog.deleteImage += '|'+currentImg;
			// } else {
			//   catalog.deleteImage = currentImg;
			// }

			// отправка картинки на сервер
			img_container.find('.imageform').ajaxForm({
				type: "POST",
				url: "ajax",
				data: {
					mguniqueurl: "action/addImage"
				},
				cache: false,
				dataType: 'json',
				success: function (response) {
					admin.indication(response.status, response.msg);
					if (response.status != 'error') {
						var src = admin.SITE + '/uploads/' + response.data.img;
						catalog.tmpImage2Del += '|' + response.data.img;
						img_container.find('img').attr('src', src);
						img_container.find('img').attr('alt', response.data.img);
					} else {
						var src = admin.SITE + mgNoImageStub;
						img_container.find('img').attr('src', src);
						img_container.find('img').attr('alt', response.data.img);
					}
					img_container.find('.img-loader').hide();
				}
			}).submit();
		},

		/**
		 *  собирает названия файлов всех картинок чтобы сохранить их в БД в поле image_url
		 */
		createFieldImgUrl: function () {
			var image_url = "";
			$('.images-block img').each(function () {
				if ($(this).attr('alt') && $(this).attr('alt') != 'undefined') {
					image_url += $(this).attr('alt') + '|';
				}
			});

			if (image_url) {
				image_url = image_url.slice(0, -1);
			}

			return image_url;


		},

		/**
		 *  собирает все заголовки для картинок, чтобы сохранить их в БД в поле image_title
		 */
		createFieldImgTitle: function () {
			var image_title = "";
			$('.images-block img').each(function () {
				if ($(this).attr('alt') && $(this).attr('alt') != 'undefined') {
					var title = $(this).parents('.parent').find('input[name=image_title]').val();
					title = title.replace('|', '');
					image_title += title + '|';
				}
			});

			if (image_title) {
				image_title = image_title.slice(0, -1);
			}

			return image_title;
		},

		/**
		 *  собирает все описания для картинок, чтобы сохранить их в БД в поле image_alt
		 */
		createFieldImgAlt: function () {
			var image_alt = "";
			$('.images-block img').each(function () {
				if ($(this).attr('alt') && $(this).attr('alt') != 'undefined') {
					var title = $(this).parents('.parent').find('input[name=image_alt]').val();
					title = title.replace('|', '');
					image_alt += title + '|';
				}
			});

			if (image_alt) {
				image_alt = image_alt.slice(0, -1);
			}

			return image_alt;
		},

		/**
		 * Помещает  выбранную основной картинку в начало ленты
		 * removemain = true - была удалена главная и требуется поднять из лены первую на место главной
		 */
		upMainImg: function (obj, removemain) {
			if (obj.find('img').attr('src') == admin.SITE + mgNoImageStub) {
				return false;
			}
			var newMain = {
				src: obj.find('img').attr('src'),
				alt: obj.find('img').attr('alt'),
				imgTitle: obj.find('[name=image_title]').val(),
				imgAlt: obj.find('[name=image_alt]').val()
			};


			var main = $('.main-image');
			var sub = obj;

			sub.find('img').attr('src', main.find('img').attr('src'));
			sub.find('img').attr('alt', main.find('img').attr('alt'));
			sub.find('[name=image_title]').val(main.find('[name=image_title]').val());
			sub.find('[name=image_alt]').val(main.find('[name=image_alt]').val());

			main.find('img').attr('src', newMain.src);
			main.find('img').attr('alt', newMain.alt);
			main.find('[name=image_title]').val(newMain.imgTitle);
			main.find('[name=image_alt]').val(newMain.imgAlt);

			if (removemain) {
				obj.detach();
			}
		},

		/**
		 * Удаляет изображение продукта
		 */
		delImageProduct: function (id, img_container) {
			var imgFile = img_container.find('img').attr('src');

			if (confirm(lang.DELETE_IMAGE + '?')) {
				// catalog.deleteImage += "|"+imgFile;
				if (catalog.deleteImage) {
					catalog.deleteImage += '|' + imgFile;
				} else {
					catalog.deleteImage = imgFile;
				}

				// удаляем текущий блок управления картинкой
				if ($('.images-block img').length > 1) {
					if (img_container.hasClass('main-image')) {
						catalog.upMainImg($('.sub-images .image-item:eq(0)'), true);
					} else {
						img_container.remove();
					}
				} else {
					// если блок единственный, то просто заменяем в нем картнку на заглушку
					var src = admin.SITE + mgNoImageStub;
					img_container.find('img').attr('src', src).attr('alt', '');
					img_container.data('filename', '');
				}
				$('#tiptip_holder').hide();
				// admin.ajaxRequest({
				//   mguniqueurl:"action/deleteImageProduct",
				//   imgFile: imgFile,
				//   id: id,
				// },
				// function(response) {
				//   admin.indication(response.status, response.msg);
				// });
			}
		},

		/**
		 * Поиск товаров
		 */
		getSearch: function (keyword, forcedPage, showIndication) {
			if (forcedPage === undefined) {
				forcedPage = false;
			}
			keyword = $.trim(keyword);
			if (keyword == lang.FIND + "...") {
				keyword = '';
			}
			if (!keyword) {
				admin.refreshPanel();
				admin.indication('error', lang.CATALOG_MESSAGE_4);
				return false
			};

			admin.ajaxRequest({
					mguniqueurl: "action/searchProduct",
					keyword: keyword,
					mode: 'groupBy',
					forcedPage: forcedPage,
					returnHtml: 1,
				},
				function (response) {
					if (showIndication === undefined) {
						admin.indication(response.status, response.msg);
					}
					$('.product-tbody').html(response.data.html);

					// Если в результате поиска ничего не найдено
					if (response.data.html.length == 0) {
						var row = "<tr><td class='no-results' colspan='" + $('.product-table th').length + "'>" + lang.SEARCH_PROD_NONE + "</td></tr>"
						$('.product-tbody').append(row);
					} else {
						catalog.initMovers();
					}
					$('.mg-pager').replaceWith(response.data.pager);
					$('.mg-pager a').attr('href', 'javascript:void(0);');
					$('.mg-pager .linkPage').addClass('linkPageCatalog').removeClass('linkPage');
				}
			);
		},


		//  Получает данные из формы фильтров и перезагружает страницу
		getProductByFilter: function () {
			var request = $("form[name=filter]").formSerialize();
			var insideCat = $('input[name="insideCat"]').prop('checked');
			admin.show("catalog.php", "adminpage", request + '&insideCat=' + insideCat + '&applyFilter=1&displayFilter=1', catalog.init);
			return false;
		},

		// Устанавливает статус продукта - рекомендуемый
		recomendProduct: function (id, val) {
			admin.ajaxRequest({
					mguniqueurl: "action/recomendProduct",
					id: id,
					recommend: val,
				},
				function (response) {
					admin.indication(response.status, response.msg);
				}
			);
		},

		// Устанавливает статус - видимый
		visibleProduct: function (id, val) {
			admin.ajaxRequest({
					mguniqueurl: "action/visibleProduct",
					id: id,
					activity: val,
				},
				function (response) {
					admin.indication(response.status, response.msg);
				}
			);
		},

		// вывод в новинках
		newProduct: function (id, val) {
			admin.ajaxRequest({
					mguniqueurl: "action/newProduct",
					id: id,
					new: val,
				},
				function (response) {
					admin.indication(response.status, response.msg);
				});
		},
		//Кнопка выбора валюты товара

		// Добавляет строку в таблицу вариантов
		cteateTableVariant: function (variants, imageDir) {
			var chooseCurrBtn = '<button aria-label="Выбрать валюту" tooltip="Выбрать валюту" flow="down" class="btn-selected-currency link tooltip--small tooltip--center"></button>';

			admin.ajaxRequest({
					mguniqueurl: "action/nextIdProduct",
				},
				function (response) {
					if (!$('.product-text-inputs .variant-table .default-code').val()) {
						var id = response.data.id;
						var prefix = response.data.prefix_code ? response.data.prefix_code : 'CN';
						$('.product-text-inputs .variant-table .default-code').val(prefix + id);
					}
				});
			if (catalog.realUnit == undefined || catalog.realUnit == 'undefined') {
				catalog.realUnit = catalog.modalUnit;
			}
			if (!catalog.modalUnit) {
				catalog.modalUnit = lang.EMPTY_UNIT;
			}

			var unitHtml = '\
		  <button style="display: inline-block;" aria-label="Выбрать единицу измерения" tooltip="Выбрать единицу измерения" flow="down" class="btn-selected-unit link tooltip--center" realUnit="' + catalog.realUnit + '" style="width:100%;text-align:left">' + catalog.modalUnit + '</button>';
			$('.input-unit-block .unit-input').val(catalog.realUnit);
			var weightUnitText = $('#add-product-wrapper .weightUnit-block [name=weightUnit] option:selected').data('short');
			var weightUnitHtml = '<button style="display: inline-block;" aria-label="Выбрать единицу измерения веса" tooltip="Выбрать единицу измерения веса" flow="down" class="btn-select-weightUnit link tooltip--center" style="width:100%;text-align:left">' + weightUnitText + '</button>';

			// строим первую строку заголовков
			$('.product-text-inputs .variant-table').html('');
			if (variants) {
				var position = '\
		<tr class="text-left">\
		  <th class="hide-content"></th>\
		  <th style="width:150px;position:relative;" class="varTitle hide-content">' + lang.NAME_VARIANT + '\
			<span class="typeGroupVar">\
			  (<a role="button" tooltip="По цвету или размеру" flow="down" aria-label="Группировать по цвету или размеру" href="javascript:void(0);" class="btn-selected-typeGroupVar tooltip--small">' + lang.GROUP_VAR + '</a>)\
			</span></th>\
		  <th>' + lang.CODE_PRODUCT + '</th>\
		  <th class="table-tr-small nowrap">' + lang.PRICE_PRODUCT + ' (' + chooseCurrBtn + ')</th>\
							  <th class="table-tr-small">' + lang.OLD_PRICE_PRODUCT + '</th>\
		  <th class="table-tr-small">' + lang.WEIGHT + ' ('+weightUnitHtml+')</th>\
		  <th class="table-tr-small">' + unitHtml + '</th>';
				// доп поля
				opf = JSON.parse($('#product-op-fields-data').val());
				$.each(opf, function (i, val) {
					position += '<th>' + admin.htmlspecialchars(admin.htmlspecialchars_decode(val.name)) + '</th>';
				});
				//
				position += '<th class="hide-content"></th></tr>';
				$('.variant-table').append(position);
				// console.log(variants);
				// заполняем вариантами продукта
				variants.forEach(function (variant, index, array) {
					var src = admin.SITE + mgNoImageStub;
					if (variant.image) {
						src = variant.image;
					}

					if (variant.count < 0) {
						variant.count = '∞'
					}
					;
					var position = '\
		  <tr data-id="' + variant.id + '" class="variant-row" data-color="' + variant.color + '" data-size="' + variant.size + '">\
			<td class="hide-content"><i class="fa fa-arrows"></i></td>\
			<td class="hide-content">\
			  <label><input type="text" name="title_variant" value="' + variant.title_variant.replace(/"/g, "&quot;") + '" class="product-name-input" title="' + lang.NAME_PRODUCT + '" ><div class="errorField" style="display:none;">' + lang.NAME_PRODUCT + '</div></label>\
			</td>\
			<td style="width:100px;">\
			  <label><input type="text" name="code" value="' + variant.code + '" class="product-name-input" title="' + lang.T_TIP_CODE_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_EMPTY + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="price" value="' + variant.price + '" class="product-name-input" title="' + lang.T_TIP_PRICE_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="old_price" value="' + variant.old_price + '" class="product-name-input" title="' + lang.T_TIP_OLD_PRICE + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="weight" value="' + variant.weight + '" class="product-name-input" title="' + lang.T_TIP_WEIGHT_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="count" value="' + variant.count + '" class="product-name-input" aria-label="' + lang.T_TIP_COUNT_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>';
					// доп поля
					opf = JSON.parse($('#product-op-fields-data').val());
					$.each(opf, function (i, val) {
						position += '<td><label><input type="text" name="opf_' + val.id + '" value="' + variant['opf_' + val.id] + '" class="product-name-input"></label></td>';
					});
					position += '<td class="tmpl-actions hide-content actions">\
			<div class="variant-dnd"></div>\
			<ul class="action-list">\
			  <div class="img-this-variant" style="display:none;">\
				<img src="' + src + '" style="width:50px; min-height:100%; position: absolute; bottom: 0;" data-filename="' + variant.image + '">\
			  </div>\
			  <li>\
				<form method="post" noengine="true" enctype="multipart/form-data" class="img-button catalog_uploads_container_variants_wrapper" style="display:' + (variant.image.indexOf('no-img') == -1 ? 'none' : 'inline-block') + '">\
				  <span class="add-img-clone"></span>\
				  <label>\
					<a role="button" class="tooltip--center tooltip--small" aria-label="' + lang.UPLOAD_IMG_VARIANT + '" tooltip="' + lang.UPLOAD_IMG_VARIANT + '" flow="left" ><i class="fa fa-picture-o" aria-hidden="true"></i></a>\
					<input type="file" style="display:none;" name="photoimg" class="add-img-var img-variant " title="' + lang.UPLOAD_IMG_VARIANT + '">\
				  </label>\
				  <div class="additional_uploads_container_variants">\
					<div class="from_pc">' + lang.CATALOG_DOWNLOAD + '</div>\
					<div class="js-open-url-popup">' +
						lang.CATALOG_DOWNLOAD_LINK +

						'<div class="custom-popup url-popup" style="display:none;">\
							<div class="row">\
								<div class="large-12 columns">\
									<label>' + lang.CATALOG_IMG_LINK + ':</label>\
								</div>\
							</div>\
							<div class="row">\
								<div class="large-12 columns">\
									<input type="text" name="variant_url" placeholder="https://site.ru/image.jpg">\
								</div>\
							</div>\
							<div class="row">\
								<div class="large-12 columns">\
									<a href="javascript:void(0);" class="link fl-left cancel-url" title="' + lang.CANCEL + '">' + lang.CANCEL + '</a>\
									<a class="button success fl-right apply-url" href="javascript:void(0);"><i class="fa fa-check"></i> ' + lang.APPLY + '</a>\
								</div>\
							</div>\
						</div>' +

						'</div>\
					<div class="js-upload-from-file">' + lang.CATALOG_DOWNLOAD_SERVER + '</div>\
					<div class="from_existing">' +
						lang.CHOOSE_FROM_IMG +
						'<div class="custom-popup existing-popup" style="display:none;">\
						</div>\
				  	</div>\
				  </div>\
				</form>\
				<button class="del-img-variant" flow="left" tooltip="' + lang.CATALOG_DELETE_IMG_VAR + '" style="display:' + (variant.image.indexOf('no-img') == -1 ? 'inline-block' : 'none') + '"><span class="del-img-variant__icon-wrap"><i aria-hidden="true" class="fa fa-picture-o"></i></span> </button>\
			  </li>\
			  <li>\
				<button class="del-variant tooltip--small tooltip--center" tooltip="Удалить вариант" flow="left"><i aria-hidden="true" class="fa fa-trash"></i></button>\
			  </li>\
			</ul>\
			</td>\
		  </tr>\ ';
					$('.variant-table').append(position);
				});
				$('.variant-table').data('have-variant', '1');
			} else {
				var position = '\
		<tr class="text-left">\
		  <th style="display:none" class="hide-content"></th>\
		  <th style="display:none;width:150px;position:relative;" class="hide-content">' + lang.NAME_VARIANT + '\
			<span class="typeGroupVar">\
			  (<a role="button" href="javascript:void(0);" class="btn-selected-typeGroupVar">' + lang.GROUP_VAR + '</a>)\
			</span></th>\
		  <th>' + lang.CODE_PRODUCT + '</th>\
		  <th class="table-tr-small">' + lang.PRICE_PRODUCT + ' (' + chooseCurrBtn + ')</th>\
		  <th class="table-tr-small">' + lang.OLD_PRICE_PRODUCT + '</th>\
		  <th class="table-tr-small">' + lang.WEIGHT + ' ('+weightUnitHtml+')</th>\
		  <th class="table-tr-small">' + unitHtml + '</th>';
				// доп поля
				opf = JSON.parse($('#product-op-fields-data').val());
				$.each(opf, function (i, val) {
					position += '<th>' + admin.htmlspecialchars(admin.htmlspecialchars_decode(val.name)) + '</th>';
				});
				position += '<th style="display:none" class="hide-content"></th>\
		</tr>\ ';
				$('.variant-table').append(position);
				var position = '\
		  <tr class="variant-row" data-color="" data-size="">\
			<td class="hide-content"><i class="fa fa-arrows"></i></td>\
			<td class="hide-content">\
			  <label><input type="text" name="title_variant" value="" class="product-name-input " title="' + lang.NAME_PRODUCT + '" ><div class="errorField" style="display:none;">' + lang.NAME_PRODUCT + '</div></label>\
			</td>\
			<td style="width:100px;">\
			  <label><input type="text" name="code" value="" class="product-name-input default-code" title="' + lang.T_TIP_CODE_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_EMPTY + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="price" value="" class="product-name-input  " title="' + lang.T_TIP_PRICE_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="old_price" value="" class="product-name-input  " title="' + lang.T_TIP_OLD_PRICE + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="weight" value="" class="product-name-input  " title="' + lang.T_TIP_WEIGHT_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>\
			<td>\
			  <label><input type="text" name="count" value="" class="product-name-input  " title="' + lang.T_TIP_COUNT_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
			</td>';
				// доп поля
				opf = JSON.parse($('#product-op-fields-data').val());
				$.each(opf, function (i, val) {
					position += '<td><label for="opf_' + val.id + '"><input type="text" name="opf_' + val.id + '" \
				value="" class="product-name-input"></label></td>';
				});
				position += '<td class="tmpl-actions hide-content actions">\
			<div class="variant-dnd"></div>\
			<ul class="action-list" style="display:none;">\
			  <div class="img-this-variant" style="display:none;">\
				<img src="' + admin.SITE + mgNoImageStub +'" style="width:50px; min-height:100%; position: absolute; bottom: 0;" data-filename="">\
			  </div>\
			  <li>\
				<form method="post" noengine="true" enctype="multipart/form-data" class="img-button catalog_uploads_container_variants_wrapper" style="display:inline-block">\
				  <span class="add-img-clone"></span>\
				  <label>\
					<a role="button" class="tooltip--center tooltip--small" aria-label="' + lang.UPLOAD_IMG_VARIANT + '" tooltip="' + lang.UPLOAD_IMG_VARIANT + '" flow="left" ><i class="fa fa-picture-o" aria-hidden="true"></i></a>\
					<input type="file" style="display:none;" name="photoimg" class="add-img-var img-variant " title="' + lang.UPLOAD_IMG_VARIANT + '">\
				  </label>\
				  <div class="additional_uploads_container_variants">\
					<div class="from_pc">' + lang.CATALOG_DOWNLOAD + '</div>\
					<div class="js-open-url-popup">' + lang.CATALOG_DOWNLOAD_LINK + '\
					<div class="custom-popup url-popup" style="display:none;">\
					<div class="row">\
					  <div class="large-12 columns">\
						<label>' + lang.CATALOG_IMG_LINK + ':</label>\
					  </div>\
					</div>\
					<div class="row">\
					  <div class="large-12 columns">\
						<input type="text" name="variant_url">\
					  </div>\
					</div>\
					<div class="row">\
					  <div class="large-12 columns">\
						<a class="button fl-left cancel-url" href="javascript:void(0);"><i class="fa fa-times"></i> ' + lang.CANCEL + '</a>\
						<a class="button success fl-right apply-url" href="javascript:void(0);"><i class="fa fa-check"></i> ' + lang.APPLY + '</a>\
					  </div>\
					</div>\
				  </div>\
					</div>\
					<div class="js-upload-from-file">' + lang.CATALOG_DOWNLOAD_SERVER + '</div>\
					<div class="from_existing">' +
					lang.CHOOSE_FROM_IMG +
					'<div class="custom-popup existing-popup" style="display:none;">\
          </div>\
          </div>\
        </div>\
      </form>\
      <a role="button" href="javascript:void(0);" class="del-img-variant fa fa-picture-o" title="' + lang.CATALOG_DELETE_IMG_VAR + '" style="display:none"> </a>\
			  </li>\
			  <li>\
				<a role="button" href="javascript:void(0);" class="del-variant fa fa-trash"></a>\
			  </li>\
			</ul>\
			</td>\
		  </tr>';
				$('.variant-table').append(position);
				$('.variant-table').data('have-variant', '0');
				$('.variant-table').sortable({
					opacity: 0.6,
					axis: 'y',
					handle: '.fa-arrows',
					items: "tr+tr",
					helper: function (e, tr) {
						var $originals = tr.children();
						var $helper = tr.clone();
						$helper.children().each(function (index) {
							// Set helper cell sizes to match the original sizes
							$(this).width($originals.eq(index).outerWidth());
						});
						return $helper;
					},
				});
			}

			$('#add-product-wrapper .storageToView option[value=' + catalog.selectedStorage + ']').prop('selected', 'selected');

			if ($('#add-product-wrapper .variant-row').length > 1) {
				$('.hide-content').css('display', '');
			} else {
				$('.hide-content').css('display', 'none');
			}

			$('.variant-table input').each(function () {
				if ($(this).val() == 'null') {
					if ($(this).attr('name') == 'weight') {
						$(this).val(0);
					} else {
						$(this).val('');
					}
				}
			});

			admin.initToolTip();
		},


		// Добавляет строку в таблицу вариантов
		addVariant: function (table) {
			if ($('.variant-table').data('have-variant') == "0") {
				$('.variant-table .hide-content').show();
				$('.variant-table').data('have-variant', '1');
			}
			var code = $('.variant-table input[name="code"]:first').val();

			var position = '\
		<tr class="variant-row" data-color="" data-size="">\
		  <td class="hide-content"><i class="fa fa-arrows"></i></td>\
		  <td class="hide-content">\
			<label><input type="text" name="title_variant" value="" class="product-name-input " title="' + lang.NAME_PRODUCT + '" ><div class="errorField" style="display:none;">' + lang.NAME_PRODUCT + '</div></label>\
		  </td>\
		  <td>\
			<label><input type="text" name="code" value="" class="product-name-input default-code" title="' + lang.T_TIP_CODE_PROD + '"><div class="errorField" style="display:none;">' + lang.ERROR_EMPTY + '</div></label>\
		  </td>\
		  <td>\
			<label><input type="text" name="price" value="" class="product-name-input  " title="' + lang.T_TIP_PRICE_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
		  </td>\
		  <td>\
			<label><input type="text" name="old_price" value="" class="product-name-input  " title="' + lang.T_TIP_OLD_PRICE + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
		  </td>\
		  <td>\
			<label><input type="text" name="weight" value="" class="product-name-input  " title="' + lang.T_TIP_WEIGHT_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
		  </td>\
		  <td>\
			<label><input type="text" name="count" value="" class="product-name-input  " title="' + lang.T_TIP_COUNT_PROD + '" ><div class="errorField" style="display:none;">' + lang.ERROR_NUMERIC + '</div></label>\
		  </td>';
			// доп поля
			opf = JSON.parse($('#product-op-fields-data').val());
			$.each(opf, function (i, val) {
				position += '<td><label for="opf_' + val.id + '"><input type="text" name="opf_' + val.id + '" value="" class="product-name-input"></label></td>';
			});
			position += '<td class="tmpl-actions hide-content actions">\
		  <div class="variant-dnd"></div>\
		  <ul class="action-list">\
			<div class="img-this-variant" style="display:none;">\
			  <img src="' + admin.SITE + mgNoImageStub + '" style="width:50px; min-height:100%; position: absolute; bottom: 0;" data-filename="">\
			</div>\
			<li>\
			  <form method="post" noengine="true" enctype="multipart/form-data" class="img-button catalog_uploads_container_variants_wrapper" style="display: inline-block;">\
				<span class="add-img-clone"></span>\
				<label>\
				  <a role="button" class="tooltip--center tooltip--small" aria-label="' + lang.UPLOAD_IMG_VARIANT + '" tooltip="' + lang.UPLOAD_IMG_VARIANT + '" flow="left" ><i class="fa fa-picture-o" aria-hidden="true"></i></a>\
				  <input type="file" style="display:none;" name="photoimg" class="add-img-var img-variant " title="' + lang.UPLOAD_IMG_VARIANT + '">\
				</label>\
				<div class="additional_uploads_container_variants">\
				  <div class="from_pc">' + lang.CATALOG_DOWNLOAD + '</div>\
				  <div class="js-open-url-popup">' + lang.CATALOG_DOWNLOAD_LINK +
				'<div class="custom-popup url-popup" style="display:none;">\
				  <div class="row">\
					<div class="large-12 columns">\
					  <label>' + lang.CATALOG_IMG_LINK + ':</label>\
					</div>\
				  </div>\
				  <div class="row">\
					<div class="large-12 columns">\
					  <input type="text" name="variant_url">\
					</div>\
				  </div>\
				  <div class="row">\
					<div class="large-12 columns">\
					  <a class="button fl-left cancel-url" href="javascript:void(0);"><i class="fa fa-times"></i> ' + lang.CANCEL + '</a>\
					  <a class="button success fl-right apply-url" href="javascript:void(0);"><i class="fa fa-check"></i> ' + lang.APPLY + '</a>\
					</div>\
				  </div>\
				</div>\
				</div>\
				  <div class="js-upload-from-file">' + lang.CATALOG_DOWNLOAD_SERVER + '</div>\
				  <div class="from_existing">' +
				lang.CHOOSE_FROM_IMG +
				'<div class="custom-popup existing-popup" style="display:none;">\
        </div>\
        </div>\
    </div>\
    </form>\
    <a role="button" href="javascript:void(0);" class="del-img-variant fa fa-picture-o" title="' + lang.CATALOG_DELETE_IMG_VAR + '" style="display:none"> </a>\
			</li>\
			<li>\
			  <a role="button" href="javascript:void(0);" class="del-variant fa fa-trash"></a>\
			</li>\
		  </ul>\
		  </td>\
		</tr>';
			table.append(position);

			$('.variant-table input[name="code"]:last').val(code + '-' + $('.variant-table input[name="code"]').length);

			$('.variant-row:eq(0) .action-list').css('display', '');

			$('.typeGroupVar').hide();

			$('.variant-table tr').each(function () {
				if ($(this).find('[name=count]').val() == '') $(this).find('[name=count]').val('∞');
			});

			$('.hide-content').show();

			admin.initToolTip();
		},


		// возвращает пакет  вариантов собранный из таблицы вариантов
		getVariant: function () {
			catalog.errorVariantField = false;
			$('.errorField').hide();

			if ($('.variant-table tr').length == 2) {
				if ($('.variant-row:eq(0)').attr('data-color') == "" && $('.variant-row:eq(0)').attr('data-size') == "") {
					$('.variant-table').data('have-variant', '0');
				} else {
					$('.variant-table').data('have-variant', '1');
				}
			}

			if ($('.variant-table').data('have-variant') == "1") {
				var result = [];
				$('.variant-table .variant-row').each(function () {

					//собираем  все значения полей варианта для сохранения в БД

					var id = $(this).data('id');
					var currency_iso = $('#add-product-wrapper select[name=currency_iso] option:selected').val();
					var obj = {};
					$(this).find('input').removeClass('error-input');
					$(this).find('input').each(function () {

						if ($(this).attr('name') != 'photoimg') {
							var val = $(this).val();
							if ((val == '\u221E' || val == '' || parseFloat(val) < 0) && $(this).attr('name') == "count") {
								val = "-1";
							}
							if (val == "" && $(this).attr('name') == 'weight') {
								val = "0";
							}

							if (val == "" &&
								$(this).attr('name') != 'old_price' &&
								$(this).attr('name') != 'color' &&
								$(this).attr('name') != 'size' &&
								$(this).attr('name') != 'variant_url' &&
								$(this).attr('name').indexOf('opf_') === -1) {

								$(this).addClass('error-input');
								catalog.errorVariantField = true;
								$(this).parents('td').find('.errorField').show();
							}
							obj[$(this).attr('name')] = val;
						}
					});
					if ($(this).attr('data-color')) {
						obj['color'] = $(this).attr('data-color');
						if (obj['color'] === 'undefined') obj['color'] = '';
					}
					if ($(this).attr('data-size')) {
						obj['size'] = $(this).attr('data-size');
						if (obj['size'] === 'undefined') obj['size'] = '';
					}
					obj["activity"] = 1;
					obj["id"] = id;
					obj["currency_iso"] = currency_iso;

					var filename = $(this).find('img[filename]').attr('filename');
					if (!filename || filename == undefined || filename == '') {
						filename = $(this).find('img[filename]').attr('filename')
					}
					if (!filename || filename == undefined || filename == '') {
						filename = $(this).find('img').attr('src')
					}

					obj["image"] = filename.replace('thumbs/30_', '');

					//преобразуем полученные данные в JS объект для передачи на сервер
					result.push(obj);
				});

				return result;
			}
			return null;
		},

		// возвращает список id связанных товаров с редактируемым
		getRelatedProducts: function () {
			var result = '';
			$('.add-related-product-block .product-unit').each(function () {
				result += $(this).data('code') + ',';
			});
			result = result.slice(0, -1);


			return result;
		},
		// возвращает список id связанных категорий с редактируемым
		getRelatedCategory: function () {
			var result = '';
			$('.add-related-product-block .category-unit').each(function () {
				result += $(this).data('id') + ',';
			});
			result = result.slice(0, -1);
			return result;
		},

		// сохраняет параметры товара прямо со страницы каталога в админке
		fastSave: function (data, val, input) {

			var obj = eval("(" + data + ")");



			// знак бесконечности
			if (obj.field == "count") {
				var v = parseFloat(val).toFixed(2).replace(/\.00/g, '');
				if (isNaN(v)) {
					v = '-1';
				}else{
					// если последний ноль, то убираем
					if(v.toString().search(/(\.)|(\,)/)!==-1){
						// если последний ноль, то убираем
						if(v.toString().slice(-1)==='0'){
							v = v.slice(0, -1);
						}
					}
				}
				input.val(v);
				val = v;
			}

			// знак бесконечности
			if (obj.field == "count" && (val == '\u221E' || val == '' || parseFloat(val) < 0)) {
				val = "-1";
				input.val('∞');
			}

			if (obj.field != 'code' && !obj.field.startsWith('opf_')) {
				// Проверка поля для стоимости, является ли текст в него введенный числом.
				if (
					(obj.field == 'price' && isNaN(parseFloat(val))) ||
					(obj.field == 'old_price' && val !== '' && isNaN(parseFloat(val)))
				) {
					admin.indication('error', lang.ENTER_NUM);
					input.addClass('error-input');
					return false;
				} else {
					val = parseFloat(val.replace(',', '.'));
					input.removeClass('error-input');
				}
			}
			var id = input.parents('.product-row').attr('id');

			admin.ajaxRequest({
					mguniqueurl: "action/fastSaveProduct",
					variant: obj.variant,
					id: obj.id,
					field: obj.field,
					value: val,
					product_id: id,
					curr: obj.curr,
				},
				function (response) {
					if (obj.field == 'price') {
						input.closest('tbody').find('.view-price[data-productid='+obj.id+']').text(response.data.shopCurrPrice);
					}
					
					admin.clearGetParam();
					admin.indication(response.status, response.msg);
				}
			);
		},
		importFromCsv: function () {
			admin.ajaxRequest({
					mguniqueurl: "action/importFromCsv",
				},
				function (response) {
					admin.indication(response.status, response.msg);
				});
		},

		/**
		 * Загружает CSV файл на сервер для последующего импорта
		 */
		uploadCsvToImport: function () {
			// отправка файла CSV на сервер
			$('.repeat-upload-file .message').text(lang.MESSAGE_WAIT);
			$('.upload-csv-form').ajaxForm({
				type: "POST",
				url: "ajax",
				data: {
					mguniqueurl: "action/uploadCsvToImport"
				},
				cache: false,
				dataType: 'json',
				error: function () {
					alert(lang.CATALOG_MESSAGE_5);
				},
				success: function (response) {
					catalog.parseSeparator = ';';
					$('.import-addition-settings__separator-celect option:first').prop('selected', true);
					admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						$('.section-catalog select[name=importScheme]').removeAttr('disabled');
						$('.section-catalog select[name=identifyType]').removeAttr('disabled');
						$('input[name=no-merge]').removeAttr('disabled');
						$('.repeat-upload-file').show();
						$('.block-upload-сsv .upload-btn').hide();

						let importType = $('.section-catalog select[name="importType"]').val();
						admin.ajaxRequest({
							mguniqueurl: "action/setCsvCompliance",
							importType: importType,
						}, function (response) {
							$('.repeat-upload-file .message').text(lang.FILE_READY_IMPORT);
							$('.columnComplianceModal .js-open-setting-csv').show();
							$('.columnComplianceModal .update').hide();
							catalog.showSchemeSettings('auto');
						});

						catalog.setCsvCompliance();
					
						setTimeout(function (){
							catalog.chooseUploadsType();
						}, 2000);
					} else {
						$('.message-importing').text('');
						$('.import-container input[name="upload"]').val('');
					}
				},

			}).submit();
		},

		/**
		 * Выбор CSV файла на сервере для последующего импорта
		 */
		selectCSV: function (file) {

			admin.ajaxRequest({
					mguniqueurl: "action/selectCSV",
					file: file.path,
					name: file.name,
				},
				function (response) {
					admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						$('.section-catalog select[name=importScheme]').removeAttr('disabled');
						$('.section-catalog select[name=identifyType]').removeAttr('disabled');
						$('input[name=no-merge]').removeAttr('disabled');
						$('.repeat-upload-file').show();
						$('.block-upload-сsv .upload-btn').hide();
						catalog.setCsvCompliance();
						$('.repeat-upload-file .message').text(lang.FILE_READY_IMPORT);
						catalog.showSchemeSettings('auto');
					} else {
						$('.message-importing').text('');
						$('.import-container input[name="upload"]').val('');
					}
				});
		},

		/**
		 * Устанавливает первоначальное соответствие полей для CSV по их заголовкам
		 */
		setCsvCompliance: function (parseSeparator = ';') {
			var importType = $('.section-catalog select[name="importType"]').val();

			admin.ajaxRequest({
				mguniqueurl: "action/setCsvCompliance",
				importType: importType,
				parseSeparator: parseSeparator,
			}, function (response) {
				return true;
			});
		},

		/**
		 * Контролирует процесс импорта, выводит индикатор в процентах обработки каталога.
		 */
		startImport: function (rowId, percent, downloadLink, iteration) {
			iteration = typeof iteration !== 'undefined' ? iteration : 1;
			parseSeparator = catalog.parseSeparator ? catalog.parseSeparator : ';';
			var typeCatalog = $(".block-upload-сsv select[name=importType]").val();
			var identifyType = $(".block-upload-сsv select[name=identifyType]").val();
			var schemeType = $('.section-catalog select[name=importScheme]').val();
			var delCatalog = null;
			var delImages = null;
			if (!rowId) {
				if (!$('.loading-line').length) {
					$('.process').append('<div class="loading-line"></div>');
				}
				rowId = 0;
				delCatalog = $('input[name=no-merge]').prop('checked');
				delImages = $('input[name=no-img]').prop('checked');
			}
			defaultActive = $('input[name=active-default]').prop('checked');
			if(catalog.exportType == 'refresh' && catalog.updateMod == 'code'){
				updateByArticle = 'true';
			}else{
				updateByArticle = 'false';
			}
			if (!percent) {
				percent = 0;
			}

			if (!downloadLink) {
				downloadLink = false;
			}

			if (!catalog.STOP_IMPORT) {
				$('.message-importing').html(lang.IMPORT_PROGRESS + percent + '% <img src="' + admin.SITE + '/mg-admin/design/images/loader-small.gif"><div class="progress-bar"><div class="progress-bar-inner" style="width:' + percent + '%;"></div> <div>' + lang.PROCESSED + ': ' + rowId + ' ' + lang.LOCALE_STRING + '</div></div>');
			} else {
				$('.loading-line').remove();
			}
			// отправка файла CSV на сервер
			admin.ajaxRequest({
					mguniqueurl: "action/startImport",
					rowId: rowId,
					iteration: iteration,
					delCatalog: delCatalog,
					typeCatalog: 'MogutaCMS',
					identifyType: identifyType,
					schemeType: schemeType,
					downloadLink: downloadLink,
					delImages: delImages,
					defaultActive: defaultActive,
					updateByArticle:updateByArticle,
					parseSeparator:parseSeparator,
				},
				function (response) {
					if (response.status == 'error') {
						admin.indication(response.status, response.msg);
					}

					if (response.data.percent < 100) {
						if (response.data.status == 'canseled') {
							$('.message-importing').html(lang.IMPORT_STOP + response.data.rowId + lang.LOCALE_GOODS + '  ' + '[<a role="button" href="javascript:void(0);" class="repeat-upload-csv">' + lang.UPLOAD_ANITHER + '</a>]');
							$('.loading-line').remove();
						} else {
							if (response.data.iteration == 2) response.data.rowId--;
							setTimeout(function () {
								catalog.startImport(response.data.rowId, response.data.percent, response.data.downloadLink, response.data.iteration);
							}, 2000);
						}
					} else {
						$('.cancel-import').hide();
						$('.message-importing').html(lang.IMPORT_FINISHED + ' \
			  <a class="refresh-page custom-btn" href="' + mgBaseDir + '/mg-admin/">\n\
				<span>' + lang.CATALOG_REFRESH + '</span>\n\
			  </a> ' + lang.LOCALE_OR + ' <a role="button" href="javascript:void(0);" class="gotoImageUpload custom-btn"><span>' + lang.GO_DOWNLOAD_IMG + '</span></a><br>\
			  <a href="' + admin.SITE + '/uploads/temp/data_csv/import_csv_log.txt" target="blank">' + lang.VIEW_IMPORT_LOG + '</a>');
						$('.block-upload-сsv').hide();

						if (response.data.startGenerationImage == true) {
							$('.message-importing').hide();
							$('.import-container h3.title').text(lang.CREATE_THUMB_IMG);
							$('.block-upload-images').show();
							$('.block-upload-images .upload-images').hide();
							catalog.startGenerationImage = true;
							catalog.startGenerationImageFunc();
						}

						//startImport
						$('.loading-line').remove();
					}
				});
		},

		/**
		 * Клик по найденным товарам поиске в форме добавления связанного товара.
		 */
		addrelatedProduct: function (elementIndex, product) {
			$('.search-block .errorField').css('display', 'none');
			$('.search-block input.search-field').removeClass('error-input');
			if (!product) {
				var product = admin.searcharray[elementIndex];
			}

			if (product.category_url.charAt(product.category_url.length - 1) == '/') {
				product.category_url = product.category_url.slice(0, -1);
			}

			var html = catalog.rowRelatedProduct(product);
			$('.added-related-product-block .product-unit[data-id=' + product.id + ']').remove();
			$('.related-wrapper .added-related-product-block').prepend(html);
			catalog.widthRelatedUpdate();
			catalog.msgRelated();
			$('input[name=searchcat]').val('');
			$('.select-product-block').hide();
			$('.fastResult').hide();
		},
		/**
		 * Клик по выбранным связанным категориям
		 */
		addrelatedCategory: function (category) {
			var html = '';
			category.forEach(function (item, i, arr) {
				if (item.image_url == null) {
					image_url = mgNoImageStub;
				} else {
					image_url = item.image_url;
				}
				html += '\
	  <div class="category-unit" data-id=' + item.id + '>\
		  <div class="product-img">\
			  <a role="button" href="javascript:void(0);"><img src="' + mgBaseDir + image_url + '"></a>\
		  </div>\
		  <a href="' + mgBaseDir + '/' + item.parent_url + item.url +
					'" data-url="' + item.url + '" class="product-name" target="_blank" title="' +
					item.title + '">' +
					item.title + '</a>\
		  <a class="move-handle fa fa-arrows" href="javascript:void(0);"></a>\
		  <a class="remove-added-category custom-btn fa fa-trash" href="javascript:void(0);"><span></span></a>\
	  </div>\
	  ';
				$('.added-related-category-block .category-unit[data-id=' + item.id + ']').remove();
			})
			$('.related-wrapper .added-related-category-block').prepend(html);
			catalog.widthRelatedUpdate();
			catalog.msgRelated();
			$('.search-block.category select[name=related_cat] option').prop('selected', false);
			$('.select-product-block').hide();
		},

		/**
		 * формирует верстку связанного продукта.
		 */
		rowRelatedProduct: function (product) {
			var price = (product.real_price) ? product.real_price : product.price;

			var html = '\
	  <div class="product-unit ui-state-default" data-id=' + product.id + ' data-code="' + product.code + '">\
		<div class="product-img" style="text-align:center;height:50px;">\
		  <img src="' + product.image_url + '" style="height:50px;">\
		  <span class="move-handle fa fa-arrows"></span>\
		  <button class="remove-img fa fa-times tip remove-added-product" data-hasqtip="88" oldtitle="' + lang.DELETE + '" title="" aria-describedby="qtip-88"></button>\
		</div>\
		<a href="' + mgBaseDir + '/' + product.category_url + "/" + product.product_url +
				'" data-url="' + product.category_url +
				"/" + product.product_url + '" class="product-name" target="_blank" title="' +
				product.title + '">' +
				product.title + '</a>\
		<span>' + admin.numberFormat(admin.numberDeFormat(price)) + ' ' + admin.CURRENCY + '</span>\
	  </div>\
	  ';
			return html;
		},

		//выводит связанные товары
		//relatedProducts - массив с товарами
		drawRelatedProduct: function (relatedArr) {
			relatedArr.forEach(function (product, index, array) {
				var html = catalog.rowRelatedProduct(product);
				$('.related-wrapper .added-related-product-block').append(html);
				catalog.widthRelatedUpdate();
			});
			catalog.msgRelated();
		},

		//выводит ссылку в пустом блоке для добавления связанного товара
		msgRelated: function () {
			if ($('.added-related-product-block .product-unit').length == 0 && $('.added-related-category-block .category-unit').length == 0) {
				if ($('a.add-related-product.in-block-message').length == 0) {
					$('.related-wrapper .added-related-product-block').append('\
		 <a class="add-related-product in-block-message" href="javascript:void(0);"><span>' + lang.RELATED_PROD + '</span></a>\
	   ');
				}
				$('.added-related-product-block').width('800px');
			} else {
				$('.added-related-product-block .add-related-product').remove();
			}
			;
			if ($('.added-related-category-block .category-unit').length == 0) {
				$('.add-related-product-block .add-related-category.in-block-message').hide();
			} else {
				$('.add-related-product-block .add-related-category.in-block-message').show();
			}
		},

		//пересчитывает ширину блока с связанными товарами, для работы скрола.
		widthRelatedUpdate: function () {
			var prodWidth = $('.product-unit').length * ($('.product-unit').width() + 30);
			var catWidth = $('.category-unit').length * ($('.category-unit').width() + 30);
			if (prodWidth > catWidth) {
				$('.related-block').width(prodWidth);
			} else {
				$('.related-block').width(catWidth);
			}
			if ($('.product-unit').length == 0) {
				$('.added-related-product-block').css('display', 'none');
			} else {
				$('.added-related-product-block').css('display', '');
			}
			if ($('.category-unit').length == 0) {
				$('.added-related-category-block').css('display', 'none');
			} else {
				$('.added-related-category-block').css('display', '');
			}
		},

		/**
		 * Останавливает процесс импорта в каталог товаров
		 */
		canselImport: function () {
			$('.message-importing').text(lang.STOP_IMPORT);
			catalog.STOP_IMPORT = true;
			admin.ajaxRequest({
					mguniqueurl: "action/canselImport"
				},
				function (response) {
					admin.indication(response.status, response.msg);
				});
		},
		/**
		 * Выделяет все категории в списке, в которых будет отображаться товар
		 */
		selectCategoryInside: function (selectedCatIds) {
			if (!selectedCatIds) {
				$('.add-category').removeClass('opened-list');
			} else {
				$('.add-category').addClass('opened-list');
			}
			if (selectedCatIds) {
				var htmlOptionsSelected = selectedCatIds.split(',');
				$('select[name=inside_cat] option').prop('selected', false);

				function buildOption(element, index, array) {
					$('.inside-category select[name="inside_cat"] [value="' + element + '"]').prop('selected', 'selected');
				}
				htmlOptionsSelected.forEach(buildOption);

				$('.js-inside-category').show();
			}
		},

		/**
		 * Возвращает список выбранных категорий для товара
		 */
		createInsideCat: function () {
			var category = '';
			$('select[name=inside_cat] option').each(function () {
				if ($(this).prop('selected')) {
					category += $(this).val() + ',';
				}
			});

			category = category.slice(0, -1);

			return category;
		},

		/**
		 * Возвращает список выбранных категорий для товара
		 */
		getFileElectro: function (file) {
			var dir = file.url;
			dir = dir.replace(mgBaseDir, '');
			$('.section-catalog input[name="link_electro"]').val(dir);
			$('.section-catalog .del-link-electro').text(dir.substr(0, 50));
			$('.section-catalog .del-link-electro').attr('title', dir);
			$('.section-catalog .del-link-electro').show();
			$('.section-catalog .add-link-electro').hide();
		},

		/**
		 * Смена валюты
		 */
		changeIso: function () {
			var short = $('#add-product-wrapper select[name=currency_iso] option:selected').text();
			var rate = $('#add-product-wrapper select[name=currency_iso] option:selected').data('rate');
			$('#add-product-wrapper .btn-selected-currency').text(short);
			$('#add-product-wrapper .select-currency-block').hide();
		},

		/**
		 * Возвращает сокращение, из списка допустимых валют
		 * @param {type} iso
		 * @returns {undefined}
		 */
		getShortIso: function (iso) {
			iso = JSON.stringify(iso);
			var short = $('.select-currency-block select[name=currency_iso] option[value=' + iso + ']').text();
			return short;
		},

		closeAddedProperty: function (type) {
			if (type == 'close') {
				$('.addedProperty .new-added-prop').each(function () {
					var id = $(this).data('id');
					admin.ajaxRequest({
						mguniqueurl: "action/deleteUserProperty",
						id: id
					})
				});
			}
			$('#add-product-wrapper .new-added-properties').hide();
			$('#add-product-wrapper .new-added-properties input').val('');
			$('#add-product-wrapper .new-added-properties input').removeClass('error-input');
			$('.new-added-properties .errorField').hide();
		},

		// добавляет новую характеристику
		addNewProperty: function (name, value) {
			admin.ajaxRequest({
					mguniqueurl: "action/addUserProperty",
					type: 'string',
				},
				function (response) {
					var id = response.data.allProperty.id;
					var html = '' +
						'<div class="new-added-prop" data-id="' + id + '">' +
						'<div class="row">' +
						'<div class="medium-5 small-12 columns">' +
						'<label for="' + id + '">' + name + ':</label>' +
						'</div>' +
						'<div class="medium-7 small-11 columns to-input-btn">' +
						'<input class="property custom-input" id="' + id + '" type="text" value="' + admin.htmlspecialchars_decode(value) + '" data-id="temp-' + id + '" name="' + id + '">' +
						'<a role="button" href="javascript:void(0);" class="remove-added-property fa fa-trash btn red"></a>' +
						'</div>' +
						'</div>' +
						'</div>';
					$('#add-product-wrapper .addedProperty').prepend(html);

					admin.ajaxRequest({
						mguniqueurl: "action/saveUserProperty",
						id: id,
						name: name,
					});

					var category = $('.product-text-inputs select[name=cat_id]').val();
					admin.ajaxRequest({
						mguniqueurl: "action/saveUserPropWithCat",
						id: id,
						category: category
					});

				});
			catalog.closeAddedProperty();
		},

		//Добавляет новую характеристику
		saveAddedProperties: function () {
			$('.addedProperty .new-added-prop ').each(function () {
				var id = $(this).data('id');
				var category = $('.product-text-inputs select[name=cat_id]').val();
				admin.ajaxRequest({
					mguniqueurl: "action/saveUserPropWithCat",
					id: id,
					category: category
				})
			})
		},

		initMovers: function() {
			if ($('.section-catalog .filter-form [name=sorter]').val() == 'sort|-1') {
				$('.product-table .mover').show();
				admin.sortable('.product-table > tbody', 'product', true);
			} else {
				$('.product-table .mover').hide();
			}

			if ($('#catalog-order').prop('checked') && $('.product-tbody .mover:visible').length < 1) {
				$('.product-table .fa-arrows').hide();
			} else {
				$('.product-table .fa-arrows').show();
			}
		},

		// Обновление по артикулу
		showtUpdateModCsv:function(){
			//$('.columnComplianceModal .setFullModCsv').show();
			$('.columnComplianceModal .delete-all-products-btn').hide();
			catalog.clearRequiredFields();
			catalog.updateMod = 'code';
			$('.columnComplianceModal .delete-all-products-btn input').prop('checked', false);
			let content = '<span class = "required_field" tooltip = "Обязательное поле">*</span>';
			let requiredFields = ['Артикул'];
			$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
				let nameOfFild = $(this).find('td:eq(0) b').text();
				if ($.inArray(nameOfFild, requiredFields) === -1 && nameOfFild != 'Цена') {
					$(this).hide().find('select').val('none').addClass('none-selected');
				}else if(nameOfFild == 'Цена'){
					$(this.cells[0]).append('<span class="delCsvRow fl-right" style = "display:none;"></span>');
					catalog.clearSelectValues();
					catalog.addToSelectValues();
					catalog.delCsvRowInit();
				}
				else{
					$(this.cells[0]).append(content);
				}
			});
			$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
				if ($(this).find('td:eq(0) b').text() == 'Оптовые цены') {
					$(this).find('select option').each(function () {
						if ($(this).text().indexOf('[оптовая цена]') > -1) {
							$(this).parent().val($(this).val()).removeClass('none-selected');
							return false;
						}
					});
				}
				if ($(this).find('td:eq(0) b').text() == 'Склады') {
					$(this).find('select option').each(function () {
						if ($(this).text().indexOf('[склад') > -1) {
							$(this).parent().val($(this).val()).removeClass('none-selected');
							return false;
						}
					});
				}
			});
			catalog.addToSelectValues();
		},

		// Обновление по названию
		showFullModCsv:function(){
			$('.columnComplianceModal .delete-all-products-btn').show();
			catalog.clearRequiredFields();
			catalog.updateMod = 'title';
			let requiredFields = ['Товар'];
			let content = '<span class = "required_field" tooltip = "Обязательное поле">*</span>';
			$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
				if ($.inArray($(this).find('td:eq(0) b').text(), requiredFields) != -1) {
					$(this.cells[0]).append(content);
				}
				$(this).show();
			});
			catalog.showSchemeSettings('auto', requiredFields,catalog.parseSeparator);
		},

		// очистка обязательных полей
		clearRequiredFields:function(){
			$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
				$(this).find('.required_field').remove();
			});
		},

		//Заполнение селекта для выбора полей при загрузке по артикулу
		addToSelectValues:function(){
			$('.widget-table-body .addColum').show();
			$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
				let name = $(this).find('td:eq(0) b').text();
				let requiredFields = ['Сложные характеристики', 'Свойства начинаются с'];
				if($(this).is(':hidden') && $.inArray(name, requiredFields) === -1){
					$('.widget-table-body #addColumSelect').append('<option value = "'+$(this).attr('id')+'">'+name+'</option>');
				}
			});
		},

		//Очистка селекта для выбора полей при загрузке по артикулу
		clearSelectValues:function(){
			$('.widget-table-body #addColumSelect').html('');
		},

		//Инициализация удаления строки при обновлении прайста
		delCsvRowInit:function(){
			$('.section-catalog').on('click', '.delCsvRow', function(){
				$(this).parent().parent().hide();
				$(this).parent().next('td').find('select').val('none').addClass('none-selected'); // устанавливаем селект в первоначальное положение "Не"
				$(this).remove();
				catalog.clearSelectValues();
				catalog.addToSelectValues();
			});
		},

		//Выбор типа загрузки в соответствии с заданным селектом
		chooseUploadsType:function(){
			let value = $('.reveal-overlay #chooseUploadsType').val();
			if(value == 'download'){
				catalog.exportType = 'upload';
				$('.columnComplianceModal .widget-table-body .complianceHeaders').show();
				let requiredFields = ['Категория', 'Товар', 'Цена'];
				let content = '<span class = "required_field" tooltip = "Обязательное поле">*</span>';
				$('.columnComplianceModal .widget-table-body .complianceHeaders tbody tr').each(function () {
					$(this).find('.required_field').remove();
					$(this).show();
					if ($.inArray($(this).find('td:eq(0) b').text(), requiredFields) != -1) {
						$(this.cells[0]).append(content);
					}
				});
				//catalog.showSchemeSettings('auto', requiredFields);
				$('.reveal-footer .update_by_article').hide();
				$('.add-product-form-wrapper .update_type').hide();
				$('.widget-table-body .addColum').hide();
				$('.columnComplianceModal .delete-all-products-btn').show();
			}else if(value == 'refresh'){
				catalog.exportType = 'refresh';
				$('.columnComplianceModal .widget-table-body .complianceHeaders').show();
				let update_type = $('.add-product-form-wrapper .update_type select').val();
				catalog.clearRequiredFields();
				if(update_type == 1){
					catalog.showFullModCsv();
					$('.widget-table-body .addColum').hide();
				}else{
					catalog.showtUpdateModCsv();
				}
				$('.add-product-form-wrapper .update_type').show();
			}
		}
	}
})();
