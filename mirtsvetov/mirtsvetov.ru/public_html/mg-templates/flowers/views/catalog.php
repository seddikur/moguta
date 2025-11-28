<?php mgSEO($data);?>
<?php mgAddMeta('css/swiper-bundle.min.css'); ?>
<?php mgAddMeta('js/swiper-bundle.min.js'); ?>

<?php 
    // Возвращает товара из корзины для последующего вывода в amount.
    $data['inCart'] = getProductsInCart();
?>

<div class="max">
    <?php if (empty($data['searchData'])): ?>
        <div class="catalog">
            <div class="top-description">
                <div class="content">
                    <h1><?php echo $data['titleCategory'] ?></h1>
                </div>
                <?php layout('menu');?>
            </div>
        </div>            

        <div class="products-wrapper flex space-between js-products-container <?php echo (User::isAuth())?'auth':'';?>" role='main'>
            <div class="left-part">
                <?php 
                    if (User::isAuth()) {
                        component('filter');
                    }
                ?>
                <div class="grid">
                    <?php foreach ($data['items'] as $item) {
                        if($item['thisUserFields'][10] && !User::isAuth()) continue;
                        component('catalog/item', ['item' => $item, 'inCart' => $data['inCart']]);
                    } ?>
                </div>
            </div>

            <?php 
                if (User::isAuth()) {
                    component(
                        'cart/catalog', [], 'cart'
                    );
                }    
            ?>

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
            
    <?php else: ?>
        <div class="top-description">
            <h1><?php echo lang('search1'); ?> «<?php echo $data['searchData']['keyword'] ?>»</h1>
            <p><?php echo lang('search2'); ?> <b><?php echo mgDeclensionNum($data['searchData']['count'], array(lang('search3-1'), lang('search3-2'), lang('search3-3')));?></b></p>
        </div>

        <div class="products-wrapper js-products-container catalog">
            <div class="grid by-4">
                <?php foreach ($data['items'] as $item) {
                    if($item['thisUserFields'][10] && !User::isAuth()) continue;
                    component('catalog/item', ['item' => $item, 'inCart' => $data['inCart']]);
                } ?>
            </div>
        </div>

        <?php if (!empty($data['pager'])): ?>
            <?php component('pagination', $data['pager']); ?>
        <?php endif; ?>
    <?php endif; ?>
</div>