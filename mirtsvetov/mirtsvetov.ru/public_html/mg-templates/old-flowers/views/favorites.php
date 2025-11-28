<?php mgSEO($data);?>

<div class="max">
    <h1><?php echo $data['titleCategory'] ?></h1>
    <?php if(!$data['items']):?>
        <div class="alert">Список избранного пуст</div>
    <?php endif;?>
    <div class="products-wrapper js-products-container catalog">
        <div class="grid by-4">
            <?php foreach ($data['items'] as $item) { ?>
              <?php component('catalog/item', ['item' => $item]); ?>
            <?php } ?>
        </div>
    </div>
</div>