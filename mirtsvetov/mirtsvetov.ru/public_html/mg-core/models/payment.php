<?php
/**
 * Класс Models_Payment
 * Реализует логику взаимодействия с новой системой оплат.
 *
 * @package moguta.cms
 */
class Models_Payment {
    private static $paymentTable = '`'.PREFIX.'payment`';
    private static $paymentLogsDir = SITE_DIR.TEMP_DIR.'payLogs';
    
    /**
     * Возвращает все оплаты новой системы.
     * 
     * @param bool $withDeliveries Если передать true, то каждая оплата будет содержать массив deliveries со списком подключенных к ней способов доставок.
     * @param bool $decryptParams Если передать true, то в массиве параметров оплаты (paramArray) параметры типа "crypt" будут расшифрованы.
     * @param bool $onlyAcitvePlugins Есил передать true, то возвращает только активные оплаты у которых активен плагин или плагина вообще нет
     * 
     * @return array Массив оплат.
     */
    public static function getPayments($withDeliveries = false, $decryptParams = false, $onlyActivePlugins = false) {
        $deliveries = [];
        if ($withDeliveries) {
            $paymentsDeliveriesSql = 'SELECT * FROM `'.PREFIX.'delivery` AS d '.
                'LEFT JOIN `'.PREFIX.'delivery_payment_compare` AS dpc '.
                'ON d.`id` = dpc.`delivery_id` '.
                'WHERE `compare` = 1';
            $paymentsDeliveriesResult = DB::query($paymentsDeliveriesSql);
            while ($deliveryRow = DB::fetchAssoc($paymentsDeliveriesResult)) {
                $deliveries[$deliveryRow['payment_id']][$deliveryRow['id']] = $deliveryRow;
            }
        }

        $paymentsWhereClause = [
            'p.`code` NOT LIKE \'old#%\''
        ];
        $paymentsSql = 'SELECT p.* FROM '.self::$paymentTable.' as p ';
        if ($onlyActivePlugins) {
            $paymentsSql .= 'LEFT JOIN `'.PREFIX.'plugins` as pl '.
                'ON p.`plugin` = pl.`folderName` ';
            $paymentsWhereClause[] = 'AND p.`activity` = 1';
            $paymentsWhereClause[] = 'AND (pl.`active` IS NULL OR pl.`active` = 1)';
        }
        $paymentsSql .= 'WHERE '.implode(' ', $paymentsWhereClause).' '.
            'ORDER BY p.`sort` DESC';
        $paymentsResult = DB::query($paymentsSql);
        $payments = [];
        while ($paymentsRow = DB::fetchAssoc($paymentsResult)) {
            if ($withDeliveries) {
                $paymentsRow['deliveries'] = $deliveries[$paymentsRow['id']];
            }
            $paymentsRow['paramArray'] = self::decodeParams($paymentsRow['paramArray'], $decryptParams);
            $payments[] = $paymentsRow;
        }
        return $payments;
    }

    /**
     * Возвращает оплату по заданному условию.
     * 
     * <code>
     * $payments = Models_Payment::getPayment('WHERE `rate` > 0', true);
     * </code>
     * 
     * @param string $whereClause SQL условие для выборки оплаты (пишется с WHERE).
     * @param bool $decryptParams Если передать true, то в массиве параметров оплаты (paramArray) параметры типа "crypt" будут расшифрованы.
     * @param bool $fullParams  Если передать false, то параметры в массиве paramArray будут иметь сокращённый вид,
     *                          где ключ массива - название параметра, а значение - значение параметра.
     *                          иначе параметры будут ввиде неассоциативного массива, каждый элемент которого представляет из себя
     *                          полную информацию о параметре, с его названием, типом, значением и т.д.
     * 
     * @return array|null Массив с данными об оплате или null если по переданному условию оплаты не найдено.
     */
    public static function getPayment($whereClause = '', $decryptParams = false, $fullParams = false) {
        $paymentSql = 'SELECT * FROM '.self::$paymentTable.' '.$whereClause;
        $paymentResult = DB::query($paymentSql);
        $result = DB::fetchAssoc($paymentResult);
        if ($result) {
            $result['paramArray'] = self::decodeParams($result['paramArray'], $decryptParams);
            if (!$fullParams) {
                $result['paramArray'] = self::compactParams($result['paramArray']);
            }
        }
        return $result;
    }

    /**
     * Возвращает оплату по её id.
     * 
     * @param int $id Идентификатор оплаты в базе данных
     * @param string $decryptParams Если передать true, то в массиве параметров оплаты (paramArray) параметры типа "crypt" будут расшифрованы.
     * 
     * @return array|null Массив с данными об оплате или null если по переданному условию оплаты не найдено.
     */
    public static function getPaymentById($id, $decryptParams = false) {
        $whereClause = 'WHERE `id` = '.DB::quote($id);
        $result = self::getPayment($whereClause, $decryptParams);
        return $result;
    }

