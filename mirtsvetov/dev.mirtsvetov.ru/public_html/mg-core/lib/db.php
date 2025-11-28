<?php

/**
 * Класс DB - предназначен для работы с базой данных.
 * Доступен из любой точки программы.
 * Реализован в виде синглтона, что исключает его дублирование.
 * Все запросы выполняемые в коде движка должны обязательно проходить через метод DB::query() данного класса, а параметры запроса  экранироваться методом DB::quote();
 * - Создает соединение с БД средствами mysqli;
 * - Защищает базу от SQL инъекций;
 * - Ведет логирование запросов если установленна данная опция;
 * 
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class DB {

  static private $_instance = null;
  static private $log = null;
  static private $lastQuery = null;
  static public $connection = null;
  static public $_debugMode = DEBUG_SQL;
  static public $count_sql = 0;
  static public $timeout = 0;
  static private $connected = false;
 

  private function __construct() {
  
  	$hostAndPort = explode(':', HOST);
  	$port = null;
  	$host = HOST;
  	if(!empty($hostAndPort[1])){
  	  $port = $hostAndPort[1];
  	  $host = $hostAndPort[0];
  	}
		
    self::$connection = new mysqli($host, USER, PASSWORD, NAME_BD, $port);
		
    if (self::$connection->connect_error) {
      die('Ошибка подключения ('.self::$connection->connect_errno.') '
        .self::$connection->connect_error);
    }
    self::$connected = true;

    if(@SQL_MODE != 1) {
      $findMode = false;
      $mode = array();
      $res = self::query('SELECT @@sql_mode ',false,true);
      if($row = self::fetchAssoc($res)) {
        $mode = explode(',', $row['@@sql_mode']);
      }
      foreach ($mode as $key => $value) {
        if(in_array($value, array('ONLY_FULL_GROUP_BY','STRICT_ALL_TABLES','STRICT_TRANS_TABLES'))) {
          unset($mode[$key]);
          $findMode = true;
        }
      }
      DB::query('SET @@sql_mode='.DB::quote(implode(',', $mode)),false,true);
      if(@SQL_MODE == 'SQL_MODE') {
        $str = "\r\n";
        $str .= "; Если у базы данных стоит строгий режим, то значение 0 исправит ситуацию, при значении 1 движок будет работать как раньше \r\n";
        if($findMode) $str .= "SQL_MODE = 0\r\n"; else $str .= "SQL_MODE = 1\r\n";
        file_put_contents('config.ini', $str, FILE_APPEND);
      } 
      // else {
      //   $config = file_get_contents('config.ini');
      //   if($findMode) $mode = 0; else $mode = 1;
      //   $config = str_replace('SQL_MODE = '.SQL_MODE, 'SQL_MODE = '.$mode, $config);
      //   file_put_contents('config.ini', $config);
      // }
    }

  }

  private function __clone() {
    
  }

  private function __wakeup() {
    
  }

  /**
   * Строит часть запроса из полученного ассоциативного массива.
   * <code>   
   * $array = (
   *   'login' => 'admin',
   *   'pass' => '1',
   * );
   * // преобразует массив в строку: "'login' = 'admin', 'pass' = '1'"
   * DB::buildPartQuery($array); 
   * </code>  
   * @param array $array ассоциативный массив полей с данными.
   * @param string $devide разделитель.
   * @return string
   */
  public static function buildPartQuery($array, $devide = ',') {
    $partQuery = '';

    if (is_array($array)) {
      $partQuery = '';
      foreach ($array as $index => $value) {
        if(is_array($value)){
          $value="";
        }
        if(preg_match('~^\-?(^(\-?0(\.|$))|^\-?[1-9]+\d*\.?)(\d{0,})?$~',trim($value))){
          $partQuery .= ' `'.self::quote($index,true).'` = '.self::quote($value,true).''.$devide;
        }else{
          $partQuery .= ' `'.self::quote($index,true).'` = "'.self::quote($value,true).'"'.$devide;
        }
      }
      $partQuery = trim($partQuery, $devide);     
    }
    return $partQuery;
  }

  /**
   * Строит часть запроса из полученного ассоциативного массива и исполняет запрос.
   * <code>
   * $array = array(
   *   'parent_url' => '',
   *   'parent' => 0,
   *   'title' => 'Блог',
   *   'url' => 'blog',
   *   'html_content' => 'Наш блог'
   * );
   * DB::buildQuery("INSERT INTO `".PREFIX."page` SET ", $array);
   * </code>
   * @param string SQL запрос.
   * @param array $array ассоциативный массив.
   * @param string $devide разделитель
   * @return obj|bool
   */
  public static function buildQuery($query, $array, $devide = ',') {

    if (is_array($array)) {
      $partQuery = '';

      foreach ($array as $index => $value) {   
        if(is_array($value)){
          $value="";
        }
        if(is_numeric($value)){
          if(preg_match('~^\-?(^(\-?0(\.|$))|^\-?[1-9]+\d*\.?)(\d{0,})?$~',trim($value))){
            $partQuery .= ' `'.self::quote($index,true).'` = '.self::quote($value,true).''.$devide;
          }else{
            $partQuery .= ' `'.self::quote($index,true).'` = "'.self::quote($value,true).'"'.$devide;
          }
        }else{
          $partQuery .= ' `'.self::quote($index,true).'` = "'.self::quote($value,true).'"'.$devide;
        }
      }

      $partQuery = trim($partQuery, $devide);
      $query .= $partQuery;

      return self::query($query);
    }
    return false;
  }

  /**
   * Возвращает ряд результата запроса в виде ассоциативного массива.
   * <code>
   * $res = DB::query($sql);
   * while ($row = DB::fetchAssoc($res)) {
   *   viewdata($row);
   * }
   * </code>
   * @param obj $object
   * @return array
   */
  public static function fetchAssoc($object) {
    return @mysqli_fetch_assoc($object);
  }

  /**
   * Возвращает ряд результата запроса в виде объекта.
   * <code>
   * $res = DB::query($sql);
   * while ($row = DB::fetchObject($res)) {
   *   viewdata($row);
   * }
   * </code>
   * @param obj $object
   * @return obj
   */
  public static function fetchObject($object) {
    return @mysqli_fetch_object($object);
  }

  /**
   * Возвращает ряд результата запроса в виде массива с ассоциативными и числовыми ключами.
   * <code>
   * $res = DB::query($sql);
   * while ($row = DB::fetchArray($res)) {
   *   viewdata($row);
   * }
   * </code>
   * @param obj $object
   * @return array
   */
  public static function fetchArray($object) {
    return @mysqli_fetch_array($object);
  }
  
   /**
   * Возвращает ряд результата запроса в виде массива с числовыми ключами.
   * <code>
   * $res = DB::query($sql);
   * while ($row = DB::fetchRow($res)) {
   *   viewdata($row);
   * }
   * </code>
   * @param obj $object
   * @return obj
   */
  public static function fetchRow($object) {
    return @mysqli_fetch_row($object);
  }

  /**
   * Возвращает единственный экземпляр данного класса.
   * @access private
   * @return obj
   */
  static public function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self;
    }
    return self::$_instance;
  }

  /**
   * Инициализирует единственный объект данного класса, устанавливает кодировку БД utf8mb4.
   * @access private
   * @return void
   */
  public static function init() {
    self::getInstance();
    if (defined('UTF8MB4') && !UTF8MB4) {
      DB::query('SET names utf8',false,true);
    } else {
      DB::query('SET names utf8mb4');
    }
    if (SQL_BIG_SELECTS) {
      DB::query('SET SQL_BIG_SELECTS = 1',false,true);
    }
  }

  /**
   * Возвращает автоматически сгенерированный ID, созданный последним INSERT запросом.
   * <code>
   * $lastId = DB::insertId();
   * viewdata($lastId);
   * </code>
   * @return int
   */
  public static function insertId() {
    return @mysqli_insert_id(self::$connection);
  }

  /**
   * Возвращает количество рядов результата запроса.
   * <code>
   * $res = DB::query($sql);
   * $numRows = DB::numRows($res);
   * viewdata($numRows);
   * </code>
   * @param obj $object
   * @return int
   */
  public static function numRows($object) {
    return @mysqli_num_rows($object);
  }
  
  /**
   * Получает число строк, затронутых предыдущей операцией MySQL.
   * <code>
   * DB::query($sql);
   * $affectedRows = DB::affectedRows();
   * viewdata($affectedRows);
   * </code>
   * @return int
   */
  public static function affectedRows() {
    return @mysqli_affected_rows(self::$connection);
  }

  /**
   * Функция для создания индексов в таблицах с проверкой на их существование.
   * <code>
   * $table = 'property_data';
   * $column = 'id';
   * DB::createIndexIfNotExist($table, $column);
   * </code>
   * @param string $table целевая таблица
   * @param string $column столбец для индекса
   */
  public static function createIndexIfNotExist($table, $column) {
    $res = DB::query("SELECT COUNT(1) indexExists FROM INFORMATION_SCHEMA.STATISTICS
        WHERE table_schema=DATABASE() AND table_name='".PREFIX.DB::quote($table, true)."' AND index_name='".DB::quote($column, true)."';");
    $res = DB::fetchAssoc($res);
    if($res['indexExists'] == 0) {
      DB::query('CREATE INDEX '.DB::quote($column, true).' ON '.PREFIX.DB::quote($table, true).'('.DB::quote($column, true).')', true);
    }
  }

  /**
   * Выполняет запрос к БД.
   * <code>
   * $sql = "SELECT * FROM `".PREFIX."product`";
   * $res = DB::query($sql);
   * while ($row = DB::fetchAssoc($res)) {
   *   viewdata($row);
   * }
   * </code>
   * @param string $sql запрос
   * @param bool $noError не выводить SQL ошибку
   * @return obj|bool
   */
  public static function query($sql, $noError = false, $addLog=false) {

    if (($num_args = func_num_args()) > 1) {
      $arg = func_get_args();
      unset($arg[0]);

      // Экранируем кавычки для всех входных параметров.
      foreach ($arg as $argument => $value) {
        $arg[$argument] = mysqli_real_escape_string(self::$connection, $value);
      }
      $sql = vsprintf($sql, $arg);
    }
    $obj = self::$_instance;

    if (isset(self::$connection)) {
      self::$count_sql++;

      $startTimeSql = microtime(true);

      if($noError) {
        $result = mysqli_query(self::$connection, $sql);
      } else {
        $result = mysqli_query(self::$connection, $sql) 
          or die(self::console('<span style="font-size: 14px"><span style="color:red">Ошибка в SQL запросе: '
            . '</span><span style="color:blue">'.self::errorLog($sql).'</span><br>  '
            . '<span style="color:red">'.mysqli_error(self::$connection).'</span></span>'));
      }

      $timeSql = microtime(true) - $startTimeSql;
      self::$timeout += $timeSql;
      self::$lastQuery = $sql;
      if (self::$_debugMode || $addLog) {    
        $color = 'green;';
        if ($timeSql > 0.05) {
          $color = 'red; font-weight: bold;';
          if ($timeSql > 0.1) {
            $color = 'red; font-weight: bold; font-size: 12px;';
            if ($timeSql > 0.5) {
              $color = 'crimson; font-weight: bold; font-size: 12px;';
              if ($timeSql > 1) {
                $color = 'black; font-weight: bold; font-size: 12px;';
              }
            }
          }
        }
        self::$log .= "<p style='margin:5px 0; font-size:10px;'><span style='color:blue'> <span style='color:green'># Запрос номер ".self::$count_sql.": </span>".$sql."</span> <span style='color:".$color."'>(".round($timeSql, 4)." sec )</span>";
        $stack = debug_backtrace();
        self::$log .= " <span style='color:#c71585'>".$stack[0]['file'].' (line '.$stack[0]['line'].")</span></p>";
      }

      return $result;
    }
    return false;
  }

  /**
   * Экранирует кавычки для части запроса.
   * <code>
   * // использование с кавычками
   * $title = 'Чука Крабс';
   * $sql = "SELECT * FROM `".PREFIX."product` WHERE `title` = ".DB::quote($title);
   * // использование без кавычек
   * $title = 'дверь';
   * $sql = "SELECT * FROM `".PREFIX."product` WHERE `title` LIKE '%".DB::quote($title, true)."%'";
   * // опасный запрос от пользователя
   * $_POST['title'] = "Чука Крабс';TRUNCATE mg_setting";
   * $sql = "SELECT * FROM `".PREFIX."product` WHERE `title` = ".DB::quote($_POST['title']);
   * </code>
   * @param string $string часть запроса.
   * @param string $noQuote - если true, то не будет выводить кавычки вокруг строки.
   * @return string
   */
  public static function quote($string, $noQuote = false) {
    return (!$noQuote) ? "'".mysqli_real_escape_string(self::$connection, $string)."'" : mysqli_real_escape_string(self::$connection, $string);
  }
  /**
   * Экранирует кавычки для части запроса и преобразует экранируемую часть запроса в тип integer.
   * <code>
   * $id = '123';
   * $sql = "SELECT * FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($id);
   * </code>
   * @param string $string часть запроса.
   * @param string $noQuote - если true, то не будет выводить кавычки вокруг строки.
   * @return int
   */
  public static function quoteInt($string, $noQuote = false) {
    return (!$noQuote) ? "'".mysqli_real_escape_string(self::$connection, intval($string))."'" : mysqli_real_escape_string(self::$connection, intval($string));
  }
  /**
   * Экранирует кавычки для части запроса, заменяет запятую на точку и преобразует экранируемую часть запроса в тип float.
   * <code>
   * $summ = '123,45';
   * $sql = "SELECT * FROM `".PREFIX."order` WHERE `summ` = ".DB::quoteFloat($summ);
   * </code>
   * @param string $string часть запроса.
   * @param string $noQuote - если true, то не будет выводить кавычки вокруг строки.
   * @return int
   */
  public static function quoteFloat($string, $noQuote = false) {
    $string = str_replace(',', '.', $string);
    return (!$noQuote) ? "'".mysqli_real_escape_string(self::$connection, floatval($string))."'" : mysqli_real_escape_string(self::$connection, floatval($string));
  }
  /**
   * Экранирует кавычки для части запроса и преобразует экранируемую часть запроса в пригодный вид для условий типа IN.
   * <code>
   * $titleArr = array(
   *   'Чука Крабс',
   *   'Дверь межкомнаткая Тенго 22A',
   *   'Стол кухонный Bin04'
   * );
   * $sql = "SELECT * FROM `".PREFIX."product` WHERE `title` IN (".DB::quoteIN($titleArr).")";
   * </code>
   * @param string $string часть запроса.
   * @param bool $returnNull если первый аргумент пустой, то возвращает NULL, иначе пустую строку 
   * @return string
   */
  public static function quoteIN($string, $returnNull = true) {
    if(empty($string)) {
      if ($returnNull) {
        return 'NULL';
      } else {
        return "''";
      }
    }
    if(is_array($string)) {
      $string = implode(',', $string);
    }
    $tmp = explode(',', $string);
    foreach ($tmp as $key => $value) {
      $tmp[$key] = self::quote(trim($value));
    }
    return implode(',', $tmp);
  }

  /**
   * Пишет в лог запросы с синтаксическими ошибками.
   * @param string $text - запрос вызвавший ошибку.
   * @return string
   */
  public static function errorLog($text = '') {
    
    $log = "\n===============[".date('d.m.Y H:i:s')."]===============";
    $log.= "\nError: ".mysqli_error(self::$connection);
    $log.= "\nQuery: ".$text;
    $stack = debug_backtrace();
    unset($stack[0]);
    $log.= "\nTrace: ";
    foreach ($stack as $item) {
      $log.="\n".$item['file'].' (line '.$item['line'].')';
    }

    file_put_contents(TEMP_DIR."error_sql.php", $log, FILE_APPEND);
    return $text;
  }


  /**
   * Выводит консоль запросов и ошибок.
   * <code>
   * echo DB::console('Тест');
   * </code>
   * @param string $text - данные лога.
   * @param string $memory - Память скрипта в начале работы.
   * @param string $start - Время начала работы скрипта.
   * @return string
   */
  public static function console($text = '', $memory=false, $start=false) {

    $stack = debug_backtrace();
    unset($stack[0]);
    $html = '<script>var consoleCount = $(".wrap-mg-console").length; if(consoleCount>1){$(".wrap-mg-console").hide();}</script>
      <div class="wrap-mg-console '.time().'" style="height: 200px; width:100%; position:fixed;z-index:9999;bottom:0;left:0;right:0;background:#fff;">
      <div class="mg-bar-console" style="display: flex;justify-content: space-between;background: #f3f3f3;height: 30px;line-height: 30px;padding: 0 0 0 10px;width:100%;border-top: 1px solid #e6e6e6;border-bottom: 1px solid #e6e6e6;">
      Всего выполнено запросов: '.self::$count_sql.' шт. за '.round(self::$timeout, 4).' сек.
      <span>
        <a class="maximize" style="margin-right:30px;" href="javascript:void(0);" onclick="$(\'.wrap-mg-console\').height(\'100%\');$(\'.wrap-mg-console .mg-console\').height(\'100%\');$(this).hide().parent().find(\'.minimize\').show();">Развернуть</a>
        <a class="minimize" style="display:none;margin-right:30px;" href="javascript:void(0);" onclick="$(\'.wrap-mg-console\').height(\'200px\');$(\'.wrap-mg-console .mg-console\').height(\'200px\');$(this).hide().parent().find(\'.maximize\').show();">Свернуть</a>
        <a style="margin-right:10px;" href="javascript:void(0);" onclick="$(\'.wrap-mg-console\').hide();">Закрыть</a>
      </span>
      </div>
      <div class="mg-console" style="background:#f4f4f4; height: 200px; overflow:auto;width: 100%;padding: 5px 10px;">
      <script>$(".'.time().'").show();</script>     
      ';
    $logStack = '';
    foreach ($stack as $item) {
      $logStack .= '<p style="margin:5px 0; font-size:10px;"><span style="color:#c71585">'.$item['file'].' (line '.$item['line'].")</span></p>";
    }
    // Подсчет время выполнения скрипта и памяти
    if($memory && $start){
      $memory = memory_get_usage() - $memory;
      $time = microtime(true) - $start;
      $time = round($time, 3); 
      // Перевод в КБ, МБ.
      $i = 0;
      while (floor($memory / 1024) > 0) {
              $i++;
              $memory /= 1024;
      }
      
      $name = array('байт', 'КБ', 'МБ');
      $memory = round($memory, 2) . ' ' . $name[$i];
      
      $html.= '<p style="margin:5px 0; font-size:10px;"><span style="color:#c71585"><span style="color:green"># Подготовка HTML:</span> '.$time . ' сек. <span style="color:green">Использовано RAM:</span> ' . $memory. '</span></p>';
    }
    $html.= self::$log.$text.$logStack;
    $html.='</div>
    </div>';
    return $html;
  }

  /**
   * Выводит последний выполненный SQL запрос.
   * <code>
   * $lastQuery = DB::lastQuery();
   * viewdata($lastQuery);
   * </code>
   * @return string
   */
  public static function lastQuery() {
    return self::$lastQuery;
  }
  
  /**
   * Возвращает следующее значение auto_increment таблицы.
   * <code>
   * $idAutoIncrementTables = DB::idAutoIncrement("product");
   * viewdata($idAutoIncrementTables);
   * </code>
   * @return int
   */
  public static function idAutoIncrement($table) {
    // Костылефикс для mysql8 (auto_increment может не обновиться, пока не пнёшь таблицу)
    $refreshTableSql = 'ANALYZE TABLE `'.PREFIX.$table.'`';
    DB::query($refreshTableSql);

    $autoIncrement = false;
    $tableInfoSql = 'SHOW TABLE STATUS '.
      'FROM `'.NAME_BD.'` '.
      'LIKE '.self::quote(PREFIX.$table);

    $tableInfoResult = DB::query($tableInfoSql);
    if ($tableInfoRow = DB::fetchAssoc($tableInfoResult)) {
      if ($tableInfoRow['Auto_increment']) {
          $autoIncrement = intval($tableInfoRow['Auto_increment']);
      }
    }

    return $autoIncrement;
  }

  /**
   * Увеличивает значение auto_increment таблицы на заданное число.
   * <code>
   * $idAutoIncrementTables = DB::addAutoIncrement("product",1);
   * viewdata($idAutoIncrementTables);
   * </code>
   * @return int
   */
  public static function addAutoIncrement($table, $number = 1) {
      $res = self::query('SHOW TABLE STATUS FROM `'.NAME_BD.'` LIKE '.self::quote(PREFIX.$table));
      $result = self::fetchArray($res);
      if(isset($result['Auto_increment'])){
        $result['Auto_increment'] += $number;
        self::query('ALTER TABLE `'.self::quote(PREFIX.$table,true).'` AUTO_INCREMENT = '.self::quoteInt($result['Auto_increment'], true));
      }
    return $result['Auto_increment'];
  }

  /**
   * Закрывает соединение с БД.
   * <code>
   * DB::close();
   * </code>
   */
  public static function close() {
    if (self::$connected) {
      self::$connected = false;
      return mysqli_close(self::$connection);
    }
    return false;
  }
}