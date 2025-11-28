<?php

$prefix = PREFIX;
if(!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if(!defined('SITE_DIR')) {
	define('SITE_DIR', dirname(__FILE__).DS);
}

class DatoBaseUpdater {
	public static $versionFromDb = 0;
	public static $lastVersion = 0;

	public static function getVer() {
		self::$versionFromDb = MG::getSetting('lastModVersion');
		if (!self::$versionFromDb) {
			self::$versionFromDb = '9.3.0';
		}
	}

	public static function check($patchVer) {
		@set_time_limit(30);
		self::$lastVersion = $patchVer;

		$result = version_compare($patchVer, self::$versionFromDb, '>');

		MG::loger('MOD CHECK. Last ver '.self::$versionFromDb.' Check ver '.$patchVer.' Result '.($result ? 'true' : 'false'));

		return $result;
	}

	public static function execute($sqlArr) {
		if (is_array($sqlArr)) {
			foreach ($sqlArr as $sql) {
				DB::query($sql);
			}
		}
	}

	public static function createIndexIfNotExist($table, $index) {
		$res = DB::query("SELECT COUNT(1) indexExists FROM INFORMATION_SCHEMA.STATISTICS
			WHERE table_schema=DATABASE() AND table_name='".PREFIX.DB::quote($table, true)."' AND index_name='".DB::quote($index, true)."';");
		$res = DB::fetchAssoc($res);
		if($res['indexExists'] == 0) {
			DB::query('CREATE INDEX '.DB::quote($index, true).' ON '.PREFIX.DB::quote($table, true).'('.DB::quote($index, true).')', true);
		}
	}

	public static function dropIdenticalLayouts() {
		@set_time_limit(30);
			$template = MG::getSetting('templateName');
			if(is_file(SITE_DIR.'mg-templates'.DS.$template.DS.'layout')){
			$layouts = array_diff(scandir(SITE_DIR.'mg-templates'.DS.$template.DS.'layout'), array('.', '..'));
	
			foreach ($layouts as $layout) {
			
				$coreFile = SITE_DIR.'mg-core'.DS.'layout'.DS.$layout;
				$file = SITE_DIR.'mg-templates'.DS.$template.DS.'layout'.DS.$layout;
				if (self::compareFiles($coreFile, $file)) {
					@unlink($file);
				}
			}
		}
	}

	public static function compareFiles($file_a, $file_b) {
		$content_a = file_get_contents($file_a);
		$content_b = file_get_contents($file_b);
		if (!$content_a || !$content_b) {
			return false;
		}
		if ($content_a === $content_b) {
			return true;
		}
		$content_a = preg_replace("/\r\n|\r|\n/", "\n", $content_a);//файлы с винды
		$content_b = preg_replace("/\r\n|\r|\n/", "\n", $content_b);//файлы с винды
		if ($content_a === $content_b) {
			return true;
		}
		return false;
	}

	public static function updateDbVer() {

		//////////////////////////////////////////////// для апгрейда и деградации редакций ////////////////////////////////////////////////
		DB::query("
			INSERT IGNORE INTO `".PREFIX."payment` (`id`, `code`, `name`, `activity`, `paramArray`, `urlArray`, `sort`, `permission`) VALUES
			(1, 'old#1', 'WebMoney', 1, '{\"Номер кошелька\":\"\",\"Секретный ключ\":\"\",\"Тестовый режим\":\"".CRYPT::mgCrypt('false')."\",\"Метод шифрования\":\"".CRYPT::mgCrypt('md5')."\"}', '{\"result URL:\":\"/payment?id=1&pay=result\",\"success URL:\":\"/payment?id=1&pay=success\",\"fail URL:\":\"/payment?id=1&pay=fail\"}', 1, 'fiz'),
			(5, 'old#5', 'ROBOKASSA', 1, '{\"Логин\":\"\",\"пароль 1\":\"\",\"пароль 2\":\"\",\"Метод шифрования\":\"".CRYPT::mgCrypt('md5')."\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"НДС, включенный в цену\":\"MjAlaFkqNm5rNyEhIzJxag==\",\"Обработка смены статуса при переходе на successUrl\":\"ZmFsc2VoWSo2bms3ISEjMnFq\"}', '{\"result URL:\":\"/payment?id=5&pay=result\",\"success URL:\":\"/payment?id=5&pay=success\",\"fail URL:\":\"/payment?id=5&pay=fail\"}', 5, 'fiz'),
			(8, 'old#8', 'Интеркасса', 1, '{\"Идентификатор кассы\":\"\",\"Секретный ключ\":\"\",\"Тестовый режим\":\"".CRYPT::mgCrypt('false')."\",\"Метод шифрования\":\"".CRYPT::mgCrypt('md5')."\"}', '{\"result URL:\":\"/payment?id=8&pay=result\",\"success URL:\":\"/payment?id=8&pay=success\",\"fail URL:\":\"/payment?id=8&pay=fail\"}', 8, 'fiz'),
			(9, 'old#9', 'PayAnyWay', 1, '{\"Номер расширенного счета\":\"\",\"Код проверки целостности данных\":\"\",\"Тестовый режим\":\"".CRYPT::mgCrypt('false')."\",\"Метод шифрования\":\"".CRYPT::mgCrypt('md5')."\"}', '{\"result URL:\":\"/payment?id=9&pay=result\",\"success URL:\":\"/payment?id=9&pay=success\",\"fail URL:\":\"/payment?id=9&pay=fail\"}', 9, 'fiz'),
			(10, 'old#10', 'PayMaster', 1, '{\"ID магазина\":\"\",\"Секретный ключ\":\"\",\"Метод шифрования\":\"".CRYPT::mgCrypt('md5')."\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"НДС, включенный в цену\":\"MjAlaFkqNm5rNyEhIzJxag==\"}', '{\"result URL:\":\"/payment?id=10&pay=result\",\"success URL:\":\"/payment?id=10&pay=success\",\"fail URL:\":\"/payment?id=10&pay=fail\"}', 10, 'fiz'),
			(11, 'old#11', 'AlfaBank', '1',  '{\"Логин\":\"\",\"Пароль\":\"\",\"Адрес сервера\":\"\",\"Метод шифрования\":\"".CRYPT::mgCrypt('md5')."\", \"Код валюты\":\"\"}',  '{\"result URL:\":\"/payment?id=11&pay=result\",\"success URL:\":\"/payment?id=11&pay=success\",\"fail URL:\":\"/payment?id=11&pay=fail\"}' , 11, 'fiz'),
			(14, 'old#14', 'Ю.Касса', 1, '{\"Ссылка для отправки данных\":\"\",\"Идентификатор магазина\":\"\",\"Идентификатор витрины\":\"\",\"shopPassword\":\"\",\"Метод шифрования\":\"".CRYPT::mgCrypt('md5')."\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"НДС, включенный в цену\":\"MjAlaFkqNm5rNyEhIzJxag==\"}', '{\"result URL:\":\"/payment?id=14&pay=result\",\"success URL:\":\"/payment?id=14&pay=success\",\"fail URL:\":\"/payment?id=14&pay=fail\"}', 12, 'fiz'), 
			(15, 'old#15', 'Приват24', 1, '{\"ID мерчанта\":\"\",\"Пароль марчанта\":\"\"}', '', 13, 'fiz'),
			(16, 'old#16', 'LiqPay', 1, '{\"Публичный ключ\":\"\",\"Приватный ключ\":\"\",\"Тестовый режим\":\"\"}', '', 14, 'fiz'),
			(17, 'old#17', 'Сбербанк', 1, '{\"API Логин\":\"\",\"Пароль\":\"\",\"Адрес сервера\":\"\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"Система налогообложения\":\"MGhZKjZuazchISMycWo=\",\"НДС на товары\":\"M2hZKjZuazchISMycWo=\",\"НДС на доставку\":\"M2hZKjZuazchISMycWo=\",\"Код валюты\":\"\"}', '{\"callback URL:\":\"/payment?id=17&pay=result\"}', 15, 'fiz'),
			(18, 'old#18', 'Тинькофф', 1, '{\"Ключ терминала\":\"\",\"Секретный ключ\":\"\",\"Адрес сервера\":\"\"}', '{\"result URL:\":\"/payment?id=18&pay=result\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"Система налогообложения\":\"b3NuaFkqNm5rNyEhIzJxag==\",\"НДС на товары\":\"dmF0MjBoWSo2bms3ISEjMnFq\",\"НДС на доставку\":\"dmF0MjBoWSo2bms3ISEjMnFq\",\"Email продавца\":\"\"}', 16, 'fiz'),
			(20, 'old#20', 'Comepay: интернет-эквайринг и прием платежей','1','{\"Идентификатор магазина\":\"\",\"Номер магазина\":\"\",\"Пароль магазина\":\"\",\"Callback Password\":\"\",\"Время жизни счета в часах\":\"\",\"Тестовый режим\":\"" . CRYPT::mgCrypt('false') . "\",\"Comepay URL\":\"".CRYPT::mgCrypt('https://actionshop.comepay.ru')."\",\"Comepay test URL\":\"".CRYPT::mgCrypt('https://moneytest.comepay.ru:449') . "\",\"Разрешить печать чеков в ККТ\":\"".CRYPT::mgCrypt('false') . "\",\"НДС на товары\":\"".CRYPT::mgCrypt('3') ."\",\"НДС на доставку\":\"".CRYPT::mgCrypt('3') ."\",\"Признак способа расчёта\":\"".CRYPT::mgCrypt('4') ."\"}', '{\"result URL:\":\"/payment?id=20&pay=result\",\"success URL:\":\"/payment?id=20&pay=success\",\"fail URL:\":\"/payment?id=20&pay=fail\"}', '20', 'fiz'),
			(21, 'old#21', 'Онлайн оплата (payKeeper)', 1, '{\"Язык страницы оплаты\":\"\",\"ID Магазина\":\"=\",\"Секретный ключ\":\"=\"}', '{\"result URL:\":\"/payment?id=21&pay=result\",\"success URL:\":\"/payment?id=21&pay=success\",\"fail URL:\":\"/payment?id=21&pay=fail\"}', 21, 'all'),
			(22, 'old#22', 'CloudPayments', 1, '{\"Public ID\":\"\",\"Секретный ключ\":\"\",\"Использовать онлайн кассу\":\"\",\"Система налогообложения\":\"".CRYPT::mgCrypt('ts_0')."\",\"Ставка НДС\":\"".CRYPT::mgCrypt('vat_20')."\",\"Ставка НДС для доставки\":\"".CRYPT::mgCrypt('vat_20')."\",\"Язык виджета\":\"".CRYPT::mgCrypt('ru-RU')."\"}', '{\"Check URL:\":\"/payment?id=22&pay=result&action=check\",\"Pay URL:\":\"/payment?id=22&pay=result&action=pay\",\"Fail URL:\":\"/payment?id=22&pay=result&action=fail\",\"Refund URL:\":\"/payment?id=22&pay=result&action=refund\"}', 22, 'fiz'),
			(23, 'old#23', 'Заплатить по частям от Ю.Касса', 1, '', '', 23, 'fiz'),
			(24, 'old#24', 'Ю.Касса (API)', 0, '{\"shopid\":\"\",\"api_key\":\"\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"НДС, включенный в цену\":\"MjAlaFkqNm5rNyEhIzJxag==\"}', '{\"Check URL:\":\"/payment?id=24\"}', 24, 'fiz'),
			(25, 'old#25', 'Apple Pay от Ю.Касса', 0, '{\"MerchantIdentifier\":\"\",\"MerchantName\":\"\",\"Password\":\"\",\"CertPath\":\"\",\"KeyPath\":\"\"}', '', 25, 'fiz'),
			(26, 'old#26', 'FREE-KASSA', 0, '{\"Язык страницы оплаты\":\"\", \"ID Магазина\":\"\", \"Секретный ключ1\":\"\", \"Секретный ключ2\":\"\"}', '{\"URL оповещения:\":\"/payment?id=26&pay=result\",\"URL возврата в случае успеха:\":\"/payment?id=26&pay=success\",\"URL возврата в случае неудачи:\":\"/payment?id=26&pay=fail\"}', 26, 'fiz'),
			(27, 'old#27', 'Мегакасса', 0, '{\"ID магазина\":\"\", \"Секретный ключ\":\"\"}', '{\"result URL:\":\"/payment?id=27&pay=result\",\"success URL:\":\"/payment?id=27&pay=success\",\"fail URL:\":\"/payment?id=27&pay=fail\"}', 27, 'fiz'),
			(28, 'old#28', 'Qiwi (API)', 1, '{\"Публичный ключ\":\"\", \"Секретный ключ\":\"\"}', '{\"result URL:\":\"/payment?id=28&pay=result\"}', 28, 'fiz'),
			(29, 'old#29', 'intellectmoney', '0', '{\"ID магазина\":\"\",\"Секретный ключ\":\"\",\"ИНН\":\"\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"Тестовый режим\":\"\",\"НДС на товары\":\"MmhZKjZuazchISMycWo=\",\"НДС на доставку\":\"NmhZKjZuazchISMycWo=\"}', '{\"result URL:\":\"/payment?id=29&pay=result\"}', 29, 'fiz')
		");


		DB::query("INSERT IGNORE INTO `".PREFIX."payment` (`id`, `code`, `name`, `activity`, `paramArray`, `urlArray`, `sort`, `permission`) VALUES
		(19, 'old#19', 'PayPal', 1, '{\"Токен идентичности\":\"\",\"Email продавца\":\"\",\"Тестовый режим\":\"dHJ1ZWhZKjZuazchISMycWo=\"}', '{\"result URL:\":\"/payment?id=19&pay=result\"}', 19, 'fiz')");


		$orderFormFileDir = SITE_DIR.'uploads'.DS.'generatedJs';
		if (!is_dir($orderFormFileDir)) {
			mkdir($orderFormFileDir);
		}
		$orderFormFile = $orderFormFileDir.DS.'order_form.js';


		if(MG::getSetting('orderFormFields') === null) {
			MG::setOption(array('option' => 'orderFormFields', 'value' => 'a:14:{s:5:\"email\";a:4:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"1\";s:8:\"required\";s:1:\"1\";s:13:\"conditionType\";s:6:\"always\";}s:5:\"phone\";a:6:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"2\";s:8:\"required\";s:1:\"1\";s:13:\"conditionType\";s:6:\"always\";s:10:\"conditions\";N;s:4:\"type\";s:5:\"input\";}s:3:\"fio\";a:4:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"3\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:6:\"always\";}s:7:\"address\";a:6:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"4\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:2:{s:4:\"type\";s:8:\"delivery\";s:5:\"value\";a:3:{i:0;s:1:\"0\";i:1;s:1:\"1\";i:2;s:1:\"2\";}}}s:4:\"type\";s:5:\"input\";}s:4:\"info\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"5\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:6:\"always\";s:10:\"conditions\";N;}s:8:\"customer\";a:4:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"6\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:6:\"always\";}s:16:\"yur_info_nameyur\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"7\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}s:15:\"yur_info_adress\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"8\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}s:12:\"yur_info_inn\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:1:\"9\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}s:12:\"yur_info_kpp\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:2:\"10\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}s:13:\"yur_info_bank\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:2:\"11\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}s:12:\"yur_info_bik\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:2:\"12\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}s:11:\"yur_info_ks\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:2:\"13\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}s:11:\"yur_info_rs\";a:5:{s:6:\"active\";s:1:\"1\";s:4:\"sort\";s:2:\"14\";s:8:\"required\";s:1:\"0\";s:13:\"conditionType\";s:5:\"ifAll\";s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:10:\"orderField\";s:9:\"fieldName\";s:8:\"customer\";s:9:\"fieldType\";s:6:\"select\";s:5:\"value\";a:1:{i:0;s:3:\"yur\";}}}}}', 'active' => 'Y', 'name' => ''));
		}
		$orderFormFileOld = SITE_DIR.'mg-core'.DS.'script'.DS.'standard'.DS.'js'.DS.'order_form.js';
		if (!file_exists($orderFormFileOld) && !file_exists($orderFormFile)) {
			file_put_contents($orderFormFile, 
				'var orderForm = (function () {'.PHP_EOL.
				'	return {'.PHP_EOL.
				'		init: function() {'.PHP_EOL.
				'			$(\'body\').on(\'change\', \'form[action*="/order?creation=1"] input[name="delivery"], form[action*="/order?creation=1"] [name=customer]\', function() {'.PHP_EOL.
				'				orderForm.redrawForm();'.PHP_EOL.
				'			});'.PHP_EOL.
				'			$(\'form[action*="/order?creation=1"] *\').removeAttr(\'data-delivery-address\');'.PHP_EOL.
				'			orderForm.redrawForm();'.PHP_EOL.
				'		},'.PHP_EOL.
				'		redrawForm: function() {'.PHP_EOL.
				'			var delivId = 0;'.PHP_EOL.
				'			if ($(\'form[action*="/order?creation=1"] input[name=delivery]:checked\').length) {'.PHP_EOL.
				'				delivId = $(\'form[action*="/order?creation=1"] input[name=delivery]:checked\').val();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray(parseInt(delivId), [0,1,2]) !== -1) {//address'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=address]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=address]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=address]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=address]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_nameyur'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_nameyur]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_adress'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_adress]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_inn'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_inn]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_kpp'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_kpp]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_bank'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bank]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_bik'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_bik]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_ks'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_ks]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'			if($.inArray($(\'form[action*="/order?creation=1"] [name=customer]:first\').val(), [\'yur\']) !== -1) {//yur_info_rs'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').prop(\'disabled\', false);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').closest(\'.js-orderFromItem\').show();'.PHP_EOL.
				'			} else {'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').prop(\'disabled\', true);'.PHP_EOL.
				'				$(\'form[action*="/order?creation=1"] [name=yur_info_rs]\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL.
				'			}'.PHP_EOL.
				'		},'.PHP_EOL. 
				'	  	//Методы для даты доставки'.PHP_EOL.
				'		disableDateMonthForDatepicker:function(monthWeek){'.PHP_EOL. 
				'   		let disableDateMonth = [];'.PHP_EOL. 
				'   		for(key in monthWeek){'.PHP_EOL. 
				'     			let days = monthWeek[key].split(\',\');'.PHP_EOL. 
				'      			let month = \'\';'.PHP_EOL. 
				'      			switch (key){'.PHP_EOL. 
				'        			case \'jan\' :'.PHP_EOL. 
				'         				month = \'01\';'.PHP_EOL. 
				'         				break;'.PHP_EOL. 
				'       			case \'feb\' :'.PHP_EOL. 
				'         				month = \'02\';'.PHP_EOL. 
				'         				break;     '.PHP_EOL. 
				'        			case \'mar\' :'.PHP_EOL. 
				'         				month = \'03\';'.PHP_EOL. 
				'         				break;'.PHP_EOL. 
				'        			case \'aip\' :'.PHP_EOL. 
				'         				month = \'04\';'.PHP_EOL. 
				'         				break; '.PHP_EOL. 
				'        			case \'may\' :'.PHP_EOL. 
				'         				month = \'05\';'.PHP_EOL. 
				'         				break; '.PHP_EOL. 
				'        			case \'jum\' :'.PHP_EOL. 
				'         				month = \'06\';'.PHP_EOL. 
				'         				break;  '.PHP_EOL. 
				'        			case \'jul\' :'.PHP_EOL. 
				'         				month = \'07\';'.PHP_EOL. 
				'         				break;'.PHP_EOL. 
				'        			case \'aug\' :'.PHP_EOL. 
				'         				month = \'08\';'.PHP_EOL. 
				'         				break;     '.PHP_EOL. 
				'        			case \'sep\' :'.PHP_EOL. 
				'         				month = \'09\';'.PHP_EOL. 
				'         				break;'.PHP_EOL. 
				'        			case \'okt\' :'.PHP_EOL. 
				'         				month = \'10\';'.PHP_EOL. 
				'         				break; '.PHP_EOL. 
				'        			case \'nov\' :'.PHP_EOL. 
				'         				month = \'11\';'.PHP_EOL. 
				'         				break; '.PHP_EOL. 
				'        			case \'dec\' :'.PHP_EOL. 
				'         				month = \'12\';'.PHP_EOL. 
				'         				break;         '.PHP_EOL.                                                                                                                                                             
				'      			}'.PHP_EOL. 
				'      		days.forEach(function(item){'.PHP_EOL. 
				'        		if(item !== \'\'){'.PHP_EOL. 
				'          			if(item < 10){'.PHP_EOL. 
				'            			item = \'0\'+item.toString();'.PHP_EOL. 
				'          			}'.PHP_EOL. 
				'          			disableDateMonth.push(month+"-"+item);'.PHP_EOL. 
				'        		}'.PHP_EOL. 
				'      		});'.PHP_EOL. 
				'    		}'.PHP_EOL. 
				'    		return disableDateMonth;'.PHP_EOL. 
				'  		},'.PHP_EOL. 
				'  		disableDateWeekForDatepicker:function(daysWeek){'.PHP_EOL. 
				'    		let disableDateWeek = [];'.PHP_EOL. 
				'    			for(key in daysWeek){'.PHP_EOL. 
				'      				if(daysWeek[key] != true){'.PHP_EOL. 
				'        				let numberOfWeekDay = \'\';'.PHP_EOL. 
				'        				switch (key){'.PHP_EOL. 
				'          					case \'su\' : numberOfWeekDay = 0;'.PHP_EOL. 
				'            					break;'.PHP_EOL. 
				'          					case \'md\' : numberOfWeekDay = 1;'.PHP_EOL. 
				'            					break;'.PHP_EOL.                                     
				'         					case \'tu\' : numberOfWeekDay = 2;'.PHP_EOL. 
				'           					break;'.PHP_EOL. 
				'         					case \'we\' : numberOfWeekDay = 3;'.PHP_EOL. 
				'            					break;'.PHP_EOL. 
				'          					case \'thu\' : numberOfWeekDay = 4;'.PHP_EOL. 
				'            					break;  '.PHP_EOL. 
				'          					case \'fri\' : numberOfWeekDay = 5;'.PHP_EOL. 
				'            					break;'.PHP_EOL. 
				'          					case \'sa\' : numberOfWeekDay = 6;'.PHP_EOL. 
				'            					break;        '.PHP_EOL.                                                                                                                                        
				'          				}'.PHP_EOL. 
				'       				disableDateWeek.push(numberOfWeekDay);'.PHP_EOL. 
				'      				}'.PHP_EOL. 
				'    			}'.PHP_EOL. 
				'    		return disableDateWeek;'.PHP_EOL. 
				'  		},'.PHP_EOL. 
				'  		disableDateForDatepicke: function(day, stringDay, monthWeek, daysWeek){  '.PHP_EOL. 
				'    		let isDisabledDaysMonth = ($.inArray(stringDay, orderForm.disableDateMonthForDatepicker(monthWeek)) != -1);'.PHP_EOL. 
				'   		let isDisabledDaysWeek = ($.inArray(day, orderForm.disableDateWeekForDatepicker(daysWeek)) != -1);'.PHP_EOL. 
				'    		return [(!isDisabledDaysWeek && !isDisabledDaysMonth)];'.PHP_EOL. 
				'  		}'.PHP_EOL. 
				'	};'.PHP_EOL.
				'})();'.PHP_EOL.
				'$(document).ready(function() {'.PHP_EOL.
				'	if (location.pathname.indexOf(\'/order\') > -1) {'.PHP_EOL.
				'		orderForm.init();'.PHP_EOL.
				'	}'.PHP_EOL.
				'});'
			);
		}
		#MG_END_END

		DB::query("INSERT IGNORE INTO `".PREFIX."messages` (`id`, `name`, `text`, `text_original`, `group`) VALUES
		('56', 'msg__pers_data_fail', 'Не удалось сохранить данные', 'Не удалось сохранить данные', 'register'),
		('57', 'msg__reg_phone_in_use', 'Номер телефона указан неверно или уже используется', 'Номер телефона указан неверно или уже используется', 'register'),
		('58', 'msg__reg_wrong_login', 'Неверно заполнен E-mail или номер телефона', 'Неверно заполнен E-mail или номер телефона', 'register'),
		('59', 'msg__pers_phone_add', 'Номер телефона был успешно добавлен', 'Номер телефона был успешно добавлен', 'register'),
		('60', 'msg__pers_phone_confirm', 'Номер телефона был успешно подтвержден. Теперь Вы можете войти в личный кабинет используя номер телефона и пароль заданный при регистрации.', 'Номер телефона был успешно подтвержден. Теперь Вы можете войти в личный кабинет используя номер телефона и пароль заданный при регистрации.', 'register'),
		('61', 'msg__reg_not_sms', 'Сервис отправки SMS временно не доступен. Зарегистрируйтесь используя email, либо свяжитесь с нами.', 'Сервис отправки SMS временно не доступен. Зарегистрируйтесь используя E-mail, или свяжитесь с нами.', 'register'),
		('62', 'msg__reg_sms_resend', 'Код подтверждения повторно отправлен на номер', 'Код подтверждения повторно отправлен на номер', 'register'),
		('63', 'msg__reg_sms_errore', 'Неверный код подтверждения', 'Неверный код подтверждения', 'register'),
		('64', 'msg__reg_not_sms_confirm', 'Сервис отправки SMS временно не доступен. Повторите попытку позже, либо свяжитесь с нами.', 'Сервис отправки SMS временно не доступен. Повторите попытку позже, либо свяжитесь с нами.', 'register'),
		('65', 'msg__reg_wrong_link_sms', 'Некорректная ссылка. Повторите попытку позже, либо свяжитесь с нами.', 'Некорректная ссылка. Повторите попытку позже, либо свяжитесь с нами.', 'register')");

		// Настройки даты доставки
		$res = DB::query("SHOW COLUMNS FROM `".PREFIX."delivery` LIKE 'date_settings'");
		if(!$row = DB::fetchArray($res)) {
			DB::query("ALTER TABLE `".PREFIX."delivery` ADD `date_settings` TEXT NOT NULL DEFAULT '' AFTER `date`;");
			DB::query("UPDATE `".PREFIX."delivery` SET `date_settings` = '{\"dateShift\":0,\"daysWeek\":{\"md\":true,\"tu\":true,\"we\":true,\"thu\":true,\"fri\":true,\"sa\":true,\"su\":true},\"monthWeek\":{\"jan\":\"\",\"feb\":\"\",\"mar\":\"\",\"aip\":\"\",\"may\":\"\",\"jum\":\"\",\"jul\":\"\",\"aug\":\"\",\"sep\":\"\",\"okt\":\"\",\"nov\":\"\",\"dec\":\"\"}}'");
		}
		
		//Для складов поле, отвечающее за отображение складов у выбранного типа доставки
	    $res = DB::query("SHOW COLUMNS FROM `".PREFIX."delivery` LIKE 'show_storages'");
	    if(!$row = DB::fetchArray($res)) {
	      DB::query("ALTER TABLE `".PREFIX."delivery` ADD `show_storages` VARCHAR(249) NOT NULL DEFAULT '0' AFTER `address_parts`;");
		}	
		
		$res = DB::query("SHOW COLUMNS FROM `".PREFIX."user` LIKE 'login_phone'");
		if(!$row = DB::fetchArray($res)) {
		  DB::query("ALTER TABLE `".PREFIX."user` ADD `login_phone` VARCHAR(50) NOT NULL AFTER `login_email`");
		}
		
		// Настройка для списывания только с определенного, выбранного пользователем склада
		if(MG::getSetting('useOneStorage') === null) {
			MG::setOption(array('option' => 'useOneStorage', 'value' => 'false', 'active' => 'N', 'name' => 'USE_ONE_STORAGE'));
		}

		// Webp
		if(MG::getSetting('useWebpImg') === null) {
			MG::setOption(array('option' => 'useWebpImg', 'value' => 'false', 'active' => 'Y', 'name' => 'USE_WEBP_IMAGES'));
		}

		//////////////////////////////////////////////// для апгрейда и деградации редакций конец ////////////////////////////////////////////////////////////////

		self::dropIdenticalLayouts();//удаление одинаковых с ядром движка лэйаутов шаблонов 

		
		// Некоторых настроек может не хватать если обновлять сайт вручную файлами или переходить между редакциями
		// Если произошло такое, то вносим в базу значения по умолчанию
		if (MG::getSetting('confirmRegistrationEmail') === null) {
			$defaultData = [
				'option' => 'confirmRegistrationEmail',
				'value' => 'true',
				'active' => 'Y',
				'name' => 'CONFIRM_REGISTRATION_EMAIL'
			];
			MG::setOption($defaultData);
		}
		if (MG::getSetting('confirmRegistrationPhone') === null) {
			$defaultData = [
				'option' => 'confirmRegistrationPhone',
				'value' => 'false',
				'active' => 'Y',
				'name' => 'CONFIRM_REGISTRATION_PHONE'
			];
			MG::setOption($defaultData);
		}
		if (MG::getSetting('confirmRegistrationPhoneType') === null) {
			$defaultData = [
				'option' => 'confirmRegistrationPhoneType',
				'value' => 'sms',
				'active' => 'Y',
				'name' => 'CONFIRM_REGISTRATION_PHONE_TYPE',
			];
			MG::setOption($defaultData);
		}
		if (is_null(MG::getSetting('registrationMethod'))) {
			$defaultData = [
				'option' => 'registrationMethod',
				'value' => 'email',
				'active' => 'Y',
				'name' => 'REGISTRATION_METHOD',
			];
			MG::setOption($defaultData);
		}
		if (MG::getSetting('genMetaLang') === null) {
			$defaultData = [
				'option' => 'genMetaLang',
				'value' => 'false',
				'active' => 'Y',
				'name' => 'GEN_META_LANG',
			];
			MG::setOption($defaultData);
		}
	}
}
DatoBaseUpdater::getVer();
// для обновления базы при входе в админку
MG::setOption('maxDbVersion', 1);

if (DatoBaseUpdater::check('3.1.2')) {
	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product_user_property` LIKE 'product_margin'");///

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product_user_property` ADD `product_margin` text NOT NULL COMMENT 'наценка продукта'";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product_user_property` LIKE 'type_view'");///

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product_user_property` ADD `type_view` enum('checkbox','select','radiobutton','') NOT NULL DEFAULT 'select'";
	}
		
	$sqlQueryTo[] = "INSERT IGNORE INTO  `".$prefix."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES 
		(NULL,  'mainPageIsCatalog',  'false',  'N',  'SETTING_CAT_ON_INDEX'),
		(NULL,  'countNewProduct',  '3',  'N',  'COUNT_NEW_PROD'),
		(NULL,  'countRecomProduct',  '3',  'N',  'COUNT_RECOM_PROD'),
		(NULL,  'countSaleProduct',  '3',  'N',  'COUNT_SALE_PROD'),
		(NULL,  'actionInCatalog',  'true',  'N',  'VIEW_OR_BUY'),
		(NULL,  'countSaleProduct',  '3',  'N',  'COUNT_SALE_PROD'),
		(NULL, 'printProdNullRem', 'true', 'N', 'PRINT_PROD_NULL_REM'),
		(NULL, 'printRemInfo', 'true', 'N', 'PRINT_REM_INFO')";

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."category` LIKE 'invisible'");

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."category` ADD  `invisible` TINYINT NOT NULL DEFAULT  '0' COMMENT  'Не выводить в меню'";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."page` LIKE 'sort'");/////

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."page` ADD `sort` int(11) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."page` LIKE 'print_in_menu'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."page` ADD `print_in_menu` tinyint(4) NOT NULL DEFAULT '0'";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'old_price'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `old_price` FLOAT NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'recommend'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `recommend` TINYINT NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'new'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `new` TINYINT NOT NULL";
	}

	// $sqlQueryTo[] = "INSERT INTO  `".$prefix."page`  VALUES (NULL, 'Обратная связь', 'feedback', '', 'Обратная связь', 'Обратная связь', '', 3, 1)";
	// $sqlQueryTo[] = "INSERT INTO  `".$prefix."page`  VALUES (NULL, 'Контакты', 'contacts', '', 'Контакты', 'Контакты', '', 4, 1)";
	// $sqlQueryTo[] = "INSERT INTO  `".$prefix."page`  VALUES (NULL, 'Каталог', 'catalog', '', 'Каталог', 'Каталог', '', 5, 1)";
	
	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.3.0')) {

	$sqlQueryTo = array(
		"CREATE TABLE IF NOT EXISTS `".$prefix."product_variant` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`product_id` int(11) NOT NULL,
		`title_variant` varchar(255) NOT NULL,
		`image` varchar(255) NOT NULL,
		`sort` int(11) NOT NULL,
		`price` float NOT NULL,
		`old_price` varchar(255) NOT NULL,
		`count` int(11) NOT NULL,
		`code` varchar(255) NOT NULL,
		`activity` tinyint(1) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1",
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.4.0')) {

	$sqlQueryTo = array(  
		"INSERT IGNORE INTO  `".$prefix."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL, 'heightPreview', '200', 'Y', 'PREVIEW_HEIGHT')",  
		"INSERT IGNORE INTO  `".$prefix."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL, 'widthPreview', '300', 'Y', 'PREVIEW_WIDTH')",  
		"INSERT IGNORE INTO  `".$prefix."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL, 'waterMark', 'true', 'N', 'WATERMARK')",  
		"INSERT IGNORE INTO  `".$prefix."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL, 'widgetCode', '<!-- В это поле необходимо прописать код счетчика посещаемости Вашего сайта. Например, Яндекс.Метрика или Google analytics -->', 'N', 'WIDGETCODE')",  
		"ALTER TABLE  `".$prefix."setting` CHANGE  `value`  `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL",  
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.4.1')) {

	$sqlQueryTo = array(
		"INSERT IGNORE INTO  `".$prefix."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (29, 'heightSmallPreview', '100', 'N', 'PREVIEW_HEIGHT')",  
		"INSERT IGNORE INTO  `".$prefix."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (30, 'widthSmallPreview', '150', 'N', 'PREVIEW_WIDTH')"
	);


	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product_variant` LIKE 'title_variant'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."product_variant` ADD FULLTEXT (`title_variant`)";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product_variant` LIKE 'code'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."product_variant` ADD FULLTEXT (`code`)";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.5.0')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."page` LIKE 'parent'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."page` ADD  `parent` INT( 11 ) NOT NULL AFTER  `id`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."page` LIKE 'parent_url'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."page` ADD  `parent_url` varchar(255) NOT NULL AFTER  `id`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."page` LIKE 'invisible'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."page` ADD  `invisible` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Не выводить в меню'";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'sort'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."product` ADD  `sort` INT( 11 ) NOT NULL AFTER `id`";
	}

	$sqlQueryTo[] = "UPDATE `".$prefix."payment` SET  `paramArray` = ".DB::quote('{"Юридическое лицо":"", "ИНН":"","КПП":"", "Адрес":"", "Банк получателя":"", "БИК":"","Расчетный счет":"","Кор. счет":""}')." WHERE  `id` =7";

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.6.0')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW INDEX FROM `".$prefix."product_user_property` WHERE Key_name = 'product_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."product_user_property` ADD INDEX (  `product_id` )";
	}

	$dbRes = DB::query("SHOW INDEX FROM `".$prefix."product_user_property` WHERE Key_name = 'property_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."product_user_property` ADD INDEX (  `property_id` )";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'inn'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `inn` TEXT NOT NULL AFTER  `activity`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'kpp'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `kpp` TEXT NOT NULL AFTER  `inn`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'nameyur'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `nameyur` TEXT NOT NULL AFTER  `kpp`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'adress'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `adress` TEXT NOT NULL AFTER  `nameyur`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'bank'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `bank` TEXT NOT NULL AFTER  `adress`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'bik'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `bik` TEXT NOT NULL AFTER  `bank`";
	}
	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'ks'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `ks` TEXT NOT NULL AFTER  `bik`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'rs'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."user` ADD  `rs` TEXT NOT NULL AFTER  `ks`";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."order` LIKE 'yur_info'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."order` ADD  `yur_info` TEXT NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."order` LIKE 'name_buyer'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."order` ADD  `name_buyer` TEXT NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.7.0')) {

	$sqlQueryTo = array(
		"ALTER TABLE `".$prefix."setting` CHANGE  `value`  `value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL",
		"ALTER TABLE `".$prefix."page` CHANGE  `html_content`  `html_content` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL",
		"ALTER TABLE `".$prefix."order` CHANGE  `order_content`  `order_content` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL",
		"INSERT IGNORE INTO `".$prefix."setting` (`option`, `value`, `active`, `name`) VALUES ('noReplyEmail', 'noreply@sitename.ru', 'Y', 'NOREPLY_EMAIL')",
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.7.1')) {

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."delivery` LIKE 'free'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo = array("ALTER TABLE `".$prefix."delivery` ADD  `free` FLOAT NOT NULL COMMENT  'Бесплатно от'");
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('3.9.0')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."property` LIKE 'activity'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."property` ADD  `activity` TINYINT(1) NOT NULL DEFAULT  '0'";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'related'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."product` ADD  `related` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.0.0')) {

	$sqlQueryTo = array(   
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'smtp', 'false', 'Y', 'SMTP')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'smtpHost', '', 'Y', 'SMTP_HOST')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'smtpLogin', '', 'Y', 'SMTP_LOGIN')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'smtpPass', '', 'Y', 'SMTP_PASS')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'smtpPort', '', 'Y', 'SMTP_PORT')",  
		"UPDATE `".$prefix."setting` SET `value` = 'default' WHERE `option` = 'templateName' AND `value` = '.default' ", 
		"UPDATE `".$prefix."setting` SET `name` = 'PREVIEW_HEIGHT_2' WHERE `option` = 'heightSmallPreview'",
		"UPDATE `".$prefix."setting` SET `name` = 'PREVIEW_WIDTH_2' WHERE `option` = 'widthSmallPreview'",  
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'sitename'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'adminEmail'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'templateName'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'countСatalogProduct'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'currency'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'countPrintRowsProduct'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'countPrintRowsPage'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'countNewProduct'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'countRecomProduct'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'countSaleProduct'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'actionInCatalog'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'printProdNullRem'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'printRemInfo'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'heightPreview'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'widthPreview'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'heightSmallPreview'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'widthSmallPreview'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'waterMark'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'widgetCode'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'noReplyEmail'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'smtp'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'smtpHost'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'smtpLogin'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'smtpPass'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'smtpPort'",
		"UPDATE `".$prefix."setting` SET `active` = 'Y' WHERE `option` = 'mainPageIsCatalog'", 
		"CREATE TABLE IF NOT EXISTS `".$prefix."cache` (
		`date_add` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
		`lifetime` int(11) NOT NULL,
		`name` varchar(255) NOT NULL,
		`value` longtext NOT NULL,
		UNIQUE KEY `name` (`name`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
	);

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'inside_cat'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD  `inside_cat` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE '1c_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD  `1c_id` VARCHAR( 36 ) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."category` LIKE '1c_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."category` ADD  `1c_id` VARCHAR( 36 ) NOT NULL";
	}

	$dbRes = DB::query("SHOW INDEX FROM `".$prefix."category` WHERE Key_name = '1c_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."category` ADD INDEX (  `1c_id` )";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'related'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD  `weight` FLOAT NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product_variant` LIKE 'weight'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product_variant` ADD  `weight` FLOAT NOT NULL";
	}

	$dbRes = DB::query("SHOW INDEX FROM `".$prefix."product` WHERE Key_name = '1c_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD INDEX (  `1c_id` )";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.1.0')) {

	$sqlQueryTo = array(
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'shopPhone', '88005555555', 'Y', 'SHOP_PHONE')",  
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'shopAddress', 'пр. Народного ополчения 10, 742.', 'Y', 'SHOP_ADDERSS')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'shopName', '', 'Y', 'SHOP_NAME')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'shopLogo', '', 'Y', 'SHOP_LOGO')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'phoneMask', '+7 (999) 999-99-99', 'Y', 'PHONE_MASK')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'printStrProp', 'true', 'Y', 'PROP_STR_PRINT')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'noneSupportOldTemplate', 'false', 'Y', 'OLD_TEMPLATE')",
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'printCompareButton', 'false', 'Y', 'BUTTON_COMPARE')",
	);

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'link_electro'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `link_electro` VARCHAR( 1024 ) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'currency_iso'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `currency_iso` VARCHAR( 50 ) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product_variant` LIKE 'currency_iso'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product_variant` ADD `currency_iso` VARCHAR( 50 ) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'price_course'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `price_course` DOUBLE NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product_variant` LIKE 'price_course'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product_variant` ADD `price_course` DOUBLE NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

	$currency = mb_strtolower(MG::getSetting('currency'), 'UTF-8');

	$arraySql = array();
	if(preg_match('/руб/i', strtolower($currency))) {

		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShopIso', 'RUR', 'Y', 'CUR_SHOP_ISO')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyRate', 'a:5:{s:3:\"RUR\";s:1:\"1\";s:3:\"UAH\";s:3:\"2.7\";s:3:\"USD\";s:4:\"39.5\";s:3:\"EUR\";s:4:\"49.8\";s:3:\"KZT\";s:4:\"49.8\";}', 'Y', 'CUR_SHOP_RATE')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShort', 'a:5:{s:3:\"RUR\";s:7:\"руб.\";s:3:\"UAH\";s:7:\"грн.\";s:3:\"USD\";s:1:\"$\";s:3:\"EUR\";s:3:\"€\";s:3:\"KZT\";s:5:\"тг.\";}', 'Y', 'CUR_SHOP_SHORT')";

		
	}elseif (preg_match('/грн/i', strtolower($currency))) {

		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShopIso', 'UAH', 'Y', 'CUR_SHOP_ISO')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyRate', 'a:5:{s:3:\"RUR\";s:1:\"2\";s:3:\"UAH\";s:1:\"1\";s:3:\"USD\";s:4:\"39.5\";s:3:\"EUR\";s:4:\"49.8\";s:3:\"KZT\";s:4:\"49.8\";}', 'Y', 'CUR_SHOP_RATE')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShort', 'a:5:{s:3:\"RUR\";s:7:\"руб.\";s:3:\"UAH\";s:7:\"грн.\";s:3:\"USD\";s:1:\"$\";s:3:\"EUR\";s:3:\"€\";s:3:\"KZT\";s:5:\"тг.\";}', 'Y', 'CUR_SHOP_SHORT')";

		
	}elseif (preg_match('/тг/i', strtolower($currency))) {

		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShopIso', 'KZT', 'Y', 'CUR_SHOP_ISO')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyRate', 'a:5:{s:3:\"RUR\";s:1:\"2\";s:3:\"UAH\";s:1:\"3\";s:3:\"USD\";s:4:\"39.5\";s:3:\"EUR\";s:4:\"49.8\";s:3:\"KZT\";s:1:\"1\";}', 'Y', 'CUR_SHOP_RATE')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShort', 'a:5:{s:3:\"RUR\";s:7:\"руб.\";s:3:\"UAH\";s:7:\"грн.\";s:3:\"USD\";s:1:\"$\";s:3:\"EUR\";s:3:\"€\";s:3:\"KZT\";s:5:\"тг.\";}', 'Y', 'CUR_SHOP_SHORT')";

		
	}elseif (preg_match('/$/i', strtolower($currency))) {

		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShopIso', 'USD', 'Y', 'CUR_SHOP_ISO')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyRate', 'a:5:{s:3:\"RUR\";s:1:\"2\";s:3:\"UAH\";s:1:\"3\";s:3:\"USD\";s:1:\"1\";s:3:\"EUR\";s:4:\"49.8\";s:3:\"KZT\";s:1:\"3\";}', 'Y', 'CUR_SHOP_RATE')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShort', 'a:5:{s:3:\"RUR\";s:7:\"руб.\";s:3:\"UAH\";s:7:\"грн.\";s:3:\"USD\";s:1:\"$\";s:3:\"EUR\";s:3:\"€\";s:3:\"KZT\";s:5:\"тг.\";}', 'Y', 'CUR_SHOP_SHORT')";

	}else{
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShopIso', 'RUR', 'Y', 'CUR_SHOP_ISO')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyRate', 'a:5:{s:3:\"RUR\";s:1:\"1\";s:3:\"UAH\";s:3:\"2.7\";s:3:\"USD\";s:4:\"39.5\";s:3:\"EUR\";s:4:\"49.8\";s:3:\"KZT\";s:4:\"49.8\";}', 'Y', 'CUR_SHOP_RATE')";
		$arraySql[]= "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'currencyShort', 'a:5:{s:3:\"RUR\";s:7:\"руб.\";s:3:\"UAH\";s:7:\"грн.\";s:3:\"USD\";s:1:\"$\";s:3:\"EUR\";s:3:\"€\";s:3:\"KZT\";s:5:\"тг.\";}', 'Y', 'CUR_SHOP_SHORT')";
	}

	if (is_array($arraySql)) {
		foreach ($arraySql as $sql) {
			DB::query($sql);
		}
	}
	unset($arraySql);
	unset($currency);

}

if (DatoBaseUpdater::check('4.1.2')) {

	$sqlQueryTo = array(
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'cacheObject', 'false', 'Y', 'CACHE_OBJECT')",  
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'cacheMode', 'DB', 'Y', 'CACHE_MODE')",  
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'cacheTime', '18000', 'Y', 'CACHE_TIME')",  
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'cacheHost', '', 'Y', 'CACHE_HOST')",  
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'cachePort', '', 'Y', 'CACHE_PORT')",  
		"INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'priceFormat', '', 'Y', 'PRICE_FORMAT')",  
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

	$product = new Models_Product();
	$product->updatePriceCourse(MG::getSetting('currencyShopIso'));
	unset($product);
}

if (DatoBaseUpdater::check('4.2.1')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'image_title'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER  TABLE `".$prefix."product` ADD `image_title` TEXT NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'image_alt'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER  TABLE `".$prefix."product` ADD `image_alt` TEXT NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."category` LIKE 'image_url'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER  TABLE `".$prefix."category` ADD `image_url` TEXT NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.3.0')) {

	$sqlQueryTo = array(); 

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."category` LIKE 'rate'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."category` ADD  `rate` DOUBLE NOT NULL DEFAULT  '0'";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."property` LIKE 'sort'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".$prefix."property` ADD  `sort` INT(11) NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.4.0')) {

	$sqlQueryTo = array( 
		"INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'horizontMenu', 'false', 'Y', 'HORIZONT_MENU');"
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.5.0')) {

	$sqlQueryTo = array(
		"INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'buttonBuyName', 'В корзину', 'Y', 'BUTTON_BUY_NAME')",
		"INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'buttonCompareName', 'Сравнить', 'Y', 'BUTTON_COMPARE_NAME')",
		"INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'buttonMoreName', 'Подробнее', 'Y', 'BUTTON_MORE_NAME')",
		"INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'randomProdBlock', 'false', 'Y', 'RANDOM_PROD_BLOCK')",
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.6.0')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'yml_sales_notes'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."product` ADD `yml_sales_notes` TEXT NOT NULL";
	}

	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'compareCategory', 'true', 'Y', 'COMPARE_CATEGORY')";
	
	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.7.0')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'count_buy'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."product` ADD `count_buy` INT(11) NOT NULL ";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."property` LIKE 'filter'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."property` ADD `filter` TINYINT(1) NOT NULL ";
	}

	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting`  VALUES (NULL, 'colorScheme', '', 'Y', 'COLOR_SCHEME')";
	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting`  VALUES (NULL, 'useCaptcha', '', 'Y', 'USE_CAPTCHA')";
	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting`  VALUES (NULL, 'autoRegister', 'true', 'Y', 'AUTO_REGISTER')";
	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting`  VALUES (NULL, 'printFilterResult', 'false', 'Y', 'FILTER_RESULT')";
	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.7.2')) {

	$sqlQueryTo = array();

	$sqlQueryTo[] = "UPDATE `".PREFIX."property` SET `sort`=`id` WHERE `sort`= 0";

	$dbRes = DB::query("SHOW INDEX FROM `".$prefix."product_variant` WHERE Key_name = 'product_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."product_variant` ADD index product_id(product_id)";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.7.3')) {

	$sqlQueryTo = array(
		"UPDATE `".PREFIX."product_user_property` SET `value`='' WHERE `value` LIKE 'null'"
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.8.0')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."delivery` LIKE 'date'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."delivery` ADD `date` INT(1) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."order` LIKE 'date_delivery'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."order` ADD `date_delivery` text";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'birthday'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."user` ADD `birthday` DATE NULL DEFAULT NULL ;";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'ip'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."user` ADD `ip` TEXT NOT NULL;";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."order` LIKE 'ip'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."order`  ADD `ip` TEXT NOT NULL ;";
	}
	
	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting`  VALUES (NULL, 'lockAuthorization', 'true', 'Y','LOCK_AUTH')";

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('4.9.0')) {

	$sqlQueryTo = array();

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."delivery` LIKE 'sort'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."delivery` ADD `sort` INT(11) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."delivery` LIKE 'ymarket'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."delivery` ADD `ymarket` INT(1) NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."payment` LIKE 'sort'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."payment` ADD `sort` INT(11) NOT NULL";
	}

	$sqlQueryTo[] = "ALTER TABLE `".PREFIX."product` CHANGE `1c_id` `1c_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";

	$sqlQueryTo[] = "ALTER TABLE `".PREFIX."category` CHANGE `1c_id` `1c_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";


	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."property` LIKE 'description'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."property` ADD `description` TEXT NOT NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."property` LIKE 'type_filter'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."property` ADD `type_filter` VARCHAR(32) NULL";
	}

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."property` LIKE '1c_id'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."property` ADD `1c_id` VARCHAR(255) NOT NULL";
	}

	$sqlQueryTo[] = "UPDATE `".PREFIX."page` SET `sort`=`id` WHERE ISNULL(sort)";

	$sqlQueryTo[] = "UPDATE `".PREFIX."property` SET `sort`=`id` WHERE ISNULL(sort)";

	$sqlQueryTo[] = "UPDATE `".PREFIX."product` SET `sort`=`id` WHERE ISNULL(sort)";

	$sqlQueryTo[] = "UPDATE `".PREFIX."payment` SET `sort` = `id` WHERE ISNULL(sort)";

	$sqlQueryTo[] = "UPDATE `".PREFIX."delivery` SET `sort` = `id` WHERE ISNULL(sort)";

	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."payment` (`id` ,`name` ,`activity` , `paramArray` , `urlArray`, `sort` )
		VALUES (12, 'Другой способ оплаты', 1, '{\"Примечание\":\"\"}', '', 12)";

	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."payment` (`id` ,`name` ,`activity` , `paramArray` , `urlArray`, `sort` )
		VALUES (13, 'Другой способ оплаты', 1, '{\"Примечание\":\"\"}', '', 13)";

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."order` LIKE 'number'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."order` ADD `number` VARCHAR(32) NOT NULL;";
	}

	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) 
			VALUES (NULL, 'orderNumber', 'false','Y', 'ORDER_NUMBER')";
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) 
			VALUES (NULL, 'popupCart', 'false', 'Y', 'POPUP_CART')";

	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'image_url'");//

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."product` CHANGE `image_url` `image_url` TEXT NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('5.0.0')) {

	$sqlQueryTo = array(
		"UPDATE `".PREFIX."product_user_property` SET `value`= TRIM(`value`) WHERE 1", 
	);

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}///

