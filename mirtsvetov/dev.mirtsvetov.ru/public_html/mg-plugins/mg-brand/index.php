<?php

/*
  Plugin Name: Бренды
  Description: Плагин добавляет на сайт карточки брендов с товарами, описанием и лого. 
  Author: Yakupov Alexander
  Version: 1.3.31
  Edition: CLOUD
 */

new Brands;

class Brands {

  private static $lang = array(); // массив с переводом плагина
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина
  public static $debug = false;

  public function __construct() {
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
    $lang =  array_merge($lang,self::$lang);
    self::$lang = $lang;

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


    if (!self::checkVersion() && !self::$debug) {
      self::deactivatePlugin();
      return false;
    }

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate'));
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin'));
    if (!URL::isSection('mg-admin')){
      $sections = URL::getSections();
      if (count($sections) == 2 && self::getIdUrlByRoute($sections[1])) {
        MG::set('urlrewrite', $sections[1]);
        mgAddAction("urlrewrite_geturlrewritedata", array(__CLASS__, 'showCatalog'), 1, 1);
        mgAddAction("MG_layoutManager", array(__CLASS__, 'switchDesc'), 1,1);
        mgAddAction("MG_meta", array(__CLASS__, 'switchCanonical'), 1, 1);
      }
    }
    mgAddShortcode('mg-brand', array(__CLASS__, 'handleShortCode'));

    if (!URL::isSection('mg-admin')) {
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/brand.css" type="text/css" />');
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/owl.carousel.css" type="text/css" />');
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/locales/ru_RU.js"></script>');
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/myowl.carousel.js"></script>');
    }
  }

  /**
   * Метод для замены каноникал ссылки на странице бренда
   *
   * @param  $res
   * @return $res
   */
  public static function switchCanonical($res)
  {
    $head = $res['result'];
    //Ищем текущий каноникал
    $searchStringBegin = '<link rel="canonical" href="';
    $pos = strpos($head, $searchStringBegin);
    $posBegin = $pos + strlen($searchStringBegin);
    $searchStringEnd = '">';
    $posEnd = strpos($head, $searchStringEnd, $posBegin);

    $oldCanonical = $searchStringBegin.mb_strcut($head, $posBegin, $posEnd-$posBegin).$searchStringEnd;

    //Готовим новый каноникал
    $url = self::getBrand(self::getIdUrlByRoute(URL::getRoute()));

    $newCanonical = $searchStringBegin.SITE."/".$url['short_url'].$searchStringEnd;

    //Меняем текущий каноникал на старый
    return str_replace($oldCanonical, $newCanonical, $head);
  }

  public static function showCatalog($res){
    self::loger(__FUNCTION__);
    $data = self::getDataForCatalog(self::getIdUrlByRoute(URL::getRoute()));
    $res['result'] = array(
      'url'           => $data['full_url'],
      //'short_url'     => 'test',
      'titeCategory'  => $data['brand'],
      'cat_desc'      => $data['desc'],
      'meta_title'    => $data['seo_title'],
      'meta_keywords' => $data['seo_keywords'],
      'meta_desc'     => $data['seo_desc'],
      'cat_desc_seo'  => $data['cat_desc_seo']
    );
    self::loger($res);
    return $res['result'];
  }

  /**
   * Возвращает название, полную ссылку, описание и сео по айдишнику
   * @param int|string $id
   * @return array|false
   */
  public static function getDataForCatalog($id) {
    self::loger(__FUNCTION__);

    $sql = "SELECT * FROM `".PREFIX.self::$pluginName."` WHERE id = ".DB::quoteInt($id);
    self::loger("[SQL_QUERY]".$sql);
    $result = DB::query($sql);
    if (!$result) return false;
    $result = DB::fetchAssoc($result);
    if (LANG != "LANG") {
      $result = self::getTranslate($id, LANG, $result);
    }

    return $result;
  }

  /**
   * Метод для хука mg_layoutManager.
   * Убирает панель с примененными фильтрами и вместо подкатегорий выводит картинку бренда
   *
   * @param array $res
   * @return html
   */
  public static function switchDesc($res){
    $img = self::getImgByRoute(URL::getRoute());
    if ($res['args'][0] == "layout_subcategory") {
      $img_html = '<img src="'.$img['url'].'" title="'.$img['img_title'].'" alt="'.$img['img_alt'].'" aria-label="'.$img['img_title'].'">';
      return $img_html;
    } else if ($res['args'][0] == "layout_apply_filter") {
      return "";
    }

    return $res['result'];
  }

