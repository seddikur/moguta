<?php

/**
 * Модель: Order
 *
 * Класс Models_Order реализует логику взаимодействия с заказами покупателей.
 * - Проверяет корректность ввода данных в форме оформления заказа;
 * - Добавляет заказ в базу данных.
 * - Отправляет сообщения на электронные адреса пользователя и администраторов, при успешном оформлении заказа.
 * - Удаляет заказ из базы данных.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>

 * @package moguta.cms
 * @subpackage Model
 */
class Models_Order {

    // ФИО покупателя.
    public $fio;
    // Электронный адрес покупателя.
    public $email;
    // Контактный адрес покупателя.
    public $contact_email;
    // Телефон покупателя.
    public $phone;
    // Адрес покупателя.
    public $address;
    // Флаг нового пользователя.
    public $newUser = false;
    // Комментарий покупателя.
    public $info;
    // Автоматически созданный пароль для нового пользователя при оформлении первого заказа, можно вывести в email_order_layout
    public $passNewUser = '';
    // Дата доставки.
    public $dateDelivery;
    // Массив способов оплаты.
    public $_paymentArray = array();
    // ip пользователя при заказе
    public $ip;
    // Статичный массив статусов.
    static $status = array(
      0 => 'NOT_CONFIRMED',
      1 => 'EXPECTS_PAYMENT',
      2 => 'PAID',
      3 => 'IN_DELIVERY',
      4 => 'CANSELED',
      5 => 'EXECUTED',
      6 => 'PROCESSING',
    );
    static $statusUser = array();
    // кастомные поля для заказов
    public $optFields = array();
    public $address_parts = false;
    public $storage = '';
    public $name_parts = false;

    function __construct() {
        if (MG::isNewPayment()) {
            $payments = Models_Payment::getPayments(false, true, true);
            foreach ($payments as $payment) {
                $newParams = [];
                foreach ($payment['paramArray'] as $param) {
                    $newParams[$param['name']] = $param['value'];
                }
                $payment['paramArray'] = CRYPT::json_encode_cyr($newParams);
                $this->_paymentArray[$payment['id']] = $payment;
            }
        } else {
            $res = DB::query('SELECT  *  FROM `'.PREFIX.'payment` WHERE `code` LIKE \'old#%\' ORDER BY `sort`');
            $i = 0;
            while ($row = DB::fetchAssoc($res)) {
                $newparam = [];
                $param = json_decode($row['paramArray']);
                if (!empty($param)) {
                    foreach ($param as $key=>$value) {
                        if ($value != '') {
                            $value = CRYPT::mgDecrypt($value);
                        }
                        $newparam[$key] = $value;
                    }
                }

                $row['paramArray'] = CRYPT::json_encode_cyr($newparam);
                $this->_paymentArray[$row['id']] = $row;
            };
        }
    }

