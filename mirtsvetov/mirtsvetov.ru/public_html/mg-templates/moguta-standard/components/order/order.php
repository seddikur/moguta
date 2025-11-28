<?php
if (MG::getSetting('usePhoneMask') == 'true') {
    mgAddMeta('lib/jquery.maskedinput.min.js');
}
mgAddMeta('components/order/form/form.js');

mgAddMeta('lib/datepicker.css');
?>
<div class="l-row min-0--12">
    <div class="c-order checkout-form-wrapper"
         style="display:<?php echo $data['isEmpty'] ? 'block' : 'none'; ?>">

        <?php
        // Ошибка
        if ($data['msg']): ?>
            <div class="l-col min-0--12">
                <div class="c-alert c-alert--red mg-error">
                    <?php echo $data['msg'] ?>
                </div>
            </div>
        <?php endif; ?>

        <!--    Форма оформления заказа    -->
        <div class="l-col min-0--12">
            <div class="payment-option">
                <form class="c-form js-orderForm"
                      action="<?php echo SITE ?>/order?creation=1"
                      method="post">
                    <div class="l-row">
                        <div class="l-col min-0--12 min-768--6 min-990--3">
                            <div class="order-podlojka">
                                <div class="c-order__title">
                                    1.&nbsp;<?php echo lang('orderDelivery'); ?>
                                </div>

                                <?php
                                // Способы доставки
                                if ('' != $data['delivery']): ?>
                                    <ul class="c-order__payment delivery-details-list">
                                        <?php foreach ($data['delivery'] as $delivery): ?>
                                            <li <?php echo ($delivery['checked']) ? 'class = "active"' : 'class = "noneactive"' ?>>
                                                <label data-delivery-date="<?php echo $delivery['date']; ?>"
                                                       data-delivery-intervals='<?php echo $delivery["interval"]; ?>'
                                                       data-delivery-address='<?php echo $delivery["address_parts"]; ?>'
                                                       data-delivery-use-storage='<?php echo $delivery["show_storages"]; ?>'>
                                                    <input type="radio"
                                                           name="delivery" <?php if ($delivery['checked']) echo 'checked' ?>
                                                           value="<?php echo $delivery['id'] ?>">
                                                    <span class="deliveryName"><?php echo $delivery['description'] ?></span>
                                                    <?php
                                                    if ($delivery['cost'] != 0 || DELIVERY_ZERO == 1) {
                                                        $deliveryCostShow = true;
                                                    } else {
                                                        $deliveryCostShow = false;
                                                    }
                                                    ?>
                                                    <span class="deliveryPrice"
                                                          style="<?php echo $deliveryCostShow ? '' : 'display:none;'; ?>">
                                                        <?php echo MG::numberFormat($delivery['cost']); ?>
                                                    </span>

                                                    <span class="deliveryCurrency"
                                                          style="<?php echo $deliveryCostShow ? '' : 'display:none;'; ?>">
                                                        <?php echo '&nbsp;' . $data['currency']; ?>
                                                    </span>
                                                    <span class = "date_settings" style="display:none">
                                                        <?php echo $delivery['date_settings'] ?>
                                                    </span>
                                                </label>
                                                <span class="deliveryInfo">
                                                    <?php echo $delivery['description_public']; ?>
                                                </span>

                                                <!--
                                                Для способов доставки с автоматическим расчетом стоимости, добавленных из плагинов.
                                                Проверяем наличие шорткода у способа доставки и выводим его в специальный блок при наличии
                                                -->
                                                <?php if (!empty($delivery['plugin'])): ?>
                                                    <?php echo '[' . $delivery['plugin'] . ']'; ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <!--  Выбор даты доставки -->
                                <div class="delivery-date" style="display:none;">
                                    <div class="c-order__title--small">
                                        <?php echo lang('orderDeliveryDate'); ?>:
                                    </div>
                                    <input type="text"
                                           aria-label="<?php echo lang('orderDeliveryDate'); ?>"
                                           name="date_delivery"
                                           placeholder="<?php echo lang('orderDeliveryDate'); ?>"
                                           value="<?php echo $_POST['date_delivery'] ?>">
                                </div>

                                <!-- Выбор времени доставки -->
                                <div class="delivery-interval" style="display:none; margin-bottom: 20px;">
                                    <div class="c-order__title--small">
                                        <?php echo lang('orderDeliveryInterval'); ?>:
                                    </div>
                                    <select name="delivery_interval"
                                            aria-label="<?php echo lang('orderDeliveryInterval'); ?>"></select>
                                </div>

                                <!-- Выбор склада -->
                                <?php MG::checkProductOnStorage(); ?>

                            </div>
                        </div>
                        
                        <div class="l-col min-0--12 min-990--6">
                            <div class="order-podlojka">
                                <div class="c-order__title">
                                    2.&nbsp;<?php echo lang('orderContactData'); ?>
                                </div>
                                <?php
                                if (in_array(EDITION, array('gipermarket', 'saas'))) {
                                    // Форма заказа, настраиваемая в панели управления Moguta.CMS «Гипермаркет» в разделе Настройки/Форма заказа
                                    component('order/form/custom');
                                }
                                else {
                                    // статичная форма заказа для остальных редакций
                                    component('order/form/static');
                                } ?>
                            </div>
                        </div>

                        <!-- Выбор способа оплаты -->
                        <div class="l-col min-0--12 min-768--6 min-990--3">
                            <div class="order-podlojka">
                                <div class="c-order__title">
                                    3.&nbsp;<?php echo lang('orderPaymentMethod'); ?>
                                </div>
                                <ul class="c-order__payment payment-details-list">
                                    <?php 
                                    $deliveryCount = 0;
                                    if ($data['delivery']) {
                                        $deliveryCount = count($data['delivery']);
                                    }
                                ?>
                                <?php if ($deliveryCount > 1 && !$_POST['payment']) : ?>
                                        <li>
                                            <div class="c-alert c-alert--blue">
                                                <?php echo lang('orderPaymentNoDeliv'); ?>
                                            </div>
                                        </li>
                                    <?php elseif ('' != $data['paymentArray']): ?>
                                        <?php echo $data['paymentArray'] ?>
                                    <?php else:
                                        ?>
                                        <li>
                                            <div class="c-alert c-alert--orange">
                                                <?php echo lang('orderPaymentNone'); ?>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Полная цена, соглашения на обработку и кнопка оформить -->
                        <div class="l-col min-0--12">
                            <div class="total-price-block total">
                                <div class="c-order__title c-order__title--last">
                                    4.&nbsp;<?php echo lang('orderPaymentTotal'); ?></div>
                                <div class="c-order__total">
                                    <div class="c-order__total--row">
                                        <div class="c-order__total--amount summ-info">
                                            <span class="order-summ total-sum"><span><?php echo $data['summOrder'] ?></span></span>
                                            <span class="delivery-summ"><?php echo $data['deliveryInfo'] ?></span>
                                        </div>
                                    </div>
                                    <?php if(class_exists('bonusCard')) { ?> 
                                        <?php if(bonusCard::getCountBonus() != '0 бонусов'): ?>
                                            [pay-bonus order=true]
                                        <?php endif; ?>
                                    <?php } ?>
                                    <?php if ($data['captcha'] && !$data['recaptcha']) { ?>
                                        <div class="checkCapcha" style="display:inline-block">
                                            <img src="captcha.html"
                                                 width="140"
                                                 height="36">
                                            <div class="capcha-text">
                                                <?php echo lang('captcha'); ?>
                                                <span class="red-star">*</span>
                                            </div>
                                            <input type="text"
                                                   aria-label="capcha"
                                                   name="capcha"
                                                   class="captcha">
                                        </div>
                                    <?php } ?>
                                    <?php if ($data['recaptcha']) { ?>
                                        <div class="checkCapcha" style="display:inline-block">
                                            <?php echo MG::printReCaptcha(); ?>
                                        </div>
                                    <?php } ?>
                                    <div class="c-order__total--row">
                                        <?php if (class_exists('PersonalData')): ?>
                                            <div class="PersonalData">
                                                [personal-data]
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    echo MG::addAgreementCheckbox(
                                        'checkout-btn',
                                        array(
                                            'text' => lang('agreementCheckboxText'),
                                            'textLink' => lang('agreementCheckboxLink')
                                        ),
                                        'addInlineScript'
                                    );
                                    ?>
                                    <div class="c-order__total--row">
                                        <form action="<?php echo SITE ?>/order"
                                              method="post"
                                              class="checkout-form">
                                            <input type="submit"
                                                   name="toOrder"
                                                   class="checkout-btn default-btn success"
                                                   value="<?php echo lang('checkout'); ?>"
                                                   disabled>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
