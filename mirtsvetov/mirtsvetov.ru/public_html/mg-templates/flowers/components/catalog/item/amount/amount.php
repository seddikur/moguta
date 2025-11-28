<?php
    mgAddMeta('components/catalog/item/amount/amount.js');
    // Добавление, изменение, удаление товара из корзины
    mgAddMeta('components/cart/cartAPI/cartAPI.js');
?>

<div class="amount custom-amount flex align-center">
    <button type="button" class="amount__down amount__change">
        <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#minus"></use></svg>
    </button>

    <input type="text" name="amount_input" class="amount__input" autocomplete="off" inputmode="numeric"
           data-product="<?php echo $data['id']; ?>"
           data-variant="<?php echo $data['variant']; ?>"
           data-max-count="<?php echo $data['maxCount']; ?>"
           data-increment-count="<?php echo $data['increment']; ?>"
           value="<?php echo $data['inCart']; ?>">

    <button type="button" class="amount__up amount__change">
        <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#plus"></use></svg>
    </button>

    <?php if ($data['inCart'] >= $data['maxCount']): ?>
        <div class="js-max-count">Max: <?php echo $data['maxCount']; ?></div>
    <?php endif; ?>
</div>