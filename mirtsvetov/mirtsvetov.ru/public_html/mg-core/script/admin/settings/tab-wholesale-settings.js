var settings_wholesale = (function () {
	return {
		search: '',
		selectedProductId: '',
		selectedVariantId: 0,
		page: 1,
		categoryId: 0,
		selectedGroup: 1,
		toVariants: 1,
		searchInterval: null,
		init: function() {
			settings_wholesale.loadList();
			settings_wholesale.getWholesaleList();
			settings_wholesale.selectedGroup = $('#tab-wholesale-settings .wholesalesGroup .template-tabs.success').data('id');
			settings_wholesale.initEvents();
			settings_wholesale.searchInterval = setInterval(settings_wholesale.searchIntervalFunction, 1000);
		},
		initEvents: function() {
			$('#tab-wholesale-settings').on('click', '.wholesalesGroup .template-tabs', function() {
				$('#tab-wholesale-settings .wholesalesGroup .template-tabs').removeClass('success');
				$(this).addClass('success');
				settings_wholesale.selectedGroup = $(this).data('id');
				settings_wholesale.loadRule();
			});

			$('#tab-wholesale-settings').on('click', '.editWholeRule', function() {
				settings_wholesale.selectedProductId = $(this).parents('.product').data('product-id');
				settings_wholesale.selectedVariantId = $(this).parents('.product').data('variant-id');
				settings_wholesale.selectedGroup = $(this).data('group');
				$('.js-add-prod-name').val($(this).data('name'));
				$('.js-add-retail-price').val($(this).data('price') + ' ' + $(this).data('cur'));
				$('.js-add-whole-type').text(lang.WHOLE_GROUP + ' ' + $(this).data('group'));
				$('#tab-wholesale-settings .product').removeClass('selected');
				$(this).addClass('selected');
				admin.openModal('#editWholePriceModal');
				settings_wholesale.loadRule();
				$('#tab-wholesale-settings .edit-field').show();
				$('#tab-wholesale-settings .curr').html($(this).data('cur'));
			});

			$('#tab-wholesale-settings').on('click', '.addField', function() {
				settings_wholesale.addField();
			});

			$('#tab-wholesale-settings').on('click', '.save-button', function() {
				settings_wholesale.saveRule();
			});

			$('#tab-wholesale-settings').on('click', '.deleteLineRule', function() {
				if(confirm('Удалить?')) {
					$(this).parents('.rule').detach();
				}
			});

			$('#tab-wholesale-settings').on('click', '.wholesale-onlyNotSetPrice', function() {
				settings_wholesale.loadList();
			});

			$('#tab-wholesale-settings').on('click', '.newNavigator', function() {
				settings_wholesale.page = admin.getIdByPrefixClass($(this), 'page');
				settings_wholesale.loadList();
			});

			$('#tab-wholesale-settings').on('change', '.category-select', function() {
				settings_wholesale.categoryId = $(this).val();
				settings_wholesale.page = 1;
				settings_wholesale.loadList();
			});

			$('#tab-wholesale-settings').on('change', '[name=sale-type]', function() {
				$(this).data('type', $(this).val());
				$('#tab-wholesale-settings .text-type').text($('#tab-wholesale-settings [name=sale-type] option[value='+$(this).val()+']').text());
				settings_wholesale.saveType();
			});

			$('#tab-wholesale-settings').on('change', '#setVariantsToo', function() {
				if($(this).prop('checked')) {
					settings_wholesale.toVariants = 1;
				} else {
					settings_wholesale.toVariants = 0;
				}
				settings_wholesale.loadList();
			});

			$('#tab-wholesale-settings').on('click', '.addWholesalesGroup', function() {
				settings_wholesale.addGroup();
				// $('wholesalesGroup').append('<div class="button primary template-tabs" data-id="'.$value.'">Цена '.$value.'</div>');
			});

			$('#tab-wholesale-settings').on('click', '.deleteWholePrice', function() {
				if(!confirm('Удалить цену?')) return false;
				id = $(this).data('id');
				$(this).parents('tr').detach();
				settings_wholesale.deleteGroup(id);
			});

			$('#tab-wholesale-settings').on('change', '.setToWholesaleGroup', function() {
				id = $(this).parents('tr').data('id');
				group = $(this).val();
				settings_wholesale.setToWholesaleGroup(id, group);
			});

			$('#tab-wholesale-settings').on('click', '.apply-massive-holy', function() {
				data = {};
				data['cats'] =	$("select[name=catsSelect]").val(),
				data['group'] = $('.massive-holy-group').val();
				data['coof'] = $('.massive-holy-coof').val();
				data['count'] = $('.massive-holy-count').val();
				$('.apply-massive-holy').html(lang.WHOLESALES_MASSIVE_PROGGRESS+': 0%');
				settings_wholesale.setMassiveHoly(data);
				settings_wholesale.getWholesaleList();
			});

			$('#tab-wholesale-settings').on('click', '.apply-massive-op', function() {
				data = {};
				data['group'] = $('.massive-op-group').val();
				data['field'] = $('.massive-op-field').val();
				data['count'] = $('.massive-op-count').val();
				$('.apply-massive-op').html(lang.WHOLESALES_MASSIVE_PROGGRESS+': 0%');
				settings_wholesale.setMassiveOp(data);
				settings_wholesale.getWholesaleList();
			});

			$('#tab-wholesale-settings').on('click', '.show-mass-holy', function() {
				$('.show-mass-holy-target').show();
			});

			$('#tab-wholesale-settings').on('click', '.show-mass-op', function() {
				$('.show-mass-op-target').show();
			});

			$('#tab-wholesale-settings').on('click', '.show-mass-delete', function() {
				$('.show-mass-delete-target').toggle();
				$('.show-mass-delete-target .text-type').text($('#tab-wholesale-settings [name=sale-type] option[value='+$('#tab-wholesale-settings [name=sale-type]').val()+']').text());
			});

			$('#tab-wholesale-settings').on('click', 'a#allProps', function() {
				$("select[name=catsSelect] option").prop("selected", true);
			});

			$('#tab-wholesale-settings').on('click', 'a#clearProps', function() {
				$("select[name=catsSelect] option:selected").prop("selected", false);
			});

			$('#tab-wholesale-settings').on('click', '.js-delete-wholesale-set', function() {
				var count = $(this).data('count');
				var group = $(this).data('group');
				var tr = $(this).parents('tr');

				if(!confirm('Вы уверены, что хотите удалить все оптовые цены для количества "'+count+'" у группы "'+group+'"')) {
					return false;
				}

				admin.ajaxRequest({
					mguniqueurl: "action/deleteMassiveWholesale", // действия для выполнения на сервере  
					count: count,
					group: group,
				},
				function(response) {
					admin.indication(response.status, response.msg);
					tr.detach();
					$('.show-mass-delete-target').hide();
					settings_wholesale.loadList();
					settings_wholesale.getWholesaleList();
				});
			});
		},
		getWholesaleList: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/getMassiveWholesale"
			},
			function(response) {
				console.log(response)
				if(response.data.length == 0) {
					$('.show-mass-delete').hide();
					$('.mass-delete-list tbody').html('');
				} else {
					$('.show-mass-delete').show();
					var html = '';
					response.data.forEach(element => {
						html += '<tr>\
						<td>'+element.count+'</td>\
						<td>Оптовая цена '+element.group+'</td>\
						<td>\
							<a 	href="javascript:void(0);" \
								class="js-delete-wholesale-set fl-right"\
								data-count="'+element.count+'" \
								data-group="'+element.group+'">\
								<i class="fa fa-trash"></i>\
							</a>\
						</td>\
						</tr>';						
					});
					$('.mass-delete-list tbody').html(html);
				}
			});
		},
		searchIntervalFunction: function() {
			if (!$('#tab-wholesale-settings .wholesale-search').length) {
				clearInterval(settings_wholesale.searchInterval);
			}
			if(settings_wholesale.search != $('#tab-wholesale-settings .wholesale-search').val()) {
				settings_wholesale.search = $('#tab-wholesale-settings .wholesale-search').val();
				settings_wholesale.page = 1;
				settings_wholesale.loadList();
			}
		},
		setMassiveOp: function(data) {
			admin.ajaxRequest({
				mguniqueurl: "action/setMassiveOp", // действия для выполнения на сервере  
				data: data,
			},
			function(response) {
				if(response.data.percent != 100) {
					setTimeout(function() {
						settings_wholesale.setMassiveOp(response.data);
					}, 1000);
					$('.apply-massive-op').html(lang.WHOLESALES_MASSIVE_PROGGRESS+': '+response.data.percent+'%');
				} else {
					$('.apply-massive-op').html(lang.WHOLESALES_MASSIVE_APPLY);
					admin.indication(response.status, response.msg);
					$('.show-mass-op-target').hide();
					$('.show-mass-op').show();
				}
			});
		},
		setMassiveHoly: function(data) {
			admin.ajaxRequest({
				mguniqueurl: "action/setMassiveHoly", // действия для выполнения на сервере  
				data: data,
			},      
			function(response) {
				if(response.data.percent != 100) {
					setTimeout(function() {
						settings_wholesale.setMassiveHoly(response.data);
					}, 1000);
					$('.apply-massive-holy').html(lang.WHOLESALES_MASSIVE_PROGGRESS+': '+response.data.percent+'%');
				} else {
					$('.apply-massive-holy').html(lang.WHOLESALES_MASSIVE_APPLY);
					admin.indication(response.status, response.msg);
					$('.show-mass-holy-target').hide();
					$('.show-mass-holy').show();
				}
			});
		},
		setToWholesaleGroup: function(id, group) {
			admin.ajaxRequest({
				mguniqueurl: "action/setToWholesaleGroup", // действия для выполнения на сервере  
				id: id,
				group: group,
			},      
			function(response) {
				admin.indication(response.status, response.msg);
			});
		},
		deleteGroup: function(id) {
			admin.ajaxRequest({
				mguniqueurl: "action/deleteWholesaleGroup", // действия для выполнения на сервере  
				id: id  
			},      
			function(response) {
				$('.userList').html(response.data.htmlUser);
				$('.priceList').html(response.data.htmlPrice);
				$('.productsHead').html('<tr>'+response.data.productsHead+'</tr>');
				$('.massive-holy-group').html($('.setToWholesaleGroup:eq(0)').html()).find('option:eq(0)').detach();
				settings_wholesale.loadList();
			});
		},
		addGroup: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/addWholesaleGroup", // действия для выполнения на сервере    
			},      
			function(response) {
				$('.userList').html(response.data.htmlUser);
				$('.priceList').html(response.data.htmlPrice);
				$('.productsHead').html('<tr>'+response.data.productsHead+'</tr>');
				$('.massive-holy-group').html($('.setToWholesaleGroup:eq(0)').html()).find('option:eq(0)').detach();
				settings_wholesale.loadList();
			});
		},
		loadList: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/loadWholesaleList", // действия для выполнения на сервере    
				search: settings_wholesale.search,
				only: $('#tab-wholesale-settings .wholesale-onlyNotSetPrice').prop('checked'),
				page: settings_wholesale.page,
				category: settings_wholesale.categoryId,
				group: settings_wholesale.selectedGroup,
				variants: settings_wholesale.toVariants,
			},      
			function(response) {
				$('#tab-wholesale-settings .product-list').html(response.data.html);
				$('#tab-wholesale-settings .table-pagination').html(response.data.pager);
				$('#tab-wholesale-settings .linkPage').addClass('newNavigator').removeClass('linkPage');
			});
		},
		loadRule: function() {
			var data = {};
			data['productId'] = settings_wholesale.selectedProductId;
			data['variantId'] = settings_wholesale.selectedVariantId;
			admin.ajaxRequest({
				mguniqueurl: "action/loadWholesaleRule", // действия для выполнения на сервере    
				data: data,
				group: settings_wholesale.selectedGroup,     
			},      
			function(response) {
				$('#tab-wholesale-settings .rule-list').html(response.data);
				// $('#tab-wholesale-settings [name=sale-type]').val($('#tab-wholesale-settings [name=sale-type]').data('type'));
				$('#tab-wholesale-settings .text-type').text($('#tab-wholesale-settings [name=sale-type] option[value='+$('#tab-wholesale-settings [name=sale-type]').val()+']').text());
			});
		},
		addField: function() {
			$('.toDel').detach();
			$('#tab-wholesale-settings .rule-list').append('\
				<tr class="rule">\
					<td><input type="text" name="count" placeholder="'+lang.EXAMPLE_2+'"></td>\
					<td><input type="text" name="price" placeholder="'+lang.EXAMPLE_4+'"></td>\
					<td class="text-right"><button title="Удалить строку" class="deleteLineRule"><i class="fa fa-trash" aria-hidden="true"></i></button></td>\
				</tr>');
		},
		saveRule: function(closeModal) {
			closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
			var data = {};
			$('#tab-wholesale-settings .rule').each(function(index) {
				data[index] = {};
				data[index]['count'] = $(this).find('[name=count]').val();
				data[index]['price'] = $(this).find('[name=price]').val().replace(',', '.');
			});
			admin.ajaxRequest({
				mguniqueurl: "action/saveWholesaleRule", // действия для выполнения на сервере    
				data: data,
				product: settings_wholesale.selectedProductId,
				variant: settings_wholesale.selectedVariantId,
				group: settings_wholesale.selectedGroup,
				variants: settings_wholesale.toVariants,
			},      
			function(response) {
				admin.indication(response.status, response.msg);
				if (closeModal) {
					admin.closeModal('#editWholePriceModal');
				} else {
					$('#editWholePriceModal').attr('data-refresh', 'true');
				}
			});
		},
		saveType: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/saveWholesaleType", // действия для выполнения на сервере    
				type: $('#tab-wholesale-settings [name=sale-type]').val()
			},      
			function(response) {
				admin.indication(response.status, response.msg);
			});
		},
	};
})();