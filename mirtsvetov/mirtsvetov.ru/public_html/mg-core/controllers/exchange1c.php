<?php

/**
 * Класс Exchange1c - предназначен для обмена данными между "1с - Управление Торговлей" и Moguta.CMS.
 * - Импортирует товары из 1с на сайт.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 * @version 1.0.2
 */
class Controllers_Exchange1c extends BaseController
{
  public $startTime = null;
  public $maxExecTime = null;
  public $mode = null;
  public $type = null;
  public $filename = null;
  public $auth = null;
  public $unlinkFile = false;
  public $commerceML = '2.04';

  public function __construct()
  {

    if (preg_match('/Basic+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
      list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
    }

    $this->commerceML = '2.04';
    // Проверка версии 1с , для 11+ изменен алгоритм обработки
    if (strpos($_SERVER['HTTP_USER_AGENT'], '/11.') !== false) {
      $this->commerceML = '2.08';
    }

    if (!empty($files)) {
      if (!empty($filename)) {
        file_put_contents('data/' . $filename, $files, FILE_APPEND);
      }
      echo "success\n";
      MG::setOption(array(
        'option' => 'downtime1C',
        'value' => 'false'
      ));
    }

    if (empty($_GET['mode'])) {
      MG::redirect('/');
    }

    if (MG::getSetting('closeSite') == 'true')
      MG::setOption(array(
        'option' => 'downtime1C',
        'value' => 'true'
      ));

    MG::disableTemplate();
    Storage::$noCache = true;
    $this->unlinkFile = true;
    $this->startTime = microtime(true);
    $this->maxExecTime = min(30, @ini_get("max_execution_time"));
    if (empty($this->maxExecTime)) {
      $this->maxExecTime = 30;
    }

    $fromPlugin = (empty($_SESSION['CML_PLUGIN_ACTIVE'])) ? false : true;

    $mode = (string)$_GET['mode'];
    $this->mode = $mode;
    $this->type = $_GET['type'];
    $this->filename = $_GET['filename'];
    if (isset($fromPlugin) && !$fromPlugin) {
	  if(!empty($_SERVER['PHP_AUTH_USER'])&&!empty($_SERVER['PHP_AUTH_PW'])){
        $this->auth = USER::auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
      } 
	}

    self::setDate();

    if (!$this->auth && !$fromPlugin) {
      self::logProcess("=================[Аутентификация не была пройдена!]===============");
      if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        self::logProcess("User: " . $_SERVER['PHP_AUTH_USER'] . " Pass: " . $_SERVER['PHP_AUTH_PW']);
      }
      self::logProcess("==================================================================");
    }

    $allowedModes = [
      'checkauth',
      'init',
      'file',
      'import',
      'query',
      'success',
      'applyWholesales',
    ];

    if ($mode === 'complete') {
      $mode = 'success';
      $this->mode = $mode;
    }

    if (!in_array($mode, $allowedModes)) {
      $mode = null;
      self::logProcess("=================[Неизвестный тип операции (mode)!]===============");
      self::logProcess("Mode: ".$this->mode);
      self::logProcess("==================================================================");
    }

    $generalLog = "Запрос \"" . self::modeToHuman($mode) . "\": mode = " . $this->mode . ", type = " . $this->type . ", filename = " . $this->filename;
    self::logProcess("==================================================================");
    self::logProcess($generalLog);

    if ($this->mode != "success") {
      self::logGeneralProcess($generalLog, "QUERY");
    } else {
      self::logGeneralProcess($generalLog, "QUERY");
      unset($_SESSION['dateDay1C']);
      unset($_SESSION['dateTime1C']);
    }

    if ($mode && $this->auth || $mode && $fromPlugin || $mode == "applyWholesales") {
      $this->$mode();
    }
  }

  /**
   * 1 шаг - авторизация 1с клиента.
   */
  public function checkauth()
  {
    self::setDate();
    unset($_SESSION['import1c']);
    echo "success\n";
    echo session_name() . "\n";
    echo session_id() . "\n";
    exit;
  }

  /**
   * Выгрузка заказов: exchange1c?type=sale&mode=success
   */
  public function success()
  {
    echo "success\n";
    echo session_name() . "\n";
    echo session_id() . "\n";
    MG::setOption(array(
      'option' => 'downtime1C',
      'value' => 'false'
    ));
    MG::createHook(__CLASS__."_".__FUNCTION__);
    exit;
  }

  /**
   * 2 шаг - сообщаем в 1с клиент о поддержке работы с архивами.
   */
  public function init()
  {
    self::setDate();
    if(file_exists(TEMP_DIR.'log1c/tmp.txt')){
      unlink(TEMP_DIR.'log1c/tmp.txt'); //Очистка временного лога на всякий случай      
    }
    $zip         = extension_loaded('zip') ? "yes" : "no";
    $fileLimit1c = MG::getSetting('fileLimit1C');
		echo "zip=" . $zip . "\n";
    echo "file_limit=" . intval($fileLimit1c) . "\n";
    exit;
  }

  /**
   * Запрос заказов
   */
  public function query()
  {
    $limit = MG::getSetting('ordersPerTransfer1c');
    self::setDate();
    $orderModel        = new Models_Order();
    $ordersArr         = $orderModel->getOrder('`updata_date` > IFNULL(`1c_last_export`, 0)', $limit);
    $orderList				 = array(); //Для логера
    $listModifyOrderId = '0';
    $nXML              = '<?xml version="1.0" encoding="utf-8"?>
<КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="' . date('Y-m-d') . '">
';
    $xml               = new XMLWriter();

    $xml->openMemory();
    $xml->setIndent(true);
    foreach ($ordersArr as $order) {
      $orderList[] = $order['id'];

      $yurInfo = unserialize(stripslashes($order['yur_info']));
      foreach ($yurInfo as $key => $value) {
        $yurInfo[$key] = preg_replace('/(?:&quot;([^>]*)&quot;)(?!>)/', '«$1»', $value);
        $yurInfo[$key] = htmlspecialchars_decode($yurInfo[$key]);
      }


      $xml->startElement("Документ");
      $xml->writeElement("Ид", $order['number']);
      $listModifyOrderId .= ',' . $order['id'];
      $xml->writeElement("Номер", $order['number']);
      $xml->writeElement("Дата", date('Y-m-d', strtotime($order['add_date'])));
      $xml->writeElement("ХозОперация", 'Заказ товара');
      $xml->writeElement("Роль", 'Продавец');
      $xml->writeElement("Валюта", MG::getSetting('currencyShopIso'));
      $xml->writeElement("Курс", 1);
      $xml->writeElement("Сумма", $order['summ']);

      $xml->startElement("Контрагенты");
      $xml->startElement("Контрагент");
      $xml->writeElement("Ид", $order['user_email']);
      $xml->writeElement("Наименование", $yurInfo['nameyur'] ? $yurInfo['nameyur'] : $order['name_buyer']);
      $xml->writeElement("Роль", "Покупатель");
      $xml->writeElement("ПолноеНаименование", $yurInfo['nameyur'] ? $yurInfo['nameyur'] : $order['name_buyer']);

      if (!empty($yurInfo['inn'])) {
        $xml->writeElement("ОфициальноеНаименование", $yurInfo['nameyur'] ? $yurInfo['nameyur'] : $order['name_buyer']);
        $xml->startElement("ЮридическийАдрес");
        $xml->writeElement("Представление", $yurInfo['adress']);

        /*
        $xml->startElement("АдресноеПоле");
        $xml->writeElement("Тип", 'Город');
        $xml->writeElement("Значение", '123');
        $xml->endElement(); //АдресноеПоле

        $xml->startElement("АдресноеПоле");
        $xml->writeElement("Тип", 'Улица');
        $xml->writeElement("Значение", '123');
        $xml->endElement(); //АдресноеПоле
       */
        $xml->endElement(); //ЮридическийАдрес

        $xml->startElement("ФактическийАдрес");
        $xml->writeElement("Представление", $yurInfo['adress']);
        /* $xml->startElement("АдресноеПоле");
            $xml->writeElement("Тип", 'Почтовый индекс');
            $xml->writeElement("Значение", '123');
          $xml->endElement();
        $xml->startElement("АдресноеПоле");
            $xml->writeElement("Тип", 'Город');
            $xml->writeElement("Значение", '123');
          $xml->endElement();
        $xml->startElement("АдресноеПоле");
            $xml->writeElement("Тип", 'Улица');
            $xml->writeElement("Значение", '123');
          $xml->endElement();
           */
        $xml->endElement(); //ФактическийАдрес
        $xml->writeElement("ИНН", $yurInfo['inn']);
        $xml->writeElement("КПП", $yurInfo['kpp']);

        $xml->startElement("РасчетныеСчета");
        $xml->startElement("РасчетныйСчет");
        $xml->writeElement("НомерСчета", $yurInfo['rs']);
        $xml->startElement("Банк");
        $xml->writeElement("БИК", $yurInfo['bik']);
        /*
              $xml->startElement("Адрес");
              $xml->writeElement("Представление", "123");
              $xml->startElement("АдресноеПоле");
                $xml->writeElement("Тип", 'Почтовый индекс');
                $xml->writeElement("Значение", '123');
              $xml->endElement();
              $xml->startElement("АдресноеПоле");
                $xml->writeElement("Тип", 'Город');
                $xml->writeElement("Значение", '123');
              $xml->endElement();

              $xml->endElement();
          */
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
      }


      $xml->writeElement("Имя", $order['name_buyer']);
      $xml->startElement("АдресРегистрации");
      $xml->writeElement("Представление", $order['address']);
      $xml->startElement("АдресноеПоле");
      $xml->writeElement("Тип", 'Страна');
      $xml->writeElement("Значение", 'RU');
      $xml->endElement(); //АдресноеПоле
      $xml->endElement(); //АдресРегистрации

      $xml->startElement("Контакты");
      $xml->startElement("Контакт");
      $xml->writeElement("Тип", 'ТелефонРабочий');
      $xml->writeElement("Значение", $order['phone']);
      $xml->endElement(); //Контакт
      $xml->startElement("Контакт");
      $xml->writeElement("Тип", 'Почта');
      $xml->writeElement("Значение", $order['user_email']);
      $xml->endElement(); //Контакт
      $xml->endElement(); //Контакты


      $xml->endElement(); //Контрагент
      $xml->endElement(); //Контрагенты
      $xml->writeElement("Время", date('H:i:s', strtotime($order['add_date'])));

      $xml->startElement("Товары");


      //Информация о доставке

      if ($order['delivery_cost'] > 0) {
        $xml->startElement("Товар");
        $xml->writeElement("Ид", 'ORDER_DELIVERY');
        $xml->writeElement("Наименование", "Доставка ".$order['description']);  //Описание доставки при выгрузке заказов
        $xml->writeElement("БазоваяЕдиница", "шт");
        $xml->writeAttribute("Код", "796");
        $xml->writeAttribute("НаименованиеПолное", "Штука");
        $xml->writeAttribute("МеждународноеСокращение", "PCE");

        $xml->writeElement("ЦенаЗаЕдиницу", $order['delivery_cost']);
        $xml->writeElement("Количество", 1);
        $xml->writeElement("Сумма", $order['delivery_cost']);

        $xml->startElement("ЗначенияРеквизитов");
        $xml->startElement("ЗначениеРеквизита");
        $xml->writeElement("Наименование", 'ВидНоменклатуры');
        $xml->writeElement("Значение", 'Услуга');
        $xml->endElement(); //ЗначениеРеквизита
        $xml->startElement("ЗначениеРеквизита");
        $xml->writeElement("Наименование", 'ТипНоменклатуры');
        $xml->writeElement("Значение", 'Услуга');
        $xml->endElement(); //ЗначениеРеквизита
        $xml->endElement(); //ЗначенияРеквизитов
        $xml->endElement(); //Товар
      }
      //Конец - Информация о доставке

      $products = unserialize(stripslashes($order['order_content']));
      foreach ($products as $product) {
        $xml->startElement("Товар");
        $extendId = $product['id'];
        $extend1cId = !empty($product['1c_id'])?$product['1c_id']:null;

        // if ($extend1cId != null) {
        // 	$extendId = $extend1cId;
        // } else {
        if (!empty($product['variant_id']) || !empty($product['variant'])) {
          $product['variant_id'] = $product['variant_id'] ? $product['variant_id'] : $product['variant'];
          $extendId .= 'V' . $product['variant_id'];
        }

        if (!empty($product['variant_id'])) {
          $sql = "
							SELECT CONCAT_WS('#', p.`1c_id`, pv.`1c_id`) as 1c_id 
							FROM `" . PREFIX . "product_variant` pv
								LEFT JOIN `" . PREFIX . "product` p
								ON p.id = pv.product_id
							WHERE pv.`id` = " . DB::quote($product['variant_id']) . " 
								AND pv.`product_id` = " . DB::quote($product['id']);
        } else {
          $sql = "
							SELECT `1c_id`
							FROM  `" . PREFIX . "product`
							WHERE `id` = " . DB::quote($product['id']) . "
							";
        }

        $res = DB::query($sql);

        if ($row = DB::fetchAssoc($res)) {
          if ($row['1c_id']) {
            $extendId = $row['1c_id'];
          }
        }
        // }

        $xml->writeElement("Ид", $extendId);
        $xml->writeElement("Наименование", htmlspecialchars_decode($product['name']));
        $xml->writeElement("ЦенаЗаЕдиницу", $product['price']);
        $xml->writeElement("БазоваяЕдиница", "шт");
        $xml->writeElement("Артикул", $product['code']);
        $xml->writeElement("Код", $product['code']);

        $xml->writeElement("Количество", $product['count']);
        $xml->writeElement("Сумма", $product['price'] * $product['count']);
        $xml->startElement("ЗначенияРеквизитов");
        $xml->startElement("ЗначениеРеквизита");
        $xml->writeElement("Наименование", 'ВидНоменклатуры');
        $xml->writeElement("Значение", 'Товар');
        $xml->endElement(); //ЗначениеРеквизита
        $xml->startElement("ЗначениеРеквизита");
        $xml->writeElement("Наименование", 'ТипНоменклатуры');
        $xml->writeElement("Значение", 'Товар');
        $xml->endElement(); //ЗначениеРеквизита
        $xml->endElement(); //ЗначенияРеквизитов

        $xml->endElement(); //Товар
      }

      $xml->endElement(); //Товары

      $xml->startElement("ЗначенияРеквизитов");

      $arrayStatus = unserialize(stripslashes(MG::getSetting('listMatch1C')));
      // $arrayStatus = array(
      // 	1 => 'Подтвержден',
      // 	2 => 'Собран',
      // 	6 => 'Собран',
      // 	3 => 'Отгружен',
      // 	4 => 'Отменен',
      // 	5 => '[F] Доставлен',
      // 	0 => '[N] Принят'
      // );

      if ($order['status_id'] || $order['status_id'] == "0") {
        $xml->startElement("ЗначениеРеквизита");

        $xml->writeElement("Наименование", 'Статус заказа');
        $xml->writeElement("Значение", $arrayStatus[$order['status_id']]);

        $xml->endElement(); //ЗначениеРеквизита

        if ($order['status_id'] == 4) {
          $xml->startElement("ЗначениеРеквизита");
          $xml->writeElement("Наименование", 'Отменен');
          $xml->writeElement("Значение", 'true');
          $xml->endElement();
        }
      }

      $xml->endElement(); //ЗначенияРеквизитов
      $xml->endElement(); // Документ
    }

    $nXML .= $xml->outputMemory();
    //$nXML = mb_convert_encoding($nXML, "WINDOWS-1251", "UTF-8");
    $nXML .= '</КоммерческаяИнформация>';


    if ($listModifyOrderId != '0') {
      DB::query('UPDATE ' . PREFIX . 'order SET `1c_last_export` = now() WHERE id IN(' . DB::quote($listModifyOrderId, 1) . ')');
    }

    self::logProcess('Экспортируемых заказов ' . count($orderList) . ' шт.');
    self::logProcess('Список экспортируемых заказов:');
    self::logProcess($orderList);

    self::logGeneralProcess('Выгружено заказов: ' . count($orderList) . ' шт.', "SYNC");

    //WRITE_MODE
    self::saveFileExport($nXML);

    header("Content-type: text/xml; charset=utf-8");
    echo "\xEF\xBB\xBF";
    echo $nXML;
  }

