<?php

/**
 * Модель: Product
 *
 * Класс Models_Product реализует логику взаимодействия с товарами магазина.
 * - Добавляет товар в базу данных;
 * - Изменяет данные о товаре;
 * - Удаляет товар из базы данных;
 * - Получает информацию о запрашиваемом товаре;
 * - Получает продукт по его URL;
 * - Получает цену запрашиваемого товара по его id.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 *
 */
class Models_Product {

  public $storage = 'all';
  public $clone = false;

  /**
   * Добавляет товар в базу данных.
   * <code>
   * $array = array(
   *  'title' => 'title', // название товара
   *  'url' => 'link', // последняя часть ссылки на товар
   *  'code' => 'CN230', // артикул товара
   *  'price' => 100, // цена товара
   *  'old_price' => 200, // старая цена товара
   *  'image_url' => 1434653074061713.jpg, // последняя часть ссылки на изображение товара
   *  'image_title' => '', // title изображения товара
   *  'image_alt' => '', // alt изображения товара
   *  'count' => 77, // остаток товара
   *  'weight' => 5, // вес товара
   *  'cat_id' => 4, // ID основной категории товара
   *  'inside_cat' => '1,2', // дополнительные категории товаров
   *  'description' => 'descr', // описание товара
   *  'short_description' => 'short descr', // краткое описание товара
   *  'meta_title' => 'title', // seo название товара
   *  'meta_keywords' => 'title купить, CN230, title', // seo ключевые слова
   *  'meta_desc' => 'meta descr', // seo описание товара
   *  'currency_iso' => 'RUR', // код валюты товара
   *  'recommend' => 0, // выводить товар в блоке рекомендуемых
   *  'activity' => 1, // выводить товар
   *  'unit' => 'шт.', // единица измерения товара (если null, то используется единица измерения основной категории товара)
   *  'new' => 0, // выводить товар в блоке новинок
   *  'userProperty' => Array, // массив с характеристиками товара
   *  'related' => 'В-500-1', // артикулы связанных товаров
   *  'variants' => Array, // массив с вариантами товаров
   *  'related_cat' => null, // ID связанных категорий
   *  'lang' => 'default', // язык для сохранения
   *  'landingTemplate' => 'noLandingTemplate', // шаблон для лэндинга товара
   *  'ytp' => '', // строка с торговым предложением для лэндинга
   *  'landingImage' => 'no-img.jpg', // изображение для лэндинга
   *  'storage' => 'all' // склад товара
   * );
   * $model = new Models_Product();
   * $id = $model->addProduct($product);
   * echo $id;
   * </code>
   * @param array $array массив с данными о товаре.
   * @param bool $clone происходит ли клонирование или обычное добавление товара
   * @return int|bool в случае успеха возвращает id добавленного товара.
   */
  public function addProduct($array, $clone = false) {
    if(empty($array['title'])) {
      return false;
    }

    $userProperty = isset($array['userProperty'])?$array['userProperty']:array();
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['count_sort']);
    unset($array['lang']);
    if(empty($array['id'])) {
      unset($array['id']);
    }

    if(empty($array['code'])) {
      $res = DB::query('SELECT max(id) FROM '.PREFIX.'product');
      $id = DB::fetchAssoc($res);
      $array['code'] = MG::getSetting('prefixCode').($id['max(id)']+1);
    }

    $result = array();

    $array['url'] = empty($array['url']) ? MG::translitIt($array['title']) : $array['url'];

    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');

    foreach ($array as $k => $v) {
      if(in_array($k, $maskField)) {
        $v = htmlspecialchars_decode($v);
        $array[$k] = htmlspecialchars($v);
      }
    }

