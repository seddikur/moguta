<?php
/*
  Plugin Name: Push
  Description: Отправка сообщений в Firebase из админки
  Author: Belka.one
  Version: 1.0.0
 */

new p4push;

class p4push {

  private static $lang = array();
  private static $pluginName = '';
  private static $path = '';

  public function __construct() {
    // Инициализация плагина
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::createLocale();
    self::setPluginPath();
    
    // Регистрация хуков
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate'));
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin'));
    
    // Подключение стилей для админки
    if (URL::isSection('mg-admin')) {
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css">');
    }
  }
  
  /** 
   * Подготовка локалей
   */
  private function createLocale() {
    self::$lang = PM::plugLocales(self::$pluginName);
    // Убедитесь что файл локалей существует
    if(file_exists('mg-admin/locales/'.MG::getSetting('languageLocale').'.php')) {
        include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
        self::$lang = array_merge($lang, self::$lang);
    }
  }
  
  /**
   * Установка пути плагина
   */
  private function setPluginPath() {
    $explode = explode(str_replace('/', DS, PLUGIN_DIR), dirname(__FILE__));
    if (strpos($explode[0], 'mg-templates') === false) {
        self::$path = str_replace('\\', '/', PLUGIN_DIR.DS.$explode[1]);
    } else {
        $templatePath = str_replace('\\', '/', $explode[0]);
        $templatePathParts = explode('/', $templatePath);
        $templatePathParts = array_filter($templatePathParts);
        $templateName = end($templatePathParts);
        self::$path = 'mg-templates/'.$templateName.'/mg-plugins/'.$explode[1];
    }
  }
  
  /**
   * Активация плагина
   */
  public static function activate() {
    self::createOptions();
  }

  /**
   * Создание опций плагина
   */
  private static function createOptions() {
    if(MG::getSetting(self::$pluginName.'-option') == null) {   
      $array = array( 
        "api_key" => "",
        "project_id" => "",
        "database_url" => "",
        "default_topic" => "notifications"
      );      
      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($array))));
    }
  }

  /**
   * Страница настроек плагина
   */
  public static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $options = self::getOptions();
    
    // Подключение необходимых файлов
    self::preparePageSettings();
    
    // Вывод страницы
    include('pageplugin.php');
  }
  
  /**
   * Подготовка страницы настроек
   */
  private static function preparePageSettings() {
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />
      <script>
        includeJS("'.SITE.'/'.self::$path.'/js/script.js"); 
      </script> 
    ';   
  }

  /**
   * Получение опций плагина
   */
  private static function getOptions() {
    $option = MG::getSetting(self::$pluginName.'-option');
    if($option) {
        $option = stripslashes($option);
        $options = unserialize($option);
        return $options;
    }
    return array(
        "api_key" => "",
        "project_id" => "",
        "database_url" => "",
        "default_topic" => "notifications"
    ); 
  }
}