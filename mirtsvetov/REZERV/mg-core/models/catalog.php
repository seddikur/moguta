<?php

/**
 * Модель: Catalog
 *
 * Класс Models_Catalog реализует логику работы с каталогом товаров.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 *
 */
class Models_Catalog {

    /**
     * @var array @var mixed Массив с категориями продуктов.
     */
    public $categoryId = array();

    /**
     * @var array @var mixed Массив текущей категории.
     */
    public $currentCategory = array();

    /**
     * @var array @var mixed Фильтр пользователя..
     */
    public $userFilter = array();

    /**
     * Записывает в переменную класса массив содержащий ссылку и название текущей, открытой категории товаров.
     * <code>
     * $catalog = new Models_Catalog;
     * $catalog->getCurrentCategory();
     * </code>
     * @return bool
     */
    public function getCurrentCategory() {
        $result = false;

        $sql = '
      SELECT *
      FROM `' . PREFIX . 'category`
      WHERE id = %d
    ';

        if (end($this->categoryId)) {
            $res = DB::query($sql, end($this->categoryId));
            if ($this->currentCategory = DB::fetchAssoc($res)) {
                $result = true;
            }

        } else {
            $this->currentCategory['url'] = 'catalog';
            $this->currentCategory['title'] = 'Каталог';
            $result = true;
        }

        $args = func_get_args();
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
    }

    public function getList($countRows = 20, $mgadmin = false, $onlyActive = false) {
        if (!$this->getCurrentCategory()) {
            echo 'Ошибка получения данных!';
            exit;
        }

        $args = func_get_args();

        // Испльзуются ли склады
        $storages = MG::enabledStorage();
        // Нужно ли выводить закончившиеся товары
        $checkStock = false;
        // Показывать закончившиеся товары в конце выборки
        $outOfStockToEnd = false;
        // Показывать акционные товары только на соответствующей странице
        $oldPricedOnSalePageOnly = false;


        if (!$mgadmin) {
            if (MG::getSetting('productsOutOfStockToEnd') === 'true') {
                $outOfStockToEnd = true;
            }
            if (MG::getSetting('printProdNullRem') === 'true') {
                $checkStock = true;
            }
            $controller = MG::get('controller');
            if ($controller == 'controllers_catalog' && MG::getSetting('oldPricedOnSalePageOnly') === 'true') {
                $oldPricedOnSalePageOnly = true;
                if (!empty($_GET['sale']) && intval($_GET['sale']) === 1) {
                    $oldPricedOnSalePageOnly = false;
                }
            }
        }

        if (!$mgadmin) {
            $filterData = $this->filterPublic(true, $checkStock);
            $filterHtml = $filterData['filterBarHtml'];
            $filterPropsHtml = $filterData['htmlProp'];
            $filterApplyList = $filterData['applyFilterList'];
            $filterSql = $filterData['userFilter'];

            MG::set('catalogfilter', $filterHtml);

            if (isset($_REQUEST['applyFilter'])) {
                $result = [];

                if (!empty($filterSql)) {
                    $result = $this->getListByUserFilter($countRows, $filterSql);

                    $result['filterBarHtml'] = $filterHtml;
                    $result['htmlProp'] = $filterPropsHtml;
                    $result['applyFilterList'] = $filterApplyList;
                }

                $args = func_get_args();
                return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
            }
        }

        // Определяем номер страницы (по умолчанию - 1)
        $page = intval(URL::get('page'));
        if (!$page) {
            $page = 1;
        }

        // Определяем сортировку
        $sorterField = 'sort';
        $sorterOrder = 'ASC';
        $sorterString = MG::getSetting('filterSort');
        if (!empty($_SESSION['filters']) && is_string($_SESSION['filters']) && !empty($_REQUEST['sorter']) ) {
            $sorterString = $_SESSION['filters'] = $_REQUEST['sorter'];
        }
        $sorterData = explode('|', $sorterString);
        if (!empty($sorterData[0])) {
            $sorterField = $sorterData[0];
        }
        if (intval($sorterData[1]) > 0) {
            $sorterOrder = 'DESC';
        }

        // Корректируем сортировку
        if ($sorterField === 'price_course') {
            $sorterField = 'p.`price_course` + p.`price_course` * IFNULL(c.`rate`, 0)';
        }
        if ($sorterField === 'old_price') {
            $sorterField = 'p.`old_price`';
        }
        if ($sorterField === 'sort') {
            $sorterField = 'p.`sort`';
        }
        if ($sorterField === 'title') {
            $sorterField = 'p.`title`';
        }
        if ($sorterField === 'count') {
            if (MG::enabledStorage()) {
                $sorterField = 'p.`storage_count`';
            } else {
                $sorterField = 'IF(pv.`product_id`, SUM(ABS(pv.`count`)), SUM(ABS(p.`count`)))';
            }
        }

        $orderBy = 'ORDER BY '.$sorterField.' '.$sorterOrder.', p.`id`';

        // Опция показа закончившихся в конце выборки
        if ($outOfStockToEnd && !$checkStock) {
            if ($storages) {
                $orderBy = 'ORDER BY p.`storage_count` != 0 DESC, '.$sorterField.' '.$sorterOrder.', p.`id`';
            } else {
                $orderBy = 'ORDER BY IF(pv.`product_id`, SUM(ABS(pv.`count`)) != 0, p.`count` != 0) DESC, '.$sorterField.' '.$sorterOrder.', p.`id`';
            }
        }

        // Тут строим sql-запрос
        $selectParts = [
            'p.*',
            'p.`id` AS id',
            'CONCAT(c.`parent_url`, c.`url`) AS category_url',
            'IF(c.`unit`, c.`unit`, "шт.") AS category_unit',
            'p.`unit` AS product_unit',
            'c.`weight_unit` AS category_weight_unit',
            'p.`weight_unit` AS product_weight_unit',
            'p.`url` AS product_url',
            'pv.`product_id` AS variant_exist',
            'c.`rate`',
            '(p.`price_course` + p.`price_course` * (IFNULL(c.`rate`, 0))) as price_course',
            'p.`storage_count` AS count_sort',
        ];

        $joinParts = [
            'LEFT JOIN `'.PREFIX.'product_variant` AS pv ON pv.`product_id` = p.`id`',
            'LEFT JOIN `'.PREFIX.'category` AS c ON c.`id` = p.`cat_id`',
        ];

        $whereParts = [];

        // Выборка только активных товаров
        if ($onlyActive) {
            $whereParts[] = 'p.`activity`';
        }

        // Не выбираем акционные товары
        if ($oldPricedOnSalePageOnly) {
            $whereParts[] = 'IF(pv.`id`, IFNULL(pv.`old_price`, 0) <= pv.`price`, IFNULL(p.`old_price`, 0) <= p.`price`)';
        }

        // Выборка только товаров в наличии
        if ($checkStock) {
            if ($storages) {
                $whereParts[] = 'p.`storage_count`';
            } else {
                $whereParts[] = 'IF(pv.`product_id`, ABS(pv.`count`), ABS(p.`count`))';
            }
        }

        $currentCategoryId = 0;
        if (!empty($this->currentCategory['id'])) {
            $currentCategoryId = intval($this->currentCategory['id']);
        }

        $productsInSubCat = MG::getSetting('productInSubcat') === 'true';

        // Если выборка идёт в определенной категории
        if ($currentCategoryId) {
            $additionalCatWherePart = 'FIND_IN_SET('.DB::quoteInt($currentCategoryId, true).', p.`inside_cat`)';
            // Строим соотвествующее условие
            $exactCategoryWherePart = 'p.`cat_id` = '.DB::quoteInt($currentCategoryId);
            // Если включена опция поиска по подкатегориям, то забираем из базы диапозон категорий и ищем по нему
            if ($productsInSubCat) {
                $catsDiaposonSql = 'SELECT `left_key`, `right_key` '.
                    'FROM `'.PREFIX.'category` '.
                    'WHERE `id` = '.DB::quoteInt($currentCategoryId);
                $catsDiaposonResult = DB::query($catsDiaposonSql);
                if ($catsDiaposonRow = DB::fetchAssoc($catsDiaposonResult)) {
                    $minLeftKey = intval($catsDiaposonRow['left_key'] - 1);
                    $maxRightKey = intval($catsDiaposonRow['right_key'] + 1);
                    $exactCategoryWherePart = 'c.`left_key` > '.DB::quoteInt($minLeftKey).' AND '.
                        'c.`right_key` < '.DB::quoteInt($maxRightKey);
                    if (count($this->categoryId) > 1) {
                        $checkCatsTreeSql = 'SELECT `id` '.
                            'FROM `'.PREFIX.'category` '.
                            'WHERE `id` IN ('.DB::quoteIN($this->categoryId).') '.
                                'AND (`left_key` <= '.DB::quoteInt($minLeftKey).' OR `right_key` >= '.DB::quoteInt($maxRightKey).')';
                        $checkCatsTreeResult = DB::query($checkCatsTreeSql);
                        if (DB::fetchAssoc($checkCatsTreeResult)) {
                            $exactCategoryWherePart = 'p.`cat_id` IN ('.DB::quoteIN($this->categoryId).')';
                            $additionalCatWherePart = '1=0';
                        }
                    }
                }
            } else {
                if (count($this->categoryId) > 1) {
                    // Костыль для выгрузки из csv из определенных категорий
                    if ($_POST['csv'] && $_POST['fromAllCats'] && $_POST['fromAllCats'] === 'false') {
                        $exactCategoryWherePart = 'p.`cat_id` IN ('.DB::quoteIN($this->categoryId).')';
                        $additionalCatWherePart = '1=0';
                    } else {
                        $exactCategoryWherePart = 'p.`cat_id` = '.DB::quoteInt($currentCategoryId);
                        $additionalCatWherePart = '1=0';
                    }
                }
            }
            $categoryWherePart = '('.$exactCategoryWherePart.' OR '.$additionalCatWherePart.')';
            $whereParts[] = $categoryWherePart;
        } else {
            if (!$productsInSubCat) {
                $categoryWherePart = 'p.`cat_id` = 0';
                $whereParts[] = $categoryWherePart;
            }
        }

        // Определяем sql-сортировку
        $groupBy = 'GROUP BY p.`id`';

        $joinsSql = implode(' ', $joinParts);
        $whereSql = '';
        if ($whereParts) {
            $whereSql = 'WHERE '.implode(' AND ', $whereParts);
        }
        $countSql = 'SELECT COUNT(DISTINCT p.`id`) AS total_count '.
            'FROM `'.PREFIX.'product` AS p ';
        if (stripos($whereSql, 'c.`') !== false || stripos($whereSql, 'pv.') !== false) {
            $countSql .= $joinsSql;
        }
        $countSql .= $whereSql;

        $productsIds = [];
        $productsIdsSql = 'SELECT p.`id` '.
            'FROM `'.PREFIX.'product` AS p '.
            $joinsSql.' '.
            $whereSql.' '.
            $groupBy.' '.
            $orderBy;

        $userCurrency = '';
        if (!empty($_SESSION['userCurrency'])) {
            $userCurrency = $_SESSION['userCurrency'];
        }

        $cacheKey = 'catalog-'.md5($productsIdsSql.$page.LANG.$userCurrency.URL::getUri());
        $cachedResult = Storage::get($cacheKey);
        if ($cachedResult) {
            return MG::createHook(__CLASS__ . '_' . __FUNCTION__, $cachedResult, $args);
        }

        $navigator = new Navigator($productsIdsSql, $page, $countRows, 6, false, 'page', null, $countSql);
        $productsIdsArray = $navigator->getRowsSql();
        $productsIds = array_map(function($productIdArray) {
            return $productIdArray['id'];
        }, $productsIdsArray);

        $whereSql2 = 'WHERE p.`id` IN ('.DB::quoteIN($productsIds).')';

        $productsSql = 'SELECT '.implode(', ', $selectParts).' '.
            'FROM `'.PREFIX.'product` AS p '.
            $joinsSql.' '.
            $whereSql2.' '.
            $groupBy.' '.
            $orderBy;
        $productsResult = DB::query($productsSql);
        $products = [];
        while ($productsRow = DB::fetchAssoc($productsResult)) {
            $products[] = $productsRow;
        }

        $products = $this->addPropertyToProduct($products, $mgadmin);

        foreach ($products as &$product) {
            MG::loadLocaleData($product['id'], LANG, 'product', $product);
            if (empty($product['category_unit'])) {
                $product['category_unit'] = 'шт.';
            }
            if (!empty($product['variants'])) {
                foreach ($product['variants'] as &$variant) {
                    MG::loadLocaleData($variant['id'], LANG, 'product_variant', $variant);
                }
            }
        }

        $pager = null;
        if ($mgadmin) {
            $pager = $navigator->getPager('forAjax');
        } else {
            $pager = $navigator->getPager();
        }

        $this->addPricesAndStockToProducts($products);

        $totalCount = intval($navigator->getNumRowsSql());

        $result = [
            'catalogItems' => $products,
            'pager' => $pager,
            'totalCountItems' => $totalCount,
        ];

        if (!empty($filterHtml)) {
            $result['filterBarHtml'] = $filterHtml;
        }

        $this->pager = $pager;
        $this->products = $products;

        Storage::save($cacheKey, $result);

        return MG::createHook(__CLASS__ . '_' . __FUNCTION__, $result, $args);
    }

    private function addPricesAndStockToProducts(&$products) {
        if (!$products) {
            return true;
        }

        $storages = MG::enabledStorage();

        $productsIds = [];
        $variantsIds = [];
        reset($products);
        foreach ($products as $product) {
            $productsIds[] = $product['id'];
            if (is_array($product['variants'])) {
                foreach ($product['variants'] as $variant) {
                    $variantsIds[] = $variant['id'];
                }
            }
        }

        $productsPrices = [];
        $variantsPrices = [];
        $productsCount = [];
        $variantsCount = [];

        $productPricesSql = 'SELECT '.
                'p.`id`, '.
                'p.`count`, '.
                'p.`storage_count`, '.
                'p.`price` * (IFNULL(c.`rate`, 0) + 1) AS price, '.
                'p.`price_course` * (IFNULL(c.`rate`, 0) + 1) AS price_course '.
            'FROM `'.PREFIX.'product` AS p '.
            'LEFT JOIN `'.PREFIX.'category` AS c '.
                'ON c.`id` = p.`cat_id` '.
            'WHERE p.`id` IN ('.DB::quoteIN($productsIds).')';
        $productPricesResult = DB::query($productPricesSql);
        while ($productPricesRow = DB::fetchAssoc($productPricesResult)) {
            $productId = intval($productPricesRow['id']);
            $productCount = floatval($productPricesRow['count']);
            if ($storages) {
                $productCount = $productPricesRow['storage_count'];
            }

            $productPrice = MG::numberFormat(MG::convertPrice($productPricesRow['price_course']));
            $productPriceCourse = $productPricesRow['price_course'];
            $productPricesData = [
                'price' => $productPrice,
                'price_course' => $productPriceCourse,
            ];
            $productsPrices[$productId] = $productPricesData;
            $productsCount[$productId] = $productCount;
        }

        $variantsPricesSql = 'SELECT '.
                'pv.`id`, '.
                'pv.`count`, '.
                'pv.`price` * (IFNULL(c.`rate`, 0) + 1) AS price, '.
                'pv.`price_course` * (IFNULL(c.`rate`, 0) + 1) AS price_course, '.
                'pv.`product_id` '.
            'FROM `'.PREFIX.'product_variant` AS pv '.
            'LEFT JOIN `'.PREFIX.'product` AS p '.
                'ON p.`id` = pv.`product_id` '.
            'LEFT JOIN `'.PREFIX.'category` AS c '.
                'ON c.`id` = p.`cat_id` '.
            'WHERE pv.`id` IN ('.DB::quoteIN($variantsIds).')';
        $variantsPricesResult = DB::query($variantsPricesSql);
        while ($variantsPricesRow = DB::fetchAssoc($variantsPricesResult)) {
            $variantId = intval($variantsPricesRow['id']);
            $productId = intval($variantsPricesRow['product_id']);
            $variantPrice = MG::convertPrice($variantsPricesRow['price']);
            $variantPriceCourse = MG::convertPrice($variantsPricesRow['price_course']);
            $variantCount = floatval($variantsPricesRow['count']);
            $variantPricesData = [
                'price' => $variantPrice,
                'price_course' => $variantPriceCourse,
            ];
            $variantsPrices[$productId][$variantId] = $variantPricesData;
            $variantsCount[$productId][$variantId] = $variantCount;
        }

        if ($storages) {
            $productModel = new Models_Product();
            foreach ($productsIds as $productId) {
                if(!empty($variantsCount[$productId])){
                  $storagesVariantsIds = array_keys($variantsCount[$productId]);
                }
                if (empty($storagesVariantsIds)) {
                    continue;
                }
                foreach ($storagesVariantsIds as $variantId) {
                    $variantsCount[$productId][$variantId] = 0;
                }
                $variantsStoragesCounts = $productModel->getStoragesCountsByVariantsIds($storagesVariantsIds);
                if (empty($variantsStoragesCounts)) {
                    continue;
                }
                foreach ($variantsStoragesCounts as $variantId => $storagesCounts) {
                    $variantStoragesCount = 0;
                    foreach ($storagesCounts as $variantStorageCount) {
                        if (floatval($variantStorageCount) === floatval(-1) || floatval($variantStoragesCount) === floatval(-1)) {
                            $variantStoragesCount = -1;
                            break;
                        }
                        $variantStoragesCount += $variantStorageCount;
                    }
                    $variantsCount[$productId][$variantId] = $variantStoragesCount;
                }
            }
        }


        foreach ($products as &$product) {
            $productId = $product['id'];
            $productPrices = $productsPrices[$productId];
            $product['price'] = $productPrices['price'];
            $product['price_course'] = $productPrices['price_course'];
            $product['count'] = $productsCount[$productId];
            if ($product['variants']) {
                foreach ($product['variants'] as &$variant) {
                    $variantId = $variant['id'];
                    $variantPrices = $variantsPrices[$productId][$variantId];
                    $variant['price'] = $variantPrices['price'];
                    $variant['price_course'] = $variantPrices['price_course'];
                    $variant['count'] = $variantsCount[$productId][$variantId];
                }
            }
        }

        return true;
    }