    /**
     * Проверяет корректность ввода данных в форму заказа и регистрацию в системе покупателя.
     * <code>
     * $arrayData = array(
     *  'email' => 'admin@admin.ru', // почта пользователя
     *  'phone' => '+7 (111) 111-11-11', // телефон пользователя
     *  'fio' => 'Администратор', // имя покупателя
     *  'address' => 'addr', // адрес доставки
     *  'info' => 'comment', // комментарий покупателя
     *  'customer' => 'fiz', // плательщик (fiz - физическое лицо, yur - юридическое)
     *  'yur_info' => Array(
     *         'nameyur' => null, // название юр лица
     *         'adress' => null, // адрес юр лица
     *         'inn' => null, // инн юр лица
     *         'kpp' => null, // кпп юр лица
     *         'bank' => null, // банк юр лица
     *         'bik' => null, // бик юр лица
     *         'ks' => null, // К/Сч юр лица
     *         'rs' => null, // Р/Сч юр лица
     *     ),
     *  'delivery' => 1, // ID доставки
     *  'date_delivery' => '08.03.2018', // дата доставки
     *  'payment' => 2 // ID оплаты
     * );
     * $order = new Models_Order;
     * $order->isValidData($arrayData);
     * </code>
     * @param array $arrayData  массив с ведёнными пользователем данными.
     * @param array $require обязательные поля к заполнению
     * @param bool $createUser создавать ли нового пользователя, если нет такого
     * @param string $error ошибка
     * @return bool|string $error сообщение с ошибкой в случае некорректных данных.
     */
    public function isValidData($arrayData, $require = array('email','phone','payment'),$createUser = true , $error = null) {
        $result = null;
        $this->newUser = false;
        if($createUser) {
            // Если пользователь авторизован => электронный адрес зарегистрирован в системе.
            $currenUser = USER::getThis();
            if(isset($arrayData['email']) && !empty($currenUser->login_email) && USER::getUserInfoByEmail($currenUser->login_email,'login_email')){
                // добавляем контактный телефон
                if(isset($arrayData['phone']) && empty($currenUser->phone)){
                    $data['phone'] = $arrayData['phone'];
                    User::update($currenUser->id, $data);
                }
                // добавляем контактный email
                if(isset($arrayData['email']) && empty($currenUser->email)){
                    $data['email'] = $arrayData['email'];
                    User::update($currenUser->id, $data);
                }
            } else {
                if(USER::getUserInfoByEmail($arrayData['email'],'login_email')){
                    // $error = "<span class='user-exist'>Пользователь с таким email существует.
                    //   Пожалуйста, <a href='".SITE."/enter?location=".SITE.$_SERVER['REQUEST_URI']."'>войдите в систему</a> используя
                    //   свой электронный адрес и пароль!</span>";
                    // $error = "<span class='user-exist'>".MG::restoreMsg('msg__email_in_use',array('#LINK#' => SITE."/enter?location=".SITE.$_SERVER['REQUEST_URI']))."</span>";
                    // Иначе новый пользователь.

                } elseif (!empty($arrayData['phone']) && $byPhoneEmail = USER::getUserEmailByPhone($arrayData['phone'])) {
                    $arrayData['email'] = $byPhoneEmail;
                } else {
                    $this->newUser = true;
                    if (!empty($arrayData['email'])) {
                        $mailsBlackListSetting = MG::getSetting('mailsBlackList');
                        $mailsBlackList = explode(' ', trim($mailsBlackListSetting));

                        if ($mailsBlackList) {
                            foreach ($mailsBlackList as $forbiddenMail) {
                                if (!$forbiddenMail) {
                                    continue;
                                }
                                if (substr($arrayData['email'], -strlen($forbiddenMail)) === $forbiddenMail) {
                                    $error .= '<span class="order-error-email">'.MG::restoreMsg('msg__reg_blocked_email').'</span>';
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($require)) {
            $requiredFields = Models_OpFieldsOrder::getRequiredFields();
            $checkboxFields = array();
            $res = DB::query("SELECT `id` FROM `".PREFIX."order_opt_fields` WHERE `droped` = 0 AND `type` = 'checkbox'");
            while ($row = DB::fetchAssoc($res)) {
                $checkboxFields[] = 'opf_'.$row['id'];
            }

            foreach ($requiredFields as $requiredField) {
                if ($requiredField == 'email' && !filter_var($arrayData['email'], FILTER_VALIDATE_EMAIL)) {
                    $error = "<span class='order-error-email'>".MG::restoreMsg('msg__email_incorrect')."</span>";
                } elseif (!isset($arrayData[$requiredField]) || $arrayData[$requiredField] === '') {
                    if ($requiredField == 'phone') {
                        $error = "<span class='no-phone'>".MG::restoreMsg('msg__phone_incorrect')."</span>";
                    } else {
                        if (in_array($requiredField, $checkboxFields) && isset($arrayData[$requiredField])) {
                            # ok
                        } else {
                            $error = "<span class='no-phone'>".MG::restoreMsg('msg__payment_required').': '.Models_OpFieldsOrder::getFieldTitle($requiredField)."</span>";
                        }
                    }
                }
            }
        }
        if(isset($arrayData['date_delivery'])){
            $idDelivery = $arrayData['delivery'];
            $delivery = $this->getDeliveryMethod();
            if(!empty($delivery)){
                $settingsDate = (!empty($delivery[$idDelivery]['date_settings'])) ?  json_decode($delivery[$idDelivery]['date_settings'], true) : '';
                if(!empty($settingsDate['requiredDate'])){
                   if(!preg_match('/\d{2}\.\d{2}.\d{4}/', $arrayData['date_delivery'])){
                       $error = "<span>".MG::restoreMsg('msg__date_delivery_incorrect')."</span>";
                    }
                }
            }           
        }

        if(in_array('payment', $require)) {
            // Не указан способ оплаты.
            if (empty($arrayData['payment'])) {
                // $error = "<span class='no-phone'>Выберите способ оплаты!</span>";
                $error = "<span class='no-phone'>".MG::restoreMsg('msg__payment_incorrect')."</span>";
            }
        }
        if (MG::getSetting('captchaOrder') == 'true' && (empty($_POST['ajax']) || $_POST['ajax'] != 'buyclickflag')) {// если при оформлении капча.
            if (MG::getSetting('useReCaptcha') == 'true' && MG::getSetting('reCaptchaSecret') && MG::getSetting('reCaptchaKey')) {
                if (!MG::checkReCaptcha() && !URL::get('addOrderOk')) {
                    $error = "<span class='no-phone'>".MG::restoreMsg('msg__recaptcha_incorrect')."</span>";
                }
            }
            else{
                if (empty($arrayData['capcha']) || (strtolower($arrayData['capcha']) != strtolower($_SESSION['capcha']))) {
                    // $error = "<span class='no-phone'>Неверно введен код с картинки!</span>";
                    $error = "<span class='no-phone'>".MG::restoreMsg('msg__captcha_incorrect')."</span>";
                }
            }
        }
        // проверка  наличия товара товаров, пока человек оформляет заказ -товар может купить другой пользователь
        if (!empty($_SESSION['cart'])
          && !MG::enabledStorage()
        ) {
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['variantId']) {
                    $res_var = DB::query('
            SELECT  pv.id, p.`title`, pv.`title_variant`, pv.count
            FROM `'.PREFIX.'product_variant` pv   
            LEFT JOIN `'.PREFIX.'product` as p ON 
            p.id = pv.product_id   
            WHERE pv.id ='.DB::quote($item['variantId']));
                    if ($prod = DB::fetchArray($res_var)) {
                        if ($prod['count'] >=0 && $prod['count'] < $_SESSION['cart'][$key]['count']) {
                            if ($prod['count'] == 0) {
                                // $error .= "<p>Товара ".$prod['title'].' '.$prod['title_variant']." уже нет в наличии. Для оформления заказа его необходимо удалить из корзины.</p>";
                                $error .= "<p>".MG::restoreMsg('msg__product_ended',array('#PRODUCT#' => $prod['title'].' '.$prod['title_variant']))."</p>";
                            } else {
                                // $error .= "<p>Товар ".$prod['title'].' '.$prod['title_variant']." доступен в количестве ".$prod['count']." шт. Для оформления заказа измените количество в корзине.</p>";
                                $error .= "<p>".MG::restoreMsg('msg__product_ending',array('#PRODUCT#' => $prod['title'].' '.$prod['title_variant'], '#COUNT#' => $prod['count']))."</p>";
                            }
                        }
                    }
                } else {
                    $res_pr = DB::query('
            SELECT id, title, count
            FROM `'.PREFIX.'product` p 
            WHERE id ='.DB::quote($item['id']));
                    if ($prod = DB::fetchArray($res_pr)) {
                        if ($prod['count'] >=0 && $prod['count'] < $_SESSION['cart'][$key]['count']) {
                            if ($prod['count'] == 0) {
                                // $error .= "<p>Товара ".$prod['title']." уже нет в наличии. Для оформления заказа его необходимо удалить из корзины.</p>";
                                $error .= "<p>".MG::restoreMsg('msg__product_ended',array('#PRODUCT#' => $prod['title']))."</p>";
                            } else {
                                // $error .= "<p>Товар ".$prod['title']." доступен в количестве ".$prod['count']." шт. Для оформления заказа измените количество в корзине.</p>";
                                $error .= "<p>".MG::restoreMsg('msg__product_ending',array('#PRODUCT#' => $prod['title'], '#COUNT#' => $prod['count']))."</p>";
                            }
                        }
                    }
                }
            }
        }

        // NEW VARIANT
        // Если используются склады и включена опция, что все товары в заказе должны быть в наличии на одном и том же складе
        if (MG::enabledStorage() && MG::getSetting('useOneStorage') == 'true') {
            // Какой склад был выбран пользователем (если пустая строка или "all" - то используется любой подходящий склад)
            $neededStorage = '';

            // Проверка, есть ли у способа доставки выбор складов (если есть, значит, пользователь выбирал конкретный склад)
            // если такого выбора нет, значит используется любой подходящий склад
            $deliveryId = intval($arrayData['delivery']);
            $deliveryShowStoragesSql = 'SELECT `show_storages` '.
                'FROM `'.PREFIX.'delivery` '.
                'WHERE `id` = '.DB::quoteInt($deliveryId);
            $deliveryShowStoragesResult = DB::query($deliveryShowStoragesSql);
            if ($deliveryShowStoragesRow = DB::fetchAssoc($deliveryShowStoragesResult)) {
                $showStorages = !!intval($deliveryShowStoragesRow['show_storages']);
                if ($showStorages) {
                    $orderStorage = $arrayData['storage'];
                    if ($orderStorage !== 'all') {
                        $neededStorage = $orderStorage;
                    }
                }
            }

            // Здесь мы собираем список идентификаторов складов, которые вообще используются в магазине
            // В будущем из этого списка будут убраны те склады, с которых нельзя собрать заказ
            $rawStorages = MG::getSetting('storages', true);
            $storagesIds = [];
            foreach ($rawStorages as $storageData) {
                $storagesIds[] = $storageData['id'];
            }

            // Получение остатка по складам происходит через модель товара
            $productModel = new Models_Product();
            // Перебираем все товары в корзине
            foreach ($_SESSION['cart'] as $cartItem) {
                $cartItemId = intval($cartItem['id']);
                $cartItemVaraiantId = intval($cartItem['variantId']);
                $cartItemCount = floatval($cartItem['count']);

                // Если для заказа выбран конкретный склад
                if ($neededStorage) {
                    // Получаем остаток с него по конкретному товару из корзины
                    $cartItemStorageCount = $productModel->getProductStorageCount($neededStorage, $cartItemId, $cartItemVaraiantId);
                    // Если количество на складе - бесконечность
                    if ($cartItemStorageCount < 0) {
                        // То ничего проверять не нужно
                        continue;
                    }
                    // А если остатка на складе меньше, чем товара в заказе
                    if ($cartItemStorageCount < $cartItemCount) {
                        // То готовим назание товара для ошибки
                        $forTitle = $item['title'];
                        if ($cartItemVaraiantId) {
                            $forTitleSql = 'SELECT CONCAT(`title`, `title_variant`) AS fulltitle '.
                                'FROM `'.PREFIX.'product` AS p '.
                                'LEFT JOIN `'.PREFIX.'product_variant` AS pv '.
                                    'ON p.`id` = pv.`product_id` '.
                                'WHERE p.`id` = '.DB::quoteInt($item['id']).' AND '.
                                    'pv.`id` = '.DB::quoteInt($cartItemVaraiantId);
    
                            $forTitleResult = DB::query($forTitleSql);
                            if ($forTitleRow = DB::fetchAssoc($forTitleResult)) {
                                $forTitle = $forTitleRow['fulltitle'];
                            }
                        }
                        // Если хоть сколько-то товара ещё есть
                        if ($cartItemStorageCount) {
                            // То добавляем ошибку, что товара в наличии меньше, чем в заказе
                            $error .= '<p>'.MG::restoreMsg(
                                'msg__product_ending',
                                [
                                    '#PRODUCT#' => $forTitle,
                                    '#COUNT#' => $cartItemStorageCount,
                                ]
                            ).'</p>';
                            continue;
                        }
                        // А если товара вообще нет, то сообщаем, что он закончився
                        $error .= '<p>'.MG::restoreMsg(
                            'msg__product_ended',
                            ['#PRODUCT#' => $forTitle]
                        ).'</p>';
                        continue;
                    }
                // Если не задано с какого конкретно склаад списывать товар
                } else {
                    // Получаем количество товара из корзины по всем складам
                    $cartItemStoragesData = $productModel->getProductStoragesData($cartItemId, $cartItemVaraiantId);
                    // Перебираем склады с количествами для конкретного твоара
                    foreach ($cartItemStoragesData as $cartItemStorageId => $cartItemStorageCount) {
                        if (
                            // Если остаток по складу не бесконечный
                            $cartItemStorageCount >= 0 &&
                            // И его меньше, чем твоара в корзине
                            $cartItemStorageCount < $cartItemCount
                        ) {
                            // То такой склад убираем из списка доступных для данного заказа складов
                            $storagesIds = array_filter($storagesIds, function($storageId) use ($cartItemStorageId) {
                                $result = $cartItemStorageId != $storageId;
                                return $result;
                            });
                        }
                    }
                    $itemStorages = array_keys($cartItemStoragesData);
                    $invalidStoragesIds = array_diff($storagesIds, $itemStorages);
                    $storagesIds = array_diff($storagesIds, $invalidStoragesIds);
                    // Если не осталось ни одного подходящего склада
                    if (empty($storagesIds)) {
                        // То добавляем соотвествующую ошибку
                        $error .= '<p>'.MG::restoreMsg('msg__products_not_same_storage').'</p>';
                        break;
                    }
                }
            }
        }

        // OLD VARIANT
        // Проверка если включены склады и списывание должно быть только с одного скалада
        // if(MG::enabledStorage() && MG::getSetting('useOneStorage') == 'true'){
        //     $neededStorage = '';

        //     $deliveryId = $arrayData['delivery'];
        //     $deliveryShowStoragesSql = 'SELECT `show_storages` '.
        //         'FROM `'.PREFIX.'delivery` '.
        //         'WHERE `id` = '.DB::quoteInt($deliveryId);
        //     $deliveryShowStoragesResult = DB::query($deliveryShowStoragesSql);
        //     if ($deliveryShowStoragesRow = DB::fetchAssoc($deliveryShowStoragesResult)) {
        //         if (intval($deliveryShowStoragesRow['show_storages']) === 1) {
        //             $neededStorage = $arrayData['storage'] === 'all' ? '' : $arrayData['storage'];
        //         }
        //     }

        //     $rawStorages = MG::getSetting('storages', true);
        //     $storagesIds = [];
        //     foreach ($rawStorages as $storage) {
        //         $storagesIds[] = $storage['id'];
        //     }
        //     foreach ($_SESSION['cart'] as $item) {
        //         $variantId = 0;
        //         if (!empty($item['variantId'])) {
        //             $variantId = $item['variantId'];
        //         }
        //         $storagesSql = 'SELECT `storage`, `count` '.
        //             'FROM `'.PREFIX.'product_on_storage` '.
        //             'WHERE `product_id` = '.DB::quoteInt($item['id']).' AND '.
        //                 '`variant_id` = '.DB::quoteInt($variantId).' AND '.
        //                 '`count` != 0';
        //         $storagesResult = DB::query($storagesSql);
        //         $itemStorages = [];
        //         while ($storagesRow = DB::fetchAssoc($storagesResult)) {
        //             if ($storagesRow['count'] < 0 || $storagesRow['count'] >= $item['count']) {
        //                 $itemStorages[] = $storagesRow['storage'];
        //             }
        //         }
        //         $storagesIds = array_filter($storagesIds, function ($storageId) use ($itemStorages) {
        //             $test1 = in_array($storageId, $itemStorages);
        //             return in_array($storageId, $itemStorages);
        //         });
        //         if (empty($storagesIds) || ($neededStorage && !in_array($neededStorage, $storagesIds))) {
        //             $forTitle = $item['title'];
        //                 if ($variantId) {
        //                 $forTitleSql = 'SELECT CONCAT(`title`, `title_variant`) AS fulltitle '.
        //                     'FROM `'.PREFIX.'product` AS p '.
        //                     'LEFT JOIN `'.PREFIX.'product_variant` AS pv '.
        //                         'ON p.`id` = pv.`product_id` '.
        //                     'WHERE p.`id` = '.DB::quoteInt($item['id']).' AND '.
        //                         'pv.`id` = '.DB::quoteInt($variantId);

        //                 $forTitleResult = DB::query($forTitleSql);
        //                 if ($forTitleRow = DB::fetchAssoc($forTitleResult)) {
        //                     $forTitle = $forTitleRow['fulltitle'];
        //                 }
        //             }
        //             if ($neededStorage) {
        //                 $forTitle = $item['title'];
        //                 if ($variantId) {
        //                     $forTitleSql = 'SELECT CONCAT(`title`, `title_variant`) AS fulltitle '.
        //                         'FROM `'.PREFIX.'product` AS p '.
        //                         'LEFT JOIN `'.PREFIX.'product_variant` AS pv '.
        //                             'ON p.`id` = pv.`product_id` '.
        //                         'WHERE p.`id` = '.DB::quoteInt($item['id']).' AND '.
        //                             'pv.`id` = '.DB::quoteInt($variantId);
    
        //                     $forTitleResult = DB::query($forTitleSql);
        //                     if ($forTitleRow = DB::fetchAssoc($forTitleResult)) {
        //                         $forTitle = $forTitleRow['fulltitle'];
        //                     }
        //                 }
        //                 $storageCount = 0;
        //                 $storageCountSql = 'SELECT `count` '.
        //                     'FROM `'.PREFIX.'product_on_storage` '.
        //                     'WHERE `storage` = '.DB::quote($neededStorage).' AND '.
        //                         '`product_id` = '.DB::quoteInt($item['id']).' AND '.
        //                         '`variant_id` = '.DB::quoteInt($variantId);
        //                 $storageCountResult = DB::query($storageCountSql);
        //                 if ($storageCountRow = DB::fetchAssoc($storageCountResult)) {
        //                     $storageCount = floatval($storageCountRow['count']);
        //                 }

        //                 if (floatval($storageCount) !== floatval(0)) {
        //                     $error .= '<p>'.MG::restoreMsg(
        //                         'msg__product_ending',
        //                         [
        //                             '#PRODUCT#' => $forTitle,
        //                             '#COUNT#' => $storageCount,
        //                         ]
        //                     ).'</p>';
        //                 } else {
        //                     $error .= '<p>'.MG::restoreMsg(
        //                         'msg__product_ended',
        //                         ['#PRODUCT#' => $forTitle]
        //                     ).'</p>';
        //                 }
        //             } else {
        //                 $error .= '<p>'.MG::restoreMsg('msg__products_not_same_storage').'</p>';
        //             }
        //         }
        //     }
        // }
        // проерка на складах
        if(MG::enabledStorage() && MG::getSetting('useOneStorage') == 'false') {
            // NEW NEW VARIANT
            // if (!isset($arrayData['storage'])) {
            //     $error .= '<p>'.
            //         MG::restoreMsg('msg__storage_non_selected').
            //         '</p>';
            // } else {
                // Какой склад был выбран пользователем (если пустая строка или "all" - то используется любой подходящий склад)
                $neededStorage = '';

                // Проверка, есть ли у способа доставки выбор складов (если есть, значит, пользователь выбирал конкретный склад)
                // если такого выбора нет, значит используется любой подходящий склад
                $deliveryId = intval($arrayData['delivery']);
                $deliveryShowStoragesSql = 'SELECT `show_storages` '.
                    'FROM `'.PREFIX.'delivery` '.
                    'WHERE `id` = '.DB::quoteInt($deliveryId);
                $deliveryShowStoragesResult = DB::query($deliveryShowStoragesSql);
                if ($deliveryShowStoragesRow = DB::fetchAssoc($deliveryShowStoragesResult)) {
                    $showStorages = !!intval($deliveryShowStoragesRow['show_storages']);
                    if ($showStorages) {
                        $orderStorage = $arrayData['storage'];
                        if ($orderStorage !== 'all') {
                            $neededStorage = $orderStorage;
                        }
                    }
                }

                $productModel = new Models_Product();
                foreach ($_SESSION['cart'] as $cartItem) {
                    $cartItemId = intval($cartItem['id']);
                    $cartItemVariantId = intval($cartItem['variantId']);
                    $cartItemCount = floatval($cartItem['count']);

                    if ($neededStorage && $neededStorage !== 'all') {
                        $cartItemStoragesCount = $productModel->getProductStorageCount($neededStorage, $cartItemId, $cartItemVariantId);
                    } else {
                        $cartItemStoragesCount = $productModel->getProductStorageTotalCount($cartItemId, $cartItemVariantId);
                    }
                    if ($cartItemStoragesCount < 0) {
                        continue;
                    }
                    if ($cartItemStoragesCount < $cartItemCount) {
                        $forTitle = $cartItem['title'];
                        if (empty($forTitle)) {
                            $productTitleSql = 'SELECT `title` '.
                                'FROM `'.PREFIX.'product` '.
                                'WHERE `id` = '.DB::quoteInt($cartItemId);
                            $productTitleResult = DB::query($productTitleSql);
                            if ($productTitleRow = DB::fetchAssoc($productTitleResult)) {
                                $forTitle = trim($productTitleRow['title']);
                            }
                        }
                        if ($cartItemVariantId) {
                            $forTitleSql = 'SELECT CONCAT(`title`, `title_variant`) AS fulltitle '.
                                'FROM `'.PREFIX.'product` AS p '.
                                'LEFT JOIN `'.PREFIX.'product_variant` AS pv '.
                                    'ON p.`id` = pv.`product_id` '.
                                'WHERE p.`id` = '.DB::quoteInt($cartItemId).' AND '.
                                    'pv.`id` = '.DB::quoteInt($cartItemVariantId);
    
                            $forTitleResult = DB::query($forTitleSql);
                            if ($forTitleRow = DB::fetchAssoc($forTitleResult)) {
                                $forTitle = $forTitleRow['fulltitle'];
                            }
                        }
                        if ($cartItemStoragesCount) {
                            $error .= '<p>'.
                                MG::restoreMsg(
                                    'msg__product_ending',
                                    [
                                        '#PRODUCT#' => $forTitle,
                                        '#COUNT#' => $cartItemStoragesCount,
                                    ]
                                ).
                                '</p>';
                            continue;
                        } else {
                            $error .= '<p>'.
                                MG::restoreMsg(
                                    'msg__product_ended',
                                    ['#PRODUCT#' => $forTitle]
                                ).
                                '</p>';
                        }
                    }
                }
            // }

            // NEW VARIANT
            // if (!isset($arrayData['storage'])) {
            //     $error .= '<p>'.
            //         MG::restoreMsg('msg_storage_non_selected').
            //         '</p>';
            // } else {
            //     $productModel = new Models_Product();
            //     foreach($_SESSION['cart'] as $cartItem) {
            //         $cartItemId = intval($cartItem['id']);
            //         $cartItemVariantId = intval($cartItem['variantId']);
            //         $cartItemCount = floatval($cartItem['count']);
            //         $storagesData = $productModel->getProductStoragesData($cartItemId, $cartItemVariantId);
            //         $cartItemAvailable = false;
            //         $maxCount = 0;
            //         foreach ($storagesData as $storageCount) {
            //             if (
            //                 $storageCount < 0 ||
            //                 $storageCount >= $cartItemCount
            //             ) {
            //                 $cartItemAvailable = true;
            //             }
            //             if ($storageCount > $maxCount) {
            //                 $maxCount = $storageCount;
            //             }
            //         }
            //         // Если ни на одном из складов не хватает товара
            //         if (!$cartItemAvailable) {
            //             $forTitle = $item['title'];
            //             if ($cartItemVaraiantId) {
            //                 $forTitleSql = 'SELECT CONCAT(`title`, `title_variant`) AS fulltitle '.
            //                     'FROM `'.PREFIX.'product` AS p '.
            //                     'LEFT JOIN `'.PREFIX.'product_variant` AS pv '.
            //                         'ON p.`id` = pv.`product_id` '.
            //                     'WHERE p.`id` = '.DB::quoteInt($item['id']).' AND '.
            //                         'pv.`id` = '.DB::quoteInt($cartItemVaraiantId);
    
            //                 $forTitleResult = DB::query($forTitleSql);
            //                 if ($forTitleRow = DB::fetchAssoc($forTitleResult)) {
            //                     $forTitle = $forTitleRow['fulltitle'];
            //                 }
            //             }
            //             // Если товар есть в наличии, просто его меньше, чем нужно
            //             if ($maxCount) {
            //                 $error .= '<p>'.
            //                     MG::restoreMsg(
            //                         'msg__product_ending',
            //                         [
            //                             '#PRODUCT#' => $forTitle,
            //                             '#COUNT#' => $maxCount,
            //                         ]
            //                     ).
            //                     '</p>';
            //             } else {
            //                 $error .= '<p>'.
            //                     MG::restoreMsg(
            //                         'msg__product_ended',
            //                         ['#PRODUCT#' => $forTitle]
            //                     ).
            //                     '</p>';
            //             }
            //         }
            //     }
            // }

            // OLD VARIANT
            // if (!isset($arrayData['storage'])) {
            //     $error .= "<p>".MG::restoreMsg('msg__storage_non_selected')."</p>";
            // } else {
            //     $error .= MG::checkStorageOnOrderCreate($arrayData['storage']);
            // }
        }

        // Если нет ошибок, то заносит информацию в поля класса.
        if (!empty($error)) {
            $result = $error;
        } else {
            $optFields = array();
            $optionalFields = Models_OpFieldsOrder::getFields();
            foreach ($optionalFields as $item) {
                if ($item['type'] == 'file') {
                    if (!empty($_FILES['opf_'.$item['id']]['name']) && $_FILES['opf_'.$item['id']]['error'] == 0) {
                        @mkdir(SITE_DIR.'uploads'.DS.'prodtmpimg');
                        @mkdir(SITE_DIR.'uploads'.DS.'prodtmpimg'.DS.'order');
                        copy($_FILES['opf_'.$item['id']]['tmp_name'], SITE_DIR.'uploads'.DS.'prodtmpimg'.DS.'order'.DS.$_FILES['opf_'.$item['id']]['name']);
                        $optFields[$item['id']] = $_FILES['opf_'.$item['id']]['name'];
                        $_POST['opf_'.$item['id']] = $arrayData['opf_'.$item['id']] = $_FILES['opf_'.$item['id']]['name'];
                    }
                } else {
                    $optFields[$item['id']] = isset($arrayData['opf_'.$item['id']])?$arrayData['opf_'.$item['id']]:null;
                    if ($item['type'] == 'checkbox' && isset($arrayData['opf_'.$item['id']])) {
                        $optFields[$item['id']] = 'true';
                    }
                }
            }

            $this->optFields = $optFields;

            $cart = new Models_Cart();
            $summ = $cart->getTotalSumm();

            if (!empty($arrayData['fio_sname']) || !empty($arrayData['fio_name']) || !empty($arrayData['fio_pname'])) {
                $this->name_parts = addslashes(serialize(array(
                  'sname' => isset($arrayData['fio_sname'])?trim($arrayData['fio_sname']):null,
                  'name' => isset($arrayData['fio_name'])?trim($arrayData['fio_name']):null,
                  'pname' => isset($arrayData['fio_pname'])?trim($arrayData['fio_pname']):null,
                )));
                $this->fio = null;
            } else {
                $this->fio = isset($arrayData['fio'])?trim($arrayData['fio']):null;
                $this->name_parts = null;
            }

            if (
              !empty($arrayData['address_index']) ||
              !empty($arrayData['address_country']) ||
              !empty($arrayData['address_region']) ||
              !empty($arrayData['address_city']) ||
              !empty($arrayData['address_street']) ||
              !empty($arrayData['address_house']) ||
              !empty($arrayData['address_flat'])) {
                $deliveryArrdessParts = array(
                  'index' => isset($arrayData['address_index'])?$arrayData['address_index']:null,
                  'country' => isset($arrayData['address_country'])?$arrayData['address_country']:null,
                  'region' => isset($arrayData['address_region'])?$arrayData['address_region']:null,
                  'city' => isset($arrayData['address_city'])?$arrayData['address_city']:null,
                  'street' => isset($arrayData['address_street'])?$arrayData['address_street']:null,
                  'house' => isset($arrayData['address_house'])?$arrayData['address_house']:null,
                  'flat' => isset($arrayData['address_flat'])?$arrayData['address_flat']:null,
                );
                $this->address_parts = addslashes(serialize($deliveryArrdessParts));
                $this->address = '';
            } else {
                $this->address = isset($arrayData['address'])?trim($arrayData['address']):'';
            }

            $this->email = isset($arrayData['email'])?trim($arrayData['email']):null;

            if(!empty($_SESSION['user']->login_email)){
                $this->email = $_SESSION['user']->login_email;
            }
            $this->contact_email = isset($arrayData['email'])?trim($arrayData['email']):null;
            if (!empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) && $_POST['email'] !== $this->contact_email) {
                $this->contact_email = $_POST['email'];
            }
            $this->phone = isset($arrayData['phone'])?trim($arrayData['phone']):null;
            $this->info = isset($arrayData['info'])?trim($arrayData['info']):null;
            $this->delivery = $arrayData['delivery'];
            $this->dateDelivery = isset($arrayData['date_delivery'])?$arrayData['date_delivery']:null;
            $this->interval = isset($arrayData['delivery_interval'])?trim($arrayData['delivery_interval']):null;
            $deliv = new Delivery();
            $tmp = $deliv->getCostDelivery($arrayData['delivery']);

            $rate = 0;
            $res = DB::query('SELECT rate FROM '.PREFIX.'payment WHERE id = '.DB::quoteInt($arrayData['payment']));
            if($row = DB::fetchAssoc($res)) {
                $rate = $row['rate'];
            }

            $this->storage = isset($arrayData['storage'])?$arrayData['storage']:null;
            $tmp = MG::convertPrice($tmp);

            if(MG::getSetting('enableDeliveryCur') == 'true') {
                $this->delivery_cost = MG::numberDeFormat(MG::numberFormat($tmp * (1+$rate)));
            } else {
                $this->delivery_cost = $tmp;
            }
            $this->payment = $arrayData['payment'];
            $this->summ = $summ;
            $this->ip = $_SERVER['REMOTE_ADDR'];
            $result = false;
            // если существуют данные сохраненного пост запроса, значит был редирект на страницу с ?addOrderOk=1 для отслеживания цели в метрике яндекса
            // значит теперь можно создать пользователя, во второй итерации данного метода
            if(!empty($_SESSION['post']) && empty($_SESSION['user']->login_email)){
                $this->addNewUser($arrayData);
            }
        }

        $args = func_get_args();
        $args['this'] = &$this;
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Если заказ оформляется впервые на нового покупателя, то создает новую запись в таблице пользователей.
     * <code>
     * $model = new Models_Order();
     * $model->newUser = true;
     * $model->email = 'user@mail.mail';
     * $model->fio = 'username';
     * $model->address = 'адрес';
     * $model->phone = '8 (555) 555-55-55';
     * $model->ip = '127.0.0.1';
     *
     * $model->addNewUser();
     * </code>
     */
    public function addNewUser($data = array()) {
        // Если заказ производит новый пользователь, то регистрируем его
        if (MG::getSetting('autoRegister') == "true") {
            $activity = 0;
            if (MG::getSetting('confirmRegistration') != "true") {
                $activity = 1;
            }
            if ($this->newUser) {

                $this->passNewUser = MG::genRandomWord(10);

                $userArr = array(
                  'login_email' => $this->email,
                  'email' => $this->email,
                  'role' => 2,
                  'name' => !empty($data['fio_name'])?$data['fio_name']:($this->fio ? $this->fio : 'Пользователь'),
                  'sname' => !empty($data['fio_sname'])?$data['fio_sname']:null,
                  'pname' => !empty($data['fio_pname'])?$data['fio_pname']:null,
                  'pass' => $this->passNewUser,
                  'address' => $this->address,
                  'phone' => $this->phone,
                  'login_phone' => !empty($this->phone) ? preg_replace('/[^\d]/','',$this->phone) : '',
                  'ip' => $this->ip,
                  'nameyur' => isset($_POST['yur_info']['nameyur'])?$_POST['yur_info']['nameyur']:null,
                  'adress' => isset($_POST['yur_info']['adress'])?$_POST['yur_info']['adress']:null,
                  'inn' => isset($_POST['yur_info']['inn'])?$_POST['yur_info']['inn']:null,
                  'kpp' => isset($_POST['yur_info']['kpp'])?$_POST['yur_info']['kpp']:null,
                  'bank' => isset($_POST['yur_info']['bank'])?$_POST['yur_info']['bank']:null,
                  'bik' => isset($_POST['yur_info']['bik'])?$_POST['yur_info']['bik']:null,
                  'ks' => isset($_POST['yur_info']['ks'])?$_POST['yur_info']['ks']:null,
                  'rs' => isset($_POST['yur_info']['rs'])?$_POST['yur_info']['rs']:null,
                  'activity' => $activity
                );

                if (!empty($this->address_parts)) {
                    $tmp = unserialize(stripcslashes($this->address_parts));
                    foreach ($tmp as $key => $value) {
                        $userArr['address_'.$key] = $value;
                    }
                }
                if(!empty($this->email) || !empty($this->phone)){
                    $userId = USER::add($userArr);
                    $newUser = USER::getUserById($userId);
                    $this->email = $newUser->login_email;
                }
            }
        }
    }

    /**
     * Сохраняет заказ в базу сайта.
     * Добавляет в массив корзины третий параметр 'цена товара', для сохранения в заказ.
     * Это нужно для того, чтобы в последствии вывести детальную информацию о заказе.
     * Если оставить только id то информация может оказаться неверной, так как цены меняются.
     * @see Models_Order::isValidData() входящий массив
     * <code>
     * $model = new Models_Order();
     * $model->isValidData($arrayData);
     * $orderId = $model->addOrder();
     * echo $orderId;
     * </code>
     * @param bool $adminOrder пришел ли заказ из админки
     * @return int $id номер заказа.
     */
    public function addOrder($adminOrder = false) {
        $itemPosition = new Models_Product();
        $cart = new Models_Cart();
        $catalog = new Models_Catalog();
        $categoryArray = $catalog->getCategoryArray();
        $this->summ = $this->summTotal = 0;
        $currencyRate = MG::getSetting('currencyRate');
        $currencyShopIso = MG::getSetting('currencyShopIso');

        // Массив запросов на обновление количества товаров.
        $updateCountProd = array();
        $variant_update = array();
        $product_update = array();
        $productPositions = array();
        // Добавляем в массив корзины параметр 'цена товара'.
        if ($adminOrder) {
//mg::loger($adminOrder);
            if(isset($adminOrder['user_email'])){
                $this->email = $adminOrder['user_email'];
            }
            $this->contact_email = $adminOrder['contact_email'];
            $this->phone = $adminOrder['phone'];
            $this->delivery = $adminOrder['delivery_id'];
            $this->dateDelivery = $adminOrder['date_delivery'];
            $this->delivery_cost = $adminOrder['delivery_cost'];
            $this->payment = $adminOrder['payment_id'];
            
            if(isset($adminOrder['storage'])){
              $this->storage = $adminOrder['storage'];
            }
            $this->fio = $adminOrder['name_buyer'];
            $this->address = $adminOrder['address'];

            if (isset($adminOrder['address_parts'])) {
                $this->address_parts = $adminOrder['address_parts'];
            }
            if (isset($adminOrder['name_parts'])) {
                $this->name_parts = $adminOrder['name_parts'];
            }
            if (!empty($adminOrder['delivery_interval'])) {
                $this->interval = $adminOrder['delivery_interval'];
            }

            $formatedDate = date('Y-m-d H:i:s'); // Форматированная дата ГГГГ-ММ-ДД ЧЧ:ММ:СС.

            foreach ($adminOrder['order_content'] as $item) {

                $product = $itemPosition->getProduct($item['id']);

                $product['category_url'] = $product['category_url'] ? $product['category_url'] : 'catalog';
                $productUrl = $product['category_url'].'/'.$product['url'];
                $itemCount = $item['count'];
                if (!empty($product)) {
                    $fulPrice = $item['fulPrice']; // полная стоимость без скидки
                    $product['price'] = $item['price'];
                    // если выбран формат без копеек, то округляем стоимость до ворматирования.
                    if(in_array(MG::getSetting('priceFormat'), array('1234','1 234','1,234'))) {
                        $product['price'] = round($item['price']);
                    }

                    $tmp = array(
                      'id' => $product['id'],
                      'name' => $item['name'],
                      'url' => $productUrl,
                      'code' => $item['code'],
                      'price' => $product['price'],
                      'count' => $itemCount,
                      'property' => $item['property'],
                      'discount' => $fulPrice?(round(100 - ($product['price'] * 100) / $fulPrice, 2)):0,
                      'fulPrice' => $fulPrice,
                      'weight' => $product['weight'],
                      'currency_iso' => $currencyShopIso,
                      'unit' => !empty($product['unit'])?$product['unit']:'шт.',
                      '1c_id' => !empty($product['1c_id'])?$product['1c_id']:'',
                      'electro' => !empty($product['link_electro']) ? 1 : 0,
                    );

                    if ($item['variant_id']) {
                        $variants = $itemPosition->getVariants($product['id']);
                        $variant = $variants[$item['variant_id']];
                        $tmp['variant_id'] = $item['variant_id'];
                        $tmp['weight'] = $variant['weight'];
                        $tmp['1c_id'] = $tmp['1c_id'] . (!empty($variant['1c_id'])?'#'.$variant['1c_id']:'');
                    }

                    $productPositions[] = $tmp;

                    $this->summ += $product['price'] * $itemCount;
                    $this->summTotal += $product['price'] * $itemCount;

                    // По ходу формируем массив запросов на обновление количества товаров.
                    if ($item['variant_id'] == 0) {
                        $product['count'] = ($product['count'] - $itemCount) >= 0 ? $product['count'] - $itemCount : 0;
                        if (!empty($product_update[$product['id']])) {
                            $product_update[$product['id']] = ($product_update[$product['id']] - $itemCount) >= 0 ? $product_update[$product['id']] - $itemCount : 0;
                        } else {
                            $product_update[$product['id']] = $product['count'];
                        }
                    } else {

                        $count = DB::query('
              SELECT count
              FROM `'.PREFIX.'product_variant`
              WHERE id = '.DB::quote($item['variant_id']));
                        $count = DB::fetchAssoc($count);

                        $product['count'] = ($count['count'] - $itemCount) >= 0 ? $count['count'] - $itemCount : 0;
                        if (!empty($variant_update[$item['variant_id']])) {
                            $variant_update[$item['variant_id']] = ($variant_update[$item['variant_id']] - $itemCount) >= 0 ? $variant_update[$item['variant_id']] - $itemCount : 0;
                        } else {
                            $variant_update[$item['variant_id']] = $product['count'];
                        }
                        $variants = $itemPosition->getVariants($product['id']);
                        $firstVariant = reset($variants);
                        if ($firstVariant['id'] == $item['variant_id']) {
                            // если приобретен вариант товара, то выясним является ли он первым в наборе, если да то обновим информацию в mg_product
                            if (!empty($product_update[$product['id']])) {
                                $product_update[$product['id']] = ($product_update[$product['id']] - $itemCount) >= 0 ? $product_update[$product['id']] - $itemCount : 0;
                            } else {
                                $product_update[$product['id']] = $product['count'];
                            }
                        }
                    }
                    $updateCountProd[] = "UPDATE `".PREFIX."product` SET `count_buy`= `count_buy` + 1 WHERE `id`=".DB::quote($product['id']);
                }
            }
        } elseif (!empty($_SESSION['cart'])) {
            $fulPrice = 0;
            foreach ($_SESSION['cart'] as $item) {
                $product = $itemPosition->getProduct($item['id']);
                $wholesalePrice = MG::setWholePrice($product['price_course'], $item['id'], $item['count'], $item['variantId'], $product['currency_iso']);
               // $product['price_course'] = $product['price'] = min([$product['price_course'], $wholesalePrice]);
                $product['price_course'] = $product['price'] = $wholesalePrice;

                // Дописываем к массиву продуктов данные о выбранных характеристиках из корзины покупок, чтобы приплюсовать к сумме заказа.
                if ($item['id'] == $product['id']) {
                    $product['property_html'] = $item['propertyReal'];
                }

                $variant = null;
                $discount = null;
                $promocode = null;
                if (!empty($item['variantId']) && $item['id'] == $product['id']) {
                    $variants = $itemPosition->getVariants($product['id']);
                    $variant = $variants[$item['variantId']];
                    $tmp = $variant['price_course'];
                    $tmp2 = MG::setWholePrice($variant['price_course'], $item['id'], $item['count'], $item['variantId'], $product['currency_iso']);
                    if ($tmp != $tmp2) {
                        $tmp2 = MG::convertCustomPrice($tmp2, $product['iso'], 'set');
                    }
                    //$variant['price_course'] = min([$variant['price_course'], $tmp2]);
                    $variant['price_course'] = $tmp2;

                    $variant['price'] = $variant['price_course'];
                    $product['price'] = $variant['price_course'];
                    $fulPrice = $product['price'];
                    $priceWithCoupon = $cart->applyCoupon(isset($_SESSION['couponCode'])?$_SESSION['couponCode']:'', $product['price'], $product);
                    $product['price'] = $cart->customPrice(array(
                      'product' => $product,
                      'priceWithCoupon' => $priceWithCoupon,
                    ));

                    if(in_array(MG::getSetting('priceFormat'), array('1234','1 234','1,234'))) {
                      $product['price'] = round($product['price']);
                    } else {
                      $product['price'] = round($product['price'], 2);
                    }

                    $product['variant_id'] = $item['variantId'];
                    $product['variantId'] = $item['variantId'];
                    $product['code'] = $variant['code'];
                    $product['count'] = $variant['count'];
                    $product['weight'] = $variant['weight'];
                    $product['title'] .= " ".$variant['title_variant'];
                    $product['1c_id'] = !empty($variant['1c_id'])?$product['1c_id'].'#'.$variant['1c_id']:$product['1c_id'];
                    //По ходу формируем массив запросов на обновление количества товаров
                    $resCount = ($variant['count'] - $item['count']) >= 0 ? $variant['count'] - $item['count'] : 0;
                    if (!empty($variant_update[$item['variantId']])) {
                        $variant_update[$item['variantId']] = ($variant_update[$item['variantId']] - $item['count']) >= 0 ? $variant_update[$item['variantId']] - $item['count'] : 0;
                    } else {
                        $variant_update[$item['variantId']] = $resCount;
                    }
                }

                $product['category_url'] = $product['category_url'] ? $product['category_url'] : 'catalog';
                $productUrl = $product['category_url'].'/'.$product['url'];

                // Если куки не актуальны исключает попадание несуществующего продукта в заказ
                if (!empty($product)) {
                    if (!$variant) {
                        $product['price'] = $product['price_course'];
                        // если выбран формат без копеек, то округляем стоимость до ворматирования.
                        if(in_array(MG::getSetting('priceFormat'), array('1234','1 234','1,234'))) {
                            $product['price'] = round($product['price_course']);
                        }
                        $fulPrice = $product['price'];
                    }
                    if (floatval($product['old_price']) > floatval($fulPrice)) {
                      $product['old_price'] = SmalCart::plusPropertyMargin($product['old_price'], $product['property_html'], $currencyRate[$product['currency_iso']]);
                    }
                    $product['price'] = SmalCart::plusPropertyMargin($fulPrice, $product['property_html'], $currencyRate[$product['currency_iso']]);
                    $fulPrice = $product['price'];
                    $tempPrice = $product['price'];
                    $priceWithCoupon = MG::roundPriceBySettings($cart->applyCoupon(isset($_SESSION['couponCode'])?$_SESSION['couponCode']:'', $product['price'], $product));
                    $product['price'] = MG::roundPriceBySettings($cart->customPrice(array(
                      'product' => $product,
                      'priceWithCoupon' => $priceWithCoupon,
                    )));

                    $discount = 0;
                    if (!empty($tempPrice)) {
                        $discount = 100 - ($product['price'] * 100) / $tempPrice;
                    }
                    $tempPriceCeil = (string) ($product['price']*100);
                    $product['price'] = ceil($tempPriceCeil)/100;

                    if(in_array(MG::getSetting('priceFormat'), array('1234','1 234','1,234'))) {
                      $product['price'] = round($product['price']);
                    }
                    $productPositions[] = array(
                      'id' => $product['id'],
                      'variant_id' => isset($product['variant_id'])?$product['variant_id']:null,
                      'name' => $product['title'],
                      'url' => $productUrl,
                      'code' => $product['code'],
                      'price' => $product['price'],
                      'count' => $item['count'],
                      'property' => $item['property'],
                      'discount' => round($discount, 2),
                      'fulPrice' => $fulPrice,
                      'weight' => $product['weight'],
                      'currency_iso' => $currencyShopIso,
                      'unit' => !empty($product['unit'])?$product['unit']:'шт.',
                      '1c_id' => !empty($product['1c_id'])?$product['1c_id']:'',
                      'electro' => !empty($product['link_electro']) ? 1 : 0,
                    );

                    $this->summ += $product['price'] * $item['count'];
                    
                    // Костыль от Жени, для фикса проблемы с копейками при примении промокодов.
                    $this->summTotal += $priceWithCoupon * $item['count'] * ($_SESSION['price_rate'] + 1);

                    if (empty($resCount)) {
                        $resCount = ($product['count'] - $item['count']) >= 0 ? $product['count'] - $item['count'] : 0;
                    }

                    //По ходу формируем массив запросов на обновление количества товаров
                    if (!$variant) {
                        if (!empty($product_update[$product['id']])) {
                            $product_update[$product['id']] = ($product_update[$product['id']] - $item['count']) >= 0 ? $product_update[$product['id']] - $item['count'] : 0;
                        } else {
                            $product_update[$product['id']] = $resCount;
                        }
                    } else {
                        $firstVariant = reset($variants);
                        if($firstVariant['id']==$item['variantId']) {
                            // если приобретен вариант товара, то выясним является ли он первым в наборе, если да то обновим информацию в mg_product
                            if (!empty($product_update[$product['id']])) {
                                $product_update[$product['id']] = ($product_update[$product['id']] - $item['count']) >= 0 ? $product_update[$product['id']] - $item['count'] : 0;
                            } else {
                                $product_update[$product['id']] = $resCount;
                            }
                        }
                    };
                    $updateCountProd[] = "UPDATE `".PREFIX."product` SET `count_buy`= `count_buy` + 1 WHERE `id`=".DB::quote($product['id']);
                    $resCount = null;
                }
            }
        }

        // Костыль от Жени, для фикса проблемы с копейками при примении промокодов.
        // Если из-за округлений получаяется плавающая копейка, то необходимо выполнить корректировку одного товара
        if($this->summ != $this->summTotal){
            $correct = $this->summTotal - $this->summ;
            $this->summ += $correct;
            foreach ($productPositions as $ckey => $citem) {
                $cprice = $citem['price'];
                $ccount = $citem['count'];
                $csumm = MG::roundPriceBySettings($cprice * $ccount);
                if($csumm > abs($correct) && $correct != 0){
                    $productPositions[$ckey]['price'] = MG::roundPriceBySettings($cprice + ($correct / $ccount));
                    break;
                }
            }
        }

        // Сериализует данные в строку для записи в бд.
        $orderContent = addslashes(serialize($productPositions));

        // Сериализует данные в строку для записи в бд информации об юридическом лице.
        $yurInfo = '';
        if (!empty($adminOrder['yur_info'])) {
            $yurInfo = addslashes(serialize($adminOrder['yur_info']));
        }
        if (!empty($_POST['yur_info_inn'])) {
            $yurInfo = addslashes(serialize($_POST['yur_info']));
        }

        $shop_yur_id = '';
        if (!empty($adminOrder['shop_yur_id'])) {
            $shop_yur_id = intVal(htmlspecialchars($adminOrder['shop_yur_id']));
        }
        if (!empty($_POST['shop_yur_id']) && !empty($_POST['yur_info_inn'])) {
            $shop_yur_id = intVal(htmlspecialchars($_POST['shop_yur_id']));
        } else {
            if(!empty($_POST['yur_info_inn']) && $shop_yur_id === ''){
                $addReqs = MG::getSetting('addRequisites',true);
                if(!empty($addReqs)){
                    foreach($addReqs as $rqKey => $rqVal){
                        if($addReqs[$rqKey]['choosen'] === 'true'){
                            $shop_yur_id = $rqKey;
                        }
                    }
                }
            }
        }

        // Создает новую модель корзины, чтобы узнать сумму заказа.
        $cart = new Models_Cart();

        // Генерируем уникальный хэш для подтверждения заказа.
        $hash = $this->_getHash($this->email);


        //Достаем настройки заказов, чтобы установить статус для нового заказа.
        $propertyOrder = MG::getSetting('propertyOrder', 1);
        if(!empty($shop_yur_id)){
            $addReqs = MG::getSetting('addRequisites',true);
            if(!empty($addReqs)){
                foreach($addReqs[$shop_yur_id] as $key => $shopReq){
                    $propertyOrder[$key] = $shopReq;			
                }	
            }
        } 
        //Если установлен статус для новых заказов по умолчанию "ожидает оплаты",
        //для способов оплаты "наличными" или "наложенным" меняем на "в доставке"
        $order_status_id = !empty($propertyOrder['order_status'])?$propertyOrder['order_status']:0;
        if (!empty($this->payment) && in_array($this->payment, array(3, 4)) && $order_status_id == 1) {
            $order_status_id = 3;
        }

        $summ = (float)number_format($this->summ, 2, '.', '');
        $deliv = MG::numberDeFormat(MG::numberFormat($this->delivery_cost));
        $shopCurr = MG::getOption('currencyShopIso');
        $newCurr = MG::getSetting('currencyShopIso');

        if ($newCurr == $shopCurr) {
            $summ_shop_curr = $summ;
            $delivery_shop_curr = $deliv;
        } else {
            $rates = MG::getSetting('currencyRate');
            $summ_shop_curr = (float)round($summ/$rates[$shopCurr],2);
            $delivery_shop_curr = (float)round($deliv/$rates[$shopCurr],2);
        }

        // для получения права на заказ
        if($_SESSION['user']->role != 1 && User::access('admin_zone') == 1) {
            $owner = $_SESSION['user']->id;
        } else {
            $owner = 0;
        }
        //Повтор ответственного
        if (MG::getSetting('orderOwners') == 'true' && MG::getSetting('ownerRemember') == 'true' && empty($owner)) {
            $ownerDays = MG::getSetting('ownerRememberDays');
            $ownerDays = !empty($ownerDays) ? $ownerDays : '14';
            //  Ищем по телефону или по эмейлу и проверяем, прошли ли дни последнего заказа
            $partWhere = '';
            if (MG::getSetting('ownerRememberPhone') == 'true') {
                $partWhere = "`phone` = ".DB::quote($this->phone). " AND ";
            }
            if (MG::getSetting('ownerRememberEmail') == 'true') {
                $partWhere = "`user_email` = ".DB::quote($this->email). " AND ";
            }
            if (MG::getSetting('ownerRememberEmail') == 'true' && MG::getSetting('ownerRememberPhone') == 'true') {
                $partWhere = "(`user_email` = ".DB::quote($this->email)." OR `phone` = ".DB::quote($this->phone).") AND ";
            }
            $sql = "SELECT `id`, `add_date`, `owner` FROM `".PREFIX."order` WHERE ".$partWhere."NOW() < adddate(`add_date`,+".DB::quoteInt($ownerDays,true).") ORDER BY id DESC LIMIT 1";
            $result = DB::fetchAssoc(DB::query($sql));
            if (!empty($result['owner'])) {
                $owner = $result['owner'];
                MG::setOption('ownerRotationCurrent', $owner);
            }
        }

        //Ротация ответственных
        if (MG::getSetting('orderOwners') == 'true' && MG::getSetting('ownerRotation') == 'true' && empty($owner)) {
            $currentOwner = MG::getSetting('ownerRotationCurrent');
            $ownerRotationList = MG::getSetting('ownerList');

            if (!empty($ownerRotationList)) { //Если список пуст, то уходим
                $ownerRotationList = explode(',', $ownerRotationList);
                if (empty($currentOwner)) { //Если в ротации ещё никого не было, берём первого
                    $ownerId = $ownerRotationList[0];
                    MG::setOption('ownerRotationCurrent', $ownerId);
                    $owner = $ownerId;
                } else { //Начинаем искать следующего ответственного
                    $flipTmp = array_flip($ownerRotationList);
                    $indexTmp = isset($flipTmp[$currentOwner])?$flipTmp[$currentOwner]:null;
                    if (empty($indexTmp) && $indexTmp != '0') { //Не можем найти текущего ответственого в списке? берём первого
                        $ownerId = $ownerRotationList[0];
                        MG::setOption('ownerRotationCurrent', $ownerId);
                        $owner = $ownerId;
                    } else {
                        // Ищем следующего ответственного либо берём первого
                        if (isset($ownerRotationList[$indexTmp + 1])) {
                            $ownerId = $ownerRotationList[$indexTmp + 1];
                            MG::setOption('ownerRotationCurrent', $ownerId);
                            $owner = $ownerId;
                        } else {
                            $ownerId = $ownerRotationList[0];
                            MG::setOption('ownerRotationCurrent', $ownerId);
                            $owner = $ownerId;
                        }
                    }
                }
            }
        }
        // Формируем массив параметров для SQL запроса.
        $storage = '';
        $storageAddress = '';
        // подготовка данных о используемом складе
        $storages = MG::getSetting('storages',true);
        foreach ($storages as $value) {
            if($this->storage == $value['id']){
                $storage =  $value;
                $storageAddress = $value['adress'];
            }
        }

        // Если адресс доставки не указан и выбран склад, то указываем адресс склада как адресс доставки
        if (!$this->address && $storageAddress) {
            $this->address = $storageAddress;
        }

        $array = array(
          'owner' => $owner,
          'user_email' => $this->email,
          'contact_email'  => $this->contact_email,
          'summ' => number_format($this->summ, 2, '.', ''),
          'summ_shop_curr' => number_format($summ_shop_curr, 2, '.', ''),
          'currency_iso' => $newCurr,
          'order_content' => $orderContent,
          'phone' => $this->phone,
          'delivery_id' => $this->delivery,
          'delivery_cost' => MG::numberDeFormat(MG::numberFormat($this->delivery_cost)),
          'delivery_shop_curr' => $delivery_shop_curr,
          'payment_id' => $this->payment,
          'paided' => '0',
          'status_id' => (int)$order_status_id,
          'confirmation' => $hash,
          'yur_info' => $yurInfo,
          'shop_yur_id' => $shop_yur_id,
          'date_delivery' => $this->dateDelivery,
          'delivery_interval' => isset($this->interval)?$this->interval:null,
          'user_comment' => $this->info,
          'ip'=> $_SERVER['REMOTE_ADDR'],

          'storage' => $this->storage,
        );

        if (!empty($this->address_parts)) {
            $array['address_parts'] = $this->address_parts;
            $tmp = array_filter(unserialize(stripcslashes($array['address_parts'])));
            $array['address'] = implode(', ', $tmp);
        } else{
            $array['address'] = $this->address;
        }

        if (!empty($this->name_parts)) {
            $array['name_parts'] = $this->name_parts;
            $tmp = array_filter(unserialize(stripcslashes($array['name_parts'])));
            $array['name_buyer'] = trim(implode(' ', $tmp));
        } else {
            $array['name_buyer'] = $this->fio;
        }

        // Если заказ оформляется через админку.
        if ($adminOrder) {
            $array['comment'] = !empty($adminOrder['comment'])?$adminOrder['comment']:'';
            $array['status_id'] = $adminOrder['status_id'];
            $array['date_delivery'] = $adminOrder['date_delivery'];
            if (!empty($adminOrder['status_id']) && ($adminOrder['status_id'] == 2 || $adminOrder['status_id'] == 5)) {
                $array['pay_date'] = date('Y-m-d H:i:s');
            }
        }

        // получаем UTM метки из Cookies
        $utm = MG::getUTM();

        $array['utm_source'] = $utm['utm_source'];
        $array['utm_medium'] = $utm['utm_medium'];
        $array['utm_campaign'] = $utm['utm_campaign'];
        $array['utm_term'] = $utm['utm_term'];
        $array['utm_content'] = $utm['utm_content'];

        // Стираем cookies
        MG::clearUTM();

        // Отдает на обработку  родительской функции buildQuery.
        DB::buildQuery("INSERT INTO `".PREFIX."order` SET add_date = now(), ", $array);           

        // Заказ номер id добавлен в базу.
        $id = DB::insertId();
        $_SESSION['usedCouponCode'] = isset($_SESSION['couponCode'])?$_SESSION['couponCode']:'';

        // для файлов
        $optionalFields = Models_OpFieldsOrder::getFields();
        foreach ($optionalFields as $item) {
            if ($item['type'] == 'file' && !empty($_POST['opf_'.$item['id']])) {
                if (is_file(SITE_DIR.'uploads'.DS.'prodtmpimg'.DS.'order'.DS.$_POST['opf_'.$item['id']])) {
                    @mkdir(SITE_DIR.'uploads'.DS.'order');
                    @mkdir(SITE_DIR.'uploads'.DS.'order'.DS.$id);
                    copy(SITE_DIR.'uploads'.DS.'prodtmpimg'.DS.'order'.DS.$_POST['opf_'.$item['id']], SITE_DIR.'uploads'.DS.'order'.DS.$id.DS.$_POST['opf_'.$item['id']]);
                    $this->optFields[$item['id']] = $_POST['opf_'.$item['id']];
                }
            }
        }
        // заполняем дополниетльные поля для заказа
        $optFieldsM = new Models_OpFieldsOrder($id);
        $optFieldsM->fill($this->optFields);
        $optFieldsM->save();


        $orderNumber = $this->getOrderNumber($id);
        $hashStatus = '';
        $linkToStatus = '';
        if (MG::getSetting('autoRegister') == "false" && !USER::isAuth()) {
            $hashStatus = md5($id.$this->email.rand(0, 9999));
            $linkToStatus = '<a href="'.SITE.'/order?hash='.$hashStatus.'" target="blank">'.SITE.'/order?hash='.$hashStatus.'</a>';
        }

        $payHash = self::orderIdToPayHash($id);

        DB::query("UPDATE `".PREFIX."order` SET `number`= ".DB::quote($orderNumber).", `hash`=".DB::quote($hashStatus).", `pay_hash`=".DB::quote($payHash)." WHERE `id`=".DB::quote($id)."");

        // Ссылка для подтверждения заказа
        $link = 'ссылке <a href="'.SITE.'/order?sec='.$hash.'&id='.$id.'" target="blank">'.SITE.'/order?sec='.substr($hash, 0, 6).'</a>';
        $table = "";

        // Формирование тела письма.
        if ($id) {
            // Уменьшаем количество купленных товаров
            if (!empty($updateCountProd)) {
                foreach ($updateCountProd as $sql) {
                    DB::query($sql);
                }
            }
            if (!empty($product_update)
              && !MG::enabledStorage()
            ) {
                foreach ($product_update as $id_upd => $count_upd) {
                    DB::query("UPDATE `".PREFIX."product` SET `count`= ".DB::quoteFloat($count_upd)." WHERE `id`=".DB::quoteint($id_upd)." AND `count`>0");
                }
            }
            if (!empty($variant_update)
              && !MG::enabledStorage()
            ) {
                foreach ($variant_update as $id_upd => $count_upd) {
                    DB::query("UPDATE `".PREFIX."product_variant` SET `count`= ".DB::quoteFloat($count_upd)." WHERE `id`=".DB::quoteInt($id_upd)." AND `count`>0");
                }
            }

            if(MG::enabledStorage()){
              $productModel = new Models_Product();
              // OLD
              // $storageArray = MG::storageMinusProduct($this->storage, $adminOrder['order_content']);
              // NEW
              $orderContent = $adminOrder['order_content'];
              $storageArray = $productModel->orderDecreaseProductStorageCount($this->storage, $orderContent);
                $order = $this->getOrder(' id = '.DB::quoteInt(intval($id), true));
                $orderContent = unserialize(stripslashes($order[$id]['order_content']));
                foreach ($storageArray as $sKey => $sVal){
                    $sProductId = intval($sVal['id']);
                    $sVariantId = intval($sVal['variantId']);
                    if (empty($sVal['variantId']) && isset($sVal['variantId'])) {
                        $sVariantId = intval($sVal['variant_id']);
                    }
                    foreach ($orderContent as $oKey => $oVal){
                        $oProductId = intval($oVal['id']);
                        $oVariantId = intval($oVal['variantId']);
                        if (empty($oVal['variantId']) && isset($oVal['variant_id'])) {
                            $oVariantId = intval($oVal['variant_id']);
                        }

                        $test = [
                            $sProductId,
                            $oProductId,
                            $sVariantId,
                            $oVariantId,
                        ];

                    if($oProductId === $sProductId && $oVariantId === $sVariantId){
                        if(isset($sVal['storage_id']) ){
                        $orderContent[$oKey]['storage_id'] = array_filter($sVal['storage_id']);
                        }
                        //Конструкция для присвоения варианта товару, который куплен из мини карточки(если вариатны есть)
                        if(isset($sVal['variantIdNew']) && empty($oVal['variant_id'])){
                        $orderContent[$oKey]['variant_id'] = $sVal['variantIdNew'];
                        }
                        break;
                    }
                    }
                }
                $orderContent = addslashes(serialize($orderContent));
                //Заносим в order_content данные, с каких складов списали товар
                DB::query('UPDATE `'.PREFIX.'order` SET `order_content` = '.db::quote($orderContent).' WHERE `id` = '.db::quote($id));
            }
            //db::query("U");

            // Если заказ создался, то уменьшаем количество товаров на складе.
            $delivery = Models_Order::getDeliveryMethod(false, $this->delivery);
            $sitename = MG::getSetting('sitename');
            $currency = MG::getSetting('currency');
            $paymentArray = $this->getPaymentMethod($this->payment, false);
            $subj = 'Оформлен заказ №'.($orderNumber != "" ? $orderNumber : $id).' на сайте '.$sitename;
            $orderWeight = 0;

            foreach ($productPositions as &$item) {
                $orderWeight += $item['count']*$item['weight'];
                $item['discountVal'] = round($item['fulPrice'], 2)-round($item['price'], 2);
                $item['discountPercent'] = round((1-round($item['price'], 2)/round($item['fulPrice'], 2))*100, 2);
                foreach ($item as &$v) {
                    $v = rawurldecode($v);
                }
            }

            $OFM = array();
            $opFieldsM = new Models_OpFieldsOrder($id);
            $OFM = $opFieldsM->getHumanView('all', true);
            $OFM = $opFieldsM->fixFieldsForMail($OFM);
            $phones = explode(', ', MG::getSetting('shopPhone'));

            // Фикс квадратных скобочек в параметрах товара в письме
            foreach ($productPositions as &$productPosition) {
                $rawProperty = $productPosition['property'];
                $decodedProperty = htmlspecialchars_decode(str_replace('&amp;', '&', $rawProperty));
                $property = preg_replace('/[prop attr[=]?[^\]]*\]/', '', $decodedProperty);
                $productPosition['property'] = htmlspecialchars($property);
            }

            $approvePayment = $this->isOrderPaymentApproved($id);

            $showPaymentForm = true;
            if (method_exists('MG', 'isNewPayment') && MG::isNewPayment()) {
                $paymentForm = Models_Payment::checkPaymentForm($this->payment);
                if (!$paymentForm) {
                    $showPaymentForm = false;
                }
            }

            $paramToMail = array(
              'id' => $id,
              'orderNumber' => $orderNumber,
              'siteName' => MG::getSetting('sitename'),
              'delivery' => $delivery['description'],
              'delivery_interval' => isset($this->interval)?$this->interval:null,
              'currency' => MG::getSetting('currency'),
              'fio' => $this->fio,
              'email' => $this->email,
              'phone' => $this->phone,
              'address' => $this->address,
              'payment' => $paymentArray['name'],
              'deliveryId' => $this->delivery,
              'paymentId' => $this->payment,
              'adminOrder' => $adminOrder,
              'result' => $this->summ,
              'deliveryCost' => $this->delivery_cost,
              'date_delivery' => $this->dateDelivery,
              'total' => $this->delivery_cost + $this->summ,
              'confirmLink' => $link,
              'ip' => $this->ip,
              'lastvisit' => isset($_SESSION['lastvisit'])?$_SESSION['lastvisit']:'',
              'firstvisit' => isset($_SESSION['firstvisit'])?$_SESSION['firstvisit']:'',
              'supportEmail' => MG::getSetting('noReplyEmail'),
              'shopName' => MG::getSetting('shopName'),
              'shopPhone' => $phones[0],
              'formatedDate' => date('Y-m-d H:i:s'),
              'productPositions' => $productPositions,
              'couponCode' => $_SESSION['usedCouponCode'],
              'toKnowStatus' => $linkToStatus,
              'userComment' => $this->info,
              'yur_info' => unserialize(stripcslashes($yurInfo)),
              'custom_fields' => $OFM,
              'orderWeight' => $orderWeight,
              'payHash' => $payHash,
              'storage' =>isset($this->storage)?$storage:'',
              'approve_payment' => $approvePayment,
              'showPaymentForm' => $showPaymentForm,
            );
            if (!empty($this->contact_email)){
                $paramToMail['email'] = $this->contact_email;
            }
            if (!empty($this->address_parts)) {
                $tmp = array_filter(unserialize(stripcslashes($this->address_parts)));
                foreach ($tmp as $ke => $va) {
                    $tmp[$ke] = htmlspecialchars_decode($va);
                }
                $paramToMail['address'] = implode(', ', $tmp);
            }

            if (!empty($this->name_parts)) {
                $tmp = array_filter(unserialize(stripcslashes($this->name_parts)));
                foreach ($tmp as $ke => $va) {
                    $tmp[$ke] = htmlspecialchars_decode($va);
                }
                $paramToMail['fio'] = trim(implode(' ', $tmp));
            }

            $paramToMail['propertyOrder'] = $propertyOrder;
            $emailToUser = MG::layoutManager('email_order', $paramToMail);

            $paramToMail['adminMail'] = true;
            $emailToAdmin = MG::layoutManager('email_order_admin', $paramToMail);

            $mails = explode(',', MG::getSetting('adminEmail'));

            $fromEmail = $this->fio;
            if (strlen($fromEmail) < 2) {
                $fromEmail = $this->email;
            }
            if (strlen($fromEmail) < 2) {
                $fromEmail = MG::getSetting('shopName');
            }

            // Отправка заявки админу
            $adminSubject = 'Оформлен заказ №'.$orderNumber.'. ';
            if ($paramToMail['fio'] || $paramToMail['email']) {
                $adminSubject .= ($paramToMail['fio'] ? $paramToMail['fio'].' ' : '').
                    ($paramToMail['email'] ? '('.$paramToMail['email'].')' : '').'. ';
            }
            $adminSubject .= 'На сайте '.$sitename;
            foreach ($mails as $mail) {
                if (MG::checkEmail($mail)) {
                    Mailer::addHeaders(array("Reply-to" => MG::getSetting('noReplyEmail')));
                    Mailer::sendMimeMail(array(
                      'nameFrom' => MG::getSetting('shopName'),
                      'emailFrom' => MG::getSetting('noReplyEmail'),
                      'nameTo' => $sitename,
                      'emailTo' => $mail,
                      'subject' => $adminSubject,
                      'body' => $emailToAdmin,
                      'html' => true
                    ));
                }
            }
            // Отправка заявки пользователю.
            Mailer::sendMimeMail(array(
              'nameFrom' => MG::getSetting('shopName'),
              'emailFrom' => MG::getSetting('noReplyEmail'),
              'nameTo' => $this->fio,
              'emailTo' => $this->contact_email,
              'subject' => $subj,
              'body' => $emailToUser,
              'html' => true
            ));
            $pass = $this->passNewUser;
            $fPass = new Models_Forgotpass;
            $hash = $fPass->getHash($this->email);
            $fPass->sendHashToDB($this->email, $hash);
            $userId = DB::fetchAssoc(DB::query("SELECT id FROM `".PREFIX."user` WHERE `login_email` = ".DB::quote($this->email)));
            $link = '<a href="'.SITE.'/registration?sec='.$hash.'&id='.$userId['id'].'" target="blank">'.SITE.'/registration?sec='.$hash.'&id='.$userId['id'].'</a>';
            if ($pass) {
                $emailToUser = MG::layoutManager('email_order_new_user', array('fio' => $this->fio, 'email' => $this->contact_email, 'pass' => $pass, 'link'=>$link));
                // Отправка данных для входа новому пользователю.
                if(MG::getSetting('confirmRegistrationEmail') === 'true' || MG::getSetting('confirmRegistration') !== 'true'){
                    Mailer::sendMimeMail(array(
                        'nameFrom' => MG::getSetting('shopName'),
                        'emailFrom' => MG::getSetting('noReplyEmail'),
                        'nameTo' => $this->fio,
                        'emailTo' => $this->contact_email,
                        'subject' => 'Регистрация на '.$sitename,
                        'body' => $emailToUser,
                        'html' => true
                      ));
                }
                
                if(MG::getSetting('confirmRegistrationPhone') === 'true' && MG::getSetting('confirmRegistrationEmail') === 'false'){
                    $result = array( 'password' => $pass, 'phone' => $paramToMail['phone'], 'sitename' => SITE);
                    $args = func_get_args();
                    $result = MG::createHook('send_sms_login_pass', $result, $args);
                }
                
            }

      // Если заказ успешно записан, то очищает корзину.
      if (!$adminOrder) {
        $cart->clearCart();
      }
    }

        LoggerAction::logAction('Order',__FUNCTION__, $id);

        $result =array('id'=>$id, 'orderNumber' => $orderNumber);
        // Возвращаем номер созданного заказа.
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Отправляет сообщение о смене статуса заказа его владельцу.
     * <code>
     * $model = new Models_Order;
     * $model->sendStatusToEmail(5, 3, 'Ваш заказ передан в службу доставки');
     * </code>
     * @param int $id номер заказа.
     * @param int $statusId новый статус.
     * @param string $text текст письма.
     */
    public function sendStatusToEmail($id, $statusId, $text = '') {
        $order = $this->getOrder('id = '.DB::quote(intval($id)));
        $lang = MG::get('lang');
        $statusArray = self::$status;
        if (class_exists('statusOrder')) {
            $dbQuery = DB::query('SELECT `id_status`, `status` FROM `'.PREFIX.'mg-status-order` ');
            while ($dbRes = DB::fetchArray($dbQuery)) {
                self::$statusUser[$dbRes['id_status']] = $dbRes['status'];
            }
        }
        $statusName = !empty(self::$statusUser[$statusId]) ? self::$statusUser[$statusId] : $lang[$statusArray[$statusId]];
        $statusOldName = !empty(self::$statusUser[$order[$id]['status_id']]) ? self::$statusUser[$order[$id]['status_id']] : $lang[$statusArray[$order[$id]['status_id']]];

        $paramToMail = array(
          'orderInfo' => $order[$id],
          'statusId' => $statusId,
          'statusName' => $statusName,
          'statusOldName' => $statusOldName,
          'comment' => $text
        );
        if ($statusName !== $statusOldName) {

            $emailToUser = MG::layoutManager('email_order_change_status', $paramToMail);

            Mailer::addHeaders(array("Reply-to" => MG::getSetting('noReplyEmail')));
            Mailer::sendMimeMail(array(
              'nameFrom' => MG::getSetting('shopName'),
              'emailFrom' => MG::getSetting('noReplyEmail'),
              'nameTo' => $order[$id]['contact_email'],
              'emailTo' => $order[$id]['contact_email'],
              'subject' => "Заказ №".$order[$id]['number']." ".$statusName,
              'body' => $emailToUser,
              'html' => true
            ));
            $result = $paramToMail;
        } else {
            $result = false;
        }
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Изменяет данные о заказе
     * <code>
     * $array = array(
     *  'address' => 'addr', // адрес доставки
     *  'date_delivery' => '08.03.2018', // дата доставки
     *  'comment' => 'comment', // комментарий менеджера
     *  'delivery_cost' => 700, // стоимость доставки
     *  'delivery_id' => 1, // ID доставки
     *  'id' => 3, // ID заказа
     *  'number' => 'M-0105268947551', // код заказа
     *  'name_buyer' => 'Администратор', // имя покупателя
     *  'payment_id' => 1, // ID оплаты
     *  'phone' => '+7 (111) 111-11-11', // телефон пользователя
     *  'status_id' => 0, // ID статуса заказа
     *  'summ' => 100, // сумма заказа без доставки
     *  'currency_iso' => 'RUR', // код валюты заказа
     *  'user_email' => 'admin@admin.ru', // почта авторизации пользователя
     *  'contact_email' => 'admin@admin.ru', // контактная почта пользователя
     *  'order_content' => 'string', // сериализованный массив состава заказа
     *  'storage' => 'default', // склад для заказа
     *  'summ_shop_curr' => 100, // сумма заказа без доставки в основной валюте магазина
     *  'delivery_shop_curr' => 700, // стоимость доставки в основной валюте магазина
     *  'yur_info' => 'string' // сериализованный массив юридических данных
     * )
     * $model = new Models_Order;
     * $model->updateOrder($array , true, 'Ваш заказ успешно обновлен');
     * </code>
     * @param array $array массив с данными о заказе.
     * @param bool $informUser информировать ли пользователя об изменении заказа.
     * @param string $text комментарий к заказу.
     * @return bool
     */
    public function updateOrder($array, $informUser = false, $text = '') {
        $id = $array['id'];
        unset($array['id']);

        if (!empty($array['status_id'])) {
            $this->refreshCountProducts($id, $array['status_id']);
        }

        if (isset($array['order_content'])) {
            $array['order_content'] = $this->join1cidProducts($array['order_content']);
        }

        if (!empty($array['status_id']) && $informUser == 'true') {
            $this->sendStatusToEmail($id, $array['status_id'], $text);
        }


	// логирование
    $array['id'] = $id;
    LoggerAction::logAction('Order',__FUNCTION__, $array);


    $result = false;
    if (!empty($id)) {
      if (DB::query('
        UPDATE `'.PREFIX.'order`
        SET '.DB::buildPartQuery($array).'
        WHERE id = '.DB::quote($id))) {
                $result = true;
            }
            if(!empty($array['status_id']) && $array['status_id'] == 2) {
                DB::query('UPDATE '.PREFIX.'order SET pay_date = NOW() WHERE id = '.DB::quote($id));
            }
        }
        $array['id'] = $id;
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Добавляет к товарам в заказе идентификаторы 1С.
     *
     * @param string $order_content
     * @return string
     */
    public function join1cidProducts($order_content) {
        //Вскрываем содержимое заказа
        $products = unserialize(stripslashes($order_content));

        foreach ($products as $key => $product) {
            //Ищем 1с_id товара
            $sql = "SELECT `1c_id` FROM `".PREFIX."product` WHERE `id` = ".DB::quote($product['id']);
            $id_1c = DB::fetchAssoc(DB::query($sql))['1c_id'];

            //Если есть ещё и вариант, то добавляем его 1с_id к товару
            if (!empty($product['variant_id'])) {
                $sql = "SELECT `1c_id` FROM `".PREFIX."product_variant` WHERE `id` = ".DB::quote($product['variant_id']);
                $variant_id_1c = DB::fetchAssoc(DB::query($sql))['1c_id'];
                //Проверяем на пустоту и то и другое
                if (!empty($id_1c) && !empty($variant_id_1c)) {
                    $id_1c = $id_1c.'#'.$variant_id_1c;
                }
            }

            $products[$key]['1c_id'] = $id_1c;
        }

        //Запаковываем содержимое заказа обратно
        $orderContent = addslashes(serialize($products));
        return $orderContent;
    }

    /**
     * Пересчитывает количество остатков продуктов при отмене заказа.
     * <code>
     * $model = new Models_Order;
     * $model->refreshCountProducts(5, 4);
     * </code>
     * @param int $orderId id заказа.
     * @param int $status_id новый статус заказа.
     * @return bool
     */
    public function refreshCountProducts($orderId, $status_id) {
        // Если статус меняется на "Отменен", то пересчитываем остатки продуктов из заказа.
        $order = $this->getOrder(' id = '.DB::quoteInt(intval($orderId)));
        //массив со статусами заказов, удаля которые не меняется кол-во товара
        $arrayOfStatusUndeletableProduct = array('4', '5');
        // Увеличиваем колличество товаров.
        $sql = "SELECT `order_content` FROM `".PREFIX."order` WHERE `id` = ".db::quoteInt($orderId);
        $row = db::fetchAssoc(db::query($sql));
        if ($status_id == 4) {
            if (!in_array($order[$orderId]['status_id'], $arrayOfStatusUndeletableProduct)) {
                $order_content = unserialize(stripslashes($row['order_content']));
                $product = new Models_Product();
                foreach ($order_content as $item) {
                    if(!MG::enabledStorage()) {
                        $product->increaseCountProduct($item['id'], $item['code'], $item['count']);
                    } else {
                        $storage = $order[$orderId]['storage'];
                        $productId = intval($item['id']);
                        $variantId = intval($item['variant_id']);
                        $productCount = $item['count'];
                        if (empty($item['storage_id'])) {
                            $currentStorageCount = $product->getProductStorageCount($storage, $productId, $variantId);
                            if (floatval($currentStorageCount) !== floatval(-1)) {
                                $newStorageCount = $currentStorageCount + $productCount;
                                $product->updateStorageCount($productId, $storage, $newStorageCount, $variantId);
                            }
                        } else {
                            $itemStorage = $item['storage_id'];
                            foreach ($itemStorage as $currentStorageId => $countFromStorage) {
                                $currentStorageCount = $product->getProductStoragecount($currentStorageId, $productId, $variantId);
                                if (floatval($currentStorageCount) !== floatval(-1)) {
                                    $newStorageCount = $currentStorageCount + $countFromStorage;
                                    $product->updateStorageCount($productId, $currentStorageId, $newStorageCount, $variantId);
                                }
                            }
                        }
                        $product->recalculateStoragesById($productId);
                      //MG::increaseCountProductOnStorageNew($item, $order[$orderId]['storage']);
                      //MG::increaseCountProductOnStorage($item ,$order[$orderId]['storage']);
                    }
                }
            }
        } else {
            // Уменьшаем колличество товаров.
            if ($order[$orderId]['status_id'] == 4) {
                $order_content = unserialize(stripslashes($row['order_content']));
                $product = new Models_Product();
                foreach ($order_content as $item) {
                    if(!MG::enabledStorage()) {
                        $product->decreaseCountProduct($item['id'], $item['code'], $item['count']);
                    } else {
                        MG::decreaseCountProductOnStorageNew($item ,$order[$orderId]['storage']);
                    }
                }
            }
        }
        $result = true;
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

  /**
   * Удаляет заказ из базы данных.
   * <code>
   * $model = new Models_Order;
   * $model->deleteOrder(false, array(1,2,3,4,5));
   * </code>
   * @param int $id id удаляемого заказа
   * @param array|null $arrayId массив id заказов, которые требуется удалить
   * @return bool
   */

  public function deleteOrder($id, $arrayId = null) {
    $result = false;
	// логирование
     LoggerAction::logAction('Order',__FUNCTION__, $id);

    if (empty($arrayId)) {
      if (DB::query('
        DELETE
        FROM `'.PREFIX.'order`
        WHERE id = %d
      ', $id)) {
                $result = true;
                MG::rrmdir(SITE_DIR.'uploads'.DS.'order'.DS.$id);
            }
        } else {
            $where = '('.implode(',', $arrayId).')';
            if (DB::query('
        DELETE
        FROM `'.PREFIX.'order`
        WHERE id in %s
      ', $where)) {
                $result = true;
                foreach ($arrayId as $folder) {
                    MG::rrmdir(SITE_DIR.'uploads'.DS.'order'.DS.$folder);
                }
            }
        }

        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Возвращает массив заказов подцепляя данные о способе доставки.
     * <code>
     * $model = new Models_Order;
     * $orders = $model->getOrder('id IN (1,2,3,4,5)');
     * viewData($orders);
     * </code>
     * @param string $where необязательный параметр формирующий условия поиска заказа, например: id = 1
     * @return array массив заказов
     */
    public function getOrder($where = '', $limit = 0) {
        $orderArray = array();
        if ($where) {
            $where = 'WHERE '.$where;
        }
        $sql = 'SELECT  * FROM `'.PREFIX.'order`'.$where.' ORDER BY id desc';
        if ($limit) {
            $sql .= ' LIMIT '. DB::quoteInt($limit, true);
        }
        $result = DB::query($sql);

        while ($order = DB::fetchAssoc($result)) {

            $delivery = Models_Order::getDeliveryMethod(false, $order['delivery_id']);
            $order['description'] = $delivery['description'];
            $order['cost'] = $delivery['cost'];
            // декодируем параметры заказа
            $order['order_content'] = unserialize(stripslashes($order['order_content']));
            foreach ($order['order_content'] as &$item) {
                foreach ($item as $k => &$v) {
                  //Для складов
                  if ($k == 'storage_id') {
                    $storage = array();
                    foreach ($v as $storageId => $count) {
                      $storage[] = $storageId . '_' . $count;
                    }
                    $v = $storage;
                  } else {
                    $v = rawurldecode($v);
                  }
                }
            }
            $order['order_content'] = addslashes(serialize($order['order_content']));

            $orderArray[$order['id']] = $order;
        }
        return $orderArray;
    }

    /**
     * Устанавливает переданный статус заказа.
     * <code>
     * $result = Models_Order::setOrderStatus(5, 4);
     * var_dump($result);
     * </code>
     * @param int $id номер заказа.
     * @param int $statusId статус заказа.
     * @return bool результат выполнения метода.
     */
    public function setOrderStatus($id, $statusId) {
      //Логирование изменеия статуса заказа
      $array['id'] = $id;
      $array['status_id'] = $statusId;
      LoggerAction::logAction('Order',__FUNCTION__, $array);

        $res = DB::query('
      UPDATE `'.PREFIX.'order`
      SET status_id = %d
      WHERE id = %d', $statusId, $id);

        if ($res) {
            $result = true;
        }
        else{
            $result = false;
        }

        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Генерация случайного хэша.
     * <code>
     * $email = 'admin@mail.mail';
     * $hash = Models_Order::_getHash($email);
     * echo $hash;
     * </code>
     * @param string $string - строка, на основе которой готовится хэш.
     * @return string случайный хэш
     */
    public function _getHash($string) {
        $hash = htmlspecialchars(password_hash($string, PASSWORD_DEFAULT));
        return $hash;
    }

    /**
     * Получение данных о способах доставки.
     * <code>
     * $order = new Models_Order();
     * $result = $order->getDeliveryMethod();
     * viewData($result);
     * </code>
     * @param bool $returnArray возвращать несколько способов доставки
     * @param int $id способа доставки
     * @return array массив содержащий способы доставки.
     */
    public static function getDeliveryMethod($returnArray = true, $id = -1) {

        if(!empty($_POST['orderItems'])){
            $itemsCart['items'] = $_POST['orderItems'];

        }else{
            $cart = new Models_Cart();
            $itemsCart = $cart->getItemsCart();
        }

        $sumWeight = 0;

        for($i=0; $i<count($itemsCart['items']); $i++){
            $sumWeight += $itemsCart['items'][$i]['weight']*$itemsCart['items'][$i]['countInCart'];
        }

        if ($returnArray) {

            $deliveryArray = [];
            $result = DB::query('SELECT  *  FROM `'.PREFIX.'delivery` ORDER BY `sort`');
            while ($delivery = DB::fetchAssoc($result)) {
                $deliveryArray[$delivery['id']] = $delivery;
                $deliveryIds[] = $delivery['id'];
                if ($delivery['plugin'] && !PM::isHookInReg('shortcode_'.$delivery['plugin'])) {
                    $deliveryArray[$delivery['id']]['plugin'] = '';
                }
            }

            if (!empty($deliveryIds)) {
                $in = 'in('.implode(',', $deliveryIds).')';
                $deliveryCompareArray = array();
                $res = DB::query('
          SELECT  *  
          FROM `'.PREFIX.'delivery_payment_compare` 
          WHERE `delivery_id` '.$in);
                while ($row = DB::fetchAssoc($res)) {
                    $deliveryCompareArray[$row['delivery_id']][] = $row;
                }
            }

            foreach ($deliveryArray as &$item) {
                if (!isset($fields)) {
                    $fields = MG::getSetting('orderFormFields',true);
                }
                $item['address_parts'] = $item['name_parts'] = '0';
                foreach ($fields as $key => $field) {
                    if (!$item['address_parts'] && strpos($key, 'address_') === 0) {
                        if ($field['conditionType'] == 'always') {
                            $item['address_parts'] = '1';
                        } elseif(!empty($field['conditions'])) {
                            foreach ($field['conditions'] as $condition) {
                                if ($condition['type'] == 'delivery' && in_array($item['id'], $condition['value'])) {
                                    $item['address_parts'] = '1';
                                    break;
                                }
                            }
                        }
                    }
                    if (!$item['name_parts'] && strpos($key, 'fio_') === 0) {
                        if ($field['conditionType'] == 'always') {
                            $item['name_parts'] = '1';
                        } elseif(!empty($field['conditions'])) {
                            foreach ($field['conditions'] as $condition) {
                                if ($condition['type'] == 'delivery' && in_array($item['id'], $condition['value'])) {
                                    $item['name_parts'] = '1';
                                    break;
                                }
                            }
                        }
                    }
                }
                // Получаем доступные методы оплаты $delivery['paymentMethod'] для данного способа доставки.
                $jsonStr = '{';
                if (!empty($deliveryCompareArray[$item['id']])) {
                    foreach ($deliveryCompareArray[$item['id']] as $compareMethod) {
                        $jsonStr .= '"'.$compareMethod['payment_id'].'":'.$compareMethod['compare'].',';
                    }
                    $jsonStr = substr($jsonStr, 0, strlen($jsonStr) - 1);
                }
                $jsonStr .= '}';
                $item['paymentMethod'] = $jsonStr;

                if (!MG::isAdmin() && $item['weight']) {
                    $weights = json_decode($item['weight'],1);
                    foreach ($weights as $key => $value) {
                        if ($sumWeight >= $value['w']) {
                            $item['cost'] = $value['p'];
                        }
                    }
                }

                if ($item['address_parts'] == '1') {
                    $item['address_parts'] = '["index","country","region","city","street","house","flat"]';
                } else{
                    $item['address_parts'] = '';
                }

                if ($item['name_parts'] == '1') {
                    $item['name_parts'] = '["sname","name","pname"]';
                } else{
                    $item['name_parts'] = '';
                }

                $item['cost'] = MG::convertPrice($item['cost']);
            }

            return $deliveryArray;
        } elseif ($id >= 0) {
            $result = DB::query('
        SELECT `description`, `cost`, `free`, `plugin`, `weight`
        FROM `'.PREFIX.'delivery`
        WHERE id = %d', $id);
            $tmp = DB::fetchAssoc($result);

            if ($sumWeight > 0 && $tmp['weight'] && !MG::isAdmin()) {
                $weights = json_decode($tmp['weight'],1);
                foreach ($weights as $key => $value) {
                    if ($sumWeight >= $value['w']) {
                        $item['cost'] = $value['p'];
                    }
                }
            }

            $tmp['cost'] = MG::convertPrice($tmp['cost']);

            if ($tmp['plugin'] && !PM::isPluginActive($tmp['plugin'])) {
                $tmp['plugin'] = '';
            }

            return $tmp;
        }
    }

    /**
     * Проверяет, существуют ли способы доставки.
     * <code>
     * var_dump(Models_Order::DeliveryExist());
     * </code>
     * @return bool
     */
    public function DeliveryExist() {
        if (DB::numRows(DB::query('SELECT  *  FROM `'.PREFIX.'delivery` ORDER BY id'))) {
            return true;
        }
        return false;
    }

    /**
     * Расшифровка по id статуса заказа.
     * <code>
     * echo Models_Order::getOrderStatus(4);
     * </code>
     * @param int $statusId id статуса заказа.
     * @return string
     */
    public function getOrderStatus($statusId) {
        if (!isset($statusId['status_id']) && is_numeric($statusId)) {
            $statusId['status_id'] = $statusId;
        }
        if (!isset($statusId['status_id'])) {
            return '';
        }
        $msg = '';
        switch ($statusId['status_id']) {
            case 0:
                $msg = 'msg__status_not_confirmed';
                break;
            case 1:
                $msg = 'msg__status_expects_payment';
                break;
            case 2:
                $msg = 'msg__status_paid';
                break;
            case 3:
                $msg = 'msg__status_in_delivery';
                break;
            case 4:
                $msg = 'msg__status_canceled';
                break;
            case 5:
                $msg = 'msg__status_executed';
                break;
            case 6:
                $msg = 'msg__status_processing';
                break;
        }
        $res = DB::query("SELECT `id`, `text` FROM `".PREFIX."messages` WHERE `name` = '".$msg."'");
        $row = DB::fetchAssoc($res);
        MG::loadLocaleData($row['id'], LANG, 'messages', $row);
        return $row['text'];
    }

    /**
     * Расшифровка по id методов оплаты.
     * <code>
     * $order = new Models_Order();
     * $result = $order->getPaymentMethod(14);
     * viewData($result);
     * </code>
     * @param int $paymentId
     * @return array
     */
    public function getPaymentMethod($paymentId, $check = true) {

        if (count($this->_paymentArray) < $paymentId && $check) {
            return false;
        }

        //получаем доступные методы доставки $this->_paymentArray[$paymentId]['deliveryMethod'] для данного способа оплаты
        //массив соответствия доставки к данному методу.
        $compareArray = $this->getCompareMethod('payment_id', $paymentId);

        if (count($compareArray)) {
            $jsonStr = '{';

            foreach ($compareArray as $compareMethod) {
                $jsonStr .= '"'.$compareMethod['delivery_id'].'":'.$compareMethod['compare'].',';
            }

            $jsonStr = substr($jsonStr, 0, strlen($jsonStr) - 1);
            $jsonStr .= '}';

            $this->_paymentArray[$paymentId]['deliveryMethod'] = $jsonStr;
        } else {
            $this->_paymentArray[$paymentId]['deliveryMethod'] = '{}';
        }
        return $this->_paymentArray[$paymentId];
    }

    /**
     * Получает набор всех способов оплаты.
     * <code>
     * $order = new Models_Order();
     * $result = $order->getPaymentBlocksMethod();
     * viewData($result);
     * </code>
     * @return array
     */
    public function getPaymentBlocksMethod() {

        $paymentArray = array();
        foreach ($this->_paymentArray as $payment) {
            $paymentArray[$payment['id']] = $payment;
            $paymentIds[] = intval($payment['id']);
        }
        $compareArray = array();
        if (!empty($paymentIds)) {
            $in = 'in('.implode(',', $paymentIds).')';
            $res = DB::query('
          SELECT  *  
          FROM `'.PREFIX.'delivery_payment_compare` 
          WHERE `payment_id` '.$in);
            while ($row = DB::fetchAssoc($res)) {
                $compareArray[$row['payment_id']][] = $row;
            }
        }

        foreach ($paymentArray as &$item) {

            // Получаем доступные методы оплаты $delivery['paymentMethod'] для данного способа доставки.
            $jsonStr = '{';
            if (empty($compareArray[$item['id']])) {
                continue;
            }

            foreach ($compareArray[$item['id']] as $compareMethod) {
                $jsonStr .= '"'.$compareMethod['delivery_id'].'":'.$compareMethod['compare'].',';
            }
            $jsonStr = substr($jsonStr, 0, strlen($jsonStr) - 1);
            $jsonStr .= '}';
            $item['deliveryMethod'] = $jsonStr;
        }

        return $paymentArray;
    }

    /**
     * Возвращает весь список способов оплаты в ассоциативном массиве с индексами.
     * <code>
     * $result = Models_Order::getListPayment();
     * viewData($result);
     * </code>
     * @return array
     */
    public function getListPayment() {
        $result = array();
        $res = DB::query('SELECT  *  FROM `'.PREFIX.'payment`');

        while ($row = DB::fetchAssoc($res)) {
            $result[$row['id']] = $row['name'];
        }
        return $result;
    }

    /**
     * Возвращает максимальную сумму заказа.
     * <code>
     * echo Models_Order::getMaxPrice();
     * </code>
     * @return string
     */
    public function getMaxPrice() {
        $result = 0;
        $res = DB::query('
      SELECT MAX(`summ_shop_curr`+`delivery_shop_curr`) as summ 
      FROM `'.PREFIX.'order`');

        if ($row = DB::fetchObject($res)) {
            $result = $row->summ;
        }

        return $result;
    }

    /**
     * Возвращает минимальную сумму заказа.
     * <code>
     * echo Models_Order::getMinPrice();
     * </code>
     * @return string
     */
    public function getMinPrice() {
        $result = 0;
        $res = DB::query('
      SELECT MIN(`summ_shop_curr`+`delivery_shop_curr`) as summ 
      FROM `'.PREFIX.'order`'
        );
        if ($row = DB::fetchObject($res)) {
            $result = $row->summ;
        }
        return $result;
    }

    /**
     * Возвращает дату последнего заказа.
     * <code>
     * echo Models_Order::getMaxDate();
     * </code>
     * @return string
     */
    public function getMaxDate() {
        $result = '0000-00-00 00:00:00';
        $res = DB::query('
      SELECT MAX(add_date) as res 
      FROM `'.PREFIX.'order`');

        if ($row = DB::fetchObject($res)) {
            $result = $row->res;
        }

        return $result;
    }

    /**
     * Возвращает дату первого заказа.
     * <code>
     * echo Models_Order::getMinDate();
     * </code>
     * @return array
     */
    public function getMinDate() {
        $result = '0000-00-00 00:00:00';
        $res = DB::query('
      SELECT MIN(add_date) as res 
      FROM `'.PREFIX.'order`'
        );
        if ($row = DB::fetchObject($res)) {
            $result = $row->res;
        }
        return $result;
    }

    /**
     * Возвращает весь список способов доставки в ассоциативном массиве с индексами.
     * <code>
     * $result = Models_Order::getListDelivery();
     * viewData($result);
     * </code>
     * @return array
     */
    public function getListDelivery() {
        $result = array();
        $res = DB::query('SELECT * FROM `'.PREFIX.'delivery`');
        while ($row = DB::fetchAssoc($res)) {
            $result[$row['id']] = $row['name'];
        }
        return $result;
    }

    /**
     * Получение статуса оплаты.
     * <code>
     * echo Models_Order::getPaidedStatus(5);
     * </code>
     * @param array $paidedId массив с заказом
     * @return string
     */
    public function getPaidedStatus($paidedId) {
        if (1 == $paidedId['paided']) {
            return 'оплачен';
        } else {
            return 'не оплачен';
        }
    }

    /**
     * Возвращает общее количество заказов.
     * <code>
     * echo Models_Order::getOrderCount('WHERE status_id = 5');
     * <\code>
     * @param string $where условие выбора
     * @return string
     */
    public function getOrderCount($where = '') {
        $result = 0;
        $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'order`
    '.$where);

        if ($order = DB::fetchAssoc($res)) {
            $result = $order['count'];
        }

        return $result;
    }

    /**
     * Возвращает информацию о соответствии методов оплаты к методам доставки.
     * <code>
     * $result = Models_Order::getCompareMethod('payment_id', 19);
     * viewData($result);
     * </code>
     * @param string $methodSearch - название поля в базе данных
     * @param int $id - значение поля в базе данных
     * @return array
     */
    private function getCompareMethod($methodSearch, $id) {
        $result = array();
        $res = DB::query('
      SELECT  *  
      FROM `'.PREFIX.'delivery_payment_compare` 
      WHERE `%s` = %d', $methodSearch, $id);
        while ($row = DB::fetchAssoc($res)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Отправляет сообщение  об оплате заказа.
     * <code>
     * $model = new Models_Order;
     * $model->sendMailOfPayed(5, 1500, 19);
     * </code>
     * @param string $orderNumber id заказа.
     * @param string $paySumm сумма заказа.
     * @param string $pamentId id способа оплаты.
     */
    public function sendMailOfPayed($orderNumber, $paySumm, $pamentId) {
        $pamentArray = $this->_paymentArray[$pamentId];
        $siteName = MG::getSetting('sitename');
        $adminEmail = MG::getSetting('adminEmail');
       
        if (class_exists('statusOrder')) {
            $dbQuery = DB::query('SELECT `status` FROM `'.PREFIX.'mg-status-order` '
              . 'WHERE `id_status`=2');
            if ($dbRes = DB::fetchArray($dbQuery)) {
                $status = $dbRes['status'];
            }
        }
        if (empty($status)) {
            $lang = MG::get('lang');
            $status = $lang['PAID'];
        }

        $res = DB::query("SELECT `summ_shop_curr`, `delivery_shop_curr`, `number` FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($orderNumber));
        if ($row = DB::fetchAssoc($res)) {
            
            $paySumm = $row['summ_shop_curr'] + $row['delivery_shop_curr'];
            $orderNumber = $row['number'];
        }

        $subj = 'Оплата заказа '.$orderNumber.' на сайте '.$siteName;

       

        $paramToMail = array(
          'number' => $orderNumber,
          'orderNumber' => $orderNumber,
          'summ' => $paySumm,
          'payment'=> $pamentArray['name'],
          'status'=> $status);

          
     
     
        $emailToUser = MG::layoutManager('email_order_paid', $paramToMail);

    
        $mails = explode(',', MG::getSetting('adminEmail'));

        foreach ($mails as $mail) {
            if (MG::checkEmail($mail)) {
                Mailer::sendMimeMail(array(
                  'nameFrom' => MG::getSetting('shopName'),
                  'emailFrom' => MG::getSetting('noReplyEmail'),
                  'nameTo' => $sitename,
                  'emailTo' => $mail,
                  'subject' => $subj,
                  'body' => $emailToUser,
                  'html' => true
                ));
            }
        }
        $result = true;
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Возвращает ссылки на скачивания электронных товаров.
     * <code>
     * $model = new Models_Order;
     * $result = $model->sendMailOfPayed(5);
     * viewData($result);
     * </code>
     * @param int $orderId id заказа
     * @return array
     */
    public function getFileToOrder($orderId) {
        $linksElectro = array();
        $orderId = (int) $orderId;
        $userInfo = USER::getThis();

        if (empty($userInfo)) {
            return false;
        }

        $orderInfo = $this->getOrder('
      id = '.DB::quoteInt($orderId, true).' AND 
      user_email = '.DB::quote($userInfo->login_email).' AND
      (status_id = 2 OR status_id = 5)'
        );

        $orderInfo[$orderId]['order_content'] = unserialize(stripslashes($orderInfo[$orderId]['order_content']));
        $product = new Models_Product();
        if (!empty($orderInfo[$orderId]['order_content'])) {
            foreach ($orderInfo[$orderId]['order_content'] as $item) {
                $productInfo = $product->getProduct($item['id']);
                if ($productInfo['link_electro']) {
                    $linksElectro[] = array(
                      'link' => SITE.'/order?link='.md5($userInfo->email.$productInfo['link_electro']),
                      'title' => $productInfo['title'],
                      'product' => $productInfo,
                    );
                }
            }
        }

        return $linksElectro;
    }

    /**
     * Возвращает файл по хэшу.
     * <code>
     * Models_Order::getFileByMd5('$1$.z8cFb7V$zt15YCRQ3442XaOU8mkWh1');
     * </code>
     * @param string $md5
     * @return bool
     */
    public function getFileByMd5($md5) {
        $linksElectro = array();
        $realLink = '';
        $userInfo = USER::getThis();

        if (empty($userInfo)) {
            return false;
        }

        $res = DB::query('
      SELECT `link_electro`
      FROM `'.PREFIX.'product`
      WHERE MD5(concat('.DB::quote($userInfo->email).',`link_electro`)) = '.DB::quote($md5).' 
    ');

        if ($row = DB::fetchAssoc($res)) {
            $realLink = $row['link_electro'];
        }

        $realLink = str_replace('/', DS, trim($realLink, DS));
        $realLink = URL::getDocumentRoot().urldecode($realLink);

        if ($realLink) {
            header("Content-Length: ".filesize($realLink));
            header("Content-type: application/octed-stream");
            header('Content-Disposition: attachment; filename="'.basename($realLink).'"');
            readfile($realLink);
            exit();
        }
    }

    /**
     * Отправляет письмо со ссылками на приобретенные электронные товары
     * <code>
     * $model = new Models_Order;
     * $model->sendLinkForElectro(5);
     * </code>
     * @param string $orderId номер заказа.
     */
    public function sendLinkForElectro($orderId) {
        $linksElectro = array();
        $orderInfo = $this->getOrder(' id = '.DB::quote(intval($orderId), true));
        $orderInfo[$orderId]['order_content'] = unserialize(stripslashes($orderInfo[$orderId]['order_content']));
        $product = new Models_Product();
        foreach ($orderInfo[$orderId]['order_content'] as $item) {
            $productInfo = $product->getProduct($item['id']);
            if ($productInfo['link_electro']) {
                $linksElectro[] = $productInfo['link_electro'];
            }
        }
        // если нет электронных товаров в заказе, то не высылаем письмо
        if (empty($linksElectro)) {
            return false;
        }

        $siteName = MG::getSetting('sitename');
        $adminEmail = MG::getSetting('adminEmail');
        $userEmail = $orderInfo[$orderId]['contact_email'];
        $orderNumber = $orderInfo['orderNumber'] != '' ? $orderInfo['orderNumber'] : $orderId;
        $subj = 'Ссылка для скачивания по заказу №'.$orderNumber.' на сайте '.$siteName;

        $paramToMail = array(
          'orderNumber' => $orderNumber,
          'getElectro' => SITE.'/order?getFileToOrder='.$orderId
        );

        $emailToUser = MG::layoutManager('email_order_electro', $paramToMail);

        if (MG::checkEmail($userEmail)) {
            Mailer::sendMimeMail(array(
              'nameFrom' => MG::getSetting('shopName'),
              'emailFrom' => MG::getSetting('noReplyEmail'),
              'nameTo' => $userEmail,
              'emailTo' => $userEmail,
              'subject' => $subj,
              'body' => $emailToUser,
              'html' => true
            ));
        }

        $mails = explode(',', MG::getSetting('adminEmail'));
        $sitename = MG::getSetting('sitename');
        foreach ($mails as $mail) {
            if (MG::checkEmail($mail)) {
                Mailer::sendMimeMail(array(
                  'nameFrom' => MG::getSetting('shopName'),
                  'emailFrom' => MG::getSetting('noReplyEmail'),
                  'nameTo' => $sitename,
                  'emailTo' => $mail,
                  'subject' => $subj,
                  'body' => 'Пользователю '.$userEmail.' выслана ссылка на электронные товары',
                  'html' => true
                ));
            }
        }
    }

    /**
     * Уведомляет админов о смене статуса заказа пользователем, высылая им письма.
     * <code>
     * $model = new Models_Order;
     * $model->sendMailOfUpdateOrder(5);
     * </code>
     * @param int $orderId id заказа.
     */
    public function sendMailOfUpdateOrder($orderId, $comment = null, $status = null) {
        $order = $this->getOrder('id = '.DB::quoteInt(intval($orderId)));
        $orderNumber = $order[$orderId]['number'];
        $siteName = MG::getSetting('sitename');
        $adminEmail = MG::getSetting('adminEmail');
        if (!$status && class_exists('statusOrder')) {
            $dbQuery = DB::query('SELECT `status` FROM `'.PREFIX.'mg-status-order` '
              . 'WHERE `id_status`=4');
            if ($dbRes = DB::fetchArray($dbQuery)) {
                $status = $dbRes['status'];
            }
        }
        if (!$status) {
            $status = $order->getOrderStatus(array('status_id' => 4));
        }
        $subj = 'Пользователь отменил заказ №'.$orderNumber.' на сайте '.$siteName;
        $msg = '
      Вы получили это письмо, так как произведена смена статуса заказа.
     <br/>Статус заказа #'.$orderNumber.' сменен на "'.$status.'".';
        if ($comment) {
            $msg .= '<br/>По причине: '.$comment;
        }

        $mails = explode(',', MG::getSetting('adminEmail'));
        $sitename = MG::getSetting('sitename');
        foreach ($mails as $mail) {
            if (MG::checkEmail($mail)) {
                Mailer::sendMimeMail(array(
                  'nameFrom' => MG::getSetting('shopName'),
                  'emailFrom' => MG::getSetting('noReplyEmail'),
                  'nameTo' => $sitename,
                  'emailTo' => $mail,
                  'subject' => $subj,
                  'body' => $msg,
                  'html' => true
                ));
            }
        }
    }

    /**
     * Возвращает массив параметров оплаты.
     * <code>
     * $order = new Models_Order;
     * $paymentInfo = $order->getParamArray(15, 5, 1500);
     * viewData($paymentInfo);
     * </code>
     * @param int $pay id способа оплаты.
     * @param int $orderId id заказа.
     * @param float $summ сумма заказа.
     * @return array параметры оплаты.
     */
    public function getParamArray($pay, $orderId = null, $summ = null) {
        $paramArray = array();
        $pay = $pay == 23 ? 14 : $pay; //Яндекс Кредит -> Яндекс Касса (HTTP)
        // $pay = $pay == 25 ? 24 : $pay; //Apple Pay -> Яндекс Касса (API)
        $jsonPaymentArray = json_decode(MG::nl2br($this->_paymentArray[$pay]['paramArray']), true);
        if (!empty($jsonPaymentArray)) {
            foreach ($jsonPaymentArray as $paramName => $paramValue) {
                $paramArray[] = array('name' => $paramName, 'value' => $paramValue);
            }
            if (5 == $pay) { // Для robokassa добавляем сигнатуру.
                $alg = $paramArray[3]['value'];
                $login = trim($paramArray[0]['value']);
                $pass1 = trim($paramArray[1]['value']);
                $paramArray['sign'] = hash($alg,$login.":".$summ.":".$orderId.":".$pass1);
            }
            if (9 == $pay) { // Для payanyway добавляем сигнатуру.
                $summ = sprintf("%01.2f", $summ);
                $currency = (MG::getSetting('currencyShopIso') == "RUR") ? "RUB" : MG::getSetting('currencyShopIso');
                $testmode = 0;

                if ($paramArray[2]['value'] == 'true') {
                    $testmode = 1;
                }

                $alg = $paramArray[3]['value'];
                $account = trim($paramArray[0]['value']);
                $securityCode = trim($paramArray[1]['value']);
                $paramArray['sign'] = hash($alg, $account.$orderId.$summ.$currency.$testmode.$securityCode);
            }
            if(15 == $pay) { // Приват24
                $model = new Models_Order;
                $summ = sprintf("%01.2f", $summ);
                $order = $model->getOrder(' id = '.DB::quoteInt(intval($orderId), true));
                $payment = 'amt='.$summ.'&ccy=UAH&details=заказ на '.SITE.'&ext_details='.$order[$orderId]['number']
                  .'&pay_way=privat24&order='.$orderId.'&merchant='.trim($paramArray[0]['value']);
                $pass = trim($paramArray[1]['value']);
                $paramArray['sign'] = sha1(md5($payment.$pass));
            }
            if(16 == $pay) { // LiqPay
                $model = new Models_Order;
                $order = $model->getOrder(' id = '.DB::quote(intval($orderId), true));
                $amount = sprintf("%01.2f", $summ);
                $currency = MG::getSetting('currencyShopIso');

                if($currency == 'RUR') {
                    $currency = 'RUB';
                }

                $params = array(
                  'version'     => 3,
                  'public_key'  => trim($paramArray[0]['value']),
                  'action'      => 'pay',
                  'amount'      => $amount,
                  'currency'    => $currency,
                  'description' => 'Оплата заказа № '.$order[$orderId]['number'],
                  'order_id'    => $orderId,
                  'server_url'  => SITE.'/payment?id=16&pay=result',
                  'result_url'  => SITE.'/payment?id=16&order_id='.$orderId,
                );

                //Для проведения тестовых платежей
                if(!empty($paramArray[2]['value']) && $paramArray[2]['value'] != "false") {
                    $params['sandbox'] = 1;
                }

                $paramArray['data'] = base64_encode(json_encode($params));
                $privateKey = trim($paramArray[1]['value']);
                $paramArray['signature'] = base64_encode(sha1($privateKey.$paramArray['data'].$privateKey, 1));
            }
        }

        return $paramArray;
    }

    /**
     * Создает дубль заказа
     * <code>
     * $order = new Models_Order;
     * $order->cloneOrder(5);
     * </code>
     * @param $id - id копируемого заказа
     * @return bool
     */
    public function cloneOrder($id) {
        $args = [
            'oldOrderId'=> $id,
        ];
        // учет остатков товаров в заказе
        $content = array();
        $storage = '';
        $res = DB::query('SELECT `order_content`, `storage`, `status_id` FROM `'.PREFIX.'order` WHERE `id`= '.DB::quote($id));
        if ($row = DB::fetchArray($res)) {
            $content = unserialize(stripslashes($row['order_content']));
            $storage = $row['storage'];
			$orderStatusId = $row['status_id'];
        }
        $allAvailable = true;
        foreach ($content as $item) {
            if ( $this->notSetGoods($item['id'])==false) {
                return MG::createHook(__CLASS__."_".__FUNCTION__, false , $args);
            }
            $count = 0;
            $res = DB::query('SELECT p.`count`, pv.`count` AS  `var_count`, pv.`code` 
        FROM `'.PREFIX.'product` p LEFT JOIN 
        `'.PREFIX.'product_variant` pv ON p.id = pv.product_id WHERE p.id='.DB::quote($item['id']));
            while($row = DB::fetchArray($res)) {
                if (!empty($row['code'])&& $row['code'] == $item['code']) {
                    $count = $row['var_count'];
                } elseif(empty($row['code'])) {
                    $count = $row['count'];
                }
                if (empty($item['variant_id'])) {$item['variant_id'] = 0;}
                if(MG::enabledStorage()) {
                  $count = 0;
                  foreach ($item['storage_id'] as $storage => $countProd){
                    $count = MG::getProductCountOnStorage($countProd, $item['id'], $item['variant_id'], $storage) + $count;
                  }
                }
            }
            if ($count >= 0 && $count < $item['count']) {
                $allAvailable = false;
            }
        }
        if ($allAvailable == false ) {
            return MG::createHook(__CLASS__."_".__FUNCTION__, false , $args);
        }
        $product = new Models_Product();
        foreach ($content as $item) {
			if($orderStatusId != '4'){
				if(MG::enabledStorage()) {
					MG::decreaseCountProductOnStorageNew($item, $storage);
				} else {
					$product->decreaseCountProduct($item['id'], $item['code'], $item['count']);
				}
			}
        }
        $sql = " INSERT INTO  
      `".PREFIX."order`
        ( 
          `updata_date`, 
          `add_date`, 
          `close_date`, 
          `user_email`, 
          `contact_email`, 
          `phone`, 
          `address`, 
          `summ`, 
          `order_content`, 
          `delivery_id`, 
          `delivery_cost`, 
          `payment_id`, 
          `paided`, 
          `status_id`, 
          `comment`, 
          `confirmation`, 
          `yur_info`, 
          `name_buyer`,
          `storage`,
          `summ_shop_curr`,
          `delivery_shop_curr`,
          `currency_iso`
        ) 
      SELECT 
        `updata_date`, 
         now() as `add_date`,
        `close_date`, 
        `user_email`, 
        `contact_email`,
        `phone`, 
        `address`, 
        `summ`,
        `order_content`,
        `delivery_id`,
        `delivery_cost`,
        `payment_id`,
        `paided`,
        `status_id`,
        `comment`,
        `confirmation`,
        `yur_info`,
        `name_buyer`,
        `storage`,
        `summ_shop_curr`,
        `delivery_shop_curr`,
        `currency_iso`
      FROM ".PREFIX."order
      WHERE `id`= ".DB::quote($id);
        $res = DB::query($sql);
        $newOrderId = DB::insertId();
        $orderNumber = $this->getOrderNumber($newOrderId);
        $orderPayHash = $this->orderIdToPayHash($newOrderId);
        DB::query("UPDATE `".PREFIX."order` SET `number`= ".DB::quote($orderNumber).", `pay_hash` = ".DB::quote($orderPayHash)." WHERE `id`=".DB::quote($newOrderId)."");
        // Возвращаем номер клонруемого заказа.
        $args['newOrderId'] = $newOrderId;
        return MG::createHook(__CLASS__."_".__FUNCTION__, true , $args);
    }

    /**
     * Возвращает общее количествo невыполненных заказов.
     * <code>
     * echo Models_Order::getNewOrdersCount();
     * </code>
     * @return int - количество заказов
     */
    public function getNewOrdersCount() {
        if (empty($_SESSION['user']->id)) {
            return 0;
        }
        $owners = '';
        if (MG::getSetting('orderOwners') == 'true' && $_SESSION['user']->role != 1) {
            $owners = ' AND owner = '.DB::quoteInt($_SESSION['user']->id);
        }
        $sql = "
  		SELECT `id`
      FROM `".PREFIX."order`
      WHERE `status_id`!=5 AND `status_id`!=4".$owners;

        $res = DB::query($sql);
        $count = DB::numRows($res);
        return $count ? $count : 0;
    }

    /**
     * Возвращает статистику заказов за каждый день начиная с открытия магазина.
     * <code>
     * $result = Models_Order::getOrderStat();
     * viewData($result);
     * </code>
     * @return array - [время, значение]
     */
    public function getOrderStat() {
        $result = array();
        $res = DB::query('    
      SELECT (UNIX_TIMESTAMP( CAST( o.add_date AS DATE ) ) * 1000) as "date" , COUNT( add_date ) as "count"
      FROM `'.PREFIX.'order` AS o
      GROUP BY CAST( o.add_date AS DATE )
    ');

        while ($order = DB::fetchAssoc($res)) {
            $result[] = array($order['date'] * 1, $order['count'] * 1);
        }
        return $result;
    }

    /**
     * Возвращает статистику заказов за выбранный период.
     * <code>
     * $result = Models_Order::getStatisticPeriod('01.01.2017','01.01.2018');
     * viewData($result);
     * </code>
     * @param string $dateFrom дата "Oт".
     * @param string $dateTo дата "До".
     * @return array
     */
    public function getStatisticPeriod($dateFrom, $dateTo) {
        $summ = 0;
        $dateFromRes = $dateFrom;
        $dateToRes = $dateTo;
        $dateFrom = date('Y-m-d', strtotime($dateFrom));
        $dateTo = date('Y-m-d', strtotime($dateTo));
        $period = "AND `add_date` >= ".DB::quote($dateFrom)."
       AND `add_date` <= ".DB::quote($dateTo);

        // Количество закрытых заказов всего.
        $ordersCount = $this->getOrderCount('WHERE status_id = 5 '.$period);

        $noclosed = $this->getOrderCount('WHERE status_id <> 5 '.$period);

        // Сумма заработанная за все время работы магазина.
        $res = DB::query("
      SELECT sum(summ) as 'summ'  FROM `".PREFIX."order`
      WHERE status_id = 5 ".$period
        );

        if ($row = DB::fetchAssoc($res)) {
            $summ = $row['summ'];
        }

        $product = new Models_Product;
        $productsCount = $product->getProductsCount();
        $res = DB::query("SELECT id  FROM `".PREFIX."user`");
        $usersCount = DB::numRows($res);

        $result = array(
          'from_date_stat' => $dateFromRes,
          'to_date_stat' => $dateToRes,
          "orders" => $ordersCount ? $ordersCount : "0",
          "noclosed" => $noclosed ? $noclosed : "0",
          "summ" => $summ ? $summ : "0",
          "users" => $usersCount ? $usersCount : "0",
          "products" => $productsCount ? $productsCount : "0",
        );

        return $result;
    }

    /**
     * Выводит на экран печатную форму для печати заказа в админке.
     * <code>
     * $model = new Models_Order;
     * echo $model->printOrder(5);
     * </code>
     * @param int $id id заказа.
     * @param bool $sign использовать ли подпись.
     * @param string $type тип документа
     * @param string $usestamp выключить печать и подпись (true - выключить, false - включить)
     * @return string
     */
    public function printOrder($id, $sign = true, $type="order", $usestamp='false') {
        $orderInfo = $this->getOrder('id='.DB::quoteInt(intval($id), true));
        if ($type == 'qittance') {
            $summ = $orderInfo[$id]['summ']+$orderInfo[$id]['delivery_cost'];
            $paramArray = $this->getParamArray('7', $id, $summ);
            $data['paramArray'] = $paramArray;
            foreach($data['paramArray'] as $k=>$field) {
                $data['paramArray'][$k]['value'] = htmlentities($data['paramArray'][$k]['value'], ENT_QUOTES, "UTF-8");
            }
            $line = "<p class='line'></p>";
            $data['line'] = "<p class='line'></p>";
            $data['line2'] = "<p class='line2'></p>";
            $shopParams = unserialize(stripslashes(MG::getSetting('propertyOrder')));
            $shopParams['nameyur'] = htmlspecialchars_decode(!empty($shopParams['nameyur']) ? $shopParams['nameyur'] : $line);
            $shopParams['inn'] = htmlspecialchars_decode(!empty($shopParams['inn']) ? $shopParams['inn'] : $line);
            $shopParams['rs'] = htmlspecialchars_decode(!empty($shopParams['rs']) ? $shopParams['rs'] : $line);
            $shopParams['ks'] = htmlspecialchars_decode(!empty($shopParams['ks']) ? $shopParams['ks'] : $data['line2']);
            $shopParams['bank'] = htmlspecialchars_decode(!empty($shopParams['bank']) ? $shopParams['bank'] : $line);
            $shopParams['bik'] = htmlspecialchars_decode(!empty($shopParams['bik']) ? $shopParams['bik'] : $data['line2']);

            $data['name'] = htmlspecialchars_decode((!empty($data['paramArray'][0]['value'])) ? $data['paramArray'][0]['value'] : $shopParams['nameyur']);
            $data['inn'] = htmlspecialchars_decode((!empty($data['paramArray'][1]['value'])) ? $data['paramArray'][1]['value'] : $shopParams['inn']);
            $data['nsp'] = htmlspecialchars_decode((!empty($data['paramArray'][6]['value'])) ? $data['paramArray'][6]['value'] : $shopParams['rs']);
            $data['ncsp'] = htmlspecialchars_decode((!empty($data['paramArray'][7]['value'])) ? $data['paramArray'][7]['value'] : $shopParams['ks']);
            $data['bank'] = htmlspecialchars_decode((!empty($data['paramArray'][4]['value'])) ? $data['paramArray'][4]['value'] : $shopParams['bank']);
            $data['bik'] = htmlspecialchars_decode((!empty($data['paramArray'][5]['value'])) ? $data['paramArray'][5]['value'] : $shopParams['bik']);

            $data['appointment'] = "Оплата по счету №".($orderInfo[$id]['number']!=''?$orderInfo[$id]['number']:$id);
            $data['nls'] = $line;
            $data['payer'] = $orderInfo[$id]['name_buyer'];
            $data['addrPayer'] = $orderInfo[$id]['address'];
            $data['sRub'] = $orderInfo[$id]['summ']+$orderInfo[$id]['delivery_cost'] ? $orderInfo[$id]['summ']+$orderInfo[$id]['delivery_cost'] : '_______';
            $data['sKop'] = 0;
            $data['uRub'] = '_______';
            $data['uKop'] = 0;
            $data['day'] = date('d');
            $data['month'] = date('m');
            $data['rub'] = '_______';
            $data['kop'] = '_______';
            return MG::layoutManager('print_'.$type, $data);
        }
        $order = $orderInfo[$id];
        $lang = MG::get('lang');

        $perOrders = unserialize(stripslashes($order['order_content']));
        $prodPicIds = $varPicIds = $prodPics = $varPics = array();
        foreach ($perOrders as $prod) {
            if (!empty($prod['variant_id'])) {
                $varPicIds[] = $prod['variant_id'];
            }
            if (!empty($prod['variant'])) {//старые заказы
                $varPicIds[] = $prod['variant'];
            }
            $prodPicIds[] = $prod['id'];
        }

        if (!empty($prodPicIds)) {
            $res = DB::query("SELECT `id`, `image_url` FROM `".PREFIX."product` WHERE `id` IN (".DB::quoteIN($prodPicIds).")");
            while ($row = DB::fetchAssoc($res)) {
                $folder = floor($row['id']/100).'00';
                $images = explode('|', $row['image_url']);
                if ($images[0] && is_file(SITE_DIR.'uploads'.DS.'product'.DS.$folder.DS.$row['id'].DS.$images[0])) {
                    $prodPics[$row['id']] = SITE.'/uploads/product/'.$folder.'/'.$row['id'].'/'.$images[0];
                }
            }
        }
        if (!empty($varPicIds)) {
            $res = DB::query("SELECT `id`, `product_id`, `image` FROM `".PREFIX."product_variant` WHERE `id` IN (".DB::quoteIN($varPicIds).")");
            while ($row = DB::fetchAssoc($res)) {
                $folder = floor($row['product_id']/100).'00';
                $images = explode('|', $row['image']);
                if ($images[0] && is_file(SITE_DIR.'uploads'.DS.'product'.DS.$folder.DS.$row['product_id'].DS.$images[0])) {
                    $varPics[$row['id']] = SITE.'/uploads/product/'.$folder.'/'.$row['product_id'].'/'.$images[0];
                }
            }
        }
        foreach ($perOrders as $key => $prod) {
            if (!empty($prod['variant_id']) && !empty($varPics[$prod['variant_id']])) {
                $perOrders[$key]['image'] = $varPics[$prod['variant_id']];
                continue;
            }
            if (!empty($prod['variant']) && !empty($varPics[$prod['variant']])) {//старые заказы
                $perOrders[$key]['image'] = $varPics[$prod['variant']];
                continue;
            }
            if (!empty($prodPics[$prod['id']])) {
                $perOrders[$key]['image'] = $prodPics[$prod['id']];
                continue;
            }
            if (is_file(SITE_DIR.'uploads'.DS.'no-img.jpg')) {
                $perOrders[$key]['image'] = SITE.'/uploads/no-img.jpg';
            }
            if (
                ($noImageStub = MG::getSetting('noImageStub')) &&
                is_file(SITE_DIR.str_replace('/', DS, $noImageStub))
            ) {
                $perOrders[$key]['image'] = SITE.$noImageStub;
            }
        }

        $currency = MG::getSetting('currency');
        $totSumm = $order['summ'] + $order['cost'];
        $paymentArray = $this->getPaymentMethod($order['payment_id'], false);
        $order['name'] = $paymentArray['name'];

        $propertyOrder = MG::getSetting('propertyOrder', true);
        if (!isset($propertyOrder['email'])) {$propertyOrder['email'] = '';}

        $addReqs = MG::getSetting('addRequisites',true);

        if(!empty($order['yur_info'])){
            if(!empty($addReqs[$order['shop_yur_id']])){
                foreach ($addReqs[$order['shop_yur_id']] as $key => $value) {
                    $propertyOrder[$key] = $value;
                }
            }
        } else {
            foreach($addReqs as $key => $req){
                if($addReqs[$key]['choosen'] === 'true'){
                    foreach ($req as $key => $value) {
                        $propertyOrder[$key] = $value;
                    }
                    break;
                }
            }
        }

      

        $customer = unserialize(stripslashes($order['yur_info']));

        if ($type == 'packing-list') {
            $customerInfo = $customer['nameyur'].', '
              .'ИНН '. $customer['inn'].', '
              .$customer['adress'].', '
              .'р/с '.$customer['rs'].', '
              .'в банке '.$customer['bank'].', '
              .'БИК '.$customer['bik'].', '
              .'к/с '.$customer['ks'];
        } else {
            $customerInfo = $customer['nameyur'] . ', ' .
              $lang['OREDER_LOCALE_16'] . ': &nbsp;' . $customer['inn'] . ', ' .
              (!empty($customer['kpp']) ? $lang['OREDER_LOCALE_17'] . ': ' . $customer['kpp'] . ', ' : '') .
              (!empty($customer['adress']) ? $lang['OREDER_LOCALE_15'] . ': ' . $customer['adress'] . ', ' : '') .
              (!empty($customer['bank']) ? $lang['OREDER_LOCALE_18'] . ': ' . $customer['bank'] . ', ' : '') .
              (!empty($customer['bik']) ? $lang['OREDER_LOCALE_19'] . ': ' . $customer['bik'] . ', ' : '') .
              (!empty($customer['ks']) ? $lang['OREDER_LOCALE_20'] . ': ' . $customer['ks'] . ', ' : '') .
              (!empty($customer['rs']) ? $lang['OREDER_LOCALE_21'] . ': ' . $customer['rs'] : '');
        }

        $ylico = false;
        if (empty($customer['inn'])) {
            $fizlico = true;
            $userInfo = USER::getUserInfoByEmail($order['user_email'],'login_email');

            if ($type == 'packing-list') {
                $customerInfo = $order['name_buyer'].', '.$order['address'].', '.$order['phone'];
            } elseif($type == 'order') {
                $customerInfo = $lang['ORDER_BUYER'].': &nbsp;'.$order['name_buyer'].'<br/>'.$lang['ORDER_PHONE'].': &nbsp;'.
                  $order['phone'].' <br/>'.$lang['ORDER_CONTACT_EMAIL'].': &nbsp;'.$order['contact_email'].'<br/>'.$lang['ORDER_ADDRESS'].': &nbsp;'.
                  $order['address'];
            } else {
                $customerInfo = $lang['ORDER_BUYER'].': &nbsp;'.$order['name_buyer'].'<br/>'.$lang['ORDER_ADDRESS'].': &nbsp;'.
                  $order['address'].'<br/> '.$lang['ORDER_PHONE'].': &nbsp;'.
                  $order['phone'].' <br/>'.$lang['ORDER_CONTACT_EMAIL'].': &nbsp;'.$order['contact_email'];
            }
        }

        if ($type == "invoice" && !empty($customer['nameyur']) && !empty($customer['adress'])) {
            $order['name_buyer'] = $customer['nameyur'];
            $order['address'] = $customer['adress'];
        }

        $opFieldsM = new Models_OpFieldsOrder($id);
        $order['optionalFields'] = $opFieldsM->getHumanView();

        $customerInfo = htmlspecialchars($customerInfo);
        $propertyOrder['sing'] = $propertyOrder['sing'] ? $propertyOrder['sing'] : 'uploads/sing.jpg';
        $propertyOrder['stamp'] = $propertyOrder['stamp'] ? $propertyOrder['stamp'] : 'uploads/stamp.jpg';
        if($usestamp=='true'){
            $propertyOrder['usedsing'] = 'false';
        }
        $data['propertyOrder'] = $propertyOrder;
        $data['order'] = $order;
        $data['customerInfo'] = $customerInfo;
        $data['perOrders'] = $perOrders;
        $data['currency'] = $currency;
        $data['customerInfo'] = htmlspecialchars_decode($data['customerInfo']);

        if(empty($type)) {
            $type = "order";
        }
        if (empty($data['propertyOrder']['prefix'])) {
            $data['propertyOrder']['prefix'] = '';
        }
        return MG::layoutManager('print_'.$type, $data);
    }

    /**
     * Отдает pdf файл на скачивание.
     * <code>
     * $model = new Models_Order;
     * $model->getPdfOrder(5);
     * </code>
     * @param int $orderId номер заказа id.
     * @param string $type тип запрашиваемого результата.
     * @param string $usestamp выключить печать и подпись (true - выключить, false - включить)
     * @return bool|void
     */
    public function getPdfOrder($orderId, $type="order", $usestamp='false') {
        if(empty($type)) {
            $type="order";
        }
        if(empty($usestamp)){
            $usestamp = 'false';
        }
        // Подключаем библиотеку tcpdf.php
        require_once('mg-core/script/tcpdf/tcpdf.php');
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setImageScale(1.53);
        $pdf->SetFont('roboto', '', 10);
        $pdf->AddPage();

        $orderInfo = $this->getOrder('id='.DB::quoteInt(intval($orderId), true));

        $access = false;
        if (USER::getThis()->login_email && (USER::getThis()->login_email == $orderInfo[$orderId]['user_email'] || USER::getThis()->role != 2)) {
            $access = true;
        }
        if (MG::getSetting('autoRegister') == "false") {
            $access = true;
        }
        if (!$access) {
            MG::redirect('/404');
            return false;
        }

        $html = $this->printOrder($orderId, true, $type, $usestamp);

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('Order '.$orderInfo[$orderId]['number'].'.pdf', 'D');
        exit;
    }

    /**
     * Отдает pdf с несколькими заказами на скачивание
     *
     * @param array $ordersIds массив id заказов
     * @param string название блока верстки
     */
    public function getMassPdfOrders($orderIds, $layout) {

        $orderIds = json_decode($orderIds, 1);
        $html = array();
        foreach ($orderIds as $orderId) {
            $html[] = $this->printOrder($orderId, true, $layout);
        }
        $html = implode('<br pagebreak="true"/>', $html);

        Error_Reporting(E_ERROR | E_PARSE);
        require_once('mg-core/script/tcpdf/tcpdf.php');
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setImageScale(1.53);
        $pdf->SetFont('arial', '', 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Orders_'.$layout.'_'.date("d.m.Y").'.pdf', 'D');
        die;
    }

    /**
     * Выводит на экран печатную форму для печати квитанции на оплату заказа.
     * <code>
     * $model = new Models_Order;
     * $model->printQittance();
     * </code>
     * @param bool вывод на печать в публичной части, либо в админке.
     * @return void|string
     */
    public function printQittance($public = true) {
        MG::disableTemplate();
        $uKop = $sKop = '___';
        $data['line'] = "<p class='line'></p>";
        $data['line2'] = "<p class='line2'></p>";
        $shopParams = unserialize(stripslashes(MG::getSetting('propertyOrder')));
        $shopParams['nameyur'] = htmlspecialchars_decode(!empty($shopParams['nameyur']) ? $shopParams['nameyur'] : $data['line']);
        $shopParams['inn'] = htmlspecialchars_decode(!empty($shopParams['inn']) ? $shopParams['inn'] : $data['line']);
        $shopParams['rs'] = htmlspecialchars_decode(!empty($shopParams['rs']) ? $shopParams['rs'] : $data['line']);
        $shopParams['ks'] = htmlspecialchars_decode(!empty($shopParams['ks']) ? $shopParams['ks'] : $data['line2']);
        $shopParams['bank'] = htmlspecialchars_decode(!empty($shopParams['bank']) ? $shopParams['bank'] : $data['line']);
        $shopParams['bik'] = htmlspecialchars_decode(!empty($shopParams['bik']) ? $shopParams['bik'] : $data['line2']);

        $data['name'] = (!empty($_POST['name'])) ? $_POST['name'] : $shopParams['nameyur'];
        $data['inn'] = (!empty($_POST['inn'])) ? $_POST['inn'] : $shopParams['inn'];
        $data['nsp'] = (!empty($_POST['nsp'])) ? $_POST['nsp'] : $shopParams['rs'];
        $data['ncsp'] = (!empty($_POST['ncsp'])) ? $_POST['ncsp'] : $shopParams['ks'];
        $data['bank'] = (!empty($_POST['bank'])) ? $_POST['bank'] : $shopParams['bank'];
        $data['bik'] = (!empty($_POST['bik'])) ? $_POST['bik'] : $shopParams['bik'];
        $data['appointment'] = (!empty($_POST['appointment'])) ? $_POST['appointment'] : $data['line'];
        $data['nls'] = (!empty($_POST['nls'])) ? $_POST['nls'] : $data['line'];
        $data['payer'] = (!empty($_POST['payer'])) ? $_POST['payer'] : $data['line2'];
        $data['addrPayer'] = (!empty($_POST['addrPayer'])) ? $_POST['addrPayer'] : $data['line2'];
        $data['sRub'] = (!empty($_POST['sRub'])) ? $_POST['sRub'] : '_______';
        $data['sKop'] = (!empty($_POST['sKop'])) ? $_POST['sKop'] : 0;
        $data['uRub'] = (!empty($_POST['uRub'])) ? $_POST['uRub'] : '_______';
        $data['uKop'] = (!empty($_POST['uKop'])) ? $_POST['uKop'] : 0;
        $data['day'] = (!isset($_POST['day']) || $_POST['day'] == '_') ? '____' : $_POST['day'];
        $data['month'] = (!isset($_POST['month']) || $_POST['month'] == '_') ?
          '___________________' : $_POST['month'];

        if (!isset($_POST['sKop'])) {
            $sKop = '___';
        }
        if (!isset($_POST['uKop'])) {
            $uKop = '___';
        }
        $sResult = (!empty($sKop)) ? $sResult = $data['sRub'].$sKop : $data['sRub'];
        $uResult = (!empty($uKop)) ? $uResult = $data['uRub'].$uKop : $data['uRub'];

        $rubResult = $sResult + $uResult;

        if (empty($rubResult)) {
            settype($rubResult, 'null');
        }

        if (is_double($rubResult)) {
            list($rub, $kop) = explode('.', $rubResult);
        } else if (is_int($rubResult)) {
            $rub = $rubResult;
            $kop = "0";
        }

        if (empty($rub))
            $rub = '_______';
        if (!isset($kop))
            $kop = '___';

        $data['rub'] = $rub;
        $data['kop'] = $kop;
        $data['uKop'] = $uKop;
        $data['sKop'] = $sKop;

        $html = '';

        $html = MG::layoutManager('print_qittance', $data);

        if ($public) {
            echo $html;
            exit();
        }
        return $html;
    }

    /**
     * Экспортирует параметры конкретного заказа в CSV файл.
     * <code>
     * $order = new Models_Order;
     * $order->getExportCSV(5);
     * </code>
     * @param $orderId - id заказа.
     * @return void
     */
    public function getExportCSV($orderId) {

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream;");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=data.csv");
        header("Content-Transfer-Encoding: binary ");

        $csvText = '';
        $orderInfo = $this->getOrder('id='.DB::quoteInt(intval($orderId), true));

        $order = $orderInfo[$orderId];

        $order_content =stripslashes($order["order_content"]);
        $order_content = unserialize($order_content);

        foreach ($order_content as $item) {
            $csvText .= "\"".$item["id"]."\";\"".
              $order["add_date"]."\";\"".
              $order["name_buyer"]."\";\"".
              $order["user_email"]."\";\"".
              $order["phone"]."\";\"".
              $order["address"]."\";\"".
              $order["comment"]."\";\"".
              $item["name"]."\";\"".
              $item["code"]."\";\"".
              number_format($item["price"]*$item["count"], 2, ',', '')."\";\"".
              $item["count"]."\";\"".
              $item["coupon"]."\"\n";
        }

        echo mb_convert_encoding($csvText, "WINDOWS-1251", "UTF-8");
        exit;
    }

    /**
     * Возвращаем номер или префикс заказа.
     * <code>
     * echo Models_Order::getOrderNumber(5);
     * </code>
     * @param int $id id заказа.
     * @return string номер заказа
     */
    private function getOrderNumber($id) {
        $orderNum = MG::getSetting('orderNumber');
        $prefix = '';
        if(MG::getSetting('prefixOrder')) $prefix = MG::getSetting('prefixOrder');
        if($orderNum =='false') {
            $result = $prefix.$id;
        } else {
            $str = mt_rand(10000, 999999999);
            $str = $str?$str:rand(10000, 999999);

            $result = str_pad((string)$str, 10, '0', STR_PAD_LEFT);
            $result = $prefix.$result;
        }
        // Возвращаем номер или префикс заказа.
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    public function refreshCountAfterEdit($orderId, $newOrderContent) {
        $orders = $this->getOrder('`id` = '.DB::quoteInt($orderId));
        if (empty($orders[$orderId])) {
            return false;
        }
        $order = $orders[$orderId];
        
        $orderOldProducts = unserialize(stripslashes($order['order_content']));
        $orderNewProducts = unserialize(stripslashes($newOrderContent));

        $productModel = new Models_Product();

        $result = 1;

        if (MG::enabledStorage()) {
            $recalculateIds = [];
            foreach ($orderOldProducts as $oldProduct) {
                if (empty($oldProduct['storage_id'])) {
                    $result = 0;
                    break;
                }
                $oldProductId = intval($oldProduct['id']);
                $oldProductVariantId = intval($oldProduct['variant_id']);
                foreach ($oldProduct['storage_id'] as $storageDataString) {
                    $storageDataArray = explode('_', $storageDataString);
                    $countFromStorage = floatval(array_pop($storageDataArray));
                    $storageId = implode('_', $storageDataArray);
                    $currentCountOnStorage = $productModel->getProductStorageCount($storageId, $oldProductId, $oldProductVariantId);
                    if (floatval($currentCountOnStorage) === floatval(-1)) {
                        continue;
                    }
                    $newCountOnStorage = $currentCountOnStorage + $countFromStorage;
                    $productModel->updateStorageCount($oldProductId, $storageId, $newCountOnStorage, $oldProductVariantId);
                    $recalculateIds[] = $oldProductId;
                }
            }
            if ($result) {
                foreach ($orderNewProducts as $newProduct) {
                    if (empty($newProduct['storage_id'])) {
                        $result = 0;
                        break;
                    }
                    $newProductId = intval($newProduct['id']);
                    $newProductVariantId = intval($newProduct['variant_id']);
                    foreach ($newProduct['storage_id'] as $storageId => $countFromStorage) {
                        $currentCountOnStorage = $productModel->getProductStorageCount($storageId, $newProductId, $newProductVariantId);
                        if (floatval($currentCountOnStorage) === floatval(-1)) {
                            continue;
                        }
                        $newCountOnStorage = $currentCountOnStorage - $countFromStorage;
                        $productModel->updateStorageCount($newProductId, $storageId, $newCountOnStorage, $newProductVariantId);
                        $recalculateIds[] = $newProductId;
                    }
                }
            }
            if ($result && $recalculateIds) {
                $recalculateIds = array_unique($recalculateIds);
                foreach ($recalculateIds as $recalculateId) {
                    $productModel->recalculateStoragesById($recalculateId);
                }
            }
        } else {
            $codes = [];
            foreach ($orderOldProducts as $oldProduct) {
                $oldProductId = intval($oldProduct['id']);
                $oldProductVariantId = intval($oldProduct['variant_id']);
                $oldProductCode = $oldProduct['code'];
                $oldProductCount = floatval($oldProduct['count']);
                $oldProductKey = $oldProductId.'|'.$oldProductCode;
                if (empty($codes[$oldProductKey])) {
                    $codes[$oldProductKey] = [
                        'id' => $oldProductId,
                        'code' => $oldProductCode,
                        'count' => $oldProductCount,
                    ];
                    if ($oldProductVariantId) {
                        $codes[$oldProductKey]['variant_id'] = $oldProductVariantId;
                    }
                    continue;
                }
                $codes[$oldProductKey]['count'] += $oldProductCount;
            }
            foreach ($orderNewProducts as $newProduct) {
                $newProductId = intval($newProduct['id']);
                $newProductVariantId = intval($newProduct['variant_id']);
                $newProductCode = $newProduct['code'];
                $newProductCount = floatval($newProduct['count']);
                $newProductKey = $newProductId.'|'.$newProductCode;
                if (empty($codes[$newProductKey])) {
                    $codes[$newProductKey] = [
                        'id' => $newProductId,
                        'code' => $newProductCode,
                        // Умножаем на -1, потому что дальше по алгоритму мы отрицательное кол-во воспринимаем как то, что нужно списать из остатков, а положительное, как то, что нужно вернуть в остатки
                        'count' => $newProductCount * -1,
                    ];
                    if ($newProductVariantId) {
                        $codes[$newProductKey]['variant_id'] = $newProductVariantId;
                    }
                    continue;
                }
                $codes[$newProductKey]['count'] -= $newProductCount;
            }
            foreach ($codes as $prod) {
                if (!$prod['count']) {
                    continue;
                }
                if ($prod['count'] > 0) {
                    $productModel->increaseCountProduct($prod['id'], $prod['code'], $prod['count']);
                    continue;
                }
                $productModel->decreaseCountProduct($prod['id'], $prod['code'], abs($prod['count']));
            }
        }
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Пересчитывает количество остатков продуктов при редактировании заказа.
     * <code>
     * $orderId = 5;
     * $content = 'a:1:{i:0;a:16:{s:2:\"id\";s:2:\"40\";s:7:\"variant\";s:4:\"1099\";s:5:\"title\";s:72:\"Чехол на руку для смартфона Demix+ Зелёный\";s:4:\"name\";s:72:\"Чехол на руку для смартфона Demix+ Зелёный\";s:8:\"property\";s:0:\"\";s:5:\"price\";s:3:\"499\";s:8:\"fulPrice\";s:3:\"499\";s:4:\"code\";s:6:\"SKU343\";s:6:\"weight\";s:1:\"0\";s:12:\"currency_iso\";s:3:\"RUR\";s:5:\"count\";s:1:\"2\";s:6:\"coupon\";s:1:\"0\";s:4:\"info\";s:0:\"\";s:3:\"url\";s:69:\"aksessuary/chehly-dlya-smartfonov/chehol-na-ruku-dlya-smartfona-demix\";s:8:\"discount\";s:1:\"0\";s:8:\"discSyst\";s:11:\"false/false\";}}';
   * $order = new Models_Order;
     * $result = $order->refreshCountAfterEdit($orderId, $content);
     * var_dump($result);
     * </code>
     * @param int $orderId id заказа.
     * @param string $content новое содержимое содержимое заказа (сериализованный массив)
     * @return bool
     */
    // public function refreshCountAfterEditOld($orderId, $content) {
    //     // Если количество товара меняется, то пересчитываем остатки продуктов из заказа.
    //     $order = $this->getOrder(' id = '.DB::quoteInt(intval($orderId), true));
    //     $flag = 0;
    //     $sql = "SELECT `order_content` FROM `".PREFIX."order` WHERE `id` = ".db::quoteInt($orderId);
    //     $row = db::fetchAssoc(db::query($sql));
    //     $order_content_old = unserialize(stripslashes($row['order_content']));
    //     $order_content_new = unserialize(stripslashes($content));
    //     $product = new Models_Product();
    //     $codes = array();
    //     foreach ($order_content_old as $item_old) {
    //         if (!empty($codes[$item_old['id'].'|'.$item_old['code']])) {
    //             $codes[$item_old['id'].'|'.$item_old['code']]['count'] += $item_old['count'];
    //         } else {
    //             $codes[$item_old['id'].'|'.$item_old['code']] = array(
    //               'id' => $item_old['id'],
    //               'code' => $item_old['code'],
    //               'count' => $item_old['count']);
    //             if (!empty($item_old['variant_id'])) {
    //                 $codes[$item_old['id'].'|'.$item_old['code']]['variant_id'] = $item_old['variant_id'];
    //             }
    //         }
    //     }

    //     foreach ($order_content_new as $item_new) {
    //         $flag = 0;
    //         foreach ($codes as $key => $info) {
    //             if (in_array($item_new['code'], $info)&&$item_new['id']==$info['id']) {
    //                 $newKey = $item_new['id'].'|'.$item_new['code'];
    //                 $codes[$newKey] = array(
    //                   'id' => $item_new['id'],
    //                   'code' => $item_new['code'],
    //                   'count' => $info['count'] - $item_new['count'],
    //                   'variant_id' => $item_new['variant_id']);
    //                 $flag = 1;
    //             }
    //         }
    //         if ($flag === 0) {
    //             $codes[] = array(
    //               'id' => $item_new['id'],
    //               'code' => $item_new['code'],
    //               'count' => $item_new['count'] * (-1),
    //               'variant_id' => $item_new['variant_id']);
    //         }
    //     }
    //     //Изменение остатков товара на складах
    //     if(MG::enabledStorage()){
    //       foreach ($order_content_old as $productItem){
    //         MG::increaseCountProductOnStorageNew($productItem);
    //       }
    //       foreach ($order_content_new as $productItem){
    //         MG::decreaseCountProductOnStorageNew($productItem);
    //       };
    //     }
    //     foreach ($codes as $prod) {
    //         if ($prod['count'] > 0) {
    //             $product->increaseCountProduct($prod['id'], $prod['code'], $prod['count']);
    //         } elseif ($prod['count'] < 0) {
    //             $product->decreaseCountProduct($prod['id'], $prod['code'], abs($prod['count']));
    //         }
    //     }

    //     $result = $flag;
    //     $args = func_get_args();
    //     return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    // }

    /**
     * Проверяет есть в заказе комплект или нет при копировании заказа
     * <code>
     * $result = Models_Order::notSetGoods(3);
     * var_dump($result);
     * </code>
     * @param int $id id товара
     * @return bool
     */
    public function notSetGoods($id) {
        $result = true;
        $args = func_get_args();
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    }

    /**
     * Выгружает список заказов в CSV файл.
     * <code>
     * $order = new Models_Order;
     * echo $order->exportToCsvOrder();
     * </code>
     * @param array $listOrderId выгрузка выбранных заказов
     * @param bool $full полная выгрузка
     * @return string
     */
    public function exportToCsvOrder($listOrderId=array(), $full = false, $exportToCSV = false) {

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream;");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=data.csv");
        header("Content-Transfer-Encoding: binary ");

        $listId = '';
        $csvText = '"id";"Номер";"Имя ответственного";"E-mail ответственного";Дата создания";"Контактный E-mail";"Имя пользователя";"Контактный телефон";"Адрес";"Сумма";"Купон";"Скидка";"Статус заказа";"Способ доставки";"Стоимость доставки";"Оплата";';
        if ($full) {
            $csvText = '"id заказа";"Номер";"Имя ответственного";"E-mail ответственного";"Дата создания";"Сумма заказа";"id товара";"Артикул";"Наименование";"Количество";"Полная цена";"Стоимость";"Вес";"Купон";"Скидка";"Контактный E-mail";"Имя пользователя";"Контактный телефон";"Адрес";"Статус заказа";"Способ доставки";"Стоимость доставки";"Оплата";"Комментарий менеджера";"Комментарий клиента";';
        }
        $opFieldsM = new Models_OpFieldsOrder('get');
        $_SESSION['csvOpOrder'] = array();
        $fields = $opFieldsM->get();
        if (!empty($fields) && is_array($fields)) {
            foreach ($fields as $id => $value) {
                $_SESSION['csvOpOrder'][] = $id;
                $csvText .= '"'.$value['name'].'";';
            }
        }
        // конец заголовков
        $csvText .= "\n";

        Storage::$noCache = true;
        $page = 1;
        // получаем максимальное количество заказов, если выгрузка всего ассортимента
        $count = 0;
        if(empty($listOrderId)) {
            $res = DB::query('
        SELECT count(id) as count
        FROM `'.PREFIX.'order`
        ');
            if ($order = DB::fetchAssoc($res)) {
                $count = $order['count'];
            }
            $maxCountPage = ceil($count / 500);
        } else {
            $maxCountPage = ceil(count($listOrderId) / 500);
            //$listId = implode(',', $listOrderId);
        }
        for ($page = 1; $page <= $maxCountPage; $page++) {
            URL::setQueryParametr("page", $page);

            //Купон для Расширенных промокодов
            $couponExist = DB::query("SHOW TABLES LIKE '".PREFIX."oik-discount-coupon-applied'");
            $couponExist = DB::fetchAssoc($couponExist);

            //Купон для Скидочных промокодов
            $couponExistPromoCode = DB::query("SHOW TABLES LIKE '".PREFIX."promo-code-applied'");
            $couponExistPromoCode = DB::fetchAssoc($couponExistPromoCode);

            if (!empty($couponExist)) {
                $sql = 'SELECT o.*, pc.coupon FROM `'.PREFIX.'order` o LEFT JOIN `'.PREFIX.'oik-discount-coupon-applied` pc ON o.id = pc.order_id';
            } else if(!empty($couponExistPromoCode)){
                $sql = 'SELECT o.*, pc.coupon FROM `'.PREFIX.'order` o LEFT JOIN `'.PREFIX.'promo-code-applied` pc ON o.id = pc.order_id';
            } else {
                $sql = 'SELECT o.* FROM `'.PREFIX.'order` o';
            }

            if(!empty($listOrderId)) {
                $sql .= ' WHERE `id` IN ('.DB::quoteIN($listOrderId).')';
            }
            $sql .= ' ORDER BY `add_date`';
            $navigator = new Navigator($sql, $page, 500); //определяем класс
            $orders = $navigator->getRowsSql();
            if (class_exists('statusOrder')) {
                $dbQuery = DB::query('SELECT `id_status`, `status` FROM `'.PREFIX.'mg-status-order` ');
                while ($dbRes = DB::fetchArray($dbQuery)) {
                    self::$statusUser[$dbRes['id_status']] = $dbRes['status'];
                }
            }

            $ownersForSql = array();
            foreach ($orders as $row) {
                $ownersForSql[] = $row['owner'];
            }
            $ownersForSql = array_map(function ($el) {
                $el = DB::quote($el);
                return $el;
            } ,$ownersForSql);
            $ownersForSql = implode(',', $ownersForSql);

            $owners = array();
            $sql = DB::query("SELECT `id`, `email`, `name` FROM `".PREFIX."user` WHERE `id` IN (".$ownersForSql.")");
            while ($ownersData = DB::fetchAssoc($sql)) {
                $owners[$ownersData['id']]['email'] = $ownersData['email'];
                $owners[$ownersData['id']]['name'] = $ownersData['name'];
            }

            foreach ($orders as $row) {
                $row['owner_email'] = !empty($row['owner']) ? $owners[$row['owner']]['email'] : '';
                $row['owner_name'] = !empty($row['owner']) ? $owners[$row['owner']]['name'] : '';
                $csvText .= self::addOrderToCsvLine($row, $full);
            }
        }

        $csvText = substr($csvText, 0, -2); // удаляем последний символ '\n'

        $csvText = mb_convert_encoding($csvText, "WINDOWS-1251", "UTF-8");
        if(empty($listOrderId) || $exportToCSV) {
            echo $csvText;
            exit;
        } else{
            $date = date('m_d_Y_h_i_s');
            $pathDir = mg::createTempDir('data_csv',false);
            file_put_contents(SITE_DIR.$pathDir.'data_csv_'.$date.'.csv', $csvText);
            $msg = $pathDir.'data_csv_'.$date.'.csv';
        }
        return $msg;
    }

    /**
     * Добавляет пользователя в CSV выгрузку.
     * @param array $row - запись о пользователе.
     * @param bool $full - полная выгрузка
     * @return string
     */
    private function addOrderToCsvLine($row, $full=false) {
        $id = $row['id'];
        // mg::loger($row);
        $csvText = $coupon = $discount = '';
        $row['contact_email'] = '"' . str_replace("\"", "\"\"", $row['contact_email']) . '"';
        $row['id'] = '"' . str_replace("\"", "\"\"", $row['id']) . '"';
        $row['owner_email'] = '"' . str_replace("\"", "\"\"", $row['owner_email']) . '"';
        $row['owner_name'] = '"' . str_replace("\"", "\"\"", $row['owner_name']) . '"';
        $row['number'] = '"' . str_replace("\"", "\"\"", $row['number']) . '"';
        $row['address'] = '"' . str_replace("\"", "\"\"", $row['address']) . '"';
        $row['phone'] = '"' . str_replace("\"", "\"\"", $row['phone']) . '"';
        $delivery = Models_Order::getDeliveryMethod(false, $row['delivery_id']);
        $row['delivery_id'] = '"' . str_replace("\"", "\"\"", $delivery['description']) . '"';
        $row['delivery_cost'] = '"' . str_replace("\"", "\"\"", $row['delivery_cost']) . '"';
        $row['payment_id'] = '"' . str_replace("\"", "\"\"", $this->_paymentArray[$row['payment_id']]['name']) . '"';
        $statusOrder = '';
        if (!empty(self::$statusUser)) {
            $statusOrder = self::$statusUser[$row['status_id']];
        }
        if (!$statusOrder) {
            $statusArray = self::$status;
            $lang = MG::get('lang');
            $statusOrder = $lang[$statusArray[$row['status_id']]];
        }
        $row['status_id'] = '"' . str_replace("\"", "\"\"", $statusOrder) . '"';
        $row['name_buyer'] = '"' . str_replace("\"", "\"\"", $row['name_buyer']) . '"';
        $row['add_date'] = '"' . str_replace("\"", "\"\"", date('d.m.Y', strtotime($row['add_date']))).'"';
        $row['summ'] = '"' . str_replace("\"", "\"\"",  str_replace('.', ',',$row['summ'])) . '"';
        $row['comment'] = '"' . str_replace("\"", "\"\"", $row['comment']) . '"';
        $row['user_comment'] = '"' . str_replace("\"", "\"\"", $row['user_comment']) . '"';
        $row['coupon'] = '"' . str_replace("\"", "\"\"", (!empty($row['coupon']) ? urldecode($row['coupon']):'')) . '"';
        $content = unserialize(stripslashes($row['order_content']));
        foreach ($content as $order) {
            $coupon= '"' . str_replace("\"", "\"\"", (!empty($order['coupon'])&&$order['coupon']!="Не указан"? urldecode($order['coupon']):'')) . '"';
            $discount= '"' . str_replace("\"", "\"\"", (!empty($order['discount'])? $order['discount']:'')) . '"';
            if ($full) {
                $order['name'] = preg_replace('/\s+/', ' ', $order['name']); //Замена многих пробелов на один
                $code = '"' . str_replace("\"", "\"\"", $order['code']) . '"';
                $id = '"' . str_replace("\"", "\"\"", $order['id']) . '"';
                $count = '"' . str_replace("\"", "\"\"", $order['count']).'"';
                $name = '"' . str_replace("\"", "\"\"", urldecode($order['name'])).'"';
                $price = '"' . str_replace("\"", "\"\"", str_replace('.', ',',$order['price'])) . '"';
                $priceFull = '"' . str_replace("\"", "\"\"", str_replace('.', ',',$order['fulPrice'])) . '"';
                $weight = '"' . str_replace('.', ',', str_replace("\"", "\"\"", floatval($order['weight'])*floatval($order['count']))) . '"';

                $csvText .= $row['id'].";".$row['number'].";". $row['owner_name'].";". $row['owner_email'].";". $row['add_date'].";".
                  $row['summ'].";".$id.";".$code.";".$name.";".$count.";".
                  $priceFull.";".$price.";".$weight.";".$row['coupon'].";".$discount.";".
                  $row['contact_email'].";".$row['name_buyer'].";".$row['phone'].";".
                  $row['address'].";".$row['status_id'].";".
                  $row['delivery_id'] . ";" .
                  $row['delivery_cost'] . ";" .
                  $row['payment_id'] . ";".
                  $row['comment'] . ";".
                  $row['user_comment'] . ";";

                // доп поля
                $opFieldsM = new Models_OpFieldsOrder($row['id']);
                foreach ($_SESSION['csvOpOrder'] as $fieldsId) {
                    $csvText .= '"'.$opFieldsM->getHumanView($fieldsId, true).'";';
                }
                // конец строки
                $csvText .= "\n";
            }
        }
        if (!$full) {
            $csvText = $row['id'] . ";" .
              $row['number'] . ";" .
              $row['owner_name'] . ";" .
              $row['owner_email'] . ";" .
              $row['add_date'] . ";" .
              $row['contact_email'] . ";" .
              $row['name_buyer'] . ";" .
              $row['phone'] . ";" .
              $row['address'] . ";" .
              $row['summ'] . ";" .
              $row['coupon'] . ";" .
              $discount . ";".
              $row['status_id'] . ";" .
              $row['delivery_id'] . ";" .
              $row['delivery_cost'] . ";" .
              $row['payment_id'] . ";";
            // доп поля
            $opFieldsM = new Models_OpFieldsOrder($row['id']);
            foreach ($_SESSION['csvOpOrder'] as $fieldsId) {
                $csvText .= '"'.$opFieldsM->getHumanView($fieldsId, true).'";';
            }
            // конец строки
            $csvText .= "\n";
        }


        return $csvText;
    }

    /**
     * Поиск скидки, применяемой к заказу по промокоду или в рамках
     * накопительной/объемной скидки.
     * <code>
     * $params = array(
     *    'summ' => 1000, // сумма заказа
     *    'email' => 'admin@admin.ru', // почта покупателя
     *    'promocode' => 'DEFAULT-DISCONT', // код купона скидки
     *    'cumulative' => 'true', // накопительная скидка
     *    'volume' => 'true', // объемная скидка
     *    'paymentId' => 5, // ID способа
     *    'orderItems' => 'array' // массив с товарами заказа
     * );
     * $order = new Models_Order;
     * $order->getOrderDiscount($params);
     * </code>
     * @param array $params параметры заказа
     * @return array
     */
    public static function getOrderDiscount() {
        $cart = new Models_Cart();
        $plugParams = false;
        if (isset($_POST['orderPluginsData'])) {
            $plugParams = $_POST['orderPluginsData'];
            unset($_POST['orderPluginsData']);
        }
        $params = $_POST;
        if (!empty($_SESSION['cart'])) {
            $oldSessionCart = $_SESSION['cart'];
        }
        if (!empty($_SESSION['price_rate'])) {
            $oldPriceRate = $_SESSION['price_rate'];
        }

        $_SESSION['cart'] = $result = array();
        $summ = $cartKey = 0;

        if (empty($_POST['usePlugins']) || $_POST['usePlugins'] != 'false') {
            $res = DB::query("SELECT `rate` FROM `".PREFIX."payment` WHERE `id` = ".DB::quoteInt($params['paymentId']));
            if ($row = DB::fetchAssoc($res)) {
                $_SESSION['price_rate'] = $row['rate'];
                mgAddCustomPriceAction(array('Controllers_Order', 'applyRate'));
            } else {
                $_SESSION['price_rate'] = 0;
            }

            $GLOBALS['getDetailedAdminDiscount'] = 1;

            // сигнал плагинам подготовить данные
            MG::createHook('adminOrderDiscountPrepareData', '', array('pluginParams'=>$plugParams,'orderParams'=>$params));
        }

        if (!empty($params['orderItems']) && is_array($params['orderItems'])) {

            $curr = MG::getCurrencyShort();
            $catIds = $prodIds = array();
            foreach ($params['orderItems'] as $product) {
                $prodIds[] = $product['id'];
            }

            $res = DB::query("SELECT `id`, `cat_id` FROM `".PREFIX."product` WHERE `id` IN (".DB::quoteIN($prodIds).")");
            while ($row = DB::fetchAssoc($res)) {
                $catIds[$row['id']] = $row['cat_id'];
            }

            if (empty($_POST['usePlugins']) || $_POST['usePlugins'] != 'false') {
                foreach ($params['orderItems'] as $product) {
                    $_SESSION['cart'][$cartKey] = [
                      'id' => $product['id'],
                      'count' => $product['count'],
                      'property' => $product['property'],
                      'propertyReal' => $product['property'],
                      'variantId' => isset($product['variant_id'])?$product['variant_id']:0,
                      'price' => $product['fulPrice'],
                      'priceWithDiscount' => $product['fulPrice'],
                    ];
                    $cartKey++;
                }
            }
            $cartKey = 0;
            foreach ($params['orderItems'] as $product) {
                $prod = [
                  'id' => $product['id'],
                  'variantId' => isset($product['variant_id'])?$product['variant_id']:0,
                  'title' => $product['name'],
                  'price' => $product['fulPrice'],
                  'price_course' => $product['fulPrice'],
                  'code' => $product['code'],
                  'count' => $product['count'],
                  'weight' => $product['weight'],
                  'currency_iso' => $product['currency_iso'],
                  'unit' => $product['unit'],
                  'property_html' => $product['property'],
                  'keyInCart' => $cartKey,
                  'cat_id' => $catIds[$product['id']]
                ];

                $urls = explode('/', $product['property']);
                $prod['product_url'] = array_pop($urls);
                $prod['url'] = $prod['product_url'];
                $prod['category_url'] = implode('/', $urls);
                $lastPrice = $prod['price'];

                if (empty($_POST['usePlugins']) || $_POST['usePlugins'] != 'false') {
                    $priceWithCoupon = MG::roundPriceBySettings($cart->applyCoupon(isset($_SESSION['couponCode'])?$_SESSION['couponCode']:'', $prod['price'], $prod));
                    $product['price'] = MG::roundPriceBySettings($cart->customPrice(array(
                      'product' => $prod,
                      'priceWithCoupon' => $priceWithCoupon,
                    )));

                    $product['discount'] = $product['fulPrice']?(round((100 - ($product['price'] * 100) / $product['fulPrice']),2)):0;

                    $discDetails = MG::getAdminDiscountDetails();
                    $product['discDetails'] = '';
                    if ($discDetails) {
                        if (count($discDetails) > 1) {
                            $number = 1;
                        } else {
                            $number = 0;
                        }
                        if (count($discDetails) > 0) {
                            $product['discDetails'] .= 'История изменения цены:[EOL]';
                        }
                        foreach ($discDetails as $discDetail) {
                            $numb = $eol = '';
                            if ($number) {
                                $numb = $number.') ';
                                if (count($discDetails) > $number) {
                                    $eol = "[EOL]";
                                }
                                $number++;
                            }
                            if (floatval($lastPrice) != floatval($discDetail['price'])) {
                                $diff = round((floatval($discDetail['price'])-floatval($lastPrice)), 2);
                                if ($diff > 0) {
                                    $diff = MG::numberFormat($diff);
                                    $diff = '+'.$diff;
                                } else {
                                    $diff = MG::numberFormat($diff);
                                }
                            } else {
                                $diff = '';
                            }
                            $lastPrice = $discDetail['price'];
                            $product['discDetails'] .= $numb.$discDetail['text'].': '.$diff.' '.$curr.' ('.MG::numberFormat($discDetail['price']).' '.$curr.')'.$eol;
                        }
                    }
                } else {
                    $product['price'] = $prod['price'];
                    $product['discount'] = 0;
                    $product['discDetails'] = '';
                }

                $arrKey = 'p_'.$product['id'];
                if (!empty($product['variant_id'])) {
                    $arrKey .= '_'.$product['variant_id'];
                }
                if (!empty($product['property'])) {
                    $prop = str_replace(' ', '', $product['property']);
                    $arrKey .= '_'.$prop;
                }
                $result[$arrKey] = $product;

                $summ += $product['count'] * $product['price'];
                $cartKey++;
            }
        }
        if (!empty($oldSessionCart)) {
            $_SESSION['cart'] = $oldSessionCart;
        } else {
            unset($_SESSION['cart']);
        }

       
        $paymentRate = 0;
        if(MG::getSetting('enableDeliveryCur') == 'true'){
            $paymentId = $params['paymentId'];
            $sqlForPaymentRate = 'SELECT `rate` FROM `'.PREFIX.'payment` WHERE `id` = '.DB::quoteInt($paymentId);
            $sqlForPaymentRes = DB::query($sqlForPaymentRate);
            $sqlForPaymentRow = DB::fetchAssoc($sqlForPaymentRes);
            $paymentRate = $sqlForPaymentRow['rate'];
        }

        if (isset($oldPriceRate)) {
            $_SESSION['price_rate'] = $oldPriceRate;
        } else {
            unset($_SESSION['price_rate']);
        }

        $result = array(
          'summ' => $summ,
          'paymentRate' => $paymentRate,
          'orderItems' => $result
        );
        return MG::createHook(__CLASS__."_".__FUNCTION__, $result, func_get_args());
    }

    /**
     * Получить список доп. комментариев менеджеров к заказу по id
     * @param string|int $id идентификатор заказа
     * @return array|false
     */
    public static function getOrderAdminComments($id) {
        //Собираем все комменты и все ID юзеров, которые оставляли комменты
        $sql = "SELECT * FROM `".PREFIX."order_comments` WHERE `order_id` = ".DB::quote($id)." ORDER BY `created_at` ASC";
        $result = DB::query($sql);
        if (!$result) return false;

        $list = array();
        $userListId = array();
        while ($row = DB::fetchAssoc($result)) {
            $list[] = $row;
            $userListId[] = $row['user_id'];
        }

        $userListId = array_unique($userListId);

        //Находим инфу о юзерах, которые оставляли комменты
        $sql = "SELECT `id`, `email`, `name` FROM `".PREFIX."user` WHERE `id` IN (".DB::quoteIN($userListId).")";
        $result = DB::query($sql);

        $userList = array();
        while ($row = DB::fetchAssoc($result)) {
            $userList[$row['id']] = $row;
        }

        //Прикрепляем инфу о юзерах к комментам
        foreach ($list as $key => $value) {
            $list[$key]['user'] = $userList[$value['user_id']];
        }

        return $list;
    }

    /**
     * Добавляет комментарий менеджера к заказу: если это первый комментарий, то он становиться главным, если последующий, то дополнительным.
     * @param string|int $id идентификатор заказа
     * @param string $text текст комментария
     * @param string|int $user_id идентификатор пользователя
     * @return boolean
     */
    public static function addAdminCommentOrder($id, $text, $user_id) {

        $sql = "INSERT INTO `".PREFIX."order_comments`(`order_id`, `user_id`, `text`) VALUES (".DB::quote($id).",".DB::quote($user_id).",".DB::quote($text).")";
        $result = DB::query($sql);
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Удаляет доп. комментарий менеджера из заказа
     * @param string|int $id идентификатор комментария
     * @return boolean
     */
    public static function deleteAdminCommentOrder($id) {
        $sql = "DELETE FROM `".PREFIX."order_comments` WHERE `id` = ".DB::quote($id);
        $result = DB::query($sql);
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Возвращает корзину заказа с округленной до копеек стоимостью товаров
     * Исправляет расхождение суммы товаров с суммой заказа, если оно возникло в результате окргления
     */
    public function getCorrectOrderContent($order) {
        $orderContent = unserialize(stripslashes($order['order_content']));
        $orderSumm = $order['summ'];
        $productsSumm = 0;
        foreach ($orderContent as $productKey => $product) {
            $product['price'] = round($product['price'], 2);
            $orderContent[$productKey] = $product;
            $productsSumm += $product['price'] * $product['count'];
        }

        // Если после округления стоимости товаров разницы между суммой товаров в заказе
        // и общей суммой заказа не появилось, то просто возвращаем content
        $summDifference = round($orderSumm - $productsSumm, 2);
        if (!$summDifference) {
            return $orderContent;
        }

        // Если есть разница в суммах, то прибавляем её к тому товару,
        // на количество которого мжоно нацело поделить разницу (умножается на 100 т. к. в копейках)
        foreach ($orderContent as $productKey => $product) {
            if (!(($summDifference * 100) % $product['count']) && ($product['price'] + $summDifference) > 0) {
                $product['price'] += $summDifference / $product['count'];
                $orderContent[$productKey] = $product;
                return $orderContent;
            }
        }

        // Если не нашёлся такой товар, к которому можно добавить разницу из-за его количества в заказе,
        // то берём первую подходящую позицию и меняем стоимость только у одной единицы товара.
        foreach ($orderContent as $productKey => $product) {
            if (($product['price'] + $summDifference > 0) && $product['count'] > 1) {
                $product['count'] -= 1;
                $orderContent[$productKey] = $product;
                $product['price'] += $summDifference;
                $product['count'] = 1;
                $orderContent[] = $product;
                return $orderContent;
            }
        }

        return $orderContent;
    }

    public static function getLastOrderId() {
        $lastOrderId = 0;
        $lastOrderIdSql = 'SELECT `id` '.
            'FROM `'.PREFIX.'order` '.
            'ORDER BY `id` DESC '.
            'LIMIT 1';
        $lastOrderIdResult = DB::query($lastOrderIdSql);
        if ($lastOrderIdRow = DB::fetchAssoc($lastOrderIdResult)) {
            $lastOrderId = $lastOrderIdRow['id'];
        }
        return $lastOrderId;
    }

    public static function orderIdToPayHash($orderId) {
        $payHashSalt = 'bymg7zxdv4qf3skht6eruc2an985p1';
        $orderIdString = preg_replace('/[^\d]/', '', $orderId.'');
        $orderIdArray = str_split($orderIdString);

        $payHash = '';
        foreach ($orderIdArray as $orderIdChar) {
            $payHash .= $payHashSalt[rand(0, strlen($payHashSalt) - 1)];
            $index = intval($orderIdChar);
            $newIndex = $index + 10 * rand(0, 2);
            $payHash .= $payHashSalt[$newIndex];
        }

        return $payHash;
    }

    public static function payHashToOrderId($payHash) {
        $payHashSalt = 'bymg7zxdv4qf3skht6eruc2an985p1';
        $payHashSaltArray = array_flip(str_split($payHashSalt));

        $orderId = '';
        $payHashLength = strlen($payHash);
        if (($payHashLength % 2) !== 0) {
            return false;
        }
        for ($i = 0; $i < $payHashLength; $i++) {
            if ($i % 2 === 0) {
                continue;
            }
            if (!isset($payHash[$i])) {
                return false;
            }
            $payHashChar = $payHash[$i];
            $messedOrderIdChar = $payHashSaltArray[$payHashChar];
            $orderIdChar = intval($messedOrderIdChar) % 10;
            $orderId .= $orderIdChar;
        }
        $result = intval($orderId);
        return $result;
    }

    private static function isOrderPaymentApproved($orderId) {
        $result = false;
        $approvePaymentSql = 'SELECT `approve_payment` '.
            'FROM `'.PREFIX.'order` '.
            'WHERE `id` = '.DB::quoteInt($orderId);
        $approvePaymentResult = DB::query($approvePaymentSql);
        if ($approvePaymentRow = DB::fetchAssoc($approvePaymentResult)) {
            $result = !!$approvePaymentRow['approve_payment'];
        }
        return $result;
    }

    public function checkOrderReturn($orderId) {
        $orders = $this->getOrder('`id` = '.DB::quoteInt($orderId));
        if (empty($orders[$orderId])) {
            return true;
        }
        $order = $orders[$orderId];
        $currentOrderStatus = intval($order['status_id']);
        if ($currentOrderStatus !== 4) {
            return true;
        }
        $orderContent = unserialize(stripslashes($order['order_content']));
        foreach ($orderContent as $product) {
            $productId = intval($product['id']);
            $variantId = intval($product['variant_id']);
            $count = floatval($product['count']);
            if (MG::enabledStorage()) {
                if (empty($product['storage_id'])) {
                    continue;
                }
                $productModel = new Models_Product();
                $storagesData = $product['storage_id'];
                foreach ($storagesData as $storageInfo) {
                    $storageInfoArray = explode('_', $storageInfo);
                    $countFromStorage = array_pop($storageInfoArray);
                    $storageId = implode('_', $storageInfoArray);
                    $currentProductStock = $productModel->getProductStorageCount($storageId, $productId, $variantId);
                    if ($currentProductStock >= 0 && $currentProductStock < $countFromStorage) {
                        return false;
                    }
                }
            } else {
                $currentProductStockSql = 'SELECT `count` '.
                    'FROM `'.PREFIX.'product` '.
                    'WHERE `id` = '.DB::quoteInt($productId);
                if ($variantId) {
                    $currentProductStockSql = 'SELECT `count` '.
                        'FROM `'.PREFIX.'product_variant` '.
                        'WHERE `id` = '.DB::quoteInt($variantId).' '.
                            'AND `product_id` = '.DB::quoteInt($productId);
                }
                $currentProductStockResult = DB::query($currentProductStockSql);
                $currentProductStockRow = DB::fetchAssoc($currentProductStockResult);
                if (!$currentProductStockRow) {
                    continue;
                }
                $currentProductStock = floatval($currentProductStockRow['count']);
                if ($currentProductStock >= 0 && $currentProductStock < $count) {
                    return false;
                }
            }
        }
        return true;
    }
}
