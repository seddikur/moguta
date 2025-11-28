<?php
if (isset($data['paramArray'][4]['value']) && $data['paramArray'][4]['value'] == 'true') {
   $receipt = [];
   $items = [];

   $order = $data['orderInfo'][$data['id']];
   $products = unserialize(stripslashes($order['order_content']));

   $tax = 'none';
   if (isset($data['paramArray'][5]['value'])) {
      switch ($data['paramArray'][5]['value']) {
         case 'без НДС':
            $tax = 'none';
            break;

         case '0%':
            $tax = 'vat0';
            break;

         case '10%':
            $tax = 'vat10';
            break;

         case '20%':
            $tax = 'vat20';
            break;
      }
   }


   foreach ($products as $product) {
      $payment_method = 'full_prepayment'; //todo
      $payment_object = 'commodity'; //todo

      $items[] = [
         'name' => mb_substr(trim(htmlspecialchars($product['name'])), 0, 63, 'utf-8'),
         //'name'     => htmlspecialchars($product['name']),
         'sum' => $product['price'] * $product['count'],
         'quantity' => $product['count'],
         'payment_method' => $payment_method,
         'payment_object' => $payment_object,
         'tax' => $tax
      ];
   }

   if (isset($order['delivery_cost'])) {
      $delivery_name = $order['description'];
      $delivery_price = $order['delivery_cost'];
      $payment_method = 'full_prepayment'; //todo
      $payment_object = 'service'; //todo

      if ($delivery_price > 0) {

         $items[] = [
            'name' => mb_substr(trim(htmlspecialchars($delivery_name)), 0, 63, 'utf-8'),
            'sum' => $delivery_price,
            'quantity' => 1,
            'tax' => $tax,
            'payment_method' => $payment_method,
            'payment_object' => $payment_object,
         ];

      }
   }

   $receipt = json_encode(array(
      'sno' => $tax_type,
      'items' => $items

   ));

   $receipt = urlencode($receipt);

   $alg = $data['paramArray'][3]['value'];
   $crc = $data['paramArray'][0]['value'] . ":" . $data['summ'] . ":" . $data['id'] . ":" . $receipt . ":" . $data['paramArray'][1]['value'];
   $crc = hash($alg,$crc);
}
?>
<div class="payment-form-block">

   <form action='https://auth.robokassa.ru/Merchant/Index.aspx' method=POST>
   <input type=hidden name=MrchLogin value=<?php echo $data['paramArray'][0]['value'] ?>>
   <input type=hidden name=OutSum value=<?php echo $data['summ'] ?>>
   <input type=hidden name=InvId value=<?php echo $data['id'] ?>>
   <input type=hidden name=Desc value='Оплата заказа <?php echo $data['orderNumber'] ?>'>
   <?php if (isset($data['paramArray'][4]['value']) && $data['paramArray'][4]['value'] == 'true'): ?>
   <input type="hidden" name="Receipt" value="<?php echo $receipt ?>" />
   <?php endif; ?>
   <?php if (isset($data['paramArray'][4]['value']) && $data['paramArray'][4]['value'] == 'true'): ?>
   <!-- with receipt -->
   <input type=hidden name=SignatureValue value=<?php echo $crc ?>>
   <?php else: ?>
   <input type=hidden name=SignatureValue value=<?php echo $data['paramArray']['sign'] ?>>
   <?php endif; ?>
   <input type=hidden name=IncCurrLabel value="">
   <input type=hidden name=Culture value="ru">
   <input type=submit value='<?php echo lang('paymentPay'); ?>' style="padding: 10px 20px;">

</form>
<p>
 <em>
 <?php echo lang('paymentDiff1'); ?>"<a href="<?php echo SITE?>/personal"><?php echo lang('paymentDiff2'); ?></a>".
 <br/>
 <?php echo lang('paymentRobo1'); ?><b><span style="color:#0077C0" >Robokassa</span></b>,<b><?php echo $data['paramArray'][0]['value']?></b><?php echo lang('paymentRobo2'); ?>
 </em>
 </p>
</div>