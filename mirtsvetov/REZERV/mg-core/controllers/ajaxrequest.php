<?php

/**
 * Контроллер: Ajaxrequest
 *
 * Класс Controllers_Ajaxrequest обрабатывает все AJAX запросы как из админки так и с публичной части сайта.
 * - Отключает вывод шаблона;
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Ajaxrequest extends BaseController{

  function __construct() {

    // Не существует обработки для прямого обращения.
    if(empty($_REQUEST)) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }

    // Отключаем вывод темы.
    MG::disableTemplate();

    $actioner = URL::getQueryParametr('actionerClass');
    if('Ajaxuser'==$actioner) {
      $this->routeUserAction(URL::getQueryParametr('action'));
      // Если в пользовательском классе не найдено обрабатывающего метода,
      // то продолжаем поиск в админском классе, но туда имеют доступ только админы.
    }

    if(URL::getQueryParametr('delInstal')) {
      $this->delInstal();
    }

    // обработка аякс запроса на вывод модалки подтверждения на обработку персональных данных
    if(URL::getQueryParametr('layoutAgreement')) {   
      echo MG::layoutManager('layout_agreement', array());   
    }

    // обработка аякс запроса на смену валюты
    if(URL::getQueryParametr('userCustomCurrency')) {
      $_SESSION['userCurrency'] = URL::getQueryParametr('userCustomCurrency');

      $rates = MG::getSetting('currencyRate');
      $names = MG::getSetting('currencyShort');
      $newCurr = $names[$_SESSION['userCurrency']];

      foreach ($_SESSION['cart'] as $Pkey => $product) {
        if (isset($product['property'])) {
          $tmp = htmlspecialchars_decode(str_replace('&amp;', '&', $product['property']));
          $arr1 = explode('<div class="prop-position">', $tmp);

          foreach ($arr1 as $key => $value) {
            $arr2 = explode('<span class="prop-val">', $value);
            $str = $arr2[1];
            $str = str_replace('</span></div>', '', $str);
            $str = str_replace(' + ', '', $str);
            $oldBlock = $str;
            $str = preg_replace("/[^0-9,.]/", "", $str);
            $oldNum = $str;
            $oldNum = preg_replace('/\s+/', '', $oldNum);
            $oldNum = str_replace(' ', '', $oldNum);
            $oldCurr = str_replace($oldNum.' ', '', $oldBlock);
            $oldCurr = preg_replace('/\s+/', '', $oldCurr);
            $oldCurr = str_replace(' ', '', $oldCurr);
            $str = str_replace(',', '.', $str);
            $str = (float)$str;
            $curr = array_search($oldCurr, $names);
            $price = round($str/$rates[$_SESSION['userCurrency']],2);
            $price = MG::priceCourse($price);
            $newBlock = $price.' '.$newCurr;
            $arr1[$key] = str_replace($oldBlock, $newBlock, $value);
          }
          $html = implode('<div class="prop-position">', $arr1);
          $html = htmlspecialchars($html);
          $_SESSION['cart'][$Pkey]['property'] = $html;
        }
      }
      Models_Cart::sessionToCookie();
    }

    // Если передана переменная $pluginFolder, то вся обработка
    // перекладывается на плечи стороннего плагина из этой папки.
    $pluginHandler = URL::getQueryParametr('pluginHandler');
    $url = URL::getQueryParametr('mguniqueurl');
    $type = URL::getQueryParametr('mguniquetype');


    if(!empty($pluginHandler)) {
      // Обработкой действия займется плагин, папка которого передана в $pluginHandler.
      $actioner = URL::getQueryParametr('actionerClass');
      $actionerPath = URL::getQueryParametr('actionerPath');
      if(empty($actioner)) {
        $actioner = 'Pactioner';
      }
      $this->routeAction($url, $pluginHandler, $actioner, $actionerPath);
    }
  }

  /**
   * Если действие запрошенно стандартными файлами движка, то
   * маршрутизирует действие в класс Actioner для дальнейшего выполнения.
   *
   * Если действие запрошено из страницы плагина, то передает действие в
   * пользовательский класс плагина. Класс плагина передается
   * в переменной  URL::getQueryParametr('action')
   *
   * @param string $url ссылка на действие.
   * @param string $plugin папка с плагином.
   * @param string $actioner обработчик аякс запросов.
   * @return bool
   */
  public function routeAction($url, $plugin = null, $actioner = false, $actionerPath = null) {
    // Если не плагин.
    if(!$plugin) {
      //Защита контролера от несанкционированного доступа вне админки.
      if(!$this->checkAccess(User::getThis()->role)) {
        echo "Для доступа к методу необходимо иметь права администратора!";
        exit;
        return false;
      };

      $parts = explode('/', $url);
      if($parts[0]=='action') {
        $act = new Actioner();
        $act->runAction($parts[1]);
        return true;
      }
    }
    else{
      // Подключам пользовательский класс для обработки.
      $action = URL::getQueryParametr('action');

      if(empty($action)) {
        $parts = explode('/', $url);
        if($parts[0]=='action') {
          $action = $parts[1];
        }
      }
      $pathDir = PLUGIN_DIR;
        if (!PM::isPluginActive($plugin)) {
          $jsonResponse = [
            'data' => [],
            'status' => 'error',
            'msg' => 'Плагин '.$plugin.' не подключен',
          ];
          echo json_encode($jsonResponse, JSON_UNESCAPED_UNICODE);
          exit;
        }


      $pluginInfo = MG::get('pluginsInfo');
      $pluginInfo = $pluginInfo[$plugin];
      if (!empty($pluginInfo['fromTemplate'])) {
        $pluginFolder = SITE_DIR.'mg-templates'.DS.MG::getSetting('templateName').DS.'mg-plugins'.DS.$plugin.DS;
      } else {
        $pluginFolder = $pathDir.$plugin.DS;
      }

      if ($actionerPath) {
        $actionerPath = str_replace('/', DS, trim($actionerPath, '/'));
      } else {
        $actionerPath = '';
      }

      $pluginClassPath = $pluginFolder.$actionerPath.DS.strtolower($actioner).'.php';
      if (file_exists($pluginClassPath)) {
        $pathPluginActioner = $pluginClassPath;
      } else {
        $pathPluginActioner = $pluginFolder.$actionerPath.DS.$actioner.'.php';
      }

      if($this->checkPathIncludeFile($pathPluginActioner)) {
        // Подключаем класс плагина.
        include $pathPluginActioner;
      }
      // Создаем экземпляр класа обработчика.
      // (он обязательно должен наследоваться от стандартного класса Actioner)    

      $lang = $pathDir.$plugin."/locales/".MG::getSetting('languageLocale').'.php';
      if(file_exists($lang)) {
        if($this->checkPathIncludeFile($pathPluginActioner)) {
          include $lang;
        }
        if (class_exists($actioner)) {
          $act = new $actioner($lang);
        }
      }
      else{
        if (class_exists($actioner)) {
          $act = new $actioner();
        }else{
          return false;
        }
      }

      // выполняем стандартный метод класса Actioner
      $act->runAction($action);
      return true;
    }

    return false;
  }

  /**
   * Маршрутизатор для AJAX запроса. Передает запрос на обработку в специальный класс.
   * @param string $action
   * @return bool
   */
  public function routeUserAction($action) {
    if (!defined('TEMPLATE_INHERIT_FROM')) {
      include PATH_TEMPLATE . '/ajaxuser.php';
    } else {
      if (is_file(PATH_TEMPLATE.DS.'ajaxuser.php')) {
        include PATH_TEMPLATE.DS.'ajaxuser.php';
      } elseif (TEMPLATE_INHERIT_FROM && is_file(SITE_DIR.'mg-templates'.DS.TEMPLATE_INHERIT_FROM.DS.'ajaxuser.php')) {
        include SITE_DIR.'mg-templates'.DS.TEMPLATE_INHERIT_FROM.DS.'ajaxuser.php';
      } elseif (TEMPLATE_INHERIT_FROM_STANDARD && is_file(SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'ajaxuser.php')) {
        include SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'ajaxuser.php';
      }
    }
    // создаем экземпляр класса обработчика
    // (он обязательно должен наследоваться от стандартного класса Actioner)
    if (class_exists('Ajaxuser')) {
      $act = new Ajaxuser();
      if(method_exists($act, $action)) {
        // выполняем стандартный метод класса Actioner
        $act->runAction($action);
        return true;
      }
    }
    return false;
  }

  /**
   * Проверяет наличие переходов по папкам /../.. в пути подключаемого файла и самого файла
   *
   * @param bool $role флаг прав администратора
   * @return bool
   */
  public function checkPathIncludeFile($path) {
    
    if (strpos($path, '..') !== false) {
      header('HTTP/1.0 404 Not Found');
      URL::setQueryParametr('view', PATH_TEMPLATE . '/404.php');
      return false;
    }

    if(!file_exists($path)) {
      header('HTTP/1.0 404 Not Found');
      URL::setQueryParametr('view', PATH_TEMPLATE . '/404.php');
      return false;
    }

    return true;
  }

  /**
   * Проверяет наличие прав администратора, на доступ к этому контролеру.
   * Защищает его от прямых ссылок таких как ajax?url=action/editProduct
   *
   * @param bool $role флаг прав администратора
   * @return bool
   */
  public function checkAccess($role) {
    if($role=='2') {
      header('HTTP/1.0 404 Not Found');
      URL::setQueryParametr('view', PATH_TEMPLATE.'/404.php');
      return false;
    }
    return true;
  }

  /**
   * Удаляет инсталлятор.
   * @return void
   */
  public function delInstal() {
    $installDir = SITE_DIR.URL::getCutPath().'/install/';
    $this->removeDir($installDir);
    MG::redirect('');
  }

  /**
   * Удаляет папку со всем ее содержимым.
   * @param string $path путь к удаляемой папке.
   * @return void|bool
   */
  public function removeDir($path) {
    if(file_exists($path)&&is_dir($path)) {
      $dirHandle = opendir($path);

      while(false!==($file = readdir($dirHandle))) {

        if($file!='.'&&$file!='..') {
          // Исключаем папки с названием '.' и '..'
          $tmpPath = $path.'/'.$file;
          chmod($tmpPath, 0777);

          if(is_dir($tmpPath)) {  // Если папка.
            $this->removeDir($tmpPath);
          }
          else{
            if(file_exists($tmpPath)) {
              // Удаляем файл.
              unlink($tmpPath);
            }
          }
        }
      }
      closedir($dirHandle);

      // Удаляем текущую папку.
      if(file_exists($path)) {
        rmdir($path);
        return true;
      }
    }
  }

}
