<?php mgAddMeta('components/personal/history/history.js'); ?>
<?php mgAddMeta('components/personal/history/history.css'); ?>
<?php mgAddMeta('components/modal/modal.css'); ?>
<?php mgAddMeta('components/modal/modal.js'); ?>

<?php if ($data['orderInfo']): ?>
    <div class="l-row">
        <div class="l-col min-0--12">

            <?php if(class_exists('bonusCard')) { ?>
                [bonus-card]
            <?php } ?>

            <div class="c-history order-history-list">
              <?php $currencyShort = MG::getSetting('currencyShort');
              $currencyShopIso = MG::getSetting('currencyShopIso');
              foreach ($data['orderInfo'] as $order): ?>
                  <div class="c-history__item order-history <?php echo count($data['orderInfo']) === 1 ? 'c-history__item--active' : '' ?>"
                       id="<?php echo $order['id'] ?>">
                      <div class="c-history__header order-number">
                          <div class="c-history__header--left">
                              <strong><?php echo $order['number'] != '' ? $order['number'] : $order['id'] ?></strong>
                              от <?php echo date('d.m.Y', strtotime($order['add_date'])) ?>
                          </div>
                          <div class="c-history__header--right">
                                    <span class="order-status">
                                        <span class="c-history__status <?php echo(empty($data['assocStatusClass'][$order['status_id']]) ? 'customStatus' : $data['assocStatusClass'][$order['status_id']]) ?>"
                                          <?php
                                          echo ' style="';
                                          if (isset($data['orderColors'][$order['status_id']]['bgColor'])) {
                                            echo 'background-color:' . $data['orderColors'][$order['status_id']]['bgColor'] . ';';
                                          }
                                          if (isset($data['orderColors'][$order['status_id']]['textColor'])) {
                                            echo 'color:' . $data['orderColors'][$order['status_id']]['textColor'] . ';';
                                          }
                                          echo '"';
                                          ?>
                                          ><?php echo $order['string_status_id'] ?></span>
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
                                    <?php if (!empty($perOrders)) foreach ($perOrders as $perOrder): ?>
                                      <?php
                                      $perCurrencyShort = $currencyShort[$perOrder['currency_iso']] ? $currencyShort[$perOrder['currency_iso']] : MG::getSetting('currency');
                                      $coupon = $perOrder['coupon'];
                                      $res = DB::query("SELECT `" . PREFIX . "product`.id, `" . PREFIX . "category`.unit
                                                                        FROM `" . PREFIX . "product`
                                                                        LEFT JOIN `" . PREFIX . "category` ON `" . PREFIX . "product`.cat_id = `" . PREFIX . "category`.id
                                                                        WHERE `" . PREFIX . "product`.id = " . DB::quoteInt($perOrder['id']));
                                      $row = DB::fetchAssoc($res);
                                      $unit = $row['unit'];
                                      if (strlen($unit) < 1) {
                                        $unit = 'шт.';
                                      }
                                      ?>
                                        <tr>
                                            <td class="c-history__table_title">
                                                <a class="c-history__table--title"
                                                   href="<?php echo $perOrder['url'] ?>"
                                                   target="_blank">
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
                                                  <?php echo MG::numberFormat(($perOrder['price'])) . '  ' . $perCurrencyShort . '/' . $unit; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="c-history__table--quantity">
                                                  <?php echo $perOrder['count'] . ' ' . $unit ?>
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
                            <?php if (
                                MG::getSetting('useElectroLink') != 'false' &&
                                (!isset($order['electro']) || $order['electro']) &&
                                ($order['status_id'] == 2 || $order['status_id'] == 5)
                            ): ?>
                                <div class="c-history__row">
                                    <a class="c-history__download download-link"
                                       href="<?php echo SITE . '/order?getFileToOrder=' . $order['id'] ?>">
                                        <svg class="icon icon--download"> 
                                            <use xlink:href="#icon--download"></use>
                                        </svg>
                                      <?php echo lang('orderDownloadDigital'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php $yurInfo = unserialize(stripslashes($order['yur_info']));
                            if (!empty($yurInfo['inn']) && $order['showPaymentForm'] == 1): ?>
                                <div class="c-history__row">
                                    <a class="c-history__download download-link"
                                       href="<?php echo SITE . '/order?getOrderPdf=' . $order['id'] ?>">
                                        <svg class="icon icon--download">
                                            <use xlink:href="#icon--download"></use>
                                        </svg>
                                      <?php echo lang('orderDownloadPdf'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($order['status_id'] < 2): ?>
                                <div class="order-settings">
                                    <div class="c-history__row">
                                        <a href="#js-modal__reason"
                                           class="c-button c-button--border close-order"
                                           id="<?php echo $order['id'] ?>"
                                           date="<?php echo date('d.m.Y', strtotime($order['add_date'])) ?>"
                                           data-number="<?php echo $order['number'] != '' ? $order['number'] : $order['id']; ?>">
                                          <?php echo lang('orderCancel'); ?>
                                        </a>
                                    </div>
                                  <?php $urInfo = unserialize(stripcslashes($order['yur_info']));
                                  if (empty($urInfo['inn'])) { ?>
                                      <div class="c-history__row">
                                          <a href="#js-modal__payment"
                                             class="c-button c-button--border change-payment"
                                             id="<?php echo $order['id'] ?>"
                                             data-customer="<?php echo empty($urInfo['inn']) ? 'fiz' : "yur"; ?>"
                                             data-delivery-id="<?php echo $order['delivery_id']; ?>"
                                             date="<?php echo date('d.m.Y', strtotime($order['add_date'])) ?>"
                                             data-number="<?php echo $order['number'] != '' ? $order['number'] : $order['id']; ?>">
                                            <?php echo lang('orderChangePayment'); ?>
                                          </a>
                                      </div>
                                  <?php } ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($order['comment'])): ?>
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

                                    <?php if ($coupon): ?>
                                        <li class="c-history__list--item">
                                            <b><?php echo lang('orderFinalCoupon'); ?></b>
                                            <span
                                                    title="<?php echo $coupon ?>"><?php echo MG::textMore($coupon, 20) ?></span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!empty($order['address'])) { ?>
                                        <li class="c-history__list--item">
                                        <b><?php echo lang('orderFinalAddress'); ?></b>
                                          <span class="c-history__list-adress"><?php echo $order['address'] ?></span>
                                      </li>
                                    <?php } ?>

                                      <li class="c-history__list--item">
                                          <b><?php echo lang('orderFinalTotal'); ?></b>
                                          <span
                                                  class="total-summ"><?php echo MG::numberFormat($order['summ']) . '  ' . $perCurrencyShort ?></span>
                                      </li>

                                    <?php if ($order['description']): ?>
                                        <li class="c-history__list--item">
                                            <b><?php echo lang('orderFinalDeliv'); ?></b>
                                            <span><?php echo $order['description'] ?></span>
                                        </li>

                                      <?php if ($order['date_delivery']): ?>
                                            <li class="c-history__list--item">
                                                <b><?php echo lang('orderFinalDelivDate'); ?></b>
                                                <span><?php echo date('d.m.Y', strtotime($order['date_delivery'])) ?></span>
                                            </li>
                                      <?php endif; ?>
                                    <?php endif; ?>

                                      <li class="c-history__list--item">
                                          <b><?php echo lang('orderFinalPayment'); ?></b>
                                          <span
                                                  class="paymen-name-to-history"><?php echo $order['name'] ?></span>
                                      </li>

                                    <?php $totSumm = $order['summ'] + $order['delivery_cost']; ?>
                                    <?php if ($order['delivery_cost']): ?>
                                        <li class="c-history__list--item">
                                            <b><?php echo lang('orderFinalDeliv'); ?></b>
                                            <span
                                                    class="delivery-price"><?php echo MG::numberFormat($order['delivery_cost']) . '  ' . $perCurrencyShort; ?></span>
                                        </li>
                                    <?php endif; ?>

                                      <li class="c-history__list--item c-history__list--total">
                                          <b><?php echo lang('orderFinalPay'); ?></b>
                                          <span
                                                  class="total-order-summ"><?php echo MG::numberFormat($totSumm) . '  ' . $perCurrencyShort; ?></span>
                                      </li>

                                    <?php if (2 > $order['status_id']): ?>
                                        <li class="c-history__list--item">
                                            <div class="order-settings">
                                                <form class="c-form"
                                                      method="POST"
                                                      action="<?php echo SITE ?>/order">
                                                    <input type="hidden"
                                                           name="orderID"
                                                           value="<?php echo $order['id'] ?>">
                                                    <input type="hidden"
                                                           name="orderSumm"
                                                           value="<?php echo $order['summ'] ?>">
                                                    <input type="hidden"
                                                           name="paymentId"
                                                           value="<?php echo $order['payment_id'] ?>">
                                                  <?php
                                                   if ($order['payment_id'] != 3 && $order['payment_id'] != 4 && ($order['showPaymentForm'] == 1 || !isset($order['showPaymentForm']))) : ?>
                                                      <button type="submit"
                                                              class="c-button"
                                                              name="pay"
                                                              value="go"><?php echo lang('orderFinalButton'); ?></button>
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
              <?php endforeach; ?>

                <!-- change payment - start -->
                <div class="c-modal c-modal--500"
                     id="js-modal__payment">
                    <div class="c-modal__wrap">
                        <div class="c-modal__content">
                            <div class="c-modal__close">
                                <svg class="icon icon--close">
                                    <use xlink:href="#icon--close"></use>
                                </svg>
                            </div>
                            <div class="c-form"
                                 id="changePayment">
                                <div class="c-form__row">
                                    <div class="order-number">
                                      <?php echo lang('personalOrderFrom1'); ?>
                                        <strong name="orderId"
                                                class="orderId"></strong> <?php echo lang('personalOrderFrom2'); ?>
                                        <span class="orderDate"></span>
                                    </div>
                                </div>
                                <div class="c-form__row">
                                    <select class="order-changer-pay">
                                    <?php
                                            foreach ($data['paymentList'] as $item) {
                                                if (empty($item)) {
                                                    continue;
                                                }
                                                $delivery = json_decode($item['deliveryMethod']);
                                                if ($delivery->{$order['delivery_id']}) {
                                                    echo "<option value='" . $item['id'] . "'>" . $item['name'] . '</option>';
                                                }
                                            }
                                            ?>
                                    </select>
                                </div>
                                <div class="c-form__row">
                                    <button type="submit"
                                            class="c-button change-payment-btn default-btn"><?php echo lang('apply'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- change payment - end -->
                <!-- reason - start -->
                <div class="c-modal c-modal--500"
                     id="js-modal__reason">
                    <div class="c-modal__wrap">
                        <div class="c-modal__content">
                            <div class="c-modal__close">
                                <svg class="icon icon--close">
                                    <use xlink:href="#icon--close"></use>
                                </svg>
                            </div>
                            <div class="c-form"
                                 id="openModal">
                                <div class="c-form__row">
                                     <textarea class="reason-text"
                                               name="comment_textarea"
                                               placeholder="<?php echo lang('personalOrderClose1'); ?>"></textarea>
                                </div>
                                <div class="c-form__row">
                                    <button type="submit"
                                            class="c-button close-order-btn"><?php echo lang('send'); ?></button>
                                </div>
                                <div class="order-number"
                                     style="display: none;"><?php echo lang('personalOrderClose2'); ?>
                                    <strong
                                            name="orderId"
                                            class="orderId"></strong> <?php echo lang('personalOrderClose3'); ?>
                                    <span class="orderDate"></span>
                                </div>
                            </div>
                            <div class="c-history__hidden"
                                 id="successModal">
                                <div class="c-alert c-alert--green">
                                  <?php echo lang('personalOrderClose4'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- reason - end -->
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="c-alert c-alert--blue msgError"><?php echo lang('personalNoOrders'); ?></div>
<?php endif ?>


<?php if (!empty($data['pagination'])): ?>
    <!-- pager - start -->
    <div class="l-col min-0--12">
        <div class="c-pagination">
          <?php component('pagination', $data['pagination']); ?>
        </div>
    </div>
    <!-- pager - end -->
<?php endif; ?>
