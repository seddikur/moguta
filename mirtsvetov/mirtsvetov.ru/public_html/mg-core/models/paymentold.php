<?php
/**
 * Класс Models_PaymentOld предназначен для приема и обработки платежей (Старый контроллер Payment).
 * @package moguta.cms
 * @subpackage Controller
 */
class Models_PaymentOld {
  private $msg = '';
  private $data = [];

  public function __construct() {
    $paymentID = $_GET['id'];
    $paymentStatus = $_GET['pay'];

    $_POST['url'] = URL::getUrl();
    $modelOrder = new Models_Order();
    if (!$paymentID) {
      $paymentID = $_REQUEST['id'];
    }    
    if (!$paymentStatus) {
      $paymentStatus = $_REQUEST['pay'];
    }
    if(isset($_GET['getQr'])){
      $this::getQr($_GET['getQr']);
    }  
     //MG::loger($_REQUEST);
    $msg = '';
    switch ($paymentID) {
      case 1: //webmoney
        $msg = $this->webmoney($paymentID, $paymentStatus);
        break;
      // case 2: //ЯндексДеньги    
      //   $msg = $this->yandex($paymentID, $paymentStatus);
      //   break;
      case 5: //robokassa
        $msg = $this->robokassa($paymentID, $paymentStatus);
        break;
      case 6: //qiwi
        $msg = $this->qiwi($paymentID, $paymentStatus);
        break;
      case 8: //interkassa
        $msg = $this->interkassa($paymentID, $paymentStatus);
        break;
      case 9: //PayAnyWay
        $msg = $this->payanyway($paymentID, $paymentStatus);
        break;
      case 10: //PayMaster
        $msg = $this->paymaster($paymentID, $paymentStatus);
        break;
      case 11: //alfabank
        $msg = $this->alfabank($paymentID, $paymentStatus);
        break;
      case 14: //Яндекс.Касса
        $msg = $this->yandexKassa($paymentID, $paymentStatus);
        break;
      case 15: //privat24
        $msg = $this->privat24($paymentID, $paymentStatus);
        break;
      case 16: //LiqPay
        $msg = $this->liqpay($paymentID, $paymentStatus);
        break;
      case 17: //Sberbank
        $msg = $this->sberbank($paymentID, $paymentStatus);
        break;
      case 18: //Tinkoff
        $msg = $this->tinkoff($paymentID, $paymentStatus);
        break;
      case 20: //ComePay
        $paymentStatus = $this->comepay($paymentID, $paymentStatus);
        $msg = $this->msg;
        break;
      case 21: //paykeeper
        $msg = $this->paykeeper($paymentID, $paymentStatus);
        break;
      case 22: //CloudPayments
        $msg = $this->cloudpayments($paymentID, $paymentStatus);
        break;
      case 24: //Новая Яндекс Касса
        $paymentStatus = $this->yandexKassaNew($paymentID);
        $msg = $this->msg;
        break;
      case 26: //Фри-касса
        $paymentStatus = $this->freeKassa($paymentID, $paymentStatus);
        $msg = $this->msg;
        break;
      case 27: //Мегакасса
        $msg = $this->megaKassa($paymentID, $paymentStatus);
        break;
       case 28: //Qiwi API
        $msg = $this->qiwiApi($paymentID, $paymentStatus);
        break;
      case 29: //intellectMoney
        $msg = $this->intellectMoney($paymentID, $paymentStatus);
        break;  
      case 30: //beGateway
        $msg = $this->beGateway($paymentID, $paymentStatus);

      case 19: //PayPal
        $paymentStatus = $this->paypal($paymentID, $paymentStatus);
        $msg = $this->msg;
        break;

    }

    $this->data = array(
      'payment' => $paymentID, //id способа оплаты
      'status' => $paymentStatus, //статус ответа платежной системы (result, success, fail)
      'message' => $msg, //статус ответа платежной системы (result, success, fail)
    );
  }

