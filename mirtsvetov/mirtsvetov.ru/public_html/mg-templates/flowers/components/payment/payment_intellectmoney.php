<?php 
   //MG::loger($data);
   $recipientAmount = round($data['orderInfo'][$data['id']]['summ'] + $data['orderInfo'][$data['id']]['delivery_cost'], 2);
   $recipientCurrency = $data['orderInfo'][$data['id']]['currency_iso'];
   if($data['paramArray'][4]['value'] == 'true'){
    $recipientCurrency = 'TST';
   }
   $merchantReceipt = array();
   if($data['paramArray'][3]['value'] == 'true'){
        $orderContent = unserialize(stripslashes($data['orderInfo'][$data['id']]['order_content']));
        //MG::loger($orderContent);
        $positions = array();
        foreach($orderContent as $item){
            $positions[] = [
                'quantity' => $item['count'],
                'price' => round($item['price']*$item['count'], 2),
                'tax' => $data['paramArray'][5]['value'],
                'text' => $item['name'],
            ];
        }
        if($data['orderInfo'][$data['id']]['delivery_cost'] > 0){
            $positions[] = [
                'quantity' => 1,
                'price' => round($data['orderInfo'][$data['id']]['delivery_cost'], 2),
                'tax' => $data['paramArray'][6]['value'],
                'text' => 'Доставка товара',
            ];
        }
        $merchantReceipt = array(
            "inn" => $data['paramArray'][2]['value'],
            "group" => "Main",
            "content" => array(
                "type" => "1",
                "positions" => $positions,
                "customerContact" => $data['orderInfo'][$data['id']]['contact_email']
            )
        );

   }
//MG::loger($data['paramArray'][3]['value']);
?>
<div class="payment-form-block-n">

<form action="https://merchant.intellectmoney.ru/ru/" method="POST" type="application/x-www-form-urlencode" accept-charset="utf-8">
 <input type="hidden" name="eshopId" value="<?php echo $data['paramArray'][0]['value'] ?>"/>
 <input type="hidden" name="orderId" value="<?php echo $data['orderInfo'][$data['id']]['number'] ?>"/>
 <input type="hidden" name="recipientAmount" value="<?php echo $recipientAmount ?>"/>
 <input type="hidden" name="recipientCurrency" value="<?php echo $recipientCurrency ?>"/>
 <input type="hidden" name="UserField_1" value="<?php echo $data['id'] ?>"/>
 <?php if($data['paramArray'][3]['value'] == 'true'){ ?>
    <input type="hidden" name="merchantReceipt" value="<?php echo urlencode(json_encode($merchantReceipt))?>"/>
 <?php } ?> 
 <input name="gopay" type="submit" class="c-button" value="Перейти на страницу оплаты"/>
 </form>
 
<br/><br/><br/>
</div>