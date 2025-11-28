<?php
/**
 * Класс Avito используется для создания и редактирования выгрузок на Avito
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class Avito {
   /**
	* Подготавливает данные для страницы интеграции
	*/
	static function createPage() {
		$rows = DB::query("SELECT COUNT(id) FROM `".PREFIX."avito_cats`;");
		$res = DB::fetchAssoc($rows);
		//MG::loger($res['COUNT(id)']);
		$databaseError = false;

		if ($res['COUNT(id)'] != 445) {
			$databaseError = true;
		}

		$rows = DB::query("SELECT COUNT(id) FROM `".PREFIX."avito_locations`;");
		$res = DB::fetchAssoc($rows);
		//MG::loger($res['COUNT(id)']);

		if ($res['COUNT(id)'] != 4038) {
			$databaseError = true;
		}

		$names = array();
		$rows = DB::query("SELECT `name` FROM `".PREFIX."avito_settings` ORDER BY `edited` DESC");
		while ($row = DB::fetchAssoc($rows)) {
			$names[] = $row['name'];
		}

		$array = array();
		//$res = DB::query('SELECT DISTINCT * FROM '.PREFIX.'category WHERE parent = 0 GROUP BY sort ASC');
		$res = DB::query('SELECT DISTINCT * FROM '.PREFIX.'category WHERE parent = 0 GROUP BY sort');
		while($row = DB::fetchAssoc($res)) {
			$array[] = $row;
		}

		// необходимо для корректного вывода таблицы
		$_SESSION['categoryCountToAdmin'] = 0;
		if (isset($_COOKIE['openedCategoryAdmin'])) {
			unset($_COOKIE['openedCategoryAdmin']);
		}

		$categoryList = MG::get('category')->getPagesSimple($array, 0, 0);
		if($categoryList == '') {
			$categoryList = '<tr><td colspan="6" style="text-align:center;">Категории не найдены</td></tr>';
		}
		unset($_SESSION['categoryCountToAdmin']);

		$rows = DB::query("SELECT `title` FROM `".PREFIX."product` WHERE 1=1 LIMIT 0,1");
		if ($row = DB::fetchAssoc($rows)) {
			$exampleName = $row['title'];
		} else {
			$exampleName = '';
		}

		$regionOptions = '<option value="-5">Введите или выберите регион</option>';
		$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."avito_locations` WHERE `type` = 1");
		while ($row = DB::fetchAssoc($rows)) {
			$regionOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
		}

		include('mg-admin/section/views/integrations/'.basename(__FILE__));
		echo '<script>'.
			'includeJS("'.SITE.'/mg-core/script/admin/category.js");'.
			'includeJS("'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js", "AvitoModule.init");'.
			'</script>';
	}
   /**
	* Получение списка городов Avito
	* @return bool
	*/
	static function getCitys($region){
		$cityOptions = '<option value="-5">Введите или выберите город</option>';

		$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."avito_locations` WHERE `type` = 2 AND `parent_id` = ".DB::quoteInt($region));
		while ($row = DB::fetchAssoc($rows)) {
			$cityOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
		}
		return $cityOptions;
	}
   /**
	* Получение списка метро и районов Avito
	* @return bool
	*/
	static function getSubways($city){
		$subwayOptions = '';
		$districtOptions = '';
		$rows = DB::query("SELECT `id`, `name`, `type` FROM `".PREFIX."avito_locations` WHERE `type` IN (3,4) AND `parent_id` = ".DB::quoteInt($city));
		while ($row = DB::fetchAssoc($rows)) {
			if ($row['type'] == 3) {
				$subwayOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			if ($row['type'] == 4) {
				$districtOptions .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
		}
		return array('subways' => $subwayOptions, 'districts' => $districtOptions);
	}
   /**
	* Записывает категории и локации avito в базу данных
	* @return bool
	*/
	static function updateDB(){
		$dir = URL::getDocumentRoot();

		DB::query("TRUNCATE TABLE `".PREFIX."avito_cats`;");

		$values = file_get_contents('zip://'.$dir.'mg-core'.DS.'script'.DS.'zip'.DS.'avito_cats.zip#cats.txt');
		DB::query("INSERT INTO `".PREFIX."avito_cats` (`id`, `name`, `parent_id`) VALUES ".$values);

		DB::query("TRUNCATE TABLE `".PREFIX."avito_locations`;");

		for ($i=1; $i < 5; $i++) { 
			$values = file_get_contents('zip://'.$dir.'mg-core'.DS.'script'.DS.'zip'.DS.'avito_cats.zip#locations_'.$i.'.txt');
			DB::query("INSERT INTO `".PREFIX."avito_locations` (`id`, `name`, `type`, `parent_id`) VALUES ".$values);
		}

		return true;
	}
   /**
	* Возвращает название avito категории по ID.
	* @param string $id ID avito категории
	* @return string название avito категории
	*/
	static function getCatName($id) {
		
		$res = DB::query("SELECT `name` FROM `".PREFIX."avito_cats` WHERE `id` = ".DB::quoteInt($id));
		$row = DB::fetchAssoc($res);
		return $row['name'];
	}
   /**
	* Возвращает верстку для выбора avito категорий по ID родительской категории.
	* @param int $id ID avito категории
	* @param int $shopCatId ID категории магазина
	* @param string $uploadName название выгрузки
	* @return array массив с версткой селектов и ID возможных выборов
	*/
	static function buildSelects($id, $shopCatId, $uploadName){

		$res = DB::query("SELECT `parent_id`, `name` FROM `".PREFIX."avito_cats` WHERE `id` = ".DB::quote((int)$id));
		$row = DB::fetchAssoc($res);
		$parentId = $row['parent_id'];
		$parentsArray = array();
		$parentsArrayWords = array();
		$html = '';

		if ($parentId != 0) {
			array_push($parentsArray, $parentId);
			array_push($parentsArrayWords, $row['name']);
		}

		while ($parentId != 0) {//иерархия выборов

			$res = DB::query("SELECT `parent_id`, `name` FROM `".PREFIX."avito_cats` WHERE `id` = ".DB::quote((int)$parentId));
			$row = DB::fetchAssoc($res);
			$parentId = $row['parent_id'];

			if ($parentId != 0) {
				array_unshift($parentsArray, $parentId);
				array_unshift($parentsArrayWords, $row['name']);
			}
		}

		//базовый селект
		$res = DB::query("SELECT `id`, `name`
			FROM `".PREFIX."avito_cats` 
			WHERE `parent_id` = 0");
		$html .= '<select class="customCatSelect">';
		$html .= '<option value="-5">Не выбрано</option>';
		while ($row = DB::fetchAssoc($res)) {
			if ($row['id'] > 0) {
				$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
		}

		$html .= '</select>';

		foreach ($parentsArray as $key => $value) {//вторичные селекты

			$res = DB::query("SELECT `id`, `name`
				FROM `".PREFIX."avito_cats` 
				WHERE `parent_id` = ".DB::quote((int)$value));
			$html .= '<select class="customCatSelect">';
			$html .= '<option value="-5">Не выбрано</option>';

			while ($row = DB::fetchAssoc($res)) {
				$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			$html .= '</select>';
			
		}

		if ($id > 0) {//следующий селект
			$res = DB::query("SELECT `id`, `name`
				FROM `".PREFIX."avito_cats` 
				WHERE `parent_id` = ".DB::quote((int)$id));

			if ($res->num_rows > 0) {
				$html .= '<select class="customCatSelect">';
				$html .= '<option value="-5">Не выбрано</option>';

				while ($row = DB::fetchAssoc($res)) {
					$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
				}
				$html .= '</select>';
			}
			
		}

		array_push($parentsArray, $id);

		if (!isset($parentsArrayWords[0])) {$parentsArrayWords[0] = '';}
		if (!isset($parentsArrayWords[1])) {$parentsArrayWords[1] = '';}
		if (!isset($parentsArrayWords[2])) {$parentsArrayWords[2] = '';}
		if (!isset($parentsArrayWords[3])) {$parentsArrayWords[3] = '';}
		
		$additional = self::buildSelectsAdditional($parentsArray, $parentsArrayWords, $shopCatId, $uploadName);

		$catCustomOptions = [];
		if ($shopCatId) {
			$customOptionsSql = 'SELECT `custom_options` '.
				'FROM `'.PREFIX.'avito_settings` '.
				'WHERE `name` = '.DB::quote($uploadName);
			$customOptionsResult = DB::query($customOptionsSql);
			if ($customOptionsRow = DB::fetchAssoc($customOptionsResult)) {
				$customOptions = unserialize(stripslashes($customOptionsRow['custom_options']));
				$catCustomOptions = !empty($customOptions[$shopCatId]) ? $customOptions[$shopCatId] : [];
			}
		}

		$data = array('html' => $html.$additional, 'choices' => $parentsArray, 'customOptions' => $catCustomOptions);

		return $data;
	}
	/**
	* Возвращает верстку для выбора avito категорий.
	* @param array $parentsArray массив выборов (id)
	* @param array $parentsArray массив выборов (названия)
	* @param int $shopCatId id категории магазина
	* @param string $uploadName название выгрузки
	* @return string
	*/
	static function buildSelectsAdditional($parentsArray, $parentsArrayWords, $shopCatId, $uploadName){
		$res = DB::query("SELECT `id`, `name`
			FROM `".PREFIX."avito_cats` 
			WHERE `id` = ".DB::quoteInt($parentsArray[0]));
		$rows = DB::fetchArray($res);
		array_unshift($parentsArrayWords, $rows['name']);
		
		$selects = array();

		if ($parentsArrayWords[0] == 'Транспорт' && $parentsArrayWords[1] == 'Запчасти и аксессуары') {
			$selects[] = array(
				'header' => '',
				'name' => 'TypeId',
				'options' => array(
					'11-618' => 'Запчасти / Для автомобилей / Автосвет',
					'11-619' => 'Запчасти / Для автомобилей / Аккумуляторы',
					'16-827' => 'Запчасти / Для автомобилей / Двигатель / Блок цилиндров, головка, картер',
					'16-828' => 'Запчасти / Для автомобилей / Двигатель / Вакуумная система',
					'16-829' => 'Запчасти / Для автомобилей / Двигатель / Генераторы, стартеры',
					'16-830' => 'Запчасти / Для автомобилей / Двигатель / Двигатель в сборе',
					'16-831' => 'Запчасти / Для автомобилей / Двигатель / Катушка зажигания, свечи, электрика',
					'16-832' => 'Запчасти / Для автомобилей / Двигатель / Клапанная крышка',
					'16-833' => 'Запчасти / Для автомобилей / Двигатель / Коленвал, маховик',
					'16-834' => 'Запчасти / Для автомобилей / Двигатель / Коллекторы',
					'16-835' => 'Запчасти / Для автомобилей / Двигатель / Крепление двигателя',
					'16-836' => 'Запчасти / Для автомобилей / Двигатель / Масляный насос, система смазки',
					'16-837' => 'Запчасти / Для автомобилей / Двигатель / Патрубки вентиляции',
					'16-838' => 'Запчасти / Для автомобилей / Двигатель / Поршни, шатуны, кольца',
					'16-839' => 'Запчасти / Для автомобилей / Двигатель / Приводные ремни, натяжители',
					'16-840' => 'Запчасти / Для автомобилей / Двигатель / Прокладки и ремкомплекты',
					'16-841' => 'Запчасти / Для автомобилей / Двигатель / Ремни, цепи, элементы ГРМ',
					'16-842' => 'Запчасти / Для автомобилей / Двигатель / Турбины, компрессоры',
					'16-843' => 'Запчасти / Для автомобилей / Двигатель / Электродвигатели и компоненты',
					'11-621' => 'Запчасти / Для автомобилей / Запчасти для ТО',
					'16-805' => 'Запчасти / Для автомобилей / Кузов / Балки, лонжероны',
					'16-806' => 'Запчасти / Для автомобилей / Кузов / Бамперы',
					'16-807' => 'Запчасти / Для автомобилей / Кузов / Брызговики',
					'16-808' => 'Запчасти / Для автомобилей / Кузов / Двери',
					'16-809' => 'Запчасти / Для автомобилей / Кузов / Заглушки',
					'16-810' => 'Запчасти / Для автомобилей / Кузов / Замки',
					'16-811' => 'Запчасти / Для автомобилей / Кузов / Защита',
					'16-812' => 'Запчасти / Для автомобилей / Кузов / Зеркала',
					'16-813' => 'Запчасти / Для автомобилей / Кузов / Кабина',
					'16-814' => 'Запчасти / Для автомобилей / Кузов / Капот',
					'16-815' => 'Запчасти / Для автомобилей / Кузов / Крепления',
					'16-816' => 'Запчасти / Для автомобилей / Кузов / Крылья',
					'16-817' => 'Запчасти / Для автомобилей / Кузов / Крыша',
					'16-818' => 'Запчасти / Для автомобилей / Кузов / Крышка, дверь багажника',
					'16-819' => 'Запчасти / Для автомобилей / Кузов / Кузов по частям',
					'16-820' => 'Запчасти / Для автомобилей / Кузов / Кузов целиком',
					'16-821' => 'Запчасти / Для автомобилей / Кузов / Лючок бензобака',
					'16-822' => 'Запчасти / Для автомобилей / Кузов / Молдинги, накладки',
					'16-823' => 'Запчасти / Для автомобилей / Кузов / Пороги',
					'16-824' => 'Запчасти / Для автомобилей / Кузов / Рама',
					'16-825' => 'Запчасти / Для автомобилей / Кузов / Решетка радиатора',
					'16-826' => 'Запчасти / Для автомобилей / Кузов / Стойка кузова',
					'11-623' => 'Запчасти / Для автомобилей / Подвеска',
					'11-624' => 'Запчасти / Для автомобилей / Рулевое управление',
					'11-625' => 'Запчасти / Для автомобилей / Салон',
					'16-521' => 'Запчасти / Для автомобилей / Система охлаждения',
					'11-626' => 'Запчасти / Для автомобилей / Стекла',
					'11-627' => 'Запчасти / Для автомобилей / Топливная и выхлопная системы',
					'11-628' => 'Запчасти / Для автомобилей / Тормозная система',
					'11-629' => 'Запчасти / Для автомобилей / Трансмиссия и привод',
					'11-630' => 'Запчасти / Для автомобилей / Электрооборудование',
					'6-401' => 'Запчасти / Для мототехники',
					'6-406' => 'Запчасти / Для спецтехники',
					'6-411' => 'Запчасти / Для водного транспорта',
					'4-943' => 'Аксессуары',
					'21' => 'GPS-навигаторы',
					'4-942' => 'Автокосметика и автохимия',
					'20' => 'Аудио- и видеотехника',
					'4-964' => 'Багажники и фаркопы',
					'4-963' => 'Инструменты',
					'4-965' => 'Прицепы',
					'11-631' => 'Противоугонные устройства / Автосигнализации',
					'11-632' => 'Противоугонные устройства / Иммобилайзеры',
					'11-633' => 'Противоугонные устройства / Механические блокираторы',
					'11-634' => 'Противоугонные устройства / Спутниковые системы',
					'22' => 'Тюнинг',
					'10-048' => 'Шины, диски и колёса / Шины',
					'10-047' => 'Шины, диски и колёса / Мотошины',
					'10-046' => 'Шины, диски и колёса / Диски',
					'10-045' => 'Шины, диски и колёса / Колёса',
					'10-044' => 'Шины, диски и колёса / Колпаки',
					'6-416' => 'Экипировка',
				)
			);
		}

		if ($parentsArrayWords[1] != 'Не выбрано' && strlen($parentsArrayWords[1]) &&
			($parentsArrayWords[0] == 'Хобби и отдых' && ($parentsArrayWords[1] == 'Охота и рыбалка' || ($parentsArrayWords[2] != 'Не выбрано' && strlen($parentsArrayWords[2])))) ||
			($parentsArrayWords[0] == 'Для дома и дачи'&& ($parentsArrayWords[1] == 'Продукты питания' || $parentsArrayWords[1] == 'Растения' || ($parentsArrayWords[2] != 'Не выбрано' && strlen($parentsArrayWords[2])))) ||
			($parentsArrayWords[0] == 'Личные вещи' && $parentsArrayWords[2] != 'Не выбрано' && strlen($parentsArrayWords[2])) ||
			($parentsArrayWords[0] == 'Транспорт' && $parentsArrayWords[1] == 'Запчасти и аксессуары') 
			) {
			$selects[] = array(
				'header' => 'Вид объявления',
				'name' => 'AdType',
				'options' => array(
					'Товар приобретен на продажу' => 'Товар приобретен на продажу',
					'Товар от производителя' => 'Товар от производителя',
				)
			);
		}

		if ($parentsArrayWords[0] == 'Транспорт' && $parentsArrayWords[1] == 'Мотоциклы и мототехника' && $parentsArrayWords[2] == 'Мотоциклы') {
			$selects[] = array(
				'header' => '',
				'name' => 'MotoType',
				'options' => array(
					'Дорожные' => 'Дорожные',
					'Кастом-байки' => 'Кастом-байки',
					'Кросс и эндуро' => 'Кросс и эндуро',
					'Спортивные' => 'Спортивные',
					'Чопперы' => 'Чопперы',
				)
			);
		}

		if (
			$parentsArrayWords[0] == 'Бытовая электроника' &&
			$parentsArrayWords[1] == 'Телефоны' &&
			$parentsArrayWords[2] == 'Аксессуары'
		) {
			$selects = array_merge($selects, [
				[
					'header' => 'Вид товара',
					'name' => 'ProductsType',
					'options' => [
						'Аккумуляторы' => 'Аккумуляторы',
						'Гарнитуры и наушники' => 'Гарнитуры и наушники',
						'Зарядные устройства' => 'Зарядные устройства',
						'Кабели и адаптеры' => 'Кабели и адаптеры',
						'Модемы и роутеры' => 'Модемы и роутеры',
						'Чехлы и плёнки' => 'Чехлы и плёнки',
						'Запчасти' => 'Запчасти',
					],
				],
			]);
		}

		if (
			$parentsArrayWords[0] == 'Бытовая электроника' &&
			$parentsArrayWords[1] == 'Телефоны' &&
			$parentsArrayWords[2] == 'Мобильные телефоны'
		) {
			$selects = array_merge($selects, [
				[
					'header' => 'Производитель',
					'name' => 'Vendor',
					'options' => [
						'3Q' => '3Q',
						'4Good' => '4Good',
						'A1' => 'A1',
						'AEG' => 'AEG',
						'AGGRESSOR' => 'AGGRESSOR',
						'AGM' => 'AGM',
						'AGmobile' => 'AGmobile',
						'AIEK' => 'AIEK',
						'AKMobile' => 'AKMobile',
						'AMOI' => 'AMOI',
						'ASSISTANT' => 'ASSISTANT',
						'ASTRO' => 'ASTRO',
						'ASUS' => 'ASUS',
						'AWAX' => 'AWAX',
						'AYYA' => 'AYYA',
						'Aceline' => 'Aceline',
						'Acer' => 'Acer',
						'Aimoto' => 'Aimoto',
						'AirOn' => 'AirOn',
						'Alcatel' => 'Alcatel',
						'AllCall' => 'AllCall',
						'AllView' => 'AllView',
						'Amazon' => 'Amazon',
						'AnexTEK' => 'AnexTEK',
						'AnyDATA' => 'AnyDATA',
						'Anycool' => 'Anycool',
						'Apple' => 'Apple',
						'Archos' => 'Archos',
						'Ark' => 'Ark',
						'Atel' => 'Atel',
						'Audiovox' => 'Audiovox',
						'BB' => 'BB',
						'BBK' => 'BBK',
						'BELLFORT' => 'BELLFORT',
						'BELLPERRE' => 'BELLPERRE',
						'BEZKAM' => 'BEZKAM',
						'BLU' => 'BLU',
						'BQ' => 'BQ',
						'BRAVIS' => 'BRAVIS',
						'BURG' => 'BURG',
						'Balmuda' => 'Balmuda',
						'Bedove' => 'Bedove',
						'BenQ' => 'BenQ',
						'BenQ-Siemens' => 'BenQ-Siemens',
						'Benefon' => 'Benefon',
						'Bird' => 'Bird',
						'Black Fox' => 'Black Fox',
						'Black Shark' => 'Black Shark',
						'BlackBerry' => 'BlackBerry',
						'Blackview' => 'Blackview',
						'BlindShell' => 'BlindShell',
						'Bliss' => 'Bliss',
						'Bluboo' => 'Bluboo',
						'Bosch' => 'Bosch',
						'CASIO' => 'CASIO',
						'CORN' => 'CORN',
						'CUBOT' => 'CUBOT',
						'Cagabi' => 'Cagabi',
						'Carbon 1' => 'Carbon 1',
						'Cat' => 'Cat',
						'Caterpillar' => 'Caterpillar',
						'Celkon' => 'Celkon',
						'Changjiang' => 'Changjiang',
						'Chigon' => 'Chigon',
						'China Mobile' => 'China Mobile',
						'CloudFone' => 'CloudFone',
						'Conquest' => 'Conquest',
						'Coolpad' => 'Coolpad',
						'Cruiser' => 'Cruiser',
						'D-JET' => 'D-JET',
						'DELL' => 'DELL',
						'DEXP' => 'DEXP',
						'DIGMA' => 'DIGMA',
						'DNS' => 'DNS',
						'DOOGEE' => 'DOOGEE',
						'Daewoo' => 'Daewoo',
						'Dex' => 'Dex',
						'Discovery' => 'Discovery',
						'Dizo' => 'Dizo',
						'Dluck' => 'Dluck',
						'Dmobo' => 'Dmobo',
						'Donod' => 'Donod',
						'Doopro' => 'Doopro',
						'Dopod' => 'Dopod',
						'Doro' => 'Doro',
						'E&L' => 'E&L',
						'ECOO' => 'ECOO',
						'ELARI' => 'ELARI',
						'EVOLVEO' => 'EVOLVEO',
						'EZRA' => 'EZRA',
						'Elephone' => 'Elephone',
						'Emol' => 'Emol',
						'Energizer' => 'Energizer',
						'Energy Sistem' => 'Energy Sistem',
						'Ergo' => 'Ergo',
						'Ericsson' => 'Ericsson',
						'Essential' => 'Essential',
						'Eten' => 'Eten',
						'Etuline' => 'Etuline',
						'Evelatus' => 'Evelatus',
						'Explay' => 'Explay',
						'F+' => 'F+',
						'FLYCAT' => 'FLYCAT',
						'FREETEL' => 'FREETEL',
						'Fairphone' => 'Fairphone',
						'Fashion' => 'Fashion',
						'Figi' => 'Figi',
						'FinePower' => 'FinePower',
						'Flipkart Billion' => 'Flipkart Billion',
						'Fly' => 'Fly',
						'Fontel' => 'Fontel',
						'FreeYond' => 'FreeYond',
						'Fujitsu' => 'Fujitsu',
						'Fujitsu-Siemens' => 'Fujitsu-Siemens',
						'G-Plus' => 'G-Plus',
						'GEO-MOBILE' => 'GEO-MOBILE',
						'GIGABYTE' => 'GIGABYTE',
						'GOCLEVER' => 'GOCLEVER',
						'GSMK CRYPTOPHONE' => 'GSMK CRYPTOPHONE',
						'GSmart' => 'GSmart',
						'GTStar' => 'GTStar',
						'Garmin' => 'Garmin',
						'Garmin-Asus' => 'Garmin-Asus',
						'GeeksPhone' => 'GeeksPhone',
						'General Mobile' => 'General Mobile',
						'Geotel' => 'Geotel',
						'Gerffins' => 'Gerffins',
						'Getac' => 'Getac',
						'Giga' => 'Giga',
						'Gigaset' => 'Gigaset',
						'Ginza' => 'Ginza',
						'Ginzzu' => 'Ginzzu',
						'Gionee' => 'Gionee',
						'Globex' => 'Globex',
						'GlobusGPS' => 'GlobusGPS',
						'GoldStar' => 'GoldStar',
						'GoldVish' => 'GoldVish',
						'Gome' => 'Gome',
						'Google' => 'Google',
						'Goophone' => 'Goophone',
						'Gooweel' => 'Gooweel',
						'Gresso' => 'Gresso',
						'Gretel' => 'Gretel',
						'Grundig' => 'Grundig',
						'Gspda' => 'Gspda',
						'Gtran' => 'Gtran',
						'H-mobile' => 'H-mobile',
						'HAMMER' => 'HAMMER',
						'HEDY' => 'HEDY',
						'HIPER' => 'HIPER',
						'HOLLEY COMMUNICATIONS' => 'HOLLEY COMMUNICATIONS',
						'HOMTOM' => 'HOMTOM',
						'HONOR' => 'HONOR',
						'HONPhone' => 'HONPhone',
						'HP' => 'HP',
						'HTC' => 'HTC',
						'HUAWEI' => 'HUAWEI',
						'Haier' => 'Haier',
						'Handheld' => 'Handheld',
						'Handyuhr' => 'Handyuhr',
						'Highscreen' => 'Highscreen',
						'Hisense' => 'Hisense',
						'Hitachi' => 'Hitachi',
						'HongKang' => 'HongKang',
						'Hotwav' => 'Hotwav',
						'Hyundai' => 'Hyundai',
						'IIIF150' => 'IIIF150',
						'INOI' => 'INOI',
						'INTEX' => 'INTEX',
						'IQM' => 'IQM',
						'IUNI' => 'IUNI',
						'Impression' => 'Impression',
						'InFocus' => 'InFocus',
						'Infinix' => 'Infinix',
						'Innos' => 'Innos',
						'Innostream' => 'Innostream',
						'Irbis' => 'Irbis',
						'Irulu' => 'Irulu',
						'Itel' => 'Itel',
						'JESY' => 'JESY',
						'JOA Telecom' => 'JOA Telecom',
						'JOY\'S' => 'JOY\'S',
						'Jeep' => 'Jeep',
						'Jiayu' => 'Jiayu',
						'Jinga' => 'Jinga',
						'Jivi' => 'Jivi',
						'Jolla' => 'Jolla',
						'Joy' => 'Joy',
						'Just5' => 'Just5',
						'KECHAODA' => 'KECHAODA',
						'KENEKSI' => 'KENEKSI',
						'KENXINDA' => 'KENXINDA',
						'KGTEL' => 'KGTEL',
						'KINGZONE' => 'KINGZONE',
						'KOOLNEE' => 'KOOLNEE',
						'KREZ' => 'KREZ',
						'KUH' => 'KUH',
						'KYOCERA' => 'KYOCERA',
						'Karbonn' => 'Karbonn',
						'Kazam' => 'Kazam',
						'Kenned' => 'Kenned',
						'Kiano' => 'Kiano',
						'KingSing' => 'KingSing',
						'Kismo' => 'Kismo',
						'Kodak' => 'Kodak',
						'Konka' => 'Konka',
						'Krome' => 'Krome',
						'Kruger&Matz' => 'Kruger&Matz',
						'L8star' => 'L8star',
						'LEXAND' => 'LEXAND',
						'LG' => 'LG',
						'LUXian' => 'LUXian',
						'Land Rover' => 'Land Rover',
						'Lark' => 'Lark',
						'LeEco' => 'LeEco',
						'LeRee' => 'LeRee',
						'Leagoo' => 'Leagoo',
						'Leitz' => 'Leitz',
						'Lenovo' => 'Lenovo',
						'Lumigon' => 'Lumigon',
						'Lumus' => 'Lumus',
						'M-Horse' => 'M-Horse',
						'M-Net' => 'M-Net',
						'MANN' => 'MANN',
						'MAXVI' => 'MAXVI',
						'MELROSE' => 'MELROSE',
						'MIG' => 'MIG',
						'MIJUE' => 'MIJUE',
						'MYSAGA' => 'MYSAGA',
						'Magic' => 'Magic',
						'Manta' => 'Manta',
						'Marshall' => 'Marshall',
						'MaxCom' => 'MaxCom',
						'Maxon' => 'Maxon',
						'Media-Droid' => 'Media-Droid',
						'Mediacom' => 'Mediacom',
						'Meiigoo' => 'Meiigoo',
						'Meizu' => 'Meizu',
						'Merlin' => 'Merlin',
						'Micromax' => 'Micromax',
						'Microsoft' => 'Microsoft',
						'Mitac' => 'Mitac',
						'Mito' => 'Mito',
						'Mitsubishi Electric' => 'Mitsubishi Electric',
						'Mlais' => 'Mlais',
						'Mobiado' => 'Mobiado',
						'Mode1' => 'Mode1',
						'Motorola' => 'Motorola',
						'MyPhone' => 'MyPhone',
						'MyWigo' => 'MyWigo',
						'NEC' => 'NEC',
						'NO.1' => 'NO.1',
						'NOA' => 'NOA',
						'NOUS' => 'NOUS',
						'Neken' => 'Neken',
						'NeoNode' => 'NeoNode',
						'Newgen' => 'Newgen',
						'Newman' => 'Newman',
						'Ninefive' => 'Ninefive',
						'Nobby' => 'Nobby',
						'Nokia' => 'Nokia',
						'Nomi' => 'Nomi',
						'Nomu' => 'Nomu',
						'Nothing' => 'Nothing',
						'NuAns' => 'NuAns',
						'Nubia' => 'Nubia',
						'O2' => 'O2',
						'OINOM' => 'OINOM',
						'OLMIO' => 'OLMIO',
						'OMLOOK' => 'OMLOOK',
						'ONEXT' => 'ONEXT',
						'ONYX BOOX' => 'ONYX BOOX',
						'OPPO' => 'OPPO',
						'OPRIX' => 'OPRIX',
						'ORRO' => 'ORRO',
						'ORSiO' => 'ORSiO',
						'OSCAL' => 'OSCAL',
						'OUKITEL' => 'OUKITEL',
						'OnePlus' => 'OnePlus',
						'Outfone' => 'Outfone',
						'Overmax' => 'Overmax',
						'Oysters' => 'Oysters',
						'PORSCHE' => 'PORSCHE',
						'PPTV' => 'PPTV',
						'Palm' => 'Palm',
						'Panasonic' => 'Panasonic',
						'Pantech-Curitel' => 'Pantech-Curitel',
						'Perfeo' => 'Perfeo',
						'Phicomm' => 'Phicomm',
						'Philips' => 'Philips',
						'Phonemax' => 'Phonemax',
						'Pixelphone' => 'Pixelphone',
						'Pixus' => 'Pixus',
						'Poptel' => 'Poptel',
						'Premier' => 'Premier',
						'Prestigio' => 'Prestigio',
						'Punkt' => 'Punkt',
						'Qtek' => 'Qtek',
						'Qumo' => 'Qumo',
						'RED' => 'RED',
						'RITZVIVA' => 'RITZVIVA',
						'RYTE' => 'RYTE',
						'Rakuten' => 'Rakuten',
						'Ramos' => 'Ramos',
						'RangerFone' => 'RangerFone',
						'Raydget' => 'Raydget',
						'Razer' => 'Razer',
						'Reeder' => 'Reeder',
						'Rezone' => 'Rezone',
						'Ritmix' => 'Ritmix',
						'Rivotek' => 'Rivotek',
						'Rolsen' => 'Rolsen',
						'Ross&Moor' => 'Ross&Moor',
						'Rover PC' => 'Rover PC',
						'RugGear' => 'RugGear',
						'RuiTel' => 'RuiTel',
						'Runbo' => 'Runbo',
						'Runfast' => 'Runfast',
						'S Mobile' => 'S Mobile',
						'S-TELL' => 'S-TELL',
						'SANTIN' => 'SANTIN',
						'SATREND' => 'SATREND',
						'SENSEIT' => 'SENSEIT',
						'SHTURMANN' => 'SHTURMANN',
						'SK' => 'SK',
						'SMS Technology Australia' => 'SMS Technology Australia',
						'SNAMI' => 'SNAMI',
						'SUGAR' => 'SUGAR',
						'Sagem' => 'Sagem',
						'Samsung' => 'Samsung',
						'Sanyo' => 'Sanyo',
						'Seals' => 'Seals',
						'SeeMax' => 'SeeMax',
						'Seekwood' => 'Seekwood',
						'Sencor' => 'Sencor',
						'Sendo' => 'Sendo',
						'SerteC' => 'SerteC',
						'Servo' => 'Servo',
						'Sewon' => 'Sewon',
						'Sharp' => 'Sharp',
						'Siemens' => 'Siemens',
						'Sierra Wireless' => 'Sierra Wireless',
						'Sigma mobile' => 'Sigma mobile',
						'Siswoo' => 'Siswoo',
						'Sitronics' => 'Sitronics',
						'Skylink' => 'Skylink',
						'Skyvox' => 'Skyvox',
						'Smartisan' => 'Smartisan',
						'Smarty' => 'Smarty',
						'Snail' => 'Snail',
						'Snopow' => 'Snopow',
						'SoftBank' => 'SoftBank',
						'Song' => 'Song',
						'Sonim' => 'Sonim',
						'Sony' => 'Sony',
						'Sony Ericsson' => 'Sony Ericsson',
						'Soutec' => 'Soutec',
						'Soyes' => 'Soyes',
						'Stark' => 'Stark',
						'Starway' => 'Starway',
						'Strike' => 'Strike',
						'Sunwind' => 'Sunwind',
						'Synertek' => 'Synertek',
						'T-Mobile' => 'T-Mobile',
						'TAG Heuer' => 'TAG Heuer',
						'TCL' => 'TCL',
						'TECNO' => 'TECNO',
						'TELEFUNKEN' => 'TELEFUNKEN',
						'TP-LINK' => 'TP-LINK',
						'TWINSCOM' => 'TWINSCOM',
						'TechnoPhone' => 'TechnoPhone',
						'Tel.me.' => 'Tel.me.',
						'Tele2' => 'Tele2',
						'Tengda' => 'Tengda',
						'Teracube' => 'Teracube',
						'Tesla' => 'Tesla',
						'ThL' => 'ThL',
						'The Q' => 'The Q',
						'Tonino Lamborghini' => 'Tonino Lamborghini',
						'Toplux' => 'Toplux',
						'Torex' => 'Torex',
						'Torson' => 'Torson',
						'Toshi' => 'Toshi',
						'Toshiba' => 'Toshiba',
						'Treelogic' => 'Treelogic',
						'Turbo' => 'Turbo',
						'TurboPad' => 'TurboPad',
						'TwinMOS' => 'TwinMOS',
						'Typhoon' => 'Typhoon',
						'ULCOOL' => 'ULCOOL',
						'UMIDIGI' => 'UMIDIGI',
						'UMi' => 'UMi',
						'UNIWA' => 'UNIWA',
						'Ubiquam' => 'Ubiquam',
						'Uhans' => 'Uhans',
						'Uhappy' => 'Uhappy',
						'Ulefone' => 'Ulefone',
						'Ulysse Nardin' => 'Ulysse Nardin',
						'Unihertz' => 'Unihertz',
						'Upnone' => 'Upnone',
						'VEON' => 'VEON',
						'VERTEX' => 'VERTEX',
						'VIWA' => 'VIWA',
						'VK Corporation' => 'VK Corporation',
						'Vargo' => 'Vargo',
						'Venso' => 'Venso',
						'Versace' => 'Versace',
						'Vertu' => 'Vertu',
						'Viewsonic' => 'Viewsonic',
						'Viking' => 'Viking',
						'Vkworld' => 'Vkworld',
						'Vodafone' => 'Vodafone',
						'Voxtel' => 'Voxtel',
						'Vsmart' => 'Vsmart',
						'WEXLER' => 'WEXLER',
						'Watch Mobile' => 'Watch Mobile',
						'Watchtech' => 'Watchtech',
						'Wieppo' => 'Wieppo',
						'Wigor' => 'Wigor',
						'Wiko' => 'Wiko',
						'Wileyfox' => 'Wileyfox',
						'Wings' => 'Wings',
						'Withus' => 'Withus',
						'X-TIGI' => 'X-TIGI',
						'XGODY' => 'XGODY',
						'Xcute' => 'Xcute',
						'Xiaomi' => 'Xiaomi',
						'YEZZ' => 'YEZZ',
						'Yeemi' => 'Yeemi',
						'Yota' => 'Yota',
						'ZIFRO' => 'ZIFRO',
						'ZOIJA' => 'ZOIJA',
						'ZTE' => 'ZTE',
						'ZUK' => 'ZUK',
						'Zakang' => 'Zakang',
						'Zeeker' => 'Zeeker',
						'Zetta' => 'Zetta',
						'Zoji' => 'Zoji',
						'Zopo' => 'Zopo',
						'babyPhone' => 'babyPhone',
						'bb-mobile' => 'bb-mobile',
						'eNOL' => 'eNOL',
						'effire' => 'effire',
						'i-Mate' => 'i-Mate',
						'i-Mobile' => 'i-Mobile',
						'iFcane' => 'iFcane',
						'iLA' => 'iLA',
						'iMAN' => 'iMAN',
						'iNew' => 'iNew',
						'iOcean' => 'iOcean',
						'iRu' => 'iRu',
						'iTravel' => 'iTravel',
						'iconBIT' => 'iconBIT',
						'realme' => 'realme',
						'teXet' => 'teXet',
						'tokky' => 'tokky',
						'vernee' => 'vernee',
						'vivo' => 'vivo',
						'xDevice' => 'xDevice',
						'Билайн' => 'Билайн',
						'Другое' => 'Другое',
						'Кнопка жизни' => 'Кнопка жизни',
						'МТС' => 'МТС',
						'МегаФон' => 'МегаФон',
						'Мотив' => 'Мотив',
						'Яндекс' => 'Яндекс',
					],
				],
			]);
		}

		if ($parentsArrayWords[0] == 'Бытовая электроника' && $parentsArrayWords[1] == 'Телефоны' && $parentsArrayWords[2] == 'Мобильные телефоны') {
			$props = [];
			$rows = DB::query('SELECT `id`, `name` FROM `'.PREFIX.'property` ORDER BY `sort` ASC');
			while ($row = DB::fetchAssoc($rows)) {
				$props[$row['id']] = $row['name'];
			}
			$selects = array_merge($selects, [
				[
					'header' => 'Модель',
					'name' => 'Model',
					'options' => $props
				],
				[
					'header' => 'Встроенная память',
					'name' => 'MemorySize',
					'options' => $props
				],
				[
					'header' => 'Цвет',
					'name' => 'Color',
					'options' => $props
				],
				[
					'header' => 'Оперативная память',
					'name' => 'RamSize',
					'options' => $props
				],
			]);
		}

		if ($parentsArrayWords[3] != '' && $parentsArrayWords[3] != 'Другое' && $parentsArrayWords[3] != 'Пиджаки и костюмы' && $parentsArrayWords[3] != 'Шапки, варежки, шарфы' &&
			($parentsArrayWords[2] == 'Женская одежда' || $parentsArrayWords[2] == 'Мужская одежда'	|| $parentsArrayWords[2] == 'Для девочек' || $parentsArrayWords[2] == 'Для мальчиков')
			) {
			$props = array();
			$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` WHERE `type` IN ('assortmentCheckBox', 'string', 'size') ORDER BY `sort` asc");
			while ($row = DB::fetchAssoc($rows)) {
				$props[$row['id']] = $row['name'];
			}
			$selects[] = array(
				'header' => 'Выберите характеристику с размером одежды или обуви',
				'name' => 'Size',
				'options' => $props
			);
		}

    if ($parentsArrayWords[0] == 'Для дома и дачи' && $parentsArrayWords[1] == 'Рмонт и строительство'	|| $parentsArrayWords[2] == 'Стройматериалы')
     {
       $selects[] = array(
         'header' => 'Подтип товара',
         'name' => 'GoodsSubType',
         'options' => array(
           'Изоляция' => 'Изоляция',
           'Крепёж' => 'Крепёж',
           'Металлопрокат' => 'Металлопрокат',
           'Общестроительные материалы' => 'Общестроительные материалы',
           'Отделка' => 'Отделка',
           'Пиломатериалы' => 'Пиломатериалы',
           'Строительные смеси' => 'Строительные смеси',
           'Строительство стен' => 'Строительство стен',
           'Электрика' => 'Электрика',
           'Лаки и краски' => 'Лаки и краски',
           'Листовые материалы' => 'Листовые материалы',
           'Кровля и водосток' => 'Кровля и водосток',
           'Другое' => 'Другое',
         )
       );
    }

	if ($parentsArrayWords[0] == 'Бытовая электроника' && $parentsArrayWords[1] == 'Телефоны') {
		$selects[] = array(
			'header' => 'Тип товара',
			'name' => 'Condition',
			'options' => array(
				'Новое' => 'Новое',
				'Отличное' => 'Отличное',
				'Хорошее' => 'Хорошее',
				'Удовлетворительное' => 'Удовлетворительное',
				'Требуется ремонт' => 'Требуется ремонт',
				'Б/у' => 'Б/у',
			)
		);
	} elseif(
		$parentsArrayWords[0] == 'Для бизнеса' && $parentsArrayWords[1] == 'Готовый бизнес' ||
		$parentsArrayWords[0] == 'Предложение услуг' ||
		($parentsArrayWords[0] == 'Животные' && $parentsArrayWords[1] != 'Товары для животных') ||
		($parentsArrayWords[0] == 'Для дома и дачи' && ($parentsArrayWords[1] == 'Продукты питания' || $parentsArrayWords[1] == 'Растения')) ||
		($parentsArrayWords[0] == 'Транспорт' && $parentsArrayWords[1] == 'Автомобили') ||
		($parentsArrayWords[0] == 'Хобби и отдых' && $parentsArrayWords[1] == 'Билеты и путешествия') ||
		$parentsArrayWords[0] == 'Недвижимость'
	) {
		// Не добавляем Condition
	} elseif (
		$parentsArrayWords[0] == 'Транспорт' && 
		$parentsArrayWords[1] != 'Водный транспорт' &&
		$parentsArrayWords[1] != 'Запчасти и аксессуары'
	) {
		$selects[] = [
			'header' => 'Тип товара',
			'name' => 'Condition',
			'options' => [
				'Новое' => 'Новое',
				'Б/у' => 'Б/у',
				'На запчасти' => 'На запчасти'
			],
		];
	} elseif ($parentsArrayWords[0] == 'Личные вещи' && $parentsArrayWords[1] == 'Одежда, обувь, аксессуары') {
		$selects[] = [
			'header' => 'Тип товара',
			'name' => 'Condition',
			'options' => [
				'Новое с биркой' => 'Новое с биркой',
				'Отличное' => 'Отличное',
				'Хорошее' => 'Хорошее',
				'Удовлетворительное' => 'Удовлетворительное',
			],
		];
	} elseif (
		$parentsArrayWords[0] == 'Личные вещи' &&
		($parentsArrayWords[1] == 'Часы и украшения' || $parentsArrayWords[1] == 'Красота и здоровье')
	) {
		$selects[] = [
			'header' => 'Тип товара',
			'name' => 'Condition',
			'options' => [
				'Новое' => 'Новый',
				'Б/у' => 'Б/у',
			],
		];
	} else {
		$selects[] = [
			'header' => 'Тип товара',
			'name' => 'Condition',
			'options' => [
				'Новое' => 'Новое',
				'Б/у' => 'Б/у',
			],
		];
	}

		if (empty($selects)) {
			return '';
		}
		else{
			$res = DB::query("SELECT `additional`
				FROM `".PREFIX."avito_settings` 
				WHERE `name` = ".DB::quote($uploadName));
			$row = DB::fetchArray($res);
			$additional = unserialize(stripslashes($row['additional']));

			$html = '';
			foreach ($selects as $select) {
				if ($select['header'] != '') {
					$html .= '<p>'.$select['header'].':</p>';
				}
				$html .= '<select class="additionalCatSelect" paramName="'.$select['name'].'">';
				foreach ($select['options'] as $key => $value) {
					$selected = '';
					if (isset($additional[$shopCatId][$select['name']]) && $key == $additional[$shopCatId][$select['name']]) {
						$selected = ' selected';
					}
					$html .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
				}
				$html .= '</select>';
			}
			
			return $html;
		}

	}
   /**
	* Возвращает список соответствий avito категорий и категорий магазина по названию выгрузки.
	* @param string $name название выгрузки
	* @return array массив соответствий ID категорий
	*/
	static function getCats($name){
		$rows = DB::query("SELECT `cats` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		$res = DB::fetchAssoc($rows);

		$cats = unserialize(stripslashes($res['cats']));

		return $cats;
	}
   /**
	* Применяет соответствующую avito категорию ко всем вложенным категориям магазина.
	* @param string $shopId ID категории магазина
	* @param string $avitoId ID категории avito
	* @param string $name название выгрузки
	* @return bool
	*/
	static function updateCatsRecurs($shopId, $avitoId, $name){
		$rows = DB::query("SELECT `cats`, `additional`, `custom_options` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		$res = DB::fetchAssoc($rows);

		$cats = unserialize(stripslashes($res['cats']));
		$additional = unserialize(stripslashes($res['additional']));
		$customOptions = unserialize(stripslashes($res['custom_options']));

		$model = new Category;
		$catIds = $model->getCategoryList($shopId);

		foreach ($catIds as $key => $value) {
			$cats[$value] = $avitoId;

			if (empty($additional[$shopId])) {
				unset($additional[$value]);
			}
			else{
				$additional[$value] = $additional[$shopId];
			}

			if (empty($customOptions[$shopId])) {
				unset($customOptions[$value]);
			} else {
				$customOptions[$value] = $customOptions[$shopId];
			}
		}
		$cats = addslashes(serialize($cats));
		$additional = addslashes(serialize($additional));
		$customOptions = addslashes(serialize($customOptions));

		DB::query("UPDATE `".PREFIX."avito_settings` SET `cats`=".DB::quote($cats).", `additional`=".DB::quote($additional).", `custom_options`=".DB::quote($customOptions)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Сохраняет соответствие avito категории и категории магазина.
	* @param int $shopId ID категории магазина
	* @param int $avitoId ID категории avito
	* @param string $name название выгрузки
	* @param array $addArr массив дополнительных параметров
	* @return bool
	*/
	static function saveCat($shopId, $avitoId, $name, $addArr, $newCustomOptions = []) {
		$rows = DB::query("SELECT `cats`, `additional`, `custom_options` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		$res = DB::fetchAssoc($rows);
		$cats = unserialize(stripslashes($res['cats']));
		$additional = unserialize(stripslashes($res['additional']));
		$customOptions = unserialize(stripslashes($res['custom_options']));
		$cats[$shopId] = $avitoId;

		if (empty($addArr)) {
			unset($additional[$shopId]);
		}
		else{
			$availableAdditional = [];
			foreach ($addArr as $value) {
				if (!$value['paramName'] || !$value['val']) {continue;}
				$additional[$shopId][$value['paramName']] = $value['val'];
				$availableAdditional[] = $value['paramName'];
			}
			foreach ($additional[$shopId] as $additionalKey => $additionalValue) {
				if (!in_array($additionalKey, $availableAdditional)) {
					unset($additional[$shopId][$additionalKey]);
				}
			}
		}
		
		$cats = addslashes(serialize($cats));
		$additional = addslashes(serialize($additional));
		$customOptions[$shopId] = $newCustomOptions;
		$customOptions = addslashes(serialize($customOptions));

		$updateAvitoSettingsSql = 'UPDATE `'.PREFIX.'avito_settings` '.
			'SET `cats` = '.DB::quote($cats).', '.
			'`additional` = '.DB::quote($additional).', '.
			'`custom_options` = '.DB::quote($customOptions).' '.
			'WHERE `name` = '.DB::quote($name);

		$result = DB::query($updateAvitoSettingsSql);

		return $result;
	}
   /**
	* Создает новую выгрузку.
	* @param string $name название выгрузки
	* @return string|bool название выгрузки, если оно уникально или false если повторяется
	*/
	static function newTab($name) {
		$dbRes = DB::query("SELECT `name` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));

		if(!$row = DB::fetchArray($dbRes)) {
			DB::query("INSERT IGNORE INTO  `".PREFIX."avito_settings` (`name`) VALUES (".DB::quote($name).")");
			return $name;
		}

		return false;
	}
   /**
	* Сохраняет настройки выгрузки.
	* @param string $name название выгрузки
	* @param array $data массив с данными для сохранения
	* @return bool
	*/
	static function saveTab($name, $data) {
		$data['manager'] = mb_substr($data['manager'], 0, 39);
		if ($data['subway'] < 0) {unset($data['subway']);}
		if ($data['district'] < 0) {unset($data['district']);}

		$data = addslashes(serialize($data));

		DB::query("UPDATE `".PREFIX."avito_settings` SET `settings`=".DB::quote($data)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Получает настройки выгрузки.
	* @param string $name название выгрузки
	* @return array массив с данными выгрузки
	*/
	static function getTab($name) {
		$rows = DB::query("SELECT `settings` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		$res = DB::fetchAssoc($rows);

		$options = unserialize(stripslashes($res['settings']));
		$options['ignoreProducts'] = self::getRelated($options['ignoreProducts']);

		$options['cityOptions'] = '<option value="-5">Введите или выберите город</option>';
		$options['subwayOptions'] = '';
		$options['districtOptions'] = '';
		$rows = DB::query("SELECT `id`, `name`, `type` FROM `".PREFIX."avito_locations` WHERE 
			(`type` = 2 AND `parent_id` = ".DB::quoteInt(isset($options['region'])?$options['region']:null).") OR 
			(`type` IN (3,4) AND `parent_id` = ".DB::quoteInt(isset($options['city'])?$options['city']:null).")");
		while ($row = DB::fetchAssoc($rows)) {
			if ($row['type'] == 2) {
				$options['cityOptions'] .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			if ($row['type'] == 3) {
				$options['subwayOptions'] .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			if ($row['type'] == 4) {
				$options['districtOptions'] .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
		}
		if ($options['cityOptions'] == '<option value="-5">Введите или выберите город</option>') {
			$options['cityOptions'] = '<option value="-5">Для выбора города выберите регион</option>';
		}
		return $options;
	}
   /**
	* Удаляет выгрузку.
	* @param string $name название выгрузки
	* @return bool
	*/
	static function deleteTab($name) {
		DB::query("DELETE FROM `".PREFIX."avito_settings` WHERE `name`=".DB::quote($name));
		
		return true;
	}
   /**
	* Возвращает данные игнорируемых товаров.
	* @param string $option артикулы товаров
	* @return array массив с данными игнорируемых товаров
	*/
	static function getRelated($option) {
		$stringRelated = ' null';
	    $sortRelated = array();
	    if (!empty($option)) {
	      foreach (explode(',', $option) as $item) {
	        $stringRelated .= ','.DB::quote($item);
	        if (!empty($item)) {
	          $sortRelated[$item] = $item;
	        }
	      }
	      $stringRelated = substr($stringRelated, 1);
	    }

	    $res = DB::query('
	      SELECT  CONCAT(c.parent_url,c.url) as category_url,
	        p.url as product_url, p.id, p.image_url,p.price_course as price,p.title,p.code
	      FROM `'.PREFIX.'product` p
	        LEFT JOIN `'.PREFIX.'category` c
	        ON c.id = p.cat_id
	        LEFT JOIN `'.PREFIX.'product_variant` AS pv
	        ON pv.product_id = p.id
	      WHERE p.code IN ('.$stringRelated.') OR pv.code IN ('.$stringRelated.')');

	    while ($row = DB::fetchAssoc($res)) {
	      $img = explode('|', $row['image_url']);
	      $row['image_url'] = $img[0];
	      $sortRelated[$row['code']] = $row;
	    }
	    $productsRelated = array();

	    if (!empty($sortRelated)) {
	      foreach ($sortRelated as $item) {
	        if (is_array($item)) {
	          $item['image_url'] = mgImageProductPath($item['image_url'], $item['id'], 'small');
	          $productsRelated[] = $item;
	        }
	      }
	    }
	    
	    return $productsRelated;
	}
	/**
	* Приводит характеристики к нормальному виду
	* @param array $thisUserFields массив с характеристиками
	* @return array массив с характеристиками
	*/
	static function convertProps($thisUserFields) {
		$result = array();
		if (!empty($thisUserFields)) {
			foreach ($thisUserFields as $key => $value) {
				$tmp = array();

				$name = explode('[', $value['name']);
				$name = $name[0];

				if (strlen($name) < 1) {continue;}

				switch ($value['type']) {
					case 'string':
						if (strlen($value['data'][0]['name']) < 1) {break;}
						$result['string'][$value['prop_id']] = array(
							'id' => $value['prop_id'],
							'name' => $name,
							'unit' => $value['unit'],
							'active' => $value['activity'],
							'value' => $value['data'][0]['name']
						);
						break;
					
					case 'assortmentCheckBox':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[] = $v['name'];
							}
						}
						if (count($tmp) > 0) {
							$result['string'][$value['prop_id']] = array(
								'id' => $value['prop_id'],
								'name' => $name,
								'unit' => $value['unit'],
								'active' => $value['activity'],
								'value' => implode(', ', $tmp)
							);
						}
						break;
					
					case 'color':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[$v['prop_data_id']] = array(
									'id' => $value['prop_id'],
									'name' => $name,
									'active' => $value['activity'],
									'value' => $v['name']
								);
							}
						}
						if (count($tmp) > 0) {
							$result['color'] = $tmp;
						}
						break;
					
					case 'size':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[$v['prop_data_id']] = array(
									'id' => $value['prop_id'],
									'name' => $name,
									'value' => $v['name'],
									'unit' => $value['unit'],
									'active' => $value['activity']
								);
							}
						}
						if (count($tmp) > 0) {
							$result['size'] = $tmp;
						}
						break;
					default:
						# code...
						break;
				}
			}
			// mg::loger($result);
			return $result;
		}
		return false;
	}
	/**
	* Конвертирует цену в рубли.
	* @param array $rates массив соотношений валют
	* @param float $price цена товара
	* @param string $currency валюта товара
	* @return float цена товара в рублях
	*/
	static function convertToRub($rates, $price, $currency) {
		$iso = false;
		if (array_key_exists('RUB', $rates)) {
			$iso = 'RUB';
		}
		if (array_key_exists('RUR', $rates)) {
			$iso = 'RUR';
		}
		if ($iso && array_key_exists($currency, $rates)) {
			return (float)round($price*$rates[$currency]/$rates[$iso],2);
		}
 		return $price;
	}

	private static function applyMargin($price, $margin) {
        if (!$price) {
            return 0;
        }
        $isPercent = strpos($margin, '%');
        $isSubtraction = strpos($margin, '-') !== false;
        // Да, оказывается это лучший способ извлечь число из строки
        // По крайней мере, в данном случае
        $marginValue = floatVal(filter_var(str_replace(',', '.', $margin), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
        if ($isPercent) {
            $price = $price + $price * $marginValue / 100;
        } else {
            $price = $price + $marginValue;
        }
        return $price;
	}

	/**
	* Возвращает массив названий категорий.
	* @param array $cats массив соотношения категорий магазина и авито
	* @param int $catId id категории товара
	* @param array $allCatsArr массив со всеми категориями авито
	* @return array
	*/
	static function fixCatNames($cats, $catId, $allCatsArr, $productTitle) {

		$parent = $allCatsArr[$cats[$catId]]['parent'];
		$catnames = array($allCatsArr[$cats[$catId]]['name']);
		
		while ($parent != 0) {
			array_unshift($catnames, $allCatsArr[$parent]['name']);
			$parent = $allCatsArr[$parent]['parent'];
		}

		$result = array();

		if ($catnames[0] == 'Для дома и дачи' && $catnames[1] == 'Бытовая техника') {
			$result['Category'] = $catnames[1];
			$result['GoodsType'] = $catnames[3];
		}
		elseif ($catnames[0] == 'Личные вещи' && ($catnames[1] == 'Одежда, обувь, аксессуары' || $catnames[1] == 'Детская одежда и обувь')) {
			$result['Category'] = $catnames[1];
			$result['GoodsType'] = $catnames[2];
			$result['Apparel'] = $catnames[3];
		}
		elseif ($catnames[0] == 'Транспорт' && ($catnames[1] == 'Мотоциклы и мототехника' || $catnames[1] == 'Водный транспорт')) {
			$result['Category'] = $catnames[1];
			$result['VehicleType'] = $catnames[2];
		}
		elseif ($catnames[0] == 'Предложение услуг') {
			$result['ServiceType'] = $catnames[1];
			$result['ServiceSubype'] = $catnames[2];
		}
		elseif ($catnames[0] == 'Хобби и отдых' && $catnames[1] == 'Велосипеды') {
			$result['Category'] = $catnames[1];
			$result['VehicleType'] = $catnames[2];
		}
		elseif ($catnames[0] == 'Животные' && ($catnames[1] == 'Собаки' || $catnames[1] == 'Кошки')) {
			$result['Category'] = $catnames[1];
			$result['Breed'] = $catnames[2];
		}
		elseif ($catnames[0] == 'Бытовая электроника' && $catnames[1] == 'Аудио и видео' && $catnames[2] == 'Телевизоры и проекторы') {
			$result['Category'] = $catnames[1];
			$result['GoodsType'] = $catnames[2];
			$result['ProductsType'] = 'Другое';
			if(strpos(mb_strtolower($productTitle), 'телевизор')!==false){
				$result['ProductsType'] = 'Телевизоры';
		  }
			if(strpos(mb_strtolower($productTitle), 'проектор')!==false){
				$result['ProductsType'] = 'Проекторы';
			}
		}
		else {
			$result['Category'] = $catnames[1];
			$result['GoodsType'] = end($catnames);
		}

		return $result;
	}
   /**
	* Создает результат выгрузки.
	* @param string $name название выгрузки
	* @return string результат выгрузки
	*/
	static function constructXML($name){

		$rows = DB::query("SELECT `settings` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
		$res = DB::fetchAssoc($rows);

		if (isset($res['settings']) && strlen($res['settings']) > 1) {

			$ds = DS;
			$options = unserialize(stripslashes($res['settings']));
			$rows = DB::query("SELECT `cats`, `additional`, `custom_options` FROM `".PREFIX."avito_settings` WHERE `name` = ".DB::quote($name));
			if ($row = DB::fetchAssoc($rows)) {
				$res = $row;
			}
			$cats = unserialize(stripslashes($res['cats']));
			$cats = array_filter($cats);
			$additional = unserialize(stripslashes($res['additional']));
			$customOptions = unserialize(stripslashes($res['custom_options']));

			$tmpLocArr = array($options['region'], $options['city']);

			if (!empty($options['subway'])) {
				$tmpLocArr[] = $options['subway'];
			}

			if (!empty($options['district'])) {
				$tmpLocArr[] = $options['district'];
			}

			$rows = DB::query("SELECT `name`, `type` FROM `".PREFIX."avito_locations` WHERE `id` IN (".DB::quoteIN($tmpLocArr).")");
			while ($row = DB::fetchAssoc($rows)) {
				if ($row['type'] == 1) {
					$options['region'] = $row['name'];
				}
				if ($row['type'] == 2) {
					$options['city'] = $row['name'];
				}
				if ($row['type'] == 3) {
					$options['subway'] = $row['name'];
				}
				if ($row['type'] == 4) {
					$options['district'] = $row['name'];
				}
			}

			if ($options['city'] == 'Москва' || $options['city'] == 'Санкт-Петербург') {
				unset($options['city']);
			}

			$arrAdress = [$options['region'], $options['city'], $options['subway'], $options['district'], $options['exact']];

			for ($i=0; $i <= count($arrAdress); $i++) { 
				if(empty($arrAdress[$i])){
					unset($arrAdress[$i]);
				}
			}

			$options['address'] = implode(', ', $arrAdress);

			$rates = MG::getSetting('dbCurrRates');
			if (empty($rates)) {
				$rates = MG::getSetting('currencyRate');
			}

			$xml = new XMLWriter();
			$xml->openMemory();
			$xml->startDocument('1.0', 'UTF-8');
			$xml->setIndent(true);
			$xml->startElement('Ads');///////////////////////////////////////////////////////////////////////////////////////// start xml
			$xml->writeAttribute('target', 'Avito.ru');
			$xml->writeAttribute('formatVersion', '3');


			if(!empty($cats) || !empty($options['ignoreProducts']) || $options['inactiveToo'] == "false"){
				$whereClauseParts = [];
				if (!empty($cats)) {
					$whereClauseParts[] = '`cat_id` IN ('.DB::quoteIN(array_keys($cats)).')';
				}
	
				if (!empty($options['ignoreProducts'])) {
					$ignored = explode(',', $options['ignoreProducts']);
					$whereClauseParts[] = '`code` NOT IN ('.DB::quoteIN($ignored).')';
				}

				if ($options['inactiveToo'] == "false") {
					$whereClauseParts[] = '`activity` = 1';
				}

				if ($whereClauseParts) {
					$filter = 'WHERE '.implode(' AND ', $whereClauseParts);
				}
			}

			$allCatsArr = array();
			$res = DB::query("SELECT `id`, `parent_id`, `name` FROM `".PREFIX."avito_cats`");
			while ($row = DB::fetchAssoc($res)) {
				$allCatsArr[$row['id']] = array('name' => $row['name'], 'parent' => $row['parent_id']);
			}

			$model = new Models_Product;
			$productsSql = 'SELECT `id`, `code`, `count` '.
				'FROM `'.PREFIX.'product` '.$filter;

			$res = DB::query($productsSql);

			while ($row = DB::fetchAssoc($res)) {

				set_time_limit(30);

				$product = $model->getProduct($row['id'], true, true);
				$variants = $model->getVariants($row['id']);

				if (!empty($variants)) {
					$tmp = reset($variants);
					$product['code'] = $tmp['code'];
					$product['count'] = $tmp['count'];
					$product['price'] = $tmp['price'];
					$product['old_price'] = $tmp['old_price'];
					$product['price_course'] = $tmp['price_course'];
					$product['currency_iso'] = $tmp['currency_iso'];
					$product['weight'] = $tmp['weight'];
				}
				// Если включено "Использовать короткое описане" и оно не пустое, то выводи его вмето обычного описания
				if(isset($options['shortDescription']) && $options['shortDescription'] == 'true' && !empty($product['short_description'])){
					$product['description'] = $product['short_description'];
				}
				if ($options['useNull'] == 'false') {
					if (is_array($variants) && !empty($variants)) {
						$empty = true;
						foreach ($variants as $variant) {
							if ($variant['count'] != 0) {
								$empty = false;
							}
						}
						if ($empty) {
							continue;
						}
					}
					else{
						if ((int)$product['count'] == 0) {
							continue;
						}
					}
				}

				$product['description'] = str_replace('&nbsp;', ' ', $product['description']);
				$product['description'] = html_entity_decode(htmlspecialchars_decode($product['description']));
				if ($options['useCdata'] == 'true') {					
					$product['description'] = strip_tags($product['description'], '<p> <br> <strong> <em> <ul> <ol> <li>');
				} else {
					$product['description'] = strip_tags($product['description']);
				}
				$product['description'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['description']);
				$product['description'] = preg_replace('/[[:cntrl:]]/', '', $product['description']);
				$product['description'] = preg_replace('/\s+/', ' ',$product['description']);
				$product['description'] = mb_substr(trim($product['description']), 0,2975, 'utf-8');

				$product['title'] = str_replace('&nbsp;', ' ', $product['title']);
				$product['title'] = strip_tags($product['title']);
				$product['title'] = html_entity_decode(htmlspecialchars_decode($product['title']));
				$product['title'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['title']);
				$product['title'] = preg_replace('/[[:cntrl:]]/', '', $product['title']);
				$product['title'] = str_replace(array("~", "<", ">"), "", $product['title']);
				$product['title'] = strip_tags($product['title']);
				$product['title'] = preg_replace('/\s+/', ' ',$product['title']);
				$product['title'] = mb_substr(trim($product['title']), 0,49, 'utf-8');

				$xml->startElement('Ad');/////////////////////////////////////////////////////////////////////////////////////// start product

				$xml->startElement('Id');
				$xml->text($row['id']);
				$xml->endElement();

				$xml->startElement('Title');
				$xml->text($product['title']);
				$xml->endElement();

				$product['price'] = self::convertToRub($rates, $product['price'], $product['currency_iso']);
				$product['price'] = round($product['price']);
				if (!empty($options['avitoMargin'])) {
					$product['price'] = self::applyMargin($product['price'], $options['avitoMargin']);
				}
				$xml->startElement('Price');
				$xml->text($product['price']);
				$xml->endElement();

				$xml->startElement('Description');
				$xml->text('<![CDATA[ '.$product['description'].' ]]>');
				$xml->endElement();


				$catArr = self::fixCatNames($cats, $product['cat_id'], $allCatsArr, $product['title']);
				if (!empty($catArr['GoodsType']) && $catArr['GoodsType'] === 'Инструменты') {
					foreach ($additional as &$add) {
						if ($add['ToolType'] === 'Бензопилы' || $add['ToolType'] === 'Другое') {
							unset($add['ToolSubType']);
						}
					}
				}

				foreach ($catArr as $key => $value) {
					if ($value) {
						$xml->startElement(preg_replace('/\s+/', '', $key));
						$xml->text($value);
						$xml->endElement();
					}
				}

				if (!empty($customOptions[$product['cat_id']]) && is_array($customOptions[$product['cat_id']])) {
					foreach ($customOptions[$product['cat_id']] as $customOption) {
						$elementValue = false;
						switch($customOption['type']) {
							case 'const':
								$elementValue = $customOption['value'];
								break;
							case 'prop':
								if (!empty($product['thisUserFields'][$customOption['value']]['data'][0]['name'])) {
									$elementValue = $product['thisUserFields'][$customOption['value']]['data'][0]['name'];
								}
								break;
							case 'productField':
								if (!empty($product[$customOption['value']])) {
									$elementValue = $product[$customOption['value']];
								}

								switch($customOption['value']) {
									case 'description':
										$elementValue = strip_tags($elementValue);
										break;
									case 'url':
										$elementValue = SITE.'/'.$product['category_url'].'/'.$product['url'];
										break;
									case 'image_url':
										if ($elementValue) {
											$elementValue = SITE.'/uploads/'.$elementValue;
										}
										break;
									case 'count':
										if ($product['count'] == -1) {
											$elementValue = 1000000;
											break;
										}
										$elementValue = floatval($product['count']);
										break;
									case 'currency_iso':
										if ($elementValue === 'RUR') {
											$elementValue = 'RUB';
										}
										break;
									case 'weight_unit':
										if (!empty($product['category_weightUnit'])) {
											$elementValue = $product['category_weightUnit'];
										}
										if (!empty($product['weight_unit'])) {
											$elementValue = $product['weight_unit'];
										}
										break;
								}
								break;
							default:
								continue;
						}
						if ($elementValue !== false && $elementValue !== null) {
							$xml->startElement(preg_replace('/\s+/', '', $customOption['name']));
							$xml->text($elementValue);
							$xml->endElement();
						}
					}
				}
				
				if (!empty($additional[$product['cat_id']]) && is_array($additional[$product['cat_id']])) {
					$propsAddititonals = [
						'Size',
						'Model',
						'MemorySize',
						'Color',
						'RamSize',
					];
					foreach ($additional[$product['cat_id']] as $key => $value) {
						if (in_array($key, $propsAddititonals)) {
							$product = $model->getProduct($row['id'], true, true);
							$product['thisUserFields'] = self::convertProps($product['thisUserFields']);							
							$xml->startElement(preg_replace('/\s+/', '', $key));
							$xml->text($product['thisUserFields']['string'][$value]['value']);
							$xml->endElement();
						}
						else{
							$xml->startElement(preg_replace('/\s+/', '', $key));
							$xml->text($value);
							$xml->endElement();
						}
					}
				}

				if (!empty($options['manager'])) {
					$xml->startElement('ManagerName');
					$xml->text($options['manager']);
					$xml->endElement();
				}
				if (!empty($options['phone'])) {
					$xml->startElement('ContactPhone');
					$xml->text($options['phone']);
					$xml->endElement();
				}

                $xml->startElement('Address');
                $xml->text($options['address']);
                $xml->endElement();

				$xml->startElement('Region');
				$xml->text($options['region']);
				$xml->endElement();

				if (!empty($options['city'])) {
					$xml->startElement('City');
					$xml->text($options['city']);
					$xml->endElement();
				}

				if (!empty($options['subway'])) {
					$xml->startElement('Subway');
					$xml->text($options['subway']);
					$xml->endElement();
				}

				if (!empty($options['district'])) {
					$xml->startElement('District');
					$xml->text($options['district']);
					$xml->endElement();
				}

				if (!empty($product['images_product'])) {
					$xml->startElement('Images');
					$i=0;
					foreach ($product['images_product'] as $key => $value) {
						if ($i==0 || $i < 10) {
							$xml->startElement('Image');
							$xml->writeAttribute('url', SITE.$ds.'uploads'.$ds.$value);
							$xml->endElement();
						}
						$i++;
					}
					$xml->endElement();
				}
				$xml->endElement();/////////////////////////////////////////////////////  end product
			}
			$xml->endElement();/////////////////////////////////////////////////////////  end xml
			$RSS = $xml->outputMemory();    
			return html_entity_decode($RSS);
		}
	}
}