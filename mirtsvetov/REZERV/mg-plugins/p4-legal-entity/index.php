<?php 

/*
	Plugin Name: Юридические лица
	Description: Плагин позволяет привязать к учетной записи пользователя юридическое лицо с адресами доставок, задолженностями и сессиями отгрузок.
	Author: belka.one
	Version: 1.1.0
*/

new LegalEntity;

class LegalEntity
{
	public static $pluginName = null;
	public static $path = null;
	private static $lang = [];

	public static $pluginPage = null;
	public static $validation = null;
	public static $features = null;

	public static $legalEntity = null;
	public static $debt = null;
	public static $address = null;
	public static $session = null;

	public function __construct()
	{
		$this->init();
		$this->hooks();
		$this->shortcodes();
	}

	private function init()
	{
		self::$pluginName = PM::getFolderPlugin(__FILE__);
		self::$path = PLUGIN_DIR . self::$pluginName;
		self::$lang = self::createLocale();

		$this->initClass();

		mgAddMeta('<script src="'. SITE .'/'. self::$path .'/js/public/script.js"></script>');
		mgAddMeta('<link rel="stylesheet" href="'. SITE .'/'. self::$path .'/css/public/modal.css" type="text/css">');

		if (!URL::isSection('order')) {
			mgAddMeta('<script src="'. SITE .'/'. self::$path .'/js/public/legalEntity.select.js"></script>');
		}

		if (URL::isSection('personal')) {
			mgAddMeta('<link rel="stylesheet" href="'. SITE .'/'. self::$path .'/css/public/personal.css" type="text/css">');
			mgAddMeta('<script src="'. SITE .'/'. self::$path .'/js/public/personal/legalEntity.js"></script>');
			mgAddMeta('<script src="'. SITE .'/'. self::$path .'/js/public/personal/legalEntity.address.js"></script>');
		}

		if (URL::isSection('order')) {
			mgAddMeta('<link rel="stylesheet" href="'. SITE .'/'. self::$path .'/css/public/order.css" type="text/css">');
			mgAddMeta('<script src="'. SITE .'/'. self::$path .'/js/public/order/legalEntity.js"></script>');
			mgAddMeta('<script src="'. SITE .'/'. self::$path .'/js/public/order/legalEntity.address.js"></script>');
		}
	}

	private function hooks()
	{
		// При активации плагина будет выполняться метод этого класса с названием: activate().
		mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate'));
		// При переходе в настройки плагина будет выполняться метод этого класса с названием: pluginPage().
		mgAddAction(__FILE__, array(__CLASS__, 'pluginPage'));
		// Выполняется после проверки данных, отправленных со страницы оформления заказа, и перед добавление нового заказа.
		mgAddAction('Models_Order_isValidData', array(__CLASS__, 'isValidData'), 1);
		// После добавления нового заказа будет выполняться метод этого класса с названием: addOrder().
		mgAddAction('Models_Order_addOrder', array(__CLASS__, 'addOrder'), 1);
		// TODO: Хук на удаление пользователя добавят в след. обновлении. Подготовить методы.
	}

	private function shortcodes()
	{
		// Шорткод для выбора юридического лица в шапке сайта.
		mgAddShortcode('p4-select-legal-entity', array(__CLASS__, 'selectLegalEntity'));
		// Шорткод для вывода информации о задолженности юридического лица в каталоге товаров.
		mgAddShortcode('p4-status-legal-entity', array(__CLASS__, 'statusLegalEntity'));
		// Шорткод для вывода информации о юридических лицах на странице личного кабинета.
		mgAddShortcode('p4-page-personal', array(__CLASS__, 'personalHtml'));
		// Шорткод для выбора юридического лица и адреса доставки на странице оформления заказа.
		mgAddShortcode('p4-page-order', array(__CLASS__, 'orderHtml'));
		// Шорткод для вывода информации о менеджере.
		mgAddShortcode('p4-manager-info', array(__CLASS__, 'managerHtml'));
	}

	private function initClass()
	{
		require_once('lib/pluginPage.php');
		self::$pluginPage = new LegalEntityPluginPage(self::$pluginName, self::$path, self::$lang);

		require_once('lib/validation.php');
		self::$validation = new LegalEntityValidation(self::$path, self::$lang);

		require_once('lib/features.php');
		self::$features = new LegalEntityFeatures();

		require_once('models/LegalEntity.php');
		self::$legalEntity = new Model_LegalEntity(self::$pluginName, self::$path, self::$lang);

		require_once('models/LegalEntityDebt.php');
		self::$debt = new Model_LegalEntityDebt(self::$pluginName);

		require_once('models/LegalEntityAddress.php');
		self::$address = new Model_LegalEntityAddress(self::$pluginName);

		require_once('models/LegalEntityUserSession.php');
		self::$session = new Model_LegalEntityUserSession(self::$pluginName);
	}

