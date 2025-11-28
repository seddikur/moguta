<?php

/**
 * Контроллер Forgotpass
 *
 * Класс Controllers_Forgotpass выполняет последовательность операций по восстановлению пароля пользователя.
 *
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Forgotpass extends BaseController {
  private $fPass;

  function __construct() {

    if (User::isAuth() && !URL::getQueryParametr('chengePass')) {
      MG::redirect('/');
    }

    // Шаг первый.
    $form = 1;
    $this->fPass = new Models_Forgotpass;
	  $message = $error = '';

    if (!User::isAuth()) {
      // Второй шаг, производящий проверку введен ого электронного адреса.
      if (URL::getQueryParametr('forgotpass')) {

        if(MG::getSetting('useCaptcha')=="true" && MG::getSetting('useReCaptcha') != 'true'){
          if (strtolower(URL::getQueryParametr('capcha')) != strtolower($_SESSION['capcha']) || empty($_SESSION['capcha'])) {
            $error = MG::restoreMsg('msg__captcha_incorrect');
    
            $this->data = [
              'error' => $error, // Сообщение об ошибке
              'message' => $message, // Информационное сообщение
              'form' => $form, // Отображение формы
              'meta_title' => 'Восстановление пароля',
              'meta_keywords' => "забыли пароль, восстановить пароль, восстановление пароля",
              'meta_desc' => "Если вы забыли пароль от личного кабинета, его модно восстановить с помощью формы восстановления паролей.",
              'checkCaptcha' => $this->showCaptcha(),
            ];
    
            return;
          }
        }
        if (!MG::checkReCaptcha()) {
          // return нету капчи
          $error = MG::restoreMsg('msg__recaptcha_incorrect');
    
          $this->data = [
            'error' => $error, // Сообщение об ошибке
            'message' => $message, // Информационное сообщение
            'form' => $form, // Отображение формы
            'meta_title' => 'Восстановление пароля',
            'meta_keywords' => "забыли пароль, восстановить пароль, восстановление пароля",
            'meta_desc' => "Если вы забыли пароль от личного кабинета, его модно восстановить с помощью формы восстановления паролей.",
            'checkCaptcha' => $this->showCaptcha(),
          ];
    
          return;
        }

        $email = URL::getQueryParametr('email');
        $phone = URL::getQueryParametr('phone');
        if(empty($phone) && !preg_match('/\@/', $email)){
            $phone = $email;
        }

        if ($userInfo = USER::getUserInfoByEmail($email)) {
          //Если введенных адрес совпадает с зарегистрированным в системе, то
          $form = 0;
          // $message = 'Инструкция по восстановлению пароля была отправлена на <strong>'.$email.'</strong>';
          $message = MG::restoreMsg('msg__forgot_restore',array('#EMAIL#' => $email));
          $hash = $this->fPass->getHash($email);
          //а) Случайный хэш заносится в БД.
          $this->fPass->sendHashToDB($email, $hash);
          $siteName = MG::getSetting('sitename');
          
          
          $emailMessage = MG::layoutManager('email_forgot',
            array(          
              'siteName'=>$siteName,
              'email'=>$email,
              'hash'=> $hash,
              'userId'=> $userInfo->id,
              'link' => SITE.'/forgotpass?sec='.$hash.'&id='.$userInfo->id,
            )
          );   
          
          $emailData = array(
            'nameFrom' => $siteName,
            'emailFrom' => MG::getSetting('noReplyEmail'),
            'nameTo' => 'Пользователю сайта '.$siteName,
            'emailTo' => $email,
            'subject' => 'Восстановление пароля на сайте '.$siteName,
            'body' => $emailMessage,
            'html' => true
          );
          //б) На указанный электронный адрес отправляется письмо со ссылкой на страницу восстановления пароля.
          $this->fPass->sendUrlToEmail($emailData);
        }
        else if (USER::getUserInfoByPhone($phone,'login_phone')) {

          //Если введенных телефон совпадает с зарегистрированным в системе, то
          $form = 0;
          if (empty($_SESSION['confirmSMS'])) {
            $message = $this->_sendActivationPhone($phone);
          } else {
            $message = $this->_sendActivationPhone($phone,'replay');
          }
        }
        else {

          $form = 0;
          // $error = 'К сожалению, такой логин не найден<br>
          //   Если вы уверены, что данный логин существует, пожалуйста, свяжитесь с нами.';
          $error = MG::restoreMsg('msg__wrong_login');
        }
      }
      // Шаг 3. Обработка перехода по ссылки. Принимается id пользователя и сгенерированный хэш.
      $urlSms = URL::getQueryParametr('sms');
      if (isset($_GET["sec"]) && empty($urlSms)) {
        $urlGetId = URL::getQueryParametr('id');
        $userInfo = USER::getUserById($urlGetId);
        $hash = URL::getQueryParametr('sec');
        // Если присланный хэш совпадает с хэшом из БД для соответствующего id.
        if ($userInfo->restore == $hash) {
          $form = 2;
          // Меняе в БД случайным образом хэш, делая невозможным повторный переход по ссылки.
          $this->fPass->sendHashToDB($userInfo->email, $this->fPass->getHash('0'));
          $_SESSION['id'] = URL::getQueryParametr('id');
        } else {
          $form = 0;
          // $error = 'Некорректная ссылка. Повторите заново запрос восстановления пароля.';
          $error = MG::restoreMsg('msg__forgot_wrong_link');
        }
      }
      // Обработка действий ввода кода из смс.
      if (URL::getQueryParametr('sms')) {
        $userPhone= URL::getQueryParametr('sms');
        $hash = URL::getQueryParametr('sec');
        $userInfo = USER::getUserInfoByPhone($userPhone,'login_phone');

        if($userInfo->restore == $hash){
          // Если присланный хэш совпадает с хэшом из БД для соответствующего номера телефона.
          if ($_SESSION['confirmSMS'] == md5($_POST['smspass'])) {
              // Меняет в БД случайным образом хэш, делая невозможным повторный переход по ссылки.
              $this->fPass->sendHashToDB($userInfo->email, $this->fPass->getHash('0'));
              $_SESSION['id'] = $userInfo->id;
              //выводи окно для ввода нового пароля
              $form = 2;
          } else {
            $currentTime = new DateTime;
            $seconds = $_SESSION['timeSMS'] - $currentTime->getTimestamp();
            // $_SESSION['countTry'] количество раз неправильно введенных подтверджений
            if(!empty($_POST['resendSMS']) && $_POST['resendSMS'] == md5($_SESSION['countTry'])||
              !empty($_SESSION['timeSMS']) && !empty($_POST['timeSMS']) && $_POST['timeSMS'] == $_SESSION['timeSMS'] && $seconds <= 0){
                    //$error = 'Код был повторно отправлен на номер '.$userPhone;
                    $error = MG::restoreMsg('msg__reg_sms_resend').' '.$userPhone;
                    $message = $this->_sendActivationPhone($userPhone,'send');
            } else {

                if (strtolower(URL::getQueryParametr('capcha')) != strtolower($_SESSION['capcha']) && $_SESSION['countTry'] > 5) {
                    // $error .= "<span class='error-captcha-text'>Текст с картинки введен неверно!</span>";
                    $error .= "<span class='error-captcha-text'>".MG::restoreMsg('msg__captcha_incorrect')."</span>";
                    $message = $this->_sendActivationPhone($userPhone,'replay');
                }
                else{
                    //$error = 'Неверный код подтверждения';
                    $error = MG::restoreMsg('msg__reg_sms_errore');
                    $message = $this->_sendActivationPhone($userPhone,'replay');
                }
            }
            //$error = MG::restoreMsg('msg__reg_wrong_link');
            $form = false;
          }
        } else {
          $form = 0;
          // $error = 'Некорректная ссылка. Повторите заново запрос восстановления пароля.';
          $error = MG::restoreMsg('msg__forgot_wrong_link');
        }
      }
    }
    // Шаг 4. обрабатываем запрос на ввод нового пароля
    if (URL::getQueryParametr('chengePass')) {
      $form = 2;
      $person = new Models_Personal;
      $msg = $person->changePass(URL::getQueryParametr('newPass'), $_SESSION['id'], true);
      if ('Пароль изменен' == $msg || MG::restoreMsg('msg__pers_pass_changed') == $msg) {
        $form = 0;
        // $message = $msg.'! '.'Вы можете войти в личный кабинет по адресу <a href="'.SITE.'/enter" >'.SITE.'/enter</a>';
        $message = MG::restoreMsg('msg__forgot_success',array('#LINK#' => SITE.'/enter'));
        $this->fPass->activateUser($_SESSION['id']);
        unset($_SESSION['id']);
        if (USER::isAuth()) {
          User::logout(false);
        }
      } else {
        $error = $msg;
      }
    }

    $this->data = array(
      'error' => $error, // Сообщение об ошибке.
      'message' => $message, // Информационное сообщение.
      'form' => $form, // Отображение формы.
      'meta_title' => 'Восстановление пароля',
      'meta_keywords' => "забыли пароль, восстановить пароль, восстановление пароля",
      'meta_desc' => "Если вы забыли пароль от личного кабинета, его модно восстановить с помощью формы восстановления паролей.",
      'checkCaptcha' => $this->showCaptcha(),
    );
  }

  /**
   * Вывод капчи или рекапчи, если включена опция
   * <code>
   * $model = new Controllers_Enter;
   * $data['checkCapcha'] = $this->showCaptcha();
   * </code>
   * @return string
   */
  private function showCaptcha() {
    $data = '';
    if(MG::getSetting('useReCaptcha') == 'true'){
      $data = MG::printReCaptcha();
    } else if (MG::getSetting('useCaptcha') == "true") {
      $data = '<div class="checkCapcha">
            <img style="margin-top: 5px; border: 1px solid gray;" src = "captcha.html?t='.time().'" width="140" height="36">
            <div>Введите текст с картинки:<span class="red-star">*</span> </div>
            <input type="text" name="capcha" class="captcha"></div>';
    }
    return $data;
  }

    /**
   * Метод отправки смс сообщения для восстановления пароля.
   * @param string $userEmail почта пользователя
   * @return void
   */
    private function _sendActivationPhone($userPhone, $mode='send') {
        $userInfo = USER::getUserInfoByPhone($userPhone,'login_phone');

        switch ($mode) {
            case 'send': // первичная отпрвка пароля
                //$secretSms = '123456';
                $secretSms = rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
                //$secretSms = 123456;
                $_SESSION['confirmSMS'] = md5($secretSms);
                // отправляем смс пользователю
                $result['message'] = $secretSms;
                $result['phone'] = $userPhone;
                $result['send'] = false; // метка состояния отправки письма
                $args = func_get_args();
                $result = MG::createHook('Method_confirm_sms', $result, $args);

                if($result['send'] == false){
                  //$error = 'Сервис отправки смс временно не доступен. Восстановите доступ используя email, либо свяжитесь с нами.';
                  unset($_SESSION['confirmSMS']);
                  unset($_SESSION['userPhone']);
                  unset($_SESSION['countTry']);
                  unset($_SESSION['timeSMS']);
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
              $result['send'] = false; // метка состояния звонка

              $result = MG::createHook('Method_confirm_call', $result, $args);
              $_SESSION['confirmCall'] = md5($result['code']);

              if($result['call'] == false){
                //$error = 'Сервис отправки смс временно не доступен. Восстановите доступ используя email, либо свяжитесь с нами.';
                unset($_SESSION['confirmSMS']);
                unset($_SESSION['userPhone']);
                unset($_SESSION['countTry']);
                unset($_SESSION['timeSMS']);
                return $error = MG::restoreMsg('msg__reg_not_sms');
              }
              if (!empty($_SESSION['timeSMS'])) {
                $_SESSION['timeSMS'] = time() + 180;
              } else {
                $_SESSION['timeSMS'] = time() + 60;
              }
              $_SESSION['countTry'] = 1; // количество попыток ввода кода пользователем
              break;

            case 'replay': // если необходимо повторить ввод пароля
                $secretSms = $_SESSION['confirmSMS'];
                $_SESSION['countTry'] = $_SESSION['countTry'] + 1;
                if($_SESSION['countTry']>5) {
                    $paramToPhone['capcha'] = md5($_SESSION['countTry']);
                } else {
                    if (empty($_SESSION['timeSMS'])) {
                      $_SESSION['timeSMS'] = time() + 60;
                    }
                }
                break;
            }
        $paramToPhone['userPhone'] = $userPhone;

        $hash = $this->fPass->getHash($userInfo->login_email);
        $this->fPass->sendHashToDB($userInfo->email, $hash);
        if($mode === 'call'){
          $message = '<form action = "'.SITE.'/forgotpass?sec='.$hash.'&call='.$userPhone.'" method = "POST">'.
          MG::layoutManager('phone_forgot', $paramToPhone) .
          '</form>';
        }
        else{
          $message = '<form action = "'.SITE.'/forgotpass?sec='.$hash.'&sms='.$userPhone.'" method = "POST">'.
          MG::layoutManager('phone_forgot', $paramToPhone) .
          '</form>';
        }
       
        return $message;
    }
}