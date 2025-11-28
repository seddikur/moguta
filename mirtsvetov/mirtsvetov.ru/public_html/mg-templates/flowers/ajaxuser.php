<?php

/**
 * Класс предназначенный для работы с БД сайта средствами AJAX запросов.
 * Должен наследоваться от класса Actioner и содержать в себе пользовательские методы, вызываемые AJAX'ом
 * Методы данного класса могут быть вызваны из JS скриптов следующим образом:
 *   $.ajax({
 *      type: "POST",
 *      url: "ajax",
 *      data: {
 *        action: "getSearchData", // название действия в пользовательском класса Ajaxuser
 *        actionerClass: "Ajaxuser", // ajaxuser.php - в папке шаблона
 *        param1: text,
 *    	  param2: text
 *      },   
 *    });
 * 
 *   @author Авдеев Марк <mark-avdeev@mail.ru>
 *   @package moguta.cms
 *   @subpackage File
 */
class Ajaxuser extends Actioner {

  /**
   * Получает список продуктов при вводе в поле поиска
   */
  public function getSearchData() {
    $keyword = URL::getQueryParametr('search');
    if (!empty($keyword)) {
      $catalog = new Models_Catalog;
      $items = $catalog->getListProductByKeyWord($keyword, true, true);
    
      $searchData = array(
        'status' => 'success',
        'item' => array(
          'keyword' => $keyword,
          'count' => $items['numRows'],
          'items' => $items,
        ),
        'currency' => MG::getSetting('currency')
      );
    }

    echo json_encode($searchData);
    exit;
  }

   /**
	* 
    * Формирует единую строку с товаром для последующего обновления корзины. 
 	* 
	* Используется в объекте `Amount` при вводе количества товара в поле input.
 	* 
 	* @return void
  	*
 	* @echo json Возвращает строку запроса и флаг для обновления корзины в формате JSON.
    * 
    */
    public function updateCartItem()
    {
		if (isset($_POST['productId'], $_POST['variantId'], $_POST['count'])) {

			$productId = $_POST['productId'];
			$variantId = $_POST['variantId'];
			$count = $_POST['count'];

			$request = [];
			$refresh = false;
			$preCount = 0;

			foreach ($_SESSION['cart'] as $product) {
				if ($product['id'] == $productId && $product['variantId'] == $variantId) {
					$refresh = true;
                	$preCount = floatval($product['count']);
					$request[] = 'item_' . $product['id'] . '[]=' . $count;
                	$request[] = 'property_' . $product['id'] . '[]=' . $product['propertySetId'];
				} else {
					$request[] = 'item_' . $product['id'] . '[]=' . $product['count'];
					$request[] = 'property_' . $product['id'] . '[]=' . $product['propertySetId'];
				}           
			}
			
			$requestString = implode('&', $request);

			$result = [
				'request' => $requestString,
				'refresh' => $refresh,
				'preVal' => $preCount
			];
        
        	echo json_encode($result);
		} else {
			echo json_encode(['error' => 'Invalid input data']);
		}

        exit;
    }

}