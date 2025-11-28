<?php

/**
 * Модель: Personal
 *
 * Класс Models_Personal реализует логику взаимодействия с личным кабинетом пользователя.
 *
 * @package moguta.cms
 * @subpackage Model
 */
class Models_Personal {

  /**
   * Функция смены пароля пользователя
   * После проверки корректности введённых данных производит хэширование и внесения в БД пароля пользователя
   * <code>
   * echo Models_Personal::changePass('newUserPassword123', 5);
   * </code>
   * @param string $newPass - новый пароль пользователя
   * @param int $id id пользователя
   * @param bool $forgotPass - флаг для функции восстановления пароля, когда не происходит изменения данных пользователя находящихся в системе
   * @return string сообщение о результате операции
   */
  public function changePass($newPass, $id, $forgotPass = false) {
    $userData = array(
      'pass' => $newPass,
    );
    $registration = new Models_Registration;

    if ($err = $registration->validDataForm($userData, 'pass')) {
      $msg = $err;
    } else {
      $userData['pass'] = password_hash($userData['pass'], PASSWORD_DEFAULT);
      USER::update($id, $userData, $forgotPass);
      // $msg = "Пароль изменен";
      $msg = MG::restoreMsg('msg__pers_pass_changed');
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $msg, $args);
  }
  /**
   * Функция смены телефона пользователя
   * После проверки корректности введённых данных производит запись в БД телефона пользователя
   * <code>
   * echo Models_Personal::changePhone('+7-999-999-99-99', 5);
   * </code>
   * @param string $newPhone - новый контактный номер телефона пользователя
   * @param int $id id пользователя
   * @param bool $changePhone - флаг для изменения контактого номера телефона пользователя
   * @return string сообщение о результате операции
   */
  public function changePhone($newPhone, $id, $changePhone = false) {
    $reviewData = array(
      'phone' => $newPhone,
    );
    $registration = new Models_Registration;

    if ($err = $registration->validDataForm($reviewData,'nopass')) {
      $msg = $err;
    } else {
      $userData['login_phone'] = preg_replace('/[\+\-\(\)\ ]/','',$newPhone);
      if($changePhone == true) $userData['phone'] = $userData['login_phone'];
      
      USER::update($id, $userData);
      //$msg = 'Номер телефона был успешно добавлен';
      $msg = MG::restoreMsg('msg__pers_phone_add');
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $msg, $args);
  }

}