    /**
     * Возвращает оплату по её коду.
     * 
     * @param string $code Код оплаты в базе данных (уникальный текстовый идентификатор).
     * @param string $decryptParams Если передать true, то в массиве параметров оплаты (paramArray) параметры типа "crypt" будут расшифрованы.
     * 
     * @return array|null Массив с данными об оплате или null если по переданному условию оплаты не найдено.
     */
    public static function getPaymentByCode($code, $decryptParams = false) {
        $whereClause = 'WHERE `code` = '.DB::quote($code);
        $result = self::getPayment($whereClause, $decryptParams);
        return $result;
    }

    /**
     * Возвращает оплату по её плагину.
     * 
     * @param string $plugin Плагин оплаты в базе данных (как правило, название папки плагина).
     * @param string $decryptParams Если передать true, то в массиве параметров оплаты (paramArray) параметры типа "crypt" будут расшифрованы.
     * 
     * @return array|null Массив с данными об оплате или null если по переданному условию оплаты не найдено.
     */
    public static function getPaymentByPlugin($plugin, $decryptParams = false) {
        $whereClause = 'WHERE `plugin` = '.DB::quote($plugin);
        $result = self::getPayment($whereClause, $decryptParams);
        return $result;
    }

    /**
     * Обновление оплаты.
     * 
     * Метод используется для изменения оплаты системой. Например, при редактировании оплаты через административную панель.
     * Изменять оплату в плагине рекомендуется напрямую в базе данных.
     * 
     * <code>
     * $result = Models_Payment::updatePayment(1002, [
     *     'name' => 'Тестовая оплата',
     *     'public_name' => 'Тестовая оплата',
     *     'activity' => 0,
     *     'deliveryMethod' => [3,4,7]
     * ], 'PLUG688');
     * </code>
     * 
     * @param int $id Идентификатор оплаты в базе данных.
     * @param array $data   Массив с данными для обновления оплаты.
     *                      Ключ в этом массиве представляет из себя атрибут оплтаы в базе данных, а значение - новое значение для этого атрибута.
     *                      Ключ logs в этом массиве не изменит значение атрибута logs в базе данных, а включит (если значение - 1) или выключит (если значение - 0) логирование для оплаты.
     *                      Помимо атрибутов в этом массиве можно, также, передать deliveryMethod - массив с идентификаторами подключенных к оплате способов доставки.
     * @param string $pluginCode Код плагина из маркетплейса. Необходимо передать при изменении активности оплаты, чтобы ареновать/приостановить аренду соответствующего плагина на SAAS сайте.
     * 
     * @return bool
     */
    public static function updatePayment($id, $data, $pluginCode = null) {
        $payment = self::getPaymentById($id);
        if (!$payment) {
            return false;
        }
        if ($data['deliveryMethod']) {
            $deletePaymentDeliveriesSql = 'DELETE FROM `'.PREFIX.'delivery_payment_compare` '.
                'WHERE `payment_id` = '.DB::quoteInt($id);
            DB::query($deletePaymentDeliveriesSql);
            $insertParts = [];
            foreach ($data['deliveryMethod'] as $deliveryId) {
                $insertParts[] = '('.DB::quoteInt($id).', '.DB::quoteInt($deliveryId).', 1)';
            }
            $setNewPaymentDeliveriesSql = 'INSERT INTO `'.PREFIX.'delivery_payment_compare` '.
                'VALUES '.implode(', ', $insertParts);
            DB::query($setNewPaymentDeliveriesSql);
        }
        $enabledLogs = MG::getSetting('payments-logs', true);
        if (!$enabledLogs) {
            $enabledLogs = [];
        }
        if (!empty($data['logs'])) {
            if (!in_array($payment['code'], $enabledLogs)) {
                self::togglePaymentLog($payment['code'], 1);
            }
        } else {
            if (in_array($payment['code'], $enabledLogs)) {
                self::togglePaymentLog($payment['code'], 0);
            }
        }
        unset($data['logs']);
        unset($data['deliveryMethod']);

        if ($payment['plugin']) {
            if (intval($payment['activity']) !== intval($data['activity'])) {
                if ($data['activity']) {
                    if ($pluginCode && !self::checkPaymentAvailable($pluginCode)) {
                        return 'nonavailable';
                    }
                    $activateResult = PM::activatePlugin($payment['plugin']);
                    if (!$activateResult) {
                        return false;
                    }
                } else {
                    $deactivateResult = PM::deactivatePlugin($payment['plugin']);
                    if (!$deactivateResult) {
                        return false;
                    }
                }
            }
        }

        $updateParts = [];
        foreach ($data as $name => $value) {
            $updateParts[] = '`'.$name.'` = '.DB::quote($value);
        }
        $updateSql = 'UPDATE '.self::$paymentTable.' '.
            'SET '.implode(', ', $updateParts).' '.
            'WHERE `id` = '.DB::quote($id);
        $result = DB::query($updateSql);
        return $result;
    }

