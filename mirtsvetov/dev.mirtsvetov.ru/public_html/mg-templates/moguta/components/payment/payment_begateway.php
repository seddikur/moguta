<?php

include_once CORE_LIB.'BegatewayPayment.php';

$api = new BegatewayPayment(
    $data['paramArray'][0]['value'], // Shop ID
    $data['paramArray'][1]['value'], // Shop Secret Key
    $data['paramArray'][2]['value'], // Shop Public Key
    $data['paramArray'][3]['value'], // Payment Domain
    $data['paramArray'][4]['value'], // Test Mode
);

$response = $api->getPayLink($data);

if (!isset($response->checkout->redirect_url)) {
    if (isset($response->errors)) {
        $errorCode = 500;
        $errorMessage = 'Error Response: ' . $response->message;
    } elseif (isset($response->error)) {
        $errorCode = 500;
        $errorMessage = 'Error Response: ' . $response->error;
    } elseif (!isset($response->checkout->token)) {
        $errorCode = 500;
        $errorMessage = 'Error Response: ' . 'Token not be Null';
    } else {
        $errorMessage = 'Error: Something went wrong';
    }
    ?>
    <div class="c-alert c-alert--red payment-form-block">
        <?= $errorMessage; ?>
    </div>
<?php } else { ?>
    <div class="payment-form-block">

        <form action="<?= $response->checkout->redirect_url ?>" method="POST">
            <input name="gopay" type="submit" class="c-button" value="<?= lang('paymentPay'); ?>"/>
        </form>

        <p>
            <em>
                <?= lang('paymentDiff1'); ?>"<a href="<?= SITE ?>/personal"><?= lang('paymentDiff2'); ?></a>".
            </em>
        </p>
    </div>
<?php } ?>