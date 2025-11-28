var settings_orderform = (function () {
	return {
		init: function() {

			$('#tab-order-form').on('click', '.moveToInactive', function() {
				$($(this).closest('li').detach()).appendTo('#inactiveOrderFields');
			});

			$('#tab-order-form').on('click', '.moveToActive', function() {
				$($(this).closest('li').detach()).appendTo('#activeOrderFields');
			});

			$('#tab-order-form').on('click', '.required', function() {
				$(this).toggleClass('active');
			});

			$('#tab-order-form').on('click', '.save-orderFields', function() {
				var fields = {};
				var i = 1;
				$('#tab-order-form #activeOrderFields li').each(function(index,element) {
					var fieldId = $(this).data('id');
					var fieldType = $(this).data('type');
					var required = 0;
					if ($(this).find('.required').hasClass('active')) {required = 1;}
					fields[fieldId] = {'active':1,'required':required,'sort':i++,'type':fieldType};
				});

				admin.ajaxRequest({
					mguniqueurl: "action/saveOrderFormFields", 
					data: fields,
				},

				function(response) {
					admin.indication(response.status, response.msg);
					admin.refreshPanel();
				});
			});

			$('#tab-order-form').on('change', '#order-form-conditions-modal [name=conditionSwitch]', function() {// global switch
				if ($(this).val() == 'always') {
					$('#order-form-conditions-modal .conditionsContainer').hide();
					$('#order-form-conditions-modal .addNewCondition').hide();
				} else {
					$('#order-form-conditions-modal .conditionsContainer').show();
					$('#order-form-conditions-modal .addNewCondition').show();
					if ($('#order-form-conditions-modal .conditionsContainer .condition').length < 1) {
						$('#tab-order-form .orderFields-templates .condition').clone().appendTo('#order-form-conditions-modal .conditionsContainer');
						settings_orderform.setCondiNumbers();
					}
				}
			});

			$('#tab-order-form').on('click', '#order-form-conditions-modal .addNewCondition', function() {
				$('#tab-order-form .orderFields-templates .condition').clone().appendTo('#order-form-conditions-modal .conditionsContainer');
				settings_orderform.setCondiNumbers();
			});

			$('#tab-order-form').on('change', '#order-form-conditions-modal [name=typeSelect]', function() {// main switch
				var container = $(this).closest('.condition');
				container.find('.condi_fieldName').html('');
				container.find('.condi_fieldVal').html('');
				switch($(this).val()) {
					case 'orderField':
						container.find('.condi_fieldName').html($('#tab-order-form .orderFields-templates select[name=fieldSelect]').clone());
					break;
					case 'delivery':
						container.find('.condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=deliverySelect]').clone());
					break;
					case 'userGroup':
						container.find('.condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=userGroupSelect]').clone());
					break;
					case 'userAddField':
						container.find('.condi_fieldName').html($('#tab-order-form .orderFields-templates select[name=userFieldSelect]').clone());
					break;
				}
			});

			$('#tab-order-form').on('change', '#order-form-conditions-modal .condi_fieldName select', function() {// fieldType switch
				var container = $(this).closest('.condition');
				var fieldType = $('option:selected', this).data('type');
				container.find('.condi_fieldVal').html('');

				switch(fieldType) {
					case 'input':
					case 'textarea':
					case 'file':
						container.find('.condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=input]').clone());
					break;
					case 'checkbox':
						container.find('.condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=checkbox]').clone());
					break;
					case 'select':
					case 'radiobutton':
						admin.ajaxRequest({
							mguniqueurl: "action/getOrderFieldsSelect", 
							data: {
								type: $(this).attr('name'),
								name: $(this).val()
							},
						},
						function(response) {
							// console.log(response);
							container.find('.condi_fieldVal').html(response.data);
						});
					break;
				}
			});

			$('#tab-order-form').on('click', '.orderFieldSettings', function() {
				var fieldId = $(this).closest('.orderField').data('id');

				$('#order-form-conditions-modal .save-orderConditions').data('id', fieldId);
				$('#order-form-conditions-modal .conditionsContainer').html('');
				$('#order-form-conditions-modal [name=conditionSwitch]').val('always');
				$('#order-form-conditions-modal .fieldTitle').text($(this).closest('.orderField').find('.fieldTitle').text());
				admin.ajaxRequest({
					mguniqueurl: "action/loadOrderFormConditions", 
					data: {
						fieldId: fieldId,
					},
				},
				function(response) {
					if (response.data && response.data.conditionType && response.data.conditionType != 'always' && response.data.conditions.length) {
						response.data.conditions.forEach(function (condition) {
							// condition type
							$('#tab-order-form .orderFields-templates .condition').clone().appendTo('#order-form-conditions-modal .conditionsContainer');
							$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_type select:last').val(condition.type);
							// condition field name
							if (condition.fieldName) {
								if (condition.type == 'orderField') {
									$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldName').html($('#tab-order-form .orderFields-templates select[name=fieldSelect]').clone());
								}
								if (condition.type == 'userAddField') {
									$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldName').html($('#tab-order-form .orderFields-templates select[name=userFieldSelect]').clone());
								}
								$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldName select:last').val(condition.fieldName);

								// condition value
								if (condition.fieldType == 'input' || condition.fieldType == 'textarea') {
									$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=input]').clone());
								}
								if (condition.fieldType == 'checkbox') {
									$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=checkbox]').clone());
								}
								if (condition.fieldType == 'select' || condition.fieldType == 'radiobutton') {
									$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldVal').html(condition.html);
								}
								$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldVal select:last').val(condition.value);
							} else {// condition value without field name
								if (condition.type == 'delivery') {
									$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=deliverySelect]').clone());
								}
								if (condition.type == 'userGroup') {
									$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldVal').html($('#tab-order-form .orderFields-templates select[name=userGroupSelect]').clone());
								}
								$('#order-form-conditions-modal .conditionsContainer .condition:last .condi_fieldVal select:last').val(condition.value);
							}
						});
						settings_orderform.setCondiNumbers();
					}
					if (response.data && response.data.conditionType) {
						$('#order-form-conditions-modal [name=conditionSwitch]').val(response.data.conditionType).trigger('change');
					}
					
					admin.openModal('#order-form-conditions-modal');
				});
			});

			$('#tab-order-form').on('click', '.save-orderConditions', function() {
				var closeModal = true;
				$('#order-form-conditions-modal .error-input').removeClass('error-input');
				var fieldId = $(this).data('id');
				var conditions = [];
				var conditionType = $('#order-form-conditions-modal [name=conditionSwitch]').val();
				var fail = false;
				if ($('#order-form-conditions-modal [name=fieldSelect] option[value=none]:checked').length) {
					$('#order-form-conditions-modal [name=fieldSelect] option[value=none]:checked').closest('select').addClass('error-input');
					return false;
				}
				if ($('#order-form-conditions-modal [name=userFieldSelect] option[value=none]:checked').length) {
					$('#order-form-conditions-modal [name=userFieldSelect] option[value=none]:checked').closest('select').addClass('error-input');
					return false;
				}
				if (conditionType != 'always') {
					$('#order-form-conditions-modal .condition').each(function(index,element){
						var condition = $(this);
						var tmp = {};
						var type = condition.find('.condi_type select:first').val();
						if (type == 'none') {return true;}
						tmp.type = type;
						if (condition.find('.condi_fieldName select:first').length) {
							tmp.fieldName = condition.find('.condi_fieldName select:first').val();
							tmp.fieldType = condition.find('.condi_fieldName select:first option:checked').data('type');
						}
						tmp.value = condition.find('.condi_fieldVal select:first').val();

						if ((type == 'delivery' || type == 'userGroup') && !tmp.value) {
							condition.find('.condi_fieldVal select:first').addClass('error-input');
							fail = true;
						}

						conditions.push(tmp);
					});
				}
				if (fail) {return false;}
				if (conditions.length === 0) {
					conditionType = 'always';
				}
				admin.ajaxRequest({
					mguniqueurl: "action/saveOrderFormFieldConditions", 
					data: {
						fieldId: fieldId,
						conditionType: conditionType,
						conditions: conditions
					},
				},

				function(response) {
					if (closeModal) {
						admin.closeModal('#order-form-conditions-modal');
						$('#tab-order-form .save-orderFields').click();
					} else {
						$('#order-form-conditions-modal .close-button').off('click', settings_orderform.closeModalAfterSave)
						$('#order-form-conditions-modal .close-button').on('click', settings_orderform.closeModalAfterSave)
					}
				});
			});
		},
		closeModalAfterSave: function () {
			admin.closeModal('#order-form-conditions-modal');
			$('#tab-order-form .save-orderFields').click();
		},

		setCondiNumbers: function() {
			$('#order-form-conditions-modal .condition').each(function(index,element) {
				$(this).find('.conditionNumber').text(index+1);
			});
		}
	};
})();