    /**
     * Создаёт пользовательскую оплату (не привязанную к плагину).
     * 
     * <code>
     * $result = Models_Payment::createCustomPayment([
     *     'name' => 'Тестовая оплата',
     *     'public_name' => 'Тестовая оплата',
     *     'activity' => 0,
     *     'description' => 'Примечание для тестовой оплаты',
     *     'rate' => 0,
     *     'permission' => 'fiz',
     *     'icon' => 'https://mysite.domainzone/uploads/payment.png'
     * ]);
     * </code>
     * 
     * @param $data Массив с данными новой оплаты.
     * 
     * @return bool
     */
    public static function createCustomPayment($data) {
        $params = [
            [
                'name' => 'description',
                'title' => 'Примечание',
                'type' => 'text',
                'value' => $data['description'] ? $data['description'] : '',
            ],
        ];
        $data['paramArray'] = self::encodeParams($params);

        $paymentId = 1000;
        $maxPaymentIdSql = 'SELECT MAX(`id`) as maxId FROM '.self::$paymentTable;
        $maxPaymentIdResult = DB::query($maxPaymentIdSql);
        if ($maxPaymentIdRow = DB::fetchAssoc($maxPaymentIdResult)) {
            $maxPaymentId = $maxPaymentIdRow['maxId'];
            if ($paymentId <= $maxPaymentId) {
                $paymentId = $maxPaymentId + 1;
            }
        }

        $createValues = [
            DB::quoteInt($paymentId),
            '\'\'',
            DB::quote($data['name']),
            DB::quote($data['public_name']),
            DB::quoteInt($data['activity']),
            DB::quote($data['paramArray']),
            DB::quote($data['rate']),
            DB::quote($data['permission']),
            DB::quote($data['icon'])
        ];
        $createPaymentSql = 'INSERT INTO '.self::$paymentTable.' '.
            '(`id`, `code`, `name`, `public_name`, `activity`, `paramArray`, `rate`, `permission`, `icon`) '.
            'VALUES ('.implode(', ', $createValues).')';
        $createResult = DB::query($createPaymentSql);
        if (!$createResult) {
            return false;
        }
        $paymentId = DB::insertId();
        $code = 'custom#'.$paymentId;
        $sort = $paymentId;
        $setCodeSql = 'UPDATE '.self::$paymentTable.' '.
            'SET `code` = '.DB::quote($code).', '.
            '`sort` = '.DB::quoteInt($sort).' '.
            'WHERE `id` = '.DB::quoteInt($paymentId);
        $setCodeResult = DB::query($setCodeSql);
        if (!$setCodeResult) {
            return false;
        }

        if ($data['deliveryMethod']) {
            $deletePaymentDeliveriesSql = 'DELETE FROM `'.PREFIX.'delivery_payment_compare` '.
                'WHERE `payment_id` = '.DB::quoteInt($paymentId);
            DB::query($deletePaymentDeliveriesSql);
            $insertParts = [];
            foreach ($data['deliveryMethod'] as $deliveryId) {
                $insertParts[] = '('.DB::quoteInt($paymentId).', '.DB::quoteInt($deliveryId).', 1)';
            }
            $setNewPaymentDeliveriesSql = 'INSERT INTO `'.PREFIX.'delivery_payment_compare` '.
                'VALUES '.implode(', ', $insertParts);
            DB::query($setNewPaymentDeliveriesSql);
        }

        return true;
    }

