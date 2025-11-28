var settings_opfields = {
	init: function() {
		settings_opfields.orderInit();
		settings_opfields.userInit();
		settings_opfields.productInit();
		settings_opfields.categoryInit();
	},

	// ===================================================================================
	// ============================ для полей категорий ==================================
	// ===================================================================================

	categoryInit: function() {
		$('#tab-opfields-settings').on('click', '.category-part .addCategoryOp', function() {
			$('#tab-opfields-settings .category-part .toDel').detach();

			var id = 0;
			$('#tab-opfields-settings .category-part .category-op-list tr').each(function(index,element) {
				var currId = parseInt($(this).data('id'));
				if (currId > id) {
					id = currId;
				}
			});
			id++;

			$('.category-op-list').append($('.category-template-row tbody').html());
		});

		$('#tab-opfields-settings').on('click', '.category-part .js-field-delete', function() {
			if(!confirm(lang.DELETE+'?')) return true;
			$(this).closest('tr').detach();
			settings_opfields.saveOp_category();
		});

		$('#tab-opfields-settings').on('click', '.category-part .save-button', function() {
			settings_opfields.saveOp_category();
		});
	},

	saveOp_category: function() {
		var data = {};
		var fail = false;
		$('[name=name]').removeClass('error-input');
		$('.category-op-list tr').each(function(index) {
			data[index] = {};
			data[index].id = $(this).data('id');
			data[index].name = $(this).find('[name=name]').val();
			data[index].sort = index;

			if (!$(this).find('[name=name]').val()) {
				$(this).find('[name=name]').addClass('error-input');
				fail = true;
			}
		});
		if (fail) {return false;}
		admin.ajaxRequest({
			mguniqueurl: "action/saveCategoryOp",
			data: data,
		},
		function(response) {
			admin.indication(response.status, response.msg);
			$('#tab-opfields-settings .category-op-list').html(response.data);
		});
	},

	// ===================================================================================
	// ============================ для полей с товарами =================================
	// ===================================================================================

	productInit: function() {

		$('#tab-opfields-settings').on('click', '.product-part .addProductOp', function() {
			$('#tab-opfields-settings .product-part .toDel').detach();

			var id = 0;
			$('#tab-opfields-settings .product-part .product-op-list tr').each(function(index,element) {
				var currId = parseInt($(this).find('.checkbox').find('label').attr('for').replace('prodIsPrice-', ''));
				if (currId > id) {
					id = currId;
				}
			});
			id++;

			$('#tab-opfields-settings .product-template-row .checkbox [name=isPrice]').attr('class', 'prodIsPrice-'+id).attr('id', 'prodIsPrice-'+id);
			$('#tab-opfields-settings .product-template-row .checkbox label').attr('for', 'prodIsPrice-'+id);
			$('.product-op-list').append($('.product-template-row tbody').html());
		});

		$('#tab-opfields-settings').on('click', '.product-part .js-field-delete', function() {
			if(!confirm(lang.DELETE+'?')) return true;
			$(this).closest('tr').detach();
			settings_opfields.saveOp_product();
		});

		$('#tab-opfields-settings').on('change', '.product-part [type=checkbox]', function() {
			$('#tab-opfields-settings .product-part tr').removeClass('selected');
		});

		$('#tab-opfields-settings').on('click' , '.product-part .fa-eye', function() {
			$(this).toggleClass('active');
			settings_opfields.saveOp_product();
		});

		$('#tab-opfields-settings').on('click', '.product-part .save-button', function() {
			settings_opfields.saveOp_product();
		});
	},

	saveOp_product: function() {
		var data = {};
		var fail = false;
		$('[name=name]').removeClass('error-input');
		$('.product-op-list tr').each(function(index) {
			data[index] = {};
			data[index]['id'] = $(this).data('id');
			data[index]['name'] = $(this).find('[name=name]').val();
			data[index]['isPrice'] = $(this).find('[name=isPrice]').prop('checked') ? 1 : 0;
			data[index]['sort'] = index;
			if($(this).find('.fa-eye').hasClass('active')) {
				data[index]['active'] = 1;
			} else {
				data[index]['active'] = 0;
			}
			if (!$(this).find('[name=name]').val()) {
				$(this).find('[name=name]').addClass('error-input');
				fail = true;
			}
		});
		if (fail) {return false;}
		admin.ajaxRequest({
			mguniqueurl: "action/saveProductOp",
			data: data,
		},
		function(response) {
			admin.indication(response.status, response.msg);
			$('#tab-opfields-settings .product-op-list').html(response.data);
			window.rebootWholesale = true;
		});
	},

	// ===================================================================================
	// ========================= для полей с пользователями ==============================
	// ===================================================================================

	userInit: function() {

		$('#tab-opfields-settings').on('click' , '.user-part .addUserOp', function() {
			$('.user-part .toDel').detach();
			$('.user-op-content').append($('.user-template-row tbody').html());
		});

		$('#tab-opfields-settings').on('change' , '.user-part .user-op-content select', function() {
			switch ($(this).val()) {
				case 'input':
				case 'textarea':
				case 'checkbox':
					$(this).closest('tr').find('.openPopup').hide();
					break;

				default:
					$(this).closest('tr').find('.openPopup').show();
			}
		});

		$('#tab-opfields-settings').on('click' , '.user-part .user-op-content .add-popup-field', function() {
			$(this).closest('tr').find('.field-variant').append('<p class="field"><input type="text" placeholder="'+lang.OP_FILEDS_VAR_NEW+'" title="'+lang.OP_FILEDS_VAR_NEW+'"><button title="'+lang.DELETE+'" aria-label="'+lang.DELETE+'" class="js-field-var-delete field__var-delete"><i class="fa fa-trash" aria-hidden="true"></button></p>');
		});

		$('#tab-opfields-settings').on('click' , '.js-popup-close', function() {
			$(this).closest('.field-variant-popup').hide();
		});

		$('#tab-opfields-settings').on('click' , '.user-part .user-op-content .apply-popup', function() {
			$(this).closest('.field-variant-popup').hide();
			settings_opfields.saveOp_user();
		});

		$('#tab-opfields-settings').on('click' , '.user-part .user-op-content .field-variant .fa-times', function() {
			$(this).closest('.field').detach();
		});

		$('#tab-opfields-settings').on('click' , '.user-part .btn-eye', function() {
			$(this).children('.fa').toggleClass('active');
			settings_opfields.saveOp_user();
		});

		$('#tab-opfields-settings').on('click' , '.user-part .js-field-delete', function() {
			if(!confirm(lang.DELETE+'?')) return false;
			$(this).closest('tr').detach();
			settings_opfields.saveOp_user();
		});

		$('#tab-opfields-settings').on('click', '.user-part .save-button', function() {
			settings_opfields.saveOp_user();
		});
	},

	saveOp_user: function() {
		var data = {};
		var fail = false;
		$('[name=name]').removeClass('error-input');
		$('.user-op-content tr').each(function(index) {
			data[index] = {};
			data[index]['id'] = $(this).data('id');
			data[index]['name'] = $(this).find('[name=name]').val();
			data[index]['placeholder'] = $(this).find('[name=placeholder]').val();
			data[index]['type'] = $(this).find('[name=type]').val();
			data[index]['sort'] = index;
			$(this).find('.field-variant .field').each(function(innerIndex) {
				if(!$(this).find('input').val()) return true;
				if(data[index]['vars'] == undefined) data[index]['vars'] = {};
				data[index]['vars'][innerIndex] = $(this).find('input').val();
			});
			if($(this).find('.fa-eye').hasClass('active')) {
				data[index]['active'] = 1;
			} else {
				data[index]['active'] = 0;
			}
			if (!$(this).find('[name=name]').val()) {
				$(this).find('[name=name]').addClass('error-input');
				fail = true;
			}
		});
		if (fail) {return false;}
		admin.ajaxRequest({
			mguniqueurl: "action/saveUserOp",
			data: data,
		},      
		function(response) {
			admin.indication(response.status, response.msg);
			$('#tab-opfields-settings .user-op-content').html(response.data);
			window.rebootOrderForm = true;
		});
	},

	// ===================================================================================
	// =========================== для полей с заказами ==================================
	// ===================================================================================

	orderInit: function() {
		$('#tab-opfields-settings').on('click', '.order-part .addField', function () {
			settings_opfields.printFieldsRow_order();
		});

		$('#tab-opfields-settings').on('change', '.order-part [name=type]', function () {
			$('.field-variant-popup').hide();
			settings_opfields.showCog($(this).closest('tr'));
		});

		$('#tab-opfields-settings').on('click', '.openPopup', function () {
			$('.field-variant-popup').hide();
			$(this).closest('tr').find('.field-variant-popup').show();
		});

		$('#tab-opfields-settings').on('click', '.order-part .add-popup-field', function () {
			settings_opfields.addPopupField_order($(this).closest('tr'));
		});

		$('#tab-opfields-settings').on('click', '.order-part .js-field-delete', function () {
			if(confirm(lang.DELETE+'?')) {
				$(this).closest('tr').detach();
				settings_opfields.saveOptionalFields_order();
			}
		});

		$('#tab-opfields-settings').on('click', '.js-field-var-delete', function () {
			$(this).parent().detach();
		});

		$('#tab-opfields-settings').on('click', '.order-part .apply-popup', function () {
			$(this).parents('.field-variant-popup').hide();
			settings_opfields.saveOptionalFields_order();
		});

		$('#tab-opfields-settings').on('click', '.order-part .fa-exclamation-triangle', function () {
			$(this).toggleClass('active');
		});
		
		$('#tab-opfields-settings').on('click', '.order-part .save-button', function() {
			settings_opfields.saveOptionalFields_order();
		});
	},

	printFieldsRow_order: function(data) {
		$('.order-part .toDel').detach();
		$('.fields-list_order').append($('.order-template-row tbody').html());
	},

	showCog: function(object) {
		switch (object.find('[name=type]').val()) {
			case 'input':
			case 'textarea':
			case 'checkbox':
			case 'file':
				object.find('.openPopup').hide();
				break;

			default:
				object.find('.openPopup').show();
		}
	},

	saveOptionalFields_order: function() {
		var data = {};
		var fail = false;
		$('[name=name]').removeClass('error-input');
		$('.fields-list_order tr').each(function(index) {
			id = $(this).data('id');
			data[index] = {};
			data[index]['name'] = $(this).find('[name=name]').val();
			data[index]['placeholder'] = $(this).find('[name=placeholder]').val();
			data[index]['type'] = $(this).find('[name=type]').val();
			data[index]['sort'] = index;
			data[index]['id'] = id;
			$(this).find('.field-variant .field').each(function(innerIndex) {
				if(!$(this).find('input').val()) return true;
				if(data[index]['vars'] == undefined) data[index]['vars'] = {};
				data[index]['vars'][innerIndex] = $(this).find('input').val();
			});
			if (!$(this).find('[name=name]').val()) {
				$(this).find('[name=name]').addClass('error-input');
				fail = true;
			}
		});
		if (fail) {return false;}
		admin.ajaxRequest({
			mguniqueurl: "action/saveOptionalFields",
			data: data
		},
		function (response) {
			admin.indication(response.status, response.msg);
			$('#tab-opfields-settings .fields-list_order').html(response.data);
			window.rebootOrderForm = true;
		});
	},

	addPopupField_order: function(object, val) {
		val = typeof val !== 'undefined' ? val : '';
		object.find('.field-variant').append('<p class="field"><input type="text" placeholder="'+lang.OP_FILEDS_VAR_NEW+'" title="'+lang.OP_FILEDS_VAR_NEW+'"><button title="'+lang.DELETE+'" aria-label="'+lang.DELETE+'" class="js-field-var-delete field__var-delete"><i class="fa fa-trash" aria-hidden="true"></button></p>');
	},
};