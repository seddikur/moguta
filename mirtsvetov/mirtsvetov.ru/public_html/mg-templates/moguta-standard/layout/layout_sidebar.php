<div class="l-col min-12--hide min-1025--3 l-main__left">

    <?php if (class_exists('dailyProduct')): ?>
        <div class="daily-wrapper">
            [daily-product]
        </div>
    <?php endif; ?>

    <?php if (!isSearch()): ?>
        <div class="c-filter" id="c-filter" onClick="">
            <div class="c-filter__content">
                <?php
                // Компонент фильтра
                    component('filter');
                ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (class_exists('SliderProducts')): ?>
        <div class="mg-advise">
            <div class="mg-advise__title">
                <?php echo lang('recommend'); ?>
            </div>
        <?php mgAddMeta('components/plugin-style/slider-products.css'); ?>
            [mg-slider-products id="<?php echo MG::get('templateParams')['SLIDER_PRODUCT']['slider_id'] ?>"]
            <!-- cлайдер товаров -->
        </div>
    <?php endif ?>

    <?php if (class_exists('PluginNews')): ?>
        [news-anons count="3"]
    <?php endif; ?>

    <?php if (isCatalog() && class_exists('chdManualRelinking')): ?>
        [chd-manual-relinking]
    <?php endif; ?>

</div>