    /**
     * Возвращает список товаров и пейджер для постраничной навигации.
     * <code>
     * $catalog = new Models_Catalog;
     * $items = $catalog->getList(6, false, true);
     * viewData($items);
     * </code>
     * @param int $countRows количество возвращаемых записей для одной страницы.
     * @param bool $mgadmin откуда вызван метод, из публичной части или панели управления.
     * @param bool $onlyActive учитывать только активные продукты.
     * @return array
     */
    public function getListOld($countRows = 20, $mgadmin = false, $onlyActive = false) {
        // Если не удалось получить текущую категорию.
        if (!$this->getCurrentCategory()) {
            echo 'Ошибка получения данных!';
            exit;
        }

        // только для публичной части строим html для фильтров, а если уже пришел запрос с нее, то получаем результат
        if (!$mgadmin) {

            $onlyInCount = false; // ищем все товары
            if(MG::getSetting('printProdNullRem') == "true") {
                $onlyInCount = true; // ищем только среди тех которые есть в наличии
            }
            $filterProduct = $this->filterPublic(true, $onlyInCount);

            MG::set('catalogfilter',$filterProduct['filterBarHtml']);

            // return array('catalogItems'=>null, 'pager'=>null, 'filterBarHtml'=>$filter->getHtmlFilter(true), 'userFilter' => $userFilter);
            // если пришел запрос с фильтра со страницы каталога и не используется плагин фильтров
            if (isset($_REQUEST['applyFilter'])) {

                $result = array();
                if (!empty($filterProduct['userFilter'])) {
                    // если при генерации фильтров был построен запрос
                    // по входящим свойствам товара из  get запроса
                    // то получим все товары  именно по данному запросу, учитывая фильтрацию по характеристикам

                    $result = $this->getListByUserFilter($countRows, $filterProduct['userFilter']);

                    $result['filterBarHtml'] = $filterProduct['filterBarHtml'];
                    $result['htmlProp'] = $filterProduct['htmlProp'];
                    $result['applyFilterList'] = $filterProduct['applyFilterList'];
                }

                $args = func_get_args();
                return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
            }
        }

        // Страница.
        $page = URL::get("page");

        $parts = !empty($_SESSION['filters']) ? explode('|',$_SESSION['filters']) : explode('|',MG::getSetting('filterSort'));
        $PCS = false;
        $priceCourseSort = '';
        if($parts[0] == 'price_course') {
            $priceCourseSort = ',p.price_course AS `price_course_sort`';
            //,IFNULL((SELECT pv.price_course FROM '.PREFIX.'product_variant AS pv WHERE pv.product_id = p.id AND count != 0 ORDER BY pv.price_course ASC LIMIT 1), p.price_course) AS `price_course_sort`
            // отрабатыает в публичной части, необходимо выявить назначение и переписать
            // при наличии большого количества вариантов/категорий просаживает выполнение sql на ~10 сек

            $PCS = true;
        }

        $sql = 'SELECT p.id, CONCAT(c.parent_url,c.url) as category_url,
          c.unit as category_unit, p.unit as product_unit,
          c.weight_unit as category_weightUnit, p.weight_unit as product_weightUnit,
          p.url as product_url, p.*, pv.product_id as variant_exist, rate,
          (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, ';
        
        if (!MG::enabledStorage()) {
            $sql .= 'IF(variantsExists > 0, varcount, IF (p.`count` < 0, 1000000, p.`count`)) AS  `count_sort`, ';
        } else {
            $sql .= '`count_sort`, ';
        }
        $sql .= 'p.currency_iso'.$priceCourseSort.'
        FROM `' . PREFIX . 'product` AS p
        LEFT JOIN `'.PREFIX.'category` AS c
          ON c.id = p.cat_id
        LEFT JOIN `' . PREFIX . 'product_variant` AS pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount,
          COUNT(pv.`id`) as variantsExists
          FROM  `'.PREFIX.'product_variant` AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id';

        if (MG::enabledStorage()) {
            $sql .= ' LEFT JOIN (SELECT `product_id`, SUM(IF(`count` < 0, 1000000, `count`)) as `count_sort` FROM `'.PREFIX.'product_on_storage` GROUP BY `product_id`) AS temp_pos ON p.`id` = temp_pos.`product_id`';
        }



        // FIND_IN_SET - учитывает товары, в настройках которых,
        // указано в каких категориях следует их показывать.
        if (!isset($this->currentCategory['id'])) {
            $this->currentCategory['id'] = 0;
        }

        if (MG::getSetting('productInSubcat')=='true') {
            $filter = '((p.cat_id IN (' .DB::quoteIN($this->categoryId) . ') '
                . 'or FIND_IN_SET(' . DB::quote($this->currentCategory['id'],1) . ',p.`inside_cat`)))';
        } else {
            $filter = '((c.id IN (' . DB::quote($this->currentCategory['id'],1) . ') '
                . 'or FIND_IN_SET(' .  DB::quote($this->currentCategory['id'],1)  . ',p.`inside_cat`)))';
        }

        if ($mgadmin) {
            $filter = ' (p.cat_id IN (' .DB::quote( implode(',', $this->categoryId),1) . ') '
                . 'or FIND_IN_SET(' .  DB::quote($this->currentCategory['id'],1)  . ',p.`inside_cat`))';

            if($this->currentCategory['id'] == 0) {
                $filter = ' 1=1 ';
            }
        }
        // Запрос вернет общее кол-во продуктов в выбранной категории.
        if ($onlyActive) {
            $filter .= ' AND p.activity = 1';
        }
        if (MG::getSetting('printProdNullRem') == "true" && !$mgadmin) {

            if(MG::enabledStorage()) {
                $filter .= ' AND ((SELECT SUM(ABS(count)) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id) != 0)';
            } else {
                // Раньше был такой кусок запроса:
//          $filter .= ' AND (SELECT IF(SUM(ipv.count) != 0, ip.count + SUM(ipv.count), IF(COUNT(ipv.id) != 0, SUM(ipv.count), ip.count)) FROM '.PREFIX.'product AS ip
//                LEFT JOIN '.PREFIX.'product_variant AS ipv ON ip.id = ipv.product_id WHERE ip.id = p.id) != 0';
                //  Теперь такой (для фикса нулевого количества товара: если 2 товара с -1, а один с 2, то они не выводилсь в каталоге):
                $filter .= ' AND IF(p.count <0, 1000000, 
            IF(varcount, 
              IF(p.count<varcount, varcount, round(p.count,2)), 
            round(p.count,2)) IS TRUE
          )';
            }
        }
        $sql .=' WHERE  ' . $filter;

        $orderBy = ' ORDER BY `sort` DESC ';
        if(MG::getSetting('filterSort') && !$mgadmin ) {
            $parts = !empty($_SESSION['filters']) ? explode('|',$_SESSION['filters']) : explode('|',MG::getSetting('filterSort'));
            if (!empty($_SESSION['filters'])) {
                $parts[1] = intval($parts[1]) > 0 ? "DESC" : "ASC";
            }
            $parts[0] = $parts[0]=='count' ? 'count_sort' : $parts[0];
            $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1);
        }
        if($PCS) $orderBy = str_replace('price_course', 'price_course_sort', $orderBy);
        $sql .= ' GROUP BY p.id '.$orderBy;

        // в админке не используем кэш
        $result = null;
        if (!$mgadmin) {
            $result = Storage::get('catalog-'.md5($sql.$page.LANG.(isset($_SESSION['userCurrency'])?$_SESSION['userCurrency']:'').URL::getClearUri()));
        }

        if ($result == null) {
            // узнаем количество товаров для построения навигатора
            $res = DB::query("SELECT count(distinct p.id) AS count
        FROM ".PREFIX."product p
        LEFT JOIN ".PREFIX."category c
          ON c.id = p.cat_id
        LEFT JOIN ".PREFIX."product_variant pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount,
            COUNT(pv.`id`) as variantsExists
          FROM  ".PREFIX."product_variant AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id WHERE ". $filter);
            $maxCount = DB::fetchAssoc($res);
            //определяем класс

            // Опция "Показывать закончившиеся товары в конце
            if (MG::getSetting('productsOutOfStockToEnd') === 'true' && !$mgadmin) {
                if (stripos($sql, 'order by') !== false) {
                    $sql = str_replace('ORDER BY', 'ORDER BY CASE WHEN `count_sort` = 0 THEN 1 WHEN `count_sort` IS NULL THEN 1 ELSE 0 END, ', $sql);
                } elseif (stripos($sql, ' limit ') !== false) {
                    $sql = str_replace(' LIMIT ', ' ORDER BY CASE WHEN `count_sort` = 0 THEN 1 WHEN `count_sort` IS NULL THEN 1 ELSE 0 END LIMIT ', $sql);
                } else {
                    $sql .= ' ORDER BY CASE WHEN `count_sort` = 0 THEN 1 WHEN `count_sort` IS NULL THEN 1 ELSE 0 END';
                }
            }

            // Фикс сортировки Mysql 8+
            if (stripos($sql, ' limit ') !== false) {
                if (stripos($sql, ' order by ') !== false) {
                    $sql = str_replace(' LIMIT ', ', p.`id` ASC LIMIT ', $sql);
                } else {
                    $sql = str_replace(' LIMIT ', ' ORDER BY p.`id` ASC LIMIT ', $sql);
                }
            } else {
                if (stripos($sql, ' order by ') !== false) {
                    $sql .= ', p.`id` ASC';
                } else {
                    $sql .= ' ORDER BY p.`id` ASC';
                }
            }

            $navigator = new Navigator($sql, $page, $countRows, 6, false, 'page', $maxCount['count']);

            $this->products = $navigator->getRowsSql();

            // добавим к полученным товарам их свойства
            $this->products = $this->addPropertyToProduct($this->products, $mgadmin);

            foreach ($this->products as &$item) {
                MG::loadLocaleData($item['id'], LANG, 'product', $item);
                if (!isset($item['category_unit'])) {
                    $item['category_unit'] = 'шт.';
                }
                if (isset($item['product_unit']) && $item['product_unit'] != null && strlen($item['product_unit']) > 0) {
                    $item['category_unit'] = $item['product_unit'];
                }
                if (!empty($item['variants'])) {
                    foreach ($item['variants'] as &$itemVariant) {
                        MG::loadLocaleData($itemVariant['id'], LANG, 'product_variant', $itemVariant);
                    }
                }
            }

            if ($mgadmin) {
                $this->pager = $navigator->getPager('forAjax');
            } else {
                $this->pager = $navigator->getPager();
            }

            $result = array('catalogItems' => $this->products, 'pager' => $this->pager, 'totalCountItems' => $navigator->getNumRowsSql());
            // в админке не используем кэш
            if (!$mgadmin) {
                Storage::save('catalog-'.md5($sql.$page.LANG.(isset($_SESSION['userCurrency'])?$_SESSION['userCurrency']:'').URL::getClearUri()), array('catalogItems' => $this->products, 'pager' => $this->pager, 'totalCountItems' => $navigator->getNumRowsSql()));
            }
        }

        if (!empty($filterProduct['filterBarHtml'])) {
            $result['filterBarHtml'] = $filterProduct['filterBarHtml'];
        }

        // подгружаем цены для каталога
        $ids = null;
        $varIds = null;
        foreach ($result['catalogItems'] as $key => $value) {
            $ids[] = $value['id'];
            if(isset($value['variants']) && is_array($value['variants'])) {
                foreach ($value['variants'] as $var) {
                    $varIds[] = $var['id'];
                }
            }
        }
        $prices = $varPrices = $prodCount = $varCount = array();
        $res = DB::query('SELECT p.`count`, p.id, p.price * (IFNULL(c.rate, 0) + 1) AS price, p.price_course * (IFNULL(c.rate, 0) + 1) AS price_course FROM '.PREFIX.'product AS p
      LEFT JOIN '.PREFIX.'category AS c ON c.id = p.cat_id
      WHERE p.id IN ('.DB::quoteIN($ids).')');
        while($row = DB::fetchAssoc($res)) {
            $prices[$row['id']]['price'] = MG::numberFormat(MG::convertPrice($row['price_course']));
            $prices[$row['id']]['price_course'] = MG::numberFormat($row['price']);
            $prodCount[$row['id']] = $row['count'];
        }
        $res = DB::query('SELECT pv.`count`, pv.id, round(pv.price * (IFNULL(c.rate, 0) + 1), 2) AS price, round(pv.price_course * (IFNULL(c.rate, 0) + 1), 2) AS price_course, pv.product_id 
      FROM '.PREFIX.'product_variant AS pv 
      LEFT JOIN '.PREFIX.'product AS p ON p.id = pv.product_id
      LEFT JOIN '.PREFIX.'category AS c ON c.id = p.cat_id 
      WHERE pv.id IN ('.DB::quoteIN($varIds).')');
        while($row = DB::fetchAssoc($res)) {
            $varPrices[$row['product_id']][$row['id']]['price'] = MG::convertPrice($row['price']);
            $varPrices[$row['product_id']][$row['id']]['price_course'] = MG::convertPrice($row['price_course']);
            $varCount[$row['product_id']][$row['id']] = $row['count'];
        }
        if (MG::enabledStorage()) {
            foreach ($prodCount as $key => $value) {
                $prodCount[$key] = 0;
            }
            foreach ($varCount as $key => $value) {
                foreach ($value as $vkey => $vvalue) {
                    $varCount[$key][$vkey] = 0;
                }
            }

            $res = DB::query("SELECT `product_id`, `variant_id`, `count` FROM `".PREFIX."product_on_storage` WHERE `product_id` IN (".DB::quoteIN($ids).")");
            while ($row = DB::fetchAssoc($res)) {
                if ($row['variant_id'] > 0) {
                    $varCount[$row['product_id']][$row['variant_id']] += $row['count'];
                } else {
                    $prodCount[$row['product_id']] += $row['count'];
                }
            }
        }
        foreach ($result['catalogItems'] as $key => $value) {
            $result['catalogItems'][$key]['price'] = $prices[$value['id']]['price'];
            $result['catalogItems'][$key]['price_course'] = $prices[$value['id']]['price_course'];
            $result['catalogItems'][$key]['count'] = $prodCount[$value['id']];
            if(isset($result['catalogItems'][$key]['variants']) && is_array($result['catalogItems'][$key]['variants'])) {
                foreach ($result['catalogItems'][$key]['variants'] as $vKey => $var) {
                    $result['catalogItems'][$key]['variants'][$vKey]['price'] = $varPrices[$value['id']][$var['id']]['price'];
                    $result['catalogItems'][$key]['variants'][$vKey]['price_course'] = $varPrices[$value['id']][$var['id']]['price_course'];
                    $result['catalogItems'][$key]['variants'][$vKey]['count'] = $varCount[$value['id']][$var['id']];
                }
            }
        }

        $args = func_get_args();

        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
    }

    public function getListByUserFilter($countRows = 20, $userFilter, $mgadmin = false, $noCache = false) {
        $args = func_get_args();

        $userCurrency = '';
        if (!empty($_SESSION['userCurrency'])) {
            $userCurrency = $_SESSION['userCurrency'];
        }

        // Определяем номер страницы (по умолчанию - 1)
        $page = intval(URL::get('page'));
        if (!$page) {
            $page = 1;
        }

        $cacheKey = 'catalog-'.md5($userFilter.LANG.$userCurrency.URL::getUri().$page);
        if (!$noCache && !$mgadmin) {
            $cachedResult = Storage::get($cacheKey);
            if ($cachedResult) {
                return MG::createHook(__CLASS__ . '_' . __FUNCTION__, $cachedResult, $args);
            }
        }

        $userFilter = trim($userFilter);
        // Испльзуются ли склады
        $storages = MG::enabledStorage();
        // Нужно ли выводить закончившиеся товары
        $checkStock = false;
        // Показывать закончившиеся товары в конце выборки
        $outOfStockToEnd = false;
        // Показывать акционные товары только на соответствующей странице
        $oldPricedOnSalePageOnly = false;

        if (!$mgadmin) {
            if (MG::getSetting('productsOutOfStockToEnd') === 'true') {
                $outOfStockToEnd = true;
            }
            if (MG::getSetting('printProdNullRem') === 'true') {
                $checkStock = true;
            }
            $controller = MG::get('controller');
            if ($controller == 'controllers_catalog' && MG::getSetting('oldPricedOnSalePageOnly') === 'true') {
                $oldPricedOnSalePageOnly = true;
                if (!empty($_GET['sale']) && intval($_GET['sale']) === 1) {
                    $oldPricedOnSalePageOnly = false;
                }
            }
        }

        $userFilterCheckStockSubstrings = [
            'AND (p.count>0 OR p.count<0)',
            'AND (p.count != 0 || pv.count != 0)',
        ];
        foreach ($userFilterCheckStockSubstrings as $userFilterCheckStockSubstring) {
            if (stristr($userFilter, $userFilterCheckStockSubstring) !== false) {
                $userFilter = str_replace($userFilterCheckStockSubstring, '', $userFilter);
                $checkStock = true;
                break;
            }
        }

        $having = '';
        if (stristr($userFilter, 'AND (IF(pv.`product_id`, SUM(ABS(pv.`count`)), p.`count`) = 0)')) {
            $userFilter = str_ireplace('AND (IF(pv.`product_id`, SUM(ABS(pv.`count`)), p.`count`) = 0)', '', $userFilter);
            $having = 'HAVING IF(pv.`product_id`, SUM(ABS(pv.`count`)), p.`count`) = 0';
        }

        // Определяем сортировку
        $sorterField = 'sort';
        $sorterOrder = 'ASC';
        $sorterString = MG::getSetting('filterSort');
        if (!empty($_SESSION['filters']) && is_string($_SESSION['filters'])) {
            $sorterString = $_SESSION['filters'];
        }
        $sorterData = explode('|', $sorterString);
        if (!empty($sorterData[0])) {
            $sorterField = $sorterData[0];
        }
        if (intval($sorterData[1]) > 0) {
            $sorterOrder = 'DESC';
        }

        // Корректируем сортировку
        if ($sorterField === 'price_course') {
            $sorterField = 'p.`price_course` + p.`price_course` * IFNULL(c.`rate`, 0)';
        }
        if ($sorterField === 'old_price') {
            $sorterField = 'p.`old_price`';
        }
        if ($sorterField === 'sort') {
            $sorterField = 'p.`sort`';
        }
        if ($sorterField === 'title') {
            $sorterField = 'p.`title`';
        }
        if ($sorterField === 'count') {
            if (MG::enabledStorage()) {
                $sorterField = 'p.`storage_count`';
            } else {
                $sorterField = 'IF(pv.`product_id`, SUM(ABS(pv.`count`)), SUM(ABS(p.`count`)))';
            }
        }

        $userFilter = self::modUserFilterNavigatorSql($userFilter);

        $stringOrderBy = false;
        $orderBy = 'ORDER BY '.$sorterField.' '.$sorterOrder.', p.id';
        $orderByPos = stripos($userFilter, 'order by');
        if ($orderByPos !== false) {
            $userFilterWithoutOrder = substr($userFilter, 0, $orderByPos);
            $userFilterOrder = str_replace($userFilterWithoutOrder, '', $userFilter);
            $userFilter = $userFilterWithoutOrder;
            // Очищаем и пытаемся привести к нормальному виду order by который приходит в метод извне
            $userFilterOrder = preg_replace('/\s+/', ' ', trim($userFilterOrder));
            // TODO
            // Костыль.
            // Если есть некоторые поля товара в сортировке, то явно указываем его для таблицы p (PREFIX_product)
            // Чтобы от этого костыля избавиться, нужно пройтись по всем местам, где вызывается getListByUserFilter и убедиться,
            // что оттуда не приходят товара без таблицы
            $ambiguousFields = [
                'weight',
                'image_url',
                'activity',
                'count_buy',
                'price_course',
                'sort',
                'old_price',
                'title',
                'count',
                'code',
            ];
            $ambiguousFieldsVariations = [];
            $ambiguousFieldsVariationsReplace = [];
            $ambiguousFieldsVariationsReplaceReplace = [];
            foreach ($ambiguousFields as $ambiguousFieldIndex => $ambiguousField) {
                $ambiguousFieldsVariations[] = 'p.`'.$ambiguousField.'`';
                $ambiguousFieldsVariations[] = 'p.'.$ambiguousField.'';
                $ambiguousFieldsVariations[] = '`'.$ambiguousField.'`';
                $ambiguousFieldsVariations[] = ''.$ambiguousField.'';

                $ambiguousFieldsVariationsReplace[] = '#OBPH'.$ambiguousFieldIndex.'#0#';
                $ambiguousFieldsVariationsReplace[] = '#OBPH'.$ambiguousFieldIndex.'#1#';
                $ambiguousFieldsVariationsReplace[] = '#OBPH'.$ambiguousFieldIndex.'#2#';
                $ambiguousFieldsVariationsReplace[] = '#OBPH'.$ambiguousFieldIndex.'#3#';

                $ambiguousFieldsVariationsReplaceReplace[] = 'p.`'.$ambiguousField.'`';
                $ambiguousFieldsVariationsReplaceReplace[] = 'p.`'.$ambiguousField.'`';
                $ambiguousFieldsVariationsReplaceReplace[] = 'p.`'.$ambiguousField.'`';
                $ambiguousFieldsVariationsReplaceReplace[] = 'p.`'.$ambiguousField.'`';
            }
            $userFilterOrder = str_replace($ambiguousFieldsVariations, $ambiguousFieldsVariationsReplace, $userFilterOrder);
            $userFilterOrder = str_replace($ambiguousFieldsVariationsReplace, $ambiguousFieldsVariationsReplaceReplace, $userFilterOrder);

            // TODO
            // Ещё костыль
            // Если в сортировке есть price_course, но нет умножения, значит он не умножается на c.`rate`
            // Чтобы от этого костыля избавиться, нужно пройтись по всем местам, где вызывается getListByUserFilter и убедиться,
            // что оттуда не приходит price_course без умножения на c.rate
            if (stripos($userFilterOrder, 'p.`price_course`') !== false) {
                if (stripos($userFilterOrder, '*') === false) {
                    $userFilterOrder = str_replace('p.`price_course`', 'p.`price_course` + p.`price_course` * IFNULL(c.`rate`, 0)', $userFilterOrder);
                }
            }
            
            $orderBy = str_ireplace('order by', 'ORDER BY', $userFilterOrder);
            $orderBy .= ', p.`id`';
            $stringOrderBy = true;
        }

        // Опция показа закончившихся в конце выборки
        if ($outOfStockToEnd && !$checkStock) {
            if ($stringOrderBy) {
                if ($storages) {
                    $orderBy = str_replace('ORDER BY', 'ORDER BY p.`storage_count` != 0 DESC, ', $orderBy).', p.`id`';
                } else {
                    $orderBy = str_replace('ORDER BY', 'ORDER BY IF(pv.`product_id`, SUM(ABS(pv.`count`)) != 0, p.`count` != 0) DESC, ', $orderBy). ', p.`id`';
                }
            } else {
                if ($storages) {
                    $orderBy = 'ORDER BY p.`storage_count` != 0 DESC, '.$sorterField.' '.$sorterOrder.', p.`id`';
                } else {
                    $orderBy = 'ORDER BY IF(pv.`product_id`, SUM(ABS(pv.`count`)) != 0, p.`count` != 0) DESC, '.$sorterField.' '.$sorterOrder.', p.`id`';
                }
            }
        }

        // Тут конструируется sql-запрос
        $selectParts = [
            'p.*',
            'p.`id` AS id',
            'CONCAT(c.`parent_url`, c.`url`) AS category_url',
            'IF(c.`unit`, c.`unit`, "шт.") AS category_unit',
            'p.`unit` AS product_unit',
            'c.`weight_unit` AS category_weight_unit',
            'p.`weight_unit` AS product_weight_unit',
            'p.`url` AS product_url',
            'pv.`product_id` AS variant_exist',
            'c.`rate`',
            '(p.`price_course` + p.`price_course` * (IFNULL(c.`rate`, 0))) as price_course',
            'p.`storage_count` AS count_sort',
        ];

        $joinParts = [
            'LEFT JOIN `'.PREFIX.'product_variant` AS pv ON pv.`product_id` = p.`id`',
            'LEFT JOIN `'.PREFIX.'category` AS c ON c.`id` = p.`cat_id`',
        ];

        $trimmedUserFilterSql = preg_replace(
            [
                '/^\s*and\s+/i',
                '/\s+and\s*$/i',
            ],
            '',
            $userFilter
        );
        $whereParts = [
            $trimmedUserFilterSql,
        ];

        // Не выбираем акционные товары
        if ($oldPricedOnSalePageOnly) {
            $whereParts[] = 'IF(pv.`id`, IFNULL(pv.`old_price`, 0) < pv.`price`, IFNULL(p.`old_price`, 0) < p.`price`)';
        }

        if (!empty($_REQUEST['sale'])) {
            $whereParts[] = '(p.`old_price` > p.`price_course` OR pv.`old_price` > pv.`price_course`)';
        }

        // Выборка только товаров в наличии
        if ($checkStock) {
            if ($storages) {
                $whereParts[] = 'p.`storage_count`';
            } else {
                $whereParts[] = 'IF(pv.`product_id`, ABS(pv.`count`), ABS(p.`count`))';
            }
        }

        // Определяем sql-сортировку
        $groupBy = 'GROUP BY p.`id`';

        $joinsSql = implode(' ', $joinParts);
        $whereSql = '';
        if ($whereParts) {
            $whereSql = 'WHERE '.implode(' AND ', $whereParts);
        }
        $countSql = 'SELECT COUNT(DISTINCT p.`id`) AS total_count '.
            'FROM `'.PREFIX.'product` AS p '.
            $joinsSql.' '.
            $whereSql;
        if ($having) {
            $countSql = null;
        }

        $productsIds = [];
        $productsIdsSql = 'SELECT p.`id` ';
        if ($having) {
            $productsIdsSql = 'SELECT p.`id`, pv.`product_id`, pv.`count`, p.`count` ';
        }
        $productsIdsSql .= 'FROM `'.PREFIX.'product` AS p '.
            $joinsSql.' '.
            $whereSql.' '.
            $groupBy.' ';
        if ($having) {
            $productsIdsSql .= $having.' ';
        }
        $productsIdsSql .= $orderBy;

        $navigator = new Navigator($productsIdsSql, $page, $countRows, 6, false, 'page', null, $countSql);
        $productsIdsArray = $navigator->getRowsSql();
        $productsIds = array_map(function($productIdArray) {
            return $productIdArray['id'];
        }, $productsIdsArray);

        $whereSql2 = 'WHERE p.`id` IN ('.DB::quoteIN($productsIds).')';

        $productsSql = 'SELECT '.implode(', ', $selectParts).' '.
            'FROM `'.PREFIX.'product` AS p '.
            $joinsSql.' '.
            $whereSql2.' '.
            $groupBy.' '.
            $orderBy;
        $productsResult = DB::query($productsSql);
        $products = [];
        while ($productsRow = DB::fetchAssoc($productsResult)) {
            $products[] = $productsRow;
        }

        $products = $this->addPropertyToProduct($products, $mgadmin);

        foreach ($products as &$product) {
            MG::loadLocaleData($product['id'], LANG, 'product', $product);
            if (empty($product['category_unit'])) {
                $product['category_unit'] = 'шт.';
            }
            if (!empty($product['variants'])) {
                foreach ($product['variants'] as &$variant) {
                    MG::loadLocaleData($variant['id'], LANG, 'product_variant', $variant);
                }
            }
        }

        $pager = null;
        if ($mgadmin) {
            $pager = $navigator->getPager('forAjax');
        } else {
            $pager = $navigator->getPager();
        }

        $this->addPricesAndStockToProducts($products);

        $totalCount = intval($navigator->getNumRowsSql());

        $result = [
            'catalogItems' => $products,
            'pager' => $pager,
            'totalCountItems' => $totalCount,
        ];

        if (!empty($filterHtml)) {
            $result['filterBarHtml'] = $filterHtml;
        }

        $this->pager = $pager;
        $this->products = $products;

        if (!$noCache && !$mgadmin) {
            Storage::save($cacheKey, $result);
        }

        return MG::createHook(__CLASS__ . '_' . __FUNCTION__, $result, $args);
    }

    /**
     * Получает список продуктов в соответствии с выбранными параметрами фильтра.
     * <code>
     * $catalog = new Models_Catalog;
     * $result = $catalog->getListByUserFilter(20, ' p.cat_id IN  (1,2,3)');
     * viewData($result);
     * </code>
     * @param int $countRows количество записей.
     * @param string $userfilter пользовательская составляющая для запроса.
     * @param bool $mgadmin админка.
     * @param bool $noCache не использовать кэш.
     * @return array
     */
    public function getListByUserFilterOld($countRows = 20, $userfilter, $mgadmin = false, $noCache = false) {
        $cache = false;
        if(!MG::isAdmin() || $noCache) $cache = Storage::get('catalog-'.md5(URL::getUri()));
        if(!$cache) {
            // Вычисляет общее количество продуктов.
            // в запросе меняем условие по количеству товаров в таблице product
            // затем добавляем условие по количеству вариантов и товаров
            $having = '';
            if (
                stristr($userfilter, 'AND (p.count>0 OR p.count<0)')!==FALSE ||
                (!$mgadmin && MG::getSetting('printProdNullRem') == "true")
            ) {
                $userfilter = str_replace([
                    'AND (p.count>0 OR p.count<0)',
                    'AND (p.count != 0 || pv.count != 0)',
                ], ' ', $userfilter);

                if(MG::enabledStorage()) {
                    $having = ' AND ((IFNULL(ABS((SELECT SUM(ABS(count)) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id)), 0)
           + ABS((SELECT SUM(ABS(count)) FROM '.PREFIX.'product_on_storage WHERE product_id = p.id))) > 0)';
                } else {
                    $having = 'HAVING(SUM(IFNULL(ABS(pv.count), 0) + ABS(p.count)) > 0)';

                }
            }
            // Костыль, если идёт запрос из админки с фильтром "Нет в наличии"
            // То переделываем стандартное условие (которое не работает с group by) на having
            if (stristr($userfilter, 'AND (SUM(ABS(p.count) + ABS(IFNULL(pv.count, 0))) = 0)') !== false) {
                $userfilter = str_replace('AND (SUM(ABS(p.count) + ABS(IFNULL(pv.count, 0))) = 0)', ' ', $userfilter);
                if (MG::enabledStorage()) {
                    $having = ' AND IFNULL((SELECT SUM(ABS(IFNULL(count, 0))) FROM `'.PREFIX.'product_on_storage` WHERE product_id = p.id), 0) = 0';
                } else {
                    $having = 'HAVING((SUM(ABS(IFNULL(pv.count, 0))) + ABS(p.count)) = 0)';
                }
            }
            if(isset($_REQUEST['sale']) && $_REQUEST['sale']) {
                $userfilter = ' (p.old_price>p.price_course OR pv.old_price>pv.price_course) AND '.$userfilter;
            }

            $parts = !empty($_SESSION['filters']) ? explode('|',$_SESSION['filters']) : explode('|',MG::getSetting('filterSort'));
            $PCS = false;
            $priceCourseSort = '';
            if($parts[0] == 'price_course') {
                $priceCourseSort = ',p.price_course AS `price_course_sort`';
                //,IFNULL((SELECT pv.price_course FROM '.PREFIX.'product_variant AS pv WHERE pv.product_id = p.id AND count != 0 ORDER BY pv.price_course ASC LIMIT 1), p.price_course) AS `price_course_sort`
                // отрабатыает в административной части, необходимо выявить назначение и переписать
                // при наличии большого количества вариантов/категорий просаживает выполнение sql на ~7 сек
                $PCS = true;
            }

            $userfilter = self::modUserFilterNavigatorSql($userfilter);

            // Запрос вернет общее кол-во продуктов в выбранной категории.
            $sql = '
        SELECT DISTINCT p.id, CONCAT(c.parent_url,c.url) AS category_url, 
          c.unit AS category_unit, p.unit AS product_unit,
          c.weight_unit AS category_weightUnit, p.weight_unit AS product_weightUnit,
          p.url AS product_url, p.*, pv.product_id AS variant_exist, rate,
          (p.price_course + p.price_course * (IFNULL(rate,0))) AS `price_course`, ';

          if (!MG::enabledStorage()) {
            $sql .= 'IF(variantsExists > 0, varcount, IF (p.`count` < 0, 1000000, p.`count`))
           AS  `count_sort`, ';
          } else {
            $sql .= '`count_sort`, ';
          }
          
          $sql .= 'p.currency_iso,
          IF(IFNULL(c.url, "") = "" AND p.cat_id <> 0, -10, p.cat_id) AS cat_id'.$priceCourseSort.'
        FROM `' . PREFIX . 'product` p
        LEFT JOIN `' . PREFIX . 'category` c
          ON c.id = p.cat_id
        LEFT JOIN `' . PREFIX . 'product_variant` pv
          ON p.id = pv.product_id
        LEFT JOIN (
          SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, round(pv.count,2))) AS varcount,
          COUNT(pv.`id`) AS variantsExists
          FROM  `' . PREFIX . 'product_variant` AS pv
          GROUP BY pv.product_id
        ) AS temp ON p.id = temp.product_id ';