    /**
     * Создаёт новую оплату (из плагина)
     * 
     * <code>
     * $defaultParams = [
     *     [
     *         'name' => 'stringOption',
     *         'title' => 'Текстовая настройка',
     *         'type' => 'text',
     *         'value' => '',
     *         'tip' => 'Подсказка для текстовой настройки',
     *     ],
     *     [
     *         'name' => 'cryptOption',
     *         'title' => 'Шифруемая текстовая настройка',
     *         'type' => 'crypt',
     *         'value' => '',
     *     ],
     *     [
     *         'name' => 'checkboxOption',
     *         'title' => 'Чекбокс-настройка',
     *         'type' => 'checkbox',
     *         'value' => false,
     *     ],
     *     [
     *         'name' => 'selectOption',
     *         'title' => 'Настройка с выбором',
     *         'type' => 'select',
     *         'value' => 'option1',
     *         'options' => [
     *             [
     *                 'name' => 'option1',
     *                 'title' => 'Первый вариант',
     *             ],
     *             [
     *                 'name' => 'option2',
     *                 'title' => 'Второй вариант',
     *             ],
     *         ]
     *     ],
     *     [
     *         'name' => 'multiSelectOption',
     *         'title' => 'Настройка с множественным выбором',
     *         'type' => 'multiselect',
     *         'values' => ['option2'],
     *         'options' => [
     *             [
     *                 'name' => 'option1',
     *                 'title' => 'Первый вариант',
     *             ],
     *             [
     *                 'name' => 'option2',
     *                 'title' => 'Второй вариант',
     *             ],
     *         ]
     *     ],
     * ];
     * 
     * $urls = [
     *     [
     *         'type' => 'info',
     *         'title' => 'Result URL',
     *         'link' => SITE.'/payment?payment=yourpayment&pay=result',
     *     ],
     *     [
     *         'type' => 'success',
     *         'title' => 'Success URL',
     *         'link' => SITE.'/payment?payment=yourpayment&pay=success',
     *     ],
     *     [
     *         'type' => 'fail',
     *         'title' => 'Fail URL',
     *         'link' => SITE.'/payment?payment=yourpayment&pay=fail',
     *     ],
     * ];
     * 
     * $result = Models_Payment::addPayment(
     *     'payment-example',
     *     'Тестовая оплата',
     *     'Тестовая оплата',
     *     'payment-example',
     *     $defaultParams,
     *     'https://mysite.domainzone/uploads/payment.png',
     *     $urls,
     *     0,
     *     'yur',
     *     true
     * );
     * </code>
     * 
     * @param string $code Уникальный текстовый идентификатор. Для удобства рекомендуется использовать название папки плагина.
     * @param string $name Название для менеджера (Отображается в административной панели).
     * @param string $publicName Название для клиента (Отображается в публичной части сайта).
     * @param string $plugin Папка плагина.
     * @param array $defaultParams Массив настроек оплаты с изначальными значениями.
     * @param string $icon Ссылка на иконку оплаты (Отображается в публичной части сайта).
     * @param int $logs Поддержка записи логов оплатой (0 - оплата не поддерживает запись логов, 1 - поддерживает).
     * @param array $urls Массив ссылок оплаты (Отображаются в окне редактировании оплаты).
     * @param float $rate Наценка/скидка на способ оплаты. Передаётся коэффициент изменения стоимости, т. е. значение 0.1 означает наценку в 10%, а значение -0.05 - скидку в 5%.
     * @param string $permission Кому доступна оплата. all - всем покупателям, fiz - только физическим лицам, yur - только юридическим лицам.
     * @param bool $disablePayment Флаг отключения оплаты после её создания. Рекомендуем передавать true или значение по умолчанию, чтобы только созданный, но ещё не настроенный способ оплаты не был изначально активен.
     * 
     * @return bool
     */
    public static function addPayment(
        $code,
        $name,
        $publicName = null,
        $plugin = null,
        $defaultParams = [],
        $icon = null,
        $logs = 0,
        $urls = [],
        $rate = 0,
        $permission = 'fiz',
        $disablePayment = true
    ) {
        if (!$publicName) {
            $publicName = $name;
        }

        $sort = 1;
        $maxSortSql = 'SELECT MAX(`sort`) as maxSort FROM '.self::$paymentTable;
        $maxSortResult = DB::query($maxSortSql);
        if ($maxSortRow = DB::fetchAssoc($maxSortResult)) {
            $sort = $maxSortRow['maxSort'] + 1;
        }

        $paymentId = 1000;
        $maxPaymentIdSql = 'SELECT MAX(`id`) as maxId FROM '.self::$paymentTable;
        $maxPaymentIdResult = DB::query($maxPaymentIdSql);
        if ($maxPaymentIdRow = DB::fetchAssoc($maxPaymentIdResult)) {
            $maxPaymentId = $maxPaymentIdRow['maxId'];
            if ($paymentId <= $maxPaymentId) {
                $paymentId = $maxPaymentId + 1;
            }
        }

        $activity = $disablePayment ? 0 : 1;

        $params = self::encodeParams($defaultParams);

        if ($logs) {
            $logs = 1;
        } else {
            $logs = 0;
        }

        $createPaymentSql = 'INSERT IGNORE INTO '.self::$paymentTable.' '.
            '(`id`, `code`, `name`, `public_name`, `activity`, `paramArray`, `urlArray`, `rate`, `sort`, `permission`, `plugin`, `icon`, `logs`) '.
            'VALUES ('.
                DB::quoteInt($paymentId).', '.
                DB::quote($code).', '.
                DB::quote($name).', '.
                DB::quote($publicName).', '.
                DB::quoteInt($activity).', '.
                DB::quote($params).', '.
                DB::quote(json_encode($urls, JSON_UNESCAPED_UNICODE)).', '.
                DB::quote($rate).', '.
                DB::quoteInt($sort).', '.
                DB::quote($permission).', '.
                DB::quote($plugin).', '.
                DB::quote($icon).', '.
                DB::quoteInt($logs).
            ')';

        if ($disablePayment) {
            PM::deactivatePlugin($plugin);
        }
        $result = DB::query($createPaymentSql);
        return $result;
    }

