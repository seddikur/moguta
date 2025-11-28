<?php
if ($data['paramArray'][2]['value'] == 'true') {

    switch ($data['paramArray'][3]['value']) {
        case 'без НДС':
            $tax = 1;
            break;
        case '0%':
            $tax = 2;
            break;
        case '10%':
            $tax = 3;
            break;

        case '20%':
            $tax = 4;
            break;

        default:
            $tax = 4;
            break;
    }

    $sno = intval($data['paramArray'][4]['value']);
    if (!$sno) {
        $sno = 1;
    }


    $customerContact = array();
    if (isset($data['orderInfo'][$data['id']]['user_email'])) {
        $customerContact['email'] = $data['orderInfo'][$data['id']]['user_email'];
    }
    else if (isset($data['orderInfo'][$data['id']]['phone'])) {
        $phone = $data['orderInfo'][$data['id']]['phone'];
        $customerContact['phone'] = preg_replace("/[^0-9+]/", '', $phone);
    }

    $content = unserialize(stripslashes($data['orderInfo'][$data['id']]['order_content']));

    $ym_merchant_receipt = array(
        "customer" => $customerContact,
        "items" => array(),
        "tax_system_code" => $sno,
    );

    foreach ($content as $key => $value) {

        $tmp = explode(PHP_EOL, $content[$key]['name']);

        $item = array(
            "description" => strip_tags(htmlspecialchars_decode(MG::textMore($tmp[0], 125))),
            "quantity" => (float)round($content[$key]['count'], 3),
            "amount" => array(
                "value" => (float)round($content[$key]['price'], 2),
                "currency" => "RUB"
            ),
            "vat_code" => $tax,
            "payment_mode" => "full_prepayment",
            "payment_subject" => "commodity"
        );

        $ym_merchant_receipt["items"][] = $item;
        unset($item);
        unset($tmp);
    }
    if ($data['orderInfo'][$data['id']]['delivery_cost'] > 0) {

        $item = array(
            "description" => 'Доставка',
            "amount" => array(
                "value" => (float)round($data['orderInfo'][$data['id']]['delivery_cost'], 2),
                "currency" => "RUB"
            ),
            "quantity" => 1,
            "vat_code" => $tax,
            "payment_mode" => "full_prepayment",
            "payment_subject" => "service"
        );
        $ym_merchant_receipt["items"][] = $item;
        unset($item);
    }

    $ym_merchant_receipt = ',
    "receipt" : '.json_encode($ym_merchant_receipt);

} else {
    $ym_merchant_receipt = '';
}


$orderStatusId = $data['orderInfo'][$data['id']]['status_id']; 
//получаем id сессии,сохранияем время платежа и передаем в api яндекса
$sessionId = session_id();
$_SESSION['pay_time'] = date("Y-m-d H:i:s"); 
$description = 'Заказ '.$data['orderNumber'];  
$postSend = '{
    "amount": {
        "value": "'.$data['summ'].'",
        "currency": "RUB"
    }, 
    "capture": true,
    "confirmation": {
        "type": "redirect",
        "return_url": "'.SITE.'"
    },
    "description":"'.$description.'",
    "metadata": {
        "orderId": "'.$data['id'].'",
        "sessionId":"'.$sessionId.'",
        "payTime":"'.$_SESSION['pay_time'].'"    
    }'.$ym_merchant_receipt.'
}';

$username = $data['paramArray'][0]['value'];
$password = $data['paramArray'][1]['value'];
if($orderStatusId != 2){
    $ch = curl_init('https://api.yookassa.ru/v3/payments');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Idempotence-Key: '.md5(time())
    ));
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postSend);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $res = json_decode($res, true);
    // 
    $toLog = "\n".'--------------------------------------'.
    "\n".'Idempotence-Key: '.md5(time())."\n\n\n";
    $toLog .= 'Auth:'."\n\n";
    $toLog .= $username . ":" . $password."\n\n\n";
    $toLog .= 'Post data:'."\n\n";
    $toLog .= $postSend;

   // mg::loger($toLog, 'append', 'kassa-api-send');

   if ($res['confirmation']['confirmation_url']) {
    header('location: '.$res['confirmation']['confirmation_url']);
   }

    if($res['type'] == 'error'){
      echo "При обмене с сервером Ю.Касса возникла ошибка!<br/> <b>".$res['description']."</b>";
      if($res['description']=="Authentication by given credentials failed"){
        echo "<br/>Необходимо указать корректные авторизационные данные в настройках модуля оплаты Ю.Касса!";
      }
    }else{
        viewdata($res);
    }
}else{
    header('location: '.SITE.'/personal');
}
?>

