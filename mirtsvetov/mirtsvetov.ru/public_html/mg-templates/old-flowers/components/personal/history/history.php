<?php if ($data['orderInfo']): ?>
    <div class="order-history-list">
        <?php $currencyShort = MG::getSetting('currencyShort'); $currencyShopIso = MG::getSetting('currencyShopIso'); foreach ($data['orderInfo'] as $order): ?>
            <div class="spoiler order-history" id="<?php echo $order['id'] ?>">
                <div class="spoiler-title flex align-center space-between">
                    <div class="status <?php echo(empty($data['assocStatusClass'][$order['status_id']]) ? 'customStatus' : $data['assocStatusClass'][$order['status_id']]) ?>" <?php echo ' style="'; if (isset($data['orderColors'][$order['status_id']]['bgColor'])) {echo 'background-color:' . $data['orderColors'][$order['status_id']]['bgColor'] . ';';} if (isset($data['orderColors'][$order['status_id']]['textColor'])) {echo 'color:' . $data['orderColors'][$order['status_id']]['textColor'] . ';';} echo '"';?>><?php echo $order['string_status_id'] ?>
                    </div>
                    <div class="order-number"><?php echo $order['number'] != '' ? $order['number'] : $order['id'] ?> <span class="mobile-hide">от <?php echo date('d.m.Y', strtotime($order['add_date'])) ?></span></div>
                </div>
                <div class="spoiler-content content">
                    <?php $perOrder['currency_iso'] = $perOrder['currency_iso'] ? $perOrder['currency_iso'] : $currencyShopIso; $perCurrencyShort = MG::getSetting('currency'); $perOrders = unserialize(stripslashes($order['order_content']));?><?php if (!empty($perOrders)) foreach ($perOrders as $perOrder): ?><?php $perCurrencyShort = $currencyShort[$perOrder['currency_iso']] ? $currencyShort[$perOrder['currency_iso']] : MG::getSetting('currency'); $coupon = $perOrder['coupon']; $res = DB::query("SELECT `" . PREFIX . "product`.id, `" . PREFIX . "category`.unit FROM `" . PREFIX . "product` LEFT JOIN `" . PREFIX . "category` ON `" . PREFIX . "product`.cat_id = `" . PREFIX . "category`.id WHERE `" . PREFIX . "product`.id = " . DB::quoteInt($perOrder['id'])); $row = DB::fetchAssoc($res); $unit = $row['unit']; if (strlen($unit) < 1) {$unit = 'шт.';}?>
                    <div class="flex align-center space-between">
                        <a class="name" href="<?php echo $perOrder['url'] ?>" target="_blank"><?php echo $perOrder['name'] ?><?php echo htmlspecialchars_decode(str_replace('&amp;', '&', $perOrder['property'])) ?></a>
                        <div class="code"><?php echo $perOrder['code']; ?></div>
                        <div class="price"><?php echo MG::numberFormat(($perOrder['price'])) . '  ' . $perCurrencyShort . '/' . $unit; ?></div>
                        <div class="qty"><?php echo $perOrder['count'] . ' ' . $unit ?></div>
                        <div class="total"><?php echo MG::numberFormat(($perOrder['price'] * $perOrder['count'])) . '  ' . $perCurrencyShort; ?></div>
                        <?php if (class_exists('p4_DigitalProduct')): ?>
                            [digital-product-key
                                 order_id="<?php echo $order['id']; ?>"
                                 product_id="<?php echo $perOrder['id']; ?>"
                                 variant_id="<?php echo $perOrder['variant_id']; ?>"
                            ]
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!empty($order['comment'])): ?>
                        <div class="alert"><?php echo $order['comment']; ?></div>
                    <?php endif; ?>
                    <ul class="total-list">
                        <?php if ($coupon): ?><li><b><?php echo lang('orderFinalCoupon'); ?></b> <?php echo MG::textMore($coupon, 20) ?></li><?php endif; ?>
                        <li><b><?php echo lang('orderFinalTotal'); ?></b> <?php echo MG::numberFormat($order['summ']) . '  ' . $perCurrencyShort ?></li>
                        <?php if ($order['description']): ?>
                            <li><b><?php echo lang('orderFinalDeliv'); ?></b> <?php echo $order['description'] ?></li>
                            <?php if ($order['date_delivery']): ?>
                                <li><b><?php echo lang('orderFinalDelivDate');?></b><?php echo date('d.m.Y', strtotime($order['date_delivery'])) ?></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li><b><?php echo lang('orderFinalPayment'); ?></b> <?php echo $order['name'] ?></li>
                        <?php $totSumm = $order['summ'] + $order['delivery_cost']; ?><?php if ($order['delivery_cost']): ?>
                            <li><b><?php echo lang('orderFinalDeliv'); ?></b><?php echo MG::numberFormat($order['delivery_cost']) . '  ' . $perCurrencyShort; ?></li>
                        <?php endif; ?>
                        <li><b><?php echo lang('orderFinalPay'); ?></b><?php echo MG::numberFormat($totSumm) . '  ' . $perCurrencyShort; ?></li>
                    </ul>
                    <?php if (2 > $order['status_id']):?>
                        <form method="POST" action="<?php echo SITE ?>/order">
                            <input type="hidden" name="orderID" value="<?php echo $order['id'] ?>">
                            <input type="hidden" name="orderSumm" value="<?php echo $order['summ'] ?>">
                            <input type="hidden" name="paymentId" value="<?php echo $order['payment_id'] ?>">
                            <?php if ($order['payment_id'] != 3 && $order['payment_id'] != 4 && ($order['showPaymentForm'] == 1 || !isset($order['showPaymentForm']))):?>
                                <button type="submit" class="button" name="pay" value="go"><?php echo lang('orderFinalButton'); ?></button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>        
    </div>
<?php else: ?>
    <div class="alert"><?php echo lang('personalNoOrders'); ?></div>
<?php endif ?>

<?php if (!empty($data['pagination'])): ?>
    <?php component('pagination', $data['pagination']); ?>
<?php endif; ?>