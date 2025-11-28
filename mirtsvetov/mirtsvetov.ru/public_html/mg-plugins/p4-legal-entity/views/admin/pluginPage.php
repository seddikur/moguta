<?php
	/**
	 * 
	 * Главная страница плагина.
	 * 
	 * Доступны переменные:
	 * 
	 * $pluginName - название плагина.
	 * $path - пусть до папки плагина.
	 * $lang - массив фраз для выбранной локали.
	 * $currentPage - текущая страница.
	 * $filter - блок фильтров.
	 * $userFilter - пользовательский фильтр.
	 * $itemsCount - количество всех найденых записей.
	 * $countPrintRows - количество записей на странице.
	 * $entities - массив данных из таблиц БД.
	 * $pagination - блок навигации.
	 * 
	 */
?>

<div class="section-<?php echo $pluginName ?>">
	<div class="widget-body">
		<div class="widget-panel-holder">
			<div class="widget-panel" style="border-top: 0;">
				<ul class="tabs custom-tabs">
					<li class="tabs-title <?php if ($currentPage === '1' || !isset($currentPage)) echo 'is-active'; ?>">
						<a role="button" class="js-tabs-link" href="javascript:void(0);">
							<span><?php echo $lang['TAB_USERS']; ?></span>
						</a>
					</li>
					<li class="tabs-title <?php if ($currentPage === '2') echo 'is-active'; ?>">
						<a role="button" class="js-tabs-link" href="javascript:void(0);">
							<span><?php echo $lang['TAB_LEGAL_ENTITIES']; ?></span>
						</a>
					</li>
					<li class="tabs-title <?php if ($currentPage === '3') echo 'is-active'; ?>">
						<a role="button" class="js-tabs-link" href="javascript:void(0);">
							<span><?php echo $lang['TAB_SESSION']; ?></span>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<?php 
		if ($currentPage === '1' || !isset($currentPage)) {
			include($path . '/views/admin/page/layout/user.php');
		}
		if ($currentPage === '2') {
			include($path . '/views/admin/page/layout/legalEntity.php');
		}
		if ($currentPage === '3') {
			include($path . '/views/admin/page/layout/session.php');
		}
	?>
</div>