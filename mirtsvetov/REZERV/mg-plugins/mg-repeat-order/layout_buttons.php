<div class="mg-plugin-repeat-order">
    <?php
if (isset($repeat) && $repeat) { ?>
    <div class="wrapper-mg-plugin-repeat-order">
<button class="mg-plugin-repeat-order plugin-button-repeat" data-id="<?php echo $arg['id']?>">
    Повторить заказ
</button>
<div class="mg-tooltip">?<div class="mg-tooltip-content" style="display:none;">
        Заказ будет продублирован с теми же данными, в т.ч. способ доставки и оплаты
    </div></div>        
    </div>
<?php }
  if (isset($edit) && $edit) { ?>
    <div class="wrapper-mg-plugin-edit-order">
<button class="button mg-plugin-edit-order plugin-button-repeat" data-id="<?php echo $arg['id']?>">
    Повторить заявку
</button>
    </div>
<?php } ?>
</div>

