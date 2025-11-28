<?php

/**
 * Контроллер: Catalog
 *
 * Класс Controllers_Catalog обрабатывает действия пользователей в каталоге интернет магазина.
 * - Формирует список товаров для конкретной страницы;
 * - Добавляет товар в корзину.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Index extends BaseController {

  function __construct() {
    $settings = MG::get('settings');
	  $actionButton = MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView';
    // Если нажата кнопка купить.
    $_REQUEST['category_id'] = URL::getQueryParametr('category_id');
    if (isset($_REQUEST['inCartProductId'])) {
      $_REQUEST['inCartProductId'] = intval($_REQUEST['inCartProductId']);
    }

    if (!empty($_REQUEST['inCartProductId'])) {
      $cart = new Models_Cart;
      $property = $cart->createProperty($_POST);
      $cart->addToCart($_REQUEST['inCartProductId'], $_REQUEST['amount_input'], $property);
      SmalCart::setCartData();
      MG::redirect('/cart');
    }

    $countСatalogProduct = $settings['countСatalogProduct'];
    // Показать первую страницу выбранного раздела.
    $page = 1;

    // Запрашиваемая страница.
    if (isset($_REQUEST['p'])) {
      $page = $_REQUEST['p'];
    }

    $model = new Models_Catalog;
    $product = new Models_Product;

    // Получаем список вложенных категорий, для вывода всех продуктов, на страницах текущей категории.
    $model->categoryId = MG::get('category')->getCategoryList($_REQUEST['category_id']);

    // В конец списка, добавляем корневую текущую категорию.
    $model->categoryId[] = $_REQUEST['category_id'];

    // Передаем номер требуемой страницы, и количество выводимых объектов.
    $countСatalogProduct = 100;
    if (MG::getSetting('mainPageIsCatalog') == 'true') {
    $printCompareButton = MG::getSetting('printCompareButton');
    // $dataGroupProducts = Storage::get('indexGroup-'.md5('dataGroupProductsIndexConroller'.LANG.$_SESSION['userCurrency']));

    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso');
    $randomProdBlock = MG::getSetting('randomProdBlock')=="true"? true: false;

    $maxCountRecommend = MG::getSetting('countRecomProduct')?MG::getSetting('countRecomProduct'):0;
    $maxCountNew = MG::getSetting('countNewProduct')?MG::getSetting('countNewProduct'):0;
    $maxCountSales = MG::getSetting('countSaleProduct')?MG::getSetting('countSaleProduct'):0;
    $allMaxCount = $maxCountRecommend + $maxCountNew + $maxCountSales;

    // достаем id товаров, которые должны будут показаны
    $sort = "p.sort";
    if($randomProdBlock) $sort = "RAND()";

    $whereCondition = '';
    if (MG::getSetting('printProdNullRem') === 'true') {
      $whereCondition = ' AND (pv.`count` != 0 || p.`count` != 0)';
      if (MG::enabledStorage()) {
        $whereCondition = ' AND p.`storage_count` != 0';
      }
    }

    $mainPageProductsIds = [];

    if (!$randomProdBlock) {
      $mainPageProductsIds = Storage::get('mainProductsIds');
    }
    if (!$mainPageProductsIds) {
      // достаем товары, которые должны быть в блоках
      $sql = 'SELECT DISTINCT p.id, p.sort FROM '.PREFIX.'product AS p LEFT JOIN '.PREFIX.'product_variant AS pv ON p.id = pv.product_id
        WHERE p.recommend = 1 and p.activity=1'.$whereCondition.' ORDER BY '.$sort.' ASC LIMIT '.$maxCountRecommend;
      $sql = Models_Catalog::checkIndexPageBlocks($sql, 'recommended');
      $res = DB::query($sql);
      while ($row = DB::fetchAssoc($res)) {
        $mainPageProductsIds[] = $row['id'];
      }
  
      $sql = 'SELECT DISTINCT p.id, p.sort FROM '.PREFIX.'product AS p LEFT JOIN '.PREFIX.'product_variant AS pv ON p.id = pv.product_id
        WHERE p.new = 1 and p.activity=1'.$whereCondition.' ORDER BY '.($randomProdBlock?$sort.' ASC':$sort.' DESC').' LIMIT '.$maxCountNew;
      $sql = Models_Catalog::checkIndexPageBlocks($sql, 'new');
      $res = DB::query($sql);
      while ($row = DB::fetchAssoc($res)) {
        $mainPageProductsIds[] = $row['id'];
      }
  
      $sql = 'SELECT DISTINCT p.id, p.sort FROM '.PREFIX.'product AS p LEFT JOIN '.PREFIX.'product_variant AS pv ON p.id = pv.product_id
        WHERE IF(pv.price_course, pv.price_course < pv.old_price, p.price_course < p.old_price) and p.activity=1'.$whereCondition.' ORDER BY '.$sort.' ASC LIMIT '.$maxCountSales;
      $sql = Models_Catalog::checkIndexPageBlocks($sql, 'sale');
      $res = DB::query($sql);
      while ($row = DB::fetchAssoc($res)) {
        $mainPageProductsIds[] = $row['id'];
      }

      if (!$randomProdBlock) {
        Storage::save('mainProductsIds', $mainPageProductsIds);
      }
    }

    if (empty($dataGroupProducts)) {
      $onlyInCount = '';
	    $recommendProducts = $newProducts = $saleProducts = array();

      // Формируем список товаров для блока рекомендуемой продукции.
      // $sort = $randomProdBlock ? "RAND()" : "p.sort";


      // Формируем список товаров со старой ценой.
      $productsList = $model->getListByUserFilter($allMaxCount, ' p.id IN ('.DB::quoteIN($mainPageProductsIds).') ORDER BY '.$sort.' DESC');
      $productsList['catalogItems'] = MG::loadWholeSalesToCatalog($productsList['catalogItems']);
      // дропаем товары, которых нет в наличии
      //$productsList['catalogItems'] = MG::clearProductBlock($productsList['catalogItems']);

      // viewData($productsList['catalogItems']);

      // viewData(count($productsList['catalogItems']));
      $recommendCounter = 0;
      $newCounter = 0;
      $salesCounter = 0;

      // viewData($productsList['catalogItems']);

      foreach ($productsList['catalogItems'] as &$item) {
        // viewData($item);
        if (!empty($item['variants']) && is_array($item['variants'])) {
          for($i = 0; $i < count($item['variants']); $i++) {
            if($item['variants'][$i]['count'] == 0) {
              $item['variants'][] = $item['variants'][$i];
              unset($item['variants'][$i]);
            }
          }
          $item['variants'] = array_values($item['variants']);
        }

        $imagesUrl = explode("|", $item['image_url']);
        $item["image_url"] = "";
        if (!empty($imagesUrl[0])) {
          $item["image_url"] = $imagesUrl[0];
        }

        if(MG::getSetting('showMainImgVar') == 'true') {
          if(isset($item['variants']) && $item['variants'][0]['image'] != '') {
            $img = explode('/', $item["image_url"]);
            $img = end($img);
             $item['image_url'] = $item['images_product'][0] = str_replace($img, $item['variants'][0]['image'], $item['image_url']);
          }
        }

        if (!empty($item['variants'])) {
          $item["price"] = MG::numberFormat($item['variants'][0]["price_course"]);
          $item["old_price"] = $item['variants'][0]["old_price"];
          $item["count"] = $item['variants'][0]["count"];
          $item["code"] = $item['variants'][0]["code"];
          $item["weight"] = $item['variants'][0]["weight"];
          $item["price_course"] = $item['variants'][0]["price_course"];
          $item["variant_exist"] = $item['variants'][0]["id"];
        }
        else{
          $item["price_course"] = MG::convertPrice($item["price_course"]);
        }
        if (defined('NULL_OLD_PRICE') && NULL_OLD_PRICE && (MG::numberDeFormat($item["price_course"]) > MG::numberDeFormat($item['old_price']))){
          $item['old_price'] = 0;
        }

        $item['currency_iso'] = $item['currency_iso']?$item['currency_iso']:$currencyShopIso;
        $item['old_price'] = $item['old_price']? MG::priceCourse($item['old_price']):0;
        $item['price'] =  MG::priceCourse($item['price_course']);
        if($printCompareButton!='true') {
          $item['actionCompare'] = '';
        }
        if($actionButton=='actionBuy' && $item['count']==0) {
          $item['actionBuy'] = isset($item['actionView']) ? $item['actionView'] : '';
          $item['count'] = 0;
        }

        // Легкая форма без характеристик.
        $blocksVariants = $product->getBlockVariants($item['id'], $item['cat_id'], defined('TEMPLATE_INHERIT_FROM'));

        if (
          (
            $item['count'] == 0 &&
            empty($item['variants'])
          ) ||
          (
            !empty($item['variants']) &&
            $item['variants'][0]['count'] == 0
          ) ||
          MG::getSetting('actionInCatalog')=='false'
        ) {
          if (!defined('TEMPLATE_INHERIT_FROM')) {
            $buyButton = MG::layoutManager('layout_btn_more', $item);
          } else {
            $buyButton = 'more';
          }
        } else {
          if (!defined('TEMPLATE_INHERIT_FROM')) {
            $buyButton = MG::layoutManager('layout_btn_buy', $item);
          } else {
            $buyButton = 'buy';
          }
        }

        $liteFormData = $product->createPropertyForm($param = array(
          'id' => $item['id'],
          'maxCount' => $item['count'],
          'productUserFields' => null,
          'action' => "/catalog",
          'method' => "POST",
          'ajax' => true,
          'noneAmount' => true,
          'titleBtn' => "В корзину",
          'blockVariants' => $blocksVariants,
          'buyButton' => $buyButton
        ), 'nope', defined('TEMPLATE_INHERIT_FROM'));

        if (!defined('TEMPLATE_INHERIT_FROM')) {
          $item['buyButton']= isset($liteFormData['html'])?$liteFormData['html']:'';
        } else {
          $item['buyButton'] = $buyButton;
          $item['liteFormData'] = isset($liteFormData['propertyData']) ? $liteFormData['propertyData'] : '';
        }

        if($item['recommend'] == 1) {
          if($recommendCounter < $maxCountRecommend) {
            $recommendProducts['catalogItems'][] = $item;
          }
          $recommendCounter++;
        }

        if($item['new'] == 1) {
          if($newCounter < $maxCountNew) {
            $newProducts['catalogItems'][] = $item;
          }
          $newCounter++;
        }

        $saleProduct = false;
        if ($item['old_price'] && MG::numberDeFormat($item['old_price']) > MG::numberDeFormat($item['price_course'])) {
          $saleProduct = true;
        } else {
          foreach ($item['variants'] as $itemVariant) {
            if ($itemVariant['old_price'] && MG::numberDeFormat($itemVariant['old_price']) > MG::numberDeFormat($itemVariant['price_course'])) {
              $saleProduct = true;
              break;
            }
          }
        }
        if($saleProduct) {
          if($salesCounter < $maxCountSales) {
            $saleProducts['catalogItems'][] = $item;
          }
          $salesCounter++;
        }
      }

      if($randomProdBlock) {
        if (!empty($recommendProducts['catalogItems'])) {
          shuffle($recommendProducts['catalogItems']);
        }
        if (!empty($newProducts['catalogItems'])) {
          shuffle($newProducts['catalogItems']);
        }
        if (!empty($saleProducts['catalogItems'])) {
          shuffle($saleProducts['catalogItems']);
        }
      } elseif (!empty($newProducts['catalogItems']) && is_array($newProducts['catalogItems'])){
        //Показываем сначала самый свежий товар (новый товар -> самое большое значение sort)
        //$newProducts['catalogItems'] = array_reverse($newProducts['catalogItems']);
      }

      $dataGroupProducts['recommendProducts'] = $recommendProducts;
      $dataGroupProducts['newProducts'] = $newProducts;
      $dataGroupProducts['saleProducts'] = $saleProducts;
    }

    $recommendProducts = $dataGroupProducts['recommendProducts'];
    $newProducts = $dataGroupProducts['newProducts'];
    $saleProducts = $dataGroupProducts['saleProducts'];
    }
    $html = MG::get('pages')->getPageByUrl('index');
    MG::loadLocaleData($html['id'], LANG, 'page', $html);

    if(!empty($html)) {
      $html['html_content'] = MG::inlineEditor(PREFIX.'page', "html_content", $html['id'], $html['html_content'], 'page'.DS.$html['id'], null, true);
    } else {
      $html['html_content'] = '';
    }
    $data = array(
      'recommendProducts' => !empty($recommendProducts['catalogItems'])&&MG::getSetting('countRecomProduct') ? $recommendProducts['catalogItems'] : array(),
      'newProducts' => !empty($newProducts['catalogItems'])&&MG::getSetting('countNewProduct') ? $newProducts['catalogItems'] : array(),
      'saleProducts' => !empty($saleProducts['catalogItems'])&&MG::getSetting('countSaleProduct') ? $saleProducts['catalogItems'] : array(),
      'cat_desc' => $html['html_content'],
      'meta_title' => $html['meta_title'],
      'meta_keywords' => $html['meta_keywords'],
      'meta_desc' => $html['meta_desc'],
      'currency' => $settings['currency'],
      'actionButton' => $actionButton
    );
    if (defined('TEMPLATE_INHERIT_FROM')) {
      $data['titleCategory'] = $html['meta_title'];
    } else {
      $data['titeCategory'] = $html['meta_title'];
    }
    $this->data = $data;
  }

}
