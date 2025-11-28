<?php
mgAddMeta('components/catalog/switcher/switcher.css');
mgAddMeta('components/catalog/switcher/switcher.js');
?>
<div class="l-col min-0--hide min-768--6 min-1025--12 c-catalog__switchers">
    <div class="c-switcher">
        <button class="js-switch-view c-switcher__item c-switcher__item--active"
                data-type="c-goods--grid"
                title="<?php echo lang('viewNet'); ?>">
            <svg class="icon icon--grid">
                <use xlink:href="#icon--grid"></use>
            </svg>
        </button>
        <button class="js-switch-view c-switcher__item"
                data-type="c-goods--list"
                title="<?php echo lang('viewList'); ?>">
            <svg class="icon icon--list">
                <use xlink:href="#icon--list"></use>
            </svg>
        </button>
    </div>
</div>