if (DatoBaseUpdater::check("5.2.0")) {

	$sqlQueryTo = array(
		"INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES 
			(NULL, 'catalogIndex', 'false', 'Y', 'CATALOG_INDEX'),
			(NULL, 'productInSubcat', 'true', 'Y', 'PRODUCT_IN_SUBCAT'),
			(NULL, 'copyrightMoguta', 'true', 'Y', 'COPYRIGHT_MOGUTA'),
			(NULL, 'picturesCategory', 'true', 'Y', 'PICTURES_CATEGORY'),
			(NULL, 'requiredFields', 'true', 'Y', 'REQUIRED_FIELDS'),
			(NULL, 'backgroundSite', '', 'Y', 'BACKGROUND_SITE'),
			(NULL, 'waterMarkVariants', 'false', 'Y', 'WATERMARK_VARIANTS')",
	); 

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("5.3.0")) {
	
	$dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."order` LIKE 'hash'");///

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE  `".PREFIX."order` ADD  `hash` VARCHAR(32) NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

	if(file_exists('mg-templates/default/css/color-scheme/color_77B8DD.css')) {
		unlink('mg-templates/default/css/color-scheme/color_77B8DD.css');
	}

	if(file_exists('mg-templates/default/css/color-scheme/color_2E79D8.css')) {
		unlink('mg-templates/default/css/color-scheme/color_2E79D8.css');
	}

	if(file_exists('mg-templates/default/css/color-scheme/color_4D484E.css')) {
		unlink('mg-templates/default/css/color-scheme/color_4D484E.css');
	}

	if(file_exists('mg-templates/default/css/color-scheme/color_5F996B.css')) {
		unlink('mg-templates/default/css/color-scheme/color_5F996B.css');
	}

	if(file_exists('mg-templates/default/css/color-scheme/color_A571B2.css')) {
		unlink('mg-templates/default/css/color-scheme/color_A571B2.css');
	}

	if(file_exists('mg-templates/default/css/color-scheme/color_CC0000.css')) {
		unlink('mg-templates/default/css/color-scheme/color_CC0000.css');
	}

	if(file_exists('mg-templates/default/css/color-scheme/color_F0DB47.css')) {
		unlink('mg-templates/default/css/color-scheme/color_F0DB47.css');
	}

	if(file_exists('mg-templates/default/css/color-scheme/color_FCA84C.css')) {
		unlink('mg-templates/default/css/color-scheme/color_FCA84C.css');
	}
	
}

if (DatoBaseUpdater::check("5.4.1")) {
	// если опции не было то добавим (это ятобы не продублировать для новой беслпатной версии)

	if(MG::getSetting('cacheCssJs') === null) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".$prefix."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'cacheCssJs', 'false', 'Y', 'CACHE_CSS_JS');";
	} 

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("5.4.2")) {
	$sqlQueryTo = array(   
		"UPDATE `".$prefix."payment`
			SET `urlArray` = '{\"result URL:\":\"/payment?id=14&pay=result\",\"success URL:\":\"/payment?id=14&pay=success\",\"fail URL:\":\"/payment?id=14&pay=fail\"}'
			WHERE `id` = 14",
	);
	
	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}
if (DatoBaseUpdater::check("5.5.0")) {

	$sqlQueryTo = array();
	// шифрование данных настроек способов оплаты при обновлении, с сохранением в файле копии данных

	$alreadyUpdate = false;
	$res = DB::query("SELECT * FROM `".PREFIX."payment`");
	$dump = array();
	$encodedparam = array();
	while ($row = DB::fetchArray($res)) { 
	
		if($alreadyUpdate) {    
			continue;
		}
	
		$dump[] = $row;
		$newparam = array();
		$param = json_decode($row['paramArray'],TRUE);
		
		if ($row['id']==="1") {   
			if(!empty($param['Тестовый режим'])) {
				$alreadyUpdate = true;
			break;
			}
		}
		
		if(!empty($row['id']))
		if ($row['id']==1||$row['id']==2||$row['id']==8||$row['id']==9) {
			$param['Тестовый режим']= 'false';
		}

		$checkIds = array(3,4,7,12,13,15);
		if (!in_array($row['id'],$checkIds)) {
			$param['Метод шифрования']= $row['id']!= 2 ? 'md5' : 'sha1';
		}

		foreach ($param as $key=>$value) {
			if ($value != '') {
				$value = CRYPT::mgCrypt($value);
			}
			$newparam[$key] = $value;
		}

		$encodedparam = CRYPT::json_encode_cyr($newparam);

		if ($encodedparam) {
			$sqlQueryTo[] = 'UPDATE `'.PREFIX."payment` SET paramArray=".DB::quote($encodedparam)." WHERE `id` = ".DB::quote($row['id']);     
		}
	}



	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'hash\'');
	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = 'ALTER TABLE `'.PREFIX.'order` ADD `hash` VARCHAR(32) NOT NULL';
	}

	$sqlQueryTo[] = '
		INSERT IGNORE INTO `'.PREFIX.'setting` 
			(`id`, `option`, `value`, `active`, `name`) VALUES
			(null, \'categoryImgWidth\', 200, \'Y\', \'CATEGORY_IMG_WIDTH\'),
			(null, \'categoryImgHeight\', 200, \'Y\', \'CATEGORY_IMG_HEIGHT\')';

	if(!$alreadyUpdate) {
		
		MG::loger("Файл создан для сохранения данных по оплате, в связи с переходом на шифрование хранимых данных,"
			. "если с системами оплаты на версии 5.5.0 или старше не обнаружено никаких проблем, то файл можно удалять.\n"
			.print_r($dump, true));

		if (is_array($sqlQueryTo)) {
			foreach ($sqlQueryTo as $sql) {
				DB::query($sql);
			}
		}
	}
}

if (DatoBaseUpdater::check("5.6.0")) {
	
	$sqlQueryTo = array();

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'user_comment\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = 'ALTER TABLE `'.PREFIX.'order` ADD `user_comment` TEXT';
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'1c_last_export\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = 'ALTER TABLE `'.PREFIX.'order` ADD `1c_last_export` TIMESTAMP ';
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product_variant` WHERE FIELD = \'1c_id\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = 'ALTER TABLE `'.PREFIX.'product_variant` ADD `1c_id` VARCHAR(255) NOT NULL ';
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("5.7.0")) {

	$sqlQueryTo = array();

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'category` WHERE FIELD = \'export\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = 'ALTER TABLE `'.PREFIX.'category` ADD `export` TINYINT(1) NOT NULL DEFAULT \'1\'';
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo); 
}

if (DatoBaseUpdater::check("5.7.1")) {

	$sqlQueryTo = array();

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'property` WHERE FIELD = \'plugin\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = 'ALTER TABLE `'.PREFIX.'property` ADD `plugin` VARCHAR( 255 ) NOT NULL';
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("5.8.0")) {

	$sqlQueryTo = array();

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'favicon\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'favicon', 'favicon.ico', 'Y', 'FAVICON')";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'payment` WHERE FIELD = \'rate\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = 'ALTER TABLE `'.PREFIX.'payment` ADD `rate` double NOT NULL DEFAULT \'0\'';
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("5.8.1")) {

	$sqlQueryTo = array();

	/*Для hotfix 5.8.1*/
	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'connectZoom\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'connectZoom', 'true', 'Y', 'CONNECT_ZOOM')";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("5.9.0")) {

	$sqlQueryTo = array();

	// добавление метода шифрования в настройки оплаты Яндекс.Касса (Ю.Касса)

	$alreadyUpdate = false;
	$res = DB::query("SELECT * FROM `".PREFIX."payment` WHERE `id`=14");
	while ($row = DB::fetchArray($res)) {  
		$param = json_decode($row['paramArray'],TRUE);
		$param['Метод шифрования']= CRYPT::mgCrypt('md5');
		$encodedparam = CRYPT::json_encode_cyr($param);

		if ($encodedparam) {
			$sqlQueryTo[] = "UPDATE `".PREFIX."payment` SET paramArray=".DB::quote($encodedparam)." WHERE `id` =14";     
		}
	}
	/*Для 5.9.0*/
	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'filterSort\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'filterSort', 'price_course|asc', 'Y', 'FILTER_SORT')";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'delivery_options\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."order` ADD COLUMN `delivery_options` TEXT";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'delivery` WHERE FIELD = \'plugin\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".PREFIX."delivery` ADD COLUMN `plugin` VARCHAR(255)";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

	if(file_exists('mg-core/mg-pages/encode.php')) {
		unlink('mg-core/mg-pages/encode.php');
	}
	if(file_exists('mg-pages/encode.php')) {
		unlink('mg-pages/encode.php');
	}
}

if (DatoBaseUpdater::check("6.1.0")) {

	$sqlQueryTo = array();

	/* Для релиза 6.1.0 Добавление вывода характеристик из новой переменной в файле шаблона layout_property.php*/
	/* Если не добавить в файл эту вставку, то из карточки товара пропадут характеристики*/

	$file = file_get_contents(PATH_TEMPLATE.'/layout/layout_property.php', true);
	if($file) {
		if(substr_count($file, '$data[\'htmlProperty\'];') == 0) file_put_contents(PATH_TEMPLATE.'/layout/layout_property.php', '<?php echo $data[\'htmlProperty\']; ?>'."\n".$file);
	}

	/*Для релиза 6.1.0*/
	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'shortLink\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$short = SHORT_LINK == 1 ? 'true' : 'false';
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'shortLink', ".DB::quote($short).", 'Y', 'SHORT_LINK')";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("6.2.0")) {

	$sqlQueryTo = array();

	/*Для релиза 6.2.0*/

	//Создаем таблицу для хранения данных по страницам выборок фильтра
	$sqlQueryTo[] = " 
		CREATE TABLE IF NOT EXISTS `".PREFIX."url_rewrite` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`url` varchar(255) NOT NULL,
			`short_url` varchar(255) NOT NULL,
			`titeCategory` varchar(255) DEFAULT NULL,
			`cat_desc` longtext NOT NULL,
			`meta_title` varchar(255) NOT NULL,
			`meta_keywords` varchar(1024) NOT NULL,
			`meta_desc` text NOT NULL,
			`activity` tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

	$sqlQueryTo[] = " 
		CREATE TABLE IF NOT EXISTS `".PREFIX."url_redirect` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`url_old` varchar(255) NOT NULL,
			`url_new` varchar(255) NOT NULL,
			`code` int(3) NOT NULL,
			`activity` tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'duplicateDesc\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'duplicateDesc', 'true', 'Y', 'DUPLICATE_DESC')";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'excludeUrl\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'excludeUrl', '', 'Y', 'EXCLUDE_SITEMAP')";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'autoGeneration\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'autoGeneration', 'false', 'Y', 'AUTO_GENERATION')";
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'generateEvery', '2', 'Y', 'GENERATE_EVERY')";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'catalog_meta_title\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "
			INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES 
				(NULL, 'catalog_meta_title', '', 'N', ''),
				(NULL, 'catalog_meta_description', '', 'N', ''),
				(NULL, 'catalog_meta_keywords', '', 'N', ''),
				(NULL, 'product_meta_title', '', 'N', ''),
				(NULL, 'product_meta_description', '', 'N', ''),
				(NULL, 'product_meta_keywords', '', 'N', ''),
				(NULL, 'page_meta_title', '', 'N', ''),
				(NULL, 'page_meta_description', '', 'N', ''),
				(NULL, 'page_meta_keywords', '', 'N', '')";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'imageResizeType\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "
			INSERT IGNORE INTO `".$prefix."setting` (`option`, `value`, `active`, `name`) VALUES 
				('imageResizeType', 'PROPORTIONAL', 'Y', 'IMAGE_RESIZE_TYPE'),
				('imageSaveQuality', '75', 'Y', 'IMAGE_SAVE_QUALITY')";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

}

if (DatoBaseUpdater::check("6.2.3")) {

	$sqlQueryTo = array();

	/*Для релиза 6.2.3*/  
	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product` WHERE FIELD = \'system_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `system_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."product` SET `system_set` =`id`+`count`*143";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'orders_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."order` ADD `orders_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."order` SET `orders_set` =`id`*`delivery_id`";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'consentData\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "
			INSERT IGNORE INTO `".$prefix."setting` (`option`, `value`, `active`, `name`) VALUES 
				('consentData', 'true', 'Y', 'CONSENT_DATA')";
	} 

	$sqlQueryTo[] = "ALTER TABLE `".$prefix."url_rewrite` MODIFY `url` TEXT";
	$sqlQueryTo[] = "ALTER TABLE `".$prefix."url_redirect` MODIFY `url_new` TEXT";
	$sqlQueryTo[] = "ALTER TABLE `".$prefix."url_redirect` MODIFY `url_old` TEXT";

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

	if(file_exists('mg-core/lib/coder.php')) {
		unlink('mg-core/lib/coder.php');
	}
	if(file_exists('mg-core/lib/source.php')) {
		unlink('mg-core/lib/source.php');
	}
}

if (DatoBaseUpdater::check("6.3.0")) {

	if(file_exists('mg-core/lib/coder.php')) {
		unlink('mg-core/lib/coder.php');
	}
	if(file_exists('mg-core/lib/source.php')) {
		unlink('mg-core/lib/source.php');
	}
}

