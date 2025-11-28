<?php
mgAddMeta('components/amount/amount.js');

if ($data['type'] === 'cart') {
  mgAddMeta('components/cart/cart.amount.js');
}
?>

<div class="cart_form js-amount-wrap"  <?php if(!MG::enabledStorage()){echo abs($data['maxCount'])>0?'':'style="display:none"';} ?>>
    <div class="c-amount amount_change">
        <button class="c-amount__up up js-amount-change-up js-cart-amount-change js-onchange-price-recalc" aria-label="<?php echo lang('plus'); ?>">
            <svg class="icon icon--arrow-right">
                <use xlink:href="#icon--arrow-right"></use>
            </svg>
        </button>
        <?php
        //viewData($data);
        // кратность, по сколько товаров будет добавляться в корзину при нажатии на кнопку 'купить' или при изменении количества в корзине
        // оно же наименьшее количество товара в корзине
        $data['increment'] = !empty($data['increment'])?$data['increment']:1;
        ?>

        <input type="text"
               name="<?php echo ($data['type'] === 'cart') ? 'item_' . $data['id'] . '[]' : 'amount_input' ?>"
               aria-label="Количество данного товара"
               class="amount_input zeroToo js-amount-input"
               data-max-count="<?php echo $data['maxCount']; ?>"
               data-increment-count="<?php echo $data['increment']; ?>"
               value="<?php echo $data['count'] ?>"/>

        <button class="c-amount__down down js-amount-change-down js-cart-amount-change" aria-label="<?php echo lang('minus'); ?>">
            <svg class="icon icon--arrow-left">
                <use xlink:href="#icon--arrow-left"></use>
            </svg>
        </button>
    </div>
</div>
