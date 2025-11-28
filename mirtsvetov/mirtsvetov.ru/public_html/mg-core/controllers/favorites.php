<?php

/**
 * Контроллер: Favorites
 *
 * Класс Controllers_Favorites позволяет добавить товар в избранное для дальнейшей возможной покупки
 *
 * @author Гайдис Михаил
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Favorites extends BaseController {

  /**
   * Определяет поведение при изменении и удаление данных в корзине,
   * а так же выводит список позиций к заказу.
   * @return void
   */
  public function __construct() {

    if (MG::getSetting('useFavorites') != 'true') {
      header('Location: '.SITE);
      die;
    }

    if (isset($_POST['addFav'])) {
      if (isset($_COOKIE['favorites'])) {
        $favorites = explode(',', $_COOKIE['favorites']);
      } else {
        $favorites = array();
      }
      if(!in_array($_POST['id'], $favorites)) {
        $favorites[] = $_POST['id'];
        $res = DB::query('SELECT id FROM '.PREFIX.'product WHERE id IN ('.DB::quoteIN($favorites).')');
        $favorites = array();
        while($row = DB::fetchAssoc($res)) {
          $favorites[] = $row['id'];
        }
        setcookie("favorites", implode(',', array_filter($favorites)), time()+3600*24*30*12,'/');
      }
      echo json_encode(count(array_filter($favorites)));
      exit;
    }

    if (isset($_POST['delFav'])) {
      $favorites = explode(',', $_COOKIE['favorites']);
      if(in_array($_POST['id'], $favorites)) {
        foreach ($favorites as $key => $id) {
          if($id == $_POST['id']) unset($favorites[$key]);
        }
        $res = DB::query('SELECT id FROM '.PREFIX.'product WHERE id IN ('.DB::quoteIN($favorites).')');
        $favorites = array();
        while($row = DB::fetchAssoc($res)) {
          $favorites[] = $row['id'];
        }
        setcookie("favorites", implode(',', array_filter($favorites)), time()+3600*24*30*12,'/');
      }
      echo json_encode(count(array_filter($favorites)));
      exit;
    }

    // для отрисовки страницы

    $model = new Models_Catalog;
    $currencyRate = MG::getSetting('currencyRate');      
    $currencyShopIso = MG::getSetting('currencyShopIso'); 


    $titeCategory = lang('indexSale');
    // $classTitle = "m-p-sale-products-title";
    // Формируем список товаров со старой ценой.
    $items = $model->getListByUserFilter(9999, ' p.id IN ('.DB::quoteIN(isset($_COOKIE['favorites'])?$_COOKIE['favorites']:null).') and p.activity=1 ORDER BY sort ASC');

      // дропаем товары, которых нет в наличии
      $items['catalogItems'] = MG::clearProductBlock($items['catalogItems']);
	  $productIds = array();
      if(!empty($items)){
        
        foreach ($items['catalogItems'] as $k => $item) {
          $productIds[] = $item['id'];
          $items['catalogItems'][$k]['currency_iso'] = $item['currency_iso']?$item['currency_iso']:$currencyShopIso;
         // $item['price'] *= $currencyRate[$item['currency_iso']];   
          $items['catalogItems'][$k]['old_price'] = floatval($item['old_price'])* $currencyRate[$item['currency_iso']];
          $items['catalogItems'][$k]['old_price'] = $item['old_price']? MG::priceCourse($item['old_price']):0;
          $items['catalogItems'][$k]['price'] =  MG::priceCourse($item['price_course']); 
        }
      }
      $product = new Models_Product;
      $blocksVariants = $product->getBlocksVariantsToCatalog($productIds, 0, defined('TEMPLATE_INHERIT_FROM'));  
      $blockedProp = $product->noPrintProperty();


      if(!empty($items)){
      
      foreach ($items['catalogItems'] as $k => $item) {

        if (!empty($item['variants'])) {
        for($i = 0; $i < count($item['variants']); $i++) {
          if($item['variants'][$i]['count'] == 0) {
            $item['variants'][] = $item['variants'][$i];
            unset($item['variants'][$i]);
          }
        }
        }

        if (!empty($item['variants'])) {
        $items['catalogItems'][$k]['variants'] = array_values($item['variants']);
        }

        $imagesUrl = explode("|", $item['image_url']);
        $items['catalogItems'][$k]["image_url"] = "";

        if (!empty($imagesUrl[0])) {
          $items['catalogItems'][$k]["image_url"] = $imagesUrl[0];
        }

        if (!empty($item['variants'])) {
        if (count($item['variants'])) {$items['catalogItems'][$k]['count'] = $items['catalogItems'][$k]['variants'][0]["count"];}
        
        $items['catalogItems'][$k]['variants'] = array_values($item['variants']);
        }

        $imagesUrl = explode("|", $item['image_url']);
        $items['catalogItems'][$k]["image_url"] = "";
        if (!empty($imagesUrl[0])) {
          $items['catalogItems'][$k]["image_url"] = $imagesUrl[0];
        }

        if(MG::getSetting('showMainImgVar') == 'true') {
            if(!empty($item['variants'][0]['image'])) {
                if($item['variants'][0]['image'] != '') {
                    $img = explode('/', $items['catalogItems'][$k]['images_product'][0]);
                    $img = end($img);
                    $items['catalogItems'][$k]["image_url"] = $items['catalogItems'][$k]['images_product'][0] = str_replace($img, $item['variants'][0]['image'], $items['catalogItems'][$k]['images_product'][0]);
                }
            }
        }
         
        $items['catalogItems'][$k]['title'] = MG::modalEditor('catalog', $item['title'], 'edit', $item["id"]);

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

        // Формируем варианты товара.
        // if ($item['variant_exist']) {

          // Легкая форма без характеристик.
          $liteFormData = $product->createPropertyForm($param = array(
            'id' => $item['id'],
            'maxCount' => $item['count'],
            'productUserFields' => null,
            'action' => "/catalog",
            'method' => "POST",
            'ajax' => true,
            'blockedProp' => $blockedProp,
            'noneAmount' => true,
            'titleBtn' => MG::getSetting('buttonBuyName'),
            // 'buyButton' => ($items['catalogItems'][$k]['count']==0)?$items['catalogItems'][$k]['actionView']:'',
            'buyButton' => $buyButton,
            'blockVariants' => !empty($blocksVariants[$item['id']]) ? $blocksVariants[$item['id']] : null
          ), 'nope', defined('TEMPLATE_INHERIT_FROM'));
          if (!defined('TEMPLATE_INHERIT_FROM')) {
            $items['catalogItems'][$k]['liteFormData'] = $liteFormData['html'];
          } else {
            $items['catalogItems'][$k]['liteFormData'] = $liteFormData['propertyData'];
          }
         // }

         // определяем для каждого продукта  тип выводимой формы: упрощенная, с кнопками или без.        
          if (!defined('TEMPLATE_INHERIT_FROM')) {
            if (!$items['catalogItems'][$k]['liteFormData']){
              if($items['catalogItems'][$k]['count']==0){
                $buyButton = $items['catalogItems'][$k]['actionView'];          
              }else{
                $buyButton = $items['catalogItems'][$k]['actionButton'];
              }
            } else{
              $buyButton = $items['catalogItems'][$k]['liteFormData'];
            }
          }
          $items['catalogItems'][$k]['buyButton'] = $buyButton;
        }
      }
        
      $data = array(
        'items' => $items['catalogItems'],
        'meta_title' => lang('favoriteTitle'),
        'meta_keywords' => lang('favoriteSEO'),
        'meta_desc' => lang('favoriteSEO'),
        'actionButton' => MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView',
			// 'class_title' => $classTitle,
        'currency' => MG::getSetting('currency'),
      );
      if (defined('TEMPLATE_INHERIT_FROM')) {
        $data['titleCategory'] = lang('favoriteTitle');
      } else {
        $data['titeCategory'] = lang('favoriteTitle');
      }

    $currencyRate = MG::getSetting('currencyRate');  
    foreach ($data['items'] as $key => $product) {
      if (!empty($product['variants'])) {
        $data['items'][$key]["price"] = MG::numberFormat($product['variants'][0]["price_course"]);
        $data['items'][$key]["old_price"] = $product['variants'][0]["old_price"];
        $data['items'][$key]["count"] = $product['variants'][0]["count"];
        $data['items'][$key]["code"] = $product['variants'][0]["code"];
        $data['items'][$key]["weight"] = $product['variants'][0]["weight"];
        $data['items'][$key]["price_course"] = $product['variants'][0]["price_course"];
        $data['items'][$key]["variant_exist"] = $product['variants'][0]["id"];
      }
      if (MG::numberDeFormat($data['items'][$key]["price"]) > MG::numberDeFormat($data['items'][$key]["old_price"])) {
        $data['items'][$key]["old_price"] = 0;
      }
    }
    
    $this->data = $data;

  }

}