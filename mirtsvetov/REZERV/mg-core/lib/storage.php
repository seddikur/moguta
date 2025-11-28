<?php

/**
 * Класс Storage - предназначен для кэширования блоков данных (объектов, массивов, строк), используемых для генерации страницы. Позволяет работать с сервером memcache.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Storage{

  static private $_instance = null;
  static private $cacheTime = null;
  static private $sessionLifeTime = null;
  static private $sessionToDB = false;
  static public $noCache = null;
  static public $cacheMode = null;
  static public $memcache_obj = null;
  static public $max_allowed_packet = null;
  static public $cachePrefix = null;

  /**
   * Окрытие сессии.
   * @access private
   * @return bool true
   */
  public function sessionOpen($savePath, $sessionName) {
    if (!self::$sessionToDB) {
      global $sess_save_path, $sess_session_name;
      $sess_save_path = $savePath;
      $sess_session_name = $sessionName;
    }
    
    return true;
  }

  /**
   * Закрытие сессии.
   * @access private
   * @return bool true
   */
  public function sessionClose() {
    $this->sessionGc(self::$sessionLifeTime);
    DB::close();
    return true;
  }

  /**
   * Считывание сессии.
   * @access private
   * @param string $id
   * @return string
   */
  public function sessionRead($id) {
    if (self::$sessionToDB) {
      // чтение из базы
      $res = DB::query("SELECT `session_data` FROM `".PREFIX."sessions`
                              WHERE `session_id` = ".DB::quote($id));

      if($row = DB::fetchArray($res)) {
        return $row[0];
      }
    } else {
      global $sess_save_path, $sess_session_name;
      $sess_file = "$sess_save_path/sess_$id";
      
      if($fp = @fopen($sess_file, "r")) {
        $sess_data = fread($fp, filesize($sess_file));
        return($sess_data);
      }
    }
    
    return "";
  }

  /**
   * Запись сессии.
   * @access private
   * @param string $id id сессии
   * @param string $sess_data данные сессии
   * @return bool
   */
  public function sessionWrite($id, $sess_data) {
    if (isset($_POST['a']) && $_POST['a'] == 'ping') {
      $this->sessionGc(self::$sessionLifeTime);
      return false;
    }
    
    if (self::$sessionToDB) {
      //Запись в базу
      DB::query("
        REPLACE INTO `".PREFIX."sessions` (session_id,session_expires,session_data) 
          VALUES(".DB::quote($id).",".time().",".DB::quote($sess_data).")");

      if(DB::affectedRows()) {
        return true;
      }
    }

    return false;
  }

  /**
   * Уничтожение сессии.
   * @access private
   * @param string $id сессии
   * @return bool
   */
  public function sessionDestroy($id) {
    if (self::$sessionToDB) {
      // удаление из базы файла
      DB::query("DELETE FROM ".PREFIX."sessions WHERE session_id = ".DB::quote($id)); 

      if(DB::affectedRows()) {
        return true;
      }
    }
    
    return false; 
  }

  /**
   * Чистильщик мусора.
   * @access private
   * @param int life time (sec.)
   * @return int
   * @see session.gc_divisor      100
   * @see session.gc_maxlifetime 1440
   * @see session.gc_probability    1
   * @usage execution rate 1/100
   *        (session.gc_probability/session.gc_divisor)
   */
  public function sessionGc($maxlifetime) {
    if (self::$sessionToDB) {
      DB::query("
      DELETE FROM ".PREFIX."sessions
      WHERE `session_expires`+".DB::quoteInt($maxlifetime, true)." <= ".time());

      return DB::affectedRows();
    }
  }
  
  /**
   * Проверка сессии на законченость.
   * @access private
   * @param string $id id сессии
   * @return string
   */
  public static function getSessionExpired($id) {
    if (self::$sessionToDB) {
      $res = DB::query("SELECT `session_expires` FROM `".PREFIX."sessions`
                            WHERE `session_id` = ".DB::quote($id));

      if ($row = DB::fetchArray($res)) {
        return $row['session_expires'];
      }
    } else {
      $sess_save_path = session_save_path();
      $sessFile = $sess_save_path."/sess_".$id;
      return filemtime($sessFile);
    }
    
    return "";
  }

  private function __construct($settings) {
    
    $sessLifeTime = ini_get("session.gc_maxlifetime");
    self::$sessionLifeTime = (empty($sessLifeTime)) ? 1440 : $sessLifeTime;
    
    if ($settings['sessionToDB']=='true') {
      self::$sessionToDB = true;
      self::$sessionLifeTime = ($settings['sessionLifeTime'] < 1440) ? 1440 : $settings['sessionLifeTime'];
    }
    if ($settings['sessionToDB']=='true' || (isset($_POST['a']) && $_POST['a'] == 'ping')) {
      session_set_save_handler(
        array($this, "sessionOpen"), 
        array($this, "sessionClose"), 
        array($this, "sessionRead"),
        array($this, "sessionWrite"), 
        array($this, "sessionDestroy"), 
        array($this, "sessionGc")
      );
    }
    session_id() != ''?:session_start();

    $cacheObject = false;
    $cacheTime = 86400;
    $cacheHost = '';
    $cachePort = '';
    $cachePrefix = '';

    if ($settings['cacheObject'] == 'true') {
      $cacheObject = true;
    }
    if (intval($settings['cacheTime'])) {
      $cacheTime = intval($settings['cacheTime']);
    }
    if ($settings['cacheHost']) {
      $cacheHost = $settings['cacheHost'];
    }
    if ($settings['cachePort']) {
      $cachePort = $settings['cachePort'];
    }
    if ($settings['cachePrefix']) {
      $cachePrefix = $settings['cachePrefix'];
    }

    define('CACHE', $cacheObject);
    define('CACHE_TIME', $cacheTime);
    define('CACHE_HOST', $cacheHost);
    define('CACHE_PORT', $cachePort);
    define('CACHE_PREFIX', $cachePrefix);

    self::$noCache = !CACHE;
    self::$cacheMode = $settings['cacheMode']; // DB or FILE or MEMCACHE
    self::$cacheTime = CACHE_TIME;
    self::$cachePrefix = defined('CACHE_PREFIX')?CACHE_PREFIX:'';

    if(self::$cacheMode=='MEMCACHE') {
      if(class_exists('Memcached')) {
        self::$memcache_obj = new Memcached(); 
        self::$memcache_obj->addServer(CACHE_HOST, CACHE_PORT); 
        self::$memcache_obj->OPT_COMPRESSION = true;
        $ver = self::$memcache_obj->getVersion();
        if (empty($ver)) {
          echo 'Ошибка подключения к серверу MEMCACHE (скорее всего неправильно указаны сервер и порт), тип кэширования временно изменен на базу данных.';
          $settings['cacheMode'] = 'DB';
        }
      } else {
        if(class_exists('Memcache')) {
          self::$memcache_obj = new Memcache;
          self::$memcache_obj->connect(CACHE_HOST, CACHE_PORT);
          $ver = self::$memcache_obj->getVersion();
          if (empty($ver)) {
            echo 'Ошибка подключения к серверу MEMCACHE (скорее всего неправильно указаны сервер и порт), тип кэширования временно изменен на базу данных.';
            $settings['cacheMode'] = 'DB';
          }
        }
      }
    }

    if($cacheMode = $settings['cacheMode']) {
      self::$cacheMode = $cacheMode;
      define('CACHE_MODE', $cacheMode);
    };

    if(self::$cacheMode=='DB') {
      $result = DB::query("SHOW VARIABLES LIKE 'max_allowed_packet' ");
      if($row = DB::fetchAssoc($result)) {
        self::$max_allowed_packet = $row['Value'];
      }
    }

    if(self::$cacheMode == 'FILE') {
      if(!file_exists('mg-cache')){
        @mkdir('mg-cache', 0755);
       }
      if(!file_exists('mg-cache')) {
        echo 'Закрыты права на запись! Использование файлового кэша невозможно! Установите права на папку с сайтом 755.';
      }
    }
    
    // чистка файлового мусора
    if(self::$cacheMode == 'FILE') {
      // алгоритм проверки устаревших файлов включаеться раз в 20 минут
      if($settings['lastTimeCacheClear'] + 60 * 20 < time()) {
        $scan = array_diff(scandir('mg-cache'), array('..', '.'));
        foreach ($scan as $file) {
          if(filemtime('mg-cache/'.$file) + $settings['sessionLifeTime'] < time()) {
            @unlink('mg-cache/'.$file);
          }
        }
        // записываем метку времени последней чистки
        $res = DB::query('SELECT id FROM '.PREFIX.'setting WHERE `option` = \'lastTimeCacheClear\'');
        if($row = DB::fetchAssoc($res)) {
          DB::query('UPDATE '.PREFIX.'setting SET value = '.DB::quoteInt(time()).' WHERE id = '.DB::quoteInt($row['id']));
        } else {
          DB::query('INSERT INTO '.PREFIX.'setting SET `option` = \'lastTimeCacheClear\', `value` = '.DB::quoteInt(time()));  
        }        
      }
    }

    // чистка кэша с базу, раз в 5 минут
    if(self::$cacheMode=='DB') {
      if($settings['lastTimeCacheClear'] + 60 * 5 < time()) {
        DB::query('DELETE FROM '.PREFIX.'cache WHERE lifetime < '.DB::quoteInt(time()));
        // записываем метку времени последней чистки
        $res = DB::query('SELECT id FROM '.PREFIX.'setting WHERE `option` = \'lastTimeCacheClear\'');
        if($row = DB::fetchAssoc($res)) {
          DB::query('UPDATE '.PREFIX.'setting SET value = '.DB::quoteInt(time()).' WHERE id = '.DB::quoteInt($row['id']));
        } else {
          DB::query('INSERT INTO '.PREFIX.'setting SET `option` = \'lastTimeCacheClear\', `value` = '.DB::quoteInt(time()));  
        }        
      }
    }

    if (isset($_SESSION['debugLogSQL']) && $_SESSION['debugLogSQL'] == 'true') {
      DB::$_debugMode = 1;
    }
  }

  private function __clone() {
    
  }

  private function __wakeup() {
    
  }

  /**
   * Возвращает единственный экземпляр данного класса.
   * <code>
   * $obj = Storage::getInstance();
   * </code>
   * @return obj объект класса Storage
   */
  static public function getInstance($settings=array()) {
    if(is_null(self::$_instance)) {
      self::$_instance = new self($settings);
    }
    return self::$_instance;
  }

  /**
   * Инициализирует единственный объект данного класса.
   * @access private
   * @return obj объект класса Storage
   */
  public static function init($settings=array()) {
    self::getInstance($settings);
  }

  /**
   * Хук для изменения сохраняемых в кэш значений
   *
   * @param string $name ключ
   * @param string $value значение
   * @return string
   */
  static function checkValue($name, $value) {
    $args = func_get_args();
    return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $value, $args);
  }

  /**
   * Сохраняет данные в формате ключ-значение.
   * <code>
   *   $array = array('сложный', 'массив', 'для', 'записи', 'в', 'кэш');
   *   $res = Storage::save('cacheName', $array);
   *   var_dump($res);
   * </code>
   * @param string $name ключ
   * @param array|string $value значение
   * @return bool true или false
   */
  public static function save($name, $value, $lifetime = 0) {
    $value = self::checkValue($name, $value);

    $name .= '-'.$_SERVER['SERVER_NAME'];
    if(self::$noCache || !$value) {
      return false;
    }

    if ($lifetime === 0 || !is_numeric($lifetime)) {
      $lifetime = self::$cacheTime;
    }

    if(is_array($value)||is_string($value)) {

      if(self::$cacheMode=='FILE') {
        @mkdir('mg-cache', 0755);
        file_put_contents('mg-cache/'.strtolower($name).'.txt', serialize($value));
        return true;
      }

      if(self::$cacheMode=='DB') {
        $cacheArray = array(
          'date_add'=>time(), // 20 минут 
          'lifetime'=>time()+$lifetime, // 20 минут 
          'name'=>$name,
          'value'=>addslashes(serialize($value)),
        );
    
        $sql = '
          INSERT INTO `'.PREFIX.'cache` SET '.DB::buildPartQuery($cacheArray).'
          ON DUPLICATE KEY UPDATE 
            lifetime = '.$cacheArray['lifetime'].',
            value = "'.$cacheArray['value'].'"';


        if((strlen($sql)+1024)<self::$max_allowed_packet) {
          DB::query($sql);
        }else{
          echo "<div style='padding: 10px;color: #A94442;border: 1px solid #EBCCD1;background: #F2DEDE; font-size: 14px;position:fixed; left: 10px;right: 10px;bottom: 10px; z-index: 111;border-radius: 3px;line-height: 21px;'>Значение директивы <strong>max_allowed_packet = ".self::$max_allowed_packet."</strong> на вашем MySQL слишком мало! Кеширование в базу невозможно! Для устранения ошибки увеличьте <strong>max_allowed_packet</strong> или используйте тип кеширования <strong>memcache</strong> (рекомендуется)</div>";
        }
      }

      if(self::$cacheMode=='MEMCACHE') {
        if(class_exists('Memcached')) {
          self::$memcache_obj->set(self::$cachePrefix.$name, $value, $lifetime);
        } else {
          if(class_exists('Memcache')) {
            self::$memcache_obj->set(self::$cachePrefix.$name, $value, MEMCACHE_COMPRESSED, $lifetime);
          }
        }
      }
    } else {
      //echo 'Ошибка: невозможно создать кэш объекта!';
      return false;
    }

    return true;
  }

  /**
   * Возвращает сохраненный ранее объект из кэша.
   * <code>
   *   $res = Storage::get('cacheName');
   *   viewData($res);
   * </code>
   * @param string $name ключ.
   * @return mixed закэшированное представление объекта или null.
   */
  public static function get($name) {
    $name .= '-'.$_SERVER['SERVER_NAME'];
    if(self::$noCache) {
      return null;
    }

    if(self::$cacheMode == 'FILE') {

      if(file_exists('mg-cache/'.strtolower($name).'.txt')){
        return unserialize(file_get_contents('mg-cache/'.strtolower($name).'.txt'));
      }else{
        return null;
      }

    }

    if(self::$cacheMode == 'MEMCACHE') {
      if(class_exists('Memcached')) {
        return self::$memcache_obj->get(self::$cachePrefix.$name);
      } else {
        if(class_exists('Memcache')) {
          return self::$memcache_obj->get(self::$cachePrefix.$name);
        }
      }
    }

    if(self::$cacheMode == 'DB') {
      $result = DB::query('
        SELECT `value` 
        FROM `'.PREFIX.'cache`
        WHERE name='.DB::quote($name)."
        AND `lifetime` >= ".time());


      if($row = DB::fetchAssoc($result)) {
        $res = unserialize(stripslashes($row['value']));
        return $res;
      }
    }
    return null;
  }

  /**
   * Очищает кэш для всех или определенного объекта.
   * <code>
   *   // чистит от указанный кэш
   *   $res = Storage::clear('cacheName');
   *   var_dump($res);
   *   // чистит весь кэш
   *   $res = Storage::clear();
   *   var_dump($res);
   * </code>
   * @param string $object ключ объекта
   * @return bool true
   */
  public static function clear($object = '') {

    if(self::$cacheMode=='FILE') {
      if($object == '') {
        $scan = array_diff(scandir('mg-cache'), array('..', '.'));
        foreach ($scan as $object) {
          $objectPath = SITE_DIR.'mg-cache'.DS.$object;
          if (is_dir($objectPath)) {
            MG::rrmdir($objectPath, true);
          } else {
            @unlink($objectPath);
          }
        }
        // при полном сбросе кэша, метку времени подальше ставим, смысла проверять файлы в ближайшее время нет
        MG::setOption('lastTimeCacheClear', time() + MG::getSetting('sessionLifeTime') * 0.9);
      } else {
        $scan = [];
        if (is_dir('mg-cache')) {
          $scan = array_diff(scandir('mg-cache'), ['..', '.']);
        }
        foreach ($scan as $file) {
          foreach (func_get_args() as $key) {
            if(substr_count($file, strtolower($key))) {
              @unlink('mg-cache/'.$file);
            }
          }
        }
      }
    }

    if(self::$cacheMode=='MEMCACHE') {
      if(class_exists('Memcached')) {
        self::$memcache_obj->flush();
      } else {
        if(class_exists('Memcache')) {
          self::$memcache_obj->flush();
        }
      }
    }

    if($object != '') {
      $like = ' WHERE name LIKE "'.DB::quote($object, true).'%"';
    } else {
      $like = " WHERE `name` NOT LIKE 'mp-cache-%'";
    }

    if(self::$cacheMode=='DB') {
      $result = DB::query("DELETE FROM `".PREFIX."cache` ".$like);
    }

    // вместе с кэшем блоков, скидываем и кеш стилей с js.
    // т.к. в админке используется путь для стандартного шаблона, то создаем путь для сброса КЭШа исходя из настроек, какой шаблон сейчас выбран
    MG::clearMergeStaticFile('mg-cache/'.MG::getSetting('templateName').'/cache/');

    return true;
  }

  /**
   * Закрывает соединение с сервером memcache.
   * @access private
   * @return bool true
   */
  public static function close() {
    if(self::$cacheMode=='MEMCACHE') {
      if(class_exists('Memcached')) {
        self::$memcache_obj->quit();
      } else {
        if(class_exists('Memcache')) {
          self::$memcache_obj->close();
        }
      }
    }
    return true;
  }
  
  /**
   * Возвращает продолжительность сессии.
   * <code>
   * echo Storage::getSessionLifeTime();
   * </code>
   * @return int длительность сессии
   */
  public static function getSessionLifeTime() {
    return self::$sessionLifeTime;
  }
  
  /**
   * Сохраняет данные в формате ключ-значение в системной директории.
   * <code>
   *   $array = array('сложный', 'массив', 'для', 'записи', 'в', 'кэш');
   *   $res = Storage::saveSystem('cacheName', $array);
   *   var_dump($res);
   * </code>
   * @param string $name ключ
   * @param array|string $value значение
   * @return bool true или false
   */
  public static function saveSystem($name, $value, $lifetime = 0) {
    $value = self::checkValue($name, $value);

    if(self::$noCache || !$value) {
      return false;
    }

    if ($lifetime === 0 || !is_numeric($lifetime)) {
      $lifetime = self::$cacheTime;
    }

    if(is_array($value)||is_string($value)) {
      
      @mkdir('mg-cache-sys', 0777);
      $value = str_replace([SITE, SITE_DIR], ['#PATCH_SITE#', '#PATCH_SITE_DIR#'], $value);
      $value = json_encode($value);
      $name = preg_replace('/[^A-Za-z0-9\/]/','_',$name);
      $filename = explode('/',$name);
      @mkdir('mg-cache-sys/'.strtolower($name), 0777, true);
      file_put_contents('mg-cache-sys/'.strtolower($name).'/'.end($filename).'.txt', $value);
      
      return true;
    } else {
      //echo 'Ошибка: невозможно создать кэш объекта!';
      return false;
    }

    return true;
  }
  

  /**
   * Возвращает сохраненный ранее объект из системного кэша.
   * <code>
   *   $res = Storage::getSystem('cacheName');
   *   viewData($res);
   * </code>
   * @param string $name ключ.
   * @return mixed закэшированное представление объекта или null.
   */
  public static function getSystem($name) {
    $name = preg_replace('/[^A-Za-z0-9\/]/','_',$name);
    $filename = explode('/',$name);
    $value = @file_get_contents('mg-cache-sys/'.strtolower($name).'/'.end($filename).'.txt');
    $value = str_replace(['#PATCH_SITE#', '#PATCH_SITE_DIR#'], [addslashes(SITE), addslashes(SITE_DIR)], $value);
    $value = json_decode($value,true);
    return $value;
  }


}
