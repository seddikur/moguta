<?php mgSEO($data);?>
<?php mgAddMeta('css/swiper-bundle.min.css'); ?>
<?php mgAddMeta('js/swiper-bundle.min.js'); ?>

<div class="max">
    <?php if (empty($data['searchData'])): ?>
        <div class="catalog">
                <div class="top-description">
                    <div class="content">
                        <h1><?php echo $data['titleCategory'] ?></h1>
                    </div>
                    <?php layout('menu');?>
                    <?php component('filter'); ?>
                    <?php component('filter/applied', $data['applyFilter']);?>
                </div>
            </div>

            <div class="products-wrapper js-products-container" role='main'>
                <div class="grid">
                    <?php foreach ($data['items'] as $item) { ?>
                        <?php component('catalog/item', ['item' => $item]); ?>
                    <?php } ?>
                </div>
            
                <?php if (count($data['items']) == 0 && $_GET['filter'] == 1) { ?>
                    <?php echo lang('searchFail') ?>
                <?php } ?>
    
                <?php if (!empty($data['pager'])): ?>
                    <?php component('pagination', $data['pager']); ?>
                <?php endif; ?>
            </div>
            
            <?php if ($data['cat_desc_seo']) { ?>
                <div class="company-description__section bottom-description content">
                    <?php echo $data['cat_desc_seo'] ?>
                </div>
            <?php } ?>
        </div>
            
    <?php else: ?>
        <div class="top-description">
            <h1><?php echo lang('search1'); ?> «<?php echo $data['searchData']['keyword'] ?>»</h1>
            <p><?php echo lang('search2'); ?> <b><?php echo mgDeclensionNum($data['searchData']['count'], array(lang('search3-1'), lang('search3-2'), lang('search3-3')));?></b></p>
        </div>

        <div class="products-wrapper js-products-container catalog">
            <div class="grid by-4">
                <?php foreach ($data['items'] as $item) { ?>
                    <?php component('catalog/item', ['item' => $item]); ?>
                <?php } ?>
            </div>
        </div>

        <?php if (!empty($data['pager'])): ?>
            <?php component('pagination', $data['pager']); ?>
        <?php endif; ?>
    <?php endif; ?>
</div>