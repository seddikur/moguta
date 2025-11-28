var YandexMarketModule = (function() {
	
	return { 
		init: function() {   

			if ($('.integration-container .template-tabs-menu .template-tabs').length > 1) {
				$('.integration-container .template-tabs-menu .template-tabs').show();
			}
			else{
				$('.integration-container .template-tabs-menu .template-tabs').hide();
			}

			 // Добавляет вкладку
			$('.integration-container').on('click', '.newNameSave', function() {

				//удаление хлама
				var nname = $(".integration-container input[name=newName]").val();
				//nname = nname.replace( /\s/g, "");
				nname = nname.toLowerCase();
				nname = nname.replace(/[^0-9a-z]/g, '');

				if (nname == '') {
					admin.indication('error', lang.UPLOAD_NAME);
				}
				else{
					admin.ajaxRequest({
						mguniqueurl: "action/newTabYandexMarket",
						name: nname,
					},
					function (response) {
						if (response.data == false && nname != 0) {
							admin.indication('error', lang.NAME_ALREADY_EXISTS);
						} else {
							$('.integration-container .template-tabs-menu .template-tabs').show();
							$(".integration-container input[name=newName]").val('');
							admin.indication('success', lang.NEW_UPLOAD_CREATED);
							$('<li class="template-tabs button primary clickMe" name="'+response.data+'"><a role="button" href="javascript:void(0);" ><span>'+response.data+'</span></a></li>').insertAfter(".creator");
							$('<li style="display:inline-block;width:4px;"></li>').insertAfter(".creator");
							$('.clickMe').click().removeClass('clickMe');
							try {
								let catsList = $('select.js-comboBox option');
								if (catsList) {
								  catsList.each(function (index2, element2) {
									let oldValue = $(this).text();
									$(this).text(admin.htmlspecialchars(oldValue).replaceAll('&quot;', '"'));
								  });
								}
								$('select.js-comboBox').each(function() {
									if ($(this)[0].sumo) {
										$(this)[0].sumo.reload();
									}
								});
								$('select.js-comboBox:not(.SumoUnder)').each(function () {
								  $(this).SumoSelect({
									okCancelInMulti: true,
									clearAll: true,
									selectAll: true,
									search: true,
									searchText: lang.SUMOSELECT_SEARCH,
									noMatch: lang.SUMOSELECT_NOMATCH,
									up: ((typeof $(this).attr('data-up') != "undefined") ? true : false)
								  });
								});
							  } catch (err) {
								console.log(err);
							  }
						}
					});
				}
			});
			
			//преключение табов
			$('.integration-container').on('click', '.template-tabs', function() {

				$(this).parent().find('li').removeClass('is-active');
				$(this).addClass('is-active');
				var nname = $(this).attr('name');
				YandexMarketModule.updateLink(nname);

				if (nname.length > 0) {
					$('.newName').hide();
					$('.editOld').show();
					$('.editOldSave').attr('name', nname);
					$('.editOldDelete').attr('name', nname);

					admin.ajaxRequest({
						mguniqueurl: "action/getTabYandexMarket",
						name: nname,  
					},

					function(response) {
						if ($.map(response.data, function() { return 1; }).length > 1) {
							$('.bottomBorder').show();
							$('#downloadLink').show();
						}
						else{
							$('.bottomBorder').hide();
							$('#downloadLink').hide();
						}

						let format = 'db';
						if (response.data.format) {
							format = response.data.format;
						}
							$(".editOld select[name=format]").val(format);

						$(".editOld input[name=company]").val(response.data.company);
						YandexMarketModule.drawCheckbox(response.data.useProps, 'useProps');
						YandexMarketModule.drawCheckbox(response.data.useVariants, 'useVariants');
						YandexMarketModule.drawCheckbox(response.data.useNull, 'useNull');
						YandexMarketModule.drawCheckbox(response.data.useCount, 'useCount');
						YandexMarketModule.drawCheckbox(response.data.inactiveToo, 'inactiveToo');
						YandexMarketModule.drawCheckbox(response.data.productWithZeroPrice, 'productWithZeroPrice');
						YandexMarketModule.drawCheckbox(response.data.useMarket, 'useMarket');
						YandexMarketModule.drawCheckbox(response.data.useOldPrice, 'useOldPrice');
						YandexMarketModule.drawCheckbox(response.data.useRelated, 'useRelated');
						YandexMarketModule.drawCheckbox(response.data.skipName, 'skipName');
						YandexMarketModule.drawCheckbox(response.data.useCode, 'useCode');
						YandexMarketModule.drawCheckbox(response.data.skipDesc, 'skipDesc');
						YandexMarketModule.drawCheckbox(response.data.useShortDesc, 'useShortDesc');
						YandexMarketModule.drawCheckbox(response.data.useCdata, 'useCdata');
						YandexMarketModule.drawCheckbox(response.data.useAdditionalCats, 'useAdditionalCats');
						YandexMarketModule.drawCheckbox(response.data.useCodeLikeId, 'useCodeLikeId');
						YandexMarketModule.drawCheckbox(response.data.useHook, 'useHook');
						YandexMarketModule.drawCheckbox(response.data.useGroupId, 'useGroupId');

						$(".editOld input[name=descLength]").val(response.data.descLength);
						$(".editOld input[name=priceRate]").val(response.data.priceRate);
						$(".editOld input[name=utm]").val(response.data.utm);
						$(".editOld select[name=propDisable] option:selected").prop("selected", false);
						$(".editOld select[name=propDisable]").val(response.data.propDisable);
						$(".editOld select[name=catsType]").val(response.data.catsType);
						$(".editOld select[name=storageCount]").val(response.data.storageCount);
						$(".editOld select[name=priceSource]").val(response.data.priceSource);

						let dimensionUnits = 'cm';
						if (response.data.dimensionUnits) {
							dimensionUnits = response.data.dimensionUnits;
						}
						$(".editOld select[name=dimensionUnits]").val(dimensionUnits);

						if (response.data.length) {
							$(".editOld select[name=length]").val(response.data.length);
						}
						if (response.data.width) {
							$(".editOld select[name=width]").val(response.data.width);
						}
						if (response.data.height) {
							$(".editOld select[name=height]").val(response.data.height);
						}
						if (response.data.weightFrom) {
							$('.editOld select[name=weightFrom]').val(response.data.weightFrom);
						}
						if (response.data.weightUnits) {
							$('.editOld select[name=weightUnits]').val(response.data.weightUnits);
						}
						
						$(".editOld select[name=uploadType]").val(response.data.uploadType).trigger('change');
						$(".editOld select[name=catsSelect] option:selected").prop("selected", false);
						$(".editOld select[name=catsSelect]").val(response.data.catsSelect);
						$(".editOld select[name=uploadCurr]").html(response.data.currSelect);
						$(".editOld textarea[name=salesNotes]").html(admin.htmlspecialchars(response.data.salesNotes));
						YandexMarketModule.drawRelatedProduct(response.data.addProducts,'add');
						YandexMarketModule.drawRelatedProductProductRemove(response.data.ignoreProducts, response.data.useAsProdVariants);
						YandexMarketModule.drawCustomTags(response.data.customTags);
						YandexMarketModule.drawCustomParams(response.data.customParams);
						YandexMarketModule.drawCustomUtm(response.data.customUtm);
						YandexMarketModule.drawDeliverys(response.data.deliverys);
						YandexMarketModule.drawPickups(response.data.pickups);
						YandexMarketModule.hideTrash();
						$('.integration-container select[name=uploadType]').trigger('change');
						YandexMarketModule.formatChanged(format);
						try {
							let catsList = $('select.js-comboBox option');
							if (catsList) {
							  catsList.each(function (index2, element2) {
								let oldValue = $(this).text();
								$(this).text(admin.htmlspecialchars(oldValue).replaceAll('&quot;', '"'));
							  });
							}
							$('select.js-comboBox').each(function() {
								if ($(this)[0].sumo) {
									$(this)[0].sumo.reload();
								}
							});
							$('select.js-comboBox:not(.SumoUnder)').each(function () {
							  $(this).SumoSelect({
								okCancelInMulti: true,
								clearAll: true,
								selectAll: true,
								search: true,
								searchText: lang.SUMOSELECT_SEARCH,
								noMatch: lang.SUMOSELECT_NOMATCH,
								up: ((typeof $(this).attr('data-up') != "undefined") ? true : false)
							  });
							});
						  } catch (err) {
							console.log(err);
						  }
					});
				}
				else{
					$('.newName').show();
					$('.editOld').hide();
				}
			});

			//сохранение таба
			$('.integration-container').on('click', '.editOldSave', function() {

				var nname = $(this).attr('name');
				var customTagz = [];
				var customTagNamez = [];
				var customTagType = '';
				var customTagVal = '';
				var customTagName = '';
				var customParamz = [];
				var customParamType = '';
				var customParamVal = '';
				var customParamName = '';
				var customUtmz = [];
				var customUtmType = '';
				var customUtmVal = '';
				var customUtmName = '';
				var deliveryz = [];
				var pickupz = [];
				var deliveryzProduct = [];

				$('.integration-container .customTagsContainer .customTags').each(function(index,element) {

					customTagType = $(this).find('.changeCustomTagType').attr('tagType');
					customTagName = $(this).find('input[name=customTagName]').val();
					customTagAttributes = $(this).find('input[name=customTagAttributes]').val();

					if (customTagType == 'prop') {
						customTagVal = $(this).find('select[name=customProp]').val();
					}
					else if (customTagType == 'text') {
						customTagVal = $(this).find('input[name=customTagText]').val();
					}
					if (customTagName != ''){
						customTagz.push({'name':customTagName, 'type':customTagType, 'val':customTagVal, 'attributes':customTagAttributes});
						customTagNamez.push(customTagName);
					}
				});

				$('.integration-container .customParamsContainer .customParams').each(function(index,element) {
					customParamType = $(this).find('.changeCustomParamType').attr('ParamType');
					customParamName = $(this).find('input[name=customParamName]').val();

					if (customParamType == 'prop') {
						customParamVal = $(this).find('select[name=customProp]').val();
					}
					else if (customParamType == 'text') {
						customParamVal = $(this).find('input[name=customParamText]').val();
					}
					if (customParamName != ''){
						customParamz.push({'name':customParamName, 'type':customParamType, 'val':customParamVal});
					}
				});

				$('.integration-container .customUtmContainer .customUtm').each(function(index,element) {
					customUtmType = $(this).find('.changeUtmParamType').attr('utmType');
					customUtmName = $(this).find('input[name=utmName]').val();

					if (customUtmType == 'prop') {
						customUtmVal = $(this).find('select[name=customProp]').val();
					}
					else if (customUtmType == 'text') {
						customUtmVal = $(this).find('input[name=customUtmText]').val();
					}
					if (customUtmName != ''){
						customUtmz.push({'name':customUtmName, 'type':customUtmType, 'val':customUtmVal});
					}
				});

				$('.integration-container tbody[name=deliverys] tr').each(function(index,element) {
					if ($(this).find('input[name=delivCost]').val() != '') {
						deliveryz.push({
							'cost':$(this).find('input[name=delivCost]').val(), 
							'time':$(this).find('input[name=delivTime]').val(), 
							'before':$(this).find('input[name=delivBefore]').val(),
							'code': $(this).find('input[name=delivCode]').val()
						});
					}
				});               

				$('.integration-container tbody[name=pickups] tr').each(function(index, element) {
					if ($(this).find('input[name=pickupTime]').val() != '') {
						pickupz.push({
							'time':$(this).find('input[name=pickupTime]').val(),
							'before':$(this).find('input[name=pickupBefore]').val(),
							'code': $(this).find('input[name=pickupCode]').val(),
						});
					}
				});

				//console.log(deliveryz);

				//https://music.yandex.ru/album/3430269/track/28656625  
				//¯\_(ツ)_/¯
				admin.ajaxRequest({
					mguniqueurl: "action/saveTabYandexMarket",
					name: nname,  
					data: {
						format : $('.editOld select[name=format]').val(),
						company : $(".editOld input[name=company]").val(),
						uploadCurr : $(".editOld select[name=uploadCurr]").val(),
						useProps : $(".editOld input[name=useProps]").prop('checked'),
						useVariants : $(".editOld input[name=useVariants]").prop('checked'),
						useNull : $(".editOld input[name=useNull]").prop('checked'),
						useCount : $(".editOld input[name=useCount]").prop('checked'),
						storageCount: $('.editOld select[name=storageCount]').val(),
						priceSource: $('.editOld select[name=priceSource]').val(),
						inactiveToo : $(".editOld input[name=inactiveToo]").prop('checked'),
						productWithZeroPrice : $(".editOld input[name=productWithZeroPrice]").prop('checked'),
						useMarket : $(".editOld input[name=useMarket]").prop('checked'),
						useOldPrice : $(".editOld input[name=useOldPrice]").prop('checked'),
						useRelated : $(".editOld input[name=useRelated]").prop('checked'),
						propDisable : $(".editOld select[name=propDisable]").val(),
						catsType : $(".editOld select[name=catsType]").val(),
						catsSelect : $(".editOld select[name=catsSelect]").val(),
						uploadType : $(".editOld select[name=uploadType]").val(),
						skipName : $(".editOld input[name=skipName]").prop('checked'),
						useCode : $(".editOld input[name=useCode]").prop('checked'),
						useCdata : $(".editOld input[name=useCdata]").prop('checked'),
						utm : $(".editOld input[name=utm]").val(),
						skipDesc : $(".editOld input[name=skipDesc]").prop('checked'),
						useShortDesc : $(".editOld input[name=useShortDesc]").prop('checked'),
						descLength : $(".editOld input[name=descLength]").val(),
						priceRate: $(".editOld input[name=priceRate]").val(),
						salesNotes : $(".editOld textarea[name=salesNotes]").val(),
						useAdditionalCats : $(".editOld input[name=useAdditionalCats]").prop('checked'),
						useCodeLikeId: $(".editOld input[name=useCodeLikeId]").prop('checked'),
						useHook: $(".editOld input[name=useHook]").prop('checked'),
						useGroupId: $(".editOld input[name=useGroupId]").prop('checked'),
						dimensionUnits: $(".editOld select[name=dimensionUnits").val(),
						length : $(".editOld select[name=length]").val(),
						width : $(".editOld select[name=width]").val(),
						height : $(".editOld select[name=height]").val(),
						weightFrom : $(".editOld select[name=weightFrom]").val(),
						weightUnits : $(".editOld select[name=weightUnits]").val(),
						addProducts : YandexMarketModule.getRelatedProducts('add'),
						ignoreProducts : YandexMarketModule.getRelatedProductsProductRemove(),
						customTags : customTagz,
						customTagNames : customTagNamez,
						customParams : customParamz,
						customUtm : customUtmz,
						deliverys : deliveryz,
						pickups : pickupz,
                        useAsProdVariants:YandexMarketModule.useAsProdVariants('variants'),
                        useAsProd:YandexMarketModule.useAsProdVariants('prod'),
					},
				},

				function(response) {
					$('.bottomBorder').show();
					$('#downloadLink').show();
					admin.indication(response.status, lang.SAVED);
				});
				
			});

			//удаление таба
			$('.integration-container').on('click', '.editOldDelete', function() {

				var nname = $(this).attr('name');

				admin.ajaxRequest({
					mguniqueurl: "action/deleteTabYandexMarket",
					name: nname,  
				},

				function(response) {
					$('.tabs-list').children('li[name="'+nname+'"]').remove();
					$('.tabs-list').children('li[name=""]').click();
					admin.indication(response.status, lang.DELETED);
				});
				
			});

			//добавление тегов
			$('.integration-container').on('click', '.addCustomTag', function () {
				 $('.templates .customTags').clone().appendTo('.customTagsContainer');
			});

			//удаление тегов/парамов/utm
			$('.integration-container').on('click', '.deleteCustom', function () {
				 $(this).parent().parent().remove();
			});

			//изменение типа тегов
			$('.integration-container').on('click', '.customTagsContainer .changeCustomTagType', function () {
				if ($(this).attr('tagType') == 'prop') {
					$(this).attr('tagType', 'text');
					$(this).parent().parent().find('.customProp').hide();
					$(this).parent().parent().find('.customTagText').show();
				}
				else if ($(this).attr('tagType') == 'text') {
					$(this).attr('tagType', 'prop');
					$(this).parent().parent().find('.customProp').show();
					$(this).parent().parent().find('.customTagText').hide();
				}
			});

			//добавление параметров
			$('.integration-container').on('click', '.addCustomParam', function () {
				 $('.templates .customParams').clone().appendTo('.customParamsContainer');
			});

			//изменение типа параметров
			$('.integration-container').on('click', '.customParamsContainer .changeCustomParamType', function () {
				if ($(this).attr('paramType') == 'prop') {
					$(this).attr('paramType', 'text');
					$(this).parent().parent().find('.customProp').hide();
					$(this).parent().parent().find('.customParamText').show();
				}
				else if ($(this).attr('paramType') == 'text') {
					$(this).attr('paramType', 'prop');
					$(this).parent().parent().find('.customProp').show();
					$(this).parent().parent().find('.customParamText').hide();
				}
			});

			//добавление utm
			$('.integration-container').on('click', '.addCustomUtm', function () {
				 $('.templates .customUtm').clone().appendTo('.customUtmContainer');
			});

			//изменение типа utm
			$('.integration-container').on('click', '.customUtmContainer .changeUtmParamType', function () {
				if ($(this).attr('utmType') == 'prop') {
					$(this).attr('utmType', 'text');
					$(this).parent().parent().find('.customProp').hide();
					$(this).parent().parent().find('.customText').show();
				}
				else if ($(this).attr('utmType') == 'text') {
					$(this).attr('utmType', 'prop');
					$(this).parent().parent().find('.customProp').show();
					$(this).parent().parent().find('.customText').hide();
				}
			});

			//скрытие категорий
			$('.integration-container').on('change', 'select[name=catsType]', function () {
				if ($('.integration-container select[name=catsType]').val() == 'selected') {
					$('.integration-container .catsSelect').show();
					$('.integration-container .useAdditionalCats').show();
				}
				else{
					$('.integration-container .catsSelect').hide();
					$('.integration-container .useAdditionalCats').hide();
				}
				if ($('.integration-container select[name=catsType]').val() == 'fromCats') {
					$('.integration-container .useAdditionalCats').show();
				}
			});

			//скрытие характеристик
			$('.integration-container').on('click', 'input[name=useProps]', function () {
				if ($('.integration-container input[name=useProps]').prop('checked') == true) {
					$('.integration-container .propDisable').show();
				}
				else{
					$('.integration-container .propDisable').hide();
				}
			});

			$('.integration-container').on('click', 'input[name=skipDesc]', function () {
				if ($('.integration-container input[name=skipDesc]').prop('checked') == true) {
					$('.integration-container input[name=useShortDesc]').closest('.row').hide();
					$('.integration-container input[name=descLength]').closest('.row').hide();
					$('.integration-container input[name=useCdata]').closest('.row').hide();
				}
				else{
					$('.integration-container input[name=useShortDesc]').closest('.row').show();
					$('.integration-container input[name=descLength]').closest('.row').show();
					$('.integration-container input[name=useCdata]').closest('.row').show();
				}
			});

			//изменение ссылки подробнее
			$('.integration-container').on('change', 'select[name=uploadType]', function () {

				if ($('select[name=uploadType]').val() == 'custom' || $('select[name=uploadType]').val() == 'musicNvidio') {
					$('.integration-container #skipName').closest('.row').show();
				}
				else{
					$('.integration-container #skipName').closest('.row').hide();
				}
				if ($('select[name=uploadType]').val() == 'simple' || $('select[name=uploadType]').val() == 'custom' || $('select[name=uploadType]').val() == 'medicine') {
					$('.integration-container #useCode').closest('.row').show();
				}
				else{
					$('.integration-container #useCode').prop('checked', false).closest('.row').hide();
				}
			});

			//очистки и заполнения селектов
			$('.integration-container').on('click', '#clearProps', function() {
				$("select[name=propDisable] option:selected").prop("selected", false);
			});

			$('.integration-container').on('click', '#allProps', function() {
				$("select[name=propDisable] option").prop("selected", true);
			});

			$('.integration-container').on('click', '#clearCats', function() {
				$("select[name=catsSelect] option:selected").prop("selected", false);
			});

			$('.integration-container').on('click', '#allCats', function() {
				$("select[name=catsSelect] option").prop("selected", true);
			});
			//очистки и заполнения селектов////////////////////////

			//игнор товаров
			// показывает сроку поиска для связанных товаров
			$('.integration-container').on('click', '.add-related-product', function() {
				$(this).parents('li').find('.select-product-block').show();
			});

			// Удаляет связанный товар из списка связанных
			$('.integration-container').on('click', '.addProducts .add-related-product-block .remove-added-product', function() {
				$(this).parents('.product-unit').remove();
				YandexMarketModule.widthRelatedUpdateAdd();
				YandexMarketModule.msgRelated();
			});
			$('.integration-container').on('click', '.removeProducts .add-related-product-block .remove-added-product', function() {
				$(this).parents('.product-unit').remove();
				YandexMarketModule.widthRelatedUpdateRemove();
				YandexMarketModule.msgRelated();
			});

			// Закрывает выпадающий блок выбора связанных товаров
			$('.integration-container').on('click', '.add-related-product-block .cancel-add-related', function() {
				$(this).parents('li').find('.select-product-block').hide();
			});

			// Поиск товара при создании связанного товара.
			// Обработка ввода поисковой фразы в поле поиска.
			$('.integration-container').on('keyup', '.addProducts .search-block input[name=searchcat]', function() {
				admin.searchProduct($(this).val(),'.integration-container .addProducts .search-block .fastResult');
			});
			$('.integration-container').on('keyup', '.removeProducts .search-block input[name=searchcat]', function() {
				admin.searchProduct($(this).val(),'.integration-container .removeProducts .search-block .fastResult');
			});

			// Подстановка товара из примера в строку поиска связанного товара.
			$('.integration-container').on('click', '.addProducts .search-block  .example-find', function() {
				$('.section-catalog .addProducts .search-block input[name=searchcat]').val($(this).text());
				admin.searchProduct($(this).text(),'.integration-container .addProducts .search-block .fastResult');
			});
//			$('.integration-container').on('click', '.removeProducts .search-block  .example-find', function() {
//				$('.section-catalog .removeProducts .search-block input[name=searchcat]').val($(this).text());
//				admin.searchProduct($(this).text(),'.integration-container .removeProducts .search-block .fastResult');
//			});

			// Клик по найденым товарам поиска в форме добавления связанного товара.
			$('.integration-container').on('click', '.addProducts .fast-result-list a', function() {
				YandexMarketModule.addrelatedProductAdd($(this).data('element-index'));
			});
			$('.integration-container').on('click', '.removeProducts .fast-result-list a', function() {
				YandexMarketModule.addrelatedProductRemove($(this).data('element-index'));
			});
			//игнор товаров/////////////
            $('.integration-container').on('keyup', '.removeProducts #remove-product', function() {
               YandexMarketModule.findProductProductRemove($(this).val());
			});
			
			$('.integration-container').on('keyup', '.add-prod-date #add-prod-date-id', function() {
				YandexMarketModule.findProductProductRemove($(this).val(), true);
			 });

			$('.integration-container').on('click', '.product-reset', YandexMarketModule.resetProductProductRemove);
			
			// Добавление доставки для товара
			$('.integration-container').on('click', '.add-custom-delivery', function() {
				$('.templates .custom-delivery').clone().appendTo('.custom-delivery-container');
			});

			// Добавление самовывоза для товара
			$('.integration-container').on('click', '.add-custom-pickup', function() {
				$('.templates .custom-pickup').clone().appendTo('.custom-pickup-container');
			});
			
			// Удаление доставки или самовывоза для товара 
			$('.integration-container').on('click', '.delete-custom-delivery, .delete-custom-pickup', function() {
				$(this).parent().parent().remove();
			});

			// Переключение форматов доставки
			$('.integration-container select[name=format]').on('change', function() {
				YandexMarketModule.formatChanged($(this).val());
			});
			
		},

		widthRelatedUpdateAdd: function() {
			var prodWidth = $('.addProducts .product-unit').length * ($('.addProducts .product-unit').width() + 30);
			$('.addProducts .related-block').width(prodWidth);
			if($('.addProducts .product-unit').length == 0) {
				$('.addProducts .added-related-product-block').css('display','none');
			} else {
				$('.addProducts .added-related-product-block').css('display','');
			}
			if($('.addProducts .category-unit').length == 0) {
				$('.addProducts .added-related-category-block').css('display','none');
			} else {
				$('.addProducts .added-related-category-block').css('display','');
			}
		},
		widthRelatedUpdateRemove: function() {
			var prodWidth = $('.removeProducts .product-unit').length * ($('.removeProducts .product-unit').width() + 30);
			$('.removeProducts .related-block').width(prodWidth);
			if($('.removeProducts .product-unit').length == 0) {
				$('.removeProducts .added-related-product-block').css('display','none');
			} else {
				$('.removeProducts .added-related-product-block').css('display','');
			}
			if($('.removeProducts .category-unit').length == 0) {
				$('.removeProducts .added-related-category-block').css('display','none');
			} else {
				$('.removeProducts .added-related-category-block').css('display','');
			}
		},

		addrelatedProductAdd: function(elementIndex, product) {
			$('.addProducts .search-block .errorField').css('display', 'none');
			$('.addProducts .search-block input.search-field').removeClass('error-input');
			if(!product) {
				var product = admin.searcharray[elementIndex];
			}

			if (product.category_url.charAt(product.category_url.length-1) == '/') {
				product.category_url = product.category_url.slice(0,-1);
			}

			var html = YandexMarketModule.rowRelatedProduct(product);
			$('.addProducts .added-related-product-block .product-unit[data-id='+product.id+']').remove();
			$('.addProducts .related-wrapper .added-related-product-block').prepend(html);
			YandexMarketModule.widthRelatedUpdateAdd();
			YandexMarketModule.msgRelated();
			$('.addProducts input[name=searchcat]').val('');
			$('.addProducts .select-product-block').hide();
			$('.addProducts .fastResult').hide();
		},

		addrelatedProductRemove: function(elementIndex, product) {
			$('.removeProducts .search-block .errorField').css('display', 'none');
			$('.removeProducts .search-block input.search-field').removeClass('error-input');
			if(!product) {
				var product = admin.searcharray[elementIndex];
			}

			if (product.category_url.charAt(product.category_url.length-1) == '/') {
				product.category_url = product.category_url.slice(0,-1);
			}

			var html = YandexMarketModule.rowRelatedProduct(product);
			$('.removeProducts .added-related-product-block .product-unit[data-id='+product.id+']').remove();
			$('.removeProducts .related-wrapper .added-related-product-block').prepend(html);
			YandexMarketModule.widthRelatedUpdateRemove();
			YandexMarketModule.msgRelated();
			$('.removeProducts input[name=searchcat]').val('');
			$('.removeProducts .select-product-block').hide();
			$('.removeProducts .fastResult').hide();
		},

		rowRelatedProduct: function(product) {
			var price = (product.real_price) ? product.real_price : product.price;

			var html = '\
			<div class="product-unit" data-id='+ product.id +' data-code="'+ product.code +'">\
				<div class="product-img" style="text-align:center;height:50px;">\
					<a role="button" href="javascript:void(0);"><img src="' + product.image_url + '" style="height:50px;"></a>\
					<a class="remove-img fa fa-trash tip remove-added-product" href="javascript:void(0);" aria-hidden="true" data-hasqtip="88" oldtitle="Удалить" title="" aria-describedby="qtip-88"></a>\
				</div>\
				<a href="' + mgBaseDir + '/' + product.category_url + "/" + product.product_url +
					'" data-url="' + product.category_url +
					"/" + product.product_url + '" class="product-name" target="_blank" title="' +
					product.title + '">' +
					product.title + '</a>\
				<span>' + price +' '+ admin.CURRENCY+'</span>\
			</div>\
			';
			return html;
		},

		msgRelated: function() {
			if($('.added-related-product-block .product-unit').length==0&&$('.added-related-category-block .category-unit').length==0) {
				if ($('a.add-related-product.in-block-message').length==0) {
				$('.related-wrapper .added-related-product-block').append('\
				 <a class="add-related-product in-block-message" href="javascript:void(0);"><span></span></a>\
			 ');
				}
				$('.added-related-product-block').width('800px');
			}else {
				$('.added-related-product-block .add-related-product').remove();
			}
			if ($('.added-related-category-block .category-unit').length==0) {
				$('.add-related-product-block .add-related-category.in-block-message').hide();
			} else {
				$('.add-related-product-block .add-related-category.in-block-message').show();
			}
		},

		getRelatedProducts: function(action) {
			var result = '';
			if (action == 'add') {
				$('.addProducts .add-related-product-block .product-unit').each(function() {
					result += $(this).data('code') + ',';
				});
			}
			else{
				$('.removeProducts .add-related-product-block .product-unit').each(function() {
					result += $(this).data('code') + ',';
				});
			}
			
			result = result.slice(0, -1);
			return result;
		},

		drawRelatedProduct: function(relatedArr, action) {
			if (action == 'add') {
				$('.addProducts .related-block').html('');
				$('.addProducts .related-block').hide();
				relatedArr.forEach(function (product, index, array) {
					var html = YandexMarketModule.rowRelatedProduct(product);
					$('.addProducts .related-wrapper .added-related-product-block').append(html);
					YandexMarketModule.widthRelatedUpdateAdd();
				});
			}
			else{
				$('.removeProducts .related-block').html('');
				$('.removeProducts .related-block').hide();
				relatedArr.forEach(function (product, index, array) {
					var html = YandexMarketModule.rowRelatedProduct(product);
					$('.removeProducts .related-wrapper .added-related-product-block').append(html);
					YandexMarketModule.widthRelatedUpdateRemove();
				});
			}
			
			YandexMarketModule.msgRelated();
		},

		drawCustomTags: function(tagsArr) {
			$('.integration-container .customTagsContainer').html('');
			if(typeof(tagsArr) != "undefined" && tagsArr !== null) {
				tagsArr.forEach(function (index, value) {
					$('.templates .customTags').clone().appendTo('.customTagsContainer');
					$('.integration-container .customTagsContainer div.customTags:last input[name=customTagName]').val(index.name);

					if (index.type == 'prop') {
						$('.integration-container .customTagsContainer div.customTags:last select[name=customProp]').val(index.val);
					}
					else if (index.type == 'text') {
						$('.integration-container .customTagsContainer div.customTags:last .changeCustomTagType').click();
						$('.integration-container .customTagsContainer div.customTags:last input[name=customTagText]').val(index.val);
					}

					$('.integration-container .customTagsContainer div.customTags:last input[name=customTagAttributes]').val(index.attributes);
				});
			}
		},

		drawCustomParams: function(paramsArr) {
			$('.integration-container .customParamsContainer').html('');
			if(typeof(paramsArr) != "undefined" && paramsArr !== null) {
				paramsArr.forEach(function (index, value) {
					$('.templates .customParams').clone().appendTo('.customParamsContainer');
					$('.integration-container .customParamsContainer div.customParams:last input[name=customParamName]').val(index.name);

					if (index.type == 'prop') {
						$('.integration-container .customParamsContainer div.customParams:last select[name=customProp]').val(index.val);
					}
					else if (index.type == 'text') {
						$('.integration-container .customParamsContainer div.customParams:last .changeCustomParamType').click();
						$('.integration-container .customParamsContainer div.customParams:last input[name=customParamText]').val(index.val);
					}
				});
			}
		},

		drawCustomUtm: function(utmArr) {
			$('.integration-container .customUtmContainer').html('');
			if(typeof(utmArr) != "undefined" && utmArr !== null) {
				utmArr.forEach(function (index, value) {
					$('.templates .customUtm').clone().appendTo('.customUtmContainer');
					$('.integration-container .customUtmContainer div.customUtm:last input[name=utmName]').val(index.name);

					if (index.type == 'prop') {
						$('.integration-container .customUtmContainer div.customUtm:last select[name=customProp]').val(index.val);
					}
					else if (index.type == 'text') {
						$('.integration-container .customUtmContainer div.customUtm:last .changeUtmParamType').click();
						$('.integration-container .customUtmContainer div.customUtm:last input[name=customUtmText]').val(index.val);
					}
				});
			}
		},

		drawDeliverys: function(delivsArr) {
			
			$("tbody[name=deliverys] input[type=checkbox]").prop('checked', false);
			$("tbody[name=deliverys] input[type=text]").val('');
			$("tbody[name=deliverys] tr").removeClass('selected');
			if(typeof(delivsArr) != "undefined" && delivsArr !== null) {
				let html = '';
				delivsArr.forEach(function (index, value) {
					if(typeof index.code == 'undefined'){
						index.code = '';
					}
					html += '<tr>' ;
					html += '<td><input type="text" name="delivCost" value="'+index.cost+'"></td>' ;
					html += '<td><input type="text" name="delivTime" value="'+index.time+'"></td>' ;
					html += '<td><input type="text" name="delivBefore" value="'+index.before+'"></td>' ;
					html += '<td><input type="text" name="delivCode" value="'+index.code+'"></td>';
					html += '<td><a class="link delete-custom-delivery" href="javascript:void(0);"><i class="fa fa-minus-circle" aria-hidden="true"></i></a></td>';
					html += '</tr>' ;
				});
				$('.integration-container .deliverys').html(html);
			}
		},

		drawPickups: function(pickupsArr) {
			$("tbody[name=pickups] input[type=checkbox]").prop('checked', false);
			$("tbody[name=pickups] input[type=text]").val('');
			$("tbody[name=pickups] tr").removeClass('selected');

			if (typeof(pickupsArr) !== 'undefined' && pickupsArr) {
				let html = '';
				pickupsArr.forEach(function (index, value) {
					if (typeof index.code == 'undefined') {
						index.code = '';
					}
					html += '<tr>' ;
						html += '<td><input type="text" name="pickupTime" value="'+index.time+'"></td>' ;
						html += '<td><input type="text" name="pickupBefore" value="'+index.before+'"></td>' ;
						html += '<td><input type="text" name="pickupCode" value="'+index.code+'"></td>';
						html += '<td><a class="link delete-custom-pickup" href="javascript:void(0);"><i class="fa fa-minus-circle" aria-hidden="true"></i></a></td>';
						html += '</tr>' ;
				});
				$('.integration-container .pickups').html(html);
			}
		},

		hideTrash: function() {
			if ($('.integration-container select[name=catsType]').val() == 'selected') {
				$('.integration-container .catsSelect').show();
				$('.integration-container .useAdditionalCats').show();
			}
			else{
				$('.integration-container .catsSelect').hide();
				$('.integration-container .useAdditionalCats').hide();
			}
			if ($('.integration-container select[name=catsType]').val() == 'fromCats') {
				$('.integration-container .useAdditionalCats').show();
			}
			if ($('.integration-container input[name=useProps]').prop('checked') == true) {
				$('.integration-container .propDisable').show();
			}
			else{
				$('.integration-container .propDisable').hide();
			}
			if ($('.integration-container input[name=skipDesc]').prop('checked') == true) {
				$('.integration-container input[name=useShortDesc]').closest('.row').hide();
				$('.integration-container input[name=descLength]').closest('.row').hide();
				$('.integration-container input[name=useCdata]').closest('.row').hide();
			}
			else{
				$('.integration-container input[name=useShortDesc]').closest('.row').show();
				$('.integration-container input[name=descLength]').closest('.row').show();
				$('.integration-container input[name=useCdata]').closest('.row').show();
			}
			if ($(".editOld select[name=catsType]").val() == null) {
				//$(".editOld select[name=catsType]").val('fromCats');
				$(".editOld select[name=catsType]").val('all');
			}
			if ($(".editOld select[name=catsType]").val() == 'fromCats') {
				//$(".editOld select[name=catsType]").val('fromCats');
				$(".editOld select[name=catsType]").val('all');
			}
			if ($(".editOld select[name=uploadType]").val() == null) {
				$(".editOld select[name=uploadType]").val('simple');
			}
			switch($('select[name=uploadType]').val()) {
				case 'custom':
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'vendor-model.html');
					break;
				case 'books':
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'books.html');
					break;
				case 'audiobooks':
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'audiobooks.html');
					break;
				case 'musicNvidio':
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'music-video.html');
					break;
				case 'medicine':
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'medicine.html');
					break;
				case 'tickets':
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'event-tickets.html');
					break;
				case 'tours':
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'tours.html');
					break;
				default:
					$('#ymlMoarLink').attr('href', $('#ymlMoarLink').attr('defaul')+'offers.html');
			}
		},

		drawCheckbox: function(resp, name) {
			if (resp == 'true') {
				$(".editOld input[name="+name+"]").prop('checked', true);
			}
			else {
				$(".editOld input[name="+name+"]").prop('checked', false);
			}
		},

		updateLink: function(name) {
			$('#ymlLink').attr('href', $('#ymlLink').attr('defaul')+name);
			$('#ymlLink').text($('#ymlLink').attr('defaul')+name);
			$('#downloadLink').attr('href', $('#downloadLink').attr('defaul')+name);
		},
        
        findProductProductRemove:function(request, findeForDate=false){
            if (request.length < 3){ 
              $('.searchResult').html('').css({'opacity': 0, 'border':'1px solid #ccc'});
              
              return;
            } 
           // console.log(request);

            admin.ajaxRequest({
                    mguniqueurl: "action/findProductProductRemove", // действия для выполнения на сервере
                    request: request,
                },

                function(response) {
                    var data = response.data;
                    var html = '';
                    $(data.items).each(function(i, e) {
                        flag = '';
                        flag1 = '';
                        if (e.variants.length != 0) {
                            flag = '<div class="variant"><i class="fa fa-plus"></i></div>';
                            flag1 = ' with-vars';
                            html += '<div class="item' + flag1 + '" data-item="' + e.id + '">' + flag +
                                '<div class="img"><img src="' +
                                e.image_url +
                                '" /></div><div class="title">' + e.title + ' ' + e.code +
                                '</div><div class="price">' +
                                e.price +
                                ' ' + data.currency + '</div>' +
                                '<div style="clear: both;"></div></div><ul data-id="' + e.id + '">';
                            Object.keys(e.variants).forEach(function(item) {
                             //  console.log($('.editOld #useVariants').val());
                                var useVariants = $('.editOld #useVariants').val()
                                  html += '<li data-item="' +
                                      e.id + '" data-var="' + e.variants[item].id + '" '+ ((e.variants[item].code == e.code)? ' data-prod="true"': '') +' ><div class="img">' +
                                      '<img src="' +
                                      e.variants[item].image +
                                      '"/></div><div class="title">' +
                                      e.title + ' ' +
                                      e.variants[item].title_variant + ' ' + e.variants[item].code + 
                                      '</div><div class="price">' + e.variants[item].price + ' ' + data.currency +
                                      '</div></li>';
                            });
                            html += '</ul>';

                        } else {

                            html +=
                                '<div class="item' + flag1 + '" data-item="' + e.id + '">' + flag +
                                '<div class="variant">&nbsp;</div>' + '<div class="img"><img src="' +
                                e.image_url +
                                '" /></div><div class="title">' + e.title + ' ' + e.code +
                                '</div><div class="price">' +
                                e.price +
                                ' ' + data.currency + '</div>' +
                                '</div>';
                        }
                    });
                    if (html != '') $('.searchResult').html(html).css('opacity', 1);
                    $('.searchResult div.item, .searchResult li').on('click', YandexMarketModule.addProductProductRemove);

                    //console.log(data);
                });
        },
        
        addProductProductRemove:function(){
            var id = $(this).data('item');
            if ($(this).hasClass('with-vars') == true && $('.variant:hover').length != 0) {
                $('body').find('.searchResult ul[data-id="' + id + '"]').toggle();
                return;
            }
            var productId = $(this).data('item');
            var variantId = $(this).data('var');
            if($(this).hasClass('with-vars') == true){
              variantId = 'false';
            }
            
            //если одинаковые артикулы у товраа и варианты, рисуем галочку, импользовать как вариант
            var prodUseAsVariant = $(this).data('prod');
        //    console.log(prodUseAsVariant);
            $('.searchResult').html('').css('opacity', 0);
            admin.ajaxRequest({
              mguniqueurl: "action/addProductProductRemove", // действия для выполнения на сервере
              id: productId,
              var : variantId,
            },
              function (response) {
                var data = response.data;
                var codeInContent =  $('.product-content .product .code');
                for(let i=0; i<codeInContent.length; i++){
                  if($(codeInContent[i]).html().trim() == data.product.code.trim()){
                    return false;
                  }
                }
                $('[name="product_variant"]').val(variantId);
                var html = '<div class="product"><input type="hidden" name="product_id" value="' + data.product.id + '" />\
                              <div class="img"><img src="' +
                        data.product.image_url +
                        '" /></div><div class="title">' + data.product.title + ' ' + data.product.code +
                        '</div>' +
                        ((typeof prodUseAsVariant != 'undefined')? ('<div class="use-as-prod-var " data-varant="true" data-code="'+data.product.code+'">'+
                                        
										'</div>'): '<div class="use-as-prod-var " data-varant="'+variantId+'" data-code="'+data.product.code+'"> </div>')+
                        '<div class="code"> '+data.product.code+' </div>' +
                        '<div class="price">' +
                        data.product.price +
                        ' ' + data.currency + '</div><div class="product-reset"><i class="fa fa-close"></i>' +
                        '</div></div>';
                $('.product-content').append(html);
                
              });
        },
        
        resetProductProductRemove:function(){
            $(this).parent().remove();
        },
        
        getRelatedProductsProductRemove:function(){
          	var result = '';		
            $('.product-content .product .code').each(function() {
              result += $(this).html().trim()+',';
            });
			result = result.slice(0, -1);
            return result;
        },
        
        useAsProdVariants:function(prodOrVar){
          var useAsProdVar = $('.product-content .product').find('.use-as-prod-var');
          var productVariants = '';
          var product = '';
          $(useAsProdVar).each(function() {
            if($(this).data('varant') == true){
              productVariants += $(this).data('code').trim()+ ',';
            }else if($(this).data('varant') == false){
              product += $(this).data('code').toString().trim()+ ',';
            }
          });
          productVariants = productVariants.slice(0, -1);
          product = product.slice(0, -1);
          if(prodOrVar == 'prod'){
            return product; 
          }else if(prodOrVar == 'variants'){
            return productVariants;
          }
        },
        
        drawRelatedProductProductRemove:function(relatedArr, useAsProdVariants){
          var html = '';
          if(relatedArr.length > 0){
          if(useAsProdVariants){
            useAsProdVariants = useAsProdVariants.split(',');  
          }
          $(relatedArr).each(function() {
           //console.log(useAsProdVariants.indexOf($(this)[0].pvcode));
            html += '<div class="product"><input type="hidden" name="product_id" value="' + $(this)[0].id + '" />\
                              <div class="img"><img src="' +
                        $(this)[0].image_url +
                        '" /></div><div class="title ">' +  (($(this)[0].pvcode != 'false') ? $(this)[0].title+' '+$(this)[0].title_variant : $(this)[0].title)
                        + ' ' + (($(this)[0].pvcode && $(this)[0].pvcode != 'false') ?$(this)[0].pvcode:$(this)[0].pcode)  +
                        '</div>' +
                                              ((typeof $(this)[0].pvcode != 'undefined' && ($(this)[0].pvcode == $(this)[0].pcode) && useAsProdVariants.indexOf($(this)[0].pvcode) != -1)? ('<div class="use-as-prod-var" data-varant="true" data-code="'+ ($(this)[0].pvcode ?$(this)[0].pvcode:$(this)[0].pcode)  +'">'+
										'</div>'): '<div class="use-as-prod-var" '+(($(this)[0].pvcode == 'false')?'data-varant="false" data-code="'+$(this)[0].pcode+'"':'')+'></div>')+
                        '<div class="code"> '+(($(this)[0].pvcode && $(this)[0].pvcode != 'false')?$(this)[0].pvcode:$(this)[0].pcode)+' </div>' +
                        '<div class="price">' +
                        $(this)[0].price +
                        ' </div><div class="product-reset"><i class="fa fa-close"></i>' +
                        '</div></div>';
                
          });
          $('.product-content').html(html).css('height', '300px');
          }else{
            $('.product-content').html(html);
          }
        },
		formatChanged: function(format) {
			$('.integration-container [data-yml-show]').each(function() {
				const formats = $(this).data('yml-show');
				if (!formats) {
					return;
				}
				const formatsArr = formats.split(',');
				if (!formatsArr) {
					return;
				}
				if (formatsArr.includes(format)) {
					$(this).show();
					return;
				}
				$(this).hide();
			});
		}
	};
})();