  /**
   * Возвращает ссылку, title и alt на картинку по роуту
   *
   * @param string $route
   * @return array
   */
  public static function getImgByRoute($route) {
    self::loger(__FUNCTION__);

    $id = self::getIdUrlByRoute($route);
    $sql = "SELECT `url`, `img_title`, `img_alt` FROM `".PREFIX.self::$pluginName."` WHERE `id` = ".DB::quoteInt($id);
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;

    return $result;
  }

  /**
   * Проверяет роут на наличие в переписанных ссылках
   *
   * @param string $name
   * @return boolean
   */
  public static function getIdUrlByRoute($name) {
    self::loger(__FUNCTION__);

    $sql = "SELECT `id` FROM `".PREFIX.self::$pluginName."` WHERE `short_url` = ".DB::quote($name);
    $result = DB::fetchAssoc(DB::query($sql));
    self::loger('[SQL_QUERY]'.$sql);
    if (!$result) return false;

    return $result['id'];
  }

  /**
   * Метод выполняющийся при активации палагина
   */
  public static function activate() {
    self::loger(__FUNCTION__);
    self::createOptions();
    self::createDataBase();
    self::insertExamples();
    //self::createPage();
  }

  /**
   * Проверяет актуальность версии движка (С версии 8.5.0 появились новые хуки для Urlrewrite)
   *
   * @return void
   */
  public static function checkVersion(){
    self::loger(__FUNCTION__);
    if (version_compare(VER, 'v8.5.0') < 0) {
      return false;
    }
    return true;
  }

  /**
   * Метод для деактивации плагина
   *
   * @return void
   */
  public static function deactivatePlugin(){
    self::loger(__FUNCTION__);
    $sql = "UPDATE `".PREFIX."plugins` SET `active` = 0 WHERE `folderName` = ".DB::quote(self::$pluginName);
    self::loger("[SQL_QUERY]".$sql);
    DB::query($sql);
  }

