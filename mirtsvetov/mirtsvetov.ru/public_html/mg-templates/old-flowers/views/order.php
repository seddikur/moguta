<div class="max">
    <?php if (class_exists('GsSDEC')): ?>
        [sdec_system]
    <?php endif; ?>
    
    <?php
    if (!empty($data['fileToOrder'])) {
        component('order/electro', $data);
    } else {
        switch ($data['step']) {
    
            // Оформление заказа
            case 1:
                mgSEO($data);
    
                $model = new Models_Cart();
                $cartData = $model->getItemsCart();
                $data['isEmpty'] = $model->isEmptyCart();
                $data['productPositions'] = $cartData['items'];
                $data['totalSumm'] = $cartData['totalSumm'];?>
                
                <div class="alert" style="display:<?php echo !$data['isEmpty'] ? 'block' : 'none'; ?>"><?php echo lang('cartIsEmpty'); ?></div>
                <div class="space-between flex" <?php if (!$data['isEmpty']):?>style="display: none"<?php endif;?>>
                    <div class="left-part">
                        <?php component('order', $data);?>
                    </div>
                    <div class="right-part">
                        <div class="sticky">
                            <?php component('cart', $data);?>
                        </div>
                    </div>
                </div>
                
            <?php break;
    
            // Оплата заказа
            case 2:
                component('payment', $data);
                break;
    
            // Подтверждение заказа
            case 3:
                component('order/confirm', $data);
                break;
    
            // Оплата заказа из личного кабинета
            case 4:
                component('payment', $data, 'payment_from_personal');
                break;
    
            // Информация о статусе заказа при переходе по ссылке из письма
            case 5:
                component('order/info', $data);
        }
    } ?>
</div>