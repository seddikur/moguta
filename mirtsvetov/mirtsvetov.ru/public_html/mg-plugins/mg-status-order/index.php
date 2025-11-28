<?php

/*
  Plugin Name: Новые статусы заказов
  Description: Плагин позволяет создавать и редактировать статусы заказов.
  Author: Daria Churkina
  Version: 1.0.8
  Edition: CLOUD
*/

new statusOrder;

class statusOrder {
  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  static $status_dfl = array(
    0 => 'NOT_CONFIRMED',
    1 => 'EXPECTS_PAYMENT',
    2 => 'PAID',
    3 => 'IN_DELIVERY',
    4 => 'CANSELED',
    5 => 'EXECUTED',
    6 => 'PROCESSING',
  );

  public function __construct(){
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроек плагина  
    
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);

    $explode = explode(str_replace('/', DS, PLUGIN_DIR), dirname(__FILE__));
    if (strpos($explode[0], 'mg-templates') === false) {
        self::$path = str_replace('\\', '/', PLUGIN_DIR.DS.$explode[1]);
    } else {
        $templatePath = str_replace('\\', '/', $explode[0]);
        $templatePathParts = explode('/', $templatePath);
        $templatePathParts = array_filter($templatePathParts, function($pathPart) {
            if (trim($pathPart)) {
            return true;
            }
            return false;
        });
        $templateName = end($templatePathParts);
        self::$path = 'mg-templates/'.$templateName.'/mg-plugins/'.$explode[1];
    }

  }
    /*
   *  функция при активации плагина - создание таблицы
   */
  public static function activate(){
    // Запрос для проверки, был ли плагин установлен ранее.
    $exist = false;
    $result = DB::query('SHOW TABLES LIKE "'.PREFIX.self::$pluginName.'"');
    if (DB::numRows($result)) {
      $exist = true;
    }    
    if (!$exist) {  
      $lang = MG::get('lang');
      DB::query("
       CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."` (   
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',     
        `id_status` int(11) NOT NULL COMMENT 'Порядковый номер статуса ',   
        `sort` int(11) NOT NULL,  
        `locale` text NOT NULL COMMENT 'Обозначение в локали',
        `status` text NOT NULL COMMENT 'Перевод',
        `bgColor` varchar(7) NOT NULL,
        `textColor` varchar(7) NOT NULL,
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
      DB::query('INSERT INTO `'.PREFIX.self::$pluginName.'` (`id_status`, `sort`, `locale`,`status`) VALUES '
        . '(0, 0, "NOT_CONFIRMED", "'.$lang["NOT_CONFIRMED"].'"),
            (1, 1, "EXPECTS_PAYMENT", "'.$lang["EXPECTS_PAYMENT"].'"),
            (2, 2, "PAID", "'.$lang["PAID"].'"),
            (3, 3, "IN_DELIVERY", "'.$lang["IN_DELIVERY"].'"),
            (4, 4, "CANSELED", "'.$lang["CANSELED"].'"),
            (5, 5, "EXECUTED", "'.$lang["EXECUTED"].'"),
            (6, 6, "PROCESSING", "'.$lang["PROCESSING"].'")');
    }
  }
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />     
      <link rel="stylesheet" href="'.SITE.'/mg-core/script/colorPicker/css/layout.css" type="text/css" />     
      <link rel="stylesheet" href="'.SITE.'/mg-core/script/colorPicker/css/colorpicker.css" type="text/css" />     
      <script>
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }
  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin(){
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $status = array();
    $statusDfl = self::$status_dfl;
    $dbQuery = DB::query('SELECT * FROM `'.PREFIX.$pluginName.'` ORDER BY `sort` ASC');
    while ($dbRes = DB::fetchArray($dbQuery)) {
      $status[] = $dbRes;
    }
    self::preparePageSettings();
    include('pageplugin.php');
  }
}