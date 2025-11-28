<?php
/**
 * Класс LoggerAction - предназначен для отслеживания изменений в заказах и товарах
 *
 * Реализован в виде синглтона, что исключает его дублирование.
 *
 * @author Шевченко Александр Станиславович
 * @package moguta.cms
 * @subpackage Libraries
 */

class LoggerAction {

    static public $db;
    static private $_instance = null;
    static private $settings = false;
    static private $logPath = '';

  /**
   * Проверяет включен ли SQLite3 на сервере.
   * При отсутствии PHP библиотеки SQLite3 пишет ошибку в лог в директорию временных файлов сайта.
   * При включении проверяет существование файла БД на сервере и создает подключение к базе лога если находит файл.
   * При отсутсвии файла создает файл базы и подключение к ней.
   */
    private function __construct(){
      if(class_exists('SQLite3')){
        $res = MG::getOption('logger');
        if(!empty($res) && ($res == 'true')){
            self::$settings = true;
            self::$logPath = mg::createTempDir('user_log');
            
            if(!file_exists(self::$logPath . 'user_log.db')) {
                self::createDbFile();
            } else{
                $db = new SQLite3(self::$logPath . 'user_log.db');
                self::$db = $db;
            }
        }
      }else{
        return false;
        MG::loger('не работает SQLite3 на сервере','add','user_log');
      }
    }

  /**
   * Логер, вызывается в том месте, где нужно отследить логи.
   * @param string $essence что логируется (например, заказ или товар).
   * @param string $action название действия, которое совершается.
   * @param array $data данные, которые записываются.
   */
    public static function logAction($essence, $action, $data){
      self::getInstance();
      $res = MG::getOption('logger');
      if(file_exists(self::$logPath . 'user_log.db') && ($res == 'true') ) {
        //сперва вызвается метод подготовки данных для вставки а потом сама вставка
        $dataArray = self::prepareDataToJson($essence, $action, $data);
        $backtrace = debug_backtrace();
        $backtraceArr = array();
        foreach($backtrace as $trace){
          $backtraceArr[] = str_replace(SITE_DIR, '', $trace['file']).' (Строка: '.$trace['line'].')';
        }
        $dataArray['backtrace'] = json_encode($backtraceArr, JSON_UNESCAPED_UNICODE);
        if($dataArray !== false) {
          $res = self::getAction($dataArray);
          if($essence == 'Test'){
            if($res){
              return true;
            }else {
              return false;
            }
          }
        }
      }
    }