  /**
   * Создает таблицу плагина в БД
   */
  static function createDataBase() {
    self::loger(__FUNCTION__);
    $sql = ("
     CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',
      `data_id` int(11) NOT NULL COMMENT 'Номер в таблице характеристик',
      `brand` text NOT NULL COMMENT 'Бренд',
      `url` text NOT NULL COMMENT 'Логотип',
      `img_alt` text NOT NULL COMMENT 'alt',
      `img_title` text NOT NULL COMMENT 'title',
      `desc` text NOT NULL COMMENT 'Описание',
      `short_url` text NOT NULL COMMENT 'Короткая ссылка',
      `full_url` text NOT NULL COMMENT 'Полная ссылка',
      `add_datetime` DATETIME NOT NULL COMMENT 'Дата добавления',
      `seo_title` text NOT NULL COMMENT '(SEO) Название',
      `seo_keywords` text NOT NULL COMMENT '(SEO) Ключевые слова',
      `seo_desc` text NOT NULL COMMENT '(SEO) Описание',
      `cat_desc_seo` text NOT NULL COMMENT 'Описание для SEO',
      `invisible` int(1) NOT NULL COMMENT 'Видимость',
      `sort` int(11) NOT NULL COMMENT 'Сортировка',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
    DB::query($sql);
  }

  /**
   * Создает опции
   *
   * @return void
   */
  static function createOptions() {
    self::loger(__FUNCTION__);
    // Если опции еще не были созданы, то создать
    if(MG::getSetting(self::$pluginName.'-option') == null){

      $array = Array(
      //Список опций

      //Тут же создаем характеристику в БД
        "propertyId" => self::createProperty(),
        "slider_options" => array(
          "items"       => "3",
          "nav"         => "true",
          "mouseDrag"   => "true",
          "autoplay"    => "false",
          "loop"        => "false",
          "responsive"  => "0:1,600:3,1000:5",
          "head"        => "Бренды",
          "margin"      => "10",
        )
      );

      //Добавляем опции
      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($array))));
    }
  }

  /**
   * Добавляет примеры брендов в таблицу
   *
   * @return void
   */
  static function insertExamples() {
    self::loger(__FUNCTION__);
    // Запрос для проверки, был ли плагин установлен ранее.
    $res = DB::query("
      SELECT id
      FROM `".PREFIX.self::$pluginName."`
    ");

    // Если плагин впервые активирован, то задаются настройки по умолчанию
    if (!DB::numRows($res)) {

      self::addBrand(array(
        "brand"         => "Huawei",
        "url"           => SITE."/uploads/logo/log5.png",
        "desc"          => "Huawei desc",
        "add_datetime"  => date("Y-m-d H:i:s"),
        "seo_title"     => "huawei",
        "seo_keywords"  => "huawei, smartphone",
        "seo_desc"      => "Huawei seo-desc",
        "cat_desc_seo"  => "Card seo description",
      ));

      self::addBrand(array(
        "brand"         => "Samsung",
        "url"           => SITE."/uploads/logo/log6.png",
        "desc"          => "Samsung desc",
        "add_datetime"  => date("Y-m-d H:i:s"),
        "seo_title"     => "Samsung",
        "seo_keywords"  => "Samsung, smartphone",
        "seo_desc"      => "Samsung seo-desc",
        "cat_desc_seo"  => "Card seo description",
      ));

      self::addBrand(array(
        "brand"         => "Xiaomi",
        "url"           => SITE."/uploads/logo/log7.png",
        "desc"          => "Xiaomi desc",
        "add_datetime"  => date("Y-m-d H:i:s"),
        "seo_title"     => "Xiaomi",
        "seo_keywords"  => "Xiaomi, smartphone",
        "seo_desc"      => "Xiaomi seo-desc",
        "cat_desc_seo"  => "Card seo description",
      ));
    }
  }

  /**
   * Логер для дебага
   *
   * @param string $log
   * @return void
   */
  static function loger($log){
    if (self::$debug) MG::loger($log, 'apend', "TEST");
  }

  /**
   * Создает характеристику, применяет ее ко все категориям и активирует ее
   *
   * @return string
   */
    static function createProperty() {
        self::loger(__FUNCTION__);
        $id = Property::createProp("Бренды[prop attr=плагин]", "assortmentCheckBox");
        //Применяем новую характеристику ко всем категориям
        if($dbRes = DB::query('SELECT id FROM `'.PREFIX.'category`')){
            $sql = 'INSERT INTO `'.PREFIX.'category_user_property` 
        (`category_id`, `property_id`) VALUES ';
            $sqlPart = '';
            $propArray = [];
            if($usedUserProp = DB::query('SELECT `category_id`,`property_id` FROM `'.PREFIX.'category_user_property` GROUP BY `category_id`,`property_id`')){
              while($propRes = DB::fetchAssoc($usedUserProp)){
                $propArray[$propRes['category_id']] = $propRes['property_id'];
              }
            }
            while($res = DB::fetchAssoc($dbRes)){
              if(isset($propArray[$res['id']]) && $propArray[$res['id']] == $id) continue;
              $sqlPart .= '('.$res['id'].', '.$id.'),';
            }

            if(!empty($sqlPart)){
                $sql .= $sqlPart;
                $sql = substr($sql, 0, -1);
                self::loger('[SQL_QUERY]'.$sql);
                DB::query($sql);
            }

        }

        $sql = "UPDATE `".PREFIX."property` 
          SET `filter` = '1' WHERE `id` = ".DB::quoteInt($id);
        $result = DB::query($sql);
        if (!$result) return false;

        return $id;
    }

  /**
   * Проверка на существования бренда по имени
   *
   * @param string $brand
   * @return boolean
   */
  public static function existBrand($brand) {
    self::loger(__FUNCTION__);
    $sql = "SELECT * FROM `".PREFIX.self::$pluginName."` WHERE `brand` = ".DB::quote($brand);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::fetchAssoc(DB::query($sql));
    if (empty($result['id'])) return false;
    return true;
  }

  /**
   * Обновляет привязку к свойству у бренда
   *
   * @param string $id
   * @param string $data_id
   * @return boolean
   */
  public static function updateDataId($id, $data_id) {
    $options = self::getOptions();
    self::loger(__FUNCTION__);
    $changeData = '';
    $newUrl = '';

    $res = DB::query('SELECT `data_id`,`brand` FROM `'.PREFIX.self::$pluginName.'` WHERE `id` = '.DB::quoteInt($id));
    if($row = DB::fetchAssoc($res)){
      $resPropId = DB::query("SELECT `id` FROM `".PREFIX."property_data` WHERE `name` = ".DB::quote($row['brand'])." AND `prop_id` = ".DB::quoteInt($options['propertyId']));
      $rowsPropId = DB::affectedRows();
      if($rowsPropId <= 2){
        if($rowPropId = DB::fetchAssoc($resPropId)){
          $newUrl = self::generateFilterUrl($rowPropId['id']);
          $data_id = $rowPropId['id'];
        }
      } else {
        if(is_numeric($data_id)){
          $newUrl = self::generateFilterUrl($data_id);
        } else {
          return false;
        }
      }
      $newUrl = ' ,`full_url` = "'.$newUrl.'" ';
    } 
    $sql = 'UPDATE `'.PREFIX.self::$pluginName.'`
    SET `data_id` = '.DB::quoteInt($data_id).$newUrl.'
        WHERE id = '.DB::quoteInt($id);
    Brands::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);
    if (!$result) return false;

    return true;
  }

  /**
   * Метод для проверки существования свойства у характеристики товара.
   * Возвращает имя свойства или false в случае отсутствия
   * @param int|string $id
   * @return string|boolean
   */
  public static function existStringProp($id) {
    self::loger(__FUNCTION__);
    $options = self::getOptions();

    $sql = 'SELECT `name` FROM `'.PREFIX.'property_data`
    WHERE `id` = '.DB::quoteInt($id).' AND `prop_id` = '.DB::quoteInt($options['propertyId']);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;
    return $result['name'];
  }

  /**
   * Создает ссылку на фильтр с свойством
   *
   * @param string $id
   * @return string
   */
  public static function generateFilterUrl($id) {
    $options = self::getOptions();

    $url = '/catalog?prop%5B'.$options['propertyId'].'%5D%5B0%5D='.$id.'%7C&applyFilter= 1';

    return $url;
  }

  public static function getNewSort() {
    $sql = "SELECT MAX(sort) AS max_sort FROM `".PREFIX.self::$pluginName."`";
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;
    return $result['max_sort'] + 1;
  }

  /**
   * Метод для добавления бренда
   *
   * @param array $array
   * @return boolean
   */
  public static function addBrand($array) {
    self::loger(__FUNCTION__);
    if (self::existBrand($array['brand'])) return false;

    //Добавить свойство к характеристике
    if (empty($array['data_id'])) {
      $array['data_id'] = self::addStringProp($array['brand']);
    }
    //Генерируем короткую ссылку
    if (empty($array['short_url'])) $array['short_url'] = MG::translitIt(strtolower($array['brand']));
    //Генерируем полную ссылку на каталог
    $array['full_url'] = self::generateFilterUrl($array['data_id']);

    $array['sort'] = self::getNewSort();

    //Добавить бренд в таблицу
    self::loger('INSERT INTO `'.PREFIX.self::$pluginName.'` SET ');
    self::loger($array);
    return DB::buildQuery('INSERT INTO `'.PREFIX.self::$pluginName.'` SET ', $array);
  }

  /**
   * Метод для обновления бренда
   *
   * @param array $array
   * @return boolean
   */
  public static function editBrand($array) {
    self::loger(__FUNCTION__);
    $sql = "SELECT `data_id` FROM `".PREFIX.self::$pluginName."` WHERE `id` = ".DB::quoteInt($array['id']);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;

    $data_id = null;

    if ($result['data_id'] != $array['data_id']) {
      $data_id = $array['data_id'];
      $array['full_url'] = self::generateFilterUrl($array['data_id']);
    } else {
      $data_id = $result['data_id'];
      $sql = 'UPDATE `'.PREFIX.'property_data`
      SET `name` = '.DB::quote($array['brand']).'
      WHERE id = '.DB::quoteInt($data_id);
      self::loger('[SQL_QUERY]'.$sql);
      $result = DB::query($sql);
      if (!$result) return false;
    }

    $sql = 'UPDATE `'.PREFIX.self::$pluginName.'`
    SET '.DB::buildPartQuery($array).'
    WHERE id = '.DB::quoteInt($array['id']);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);
    if (!$result) return false;



    return true;
  }

  /**
   * Метод для удаления бренда
   *
   * @param string $id
   * @return boolean
   */
  public static function deleteBrand($id) {
    self::loger(__FUNCTION__);
    $sql = "SELECT `data_id` FROM `".PREFIX.self::$pluginName."` WHERE `id` = ".DB::quoteInt($id);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;
    $data_id = $result['data_id'];

    $sql = "DELETE FROM `".PREFIX.self::$pluginName."` WHERE `id` = ".DB::quoteInt($id);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);
    if (!$result) return false;

    $sql = "DELETE FROM `".PREFIX."property_data` WHERE `id` = ".DB::quoteInt($data_id);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);

    if (!$result) return false;

    return true;
  }

  /**
   * Метод, добавляющий свойство в характеристику
   * Возвращает айди новой характеристики
   * @param string $text
   * @return string
   */
  public static function addStringProp($text) {
    self::loger(__FUNCTION__);
    $options = self::getOptions();

    $res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property_data');
    while($row = DB::fetchAssoc($res)) {
      $maxId = $row['MAX(id)'];
      $maxId++;
    }
    $sql = 'INSERT INTO `'.PREFIX.'property_data` (prop_id, name, sort) VALUES
    ('.DB::quoteInt($options['propertyId']).', '.DB::quote($text).', '.DB::quoteInt($maxId).')';
    self::loger('[SQL_QUERY]'.$sql);
    DB::query($sql);

    return DB::insertId();
  }

  /**
   * Создает бренд на основе айдишника свойства
   * @param int|string $id
   * @return boolean
   */
  public static function addBrandOnProp($id) {
    self::loger(__FUNCTION__);

    //Проверяем, есть ли уже бренд с таким свойством
    $sql = "SELECT `id` FROM `".PREFIX.self::$pluginName."` WHERE `data_id` = ".DB::quoteInt($id);
    $result = DB::fetchAssoc(DB::query($sql));
    if ($result != null) return false;

    $sql = 'SELECT `name` FROM `'.PREFIX.'property_data`
            WHERE id = '.DB::quoteInt($id);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;
    $array = array(
      'data_id'       => $id,
      'brand'         => $result['name'],
      'add_datetime'  => date("Y-m-d H:i:s"),
      'short_url'     => MG::translitIt(strtolower($result['name'])),
      'full_url'      => self::generateFilterUrl($id)
    );

    self::loger('[SQL_QUERY]'.'INSERT INTO `'.PREFIX.self::$pluginName.'` SET ');
    self::loger($array);

    return DB::buildQuery('INSERT INTO `'.PREFIX.self::$pluginName.'` SET ', $array);
  }

  /**
   * Метод для удаления свойства характеристики
   *
   * @param string|int $id
   * @return boolean
   */
  public static function deleteProp($id) {
    self::loger(__FUNCTION__);
    $options = self::getOptions();
    $sql = "DELETE FROM `".PREFIX."property_data` WHERE `id` = ".DB::quoteInt($id)." AND `prop_id` = ".DB::quoteInt($options['propertyId']);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);
    if (!$result) return false;
    return true;
  }

  /**
   * Возвращает бренд по айди
   *
   * @param string|int
   * @return array|boolean
   */
  public static function getBrand($id) {
    self::loger(__FUNCTION__);
    $sql = "SELECT * FROM `".PREFIX.self::$pluginName."` WHERE `id` = ".DB::quoteInt($id);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result || $result == null) return false;
    return $result;
  }

  static function createPage() {
    self::loger(__FUNCTION__);
    //Копирование страницы из папки плагина в папку mg-pages
    $page = self::$path.'/views/brand.php';
    if (!file_exists(PAGE_DIR.'/brand.php')){
      copy($page, PAGE_DIR.'/brand.php');
    }
  }

  /**
   * Метод, сравнивающий таблицу свойства характеристики и таблицу брендов.
   * Результатом будет список свойств, отсутствующие в таблице брендов
   * @return array|boolean
   */
  public static function getPropsWithoutBrand() {
    self::loger(__FUNCTION__);
    $options = self::getOptions();

    $sql = "SELECT `id`, `name` FROM `".PREFIX."property_data` WHERE `prop_id` = ".DB::quoteInt($options['propertyId']);
    self::loger($sql);
    $result = DB::query($sql);
    if (!$result) return false;

    $list_props = array();
    while ($row = DB::fetchAssoc($result)) {
      $list_props[] = $row;
    }

    $sql = "SELECT `data_id` FROM `".PREFIX.self::$pluginName."`";
    self::loger($sql);
    $result = DB::query($sql);
    if (!$result) return false;

    $list_brands_data_id = array();
    while ($row = DB::fetchAssoc($result)) {
      $list_brands_data_id[] = $row['data_id'];
    }

    $result_list = array();
    foreach ($list_props as $key => $value) {
      if (!in_array($value['id'], $list_brands_data_id)) {
        $value['no-brand'] = 'true';
        $result_list[] = $value;
      }
    }

    return $result_list;
  }

  /**
   * Метод для экспорта брендов из старого плагина
   *
   * @return boolean
   */
  public static function exportOldBrands() {
    $option = MG::getOption('brand');
    $option = stripslashes($option);
    $options = unserialize($option);

    $sql = "SELECT `id`, `name` FROM `".PREFIX."property_data` WHERE `prop_id` = ".DB::quote($options['propertyId']);
    self::loger($sql);
    $result = DB::query($sql);
    if (!$result) return false;

    $list_props = array();
    while ($row = DB::fetchAssoc($result)) {
      $list_props[$row['name']] = $row['id'];
    }

    $sql = "SELECT * FROM `".PREFIX."brand-logo`";
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);
    if (!$result) return false;

    $new_options = self::getOptions();
    $new_options['propertyId'] = $options['propertyId'];
    MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($new_options))));

    while ($row = DB::fetchAssoc($result)) {
      $row['add_datetime'] = date("Y-m-d H:i:s");
      $row['data_id'] = $list_props[$row['brand']];
      unset($row['id']);
      unset($row['sort']);
      self::addBrand($row);
    }

    return true;
  }



  /**
   * Метод, генерирующий новую характеристику и переносящий туда все бренды
   */
  public static function generateNew() {
    self::loger(__FUNCTION__);
    self::generateNewPropertyId();
    return self::generateNewProps();
  }

  /**
   * Метод, переносящий все бренды на новую характеристику
   *
   * @return boolean
   */
  public static function generateNewProps() {
    self::loger(__FUNCTION__);
    $sql = "SELECT `id`, `brand` FROM `".PREFIX.self::$pluginName."`";
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);
    if (!$result) return false;

    while ($row = DB::fetchAssoc($result)) {
      if (!self::updateDataId($row['id'], self::addStringProp($row['brand']))) {
        return false;
      }
    }

    return true;
  }

  /**
   * Метод, генерирующий новую характеристику
   */
  public static function generateNewPropertyId() {
    self::loger(__FUNCTION__);
    $newProperty = self::createProperty();
    $options = self::getOptions();
    $options['propertyId'] = $newProperty;
    MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($options))));
  }

  public static function checkProperty() {
    $options = self::getOptions();

    self::loger(__FUNCTION__);
    $sql = "SELECT `id` FROM `".PREFIX."property` WHERE `id` = ".DB::quoteInt($options['propertyId']);
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;

    return true;
  }

  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    self::loger(__FUNCTION__);
    //Подключаем файл со стилями и скрипт для страницы настроек плагина
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/timepicker.min.css" type="text/css" />
      <script>
        includeJS("'.SITE.'/'.self::$path.'/js/script.js"); 
        includeJS("'.SITE.'/'.self::$path.'/js/jquery-ui-timepicker-addon.js"); 
      </script> 
      ';
  }

  /**
   * Метод, возвращающий флаг активности характеристики (для проверки, вдруг ее отключили в настройках)
   *
   * @return string
   */
  public static function getActivityProperty()
  {
    self::loger(__FUNCTION__);

    $options = self::getOptions();

    $sql = "SELECT `activity` FROM `".PREFIX."property` WHERE `id` = ".DB::quoteInt($options['propertyId']);
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::fetchAssoc(DB::query($sql));
    return $result['activity'];
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    self::loger(__FUNCTION__);
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $debug = self::$debug;
    $displayFilter = false;

    if (!self::checkProperty()) {
      $listprops = self::getAllProps();

      self::preparePageSettings();
      include('views/no_found.php');
    } else {

    $options = self::getOptions();

    $responsive = array();
    $listResponsive = explode(',', $options['slider_options']['responsive']);
    foreach ($listResponsive as $value) {
      $listItems = explode(':',$value);
      $responsive[$listItems[0]] = $listItems[1];
    }

    $activityProp = self::getActivityProperty();

    $maxDate = self::maxDate();
    $minDate = self::minDate();

    //фильтры
    $property = array(
      'id' => array(
        'type' => 'text',
        'label' => $lang['ID'],
        'value' => !empty($_POST['id']) ? $_POST['id'] : null,
      ),
      'add_datetime' => array(
        'type' => 'between',
        'label0' => $lang['ADD_DATE'],
        'label1' => $lang['ADD_DATE_FROM'],
        'label2' => $lang['ADD_DATE_TO'],
        'min' => !empty($_POST['add_datetime'][0]) ? $_POST['add_datetime'][0] : $minDate,
        'max' => !empty($_POST['add_datetime'][1]) ? $_POST['add_datetime'][1] : $maxDate,
        'special' => 'date',
        'class' => 'date'
      ),
      'brand' => array(
        'type' => 'text',
        'label' => $lang['NAME'],
        'value' => !empty($_POST['brand']) ? $_POST['brand'] : null,
      ),
      'desc' => array(
        'type' => 'text',
        'label' => $lang['DESC'],
        'value' => !empty($_POST['desc']) ? $_POST['desc'] : null,
      ),

      'sorter' => array(
        'type' => 'hidden', //текстовый инпут
        'label' => 'сортировка по полю',
        'value' => !empty($_POST['sorter'])?$_POST['sorter']:null,
      ),
    );

    if(isset($_POST['applyFilter'])){
      $property['applyFilter'] = array(
        'type' => 'hidden', //текстовый инпут
        'label' => 'флаг примения фильтров',
        'value' => 1,
      );
      $displayFilter = true;
    }

    $filter = new Filter($property);

    $arFilter = array(
      'id'=> !empty($_POST['id']) ? $_POST['id'] : null,
      'add_datetime' => array(!empty($_POST['add_datetime'][0]) ? $_POST['add_datetime'][0] : $minDate, !empty($_POST['add_datetime'][1]) ? $_POST['add_datetime'][1] : $maxDate, 'date')
    );

    if(!empty($_POST['brand'])){
      $arFilter['brand'] = array(
          $_POST['brand'],
          'like'
      );
    }

    if(!empty($_POST['desc'])){
      $arFilter['desc'] = array(
          $_POST['desc'],
          'like'
      );
    }

    $userFilter = $filter->getFilterSql($arFilter, explode('|',$_POST['sorter']));

    $sorterData = explode('|',$_POST['sorter']);

    if($sorterData[1]>0){
      $sorterData[3] = 'desc';
    } else{
      $sorterData[3] = 'asc';
    }

    //Костыль для desc
    if(!empty($_POST['desc'])){
      $desc_pos = strpos($userFilter, 'desc');
      $userFilter = substr_replace($userFilter, "`", $desc_pos+4, 0);
      $userFilter = substr_replace($userFilter, "`", $desc_pos, 0);
    }


    $page=!empty($_POST["page"])?$_POST["page"]:0;//если был произведен запрос другой страницы, то присваиваем переменной новый индекс

    $countPrintRows = MG::getSetting('countPrintRows') != null? MG::getSetting('countPrintRows'): 10;

    if(empty($_POST['sorter'])){
      if(empty($userFilter)){ $userFilter .= ' 1=1 ';}
      $userFilter .= "  ORDER BY `sort` ASC";
    }

    $sql = "
    SELECT * FROM `".PREFIX.self::$pluginName."`
    WHERE ".$userFilter."
    ";
    self::loger('[SQL_QUERY]'.$sql);

    $navigator = new Navigator($sql, $page, $countPrintRows); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');
    $filter = $filter->getHtmlFilterAdmin();
    //фильтры конец

    // self::$debug = false;
    $withoutProp = 0;

    foreach ($entity as $key => $value) {
      //Проверяем, все ли свойства есть в характеристике
      if (!self::existStringProp($value['data_id'])) {
        $entity[$key]['refresh'] = "true";
        $withoutProp++;
      }
    }

    //Добавляем на вывод свойства без бренда в БД
    $withoutBrandList = self::getPropsWithoutBrand();
    $withoutBrand = count($withoutBrandList);
    $entity = array_merge($entity, $withoutBrandList);
    $data_id_list = self::getDataId();

    //Получаем все характеристики для настроек
    $listprops = self::getAllProps();

    // self::$debug = true;
    self::preparePageSettings();
    include('pageplugin.php');
    }
  }

    private static function maxDate() {
    //self::loger(__FUNCTION__);
    $sql = "SELECT MAX(add_datetime) as res FROM `".PREFIX.self::$pluginName."`";
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;

    return $result['res'];
  }

  private static function minDate() {
    //self::loger(__FUNCTION__);
    $sql = "SELECT MIN(add_datetime) as res FROM `".PREFIX.self::$pluginName."`";
    $result = DB::fetchAssoc(DB::query($sql));
    if (!$result) return false;

    return $result['res'];
  }

  /**
   * Метод для получения всех характеристик (которые с чекбоксами)
   * @return array|boolean
   */
  public static function getAllProps() {
    self::loger(__FUNCTION__);
    $sql = "SELECT `id`, `name` FROM `".PREFIX."property` WHERE `type` = 'assortmentCheckBox' OR `type` = 'string'";
    $result = DB::query($sql);
    if (!$result) return false;

    $list = array();
    while ($row = DB::fetchAssoc($result)) {
      $list[] = $row;
    }

    return $list;
  }

  /**
   * Возвращает список "видимых" брендов
   *
   * @return array
   */
  static function getAll() {
    self::loger(__FUNCTION__);
    $sql = "SELECT * FROM `".PREFIX.self::$pluginName."` WHERE `invisible` = 0 ORDER BY `sort` asc";
    self::loger('[SQL_QUERY]'.$sql);
    $result = DB::query($sql);
    if (!$result) return false;

    $list = array();
    while ($row = DB::fetchAssoc($result)) {
      if ($row['url'] != "") {
        $list[] = $row;
      }
    }

    return $list;
  }


  /**
   * Обработчик шотркода вида [mg-brand]
   * выполняется когда при генерации страницы встречается [mg-brand]
   *
   * @return text
   */
  static function handleShortCode() {
    self::loger(__FUNCTION__);
    $lang = self::$lang;
    $options = self::getOptions();

    if (!empty($options['slider_options']['responsive'])) {
      $responsive = array();
      $listResponsive = explode(',', $options['slider_options']['responsive']);
      foreach ($listResponsive as $value) {
        $listItems = explode(':',$value);
        $responsive[$listItems[0]] = $listItems[1];
      }
    } else {
      $responsive = null;
    }

    $entity = self::getAll();

    foreach ($entity as $key => $value) {
      //Проверяем, все ли свойства есть в характеристике
      if (!self::existStringProp($value['data_id'])) {
        $entity[$key]['short_url'] = "javascript:void(0);";
      }
    }

    ob_start();
    include('views/shortcode.php');
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  /**
   * Метод, возвращающий опции плагина
   *
   * @return array
   */
  public static function getOptions() {
    //self::loger(__FUNCTION__);
    $option = MG::getOption(self::$pluginName.'-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    return $options;
  }

    /**
   * Получает перевод
   */
  public static function getTranslate($id, $locale, $array) {
    Brands::loger(__FUNCTION__);
    $array = Brands::getBrand($id);
    MG::loadLocaleData($id, $locale, self::$pluginName, $array);
    return $array;
  }

  /**
   * Сохраняет перевод
   */
  public static function saveTranslate($id, $locale, $data) {
    Brands::loger(__FUNCTION__);
    self::saveLocaleData($id, $locale, self::$pluginName, $data);
    return true;
  }

  /**
   * Переопределение движкового сохранятеля перевода
   */
  public static function saveLocaleData($id_ent, $locale, $table, $data) {
    if($id_ent == 0) return false;
    foreach ($data as $k => $v) {
      unset($id);

      $res = DB::query('SELECT id, `'.DB::quote($k, true).'` FROM `'.PREFIX.DB::quote($table, true).'` WHERE id = '.DB::quoteInt($id_ent));
      $row = DB::fetchAssoc($res);
      if($row[$k] == $v) {
        continue;
      }

      $res = DB::query('SELECT id FROM '.PREFIX.'locales WHERE id_ent = '.DB::quoteInt($id_ent).' AND
        locale = '.DB::quote($locale).' AND `table` = '.DB::quote($table).' AND field = '.DB::quote($k));
      while ($row = DB::fetchAssoc($res)) {
        $id = $row['id'];
      }

      if(!empty($id)) {
        DB::query('UPDATE '.PREFIX.'locales SET text = '.DB::quote($v).' WHERE id = '.DB::quoteInt($id));
      } else {
        DB::query('INSERT INTO '.PREFIX.'locales (id_ent, locale, `table`, field, text) VALUES
          ('.DB::quote($id_ent).', '.DB::quote($locale).', '.DB::quote($table).', '.DB::quote($k).', '.DB::quote($v).')');
      }
    }
  }

  /**
   * Получает доступные свойства характеристики
   *
   * @return void
   */
  public static function getDataId(){
    $options = self::getOptions();
    $sql = 'SELECT `id`, `name` FROM `'.PREFIX.'property_data`
    WHERE `prop_id` = '.$options['propertyId'];
    $result = DB::query($sql);
    if (!$result) return false;
    $list = array();

    while ($row = DB::fetchAssoc($result)) {
      $list[] = array('id' => $row['id'], 'name' => $row['name']);
    }

    return $list;
  }

}