	/**
	 * 
	 * Локализация плагина.
	 * 
	 */
	private function createLocale()
	{
		self::$lang = PM::plugLocales(self::$pluginName);
		include('mg-admin/locales/' . MG::getSetting('languageLocale') . '.php');
		$lang = array_merge($lang, self::$lang);
		return $lang;
	}

	/**
	 * 
	 * Активация палагина в панели управления.
	 * 
	 */
	public static function activate()
	{
		self::$legalEntity->createTables();
		self::$debt->createTables();
		self::$address->createTables();
		self::$session->createTables();
	}

	/**
	 * 
	 * Страница плагина в панели управления.
	 * 
	 */
	public static function pluginPage()
	{
		self::$pluginPage->pluginPage();
	}

	/**
	 * 
	 * Шорткод [p4-status-legal-entity]
	 * 
	 * Шорткод для вывода информации о задолженности юридического лица в каталоге товаров.
	 * 
	 * @return string
	 * 
	 */
	static function statusLegalEntity()
	{
		$user_id = $_SESSION['user']->id;

		if (!$user_id) return;


		// TODO: Есть баг, если залогиниться под другим пользователем, 
		// то в сессии юр лица остается информация от другой учетки
		// Временный фикс
		$data['legal'] = self::$legalEntity->getUserLegalEntities($user_id);

		$count = count($data['legal']['items']);
		if ($count == 1) {
			$legal_id = array_key_first($data['legal']['items']);
		} else {
			$legal_id = $_SESSION['LegalEntity']['id'] ?? 0;
		}
			
		$data = self::$debt->getEntityData($args = ['legal_id' => $legal_id], true);

		ob_start();
		include(__DIR__ . '/views/public/shortcodes/status.php');
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * 
	 * Шорткод [p4-page-personal]
	 * 
	 * Шорткод для вывода информации о юридических лицах на странице личного кабинета.
	 * 
	 * @return string
	 * 
	 */
	public static function personalHtml()
	{
		$user_id = $_SESSION['user']->id;
		$data = self::$legalEntity->getUserLegalEntitiesAllData($user_id);

		ob_start();
		include(__DIR__ . '/views/public/shortcodes/personal/personal.php');
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * 
	 * Шорткод [p4-select-legal-entity]
	 * 
	 * Шорткод для выбора юридического лица в шапке сайта.
	 * 
	 * @return string
	 * 
	 */
	static function selectLegalEntity()
	{
		$user_id = $_SESSION['user']->id;
		$data['legal'] = self::$legalEntity->getUserLegalEntities($user_id);

		ob_start();
		include(__DIR__ . '/views/public/shortcodes/select.php');
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * 
	 * Шорткод [p4-manager-info]
	 * 
	 * Шорткод для выбора юридического лица в шапке сайта.
	 * 
	 * @return string
	 * 
	 */
	public static function managerHtml()
	{
		$legalId = (int) $_SESSION['LegalEntity']['id'] ?? 0;
		$userId = (int) $_SESSION['user']->id ?? 0;

		if ($userId && $legalId === 0) {
			$data = self::$legalEntity->getUserDefaultManagerLegal($userId);
		} else {
			$data = self::$legalEntity->getManagerLegal($legalId, $userId);
		}

		if (!empty($data['manager_phone'])) {
			$data['manager_phone'] = self::$features->phoneFormat($data['manager_phone']);
		}

		ob_start();
		include(__DIR__ . '/views/public/shortcodes/manager.php');
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * 
	 * Шорткод [p4-page-order]
	 * 
	 * Шорткод для выбора юридического лица и адреса доставки на странице оформления заказа.
	 * 
	 * @return string
	 * 
	 */
	public static function orderHtml()
	{
		$user_id = $_SESSION['user']->id;
		$data['legal'] = self::$legalEntity->getUserLegalEntities($user_id);
		$data['address'] = self::$address->getUserLegalEntitiesAddresses($data['legal']['selected'], $user_id);

		ob_start();
		include(__DIR__ . '/views/public/shortcodes/order/order.php');
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * 
	 * Хук Models_Order_isValidData
	 * 
	 * Выполняется после проверки данных, 
	 * отправленных со страницы оформления заказа, 
	 * и перед добавление нового заказа.
	 * 
	 * Возвращает ошибку, если не указаны данные или закончилась сессия отгрузки при оформлении заказа.
	 * 
	 * @param array $args
	 * 
	 * @return mixed
	 * 
	 */
	static function isValidData($args)
	{
		$error = isset($arg['result']) ? true : false;

		$result = $args['args'][0];

		if (!$error) {
			if (!isset($result['yur_info_nameyur'])) {
				$args['result'] = 'Пожалуйста, укажите юридическое лицо';
			}
		}

		return $args['result'];
	}

	/**
	 * 
	 * Хук Models_Order_addOrder
	 * 
	 * Выполняется после добавления нового заказа.
	 * 
	 * Метод удаляет сессию юридического лица.
	 * 
	 * @param array $args
	 * 
	 * @return mixed
	 * 
	 */
	static function addOrder($args)
	{
		unset($_SESSION['LegalEntity']['address_id']);

		return $args['result'];
	}
}