        if (MG::enabledStorage()) {
            $sql .= ' LEFT JOIN (SELECT `product_id`, SUM(IF(`count` < 0, 1000000, `count`)) as `count_sort` FROM `'.PREFIX.'product_on_storage` GROUP BY `product_id`) AS temp_pos ON p.`id` = temp_pos.`product_id` ';
        }

       $sql .= 'WHERE  '.(MG::enabledStorage() ? '1=1'.$having.' AND '.$userfilter : str_replace('ORDER BY', ' GROUP BY p.id '.$having.' ORDER BY', $userfilter));

            $sql = str_replace('ORDER BY `count`', 'ORDER BY `count_sort`', $sql);
            if($PCS) $sql = str_replace('ORDER BY `price_course`', 'ORDER BY `price_course_sort`', $sql);

            $userfilterCount = explode('ORDER BY', $userfilter);
            $userfilterCount = $userfilterCount[0];

            $countSubSql = 'SELECT
                    DISTINCT p.id,
                    p.count,
                    CONCAT(c.parent_url,c.url) AS category_url,
                    c.unit AS category_unit,
                    p.unit AS product_unit,
                    c.weight_unit AS category_weightUnit,
                    p.weight_unit AS product_weightUnit,
                    p.url AS product_url, 
                    pv.product_id AS variant_exist,
                    rate,
                    (p.price_course + p.price_course * (IFNULL(rate,0))) AS `price_course`,
                    IF(variantsExists > 0, varcount, IF (p.`count` < 0, 1000000, p.`count`)) AS  `count_sort`,
                    p.currency_iso,
                    IF(IFNULL(c.url, "") = "" AND p.cat_id <> 0, -10, p.cat_id) AS cat_id' .
                    $priceCourseSort . '
                FROM `' . PREFIX . 'product` as p
                LEFT JOIN `' . PREFIX . 'category` as c
                ON c.id = p.cat_id
                LEFT JOIN `' . PREFIX . 'product_variant` as pv
                ON p.id = pv.product_id
                LEFT JOIN (
                SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, round(pv.count,2))) AS varcount,
                COUNT(pv.`id`) AS variantsExists
                FROM  `' . PREFIX . 'product_variant` AS pv
                GROUP BY pv.product_id
                ) AS temp ON p.id = temp.product_id 
                WHERE  ' . (MG::enabledStorage() ? '1=1' . $having . ' AND ' . $userfilter : str_replace('ORDER BY', ' GROUP BY p.id ' . $having . ' ORDER BY', $userfilter));
            $countSubSql = preg_replace('/ORDER BY.*$/is', '', $countSubSql);

            $countSql = 'SELECT COUNT(*) AS count FROM ('.$countSubSql.') as countTable';

            $res = DB::query($countSql);
            $count = DB::fetchAssoc($res);
            $page = URL::get("page");
            // Опция "Показывать закончившиеся товары в конце
            if (MG::getSetting('productsOutOfStockToEnd') === 'true' && !$mgadmin) {
                if (stripos($sql, 'order by') !== false) {
                    $sql = str_replace('ORDER BY', 'ORDER BY CASE WHEN `count_sort` = 0 THEN 1 WHEN `count_sort` IS NULL THEN 1 ELSE 0 END, ', $sql);
                } elseif (stripos($sql, ' limit ') !== false) {
                    $sql = str_replace(' LIMIT ', ' ORDER BY CASE WHEN `count_sort` = 0 THEN 1 WHEN `count_sort` IS NULL THEN 1 ELSE 0 END LIMIT ', $sql);
                } else {
                    $sql .= ' ORDER BY CASE WHEN `count_sort` = 0 THEN 1 WHEN `count_sort` IS NULL THEN 1 ELSE 0 END';
                }
            }

            // Фикс сортировки Mysql 8+
            if (stripos($sql, ' limit ') !== false) {
                if (stripos($sql, ' order by ') !== false) {
                    $sql = str_replace(' LIMIT ', ', p.`id` ASC LIMIT ', $sql);
                } else {
                    $sql = str_replace(' LIMIT ', ' ORDER BY p.`id` ASC LIMIT ', $sql);
                }
            } else {
                if (stripos($sql, ' order by ') !== false) {
                    $sql .= ', p.`id` ASC';
                } else {
                    $sql .= ' ORDER BY p.`id` ASC';
                }
            }

            $navigator = new Navigator($sql, $page, $countRows, 6, false, 'page', $count['count']); //определяем класс.
            $this->products = $navigator->getRowsSql();
            //
            if ($mgadmin) {
                $this->pager = $navigator->getPager('forAjax');
            } else {
                $this->pager = $navigator->getPager();
            }
            //
            // добавим к полученным товарам их свойства
            $this->products = $this->addPropertyToProduct($this->products, $mgadmin);

            foreach ($this->products as &$item) {
                MG::loadLocaleData($item['id'], LANG, 'product', $item);
                if (!isset($item['category_unit'])) {
                    $item['category_unit'] = 'шт.';
                }
                if (isset($item['product_unit']) && $item['product_unit'] != null && strlen($item['product_unit']) > 0) {
                    $item['category_unit'] = $item['product_unit'];
                }
            }
            //
            $data['products'] = $this->products;
            $data['count'] = $productCount = $navigator->getNumRowsSql();
            $data['pager'] = $this->pager;
            if(!MG::isAdmin() && MG::get('controller')!="controllers_compare" && $noCache) Storage::save('catalog-'.md5(URL::getUri()), $data);
        } else {
            $this->products = $cache['products'];
            $productCount = $cache['count'];
            $this->pager = $cache['pager'];
        }

        // добавляем к товарам со складов инфу, если надо
        $ids = array();
        foreach ($this->products as $value) {
            $ids[] = $value['id'];
        }
        if(MG::enabledStorage()) {
            $res = DB::query('SELECT round(SUM(count),2) as `SUM(count)`, product_id FROM '.PREFIX.'product_on_storage WHERE product_id IN ('.DB::quoteIN($ids).') GROUP BY `product_id`');
            while($row = DB::fetchAssoc($res)) {
                $data[$row['product_id']] = $row['SUM(count)'];
            }
        } else {
            $res = DB::query('SELECT round(SUM(IFNULL(pv.`count`,0) + p.`count`),2) AS count, p.id FROM '.PREFIX.'product AS p
        LEFT JOIN '.PREFIX.'product_variant AS pv ON pv.product_id = p.id WHERE p.id IN ('.DB::quoteIN($ids).') GROUP BY p.id');
            while($row = DB::fetchAssoc($res)) {
                $data[$row['id']] = $row['count'];
            }
        }
        foreach ($this->products as $key => $value) {
            // округление на php
            $this->products[$key]['count'] = empty($data[$value['id']])?0:round($data[$value['id']],2);
            // $this->products[$key]['count'] = empty($data[$value['id']])?0:$data[$value['id']];

        }

        // подгружаем цены для каталога
        $prices = array();
        $res = DB::query('SELECT p.id, p.price * (IFNULL(c.rate, 0) + 1) AS price, p.price_course * (IFNULL(c.rate, 0) + 1) AS price_course FROM '.PREFIX.'product AS p
      LEFT JOIN '.PREFIX.'category AS c ON c.id = p.cat_id
      WHERE p.id IN ('.DB::quoteIN($ids).')');
        while($row = DB::fetchAssoc($res)) {
            $prices[$row['id']]['price'] = MG::numberFormat(MG::convertPrice($row['price_course']));
            $prices[$row['id']]['price_course'] = $row['price_course'];
        }
        // $res = DB::query('SELECT id, price, price_course FROM '.PREFIX.'product WHERE id IN ('.DB::quoteIN($ids).')');
        // while($row = DB::fetchAssoc($res)) {
        //   $prices[$row['id']]['price'] = $row['price'];
        //   $prices[$row['id']]['price_course'] = $row['price_course'];
        // }
        foreach ($this->products as $key => $value) {
            if (isset($prices[$value['id']])) {
                $this->products[$key]['price'] = $prices[$value['id']]['price'];
                $this->products[$key]['price_course'] = $prices[$value['id']]['price_course'];
            }
        }

        $result = array('catalogItems' => $this->products, 'pager' => $this->pager, 'totalCountItems' => $productCount);

        // Костыль. Если у нас в оптовой цене прописано правило для товара кол-ва 1, то подставляетм их.
        foreach($result['catalogItems'] as &$product){
            if(!empty($product['variant_exist'])){
                $sqlWholesale = 'SELECT product_id, variant_id, price FROM '.PREFIX.'wholesales_sys 
                    WHERE product_id = '.DB::quoteIN($product['id']).' AND
                    variant_id = '.DB::quoteIN($product['variants'][0]['id']).' AND `count` = 1 AND `group` = '.User::access('wholesales');
            }else{
                $sqlWholesale = 'SELECT product_id, variant_id, price FROM '.PREFIX.'wholesales_sys 
                    WHERE product_id = '.DB::quoteIN($product['id']).' AND 
                    variant_id = 0 AND `count` = 1 AND `group` = '.User::access('wholesales');
            }
            if($res = DB::query($sqlWholesale)){
                $row = DB::fetchAssoc($res);
                if(!empty($row)){
                    $product['price'] = $row['price'];
                }
            }
        }
        $args = func_get_args();
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
    }

    public function getListProductByKeyWord($rawKeyword, $allRow = false, $onlyActive = false, $mgadmin = false, $mode = false, $forcedPage = false, $searchCats = -1)
    {
        $args = func_get_args();

        $userCurrency = '';
        if (!empty($_SESSION['userCurrency'])) {
            $userCurrency = $_SESSION['userCurrency'];
        }

        $cacheKeyParts = [
            URL::getUri(),
            $rawKeyword,
            $allRow ? 1 : 0,
            $onlyActive ? 1 : 0,
            $forcedPage ? $forcedPage : 0,
            $searchCats,
            $userCurrency,
            LANG
        ];
        $cacheKey = 'catalog-'.md5(implode('_', $cacheKeyParts));
        if (!$mgadmin) {
            $cachedResult = Storage::get($cacheKey);
            if ($cachedResult) {
                return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $cachedResult, $args);
            }
        }
        $result = [
            'catalogItems' => [],
            'pager' => null,
            'numRows' => null,
        ];

        // Испльзуются ли склады
        $storages = MG::enabledStorage();
        // Нужно ли выводить закончившиеся товары
        $checkStock = false;
        // Показывать закончившиеся товары в конце выборки
        $outOfStockToEnd = false;

        if (!$mgadmin) {
            if (MG::getSetting('productsOutOfStockToEnd') === 'true') {
                $outOfStockToEnd = true;
            }
            if (MG::getSetting('printProdNullRem') === 'true') {
                $checkStock = true;
            }
        }

        // Определяем номер страницы (по умолчанию - 1)
        $page = intval(URL::get('page'));
        if (!$page) {
            $page = 1;
        }
        if ($forcedPage) {
            $page = intval($forcedPage);
        }

        $encodedKeyword = htmlspecialchars($rawKeyword);
        $keyword = trim($encodedKeyword);
        
        $searchLang = '';
        if (
            defined('LANG') &&
            LANG != 'LANG' &&
            LANG != 'default'
        ) {
            $searchLang = LANG;
        }

        $searchType = MG::getSetting('searchType');
        if ($searchType === 'fulltext' && !MG::accessFullTextSearch()) {
            $searchType = 'like';
        }

        // Определяем сортировку
        $sorterField = 'sort';
        $sorterOrder = 'ASC';
        $sorterString = MG::getSetting('filterSort');
        if (!empty($_SESSION['filters']) && is_string($_SESSION['filters'])) {
            $sorterString = $_SESSION['filters'];
        }
        $sorterData = explode('|', $sorterString);
        if (!empty($sorterData[0])) {
            $sorterField = $sorterData[0];
        }
        if (intval($sorterData[1]) > 0) {
            $sorterOrder = 'DESC';
        }

        // Корректируем сортировку
        if ($sorterField === 'price_course') {
            $sorterField = 'p.`price_course` + p.`price_course` * IFNULL(c.`rate`, 0)';
        }
        if ($sorterField === 'old_price') {
            $sorterField = 'p.`old_price`';
        }
        if ($sorterField === 'sort') {
            $sorterField = 'p.`sort`';
        }
        if ($sorterField === 'title') {
            $sorterField = 'p.`title`';
        }
        if ($sorterField === 'count') {
            if (MG::enabledStorage()) {
                $sorterField = 'p.`storage_count`';
            } else {
                $sorterField = 'IF(pv.`product_id`, SUM(ABS(pv.`count`)), SUM(ABS(p.`count`)))';
            }
        }

        $orderBy = 'ORDER BY '.$sorterField.' '.$sorterOrder.', p.`id`';

        // Опция показа закончившихся в конце выборки
        if ($outOfStockToEnd && !$checkStock) {
            if ($storages) {
                $orderBy = 'ORDER BY p.`storage_count` != 0 DESC, '.$sorterField.' '.$sorterOrder.', p.`id`';
            } else {
                $orderBy = 'ORDER BY IF(pv.`product_id`, SUM(ABS(pv.`count`)) != 0, p.`count` != 0) DESC, '.$sorterField.' '.$sorterOrder.', p.`id`';
            }
        }

        // Тут конструируется sql-запрос
        $selectParts = [
            'p.*',
            'p.`id` AS id',
            'CONCAT(c.`parent_url`, c.`url`) AS category_url',
            'IF(c.`unit`, c.`unit`, "шт.") AS category_unit',
            'p.`unit` AS product_unit',
            'c.`weight_unit` AS category_weight_unit',
            'p.`weight_unit` AS product_weight_unit',
            'p.`url` AS product_url',
            'pv.`product_id` AS variant_exist',
            'c.`rate`',
            '(p.`price_course` + p.`price_course` * (IFNULL(c.`rate`, 0))) as price_course',
            'p.`storage_count` AS count_sort',
        ];

        $searchJoinParts = $joinParts = [
            'LEFT JOIN `'.PREFIX.'product_variant` AS pv ON pv.`product_id` = p.`id`',
            'LEFT JOIN `'.PREFIX.'category` AS c ON c.`id` = p.`cat_id`',
        ];

        $searchWhereParts = [];

        if (!$mgadmin || $onlyActive) {
            $searchWhereParts[] = 'p.`activity`';
        }

        if ($searchCats > -1) {
            $searchWhereParts[] = 'p.`cat_id` = '.DB::quoteInt($searchCats);
        }

        // Выборка только товаров в наличии
        if ($checkStock) {
            if ($storages) {
                $searchWhereParts[] = 'p.`storage_count`';
            } else {
                $searchWhereParts[] = 'IF(pv.`product_id`, ABS(pv.`count`), ABS(p.`count`))';
            }
        }

        switch ($searchType) {
            case 'sphinx':
                $sphinxProductsIds = [];
                $sphinxProductsIds = $this->getSphinxProductsIds($keyword);
                $searchWhereParts[] = 'p.`id` IN ('.DB::quoteIN($sphinxProductsIds).')';
                if ($sphinxProductsIds) {
                    if ($outOfStockToEnd) {
                        if ($storages) {
                            $orderBy = 'ORDER BY p.`storage_count` != 0 DESC, FIELD(p.`id`, '.implode(', ', $sphinxProductsIds).')';
                        } else {
                            $orderBy = 'ORDER BY IF(pv.`product_id`, SUM(ABS(pv.`count`)) != 0, p.`count` != 0) DESC, FIELD(p.`id`, '.implode(', ', $sphinxProductsIds).')';
                        }
                    } else {
                        $orderBy = 'ORDER BY FIELD(p.`id`, '.implode(', ', $sphinxProductsIds).')';
                    }
                }
                break;
            case 'like':
            default:
                $keywordsArray = explode(' ', $keyword);
                $newKeyword = '%'.implode('%', $keywordsArray).'%';

                $whereOrParts = [];
                if ($searchLang) {
                    $searchJoinParts[] = 'LEFT JOIN `'.PREFIX.'locales` AS lp ON p.`id` = lp.`id_ent`';
                    $searchJoinParts[] = 'LEFT JOIN `'.PREFIX.'locales` AS lv ON pv.`id` = lv.`id_ent`';
                    $whereLpParts = [];
                    $whereLpParts[] = 'lp.`locale` = '.DB::quote($searchLang);
                    $whereLpParts[] = 'lp.`table` = "product"';
                    $whereLpParts[] = 'lp.`field` = "title"';
                    $whereLpParts[] = 'lp.`text` LIKE '.DB::quote($newKeyword);
                    $whereLvParts = [];
                    $whereLvParts[] = 'lv.`locale` = '.DB::quote($searchLang);
                    $whereLvParts[] = 'lv.`table` = "product_variant"';
                    $whereLvParts[] = 'lv.`field` = "title_variant"';
                    $whereLvParts[] = 'lv.`text` LIKE '.DB::quote($newKeyword);
                    $whereLp = '('.implode(' AND ', $whereLpParts).')';
                    $whereLv = '('.implode(' AND ', $whereLvParts).')';
                    $whereOrParts[] = $whereLp;
                    $whereOrParts[] = $whereLv;
                }

                $searchInDefaultLang = MG::getSetting('searchInDefaultLang') === 'true';
                if (!$searchLang || $searchInDefaultLang) {
                    $whereOrParts[] = 'p.`title` LIKE '.DB::quote($newKeyword);
                    $whereOrParts[] = 'p.`code` LIKE '.DB::quote($newKeyword);
                    $whereOrParts[] = 'pv.`title_variant` LIKE '.DB::quote($newKeyword);
                    $whereOrParts[] = 'pv.`code` LIKE '.DB::quote($newKeyword);
                }
                $searchWhereParts[] = '('.implode(' OR ', $whereOrParts).')';
            break;
        }

        // Определяем sql-сортировку
        $groupBy = 'GROUP BY p.`id`';

        $searchJoinsSql = implode(' ', $searchJoinParts);
        $searchWhereSql = '';
        if ($searchWhereParts) {
            $searchWhereSql = 'WHERE '.implode(' AND ', $searchWhereParts);
        }
        $countSql = 'SELECT COUNT(DISTINCT p.`id`) AS total_count '.
            'FROM `'.PREFIX.'product` AS p '.
            $searchJoinsSql.' '.
            $searchWhereSql;

        $productsIds = [];
        $productsIdsSql = 'SELECT p.`id` '.
            'FROM `'.PREFIX.'product` AS p '.
            $searchJoinsSql.' '.
            $searchWhereSql.' '.
            $groupBy.' '.
            $orderBy;

        $countRows = 20;
        $countRowsSetting = MG::getSetting('countСatalogProduct');
        if ($mgadmin) {
            $countRowsSetting = MG::getSetting('countPrintRowsProduct');
        }
        if ($countRowsSetting) {
            $countRows = $countRowsSetting;
        }

        $navigator = new Navigator($productsIdsSql, $page, $countRows, 6, false, 'page', null, $countSql);
        $productsIdsArray = $navigator->getRowsSql();
        $productsIds = array_map(function($productIdArray) {
            return $productIdArray['id'];
        }, $productsIdsArray);

        $joinsSql = implode(' ', $joinParts);
        $whereSql = 'WHERE p.`id` IN ('.DB::quoteIN($productsIds).')';

        $orderBy = 'ORDER BY FIELD(p.`id`, '.DB::quoteIN($productsIds).')';

        $productsSql = 'SELECT '.implode(', ', $selectParts).' '.
            'FROM `'.PREFIX.'product` AS p '.
            $joinsSql.' '.
            $whereSql.' '.
            $groupBy.' '.
            $orderBy;
        $productsResult = DB::query($productsSql);
        $products = [];
        while ($productsRow = DB::fetchAssoc($productsResult)) {
            $products[] = $productsRow;
        }

        $products = $this->addPropertyToProduct($products, $mgadmin);

        foreach ($products as &$product) {
            MG::loadLocaleData($product['id'], LANG, 'product', $product);
            if (empty($product['category_unit'])) {
                $product['category_unit'] = 'шт.';
            }
            if (!empty($product['variants'])) {
                foreach ($product['variants'] as &$variant) {
                    MG::loadLocaleData($variant['id'], LANG, 'product_variant', $variant);
                }
            }
        }

        $pager = null;
        if ($mgadmin) {
            $pager = $navigator->getPager('forAjax');
        } else {
            $pager = $navigator->getPager();
        }

        $this->addPricesAndStockToProducts($products);

        $totalCount = intval($navigator->getNumRowsSql());

        // Постсортировка
        if (count($products) > 0) {
            // упорядочивание списка найденных  продуктов
            // первыми в списке будут стоять те товары, у которых полностью совпала поисковая фраза
            // затем будут слова в начале которых встретилось совпадение
            // в конце слова в середине которых встретилось совпадение
            $keyword = str_replace('*', '', $keyword);
            $keyword = mb_convert_case($keyword, MB_CASE_LOWER, "UTF-8");
            $resultTemp = $products;
            $prioritet0 = [];
            $prioritet1 = [];
            $prioritet2 = [];
            $prioritet3 = [];
            foreach ($resultTemp as $item) {
                $title = mb_convert_case($item['title'], MB_CASE_LOWER, "UTF-8");
                $item['image_url'] = mgImageProductPath($item["image_url"], $item['id']);

                if ($outOfStockToEnd && floatval($item['count']) === floatval(0)) {
                    $prioritet3[] = $item;
                    continue;
                }

                if (trim($title) == $keyword) {
                    $prioritet0[] = $item;
                    continue;
                }

                if (strpos($title, $keyword) === 0) {
                    $prioritet1[] = $item;
                } else {
                    $prioritet2[] = $item;
                }
            }

            $products = array_merge($prioritet0,  $prioritet1, $prioritet2, $prioritet3);
        }

        $result = [
            'catalogItems' => $products,
            'pager' => $pager,
            'numRows' => $totalCount,
        ];

        if (!empty($filterHtml)) {
            $result['filterBarHtml'] = $filterHtml;
        }

        if (!$mgadmin) {
            Storage::save($cacheKey, $result);
        }

        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
    }

    private function getSphinxProductsIds($keyword) {
        require_once 'sphinxapi.php';

        $sphinxHost = MG::getSetting('searchSphinxHost');
        $sphinxPort = MG::getSetting('searchSphinxPort');
        $sphinxLimit = MG::getSetting('sphinxLimit');
        if (!$sphinxLimit) {
            $sphinxLimit = 10;
        }

        $sphinxClient = new SphinxClient();

        $sphinxClient->SetServer($sphinxHost, $sphinxPort);
        $sphinxClient->SetConnectTimeout(1);
        $sphinxClient->SetMaxQueryTime(1000);
        $sphinxClient->SetMatchMode(SPH_MATCH_ALL);
        $sphinxClient->SetSortMode(SPH_SORT_RELEVANCE);
        $sphinxClient->SetRankingMode(SPH_RANK_SPH04);
        $sphinxClient->SetLimit($sphinxLimit);

        $productsIds = [];
        $productsIdsSphinxQueryResult = $sphinxClient->Query($keyword, 'product');
        if (isset($productsIdsSphinxQueryResult['matches'])) {
            $productsIds = array_keys($productsIdsSphinxQueryResult['matches']);
        }

        if (!$productsIdsSphinxQueryResult) {
            if ($productsIdsSphinxQueryResult === false) {
                $sphinxLastWarning = $sphinxClient->GetLastWarning();
                if ($sphinxLastWarning) {
                    echo 'WARNING: '.$sphinxLastWarning;
                    exit;
                }
                exit('Невозможно установить соединение с поисковым движком Sphinx, пожалуйста, обратитесь к администратору.');
            }
        }

        $propsProductsIds = [];
        $propsProductsIdsSphinxQueryResult = $sphinxClient->Query($keyword, 'property');
        if (isset($propsProductsIdsSphinxQueryResult['matches'])) {
            $propsProductsIds = array_keys($propsProductsIdsSphinxQueryResult['matches']);
        }

        $rawAllProductsIds = array_merge($productsIds, $propsProductsIds);
        $allProductsIds = [];
        foreach ($rawAllProductsIds as $rawProductId) {
            $allProductsIds[] = intval($rawProductId);
        }

        return $allProductsIds;
    }

    /**
     * Возвращает список найденных продуктов соответствующих поисковой фразе.
     * <code>
     * $catalog = new Models_Catalog();
     * $items = $catalog->getListProductByKeyWord('Nike', true, true);
     * viewData($items);
     * </code>
     * @param string $keyword поисковая фраза.
     * @param string $allRows получить сразу все записи.
     * @param string $onlyActive учитывать только активные продукты.
     * @param bool $adminPanel запрос из публичной части или админки.
     * @param bool $mode (не используеться)
     * @param bool|int $forcedPage номер страницы использующийся вместо url
     * @param int $searchCats поиск в категории (оставить пустым если не надо искать)
     * @return array
     */
    public function getListProductByKeyWordOld($keyword, $allRows = false, $onlyActive = false, $adminPanel = false, $mode = false, $forcedPage = false, $searchCats = -1)
    {

        $result = array(
            'catalogItems' => array(),
            'pager' => null,
            'numRows' => null
        );

        $keyword = htmlspecialchars($keyword);
        $keywordUnTrim = $keyword;
        $keyword = trim($keyword);

        //if (empty($keyword) || mb_strlen($keyword, 'UTF-8') <= 2) {
        //  return $result;
        // }
        $currencyRate = MG::getSetting('currencyRate');
        $currencyShopIso = MG::getSetting('currencyShopIso');
        // Поиск по точному соответствию.
        // Пример $keyword = " 'красный',   зеленый "
        // Убираем начальные пробелы и конечные.
        $keyword = trim($keyword); //$keyword = "'красный',   зеленый"
        if (defined('LANG') && LANG != 'LANG' && LANG != 'default') {
            $searchLang = LANG;
        }

        if (MG::getSetting('searchType') == 'sphinx') {
            // подключаем библиотеку для поискового движка
            require_once("sphinxapi.php");
            $cl = new SphinxClient();
            $cl->SetServer(MG::getSetting('searchSphinxHost'), MG::getSetting('searchSphinxPort'));
            $cl->SetConnectTimeout(1);
            $cl->SetMaxQueryTime(1000);
            $cl->SetMatchMode(SPH_MATCH_ALL);
            $sphinxLimit = MG::getSetting('sphinxLimit');
            $cl->_limit = $sphinxLimit ? $sphinxLimit : 20;

            $matches = array();
            // поиск по индексам товаров и вариантов
            $resultSphinx = $cl->Query($keyword, 'product');
            $matches = isset($resultSphinx['matches']) ? $resultSphinx['matches'] : array();
            // поиск по индексам характеристик
            $resultSphinx2 = $cl->Query($keyword, 'property');
            $matches = isset($resultSphinx2['matches']) ? ($matches + $resultSphinx2['matches']) : $matches;

            if ($resultSphinx === false) {
                if ($cl->GetLastWarning()) {
                    echo 'WARNING: ' . $cl->GetLastWarning();
                    exit;
                }
                exit('Невозможно установить соединение с поисковым движком Sphinx, пожалуйста, обратитесь к администратору.');
            }
            $idsArr = array();
            foreach ($matches as $key => $row) {
                $idsArr[] = intval($key);
            }

            $idsProductSphinx = join(',', $idsArr);
        } else {
            $fulltextInVar = '';
            if (MG::getSetting('searchType') == 'fulltext' && MG::accessFullTextSearch()) {
                // Вырезаем спец символы из поисковой фразы.
                $keyword = preg_replace('/[`~!#$%^*()=+\\\\|\\/\\[\\]{};:"\',<>?]+/', '', $keyword); //$keyword = "красный   зеленый"
                // Замена повторяющихся пробелов на на один.
                $keyword = preg_replace('/ +/', ' ', $keyword); //$keyword = "красный зеленый"
                // Обрамляем каждое слово в звездочки, для расширенного поиска.
                $keyword = str_replace(' ', '* +', $keyword); //$keyword = "красный* *зеленый"
                // Добавляем по краям звездочки.
                $keyword = '+' . $keyword . '*'; //$keyword = "*красный* *зеленый*"

                $sql = " 
	      SELECT distinct p.code, CONCAT(c.parent_url,c.url) AS category_url, 
          c.unit as category_unit, p.unit as product_unit, 
          c.weight_unit AS category_weightUnit, p.weight_unit AS product_weightUnit,
	        p.url AS product_url, p.*, pv.product_id as variant_exist, pv.id as variant_id, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`
	      FROM  `" . PREFIX . "product` AS p
	      LEFT JOIN  `" . PREFIX . "category` AS c ON c.id = p.cat_id
	      LEFT JOIN  `" . PREFIX . "product_variant` AS pv ON p.id = pv.product_id";

                if (!$adminPanel) {
                    $sql .= " LEFT JOIN (
	        SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount,
            COUNT(pv.`id`) AS variantsExists
	        FROM  `" . PREFIX . "product_variant` AS pv
	        GROUP BY pv.product_id
	      ) AS temp ON p.id = temp.product_id";
                }

                $prod = new Models_Product();
                $fulltext = "";
                $sql .= " WHERE ";
                $match =
                    " MATCH (
	      p.`title` , p.`code`, p.`description` " . $fulltextInVar . " " . $fulltext . "
	      )
	      AGAINST (
	      '" . $keyword . "'
	      IN BOOLEAN
	      MODE
	      ) ";

                DB::query("SELECT id FROM `" . PREFIX . "product_variant` LIMIT 1");

                //Если есть варианты товаров то будет искать и в них.
                if (DB::numRows(DB::query("SELECT id FROM `" . PREFIX . "product_variant` LIMIT 1"))) {
                    $fulltextInVar = ', pv.`title_variant`, pv.`code` ';

                    $match = "(" . $match .
                        " OR MATCH (pv.`title_variant`, pv.`code`)
	        AGAINST (
	        '" . $keyword . "'
	        IN BOOLEAN
	        MODE
	        )) ";
                }

                $sql .= $match;
                // Проверяем чтобы в вариантах была хотябы одна единица.
                if (!$adminPanel) {
                    if (MG::getSetting('printProdNullRem') == "true") {
                        $sql .= " AND (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)";
                    }
                    if (MG::getSetting('showVariantNull') == 'false') {
                        $sql .= ' AND (pv.`count` != 0 OR pv.`count` IS NULL) ';
                    }
                }

                if ($onlyActive) {
                    $sql .= ' AND p.`activity` = 1';
                }
                if ($searchCats > -1) {
                    $sql .= ' AND c.`id` = ' . DB::quoteInt($searchCats);
                }
            } else {

                $keywords = explode(" ", $keyword);
                $keyword = "%" . implode('%%', $keywords) . "%";


                $sql = "
	       SELECT distinct p.id, CONCAT(c.parent_url,c.url) AS category_url, 
          c.unit as category_unit, p.unit as product_unit,
          c.weight_unit AS category_weightUnit, p.weight_unit AS product_weightUnit,
	         p.url AS product_url, p.*, pv.product_id as variant_exist, pv.id as variant_id, pv.code as variant_code, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`,";
                if (!$adminPanel && !MG::enabledStorage()) {
                    $sql .= "
                IF(variantsExists > 0, varcount, IF (p.`count` < 0, 1000000, p.`count`))
                 AS  `count_sort`,
                ";
                }
                $sql .= "p.currency_iso
	       FROM  `" . PREFIX . "product` AS p
	       LEFT JOIN  `" . PREFIX . "category` AS c ON c.id = p.cat_id
         LEFT JOIN  `" . PREFIX . "product_variant` AS pv ON p.id = pv.product_id ";

                if (!$adminPanel) {
                    if (MG::enabledStorage()) {
                        $sql .= ' LEFT JOIN (SELECT `product_id`, SUM(IF(`count` < 0, 1000000, `count`)) as `count_sort` FROM `' . PREFIX . 'product_on_storage` GROUP BY `product_id`) AS temp_pos ON p.`id` = temp_pos.`product_id` ';
                    }
                    $sql .= " LEFT JOIN (
  	         SELECT pv.product_id, SUM(IF(pv.count <0, 1000000, pv.count)) AS varcount,
               COUNT(pv.`id`) AS variantsExists
  	         FROM  `" . PREFIX . "product_variant` AS pv
  	         GROUP BY pv.product_id
  	       ) AS temp ON p.id = temp.product_id";
                    if (!empty($searchLang)) {
                        $sql .= " 
              LEFT JOIN `" . PREFIX . "locales` AS lp ON p.id = lp.id_ent 
              LEFT JOIN `" . PREFIX . "locales` AS lv ON pv.id = lv.id_ent ";
                        if (MG::getSetting('searchInDefaultLang') == 'true') {
                            $sqlWhere = " 
                 WHERE (
                   p.`title` LIKE '%" . DB::quote($keyword, true) . "%'
                 OR
                   p.`code` LIKE '%" . DB::quote($keyword, true) . "%'
                 OR
                   pv.`title_variant` LIKE '%" . DB::quote($keyword, true) . "%'
                 OR
                   pv.`code` LIKE '%" . DB::quote($keyword, true) . "%'
                 OR
                   (lp.`locale` = " . DB::quote($searchLang) . " AND lp.`table` = 'product' AND lp.`field` = 'title' AND lp.`text` LIKE '%" . DB::quote($keyword, true) . "%')
                 OR
                   (lv.`locale` = " . DB::quote($searchLang) . " AND lv.`table` = 'product_variant' AND lv.`field` = 'title_variant' AND lv.`text` LIKE '%" . DB::quote($keyword, true) . "%')
                 )";
                        } else {
                            $sqlWhere = " 
                 WHERE (
                   p.`code` LIKE '%" . DB::quote($keyword, true) . "%'
                 OR
                   pv.`code` LIKE '%" . DB::quote($keyword, true) . "%'
                 OR
                   (lp.`locale` = " . DB::quote($searchLang) . " AND lp.`table` = 'product' AND lp.`field` = 'title' AND lp.`text` LIKE '%" . DB::quote($keyword, true) . "%')
                 OR
                   (lv.`locale` = " . DB::quote($searchLang) . " AND lv.`table` = 'product_variant' AND lv.`field` = 'title_variant' AND lv.`text` LIKE '%" . DB::quote($keyword, true) . "%')
                 )";
                        }
                    }
                }
                if ($adminPanel || empty($searchLang)) {
                    $sqlWhere = " 
             WHERE (
               p.`title` LIKE '%" . DB::quote($keyword, true) . "%'
             OR
               p.`code` LIKE '%" . DB::quote($keyword, true) . "%'
             OR
               pv.`title_variant` LIKE '%" . DB::quote($keyword, true) . "%'
             OR
               pv.`code` LIKE '%" . DB::quote($keyword, true) . "%')";
                }
                $sql .= $sqlWhere;

                // Проверяем чтобы в вариантах была хотябы одна единица.
                if (!$adminPanel) {
                    if (MG::getSetting('printProdNullRem') == "true") {
                        if (MG::enabledStorage()) {
                            $sql .= ' AND ((SELECT SUM(count) FROM ' . PREFIX . 'product_on_storage WHERE product_id = p.id) > 0)';
                        } else {
                            $sql .= " AND (temp.`varcount` > 0 OR temp.`varcount` < 0 OR p.count>0 OR p.count<0)";
                        }
                    }
                    if (MG::getSetting('showVariantNull') == 'false') {
                        $sql .= ' AND (pv.`count` != 0 OR pv.`count` IS NULL)';
                    }
                }

                if ($onlyActive) {
                    $sql .= ' AND p.`activity` = 1';
                }

                if ($searchCats > -1) {
                    $sql .= ' AND (c.`id` = ' . DB::quoteInt($searchCats) . ' OR c.`parent` = ' . DB::quoteInt($searchCats) . ')';
                }
            }
        }

        $sphinx = false;
        if (!empty($idsProductSphinx)) {
            $sphinx = true;

            // Что будем выбирать из базы
            $selectParts = [
                'DISTINCT p.`id`',                                     // Идентификатор товара
                'CONCAT(c.`parent_url`, c.`url`) AS category_url',      // Url категории
                'c.`unit` AS category_unit',                            // Единицы измерения количества товаров для всей категории
                'p.`unit` AS product_unit',                             // Единицы измерения количества конкретного товара
                'c.`weight_unit` AS category_weightUnit',               // Единицы измерения веса товара для всей категории
                'p.`weight_unit` AS product_weightUnit',                // Единицы измерения веса конкретного товара
                'p.`url` as product_url',                               // Url товара
                'p.*',                                                  // Остальные поля из таблицы товара
                'pv.`product_id` as variant_exist',                    // Если у товара есть варианты, то в этом поле будет id товара
                'pv.`id` as variant_id',                                // Идентификатор первого варианта
                'pv.`code` as varaint_code',                            // Артикул первого варианта
                '(p.`price_course` + p.`price_course` * ' .
                '(IFNULL (rate, 0))) as price_course',              // price_course сразу вычисляем из rate
            ];

            // с какими таблицами будет объединение
            $joinParts = [
                'LEFT JOIN `' . PREFIX . 'product_variant` AS pv ON p.`id` = pv.`product_id`',
                'LEFT JOIN `' . PREFIX . 'category` AS c ON c.`id` = p.`cat_id`',
            ];

            // условия выборки (через AND)
            $whereParts = [
                'p.`id` IN (' . $idsProductSphinx . ')',
            ];

            // Если ищем только активные товары
            if ($onlyActive) {
                // То добавляем соответствующее условие
                $whereParts[] = 'p.`activity` = 1';
            }

            // Если ищем в определенной категории (и вложенных в неё)
            if ($searchCats > -1) {
                // Тоже добавляем соответсвующее условие
                $whereParts[] = 'c.`id` = ' . DB::quoteInt($searchCats) . ' OR c.`parent` = ' . DB::quoteInt($searchCats);
            }

            // Если это публичка
            if (!$adminPanel) {
                // Проверка на наличие товара
                $whereCountPart = 'temp.`varcount` != 0 OR p.`count` != 0';

                // Если используются склады
                if (MG::enabledStorage()) {
                    // И подключаем таблицу складов для выборки количества
                    $joinParts[] = 'LEFT JOIN (SELECT `product_id`, SUM(IF(`count` < 0, 1000000, `count`)) as `count_sort` FROM `' . PREFIX . 'product_on_storage` GROUP BY `product_id`) AS temp_pos ON p.`id` = temp_pos.`product_id`';
                    // И проверка на наличие тоже будет другая
                    $whereCountPart = '`count_sort` > 0';
                    // Если не используются
                } else {
                    // Добавляем выборку количества товаров в SELECT
                    $selectParts[] = 'IF(' .
                    'variantsExists > 0, ' .
                    'varcount, ' .
                    'IF (p.`count` < 0, 1000000, p.`count`)' .
                    ') AS `count_sort`';
                }


                // Если включена опция выводить только товары в наличии
                if (MG::getSetting('printProdNullRem') == 'true') {
                    // То добавляем соответствующее условие
                    $whereParts[] = $whereCountPart;
                }

                // Подзапрос с выборкой количества товара
                $subSelect = 'SELECT ' .
                'pv.`product_id`, ' .
                'SUM(IF(pv.`count` < 0, 1000000, pv.`count`)) AS varcount, ' .
                'COUNT(pv.`id`) AS variantsExists ' .
                'FROM `' . PREFIX . 'product_variant` AS pv ' .
                'GROUP BY pv.`product_id`';
                $subSelectJoin = 'LEFT JOIN (' . $subSelect . ') AS temp ON p.`id` = temp.`product_id`';
                $joinParts[] = $subSelectJoin;
            }

            $select = implode(', ', $selectParts);
            $joins = implode(' ', $joinParts);
            $where = '(' . implode(') AND (', $whereParts) . ')';
            $sql = 'SELECT ' . $select . ' ' .
                'FROM `' . PREFIX . 'product` AS p ' .
                $joins .
                'WHERE ' . $where;
        }
        if (empty($sql)) {
            return  $result;
        }

        $page = URL::get("page");
        $settings = MG::get('settings');

        if ($forcedPage) {
            $page = $forcedPage;
        }

        //if ($mode=='groupBy') {
        $sql .= ' GROUP BY p.id';
        //}
        if ($allRows) {
            $sql .= ' LIMIT 15';
        }

        if ($adminPanel) {
            // $allRows = true;
            $settings['countСatalogProduct'] = $settings['countPrintRowsProduct'];
        }

        if (!$settings['countСatalogProduct']) {
            $settings['countСatalogProduct'] = 10;
        }

        // Сортировка в настройках
        // Опция "Показывать закончившиеся товары в конце"
        // Фикс сортировки MySQL 8+
        $sorter = str_replace(array('|asc', '|desc'), array(' ASC', ' DESC'), MG::getSetting('filterSort'));
        $sorter .= ', p.`id` ASC';
        if (!$adminPanel && MG::getSetting('productsOutOfStockToEnd') === 'true') {
            $sorter = 'CASE WHEN `count_sort` = 0 THEN 1 WHEN `count_sort` IS NULL THEN 1 ELSE 0 END, ' . $sorter;
        }
        if (stripos($sql, ' limit ') !== false) {
            $sql = str_replace(' LIMIT ', ' ORDER BY ' . $sorter . ' LIMIT ', $sql);
        } else {
            $sql .= ' ORDER BY ' . $sorter;
        }
        $navigator = new Navigator($sql, $page, $settings['countСatalogProduct'], $linkCount = 6, $allRows); // Определяем класс.

        $this->products = $navigator->getRowsSql();

        // добавим к полученым товарам их свойства
        $this->products = $this->addPropertyToProduct($this->products, $adminPanel, false);

        $useStorages = MG::enabledStorage();

        foreach ($this->products as &$pitem) {
            if (!empty($searchLang)) {
                MG::loadLocaleData($pitem['id'], $searchLang, 'product', $pitem);
            }

            if (!isset($pitem['category_unit'])) {
                $pitem['category_unit'] = 'шт.';
            }
            if (isset($pitem['product_unit']) && $pitem['product_unit'] != null && strlen($pitem['product_unit']) > 0) {
                $pitem['category_unit'] = $pitem['product_unit'];
            }

            if ($useStorages) {
                if (!empty($pitem['variants'])) {
                    foreach ($pitem['variants'] as $pkey => $pvalue) {
                        $pitem['variants'][$pkey]['count'] = MG::getProductCountOnStorage(0, $pitem['id'], $pitem['variants'][$pkey]['id'], 'all');
                    }
                } else {
                    $pitem['count'] = MG::getProductCountOnStorage(0, $pitem['id'], 0, 'all');
                }
            }
        }

        $this->pager = $navigator->getPager();

        $result = array(
            'catalogItems' => $this->products,
            'pager' => $this->pager,
            'numRows' => $navigator->getNumRowsSql()
        );

        if (count($result['catalogItems']) > 0) {

            // упорядочивание списка найденных  продуктов
            // первыми в списке будут стоять те товары, у которых полностью совпала поисковая фраза
            // затем будут слова в начале которых встретилось совпадение
            // в конце слова в середине которых встретилось совпадение
            $keyword = str_replace('*', '', $keyword);
            $keyword = mb_convert_case($keyword, MB_CASE_LOWER, "UTF-8");
            $resultTemp = $result['catalogItems'];
            $prioritet0 = array();
            $prioritet1 = array();
            $prioritet2 = array();
            foreach ($resultTemp as $key => $item) {
                $title = mb_convert_case($item['title'], MB_CASE_LOWER, "UTF-8");
                $item['image_url'] = mgImageProductPath($item["image_url"], $item['id']);

                if (trim($title) == $keyword) {
                    $prioritet0[] = $item;
                    continue;
                }

                if (strpos($title, $keyword) === 0) {
                    $prioritet1[] = $item;
                } else {
                    $prioritet2[] = $item;
                }
            }

            $result['catalogItems'] = array_merge($prioritet0,  $prioritet1, $prioritet2);
        }

        $args = func_get_args();
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
    }

    /**
     * Записывает построчно CSV выгрузку в файл data_csv_m_d_Y.csv в директорию временных файлов сайта.
     * <code>
     * $model = new Models_Product;
     * $product = $model->getProduct(5);
     * $line1 = Models_Catalog::addToCsvLine($product);
     * $product = $model->getProduct(6);
     * $line2 = Models_Catalog::addToCsvLine($product);
     * $csvText = array($line1, $line2);
     * Models_Catalog::rowCsvPrintToFile($csvText);
     * <code>
     * @param array $csvText массив с csv строками.
     * @param bool $new записывать в конец файла.
     * @return void
     */
    public function rowCsvPrintToFile($csvText, $new = false) {

        if (!empty($_POST['encoding']) && trim($_POST['encoding']) !== 'utf8') {
            foreach ($csvText as &$item) {
                $item = mb_convert_encoding($item, "WINDOWS-1251", "UTF-8");
            }
        }
        //
        $date = date('m_d_Y');
        $pathDir = mg::createTempDir('data_csv');

        if ($new) {
            if (is_dir($pathDir)) {
                $dataCsvDirObjects = array_diff(scandir($pathDir), ['.','..']);
                foreach ($dataCsvDirObjects as $dataCsvObject) {
                    @unlink($pathDir.$dataCsvObject);
                }
            }
        }

        $filename = $pathDir . 'data_csv_'.$date.'.csv';
        if($new) {
            $fp = fopen($filename, 'w');
        } else {
            $fp = fopen($filename, 'a');
        }

        //fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
        fputcsv($fp, $csvText, ';');
        fclose($fp);
    }

    /**
     * Выгружает содержание всего каталога в CSV файл.
     * <code>
     * $listProductId = array(1, 2, 5, 25);
     * $catalog = new Models_Catalog();
     * $result = $catalog->exportToCsv($listProductId);
     * viewData($result);
     * </code>
     * @param array $listProductId массив id товаров
     * @return array
     */
    public function exportToCsv($listProductId = array()) {

        // Новый вариант. Тут максимальное время работы скрипта не должно превышать 30 секунд в любом случае
        $items2page = 30;
        $timeMargin = 5;
        $defaultMaxExecTime = 30;
        $maxExecTime = min($defaultMaxExecTime, ini_get('max_execution_time'));
        if (!$maxExecTime) {
            $maxExecTime = $defaultMaxExecTime;
        }
        if ($maxExecTime < $timeMargin + 3) {
            $maxExecTime = $timeMargin + 3;
        }

        // Старый вариант
        // Вызывал проблемы, т. к. тайминги сервера (nginx, apache) могут быть ниже чем max_execution_time у пыхи, что приводит к 504 ошибке
        // if(@set_time_limit(100)) {
        //     $maxExecTime = 100;
        //     $items2page = 100;
        //     $timeMargin = 20;
        // } else {
        //     $maxExecTime = @ini_get("max_execution_time") ? min(30, @ini_get("max_execution_time")) : 30;
        //     $items2page = 10;
        //     $timeMargin = 10;
        // }

        $startTime = microtime(true);
        $startPage = (URL::getQueryParametr('page')) ? URL::getQueryParametr('page') : 1;

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream;");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=data.csv");
        header("Content-Transfer-Encoding: binary ");

        $propNameToCsv = Property::getEasyPropNameToCsv($listProductId);

        $csvText = array();

        if($startPage == 1) {
            $csvText = array("ID товара","Артикул","Категория","URL категории","Товар","Вариант","Краткое описание","Описание","Цена","Старая цена","URL товара","Изображение","Ссылка на изображение","Изображение варианта", "Ссылка на изображение варианта","Количество","Активность","Заголовок [SEO]","Ключевые слова [SEO]","Описание [SEO]","Рекомендуемый","Новый","Сортировка","Вес","Связанные артикулы","Смежные категории","Ссылка на товар","Валюта","Единицы измерения","Единицы веса","Кратность");

            // добавляем заголовки для оптовых цен
            $wholesalesGroup = unserialize(stripslashes(MG::getSetting('wholesalesGroup')));
            foreach ($wholesalesGroup as $key => $value) {
                $res = DB::query('SELECT DISTINCT count FROM '.PREFIX.'wholesales_sys WHERE `group` = '.DB::quoteInt($value));
                while ($row = DB::fetchAssoc($res)) {
                    $_SESSION['export']['wholeColumns'][] = $row['count'].'/'.$value;
                    $csvText[] = 'Количество от '.$row['count'].' для цены '.$value.' [оптовая цена]';
                }
            }

            if(MG::enabledStorage()) {
                unset($_SESSION['export']['storageColumns']);
                $storages = unserialize(stripcslashes(MG::getSetting('storages')));
                foreach ($storages as $item) {
                    $csvText[] = $item['name'].' [склад='.$item['id'].']';
                    $_SESSION['export']['storageColumns'][] = $item['id'];
                }
            }

            $fields = Models_OpFieldsProduct::getFields();
            foreach ($fields as $item) {
                $csvText[] = $item['name'].' [op id='.$item['id'].']';
                $_SESSION['export']['opColumns'][] = $item['id'];
            }

            foreach ($propNameToCsv as $item) {
                $csvText[] = $item;
            }
            $csvText[] = "Сложные характеристики";
            $this->rowCsvPrintToFile($csvText, true);
        }

        $product = new Models_Product();
        $catalog = new Models_Catalog();

        Storage::$noCache = true;
        $page = 1;

        $where = '';

        if ($_POST['fromAllCats'] === 'false' && $_POST['catIds']) {
            $where .= '`cat_id` in ('.DB::quote(implode(', ', $_POST['catIds']), true).')';
        }
        // получаем максимальное количество заказов, если выгрузка всего ассортимента
        if(empty($listProductId)) {
            $maxCountPage = ceil($product->getProductsCount($where) / $items2page);
        } else {
            $maxCountPage = ceil(count($listProductId) / $items2page);
        }
        $catalog->categoryId = MG::get('category')->getCategoryList(0);
        $catalog->categoryId[] = 0;
        if ($_POST['fromAllCats'] === 'false' && $_POST['catIds']) {
            $catalog->categoryId = $_POST['catIds'];
        }
        $listId = implode(',', $listProductId);

        for ($page = $startPage; $page <= $maxCountPage; $page++) {
            URL::setQueryParametr("page", $page);

            if(empty($listProductId)) {
                $catalog->getList($items2page, true);
            } else {
                $catalog->getListByUserFilter($items2page, ' p.id IN  ('.DB::quote($listId,1).')', true);
            }
            //Логирование выгрузки CSV
            $data['id'] = '';
            $data['product_id'] = $listProductId;
            LoggerAction::logAction('Export', 'exportToCsv', $data);
            $rowCount = (empty($_POST['rowCount'])) ? 0 : $_POST['rowCount'];
            unset($_POST['rowCount']);

            foreach ($catalog->products as $cell=>$row) {
                if($cell < $rowCount) {
                    continue;
                }

                $csvText = array();
                $parent = $row['category_url'];

                // Подставляем всесто URL названия разделов.
                $resultPath = '';
                $resultPathUrl = '';
                while ($parent) {
                    $url = URL::parsePageUrl($parent);
                    $parent = URL::parseParentUrl($parent);
                    $parent = $parent != '/' ? $parent : '';
                    $alreadyParentCat = MG::get('category')->getCategoryByUrl(
                        $url, $parent
                    );

                    $resultPath = $alreadyParentCat['title'] . '/' . $resultPath;
                    $resultPathUrl = $alreadyParentCat['url'] . '/' . $resultPathUrl;
                }

                $resultPath = trim($resultPath, '/');
                $resultPathUrl = trim($resultPathUrl, '/');

                $variants = $product->getVariants($row['id']);

                if(!empty($variants)) {
                    foreach ($variants as $key => $variant) {
                        foreach ($variant as $k => $v) {
                            if($k != 'sort' && $k != 'id') {
                                $row[$k] = $v;
                            }
                        }
                        $row['image'] = $variant['image'];
                        $row['category_url'] = $resultPath;
                        $row['category_full_url'] = $resultPathUrl;
                        $row['real_price'] = $row['price'];
                        $row['image_variant'] = $variant['image'];
                        $csvText = $this->addToCsvLine($row, 1);
                        $this->rowCsvPrintToFile($csvText);
                    }
                } else {
                    $row['category_url'] = $resultPath;
                    $row['category_full_url'] = $resultPathUrl;
                    $pricesSql = 'SELECT `price`, `old_price` '.
                        'FROM `'.PREFIX.'product` '.
                        'WHERE `id` = '.DB::quoteInt($row['id']);
                    $pricesResult = DB::query($pricesSql);
                    if ($pricesRow = DB::fetchAssoc($pricesResult)) {
                        $row['price'] = $pricesRow['price'];
                        $row['old_price'] = $pricesRow['old_price'];
                    }
                    $csvText = $this->addToCsvLine($row);
                    $this->rowCsvPrintToFile($csvText);
                }

                $rowCount++;
                $execTime = microtime(true) - $startTime;

                if($execTime+$timeMargin >= $maxExecTime) {
                    $data = array(
                        'success' => false,
                        'nextPage' => $page,
                        'rowCount' =>$rowCount,
                        'percent' => round(($page / $maxCountPage) * 100)
                    );
                    echo json_encode($data);
                    exit();
                }
            }
        }

        $date = date('m_d_Y');
        $pathDir = mg::createTempDir('data_csv',false);
        unset($_SESSION['export']);

        if(empty($listProductId)) {
            $data = array(
                'success' => true,
                'file' => $pathDir.'data_csv_'.$date.'.csv'
            );
            echo json_encode($data);
            exit();
        }

        return $pathDir.'data_csv_'.$date.'.csv';
    }

    /**
     * Добавляет продукт в CSV выгрузку.
     * <code>
     * $model = new Models_Product;
     * $product = $model->getProduct(5);
     * echo Models_Catalog::addToCsvLine($product);
     * </code>
     * @param array $row - продукт.
     * @param bool $variant - есть ли варианты этого продукта.
     * @return string
     */
    public function addToCsvLine($row, $variant = false) {
        // конвертируем старую цену
        $curSetting = MG::getSetting('currencyRate');
        if($row['currency_iso'] != 'RUR') {
            $newOldPrice = $oldPrice = floatval($row['old_price']);
            if ($curRate = floatval($curSetting[$row['currency_iso']])) {
                $newOldPrice = $oldPrice / $curRate;
            }
            $row['old_price'] = $newOldPrice;
        }

        $row['price'] = str_replace(".", "," ,$row['price']);

        $row['image_links'] = '';
        $row['image_url'] = '';
        if(!empty($row['images_product'])) {
            foreach ($row['images_product'] as $key => $url ) {
                $param = '';
                if (!empty($row['images_alt'][$key])||!empty($row['images_title'][$key])) {
                    $param = '[:param:][alt='.(!empty($row['images_alt'][$key]) ? $row['images_alt'][$key] : '').'][title='.(!empty($row['images_title'][$key]) ? $row['images_title'][$key] : '').']';
                }
                $row['image_links'] .= SITE.DS.'uploads'.DS.$url.'|';
                $row['image_url'] .= basename($url).$param.'|';
            }
            $row['image_url'] = substr($row['image_url'], 0, -1);
            $row['image_links'] = substr($row['image_links'], 0, -1);
            //   $row['image_url'] = implode('|',$row['images_product']);
        }

        if (!empty($row['product_weightUnit'])) {
            $weightUnit = $row['product_weightUnit'];
        } elseif(!empty($row['category_weightUnit'])) {
            $weightUnit = $row['category_weightUnit'];
        } else {
            $weightUnit = 'kg';
        }
        if ($weightUnit != 'kg') {
            $row['weight'] = MG::getWeightUnit('convert', ['from'=>'kg','to'=>$weightUnit,'value'=>$row['weight']]);
        }
        // $row['title'] = htmlspecialchars(str_replace("\"", "\"\"", htmlspecialchars_decode($row['title'])));
        $row['title'] = htmlspecialchars_decode($row['title']);
        $row['meta_title'] = htmlspecialchars_decode($row['meta_title']);
        $row['meta_keywords'] = htmlspecialchars_decode($row['meta_keywords']);
        $row['meta_desc'] = htmlspecialchars_decode($row['meta_desc']);
        $row['old_price'] = ($row['old_price']!='"0"')?str_replace(".", "," ,$row['old_price']):'';
        $row['description'] = str_replace("&quot;", "'", $row['description']);
        $row['description'] = str_replace("\r", "", $row['description']);
        $row['description'] = str_replace("\n", "", $row['description']);
        $row['description'] = str_replace(";\"", "\"", $row['description']);
        $row['description'] = str_replace("\"\"", "\"", $row['description']);
        $row['description'] = str_replace('=" ', '="" ', $row['description']);
        $row['short_description'] = str_replace(";\"", "\"", $row['short_description']);
        $row['meta_desc'] = str_replace("\r", "", $row['meta_desc']);
        $row['meta_desc'] = str_replace("\n", "", $row['meta_desc']);
        $row['weight'] = str_replace(".", "," ,$row['weight']);
        // получаем строку со связанными продуктами
        // формируем строку с характеристиками
        $row['property'] = Property::getHardPropToCsv($row['id']);
        $row['property'] = str_replace("\r", "", $row['property']);
        $row['property'] = str_replace("\n", "", $row['property']);

        if (!empty($row['image_url'])) {
            $row['image_url'] = htmlspecialchars_decode($row['image_url']);
        }
        if (!empty($row['image_title'])) {
            $row['image_title'] = htmlspecialchars_decode($row['image_title']);
        }
        if (!empty($row['image_alt'])) {
            $row['image_alt'] = htmlspecialchars_decode($row['image_alt']);
        }
        if (!empty($row['meta_title'])) {
            $row['meta_title'] = htmlspecialchars_decode($row['meta_title']);
        }
        if (!empty($row['meta_keywords'])) {
            $row['meta_keywords'] = htmlspecialchars_decode($row['meta_keywords']);
        }
        if (!empty($row['meta_desc'])) {
            $row['meta_desc'] = htmlspecialchars_decode($row['meta_desc']);
        }

        foreach ($row as $key => $value) {
            if (is_string($row[$key])) {
                $row[$key] = str_replace("\n", "", $value);
            }
        }

        if(MG::enabledStorage()) {
            foreach ($_SESSION['export']['storageColumns'] as $item) {
                $variant = 0;
                if (!empty($row['variants']) && is_array($row['variants'])) {
                    foreach ($row['variants'] as $var) {
                        if($var['title_variant'] == $row['title_variant']) {
                            $variant = $var['id'];
                        }
                    }
                }
                $res = DB::query('SELECT count, storage FROM '.PREFIX.'product_on_storage WHERE 
          product_id = '.DB::quoteInt($row['id']).' AND variant_id = '.DB::quoteInt($variant).'
          AND storage = '.DB::quote($item));
                while ($subRow = DB::fetchAssoc($res)) {
                    $result = $subRow['count'];
                }
                if(empty($result)) $result = 0;
                $row['storages'][] = $result;
            }
        }

        $row['wholesales'] = MG::getWholesalesToCSV($row['id'], $row);

        $variant = 0;
        if (isset($row['variants']) && is_array($row['variants'])) {
            foreach ($row['variants'] as $var) {
                if($var['title_variant'] == $row['title_variant']) {
                    $variant = $var['id'];
                }
            }
        }
        $opFieldsM = new Models_OpFieldsProduct($row['id']);
        if (!empty($_SESSION['export']['opColumns']) && is_array($_SESSION['export']['opColumns'])) {
            foreach ($_SESSION['export']['opColumns'] as $item) {
                $field = $opFieldsM->get($item);
                if($variant === 0) {
                    $res = $field['value'];
                } else {
                    $res = $field['variant'][$variant]['value'];
                }
                $opFieldsRes[] = $res;
            }
        }
        if (!isset($row['color'])) {
            $row['color'] = null;
        }
        if (!isset($row['size'])) {
            $row['size'] = null;
        }
        if ($row['size'] === 'undefined') {
            $row['size'] = '';
        }
        $row['easy_prop'] = Property::getEasyPropToCsv($row['id'], $row['color'], $row['size']);
        if ($variant) {
            // Теперь изображения вариантов выгружаются отдельной графой
            //$var_image = '[:param:][src='.$row['image'].']';
            //$row['title_variant'] .= $var_image;
            //Формируем ссылку и записываем урл изображения
            if(!empty($row['image_variant'])){
                // Этот вариант не работает, если у основного товара нет изображения, а у вариантов они есть
                // $pathToImages = substr($row['images_product'][0], 0, strrpos($row['images_product'][0], '/'));
                // $row['image_variant_links'] = SITE.DS.'uploads'.DS.$pathToImages.DS.$row['image_variant'];

                // Такой вариант должен быть более универсальным
                $row['image_variant_links'] = SITE.'/uploads/product/'.(floor($row['id'] / 100)).'00/'.$row['id'].'/'.$row['image_variant'];
            } else {
                $row['image_variant_links'] = '';
            }
            $variantsCol = htmlspecialchars_decode($row['title_variant']);
        } else {
            $row['image_variant'] = '';
            $row['image_variant_links'] = '';
            $variantsCol = "";
        }
        $csvText = array($row['id'], $row['code'], $row['category_url'], $row['category_full_url'], $row['title'], $variantsCol, $row['short_description'],
            $row['description'], $row['price'], $row['old_price'], $row['url'], $row['image_url'], $row['image_links'], $row['image_variant'], $row['image_variant_links'], $row['count'], $row['activity'], $row['meta_title'],
            $row['meta_keywords'], $row['meta_desc'], $row['recommend'], $row['new'], $row['sort'], $row['weight'], $row['related'], $row['inside_cat'],
            $row['link_electro'], $row['currency_iso'], $row['category_unit'], $weightUnit, $row['multiplicity']);




        if (isset($row['wholesales']) && is_array($row['wholesales'])) {
            foreach ($row['wholesales'] as $item) {
                $csvText[] = $item;
            }
        }
        if (isset($row['storages']) && is_array($row['storages'])) {
            foreach ($row['storages'] as $item) {
                $csvText[] = $item;
            }
        }
        if (isset($opFieldsRes) && is_array($opFieldsRes)) {
            foreach ($opFieldsRes as $item) {
                $csvText[] = $item;
            }
        }
        if (isset($row['easy_prop']) && is_array($row['easy_prop'])) {
            foreach ($row['easy_prop'] as $item) {
                $csvText[] = $item;
            }
        }
        $csvText[] = $row['property'];

        return $csvText;
    }

    /**
     * Получает массив всех категорий магазина.
     * <code>
     * $catalog = new Models_Catalog();
     * $categoryArray = $catalog->getCategoryArray();
     * viewData($categoryArray);
     * </code>
     * @return array - ассоциативный массив id => категория.
     */
    public function getCategoryArray() {
        $result = array();
        $res = DB::query('
      SELECT *
      FROM `' . PREFIX . 'category`');
        while ($row = DB::fetchAssoc($res)) {
            $result[$row['id']] = $row;
        }
        return $result;
    }

    /**
     * Получает минимальную цену из всех стоимостей товаров (варианты тоаров не учитываются).
     * <code>
     * echo Models_Catalog::getMinPrice();
     * </code>
     * @return float
     */
    public function getMinPrice() {
        $result = array();
        $res = DB::query('SELECT MIN(`price_course`) as price FROM `' . PREFIX . 'product`');
        if ($row = DB::fetchObject($res)) {
            $result = $row->price;
        }
        return $result;
    }

    /**
     * Получает максимальную цену из всех стоимостей товаров (варианты тоаров не учитываются).
     * <code>
     * echo Models_Catalog::getMaxPrice();
     * </code>
     * @return float
     */
    public function getMaxPrice() {
        $result = array();
        $res = DB::query('SELECT MAX(`price_course`) as price FROM `' . PREFIX . 'product`');
        if ($row = DB::fetchObject($res)) {
            $result = $row->price;
        }
        return $result;
    }
    /**
     * Возвращает пример загружаемого файла, содержащего информацию о категориях.
     * <code>
     * Models_Catalog::getExampleCategoryCSV();
     * </code>
     */
    public function getExampleCategoryCSV() {
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream;");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=data.csv");
        header("Content-Transfer-Encoding: binary ");

        $csvText = '"Название категории";"URL категории";"id родительской категории";"URL родительской категории";"Описание категории";"Изображение";"Иконка для меню";"Заголовок [SEO]";"Ключевые слова [SEO]";"Описание [SEO]";"SEO Описание";"Наценка";"Не выводить в меню";"Активность";"Не выгружать в YML";"Сортировка";"Внешний идентификатор";"ID категории";
"Программное обеспечение";"programmnoe-obespechenie";0;"";"";"";"";"";"";"";;0;0;1;1;"1";;1;
"Комплектующие";"komplektuyuschie";0;"";"";"";"";"";"";"";;0;0;1;1;"2";;2;
"HDD";"hdd";2;"komplektuyuschie/";"";"";"";"";"";"";;0;0;1;1;"3";;3;
"Процессоры";"protsessory";2;"komplektuyuschie/";"";"";"";"";"";"";;0;0;1;1;"12";;12;
"Видеокарты";"videokarty";2;"komplektuyuschie/";"";"";"";"";"";"";;0;0;1;1;"23";;23;
"Сетевое оборудование";"setevoe-oborudovanie";0;"";"";"";"";"";"";"";;0;0;1;1;"4";;4;
"Wifi и Bluetooth";"wifi-i-bluetooth";4;"setevoe-oborudovanie/";"";"";"";"";"";"";;0;0;1;1;"5";;5;
"Оргтехника";"orgtehnika";0;"";"";"";"";"";"";"";;0;0;1;1;"6";;6;
"Сканеры";"skanery";6;"orgtehnika/";"";"";"";"";"";"";;0;0;1;1;"7";;7;
"Принтеры и МФУ";"printery-i-mfu";6;"orgtehnika/";"";"";"";"";"";"";;0;0;1;1;"10";;10;
"3D принтеры";"3d-printery";6;"orgtehnika/";"";"";"";"";"";"";;0;0;1;1;"11";;11;
"Периферийные устройства";"periferiynye-ustroystva";0;"";"";"";"";"";"";"";;0;0;1;1;"8";;8;
"Комп. акустика";"komp.-akustika";8;"periferiynye-ustroystva/";"";"";"";"";"";"";;0;0;1;1;"9";;9;
"Мониторы";"monitory";8;"periferiynye-ustroystva/";"";"";"";"";"";"";;0;0;1;1;"13";;13;
"Устройства ввода";"ustroystva-vvoda";8;"periferiynye-ustroystva/";"";"";"";"";"";"";;0;0;1;1;"17";;17;
"Компьютерные мыши";"kompyuternye-myshi";17;"periferiynye-ustroystva/ustroystva-vvoda/";"";"";"";"";"";"";;0;0;1;1;"18";;18;
"Клавиатуры";"klaviatury";17;"periferiynye-ustroystva/ustroystva-vvoda/";"";"";"";"";"";"";;0;0;1;1;"19";;19;
"Накопители";"nakopiteli";0;"";"";"";"";"";"";"";;0;0;1;1;"14";;14;
"Карты памяти";"karty-pamyati";14;"nakopiteli/";"";"";"";"";"";"";;0;0;1;1;"15";;15;
"USB Flash drive";"usb-flash-drive";14;"nakopiteli/";"";"";"";"";"";"";;0;0;1;1;"16";;16;
"Компьютеры";"kompyutery";0;"";"";"";"";"";"";"";;0;0;1;1;"20";;20;
"Ноутбуки";"noutbuki";20;"kompyutery/";"";"";"";"";"";"";;0;0;1;1;"21";;21;
"Планшеты";"planshety";20;"kompyutery/";"";"";"";"";"";"";;0;0;1;1;"22";;22;
"Настольные";"nastolnye";20;"kompyutery/";"";"";"";"";"";"";;0;0;1;1;"24";;24;';

        echo iconv("UTF-8", "WINDOWS-1251", $csvText);
        exit;
    }

    /**
     * Возвращает пример загружаемого каталога.
     * <code>
     * Models_Catalog::getExampleCSV();
     * </code>
     */
    public function getExampleCSV() {

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream;");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=data.csv");
        header("Content-Transfer-Encoding: binary ");

        $csvText ='"ID товара";Категория;"URL категории";Товар;Вариант;"Краткое описание";Описание;Цена;URL;Изображение;Изображение варианта;Артикул;Количество;Активность;"Заголовок [SEO]";"Ключевые слова [SEO]";"Описание [SEO]";"Старая цена";Рекомендуемый;Новый;Сортировка;Вес;"Связанные артикулы";"Смежные категории";"Ссылка на товар";Валюта;"Единицы измерения";"Количество от 10 для цены 1 [оптовая цена]";"Количество от 20 для цены 1 [оптовая цена]";"Количество от 50 для цены 1 [оптовая цена]";"Слад №1  [склад=Slad-№1]";"Склад №2  [склад=Sklad-№2]";"Пункт самомвывоза  [склад=Punkt-samomvyvoza]";"Цвет [color]";"Страна производства   ";"Производитель   ";"Пол   ";"Сезон   ";"Размер [size]";"Возраст   ";"Сложные характеристики"
51;"Аксессуары/Головные уборы";aksessuary/golovnye-ubory;"Бейсболка мужская Demix";"50 Голубой";"<p>  &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<strong>Demix</strong> — великолепный пример воплощения американской мечты. Компания, начинавшаяся с желания студента найти достойную спортивную обувь, превратилась в один из сильнейших брендов мира, который существенно повлиял на развитие спорта и вирусного маркетинга. Кроме того, именно Demix сделала спорт не только великолепным зрелищем, но и прибыльным бизнесом.";199;beysbolka-mujskaya-demix_51;no-img.jpg;;CN51_1;0;1;"Бейсболка мужская Demix";"Бейсболка мужская Demix купить, CN32, Бейсболка, мужская, Demix";;;0;0;51;0;;;;RUR;шт.;159;119;79;10;11;12;"Голубой [#2832f0]";Китай;Demix;Унисекс;Лето;50;;
51;"Аксессуары/Головные уборы";aksessuary/golovnye-ubory;"Бейсболка мужская Demix";"50 Красный";"<p>  &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<strong>Demix</strong> — великолепный пример воплощения американской мечты. Компания, начинавшаяся с желания студента найти достойную спортивную обувь, превратилась в один из сильнейших брендов мира, который существенно повлиял на развитие спорта и вирусного маркетинга. Кроме того, именно Demix сделала спорт не только великолепным зрелищем, но и прибыльным бизнесом.";199;beysbolka-mujskaya-demix_51;no-img.jpg;;CN51_2;0;1;"Бейсболка мужская Demix";"Бейсболка мужская Demix купить, CN32, Бейсболка, мужская, Demix";;;0;0;51;0;;;;RUR;шт.;159;119;79;11;12;13;"Красный [#d90707]";Китай;Demix;Унисекс;Лето;50;;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"36 Голубой";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];;CN44;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;12;13;14;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"39 Голубой";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];;CN44-2;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;13;14;15;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"40 Голубой";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];;CN44-3;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;14;15;16;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;40;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"41 Голубой";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];;CN44-4;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;15;16;17;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"42 Голубой";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];;CN44-5;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;16;17;18;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;;Взрослые;
44;"Мужская обувь";mujskaya-obuv;"Кроссовки мужские Demix Beast";"43 Голубой";"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    <b>ГИБКОСТЬ</b><br />   Специальные канавки&nbsp;Flex Grooves&nbsp;позволяют подошве легко сгибаться. </li> <li>    <b>СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ</b><br />   Высокая шнуровка надежно фиксирует голеностоп.  </li> <li>    <b>НИЗКОПРОФИЛЬНАЯ АМОРТИЗАЦИЯ</b><br />    Промежуточная подошва из ЭВА смягчает ударную нагрузки при прыжках, защищая суставы от преждевременного износа. </li> <li>    <b>СВОБОДА ДВИЖЕНИЙ</b><br />   Кроссовки с низким резом позволяют добиться более динамичного ускорения и резких маневров на высокой скорости.  </li></ul>";1199;krossovki-mujskie-demix-beast;no-img.jpg|no-img.jpg[:param:][alt=prodtmpimg/321.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/322.jpg][title=]|no-img.jpg[:param:][alt=prodtmpimg/323.jpg][title=];;CN44-6;-1;1;"Кроссовки мужские Demix Beast";"Кроссовки мужские Demix Beast купить, CN44, Кроссовки, мужские, Demix, Beast";"ГИБКОСТЬ Специальные канавкиFlex Groovesпозволяют подошве легко сгибаться. СТАБИЛИЗАЦИЯ И ПОДДЕРЖКА СТОПЫ Высокая шнуровка надежно фиксирует голеностоп";;0;0;44;0;;;;RUR;шт.;959;719;479;17;18;19;"Голубой [#2832f0]";Китай;Demix;Мужской;Лето;43;Взрослые;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Черный;"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;241.jpg;CN40_1;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;18;19;20;"Черный [#1a191a]";Китай;Demix;Унисекс;;;;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Зелёный;"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;;CN40_2;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;19;20;21;"Зелёный [#a1e63a]";Китай;Demix;Унисекс;;;;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Голубой;"<p>  &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;242.jpg;CN40_3;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;20;21;22;"Голубой [#2832f0]";Китай;Demix;Унисекс;;;;
40;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол на руку для смартфона Demix+";Розовый;"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"Удобный чехол для смартфона крепится на руку и позволяет во время тренировки оставаться на связи, пользоваться приложениями для более эффективных занятий и слушать музыку. Влагоотводящая сетка на задней части и легкая в обращении система регулировки размера сделает занятия спортом еще комфортнее и приятнее.";299;chehol-na-ruku-dlya-smartfona-demix;no-img.jpg|no-img.jpg|no-img.jpg;243.jpg;CN40_4;-1;1;"Чехол на руку для смартфона Demix";"Чехол на руку для смартфона Demix купить, CN39, Чехол, на руку, для смартфона, Demix";;;0;0;40;0;;;;RUR;шт.;239;179;119;21;22;23;"Розовый [#ff0040]";Китай;Demix;Унисекс;;;;
36;"Аксессуары/Чехлы для смартфонов";aksessuary/chehly-dlya-smartfonov;"Чехол для смартфона iPhone Nike Waffle";;"<p> &nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.</p>";"<ul>  <li>    надежная защита от сколов и царапин;  </li> <li>    дополнительная защита для камеры; </li> <li>    вырезы для удобства доступа к элементам управления. </li></ul>";1199;chehol-dlya-smartfona-iphone-nike-waffle;no-img.jpg;;CN36;-1;1;"Чехол для смартфона iPhone Nike Waffle";"Чехол для смартфона iPhone Nike Waffle  купить, CN36, Чехол, для смартфона, iPhone, Nike, Waffle,";"надежная защита от сколов и царапин; дополнительная защита для камеры; вырезы для удобства доступа к элементам управления.";;0;0;36;0;;;;RUR;шт.;959;719;479;0;0;0;;Китай;NIke;Унисекс;;;;
35;Фитнес-браслеты;fitnes-braslety;"Кардиодатчик Kettler Cardio Pulse";;"&nbsp;Непревзойдённое сочетание цены и качества говорят сами за себя, что значительно упрощает решение при выборе товара.";"Показания снимаются в околосердечной зоне груди. Совместимость со всеми кардиотренажерами поддерживающими протокол Bluetooth. Данные выводятся на дисплей тренировочного компьютера. Батарейка: CR2032 3V, время работы: 600-800 часов. Длина ремня: 65-95 см. Передача данных осуществляется через протоколы&nbsp;Bluetooth low energy&nbsp;(радиус до 10 метров) или&nbsp;ANT+&nbsp;(до 6 метров).";1199;kardiodatchik-kettler-cardio-pulse;no-img.jpg|no-img.jpg;;CN35;-1;1;"Кардиодатчик Kettler Cardio Pulse";"Кардиодатчик Kettler Cardio Pulse купить, CN35, Кардиодатчик, Kettler, Cardio, Pulse";;;0;0;35;0;;;;RUR;шт.;959;719;479;0;0;0;;Китай;Kettler;Унисекс;;;;

';

        echo iconv("UTF-8", "WINDOWS-1251", $csvText);
        exit;
    }


    /**
     * Возвращает пример CSV файла для обновления цен товаров.
     * <code>
     * Models_Catalog::getExampleCsvUpdate();
     * </code>
     */
    public function getExampleCsvUpdate() {

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream;");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=data.csv");
        header("Content-Transfer-Encoding: binary ");

        $csvText ='"Артикул";"Цена";"Старая цена";"Количество";"Активность"
