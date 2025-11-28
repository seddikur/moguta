<div class="l-row">

    <div class="l-col min-0--12">
        <div class="c-title">
            <?php echo lang('orderStatus'); ?>
        </div>
    </div>

    <?php if ($data['msg']) { ?>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--orange errorSend">
                <?php echo $data['msg']; ?>
            </div>
        </div>
    <?php } else {
        $order = $data['orderInfo'][$data['id']]; ?>

        <div class="l-col min-0--12">
            <div class="c-history__item order-history" id="<?php echo $order['id'] ?>">
                <div class="c-history__header order-number">
                    <div class="c-history__header--left">
                        <strong><?php echo $order['number'] != '' ? $order['number'] : $order['id'] ?></strong>
                        от <?php echo date('d.m.Y', strtotime($order['add_date'])) ?>
                    </div>
                    <div class="c-history__header--right">
                        <span class="order-status">
                            <?php if ($order['status_id'] == 0) { ?>
                                <span class="c-history__status dont-confirmed"><?php echo lang('paymentNotConfirmed'); ?></span>
                            <?php } else if ($order['status_id'] == 1) { ?>
                                <span class="c-history__status get-paid"><?php echo lang('paymentWaiting'); ?></span>
                            <?php } else if ($order['status_id'] == 2) { ?>
                                <span class="c-history__status paid"><?php echo lang('paymentPaid'); ?></span>
                            <?php } else if ($order['status_id'] == 3) { ?>
                                <span class="c-history__status in-delivery"><?php echo lang('paymentDelivering'); ?></span>
                            <?php } else if ($order['status_id'] == 4) { ?>
                                <span class="c-history__status dont-paid"><?php echo lang('paymentCanceled'); ?></span>
                            <?php } else if ($order['status_id'] == 5) { ?>
                                <span class="c-history__status performed"><?php echo lang('paymentCompleted'); ?></span>
                            <?php } else { ?>
                                <span class="c-history__status processed"><?php echo lang('paymentProcessing'); ?></span>
                            <?php } ?>
                        </span>
                    </div>
                </div>
                <div class="c-history__content">
                    <div class="c-history__content--top">
                        <div class="c-table c-table--hover c-history__table">
                            <table class="status-table">
                                <?php
                                $perOrder['currency_iso'] = $perOrder['currency_iso'] ? $perOrder['currency_iso'] : $currencyShopIso;
                                $perCurrencyShort = MG::getSetting('currency');
                                $perOrders = unserialize(stripslashes($order['order_content']));
                                ?>
                                <?php if (!empty($perOrders)) foreach ($perOrders as $perOrder) : ?>
                                    <?php
                                    $perCurrencyShort = $currencyShort[$perOrder['currency_iso']] ? $currencyShort[$perOrder['currency_iso']] : MG::getSetting('currency');
                                    $coupon = $perOrder['coupon'];
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="c-history__table--title" href="<?php echo $perOrder['url'] ?>" target="_blank">
                                                <?php echo $perOrder['name'] ?>
                                                <?php echo htmlspecialchars_decode(str_replace('&amp;', '&', $perOrder['property'])) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="c-history__table--code">
                                                Код: <?php echo $perOrder['code'] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="c-history__table--price">
                                                <?php echo MG::numberFormat(($perOrder['price'])) . '  ' . $perCurrencyShort; ?>
                                                /шт.
                                            </div>
                                        </td>
                                        <td>
                                            <div class="c-history__table--quantity">
                                                <?php echo $perOrder['count'] ?> шт.
                                            </div>
                                        </td>
                                        <td>
                                            <div class="c-history__table--total">
                                                <?php echo MG::numberFormat(($perOrder['price'] * $perOrder['count'])) . '  ' . $perCurrencyShort; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                    <div class="c-history__content--left">
                        <?php if (($order['status_id'] == 2 || $order['status_id'] == 5) && MG::getSetting('useElectroLink') != 'false') : ?>
                            <div class="c-history__row">
                                <a class="c-history__download download-link" href="<?php echo SITE . '/order?getFileToOrder=' . $order['id'] ?>">
                                    <svg class="icon icon--download">
                                        <use xlink:href="#icon--download"></use>
                                    </svg>
                                    <?php echo lang('orderDownloadDigital'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php $yurInfo = unserialize(stripslashes($order['yur_info']));
                        if (!empty($yurInfo['inn'])) : ?>
                            <div class="c-history__row">
                                <a class="c-history__download download-link" href="<?php echo SITE . '/order?getOrderPdf=' . $order['id'] ?>">
                                    <svg class="icon icon--download">
                                        <use xlink:href="#icon--download"></use>
                                    </svg>
                                    <?php echo lang('orderDownloadPdf'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($order['comment'])) : ?>
                            <div class="c-history__row">
                                <div class="c-alert c-alert--blue">
                                    <?php echo $order['comment']; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="c-history__content--right">
                        <div class="order-total">
                            <ul class="c-history__list total-list">
                                <?php if ($coupon) : ?>
                                    <li class="c-history__list--item">
                                        <b><?php echo lang('orderFinalCoupon'); ?></b> <span title="<?php echo $coupon ?>"><?php echo MG::textMore($coupon, 20) ?></span>
                                    </li>
                                <?php endif; ?>

                                <li class="c-history__list--item">
                                    <b><?php echo lang('orderFinalTotal'); ?></b> <span class="total-summ"><?php echo MG::numberFormat($order['summ']) . '  ' . $perCurrencyShort ?></span>
                                </li>

                                <?php if ($order['description']) : ?>
                                    <li class="c-history__list--item">
                                        <b><?php echo lang('orderFinalDeliv'); ?></b>
                                        <span><?php echo $order['description'] ?></span>
                                    </li>

                                    <?php if ($order['date_delivery']) : ?>
                                        <li class="c-history__list--item">
                                            <b><?php echo lang('orderFinalDelivDate'); ?></b>
                                            <span><?php echo date('d.m.Y', strtotime($order['date_delivery'])) ?></span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <li class="c-history__list--item">
                                    <b><?php echo lang('orderFinalPayment'); ?></b> <span class="paymen-name-to-history"><?php echo $order['paymentName'] ?></span>
                                </li>

                                <?php $totSumm = $order['summ'] + $order['delivery_cost']; ?>
                                <?php if ($order['delivery_cost']) : ?>
                                    <li class="c-history__list--item">
                                        <b><?php echo lang('orderFinalDeliv'); ?></b> <span class="delivery-price"><?php echo MG::numberFormat($order['delivery_cost']) . '  ' . $perCurrencyShort; ?></span>
                                    </li>
                                <?php endif; ?>

                                <li class="c-history__list--item c-history__list--total">
                                    <b><?php echo lang('orderFinalPay'); ?></b> <span class="total-order-summ"><?php echo MG::numberFormat($totSumm) . '  ' . $perCurrencyShort; ?></span>
                                </li>

                                <?php if (2 > $order['status_id']) : ?>
                                    <li class="c-history__list--item">
                                        <div class="order-settings">
                                            <form class="c-form" method="POST" action="<?php echo SITE ?>/order">
                                                <input type="hidden" name="orderID" value="<?php echo $order['id'] ?>">
                                                <input type="hidden" name="orderSumm" value="<?php echo $order['summ'] ?>">
                                                <input type="hidden" name="paymentId" value="<?php echo $order['payment_id'] ?>">
                                                <?php if ($order['payment_id'] != 3 && $order['payment_id'] != 4 && ($order['showPaymentForm'] == 1 || !isset($order['showPaymentForm']))) : ?>
                                                    <button type="submit" class="c-button" name="pay" value="go"><?php echo lang('orderFinalButton'); ?></button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>