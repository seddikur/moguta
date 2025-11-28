var settings_integration = (function () {
	return {
		init: function() {
			$('#integration-settings').on('click', '.integrations-container a', function() {
				admin.SECTION = 'integrations';
				cookie("integrationPart", $(this).attr('part'));
				admin.show("integrations.php", "adminpage", cookie(admin.SECTION + "_getparam"));
			});
			$(document).on('click','.js-go-to-market-integr' ,function () {
				includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
				admin.show("marketplace.php", cookie("type"), "mpFilter=all&mpFilterType=p", () => {
					marketplaceModule.init()
					$('.mg-admin-mainmenu-item').removeClass('active');
					$('#mpFilter').find('[value="10"]').prop('selected', true).change();
				});
			});
		},
	};
})();