<?php
$data['meta_title'] = lang('orderPayment');
mgSEO($data); ?>

<?php if ($data['msg']): ?>
    <div class="alert"><?php echo $data['msg'] ?></div>
<?php endif; ?>

<h1>Заказ оформлен</h1>
<?php if ($data['step'] !== 2) { ?>
    <div class="alert">
        <?php echo lang('orderPay1'); ?>

        <?php echo $data['orderNumber'] ?>

        <?php echo lang('orderPay2'); ?>

        <?php echo MG::numberFormat($data['summ']) ?>

        <?php echo $data['currency'] ?>
    </div>
<?php } ?>

<?php if (!$data['pay'] && $data['payment'] == 'fail'): ?>
    <div class="alert">
        <?php echo $data['message']; ?>
    </div>
<?php else: ?>

<div class="payment-form-block">
    <?php if ($data['step'] === 2) { ?>
        <div class="alert success">
            <?php echo lang('orderPaymentForm1'); ?>
            <strong>№ <?php echo $data['orderNumber'] ?></strong> <?php echo lang('orderPaymentForm2'); ?>
        </div>
        <?php if (($data['pay'] !== '4' && $data['pay'] !== '3') && $data['showPaymentForm'] == 1): ?>
            <div class="alert">
                <p><?php echo lang('orderPaymentForm4'); ?>
                    <b>№ <?php echo $data['orderNumber'] ?></b>
                    <?php echo lang('orderPaymentForm5'); ?>
                    <b><?php echo MG::numberFormat($data['summ']) ?></b>
                    <?php echo $data['currency']; ?>
                </p>
            </div>
        <?php endif; ?>
    <?php } ?>
</div>

<?php endif;
if ($data['paymentViewFile'] && ($data['showPaymentForm'] == 1 || !isset( $data['showPaymentForm']))) {
    // Вставляем необходимый компонент страницы оплаты
    component(
        'payment',
        $data,
        'payment_' . $data['paymentViewFile']
    );
} elseif ($data['pay'] == 12 || $data['pay'] == 13) { ?>
    <div class="alert">
        <?php echo lang('orderPaymentView1'); ?>
            <b><?php echo $data['paramArray'][0]['value'] ?></b>
        <?php echo lang('orderPaymentView2'); ?>
    </div>
<?php } ?>