if (DatoBaseUpdater::check("6.4.0")) {

	$sqlQueryTo = array();
	
	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'showCountInCat\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'showCountInCat', 'true', 'Y', 'SHOW_COUNT_IN_CAT')";
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'nameOfLinkyml', 'getyml', 'N', 'NAME_OF_LINKYML')";
		MG::setOption('smtpPass', CRYPT::mgCrypt(MG::getSetting('smtpPass')));
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("6.5.0")) {

	$sqlQueryTo = array();

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'category` WHERE FIELD = \'seo_content\'');

	if (!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."category` ADD `seo_content` TEXT";
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."category` ADD `activity` TINYINT(1) NOT NULL DEFAULT  '1'";
		$sqlQueryTo[] = "UPDATE `".$prefix."category` SET `activity` = 1";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'showSortFieldAdmin\'');

	if (!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'showSortFieldAdmin', 'false', 'Y', 'SHOW_SORT_FIELD_ADMIN')";
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'confirmRegistration', 'true', 'Y', 'CONFIRM_REGISTRATION')";
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'cachePrefix', '', 'Y', 'CACHE_PREFIX')";
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'usePhoneMask', 'true', 'Y', 'USE_PHONE_MASK')";  
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'showVariantNull\'');

	if (!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'showVariantNull', 'true', 'Y', 'SHOW_VARIANT_NULL')";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'filterSortVariant\'');

	if (!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'filterSortVariant', 'price_course|asc', 'Y', 'FILTER_SORT_VARIANT')";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.$prefix.'product` WHERE FIELD = \'related_cat\'');

	if (!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `related_cat` TEXT NOT NULL";
	}

	$smtpSsl = 'false';
	$smtpHost = MG::getSetting('smtpHost');
	if (stristr($smtpHost, 'ssl://')!==FALSE) {
		$smtpHost = str_replace('ssl://', '', $smtpHost);
		$smtpSsl = 'true';
		MG::setOption(array('option' => 'smtpHost', 'value' => $smtpHost));  
	}

	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, 'smtpSsl', ".DB::quote($smtpSsl)." , 'Y', 'SMTP_SSL')";

	$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` MODIFY `description` LONGTEXT";
	
	$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`id`, `option`, `value`, `active`, `name`) VALUES "
		."(null, 'clearCatalog1C', 'false', 'Y', 'CLEAR_1C_CATALOG'),"
		."(null, 'fileLimit1C', '10000000', 'Y', 'FILE_LIMIT_1C'),"
		."(null, 'notUpdateDescription1C', 'true', 'Y', 'UPDATE_DESCRIPTION_1C'),"
		."(null, 'notUpdateImage1C', 'true', 'Y', 'UPDATE_IMAGE_1C')";

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'property` WHERE FIELD = \'unit\'');

	if (!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."property` ADD `unit` VARCHAR(32) NOT NULL";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("6.6.0")) {

	$sqlQueryTo = array();

	if ($dbRes = DB::query("SELECT `paramArray` FROM `".$prefix."payment` WHERE `id` = 11")) {

		if ($arRes = DB::fetchAssoc($dbRes)) {
			$arFields = explode(",", $arRes['paramArray']);

			if (count($arFields) == 3) {
				$arFields[] = $arFields[2];
				$arFields[2] = '"Адрес сервера":"aHR0cHM6Ly9lbmdpbmUucGF5bWVudGdhdGUucnVoWSo2bms3ISEjMnFq"';
				$newStrFields = implode(",", $arFields);
				$sqlQueryTo[] = "UPDATE `".$prefix."payment` SET `paramArray` = '".$newStrFields."' WHERE `id` = 11";
			}    
		}  
	}

	if ($dbRes = DB::query("SELECT `paramArray` FROM `".$prefix."payment` WHERE `id` = 17")) {
		if ($arRes = DB::fetchAssoc($dbRes)) {
			$arFields = explode(",", $arRes['paramArray']);

			if (count($arFields) == 2) {
				$arFields[1] = substr($arFields[1], 0, -1);
				$arFields[] = '"Адрес сервера":"aHR0cHM6Ly8zZHNlYy5zYmVyYmFuay5ydWhZKjZuazchISMycWo="}';
				$newStrFields = implode(",", $arFields);
				$sqlQueryTo[] = "UPDATE `".$prefix."payment` SET `paramArray` = '".$newStrFields."' WHERE `id` = 17";
			}    
		}  
	}

	$sqlQueryTo[] = "CREATE TABLE IF NOT EXISTS `".$prefix."sessions` ( 
		`session_id` varchar(255) binary NOT NULL default '', 
		`session_expires` int(11) unsigned NOT NULL default '0', 
		`session_data` longtext, 
		PRIMARY KEY  (`session_id`) 
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"; 

	if ($dbRes = DB::query("SELECT `id` FROM `".$prefix."setting` WHERE `option` = 'sessionToDB'")) {
		$arRes = DB::fetchAssoc($dbRes);

		if (empty($arRes)) {
			$sqlQueryTo[] = "INSERT IGNORE INTO `".$prefix."setting` (`option`, `value`, `active`, `name`) VALUES
				('sessionToDB', 'false', 'Y', 'SAVE_SESSION_TO_DB'),
				('sessionLifeTime', '1440', 'Y', 'SESSION_LIVE_TIME'),
				('sessionAutoUpdate', 'true', 'Y', 'SESSION_AUTO_UPDATE')";
		}  
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
	
}

if (DatoBaseUpdater::check("6.6.4")) {

	if (file_exists('mg-templates/default/views/mgadmin.php')) {
		unlink('mg-templates/default/views/mgadmin.php');
	}
	if (file_exists('mg-core/lib/source.php')) {
		unlink('mg-core/lib/source.php');
	}
	if (file_exists('mg-core/lib/updata_1.php')) {
		unlink('mg-core/lib/updata_1.php');
	}
	if (file_exists('mg-core/lib/coder.php')) {
		unlink('mg-core/lib/coder.php');
	}
}

if (DatoBaseUpdater::check("6.6.5")) {

	if (file_exists('mg-templates/default/views/mgadmin.php')) {    
		unlink('mg-templates/default/views/mgadmin.php');
	} 

	if (file_exists('mg-core/lib/source.php')) {    
		unlink('mg-core/lib/source.php');
	} 

	if (file_exists('mg-core/lib/updata_1.php')) {    
		unlink('mg-core/lib/updata_1.php');
	} 

	if (file_exists('mg-core/lib/coder.php')) {    
		unlink('mg-core/lib/coder.php');
	} 

	if (file_exists('mg-templates/default/css/color-scheme/color_2E79D8.css')) {    
		unlink('color_2E79D8.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_4D484E.css')) {
		unlink('color_4D484E.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_5F996B.css')) {
		unlink('color_5F996B.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_77B8DD.css')) {
		unlink('color_77B8DD.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_EB5F5F.css')) {
		unlink('color_EB5F5F.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_F0DB47.css')) {
		unlink('color_F0DB47.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_FCA84C.css')) {
		unlink('color_FCA84C.css');
	}
	 
}

if (DatoBaseUpdater::check("6.7.0")) {
		$sqlQueryTo = array();

	// удаление лишних файлов после 6.6.3
	if (file_exists('mg-templates/default/views/mgadmin.php')) {    
		unlink('mg-templates/default/views/mgadmin.php');
	} 

	if (file_exists('mg-core/lib/source.php')) {    
		unlink('mg-core/lib/source.php');
	} 

	if (file_exists('mg-core/lib/updata_1.php')) {    
		unlink('mg-core/lib/updata_1.php');
	} 

	if (file_exists('mg-core/lib/coder.php')) {    
		unlink('mg-core/lib/coder.php');
	} 

	if (file_exists('mg-templates/default/css/color-scheme/color_2E79D8.css')) {    
		unlink('color_2E79D8.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_4D484E.css')) {
		unlink('color_4D484E.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_5F996B.css')) {
		unlink('color_5F996B.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_77B8DD.css')) {
		unlink('color_77B8DD.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_EB5F5F.css')) {
		unlink('color_EB5F5F.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_F0DB47.css')) {
		unlink('color_F0DB47.css');
	}

	if (file_exists('mg-templates/default/css/color-scheme/color_FCA84C.css')) {
		unlink('color_FCA84C.css');
	}
	// удаление лишних файлов после 6.6.3
	/*Для обновления*/  

	$dbRes = DB::query('SHOW COLUMNS FROM `'.$prefix.'product` WHERE FIELD = \'system_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `system_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."product` SET `system_set` =`id`+`count`*143";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.$prefix.'order` WHERE FIELD = \'orders_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."order` ADD `orders_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."order` SET `orders_set` =`id`*`delivery_id`";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

}

if (DatoBaseUpdater::check("6.9.0")) {

	$sqlQueryTo = array();

	$dbRes = DB::query('SHOW COLUMNS FROM `'.$prefix.'url_rewrite` WHERE FIELD = \'cat_desc_seo\'');

	if (!$row = DB::fetchArray($dbRes)) { 
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."url_rewrite` ADD `cat_desc_seo` TEXT NOT NULL DEFAULT ''";
	}

	$dbRes = DB::query('SELECT * FROM `'.PREFIX.'setting` WHERE `option` = \'showCodeInCatalog\'');

	if (!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".PREFIX."setting` (`option`, `value`, `active`, `name`) VALUES ('showCodeInCatalog', 'false', 'Y', 'SHOW_CODE_IN_CATALOG')";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);

	// в активном шаблоне в файле personal необходимо заменить вывод статуса заказа:
	// $lang[$order['string_status_id']] на $order['string_status_id']

	$sql = DB::query("SELECT `option`, `value` FROM `".PREFIX."setting` WHERE `option`='templateName' ");
	$row = DB::fetchAssoc($sql);
	$personal = '/mg-templates/'.$row['value'].'/views/personal.php';

	if (file_exists($personal)) {
		$content = file_get_contents($personal);
		$content = str_replace('$lang[$order[\'string_status_id\']]', '$order[\'string_status_id\']', $content);
		file_put_contents($personal, $content);
	}
}

if (DatoBaseUpdater::check('6.9.5')) {

	$sqlQueryTo = array();

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product` WHERE FIELD = \'system_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `system_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."product` SET `system_set` =`id`+`count`*143";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'orders_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."order` ADD `orders_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."order` SET `orders_set` =`id`*`delivery_id`";
	}

	$dbRes = DB::query('SELECT `id` FROM `'.$prefix.'setting` WHERE `option` = "openGraph"');

	if(!$dbRes = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".$prefix."setting`(`option`, `value`, `active`, `name`) VALUES ('openGraph', 'true', 'Y', 'OPEN_GRAPH')";
	}
	
	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('6.9.9')) {

	$sqlQueryTo = array();
	$hasPrintSameProd = 0;
	// проверка наличия параметра для показа или нет товара, которого нет на скаде в разделе с этим покупают
	$res = DB::query('SELECT COUNT(id) FROM `'.$prefix.'setting` WHERE `option` = "printSameProdNullRem"');

	while($row = DB::fetchAssoc($res)) {
		$hasPrintSameProd = $row['COUNT(id)'];
	}

	if($hasPrintSameProd == 0) {
		$sqlQueryTo[] = "INSERT IGNORE INTO `".$prefix."setting`(`option`, `value`, `active`, `name`) VALUES ('printSameProdNullRem', 'true', 'Y', 'PRINT_SAME_PROD_NULL_REM')";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('6.9.10')) {
	
	$res = DB::query('UPDATE `'.$prefix.'payment` SET name = "Тинькофф", urlArray = "{\"result URL:\":\"/id=18&pay=info\",\"success URL:\":\"/payment?id=18&pay=result\",\"fail URL:\":\"/payment?id=2&pay=fail\"}" WHERE id = 18');
}

if (DatoBaseUpdater::check('6.9.11')) {

	$sqlQueryTo = array();

	$sqlQueryTo[] = "CREATE TABLE IF NOT EXISTS `".$prefix."site-block-editor`  (
		`id` int(11) NOT NULL,
		`comment` text NOT NULL,
		`type` varchar(255) NOT NULL,
		`content` text NOT NULL,
		`width` text NOT NULL,
		`height` text NOT NULL,
		`alt` text NOT NULL,
		`title` text NOT NULL,
		`href` text NOT NULL,
		`class` text NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
	
	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check('6.9.16')) {

	$sqlQueryTo = array();

	$res = DB::query("SELECT COUNT(`id`) AS count FROM `".$prefix."setting` WHERE `option` = 'interface'");
	
		while ($row = DB::fetchAssoc($res)) {
			$res = $row;
		}
	if($res['count'] != 1) {
		$sqlQueryTo[] = "INSERT INTO `".$prefix."setting` (`option`, `value`, `active`, `name`) VALUES ('interface', 'a:5:{s:9:\"colorMain\";s:7:\"#2773eb\";s:9:\"colorLink\";s:7:\"#1585cf\";s:9:\"colorSave\";s:7:\"#4caf50\";s:11:\"colorBorder\";s:7:\"#e6e6e6\";s:14:\"colorSecondary\";s:7:\"#ebebeb\";}', 'Y', '')";
	}

	/*Для обновления*/
	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product` WHERE FIELD = \'system_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `system_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."product` SET `system_set` =`id`+`count`*143";
	}

	$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'orders_set\'');

	if(!$row = DB::fetchArray($dbRes)) {
		$sqlQueryTo[] = "ALTER TABLE `".$prefix."order` ADD `orders_set` INT( 11 )";
		$sqlQueryTo[] = "UPDATE `".$prefix."order` SET `orders_set` =`id`*`delivery_id`";
	}

	DatoBaseUpdater::execute($sqlQueryTo);
	unset($sqlQueryTo);
}

if (DatoBaseUpdater::check("8.2.0")) {

// для обновления с фришки
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."product` LIKE 'system_set'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."product` ADD `system_set` int(11) DEFAULT NULL");
}


// ===========================================================
// ====== РЕСТРУКТУИРИЗИРУЕМ БАЗУ ДЛЯ ГИПЕРМАРКЕТА ===========
// ===========================================================

//обновляем запись про яндекс кассу
$dbRes = DB::query('select `paramArray` FROM `'.PREFIX.'payment` WHERE id = 14');
$res = DB::fetchArray($dbRes);
if (strpos($res['paramArray'], "Использовать онлайн кассу") == false && strpos($res['paramArray'], "НДС, включенный в цену") == false) {
	$res = substr($res['paramArray'], 0, -1).',"Использовать онлайн кассу":"ZmFsc2VoWSo2bms3ISEjMnFq","НДС, включенный в цену":"MjAlaFkqNm5rNyEhIzJxag=="}';
	DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 14");
}
//обновляем запись про paymaster
$dbRes = DB::query('select `paramArray` FROM `'.PREFIX.'payment` WHERE id = 10');
$res = DB::fetchArray($dbRes);
if (strpos($res['paramArray'], "Использовать онлайн кассу") == false && strpos($res['paramArray'], "НДС, включенный в цену") == false) {
	$res = substr($res['paramArray'], 0, -1).',"Использовать онлайн кассу":"ZmFsc2VoWSo2bms3ISEjMnFq","НДС, включенный в цену":"MjAlaFkqNm5rNyEhIzJxag=="}';
	DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 10");
}

//-----------------------------------------------
// фикс двойного таймштампа для кривых баз данных
//-----------------------------------------------
$versionMysql = mysqli_get_server_version(DB::$connection);
$arVersionMysql = array(
	'main' => round($versionMysql/10000),
	'minor' => ($versionMysql/100)%10,
	'sub' => $versionMysql%100,
);

if ($arVersionMysql['main'] == 5 && $arVersionMysql['minor'] < 6 || $arVersionMysql['main'] == 5 && $arVersionMysql['minor'] == 6 && $arVersionMysql['sub'] < 5) {
	DB::query("ALTER TABLE `".PREFIX."user` MODIFY COLUMN `date_add` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'");
}

unset($versionMysql);
unset($arVersionMysql);

//-----------------------------------------------
// добавляем таблицу для оптовых скидок
//-----------------------------------------------
DB::query('CREATE TABLE IF NOT EXISTS `'.PREFIX.'wholesales_sys` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`product_id` int(11) NOT NULL,
	`variant_id` int(11) NOT NULL,
	`count` int(11) NOT NULL,
	`price` double NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
DatoBaseUpdater::createIndexIfNotExist('wholesales_sys', 'product_id');
DatoBaseUpdater::createIndexIfNotExist('wholesales_sys', 'variant_id');
DatoBaseUpdater::createIndexIfNotExist('wholesales_sys', 'count');

DB::query("ALTER TABLE `".PREFIX."wholesales_sys` MODIFY COLUMN `price` double NOT NULL");

//обновляем базу для синхронизации с RetailCRM
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'user` WHERE FIELD = \'last_updated\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product` WHERE FIELD = \'last_updated\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."product` ADD `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product_variant` WHERE FIELD = \'last_updated\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."product_variant` ADD `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}
// для краткого описания
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."product` LIKE 'short_description'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."product` ADD `short_description` longtext");
}

// для единиц измерения
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'unit'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `unit` varchar(255) NOT NULL DEFAULT 'шт.'");
}
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."product` LIKE 'unit'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."product` ADD `unit` varchar(255) DEFAULT NULL");
}

//для лендингов
if(MG::getSetting('landingName') === null) {
	MG::setOption(array('option' => 'landingName', 'value' => 'lp-moguta'));
}
if(MG::getSetting('colorSchemeLanding') === null) {
	MG::setOption(array('option' => 'colorSchemeLanding', 'value' => 'none'));
}

//для валют
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."order` LIKE 'summ_shop_curr'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."order` ADD `summ_shop_curr` double DEFAULT NULL");
	DB::query("UPDATE `".PREFIX."order` SET `summ_shop_curr` = `summ`");
}
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."order` LIKE 'delivery_shop_curr'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."order` ADD `delivery_shop_curr` double DEFAULT NULL");
	DB::query("UPDATE `".PREFIX."order` SET `delivery_shop_curr` = `delivery_cost`");
}
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."order` LIKE 'currency_iso'");
if(!$row = DB::fetchArray($dbQuery)) {
	$tmp = MG::getOption('currencyShopIso');
	DB::query("ALTER TABLE `".PREFIX."order` ADD `currency_iso` varchar(255) DEFAULT NULL");
	DB::query("UPDATE `".PREFIX."order` SET `currency_iso` = ".DB::quote($tmp));
}

DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."messages` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`text` text NOT NULL,
	`text_original` text NOT NULL,
	`group` varchar(255) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

//для настроек
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."messages` LIKE 'group'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("DROP TABLE IF EXISTS `".PREFIX."messages`");
}

DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."messages` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(249) NOT NULL,
	`text` text NOT NULL,
	`text_original` text NOT NULL,
	`group` varchar(255) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$dbRes = DB::query("SHOW INDEXES FROM `".$prefix."messages` WHERE Column_name='name' AND NOT Non_unique");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("DELETE FROM ".$prefix."messages USING ".$prefix."messages,
			".$prefix."messages e1
		WHERE ".$prefix."messages.id > e1.id
		AND ".$prefix."messages.name = e1.name");
	DB::query("ALTER TABLE `".$prefix."messages` ADD UNIQUE(`name`)");
}
@set_time_limit(30);
DB::query("INSERT IGNORE INTO `".$prefix."messages` (`id`, `name`, `text`, `text_original`, `group`) VALUES
	('1', 'msg__order_denied', 'Для просмотра страницы необходимо зайти на сайт под пользователем сделавшим заказ №#NUMBER#.', 'Для просмотра страницы необходимо зайти на сайт под пользователем сделавшим заказ №#NUMBER#.', 'order'),
	('2', 'msg__no_electro', 'Заказ не содержит электронных товаров или ожидает оплаты!', 'Заказ не содержит электронных товаров или ожидает оплаты!', 'order'),
	('3', 'msg__electro_download', 'Скачать электронные товары для заказа №#NUMBER#.', 'Скачать электронные товары для заказа №#NUMBER#.', 'order'),
	('4', 'msg__view_status', 'Посмотреть статус заказа Вы можете в <a href=\"#LINK#\">личном кабинете</a>.', 'Посмотреть статус заказа Вы можете в <a href=\"#LINK#\">личном кабинете</a>.', 'order'),
	('5', 'msg__order_not_found', 'Некорректная ссылка.<br> Заказ не найден.<br>', 'Некорректная ссылка.<br> Заказ не найден.<br>', 'order'),
	('6', 'msg__view_order', 'Следить за статусом заказа Вы можете по ссылке<br><a href=\"#LINK#\">#LINK#</a>.', 'Следить за статусом заказа Вы можете по ссылке<br><a href=\"#LINK#\">#LINK#</a>.', 'order'),
	('7', 'msg__order_confirmed', 'Ваш заказ №#NUMBER# подтвержден и передан на обработку.<br>', 'Ваш заказ №#NUMBER# подтвержден и передан на обработку.<br>', 'order'),
	('8', 'msg__order_processing', 'Заказ уже подтвержден и находится в работе.<br>', 'Заказ уже подтвержден и находится в работе.<br>', 'order'),
	('9', 'msg__order_not_confirmed', 'Некорректная ссылка.<br>Заказ не подтвержден.<br>', 'Некорректная ссылка.<br>Заказ не подтвержден.<br>', 'order'),
	('10', 'msg__email_in_use', 'Пользователь с таким email существует. Пожалуйста, <a href=\"#LINK#\">войдите в систему</a> используя свой электронный адрес и пароль!', 'Пользователь с таким email существует. Пожалуйста, <a href=\"#LINK#\">войдите в систему</a> используя свой электронный адрес и пароль!', 'order'),
	('11', 'msg__email_incorrect', 'E-mail введен некорректно!', 'E-mail введен некорректно!', 'order'),
	('12', 'msg__phone_incorrect', 'Введите верный номер телефона!', 'Введите верный номер телефона!', 'order'),
	('13', 'msg__payment_incorrect', 'Выберите способ оплаты!', 'Выберите способ оплаты!', 'order'),
	('15', 'msg__product_ended', 'Товара #PRODUCT# уже нет в наличии. Для оформления заказа его необходимо удалить из корзины.', 'Товара #PRODUCT# уже нет в наличии. Для оформления заказа его необходимо удалить из корзины.', 'product'),
	('16', 'msg__product_ending', 'Товар #PRODUCT# доступен в количестве #COUNT# шт. Для оформления заказа измените количество в корзине.', 'Товар #PRODUCT# доступен в количестве #COUNT# шт. Для оформления заказа измените количество в корзине.', 'product'),
	('17', 'msg__no_compare', 'Нет товаров для сравнения в этой категории.', 'Нет товаров для сравнения в этой категории.', 'product'),
	('18', 'msg__product_nonavaiable1', 'Товара временно нет на складе!<br/><a rel=\"nofollow\" href=\"#LINK#\">Сообщить когда будет в наличии.</a>', 'Товара временно нет на складе!<br/><a rel=\"nofollow\" href=\"#LINK#\">Сообщить когда будет в наличии.</a>', 'product'),
	('19', 'msg__product_nonavaiable2', 'Здравствуйте, меня интересует товар #PRODUCT# с артикулом #CODE#, но его нет в наличии. Сообщите, пожалуйста, о поступлении этого товара на склад. ', 'Здравствуйте, меня интересует товар #PRODUCT# с артикулом #CODE#, но его нет в наличии. Сообщите, пожалуйста, о поступлении этого товара на склад. ', 'product'),
	('20', 'msg__enter_failed', 'Неправильная пара email-пароль! Авторизоваться не удалось.', 'Неправильная пара email-пароль! Авторизоваться не удалось.', 'register'),
	('21', 'msg__enter_captcha_failed', 'Неправильно введен код с картинки! Авторизоваться не удалось. Количество оставшихся попыток - #COUNT#.', 'Неправильно введен код с картинки! Авторизоваться не удалось. Количество оставшихся попыток - #COUNT#.', 'register'),
	('22', 'msg__enter_blocked', 'В целях безопасности возможность авторизации заблокирована на 15 мин. Отсчет времени от #TIME#.', 'В целях безопасности возможность авторизации заблокирована на 15 мин. Отсчет времени от #TIME#.', 'register'),
	('23', 'msg__enter_field_missing', 'Одно из обязательных полей не заполнено!', 'Одно из обязательных полей не заполнено!', 'register'),
	('24', 'msg__feedback_sent', 'Ваше сообщение отправлено!', 'Ваше сообщение отправлено!', 'feedback'),
	('25', 'msg__feedback_wrong_email', 'E-mail не существует!', 'E-mail не существует!', 'feedback'),
	('26', 'msg__feedback_no_text', 'Введите текст сообщения!', 'Введите текст сообщения!', 'feedback'),
	('27', 'msg__captcha_incorrect', 'Текст с картинки введен неверно!', 'Текст с картинки введен неверно!', 'feedback'),
	('28', 'msg__reg_success_email', 'Вы успешно зарегистрировались! Для активации пользователя Вам необходимо перейти по ссылке высланной на Ваш электронный адрес <strong>#EMAIL#</strong>', 'Вы успешно зарегистрировались! Для активации пользователя Вам необходимо перейти по ссылке высланной на Ваш электронный адрес <strong>#EMAIL#</strong>', 'register'),
	('29', 'msg__reg_success', 'Вы успешно зарегистрировались! <a href=\"#LINK#\">Вход в личный кабинет</a></strong>', 'Вы успешно зарегистрировались! <a href=\"#LINK#\">Вход в личный кабинет</a></strong>', 'register'),
	('30', 'msg__reg_activated', 'Ваша учетная запись активирована. Теперь Вы можете <a href=\"#LINK#\">войти в личный кабинет</a> используя логин и пароль заданный при регистрации.', 'Ваша учетная запись активирована. Теперь Вы можете <a href=\"#LINK#\">войти в личный кабинет</a> используя логин и пароль заданный при регистрации.', 'register'),
	('31', 'msg__reg_wrong_link', 'Некорректная ссылка. Повторите активацию!', 'Некорректная ссылка. Повторите активацию!', 'register'),
	('32', 'msg__reg_link', 'Для активации пользователя Вам необходимо перейти по ссылке высланной на Ваш электронный адрес #EMAIL#', 'Для активации пользователя Вам необходимо перейти по ссылке высланной на Ваш электронный адрес #EMAIL#', 'register'),
	('33', 'msg__wrong_login', 'К сожалению, такой логин не найден. Если вы уверены, что данный логин существует, свяжитесь, пожалуйста, с нами.', 'К сожалению, такой логин не найден. Если вы уверены, что данный логин существует, свяжитесь, пожалуйста, с нами.', 'register'),
	('34', 'msg__reg_email_in_use', 'Указанный email уже используется.', 'Указанный email уже используется.', 'register'),
	('35', 'msg__reg_short_pass', 'Пароль менее 5 символов.', 'Пароль менее 5 символов.', 'register'),
	('36', 'msg__reg_wrong_pass', 'Введенные пароли не совпадают.', 'Введенные пароли не совпадают.', 'register'),
	('37', 'msg__reg_wrong_email', 'Неверно заполнено поле email', 'Неверно заполнено поле email', 'register'),
	('38', 'msg__forgot_restore', 'Инструкция по восстановлению пароля была отправлена на <strong>#EMAIL#</strong>.', 'Инструкция по восстановлению пароля была отправлена на <strong>#EMAIL#</strong>.', 'register'),
	('39', 'msg__forgot_wrong_link', 'Некорректная ссылка. Повторите заново запрос восстановления пароля.', 'Некорректная ссылка. Повторите заново запрос восстановления пароля.', 'register'),
	('40', 'msg__forgot_success', 'Пароль изменен! Вы можете войти в личный кабинет по адресу <a href=\"#LINK#\">#LINK#</a>', 'Пароль изменен! Вы можете войти в личный кабинет по адресу <a href=\"#LINK#\">#LINK#</a>', 'register'),
	('41', 'msg__pers_saved', 'Данные успешно сохранены', 'Данные успешно сохранены', 'register'),
	('42', 'msg__pers_wrong_pass', 'Неверный пароль', 'Неверный пароль', 'register'),
	('43', 'msg__pers_pass_changed', 'Пароль изменен', 'Пароль изменен', 'register'),
	('44', 'msg__recaptcha_incorrect', 'reCAPTCHA не пройдена!', 'reCAPTCHA не пройдена!', 'feedback'),
	('45', 'msg__enter_recaptcha_failed', 'reCAPTCHA не пройдена! Авторизоваться не удалось. Количество оставшихся попыток - #COUNT#.', 'reCAPTCHA не пройдена! Авторизоваться не удалось. Количество оставшихся попыток - #COUNT#.', 'register'),
	('46', 'msg__status_not_confirmed', 'не подтвержден', 'не подтвержден', 'status'),
	('47', 'msg__status_expects_payment', 'ожидает оплаты', 'ожидает оплаты', 'status'),
	('48', 'msg__status_paid', 'оплачен', 'оплачен', 'status'),
	('49', 'msg__status_in_delivery', 'в доставке', 'в доставке', 'status'),
	('50', 'msg__status_canceled', 'отменен', 'отменен', 'status'),
	('51', 'msg__status_executed', 'выполнен', 'выполнен', 'status'),
	('52', 'msg__status_processing', 'в обработке', 'в обработке', 'status'),
	('53', 'msg__payment_inn', 'Заполните ИНН', 'Заполните ИНН', 'order')
	");

if(MG::getSetting('printQuantityInMini') === null) {
	MG::setOption(array('option' => 'printQuantityInMini', 'value' => 'false','active' => 'Y', 'name' => 'SHOW_QUANTITY'));
}
if(MG::getSetting('printVariantsInMini') === null) {
	MG::setOption(array('option' => 'printVariantsInMini', 'value' => 'false','active' => 'Y', 'name' => 'SHOW_VARIANT_MINI'));
}
if(MG::getSetting('useReCaptcha') === null) {
	MG::setOption(array('option' => 'useReCaptcha', 'value' => 'false','active' => 'Y', 'name' => 'USE_RECAPTCHA'));
}
if(MG::getSetting('reCaptchaKey') === null) {
	MG::setOption(array('option' => 'reCaptchaKey', 'value' => '','active' => 'Y', 'name' => 'RECAPTCHA_KEY'));
}
if(MG::getSetting('reCaptchaSecret') === null) {
	MG::setOption(array('option' => 'reCaptchaSecret', 'value' => '','active' => 'Y', 'name' => 'RECAPTCHA_SECRET'));
}

if(MG::getSetting('filterCountProp') === null) {
	MG::setOption(array('option' => 'filterCountProp', 'value' => '3','active' => 'Y', 'name' => 'FILTER_COUNT_PROP'));
}
if(MG::getSetting('filterMode') === null) {
	MG::setOption(array('option' => 'filterMode', 'value' => 'true','active' => 'Y', 'name' => 'FILTER_MODE'));
}
if(MG::getSetting('filterSubcategory') === null) {
	MG::setOption(array('option' => 'filterSubcategory', 'value' => 'false','active' => 'Y', 'name' => 'FILTER_SUBCATGORY'));
}

if(MG::getSetting('printCurrencySelector') === null) {
	MG::setOption(array('option' => 'printCurrencySelector', 'value' => 'false','active' => 'Y', 'name' => 'CURRENCY_SELECTOR'));
}

if(MG::getSetting('timeWork') === null) {
	MG::setOption(array('option' => 'timeWork', 'value' => '11:00 - 11:30','active' => 'Y', 'name' => 'TIME_WORK'));
}

if(MG::getSetting('useSeoRewrites') === null) {
	MG::setOption(array('option' => 'useSeoRewrites', 'value' => 'false','active' => 'Y', 'name' => 'SEO_REWRITES'));
}

if(MG::getSetting('useSeoRedirects') === null) {
	MG::setOption(array('option' => 'useSeoRedirects', 'value' => 'false','active' => 'Y', 'name' => 'SEO_REDIRECTS'));
}

// создаем таблицы для интеграций
DB::query(
"CREATE TABLE IF NOT EXISTS `".PREFIX."vk-export` (
	`moguta_id` int(11) NOT NULL,
	`vk_id` varchar(255) NOT NULL,
	`moguta_img` varchar(255) NOT NULL,
	`vk_img` varchar(255) NOT NULL,
	PRIMARY KEY (`moguta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."avito_settings` (
	`name` varchar(255) NOT NULL,
	`settings` longtext NOT NULL,
	`cats` longtext NOT NULL,
	`additional` longtext NOT NULL,
	`edited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."avito_cats` (
	`id` int(255) NOT NULL,
	`name` varchar(255) NOT NULL,
	`parent_id` int(255) NOT NULL,
	UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."avito_locations` (
	`id` int(255) NOT NULL,
	`name` varchar(255) NOT NULL,
	`type` int(5) NOT NULL,
	`parent_id` int(255) NOT NULL,
	UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."googlemerchant` (
	`name` varchar(255) NOT NULL,
	`settings` longtext NOT NULL,
	`cats` longtext NOT NULL,
	`edited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."googlemerchantcats` (
	`id` int(255) NOT NULL,
	`name` varchar(255) NOT NULL,
	`parent_id` int(255) NOT NULL,
	UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DB::query(
"CREATE TABLE IF NOT EXISTS `".PREFIX."landings` (
	`id` int(11) NOT NULL,
	`template` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
	`templateColor` varchar(6) CHARACTER SET utf8 DEFAULT NULL,
	`ytp` longtext CHARACTER SET utf8,
	`image` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
	`buySwitch` varchar(6) CHARACTER SET utf8 DEFAULT NULL,
	UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

DB::query(
	"CREATE TABLE IF NOT EXISTS `".PREFIX."yandexmarket` (
	`name` varchar(255) NOT NULL,
	`settings` longtext NOT NULL,
	`edited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
@set_time_limit(30);
//-----------------------------------------------
// тут делаем реструктуиризацию характеристик
//-----------------------------------------------
// создаем таблицы
DB::query(
	'CREATE TABLE IF NOT EXISTS `'.PREFIX.'product_user_property_data` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`prop_id` int(11) NOT NULL,
		`prop_data_id` int(11) NOT NULL,
		`product_id` int(11) NOT NULL,
		`name` text CHARACTER SET utf8 NOT NULL,
		`margin` text CHARACTER SET utf8 NOT NULL,
		`active` tinyint(4) NOT NULL DEFAULT "1",
		`type_view` text CHARACTER SET utf8 NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
// создаем индексы для product_user_property_data
DatoBaseUpdater::createIndexIfNotExist('product_user_property_data', 'id');
DatoBaseUpdater::createIndexIfNotExist('product_user_property_data', 'prop_id');
DatoBaseUpdater::createIndexIfNotExist('product_user_property_data', 'product_id');

DB::query(
	'CREATE TABLE IF NOT EXISTS `'.PREFIX.'property_data` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`prop_id` int(11) NOT NULL,
		`name` varchar(1024) CHARACTER SET utf8 NOT NULL,
		`margin` text CHARACTER SET utf8 NOT NULL,
		`sort` int(11) NOT NULL DEFAULT "1",
		`color` varchar(45) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
// создаем индексы для property_data
DatoBaseUpdater::createIndexIfNotExist('property_data', 'id');
DatoBaseUpdater::createIndexIfNotExist('property_data', 'name');
DatoBaseUpdater::createIndexIfNotExist('property_data', 'prop_id');

// добавляем столбец в таблицу, если его нет
$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."product_user_property` LIKE 'id'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."product_user_property` ADD `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY");
}

// добавляем столюец для картинок
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'property_data` WHERE FIELD = \'img\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."property_data` ADD `img` text NOT NULL");
}

//-----------------------------------------------
// тыкаем индексы для ускорения работы
//-----------------------------------------------
// для кэша
DatoBaseUpdater::createIndexIfNotExist('cache', 'name');
DatoBaseUpdater::createIndexIfNotExist('cache', 'date_add');
// для категорий
DatoBaseUpdater::createIndexIfNotExist('category', 'id');
// для связки категорий с характеристиками
DatoBaseUpdater::createIndexIfNotExist('category_user_property', 'category_id');
DatoBaseUpdater::createIndexIfNotExist('category_user_property', 'property_id');
// для заказов
DatoBaseUpdater::createIndexIfNotExist('order', 'id');
DatoBaseUpdater::createIndexIfNotExist('order', 'user_email');
DatoBaseUpdater::createIndexIfNotExist('order', 'status_id');
DatoBaseUpdater::createIndexIfNotExist('order', '1c_last_export');
// для товаров
DatoBaseUpdater::createIndexIfNotExist('product', 'id');
DatoBaseUpdater::createIndexIfNotExist('product', 'cat_id');
DatoBaseUpdater::createIndexIfNotExist('product', 'url');
DatoBaseUpdater::createIndexIfNotExist('product', 'code');
// для характеристик
DatoBaseUpdater::createIndexIfNotExist('property', 'id');
DatoBaseUpdater::createIndexIfNotExist('property', 'name');
DatoBaseUpdater::createIndexIfNotExist('property', '1c_id');
// для настроек
DatoBaseUpdater::createIndexIfNotExist('setting', 'option');

//-----------------------------------------------
// добавляем стобцы для размерной сетки
//-----------------------------------------------
// добавляем столбец в таблицу, если его нет (цвет)
$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."product_variant` LIKE 'color'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."product_variant` ADD `color` VARCHAR(255) NOT NULL");
}
// добавляем столбец в таблицу, если его нет (размер)
$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."product_variant` LIKE 'size'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."product_variant` ADD `size` VARCHAR(255) NOT NULL");
}

//-----------------------------------------------
// добавляем таблицу для новых полей заказов
//-----------------------------------------------
DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."custom_order_fields` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`field` text NOT NULL,
		`id_order` int(11) NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DatoBaseUpdater::createIndexIfNotExist('custom_order_fields', 'field');
DatoBaseUpdater::createIndexIfNotExist('custom_order_fields', 'id_order');

//-----------------------------------------------
// добавляем таблицу для мультискладовости
//-----------------------------------------------
DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."product_on_storage` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`storage` text NOT NULL,
	`product_id` int(11) NOT NULL,
	`variant_id` int(11) NOT NULL,
	`count` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
DatoBaseUpdater::createIndexIfNotExist('product_on_storage', 'storage');
DatoBaseUpdater::createIndexIfNotExist('product_on_storage', 'product_id');
DatoBaseUpdater::createIndexIfNotExist('product_on_storage', 'variant_id');

$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'storage\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."order` ADD `storage` text NOT NULL");
}

//-----------------------------------------------
// добавляем таблицу локализаций с индексами
//-----------------------------------------------
DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."locales` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`id_ent` int(11) NOT NULL,
	`locale` varchar(255) CHARACTER SET utf8 NOT NULL,
	`table` varchar(255) CHARACTER SET utf8 NOT NULL,
	`field` varchar(255) CHARACTER SET utf8 NOT NULL,
	`text` longtext CHARACTER SET utf8 NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
// ставим индексы
DatoBaseUpdater::createIndexIfNotExist('locales', 'id');
DatoBaseUpdater::createIndexIfNotExist('locales', 'id_ent');
DatoBaseUpdater::createIndexIfNotExist('locales', 'locale'); 
DatoBaseUpdater::createIndexIfNotExist('locales', 'field');
DatoBaseUpdater::createIndexIfNotExist('locales', 'table');

$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'property` WHERE FIELD = \'group_id\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."property` ADD `group_id` INT(11) NOT NULL");
}

//-----------------------------------------------
// добавляем таблицу для групп характеристик
//-----------------------------------------------
DB::query("CREATE TABLE IF NOT EXISTS `".$prefix."property_group` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`sort` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
MG::setOption(array('option' => 'printStrProp', 'value' => 'false','active' => 'Y', 'name' => 'PROP_STR_PRINT'));

// добавляем в конфиг новый параметр для автоустановки новой версии
if(AUTO_UPDATE == 'AUTO_UPDATE') {
	$configIni ="\r\n";
	$configIni .="; Разрешить ли движку автоматическое обновление файлов, в случае изменения версии PHP на сервере\r\n";
	$configIni .="AUTO_UPDATE = 1\r\n";

	// в конец  конфига добавляем новые строки
	if($configIni) { 
		file_put_contents('config.ini', $configIni, FILE_APPEND);
	}
}

// добавляем в конфиг новый параметр для бэкапов
if(BACKUP_MAX_FILE_SIZE == 'BACKUP_MAX_FILE_SIZE') {
	$configIni ="\r\n";
	$configIni .="; Максимальный размер файлов для добавления в резервные копии (в мегабайтах)\r\n";
	$configIni .="BACKUP_MAX_FILE_SIZE = 30\r\n";

	// в конец  конфига добавляем новые строки
	if($configIni) { 
		file_put_contents('config.ini', $configIni, FILE_APPEND);
	}
}
@set_time_limit(30);

// для групп пользователей
DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."user_group` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`can_drop` tinyint(1) NOT NULL DEFAULT '1',
		`name` varchar(255) NOT NULL DEFAULT '0',
		`admin_zone` tinyint(1) NOT NULL DEFAULT '0',
		`product` tinyint(1) NOT NULL DEFAULT '0',
		`page` tinyint(1) NOT NULL DEFAULT '0',
		`category` tinyint(1) NOT NULL DEFAULT '0',
		`order` tinyint(1) DEFAULT '0',
		`user` tinyint(1) NOT NULL DEFAULT '0',
		`plugin` tinyint(1) NOT NULL DEFAULT '0',
		`setting` tinyint(1) NOT NULL DEFAULT '0',
		`wholesales` tinyint(1) NOT NULL DEFAULT '0',
		UNIQUE KEY `id` (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

DB::query('INSERT IGNORE INTO `'.PREFIX.'user_group` (id, can_drop, name, admin_zone, product, page, category, `order`, user, plugin, setting, wholesales) VALUES
	(1, 0, \'Администратор\', 1, 2, 2, 2, 2, 2, 2, 2, 1),
	(2, 0, \'Пользователь\', 0, 0, 0, 0, 0, 0, 0, 0, 0),
	(3, 0, \'Менеджер\', 1, 2, 0, 1, 2, 0, 2, 0, 0),
	(4, 0, \'Модератор\', 1, 1, 2, 0, 0, 0, 2, 0, 0)');

//дропаем пустые валюты
$curr = unserialize(stripslashes(MG::getOption('currencyRate')));
foreach ($curr as $key => $value) {
	$tmp = trim($key);
	if (empty($tmp)) {
		unset($curr[$key]);
	}
}
MG::setOption(array('option' => 'currencyRate', 'value' => addslashes(serialize($curr))));

$curr = unserialize(stripslashes(MG::getOption('currencyShort')));
foreach ($curr as $key => $value) {
	$tmp = trim($key);
	if (empty($tmp)) {
		unset($curr[$key]);
	}
}
MG::setOption(array('option' => 'currencyShort', 'value' => addslashes(serialize($curr))));

if(MG::getSetting('onOffStorage') === null) {
	MG::setOption(array('option' => 'onOffStorage', 'value' => 'OFF'));
}

if(MG::getSetting('showMainImgVar') === null) {
	MG::setOption(array('option' => 'showMainImgVar', 'value' => 'false', 'active' => 'Y', 'name' => 'SHOW_MAIN_IMG_VAR'));
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."category` WHERE FIELD = 'menu_icon'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."category` ADD `menu_icon` text NOT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."delivery` WHERE FIELD = 'address_parts'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."delivery` ADD `address_parts` INT(1) NOT NULL DEFAULT 0");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."order` WHERE FIELD = 'address_parts'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."order` ADD `address_parts` TEXT DEFAULT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."user` WHERE FIELD = 'address_index'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `address_index` TEXT DEFAULT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."user` WHERE FIELD = 'address_country'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `address_country` TEXT DEFAULT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."user` WHERE FIELD = 'address_region'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `address_region` TEXT DEFAULT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."user` WHERE FIELD = 'address_city'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `address_city` TEXT DEFAULT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."user` WHERE FIELD = 'address_street'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `address_street` TEXT DEFAULT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."user` WHERE FIELD = 'address_house'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `address_house` TEXT DEFAULT NULL");
}

$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."user` WHERE FIELD = 'address_flat'");
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".$prefix."user` ADD `address_flat` TEXT DEFAULT NULL");
}

if(MG::getSetting('weightPropertyName1c') === null) {
	MG::setOption(array('option' => 'weightPropertyName1c', 'value' => 'Вес','active' => 'Y', 'name' => 'WEIGHT_NAME_1C'));
}

if(MG::getSetting('printAgreement') === null) {
	MG::setOption(array('option' => 'printAgreement', 'value' => 'true','active' => 'Y', 'name' => 'PRINT_AGREEMENT'));
}

if(MG::getSetting('useElectroLink') === null) {
	MG::setOption(array('option' => 'useElectroLink', 'value' => 'true', 'active' => 'Y', 'name' => 'USE_ELECTRO_LINK'));
}

if(MG::getSetting('dublinCore') === null) {
	MG::setOption(array('option' => 'dublinCore', 'value' => 'true', 'active' => 'Y', 'name' => 'DUBLIN_CORE'));
}

if(MG::getSetting('closeSite') === null) {
	MG::setOption(array('option' => 'closeSite', 'value' => 'false', 'active' => 'Y', 'name' => 'CLOSE_SITE_1C'));
}

if(MG::getSetting('catalogPreCalcProduct') === null) {
	MG::setOption(array('option' => 'catalogPreCalcProduct', 'value' => 'old', 'active' => 'Y', 'name' => 'CATALOG_PRE_CALC_PRODUCT'));
}

if(MG::getSetting('printSpecFilterBlock') === null) {
	MG::setOption(array('option' => 'printSpecFilterBlock', 'value' => 'true', 'active' => 'Y', 'name' => 'FILTER_PRINT_SPEC'));
}

if(MG::getSetting('disabledPropFilter') === null) {
	MG::setOption(array('option' => 'disabledPropFilter', 'value' => 'false', 'active' => 'Y', 'name' => 'DISABLED_PROP_FILTER'));
}

if(MG::getSetting('enableDeliveryCur') === null) {
	MG::setOption(array('option' => 'enableDeliveryCur', 'value' => 'false', 'active' => 'Y', 'name' => 'ENABLE_DELIVERY_CUR'));
}
if(MG::getSetting('addDateToImg') === null) {
	MG::setOption(array('option' => 'addDateToImg', 'value' => 'true', 'active' => 'Y', 'name' => 'ADD_DATE_TO_IMG'));
}
if(MG::getSetting('variantToSize1c') === null) {
	MG::setOption(array('option' => 'variantToSize1c', 'value' => 'false', 'active' => 'Y', 'name' => 'VARIANT_TO_SIZE_1C'));
}
if(MG::getSetting('filterCatalogMain') === null) {
	MG::setOption(array('option' => 'filterCatalogMain', 'value' => 'false', 'active' => 'Y', 'name' => 'FILTER_CATALOG_MAIN'));
}
if(MG::getSetting('sphinxLimit') === null) {
	MG::setOption(array('option' => 'sphinxLimit', 'value' => '20', 'active' => 'Y', 'name' => 'SPHINX_LIMIT'));
}
if(MG::getSetting('importColorSize') === null) {
	MG::setOption(array('option' => 'importColorSize', 'value' => 'size', 'active' => 'Y', 'name' => 'IMPORT_COLOR_SIZE'));
}
if(MG::getSetting('colorName1c') === null) {
	MG::setOption(array('option' => 'colorName1c', 'value' => 'Цвет', 'active' => 'Y', 'name' => 'COLOR_NAME_1C'));
}
if(MG::getSetting('sizeName1c') === null) {
	MG::setOption(array('option' => 'sizeName1c', 'value' => 'Размер', 'active' => 'Y', 'name' => 'SIZE_NAME_1C'));
}
if(MG::getSetting('sizeMapMod') === null) {
	MG::setOption(array('option' => 'sizeMapMod', 'value' => 'color', 'active' => 'Y', 'name' => 'SIZE_MAP_MOD'));
}

// конфиг правим, если чего-то нет
if(DEBUG_SQL == 'DEBUG_SQL') {
	$str ="\r\n";
	$str .=";Консоль выполненных sql запросов, для генерации страницы\r\n";
	$str .="DEBUG_SQL = 0\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(PROTOCOL == 'PROTOCOL') {
	$str ="\r\n";
	$str .="; Протокол обмена данными с сайтом,(http или https)\r\n";
	$str .="PROTOCOL = \"".(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off'  ? 'https' : 'http')."\"\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(MAX_COUNT_CART == 'MAX_COUNT_CART') {
	$str ="\r\n";
	$str .="; Максимальное количество наименований товаров в одном заказе\r\n";
	$str .="MAX_COUNT_CART = 50\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(SQL_BIG_SELECTS == 'SQL_BIG_SELECTS') {
	$str ="\r\n";
	$str .="; Позволяет использовать объемные запросы на хостинге\r\n";
	$str .="SQL_BIG_SELECTS = 0\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(OLDSCOOL_LINK == 'OLDSCOOL_LINK') {
	$str ="\r\n";
	$str .="; Включает дубли страниц заканчивающиеся на .html\r\n";
	$str .="OLDSCOOL_LINK = 0\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(CREATE_TMB == 'CREATE_TMB') {
	$str = "\r\n";
	$str .= "; Опция для создания папок с файлами .quarantine и .thums при работе в elfinder \r\n";
	$str .= "CREATE_TMB = 0 \r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(UPDATE_SERVER == 'UPDATE_SERVER') {
	$str = "\r\n";
	$str .= "; Сервер обновлений плагинов и движка \r\n";
	$str .= "UPDATE_SERVER = 'http://updata.moguta.ru' \r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(LOG_USER_AGENT == 'LOG_USER_AGENT') {
	$str = "\r\n";
	$str .= "; создает текстовый файл log_user_agent.txt с возможными юзер-агентам по обращению на страницу проверки форм. Подробнее в файлe mg-pages/mg-formvalid.php \r\n";
	$str .= "LOG_USER_AGENT = 0\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(ENCODE_YML_CATALOG == 'ENCODE_YML_CATALOG') {
	$str = "\r\n";
	$str .= "; Кодировка для выгрузки каталога в Яндекс.Маркет по ссылке /getyml, значение none - кодировка не будет указана \r\n";
	$str .= "ENCODE_YML_CATALOG = 'windows-1251'\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(YML_ONLY_AVAILABLE == 'YML_ONLY_AVAILABLE') {
	$str = "\r\n";
	$str .= "; Выгрузка на Яндекс.Маркет по ссылке /getyml всех товаров (=0) или только тех, которые есть в наличии (=1) \r\n";
	$str .= "YML_ONLY_AVAILABLE = 0\r\n";
	file_put_contents('config.ini', $str, FILE_APPEND);
}

if(MG::getSetting('loginAttempt') === null) {
	if (LOGIN_ATTEMPT == 'LOGIN_ATTEMPT') {
		MG::setOption(array('option' => 'loginAttempt', 'value' => '5', 'active' => 'Y', 'name' => 'LOGIN_ATTEMPT'));
	}
	else{
		MG::setOption(array('option' => 'loginAttempt', 'value' => LOGIN_ATTEMPT, 'active' => 'Y', 'name' => 'LOGIN_ATTEMPT'));
	}
}

if(MG::getSetting('prefixOrder') === null) {
	if (PREFIX_ORDER == 'PREFIX_ORDER') {
		MG::setOption(array('option' => 'prefixOrder', 'value' => 'M-010', 'active' => 'Y', 'name' => 'PREFIX_ORDER'));
	}
	else{
		MG::setOption(array('option' => 'prefixOrder', 'value' => PREFIX_ORDER, 'active' => 'Y', 'name' => 'PREFIX_ORDER'));
	}
}

if(MG::getSetting('captchaOrder') === null) {
	if (CAPTCHA_ORDER == 1) {
		MG::setOption(array('option' => 'captchaOrder', 'value' => 'true', 'active' => 'Y', 'name' => 'CAPTCHA_ORDER'));
	}
	else{
		MG::setOption(array('option' => 'captchaOrder', 'value' => 'false', 'active' => 'Y', 'name' => 'CAPTCHA_ORDER'));
	}
}

if(MG::getSetting('deliveryZero') === null) {
	if (DELIVERY_ZERO == 0) {
		MG::setOption(array('option' => 'deliveryZero', 'value' => 'false', 'active' => 'Y', 'name' => 'DELIVERY_ZERO'));
	}
	else{
		MG::setOption(array('option' => 'deliveryZero', 'value' => 'true', 'active' => 'Y', 'name' => 'DELIVERY_ZERO'));
	}
}

if(MG::getSetting('outputMargin') === null) {
	if (OUTPUT_MARGIN == 0) {
		MG::setOption(array('option' => 'outputMargin', 'value' => 'false', 'active' => 'Y', 'name' => 'OUTPUT_MARGIN'));
	}
	else{
		MG::setOption(array('option' => 'outputMargin', 'value' => 'true', 'active' => 'Y', 'name' => 'OUTPUT_MARGIN'));
	}
}

if(MG::getSetting('prefixCode') === null) {
	if (PREFIX_CODE == 'PREFIX_CODE') {
		MG::setOption(array('option' => 'prefixCode', 'value' => 'CN', 'active' => 'Y', 'name' => 'PREFIX_CODE'));
	}
	else{
		MG::setOption(array('option' => 'prefixCode', 'value' => PREFIX_CODE, 'active' => 'Y', 'name' => 'PREFIX_CODE'));
	}
}

if(MG::getSetting('maxUploadImgWidth') === null) {
	if (MAX_UPLOAD_IMAGE_WIDTH == 'MAX_UPLOAD_IMAGE_WIDTH') {
		MG::setOption(array('option' => 'maxUploadImgWidth', 'value' => '1500', 'active' => 'Y', 'name' => 'MAX_UPLOAD_IMAGE_WIDTH'));
	}
	else{
		MG::setOption(array('option' => 'maxUploadImgWidth', 'value' => MAX_UPLOAD_IMAGE_WIDTH, 'active' => 'Y', 'name' => 'MAX_UPLOAD_IMAGE_WIDTH'));
	}
}

if(MG::getSetting('maxUploadImgHeight') === null) {
	if (MAX_UPLOAD_IMAGE_HEIGHT == 'MAX_UPLOAD_IMAGE_HEIGHT') {
		MG::setOption(array('option' => 'maxUploadImgHeight', 'value' => '1500', 'active' => 'Y', 'name' => 'MAX_UPLOAD_IMAGE_HEIGHT'));
	}
	else{
		MG::setOption(array('option' => 'maxUploadImgHeight', 'value' => MAX_UPLOAD_IMAGE_HEIGHT, 'active' => 'Y', 'name' => 'MAX_UPLOAD_IMAGE_HEIGHT'));
	}
}

if(MG::getSetting('searchType') === null) {
	if ((int)SEARCH_SPHINX === 1) {
		MG::setOption(array('option' => 'searchType', 'value' => 'sphinx', 'active' => 'Y', 'name' => 'SEARCH_TYPE'));
	}
	elseif((int)SEARCH_FULLTEXT === 1) {
		MG::setOption(array('option' => 'searchType', 'value' => 'fulltext', 'active' => 'Y', 'name' => 'SEARCH_TYPE'));
	}
	else{
		MG::setOption(array('option' => 'searchType', 'value' => 'like', 'active' => 'Y', 'name' => 'SEARCH_TYPE'));
	}
}

if(MG::getSetting('searchSphinxHost') === null) {
	if (SEARCH_SPHINX_HOST == 'SEARCH_SPHINX_HOST') {
		MG::setOption(array('option' => 'searchSphinxHost', 'value' => 'localhost', 'active' => 'Y', 'name' => 'SEARCH_SPHINX_HOST'));
	}
	else{
		MG::setOption(array('option' => 'searchSphinxHost', 'value' => SEARCH_SPHINX_HOST, 'active' => 'Y', 'name' => 'SEARCH_SPHINX_HOST'));
	}
}

if(MG::getSetting('searchSphinxPort') === null) {
	if (SEARCH_SPHINX_PORT == 'SEARCH_SPHINX_PORT') {
		MG::setOption(array('option' => 'searchSphinxPort', 'value' => '9312', 'active' => 'Y', 'name' => 'SEARCH_SPHINX_PORT'));
	}
	else{
		MG::setOption(array('option' => 'searchSphinxPort', 'value' => SEARCH_SPHINX_PORT, 'active' => 'Y', 'name' => 'SEARCH_SPHINX_PORT'));
	}
}

if(MG::getSetting('checkAdminIp') === null) {
	if(CHECK_ADMIN_IP == 1) {
		MG::setOption(array('option' => 'checkAdminIp', 'value' => 'true', 'active' => 'Y', 'name' => 'CHECK_ADMIN_IP'));
	}
	else{
		MG::setOption(array('option' => 'checkAdminIp', 'value' => 'false', 'active' => 'Y', 'name' => 'CHECK_ADMIN_IP'));
	}
}

if(MG::getSetting('printSeo') === null) {
	if (PRINT_SEO == 'PRINT_SEO') {
		MG::setOption(array('option' => 'printSeo', 'value' => 'all', 'active' => 'Y', 'name' => 'PRINT_SEO'));
	}
	else{
		MG::setOption(array('option' => 'printSeo', 'value' => PRINT_SEO, 'active' => 'Y', 'name' => 'PRINT_SEO'));
	}
}

if(MG::getSetting('catalogProp') === null) {
	if (CATALOG_PROP == 'CATALOG_PROP') {
		MG::setOption(array('option' => 'catalogProp', 'value' => '0', 'active' => 'Y', 'name' => 'CATALOG_PROP'));
	}
	else{
		MG::setOption(array('option' => 'catalogProp', 'value' => CATALOG_PROP, 'active' => 'Y', 'name' => 'CATALOG_PROP'));
	}
}
@set_time_limit(30);
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."payment` LIKE 'permission'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."payment` ADD `permission` varchar(5) NOT NULL DEFAULT 'fiz'");
	DB::query("UPDATE `".PREFIX."payment` SET `permission` = 'yur' WHERE `id` = 7");
}

if(substr_count(MG::getSetting('phoneMask'), '9') > 0) {
	MG::setOption('phoneMask', str_replace('9', '#', MG::getSetting('phoneMask')));
}

DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."notification` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`message` longtext NOT NULL,
		`status` tinyint(1) NOT NULL DEFAULT '0',
		UNIQUE KEY `id` (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'category` WHERE FIELD = \'seo_alt\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `seo_alt` text");
}
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'category` WHERE FIELD = \'seo_title\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `seo_title` text");
}
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'delivery` WHERE FIELD = \'weight\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."delivery` ADD `weight` longtext");
}
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'delivery` WHERE FIELD = \'interval\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."delivery` ADD `interval` longtext");
}
$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'pay_date\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."order` ADD `pay_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'");
	DB::query('UPDATE '.PREFIX.'order SET pay_date = updata_date');
}

$dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'order` WHERE FIELD = \'delivery_interval\'');
if(!$row = DB::fetchArray($dbRes)) {
	DB::query("ALTER TABLE `".PREFIX."order` ADD `delivery_interval` text NOT NULL");
}

DB::query('UPDATE '.PREFIX.'payment SET urlArray = \'{"result URL:":"/payment?id=18&pay=result"}\' WHERE id = 18');

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'countProduct'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `countProduct` int(11) DEFAULT 0");
}

DB::query('DROP TABLE IF EXISTS `'.PREFIX.'short_prop_id`');

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."wholesales_sys` LIKE 'group'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."wholesales_sys` ADD `group` int(11) DEFAULT 1");
	$data = array(1);
	$data = addslashes(serialize($data));
	MG::setOption('wholesalesGroup', $data);
}

$res = DB::query('SELECT id FROM '.PREFIX.'user_group WHERE id = -1');
if(!DB::fetchAssoc($res)) {
	DB::query('INSERT INTO '.PREFIX.'user_group (`id`, `can_drop`, `name`, `admin_zone`, `product`, `page`, 
		`category`, `order`, `user`, `plugin`, `setting`, `wholesales`) VALUES
		(-1, 0, \'Гость (Не авторизован)\', 0, 0, 0, 0, 0, 0, 0, 0, 0)');
}

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'left_key'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `left_key` int(11) DEFAULT 0 AFTER id");
}
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'right_key'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `right_key` int(11) DEFAULT 0 AFTER left_key");
}
$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'level'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `level` int(11) DEFAULT 0 AFTER right_key");
}
DatoBaseUpdater::createIndexIfNotExist('category', 'left_key');
DatoBaseUpdater::createIndexIfNotExist('category', 'right_key');
DatoBaseUpdater::createIndexIfNotExist('category', 'level');

if(MG::getSetting('modParamInVarName') === null) {
	MG::setOption(array('option' => 'modParamInVarName', 'value' => 'false', 'active' => 'Y', 'name' => 'MOD_PARAM_IN_VAR_NAME'));
}

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'menu_title'");
if(!$row = DB::fetchArray($dbQuery)) {
	DB::query("ALTER TABLE `".PREFIX."category` ADD `menu_title` TEXT NOT NULL DEFAULT '' AFTER title");
}

}

if (DatoBaseUpdater::check("8.2.1")) { 

	DB::query('UPDATE '.PREFIX.'url_rewrite SET url = REPLACE(url, '.DB::quote(SITE).', \'\')');

}

if (DatoBaseUpdater::check("8.3.0")) {

	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."order` LIKE 'owner'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."order` ADD `owner` int(11) NOT NULL DEFAULT 0 AFTER id");
	}

	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."user` LIKE 'owner'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."user` ADD `owner` int(11) NOT NULL DEFAULT 0 AFTER id");
	}

	if(MG::getSetting('orderOwners') === null) {
		MG::setOption(array('option' => 'orderOwners', 'value' => 'false', 'active' => 'Y', 'name' => 'ORDER_OWNERS'));
	}

	DB::query('ALTER TABLE '.PREFIX.'category MODIFY meta_title text DEFAULT NULL');
	DB::query('ALTER TABLE '.PREFIX.'category MODIFY meta_keywords text DEFAULT NULL');

	DB::query('ALTER TABLE '.PREFIX.'page MODIFY meta_title text DEFAULT NULL');
	DB::query('ALTER TABLE '.PREFIX.'page MODIFY meta_keywords text DEFAULT NULL');

	DB::query('ALTER TABLE '.PREFIX.'product MODIFY meta_title text DEFAULT NULL');
	DB::query('ALTER TABLE '.PREFIX.'product MODIFY meta_keywords text DEFAULT NULL');
	
	DB::query('ALTER TABLE '.PREFIX.'url_rewrite MODIFY meta_title text DEFAULT NULL');
	DB::query('ALTER TABLE '.PREFIX.'url_rewrite MODIFY meta_keywords text DEFAULT NULL');

	if(!defined('NULL_OLD_PRICE') || NULL_OLD_PRICE == 'NULL_OLD_PRICE') {
		$str = "\r\n";
		$str .= "; Приравнивать к 0 старую цену товара, если старая цена меньше обычной\r\n";
		$str .= "NULL_OLD_PRICE = 1\r\n";
		file_put_contents('config.ini', $str, FILE_APPEND);
	}
}

if (DatoBaseUpdater::check("8.4.0")) { 
	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."user` LIKE 'op'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."user` ADD `op` TEXT COMMENT 'Дополнительные поля'");
	}

	if(MG::getSetting('invisibleReCaptcha') === null) {
		MG::setOption(array('option' => 'invisibleReCaptcha', 'value' => 'false', 'active' => 'Y', 'name' => 'INVISIBLE_RECAPTCHA'));
	}

	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'write_lock (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`table` varchar(255) NOT NULL,
		`entity_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		`time_block` int(11) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	if(MG::getSetting('blockEntity') === null) {
		MG::setOption(array('option' => 'blockEntity', 'value' => 'false', 'active' => 'Y', 'name' => 'BLOCK_ENTITY'));
	}

	//обновляем запись про сбербанк
	$res = DB::query('SELECT `paramArray` FROM `'.PREFIX.'payment` WHERE id = 17');
	if ($row = DB::fetchArray($res)) {
		if (
			strpos($row['paramArray'], "Использовать онлайн кассу") == false && 
			strpos($row['paramArray'], "НДС на товары") == false && 
			strpos($row['paramArray'], "НДС на доставку") == false &&
			strpos($row['paramArray'], "Система налогообложения") == false
		) {
			$row = substr($row['paramArray'], 0, -1).',"Использовать онлайн кассу":"ZmFsc2VoWSo2bms3ISEjMnFq","Система налогообложения":"MGhZKjZuazchISMycWo=","НДС на товары":"M2hZKjZuazchISMycWo=","НДС на доставку":"M2hZKjZuazchISMycWo="}';
			DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($row)." WHERE id = 17");
		}
	}
}

if (DatoBaseUpdater::check("8.5.0")) { 
	$templates = array_diff(scandir(SITE_DIR.'mg-templates'), array('.', '..'));
	foreach ($templates as $template) {
		$file = SITE_DIR.'mg-templates'.DS.$template.DS.'layout'.DS.'payment_yandex-kassa.php';
		if (is_file($file)) {
			$content = file_get_contents($file);
			$changedContent = str_replace('name="orderId"', 'name="orderMId"', $content);
			if ($content != $changedContent) {
				file_put_contents($file, $changedContent);
			}
		}
	}

	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'menu_seo_title'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."category` ADD `menu_seo_title` TEXT NOT NULL AFTER `seo_title`");
	}
	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'menu_seo_alt'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."category` ADD `menu_seo_alt` TEXT NOT NULL AFTER `seo_title`");
	}

	if(MG::getSetting('useFavorites') === null) {
		MG::setOption(array('option' => 'useFavorites', 'value' => 'true', 'active' => 'Y', 'name' => 'USE_FAVORITES'));
	}

	if(MG::getSetting('varHashProduct') === null) {
		MG::setOption(array('option' => 'varHashProduct', 'value' => 'true', 'active' => 'Y', 'name' => 'VAR_HASH_PRODUCT'));
	}

	if(MG::getSetting('useSearchEngineInfo') === null) {
		MG::setOption(array('option' => 'useSearchEngineInfo', 'value' => 'false', 'active' => 'Y', 'name' => 'USE_SEARCH_ENGINE_INFO'));
	}
}

if (DatoBaseUpdater::check("8.6.0")) { 

	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'order_opt_fields (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`type` varchar(255) NOT NULL,
		`vars` TEXT,
		`sort` int(11),
		`droped` int(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'order_opt_fields_content (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`field_id` int(11) NOT NULL DEFAULT 0,
		`order_id` int(11) NOT NULL DEFAULT 0,
		`value` TEXT,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."order_opt_fields` LIKE 'placeholder'");
	if(!$row = DB::fetchArray($dbQuery)) {
		DB::query("ALTER TABLE `".PREFIX."order_opt_fields` ADD `placeholder` TEXT NOT NULL DEFAULT ''");
	}

	// адаптизация старых полей под новые
	include_once SITE_DIR.'mg-code'.DS.'models'.DS.'addFieldsOrder.php';
	$data = unserialize(stripslashes(MG::getSetting('optionalFields')));
	$toFields = array();
	foreach ($data as $key => $value) {
	    $tmp = array();
	    $tmp['name'] = $value['name'];
	    $tmp['type'] = $value['type'];
	    $tmp['vars'] = $value['variants'];
	    $toFields[] = $tmp;
	}
	$oldFields = Models_OpFieldsOrder::getFields();
	if(!$oldFields) {
		foreach ($oldFields as $key => $value) {
		    $toFields[] = $value;
		}
		Models_OpFieldsOrder::saveFields($toFields);
		$newFields = Models_OpFieldsOrder::getFields();
		foreach ($newFields as $key => $item) {
		    $name = trim(MG::translitIt($item['name']));
		    $res = DB::query('SELECT id_order, value FROM '.PREFIX.'custom_order_fields WHERE `field` = '.DB::quote($name));
		    while($row = DB::fetchAssoc($res)) {
		        if($item['type'] == 'checkbox' && $row['value'] == $name) $row['value'] = 'true';
		        $opFieldM = new Models_OpFieldsOrder($row['id_order']);
		        $opFieldM->fill(array($item['id'] => $row['value']));
		        $opFieldM->save();
		    }
		}
	}

	$retailOptions = unserialize(stripslashes(MG::getSetting('retailcrm')));
	if (!empty($retailOptions['retailOpFields'])) {
		$newRetailFields = $opNames = array();
		$res = DB::query("SELECT `name` FROM `".PREFIX."order_opt_fields`");
		while($row = DB::fetchAssoc($res)) {
			$opNames[trim(MG::translitIt($row['name']))] = $row['name'];
		}

		foreach ($retailOptions['retailOpFields'] as $key => $value) {
			$res = DB::query("SELECT `id` FROM `".PREFIX."order_opt_fields` WHERE `name` = ".DB::quote($opNames[trim(MG::translitIt($key))]));
			if ($row = DB::fetchAssoc($res)) {
				$newRetailFields[$row['id']] = $value;
			}
		}
		$retailOptions['retailOpFields'] = $newRetailFields;
		MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($retailOptions)), 'active' => 'N'));
	}

	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'user_opt_fields (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`type` varchar(255) NOT NULL,
		`vars` TEXT,
		`sort` int(11),
		`active` tinyint(1) DEFAULT 0,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'user_opt_fields_content (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`field_id` int(11) NOT NULL DEFAULT 0,
		`user_id` int(11) NOT NULL DEFAULT 0,
		`value` TEXT,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	// для доп полей у товаров
	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'product_opt_fields (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` TEXT,
		`is_price` tinyint(1) DEFAULT 0,
		`sort` int(11),
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."product_opt_fields` LIKE 'active'");
	if(!$row = DB::fetchArray($dbQuery)) {
		DB::query("ALTER TABLE `".PREFIX."product_opt_fields` ADD `active` tinyint(1) DEFAULT 1");
	}

	$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."user_opt_fields` LIKE 'placeholder'");
	if(!$row = DB::fetchArray($dbQuery)) {
		DB::query("ALTER TABLE `".PREFIX."user_opt_fields` ADD `placeholder` TEXT NOT NULL DEFAULT ''");
	}
	
	DB::query("INSERT IGNORE INTO `".$prefix."messages` (`id`, `name`, `text`, `text_original`, `group`) VALUES
		('54', 'msg__payment_required', 'Заполнены не все обязательные поля', 'Заполнены не все обязательные поля', 'order')");

	if(MG::getSetting('timezone') === null) {
		MG::setOption(array('option' => 'timezone', 'value' => 'noChange', 'active' => 'Y', 'name' => 'TIMEZONE'));
	}

}

