<?php

/**
 * Контроллер: Api
 *
 * Класс Controllers_Api позволяет сторонним сайтам и приложениям осуществлять взаимодействия с магазином;
 *	
 * <code>
 *	$api = new mogutaApi('адрес магазина', 'токен', 'секретный ключ');
 *	$testParam = array('111', '222', '333');
 * 	$res = $api->run('test', $testParam, true);
 * 	viewData($res);
 * </code>
 *
 * @package moguta.cms
 * @subpackage Controller
 */

class Controllers_Api extends BaseController {

	//================================================
	//			ПЕРЕМЕННЫЕ ДЛЯ РАБОТЫ API
	//================================================

	private static $status = 'OK'; 
	private static $error = '0'; 
	private static $sign = ''; 
	private static $key = ''; 
	private static $options = array(); 
	private static $workTime = null; 

	//================================================
	//			  РАЗРЕШЕННЫЕ ФУНКЦИИ
	//================================================

	private static $functionsArray = array(
		// - test
		'test', 
		// - пользователи
		'getUsers',
		'importUsers',
		'deleteUser',
		'findUser',
		// - категории
		'getCategory',
		'importCategory',
		'deleteCategory',
		// - заказы
		'getOrder',
		'importOrder',
		'deleteOrder',
		// - товары
		'getProduct',
		'importProduct',
		'deleteProduct',
		// - дополнительные методы
		'createCustomFields',
	);

	//================================================
	//				  СПИСОК ОШИБОК
	//================================================
	// 1 - неверный токен
	// 2 - ошибка вызова функции
	// 3 - API не настроен
	//================================================

	public function __construct() {
		self::$workTime = microtime(true);
		// загружаем настройки для API
		self::$options = unserialize(stripslashes(MG::getOption('API')));
		if(empty(self::$options)) {
			self::error(3);
		}
		//логирование входных данных API
        LoggerAction::logAction('API', 'apiQuery', array(
        	'id' =>  $_REQUEST['token'],
        	'token' =>  $_REQUEST['token'],
        	'method' => $_REQUEST['method'],
        	'param' => $_REQUEST['param']
        ));
		// проверка ключа
		$valid = false;
		foreach (self::$options as $item) {
			if($item['token'] == $_REQUEST['token']) {
				$valid = true;
				self::$key = $item['key'];
			}
		}
		if(!$valid) self::error(1);

		// вызов нужной функции
		$result = self::run();

		// генерируем подпись
		self::signGen();

		// выдаем ответ
		self::echoResult($result);
	}

	//================================================
	//			  ВНУТРЕННИЕ ФУНКЦИИ API
	//================================================

	/**
	 * Метод для формирования и отдачи ответа (звершает работу движка)
	 * @param int $data
	 * @return int $data
	 */
	private static function echoResult($data = '') {
		$result = array(
			'status' => self::$status,
			'response' => $data,
			'error' => self::$error,
			'sign' => self::$sign,
			'workTime' => round(microtime(true) - self::$workTime, 3).' ms'
		);

		echo json_encode($result);
		//логирование ответа API
		$result['id'] = isset($token)?$token:'';
        LoggerAction::logAction('API', 'apiResult', $result);                
		exit;
	}

	/**
	 * Eсли произошла ошибка, то запускаем эту функцию и передаем в нее код ошибки, дальше она сама.
	 * @param array $code
	 */
	private static function error($code) {
		self::$error = $code;
		self::$status = 'ERROR';
		self::echoResult();
	}

	/**
	 * Метод для генерации подписи, чтобы клиент был уверен в подлиннсоти ответа.
	 */
	private static function signGen() {
		self::$sign = md5($_REQUEST['token'].$_REQUEST['method'].str_replace('amp;', '', $_REQUEST['param']).self::$key);
	}

	/**
	 * Метод для вызова методов класса.
	 * @return array
	 */
	private static function run() {
		$result = null;
		$_REQUEST['method'] = str_replace(array('export'), array('get'), $_REQUEST['method']);
		if(in_array($_REQUEST['method'], self::$functionsArray)) {
			$function = $_REQUEST['method'];
			$param = json_decode(MG::defenderXss_decode($_REQUEST['param']), true);
			$result = self::$function($param);
		} else {
			self::error(2);
		}
		return $result;
	}

	//================================================
	//		  ЗАПРАШИВАЕМЫЕ ФУНКЦИИ (внешние)
	//================================================

	/**
	 * Метод для проверки подключения к API магазина.
	 * <code>
	 * $param = array('test1' => '111', 'test2' => '222');
	 * $res = $api->run('test', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=test&param={"test1":"111","test2":"222"}');
	 * </code>
	 * @param array|null $param массив с любыми параметрами для тестового подключенияы
	 * @return array
	 */
	public static function test($param = null) {
		return $param;
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С ЮЗЕРАМИ
	//------------------------------------------------

	/**
	 * Метод для получения списка пользователей getUsers (синоним exportUsers)
	 * 
	 * <code>
	 * // Постраничная выгрузка пользователей
	 * $param = array('page' => '1', 'count' => '15');
	 * $res = $api->run('getUsers', $param, true);
	 * viewData($res);
	 * // Выгразка пользователей по Емэйлу
	 * $param['email'] = array('user@localhost/mogutasite', 'admin@admin.ru');
	 * $res = $api->run('getUsers', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getUsers&param={"page":"1","count":"15"}');
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getUsers&param={"email":["user@localhost/mogutasite","admin@admin.ru"]}');
	 * </code>
	 * $param['page'] - страница для выгрузки
	 * $param['count'] - количество пользователей на страницу максимум 250
	 * // или
	 * $param['email'] - массив email'ов пользователей, которых надо найти
	 * @param array|null $param - массив описанных параметров
	 * @return array
	 */
	public static function getUsers($param = null) {
		$result = array();
		if(empty($param['email'])) {
			// ставим параметры по умолчанию, если нет входящих параметров
			if(empty($param['page'])) $param['page'] = 1;
			if(empty($param['count'])) $param['count'] = 250;
			// получаем количество пользователей
			$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'user');
			$tmp = DB::fetchAssoc($res);
			$result['countUsers'] = $tmp['count'];
			// определение того, что нужно выгрузить
			if($param['count'] > 250) $param['count'] = 250; 
			if($result['countUsers'] < $param['count']) {
				$limit = '';
			} else {
				$start = ($param['page'] - 1) * $param['count'];
				$limit = ' LIMIT '.DB::quoteInt($start, true).','.DB::quoteInt($param['count'], true);
			}
			// достаем список пользователей
			$res = DB::query("SELECT * FROM ".PREFIX."user".$limit);
			while($row = DB::fetchAssoc($res)) {
				$result['users'][] = $row;
			}
			$result['page'] = $param['page'];
			$result['count'] = $param['count'];
		} else {
			// достаем список пользователей
			$res = DB::query("SELECT * FROM ".PREFIX."user WHERE email IN (".DB::quoteIN($param['email']).")");
			while($row = DB::fetchAssoc($res)) {
				$result['users'][] = $row;
			}
		}
		return $result;
	}

