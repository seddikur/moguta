<?php

/*
  Plugin Name: Images
  Description: Плагин позволяет выборочно подключить автозамену изображений на сайте на WebP. Требуется подключение соответствующего модуля на стороне сервера. 
  Version: 1.1.1
 */


new WEBP2;

class WEBP2 {

  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  private static $site = SITE;
  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации 
    mgAddShortcode('webp', array(__CLASS__, 'getCode')); // Инициализация шорткода [webp] - доступен в любом HTML коде движка.
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$path = PLUGIN_DIR.self::$pluginName;
  }
  static function pageSettingsPlugin() {
    $dir = PLUGIN_DIR.self::$pluginName;
    $pluginName = self::$pluginName;  
    $site = self::$site;  
    include('pageplugin.php');
  }
  static function getCode($data) {
     $url = $data['url'];
     $path = parse_url($url, PHP_URL_PATH);
     $filename = basename($path);
     $fe = preg_replace('/\.[a-z]+$/', '.webp', $path);
     $webp_server_url = $_SERVER['DOCUMENT_ROOT'].$path;
     $webp_server_url = preg_replace('/\.[a-z]+$/', '.webp', $webp_server_url);
     $w = SITE.$path;
     $return_webp_url = preg_replace('/\.[a-z]+$/', '.webp', $w);
     if(strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
         
            if(!file_exists($webp_server_url)) {
                $a = self::create($url, $webp_server_url);
                if ($a == true) {
                return $return_webp_url;
                } else {
                    return $url;
                }
            } else {
               
                return $return_webp_url;
            }
        }
        return $url;
    }
    public function create($original_image_path, $webp_image_path)
    {     
      
        if(function_exists('imagewebp')) {
            if($resource = self::gd_imagecreatefromfile($original_image_path)) {
               $a =  imagewebp($resource, $webp_image_path, 100);
                return true;
            } else {
                return false;
            }
        } 
        return false;
    }

    protected function gd_imagecreatefromfile( $filename ) {
        
        switch ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ))) {
            case 'jpeg':
            case 'jpg':
                return imagecreatefromjpeg($filename);
                break;
            case 'png':
                $img = imagecreatefrompng($filename);
               imagepalettetotruecolor($img);
               imagealphablending($img, true);
               imagesavealpha($img, true);
               return $img;
                break;

            case 'gif':
                return imagecreatefromgif($filename);
                break;

            default:
                return false;
                break;
        }
    }
    
    
    
}