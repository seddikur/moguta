<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'mg-repeat-order';

  /**
   * функция дублирования заказа, проверяет наличие товаров, входящих в заказ 
   */
  public function repeatOrder() {
    $id = $_POST['id'];
    if ($id) {
      $model = new Models_Order();
      // если гипер, то достаем еще и склад по возможности
      if(method_exists('MG', 'enabledStorage')) {
        $storageToSelect = ', `storage`';
      } else {
        $storageToSelect = '';
      }
      // учет остатков товаров в заказе
      $res = DB::query('SELECT `payment_id`, `order_content`'.$storageToSelect.' FROM `'.PREFIX.'order` WHERE `id`= '.DB::quote($id));
      if ($row = DB::fetchArray($res)) {
        $content = unserialize(stripslashes($row['order_content']));
        $storage = $row['storage'];
      }
      mg::loger($storage);
      $error = false;
      $errorMsg = '';
      foreach ($content as $item) {
        if ($model->notSetGoods($item['id']) == false) {
          $error = true;
          $errorMsg .= '<a href="'.SITE.'/'.$item['url'].'">'.$item['title'].'</a> - '.$this->lang['SET_GOODS'].'<br>';
          continue;
        }        
        $res = DB::query('SELECT p.`count`, pv.`count` AS `var_count`, p.`activity`,  pv.`code` 
           FROM `'.PREFIX.'product` p LEFT JOIN 
           `'.PREFIX.'product_variant` pv ON p.id = pv.product_id WHERE p.id='.DB::quote($item['id']));
        if ($row = DB::fetchArray($res)) {
          $count = $row['count'];
          if (!empty($row['code']) && $row['code'] == $item['code']) {
            $count = $row['var_count'];
          } elseif (empty($row['code'])) {
            $count = $row['count'];
          }
          if(method_exists('MG', 'enabledStorage') && MG::enabledStorage()) {
            if(empty($storage)) {
              $storage = 'all';
            }
            mg::loger($count.'/'.$item['id'].'/'.$item['variant_id'].'/'.$storage);
            $count = MG::getProductCountOnStorage($count, $item['id'], $item['variant_id'], $storage);
          }
          mg::loger($count);
          if($row['activity']==0) {
            $errorMsg .= '<span>'.$item['name'].'</span> - '.$this->lang['INACTIVE'].'<br>';
            $error = true;
            continue;
          }
          if ((int)$count == 0) {
            $errorMsg .= '<span>'.$item['name'].'</span> - '.$this->lang['NOT_AVAILABLE'].'<br>';
            $error = true;
            continue;
          }
          if ((int)$count>0&&(int)$count < (int)$item['count']) {
            $errorMsg .= '<a href="'.SITE.'/'.$item['url'].'">'.$item['name'].'</a> - '.$this->lang['THE_REST'].' '.$count.' '.$this->lang['UNIT'].'<br>';
            $error = true;
          }
        }
      }
      mg::loger($errorMsg);
      if ($error) {
        $this->data['error'] = $errorMsg;
        return false;
      }

      if($model->cloneOrder($id)) {    
        //TODO костыль, так как $id нового заказа не возвращается движком и не содержится в объекте $model  
        $res = DB::query('SELECT `id`, `number` FROM `'.PREFIX.'order` WHERE `user_email` = '.DB::quote(USER::getThis()->login_email).' ORDER BY `id` DESC LIMIT 1');
        $row = DB::fetchArray($res);
        $newOrderId = $row['id'];
        $newOrderNumber = $row['number'];
        
        //Достаем настройки заказов, чтобы установить статус для нового заказа.
        $propertyOrder = MG::getOption('propertyOrder');
        $propertyOrder = stripslashes($propertyOrder);
        $propertyOrder = unserialize($propertyOrder);
        //Если установлен статус для новых заказов по умолчанию "ожидает оплаты", 
        //для способов оплаты "наличными" или "наложенным" меняем на "в доставке"
        $order_status_id = 0;
        if (isset($row['payment_id']) && isset($propertyOrder['order_status'])) {
          if (in_array($row['payment_id'], array(3, 4)) && $propertyOrder['order_status'] == 1) {
            $order_status_id = 3;
          } else {
            $order_status_id = $propertyOrder['order_status'];
          }
        }
        DB::query('UPDATE `'.PREFIX.'order` SET `status_id`='.DB::quote($order_status_id) 
          . 'WHERE `id`='.DB::quote($newOrderId).' ORDER BY `id` DESC LIMIT 1');

        $this->repeatEmailOrder($newOrderId, $newOrderNumber);
        $this->data['success'] = $this->lang['SUCCESS'];
        return true;
      }      
    }
    return false;
  }

  private function repeatEmailOrder($id, $orderNumber) {
		$model = new Models_Order;
		$order = $model->getOrder('id = '.$id);

		$delivery = Models_Order::getDeliveryMethod(false, $order[$id]["delivery_id"]);

		$paymentArray = $model->getPaymentMethod($order[$id]["payment_id"], false);

		$hash = $order[$id]["confirmation"];
		$link = 'ссылке <a href="'.SITE.'/order?sec='.$hash.'&id='.$id.'" target="blank">'.SITE.'/order?sec='.$hash.'&id='.$id.'</a>';

		$OFM = array();
    if(defined("EDITION") && !in_array(EDITION,['market','minimarket','vitrina'])){
      $opFieldsM = new Models_OpFieldsOrder($id);
      $OFM = $opFieldsM->getHumanView('all', true);
      $OFM = $opFieldsM->fixFieldsForMail($OFM);
    }

		$productPositions = unserialize(stripslashes($order[$id]['order_content']));

		$orderWeight = 0;

		foreach ($productPositions as &$item) {
			$orderWeight += $item['count']*$item['weight'];
			$item['discountVal'] = round($item['fulPrice'], 2)-round($item['price'], 2);
			$item['discountPercent'] = round((1-round($item['price'], 2)/round($item['fulPrice'], 2))*100, 2);
			foreach ($item as &$v) {
			$v = rawurldecode($v);
			}
		}

		$phones = explode(', ', MG::getSetting('shopPhone'));
		$subj = 'Оформлен заказ №'.($order[$id]['number'] != "" ? $order[$id]['number'] : $order[$id]['id']).' на сайте '.MG::getSetting('sitename');

		$paramToMail = array(
		  'id' => $order[$id]['id'],
		  'orderNumber' => $order[$id]['number'],
		  'siteName' => MG::getSetting('sitename'),
		  'delivery' => $delivery['description'],
		  'delivery_interval' => isset($order[$id]['delivery_interval'])?$order[$id]['delivery_interval']:null,
		  'currency' => MG::getSetting('currency'),
		  'fio' => $order[$id]['name_buyer'],
		  'email' => $order[$id]['contact_email'],
		  'phone' => $order[$id]['phone'],
		  'address' => $order[$id]['address'],
		  'payment' => $paymentArray['name'],
		  'deliveryId' => $order[$id]["delivery_id"],
		  'paymentId' => $order[$id]["payment_id"],
		  'result' => $order[$id]["summ"],
		  'deliveryCost' => $order[$id]["delivery_cost"],
		  'date_delivery' => $order[$id]["date_delivery"],
		  'total' => $order[$id]["delivery_cost"] + $order[$id]["summ"],
		  'confirmLink' => $link,
		  'ip' => $order[$id]["ip"],
		  'lastvisit' => '',
		  'firstvisit' => '',
		  'supportEmail' => MG::getSetting('noReplyEmail'),
		  'shopName' => MG::getSetting('shopName'),
		  'shopPhone' => $phones[0],
		  'formatedDate' => date('Y-m-d H:i:s'),
		  'productPositions' => $productPositions,
		  'couponCode' => '',
		  'toKnowStatus' => '',
		  'userComment' => $order[$id]["user_comment"],
		  'yur_info' => unserialize(stripcslashes($order[$id]["yur_info"])),
		  'custom_fields' => $OFM,
		  'orderWeight' => $orderWeight,
		);

		if (!empty($order[$id]["address_parts"])) {
			$tmp = array_filter(unserialize(stripcslashes($order[$id]["address_parts"])));
			foreach ($tmp as $ke => $va) {
				$tmp[$ke] = htmlspecialchars_decode($va);
			}
			$paramToMail['address'] = implode(', ', $tmp);
		}

		if (!empty($order[$id]["name_parts"])) {
			$tmp = array_filter(unserialize(stripcslashes($order[$id]["name_parts"])));
			foreach ($tmp as $ke => $va) {
				$tmp[$ke] = htmlspecialchars_decode($va);
			}
			$paramToMail['fio'] = trim(implode(' ', $tmp));
		}

		$emailToUser = MG::layoutManager('email_order', $paramToMail);

		// Отправка заявки пользователю.
		Mailer::sendMimeMail(array(
			'nameFrom' => MG::getSetting('shopName'),
			'emailFrom' => MG::getSetting('noReplyEmail'),
			'nameTo' => $paramToMail['fio'],
			'emailTo' => $order[$id]["contact_email"],
			'subject' => $subj,
			'body' => $emailToUser,
			'html' => true
		));

    $paramToMail['adminMail'] = true;
    $emailToAdmin = MG::layoutManager('email_order_admin', $paramToMail);
    // Отправка заявки админу.
    $adminSubject = 'Оформлен заказ №'.$orderNumber.'. ';
    if ($paramToMail['fio'] || $paramToMail['email']) {
        $adminSubject .= ($paramToMail['fio'] ? $paramToMail['fio'].' ' : '').
            ($paramToMail['email'] ? '('.$paramToMail['email'].')' : '').'. ';
    }
    $adminSubject .= 'На сайте '.$paramToMail['siteName'];

    $mails = explode(',', MG::getSetting('adminEmail'));
    foreach ($mails as $mail) {
      if (preg_match('/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]+\.)+[a-zA-Z]{2,20}$/', $mail)) {
        Mailer::sendMimeMail(array(
          'nameFrom' => MG::getSetting('shopName'),
          'emailFrom' => MG::getSetting('noReplyEmail'),
          'nameTo' => $paramToMail['siteName'],
          'emailTo' => $mail,
          'subject' => $adminSubject,
          'body' => $emailToAdmin,
          'html' => true
        ));
      }
    }
		$this->messageSucces = $this->lang['RESENDING_EMAIL_ORDER_NOTIFY'];
		return true;
	}

  /*
   * добавляет товары из заказа в корзину
   */
  public function addOrderToCart(){
    $id = $_POST['id'];
    $inform = !empty($_POST['inform']) ? false : true;
    $notEmpty = false; 
    if ($id) {
      unset($_SESSION['cart']);
      $res = DB::query('SELECT `order_content` FROM `'.PREFIX.'order` WHERE `id`= '.DB::quote($id));
      if ($row = DB::fetchArray($res)) {
        $content = unserialize(stripslashes($row['order_content']));
      }
      $cart = new Models_Cart;
      $modelProduct = new Models_Product;
      $copyNotAll = false;
      foreach ($content as $item) { 
        $error = false;
        $res = DB::query('SELECT p.`count`, pv.`count` AS `var_count`, p.`activity`,  pv.`code` 
           FROM `'.PREFIX.'product` p LEFT JOIN 
           `'.PREFIX.'product_variant` pv ON p.id = pv.product_id WHERE p.id='.DB::quote($item['id']));
        if ($row = DB::fetchArray($res)) {
          $count = $row['count'];
          if (!empty($row['code']) && $row['code'] == $item['code']) {
            $count = $row['var_count'];
          } elseif (empty($row['code'])) {
            $count = $row['count'];
          }
          if(method_exists('MG', 'enabledStorage') && MG::enabledStorage()) {
            $count = MG::getProductCountOnStorage($count, $item['id'], $item['variant_id'], 'all');
          }
          if($row['activity']==0) {
            $errorMsg .= '<span>'.$item['name'].'</span> - '.$this->lang['INACTIVE'].'<br>';
            $error = true;            
          }
          if ((int)$count == 0) {
            $errorMsg .= '<span>'.$item['name'].'</span> - '.$this->lang['NOT_AVAILABLE'].'<br>';
            $error = true;
          }
          if ((int)$count>0&&(int)$count < (int)$item['count']) {
            $errorMsg .= '<a href="'.SITE.'/'.$item['url'].'">'.$item['name'].'</a> - '.$this->lang['THE_REST'].' '.$count.' '.$this->lang['UNIT'].'<br>';
            $item['count'] = $count;
            $copyNotAll = true;
          }
        }
       if (!$error) {
          $property =  htmlspecialchars_decode(htmlspecialchars_decode($item['property']));
          $parseRow = explode('<div class="prop-position"> <span class="prop-name">', $property);
          foreach ($parseRow as &$propertyInfo) {
            if ($propertyInfo) {
              preg_match('~.*:\s(.*?)</span>.*?~', $propertyInfo, $res);//замена на новую хар-ку	
              $propertyName = $res[1];
              if (stristr($propertyInfo, '> +')===FALSE){                
                $propertyInfo = str_replace('class="prop-val">', 'class="prop-val"> '.$propertyName.'#0#', $propertyInfo);
              } else {
                $propertyInfo = str_replace('> + ', '> '.$propertyName.'#', $propertyInfo);
                $propertyInfo = str_replace(' '.MG::getSetting('currency'), '#', $propertyInfo);
              }
            }
          }
          $propertyReal = implode('<div class="prop-position"> <span class="prop-name">', $parseRow);
          $propertArray = array('property'=>$property, 'propertyReal'=> $propertyReal);
          $cart->addToCart(intval($item['id']), $item['count'], $propertArray, intval($item['variant_id']));
          $notEmpty = true;
        } else {
          $copyNotAll = true;
        }
        
      }
      if ($copyNotAll&&$inform) {
        $this->data['result'] = $this->lang['NOT_COPY_ALL'];
        $this->data['error'] = $errorMsg;
        if (!$notEmpty) {
          $this->data['result'] = $this->lang['EMPTY_CART'];
          $this->data['empty'] = true;
        }
        return false;
      } else {
        return true;
      }
      
    }
    return false;
  }

}
