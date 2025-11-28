<?php

/**
 * Контроллер: Order
 *
 * Класс Controllers_Order обрабатывает действия пользователей на 
 * странице оформления заказа.
 * - Производит проверку введенных данных в форму оформления заказа;
 * - Добавляет заказ в базу данных сайта;
 * - Для нового покупателя производится регистрация пользователя;
 * - Отправляет письмо с подтверждением заказа на указанный адрес покупателя 
 * и администратору сайта с составом заказа;
 * - Очищает корзину товаров, при успешном оформлении заказа;
 * - Перенаправляет на страницу с сообщеним об успешном оформлении заказа;
 * - Генерирует данные для страниц успешной и неудавшейся электронной оплаты 
 * товаров.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Order extends BaseController {
  public static $discountTitle = '';
  function __construct() {
    // костыль для обратной совместимости
    if (!empty($_POST['yur_info_nameyur']) && empty($_POST['yur_info']['nameyur'])) {$_POST['yur_info']['nameyur'] = $_POST['yur_info_nameyur'];}
    if (!empty($_POST['yur_info_adress']) && empty($_POST['yur_info']['adress'])) {$_POST['yur_info']['adress'] = $_POST['yur_info_adress'];}
    if (!empty($_POST['yur_info_inn']) && empty($_POST['yur_info']['inn'])) {$_POST['yur_info']['inn'] = $_POST['yur_info_inn'];}
    if (!empty($_POST['yur_info_kpp']) && empty($_POST['yur_info']['kpp'])) {$_POST['yur_info']['kpp'] = $_POST['yur_info_kpp'];}
    if (!empty($_POST['yur_info_bank']) && empty($_POST['yur_info']['bank'])) {$_POST['yur_info']['bank'] = $_POST['yur_info_bank'];}
    if (!empty($_POST['yur_info_bik']) && empty($_POST['yur_info']['bik'])) {$_POST['yur_info']['bik'] = $_POST['yur_info_bik'];}
    if (!empty($_POST['yur_info_ks']) && empty($_POST['yur_info']['ks'])) {$_POST['yur_info']['ks'] = $_POST['yur_info_ks'];}
    if (!empty($_POST['yur_info_rs']) && empty($_POST['yur_info']['rs'])) {$_POST['yur_info']['rs'] = $_POST['yur_info_rs'];}

    $lang = MG::get('lang');
    // Модель для работы заказом.
    $model = new Models_Order;  

    // для редиректа
    if(LANG != 'LANG' && LANG != 'default') {
      $lang = '/'.LANG;
    } else {
      $lang = '';
    }
    
    // Печать заказа в квитанцию.
    if (isset($_POST['printQittance'])) {
      $model->printQittance();
    }
    
    if ($orderId = URL::get('getOrderPdf')) {
      $model->getPdfOrder((int)$orderId);
    }

    // Запрос электронных товаров
    $fileToOrder = null;
    if (isset($_REQUEST['getFileToOrder'])) {
      $electroInfo = $model->getFileToOrder($_REQUEST['getFileToOrder']);
	    $infoMsg = '';
	  $orderInfo = $model->getOrder(' id = '.DB::quote(intval($_REQUEST['getFileToOrder'])));	
	  $orderNumber = $orderInfo[$_REQUEST['getFileToOrder']]['number'];
	  
      if ($electroInfo === false) {
        // $infoMsg = "Для просмотра страницы необходимо зайти на сайт под пользователем сделавшим заказ №".$orderNumber;
        $infoMsg = MG::restoreMsg('msg__order_denied',array('#NUMBER#' => $orderNumber));
      }

      if (is_array($electroInfo) && empty($electroInfo)) {
        // $infoMsg = "Заказ  не содержит электронных товаров или ожидает оплаты!";
        $infoMsg = MG::restoreMsg('msg__no_electro',array('#NUMBER#' => $orderNumber));
      };

      if (is_array($electroInfo) && !empty($electroInfo)) {
        // $infoMsg = "Скачать электронные товары для заказа №".$orderNumber."";
        $infoMsg = MG::restoreMsg('msg__electro_download',array('#NUMBER#' => $orderNumber));
      };
      $fileToOrder = array('infoMsg' => $infoMsg, 'electroInfo' => $electroInfo);
    }

    // пришел запрос на скачивание электронного товара
    if (isset($_REQUEST['link'])) {
      $model->getFileByMd5($_REQUEST['link']);
    }


    // Первый экран - Оформление заказа.
    $step = 1;
	
	// если до этого произошел редирект на этуже страницу на с параметром ?addOrderOk=1, то восстанавливаем массив $_POST и продолжаем работу скрипта как без редиректа
	 if (URL::get('addOrderOk')){
	   $_POST = $_SESSION['post'];
	 }

    // Если пришли данные с формы оформления заказа.
    if (isset($_POST['toOrder'])) {
      if (empty($_SESSION['cart'])) {
        MG::redirect($lang.'/cart');
      }

      // Если параметры введены корректно, то создается новый заказ.
      if ($error = $model->isValidData($_POST)) {
        $msg = $error;
      } else {
        // Второй экран - оплата заказа
        $step = 2;  
		
	    //сохраняем данные Post запроса и перенаправляем на страницу с Get параметром ?addOrderOk=1 для отслеживания конверсии цели в ЯндексМетрике
		if (URL::get('addOrderOk')) {	
		  $_SESSION['post'] = null;	
		} else {
		  $_SESSION['post'] = $_POST;
      if(LANG != 'LANG') {
        $lang = '/'.LANG;
      } else {
        $lang = '';
      } 
      MG::redirect($lang.'/order?addOrderOk=1');
    }	
        mgAddCustomPriceAction(array(__CLASS__, 'applyRate'));
        $model->delivery_cost = MG::roundPriceBySettings($model->delivery_cost);
        $model->summ = MG::roundPriceBySettings($model->summ);
        $orderArray = $model->addOrder();
        $orderId = $orderArray['id'];
        $orderNumber = $orderArray['orderNumber'];
        $summ = $model->summ + $model->delivery_cost;
        $pay = $model->payment;
        // для кредита
        $toGetId = $pay == 23 ? 14 : $pay;
        $paramArray = $model->getParamArray($toGetId, $orderId, $summ);
      }
    } else {
      $_SESSION['price_rate'] = 0;
    }        

    // Обработка действия при переходе по ссылке подтверждения заказа.
    if ($id = URL::getQueryParametr('id')) {
      $info = $this->confirmOrder($id);
      $msg = $info['msg'];
      $userEmail = $info['userEmail'];
      // Третий экран - подтверждение заказа по ссылке из письма.
      $step = 3;
    }
     // Обработка действия при переходе по ссылке получения информации о статусе заказа.
    if (URL::getQueryParametr('hash')) {
      $hash = URL::getQueryParametr('hash');
      // Информация о заказе по переданному id.
      $orderInfo = $model->getOrder('`'.PREFIX.'order`.hash = '.DB::quote($hash));  
      $id = (key($orderInfo));        
      if ($orderInfo) {
        if (USER::getUserInfoByEmail($orderInfo[$id]['user_email'],'login_email')) {
          $orderNumber = !empty($orderInfo[$id]['number']) ? $orderInfo[$id]['number'] : $id;
          // $msg = 'Посмотреть статус заказа Вы можете в <a href="'.SITE.'/personal">личном кабинете</a>.';
          $msg = MG::restoreMsg('msg__view_status',array('#NUMBER#' => $orderNumber, '#LINK#' => SITE.'/personal'));
      } else {
        $lang = MG::get('lang');
        $orderNumber = $orderInfo[$id]['number'];  
        $orderId = $id;     
        if (class_exists('statusOrder')) {
          $dbQuery = DB::query('SELECT `status` FROM `'.PREFIX.'mg-status-order` '
            . 'WHERE `id_status`='.DB::quote($orderInfo[$id]['status_id']));
          if ($dbRes = DB::fetchArray($dbQuery)) {
            $orderInfo[$id]['string_status_id'] = $dbRes['status'];
          }
        }
        if (!$orderInfo[$id]['string_status_id']) {
          $status = $model->getOrderStatus($orderInfo[$id]['status_id']);
          $orderInfo[$id]['string_status_id'] = $status; 
        }        
        $paymentArray = $model->getPaymentMethod($orderInfo[$id]['payment_id']);
        $orderInfo[$id]['paymentName'] = $paymentArray['name'];
        $msg = '';
      }       
      } else {
        // $msg = 'Некорректная ссылка.<br> Заказ не найден<br>';
        $msg = MG::restoreMsg('msg__order_not_found');
    }  
      // пятый экран - инфо о статусе заказа
      $step = 5;
    }

    // Запрос оплаты из ЛК.
    if (URL::getQueryParametr('pay')) {
      // Четвертый экран - Запрос оплаты из ЛК.
      $step = 4;
      $pay = URL::getQueryParametr('paymentId');
      $orderId = URL::getQueryParametr('orderID');
      $order = $model->getOrder(' id = '.DB::quoteInt($orderId));

      if (
        empty($_SESSION['user']->email) ||
        $_SESSION['user']->email !== $order[$orderId]['user_email']
      ) {
        $payHash = URL::getQueryParametr('payHash');
        if ($payHash !== $order[$orderId]['pay_hash']) {
          MG::redirect('/enter');
        }
      }

      $summ = URL::getQueryParametr('orderSumm');
      $summ = $order[$orderId]['summ'] * 1 + $order[$orderId]['delivery_cost'] * 1;
      $paramArray = $model->getParamArray($pay, $orderId, $summ);
    }

    // Новая система оплаты по ссылке (короткие ссылки)
    if ($payHash = URL::getQueryParametr('p')) {
      $orderId = Models_Order::payHashToOrderId($payHash);
      if ($orderId) {
        $orders = $model->getOrder(' id = '.DB::quoteInt($orderId));
        if ($order = $orders[$orderId]) {
          $step = 4;
          $summ = round(($order['summ'] + $order['delivery_cost']), 2);
          $pay = $order['payment_id'];
          $paramArray = $model->getParamArray($pay, $orderId, $summ);
        }
      }
    }

    // Если пользователь авторизован, то заполняем форму личными данными.
    if (User::isAuth()) {
      $userInfo = User::getThis();
      $_POST['email'] = isset($_POST['email']) ? $_POST['email'] : $userInfo->email;
      $_POST['phone'] = isset($_POST['phone']) ? $_POST['phone'] : $userInfo->phone;
      $_POST['fio'] = isset($_POST['fio']) ? $_POST['fio'] : 
        trim(($userInfo->sname?$userInfo->sname:'').' '.($userInfo->name?$userInfo->name:'').' '.($userInfo->pname?$userInfo->pname:''));
      $_POST['fio_sname'] = isset($_POST['fio_sname'])?$_POST['fio_sname']:$userInfo->sname;
      $_POST['fio_name'] = isset($_POST['fio_name'])?$_POST['fio_name']:$userInfo->name;
      $_POST['fio_pname'] = isset($_POST['fio_pname'])?$_POST['fio_pname']:$userInfo->pname;
      $_POST['address'] = isset($_POST['address']) ? $_POST['address'] : $userInfo->address;
      if (
        empty($_POST['customer']) &&
        (!empty($_POST['yur_info']['inn']) || $userInfo->inn) && 
        (empty($_POST['action']) || $_POST['action'] != 'getPaymentByDeliveryId')
      ) {
        $_POST['customer'] = 'yur';
      }
      $_POST['yur_info_nameyur'] = $_POST['yur_info']['nameyur'] = isset($_POST['yur_info']['nameyur'])?$_POST['yur_info']['nameyur']:$userInfo->nameyur;
      $_POST['yur_info_adress'] = $_POST['yur_info']['adress'] = isset($_POST['yur_info']['adress'])?$_POST['yur_info']['adress']:$userInfo->adress;
      $_POST['yur_info_inn'] = $_POST['yur_info']['inn'] = isset($_POST['yur_info']['inn'])?$_POST['yur_info']['inn']:$userInfo->inn;
      $_POST['yur_info_kpp'] = $_POST['yur_info']['kpp'] = isset($_POST['yur_info']['kpp'])?$_POST['yur_info']['kpp']:$userInfo->kpp;
      $_POST['yur_info_bank'] = $_POST['yur_info']['bank'] = isset($_POST['yur_info']['bank'])?$_POST['yur_info']['bank']:$userInfo->bank;
      $_POST['yur_info_bik'] = $_POST['yur_info']['bik'] = isset($_POST['yur_info']['bik'])?$_POST['yur_info']['bik']:$userInfo->bik;
      $_POST['yur_info_ks'] = $_POST['yur_info']['ks'] = isset($_POST['yur_info']['ks'])?$_POST['yur_info']['ks']:$userInfo->ks;
      $_POST['yur_info_rs'] = $_POST['yur_info']['rs'] = isset($_POST['yur_info']['rs'])?$_POST['yur_info']['rs']:$userInfo->rs;

      $_POST['address_index'] = $userInfo->address_index;
      $_POST['address_country'] = $userInfo->address_country;
      $_POST['address_region'] = $userInfo->address_region;
      $_POST['address_city'] = $userInfo->address_city;
      $_POST['address_street'] = $userInfo->address_street;
      $_POST['address_house'] = $userInfo->address_house;
      $_POST['address_flat'] = $userInfo->address_flat;
    }

    // Обработка ajax запроса из шаблона.
    if ('getPaymentByDeliveryId' == URL::getQueryParametr('action')) {
      if (MG::isNewPayment()) {
        $deliveryId = $_POST['deliveryId'];
        $customer = $_POST['customer'];
        $result = Models_Payment::getPaymentsByDeliveryId($deliveryId, $customer);
        echo json_encode($result);
        exit;
      } else {
        $this->getPaymentByDeliveryIdOld();
      }
    }
    
    // Обработка ajax запроса из шаблона.
    if ('setPaymentRate' == URL::getQueryParametr('action')) {
      $this->setPaymentRate();
    }

    // Обработка ajax запроса из шаблона.
    if ('getEssentialElements' == URL::getQueryParametr('action')) {
      $this->getEssentialElements();
    }

    //Обработка ajax запроса из редактирования заказа
    if('getDeliveryOrderOptions' == URL::getQueryParametr('action')) {           
      $this->getDeliveryOrderOptions();
    }

    $this->includeIconsPack();
    // Массив способов доставки.    
    $deliveryArray = $this->getDelivery();
    
    foreach ($deliveryArray as &$item) {
      MG::loadLocaleData($item['id'], LANG, 'delivery', $item);
    }
    
    // Массив способов оплаты.
    $deliveryCount = count($deliveryArray);  
   
    // если из доступных способов доставки - только один, то сразу находим для него способы оплаты
    if($deliveryCount===1) {
      $keyDev = array_keys($deliveryArray);
      $_POST['delivery'] = $deliveryArray[$keyDev[0]]['id'];
    }
    //Если это первый заход на форму, то плательщик не выставлен. 
    //Ставим подефолту физлицо 
    if (!isset($_POST['customer'])) {
      $_POST['customer'] = "fiz";
    }
    if (MG::isNewPayment()) {
      $paymentTable = Models_Payment::getPaymentsByDeliveryId(
        $_POST['delivery'],
        $_POST['customer'],
        true,
        $deliveryCount
      );
    } else {
      $paymentTable = $this->getPaymentByDeliveryIdOld(isset($_POST['delivery'])?$_POST['delivery']:'',isset($_POST['customer'])?$_POST['customer']:null,true,$deliveryCount);
    }
   
    // если доставка не предусмотрена, то выводим все доступные активные метода оплаты
    if ($deliveryCount === 0) {
      $paymentTable = '';
      $paymentsArray = [];
      if (MG::isNewPayment()) {
        $paymentsArray = Models_Payment::getPayments(true, false, true);
      } else {
        $paymentsArray = $this->getPayment();
      }
      foreach ($paymentsArray as $payment) {
        $paymentRate = '';
        
        $delivArray = json_decode($payment['deliveryMethod'], true);
        if ($_POST['customer'] == "yur" && $payment['id'] != "7") {
          continue;
        }

        if (!empty($payment['rate'])) {
          $paymentRate = (abs($payment['rate']) * 100) . '%';

          if ($payment['rate'] > 0) {
            $paymentRate = '(Комиссия ' . $paymentRate . ')';
          } else {
            $paymentRate = '(Скидка ' . $paymentRate . ')';
          }
        }

        $paymentTable .= '
         <li class="noneactive">
           <label>
           <input type="radio" name="payment" rel value=' . $payment['id'] . '>' .
                (MG::isNewPayment() ? $payment['public_name'] : $payment['name']) .
                '</label>
           <span class="icon-payment-' . $payment['id'] . '"></span>
             <span class="rate-payment">'.$paymentRate.'</span>
         </li>';
      }
    }

    if($step == 1) {
      mgAddCustomPriceAction(array(__CLASS__, 'applyRate'));
      if (defined('TEMPLATE_INHERIT_FROM')) {
        $orderFormFile = 'uploads'.DS.'generatedJs'.DS.'order_form.js';
        $orderFormSrc = '/uploads/generatedJs/order_form.js';
        if (file_exists(SITE_DIR.$orderFormFile)) {
          mgAddMeta('<script src="'.SITE.$orderFormSrc.'"></script>');
        }
      }
    }
    
    $cart = new Models_Cart;
    $summOrder = $cart->getTotalSumm();       
    $summOrder = MG::numberFormat($summOrder);
    if (empty($orderInfo)) {$orderInfo = array();}
    if ($step !=5 ) {
      $orderInfo = $model->getOrder('`'.PREFIX.'order`.id = '.DB::quote(intval($orderId)).'');
    }    
    $userInfo = USER::getUserInfoByEmail(isset($orderInfo[$orderId]['user_email'])?$orderInfo[$orderId]['user_email']:'','login_email');
    $settings = MG::get('settings');
    $orderNumber = !empty($orderInfo[$orderId]['number']) ? $orderInfo[$orderId]['number'] : $orderId;
    $linkToStatus = !empty($orderInfo[$orderId]['hash']) ? $orderInfo[$orderId]['hash'] : '';
    $approve_payment = isset($orderInfo[$orderId]['approve_payment']) ? $orderInfo[$orderId]['approve_payment'] : '';
    if (empty($deliveryPrice)) {$deliveryPrice = '';}
    if (empty($pay)) {$pay = '';}
    $deliveryInfo = Models_Order::getDeliveryMethod(false, isset($_POST['delivery'])?$_POST['delivery']:null);
    if(!empty($deliveryInfo['cost'])) {
      $deliveryPrice = '+ доставка: <span class="order-delivery-summ">'.round($deliveryInfo['cost']).' '.MG::getSetting('currency').'</span>';
    }
    if(!empty($deliveryInfo['cost'])&&($deliveryInfo['free']<MG::numberDeFormat($summOrder))) {
      $deliveryPrice = '';   
    } 
    $paymentViewFile = $this->getPaymentViewFile($pay);
    /*
    Проверка, можно ли отображать платежную форму:
      1) Проверяем, если у нас заказ на Юр.лицо (quittance), можно скачивать заказ сразу после оплаты или статус заказа изменен с дефолтного (т.е. менеджер его не подтвердил)
      2) Если у нас оплата на физика и выключена опция "Оплата после подтверждения менеджером"
      3) Если оплата на физика, включена опция "Оплата после подтверждения менеджером" и заказ разрешено оплачивать
      4) Если нет полей настройки "Оплата после подтверждения менеджером" или "Скачивание счета"
    */
    $propertyOrder = unserialize(stripcslashes(MG::getSetting('propertyOrder')));
    if (
      (
        $paymentViewFile == 'quittance' &&
        isset($propertyOrder['downloadInvoice']) &&
        (
          $propertyOrder['downloadInvoice'] == 1 ||
          (
            $propertyOrder['downloadInvoice'] == 2 && $orderInfo[$orderId]['status_id'] != $propertyOrder['order_status']
          )
        )
      ) ||
      (
        $paymentViewFile != 'quittance' &&
        isset($propertyOrder['paymentAfterConfirm']) &&
        $propertyOrder['paymentAfterConfirm'] == 'false'
      ) ||
      (
        $paymentViewFile != 'quittance' &&
        isset($propertyOrder['paymentAfterConfirm']) &&
        $propertyOrder['paymentAfterConfirm'] == 'true' &&
        $approve_payment == 1
      ) ||
      (
        $paymentViewFile != 'quittance' &&
        !isset($propertyOrder['paymentAfterConfirm']) ||
        !isset($propertyOrder['downloadInvoice'])
      )
    ){
      $showPaymentForm = 1;
    }else{
      $showPaymentForm = 0;
    }  
    // Костыль для новых оплат
    $paymentViewContent = '';
    if (MG::isNewPayment()) {
      if ($pay) {
        $paymentViewFile = 'payments';
        if ($showPaymentForm) {
          $paymentViewContent = Models_Payment::getPaymentForm($pay, $orderId);
          if (empty($paymentViewContent)) {
            $showPaymentForm = 0;
          }
        }
      }
    }

    // Массив параметров для отображения в представлении.
    $data = array(
      'active' => !empty($userEmail) ? $userEmail : '', //состояние активации пользователя.
      'msg' => !empty($msg) ? $msg : '', //сообщение.
      'step' => !empty($step) ? $step : '', //стадия оформления заказа.
      'delivery' => !empty($deliveryArray) ? $deliveryArray : '', //массив способов доставки.
      'deliveryInfo' => $deliveryPrice,
      'paymentArray' => !empty($paymentTable) ? $paymentTable : '', //массив способов оплаты.
      'paramArray' => !empty($paramArray) ? $paramArray : '', //массив способов оплаты.
      'id' => !empty($orderId) ? $orderId : '', //id заказа.
      'orderNumber' => !empty($orderNumber) ? $orderNumber : $orderId, //id заказа.
      'summ' => !empty($summ) ? $summ : '', //сумма заказа.
      'pay' => !empty($pay) ? $pay : '', //
      'payMentView' => $this->getPaymentView($pay), //
      'paymentViewFile' => $paymentViewFile, // название файла с формой оплаты
      'paymentViewContent' => $paymentViewContent, // форма оплаты
      'currency' => $settings['currency'],
      'userInfo' => $userInfo,
      'orderInfo' => $orderInfo,
      'fileToOrder' => $fileToOrder,
      'meta_title' => 'Оформление заказа',
      'meta_keywords' => !empty($model->currentCategory['meta_keywords']) ? $model->currentCategory['meta_keywords'] : "заказы,заявки,оформить,оформление заказа",
      'meta_desc' => !empty($model->currentCategory['meta_desc']) ? $model->currentCategory['meta_desc'] : "Оформление заказа происходит в несколько этапов. 1 - ввод личных данных покупателя, 2 - оплата заказа.",
      'summOrder' => !empty($summOrder) ? $summOrder.' '.MG::getSetting('currency') : '', //сумма заказа без доставки
      'captcha' => (MG::getSetting('captchaOrder') == 'true' ? true : false),
      'recaptcha' => ((MG::getSetting('captchaOrder') == 'true' && MG::getSetting('useReCaptcha') == 'true' && MG::getSetting('reCaptchaSecret') && MG::getSetting('reCaptchaKey')) ? true : false),
      'linkToStatus' => $linkToStatus,
      'showPaymentForm' =>  $showPaymentForm // показывать ли форму платежа
      );

      $paymentOutdated = Models_Payment::checkPaymentOutdated($orderInfo[$orderId]['payment_id']);
      if ($paymentOutdated) {
        $data['paymentViewContent'] = '<div class="c-alert c-alert--red">Способ оплаты отключен администратором! Измените способ оплаты в <a href="'.SITE.'/personal" style="text-decoration: underline !important;">личном кабинете</a> или обратитесь к администратору.</div>';
        $data['paymentViewFile'] = 'payments';
      }
      $this->data = $data;
  }    

  /**
   * Возвращает путь к странице с формой оплаты.
   * @param int $pay id способа оплаты.
   * @return string путь к странице с формой оплаты.
   */
  public function getPaymentView($pay) {
    if (MG::isNewPayment()) {
      return SITE_DIR.'mg-core'.DS.'layout'.DS.'payment_payments.php';
    }
	  $payMentView = '';
    switch ($pay) {
      case 1:
        $payMentView = 'webmoney.php';
        break;
      case 2:
        $payMentView = 'yandex.php';
        break;
      case 5:
        $payMentView = 'robokassa.php';
        break;
      case 6:
        $payMentView = 'qiwi.php';
        break;
      case 7:
        $payMentView = 'quittance.php';
        break;
      case 8:
        $payMentView = 'interkassa.php';
        break;
      case 9:
        $payMentView = 'payanyway.php';
        break;
      case 10:
        $payMentView = 'paymaster.php';
        break;
      case 11:
        $payMentView = 'alfabank.php';
        break;
      case 14:
        $payMentView = 'yandex-kassa.php';
        break;
      case 15:
        $payMentView = 'privat24.php';
        break;
      case 16:
        $payMentView = 'liqpay.php';
        break;
      case 17:
        $payMentView = 'sberbank.php';
        break;
      case 18:
        $payMentView = 'tinkoff.php';
        break;
      case 19:
        $payMentView = 'paypal.php';
        break;
      case 20:
        $payMentView = 'comepay.php';
        break;
      case 21:
        $payMentView = 'paykeeper.php';
        break;
      case 22:
        $payMentView = 'cloudpayments.php';
        break;
      case 23:
        $payMentView = 'yandex-kassa-kredit.php';
        break;
      case 24:
        $payMentView = 'yandex-kassa-api.php';
        break;
      case 25:
        $payMentView = 'yandex-kassa-api-apple.php';
        break;
      case 26:
        $payMentView = 'free-kassa.php';
        break;
      case 27:
        $payMentView = 'megakassa.php';
        break;
      case 28:
        $payMentView = 'qiwi-api.php';
        break;
      case 29:
        $payMentView = 'intellectmoney.php';
        break;     
      case 30:
        $payMentView = 'begateway.php';
        break;
      case 31:
        $payMentView = 'qrcode.php';
      break;

    }
    $dir = URL::getDocumentRoot();
    if (file_exists($dir.'mg-templates'.DS.MG::getSetting('templateName').DS.'layout'.DS.'payment_'.$payMentView)) {
      $payMentView2 = $dir.'mg-templates'.DS.MG::getSetting('templateName').DS.'layout'.DS.'payment_'.$payMentView;
    }
    elseif (file_exists($dir.'mg-core'.DS.'layout'.DS.'payment_'.$payMentView)) {
      $payMentView2 = $dir.'mg-core'.DS.'layout'.DS.'payment_'.$payMentView;
    }
    else{
      $payMentView2 = 'mg-pages/payment/'.$payMentView;
    }

    return $payMentView2;
  }

  /**
   * Возвращает название файла с формой оплаты.
   * @param int $pay id способа оплаты.
   * @return string название файла с формой оплаты.
   */
  public function getPaymentViewFile($pay) {
    $payMentView = '';
    switch ($pay) {
      case 1:
        $payMentView = 'webmoney';
        break;
      case 2:
        $payMentView = 'yandex';
        break;
      case 5:
        $payMentView = 'robokassa';
        break;
      case 6:
        $payMentView = 'qiwi';
        break;
      case 7:
        $payMentView = 'quittance';
        break;
      case 8:
        $payMentView = 'interkassa';
        break;
      case 9:
        $payMentView = 'payanyway';
        break;
      case 10:
        $payMentView = 'paymaster';
        break;
      case 11:
        $payMentView = 'alfabank';
        break;
      case 14:
        $payMentView = 'yandex-kassa';
        break;
      case 15:
        $payMentView = 'privat24';
        break;
      case 16:
        $payMentView = 'liqpay';
        break;
      case 17:
        $payMentView = 'sberbank';
        break;
      case 18:
        $payMentView = 'tinkoff';
        break;
      case 19:
        $payMentView = 'paypal';
        break;
      case 20:
        $payMentView = 'comepay';
        break;
      case 21:
        $payMentView = 'paykeeper';
        break;
      case 22:
        $payMentView = 'cloudpayments';
        break;
      case 23:
        $payMentView = 'yandex-kassa-kredit';
        break;
      case 24:
        $payMentView = 'yandex-kassa-api';
        break;
      case 25:
        $payMentView = 'yandex-kassa-api-apple';
        break;
      case 26:
        $payMentView = 'free-kassa';
        break;
      case 27:
        $payMentView = 'megakassa';
        break;
      case 28:
        $payMentView = 'qiwi-api';
        break;
      case 29:
        $payMentView = 'intellectmoney';
        break;
      case 30:
        $payMentView = 'begateway';
        break;
      case 31:
        $payMentView = 'qrcode';
      break;
    }

    return $payMentView;
  }

  /**
   * Возвращает сообщение о статусе заказа "Подтвержден".
   * @param int $pay - id заказа.
   * @return array - сообщение и email пользователя.
   */
  public function confirmOrder($id) {
    // Модель для работы заказом.
    $model = new Models_Order;
	  $userEmail = '';
    // Информация о заказе по переданному id.
    $orderInfo = $model->getOrder('`'.PREFIX.'order`.id = '.DB::quote(intval($id)));
    $hash = URL::getQueryParametr('sec');
    // Информация о пользователе, сделавший заказ .
    $orderUser = USER::getUserInfoByEmail($orderInfo[$id]['user_email'],'login_email');
    $orderNumber = !empty($orderInfo[$id]['number']) ? $orderInfo[$id]['number'] : $id;
    // Если присланный хэш совпадает с хэшом из БД для соответствующего id.
    if ($orderInfo[$id]['confirmation'] == $hash) {
      if ($orderInfo[$id]['hash'] == '') {
          // $msg = 'Посмотреть статус заказа Вы можете в <a href="'.SITE.'/personal">личном кабинете</a>.';
          $msg = MG::restoreMsg('msg__view_status',array('#NUMBER#' => $orderNumber, '#LINK#' => SITE.'/personal'));
        } 
        else  {
          // $msg = 'Следить за статусом заказа Вы можете по ссылке <br> '
          //   . '<a href="'.SITE.'/order?hash='.$orderInfo[$id]['hash'].'">'.SITE.'/order?hash='.$orderInfo[$id]['hash'].'</a>';
          $msg = MG::restoreMsg('msg__view_order',array('#NUMBER#' => $orderNumber, '#LINK#' => SITE.'/order?hash='.$orderInfo[$id]['hash']));
        }
      // Если статус заказа "Не подтвержден".
      if (0 == $orderInfo[$id]['status_id']) {
        // Подтверждаем заказ.
        $orderStatus = 1;
        // если оплата выбрана наложенным платежём или наличными(курьеру), то статус заказа изменяем на "в доставке"
        if(in_array($orderInfo[$id]['payment_id'], array(3, 4))) {  
          $orderStatus = 6;
        }    
        
        $model->sendStatusToEmail($id, $orderStatus);
        $model->setOrderStatus($id, $orderStatus);
        
        $orderNumber = $orderInfo[$id]['number'];    
        $orderId = $id;
        // $msg = 'Ваш заказ №'.$orderNumber.' подтвержден и передан на обработку. <br>'.$msg;
        $msg = MG::restoreMsg('msg__order_confirmed',array('#NUMBER#' => $orderNumber)).$msg;
      } else {
        // $msg = 'Заказ уже подтвержден и находится в работе. <br> '.$msg;
        $msg = MG::restoreMsg('msg__order_processing',array('#NUMBER#' => $orderNumber)).$msg;
      }
      if (!$orderUser->activity) {
        $userEmail = $orderUser->email;
        $_SESSION['id'] = $orderUser->id;
      }
    } else {
      // $msg = 'Некорректная ссылка.<br> Заказ не подтвержден<br>';
      $msg = MG::restoreMsg('msg__order_not_confirmed',array('#NUMBER#' => $orderNumber));
    }

    $result = array(
      'msg' => $msg,
      'userEmail' => $userEmail,
    );
    return $result;
  }

  /**
   * Возвращает массив доступных способов доставки.
   * <code>
   * $result = Controllers_Order::getDelivery();
   * viewData($result);
   * </code>
   * @return array массив доступных способов доставки.
   */
  public function getDelivery() {
    $result = array();

    // Модель для работы с заказом.
    $model = new Models_Order;
    $cart = new Models_Cart;
    $cartSumm = $cart->getTotalSumm();

    foreach (Models_Order::getDeliveryMethod() as $id => $delivery) {
      if ($delivery['free'] != 0 && $delivery['free'] <= $cartSumm) {
        $delivery['cost'] = 0;
      }

      if (!$delivery['activity']) {
        continue;
      }

      if (isset($_POST['delivery']) && $_POST['delivery'] == $id) {
        $delivery['checked'] = 1;
      }

      // Заполнение массива способов доставки.
      $result[$delivery['id']] = $delivery;
    }

    // Если доступен только один способ доставки, то он будет выделен.
    if (1 === count($result)) {
      $deliveryId = array_keys($result);
      $result[$deliveryId[0]]['checked'] = 1;
    }

    return $result;
  }

  /**
   * Возвращает массив доступных способов оплаты.
   * <code>
   * $result = Controllers_Order::getDelivery();
   * viewData($result);
   * </code>
   * @return array массив доступных способов оплаты.
   */
  public function getPayment() {
    $result = array();

    // Модель для работы с заказом.
    $model = new Models_Order;

    $i = 1;
    // Количество активных методов оплаты.
    $countPaymentMethod = 0;
    $allPayment = $model->getPaymentBlocksMethod();
    foreach ($allPayment as $payment) {
      $i++;
      if (!empty($_POST['payment']) && !empty($deliveryArray)) {
        $delivArray = json_decode($payment['deliveryMethod'], true);
        if (!$delivArray[$_POST['delivery']])
          continue;
      }

      if (!$payment['activity']) {
        continue;
      }

      if (!empty($_POST['payment']) == $payment['id']) {
        $payment['checked'] = 1;
      }

      // Заполнение массива способов оплаты.
      $result[$payment['id']] = $payment;
      $countPaymentMethod++;
    }
    return $result;
  }

  /**
   * Возвращает массив доступных способов оплаты с учетом количества способов доставки.
   * @deprecated
   * @param array массив способов доставки
   * @return array массив доступных способов оплаты.
   */
  public function getPaymentTable($deliveryArray) {
    $result = array();
    // Массив способов оплаты.
    $paymentArray = $this->getPayment();

    // Если доступен только один способ доставки.
    if (1 == count($deliveryArray)) {
      $deliveryId = array_keys($deliveryArray);
      foreach ($paymentArray as $payment) {
        $delivArray = json_decode($payment['deliveryMethod'], true);
        if (!$delivArray[$deliveryId[0]]) {
          continue;
        }
        $result[$payment['id']] = $payment;
      }
    } else {
      $result = $paymentArray;
    }

    // Если доступен только один способ оплаты, то он будет выделен.
    if (1 == count($result)) {
      $paymentId = array_keys($result);
      $result[$paymentId[0]]['checked'] = 1;
    }

    return $result;
  }

  /**
   * Используется при AJAX запросе, 
   * возвращает html список способов доставки в зависимости от 
   * выбранного способа доставки.
   * @param int ID заказа
   */
  public function getDeliveryOrderOptions($orderId=null) {
    $orderId = intval($_POST['order_id']); 
    $orderOptions = array();
    $model = new Models_Order();
    $delivery = Models_Order::getDeliveryMethod(false, $_POST['deliveryId']);
    $orderOptions = array(
      'deliverySum' => $delivery['cost'],
    );        
    //Если указан id заказа
    if($orderId > 0) {      
      $orderInfo = $model->getOrder(' id = '.DB::quote($orderId));
      
      if(!empty($delivery['plugin'])) {
        if($orderInfo[$orderId]['delivery_id'] == $_POST['deliveryId']) {
          if(empty($_SESSION['deliveryAdmin'][$_POST['deliveryId']])) {
            $orderOptions = unserialize(stripslashes($orderInfo[$orderId]['delivery_options']));  
            $_SESSION['deliveryAdmin'][$_POST['deliveryId']] = $orderOptions;  
          }   
          
          $orderOptions['deliverySum'] = 0;
        } else {
          $orderOptions = $_SESSION['deliveryAdmin'][$_POST['deliveryId']];
          $orderOptions['deliverySum'] = 0;
        }        
      } else {
        if($orderInfo[$orderId]['delivery_id'] == $_POST['deliveryId']) {
          $orderOptions = array(
            'deliverySum' => $orderInfo[$orderId]['delivery_cost'],
          );
        }
      }          
    } else {          
      if(!empty($delivery['plugin']) && !empty($_SESSION['deliveryAdmin'][$_POST['deliveryId']])) {
        $orderOptions = $_SESSION['deliveryAdmin'][$_POST['deliveryId']];
        $orderOptions['deliverySum'] = 0;
      }
    } 
    
    echo json_encode($orderOptions);
    exit();
  }
  
  /**
   * Используется при AJAX запросе, 
   * возвращает html список способов оплаты в зависимости от 
   * выбранного способа доставки.
   * @param int ID заказа
   * @param string тип покупателя
   * @param bool возвращать верстку или ajax ответ
   * @param int количество доставок
   * @return string html верстка
   */
  public function getPaymentByDeliveryIdOld($deliveryId=null,$customer=null,$nojson=false, $countDeliv=null) {
    
    if(empty($deliveryId) && !empty($_POST['deliveryId'])) {
      $deliveryId = $_POST['deliveryId'];
    }
    if(empty($customer) && !empty($_POST['customer'])) {
      $customer = $_POST['customer'];
    }    
    if($countDeliv===1) {
      $seletFirst = true;
    }    

    $countPaymentMethod = 0; //количество активных методов оплаты

    $paymentTable = '';

    foreach ($this->getPayment() as $payment) {  
      if (isset($payment['deliveryMethod'])) { 
        $delivArray = json_decode($payment['deliveryMethod'], true);
      } else {
        $delivArray = array();
      }
      if($customer=="yur" && $payment['permission'] == "fiz") {
        continue;
      }

      if($customer=="fiz" && $payment['permission'] == "yur") {
        continue;
      }
      
      if (empty($delivArray[$deliveryId]) || !$payment['activity']) {
        continue;
      }

      $countPaymentMethod++;
    }

    foreach ($this->getPayment() as $payment) {
      if (isset($payment['deliveryMethod'])) {
        $delivArray = json_decode($payment['deliveryMethod'], true);
      } else {
        $delivArray = array();
      }
      
      if($customer=="yur" && $payment['permission'] == "fiz") {
        continue;
      }

      if($customer=="fiz" && $payment['permission'] == "yur") {
        continue;
      }
      
      if (empty($delivArray[$deliveryId]) || !$payment['activity']) {
        continue;
      }

      if (isset($_POST['lang'])) {
        MG::loadLocaleData($payment['id'], $_POST['lang'], 'payment', $payment);
      } else {
        MG::loadLocaleData($payment['id'], LANG, 'payment', $payment);
      }

      $payActive = false;

      if ((isset($_POST['payment']) && $payment['id']===$_POST['payment']) || 1 == $countPaymentMethod) {
        $payActive = true;
      }
      if (defined('TEMPLATE_INHERIT_FROM')) {
        ob_start();
        component('order/payments', array('id' => $payment['id'], 'name' => $payment['name'], 'rate' => $payment['rate'], 'active' => $payActive));
        $paymentTable .= ob_get_clean();
      } else {
        $paymentTable .= MG::layoutManager('layout_payment', array('id' => $payment['id'], 'name' => $payment['name'], 'rate' => $payment['rate'], 'active' => $payActive));
      }
      
    }

    if($nojson) {
      return $paymentTable;
    }
    
    $summDelivery = 0;                             
    $deliveryArray = $this->getDelivery();
    foreach($deliveryArray as $delivery) {
      if ($delivery['id'] == $deliveryId && $delivery['cost'] != 0 ) {
        $delivery['cost'] = MG::convertPrice($delivery['cost']);
        $summDelivery = MG::numberFormat($delivery['cost']).' '.MG::getSetting('currency');
      }
    }    
    // Расшифровка локалей
    if(class_exists('CloudCore')){
      $paymentTable = CloudCore::decryptionContent($paymentTable);
    }
    $result = array(
      'status' => true,
      'paymentTable' => $paymentTable,
      'summDelivery' => $summDelivery,
    );
    
    $args = func_get_args();
    
    if(empty($args)) {
      $args = array($deliveryId);
    }
    
    $result = MG::createHook(__CLASS__."_getPaymentByDeliveryId", $result, $args);
    echo json_encode($result);
    MG::disableTemplate();    
    exit;
  }
  /**
   * Устанавливает наценку от способа оплаты 
   * <code>
   * $_POST['paymentId'] = 1;
   * $_SESSION['price_rate'] = 0.5;
   * $model = new Controllers_Order();
   * $model->setPaymentRate();
   * </code>
   */
  public function setPaymentRate() {
    if(!empty($_POST['paymentId'])) {
      $order = new Models_Order();
      if (MG::isNewPayment()) {
        $payment = Models_Payment::getPaymentById($_POST['paymentId']);
      } else {
        $payment = $order->getPaymentMethod($_POST['paymentId'], false);
      }
      
      if(!empty($payment['rate'])) {
        $_SESSION['price_rate'] = $payment['rate'];
        mgAddCustomPriceAction(array(__CLASS__, 'applyRate'));        
      } else {
        $_SESSION['price_rate'] = 0;
      }
      
      $cart = new Models_Cart;
      $summOrder = $cart->getTotalSumm();       
      $res = array(
        'summ' => MG::numberFormat($summOrder).' '.htmlspecialchars_decode(MG::getSetting('currency')), 
        'rate' => $_SESSION['price_rate'], 
        'cur' => htmlspecialchars_decode(MG::getSetting('currency')),
        'enableDeliveryCur' => MG::getSetting('enableDeliveryCur')); 
      echo json_encode($res);
      exit;
    }        
  }
  /**
   * Добавляет к заказу наценку от способа оплаты 
   * <code>
   * $_SESSION['price_rate'] = 0.5;
   * $product = Array(
   *   'priceWithCoupon' => 19499,
   *   'priceWithDiscount' => 19499
   * );
   * $model = new Controllers_Order();
   * $res = $model->applyRate($product);
   * viewData($res);
   * </code>
   * @param array массив параметров заказа
   * @return float
   */
  static function applyRate($args) {
    $price = $args['priceWithCoupon'];
    if(!empty($_SESSION['price_rate'])) {
      $price += $price * $_SESSION['price_rate'];
      if ($_SESSION['price_rate'] > 0) {
        self::$discountTitle = 'Наценка способа оплаты';
      } elseif ($_SESSION['price_rate'] < 0) {
        self::$discountTitle = 'Скидка способа оплаты';
      }
    }
    return round($price, 2);   
  }
  
  /**
   * Используется при AJAX запросе.
   * <code>
   * $_POST['paymentId'] = 1;
   * $model = new Controllers_Order();
   * $model->getEssentialElements();
   * </code>
   */
  public function getEssentialElements() {
    $paymentId = $_POST['paymentId'];
	  $model = new Models_Order;
    $paramArray = $model->getParamArray($paymentId);
    $result = array(
      'name' => $paramArray[0]['name'],
      'value' => $paramArray[0]['value']
    );
    echo json_encode($result);
    MG::disableTemplate();
    exit;
  }

  /**
   * Подключает набор иконок для способов оплаты.
   * <code>
   * $model = new Controllers_Order();
   * $model->includeIconsPack();
   * </code>
   */
  public function includeIconsPack() {
    /* Иконки оплаты для сайта */
    mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/layout.order.css" rel="stylesheet"/>');
  }

}