  /**
   * Обновление заказов
   * @param string $filename
   */
  public function ordersUpdate($filename)
  {

    // вычисляем какой из имеющихся файлов в папке обмена относится к заказам.
    $sep              = DS;
    $dirname          = dirname(__FILE__);
    // $realDocumentRoot = str_replace($sep . 'mg-core' . $sep . 'controllers', '', $dirname);
    $realDocumentRoot = URL::getDocumentRoot(false);
    $files            = scandir($realDocumentRoot . DS . TEMP_DIR . 'tempcml/');

    foreach ($files as $name) {
      $tmp = explode(".", $name);
      if (end($tmp) == 'xml' && (stristr($name, 'import') === FALSE) && (stristr($name, 'offers') === FALSE)) {
        $filename = $name;
      }
    }

    $orderModel  = new Models_Order();
    $arrayStatus = unserialize(stripslashes(MG::getSetting('listMatch1C')));
    $arrayStatus = array_flip($arrayStatus);
    // $arrayStatus = array(
    // 	'Новый' => 0,
    // 	'Подтвержден' => 1,
    // 	'Собран' => 6,
    // 	'Отгружен' => 3,
    // 	'Доставлен' => 5,
    // 	'Возврат' => 5,
    // 	'Отменен' => 4,
    // 	'[F] Доставлен' => 5,
    // 	'[N] Принят' => 0
    // );
    //WRITE_MODE
    self::saveFileImport($filename);

    self::logProcess('Обновление заказов из вне:');
    $leaveOrderStatuses = MG::getSetting('leaveOrderStatuses') == 'true' ? true : false;
    if ($leaveOrderStatuses) {
      self::logProcess('Обновление статусов отключено');
      return true;
    }

    $xmlReader = new XMLReader();
    $xmlOpenResult = $xmlReader->open(TEMP_DIR . 'tempcml/' . $filename);
    if (!$xmlOpenResult) {
      self::logProcess('Ошибка! Не удалось считать файл ' . $filename);
      self::logGeneralProcess('Ошибка! Во время обновления заказов произошла ошибка! Статусы заказов не обновлены!', "SYNC");
      if ($xmlReader) {
          $xmlReader->close();
        }
      unset($xmlReader);
      return false;
    }

    $orderList = array(); //Для логера

    while($xmlReader->read()) {
      if (
        $xmlReader->nodeType === XMLReader::ELEMENT &&
        $xmlReader->localName === 'Документ'
      ) {
        $order = simplexml_load_string($xmlReader->readOuterXML());

        $orderId       = 0;
        $orderNumber   = strval($order->Номер);
        $orderStatusId = null;
        $oldStatus = null;

        foreach ($order->ЗначенияРеквизитов->ЗначениеРеквизита as $item) {
          //if ($item->Наименование == "Номер по 1С") {
          //  $orderNumber = $item->Значение;
          //}
          $currentStatusId = null;
          $res = DB::query("SELECT id, status_id FROM " . PREFIX . "order WHERE number = " . DB::quote($orderNumber));
  
          if ($row = DB::fetchAssoc($res)) {
            $orderId = $row['id'];
            $currentStatusId = $row['status_id'];
            $oldStatus = intval($currentStatusId);
          }
  
          if ($item->Наименование == "Дата оплаты по 1С" && !empty($item->Значение)) {
            $orderStatusId = 2;
          }
  
          if ($item->Наименование == "Статус заказа") {
            $orderStatus   = $item->Значение;
            $orderList[$orderNumber] = 'Изменен статус на ' . $orderStatus . ' '; //Для логера
            $orderStatusId = $arrayStatus[(string)$orderStatus];
          }
          //if ($item->Наименование == "Проведен") {
          //  $passed = $item->Значение == "true"?1:0;
          //}
          if ($item->Наименование == "ПометкаУдаления") {
            $delete = ($item->Значение == "true") ? 1 : 0;
            if ($delete) {
              $orderList[$orderNumber] .= 'Помечен на удаление'; //Для логера
              $orderModel->deleteOrder($orderId);
            }
          }
        }
  
        if (empty($orderId) || !isset($orderStatusId) || $orderStatusId == $currentStatusId) {
          continue;
        }
  
        //echo "<br>".$orderId.'['.$orderNumber.']['.$orderId1c.']='.$orderStatus.'['.$orderStatusId.']';
  
        if (!$leaveOrderStatuses) {
          $arrayOrder = array(
            'id' => $orderId,
            'status_id' => $orderStatusId
          );
  
          $sendMail = isset($oldStatus)
            && intval($orderStatusId) !== $oldStatus 
            && MG::getSetting('statusChangeMail1c') === 'true'; 
          $orderModel->updateOrder($arrayOrder, $sendMail);
          
        }

      }
    }
    self::logProcess('Всего заказов обновлено: ' . count($orderList));
    self::logProcess($orderList);

    self::logGeneralProcess('Изменено заказов: ' . count($orderList) . ' шт.', "SYNC");

    unlink($realDocumentRoot  . DS . TEMP_DIR . 'tempcml/' . $filename);
    $upload = new Upload(false);
    $upload->removeDirectory($realDocumentRoot  . DS . TEMP_DIR . 'tempcml/');
    if ($xmlReader) {
          $xmlReader->close();
        }
    unset($xmlReader);
  }

  /**
   * 3 шаг - сохраняем файл выгрузки полученный из 1с.
   */
  public function file()
  {
    $filename = $this->filename;

    if (isset($filename) && ($filename) > 0) {
      $filename = trim(str_replace("\\", "/", trim($filename)), "/");
    }

    if (function_exists("file_get_contents")) {
      $tmp = explode(".", $filename);
      if (end($tmp) == 'zip') {
        
        mg::createTempDir('tempcml');
	      $data = file_get_contents("php://input");
        file_put_contents(TEMP_DIR.$filename, $data, FILE_APPEND);
        $_SESSION['fileName'] = $filename;

        if ($this->type == "sale") {
          $this->extractZip($filename);
          $this->ordersUpdate($filename);
        }

        echo "success\n";
      } else {
        $data             = file_get_contents("php://input");
        $realDocumentRoot = URL::getDocumentRoot(false);
        chdir($realDocumentRoot);

        $tempDir = mg::createTempDir('tempcml');
        $filePath = $tempDir.$filename;
        $fileDir = dirname($filePath);
        mkdir($fileDir, 0777, true);
        file_put_contents($filePath, $data, FILE_APPEND);

        if ($this->type == "sale") {
            $this->ordersUpdate($filename);
        }

        echo "success\n";
      }
    } else {
      echo "failure\n";
      echo "Error of get data!\n";
    }

    exit;
  }

  /**
   * Получение файлов из архива
   * @param string $filename путь к файлу архива
   */
  private function getFilesFromZip($filename)
  {
    if (end(explode(".", $filename)) == 'zip') {
      if ($this->extractZip($filename)) {

        if ($this->type == "catalog") {
          $_SESSION['lastCountOffer1cImport']   = 0;
          $_SESSION['lastCountProduct1cImport'] = 0;
          unset($_SESSION['fileName']);
        }
      } else {
        echo "failure\n";
        echo "Error unzip data!\n";
        exit();
      }
    }
  }

  /**
   * 4 шаг - запуск процесса импорта файла выгрузки.
   */
  public function import()
  {
    self::setDate();
    if (isset($_SESSION['fileName'])) {
        if ($this->type !== 'sale') {
          $this->getFilesFromZip($_SESSION['fileName']);
        }
    }
    $log = '';
    if ($this->type !== 'sale') {
      $log = $this->processImportXml($this->filename);
    }
    echo "success\n";
    echo session_name() . "\n";
    echo session_id() . "\n";
    echo $log;
    exit;
  }

  /**
   * 5 шаг - распаковывает архив с данными по выгрузкам заказов и товаров.
   * @param string $file - путь к файлу архива с данными.
   * @return bool
   */
  public function extractZip($file)
  {

    if (file_exists(TEMP_DIR.$file)) {
      $zip = new ZipArchive;
      $res = $zip->open(TEMP_DIR.$file, ZIPARCHIVE::CREATE);

      if ($res === true) {
        $sep              = DS;
        $dirname          = dirname(__FILE__);
        // $realDocumentRoot = str_replace($sep . 'mg-core' . $sep . 'controllers', '', $dirname);
        $realDocumentRoot = URL::getDocumentRoot(false);
        $zip->extractTo($realDocumentRoot .DS. TEMP_DIR . 'tempcml/' );
        $zip->close();
        unlink(TEMP_DIR.$file);
        return true;
      } else {
        return false;
      }
    }
    return false;
  }

  private function parseStorages($xmlFullPath) {
    $xmlReader = new XMLReader();
    $xmlOpenResult = $xmlReader->open($xmlFullPath);
    if (!$xmlOpenResult) {
      return false;
    }

    while($xmlReader->read()) {
      if (
        $xmlReader->nodeType === XMLReader::ELEMENT &&
        $xmlReader->localName === 'Склады'
      ) {
        $storagesXml = simplexml_load_string($xmlReader->readOuterXML());
        if ($xmlReader) {
          $xmlReader->close();
        }
        unset($xmlReader);
        return $storagesXml;
      }
      if (
        $xmlReader->nodeType === XMLReader::END_ELEMENT &&
        $xmlReader->localName === 'Склады'
      ) {
        return '';
      }
    }
    if ($xmlReader) {
          $xmlReader->close();
        }
    unset($xmlReader);
    return '';
  }

  private function parsePriceTypes($xmlFullPath) {
    $xmlReader = new XMLReader();
    $xmlOpenResult = $xmlReader->open($xmlFullPath);
    if (!$xmlOpenResult) {
      return false;
    }

    while($xmlReader->read()) {
      if (
        $xmlReader->nodeType === XMLReader::ELEMENT &&
        $xmlReader->localName === 'ТипыЦен'
      ) {
        $priceTypesXml = simplexml_load_string($xmlReader->readOuterXML());
        if ($xmlReader) {
          $xmlReader->close();
        }
        unset($xmlReader);
        return $priceTypesXml;
      }
      if (
        $xmlReader->nodeType === XMLReader::END_ELEMENT &&
        $xmlReader->localName === 'ТипыЦен'
      ) {
        return '';
      }
    }
    if ($xmlReader) {
          $xmlReader->close();
        }
    unset($xmlReader);
    return '';
  }

  private function parseCategory($xmlFullPath) {
    $xmlReader = new XMLReader();
    $xmlOpenResult = $xmlReader->open($xmlFullPath);
    if (!$xmlOpenResult) {
      return false;
    }

    while($xmlReader->read()) {
      if (
        $xmlReader->nodeType === XMLReader::ELEMENT &&
        $xmlReader->localName === 'Классификатор'
      ) {
        while($xmlReader->read()) {
          if (
            $xmlReader->nodeType === XMLReader::END_ELEMENT &&
            $xmlReader->localName === 'Классификатор'
          ) {
            return '';
          }
          if (
            $xmlReader->nodeType === XMLReader::ELEMENT &&
            $xmlReader->localName === 'Группы'
          ) {
            $catsXml = simplexml_load_string('<document>'.($xmlReader->readOuterXML()).'</document>');
            if ($xmlReader) {
          $xmlReader->close();
        }
            unset($xmlReader);
            return $catsXml;
          }
        }
        break;
      }
    }
    if ($xmlReader) {
          $xmlReader->close();
        }
    unset($xmlReader);
    return '';
  }

  private function parseProperties($xmlFullPath) {
    $xmlReader = new XMLReader();
    $xmlOpenResult = $xmlReader->open($xmlFullPath);
    if (!$xmlOpenResult) {
      return false;
    }

    while($xmlReader->read()) {
      if (
        $xmlReader->nodeType === XMLReader::ELEMENT &&
        $xmlReader->localName === 'Классификатор'
      ) {
        while($xmlReader->read()) {
          if (
            $xmlReader->nodeType === XMLReader::END_ELEMENT &&
            $xmlReader->localName === 'Классификатор'
          ) {
            return '';
          }
          if (
            $xmlReader->nodeType === XMLReader::ELEMENT &&
            $xmlReader->localName === 'Свойства'
          ) {
            $propertiesXml = simplexml_load_string($xmlReader->readOuterXML());
            if ($xmlReader) {
          $xmlReader->close();
        }
            unset($xmlReader);
            return $propertiesXml;
          }
        }
        break;
      }
    }
    if ($xmlReader) {
          $xmlReader->close();
        }
    unset($xmlReader);
    return '';
  }

  /**
   * Парсинг XML и импорт в БД товаров.
   * @param string $filename - путь к файлу архива с данными.
   * @return string|void
   */
  public function processImportXml($filename)
  {
    //логирование импорта
    $data['id'] = '';
    LoggerAction::logAction('1C', __FUNCTION__, $data);
    /*
    if ($this->commerceML != '2.04' && $filename == 'import.xml') {
      $filename = 'import0_1.xml';
    }

    if ($this->commerceML != '2.04' && $filename == 'offers.xml') {
      $filename = 'offers0_1.xml';
    }*/

    $resizeType = MG::getSetting("imageResizeType");

    if (empty($resizeType)) {
      $resizeType = 'PROPORTIONAL';
    }

    $widthPreview       = MG::getSetting('widthPreview') ? MG::getSetting('widthPreview') : 200;
    $widthSmallPreview  = MG::getSetting('widthSmallPreview') ? MG::getSetting('widthSmallPreview') : 50;
    $heightPreview      = MG::getSetting('heightPreview') ? MG::getSetting('heightPreview') : 100;
    $heightSmallPreview = MG::getSetting('heightSmallPreview') ? MG::getSetting('heightSmallPreview') : 50;

    $importOnlyNew    = false;
    $sep              = DS;
    $dirname          = dirname(__FILE__);
    // $realDocumentRoot = str_replace($sep . 'mg-core' . $sep . 'controllers', '', $dirname);
    $realDocumentRoot = URL::getDocumentRoot(false);
    $upload           = new Upload(false);

    $lastPositionProduct = $_SESSION['lastCountProduct1cImport'];
    $lastPositionOffer   = $_SESSION['lastCountOffer1cImport'];

    $xmlFullPath = SITE_DIR . DS . TEMP_DIR . 'tempcml' . DS . $filename;
    $log = "";

    //Массив для преобразованя единиц измерения
    $unitArray = explode(',', MG::getSetting('1c_unit_item'));
    foreach ($unitArray as &$value) {
      $value = explode(':', $value);
    }

    //UPDATE_MODE
    $updateSetting = unserialize(stripslashes(MG::getSetting('notUpdate1C')));

    // После того как созданы харатеристики из 1с-свойств, получаем внутренний
    // 1c_id для характеристики Вес. По указанному названию в настройказ 1с, на стороне CMS.
    // Например: 'Вес брутто, кг'
    // ля того чтобы записать значения характеристики в системное поле Вес, для дальнейшего расчета в плагинах доставки.

    $weightUnit1C = MG::getSetting('weightUnit1C');
    $weightPropertyName = MG::getSetting('weightPropertyName1c') ? MG::getSetting('weightPropertyName1c') : 'Вес';
    $weightProperty1cId = 'none';
    $res                = DB::query('SELECT 1c_id 
										FROM `' . PREFIX . 'property` 
										WHERE `name`=' . DB::quote($weightPropertyName) . ' and 1c_id <> "" ');
    if ($row = DB::fetchAssoc($res)) {
      $weightProperty1cId = $row['1c_id'];
    }

    $needWholesales = false;

    if (is_file($xmlFullPath) && (stristr($filename, 'import') !== FALSE)) { //IMPORT SECTION
      self::saveFileImport($filename);
      self::logProcess('Импорт товара из вне:');

      $totalProducts = 0;
      $xmlReader = new XMLReader();
      $xmlReader->open($xmlFullPath);
      $productsElementStart = false;
      while (
        $xmlReader->read()
      ) {
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'Каталог'
        ) {
          if ($xmlReader->getAttribute('СодержитТолькоИзменения') === 'true') {
            $importOnlyNew = true;
          }
        }
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'Товары'
        ) {
          $productsElementStart = true;
        }
        if (
          $xmlReader->nodeType === XMLReader::END_ELEMENT &&
          $xmlReader->localName === 'Товары'
        ) {
          $productsElementStart = false;
        }
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'Товар' &&
          $productsElementStart
        ) {
          ++$totalProducts;
        }
      }
      if ($xmlReader) {
          $xmlReader->close();
        }
      unset($xmlReader);

