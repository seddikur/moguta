<link rel="stylesheet" href="<?php echo SITE ?>/mg-admin/section/views/layout/mobile-menu/css/style.css">
<div class="cd-nav__wrap">
	<button title="Меню" aria-label="Меню открыть-закрыть" class="cd-nav-trigger"><span></span></button>
	<div class="cd-main-content">
		<nav class="cd-side-nav">
			<ul>
				<li class="has-children notifications active adm-section--notify">
					<a role="button" href="javascript:void(0);" class="mobileInformerCount">
						Оповещения
						<span <?php $data['informerPanel']['mobileInformersSumm']?'':'style="display:none"' ?> class="count"><?php echo $data['informerPanel']['mobileInformersSumm']; ?></span>
					</a>

					<ul class="cd-side-nav__submenu submenu mobileInformers">
						<?php echo $data['informerPanel']['mobile']; ?>
					</ul>
				</li>
			</ul>

			<ul class="adm-section__wrap">
				<li class="cd-label adm-section__title">Разделы</li>
				<?php if (USER::access('product') > 0) :?>
					<li class="adm-section--prods">
						<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="catalog" data-vis-click="toProductsTab" href="javascript:void(0);">Товары</a>
					</li>
				<?php endif; ?>
				<?php if (USER::access('category') > 0) :?>
					<li class="adm-section--cats">
						<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="category" data-vis-click="toCategoryTab" href="javascript:void(0);">Категории</a>
					</li>
				<?php endif; ?>
				<?php if (USER::access('page') > 0) :?>
					<li class="adm-section--pages">
						<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="page" data-vis-click="toPagesTab" href="javascript:void(0);">Страницы</a>
					</li>
				<?php endif; ?>
				<?php if (USER::access('order') > 0) :?>
					<li class="adm-section--orders">
						<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="orders" data-vis-click="toOrdersTab" href="javascript:void(0);">Заказы</a>
					</li>
				<?php endif; ?>
				<?php if (USER::access('user') > 0) :?>
					<li class="adm-section--users">
						<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="users" data-vis-click="toUsersTab" href="javascript:void(0);">Пользователи</a>
					</li>
				<?php endif; ?>
				<?php if (USER::access('plugin') > 0) :?>
					<?php if(EDITION=='saas'){ ?>
						<li class="adm-section--plugins">
							<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="marketplace" data-vis-click="toMarketplaceTab" href="javascript:void(0);">Плагины</a>
					  </li>
					<?php }else{ ?>
						<li class="adm-section--plugins">
							<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="plugins" data-vis-click="toPluginsTab" href="javascript:void(0);">Плагины</a>
						</li>
				 <?php	} ?>
				<?php endif; ?>
				<?php if (USER::access('setting') > 0) :?>
					<li class="adm-section--sets">
						<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="settings" data-vis-click="toSettingsTab" href="javascript:void(0);">Настройки</a>
					</li>
				<?php endif; ?>
				<?php if (USER::access('plugin') > 1) :?>
					<?php if(EDITION!='saas'){ ?>
					<li class="adm-section--market">
						<a class="mg-admin-mainmenu-item js-mob-nav-close" data-section="marketplace" data-vis-click="toMarketplaceTab" href="javascript:void(0);">Маркетплейс</a>
					</li>
					<?php } ?>
				<?php endif; ?>
			</ul>

			<ul>
				<li class="cd-label">Действия</li>
				<li class="adm-section--gopublic" data-vis-click="adminToPublic">
					<a role="button" target="_blank" href="<?php echo SITE ?>/" data-vis-click="adminToPublic">Перейти на сайт</a>
				</li>
				<li class="adm-section--logout">
					<a role="button" class="logout-button" href="javascript:void(0);" data-vis-click="adminLogout">Выйти</a>
				</li>

			</ul>
		</nav>
	</div>
</div>
