<?php 
/**
 * Класс CheckEngine - Предназначен для тестированя движка и автодиагностити системы
 *
 * * Реализован в виде синглтона, что исключает его дублирование.
 *
 * @author Шевченко Александр Станиславович
 * @package moguta.cms
 * @subpackage Libraries
 */

class CheckEngine{
    // Здесь находятся все дефолтные настройки
    public static $settingDef = [
        'sitename' => 'localhost', 
        'adminEmail' => '', 
        'templateName' => 'moguta', 
        'countСatalogProduct' => '6', 
        'currency' => 'руб.', 
        'staticMenu' => 'true', 
        'orderMessage' => 'Оформлен заказ № #ORDER# на сайте #SITE#', 
        'downtime' => 'false', 
        'downtime1C' => 'false', 
        'title' => ' Лучший магазин | Moguta.CMS', 
        'countPrintRowsProduct' => '20', 
        'languageLocale' => 'ru_RU', 
        'countPrintRowsPage' => '10', 
        'themeColor' => 'green-theme', 
        'themeBackground' => 'bg_7.png', 
        'countPrintRowsOrder' => '20', 
        'countPrintRowsUser' => '30', 
        'licenceKey' => '', 
        'mainPageIsCatalog' => 'true', 
        'countNewProduct' => '5', 
        'countRecomProduct' => '5', 
        'countSaleProduct' => '5', 
        'actionInCatalog' => 'true', 
        'printProdNullRem' => 'true', 
        'printRemInfo' => 'true', 
        'printCount' => 'true',
        'printCode' => 'true',
        'printUnits' => 'true',
        'printBuy' => 'true',
        'printCost' => 'true',
        'heightPreview' => '348', 
        'widthPreview' => '540', 
        'heightSmallPreview' => '200', 
        'widthSmallPreview' => '300', 
        'categoryIconHeight' => '100', 
        'categoryIconWidth' => '100', 
        'waterMark' => 'false', 
        'waterMarkPosition' => 'center', 
        'widgetCode' => '<!-- В это поле необходимо прописать код счетчика посещаемости Вашего сайта. Например, Яндекс.Метрика или Google analytics -->', 
        'metaConfirmation' => '<!-- В это поле необходимо прописать код подтверждения для Вашего сайта. Например, Яндекс.Метрика или Google analytics -->',
        'noReplyEmail' => 'noreply@sitename.ru',
        'smtp' => 'false', 
        'smtpHost' => '', 
        'smtpLogin' => '', 
        'smtpPass' => '', 
        'smtpPort' => '', 
        'shopPhone' => '8 (555) 555-55-55', 
        'shopAddress' => 'г. Москва, ул. Тверская, 1.',
        'shopName' => 'Интернет-магазин', 
        'shopLogo' => '/uploads/logo.svg', 
        'phoneMask' => '+7 (###) ### ##-#', 
        'printStrProp' => 'false', 
        'noneSupportOldTemplate' => 'false', 
        'printCompareButton' => 'true', 
        'currencyShopIso' => 'RUR', 
        'cacheObject' => 'true', 
        'cacheMode' => 'FILE', 
        'cacheTime' => '86400', 
        'cacheHost' => '', 
        'cachePort' => '', 
        'priceFormat' => '1 234,56', 
        'horizontMenu' => 'false', 
        'buttonBuyName' => 'Купить', 
        'buttonCompareName' => 'Сравнить', 
        'randomProdBlock' => 'false', 
        'buttonMoreName' => 'Подробнее', 
        'compareCategory' => 'true', 
        'colorScheme' => '1', 
        'useCaptcha' => '', 
        'autoRegister' => 'true', 
        'printFilterResult' => 'true', 
        'dateActivateKey' => '0000-00-00 00:00:00', 
        'propertyOrder' => 'a:20:{s:7:\"nameyur\";s:6:\"ООО\";s:6:\"adress\";s:48:\"г.Москва ул. Тверска', 
        'enabledSiteEditor' => 'false', 
        'lockAuthorization' => 'false', 
        'orderNumber' => 'true', 
        'popupCart' => 'true', 
        'catalogIndex' => 'false', 
        'productInSubcat' => 'true', 
        'copyrightMoguta' => 'true', 
        'copyrightMogutaLink' => '',
        'picturesCategory' => 'true', 
        'noImageStub' => '/uploads/no-img.jpg',
        'backgroundSite' => '', 
        'backgroundColorSite' => '', 
        'backgroundTextureSite' => '', 
        'backgroundSiteLikeTexture' => 'false', 
        'fontSite' => '', 
        'cacheCssJs' => 'false', 
        'categoryImgWidth' => '20', 
        'categoryImgHeight' => '20', 
        'propImgWidth' => '5', 
        'propImgHeight' => '5', 
        'favicon' => 'favicon.ico', 
        'connectZoom' => 'true', 
        'filterSort' => 'price_course|asc', 
        'shortLink' => 'false', 
        'imageResizeType' => 'PROPORTIONAL', 
        'imageSaveQuality' => '75', 
        'duplicateDesc' => 'false', 
        'excludeUrl' => '', 
        'autoGeneration' => 'false', 
        'generateEvery' => '2', 
        'consentData' => 'true', 
        'showCountInCat' => 'true', 
        'nameOfLinkyml' => 'getyml', 
        'clearCatalog1C' => 'false', 
        'fileLimit1C' => '10000000', 
        'ordersPerTransfer1c' => '1000', 
        'weightPropertyName1c' => 'Вес', 
        'multiplicityPropertyName1c' => 'Кратность', 
        'oldPriceName1c' => '', 
        'retailPriceName1c' => '', 
        'showSortFieldAdmin' => 'false', 
        'filterSortVariant' => 'price_course|asc', 
        'showVariantNull' => 'true', 
        'confirmRegistration' => 'true', 
        'cachePrefix' => '', 
        'usePhoneMask' => 'true', 
        'smtpSsl' => 'false', 
        'sessionToDB' => 'false', 
        'sessionLifeTime' => '1440', 
        'sessionAutoUpdate' => 'true', 
        'showCodeInCatalog' => 'false', 
        'openGraph' => 'true', 
        'openGraphLogoPath' => '', 
        'dublinCore' => 'true', 
        'printSameProdNullRem' => 'true', 
        'landingName' => 'lp-moguta', 
        'colorSchemeLanding' => 'none', 
        'printQuantityInMini' => 'false', 
        'printCurrencySelector' => 'false', 
        'interface' => 'a:7:{s:9:\"colorMain\";s:7:\"#2773eb\";s:9:\"colorLink\";s:7:\"#1585cf\";s:9:\"colorSave\";s:7:\"#4caf50\";s:11:\"colorBorder\";s:7:\"#e6e6e6\";s:14:\"colorSecondary\";s:7:\"#ebebeb\";s:8:\"adminBar\";s:7:\"#f0f1f3\";s:17:\"adminBarFontColor\";s:7:\"#000000\";}', 
        'filterCountProp' => '3', 
        'filterMode' => 'true', 
        'filterCountShow' => '5', 
        'filterSubcategory' => 'false', 
        'printVariantsInMini' => 'false', 
        'useReCaptcha' => 'false', 
        'invisibleReCaptcha' => 'false', 
        'reCaptchaKey' => '', 
        'reCaptchaSecret' => '', 
        'timeWork' => '09:00 - 19:00', 
        'useSeoRewrites' => 'false', 
        'useSeoRedirects' => 'false', 
        'showMainImgVar' => 'false', 
        'loginAttempt' => '5', 
        'prefixOrder' => 'M-010', 
        'captchaOrder' => 'false', 
        'deliveryZero' => 'true', 
        'outputMargin' => 'true', 
        'prefixCode' => 'CN', 
        'maxUploadImgWidth' => '1500', 
        'maxUploadImgHeight' => '1500', 
        'searchType' => 'like', 
        'searchSphinxHost' => 'localhost', 
        'searchSphinxPort' => '9312',
        'registrationMethod' => 'email',
        'checkAdminIp' => 'false', 
        'printSeo' => 'all', 
        'catalogProp' => '0', 
        'printAgreement' => 'true', 
        'currencyShort' => 'a:6:{s:3:\"RUR\";s:7:\"руб.\";s:3:\"UAH\";s:7:\"грн.\";s:3:\"USD\";s:1:\"$\";s:3:\"EUR\";s:3:\"€\";s:3:\"KZT\";s:10:\"тенге\";s:3:\"UZS\";s:6:\"сум\";}', 
        'useElectroLink' => 'false', 
        'useMultiplicity' => 'false', 
        'currencyActive' => 'a:5:{i:0;s:3:\"UAH\";i:1;s:3:\"USD\";i:2;s:3:\"EUR\";i:3;s:3:\"KZT\";i:4;s:3:\"UZS\";}', 
        'closeSite' => 'false', 
        'catalogPreCalcProduct' => 'old', 
        'printSpecFilterBlock' => 'true',
        'printPriceFilterBlock' => 'true',
        'printTypeProduct' => 'false',
        'statusChangeMail1c' => 'false',
        'sortFieldsCatalog' => 'a:13:{s:15:\"price_course|-1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_11\";s:6:\"enable\";s:4:\"true\";}s:14:\"price_course|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_12\";s:6:\"enable\";s:4:\"true\";}s:4:\"id|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_13\";s:6:\"enable\";s:4:\"true\";}s:11:\"count_buy|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_14\";s:6:\"enable\";s:4:\"true\";}s:11:\"recommend|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_15\";s:6:\"enable\";s:4:\"true\";}s:5:\"new|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_16\";s:6:\"enable\";s:4:\"true\";}s:11:\"old_price|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_17\";s:6:\"enable\";s:4:\"true\";}s:7:\"sort|-1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_10\";s:6:\"enable\";s:4:\"true\";}s:6:\"sort|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_22\";s:6:\"enable\";s:4:\"true\";}s:7:\"count|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_18\";s:6:\"enable\";s:4:\"true\";}s:8:\"count|-1\";a:2:{s:4:\"lang\";s:21:\"SORT_INCREASING_COUNT\";s:6:\"enable\";s:4:\"true\";}s:8:\"title|-1\";a:2:{s:4:\"lang\";s:10:\"SORT_NAMES\";s:6:\"enable\";s:4:\"true\";}s:7:\"title|1\";a:2:{s:4:\"lang\";s:14:\"SORT_REV_NAMES\";s:6:\"enable\";s:4:\"true\";}}',  
        'disabledPropFilter' => 'false', 
        'enableDeliveryCur' => 'false', 
        'addDateToImg' => 'true', 
        'variantToSize1c' => 'false', 
        'skipRootCat1C' => 'false',
        'sphinxLimit' => '20', 
        'filterCatalogMain' => 'false', 
        'importColorSize' => 'size', 
        'sizeName1c' => 'Размер', 
        'colorName1c' => 'Цвет', 
        'sizeMapMod' => 'COLOR', 
        'modParamInVarName' => 'true', 
        'orderOwners' => 'false', 
        'ownerRotation' => 'false', 
        'ownerRotationCurrent' => '', 
        'ownerList' => '', 
        'ownerRemember' => 'false', 
        'ownerRememberPhone' => 'false', 
        'ownerRememberEmail' => 'false', 
        'ownerRememberDays' => '14', 
        'convertCountToHR' => '2:последний товар,5:скоро закончится,15:мало,100:много', 
        'blockEntity' => 'false', 
        'useFavorites' => 'true', 
        'countPrintRowsBrand' => '10', 
        'varHashProduct' => 'true', 
        'useSearchEngineInfo' => 'false', 
        'timezone' => 'noChange', 
        'recalcWholesale' => 'true', 
        'recalcForeignCurrencyOldPrice' => 'true',
        'printOneColor' => 'false', 
        'updateStringProp1C' => 'false', 
        'weightUnit1C' => 'kg', 
        'writeLog1C' => 'false', 
        'writeFiles1C' => 'false', 
        'writeFullName1C' => 'false', 
        'activityCategory1C' => 'true', 
        'notUpdate1C' => 'a:13:{s:8:\"1c_title\";s:4:\"true\";s:7:\"1c_code\";s:4:\"true\";s:6:\"1c_url\";s:4:\"true\";s:9:\"1c_weight\";s:4:\"true\";s:8:\"1c_count\";s:4:\"true\";s:14:\"1c_description\";s:4:\"true\";s:12:\"1c_image_url\";s:4:\"true\";s:13:\"1c_meta_title\";s:4:\"true\";s:16:\"1c_meta_keywords\";s:4:\"true\";s:11:\"1c_activity\";s:4:\"true\";s:12:\"1c_old_price\";s:4:\"true\";s:15:\"1c_multiplicity\";s:5:\"false\";s:9:\"1c_cat_id\";s:4:\"true\";}', 
        'notUpdateCat1C' => 'a:4:{s:11:\"cat1c_title\";s:4:\"true\";s:9:\"cat1c_url\";s:4:\"true\";s:12:\"cat1c_parent\";s:4:\"true\";s:18:\"cat1c_html_content\";s:5:\"false\";}',
        'listMatch1C' => 'a:7:{i:0;s:27:\"не подтвержден\";i:1;s:27:\"ожидает оплаты\";i:2;s:14:\"оплачен\";i:3;s:19:\"в доставке\";i:4;s:14:\"отменен\";i:5;s:16:\"выполнен\";i:6;s:21:\"в обработке\";}', 
        'product404' => 'false', 
        'product404Sitemap' => 'false', 
        'productFilterPriceSliderStep' => '10', 
        'catalogColumns' => 'a:6:{i:0;s:6:\"number\";i:1;s:8:\"category\";i:2;s:3:\"img\";i:3;s:5:\"title\";i:4;s:5:\"price\";i:5;s:5:\"count\";}', 
        'orderColumns' => 'a:9:{i:0;s:2:\"id\";i:1;s:6:\"number\";i:2;s:4:\"date\";i:3;s:3:\"fio\";i:4;s:5:\"email\";i:5;s:4:\"summ\";i:6;s:5:\"deliv\";i:7;s:7:\"payment\";i:8;s:6:\"status\";}', 
        'userColumns' => 'a:5:{i:0;s:5:\"email\";i:1;s:6:\"status\";i:2;s:5:\"group\";i:3;s:8:\"register\";i:4;s:8:\"personal\";}', 
        'useNameParts' => 'false', 
        'searchInDefaultLang' => 'true', 
        'backupBeforeUpdate' => 'true',
        'mailsBlackList' => '',
        'orderOnlyForConfirmedUsers' => 'false',
        'rememberLogins' => 'true', 
        'rememberLoginsDays' => '180', 
        'loginBlockTime' => '15', 
        'printMultiLangSelector' => 'true', 
        'imageResizeRetina' => 'false', 
        'timeWorkDays' => 'Пн-Пт', 
        'logger' => 'false', 
        'hitsFlag' => 'false', 
        'introFlag' => '[]', 
        'useAbsolutePath' => 'true', 
        'confirmRegistrationEmail' => 'true', 
        'confirmRegistrationPhone' => 'false', 
        'storages_settings' => 'a:3:{s:12:\"writeOffProc\";s:1:\"1\";s:28:\"storagesAlgorithmWithoutMain\";s:0:\"\";s:11:\"mainStorage\";s:0:\"\";}', 
        'catalog_meta_title' => '{titeCategory}', 
        'catalog_meta_description' => '{cat_desc}', 
        'catalog_meta_keywords' => '{meta_keywords}', 
        'product_meta_title' => '{title}', 
        'product_meta_description' => '{title} за {price} {currency} купить. {description}', 
        'product_meta_keywords' => '{meta_keywords}', 
        'page_meta_title' => '{title}', 
        'page_meta_description' => '{html_content}', 
        'page_meta_keywords' => '{meta_keywords}', 
        'useWebpImg' => 'false', 
        'currencyRate' => 'a:6:{s:3:\"RUR\";d:1;s:3:\"UAH\";d:1;s:3:\"USD\";d:1;s:3:\"EUR\";d:1;s:3:\"KZT\";d:1;s:3:\"UZS\";d:1;}', 
        '1c_unit_item' => 'Килограмм:кг,Метр:м,Квадратный метр:м2,Кубический метр:м3,Штука:шт.,Литр:л',
        'requiredFields' => 'true',
        'productSitemapLocale' => 'true',
        'sitetheme' => '',
        'useOneStorage' => 'false',
        'ordersPerPageForUser' => 10,
        'isFirstStartCloud' => '1',
        'useTemplatePlugins' => '1',
        'thumbsProduct' => 'false', 
        'exifRotate' => 'false',
        'confirmRegistrationPhoneType' => 'sms', 
        'productsOutOfStockToEnd' => 'false',
        'oldPricedOnSalePageOnly' => 'false',
        'metaLangContent' => 'zxx',
        'genMetaLang' => 'true',
        'noviceListProgressBar' => 'true',
        'useSeoCanonical' => 'false',
    ];
    