      if (empty($lastPositionProduct) && $importOnlyNew == false) {
        $clearCatalog1c = MG::getSetting('clearCatalog1C');
        if ($clearCatalog1c && $clearCatalog1c != "false") {
          self::logProcess('Очистка каталога перед загрузкой');
          $productsModel = new Models_Product();
          DB::query('TRUNCATE TABLE ' . PREFIX . 'product');
          if (empty($_SESSION['import1c']['category'])) DB::query('TRUNCATE TABLE ' . PREFIX . 'category');
          DB::query('TRUNCATE TABLE ' . PREFIX . 'product_variant');
          $productsModel->clearStoragesTable();
          DB::query('TRUNCATE TABLE ' . PREFIX . 'product_user_property_data');
          DB::query('TRUNCATE TABLE ' . PREFIX . 'wholesales_sys');
          DB::query('DELETE FROM ' . PREFIX . 'property WHERE 1c_id != \'\'');
          DB::query('DELETE FROM ' . PREFIX . 'locales WHERE `table` IN (\'product\', \'product_variant\', \'category\', \'product_user_property_data\')');

          MG::rrmdir(URL::getDocumentRoot() . DS . 'uploads' . DS . 'product');
          MG::rrmdir(URL::getDocumentRoot() . DS . 'uploads' . DS . 'category');
          MG::rrmdir(URL::getDocumentRoot() . DS . 'uploads' . DS . 'webp' . DS .'product');
          MG::rrmdir(URL::getDocumentRoot() . DS . 'uploads' . DS . 'webp' . DS .'category');
          @mkdir('product', 0755);
        }
      }


      if (!isset($category)) {
        $category = array();
      }

      $propertiesXml = $this->parseProperties($xmlFullPath);

      if (
        !empty($propertiesXml) &&
        file_exists('uploads/variants1C.txt') &&
        is_writable('uploads') &&
        (stristr($filename, 'import') !== FALSE)
      ) {
        unlink('uploads/variants1C.txt');
      }

      if (empty($_SESSION['import1c']['createProduct'])) {
        $categoryXml = $this->parseCategory($xmlFullPath);
        $rootCategory = [
          'category_id' => 0,
          'name' => '',
        ];
        $category = $this->groupsGreate($categoryXml, $category, $rootCategory);
        $this->propertyСreate($propertiesXml);
      }


      // Берем 1c_id для кратности из БД
      $multiplicityPropertyName = MG::getSetting('multiplicityPropertyName1c') ? MG::getSetting('multiplicityPropertyName1c') : 'Кратность';
      $multiplicityProperty1cId = 'none';
      $res = DB::query('SELECT 1c_id FROM `' . PREFIX . 'property` WHERE `name`=' . DB::quote($multiplicityPropertyName) . ' and 1c_id <> "" ');
      if ($row = DB::fetchAssoc($res)) {
        $multiplicityProperty1cId = $row['1c_id'];
      }

      $opFields = array();

      $op1c = unserialize(stripslashes(MG::getSetting('op1c')));
      $op1cPrice = unserialize(stripslashes(MG::getSetting('op1cPrice')));
      if (!empty($op1c)) {
        $op1cKeys = array_flip($op1c);
      } else {
        $op1c = [];
        $op1cKeys = [];
      }

      $_SESSION['import1c']['createProduct'] = true;

      $res = DB::query('SELECT c.1c_id, c.title AS name, c.id AS category_id, c.parent AS parent_id, c.html_content AS description, (SELECT title FROM ' . PREFIX . 'category AS ic WHERE ic.id = c.id) AS parentname	FROM ' . PREFIX . 'category AS c');
      while ($row = DB::fetchAssoc($res)) {
        $category[$row['1c_id']] = $row;
      }

      $model           = new Models_Product;
      $currentPosition = 0;

      $log = "";
      $countProduct = 0;
      $countImg = 0;

      self::logProcess('Количество импортируемых товаров: ' . $totalProducts);
      self::logGeneralProcess("Загружено товаров: " . $totalProducts . " шт.", "SYNC");
      //self::logGeneralProcess('Количество присланных товаров: '.$totalProducts);