    /**
     * Задаёт настройкам оплаты новые значения.
     * 
     * <code>
     * $newParamsJson = Models_Payment(1066, [
     *     'stringOption' => 'Новое значение',
     *     'checkboxOption' => true,
     *     'multiselectOption' => [option1, option2],
     * ]);
     * </code>
     * 
     * @param int $id Идентификатор оплаты в базе данных.
     * @param array $newParams Массив новых значений настроек. Ключ массива - название настройки, значение в массиве - новое значение настройки.
     * 
     * @return string JSON строка с обновленными параметрами оплаты.
     */
    public static function mergeParams($id, $newParams) {
        $payment = self::getPayment('WHERE `id` = '.DB::quoteInt($id), true, true);
        if (!$payment) {
            return false;
        }
        $params = $payment['paramArray'];
        foreach ($params as &$param) {
            foreach ($newParams as $newParam) {
                if ($param['name'] === $newParam['name']) {
                    switch ($param['type']) {
                        case 'checkbox':
                            $param['value'] = ($newParam['value'] === 'true');
                            break;
                        case 'multiselect':
                            $param['values'] = $newParam['value'];
                            break;
                        default:
                            $param['value'] = $newParam['value'];
                            break;
                    }
                }
            }
        }
        $params = self::encodeParams($params);
        return $params;
    }

    /**
     * Используется при AJAX запросе, 
     * возвращает html список способов оплаты в зависимости от 
     * выбранного способа доставки.
     * 
     * @param int ID доставки.
     * @param string Тип покупателя.
     * @param bool Возвращать верстку или ajax ответ.
     * @param int Количество доставок.
     * 
     * @return string HTML верстка.
     */
    public static function getPaymentsByDeliveryId($deliveryId = null, $customer = null, $nojson = false, $countDeliv = null)
    {
        $countPaymentMethod = 0; //количество активных методов оплаты

        $paymentTable = '';

        $payments = self::getPayments(true, false, true);
        foreach ($payments as $payment) {
            $deliveries = [];
            if (!empty($payment['deliveries'])) {
                $deliveries = $payment['deliveries'];
            }

            if ($customer == "yur" && $payment['permission'] == "fiz") {
                continue;
            }

            if ($customer == "fiz" && $payment['permission'] == "yur") {
                continue;
            }

            if (empty($deliveries[$deliveryId]) || !$payment['activity']) {
                continue;
            }

            if (isset($_POST['lang'])) {
                MG::loadLocaleData($payment['id'], $_POST['lang'], 'payment', $payment);
            } else {
                // TODO REMOVE IT?
                MG::loadLocaleData($payment['id'], LANG, 'payment', $payment);
            }

            $payActive = false;

            if ((isset($_POST['payment']) && $payment['id'] === $_POST['payment']) || 1 == $countPaymentMethod) {
                $payActive = true;
            }
            if (defined('TEMPLATE_INHERIT_FROM')) {
                ob_start(); // ପ(๑•ᴗ•๑)ଓ ♡ (2/17)
                component('order/payments', array('id' => $payment['id'], 'name' => $payment['public_name'], 'rate' => $payment['rate'], 'active' => $payActive));
                $paymentTable .= ob_get_clean();
            } else {
                $paymentTable .= MG::layoutManager('layout_payment', array('id' => $payment['id'], 'name' => $payment['public_name'], 'rate' => $payment['rate'], 'active' => $payActive));
            }
        }

        if ($nojson) {
            return $paymentTable;
        }

        $summDelivery = 0;
        $deliveryArray = self::getOrderDeliveries($deliveryId);
        foreach ($deliveryArray as $delivery) {
            if ($delivery['id'] == $deliveryId && $delivery['cost'] != 0) {
                $delivery['cost'] = MG::convertPrice($delivery['cost']);
                $summDelivery = MG::numberFormat($delivery['cost']) . ' ' . MG::getSetting('currency');
            }
        }
        // Расшифровка локалей
        if (class_exists('CloudCore')) {
            $paymentTable = CloudCore::decryptionContent($paymentTable);
        }
        $result = array(
            'status' => true,
            'paymentTable' => $paymentTable,
            'summDelivery' => $summDelivery,
        );

        $args = func_get_args();

        if (empty($args)) {
            $args = array($deliveryId);
        }

        $result = MG::createHook('Controllers_Order_getPaymentByDeliveryId', $result, $args);
        return $result;
    }
    
    /**
     * Возвращает массив доставок.
     * 
     * @return array Массив доставок.
     */
    public static function getDeliveries() {
        $deliveries = [];
        $deliveriesSql = 'SELECT * FROM `'.PREFIX.'delivery`';
        $deliveriesResult = DB::query($deliveriesSql);
        while ($deliveriesRow = DB::fetchAssoc($deliveriesResult)) {
            $deliveries[] = $deliveriesRow;
        }
        return $deliveries;
    }

