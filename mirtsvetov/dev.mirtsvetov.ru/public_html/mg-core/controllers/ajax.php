<?php

/**
 * Контроллер: Ajax
 *
 * Класс Controllers_Ajax обрабатывает все AJAX запросы присылаемые из админки.
 * - Отключает вывод шаблона;
 * - Передает запрос в библиотеку Actioner.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Ajax extends BaseController {

  function __construct() {
    // Не существует обработки для прямого обращения.
    if (empty($_REQUEST)) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }

    // Отключаем вывод темы.
    MG::disableTemplate();


    $actioner = URL::getQueryParametr('actionerClass');
    if ('Ajaxuser' == $actioner) {
      $this->routeUserAction(URL::getQueryParametr('action'));
    }

    if(User::access('admin_zone') == 0) {
      header('HTTP/1.1 500 Internal Server Access denied');
      header('Content-Type: application/json; charset=UTF-8');
      echo json_encode(array('message' => 'Access denied', 'code' => 594));
      exit;
    }

    // Если этот аякс запрос направлен на выполнение
    // действия с БД, то пытаемся их выполнить.
    // Иначе подключается контролер из админки.

    $url = URL::getQueryParametr('mguniqueurl');

    $type = URL::getQueryParametr('mguniquetype');

    // Незарегистрированным пользователям и клиентам запрещено работать с разделами.
    if (strpos(URL::getQueryParametr('mguniqueurl'),'.')!==false) {
      // разрешенные имена файлов в запросе
      $access = array(
        'category.php',
        'page.php',
        'orders.php',
        'catalog.php',
        'plugins.php',
        'marketplace.php',        
        'integrations.php',
        'users.php',
        'settings.php',
        'statistic.php');
      if (!in_array($url, $access)) {
        header('HTTP/1.0 404 Not Found');
        exit();
      }
    }

    // Если передана переменная $pluginFolder, то вся обработка
    // происходит в плагине из этой папки.
    $pluginHandler = URL::getQueryParametr('pluginHandler');

    //Для фильтров:
    //Если идёт выгрузка в CSV по фильтру, перекидываем все 
    //GET-параметры в POST, так как у нас все контроллеры
    //читают фильтры через этот [POST] метод
    if (URL::getQueryParametr('csvExport') == '1') {
      //$_POST = array_merge($_POST, $_GET);
      $_POST = $_GET;
    }

    if (empty($pluginHandler)) {
      if (!$this->routeAction($url)) {
        if ('plugin' == $type) {
          if (!empty($_POST['request'])) {
            $_POST = $_POST['request'];
          }
          URL::setQueryParametr('view', ADMIN_DIR . 'section/views/plugintemplate.php');
        } else {


          if (!file_exists(ADMIN_DIR . 'section/controlers/' . $url)) {   
            require_once ADMIN_DIR . 'enter-key.php';
            $this->lang = MG::get('lang');
            // URL::setQueryParametr('view', ADMIN_DIR . 'section/views/' . $url);
            // header('HTTP/1.0 404 Not Found');
            // exit();
          } else {
            require ADMIN_DIR . 'section/controlers/' . $url;
            $this->lang = MG::get('lang');
            URL::setQueryParametr('view', ADMIN_DIR . 'section/views/' . $url);
          }
        }
      }
    } else {
      if (!PM::isPluginActive($pluginHandler)) {
        $jsonResponse = [
          'data' => [],
          'status' => 'error',
          'msg' => 'Плагин '.$pluginHandler.' не подключен',
        ];
        echo json_encode($jsonResponse, JSON_UNESCAPED_UNICODE);
        exit;
      }
      // Обработкой действия займется плагин, папка которого передана в $pluginHandler.
      $actioner = URL::getQueryParametr('actionerClass');
      if (empty($actioner)) {
        $actioner = 'Pactioner';
      }
      $actionerPath = URL::getQueryParametr('actionerPath');
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
   * @param string $url - ссылка на действие.
   * @param string $plugin - папка с плагином.
   * @param string $actioner - обработчик аякс запросов.
   * @return bool
   */
  public function routeAction($url, $plugin = null, $actioner = false, $actionerPath = null) {
    // Если не плагин.
    if (!$plugin) {
      //Защита контролера от несанкционированного доступа вне админки.
      if (!$this->checkAccess(User::getThis()->role)) {
        echo "Для доступа к методу необходимо иметь права администратора!";
        exit;
        return false;
      };

      $parts = explode('/', $url);
      if ($parts[0] == 'action') {
        $act = new Actioner();
        if(!method_exists($act, $parts[1])){
          header('HTTP/1.0 404 Not Found');
          exit();
        }
        $act->runAction($parts[1]);
        return true;
      }
    } else {

      // Подключам пользовательский класс для обработки.
      $action = URL::getQueryParametr('action');

      if (empty($action)) {
        $parts = explode('/', $url);
        if ($parts[0] == 'action') {
          $action = $parts[1];
        }
      }

      // Формируем путь до класса плагина, который обработает действие.
      $pluginInfo = MG::get('pluginsInfo');
      $pluginInfo = $pluginInfo[$plugin];
      if (!empty($pluginInfo['fromTemplate'])) {
        $pluginFolder = SITE_DIR.'mg-templates'.DS.MG::getSetting('templateName').DS.'mg-plugins'.DS.$plugin.DS;
      } else {
        $pluginFolder = SITE_DIR.'mg-plugins'.DS.$plugin.DS;
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
        // Подключаем класс плагина.
        include $pathPluginActioner;
      }

      // Создаем экземпляр класса обработчика.
      // (он обязательно должен наследоваться от стандартного класса Actioner)
      $lang = $pluginFolder.'locales'.DS.MG::getSetting('languageLocale').'.php';

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

      // Выполняем стандартный метод класса Actioner.
      $act->runAction($action);
      return true;
    }

    return false;
  }

  /**
   * Маршрутизатор для AJAX запроса. Передает запрос на 
   * обработку в  файл шаблона ajaxuser.php.
   * @param string $action - запрошенное действие.
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
    // Создаем экземпляр класса обработчика.
    // (он обязательно должен наследоваться от стандартного класса Actioner)
    if (class_exists('Ajaxuser')) {
      $act = new Ajaxuser();
      if (method_exists($act, $action)) {
        // Выполняем стандартный метод класса Actioner.
        $act->runAction($action);
        return true;
      }
    }
    return false;
  }

  /**
   * Проверяет наличие прав администратора, на доступ к этому контролеру.
   * Защищает его от прямых ссылок таких как ajax?url=action/editProduct
   *
   * @param bool $role флаг прав администратора
   * @return bool
   */
  public function checkAccess($role) {
    if ($role == '2') {
      header('HTTP/1.0 404 Not Found');
      URL::setQueryParametr('view', PATH_TEMPLATE . '/404.php');
      return false;
    }
    return true;
  }

  /**
   * Проверяет наличие переходов по папкам /../.. в пути подключаемого файла и наличие самого файла
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

}
