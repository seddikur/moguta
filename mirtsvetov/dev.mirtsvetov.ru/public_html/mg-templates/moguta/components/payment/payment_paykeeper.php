<?php 
$orderConten = unserialize(stripslashes($data['orderInfo'][$data['id']]['order_content']));
$clientEmail = $data['orderInfo'][$data['id']]['contact_email'];
$cart = array();
foreach($orderConten as $item){
	$cart[] = [
		'name' => $item['name'],
		'price' => $item['price'],
      	'quantity' => $item['count'],
		'sum' => strval($item['price']*$item['count']),
		'tax' => !empty($data['paramArray'][3]['value'])?$data['paramArray'][3]['value']:'none'
	];
}

if($data['orderInfo'][$data['id']]['delivery_cost'] > 0){
  $cart[] = [
    'name' =>'Доставка товара',
    'price' => round($data['orderInfo'][$data['id']]['delivery_cost'], 2),
    'quantity' => 1,
    'sum' => round($data['orderInfo'][$data['id']]['delivery_cost'], 2),
    'tax' => !empty($data['paramArray'][3]['value'])?$data['paramArray'][3]['value']:'none'
  ];
}

?>
<div class="payment-form-block-n">

   <form action="<?php echo $data['paramArray'][1]['value'] ?>" method="POST" type="application/x-www-form-urlencoded" accept-charset="utf-8">
	<input type="hidden" name="sum" value="<?php echo $data['summ'] ?>"/>
	<input type="hidden" name="orderid" value="<?php echo $data['orderNumber'] ?>"/>
	<input type="hidden" name="clientid" value="<?php echo $data['id'] ?>"/>
	<input type="hidden" name="phone" value="<?php echo $data['phone'] ?>"/>
	<input type="hidden" name="client_email" value="<?php echo $clientEmail ?>"/>
	<input type="hidden" name="cart" value='<?php echo json_encode($cart)?>'/>
	<input name="gopay" type="submit" class="c-button" value="Перейти на страницу оплаты"/>
	</form>
	
<br/><br/><br/>
</div>