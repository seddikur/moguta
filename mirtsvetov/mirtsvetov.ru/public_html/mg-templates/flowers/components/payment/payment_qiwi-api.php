<style>
  #qiwiSendForm {
    background: rgb(255, 140, 0);
    color: white;
    border-radius: 24px;
    font-weight: 500;
    padding: 15px 50px;
  }

  #qiwiSendForm:hover {
    background: rgb(255, 130, 0);
  }
</style>
<?php
$orderID = array_keys($data['orderInfo'])[0];
$orderInfo = $data['orderInfo'][$orderID];

// Делаем уникальный идентификатор платежа
$orderID = $orderID.'-'.time();

//Получаем настройки оплаты
$order = new Models_Order();
$paymentInfo = $order->getParamArray(28, null, null);
$publicKey = $paymentInfo[0]['value'];
$secretKey = $paymentInfo[1]['value'];

//Удаляем предыдущий выставленный счет, если он не был оплачен
$auths = 'Bearer '. $secretKey;
$urls = 'https://api.qiwi.com/partner/bill/v1/bills/'.($_SESSION['qiwiApi']['orderID']).'/reject';
$curls = curl_init($urls);
curl_setopt($curls, CURLOPT_POST, true);
curl_setopt($curls, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curls, CURLOPT_HTTPHEADER, array("Accept: application/json", "Content-Type: application/json", "Authorization: $auths"));
curl_exec($curls);
curl_close($curls);

if (!empty($orderInfo['delivery_shop_curr'])) {
  $value = $orderInfo['summ_shop_curr'] + $orderInfo['delivery_shop_curr'];
} else {
  $value = $orderInfo['summ_shop_curr'];
}
if ($orderInfo['currency_iso'] == 'RUR') {
  $currency = 'RUB';
}
$amount = array('currency' => $currency, 'value' => $value);

if (!empty($orderInfo['phone'])) {
  $phone = str_replace([' ', '(', ')', '-'], '', $orderInfo['phone']);
  $phone = str_replace('+7', '8', $phone);
} else {
  $phone = '';
}
$email = (!empty($orderInfo['user_email'])) ? $orderInfo['user_email'] : '';
$successURL = SITE.'/payment?id=28&pay=result';
$_SESSION['qiwiApi']['orderID'] = $orderID;

?>
<form method="get" action="https://oplata.qiwi.com/create" accept-charset="UTF-8">
  <input type="hidden" name="publicKey" value="<?php echo $publicKey;?>" />
  <input type="hidden" name="billId" value="<?php echo $orderID;?>" />
  <input type="hidden" name="amount" value="<?php echo $value;?>" />
  <input type="hidden" name="phone" value="<?php echo $phone;?>" />
  <input type="hidden" name="email" value="<?php echo $email;?>" />
  <input type="hidden" name="successUrl" value="<?php echo $successURL;?>" />
  <input type="submit" value="Оплатить" id="qiwiSendForm">
</form>