    // Возвращает список доставок для публички
    private static function getOrderDeliveries($checkedDelivery = null) {
        $result = [];

        $cart = new Models_Cart;
        $isDeliveryCostDiscount = MG::getSetting('enableDeliveryCostDiscount') === 'true';
        $cartSumm = $cart->getTotalSumm($isDeliveryCostDiscount);
    
        foreach (self::getDeliveries() as $delivery) {
          $id = $delivery['id'];
          if ($delivery['free'] != 0 && $delivery['free'] <= $cartSumm) {
            $delivery['cost'] = 0;
          }
    
          if (!$delivery['activity']) {
            continue;
          }
    
          if ($checkedDelivery === intval($id)) {
            $delivery['checked'] = 1;
          }
    
          // Заполнение массива способов доставки.
          $result[$delivery['id']] = $delivery;
        }
    
        // Если доступен только один способ доставки, то он будет выделен.
        if (1 === count($result)) {
          $deliveryId = array_keys($result);
          $result[$deliveryId[0]]['checked'] = 1;
        }
        return $result;
    }

    /**
     * Создаёт событие отрисовки формы оплаты для конкретной оплаты.
     * 
     * @param int $paymentId Идентификатор оплаты в базе данных.
     * @param int $orderId Идентификатор заказа в базе данных.
     * 
     * @return string Форма оплаты из плагина.
     */
    public static function getPaymentForm($paymentId, $orderId) {
        $result = '';
        $payment = self::getPaymentById($paymentId);
        if ($payment && $payment['plugin']) {
            $args = func_get_args();
            $result = MG::createHook($payment['plugin'], $result, $args);
        }
        return $result;
    }

    /**
     * Возвращает подготовленный список параметров оплаты.
     * 
     * @param string $code Код оплаты (Уникальный строковый идентификатор).
     * @param bool $decrypt Флаг расшифровки параметров типа "crypt".
     * 
     * @return array Массив параметров оплаты, где ключ - название параметра, значение - значение параметра.
     */
    public static function getPaymentParams($code, $decrypt = false) {
        $result = null;
        $paymentParamsSql = 'SELECT `paramArray` '.
            'FROM '.self::$paymentTable.' '.
            'WHERE `code` = '.DB::quote($code);
        $paymentParamsResult = DB::query($paymentParamsSql);
        if ($paymentParamsRow = DB::fetchAssoc($paymentParamsResult)) {
            $paymentParams = self::decodeParams($paymentParamsRow['paramArray'], $decrypt);
            $result = self::compactParams($paymentParams);
        }
        return $result;
    }

    // Кодирует параметры в json, параметры типа crypt перед этим шифрует
    private static function encodeParams($params) {
        foreach ($params as &$param) {
            if ($param['type'] === 'crypt') {
                $param['value'] = CRYPT::mgCrypt($param['value']);
            }
        }
        $result = json_encode($params, JSON_UNESCAPED_UNICODE);
        return $result;
    }

    // Преобразовывает параметры оплаты из json в массив, при необходимости расшифровывает crypt
    private static function decodeParams($encodedParams, $decrypt = false) {
        $params = json_decode($encodedParams, true);
        if ($decrypt) {
            foreach ($params as &$param) {
                if ($param['type'] === 'crypt') {
                    $param['value'] = CRYPT::mgDecrypt($param['value']);
                }
            }
        }
        return $params;
    }

    public static function compactParams($params) {
        $result = [];
        foreach ($params as $param) {
            if (empty($param['type'])) {
                continue;
            }
            if ($param['type'] === 'multiselect') {
                $param['value'] = $param['values'];
            }
            $result[$param['name']] = $param['value'];
        }
        return $result;
    }

    /**
     * Создаёт событие запроса к оплатам. Срабатывает когда от банков приходит вебхук (нотификация), чтобы плагины оплат могли их обработать.
     * 
     * @return array Результат события запроса к оплатам.
     */
    public static function handleRequest() {
        $data = [];
        $data = MG::createHook(__CLASS__ . "_" . __FUNCTION__, $data, []);
        return $data;
    }

