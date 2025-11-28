var settings_seo = (function () {
	var savedDataRow = {}; // данные редактируемой строки
	var savedDataRowCanonical = {}; // данные редактируемой строки
	var cansel = false; // использовать возврат значений при отмене
	return {
		supportCkeditorR: null,
		supportCkeditor: null, 
		supportCkeditorExtra: null,
		init: function() {
			settings_seo.initMisc();
			settings_seo.initUrlRedirect();
			settings_seo.initUrlRewrite();
			settings_seo.initUrlCanonical();
		},
		initMisc: function() {
			// для Opengraph
			$('#SEOMethod-settings').on('change', '#q2-openGraph, #useSeoRewrites', function() { 
				if($('#q2-openGraph').prop('checked')) {
					$('.js-open-graph-logo-path').show();
				} else {
					$('.js-open-graph-logo-path').hide();
				}
				if($(this).prop('checked')) {
					$('.addShortLink-table').show();
				} else {
					$('.addShortLink-table').hide();
				}
			});

			// установка метатегов для всех сущностей одной категории
			$('#SEOMethod-settings').on('click', '#setCatalogSeoForTemplate', function() {
				if($(this).attr('status') == 'active') {
					settings_seo.setSeoForGroup('catalog');
				}
			}); 
			$('#SEOMethod-settings').on('click', '#setProductSeoForTemplate', function() {
				if($(this).attr('status') == 'active') {
					settings_seo.setSeoForGroup('product');
				}
			}); 
			$('#SEOMethod-settings').on('click', '#setPageSeoForTemplate', function() {
				if($(this).attr('status') == 'active') {
					settings_seo.setSeoForGroup('page');
				}
			});

			$('#SEOMethod-settings').on('keyup', '.catalog-seo input, .section-settings .catalog-seo textarea', function() {
				$('.section-settings #setCatalogSeoForTemplate').attr('status','disable');
				$('.section-settings #setCatalogSeoForTemplate').css('color','red!important');
				$('.section-settings #setCatalogSeoForTemplate').text(lang.SEO_TEMPLATE_ALERT);
			});
			$('#SEOMethod-settings').on('keyup', '.product-seo input, .section-settings .product-seo textarea', function() {
				$('.section-settings #setProductSeoForTemplate').attr('status','disable');
				$('.section-settings #setProductSeoForTemplate').css('color','red!important');
				$('.section-settings #setProductSeoForTemplate').text(lang.SEO_TEMPLATE_ALERT);
			});
			$('#SEOMethod-settings').on('keyup', '.page-seo input, .section-settings .page-seo textarea', function() {
				$('.section-settings #setPageSeoForTemplate').attr('status','disable');
				$('.section-settings #setPageSeoForTemplate').css('color','red!important');
				$('.section-settings #setPageSeoForTemplate').text(lang.SEO_TEMPLATE_ALERT);
			});

			$('#SEOMethod-settings').on('change', 'input[name=cacheCssJs]', function() {        
				if($(this).prop('checked')) {
					$('.create-images-for-css-cache').show(); 
					$('.warning-create-images').show();
				} else {
					$('.create-images-for-css-cache').hide();	
					$('.warning-create-images').hide();
				}
			});

			// Выбор картинки для OpenGraph
			$('#SEOMethod-settings').on('click', '.browseOpenGraphLogo', function() {
				admin.openUploader('settings_seo.getOgLogo'); 
				$('.logo-og-img').show();      
			});

			// удаление логотипа OpenGraph
			$('#SEOMethod-settings').on('click', '.remove-added-logo-og', function() {
				$(this).hide();
				$('.section-settings input[name="openGraphLogoPath"]').val('');    
				$('.section-settings .logo-og-img img').removeAttr().hide();  
				$('.section-settings .logo-og-img').hide();  
			});
			
			$('#SEOMethod-settings').on('click', '.createSitemap', function() {    
				settings_seo.createMap();
			});

			// обработка клика по кнопки - создать images для css
			$('#SEOMethod-settings').on('click', '.create-images-for-css-cache', function () {
				admin.ajaxRequest({
					mguniqueurl: "action/clearImageCssСache",
				},
				function (response) {
					admin.indication('success', 'Изображения успешно созданы!');
					$('.group-property .warning-create-images').hide();
				});
			});

			// Ищет по названию выборки
			$('#SEOMethod-settings').on('click', '.js-do-search', function (){
				admin.ajaxRequest({
						mguniqueurl: "action/searchUrlRewrite",
						searchSeoUrl: $('input[name=search]').val()
					},
					function (response) {
						admin.indication(response.status);
						$('.filterShortLinkTable').html(response.data);
						
					});
			});

			// Блокируем настройку содержимого атрибута lang, если включена настройка автогенирации lang
			$('#SEOMethod-settings').on('change', 'input#genMetaLang', function (){
				if ($('input#genMetaLang').val() == 'true') {
					$('input#metaLangContent').prop('disabled', true);
					return;
				}
				$('input#metaLangContent').prop('disabled', false);
			});

			$('#SEOMethod-settings #q2-openGraph').trigger('change');
		},
		setSeoForGroup: function(type) {  
			isChange = confirm(lang.MESSAGE_CONFIRM);
			if(isChange) {
				admin.ajaxRequest({
					mguniqueurl: "action/setSeoForGroup", // действия для выполнения на сервере
					data: type
				},
				function(response) {
					admin.indication(response.status, response.msg);
				});
			}
		},
		// Запускает процесс создания карты в корне сайта           
		createMap: function() {
			admin.ajaxRequest({
				mguniqueurl: "action/generateSitemap", // действия для выполнения на сервере
			},
			function(response) {
				admin.indication(response.status, response.msg);
				if (response.status!='error') {
					$('#SEOMethod-settings .sitemap-msg').html('<div class="alert-block success text-center">'+response.data.msg+'</div>');
				}
			});
		},
		// функция для приема файла из аплоадера, для сохранения в путь логотипа OpenGraph        
		getOgLogo: function(file) {
			var dir = file.url;
			$('.section-settings input[name="openGraphLogoPath"]').val(dir);
			$('.section-settings .logo-og-img img').attr('src',dir).show();
			$('.section-settings .logo-og-img .remove-added-logo').show();
		},
		////////////////////////////// urlRedirect /////////////////////////////////////
		initUrlRedirect: function() {
			// добавить запись перенаправления
			$('#SEOMethod-settings').on('click', '.addRedirect', function() {
				$(this).closest('.accordion-content').find('.save-row:visible').click();
				savedDataRow.url_old = '';
				savedDataRow.url_new = '';
				savedDataRow.code = 0;
				settings_seo.createRow();
			});
			// редактирования строки свойства
			$('#SEOMethod-settings').on('click', '.urlRedirectTable li.edit-row', function() {
				$(this).closest('.accordion-content').find('.save-row:visible').click();
				var id = $(this).parents('tr').attr('id');
				settings_seo.canselEditRow(savedDataRow.id);
				settings_seo.hideActions(savedDataRow.id);
				settings_seo.rowToEditRow(id);
				settings_seo.showActions(id);        
			});
			// сохранение строки свойства
			$('#SEOMethod-settings').on('click', '.urlRedirectTable li.save-row', function() {
				var id = $(this).parents('tr').attr('id');
				settings_seo.saveEditRow(id);
				settings_seo.hideActions(id);
			});

			// отмена редактирования строки
			$('#SEOMethod-settings').on('click', '.urlRedirectTable li.cancel-row', function() {
				var id = $(this).parents('tr').attr('id');
				settings_seo.canselEditRow(id);
				settings_seo.hideActions(id);
			});
			// смена активности записи 
			$('#SEOMethod-settings').on('click', '.urlRedirectTable li.visible', function() {
				settings_seo.setActivityRedirect($(this), $(this).attr('data-id'));
			});
			// удаление записи 
			$('#SEOMethod-settings').on('click', '.urlRedirectTable li.delete-row', function() {
				if(confirm('Удалить?')) {
					settings_seo.deleteRedirect($(this), $(this).attr('id'));
				}
			});
			$('#SEOMethod-settings').on('click', '.redirectLinkPage', function () {
				admin.ajaxRequest({
					mguniquetype: cookie("type"),
					mguniqueurl: admin.SECTION+'.php',
					seo_pager: 1,
					group: "STNG_SEO_GROUP_2",
					redirectPage: $(this).text()
				},
				function(response) {
					$('.urlRedirectTable').empty();

					response.data.forEach(function(element, index, array){
						$('.urlRedirectTable').append(settings_seo.drawRowRedirect(element));
					});

					$('.urlRedirectListPager').html(response.pager);
				});
				return false;
			});
		},
		deleteRedirect: function(obj, id){
			admin.ajaxRequest({
				mguniqueurl: "action/deleteUrlRedirect",
				id: id,				
			},
			function (response) {
				if(response.status != "error"){
					obj.parents("tr.rewrite-line").remove();
				}
					
				admin.indication(response.status, response.msg);
			});
		},
		setActivityRedirect: function(obj, id){
			var activity = 1;
			
			if(obj.find('.fa-eye').hasClass('active')){
				activity = 0;
			}
			
			admin.ajaxRequest({
				mguniqueurl: "action/setUrlRedirectActivity",
				id: id,
				activity: activity
			},
			function (response) {
				if(response.status != "error"){
					obj.find('.fa-eye').toggleClass('active');
				}
					
				admin.indication(response.status, response.msg);
			});
		},
		createRow: function(){
			admin.ajaxRequest({
				mguniqueurl: "action/addUrlRedirect"
			},
			function(response) {
				admin.indication(response.status, response.msg);
				var row = settings_seo.drawRowRedirect(response.data);

				if ($(".urlRedirectList tr[class=tempMsg]").length != 0) {
					$(".urlRedirectTable").html('');
				}
				$('.urlRedirectTable').prepend(row);
				
				savedDataRow.id = response.data.id;
				
				settings_seo.canselEditRow(savedDataRow.id);
				settings_seo.hideActions(savedDataRow.id);
				settings_seo.rowToEditRow(response.data.id);
				settings_seo.showActions(response.data.id);
			});						
		},
		drawRowRedirect:function(data){
			var codeName = 'REDIRECT_MESSAGE_'+data.code;
			var row = '\
				<tr id="'+data.id+'" class="rewrite-line">\\n\
					<td class="url_old">'+data.url_old+'</td>\
					<td class="url_new">'+data.url_new+'</td>\
					<td class="code" value="'+data.code+'">'+lang[codeName]+'</td>\
					<td class="actions">\
						<ul class="action-list text-right">\
							<li class="save-row" id="' + data.id + '" style="display:none"><a class="tool-tip-bottom fa fa-check" href="javascript:void(0);" title="' + lang.SAVE + '"></a></li>\
							<li class="cancel-row" id="' + data.id + '" style="display:none"><a class="tool-tip-bottom fa fa-times" href="javascript:void(0);" title="' + lang.CANCEL + '"></a></li>\
							<li class="edit-row" id="'+data.id+'"><a role="button" href="javascript:void(0);" title="'+lang.EDIT+'" class="fa fa-pencil"></a></li>\
							<li class="visible tool-tip-bottom" data-id="'+data.id+'" title="'+lang.ACTIVITY+'" ><a role="button" href="javascript:void(0);" class="fa fa-eye active"></a></li>\
							<li class="delete-row" id="'+data.id+'"><a role="button" href="javascript:void(0);" class="fa fa-trash" title="'+lang.DELETE+'"></a></li>\
						</ul>\
					</td>\
				</tr>';
			
			return row;
		},
		//Отменяет редактирование
		canselEditRow: function(id) {
			if(cansel){
				var url_old = $('.urlRedirectTable tr[id=' + id + '] td[class=url_old]');
				url_old.text(savedDataRow.url_old);
				
				var url_new = $('.urlRedirectTable tr[id=' + id + '] td[class=url_new]');
				url_new.text(savedDataRow.url_new);
				
				var code = $('.urlRedirectTable tr[id=' + id + '] td[class=code]');
				code.text(savedDataRow.code);
			}
		},
		// показывает дополнительные	действия при редактировании
		showActions: function(id) {			
			$('.urlRedirectTable tr[id=' + id + '] .cancel-row').show();
			$('.urlRedirectTable tr[id=' + id + '] .save-row').show();
			$('.urlRedirectTable tr[id=' + id + '] .edit-row').hide(); 
		},
		// скрывает дополнительные	действия при редактировании
		hideActions: function(id) {							 
			$('.urlRedirectTable tr[id=' + id + '] .cancel-row').hide();
			$('.urlRedirectTable tr[id=' + id + '] .save-row').hide();
			$('.urlRedirectTable tr[id=' + id + '] .edit-row').show();
		},
		//Делает поля доступными для редактирования
		rowToEditRow: function(id) {
			cansel = true;
			var url_old = $('.urlRedirectTable tr[id=' + id + '] td[class=url_old]');
			var urlOldVal = url_old.text();
			url_old.html('<input name="url_old" type="text" class="custom-input tool-tip-bottom" title="' + lang.T_TIP_STNG_SEO_URL_REDIRECT_OLD_URL + '" value="' + url_old.text() + '">');
				 
			var url_new = $('.urlRedirectTable tr[id=' + id + '] td[class=url_new]');
			var urlNewVal = url_new.text();
			url_new.html('<input name="url_new" type="text" class="custom-input tool-tip-bottom" title="' + lang.T_TIP_STNG_SEO_URL_REDIRECT_NEW_URL + '" value="' + url_new.text() + '">');
			
			var code = $('.urlRedirectTable tr[id=' + id + '] td[class=code]');
			var codeVal = code.attr('value');
			var codeChoise = '\
				<select name="code" class="custom-input tool-tip-bottom">\
					<option value="301">' + lang.REDIRECT_MESSAGE_301 + '</option>\
					<option value="302">' + lang.REDIRECT_MESSAGE_302 + '</option>\
					<option value="303">' + lang.REDIRECT_MESSAGE_303 + '</option>\
					<option value="307">' + lang.REDIRECT_MESSAGE_307 + '</option>\
				</select>';
				//<option value="300">' + lang.REDIRECT_MESSAGE_300 + '</option>\
			code.html(codeChoise);

			if(codeVal == '') {
				codeVal = 300;
			}

			code.find('option[value='+codeVal+']').prop('selected', 'selected');
			codeVal = $('.urlRedirectTable tr[id=' + id + '] td[class=code] option:selected').html();

			savedDataRow = {
				id: id,
				url_old: urlOldVal,
				url_new: urlNewVal,
				code: codeVal,
			};

			admin.initToolTip();
		},
		//Сохраняет редактирование
		saveEditRow: function(id) {
			cansel = false;
			
			var url_old = $('.urlRedirectTable tr[id=' + id + '] td[class=url_old]');
			var urlOldVal = url_old.find('input').val();
			url_old.text(urlOldVal);
			
			var url_new = $('.urlRedirectTable tr[id=' + id + '] td[class=url_new]');
			var urlNewVal = url_new.find('input').val();
			url_new.text(urlNewVal);
			
			var code = $('.urlRedirectTable tr[id=' + id + '] td[class=code]');
			var codeVal = code.find('select').val();
			code.text(code.find('option[value='+codeVal+']').text());

			// удаляем обработчик показа установки дефолтного значения
			$('.itemData').unbind();
			$('.list-prop').unbind();
			 
			admin.ajaxRequest({
				mguniqueurl: "action/saveUrlRedirect",
				id: id,
				url_old: urlOldVal,
				url_new: urlNewVal,
				code: codeVal,
			},
			function(response) {
				var data = {};
				data.url_old = urlOldVal;
				data.url_new = urlNewVal;
				data.code = codeVal;
				data.id = id;
				var row = settings_seo.drawRowRedirect(data);
				$('.urlRedirectTable tr[id='+id+']').replaceWith(row);
				admin.indication(response.status, response.msg);
			});
		},
		//////////////////////////////urlCanonical/////////////////////////////////////
		initUrlCanonical: function() {
			// добавить запись перенаправления
			$('#SEOMethod-settings').on('click', '.addCanonical', function() {
				$(this).closest('.accordion-content').find('.save-row:visible').click();
				savedDataRowCanonical.url_page = '';
				savedDataRowCanonical.url_canonical = '';
				settings_seo.createRowCanonical();
			});
			// редактирования строки свойства
			$('#SEOMethod-settings').on('click', '.urlCanonicalTable li.edit-row', function() {
				$(this).closest('.accordion-content').find('.save-row:visible').click();
				var id = $(this).parents('tr').attr('id');
				settings_seo.canselEditRowCanonical(savedDataRowCanonical.id);
				settings_seo.hideActionsCanonical(savedDataRowCanonical.id);
				settings_seo.rowToEditRowCanonical(id);
				settings_seo.showActionsCanonical(id);        
			});
			// сохранение строки свойства
			$('#SEOMethod-settings').on('click', '.urlCanonicalTable li.save-row', function() {
				var id = $(this).parents('tr').attr('id');
				settings_seo.saveEditRowCanonical(id);
				settings_seo.hideActionsCanonical(id);
			});

			// отмена редактирования строки
			$('#SEOMethod-settings').on('click', '.urlCanonicalTable li.cancel-row', function() {
				var id = $(this).parents('tr').attr('id');
				settings_seo.canselEditRowCanonical(id);
				settings_seo.hideActionsCanonical(id);
			});
			// смена активности записи 
			$('#SEOMethod-settings').on('click', '.urlCanonicalTable li.visible', function() {
				settings_seo.setActivityCanonical($(this), $(this).attr('data-id'));
			});
			// удаление записи 
			$('#SEOMethod-settings').on('click', '.urlCanonicalTable li.delete-row', function() {
				if(confirm('Удалить?')) {
					settings_seo.deleteCanonical($(this), $(this).attr('id'));
				}
			});
			$('#SEOMethod-settings').on('click', '.canonicalLinkPage', function () {
				admin.ajaxRequest({
					mguniquetype: cookie("type"),
					mguniqueurl: admin.SECTION+'.php',
					seo_pager: 1,
					group: "STNG_SEO_GROUP_5",
					canonicalPage: $(this).text()
				},
				function(response) {
					$('.urlCanonicalTable').empty();

					response.data.forEach(function(element, index, array){
						$('.urlCanonicalTable').append(settings_seo.drawRowCanonical(element));
					});

					$('.urlCanonicalListPager').html(response.pager);
				});
				return false;
			});
		},
		deleteCanonical: function(obj, id){
			admin.ajaxRequest({
				mguniqueurl: "action/deleteUrlCanonical",
				id: id,				
			},
			function (response) {
				if(response.status != "error"){
					obj.parents("tr.canonical-line").remove();
				}
					
				admin.indication(response.status, response.msg);
			});
		},
		setActivityCanonical: function(obj, id){
			var activity = 1;
			if(obj.find('.fa-lightbulb-o').hasClass('active')){
				activity = 0;
			}
			
			admin.ajaxRequest({
				mguniqueurl: "action/setUrlCanonicalActivity",
				id: id,
				activity: activity
			},
			function (response) {
				if(response.status != "error"){
					obj.find('.fa-lightbulb-o').toggleClass('active');
				}
					
				admin.indication(response.status, response.msg);
			});
		},
		createRowCanonical: function(){
			admin.ajaxRequest({
				mguniqueurl: "action/addUrlCanonical"
			},
			function(response) {
				admin.indication(response.status, response.msg);
				var row = settings_seo.drawRowCanonical(response.data);

				if ($(".urlCanonicalList tr[class=tempMsg]").length != 0) {
					$(".urlCanonicalTable").html('');
				}
				$('.urlCanonicalTable').prepend(row);
				
				savedDataRowCanonical.id = response.data.id;
				
				settings_seo.canselEditRowCanonical(savedDataRowCanonical.id);
				settings_seo.hideActionsCanonical(savedDataRowCanonical.id);
				settings_seo.rowToEditRowCanonical(response.data.id);
				settings_seo.showActionsCanonical(response.data.id);
			});						
		},
		drawRowCanonical:function(data){
			var codeName = 'REDIRECT_MESSAGE_'+data.code;
			var row = '\
				<tr id="'+data.id+'" class="rewrite-canonical">\\n\
					<td class="url_page">'+data.url_page+'</td>\
					<td class="url_canonical">'+data.url_canonical+'</td>\
					<td class="actions">\
						<ul class="action-list text-right">\
							<li class="save-row" id="' + data.id + '" style="display:none"><a class="tool-tip-bottom fa fa-check" href="javascript:void(0);" title="' + lang.SAVE + '"></a></li>\
							<li class="cancel-row" id="' + data.id + '" style="display:none"><a class="tool-tip-bottom fa fa-times" href="javascript:void(0);" title="' + lang.CANCEL + '"></a></li>\
							<li class="edit-row" id="'+data.id+'"><a role="button" href="javascript:void(0);" title="'+lang.EDIT+'" class="fa fa-pencil"></a></li>\
							<li class="visible tool-tip-bottom" data-id="'+data.id+'" title="'+lang.ACTIVITY+'" ><a role="button" href="javascript:void(0);" class="fa fa-lightbulb-o active"></a></li>\
							<li class="delete-row" id="'+data.id+'"><a role="button" href="javascript:void(0);" class="fa fa-trash" title="'+lang.DELETE+'"></a></li>\
						</ul>\
					</td>\
				</tr>';
			
			return row;
		},
		//Отменяет редактирование
		canselEditRowCanonical: function(id) {
			if(cansel){
				var url_old = $('.urlCanonicalTable tr[id=' + id + '] td[class=url_old]');
				url_old.text(savedDataRow.url_old);
				
				var url_new = $('.urlCanonicalTable tr[id=' + id + '] td[class=url_new]');
				url_new.text(savedDataRow.url_new);
				
				var code = $('.urlCanonicalTable tr[id=' + id + '] td[class=code]');
				code.text(savedDataRow.code);
			}
		},
		// показывает дополнительные	действия при редактировании
		showActionsCanonical: function(id) {			
			$('.urlCanonicalTable tr[id=' + id + '] .cancel-row').show();
			$('.urlCanonicalTable tr[id=' + id + '] .save-row').show();
			$('.urlCanonicalTable tr[id=' + id + '] .edit-row').hide(); 
		},
		// скрывает дополнительные	действия при редактировании
		hideActionsCanonical: function(id) {							 
			$('.urlCanonicalTable tr[id=' + id + '] .cancel-row').hide();
			$('.urlCanonicalTable tr[id=' + id + '] .save-row').hide();
			$('.urlCanonicalTable tr[id=' + id + '] .edit-row').show();
		},
		//Делает поля доступными для редактирования
		rowToEditRowCanonical: function(id) {
			cansel = true;
			var url_page = $('.urlCanonicalTable tr[id=' + id + '] td[class=url_page]');
			var url_pageVal = url_page.text();
			url_page.html('<input name="url_page" type="text" class="custom-input tool-tip-bottom" title="' + lang.T_TIP_STNG_SEO_URL_REDIRECT_OLD_URL + '" value="' + url_page.text() + '">');
				 
			var url_canonical = $('.urlCanonicalTable tr[id=' + id + '] td[class=url_canonical]');
			var url_canonicalVal = url_canonical.text();
			url_canonical.html('<input name="url_canonical" type="text" class="custom-input tool-tip-bottom" title="' + lang.T_TIP_STNG_SEO_URL_REDIRECT_NEW_URL + '" value="' + url_canonical.text() + '">');
			
			savedDataRowCanonical = {
				id: id,
				url_page: url_pageVal,
				url_canonical: url_canonicalVal,
			};

			admin.initToolTip();
		},
		//Сохраняет редактирование
		saveEditRowCanonical: function(id) {
			cansel = false;
			
			var url_page = $('.urlCanonicalTable tr[id=' + id + '] td[class=url_page]');
			var url_pageVal = url_page.find('input').val();
			url_page.text(url_pageVal);
			
			var url_canonical = $('.urlCanonicalTable tr[id=' + id + '] td[class=url_canonical]');
			var url_canonicalVal = url_canonical.find('input').val();
			url_canonical.text(url_canonicalVal);

			// удаляем обработчик показа установки дефолтного значения
			$('.itemData').unbind();
			$('.list-prop').unbind();
			 
			admin.ajaxRequest({
				mguniqueurl: "action/saveUrlCanonical",
				id: id,
				url_page: url_pageVal,
				url_canonical: url_canonicalVal,
			},
			function(response) {
				var data = {};
				data.url_page = url_pageVal;
				data.url_canonical = url_canonicalVal;
				data.id = id;
				var row = settings_seo.drawRowCanonical(data);
				$('.urlCanonicalTable tr[id='+id+']').replaceWith(row);
				admin.indication(response.status, response.msg);
			});
		},
		////////////////////////////// urlRewrite /////////////////////////////////////
		initUrlRewrite: function() {
			$('#SEOMethod-settings').on('change', '#add-short-link-modal [name=url]', function() {
				$(this).val($(this).val().replace(mgBaseDir, ''));
			});
			// смена языка
			$('#SEOMethod-settings').on('change','#add-short-link-modal .select-lang', function() {
				settings_seo.editRewrite($('#add-short-link-modal .save-button').attr('id'));
			});
			// открыть модалку с привязками к категориям
			$('#SEOMethod-settings').on('click', '.addShortLink', function(){
				settings_seo.openModalWindow('add');
			});
			// редактирования строки свойства
			$('#SEOMethod-settings').on('click', '.filterShortLinkTable li.edit-row', function(){ 
				settings_seo.openModalWindow('edit', $(this).attr('id'));
			});
			// смена активности записи 
			$('#SEOMethod-settings').on('click', '.filterShortLinkTable li.visible', function(){
				settings_seo.setActivityRewrite($(this), $(this).attr('data-id'));
			});
			// удаление записи 
			$('#SEOMethod-settings').on('click', '.filterShortLinkTable li.delete-row', function(){
				if(confirm('Удалить?')) {
					settings_seo.deleteRewrite($(this), $(this).attr('id'));
				}
			});
			// показ длинного урла
			$('#SEOMethod-settings').on('click', '.filterShortLinkTable .show-long-url', function(){
				$(this).hide();
				$(this).parents('td').find('.url-long').show();
			});
			// Сохранение в модальном окне.
			$('#SEOMethod-settings').on('click', '#add-short-link-modal .save-button', function(){
				settings_seo.saveRewrite($(this).attr('id'));
			});
			$('#SEOMethod-settings').on('click', '.rewriteLinkPage', function (){
				admin.ajaxRequest({
					mguniquetype: cookie("type"),
					mguniqueurl: admin.SECTION+'.php',
					seo_pager: 1,
					group: "STNG_SEO_GROUP_1",
					rewritePage: admin.getIdByPrefixClass($(this), 'page')
				}, function(response){
					$('.filterShortLinkTable').empty();
					
					response.data.forEach(function(element, index, array){
						$('.filterShortLinkTable').append(settings_seo.drawRowRewrite(element));
					});
					
					$('#urlRewritePager').html(response.pager);
				});
				return false;
			});
			// формирование meta title по введенному названию
			$('#SEOMethod-settings').on('blur', '#add-short-link-modal input[name=titeCategory]', function(){
				var title = $(this).val().replace(/"/g,'');
				
				if (!$('#add-short-link-modal input[name=short_url]').val()){
					$('#add-short-link-modal input[name=short_url]').val(admin.urlLit(title));
				}
				
				if (!$('#add-short-link-modal input[name=meta_title]').val()){
					$('#add-short-link-modal input[name=meta_title]').val(title);
				}
				
				if (!$('#add-short-link-modal input[name=meta_keywords]').val()) {
					$('#add-short-link-modal input[name=meta_keywords]').val(title);
				}
			});
			// автотранслит заголовка в URL. При клике, или табе, на поле URL, если оно пустое то будет автозаполнено транслитироированным заголовком
			$('#SEOMethod-settings').on('click, focus', '#add-short-link-modal input[name=short_url]', function () {
				if ($('#add-short-link-modal input[name=short_url]').val() == '') {
					var text = $('#add-short-link-modal input[name=title]').val();
					if (text) {
						text.replace('%', '-');
						text = admin.urlLit(text, 1);
						$(this).val(text);
					}
				}
			});
		},
		deleteRewrite: function(obj, id){
			admin.ajaxRequest({
				mguniqueurl: "action/deleteRewrite",
				id: id,
			},
			function (response) {
				if(response.status != "error"){
					obj.parents("tr.rewrite-line").remove();
				}
					
				admin.indication(response.status, response.msg);
			});
		},
		setActivityRewrite: function(obj, id){
			var activity = 1;
			
			if(obj.find('a').hasClass('active')){
				activity = 0;
			}
			
			admin.ajaxRequest({
				mguniqueurl: "action/setRewriteActivity",
				id: id,
				activity: activity
			},
			function (response) {
				if(response.status != "error"){
					obj.find('a').toggleClass('active');
				}
					
				admin.indication(response.status, response.msg);
			});
		},
		saveRewrite: function(id, closeModal){
			closeModal = typeof closeModal !== 'undefined' ? closeModal : true;
			// Пакет характеристик категории.
			var packedProperty = {
				mguniqueurl: "action/saveRewrite",
				id: id,
				activity: $('input[name=activity]').val(),
				titeCategory: $('input[name=titeCategory]').val(),
				url: $('input[name=url]').val(),
				short_url: $('input[name=short_url]').val(),
				cat_desc: $('textarea[name=cat_desc]').val(),
				meta_title: $('input[name=meta_title]').val(),
				meta_keywords: $('input[name=meta_keywords]').val(),
				meta_desc: $('textarea[name=meta_desc]').val(),
				cat_desc_seo: $('textarea[name=seo_content]').val(),
				lang: $('#add-short-link-modal .select-lang').val()
			};
			
			// Отправка данных на сервер для сохранения.
			admin.ajaxRequest(packedProperty,
			function (response) {
					admin.indication(response.status, response.msg);
					if (response.status == 'success') {
						var row = settings_seo.drawRowRewrite(response.data);
					
						if(id){
							$('.filterShortLinkTable tr[id='+id+']').replaceWith(row);
						}else{
							$('.filterShortLinkTable').prepend(row);
						}
						if (closeModal) {
							admin.closeModal('#add-short-link-modal');
						} else {
							$('#add-short-link-modal').attr('data-refresh', 'true');
						}
					}
			});
		},
		drawRowRewrite: function(data){
			var activity = data.activity == 1?'active':'';
			
			var row = '\
				<tr id="'+data.id+'" class="rewrite-line">\
					<td>'+data.titeCategory+'</td>\
					<td>'
							+admin.SITE+'/'+data.short_url+'\
							<a class="link-to-site tool-tip-bottom" href="'+admin.SITE+'/'+data.short_url+'" target="_blank">\
								<img src="'+admin.SITE+'/mg-admin/design/images/icons/link.png" alt="">\
							</a>\
					</td>\
					<td style="word-wrap: break-word;">\
						<span class="show-long-url">'+lang.SHOW+'</span>\
						<span style="display: none;" class="url-long">'+mgBaseDir+data.url+'</span>\
					</td>\
					<td class="actions text-right">\
						<ul class="action-list">\
							<li class="edit-row" id="'+data.id+'"><a role="button" href="javascript:void(0);" class="fa fa-pencil" title="'+lang.EDIT+'"></a></li>\
							<li class="visible tool-tip-bottom" data-id="'+data.id+'" title="'+lang.ACTIVITY+'" ><a role="button" href="javascript:void(0);" class="fa fa-eye '+activity+'"></a></li>\
							<li class="delete-row" id="'+data.id+'"><a role="button" href="javascript:void(0);" class="fa fa-trash" title="'+lang.DELETE+'"></a></li>\
						</ul>\
					</td>\
				</tr>';
			
			return row;
		},
		/**
		 * Открывает модальное окно.
		 * type - тип окна, либо для создания нового товара, либо для редактирования старого.
		 * id - редактируемая категория, если это не создание новой
		 */
		openModalWindow: function (type, id) {
			switch (type) {
				case 'edit':
				{
					settings_seo.clearFileds();
					settings_seo.editRewrite(id);
					break;
				}
				case 'add':
				{
					settings_seo.clearFileds();
					break;
				}
				default:
				{
					settings_seo.clearFileds();
					break;
				}
			}

			$('textarea[name=cat_desc]').ckeditor(function () {
				this.setData(settings_seo.supportCkeditor);
			});
			$('textarea[name=seo_content]').ckeditor(function () {
				this.setData(settings_seo.supportCkeditorExtra);
			});

			// Вызов модального окна.
			admin.openModal('#add-short-link-modal');
		},
		editRewrite: function(id){
			admin.ajaxRequest({
				mguniqueurl: "action/getRewriteData",
				id: id,
				lang: $('#add-short-link-modal .select-lang').val()
			},
			settings_seo.fillFileds(),
			$('.add-product-form-wrapper .add-category-form')
			);
		},
		/**
		 * Заполняет поля модального окна данными.
		 */
		fillFileds: function () {
			return (function (response) {
				settings_seo.supportCkeditor = response.data.cat_desc;
				settings_seo.supportCkeditorExtra = response.data.cat_desc_seo;
				$('input').removeClass('error-input');
				$('input[name=activity]').val(response.data.activity);
				$('input[name=titeCategory]').val(response.data.titeCategory);
				$('input[name=url]').val(response.data.url);
				$('input[name=short_url]').val(response.data.short_url);
				$('textarea[name=cat_desc]').val(response.data.cat_desc);
				$('input[name=meta_title]').val(response.data.meta_title);
				$('input[name=meta_keywords]').val(response.data.meta_keywords);
				$('textarea[name=meta_desc]').val(response.data.meta_desc);
				$('.symbol-count').text($('textarea[name=meta_desc]').val().length);
				$('.save-button').attr('id', response.data.id);
				$('textarea[name=seo_content]').val(response.data.cat_desc_seo);
			});
		},
		/**
		 * Чистит все поля модального окна.
		 */
		clearFileds: function () {
			$('input[name=titeCategory]').val('');
			$('input[name=url]').val('');
			$('input[name=short_url]').val('');
			$('textarea[name=cat_desc], textarea[name=seo_content] ').val('');
			$('input[name=meta_title]').val('');
			$('input[name=meta_keywords]').val('');
			$('textarea[name=meta_desc]').val('');
			$('.symbol-count').text('0');
			$('.save-button').attr('id', '');
			$('.category-desc-wrapper .html-content-edit,.category-desc-wrapper .html-content-edit-seo').show();

			// Стираем все ошибки предыдущего окна если они были.
			$('.errorField').css('display', 'none');
			$('.error-input').removeClass('error-input');
			settings_seo.supportCkeditor = "";
			settings_seo.supportCkeditorExtra = "";
		},
	};
})();