    if (!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);
    }

    // Исключает дублирование.
    $dublicatUrl = false;
    $tempArray = $this->getProductByUrl($array['url']);
    if (!empty($tempArray)) {
      $dublicatUrl = true;
    }

    if(!empty($array['weight'])) {
     $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
    }else {
      $array['weight'] = 0;
    }

    if(!empty($array['price'])) {
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }
    $productActive = DB::query('SELECT `activity` FROM '.PREFIX.'category WHERE `id` = '.DB::quoteInt($array['cat_id']));
    $productActive = DB::fetchAssoc($productActive);
    $array['activity'] = $productActive['activity'];

    $array['sort'] = 0;
    $array['system_set'] = 1;

    // округляем количество до 2 знаков
    if (isset($array['count'])) {
      $array['count'] = round(floatval($array['count']),2);
    }

    //сохранение настроек лендинга
    if (isset($array['landingTemplate'])) {
      $landArr['landingTemplate'] = $array['landingTemplate'];
    }
    if (isset($array['landingColor'])) {
      $landArr['landingColor'] = $array['landingColor'];
    }
    if (isset($array['ytp'])) {
      $landArr['ytp'] = $array['ytp'];
    }
    if (isset($array['landingImage'])) {
      $landArr['landingImage'] = $array['landingImage'];
    }
    if (isset($array['landingSwitch'])) {
      $landArr['landingSwitch'] = $array['landingSwitch'];
    }

    unset($array['landingTemplate']);
    unset($array['landingColor']);
    unset($array['ytp']);
    unset($array['landingImage']);
    unset($array['landingSwitch']);

    unset($array['storage']);

    unset($array['color']);
    unset($array['size']);

    if(empty($array['currency_iso'])) $array['currency_iso'] = MG::getSetting('currencyShopIso');

    if (DB::buildQuery('INSERT INTO `'.PREFIX.'product` SET ', $array)) {
      $id = DB::insertId();

      // Если url дублируется, то дописываем к нему id продукта.
      if ($dublicatUrl) {
        $url_explode = explode('_', $array['url']);
        if (count($url_explode) > 1) {
          $array['url'] = str_replace('_'.array_pop($url_explode), '', $array['url']);
        }
        $updateArray = array(
          'id' => $id,
          'url' => $array['url'].'_'.$id,
          'sort' => $id,
          'description' => $array['description'],
        );
        if (isset($landArr)) {
          if (isset($landArr['landingTemplate'])) {
            $updateArray['landingTemplate'] = $landArr['landingTemplate'];
          }
          if (isset($landArr['landingColor'])) {
            $updateArray['landingColor'] = $landArr['landingColor'];
          }
          if (isset($landArr['ytp'])) {
            $updateArray['ytp'] = $landArr['ytp'];
          }
          if (isset($landArr['landingImage'])) {
            $updateArray['landingImage'] = $landArr['landingImage'];
          }
          if (isset($landArr['landingSwitch'])) {
            $updateArray['landingSwitch'] = $landArr['landingSwitch'];
          }
        }
        if ($clone) {
          $updateArray['code'] = MG::getSetting('prefixCode').$id;
          $array['code'] = MG::getSetting('prefixCode').$id;
        }
        $this->updateProduct($updateArray);
      } else {
        $updateArray = array(
          'id' => $id,
          'url' => $array['url'],
          'sort' => $id,
          'description' => isset($array['description'])?$array['description']:'',
        );
        if (isset($landArr)) {
          if (isset($landArr['landingTemplate'])) {
            $updateArray['landingTemplate'] = $landArr['landingTemplate'];
          }
          if (isset($landArr['landingColor'])) {
            $updateArray['landingColor'] = $landArr['landingColor'];
          }
          if (isset($landArr['ytp'])) {
            $updateArray['ytp'] = $landArr['ytp'];
          }
          if (isset($landArr['landingImage'])) {
            $updateArray['landingImage'] = $landArr['landingImage'];
          }
          if (isset($landArr['landingSwitch'])) {
            $updateArray['landingSwitch'] = $landArr['landingSwitch'];
          }
        }
        if ($clone) {
          $updateArray['code'] = MG::getSetting('prefixCode').$id;
          $array['code'] = MG::getSetting('prefixCode').$id;
        }
        $this->updateProduct($updateArray);
      }
      unset($landArr);

      $array['id'] = $id;
      $array['sort'] = (int)$id;
      $array['userProperty'] = $userProperty;
      $userProp = array();

      if ($clone) {
        if (!empty($userProperty)) {
          foreach ($userProperty as $property) {
            if (isset($property['property_id']) && isset($property['value'])) {
              $userProp[$property['property_id']] = $property['value'];
            }
            if (!empty($property['product_margin'])) {
              $userProp[("margin_".$property['property_id'])] = $property['product_margin'];
            }
          }
          $userProperty = $userProp;
        }
      }

      if (!empty($userProperty)) {
        Property::saveUserProperty($userProperty, $id);
      }

      // Обновляем и добавляем варианты продукта.
      $this->saveVariants($variants, $id);
      $variants = $this->getVariants($id);
      foreach ($variants as $variant) {
        $array['variants'][] = $variant;
      }

      if(!empty($array['variants'][0]['code'])) {
        $arrayVariant = array('code' => $array['variants'][0]['code']);
        $this->fastUpdateProduct($array['id'], $arrayVariant);
      }

      $tempProd = $this->getProduct($id);
      $array['category_url'] = $tempProd['category_url'];
      $array['product_url'] = $tempProd['product_url'];

      $result = $array;
    }

    if (!isset($currencyShopIso)) {
      $currencyShopIso = MG::get('dbCurrency');
    }

    $this->updatePriceCourse($currencyShopIso, array($result['id']));

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Изменяет данные о товаре.
   * <code>
   * $array = array(
   *  'id' => 23, // ID товара
   *  'title' => 'title', // название товара
   *  'url' => 'link', // последняя часть ссылки на товар
   *  'code' => 'CN230', // артикул товара
   *  'price' => 100, // цена товара
   *  'old_price' => 200, // старая цена товара
   *  'image_url' => 1434653074061713.jpg, // последняя часть ссылки на изображение товара
   *  'image_title' => '', // title изображения товара
   *  'image_alt' => '', // alt изображения товара
   *  'count' => 77, // остаток товара
   *  'weight' => 5, // вес товара
   *  'cat_id' => 4, // ID основной категории товара
   *  'inside_cat' => '1,2', // дополнительные категории товаров
   *  'description' => 'descr', // описание товара
   *  'short_description' => 'short descr', // краткое описание товара
   *  'meta_title' => 'title', // seo название товара
   *  'meta_keywords' => 'title купить, CN230, title', // seo ключевые слова
   *  'meta_desc' => 'meta descr', // seo описание товара
   *  'currency_iso' => 'RUR', // код валюты товара
   *  'recommend' => 0, // выводить товар в блоке рекомендуемых
   *  'activity' => 1, // выводить товар
   *  'unit' => 'шт.', // единица измерения товара (если null, то используется единица измерения основной категории товара)
   *  'new' => 0, // выводить товар в блоке новинок
   *  'userProperty' => Array, // массив с характеристиками товара
   *  'related' => 'В-500-1', // артикулы связанных товаров
   *  'variants' => Array, // массив с вариантами товаров
   *  'related_cat' => null, // ID связанных категорий
   *  'lang' => 'default', // язык для сохранения
   *  'landingTemplate' => 'noLandingTemplate', // шаблон для лэндинга товара
   *  'ytp' => '', // строка с торговым предложением для лэндинга
   *  'landingImage' => 'no-img.jpg', // изображение для лэндинга
   *  'storage' => 'all' // склад товара
   * );
   * $model = new Models_Product();
   * $model->updateProduct($array);
   * </code>
   * @param array $array массив с данными о товаре.
   * @return bool
   */
  public function updateProduct($array) {
    $id = $array['id'];
    $count = 0;
    if (!empty($array['description'])) {
      $array['description'] = MG::moveCKimages($array['description'], 'product', $id, 'desc', 'product', 'description');
    }

    $userProperty = !empty($array['userProperty']) ? $array['userProperty'] : null; //свойства товара
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    $updateFromModal = !empty($array['updateFromModal']) ? true : false; // варианты товара

    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['updateFromModal']);

    if (!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);
      $checkProductUrlSql = 'SELECT COUNT(`id`) as urlsCount FROM `'.PREFIX.'product` '.
        'WHERE `url` = '.DB::quote($array['url']).' '.
        'AND `id` != '.DB::quoteInt($id, true).';';
      $checkProductUrlQuery = DB::query($checkProductUrlSql);
      if ($checkProductUrlResult = DB::fetchAssoc($checkProductUrlQuery)) {
        if ($checkProductUrlResult['urlsCount'] > 0) {
          $array['url'] .= '_'.$id;
        }
      }
    }

    // перехватываем данные для записи, если выбран другой язык
    $lang = null;
    if (isset($array['lang'])) {
      $lang = $array['lang'];
    }
    unset($array['lang']);

    $filter = array('title','meta_title','meta_keywords','meta_desc','description','short_description','unit');
    $opf = Models_OpFieldsProduct::getFields();
    foreach ($opf as $value) {
      $filter[] = 'opf_'.$value['id'];
    }
    $localeData = MG::prepareLangData($array, $filter, $lang);

    $filterLanding = array('ytp');
    $localeDataLanding = MG::prepareLangData($array, $filterLanding, $lang);

    // фильтрация данных
    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');
    foreach ($array as $k => $v) {
      if(in_array($k, $maskField)) {
        $v = htmlspecialchars_decode($v);
        $array[$k] = htmlspecialchars($v);
      }
    }

    $result = false;

    // Если происходит обновление параметров.
    if (!empty($id)) {
      unset($array['delete_image']);

      if(isset($array['weight']) && $array['weight']) {
        $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
      }

      if(isset($array['price']) && $array['price']) {
        $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
      }
      if(isset($array['price_course']) && $array['price_course']) {
        $array['price_course'] = (double)str_replace(array(',',' '), array('.',''), $array['price_course']);
      }
      if(isset($array['price_course']) && empty($array['price_course'])) {
        unset($array['price_course']);
      }

      if (isset($array['code']) && empty($array['code'])) {
        unset($array['code']);
      }

      // логгер
      $array['id'] = $id;
      $user_log_array = $array;
      $user_log_array['userProperty'] = $userProperty;
      if(isset($variants)){
        if(count($variants)>0) {
          $user_log_array['variants'] = $variants;
        }
      }
      LoggerAction::logAction('Product',__FUNCTION__, $user_log_array);

      //сохранение настроек лендинга
      if (isset($array['landingImage'])) {
        $tmp = explode('/', $array['landingImage']);
        $tmp = array_slice($tmp, -1);
        if ($tmp[0] == 'no-img.jpg') {
          unset($array['landingImage']);
        }
      }


      if (
        (!isset($array['landingTemplate']) || $array['landingTemplate'] == 'noLandingTemplate') &&
        (!isset($array['ytp']) || $array['ytp'] == '') &&
        !isset($array['landingImage']) &&
        (!isset($array['landingSwitch']) || $array['landingSwitch'] == -1) || !isset($array['landingTemplate']) || $array['landingTemplate'] == '') {
        DB::query("DELETE FROM `".PREFIX."landings` where id = ".DB::quoteInt($id));
      }
      else{
        if (!isset($array['landingColor'])) {$array['landingColor'] = null;}
        if (!isset($array['landingSwitch'])) {$array['landingSwitch'] = null;}
        if (!isset($array['landingImage'])) {$array['landingImage'] = null;}
        DB::query("INSERT INTO `".PREFIX."landings` (id, template, templateColor, ytp, image, buySwitch) 
        VALUES(".DB::quoteInt($id).", ".DB::quote($array['landingTemplate']).", ".DB::quote($array['landingColor']).", ".DB::quote($array['ytp']).", ".DB::quote($array['landingImage']).", ".DB::quote($array['landingSwitch']).") 
        ON DUPLICATE KEY UPDATE template = ".DB::quote($array['landingTemplate']).", templateColor = ".DB::quote($array['landingColor']).", ytp = ".DB::quote($array['ytp']).", image = ".DB::quote($array['landingImage']).", buySwitch = ".DB::quote($array['landingSwitch']));
      }

      unset($array['landingTemplate']);
      unset($array['landingColor']);
      unset($array['ytp']);
      unset($array['landingImage']);
      unset($array['landingSwitch']);

      // фикс для размерной сетки, чтобы сюда не шло то, что не надо
      unset($array['color']);
      unset($array['size']);

      // если есть склады,
      if(MG::enabledStorage()) {
        $count = isset($array['count'])?round($array['count'],2):null;
        unset($array['count']);
      }
      $storage = 'all';
      if (isset($array['storage'])) {
        $storage = $array['storage'];
      }
      unset($array['storage']);

      foreach ($array as $key => $value) {
        if($key == '') unset($array[$key]);
      }

      if (!empty($userProperty)) {
        $res = DB::query("SELECT `cat_id` FROM `".PREFIX."product` WHERE id = ".DB::quoteInt($id));
        $row = DB::fetchAssoc($res);
        $oldCatId = $row['cat_id'];
        Property::addCategoryBinds($oldCatId, $array['cat_id'], $userProperty);
      }

      $setParts = [];
      foreach ($array as $field => $value) {
        $setParts[] = '`'.$field.'` = '.DB::quote($value);
      }
      // Обновляем стандартные  свойства продукта.
      if (DB::query('
          UPDATE `'.PREFIX.'product`
          SET '.implode(', ', $setParts).'
          WHERE id = '.DB::quote($id))) {

        // сохраняем локализацию
        MG::saveLocaleData($id, $lang, 'product', $localeData);
        MG::saveLocaleData($id, $lang, 'landings', $localeDataLanding);

        // Обновляем пользовательские свойства продукта.
        if (!empty($userProperty)) {
          Property::saveUserProperty($userProperty, $id, $lang);
        }

        // сохраняем количество товара на определенном складе
        if(MG::enabledStorage()) {
          if($storage != 'all') {
            // NEW
            $this->updateStorageCount($id, $storage, round($count, 2));
            $this->recalculateStoragesById($id);

            // OLD
            // $res = DB::query('SELECT id FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quote($id).' 
            //   AND storage = '.DB::quote($storage).' AND variant_id = 0');
            // if($row = DB::fetchassoc($res)) {
            //   DB::query('UPDATE '.PREFIX.'product_on_storage SET count = '.DB::quoteFloat(round($count,2)).' WHERE id = '.DB::quoteInt($row['id']));
            // } else {
            //   DB::query('INSERT INTO '.PREFIX.'product_on_storage (product_id, storage, count) VALUES
            //     ('.DB::quoteInt($id).', '.DB::quote($storage).', '.DB::quoteFloat(round($count,2)).')');
            // }
          }
        }

        // Эта проверка нужна только для того, чтобы исключить удаление
        //вариантов при обновлении продуктов не из карточки товара в админке,
        //например по нажатию на "лампочку".
        if (!empty($variants) || $updateFromModal) {

          // обновляем и добавляем варианты продукта.
          if ($variants === null) {
            $variants = array();
          }

          $filterVar = array('title_variant');
          $opf = Models_OpFieldsProduct::getFields();
          foreach ($opf as $value) {
            $filterVar[] = 'opf_'.$value['id'];
          }
          foreach ($variants as &$item) {
            $localeDataVariants = MG::prepareLangData($item, $filterVar, $lang);
            MG::saveLocaleData(isset($item['id'])?$item['id']:null, $lang, 'product_variant', $localeDataVariants);
          }
          if(!empty($variants[0]['code'])) {
            $arrayVariant = array('code' => $variants[0]['code']);
            $this->fastUpdateProduct($array['id'], $arrayVariant);
          }
          // оключаем сохранение вариантов, когда выбран другой язык, чтобы все не поломать
          if(empty($localeDataVariants)) {
            $this->saveVariants($variants, $id);
          }
        }

        $result = true;
      }
    } else {
      $result = $this->addProduct($array);
    }

    $currencyShopIso = MG::getSetting('currencyShopIso');

    $this->updatePriceCourse($currencyShopIso, array($id));

    Storage::clear('product-'.$id, 'sizeMap-'.$id, 'catalog', 'prop');

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Обновляет поле в варианте и синхронизирует привязку первого варианта с продуктом.
   * <code>
   * $array = array(
   * 'price' => 200, // цена
   * 'count' => 50 // количество
   * );
   * $model = new Models_Product();
   * $model->fastUpdateProductVariant(5, $array, 2);
   * </code>
   * @param int $id id варианта.
   * @param array $array ассоциативный массив поле=>значение.
   * @param int $product_id id продукта.
   * @return bool
   */
  public function fastUpdateProductVariant($id, $array, $product_id) {

    //логирование
    $logArray = $array;
    $logArray['variant_id'] = $id;
    $logArray['id'] = $product_id;
    LoggerAction::logAction('Product',__FUNCTION__, $logArray);

    if (!DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = '.DB::quote($id))) {
      return false;
    };

    // Следующие действия выполняются для синхронизации  значений первого
    // варианта со значениями записи продукта из таблицы product.
    // Перезаписываем в $array новое значение от первого в списке варианта,
    // и получаем id продукта от этого варианта
    $variants = $this->getVariants($product_id);

    $field = array_keys($array);
    foreach ($variants as $key => $value) {
      $array[$field[0]] = $value[$field[0]];
      break;
    }

    // Обновляем продукт в соответствии с первым вариантом.
    $this->fastUpdateProduct($product_id, $array);
    return true;
  }

  /**
   * Аналогичная fastUpdateProductVariant функция, но с поправками для
   * процесса импорта вариантов.
   * <code>
   *   $model = new Models_Product();
   *   $model->importUpdateProductVariant(5, $array, 2);
   * </code>
   * @param int $id id варианта.
   * @param array $array массив поле = значение.
   * @param int $product_id id продукта.
   * @return bool
   */
  public function importUpdateProductVariant($id, $array, $product_id) {
    if($array['weight']) {
     $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
    }

    if($array['price']) {
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }

    if(isset($array['price_course']) && $array['price_course']) {
      $array['price_course'] = (double)str_replace(array(',',' '), array('.',''), $array['price_course']);
    }
    
    if(empty($array['price_course'])) {
      unset($array['price_course']);
    }

   // костыль, на будущее, может пригодится, от нулевых price_course
   // if(empty($array['price_course'])|| $array['price_course']===0) {
   //   $array['price_course']=$array['price'];
   // }

    if (!$id || !DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = %d
     ', $id)) {
      $res = DB::query('SELECT MAX(id) FROM '.PREFIX.'product_variant');
      while($row = DB::fetchAssoc($res)) {
        $array['sort'] = $row['MAX(id)']+1;
      }
      DB::query('
       INSERT INTO `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array)
      );
    };

    return true;
  }

  /**
   * Обновление заданного поля продукта.
   * <code>
   * $array = array(
   * 'price' => 200, // цена
   * 'sort' => 5, // номер сортировки
   * 'count' => 50 // количество
   * );
   * $model = new Models_Product();
   * $model->fastUpdateProduct(5, $array);
   * </code>
   * @param int $id - id продукта.
   * @param array $array - параметры для обновления.
   * @return bool
   */
  public function fastUpdateProduct($id, $array) {
    if(isset($array['price']) && $array['price']) {
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }
    if(isset($array['sort']) && $array['sort']) {
      $array['sort'] = (int)str_replace(array(',',' '), array('.',''), $array['sort']);
    }
    if(isset($array['count']) && $array['count']) {
      $array['count'] = (float)str_replace(array(',',' '), array('.',''), $array['count']);
      $array['count'] = round($array['count'],2);
    }

    //логирование
    $logArray = $array;
    $logArray['id'] = $id;
    LoggerAction::logAction('Product',__FUNCTION__, $logArray);

    $setParts = [];
    foreach ($array as $field => $value) {
      $setParts[] = '`'.$field.'` = '.DB::quote($value);
    }

    if (!DB::query('
      UPDATE `'.PREFIX.'product`
      SET '.implode(', ', $setParts).'
      WHERE id = %d
    ', $id)) {
      return false;
    };

    if(isset($array['price'])) {
      $currencyShopIso = MG::getSetting('currencyShopIso');
      $this->updatePriceCourse($currencyShopIso, array($id));
    }

    $result = true;
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Сохраняет варианты товара.
   * <code>
   * $variants = Array(
   *  0 => Array(
   *     'color' => 19, // id цвета варианта
   *     'size' => 11, // id размера варианта
   *     'title_variant' => '22 Голубой', // название
   *     'code' => 'SKU241', // артикул
   *     'price' => 2599, // цена
   *     'old_price' => 3000, // старая цена
   *     'weight' => 1, // вес
   *     'count' => 50, // количество
   *     'activity' => 1, // активность
   *     'id' => 1249, // id варианта
   *     'currency_iso' => 'RUR', // код валюты
   *     'image' => '13140250299.jpg' // название картинки варианта
   *  )
   * );
   * $model = new Models_Product();
   * $model->saveVariants($variants, 51);
   * </code>
   * @param array $variants набор вариантов
   * @param int $id id товара
   * @return bool
   */
  public function saveVariants($variants = array(), $id) {
    $existsVariant = $countArray = array();
    $count = 0;

    $dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product` WHERE FIELD = \'system_set\'');
    if(!$row = DB::fetchArray($dbRes)) {
      return false;
    }

    $dbRes = DB::query("SELECT * FROM `".PREFIX."product_variant` WHERE product_id = ".DB::quote($id));

    while ($arRes = DB::fetchAssoc($dbRes)) {
      $existsVariant[$arRes['id']] = $arRes;
    }

    foreach ($variants as $item) {
      if (!isset($item['id'])) {continue;}
      $res = DB::query('SELECT count FROM '.PREFIX.'product_variant WHERE id = '.DB::quoteInt($item['id']));
      while ($row = DB::fetchAssoc($res)) {
        $countArray[$item['id']] = $row['count'];
      }
    }

    // Удаляем все имеющиеся товары.
    $res = DB::query("DELETE FROM `".PREFIX."product_variant` WHERE product_id = ".DB::quote($id));

    if(!empty($variants) && !empty($_POST['storage']) && trim($_POST['storage']) !== 'all') {
      // NEW
      $this->deleteStorageRecordsAll($id, trim($_POST['storage']));

      // OLD
      // DB::query("DELETE FROM `".PREFIX."product_on_storage` WHERE product_id = ".DB::quote($id).' 
      //   AND storage = '.DB::quote($_POST['storage']));
    }

    // Если вариантов как минимум два.
   // if (count($variants) > 1) {
      // Сохраняем все отредактированные варианты.
    $i = 1;
    $recalculateStorage = false;
    foreach ($variants as $variant) {
      if (isset($variant['id']) && !empty($existsVariant[$variant['id']]['1c_id'])) {
        $variant['1c_id'] = $existsVariant[$variant['id']]['1c_id'];
      }
      if (empty($variant['code'])) {
        $variant['code'] = MG::getSetting('prefixCode').$id.'_'.$i;
      }
      $variant['sort'] = $i++;
      unset($variant['product_id']);
      unset($variant['rate']);
      unset($variant['count_sort']);
      unset($variant['weightCalc']);
      if(!empty($variant['id'])) {

        if(MG::enabledStorage()) {
          $count = $variant['count'];
          $variant['count'] = round($countArray[$variant['id']],2);
        }
      }

      $varId = isset($variant['id'])?$variant['id']:null;
      if(isset($this->clone) && $this->clone) {
        unset($variant['id']);
      }
      DB::query(' 
        INSERT  INTO `'.PREFIX.'product_variant` 
        SET product_id= '.DB::quote($id).", ".DB::buildPartQuery($variant)
      );

      $newVarId = DB::insertId();

      if($i === 2 && MG::get('wholesales_prices') != 0){
        //если вариантов не было, передаем оптовые цены товара первому варианту 
        if (count($variants) > 1 && empty($existsVariant)){

          $matchProductToVariant = '
            UPDATE 
              '.PREFIX.'wholesales_sys 
            SET 
              variant_id = '.DB::quoteInt($newVarId).'
            WHERE 
              product_id = '.DB::quoteInt($id).' AND
              variant_id = 0';

          DB::query($matchProductToVariant);
        }

        if (count($variants) <= 1 && !empty($existsVariant)){

          $variantToProduct = '
          UPDATE 
            '.PREFIX.'wholesales_sys 
          SET 
            `variant_id` = 0
          WHERE 
            `variant_id` = '.DB::quoteInt($newVarId);

          if(!empty($matchProductToVariant)){
            DB::query($matchProductToVariant);
          }

          $deleteVariantsFromWholeTable = '
          DELETE FROM 
            '.PREFIX.'wholesales_sys 
          WHERE 
            `product_id` = '.DB::quoteInt($id).' AND
            `variant_id` != 0';

          DB::query($deleteVariantsFromWholeTable);
        }
      }

      if(isset($this->clone) && $this->clone) {
        MG::cloneLocaleData($varId, $newVarId, 'product_variant');
        if(MG::enabledStorage()) {
          $this->cloneStorageData($varId, $id, $newVarId);
        }
      }

      // сохраняем количество товара на определенном складе
      if(!empty($varId)) {
        if(MG::enabledStorage()) {
          if(!empty($_POST['storage'])) {
            $this->storage = $_POST['storage'];
          }
          if($this->storage != 'all') {
            // $res = DB::query('SELECT id FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quote($id).'
            //   AND storage = '.DB::quote($this->storage).' AND variant_id = '.$variant['id']);
            // if($row = DB::fetchassoc($res)) {
            //   DB::query('UPDATE '.PREFIX.'product_on_storage SET count = '.DB::quote($count).' WHERE id = '.DB::quoteInt($row['id']));
            // } else {
              $this->updateStorageCount($id, $this->storage, round($count, 2), $varId);
              $recalculateStorage = true;
            // }
          }
        }
      }
    }
    if ($recalculateStorage) {
      $this->recalculateStoragesById($id);
    }
   // }
  }

  /**
   * Клонирует товар.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $model->cloneProduct($productId);
   * </code>
   * @param int $id id клонируемого товара.
   * @return array
   */
  public function cloneProduct($id) {
    $result = false;

    $arr = $this->getProduct($id, true, true);
    $arr['unit'] = $arr['product_unit'];
    $arr['title'] = htmlspecialchars_decode($arr['title']);
    $image_url = basename($arr['image_url']);

    foreach ($arr['images_product'] as $k=>$image) {
      $arr['images_product'][$k] = basename($image);
    }
    $arr['image_url'] = implode("|", $arr['images_product']);
    $imagesArray = $arr['images_product'];

    $userProperty = $arr['thisUserFields'];

    unset($arr['product_unit']);
    unset($arr['category_unit']);
    unset($arr['product_weightUnit']);
    unset($arr['category_weightUnit']);
    unset($arr['weightUnit']);
    unset($arr['weightCalc']);
    unset($arr['count_hr']);
    unset($arr['real_category_unit']);
    unset($arr['category_name']);
    unset($arr['thisUserFields']);
    unset($arr['category_url']);
    unset($arr['product_url']);
    unset($arr['images_product']);
    unset($arr['images_title']);
    unset($arr['images_alt']);
    unset($arr['rate']);
    unset($arr['plugin_message']);
    unset($arr['id']);
    unset($arr['count_buy']);
    $arr['code'] = '';
    $arr['userProperty'] = $userProperty;
    $variants = $this->getVariants($id);

    foreach ($variants as &$item) {
      // unset($item['id']);
      unset($item['product_id']);
      unset($item['rate']);
      unset($item['orig_price_course']);
      $item['code'] = '';
      $imagesArray[] = $item['image'];
    }

    $arr['variants'] = $variants;

    // перед клонированием создадим копии изображений,
    // чтобы в будущем можно было без проблемно удалять их вместе с удалением продукта
    $result = $this->addProduct($arr, true);

    $this->cloneImagesProduct($imagesArray, $id, $result['id']);

    // клонирование характеристик характеристик
    if (isset($userProperty) && is_array($userProperty)) {
      foreach ($userProperty as $item) {
        if (!isset($item['value'])) {
          $item['value'] = '';
        }
        if(empty($item['data'])) {
          if ($item['type'] != 'assortmentCheckBox') {
            DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, product_id, name) VALUES
              ('.DB::quote($item['prop_id']).', '.DB::quote($result['id']).', '.DB::quote($item['value']).')');
            MG::cloneLocaleData($item['prop_id'], DB::insertId(), 'product_user_property_data');
          }
        } else {
          foreach ($item['data'] as $val) {
            DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, product_id, name, margin, active, prop_data_id) VALUES
              ('.DB::quote($item['prop_id']).', '.DB::quote($result['id']).', '.DB::quote($val['name']).', 
              '.DB::quote($val['margin']).', '.DB::quote($val['active']).', '.DB::quote($val['prop_data_id']).')');
            MG::cloneLocaleData($item['prop_id'], DB::insertId(), 'product_user_property_data');
          }
        }
      }
    }

    // клонирование складов
    if(MG::enabledStorage()) {
      $this->cloneStorageData($id, $result['id']);
    }
    // клонирование локализаций
    MG::cloneLocaleData($id, $result['id'], 'product');

    $result['image_url'] = $image_url;
    $result['currency'] = MG::getSetting('currency');

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

   /**
     * Клонирует изображения продукта.
     * <code>
     *   $imagesArray = array(
     *     '40Untitled-1.jpg',
     *     '41Untitled-1.jpg',
     *     '42Untitled-1.jpg'
     *   );
     *   $oldId = 40;
     *   $newId = 130;
     *   $model = new Models_Product;
     *   $model->deleteProduct($imagesArray, $oldId, $newId);
     * </code>
     * @param array $imagesArray массив url изображений, которые надо клонировать.
     * @param int $oldId старый ID товара.
     * @param int $newId новый ID товара.
     * @return bool
     */
  public function cloneImagesProduct($imagesArray = array(), $oldId = 0, $newId = 0) {
    if(!$oldId && !$newId) return false;
    $ds = DS;
    $documentroot = str_replace($ds.'mg-core'.$ds.'models','',dirname(__FILE__)).$ds;
    $dir = floor($oldId/100).'00'.$ds.$oldId;
    $this->movingProductImage($imagesArray, $newId, 'uploads'.$ds.'product'.$ds.$dir, false);

    return true;
  }

  /**
   * Удаляет товар, его свойства, варианты, локализации, оптовые цены из базы данных.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $model->deleteProduct($productId);
   * </code>
   * @param int $id id удаляемого товара
   * @return bool
   */
  public function deleteProduct($id) {
    $result = false;
    $prodInfo = $this->getProduct($id);

    // $this->deleteImagesProduct($prodInfo['images_product'], $id);
    // $this->deleteImagesVariant($id);
    // $this->deleteImagesFolder($id);
    $imgFolder = SITE_DIR.'uploads'.DS.'product'.DS.floor($id/100).'00'.DS.$id;
    $imgWebpFolder = SITE_DIR.'uploads'.DS.'webp'.DS.'product'.DS.floor($id/100).'00'.DS.$id;
    MG::rrmdir($imgFolder);
    MG::rrmdir($imgWebpFolder);


	// логгер
    LoggerAction::logAction('Product',__FUNCTION__, $id);

    // Удаляем продукт из базы.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    // Удаляем все значения пользовательских характеристик данного продукта.
    $res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE product_id = '.DB::quoteInt($id));
    while($row = DB::fetchAssoc($res)) {
      MG::removeLocaleDataByEntity($row['id'], 'product_user_property_data');
    }
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_user_property_data`
      WHERE product_id = %d
    ', $id);

    // Удаляем все варианты данного продукта.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_variant`
      WHERE product_id = %d
    ', $id);

    // удаляем склады
    DB::query('DELETE FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quoteInt($id));

    // Удаляем лендинг
    DB::query("DELETE FROM `".PREFIX."landings` where id = ".DB::quoteInt($id));

    // удаляем локализацию
    MG::removeLocaleDataByEntity($id, 'product');
    MG::removeLocaleDataByEntity($id, 'landings');

    $result = true;
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

   /**
   * Удаляет папки из структуры папок изображений относящиеся к заданному продукту.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $model->deleteImagesFolder($productId);
   * </code>
   * @param int $id id товара.
   */
  public function deleteImagesFolder($id) {
    if(!empty($id)) {
      $ds = DS;
      $path = 'uploads'.$ds.'product'.$ds.floor($id/100).'00'.$ds.$id;
      if(file_exists($path)) {
        if(file_exists($path.$ds.'thumbs')) {
          rmdir($path.$ds.'thumbs');
        }
        rmdir($path);
      }
    }
  }
  /**
   * Удаляет все картинки привязанные к продукту.
   * <code>
   *   $array = array(
   *    'product/100/105/120.jpg',
   *    'product/100/105/122.jpg',
   *    'product/100/105/121.jpg'
   *  );
   *  $model = new Models_Product();
   *  $model->deleteImagesProduct($array);
   * </code>
   * @param array $arrayImages массив с названиями картинок
   * @param int $productId ID товара
   */
   public function deleteImagesProduct($arrayImages = array(), $productId = false) {
     if(empty($arrayImages)) {
       return true;
     }
     // удаление картинки с сервера
    $uploader = new Upload(false);
    foreach ($arrayImages as $key => $imageName) {
      $pos = strpos($imageName, 'no-img');
      if(!$pos && $pos !== 0) {
        $uploader->deleteImageProduct($imageName, $productId);
      }
    }
  }
  /**
   * Получает информацию о запрашиваемом товаре.
   * <code>
   * $where = '`cat_id` IN (5,6)';
   * $model = new Models_Product;
   * $result = $model->deleteImagesFolder($where);
   * viewData($result);
   * </code>
   * @param string $where необязательный параметр, формирующий условия поиска, например: id = 1
   * @return array массив товаров
   */
  public function getProductByUserFilter($where = '', $joinVariant = false) {
    $result = array();

    if ($where) {
      $where = ' WHERE '.$where;
    }

    if ($joinVariant) {
      $res = DB::query('
      SELECT  CONCAT(c.parent_url,c.url) as category_url,
        p.url as product_url, p.*, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`,
        p.`currency_iso`
      FROM `'.PREFIX.'product` p
        LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id
        LEFT JOIN `'.PREFIX.'product_variant` pv 
        ON pv.product_id = p.id
      '.$where);
    } else {
      $res = DB::query('
      SELECT  CONCAT(c.parent_url,c.url) as category_url,
        p.url as product_url, p.*, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
        p.`currency_iso`
      FROM `'.PREFIX.'product` p
        LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id
      '.$where);
    }



    while ($product = DB::fetchAssoc($res)) {
      $result[$product['id']] = $product;
    }
    if (!empty($result) && $joinVariant) {
      $catalogModel = new Models_Catalog();
      $result = $catalogModel->addPropertyToProduct($result);
      foreach ($result as $productId => $product) {
        $result[$productId]['variant_exist'] = $product['variants'][0]['id'];
      }
    }
    return $result;
  }

  /**
   * Получает информацию о запрашиваемом товаре по его ID.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $product = $model->getProduct($productId);
   * viewData($product);
   * </code>
   * @param int $id id запрашиваемого товара.
   * @param bool $getProps возвращать ли характеристики.
   * @param bool $disableCashe отключить ли кэш.
   * @return array массив с данными о товаре.
   */
  public function getProduct($id, $getProps = true, $disableCashe = false) {
    $prodCash = false;
    if(!$disableCashe && $getProps) $prodCash = Storage::get('product-'.$id.'-'.LANG.'-'.MG::getSetting('currencyShopIso'));

    if(!$prodCash) {
      $id =  intval($id);
      $result = array();
      $res = DB::query('
        SELECT  CONCAT(c.parent_url,c.url) as category_url, c.title as category_name,
          c.unit as category_unit, p.unit as product_unit,
          c.weight_unit as category_weightUnit, p.weight_unit as product_weightUnit,
          p.url as product_url, p.*, rate, (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
          p.`currency_iso` 
        FROM `'.PREFIX.'product` p
          LEFT JOIN `'.PREFIX.'category` c
          ON c.id = p.cat_id
        WHERE p.id = '.DB::quoteInt($id, true));

      if (!empty($res)) {
        if ($product = DB::fetchAssoc($res)) {
          $result = $product;

          // подгражем количество товара на складах
          // NEW
          $storagesEnabled = MG::enabledStorage();
          if ($storagesEnabled) {
            $storage = $_POST['storage'];
            $storageCount = 0;
            if ($storage && $storage !== 'all') {
              $storageCount = $this->getProductStorageCount($storage, $id);
            } else {
              $storageCount = $this->getProductStorageTotalCount($id);
            }
            $result['count'] = $storageCount;
            $result['count_hr'] = '';
            $convertCountToHR = MG::getSetting('convertCountToHR');
            if (!empty($convertCountToHR) && !MG::isAdmin()) {
              $result['count_hr'] = MG::convertCountToHR($result['count']);
            }
          }
          // OLD
          // if(empty($_POST['storage'])) $_POST['storage'] = 'all';
          // if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
          // $result['count'] = MG::getProductCountOnStorage($result['count'], $id, 0, $this->storage);

          if ($getProps) {
            // Запрос делает следующее
            // 1. Вычисляет список пользовательских характеристик для категории товара,
            // 2. Присваивает всем параметрам значения по умолчанию,
            // 3. Находит заполненные характеристики товара, заменяет ими значения по умолчанию.
            // В результате получаем набор всех пользовательских характеристик включая те, что небыли определены явно.
            $assortmentCheckBoxes = array(0);

            $res = DB::query("
              SELECT pup.prop_id, pup.type_view, prop.*
              FROM `".PREFIX."product_user_property_data` as pup
              LEFT JOIN `".PREFIX."property` as prop
                ON pup.prop_id = prop.id
              LEFT JOIN  `".PREFIX."category_user_property` as cup 
                ON cup.property_id = prop.id
              WHERE pup.`product_id` = ".DB::quote($id)." AND cup.category_id = ".DB::quote($result['cat_id'])."
              ORDER BY `sort` DESC;
            ");

            while ($userFields = DB::fetchAssoc($res)) {
              if ($userFields['type'] == 'assortmentCheckBox') {
                $assortmentCheckBoxes[] = $userFields['prop_id'];
              }
              // Заполняет каждый товар его характеристиками.
              $result['thisUserFields'][$userFields['prop_id']] = $userFields;
            }

            $res = DB::query("
              SELECT pup.prop_id, pup.type_view, prop.*
              FROM `".PREFIX."product_user_property_data` as pup
              LEFT JOIN `".PREFIX."property` as prop
                ON pup.prop_id = prop.id
              LEFT JOIN  `".PREFIX."category_user_property` as cup 
                ON cup.property_id = prop.id
              WHERE prop.`type` = 'assortmentCheckBox' AND 
                cup.category_id = ".DB::quote($result['cat_id'])." AND 
                pup.`prop_id` NOT IN (".DB::quoteIN($assortmentCheckBoxes).")
              GROUP BY pup.`prop_id`
              ORDER BY `sort` DESC;
            ");

            while ($userFields = DB::fetchAssoc($res)) {
              $result['thisUserFields'][$userFields['prop_id']] = $userFields;
            }

            // получаем содержимое сложных настроек для пользовательских характеристик
            Property::addDataToProp($result['thisUserFields'], $id);
          }

          $imagesConctructions = $this->imagesConctruction($result['image_url'],$result['image_title'],$result['image_alt'], $result['id']);
          $result['images_product'] = $imagesConctructions['images_product'];
          $result['images_title'] = $imagesConctructions['images_title'];
          $result['images_alt'] = $imagesConctructions['images_alt'];
          $result['image_url'] = $imagesConctructions['image_url'];
          $result['image_title'] = $imagesConctructions['image_title'];
          $result['image_alt'] = $imagesConctructions['image_alt'];

          $result['price'] = MG::convertPrice($result['price']);
          $result['price_course'] = MG::convertPrice($result['price_course']);
          $result['old_price'] = MG::convertPrice($result['old_price']);

          $result['unit'] = $result['product_unit'];
        }
      }
      if (empty($result['id'])) {
        return null;
      }

      if (!isset($result['category_unit'])) {
        $result['category_unit'] = 'шт.';
      }

      $cat = [
        'title' => $result['category_name'],
        'unit'=>$result['category_unit'],
      ];
      MG::loadLocaleData($id, LANG, 'product', $result);
      MG::loadLocaleData($result['cat_id'], LANG, 'category', $cat);
      if ($cat['title']) {
        $result['category_name'] = $cat['title'];
      }
      $result['product_unit'] = !empty($result['unit'])?$result['unit']:'';
      $result['real_category_unit'] = $result['category_unit'];
      $result['real_category_unit'] = $cat['unit'];
      if (isset($result['product_unit']) && $result['product_unit'] != null && strlen($result['product_unit']) > 0) {
        $result['category_unit'] = $result['product_unit'];
      }
      if (!empty($result['product_weightUnit'])) {
        $result['weightUnit'] = $result['product_weightUnit'];
      } elseif(!empty($result['category_weightUnit'])) {
        $result['weightUnit'] = $result['category_weightUnit'];
      } else {
        $result['weightUnit'] = 'kg';
      }
      if ($result['weightUnit'] != 'kg') {
        $result['weightCalc'] = MG::getWeightUnit('convert', ['from'=>'kg','to'=>$result['weightUnit'],'value'=>$result['weight']]);
      } else {
        $result['weightCalc'] = $result['weight'];
      }

      if ($getProps) {
        Storage::save('product-'.$id.'-'.LANG.'-'.MG::getSetting('currencyShopIso'), $result);
      }
    } else {
      $result = $prodCash;

      if(MG::enabledStorage()) {
        // подгражем количество товара на складах
        if(empty($_POST['storage'])) $_POST['storage'] = 'all';
        if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
        if ($this->storage && $this->storage !== 'all') {
          $result['count'] = $this->getProductStorageCount($this->storage, $id);
        } else {
          $result['count'] = $this->getProductStorageTotalCount($id);
        }
      } else {
        $res = DB::query('SELECT IF(COUNT(pv.id) = 0, round(p.count,2), SUM(pv.count)) AS count 
          FROM '.PREFIX.'product AS p
          LEFT JOIN '.PREFIX.'product_variant AS pv ON p.id = pv.product_id
          WHERE p.id = '.DB::quoteInt($id));
        while ($row = DB::fetchAssoc($res)) {
          $result['count'] = $row['count'];
        }
      }
    }

    //В обход кэша меняем название количества
    $result['count_hr'] = '';
    $convertCountToHR = MG::getSetting('convertCountToHR');
    if (!empty($convertCountToHR) && !MG::isAdmin()) {
      $result['count_hr'] = MG::convertCountToHR($result['count']);
    }

    // подгрузка цен без кэша
    if(!MG::isAdmin()) {
      $res = DB::query('SELECT p.id, p.price, p.price_course * (IFNULL(c.rate, 0) + 1) AS price_course FROM '.PREFIX.'product AS p
        LEFT JOIN '.PREFIX.'category AS c ON c.id = p.cat_id
        WHERE p.id = '.DB::quoteInt($id));
      while($row = DB::fetchAssoc($res)) {
        $result['price'] = MG::convertPrice($row['price']);
        $result['price_course'] = MG::convertPrice($row['price_course']);
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Создает массивы данных для картинок товара, возвращает три массива со ссылками, заголовками и альт, текстами.
   * <code>
   *   $model = new Models_Product();
   *   $imageUrl = '120.jpg|121.jpg';
   *   $imageTitle = 'Каритинка товара';
   *   $imageAlt = 'Альтернативная подпись картинки';
   *   $res = $model->imagesConctruction($imageUrl, $imageTitle, $imageAlt);
   *   viewData($res);
   * </code>
   * @param string $imageUrl строка с разделителями | между ссылок.
   * @param string $imageTitle строка с разделителями | между заголовков.
   * @param string $imageAlt строка с разделителями | между тестов.
   * @param string $id ID товара.
   * @return array
   */
  public function imagesConctruction($imageUrl, $imageTitle, $imageAlt, $id = 0) {
    $result = array(
      'images_product'=>array(),
      'images_title'=>array(),
      'images_alt'=>array()
    );

    // Получаем массив картинок для продукта, при этом первую в наборе делаем основной.
    $arrayImages = explode("|", $imageUrl);

    foreach($arrayImages as $cell=>$image) {
      $arrayImages[$cell] = str_replace(SITE.'/uploads/', '', mgImageProductPath($image, $id));
    }

    if (!empty($arrayImages)) {
      $result['image_url'] = $arrayImages[0];
    }

    $result['images_product'] = $arrayImages;
    // Получаем массив title для картинок продукта, при этом первый в наборе делаем основной.
    $arrayTitles = explode("|", $imageTitle);
    if (!empty($arrayTitles)) {
      $result['image_title'] = $arrayTitles[0];
    }

    $result['images_title'] = $arrayTitles;

    // Получаем массив alt для картинок продукта, при этом первый в наборе делаем основной.
    $arrayAlt = explode("|", $imageAlt);
    if (!empty($arrayAlt)) {
      $result['image_alt'] = $arrayAlt[0];
    }

    $result['images_alt'] = $arrayAlt;

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Обновляет остатки продукта, увеличивая их на заданное количество.
   * <code>
   * Models_Product::increaseCountProduct(37, 'SKU348', 2);
   * </code>
   * @param int $id номер продукта.
   * @param string $code артикул.
   * @param int $count прибавляемое значение к остатку.
   */
  public function increaseCountProduct($id, $code, $count) {

    $sql = "
      UPDATE `".PREFIX."product_variant` as pv 
      SET pv.`count`= pv.`count`+".DB::quoteFloat($count)." 
      WHERE pv.`product_id`=".DB::quoteInt($id)." 
        AND pv.`code`=".DB::quote($code)." 
        AND pv.`count`>=0
    ";

    DB::query($sql);

    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= p.`count`+".DB::quoteFloat($count)." 
      WHERE p.`id`=".DB::quoteInt($id)." 
        AND p.`code`=".DB::quote($code)." 
        AND  p.`count`>=0
    ";

    DB::query($sql);
  }

  /**
   * Обновляет остатки продукта, уменьшая их количество,
   * при смене статуса заказа с "отменен" на любой другой.
   * <code>
   * Models_Product::decreaseCountProduct(37, 'SKU348', 2);
   * </code>
   * @param int $id ID продукта.
   * @param string $code Артикул.
   * @param int $count Прибавляемое значение к остатку.
   */
  public function decreaseCountProduct($id, $code, $count) {

    $product = $this->getProduct($id);
    $variants = $this->getVariants($product['id']);
    foreach ($variants as $idVar => $variant) {
      if ($variant['code'] == $code) {
        $variantCount = ($variant['count'] * 1 - $count * 1) >= 0 ? $variant['count'] - $count : 0;
        $sql = "
          UPDATE `".PREFIX."product_variant` as pv 
          SET pv.`count`= ".DB::quoteFloat($variantCount, true)." 
          WHERE pv.`id`=".DB::quoteInt($idVar)." 
            AND pv.`code`=".DB::quote($code)." 
            AND  pv.`count`>0";
        DB::query($sql);
      }
    }

    $product['count'] = ($product['count'] * 1 - $count * 1) >= 0 ? $product['count'] - $count : 0;
    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= ".DB::quoteFloat($product['count'], true)." 
      WHERE p.`id`=".DB::quoteInt($id)." 
        AND p.`code`=".DB::quote($code)."
        AND  p.`count`>0";
    DB::query($sql);
  }

  /**
   * Удаляет все миниатюры и оригинал изображения товара из папки upload.
   * @param array $arrayDelImages массив с изображениями для удаления
   * @return bool
   * @deprecated
   */
  public function deleteImageProduct($arrayDelImages) {
    if (!empty($arrayDelImages)) {
      foreach ($arrayDelImages as $value) {
        if (!empty($value)) {
          // Удаление картинки с сервера.
          if (is_file(SITE_DIR."uploads/".basename($value))) {
            unlink(SITE_DIR."uploads/".basename($value));
            if (is_file(SITE_DIR."uploads/thumbs/30_".basename($value))) {
              unlink(SITE_DIR."uploads/thumbs/30_".basename($value));
            }
            if (is_file(SITE_DIR."uploads/thumbs/70_".basename($value))) {
              unlink(SITE_DIR."uploads/thumbs/70_".basename($value));
            }
          }
        }
      }
    }
    return true;
  }

  /**
   * Возвращает общее количество продуктов каталога.
   * <code>
   * $result = Models_Product::getProductsCount();
   * viewData($result);
   * </code>
   * @return int количество товаров.
   */
  public function getProductsCount($where = '') {
    if ($where) {
      $where = 'WHERE '.$where;
    }

    $result = 0;
    $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'product`
    '.$where);

    if ($product = DB::fetchAssoc($res)) {
      $result = $product['count'];
    }

    return $result;
  }

  /**
   * Получает продукт по его URL.
   * <code>
   * $url = 'nike-air-versitile_102';
   * $result = Models_Product::getProductByUrl($url);
   * viewData($result);
   * </code>
   * @param string $url запрашиваемого товара.
   * @param int $catId id-категории, т.к. в разных категориях могут быть одинаковые url.
   * @return array массив с данными о товаре.
   */
  public function getProductByUrl($url, $catId = false) {
    $result = array();
    $where = '';
    if ($catId !== false) {
      $where = ' and cat_id='.DB::quote($catId);
    }

    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'product`
      WHERE url = '.DB::quote($url).' 
    '.$where);

    if (!empty($res)) {
      if ($product = DB::fetchAssoc($res)) {
        $result = $product;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает цену запрашиваемого товара по его id.
   * <code>
   * $result = Models_Product::getProductPrice(5);
   * viewData($result);
   * </code>
   * @param int $id id изменяемого товара.
   * @return bool|float $error в случаи ошибочного запроса.
   */
  public function getProductPrice($id) {
    $result = false;
    $res = DB::query('
      SELECT price
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Создает форму пользовательских характеристик для товара.
   * В качестве входящего параметра получает массив:
   * <code>
   * $param = array(
   *   'id' => null, // id товара.
   *   'maxCount' => null, // максимальное количество товара на складе.
   *   'productUserFields' => null, // массив пользовательских полей для данного продукта.
   *   'action' => "/catalog", // ссылка для метода формы.
   *   'method' => "POST", // тип отправки данных на сервер.
   *   'ajax' => true, // использовать ajax для пересчета стоимости товаров.
   *   'blockedProp' => array(), // массив из ID свойств, которые не нужно выводить в форме.
   *   'noneAmount' => false, // не выводить  input для количества.
   *   'titleBtn' => "В корзину", // название кнопки.
   *   'blockVariants' => '', // блок вариантов.
   *   'classForButton' => 'addToCart buy-product buy', // классы для кнопки.
   *   'noneButton' => false, // не выводить кнопку отправки.
   *   'addHtml' => '' // добавить HTML в содержимое формы.
   *   'currency_iso' => '', // обозначение валюты в которой сохранен товар
   *   'printStrProp' => 'true', // выводить строковые характеристики
   *   'printCompareButton' => 'true', // выводить кнопку сравнения
   *   'buyButton' => 'true', // показывать кнопку 'купить' в миникарточках (если false - показывается кнопка 'подробнее')
   *   'productData' => 'Array', // массив с данными о товаре
   *   'showCount' => 'true' // показывать блок с количеством
   * );
   * $model = new Models_Product;
   * $result = $model->getProduct($param);
   * echo $result;
   * </code>
   * @param array $param массив параметров.
   * @param string $adminOrder заказ для админки или нет (по умолчанию - нет).
   * @return string html форма.
   */
  public function createPropertyForm(
    $param = array(
      'id' => null,
      'maxCount' => null,
      'productUserFields' => null,
      'action' => "/catalog",
      'method' => "POST",
      'ajax' => true,
      'blockedProp' => array(),
      'noneAmount' => false,
      'titleBtn' => "В корзину",
      'blockVariants' => '',
      'classForButton' => 'addToCart buy-product buy',
      'noneButton' => false,
      'addHtml' => '',
      'printStrProp' => null,
      'printCompareButton' => null,
      'buyButton' => '',
      'currency_iso' => '',
      'productData' => null,
      'showCount' => true,
    ),
    $adminOrder = 'nope',
    $returnArray = false
  ) {
    extract($param);

    $productCurr = !empty($param['productData']['currency_iso'])?$param['productData']['currency_iso']:null;

    if (empty($classForButton)) {
      $classForButton = 'addToCart buy-product buy';
    }
    if ((!isset($id) || $id === null) || (!isset($maxCount) || $maxCount === null)) {
      return "error param!";
    }
    if (empty($printStrProp)) {
      $printStrProp = MG::getSetting('printStrProp');
    }
    if (!isset($printCompareButton) || $printCompareButton===null) {
      $printCompareButton = MG::getSetting('printCompareButton');
    }

    if(!isset($this->groupProperty)){
      $this->groupProperty = Property::getPropertyGroup(true);
    }

    $catalogAction = MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView';
    // если используется аяксовый метод выбора, то подключаем доп класс для работы с формой.
    $marginPrice = 0; // добавочная цена, в зависимости от выбранных автоматом характеристик
    $secctionCartNoDummy = array(); //Не подставной массив характеристик, все характеристики с настоящими #ценами#
    //в сессию записать реальные значения, в паблик подмену, с привязкой в конце #№
    $html = '';
   //if ($ajax) {
    //  mgAddMeta("<script type=\"text/javascript\" src=\"".SITE."/mg-core/script/jquery.form.js\"></script>");
    //}

    $currencyRate = MG::getSetting('currencyRate');
    $currencyShort = MG::getSetting('currencyShort');
    $currency_iso = MG::getSetting('currencyShopIso');
    $currencyRate = $currencyRate[$currency_iso];
    $currencyShort = $currencyShort[$currency_iso];
    $propPieces = array();
    $htmlProperty = '';

    if (!isset($blockedProp)) {$blockedProp = array();}
    if (!isset($item)) {$item = array();}
    if (!isset($data)) {$data = array();}
    if (!empty($productUserFields)) {
      $defaultSet = array(); // набор характеристик предоставленных по умолчанию.
      // массив со строковыми характеристиками
      $stringsProperties = array();
      // массив с файловыми характеристиками
      $filesProperties = [];
      foreach ($productUserFields as $property) {

        if (in_array($property['id'], $blockedProp)) {
          continue;
        }

        // подгрузка локализаций для сложных характеристик
        if(isset($item['type']) && ($item['type'] != 'string') && ($item['type'] != 'textarea') && ($item['type'] != 'file')) {
          foreach ($data as &$val) {
            $res = DB::query("SELECT * FROM ".PREFIX."property_data WHERE `id` = ".DB::quote($val['prop_data_id']));
            while ($userFieldsData = DB::fetchAssoc($res)) {
              MG::loadLocaleData($userFieldsData['id'], LANG, 'prop_data', $userFieldsData);
              $val['name'] = $userFieldsData['name'];
            }
          }
        }
        MG::loadLocaleData($property['id'], LANG, 'property', $property);
        $collectionParse = array();
        $collectionAccess = array();
        /*
          'select' - набор значений, можно интерпретировать как  выпадающий список либо набор радиокнопок
          'assortment' - мультиселект
          'string' - пара ключ-значение
          'assortmentCheckBox' - набор чекбоксов
         */

        switch ($property['type']) {
          case 'assortmentCheckBox': {
              $marginStoper = $marginPrice;
              $htmladd = '';
              // $htmladd .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span> <span class="label-black">';
              $htmladdIn = '';

              if (is_array($property['data'])) {
                foreach ($property['data'] as $item) {
                  if ($item['active'] == 1) {
                    $htmladdIn .= ''.$item['name'].', ';
                  }
                }
              }

              $htmladdIn = substr($htmladdIn, 0, -2);
              // сохраняем в массив строковых переменных
              $tmp = array();
              $tmp['name'] = $htmladdIn;
              $tmp['group_prop'] = NULL;
              if (isset($this->groupProperty[$property['group_id']])) {
                $tmp['group_prop'] = $this->groupProperty[$property['group_id']];
              }
              $tmp['unit'] = $property['unit'];
              $tmp['priority'] = $property['sort'];
              $stringsProperties[$property['name']][] = $tmp;

              // не выводим если ненужно
              if($printStrProp=='true') {
                if (strlen($htmladdIn)) {
                  $propPieces[] = array('type' => 'assortment', 'name' => $property['name'], 'additional' => $htmladdIn);
                }
              }

              break;
            }

          case 'assortment': {

              if ((empty($property['type_view']) || $property['type_view'] == '' || $property['type_view'] == 'type_view') && !empty($property['data'][0]) && !empty($property['data'][0]['type_view'])) {
                  $property['type_view'] = $property['data'][0]['type_view'];
              }

              $marginStoper = $marginPrice;

              if (!isset($property['value'])) {
                $property['value'] = $property['default'];
              }
              if (!isset($property['property_id'])) {
                $property['property_id'] = $property['id'];
              }
              $collection = explode('|', $property['value']);

              $i = 0;
              $firstLiMargin = 0;
              $isExistSelected = false;

              foreach ($collection as $value) {
                $tempVar = $this->parseMarginToProp($value);
                if(isset($tempVar['name']) && $tempVar['name']) {
                  $collectionParse[$tempVar['name']] = $tempVar['margin'];
                } else {
                  $collectionParse[$value] = 0;
                }
              }

              $collectionAccess = array(); // допустимый актуальный перечень
              if (isset($property['product_margin'])) {
                foreach (explode('|', $property['product_margin']) as $value) {
                  $tempVar = $this->parseMarginToProp($value);
                  if($tempVar['name']) {
                    $collectionAccess[] = $tempVar['name'];
                  } else {
                    $collectionAccess[] = $value;
                  }
                }
              }

              // для типа вывода select :
              if (!isset($property['type_view']) || $property['type_view'] == 'select' || empty($property['type_view']) || $property['type_view'] == 'type_view') {
                // для типа вывода select :
                if ($property['value'] == 'null') {
                  break;
                }
                $selectPieces = array();
                // $htmlSelect = '<p class="select-type"><span class="property-title">'.$property['name'].'<span class="property-delimiter">:</span> </span><select name="'.$property['name'].'" class="last-items-dropdown">';

                if (!empty($property['data'])) {
                  foreach ($property['data'] as $item) {
                    if($item['active'] == 0) {
                      continue;
                    }

                    $value = '';
                    $value = $item['name']."#".$item['margin']."#";

                    if (empty($item)) {
                      $item = array('name' => $value, 'margin' => 0);
                    }

                    $plus = $this->addMarginToProp($item['margin'], $currencyRate, $currencyShort, $productCurr);
                    $plus = MG::getSetting('outputMargin')=='false' ? '' : $plus;
                    $selected = "";

                    if ($marginStoper == $marginPrice) {

                      // только один раз добавляем цену и выделяем пункт
                      //  (т.к. не исключена возможнось нескольких выделанных пунктов)
                      if ($i == 0) {

                        $selected = ' selected="selected" ';
                        if(strpos($item['margin'], '%')){
                          $marginPriceWithDot = str_replace(',', '.', $item['margin']);
                          $marginPriceWithPercents = $productData['price'] / 100 * floatval($marginPriceWithDot);
                          $marginPrice += round ($marginPriceWithPercents);
                        }
                        else{
                          $marginPrice += (float)$item['margin'];
                        }

                        // запоминаем дефолтное значение
                        $defaultSet[$property['property_id'].'#'.$i] = $value;
                        $isExistSelected = true;

                      };
                    }

                    $itemName = $item['name'];
                    if(isset($item['unit_orig']) && !empty($item['unit_orig'])){
                      $itemName .= ' '.$item['unit_orig'];
                    }
                    // $htmlSelect .= '<option value="'.$property['property_id'].'#'.
                    //   $i.'" '.$selected.'>'.$item['name'].$plus.'</option>';
                    $secctionCartNoDummy[$property['property_id']][$i++] = array(
                      'value' => $value,
                      'name' => $property['name']);
                    $selectPieces[] = array('value' => $property['property_id'].'#'.($i-1), 'selected' => $selected, 'itemName' => $itemName, 'price' => $plus);
                  }
                }

                // $htmlSelect .= '</select></p>';

                if($isExistSelected) {
                  // $html .= $htmlSelect;
                  if (!empty($selectPieces)) {
                    $propPieces[] = array('type' => 'assortment-select', 'name' => $property['name'], 'additional' => $selectPieces);
                  }
                }

                break;
              }

              // Для типа вывода radiobutton :
              if ($property['type_view'] == 'radiobutton') {
                if ($property['data'] == 'null') {
                  break;
                }
                $radioPieces = array();
                // $htmlRadiobutton = '<span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span><br/>';
                $collection = explode('|', $property['value']);
                $i = 0;
                $htmlButtonList = '';

                foreach ($property['data'] as $item) {
                  if($item['active'] == 0) {
                    continue;
                  }

                  $value = '';
                  $value = $item['name']."#".$item['margin']."#";

                  if (empty($item)) {
                    $item = array('name' => $value, 'margin' => 0);
                  }

                  $plus = $this->addMarginToProp($item['margin'], $currencyRate, $currencyShort, $productCurr);
                  $plus = MG::getSetting('outputMargin')=='false' ? '' : $plus;

                  $checked = '';
                  if ($i == 0) {
                    $checked = ' checked="checked" ';

                    // запоминаем дефолтное значение
                    $defaultSet[$property['property_id'].'#'.$i] = $value;

                    if ($marginStoper == $marginPrice) {
                      if (strpos($item['margin'], '%') !== false) {
                        $marginPrice += floatval($item['margin']) * $productData['price_course'] / 100;
                      } else {
                        $marginPrice += floatval($item['margin']);
                      }
                    }
                  }

                  $htmlButtonList .= '<label '.($checked ? 'class="active"': '').'><input type="radio" name="'.
                    $property['property_id'].'#'.$i.'" value="'.$value.'" '.$checked.'>
                     <span class="label-black">'.$item['name'].$plus.'</span></label><br>';

                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']);
                  $radioPieces[] = array('name' => $property['property_id'].'#'.($i-1), 'checked' => $checked, 'value' => $value, 'itemName' => $item['name'], 'price' => $plus);
                }

                if($htmlButtonList) {
                  // $html .= '<p>'.$htmlRadiobutton.$htmlButtonList."</p>";
                  if (!empty($radioPieces)) {
                    $propPieces[] = array('type' => 'assortment-radio', 'name' => $property['name'], 'additional' => $radioPieces);
                  }
                }
                break;
              }

              // Для типа вывода checkbox:
              if ($property['type_view'] == 'checkbox') {

                if ($property['data'] == 'null') {
                  break;
                }
                $checkBoxPieces = array();
                // $html .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span><br/>';

                $i = 0;
                foreach ($property['data'] as $item) {

                  if($item['active'] == 0) {
                    continue;
                  }
                  $value = $item['name']."#".$item['margin']."#";

                  if (empty($value)) {
                    $value = array('name' => $value, 'margin' => 0);
                  }
                  $plus = $this->addMarginToProp($item['margin'], $currencyRate, $currencyShort, $productCurr);
                  $plus = MG::getSetting('outputMargin')=='false' ? '' : $plus;

                  // $html .= '<label><input type="checkbox" name="'.
                  //   $property['property_id'].'#'.$i.'" value="+'.
                  //   $value['margin'].' '.MG::getSetting('currency').'">
                  //   <span class="label-black">'.$item['name'].$plus.' </span></label><br>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']
                  );
                  $value = '+'.(isset($value['margin'])?floatval($value['margin']):0).' '.MG::getSetting('currency');
                  $checkBoxPieces[] = array('name' => $property['property_id'].'#'.($i-1), 'value' => $value, 'itemName' => $item['name'], 'price' => $plus);
                }

                // $html .= "</p>";
                if (!empty($checkBoxPieces)) {
                  $propPieces[] = array('type' => 'assortment-checkBox', 'name' => $property['name'], 'additional' => $checkBoxPieces);
                }
                break;
              }
            }
          case 'file': {
            $marginStoper = $marginPrice;
            if (isset($property['data'][0]) && (!empty($property['data'][0]['name']) || isset($property['data'][0]['name']) && $property['data'][0]['name'] == 0)) {
              if (isset($this->groupProperty[$property['group_id']])) {
                $property['data'][0]['group_prop'] = $this->groupProperty[$property['group_id']];
              }
              $property['data'][0]['priority'] = $property['sort'];
              $property['data'][0]['unit'] = $property['unit'];
              // Для сортировки характеристик в сравнении товаров
              $value = $property['data'];
              //$value = !empty($property['value']) ? $property['value'] : $property['data'];

              $filesProperties[$property['name']] = $value;
              if($printStrProp=='true') {
                // $html .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span> <span class="label-black">'.
                //   htmlspecialchars_decode($property['data'][0]['name']).
                // '</span><span class="unit"> '.$property['unit'].'</span></p>';
                $propPieces[] = array('type' => 'file', 'name' => $property['name'], 'text' => htmlspecialchars_decode($property['data'][0]['name']), 'unit' => $property['unit']);
              }
            }
            break;
          }
          case 'string': {
              $marginStoper = $marginPrice;
              if (isset($property['data'][0]) && (!empty($property['data'][0]['name']) || isset($property['data'][0]['name']) && $property['data'][0]['name'] == 0)) {
                if (isset($this->groupProperty[$property['group_id']])) {
                  $property['data'][0]['group_prop'] = $this->groupProperty[$property['group_id']];
                }
                $property['data'][0]['priority'] = $property['sort'];
                $property['data'][0]['unit'] = $property['unit'];
                // Для сортировки характеристик в сравнении товаров
                $value = $property['data'];
                //$value = !empty($property['value']) ? $property['value'] : $property['data'];

                $stringsProperties[$property['name']] = $value;
                if($printStrProp=='true') {
                  // $html .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span> <span class="label-black">'.
                  //   htmlspecialchars_decode($property['data'][0]['name']).
                  // '</span><span class="unit"> '.$property['unit'].'</span></p>';
                  $propPieces[] = array('type' => 'string', 'name' => $property['name'], 'text' => htmlspecialchars_decode($property['data'][0]['name']), 'unit' => $property['unit']);
                }
              }
              break;
            }

            case 'diapason': {
              if (!empty($property['data'][0]['name'])) {
                $property['data'][0]['group_prop'] = isset($this->groupProperty[$property['group_id']])?$this->groupProperty[$property['group_id']]:0;
                $property['data'][0]['priority'] = $property['sort'];
                $property['data'][0]['unit'] = $property['unit'];
                $tmp = array($property['data'][0]['name'], $property['data'][1]['name']);
                sort($tmp);
                $value = array();
                // от  до
                if(isset($GLOBALS['templateLocale']['filterFrom']) && isset($GLOBALS['templateLocale']['filterTo']) && !empty($GLOBALS['templateLocale']['filterFrom']) && !empty($GLOBALS['templateLocale']['filterTo'])){
                  $diapasonName = $GLOBALS['templateLocale']['filterFrom'].' '.$tmp[0].' '.$GLOBALS['templateLocale']['filterTo'].' '.$tmp[1];
                }else{
                  $diapasonName = $tmp[0].' - '.$tmp[1];
                }
                $value[] = array(
                  'name' => $diapasonName,
                  'group_prop' => $property['data'][0]['group_prop'],
                  'unit' => $property['data'][0]['unit'],
                  'priority' => $property['sort']);
                $stringsProperties[$property['name']] = $value;
              }
              break;
            }

          default:
            if($property['type'] != 'textarea' && $property['type'] != 'color' && $property['type'] != 'size') {
              if (!empty($property['data'])) {
                // $html .= ''.$property['name'].': <span class="label-black">'.$property['data'][0]['name'].'</span>';
                $text='';
                if(isset($property['data'][0]['name'])){
                  $text=$property['data'][0]['name'];
                }
                $propPieces[] = array('type' => 'other', 'name' => $property['name'], 'text' => $text);
              }
            }
            break;
        }
      }
    }

    if ($adminOrder != 'yep' && MG::getSetting('outputMargin') == 'false') {
      foreach ($propPieces as $key => $value) {
        if(is_array($value['additional'])) {
          foreach ($value['additional'] as $key1 => $value1) {
            unset($propPieces[$key]['additional'][$key1]['price']);
          }
        }
      }
    }

    if ($adminOrder == 'yep' || $returnArray) {
      $htmlProperty = $propPieces;
    } else {
      $htmlProperty = MG::layoutManager('layout_htmlproperty', $propPieces);
    }

    // $htmlProperty = $html;

    if (!isset($noneButton)) {$noneButton = null;}
    if (!isset($buyButton)) {$buyButton = null;}
    if (!isset($addHtml)) {$addHtml = null;}
    if (!isset($noneAmount)) {$noneAmount = false;}
    if (!isset($ajax)) {$ajax = true;}
    if (!isset($titleBtn)) {$titleBtn = 'В корзину';}
    if (!isset($blockVariants)) {$blockVariants = '';}
    if (!isset($showCount)) {$showCount = true;}
    if (!isset($action)) {$action = '/catalog';}
    if (!isset($method)) {$method = 'POST';}

    if (!isset($stringsProperties)) {
      $stringsProperties = array();
    } else {
      uasort($stringsProperties, function ($a, $b) {
        if (isset($a['0']['group_prop']['sort'])) {
          $tmpA = $a['0']['group_prop']['sort'];
        } else {
          $tmpA = '';
        }
        if (isset($b['0']['group_prop']['sort'])) {
          $tmpB = $b['0']['group_prop']['sort'];
        } else {
          $tmpB = '';
        }
        return strcmp($tmpA, $tmpB);
      });
    }
    if (!isset($filesProperties)) {
      $filesProperties = array();
    } else {
      uasort($filesProperties, function ($a, $b) {
        if (isset($a['0']['group_prop']['sort'])) {
          $tmpA = $a['0']['group_prop']['sort'];
        } else {
          $tmpA = '';
        }
        if (isset($b['0']['group_prop']['sort'])) {
          $tmpB = $b['0']['group_prop']['sort'];
        } else {
          $tmpB = '';
        }
        return strcmp($tmpA, $tmpB);
      });
    }
    if (!isset($defaultSet)) {$defaultSet = array();}

    if (!isset($productData)) {
      $productData['price_course'] = '';
      $productData['old_price'] = '';
      $productData['activity'] = null;
    }

    $data = array(
     'maxCount' => $maxCount,
     'noneAmount' => $noneAmount,
     'noneButton' => $noneButton,
     'printCompareButton' => $printCompareButton,
     'ajax' => $ajax,
     'buyButton' => $buyButton,
     'classForButton' => $classForButton,
     'titleBtn' => $titleBtn,
     'id' => $id,
     'blockVariants' => $blockVariants,
     'addHtml' => $addHtml,
     'price' => $productData['price_course'],
     'old_price' => $productData['old_price'],
     'activity' => $productData['activity'],
     'parentData' => $param,
     'htmlProperty' => $htmlProperty,
     'showCount' => $showCount,
     'action' => $action,
     'method' => $method,
     'catalogAction' => $catalogAction,
    );

    if ($returnArray || $adminOrder == 'yep') {
      $data['stringsProperties'] = Property::sortPropertyToGroup(array('stringsProperties' => $stringsProperties),true);
      $data['filesProperties'] = Property::sortPropertyToGroup(['filesProperties' => $filesProperties], true, 'filesProperties');
    }

    if (!$returnArray) {
      if ($adminOrder == 'yep') {
        $htmlLayout = MG::adminLayout('adminOrder.php', $data);
      } else {
        $htmlLayout = MG::layoutManager('layout_property', $data);
      }
      if (strpos($htmlLayout, '<form') === false ||
          strpos($htmlLayout, $action) === false ||
          strpos($htmlLayout, $method) === false ||
          strpos($htmlLayout, $catalogAction) === false ||
          strpos($htmlLayout, '</form>') === false
          ) {
        $htmlForm = '<form action="'.SITE.$action.'" method="'.$method.'" class="property-form '.$catalogAction.'" data-product-id='.$id.'>';
        $htmlForm .= $htmlLayout;
        $htmlForm .= '</form>';
      }
      else{
        $htmlForm = $htmlLayout;
      }

      $result = array(
        'html' => $htmlForm,
        'marginPrice' => $marginPrice * $currencyRate,
        'defaultSet' => $defaultSet,  // набор характеристик, которые были бы выбраны по умолчанию при открытии карточки товара.
        'propertyNodummy' => $secctionCartNoDummy,
        'stringsProperties' => $stringsProperties,
        'filesProperties' => $filesProperties,
      );
    } else {
      unset($data['parentData']);
      unset($data['blockVariants']);
      $result = array(
        'propertyData' => $data,
        'marginPrice' => $marginPrice * $currencyRate,
        'defaultSet' => $defaultSet,
        'propertyNodummy' => $secctionCartNoDummy,
        'stringsProperties' => $stringsProperties,
        'filesProperties' => $filesProperties,
      );
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Формирует блок вариантов товара.
   * <code>
   * $model = new Models_Product;
   * $result = $model->getBlockVariants(5);
   * echo $result;
   * </code>
   * @param int $id id товара
   * @param int $cat_id id категории
   * @param string $returnArray вернуть массив (по умолчанию - нет)
   * @return string|array (array - для админки)
   */
  public function getBlockVariants($id, $cat_id = 0, $returnArray = false) {
    $arr = $this->getVariants($id, false, true);

    foreach ($arr as $key => $value) {
      if($value['count'] == 0) {
        $tmp = $value;
        unset($arr[$key]);
        $arr[$tmp['id']] = $tmp;
      }
    }

    $convertCountToHR = MG::getSetting('convertCountToHR');
    foreach ($arr as &$var) {
      $var['count_hr'] = '';
      if (!empty($convertCountToHR) && !MG::isAdmin()) {
        $var['count_hr'] = MG::convertCountToHR($var['count'] );
      }
      $var['price'] = MG::priceCourse($var['price_course']);
    }
    if ($returnArray == 'yep') {
      $html = $arr;
    }
    else{
      $html = MG::layoutManager('layout_variant', array('blockVariants'=>$arr, 'type'=>'product', 'cat_id' => $cat_id));
    }
    return $html;
  }

  /**
   * Формирует массив блоков вариантов товаров на странице каталога.
   * Метод создан для сокращения количества запросов к БД.
   * <code>
   * $model = new Models_Product;
   * $result = $model->getBlocksVariantsToCatalog(array(2,3,4));
   * echo $result;
   * </code>
   * @param int $array массив id товаров
   * @param array $returnArray если true то вернет просто массив без html блоков
   * @param bool $mgadmin если true то вернет данные для админки
   * @return string|array
   */
  public function getBlocksVariantsToCatalog($array, $returnArray = false, $mgadmin = false) {
    $results = array();
    $in = '';
    if (!empty($array)) {
      $in = implode(',', $array);
    }
    $orderBy = 'ORDER BY sort, id';
    $where = '';
    if(MG::getSetting('filterSortVariant') && !$mgadmin) {
      $parts = explode('|',MG::getSetting('filterSortVariant'));
      $parts[0] = $parts[0] == 'count' ? 'count_sort' : $parts[0];
      $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1).', id';
    }
    if(MG::getSetting('showVariantNull')=='false' && !$mgadmin) {
      if(MG::enabledStorage()) {
        $orderBy = ' AND (SELECT round(SUM(ABS(count)),2) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id AND variant_id = pv.id) > 0 '.$orderBy;
      } else {
        $orderBy = ' AND (pv.`count` != 0 OR pv.`count` IS NULL) '.$orderBy;
      }
    }
    $storageCheck = '';
    if(MG::enabledStorage()) {
      $storageCheck = ',(SELECT round(SUM(ABS(count)),2) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id AND variant_id = pv.id) AS count';
    }
    // Получаем все варианты для передранного массива продуктов.
    if ($in) {
      $res = DB::query('
       SELECT pv.*, c.rate,(pv.price_course + pv.price_course * (IFNULL(c.rate,0))) as `price_course`, c.`id` as catId,
       IF( pv.count<0,  1000000, round(pv.count,2) ) AS  `count_sort`
       '.$storageCheck.'
       FROM `'.PREFIX.'product_variant` pv    
         LEFT JOIN `'.PREFIX.'product` as p ON 
           p.id = pv.product_id
         LEFT JOIN `'.PREFIX.'category` as c ON 
           c.id = p.cat_id  
       WHERE pv.product_id  in ('.$in.')
       '.$orderBy);

      if (!empty($res)) {
        while ($variant = DB::fetchAssoc($res)) {
          if (!$returnArray) {

            $variant['old_price'] = MG::convertPrice($variant['old_price']);
            $variant['price_course'] = MG::convertPrice($variant['price_course']);
            $variant['price'] = MG::priceCourse($variant['price_course']);
          }
          $results[$variant['product_id']][] = $variant;
        }
      }
    }
    $productCount = 0;

    if(!$mgadmin) {
      foreach ($results as &$blockVariants) {
        for($i = 0; $i < count($blockVariants); $i++) {
          $productCount += $blockVariants[$i]['count'];
          if($blockVariants[$i]['count'] == 0) {
            $blockVariants[] = $blockVariants[$i];
            unset($blockVariants[$i]);
          }
        }
        $blockVariants = array_values($blockVariants);
      }
    }

    if ($returnArray) {
      return $results;
    }

    sort($array);

    $cash = Storage::get('getBlocksVariantsToCatalog-'.md5(json_encode($array).$productCount.LANG));
    if(!$cash) {
      if (!empty($results)) {
        // Для каждого продукта создаем HTML верстку вариантов.
        foreach ($results as &$blockVariants) {
          $firstVariantCatId = null;
          if (!empty($blockVariants[0]['catId'])) {
            $firstVariantCatId = intval($blockVariants[0]['catId']);
          }
          $html = MG::layoutManager('layout_variant', array('blockVariants'=>$blockVariants, 'type'=>'catalog', 'cat_id' => $firstVariantCatId));
          $blockVariants = $html;
        }
      }
      Storage::save('getBlocksVariantsToCatalog-'.md5(json_encode($array).$productCount.LANG), $results);
      return $results;
    } else {
      return $cash;
    }
  }

  /**
   * Формирует добавочную строку к названию характеристики,
   * в зависимости от наличия наценки и стоимости.
   * <code>
   * $model = new Models_Product;
   * $result = $model->addMarginToProp(250);
   * echo $result;
   * </code>
   * @param float $margin наценка
   * @param float $rate множитель цены
   * @param string $currency валюта
   * @param string $productCurr валюта товара
   * @return string
   */
  public function addMarginToProp($margin, $rate = 1, $currency = false, $productCurr = null) {
    $originalMargin = $margin;
    $currency = $currency ? $currency : MG::getSetting('currencyShopIso');
    $isPercent = false;
    $percentPath = '';
    $symbol = '+';
    if (!empty($margin)) {
      if ($margin < 0) {
        $symbol = '-';
        $margin = $margin * -1;
      }
      if(stripos($originalMargin, '%')){
        $isPercent = true;
        $percentPath = '%';
      }
      if ($productCurr) {
        $margin = MG::convertCustomPrice($margin, $productCurr, 'set');
      }
    }

    $numberPath = ' '.$symbol.MG::numberFormat(floatval($margin) * $rate);
    if($isPercent){
      $result = $numberPath.$percentPath;
    }
    else{
      $result = $numberPath .' '.MG::getSetting('currency');
    }
    return (!empty($margin) || $margin === 0) ? $result  : '';
  }

  /**
   * Отделяет название характеристики от цены название_пункта#стоимость#.
   * Пример входящей строки: "Красный#300#"
   * <code>
   * $model = new Models_Product;
   * $result = $model->parseMarginToProp('Красный#300#');
   * echo $result;
   * </code>
   * @param string $value строка, которую надо распарсить
   * @return array $array массив с разделенными данными, название пункта и стоимость.
   */
  public function parseMarginToProp($value) {
    $array = array();
    $pattern = "/^(.*)#([\d\.\,-]*%?)#$/";
    preg_match($pattern, $value, $matches);
    if (isset($matches[1]) && isset($matches[2])) {
      $array = array('name' => $matches[1], 'margin' => $matches[2]);
    }
    return $array;
  }

  /**
   * Обновление состояния корзины.
   * Используеться для пересчета корзины и обновления цены в карточке товара ajax'ом
   * <code>
   *   $model = new Models_Product;
   *   $model->calcPrice();
   * </code>
   */
  public function calcPrice() {
    $product = $this->getProduct($_POST['inCartProductId']);
    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso');
    $variantId = 0;
    if (isset($_POST['variant'])) {
      $variants = $this->getVariants($_POST['inCartProductId']);

      $variant = $variants[$_POST['variant']];
      $variantId = $_POST['variant'];
      
      if (!empty($variant['image'])) {
        $product['image'] = $variant['image'];
      } else {
        $product['image'] = $product['image_url'];
      }
      $product['id'] = $variant['product_id'];

      $product['price'] = $variant['price'];
      $product['code'] = $variant['code'];
      $product['count'] = $variant['count'];
      $product['old_price'] = $variant['old_price'];
      $product['weight'] = $variant['weight'];
      $product['weightCalc'] = $variant['weightCalc'];
      $product['price_course'] = $variant['price_course'];
      $product['variant'] = $variant['id'];
    } else {
      $product['variant'] = null;
    }

    
    $product['image_url_orig'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'orig') : '';
    $product['image_url_30'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'small') : '';
    $product['image_url_30_2x'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'small_2x') : '';
    $product['image_url_70'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'big') : '';
    $product['image_url_70_2x'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'big_2x') : '';

    $cart = new Models_Cart;
    $property = $cart->createProperty($_POST);
    $product['currency_iso'] = $product['currency_iso']?$product['currency_iso']:$currencyShopIso;
    $product['price'] = $product['price_course'];

    // $tmpPrice = $product['price'];

    $wholesalePrice = MG::setWholePrice(
            $product['price'],
            $product['id'],
            $_POST['amount_input'],
            $variantId,
            $product['currency_iso'],
            (isset($_POST['wholesaleGroup'])?$_POST['wholesaleGroup']:0),
            (isset($_POST['wholesaleForcedCount'])?$_POST['wholesaleForcedCount']:0)
    );
    //$product['price'] = min([$product['price'], $wholesalePrice]);
    $product['price'] = $wholesalePrice;
    // if ($tmpPrice != $product['price']) {
    //   $product['price'] = MG::convertPrice($product['price']);
    // }

    if (floatval($product['old_price']) > floatval($product['price'])) {
      $product['old_price'] = SmalCart::plusPropertyMargin($product['old_price'], $property['propertyReal'], $currencyRate[$product['currency_iso']]);
    }
    $product['price'] = SmalCart::plusPropertyMargin($product['price'], $property['propertyReal'], $currencyRate[$product['currency_iso']]);

    $product['real_price'] = $product['price'];

    // $product['old_price'] *= $currencyRate[$product['currency_iso']];
    $product['remInfo'] = !empty($_POST['remInfo']) ? $_POST['remInfo'] : '';

    // для склада
    $storage = array();
    if(MG::enabledStorage()) {
      $storages = unserialize(stripcslashes(MG::getSetting('storages')));
      foreach ($storages as $item) {
        $count = MG::getProductCountOnStorage(0, $product['id'], $_POST['variant'], $item['id']);
        if($count == -1) {
          $storage[$item['id']] = lang('countMany');
          break;
        }
        if (!isset($storage[$item['id']])) {$storage[$item['id']] = 0;}
        $storage[$item['id']] += $count;
      }
    }

    $wholesalesTable = $productOpFields ='';
    if(MG::get('controller') == 'controllers_product' && USER::access('wholesales') > 0) {
      $res = DB::query('SELECT count, price FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($product['id']).' 
        AND variant_id = '.DB::quoteInt($product['variant']).' AND `group` = '.User::access('wholesales').'  ORDER BY count ASC');
      while ($row = DB::fetchAssoc($res)) {
        $row['price'] = MG::numberFormat(MG::convertCustomPrice($row['price'], $product['currency_iso'], 'set')).' '.MG::getSetting('currency');
        $data['wholesalesData']['data'][] = $row;
      }
      $data['wholesalesData']['type'] = MG::getSetting('wholesalesType');
      $data['wholesalesData']['unit'] = $product['unit']?$product['unit']:$product['category_unit'];
      if (empty($data['wholesalesData']['data']) ) {
          $data['wholesalesData']['data'] = array();
      }
      if (defined('TEMPLATE_INHERIT_FROM')) {
        ob_start();
        component('product/wholesales', $data['wholesalesData']);
        $wholesalesTable .= ob_get_clean();
      } else {
        $wholesalesTable = MG::layoutManager('layout_wholesales_info', $data['wholesalesData']);
      }
    }

    if(MG::get('controller') == 'controllers_product') {
      $data = array();
      $data['id'] = $_POST['inCartProductId'];
      if(!empty($_POST['variant'])) $data['variant'] = $_POST['variant'];
      if (!defined('TEMPLATE_INHERIT_FROM')) {
        $productOpFields = MG::layoutManager('layout_op_product_fields', $data);
      } else {
        ob_start();
        component('product/opfields', $data);
        $productOpFields = ob_get_clean();
      }
    }

    if (defined('NULL_OLD_PRICE') && NULL_OLD_PRICE && $product['price'] > $product['old_price']) {
      $product['old_price'] = 0;
    }

    $product['count_hr'] = '';
    $convertCountToHR = MG::getSetting('convertCountToHR');
    if (!empty($convertCountToHR) && !MG::isAdmin()) {
      $product['count_hr'] = MG::convertCountToHR($product['count']);
    }

    if (!defined('TEMPLATE_INHERIT_FROM')) {
      $count_layout = MG::layoutManager('layout_count_product', $product);
    } else {
      ob_start();
      component('product/count', $product);
      $count_layout = ob_get_clean();
    }

    $buttonMessage = lang('countMsg1') . ' "' . str_replace("'", "&quot;", $product['title']) . '" ' . lang('countMsg2') . ' "' . $product['code'] . '"' . lang('countMsg3');
    $buttonMessage = urlencode($buttonMessage);
    $buttonMessage = SITE . "/feedback?message=" . str_replace(' ', '&#32;', $buttonMessage)."&code=".$product['code'];
    $response = array(
      'status' => 'success',
      'data' => array(
        'title' => $product['title'],
        'price' => MG::numberFormat($product['price']).' <span class="currency">'.MG::getSetting('currency').'</span>',
        'old_price' => MG::numberFormat($product['old_price']).' '.MG::getSetting('currency'),
        'code' => $product['code'],
        'count' => $product['count'],
        'count_hr' => $product['count_hr'],
        'price_wc' => $product['price'],
        'old_price_wc' => $product['old_price'],
        'real_price' => $product['real_price'],
        'weight' => $product['weight'],
        'weightCalc' => $product['weightCalc'],
        'image_orig' => $product['image_url_orig'],
        'image_thumbs' => array(
          '30' => $product['image_url_30'],
          '2x30' => $product['image_url_30_2x'],
          '70' => $product['image_url_70'],
          '2x70' => $product['image_url_70_2x'],
        ),
        'count_layout' => $count_layout,
        'actionInCatalog' => MG::getSetting('actionInCatalog'),
        'buttonMessage' => $buttonMessage,
        'storage' => $storage,
        'wholesalesTable' => $wholesalesTable,
        'productOpFields' => $productOpFields,
      )
    );
    if(MG::getSetting('useWebpImg') == 'true'){
      $newImgThumbs = array();
      foreach($response['data']['image_thumbs'] as $key => $img){
        if(!empty($img)){
          $newImgThumbs[$key] = Webp::changeImg($img);
        }
      }
      $response['data']['image_thumbs'] = $newImgThumbs;
      $response['data']['image_orig'] = Webp::changeImg($response['data']['image_orig']);
    }
    MG::ajaxResponse($response);
  }

  /**
   * Возвращает оригинал и все варианты миниатюр изображений варианта
   * Используется Ajax'ом при изменении варианта в карточке и миникарточке товара
   */
  public function getVariantImages() {
    $product = $this->getProduct($_POST['productId']);

    if (isset($_POST['variant'])) {
      $variants = $this->getVariants($_POST['productId']);
      $variant = $variants[$_POST['variant']];

      $product['image'] = $variant['image'];
      $product['id'] = $variant['product_id'];
    } else {
      $product['variant'] = null;
    }

    $product['image_url_orig'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'orig') : '';
    $product['image_url_30'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'small') : '';
    $product['image_url_30_2x'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'small_2x') : '';
    $product['image_url_70'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'big') : '';
    $product['image_url_70_2x'] = !empty($product['image']) ? mgImageProductPath($product['image'], $product['id'], 'big_2x') : '';
    $response = array(
      'status' => 'success',
      'data' => array(
        'image_orig' => $product['image_url_orig'],
        'image_thumbs' => array(
          '30' => $product['image_url_30'],
          '2x30' => $product['image_url_30_2x'],
          '70' => $product['image_url_70'],
          '2x70' => $product['image_url_70_2x'],
        )
      )
    );
    if(MG::getSetting('useWebpImg') == 'true'){
      $newImgThumbs = array();
      foreach($response['data']['image_thumbs'] as $key => $img){
        if(!empty($img)){
          $newImgThumbs[$key] = Webp::changeImg($img);
        }
      }
      $response['data']['image_thumbs'] = $newImgThumbs;
      $response['data']['image_orig'] = Webp::changeImg($response['data']['image_orig']);
    }
    echo json_encode($response);
    exit;
  }

  /**
   * Возвращает набор вариантов товара.
   * <code>
   * $productId = 25;
   * $model = new Models_Product;
   * $variants = $model->getVariants($productId);
   * viewData($variants);
   * </code>
   * @param int $id id продукта для поиска его вариантов
   * @param string|bool $title_variants название варианта продукта для поиска его вариантов
   * @param bool $sort использовать ли сортировку результатов (из настройки 'filterSortVariant')
   * @return array $array массив с параметрами варианта.
   */
  public function getVariants($id, $title_variants = false, $sort = false, $forceNullVariants = false, $ignoreCatRate = false) {
    $results = array();
    $orderBy = 'ORDER BY sort';
    if(MG::getSetting('filterSortVariant')&& $sort) {
      $parts = explode('|',MG::getSetting('filterSortVariant'));
      $parts[0] = $parts[0] == 'count' ? 'count_sort' : $parts[0];
      $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1).', id';
    }
    // if(MG::getSetting('showVariantNull')=='false' && $sort) {
    //   $orderBy = ' AND pv.`count` != 0 '.$orderBy;
    // }
    if (!$title_variants) {
      $res = DB::query('
      SELECT  pv.*, c.rate,(pv.price_course + pv.price_course *(IFNULL(c.rate,0))) as `price_course`, pv.`price_course` as orig_price_course,
      p.currency_iso, IF( pv.count<0,  1000000, round(pv.count,2) ) AS `count_sort`, c.weight_unit as category_weightUnit, p.weight_unit as product_weightUnit
      FROM `'.PREFIX.'product_variant` pv   
        LEFT JOIN `'.PREFIX.'product` as p ON 
          p.id = pv.product_id
        LEFT JOIN `'.PREFIX.'category` as c ON 
          c.id = p.cat_id       
      WHERE pv.product_id = '.DB::quote($id).' '.$orderBy);
    } else {
      $res = DB::query('
        SELECT  pv.*
        FROM `'.PREFIX.'product_variant` pv    
        WHERE pv.product_id = '.DB::quote($id).'  and pv.title_variant = '.DB::quote($title_variants).' '.$orderBy);
    }

    if (!empty($res)) {
      while ($variant = DB::fetchAssoc($res)) {
        if ($ignoreCatRate) {
          $variant['price_course'] = $variant['orig_price_course'];
        }
        // MG::loadLocaleData($variant['id'], LANG, 'product_variant', $variant);
        // подгражем количество товара на складах
        if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
        $variant['price_course'] = MG::convertPrice($variant['price_course']);
        $variant['old_price'] = MG::convertPrice($variant['old_price']);

        if (!empty($variant['product_weightUnit'])) {
          $weightUnit = $variant['product_weightUnit'];
        } elseif(!empty($variant['category_weightUnit'])) {
          $weightUnit = $variant['category_weightUnit'];
        } else {
          $weightUnit = 'kg';
        }
        if ($weightUnit != 'kg') {
          $variant['weightCalc'] = MG::getWeightUnit('convert', ['from'=>'kg','to'=>$weightUnit,'value'=>$variant['weight']]);
        } else {
          $variant['weightCalc'] = $variant['weight'];
        }
        unset($variant['product_weightUnit']);
        unset($variant['category_weightUnit']);
        $results[$variant['id']] = $variant;
      }
    }

    if(MG::enabledStorage()) {
      // NEW
      $variantsIds = [];
      foreach ($results as $key => $value) {
        $variantsIds[] = $key;
      }
      $storage = null;
      if (!empty($_POST['storage'])) {
        $storage = trim($_POST['storage']);
      }
      if (!$storage || $storage === 'all') {
        foreach ($results as &$variant) {
          $variant['count'] = $this->getProductStorageTotalCount($id, $variant['id']);
        }
      } else {
        $variantsCountData = $this->getStoragesCountsByVariantsIds($variantsIds, $storage);
        if (empty($variantsCountData)) {
          foreach ($results as &$variant) {
            $variant['count'] = 0;
          }
        } else {
          foreach ($variantsCountData as $variantId => $variantCountData) {
            if ($storage) {
              foreach ($variantCountData as $variantStorage => $count) {
                if (strval($storage) === strval($variantStorage)) {
                  $results[$variantId]['count'] = $count;
                  break;
                }
              }
              continue;
            }
            $allCount = 0;
            foreach ($variantCountData as $count) {
              if (floatval($count) === floatval(-1)) {
                $allCount = -1;
                break;
              }
              $allCount += $count;
            }
            $results[$variantId]['count'] = $allCount;
          }
        }
      }
      // OLD
      // $ids = $tmpData = array();
      // foreach ($results as $key => $value) {
      //   $ids[] = $key;
      //   $results[$key]['count'] = 0;
      // }
      // $ids = array_unique($ids);
      // if(!empty($_POST['storage'])) $this->storage = $_POST['storage'];
      // if($this->storage == 'all' || !$this->storage) {
      //   $storageWhere = '';
      // } else {
      //   $storageWhere = 'AND storage = '.DB::quote($this->storage);
      // }
      // // $storage = ',(SELECT IF(SUM(count) != 0, SUM(count), 0) FROM '.PREFIX.'product_on_storage WHERE product_id = '.DB::quoteInt($id).'
      // //   AND variant_id = pv.id '.$storageWhere.') AS count ';
      // $sql='SELECT count, variant_id FROM '.PREFIX.'product_on_storage WHERE variant_id IN ('.DB::quoteIN($ids).') '.$storageWhere;
      // $res = DB::query($sql);
      // while($row = DB::fetchAssoc($res)) {
      //   $tmpData[$row['variant_id']][] = $row['count'];
      // }
      // foreach ($results as $key => $value) {
      //   // $tmpData[$key];
      //   if (!empty($tmpData[$key]) && is_array($tmpData[$key])) {
      //     foreach ($tmpData[$key] as $ivalue) {
      //       if($results[$key]['count'] == -1) break;
      //       if($ivalue == -1) {
      //         $results[$key]['count'] = -1;
      //         break;
      //       }
      //       $results[$key]['count'] += $ivalue;
      //     }
      //   }
      // }
    }


    if(MG::getSetting('showVariantNull')=='false' && $sort && !$forceNullVariants) {
      foreach ($results as $key => $value) {
        if($results[$key]['count'] == 0) {
          unset($results[$key]);
        }
      }
    }

    // загрузка локалей для вариантов
    if((LANG != '')&&(LANG != 'LANG')&&(LANG != 'default')) {
      if(!empty($results)) {
        $idsVar = array();
        foreach ($results as $key => $value) {
          $idsVar[] = $key;
        }
        $res = DB::query('SELECT `id_ent`, `field`, `text` FROM '.PREFIX.'locales WHERE 
          `id_ent` IN ('.DB::quoteIN($idsVar).') AND `table` = \'product_variant\' AND locale = '.DB::quote(LANG));
        while($row = DB::fetchAssoc($res)) {
          $localeData[$row['id_ent']][$row['field']] = $row['text'];
        }
        foreach ($results as $key => $value) {
          foreach ($value as $key2 => $item) {
            if(!empty($localeData[$key][$key2])) $results[$key][$key2] = $localeData[$key][$key2];
          }
        }
      }
    }

    // for($i = 0; $i < count($item['variants']); $i++) {
    //       if($item['variants'][$i]['count'] == 0) {
    //         $item['variants'][] = $item['variants'][$i];
    //         unset($item['variants'][$i]);
    //       }
    //     }
    //     $items['catalogItems'][$k]['variants'] = array_values($item['variants']);

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $results, $args);
  }

  /**
   * Возвращает массив id характеристик товара, которые не нужно выводить в карточке.
   * <code>
   * $result = Models_Product::noPrintProperty($productId);
   * viewData($result);
   * </code>
   * @return array $array - массив с id.
   */
  public function noPrintProperty() {
    $results = array();

    $res = DB::query('
      SELECT  `id`
      FROM `'.PREFIX.'property`     
      WHERE `activity` = 0');

    while ($row = DB::fetchAssoc($res)) {
      $results[] = $row['id'];
    }

    return $results;
  }

  /**
   * Возвращает HTML блок связанных товаров.
   * <code>
   * $args = array(
   *  'product' => 'CN182,В-500-1', // артикулы связанных товаров
   *  'category' => '2,4' // ID связанных категорий
   * );
   * $model = new Models_Product;
   * $result = $model->createRelatedForm($args);
   * echo $result;
   * </code>
   * @param array $args массив с данными о товарах
   * @param string $title заголовок блока
   * @param string $layout используемый лэйаут
   * @return string
   */
  public function createRelatedForm($args,$title='С этим товаром покупают', $layout = 'layout_related') {
    $result = '';
    if($args && (!empty($args['product']) || !empty($args['category']))) {
      $data['title'] = $title;

      $catalogModel = new Models_Catalog;

      // получение товаров по кодам товаров
      $catalogItemsByCodes = [];
      $stringRelated = ' null';
      $sortRelated = [];
      if (!empty($args['product'])) {
        foreach (explode(',',$args['product']) as $item) {
          $stringRelated .= ','.DB::quote($item);
          $sortRelated[$item] = $item;
        }
        $stringRelated = substr($stringRelated, 1);
        $whereParts = [
          'p.`activity`',
          'p.`code` IN ('.$stringRelated.')',
        ];
        $where = implode(' AND ', $whereParts);
        $catalogDataByCodes = $catalogModel->getListByUserFilter(100, $where);
        $catalogItemsByCodes = $catalogDataByCodes['catalogItems'];
      }

      // получение товаров по категориям
      $catalogItemsByCats = [];
      if (!empty($args['category'])) {
        $stringRelatedCat = ' null';
        foreach (explode(',',$args['category']) as $item) {
          $stringRelatedCat .= ','.DB::quote($item);
        }
        $whereParts = [
          'p.`activity`',
          'p.`cat_id` IN ('.$stringRelatedCat.')',
        ];
        if (!empty($args['exclude'])) {
          $whereParts[] = 'p.`id` != '.DB::quoteInt($args['exclude']);
        }
        $where = implode(' AND ', $whereParts);
        $stringRelatedCat = substr($stringRelatedCat, 1);
        $catalogDataByCats = $catalogModel->getListByUserFilter(100, $where);
        $catalogItemsByCats = $catalogDataByCats['catalogItems'];
        shuffle($catalogItemsByCats);
      }

      $catalogItems = array_merge($catalogItemsByCodes, $catalogItemsByCats);
      $catalogItemsIds = [];
      if (empty($catalogItems)) {
        $catalogItems = [];
        $sortRelated = [];
      } else {
        foreach ($catalogItems as $catalogItem) {
          if ($catalogItem['id']) {
            $catalogItemsIds[] = $catalogItem['id'];
          }
        }
      }

      $catalogItems = MG::loadCountFromStorageToCatalog($catalogItems);
      $catalogItems = MG::loadWholeSalesToCatalog($catalogItems);

      if (!empty($catalogItemsIds)) {
        $blocksVariants = $this->getBlocksVariantsToCatalog($catalogItemsIds, defined('TEMPLATE_INHERIT_FROM'));
      } else {
        $blocksVariants = null;
      }

      $blockedProp = $this->noPrintProperty();
      if (empty($blockedProp)) {
        $blockedProp = [];
      }

      foreach ($catalogItems as $k => $catalogItem) {
        if (!empty($catalogItem['variants'])) {
          for($i = 0; $i < count($catalogItem['variants']); $i++) {
            if($catalogItem['variants'][$i]['count'] == 0) {
              $catalogItem['variants'][] = $catalogItem['variants'][$i];
              unset($catalogItem['variants'][$i]);
            }
          }
          $catalogItems[$k]['variants'] = array_values($catalogItem['variants']);
        } else {
          $catalogItems[$k]['variants'] = array();
        }
        
        $imagesUrl = explode("|", $catalogItem['image_url']);
        $catalogItems[$k]["image_url"] = "";

        if (!empty($imagesUrl[0])) {
          $catalogItems[$k]["image_url"] = $imagesUrl[0];
        }

        $catalogItems[$k]['title'] = MG::modalEditor('catalog', $catalogItem['title'], 'edit', $catalogItem["id"]);

        if (
          (
            $catalogItems[$k]['count'] == 0 &&
            empty($catalogItems[$k]['variants'])
          ) ||
          (
            !empty($catalogItems[$k]['variants']) &&
            $catalogItems[$k]['variants'][0]['count'] == 0
          ) ||
          MG::getSetting('actionInCatalog')=='false'
        ) {
          if (!defined('TEMPLATE_INHERIT_FROM')) {
            $buyButton = MG::layoutManager('layout_btn_more', $catalogItems[$k]);
          } else {
            $buyButton = 'more';
          }
        } else {
          if (!defined('TEMPLATE_INHERIT_FROM')) {
            $buyButton = MG::layoutManager('layout_btn_buy', $catalogItems[$k]);
          } else {
            $buyButton = 'buy';
          }
        }
        
        if(MG::getSetting('showMainImgVar') == 'true') {
          if(isset($catalogItem['variants']) && $catalogItem['variants'][0]['image'] != '') {
            $img = explode('/', $catalogItems[$k]['images_product'][0]);
            $img = end($img);
            $catalogItems[$k]["image_url"] = $catalogItems[$k]['images_product'][0] = str_replace($img, $catalogItem['variants'][0]['image'], $catalogItems[$k]['images_product'][0]);
          }
        }

        // Легкая форма без характеристик.
        $liteFormData = $this->createPropertyForm($param = array(
          'id' => $catalogItem['id'],
          'maxCount' => $catalogItem['count'],
          'productUserFields' => null,
          'action' => "/catalog",
          'method' => "POST",
          'ajax' => true,
          'blockedProp' => $blockedProp,
          'noneAmount' => true,
          'titleBtn' => "В корзину",
          'blockVariants' => isset($blocksVariants[$catalogItem['id']])?$blocksVariants[$catalogItem['id']]:'',
          'buyButton' => $buyButton
        ), 'nope', defined('TEMPLATE_INHERIT_FROM'));

        if (!defined('TEMPLATE_INHERIT_FROM')) {
          $catalogItems[$k]['liteFormData'] = $liteFormData['html'];
          $buyButton = $catalogItems[$k]['liteFormData'];
          $catalogItems[$k]['buyButton'] = $buyButton;
        } else {
          $catalogItems[$k]['liteFormData'] = $liteFormData['propertyData'];
          $catalogItems[$k]['buyButton'] = $buyButton;
        }
      }

      foreach ($catalogItems as $key => $catalogItem) {
        $catalogItemCode = $catalogItem['code'];
        if (!empty($catalogItem['variants'])) {
          $catalogItem["price"] = MG::numberFormat($catalogItem['variants'][0]["price_course"]);
          $catalogItem["old_price"] = $catalogItem['variants'][0]["old_price"];
          $catalogItem["count"] = $catalogItem['variants'][0]["count"];
          $catalogItem["code"] = $catalogItem['variants'][0]["code"];
          $catalogItem["weight"] = $catalogItem['variants'][0]["weight"];
          $catalogItem["price_course"] = $catalogItem['variants'][0]["price_course"];
          $catalogItem["variant_exist"] = $catalogItem['variants'][0]["id"];
        }
        if (defined('NULL_OLD_PRICE') && NULL_OLD_PRICE && (MG::numberDeFormat($catalogItem["price"]) > MG::numberDeFormat($catalogItem["old_price"]))) {
          $catalogItem["old_price"] = 0;
        }
        $sortRelated[$catalogItemCode] = $catalogItem;
      }

      foreach ($sortRelated as $srProductIndex => $srProduct) {
        if (!is_array($srProduct)) {
          unset($sortRelated[$srProductIndex]);
        }
      }

      $data['products'] = $sortRelated;
      $data['currency'] = MG::getSetting('currency');


      $result = '';
      if (!defined('TEMPLATE_INHERIT_FROM')) {
        $result = MG::layoutManager($layout, $data);
      } else {
        $result = $data;
      }

    };


    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Конвертирование стоимости товаров по заданному курсу.
   * <code>
   * $model = new Models_Product;
   * $model->convertToIso('USD', array(2, 3, 4));
   * </code>
   * @param string $iso валюта в которую будет производиться конвертация.
   * @param array $productsId массив с id продуктов.
   */
  public function convertToIso($iso,$productsId=array()) {

    //$productsId = implode(',', $productsId);
    //if(empty($productsId)) {$productsId = 0;};

    // вычислим соотношение валют имеющихся в базе товаров к выбранной для замены
    // вычисление производится на основе имеющихся данных по отношению в  валюте магазина
    $currencyShort = MG::getSetting('currencyShort');
    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso');

    // если есть непривязанные к валютам товары, то  назначаем им текущую валюту магазина
    DB::query('
      UPDATE `'.PREFIX.'product` SET 
            `currency_iso` = '.DB::quote($currencyShopIso).'
      WHERE `currency_iso` =  "" AND `id` IN ('.DB::quoteIN($productsId).')');
    DB::query('
      UPDATE `'.PREFIX.'product_variant` SET 
            `currency_iso` = '.DB::quote($currencyShopIso).'
      WHERE `currency_iso` =  "" AND `id` IN ('.DB::quoteIN($productsId).')');

    // запоминаем базовое соотношение курсов к валюте магазина
    $rateBaseArray = $currencyRate;
    $rateBase = $currencyRate[$iso];
    // создаем новое соотношение валют по отношению в выбранной для конвертации
    foreach ($currencyRate as $key => $value) {
        if(!empty($rateBase)) {
          $currencyRate[$key] = $value / $rateBase;
        }
    }
    $currencyRate[$iso] = 1;

    // пересчитываем цену, старую цену и цену по курсу для выбранных товаров
    foreach ($currencyRate as $key => $rate) {
      DB::query('
      UPDATE `'.PREFIX.'product`
      SET `price`= ROUND(`price`*'.DB::quoteFloat($rate,TRUE).',2),
          `price_course`= ROUND(`price`*'.DB::quoteFloat(($rateBaseArray[$iso]?$rateBaseArray[$iso]:1),TRUE).',2)
      WHERE currency_iso = '.DB::quote($key).' AND `id` IN ('.DB::quoteIN($productsId).')');

      // также и в вариантах
      DB::query('
      UPDATE `'.PREFIX.'product_variant`
       SET `price`= ROUND(`price`*'.DB::quoteFloat($rate,TRUE).',2),
          `price_course`= ROUND(`price`*'.DB::quoteFloat(($rateBaseArray[$iso]?$rateBaseArray[$iso]:1),TRUE).',2)
      WHERE currency_iso = '.DB::quote($key).' AND `product_id` IN ('.DB::quoteIN($productsId).')');

      // пересчитываем оптовые цены товару
      DB::query('UPDATE '.PREFIX.'wholesales_sys AS h
        LEFT JOIN '.PREFIX.'product AS p ON p.id = h.product_id
        SET h.price = ROUND(h.price * '.DB::quoteFloat($rate,TRUE).',2) 
        WHERE p.id IN ('.DB::quoteIN($productsId).') AND p.currency_iso = '.DB::quote($key));

    }

    // всем выбранным продуктам изменяем ISO
     DB::query('
      UPDATE `'.PREFIX.'product`
      SET `currency_iso` = '.DB::quote($iso).'
      WHERE `id` IN ('.DB::quoteIN($productsId).')');

     DB::query('
      UPDATE `'.PREFIX.'product_variant`
      SET `currency_iso` = '.DB::quote($iso).'
      WHERE `product_id` IN ('.DB::quoteIN($productsId).')');
  }

   /**
   * Обновления цены товаров в соответствии с курсом валюты.
   * <code>
   * $model = new Models_Product;
   * $model->updatePriceCourse('USD', array(2, 3, 4));
   * </code>
   * @param string $iso валюта в которую будет производиться конвертация.
   * @param array $listId массив с id продуктов.
   */
  public function updatePriceCourse($iso,$listId = array()) {

     if(empty($listId)) {$listId = 0;}
     else{
      if (is_array($listId) || is_object($listId)){
        foreach ($listId as $key => $value) {
          $listId[$key] = intval($value);
        }
      }
       //$listId = implode(',', $listId);
     }

    // вычислим соотношение валют имеющихся в базе товаров к выбранной для замены
    // вычисление производится на основе имеющихся данных по отношению в  валюте магазина
    $currencyShort = MG::getSetting('currencyShort');
    $currencyRate = unserialize(stripcslashes(MG::getOption('currencyRate')));
    $currencyShopIso = MG::getOption('currencyShopIso');
    $recalcForeignCurrencyOldPrice = MG::getSetting('recalcForeignCurrencyOldPrice');


    $rate = $currencyRate[$iso];
    
    if($rate != 0) {
      DB::query('
        UPDATE `'.PREFIX.'wholesales_sys` 
          SET `price`= ROUND(`price`/'.DB::quote((float)$rate,TRUE).',2) 
          WHERE `product_id` IN ('.DB::quoteIN($listId).')');
    }

    $where = '';
    if(!empty($listId)) {
      $where =' AND `id` IN ('.DB::quoteIN($listId).')';
    }

    $whereVariant = '';
    if(!empty($listId)) {
      $whereVariant =' AND `product_id` IN ('.DB::quoteIN($listId).')';
    }

    DB::query('
     UPDATE `'.PREFIX.'product` SET 
           `currency_iso` = '.DB::quote($currencyShopIso).'
     WHERE `currency_iso` = "" '.$where);

    foreach ($currencyRate as $key => $value) {
        if(!empty($rate)) {
          $currencyRate[$key] = $value / $rate;
        }
    }
    $currencyRate[$iso] = 1;
    //Обновление старой цены при изменении курса валют
    foreach ($currencyRate as $key => $rate) {
      $sql = 'SELECT `id`, `price`, `old_price`, `price_course`, `currency_iso` FROM `'.PREFIX.'product` WHERE currency_iso = '.DB::quote($key).' AND `old_price` > 0 '.$where;
      $res = DB::query($sql);
      if ($recalcForeignCurrencyOldPrice === 'true') {
        while($row = DB::fetchAssoc($res)) {
          if($row['currency_iso'] == $currencyShopIso) continue;
          $priceRateCoursOld = $row['price_course']/$row['price'];
          if($priceRateCoursOld != 0){
            $oldPriceInCurrens = round($row['old_price']/$priceRateCoursOld, 2);
            $newOldPrice = round($oldPriceInCurrens*$rate,2);
            DB::query('UPDATE `'.PREFIX.'product` SET `old_price` = '.DB::quote($newOldPrice).' WHERE `id` = '.DB::quote($row['id']));
          }
        }
      }

      DB::query('
      UPDATE `'.PREFIX.'product` 
        SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)          
      WHERE currency_iso = '.DB::quote($key).' '.$where);

      DB::query('
      UPDATE `'.PREFIX.'product_variant` 
        SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)         
      WHERE currency_iso = '.DB::quote($key).' '.$whereVariant);
    }
  }

   /**
   * Удаляет картинки вариантов товара.
   * <code>
   * $model = new Models_Product;
   * $model->deleteImagesVariant(4);
   * </code>
   * @param int $productId ID товара
   * @return bool
   */
  public function deleteImagesVariant($productId) {
    $imagesArray = array();
    // Удаляем картинки продукта из базы.
    $res = DB::query('
      SELECT image
      FROM `'.PREFIX.'product_variant` 
      WHERE product_id = '.DB::quote($productId) );
    while($row = DB::fetchAssoc($res)) {
      $imagesArray[] = $row['image'];
    }
    $this->deleteImagesProduct($imagesArray, $productId);
    return true;
  }

  /**
   * Подготавливает названия изображений товара.
   * <code>
   *   $model = new Models_Product;
   *   $res = $model->prepareImageName($product);
   *   viewData($res);
   * </code>
   * @param array $product массив с товаром
   * @return array
   */
  public function prepareImageName($product) {
    $result = $product;

    $images = explode("|", $result['image_url']);
    foreach($images as $cell=>$image) {
      $pos = strpos($image, 'no-img');
      if($pos || $pos === 0) {
        unset($images[$cell]);
      } else {
        $images[$cell] = basename($image);
      }
    }
    $result['image_url'] = implode('|', $images);

    if (isset($result['variants']) && is_array($result['variants'])) {
      foreach($result['variants'] as $cell=>$variant) {
        $images = array();
        if(empty($variant['image'])) {
          continue;
        }

        $pos = strpos($variant['image'], 'no-img');
        if($pos || $pos === 0) {
          unset($result['variants'][$cell]['image']);
        } else {
          if (strpos($variant['image'], DS.'thumbs'.DS)) {
            $variant['image'] = str_replace(array('thumbs'.DS.'30_', 'thumbs'.DS.'70_'), '', $variant['image']);
          }

          $images[] = basename($variant['image']);
        }
        $result['variants'][$cell]['image'] = implode('|', $images);
      }
    }

    return $result;
  }

  /**
   * Обнуляет остатки товара и всех его вариантов по всем складам.
   * 
   * <code>
   *   $productId = 1;
   *   $model = new Models_Product;
   *   $model->setZeroStock($productId);
   * </code>
   *
   * @param int $productId - id товара
   * 
   * @return void
   */
  public function setZeroStock($productId) {
    if (MG::enabledStorage()) {
      $setZeroStockSql = 'UPDATE `'.PREFIX.'product_on_storage` '.
        'SET `count` = 0 '.
        'WHERE `product_id` = '.DB::quoteInt($productId);
      DB::query($setZeroStockSql);
    }
    $setProductZeroStockSql = 'UPDATE `'.PREFIX.'product` '.
      'SET `count` = 0, storage_count = 0 '.
      'WHERE `id` = '.DB::quoteInt($productId);
    $setVariantsZeroStockSql = 'UPDATE `'.PREFIX.'product_variant` '.
      'SET `count` = 0 '.
      'WHERE `product_id` = '.DB::quoteInt($productId);
    DB::query($setProductZeroStockSql);
    DB::query($setVariantsZeroStockSql);
  }

  /**
   * Копирует изображения товара в новую структуру хранения.
   *
   * @param array $images - массив изображений
   * @param int $productId - id товара
   * @param string $path - папка в которой лежат исходные изображения
   * @param bool $removeOld - флаг удаления изображений из папки $path после копирования в новое место
   * @return void
   */
  public function movingProductImage($images, $productId, $path='uploads', $removeOld = true) {
    if(empty($images)) {
      return false;
    }
    $ds = DS;
    $dir = floor($productId/100).'00';
    @mkdir(SITE_DIR.'uploads'.$ds.'product', 0755);
    @mkdir(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir, 0755);
    @mkdir(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId, 0755);
    @mkdir(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs', 0755);
    
    // получаем актуальные изображения для товара и его вариантов
    $variantsImgs = array();
    $sqlFindVariantsImgs = 'SELECT `image` FROM `'.PREFIX.'product_variant` WHERE `product_id` ='.DB::quoteInt($productId);
    $res = DB::query($sqlFindVariantsImgs);
    while($row = DB::fetchAssoc($res)) {
      $variantsImgs[] = $row['image'];
    }
    
    $productImages = array();
    $sqlProductImages = 'SELECT `image_url` FROM `'.PREFIX.'product` WHERE `id` ='.DB::quoteInt($productId);
    $res = DB::query($sqlProductImages);

    if($row = DB::fetchAssoc($res)) {
      $productImages =  explode('|', $row['image_url']);
    }

    foreach($images as $cell=>$image) {
      $pos = strpos($image, '_-_time_-_');

      if ($pos) {
        if (MG::getSetting('addDateToImg') == 'true') {
          $tmp1 = explode('_-_time_-_', $image);
          $tmp2 = strrpos($tmp1[1], '.');
          $tmp1[0] = date("_Y-m-d_H-i-s", substr_replace($tmp1[0], '.', 10, 0));
          $imageClear = substr($tmp1[1], 0, $tmp2).$tmp1[0].substr($tmp1[1], $tmp2);
        }
        else{
          $imageClear = substr($image, ($pos+10));
        }
      }
      else{
        $imageClear = $image;
      }

      // удаляем лишние файлы в папке которые уже не относятся к товару или вариантам
       if (file_exists(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds)) {
        foreach (glob(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'*') as $productFile) {
          $fileName = basename($productFile);
          if(!in_array($fileName, $variantsImgs) && !in_array($fileName, $productImages) && $fileName != 'thumbs'){
              unlink($productFile);
          }
          elseif($fileName === 'thumbs'){
            foreach (glob(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$fileName.$ds.'*') as $productFileThumbs) {
              $fileName = preg_replace(['/^2x_30_/', '/^2x_70_/', '/^30_/', '/^70_/'],'',basename($productFileThumbs));          
              if(!in_array($fileName, $variantsImgs) && !in_array($fileName, $productImages)){
                unlink($productFileThumbs);
              }
            }
          }
        }
      }

      if(
        is_file($path.$ds.$image) &&
        copy($path.$ds.$image, SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$imageClear)
      ) {
        // Поворачиваем скопированное изображение в соответствии с meta-информацией (exif)
        $fullName = explode('.', $imageClear);
        $ext = array_pop($fullName);
        $exifRotateImagesExts = [
          'jpeg',
          'jpg',
          'png'
        ];
        if (
          MG::getSetting('exifRotate') === 'true' &&
          in_array(strtolower($ext), $exifRotateImagesExts)
        ) {
          $imagePath = SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$imageClear;
          if ($ext === 'png') {
            $imageForRotation = imagecreatefrompng($imagePath);
            imageAlphaBlending($imageForRotation, true);
            $imageForRotation = Upload::rotateImageByExif($imageForRotation, $imagePath);
            imageSaveAlpha($imageForRotation, true);
            imagepng($imageForRotation, $imagePath);
          } else {
            $imageForRotation = imagecreatefromjpeg($imagePath);
            $imageForRotation = Upload::rotateImageByExif($imageForRotation, $imagePath);
            imagejpeg($imageForRotation, $imagePath);
          }
        }

        $productImages[] = $imageClear;
        Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$imageClear);
        if(
          is_file($path.$ds.'thumbs'.$ds.'30_'.$image) &&
          copy($path.$ds.'thumbs'.$ds.'30_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$imageClear) && $removeOld
        ) {
          Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$imageClear);
          unlink($path.$ds.'thumbs'.$ds.'30_'.$image);
        }
        
        if(
          is_file($path.$ds.'thumbs'.$ds.'2x_30_'.$image) &&
          copy($path.$ds.'thumbs'.$ds.'2x_30_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'2x_30_'.$imageClear) && $removeOld
        ) {
          Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'2x_30_'.$imageClear);
          unlink($path.$ds.'thumbs'.$ds.'2x_30_'.$image);
        }

        if(
          is_file($path.$ds.'thumbs'.$ds.'70_'.$image) &&
          copy($path.$ds.'thumbs'.$ds.'70_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$imageClear) && $removeOld
        ) {
          Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$imageClear);
          unlink($path.$ds.'thumbs'.$ds.'70_'.$image);
        }

        if(
          is_file($path.$ds.'thumbs'.$ds.'2x_70_'.$image) &&
          copy($path.$ds.'thumbs'.$ds.'2x_70_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'2x_70_'.$imageClear) && $removeOld
        ) {
          Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'2x_70_'.$imageClear);
          unlink($path.$ds.'thumbs'.$ds.'2x_70_'.$image);
        }

        if($removeOld) {
          unlink($path.$ds.$image);
        }
      }elseif(
        is_file('uploads'.$ds.$image) &&
        copy('uploads'.$ds.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$imageClear)
      ) {
        Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$imageClear);
        if(
          is_file('uploads'.$ds.'thumbs'.$ds.'30_'.$image) &&
          copy('uploads'.$ds.'thumbs'.$ds.'30_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$imageClear) && $removeOld
        ) {
          Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$imageClear);
          unlink('uploads'.$ds.'thumbs'.$ds.'30_'.$image);
        }

        if(
          is_file('uploads'.$ds.'thumbs'.$ds.'70_'.$image) &&
          copy('uploads'.$ds.'thumbs'.$ds.'70_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$imageClear) && $removeOld
        ) {
          Webp::createWebpImg(SITE_DIR.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$imageClear);
          unlink('uploads'.$ds.'thumbs'.$ds.'70_'.$image);
        }

        if($removeOld) {
          unlink('uploads'.$ds.$image);
        }
      }
    }
  }

  /**
   * Пересчитывает все остатки по складам по всем товарам и вариантам. Записывает количество в таблицу товаров для оптимизации запросов.
   *
   * @param int $offset - смещение выборки товаров (с какой позиции начинать).
   * 
   * @return array - информация о статусе перерасчёта. ['total' => 100, 'processed' => 10] если обработаны не все товары, ['completed' => 1], если все.
   */
  public function recalculateStoragesAll($offset = 0, $noTimeLimit = false) {
    if (!MG::enabledStorage()) {
      return false;
    }
    $startTime = microtime(true);
    $maxExecTime = 20;
    $productsChunkSize = 100;

    $totalCount = $this->getProductsTotalCount();
    while ($offset < $totalCount) {
        $productsIds = [];
        $productsIdsSql = 'SELECT `id` '.
          'FROM `'.PREFIX.'product` '.
          'LIMIT '.intval($offset).', '.intval($productsChunkSize);
        $productsIdsResult = DB::query($productsIdsSql);
        while ($productsIdsRow = DB::fetchAssoc($productsIdsResult)) {
          $productsIds[] = intval($productsIdsRow['id']);
        }
        if (empty($productsIds)) {
          return false;
        }
        $this->recalculateStorages($productsIds);
        $offset += count($productsIds);
        $execTime = microtime(true) - $startTime;
        if ($execTime > $maxExecTime && !$noTimeLimit) {
          $result = [
            'total' => $totalCount,
            'processed' => $offset,
          ];
          return $result;
        }
    }
    $result = [
      'complete' => 1
    ];
    return $result;
  }

  /**
   * Пересчитывает все остатки по складам для конкретного товара и всех его вариантов. Записывает количество в таблицу товаров для оптимизации запросов.
   *
   * @param int $productId - id товара
   * 
   * @return bool
   */
  public function recalculateStoragesById($productId) {
    $productsIds = [$productId];
    $result = $this->recalculateStorages($productsIds);
    return $result;
  }

  /**
   * Пересчитывает все остатки по складам для всех переданных товаров и их вариантов. Записывает количество в таблицу товаров для оптимизации запросов.
   *
   * @param array $allProductsIds - массив идентификаторов товаров.
   * 
   * @return bool
   */
  public function recalculateStorages($allProductsIds = []) {
    $countableProductsIds = $allProductsIds;
    $infProductsIds = [];
    $infProductsIdsSql = 'SELECT `product_id` '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `id` IN ('.DB::quoteIN($allProductsIds).') '.
        'AND `count` = -1 '.
      'GROUP BY `product_id`';
    $infProductsIdsResult = DB::query($infProductsIdsSql);
    while ($infProductsIdsRow = DB::fetchAssoc($infProductsIdsResult)) {
      $infProductsIds[] = intval($infProductsIdsRow['id']);
    }

    if ($infProductsIds) {
      $updateInfProductsCountSql = 'UPDATE `'.PREFIX.'product` '.
        'SET `storage_count` = -1 '.
        'WHERE `id` IN ('.DB::quoteIN($infProductsIds).')';
      DB::query($updateInfProductsCountSql);

      $countableProductsIds = array_diff($allProductsIds, $infProductsIds);
    }

    if ($countableProductsIds) {
      $productsTotalCountSql = 'SELECT '.
          '`product_id`, '.
          'SUM(IFNULL(`count`, 0)) as total_count '.
        'FROM `'.PREFIX.'product_on_storage` '.
        'WHERE `product_id` IN ('.DB::quoteIN($countableProductsIds).') '.
        'GROUP BY `product_id`';
      $productsTotalCountResult = DB::query($productsTotalCountSql);
      while ($productsTotalCountRow = DB::fetchAssoc($productsTotalCountResult)) {
        $productId = intval($productsTotalCountRow['product_id']);
        $productTotalCount = floatval($productsTotalCountRow['total_count']);
        $setProductTotalCountSql = 'UPDATE `'.PREFIX.'product` '.
          'SET `storage_count` = '.DB::quoteFloat($productTotalCount).' '.
          'WHERE `id` = '.DB::quoteInt($productId);
        DB::query($setProductTotalCountSql);
      }
    }

    return true;
  }

  /**
   * Возвращает общее количество всех остатков всех товаров и вариантов по всем складам.
   * 
   * <code>
   *   $model = new Models_Product;
   *   $allStocksCount = $model->getProductsTotalCount();
   * </code>
   * 
   * @return int
   */
  public function getProductsTotalCount() {
    $totalCount = 0;
    $totalCountSql = 'SELECT COUNT(`id`) as total_count '.
      'FROM `'.PREFIX.'product`';
    $totalCountResult = DB::query($totalCountSql);
    if ($totalCountRow = DB::fetchAssoc($totalCountResult)) {
      $totalCount = intval($totalCountRow['total_count']);
    }
    return $totalCount;
  }

  /**
   * Возвращает идентификатор товара по его внешнему идентификатору (1c_id).
   * 
   * <code>
   *   $externalProductId = 'some1cid';
   *   $model = new Models_Product;
   *   $productId = $model->getProductIdByExternalId($externalProductId);
   * </code>
   * 
   * @param string $externalId - внешний идентификатор товара
   * 
   * @return int
   */
  public function getProductIdByExternalId($externalId) {
    $productId = null;
    $productIdSql = 'SELECT `id` '.
      'FROM `'.PREFIX.'product` '.
      'WHERE `1c_id` = '.DB::quote($externalId);
    $productIdResult = DB::query($productIdSql);
    if ($productIdRow = DB::fetchAssoc($productIdResult)) {
      $productId = intval($productIdRow['id']);
    }
    return $productId;
  }


  /**
   * Устанавливает количество товара или его варианта на конкретном складе. Метод обновляет текущую запись о количестве, если она имеется или добавляет новую.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $variantId = 2;
   *   $newCount = 71;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $updateCountResult = $model->updateStorageCount($productId, $storageId, $newCount, $variantId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param string $storageId - идентификатор склада
   * @param float $count = количество
   * @param int $variantId - идентификатор варианта
   * 
   * @return bool
   */
  public function updateStorageCount($productId, $storageId, $count, $variantId = null) {
    $result = false;
    $storageCountIdSql = 'SELECT `id` '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `product_id` = '.DB::quoteInt($productId).' '.
        'AND `storage` = '.DB::quote($storageId);

    $variantWhereClause = 'AND `variant_id` = '.DB::quoteInt($variantId);
    if (!$variantId) {
      $variantWhereClause = 'AND (`variant_id` = 0 OR ISNULL(`variant_id`))';
    }

    $storageCountIdSql .= ' '.$variantWhereClause;
    
    $storageCountIdResult = DB::query($storageCountIdSql);
    if ($storageCountIdRow = DB::fetchAssoc($storageCountIdResult)) {
      $storageCountId = intval($storageCountIdRow['id']);
      $updateCountSql = 'UPDATE `'.PREFIX.'product_on_storage` '.
        'SET `count` = '.DB::quoteFloat($count).' '.
        'WHERE `id` = '.DB::quoteInt($storageCountId);
      $result = DB::query($updateCountSql);
    } else {
      $countData = [
        'NULL',
        DB::quote($storageId),
        DB::quoteInt($productId),
        DB::quoteInt($variantId),
        DB::quoteFloat($count),
      ];
      $createCountSql = 'INSERT INTO `'.PREFIX.'product_on_storage` '.
        'VALUES ('.implode(', ', $countData).')';
      $result = DB::query($createCountSql);
    }
    return $result;
  }

  /**
   * Устанавливает количество товара или его варианта на конкретном складе. Метод удаляет все существующие записи для переданного товара, варианта и склада, а затем добавляет новую.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $variantId = 2;
   *   $newCount = 71;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $setNewCountResult = $model->setNewStorageCount($productId, $storageId, $newCount, $variantId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param string $storageId - идентификатор склада
   * @param float $count = количество
   * @param int $variantId - идентификатор варианта
   * 
   * @return bool
   */
  public function setNewStorageCount($productId, $storageId, $count, $variantId = null) {
    if ($variantId) {
      if (!$this->deleteStorageRecordsVariantOnly($productId, $variantId, $storageId)) {
        return false;
      }
    } else {
      if (!$this->deleteStorageRecordsProductOnly($productId, $storageId)) {
        return false;
      }
    }
    if (!$this->addStorageRecord($productId, $storageId, $count, $variantId)) {
      return false;
    }
    return true;
  }

  /**
   * Метод удаляет все записи о количестве переданного товара и всех его вариантов на переданном складе.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $deleteAllRecordsResult = $model->deleteStorageRecordsAll($productId, $storageId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param string $storageId - идентификатор склада
   * 
   * @return bool
   */
  public function deleteStorageRecordsAll($productId, $storageId) {
    $whereParts = [
      '`product_id` = '.DB::quoteInt($productId),
    ];
    if ($storageId !== 'all') {
      $whereParts[] = '`storage` = '.DB::quote($storageId);
    }
    $whereClause = implode(' AND ', $whereParts);
    $result = $this->deleteStorageRecords($whereClause);
    return $result;
  }

  /**
   * Метод удаляет все записи о количестве переданного товара без учёта его вариантов на переданном складе.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $deleteProductRecordsOnlyResult = $model->deleteStorageRecordsProductOnly($productId, $storageId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param string $storageId - идентификатор склада
   * 
   * @return bool
   */
  public function deleteStorageRecordsProductOnly($productId, $storageId) {
    $whereParts = [
      '`product_id` = '.DB::quoteInt($productId),
      '(`variant_id` = 0 OR `variant_id` IS NULL)'
    ];
    if ($storageId !== 'all') {
      $whereParts[] = '`storage` = '.DB::quote($storageId);
    }
    $whereClause = implode(' AND ', $whereParts);
    $result = $this->deleteStorageRecords($whereClause);
    return $result;
  }

  /**
   * Метод удаляет все записи о количестве переданного варианта переданного товара на переданном складе.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $variantId = 2;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $deleteVariantsRecordsOnlyResult = $model->deleteStorageRecordsVariantOnly($productId, $variantId, $storageId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param int $variantId - идентификатор варианта товара
   * @param string $storageId - идентификатор склада
   * 
   * @return bool
   */
  public function deleteStorageRecordsVariantOnly($productId, $variantId, $storageId) {
    $whereParts = [
      '`product_id` = '.DB::quoteInt($productId),
      '`variant_id` = '.DB::quoteInt($variantId),
    ];
    if ($storageId !== 'all') {
      $whereParts[] = '`storage` = '.DB::quote($storageId);
    }
    $whereClause = implode(' AND ', $whereParts);
    $result = $this->deleteStorageRecords($whereClause);
    return $result;
  }


  /**
   * Метод удаляет все записи о количестве переданного варианта переданного товара на переданном складе.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $variantId = 2;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $deleteVariantRecordsOnlyResult = $model->deleteStorageRecordsVariantOnly($productId, $variantId, $storageId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param int $variantId - идентификатор варианта товара
   * @param string $storageId - идентификатор склада
   * 
   * @return bool
   */
  public function deleteStorageRecordsAllVariants($productId, $storageId) {
    $whereParts = [
      '`product_id` = '.DB::quoteInt($productId),
      '`variant_id` != 0',
      '`variant_id` IS NOT NULL',
    ];
    if ($storageId !== 'all') {
      $whereParts[] = '`storage` = '.DB::quote($storageId);
    }
    $whereClause = implode(' AND ', $whereParts);
    $result = $this->deleteStorageRecords($whereClause);
    return $result;
  }

  /**
   * Метод удаляет все записи о количестве вариантов переданного товара без учёта самого товара на переданном складе.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $deleteVariantsRecordsOnlyResult = $model->deleteStorageRecordsVariantOnly($productId, $storageId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param string $storageId - идентификатор склада
   * 
   * @return bool
   */
  private function deleteStorageRecords($whereClause) {
    $deleteStorageRecordSql = 'DELETE '.
      'FROM `'.PREFIX.'product_on_storage`';
    if ($whereClause) {
      $deleteStorageRecordSql .= ' WHERE '.$whereClause;
    }
    $result = DB::query($deleteStorageRecordSql);
    return $result;
  }

  /**
   * Добавляет новую запись о количестве товара/варианта в таблицу остатков по складам.
   * 
   * <code>
   *   $model = new Models_Product;
   * 
   *   $productId = 17;
   *   $variantId = 2;
   *   $newCount = 71;
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $createNewCountRecordResult = $model->addStorageRecord($productId, $storageId, $newCount, $variantId);
   * </code>
   * 
   * @param int $productId - идентификатор товара
   * @param string $storageId - идентификатор склада
   * @param float $count = количество
   * @param int $variantId - идентификатор варианта
   * 
   * @return bool
   */
  public function addStorageRecord($productId, $storageId, $count, $variantId = null) {
    $addStorageRecordValues = [
      'NULL',
      DB::quote($storageId),
      DB::quoteInt($productId),
      DB::quoteInt($variantId),
      DB::quoteFloat($count),
    ];
    $addStorageRecordSql = 'INSERT INTO `'.PREFIX.'product_on_storage` '.
      'VALUES ('.implode(', ', $addStorageRecordValues).')';
    $result = DB::query($addStorageRecordSql);
    return $result;
  }

  /**
   * Полностью очищает таблицу остатков товаров и вариантов по складам (TRUNCATE).
   * 
   * <code>
   *   $model = new Models_Product;
   *   $clearStocksTableResult = $model->clearStoragesTable();
   * </code>
   * 
   * @return bool
   */
  public function clearStoragesTable() {
    $truncateStoragesTableSql = 'TRUNCATE `'.PREFIX.'product_on_storage`';
    $result = DB::query($truncateStoragesTableSql);
    return $result;
  }

  /**
   * Удаляет все записи о количестве товаров и вариантах на определенном складе.
   * 
   * <code>
   *   $outdatedStorageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $model = new Models_Product;
   *   $clearStocksTableResult = $model->destroyStorageStocks($outdatedStorageId);
   * </code>
   * 
   * @param string $storage - идентификатор склада
   * 
   * @return bool
   */
  public function destroyStorageStocks($storage) {
    $destroyStorageStockSql = 'DELETE '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `storage` = '.DB::quote($storage);
    $result = DB::query($destroyStorageStockSql);
    return $result;
  }

  /**
   * Возвращает количество товара/варианта на определенном складе.
   * 
   * <code>
   *   $storageId = 'th330305562b6b754a754707361933eo';
   *   $productId = 3;
   *   $variantId = 1;
   * 
   *   $model = new Models_Product;
   *   $variantCount = $model->getProductStorageCount($storageId, $productId, $variantId);
   * </code>
   * 
   * @param string $storageId - идентификатор склада
   * @param int $productId - идентификтаор товара
   * @param int $variantId - идентификатор варианта
   * 
   * @return int
   */
  public function getProductStorageCount($storageId, $productId, $variantId = 0) {
    $result = 0;
    $storageCountSql = 'SELECT `count` '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `storage` = '.DB::quote($storageId).' '.
        'AND `product_id` = '.DB::quoteInt($productId).' '.
        'AND `variant_id` = '.DB::quoteInt($variantId);

    $storageCountResult = DB::query($storageCountSql);
    if ($storageCountRow = DB::fetchAssoc($storageCountResult)) {
      $result = floatval($storageCountRow['count']);
    }

    return $result;
  }

  /**
   * Возвращает идентификатор варианта по его артикулу.
   * 
   * <code>
   *   $variantVendorCode = 'CN31-2';
   *   $model = new Models_Product;
   *   $variantId = $model->getVariantByCode($variantVendoreCode);
   * </code>
   * 
   * @param string $code - артикул варианта
   * 
   * @return int | null
   */
  public function getVariantIdByCode($code) {
    $variantId = null;
    $variantIdSql = 'SELECT `id` '.
      'FROM `'.PREFIX.'product_variant` '.
      'WHERE `code` = '.DB::quote($code);
    $variantIdResult = DB::query($variantIdSql);
    if ($variantIdRow = DB::fetchAssoc($variantIdResult)) {
      $variantId = intval($variantIdRow['id']);
    }
    return $variantId;
  }

  /**
   * Возвращает данные об остатках для переданного товара и варианта на складах, а так же склад списания остатков в зависимости от настроек списания.
   * 
   * <code>
   *   $productId = 1;
   *   $variantId = 7;
   * 
   *   $model = new Models_Product;
   *   $storagesInfo = $model->getProductStorageData($productId, $variantId);
   * 
   *   $stocksOnStorages = $storagesInfo['storageArray'];
   *   $writeOffStorage = $storagesInfo['storage'];
   * 
   *   $firstStorageId = $stocksOnStorages[0]['storage'];
   *   $firstStorageCount = $stocksOnStorages[0]['count'];
   * </code>
   * 
   * @param int $productId - идентификтаор товара
   * @param int $variantId - идентификатор варианта
   * 
   * @return array
   */
  public function getProductStorageData($productId, $variantId = 0) {
    $storageArray = [];
    $storagesData = $this->getProductStoragesData($productId, $variantId);
    if (empty($storagesData)) {
      $storagesData = [];
    }
    foreach ($storagesData as $storageId => $storageCount) {
      $storageArray[] = [
        'storage' => $storageId,
        'count' => $storageCount,
      ];
    }
    $storagesSettings = MG::getSetting('storages_settings', true);
    $writeOffProc = intval($storagesSettings['writeOffProc']);
    $storage = strval(array_keys($storagesData)[0]);
    if ($writeOffProc === 2) {
      $storage = $storagesSettings['storagesOrderArray'][0]['storagId'];
    }
    if ($writeOffProc === 3) {
      $storage = $storagesSettings['mainStorage'];
    }
    $result = [
      'storageArray' => $storageArray,
      'storage' => $storage,
    ];
    return $result;
  }

  /**
   * Возвращает для переданного товара и варианта остатки по всем складам.
   * 
   * <code>
   *   $productId = 1;
   *   $variantId = 7;
   * 
   *   $model = new Models_Product;
   *   $stocksOnStorages = $model->getProductStoragesData($productId, $variantId);
   * 
   *   $firstStorageId = $stocksOnStorages[0]['storage'];
   *   $firstStorageCount = $stocksOnStorages[0]['count'];
   * </code>
   * 
   * @param int $productId - идентификтаор товара
   * @param int $variantId - идентификатор варианта
   * 
   * @return array
   */
  public function getProductStoragesData($productId, $variantId = 0) {
    $result = [];
    $storagesCountSql = 'SELECT `storage`, `count` '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `product_id` = '.DB::quoteInt($productId);
    
    $variantWhereClause = 'AND `variant_id` = '.DB::quoteInt($variantId);
    if (!$variantId) {
      $variantWhereClause = 'AND (`variant_id` = 0 OR ISNULL(`variant_id`))';
    }

    $storagesCountSql .= ' '.$variantWhereClause;

    $storagesCountResult = DB::query($storagesCountSql);
    while ($storagesCountRow = DB::fetchAssoc($storagesCountResult)) {
      $storageId = strval($storagesCountRow['storage']);
      $count = floatval($storagesCountRow['count']);
      $result[$storageId] = $count;
    }
    return $result;
  }

  /**
   * Возвращает сумму остатков по всем складам для переданного товара и варианта.
   * 
   * <code>
   *   $productId = 1;
   *   $variantId = 7;
   * 
   *   $model = new Models_Product;
   *   $totalStocksCount = $model->getProductStorageTotalCount($productId, $variantId);
   * </code>
   * 
   * @param int $productId - идентификтаор товара
   * @param int $variantId - идентификатор варианта
   * 
   * @return int
   */
  public function getProductStorageTotalCount($productId, $variantId = null) {
    $result = null;
    $checkProductInfSql = 'SELECT `id` '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `product_id` = '.DB::quoteInt($productId).' '.
        'AND `variant_id` = '.DB::quoteInt($variantId).' '.
        'AND `count` = -1';
    $checkProductInfResult = DB::query($checkProductInfSql);
    if (DB::fetchAssoc($checkProductInfResult)) {
      $result = -1;
      return $result;
    }

    $totalCountSql = 'SELECT SUM(`count`) as total_count '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `product_id` = '.DB::quoteInt($productId).' '.
        'AND `variant_id` = '.DB::quoteInt($variantId);
    $totalCountResult = DB::query($totalCountSql);
    if ($totalCountRow = DB::fetchAssoc($totalCountResult)) {
      $result = floatval($totalCountRow['total_count']);
    }
    return $result;
  }

  /**
   * Метод списывает количество товара со склада. Используется при оформлении заказа. Если в $storage передать 'all', то списание произойдёт со склада, автоматически выбранного согласно настройкам.
   * 
   * <code>
   *   $productInfo = [
   *     'id' => 1,
   *     'variantId' => 3,
   *     'count' => 4,
   *   ];
   *   $storageId = 'th330305562b6b754a754707361933eo';
   * 
   *   $model = new Models_Product;
   *   $decreasingResult = $model->decreaseCountProductOnStorage($productInfo, $storageId);
   * </code>
   * 
   * @param array $product - информация о товаре
   * @param int $storage - идентификатор склада или "all" для автоматического выбора
   * 
   * @return bool
   */
  public function decreaseCountProductOnStorage($product, $storage = 'all') {
    $productId = intval($product['id']);
    $variantId = intval($product['variantId']);
    $count = floatval($product['count']);

    // Если передан склад, с которого нужно списать
    if (
      $storage &&
      $storage !== 'all'
    ) {
      // Сначала проверяем сколько товара доступно на этом складе
      $countOnCurrentStorage = $this->getProductStorageCount($storage, $productId, $variantId);
      // Если на переданном складе достаточно товара, то списываем с него
      if ($countOnCurrentStorage >= $count) {
        $this->updateStorageCount($productId, $storage, $countOnCurrentStorage - $count, $variantId);
        $this->recalculateStorages($productId);
        $this->resetLastUpdate($product);
        return true;
      }
    }

    // Если не передан склад, с которого нужно списать, то тут уже ориентируемся на настройку порядка списываения со складов
    $storagesSettings = MG::getSetting('storages_settings', true);
    $writeOffOrder = 1;
    if (!empty($storagesSettings['writeOffProc'])) {
      $writeOffOrder = intval($storagesSettings['writeOffProc']);
    }

    switch ($writeOffOrder) {
      case 1: // С первого, на котором есть все
        break;
      case 2: // В заданном порядке
        break;
      case 3: // С основого
        break;
      default:
        return false;
    }


    return false;
  }

  /**
   * @deprecated
   */
  public function orderDecreaseProductStorageCountOld($storage = 'all', $adminCartItems = null) {
    if (!MG::enabledStorage()) {
      return false;
    }
    $cartItems = $_SESSION['cart'];
    if ($adminCartItems) {
      $cartItems = $adminCartItems;
    }

    foreach ($cartItems as $cartItem) {
      $this->decreaseCountProductOnStorage($cartItem);
    }

    return $cartItems;

    // $cartItems = $_SESSION['cart'];
    // $storagesSettings = MG::getSetting('storages_settings', true);

    // foreach ($cartItems as $cartItem) {
    //   $productId = intval($cartItem['id']);
    //   $variantId = intval($cartItem['variantId']);
    //   $count = floatval($cartItem['count']);

    //   // Если используется конкретный склад
    //   if (
    //     $storage &&
    //     $storage !== 'all'
    //   ) {
    //     // Проверяем сколько товара на этом складе
    //     $currentStorageCount = $this->getProductStorageCount($storage, $productId, $variantId);
    //     // Если товар в полном объёме есть на этом складе
    //     if ($currentStorageCount >= $count) {
    //       // То списываем с него
    //       $this->decreaseCountProductOnStorage($cartItem, $storage);
    //     }
    //   }
    // }
  }

  public function orderDecreaseProductStorageCount($storage = null, $cartItems = null) {
    // Если заказ из админки, то товары передаются в cartItems,
    // если из публички, то они находятся в сессии

    if (!empty($cartItems)) {
      foreach ($cartItems as $cartItem) {
        $productId = intval($cartItem['id']);
        $variantId = intval($cartItem['variant_id']);
        $storagesData = $cartItem['storage_id'];
        foreach ($storagesData as $storageId => $count) {
          $realCount = $this->getProductStorageCount($storageId, $productId, $variantId);
          $count = min($count, $realCount);
          $this->decreaseCountProductOnStorage([
            'id' => $productId,
            'variantId' => $variantId,
            'count' => $count,
          ], $storageId);
        }
      }
      return $cartItems;
    }

    $cartItems = $_SESSION['cart'];
    if (!$cartItems) {
      return false;
    }

    if ($storage === 'all') {
      $storage = null;
    }

    // Если передан склад, с которого нужно списать
    if ($storage) {
      $fromExactStorage = true;
      // То перебираем товары и проверяем, хватит ли количества на складах, чтобы списать
      foreach ($cartItems as $product) {
        $productId = intval($product['id']);
        $variantId = intval($product['variantId']);
        $count = floatval($product['count']);
        $storageCount = $this->getProductStorageCount($storage, $productId, $variantId);
        if ($storageCount >= 0 && $storageCount < $count) {
          $fromExactStorage = false;
          break;
        }
      }
      // Если количества точно хватает, то списываем эти товары со складов
      // И добавляем каждому товару запись о том, с какого склада сколько его было списано
      if ($fromExactStorage) {
        foreach ($cartItems as &$product) {
          $productId = intval($product['id']);
          $variantId = intval($product['variantId']);
          $count = floatval($product['count']);
          $storageCount = $this->getProductStorageCount($storage, $productId, $variantId);
          $newCount = $storageCount - $count;
          if (floatval($storageCount) === floatval(-1)) {
            $newCount = -1;
          }
          $this->updateStorageCount($productId, $storage, $newCount, $variantId);
          $this->recalculateStoragesById($productId);
          $product['storage_id'] = [
            $storage => $count,
          ];
        }
        return $cartItems;
      }
    }

    // Настройки складов
    $storagesSettings = MG::getSetting('storages_settings', true);

    $writeOffAlg = intval($storagesSettings['writeOffProc']); // В каком порядке списывать со складов
    $writeOffWithoutMainAlg = intval($storagesSettings['storagesAlgorithmWithoutMain']); // В каком порядке списывать со складов, если нет на основном
    $storagesOrder = $storagesSettings['storagesOrderArray']; // Сортировка складов
    $mainStorage = $storagesSettings['mainStorage']; // Основной склад
    $useOneStorage = MG::getSetting('useOneStorage') === 'true'; // Настройка, запрещающая списывать один заказ с разных складов

    if ($storagesOrder) {
      // Дополнительно отсортировываем массив сортировки складов
      usort($storagesOrder, function ($storageDataA, $storageDataB) {
        $sortA = intval($storageDataA['storageNumver']);
        $sortB = intval($storageDataB['storageNumber']);
        if ($sortA === $sortB) {
          return 0;
        }
        if ($sortA < $sortB) {
          return -1;
        }
        return 1;
      });
    }

    // Этот массив хранит информацию о том, какой товар, в каком объёме, с какого склада можно списать
    $writeOffData = [];

    // Список доступных складов
    $availableStorages = [];
    $storagesDatas = unserialize(stripcslashes(MG::getSetting('storages')));
    foreach ($storagesDatas as $storageData) {
      $storageId = $storageData['id'];
      $availableStorages[$storageId] = $storageId;
    }

    // Сортируем список доступных складов в соответсвии с настройками складов
    $sortedStorages = [];
    switch ($writeOffAlg) {
      case 1: // С первого у которого есть все
        $sortedStorages = $availableStorages; // Эта соритровка работает непосредственно в цикле по товарам, поэтому здесь ничего делать не нужно
        break; 
      case 2: // В заданном порядке
        // Тут в качестве сортировки выступает конкретный список складов
        foreach ($storagesOrder as $storageData) {
          $storageId = $storageData['storagId'];
          $sortedStorages[$storageId] = $storageId;
        }
        break;
      case 3: // С основого
        // Если остальные склады упорядочить в заданном порядке
        if ($writeOffWithoutMainAlg === 2) {
          // В список отсортированных складов сначала записываем основной склад
          $sortedStorages[$mainStorage] = $mainStorage;
          // А затем все остальные из заданного порядка
          foreach ($storagesOrder as $storageData) {
            $storageId = $storageData['storagId'];
            // Кроме основного склада
            if ($storageId === $mainStorage) {
              continue;
            }
            $sortedStorages[$storageId] = $storageId;
          }
        // Если остальные склады упорядочить по "Первый на котором есть все"
        } else {
          // То в начала записываем основной склад, а остальные "как есть",
          // они будут отсортированы уже в процессе перебирания товаров
          $sortedStorages[$mainStorage] = $mainStorage;
          foreach ($availableStorages as $storageId) {
            if ($storageId === $mainStorage) {
              continue;
            }
            $sortedStorages[$storageId] = $storageId;
          }
        }
        break;
    }

    // Перебираем все товары и определяем с какого склада сколько товара можно списать
    foreach ($cartItems as $product) {
      $productId = intval($product['id']);
      $variantId = intval($product['variantId']);
      if (empty($product['variantId']) && isset($product['variant_id'])) {
        $variantId = intval($product['variant_id']);
      }
      $inOrderCount = floatval($product['count']);

      // В первую очередь проверяем а хватит ли вообще количества товара на складах
      $totalCount = $this->getProductStorageTotalCount($productId, $variantId);
      // Если на всех складах вместе взятых меньше товара, чем нужно
      if ($totalCount >= 0 && $totalCount < $inOrderCount) {
        // То такой заказ оформить невозможно
        return false;
      }

      foreach ($sortedStorages as $storageId) {
        $storageCount = $this->getProductStorageCount($storageId, $productId, $variantId);
        // Если на складе недостаточно товара, то убираем этот склад из доступных
        if (!$storageCount || ($storageCount >= 0 && $storageCount < $inOrderCount)) {
          unset($availableStorages[$storageId]);
        }
        $writeOffData[$productId][$variantId][$storageId] = $storageCount;
      }
    }

    // Тут будет храниться идентификатор склада, с которого можно списать все товары в заказе
    // Выбран он будет из всех складов с которых можно списать все товары заказа
    // по сортировке первого товара
    $selectedAvailableStorage = null;
    foreach ($writeOffData as $writeOffProduct) {
      foreach ($writeOffProduct as $writeOffVariant) {
        foreach ($writeOffVariant as $writeOffStorage => $writeOffCount) {
          if (in_array($writeOffStorage, $availableStorages)) {
            $selectedAvailableStorage = $writeOffStorage;
            break 3;
          }
        }
      }
    }

    // Если включена настройка списывать только с одного склада
    if ($useOneStorage) {
      // Если нет склада, с которого можно списать все товары заказа
      if (!$selectedAvailableStorage) {
        // То такой заказ невозможен
        return false;
      }
      // А если есть, то перебираем все товары и все варинты и списываем с этого склада
      foreach ($cartItems as &$product) {
        $productId = intval($product['id']);
        $variantId = intval($product['variantId']);
        if (empty($product['variantId']) && isset($product['variant_id'])) {
          $variantId = intval($product['variant_id']);
        }
        $inOrderCount = floatval($product['count']);
        $storageCount = floatval($writeOffData[$productId][$variantId][$selectedAvailableStorage]);
        $newCount = $storageCount - $inOrderCount;
        if ($storageCount < 0) {
          $newCount = -1;
        }

        $this->updateStorageCount($productId, $selectedAvailableStorage, $newCount, $variantId);
        $this->recalculateStoragesById($productId);
        $product['storage_id'][$selectedAvailableStorage] = $inOrderCount;
      }
      // Списание успешно завершено, возвращаем список товаров
      return $cartItems;
    }

    // Если списывать товары можно с разных складов
    // То снова ориентируемся на настройки приоритетов списания со складов
    switch($writeOffAlg) {
      case 1:  // С первого у которого есть все
        // Если есть склад, с которого можно списать все товары заказа
        if ($selectedAvailableStorage) {
          // То списываем все товары с него
          foreach ($cartItems as &$product) {
            $productId = intval($product['id']);
            $variantId = intval($product['variantId']);
            if (empty($product['variantId']) && isset($product['variant_id'])) {
              $variantId = intval($product['variant_id']);
            }
            $inOrderCount = floatval($product['count']);
            $storageCount = floatval($writeOffData[$productId][$variantId][$selectedAvailableStorage]);
            $newCount = $storageCount - $inOrderCount;
            if ($storageCount < 0) {
              $newCount = -1;
            }
    
            $this->updateStorageCount($productId, $selectedAvailableStorage, $newCount, $variantId);
            $this->recalculateStoragesById($productId);
            $product['storage_id'][$selectedAvailableStorage] = $inOrderCount;
          }
          // Списание успешно завершено, возвращаем список товаров
          return $cartItems;
          // Если такого склада нет
        } else {
          // То списываем просто по порядку с какого склада сколько можно
          foreach ($cartItems as &$product) {
            $productId = intval($product['id']);
            $variantId = intval($product['variantId']);
            if (empty($product['variantId']) && isset($product['variant_id'])) {
              $variantId = intval($product['variant_id']);
            }
            $inOrderCount = floatval($product['count']);
            $complete = 0;
            foreach ($writeOffData[$productId][$variantId] as $storageId => $storageCount) {
              $toWriteOffCount = min($inOrderCount - $complete, $storageCount);
              $newCount = $storageCount - $toWriteOffCount;
              if ($storageCount < 0) {
                $toWriteOffCount = $inOrderCount;
                $newCount = -1;
              } else {
                $this->updateStorageCount($productId, $storageId, $newCount, $variantId);
                $product['storage_id'][$storageId] = $toWriteOffCount;
              }
              $complete += $toWriteOffCount;
              if (floatval($complete) >= floatval($inOrderCount)) {
                $this->recalculateStoragesById($productId);
                continue 2;
              }
            }
          }
        }
        break;
      case 2: // В заданном порядке
        // Просто перебираем заготовленный массив
        foreach ($cartItems as &$product) {
          $productId = intval($product['id']);
          $variantId = intval($product['variantId']);
          if (empty($product['variantId']) && isset($product['variant_id'])) {
            $variantId = intval($product['variant_id']);
          }
          $inOrderCount = floatval($product['count']);
          $complete = 0;
          foreach ($writeOffData[$productId][$variantId] as $storageId => $storageCount) {
            $toWriteOffCount = min($inOrderCount - $complete, $storageCount);
            $newCount = $storageCount - $toWriteOffCount;
            if ($storageCount < 0) {
              $toWriteOffCount = $inOrderCount;
              $newCount = -1;
            } else {
              $this->updateStorageCount($productId, $storageId, $newCount, $variantId);
              $product['storage_id'][$storageId] = $toWriteOffCount;
            }
            $complete += $toWriteOffCount;
            if (floatval($complete) >= floatval($inOrderCount)) {
              $this->recalculateStoragesById($productId);
              continue 2;
            }
          }
        }
        break;
      case 3: // С основого
        // И снова перебираем товары
        foreach ($cartItems as &$product) {
          $productId = intval($product['id']);
          $variantId = intval($product['variantId']);
          if (empty($product['variantId']) && isset($product['variant_id'])) {
            $variantId = intval($product['variant_id']);
          }
          $inOrderCount = floatval($product['count']);

          // Выковыриваем основной склад из общего списка
          // И списываем с него сколько можно
          $mainStorageCount = $writeOffData[$productId][$variantId][$mainStorage];
          if ($mainStorageCount < 0) {
            $product['storage_id'][$mainStorage] = $inOrderCount;
            continue;
          }
          $complete = 0;
          $toWriteOffCount = min($inOrderCount - $complete, $mainStorageCount);
          $newCount = $mainStorageCount - $toWriteOffCount;
          if ($newCount !== $mainStorageCount) {
            $this->updateStorageCount($productId, $mainStorage, $newCount, $variantId);
            $product['storage_id'][$mainStorage] = $toWriteOffCount;
          }
          $complete += $toWriteOffCount;

          // Если списания с основного склада не хватило
          if ($complete < $inOrderCount) {
            // Если установлена сортировка списывать с первого где есть все товары
            // И такой склад есть
            if ($writeOffWithoutMainAlg !== 2 && $selectedAvailableStorage) {
              // То списываем с него
              $selectedAvailableStorageCount = $writeOffData[$productId][$variantId][$selectedAvailableStorage];
              if ($selectedAvailableStorageCount >= 0) {
                $toWriteOffCount = min($inOrderCount - $complete, $selectedAvailableStorageCount);
                $newCount = $selectedAvailableStorageCount - $toWriteOffCount;
                if ($newCount !== $selectedAvailableStorageCount) {
                  $this->updateStorageCount($productId, $selectedAvailableStorage, $newCount, $variantId);
                  $product['storage_id'][$selectedAvailableStorage] = $toWriteOffCount;
                }
                $complete += $toWriteOffCount;
              } else {
                $product['storage_id'][$selectedAvailableStorage] = $inOrderCount;
              }
            }
            // Если и после списывания с "общего" склада не хватило
            if ($complete < $inOrderCount) {
              // То списываем с оставшихся складов
              foreach ($writeOffData[$productId][$variantId] as $storageId => $storageCount) {
                // Только основной и выбранный общий склады уже пропускаем
                if (in_array($storageId, [$mainStorage, $selectedAvailableStorage])) {
                  continue;
                }
                $toWriteOffCount = min($inOrderCount - $complete, $storageCount);
                $newCount = $storageCount - $toWriteOffCount;
                if ($storageCount < 0) {
                  $toWriteOffCount = $inOrderCount;
                  $newCount = -1;
                } else {
                  $this->updateStorageCount($productId, $storageId, $newCount, $variantId);
                  $product['storage_id'][$storageId] = $toWriteOffCount;
                }
                $complete += $toWriteOffCount;
                if (floatval($complete) >= floatval($inOrderCount)) {
                  $this->recalculateStoragesById($productId);
                  continue 2;
                }
              }
            }
            $this->recalculateStoragesById($productId);
          }
        }
        break;
    }
    return $cartItems;
  }

  public function resetLastUpdate($product) {
    $productId = intval($product['id']);
    $variantId = intval($product['variantId']);
    if (empty($product['variantId']) && isset($product['variant_id'])) {
      $variantId = intval($product['variant_id']);
    }
    
    $currentDate = date('Y-m-d H:i:s');
    $updateLastUpdateSql = 'UPDATE `'.PREFIX.'product` '.
      'SET `last_updated` = '.DB::quote($currentDate).' '.
      'WHERE `id` = '.DB::quoteInt($productId);
    if ($variantId) {
      $updateLastUpdateSql = 'UPDATE `'.PREFIX.'product_variant` '.
        'SET `last_updated` = '.DB::quote($currentDate).' '.
        'WHERE `id` = '.DB::quoteInt($variantId);
    }

    $result = DB::query($updateLastUpdateSql);
    return $result;
  }

  /**
   * Клонирует остатки по складам с одного товара/варианта на другой, используется при клонировании товаров и вариантов.
   * 
   * @param int $oldId - идентификатор клонируемого товара/варианта
   * @param int $newId - идентификатор нового товара
   * @param int $newVariantId - идентификатор нового варианта
   * 
   * @return bool
   */
  public function cloneStorageData($oldId, $newId, $newVariantId = 0) {
    $result = true;
    $oldStoragesDataWhereClause = '`product_id` = '.DB::quoteInt($oldId);
    if ($newVariantId) {
      $oldStoragesDataWhereClause = '`variant_id` = '.DB::quoteInt($oldId);
    }
    $oldStoragesDataSql = 'SELECT * '.
      'FROM `'.PREFIX.'product_on_storage`'.
      'WHERE '.$oldStoragesDataWhereClause;
    $oldStoragesDataResult = DB::query($oldStoragesDataSql);
    $insertParts = [];
    while ($oldStorageDataRow = DB::fetchAssoc($oldStoragesDataResult)) {
      $insertParts[] = '('.implode(', ', [
        'NULL',
        DB::quote($oldStorageDataRow['storage']),
        DB::quoteInt($newId),
        DB::quoteInt($newVariantId),
        DB::quoteFloat($oldStorageDataRow['count']),
      ]).')';
    }
    if ($insertParts) {
      $insertSql = 'INSERT INTO '.
        '`'.PREFIX.'product_on_storage` '.
        'VALUES '.implode(', ', $insertParts);
      $result = DB::query($insertSql);
    }
    return $result;
  }

  /**
   * Возвращает остатки для списка вариантов с переданного или со всех складов.
   * 
   * @param array $variantsIds - массив идентификаторов вариантов
   * @param string $storaeg - идентификатор склада, если не передавать, то будет возвращены остатки по всем складам
   * 
   * @return array
   */
  public function getStoragesCountsByVariantsIds($variantsIds = [], $storage = null) {
    if (!$variantsIds) {
      return [];
    }
    $storagesCount = [];
    $storagesCountSql = 'SELECT `storage`, `count`, `variant_id` '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `variant_id` IN ('.DB::quoteIN($variantsIds).')';
    if ($storage) {
      $storagesCountSql .= ' AND `storage` = '.DB::quote($storage);
    }
    $storagesCountResult = DB::query($storagesCountSql);
    while ($storagesCountRow = DB::fetchAssoc($storagesCountResult)) {
      $storage = strval($storagesCountRow['storage']);
      $variantId = intval($storagesCountRow['variant_id']);
      $count = floatval($storagesCountRow['count']);
      $storagesCount[$variantId][$storage] = $count;
    }
    return $storagesCount;
  }

  /**
   * Проверяет, требуется ли перерасчёт остатков товаров по складам.
   * 
   * @return bool
   */
  public function checkStoragesRecalculation() {
    $checkStorageCountSql = 'SELECT `id` '.
      'FROM `'.PREFIX.'product` '.
      'WHERE `storage_count` IS NOT NULL AND'.
        '`storage_count` != 0 '.
      'LIMIT 1';
    $checkStorageCountResult = DB::query($checkStorageCountSql);
    if (DB::fetchAssoc($checkStorageCountResult)) {
      return true;
    }

    $checkStocksSql = 'SELECT `id` '.
      'FROM `'.PREFIX.'product_on_storage` '.
      'WHERE `count` != 0 '.
      'LIMIT 1';
    $checkStocksResult = DB::query($checkStocksSql);
    if (DB::fetchAssoc($checkStocksResult)) {
      MG::setOption('showStoragesRecalculate', 'true');
      MG::setSetting('showStoragesRecalculate', 'true');
    }
    return true;
  }
}