    /**
     * Метод записывает лог оплаты. Логи, записанные с использованием этого метода доступны для скачивания в настройках оплаты только пользователям с полным доступом к настройкам.
     * 
     * <code>
     * $url = 'api.somebank.domainzone/merchant';
     * $request = [
     *     'action' => 'getPaymentLink',
     *     'summ' => 17500
     * ];
     * $response = ApiLib::sendRequest($url, $request);
     * 
     * $logData = [
     *     'url' => $url,
     *     'request' => $request,
     *     'response' => $response,
     * ];
     * Models_Payment::loger('payment-example', 'getPaymentLink', $logData);
     * 
     * </code>
     * 
     * @param string $code Код оплаты (Уникальный текстовый идентификатор).
     * @param string $action Логируемое действие.
     * @param mixed $content    Логируемые данные. Это может быть как числовое или строковое значение, так и массив или объект.
     *                          Если $content представляет из себя логический тип данных (true/false),
     *                          лучше преобразовать его в другой, текстовый или числовой перед логированием,
     *                          иначе в логе он будет отображаться как пустая строка.
     *                          Метод записывает $content в лог через print_r.
     * 
     * @return bool
     */
    public static function loger($code, $action, $content) {
        $enabledLogs = MG::getSetting('payments-logs', true);
        if (!$enabledLogs || !in_array($code, $enabledLogs)) {
            return false;
        }

        if (!is_dir(self::$paymentLogsDir)) {
            $paymentLogsDirArr = explode(DS, self::$paymentLogsDir);
            $currentDir = '';
            foreach ($paymentLogsDirArr as $paymentLogsDirPart) {
                $currentDir .= $paymentLogsDirPart;
                if (!is_dir($currentDir)) {
                    mkdir($currentDir);
                }
                $currentDir .= DS;
            }
        }

        $paymentLogsFile = self::$paymentLogsDir.DS.$code.'.php';

        ob_start();
        print_r($content);
        $preparedContent = ob_get_contents();
        ob_end_clean();
        $logData = '###LOG '.date('Y-m-d H:i:s')."\n";
        $logData .= '###ACTION '.$action."\n";
        $logData .= '###CONTENT_START'."\n";
        $logData .= $preparedContent."\n";
        $logData .= '###CONTENT_END'."\n\n";
        file_put_contents($paymentLogsFile, $logData, FILE_APPEND);
        return true;
    }

    /**
     * Проверка существования логов по коду оплаты.
     * 
     * @param string $code Код оплаты (Уникальный текстовый идентификатор).
     * 
     * @return bool true - у оплаты есть логи, false - логи отсутствуют.
     */
    public static function isLogsExists($code) {
        $result = is_file(self::$paymentLogsDir.DS.$code.'.php');
        return $result;
    }

    /**
     * Переключение логирования оплаты.
     * 
     * @param string $code Код оплаты (Уникальный текстовый идентификатор).
     * @param bool $active Флаг активности логирования. true - включить логирование, false - отключить.
     * 
     * @return bool
     */
    public static function togglePaymentLog($code, $active) {
        $payment = self::getPaymentByCode($code);
        if (!$payment) {
            return false;
        }
        $enabledLogs = MG::getSetting('payments-logs', true);
        if (!$enabledLogs) {
            $enabledLogs = [];
        }
        if ($active) {
            $paymentKey = array_search($code, $enabledLogs, true);
            if ($paymentKey === false) {
                $enabledLogs[] = $code;
            }
        } else {
            $paymentKey = array_search($code, $enabledLogs, true);
            if ($paymentKey !== false) {
                unset($enabledLogs[$paymentKey]);
                sort($enabledLogs);
            }
        }

        MG::setOption('payments-logs', $enabledLogs, true);
        return true;
    }

    /**
     * Удаляет логи оплаты.
     * 
     * @param string $code Код оплаты (Уникальный текстовый идентификатор).
     * 
     * @return bool
     */
    public static function clearLogs($code) {
        $logsFile = self::$paymentLogsDir.DS.$code.'.php';
        if (!is_file($logsFile)) {
            return true;
        }
        $result = unlink($logsFile);
        return $result;
    }

    /**
     * Отправляет на скачивание логи оплаты.
     * 
     * @param string $code Код оплаты (Уникальный текстовый идентификатор).
     * 
     * @return void
     */
    public static function downloadLogs($code) {
        $log = self::$paymentLogsDir.DS.$code.'.php';
        if (!is_file($log)) {
            return false;
        }
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename='.str_replace('.php', '.txt', basename($log)));
        header('Content-Type: text/plain');
        header('Content-Transfer-Encoding: binary');

        readfile($log);
        exit;
    }

    /**
     * Отключает оплату.
     * 
     * @param $id Идентификатор оплаты в базе данных.
     * 
     * @return bool
     */
    public static function disablePayment($id) {
        $disableSql = 'UPDATE '.self::$paymentTable.' '.
            'SET `activity` = 0 '.
            'WHERE `id` = '.DB::quoteInt($id);
        $result = DB::query($disableSql);
        return $result;
    }

    /**
     * Удаляет оплату и отключает / снимает с аренды плагин оплаты.
     * 
     * @param string $code Код оплаты (Уникальный текстовый идентификатор).
     * @param string $pluginCode Код плагина из маркетплейса.
     * 
     * @return bool
     */
    public static function deletePluginPayment($code, $pluginCode = null) {
        $args = func_get_args();
        $payment = self::getPaymentByCode($code);
        if (!$payment) {
            return false;
        }
        $deactivateResult = PM::deactivatePlugin($payment['plugin']);
        if (!$deactivateResult) {
            return false;
        }
        $result = self::deletePaymentByCode($code);
        MG::createHook(__CLASS__ . "_" . __FUNCTION__, $args, $result);
        return $result;
    }

