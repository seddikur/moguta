<?php

/**
 * Файл может содержать ряд пользовательских фунций влияющих на работу движка.
 * В данном файле можно использовать собственные обработчики
 * перехватывая функции движка, аналогично работе плагинов.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage File
 */
if (!function_exists('seoMeta')) {
  function seoMeta($args)
  {
    $settings = MG::get('settings');
    $args[0]['title'] = !empty($args[0]['title']) ? $args[0]['title'] : '';
    $title = !empty($args[0]['meta_title']) ? $args[0]['meta_title'] : $args[0]['title'];
    if(URL::isSection(null)) {
        MG::set('metaTitle', $settings['sitename'].' - официальный сайт');
    } else {
        MG::set('metaTitle', $title . ' - ' . $settings['sitename']);
    }
  }
}

mgAddActionOnce('mg_seometa', 'seoMeta', 1);

/*
Этой функцией можно отключать ненужные css и js подключаемые плагинами и движком
mgExcludeMeta(
  array(
   '/mg-plugins/rating/css/rateit.css',
   '/mg-plugins/rating/js/rating.js',
   '/mg-core/script/standard/css/layout.agreement.css'
 )
);
*/

/**
 * Функция возвращает массив с полными ссылками на оригинал и все миниатюры изображений товара
 * <code>
 *   $res = getThumbsFromUrl('product/000/16/product-image.jpg');
 *   viewData($res);
 * </code>
 * @param string $url Ссылка на изображение товара
 * @param string $id id товара
 * @return array
 */
function getThumbsFromUrl($url, $id)
{
  $pathToProductImages = 'uploads/product/' . floor($id / 100) . '00' . '/' . $id . '/';
  $url = str_replace(DS, '/', $url);

  if (strpos($url, '/thumbs/') !== false) {
    $url = str_replace(
      [
        'thumbs/30_',
        'thumbs/70_'
      ],
      '',
      $url
    );
  }

  $tmp = explode('/', $url);
  $imgName = end($tmp);
  $noImageStub = MG::getSetting('noImageStub');
  if (!$noImageStub) {
    $noImageStub = '/uploads/no-img.jpg';
  }
  $noImgUrl = SITE . $noImageStub;

  $orig = (is_file(SITE_DIR . $pathToProductImages . $imgName) ? SITE . '/' . $pathToProductImages . $imgName : $noImgUrl);
  $thumb30 = (is_file(SITE_DIR . $pathToProductImages . 'thumbs/30_' . $imgName) ? SITE . '/' . $pathToProductImages . 'thumbs/30_' . $imgName : $noImgUrl);
  $thumb30_2x = (is_file(SITE_DIR . $pathToProductImages . 'thumbs/2x_30_' . $imgName) ? SITE . '/' . $pathToProductImages . 'thumbs/2x_30_' . $imgName : $thumb30);
  $thumb70 = (is_file(SITE_DIR . $pathToProductImages . 'thumbs/70_' . $imgName) ? SITE . '/' . $pathToProductImages . 'thumbs/70_' . $imgName : $noImgUrl);
  $thumb70_2x = (is_file(SITE_DIR . $pathToProductImages . 'thumbs/2x_70_' . $imgName) ? SITE . '/' . $pathToProductImages . 'thumbs/2x_70_' . $imgName : $thumb70);

  $thumbsArr = [
    'orig' => $orig,
    30 => [
      'main' => $thumb30,
      '2x' => $thumb30_2x,
    ],
    70 => [
      'main' => $thumb70,
      '2x' => $thumb70_2x,
    ]
  ];
  
  if(MG::getSetting('thumbsProduct') == 'false') {
    $thumbsArr = [
        'orig' => $orig,
        30 => [
          'main' => $orig,
          '2x' => $orig,
        ],
        70 => [
          'main' => $orig,
          '2x' => $orig,
        ]
      ];
  }

  return $thumbsArr;
}

