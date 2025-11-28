<?php

/**
 * Класс SmalCart - моделирует данные для маленькой корзины.
 *  - Предоставляет массив с количеством товаров и их общей стоимостью.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class SmalCart {

  /**
   * Записывает в cookie текущее состояние
   * корзины в сериализованном виде.
   * @access private
   * @return void
   */
  public static function setCartData() {
    // Сериализует  данные корзины из сессии в строку.
    // $cartContent = serialize($_SESSION['cart']);
    // Записывает сериализованную строку в куки, хранит 1 год.
    // SetCookie('cart', $cartContent, time()+3600*24*365);
    // MG::create Hook(__CLASS__."_".__FUNCTION__, $cartContent);
  }

  /**
   * Получает данные из куков назад в сессию.
   * @access private
   * @return array
   */
  public static function getCokieCart() {
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $_SESSION['cart'], $args);
  }

  /**
   * Вычисляет общую стоимость содержимого, а также количество.
   * <code>
   * $res = Smalcart::getCartData();
   * viewData($res);
   * </code>
   * @return array массив с данными о количестве и цене.
   */
  public static function getCartData() {
    $modelCart = new Models_Cart();
    // Количество вещей в корзине.
    $res['cart_count'] = 0;

    // Общая стоимость.
    $res['cart_price'] = 0;

    // Если удалось получить данные из куков и они успешно десериализованны в $_SESSION['cart'].
    //self::getCokieCart() &&
    if (!empty($_SESSION['cart'])) {
      $settings = MG::get('settings');
      $totalPrice = 0;
      $totalCount = 0;
      $where = '';

      if (!empty($_SESSION['cart'])) {
        $itemIds = array();
        $variantsId = array();
        foreach ($_SESSION['cart'] as $key => $item) {
          if (!empty($item['id'])) {
            if (!empty($item['variantId'])) {
              $variantsId[] = intval($item['variantId']);
            }
            $itemIds[] = intval($item['id']);
          }
        }

        if (!empty($itemIds)) {
          // Пробегаем по содержимому.
          //$idsPr = implode(',', array_unique($itemIds));
          $where = " IN ('".trim(DB::quoteIN(array_unique($itemIds)), "'")."')";
        }
      } else {
        $where = ' IN (0)';
      }
      // Пробегаем по содержимому.
      //   $where = ' IN ('.trim(DB::quote(implode(',',$itemIds)),"'").')';
      $result = DB::query('
          SELECT CONCAT(c.parent_url,c.url) AS category_url, p.url AS product_url, p.*, rate,
          (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, p.currency_iso
          FROM `'.PREFIX.'product` AS p
          LEFT JOIN `'.PREFIX.'category` AS c ON c.id = p.cat_id
          WHERE p.id '.$where);
      $array_variants = array();
      if (!empty($variantsId)) {
          $ids = implode(',', $variantsId);
          $variants_res = DB::query('SELECT  pv.*, c.rate,(pv.price_course + pv.price_course *(IFNULL(c.rate,0))) as `price_course`,
          p.currency_iso
          FROM `'.PREFIX.'product_variant` pv   
          LEFT JOIN `'.PREFIX.'product` as p ON 
            p.id = pv.product_id
          LEFT JOIN `'.PREFIX.'category` as c ON 
            c.id = p.cat_id       
          WHERE pv.id IN ('.trim(DB::quote($ids, true)).')');
          while ($variant_row = DB::fetchAssoc($variants_res)) {
            $array_variants[$variant_row['id']] = $variant_row;
           }
        }

      // пересчет количества доступных вариантов товара по мультискладовости
      if(MG::enabledStorage()) {
        $idsToStorage = $tmpData = array();
        foreach ($array_variants as $key => $value) {
          $idsToStorage[] = $key;
          $array_variants[$key]['count'] = 0;
        }
        $idsToStorage = array_unique($idsToStorage);
        $sqlStorage='SELECT count, variant_id FROM '.PREFIX.'product_on_storage WHERE variant_id IN ('.DB::quoteIN($idsToStorage).') '.$storageWhere;
        $resStorage = DB::query($sqlStorage);
        while($rowStorage = DB::fetchAssoc($resStorage)) {
          $tmpData[$rowStorage['variant_id']][] = $rowStorage['count'];
        }
        foreach ($array_variants as $key => $value) {
          if (!empty($tmpData[$key]) && is_array($tmpData[$key])) {
            foreach ($tmpData[$key] as $ivalue) {
              if($array_variants[$key]['count'] == -1) break;
              if($ivalue == -1) {
                $array_variants[$key]['count'] = -1;
                break;
              }
              $array_variants[$key]['count'] += $ivalue;
            }
          }
        }
      }

      $currencyRate = MG::getSetting('currencyRate');
      $currencyShopIso = MG::getSetting('currencyShopIso');
      $products_row = array();
      while ($prod = DB::fetchAssoc($result)) {
        // Костыль
        // Для того, чтобы от него избавиться, нужно, чтобы шаблоны работали со storage_count, а не с count
        // Или найти другой способ
        if (MG::enabledStorage() && isset($prod['storage_count'])) {
          $prod['count'] = $prod['storage_count'];
        }
        $products_row[$prod['id']] = $prod;
      }
      foreach ($_SESSION['cart'] as $key => $item) {
        $variant = null;
        if (!isset($products_row[$item['id']])) {continue;}
        $row = $products_row[$item['id']];
        $arrayImages = explode("|", $row['image_url']);

        if (!empty($arrayImages)) {
          $row['image_url'] = $arrayImages[0];
          $row['image_url_new'] = mgImageProductPath($row['image_url'], $item['id'], 'small');
          $row['image_thumbs'] = array(
            '30' => mgImageProductPath($row['image_url'], $item['id'], 'small'),
            '2x30' => mgImageProductPath($row['image_url'], $item['id'], 'small_2x'),
            '70' => mgImageProductPath($row['image_url'], $item['id'], 'big'),
            '2x70' => mgImageProductPath($row['image_url'], $item['id'], 'big_2x'),
          );
        }

        MG::loadLocaleData($row['id'], LANG, 'product', $row);

        if (!empty($item['variantId']) && !empty($array_variants[$item['variantId']])) {
          $variant = $array_variants[$item['variantId']];
          $image = ($variant['image']) ? $variant['image'] : $arrayImages[0];

          MG::loadLocaleData($variant['id'], LANG, 'product_variant', $variant);

          $row['price'] = $variant['price'];
          $row['old_price'] = $variant['old_price'];
          $row['code'] = $variant['code'];
          $row['count'] = $variant['count'];
          $row['image_url'] = $variant['image'] ? $variant['image'] : $row['image_url'];


          $row['image_thumbs'] = array(
            '30' => mgImageProductPath($image, $item['id'], 'small'),
            '2x30' => mgImageProductPath($image, $item['id'], 'small_2x'),
            '70' => mgImageProductPath($image, $item['id'], 'big'),
            '2x70' => mgImageProductPath($image, $item['id'], 'big_2x'),
          );

          $row['image_url_new'] = mgImageProductPath($image, $item['id'], 'small');
          $row['weight'] = $variant['weight'];
          $row['title'] = $row['title'] . " " . $variant['title_variant'];
          $row['variantId'] = $variant['id'];
          $row['price_course'] = $variant['price_course'];


          // Добавляем артикул варианта к ссылке, если включено в настройках
          if (MG::getSetting('varHashProduct') == 'true') {
            $row['product_url'] .= '#' . $row['code'];
          }
        }

        $price = $row['price_course'];

        $currency_iso = 'RUR';
        $resIn = DB::query('SELECT currency_iso FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($item['id']));
        if($rowIn = DB::fetchAssoc($resIn)) {
          $currency_iso = $rowIn['currency_iso'];
        }

        $price = MG::convertPrice($price);

        $wholesalePrice = MG::setWholePrice($price, $item['id'], $item['count'], $item['variantId'], $currency_iso);
        // $price = min([$price, $wholesalePrice]);
        $price = $wholesalePrice;

        $_SESSION['cart'][$key]['price'] = $price;
        if ($item['id'] == $row['id']) {
          $count = $item['count'];
          $row['countInCart'] = $count;
          $row['property_html'] = htmlspecialchars_decode(str_replace('&amp;', '&', $item['property']));
          $row['title'] = htmlspecialchars_decode(str_replace('&amp;', '&', $row['title']));
          $price = self::plusPropertyMargin($price, $item['propertyReal'], $currencyRate[$row['currency_iso']]);
          $_SESSION['cart'][$key]['price'] = $price;
          $row['property'] = isset($item['propertySetId'])?$item['propertySetId']:null;
          $priceWithCoupon = $modelCart->applyCoupon(isset($_SESSION['couponCode'])?$_SESSION['couponCode']:'', $price, $row);

          $price = $modelCart->customPrice(array(
            'product' => $row,
            'priceWithCoupon' => $priceWithCoupon,
          ));

          // если выбран формат без копеек, то округляем стоимость до ворматирования.
          if(in_array(MG::getSetting('priceFormat'), array('1234','1 234','1,234'))){
            $price = round($price);
          }
          $row['priceInCart'] = MG::priceCourse($price * $count)." ".$settings['currency'];
          $_SESSION['cart'][$key]['priceWithDiscount'] = $price;
          $_SESSION['cart'][$key]['priceWithDiscountFormat'] = MG::numberFormat($price);
          $row['category_url'] = (MG::getSetting('shortLink') == 'true' ? '' : $row['category_url'].'/');
          $row['category_url'] = ($row['category_url'] == '/' ? 'catalog/' : $row['category_url']);
          $row['price'] = $price;
          if (!isset($row['variantId'])) {
            $row['variantId'] = 0;
          }
          $res['dataCart'][] = $row;

          $totalPrice += $price * $count;
          $totalCount += $count;
          $itemIds[] = $item['id'];
        }
      }
      unset($_SESSION['wholeSalesCartSum']);

      $res['cart_price_wc'] = MG::priceCourse($totalPrice)." ".htmlspecialchars_decode($settings['currency']);
      $res['cart_count'] = $totalCount;
      $res['cart_price'] = MG::priceCourse($totalPrice);
      Models_Cart::sessionToCookie();
    }


    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $res, $args);
  }

  /**
   * Прибавляет к стоимости товара дополнительные цены от выбранных характеристик.
   * <code>
   * $price = 6299;
   * $propertyHtml = '<div class="prop-position"> <span class="prop-name">Дополнительно: переходник</span> <span class="prop-val"> переходник#100#</span></div>';
   * $rate = 1;
   * $res = Smalcart::plusPropertyMargin($price, $propertyHtml, $rate);
   * viewData($res);
   * </code>
   * @param float $price базовая цена товара.
   * @param string $propertyHtml строка с информацией о выбранных характеристиках.
   * @param float $rate наценка.
   * @return float
   */
  public static function plusPropertyMargin($price, $propertyHtml, $rate) {
    $m = array();
    preg_match_all("/prop-val.*#((-|\+)?\d+(.|,)?\d*%?)#</U", $propertyHtml, $m);
    $rate = $rate ? $rate : 1;
    if (!empty($m[1])) {
      //находим все составляющие цены характеристик и прибавляем их к общей стоимости позиции.
      foreach ($m[1] as $partPrice) {
        if(is_numeric($partPrice)){
          $price += $partPrice * $rate;
        }
        if(stripos($partPrice,'%')){ //проверка на число с процентом (20%)
          $marginPercent = str_replace('%', '', $partPrice);
          $marginPercent = str_replace(',', '.', $marginPercent);
          $marginCoefficient = $marginPercent / 100 + 1;
          $price *= $marginCoefficient * $rate;
        }
      }
    }
    return $price;
  }
}