    // Удаляет оплату из базы данных
    private static function deletePaymentByCode($code) {
        $deleteSql = 'DELETE FROM '.self::$paymentTable.' '.
            'WHERE `code` = '.DB::quote($code);
        $result = DB::query($deleteSql);
        return $result;
    }

    /**
     * Проверка, отосится ли оплата к устаревшей или новой системе.
     * 
     * @param int $id Идентификатор оплаты в базе данных.
     * 
     * @return bool true - если оплата устарела, false - если оплата новой системы
     */
    public static function checkPaymentOutdated($id) {
        $result = true;
        $checkPaymentOutdatedSql = 'SELECT `id` '.
            'FROM `'.PREFIX.'payment` '.
            'WHERE `id` = '.DB::quoteInt($id).' '.
            'AND `activity` = 1';
        if (MG::isNewPayment()) {
            $checkPaymentOutdatedSql .= ' '.
                'AND `code` NOT LIKE \'old#%\'';
        } else {
            $checkPaymentOutdatedSql .= ' '.
                'AND `code` LIKE \'old#%\'';
        }
        $checkPaymentOutdatedResult = DB::query($checkPaymentOutdatedSql);
        if (DB::fetchAssoc($checkPaymentOutdatedResult)) {
            $result = false;
        }
        return $result;
    }

    /**
     * Возвращает иконки оплат новой системы
     * 
     * @return array Массив, в котором ключ - идентификатор оплаты, значение - ссылка на иконку
     */
    public static function getPaymentsIcons() {
        $paymentsIcons = [];
        $paymentsIconsSql = 'SELECT `id`, `icon` '.
            'FROM '.self::$paymentTable.' '.
            'WHERE `code` NOT LIKE \'old#%\'';
        $paymentsIconsResult = DB::query($paymentsIconsSql);
        while ($paymentsIconsRow = DB::fetchAssoc($paymentsIconsResult)) {
            $paymentsIcons[$paymentsIconsRow['id']] = $paymentsIconsRow['icon'];
        }
        return $paymentsIcons;
    }

    /**
     * Проверяет, есть ли у оплаты платёжная форма
     * 
     * @param string $paymentId - идентификатор оплаты
     * 
     * @return bool Возвращает true, если оплата сейчас возвращает платёжную форму
     */
    public static function checkPaymentForm($paymentId) {
        $result = false;

        $payment = self::getPaymentById($paymentId);
        if (!empty($payment['plugin'])) {
            $paymentPlugin = $payment['plugin'];
            if (PM::isHookInReg($paymentPlugin)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Проверяет, что плагин оплаты не является платным или его тестовый период не истёк
     * 
     * @param string $pluginCode - код плагина оплаты в маркетплейсе
     * 
     * @return bool Возвращает true, если плагин можно использовать или false, если нельзя
     */
    public static function checkPaymentAvailable($pluginCode) {
        // Если не передан код плагина, то проверять не нужно
        if (!$pluginCode) {
            return true;
        }
        // Информация о целевом плагине оплаты из маркетплейса
        $targetMarketplacePlugin = null;
        // Список плагинов оплат, доступных в маркетплейсе
        $marketplacePaymentsPlugins = MarketplaceMain::getAdminData(1);
        foreach ($marketplacePaymentsPlugins as $marketplacePaymentPlugin) {
            if ($marketplacePaymentPlugin['mpCode'] == $pluginCode) {
                $targetMarketplacePlugin = $marketplacePaymentPlugin;
                break;
            }
        }
        // Если информации о плагине в маркетплейсе найти не удалось, то возвращаем, что его можно использовать
        if (!$targetMarketplacePlugin) {
            return true;
        }
        // Если плагин бесплатный - значит проверять его не нужно
        if (!floatval($targetMarketplacePlugin['price'])) {
            return true;
        }
        // Если куплен - проверять не нужно
        if ($targetMarketplacePlugin['bought']) {
            return true;
        }
        // Если есть триалка - то всё ок
        if (intval($targetMarketplacePlugin['button']['trial'])) {
            return true;
        }
        return false;
    }

    /**
     * Получает старые оплаты
     * 
     * @param string $onlyActive - флаг возвращения только активных оплат
     * 
     * @return array Возвращает массив старых оплат
     */
    public static function getOldPayments($onlyActive = true){
        $payments = [];

        $whereParts = [
            '`code` LIKE "old#%"',
        ];
        if ($onlyActive) {
            $whereParts[] = '`activity` = 1';
        }

        $paymentsSql = 'SELECT * FROM `'.PREFIX.'payment` '.
            'WHERE '.implode(' AND ', $whereParts);
        $paymentsResult = DB::query($paymentsSql);
        while ($paymentRow = DB::fetchAssoc($paymentsResult)) {
            $payments[$paymentRow['id']] = $paymentRow;
        }
        return $payments;
    }

}