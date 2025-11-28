<?php
/**
 * Контроллер: Payment
 *
 * Класс Controllers_Payment предназначен для приема и обработки платежей.
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Payment extends BaseController {
  public function __construct() {
    if (!MG::isNewPayment()) {
      // Старая система оплат
      $oldPaymentModel = new Models_PaymentOld();
      $this->data = $oldPaymentModel->getData();
    } else {
      // Скачивание логов
      if (
        !empty($_GET['downloadLogs']) &&
        $_GET['downloadLogs'] &&
        !empty($_GET['code'])
      ) {
        if(!(USER::access('setting') < 2)) {
          $code = $_GET['code'];
          Models_Payment::downloadLogs($code);
        }
        
        if(LANG != 'LANG' && LANG != 'default') {
          $lang = '/'.LANG;
        } else {
          $lang = '';
        }
        MG::redirect($lang.'/404');
        exit;
      }

      // Обработка webhook's или ссылок возврата от платёжек
      $data = Models_Payment::handleRequest();
      if ($data) {
        $this->data = $data;
        return;
      }

      // Страница успеха, страница ошибки (общая)
      if (!empty($_GET['payStatus'])) {
        $payStatus = $_GET['payStatus'];
        if ($payStatus === 'success') {
          $this->data = [
            'status' => $payStatus,
            'message' => 'Заказ успешно оплачен.',
          ];
        } elseif($payStatus == 'fail') {
          $this->data = [
            'status' => $payStatus,
            'message' => 'При попытке оплаты заказа произошла ошибка.<br />Пожалуйста, попробуйте позже или используйте другой способ оплаты.',
          ];
        } 
        if($payStatus == 'cancel') {
          $this->data = [
            'status' => $payStatus,
            'message' => 'Заказ был отменен.',
          ];
        }
      }
    }
  }

  /**
   * Действие при оплате заказа.
   * Обновляет статус заказа на Оплачен, отправляет письма оповещения, генерирует хук.
   * @param array $args массив с результатом оплаты
   * @return array
   */
  public static function actionWhenPayment($args) {
    $result = true;
    ob_start();
    $order = new Models_Order();

    if(method_exists($order, 'updateOrder')) {
      $order->updateOrder(array('id' => $args['paymentOrderId'], 'status_id' => 2, 'paided' => 1));
    }
    if(method_exists($order, 'sendMailOfPayed')) {
      $order->sendMailOfPayed($args['paymentOrderId'], $args['paymentAmount'], $args['paymentID']);
    }
    if(method_exists($order, 'sendLinkForElectro')) {
      $order->sendLinkForElectro($args['paymentOrderId']);
    }

    $content = ob_get_contents();
    ob_end_clean();

    // если в ходе работы метода допущен вывод контента, то записать в лог ошибку.
    if(!empty($content)) {
      MG::loger('ERROR PAYMENT: ' . $content);
    }

    return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
  }
}