<?php

/**
 * Класс MailerIntegration - предназначен для передачи списка пользователей в Moguta Mailer
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Mailer extends BaseController
{
	public $startTime = null;
	public $maxExecTime = null;
	public $mode = null;
	public $type = null;
	public $auth = null;

	public function __construct(){
		
		//Вытаскиваем логин и пароль
		if (preg_match('/Basic+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
		}

		//Авторизуемся
		$auth = USER::auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

		//Проверяем наличие прав администратора
		if (!USER::AccessOnly('1')) {
			echo '{"error": "Пользователь не обладает правами администратора!"}';
			exit();
		}
		
		if($auth) {
			echo self::getUsersJSON();
		} else {
			echo '{"error": "авторизация не удалась!"}';
		}
		exit();

	}

	/**
	 * Метод возвращает весь список пользовтелей в формате JSON
	 * @access private
	 *
	 * @return string
	 */
	public function getUsersJSON() {
		$users = User::getListUser();
		return json_encode($users);
	}
}