	/**
	 * Метод для импорта пользователей.
	 * 
	 * <code>
	 * $param['users'] = array(
	 * 	Array(
     *  	'id' => 1,	// id в базе
     *  	'email' => admin@admin.ru,	// email пользователя	
     *  	'role' => 1,	// группа пользователей
     *  	'name' => Администратор,	// имя пользователя (ФИО)
     *  	'sname' => ,	// фамилия (не используется почти)
     *  	'address' => ,	// адресс
     *  	'phone' => ,	// телефон
     *  	'date_add' => 2017-07-12 10:05:47,	// дата создания пользователя				
     *  	'blocked' => 0,	// блокировка доступа к личному кабинету 
     *  	'activity' => 1,	// статус
     *  	'inn' => ,	// ИНН
     *  	'kpp' => ,	// КПП
     *  	'nameyur' => ,	// Юр. лицо
     *  	'adress' => ,	// Юр. адрес
     *  	'bank' => ,	// Банк
     *  	'bik' => ,	// БИК
     *  	'ks' => ,	// К/Сч
     *  	'rs' => ,	// Р/Сч
     *  	'birthday' => ,	// день рождения пользователя
     *  )));
     * $param['enableUpdate'] = true;
	 * $res = $api->run('importUsers', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=importUsers&param={"users":[{"id":1,"email":"admin@admin.ru","role":1,"name":"\u0410\u0434\u043c\u0438\u043d\u0438\u0441\u0442\u0440\u0430\u0442\u043e\u0440","sname":null,"address":null,"phone":null,"date_add":"2017-07-12 10:05:47","blocked":0,"activity":1,"inn":null,"kpp":null,"nameyur":null,"adress":null,"bank":null,"bik":null,"ks":null,"rs":null,"birthday":null}]}');
	 * </code>
	 * $param['users'] - входящий список пользователей для импорта (желательно до 100)
	 * $param['enableUpdate'] - (true/false) включить или выключить обновление пользователей
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function importUsers($param = null) {
		foreach($param['users'] as $item) {
			$user = array();
			// проверка полей по белому списку
			foreach ($item as $key => $value) {
				if(in_array($key, array('email','role','name','sname','phone','date_add','blocked','restore','activity','inn','kpp',
					'nameyur','adress','bank','bik','ks','rs','birthday','ip','pass'))) {
					$user[$key] = $value;
				}
			}
			// проверка наличия юзера
			$res = DB::query('SELECT id FROM '.PREFIX.'user WHERE email = '.DB::quote($user['email']));
			if($id = DB::fetchAssoc($res)) {
				if($param['enableUpdate'])
					DB::query('UPDATE '.PREFIX.'user SET '.DB::buildPartQuery($user).' WHERE id = '.DB::quoteInt($id['id']));
			} else {
				DB::query('INSERT INTO '.PREFIX.'user (email,role,name,sname,phone,date_add,blocked,restore,activity,inn,kpp,
					nameyur,adress,bank,bik,ks,rs,birthday,ip,pass) VALUES 
					('.DB::quote($user['email']).','.DB::quote($user['role']).','.DB::quote($user['name']).','.DB::quote($user['sname']).',
						'.DB::quote($user['phone']).','.DB::quote($user['date_add']).','.DB::quote($user['blocked']).','.DB::quote($user['restore']).',
							'.DB::quote($user['activity']).','.DB::quote($user['inn']).','.DB::quote($user['kpp']).',nameyur,'.DB::quote($user['adress']).',
								'.DB::quote($user['bank']).','.DB::quote($user['bik']).','.DB::quote($user['ks']).','.DB::quote($user['rs']).',
									'.DB::quote($user['birthday']).','.DB::quote($user['ip']).','.DB::quote($user['pass']).')');
			}
		}

		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления пользователей.
	 * 
	 * <code>
	 * $param['email'] = array('user1@mail.ru', 'user2@email.ru', 'user3@mail.ru');
	 * $res = $api->run('deleteUser', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=deleteUser&param={"email":["user1@mail.ru","user2@email.ru","user3@mail.ru"]}');
	 * </code>
	 * $param['email'] - список емэйлов пользователей, которых нужно удалить.
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function deleteUser($param = null) {
		foreach ($param['email'] as $user) {
			$res = DB::query('SELECT id FROM '.PREFIX.'user WHERE email = '.DB::quote($user));
			$id = DB::fetchAssoc($res);
			USER::delete($id['id']);
		}
		
		return 'Удаление завершено';
	}

	/**
	 * Метод для поиска пользователей по электронной почте.
	 * 
	 * <code>
	 * $param = array('email' => 'user2@email.ru');
	 * $res = $api->run('findUser', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=findUser&param={"email":"user1@localhost/mogutasite"}');
	 * </code>
	 * $param['email'] - емэйл пользователя, информацию о котором нужно получить.
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function findUser($param = null) {
		$result = false;
		$res = DB::query('SELECT * FROM '.PREFIX.'user WHERE email = '.DB::quote($param['email']));
		if($row = DB::fetchAssoc($res)) {
			$result = $row;
		}

		if(!$result) $result = 'Пользователь не найден!';
		
		return $result;
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С КАТЕГОРИЯМИ                      
	//------------------------------------------------

	/**
	 * Метод для получения списка категорий getCategory (синоним exportCategory).
	 * 
	 * <code>
	 * // постраничная выгрузка категорий
	 * $param['page'] = 1;
	 * $param['count'] = 15;
	 * $res = $api->run('getCategory', $param, true);
	 * viewData($res);
	 * // поиск категорий по id
	 * $param['id'] = array(1, 2);
	 * $res = $api->run('getCategory', $param, true);
	 * viewData($res);
	 * // поиск категорий по урлу
	 * $param['url'] = array('smartfony');
	 * $res = $api->run('getCategory', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getCategory&param={"page":1,"count":20}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getCategory&param={"id":[1,2,3,4,5]}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getCategory&param={"url":["smartphone","ball"]}');
	 * </code>
	 * $param['page'] - страница для выгрузки
	 * $param['count'] - количество категорий на страницу максимум 250
	 * // или
	 * $param['id'] - массив id категорий
	 * // или
	 * $param['url'] - массив url'ов категорий (последняя секция)
	 * @param array|null $param - массив описанных параметров
	 * @return array
	 */
	public static function getCategory($param = null) {
		$find = false;
		$result = array();
		// поиск по id
		if(!empty($param['id'])) {
			$find = true;
			$res = DB::query('SELECT * FROM '.PREFIX.'category WHERE id IN ('.DB::quoteIN($param['id']).')');
			while($row = DB::fetchAssoc($res)) {
				$result['categories'][] = $row;
			}
			if(empty($result['categories'])) {
				$result = 'Категории не найдены';
			}
		}
		// поиск по урлу категории (последняя секция урла)
		if(!empty($param['url'])) {
			$res = DB::query('SELECT * FROM '.PREFIX.'category WHERE url IN ('.DB::quoteIN($param['url']).')');
			while($row = DB::fetchAssoc($res)) {
				$result['categories'][] = $row;
			}
			if(empty($result['categories'])) {
				$result = 'Категории не найдены';
			}
			$find = true;
		}
		// ставим параметры по умолчанию, если нет входящих параметров
		if(!$find) {
			if(empty($param['page'])) $param['page'] = 1;
			if(empty($param['count'])) $param['count'] = 250;
			// получаем количество категорий
			$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'category');
			$tmp = DB::fetchAssoc($res);
			$result['countCategory'] = $tmp['count'];
			// определение того, что нужно выгрузить
			if($param['count'] > 250) $param['count'] = 250; 
			if($result['countCategory'] < $param['count']) {
				$limit = '';
			} else {
				$start = ($param['page'] - 1) * $param['count'];
				$limit = ' LIMIT '.DB::quoteInt($start, true).','.DB::quoteInt($param['count'], true);
			}
			// достаем список категорий
			$res = DB::query("SELECT * FROM ".PREFIX."category".$limit);
			while($row = DB::fetchAssoc($res)) {
				$result['categories'][] = $row;
			}
			$result['page'] = $param['page'];
			$result['count'] = $param['count'];
		}
		
