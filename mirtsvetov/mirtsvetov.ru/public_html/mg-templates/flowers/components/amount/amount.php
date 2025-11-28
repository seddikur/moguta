<?php mgAddMeta('components/amount/amount.js'); if ($data['type'] === 'cart') {mgAddMeta('components/cart/cart.amount.js');}?>
<div class="cart_form nowrap js-amount-wrap amount_change flex" <?php if(!MG::enabledStorage()){echo abs($data['maxCount'])>0?'':'style="display:none"';} ?>>
    <button class="mines flex align-center center down js-amount-change-down js-cart-amount-change"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#minus"></use></svg></button>
    <?php $data['increment'] = !empty($data['increment'])?$data['increment']:1;?>
    <input type="text" name="<?php echo ($data['type'] === 'cart') ? 'item_' . $data['id'] . '[]' : 'amount_input' ?>" class="quantity amount_input zeroToo js-amount-input" data-max-count="<?php echo $data['maxCount']; ?>" data-increment-count="<?php echo $data['increment']; ?>" value="<?php echo $data['count'] ?>"/>
    <button class="plus flex align-center center up js-amount-change-up js-cart-amount-change js-onchange-price-recalc"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#plus"></use></svg></button>
</div>