if (DatoBaseUpdater::check("8.7.0")) { 
	$retailOptions = MG::getSetting('retailcrm');
	if ($retailOptions) {
		$retailOptions = unserialize(stripslashes($retailOptions));
		if ($retailOptions['syncRemains'] == 'true') {
			$retailOptions['syncRemainsBack'] = 'true';
			MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($retailOptions)), 'active' => 'N'));
		}
	}	
	if(MG::getSetting('recalcWholesale') === null) {
		MG::setOption(array('option' => 'recalcWholesale', 'value' => 'true', 'active' => 'Y', 'name' => 'RECALC_WHOLESALE'));
	}

	// дроп повторов из настроек
	$dbRes = DB::query("SHOW INDEXES FROM `".PREFIX."setting` WHERE Column_name='option' AND NOT Non_unique");
	if(!$row = DB::fetchArray($dbRes)) {
		DB::query("DELETE FROM ".PREFIX."setting USING ".PREFIX."setting,
				".PREFIX."setting e1
			WHERE ".PREFIX."setting.id > e1.id
			AND ".PREFIX."setting.option = e1.option");
		DB::query("ALTER TABLE `".PREFIX."setting` ADD UNIQUE(`option`)");
	}

	//Для настроек синхронизации 1С
	if(MG::getSetting('notUpdate1C') === null) {
		MG::setOption(array('option' => 'notUpdate1C', 'value' => 'a:10:{s:8:\"1c_title\";s:4:\"true\";s:7:\"1c_code\";s:4:\"true\";s:6:\"1c_url\";s:4:\"true\";s:9:\"1c_weight\";s:4:\"true\";s:8:\"1c_count\";s:4:\"true\";s:14:\"1c_description\";s:4:\"true\";s:12:\"1c_image_url\";s:4:\"true\";s:13:\"1c_meta_title\";s:4:\"true\";s:16:\"1c_meta_keywords\";s:4:\"true\";s:11:\"1c_activity\";s:4:\"true\";}', 'active' => 'y', 'name' => 'update_1c'));
	}

	if(MG::getSetting('writeLog1C') === null) {
		MG::setOption(array('option' => 'writeLog1C', 'value' => 'false', 'active' => 'Y', 'name' => 'WRITE_LOG_1C'));
	}

	if(MG::getSetting('writeFiles1C') === null) {
		MG::setOption(array('option' => 'writeFiles1C', 'value' => 'false', 'active' => 'Y', 'name' => 'WRITE_FILES_1C'));
	}

	if(MG::getSetting('writeFullName1C') === null) {
		MG::setOption(array('option' => 'writeFullName1C', 'value' => 'false', 'active' => 'Y', 'name' => 'WRITE_FULL_NAME_1C'));
	}

	if(MG::getSetting('activityCategory1C') === null) {
		MG::setOption(array('option' => 'activityCategory1C', 'value' => 'true', 'active' => 'Y', 'name' => 'ACTIVITY_CATEGORY_1C'));
	}
}

if (DatoBaseUpdater::check("8.8.0")) { 
	@unlink(SITE_DIR.'mg-admin'.DS.'section'.DS.'views'.DS.'integrations'.DS.'avito_cats.zip');
	@unlink(SITE_DIR.'mg-admin'.DS.'section'.DS.'views'.DS.'integrations'.DS.'google_merchant_cats.zip');
	@unlink(SITE_DIR.'mg-admin'.DS.'section'.DS.'views'.DS.'layout'.DS.'op-fields-product.php');
	@unlink(SITE_DIR.'mg-pages'.DS.'colorSet.zip');
	@unlink(SITE_DIR.'mg-pages'.DS.'comic.ttf');
	@unlink(SITE_DIR.'mg-pages'.DS.'order_form_default.js');
	if (is_file(SITE_DIR.'mg-pages'.DS.'order_form.js')) {
		rename(SITE_DIR.'mg-pages'.DS.'order_form.js', SITE_DIR.'mg-core'.DS.'script'.DS.'standard'.DS.'js'.DS.'order_form.js');
	}
	if(MG::getSetting('product404') === null) {
		if (PRODUCT_404 == 1) {
			MG::setOption(array('option' => 'product404', 'value' => 'true', 'active' => 'Y'));
		} else {
			MG::setOption(array('option' => 'product404', 'value' => 'false', 'active' => 'Y'));
		}
	}
	if(MG::getSetting('product404Sitemap') === null) {
		MG::setOption(array('option' => 'product404Sitemap', 'value' => 'true', 'active' => 'Y'));
	}

	$tmp = explode(',', MG::getSetting('adminEmail'));
	foreach ($tmp as $ke => $va) {
		$tmp[$ke] = trim($va);
	}
	$tmp = array_filter($tmp);
	$tmp = implode(',', $tmp);
	MG::setOption(array('option' => 'adminEmail', 'value' => $tmp));

	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'category_opt_fields (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`sort` int(11),
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	DB::query('CREATE TABLE IF NOT EXISTS '.PREFIX.'category_opt_fields_content (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`field_id` int(11) NOT NULL DEFAULT 0,
		`category_id` int(11) NOT NULL DEFAULT 0,
		`value` TEXT,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');

	//Список соответствий статусов для 1С
	if (MG::getSetting('listMatch1C') === null) {
		$listStatus = array();

		if (class_exists('statusOrder')) {
			$dbQuery = DB::query('SELECT `id_status`, `status` FROM `'.PREFIX.'mg-status-order`');
			while ($dbRes = DB::fetchArray($dbQuery)) {
				$listStatus[$dbRes['id_status']] = $dbRes['status'];
			}
		} else {
			$lang = MG::get('lang');
			$ls = Models_Order::$status;
			foreach ($ls as $key => $value) {
				$listStatus[$key] = $lang[$value];
			}
		}

		MG::setOption(array('option' => 'listMatch1C', 'value' => addslashes(serialize($listStatus))));
	}
	DB::query("UPDATE `".PREFIX."product` SET `count_buy` = 0 WHERE `count_buy` IS NULL");
	DB::query("ALTER TABLE `".PREFIX."product` CHANGE `count_buy` `count_buy` INT(11) NOT NULL DEFAULT 0");
}

if (DatoBaseUpdater::check("8.9.0")) {
	if(!defined('PREVIEW_TEMPLATE')) {
		$str ="\r\n";
		$str .="; Возможность просматривать шаблоны сайта с помощью GET параметра tpl= (папка шаблона), цвет шаблона color= (код цвета шаблона, например, 81bd60) и вкл/выкл плагинов pm= (on/off)\r\n";
		$str .="PREVIEW_TEMPLATE = 0\r\n";
		file_put_contents('config.ini', $str, FILE_APPEND);
	}

	if(MG::getSetting('productFilterPriceSliderStep') === null) {
		MG::setOption(array('option' => 'productFilterPriceSliderStep', 'value' => '10', 'active' => 'Y', 'name' => 'PRODUCT_FILTER_PRICE_SLIDER_STEP'));
	}
}

if (DatoBaseUpdater::check("8.10.0")) {
	// реструктуризация настроек вывода колонок товаров
	if (MG::getSetting('catalogColumns') === null) {
		MG::setOption(array(
			'option' => 'catalogColumns', 
			'value' => 'a:6:{i:0;s:6:\"number\";i:1;s:8:\"category\";i:2;s:3:\"img\";i:3;s:5:\"title\";i:4;s:5:\"price\";i:5;s:5:\"count\";}', 
			'active' => 'Y')
		);
	} else {
		$oldColumns = unserialize(stripcslashes(MG::getSetting('catalogColumns')));
		$newColumns = array();

		foreach ($oldColumns as $name => $value) {
			if (!$name) {
				break;
			}
			if ($value == 'true') {
				$newColumns[] = $name;
			}
			if ($name == 'img') {
				$newColumns[] = 'title';
			}
		}
		if (!empty($newColumns)) {
			MG::setOption(array(
				'option' => 'catalogColumns', 
				'value' => addslashes(serialize($newColumns)), 
				'active' => 'Y')
			);
		}
	}

	// реструктуризация настроек вывода колонок заказов
	if (MG::getSetting('orderColumns') === null) {
		MG::setOption(array(
			'option' => 'orderColumns', 
			'value' => 'a:9:{i:0;s:2:\"id\";i:1;s:6:\"number\";i:2;s:4:\"date\";i:3;s:3:\"fio\";i:4;s:5:\"email\";i:5;s:4:\"summ\";i:6;s:5:\"deliv\";i:7;s:7:\"payment\";i:8;s:6:\"status\";}', 
			'active' => 'Y')
		);
	} else {
		$oldColumns = unserialize(stripcslashes(MG::getSetting('orderColumns')));
		$newColumns = array();

		foreach ($oldColumns as $name => $value) {
			if (!$name) {
				break;
			}
			if ($name == 'additional') {
				foreach ($value as $op) {
					$newColumns[] = 'opf_'.$op;
				}
			}
			if ($value == 'true') {
				$newColumns[] = $name;
			}
			if ($name == 'id') {
				$newColumns[] = 'number';
			}
		}
		if (!empty($newColumns)) {
			MG::setOption(array(
				'option' => 'orderColumns', 
				'value' => addslashes(serialize($newColumns)), 
				'active' => 'Y')
			);
		}
	}

	// реструктуризация настроек вывода колонок пользователей
	if (MG::getSetting('userColumns') === null) {
		MG::setOption(array(
			'option' => 'userColumns', 
			'value' => 'a:5:{i:0;s:5:\"email\";i:1;s:6:\"status\";i:2;s:5:\"group\";i:3;s:8:\"register\";i:4;s:8:\"personal\";}', 
			'active' => 'Y')
		);
	} else {
		$oldColumns = unserialize(stripcslashes(MG::getSetting('userColumns')));
		$newColumns = array();

		foreach ($oldColumns as $name => $value) {
			if (!$name) {
				break;
			}
			if ($name == 'additional') {
				foreach ($value as $op) {
					$newColumns[] = 'opf_'.$op;
				}
			}
			if ($value == 'true') {
				$newColumns[] = $name;
			}
		}
		if (!empty($newColumns)) {
			MG::setOption(array(
				'option' => 'userColumns', 
				'value' => addslashes(serialize($newColumns)), 
				'active' => 'Y')
			);
		}
	}

	// реструктуризация настроек формы заказа
	$oldFields = unserialize(stripcslashes(MG::getSetting('orderFormFields')));
	if (!empty($oldFields)) {
		$newFields = array();
		$sortShift = 0;
		$addEnctype = false;
		foreach ($oldFields as $key => $field) {
			if (!$field['active']) {continue;}
			if ($key == 'yur_info') {
				$sort = $field['sort']+$sortShift;
				$sortShift--;
				foreach (
					array(
						'yur_info_nameyur',
						'yur_info_adress',
						'yur_info_inn',
						'yur_info_kpp',
						'yur_info_bank',
						'yur_info_bik',
						'yur_info_ks',
						'yur_info_rs',
					) as $newField) {
					$newFields[$newField] = array(
						'active' => 1,
			            'sort' => $sort,
			            'required' => $field['required'],
			            'conditionType' => $field['conditionType'],
					);
					if (isset($field['conditions'])) {
						$newFields[$newField]['conditions'] = $field['conditions'];
					}
					$sort++;
					$sortShift++;
				}
			} elseif ($key == 'address_parts') {
				$sort = $field['sort']+$sortShift;
				$sortShift--;
				foreach (
					array(
						'address_index',
						'address_country',
						'address_region',
						'address_city',
						'address_street',
						'address_house',
						'address_flat',
					) as $newField) {
					$newFields[$newField] = array(
						'active' => 1,
			            'sort' => $sort,
			            'required' => $field['required'],
			            'conditionType' => $field['conditionType'],
					);
					if (isset($field['conditions'])) {
						$newFields[$newField]['conditions'] = $field['conditions'];
					}
					$sort++;
					$sortShift++;
				}
			} else {
				$field['sort'] += $sortShift;
				$newFields[$key] = $field;
			}
			if ($field['type'] == 'file') {
				$addEnctype = true;
			}
		}
		MG::setOption(array(
			'option' => 'orderFormFields', 
			'value' => addslashes(serialize($newFields)), 
			'active' => 'Y')
		);
		if (class_exists('Models_OpFieldsOrder')) {
			Models_OpFieldsOrder::rebuildJs($newFields, $addEnctype);
		}
	}

	$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."user` LIKE 'pname'");
	if(!$row = DB::fetchArray($dbQuery)) {
		DB::query("ALTER TABLE `".PREFIX."user` ADD `pname` VARCHAR(255) COLLATE utf8_general_ci NULL AFTER `sname`");
	}
	$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."order` LIKE 'name_parts'");
	if(!$row = DB::fetchArray($dbQuery)) {
		DB::query("ALTER TABLE `".PREFIX."order` ADD `name_parts` TEXT COLLATE utf8_general_ci NULL AFTER `name_buyer`");
	}

	if(MG::getSetting('useNameParts') === null) {
		MG::setOption(array('option' => 'useNameParts', 'value' => 'false', 'active' => 'Y', 'name' => 'ORDER_NAME_PARTS'));
	}
	if(MG::getSetting('printOneColor') === null) {
		MG::setOption(array('option' => 'printOneColor', 'value' => 'false', 'active' => 'Y', 'name' => 'PRINT_ONE_COLOR'));
	}
	if(MG::getSetting('updateStringProp1C') === null) {
		MG::setOption(array('option' => 'updateStringProp1C', 'value' => 'false', 'active' => 'Y', 'name' => 'UPDATE_STRING_PROP_1C'));
	}

	//обновляем запись про робокассу
	$dbRes = DB::query('select `paramArray` FROM `'.PREFIX.'payment` WHERE id = 5');
	$res = DB::fetchArray($dbRes);
	if (strpos($res['paramArray'], "Использовать онлайн кассу") == false && strpos($res['paramArray'], "НДС, включенный в цену") == false) {
		$res = substr($res['paramArray'], 0, -1).',"Использовать онлайн кассу":"ZmFsc2VoWSo2bms3ISEjMnFq","НДС, включенный в цену":"MjAlaFkqNm5rNyEhIzJxag=="}';
		DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 5");
	}
}

if (DatoBaseUpdater::check("8.12.0")) {
	if(MG::getSetting('openGraphLogoPath') === null) {
		MG::setOption(array('option' => 'openGraphLogoPath', 'value' => '','active' => 'Y', 'name' => 'OPEN_GRAPH_LOGO_PATH'));
	}

	if(MG::getSetting('convertCountToHR') === null) {
		MG::setOption(array('option' => 'convertCountToHR', 'value' => '','active' => 'Y', 'name' => 'CONVERT_COUNT_TO_HR'));
	}

	$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."page` LIKE 'without_style'");
	if(!$row = DB::fetchArray($dbQuery)) {
		DB::query("ALTER TABLE `".PREFIX."page` ADD `without_style` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Выводить без стилей шаблона'");
	}

	$mysqlVersion = mysqli_get_server_version(DB::$connection);
	if ($mysqlVersion >= 50503) {
		$first = true;
		$longIndexes = array(
			PREFIX.'avito_settings'	=>'name',
			PREFIX.'cache'			=>'name',
			PREFIX.'googlemerchant'	=>'name',
			PREFIX.'messages'		=>'name',
			PREFIX.'plugins'		=>'folderName',
			PREFIX.'setting'		=>'option',
			PREFIX.'yandexmarket'	=>'name',
			PREFIX.'sessions'		=>'session_id',
		);

		$res = DB::query("SELECT TABLE_NAME FROM information_schema.`TABLES` T,
		       	information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
				WHERE CCSA.collation_name = T.table_collation
				  AND T.table_schema = '".NAME_BD."'
				  AND (CCSA.character_set_name <> 'utf8mb4' OR T.TABLE_COLLATION <> 'utf8mb4_general_ci')");

		while($row = DB::fetchArray($res)) {
			if ($first) {
				DB::query("ALTER DATABASE `".NAME_BD."` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;",1);
				$first = false;
			}
			$table = $row['TABLE_NAME'];

			if (isset($longIndexes[$table])) {
				DB::query("ALTER TABLE `".$table."` CHANGE `".$longIndexes[$table]."` `".$longIndexes[$table]."` VARCHAR(249)");
			}
			DB::query("ALTER TABLE `".$table."` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci",1);
		}
		DB::query("ALTER TABLE `".PREFIX."page` CHANGE `url` `url` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;");
	} elseif(!defined('UTF8MB4')) {
		$configIni ="\r\n";
		$configIni .="; Использовать кодировку utf8mb4\r\n";
		$configIni .="UTF8MB4 = 0\r\n";
		file_put_contents('config.ini', $configIni, FILE_APPEND);
	}

	DB::query("INSERT IGNORE INTO `".$prefix."messages` (`id`, `name`, `text`, `text_original`, `group`) VALUES
		('55', 'msg__storage_non_selected', 'Склад не выбран!', 'Склад не выбран!', 'product')");

	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."category` LIKE 'weight_unit'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."category` ADD `weight_unit` VARCHAR(10) NOT NULL DEFAULT 'kg';");
	}
	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."product` LIKE 'weight_unit'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."product` ADD `weight_unit` VARCHAR(10) DEFAULT NULL;");
	}

	if(MG::getSetting('weightUnit1C') === null) {
		MG::setOption(array('option' => 'weightUnit1C', 'value' => 'kg', 'active' => 'Y', 'name' => 'WEIGHT_UNIT_1C'));
	}
}

