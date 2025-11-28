<?php

/*
  Plugin Name: Повторить заказ
  Description: Плагин позволяет повторить оформленные ранее заказы. В личном кабинете выводятся кнопки для полного копирования заказа и для добавления товаров из заказа в корзину.
  Author: Daria Churkina
  Version: 1.0.11
  Edition: CLOUD
*/

new repeatOrder;

class repeatOrder{    
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  public function __construct(){
    mgAddShortcode('repeat-order', array(__CLASS__, 'repeatOrderWithoutChanges')); // Инициализация шорткода 
    mgAddShortcode('edit-order', array(__CLASS__, 'copyOrderToCart')); // Инициализация шорткода 
    self::$pluginName = PM::getFolderPlugin(__FILE__);  

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

    if(URL::isSection('personal')) {
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/script.js"></script>');
    }
  }
  static public function repeatOrderWithoutChanges($arg){ 
    if (empty($arg['id'])) {
      return false;
    }
    $repeat = true;
    ob_start();
    include ('layout_buttons.php');
    $html = ob_get_contents();
    ob_clean();
    return $html;
  }
  static public function copyOrderToCart($arg){ 
    if (empty($arg['id'])) {
      return false;
    }
    $edit = true;
    ob_start();
    include ('layout_buttons.php');
    $html = ob_get_contents();
    ob_clean();
    return $html;
  }
}