    //Те участки настроек, которые не надо проверять
    public static $notUsedSettings = [
        'sitename',
        'adminEmail', 
        'templateName',
        'noReplyEmail',
        'propertyOrder',
        'currentVersion',
        'timeLastUpdata',
        'title',
        'licenceKey',
        'dateActivateKey',
        'timeWork',
        'hitsFlag',
        'introFlag',
        'useDefaultSettings',
        'backupSettingsFile',
        'sitetheme',
        'siteThemeVariants'
    ];

    private function __construct(){
        self::getInstance();
    }

    /**
     * Возвращает единственный экземпляр данного класса.
     * @access private
     * @return object объект класса CheckEngine.
     */
    static public function getInstance() {
        if(is_null(self::$_instance)) {
          self::$_instance = new self;
        }
        return self::$_instance;
    }

    public static function checkEngine($allCheck = false){

        $timeDiff = round((time()-MG::getSetting('checkEngine'))/60);
        //Базовая проверка, которая отображается в шапке сайта
        if($timeDiff > 1 || $allCheck){
            $check = self::baseCheck();
        }
        //Полная проверка
        if($allCheck){
            $check = array_merge($check, self::additionalCheck());
        }

        //Что возвращаем (в зависимосьт от типа проверки)
        if($allCheck){
            return ['checkEngine' => $check];
        }else{
            return $_SESSION['engineErroros'];
        }
    }
    