if (DatoBaseUpdater::check("8.13.0")) {
	if(MG::getSetting('backgroundColorSite') === null) {
		MG::setOption(array('option' => 'backgroundColorSite', 'value' => '', 'active' => 'Y', 'name' => 'BACKGROUND_COLOR_SITE'));
	}

	if(MG::getSetting('backgroundTextureSite') === null) {
		MG::setOption(array('option' => 'backgroundTextureSite', 'value' => '', 'active' => 'Y', 'name' => 'BACKGROUND_TEXTURE_SITE'));
	}

	if(MG::getSetting('backgroundSiteLikeTexture') === null) {
		MG::setOption(array('option' => 'backgroundSiteLikeTexture', 'value' => 'false', 'active' => 'Y', 'name' => 'BACKGROUND_SITE_LIKE_TEXTURE'));
	}

	if(MG::getSetting('fontSite') === null) {
		MG::setOption(array('option' => 'fontSite', 'value' => '', 'active' => 'Y', 'name' => 'FONT_SITE'));
	}
	
	MG::setOption(array('option' => 'themeBackground', 'value' => 'bg_7.png', 'active' => 'N', 'name' => 'ADMIN_THEM_BG'));

	$interface = unserialize(stripslashes(MG::getSetting('interface')));
	if (!isset($interface['adminBar'])) {
		$interface['adminBar'] = '#000000bf';
		MG::setOption('interface', addslashes(serialize($interface)));
	}

	DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."order_comments` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` int(11) NOT NULL,
		`order_id` int(11) NOT NULL,
		`text` longtext NOT NULL,
		`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY `id` (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

if (DatoBaseUpdater::check("8.14.0")) {
	//обновляем запись про Яндекс.Кассу по апи
	$dbRes = DB::query('select `paramArray` FROM `'.PREFIX.'payment` WHERE id = 24');
	$res = DB::fetchArray($dbRes);
	if (strpos($res['paramArray'], "Использовать онлайн кассу") == false && strpos($res['paramArray'], "НДС, включенный в цену") == false) {
		$res = substr($res['paramArray'], 0, -1).',"Использовать онлайн кассу":"ZmFsc2VoWSo2bms3ISEjMnFq","НДС, включенный в цену":"MjAlaFkqNm5rNyEhIzJxag=="}';
		DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 24");
	}

	// настройка поиска при нестандартном языке
	if(MG::getSetting('searchInDefaultLang') === null) {
		MG::setOption(array('option' => 'searchInDefaultLang', 'value' => 'true', 'active' => 'Y', 'name' => 'SEARCH_IN_DEFAULT_LANG'));
	}

	// настройка бэкапа перед обновлением
	if(MG::getSetting('backupBeforeUpdate') === null) {
		MG::setOption(array('option' => 'backupBeforeUpdate', 'value' => 'true', 'active' => 'Y', 'name' => 'BACKUP_BEFORE_UPDATE'));
	}

	// таблица сохранения авторизации вне сессии
	DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."user_logins` ( 
			`created_at` BIGINT(20) NULL DEFAULT NULL,
			`user_id` INT(11) NULL DEFAULT NULL,
			`access` TINYTEXT NULL DEFAULT NULL,
			`last_used` INT(11) NULL DEFAULT NULL,
			`fails` TINYTEXT NULL DEFAULT NULL,
			UNIQUE KEY (`created_at`)
		) ENGINE = InnoDB;");

	// настройка сохранения авторизации вне сессии
	if(MG::getSetting('rememberLogins') === null) {
		MG::setOption(array('option' => 'rememberLogins', 'value' => 'true', 'active' => 'Y', 'name' => 'REMEMBER_LOGINS'));
	}
	// настройка количества дней хранения авторизации вне сессии
	if(MG::getSetting('rememberLoginsDays') === null) {
		MG::setOption(array('option' => 'rememberLoginsDays', 'value' => '180', 'active' => 'Y', 'name' => 'REMEMBER_LOGINS_DAYS'));
	}

	// блокировка авторизации пользователей через базу
	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."user` LIKE 'fails'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."user` ADD `fails` TINYTEXT NULL DEFAULT NULL;");
	}
	// настройка времени блокировки авторизации
	if(MG::getSetting('loginBlockTime') === null) {
		MG::setOption(array('option' => 'loginBlockTime', 'value' => '15', 'active' => 'Y', 'name' => 'LOGIN_BLOCK_TIME'));
	}

	// обновление сообщения о блокировке авторизации
	DB::query("UPDATE `".PREFIX."messages` 
		SET `text` = 'В целях безопасности возможность авторизации заблокирована на #MINUTES# мин. Отсчет времени от #TIME#.'
		WHERE `text` = 'В целях безопасности возможность авторизации заблокирована на 15 мин. Отсчет времени от #TIME#.'");
	DB::query("UPDATE `".PREFIX."messages` 
		SET `text_original` = 'В целях безопасности возможность авторизации заблокирована на #MINUTES# мин. Отсчет времени от #TIME#.'
		WHERE `name` = 'msg__enter_blocked'");

	// настройка вывода селекта мультиязычности в публичке
	if(MG::getSetting('printMultiLangSelector') === null) {
		MG::setOption(array('option' => 'printMultiLangSelector', 'value' => 'true', 'active' => 'Y', 'name' => ''));
	}

	// изменение структуры хранения мультиязычности
	$multiLang = unserialize(stripcslashes(MG::getSetting('multiLang')));
	$dropFiles = array();
	$currentTemplateLocalesPath = SITE_DIR.'mg-templates'.DS.MG::getSetting('templateName').DS.'locales'.DS;
	$defaultTemplateLocalesPath = SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'locales'.DS;
	$coreLocalesPath = SITE_DIR.'mg-core'.DS.'locales'.DS;

	if (is_array($multiLang)) {
		foreach ($multiLang as $key => $lang) {
			if (is_dir($currentTemplateLocalesPath) && !empty($lang['template_lang']) && $lang['template_lang'] != 'default' && $lang['template_lang'] != $lang['short']) {
				foreach (['.php','.js'] as $ext) {
					if (!is_file($currentTemplateLocalesPath.$lang.$ext)) {
						if (is_file($currentTemplateLocalesPath.$item['template_lang'].$ext)) {
							copy($currentTemplateLocalesPath.$item['template_lang'].$ext, $currentTemplateLocalesPath.$lang['short'].$ext);
							$dropFiles[] = $currentTemplateLocalesPath.$item['template_lang'].$ext;
						} elseif (is_file($currentTemplateLocalesPath.'default'.$ext)) {
							copy($currentTemplateLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang['short'].$ext);
						} elseif (is_file($defaultTemplateLocalesPath.'default'.$ext)) {
							copy($defaultTemplateLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang['short'].$ext);
						} elseif (is_file($coreLocalesPath.'default'.$ext)) {
							copy($coreLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang['short'].$ext);
						} else {
							file_put_contents($currentTemplateLocalesPath.$lang['short'].$ext, '');
						}
					}
				}
			}
			if (!empty($lang['template_lang'])) {
				unset($lang['template_lang']);
			}
			if (empty($lang['enabled'])) {
				$lang['enabled'] = 'true';
			}
			$multiLang[$key] = $lang;
		}
		if (!empty($dropFiles)) {
			foreach ($dropFiles as $file) {
				@unlink($file);
			}
		}
		MG::setOption('multiLang', addslashes(serialize($multiLang)));
	}

	if(!defined('CART_IN_COOKIE')) {
		$str = "\r\n";
		$str .= "; Сохранение корзины в cookie (0 - отключить, 1 - работает для всех, 2 - включить только для администратора)\r\n";
		$str .= "CART_IN_COOKIE = 1\r\n";
		file_put_contents('config.ini', $str, FILE_APPEND);
	}

	//расположение водяного знака
	if(MG::getSetting('waterMarkPosition') === null) {
		MG::setOption(array('option' => 'waterMarkPosition', 'value' => 'center', 'active' => 'Y', 'name' => 'WATERMARKPOSITION'));
	}

	//миниатюры для ретины
	if(MG::getSetting('imageResizeRetina') === null) {
		MG::setOption(array('option' => 'imageResizeRetina', 'value' => 'false', 'active' => 'Y', 'name' => 'IMAGE_RESIZE_RETINA'));
	}

	//Старая цена в 1с
	if(MG::getSetting('oldPriceName1c') === null) {
		MG::setOption(array('option' => 'oldPriceName1c', 'value' => '', 'active' => 'Y', 'name' => 'OLD_PRICE_NAME_1C'));
	}

	//Ширина для картинок характеристики
	if(MG::getSetting('propImgWidth') === null) {
		MG::setOption(array('option' => 'propImgWidth', 'value' => '50', 'active' => 'Y', 'name' => 'PROP_IMG_WIDTH'));
	}

	//Высота для картинок характеристики
	if(MG::getSetting('propImgHeight') === null) {
		MG::setOption(array('option' => 'propImgHeight', 'value' => '50', 'active' => 'Y', 'name' => 'PROP_IMG_HEIGHT'));
	}

	//отображение заданного количества характеристик в фильтрах
	if(MG::getSetting('filterCountShow') === null) {
		MG::setOption(array('option' => 'filterCountShow', 'value' => '5','active' => 'Y', 'name' => 'FILTER_COUNT_SHOW'));
	}
}

if (DatoBaseUpdater::check("8.15.0")) {
	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."user_group` LIKE 'order_status'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."user_group` ADD `order_status` TEXT NULL DEFAULT NULL COMMENT 'доступные статусы заказов';");
	}
	if(MG::getSetting('ownerRotation') === null) {
		MG::setOption(array('option' => 'ownerRotation', 'value' => 'false', 'active' => 'Y', 'name' => 'OWNER_ROTATION'));
	}

	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."user_group` LIKE 'ignore_owners'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."user_group` ADD `ignore_owners` TINYINT NULL DEFAULT '0' COMMENT 'игнорировать ответственных';");
	}
	if(MG::getSetting('ownerList') === null) {
		MG::setOption(array('option' => 'ownerList', 'value' => '', 'active' => 'Y', 'name' => 'OWNER_LIST'));
	}

	if(MG::getSetting('ownerRemember') === null) {
		MG::setOption(array('option' => 'ownerRemember', 'value' => 'false', 'active' => 'Y', 'name' => 'OWNER_REMBER'));
	}

	if(MG::getSetting('ownerRememberDays') === null) {
		MG::setOption(array('option' => 'ownerRememberDays', 'value' => '14', 'active' => 'Y', 'name' => 'OWNER_REMBER_DAYS'));
	}

  if(MG::getSetting('ownerRememberPhone') === null) {
    MG::setOption(array('option' => 'ownerRememberPhone', 'value' => 'false', 'active' => 'Y', 'name' => 'OWNER_REMBER_PHONE'));
  }

  if(MG::getSetting('ownerRememberEmail') === null) {
    MG::setOption(array('option' => 'ownerRememberEmail', 'value' => 'false', 'active' => 'Y', 'name' => 'OWNER_REMBER_EMAIL'));
  }

	if(MG::getSetting('ownerRotationCurrent') === null) {
		MG::setOption(array('option' => 'ownerRotationCurrent', 'value' => '', 'active' => 'Y', 'name' => 'OWNER_REMBER_CURRENT'));
	}

	if(!defined('MASS_IMG_DELAY')) {
		$str = "\r\n";
		$str .= "; Задержка при массовом создании миниатюр изображений в секундах (если ваш хостинг ограничивает максимальную нагрузку на процессор и при пересоздании миниатюр или создании изображений после загрузки CSV появляется ошибка, то увеличьте значение этой настройки)\r\n";
		$str .= "MASS_IMG_DELAY = 0.001\r\n";
		file_put_contents('config.ini', $str, FILE_APPEND);
	}

	if(MG::getSetting('disabledPropFilter') === 'true') {
		MG::setOption('disabledPropFilter', 'disable');
	}

	if(MG::getSetting('categoryIconHeight') === null) {
		MG::setOption(array('option' => 'categoryIconHeight', 'value' => '150', 'active' => 'Y', 'name' => ''));
	}
	if(MG::getSetting('categoryIconWidth') === null) {
		MG::setOption(array('option' => 'categoryIconWidth', 'value' => '150', 'active' => 'Y', 'name' => ''));
	}

  if(MG::getSetting('notUpdate1C') != null) {
    $syncOptions1c = unserialize(stripslashes(MG::getSetting('notUpdate1C')));
    $syncOptions1c['1c_old_price'] = 'true';
    $syncOptions1c = addslashes(serialize($syncOptions1c));
    MG::setOption('notUpdate1C', $syncOptions1c);
  }
}

if (DatoBaseUpdater::check("8.15.2")) {
  $options = unserialize(stripslashes(MG::getSetting('notUpdate1C')));
  if($options === null || count($options) <= 10) {
    MG::setOption(array('option' => 'notUpdate1C', 'value' => 'a:11:{s:8:\"1c_title\";s:4:\"true\";s:7:\"1c_code\";s:4:\"true\";s:6:\"1c_url\";s:4:\"true\";s:9:\"1c_weight\";s:4:\"true\";s:8:\"1c_count\";s:4:\"true\";s:14:\"1c_description\";s:4:\"true\";s:12:\"1c_image_url\";s:4:\"true\";s:13:\"1c_meta_title\";s:4:\"true\";s:16:\"1c_meta_keywords\";s:4:\"true\";s:11:\"1c_activity\";s:4:\"true\";s:12:\"1c_old_price\";s:4:\"true\";}', 'y', 'update_1c'));
  }
  if(MG::getSetting('ownerRememberPhone') != null) {
    MG::setOption(array('option' => 'ownerRememberPhone', 'value' => 'false', 'active' => 'Y', 'name' => 'OWNER_REMBER_PHONE'));
  }
  if(MG::getSetting('ownerRememberEmail') != null) {
    MG::setOption(array('option' => 'ownerRememberEmail', 'value' => 'false', 'active' => 'Y', 'name' => 'OWNER_REMBER_EMAIL'));
  }
}

if (DatoBaseUpdater::check("9.0.0")) {
	$res = DB::query("SHOW COLUMNS FROM `".PREFIX."plugins` LIKE 'template'");
	if(!$row = DB::fetchArray($res)) {
		DB::query("ALTER TABLE `".PREFIX."plugins` ADD `template` TEXT NOT NULL DEFAULT '';");
	}
	DB::query("ALTER TABLE `".PREFIX."plugins` DROP INDEX `name`;",1);

  //Розничная цена в 1с
  if(MG::getSetting('retailPriceName1c') === null) {
    MG::setOption(array('option' => 'retailPriceName1c', 'value' => '', 'active' => 'Y', 'name' => 'RETAIL_PRICE_NAME_1C'));
  }

  // Универсальные шаблоны писем
  DB::query("CREATE TABLE IF NOT EXISTS `".$prefix."letters` (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  content mediumtext NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

  $sql = DB::query("SELECT * FROM `".$prefix."letters`");
  $res = DB::fetchAssoc($sql);
  if (empty($res)) {
    DB::query("INSERT INTO `".$prefix."letters` (id, name, content) VALUES " .
    "(1, 'email_feedback.php', '<h1 style=\'margin: 0 0 10px 0; font-size: 16px;padding: 0;\'>Сообщение с формы обратной связи!</h1><p style=\'padding: 0;margin: 10px 0;font-size: 12px;\'>Пользователь <strong>{userName}</strong> с почтовым ящиком <strong>{userEmail}</strong> пишет:</p><div style=\'margin: 0;padding: 10px;background: #FFF5B5; font-weight: bold;\'>{message}</div>'),
    (2, 'email_forgot.php', '<h1 style=\"margin: 0 0 10px 0; font-size: 16px;padding: 0;\">Здравствуйте!</h1><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Вы зарегистрированы на сайте <strong>{siteName}</strong> с логином <strong>{userEmail}</strong></p><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Для восстановления пароля пройдите по ссылке</p><div style=\"margin: 0;padding: 10px;background: #FFF5B5; font-weight: bold; text-align: center;\"><a href=\"{link}\" target=\"blank\"> {link} </a></div><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Если Вы не делали запрос на восстановление пароля, то проигнорируйте это письмо.</p><p style=\"padding: 0;margin: 10px 0;font-size: 10px; color: #555; font-weight: bold;\">Отвечать на данное сообщение не нужно.</p>'),
    (3, 'email_order.php', '<table bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"10\" border=\"0\" width=\"675\"><tbody><tr><td valign=\"top\"><h1 style=\"margin: 0 0 10px 0; font-size: 16px;padding: 0;\">Здравствуйте, {fullName}!</h1><div style=\"font-size:12px;line-height:16px;margin:0;\">Ваш заказ <b>№{orderNumber}</b> успешно оформлен.<p class=\"confirm-info\" style=\"font-size:12px;margin:0 0 10px 0\"><br>Перейдите по {confirmLink} для подтверждения заказа и создания личного кабинета<br><br>Следить за статусом заказа вы можете в <a href=\"{personal}\" style=\"color:#1E7EC8;\" target=\"_blank\">личном кабинете</a>.</p><br>Если у Вас возникнут вопросы — их можно задать по почте: <a href=\"mailto:{adminEmail}\" style=\"color:#1E7EC8;\" target=\"_blank\">{adminEmail}</a> или по телефону: <span><span class=\"js-phone-number highlight-phone\">{shopPhone}</span></span></div></td></tr></tbody></table>{tableOrder}'),
    (4, 'email_order_change_status.php', '<p style=\"font-size:12px;line-height:16px;margin:0;\">Здравствуйте, <b>{buyerName}</b>!<br/> Статус Вашего заказа <b>№{orderInfo}</b> был изменен c \"<b>{oldStatus}</b>\" на \"<b>{newStatus}</b>\".<br/> Следить за состоянием заказа Вы можете в <a href=\"{personal}\">личном кабинете</a>.</p>'),
    (5, 'email_order_electro.php', '<p style=\"font-size:12px;line-height:16px;margin:0;\">Ваш заказ <b>№{orderNumber}</b> содержит электронные товары, которые можно скачать по следующей ссылке:<br/> <a href=\"{getElectro}\">{getElectro}</a></p>'),
    (6, 'email_order_new_user.php', '<table bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"10\" border=\"0\" width=\"675\"><tbody><tr><td valign=\"top\"><h1 style=\"margin: 0 0 10px 0; font-size: 16px;padding: 0;\">Здравствуйте, {fullName}!</h1><div style=\"font-size:12px;line-height:16px;margin:0;\"><br>Мы создали для вас <a href=\"{personal}\" style=\"color:#1E7EC8;\" target=\"_blank\">личный кабинет</a>, чтобы вы могли следить за статусом заказа, а также скачивать оплаченные электронные товары.<br><br><b>Ваш логин:</b> {userEmail}<br><b>Ваш пароль:</b> {pass}</div></td></tr></tbody></table>'),
    (7, 'email_order_paid.php', '<p style=\"font-size:12px;line-height:16px;margin:0;\">Вы получили это письмо, так как произведена оплата заказа №{orderNumber} на сумму {summ}. Оплата произведена при помощи {payment} <br/>Статус заказа сменен на \"{status} \"</p>'),
    (8, 'email_order_status.php', '<p style=\"font-size:12px;line-height:16px;margin:0;\">Здравствуйте,  <b>{buyerName}</b>!<br/> Статус Вашего заказа <b>№{orderInfo}</b> был изменен c \"<b>{oldStatus}</b>\" на \"<b>{newStatus}</b>\".<br/> Следить за состоянием заказа Вы можете в <a href=\"{personal}\">личном кабинете</a>.</p>'),
    (9, 'email_registry.php', '<h1 style=\"margin: 0 0 10px 0; font-size: 16px;padding: 0;\">Здравствуйте!</h1><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Вы получили данное письмо так как зарегистрировались на сайте <strong>{siteName}</strong> с логином <strong>{userEmail}</strong></p><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Для активации пользователя и возможности пользоваться личным кабинетом пройдите по ссылке:</p><div style=\"margin: 0;padding: 10px;background: #FFF5B5; font-weight: bold; text-align: center;\">{link}</div><p style=\"padding: 0;margin: 10px 0;font-size: 10px; color: #555; font-weight: bold;\">Отвечать на данное сообщение не нужно.</p>'),
    (10, 'email_registry_independent.php', '<h1 style=\"margin: 0 0 10px 0; font-size: 16px;padding: 0;\">Здравствуйте!</h1><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Вы получили данное письмо так как на сайте <strong>{siteName} </strong> зарегистрирован новый пользователь с логином <strong>{userEmail}</strong></p><p style=\"padding: 0;margin: 10px 0;font-size: 10px; color: #555; font-weight: bold;\">Отвечать на данное сообщение не нужно.</p>'),
    (11, 'email_unclockauth.php', '<h1 style=\"margin: 0 0 10px 0; font-size: 16px;padding: 0;\">Подбор паролей на сайте {siteName} предотвращен!</h1><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Система защиты от перебора паролей для авторизации зафиксировала активность. С IP адреса {IP} было введено более 5 неверных паролей. Последний email: <strong>{lastEmail}</strong> Пользователь вновь сможет ввести пароль через 15 минут.</p><p style=\"padding: 0;margin: 10px 0;font-size: 12px;\">Если 5 неправильных попыток авторизации были инициированы администратором,то для снятия блокировки перейдите по ссылке</p><div style=\"margin: 0;padding: 10px;background: #FFF5B5; font-weight: bold; text-align: center;\">{link}</div><p style=\"padding: 0;margin: 10px 0;font-size: 10px; color: #555; font-weight: bold;\">Отвечать на данное сообщение не нужно.</p>')
    ");
  }

  if(MG::getSetting('timeWorkDays') === null) {
    MG::setOption(array('option' => 'timeWorkDays', 'value' => 'Пн-Пт:,Сб-Вс:', 'active' => 'Y', 'name' => ''));
  }

}

if (DatoBaseUpdater::check("9.1.0")) {
  $res = DB::query("SHOW COLUMNS FROM `".$prefix."letters` LIKE 'lang'");
  if(!$row = DB::fetchArray($res)) {
    DB::query("ALTER TABLE `".$prefix."letters` ADD `lang` varchar(50) NOT NULL DEFAULT 'default';");
  }

  DatoBaseUpdater::createIndexIfNotExist('product_variant', 'product_id');

  $sql = DB::query("SELECT * FROM `".$prefix."letters` WHERE name = 'email_order_change_status.php'");
  $res = DB::fetchAssoc($sql);
  if (!empty($res)) {
    DB::query("UPDATE `".$prefix."letters` SET content = '<p style=\"font-size:12px;line-height:16px;margin:0;\">Здравствуйте, <b>{buyerName}</b>!<br/> Статус Вашего заказа <b>№{orderInfo}</b> был изменен c \"<b>{oldStatus}</b>\" на \"<b>{newStatus}</b>\".<br/>{managerComment}<br/>Следить за состоянием заказа Вы можете в <a href=\"<?php echo SITE.\'/personal\'?>\">личном кабинете</a>.</p>' WHERE name = 'email_order_change_status.php'");
  }

  if(!defined('MAX_IMAGES_COUNT')) {
    $str = "\r\n";
    $str .= "; Максимальное количество картинок, которое обрабатывается за 1 итерацию при генерации миниатюр\r\n";
    $str .= "MAX_IMAGES_COUNT = 25\r\n";
    file_put_contents('config.ini', $str, FILE_APPEND);
  }
}

if (DatoBaseUpdater::check("9.3.0")) {

   if(MG::getSetting('logger') === NULL ){
       MG::setOption(array('option' => 'logger', 'value' => 'false', 'active' => 'Y', 'name'=>'LOGGER'));
   }

  $sqlQueryTo = array();
    // изменение типа поля количества товара с инта на флоат
  $sqlQueryTo[] = "ALTER TABLE `".PREFIX."wholesales_sys` CHANGE `count` `count` FLOAT(11) NOT NULL";
  $sqlQueryTo[] = "ALTER TABLE `".PREFIX."product` CHANGE `count` `count` FLOAT(11) NOT NULL";
  $sqlQueryTo[] = "ALTER TABLE `".PREFIX."product_variant` CHANGE `count` `count` FLOAT(11) NOT NULL";

  $dbRes = DB::query("SHOW COLUMNS FROM `".$prefix."product` LIKE 'multiplicity'");

  if(!$row = DB::fetchArray($dbRes)) {
    $sqlQueryTo[] = "ALTER TABLE `".$prefix."product` ADD `multiplicity` FLOAT NOT NULL DEFAULT 1";
  }

  DatoBaseUpdater::execute($sqlQueryTo);
  unset($sqlQueryTo);

  $options = unserialize(stripslashes(MG::getSetting('notUpdate1C')));
  if($options === null || count($options) <= 11) {
    MG::setOption(array('option' => 'notUpdate1C', 'value' => 'a:12:{s:8:\"1c_title\";s:4:\"true\";s:7:\"1c_code\";s:4:\"true\";s:6:\"1c_url\";s:4:\"true\";s:9:\"1c_weight\";s:4:\"true\";s:8:\"1c_count\";s:4:\"true\";s:14:\"1c_description\";s:4:\"true\";s:12:\"1c_image_url\";s:4:\"true\";s:13:\"1c_meta_title\";s:4:\"true\";s:16:\"1c_meta_keywords\";s:4:\"true\";s:11:\"1c_activity\";s:4:\"true\";s:12:\"1c_old_price\";s:4:\"true\";s:15:\"1c_multiplicity\";s:5:\"false\";}', 'y', 'update_1c'));
  }

  if(MG::getSetting('useMultiplicity') === null) {
    MG::setOption(array('option' => 'useMultiplicity', 'value' => 'false', 'active' => 'Y', 'name' => 'USE_MULTIPLICITY'));
  }

  if(MG::getSetting('multiplicityPropertyName1c') === null) {
    MG::setOption(array('option' => 'multiplicityPropertyName1c', 'value' => 'Кратность', 'active' => 'Y', 'name' => 'MULTIPLICITY_NAME_1C'));
  }

  if(MG::getSetting('hitsFlag') === NULL ){
      MG::setOption(array('option' => 'hitsFlag', 'value' => 'false', 'active' => 'Y', 'name'=>'HITSFLAG'));
  }
  
  if(MG::getSetting('introFlag') === NULL ){
      MG::setOption(array('option' => 'introFlag', 'value' => '[]', 'active' => 'Y', 'name'=>'INTRO_FLAG'));
  }

  if(MG::getSetting('useAbsolutePath') === NULL ){
      MG::setOption(array('option' => 'useAbsolutePath', 'value' => 'false', 'active' => 'Y', 'name'=>'USE_ABSOLUTE_PATH'));
  }

  $res = DB::query("SHOW COLUMNS FROM `".PREFIX."delivery` LIKE 'description_public'");
  if(!$row = DB::fetchArray($res)) {
    DB::query("ALTER TABLE `".PREFIX."delivery` ADD `description_public` TEXT NULL AFTER `name`;");
  }
  $res = DB::query("SHOW COLUMNS FROM `".$prefix."user` LIKE 'login_email'");
  if(!$row = DB::fetchArray($res)) {
	  DB::query("ALTER TABLE `".$prefix."user` ADD `login_email` VARCHAR(255) NOT NULL AFTER `fails`");
	  DB::query("UPDATE `".$prefix."user` SET `login_email` = `email`");
  }

}

if (DatoBaseUpdater::check("9.3.2")) {
  $res = DB::query("SHOW COLUMNS FROM `".PREFIX."order` LIKE 'contact_email'");
  if(!$row = DB::fetchArray($res)) {
	DB::query("ALTER TABLE `".$prefix."order` ADD `contact_email` VARCHAR(255) NOT NULL AFTER `user_email`");
	DB::query("UPDATE `".$prefix."order` SET `contact_email` = `user_email`");
  }
}

if (DatoBaseUpdater::check("9.4.0")) {
	DB::query("ALTER TABLE `".PREFIX."user` CHANGE `login_email` `login_email` VARCHAR(249) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
	
	
	
	//DB::query("ALTER TABLE `".PREFIX."user` ADD UNIQUE(`login_email`);");
    // добавляем в конфиг новый параметр для использования старого объединения JS
    //$configIni ="\r\n";
    //$configIni .="; Для использования старого алгорима объединения JS в один файл (0 - исплользуется новый алгоритм, 1 - используется старый алгоритм)\r\n";
    //$configIni .="OLD_CACHE_JS = 0\r\n";
    //file_put_contents('config.ini', $configIni, FILE_APPEND);
  
    //Для складов дефолтное списывание
    if(MG::getSetting('storages_settings') === NULL ){
      MG::setOption(array('option' => 'storages_settings', 'value' => 'a:3:{s:12:\"writeOffProc\";s:1:\"1\";s:28:\"storagesAlgorithmWithoutMain\";s:0:\"\";s:11:\"mainStorage\";s:0:\"\";}', 'active' => 'N', 'name' => 'STORAGES_SETTINGS'));
    }    
    //Для складов поле, отвечающее за отображение складов у выбранного типа доставки
    $res = DB::query("SHOW COLUMNS FROM `".PREFIX."delivery` LIKE 'show_storages'");
    if(!$row = DB::fetchArray($res)) {
      DB::query("ALTER TABLE `".PREFIX."delivery` ADD `show_storages` VARCHAR(249) NOT NULL DEFAULT '0' AFTER `address_parts`;");
    }
    //Поле для выгрузки едиинц измерения из 1С
    if(MG::getSetting('1c_unit_item') === NULL){
      MG::setOption(array('option' => '1c_unit_item', 'value' => 'Килограмм:кг,Метр:м,Квадратный метр:м2,Кубический метр:м3,Штука:шт.,Литр:л', 'active' => 'Y', 'name' => '1C_UNIT_ITEM'));
    }
  }

	if (DatoBaseUpdater::check("9.4.1")) {
		
		$res = DB::query("SHOW COLUMNS FROM `".PREFIX."user` LIKE 'login_phone'");
		if(!$row = DB::fetchArray($res)) {
		  DB::query("ALTER TABLE `".PREFIX."user` ADD `login_phone` VARCHAR(50) NOT NULL AFTER `login_email`");
		}

		// Пустые E-mail
		$sql = "SELECT * FROM `" . PREFIX . "user` WHERE `email` = ''";
		$sql .= " AND `login_phone` = ''";
		$res = DB::query($sql);
		while ($row = DB::fetchArray($res)) {
			DB::query("SELECT * FROM `" . PREFIX . "user` WHERE `login_email` = ".DB::quote($row['login_email']));
			$affectedRows = DB::affectedRows();
			if($affectedRows == 1){
				DB::query("UPDATE `" . PREFIX . "user` SET `email` = `login_email` WHERE `login_email` = ".DB::quote($row['login_email']));
			}
		}
		DB::query("DELETE FROM `" . PREFIX . "user` WHERE `email` = ''");
		// Повторяющиеся `login-email`
		$res = DB::query("SELECT `login_email`,COUNT(*) AS total FROM `" . PREFIX . "user` GROUP BY `login_email` HAVING COUNT(*) > 1");
		while ($row = DB::fetchArray($res)) {
			$user = DB::query("SELECT `id` FROM `" . PREFIX . "user` WHERE `login_email` = ".DB::quote($row['login_email']));
			$affectedRows = DB::affectedRows();
			$currentRow = 1;
			while ($rows = DB::fetchArray($user)) {
				if($currentRow != $affectedRows){
					DB::query("DELETE FROM `" . PREFIX . "user` WHERE `id` = ".DB::quote($rows['id']));
				}
				$currentRow++;
			}
		}
		// Удаление повторяющихся индексов
		$res = DB::query("SHOW INDEX FROM `" . PREFIX . "user` WHERE `Column_name` = 'login_email'");
		while ($row = DB::fetchArray($res)) {
			DB::query("DROP INDEX `".$row['Key_name']."` ON `" . PREFIX . "user`");
		}
		DB::query("CREATE UNIQUE INDEX `login_email` ON `" . PREFIX . "user`(`login_email`)");
	}
    
    if (DatoBaseUpdater::check("9.4.2")) {
      //Патч для .htaccess
      $fileWr= file_get_contents('.htaccess');
      $fileWr = str_replace('RewriteCond %{REQUEST_URI} \.(png|gif|ico|swf|jpe?g|js|css|ttf|svg|eot|woff|yml|xml|zip|txt|doc)$', 'RewriteCond %{REQUEST_URI} \.(png|gif|ico|swf|jpe?g|js|css|ttf|svg|eot|woff|yml|txt|xml|zip|doc|map)$', $fileWr);
      file_put_contents('.htaccess', $fileWr);
      
	}
	
	if (DatoBaseUpdater::check("9.5.0")) {
		//Перемещение файлов из корня в TEMP_DIR
		$files = array_diff(scandir(SITE_DIR), ['.','..']);
		foreach ($files as $file) {
			$copyFile = false;
			$copyDir = false;
			// CSV
			if(preg_match('/data\_csv\_(\d|\_)+\.csv/',$file)){
				$tempDir = mg::createTempDir('data_csv');
				$copyFile = true;
			}
			// lib/logger
			if(preg_match('/user\_log\.db/',$file)){
				$tempDir = mg::createTempDir('user_log');
				$copyFile = true;
			}
			// mg::loger()
			if(preg_match('/log\_(\d|\_)+\.txt/',$file)){
				$tempDir = mg::createTempDir('log');
				$copyFile = true;
			}
			// retail_crm
			if(preg_match('/retail\_crm\_(\d|\_)+\.txt/',$file)){
				$tempDir = mg::createTempDir('retail_crm');
				$copyFile = true;
			}
			// data_yml
			if(preg_match('/data\_yml\_(\d|\_)+\.xml/',$file)){
				$tempDir = mg::createTempDir('data_yml');
				$copyFile = true;
			}
			// sms плагин
			if(preg_match('/log\_sms\_send\.txt/',$file)){
				$tempDir = mg::createTempDir('sms');
				$copyFile = true;
			}
			// kassa-api-send
			if(preg_match('/kassa\-api\-send\.txt/',$file)){
				$tempDir = mg::createTempDir('kassa-api-send');
				$copyFile = true;
			}
			// 1с
			if(preg_match('/^log1c$/',$file)){
				$tempDir = mg::createTempDir('log1c');
				$copyDir = true;
			}
			if(preg_match('/^tempcml$/',$file)){
				$tempDir = mg::createTempDir('tempcml');
				$copyDir = true;
			}
			if($copyDir){
				$catalog = array_diff(scandir(SITE_DIR.$file), ['.','..']);
				foreach ($catalog as $cat) {
					if(!rename(SITE_DIR.$file.DS.$cat, $tempDir.$cat)){
						mg::loger('#Ошибка: Копирование '.$file.DS.$cat.' в '.$tempDir.$cat.' не выполнено!', 'add', 'system');
					}
				}
				unlink(SITE_DIR.$file);
			}
			if($copyFile){
				if(!rename($file, $tempDir.DS.$file)){
					mg::loger('#Ошибка: Копирование '.$file.' в '.$tempDir.' не выполнено!', 'add', 'system');
				}
			}
		}	
		//Перезапись order_form.js
		$orderFormFile = SITE_DIR.'mg-core'.DS.'script'.DS.'standard'.DS.'js'.DS.'order_form.js';
		unlink($orderFormFile);
		
		// Настройки даты доставки
		$res = DB::query("SHOW COLUMNS FROM `".PREFIX."delivery` LIKE 'date_settings'");
		if(!$row = DB::fetchArray($res)) {
			DB::query("ALTER TABLE `".PREFIX."delivery` ADD `date_settings` TEXT NOT NULL DEFAULT '' AFTER `date`;");
			DB::query("UPDATE `".PREFIX."delivery` SET `date_settings` = '{\"dateShift\":0,\"daysWeek\":{\"md\":true,\"tu\":true,\"we\":true,\"thu\":true,\"fri\":true,\"sa\":true,\"su\":true},\"monthWeek\":{\"jan\":\"\",\"feb\":\"\",\"mar\":\"\",\"aip\":\"\",\"may\":\"\",\"jum\":\"\",\"jul\":\"\",\"aug\":\"\",\"sep\":\"\",\"okt\":\"\",\"nov\":\"\",\"dec\":\"\"}}'");
		}	
		// Оплата платежа только после подтверждения
		$res = DB::query("SHOW COLUMNS FROM `".PREFIX."order` LIKE 'approve_payment'");
		if(!$row = DB::fetchArray($res)) {
			DB::query("ALTER TABLE `".PREFIX."order` ADD `approve_payment` INT(1) NOT NULL DEFAULT '0' AFTER `paided`;");
		}
		$propertyOrder = unserialize(stripcslashes(MG::getSetting('propertyOrder')));
		if(!isset($propertyOrder['downloadInvoice'])){
			$propertyOrder['downloadInvoice'] = 1;
		}
		if(!isset($propertyOrder['paymentAfterConfirm'])){
			$propertyOrder['paymentAfterConfirm'] = 'false';
		}
		MG::setOption(array('option' => 'propertyOrder', 'value' => addslashes(serialize($propertyOrder))));
		
		// Патч для config.ini
		
		$configFile = file_get_contents('config.ini');
		$replaceArray = [
			"\r\n; Для использования старого алгорима объединения JS в один файл (0 - исплользуется новый алгоритм, 1 - используется старый алгоритм)\r\n",
			"OLD_CACHE_JS = 1\r\n",
			"OLD_CACHE_JS = 0\r\n"
		];
		$configFile = str_replace($replaceArray, '', $configFile);
		file_put_contents('config.ini', $configFile);
		$str = "\r\n; Для использования старого алгорима объединения JS в один файл (0 - исплользуется новый алгоритм, 1 - используется старый алгоритм)\r\n OLD_CACHE_JS = ".(!defined('OLD_CACHE_JS')?"0":OLD_CACHE_JS)."\r\n";
		file_put_contents('config.ini', $str, FILE_APPEND);

		
	}

	if (DatoBaseUpdater::check("9.5.1")) {
		$res = DB::query("SELECT * FROM `".PREFIX."page` WHERE `url` = 'group?type=latest' ");
		if(!$row = DB::fetchArray($res)) {
			DB::query("INSERT INTO `".PREFIX."page` (`parent_url`, `parent`, `title`, `url`, `html_content`, `meta_title`, `meta_keywords`, `meta_desc`, `sort`, `print_in_menu`, `invisible`) VALUES
				('', 0, 'Новинки', 'group?type=latest', '', 'Новинки', 'Новинки', 'Новинки', 6, 1, 1);");
		}
		$res = DB::query("SELECT * FROM `".PREFIX."page` WHERE `url` = 'group?type=sale' ");
		if(!$row = DB::fetchArray($res)) {
			DB::query("INSERT INTO `".PREFIX."page` (`parent_url`, `parent`, `title`, `url`, `html_content`, `meta_title`, `meta_keywords`, `meta_desc`, `sort`, `print_in_menu`, `invisible`) VALUES
				('', 0, 'Акции', 'group?type=sale', '', 'Акции', 'Акции', 'Акции', 7, 1, 1);");
		}
		$res = DB::query("SELECT * FROM `".PREFIX."page` WHERE `url` = 'group?type=recommend' ");
		if(!$row = DB::fetchArray($res)) {
			DB::query("INSERT INTO `".PREFIX."page` (`parent_url`, `parent`, `title`, `url`, `html_content`, `meta_title`, `meta_keywords`, `meta_desc`, `sort`, `print_in_menu`, `invisible`) VALUES
				('', 0, 'Хиты продаж', 'group?type=recommend', '', 'Хиты продаж', 'Хиты продаж', 'Хиты продаж', 8, 1, 1);");
		}
	}

	if (DatoBaseUpdater::check("9.6.0")) {
		$res = DB::query("SELECT * FROM `".PREFIX."letters` WHERE `name` = 'email_feedback.php';");
		if($row = DB::fetchArray($res)){
			DB::query("UPDATE `".PREFIX."letters` SET `content` = CONCAT(content,' Телефон: {userPhone}') WHERE `name` = 'email_feedback.php';");
		}
		$res = DB::query('SELECT * FROM `'.PREFIX.'page` WHERE `url` REGEXP "\%";');
		while ($row = DB::fetchArray($res)) {
			$newUrl = rawurldecode($row['url']);
			DB::query("UPDATE `".PREFIX."page` SET `url` = ".DB::quote($newUrl)." WHERE `id` = ".DB::quoteInt($row['id']));
		}

		$pages = array('group?type=latest','group?type=sale','group?type=recommend');
		foreach($pages as $url){
			$res = DB::query('SELECT * FROM `'.PREFIX.'page` WHERE `url`="'.$url.'" ORDER BY `'.PREFIX.'page`.`id` ASC');
			if(DB::numRows($res) > 1){
				$row = DB::fetchArray($res);
				DB::query('DELETE FROM `'.PREFIX.'page` WHERE `id`<>"'.$row['id'].'" AND `url`="'.$row['url'].'"');
			}
		}

		//Опция на закрытие сайта для обновления 1С.
		if(MG::getSetting('downtime1C') === null) {
			MG::setOption(array('option' => 'downtime1C', 'value' => 'false', 'active' => 'N', 'name' => 'CLOSE_SITE_FOR_1C'));
		}
		$interface = unserialize(stripslashes(MG::getSetting('interface')));
		$interface["adminBar"] = "#F3F3F3";
		
		if (!isset($interface['adminBarFontColor'])) {
			$interface['adminBarFontColor'] = "#000000";
		}
		MG::setOption(array('option' => 'interface', 'value' => addslashes(serialize($interface)), 'active' => 'Y'));

		//Для сбербанка мультивалютность
		$dbRes = DB::query("SELECT `paramArray` FROM `".PREFIX."payment` WHERE `id` = 17;");
		$res = DB::fetchArray($dbRes);
		if (strpos($res['paramArray'], "Код валюты") == false) {
			$res = substr($res['paramArray'], 0, -1).',"Код валюты":""}';
			DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 17");
		}

		//Для альфабанка мультивалютность
		$dbRes = DB::query("SELECT `paramArray` FROM `".PREFIX."payment` WHERE `id` = 11;");
		$res = DB::fetchArray($dbRes);
		if (strpos($res['paramArray'], "Код валюты") == false) {
			$res = substr($res['paramArray'], 0, -1).',"Код валюты":""}';
			DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 11");
		}
		
	}

	if (DatoBaseUpdater::check("9.7.0")) {
		// Добавление периода выгрузки для заказов из Ритейла по дефолту
		$options = unserialize(stripslashes(MG::getSetting('retailcrm')));
		if(!isset($options['changeSyncDate'])){
			$options['changeSyncDate'] = 1;
		}
		if(!isset($options['orderPeriod'])){
			$options['orderPeriod'] = 4;
		}
		MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($options)), 'active' => 'N'));
		// Webp
		if(!MG::getSetting('useWebpImg')) {
			MG::setOption(array('option' => 'useWebpImg', 'value' => 'false', 'active' => 'Y', 'name' => 'USE_WEBP_IMAGES'));
		}

	}

	if (DatoBaseUpdater::check("9.8.0")) {
		//Выгрузка sitemap с локалями
		if(!MG::getSetting('productSitemapLocale')) {
			MG::setOption(array('option' => 'productSitemapLocale', 'value' => 'true', 'active' => 'Y', 'name' => 'PRODUCT_SITEMAP_LOCALE'));
		}
		// Настройка для списывания только с определенного, выбранного пользователем склада
		if(!MG::getSetting('useOneStorage')) {
			MG::setOption(array('option' => 'useOneStorage', 'value' => 'false', 'active' => 'N', 'name' => 'USE_ONE_STORAGE'));
		}
		//Для смены статусов в Яндекс Кассе
		if(!defined('CHANGE_STATUS_UKASSA')) {
			$str = "\r\n";
			$str .= "; Измениять статус заказа в ЯндексКассе при отмене платежа(0 - не менять, 1 - менять)\r\n";
			$str .= "CHANGE_STATUS_UKASSA = 0\r\n";
			file_put_contents('config.ini', $str, FILE_APPEND);
		}
		//Вывод доставки в товарном чеке
		$propertyOrder = unserialize(stripcslashes(MG::getSetting('propertyOrder')));
		if(!isset($propertyOrder['showDeliveryCost'])){
			$propertyOrder['showDeliveryCost'] = 'false';
		}
		MG::setOption(array('option' => 'showDeliveryCost', 'value' => addslashes(serialize($propertyOrder))));

		//Для payKeeper НДС
		$dbRes = DB::query("SELECT `paramArray` FROM `".PREFIX."payment` WHERE `id` = 21;");
		$res = DB::fetchArray($dbRes);
		if (strpos($res['paramArray'], "Система налогообложения") == false) {
			$res = substr($res['paramArray'], 0, -1).',"Система налогообложения":"bm9uZWhZKjZuazchISMycWo="}';
			DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 21");
		}

		//Для отправки темы сайта на mogutasite
		if(!MG::getSetting('sitetheme')) {
			MG::setOption(array('option' => 'sitetheme', 'value' => '', 'active' => 'Y', 'name' => 'SITE_TEME'));
		}
		//Темы сайта, которые может выбрать пользователь
		if(!MG::getSetting('siteThemeVariants')) {
			$variants = array(
				"Одежда и обувь", "Электроника и бытовая техника", "Косметика и парфюмерия", "Специализированные магазины", "Товары для ремонта и строительства",
				"Автозапчасти и транспорт", "Красота и здоровье", "Товары для детей", "Товары для дома", "Товары для сада и огорода", "Товары для спорта", "Доставка цветов",
				"Товары для хобби и творчества", "Смартфоны, планшеты, аксессуары", "Продукты питания и напитки", "Универсальные магазины", "Товары для офиса", 
				"Товары для животных", "Чай и кофе", "Табак и кальяны", "Рыбалка и охота", "Свет и светильники", "Сантехника и вода", "Радиотехника и запчасти", 
				"Товары для бизнеса", "Мебель", "Еда, пицца и роллы", "Производство и промышленность", "Товары для взрослых", "Видеонаблюдение и безопасность",
				"Сумки", "Услуги", "Софт и программы", "Ювелирные магазины и часы", "Двери", "Книги", "Подарки и сувениры", "Упаковки и коробки", "Средства для борьбы с вредителями",
				"Постельное белье", "Другое"
			);
			$variants = addslashes(serialize($variants));
			MG::setOption(array('option' => 'siteThemeVariants', 'value' => $variants, 'active' => 'N', 'name' => 'SITE_TEME_VARIANTS'));
		}

		//Время когда совершена последняя проверка движка
		if(!MG::getSetting('checkEngine')) {
			MG::setOption(array('option' => 'checkEngine', 'value' => time(), 'active' => 'N', 'name' => 'CHECK_ENGINE'));
		}

		// Флаг, что в данный момент используются стандартные настройки
		if(!MG::getSetting('useDefaultSettings')) {
			MG::setOption(array('option' => 'useDefaultSettings', 'value' => 'false', 'active' => 'N', 'name' => 'USE_DEFAULT_SETT'));
		}

		// Названия резервной копии настрое таблицы настроек (при включении дефолтной)
		if(!MG::getSetting('backupSettingsFile')) {
			MG::setOption(array('option' => 'backupSettingsFile', 'value' => '', 'active' => 'N', 'name' => 'BACKUP_SETTINGS_FILE'));
		}
		
		//Локали для настроек 
		$option = MG::getSetting('timeWorkDays');
		MG::setOption(array('option' => 'timeWorkDays', 'value' => $option, 'active' => 'Y', 'name' => 'TIME_WORK_DAYS'));
		$option = MG::getSetting('product404Sitemap');
		MG::setOption(array('option' => 'product404Sitemap', 'value' => $option, 'active' => 'Y', 'name' => 'PRODUCT404SITEMAP'));
		$option = MG::getSetting('product404');
		MG::setOption(array('option' => 'product404', 'value' => $option, 'active' => 'Y', 'name' => 'PRODUCT404'));
		$option = MG::getSetting('countPrintRowsBrand');
		MG::setOption(array('option' => 'countPrintRowsBrand', 'value' => $option, 'active' => 'Y', 'name' => 'COUNT_PRINT_ROWS_BRAND'));
		$option = MG::getSetting('currencyActive');
		MG::setOption(array('option' => 'currencyActive', 'value' => $option, 'active' => 'Y', 'name' => 'CURRENCY_ACTIVE'));
		$option = MG::getSetting('colorSchemeLanding');
		MG::setOption(array('option' => 'colorSchemeLanding', 'value' => $option, 'active' => 'Y', 'name' => 'COLOR_SCHEME_LANDING'));
		$option = MG::getSetting('landingName');
		MG::setOption(array('option' => 'landingName', 'value' => $option, 'active' => 'Y', 'name' => 'LANDING_NAME'));
		$option = MG::getSetting('categoryIconWidth');
		MG::setOption(array('option' => 'categoryIconWidth', 'value' => $option, 'active' => 'Y', 'name' => 'CAT_ICON_WIDTH'));
		$option = MG::getSetting('categoryIconHeight');
		MG::setOption(array('option' => 'categoryIconHeight', 'value' => $option, 'active' => 'Y', 'name' => 'CAT_ICON_HEIGHT'));
		$option = MG::getSetting('interface');
		MG::setOption(array('option' => 'interface', 'value' => $option, 'active' => 'Y', 'name' => 'INTERFACE_SETTING'));
		$option = MG::getSetting('catalog_meta_title');
		MG::setOption(array('option' => 'catalog_meta_title', 'value' => $option, 'active' => 'Y', 'name' => 'CATALOG_META_TITLE'));
		$option = MG::getSetting('catalog_meta_description');
		MG::setOption(array('option' => 'catalog_meta_description', 'value' => $option, 'active' => 'Y', 'name' => 'CATALOG_META_DESC'));
		$option = MG::getSetting('catalog_meta_keywords');
		MG::setOption(array('option' => 'catalog_meta_keywords', 'value' => $option, 'active' => 'Y', 'name' => 'CATALOG_META_KEYW'));
		$option = MG::getSetting('product_meta_title');
		MG::setOption(array('option' => 'product_meta_title', 'value' => $option, 'active' => 'Y', 'name' => 'PRODUCT_META_TITLE'));
		$option = MG::getSetting('product_meta_description');
		MG::setOption(array('option' => 'product_meta_description', 'value' => $option, 'active' => 'Y', 'name' => 'PRODUCT_META_DESC'));
		$option = MG::getSetting('product_meta_keywords');
		MG::setOption(array('option' => 'product_meta_keywords', 'value' => $option, 'active' => 'Y', 'name' => 'PRODUCT_META_KEYW'));
		$option = MG::getSetting('page_meta_title');
		MG::setOption(array('option' => 'page_meta_title', 'value' => $option, 'active' => 'Y', 'name' => 'PAGE_META_TITLE'));
		$option = MG::getSetting('page_meta_description');
		MG::setOption(array('option' => 'page_meta_description', 'value' => $option, 'active' => 'Y', 'name' => 'PAGE_META_DESC'));
		$option = MG::getSetting('page_meta_keywords');
		MG::setOption(array('option' => 'page_meta_keywords', 'value' => $option, 'active' => 'Y', 'name' => 'PAGE_META_KEYW'));
		$option = MG::getSetting('propertyOrder');
		MG::setOption(array('option' => 'propertyOrder', 'value' => $option, 'active' => 'Y', 'name' => 'PROPERTY_OPRDER'));
		$option = MG::getSetting('useOneStorage');
		MG::setOption(array('option' => 'useOneStorage', 'value' => $option, 'active' => 'N', 'name' => 'USE_ONE_STORAGE'));
		//Интеграция intellectmoney
		$dbRes = DB::query("SELECT * FROM `".PREFIX."payment` WHERE `id` = 29;");
		$res = DB::fetchArray($dbRes);
		if(empty($res)){
			DB::query("INSERT INTO `".PREFIX."payment` (`id`, `name`, `activity`, `paramArray`, `urlArray`, `sort`, `permission`) VALUES
			(29, 'intellectmoney', '0', '{\"ID магазина\":\"\",\"Секретный ключ\":\"\",\"ИНН\":\"\",\"Использовать онлайн кассу\":\"ZmFsc2VoWSo2bms3ISEjMnFq\",\"Тестовый режим\":\"\",\"НДС на товары\":\"MmhZKjZuazchISMycWo=\",\"НДС на доставку\":\"NmhZKjZuazchISMycWo=\"}', '{\"result URL:\":\"/payment?id=29&pay=result\"}', 29, 'fiz')");
		}
	}

	if (DatoBaseUpdater::check("9.8.2")) {
		DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."logs_ajax` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`ajax` text NOT NULL COMMENT 'ajax запросы с заменой параметров',
			`action` varchar(256) NOT NULL,
			`actioner` varchar(256) NOT NULL,
			`handler` varchar(256) NOT NULL,
			`mguniqueurl` varchar(256) NOT NULL,
			`params` text NOT NULL COMMENT 'Часть запроса с данными, без данных о роутинге',
			`example` text NOT NULL COMMENT 'Исходный запрос со всеми данными',
			`controller` varchar(32) NOT NULL COMMENT 'ajax или ajaxRequest',
			`requestType` varchar(32) NOT NULL,
			PRIMARY KEY (`id`)
		  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		if (!MG::getSetting('ordersPerPageForUser')) {
			MG::setOption(array('option' => 'ordersPerPageForUser', 'value' => '10', 'active' => 'Y', 'name' => 'ORDER_USER_COUNT'));
		}

		//Для альфабанка НДС
		$dbRes = DB::query("SELECT `paramArray` FROM `".PREFIX."payment` WHERE `id` = 11;");
		$res = DB::fetchArray($dbRes);
		if (strpos($res['paramArray'], "НДС на товары") == false && strpos($res['paramArray'], "Использовать онлайн кассу") == false) {
			$res = substr($res['paramArray'], 0, -1).',"НДС на товары":"MGhZKjZuazchISMycWo=","Использовать онлайн кассу":"ZmFsc2VoWSo2bms3ISEjMnFq"}';
		}
		DB::query("UPDATE `".PREFIX."payment` SET `paramArray` = ".DB::quote($res)." WHERE id = 11");

	}

	if (DatoBaseUpdater::check("9.9.0")) {
		if (NEW_SMTP_TLS == 'NEW_SMTP_TLS') {
		$str = "\r\n";
		$str .= "; Включает новый способ отправки писем через smtp\r\n";
		$str .= "NEW_SMTP_TLS = 1\r\n";
		file_put_contents('config.ini', $str, FILE_APPEND);
		}
	}

	if (DatoBaseUpdater::check("9.9.2")) {
		DB::query("INSERT IGNORE INTO  `".PREFIX."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL, 'metaConfirmation', '<!-- В это поле необходимо прописать код подтверждения Вашего сайта. Например, Яндекс.Метрика или Google analytics -->', 'Y', 'META_TAGS_CONFIRMATION')");
		DB::query("INSERT IGNORE INTO `".PREFIX."letters` (id, name, content) VALUES (12, 'layout_agreement.php', '<h2>Соглашение на обработку персональных данных.</h2><hr/><br/>Настоящим, я (далее – Лицо), даю свое согласие ООО «Интернет-магазин», юридический адрес: 115230, город Санкт-Петербург, Невский проспект, дом 30 (далее – Компания) на обработку своих персональных данных, указанных при оформлении заказа на сайте Компании для обработки моего заказа, коммуникации со мной в рамках обработки моего заказа, доставки заказанного мной товара, а также иных сопряженных с этим целей в рамках действующего законодательства РФ и технических возможностей Компании, а также для получения сервисного опроса по завершении оказания услуги или при невозможности оказания таковой.<br/><br/>Обработка персональных данных Лица может осуществляться с помощью средств автоматизации и/или без использования средств автоматизации в соответствии с действующим законодательством РФ и положениями Компании. Настоящим Лицо соглашается на передачу своих персональных данных третьим лицам для их обработки в соответствии с целями, предусмотренными настоящим согласием, на основании договоров, заключенных Компанией с этими лицами, персональные данные Лица могут передаваться внутри группы лиц ООО «Интернет-магазин», включая трансграничную передачу. Настоящее согласие Лица на обработку его/ее персональных данных, указанных при оформлении заказа на сайте Компании, направляемых (заполненных) с использованием настоящего сайта, действует с момента оформления заказа на сайте Компании до момента его отзыва. Согласие на обработку персональных данных, указанных при оформлении заказа на сайте Компании, направляемых (заполненных) с использованием настоящего сайта, может быть отозвано Лицом при подаче письменного заявления (отзыва) в Компанию. Обработка персональных данных Лица прекращается в течение 2 месяцев с момента получения Компанией письменного заявления (отзыва) Лица и/или в случае достижения цели обработки и уничтожается в срок и на условиях, установленных законом, если не предусмотрено иное. Обезличенные персональные данные Лица могут использоваться Компанией в статистических (и иных исследовательских целей) после получения заявления (отзыва) согласия, а также после достижения целей, для которых настоящее согласие было получено.<br/><br/>Настоящим Лицо подтверждает достоверность указанной информации.')");
	}	
	
	if (DatoBaseUpdater::check("10.0.1")) {

		$dbRes = DB::query("SELECT * FROM `".PREFIX."payment` WHERE `id` = 30;");
		$res = DB::fetchArray($dbRes);
		if(empty($res)){
			DB::query("INSERT INTO `".PREFIX."payment` (`id`, `name`, `activity`, `paramArray`, `urlArray`,`rate`, `sort`, `permission`) VALUES
			(30, 'beGateway', 1, '{\"Shop ID\":\"\",\"Shop Secret Key\":\"\",\"Shop Public Key\":\"\",\"Payment Domain\":\"\",\"Тестовый режим\":\"dHJ1ZWhZKjZuazchISMycWo=\"}', '{\"result URL:\":\"/payment?id=30&pay=result\",\"success URL:\":\"/payment?id=30&pay=success\",\"fail URL:\":\"/payment?id=30&pay=fail\"}', 0, 30, 'fiz')");
		}
	}	


	if (DatoBaseUpdater::check("10.1.0")) {
		// Опция выгрузки заказов в 1с частями
		if(MG::getSetting('ordersPerTransfer1c') === null) {
				MG::setOption(array('option' => 'ordersPerTransfer1c', 'value' => '1000', 'active' => 'Y', 'name' => 'ORDERS_PER_TRANSFER_1C'));
		}

		$dbRes = DB::query("SELECT * FROM `".PREFIX."payment` WHERE `id` = 31;");
		$res = DB::fetchArray($dbRes);
		if(empty($res)){
			DB::query("INSERT INTO `".PREFIX."payment` (`id`, `name`, `activity`, `paramArray`, `urlArray`,`rate`, `sort`, `permission`) VALUES
			(31, 'Оплата по QR', 1, '', '', 0, 31, 'fiz')");
		}

		// Новая ссылка на api Tinkoff для способа оплаты
		$getTinkoffPaymentSql = 'SELECT `paramArray` FROM `'.PREFIX.'payment` WHERE `id` = 18;';
		$query = DB::query($getTinkoffPaymentSql);
		$result = DB::fetchAssoc($query);
		$params = json_decode($result['paramArray'], true);
		$serverAddress = 'https://securepay.tinkoff.ru/v2/';
		$params['Адрес сервера'] = CRYPT::mgCrypt($serverAddress);
		$updateTinkoffPaymentSql = 'UPDATE `'.PREFIX.'payment` SET `paramArray` = '.DB::quote(CRYPT::json_encode_cyr($params)).' WHERE `id` = 18;';
		DB::query($updateTinkoffPaymentSql);
	}




	if (DatoBaseUpdater::check("10.1.1")) {

		$from= SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'components'.DS.'payment'.DS.'payment_qrcode.php';
		$to = SITE_DIR.'mg-templates'.DS.'moguta'.DS.'components'.DS.'payment'.DS.'payment_qrcode.php';
		if (is_file($from)) {
			copy($from, $to);	
		}

	}

	if (DatoBaseUpdater::check('10.2.0')) {

		// Обновление ссылок tinkoff
		// (Должны быть 2 ссылки - success и fail. Ссылка для HTTP-нотификаций подставляется автоматически)
		$links = [
			'success URL:' => '/payment?id=18&pay=success',
			'fail URL:' => '/payment?id=18&pay=fail'
		];
		$linksJson = json_encode($links);
		$updateTinkoffUrlsSql = 'UPDATE `'.PREFIX.'payment` SET `urlArray` = '.DB::quote($linksJson).' WHERE `id` = 18;';
		DB::query($updateTinkoffUrlsSql);

	}

	if (DatoBaseUpdater::check('10.3.0')) {
		if(MG::getSetting('recalcForeignCurrencyOldPrice') === null) {
			MG::setOption(array('option' => 'recalcForeignCurrencyOldPrice', 'value' => 'true', 'active' => 'Y', 'name' => 'RECALCT_FOREIGN_CURRENCY_OLD_PRICE'));
		}

		if(MG::getSetting('useTemplatePlugins') === null) {
			MG::setOption([
				'option' => 'useTemplatePlugins',
				'value' => '1',
				'active' => 'Y',
				'name' => 'USE_TEMPLATE_PLUGINS'
			]);
		}

	}

	if (DatoBaseUpdater::check('10.3.3')) {
		// Повторяем создание нового способа оплаты по QR, потому что по непонятным причинам он не появился в некоторых магазинах
		$dbRes = DB::query("SELECT * FROM `".PREFIX."payment` WHERE `id` = 31;");
		$res = DB::fetchArray($dbRes);
		if(empty($res)){
			DB::query("INSERT INTO `".PREFIX."payment` (`id`, `name`, `activity`, `paramArray`, `urlArray`,`rate`, `sort`, `permission`) VALUES
			(31, 'Оплата по QR', 1, '', '', 0, 31, 'fiz')");
		}
	}


	if (DatoBaseUpdater::check('10.4.0')) {
		// Опция отключения миниатюр
		if(MG::getSetting('thumbsProduct') === null) {
			if(EDITION=="saas"){
		  	MG::setOption(array('option' => 'thumbsProduct', 'value' => 'false', 'active' => 'Y', 'name' => 'THUMBS_PRODUCT'));
		  }else{
				MG::setOption(array('option' => 'thumbsProduct', 'value' => 'true', 'active' => 'Y', 'name' => 'THUMBS_PRODUCT'));
			}
	  }
	}

	if (DatoBaseUpdater::check('10.4.3')) {
		// Копируем файл order_form.js из движка в uploads,
		// в новую папку generatedJs (предварительно создаём её)
		// если его там ещё нет.
		// (Нужно для корректной работы на saas-сайтах)
		$orderFormJsNewDir = SITE_DIR.'uploads'.DS.'generatedJs';
		if (!is_dir($orderFormJsNewDir)) {
			mkdir($orderFormJsNewDir);
		}

		$orderFormJsOldFile = SITE_DIR.'mg-core'.DS.'script'.DS.'standard'.DS.'js'.DS.'order_form.js';
		$orderFormJsNewFile = $orderFormJsNewDir.DS.'order_form.js';
		if (!is_file($orderFormJsNewFile) && is_file($orderFormJsOldFile)) {

			$orderFormJsContent = file_get_contents($orderFormJsOldFile);
			if ($orderFormJsContent) {
				file_put_contents($orderFormJsNewFile, $orderFormJsContent);
			}
			unlink($orderFormJsOldFile);
		}

		// Добавляем параметр налогообложения для оплат через ЮКассу
		// Для соответствия ФФД 1.2 (тег 1055 receipt.company.sno)
		
		// yandexKassaNew (ЮКасса (API))
		$yknParamsSql = 'SELECT `paramArray` FROM `'.PREFIX.'payment` '.
			'WHERE `id` = 24;';
		$yknParamsResult = DB::query($yknParamsSql);
		if ($yknParamsRow = DB::fetchAssoc($yknParamsResult)) {
			$yknParams = $yknParamsRow['paramArray'];
			if (
				strpos($yknParams, 'Система налогообложения') === false
			) {
				$yknParams = substr($yknParams, 0, -1).',"Система налогообложения":"MWhZKjZuazchISMycWo="}';
				$updateYknParamsSql = 'UPDATE `'.PREFIX.'payment` '.
					'SET `paramArray` = '.DB::quote($yknParams).' '.
					'WHERE `id` = 24;';
				DB::query($updateYknParamsSql);
			}
		}

		// yandexKassa (ЮКасса (HTTP))
        $ykParamsSql = 'SELECT `paramArray` FROM `'.PREFIX.'payment` '.
			'WHERE `id` = 14;';
        $ykParamsResult = DB::query($ykParamsSql);
        if ($ykParamsRow = DB::fetchAssoc($ykParamsResult)) {
            $ykParams = $ykParamsRow['paramArray'];
            if (
                strpos($ykParams, 'Система налогообложения') === false
            ) {
                $ykParams = substr($ykParams, 0, -1).',"Система налогообложения":"MWhZKjZuazchISMycWo="}';
                $updateYkParamsSql = 'UPDATE `'.PREFIX.'payment` '.
                    'SET `paramArray` = '.DB::quote($ykParams).' '.
                    'WHERE `id` = 14;';
                DB::query($updateYkParamsSql);
            }
        }

		//Добавляем настройку выбора подтверждения регистрации по телефону через звонок или через смс
		DB::query("INSERT IGNORE INTO  `".PREFIX."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL,'confirmRegistrationPhoneType', 'sms', 'Y', 'CONFIRM_REGISTRATION_PHONE_TYPE')");		
	}

	if (DatoBaseUpdater::check('10.5.2')) {
	}

	if (DatoBaseUpdater::check('10.6.0')) {
		$sqlForAvitoCats = 'SELECT * FROM `' . PREFIX . 'avito_cats`';
		$avitoCatsRes = DB::query($sqlForAvitoCats);
		if($avitoCatsRow = DB::fetchAssoc($avitoCatsRes)){
			$updatePluginsTableSql = 'UPDATE `' . PREFIX . 'avito_cats` SET `name` = "Шкафы, комоды и стеллажи" WHERE `name` LIKE "%Шкафы и комоды%"';
		}

		// Добавляем unique индекс на login_phone таблицы prefix_user
		$checkIfLoginPhoneColumnExistsSql = 'SHOW COLUMNS FROM `'.PREFIX.'user` LIKE \'login_phone\';';
		$checkIfLoginPhoneColumnExistsResult = DB::query($checkIfLoginPhoneColumnExistsSql);
		if (DB::fetchAssoc($checkIfLoginPhoneColumnExistsResult)) {
			$loginPhoneIndexesSql = 'SHOW INDEX FROM `'.PREFIX.'user` '.
				'WHERE `Column_name` = \'login_phone\';';
			$loginPhoneIndexesResult = DB::query($loginPhoneIndexesSql);
			while ($loginPhoneIndexesRow = DB::fetchArray($loginPhoneIndexesResult)) {
				$dropLoginPhoneIndexSql = 'DROP INDEX '.
					'`'.$loginPhoneIndexesRow['Key_name'].'` '.
					'ON `'.PREFIX.'user`;';
				DB::query($dropLoginPhoneIndexSql);
			}
			$addLoginPhoneIndexSql = 'CREATE INDEX `login_phone` '.
				'ON `'.PREFIX.'user`(`login_phone`);';
			DB::query($addLoginPhoneIndexSql);
		}
		
		// Обновление ссылки для cberbank
		$links = [
			'callback URL:' => '/payment?id=17&pay=result',
		];
		$linksJson = json_encode($links);
		$updateTinkoffUrlsSql = 'UPDATE `'.PREFIX.'payment` SET `urlArray` = '.DB::quote($linksJson).' WHERE `id` = 17';
		DB::query($updateTinkoffUrlsSql);

	}

	if (DatoBaseUpdater::check('10.7.0')) {
		//Добавляем настройку вывода закончившихся товаров в конец
		DB::query("INSERT IGNORE INTO  `".PREFIX."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL,'productsOutOfStockToEnd', 'false', 'Y', 'PRODUCTS_OUT_OF_STOCK_TO_THE_END')");

		// Удаление Яндекс.Деньги из оплат
		$deleteYandexMoneySql = 'DELETE FROM `'.PREFIX.'payment` '.
			'WHERE `id` = 2';
		DB::query($deleteYandexMoneySql);

		// Новый параметр валюты в оплате FREEKASSA
		$freeKassaId = 	26;
		$freeKassaParamsSql = 'SELECT `paramArray` '.
			'FROM `'.PREFIX.'payment` '.
			'WHERE `id` = 26';
		$freeKassaParamsResult = DB::query($freeKassaParamsSql);
		if ($freeKassaParamsRow = DB::fetchAssoc($freeKassaParamsResult)) {
			$freeKassaRawParams = $freeKassaParamsRow['paramArray'];
			$freeKassaParams = json_decode($freeKassaRawParams, true);
			if ($freeKassaParams && !isset($freeKassaParams['currency'])) {
				$freeKassaParams['Валюта'] = CRYPT::mgCrypt('RUB');
				$freeKassaNewParamsJson = json_encode($freeKassaParams, JSON_UNESCAPED_UNICODE);
				$updateFreeKassaParamsSql = 'UPDATE `'.PREFIX.'payment` '.
					'SET `paramArray` = '.DB::quote($freeKassaNewParamsJson).' '.
					'WHERE `id` = 26';
				DB::query($updateFreeKassaParamsSql);
			}
		}
	}

	if (DatoBaseUpdater::check('10.8.0')) {
		// Добавляем опцию обновления категории
		// в обновляемые поля товаров в настройках 1С
		$notUpdate1CSetting = MG::getSetting('notUpdate1C', true);
		if (!isset($notUpdate1CSetting['1c_cat_id'])) {
			$notUpdate1CSetting['1c_cat_id'] = 'true';
			MG::setOption('notUpdate1C', $notUpdate1CSetting, true);
		}

		// Добавляем опции обнровления полей категории
		// в настрйоках 1C
		if (!MG::getSetting('notUpdateCat1C')) {
			$notUpdateCat1C = [
				'cat1c_title' => 'true',
				'cat1c_url' => 'true',
				'cat1c_parent' => 'true',
				'cat1c_html_content' => 'false',
			];
			$notUpdateCat1CSerialized = addslashes(serialize($notUpdateCat1C));
			MG::setOption(array('option' => 'notUpdateCat1C', 'value' => $notUpdateCat1CSerialized, 'active' => 'Y', 'name' => 'UPDATE_CAT_1C'));
		}

		// Убираем лишний пробел в опции активации ключа
		if (!MG::getSetting('dateActivateKey')) {
			DB::query("UPDATE `".PREFIX."setting` SET `option` = 'dateActivateKey' WHERE `option` = 'dateActivateKey '");
		}

		// Добавляем в оплату PayAnyWay
		// Настройку НДС
		$payAnyWayParamsSql = 'SELECT `paramArray` '.
			'FROM `'.PREFIX.'payment` '.
			'WHERE `id` = 9';
		$payAnyWayParamsResult = DB::query($payAnyWayParamsSql);
		if ($payAnyWayParamsRow = DB::fetchAssoc($payAnyWayParamsResult)) {
			$rawPayAnyWayParams = $payAnyWayParamsRow['paramArray'];
			$payAnyWayParams = json_decode($rawPayAnyWayParams, true);
			$newKey = 'НДС на товары';
			if (
				!array_key_exists($newKey, $payAnyWayParams)
			) {
				$payAnyWayParams[$newKey] = 'MTEwNGhZKjZuazchISMycWo=';
				$newParams = json_encode($payAnyWayParams, JSON_UNESCAPED_UNICODE);
				$updatePayAnyWayParamsSql = 'UPDATE `'.PREFIX.'payment` '.
					'SET `paramArray` = '.DB::quote($newParams).' '.
					'WHERE `id` = 9';
				DB::query($updatePayAnyWayParamsSql);
			}
		}

		// Добавляем опции отображений некоторой информации на странице товара
		$checkNewProductOptions = 'SELECT `id` '.
			'FROM `'.PREFIX.'setting` '.
			'WHERE `option` = \'printCount\'';
		$checkNewProductOptionsResult = DB::query($checkNewProductOptions);
		if (!DB::fetchAssoc($checkNewProductOptionsResult)) {
			$newProductOptionsSql = 'INSERT INTO `'.PREFIX.'setting` '.
				'(`id`, `option`, `value`, `active`, `name`) VALUES '.
				'(NULL, \'printCount\', \'true\', \'Y\', \'PRINT_COUNT\'), '.
				'(NULL, \'printCode\', \'true\', \'Y\', \'PRINT_CODE\'), '.
				'(NULL, \'printUnits\', \'true\', \'Y\', \'PRINT_UNITS\'), '.
				'(NULL, \'printCost\', \'true\', \'Y\', \'PRINT_COST\'), '.
				'(NULL, \'printBuy\', \'true\', \'Y\', \'PRINT_BUY\')';

			DB::query($newProductOptionsSql);
		}
		// UTM метки к заказу
		foreach(['utm_source','utm_medium','utm_campaign','utm_term','utm_content'] as $utm){
			$dbRes = DB::query("SHOW COLUMNS FROM `".PREFIX."order` WHERE FIELD = ".DB::quote($utm));
			if(!$row = DB::fetchArray($dbRes)) {
				DB::query("ALTER TABLE `".PREFIX."order` ADD `".DB::quote($utm,1)."` text NOT NULL");
			}
		}

		// Таблицы для импорта изображений YML
    DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."import_yml_core_cats` (
      `id` int, 
      `parentId` int NULL, 
      `title` varchar(255) 
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."import_yml_core_images` (
      `id` int, 
      `images` text
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		// удаляем полнотекстовые индексы и переключаем тип поиска на обычный, если был установлен полнотекстовый
		$dbRes = DB::query("SELECT `value` FROM `".PREFIX."setting` WHERE `option` = 'searchType' AND `value` = 'fulltext' ");
		if($row = DB::fetchArray($dbRes)) {
			DB::query("UPDATE `".PREFIX."setting` SET value = 'like' WHERE `option` = 'searchType'");
		}
		$productIndexesSql = 'SHOW INDEX FROM `'.PREFIX.'product`';
		$productIndexesResult = DB::query($productIndexesSql);
		while($productIndexesRow = DB::fetchAssoc($productIndexesResult)) {
			if ($productIndexesRow['Key_name'] === 'SEARCHPROD') {
				DB::query("ALTER TABLE `".PREFIX."product` DROP INDEX `SEARCHPROD`");
				break;
			}
		}

		//обновляем запись про tinkoff
		$res = DB::query('SELECT `paramArray` FROM `'.PREFIX.'payment` WHERE id = 18');
		if ($row = DB::fetchArray($res)) {
			$decodedParamArray = json_decode($row['paramArray'], true);
			$tinkoffRequiredParams = [
				'Использовать онлайн кассу' => 'ZmFsc2VoWSo2bms3ISEjMnFq',
				'НДС на товары' => 'dmF0MjBoWSo2bms3ISEjMnFq',
				'НДС на доставку' => 'dmF0MjBoWSo2bms3ISEjMnFq',
				'Система налогообложения' => 'b3NuaFkqNm5rNyEhIzJxag==',
				'Email продавца' => '',
			];
			$unexistedRequiredParams = array_diff(array_keys($tinkoffRequiredParams), array_keys($decodedParamArray));
			if ($unexistedRequiredParams) {
				$tinkoffResultParams = [];
				$tinkoffParamsOrder = [
					'Ключ терминала',
					'Секретный ключ',
					'Адрес сервера',
					'Использовать онлайн кассу',
					'Система налогообложения',
					'НДС на товары',
					'НДС на доставку',
					'Email продавца',
				];
				foreach ($tinkoffParamsOrder as $tinkoffParam) {
					if (in_array($tinkoffParam, $unexistedRequiredParams)) {
						$tinkoffResultParams[$tinkoffParam] = $tinkoffRequiredParams[$tinkoffParam];
					} else {
						$tinkoffResultParams[$tinkoffParam] = $decodedParamArray[$tinkoffParam];
					}
				}
				$tinkoffNewParams = json_encode($tinkoffResultParams, JSON_UNESCAPED_UNICODE);
				$updateTinkoffParamsSql = 'UPDATE `'.PREFIX.'payment` '.
					'SET `paramArray` = '.DB::quote($tinkoffNewParams).' '.
					'WHERE `id` = 18';
				$test = DB::query($updateTinkoffParamsSql);
			}
		}

		// Опция отключения обновления статусов заказов в CMS, которые приходят из 1C
		if(MG::getSetting('leaveOrderStatuses') === null) {
			MG::setOption(array('option' => 'leaveOrderStatuses', 'value' => 'false', 'active' => 'Y', 'name' => 'LEAVE_ORDER_STATUSES_1C'));
		}
	}

	if (DatoBaseUpdater::check('10.8.2')) {
		// Некоторых настроек может не хватать если обновлять сайт вручную файлами или переходить между редакциями
		// Если произошло такое, то вносим в базу значения по умолчанию
		if (MG::getSetting('confirmRegistrationEmail') === null) {
			$defaultData = [
				'option' => 'confirmRegistrationEmail',
				'value' => 'true',
				'active' => 'Y',
				'name' => 'CONFIRM_REGISTRATION_EMAIL'
			];
			MG::setOption($defaultData);
		}
		if (MG::getSetting('confirmRegistrationPhone') === null) {
			$defaultData = [
				'option' => 'confirmRegistrationPhone',
				'value' => 'false',
				'active' => 'Y',
				'name' => 'CONFIRM_REGISTRATION_PHONE'
			];
			MG::setOption($defaultData);
		}
	}
	

	if (DatoBaseUpdater::check('10.8.5')) {
		// Если нет настроек автогенерации или контента lang атрибута html, то создаём их
		if (MG::getSetting('genMetaLang') === null) {
			$defaultData = [
				'option' => 'genMetaLang',
				'value' => 'true',
				'active' => 'Y',
				'name' => 'GEN_META_LANG',
			];
			MG::setOption($defaultData);
		}
		if (MG::getSetting('metaLangContent') === null) {
			$defaultData = [
				'option' => 'metaLangContent',
				'value' => 'zxx',
				'active' => 'Y',
				'name' => 'META_LANG_CONTENT',
			];
			MG::setOption($defaultData);
		}

	}

	if (DatoBaseUpdater::check('10.8.6')) {
		if(!defined('UTM_UPDATE_LAST_VISIT')) {
			$str = "\r\n";
			$str .= "; Перезаписывать cookies c UTM метками по последнему визиту\r\n";
			$str .= "UTM_UPDATE_LAST_VISIT = 0\r\n";
			file_put_contents('config.ini', $str, FILE_APPEND);
		}
		if(!defined('OLD_1C_EXCHANGE')) {
			$str = "\r\n";
			$str .= "; Для использования старого алгоритма обмена с 1С (0 - используется новый алгоритм, 1 - используется старый алгоритм)\r\n";
			$str .= "OLD_1C_EXCHANGE = 1\r\n";
			file_put_contents('config.ini', $str, FILE_APPEND);
		}
	}

	if (DatoBaseUpdater::check('10.8.7')) {
		// Опция переворота изображений по meta-информации через exif
		if(MG::getSetting('exifRotate') === null) {
			MG::setOption(array('option' => 'exifRotate', 'value' => 'false', 'active' => 'Y', 'name' => 'EXIF_ROTATE'));
		}
	}

	if (DatoBaseUpdater::check('10.9.0')) {
		// Изменяем движок таблицы оплат на InnoDB
		$setPaymentTableInnoSql = 'ALTER TABLE `'.PREFIX.'payment` ENGINE = InnoDB';
		DB::query($setPaymentTableInnoSql);

		// Добавляем новые поля в таблицу оплат
		$checkPaymentsTableNewColumnsSql = 'SHOW COLUMNS FROM `'.PREFIX.'payment` LIKE \'plugin\'';
		$checkPaymentsTableNewColumnsResult = DB::query($checkPaymentsTableNewColumnsSql);
		if (!DB::fetchAssoc($checkPaymentsTableNewColumnsResult)) {
			$addNewPaymentsColumnsSql = 'ALTER TABLE `'.PREFIX.'payment` '.
				'ADD COLUMN `code` varchar(191) NOT NULL DEFAULT \'\' AFTER `id`, '.
				'ADD COLUMN `public_name` varchar(1024) DEFAULT NULL AFTER `name`, '.
				'ADD COLUMN `plugin` varchar(255) DEFAULT NULL AFTER `permission`, '.
				'ADD COLUMN `icon` varchar(511) DEFAULT NULL AFTER `plugin`, '.
				'ADD COLUMN `logs` tinyint(1) UNSIGNED NOT NULL DEFAULT \'0\' COMMENT \'Флаг, обозначающий поддерживает оплата логирование или нет\' AFTER `icon`';
			DB::query($addNewPaymentsColumnsSql);
		}

		// Проставляем старым оплатам code с префиксом old#
		$setOldPaymentsCodeSql = 'UPDATE `'.PREFIX.'payment` '.
			'SET `code` = CONCAT(\'old#\',`id`) '.
			'WHERE `code` = \'\'';
		DB::query($setOldPaymentsCodeSql);

		// Устанавливаем уникальный индекс для поля code в таблице оплат если его ещё нет
		$checkPaymentCodeUniqueSql = 'SHOW INDEXES '.
			'FROM `'.PREFIX.'payment` '.
			'WHERE `Column_name` = \'code\' '.
			'AND NOT Non_unique';
		$checkPaymentCodeUniqueResult = DB::query($checkPaymentCodeUniqueSql);
		if (!DB::fetchAssoc($checkPaymentCodeUniqueResult)) {
			$setPaymentCodeUniqueSql = 'ALTER TABLE `'.PREFIX.'payment` '.
				'ADD UNIQUE(`code`)';
			DB::query($setPaymentCodeUniqueSql);
		}

		// Убираем ненужное поле add_security из таблицы оплат
		$checkAddSecurityFieldSql = 'SHOW COLUMNS FROM '.
			'`'.PREFIX.'payment` LIKE \'add_security\'';
		$checkAddSecurityFieldResult = DB::query($checkAddSecurityFieldSql);
		if (DB::fetchAssoc($checkAddSecurityFieldResult)) {
			$removeAddSecurityFieldSql = 'ALTER TABLE `'.PREFIX.'payment` '.
				'DROP COLUMN `add_security`';
			DB::query($removeAddSecurityFieldSql);
		}

		// добавляем директиву системы оплат
		if(!defined('NEW_PAYMENT')) {
			$str = "\r\n";
			$str .= "; Для переключения между старой системой оплат и новой системой оплат\r\n";
			$str .= "NEW_PAYMENT = 0\r\n";
			file_put_contents('config.ini', $str, FILE_APPEND);
		}

		// меняем размерность атрибута paramArray с varchar(512) на text
		$setPaymentParamArrayAsTextSql = 'ALTER TABLE `'.PREFIX.'payment` '.
			'CHANGE `paramArray` `paramArray` text DEFAULT NULL';
		DB::query($setPaymentParamArrayAsTextSql);
		
		//Добавляем настройку вывода закончившихся товаров в конец
		DB::query("INSERT IGNORE INTO  `".PREFIX."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL,'productsOutOfStockToEnd', 'false', 'Y', 'PRODUCTS_OUT_OF_STOCK_TO_THE_END')");
	}

	if (DatoBaseUpdater::check('10.9.11')) {
		// Переименовываем файл error_sql.log в error_sql.php
		// Чтобы его нельзя было просмотреть и скачать через сайт
		if (is_file(TEMP_DIR.DS.'error_sql.log')) {
			rename(TEMP_DIR.DS.'error_sql.log', TEMP_DIR.DS.'error_sql.php');
		}

		// Удаляем старый модификатор
		$oldModifFile = SITE_DIR.CORE_DIR.'modificatoryInc.php';
		if (is_file($oldModifFile)) {
			unlink($oldModifFile);
		}
	}

	if (DatoBaseUpdater::check('10.10.2')) {
		// увеличиваем размерность поля id в таблице остатков по складам
		$storageTable = '`'.PREFIX.'product_on_storage`';
		$storageIdColumnSql = 'SHOW COLUMNS '.
			'FROM '.$storageTable.' '.
			'LIKE \'id\'';
		$storageIdColumnResult = DB::query($storageIdColumnSql);
		if ($storageIdColumnRow = DB::fetchAssoc($storageIdColumnResult)) {
			$storageIdColumnType = $storageIdColumnRow['Type'];
			if (strpos($storageIdColumnType, 'int') === 0) {
				$changeStorageIdColumnTypeSql = 'ALTER TABLE '.$storageTable.' '.
					'CHANGE `id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST';
				DB::query($changeStorageIdColumnTypeSql);
			}
		}
	}

	if (DatoBaseUpdater::check('10.10.3')) {
		// Настройка блокировки почтовых сервисов (№ 406-ФЗ)
		if(MG::getSetting('mailsBlackList') === null) {
			MG::setOption(array('option' => 'mailsBlackList', 'value' => '', 'active' => 'Y', 'name' => 'MAILS_BLACK_LIST'));
		}

		// Сообщение о невозможновсти зарегистрироваться с определенной почтой
		$checkWrongMailSql = 'SELECT `id` '.
			'FROM `'.PREFIX.'messages` '.
			'WHERE `name` = '.DB::quote('msg__reg_blocked_email');
		$checkWrongMailResult = DB::query($checkWrongMailSql);
		if (!DB::fetchAssoc($checkWrongMailResult)) {
			$wrongMailMessageData = [
				'NULL',
				DB::quote('msg__reg_blocked_email'),
				DB::quote('Указанный E-mail запрещён администратором!'),
				DB::quote('Указанный E-mail запрещён администратором!'),
				DB::quote('register'),
			];
			$addWrongMailMessageSql = 'INSERT '.
				'INTO `'.PREFIX.'messages` '.
				'VALUES ('.implode(', ', $wrongMailMessageData).')';
			DB::query($addWrongMailMessageSql);
		}
	}

	if (DatoBaseUpdater::check('10.10.5')) {
		// Добавляем настройку для favicon
		if (MG::getSetting('favicon') === null) {
			MG::setOption(['option' => 'favicon', 'value' => 'favicon.ico', 'active' => 'Y', 'name' => '']);
			MG::setSetting('favicon', 'favicon.ico');
		}

		// Заново проставляем AUTO_INCREMENT для id таблицы остатков по складам (product_on_storage)
		$storageTable = '`'.PREFIX.'product_on_storage`';
		$changeStorageIdColumnTypeSql = 'ALTER TABLE '.$storageTable.' '.
			'CHANGE `id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST';
		DB::query($changeStorageIdColumnTypeSql);

		// Добавляем в таблицу заказов поле для оплаты заказа по ссылке
		$orderTable = '`'.PREFIX.'order`';
		$checkPayHashColumnExistsSql = 'SHOW COLUMNS '.
			'FROM '.$orderTable.' LIKE "pay_hash"';
		$checkPayHashColumnExistsResult = DB::query($checkPayHashColumnExistsSql);
		if (!DB::fetchAssoc($checkPayHashColumnExistsResult)) {
			$addPayHashColumnSql = 'ALTER TABLE '.$orderTable.' '.
				'ADD COLUMN `pay_hash` VARCHAR(191) NULL DEFAULT "" '.
				'COMMENT "Случайный hash для оплаты заказа по ссылке"';
			DB::query($addPayHashColumnSql);
		}
	}

	if (DatoBaseUpdater::check('10.10.6')) {
		// Меняем кодировку некоторых полей в таблице property_data
		$changePropertyDataNameEncodingSql = 'ALTER TABLE `'.PREFIX.'property_data` '.
			'CHANGE `name` `name` varchar(249) CHARACTER SET utf8mb4 NOT NULL';
		$changePropertyDataMarginEncodingSql = 'ALTER TABLE `'.PREFIX.'property_data` '.
			'CHANGE `margin` `margin` text CHARACTER SET utf8mb4 NOT NULL';
		DB::query($changePropertyDataNameEncodingSql);
		DB::query($changePropertyDataMarginEncodingSql);
	}

	if (DatoBaseUpdater::check('10.11.0')) {
		// Добавляем в таблицу настроек интеграции с Авито
		// поле для пользовательских тегов товаров
		$checkAvitoCustomOptionsFieldSql = 'SHOW COLUMNS '.
			'FROM `'.PREFIX.'avito_settings` '.
			'LIKE \'custom_options\'';
		$checkAvitoCustomOptionsFieldResult = DB::query($checkAvitoCustomOptionsFieldSql);
		if (!DB::fetchAssoc($checkAvitoCustomOptionsFieldResult)) {
			$addAvitoCustomOptionsFieldSql = 'ALTER TABLE `'.PREFIX.'avito_settings` '.
				'ADD `custom_options` longtext COLLATE \'utf8mb4_general_ci\' NULL';
				DB::query($addAvitoCustomOptionsFieldSql);
		}


		// Добавляем настройку игнорирования корневой категории при импорте из 1С
		$addSkipRootCat1CSettingSql = 'INSERT IGNORE INTO `'.PREFIX.'setting` '.
			'VALUES (NULL, "skipRootCat1C", "false", "Y", "SKIP_ROOT_CAT_1C")';
		DB::query($addSkipRootCat1CSettingSql);

		DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."url_canonical` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`url_page` TEXT NOT NULL,
				`url_canonical` TEXT NOT NULL,
				`activity` tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		if(MG::getSetting('useSeoCanonical') === null) {
			MG::setOption(['option' => 'useSeoCanonical', 'value' => 'false','active' => 'Y', 'name' => 'SEO_CANONICAL']);
		}

		// Добавляем настройку партнёрской (агентской) ссылки с копирайта
		if (MG::getSetting('copyrightMogutaLink') === null) {
			MG::setOption(['option' => 'copyrightMogutaLink', 'value' => '', 'active' => 'Y', 'name' => 'COPYRIGHT_MOGUTA_LINK']);
		}

	}

	if (DatoBaseUpdater::check('10.11.4')) {
		// фикс типа дополнительного поля в настройках 1c
		$op1cPrice = MG::getSetting('op1cPrice', true);
		if ($op1cPrice) {
			foreach ($op1cPrice as $opId => $opType) {
				if ($opType === 'true') {
					$op1cPrice[$opId] = 'fromPrice';
					continue;
				}
				if ($opType === 'false') {
					$op1cPrice[$opId] = 'fromProp';
					continue;
				}
			}

			$newOp1cPrice = addslashes(serialize($op1cPrice));
			MG::setOption('op1cPrice', $newOp1cPrice);
		}
	}

	if (DatoBaseUpdater::check('10.12.0')) {
		// Добавляем новое сообщение для валидации заказа
		$checkOrderValidationMessageExistsSql = 'SELECT `id` '.
			'FROM `'.PREFIX.'messages` '.
			'WHERE `name` = '.DB::quote('msg__products_not_same_storage');
		$checkOrderValidationMessageExistsResult = DB::query($checkOrderValidationMessageExistsSql);
		if (!DB::fetchAssoc($checkOrderValidationMessageExistsResult)) {
			$addOrderValidationMessageSql = 'INSERT IGNORE INTO `'.PREFIX.'messages` '.
				'VALUES ('.implode(', ', [
					'NULL',
					DB::quote('msg__products_not_same_storage'),
					DB::quote('Невозможно собрать заказ с одного склада'),
					DB::quote('Невозможно собрать заказ с одного склада'),
					DB::quote('order'),
				]).')';
			DB::query($addOrderValidationMessageSql);
		}

		// Небольшие изменения текста оформления заказа
		$origEmailLetterPart = 'для подтверждения заказа и создания личного кабинета';
		$newEmailLetterPart = 'для подтверждения заказа';
		$emailLetterSql = 'SELECT `content` '.
			'FROM `'.PREFIX.'letters` '.
			'WHERE `name` = "email_order.php" AND '.
				'`content` LIKE "'.DB::quote('%'.$origEmailLetterPart.'%', true).'"';
		$emailLetterResult = DB::query($emailLetterSql);
		if ($emailLetterRow = DB::fetchAssoc($emailLetterResult)) {
			$emailLetterContent = $emailLetterRow['content'];
			if (strpos($emailLetterContent, $origEmailLetterPart) !== false) {
				$newEmailLetterContent = str_replace($origEmailLetterPart, $newEmailLetterPart, $emailLetterContent);
				$updateEmailLetterContentSql = 'UPDATE `'.PREFIX.'letters` '.
					'SET `content` = '.DB::quote($newEmailLetterContent).' '.
					'WHERE `name` = "email_order.php"';
				DB::query($updateEmailLetterContentSql);
			}
		}

		// Изменение категорий авито
		$avitoCatsTable = '`'.PREFIX.'avito_cats`';

		$catsCountSql = 'SELECT COUNT(`id`) as count '.
			'FROM '.$avitoCatsTable;
		$catsCountResult = DB::query($catsCountSql);
		if ($catsCountRow = DB::fetchAssoc($catsCountResult)) {
			$catsCount = intval($catsCountRow['count']);
			if ($catsCount && $catsCount !== 445) {
				$currentSettings = [];
				$currentSettingsSql = 'SELECT `name`, `cats` '.
					'FROM `'.PREFIX.'avito_settings`';
				$currentSettingsResult = DB::query($currentSettingsSql);
				$currentCatsIds = [];
				$catsNamesToIds = [];
				while ($currentSettingsRow = DB::fetchAssoc($currentSettingsResult)) {
					$settingName = $currentSettingsRow['name'];
					$catsSettings = unserialize(stripslashes($currentSettingsRow['cats']));
					$currentSettings[$settingName] = $catsSettings;
					foreach ($catsSettings as $mCatId => $aCatId) {
						if ($aCatId) {
							$currentCatsIds[] = $aCatId;
						}
					}
				}
				if ($currentCatsIds) {
					$catsNamesSql = 'SELECT `id`, `name` '.
						'FROM '.$avitoCatsTable.' '.
						'WHERE `id` IN ('.DB::quoteIN($currentCatsIds).')';
					$catsNamesResult = DB::query($catsNamesSql);
					while ($catsNamesRow = DB::fetchAssoc($catsNamesResult)) {
						$catId = $catsNamesRow['id'];
						$catName = $catsNamesRow['name'];
						$catsNamesToIds[$catName] = $catId;
					}
				}
				$avitoCatsFile = 'zip://'.SITE_DIR.'mg-core'.DS.'script'.DS.'zip'.DS.'avito_cats.zip#cats.txt';
				$avitoCatsValues = file_get_contents($avitoCatsFile);
				if ($avitoCatsValues) {
					$truncateAvitoCatsSql = 'TRUNCATE TABLE '.$avitoCatsTable;
					if (DB::query($truncateAvitoCatsSql)) {
						$updateAvitoCatsSql = 'INSERT INTO '.$avitoCatsTable.' '.
							'VALUES '.$avitoCatsValues;
						DB::query($updateAvitoCatsSql);
						if ($currentCatsIds) {
							$newCatsNamesToIds = [];
							$catsNamesSql = 'SELECT `id`, `name` '.
								'FROM '.$avitoCatsTable.' '.
								'WHERE `name` IN ('.DB::quoteIN(array_keys($catsNamesToIds)).')';
							$catsNamesResult = DB::query($catsNamesSql);
							while ($catsNamesRow = DB::fetchAssoc($catsNamesResult)) {
								$catId = $catsNamesRow['id'];
								$catName = $catsNamesRow['name'];
								$newCatsNamesToIds[$catName] = $catId;
							}
							foreach ($currentSettings as $settingName => $catsSettings) {
								$newSettings = [];
								foreach ($catsSettings as $mCatId => $aCatId) {
									if ($aCatId) {
										foreach ($catsNamesToIds as $catName => $catId) {
											if (intval($aCatId) === intval($catId)) {
												if (empty($newCatsNamesToIds[$catName])) {
													break;
												}
												$newCatId = $newCatsNamesToIds[$catName];
												$newSettings[$mCatId] = $newCatId;
												break;
											}
										}
									}
								}
								$newSerializedSettings = addslashes(serialize($newSettings));
								$updateSettingsSql = 'UPDATE `'.PREFIX.'avito_settings` '.
									'SET `cats` = '.DB::quote($newSerializedSettings).' '.
									'WHERE `name` = '.DB::quote($settingName);
									DB::query($updateSettingsSql);
							}
						}
					}
				}
			}
		}

		// Добавляем новое поле в таблицу товаров для общего количества по складам
		$checkStorageCountFieldSql = 'SHOW COLUMNS FROM '.
			'`'.PREFIX.'product` '.
			'LIKE "storage_count"';
		$checkStorageCountFieldResult = DB::query($checkStorageCountFieldSql);
		if (!DB::fetchAssoc($checkStorageCountFieldResult)) {
			$addStorageCountColumnSql = 'ALTER TABLE `'.PREFIX.'product` '.
				'ADD COLUMN `storage_count` FLOAT NULL DEFAULT 0 AFTER `multiplicity`';
			DB::query($addStorageCountColumnSql);
		}

		// Добавляем информационное сообщение, о необоходимости перерасчёта количества товара, если используются склады
		if (MG::enabledStorage()) {
			$notificationsTable = '`'.PREFIX.'notification`';
			$storagesNotificationText = '<p><b>Внимание!</b> У вас в настройках включена опция "Использовать склады".</p>'.
			'<p>В версии Moguta.CMS v10.12.0 и выше оптимизирована работа со складами, что существенно увеличивает скорость работы сайта.</p>'.
			'<p><b>Важно!</b> Для корректной работы складов, необоходимо перейти в раздел «Настройки» -> «Склады» и нажать на кнопку «Пересчитать количество товаров».
			Будет произведен сбор актуального количества товаров на всех складах и занесен в таблицу товаров, 
			для ускорения дальнейшей работы. После завершения пересчета, кнопка исчезнет, а в будущем актульное количество товаров 
			будет поддерживаться автоматически при работе с сайтом.</p>
			<img src="https://moguta.ru/uploads/sys-info-banner/ne-udaliat-nujno-dlia-obnovy.jpg">';
			$checkStoragesNotificationExistsSql = 'SELECT `id` '.
				'FROM '.$notificationsTable.' '.
				'WHERE `message` = '.DB::quote($storagesNotificationText);
			$checkStoragesNotificationExistsResult = DB::query($checkStoragesNotificationExistsSql);
			if (!DB::fetchAssoc($checkStoragesNotificationExistsResult)) {
				$addStoragesNotificationSql = 'INSERT INTO '.$notificationsTable.' '.
					'(`message`, `status`) '.
					'VALUES ('.DB::quote($storagesNotificationText).', 0)';
				DB::query($addStoragesNotificationSql);
			}

			MG::setOption('showStoragesRecalculate', 'true');
			MG::setSetting('showStoragesRecalculate', 'true');
		}

		// Отключаем опцию старого объединения CSS/JS
		MG::setOption('cacheCssJs', 'false');
		MG::setSetting('cacheCssJs', 'false');

		// Убираем из таблицы складов лишние (которых нет в настройках)
		// по большей части это для разворота квикстартов
		$storagesIds = [];
		$storagesSettings = MG::getSetting('storages', true);
		foreach ($storagesSettings as $storageData) {
			$storageId = $storageData['id'];
			$storagesIds[] = $storageData['id'];
		}
		$deleteOutdatedStocksSql = 'DELETE FROM `'.PREFIX.'product_on_storage` '.
			'WHERE `storage` NOT IN ('.DB::quoteIN($storagesIds).')';
		DB::query($deleteOutdatedStocksSql);
	}

	if (DatoBaseUpdater::check('10.12.6')) {
		// Если куда-то исчезли настройки обновляемых полей товаров в 1с (или их часть),
		// то восстанавливаем их
		$neededFields = [
			'1c_title',
			'1c_code',
			'1c_url',
			'1c_weight',
			'1c_count',
			'1c_description',
			'1c_image_url',
			'1c_meta_title',
			'1c_meta_keywords',
			'1c_activity',
			'1c_old_price',
			'1c_cat_id',
		];
		$notUpdate1CSetting = MG::getSetting('notUpdate1C', true);

		$update1CSetting = false;
		foreach ($neededFields as $neededField) {
			if (!isset($notUpdate1CSetting[$neededField])) {
				$notUpdate1CSetting[$neededField] = 'true';
				$update1CSetting = true;
			}
		}

		if ($update1CSetting) {
			$newNotUpdate1CSetting = addslashes(serialize($notUpdate1CSetting));
			MG::setOption('notUpdate1C', $newNotUpdate1CSetting);
			MG::setSetting('notUpdate1C', $newNotUpdate1CSetting);
		}
	}

	if (DatoBaseUpdater::check('10.12.7')) {
		// Для шаблона moguta-standard и moguta добавляем те пользовательские цвета, которые отсутсвуют
		// Берём их из первого цвета в наборе
		// А лишние цвета наоборот убираем
		$currentUserColors = MG::getSetting('userDefinedTemplateColors', true);
		if (!empty($currentUserColors['moguta-standard'])) {
			$mgUserColors = $currentUserColors['moguta-standard'];
			$mgTemplateIniFilePath = SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'config-sample.ini';
			if (is_file($mgTemplateIniFilePath)) {
				$mgTemplateConfig = parse_ini_file($mgTemplateIniFilePath, 1);
				if (!empty($mgTemplateConfig['COLORS']['1'])) {
					$firstColors = $mgTemplateConfig['COLORS']['1'];
					foreach ($firstColors as $colorKey => $colorValue) {
						if (!isset($mgUserColors[$colorKey])) {
							$mgUserColors[$colorKey] = $colorValue;
						}
					}
					$mgUserColors = array_intersect_key($mgUserColors, $firstColors);
					$currentUserColors['moguta-standard'] = $mgUserColors;
					$newUserColors = addslashes(serialize($currentUserColors));
					MG::setSetting('userDefinedTemplateColors', $newUserColors);
					MG::setOption('userDefinedTemplateColors', $newUserColors);
				}
			}
		}
		if (!empty($currentUserColors['moguta'])) {
			$mgUserColors = $currentUserColors['moguta'];
			$mgTemplateIniFilePath = SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'config-sample.ini';
			if (is_file($mgTemplateIniFilePath)) {
				$mgTemplateConfig = parse_ini_file($mgTemplateIniFilePath, 1);
				if (!empty($mgTemplateConfig['COLORS']['1'])) {
					$firstColors = $mgTemplateConfig['COLORS']['1'];
					foreach ($firstColors as $colorKey => $colorValue) {
						if (!isset($mgUserColors[$colorKey])) {
							$mgUserColors[$colorKey] = $colorValue;
						}
					}
					$mgUserColors = array_intersect_key($mgUserColors, $firstColors);
					$currentUserColors['moguta'] = $mgUserColors;
					$newUserColors = addslashes(serialize($currentUserColors));
					MG::setSetting('userDefinedTemplateColors', $newUserColors);
					MG::setOption('userDefinedTemplateColors', $newUserColors);
				}
			}
		}
	}

	if (DatoBaseUpdater::check('11.0.0')) {
		// Удаляем лишние письмо о смене статуса заказа
		$deleteOrderStatusMailSql = 'DELETE FROM `'.PREFIX.'letters` '.
			'WHERE `name` = "email_order_status.php"';
		DB::query($deleteOrderStatusMailSql);

		// Добавляем настройку заглушечного изображения
		$currentNoImageStub = MG::getSetting('noImageStub');
		if (is_null($currentNoImageStub)) {
			MG::setSetting('noImageStub', '/uploads/no-img.jpg');
			MG::setOption([
				'option' => 'noImageStub',
				'value' => '/uploads/no-img.jpg',
				'active' => 'Y',
				'name' => 'NO_IMAGE_STUB',
			]);
		}
	}

	if (DatoBaseUpdater::check('11.0.7')) {
		$updateAvitoMonitorCatSql = 'UPDATE `'.PREFIX.'avito_cats` '.
			'SET `name` = "Мониторы и запчасти" '.
			'WHERE `id` = 418 '.
				'AND `name` = "Мониторы" '.
				'AND `parent_id` = 417';
		DB::query($updateAvitoMonitorCatSql);
	}

	if (DatoBaseUpdater::check('11.1.0')) {
		if (is_null(MG::getSetting('registrationMethod'))) {
			$registrationMethod = 'email';
			if (MG::getSetting('confirmRegistrationPhone') === 'true' && class_exists('SMSAlertsExt')) {
				$registrationMethod = 'both';
			}
			$registrationMethodsSetting = [
				'option' => 'registrationMethod',
				'value' => $registrationMethod,
				'active' => 'Y',
				'name' => 'REGISTRATION_METHOD',
			];
			MG::setOption($registrationMethodsSetting);
			MG::setSetting('registrationMethod', $registrationMethod);
		}

		//Добавляем настройку вывода товаров со старой ценой только на странице акций
		DB::query("INSERT IGNORE INTO  `".PREFIX."setting` (`id` ,`option` ,`value` ,`active` ,`name`) VALUES (NULL,'oldPricedOnSalePageOnly', 'false', 'Y', 'OLD_PRICED_ON_SALE_PAGE_ONLY')");
	}

	if (DatoBaseUpdater::check('11.2.0')) {
		// Опция запрета оформления заказа для неподтверждённых пользователей
		$orderOnlyForConfirmedUsers = MG::getSetting('orderOnlyForConfirmedUsers');
		if (!$orderOnlyForConfirmedUsers) {
			MG::setOption([
				'option' => 'orderOnlyForConfirmedUsers',
				'value' => 'false',
				'active' => 'Y',
				'name' => 'ORDER_ONLY_FOR_CONFIRMED_USERS',
			]);
			MG::setSetting('orderOnlyForConfirmedUsers', 'false');
		}

		$sql = DB::query("SELECT * FROM `".$prefix."letters` WHERE name = 'layout_agreement.php'");

		if(!DB::fetchAssoc($sql)){
			DB::query("INSERT INTO `".$prefix."letters` (name, content, lang) VALUES ('layout_agreement.php', '', 'default')");
		}
	}

	if (DatoBaseUpdater::check('11.3.0')) {
		//Настройка учета скидок и куппонов при расчете стоимости бесплатной доставки
		$enableDeliveryCostDiscount = MG::getSetting('enableDeliveryCostDiscount');
		if (!$enableDeliveryCostDiscount) {
			MG::setOption([
				'option' => 'enableDeliveryCostDiscount',
				'value' => 'false',
				'active' => 'Y',
				'name' => 'ENABLE_DELIVERY_COST_DISCOUNT',
			]);
			MG::setSetting('enableDeliveryCostDiscount', 'false');
		}

		//Настройка отображения фильтра цена от
		$printPriceFilterBlock = MG::getSetting('printPriceFilterBlock');
		if (!$printPriceFilterBlock) {
			MG::setOption([
				'option' => 'printPriceFilterBlock',
				'value' => 'true',
				'active' => 'Y',
				'name' => 'FILTER_PRINT_PRICE',
			]);
			MG::setSetting('printPriceFilterBlock', 'true');
		}

		// Добавляем в таблицу заказов поле идентификатор реквизитов владельца магазина
		$orderTable = '`'.PREFIX.'order`';
		$checkShopYurIdColumnExistsSql = 'SHOW COLUMNS '.
			'FROM '.$orderTable.' LIKE "shop_yur_id"';
		$checkShopYurIdColumnExistsResult = DB::query($checkShopYurIdColumnExistsSql);
		if (!DB::fetchAssoc($checkShopYurIdColumnExistsResult)) {
			$addShopYurIdColumnSql = 'ALTER TABLE '.$orderTable.' '.
				'ADD COLUMN `shop_yur_id` int(11) unsigned NULL DEFAULT 0 '.
				'COMMENT "id реквизитов юрлица магазина"';
			DB::query($addShopYurIdColumnSql);
		}

		//Настройка отображения товаров в каталоге
		$printTypeProduct = MG::getSetting('printTypeProduct');
		if (!$printTypeProduct) {
			MG::setOption([
				'option' => 'printTypeProduct',
				'value' => 'false',
				'active' => 'Y',
				'name' => 'PRINT_TYPE_PRODUCT',
			]);
			MG::setSetting('printTypeProduct', 'false');
		}
		
		DB::query("INSERT IGNORE INTO `".$prefix."messages` (`name`, `text`, `text_original`, `group`) VALUES
		('msg__date_delivery_incorrect', 'Неправильно заполнена дата доставки', 'Неправильно заполнена дата доставки', 'order')");

		//Настройка отправки email клиентам при смене статуса заказа в обмене 1с
		$statusChangeMail1c = MG::getSetting('statusChangeMail1c');
		if (!$statusChangeMail1c) {
			MG::setOption([
				'option' => 'statusChangeMail1c',
				'value' => 'false',
				'active' => 'Y',
				'name' => 'STATUS_CHANGE_MAIL_1C',
			]);
			MG::setSetting('statusChangeMail1c', 'false');
		}

		$sortFieldsCatalog = MG::getSetting('sortFieldsCatalog');
		if (!$sortFieldsCatalog) {
			MG::setOption([
				'option' => 'sortFieldsCatalog',
				'value' => 'a:13:{s:15:\"price_course|-1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_11\";s:6:\"enable\";s:4:\"true\";}s:14:\"price_course|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_12\";s:6:\"enable\";s:4:\"true\";}s:4:\"id|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_13\";s:6:\"enable\";s:4:\"true\";}s:11:\"count_buy|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_14\";s:6:\"enable\";s:4:\"true\";}s:11:\"recommend|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_15\";s:6:\"enable\";s:4:\"true\";}s:5:\"new|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_16\";s:6:\"enable\";s:4:\"true\";}s:11:\"old_price|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_17\";s:6:\"enable\";s:4:\"true\";}s:7:\"sort|-1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_10\";s:6:\"enable\";s:4:\"true\";}s:6:\"sort|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_22\";s:6:\"enable\";s:4:\"true\";}s:7:\"count|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_18\";s:6:\"enable\";s:4:\"true\";}s:8:\"count|-1\";a:2:{s:4:\"lang\";s:21:\"SORT_INCREASING_COUNT\";s:6:\"enable\";s:4:\"true\";}s:7:\"title|1\";a:2:{s:4:\"lang\";s:10:\"SORT_NAMES\";s:6:\"enable\";s:4:\"true\";}s:8:\"title|-1\";a:2:{s:4:\"lang\";s:14:\"SORT_REV_NAMES\";s:6:\"enable\";s:4:\"true\";}}',
				'active' => 'Y',
				'name' => 'SORT_FIELDS_CATALOG',
			]);
			MG::setSetting('sortFieldsCatalog', 'false');
		}

		// Директива для вложенных категорий(скрывает вложенные категории у которых нет товаров) 
		if(!defined('CATEGORY_EMPTY_SHOW')) {
			$str = "\r\n";
			$str .= "; Скрывать вложенные категории у которых нет товаров \r\n";
			$str .= "CATEGORY_EMPTY_SHOW = 1\r\n";
			file_put_contents('config.ini', $str, FILE_APPEND);
		}

	}

	if (DatoBaseUpdater::check('11.3.1')) {
		// меняем CATEGORY_EMPTY_SHOW директиву на 0 (новое значение по умолчанию)
		$configIniPath = SITE_DIR.'config.ini';
		$currentConfigIniContent = file_get_contents($configIniPath);
		if ($currentConfigIniContent) {
			$newConfigIniContent = str_replace('CATEGORY_EMPTY_SHOW = 1', 'CATEGORY_EMPTY_SHOW = 0', $currentConfigIniContent);
			file_put_contents($configIniPath, $newConfigIniContent);
		}

		//опция сортировки
		$sortFieldsValues = 'a:13:{s:15:\"price_course|-1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_11\";s:6:\"enable\";s:4:\"true\";}s:14:\"price_course|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_12\";s:6:\"enable\";s:4:\"true\";}s:4:\"id|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_13\";s:6:\"enable\";s:4:\"true\";}s:11:\"count_buy|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_14\";s:6:\"enable\";s:4:\"true\";}s:11:\"recommend|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_15\";s:6:\"enable\";s:4:\"true\";}s:5:\"new|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_16\";s:6:\"enable\";s:4:\"true\";}s:11:\"old_price|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_17\";s:6:\"enable\";s:4:\"true\";}s:7:\"sort|-1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_10\";s:6:\"enable\";s:4:\"true\";}s:6:\"sort|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_22\";s:6:\"enable\";s:4:\"true\";}s:7:\"count|1\";a:2:{s:4:\"lang\";s:19:\"SETTINGS_OPTIONS_18\";s:6:\"enable\";s:4:\"true\";}s:8:\"count|-1\";a:2:{s:4:\"lang\";s:21:\"SORT_INCREASING_COUNT\";s:6:\"enable\";s:4:\"true\";}s:8:\"title|-1\";a:2:{s:4:\"lang\";s:10:\"SORT_NAMES\";s:6:\"enable\";s:4:\"true\";}s:7:\"title|1\";a:2:{s:4:\"lang\";s:14:\"SORT_REV_NAMES\";s:6:\"enable\";s:4:\"true\";}}';
		MG::setOption([
			'option' => 'sortFieldsCatalog',
			'value' => $sortFieldsValues,
			'active' => 'Y',
			'name' => 'SORT_FIELDS_CATALOG',
		]);
		MG::setSetting('sortFieldsCatalog', $sortFieldsValues);
	}

	if (DatoBaseUpdater::check('11.3.2')) {
		// Добавляем индекс на prop_data_id в таблицу product_user_property_data
		$pupdTable = '`'.PREFIX.'product_user_property_data`';
		$checkPropDataIdIndex = 'SHOW INDEXES '.
			'FROM '.$pupdTable.' '.
			'WHERE `Column_name` = \'prop_data_id\' ';
		$checkPropDataIdIndex = DB::query($checkPropDataIdIndex);
		if (!DB::fetchAssoc($checkPropDataIdIndex)) {
			$addPropDataIdSql = 'ALTER TABLE '.$pupdTable.' '.
				'ADD INDEX(`prop_data_id`)';
			DB::query($addPropDataIdSql);
		}
	}
	DatoBaseUpdater::updateDbVer();//должно быть в самом конце
$actualVer = str_replace('v', '', VER);
MG::setOption('lastModVersion', $actualVer);
MG::loger('MOD COMPLETE. New lastModVersion '.$actualVer);
