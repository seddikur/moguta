<header class="l-header">
    <div class="l-header__top">
        <div class="l-container">
            <div class="l-row">
                <div class="l-col min-0--3 min-1025--8 l-header__left <?php echo class_exists('bonusCard') ? 'align-center' : '' ;?>">
                    <div class="l-header__block">
                        <?php
                        // Компонент меню страниц – menu/pages
                        component('menu/pages', $data['menuPages']) ?>
                    </div>
                </div>
                <div class="lcg l-col min-0--9 min-1025--4 l-header__right">
                    <?php if(class_exists('bonusCard')) { ?>
                        <div class="с-bonus-info">
                            <div class="с-bonus-card-btn">
                                <a href="<?php echo SITE ?>/enter" class="bonus-card-link">
                                    <span><?php echo lang('payBonusCount'); ?> [bonus-count]</span>
                                </a>
                            </div>
                            <?php if (MG::get('controller') != 'controllers_personal') { ?>
                            <div class="с-header-bonus-popup">[pay-bonus]</div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php
                    // Компонент выбора языка сайта – language_select
                    component('select/lang');

                    // Компонент выбора валюты сайта – currency_select
                    component('select/currency');
                    ?>
                    <div class="l-header__block group">
                        <?php
                        //  Компонент меню групп товаров - groups_menu
                        component('menu/groups');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="l-header__middle">
        <div class="l-container">
            <div class="l-row min-0--align-center">
                <div class="l-col min-0--12 min-768--3">
                    <a class="c-logo"
                       title="<?php echo htmlspecialchars(MG::getSetting('sitename')) ?>"
                       href="<?php echo SITE ?>">
                        <?php echo mgLogo(); ?>
                    </a>
                </div>
                <div class="l-col min-0--12 min-768--9">
                    <div class="min-0--flex min-0--justify-center min-768--justify-end">
                        
                        <div class="l-header__block">
                            <?php
                            // Компонент контактов в шапке – contacts
                            component('contacts');
                            ?>
                        </div>
                        <div class="l-header__block">
                            <?php
                            // Компонент кнопки перехода в лк/страницу авторизации
                            component('auth/login/link', MG::get('templateData'));
                            ?>
                        </div>
                        <?php
                            // Всплывающее оповещение о добавлении товара в избранное
                            component('favorites/informer');
                        ?>
                        <?php if (MG::getSetting('printCompareButton') == 'true') { ?>
                            <div class="l-header__block max-767--hide">
                                <?php
                                // Компонент кнопки сравнения в шапке
                                component('compare/link');
                                ?>
                            </div>
                        <?php } ?>

                        
                        <div class="l-header__block">
                            <?php
                            // Компонент всплывающей мини-корзины
                            component('cart/small', $data['cartData']);

                            // Если в настройках включена опция
                            // «Показывать покупателю сообщение о добавлении товара в корзину»,
                            // то выводим компонент модального окна с шаблоном «modal_cart»
                            if (MG::getSetting('popupCart') == 'true') {
                                component('modal', $data['cartData'], 'modal_cart');
                            };
                            ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="l-header__bottom">
        <div class="l-container">
            <div class="l-row">
                <div class="l-col min-0--5 min-768--3 l-header__catalog">
                    <div class="l-header__block">
                        <?php
                        // меню категорий каталога
                        component('menu/categories', $data['menuCategories']);
                        ?>
                    </div>
                </div>
                <div class="l-col min-0--7 min-768--9 l-header__search">
                    <div class="l-header__block">
                        <?php
                        // Поиск по каталогу
                        component('search'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>