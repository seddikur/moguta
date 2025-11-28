<?php
$data['meta_title'] = lang('orderPayment');
mgSEO($data); ?>
<div class="l-row order-payment-page">
    <?php if ($data['msg']): ?>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--red errorSend">
                <?php echo $data['msg'] ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="l-col min-0--12">
        <div class="c-title">
            <?php echo lang('orderPayment'); ?>
        </div>
    </div>
    <?php if ($data['step'] !== 2) { ?>
        <div class="l-col min-0--12" style="margin-top:15px">
            <div class="c-alert c-alert--blue">
                <?php echo lang('orderPay1'); ?>

                <?php echo $data['orderNumber'] ?>

                <?php echo lang('orderPay2'); ?>

                <?php echo MG::numberFormat($data['summ']) ?>

                <?php echo $data['currency'] ?>
            </div>
        </div>
    <?php } ?>

    <?php if (!$data['pay'] && $data['payment'] == 'fail'): ?>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--red payment-form-block">
                <?php echo $data['message']; ?>
            </div>
        </div>
    <?php else: ?>

    <div class="payment-form-block">
        <?php if ($data['step'] === 2) { ?>
            <div class="l-col min-0--12">
                <div class="c-alert c-alert--green">
                    <?php echo lang('orderPaymentForm1'); ?>
                    <strong>№ <?php echo $data['orderNumber'] ?></strong> <?php echo lang('orderPaymentForm2'); ?>
                </div>
            </div>
            <?php if (($data['pay'] !== '4' && $data['pay'] !== '3') && $data['showPaymentForm'] == 1): ?>
                <div class="l-col min-0--12">
                    <div class="c-alert c-alert--blue">
                        <p><?php echo lang('orderPaymentForm4'); ?>
                            <b>№ <?php echo $data['orderNumber'] ?></b>
                            <?php echo lang('orderPaymentForm5'); ?>
                            <b><?php echo MG::numberFormat($data['summ']) ?></b>
                            <?php echo $data['currency']; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php } ?>
    </div>

    <div class="l-col min-0--12">
        <?php endif;
        if ($data['paymentViewFile'] && ($data['showPaymentForm'] == 1 || !isset( $data['showPaymentForm']))) {
            // Вставляем необходимый компонент страницы оплаты
            component(
                'payment',
                $data,
                'payment_' . $data['paymentViewFile']
            );
        } elseif ($data['pay'] == 12 || $data['pay'] == 13) { ?>
            <div class="c-alert c-alert--blue">
                <?php echo lang('orderPaymentView1'); ?>
                    <b><?php echo $data['paramArray'][0]['value'] ?></b>
                <?php echo lang('orderPaymentView2'); ?>
            </div>
        <?php } ?>
    </div>
</div>
