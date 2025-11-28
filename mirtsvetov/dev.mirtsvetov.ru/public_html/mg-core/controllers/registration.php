<?php

/**
 * Контроллер: Registration
 *
 * Класс Controllers_Registration обрабатывает действия пользователей на странице регистрации нового пользователя.
 * - Проверяет корректность данных;
 * - Регистрирует учетную запись пользователя.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Registration extends BaseController {

  private $error;
  private $userData;
  private $fPass;

  function __construct() {
    $this->fPass = new Models_Forgotpass;
    $form = true; // Отображение формы.
    $error = $message = '';

    //запрещенное имя при регистрации, используется ботом
    if($_POST['name'] == 'oyfuigef'){
      exit();
    }

    // Оброботка действий пользователя при регистрации.
    if (isset($_POST['registration'])) {

        //Убираем пробелы покраям
        $this->trimMailAndPass();
        $methodRegistration = '';
        // Если данные введены верно.
        if (!$this->unValidForm()) {
            // Определение способа регистрации
            if($this->selectEmailOrPhone($this->userData)){
                // выбрана регистрация по email
                $methodRegistration = 'Email';
                USER::add($this->userData);        
                if (MG::getSetting('confirmRegistration'.$methodRegistration) == 'true'){
                  // $message = '<span class="succes-reg">Вы успешно зарегистрировались! Для активации пользователя Вам необходимо перейти по ссылке высланной на Ваш электронный адрес <strong>'.$this->userData['email'].'</strong></span>';
                  $message = '<span class="succes-reg">'.MG::restoreMsg('msg__reg_success_email',array('#EMAIL#' => $this->userData['email'])).'</span>';
                  // Рассылаем письма со ссылкой для подтверждения регистрации.          
                  $this->_sendActivationMail($this->userData['email']);
                } else{
                  // $message = '<span class="succes-reg">Вы успешно зарегистрировались! <a href="'.SITE.'/enter">Вход в личный кабинет</a></strong></span>';
                  $message = '<span class="succes-reg">'.MG::restoreMsg('msg__reg_success',array('#LINK#' => SITE.'/enter')).'</span>';
                }
                $form = false;
            } else {
                $methodRegistration = 'Phone';
                $methonRegistrationType = MG::getSetting('confirmRegistrationPhoneType');
                // выбрана регистрация по номеру телефона
                if(empty($this->userData['phone'])) {
                    $this->userData['phone'] = $this->changeSymbolPhone($this->userData['email']);
                    $this->userData['email'] = '';
                }
                if (MG::getSetting('confirmRegistration'.$methodRegistration) == 'true') {//тут проверка на настройки регистрации
                  // Вывод формы для ввода пароля из смс
                  // Если пользователь создан, но не подтвердил регистрацию
                  if (USER::getUserInfoByPhone($this->userData['phone'],'login_phone')) {
                    $_SESSION['newUserData'] = $this->userData;
                    if (empty($_SESSION['userPhone'])) {
                      if($methonRegistrationType === 'call'){
                        $message = $this->_sendActivationPhone($this->userData['phone'], 'call');
                      }
                      else{
                        $message = $this->_sendActivationPhone($this->userData['phone']);
                      }
                    } else {
                      if($methonRegistrationType == 'call'){
                        $message = $this->_sendActivationPhone($this->userData['phone'], 'replayCall');
                      }
                      else{
                        $message = $this->_sendActivationPhone($this->userData['phone'],'replay');  
                      }
                    }
                  } else {
                    $this->userData['login_phone'] = $this->userData['phone'];
                    USER::add($this->userData);
                    if($methonRegistrationType == 'call'){
                      $message = $this->_sendActivationPhone($this->userData['phone'], 'call');
                    }
                    else{
                      $message = $this->_sendActivationPhone($this->userData['phone']);
                    }
                  }
                  $form = false;
                    //$message = '<span class="succes-reg">Вам пришло сообщение СМС'.MG::restoreMsg('msg__reg_success_email',array('#EMAIL#' => $this->userData['email'])).'</span>';

                } else{
                  // Выполнена регистрация без смс
                  // $message = '<span class="succes-reg">Вы успешно зарегистрировались! <a href="'.SITE.'/enter">Вход в личный кабинет</a></strong></span>';
                  $this->userData['login_phone'] = $this->userData['phone'];
                  USER::add($this->userData);
                  $message = '<span class="succes-reg">'.MG::restoreMsg('msg__reg_success',array('#LINK#' => SITE.'/enter')).'</span>';
                  $form = false;
                }
            }
        } else {
            $error = $this->error;
            $form = true;
        }
    }

    // Обработка действий перехода по ссылки.
    if (URL::getQueryParametr('id')) {
      $urlGetId = URL::getQueryParametr('id');
      $userInfo = USER::getUserById($urlGetId);
      $hash = URL::getQueryParametr('sec');

      // Если присланный хэш совпадает с хэшом из БД для соответствующего id.
      if ($userInfo->restore == $hash) {

        // Меняет в БД случайным образом хэш, делая невозможным повторный переход по ссылки.
        $this->fPass->sendHashToDB($userInfo->email, $this->fPass->getHash('0'));
        // $message = 'Ваша учетная запись активирована. Теперь Вы можете <a href="'.SITE.'/enter">войти в личный кабинет</a> используя логин и пароль заданный при регистрации.';
        $message = MG::restoreMsg('msg__reg_activated',array('#LINK#' => SITE.'/enter'));
        $form = false;
        $this->fPass->activateUser(URL::getQueryParametr('id'));
        $currentUser = USER::getThis();
        if ($currentUser) {
          $currentUser->activity = 1;
        }

        //отправляет регистрацию в MailChimp, если надо
        /*$mailChimp = unserialize(stripslashes(MG::getSetting('mailChimp')));

        if ($mailChimp['uploadNew'] == 'true') {
          MailChimp::uploadOne($mailChimp['api'], $mailChimp['listId'], $mailChimp['perm'], $userInfo->email, $userInfo->name, $userInfo->sname, $userInfo->birthday);
        }*/

      } else {
        // $error = 'Некорректная ссылка. Повторите активацию!';
        $error = MG::restoreMsg('msg__reg_wrong_link');
        $form = false;
      }
    }
    // Обработка действий ввода кода из смс.
    if ($smsPrametr = URL::getQueryParametr('sms')) {
      if(!empty($_SESSION['userPhone']) && $_SESSION['userPhone'] == $smsPrametr){
        if (!empty($_POST['smspass']) && $_SESSION['confirmSMS'] == md5($_POST['smspass'])){

          $userInfo = USER::getUserInfoByPhone($smsPrametr,'login_phone');
          $this->fPass->activateUser($userInfo->id);
          User::getThis()->activity = 1;
          
          if (!empty($_SESSION['newUserData'])) {
            $data['name'] = $_SESSION['newUserData']['name'];
            $data['pass'] = password_hash($_SESSION['newUserData']['pass'], PASSWORD_DEFAULT);
            User::update($userInfo->id, $data);
            unset($_SESSION['newUserData']);
          }
          unset($_SESSION['confirmSMS']);
          unset($_SESSION['userPhone']);
          unset($_SESSION['countTry']);

          //отправляет регистрацию в MailChimp, если надо
         /* $mailChimp = unserialize(stripslashes(MG::getSetting('mailChimp')));

          if ($mailChimp['uploadNew'] == 'true') {
            MailChimp::uploadOne($mailChimp['api'], $mailChimp['listId'], $mailChimp['perm'], $userInfo->email, $userInfo->name, $userInfo->sname, $userInfo->birthday);
          }*/

          $message = MG::restoreMsg('msg__reg_activated',array('#LINK#' => SITE.'/enter'));
          $form = false;

        } else {
          $currentTime = new DateTime;
          $seconds = $_SESSION['timeSMS'] - $currentTime->getTimestamp();

          // $_SESSION['countTry'] количество раз неправильно введенных подтверджений
          if(!empty($_POST['resendSMS']) && $_POST['resendSMS'] == md5($_SESSION['countTry']) && strtolower(URL::getQueryParametr('capcha')) == strtolower($_SESSION['capcha']) ||
             !empty($_SESSION['timeSMS']) && !empty($_POST['timeSMS']) && $_POST['timeSMS'] == $_SESSION['timeSMS'] && $seconds <= 0){
                  // $error = "Код подтверждения повторно отправлен на номер ".$smsPrametr;
                  $error = MG::restoreMsg('msg__reg_sms_resend').' '.$smsPrametr;
                  $message = $this->_sendActivationPhone($smsPrametr,'send');
          } else {
              $urlCapcha = URL::getQueryParametr('capcha');
              if (!empty($urlCapcha) && strtolower($urlCapcha) != strtolower($_SESSION['capcha']) && $_SESSION['countTry'] > 5) {
                  // $error .= "<span class='error-captcha-text'>Текст с картинки введен неверно!</span>";
                  $error .= "<span class='error-captcha-text'>".MG::restoreMsg('msg__captcha_incorrect')."</span>";
                  $message = $this->_sendActivationPhone($smsPrametr,'replay');
              }
              else{
                  $error = 'Неверный код подтверждения';
                  //$error = MG::restoreMsg('msg__reg_sms_errore');
                  $message = $this->_sendActivationPhone($smsPrametr,'replay');
              }
          }
          $form = false;
        }
      } else {
        // $error = 'Ссылка не существует!';
        $error = MG::restoreMsg('msg__reg_wrong_link');
        $form = false;
      }
    }


    if ($callParametr = URL::getQueryParametr('call')) {
      if(!empty($_SESSION['userPhone']) && $_SESSION['userPhone'] == $callParametr){
        if (!empty($_POST['callpass']) && $_SESSION['confirmCall'] == md5($_POST['callpass'])){

          $userInfo = USER::getUserInfoByPhone($callParametr,'login_phone');
          $this->fPass->activateUser($userInfo->id);
          User::getThis()->activity = 1;
          
          if (!empty($_SESSION['newUserData'])) {
            $data['name'] = $_SESSION['newUserData']['name'];
            $data['pass'] = password_hash($_SESSION['newUserData']['pass'], PASSWORD_DEFAULT);
            User::update($userInfo->id, $data);
            unset($_SESSION['newUserData']);
          }
          unset($_SESSION['confirmCall']);
          unset($_SESSION['userPhone']);
          unset($_SESSION['countTry']);

          //отправляет регистрацию в MailChimp, если надо
          /*$mailChimp = unserialize(stripslashes(MG::getSetting('mailChimp')));

          if ($mailChimp['uploadNew'] == 'true') {
            MailChimp::uploadOne($mailChimp['api'], $mailChimp['listId'], $mailChimp['perm'], $userInfo->email, $userInfo->name, $userInfo->sname, $userInfo->birthday);
          }*/

          $message = MG::restoreMsg('msg__reg_activated',array('#LINK#' => SITE.'/enter'));
          $form = false;

        } else {
          $currentTime = new DateTime;
          $seconds = $_SESSION['timeCall'] - $currentTime->getTimestamp();

          // $_SESSION['countTry'] количество раз неправильно введенных подтверджений
          if(!empty($_POST['reCallBtn']) && $_POST['reCallBtn'] == md5($_SESSION['countTry']) && strtolower(URL::getQueryParametr('capcha')) == strtolower($_SESSION['capcha']) ||
             !empty($_SESSION['timeCall']) && !empty($_POST['timeCall']) && $_POST['timeCall'] == $_SESSION['timeCall'] && $seconds <= 0){
                  // $error = "Код подтверждения повторно отправлен на номер ".$callParametr;
                  $error = MG::restoreMsg('msg__reg_sms_resend').' '.$callParametr;
                  $message = $this->_sendActivationPhone($callParametr,'call');
          } else {
              $urlCapcha = URL::getQueryParametr('capcha');
              if (!empty($urlCapcha) && strtolower($urlCapcha) != strtolower($_SESSION['capcha']) && $_SESSION['countTry'] > 5) {
                  // $error .= "<span class='error-captcha-text'>Текст с картинки введен неверно!</span>";
                  $error .= "<span class='error-captcha-text'>".MG::restoreMsg('msg__captcha_incorrect')."</span>";
                  $message = $this->_sendActivationPhone($callParametr,'replayCall');
              }
              else{
                  $error = 'Неверный код подтверждения';
                  //$error = MG::restoreMsg('msg__reg_sms_errore');
                  $message = $this->_sendActivationPhone($callParametr,'replayCall');
              }
          }
          $form = false;
        }
      } else {
        // $error = 'Ссылка не существует!';
        $error = MG::restoreMsg('msg__reg_wrong_link');
        $form = false;
      }
    }
    // Обработка действий при запросе на повторную активацию.
    if (!empty($_POST['reActivate'])) {
      $thisUserInfo = USER::getThis();
      $email = URL::getQueryParametr('activateEmail');
      if (USER::getUserInfoByPhone($email)->activity == 0 && $thisUserInfo->login_phone == $email) {
        // $_SESSION['countTry'] отвечает за контроль количества ввод кодов из смс
        // Если смс было отправлено, то переадресуем на страницу ввода кода
        if(empty($_SESSION['countTry'])) $message = $this->_sendActivationPhone($email,'send');
        else $message = $this->_sendActivationPhone($email,'replay');
        $form = false;
      } else {
        if (USER::getUserInfoByEmail($email)->activity == 0 && $thisUserInfo->login_email == $email) {
          $this->_sendActivationMail($email);
          // $message = 'Для активации пользователя Вам необходимо перейти по ссылке высланной на Ваш электронный адрес '.$this->userData['email'];
          $message = MG::restoreMsg('msg__reg_link',array('#EMAIL#' => $this->userData['email']));
          $form = false;
        }
        else {
          // $error = 'К сожалению, такой логин не найден. Если вы уверены, что данный логин существует, свяжитесь, пожалуйста, с нами.';
          $error = MG::restoreMsg('msg__wrong_login');
          $form = false;
        }
      }
    }

    $this->data = array(
      'error' => $error, // Сообщение об ошибке.
      'message' => $message, // Информационное сообщение
      'form' => $form, // Отображение формы.
      'meta_title' => 'Регистрация',
      'meta_keywords' => "регистрация, зарегистрироваться",
      'meta_desc' => "Зарегистрируйтесь в системе, чтобы получить дополнительные возможности, такие как просмотр состояния заказа",
    );
  }

  /**
   * Метод проверяет корректность данных введенных в форму регистрации.
   * <code>
   * $model = new Controllers_Registration();
   * $res = $model->unValidForm();
   * var_dump($res);
   * </code>
   * @return bool
   */
  public function unValidForm() {
    if (!URL::getQueryParametr('name')) {
      $name = 'Пользователь';
    } else {
      $name = URL::getQueryParametr('name');
    }
    
    $this->userData = array(
      'pass' => URL::getQueryParametr('pass'),
      'email' => URL::getQueryParametr('email'),
      'role' => 2,
      'name' => $name,
      'sname' => URL::getQueryParametr('sname'),
      'address' => URL::getQueryParametr('address'),
      'phone' => URL::getQueryParametr('phone'),
      'ip' => URL::getQueryParametr('ip'),
    );

    $registration = new Models_Registration;

    if ($err = $registration->validDataForm($this->userData)) {
      $this->error = $err;
      return true;
    }
    return false;
  }

  /**
   * Метод отправки письма для активации пользователя.
   * @param string $userEmail почта пользователя
   * @return void 
   */
  private function _sendActivationMail($userEmail) {
    $userId = USER::getUserInfoByEmail($userEmail,'login_email')->id;
    $hash = $this->fPass->getHash($userEmail);
    $this->fPass->sendHashToDB($userEmail, $hash);
    $siteName = MG::getSetting('sitename');
    $link = '<a href="'.SITE.'/registration?sec='.$hash.'&id='.$userId.'" target="blank">'.SITE.'/registration?sec='.$hash.'&id='.$userId.'</a>';

    $paramToMail = array(
      'siteName' => $siteName,
      'userEmail' => $userEmail,
      'link' => $link,
    );

    $message = MG::layoutManager('email_registry', $paramToMail);
    $emailData = array(
      'nameFrom' => $siteName,
      'emailFrom' => MG::getSetting('noReplyEmail'),
      'nameTo' => 'Пользователю сайта '.$siteName,
      'emailTo' => $userEmail,
      'subject' => 'Активация пользователя на сайте '.$siteName,
      'body' => $message,
      'html' => true
    );

    $this->fPass->sendUrlToEmail($emailData);
  }
  /**
   * Метод отправки смс сообщения для активации пользователя.
   * @param string $userEmail почта пользователя
   * @return void 
   */
  private function _sendActivationPhone($userPhone, $mode='send') {
    switch ($mode) {
        case 'send': // первичная отпрвка пароля
            //$secretSms = '123456'; // в сессию
            $secretSms = rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
            $_SESSION['confirmSMS'] = md5($secretSms);
            $_SESSION['userPhone'] = $userPhone;
            // отправляем смс пользователю
            $result['message'] = $secretSms;
            $result['phone'] = $userPhone;
            $result['send'] = false; // метка состояния отправки письма
            $args = func_get_args();
            $result = MG::createHook('Method_confirm_sms', $result, $args);
            
            if($result['send'] == false){
                // Если смс не отправлено, удаляем пользователя и выводим сообщение
                $userId = USER::getUserInfoByPhone($userPhone,'login_phone')->id;
                USER::delete($userId);
                unset($_SESSION['confirmSMS']);
                unset($_SESSION['userPhone']);
                unset($_SESSION['countTry']);
                unset($_SESSION['timeSMS']);
                //return $error = 'Сервис отправки смс временно не доступен. Зарегистрируйтесь используя email, либо свяжитесь с нами.';
                return $error = MG::restoreMsg('msg__reg_not_sms');
            }
            if (!empty($_SESSION['timeSMS'])) {
              $_SESSION['timeSMS'] = time() + 180;
            } else {
              $_SESSION['timeSMS'] = time() + 60;
            }
            $_SESSION['countTry'] = 1; // количество попыток ввода кода пользователем
            break;

        case 'call': // первичная отпрвка пароля
          $result['phone'] = $userPhone;
          $result['call'] = false; // метка состояния звонка
          $_SESSION['userPhone'] = $userPhone;
          $result = MG::createHook('Method_confirm_call', $result, $args);
          $_SESSION['confirmCall'] = md5($result['code']);

          if($result['call'] == false){
            $userId = USER::getUserInfoByPhone($userPhone,'login_phone')->id;
            USER::delete($userId);
            unset($_SESSION['confirmCall']);
            unset($_SESSION['userPhone']);
            unset($_SESSION['countTry']);
            unset($_SESSION['timeCall']);
            $result['error'] = 'Не удалось подтвердить номер телефона, аккаунт не создан! Пожалуйста, обратитесь к администратору сайта для устранения технической проблемы.';
            return $error = $result['error'];
          }
          if (!empty($_SESSION['timeCall'])) {
            $_SESSION['timeCall'] = time() + 180;
          } else {
            $_SESSION['timeCall'] = time() + 60;
          }
          $_SESSION['countTry'] = 1; // количество попыток ввода кода пользователем
          break;
        case 'replay': // если необходимо повторить ввод пароля
            $secretSms = $_SESSION['confirmSMS'];
            $_SESSION['countTry'] = $_SESSION['countTry'] + 1;
            $paramToPhone['mode'] = $mode;
            if($_SESSION['countTry']>5) {
                $paramToPhone['capcha'] = md5($_SESSION['countTry']);
            } else {
                if (empty($_SESSION['timeSMS'])) {
                  $_SESSION['timeSMS'] = time() + 60;
                }
            }
            break;
        case 'replayCall': // если необходимо повторить ввод пароля
          $secretSms = $_SESSION['confirmCall'];
          $_SESSION['countTry'] = $_SESSION['countTry'] + 1;
          if($_SESSION['countTry']>5) {
              $paramToPhone['capcha'] = md5($_SESSION['countTry']);
          } else {
              if (empty($_SESSION['timeCall'])) {
                $_SESSION['timeCall'] = time() + 60;
              }
          }
          break;
        default :
            break;
        }
    $paramToPhone['userPhone'] = $userPhone;

    if($mode === 'call' || $mode === 'replayCall'){
       $message = '<form action = "'.SITE.'/registration?call='.$userPhone.'" method = "POST">'.
            MG::layoutManager('phone_registry_call', $paramToPhone) .
          '</form>';      
    }
    else{
      $message = '<form action = "'.SITE.'/registration?sms='.$userPhone.'" method = "POST">'.
      MG::layoutManager('phone_registry', $paramToPhone) .
      '</form>';
    }
    return $message;    
  }
  
  /**
   * Определяет способ регистрации почта = true /телефон = false
   *
   * @return bool
   */
  private function selectEmailOrPhone($userLogin) {
    if (!empty($userLogin['email']) && preg_match('/\@/', $userLogin['email'])) {
        return true;
    } else {
        return false;
    }
  }
  
  /**
   * Оставляем в номере телефона только цифры
   *
   * @return int
   */
  private function changeSymbolPhone($userLogin) {
    return preg_replace('/[\+\-\(\)\ ]/','',$userLogin);
  }
  
  /**
   * Применение функции trim (Удаление пробелов (или других символов) из начала и конца строки) к почте и паролю
   *
   * @return void
   */
  private function trimMailAndPass() {
    $_POST['email'] = trim($_POST['email']);
    $_POST['pass']  = trim($_POST['pass']);
    $_POST['pass2'] = trim($_POST['pass2']); 
  }

}
