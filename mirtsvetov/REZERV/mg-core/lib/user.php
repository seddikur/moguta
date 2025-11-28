<?php

/**
 * Класс User - предназначен для работы с учетными записями пользователей системы.
 * Доступен из любой точки программы.
 * Реализован в виде синглтона, что исключает его дублирование.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class User {

  static private $_instance = null;
  private $auth = array();
  static $accessStatus = array(0 => 'Разрешён', 1 => 'Заблокирован');
  static $groupName = array(1 => 'Администратор', 2 => 'Пользователь', 3 => 'Менеджер', 4 => 'Модератор');

  private function __construct() {
    if(empty($_SESSION['user']) && MG::getSetting('rememberLogins') == 'true') {
      self::restoreLogin();
    }
    // гостей делаем
    if(empty($_SESSION['user'])) {
      $_SESSION['user'] = new stdClass();
      $_SESSION['user']->role = -1;
    } 
    // Если пользователь был авторизован, то присваиваем сохраненные данные.
    if (isset($_SESSION['userAuthDomain']) && $_SESSION['userAuthDomain'] == $_SERVER['SERVER_NAME']) {
      if ((int)MG::getSetting('checkAdminIp') === 1) {
        if (!empty($_SESSION['user']->hash)&& ($_SESSION['user']->hash === md5($_SESSION['user']->email.$_SESSION['user']->date_add.$_SERVER['REMOTE_ADDR']) )) {
          $this->auth = $_SESSION['user'];
        } else {
          $this->auth = null;
          unset($_SESSION['user']);
          unset($_SESSION['loginAttempt']);
       } 
      } else {
        $this->auth = $_SESSION['user'];
      }               
    }
  }

  private function __clone() {
    
  }

  private function __wakeup() {
    
  }

  /**
   * Возвращает единственный экземпляр данного класса.
   * <code>
   * $obj = User::getInstance();
   * </code>
   * @return object объект класса User.
   */
  static public function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self;
    }
    return self::$_instance;
  }

  /**
   * �?нициализирует объект данного класса User.
   * @access private
   * @return void
   */
  public static function init() {
    self::getInstance();
  }

  /**
   * Возвращает авторизированнго пользователя.
   * <code>
   * $result = User::getThis();
   * viweData($result);
   * </code>
   * @return array|object
   */
  public static function getThis() {
    return self::$_instance->auth;
  }

  /**
   * Добавляет новую учетную запись пользователя в базу сайта.
   * <code>
   * $userInfo = array(
   *  'id' => null,                   // id пользователя, при добавлении null
   *  'email' => 'mail@email.com',    // почта пользователя
   *  'pass' => '123456',             // пароль
   *  'name' => 'username',           // имя пользователя
   *  'birthday' => '01.03.2018',     // день рождения пользователя
   *  'sname' => 'usersname',         // фамилия 
   *  'address' => 'adr',             // адрес
   *  'phone' => '+7 (111) 111-11-11',// телефон
   *  'blocked' => 0,                 // флаг блокировки пользователя (1 = заблокирован)
   *  'activity' => 1,                // флаг активности пользователя (0 = не активен)
   *  'role' => 2                     // группа пользователя (1 - администратор, 2 - пользователь, 3 - менеджер, 4 - модератор)
   * );
   * User::add($userInfo);
   * </code>
   * @param array $userInfo массив значений для вставки в БД [Поле => Значение].
   * @return bool
   */
  public static function add($userInfo) {
    $result = false;
    $methodRegistration = '';
    $userInfo['login_email'] = $userInfo['email'];
    $getUserInfoByPhone = 0;
    $methodRegistration = 'Email';
    // $methodRegistration - флаг для проверки опции подтверждение регистрации пользователя по 'Email' or 'Phone', согластно настройкам безопасности в админ.панеле
    // Если пользователь регистрируется по номеру телефона.
    if(empty($userInfo['email'])){
        $userInfo['login_email'] = DB::idAutoIncrement("user").'@'.$_SERVER['SERVER_NAME'];
        $methodRegistration = 'Phone';
    }

    if(!empty($userInfo['phone']) && !isset($userInfo['login_phone']) && !empty($userInfo['login_phone'])){
        $userInfo['login_phone'] = $userInfo['phone'];
    }
    if (!empty($userInfo['phone']) && !self::getUserInfoByPhone($userInfo['login_phone'],'login_phone')) {
      $getUserInfoByPhone = 1;
    }
    // Если пользователя с таким емайлом еще нет.
    if ($userInfo['email'] && !self::getUserInfoByEmail($userInfo['login_email'],'login_email') || $getUserInfoByPhone == 1) {
      $saveRealPass = $userInfo['pass']; // сохраняем актуальный пароль, чтобы автоматически авторизовать, пользователя после создания заказа
      $userInfo['pass'] = password_hash($userInfo['pass'], PASSWORD_DEFAULT);

      foreach ($userInfo as $k => $v) {
        if($k !== 'pass' && $k !== 'op') {
          $userInfo[$k] = htmlspecialchars_decode($v);
          $userInfo[$k] = htmlspecialchars($v);
        }
      }

      if (!isset($userInfo['activity'])) {
        $userInfo['activity'] = 0;
        if (MG::getSetting('confirmRegistration'.$methodRegistration) == 'false') {
          $userInfo['activity'] = 1;
        }
      }
      $userInfo['date_add'] = date('Y-m-d H:i:s');
      unset($userInfo['id']);
      if (DB::buildQuery('INSERT INTO  `'.PREFIX.'user` SET ', $userInfo)) {
        $id = DB::insertId();
        LoggerAction::logAction('User','addUser', $id);
        $result = $id;
        // После создания пользователя и отправки ему данных для авторизации, автоматически аторизуем его на сайте.
        // Актуально для просмотра QR кода при первом создании заказа. В личный кабинет такой пользователь не сможет попасть. Будет предложенно перейти по ссылке в письме.
        if (!User::isAuth()) { // если ранее никто небыл авторизоан, например, админ добавил пользователя вручную, или интеграция какая-либо.
          //(По идее) этот код сработает только при создании пользователя из публички 
          User::auth($userInfo['email'], $saveRealPass);
        }
      }
    } else {
      $result = false;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Удаляет учетную запись пользователя из базы.
   * <code>
   * User::delete(3);
   * </code>
   * @param int $id id пользователя, чью запись следует удалить.
   * @return bool
   */
  public static function delete($id) {
    $res = DB::query('SELECT `role` FROM `'.PREFIX.'user` WHERE id = '.DB::quote($id));
    $role = DB::fetchArray($res);
    $args = func_get_args();
    // Нельзя удалить первого пользователя, поскольку он является админом
    if ($role['role'] == 1 ) {
      $res = DB::query('SELECT `id` FROM `'.PREFIX.'user` WHERE `role` = 1');
      if (DB::numRows($res) == 1 || $_SESSION['user']->id == $id) {
        return MG::createHook(__CLASS__."_".__FUNCTION__, false, $args);
      }
    }
    LoggerAction::logAction('User','deleteUser', $id);
    DB::query('DELETE FROM `'.PREFIX.'user` WHERE id = '.DB::quote($id));
    return MG::createHook(__CLASS__."_".__FUNCTION__, true, $args);
  }

  /**
   * Обновляет учетную запись пользователя.
   * <code>
   * $data = array(
   *  'id' => 14,                     // id пользователя
   *  'email' => 'mail@email.com',    // почта пользователя
   *  'pass' => '123456',             // пароль
   *  'name' => 'username',           // имя пользователя
   *  'birthday' => '01.03.2018',     // день рождения пользователя
   *  'sname' => 'usersname',         // фамилия 
   *  'address' => 'adr',             // адрес
   *  'phone' => '+7 (111) 111-11-11',// телефон
   *  'blocked' => 0,                 // флаг блокировки пользователя (1 = заблокирован)
   *  'activity' => 1,                // флаг активности пользователя (0 = не активен)
   *  'role' => 2                     // группа пользователя (1 - администратор, 2 - пользователь, 3 - менеджер, 4 - модератор)
   * );
   * User::update(14, $data);
   * </code>
   * @param int $id id пользователя.
   * @param array $data массив значений для вставки в БД 
   * @param bool $authRewrite false = перезапишет данные в сессии детущего пользователя, на полученные у $data.
   * @return bool
   */
  public static function update($id, $data, $authRewrite = false) {
    $userInfo = USER::getUserById($id);

    foreach ($data as $k => $v) {
      if($k !== 'pass' && $k !== 'op') {
        $v = htmlspecialchars_decode($v);    
        $data[$k] = htmlspecialchars($v);  
      }
    } 
      
    // только если пытаемся разжаловать админа, проверяем,
    // не является ли он последним админом
    // Без админов никак нельзя!
    if ($userInfo->role == '1' && (!isset($data['role']) || $data['role'] != '1')) {
      $countAdmin = DB::query('
     SELECT count(id) as "count"
      FROM `'.PREFIX.'user`    
      WHERE role = 1
    ');
      if ($row = DB::fetchAssoc($countAdmin)) {
        if ($row['count'] == 1) {// остался один админ    
          $data['role'] = 1; // не даем разжаловать админа, уж лучше плохой чем никакого :-)
        }
      }
    }

    // Перед сменой у пользователя авторизационного email
    // меняем его у его заказов
    if (!empty($data['login_email']) && $userInfo->login_email !== $data['login_email']) {
      $updateOrdersEmailSql = 'UPDATE `'.PREFIX.'order` '.
        'SET `user_email` = '.DB::quote($data['login_email']).' '.
        'WHERE `user_email` = '.DB::quote($userInfo->login_email).';';
      DB::query($updateOrdersEmailSql);
    }

    //логирование
    LoggerAction::logAction('User','updateUser', $data);

    // Новый метод
    $strFields = [
      'login_phone',
      'login_email',
      'name',
      'birthday',
      'sname',
      'pname',
      'address',
      'address_index',
      'address_country',
      'address_region',
      'address_city',
      'address_street',
      'address_house',
      'address_flat',
      'email',
      'phone',
      'nameyur',
      'adress',
      'inn',
      'kpp',
      'bank',
      'bik',
      'ks',
      'rs',
      'pass',
    ];

    $intFields = [
      'blocked',
      'activity',
      'role',
    ];

    $updateParts = [];
    foreach ($data as $field => $value) {
      if (in_array($field, $strFields)) {
        $updateParts[] = '`'.$field.'` = '.DB::quote($value);
        continue;
      }
      if (in_array($field, $intFields)) {
        $updateParts[] = '`'.$field.'` = '.DB::quoteInt($value);
      }
    }

    if ($updateParts) {
      $updateUserSql = 'UPDATE `'.PREFIX.'user` '.
        'SET '.implode(', ', $updateParts).' '.
        'WHERE `id` = '.DB::quoteInt($id);
      DB::query($updateUserSql);
    }

    // Старый метод Большие цифры превращал в 000e0
    // DB::query('
    //   UPDATE `'.PREFIX.'user`
    //   SET '.DB::buildPartQuery($data).'
    //   WHERE id = '.DB::quote($id));

    if (!$authRewrite) {
      foreach ($data as $k => $v) {
        self::$_instance->auth->$k = $v;
      }
      $_SESSION['user'] = self::$_instance->auth;
    }

    return true;
  }

  /**
   * Разлогинивает авторизованного пользователя.
   * <code>
   * User::logout();
   * </code>
   */
  public static function logout($redirect = true) {
    if (MG::getSetting('rememberLogins') == 'true') {
      self::dropLogin($_SESSION['user']->id);
    }
    self::getInstance()->auth = null;
    unset($_SESSION['user']);
    unset($_SESSION['loginAttempt']);
    setcookie (session_id(), "", time() - 3600);
    session_destroy();
    session_write_close();
    if ($redirect) {
      header('Location: '.SITE);
      exit;
    }
  }

  /**
   * Очищает внутренний массив с данными пользователя
   * @access private
   */
  public static function clearAuth() {
    self::getInstance()->auth = new stdClass();;
  }

  /**
   * Аутентифицирует данные, с помощью криптографического алгоритма.
   * <code>
   * User::auth('mail@email.com', '123456');
   * </code>
   * @param string $login емайл или номер телефона.
   * @param string $pass пароль.
   * @param bool $getErrorStatus возвращать ответ в виде массива и при ошибке возвращать причину
   * @return bool|array
   */
  public static function auth($login, $pass, $getErrorStatus = false) {
      $methodAuth = 'login_email';
      if(!preg_match('/\@/', $login)) { 
          $methodAuth = 'login_phone';
          $login = preg_replace('/[\+\-\(\)\ ]/','',$login);
      }
      
      $result = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
      WHERE '.$methodAuth.' ='.DB::quote($login));

    if ($row = DB::fetchObject($result)) {
      if (MG::getSetting('lockAuthorization') == 'true' && substr($row->fails, 0, 2) == 'b~') {
        $blockTime = str_replace('b~', '', $row->fails);		
        if (time()-$blockTime < 0) {
          unset($_SESSION['loginAttempt']);
          if ($getErrorStatus) {
            return array('result'=>false,'reason'=>'user blocked','time'=>$blockTime);
          } else {
            return false;
          }
        }
      }
      if (password_verify($pass, $row->pass)) {
        if ($row->fails || $row->restore) {
          DB::query("UPDATE `".PREFIX."user` 
            SET `fails` = '', 
                `restore` = ''
            WHERE ".$methodAuth." = ".DB::quote($login));
        }
        unset($row->fails);
        unset($row->restore);
        unset($_SESSION['loginAttempt']);
        if ((int)MG::getSetting('checkAdminIp') === 1) {
          $row->hash = md5($row->$methodAuth.$row->date_add.$_SERVER['REMOTE_ADDR']);
        }
        self::$_instance->auth = $row;
        $_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
        $_SESSION['user'] = self::$_instance->auth;
        if (MG::getSetting('rememberLogins') == 'true') {
          self::saveLogin($row->id);
        }
        unset($_POST['pass']);
        unset($_REQUEST['pass']);
        //Логирование авторизации под администратором
       if($row->role == '1'){
          LoggerAction::logAction('Enter', 'auth', array(
              'ip' => $_SERVER['REMOTE_ADDR'],
              'user'=>$_SESSION['user'],
              'id' => ''
          ));
        }
        MG::createHook(__CLASS__."_".__FUNCTION__, $row, array());
        if ($getErrorStatus) {
          return array('result'=>true);
        } else {
          return true;
        }
      } elseif (MG::getSetting('lockAuthorization') == 'true') {
        if (substr($row->fails, 0, 2) == 'b~') {
          $row->fails = '';
        }

        $maxAttempts = intval(MG::getSetting('loginAttempt'));
        if ($maxAttempts < 1) {
          $maxAttempts = 5;
        }
        $currentTime = time();
        $failTime = $currentTime-900;

        $fails = explode('|', $row->fails);
        $newFails = array();
        foreach ($fails as $fail) {
          if ($fail > $failTime) {
            $newFails[] = $fail;
          }
        }
        $newFails[] = $currentTime;
        $_SESSION['loginAttempt'] = count($newFails);

        if (count($newFails) >= $maxAttempts) {// превышен лимит ошибок
          $blockTime = $currentTime + (intval(MG::getSetting('loginBlockTime'))*60);
          $hash = hash('sha256', 'mguzer'.$login.str_replace('.', '', microtime(1)));

          DB::query("UPDATE `".PREFIX."user`
            SET   `fails` = ".DB::quote('b~'.$blockTime).",
                  `restore` = ".DB::quote($hash)."
            WHERE ".$methodAuth." = ".DB::quote($login));

          self::sendUnlockMail($hash, $login);
        } else {// +1 ошибка
          DB::query("UPDATE `".PREFIX."user`
            SET   `fails` = ".DB::quote(implode('|', $newFails))."
            WHERE ".$methodAuth." = ".DB::quote($login));
        }
		
		if($row->role == '1'){
                LoggerAction::logAction('Enter', 'tryAuth', array(
              'ip' => $_SERVER['REMOTE_ADDR'],
              'user' => $row->email,
              'id' => ''
          ));
        }

        if ($getErrorStatus) {
          return array('result'=>false,'reason'=>'user pass failed');
        } else {
          return false;
        }
      }else {
        //Логирование попытки авторизации под администратором
        if($row->role == '1'){
          $arr['ip'] = $_SERVER['REMOTE_ADDR'];
          $arr['user'] = $row->$methodAuth;
          $arr['id'] = '';
          $_SESSION['debugLogger'] = 'true';
          LoggerAction::logAction('Enetr', 'tryAuth', $arr);
          unset($_SESSION['debugLogger']);
        }
      }
    }
    if ($getErrorStatus) {
      return array('result'=>false,'reason'=>'user not found');
    } else {
      return false;
    }
  }

  /**
   * Метод отправки письма администратору с ссылкой для отмены блокировки авторизации.
   * @param string $unlockCode код разблокировки
   * @param string $postEmail почта пользователя
   * @return void 
   */
  private static function sendUnlockMail($unlockCode, $postEmail) {
    $link = '<a href="'.SITE.'/enter?unlock='.$unlockCode.'" target="blank">'.SITE.'/enter?unlock='.$unlockCode.'</a>';
    $siteName = MG::getSetting('sitename');

    $paramToMail = array(
      'siteName' => $siteName,
      'link' => $link,
      'lastEmail' => $postEmail,
    );
    
    $message = MG::layoutManager('email_unclockauth', $paramToMail);
    $emailData = array(
      'nameFrom' => $siteName,
      'emailFrom' => MG::getSetting('noReplyEmail'),
      'nameTo' => 'Администратору сайта '.$siteName,
      'emailTo' => MG::getSetting('adminEmail'),
      'subject' => 'Подбор паролей на сайте '.$siteName.' предотвращен!',
      'body' => $message,
      'html' => true
    );
    
    Mailer::sendMimeMail($emailData);
  }

  /**
   * Отменяет блокировку авторизации пользователя
   * @param  string $unlockCode код разблокировки
   * @return void
   */
  public static function unlock($unlockCode) {
    DB::query("UPDATE `".PREFIX."user` 
      SET `fails` = '', 
          `restore` = ''
      WHERE `restore` = ".DB::quote($unlockCode));

    unset($_SESSION['loginAttempt']);
  }

  /**
   * Получает все данные пользователя из БД по ID.
   * <code>
   * $result = User::getUserById(14);
   * viewData($result);
   * </code>
   * @param int $id id пользователя.
   * @return object
   */
  public static function getUserById($id) {
    $result = false;
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
      WHERE id = "%s"
    ', $id);

    if ($row = DB::fetchObject($res)) {
      $result = $row;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает все данные пользователя из БД по email.
   * <code>
   * $result = User::getUserById('mail@email.com');
   * viewData($result);
   * </code>
   * @param string $email почта пользователя.
   * @return object
   */
  public static function getUserInfoByEmail($email, $type = 'email') { //login_email
    $result = false;
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
      WHERE '.$type.' = "%s"
    ', $email);

    if ($row = DB::fetchObject($res)) {
      $result = $row;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
    /**
   * Получает все данные пользователя из БД по номеру телефона.
   * <code>
   * $result = User::getUserInfoByPhone('+7-999-999-99-99');
   * viewData($result);
   * </code>
   * @param string $phone номер телефона пользователя.
   * @return object
   */
  public static function getUserInfoByPhone($phone, $type = 'phone') { //login_phone
    $result = false;
    $phone = preg_replace('/[\+\.\-\(\)\ ]/','',$phone);
    // если в строке нет эмодзи, то можно выполнять SQL запрос
    if(!MG::hasEmoji($phone)){
      $res = DB::query('
        SELECT *
        FROM `'.PREFIX.'user`
        WHERE `'.$type.'` = '.DB::quote($phone).'AND `'.$type.'` <> 0'); // перевод только в цифры и последующее сравнение
  
      if ($row = DB::fetchObject($res)) {
        $result = $row;
      }
     }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Проверяет, авторизован ли текущий пользователь.
   * <code>
   * $result = User::isAuth();
   * var_dump($result);
   * </code>
   * @return bool
   */
  public static function isAuth() {
    if (self::getThis()) {
      return true;
    }
    return false;
  }

  /**
   * Получает список пользователей.
   * <code>
   * $result = User::getListUser();
   * viewData($result);
   * </code>
   * @return array
   */
  public static function getListUser() {
    $result = false;
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
    ');

    while ($row = DB::fetchObject($res)) {
      $result[] = $row;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
    /**
   * Проверяет права пользователя на выполнение ajax запроса.
   * <code>
   * USER::AccessOnly('1,4','exit()');
   * </code>
   * @param string $roleMask - строка с перечисленными ролями, которые имеют доступ,
   *   если параметр не передается, то доступ открыт для всех.
   *  1 - администратор,
   *  2 - пользователь,
   *  3 - менеджер,
   *  4 - модератор
   * @param bool нужно ли прерывать движок
   * @return bool or exit;
   * @deprecated
   */
  public static function AccessOnly($roleMask="1,2,3,4",$exit=null) {
    $thisRole = empty(self::getThis()->role)?'2':self::getThis()->role;
    
    if(strpos($roleMask,(string)$thisRole)!==false) {
      return true;
  	}
  	// мод для аяксовых запросов.
  	if($exit) {
  	  exit();
  	}
    return false;
  }
  
  /**
   * Возвращает дату последней регистрации пользователя.
   * <code>
   * $result = User::getMaxDate();
   * viewData($result);
   * </code>
   * @return array
   */
  public static function getMaxDate() {
    $result = new stdClass();
    $res = DB::query('
      SELECT MAX(date_add) as res 
      FROM `'.PREFIX.'user`');

    if ($row = DB::fetchObject($res)) {
      $result = $row->res;
    }

    return $result;
  }

  /**
   * Возвращает дату первой регистрации пользователя.
   * <code>
   * $result = User::getMinDate();
   * viewData($result);
   * </code>
   * @return array
   */
  public static function getMinDate() {
    $result = new stdClass();
    $res = DB::query('
      SELECT MIN(date_add) as res 
      FROM `'.PREFIX.'user`'
    );
    if ($row = DB::fetchObject($res)) {
      $result = $row->res;
    }
    return $result;
  }
  
  /**
   * Получает все email пользователя из БД.
   * <code>
   * $result = User::searchEmail('mail@email.com');
   * viewData($result);
   * </code>
   * @param string $email почтовый адрес пользователя.
   * @return array
   */
  public static function searchEmail($email) {
    $result = false;
    $res = DB::query('
      SELECT `email`
      FROM `'.PREFIX.'user`
      WHERE email LIKE '.DB::quote($email,1).'%');

    if ($row = DB::fetchObject($res)) {
      $result = $row;
    }
    return $result;    
    }

  /**
   * Выгружает список пользователей в CSV файл.
   * <code>
   * $listUserId = array(1, 5, 9, 15);
   * $result = User::exportToCsvUser($listUserId);
   * viewData($result);
   * </code>
   * @param array $listUserId массив с id пользователей для выгрузки (необязаьельно)
   * @return string
   */
  public static function exportToCsvUser($listUserId = array()) {
  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream;");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=data.csv");
    header("Content-Transfer-Encoding: binary ");
    $csvText = '"Телефон";';
    $csvText .= '"Email";"Контактный email";"Имя";"Фамилия";"Адрес";"Контактный телефон";"День рождения";"Статус";"Группа";"Дата регистрации";"Доступ к кабинету";"Юр.лицо";"Юр.адрес";"ИНН";"КПП";"Банк";"БИК";"К/Сч";"Р/Сч";"IP";';
    $opFieldsM = new Models_OpFieldsUser('get');
    unset($_SESSION['csvOpUser']);
    $fields = $opFieldsM->get();
    if (is_array($fields)) {
      foreach ($fields as $id => $value) {
        $_SESSION['csvOpUser'][] = $id;
        $csvText .= '"'.$value['name'].'";';
      }
    }
    // конец заголовков
    $csvText .= "\n";
    
    Storage::$noCache = true;
    $page = 1;
    $count = 0;
    // получаем максимальное количество заказов, если выгрузка всего ассортимента
    if(empty($listUserId)) {
      $res = DB::query('
        SELECT count(id) as count
        FROM `'.PREFIX.'user` WHERE id > 0');
      if ($user = DB::fetchAssoc($res)) {
        $count = $user['count'];
      }
      $maxCountPage = ceil($count / 500);
    } else {
      $maxCountPage = ceil(count($listUserId) / 500);
    }
    //$listId = implode(',', $listUserId);
    for ($page = 1; $page <= $maxCountPage; $page++) {      
      URL::setQueryParametr("page", $page);
      $sql = 'SELECT * FROM `'.PREFIX.'user` WHERE id > 0';
      if(!empty($listUserId)) {  
        $sql .= ' AND `id` IN ('.DB::quoteIN($listUserId,1).')';
      }
      $navigator = new Navigator($sql, $page, 500); //определяем класс  
      $users = $navigator->getRowsSql();
      foreach ($users as $row) {
        $csvText .= self::addUserToCsvLine($row);
        }
      }
    
    $csvText = substr($csvText, 0, -2); // удаляем последний символ '\n'
        
    $csvText = mb_convert_encoding($csvText, "WINDOWS-1251", "UTF-8");
    if(empty($listUserId)) {
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
   * @access private
   * @param array $row запись о пользователе.
   * @return string
   */
  public static function addUserToCsvLine($row) {
    $row['address'] = '"' . str_replace("\"", "\"\"", $row['address']) . '"';
    $row['adress'] = '"' . str_replace("\"", "\"\"", $row['adress']) . '"';
    $row['login_email'] = '"' . str_replace("\"", "\"\"", $row['login_email']) . '"';
    $row['login_phone'] = '"' . str_replace("\"", "\"\"", $row['login_phone']) . '"';
    $row['email'] = '"' . str_replace("\"", "\"\"", $row['email']) . '"';
    $row['role'] = '"' . str_replace("\"", "\"\"", self::$groupName[$row['role']]) . '"';
    $row['name'] = '"' . str_replace("\"", "\"\"", $row['name']) . '"';
    $row['sname'] = '"' . str_replace("\"", "\"\"", $row['sname']).'"';
    $row['phone'] = '"' . str_replace("\"", "\"\"", $row['phone']) . '"';
    $row['date_add'] = '"' . str_replace("\"", "\"\"", date('d.m.Y', strtotime($row['date_add']))).'"';
    $row['blocked'] = '"' . str_replace("\"", "\"\"",  self::$accessStatus[$row['blocked']]).'"';
    $activity = $row['activity'] == 1 ? 'Подтвердил регистрацию' : 'Не подтвердил регистрацию';
    $row['activity'] = '"' . str_replace("\"", "\"\"", $activity) . '"';
    $row['inn'] = '"' . str_replace("\"", "\"\"", $row['inn']) . '"';
    $row['kpp'] = '"' . str_replace("\"", "\"\"", $row['kpp']) . '"';
    $row['nameyur'] = '"' . str_replace("\"", "\"\"", $row['nameyur']) . '"';
    $row['bank'] = '"' . str_replace("\"", "\"\"", $row['bank']) . '"';
    $row['bik'] = '"' . str_replace("\"", "\"\"", $row['bik']) . '"';
    $row['ks'] = '"' . str_replace("\"", "\"\"", $row['ks']) . '"';
    $row['rs'] = '"' . str_replace("\"", "\"\"", $row['rs']) . '"';
    $row['birthday'] = '"' . str_replace("\"", "\"\"", strtotime($row['birthday']) ? date('d.m.Y', strtotime($row['birthday'])) : '') . '"';
    $row['ip'] = '"' . str_replace("\"", "\"\"", $row['ip']) . '"';
    $csvText = $row['login_phone'] . ";";
    $csvText .= $row['login_email'] . ";" .
      $row['email'] . ";" .
      $row['name'] . ";" .
      $row['sname'] . ";" .
      $row['address'] . ";" .
      $row['phone'] . ";" .
      $row['birthday'] . ";" .
      $row['activity'] . ";" .
      $row['role'] . ";" .
      $row['date_add'] . ";" .
      $row['blocked'] . ";" .
      $row['nameyur'] . ";" .
      $row['adress'] . ";" .
      $row['inn'] . ";" .
      $row['kpp'] . ";" .
      $row['bank'] . ";" .
      $row['bik'] . ";" .
      $row['ks'] . ";" .
      $row['rs'] . ";" .
      $row['ip'] . ";";
      // доп поля
      $opFieldsM = new Models_OpFieldsUser($row['id']);
      if (!empty($_SESSION['csvOpUser']) && is_array($_SESSION['csvOpUser'])) {
        foreach ($_SESSION['csvOpUser'] as $fieldsId) {
          $csvText .= '"'.$opFieldsM->getHumanView($fieldsId, true).'";';
        }
      }
      // конец строки
    $csvText .= "\n";

    return $csvText;
  }

  /**
   * Получает права доступа пользователей сайта к различным разделам системы.
   * <code>
   * $result = USER::access('product');
   * viewData($result);
   * </code>
   * @param string $zone название зоны доступа 
   *   1 admin_zone - админка,
   *   2 product - товары,
   *   3 page - страницы,
   *   4 category - категории,
   *   5 order - заказы,
   *   6 user - покупатели,
   *   7 plugin - плагины,
   *   8 setting - настройки,
   *   9 wholesales - оптовые цены
   * @return int число показывающее уровень доступа (0 - нет доступа; 1 - только просмотр; 2 - просмотр и редактирование(кроме admin_zone и wholesales))
   */
  public static function access($zone = '') {
    $role = !empty($_SESSION['user']->role) ? $_SESSION['user']->role : 2;
    if(!$userAccessRule = MG::get('userAccessRule')) {
      //$res = DB::query('SELECT * FROM '.PREFIX.'user_group WHERE id = '.DB::quote($role));
      if($zone == 'admin_zone'){
        if((isset($_SESSION['user']->login_email) || isset($_SESSION['user']->login_phone)) && isset($_SESSION['user']->id)){
          $res = DB::query('SELECT * FROM `'.PREFIX.'user_group` as `g`
          LEFT JOIN `'.PREFIX.'user` as `u` 
          ON `u`.`role` = `g`.`id`
          WHERE `g`.`id` = '.DB::quote($role).' AND (`u`.`login_email` = '.DB::quote($_SESSION['user']->login_email).' OR `u`.`login_phone` = '.DB::quote($_SESSION['user']->login_phone).') AND `u`.`id` = '.DB::quote($_SESSION['user']->id));
        }else{
          $res = DB::query('SELECT * FROM '.PREFIX.'user_group WHERE id = '.DB::quote($role));
        }
      }else{
        $res = DB::query('SELECT * FROM '.PREFIX.'user_group WHERE id = '.DB::quote($role));
      }   
      if($row = DB::fetchAssoc($res)) {
        $userAccessRule = $row;
      }
      MG::set('userAccessRule', $userAccessRule);
    }
    return $userAccessRule[$zone];
    

  }

  /**
   * Возвращает состав заказов пользователя для составления статистика покупок
   *
   * @param string $email
   * @return array
   */
  public static function getUserOrderContent($email) {
    // сумма оплаченных заказов
    $orderSumm = 0;
    $res = DB::query('SELECT SUM(summ_shop_curr) AS summ FROM '.PREFIX.'order WHERE user_email = '.DB::quote($email).' AND status_id IN (2, 5)');
    if($row = DB::fetchAssoc($res)) {
      $orderSumm = $row['summ'];
    }
    // содержимое заказов
    $orderContent = $sort = array();
    $res = DB::query('SELECT order_content, `number`, add_date FROM '.PREFIX.'order WHERE user_email = '.DB::quote($email).' AND status_id IN (2, 5)');
    while($row = DB::fetchAssoc($res)) {
      $tmp = unserialize(stripcslashes($row['order_content']));
      foreach ($tmp as $value) {
        if (!isset($orderContent[$value['code']]['count'])) {$orderContent[$value['code']]['count'] = 0;}
        if (!isset($orderContent[$value['code']]['price'])) {$orderContent[$value['code']]['price'] = 0;}
        $orderContent[$value['code']]['name'] = $value['name'];
        $orderContent[$value['code']]['count'] += $value['count'];
        $orderContent[$value['code']]['price'] += $value['price'] * $value['count'];
        $orderContent[$value['code']]['number'] = $row['number'];
        $orderContent[$value['code']]['add_date'] = $row['add_date'];
        $orderContent[$value['code']]['code'] = $value['code'];
      }
    }
    // сортировка содержимого заказов
    foreach($orderContent as $key => $arr) {
      $sort[$key] = $arr['add_date'];
    }
    array_multisort($sort, SORT_NUMERIC, $orderContent);
    $data['summ'] = $orderSumm;
    $data['products'] = $orderContent;
    return $data;
  }

  /**
   * Возвращает список ответственных за выбранные разделы магазины (товары, заказы, пользователи и т.д.)
   *
   * <code>
   * $data = User::getOwners('order');
   * viewData($data);
   * <code>
   * @param string $type тип раздела
   * @param boolean $filter
   * @return array
   */
  public static function getOwners($type, $filter = false) {
    $roles = $owners = array();
    $res = DB::query('SELECT id FROM '.PREFIX.'user_group WHERE id != 1 AND admin_zone = 1');
    while($row = DB::fetchAssoc($res)) {
      $roles[] = $row['id'];
    }
    $lang = MG::get('lang');
    if($filter) $owners[0] = $lang['LAYOUT_CATALOG_66'];
    $res = DB::query('SELECT id, name, email FROM '.PREFIX.'user WHERE role IN ('.DB::quoteIN($roles).')');
    while($row = DB::fetchAssoc($res)) {
      $owners[$row['id']] = $row['name'].' ('.$row['email'].')';
    }
    return $owners;
  }

  // Обновление или запись информации о логине в базу и куки
  public static function saveLogin($userId) {
    $check = self::checkLoginCookie($userId);
    extract($check);

    if ($access) {
      DB::query("UPDATE `".PREFIX."user_logins`
          SET `last_used` = ".DB::quoteInt(time())."
          WHERE `created_at` = ".DB::quoteInt($createdAt));

      setcookie('rememberme', $_COOKIE['rememberme'], (time()+(intval(MG::getSetting('rememberLoginsDays'))*86400)), "/");
      return true;
    }

    $createdAt = str_replace('.', '', microtime(1));
    $access = '';
    $chars = range('!', '~');
    for ($i = 0; $i < 32; $i++) {
      $char = $chars[array_rand($chars)];
      if ($char == ':') {
        $i--;
      } else {
        $access .= $char;
      }
    }

    DB::query("INSERT IGNORE INTO `".PREFIX."user_logins`
      (`created_at`, `user_id`, `access`, `last_used`) 
      VALUES (
        ".DB::quote($createdAt).", 
        ".DB::quoteInt($userId).", 
        ".DB::quote(password_hash($access, PASSWORD_DEFAULT)).", 
        ".DB::quoteInt(time())."
      )");

    $data = $createdAt.':'.$access;
    setcookie('rememberme', $data, (time()+(intval(MG::getSetting('rememberLoginsDays'))*86400)), "/");
  }

  // Восстановление информации о логине из базы и куков
  private static function restoreLogin() {
    $check = self::checkLoginCookie('', true);
    extract($check);

    if ($access) {
      $res = DB::query("SELECT * FROM `".PREFIX."user` 
        WHERE `id` = ".DB::quoteInt($userId));

      if ($row = DB::fetchObject($res)) {
        $_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
        $_SESSION['user'] = $row;
        MG::createHook(__CLASS__."_auth", $row, array());

        DB::query("UPDATE `".PREFIX."user_logins`
          SET   `last_used` = ".DB::quoteInt(time()).",
                `fails` = ''
          WHERE `created_at` = ".DB::quoteInt($createdAt));

        setcookie('rememberme', $_COOKIE['rememberme'], (time()+(intval(MG::getSetting('rememberLoginsDays'))*86400)), "/");
      }
    }
  }

  // Удаление информации о логине из базы и куков
  private static function dropLogin($userId) {
    $check = self::checkLoginCookie($userId);
    extract($check);

    if ($access) {
      DB::query("DELETE FROM `".PREFIX."user_logins`
        WHERE `created_at` = ".DB::quoteInt($createdAt));
    }

    setcookie('rememberme', null, -1, '/');
  }

  // Проверка информации о логине из куков по базе
  private static function checkLoginCookie($userId = '', $failControl = false) {
    if (isset($_COOKIE['rememberme']) && $uncompressed = $_COOKIE['rememberme']) {
      $uncompressed = explode(':', $uncompressed);
      if (count($uncompressed) == 2) {
        if ($userId) {
          $userIdLine = " AND `user_id` = ".DB::quoteInt($userId);
        } else {
          $userIdLine = "";
        }

        $createdAt = $uncompressed[0];
        $access = $uncompressed[1];
        $res = DB::query("SELECT `user_id`, `access`, `fails` FROM `".PREFIX."user_logins`
          WHERE `created_at` = ".DB::quoteInt($createdAt).
                $userIdLine
              );
        if ($row = DB::fetchAssoc($res)) {
          if (password_verify($access, $row['access'])) {
            $return = array('createdAt'=>$createdAt,'access'=>$row['access']);
            if (!$userId) {
              $return['userId'] = $row['user_id'];
            }
            return $return;
          } elseif ($failControl) {
            $failTime = time()-30*60;// за последние 30 минут
            $fails = explode('|', $row['fails']);
            $newFails = array();
            foreach ($fails as $fail) {
              if ($fail > $failTime) {
                $newFails[] = $fail;
              }
            }
            $newFails[] = time();
            if (count($newFails) > 3) {// более 3 ошибок
              DB::query("DELETE FROM `".PREFIX."user_logins`
                WHERE `created_at` = ".DB::quoteInt($createdAt));
            } else {
              DB::query("UPDATE `".PREFIX."user_logins`
                SET   `fails` = ".DB::quote(implode('|', $newFails))."
                WHERE `created_at` = ".DB::quoteInt($createdAt));
            }

            setcookie('rememberme', null, -1, '/'); 
          }
        }
      }
    }
    return array('createdAt'=>'','access'=>'');
  }

  public static function getUserEmailByPhone($phone) {
    $phone = intval(preg_replace('/\D+/', '', $phone));
    $userEmailSql = 'SELECT `login_email` FROM `'.PREFIX.'user` '.
      'WHERE `login_phone` = '.DB::quote($phone).';';
    $userEmailQuery = DB::query($userEmailSql);

    if ($userEmailResult = DB::fetchAssoc($userEmailQuery)) {
      return $userEmailResult['login_email'];
    }

    return false;
  }
}