		return $result;
	}

	/**
	 * Метод для импорта категорий.
	 * 
	 * <code>
	 * $param['categories'] = Array(
     *     Array (												 
     *         'id' => 1,	// id категории
     *         'title' => 'Обезжелезивание реагентное',	// название категории
     *         'url' => 'obezjelezivanie-reagentnoe',	// url категории
     *         'parent' => 0,	// id родительской категории
     *         'parent_url' => ,	// родительский url (полная ссылка без сайта)
     *         'sort' => 1,	// параметр для сортировки
     *         'html_content' => ,	// описание категории
     *         'meta_title' => ,	// SEO заголовок
     *         'meta_keywords' => ,	// SEO ключевые слова
     *         'meta_desc' => ,	// SEO описание
     *         'invisible' => 0,	// скрыть категорию
     *         '1c_id' => ,	// идентификатор в 1с
     *         'image_url' => ,	// изображение категории
     *         'menu_icon' => ,	// иконка в меню
     *         'rate' => 0,	// наценка
     *         'unit' => 0,	// единица измерения товара
     *         'export' => 1,	// 
     *         'seo_content' =>,	// 
     *         'activity' => 1,	// активность
     *     )
     * );
	 * $res = $api->run('importCategory', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=importCategory&param={"categories":[{"id":1,"title":"\u041e\u0431\u0435\u0437\u0436\u0435\u043b\u0435\u0437\u0438\u0432\u0430\u043d\u0438\u0435 \u0440\u0435\u0430\u0433\u0435\u043d\u0442\u043d\u043e\u0435","url":"obezjelezivanie-reagentnoe","parent":0,"parent_url":null,"sort":1,"html_content":null,"meta_title":null,"meta_keywords":null,"meta_desc":null,"invisible":0,"1c_id":null,"image_url":null,"menu_icon":null,"rate":0,"unit":0,"export":1,"seo_content":null,"activity":1}]}');
	 * </code>
	 * $param['categories'] - входящий список категорий для импорта (желательно до 100)
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function importCategory($param = null) {
		foreach ($param['categories'] as $category) {
			// если нет id то пытаемся его найти
			if(empty($category['id'])) {
				$res = DB::query('SELECT id FROM '.PREFIX.'category WHERE url = '.DB::quote($category['url']));
				if($row = DB::fetchAssoc($res)) {
					$category['id'] = $row['id'];
				}
			}
			// если id все же нет, значит новая категория, создаем, иначе обновляем
			if(empty($category['id'])) {
				MG::get('category')->addCategory($category);
			} else {
				MG::get('category')->updateCategory($category);
			}
		}
		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления категорий.
	 * 
	 * <code>
	 * $param['category'] = array(1, 2, 3, 4, 5);
	 * $res = $api->run('deleteCategory', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=deleteCategory&param={"category":[1,2,3,4,5]}');
	 * </code>
	 * $param['category'] - массив с id категорий, которые нужно удалить
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function deleteCategory($param = null) {
		foreach ($param['category'] as $item) {
			MG::get('category')->delCategory($item);
		}
		return 'Удаление завершено';
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С ЗАКАЗАМИ                      
	//------------------------------------------------

	/**
	 * Метод для получения заказов getOrder (синоним exportOrder).
	 * <code>
	 * // постраничная выгрузка заказов
	 * $param['page'] = 1;
	 * $param['count'] = 25;
	 * $res = $api->run('getOrder', $param, true);
	 * viewData($res);
	 * // выгрузка заказов по id
	 * $param['id'] = array(1);
	 * $res = $api->run('getOrder', $param, true);
	 * viewData($res);
	 * // выгрузка заказов по номеру заказа
	 * $param['number'] = array('M-0109749529854');
	 * $res = $api->run('getOrder', $param, true);
	 * viewData($res);
	 * // выгрузка заказов по email'у пользователя
	 * $param['email'] = array('user@localhost/mogutasite');
	 * $res = $api->run('getOrder', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getOrder&param={"page":1,"count":25}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getOrder&param={"id":[1,2,5]}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getOrder&param={"number":["M-732468","M-768743"]}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getOrder&param={"email":["user1@localhost/mogutasite","user2@localhost/mogutasite"]}');
	 * </code>
	 *
	 * $param['page'] - страница для выгрузки
	 * $param['count'] - количество заказов на страницу максимум 250
	 * // или
	 * $param['id'] - массив id заказов
	 * // или
	 * $param['number'] - массив номеров заказов (вот это M-732468)
	 * // или
	 * $param['email'] - массив email'ов пользователей, заказы которых хотите выгрузить
	 * 					 
	 * @param array|null $param - массив описанных параметров
	 * @return array
	 */
	public static function getOrder($param = null) {
		$find = false;
		$result = array();
		// поиск по id
		if(!empty($param['id'])) {
			$find = true;
			$res = DB::query("SELECT * FROM ".PREFIX."order WHERE id IN (".DB::quoteIN($param['id']).")");
			while($row = DB::fetchAssoc($res)) {
				$row['address_parts'] = unserialize(stripslashes($row['address_parts']));
				$row['yur_info'] = unserialize(stripslashes($row['yur_info']));
				$row['order_content'] = unserialize(stripslashes($row['order_content']));
				unset($row['orders_set']);
				$result['orders'][] = $row;
			}
			if(empty($result['orders'])) {
				$result = 'Заказы не найдены';
			}
		}
		// поиск по номеру заказа
		if(!empty($param['number'])) {
			$find = true;
			$res = DB::query("SELECT * FROM ".PREFIX."order WHERE number IN (".DB::quoteIN($param['number']).")");
			while($row = DB::fetchAssoc($res)) {
				$row['address_parts'] = unserialize(stripslashes($row['address_parts']));
				$row['yur_info'] = unserialize(stripslashes($row['yur_info']));
				$row['order_content'] = unserialize(stripslashes($row['order_content']));
				unset($row['orders_set']);
				$result['orders'][] = $row;
			}
			if(empty($result['orders'])) {
				$result = 'Заказы не найдены';
			}
		}
		// поиск по номеру email'у пользователя
		if(!empty($param['email'])) {
			$find = true;
			$res = DB::query("SELECT * FROM ".PREFIX."order WHERE user_email IN (".DB::quoteIN($param['email']).")");
			while($row = DB::fetchAssoc($res)) {
				$row['address_parts'] = unserialize(stripslashes($row['address_parts']));
				$row['yur_info'] = unserialize(stripslashes($row['yur_info']));
				$row['order_content'] = unserialize(stripslashes($row['order_content']));
				unset($row['orders_set']);
				$result['orders'][] = $row;
			}
			if(empty($result['orders'])) {
				$result = 'Заказы не найдены';
			}
		}
		// постраничный поиск
		if(!$find) {
			// ставим параметры по умолчанию, если нет входящих параметров
			if(empty($param['page'])) $param['page'] = 1;
			if(empty($param['count'])) $param['count'] = 250;
			// получаем количество заказов
			$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'order');
			$tmp = DB::fetchAssoc($res);
			$result['countOrder'] = $tmp['count'];
			// определение того, что нужно выгрузить
			if($param['count'] > 250) $param['count'] = 250; 
			if($result['countOrder'] < $param['count']) {
				$limit = '';
			} else {
				$start = ($param['page'] - 1) * $param['count'];
				$limit = ' LIMIT '.DB::quote($start, true).','.DB::quote($param['count'], true);
			}
			// достаем список заказов
			$res = DB::query("SELECT * FROM ".PREFIX."order".$limit);
			while($row = DB::fetchAssoc($res)) {
				$row['address_parts'] = unserialize(stripslashes($row['address_parts']));
				$row['yur_info'] = unserialize(stripslashes($row['yur_info']));
				$row['order_content'] = unserialize(stripslashes($row['order_content']));
				unset($row['orders_set']);
				$result['orders'][] = $row;
			}
			$result['page'] = $param['page'];
			$result['count'] = $param['count'];
		}
		return $result;
	}

	/**
	 * Метод для импорта заказов.
	 * <code>
	 * $param['orders'] = Array (
     *     Array(
     *         'id' => 1,	// id заказа
     *         'updata_date' => '2017-08-18 13:07:29',	// время изменения заказа
     *         'add_date' => '2017-08-18 13:07:29',	// время добавления заказа
     *         'pay_date' => '2017-08-18 13:07:29',	// время оплаты заказа
     *         'close_date' => '2017-08-18 13:07:29',	// время завершения заказа
     *         'user_email' => 'hg@ds.aq',	// емэйл пользователя
     *         'phone' =>,	// телефон пользователя
     *         'address' =>,	// адресс доставки
     *         'summ' => 17519.00,	// сумма товаров заказа
     *         'order_content' => 'a:1:{i:0;a:16:{s:2:\"id\";s:3:\"256\";s:7:\"variant\";s:1:\"0\";s:5:\"title\";s:90:\"Кухонная мойка гранитная MARRBAXX Рики Z22 темно-серый\";s:4:\"name\";s:90:\"Кухонная мойка гранитная MARRBAXX Рики Z22 темно-серый\";s:8:\"property\";s:0:\"\";s:5:\"price\";s:4:\"1000\";s:8:\"fulPrice\";s:4:\"1000\";s:4:\"code\";s:5:\"CN256\";s:6:\"weight\";s:1:\"0\";s:12:\"currency_iso\";s:3:\"RUR\";s:5:\"count\";s:1:\"1\";s:6:\"coupon\";s:1:\"0\";s:4:\"info\";s:0:\"\";s:3:\"url\";s:71:\"kuhonnye-moyki/kuhonnaya-moyka-granitnaya-marrbaxx-riki-z22-temno-seryy\";s:8:\"discount\";s:1:\"0\";s:8:\"discSyst\";s:11:\"false/false\";}}',
     * // сожержание заказа в сериализированном виде
     *         'delivery_id' => 1,	// id способа доставки
     *         'delivery_cost' => 0,	// стоимость доставки
     *         'delivery_options' =>,	// дополнительная информация о способе доставке
     *         'payment_id' => 1,	// id способа оплаты
     *         'status_id' => 0,	// статус заказа
     *         'user_comment' => ,	// комментарий пользователя
     *         'comment' => ,	// комментарий менеджера
     *         'yur_info' => ,	// информация о юридическом лице
     *         'name_buyer' => ,	// ФИО покупателя
     *         'date_delivery' => ,	// дата доставки
     *         'ip' => '::1',	// ip с которого был оформлен заказ
     *         'number' => 'M-0105341895042',	// номер заказа
     *         '1c_last_export' => '2017-08-18 13:07:29',	// идентификатор в 1с
     *         'storage' => ,	// склад
     *         'summ_shop_curr' => 1000,	// сумма заказа в валюте магазина
     *         'delivery_shop_curr' => 0,	// стоимость доставки в валюте магазина
     *         'currency_iso' => 'RUR',	// код валюты
     *     )
	 * );
	 * $res = $api->run('importOrder', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getOrder&param={"orders":[{"id":1,"updata_date":"2017-08-18 13:07:29","add_date":"2017-08-18 13:07:29","pay_date":"2017-08-18 13:07:29","close_date":"2017-08-18 13:07:29","user_email":"hg@ds.aq","phone":null,"address":null,"summ":17519,"order_content":"a:1:{i:0;a:16:{s:2:\\\"id\\\";s:3:\\\"256\\\";s:7:\\\"variant\\\";s:1:\\\"0\\\";s:5:\\\"title\\\";s:90:\\\"\u041a\u0443\u0445\u043e\u043d\u043d\u0430\u044f \u043c\u043e\u0439\u043a\u0430 \u0433\u0440\u0430\u043d\u0438\u0442\u043d\u0430\u044f MARRBAXX \u0420\u0438\u043a\u0438 Z22 \u0442\u0435\u043c\u043d\u043e-\u0441\u0435\u0440\u044b\u0439\\\";s:4:\\\"name\\\";s:90:\\\"\u041a\u0443\u0445\u043e\u043d\u043d\u0430\u044f \u043c\u043e\u0439\u043a\u0430 \u0433\u0440\u0430\u043d\u0438\u0442\u043d\u0430\u044f MARRBAXX \u0420\u0438\u043a\u0438 Z22 \u0442\u0435\u043c\u043d\u043e-\u0441\u0435\u0440\u044b\u0439\\\";s:8:\\\"property\\\";s:0:\\\"\\\";s:5:\\\"price\\\";s:4:\\\"1000\\\";s:8:\\\"fulPrice\\\";s:4:\\\"1000\\\";s:4:\\\"code\\\";s:5:\\\"CN256\\\";s:6:\\\"weight\\\";s:1:\\\"0\\\";s:12:\\\"currency_iso\\\";s:3:\\\"RUR\\\";s:5:\\\"count\\\";s:1:\\\"1\\\";s:6:\\\"coupon\\\";s:1:\\\"0\\\";s:4:\\\"info\\\";s:0:\\\"\\\";s:3:\\\"url\\\";s:71:\\\"kuhonnye-moyki\/kuhonnaya-moyka-granitnaya-marrbaxx-riki-z22-temno-seryy\\\";s:8:\\\"discount\\\";s:1:\\\"0\\\";s:8:\\\"discSyst\\\";s:11:\\\"false\/false\\\";}}","delivery_id":1,"delivery_cost":0,"delivery_options":null,"payment_id":1,"status_id":0,"user_comment":null,"comment":null,"yur_info":null,"name_buyer":null,"date_delivery":null,"ip":"::1","number":"M-0105341895042","1c_last_export":"2017-08-18 13:07:29","storage":null,"summ_shop_curr":1000,"delivery_shop_curr":0,"currency_iso":"RUR"}]}');
	 * </code>
	 * $param['orders'] - входящий список заказов для импорта (желательно до 100)
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function importOrder($param = null) {
		$model = new Models_Order;
		foreach ($param['orders'] as $order) {
			if($order['order_content']) $order['order_content'] = addslashes(serialize($order['order_content']));
			if($order['yur_info']) $order['yur_info'] = addslashes(serialize($order['yur_info']));
			if($order['address_parts']) $order['address_parts'] = addslashes(serialize($order['address_parts']));
			// если id все же нет, значит новая категория, создаем, иначе обновляем
			if(empty($order['id'])) {
				// расшифровка содержимого заказа
				$order['order_content'] = unserialize(stripcslashes($order['order_content']));
				foreach ($order['order_content'] as &$item) {
					$item['title'] = urldecode($item['name']);
				}
				$model->addOrder($order);
			} else {
				$model->updateOrder($order);
			}
		}

		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления заказов.
	 * 
	 * <code>
	 * $param['orders'] = array('1', '2', '3', '4', '5');
	 * $res = $api->run('deleteOrder', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=deleteOrder&param={"orders":[1,2,3,4,5]});
	 * </code>
	 * $param['orders'] - массив с id заказов, которые нужно удалить
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function deleteOrder($param = null) {
		$model = new Models_Order;
		foreach ($param['orders'] as $item) {
			$model->deleteOrder($item);
		}
		return 'Удаление завершено';
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С ТОВАРАМИ                      
	//------------------------------------------------

	/**
	 * Метод для получения списка товаров getProduct (синоним exportProduct).
	 * 
	 * <code>
	 * // постраничная выгрузка товаров
	 * $param['page'] = 1;
	 * $param['count'] = 20;
	 * $param['variants'] = true;
	 * $param['property'] = true;
	 * $res = $api->run('getProduct', $param, true);
	 * viewData($res);
	 * // выгрузка товаров по id
	 * $param['id'] = array(1, 12);
	 * $param['variants'] = true;
	 * $param['property'] = false;
	 * $res = $api->run('getProduct', $param, true);
	 * viewData($res);
	 * // выгрузка товаров по артикулу
	 * $param['code'] = array('SKU348', 'SKU165');
	 * $param['variants'] = true;
	 * $param['property'] = false;
	 * $res = $api->run('getProduct', $param, true);
	 * viewData($res);
	 * // выгрузка товаров по названию
	 * $param['title'] = array('Фитнес-трекер Xiaomi');
	 * $param['variants'] = true;
	 * $param['property'] = false;
	 * $res = $api->run('getProduct', $param, true);
	 * viewData($res);
	 * // вызов через GET
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getProduct&param={"page":1,"count":20,"variants":"true","property":"false"}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getProduct&param={"id":[1,2,3],"variants":"true","property":"false"}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getProduct&param={"code":["SKU348","SKU165"],"variants":"true","property":"false"}');
	 * // или
	 * echo file_get_content('http://site.ru/api?token=ключ-приложения&method=getProduct&param={"title":["Фитнес-трекер Xiaomi"],"variants":"true","property":"false"}');
	 * </code>
	 * $param['page'] - страница для выгрузки
	 * $param['count'] - количество заказов на страницу максимум 100
	 * $param['variants'] - включает вывод вариантов
	 * $param['property'] - включает вывод характеристик
	 * // или
	 * $param['id'] - массив id товаров для выгрузки
	 * $param['variants'] - включает вывод вариантов
	 * $param['property'] - включает вывод характеристик
	 * // или
	 * $param['code'] - массив артикулов товаров для выгрузки
	 * $param['variants'] - включает вывод вариантов
	 * $param['property'] - включает вывод характеристик
	 * // или
	 * $param['title'] - массив названий товаров для выгрузки
	 * $param['variants'] - включает вывод вариантов
	 * $param['property'] - включает вывод характеристик
	 * @param array|null $param - массив описанных параметров
	 * @return array
	 */
	public static function getProduct($param = null) {
		if(empty($param['variants'])) $param['variants'] = false;
		if(empty($param['property'])) $param['property'] = false;
		$find = false;
		// выгрузка товаров по id
		if(!empty($param['id'])) {
			$find = true;
			$res = DB::query("SELECT * FROM ".PREFIX."product WHERE id IN (".DB::quoteIN($param['id']).")");
			while($row = DB::fetchAssoc($res)) {
				$result['products'][] = $row;
			}
		}
		// выгрузка товаров по артикулу
		if(!empty($param['code'])) {
			$find = true;
			$res = DB::query("SELECT * FROM ".PREFIX."product WHERE code IN (".DB::quoteIN($param['code']).")");
			while($row = DB::fetchAssoc($res)) {
				$result['products'][] = $row;
			}
		}
		// выгрузка товаров по названию
		if(!empty($param['title'])) {
			$find = true;
			$res = DB::query("SELECT * FROM ".PREFIX."product WHERE title IN (".DB::quoteIN($param['title']).")");
			while($row = DB::fetchAssoc($res)) {
				$result['products'][] = $row;
			}
		}
		// постраничная выгрузка товаров
		if(!$find) {
			// ставим параметры по умолчанию, если нет входящих параметров
			if(empty($param['page'])) $param['page'] = 1;
			if(empty($param['count'])) $param['count'] = 100;
			// получаем количество товаров
			$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'product');
			$tmp = DB::fetchAssoc($res);
			$result['countProduct'] = $tmp['count'];
			if($param['count'] > 100) $param['count'] = 100; 
			// определение того, что нужно выгрузить
			if($result['countProduct'] < $param['count']) {
				$limit = '';
			} else {
				$start = ($param['page'] - 1) * $param['count'];
				$limit = ' LIMIT '.DB::quote($start, true).','.DB::quote($param['count'], true);
			}
			// достаем список товаров
			$res = DB::query("SELECT * FROM ".PREFIX."product".$limit);
			while($row = DB::fetchAssoc($res)) {
				$result['products'][] = $row;
			}
			// возврат параметров работы метода
			$result['page'] = $param['page'];
			$result['count'] = $param['count'];
		}

		if(empty($result['products'])) return 'Товары не найдены';

		// загрузка вариантов
		if($param['variants']) {
			$ids = array();
			foreach ($result['products'] as $item) {
				$ids[] = $item['id'];
			}
			$prodIdIn = implode(',', $ids);
			unset($ids);
			$res = DB::query('SELECT * FROM '.PREFIX.'product_variant WHERE product_id IN ('.DB::quoteIN($prodIdIn).')');
			while($row = DB::fetchAssoc($res)) {
				foreach ($result['products'] as $key => $value) {
					if($row['product_id'] == $value['id']) {
						$result['products'][$key]['variants'][] = $row;
					}
				}
			}
		}
		// загрузка списка характеристик
		if($param['property']) {
			// массив категорий из текщих выгруженных товаров
			$categories = array();
			foreach ($result['products'] as $item) {
				if(!in_array($item['cat_id'], $categories)) {
					$categories[] = $item['cat_id'];
				}
			}
			// загрузка характеристик (без значений)
			//$catIdIn = implode(',', $categories);
			$property = null;
			$res = DB::query('
				SELECT p.*, (SELECT GROUP_CONCAT(distinct category_id) 
					FROM '.PREFIX.'category_user_property 
						WHERE property_id = p.id AND category_id IN ('.DB::quoteIN($categories).')) AS cat_id 
				FROM '.PREFIX.'property AS p
					RIGHT JOIN '.PREFIX.'category_user_property AS cup
						ON p.id = cup.property_id
					WHERE cup.category_id IN ('.DB::quoteIN($categories).') GROUP BY p.id');
			while($row = DB::fetchAssoc($res)) {
				$property[] = $row;
			}
			// прикрепляем характеристики к товарам
			foreach ($result['products'] as $key => $value) {
				foreach ($property as $prop) {
					if(substr_count(','.$prop['cat_id'].',', ','.$value['cat_id'].',') != 0) {
						unset($prop['cat_id']);
						unset($prop['default']);
						$result['products'][$key]['property'][] = $prop; 
					}
				}
			}
			// загружаем данные характеристик для товаров
			// берем id товаров
			$ids = array();
			foreach ($result['products'] as $item) {
				$ids[] = $item['id'];
			}
			//$prodIdIn = implode(',', $ids);
			$ids = array();
			// берем id характеристик
			foreach ($property as $prop) {
				$ids[] = $prop['id'];
			}
			//$propIdIn = implode(',', $ids);
			//unset($ids);
			$propertyData = array();
			// достаем значения характеристик
			$res = DB::query('SELECT pupd.id, pupd.product_id, pupd.prop_id, pupd.prop_data_id, IF(pupd.name = \'\', pd.name, pupd.name) AS name, pupd.margin 
				FROM '.PREFIX.'product_user_property_data AS pupd 
				LEFT JOIN '.PREFIX.'property_data AS pd ON pd.id = pupd.prop_data_id
				WHERE pupd.prop_id IN ('.DB::quoteIN($ids).') 
				AND pupd.product_id IN ('.DB::quoteIN($ids).')');
			while($row = DB::fetchAssoc($res)) {
				$propertyData[] = $row;
			}
			// прикрепляем значение характеристики к товарам
			foreach ($result['products'] as $key => $value) {
				foreach ($propertyData as $data) {
					if($data['product_id'] == $value['id']) {
						foreach ($value['property'] as $keyP => $valueP) {
							if($valueP['id'] == $data['prop_id']) {
								unset($data['product_id']);
								$result['products'][$key]['property'][$keyP]['data'][] = $data;
							}
						}
					}
				}
			}
		}
		
		$result['variants'] = $param['variants'];
		$result['property'] = $param['property'];

		return $result;
	}

	/**
	 * Метод для добавления или обновления товаров.
	 * 
	 * <code>
	 * $param['products'] = array(array(
	 * 	'id' : => 1,	// id товара
	 * 	'cat_id' : => 1,	// id категории
	 * 	'title' : => 'Распределительный электрошкаф',	// название товара
	 * 	'description' : => '<p>Периодичность обс>ила.</p>',	// описание товара
	 * 	'short_description' : => '<p>Периодичность обс>ила.</p>',	// краткое описание товара
	 * 	'price' : => 87894,	// цена
	 * 	'url' : => 'raspredelitelnyy-elektroshkaf',	// последняя секция урла
	 * 	'image_url' : => 'no-img.jpg',	// ссылки на изображения, разделитель |
	 * 	'code' : => 'TR-15000-V1',	// артикул
	 * 	'count' : => 35,	// количество
	 * 	'storage' : => '1716535424571',	// Идентификатор склада, на котором нужно установить передаваемое количество товара. Работает только при обновлении товара. Идентификатор нужного склада можно найти на вкладке настройки складов
	 * 	'activity' : => 1,	// видимость товара
	 * 	'meta_title' : => 'Распределительный электрошкаф',	// заголовок страницы
	 * 	'meta_keywords' : => 'Распределительный, электрошкаф',	// ключевые слова
	 * 	'meta_desc' : => 'Распределительный электрошкаф',	// мета описание
	 * 	'old_price' : => 38517,	// старая цена
	 * 	'weight' : => 422.019,	// вес
	 * 	'link_electro' : => ,	// сыылка на электронный товар
	 * 	'currency_iso' : => 'RUR',	// символьный код валюты
	 * 	'price_course' : => 87894,	// цена в валюте магазина
	 * 	'image_title' : => ,	// 
	 * 	'image_alt' : => ,	//
	 * 	'unit' : => ,	// единица измерения
	 * 	'variants' : => Array(	// варианты
	 * 		Array(												
	 * 			'title_variant' => '-Var1',	// заголовок варианта
	 * 			'image' => ,	// ссылка на изображение
	 * 			'price' => 87894,	// цена
	 * 			'old_price' => 38517,	// старая цена 
	 * 			'count' => 43,	// количество
	 * 			'code' => 'TR-15000-V1',	// артикул
	 * 			'weight' => 422.019,	// вес товара
	 * 			'currency_iso' => 'RUR',	// символьный код валюты
	 * 			'price_course' => 87894,	// цена в валюте магазина
	 * 			'color' => 87894,	// id цвета товара (если есть) от характеристики
	 * 			'size' => 87894,	// id размера товара (если есть) от характеристики
	 * 		),														
	 * 	),													
	 * 	'property' => Array(	// характиеристики
	 * 	    array(												
	 * 	        'name' => 'Строковая характеристик',	// название характеристики
	 * 	        'type' => 'string',	// тип хварактеристики
	 * 	        'value' => 'Значение',	// значение характеристики
	 * 	    ),													
	 * 	    array(												
	 * 	        'name' => 'Текстовая характеристика',	// название характеристики
	 * 	        'type' => 'textarea',	// тип хварактеристики
	 * 	        'value' => 'Тут может быть много текста',	// значение характеристики
	 * 	    )
	 * 	)
	 * ));
	 * $res = $api->run('importProduct', $param, true);
	 * viewData($res);
	 * </code>
	 * $param['products'] - входящий список товаров для импорта (максимум 100)
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */	
	public static function importProduct($param = null) {
		$model = new Models_Product;
		foreach ($param['products'] as $item) {
			$property = $item['property'];
			unset($item['property']);
			$variants = $item['variants'];
			unset($item['variants']);
			// если нет id, то пробуем его найти по артикулу
			if(empty($item['id'])) {
				$res = DB::query('SELECT id FROM '.PREFIX.'product WHERE code = '.DB::quote($item['code']));
				$id = DB::fetchAssoc($res);
				$item['id'] = $id['id'];
			}
			// если все равно нет id, то добавляем товар
			if(empty($item['id'])) {
				// создаем товар
				DB::query('INSERT INTO '.PREFIX.'product SET '.DB::buildPartQuery($item));
				$prodId = DB::insertId();
				// обрабатываем характеристики
				foreach ($property as $prop) {
					$propId = Property::createProp($prop['name'], $prop['type']);
					Property::createProductStringProp($prop['value'], $prodId, $propId);
					Property::createPropToCatLink($propId, $item['cat_id']);
				}
				// добавляем варианты товара
				foreach ($variants as $variant) {
					DB::query('INSERT INTO '.PREFIX.'product_variant SET '.DB::buildPartQuery($variant));
				}
			} else {
				$item['userProperty'] = $property;
				$item['variants'] = $variants;
				$res = DB::query('SELECT id FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($item['id']));
				if($res = DB::fetchAssoc($res)) {
					//Переконвертируем характеристики для saveUserProperty
					$convertProperty = array();
					foreach ($property as $prop) {
						$propId = Property::createProp($prop['name'], $prop['type']);
						//Преобразуем массив
						$convertProperty[$propId] = array();
						if ( $prop['type'] == 'string' ) $prop['type'] = 'input';
						if ( $prop['type'] == 'assortmentCheckbox' ) $prop['type'] = 'checkbox';
						$convertProperty[$propId]['type'] = $prop['type'];
						$convertProperty[$propId]['temp-1']['active'] = 1;
						$convertProperty[$propId]['temp-1']['prop_id'] = $propId;
						$convertProperty[$propId]['temp-1']['product_id'] = $item['id'];
						$convertProperty[$propId]['temp-1']['name'] = $prop['name'];
						$convertProperty[$propId]['temp-1']['val'] = $prop['value'];
					}
					$item['userProperty'] = $convertProperty;
					$model->updateProduct($item);
				} else {
					unset($item['userProperty']);
					unset($item['variants']);
					// создаем товар
					DB::query('INSERT INTO '.PREFIX.'product SET '.DB::buildPartQuery($item));
					$prodId = DB::insertId();
					// обрабатываем характеристики
					foreach ($property as $prop) {
						$propId = Property::createProp($prop['name'], $prop['type']);
						Property::createProductStringProp($prop['value'], $prodId, $propId);
						Property::createPropToCatLink($propId, $item['cat_id']);
					}
					// добавляем варианты товара
					foreach ($variants as $variant) {
						DB::query('INSERT INTO '.PREFIX.'product_variant SET '.DB::buildPartQuery($variant));
					}
				}
			}
		}
		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления товаров.
	 * 
	 * <code>
	 * $param['products'] = array('1', '2', '3', '4', '5');
	 * $res = $api->run('deleteProduct', $param, true);
	 * viewData($res);
	 * </code>
	 * $param['products'] - массив id для удаления товаров
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function deleteProduct($param = null) {
		$model = new Models_Product;
		foreach ($param['products'] as $item) {
			$model->deleteProduct($item);
		}
		return 'Удаление завершено';
	}

	//------------------------------------------------
	//		  	  ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ                      
	//------------------------------------------------

	/**
	 * Метод для создания дополнительных полей у заказа.
	 * 
	 * <code>
	 * 	$param['data'] = Array(
	 *      Array(
     *         'name' => 'инпут',
     *         'type' => 'input',
     *         'required' => 0,
     *         'active' => 1,
     *     	),
	 *      Array(
     *         'name' => 'селект',
     *         'type' => 'select',
     *         'variants' => Array(
     *             'первый',
     *             'второй',
     *         ),
     *         'required' => 0,
     *         'active' => 1,
     *     	),
	 *      Array(
     *         'name' => 'чекбокс',
     *         'type' => 'checkbox',
     *         'required' => 1,
     *         'active' => 1,
     *     	),
	 *      Array(
     *         'name' => 'радио',
     *         'type' => 'radiobutton',
     *         'variants' => Array(
     *             'первый',
     *             'второй',
     *             'третий',
     *         ),
     *         'required' => 0,
     *         'active' => 0,
     *     	),
	 *      Array(
     *         'name' => 'текст',
     *         'type' => 'textarea',
     *         'required' => 1,
     *         'active' => 1,
     *     	)
	 *  );
	 * $res = $api->run('createCustomFields', $param, true);
	 * viewData($res);
	 * </code>
	 * $param['data'] - массив данных с информацией о дополнительных полях
	 * @param array|null $param - массив описанных параметров
	 * @return string
	 */
	public static function createCustomFields($param = null) {
		MG::setOption('optionalFields', addslashes(serialize($param['data'])));
		return 'Поля сохранены';
	}

}

?>
