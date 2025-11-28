<?php mgSEO($data);?>

<div class="max">
    <div class="alert" style="display:<?php echo $data['isEmpty'] ? 'none' : 'block'; ?>"><?php echo lang('cartIsEmpty'); ?></div>
    <h1><?php echo lang('productCart'); ?></h1>
    <?php component('cart', $data, 'cart');?>
    <?php component('catalog/carousel',
        [
            'items' => $data['related']['products'],
            'title' => lang('relatedAddCart')
        ]
    );?>
</div>