    // Проверка основных настроек движка и сервера
    public static function baseCheck(){
        $_SESSION['engineErroros'] = '';
        $html = '';
        // Ошибки для основной проверки
        $errors = [
            'ZIP' => 1,
            'GD' => 1,
            'xmlwriter' => 1,
            'xmlreader' => 1,
            'curl' => 1,
            'PHP' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'uploads' =>intval(substr(sprintf('%o', fileperms('uploads')), -4)),
            'mg-cache' => intval(substr(sprintf('%o', fileperms('mg-cache')), -4)),
            'template-cache' => intval(substr(sprintf('%o', fileperms('mg-templates'.DS.MG::getSetting('templateName').DS.'cache')), -4)),
            'create-dir' => 1,
            'chmod' => 1,
            'file-write' => 1,
            'file-chmod' => 1,
            'file-del' => 1,
            'dir-del' => 1,
            'imagewebp' => 1
        ]; 

        //Проверка на ZIP-архив 
        if(!extension_loaded('zip')){
            $errors['ZIP'] = 0;
            $html .= ' ZIP,';
        }
        //Библиотке GD
        if(!extension_loaded('gd')){
            $errors['GD'] = 0;
            $html .= ' библиотека GD,';
        }
        //xmlreader
        if(!extension_loaded('xmlreader')){
            $errors['xmlwriter'] = 0;
            $html .= ' xmlreader,';
        }
        //xmlwriter
        if(!extension_loaded('xmlwriter')){
            $errors['xmlwriter'] = 0;
            $html .= ' xmlwriter,';
        }
        //curl
        if(!extension_loaded('curl')){
            $errors['curl'] = 0;
            $html .= ' curl,';
        }
        //PHP
        if(floatval(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION) < 5.6 || floatval(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION) > 8.3){
            $errors['PHP'] = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
            $html .= ' Версия PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.',';
        }
        //Права на запись
        // uploads
        $fileRights = intval(substr(sprintf('%o', fileperms('uploads')), -4));
        if($fileRights < 755){
            $errors['uploads'] = $fileRights;
            $html .= ' права на папку uploads-'.$fileRights.',';
        }
        //mg-cache
        if(is_dir('mg-cache')){
            $fileRights = intval(substr(sprintf('%o', fileperms('mg-cache')), -4));
            if($fileRights < 700){
                $errors['mg-cache'] = $fileRights;
                $html .= ' права на папку mg-cache-'.$fileRights.',';
            }
        }
        //КЭШ шаблона 
        if(is_dir('mg-templates'.DS.MG::getSetting('templateName').DS.'cache')){
            $fileRights = intval(substr(sprintf('%o', fileperms('mg-templates'.DS.MG::getSetting('templateName').DS.'cache')), -4));
            if($fileRights < 755){
                $errors['template-cache'] = $fileRights;
                $html .= ' права на папку cache шаблона-'.$fileRights.',';
            }
        }
       
        $current_dir = SITE_DIR;
        // Права при создании файлов и директорий
        if(!@mkdir($current_dir.DS."test", 0777)){
            $errors['create-dir'] = 0;
            $html .= ' не создаются директории,';
        }
        elseif(!@chmod($current_dir.DS."test", 0777)){
            $errors['chmod'] = 0;
            $html .= ' не меняются права у директорий,';
        }elseif(!$tf=@fopen($current_dir.DS."test".DS."test.txt", 'w')){
            $errors['file-write'] = 0;
            $html .= ' не создаются файлы в движке,';
        }else{
            @fclose($tf);
        }

        if(!@chmod($current_dir.DS."test".DS."test.txt", 0777)){
            $errors['file-chmod'] = 0;
            $html .= ' не меняются права на файл,';
        }elseif(!@unlink($current_dir.DS."test".DS."test.txt")){
            $errors['file-del'] = 0;
            $html .= ' не удаляются файлы,';
        }elseif(!@rmdir($current_dir.DS."test")){
            $errors['dir-del'] = 0;
            $html .= ' не удаляются директории,';
        }

        if(!function_exists('imagewebp')){
            $errors['imagewebp'] = 0;
        }
        MG::setOption('checkEngine', time());
        $_SESSION['engineErroros'] = substr($html, 0, -1);

        return $errors;
    }
    // Метод для доп проверок. Вызывается только при полной диагностике движка
    public static function additionalCheck(){
        // Массив полных проверко
        $checkArray = [
            'file-create' => '',
        ];
        //Права на файл по умолчанию, который создается на сервере
        $tempFileName = 'temp_'.time().'.txt';
        file_put_contents($tempFileName, '');
        $checkArray['file-create'] = intval(substr(sprintf('%o', fileperms($tempFileName)), -4));
        @unlink($tempFileName);
        return $checkArray;
        
    }
    // Метод на проверку всех настроек движка
    public static function getSettingsFromBd(){

        $res = DB::query("SELECT `option`, `value`, `name` FROM `".PREFIX."setting`");
        while($row = DB::fetchAssoc($res)) {
            if(!in_array($row['option'], self::$notUsedSettings)){
                $settings[$row['option']] = [
                    'value' => $row['value'],
                    'name' => $row['name']
                ];
            }
        }
        
        $settingsDefault = self::$settingDef;

        $resArray = array();
        $lang = MG::get('lang');
        $html = '';
        $html .= '<div class ="row sett-line js-settline-toggle server-info__row" >';
        $html .= '<table class="show-engine-settings-table">';
        $html .= '<thead><tr><td>Настройка</td><td>Установленное значение</td><td>Значение по умолчанию</td><td>Ключ</td></tr></thead>';
        $html .= '<tbody>';
        //MG::loger($settings);
        foreach($settings as $key => $setting){
            if(!isset($settingsDefault[$key])){
                continue;
                $html .='<tr>';
                $html .= '<td>'.(($setting['name'] !='' && isset($lang[$setting['name']]))?$lang[$setting['name']]:'-').'</td>';
                $html .= '<td> Не дефолтная настройка </td>';
                $html .= '<td> - </td>';
                $html .= '<td class="statusId"><span class="badge dont-paid" style=""> '.$key.' </span></td>';
                $resArray[$key] = '-';
            }
            if(stripslashes($setting['value']) != stripslashes($settingsDefault[$key])){
                $html .='<tr>';
                $html .= '<td><span>'.(($setting['name'] !='' && isset($lang[$setting['name']]))?$lang[$setting['name']]:'-').'</span></td>';
                $html .= '<td class="engine-setting-td"><span>'.$setting['value'].'</span></td>';
                $html .= '<td class="engine-setting-td">'.$settingsDefault[$key].'</td>';
                $html .= '<td class="statusId"><span class="badge dont-paid" style=""> '.$key.' </span></td>';
                $resArray[$key] = [
                    'change' => 'false',
                    'value' => $setting['value'],
                    'def_value' => $settingsDefault[$key],
                ];
            }else{
                continue;
            }
            $html .='</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }
    //Здесь происходит сверка всех дефолтных настроек с настройками в дампе БД
    public static function getDefaultSettings()
    {
        $dbDump = file_get_contents('install/dbDump.php');
        preg_match_all('/(\"INSERT IGNORE INTO `"\.\$prefix\."setting` \(`option`, `value`, `active`, `name`\) VALUES)(.+?)\"\,/s', $dbDump, $matches);
        $settingFromDumpFile = array();
        foreach($matches[2] as $match){
            //preg_match_all('/\((.+?)\),/s', $match, $settingLine);
            preg_match_all('/\((.+?)\)[,|\n]|\((.+?)\)$/s', $match, $settingLine);
            foreach($settingLine[2] as $setting){
                if(!empty($setting)){
                    array_push($settingLine[1], $setting);
                }
            }
            foreach($settingLine[1] as $setting){
                if(empty($setting)){
                    continue;
                }
                $settingArr = explode(',', $setting);
                $settingArr[1] = mb_substr($settingArr[1], 1);
                $firstCharset = mb_substr($settingArr[1], 0, 1);
                if($firstCharset === '"' || $firstCharset === "'"){
                    $settingArr[1] = mb_substr($settingArr[1], 1);
                }
                $settingArr[1] = mb_substr($settingArr[1], 0, -1);
                $settingArr[0] = str_replace(["'", '"'], '', $settingArr[0]);
                $settingFromDumpFile[$settingArr[0]] = $settingArr[1];
            }
        }
        $error = array();
        foreach($settingFromDumpFile as $key => $val){
            if(!isset(self::$settingDef[$key])){
                $error[] = $key;
            }
        }
        
        foreach($error as $key => $value){
            if(in_array($value, self::$notUsedSettings)){
                unset($error[$key]);
            }
        }

        if(empty($error)){
            return true;
        }else{
            return $error;
        }
    }

    public static function resetSettings(){
        
        return true;
    }

}
