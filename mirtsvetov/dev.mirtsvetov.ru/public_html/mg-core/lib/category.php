<?php

/**
 * Класс Category - совершает все операции с категориями товаров.
 * - Создает новую категорию;
 * - Удаляет категорию; 
 * - Редактирует категорию;
 * - Возвращает список id всех вложенных категорий;
 * - Возвращает древовидный список категорий, пригодный для использования в меню;
 * - Возвращает массив id категории и ее заголовок;
 * - Возвращает иерархический массив категорий;
 * - Возвращает отдельные пункты списка заголовков категорий.
 * - Генерирует UL список категорий для вывода в меню.
 * - Экземпляр класса категорий хранится в реестре класс MG
 * <code>
 * //пример вызова метода getCategoryListUl() из любого места в коде.
 * MG::get('category')->getCategoryListUl()
 * </code>
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Category {

  // Массив категрорий.
  private $categories;
  private $listCategoryId;

  private static $optCats; //Доп поля в категориях

  public function __construct() {
    // проверка целостности NESTED SETS
    $res = DB::query('SELECT COUNT(id), MIN(left_key), MAX(right_key) FROM '.PREFIX.'category', true);
    $resCheck = DB::fetchAssoc($res);
    if($res) {
      if(($resCheck['MIN(left_key)'] != 2) || ((($resCheck['COUNT(id)'] * 2) + 1) != $resCheck['MAX(right_key)'])) {
        self::moveBrokenCats();
        self::startIndexation();
        Storage::clear();
      }
    }
    // получаем список категорий
    $this->categories = Storage::get('categoryInit-'.LANG);
    
	// проверка корректности ссылок в кэше
    if($this->categories != null) {
  		$arrkey = array_keys($this->categories);
  		$firsdId = $arrkey[0];
  		$normalLink = SITE.'/'.$this->categories[$firsdId]['parent_url'].$this->categories[$firsdId]['url'];
  		if($this->categories[$firsdId]['link'] != $normalLink) {
  			$this->categories = null;
  		}
    }
    //Получаем сразу один раз все доп поля+
    $sql = DB::query("SELECT * FROM `".PREFIX."category_opt_fields_content`");
    self::$optCats = DB::fetchAssoc($sql);

    if($this->categories == null) {  

      $result = DB::query('SELECT * FROM `'.PREFIX.'category` ORDER BY sort');
      $listId = "";
      while ($cat = DB::fetchAssoc($result)) {
        $listId .= ','.$cat['id'];
        $link = SITE.'/'.$cat['parent_url'].$cat['url'];              
        $cat['link'] = $link;

        MG::loadLocaleData($cat['id'], LANG, 'category', $cat);

        $this->categories[$cat['id']] = $cat;     
        $this->categories[$cat['id']]['userProperty'] = array();
        $this->categories[$cat['id']]['propertyIds'] = array();
      }
      if($listId) {
        $listId = "in (".ltrim($listId,',').")";    
      }
      
      // старый кривовастый подсчет товаров для меню
      if(MG::getSetting('catalogPreCalcProduct') == 'old') {
        if (!empty($this->categories) && is_array($this->categories)) {
          foreach ($this->categories as $key => $value) {
            $this->categories[$key]['countProduct'] = 0; 
          }
        }

        $onlyInCount = '';
        
        if(MG::getSetting('printProdNullRem') == "true") {
          if(MG::enabledStorage()) {
            $onlyInCount = 'AND p.`storage_count`';
          } else {
            $onlyInCount = 'AND ABS(IFNULL( pv.`count` , 0 ) ) + ABS( p.`count` ) >0'; // ищем только среди тех которые есть в наличии
          }
        }    

        // получаем строку с идентификаторами дополнительных категорий
	     $idRow = '';
        $res = DB::query("SELECT GROUP_CONCAT(REPLACE(`inside_cat`, `cat_id`, '')) AS insideCatRow FROM ".PREFIX."product WHERE `inside_cat` <> ''");
        while ($row = DB::fetchAssoc($res)) {  
          $idRow = ','.$row['insideCatRow'].',';  
        }

        // viewdatA($idRow);
        // viewdatA(1);

        // получаем количесво товаров для каждой категории
        $res = DB::query("SELECT cat_id, count(DISTINCT p.id) as count FROM `".PREFIX."product` p 
          LEFT JOIN `".PREFIX."product_variant` as pv ON p.id = pv.product_id
          LEFT JOIN `".PREFIX."category` c ON c.`id`=p.`cat_id`"
          ." WHERE p.`activity` = 1 AND c.`id` IS NOT NULL ".$onlyInCount." GROUP BY cat_id");
        while ($row = DB::fetchAssoc($res)) {  
          $this->categories[$row['cat_id']]['countProduct'] = $row['count'];  
        }

        $res = DB::query('SELECT id FROM '.PREFIX.'category');
        while ($row = DB::fetchAssoc($res)) {
			 if (!isset($this->categories[$row['id']]['countProduct'])) {
				 $this->categories[$row['id']]['countProduct'] = 0;
			 }
          $this->categories[$row['id']]['countProduct'] += substr_count(','.$idRow.',', ','.$row['id'].',');
        }
      }

      // viewdata($this->categories);

      // для каждой категории получаем массив пользовательских характеристик
      // $res = DB::query("
      //     SELECT p.*, c.category_id
      //     FROM `".PREFIX."category_user_property` AS c, `".PREFIX."property` AS p
      //     WHERE c.category_id ".$listId."
      //     AND (p.id = c.property_id OR p.all_category = 1)
      //     ORDER BY `sort` DESC"
      // );
      
      // while ($prop = DB::fetchAssoc($res)) {
      //   $prop['type_view'] = 'type_view';
      //   $this->categories[$prop['category_id']]['userProperty'][$prop['id']] = $prop;
      //   $this->categories[$prop['category_id']]['propertyIds'][] = $prop['id'];
      // }
      Storage::save('categoryInit-'.LANG, $this->categories);
    }
 
  }

  /**
   * чинит кривые категории
   * @access private
   */
  static function moveBrokenCats() {
    $res = DB::query("SELECT `id` FROM `".PREFIX."category` WHERE (`parent` NOT IN (SELECT `id` FROM `".PREFIX."category`) and `parent` > 0) OR `id` = `parent`");
    while ($row = DB::fetchAssoc($res)) {
      DB::query("UPDATE ".PREFIX."category SET `parent` = 0, `parent_url` = '' WHERE id = ".DB::quoteInt($row['id']));
    }
  }

  /**
   * запускает генерацию NESTED SETS
   * @access private
   */
  public function startIndexation() {
    $_categories = null;
    $_categories[-1][0] = array(
      'id' => 0,
      'sort' => -1,
      'parent' => -1);
    $result = DB::query('SELECT id, parent, sort FROM `'.PREFIX.'category` ORDER BY sort');
    while ($cat = DB::fetchAssoc($result)) {
      $_categories[$cat['parent']][$cat['id']] = $cat;     
    }
    self::indexation($_categories);
  }

  /**
   * генерация NESTED SETS
   * @access private
   */
  public static function indexation($_categories, $parent = -1, $level = 0, $left_key = 0, $right_key = 0) {
    $catArray = array();
    if (!empty($_categories[$parent])) {
      foreach ($_categories[$parent] as $category) { 
        if ($parent == $category['parent']) {  
          $left_key += 2;
          $category['left_key'] = $left_key - 1;
          $category['right_key'] = $left_key;
          $child = self::indexation($_categories, $category['id'], $level+1, $category['left_key'], $category['right_key']);
          if (!empty($child)) {
            $chAr = end($child);
            $right_key = $chAr['right_key'] + 1;
            $left_key = $chAr['right_key'] + 1;
            $category['right_key'] = $right_key;
            $array = $category;
            $array['level'] = $level;  
            $array['child'] = $child;       
          } else {
            $array = $category;
            $array['level'] = $level;  
          }
          $catArray[] = $array;
          $toDb = $array;
          unset($toDb['child']);
          unset($toDb['sort']);
          unset($toDb['parent']);
          DB::query('UPDATE '.PREFIX.'category SET '.DB::buildPartQuery($toDb).' WHERE id = '.DB::quote($toDb['id']));
        }
      }
    }
    $result = $catArray;
    return $result;
  }
  
 /**
   * Возвращает полный url категории по ее id.
   * <code>
   *  $res = MG::get('category')->getParentUrl(12);
   *  viewData($res);
   * </code>
   * @param $parentId - id категории для которой нужно найти UR родителя.
   * @return string
   */
  public function getParentUrl($parentId) {
    $cat = $this->getCategoryById($parentId);
    $res = !empty($cat) ? $cat['parent_url'].$cat['url'] : '';
    return $res ? $res.'/' : '';
  }
  
  /**
   * Сжимает изображение категории, по заданным в настройках параметрам.
   * <code>
   *  $imageUrl = 'uploads/image.png';
   *  $res = MG::get('category')->resizeCategoryImg($imageUrl);
   *  viewData($res);
   * </code>
   * @param string путь к файлу
   * @return string
   */
  public static function resizeCategoryImg($file, $id, $prefix, $resize = true) {
    $imgFolder = 'uploads'.DS.'category'.DS.$id;

    if ($prefix == 'menu_') {
      $categoryImgHeight = MG::getSetting('categoryIconHeight')?MG::getSetting('categoryIconHeight'):200;
      $categoryImgWidth = MG::getSetting('categoryIconWidth')?MG::getSetting('categoryIconWidth'):200;
    } else {
      $categoryImgHeight = MG::getSetting('categoryImgHeight')?MG::getSetting('categoryImgHeight'):200;
      $categoryImgWidth = MG::getSetting('categoryImgWidth')?MG::getSetting('categoryImgWidth'):200;
    }

    $file = urldecode($file);
    $file = str_replace('\\', '/', $file);
    $arName = URL::getSections($file);
    $name = array_pop($arName);
    $arNameExt = explode('.', $name);
    $ext = array_pop($arNameExt);
    $name = MG::translitIt(implode('.', $arNameExt));
    if ($prefix !== '' && strpos($name, $prefix) === 0) {
      $prefix = '';
    }
    $pos = strpos($name, '_-_time_-_');
    if ($pos) {
      $name = substr($name, ($pos+10));
      if (MG::getSetting('addDateToImg') == 'true') {
        $name .= date("_Y-m-d_H-i-s");
      }
    }
    
    $name = $prefix.$name.'.'.$ext;
    if ('svg' == strtolower($ext) || 'gif' == strtolower($ext) || 'webp' == strtolower($ext) || !$resize) {
      @mkdir(SITE_DIR.$imgFolder, 0777, true);

      if (file_exists(SITE_DIR.$file)) {
        $from = str_replace(DS.DS, DS, SITE_DIR.$file);
        $to = SITE_DIR.$imgFolder.DS.$name;
        copy($from , $to);
        if (strpos($file, 'uploads/prodtmpimg/') !== false) {
          unlink($from);
        }
      } elseif (file_exists(SITE_DIR.'uploads'.DS.$file)) {
        $from = str_replace(DS.DS, DS, SITE_DIR.'uploads'.DS.$file);
        $to = SITE_DIR.$imgFolder.DS.$name;
        copy($from , $to);
        if (strpos($file, 'uploads/prodtmpimg/') !== false) {
          unlink($from);
        }
      } else {
        $upload = new Upload(false);
        $imageData = $upload->uploadImageFromUrl($file, false);
        if ($imageData['status'] === true) {
          $name = $imageData['newName'];
          $file = 'uploads'.DS.$imageData['data'];
          $from = str_replace(DS.DS, DS, SITE_DIR.$file);
          $to = SITE_DIR.$imgFolder.DS.$name;
          copy($from, $to);
          unlink($from);
        } else {
          return '';
        }
      }
      Webp::createWebpImg(SITE_DIR.$imgFolder.DS.$name);
      return DS.$imgFolder.DS.$name;
    }  
    
    $upload = new Upload(false);
    if (file_exists(SITE_DIR.$file)) {
      $upload->_reSizeImage($name, SITE_DIR.$file, $categoryImgWidth, $categoryImgHeight, "PROPORTIONAL", $imgFolder.DS, true, true);
      Webp::createWebpImg(SITE_DIR.$imgFolder.DS.$name);
      if (strpos($file, 'uploads/prodtmpimg/') !== false) {
        unlink(SITE_DIR.$file);
      }
    } elseif (file_exists(SITE_DIR.'uploads'.DS.$file)) {
        $upload->_reSizeImage($name, SITE_DIR.'uploads'.DS.$file, $categoryImgWidth, $categoryImgHeight, "PROPORTIONAL", $imgFolder.DS, true, true);
        Webp::createWebpImg(SITE_DIR.$imgFolder.DS.$name);
        if (strpos($file, 'uploads/prodtmpimg/') !== false) {
          unlink(SITE_DIR.'uploads'.DS.$file);
        }
      } else {
      $imageData = $upload->uploadImageFromUrl($file, false);
      if ($imageData['status'] === true) {
        $name = $imageData['newName'];
        $upload->_reSizeImage($name, SITE_DIR.'uploads'.DS.$imageData['data'], $categoryImgWidth, $categoryImgHeight, "PROPORTIONAL", $imgFolder.DS, true, true);
        Webp::createWebpImg(SITE_DIR.$imgFolder.DS.$name);
        $file = DS.'uploads'.DS.$imageData['data']; 
        if (strpos($file, 'uploads/prodtmpimg/') !== false) {
          unlink(SITE_DIR.$file);
        }
      } else {
        return '';
      }
    }
    return DS.$imgFolder.DS.$name;
  }

  /**
   * Создает новую категорию.
   * <code>
   *  $array = array(
   *    'id' => ,              // id
   *    'unit' => 'шт.',       // единица измерения товаров
   *    'title' => 123,        // название категории
   *    'url' => 123,          // url последней секции категории
   *    'parent' => 0,         // id родительской категории
   *    'html_content' => ,    // описание категории
   *    'meta_title' => ,      // заголовок страницы
   *    'meta_keywords' => ,   // ключевые слова
   *    'meta_desc' => ,       // мета описание
   *    'image_url' => ,       // ссылка на изображение
   *    'menu_icon' => ,       // ссылка на иконку меню
   *    'invisible' => 0,      // параметр видимости
   *    'rate' => 0,           // наценка
   *    'seo_content' => ,     // seo контент
   *    'seo_alt' => ,         // seo 
   *    'seo_title' => ,       // seo
   *    'parent_url' => ,      // url родительской категории
   *  );
   *  $res = MG::get('category')->addCategory($array);
   *  viewData($res);
   * </code>
   * @param array $array массив с данными о категории.
   * @param array $checks массив со списком проверок и подготовок данных к созданию категории
   * <code>
   *    $checks = [
   *      'url'             => (bool) Проверять ли и подготавливать url
   *      'html'            => (bool) Отфильтровать html теги в некоторых полях
   *      'urlDuplicate'    => (bool) Проверка на дублирующуюся ссылку
   *      'resizeImages'    => (bool) Обрезать изображения
   *      'moveCKImages'    => (bool) Перенос изображений из CK Editor
   *      'lang'            => (bool) Подготовка локалей
   *      'childrenParents' => (bool) Проверка наследования категорий
   *      'indexation'      => (bool) Сгенерировать NESTED SETS
   *      'clearCache'      => (bool) Очистить кэш категорий
   *    ];
   * </code>
   * @return bool|int в случае успеха возвращает id добавленной категории.
   */
  public function addCategory($array, $checks = []) {    
    $checksRules = [
      'url',
      'html',
      'urlDuplicate',
      'resizeImages',
      'moveCKImages',
      'lang',
      'childrenParents',
      'indexation',
      'clearCache'
    ];

    foreach ($checksRules as $checkRule) {
      if (!isset($checks[$checkRule])) {
        $checks[$checkRule] = true;
      }
    }

    if(isset($array['id']) && $array['id'] == "") unset($array['id']); // удаление для минимаркета/php 5.4
    $result = array();

    if(!empty($array['url']) && $checks['url']) {
      $array['url'] = URL::prepareUrl($array['url']); 
    }
    
    if (!empty($checks['htmlspecialchars'])) {
      $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt','seo_alt','seo_content','menu_seo_alt','menu_seo_content');

      foreach ($array as $k => $v) {
        if(in_array($k, $maskField)) {
          $v = htmlspecialchars_decode($v);
          $array[$k] = htmlspecialchars($v);     
        }
      }
    }
    
    // Исключает дублирование.
    $dublicatUrl = false;
    
    if ($checks['urlDuplicate']) {
      $tempArray = $this->getCategoryByUrl($array['url'],$array['parent_url']);
      if (!empty($tempArray)) {
        $dublicatUrl = true;
      }
    }

    unset($array['csv']);
    if (DB::buildQuery('INSERT INTO `'.PREFIX.'category` SET ', $array)) {
      $id = DB::insertId();

      if ($checks['resizeImages']) {
        if(!empty($array['image_url'])) {
          $imageUrl = $array['image_url'];
          $array['image_url'] = self::resizeCategoryImg($array['image_url'], $id, '');
          if (empty($array['image_url'])) {
            $array['image_url'] = $imageUrl;
          }
        }
    
        if(!empty($array['menu_icon'])) {
          $rawIcon = $array['menu_icon'];
          $array['menu_icon'] = self::resizeCategoryImg($rawIcon, $id, 'menu_', false);
          if (empty($array['menu_icon'])) {
            $array['menu_icon'] = $rawIcon;
          }
          if ($rawIcon !== $array['image_url'] && $rawIcon !== $array['menu_icon']) {
            $rawIconPath = str_replace(DS.DS, DS, SITE_DIR.$rawIcon);
            unlink($rawIconPath);
            $rawWebpIconPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', str_replace('/uploads/', '/uploads/webp/', $rawIconPath));
            if (is_file($rawWebpIconPath)) {
              unlink($rawWebpIconPath);
            }
          }
        }
      }

      $arr = array(
        'id' => $id,
        'sort' => $id,
        'url' => $array['url'],
        'image_url' => isset($array['image_url'])?$array['image_url']:'',
        'menu_icon' => isset($array['menu_icon'])?$array['menu_icon']:'',
        'html_content' => isset($array['html_content'])?$array['html_content']:'',
        'seo_content' => isset($array['seo_content'])?$array['seo_content']:'',
      );
      // Если url дублируется, то дописываем к нему id продукта.
      if ($dublicatUrl) {
        $arr['url'] = $array['url'].'_'.$id;
      }
      $this->listCategoryId[] = $id;
      $this->updateCategory($arr, $checks);     
      $array['id'] = $id;
      //логирование
      LoggerAction::logAction('Category',__FUNCTION__, $id);
      $result = $array;
    }

    if ($checks['clearCache']) {
      //очищам кэш категорий
      Storage::clear('category');
    }
    
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Изменяет данные о категории.
   * <code>
   *  $array = array(
   *    'id' => ,              // id
   *    'unit' => 'шт.',       // единица измерения товаров
   *    'title' => 123,        // название категории
   *    'url' => 123,          // url последней секции категории
   *    'parent' => 0,         // id родительской категории
   *    'html_content' => ,    // описание категории
   *    'meta_title' => ,      // заголовок страницы
   *    'meta_keywords' => ,   // ключевые слова
   *    'meta_desc' => ,       // мета описание
   *    'image_url' => ,       // ссылка на изображение
   *    'menu_icon' => ,       // ссылка на иконку меню
   *    'invisible' => 0,      // параметр видимости
   *    'rate' => 0,           // наценка
   *    'seo_content' => ,     // seo контент
   *    'seo_alt' => ,         // seo 
   *    'seo_title' => ,       // seo
   *    'parent_url' => ,      // url родительской категории
   *  );
   *  $res = MG::get('category')->updateCategory($array);
   *  viewData($res);
   * </code>
   * @param array $array массив с данными о категории.
   * @param array $checks массив со списком проверок и подготовок данных к созданию категории
   *    $checks = [
   *      'url'             => (bool) Проверять ли и подготавливать url
   *      'html'            => (bool) Отфильтровать html теги в некоторых полях
   *      'urlDuplicate'    => (bool) Проверка на дублирующуюся ссылку
   *      'resizeImages'    => (bool) Обрезать изображения
   *      'moveCKImages'    => (bool) Перенос изображений из CK Editor
   *      'lang'            => (bool) Подготовка локалей
   *      'childrenParents' => (bool) Проверка наследования категорий
   *      'indexation'      => (bool) Сгенерировать NESTED SETS
   *      'clearCache'      => (bool) Очистить кэш категорий
   *    ];
   * @return bool|int в случае добавления возвращает id добавленной категории.
   */
  public function updateCategory($array, $checks = []) {
    $checksRules = [
      'url',
      'html',
      'urlDuplicate',
      'resizeImages',
      'moveCKImages',
      'lang',
      'childrenParents',
      'indexation',
      'clearCache'
    ];

    foreach ($checksRules as $checkRule) {
      if (!isset($checks[$checkRule])) {
        $checks[$checkRule] = true;
      }
    }

    $id = $array['id'];

    if (
      !empty($array['image_url']) || !empty($array['menu_icon']) || 
      (isset($array['html_content']) && strpos($array['html_content'], 'src="'.SITE.'/uploads')) || 
      (isset($array['seo_content']) && strpos($array['seo_content'], 'src="'.SITE.'/uploads'))
    ) {
      @mkdir(SITE_DIR.'uploads'.DS.'category', 0755);
      @mkdir(SITE_DIR.'uploads'.DS.'category'.DS.$id, 0755);
    }
    if ($checks['moveCKImages']) {
      if (isset($array['html_content']) && $array['html_content']) {
        $array['html_content'] = MG::moveCKimages($array['html_content'], 'category', $id, 'desc', 'category', 'html_content');
      }
      if (isset($array['seo_content']) && $array['seo_content']) {
        $array['seo_content'] = MG::moveCKimages($array['seo_content'], 'category', $id, 'seo', 'category', 'seo_content');
      }
    }

    $result = false;
    if ($checks['url']) {
      if(!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);     
      }elseif(!empty($array['title'])) {
        $array['url'] = MG::translitIt($array['title']);
        $array['url'] = URL::prepareUrl($array['url']);
      }
    }

    
    // перехватываем данные для записи, если выбран другой язык
    if ($checks['lang']) {
      if (isset($array['lang'])) {
        $lang = $array['lang'];
      } else {
        $lang = null;
      }

     unset($array['lang']);

      $filter = array('title','meta_title','meta_keywords','meta_desc','html_content','html_content-seo','unit','seo_title','seo_alt','seo_content','menu_seo_alt','menu_seo_content','menu_title');
      $localeData = MG::prepareLangData($array, $filter, $lang);
    }

    if ($checks['html']) {
      $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt','seo_title','seo_alt','menu_seo_alt','menu_seo_content','menu_title');

      foreach ($array as $k => $v) {
        if(in_array($k, $maskField)) {
          $v = htmlspecialchars_decode($v);
          $array[$k] = htmlspecialchars($v);       
        }
      }
    }

    if ($checks['childrenParents']) {
      // Если назначаемая категория, является тойже.
      if (isset($array['parent']) && $array['parent']===$id) {
        $this->messageError = 'Нельзя назначить выбраную категорию родительской!';
        return false;
      }

      if ($id || $id===0) {
        $childsCaterory = $this->getCategoryList($id);
      }

      // Если есть вложенные, и одна из них назначена родительской.
      if (!empty($childsCaterory)) {
        foreach ($childsCaterory as $cateroryId) {
          if (isset($array['parent']) && $array['parent']==$cateroryId) {
            $this->messageError = 'Нельзя назначить выбраную категорию родительской!';         
            return false;
          }
        }
      }

      if (isset($_POST['parent']) && $_POST['parent']===$id && !isset($array['parent'])) {
        $this->messageError = 'Нельзя назначить выбраную категорию родительской!';
        return false;
      }
    }


    if ($checks['resizeImages']) {
      if(!empty($array['image_url'])) {
        $imageUrl = $array['image_url'];
        $array['image_url'] = self::resizeCategoryImg($array['image_url'], $id, '');
        if (empty($array['image_url'])) {
          $array['image_url'] = $imageUrl;
          $categoryImageDir = SITE_DIR.'uploads'.DS.'category'.DS.$id;
          if (is_file($categoryImageDir.DS.$imageUrl)) {
            $array['image_url'] = '/'.str_replace([SITE_DIR, DS], ['', '/'], $categoryImageDir.DS.$imageUrl);
          }
        }
      }

      if(!empty($array['menu_icon'])) {
        $menuIcon = $array['menu_icon'];
        $array['menu_icon'] = self::resizeCategoryImg($array['menu_icon'], $id, 'menu_', false);
        if (empty($array['menu_icon'])) {
          $array['menu_icon'] = $menuIcon;
          $categoryImageDir = SITE_DIR.'uploads'.DS.'category'.DS.$id;
          if (is_file($categoryImageDir.DS.$menuIcon)) {
            $array['menu_icon'] = '/'.str_replace([SITE_DIR, DS], ['', '/'], $categoryImageDir.DS.$menuIcon);
          }
        }
      }
    }
    
    if (!empty($this->categories) && is_array($this->categories)) {
      $catIds = array_keys($this->categories);
    } else {
      $catIds = array();
    }

    if (!empty($id) && 
      ((is_array($this->listCategoryId) && in_array($id, $this->listCategoryId)) ||
       (is_array($catIds) && in_array($id, $catIds)))
    ) {
      // обновляем выбраную категорию
      unset($array['csv']);
      //логирование
      LoggerAction::logAction('Category', __FUNCTION__, $array);
      if (DB::query('
        UPDATE `'.PREFIX.'category`
        SET '.DB::buildPartQuery($array).'
        WHERE id = '.DB::quote(intval($id), true))) {        
        $result = true;
      }
      
      if ($checks['lang']) {
        // сохраняем локализацию
        MG::saveLocaleData($id, $lang, 'category', $localeData);
      }

      if ($checks['childrenParents']) {
        // находим список всех вложенных в нее категорий
        if (isset($array['parent'])) {
          $arrayChildCat = $this->getCategoryList($array['parent']);
        }
        if (!empty($arrayChildCat)) {
          // обновляем parent_url у всех вложенных категорий, т.к. корень поменялся
          foreach ($arrayChildCat as $childCat) {
          
            $childCat = $this->getCategoryById($childCat);
            $upParentUrl = $this->getParentUrl($childCat['parent']);
            if(!empty($childCat['id'])) {
              if (DB::query('
                  UPDATE `'.PREFIX.'category`
                  SET parent_url='.DB::quote($upParentUrl).'
              WHERE id = '.DB::quoteInt($childCat['id'], true)));
            }
        
          }
        }
      }
    } else {
      unset($array['csv']);
      $result = $this->addCategory($array);      
    }

    if ($checks['indexation']) {
      // обновляем ключи
      $this->startIndexation();
    }

    if ($checks['clearCache']) {
      //очищам кэш категорий
      Storage::clear('category');
      Storage::clear('categoryInit-'.LANG);
    }
    
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Удаляет категорию.
   * <code>
   *  $res = MG::get('category')->delCategory(12);
   *  viewData($res);
   * </code>
   * @param int $id id удаляемой категории.
   * @return bool
   */
  public function delCategory($id) {
    $categories = $this->getCategoryList($id);
    $categories[] = $id;
    //логирование
    LoggerAction::logAction('Category',__FUNCTION__, $id);
    foreach ($categories as $categoryID) {
      DB::query('
        DELETE FROM `'.PREFIX.'category`
        WHERE id = %d
      ', $categoryID);
      $uploadsCategory = SITE_DIR.'uploads/category/'.$categoryID;
      $webpUploadsCategory = str_replace('/uploads/', '/uploads/webp/', $uploadsCategory);
      MG::rrmdir($uploadsCategory);
      MG::rrmdir($webpUploadsCategory);
    }

    //очищам кэш категорий
    Storage::clear('category');
    
    $args = func_get_args();
    $result = true;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

   /**
    * Возвращает закэшированное левое меню категорий.
    * <code>
    *  $res = MG::get('category')->getCategoriesHTML();
    *  viewData($res);
    * </code>
    * @return string
    */
   public function getCategoriesHTML() {  
      $result = Storage::get('getCategoriesHTML-'.LANG);     

      if($result == null) {        
                
        $category = $this->getHierarchyCategory();

        $result =  MG::layoutManager('layout_leftmenu', array('categories'=>$category));
      
        if (!empty($_SESSION['user']->enabledSiteEditor) && $_SESSION['user']->enabledSiteEditor == "false") {
          Storage::save('getCategoriesHTML-'.LANG, $result);        
        }
      }
     
      $args = func_get_args();
      return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
   }
   
    /**
    * Возвращает закэшированное горизонтальное меню категорий.
    * <code>
    *  $res = MG::get('category')->getCategoriesHorHTML();
    *  viewData($res);
    * </code>
    * @return string
    */
   public function getCategoriesHorHTML() {  
      $result = Storage::get('getCategoriesHorHTML-'.LANG);     

      if($result == null) {        
                
        $category = $this->getHierarchyCategory();
        $result =  MG::layoutManager('layout_horizontmenu', array('categories'=>$category));
      
        if ($_SESSION['user']->enabledSiteEditor == "false") {
          Storage::save('getCategoriesHorHTML-'.LANG, $result);        
        }
      }
     
      $args = func_get_args();
      return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
   }
   
  /**
   * Возвращает древовидный список категорий, пригодный для использования в меню.
   * <code>
   *  $res = MG::get('category')->getCategoryListUl();
   *  viewData($res);
   * </code>
   * @param int $parent id категории, для которой надо вернуть список.
   * @param int $type тип списка (для публичной части, либо для админки).
   * @param bool $recursion использовать рекурсию.
   * @return string
   */
  public function getCategoryListUl($parent = 0, $type = 'public', $recursion=true, $sql = true, $categories = array()) {
    // получаем данные об открытых категориях из куков  
    if(empty($this->openedCategory)) {
      if($type == 'admin') {
        if(!empty($_COOKIE['openedCategoryAdmin'])) {
          $this->openedCategory = json_decode($_COOKIE['openedCategoryAdmin']);    
        }
      } else {
        if(!empty($_COOKIE['openedCategory'])) {
          $this->openedCategory = json_decode($_COOKIE['openedCategory']);  
        }
      }
      if(empty($this->openedCategory)) {
        $this->openedCategory = array();
      }    
    }

    if($sql) $categories = self::getCategoryFromBdNestedH($parent);
    $print = '';
    if (empty($categories)) {
      $print = '';
    } else {
      $lang = MG::get('lang');
      $categoryArr = $categories[$parent];
      
      //для публичной части убираем из меню закрытые категории
      if($type == 'public') {
        foreach ($categoryArr as $key => $val) {
           if($val['invisible'] == 1) {
             unset($categoryArr[$key]);
           } 
        }
      }
      
      foreach ($categoryArr as $category) {
        if(!isset($category['id'])) break; //если категории неceotcndetn
        if ($parent == $category['parent']) {

          $flag = false;
          
          $mover = '';

          if ('admin' == $type) {
            $class = 'active';
            $title = $lang['ACT_EXPORT_CAT'];
            
            if($category['export'] == 0) {
              $class = '';
              $title = $lang['ACT_NOT_EXPORT_CAT'];
            }
            
            $export = '<div class="export tool-tip-bottom ' . $class . '" title="' . $title . '" data-category-id="' . $category['id'] . '"></div>';
            
            $class = 'active';
            $title = $lang['ACT_V_CAT'];
            
            if ($category['invisible'] == 1) {
              $class = '';
              $title = $lang['ACT_UNV_CAT'];
            }
            $classAct = 'active';
            $titleAct = $lang['ACT_V_CAT_ACT'];
            
            if ($category['activity'] == 0) {
              $classAct = '';
              $titleAct = $lang['ACT_UNV_CAT_ACT'];
              $titleAct .= ' '.$this->lang['ACT_UNV_CAT_ACT2'];
            }

            $checkbox = '<input type="checkbox" name="category-check">';
            $mover .= $checkbox . '<div class="mover"></div>'
              . '<div class="link-to-site tool-tip-bottom" title="' . $lang['MOVED_TO_CAT'] . '"  data-href="' . SITE . '/' . $category['parent_url'] . $category['url'] . '"></div>'.
              $export.'<div class="visible tool-tip-bottom ' . $class . '" title="' . $title . '" data-category-id="' . $category['id'] . '"></div>'.
              '<div class="activity tool-tip-bottom ' . $classAct . '" title="' . $titleAct . '" data-category-id="' . $category['id'] . '"></div>';
          }

          $slider = '>'.$mover;

          foreach ($categories as $sub_category) {             
            if (isset($sub_category['parent']) && ($category['id'] == $sub_category['parent'])) {
              $slider = ' class="slider">'.$mover.'<div class="slider_btn"></div>';
              $style = "";
              $opened = "";

              if(in_array( $category['id'],$this->openedCategory)) {
                $opened = " opened ";
                $style=' style="background-position: 0 0"';
              }
              
              $slider = ' class="slider">'.$mover.'<div class="slider_btn '.$opened.'" '.$style.'></div>';
              $flag = true;
              break;
            }
          }
            
          $rate = '';
          if ($category['rate']>0) {
            $rate = '<div class="sticker-menu discount-rate-up" data-cat-id="'.$category['id'].'"> '.$lang['DISCOUNT_UP'].' +'.($category['rate']*100).'% <div class="discount-mini-control"><span class="discount-apply-follow tool-tip-bottom"  title="Применить ко всем вложенным категориям" >&darr;&darr;</span> <span class="discount-cansel tool-tip-bottom" title="Отменить">x</span></div></div>';
          }
          if ($category['rate']<0) {
            $rate = '<div class="sticker-menu discount-rate-down" data-cat-id="'.$category['id'].'"> '.$lang['DISCOUNT_DOWN'].' '.($category['rate']*100).'% <div class="discount-mini-control"><span class="discount-apply-follow tool-tip-bottom"  title="Применить ко всем вложенным категориям">&darr;&darr;</span> <span class="discount-cansel tool-tip-bottom" title="Отменить">x</span></div></div>';
          }
          if ('admin'==$type) {
            $print.= '<li'.$slider.'<a role="button" href="javascript:void(0);" onclick="return false;" class="CategoryTree" rel="CategoryTree" id="'.$category['id'].'" parent_id="'.$category["parent"].'">'.$category['title'].'</a>
              '.$rate;
          } else {
            if ($category['invisible']!=1) {             
              $active = '';     
              if(URL::isSection($category['parent_url'].$category['url'])) {
                $active = 'class="active"';              
              }
              $category['title'] = MG::contextEditor('category', $category['title'], $category["id"],"category");              
              $print.= '<li'.$slider.'<a href="'.SITE.'/'.$category['parent_url'].$category['url'].'"><span '.$active.'>'.$category['title'].'</span></a>';
            }
          }

          if ($flag) {
            $display = "display:none";
            if(in_array( $category['id'],$this->openedCategory)) {
              $display = "display:block";
            }
            
      
            
            // если нужно выводить подкатегории то делаем рекурсию
            if ((($category['right_key'] - $category['left_key']) > 1) && $recursion) {  
              $sub_menu = '
              <ul class="sub_menu" style="'.$display.'">
                [li]
              </ul>';   
              $li = $this->getCategoryListUl($category['id'], $type, $recursion, false, $categories);         
              $print .= strlen($li)>0 ? str_replace('[li]', $li, $sub_menu) : "";
            }
           $print .= '</li>'; 
        
          } else {            
            $print .= '</li>';
          }
        }
      }
    }

    $args = func_get_args();
    $result = $print;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив вложенных категорий первого уровня.
   * <code>
   *  $parentId = 5; // id родительской категории
   *  $res = MG::get('category')->getChildCategoryIds($parentId);
   *  viewData($res);
   * </code>
   * @param int $parent id родительской категории.
   * @return string.
   */
  public function getChildCategoryIds($parentId = 0) {
    $result = array();

    $res = DB::query('
      SELECT id
      FROM `'.PREFIX.'category`
      WHERE parent = %d
      ORDER BY id
    ', $parentId);

    while ($row = DB::fetchArray($res)) {
      $result[] = $row['id'];
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список только id всех вложеных категорий.
   * <code>
   *  $parentId = 5; // id родительской категории
   *  $res = MG::get('category')->getCategoryList($parentId);
   *  viewData($res);
   * </code>
   * @param int $parent id родительской категории
   * @return array
   */
  public function getCategoryList($parent = 0, $sql = true, $categories = array()) {
    if(!MG::isAdmin() && $parent == 0) $result = Storage::get('getCategoryList');
    if(empty($result)) {
      if($sql) $categories = self::getCategoryFromBdNestedH($parent, true);
      if (isset($categories[$parent]) && is_array($categories[$parent])) {
        foreach ($categories[$parent] as $category) {
        
          if(!isset($category['id'])) {break;}//если категории несуществует
          
          if ($parent==$category['parent']) {
            $this->listCategoryId[] = $category['id'];      
            if(($category['right_key'] - $category['left_key']) > 1) {    
              $this->getCategoryList($category['id'], false, $categories);
            }
          }
        }
      }

      if (!empty($this->listCategoryId)) {
        $this->listCategoryId = array_unique($this->listCategoryId, SORT_REGULAR);
      }
      $result = $this->listCategoryId;
      if(!MG::isAdmin() && $parent == 0) Storage::save('getCategoryList', $result);
    }
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список id всех вложеных категорий.
   * Отличие от getCategoryList в том, что при вызове данного метода происходит перезапись переменной listCategoryId
   * В следствии чего не происходит добавления новых категорий к уже существующим категориям в этой переменной, если этот метод был вызван ранее
   * <code>
   *  $parent = 5; // id родительской категории
   *  $res = MG::get('category')->getChildsCategory($parentId);
   *  viewData($res);
   * </code>
   * @param int $parent id родительской категории
   * @return array
   */

  public function getChildsCategory($parent=0){
    unset($this->listCategoryId);
    return MG::get('category')->getCategoryList($parent);
  }


  /**
   * Возвращает массив id категории и ее заголовок.
   * <code>
   *  $res = MG::get('category')->getCategoryTitleList();
   *  viewData($res);
   * </code>
   * @return array
   */
  public function getCategoryTitleList() {
    $titleList[0] = 'Корень каталога';
    if (!empty($this->categories))
      foreach ($this->categories as $category) {
        $titleList[$category['id']] = $category['title'];
      }

    $args = func_get_args();
    $result = $titleList;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * получение иерархии категорий через NESTED SETS
   * @access private
   */
  public function getCategoryFromBdNestedH($id = 0, $lite = false) {
    $data = array();
    $tmp = MG::get('getCategoryFromBdNestedH'.serialize(array($id,$lite)));
    if ($tmp !== null) {return $tmp;}
    if(!URL::isSection('mg-admin') && $id == 0) $data = Storage::get('getCategoryFromBdNestedH-'.LANG);
    if(!$data) {
      // достаем ключи для выборки
	    $leftKey = 1;
	    $rightKey = 999999999;
      if($id != 0) {
        $res = DB::query('SELECT left_key, right_key FROM '.PREFIX.'category WHERE id = '.DB::quoteInt($id));
        while($row = DB::fetchAssoc($res)) {
          $leftKey = $row['left_key'];
          $rightKey = $row['right_key'];
        }
      }
      // достаем категории для построения
      $res = DB::query('SELECT * FROM '.PREFIX.'category WHERE left_key > '.DB::quoteInt($leftKey).' AND right_key < '.DB::quoteInt($rightKey).' ORDER BY `sort` ASC');
      while($row = DB::fetchAssoc($res)) {
        $link = SITE.'/'.$row['parent_url'].$row['url'];              
        $row['link'] = $link;

        MG::loadLocaleData($row['id'], LANG, 'category', $row);

        $data[$row['parent']][$row['id']] = $row;     
        $data[$row['parent']][$row['id']]['userProperty'] = array();
        $data[$row['parent']][$row['id']]['propertyIds'] = array();
      }

      if(!URL::isSection('mg-admin') && $id == 0) Storage::save('getCategoryFromBdNestedH-'.LANG, $data);
    }
    MG::set('getCategoryFromBdNestedH'.serialize(array($id,$lite)), $data);
    return $data;
  }
  
  /**
   * Возвращает вложенные категории одного уровня в выбранной.
   */
  public function getInsideCategory($idCategory=0) {
    $res = MG::get('category')->getChildCategoryIds($idCategory);
    foreach($res as $key=>$catId){
      $res[$key] = $this->categories[$catId];
    }
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $res, $args);
  }


  /**
   * Возвращает иерархический массив категорий.
   * <code>
   *  $res = MG::get('category')->getHierarchyCategory();
   *  viewData($res);
   * </code>
   * @param int $parent id родительской категории.
   * @param bool $onlyActive возвращать только активные категории.
   * @return array
   */
  public function getHierarchyCategory($parent = 0, $onlyActive = false, $sql = true, $categories = array()) {
    if(!MG::isAdmin() && !$onlyActive && $parent == 0) $result = Storage::get('getHierarchyCategory'.'-'.LANG);
    if(empty($result)) {
      if($sql) $categories = self::getCategoryFromBdNestedH($parent);
      $catArray = array();
      // viewdata($categories);  
      // viewdata(count($categories));
      if (!empty($categories)) {

        if(!MG::isAdmin()) {
          $cacheName = 'haveOptCats';
          $haveOptCats = Storage::get($cacheName);
          if (!isset($haveOptCats)) {
            $optCats =self::$optCats;
            if (empty($optCats)) {
              $haveOptCats = false;
            } else {
              $haveOptCats = true;
            }
            Storage::save($cacheName, $haveOptCats, 60*60); // 1 час
          }
        }

        foreach ($categories[$parent] as $category) {     
          unset($child);
          if(!isset($category['id'])) {break;}//если категории неceotcndetn
            if ($onlyActive && $category['invisible']==="1") {
               continue;
            }
           
            if ($parent == $category['parent']) {
              // проверка на то, есть ли дочерние элементы
              if(($category['right_key'] - $category['left_key']) > 1) {
                $child = $this->getHierarchyCategory($category['id'], $onlyActive, false, $categories);
              }

              if (!empty($child)) {
                $array = $category;
                if (!array_key_exists('insideProduct', $array)) {
                  $array['insideProduct'] = 0;
                }
                usort($child, array(__CLASS__, "sort"));        
                $array['child'] = $child; 
                
                $data = 0;
                if(MG::getSetting('catalogPreCalcProduct') == 'old') {
                  foreach($child as $item) {
                    $data += $item['countProduct'];
                  }
                  $array['insideProduct'] = isset($this->categories[$category['id']])?$this->categories[$category['id']]['countProduct']:0 + $data;
                } else {
                  $array['insideProduct'] += isset($this->categories[$category['id']])?$this->categories[$category['id']]['countProduct']:0;
                }            
              } else {
                $array = $category;
                if(empty($array['insideProduct'])) $array['insideProduct'] = 0;
                $array['insideProduct'] += !empty($this->categories[$category['id']]['countProduct'])?$this->categories[$category['id']]['countProduct']:0;
              }
              $array['countProduct'] = $array['insideProduct'];
              if(!MG::isAdmin() && $haveOptCats === true) {
                $array['opFields'] = Models_OpFieldsCategory::getContent($array['id'], false);
              }
              $catArray[] = $array;
            }
          
        }
      }
      $result = $catArray;
      if(!MG::isAdmin() && !$onlyActive && $parent == 0) Storage::save('getHierarchyCategory'.'-'.LANG, $result);
    }
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }


  
  /**
   * Возвращает отдельные пункты списка заголовков категорий.
   * <code>
   *  $arrayCategories = MG::get('category')->getArrayCategory();
   *  $res = MG::get('category')->getTitleCategory($arrayCategories);
   *  viewData($res);
   * </code>
   * @param array $arrayCategories массив с категориями.
   * @param int $selectCategory id выбранной категории.
   * @param bool $modeArray - если установлен этот флаг, то  результат вернет массив а не HTML список
   * @param string $prefix префикс для подкатегорий
   * @return string
   */
  public function getTitleCategory($arrayCategories, $selectCategory = 0, $modeArray = false, $prefix = '  --  ', $printChildIds = false) {
    // MG::LOGER($arrayCategories);
	  $catArr = array();
    if($modeArray) {
      global $catArr;
    }
    global $lvl;
    $option = '';
    $level = 0;
    foreach ($arrayCategories as $category) {
      $select = '';
      if ($selectCategory==$category['id']) {
        $select = 'selected = "selected"';
      }

      if ($printChildIds) {
        if (!empty($category['child'])) {
          $childIdsArr = self::getChildIds($category['child']);
          $childIds = 'data-childids=['.implode(',', $childIdsArr).']';
        } else {
          $childIdsArr = array();
          $childIds = 'data-childids=[]';
        }
      } else {
        $childIdsArr = array();
        $childIds = '';
      }

      $option .= '<option '.$childIds.' data-parent='.$category['parent'].' value='.$category['id'].' '.$select.' >';
      $option .= str_repeat($prefix, $lvl);
      $option .= $category['title'];
      $option .= '</option>';
      if ($modeArray && $printChildIds) {
        $catArr[$category['id']] = array(
          'title'=>str_repeat($prefix, $lvl).$category['title'],
          'parent'=>$category['parent'],
          'children'=>$childIdsArr
        );
      } else {
        $catArr[$category['id']] = str_repeat($prefix, $lvl).$category['title'];
      }

      if (isset($category['child'])) {
        $lvl++;
        $tmp = $this->getTitleCategory($category['child'],$selectCategory,$modeArray,$prefix,$printChildIds);
        if (is_string($tmp)) {
          $option .= $tmp;
        }
        $lvl--;
      }
    }
    $args = func_get_args();
    
    $result = $option;  
    if($modeArray) {
      $result = $catArr;      
    }
  
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  private static function getChildIds($children, $childIds = array()) {
    foreach ($children as $child) {
      $childIds[] = $child['id'];
      if (!empty($child['child'])) {
        $childIds = array_merge($childIds, self::getChildIds($child['child']));
      }
    }
    return $childIds;
  }

  /**
   * Перемещает категорию
   * @param string $catId id перемещаемой категории.
   * @param string $parentId id категории, в которую перемещать.
   * @return void
   */
  public function moveCategory($catId, $parentId) {
    $parentUrl = $this->getParentUrl($parentId);
    DB::query("UPDATE `".PREFIX."category` SET 
      `parent_url` = ".DB::quote($parentUrl).",
      `parent` = ".DB::quoteInt($parentId)."
      WHERE `id` = ".DB::quoteInt($catId));
    $res = DB::query("SELECT `id` FROM `".PREFIX."category` 
      WHERE `parent` = ".DB::quote($catId));
    while ($row = DB::fetchAssoc($res)) {
      $this->moveCategory($row['id'], $catId);
    }
  }

  /**
   * Получает параметры категори по его URL.
   * <code>
   *  $url = 'chasy-sekundomery-shagomery';
   *  $parentUrl = 'aksessuary';
   *  $res = MG::get('category')->getCategoryByUrl($url, $parentUrl);
   *  viewData($res);
   * </code>
   * @param string $url запрашиваемой категории.
   * @param string $parentUrl родительской категории.
   * @return array массив с данными о категории.
   */
  public function getCategoryByUrl($url, $parentUrl="") {
    $result = array();

    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'category`
      WHERE url = '.DB::quote($url).' AND parent_url = '.DB::quote($parentUrl).'
    ');

    if (!empty($res)) {
      if ($cat = DB::fetchAssoc($res)) {
        $result = $cat;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает параметры категори по его Id.
   * <code>
   *  $res = MG::get('category')->getCategoryById(12);
   *  viewData($res);
   * </code>
   * @param string $id запрашиваемой  категории.
   * @return array массив с данными о категории.
   */
  public function getCategoryById($id, $localize = false) {
    $result = array();
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'category`
      WHERE id = '.DB::quote($id));

    if (!empty($res)) {
      if ($cat = DB::fetchArray($res)) {
        if ($localize) {
          MG::loadLocaleData($cat['id'], LANG, 'category', $cat);
        }
        $result = $cat;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив пользовательских характеристик для заданной категории.
   * <code>
   *  $res = MG::get('category')->getUserPropertyCategoryById(12);
   *  viewData($res);
   * </code>
   * @param string $id запрашиваемой  категории.
   * @return array
   */
  public function getUserPropertyCategoryById($id) {
    return isset($this->categories[$id]['userProperty'])?$this->categories[$id]['userProperty']:null;
  }
  
   /**
   * Возвращает массив id всех характеристик для заданной категории.
   * <code>
   *  $res = MG::get('category')->getPropertyForCategoryById(12);
   *  viewData($res);
   * </code>
   * @param string $id запрашиваемой категории.
   * @return array 
   */  
  public function getPropertyForCategoryById($id) {
    return isset($this->categories[$id]['propertyIds'])?$this->categories[$id]['propertyIds']:null;
  }
  
   /**
   * Возвращает массив всех категорий каталога.
   * <code>
   *  $res = MG::get('category')->getArrayCategory();
   *  viewData($res);
   * </code>
   * @return array
   */
  public function getArrayCategory() {
    return $this->categories;
  }

  /**
   * Получает описание категории.
   * <code>
   *  $res = MG::get('category')->getDesctiption(12);
   *  viewData($res);
   * </code>
   * @param int $id id категории
   * @return array
   */
  public function getDesctiption($id) {
    $result = null;
    $res = DB::query('
      SELECT `html_content`, `seo_content`
      FROM `'.PREFIX.'category`
      WHERE id = "%d"
    ', intval($id));

    if (!empty($res)) {
      if ($cat = DB::fetchArray($res)) {
        MG::loadLocaleData($id, LANG, 'category', $cat);
        $result = array('html_content' => $cat['html_content'],
          'seo_content' => $cat['seo_content']);
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает изображение категории.
   * <code>
   *  $res = MG::get('category')->getImageCategory(12);
   *  viewData($res);
   * </code>
   * @param int $id id категории
   * @return string
   */
  public function getImageCategory($id) {   
    return isset($this->categories[$id]['image_url'])?$this->categories[$id]['image_url']:'';
  }
  
  /** 
   *  Упорядочивает категорию по сортировке.
   *  @param array $a первая категория
   *  @param array $b вторая категория
   *  @return int
   */
  public static function sort($a, $b) {
    if (!is_array($a) || !isset($a['sort'])) {return false;}
    if (!is_array($b) || !isset($b['sort'])) {return true;}
    return $a['sort'] - $b['sort'];
  }
  
  /** 
   * Меняет местами параметры сортировки двух категории.
   * @param int $oneId - id первой категории.
   * @param int $twoId - id второй категории.
   * @return bool
   */
  public function changeSortCat($oneId, $twoId) {
    $cat1 = $this->getCategoryById($oneId); 
    $cat2 = $this->getCategoryById($twoId); 
    if(!empty($cat1)&&!empty($cat2)) {
      
     $res = DB::query('
       UPDATE `'.PREFIX.'category` 
       SET  `sort` = '.DB::quote($cat1['sort']).'  
       WHERE  `id` ='.DB::quote($cat2['id']).'
     ');
     
     $res = DB::query('
       UPDATE `'.PREFIX.'category` 
       SET  `sort` = '.DB::quote($cat2['sort']).'  
       WHERE  `id` ='.DB::quote($cat1['id']).'
     ');  
     //очищаем кэш категорий
      Storage::clear('category');
      return true;
    }
    return false;
  }
  
  
  
  /**
   * Отменяет скидки и наценки для выбранной категории.
   * <code>
   *  $res = MG::get('category')->clearCategoryRate(12);
   *  viewData($res);
   * </code>
   * @param int $id id категории
   * @return bool Делает все категории видимыми в меню.
   */
  public function  clearCategoryRate($id) {
     $res = DB::query('
       UPDATE `'.PREFIX.'category` 
       SET `rate` = 0  
       WHERE `id` = '.DB::quote($id)
     ); 
     Storage::clear('category');
     return true;
  }
  
   /**
   * Применяет скидку/наценку ко всем вложенным подкатегориям.
   * <code>
   *  $res = MG::get('category')->applyRateToSubCategory(12);
   *  viewData($res);
   * </code>
   * @param id - id текущей категории
   * @return bool 
   */
  public function  applyRateToSubCategory($id) {
    $childsCaterory = $this->getCategoryList($id);    
    // Если есть вложенные
    if (!empty($childsCaterory)) {
      $caterory = $this->getCategoryById($id);
      foreach ($childsCaterory as $cateroryId) {
        $res = DB::query('
          UPDATE `'.PREFIX.'category` 
          SET  `rate` = '.$caterory['rate'].'
          WHERE `id` = '.DB::quote($cateroryId)
        ); 
      }
    }
    Storage::clear('category');
    return true;
  }
  
   /**
   * Возвращает общее количество категорий каталога.
   * <code>
   *  $res = MG::get('category')->getCategoryCount();
   *  viewData($res);
   * </code>
   * @return int
   */
  public function getCategoryCount() {
    $result = 0;
    $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'category`
    ');

    if ($product = DB::fetchAssoc($res)) {
      $result = $product['count'];
    }

    return $result;
  }
  
  /**
   * Сортировка по алфавиту.
   * <code>
   *  MG::get('category')->sortToAlphabet();
   * </code>
   */
  public function sortToAlphabet() {
   $result = DB::query('SELECT id, title FROM `'.PREFIX.'category` ORDER BY title');
   $sort = 1;
   while ($row = DB::fetchAssoc($result)) {    
     DB::query('SELECT id, title FROM `'.PREFIX.'category` ORDER BY title');     
     $res = DB::query('
        UPDATE `'.PREFIX.'category` 
        SET  `sort` = '.DB::quote($sort).'
        WHERE `id` = '.DB::quote($row['id'])
     ); 
     $sort++;
   }
   Storage::clear('category');  
  }
  
  /**
   * Сортировка по порядку добавления категорий на сайт.
   * <code>
   *  MG::get('category')->sortToAdd();
   * </code>
   */
  public function sortToAdd() { 
     $res = DB::query('
        UPDATE `'.PREFIX.'category` 
        SET  `sort` = `id`'      
     );   
     Storage::clear('category');
   }     
  
  /**
   * Выгрузка категории в CSV.
   * <code>
   *  MG::get('category')->exportToCsv();
   * </code>
   */
  public function exportToCsv() {
    $categories = $this->getCategoryList();
    
    if(@set_time_limit(100)) {
      $maxExecTime = 90;
    } else {
      $maxExecTime = min(30, @ini_get("max_execution_time"));      
    }     
        
    $startTime = microtime(true);
    $timeMargin = 5;
    $rowCount = (URL::getQueryParametr('rowCount')) ? URL::getQueryParametr('rowCount') : 0;
    $csvText = '';
    
    if($rowCount == 0) {
      $csvText = array("Название категории","URL категории","ID родительской категории","URL родительской категории","Описание категории","Изображение", "Иконка для меню","Заголовок [SEO]","Ключевые слова [SEO]","Описание [SEO]","SEO Описание","Наценка","Не выводить в меню","Активность","Не выгружать в YML","Сортировка","Внешний идентификатор","ID категории","Title изображения","Alt изображения");
      $this->rowCsvPrintToFile($csvText, true);
    }
    
    foreach ($categories as $cell=>$catId) {
      if ($cell < $rowCount) {
        continue;
      }
      
      $category = $this->getCategoryById($catId);

      $row = array(
        'title' => $category['title'],
        'url' => $category['url'],
        'parent' => $category['parent'],
        'parent_url' => $category['parent_url'],
        'html_content' => str_replace(array("\r\n", "\r", "\n"), "", $category['html_content']),
        'image_url' => pathinfo($category['image_url'])['basename'],
        'menu_icon' => pathinfo($category['menu_icon'])['basename'],
        'meta_title' => $category['meta_title'],
        'meta_keywords' => $category['meta_keywords'],
        'meta_desc' => $category['meta_desc'],
        'seo_content' => str_replace(array("\r\n", "\r", "\n"), "", $category['seo_content']),
        'rate' => $category['rate'],
        'invisible' => $category['invisible'],
        'activity' => $category['activity'],
        'export' => $category['export'],
        'sort' => $category['sort'],
        '1c_id' => $category['1c_id'],
        'id' => $category['id'],
        'seo_title' => $category['seo_title'],
        'seo_alt' => $category['seo_alt'], 
      );
      
      $csvLine = $this->addToCsvLine($row);
      $this->rowCsvPrintToFile($csvLine, false);
      $rowCount++;
      
      $execTime = microtime(true) - $startTime;        

      if($execTime+$timeMargin >= $maxExecTime) {                  
        $data = array(
          'success' => false,          
          'rowCount' =>$rowCount,
          'percent' => round(($rowCount / count($categories)) * 100)
        );
        echo json_encode($data);
        exit();
      }
    }
    
    $date = date('m_d_Y');
    $pathDir = mg::createTempDir('data_csv',false);
    $data = array(
      'success' => true,
      'file' => $pathDir.'data_csv_'.$date.'.csv'
    );
    echo json_encode($data);
    exit();
  }
  
   /**
   * По входящим данным формирует новую строку CSV файла, в требуемом формате.
   * <code>
   *  $array = array(
   *    'title' => 'Смартфоны',                  // название категории
   *    'url' => 'smartfony',                    // url
   *    'parent' => 0,                           // id родительской категори
   *    'parent_url' => ,                        // родительский url
   *    'html_content' => ,                      // содеражние страницы
   *    'image_url' => '/uploads/cat_smart.png', // ссылка на изображение
   *    'meta_title' => ,                        // заголовок страницы
   *    'meta_keywords' => ,                     // ключевые слова
   *    'meta_desc' => ,                         // мета описание
   *    'seo_content' => ,                       // seo контент
   *    'rate' => 0,                             // наценка
   *    'invisible' => 0,                        // параметр видимости
   *    'activity' => 1,                         // параметр активности
   *    'export' => 1,                           // 
   *    'sort' => 1,                             // порядок сортировки
   *    '1c_id' => ,                             // идентификатор в 1с
   *    'id' => 1,                               // id
   *    'seo_title' => ,                         // seo title
   *    'seo_alt' => ,                           // seo alt
   *  );
   *  $res = MG::get('category')->addToCsvLine($array);
   *  viewData($res);
   * </code>
   * @param array $row массив со всеми данными о категории.
   * @return void
   */
  function addToCsvLine($row) {
    $row['title'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['title']));
    $row['url'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['url']));
    $row['parent_url'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['parent_url']));
    $row['html_content'] = str_replace("\"", "\"\"", $row['html_content']);
    $row['html_content'] = str_replace("\r", "", $row['html_content']);
    $row['html_content'] = str_replace("\n", "", $row['html_content']);
    $row['activity'] = str_replace("\"", "\"\"", $row['activity']);
    $row['meta_title'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['meta_title']));
    $row['meta_keywords'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['meta_keywords']));
    $row['meta_desc'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['meta_desc']));
    $row['meta_desc'] = str_replace("\r", "", $row['meta_desc']);
    $row['meta_desc'] = str_replace("\n", "", $row['meta_desc']);
    $row['sort'] = str_replace("\"", "\"\"", $row['sort']);
    $row['seo_title'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['seo_title']));
    $row['seo_alt'] = str_replace("\"", "\"\"", htmlspecialchars_decode($row['seo_alt']));
    
    $csvText = array(
      $row['title'],
      $row['url'],      
      $row['parent'],
      $row['parent_url'],
      $row['html_content'],
      $row['image_url'],
      $row['menu_icon'],
      $row['meta_title'],
      $row['meta_keywords'],
      $row['meta_desc'],
      $row['seo_content'],
      $row['rate'],
      $row['invisible'],
      $row['activity'],
      $row['export'],
      $row['sort'],
      $row['1c_id'],
      $row['id'],
      $row['seo_title'],
      $row['seo_alt'],
    );

    return $csvText;
  }
  
   /**
   * Записывает построчно CSV выгрузку в файл data_csv_m_d_Y.csv в директорию временных файлов сайта.
   * <code>
   *  $csvText = MG::get('category')->addToCsvLine($array);
   *  MG::get('category')->rowCsvPrintToFile($csvText);
   *  viewData($res);
   * </code>
   * @param string $csvText csv строка.
   * @param bool $new записывать в конец файла.
   * @return void
   */
  public function rowCsvPrintToFile($csvText, $new = false) {
    foreach ($csvText as &$item) {
      $item = mb_convert_encoding($item, "WINDOWS-1251", "UTF-8");
    }

    $date = date('m_d_Y');
    $pathDir = mg::createTempDir('data_csv');
    $filename = $pathDir.'data_csv_'.$date.'.csv';
    if($new) {      
      $fp = fopen($filename, 'w');
    } else {      
      $fp = fopen($filename, 'a');
    }

    fputcsv($fp, $csvText, ';');
    fclose($fp);
  }

  /**
   * Возвращает строки для таблицы с категориями в админке.
   * @param array $pagesArray массив с информацие о категориях
   * @param int $parentLevel уровень вложенности родительской страницы
   * @param int $parent id родительской характеристики
   * @return string html
   */
  public function getPages($pagesArray, $parentLevel, $parent) {
    $pages = '';
    foreach($pagesArray as $page) { 
      $pages .= self::getHtmlPageRow($pagesArray, $page['id'], $parentLevel);
    } 

    return $pages;
  }

  /**
   * возвращает строки для таблицы с категориями (упрощенный).
   * @param array $pagesArray массив с информацие о категориях
   * @param int $parentLevel уровень вложенности родительской страницы
   * @param int $parent id родительской характеристики
   * @return string html
   */
  public function getPagesSimple($pagesArray, $parentLevel, $parent) {
    $pages = '';
    foreach($pagesArray as $page) { 
      $pages .= self::getHtmlPageRowSimple($pagesArray, $page['id'], $parentLevel);
    } 

    return $pages;
  }

  /**
   * возвращает html верстку строк для таблицы с категориями (упрощенный).
   * @param array $pages массив с информацие о категориях
   * @param int $id id категории
   * @param int $level уровень вложенности
   * @return string html
   */
  public function getHtmlPageRowSimple($pages, $id, $level) {
    $categoryCount = isset($_SESSION['categoryCountToAdmin'])?$_SESSION['categoryCountToAdmin']:null;
    foreach($pages as $page) { 
      if($page['id'] == $id) {
        // группировка для сортировки
        if($level == 0) {
          $group = 'main';
        } else {
          $group = 'group-'.$page['parent'];
        }
        // отображать ли кнопку для выпадающего списка
	      $result = '';
        $res = DB::query('SELECT id FROM '.PREFIX.'category WHERE parent = '.DB::quote($page['id']).' GROUP BY sort LIMIT 1');
        if($row = DB::fetchAssoc($res)) {
          $result = $row['id'];
        }
        if($result != "") {
          $circlePlus = '<button class="show_sub_menu tooltip--small" id="toHide-'.$page['id'].'" data-id="'.$page['id'].'" aria-label="Показать/скрыть вложенные категории" tooltip="Показать/скрыть вложенные категории" flow="right"><i aria-hidden="true" class="fa fa-plus-circle"></i></button>';
        } else {
          $circlePlus = '';
        }
        // отображать ли иконку вложенности
        $levelArrow = '';
        for($i = 0; $i < $level; $i++) {
          $levelArrow .= '<i class="fa fa-long-arrow-right" aria-hidden="true"></i>';
        }

        return '
              <tr class="level-'.($level+1).' '.$group.'" data-group="'.$group.'" data-level="'.($level+1).'" data-id="'.$page['id'].'" data-sort="'.$categoryCount.'">
        
                <td class="name">'.$circlePlus.$levelArrow.'<span class="product-name"><a class="name-link tip edit-sub-cat" href="javascript:void(0);">'.$page['title'].'</a><a class="fa fa-external-link tip" href="'.SITE.'/'.$page['parent_url'].$page['url'].'" aria-hidden="true" title="Открыть категорию на сайте" target="_blank"></a></span></td>

                <td class="uploadCat">
                  <div class="sticker-menu discount-rate-down badge alert">
                    <span class="upload-cat-text" title="Изменить привязку к категории выгрузки" upload-cat-name="0" data-cat-id="'.$page['id'].'" upload-cat-name="">Привязать категорию</span>
                    <div class="discount-mini-control">
                      <span class="cat-apply-follow tool-tip-bottom"  title="Применить ко всем вложенным категориям">&darr;&darr;</span> 
                      <span class="cat-cansel tool-tip-bottom" title="Убрать привязку к категории выгрузки">x</span>
                    </div>
                  </div>
                </td>
              </tr>
              ';
      }
    }
  }

  /**
   * возвращает html верстку строк для таблицы с категориями.
   * @param array $pages массив с информацие о категориях
   * @param int $id id категории
   * @param int $level уровень вложенности
   * @return string html
   */
  public function getHtmlPageRow($pages, $id, $level) {
    $lang = MG::get('lang');
    if (!empty($_SESSION['categoryCountToAdmin'])) {
      $categoryCount = $_SESSION['categoryCountToAdmin'];
    } else {
      $categoryCount = 0;
    }

    foreach($pages as $page) { 
      if($page['id'] == $id) {
        // группировка для сортировки
        if($level == 0) {
          $group = 'main';
        } else {
          $group = 'group-'.$page['parent'];
        }
        $result = '';
        // отображать ли кнопку для выпадающего списка
        $res = DB::query('SELECT id FROM '.PREFIX.'category WHERE parent = '.DB::quote($page['id']).' ORDER BY sort ASC LIMIT 1');
        while($row = DB::fetchAssoc($res)) {
          $result = $row['id'];
        }
        if($result != "") {
          $circlePlus = '<button class="show_sub_menu tooltip--small" id="toHide-'.$page['id'].'" data-id="'.$page['id'].'" aria-label="Показать/скрыть вложенные категории" tooltip="Показать/скрыть вложенные категории" flow="right"><i aria-hidden="true" class="fa fa-plus-circle"></i></button>';
        } else {
          $circlePlus = '';
        }
        // отображать ли иконку вложенности
        $levelArrow = '';
        for($i = 0; $i < $level; $i++) {
          $levelArrow .= '<i class="fa fa-long-arrow-right" aria-hidden="true"></i>';
        }
        // отмечен ли чекбокс показа
        if($page['activity'] == '1') {
          $checkbox = 'active';
        } else {
          $checkbox = '';
        } 
        // отмечен ли чекбокс показа
        if($page['invisible'] == '0') {
          $invisible = 'active';
        } else {
          $invisible = '';
        } 
        // отмечен ли чекбокс export
        if($page['export'] == '1') {
          $export = 'active';
        } else {
          $export = '';
        }

        $rate = '';
        if ($page['rate']>0) {
          $rate = '<div class="sticker-menu badge-discount-category discount-rate-up badge success" data-cat-id="'.$page['id'].'"> '.$lang['DISCOUNT_UP'].' +'.($page['rate']*100).'% <div class="discount-mini-control"><span class="discount-apply-follow tool-tip-bottom"  title="Применить ко всем вложенным категориям" >
          <svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" ><polyline  points="4 17 7 20 10 17" style="fill: none; stroke:currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></polyline><path id="primary-2" data-name="primary" d="M7,4V20M17,4V20" style="fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></path><polyline id="primary-3" data-name="primary" points="14 17 17 20 20 17" style="fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></polyline></svg></span> <span class="discount-cansel tool-tip-bottom" title="Отменить"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20.7457 3.32851C20.3552 2.93798 19.722 2.93798 19.3315 3.32851L12.0371 10.6229L4.74275 3.32851C4.35223 2.93798 3.71906 2.93798 3.32854 3.32851C2.93801 3.71903 2.93801 4.3522 3.32854 4.74272L10.6229 12.0371L3.32856 19.3314C2.93803 19.722 2.93803 20.3551 3.32856 20.7457C3.71908 21.1362 4.35225 21.1362 4.74277 20.7457L12.0371 13.4513L19.3315 20.7457C19.722 21.1362 20.3552 21.1362 20.7457 20.7457C21.1362 20.3551 21.1362 19.722 20.7457 19.3315L13.4513 12.0371L20.7457 4.74272C21.1362 4.3522 21.1362 3.71903 20.7457 3.32851Z" fill="currentColor"/>
          </svg></span></div></div>';
        }
        if ($page['rate']<0) {
          $rate = '<div class="sticker-menu badge-discount-category discount-rate-down badge alert" data-cat-id="'.$page['id'].'"> '.$lang['DISCOUNT_DOWN'].' '.($page['rate']*100).'% <div class="discount-mini-control"><span class="discount-apply-follow tool-tip-bottom"  title="Применить ко всем вложенным категориям">
          <svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" ><polyline  points="4 17 7 20 10 17" style="fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></polyline><path id="primary-2" data-name="primary" d="M7,4V20M17,4V20" style="fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></path><polyline id="primary-3" data-name="primary" points="14 17 17 20 20 17" style="fill: none; stroke:currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></polyline></svg></span> <span class="discount-cansel tool-tip-bottom" title="Отменить">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20.7457 3.32851C20.3552 2.93798 19.722 2.93798 19.3315 3.32851L12.0371 10.6229L4.74275 3.32851C4.35223 2.93798 3.71906 2.93798 3.32854 3.32851C2.93801 3.71903 2.93801 4.3522 3.32854 4.74272L10.6229 12.0371L3.32856 19.3314C2.93803 19.722 2.93803 20.3551 3.32856 20.7457C3.71908 21.1362 4.35225 21.1362 4.74277 20.7457L12.0371 13.4513L19.3315 20.7457C19.722 21.1362 20.3552 21.1362 20.7457 20.7457C21.1362 20.3551 21.1362 19.722 20.7457 19.3315L13.4513 12.0371L20.7457 4.74272C21.1362 4.3522 21.1362 3.71903 20.7457 3.32851Z" fill="currentColor"/>
          </svg></span></div></div>';
        }

        if(USER::access('category') > 1) {
          $notActiveMessage = $lang['ACT_UNV_CAT_ACT'];
          $notActiveMessage .= ' '.$this->lang['ACT_UNV_CAT_ACT2'];
          
          $actions = '<li><a class="add-sub-cat tooltip--small" href="javascript:void(0);" aria-label="Добавить вложенную категорию" tooltip="Добавить вложенную категорию" flow="left"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></li>
                    <li><a class="'.$checkbox.' activity" href="javascript:void(0);" aria-label="'.($checkbox == "" ? $notActiveMessage : $lang["ACT_V_CAT_ACT"]).'" tooltip="'.($checkbox == "" ? $notActiveMessage : $lang["ACT_V_CAT_ACT"]).'" flow="left" ><i class="fa fa-lightbulb-o" aria-hidden="true"></i></a></li>
                    <li><a class="visible '.$invisible.' tooltip--small" href="javascript:void(0);" aria-label="'.($invisible == "" ? $lang["ACT_UNV_CAT"] : $lang["ACT_V_CAT"]).'" tooltip="'.($invisible == "" ? $lang["ACT_UNV_CAT"] : $lang["ACT_V_CAT"]).'" flow="left"><i class="fa fa-list" aria-hidden="true"></i></a></li>';
          $actions2 = '<li><a class="delete-sub-cat tooltip--xs tooltip--center" href="javascript:void(0);" aria-label="'.$lang['DELETE'].'" tooltip="'.$lang['DELETE'].'" flow="left"><i class="fa fa-trash" aria-hidden="true"></i></a></li>';
        } else {
          $actions = '';
          $actions2 = '';
        }

        return '
              <tr class="level-'.($level+1).' '.$group.'" data-group="'.$group.'" data-level="'.($level+1).'" data-id="'.$page['id'].'" data-sort="'.$categoryCount.'">
                <td class="checkbox">
                  <div class="checkbox">
                    <input type="checkbox" id="c'.$page['id'].'" name="category-check">
                    <label class="select-row shiftSelect" for="c'.$page['id'].'"></label>
                  </div>
                </td>
                <td class="sort">
                    <a class="mover"
                       tooltip="Нажмите и перетащите категорию для изменения порядка сортировки в каталоге"
                       flow="right"
                       href="javascript:void(0);"
                       aria-label="Сортировать"
                       role="button">
                       <i class="fa fa-arrows" aria-hidden="true"></i>
                    </a>
                </td>
                <td class="number">'.$page['id'].'</td>
                <td class="name">'.$circlePlus.$levelArrow.'<span class="product-name"><a class="name-link edit-sub-cat link tooltip--center " aria-label="Редактировать категорию" tooltip="Редактировать категорию" flow="up" href="javascript:void(0);"><span>'.$page['title'].'</span></a><a class="tooltip--center" href="'.SITE.'/'.$page['parent_url'].$page['url'].'" aria-label="Открыть категорию на сайте" tooltip="Открыть категорию на сайте" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a></span></td>
                <td>'.$rate.'</td>
                <td><a class="tip"  href="'.SITE.'/'.$page['parent_url'].$page['url'].'" aria-label="Перейти на страницу категории" tooltip="Перейти на страницу категории" flow="up"  target="blank">/'.$page['parent_url'].$page['url'].'</a></td>
                <td class="text-right actions">
                  <ul class="action-list">
                    <li><a class="tip edit-sub-cat tooltip--small" aria-label="Редактировать категорию" tooltip="Редактировать категорию" flow="left"  href="javascript:void(0);" tabindex="0"><i class="fa fa-pencil" aria-hidden="true"></i></a></li>
                    '.$actions.'
                    <li><a class="prod-sub-cat" href="javascript:void(0);" aria-label="Посмотреть товары категории" tooltip="Посмотреть товары категории" flow="left"><i class="fa fa-search" aria-hidden="true"></i></a></li>
                    '.$actions2.'
                  </ul>
                </td>
              </tr>
              ';
      }
    }
  }
}