function getMainStyleLink() {
// Если включено объединение css/js, то для minify-css.css
  if ((MG::getSetting('cacheCssJs') == true)) {
    $docRoot = URL::getDocumentRoot();
    $controller = str_replace('controllers_', '', MG::get('controller'));
    $dirCache = $docRoot . PATH_TEMPLATE . DS . 'cache' . DS . $controller . DS;
    $decodeDirCache = urldecode($dirCache);
    if (file_exists($decodeDirCache . DS . 'minify-css.css')) {
      $styleDir = $docRoot . PATH_TEMPLATE . DS . 'cache' . DS . $controller . DS . 'minify-css.css';
      return PATH_SITE_TEMPLATE . '/cache/' . $controller . '/minify-css.css?rev=' . filemtime($styleDir);
    }
  } else {
    // Если нет, то для style.css
    return PATH_SITE_TEMPLATE . '/css/style.css';
  }
}

function console_log($var) {
    $str = str_replace('&nbsp;', '', json_encode($var, true));
    echo "<script>window.str={$str};console.log(str);</script>";
}

function deleteCurrentUser(){
  
  $avaibleRoles = [2];
 
  if(!$_SESSION['user'] ||!$_SESSION['user']->id) return false;
  $currentUserId = $_SESSION['user']->id;
  $result = User:: getUserById($currentUserId);
  if(!in_array($result->role, $avaibleRoles)) return false;
  //User::logout(false);//важно редирект фолс иначе перенаправит до удаления
  User::delete($currentUserId);
  return true;
}


function edition(){
  $edition = json_decode(MG::getSetting('currentVersion'), true);
  if (in_array($edition['edition'], array('market', 'lite', 'free'))) {return 1;}
  return 0;
}

/**
 * 
 * Пересобирает массив товаров корзины, создавая уникальные ключи для каждого товара
 * на основе идентификаторов товара и варианта. Возвращает ассоциативный массив с
 * ключами в формате 'id|variantId' и соответствующими данными о количестве и сумме.
 * 
 * Пример:
 * Входные данные:
 * [
 *     0 => ['id' => 1, 'variantId' => 700 'title' => 'Товар 1'],
 *     1 => ['id' => 2, 'variantId' => 701 'title' => 'Товар 1'],
 * ]
 * 
 * Выходные данные:
 * [
 *     '1|700' => ['count' => 2, 'sum' => 500],
 *     '1|701' => ['count' => 1, 'sum' => 250],
 * ]
 * 
 * Если корзина пуста или произошла ошибка, возвращает false.
 * 
 * @return array|bool Возвращает пересобранный массив товаров или false, если данных нет.
 * 
 */
function getProductsInCart() {

    $model = new Models_Cart();
    $cart = $model->getItemsCart();

    if (empty($cart['items'])) return false;

	  $result = [];
    foreach ($cart['items'] as $item) {
		$key = $item['id'] . '|' . $item['variantId'];
        $result[$key] = [
            'count' => $item['countInCart'],
            'sum' => $item['priceInCart'],
			      'vat' => (int) $item['opf_21'],
        ];
    }

    return $result;
}


/**
 * Отладка. Создает файл и добавляет информацию.
 * 
 * @param mixed $data
 * @param string $fileName
 * 
 * @return void
 */
function filePut($data, $fileName = 'data') {
    // Добавляем перенос строки перед записью
    $logData = "\n\n" . print_r($data, true);

    file_put_contents(
        __DIR__ . '/'. $fileName .'.log', 
        $logData, 
        FILE_APPEND
    );
}

/**
 * Возврашает доп. поля для вывода в Email 
 * 
 * @param int $id Идентификатор товара.
 * @param int $variant Идентификатор варианта товара.
 * 
 * @return array|bool
 */
function getProduct($id, $variant) {
  $id = (int) $id;
  $varinat = (int) $variant;

  // opf_21 - доп. поле "Баки".
  if ($varinat)
  {
    $sql = 'SELECT `opf_21` 
              FROM `'. PREFIX .'product_variant` 
            WHERE `id` = '. DB::quoteInt($variant);
  } 
    else
  {
    $sql = 'SELECT `opf_21` 
              FROM `'. PREFIX .'product` 
            WHERE `id` = '. DB::quoteInt($id);
  }

  $query = DB::query($sql);
  if (!$query->num_rows) return false;

  $result = DB::fetchAssoc($query);

  return $result;
}

