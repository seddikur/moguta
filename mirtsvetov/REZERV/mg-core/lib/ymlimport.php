<?php

/*
  Plugin Name: Импорт каталога из YML
*/

class ymlImport{
  // DEBUG HELPERS
  //
  // Если интересно узнать потребление памяти или скорость импорта скрипта
  // Запусти в терминале в devTools:
  // localStorage.setItem('debugYmlImport', 'short');
  // Для отключения:
  // localStorage.removeItem('debugYmlImport');
  // Выводит информацию в textarea вместе с сообщениями о прогрессе импорта
  //
  // Аналогично можно ограничить количество импортируемых товаров (и соответственно изображений)
  // Пример для ограничения в 100 товаров
  // Запусти в терминале в devTools:
  // localStorage.setItem('debugYmlMaxProducts', 100);
  // Для отключения:
  // localStorage.removeItem('debugYmlMaxProducts');
  static private $_instance = null;
  public static $lang = array(); // массив с переводом плагина
  private static $maxExecTime = null;

  public function __construct() {
    self::$lang =  MG::get('lang');
    self::$maxExecTime = min(25, @ini_get("max_execution_time"));
    if(empty(self::$maxExecTime)) {
      self::$maxExecTime = 25;
    }

  }

   /**
   * Возвращает единственный экземпляр данного класса.
   */
  static public function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self;
    }
    return self::$_instance;
  }

  /**
   * Иннициализирует объект данного класса User.
   * @access private
   * @return void
   */
  public static function init() {
    self::getInstance();
  }


  /*
   * Получение XML объекта из файла, по указанному пути
   */
  private static function GetXMLObject($filePath)  {
    USER::AccessOnly('1,4','exit()');

    $file_content = file_get_contents($filePath);

    libxml_use_internal_errors(true);

    $xml = simplexml_load_string($file_content, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);  // пытаемся получить объект

    if (!is_object($xml->shop)) {  //если объект не создался, меняем кодировку и пробуем еще раз
      $file_content = iconv("Windows-1251", "UTF-8", $file_content);
      $xml = simplexml_load_string($file_content, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
    }

    return $xml;
  }

  /**
   * Получает параметры категори по его XmlId.
   *
   * @param string $xmlId запрашиваемой  категории.
   * @return array массив с данными о категории.
   *
   */
  private static function getCategoryByXmlId($xmlId) {
    $result = 0;
    $res = DB::query('
      SELECT id
      FROM `'.PREFIX.'category`
      WHERE 1c_id = '.DB::quote($xmlId));

    if(!empty($res)) {
      if($cat = DB::fetchAssoc($res)) {
        $result = $cat['id'];
      }
    }

    return $result;
  }

  /*
   * Получаем товар по xmlId
   */
  private static function getProductByXmlId($xmlId) {
    $result = 0;
    $res = DB::query('
      SELECT id
      FROM `'.PREFIX.'product`
      WHERE 1c_id = '.DB::quote($xmlId));

    if(!empty($res)) {
      if($product = DB::fetchAssoc($res)) {
        $result = $product['id'];
      }
    }

    return $result;
  }

  /*
   * Получаем свойства продукта по его ID
   */
  private static function getProductProperties($id) {
    $result = array();

      $dbRes = DB::query('
        SELECT name, value
        FROM `'.PREFIX.'property` as p
          LEFT JOIN `'.PREFIX.'product_user_property` as pup
            ON p.id = pup.property_id
        WHERE pup.product_id = '.DB::quote($id)
      );

      while($res = DB::fetchAssoc($dbRes)) {
        $result[$_SESSION['yml_import']['fileId'].URL::prepareUrl(MG::translitIt($res['name']), true)] = $res['value'];
      }

    return $result;
  }

  /**
   * Создает свойства продукта
   * @param type $key = xmlId характеристики
   * @param type $name = Название характеристики
   * @param type $value = Значание
   * @param type $categoryId = Категория
   * @param type $productId = Продукт
   * @return type
   */
  private static function createProperty($key, $name, $value, $categoryId, $productId) {
    if(empty($key)) {
      return false;
    }

    $propertyId = '';
    // 1. Проверяем, существует такая характеристика у данной категории?
    $res = DB::query('
      SELECT * 
      FROM `'.PREFIX.'property` as `p`
      LEFT JOIN `'.PREFIX.'category_user_property` as `cup`
        ON `cup`.`property_id`=`p`.`id` 
      WHERE `1c_id` = '.DB::quote($key)
    );

    $row = DB::fetchAssoc($res);
    if(empty($row)) {

      // если нет характеристики до создадим ее
      DB::query('
        INSERT INTO `'.PREFIX.'property`
          (`name`, `type`, `activity`, `1c_id`)
        VALUES ('.DB::quote($name).', "string", 1, '.DB::quote($key).')'
      );
      $propertyId = DB::insertId();
      // установка  сортировки
      DB::query('
        UPDATE `'.PREFIX.'property`
        SET `sort` = '.DB::quote($propertyId).'
        WHERE `id` = '.DB::quote($propertyId)
      );
    }else{

      // если найдена уже характеристика, получаем ее id
      $propertyId = $row['id'];

      // добавляем привязку, если ее небыло раньше, для действующей категории
      $res = DB::query('
       SELECT * 
       FROM `'.PREFIX.'category_user_property` 
       WHERE `property_id` = '.DB::quote($propertyId).' 
         AND `category_id` = '.DB::quote($categoryId)
      );
      $rowCup = DB::fetchAssoc($res);
      if(empty($rowCup)) {
       DB::query('
         INSERT INTO `'.PREFIX.'category_user_property`
          (`category_id`, `property_id`)
         VALUES ('.DB::quote($categoryId).','.DB::quote($propertyId).')'
       );
      }

    }


    // 2. Привязываем к продукту
      $res = DB::query('
       SELECT * 
       FROM `'.PREFIX.'product_user_property` 
       WHERE `property_id` = '.DB::quote($propertyId).'
         AND `product_id` = '.DB::quote($productId)
      );
      $row = DB::fetchAssoc($res);
      if(empty($row)) {
        DB::query('
          INSERT INTO `'.PREFIX.'product_user_property`
           (`product_id`, `property_id`, `value`)
          VALUES ('.DB::quote($productId).','.DB::quote($propertyId).','.DB::quote($value).')'
        );
      }else{
        DB::query('
          UPDATE `'.PREFIX.'product_user_property`
          SET `value` = '.DB::quote($value).'
          WHERE `product_id` = '.DB::quote($productId).'
            AND `property_id` = '.DB::quote($propertyId)
        );
      }

    // 3. Привязываем к категории
    $res = DB::query('
      SELECT * 
      FROM `'.PREFIX.'category_user_property` 
      WHERE `property_id` = '.DB::quote($propertyId)
    );
    $row = DB::fetchAssoc($res);
    if(empty($row)) {
      // если нет характеристики до создадим ее
      DB::query('
      INSERT INTO `'.PREFIX.'category_user_property`
        (`category_id`, `property_id`)
      VALUES ('.DB::quote($categoryId).','.DB::quote($propertyId).')'
      );
    }
  }

  /*
   * Загружаем изображение
   */
  private static function getPicture($url, $name, $path) {
    if(!file_exists($path)) {
      mkdir($path, 0755);
    }

    $ch = curl_init($url);
    $fp = fopen($path.'/'.$name, 'wb');
    if (is_dir(SITE_DIR.'uploads'.DS.'temp')) {
      $errFile = fopen(SITE_DIR.'uploads'.DS.'temp'.DS.'ymlImportErr.txt', 'wb');
      curl_setopt($ch, CURLOPT_STDERR, $errFile);
    }
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    if(!curl_exec($ch)) {
      MG::loger('Ошибка curl: '.curl_errno($ch).': '.curl_error($ch));
      MG::loger('Error URL: '.$url);
    }

    curl_close($ch);
    if (is_resource($fp)) {
      fclose($fp);
    }
    if (is_resource($errFile)) {
      fclose($errFile);
    }

    $filePath = $path.DS.$name;
    
    list($width_uploaded, $height_uploaded) = getimagesize($filePath);
    $maxUploadWidth = MG::getSetting('maxUploadImgWidth');
    if (!$maxUploadWidth) {$maxUploadWidth = 1500;}
    $maxUploadHeight = MG::getSetting('maxUploadImgHeight');
    if (!$maxUploadHeight) {$maxUploadHeight = 1500;}

    if(EDITION=='saas'){
      $maxUploadWidth = 15000;
      $maxUploadHeight = 15000;
    }

    if ($width_uploaded > $maxUploadWidth || $height_uploaded > $maxUploadHeight) {
      unlink($filePath);
      return ['error' => 'HUGE_IMAGE_ERROR'];
    }

    return ['name' => $name];
  }

  /*
   * Загружаем файл
   */
  public static function getFileByUrl($url) {
    @copy($url, SITE_DIR.'uploads'.DS.'yml.xml');
    return true;
  }

  /**
   * Сохраняет в сессию уникальный для выгружаемоего магазина тектовый идентификатор
   */
  public static function setFileId($file, $forceName = null, $forceCompany = null) {
    if ($forceName && $forceCompany) {
      $fileIdArray = [
        'fileFormat' => 'yml',
        'name' => $forceName,
        'company' => $forceCompany,
      ];
      
      $fileId = implode('_', $fileIdArray);
      $_SESSION['yml_import']['fileId'] = MG::translitIt($fileId).'_';
      return true;
    }
    $xmlReader = new XMLReader;
    $xmlReader->open($file);

    $fileIdArray = [
      'fileFormat' => 'yml',
      'name' => '',
      'company' => '',
    ];

    while ($xmlReader->read() && (empty($fileIdArray['name']) || empty($fileIdArray['company']))) {
      $nodeName = $xmlReader->name;
      if (($nodeName === 'name' || $nodeName === 'company') && $fileIdArray[$nodeName] === '') {
        $fileIdArray[$nodeName] = $xmlReader->readString();
      }
    }

    if (empty($fileIdArray['name']) || empty($fileIdArray['company'])) {
      return ['error' => self::$lang['ERROR_SHOP_INFO']];
    }

    $fileId = implode('_', $fileIdArray);

    $_SESSION['yml_import']['fileId'] = MG::translitIt($fileId).'_';
    return true;
  }

  public static function parseCatsToDb($file, $skipRootCat) {
    $startTime = microtime(true);
    $importCatsTableName = '`'.PREFIX.'import_yml_core_cats`';
    $catXmlNodeName = 'category';
    $maxCatsInOneInsertSql = 100;
    $insertCatsSqlStart = 'INSERT INTO '.$importCatsTableName.' '.
      'VALUES ';

    $xmlReader = new XMLReader;
    $xmlReader->open($file);
    while ($xmlReader->read() && $xmlReader->name !== $catXmlNodeName);
    $insertValues = [];
    $completed = !empty($_POST['completed']) ? $_POST['completed'] : 0;
    if ($completed === 0) {
      $truncateSql = 'TRUNCATE '.$importCatsTableName.';';
      DB::query($truncateSql);
    }
    $count = 0;
    while ($xmlReader->name === $catXmlNodeName) {
      if ($count < $completed) {
        $count += 1;
        $xmlReader->next($catXmlNodeName);
        continue;
      }
      $insertValue = [
        intval($xmlReader->getAttribute('id')),
        $xmlReader->getAttribute('parentId'),
        DB::quote($xmlReader->readString())
      ];
      if ($insertValue[1] === null) {
        $insertValue[1] = 'NULL';
      } else {
        $insertValue[1] = intval($insertValue[1]);
      }
      $insertValues[] = '('.implode(', ', $insertValue).')';
      $count += 1;
      if (count($insertValues) === $maxCatsInOneInsertSql) {
        $insertCatsSql = $insertCatsSqlStart . implode(', ', $insertValues).';';
        DB::query($insertCatsSql);
        $insertValues = [];
        if ((microtime(true) - $startTime) > self::$maxExecTime) {
          $xmlReader->close();
          $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
          return ['step' => 0, 'completed' => $count, 'message' => self::$lang['READ_CATS_FROM_FILE'].'. '.self::$lang['FOUND'].' '.$count.$debugInfo];
        }
      }
      $xmlReader->next($catXmlNodeName);
    }
    if (count($insertValues)) {
      $insertCatsSql = $insertCatsSqlStart . implode(', ', $insertValues).';';
      DB::query($insertCatsSql);
      $insertValues = [];
    }
    $xmlReader->close();

    if ($skipRootCat) {
      $rootCatsCountSql = 'SELECT COUNT(`id`) as cats_count, `id` '.
        'FROM `'.PREFIX.'import_yml_core_cats` '.
        'WHERE `parentId` IS NULL OR `parentId` = ""';
      $rootCatsCountResult = DB::query($rootCatsCountSql);
      if ($rootCatsCountRow = DB::fetchAssoc($rootCatsCountResult)) {
        if (intval($rootCatsCountRow['cats_count']) === 1) {
          $rootCatId = $rootCatsCountRow['id'];
          
          $deleteRootCatSql = 'DELETE FROM `'.PREFIX.'import_yml_core_cats` '.
            'WHERE `id` = '.DB::quoteInt($rootCatId);
          DB::query($deleteRootCatSql);

          $unsetRootCatSql = 'UPDATE `'.PREFIX.'import_yml_core_cats` '.
            'SET `parentId` = NULL '.
            'WHERE `parentId` = '.DB::quoteInt($rootCatId);
          DB::query($unsetRootCatSql);
        }
      }
    }

    $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
    return ['step' => 1, 'completed' => 0, 'message' => self::$lang['READ_CATS_FROM_FILE_DONE'].'. '.self::$lang['FOUND'].' '.$count.$debugInfo."\n".self::$lang['START_IMPORT_CATS']];
  }

  public static function importCats() {
    $startTime = microtime(true);
    $count = 0;

    if (isset($_POST['total'])) {
      $totalCats = $_POST['total'];
    } else {
      $totalCatsSql = 'SELECT COUNT(`id`) as totalCats FROM `'.PREFIX.'import_yml_core_cats`;';
      $totalCatsQuery = DB::query($totalCatsSql);
      $totalCatsResult = DB::fetchAssoc($totalCatsQuery);
      $totalCats = intval($totalCatsResult['totalCats']);
    }

    $completed = isset($_POST['completed']) && $_POST['completed'] ?
    $_POST['completed'] : 0;

    $getCatsSql = 'SELECT * FROM `'.PREFIX.'import_yml_core_cats` '.
      'ORDER BY `parentId` LIMIT 1000000 OFFSET '.$completed.';';
    $getCatsQuery = DB::query($getCatsSql);
    while ($getCatsResult = DB::fetchAssoc($getCatsQuery)) {
      self::insertOrUpdateCat($getCatsResult);
      $completed += 1;
      $count += 1;
      if ((microtime(true) - $startTime) > self::$maxExecTime) {
        $percent = round(($completed * 100) / $totalCats);
        $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count, microtime(true) - $startTime);
        return ['step' => 1, 'completed' => $completed, 'total' => $totalCats, 'message' => self::$lang['SUCCESS_STEP_CATEGORI'].$percent.'%'.$debugInfo];
      }
    }

    $importCatsTableName = '`'.PREFIX.'import_yml_core_cats`';
    $truncateSql = 'TRUNCATE '.$importCatsTableName.';';
    DB::query($truncateSql);
    $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count, microtime(true) - $startTime);

    return ['step' => 2, 'completed' => 0, 'message' => self::$lang['IMPORT_CATEGORY_SUCCESS'].'. '.self::$lang['SUCCESS_STEP'].' '.$completed.$debugInfo."\n".self::$lang['START_INDEX_CATS']];
  }

  public static function indexCatsAndClearCache() {
    $startTime = microtime(true);

    $getCategoriesCountSql = 'SELECT COUNT(`id`) as catsCount FROM `'.PREFIX.'category`;';
    $getCategoriesCountQuery = DB::query($getCategoriesCountSql);
    $getCategoriesCountResult = DB::fetchAssoc($getCategoriesCountQuery);
    $catsCount = $getCategoriesCountResult['catsCount'];

    $categoryLib = MG::get('category');
    $categoryLib->startIndexation();
    
    Storage::clear('category');
    Storage::clear('categoryInit-'.LANG);

    $debugInfo = self::getMemoryUsage().self::getSpeedInfo($catsCount, microtime(true) - $startTime);
    return ['step' => 3, 'completed' => 0, 'message' => self::$lang['INDEX_CATS_DONE'].$debugInfo."\n".self::$lang['START_IMPORT_PRODUCT']];
  }

  public static function insertOrUpdateCat($xmlCat) {
    $categoryLib = MG::get('category');

    $categoryChecks = [
      'resizeImages' => false,
      'moveCKImages' => false,
      'lang' => false,
      'indexation' => false,
      'clearCache' => false
    ];

    $parentId = null;
    if ($xmlCat['parentId'] !== null) {
      $parentRawId = $xmlCat['parentId'];
      $parentXmlId = $_SESSION['yml_import']['fileId'].$parentRawId;
      $parentId = self::getCategoryByXmlId($parentXmlId);
      if (!$parentId) {
        $parentXmlSql = 'SELECT * FROM `'.PREFIX.'import_yml_core_cats` '.
          'WHERE `id` = '.$parentRawId.';';
        $parentXmlQuery = DB::query($parentXmlSql);
        $parentXmlResult = DB::fetchAssoc($parentXmlQuery);
        $parentId = self::insertOrUpdateCat($parentXmlResult);
      }
    } else {
      if (!empty($_POST['data']['import_in_category'])) {
        $parentId = $_POST['data']['import_in_category'];
      }
    }
    $xmlId = $_SESSION['yml_import']['fileId'].$xmlCat['id'];
    $findCategory = self::getCategoryByXmlId($xmlId);
    if ($findCategory) {
      $updateCategoryArray = [
        'id' => $findCategory,
        'title' => $xmlCat['title'],
        'url' => MG::translitIt($xmlCat['title']),
        'parent' => $parentId ? $parentId : 0,
        'parent_url' => $parentId !== null ? self::getCatUrlById($parentId).'/' : ''
      ];

      $categoryLib->updateCategory($updateCategoryArray, $categoryChecks);

      return $findCategory;
    }

    $categoryChecks['childrenParents'] = false;

    $addCategoryArray = [
      'title' => $xmlCat['title'],
      'url' => MG::translitIt($xmlCat['title']),
      'parent' => $parentId ? $parentId : 0,
      'parent_url' => $parentId !== null ? self::getCatUrlById($parentId).'/' : '',
      '1c_id' => $xmlId
    ];

    $result = $categoryLib->addCategory($addCategoryArray, $categoryChecks);
    return $result['id'];
  }

  public static function getCatUrlById($catId) {
    $getCatUrlSql = 'SELECT `url` FROM `'.PREFIX.'category` '.
      'WHERE `id` = '.$catId.';';
    $getCatUrlQuery = DB::query($getCatUrlSql);
    if ($getCatUrlResult = DB::fetchAssoc($getCatUrlQuery)) {
      return $getCatUrlResult['url'];
    }
    return null;
  }

  public static function parseProductsToDb($file, $priceModifier, $onlyPrice, $importCategory, $importInCategory, $newFileStructure, $onlyNewProductsImages = false, $importInWarehouse = -1, $doNotUpdateExistingProductsCats = false, $changeRest = '') {
    $startTime = microtime(true);
    $productXmlNodeName = 'offer';

    $max = false;
    if (isset($_POST['data']['maxProductsImport']) && $_POST['data']['maxProductsImport']) {
      $max = $_POST['data']['maxProductsImport'];
    }

    $xmlReader = new XMLReader;
    $xmlReader->open($file);
    while ($xmlReader->read() && $xmlReader->name !== $productXmlNodeName);
    $completed = !empty($_POST['completed']) ? $_POST['completed'] : 0;
    $count = 0;

    if (!$completed) {
      $truncateTempImagesSql = 'TRUNCATE `'.PREFIX.'import_yml_core_images`;';
      DB::query($truncateTempImagesSql);
    }

    while ($xmlReader->name === $productXmlNodeName) {
      if ($count < $completed) {
        $xmlReader->next($productXmlNodeName);
        $count += 1;
        continue;
      }



      $productXml = simplexml_load_string($xmlReader->readOuterXml());
      $productXmlId = $_SESSION['yml_import']['fileId'].$productXml['id'];
      $productCategoryXmlId = $productXml->categoryId !== '' ? $_SESSION['yml_import']['fileId'].$productXml->categoryId : null;

      $findProduct = self::getProductByXmlId($productXmlId);

      $productPrice = $productXml->price;
      $oldPrice = $productXml->oldprice;

      if (doubleval($priceModifier) !== 1.00) {
        $productPrice = $productPrice * doubleval($priceModifier);
        $oldPrice = $oldPrice * doubleval($priceModifier);
      }

      //Необходима какая-нибудь проверка, чтобы определить каким образом задана валюта
      $currencyId = ($productXml->currencyId == "RUB") ? "RUR" : strval($productXml->currencyId);

      $productModel = new Models_Product;

      if (!$onlyPrice) {
          $images = [];
        if (!$findProduct || !$onlyNewProductsImages) {
          foreach ($productXml->picture as $picture) {
            $images[] = (string) $picture;
          }
        }

        $article = '';
        $weight = '';
        $multiplicity = '';

        $arFindProductProperty = [];

        if($findProduct) {
          $arFindProductProperty = self::getProductProperties($findProduct);
        }

        $arProductProperty = [];

        foreach($productXml->param as $param) {
          $atr = array();
          $atr = $param[0]->attributes();
          $value = (string)$param;
          $propertyXmlId = $_SESSION['yml_import']['fileId'].URL::prepareUrl(MG::translitIt($atr['name']), true);

          if(empty($value) && empty($arFindProductProperty[$propertyXmlId])) {
            continue;
          }

          switch($atr['name']) {
            case 'Артикул':
              $article = $value;
              break;
            case 'Вес':
              $weight = $value;
              break;
            default:
              $arProductProperty[] = array(
                'name' => (string)$atr['name'],
                'xmlId' => $propertyXmlId,
                'value' => (string)$param[0],
              );
          }
        }

        $productType = $productXml['type'];
        $multiplicityPropName = 'min-quantity';

        switch($productType) {
          case "vendor.model":
            $productName = $productXml->vendor.' '.$productXml->model;
            $weight = $productXml->weight;
            $article = $productXml->vendorCode;
            $multiplicity = $productXml->$multiplicityPropName;
            break;
          case "book":
          case "audiobook":
            $productName = $productXml->author.' '.$productXml->name;
            $weight = $productXml->weight;
            $article = $productXml->vendorCode;
            $multiplicity = $productXml->$multiplicityPropName;
            break;
          case "artist.title":
            $productName = $productXml->artist.' '.$productXml->title;
            $weight = $productXml->weight;
            $article = $productXml->vendorCode;
            $multiplicity = $productXml->$multiplicityPropName;
            break;
          default:
            $productName = $productXml->name;
            if (!$weight && $productXml->weight) {
              $weight = $productXml->weight;
            }
            if (!$article && $productXml->vendorCode) {
              $article = $productXml->vendorCode;
            }
            $multiplicityPropName = 'min-quantity';
            $multiplicity = $productXml->$multiplicityPropName;
        }
      }

      if($onlyPrice && $findProduct) {
        $arUpdatePriceFields = array(
          'id' => $findProduct,
          'price' => $productPrice,
          'currency_iso' => $currencyId,
        );
        $productInfo['id'] = $findProduct;
        $productModel->updateProduct($arUpdatePriceFields);
      } elseif (!$onlyPrice) {
        if ($importCategory == false && $importInCategory != 0) {
          $cat_idi = $importInCategory;
        }
        else{
          $cat_idi = self::getCatIdByXmlId($productCategoryXmlId);
        }

        $productCount = 0;
        if (!empty($productXml['available']) && $productXml['available'] === 'true') {
          $productCount = -1;
        }
        if (!empty($productXml->count)) {
          $productCount = round(floatval($productXml->count), 2);
        }

        if ($changeRest >= -1 && $changeRest !== '') {
          $productCount = $changeRest;
        }

        if($findProduct) {
          $arUpdateProductFields = array(
            'id' => $findProduct,
            'title' => strval($productName),
            'description' => trim(str_replace(array('<![CDATA[', ']]>'), '', $productXml->description)),
            'price' => floatval($productPrice),
            'count' => intval($productCount),
            'currency_iso' => $currencyId,
            'cat_id' => $cat_idi,
            'multiplicity' => !empty($multiplicity) ? $multiplicity : 1,
          );

          if ($doNotUpdateExistingProductsCats) {
            unset($arUpdateProductFields['cat_id']);
          }

          if (!MG::enabledStorage()) {
            $arUpdatePriceFields['count'] = $productCount;
          }

          if (!$onlyNewProductsImages) {
            unset($arUpdateProductFields['image_url']);
          }

          if($productModel->updateProduct($arUpdateProductFields)) {
            $productInfo['id'] = $findProduct;
            if (MG::enabledStorage() && $importInWarehouse > 0) {
              $productModel->updateStorageCount($findProduct, $importInWarehouse, $productCount);
              $productModel->recalculateStoragesById($findProduct);
            }
          }
        } else {
          $arAddProductFields = array(
            'code' => (!empty($article)) ? $article : $productXmlId,
            'title' => $productName,
            'url' => URL::prepareUrl(URL::prepareUrl(MG::translitIt($productName), true)),
            'description' => trim(str_replace(array('<![CDATA[', ']]>'), '', $productXml->description)),
            'activity' => 1,
            'price' => $productPrice,
            'old_price' => $oldPrice,
            'currency_iso' => $currencyId,
            'image_url' => [], // implode('|', $imagesList),
            'image_title' => $productName,
            'image_alt' => $productName,
            'weight' => (!empty($weight)) ? $weight : 0,
            'cat_id' => $cat_idi,
            'recommend' => 0,
            'new' => 0,
            'related' => '',
            'inside_cat' => '',
            //'variants' => '',
            '1c_id' => $productXmlId,
            'multiplicity' => !empty($multiplicity) ? $multiplicity : 1,
          );

          if (!MG::enabledStorage()) {
            $arAddProductFields['count'] = $productCount;
          }

          $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');

          foreach ($arAddProductFields as $k => $v) {
            if(in_array($k, $maskField)) {
              $v = htmlspecialchars_decode($v);
              $arAddProductFields[$k] = htmlspecialchars($v);
            }
          }

          // Исключает дублирование.
          $dublicatUrl = false;
          $urlsCountSql = 'SELECT COUNT(`id`) as urlsCount FROM `'.PREFIX.'product` '.
            'WHERE `url` = '.DB::quote($arAddProductFields['url']).';';
          $urlsCountQuery = DB::query($urlsCountSql);
          if ($urlsCountResult = DB::fetchAssoc($urlsCountQuery)) {
            if ($urlsCountResult['urlsCount'] > 0) {
              $dublicatUrl = true;
            }
          }

          if(!empty($arAddProductFields['weight'])) {
            $arAddProductFields['weight'] = (double)str_replace(array(',',' '), array('.',''), $arAddProductFields['weight']);
          }else {
            $arAddProductFields['weight'] = 0;
          }

          if(!empty($arAddProductFields['price'])) {
            $arAddProductFields['price'] = (double)str_replace(array(',',' '), array('.',''), $arAddProductFields['price']);
          }


          $productActive = DB::query('SELECT `activity` FROM '.PREFIX.'category WHERE `id` = '.DB::quoteInt($arAddProductFields['cat_id']));
          $productActive = DB::fetchAssoc($productActive);
          $arAddProductFields['activity'] = $productActive['activity'];

          $arAddProductFields['sort'] = 0;
          $arAddProductFields['system_set'] = 1;

          if(empty($arAddProductFields['currency_iso'])) $arAddProductFields['currency_iso'] = MG::getSetting('currencyShopIso');

          DB::buildQuery('INSERT INTO `'.PREFIX.'product` SET ', $arAddProductFields);
          $productInfo['id'] = DB::insertId();

          if (MG::enabledStorage() && $importInWarehouse > 0) {
            $productModel->updateStorageCount($productInfo['id'], $importInWarehouse, $productCount);
            $productModel->recalculateStoragesById($productInfo['id']);
          }

          $updateProductSql = 'UPDATE `'.PREFIX.'product` '.
            'SET `sort` = '.$productInfo['id'];
          if ($dublicatUrl) {
            $newUrl = $arAddProductFields['url'].'_'.$productInfo['id'];
            $updateProductSql .= ', `url` = '.DB::quote($newUrl);
          }
          if (empty($arAddProductFields['code'])) {
            $updateProductSql .= ', `code` = '.$productInfo['id'];
          } elseif (empty($article)) {
            $updateProductSql .= ', `code` = '.DB::quote(MG::getSetting('prefixCode').$productInfo['id']);
          }
          $updateProductSql.=' WHERE `id` = '.$productInfo['id'].';';
          DB::query($updateProductSql);

          if (!isset($currencyShopIso)) {
            $currencyShopIso = MG::get('dbCurrency');
          }

          $productModel->updatePriceCourse($currencyShopIso, $productInfo['id']);
        }

        foreach($arProductProperty as $prop) {
          if (class_exists('Property')) {
            $propId = Property::createProp($prop['name'], 'string');
            Property::createProductStringProp($prop['value'], $productInfo['id'], $propId);
            Property::createPropToCatLink($propId, $cat_idi);
          } else {
            self::createProperty($prop['xmlId'], $prop['name'], $prop['value'], $cat_idi, $productInfo['id']);
          }
        }
      }

      if (!$onlyPrice && $images) {
        $imagesString = implode('|', $images);
        $insertImagesSql = 'INSERT IGNORE INTO `'.PREFIX.'import_yml_core_images` '.
          'VALUES ('.intval($productInfo['id']).', '.DB::quote($imagesString).') '.';';
          //'ON DUPLICATE KEY UPDATE images = '.DB::quote($imagesString).';';
        DB::query($insertImagesSql);
      }

      $count += 1;
      if ($max && $count >= $max) {
        $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
        return ['step' => 4, 'completed' => 0, 'message' => 'Все товары спаршены. (Всего '.$count.')'.$debugInfo, 'enableImagesImport' => true];
      }
      if ((microtime(true) - $startTime) > self::$maxExecTime) {
        $xmlReader->close();
        $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
        return ['step' => 3, 'completed' => $count, 'message' => self::$lang['SUCCESS_STEP'].' '.$count.$debugInfo];
      }
      $xmlReader->next($productXmlNodeName);
    }
    $xmlReader->close();
    $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
    return ['step' => 4, 'completed' => 0, 'message' => self::$lang['SUCCESS_IMPORT'].'. '.self::$lang['SUCCESS_STEP'].' '.$count.$debugInfo, 'enableImagesImport' => true];
  }

  public static function parseImagesToProducts() {
    $startTime = microtime(true);

    //Устанавливаем значения высоты и ширины миниатюр изображений
    $widthPreview = MG::getSetting('widthPreview')?MG::getSetting('widthPreview'):200;
    $widthSmallPreview = MG::getSetting('widthSmallPreview')?MG::getSetting('widthSmallPreview'):50;
    $heightPreview = MG::getSetting('heightPreview')?MG::getSetting('heightPreview'):100;
    $heightSmallPreview = MG::getSetting('heightSmallPreview')?MG::getSetting('heightSmallPreview'):50;

    $productModel = new Models_Product();

    $completed = !empty($_POST['completed']) ? $_POST['completed'] : 0;
    $count = 0;
    $imagesCount = 0;

    $imagesTempTable = '`'.PREFIX.'import_yml_core_images`';

    if (isset($_POST['data']['total'])) {
      $totalImages = $_POST['data']['total'];
    } else {
      $totalImagesSql = 'SELECT COUNT(`id`) as totalImages FROM '.$imagesTempTable.';';
      $totalImagesQuery = DB::query($totalImagesSql);
      $totalImagesResult = DB::fetchAssoc($totalImagesQuery);
      $totalImages = intval($totalImagesResult['totalImages']);
    }

    while (true) {
      if ($count < $completed) {
        $count += 1;
        continue;
      }

      $getImageDataSql = 'SELECT * FROM '.$imagesTempTable.' LIMIT 1;';
      $getImageDataQuery = DB::query($getImageDataSql);
      if (!($getImageDataResult = DB::fetchAssoc($getImageDataQuery))) {
        break;
      }
      $deleteImageDataSql = 'DELETE FROM '.$imagesTempTable.' LIMIT 1;';
      DB::query($deleteImageDataSql);

      $productId = $getImageDataResult['id'];

      $rawImageList = $getImageDataResult['images'];
      $rawImages = explode('|', $rawImageList);

      $imagesList = [];

      $upload = new Upload(false);
      $imagesErrors = [];
      foreach($rawImages as $picture) {
        if (strpos($picture, 'http') === 0) {
          $picture = urldecode($picture);
        }
        $imagesCount += 1;
        $imageRawExtension = pathinfo($picture, PATHINFO_EXTENSION);
        $imageRawExtensionParts = explode('?', $imageRawExtension);
        $imageExtension = $imageRawExtensionParts[0];
        $imageName = MG::translitIt(urldecode(md5($picture).'.'.$imageExtension),0,true);
        if (in_array($imageName, $imagesList)) {
          $imageName = str_replace('.', '', microtime(true)).'_'.$imageName;
        }
        $pictureUrlData = parse_url($picture);
        $pictureRawPathParts = explode('/', $pictureUrlData['path']);
        $picturePathParts = array_map(function($pathPart) {
          return rawurlencode($pathPart);
        }, $pictureRawPathParts);
        $pictureEncodedPath = implode('/', $picturePathParts);
        $picture = str_replace($pictureUrlData['path'], $pictureEncodedPath, $picture);

        $getPictureResult = self::getPicture($picture, $imageName, 'uploads');
        if (isset($getPictureResult['error'])) {
          $imagesErrors[] = [
            'img' => $picture,
            'error' => self::$lang[$getPictureResult['error']]
          ];
          continue;
        }
        $newFile = $getPictureResult['name'];
        $fileType = mime_content_type('uploads/'.$newFile);
        if (strpos($fileType, 'image/') !== false) {
          $resizeType = 'PROPORTIONAL';
          if ($settingResizeType = MG::getSetting('imageResizeType')) {
            $resizeType = $settingResizeType;
          }
          $upload->_reSizeImage('70_'.$imageName, 'uploads/'.$newFile, $widthPreview, $heightPreview, $resizeType);
          $upload->_reSizeImage('30_'.$imageName, 'uploads/'.$newFile, $widthSmallPreview, $heightSmallPreview, $resizeType);

          $imagesList[] = $imageName;
        } else {
          @unlink('uploads/'.$newFile);
        }
      }

      if ($imagesErrors) {
        $errorMessage = '';
        foreach ($imagesErrors as $imageError) {
          $errorMessage .= $imageError['img'].' '.$imageError['error']." \n";
        }
        $count += 1;
        $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
        $percent = round(($count / $totalImages) * 100);
        return ['step' => 5, 'completed' => $count, 'total' => $totalImages, 'message' => $errorMessage.$debugInfo];
      }

      if ($imagesList) {
        $images = implode('|', $imagesList);
        $updateProductImagesSql = 'UPDATE `'.PREFIX.'product` '.
          'SET `image_url` = '.DB::quote($images).
          'WHERE `id` = '.$productId.';';
        DB::query($updateProductImagesSql);
        $productModel->movingProductImage($imagesList, $productId, SITE_DIR.'uploads');
      }

      $count += 1;
      if ((microtime(true) - $startTime) > self::$maxExecTime) {
        $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
        $percent = round(($count / $totalImages) * 100);
        return ['step' => 5, 'completed' => $count, 'total' => $totalImages, 'message' => self::$lang['SUCCESS_STEP_IMAGES_FOR_PRODUCTS_PART1'].$count.self::$lang['SUCCESS_STEP_IMAGES_FOR_PRODUCTS_PART2'].'. '.$percent.'%'.$debugInfo];
      }
    }
    $debugInfo = self::getMemoryUsage().self::getSpeedInfo($count-$completed, microtime(true) - $startTime);
    return ['step' => 4, 'completed' => 0, 'enableImagesImport' => false, 'message' => self::$lang['SUCCESS_STEP_IMAGES_FOR_PRODUCTS_PART1'].$count.self::$lang['SUCCESS_STEP_IMAGES_FOR_PRODUCTS_PART2'].$debugInfo];
  }

  public static function getCatIdByXmlId($xmlCatId) {
    $sql = 'SELECT `id` FROM `'.PREFIX.'category` '.
      'WHERE `1c_id` = '.DB::quote($xmlCatId).';';
    $query = DB::query($sql);
    if ($result = DB::fetchAssoc($query)) {
      return $result['id'];
    }
    return 0;
  }

  public static function getMemoryUsage() {
    if (!isset($_POST['data']['debugMode']) || !$_POST['data']['debugMode'] || $_POST['data']['debugMode'] === 'false') {
      return '';
    }
    $trueMemUsage = round((memory_get_usage(true) / 1024 / 1024));
    $trueMemPickUsage = round((memory_get_peak_usage(true) / 1024 / 1024));
    $memUsage = round((memory_get_usage() / 1024 / 1024));
    $memPickUsage = round((memory_get_peak_usage() / 1024 / 1024));
    if ($_POST['data']['debugMode'] === 'short') {
      return ' | RAM used - '.max($trueMemUsage, $trueMemPickUsage, $memUsage, $memPickUsage).'Mb';
    }
    return ' | TMU: '.$trueMemUsage.' TMPU: '.$trueMemPickUsage.' MU: '.$memUsage.' MPU: '.$memPickUsage;
  }

  public static function getSpeedInfo($itemsCount, $timeInSecs) {
    if (!isset($_POST['data']['debugMode']) || !$_POST['data']['debugMode'] || $_POST['data']['debugMode'] === 'false') {
      return '';
    }
    $itemsPerMinute = round(($itemsCount / $timeInSecs) * 60);
    return ' | Speed ~'.$itemsPerMinute.' items/minute';
  }

  public static function getImagesCount() {
    $tempImagesTableName = '`'.PREFIX.'import_yml_core_images`';
    $getImagesCountSql = 'SELECT COUNT(`id`) as imagesCount FROM '.$tempImagesTableName.';';
    $getImagesCountQuery = DB::query($getImagesCountSql);
    $imagesCount = 0;
    if ($getImagesCountResult = DB::fetchAssoc($getImagesCountQuery)) {
      $imagesCount = $getImagesCountResult['imagesCount'];
    }
    return $imagesCount;
  }

  public static function getMaxExecTime() {
    return self::$maxExecTime;
  }

  public static function clearCatalog() {
    DB::query('TRUNCATE TABLE `' . PREFIX . 'cache`');
    DB::query('TRUNCATE TABLE `' . PREFIX . 'product_variant`');
    DB::query('TRUNCATE TABLE `' . PREFIX . 'product`');
    DB::query('TRUNCATE TABLE `' . PREFIX . 'product_user_property`');
    DB::query('TRUNCATE TABLE `' . PREFIX . 'product_user_property_data`');
    DB::query('TRUNCATE TABLE `' . PREFIX . 'category`');
    DB::query('TRUNCATE TABLE `' . PREFIX . 'category_user_property`');
    DB::query('TRUNCATE TABLE `' . PREFIX . 'property`');

    $dirsForDeletion = [
      'uploads'.DS.'category',
      'uploads'.DS.'product',
      'uploads'.DS.'property-img',
      'uploads'.DS.'prodtmpimg',
    ];

    foreach ($dirsForDeletion as $dirForDeletion) {
      $dirForDeletion = SITE_DIR.$dirForDeletion;
      $webpDirForDeletion = str_replace('/uploads/', '/uploads/webp/', $dirForDeletion);


      MG::rrmdir($dirForDeletion);
      MG::rrmdir($webpDirForDeletion);
    }

    return true;
  }

  public static function setMaxExecTime($execTime) {
    self::$maxExecTime = $execTime;
  }
}
