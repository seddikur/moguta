<?php

/**
 * Контроллер: Enter
 *
 * Класс Controllers_Enter обрабатывает действия пользователей на странице авторизации.
 * - Аутентифицирует пользовательские данные;
 * - Проверяет корректность ввода данных с формы авторизации;
 * - При успешной авторизации перенаправляет пользователя в личный кабинет;
 * - При необходимых настройках включает защиту от подбора паролей;
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Enter extends BaseController {

  function __construct() {
    // 0. Мета-информация

    $data = array(
      'meta_title' => 'Авторизация',
      'meta_keywords' => "Авторизация,вход, войти в личный кабинет",
      'meta_desc' => "Авторизуйтесь на сайте и вы получите дополнительные возможности, недоступные для обычных пользователей.",
    );

    // 1. Проверка на авторизованность

    if (User::isAuth()) {
      header('Location: '.SITE.'/personal');
    }

    // 2. Проверка на запрос разлогинить

    if (URL::getQueryParametr('logout')) {
      User::logout();
      header('Location: '.SITE);
      exit;
    }

    // 3 Проверка на запрос разблокировать
    if (URL::getQueryParametr('unlock')) {
      USER::unlock(URL::getQueryParametr('unlock'));
    }

    // 4. Авторизация
    $data['checkCapcha'] = $this->showCaptcha();

    // 4.1 Проверка наличия емэила и пароля

    // 4.1.1 Проверка на наличие одно из двух
    if ( ( empty($_POST['email']) XOR empty($_POST['pass']) ) ) {
      // return нужен эмаил или пароль
      $data['msgError'] = MG::restoreMsg('msg__enter_field_missing');
      $this->data = $data;
      return false;

    // 4.1.2 Проверка на отсутствие обеих
    } else if (empty($_POST['email']) && empty($_POST['pass'])) {
      $this->data = $data;
      return false;
    }

    // 4.2 Проверка капчи
    if(MG::getSetting('useCaptcha')=="true" && MG::getSetting('useReCaptcha') != 'true'){
      if (strtolower(URL::getQueryParametr('capcha')) != strtolower($_SESSION['capcha']) || empty($_SESSION['capcha'])) {
        // return нету капчи
        $data['msgError'] = MG::restoreMsg('msg__captcha_incorrect');
        $this->data = $data;
        return false;
      }
    }
    if (!MG::checkReCaptcha()) {
      // return нету капчи
      $data['msgError'] = MG::restoreMsg('msg__recaptcha_incorrect');
      $this->data = $data;
      return false;
    }

    // 4.3 Проверка на правильность и авторизация
    $auth = User::auth(URL::get('email'), URL::get('pass'), true);
    if ($auth['result'] === true) {
      $this->successfulLogon();
    } else {
      if ($auth['reason'] == 'user blocked') {
        // return у вас закончились попытки
        $auth['time'] -= (intval(MG::getSetting('loginBlockTime'))*60);
        $data['msgError'] = MG::restoreMsg('msg__enter_blocked',array('#TIME#' => date("H:i", $auth['time']),'#MINUTES#'=>intval(MG::getSetting('loginBlockTime'))));
        $this->data = $data;
        return false;
      }

      if ($auth['reason'] == 'user pass failed' || $auth['reason'] == 'user not found') {
        // return не верный емаил или пароль
        $data['msgError'] = MG::restoreMsg('msg__enter_failed');
        $this->data = $data;
        return false;
      }
    }

    $this->data = $data;
  }

  /**
   * Вывод капчи или рекапчи, если включена опция
   * <code>
   * $model = new Controllers_Enter;
   * $data['checkCapcha'] = $this->showCaptcha();
   * </code>
   * @return string
   */
  public function showCaptcha() {
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
   * Перенаправляет пользователя на страницу в личном кабинете.
   * <code>
   * $model = new Controllers_Enter;
   * $model->successfulLogon();
   * </code>
   * @return void
   */
  public function successfulLogon() {

    if (empty($_REQUEST['location']) ||
          $_REQUEST['location'] == SITE.$_SERVER['REQUEST_URI'] ||
          $_REQUEST['location'] == $_SERVER['REQUEST_URI'] ||
          $_REQUEST['location'] == '/mg-admin') {

      header('Location: '.$_SERVER['HTTP_REFERER']);
      exit;
    }

    header('Location: '.$_REQUEST['location']);
    exit;
  }

  /**
   * Проверяет корректность ввода данных с формы авторизации.
   * <code>
   * $model = new Controllers_Enter;
   * $res = $model->validForm();
   * var_dump($res);
   * </code>
   * @return bool
   */
  public function validForm() {
    $email = URL::getQueryParametr('email');
    $pass = URL::getQueryParametr('pass');

    if (!$email || !$pass) {
      // При первом показе, не выводить ошибку.
      if (strpos($_SERVER['HTTP_REFERER'], '/enter')) {
        $this->data = array(
          // 'msgError' => '<span class="msgError">'.'Одно из обязательных полей не заполнено!'.'</span>',
          'msgError' => '<span class="msgError">'.MG::restoreMsg('msg__enter_field_missing').'</span>',
          'meta_title' => 'Авторизация',
          'meta_keywords' => "Авторизация,вход, войти в личный кабинет",
          'meta_desc' => "Авторизуйтесь на сайте и вы получите дополнительные возможности, недоступные для обычных пользователей.",
        );
      }
      return false;
    }
    return true;
  }
}