      $xmlReader = new XMLReader();
      $xmlReader->open($xmlFullPath);
      $productsElementStart = false;
      while ($xmlReader->read()) {
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'Товары'
        ) {
          $productsElementStart = true;
        }
        if (
          $xmlReader->nodeType === XMLReader::END_ELEMENT &&
          $xmlReader->localName === 'Товары'
        ) {
          $productsElementStart = false;
          if ($xmlReader) {
          $xmlReader->close();
        }
          unset($xmlReader);
          break;
        }
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'Товар' &&
          $productsElementStart
        ) {
          $productXml = simplexml_load_string($xmlReader->readOuterXml());




          $currentPosition++;
          if ($currentPosition <= $lastPositionProduct) {
            continue;
          }

          // Добавляем изображение товара в папку uploads
          $imageUrl    = array();
          $realImgPath = array();
          if (isset($productXml->Картинка)) {
            foreach ($productXml->Картинка as $img) {
              $path          = TEMP_DIR . 'tempcml/'  . $sep . $img;
              $realImgPath[] = $path;
              $image         = basename($img);
              $imageUrl[]    = $image;
            }
          }
          //Подтягивание единиц измерения
          $unitsXml = $productXml->БазоваяЕдиница;
          $unitToBD = 'шт.';
          if ($unitsXml) {
            $unitTem = trim((string)$unitsXml->attributes()->НаименованиеПолное);
            foreach ($unitArray as $unit) {
              if ($unit[0] == $unitTem) {
                $unitToBD = $unit[1];
              }
            }
          }


          $imageUrl    = implode("|", $imageUrl);
          $id          = (string)$productXml->Группы->Ид[0];
          $name        = (string)$productXml->Наименование[0];
          $description = '';
          $productId   = 0;
          $desExist    = false;
          if (isset($productXml->Описание)) {
            $description = MG::nl2br((string)$productXml->Описание[0]);
            $desExist    = true;
          }

          foreach ($productXml->ЗначенияРеквизитов->ЗначениеРеквизита as $row) {
            if ($row->Наименование == 'Полное наименование') {

              // если в файле нет специального тега с описанием, то берем из полного наименования
              if (!$desExist) {
                $description = (string)$row->Значение ? (string)$row->Значение : $description;
                $description = MG::nl2br($description);
              }
              if (MG::getSetting('writeFullName1C') == "true") {
                $name = (string)$row->Значение ? (string)$row->Значение : $name;
              }
            }

            // Выгрузка габаритов из нестандартной карточки товара 1С
            if ($row->Наименование == 'Вес') {
              $weight = (string)$row->Значение;
            }
            if ($row->Наименование == 'Длина') {
              $length = (string)$row->Значение;
            }
            if ($row->Наименование == 'Ширина') {
              $width = (string)$row->Значение;
            }
            if ($row->Наименование == 'Высота') {
              $height = (string)$row->Значение;
            }
          }
          unset($weight);
          $code = !empty($productXml->Артикул[0]) ? str_replace(' ', '', $productXml->Артикул[0]) : $productXml->ШтрихКод[0];
          $code = str_replace(array(',', '|', '"', "'"), '', $code);
          if (empty($weight)) {
            $weight     = !empty($productXml->Вес[0]) ? $productXml->Вес[0] : 0;
          }
          $multiplicity = 1; // поумолчанию кратность равно 1 у всех товаров
          $newProduct = false;
          $id_1c      = (string)$productXml->Ид[0];
          $ids1c      = explode("#", $id_1c);

          // Привязываем свойство веса к системному полю "вес". А также кратность товара.
          // $weight = '';
          if (isset($productXml->ЗначенияСвойств)) {
            foreach ($productXml->ЗначенияСвойств->ЗначенияСвойства as $prop) {
              $propId  = '' . $prop->Ид[0];
              $propVal = '' . $prop->Значение[0];
              if ($propId == $weightProperty1cId) {
                $weight = $propVal;
              }
              if ($propId == $multiplicityProperty1cId) {
                //Проверяем, что у нас в значении (сама кратность или ID варианта кратности из свойств)
                if (is_numeric(str_replace(',', '.', $propVal))) {
                  $multiplicity = $propVal;
                } else {
                  // Здесь перебираем свойства, ищем свойство с кратностью перебираем его значение и находим нужное нам, сопоставив ID
                  foreach ($propertiesXml as $value) {
                    if ((string)$value->Ид[0] == $multiplicityProperty1cId && (string)$value->ТипЗначений == 'Справочник') {
                      foreach ($value->ВариантыЗначений->Справочник as $multiplicityVar) {
                        if ((string)$multiplicityVar->ИдЗначения[0] == $propVal) {
                          $multiplicity = (string)$multiplicityVar->Значение[0];
                        }
                      }
                    }
                  }
                }
              }
            }
          }
          // Если установлено соответствие дополнительных полей из свойст, то ищем свойства из которых требуется записать значения в дополнительные поля в CMS
          // Свойства у которых значения не заданы в файле import.xml затрутся для дополнительных полей в CMS!
          if (!empty($op1c)) {
            $opFields = array();
            $cachedVars = array();

            if (is_writable('uploads')) {
              $cachedVars = unserialize(file_get_contents('uploads/variants1C.txt'));
            }

            if (isset($productXml->ЗначенияСвойств)) {
              // перебираем все свойства чтобы найти, то которое нужно записать в доп поле
              foreach ($productXml->ЗначенияСвойств->ЗначенияСвойства as $prop) {
                $propVal  = '';
                $tempProp = '' . $prop->Значение[0];
                $idVal   = '' . $prop->Ид[0];
                $propName1c = '';

                //Поиск читаемого названия $propVal свойства по ID
                if (!empty($_SESSION['variant_value'][$tempProp])) {
                  $propVal = $_SESSION['variant_value'][$tempProp];
                } elseif (!empty($cachedVars[$tempProp])) {
                  $propVal = $cachedVars[$tempProp];
                } else {
                  if (!empty($tempProp)) {
                    $propVal = $tempProp;
                  }
                }

                if (empty($propVal)) {
                  if (!empty($_SESSION['variant_value'][$idVal])) {
                    $propVal = $_SESSION['variant_value'][$idVal];
                  } elseif (!empty($cachedVars[$idVal])) {
                    $propVal = $cachedVars[$idVal];
                  }
                }

                //Поиск названия свойства по ID
                foreach ($propertiesXml as $value) {
                  if ((string)$value->Ид[0] == $idVal) {
                    $propName1c = '' . $value->Наименование[0];
                  }
                }

                // Если в настройках испорта указано это свойство для загрузки в дополнительное поле то запоминаем значение
                if (isset($op1cKeys[$propName1c]) && $op1cKeys[$propName1c] === 'fromProp') {
                  $opFields['opf_' . $op1cKeys[$propName1c]] = $propVal;
                }
              }
            }
          }

          if (!empty($op1c)) {
            foreach ($op1cPrice as $op1cId => $op1cType) {
              if ($op1cType !== 'fromTag') {
                continue;
              }
              $tagName = $op1c[$op1cId];
              if (isset($productXml->$tagName)) {
                $op1cValue = (string) $productXml->$tagName;
                $opFields['opf_'.$op1cId] = $op1cValue;
              }
            }
          }

          $categoryId = !empty($category[$id]['category_id']) ? $category[$id]['category_id'] : null;
          if (
            !empty($_SESSION['import1c']['skippedRootCat']) &&
            $_SESSION['import1c']['skippedRootCat'] === $id
          ) {
            $categoryId = 0;
          }

          $id_1c    = $ids1c[0];
          $dataProd = array(
            'title' => $name,
            'url' => str_replace('\\', '-', URL::clean(URL::prepareUrl(MG::translitIt($name), true))),
            'code' => $code,
            'description' => $description,
            'image_url' => $imageUrl,
            'cat_id' => $categoryId,
            'activity' => 1,
            '1c_id' => $id_1c,
            'weight' => str_replace(',', '.', $weight),
            'unit' => $unitToBD,
            'multiplicity' => str_replace(',', '.', $multiplicity)
          );

          $dataProd += $opFields;

          // Выгрузка габаритов из нестандартной карточки товара 1С
          if (MG::getSetting('updateStringProp1C') == true) {
            if (!empty($length)) {
              $sql = DB::query("SELECT id FROM " . PREFIX . "property WHERE 1c_id = 1 AND name = 'Длина'");
              $lengthId = DB::fetchRow($sql);
              if (empty($lengthId)) {
                DB::query("INSERT INTO " . PREFIX . "property 
                                  (name, type, all_category, activity, filter, type_filter, 1c_id, group_id) VALUES 
                                  ('Длина', 'string', 1, 1, 1, 'checkbox', 1, 0)");
                $sql = DB::query("SELECT MAX(sort) FROM " . PREFIX . "property");
                $sort = DB::fetchRow($sql);
                if (!empty($sort)) {
                  $sort = $sort[0] + 1;
                }
                DB::query("UPDATE " . PREFIX . "property SET sort = " . DB::quoteInt($sort) . " WHERE 1c_id = 1 AND name = 'Длина'");
              }
              $this->propertyConnect($id_1c, 1, $length, $category[$id]['category_id']);
            }
            if (!empty($height)) {
              $sql = DB::query("SELECT id FROM " . PREFIX . "property WHERE 1c_id = 2 AND name = 'Высота'");
              $heightId = DB::fetchRow($sql);
              if (empty($heightId)) {
                DB::query("INSERT INTO " . PREFIX . "property 
                                  (name, type, all_category, activity, filter, type_filter, 1c_id, group_id) VALUES 
                                  ('Высота', 'string', 1, 1, 1, 'checkbox', 2, 0)");
                $sql = DB::query("SELECT MAX(sort) FROM " . PREFIX . "property");
                $sort = DB::fetchRow($sql);
                if (!empty($sort)) {
                  $sort = $sort[0] + 1;
                }
                DB::query("UPDATE " . PREFIX . "property SET sort = " . DB::quoteInt($sort) . " WHERE 1c_id = 2 AND name = 'Высота'");
              }
              $this->propertyConnect($id_1c, 2, $height, $category[$id]['category_id']);
            }
            if (!empty($width)) {
              $sql = DB::query("SELECT id FROM " . PREFIX . "property WHERE 1c_id = 3 AND name = 'Ширина'");
              $widthId = DB::fetchRow($sql);
              if (empty($widthId)) {
                DB::query("INSERT INTO " . PREFIX . "property 
                                  (name, type, all_category, activity, filter, type_filter, 1c_id, group_id) VALUES 
                                  ('Ширина', 'string', 1, 1, 1, 'checkbox', 3, 0)");
                $sql = DB::query("SELECT MAX(sort) FROM " . PREFIX . "property");
                $sort = DB::fetchRow($sql);
                if (!empty($sort)) {
                  $sort = $sort[0] + 1;
                  DB::query("UPDATE " . PREFIX . "property SET sort = " . DB::quoteInt($sort) . " WHERE 1c_id = 3 AND name = 'Ширина'");
                }
              }
              $this->propertyConnect($id_1c, 3, $width, $category[$id]['category_id']);
            }
          }

          self::logProcess('Получен товар: "' . $name . '"');
          self::logProcess($dataProd);


          if ($dataProd['code'] == '')
            unset($dataProd['code']);
          if ($dataProd['weight'] == '') {
            unset($dataProd['weight']);
          }

          if ($importOnlyNew) {
            // unset($dataProd['description']);
            // unset($dataProd['image_url']);
            // unset($dataProd['meta_title']);
            // unset($dataProd['meta_keywords']);
            unset($dataProd['recommend']);
            // unset($dataProd['activity']);
            unset($dataProd['new']);
            unset($dataProd['related']);
            unset($dataProd['inside_cat']);
            //unset($dataProd['url']);
          }

          $res = DB::query('SELECT * 
          FROM ' . PREFIX . 'product WHERE `1c_id`=' . DB::quote($id_1c));

          if ($row = DB::fetchAssoc($res)) {
            if (empty($id)) {
              $dataProd['cat_id'] = null;
            }
            $dataProd['cat_id'] = $dataProd['cat_id'] ? $dataProd['cat_id'] : $row['cat_id'];

            foreach ($updateSetting as $key => $setting) {
              $key = str_replace('1c_', '', $key);
              if ($setting != "true" || empty($dataProd[$key])) {
                unset($dataProd[$key]);
              }
            }

            //Конвертация веса из 1С в единицу измерения, установленную в товаре
            if (isset($dataProd['weight']) && $row['weight_unit'] != $weightUnit1C) {
              $dataProd['weight'] = MG::getWeightUnit('convert', ['from' => $weightUnit1C, 'to' => $row['weight_unit'], 'value' => $dataProd['weight']]);
            }

            DB::query('
            UPDATE `' . PREFIX . 'product`
            SET ' . DB::buildPartQuery($dataProd) . ', `last_updated` = \'' . date('Y-m-d H:i:s') . '\'
            WHERE `1c_id`=' . DB::quote($id_1c));
            $productId = $row['id'];
            self::logProcess('Товар "' . $name . '" обновлен');
            $countProduct++;
          } else {
            $found = false;
            if (strpos($id_1c, 'V') === false) {
              // проверим возможно id продукта совпадает с внешним id
              // это может случиться если изначально товары магазина, не имеющие внешнего кода,
              // были выгружены из заказов с действующим id вместо внешнего кода
              $res = DB::query('SELECT * 
							  FROM ' . PREFIX . 'product WHERE `id` LIKE ' . DB::quote($id_1c) . ' AND `1c_id` = \'\'');
              if ($row = DB::fetchAssoc($res)) {
                $dataProd['cat_id']  = $dataProd['cat_id'] ? $dataProd['cat_id'] : $row['cat_id'];

                foreach ($updateSetting as $key => $setting) {
                  $key = str_replace('1c_', '', $key);
                  if ($setting != "true" || empty($dataProd[$key])) {
                    unset($dataProd[$key]);
                  }
                }

                //Конвертация веса из 1С в единицу измерения, установленную в товаре
                if (isset($dataProd['weight']) && $row['weight_unit'] != $weightUnit1C) {
                  $dataProd['weight'] = MG::getWeightUnit('convert', ['from' => $weightUnit1C, 'to' => $row['weight_unit'], 'value' => $dataProd['weight']]);
                }

                unset($dataProd['1c_id']);
                self::logProcess('Товар "' . $name . '" обновлен');
                $countProduct++;
                DB::query('
                UPDATE `' . PREFIX . 'product`
                SET ' . DB::buildPartQuery($dataProd) . ', `last_updated` = \'' . date('Y-m-d H:i:s') . '\'
                WHERE `id`=' . DB::quote($id_1c));
                $productId = $row['id'];
                $found = true;
              }
            } else {
              $tmp = explode('V', $id_1c);
              $id_1c_prod = $tmp[0];
              $id_1c_var = $tmp[1];

              $res = DB::query("SELECT `id` FROM `" . PREFIX . "product_variant` 
							WHERE `product_id` = " . DB::quote($id_1c_prod) . " AND `id` = " . DB::quote($id_1c_var) . ' AND (`1c_id` = \'\' OR `1c_id` IS NULL)');
              if ($row = DB::fetchAssoc($res)) {
                $updateImage1c = $updateSetting['1c_image_url'];
                if ($updateImage1c && $updateImage1c != "true" || empty($dataProd['image_url'])) {
                  // При импорте данных из 1с, не перезаписывать картинки товара из 1с
                  unset($dataProd['image_url']);
                }

                if ($dataProd['image_url']) {
                  $dataProd['image_url'] = explode('|', $dataProd['image_url']);
                  $dataProd['image'] = $dataProd['image_url'][0];
                  unset($dataProd['image_url']);
                }

                $dataProd['title_variant'] = $dataProd['title'];
                unset($dataProd['url']);
                unset($dataProd['title']);
                unset($dataProd['description']);
                unset($dataProd['cat_id']);
                unset($dataProd['activity']);
                unset($dataProd['1c_id']);

                $variantDataProd = $dataProd;
                unset($variantDataProd['unit']);
                unset($variantDataProd['multiplicity']);
                unset($variantDataProd['meta_title']);
                unset($variantDataProd['meta_keywords']);
                unset($variantDataProd['meta_desc']);

                //Конвертация веса из 1С в единицу измерения, установленную в товаре
                $productWeightByVariant = DB::fetchAssoc(DB::query("SELECT `weight_unit` FROM `" . PREFIX . "product` 
              WHERE `id` = " . DB::quote($id_1c_prod)));
                if (isset($dataProd['weight']) && $productWeightByVariant['weight_unit'] != $weightUnit1C) {
                  $dataProd['weight'] = MG::getWeightUnit('convert', ['from' => $weightUnit1C, 'to' => $productWeightByVariant['weight_unit'], 'value' => $dataProd['weight']]);
                  $variantDataProd['weight'] = $dataProd['weight'];
                }

                self::logProcess('Вариант товара "' . $name . '" обновлен');
                $countProduct++;
                //self::logProcess($dataProd);
                DB::query("UPDATE `" . PREFIX . "product_variant` SET " . DB::buildPartQuery($variantDataProd) . ", `last_updated` = '" . date('Y-m-d H:i:s') . "'	WHERE `id` = " . DB::quote($row['id']));
                $productId = $id_1c_prod;
                $found = true;
              }
            }

            if (!$found) {
              // если внешний код не совпал ни с внешним кодом товара ни с его id,
              // значит переданный товар является новым
              self::logProcess('Товар "' . $name . '" создан');
              // Раньше для новых товаров срабатывали настройки исключений обновления товаров
              // но в этом никакого смысла нет, т. к. товар не обновляется а создаётся
              // foreach ($updateSetting as $key => $setting) {
              //   $key = str_replace('1c_', '', $key);
              //   if($key == 'title' && $setting == 'false'){
              //     continue;
              //   }elseif($setting != "true" || empty($dataProd[$key])) {
              //     unset($dataProd[$key]);
              //   }
              // }

              //Конвертация веса из 1С в единицу измерения, установленную в категории
              $productWeightByCat = DB::fetchAssoc(DB::query("SELECT `weight_unit` FROM `" . PREFIX . "category` 
            WHERE `id` = " . DB::quoteInt($dataProd['cat_id'])));
              if (isset($dataProd['weight']) && $productWeightByCat['weight_unit'] != $weightUnit1C) {
                $dataProd['weight'] = MG::getWeightUnit('convert', ['from' => $weightUnit1C, 'to' => $productWeightByCat['weight_unit'], 'value' => $dataProd['weight']]);
                $dataProd['weight_unit'] = $productWeightByCat['weight_unit'];
              }

              //self::logProcess($dataProd);
              $countProduct++;
              $newProd    = $model->addProduct($dataProd);
              $newProduct = true;

              if ($newProd == false) {
                continue;
              }

              $productId  = $newProd['id'];
            }
          }
          $updateImage1c = $updateSetting['1c_image_url'];
          if (!empty($realImgPath[0])) {
            $arImgPath = explode('/', $realImgPath[0]);
            array_pop($arImgPath);
            $path     = implode($sep, $arImgPath);
          }
          $imageUrl = explode('|', $imageUrl);

          $dir = floor($productId / 100) . '00';

          if (!empty($realImgPath) && ($updateImage1c == "true" || $newProduct)) {
            if (!file_exists($path . $sep . 'thumbs')) {
              mkdir($path . $sep . 'thumbs');
            }

            foreach ($realImgPath as $cell => $image) {
              if (!empty($image) && is_file($image)) {
                if (MG::getSetting("waterMark") == "true") {
                  $upload->addWatterMark($image);
                }

                $bigImg   = $upload->_reSizeImage('70_' . $imageUrl[$cell], $realDocumentRoot . $sep . $image, $widthPreview, $heightPreview, $resizeType, $path . $sep . 'thumbs' . $sep);
                $smallImg = $upload->_reSizeImage('30_' . $imageUrl[$cell], $realDocumentRoot . $sep . $image, $widthSmallPreview, $heightSmallPreview, $resizeType, $path . $sep . 'thumbs' . $sep);

                if (!$bigImg || !$smallImg) {
                  self::logProcess("Изображение " . $imageUrl[$cell] . " не обработано. Слишком большое разрешение.");
                  $log .= "Изображение " . $imageUrl[$cell] . " не обработано. Слишком большое разрешение.\n";
                } else {
                  self::logProcess("Изображение " . $imageUrl[$cell] . " загружено.");
                  $countImg++;
                }
              }
            }

            $model->movingProductImage($imageUrl, $productId, $path);
            rmdir($path . $sep . 'thumbs');
          }

          // Привязываем свойства.
          if (isset($productXml->ЗначенияСвойств)) {
            foreach ($productXml->ЗначенияСвойств->ЗначенияСвойства as $prop) {
              $propVal  = '';
              $tempProp = '' . $prop->Значение[0];

              if (is_writable('uploads')) {
                $cachedVars = unserialize(file_get_contents('uploads/variants1C.txt'));
              } else {
                $cachedVars = array();
              }

              if (!empty($_SESSION['variant_value'][$tempProp])) {
                $propVal = $_SESSION['variant_value'][$tempProp];
              } elseif (!empty($cachedVars[$tempProp])) {
                $propVal = $cachedVars[$tempProp];
              } else {
                if (!empty($tempProp)) {
                  $propVal = '' . $prop->Значение[0];
                }
              }

              if (empty($propVal)) {
                $propVal = '';
                $idVal   = '' . $prop->ИдЗначения;
                if (!empty($_SESSION['variant_value'][$idVal])) {
                  $propVal = $_SESSION['variant_value'][$idVal];
                } elseif (!empty($cachedVars[$idVal])) {
                  $propVal = $cachedVars[$idVal];
                }
              }

              if (empty($category[$id]['category_id'])) {
                $category[$id]['category_id'] = null;
              }

              $this->propertyConnect($id_1c, $prop->Ид, $propVal, $category[$id]['category_id']);
            }
          }

          $this->fillSeo($id_1c);

          $execTime = microtime(true) - $this->startTime;
          if ($execTime + 5 >= $this->maxExecTime) {
            self::logProcess("Выгрузка по частям: было выгружено " . $countProduct . " товаров и сохранено " . $countImg . " картинок");
            header("Content-type: text/xml; charset=utf-8");
            echo "\xEF\xBB\xBF";
            echo "progress\r\n";
            echo "Выгружено товаров: $currentPosition\n";
            echo $log;
            $_SESSION['lastCountProduct1cImport'] = $currentPosition;
            exit();
          }
        }
      }
      if ($xmlReader) {
          $xmlReader->close();
        }
      unset($xmlReader);

      self::logProcess("Загружено товаров: " . $countProduct . " шт.");
      self::logGeneralProcess("Загружено новых картинок: " . $countImg . " шт.", "SYNC");

      if ($this->unlinkFile) {
        unlink($realDocumentRoot . DS . TEMP_DIR . 'tempcml/'  . $filename);
      }

      $_SESSION['lastCountProduct1cImport'] = 0;
    } elseif (is_file($xmlFullPath) && stristr($filename, 'offers') !== FALSE) { //OFFERS SECTION
      self::saveFileImport($filename);
      $currentPosition = 0;
      $model           = new Models_Product;


      // Ищем новые склады в файле импорта из 1c, если есть новые добавляем, старые оставляем в движке.
      $storagesCMS = MG::getSetting('storages');
      $storagesCMS = unserialize(stripslashes($storagesCMS));
      $storages1c  = array();
      $storagesXml = $this->parseStorages($xmlFullPath);
      if (!empty($storagesXml)) {
        foreach($storagesXml as $store) {
          $storageId   = (string)$store->Ид[0];
          $storageName = (string)$store->Наименование[0];

          if (!$this->storageExist($storagesCMS, $storageId)) {
            $storagesCMS[] = array(
              'id' => $storageId,
              'name' => $storageName,
              'adress' => '',
              'desc' => ''
            );
          };
        }
      }

      //Оптовые цены
      $op1c = unserialize(stripslashes(MG::getSetting('op1c')));
      $op1cPrice = unserialize(stripslashes(MG::getSetting('op1cPrice')));
      $oldPriceName = MG::getSetting('oldPriceName1c');
      $oldPriceId = '';
      // Розничная цена
      $retailPriceName = MG::getSetting('retailPriceName1c');
      $retailPriceId = '';
      if (!empty($op1c)) {
        $op1cKeys = array_flip($op1c);
      } else {
        $op1c = [];
        $op1cKeys = [];
      }
      $priceList = array();
      $priceTypes = $this->parsePriceTypes($xmlFullPath);
      if (((!empty($op1cPrice) && !empty($op1c)) || (!empty($oldPriceName) && $updateSetting['1c_old_price'] == "true") || (!empty($retailPriceName)))
        && !empty($priceTypes)
      ) {
        foreach ($priceTypes as $price) {
          $priceId = (string)$price->Ид[0];
          $priceName = (string)$price->Наименование[0];

          if (in_array($priceName, $op1c) && $op1cPrice[$op1cKeys[$priceName]] === 'fromPrice') {
            $priceList[$priceId] = 'opf_' . $op1cKeys[$priceName];
            $needWholesales = true;
          }

          if (!empty($oldPriceName) && $priceName == $oldPriceName) {
            $oldPriceId = $priceId;
          }

          if (!empty($retailPriceName) && $priceName == $retailPriceName) {
            $retailPriceId = $priceId;
          }
        }
      }


      MG::setOption(array(
        'option' => 'storages',
        'value' => addslashes(serialize($storagesCMS))
      ));


      $currencyRate  = MG::getSetting('currencyRate');
      $currencyShort = MG::getSetting('currencyShort');

      $offersName = 'ИзмененияПакетаПредложений';
      $totalOffers = 0;
      $xmlReader = new XMLReader();
      $xmlReader->open($xmlFullPath);
      while (
        $xmlReader->read()
      ) {
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'ПакетПредложений'
        ) {
          $offersName = 'ПакетПредложений';
        }
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'Предложение'
        ) {
          ++$totalOffers;
        }
      }
      if ($xmlReader) {
          $xmlReader->close();
        }
      unset($xmlReader);

      self::logProcess('Количество импортированных предложений: ' . $totalOffers);
      self::logGeneralProcess('Загружено предложений: ' . $totalOffers . ' шт.', "SYNC");

      $xmlReader = new XMLReader();
      $xmlReader->open($xmlFullPath);

      $currentPosition = 0;

      $startOffers = false;
      while(
        $xmlReader->read()
      ) {
        if (
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === $offersName
        ) {
          $startOffers = true;
        }
        if (
          $xmlReader->nodeType === XMLReader::END_ELEMENT &&
          $xmlReader->localName  === $offersName
        ) {
          $startOffers = false;
        }
        if (
          $startOffers &&
          $xmlReader->nodeType === XMLReader::ELEMENT &&
          $xmlReader->localName === 'Предложение'
        ) {
          $offerXml = simplexml_load_string($xmlReader->readOuterXML());

          $currentPosition++;
          if ($currentPosition <= $lastPositionOffer) {
            continue;
          }
  
          $id    = (string)$offerXml->Ид[0];
          $ids1c = explode('#', (string)$offerXml->Ид[0]);
          if (strpos($ids1c[0], 'V') !== false) {
            $tmp = explode('V', $ids1c[0]);
            if (is_numeric($tmp[0]) && is_numeric($tmp[1])) {
              $ids1c[0] = $tmp[0];
              $ids1c[1] = $tmp[1];
            }
          }
  
          // if (empty($ids1c[1])) {
          // 	if (isset($offerXml->ИдХарактеристики)) {
          // 		$ids1c[1] = (string)$offerXml->ИдХарактеристики[0];
          // 	}
          // }
  
          if (!empty($retailPriceId) && isset($offerXml->Цены)) {
            foreach ($offerXml->Цены->Цена as $priceType) {
              if ((string)$priceType->ИдТипаЦены == $retailPriceId) {
                $price = (string)$priceType->ЦенаЗаЕдиницу;
              }
            }
          } else {
            //берем первую доступную цену в качестве розничной (если не указано явно какую брать)
            $price        = (string)$offerXml->Цены->Цена->ЦенаЗаЕдиницу[0];
          }
          $price_course = 0;
  
          $iso = $this->getIsoByCode((string)$offerXml->Цены->Цена->Валюта[0]);
          if ($iso == 'NULL') {
            $iso = mb_substr(MG::translitIt((string)$offerXml->Цены->Цена->Валюта[0]), 0, 3);
          }
  
          $count = (string)$offerXml->Количество[0];
  
          // если валюта товара не задана ранее в магазине, то добавим ее. (Курс нужно будет установить вручную в настройках)
  
          $currency = array();
  
          if (empty($currencyRate[$iso])) {
            $currency['iso'] = htmlspecialchars($iso);
            $tmp             = trim($currency['iso']);
            if (!empty($tmp)) {
              $currency['short']               = $currency['iso'];
              $currency['rate']                = 1;
              $currencyRate[$currency['iso']]  = $currency['rate'];
              $currencyShort[$currency['iso']] = $currency['short'];
  
              MG::setOption(array(
                'option' => 'currencyRate',
                'value' => addslashes(serialize($currencyRate))
              ));
              MG::setOption(array(
                'option' => 'currencyShort',
                'value' => addslashes(serialize($currencyShort))
              ));
            }
          }
  
          $opFields = array();
          // выгрузка характеристик в дополнительные поля
          if (isset($offerXml->ХарактеристикиТовара)) {
            $op1c = unserialize(stripslashes(MG::getSetting('op1c')));
            if (!empty($op1c)) {
              $op1cKeys = array_flip($op1c);
              foreach ($offerXml->ХарактеристикиТовара->ХарактеристикаТовара as $prop) {
                $propName  = (string)$prop->Наименование;
                $propVal = (string)$prop->Значение;
                if (in_array($propName, $op1c) && $op1cPrice[$op1cKeys] === 'fromProp') {
                  $opFields['opf_' . $op1cKeys[$propName]] = $propVal;
                }
              }
            }
          }

          if (!empty($op1c)) {
            foreach ($op1cPrice as $op1cId => $op1cType) {
              if ($op1cType !== 'fromTag') {
                continue;
              }
              $tagName = $op1c[$op1cId];
              if (isset($offerXml->$tagName)) {
                $op1cValue = (string) $offerXml->$tagName;
                $opFields['opf_'.$op1cId] = $op1cValue;
              }
            }
          }
  
  
  
  
          $oldPrice = '';
          if ((!empty($priceList) || !empty($oldPriceId)) && isset($offerXml->Цены)) {
            foreach ($offerXml->Цены->Цена as $priceType) {
              if (isset($priceList[(string)$priceType->ИдТипаЦены])) {
                $opName = $priceList[(string)$priceType->ИдТипаЦены];
                $opFields[$opName] = (string)$priceType->ЦенаЗаЕдиницу;
              }
              if (!empty($oldPriceId) && (string)$priceType->ИдТипаЦены == $oldPriceId && $updateSetting['1c_old_price'] == "true") {
                $oldPrice = (string)$priceType->ЦенаЗаЕдиницу;
              }
            }
          }
  
  
  
          // Привязываем свойство веса к системному полю "вес".
          $weight  = '';
          if (isset($offerXml->ЗначенияСвойств)) {
            foreach ($offerXml->ЗначенияСвойств->ЗначенияСвойства as $prop) {
              $propId  = '' . $prop->Ид[0];
              $propVal = '' . $prop->Значение[0];
              if (isset($weightProperty1cId) && $propId == $weightProperty1cId) {
                $weight = $propVal;
              }
            }
          }
  
          $partProd = array(
            'price' => $price,
            'count' => $count < 0 ? 0 : $count,
            // 'price_course' => $price*$currencyRate[$currency['iso']],
            'currency_iso' => $iso,
          );
  
          if (!empty($weight)) {
            $weight = str_replace(',', '.', $weight);
            $partProd['weight'] = $weight;
          }
  
          if ((!empty($oldPrice) || $oldPrice == '0') && $updateSetting['1c_old_price'] == "true") {
            $partProd['old_price'] = round($oldPrice, 2);
          } elseif (empty($oldPrice) && $updateSetting['1c_old_price'] == "true") {
            $partProd['old_price'] = null;
          }
  
          $partProd += $opFields;
  
          // проверяем, вдруг это предложение является вариантом для товара
          $variantId = '';
          // если id варианта не найден
          if (empty($ids1c[1])) {
            foreach ($updateSetting as $key => $setting) {
              $key = str_replace('1c_', '', $key);
              if ($setting != "true") {
                unset($partProd[$key]);
              }
            }
  
            //Конвертация веса из 1С в единицу измерения, установленную в товаре
            $productWeight = DB::fetchAssoc(DB::query("SELECT `weight_unit` FROM `" . PREFIX . "product` 
            WHERE 1c_id = " . DB::quote($ids1c[0])));
            if (isset($partProd['weight']) && $productWeight['weight_unit'] != $weightUnit1C) {
              $partProd['weight'] = MG::getWeightUnit('convert', ['from' => $weightUnit1C, 'to' => $productWeight['weight_unit'], 'value' => $partProd['weight']]);
            }
  
            // просто товар, не вариант
            $ptemp = isset($price_course) && $price_course != 0 ? $price_course : (floatval($price) * ($currencyRate[$iso] ? $currencyRate[$iso] : 1));
  
            $sql = '
              UPDATE `' . PREFIX . 'product`
              SET ' . DB::buildPartQuery($partProd) . ', `price_course` = ROUND(' . DB::quoteFloat($ptemp, true) . ',2), `last_updated` = \'' . date('Y-m-d H:i:s') . '\' 
              WHERE 1c_id = ' . DB::quote($ids1c[0]) . '
            ';
  
            DB::query($sql);
  
            foreach ($offerXml->Склад as $store) {
              $storageId    = (string)$store->attributes()->ИдСклада;
              $storageCount = (string)$store->attributes()->КоличествоНаСкладе;
            }
  
            // если есть склад в импортируемом файле, то обновляем информацию о количестве на этом складе
            if (isset($offerXml->Склад)) {
              $this->updateStorage($offerXml->Склад, $ids1c[0]);
            }
          } else {
            // если товарное предложение является вариантом для продукта
            $productId = '';
            $variantId = $ids1c[1];
  
            $variant = array();
  
            $dbRes = DB::query('
              SELECT `id`, `cat_id`, `code`, `title`, `weight_unit`, `weight` FROM `' . PREFIX . 'product`
              WHERE 1c_id LIKE ' . DB::quote($ids1c[0]));
  
            if ($row = DB::fetchArray($dbRes)) {
              $productId = $row['id'];
              $name      = array();
              if (!empty($offerXml->ХарактеристикиТовара->ХарактеристикаТовара)) {
                foreach ($offerXml->ХарактеристикиТовара->ХарактеристикаТовара as $prop) {
                  $name[] = $prop->Значение;
                }
              }
              if (!empty($name) && MG::getSetting('modParamInVarName') != 'false') {
                $name = implode(', ', $name);
              } else {
                //Если в название варианта есть название продукта...
                if (strpos(trim($offerXml->Наименование), trim($row['title'])) !== false) {
                  //...то отсекаем название продука
                  $name = str_replace(trim($row['title']), '', trim($offerXml->Наименование));
                  //...и убираем скобки
                  $name = trim($name);
  
                  /*
                  Поиск скобки регулярным выражением:
                  Так как вариант 100% обрамляется скобками со стороны 1С, мы просто ищем скобки первого уровня.
                  Примеры:
                  <Наименование>Флакон духов ( 100 мл )</Наименование> => 100 мл
                  <Наименование>Флакон духов ( 100 мл (за штуку) )</Наименование> => 100 мл (за штуку)
                  <Наименование>Табличка с надписью ( с текстом "Как дела?)" )</Наименование> =>  с текстом "Как дела?)"
                  <Наименование>Табличка с надписью ( с текстом "Не очень(" )</Наименование> =>  с текстом "Не очень("
                  */
                  preg_match('@\(((?:[^\(\)]++|(?R))*)\)@', $name, $matches);
                  if (isset($matches[1])) {
                    $name = $matches[1];
                    $name = trim($name);
                  } else {
                    //если не нашли через регулярку вдруг, то ищем вручную
                    if ($name[strlen($name) - 1] == ')') {
                      $name = mb_substr($name, 0, -1);
                    }
                    $name = trim($name);
                    if ($name[0] == '(') {
                      $name = mb_substr($name, 1);
                    }
                  }
                  //иначе пишем название варианта так, как оно есть
                } else {
                  $name = trim($offerXml->Наименование);
                }
              }
  
              //Конвертация веса из 1С в единицу измерения, установленную в товаре
              if (!empty($weight) && $row['weight_unit'] != $weightUnit1C) {
                $weight = MG::getWeightUnit('convert', ['from' => $weightUnit1C, 'to' => $row['weight_unit'], 'value' => $weight]);
              }
  
              //Если веса у варианта нету, то берем у товара
              if (empty($weight)) {
                $weight = $row['weight'];
              }
  
              //$weight = !empty($offerXml->Вес[0])? $offerXml->Вес[0]:0;
              $sizeId  = 0;
              $colorId = 0;
  
              // выгрузка из самого свойства
              if (isset($offerXml->ХарактеристикиТовара)) {
                foreach ($offerXml->ХарактеристикиТовара->ХарактеристикаТовара as $prop) {
                  $propName  = (string)$prop->Наименование;
                  $propVal = (string)$prop->Значение;
                  // размер
                  if (trim($propName) == MG::getSetting('sizeName1c')) {
                    $sizeName = (string)$prop->Значение;
                    $sizeId   = 0;
                    $sizeId   = $this->getSizeId($row['cat_id'], $sizeName, $productId, 'size');
                  }
                  // цвет
                  if (trim($propName) == MG::getSetting('colorName1c')) {
                    $colorName = $propVal;
                    $colorId   = 0;
                    $colorId   = $this->getSizeId($row['cat_id'], $colorName, $productId, 'color');
                  }
                }
              }
              // выгрузка из классификатора свойств
              if (isset($offerXml->ЗначенияСвойств)) {
                $propertiesXml = $this->parseProperties($xmlFullPath);
                foreach ($offerXml->ЗначенияСвойств->ЗначенияСвойства as $prop) {
                  $propId  = '' . $prop->Ид[0];
                  $propVal = '' . $prop->Значение[0];
  
                  // для создания размерной сетки из характеристики варианта
                  // $sizeId = 0;
                  // $colorId = 0;
                  if (MG::getSetting('variantToSize1c') == 'true') {
                    // размер
                    if ((string)$prop->Наименование[0] == MG::getSetting('sizeName1c')) {
                      foreach ($propertiesXml as $value) {
                        if ((string)$value->Наименование[0] == MG::getSetting('sizeName1c')) {
                          foreach ($value->ВариантыЗначений->Справочник as $itemSize) {
                            if ((string)$itemSize->ИдЗначения == $propVal) {
                              $sizeName = (string)$itemSize->Значение;
                              //
                              $sizeId   = 0;
                              $sizeId   = $this->getSizeId($row['cat_id'], $sizeName, $productId, 'size');
                            }
                          }
                        }
                      }
                    }
                    // цвет
                    if ((string)$prop->Наименование[0] == MG::getSetting('colorName1c')) {
                      foreach ($propertiesXml as $value) {
                        if ((string)$value->Наименование[0] == MG::getSetting('colorName1c')) {
                          foreach ($value->ВариантыЗначений->Справочник as $itemColor) {
                            if ((string)$itemColor->ИдЗначения == $propVal) {
                              $colorName = (string)$itemColor->Значение;
                              //
                              $colorId   = 0;
                              $colorId   = $this->getSizeId($row['cat_id'], $colorName, $productId, 'color');
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }


              // Обрабатываем изображения варианта, которые приходят в offers.xml
              $variantImage = null;
              if (!empty($offerXml->Картинка[0])) {
                $variantImage = $offerXml->Картинка[0].'';
                $imagesTempcmlPath = TEMP_DIR.'tempcml'.DS.rtrim(str_replace(basename($variantImage), '', $variantImage), DS);
                $variantImage = basename($variantImage);
                $imageTempcmlPath = $imagesTempcmlPath.DS.$variantImage;

  
                if (MG::getSetting("waterMark") == "true") {
                  $upload->addWatterMark($imageTempcmlPath);
                }
  
                if (MG::getSetting('thumbsProduct') === 'true') {
                  $bigImg = $upload->_reSizeImage('70_'.$variantImage, $imageTempcmlPath, $widthPreview, $heightPreview, $resizeType, $imagesTempcmlPath.DS.'thumbs'.DS);
                  $smallImg = $upload->_reSizeImage('30_'.$variantImage, $imageTempcmlPath, $widthSmallPreview, $heightSmallPreview, $resizeType, $imagesTempcmlPath.DS.'thumbs'.DS);
                
                  if (!$bigImg || !$smallImg) {
                    self::logProcess("Изображение ".$imageTempcmlPath." не обработано. Слишком большое разрешение.");
                    $log .= "Изображение ".$imageTempcmlPath." не обработано. Слишком большое разрешение.\n";
                  } else {
                    self::logProcess("Изображение ".$imageTempcmlPath." загружено.");
                  }
                }
  
                $model->movingProductImage([$variantImage], $productId, $imagesTempcmlPath);
                rmdir($imagesTempcmlPath.DS.'thumbs');
  
              }
  
              $titleVariant = $name;
              $variant      = array(
                'code' => !empty($offerXml->Артикул[0]) ? $offerXml->Артикул[0] : $offerXml->ШтрихКод[0],
                'price' => $price,
                'count' => $count < 0 ? 0 : $count,
                '1c_id' => $variantId,
                'weight' => $weight,
                'activity' => 1,
                'currency_iso' => $iso
              );

              if ($variantImage) {
                $variant['image'] = $variantImage;
              }
  
              if ($updateSetting['1c_old_price'] == "true") {
                $variant['old_price'] = $oldPrice;
              } else {
                unset($partProd['old_price']);
              }
  
              if ($updateSetting['1c_title'] == "true" || empty($titleVariant)) {
                $variant['title_variant'] = $titleVariant;
              }
  
              $variant += $opFields;
              $variant['code'] = str_replace(array(',', '|', '"', "'"), '', $variant['code']);
  
              if ($sizeId > 0)
                $variant['size'] = $sizeId;
              if ($colorId > 0)
                $variant['color'] = $colorId;
  
              if ($variant['code'] == '') {
                //если у варианта отсутствует свой артикул, а в продукте есть, то возьмем из продукта.
                $variant['code'] = $row['code'];
  
                // если и у продукта нет кода, тогда не обновляем.
                if ($row['code'] == '') {
                  unset($variant['code']);
                }
              }
  
              if ($variant['weight'] == '') {
                unset($variant['weight']);
              }
  
              // ******
              //  ищем варианты для этого товара
              $dbRes = DB::query('
                SELECT id FROM `' . PREFIX . 'product_variant`           
                WHERE product_id = ' . DB::quote($productId) . '
              ');
              // если еще ни одного небыло, то создаем и обновляем в таблице product значения по первому варианту
              if (!$row = DB::fetchArray($dbRes)) {
                foreach ($updateSetting as $key => $setting) {
                  $key = str_replace('1c_', '', $key);
                  if ($setting != "true" || empty($partProd[$key])) {
                    unset($partProd[$key]);
                  }
                }
                $sql = 'UPDATE `' . PREFIX . 'product`
                SET ' . DB::buildPartQuery($partProd) . ' , `price_course` = ROUND(' . DB::quoteFloat($price * $currencyRate[$iso], true) . ',2), `last_updated` = \'' . date('Y-m-d H:i:s') . '\' 
                WHERE 1c_id LIKE ' . DB::quote($ids1c[0]) . ' OR `id` LIKE ' . DB::quote($ids1c[0]) . '
               ';
  
                DB::query($sql);
              }
  
              // если для данного товара загружается первый вариант из списка, обновляем mg_product
              if ($_SESSION['temp_var_pid_1c'] != $ids1c[0]) {
                $notUnsetValuse = array('count'); //Массив полей, которые надо обновить (потому что функция empty принимает 0 как пустоту и не обновляет это поле count в 0)
                foreach ($updateSetting as $key => $setting) {
                  $key = str_replace('1c_', '', $key);
                  if (($setting != "true" || empty($partProd[$key])) && !in_array($key, $notUnsetValuse)) {
                    unset($partProd[$key]);
                  }
                }
                DB::query('
                 UPDATE `' . PREFIX . 'product`
                 SET ' . DB::buildPartQuery($partProd) . ' , `price_course` = ROUND(' . DB::quoteFloat($price * $currencyRate[$iso], true) . ',2), `last_updated` = \'' . date('Y-m-d H:i:s') . '\'
                 WHERE 1c_id LIKE ' . DB::quote($ids1c[0]) . ' OR `id` LIKE ' . DB::quote($ids1c[0]) . '
                ');
              }
              // обновляем 1c_id в памяти, чтобы в следующий раз предыдущее условие не сработало, т.к. это уже будет второй вариант
              $_SESSION['temp_var_pid_1c'] = $ids1c[0];
  
              // ******
              // проверяем, импортирован ли ранее этот вариант
              $dbRes = DB::query('
                SELECT id FROM `' . PREFIX . 'product_variant`           
                WHERE (1c_id LIKE ' . DB::quote($ids1c[1]) . ') 
                AND product_id = ' . DB::quote($productId) . '
              ');
  
              // если еще нет, то получаем массив всех имеющихся вариантов по этому продукту,
              // добавляем к нему новый вариант и обновляем массив вариантов стандартными средствами
              if (!$row = DB::fetchArray($dbRes)) {
                $arrVariants = array();
                $arVarCodes  = array();
                $res         = DB::query('
                  SELECT  pv.*
                  FROM `' . PREFIX . 'product_variant` pv    
                  WHERE pv.product_id = ' . DB::quote($productId) . '
                  ORDER BY sort
                ');
  
                if (!empty($res)) {
                  while ($var = DB::fetchAssoc($res)) {
                    $arrVariants[$var['id']] = $var;
                    $var['code'] = str_replace(array(',', '|', '"', "'"), '', $var['code']);
                    $arVarCodes[]            = $var['code'];
                  }
                }
  
                $variant['sort'] = count($arrVariants);
  
                if (count($arrVariants) > 0 && in_array($variant['code'], $arVarCodes)) {
                  $variant['code'] .= '-' . count($arrVariants);
                }
  
                $variant['price_course'] = round($price * $currencyRate[$iso], 2);
                $variant['title_variant'] = $titleVariant;
                $arrVariants[]           = $variant;
                $model->saveVariants($arrVariants, $productId);
              } else {
                // обновить вариант
                foreach ($updateSetting as $key => $setting) {
                  $key = str_replace('1c_', '', $key);
                  if ($setting != "true") {
                    unset($variant[$key]);
                  }
                }
                DB::query('
                 UPDATE `' . PREFIX . 'product_variant`
                 SET ' . DB::buildPartQuery($variant) . ',`price_course` = ROUND(' . DB::quoteFloat($price * $currencyRate[$iso], true) . ',2), `last_updated` = \'' . date('Y-m-d H:i:s') . '\'
                 WHERE (1c_id LIKE ' . DB::quote($ids1c[1]) . ' OR `id` LIKE ' . DB::quote($ids1c[1]) . ') 
                 AND product_id = ' . DB::quoteInt($productId) . '
                ');
              }
  
              // если есть склад в импортируемом файле, то обновляем информацию о количестве варианта товара на этом складе
              if (isset($offerXml->Склад)) {
                $dbRes = DB::query('
                SELECT id FROM `' . PREFIX . 'product_variant`          
                WHERE (1c_id LIKE ' . DB::quote($ids1c[1]) . ' OR `id` LIKE ' . DB::quote($ids1c[1]) . ') AND  product_id = ' . DB::quoteInt($productId) . '
                ');
                if ($rowVar = DB::fetchArray($dbRes)) {
                  $this->updateStorage($offerXml->Склад, $ids1c[0], $rowVar['id']);
                }
              }
            }
          }
          $this->fillSeo($ids1c[0]);
          $execTime = microtime(true) - $this->startTime;
  
          if ($execTime + 1 >= $this->maxExecTime) {
            header("Content-type: text/xml; charset=utf-8");
            echo "\xEF\xBB\xBF";
            echo "progress\r\n";
            echo "Выгружено предложений: $currentPosition";
            echo $log;
            $_SESSION['lastCountOffer1cImport'] = $currentPosition;
            exit();
          }
        }
      }
      if ($xmlReader) {
          $xmlReader->close();
        }
      unset($xmlReader);

      if ($this->unlinkFile) {
        unlink($realDocumentRoot . DS . TEMP_DIR . 'tempcml/'  . $filename);
        //$upload->removeDirectory($realDocumentRoot .DS. TEMP_DIR . 'tempcml/');
      }

      $_SESSION['lastCountOffer1cImport'] = 0;
      unset($_SESSION['sizePropId1c']);
      unset($_SESSION['colorPropId1c']);
      $_SESSION['sizePropCatsBound1c'] = array();
      $_SESSION['sizePropVarsBound1c'] = array();
      Storage::clear();
    } else {
      echo "Ошибка загрузки XML\n";
      foreach (libxml_get_errors() as $error) {
        echo "\t", $error->message;
        exit;
      }
    }

    //Применяем оптовые цены после обмена предложениями
    if ($needWholesales) {
      self::asyncRequest('applyWholesales');
    }

    return $log;
  }
  /**
   * сохранение и получение данных для сохранения размера
   * @access private
   * @param int $catId id категории
   * @param string $value значение характеристики
   */
  function getSizeId($catId, $value, $productId, $type)
  {
    if ($type == 'color') {
      $typeText = MG::getSetting('colorName1c');
    } else {
      $typeText = MG::getSetting('sizeName1c');
    }

    // фикс если xml не очень
    $value = (string)$value;
    if (empty($_SESSION[$type . 'PropId1c'])) {
      $res = DB::query("SELECT `id` FROM `" . PREFIX . "property` WHERE `1c_id` = '" . DB::quote($type, true) . "1c'");
      if ($row = DB::fetchArray($res)) {
        $_SESSION[$type . 'PropId1c'] = $row['id'];
        $res                          = DB::query("SELECT `category_id` FROM `" . PREFIX . "category_user_property` WHERE `property_id` = " . DB::quote($_SESSION[$type . 'PropId1c']));
        while ($row = DB::fetchArray($res)) {
          $_SESSION[$type . 'PropCatsBound1c'][] = $row['category_id'];
        }
        $res = DB::query("SELECT `id`, `name` FROM `" . PREFIX . "property_data` WHERE `prop_id` = " . DB::quote($_SESSION[$type . 'PropId1c']));
        while ($row = DB::fetchArray($res)) {
          $_SESSION[$type . 'PropVarsBound1c'][$row['name']] = $row['id'];
        }
      } else {
        DB::query("INSERT INTO `" . PREFIX . "property`
			(`name`,`type`,`activity`,`filter`,`type_filter`,`1c_id`,`plugin`,`unit`) VALUES
			('" . DB::quote($typeText, true) . "[prop attr=1C]', " . DB::quote($type) . ", '1', '1', 'checkbox', '" . DB::quote($type, true) . "1c', '', '')");
        $_SESSION[$type . 'PropId1c']        = DB::insertId();
        $_SESSION[$type . 'PropCatsBound1c'] = array();
        $_SESSION[$type . 'PropVarsBound1c'] = array();
      }
    }

    if (!in_array($catId, $_SESSION[$type . 'PropCatsBound1c'])) {
      DB::query("INSERT INTO `" . PREFIX . "category_user_property` (`category_id`,`property_id`) VALUES (" . DB::quoteInt($catId) . ", " . DB::quote($_SESSION[$type . 'PropId1c']) . ")");
      $_SESSION[$type . 'PropCatsBound1c'][] = $catId;
    }

    if (!array_key_exists($value, $_SESSION[$type . 'PropVarsBound1c'])) {
      if ($type == 'color') {
        $color = self::getHexByName($value);
        DB::query("INSERT INTO `" . PREFIX . "property_data` (`prop_id`,`name`, `color`) VALUES (" . DB::quote($_SESSION[$type . 'PropId1c']) . ", " . DB::quote($value) . ", " . DB::quote($color) . ")");
        $lastId                                      = DB::insertId();
        $_SESSION[$type . 'PropVarsBound1c'][$value] = $lastId;
        DB::query("UPDATE `".PREFIX."property_data` SET `sort` = ".$lastId." WHERE `id` = ".$lastId);
      } else {
        DB::query("INSERT INTO `" . PREFIX . "property_data` (`prop_id`,`name`) VALUES (" . DB::quote($_SESSION[$type . 'PropId1c']) . ", " . DB::quote($value) . ")");
        $lastId                                      = DB::insertId();
        $_SESSION[$type . 'PropVarsBound1c'][$value] = $lastId;
        DB::query("UPDATE `".PREFIX."property_data` SET `sort` = ".$lastId." WHERE `id` = ".$lastId);
      }
    }

    $res = DB::query("SELECT `id` FROM `" . PREFIX . "product_user_property_data` WHERE `prop_id` = " . DB::quote($_SESSION[$type . 'PropId1c']) . " AND `prop_data_id` = " . DB::quote($_SESSION[$type . 'PropVarsBound1c'][$value]) . " AND `product_id` = " . DB::quoteInt($productId));
    if (!DB::fetchArray($res)) {
      DB::query("INSERT INTO `" . PREFIX . "product_user_property_data` (`prop_id`,`prop_data_id`,`product_id`,`name`,`active`) VALUES (" . DB::quote($_SESSION[$type . 'PropId1c']) . ", " . DB::quote($_SESSION[$type . 'PropVarsBound1c'][$value]) . ", " . DB::quoteInt($productId) . ",".DB::quote($value).", '1')");
    }

    return $_SESSION[$type . 'PropVarsBound1c'][$value];
  }

  /**
   * Возвращает HEX цвета по названию
   * @access private
   * @param string $value
   * @return string
   */
  public function getHexByName($value){
    if (MG::getSetting('variantToSize1c') != "true") {
      return "";
    }
    $sql = "SELECT `hex`, `name` FROM `".PREFIX."color_list` WHERE `name` LIKE '%".DB::quote($value, true)."%'";
    $result = DB::query($sql);
    if (!$result || $result->num_rows == 0) return '';
    $listResult = array();

    while ($row = DB::fetchAssoc($result)) {
      $listResult[$row['name']] = $row['hex'];
    }

    //Ищем самое короткое название и отдаем его HEX
    $nameList = array_keys($listResult);
    array_multisort(array_map('strlen', $nameList), $nameList);
    return $listResult[$nameList[0]];
  }

  /**
   * Обход дерева групп полученных из 1С.
   * @access private
   * @param object $xml дерево с данными.
   * @param array $category категория.
   * @param int $parent родительская категория.
   * @return array
   */
  function groupsGreate($xml, $category, $parent)
  {
    if (!isset($xml->Группы)) {
      return $category;
    }

    $countCategory = 0; //Для логера

    foreach ($xml->Группы->Группа as $category_data) {

      $cnt                           = (string)$category_data->Ид;

      if (isset($_SESSION['import1c']['category'][$cnt])) continue;

      $name = (string)$category_data->Наименование;
      // костыль для МойСклад (Ни при каких условиях не создавать категорию 'Товары интернет-магазинов')
      if ($name == 'Товары интернет-магазинов') {
        $rootCategory = [
          'category_id' => 0,
          'name' => '',
        ];
        $this->groupsGreate($category_data, $category, $rootCategory);
        continue;
      }

      $category[$cnt]['1c_id']       = $cnt;
      $category[$cnt]['name']        = $name;
      $category[$cnt]['parent_id']   = $parent['category_id'];
      $category[$cnt]['parentname']  = $parent['name'];
      $category[$cnt]['description'] = "";
      $category[$cnt]['category_id'] = $this->newCategory($category[$cnt]);
      $category                      = $this->groupsGreate($category_data, $category, $category[$cnt]);

      $countCategory++; //Для логера

      $_SESSION['import1c']['category'][$cnt] = true;

      $execTime = microtime(true) - $this->startTime;
      if ($execTime + 5 >= $this->maxExecTime) {
        header("Content-type: text/xml; charset=utf-8");
        echo "\xEF\xBB\xBF";
        echo "progress\r\n";
        echo "Выгружено категорий: " . count($_SESSION['import1c']['category']) . "\n";
        if (isset($currentPosition)) {
          $_SESSION['lastCountProduct1cImport'] = $currentPosition;
        }
        exit();
      }
    }

    if (
      empty($parent['category_id']) &&
      empty($parent['name'])
    ) {
      $this->deleteRootCat();
    }

    self::logGeneralProcess("Загружено категорий: " . $countCategory . " шт.", "SYNC");
    return $category;
  }

  /**
   * Создание новой категории.
   * @access private
   * @param array $category категория.
   * @return int
   */
  function newCategory($category)
  {
    $notUpdateCat1CRaw = MG::getSetting('notUpdateCat1C', true);
    $notUpdateCat1C = [];
    foreach ($notUpdateCat1CRaw as $notUpdateKey => $notUpdateField) {
      if ($notUpdateField === 'true') {
        $notUpdateCat1C[str_replace('cat1c_', '', $notUpdateKey)] = true;
      }
    }

    $oldCategory = null;
    $res = DB::query('SELECT * FROM `' . PREFIX . 'category` WHERE `1c_id`=' . DB::quote($category['1c_id']));
    $oldCategory = DB::fetchAssoc($res);

    $parentId = $category['parent_id'];
    if (!$notUpdateCat1C['parent']) {
      if ($oldCategory) {
        $parentId = $oldCategory['parent'];
      } else {
        $parentId = 0;
      }
    }

    if ($oldCategory && !$notUpdateCat1C['url']) {
      $url = $oldCategory['url'];
    } else {
      $url = URL::prepareUrl(MG::translitIt($category['name'], 1));
    }

    if ($oldCategory && !$notUpdateCat1C['title']) {
      $category['name'] = $oldCategory['title'];
    }

    $parent_url = MG::get('category')->getParentUrl($parentId);

    $data = array(
      'title' => $category['name'],
      'url' => str_replace(array('/', '\\'), '-', $url),
      'parent' => $parentId,
      //'meta_title' => $category['name'],
      //'meta_keywords' => $category['name'],
      //'meta_desc' => MG::textMore($category['description'], 157),
      'invisible' => (MG::getSetting('activityCategory1C')) ? 0 : 1,
      'parent_url' => $parent_url,
      '1c_id' => $category['1c_id']
    );

    if ($notUpdateCat1C['html_content']) {
      $data['html_content'] = $category['description'];
    }

    if ($oldCategory) {
      unset($data['invisible']);
      DB::query(' UPDATE `' . PREFIX . 'category` SET ' . DB::buildPartQuery($data) . ' WHERE `1c_id`=' . DB::quote($category['1c_id']));
      self::logProcess('Обновлена категория "' . $data['title'] . '"');
      return $oldCategory['id'];
    } else {
      $data = MG::get('category')->addCategory($data);
      self::logProcess('Создана категория "' . $data['title'] . '"');
      return $data['id'];
    }

    return 0;
  }

  /**
   * Создание свойств для товаров.
   * @access private
   * @param object $xml дерево с данными
   */
  function propertyСreate($xml)
  {

  
    $countProp = 0; //Для логера
    $varsArr = array();
    
    // добавляем свойства в обратном порядке, так как в движке они инвертированы
    $allCount = 0;
    if ($xml->Свойство) {
      $allCount = count($xml->Свойство);
    }
    for ($i = $allCount - 1; $i >= 0; $i--) {
      $property_data = $xml->Свойство[$i];
      foreach ($property_data->ТипыЗначений as $typesValue) {
        foreach ($typesValue->ТипЗначений as $typeValue) {
          foreach ($typeValue->ВариантыЗначений as $variantsVal) {
            foreach ($variantsVal->ВариантЗначения as $variantVal) {
              $tId  = '' . $variantVal->Ид;
              $varsArr[$tId] = '' . $variantVal->Значение;
              $_SESSION['variant_value'][$tId] = '' . $variantVal->Значение;
            }
          }
        }
      }

      foreach ($property_data->ВариантыЗначений as $variantsVal) {
        foreach ($variantsVal->Справочник as $variantVal) {
          $tId                             = '' . $variantVal->ИдЗначения;
          $varsArr[$tId] = '' . $variantVal->Значение;
          $_SESSION['variant_value'][$tId] = '' . $variantVal->Значение;
        }
      }
      if (is_writable('uploads')) {
        file_put_contents('uploads/variants1C.txt', serialize($varsArr));
      }
      $this->propertyСreateProcess($property_data);
      $countProp++;
    }


    foreach ($xml->СвойствоНоменклатуры as $property_data) {
      $this->propertyСreateProcess($property_data);
      $countProp++;
    }

    self::logGeneralProcess("Загружено свойств: " . $countProp . " шт.", "SYNC");
  }

  /**
   * Процесс создания характеристик
   * @access private
   * @param object $property_data - объект с характеристиками
   */
  function propertyСreateProcess($property_data)
  {

    $id   = (string)$property_data->Ид;
    $name = (string)$property_data->Наименование;

    $property['1c_id'] = $id;
    $property['name']  = $name;

    $res = DB::query('SELECT * 
		FROM `' . PREFIX . 'property` 
		WHERE `1c_id`=' . DB::quote($property['1c_id']));
    if ($row = DB::fetchAssoc($res)) {
      DB::query('
			UPDATE `' . PREFIX . 'property`
			SET `name` =' . DB::quote($property['name']) . '
			WHERE `1c_id`=' . DB::quote($property['1c_id']));
      self::logProcess('Обновлено свойство ' . $property['name'] . '"');
    } else {
      $res = DB::query('SELECT COUNT(id) AS count FROM '.PREFIX.'property WHERE name = '.DB::quote($property['name']));
      $row = DB::fetchAssoc($res);
      if($row['count'] > 1) {
        $property['name'] .= ' [prop attr='.$id.']';
      }
      /**/
      DB::query("
			INSERT INTO `" . PREFIX . "property` 
			(`name`,`type`,`all_category`,`activity`,`filter`,`type_filter`,`1c_id`)        
			VALUES (" . DB::quote($property['name']) . ",'string','1','1','1','checkbox'," . DB::quote($property['1c_id']) . ")");
      self::logProcess('Создано свойство "' . $property['name'] . '"');
      if ($lastId = DB::insertId()) {
        DB::query("
		 		UPDATE `" . PREFIX . "property`
		 		SET `sort`=`id` WHERE `id` = " . DB::quote($lastId));
      }
    }
  }

  /**
   * Привязка свойств к товару, категории и установка значений
   * @access private
   * @param int $productId1c - id товара из 1с в бзе сайта.
   * @param int $propId1c - id обрабатываемого товара из 1с.
   * @param string $propValue - значение свойства.
   * @param int $categoryId - id категории.
   * @return bool
   */
  function propertyConnect($productId1c, $propId1c, $propValue, $categoryId)
  {
    // Получаем реальные id для товара и свойства из базы данных.
    $res = DB::query('SELECT id FROM `' . PREFIX . 'product` WHERE `1c_id`=' . DB::quote($productId1c));
    if ($row = DB::fetchAssoc($res)) {
      $productId = $row['id'];
    } else {
      return false;
    }

    $res = DB::query('SELECT id FROM `' . PREFIX . 'property` WHERE `1c_id`=' . DB::quote($propId1c));
    if ($row = DB::fetchAssoc($res)) {
      $propertyId = $row['id'];
    } else {
      return false;
    }

    if (MG::getSetting('updateStringProp1C') == 'true') { //Если включена опция обновлять свойства
      $res = DB::query('SELECT id FROM ' . PREFIX . 'product_user_property_data WHERE
			prop_id = ' . DB::quoteInt($propertyId) . ' AND product_id = ' . DB::quoteInt($productId));
      if ($row = DB::fetchAssoc($res)) {
        $res = DB::query('DELETE FROM ' . PREFIX . 'product_user_property_data WHERE prop_id = ' . DB::quoteInt($propertyId) . ' AND product_id = ' . DB::quoteInt($productId));
      }
    }
    Property::createProductStringProp($propValue, $productId, $propertyId);
    Property::createPropToCatLink($propertyId, $categoryId);
    return true;
  }

  /**
   * Парсинг XML.
   * @access private
   * @param string $filename исходный файл.
   * @return object
   */
  public function getImportXml($filename)
  {
	$realDocumentRoot = URL::getDocumentRoot(false);
    $xml = simplexml_load_file($realDocumentRoot .DS. TEMP_DIR . 'tempcml/'  . $filename);
    //WRITE_MODE
    self::saveFileImport($filename);
    return $xml;
  }

  /**
   * Возвращает ISO коды валют по ID валюты.
   * @access private
   * @param string $id ID валюты.
   * @return string
   */
  public function getIsoByCode($id)
  {
    $arrayRub = array(
      'RUB',
      'руб',
      'RUR'
    );
    if (in_array($id, $arrayRub)) {
      return 'RUR';
    }
    $arr = array(
      '643' => 'RUB',
      '980' => 'UAH',
      '974' => 'BYR',
      '398' => 'KZT',
      '860' => 'UZS',
      '972' => 'TJS',
      '795' => 'TMM',
      '417' => 'KGS',
      '498' => 'MDL',
      '051' => 'AMD',
      '031' => 'AZM',
      '981' => 'GEL',
      '428' => 'LVL',
      '233' => 'EEK',
      '440' => 'LTL',
      '840' => 'USD',
      '826' => 'GBP',
      '756' => 'CHF',
      '752' => 'SEK',
      '578' => 'NOK',
      '208' => 'DKK',
      '124' => 'CAD',
      '368' => 'IQD',
      '392' => 'JPY',
      '036' => 'AUD',
      '978' => 'EUR',
      '414' => 'KWD',
      '586' => 'PKR',
      '422' => 'LBP',
      '352' => 'ISK',
      '702' => 'SGD',
      '400' => 'JOD',
      '736' => 'SDD',
      '949' => 'TRY',
      '682' => 'SAR',
      '032' => 'ARS',
      '818' => 'EGP',
      '986' => 'BRL',
      '364' => 'IRR',
      '356' => 'INR',
      '524' => 'NPR',
      '004' => 'AFA',
      '360' => 'IDR',
      '710' => 'ZAR',
      '196' => 'CYP',
      '901' => 'TWD',
      '152' => 'CLP',
      '012' => 'DZD',
      '376' => 'ILS',
      '348' => 'HUF',
      '203' => 'CZK',
      '642' => 'ROL',
      '496' => 'MNT',
      '975' => 'BGN',
      '704' => 'VND',
      '985' => 'PLN',
      '192' => 'CUP',
      '703' => 'SKK',
      '960' => 'XDR',
      '008' => 'ALL',
      '784' => 'AED',
      '404' => 'KES',
      '156' => 'CNY',
      '170' => 'COP',
      '418' => 'LAK',
      '434' => 'LYD',
      '504' => 'MAD',
      '484' => 'MXN',
      '566' => 'NGN',
      '554' => 'NZD',
      '604' => 'PEN',
      '760' => 'SYP',
      '705' => 'SIT',
      '764' => 'THB',
      '788' => 'TND',
      '858' => 'UYU',
      '608' => 'PHP',
      '144' => 'LKR',
      '230' => 'ETB',
      '891' => 'YUM',
      '410' => 'KRW'
    );

    return isset($arr[$id]) ? $arr[$id] : 'NULL';
  }
  /**
   * Проверка на существование ID склада в базе движка
   * @access private
   * @param array $storagesCMS массив складов.
   * @param int $id ID искомого склада.
   * @return bool
   */
  public function storageExist($storagesCMS, $id)
  {
    $result = false;

    foreach ($storagesCMS as $storeCms) {
      if ($storeCms['id'] == $id) {
        // Если нашли, то записываем флаг nextStorage для перехода к следующему объекту.
        $result = true;
        break;
      }
    }

    return $result;
  }



  function updateStorage($storagesXml, $product1cId, $variantId = 0) {
    $productModel = new Models_Product();
    
    $productId = $productModel->getProductIdByExternalId($product1cId);
    if (!$productId) {
      return false;
    }
    
    $variantId = intval($variantId);

    foreach ($storagesXml as $storageXml) {
      $storageId = strval($storageXml->attributes()->ИдСклада);
      $storageCount = floatval($storageXml->attributes()->КоличествоНаСкладе);
      if ($storageId) {
        $productModel->setNewStorageCount($productId, $storageId, $storageCount, $variantId);
      }
    }

    $productModel->recalculateStoragesById($productId);
    return true;
  }

  /**
   * Метод для записи лога, если включена опция
   *
   * @access private
   * @param array $text
   * @return void
   */
  public function logProcess($text)
  {
    //WRITE_MODE
    if (MG::getSetting('writeLog1C') == 'true') {
      $dateDay = $_SESSION['dateDay1C'];
      $dateTime = $_SESSION['dateTime1C'];
      $date = $dateDay . '_' . $dateTime;
      $fileName =  TEMP_DIR.'log1c' . DS . $dateDay . DS . $dateTime . DS . 'log.txt';
      ob_start();
      print_r($text);
      $content = ob_get_contents();
      ob_end_clean();
      $content = str_replace('=>' . "\n ", ' =>', $content);
      $string = date('d.m.Y H:i:s') . ' => ' . $content . "\r\n";
      self::checkDirs($dateDay, $dateTime);
      $f = fopen($fileName, 'a+');
      fwrite($f, $string);
      fclose($f);
      chmod($fileName, 0777);
    }
  }

  /**
   * Создает файл общего лога и заполняет его по меткам.
   * Метки создаются в методе createInfoLog(). Текст вставляется до найденной метки.
   *
   * @access private
   * @param string $text
   * @param string $position
   * @return void
   */
  public function logGeneralProcess($text, $position = "START")
  {
    //WRITE_MODE
    if (MG::getSetting('writeLog1C') == 'true') {

      $dateDay = $_SESSION['dateDay1C'];
      $dateTime = $_SESSION['dateTime1C'];
      $fileName = TEMP_DIR.'log1c' . DS . $dateDay . DS . $dateTime . DS . 'info.txt';
      ob_start();
      print_r($text);
      $content = ob_get_contents();
      ob_end_clean();
      $content = str_replace('=>' . "\n ", ' =>', $content);
      $string = $content."\n";
      self::checkDirs($dateDay, $dateTime);

      if ($position == "START") {
        $f = fopen($fileName, 'a+');
        fwrite($f, $string);
        fclose($f);
      } else {
        $file = file_get_contents($fileName);
        $pos = strpos($file, "#".$position);
        $file = substr_replace($file, $string, $pos, 0);
        file_put_contents($fileName, $file);
      }

      chmod($fileName, 0777);
    }
  }

  /**
   * Сохранять файл импорта, если включена опция
   *
   * @access private
   * @param string $filename
   * @return void
   */
  public function saveFileImport($filename)
  {
    if (MG::getSetting('writeFiles1C') == "true") {
      $dateDay = $_SESSION['dateDay1C'];
      $dateTime = $_SESSION['dateTime1C'];
      $date = $dateDay . '_' . $dateTime;
      //$filenameParts = explode('.',$filename);
      $newFile = TEMP_DIR.'log1c' . DS . $dateDay . DS . $dateTime . DS . $filename;
      self::checkDirs($dateDay, $dateTime);
      copy(TEMP_DIR . 'tempcml/' . $filename, $newFile);
      chmod($newFile, 0777);
    }
  }

  /**
   * Сохранять файл экспорта, если включена опция
   *
   * @access private
   * @param string $nXML
   * @return void
   */
  public function saveFileExport($nXML)
  {
    if (MG::getSetting('writeFiles1C') == "true") {
      $dateDay = $_SESSION['dateDay1C'];
      $dateTime = $_SESSION['dateTime1C'];
      $date = $dateDay . '_' . $dateTime;
      $file = TEMP_DIR.'log1c' . DS . $dateDay . DS . $dateTime . DS . 'exportQuery.xml';
      self::checkDirs($dateDay, $dateTime);
      $saveFile = fopen($file, 'w');
      fwrite($saveFile, $nXML);
      fclose($saveFile);
      chmod($file, 0777);
    }
  }

  /**
   * Метод для создания папок под логи и файлы экспорта/импорта
   *
   * @access private
   * @param string $dateDay
   * @param string $dateTime
   * @return void
   */
  public function checkDirs($dateDay, $dateTime)
  {
    mg::createTempDir('tempcml');
    mg::createTempDir('log1c');
    mg::createTempDir('log1c' . DS . $dateDay);
    mg::createTempDir('log1c' . DS . $dateDay . DS . $dateTime);
  }

  /**
   * Метод для генерации текста о включенных опциях обмена
   * @access private
   * @return string
   */
  public function getOptions()
  {
    $lang = MG::get('lang');
    $fileLimit1C = MG::getSetting('fileLimit1C');
    $ordersPerTransfer1c = MG::getSetting('ordersPerTransfer1c');
    $leaveOrderStatuses = MG::getSetting('leaveOrderStatuses') == 'true' ? 'Да' : 'Нет';
    $weightPropertyName1c = MG::getSetting('weightPropertyName1c');
    $weightUnit1C = MG::getSetting('weightUnit1C');
    $variantToSize1c = MG::getSetting('variantToSize1c') == "true" ? "Да" : "Нет";
    $skipRootCat1C = MG::getSetting('skipRootCat1C') == "true" ? "Да" : "Нет";
    $colorName1c = MG::getSetting('colorName1c');
    $sizeName1c = MG::getSetting('sizeName1c');
    $clearCatalog1C = MG::getSetting('clearCatalog1C') == "true" ? "Да" : "Нет";
    $closeSite = MG::getSetting('closeSite') == "true" ? "Да" : "Нет";
    $modParamInVarName = MG::getSetting('modParamInVarName') == "true" ? "Да" : "Нет";
    $activityCategory1C = MG::getSetting('activityCategory1C') == "true" ? "Да" : "Нет";
    $writeFullName1C = MG::getSetting('writeFullName1C') == "true" ? "Да" : "Нет";
    $notUpdate1C = unserialize(stripslashes(MG::getSetting('notUpdate1C')));
    $notUpdateText = "Список обновляемых полей: '";
    $tmp = array();
    foreach ($notUpdate1C as $key => $value) {
      if ($value == "true") $tmp[] = str_replace("1c_", "", $key);
    }
    $notUpdateText .= implode(",", $tmp);
    $notUpdateText .= "'\n";
    $writeLog1C = MG::getSetting('writeLog1C') == "true" ? "Да" : "Нет";
    $writeFiles1C = MG::getSetting('writeFiles1C') == "true" ? "Да" : "Нет";
    $updateStringProp1C = MG::getSetting('updateStringProp1C') == "true" ? "Да" : "Нет";

    $opFieldsM = new Models_OpFieldsProduct('get');
    $fields = $opFieldsM->get();
    $op1c = unserialize(stripslashes(MG::getSetting('op1c')));

    $opFieldsText = '';
    if (!empty($fields)) {
      foreach ($fields as $id => $field) {
        $opFieldsText .= "Название свойства в 1С, в котором хранится \"".$field['name']."\": \"".$op1c[$id]."\"\n" ;
      }
    }

    $string =
      "Версия интеграции: 1.0.2 \n" .
      "Максимальный размер архива, передаваемого из 1С: " . $fileLimit1C . " байт\n" .
      "Максимальное количество заказов в одном обмене: " . $ordersPerTransfer1c . " шт.\n".
      "Не обновлять в CMS статусы заказов из 1С: " . $leaveOrderStatuses . "\n".
      "Название свойства в 1С, в котором хранится Вес товара: \"" . $weightPropertyName1c . "\"\n" .
      "Единица измерения веса в 1С: \"" . $lang['weightUnit_'.$weightUnit1C] . "\"\n" .
      "Создавать размеры/цвета из названий вариантов: " . $variantToSize1c . "\n" .
      "Название свойства в 1С, в котором хранится цвет: \"" . $colorName1c . "\"\n" .
      "Название свойства в 1С, в котором хранится размер: \"" . $sizeName1c . "\"\n" .
      "Очищать каталог перед импортом: " . $clearCatalog1C . "\n" .
      "Автоматически закрывать сайт на время синхронизации: " . $closeSite . "\n" .
      "Генерация названий вариантов: " . $modParamInVarName . "\n" .
      "Активировать категории после импорта: " . $activityCategory1C . "\n" .
      "Не импортировать корневую директорию, если она единственная: " . $skipRootCat1C . "\n" .
      "Записывать полное наименование: " . $writeFullName1C . "\n" .
      "Обновлять свойства: " . $updateStringProp1C . "\n" .
      $notUpdateText.
      "Доп. поля:" . "\n".
      $opFieldsText.
      "Записывать логи: " . $writeLog1C . "\n" .
      "Записывать файлы импорта и экспорта: " . $writeFiles1C;
    return $string;
  }

  /**
   * Конвертация этапа обмена в текстовое название
   *
   * @access private
   * @param string $mode
   * @return string
   */
  public function modeToHuman($mode)
  {
    //http://v8.1c.ru/edi/edi_stnd/131/
    switch ($mode) {
      case 'checkauth':
        return 'Начало сеанса';
        break;
      case 'init':
        return 'Запрос параметров от сайта';
        break;
      case 'file':
        return 'Выгрузка на сайт файлов обмена';
        break;
      case 'import':
        return 'Пошаговая загрузка данных';
        break;
      case 'query':
        return 'Получение файла обмена с сайта';
        break;
      case 'success':
        return 'Завершение сеанса';
        break;
      case 'applyWholesales':
        return 'Применение оптовых цен';
        break;
      default:
        return '<Неизвестный запрос>';
        break;
    }
  }

  /**
   * Метод для запоминания даты и времени начала обмена и запуска записи лога
   *
   * @access private
   * @return void
   */
  public function setDate()
  {
    if (!(isset($_SESSION['dateDay1C']) && isset($_SESSION['dateTime1C']))) {
      $_SESSION['dateDay1C'] = date('d_m_Y');
      $_SESSION['dateTime1C'] = date('H_i');
      if (!file_exists(TEMP_DIR.'log1c' . DS . $_SESSION['dateDay1C'] . DS . $_SESSION['dateTime1C'] . DS . 'info.txt')) {
        self::logGeneralProcess(self::createInfoLog());
        self::logGeneralProcess(self::getOptions(), 'SETTING');
      }
    }
  }

  /**
   * Метод для генерации начального блока логов
   *
   * @access private
   * @return string
   */
  public function createInfoLog()
  {
    $string =
      "********************************************************************\n".
      "* Сводная информация по синхронизации данных с 1С от ".date('d.m.Y')." г. *\n".
      "********************************************************************\n".
      "==========================[Обмен данными]===========================\n".
      "#SYNC]===================[/Обмен данными]===========================\n".
      "==========================[История запросов]========================\n".
      "#QUERY]==================[/История запросов]========================\n".
      "============================[Настройки]=============================\n".
      "#SETTING]===================[/Настройки]============================\n";
    return $string;
  }

  /**
   * Ассинхронный вызов
   * @access private
   */
  public function asyncRequest($mode) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SITE.'/exchange1c?mode='.$mode);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
  }

  /**
   * Применение оптовых цен по всем группам
   * @access private
   * @return void
   */
  public function applyWholesales() {
    $op1cPriceWholesales = unserialize(stripslashes(MG::getSetting('op1cPriceWholesales')));

    if (empty($op1cPriceWholesales)) {
      return false;
    }

    $opFieldsM = new Models_OpFieldsProduct('get');
    $fields = $opFieldsM->getFields();

    foreach ($op1cPriceWholesales as $key => $value) {
      if ($fields[$key]['is_price'] == 0) continue;
      $data = array();
      $data['field'] = $key;
      $data['group'] = $value['group'];
      $data['count'] = $value['count'];

      self::setMassiveop($data);
      self::logGeneralProcess("Применены оптовые цены для группы \"Оптовая цена ".$data['group']."\" из доп. поля ".$fields[$key]['name']." на количество ".$data['count'], "SYNC");
    }
  }

  /**
   * Применение одного правила оптовых цен
   * @access private
   */
  public function setMassiveop($data) {
    if($data['count'] == '') $data['count'] = 1;
    $tmp = array();

    if(empty($data['products'])) {
      $res = DB::query('SELECT id FROM '.PREFIX.'product');
      while($row = DB::fetchAssoc($res)) {
        $tmp[] = $row['id'];
      }
      $data['products'] = serialize($tmp);
      $data['prodCount'] = count($tmp);
      unset($tmp);
    }

    $data['products'] = unserialize($data['products']);
    foreach ($data['products'] as $key => $productId) {
      set_time_limit(30);

      $opFieldsM = new Models_OpFieldsProduct($productId);
      $fields = $opFieldsM->get($data['field']);
      $res = DB::query('SELECT id FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($productId).' 
				AND variant_id = 0 AND `group` = '.DB::quote($data['group']).' AND count = '.DB::quoteFloat($data['count']));
      if($row = DB::fetchAssoc($res)) {
        DB::query('UPDATE '.PREFIX.'wholesales_sys SET price = '.DB::quote($fields['value']).' WHERE id = '.DB::quoteInt($row['id']));
      } else {
        DB::query('INSERT INTO '.PREFIX.'wholesales_sys SET product_id = '.DB::quoteInt($productId).', variant_id = 0, 
					price = '.DB::quote($fields['value']).', count = '.DB::quoteFloat($data['count']).', `group` = '.DB::quote($data['group']));
      }
      if($fields['variant']) {
        foreach ($fields['variant'] as $item) {
          $res = DB::query('SELECT id FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($productId).' 
						AND variant_id = '.DB::quoteInt($item['id']).' AND `group` = '.DB::quote($data['group']).' AND count = '.DB::quote($data['count']));
          if($row = DB::fetchAssoc($res)) {
            DB::query('UPDATE '.PREFIX.'wholesales_sys SET price = '.DB::quote($item['value']).' WHERE id = '.DB::quoteInt($row['id']));
          } else {
            DB::query('INSERT INTO '.PREFIX.'wholesales_sys SET product_id = '.DB::quoteInt($productId).', variant_id = '.DB::quoteInt($item['id']).', 
							price = '.DB::quote($item['value']).', count = '.DB::quoteFloat($data['count']).', `group` = '.DB::quote($data['group']));
          }
        }
      }
      unset($data['products'][$key]);
    }

    return true;
  }

  private function fillSeo($id1c)
  {
    $updateSettings = unserialize(stripslashes(MG::getSetting('notUpdate1C')));
    $productsModel = new Models_Product();
    $whereClause = 'p.`1c_id` = '.DB::quote($id1c);
    $productsResult = $productsModel->getProductByUserFilter($whereClause);
    $product = reset($productsResult);
    if (empty($product)) {
      return false;
    }
    $short = MG::getSetting('currencyShort');
    $product['currency'] = $short[$product['currency_iso']];
    $meta = Seo::getMetaByTemplate('product', $product);
    $updateProductParts = [];
    foreach ([
      'meta_title',
      'meta_desc',
      'meta_keywords',
    ] as $field) {
      if (!empty($updateSettings['1c_'.$field]) && $updateSettings['1c_'.$field] === 'true') {
        $updateProductParts[] = '`'.$field.'` = '.DB::quote(htmlspecialchars($meta[$field]));
      }
    }
    if (empty($updateProductParts)) {
      return true;
    }
    $updateProductSql = 'UPDATE `'.PREFIX.'product` '.
      'SET '.implode(', ', $updateProductParts).' '.
      'WHERE `id` = '.DB::quote($product['id']).';';
    return DB::query($updateProductSql);
  }

  /**
   * Метод удаляет корневую директорию из 1c, если она единственная
   * Все дочерние для неё категории становятся корневыми
   * Все товары такой категории перемещаются в корень каталога
   * Метод работает только при включенной соответствующей опции
   */
  public function deleteRootCat() {
    if (MG::getSetting('skipRootCat1C') !== 'true') {
      return false;
    }
    $rootCatDataSql = 'SELECT COUNT(`id`) as cats_count, `id`, `1c_id`, `url` '.
      'FROM `'.PREFIX.'category` '.
      'WHERE (`parent` = 0 OR `parent` IS NULL) '.
      'AND `1c_id` != "" AND `1c_id` IS NOT NULL';
    $rootCatDataResult = DB::query($rootCatDataSql);
    if ($rootCatDataRow = DB::fetchAssoc($rootCatDataResult)) {
      if (intval($rootCatDataRow['cats_count']) !== 1) {
        return false;
      }
      $catId = intval($rootCatDataRow['id']);
      $cat1cId = $rootCatDataRow['1c_id'];
      $rootCatUrl = $rootCatDataRow['url'].'/';

      $deleteRootCatSql = 'DELETE FROM `'.PREFIX.'category` '.
        'WHERE `id` = '.DB::quoteInt($catId);
      DB::query($deleteRootCatSql);

      $setRootCatSql = 'UPDATE `'.PREFIX.'category` '.
        'SET `parent` = 0 '.
        'WHERE `parent` = '.DB::quoteInt($catId);
      DB::query($setRootCatSql);

      $fixParentUrlSql = 'UPDATE `'.PREFIX.'category` '.
        'SET `parent_url` = SUBSTRING(`parent_url`, '.(strlen($rootCatUrl) + 1).') '.
        'WHERE `parent_url` LIKE '.DB::quote($rootCatUrl.'%');
      DB::query($fixParentUrlSql);

      $setProductsCatSql = 'UPDATE `'.PREFIX.'product` '.
        'SET `cat_id` = 0 '.
        'WHERE `cat_id` = '.DB::quoteInt($catId);
      DB::query($setProductsCatSql);

      $_SESSION['import1c']['skippedRootCat'] = $cat1cId;
    }
  }
}