1000A;39000;;9;1
1001A;27900;;0;1
1003A;33500;;-1;1
10034B;14990;19000;-1;1
10034Bb;14990;;0;1
10024Bw;13000;;-1;1
10024Bbl;13000;;-1;1
10024Bbr;13000;;-1;1
10105A;44500;;1;1
340К;1390;;0;1
1004C;17990;;-1;1
1054C;12550;;-1;1
1005A;27990;30000;6;1
10002A;21900;25000;2;1
1006A;39990;;-1;1
1007D;25000;28990;7;1
390K;1090;1390;-1;1
2060B;19990;;-1;1
1004С;1090;1190;-1;1';
        echo iconv("UTF-8", "WINDOWS-1251", $csvText);
        exit;
    }

    /**
     * Метод для обработки фильтрации товаров в каталоге.
     * <code>
     * $catalog = new Models_Catalog();
     * $result = $catalog->filterPublic();
     * viewData($result);
     * </code>
     * @param bool $noneAjax построение HTML для использования AJAX запросов.
     * @param bool $onlyInCount учитывать только товары в наличии,
     * @param bool $onlyActive учитывать только активные товары,
     * @param array $sortFields массив доступных сортировок товаров.
     * @param string $baseSort сортировка по умолчанию,
     * @return array возвращает array('filterBarHtml' => $filter->getHtmlFilter($noneAjax), 'userFilter' => $userFilter, 'applyFilterList' => $applyFilterList);
     */
    public function filterPublic($noneAjax = true, $onlyInCount = false, $onlyActive=true, $sortFields = array(), $baseSort = 'sort|-1') {

        if(!count($sortFields)){
            $sortFields = MG::getSetting('sortFieldsCatalog', true);
        }
        
        if (MG::enabledStorage()) {
            unset($sortFields['count|1']);
            unset($sortFields['count|-1']);
        }

        $orderBy = strtolower(MG::getSetting('filterSort'));

        if(MG::isAdmin()) {
            $sortFieldsAdmin = array(

                'id|-1' => [
                    'lang' => 'SORT_OLDS',
                    'enable' => 'true'
                ],
                'count|-1' => [ 
                    'lang' => 'SORT_INCREASING_COUNT',
                    'enable' => 'true'
                ],
                'cat_id|1' => [ 
                    'lang' => 'SORT_CATEGORY_NAMES',
                    'enable' => 'true'
                ],
                'cat_id|-1' => [ 
                    'lang' => 'SORT_CATEGORY_REV_NAMES',
                    'enable' => 'true'
                ],
                'title|-1' => [ 
                    'lang' => 'SORT_ADMIN_NAMES',
                    'enable' => 'true'
                ],
                'title|1' => [ 
                    'lang' => 'SORT_ADMIN_REV_NAMES',
                    'enable' => 'true'
                ],
                'code|-1' => [ 
                    'lang' => 'SORT_ARTICLE_NAMES',
                    'enable' => 'true'
                ],
                'code|1' => [ 
                    'lang' => 'SORT_ARTICLES_REV_NAMES',
                    'enable' => 'true'
                ],
                'activity|1' => [ 
                    'lang' => 'SORT_ACTIVE',
                    'enable' => 'true'
                ],
                'activity|-1' => [ 
                    'lang' => 'SORT_NO_ACTIVE',
                    'enable' => 'true'
                ],

            );

            if (MG::enabledStorage()) {
                unset($sortFieldsAdmin['count|-1']);
            }

            $sortFields = array_merge($sortFields, $sortFieldsAdmin);
        }

        
        $sortOrder = '';
        foreach($sortFields as $nameField => $valueField){
            if($valueField['enable'] === 'true'){
                $sortOrder = $nameField;
                break;
            }
        }

        if(!empty($orderBy)){
            $tmpOrder = explode('|', $orderBy);
            if($tmpOrder[1] === 'desc'){
                $orderBy = $tmpOrder[0].'|1';
            } else {
                $orderBy = $tmpOrder[0].'|-1';
            }
            $sortOrder = $sortFields[$orderBy]['enable'] === 'true' ? $orderBy : $sortOrder;
        }        

        $lang = MG::get('lang');
        $model = new Models_Catalog;
        $catalog = array();

        foreach ($this->categoryId as $key => $value) {
            $this->categoryId[$key] = intval($value);
        }

        if(!empty($_REQUEST['insideCat']) && $_REQUEST['insideCat']==="false") {
            $this->categoryId = array(end($this->categoryId));
        }

        $currentCategoryId = 0;
        if (isset($this->currentCategory['id'])) {
            $currentCategoryId = $this->currentCategory['id'];
        }
        $where = '';

        if(!URL::isSection('mg-admin')) {
            $where .= ' p.activity = 1 ';

            if(MG::getSetting('printProdNullRem') == "true") {

                if(MG::enabledStorage()) {
                    $where .= ' AND p.`storage_count`';
                } else {
                    $where .= ' AND p.`count` != 0 ';
                }
            }
        }
        $catIds = implode(',', $this->categoryId);
        $rule1 = '';

        // Этот флаг определяет, искать ли товары точно в передаваемой категории или учитывать и вложенные
        $exactCat = 
            // Проверка для публички
            (
                $onlyActive &&
                MG::getSetting('productInSubcat') !== 'true'
            ) ||
            // Проверка для адмики
            (
                !$onlyActive &&
                !empty($_REQUEST['insideCat']) &&
                $_REQUEST['insideCat'] === 'false'
            );

        if (!empty($catIds)||$catIds === 0) {
            // $where1 = ' (p.cat_id IN (' . DB::quoteIN($this->categoryId) . ') or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`))';
            // $rule1 = ' (cat_id IN (' . DB::quoteIN($this->categoryId) . ') or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`)) ';
            if($currentCategoryId==0) {
                $categoryWherePart = '1=1';
                if ($exactCat) {
                    $categoryWherePart = 'p.`cat_id` = 0';
                }
                $where1 = ' '.$categoryWherePart.' or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`)';
                $rule1 = ' '.$categoryWherePart.' or FIND_IN_SET(' . DB::quote($currentCategoryId,1) . ',p.`inside_cat`) ';
            } else {
                if ($exactCat) {
                    $where1 = $rule1 = ' (c.`id` = '.DB::quoteInt($currentCategoryId).' OR FIND_IN_SET('.DB::quoteInt($currentCategoryId).', `inside_cat`)) ';
                } else {
                    $catWhereClause = '1 = 1';
                    $catsKeysSql = 'SELECT `left_key`, `right_key` '.
                        'FROM `'.PREFIX.'category` '.
                        'WHERE `id` = '.DB::quoteInt($currentCategoryId);
                    $catsKeysResult = DB::query($catsKeysSql);
                    if ($catsKeysRow = DB::fetchAssoc($catsKeysResult)) {
                        $catLeftKey = intval($catsKeysRow['left_key']) - 1;
                        $catRightKey = intval($catsKeysRow['right_key']) + 1;
                        $catWhereClause = '('.
                                '('.
                                    'c.`left_key` > '.DB::quoteInt($catLeftKey, true).' AND '.
                                    'c.`right_key` < '.DB::quoteInt($catRightKey, true).
                                ') OR '.
                                'FIND_IN_SET('.DB::quoteInt($currentCategoryId).', `inside_cat`)'.
                            ')';
                        $where1 = $rule1 = ' '.$catWhereClause.' ';
                    }
                }
            }
        } else {
            $where1 = $rule1 = '(p.`cat_id` = 0)';
            $catIds = 0;
        }

        if(!empty($where) || !empty($where1)) {
            $where = 'WHERE '.$where;
            if(!empty($where1)) {
                $where .= (URL::isSection('mg-admin')) ? $where1 : ' AND '.$where1;
            }
        }

        $whereVar = str_replace('AND p.`count` != 0', 'AND (pv.count != 0 OR pv.count IS NULL)', $where);
        if(MG::getSetting('showVariantNull') == "false") {
            $whereVar = str_replace('p.activity = 1', 'p.activity = 1 AND (pv.count != 0 OR pv.count IS NULL)', $whereVar);
        }

        if (!empty($_REQUEST['price_course'][0])) {
            $_REQUEST['price_course'][0] = MG::convertPrice($_REQUEST['price_course'][0],1);
        }
        if (!empty($_REQUEST['price_course'][1])) {
            $_REQUEST['price_course'][1] = MG::convertPrice($_REQUEST['price_course'][1],1);
        }

        $minMaxWhere = array('product'=>$where,'variant'=>$whereVar,'catIds'=>$catIds,'currentCategoryId'=>$currentCategoryId);
        $minMaxWhere = self::modFilterMinMaxPricesWhere($minMaxWhere);
        $where = $minMaxWhere['product'];
        $whereVar = $minMaxWhere['variant'];
        unset($minMaxWhere);

        // $productsPricesSql = 'SELECT '.
        //         'p.`id`, '.
        //         'p.`price_course` * (1 + c.`rate`) AS price_course '.
        //     'FROM `'.PREFIX.'product` AS p '.
        //     'LEFT JOIN `'.PREFIX.'category` AS c '.
        //         'ON p.`cat_id` = c.`id` '.$where;
        // $variantsPricesSql = 'SELECT '.
        //         'pv.`id`, '.
        //         'pv.`price_course` * (1 + c.`rate`) AS price_course '.
        //     'FROM `'.PREFIX.'product_variant` AS pv '.
        //     'LEFT JOIN `'.PREFIX.'product` AS p '.
        //         'ON pv.`product_id` = p.`id` '.
        //     'LEFT JOIN `'.PREFIX.'category` AS c '.
        //         'ON p.`cat_id` = c.`id` '.$where;
        // $productsPricesTempTableSql = 'CREATE TEMPORARY TABLE IF NOT EXISTS tmp_p_prices '.$productsPricesSql;
        // $variantsPricesTempTableSql = 'CREATE TEMPORARY TABLE IF NOT EXISTS tmp_v_prices '.$variantsPricesSql;

        // DB::query($productsPricesTempTableSql);
        // DB::query($variantsPricesTempTableSql);

        // $minMaxProductsPriceSql = 'SELECT '.
        //         'MIN(`price_course`) as min_price, '.
        //         'MAX(`price_course`) as max_price '.
        //     'FROM `tmp_p_prices`';
        // $minMaxVariantsPriceSql = 'SELECT '.
        //         'MIN(`price_course`) as min_price, '.
        //         'MAX(`price_course`) as max_price '.
        //     'FROM `tmp_p_prices`';

        $minMaxProductsPriceSql = 'SELECT '.
                'MIN(p.`price_course` * (1 + IFNULL(c.`rate`, 0))) as min_price, '.
                'MAX(p.`price_course` * (1 + IFNULL(c.`rate`, 0))) as max_price '.
            'FROM `'.PREFIX.'product` AS p '.
            'LEFT JOIN `'.PREFIX.'category` AS c '.
                'ON p.`cat_id` = c.`id` '.$where;
        $minMaxVariantsPriceSql = 'SELECT '.
                'MIN(pv.`price_course` * (1 + IFNULL(c.`rate`, 0))) as min_price, '.
                'MAX(pv.`price_course` * (1 + IFNULL(c.`rate`, 0))) as max_price '.
            'FROM `'.PREFIX.'product_variant` AS pv '.
            'LEFT JOIN `'.PREFIX.'product` AS p '.
                'ON pv.`product_id` = p.`id` '.
            'LEFT JOIN `'.PREFIX.'category` AS c '.
                'ON p.`cat_id` = c.`id` '.$where;

        $minMaxProductsPriceResult = DB::query($minMaxProductsPriceSql);
        $minMaxVariantsPriceResult = DB::query($minMaxVariantsPriceSql);
        $minMaxProductsPriceRow = DB::fetchAssoc($minMaxProductsPriceResult);
        $minMaxVariantsPriceRow = DB::fetchAssoc($minMaxVariantsPriceResult);

        $minProductsPrice = floatval($minMaxProductsPriceRow['min_price']);
        $maxProductsPrice = floatval($minMaxProductsPriceRow['max_price']);
        $minVariantsPrice = floatval($minMaxVariantsPriceRow['min_price']);
        $maxVariantsPrice = floatval($minMaxVariantsPriceRow['max_price']);

        $maxPrice = ceil(max([$maxProductsPrice, $maxVariantsPrice]));
        $minPrice = floor(min([$minProductsPrice, $minVariantsPrice]));

        // DB::query("CREATE TEMPORARY TABLE IF NOT EXISTS tmp_cat (PRIMARY KEY (id)) SELECT id, rate FROM `".PREFIX."category`");
        // $prices = DB::fetchAssoc(
        //     DB::query('
        //  SELECT
        //   CEILING(MAX((p.price_course + p.price_course * (IFNULL(c.rate,0))))) as `max_price`,
        //   FLOOR(MIN((p.price_course + p.price_course * (IFNULL(c.rate,0))))) as min_price
        // FROM `' . PREFIX . 'product` as p
        //   LEFT JOIN tmp_cat as c ON
        //   c.id = p.cat_id WHERE p.`activity` AND p.`storage_count`'));

        // $pricesVariant = DB::fetchAssoc(
        //     DB::query('
        //  SELECT
        //   CEILING(MAX((pv.price_course + pv.price_course * (IFNULL(c.rate,0))))) as `max_price`, 
        //   FLOOR(MIN((pv.price_course + pv.price_course * (IFNULL(c.rate,0))))) as `min_price`
        // FROM `' . PREFIX . 'product` as p
        //   LEFT JOIN tmp_cat as c ON
        //   c.id = p.cat_id 
        //   LEFT JOIN `'.PREFIX.'product_variant` pv ON pv.`product_id`=p.id  WHERE p.`activity` AND p.`storage_count`'
        //     ));
        // $maxPrice = max($prices['max_price']||$prices['max_price']=="0" ? $prices['max_price'] : $pricesVariant['max_price'], $pricesVariant['max_price']||$pricesVariant['max_price']=="0" ? $pricesVariant['max_price'] : $prices['max_price']);
        // $minPrice = min($prices['min_price']||$prices['min_price']=="0" ? $prices['min_price'] : $pricesVariant['min_price'], $pricesVariant['min_price']||$pricesVariant['min_price']=="0" ? $pricesVariant['min_price'] : $prices['min_price']);

        $minMaxPrices = array('min'=>$minPrice,'max'=>$maxPrice,'catIds'=>$catIds,'currentCategoryId'=>$currentCategoryId);
        $minMaxPrices = self::modFilterMinMaxPrices($minMaxPrices);
        $minPrice = $minMaxPrices['min'];
        $maxPrice = $minMaxPrices['max'];
        unset($minMaxPrices);

        $property = array(
            'cat_id' => array(
                'type' => 'hidden',
                'value' => !empty($_REQUEST['cat_id'])?$_REQUEST['cat_id']:null,
            ),

            'sorter' => array(
                'type' => 'select', //текстовый инпут
                'label' => 'Сортировать по',
                'option' => $sortFields,
                'selected' => !empty($_REQUEST['sorter']) ? $_REQUEST['sorter'] : 'null', // Выбранный пункт (сравнивается по значению)
                'value' => !empty($_REQUEST['sorter'])?$_REQUEST['sorter']:null,
            ),

            'price_course' => array(
                'type' => 'between', //Два текстовых инпута
                'label1' => $lang['PRICE_FROM'],
                'label2' => $lang['PRICE_TO'],
                'min' => !empty($_REQUEST['price_course'][0]) ? $_REQUEST['price_course'][0] : $minPrice,
                'max' => !empty($_REQUEST['price_course'][1]) ? $_REQUEST['price_course'][1] : $maxPrice,
                'factMin' => $minPrice,
                'factMax' => $maxPrice,
                'class' => 'price numericProtection'
            ),

            'applyFilter' => array(
                'type' => 'hidden', //текстовый инпут
                'label' => 'флаг примения фильтров',
                'value' => 1,
            )
        );

        if (URL::isSection('mg-admin') || URL::isAdminAjax()) {
            $property['title'] = array(
                'type' => 'text',
                'special' => 'like',
                'label' => $lang['NAME_PRODUCT'],
                'value' => !empty($_POST['title'][0]) ? $_POST['title'][0] : null,
            );
            $property['code'] = array(
                'type' => 'text',
                'special' => 'like',
                'label' => $lang['CODE_PRODUCT'],
                'value' => !empty($_POST['code'][0]) ? $_POST['code'][0] : null,
            );

            $property['activity'] = array(
                'type' => 'select',
                'label' => $lang['ACTIVITY'],
                'option' => array('null' => $lang['NO_SELECT'], 1 => $lang['ACTYVITY_TRUE'], 0 => $lang['ACTYVITY_FALSE']),
                'selected' => !empty($_REQUEST['activity']) || (isset($_REQUEST['activity']) && $_REQUEST['activity'] == 0) ? $_REQUEST['activity'] : 'null',
                'value' => !empty($_REQUEST['activity'])?$_REQUEST['activity']:null,
            );
            $property['noImage'] = array(
                'type' => 'select',
                'label' => $lang['FILTR_NO_IMAGE'],
                'option' => array('null' => $lang['NO_SELECT'], 1 => $lang['IMAGE_TRUE'], 0 => $lang['IMAGE_FALSE']),
                'selected' => !empty($_REQUEST['noImage']) || (isset($_REQUEST['noImage']) && $_REQUEST['noImage'] == 0 ) ? $_REQUEST['noImage'] : 'null',
                'value' => !empty($_REQUEST['noImage']) ? $_REQUEST['noImage']:null,
            );
            $property['noLeftovers'] = array(
                'type' => 'select',
                'label' => $lang['LEFTOVERS'],
                'option' => array('null' => $lang['NO_SELECT'], 1 => $lang['LEFTOVERS_TRUE'], 0 => $lang['LEFTOVERS_FALSE']),
                'selected' => !empty($_REQUEST['noLeftovers']) || (isset($_REQUEST['noLeftovers']) && $_REQUEST['noLeftovers'] == 0 )? $_REQUEST['noLeftovers'] : 'null',
                'value' => !empty($_REQUEST['noLeftovers']) ? $_REQUEST['noLeftovers']:null,
            );
        }

        $filter = new Filter($property);

        $arr = array(
            'dual_condition' => array (
                array(
                    !empty($_REQUEST['price_course'][0]) ? $_REQUEST['price_course'][0] : $minPrice,
                    !empty($_REQUEST['price_course'][1]) ? $_REQUEST['price_course'][1] : $maxPrice,
                    '(p.price_course + p.price_course * (IFNULL(rate,0)))'
                ),
                array(
                    !empty($_REQUEST['price_course'][0]) ? $_REQUEST['price_course'][0] : $minPrice,
                    !empty($_REQUEST['price_course'][1]) ? $_REQUEST['price_course'][1] : $maxPrice,
                    '(pv.price_course + pv.price_course * (IFNULL(rate,0)))'
                ),
                'operator' => 'OR'
            ),
            'p.new' => (isset($_REQUEST['new'])) ? $_REQUEST['new'] : 'null',
            'p.recommend' => (isset($_REQUEST['recommend'])) ? $_REQUEST['recommend'] : 'null',
            'rule1' => $rule1,

        );
        if (URL::isSection('mg-admin') || URL::isAdminAjax()) {
            if (isset($_REQUEST['code'])&&!empty($_REQUEST['code'][0])) {
                $rule2 = 'p.`code` LIKE ("%'.DB::quote($_REQUEST['code'][0],1).'%") or pv.`code` LIKE ("%'.DB::quote($_REQUEST['code'][0],1).'%")  ';
                $arr['rule2'] = $rule2;
            }
            if (isset($_REQUEST['title'])&&!empty($_REQUEST['title'][0])) {
                $rule3 = 'p.`title` LIKE ("%'.DB::quote($_REQUEST['title'][0],1).'%") or pv.`title_variant` LIKE ("%'.DB::quote($_REQUEST['title'][0],1).'%")  ';
                $arr['rule3'] = $rule3;
            }
            if (isset($_REQUEST['activity']) && ($_REQUEST['activity'] === '0' || $_REQUEST['activity'] === '1')) {
                $arr['p.activity'] = DB::quoteInt($_REQUEST['activity'], true);
            }
            if (isset($_REQUEST['noImage']) && ($_REQUEST['noImage'] === '0' || $_REQUEST['noImage'] === '1')) {
                $_REQUEST['noImage'] == '0' ? $arr['rule4'] = "p.image_url = '' " : $arr['rule4'] = "p.image_url != '' ";
            }
            if (isset($_REQUEST['noLeftovers']) && ($_REQUEST['noLeftovers'] === '0' || $_REQUEST['noLeftovers'] === '1' )) {
                if (MG::enabledStorage()) {
                    $countRule = '`storage_count` = 0';
                    $noLeftovers = intval($_REQUEST['noLeftovers']);
                    if ($noLeftovers) {
                        $countRule = '`storage_count` != 0';
                    }
                } else {
                    $noLeftovers = intval($_REQUEST['noLeftovers']);
                    $countRule = 'IF(pv.`product_id`, SUM(ABS(pv.`count`)), p.`count`) = 0';
                    if ($noLeftovers) {
                        $countRule = '(IF(pv.`product_id`, pv.`count`, p.`count`) > 0 OR IF(pv.`product_id`, pv.`count`, p.`count`) < 0)';
                    }
                }
                $arr['rule5'] = $countRule;
            }
        }
        if (!isset($_REQUEST['insideCat'])) {
            $_REQUEST['insideCat'] = false;
        }
        $userFilter = $filter->getFilterSql($arr, array(), $_REQUEST['insideCat']);

        $propFilterCounter = 0;
        // отсеивание фильтра ползунка, если его не настраивали
        if (isset($_REQUEST['prop']) && !empty($_REQUEST['prop'])) {
            foreach ($_REQUEST['prop'] as $id => $property) {
                if(in_array($property[0], array('slider|easy', 'slider|hard'))) {
                    if($property[1] == '') {
                        unset($_REQUEST['prop'][$id]);
                        continue;
                    }
                    if($property[2] == '') {
                        unset($_REQUEST['prop'][$id]);
                        continue;
                    }
                    // проверка значений на дефолтность
                    if(($property[1] == $property['min']) && ($property[2] == $property['max'])) {
                        unset($_REQUEST['prop'][$id]);
                        continue;
                    }
                }
                // проерка значений фильтра на их наличие
                foreach ($property as $cnt=>$value) {
                    if($value != '') {
                        $propFilterCounter++;
                    }
                }
            }
        }

        if(!empty($_REQUEST['prop']) && ($propFilterCounter != 0)) {
            if (!empty($_REQUEST['insideCat'])&&$_REQUEST['insideCat']=='true') {
                $catIdsFilter = $this->categoryId;
            } else {
                if (isset($this->currentCategory['id'])) {
                    $catIdsFilter = $this->currentCategory['id'];
                }
            }
            $arrayIdsProd = $filter->getProductIdByFilter($_REQUEST['prop']);
            if (!empty($arrayIdsProd)) {
                $listIdsProd = implode(',',$arrayIdsProd);
            } else {
                $listIdsProd = '';
            }

            if($listIdsProd != '') {
                if(strlen($userFilter) > 0) {
                    $userFilter .= ' AND ';
                }
                $userFilter .= ' p.id IN ('.$listIdsProd.') ';
            } else {
                // добавляем заведомо неверное  условие к запросу,
                // чтобы ничего не попало в выдачу, т.к. товаров отвечающих заданым характеристикам ненайдено
                $userFilter = ' 0 = 1 ';
            }
        }

        $keys = array_keys($sortFields);
        if(empty($_REQUEST['sorter'])) {
            $_REQUEST['sorter'] = $sortOrder;
        } elseif(!URL::isSection('mg-admin') && !in_array($_REQUEST['sorter'], $keys)) {
            $_REQUEST['sorter'] = $sortOrder;
        }

        if(!empty($_REQUEST['sorter']) && !empty($userFilter)) {
            $sorterData = explode('|', $_REQUEST['sorter']);
            $field = $sorterData[0];
            if ($sorterData[1] > 0) {
                $dir = 'desc';
            } else {
                $dir = 'asc';
            }

            if ($onlyInCount) {
                $userFilter .= ' AND (p.count>0 OR p.count<0)';
            }

            if ($onlyActive) {
                $userFilter .= ' AND p.`activity` = 1';
            }

            if(!empty($userFilter)) {
                $userFilter .= " ORDER BY `".DB::quote($field, true)."`  ".$dir;
            }
        }

        $applyFilterList = $filter->getApplyFilterList();
        if(URL::isAdminAjax()) {
            return array('filterBarHtml' => $filter->getHtmlFilterAdmin($noneAjax), 'userFilter' => $userFilter, 'applyFilterList' => $applyFilterList);
        } else {
            $result = array('filterBarHtml' => $filter->getHtmlFilter($noneAjax), 'userFilter' => $userFilter, 'applyFilterList' => $applyFilterList,
                'htmlProp' => $filter->getHtmlPropertyFilter());
            $args = func_get_args();
            return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
        }
    }

    /**
     * Метод добавляет к массиву продуктов информацию о характеристиках
     * для каждого продукта.
     * <code>
     * $catalog = new Models_Catalog;
     * $products = $catalog->addPropertyToProduct($products);
     * </code>
     * @param array $arrayProducts массив с продуктами
     * @param bool $mgadmin если из админки
     * @param bool $changePic заменять изображение
     * @return array
     */
    public function addPropertyToProduct($arrayProducts, $mgadmin = false, $changePic = true) {
        if(empty($arrayProducts)) {
            return $arrayProducts;
        }

        $categoryIds = array();
        $whereCat = '';
        $idsProduct = array();
        $currency = MG::getSetting("currency");
        $currencyRate = MG::getSetting('currencyRate');
        $currencyShopIso = MG::getSetting('currencyShopIso');
        $prod = new Models_Product();
        $idsVariantProduct = array();

        foreach ($arrayProducts as $key => $product) {
            $change = true;
            $arrayProducts[$key]['category_url'] = (MG::getSetting('shortLink') == 'true'&&(!URL::isSection('mg-admin')&&!URL::isSection('mgadmin')) ? '' : $arrayProducts[$key]['category_url'].'/');
            $arrayProducts[$key]['category_url'] = ($arrayProducts[$key]['category_url'] == '/' ? '' : $arrayProducts[$key]['category_url']);
            $product['category_url'] = (MG::getSetting('shortLink') == 'true' ? '' : $product['category_url'].'/');
            $product['category_url'] = ($product['category_url'] == '/' ? '' : $product['category_url']);
            if($product['variant_exist'] && isset($product['variant_id']) && $product['variant_id']) {

                $variants = $prod->getVariants($product['id']);
                $variantsKey = array_keys($variants);
                $product['variant_id'] = $variantsKey[0];
                $idsVariantProduct[$product['id']][] = $key;
                $variant = $variants[$product['variant_id']];

                $arrayProducts[$key]['price_course'] =  $variant['price_course'];
                $arrayProducts[$key]['price'] =  $variant['price'];
                $change = false;
                if ($changePic) {
                    $arrayProducts[$key]['image_url'] =  $variant['image']?$variant['image']:$arrayProducts[$key]['image_url'];
                }
            }
            $idsProduct[$product['id']] = $key;
            $categoryIds[] = $product['cat_id'];
            // Назначаем для продукта пользовательские
            // характеристики по умолчанию, заданные категорией.

            $arrayProducts[$key]['thisUserFields'] = MG::get('category')->getUserPropertyCategoryById($product['cat_id']);
            Property::addDataToProp($arrayProducts[$key]['thisUserFields'], $product['id']);
            $arrayProducts[$key]['propertyIdsForCat'] =  MG::get('category')->getPropertyForCategoryById($product['cat_id']);

            $arrayProducts[$key]['currency'] = $currency;

            // Формируем ссылки подробнее и в корзину.
            if (!defined('TEMPLATE_INHERIT_FROM')) {
                $arrayProducts[$key]['actionBuy'] = MG::layoutManager('layout_btn_buy', $product);
                $arrayProducts[$key]['actionCompare'] =  MG::layoutManager('layout_btn_compare', $product);
                $arrayProducts[$key]['actionView'] =  MG::layoutManager('layout_btn_more', $product);
            }

            $arrayProducts[$key]['link'] = (MG::getSetting('shortLink') == 'true' ? SITE.'/'.$product["product_url"] : SITE.'/'.(isset($product["category_url"])&&($product["category_url"]!='') ? $product["category_url"] : 'catalog/').$product["product_url"]);
            if (empty($arrayProducts[$key]['currency_iso'])) {
                $arrayProducts[$key]['currency_iso'] = $currencyShopIso;
            }


            $arrayProducts[$key]['real_old_price'] = $arrayProducts[$key]['old_price'];

            $arrayProducts[$key]['old_price'] = MG::convertPrice($arrayProducts[$key]['old_price']);

            // $arrayProducts[$key]['old_price'] = round($arrayProducts[$key]['old_price'],2);
            $arrayProducts[$key]['real_price'] = $arrayProducts[$key]['price'];

            if ($change) {
                $arrayProducts[$key]['price_course'] = MG::convertPrice($arrayProducts[$key]['price_course']);
            }

            $arrayProducts[$key]['price'] = MG::priceCourse($arrayProducts[$key]['price_course']);

            $imagesConctructions = $prod->imagesConctruction($arrayProducts[$key]['image_url'],$arrayProducts[$key]['image_title'],$arrayProducts[$key]['image_alt'], $product['id']);
            $arrayProducts[$key]['images_product'] = $imagesConctructions['images_product'];
            $arrayProducts[$key]['images_title'] = $imagesConctructions['images_title'];
            $arrayProducts[$key]['images_alt'] = $imagesConctructions['images_alt'];
            $arrayProducts[$key]['image_url'] = $imagesConctructions['image_url'];
            $arrayProducts[$key]['image_title'] = $imagesConctructions['image_title'];
            $arrayProducts[$key]['image_alt'] = $imagesConctructions['image_alt'];

            $imagesUrl = explode("|", $arrayProducts[$key]['image_url']);
            $arrayProducts[$key]["image_url"] = "";
            if (!empty($imagesUrl[0])) {
                $arrayProducts[$key]["image_url"] = $imagesUrl[0];
            }

        }

        $model = new Models_Product();
        $arrayVariants = $model->getBlocksVariantsToCatalog(array_keys($idsProduct), true, $mgadmin);

        foreach (array_keys($idsProduct) as $id) {
            if (isset($arrayProducts[$idsProduct[$id]]) && isset($arrayVariants[$id])) {
                $arrayProducts[$idsProduct[$id]]['variants'] = $arrayVariants[$id];
            }
        }

        foreach ($arrayProducts as $key => $value) {
            if (!empty($arrayProducts[$key]['variant_exist'])) {
                $arrayProducts[$key]['real_old_price'] = $arrayProducts[$key]['old_price'];
                $arrayProducts[$key]['real_price'] = MG::priceCourse($arrayProducts[$key]['price_course']);
                if ($arrayProducts[$key]['count'] == 0 && isset($arrayProducts[$key]['actionView'])) {
                    $arrayProducts[$key]['actionBuy'] = !empty($arrayProducts[$key]['actionView']) ? $arrayProducts[$key]['actionView'] : '';
                }
                if (!empty($value['variants'])) {
                    foreach ($value['variants'] as $key2 => $value2) {

                        $arrayProducts[$key]['variants'][$key2]['price_course'] = MG::convertPrice($arrayProducts[$key]['variants'][$key2]['price_course']);
                        $arrayProducts[$key]['variants'][$key2]['old_price'] = MG::convertPrice($arrayProducts[$key]['variants'][$key2]['old_price']);

                        $arrayProducts[$key]['variants'][$key2]['price'] = MG::priceCourse($arrayProducts[$key]['variants'][$key2]['price']);
                        // округление средствами php, чтобы убрать лишний ноль
                        //$arrayProducts[$key]['variants'][$key2]['count'] = round($arrayProducts[$key]['variants'][$key2]['count'],2);
                    }
                }
            }    }

        // Собираем все ID продуктов в один запрос.
        if ($prodSet = trim(DB::quote(implode(',', array_keys($idsProduct))), "'")) {
            // Формируем список id продуктов, к которым нужно найти пользовательские характеристики.
            $where = ' IN (' . $prodSet . ') ';
        } else {
            $where = ' IN (0) ';
        }

        //Определяем id категории, в которой находимся
        $catCode = URL::getLastSection();

        // $sql = '
        //   SELECT pup.property_id, pup.value, pup.product_id, prop.*, pup.type_view, pup.product_margin
        //   FROM `'.PREFIX.'product_user_property` as pup
        //   LEFT JOIN `'.PREFIX.'property` as prop
        //     ON pup.property_id = prop.id ';


        if((int)MG::getSetting('catalogProp') > 0) {
            $sql = '
        SELECT DISTINCT pup.prop_id, pup.product_id, prop.*, pup.type_view, pup.name AS value
            FROM `'.PREFIX.'product_user_property_data` as pup
            LEFT JOIN `'.PREFIX.'property` as prop
              ON pup.prop_id = prop.id ';

            if($catSet = trim(DB::quote(implode(',', $categoryIds)), "'")) {
                $categoryIds = array_unique($categoryIds);
                $sql .= '
          LEFT JOIN  `'.PREFIX.'category_user_property` as cup
          ON cup.property_id = prop.id ';
                $whereCat = ' AND cup.category_id IN ('.$catSet.') ';
            }

            $sql .= 'WHERE pup.`product_id` '.$where.$whereCat;
            $sql .= 'ORDER BY `sort` DESC';

            $res = DB::query($sql);

            while ($userFields = DB::fetchAssoc($res)) {
                // Обновляет данные по значениям характеристик, только для тех хар. которые  назначены для категории текущего товара.
                // Это не работает в фильтрах и сравнениях.
                if((int)MG::getSetting('catalogProp') > 1) {
                    if(($userFields['type'] != 'string') && ($userFields['type'] != 'textarea') && ($userFields['type'] != 'select')) {
                        $resIn = DB::query('SELECT GROUP_CONCAT(ipd.name,\'#\',ipupd.margin,\'#|\') AS value
              FROM '.PREFIX.'product_user_property_data AS ipupd
              LEFT JOIN '.PREFIX.'property_data AS ipd ON ipupd.prop_data_id = ipd.id 
              WHERE ipupd.product_id = '.$userFields['product_id'].' AND ipupd.prop_id = '.$userFields['prop_id'].' AND ipupd.active = 1');
                        while($rowIn = DB::fetchAssoc($resIn)) {
                            $userFields['value'] = str_replace(',', '', mb_substr($rowIn['value'], 0, -1));
                        }
                    }
                }
                // дописываем в массив пользовательских характеристик,
                // все переопределенные для каждого товара, оставляя при
                // этом не измененные характеристики по умолчанию
                $arrayProducts[$idsProduct[$userFields['product_id']]]['thisUserFields'][$userFields['prop_id']] = $userFields;
                // добавляем польз характеристики ко всем вариантам продукта
                if(!empty($idsVariantProduct[$userFields['product_id']])) {
                    foreach ($idsVariantProduct[$userFields['product_id']]  as $keyPages ) {
                        $arrayProducts[$keyPages]['thisUserFields'][$userFields['prop_id']] = $userFields;
                    }
                }
            }
        }


        return $arrayProducts;
    }

    /**
     * Метод содержит хук для изменения sql запроса на получение блоков новинки/хиты/акции на главной странице
     * @param string $sql sql запрос
     * @param string $block тип блока
     * @return string
     */
    static function checkIndexPageBlocks($sql, $block) {
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $sql, func_get_args());
    }

    /**
     * Метод содержит хук для изменения части sql запроса на получение min/max цен при построении фильтра
     * @param array $whereArr массив с данными для построения фильтра
     * @return array
     */
    static function modFilterMinMaxPricesWhere($whereArr) {
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $whereArr, func_get_args());
    }

    /**
     * Метод содержит хук для изменения результата получения min/max цен при построении фильтра
     * @param array $prices массив с данными для построения фильтра
     * @return array
     */
    static function modFilterMinMaxPrices($prices) {
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $prices, func_get_args());
    }

    /**
     * Метод содержит хук для изменения sql перед построением пагинации при применении фильтра
     * @param string $sql часть sql запроса
     * @return string
     */
    static function modUserFilterNavigatorSql($sql) {
        return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $sql, func_get_args());
    }
}
