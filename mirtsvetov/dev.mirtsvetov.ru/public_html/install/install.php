<?php
// нужно для прекращения работы скрипта при проверке на работоспособность модуля mod_rewrite
if(isset($_GET['test'])) {
  echo '1';
  exit;
}

error_reporting(1);	

define('SITE', (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off'  ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].URL::getCutSection());
$prefix = empty($_REQUEST['prefix']) ? 'mg_' : $_REQUEST['prefix'];
$_SESSION = array();

if ($_REQUEST['siteName']) {
  $siteName = clearData($_REQUEST['siteName']);
} else {
  $siteName = $_SERVER['SERVER_NAME'];
}

$aLogin = 'Администратор';
$aPass = clearData($_REQUEST['pass']);
$adminEmail = clearData($_REQUEST['email']);
if ($_REQUEST['id']) {
  setcookie("installerMoguta", $_REQUEST['id'], time()+3600*24*30,'/');
  $idInstaller = $_REQUEST['id'];
}
if ($_REQUEST['step1']) {
  $step = 0;
  if ('ok'==$_REQUEST['agree']) {
    $step = 1;
  }
  
  if($checkLibs = libExists()){
    $libError = true;
	$msg .= '<div class="wrapper-error">';
    $msg .= '<div class="error-system-install">Установка системы невозможна!</div>';
    foreach ($checkLibs as $message){
        $msg .= '<span class="error-lib">'.$message.'</span><br>';
    }
    $msg .= '</div>';
  }
  
  if($_SERVER['HTTP_HOST']=='localhost'){
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $nameDB = 'BASE_NAME';
  };
	
}

if ($_REQUEST['step2']) {
  
  if(!empty($_REQUEST['ajax'])){
    
    if(downloadDemoData($_REQUEST['part'])){
      return extractZip('uploads.zip');
    }
    
    return false;
  }
  
  $host = clearData($_REQUEST['host']);
  $user = clearData($_REQUEST['user']);
  $password = clearData($_REQUEST['password']);
  $nameDB = clearData($_REQUEST['nameDB']);
  $engineType = clearData($_REQUEST['engineType']);
  $step = 2;	
  
  $hostAndPort = explode(':', $host);
  $port = null;
  $host = clearData($_REQUEST['host']);
  if(!empty($hostAndPort[1])){
    $port = $hostAndPort[1];
    $host = $hostAndPort[0];
  }

  if(!empty($engineType)){
    $checkedTest = 'checked=checked'; //отметка "по умолчанию" типа магазина -тестовый
  }
  //Тестирование введенных пользователем параметров.
    try {
    $connection = mysqli_connect($host, $user, $password, $nameDB, $port);
   
    if (!$connection) {
      throw new Exception('<span class="no-bd">Невозможно установить соединение.</span>');
    }

    if (!mysqli_select_db($connection,$nameDB)) {
      throw new Exception('<span class="error-db">Ошибка! Невозможно выбрать указанную базу.'. mysqli_error($connection).'</span>');
    }
  } catch (Exception $e) {
    //Выведет либо сообщение об ошибке подключения, либо об ошибке выбора.
    $msg = '<div class="msgError">'.$e->getMessage().'</div>';
	$step = 1;

  }
}

if ($_REQUEST['step3']) {
  $step = 3;
  
  $host = clearData($_REQUEST['host']);
  $user = clearData($_REQUEST['user']);
  $password = clearData($_REQUEST['password']);
  $nameDB = clearData($_REQUEST['nameDB']);
  $engineType = clearData($_REQUEST['engineType']);
  $consentData = clearData($_REQUEST['consentData']);
  
  $hostAndPort = explode(':', $host);
  $port = null;
  $host = clearData($_REQUEST['host']);
  if(!empty($hostAndPort[1])){
    $port = $hostAndPort[1];
    $host = $hostAndPort[0];
  }
  
  if(!empty($engineType)){
    $checkedTest = 'checked=checked'; //отметка "по умолчанию" типа магазина -тестовый
  }

  if (!$_REQUEST['existDB']) {
     
    // Проверка адреса сайта.
    if (''==$siteName) {
      $msg .= '<div class="msgError">Ошибка!
        Не заполнено имя сайта</div>';
    }
    // Проверка электронного адреса.
    if (!preg_match(
        '/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]{0,61}+\.)+[a-zA-Z]{2,10}$/', $adminEmail)
    ) {
      $msg .= '<span class="error-email">Ошибка!
        Неверно заполнено email администратора</span>';
    }
      
    // Пароль должен быть больше 5-ти символов.
    if (strlen($aPass)<5) {
      $msg .= '<span class="error-pass-count">Ошибка!
        Пароль менее 5 символов</span>';
      // Иначе, если не отмечено что пароль видимый.
    } elseif (!$_REQUEST['showPass']) {
      $rePass = clearData($_REQUEST['rePass']);

      // Проверяем равенство введенных паролей.
      if ($rePass!=$aPass) {
        $msg .= '<span class="error-pass">Ошибка!
          Введенные пароли не совпадают</span>';
      }
    }
  
    // Если ошибок нет
    if (!$msg) {

	  //Тестирование введенных пользователем параметров.
	  try {
      $connection = mysqli_connect($host, $user, $password, $nameDB, $port);

		if (!$connection) {
		  throw new Exception('<span class="no-bd">Невозможно установить соединение.</span>');
		}

		if (!mysqli_select_db($connection,$nameDB)) {
		  throw new Exception('<span class="error-db">Ошибка! Невозможно выбрать указанную базу.</span>');
		}
	  } catch (Exception $e) {
		//Выведет либо сообщение об ошибке подключения, либо об ошибке выбора.
		$msg = '<div class="msgError">'.$e->getMessage().'</div>';
	  }

      $mysqlVersion = mysqli_get_server_version($connection);
      $_SESSION['install_mysqlVersion'] = $mysqlVersion;
      $arVersion = array(
        'main' => round($mysqlVersion/10000),
        'minor' => ($mysqlVersion/100)%10,
        'sub' => $mysqlVersion%100,
      );

      if (file_exists('install/dbDump.php')) { //подгружаем основной дамп БД
      
        require_once ('install/dbDump.php');
   
        if(is_array($damp)){
          if (file_exists('install/dbDumpTestShop.php') && $checkedTest == 'checked=checked') { //если указано, что устанавливать тестовый магазин,
          //  то подгружаем дамп тестового магазина
            require_once ('install/dbDumpTestShop.php');
            
            if(is_array($dampTestShop)){
              $damp = array_merge($damp, $dampTestShop);
            }
          } else {
            $disablePlugins = "UPDATE `".$prefix."plugins` SET active = 0 WHERE folderName IN ('adaptizator', 'mg-brand', 'mg-slider', 'trigger-guarantee', 'daily-product')";
          }

          if ($mysqlVersion >= 50503) {
            mysqli_query($connection, "ALTER DATABASE `".$nameDB."` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;");
          }
          foreach ($damp as $sql) {
            mysqli_query($connection,$sql) or die  ("Ошибка выполнения запроса:".mysqli_error($connection)."<br/>".$sql);
          }
          if (!empty($disablePlugins)) {
            mysqli_query($connection, $disablePlugins);
          }
          // include 'mg-core/lib/category.php';
          // $cat = new Category;
          // $cat->startIndexation();
        }
      }else{
	    echo "Внимание! Файл install/dbDump.php - не существует, не удалось установить движок! ";
	    exit();
	  }

      $cryptAPass = password_hash($aPass, PASSWORD_DEFAULT);
      $sql = "
        INSERT INTO `".$prefix."user` 
          (`email`, `pass`,`name`,`role`,`activity`,`phone`,`date_add`)
        VALUES ('".$adminEmail."','".$cryptAPass."', '".$aLogin."', 1, 1, '+7 (111) 111 11-11', '".date('Y-m-d H:i:s')."')
      ";
      mysqli_query($connection,$sql);
      $sql = "
        UPDATE `".$prefix."setting`
        SET `value` = '".$adminEmail."'
        WHERE `option` = 'adminEmail'
      ";
      mysqli_query($connection,$sql);
      $sql = "
      UPDATE `".$prefix."user` 
      SET `login_email` = `email`
      ";
      mysqli_query($connection,$sql);
      $sql = "
        UPDATE `".$prefix."setting`
        SET `value` = '".$consentData."'
        WHERE `option` = 'consentData'
      ";
      mysqli_query($connection,$sql);
      $sql = '
        SELECT *
        FROM `'.$prefix.'user`
        WHERE `email` = "'.$adminEmail.'"';

      $res = mysqli_query($connection,$sql);    
      session_start();
      if ($row = mysqli_fetch_object($res)) {        
        $_SESSION['user'] = $row;
      }
  
    }else{
      $step = 2;
    }
  } else {

    $sql = '
    SELECT id
    FROM `'.$prefix.'user`
    WHERE `role` = 1';

    $res = mysqli_query($connection,$sql);

    if (!mysql_fetch_assoc($res))
      $msg .= '<div class="error-email">Ошибка! Недостаточно данных для установки системы. Не найден аккаунт с правами администратора</div>';
  }
  if (!$msg) {
    $step = 3;
    // Запись введенных данных в файл параметров config.ini
    if(!empty($port)){ 
        $host = $host.":".$port;
    }
    $str = "[DB]\r\n";
    $str .="HOST = \"".$host."\"\r\n";
    $str .="USER = \"".$user."\"\r\n";
    $str .="PASSWORD = \"".$password."\"\r\n";
    $str .="NAME_BD = \"".$nameDB."\"\r\n";
    $str .="TABLE_PREFIX = \"".$prefix."\"\r\n";
    
    $str .="\r\n";
    $str .="[SETTINGS]\r\n";
    $str .=";Консоль выполненных sql запросов, для генерации страницы\r\n";
    $str .="DEBUG_SQL = 0\r\n";
    
    $str .="\r\n";
    $str .="; Протокол обмена данными с сайтом,(http или https)\r\n";
    $str .="PROTOCOL = \"".(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off'  ? 'https' : 'http')."\"\r\n";

    $str .="\r\n";
    $str .="; Включает логирование аякс запросов в БД\r\n";
    $str .="AJAX_LOGGING = 0\r\n";

    $str .="\r\n";
    $str .="; Включает новый способ отправки писем через smtp\r\n";
    $str .="NEW_SMTP_TLS = 1\r\n";
	
    $str .="\r\n";
    $str .="; Максимальное количество наименований товаров в одном заказе\r\n";
    $str .="MAX_COUNT_CART = 50\r\n";
    
    $str .="\r\n";
    $str .="; Позволяет использовать объемные запросы на хостинге\r\n";
    $str .="SQL_BIG_SELECTS = 0\r\n";  
    
    $str .="\r\n";
    $str .="; Включает дубли страниц заканчивающиеся на .html\r\n";
    $str .="OLDSCOOL_LINK = 0\r\n";
    
    $str .= "\r\n";
    $str .= "; Опция для создания папок с файлами .quarantine и .thums при работе в elfinder \r\n";
    $str .= "CREATE_TMB = 0 \r\n";

    $str .= "\r\n";
    $str .= "; Сервер обновлений плагинов и движка \r\n";
    $str .= "UPDATE_SERVER = 'http://updata.moguta.ru' \r\n";

    $str .= "LOG_USER_AGENT = 0\r\n";

    $str .= "\r\n";
    $str .= "; Включение режима редактирования в публичной части \r\n";
    $str .= "EDIT_MODE = 1 \r\n";

    $str .="\r\n";
    $str .="; Разрешить ли движку автоматическое обновление файлов, в случае изменения версии PHP на сервере\r\n";
    $str .="AUTO_UPDATE = 1\r\n";

    $str .="\r\n";
    $str .="; Максимальный размер файлов для добавления в резервные копии (в мегабайтах)\r\n";
    $str .="BACKUP_MAX_FILE_SIZE = 30\r\n";

    $str .="\r\n";
    $str .="; Кодировка для выгрузки каталога в Яндекс.Маркет по ссылке /getyml, значение none - кодировка не будет указана \r\n";
    $str .="ENCODE_YML_CATALOG = 'windows-1251'\r\n";

    $str .="\r\n";
    $str .="; Выгрузка на Яндекс.Маркет по ссылке /getyml всех товаров (=0) или только тех, которые есть в наличии (=1) \r\n";
    $str .="YML_ONLY_AVAILABLE = 0\r\n";

    $str .="\r\n";
    $str .="; Подставляет миниатюру 30_ вместо 70_ в миникарточку товара в шаблоне moguta \r\n";
    $str .="MODE_MINI_IMAGE = 1\r\n";

    $str .= "\r\n";
    $str .= "; Если установлен параметр \"1\", то будет производиться проверка целостности файла (в случае проблемы установите значение на \"0\") \r\n";
    $str .= "CSV_COLUMN_CHECK = 1\r\n";

    $str .= "\r\n";
    $str .= "; Приравнивать к 0 старую цену товара, если старая цена меньше обычной\r\n";
    $str .= "NULL_OLD_PRICE = 1\r\n";

    $str .= "\r\n";
    $str .= "; Возможность просматривать шаблоны сайта с помощью GET параметра tpl= (папка шаблона), цвет шаблона color= (код цвета шаблона, например, 81bd60) и вкл/выкл плагинов pm= (on/off)\r\n";
    $str .= "PREVIEW_TEMPLATE = 0\r\n";

    $str .= "\r\n";
    $str .= "; Сохранение корзины в cookie (0 - отключить, 1 - работает для всех, 2 - включить только для администратора)\r\n";
    $str .= "CART_IN_COOKIE = 1\r\n";

    $str .= "\r\n";
    $str .= "; Задержка при массовом создании миниатюр изображений в секундах (если ваш хостинг ограничивает максимальную нагрузку на процессор и при пересоздании миниатюр или создании изображений после загрузки CSV появляется ошибка, то увеличьте значение этой настройки)\r\n";
    $str .= "MASS_IMG_DELAY = 0.001\r\n";

    if ($_SESSION['install_mysqlVersion'] < 50503) {
      $str .= "\r\n";
      $str .= "; Использовать кодировку utf8mb4\r\n";
      $str .= "UTF8MB4 = 0\r\n";
    }

    $str .= "\r\n";
    $str .= "; Максимальное количество картинок, которое обрабатывается за 1 итерацию при генерации миниатюр\r\n";
    $str .= "MAX_IMAGES_COUNT = 25\r\n";

    $str .= "\r\n";
    $str .= "; Для использования старого алгорима объединения JS в один файл (0 - исплользуется новый алгоритм, 1 - используется старый алгоритм)\r\n";
    $str .= "OLD_CACHE_JS = 0\r\n";

    $str .= "\r\n";
    $str .= "; Измениять статус заказа в ЯндексКассе при отмене платежа(0 - не менять, 1 - менять)\r\n";
    $str .= "CHANGE_STATUS_UKASSA = 0\r\n";

    $str .= "\r\n";
    $str .= "; Если у базы данных стоит строгий режим, то значение 0 исправит ситуацию, при значении 1 движок будет работать как раньше\r\n";
    $str .= "SQL_MODE = 0\r\n";

    $str .= "\r\n";
    $str .= "; Перезаписывать cookies c UTM метками по последнему визиту\r\n";
    $str .= "UTM_UPDATE_LAST_VISIT = 0\r\n";

    $str .= "\r\n";
    $str .= "; Для переключения между старой системой оплат и новой системой оплат \r\n";
    $str .= "NEW_PAYMENT = 1\r\n";
		
	
    file_put_contents('config.ini', $str);

    $robots ="User-agent: Yandex
  Allow: /uploads/
  Allow: *.css
  Allow: *.js
  Allow: *.jpg
  Allow: *.JPG
  Allow: *.svg
  Disallow: /install*
  Disallow: /mg-admin*
  Disallow: /personal*
  Disallow: /enter*
  Disallow: /forgotpass*
  Disallow: /payment*
  Disallow: /registration*
  Disallow: /compare*
  Disallow: /cart*
  Disallow: /*?*lp*
  Disallow: *applyFilter=*
  Disallow: *?inCartProductId=*
  Disallow: *?inCompareProductId=*
  Disallow: /*?

  User-agent: *
  Allow: /uploads/
  Allow: /*.js
  Allow: /*.css
  Allow: /*.jpg
  Allow: /*.gif
  Allow: /*.png
  Allow: /*.svg
  Allow: *engine-script-LANG.js*
  Allow: *engine-script.js*

  Disallow: /install*
  Disallow: /mg-admin*
  Disallow: /personal*
  Disallow: /enter*
  Disallow: /forgotpass*
  Disallow: /payment*
  Disallow: /registration*
  Disallow: /compare*
  Disallow: /cart*
  Disallow: /*?*lp*
  Disallow: *applyFilter=*
  Disallow: *?inCartProductId=*
  Disallow: *?inCompareProductId=*
  Disallow: /*?

  User-agent: Googlebot
  Allow: *.css
  Allow: *.js
  Allow: *.jpg
  Allow: *.JPG
  Allow: *.svg
  Allow: /mg-core/script/*.js
  Allow: *engine-script-LANG.js*
  Disallow: /*?
  Disallow: /mg-formvalid
  
  Host: ".$_SERVER['SERVER_NAME']."
  Sitemap: http://".$_SERVER['SERVER_NAME']."/sitemap.xml";
    
    file_put_contents('robots.txt', $robots);
     
    $tables = array(
      'category',
      'category_user_property',
      'delivery',
      'order',
      'page',
      'payment',
      'plugins',
      'product',
      'product_user_property',
      'property',
      'setting',
      'user',
    );
    
       // отправка флага окончания установки
    $id = $idInstaller;
    if ($id) {
      $post = "&installer=".$id."&flag=install&edition=giper";
      
      $url = "https://moguta.ru/checkinstaller";
      // Инициализация библиотеки curl.
      $ch = curl_init();
      // Устанавливает URL запроса.
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      // При значении true CURL включает в вывод заголовки.
      curl_setopt($ch, CURLOPT_HEADER, false);
      // Куда помещать результат выполнения запроса:
      //  false – в стандартный поток вывода,
      //  true – в виде возвращаемого значения функции curl_exec.
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      // Нужно явно указать, что будет POST запрос.
      curl_setopt($ch, CURLOPT_POST, true);
      // Здесь передаются значения переменных.
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      // Максимальное время ожидания в секундах.
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
      // Выполнение запроса.
      $res = curl_exec($ch);
      curl_close($ch);
     }
     //копирование стандартного шаблона
     $baseDir = str_replace(DIRECTORY_SEPARATOR.'install', DIRECTORY_SEPARATOR, dirname(__FILE__));
     $templateDir = $baseDir.'mg-templates'.DIRECTORY_SEPARATOR;
     if (!is_file($templateDir.'moguta'.DIRECTORY_SEPARATOR.'template.php')) {
       copyDir($templateDir.'moguta-standard', $templateDir.'moguta');
       rmdir($templateDir.'moguta-standard'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'payment');
     }
     $orderFormFileDir = $baseDir.'uploads'.DIRECTORY_SEPARATOR.'generatedJs';

     if (!is_dir($orderFormFileDir)) {
      mkdir($orderFormFileDir);
     }

     $orderFormFile = $orderFormFileDir.DIRECTORY_SEPARATOR.'order_form.js';

     file_put_contents($orderFormFile, 
        'var orderForm = (function () {'.PHP_EOL.
        ' return {'.PHP_EOL.
        '   init: function() {'.PHP_EOL.
        '     $(\'body\').on(\'change\', \'form[action*="/order?creation=1"] input[name="delivery"], form[action*="/order?creation=1"] [name=customer]\', function() {'.PHP_EOL.
        '       orderForm.redrawForm();'.PHP_EOL.
        '     });'.PHP_EOL.
        '     $(\'form[action*="/order?creation=1"] *\').removeAttr(\'data-delivery-address\');'.PHP_EOL.
        '     orderForm.redrawForm();'.PHP_EOL.
        '   },'.PHP_EOL.
        '   redrawForm: function() {'.PHP_EOL.
        '     var delivId = 0;'.PHP_EOL.
        '     if ($(\'form[action*="/order?creation=1"] input[name=delivery]:checked\').length) {'.PHP_EOL.
        '       delivId = $(\'form[action*="/order?creation=1"] input[name=delivery]:checked\').val();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray(parseInt(delivId), [0,1,2]) !== -1) {//address'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=address]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=address]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=address]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=address]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_nameyur'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_adress'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_inn'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_kpp'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_bank'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_bik'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_ks'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '     if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_rs'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').prop(\'disabled\', false);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
        '     } else {'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').prop(\'disabled\', true);'.PHP_EOL.
        '       $(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
        '     }'.PHP_EOL.
        '		},'.PHP_EOL. 
        '  //Методы для даты доставки'.PHP_EOL.
        ' disableDateMonthForDatepicker:function(monthWeek){'.PHP_EOL. 
        '   let disableDateMonth = [];'.PHP_EOL. 
        '    for(key in monthWeek){'.PHP_EOL. 
        '      let days = monthWeek[key].split(\',\');'.PHP_EOL. 
        '      let month = \'\';'.PHP_EOL. 
        '      switch (key){'.PHP_EOL. 
        '        case \'jan\' :'.PHP_EOL. 
        '         month = \'01\';'.PHP_EOL. 
        '         break;'.PHP_EOL. 
        '        case \'feb\' :'.PHP_EOL. 
        '         month = \'02\';'.PHP_EOL. 
        '         break;     '.PHP_EOL. 
        '        case \'mar\' :'.PHP_EOL. 
        '         month = \'03\';'.PHP_EOL. 
        '         break;'.PHP_EOL. 
        '        case \'aip\' :'.PHP_EOL. 
        '         month = \'04\';'.PHP_EOL. 
        '         break; '.PHP_EOL. 
        '        case \'may\' :'.PHP_EOL. 
        '         month = \'05\';'.PHP_EOL. 
        '         break; '.PHP_EOL. 
        '        case \'jum\' :'.PHP_EOL. 
        '         month = \'06\';'.PHP_EOL. 
        '         break;  '.PHP_EOL. 
        '        case \'jul\' :'.PHP_EOL. 
        '         month = \'07\';'.PHP_EOL. 
        '         break;'.PHP_EOL. 
        '        case \'aug\' :'.PHP_EOL. 
        '         month = \'08\';'.PHP_EOL. 
        '         break;     '.PHP_EOL. 
        '        case \'sep\' :'.PHP_EOL. 
        '         month = \'09\';'.PHP_EOL. 
        '         break;'.PHP_EOL. 
        '        case \'okt\' :'.PHP_EOL. 
        '         month = \'10\';'.PHP_EOL. 
        '         break; '.PHP_EOL. 
        '        case \'nov\' :'.PHP_EOL. 
        '         month = \'11\';'.PHP_EOL. 
        '         break; '.PHP_EOL. 
        '        case \'dec\' :'.PHP_EOL. 
        '         month = \'12\';'.PHP_EOL. 
        '         break;         '.PHP_EOL.                                                                                                                                                             
        '      }'.PHP_EOL. 
        '      days.forEach(function(item){'.PHP_EOL. 
        '        if(item !== \'\'){'.PHP_EOL. 
        '          if(item < 10){'.PHP_EOL. 
        '            item = \'0\'+item.toString();'.PHP_EOL. 
        '          }'.PHP_EOL. 
        '          disableDateMonth.push(month+"-"+item);'.PHP_EOL. 
        '        }'.PHP_EOL. 
        '      });'.PHP_EOL. 
        '    }'.PHP_EOL. 
        '    return disableDateMonth;'.PHP_EOL. 
        '  },'.PHP_EOL. 
        '  disableDateWeekForDatepicker:function(daysWeek){'.PHP_EOL. 
        '    let disableDateWeek = [];'.PHP_EOL. 
        '    for(key in daysWeek){'.PHP_EOL. 
        '      if(daysWeek[key] != true){'.PHP_EOL. 
        '        let numberOfWeekDay = \'\';'.PHP_EOL. 
        '        switch (key){'.PHP_EOL. 
        '          case \'su\' : numberOfWeekDay = 0;'.PHP_EOL. 
        '            break;'.PHP_EOL. 
        '          case \'md\' : numberOfWeekDay = 1;'.PHP_EOL. 
        '            break;'.PHP_EOL.                                     
        '         case \'tu\' : numberOfWeekDay = 2;'.PHP_EOL. 
        '           break;'.PHP_EOL. 
        '         case \'we\' : numberOfWeekDay = 3;'.PHP_EOL. 
        '            break;'.PHP_EOL. 
        '          case \'thu\' : numberOfWeekDay = 4;'.PHP_EOL. 
        '            break;  '.PHP_EOL. 
        '          case \'fri\' : numberOfWeekDay = 5;'.PHP_EOL. 
        '            break;'.PHP_EOL. 
        '          case \'sa\' : numberOfWeekDay = 6;'.PHP_EOL. 
        '            break;        '.PHP_EOL.                                                                                                                                        
        '          }'.PHP_EOL. 
        '        disableDateWeek.push(numberOfWeekDay);'.PHP_EOL. 
        '      }'.PHP_EOL. 
        '    }'.PHP_EOL. 
        '    return disableDateWeek;'.PHP_EOL. 
        '  },'.PHP_EOL. 
        '  disableDateForDatepicke: function(day, stringDay, monthWeek, daysWeek){  '.PHP_EOL. 
        '    let isDisabledDaysMonth = ($.inArray(stringDay, orderForm.disableDateMonthForDatepicker(monthWeek)) != -1);'.PHP_EOL. 
        '    let isDisabledDaysWeek = ($.inArray(day, orderForm.disableDateWeekForDatepicker(daysWeek)) != -1);'.PHP_EOL. 
        '    return [(!isDisabledDaysWeek && !isDisabledDaysMonth)];'.PHP_EOL. 
        '  }'.PHP_EOL. 
        ' };'.PHP_EOL.
        '})();'.PHP_EOL.
        '$(document).ready(function() {'.PHP_EOL.
        ' if (location.pathname.indexOf(\'/order\') > -1) {'.PHP_EOL.
        '   orderForm.init();'.PHP_EOL.
        ' }'.PHP_EOL.
        '});'
     );
  }
}

/**
 * Фильтрует введенные пользователем данные
 *
 * @param string $str передаваемая строка
 * @param int $strong строгость проверки
 * @return string отфильтрованная строка
 *
 */
function clearData($str, $strong = 2) {

  switch ($strong) {
    case 1:
      return trim($str);
    case 2:
      return trim(strip_tags($str));
  }
}

function downloadDemoData($part){
  $imageZip = 'uploads.zip';
  $ch = curl_init('http://updata.moguta.ru/downloads/demofiles/uploads-8-'.$part.'.zip');
  $fp = fopen($imageZip, "w");
  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);

  if(file_exists($imageZip)) return true;

  return false;
}

/**
 * скачивание архива с изображениями для тестового магазина
 * @param string $imageFile путь к архиву на сервере
 * @return string|boolean в случае успеха путь к архиву в папке инсталлятора
 */
function downloadTestImage($imageFile){
    $imageZip = 'install/image.zip';
    $ch = curl_init($imageFile);
    $fp = fopen($imageZip, "w");
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    
    if(file_exists($imageZip)) return $imageZip;
    
    return false;
}

  /**
   * Распаковывает архив.
   * После распаковки удаляет заданный архив.
   *
   * @param $file - название архива, который нужно распаковать
   * @return bool
   */
  function extractZip($file) {
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'install', '', dirname(__FILE__));
    $imageFolder = $realDocumentRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
      
    if (file_exists($file)) {
      $zip = new ZipArchive;
      $res = $zip->open($file, ZIPARCHIVE::CREATE);

      if ($res === TRUE) {
        $zip->extractTo($imageFolder);
        $zip->close();
        unlink($file);

        return true;
      } else {
				unlink($file);
        return false;
      }
    }
    return false;
  }
    /**
   * Функция проверяет наличие установленных библиотек PHP
   * @return boolean|srting сообщение об отсутствии необходимого модуля
   */
  function libExists() {
        $res = array();
      if(!function_exists('curl_init')){
        $res[] = 'Пакет libcurl не установлен! Библиотека cURL не подключена.';
      }
      
      if(!extension_loaded('zip')){
        $res[] = 'Пакет zip не установлен! Библиотека ZipArchive не подключена.';
      }
      
      file_put_contents('temp.txt', ' ');
      
      if(!file_exists('temp.txt')){
        $res[] = 'Нет прав на создание файла. Загрузка архива с обновлением невозможна';
      }else{
        unlink('temp.txt');
      }
      
     
      if(!filesize('.htaccess')){
        // создаем необходимый htaccess
        createHtAccess(getVersionHtaccess());
      }    

      return $res;
    }

  switch ($step) {
    case 0:
      require_once ('step0.php');
      break;
    case 1:
      require_once ('step1.php');
      break;
    case 2:
      require_once ('step2.php');
      break;
    case 3:
      require_once ('step3.php');
    break;
  }

// функция для определения нужного варпианта htaccess
function getVersionHtaccess() { 
  $verAc = 1;
    $result = false;
  if(!$result) {
    // создаем стандартный файл htaccess
    createHtAccess(1);
    // отправляем тестовый запрос для проверки перенаправления
    $ch = curl_init('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'test?test=test');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    $result = $info['http_code'];
    curl_close($ch);
    $verAc = 1;
  }

  if($result != 200) {
    // создаем измененный файл htaccess
    createHtAccess(2);
    // отправляем тестовый запрос для проверки перенаправления
    $ch = curl_init('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'test?test=test');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    $result = $info['http_code'];
    curl_close($ch);
    $verAc = 2;
  }

  if($result != 200) {
    // создаем измененный файл htaccess
    createHtAccess(3);
    // отправляем тестовый запрос для проверки перенаправления
    $ch = curl_init('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'test?test=test');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    $result = $info['http_code'];
    curl_close($ch);
    $verAc = 3;
  }

  if($result != 200) {
    $verAc = 1;
  }

  @unlink('.htaccess');

  return $verAc;
}

// функция для создания файла htaccess
function createHtAccess($var = 1) {
if($var == 1) {
    $rewriteBase = '#RewriteBase /';
} else {
    $rewriteBase = 'RewriteBase /';
}

@unlink('.htaccess');

$htaccess = 'AddType image/x-icon .ico
AddDefaultCharset UTF-8

<IfModule mod_rewrite.c>
';
if($var != 3) {
    $htaccess .= 'Options +FollowSymlinks
Options -Indexes
';
}
$htaccess .= 'RewriteEngine on

'.$rewriteBase.'
#запрос к изображению напрямую без запуска движка 
RewriteCond %{REQUEST_URI} \.(png|gif|ico|swf|jpe?g|js|css|ttf|svg|eot|woff|yml|xml|zip|txt|doc|map)$
RewriteRule ^(.*) $1 [QSA,L]
RewriteCond %{REQUEST_FILENAME} !-f [OR]
RewriteCond %{REQUEST_URI} \.(ini|ph.*)$
RewriteRule ^(.*) index.php [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L,QSA]
</IfModule>
';
if($var != 3) {
    $htaccess .= '<IfModule mod_php5.c> 
php_flag magic_quotes_gpc Off
</IfModule>';
}

file_put_contents('.htaccess', $htaccess);
chmod('.htaccess', 0777);
} 

function copyDir($source, $dest) {
    mkdir($dest, 0755);
    if (!is_dir($source) || !is_dir($dest)) {return false;}
    foreach (
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
      if ($item->isDir()) {
        mkdir($dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
      }
      else {
        copy($item, $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
      }
    }
  }
