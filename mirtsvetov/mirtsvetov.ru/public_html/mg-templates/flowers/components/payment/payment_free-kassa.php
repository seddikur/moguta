<?php

$merchant_id = $data['paramArray'][1]['value'];
$secret_word = $data['paramArray'][2]['value'];
$lang = $data['paramArray'][0]['value'];
$currency = 'RUB';
if (!empty($data['paramArray'][4]['value'])) {
   $currency = $data['paramArray'][4]['value'];
}

$id = $data['id'];
$summ = $data['summ'];

$sign = md5(implode(':', [
   $merchant_id,
   $summ,
   $secret_word,
   $currency,
   $id,
]));

$url = "https://pay.freekassa.ru/?m=$merchant_id&o=$id&oa=$summ&s=$sign&currency=$currency&us_pay_id=26";

?>

<div class="payment-form-block">
<a class="default-btn" href="<?php echo $url; ?>"><?php echo lang('paymentPay'); ?></a>
</div>