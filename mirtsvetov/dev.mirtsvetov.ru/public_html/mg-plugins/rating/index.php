<?php

/*
  Plugin Name: Рейтинг товаров
  Description: Плагин вывода "звездочек" для оценки товара от 1 до 5.
  Author: Дарья Чуркина
  Version: 1.1.23
  Edition: CLOUD
 */

new Rating;

class Rating {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddShortcode('rating', array(__CLASS__, 'showRating')); // Инициализация шорткода [rating] - доступен в любом HTML коде движка.    

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


    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/rateit.css" type="text/css" />');
    }

    mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/script.js"></script>');
    // подключаем плагин для работы с отображение звезд системы рейтинга
    mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/jquery.rateit.js"></script>');
    
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  public static function activate() {
    self::createDateBase();
  }

  /**
   * Создает таблицу плагина в БД
   */
  static function createDateBase() {
    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX."product_".self::$pluginName."` (     
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',     
      `id_product` int(11) NOT NULL COMMENT 'ID товара',
      `rating` double NOT NULL COMMENT 'Оценка',      
      `count` int(11) NOT NULL COMMENT 'Количество голосов',
       PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
  }

  /**
   * Получает из БД запись рейтинга по id товара
   */
  static function getEntity($id) {
    $array = array();
    $sql = "SELECT * FROM `".PREFIX."product_".self::$pluginName."` WHERE `id_product` = ".DB::quoteInt($id).";";
    $result = DB::query($sql);
    while ($row = DB::fetchAssoc($result)) {
      $array[] = $row;
    }
    return $array;
  }
/*
 * Получает из БД название товара по его id (Для микроразметки Schema.org)
 * */
static function getProdName($id) {
    $resProds = DB::query("SELECT title FROM ".PREFIX."product WHERE id=".DB::quoteInt($id));
    if ($rowProd = DB::fetchAssoc($resProds)) {
        $arrayProd = $rowProd['title'];
    }
    return $arrayProd;
}


  /**
   * Функция вывода рейтинга на месте шорткода 
   * [rating id = "<?php echo $data['id'] ?>"] и [rating id = "<?php  echo ($item ['id']) ?>"]
   *  @param type $vote - массив с данными о рейтнге (полностью запись из БД)
   */
  static function showRating($vote) {
    if ($vote['id']) {
    $check = 0;
    if (isset($_COOKIE['rating_product'])) {
      $array_id = json_decode($_COOKIE['rating_product']);
      if (in_array($vote['id'], $array_id)){
        $check = 1;
      }
    }
    $entity = self::getEntity($vote['id']);
    $nameProd = str_replace(array('"',"'"),'', self::getProdName($vote['id']));
    $core = "";
    // если запись об рейтинге товара нет в БД плагина
    foreach ($entity as $rows) {
      $rating = round($rows['rating'] / $rows['count'], 1);
      $count = $rows['count'];
      $id = $rows['id_product'];
        if (MG::get('controller')=="controllers_product") {
            $core = "<span class='info' itemprop='aggregateRating' itemscope itemtype='http://schema.org/AggregateRating' data-id = '".$id."'><meta itemprop='itemReviewed' content='".$nameProd."'><span class='mg-rating-count' data-count='".$id."'>(<meta itemprop='worstRating' content='0'><meta itemprop='bestRating' content='5'/><span itemprop='ratingValue'>".$rating."</span>/<span itemprop='ratingCount'>".$count."</span>)</span></span></div>";
        }
        else {
            $core = "<span class='info' data-id = '".$id."'><span class='mg-rating-count' data-count='".$id."'>(<span>".$rating."</span>/<span>".$count."</span>)</span></span></div>";
        }

    }
    if ($core == "") {
      $rating = $count = 0;
      $core = "<span class='info' data-id = '".$vote['id']."'><span class='mg-rating-count' data-count='".$vote['id']."'>(<span>".$rating."</span>/<span>".$count."</span>)</span></span></div>";
    }
    $core = "<div class='rating-wrapper'>             
            <div class='rating-action' data-rating-id = ".$vote['id'].">
            <div class='rateit' data-plugin='stars' data-rateit-value =".$rating."
                data-productid=".$vote['id']." data-rateit-readonly=".$check.">
            </div>    
            </div>
            ".$core;

    return $core;
   }
  }
}