  /**
   * Метод создания таблицы в БД SQLite3 и установки соединения.
   */
    public static function createDbFile(){
      $db = new SQLite3(self::$logPath . 'user_log.db');
      self::$db = $db;
      $sql="CREATE TABLE user_action(
            id INTEGER PRIMARY KEY,
            user TEXT,
            email TEXT,
            role TEXT,
            number TEXT,
            essence TEXT,
            action TEXT,
            essence_id TEXT,
            data_new TEXT,
            data_old TEXT,
            backtrace TEXT,
            datetime TEXT              
        )";
      $db->query($sql);
    }

   /**
   * Осуществляет запрос в БД SQLite3.
    * @param string $sql Запрос в БД.
   */

    public static function sqlQuery($sql){
      return self::$db ->query($sql);
    }

    /**
   * Вставляет лог в БД SQLite3.
   * @param array $dataArray массив с данными
   */
    public static function getAction($dataArray){
      $user = User::getThis();
      if($user){
        $user_id = $user->id;
        $userEmail = $user->email;
        $role = self::getRoleByUser($user->role);
      } else {
        $user_id = ' ';
        $userEmail = ' ';
        $role = 'Гость';
      }

      if(!isset($dataArray['essence']) && !isset($dataArray['action'])){
        return false;
      }

      $dataArray['essence_id'] = !isset($dataArray['essence_id'])?'':$dataArray['essence_id'];
      $dataArray['data_new'] = !isset($dataArray['data_new'])?'':$dataArray['data_new'];
      $dataArray['data_old'] = !isset($dataArray['data_old'])?'':$dataArray['data_old'];

      //$date = MG::dateConvert(date("Y-m-d H:i:s"), true).' '.date("H:i:s");
      $date = date("Y-m-d H:i:s");
      if($dataArray['essence'] == 'Order'){
        $orderNumber = self::getOrderNumber($dataArray['essence_id']);
      } else{
        $orderNumber = ' ';
      }
           

      $sql = "INSERT INTO user_action( user, email, role, number, essence, action, essence_id, data_new, data_old, backtrace, datetime)
              VALUES ( 
              ".DB::quote($user_id).", ".DB::quote($userEmail).", ".DB::quote($role).", ".DB::quote($orderNumber).",
              ".DB::quote($dataArray['essence']).", ".DB::quote($dataArray['action']).", 
              ".DB::quote($dataArray['essence_id']).", '".$dataArray['data_new']."', 
              '".$dataArray['data_old']."', '".$dataArray['backtrace']."',".DB::quote($date).");";      
      return self::sqlQuery($sql);
    }

  /**
   * Подготавливает данные к вставке в БД SQLite3 (Данные хранятся в JSON формате)
   * Возвращает готовый ко вставке в БД SQLite3 массив.
   * @param string $essence что логируется (например, заказ или товар).
   * @param string $action название действия, которое совершается.
   * @param array $data данные, которые записываются.
   * @return array $dataArray подготовленные данные для вставки в БД.;
   */
    public static function prepareDataToJson($essence, $action, $data){
      if(is_array($data)){
       $orderId = $data['id'];
      }else {
       $orderId = $data;
      }
      $data = self::returnDataByAction($action, $data);
      if( $data !== false){
        if(isset($data['data_old']['sort']) && isset($data['data_new']['sort'])){
          if(((count($data['data_old'])==0)&&($action == 'updateProduct')) || (($data['data_old']['sort'] == 0) && ($data['data_new']['sort'] !== 0))){
            $action = 'createProduct';
          }
        }
        $dataArray = [
         'essence' => $essence,
         'action' => $action,
         'essence_id' => $orderId,
         'data_new' => json_encode($data['data_new'], JSON_UNESCAPED_UNICODE),
         'data_old' => json_encode($data['data_old'], JSON_UNESCAPED_UNICODE)
        ];
        return $dataArray;
      } else{
        return $data;
      }
    }
   /**
   * Возвращает все данные, котрые были изменены (Старые данные + новые данные)
   * @param string $action - Метод, который совершается
   * @param array $data - данные, которые записываются
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */
    public static function returnDataByAction($action, $data){
      switch ($action){
        case 'updateOrder';
          return self::updateOrder($data);
        case "updateCategory";
          return self::updateCategory($data);
        case 'updateProduct';
          return self::updateProduct($data);
        case 'fastUpdateProductVariant';
          return self::fastUpdateProductVariant($data);
        case 'fastUpdateProduct';
          return self::fastUpdateProduct($data);
        case 'updatePage';
          return self::updatePage($data);
        case 'updateUser';
          return self::updateUser($data);
        case 'addOrder';
          return self::addOrder($data);
        case 'addCategory';
          return self::addCategory($data);
        case 'addPage';
          return self::addPage($data);
        case 'addProduct';
          return self::addProduct($data);
        case 'addUser';
          return self::addUser($data);
        case 'delCategory';
          return self::deleteCategory($data);
        case 'delPage';
          return self::delPage($data);
        case 'deleteOrder';
          return self::deleteOrder($data);
        case 'deleteProduct';
         return self::deleteProduct($data);
        case 'deleteUser';
         return self::deleteUser($data);
        case 'test';
         return self::testData($data);
        case 'tryAuth';
         return self::tryAuth($data);  
        case 'auth'; 
         return self::auth($data);   
        case 'editSettings';
         return self::editSettings($data);   
        case 'activatePlugin';
         return self::LogPlugin($data);  
        case 'deactivatePlugin';
         return self::LogPlugin($data);
        case 'Send';
         return self::send($data); 
		case 'callBackupMethod';
         return self::callBackupMethod($data);  
        default: return self::defaultData($data); 
      }
    }

  /**
   * Проверяет изменения заказа
   * @param array $data - массив новых данных, которые логируются
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function updateOrder($data){
      $orderId = (int)$data['id'];
      $orderFormDb = self::getOrderFromDb($orderId);
      $newTempArr = unserialize(str_replace('\\', '', $data['order_content']));
 
      $data['order_content'] = serialize(self::unsetKeys($newTempArr));
      $orderFormDb['order_content'] = self::getOrderContent($orderId);
      $oldTempArr = unserialize(str_replace('\\', '', $orderFormDb['order_content']));
      $orderFormDb['order_content'] = serialize(self::unsetKeys($oldTempArr));
      $data = self::addingMissingKeysInData($orderFormDb, $data);
      return self::prepareDataOldAndDataNewArray($data, $orderFormDb);
    }


  /**
   * Проверяет изменения категории
   * @param array $data - массив новых данных, которые логируются
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function updateCategory($data){
      $categoryId = (int)$data['id'];
      $categoryFromDb = self::getCategoryFromDb($categoryId);
      $data = self::addingMissingKeysInData($categoryFromDb, $data);
      return self::prepareDataOldAndDataNewArray($data, $categoryFromDb);
    }

  /**
   * Проверяет изменения заказа
   * @param array $data - массив новых данных, которые логируются
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function updatePage($data){
      $pageId = $data['id'];
      $pageFromDb = self::getPageFromDb($pageId);
      $data = self::addingMissingKeysInData($pageFromDb, $data);
      return self::prepareDataOldAndDataNewArray($data, $pageFromDb);
    }

  /**
   * Проверяет изменения пользователя
   * @param array $data - массив новых данных, которые логируются
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function updateUser($data){
      $userId = $data['id'];
      $userFromDb = self::getUserFromDb($userId);
      $data = self::addingMissingKeysInData($userFromDb, $data);
      return self::prepareDataOldAndDataNewArray($data, $userFromDb);
    }

    /**
   * Отслеживает изменения товара
     * @param array $data - массив новых данных, которые логируются
     * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function updateProduct($data){
      $productId = (int)$data['id'];
      $productFromDb = self::getProductFromDb($productId);
      $data = self::addingMissingKeysInData($productFromDb, $data);
      if(is_array($data['userProperty'])){
        $data['userProperty'] = self::prepareUserProperty($data['userProperty'], $productId);
        $productFromDb['userProperty'] = self::getOldUserProperty($productId);
      }
      if(isset($data['variants'])){
        $productFromDb['variants'] = self::getOldUserVariants($productId);
        foreach ($data['variants'][0] as $key => $val){
          if(isset($data[$key])){
            unset($data[$key]);
            unset($productFromDb[$key]);
          }
        }
      }
      if(!(isset($data['title']))){
        return [
          'data_old' => [],
          'data_new' => $productFromDb
        ];
      } else {
        return self::prepareDataOldAndDataNewArray($data, $productFromDb);
      }
    }

  /**
   * *Подготавливает данные при быстром изменении вариантов товара
   * @param array $data - массив измененных данных варианта
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function fastUpdateProductVariant($data){
      $dataKeys = array_keys($data);
      $firsKey = $dataKeys[0];
      $productVariantsFromBd = self::getOldUserVariants($data['id']);
      $newProductVariants = $productVariantsFromBd;
      $i = 0;
      foreach ($productVariantsFromBd as $values){
        if($values['id'] == $data['variant_id']){
          $newProductVariants[$i][$firsKey] = $data[$firsKey];
        }
        $i++;
      }
      return [
        'data_old' => ['variants' => $productVariantsFromBd],
        'data_new' => ['variants' => $newProductVariants]
      ];
    }

  /**
   * *Подготавливает данные при быстром изменении товара
   * @param array $data - массив измененных данных товара
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function fastUpdateProduct($data){
      $dataKeys = array_keys($data);
      $firstKey = $dataKeys[0];
      $productFromDb = self::getProductFromDb($data['id']);
      $productNew = $productFromDb;
      $productNew[$firstKey] = $data[$firstKey];
      $productVariants = self::getOldUserVariants($data['id']);
      if(($productNew[$firstKey] == $productFromDb[$firstKey]) || (count($productVariants) > 0)) {
        return false;
      }else{
        return [
          'data_old' => [$firstKey => strval($productFromDb[$firstKey])],
          'data_new' => [$firstKey => strval($productNew[$firstKey])]
        ];
      }
    }

  /**
   * *Подготавливает данные при удалении товара
   * @param string $productId - Id товара
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */
    public static function deleteProduct($productId){
      $dataOld = self::getProductFromDb($productId);
      $dataNew = [];
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

  /**
   * *Подготавливает данные при удалении категории
   * @param string $categoryId - Id категории
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function deleteCategory($categoryId){
      $dataOld = self::getCategoryFromDb($categoryId);
      $dataNew = [];
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

  /**
   * *Подготавливает данные данных при удалении страницы
   * @param string $pageId - Id страницы
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function delPage($pageId){
      $dataOld = self::getPageFromDb($pageId);
      $dataNew = [];
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

  /**
   *Подготавливает данные при удалении пользователя
   * @param string $userId - Id пользователя
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function deleteUser($userId){
      $dataOld = self::getUserFromDb($userId);
      $dataNew = [];
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

   /**
   *Подготавливает данные при удалении заказа
    * @param string $orderId - Id заказа
    * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */
    public static function deleteOrder($orderId){
      $dataOld = self::getOrderFromDb($orderId);
      $dataNew = [];
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

   /**
   *  Подготавливает данные при добавлении заказа
    * @param string $orderId - Id заказа
    * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function addOrder($orderId){
      $dataOld = [];
      $dataNew = self::getOrderFromDb($orderId);
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

  /**
   * Подготавливает данные при добавлении категории
   * @param string $categoryId - Id категории
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function addCategory($categoryId){
      $dataOld = [];
      $dataNew = self::getCategoryFromDb($categoryId);
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

  /**
   * Подготавливает данные при добавлении страницы
   * @param string $pageId - Id страницы
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function addPage($pageId){
      $dataOld = [];
      $dataNew = self::getPageFromDb($pageId);
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

    public static function addProduct($productId){
      $dataOld = [];
      $dataNew = self::getProductFromDb($productId);
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

  /**
   * Подготавливает данные при добавлении пользователя
   * @param string $userId - Id пользователя
   * @return array $returnArray ['data_old'] - что было ['data_new'] - что стало
   */

    public static function addUser($userId){
      $dataOld = [];
      $dataNew = self::getUserFromDb($userId);
      $returnArray = [
        'data_new' => $dataNew,
        'data_old' => $dataOld
      ];
      return $returnArray;
    }

  /**
   * Поиск новых ключей в массиве входящих данных путем проверки на существование во входящих данных значений
   * @param array $arrayFromDb - массив из БД
   * @param array $data - массив входящих данных
   * @return array $data - дополненный массив входящих данных
   */


    public static function addingMissingKeysInData($arrayFromDb, $data){
      foreach ($arrayFromDb as $key => $value){
        if(!isset($data[$key])){
          $data[$key] = $value;
        }
      }
      return $data;
    }

  /**
   * Удаление из массива значений деталей заказа, которые не отображаются в плагине для просмотра логов
   * @param array $array - входящий массив
   * @return array $newArray - массив только с теми позициями, которые будут выводится в плагине
   */

    public static function unsetKeys($array){
      $newArray = array();
      $keyArray = ['id', 'name', 'code', 'count', 'price', 'fulPrice', 'property'];
      $i = 0;
      foreach ($array as $orderContents){
        foreach ($orderContents as $key => $value){
          foreach ($keyArray as $truKeys){
            if($key == $truKeys){
              $newArray[$i][$key] = strval($value);
            }
          }
        }
        $i++;
      }
      return $newArray;
    }

  /**
   * Сравнивает массив старых данных из базы с новыми данными, полученными при логировании. Емли между элементами массива есть разница, то мы их будем записывать в БД
   * @param array $arrayFromDb - массив из БД
   * @param array $data - массив новых данных при логировании
   * @return array $returnArray - ['data_old'] - что было ['data_new'] - что стало
   */
    public static function prepareDataOldAndDataNewArray($data, $arrayFromDb){
      $dataNewArray = array();
      $dataOldArray = array();
      foreach($arrayFromDb as $key => $val){
       if(!($arrayFromDb[$key] == $data[$key])){
          $dataNewArray[$key] = $data[$key];
        }
      }
      foreach ($dataNewArray as $key => $value){
        $dataOldArray[$key] = $arrayFromDb[$key];
      }
      $returnArray = array();
      $returnArray['data_old'] = $dataOldArray;
      $returnArray['data_new'] = $dataNewArray;
      return $returnArray;
    }

  /**
   * Подготавливает новые свойства товара для записи в логи
   * @param array $userPropertyArray - новые данные
   * @return array $returnArray - список с массивами свойств товара
   */

    public static function prepareUserProperty($userPropertyArray, $productId){
      $resultUserPropertyArray = array();
      $tempArray = array();
        foreach ($userPropertyArray as $key => $value) {
            $propertyId = (int) $key;
            switch ($value['type']) {
                case 'select':
                case 'checkbox':
                    unset($value['type']);
                    foreach ($value as $keyIn => $item) {
                        $toDB = array();
                        $toDB['id'] = $keyIn;
                        $toDB['prop_id'] = $propertyId;
                        $toDB['product_id'] = $productId;
                        $toDB['name'] = '';
                    }
                    break;
                case 'input':
                case 'textarea':
                    unset($value['type']);
                    foreach ($value as $keyIn => $item) {
                        $toDB = array();
                        $toDB['id'] = $keyIn;
                        $toDB['prop_id'] = $propertyId;
                        $toDB['product_id'] = $productId;
                        $toDB['name'] = $item['val'];
                        
                    }
                
                    break;
                case 'diapason':
                    $toDB = array();
                    if ($value['min']['val'] == '')
                        continue;
                    if (!isset($value['min']['id'])) {
                        $toDB['id'] = null;
                    } elseif (substr_count($value['min']['id'], 'tmp') == 0) {
                        $toDB['id'] = $value['min']['id'];
                    }
                    $toDB['prop_id'] = $propertyId;
                    $toDB['product_id'] = $productId;
                    $toDB['name'] = $value['min']['val'];
                    break;
            }
            $tempArray[] = $toDB;
            
        }
       $oldUserArr = self::getOldUserProperty($productId);
       $retArr = $oldUserArr;
       $i =0 ;
       foreach ($tempArray as $value){
           if(!in_array($value, $oldUserArr)){
               $temp = false;
               foreach ($oldUserArr as $val){
                   if(in_array($value['id'], $val)){
                       $temp = true;
                       break;
                   }
               }
               if($temp == true){
                   $retArr[$i]['name'] = $value['name'];
               } else {
                   $retArr[] = $value;
               }
           }
           $i++;
       }
       return $retArr; 
    }
    /**
   * Тестовая запись в логи
   * @param array $data - новые данные
   * @return array $returnArray - вывод данных в логи
   */
    
    public static function testData($data){
        $dataOld['test'] = $data;
        $dataNew['test'] = $data;
      return $returnArray = [
        'data_old' => $dataOld,
        'data_new' => $dataNew 
      ];
    }
    
   /**
   * При попытке авторизации под администратором
   * @param array $data - новые данные
   * @return array $returnArray - вывод ip в логи
   */
    
    public static function tryAuth($data){
        return $returnArray = [
        'data_old' => [],
        'data_new' => [
            'ip' => $data['ip'],
            'user'=> $data['user']
        ]
      ];
    }
    
   /**
   * При авторизации под администратором
   * @param array $data - новые данные
   * @return array $returnArray - вывод ip в логи
    *   */
    
    public static function auth($data){
        return $returnArray = [
        'data_old' => [],
        'data_new' => [
            'ip' => $data['ip']
        ]
      ];
    }
    
   /**
   * Сохранение настроек 
   * @param array $data - новые данные
   * @return array $returnArray - вывод данных в логи
   *   */
    
    public static function editSettings($data){
        if(is_array($data)){
            $dataNew = array();
            $dataOld = array();
            foreach ($data as $key => $value){
                if($key !== 'id'){
                    $sql = "SELECT value FROM ".PREFIX."setting WHERE `option` = ".DB::quote($key); 
                    $res = DB::query($sql);
                    $row = DB::fetchAssoc($res);
                    if(($row['value'] !== '') && ( $row['value'] != $value) && (!is_null($row['value']))){
                        $dataOld[$key] = $row['value'];
                        $dataNew[$key] = $value;    
                    }
                }
            } 
            if(count($dataNew)>0 && count($dataOld)>0){
               return $returnArray = [
                            'data_old' => $dataOld,
                            'data_new' => $dataNew
                ]; 
            } else {
                return false;
            }  
        }
               
    }
    /**
   * Включение или выключение плагина 
   * @param array $data - новые данные
   * @return array $returnArray - вывод данных в логи
   *   */
    public static function LogPlugin($data){
        $dataOld = ['pluginFolder' => $data['pluginFolder']];
        return $returnArray = [
                            'data_new' => $dataOld,
                            'data_old' => []
                ]; 
    }
    
    public static function send($data){
        return $returnArray = [
                            'data_new' => $data,
                            'data_old' => []
                ]; 
    }

    public static function callBackupMethod($data){
      return $returnArray = [
        'data_new' => $data,
        'data_old' => []
      ];
    }

        
    public static function defaultData($data){
        return [ 
            'data_new' => $data,
            'data_old' => []
        ];
    }

    /**
   * Получает старые свойства товара из БД
   * @param string $productId - ID товара
   * @return array $returnArray - список свойств товара
   */

    public static function getOldUserProperty($productId){
      $sql = "SELECT prop_id, id, name, product_id FROM ".PREFIX."product_user_property_data WHERE product_id = ".DB::quoteInt($productId);
      /*
      $sql = "SELECT prop_id, id, name, product_id 
                FROM ".PREFIX."product_user_property_data mpupd
                WHERE product_id = ".DB::quoteInt($productId)." 
                ORDER BY mpupd.id ASC"; */
      $res = DB::query($sql);
      $returnArray = array();
      while ($row = DB::fetchAssoc($res)){
        $returnArray[] = $row;
      }
      return $returnArray;
    }

    /**
   * Подготавливает варианты товара для записи в логи
   * @param string $productId - ID товара
   * @return array $returnArray - список вариантов товара
   */

    public static function getOldUserVariants($productId){
      $sql = "SELECT title_variant, code, price,  old_price, weight, count, activity, id, currency_iso, image
              FROM ".PREFIX."product_variant
              WHERE product_id = ".DB::quoteInt($productId)."
              ORDER BY id ASC";
      $res = DB::query($sql);
      $returnArray = array();
      while ($row = DB::fetchAssoc($res)){
        $returnArray[] = $row;
      }
      return $returnArray;
    }

  /**
   * Получает заказ из БД
   * @param string $orderId - ID заказа
   * @return array $order - массив с заказом
   */

    public static function getOrderFromDb($orderId){
      $modelOrder = new Models_Order();
      $order = $modelOrder->getOrder('id = '.DB::quote(intval($orderId)));
      return $order[$orderId];
    }

  /**
   * Получает категорию из БД
   * @param string $categoryId - ID категории
   * @return array $row - массив с категорией
   */
    public static function getCategoryFromDb($categoryId){
      $sql = "SELECT id, unit, title, url, parent, html_content, meta_title,
             meta_keywords, meta_desc, image_url, menu_icon, invisible,
             rate, seo_content, seo_alt, seo_title, parent_url, menu_title, weight_unit 
             FROM ".PREFIX."category
             WHERE id = ".DB::quoteInt($categoryId);
      $res = DB::query($sql);
      return $row = DB::fetchAssoc($res);
    }

  /**
   * Получает страницу из БД
   * @param string $pageId - ID страницы
   * @return array $row - массив со страницей
   */

    public static function getPageFromDb($pageId){
      $sql = "SELECT id, title, url, parent, html_content, meta_title,
              meta_keywords,meta_desc, invisible, parent_url
              FROM ".PREFIX."page WHERE id = ".DB::quoteInt($pageId);
      $res = DB::query($sql);
      return $row = DB::fetchAssoc($res);
    }


  /**
   * Получает пользователя из БД
   * @param string $userId - ID пользователя
   * @return array $res - массив с пользователем
   */
    public static function getUserFromDb($userId){
       $res = (array)User::getUserById($userId);
       return $res;
    }

  /**
   * Получает товар из БД
   * @param string $productId - ID товара
   * @return array $product - массив с товаром
   */

    public static function getProductFromDb($productId){
      $model = new Models_Product;
      $product = $model->getProduct($productId);
      //получить url изображения
      $sql = "SELECT image_url, weight, count, code, old_price, sort FROM ".PREFIX."product WHERE id = ".DB::quoteInt($productId);
      $res = DB::query($sql);
      $row = DB::fetchAssoc($res);
      $product['image_url'] = $row['image_url'];
      return $product;
    }

    /**
     * Получает деталей заказа из БД
     * @param string $orderId - ID заказа
     * @return array $row - массив с деталями заказа
     */

    public static function getOrderContent($orderId){
      $sql = "SELECT order_content
              FROM ".PREFIX."order
              WHERE id = ".DB::quoteInt($orderId);
      $res = DB::query($sql);
      $row = DB::fetchAssoc($res);
      return $row['order_content'];
    }

  /**
   * Получает номер заказа по ID
   * @param string $orderId - ID заказа
   * @return array $row - массив с номером заказа
   */

    public static function getOrderNumber($orderId){
      $sql = "SELECT number 
              FROM ".PREFIX."order
              WHERE id = ".DB::quoteInt($orderId);
      $res = DB::query($sql);
      $row = DB::fetchAssoc($res);
      return $row['number'];
    }

  /**
   * Получает группу пользователя из БД по ID
   * @param string $orderId - ID группы
   * @return array $row - массив с названием группы
   */

    public static function getRoleByUser($roleId){
      $sql = "SELECT name 
              FROM ".PREFIX."user_group
              WHERE id = ".DB::quoteInt($roleId);
      $res = DB::query($sql);
      $row = DB::fetchAssoc($res);
      return $row['name'];
    }

    /**
     * Возвращает единственный экземпляр данного класса.
     * @access private
     * @return object объект класса LoggerAction.
     */
    static public function getInstance() {
      if(is_null(self::$_instance)) {
        self::$_instance = new self;
      }
      return self::$_instance;
    }

    /**
   * Заносит в БД данные об ajax запросе
   */

  public static function logAjaxRequest()
  {
    if (!defined("AJAX_LOGGING")) return;
    if (AJAX_LOGGING != 1) return;

    // url исходного запроса до знака '?'
    $request = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'])[0];
    $params = array_merge($_POST, $_GET);

    // убираем лишний параметр с временной меткой
    if (isset($params['_'])) unset($params['_']);

    // сортировка параметров по ключам, для дальнейшей проверки на уникальность набора параметров
    ksort($params);
    $params = self::ksortRecursive($params);

    // достаем данные для роутинга
    $controller = explode('=', $_SERVER['QUERY_STRING'])[1];
    $action = isset($params['action']) ? $params['action'] : '';
    $actioner = isset($params['actionerClass']) ? $params['actionerClass'] : '';
    $handler = isset($params['pluginHandler']) ? $params['pluginHandler'] : '';
    $mguniqueurl = isset($params['mguniqueurl']) ? $params['mguniqueurl'] : '';
    $requestType = $_SERVER['REQUEST_METHOD'];

    // обработка данных из запроса и представление их в нужном виде
    $example = []; // все параметры, в виде url строки
    $ajax = []; // параметры, кроме нужных для роутинга, заменены на _sql_, в виде url строки

    $strParams = self::requestParamsToStringRecursive($params);
    $example = $strParams;

    foreach ($strParams as $key => $value) {
      if (in_array($key, ['action', 'actionerClass', 'pluginHandler', 'mguniqueurl'])) {
        unset($strParams[$key]);
        $ajax[$key] = $value;
      } else {
        $ajax[$key] = '_sql_';
      }
    }

    $ajax = $request . '?' . http_build_query($ajax);
    $example = $request . '?' . http_build_query($example);;
    $params = implode('&', array_keys($strParams));

    // проверка на существования такого же запроса
    DB::query("SELECT * FROM `" . PREFIX . "logs_ajax` WHERE `params`=" . DB::quote($params) . " AND `actioner`=" . DB::quote($actioner) . " AND `action`=" . DB::quote($action) .
      " AND `handler`=" . DB::quote($handler) . " AND `mguniqueurl`=" . DB::quote($mguniqueurl) . " AND `requestType`=" . DB::quote($requestType));

    if (DB::affectedRows() == 0) {
      DB::query("INSERT INTO `" . PREFIX . "logs_ajax`(`ajax`,`action`,`actioner`,`handler`,`mguniqueurl`, `params`, 
        `example`, `controller`,`requestType`) VALUES(" . DB::quote($ajax) . ", " . DB::quote($action) . ", " .
        DB::quote($actioner) . ", " . DB::quote($handler) . ", " . DB::quote($mguniqueurl) . ", " . DB::quote($params) . ", " .
        DB::quote($example) . ", " . DB::quote($controller) . ", " . DB::quote($requestType) . ")");
    }
  }

  /**
   * Генерирует одномерный массив параметров для url запроса из представленного многомерного массива
   *
   * @param array $array
   * @param string $prefix
   * @return array
   */
  private static function requestParamsToStringRecursive($array, $prefix = '')
  {
    $res = [];

    foreach ($array as $key => $value) {
      if (is_array($value)) {
        if ($prefix == '') {
          $temp = self::requestParamsToStringRecursive($value, $key);
        } else {
          $temp = self::requestParamsToStringRecursive($value, $prefix . '[' . $key . ']');
        }

        $res = array_merge($res, $temp);
      } else {
        if ($prefix == '') {
          $res[$key] = $value;
        } else {
          $res[$prefix . '[' . $key . ']'] = $value;
        }
      }
    }

    return $res;
  }


  /**
   * Рекурсивная сортировка массива по ключам
   *
   * @param array $array
   * @return array
   */
  private static function ksortRecursive($array)
  {
    $res = [];

    foreach ($array as $key => $value) {
      if (is_array($value)) {
        ksort($value);
        $res[$key] = self::ksortRecursive($value);
      } else {
        $res[$key] = $value;
      }
    }

    return $res;
  }
}