  /**
   * Проверка платежа через WebMoney.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function webmoney($paymentID, $paymentStatus) {
    $order = new Models_Order();

    if('success' == $paymentStatus) {
      if(empty($_POST['LMI_PAYMENT_NO'])) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          exit;
      }
    
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($_POST['LMI_PAYMENT_NO']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['LMI_PAYMENT_NO']]['number']; 
      $msg .= $this->msg;
    } elseif('result' == $paymentStatus && count($_POST) > 1) {      
      $paymentAmount = trim($_POST['LMI_PAYMENT_AMOUNT']);
      //$paymentAmount = $paymentAmount*1;
      $paymentOrderId = trim($_POST['LMI_PAYMENT_NO']);
      if(!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quoteFloat($paymentAmount));
      }

      $paymentInfo = $order->getParamArray($paymentID);
      $payeePurse = trim($paymentInfo[0]['value']);
      $secretKey = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[3]['value'];
      // предварительная проверка платежа
      if($_POST['LMI_PREREQUEST'] == 1) {
        $error = false;

        if(empty($orderInfo)) {
          echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          exit;
        }

        if(trim($_POST['LMI_PAYEE_PURSE']) != $payeePurse) {
          echo "ERR: НЕВЕРНЫЙ КОШЕЛЕК ПОЛУЧАТЕЛЯ " . $_POST['LMI_PAYEE_PURSE'];
          exit;
        }
        echo "YES";
        exit;
      } else {
        // проверка хэша, присвоение нового статуса заказу
        $chkstring = $_POST['LMI_PAYEE_PURSE'] .
          $_POST['LMI_PAYMENT_AMOUNT'] .
          $_POST['LMI_PAYMENT_NO'] .
          $_POST['LMI_MODE'] .
          $_POST["LMI_SYS_INVS_NO"] .
          $_POST["LMI_SYS_TRANS_NO"] .
          $_POST["LMI_SYS_TRANS_DATE"] .
          $secretKey .
          $_POST["LMI_PAYER_PURSE"] .
          $_POST["LMI_PAYER_WM"];
        
        $md5sum = strtoupper(hash($alg, $chkstring));

        if($_POST['LMI_HASH'] == $md5sum) {
          Controllers_Payment::actionWhenPayment(
            array(
              'paymentOrderId' => $paymentOrderId,
              'paymentAmount' => $paymentAmount,
              'paymentID' => $paymentID
            )
          );
          echo "YES";
          exit;
        } else {
          echo "ERR: Произошла ошибка или подмена параметров.";
          exit;
        }
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверка платежа через paymaster.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function paymaster($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $msg = '';

    if(empty($_POST['LMI_PAYMENT_NO'])) {
      echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
      exit;
    }

    if('success' == $paymentStatus) {
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($_POST['LMI_PAYMENT_NO']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['LMI_PAYMENT_NO']]['number']; 
      $msg .= $this->msg;
    } elseif('result' == $paymentStatus && $_POST) {
      $paymentAmount = trim($_POST['LMI_PAYMENT_AMOUNT']);
      //$paymentAmount = $paymentAmount*1;
      $paymentOrderId = trim($_POST['LMI_PAYMENT_NO']);
      if(!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quoteFloat($paymentAmount));
      }

      $paymentInfo = $order->getParamArray($paymentID);
      $payeePurse = trim($paymentInfo[0]['value']);
      $secretKey = trim($paymentInfo[1]['value']);
      $alg =  $paymentInfo[2]['value'];
      // предварительная проверка платежа
      if($_POST['LMI_PREREQUEST'] == 1) {
        $error = false;

        if(empty($orderInfo)) {
          echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          exit;
        }

        echo "YES";
        exit;
      } else {

        $chkstring = $_POST['LMI_MERCHANT_ID'] . ";" .
          $_POST['LMI_PAYMENT_NO'] . ";" .
          $_POST['LMI_SYS_PAYMENT_ID'] . ";" .
          $_POST['LMI_SYS_PAYMENT_DATE'] . ";" .
          $_POST['LMI_PAYMENT_AMOUNT'] . ";" .
          $_POST['LMI_CURRENCY'] . ";" .
          $_POST['LMI_PAID_AMOUNT'] . ";" .
          $_POST['LMI_PAID_CURRENCY'] . ";" .
          $_POST['LMI_PAYMENT_SYSTEM'] . ";" .
          $_POST['LMI_SIM_MODE'] . ";" .
          $secretKey;

        $md5sum = base64_encode(hash($alg,$chkstring, true));

        if($_POST['LMI_HASH'] == $md5sum) {

          Controllers_Payment::actionWhenPayment(
            array(
              'paymentOrderId' => $paymentOrderId,
              'paymentAmount' => $paymentAmount,
              'paymentID' => $paymentID
            )
          );
          echo "YES";
          exit;
        } else {
          echo "ERR: Произошла ошибка или подмена параметров.";
          exit;
        }
        $msg = 'Оплата не удалась';
      }
    }

    return $msg;
  }

  /**
   * Проверка платежа через ROBOKASSA.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function robokassa($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $msg = '';
    if('success' == $paymentStatus) {
      if(!empty($_POST['InvId'])) {
        $paymentAmount = trim($_POST['OutSum']);
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($_POST['InvId']), 1) . " and ROUND(summ+delivery_cost, 2) = " . DB::quoteFloat($paymentAmount));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']+$orderInfo['delivery_cost']);
        $isTest = $paymentInfo[6]['value'];

        if($isTest === 'false'){
          exit;
        }
        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['InvId']]['number']; 
        $merchantPass1 = trim($paymentInfo[1]['value']);
        $alg = $paymentInfo[3]['value'];
        $signatureValue = strtoupper($_POST['SignatureValue']);
        $out_summ = $orderInfo[$_POST['InvId']]['summ'];
        $inv_id = $_POST['InvId'];
        $sSignatureValue = $out_summ.':'.$inv_id.':'.$merchantPass1;

        $crcHashed = strtoupper(hash($alg,$sSignatureValue));
        
        if($signatureValue === $crcHashed) {
          Controllers_Payment::actionWhenPayment(
            array(
              'paymentOrderId' => $inv_id,
              'paymentAmount' => $out_summ,
              'paymentID' => $paymentID
            )
          );
        }

      } else {
        $msg = 'Не указан номер заказа!';
      }
      
      $msg .= $this->msg;
    } elseif('result' == $paymentStatus && count($_POST) > 1) {    
      $paymentAmount = trim($_POST['OutSum']);
      $paymentOrderId = trim($_POST['InvId']);
      if(!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and ROUND(summ+delivery_cost, 2) = " . DB::quoteFloat($paymentAmount));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']+$orderInfo['delivery_cost']);
       
      } else {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }
      // предварительная проверка платежа
      if(empty($orderInfo) || empty($paymentInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }

      $sMerchantPass2 = trim($paymentInfo[2]['value']);
      $alg = $paymentInfo[3]['value'];
      $sSignatureValue = $paymentAmount . ':' . $paymentOrderId . ':' . $sMerchantPass2;
      $md5sum = strtoupper(hash($alg,$sSignatureValue));

      if($_POST['SignatureValue'] == $md5sum) {
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $paymentAmount,
            'paymentID' => $paymentID
          )
        );

        echo "OK" . $paymentOrderId;
        exit;
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверка платежа через QIWI.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function qiwi($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $msg = '';
    if('success' == $paymentStatus) {
      if (!empty($_GET['order'])) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($_GET['order']), 1));
        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_GET['order']]['number'];
      } else {
        $msg = 'Не указан номер заказа!';
      }
      $msg .= $this->msg;
    } elseif('result' == $paymentStatus && $i = file_get_contents('php://input')) {

      $l = array('/<login>(.*)?<\/login>/', '/<password>(.*)?<\/password>/');
      $s = array('/<txn>(.*)?<\/txn>/', '/<status>(.*)?<\/status>/');

      preg_match($l[0], $i, $m1);
      preg_match($l[1], $i, $m2);

      preg_match($s[0], $i, $m3);
      preg_match($s[1], $i, $m4);

      $paymentOrderId = $m3[1];

      $statusQiwi = $m4[1];


      if(!empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1));
      } else {
        $orderInfo = NULL;
        echo "Ошибка обработки";
        exit();
      }

      $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
      $password = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[2]['value'];
      $parseLog =
        ' status=' . $statusQiwi .
        ' paymentOrderId=' . $paymentOrderId .
        ' paymentID=' . $paymentID .
        ' summ=' . $orderInfo[$paymentOrderId]['summ'];

      // если заказа не существует то отправляем код 150
      if(empty($orderInfo)) {
        $resultCode = 300;
      } else {

        $hash = strtoupper(hash($alg,$m3[1] . strtoupper(hash($alg,$password))));

        if($hash !== $m2[1]) { //сравнение хешей
          $resultCode = 150;
        } else {
          if($statusQiwi == 60) {// заказ оплачен         
            Controllers_Payment::actionWhenPayment(
              array(
                'paymentOrderId' => $paymentOrderId,
                'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
                'paymentID' => $paymentID
              )
            );
          }
          $resultCode = 0; // все прошло успешно оправляем "0"
        }
      }
      header('content-type: text/xml; charset=UTF-8');
      echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://client.ishop.mw.ru/"><SOAP-ENV:Body><ns1:updateBillResponse><updateBillResult>' . $resultCode . '</updateBillResult></ns1:updateBillResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
      exit;
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверка платежа через Interkassa.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function interkassa($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $msg = '';
    if('success' == $paymentStatus) {
      if (!empty($_POST['ik_pm_no'])) {
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($_POST['ik_pm_no']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['ik_pm_no']]['number'];
      } else {
        $msg = 'Не указан номер заказа!';
      }
      $msg .= $this->msg;
    } elseif('result' == $paymentStatus && count($_POST) > 1) {
  
      $paymentAmount = trim($_POST['ik_am']);
      $paymentOrderId = trim($_POST['ik_pm_no']);
      if(!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quoteFloat($paymentAmount));
      }
      // предварительная проверка платежа
      if(empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }

      $paymentInfo = $order->getParamArray($paymentID);
      $testKey = '*****';
      $normKey = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[3]['value'];
      $signString = $_POST['ik_co_id'];
      $key = $normKey;
      if(!empty($_POST['ik_pw_via']) && $_POST['ik_pw_via'] == 'test_interkassa_test_xts') {
        $key = $testKey;
      }

      $dataSet = $_POST;
      unset($dataSet['url']);
      unset($dataSet['ik_sign']);
      ksort($dataSet, SORT_STRING); // сортируем по ключам в алфавитном порядке элементы массива 
      array_push($dataSet, $key); // добавляем в конец массива "секретный ключ"    
      $signString = implode(':', $dataSet); // конкатенируем значения через символ ":" 
      $sign = base64_encode(hash($alg,$signString, true)); // берем MD5 хэш в бинарном виде по

      if($sign == $_POST['ik_sign']) {
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
            'paymentID' => $paymentID
          )
        );
        echo "200 OK";
        exit;
      } else {
        echo "Подписи не совпадают!";
        exit;
      }
    } else {
      $msg = 'Оплата не удалась';
    }
    return $msg;
  }

    /**
   * Проверка платежа через payanyway.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function payanyway($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $msg = '';
    
    // MG::loger('paymentStatus='.$paymentStatus);
       
    if('success' == $paymentStatus) {
      $paymentOrderId = trim(URL::getQueryParametr('MNT_TRANSACTION_ID'));
      if (!empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1));
        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['number'];
      } else {
        $msg = 'Не указан номер заказа!';
      }
      $msg .= $this->msg;
       /*     
      Controllers_Payment::actionWhenPayment(
        array(
          'paymentOrderId' => $paymentOrderId,
          'paymentAmount' => $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost'],
          'paymentID' => $paymentID
        )
      );*/
    } elseif('result' == $paymentStatus) {
        
       // MG::loger('POST=');
     //   MG::loger($_POST);
        
      //  MG::loger('GET=');
      //  MG::loger($_GET);
        
      $paymentAmount = trim($_REQUEST['MNT_AMOUNT']);
      $paymentOrderId = trim($_REQUEST['MNT_TRANSACTION_ID']);

      if(!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quoteFloat($paymentAmount));
        $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']);
        
       //   MG::loger($orderInfo);
       //   MG::loger($paymentInfo);
      } else {
        echo "FAIL";
        exit;
      }
      //  MG::loger('Шаг 1');
      // предварительная проверка платежа
      if(empty($orderInfo)) {
        echo "FAIL";
        exit;
      }

      $testmode = 0;

      if($paymentInfo[2]['value'] == 'true') {
        $testmode = 1;
      }

      $account = trim($paymentInfo[0]['value']);
      $securityCode = trim($paymentInfo[1]['value']);

      // предварительная проверка платежа обработка команды CHECK
      if($_REQUEST['MNT_COMMAND'] == 'CHECK') {
        //   MG::loger('Шаг 2');
        $summ = sprintf("%01.2f", $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']);
        $currency = (MG::getSetting('currencyShopIso') == "RUR") ? "RUB" : MG::getSetting('currencyShopIso');
        $alg = $paymentInfo[3]['value'];
        $sign = hash($alg, $_REQUEST['MNT_COMMAND'] . $account . $paymentOrderId . $summ . $currency . $testmode . $securityCode);
        
        if($sign == $_REQUEST['MNT_SIGNATURE']) {
         //   MG::loger('Шаг 3');
          $signNew = hash($alg, '402' . $account . $paymentOrderId . $securityCode);
          $responseXml = '<?xml version="1.0" encoding="UTF-8"?>
            <MNT_RESPONSE>
            <MNT_ID>' . $account . '</MNT_ID>
            <MNT_TRANSACTION_ID>' . $paymentOrderId . '</MNT_TRANSACTION_ID>
            <MNT_RESULT_CODE>402</MNT_RESULT_CODE>
            <MNT_DESCRIPTION>Оплата заказа ' . $paymentOrderId . '</MNT_DESCRIPTION>
            <MNT_AMOUNT>' . ($orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']) . '</MNT_AMOUNT>
            <MNT_SIGNATURE>' . $signNew . '</MNT_SIGNATURE>
            </MNT_RESPONSE>';
          header("Content-type: text/xml");
          echo $responseXml;
          
         // MG::loger($responseXml);
        } else {
          echo "Подписи не совпадают!";
        }
        
        exit;
      } elseif(isset($_REQUEST['MNT_OPERATION_ID'])) {
        //  MG::loger('Шаг 4');
        $summ = sprintf("%01.2f", $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']);
        $currency = (MG::getSetting('currencyShopIso') == "RUR") ? "RUB" : MG::getSetting('currencyShopIso');
        $alg = $paymentInfo[3]['value'];
        $sign = hash($alg, $_REQUEST['MNT_COMMAND'] . $account . $paymentOrderId . $_REQUEST['MNT_OPERATION_ID'] . $summ . $currency . $testmode . $securityCode);

        if($sign == $_REQUEST['MNT_SIGNATURE']) {
          //  MG::loger('Шаг 5');
          $signNew = hash($alg, '200' . $account . $paymentOrderId . $securityCode);


          /*
            1104 - НДС 0%
            1103 - НДС 10%
            1102 - НДС 18% (c 01.01.2019 ставка 20%)
          */
          $vatType = intval($paymentInfo[4]['value']);

          $orderData = reset($orderInfo);
          $orderContent = unserialize(stripslashes($orderData['order_content']));

          $inventory = [];
          foreach ($orderContent as $orderItem) {
            $orderItemName = trim(
              mb_substr(
                preg_replace(
                  '/[^0-9a-zA-Zа-яА-Я-,. ]/ui',
                  '',
                  htmlspecialchars_decode($orderItem['name'])
                ),
                0,
                12
              )
            );
            $inventory[] = [
              'name' => $orderItemName,
              'price' => number_format($orderItem['price'], 2, '.', ''),
              'quantity' => $orderItem['count'],
              'vatTag' => $vatType,
            ];
          }

          if ($inventory) {
            $inventoryJson = preg_replace_callback('/\\\\u(\w{4})/', function ($matches) {
              return html_entity_decode('&#x'.$matches[1].';', ENT_COMPAT, 'UTF-8');
            }, json_encode($inventory));

            $inventoryXml = '<ATTRIBUTE>'.
              '<KEY>INVENTORY</KEY>'.
              '<VALUE>'.$inventoryJson.'</VALUE>'.
              '</ATTRIBUTE>';
          }

          $email = $orderData['contact_email'] ? $orderData['contact_email'] : $orderData['user_email'] ? $orderData['user_email'] : '';

          $clientXml = '';
          if ($email) {
            $clientXml .= '<ATTRIBUTE>'.
                '<KEY>CUSTOMER</KEY>'.
                '<VALUE>'.$email.'</VALUE>'.
              '</ATTRIBUTE>';
          }

          if ($orderData['phone']) {
            $clientXml .= '<ATTRIBUTE>'.
                '<KEY>PHONE</KEY>'.
                '<VALUE>'.preg_replace('/\D+/', '', $orderData['phone']).'</VALUE>'.
              '</ATTRIBUTE>';
          }


          $responseXml = '<?xml version="1.0" encoding="UTF-8"?>
            <MNT_RESPONSE>
            <MNT_ID>' . $account . '</MNT_ID>
            <MNT_TRANSACTION_ID>' . $paymentOrderId . '</MNT_TRANSACTION_ID>
            <MNT_RESULT_CODE>200</MNT_RESULT_CODE>
            <MNT_SIGNATURE>' . $signNew . '</MNT_SIGNATURE>
            <MNT_ATTRIBUTES>
            '.$inventoryXml.
            $clientXml.'
            </MNT_ATTRIBUTES>
            </MNT_RESPONSE>';

          header("Content-type: text/xml");
          echo $responseXml;
         //   MG::loger('Шаг 6');
            
        //    MG::loger($responseXml);
          //Меняем статус оплаты на оплачен
        //  MG::loger('URL::getQueryParametr(MNT_TRANSACTION_ID)='.URL::getQueryParametr('MNT_TRANSACTION_ID'));
          $paymentOrderId = trim(URL::getQueryParametr('MNT_TRANSACTION_ID'));
        //  MG::loger('paymentOrderId='.$paymentOrderId);
          
          //$paymentOrderId = trim($_REQUEST['MNT_COMMAND']);
          //MG::loger('$_REQUEST[MNT_COMMAND]='.$_REQUEST['MNT_COMMAND']);
         // MG::loger('paymentOrderId='.$paymentOrderId);
            
          if (!empty($paymentOrderId)) { 
        //      MG::loger('Шаг 7');
            $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1));    
         //     MG::loger('Шаг 8');
         //     MG::loger($orderInfo);
         //      MG::loger('Меняем статус заказа '.$paymentOrderId);
         Controllers_Payment::actionWhenPayment(
              array(
                'paymentOrderId' => $paymentOrderId,
                'paymentAmount' => $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost'],
                'paymentID' => $paymentID
              )
            );
          } 

        } else {
          echo "Подписи не совпадают!";
        }
        
        exit;
      }
    } else {
      $msg = 'Оплата не удалась';
    }
    
    return $msg;
  }


  /**
   * Проверка платежа через Yandex.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function yandex($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $msg = '';
    if('success' == $paymentStatus) {
      if (!empty($_POST['label'])) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($_POST['label']), 1));
        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['label']]['number'];
      } else {
        $msg = 'Не указан номер заказа!';
      }
      $msg .= $this->msg;
    } elseif('result' == $paymentStatus && count($_POST) > 2) {     
      $paymentAmount = trim($_POST['withdraw_amount']);
      $paymentOrderId = trim($_POST['label']);
      if(!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and summ+delivery_cost = "
          . DB::quoteFloat($paymentAmount));
      }
      // предварительная проверка платежа
      if(empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }

      $paymentInfo = $order->getParamArray($paymentID);
      $secret = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[3]['value'];
      $pre_sha = $_POST['notification_type'] . '&' .
        $_POST['operation_id'] . '&' .
        $_POST['amount'] . '&' .
        $_POST['currency'] . '&' .
        $_POST['datetime'] . '&' .
        $_POST['sender'] . '&' .
        $_POST['codepro'] . '&' .
        $secret . '&' .
        $_POST['label'];

      $sha = hash($alg,$pre_sha);
      if($sha == $_POST['sha1_hash']) {
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
            'paymentID' => $paymentID
          )
        );
        echo "0";
        exit;
      } else {
        echo "1";
        exit;
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверка платежа через Яндекс.Кассу.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   */
  public function yandexKassa($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $action = URL::getQueryParametr('action');
    $orderNumber = URL::getQueryParametr('orderNumber');
    $orderId = URL::getQueryParametr('orderMId');

    if (empty($orderNumber)) {
      echo 'Не указан номер заказа!';
      exit();
    }
    
    if($paymentStatus == 'success') {
      //$orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
      $msg = 'Вы успешно оплатили заказ №' . $orderNumber;
      $msg .= $this->msg;
      return $msg;
    } elseif($paymentStatus == 'fail') {
      //$orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
      $msg = 'При попытке оплаты заказа №'.$orderNumber.' произошла ошибка.<br />Пожалуйста, попробуйте позже или используйте другой способ оплаты';
      $msg .= $this->msg;
      return $msg;
    }
    
    $error = false;
    
    $orderSumAmount = URL::getQueryParametr('orderSumAmount');
    $orderSumCurrencyPaycash = URL::getQueryParametr('orderSumCurrencyPaycash');
    $orderSumBankPaycash = URL::getQueryParametr('orderSumBankPaycash');
    $shopId = URL::getQueryParametr('shopId');
    $invoiceId = URL::getQueryParametr('invoiceId');
    $customerNumber = URL::getQueryParametr('customerNumber');
    $key = URL::getQueryParametr('md5');
    
    $responseXml = '<?xml version="1.0" encoding="UTF-8"?> ';
    
    if($action == 'paymentAviso') {
      $responseXml .= '<paymentAvisoResponse ';
    } else {
      $responseXml .= '<checkOrderResponse ';
    }
    
    $responseXml .= 'performedDatetime="'.date('c').'" ';
    
    if(!empty($orderSumAmount) && !empty($orderNumber) && !empty($orderId)) {
      $orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber) . " and ROUND(summ+delivery_cost, 2) = " . DB::quoteFloat($orderSumAmount));
    } else {
      $error = true;
      $responseXml .= 'code="200"
        message="не пришла сумма или номер"';
    }
    
    //action;orderSumAmount;orderSumCurrencyPaycash;orderSumBankPaycash;shopId;invoiceId;customerNumber;shopPassword 
    if(!empty($orderInfo)) {
      $paymentInfo = $order->getParamArray($paymentID);
      $shopPassword = trim($paymentInfo[3]['value']);
      $alg= $paymentInfo[4]['value'];

      $hash = strtoupper(hash($alg,$action.';'.$orderSumAmount.';'.$orderSumCurrencyPaycash.';'.$orderSumBankPaycash.';'.$shopId.';'.$invoiceId.';'.$customerNumber.';'.$shopPassword));
      
      if($action == 'checkOrder') {
        if($hash == $key) {
          $responseXml .= 'code="0" ';
        } else {
          $responseXml .= 'code="1" ';
        }
      } elseif($action == 'paymentAviso') {
        if($hash == $key) {
          $responseXml .= 'code="0" ';
        } else {
          $responseXml .= 'code="1" paymentAviso ';
        }
        
        if($orderInfo[$orderId]['status_id']!=2 && $orderInfo[$orderId]['status_id']!=4 && $orderInfo[$orderId]['status_id']!=5) {
          $orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
          Controllers_Payment::actionWhenPayment(
            array(
              'paymentOrderId' => $orderId,
              'paymentAmount' => $orderInfo[$orderId]['summ'],
              'paymentID' => $paymentID
            )
          );
        }
      } else {
        $responseXml .= 'code="200"
          message="Неизвестное действие"';
      } 
    } elseif(!$error) {
      $responseXml .= '
        code="200"
        message="Указаны неверные параметры заказа"';
    }
    
    $responseXml .= '
      invoiceId="'.$invoiceId.'" 
      shopId="'.$shopId.'" />';

    header('content-type: text/xml; charset=UTF-8');
    echo $responseXml;
    exit;
  }


  /**
   * Проверка платежа через AlfaBank.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function alfabank($paymentID, $paymentStatus) {
    $order = new Models_Order();
    $msg = '';
    if('result' == $paymentStatus && isset($_POST)) {     
      // если пользователь вернулся на страницу после оплаты, проверяем статус заказа
      if(isset($_REQUEST['orderId'])) {
        $paymentInfo = $order->getParamArray($paymentID, null, null);
          $serverUrl = (empty($paymentInfo[2]['value'])) 
          ? "https://engine.paymentgate.ru/payment/rest" : $paymentInfo[2]['value'];       
          if( $curl = curl_init() ) {
            curl_setopt($curl, CURLOPT_URL, $serverUrl.'/getOrderStatusExtended.do');
            $jsondata = array(
              "language" => "ru",
              "orderId" => $_REQUEST['orderId'],
              "userName" => trim($paymentInfo[0]['value']),
              "password" => trim($paymentInfo[1]['value']),
            );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsondata);
            $out = curl_exec($curl);
            curl_close($curl);
          }
          $obj = json_decode($out);

        // приводим сумму заказа к нормальному виду
        $obj->amount = substr($obj->amount, 0, - 2) . "." . substr($obj->amount, -2);

        // приводим номер заказа к нормальному виду
        $orderNumber = explode('/', $obj->orderNumber);
        $obj->orderNumber = $orderNumber[0];

        $paymentAmount = trim($obj->amount);
        $paymentOrderId = trim($obj->orderNumber);
        // проверяем имеется ли в базе заказ с такими параметрами
        if(!empty($paymentAmount) && !empty($paymentOrderId)) {
          $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and summ+delivery_cost = "
            . DB::quoteFloat($paymentAmount));
        }

        // если заказа с таким номером и стоимостью нет, то возвращаем ошибку
        if(empty($orderInfo)) {
          echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ (Заказа с таким номером не существует)";
          exit;
        }

        // если заказ есть и он успешно оплачен в банке
        if($obj->errorCode == 0 && $obj->actionCode==0) {
          // высылаем письма админу и пользователю об успешной оплате заказа, 
      // только если его действующий статус не равен "оплачен" или "выполнен" или "отменен"   
     // if($orderInfo[$paymentOrderId]['status_id']!=2 && $orderInfo[$paymentOrderId]['status_id']!=4 && $orderInfo[$paymentOrderId]['status_id']!=5) {
      if($orderInfo[$paymentOrderId]['status_id'] == 0 || $orderInfo[$paymentOrderId]['status_id'] == 1 ) {
        Controllers_Payment::actionWhenPayment(
        array(
          'paymentOrderId' => $paymentOrderId,
          'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
          'paymentID' => $paymentID
        )
        );
      }
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['number'];
      $msg .= $this->msg;
    } else {
      $msg = $obj->actionCodeDescription;
    }
    
      } else {
        //Запрос в альфабанк на формирование ссылки для перенаправления клиента к платежной форме
        if(!empty($_POST['paymentAlfaBank'])) {
          $paymentAmount = trim($_POST['amount']);
          $paymentOrderId = trim($_POST['orderNumber']);
          if(!empty($paymentAmount) && !empty($paymentOrderId)) {
            $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quoteFloat($paymentAmount));
          }
          // предварительная проверка платежа
          if(empty($orderInfo)) {
            echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
            exit;
          }
          $orderContent = unserialize(stripslashes($orderInfo[$paymentOrderId]['order_content']));
          $cartsItm = array();
          $taxType = $_POST['tax'];
          foreach($orderContent as $key => $item){
            $cartsItm[] = [
              'positionId' => $key+1,
              'name' => $item['name'],
              'quantity' => [
                'value' => $item['count'],
                'measure' => $item['unit']
              ],
              'itemCode' => $item['code'],
              'tax' => array('taxType' => $taxType),
              'itemPrice' => $item['fulPrice']*100
            ];
          }

          if($orderInfo[$paymentOrderId]['delivery_cost'] > 0){
            $cartsItm[] = [
              'positionId' => count($cartsItm)+1,
              'name' => 'Доставка',
              'quantity' => [
                'value' => '1',
                'measure' => 'шт.'
              ],
              'itemCode' => 'DOSTAVKA',
              'tax' => array('taxType' => $taxType),
              'itemPrice' => $orderInfo[$paymentOrderId]['delivery_cost']*100
            ];
          }
          $paymentInfo = $order->getParamArray($paymentID);
          $_POST['orderNumber'] = $_POST['orderNumber'] . '/' . time();
          $_POST['userName'] = urlencode(trim($paymentInfo[0]['value']));
          $_POST['password'] = urlencode(trim($paymentInfo[1]['value']));
          $_POST['amount'] = number_format($_POST['amount'], 2, '', '');
          $serverUrl = (empty($paymentInfo[2]['value'])) 
                ? "https://engine.paymentgate.ru/payment/rest" : $paymentInfo[2]['value'];
            if( $curl = curl_init() ) {
              curl_setopt($curl, CURLOPT_URL, $serverUrl.'/register.do');
              $jsondata = array(
                "amount" => $_POST['amount'],
                "currency" => $_POST['currency'],
                "language" => $_POST['language'],
                "orderNumber" => $_POST['orderNumber'],
                "returnUrl" => urldecode(SITE."/payment%3Fid=11&pay=result"),
                "userName" => $_POST['userName'],
                "password" => urldecode($_POST['password']),
                "description" =>$_POST['description']
              );
              if(isset($paymentInfo[6]['value']) && $paymentInfo[6]['value'] == 'true'){
                $jsondata['orderBundle'] = json_encode(array(
                  'cartItems' => ['items' => $cartsItm]
                ));
              }
              curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
              curl_setopt($curl, CURLOPT_POST, true);
              curl_setopt($curl, CURLOPT_POSTFIELDS, $jsondata);
              $out = curl_exec($curl);
              curl_close($curl);
            }
            $obj = json_decode($out);
          // если произошла ошибка
          if(!empty($obj->errorCode)) {
            echo "ERR: " . $obj->errorMessage;
            exit;
          }

          // если ссылка сформированна, то отправляем клиента в альфабанк
          if(!empty($obj->orderId) && !empty($obj->formUrl)) {
            header('Location: ' . $obj->formUrl);
          }
          echo "ERR: не удалось получить ответ с сервера эквайринга!";
          exit;
        }
      }
    }
    return $msg;
  }
    
  /**
   * Проверка платежа через liqpay.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  private function liqpay($paymentID, $paymentStatus) {
    if (!empty($_POST['data'])) {
      $data = json_decode(base64_decode($_POST['data']));
      $orderId = URL::getQueryParametr('order_id');
    } else {
      $orderId = 0;
    }

    if(intval($orderId) > 0) {
      sleep(2);
      $orderId = intval($orderId);
      $order = new Models_Order(); 
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt($orderId, 1));
      
      if(!empty($orderInfo)) {
        if(in_array($orderInfo[$orderId]['status_id'], array(2,5))) {
          $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$orderId]['number'];
          $msg .= $this->msg;
          $pay = 'success';
        } else {
          $msg = 'Неудалось произвести оплату заказа №' . $orderInfo[$orderId]['number'].'. Используйте другой способ оплаты, или попробуте позже.';
          $pay = 'fail';    
        }
      } else {
        $msg = 'Заказа, с указанным идентификатором не существует с системе';       
        $pay = 'fail';
      }      

      if(empty($paymentStatus)) {
        MG::redirect(URL::getUri().'&pay='.$pay);
      }
      
      return $msg;
    }    
    
    if('result' == $paymentStatus && count($_POST) > 1) {
      
      if(empty($_POST['data']) || empty($_POST['signature'])) {
        $msg = "Неверный ответа от сервиса оплаты";
        return $msg;
      }
      
      if($data->status == 'failure') {
        $msg = 'Неуспешный платеж';
        return $msg;
      }
      
      if($data->status == 'error') {
        $msg = 'Неуспешный платеж. Некорректно заполнены данные';
        return $msg;
      }
      
      if($data->status == 'reversed') {
        $msg = 'Платеж возвращен';
        return $msg;
      }
      
      $order = new Models_Order();              
      $received_public_key = $data->public_key;
      $paymentOrderId = $data->order_id;
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1));
      
      if(empty($orderInfo)) {
        $msg = 'Заказа, с указанным идентификатором не существует в системе';
        return $msg;
      }
      
      $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
      $publicKey = trim($paymentInfo[0]['value']);
      $privateKey = trim($paymentInfo[1]['value']);
      $sign = base64_encode(sha1($privateKey.$_POST['data'].$privateKey, 1));
      $paymentAmount = $data->amount;
      
      if($sign != $_POST['signature'] || $publicKey != $received_public_key) {
        $msg = "Не совпадает подпись или ключ доступа";
        return $msg;
      }else if($data->status == 'success') {
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $paymentAmount,
            'paymentID' => $paymentID
          )
        );
        
        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['id'];      
        $msg .= $this->msg;
      } else {
        $msg = 'Во время оплаты произошла ошибка.';
      }
    } else {
      $msg = "Не верный ответа от сервиса оплаты";        
    }
    
    return $msg;
  }
  
  /**
   * Проверка платежа через privat24.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function privat24($paymentID, $paymentStatus) {
    $order = new Models_Order();
    
    if('result' == $paymentStatus && !empty($_POST['payment'])) {
      $payment = $_POST['payment'];

      if($payment) {
        $payment_array = array();
        parse_str($payment, $payment_array);

        $state = trim($payment_array['state']);
        $paymentOrderId = trim($payment_array['order']);
        $orderNumber = trim($payment_array['ext_details']);
        $paymentAmount = trim($payment_array['amt']);

        switch($state) {
          case 'not found':
            $msg = "Платеж не найден";
            return $msg;
            break;
          case 'fail':
            $msg =  "Ошибка оплаты";
            return $msg;
            break;
          case 'incomplete':
            $msg = "Пользователь не подтвердил оплату";
            return $msg;
            break;
          case 'wait':
            $msg = "Платеж в ожидании";
            return $msg;
            break;
        }
        
        if(empty($paymentOrderId)) {
          $msg = "Оплата не удалась";
          return $msg;
        }

        if(!empty($paymentAmount) && !empty($paymentOrderId)) {
          $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($paymentOrderId), 1));
          $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
        } else {
          $msg = "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          return $msg;
        }
  
        if(empty($orderInfo) || empty($paymentInfo)) {
          $msg = "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          return $msg;
        }

        $merchant = trim($paymentInfo[0]['value']);
        $pass = trim($paymentInfo[1]['value']);

        $amt = round($orderInfo[$paymentOrderId]['summ'], 2) + round($orderInfo[$paymentOrderId]['delivery_cost'], 2);
        $payment = 'amt='.$amt.'&ccy=UAH&details=заказ на '.SITE.'&ext_details='.$orderNumber.'&pay_way=privat24&order='.$paymentOrderId.'&merchant='.$merchant;
        $signature = sha1(md5($payment.$pass));

        $paymentSignatureString = 'amt=' . round($payment_array['amt'], 2) . '&ccy=' . $payment_array['ccy'] . '&details=' .  $payment_array['details'] . '&ext_details=' . $payment_array['ext_details'] . '&pay_way=' . $payment_array['pay_way'] . '&order=' . $payment_array['order'] . '&merchant=' . $payment_array['merchant'];
        $paymentSignature = sha1(md5($paymentSignatureString.$pass));

        if($paymentSignature !== $signature) {
          $msg = "Подписи не совпадают!";
           return $msg;
        }

        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $paymentAmount,
            'paymentID' => $paymentID
          )
        );

        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['id'];      
        $msg .= $this->msg;

      } else {
        $msg = 'Оплата не удалась';
      }
    } else {
      $msg = 'Оплата не удалась';
    }
    return $msg;
  }
  /**
   * Проверка платежа через Сбербанк.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function sberbank($paymentID, $paymentStatus) {
    $msg = '';
    if('result' == $paymentStatus && isset($_POST)) {
      $order = new Models_Order();
      $paymentInfo = $order->getParamArray($paymentID, null, null);
      $serverUrl = (empty($paymentInfo[2]['value'])) 
              ? "https://3dsec.sberbank.ru" : $paymentInfo[2]['value'];
      $userName = urlencode(trim($paymentInfo[0]['value']));
      $password = urlencode(trim($paymentInfo[1]['value']));

      if(!empty($_POST['paymentSberbank'])) {
        $paymentAmount = trim($_POST['amount']);
        $paymentOrderId = trim($_POST['orderNumber']);

        if (!empty($paymentAmount) && !empty($paymentOrderId)) {
          $paymentAmount = round($paymentAmount, 2);
          $orderInfo = $order->getOrder(" id = " . DB::quoteInt($paymentOrderId, 1)
            . " and ROUND(summ+delivery_cost, 2) = " . DB::quoteFloat($paymentAmount));
        }
        // предварительная проверка платежа
        if (empty($orderInfo)) {
          $msg = "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          return $msg;
        }

        $orderNumber = $_POST['orderNumber'] . '/' . time();
        $amount = number_format($paymentAmount, 2, '', '');
        $url = $postUrl = $serverUrl . '/payment/rest/register.do';
        $url .= '?userName=' . $userName . '&password=' . $password . '&amount=' . $amount
          . '&currency=' . $_POST['currency'] . '&language=' . $_POST['language']
          . '&orderNumber=' . $orderNumber . '&description=' . $_POST['description']
          . '&returnUrl=' . urlencode($_POST['returnUrl']);
        //Данные для Post запроса при использовании онлайн кассы
        if ($paymentInfo[3]['value'] == 'true') {
          $postFilds = 'userName=' . $userName . '&password=' . $password . '&amount=' . $amount
            . '&currency=' . $_POST['currency'] . '&language=' . $_POST['language']
            . '&orderNumber=' . $orderNumber . '&description=' . $_POST['description']
            . '&returnUrl=' . urlencode($_POST['returnUrl']);
        }
        //Передача почты в сберик, если она есть
        if (isset($_POST['userEmail']) && preg_match('/\@/', $_POST['userEmail'])) {
          $url .= '&jsonParams=' . json_encode(['email' => $_POST['userEmail']]);
          $postFilds .= '&jsonParams=' . json_encode(['email' => $_POST['userEmail']]);
        }

        if ($paymentInfo[3]['value'] == 'true') {
          //$content = unserialize(stripslashes($orderInfo[$paymentOrderId]['order_content']));
          $content = $order->getCorrectOrderContent($orderInfo[$paymentOrderId]);
          $ids = array();
          $units = array();
          foreach ($content as $prod) {
            $ids[] = $prod['id'];
          }

          $res = DB::query("SELECT p.`id`, p.`unit` as produnit, c.`unit` as catunit 
              FROM `" . PREFIX . "product` p 
              LEFT JOIN `" . PREFIX . "category` c 
              ON p.`cat_id` = c.`id`
              WHERE p.`id` IN (" . DB::quoteIN($ids) . ")");
          while ($row = DB::fetchArray($res)) {
            if ($row['produnit']) {
              $units[$row['id']] = $row['produnit'];
            } elseif ($row['catunit']) {
              $units[$row['id']] = $row['catunit'];
            } else {
              $units[$row['id']] = 'шт.';
            }
          }

          $orderBundle = array();
          $i = 1;
          foreach ($content as $prod) {
            $item = array();

            $item['positionId'] = $i++;
            $prod['name'] = htmlspecialchars_decode($prod['name']);
            $prod['name'] = str_replace("\"", "'", $prod['name']); // проблема с товарами в названии которых двойные кавычки
            $prod['name'] = strip_tags($prod['name']);
            $prod['name'] = str_replace('\\', '-', $prod['name']);
            $prod['name'] = preg_replace('/[[:cntrl:]]/', '', $prod['name']);
            $prod['name'] = preg_replace('/\s+/', ' ', $prod['name']);
            $prod['name'] = MG::textMore(trim($prod['name']), 96);
            $item['name'] = urlencode($prod['name']);

            $item['quantity']['value'] = floatval($prod['count']); //Здесь убрано intval, потопму что пробелма с товарами, которые имеют кратность.
            $item['quantity']['measure'] = $units[$prod['id']];
            // Параметр отключён, теперь сбер сам рассчитывает сумму по товару
            //$item['itemAmount'] = strval($prod['price'] * $prod['count'] * 100); //Приведение к строке, чтобы не было проблем с кодированием в JSON

            $prod['code'] = str_replace('\\', '-', $prod['code']); // Проблема с обратным слэшом в артикуле
            $item['itemCode'] = urlencode($prod['code']);
            $item['tax']['taxType'] = $paymentInfo[5]['value'];
            $item['itemPrice'] = strval($prod['price'] * 100);
            $item['itemAttributes']['attributes'] = array(
              array('name' => 'paymentMethod', 'value' => 1),
              array('name' => 'paymentObject', 'value' => 1)
            );

            $orderBundle['cartItems']['items'][] = $item;
          }
          if ($orderInfo[$paymentOrderId]['delivery_cost'] > 0) {
            $item = array();

            $item['positionId'] = $i++;
            $item['name'] = 'Доставка';
            $item['quantity']['value'] = 1;
            $item['quantity']['measure'] = 'шт.';
            $item['itemAmount'] = strval($orderInfo[$paymentOrderId]['delivery_cost'] * 100); //Приведение к строке, чтобы не было проблем с кодированием в JSON
            $item['itemCode'] = 'DOSTAVKA';
            $item['tax']['taxType'] = $paymentInfo[6]['value'];
            $item['itemPrice'] = strval($orderInfo[$paymentOrderId]['delivery_cost'] * 100);
            $item['itemAttributes']['attributes'] = array(
              array('name' => 'paymentMethod', 'value' => 1),
              array('name' => 'paymentObject', 'value' => 4)
            );

            $orderBundle['cartItems']['items'][] = $item;
          }

          if (!empty($orderBundle)) {
            $postFilds .= '&orderBundle=' . json_encode($orderBundle) . '&taxSystem=' . $paymentInfo[4]['value'];
          }
        }
        //Если есть онлайн касса, то передаем данные с корзиной товаров Post запросом
        if ($paymentInfo[3]['value'] == 'true') {
          $curl = curl_init();
          curl_setopt($curl, CURLOPT_URL, $postUrl);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_POST, true);
          curl_setopt($curl, CURLOPT_POSTFIELDS, $postFilds);
          $out = curl_exec($curl);
          $objResponse = json_decode($out);
        } else {
          $jsondata = file_get_contents($url);
          $objResponse = json_decode($jsondata);
        }

        // Чтобы не было белого экрана 
        if (empty($objResponse)) {
          $msg = "ERR: Пустой ответ. Проверьте адрес сервера";
          return $msg;
        }

        // если произошла ошибка
        if (!empty($objResponse->errorCode)) {
          if ($objResponse->errorMessage == '[orderBundle] неверен') {
            $msg = "ERR: Ошибка в составе заказа";
            return $msg;
          }
          $msg = "ERR: " . $objResponse->errorMessage;
          return $msg;
        }

        // если ссылка сформированна, то отправляем клиента в альфабанк
        if (!empty($objResponse->orderId) && !empty($objResponse->formUrl)) {
          header('Location: ' . $objResponse->formUrl);
        }

        exit;
      } else if(!empty($_REQUEST['orderId']) || !empty($_REQUEST['mdOrder'])) {
          if (!empty($_REQUEST['mdOrder'])) {
            if ($_REQUEST['operation']!='deposited' || $_REQUEST['status']!=1) {
              return $msg;
            } else {
              $orderId = $_REQUEST['mdOrder'];
            }
          } else {
            $orderId = $_REQUEST['orderId'];
          }
        $url = $serverUrl.'/payment/rest/getOrderStatusExtended.do';
        $url .= '?userName=' . $userName . '&password=' . $password 
            . '&language=ru' . '&orderId=' . $orderId;

        $jsondata = file_get_contents($url);
        $objResponse = json_decode($jsondata);

        // если произошла ошибка
        if(!empty($objResponse->ErrorCode)) {
          $msg = "ERR: " . $objResponse->ErrorMessage;
          return $msg;
        }

        if($objResponse->errorCode == 0 && $objResponse->orderStatus == 2 
            && $objResponse->actionCode == 0) {
          // приводим номер заказа к нормальному виду
          $orderNumber = explode('/', $objResponse->orderNumber);
          $paymentOrderId = $orderNumber[0];
          
          $paymentAmount = substr($objResponse->amount, 0, - 2) . "." . substr($objResponse->amount, -2);

          // проверяем имеется ли в базе заказ с такими параметрами
          if(!empty($paymentAmount) && !empty($paymentOrderId)) {
            $orderInfo = $order->getOrder(" id = " . DB::quoteInt($paymentOrderId, 1)
                . " AND ROUND(summ+delivery_cost, 2) = " . DB::quoteFloat(round($paymentAmount, 2)));
          }

          // если заказа с таким номером и стоимостью нет, то возвращаем ошибку
          if(empty($orderInfo)) {
            $msg =  "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ";
            return $msg;
          }
          
          // высылаем письма админу и пользователю об успешной оплате заказа, 
          // только если его действующий статус не равен "оплачен" или "выполнен" или "отменен"   

          if(!empty($_REQUEST['mdOrder']) && ($orderInfo[$paymentOrderId]['status_id'] == 0 || $orderInfo[$paymentOrderId]['status_id'] == 1)) {
              Controllers_Payment::actionWhenPayment(
                array(
                  'paymentOrderId' => $paymentOrderId,
                  'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
                  'paymentID' => $paymentID
                )
              );
            }
          

          $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['number'];
          $msg .= $this->msg;
        } else {
          $msg = $objResponse->actionCodeDescription;
        }

      }
    }

    return $msg;
  }
  

  /**
   * Проверка платежа через Tinkoff.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function tinkoff($paymentID, $paymentStatus) {
    // Установить в true, чтобы выводить подробные логи проверки статуса платежа
    $DEBUG = false;

    if ($DEBUG) {
      mg::loger(' ----------------------------------------------------- ');
      mg::loger(' --- Инициализация проверки статуса оплаты Tinkoff --- ');
      mg::loger(' -- Параметры вызова метода -- ');
      mg::loger('paymentID: '.$paymentID);
      mg::loger('paymentStatus: '.$paymentStatus);
    }

    // Получаем тело запроса
    $requestBodyJson = file_get_contents('php://input');
    $body = json_decode($requestBodyJson, true);

    $orderModel = new Models_Order();
    $orderNumber = null;
    if (!empty($_REQUEST['OrderId'])) {
      $orderNumber = $_REQUEST['OrderId'];
    } 
    if (!empty($body['OrderId'])) {
      $orderNumber = $body['OrderId'];
    }

    if ($orderNumber) {
      $orders = $orderModel->getOrder('`id` = '.DB::quote($orderNumber));
      $order = $orders[$orderNumber];
      if ($order) {
        // Если страница результатов, то возвращаем сообщение с результатом
        if($paymentStatus == 'success') {
          //$orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
          $msg = 'Вы успешно оплатили заказ ' . $order['number'];
          $msg .= $this->msg;
          return $msg;
        } elseif($paymentStatus == 'fail') {
          //$orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
          $msg = 'При попытке оплаты заказа '.$order['number'].' произошла ошибка.<br />Пожалуйста, попробуйте позже или используйте другой способ оплаты';
          $msg .= $this->msg;
          return $msg;
        }
      } else {
        $msg = 'Такой заказ в системе не найден!';
        $msg .= $this->msg;
        return $msg;
      }
    } else {
      // Если страница результатов, то возвращаем сообщение с результатом
      if($paymentStatus == 'success') {
        $msg = 'Заказ успешно оплачен.';
        $msg .= $this->msg;
        return $msg;
      } elseif($paymentStatus == 'fail') {
        $msg = 'При попытке оплаты заказа произошла ошибка.<br />Пожалуйста, попробуйте позже или используйте другой способ оплаты.';
        $msg .= $this->msg;
        return $msg;
      }
    }

    // Если нет номера заказа, то возвращаем сообщение об этом
    if (!isset($body['OrderId'])) {
      $msg = 'Не указан номер заказа!';
      $msg .= $this->msg;
      return $msg;
    }

    $orderNumber = $body['OrderId'];

    if ($DEBUG) {
      mg::loger(' -- Параметры полученного запроса:');
      foreach ($body as $key => $value) {
        if (!is_array($value)) {
          mg::loger($key.': '.$value);
        }
      }
    }

    // Проверяем тело запроса
    $requiredFields = [
      'Token',
      'OrderId',
      'Status'
    ];
    if (count(array_intersect_key(array_flip($requiredFields), $body)) !== count($requiredFields)) {
      if ($DEBUG) {
        mg::loger('ERROR Нет обязательных параметров в запросе!');
        mg::loger(' --- Завершение проверки статуса оплаты Tinkoff --- ');
        mg::loger(' -------------------------------------------------- ');
      }
      echo 'OK';
      exit();
    }

    if ($DEBUG) {
      mg::loger('OK Запрос содежрит все обязательные параметры');
    }

    // Получаем секретный ключ терминала
    $paymentInfo = $orderModel->getParamArray($paymentID);
    $secretKey = $paymentInfo[1]['value'];

    if (!$secretKey) {
      if ($DEBUG) {
        mg::loger('ERROR Не удалось получить секретный ключ терминала!');
        mg::loger(' --- Завершение проверки статуса оплаты Tinkoff --- ');
        mg::loger(' -------------------------------------------------- ');
      }
      echo 'OK';
      exit();
    }

    if ($DEBUG) {
      mg::loger('OK Ключ терминала успешно получен');
    }

    // Проверяем токен запроса
    $verified = $this->checkTinkoffToken($body, $secretKey);
    if (!$verified) {
      if ($DEBUG) {
        mg::loger('ERROR Подпись запроса не прошла проверку!');
        mg::loger(' --- Завершение проверки статуса оплаты Tinkoff --- ');
        mg::loger(' -------------------------------------------------- ');
      }
      echo 'OK';
      exit();
    }

    if ($DEBUG) {
      mg::loger('OK Подпись запроса валидна');
    }

    // Возможно устаревшая проверка
    if ($paymentStatus === 'result') {
      // Получаем заказ
      $orderId = $body['OrderId'];
      $orderInfo = $orderModel->getOrder(" id = ".DB::quoteInt($orderId, 1));

      if (!$orderInfo) {
        if ($DEBUG) {
          mg::loger('ERROR Заказ с таким номером не найден!');
          mg::loger(' --- Завершение проверки статуса оплаты Tinkoff --- ');
          mg::loger(' -------------------------------------------------- ');
        }
        echo 'OK';
        exit();
      }

      if ($DEBUG) {
        mg::loger(' -- Полученные параметры заказа -- ');
        mg::loger($orderInfo);
      }

      // Если статус заказа НЕ ПОДТВЕРЖДЕН или ОЖИДАЕТ ОПЛАТЫ, то проверяем статус оплаты
      // и при необходимости меняем его на ОПЛАЧЕН
      if(($orderInfo[$orderId]['status_id'] == 0 || $orderInfo[$orderId]['status_id'] == 1) && $body['Status'] === 'CONFIRMED') {
        // Устанавливаем статус на оплачен, если тинькофф прислал статус Подтвержден (CONFIRMED)
        $actionWhenPaymentParams = [
          'paymentOrderId' => $orderId,
          'paymentAmount' => $orderInfo[$orderId]['summ'],
          'paymentID' => $paymentID
        ];
        if ($DEBUG) {
          mg::loger('INFO Вызван метод изменения статуса заказа');
          mg::loger(' -- Параметры, переданные в метод изменения статуса заказа -- ');
          mg::loger($actionWhenPaymentParams);
        }
        Controllers_Payment::actionWhenPayment($actionWhenPaymentParams);
        if ($DEBUG) {
          mg::loger(' --- Завершение проверки статуса оплаты Tinkoff --- ');
          mg::loger(' -------------------------------------------------- ');
        }
      } elseif ($DEBUG) {
        mg::loger('WARNING Статус заказа уже отличается от "Не подтвержден" или "Не оплачен"');
        mg::loger(' --- Завершение проверки статуса оплаты Tinkoff --- ');
        mg::loger(' -------------------------------------------------- ');
      }
    } elseif ($DEBUG) {
      mg::loger('ERROR Get параметры не соответствуют ожидаемым!');
      mg::loger(' --- Завершение проверки статуса оплаты Tinkoff --- ');
      mg::loger(' -------------------------------------------------- ');
    }
    echo 'OK';
    exit();
  }

  private function checkTinkoffToken($params, $secretKey) {
    // Получаем подпись из тела
    $token = $params['Token'];

    // Поля, кототрые не участвуюе в формировании подписи
    $exclusionFields = [
      'Token',
      'Shops',
      'Receipt',
      'DATA'
    ];

    foreach ($exclusionFields as $exclusionField) {
      unset($params[$exclusionField]);
    }

    // Формируем подпись
    $params['Password'] = $secretKey;
    ksort($params);
    foreach ($params as $paramKey => $param) {
      if (is_bool($param)) {
        $params[$paramKey] = $param ? 'true' : 'false';
      }
    }
    $paramsString = implode('', $params);
    $hash = hash('sha256', $paramsString);

    // Проверяем, соответсвует ли полученная подпись сформированной и возвращаем результат
    return $token === $hash;
  }

  /**
   * Проверка платежа через PayPal.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function paypal($paymentID, $paymentStatus) {
    $paymentType = $msg = '';
    $res = DB::query("SELECT `paramArray` FROM `".PREFIX."payment` WHERE `id` = 19");
    $row = DB::fetchAssoc($res);

    $i = 0;
    $paymentParam = array();
    $param = json_decode($row['paramArray']);
    foreach ($param as $key=>$value) {
      $paymentParam[$i] = CRYPT::mgDecrypt($value);
      $i++;
    }

    if (!ini_get('user_agent')) {
      ini_set('user_agent', "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36");
    }

    if(array_key_exists('amt', $_GET) && array_key_exists('cc', $_GET) && array_key_exists('cm', $_GET) && array_key_exists('tx', $_GET)) {
      $paymentType = 'pdt';
    }

    if(array_key_exists('mc_gross', $_POST) && array_key_exists('payment_status', $_POST) && array_key_exists('custom', $_POST) && array_key_exists('business', $_POST) && array_key_exists('txn_id', $_POST) && array_key_exists('mc_currency', $_POST)) {
      $paymentType = 'ipn';
    }

    if($paymentType == 'pdt') {

      $res = DB::query("SELECT `summ`, `delivery_cost`, `paided`, `number`, `currency_iso` FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($_GET['cm'], true));
      $row = DB::fetchAssoc($res);

      $orderNumber = $row['number'];
      $status = $row['paided'];
      $newPrice = $row['summ'] + $row['delivery_cost'];

      $currency = $row['currency_iso'];
      if($currency == 'RUR') {
        $currency = 'RUB';
      }

      if($_GET['amt'] == $newPrice && $status == 0) {

        if($paymentParam[2] === 'true' || $paymentParam[2] === true || $paymentParam[2] === 1) {
          $req = "cmd=_notify-synch&tx=".$_GET['tx']."&at=".$paymentParam[0];

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, "https://www.sandbox.paypal.com/cgi-bin/webscr");
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
          curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
          $res = curl_exec($ch);
          curl_close($ch);
        }
        else{
          $res = file_get_contents("https://www.paypal.com/cgi-bin/webscr?cmd=_notify-synch&tx=".$_GET['tx']."&at=".$paymentParam[0], false);
        }

        if(!$res || strpos($res, '400 Bad Request') !== false) {
          $msg = 'fail';
          $this->msg = 'Ошибка соединения с PayPal';
        }
        else{
          $lines = explode("\n", trim($res));
          $resArr = array();
          if(strcmp ($lines[0], "SUCCESS") == 0) {
            for ($i = 1; $i < count($lines); $i++) {
              $temp = explode("=", $lines[$i],2);
              $resArr[urldecode($temp[0])] = urldecode($temp[1]);
            }

            if($resArr['payment_status'] == 'Completed' && $newPrice == $resArr['mc_gross'] && $_GET['tx'] == $resArr['txn_id'] && $paymentParam[1] == $resArr['business'] && $currency == $resArr['mc_currency'] && $_GET['cm'] == $resArr['custom']) {

              $res = DB::query("SELECT `paided` FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($_GET['cm'], true));
              $row = DB::fetchAssoc($res);
              $status = $row['paided'];
              
              $res = DB::query('UPDATE `'.PREFIX.'order` SET status_id = 2, paided = 1 WHERE id = '.DB::quoteInt($resArr['custom'], true));

              if($res) {

                if($status == 0) {
                  // MG::loger('payed via pdt');
                  Controllers_Payment::actionWhenPayment(
                    array(
                      'paymentOrderId' => DB::quoteInt($resArr['custom'], true),
                      'paymentAmount' => $newPrice,
                      'paymentID' => $paymentID
                    ));
                }

              $msg = 'success'; 
              $this->msg = 'Вы успешно оплатили заказ № '.$orderNumber; 
              }
            }
            else{
              MG::loger('Проверка оплаты через PayPal (заказ № '.DB::quoteInt($resArr['custom'], true).') не удалась (проверка отклонена магазином)');
              $msg = 'fail';
              $this->msg = 'Оплата не удалась';
            }
          }
          else if(strcmp ($lines[0], "FAIL") == 0) {
            MG::loger('Проверка оплаты через PayPal (заказ № '.DB::quoteInt($_GET['cm'], true).') не удалась (проверка отклонена сервером PayPal)');
            $msg = 'fail';
            $this->msg = 'Оплата не удалась';
          }
        }
      }
      else if($status == 1) {
        //MG::loger('Заказ через PayPal № '.DB::quoteInt($_GET['cm'], true).' уже оплачен (скорее всего методом ipn)');
        $msg = 'success'; 
        $this->msg = 'Заказ № '.$orderNumber.' оплачен'; 
      }
      else{
        MG::loger('Проверка оплаты через PayPal (заказ № '.DB::quoteInt($_GET['cm']).') не удалась (не совпали данные запроса)');
        $msg = 'fail';
        $this->msg = 'Оплата не удалась';
      }
    } elseif($paymentType == 'ipn') {

      $postdata = $_POST;

      if(array_key_exists('charset', $postdata) && ($charset = $postdata['charset']) && $postdata['charset'] != 'utf-8') {
        foreach ($postdata as $key => $value) {
          $postdata[$key] = mb_convert_encoding($value, 'utf-8', $charset);
        }
      }

      $address = 'Адрес доставки PayPal: '.$postdata['address_country'].'; '.$postdata['address_state'].'; '.$postdata['address_city'].'; '.$postdata['address_street'].'; '.$postdata['address_name'];

      $orderid = $postdata['custom'];

      $res = DB::query("SELECT `summ`, `delivery_cost`, `currency_iso` FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($orderid, true));
      $row = DB::fetchAssoc($res);

      $newPrice = $row['summ'] + $row['delivery_cost'];

      $currency = $row['currency_iso'];
      if($currency == 'RUR') {
        $currency = 'RUB';
      }

      if($postdata['payment_status'] == 'Completed' && $postdata['mc_gross'] == $newPrice && $postdata['business'] == $paymentParam[1] && $postdata['mc_currency'] == $currency) {
        
        if($paymentParam[2] === 'true' || $paymentParam[2] === true || $paymentParam[2] === 1) {
          $link = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $link);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('cmd' => '_notify-validate') + $_POST) );
          curl_setopt($ch, CURLOPT_HEADER, 0);
          $res = curl_exec($ch);
          $stat = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);
        }
        else{
          $stat = 200;

          $req = http_build_query(array('cmd' => '_notify-validate') + $_POST);

          $res = file_get_contents("https://www.paypal.com/cgi-bin/webscr?".$req, false);
        }

        if($stat == 200 && $res == 'VERIFIED') {

          $res = DB::query("SELECT `paided` FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($orderid, true));
          $row = DB::fetchAssoc($res);
          $status = $row['paided'];

          if(strpos($row['comment'], 'Адрес доставки PayPal:') === false) {
            $address = $row['comment'].$address;
            DB::query('UPDATE `'.PREFIX.'order` SET `comment` = '.DB::quote($address).' WHERE id = '.DB::quoteInt($orderid));
          }

          if($status == 0) {
            $res = DB::query('UPDATE `'.PREFIX.'order` SET status_id = 2, paided = 1 WHERE id = '.DB::quoteInt($orderid, true));
            if($res) {
              // MG::loger('payed via ipn');
              Controllers_Payment::actionWhenPayment(
                array(
                  'paymentOrderId' => DB::quoteInt($orderid, true),
                  'paymentAmount' => $newPrice,
                  'paymentID' => $paymentID
                ));
              $msg = 'success'; 
              // return 'Вы успешно оплатили заказ № '.DB::quoteInt($orderid, true); 
            }
          }
          else{
            // MG::loger('Заказ через PayPal № '.DB::quoteInt($orderid, true).' уже оплачен (скорее всего методом pdt)');
            $msg = 'success'; 
            // return 'Заказ уже оплачен';
          }
        }
        else{
          MG::loger('Проверка оплаты через PayPal (заказ № '.DB::quoteInt($orderid, true).') не удалась (проверка отклонена сервером PayPal)');
          $msg = 'fail'; 
          // return 'Проверка оплаты не удалась';
        }
      }
      else{
        if(class_exists('IpnOrder') && $postdata['payment_status'] == 'Completed') {
          IpnOrder::createOrder($_POST, $paymentParam);
        }
        else{
          MG::loger('Проверка оплаты через PayPal (заказ № '.DB::quoteInt($orderid, true).') не удалась (не совпали данные запроса)');
        }
        $msg = 'fail'; 
        // return 'Проверка оплаты не удалась';
      }
    } else {
      $this->msg = 'Оплата не удалась';
    }
    return $msg;
  }

  /**
   * Проверка платежа через ComePay.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function comepay($paymentID, $paymentStatus)
  {
    if('result' == $paymentStatus) {
      $orderModel  = new Models_Order();
      $paymentInfo = $orderModel->getParamArray($paymentID, null, null);
      /**
       * Для Basic-авторизации при выставлении счетов
       */
      $shopNumber = $paymentInfo[1]['value'];
      /**
       * Для авторизации уведомлений платежной системы
       */
      $callbackPassword = $paymentInfo[3]['value'];
      if(isset($_SERVER['PHP_AUTH_USER'])) {
        if(($_SERVER['PHP_AUTH_USER'] !== $shopNumber || $_SERVER['PHP_AUTH_PW'] !== $callbackPassword)) {
          $resultCode = 150;
          echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";
          exit;
        }
      } elseif(isset($_SERVER['REMOTE_USER'])) {
        if(($_SERVER['REMOTE_USER'] !== 'Basic ' . base64_encode($shopNumber . ':' . $callbackPassword))) {
          $resultCode = 150;
          echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";
          exit;
        }
      } else {
        $resultCode = 150;
        echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";
        exit;
      }

      $transactionId = htmlspecialchars(URL::get("bill_id"));
      $orderId       = explode('-', $transactionId);
      if(empty($orderId[0])) {
        $resultCode = 0;
        echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";
        exit;
      }

      $orderId = intval($orderId[0]);

      $orderInfo = $orderModel->getOrder(" id = " . DB::quoteInt($orderId, true));
      if( ! isset($orderInfo)) {
        $resultCode = 0;
        echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";
        exit;
      }

      $order = $orderInfo[$orderId];
      //if($orderInfo[$orderId]['status_id'] != 2 && $orderInfo[$orderId]['status_id'] != 4 && $orderInfo[$orderId]['status_id'] != 5) {
      if($orderInfo[$orderId]['status_id'] == 0 || $orderInfo[$orderId]['status_id'] == 1 ) {  

        $amount = round(($order['summ'] + $order['delivery_cost']) * 100);

        $transactionAmount = round(intval(htmlspecialchars(URL::get("amount"))* 100));

        if($transactionAmount !== $amount) {
          $resultCode = 0;
          echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";
          exit;
        }
        $transactionStatus = htmlspecialchars(URL::get("status"));
        if(strtolower($transactionStatus) !== strtolower('paid')) {
          $resultCode = 0;
          echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";
          exit;
        }
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $orderId,
            'paymentAmount'  => $orderInfo[$orderId]['summ'],
            'paymentID'      => $paymentID
          )
        );
      }

      $resultCode = 0;
      echo "<?xml version=\"1.0\"?><result><result_code>{$resultCode}</result_code></result>";

      exit;
    } elseif('success' == $paymentStatus) {
      $this->msg = 'Вы успешно оплатили заказ';

      return 'success';
    } elseif('fail' == $paymentStatus) {
      $this->msg = 'Оплата не удалась';

      return 'fail';
    }

    return 'fail';
  }

  /**
   * Проверка платежа через PayKeeper.
   */
  public function paykeeper($paymentID, $paymentStatus) {
    $order = new Models_Order();
  
    if('success' == $paymentStatus) {
      
      if(!empty($_POST['clientid'])) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($_POST['clientid']), 1));
        $msg = 'Вы успешно оплатили заказ № ' . $orderInfo[$_POST['clientid']]['number'].'. Спасибо! Ожидайте звонка менеджера.'; 
      } else {
        $msg = 'Вы успешно оплатили заказ. Спасибо! Ожидайте звонка менеджера.';
      }  
      $msg .= $this->msg;
      
    } elseif('result' == $paymentStatus && count($_POST) > 1) {
      
      $id = $_POST['id'];
      $paymentAmount = $_POST['sum'];
      $paymentOrderId = intval($_POST['clientid']);
      $orderid = $_POST['orderid'];
      $key = $_POST['key'];
        
      //Проверка существование заказа и подлинности платежа
      if(!empty($paymentAmount) && $paymentOrderId > 0) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt($paymentOrderId, 1) . " and summ+delivery_cost = " . DB::quoteFloat($paymentAmount));
      }

      $paymentInfo = $order->getParamArray($paymentID);
      $secret_seed = trim($paymentInfo[2]['value']);

      if($key != md5 ($id . sprintf ("%.2lf", $paymentAmount).$paymentOrderId.$orderid.$secret_seed)) {
        echo "Error! Hash mismatch";
        exit();
      }
      
      // предварительная проверка платежа
      if(empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit();
      }
      if (!$orderInfo[$paymentOrderId]['paided']) {
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $paymentAmount,
            'paymentID' => $paymentID
          )
        );
      }
      
      // ОТДАЕМ PAYKEEPER ВСЕ OK
      echo "OK ".md5($id.$secret_seed);
      exit;      
    } else {
      $msg = 'Оплата не удалась';
    }
    
    return $msg;
  }

  /**
   * Проверка платежа через CloudPayments.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   * @return string
   */
  public function cloudpayments($paymentID, $paymentStatus) {
  
    $orderNumber = URL::getQueryParametr('orderNumber');

 //   if (empty($orderNumber)) {
 //     echo 'Оплата не удалась';
 //     exit();
 //   }

    // Редирект из виджета
    if($paymentStatus == 'success') {
      $msg = str_replace('{number}', $orderNumber, lang('paymentCloudPaymentsSuccess'));
      $msg .= $this->msg;
      return $msg;
    } elseif($paymentStatus == 'fail') {
      $msg = str_replace('{number}', $orderNumber, lang('paymentCloudPaymentsFail'));
      $msg .= $this->msg;
      return $msg;
    }

    // Обрабатываем уведомление от CloudPayments
    $response_codes = array(
      'SUCCESS' => 0,
      'ERROR_INVALID_ORDER' => 10,
      'ERROR_INVALID_COST' => 11,
      'ERROR_NOT_ACCEPTED' => 13,
      'ERROR_EXPIRED' => 20
    );

    $response = array(
      'code' => $response_codes['SUCCESS']
    );

    $order = new Models_Order();
    $paymentInfo = $order->getParamArray($paymentID, null, null);
    // Проверяем контрольную подпись
    $post_data    = file_get_contents('php://input');
    $check_sign   = base64_encode(hash_hmac('SHA256', $post_data, $paymentInfo[1]['value'], true));
    $request_sign = isset($_SERVER['HTTP_CONTENT_HMAC']) ? $_SERVER['HTTP_CONTENT_HMAC'] : '';


    if(false && $check_sign !== $request_sign) {
      $response['code'] = $response_codes['ERROR_NOT_ACCEPTED'];
      $response['msg'] = 'Invalid signature';
    } else {
      $action = URL::getQueryParametr('action');
      $orderId = null;
      if(isset($_POST['Data'])) {
        $data = json_decode(str_replace('&quot;', '"', $_POST['Data']), true);
        if(!empty($data['order_id'])) {
          $orderId = intval($data['order_id']);
        }
      }
      if(!empty($orderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quoteInt($orderId, 1));
        $orderInfo = current($orderInfo);
      } else {
        $orderNumber = isset($_POST['InvoiceId']) ? $_POST['InvoiceId'] : '';
        $orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
        $orderInfo = current($orderInfo);
        $orderId = isset($orderInfo['id']) ? $orderInfo['id'] : null;
      }
      if(empty($orderInfo)) {
        $response['code'] = $response_codes['ERROR_INVALID_ORDER'];
        $response['msg'] = 'Order not found';
      } else {
        // Запросы связанные с оплатой, для них проверяем статус заказа и сумму
        $is_payment_callback = in_array($action, array('check', 'pay', 'confirm'));
        $orderSum = floatval($orderInfo['summ']) + floatval($orderInfo['delivery_cost']);

        if($is_payment_callback && in_array($orderInfo['status_id'], array(2, 4, 5))) {
          // Нельзя оплатить уже оплаченный заказ
          $response['code'] = $response_codes['ERROR_NOT_ACCEPTED'];
          $response['msg'] = 'Order already payment or canceled';
        } 
        elseif($is_payment_callback && floatval($_POST['Amount']) != $orderSum) {
          // Проверяем сумму заказа
          $response['code'] = $response_codes['ERROR_INVALID_COST'];
          $response['msg'] = 'Invalid order summ, should be ' . $orderSum;
        } 
        elseif(($action == 'pay' && $_POST['Status'] == 'Completed') || $action == 'confirm') {
          Controllers_Payment::actionWhenPayment(
            array(
              'paymentOrderId' => $orderId,
              'paymentAmount' => $orderInfo['summ'],
              'paymentID' => $paymentID
            )
          );
        }
        elseif(in_array($action, array('fail', 'cancel'))) {
          $order = new Models_Order();
          if(method_exists($order, 'updateOrder')) {
            $order->updateOrder(array(
              'id' => $orderId,
              'status_id' => 0
            ), true);
          }
        }
        elseif(in_array($action, array('refund'))) {
          $order = new Models_Order();
          if(method_exists($order, 'updateOrder')) {
            $order->updateOrder(array(
              'id' => $orderId,
              'status_id' => 4
            ), true);
          }
        }
        elseif(in_array($action, array('pay')) && $_POST['Status'] == 'Authorized') {
          $order = new Models_Order();
          if(method_exists($order, 'updateOrder')) {
            $order->updateOrder(array(
              'id' => $orderId,
              'status_id' => 1
            ), true);
          }
        }
      }
    }

    header('Content-Type: application/json');
    echo json_encode($response, 256); //JSON_UNESCAPED_UNICODE для совместимости с PHP 5.3;
    exit;
  }

  /**
   * Проверка платежа через Яндекс.Кассу.
   * @param int $paymentID ID способа оплаты
   */
  public function yandexKassaNew($paymentID) {

    /* Узнаем статус
      waiting_for_capture
      succeeded
      canceled
    */

    $data = file_get_contents("php://input");
    $json = json_decode($data, true);
    
    $paymentStatus = $json['object']['status'];
    $orderId = $json['object']['metadata']['orderId'];
    $orderSessionId = $json['object']['metadata']['sessionId'];
    $orderPayTime = $json['object']['metadata']['payTime'];

    $sess_path = session_save_path();
    //флаг платежа
    $payFlag = true;
    //Проверка платежа по сессии
    if ($handle = opendir($sess_path)) {
        while (false !== ($entry = readdir($handle))) { 
            $file = $sess_path.DS.$entry;
            if(stripos($file, $orderSessionId) !== false){
                $sessionContent = file_get_contents($file);
                session_decode($sessionContent);
                if($_SESSION['pay_time'] != $orderPayTime){
                    $payFlag = false;
                }
            }
        }       
    }    

    if ((empty($orderId)) || (($payFlag == false))) {
      echo 'Оплата не удалась';
      exit();
    }
    //Массив статусов, которые могут быть изменены
    $orderStatusChangeArray = array(0, 1);
    $order = new Models_Order();
    $orderInfo = $order->getOrder(' id = '.DB::quoteInt(intval($orderId), true));
    $orderNumber = $orderInfo[$orderId]['number'];    
    if($paymentStatus == 'succeeded' || $paymentStatus == 'waiting_for_capture') {
      //$orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
      if(in_array($orderInfo[$orderId]['status_id'], $orderStatusChangeArray)){
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $orderId,
            'paymentAmount' => $orderInfo[$orderId]['summ'],
            'paymentID' => $paymentID
            )
          );
      }
      $msg = 'Вы успешно оплатили заказ №' . $orderNumber;
      $msg .= $this->msg;
      $this->msg = $msg;
    } elseif($paymentStatus == 'canceled') {
      //Нужно ли менять статус у заказа  
      if(in_array($orderInfo[$orderId]['status_id'], $orderStatusChangeArray) && (defined('CHANGE_STATUS_UKASSA')) &&  CHANGE_STATUS_UKASSA == 1){
      $order->setOrderStatus($orderId, 4);
      }
      $msg = "Оплата заказ №" . $orderNumber . " не удаласть\n";
      $msg .= "Ошибка: ".$json['object']['cancellation_details']['reason'];
      $msg .= $this->msg;
      $this->msg = $msg;
    }  else {  
      //Раскоментировать, если будут проблемы
      //MG::loger($data, 'new', 'yandex');
      $this->msg = 'Оплата не удалась';
    }
    return $paymentStatus;
  }

  /**
   * Проверка платежа через FreeKassa.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   */
  public function freeKassa($paymentID, $paymentStatus) {
    if('success' == $paymentStatus) {
      
      $msg = 'Вы успешно оплатили заказ. Спасибо!';
      
      $msg .= $this->msg;
      
    } elseif('fail' == $paymentStatus) {
      $msg = 'Не удалось оплатить заказ!';
      
      $msg .= $this->msg;
    } elseif('result' == $paymentStatus && !empty($_REQUEST['MERCHANT_ORDER_ID'])) {
      $orderAmount = $_REQUEST['AMOUNT']; //Стоимость заказа
      $orderId = $_REQUEST['MERCHANT_ORDER_ID']; //ID заказа

      if(empty($orderId)) {
        echo 'Оплата не удалась';
        exit();
      }

      //Получаем настройки оплаты
      $order = new Models_Order();
      $paymentInfo = $order->getParamArray($paymentID, null, null);

      //Получаем ID магазина и второе секретное слово
      $merchant_id = $paymentInfo[1]['value'];
      $merchant_secret = $paymentInfo[3]['value'];

      //Собираем подпись для проверки
      $sign = md5($merchant_id.':'.$orderAmount.':'.$merchant_secret.':'.$orderId);

      //Проверяем собранную подпись с пришедшей
      if ($sign != $_REQUEST['SIGN']) {
        //Если не совпало, то спамим в логер и прерываем выполнение
        MG::loger('Error pay free-kassa:');
        MG::loger($_REQUEST);
        die('wrong sign');
      }

      //Если подписи равны, то ищем заказ и помечаем его оплаченым
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt($orderId));
     // if($orderInfo[$orderId]['status_id']!=2 && $orderInfo[$orderId]['status_id']!=4 && $orderInfo[$orderId]['status_id']!=5) {
      if($orderInfo[$orderId]['status_id'] == 0 || $orderInfo[$orderId]['status_id'] == 1 ) {  
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $orderId,
            'paymentAmount' => $orderAmount,
            'paymentID' => $paymentID
            )
          );
      }


      echo 'YES'; //Возвращаем 'YES' по документации FREE-KASSA
      exit();
    }
  }

  /**
   * Проверка платежа через Мегакасса.
   * @param int $paymentID ID способа оплаты
   * @param string $paymentStatus статус платежа
   */
  public function megaKassa($paymentID, $paymentStatus){
    if ($paymentStatus == 'success') {

      $msg = 'Вы успешно оплатили заказ. Спасибо!';

      $msg .= $this->msg;

    } elseif ($paymentStatus == 'fail') {
      $msg = 'Не удалось оплатить заказ!';

      $msg .= $this->msg;
    } elseif ($paymentStatus == 'result') {

      //Получаем настройки оплаты
      $order = new Models_Order();
      $paymentInfo = $order->getParamArray(27, null, null);
      $secretKey = $paymentInfo[1]['value'];

      $orderId = $_REQUEST['mg_order_id']; //ID заказа
      $orderAmount = $_REQUEST['amount']; //Стоимость заказа

      // проверка IP-адреса
      $ipChecked = false;

      foreach (array('HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $param) {
        if (!empty($_SERVER[$param]) && $_SERVER[$param] === '5.196.121.217') {
          $ipChecked = true;
          break;
}
      }
      if (!$ipChecked) {
        die('error');
      }

      // проверка на наличие обязательных полей
      // поля $payment_time и $debug могут дать true для empty() поэтому их нет в проверке
      foreach (array('uid', 'amount', 'amount_shop', 'amount_client', 'currency', 'order_id', 'payment_method_title', 'creation_time', 'client_email', 'status', 'signature') as $field) {
        if (empty($_REQUEST[$field])) {
          die('error');
        }
      }

      // нормализация данных
      $uid = (int)$_REQUEST["uid"];
      $amount = (double)$_REQUEST["amount"];
      $amountShop = (double)$_REQUEST["amount_shop"];
      $amountClient = (double)$_REQUEST["amount_client"];
      $currency = $_REQUEST["currency"];
      $orderID = $_REQUEST["order_id"];
      $paymentMethodID = (int)$_REQUEST["payment_method_id"];
      $paymentMethodTitle = $_REQUEST["payment_method_title"];
      $creationTime = $_REQUEST["creation_time"];
      $paymentTime = $_REQUEST["payment_time"];
      $clientEmail = $_REQUEST["client_email"];
      $status = $_REQUEST["status"];
      $debug = (!empty($_REQUEST["debug"])) ? '1' : '0';
      $signature = $_REQUEST["signature"];

      // проверка валюты
      if (!in_array($currency, array('RUB', 'USD', 'EUR'), true)) {
        die('error');
      }

      // проверка статуса платежа
      if (!in_array($status, array('success', 'fail'), true)) {
        die('error');
      }

      // проверка формата сигнатуры
      if (!preg_match('/^[0-9a-f]{32}$/', $signature)) {
        die('error');
      }

      // проверка значения сигнатуры
      $signature_calc = md5(join(':', array($uid, $amount, $amountShop, $amountClient, $currency, $orderID, $paymentMethodID, $paymentMethodTitle, $creationTime, $paymentTime, $clientEmail, $status, $debug, $secretKey)));
      if ($signature_calc !== $signature) {
        die('error');
      }

      //Если подписи равны, то ищем заказ и помечаем его оплаченным
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt($orderId));

      /*
      if($orderInfo[$orderId]['status_id']!=2 && $orderInfo[$orderId]['status_id']!=4 && $orderInfo[$orderId]['status_id']!=5 && $_REQUEST['status'] == 'success') {
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $orderId,
            'paymentAmount' => $orderAmount,
            'paymentID' => $paymentID
          )
        );
      }*/

      if($orderInfo[$orderId]['status_id'] == 0 || $orderInfo[$orderId]['status_id'] == 1 ) {  
        if($_REQUEST['status'] == 'success') {
          Controllers_Payment::actionWhenPayment(
            array(
              'paymentOrderId' => $orderId,
              'paymentAmount' => $orderAmount,
              'paymentID' => $paymentID
            )
          );
        }
      }
    

      echo('ok');
      exit();
    }
    return $msg;
  }

  /**
  * Проверка платежа через Qiwi API.
  * @param int $paymentID ID способа оплаты
  * @param string $paymentStatus статус платежа
  */
  public function qiwiApi($paymentID, $paymentStatus){
  //Получаем настройки оплаты
  $order = new Models_Order();
  $paymentInfo = $order->getParamArray(28, null, null);
  $secretKey = $paymentInfo[1]['value'];
  $orderIDqiwi = $_SESSION['qiwiApi']['orderID'];

  $auth = 'Bearer '. $secretKey;
  $url = 'https://api.qiwi.com/partner/bill/v1/bills/'.$orderIDqiwi;
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/json", "Content-Type: application/json", "Authorization: $auth"));

  $response = curl_exec($curl);
  $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
  curl_close($curl);

  $response = json_decode($response, true);
  $status = $response['status']['value'];
  if ($status == 'PAID') {
    $orderID = preg_replace('/-.*/is', '',$response['billId']);
    $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($orderID), 1) . " and summ+delivery_cost = " . DB::quoteFloat($response['amount']['value']));

    if(empty($orderInfo)) {
          $msg = 'ERR: Заказ был изменен! Была произведена оплата '.$response['amount']['value'].' '.$response['amount']['currency'].'  по некорректному счету!';
          $msg .= $this->msg;
        }
    else{
    $msg = 'Вы успешно оплатили заказ. Спасибо!';
    $msg .= $this->msg;
      
    // Находим заказ и помечаем его оплаченным
    if ($response['billId'] == $orderIDqiwi) {
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt($orderID));
      //if($orderInfo[$orderID]['status_id']!=2 && $orderInfo[$orderID]['status_id']!=4 && $orderInfo[$orderID]['status_id']!=5) {
      if($orderInfo[$orderID]['status_id'] == 0 || $orderInfo[$orderID]['status_id'] == 1 ) {  
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $orderID,
            'paymentAmount' => $response['amount']['value'],
            'paymentID' => $paymentID
          )
        );
       }
      }
    }
  } else {
  $msg = 'Возникла ошибка в оплате заказа.';
  $msg .= $this->msg;
  }

  return $msg;
  }
  
  /**
  * Проверка платежа через intellectMoney.
  * @param int $paymentID ID способа оплаты
  * @param string $paymentStatus статус платежа
  */
  public function intellectMoney($paymentID, $paymentStatus){
    $order = new Models_Order();
    if($_REQUEST['paymentStatus'] == 5 && isset($_REQUEST['UserField_1'])){
      $orderInfo = $order->getOrder(" id = " . DB::quoteInt($_REQUEST['UserField_1']));
      if($orderInfo[$_REQUEST['UserField_1']]['status_id'] == 0 || $orderInfo[$_REQUEST['UserField_1']]['status_id'] == 1){
        $msg = 'Вы успешно оплатили заказ. Спасибо!';
        Controllers_Payment::actionWhenPayment(
          array(
            'paymentOrderId' => $_REQUEST['UserField_1'],
            'paymentAmount' => $_REQUEST['recipientAmount'],
            'paymentID' => $_REQUEST['id']
            )
          );
      }
    }else if($_REQUEST['paymentStatus'] == 3){
      $msg = 'Создан счет на оплату';
    }else if($_REQUEST['paymentStatus'] == 4){
      $msg = 'Оплата не удалась';
    }
    return $msg;
  }

      /**
     * Проверка платежа через beGateway.
     * @param int $paymentID ID способа оплаты
     * @param string $paymentStatus статус платежа
     * @return string
     */
    public function beGateway($paymentID, $paymentStatus) {
      $msg = '';
      $order = new Models_Order();
      $orderId = $_REQUEST['order'];

      if ($paymentStatus == 'success') {

          if (isset($orderId)) {
              $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($orderId), 1));
              $msg = 'Вы успешно оплатили заказ № ' . $orderInfo[$orderId]['number'] . '. Спасибо!';
          } else {
              $msg = 'Не указан номер заказа!';
          }

      } elseif ($paymentStatus == 'fail') {

          $msg = 'Не удалось оплатить заказ!';

      } elseif ($paymentStatus == 'result') {

          $paymentInfo = $order->getParamArray($paymentID, null, null);

          $rawbody = file_get_contents('php://input');

          $data = json_decode($rawbody, true);

          include_once CORE_LIB . 'BegatewayPayment.php';
          $api = new BegatewayPayment(
              $paymentInfo[0]['value'], // Shop ID
              $paymentInfo[1]['value'], // Shop Secret Key
              $paymentInfo[2]['value'], // Shop Public Key
              $paymentInfo[3]['value'], // Payment Domain
              $paymentInfo[4]['value'] // Test Mode
          );

          // Check request signature
          if ( !$api->isAuthorized() ) {
              $errorCode = 400;
              $errorMessage = 'Unauthorized Request';
              return $api->setError($errorCode, $errorMessage);
          }

          if (!isset($orderId) || !isset($data['transaction']['status'])) {
              $errorCode = 400;
              $errorMessage = 'Bad Request';
              return $api->setError($errorCode, $errorMessage);
          }

          $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($orderId), 1));

          if (empty($orderInfo)) {
              $errorCode = 500;
              $errorMessage = 'Error: Заказа, с указанным идентификатором не существует в системе.';
              return $api->setError($errorCode, $errorMessage);
          }


          if($orderInfo[$orderId]['status_id'] == 0 || $orderInfo[$orderId]['status_id'] == 1 ) {

            if ($data['transaction']['status'] == 'successful') {
                Controllers_Payment::actionWhenPayment(
                    array(
                        'paymentOrderId' => $orderId,
                        'paymentAmount' => $orderInfo[$orderId]['summ'],
                        'paymentID' => $paymentID
                    )
                );
            }

         }

          return true;

      }

      $msg .= $this->msg;
      return $msg;
  }

  public static function getQr($orderId){
    require_once(REAL_DOCUMENT_ROOT.'/mg-core/script/phpqrcode/qrlib.php');
    $arr = unserialize(stripslashes(mg::getSetting('propertyOrder')));
    $order = new Models_Order();    
    $orderInfo = $order->getOrder(" id = " . DB::quoteInt(intval($orderId), 1));
    $site = str_replace('http://', '', SITE);
    $site = str_replace('https://', '', $site);
    $startPurposeQr = lang('startPurposeQr');
    $completePurposeQr = lang('completePurposeQr');
    if (EDITION === 'saas') {
      $startPurposeQr = CloudCoreBase::$CloudLocales->decryptionContentToRaw($startPurposeQr);
      $completePurposeQr = CloudCoreBase::$CloudLocales->decryptionContentToRaw($completePurposeQr);
    }
    $propertyOrder = 'ST00012|Name='.htmlspecialchars_decode($arr['nameyur']).
                      '|PersonalAcc='.$arr['rs'].
                      '|BankName='.htmlspecialchars_decode($arr['bank']).
                      '|BIC='.$arr['bik'].
                      '|CorrespAcc='.$arr['ks'].
                      '|PayeeINN='.$arr['inn'].
                      '|KPP='.$arr['kpp'].
                      '|Purpose='.$startPurposeQr.' № '.$orderInfo[$orderId]['number'].' '.$completePurposeQr.' '.$site.
                      '|Sum='.((MG::numberDeFormat($orderInfo[$orderId]['summ_shop_curr']) + MG::numberDeFormat($orderInfo[$orderId]['delivery_shop_curr'])) * 100).
                      '|TechCode=01';
    $access = false;
    if (USER::getThis()->login_email && (USER::getThis()->login_email == $orderInfo[$orderId]['user_email'] || USER::getThis()->role != 2)) {
      $access = true;
    }
    if (MG::getSetting('autoRegister') == "false") {
      $access = true;
    }
    if (!$access) {
      MG::redirect('/404');
      return false;
    }
    header('Content-Type: image/x-png');
    QRcode::png($propertyOrder, false, 'M', 6, 2);
  }

  public function getData() {
    return $this->data;
  }
}
