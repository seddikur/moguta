<?php

/**
 * Класс Property - предназначен для работы с характеристиками.
 *
 * @package moguta.cms
 * @subpackage Libraries
 */

class Property {

	static private $_instance = null;

	/**
	 * Создает характеристики с нуля, для таких моментов как например импорт товаров.
	 * <code>
	 * $res = Property::createProp('name1');
	 * viewData($res);
	 * </code>
	 * @param string $name название характеристики 
	 * @param string $type тип характеристики 
	 * @param string $type единица измерения
	 * @return int возвращает id характеристики
	 */
	public static function createProp($name, $type = 'string', $unit = '') {
		// единица измерения
		if(substr_count($name, '[unit=') == 1) {
			$tmp = explode('[unit=', $name);
			$tmp = explode(']', $tmp[1]);
			$unit = $tmp[0];
			$name = trim(str_replace('[unit='.$unit.']', '', $name));
		}
		// тип диапазон
		if(substr_count($name, '[diapason]') == 1) {
			$name = trim(str_replace('[diapason]', '', $name));
			$type = 'diapason';
		}
		// если текстареа, то меняем тип
		if(substr_count($name, '[textarea]') == 1) {
			$name = trim(str_replace('[textarea]', '', $name));
			$type = 'textarea';
		}
		// если file, то меняем тип
		if (substr_count($name, '[file]') == 1) {
			$name = trim(str_replace('[file]', '', $name));
			$type = 'file';
		}
		// проверка наличия характеристики с таким именем
		$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE name = '.DB::quote($name));
		if($row = DB::fetchAssoc($res)) {
			return $row['id'];
		} else {
      $sort = 1;
			$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property');
			while($row = DB::fetchAssoc($res)) {
				$sort = 10000000 - $row['MAX(id)'];
			}
			DB::query('INSERT INTO '.PREFIX.'property (name, type, activity, sort, unit) VALUES 
				('.DB::quote($name).', '.DB::quote($type).', 1, '.DB::quoteInt($sort).', '.DB::quote($unit).')');
		}
		// делаем запрос для получения свежего id новой только созданной характеристики
		return DB::insertId();
	}

	/**
	 * Создает связки категории с характеристикой.
	 * <code>
	 * $propId = 1;
	 * $catId = 12;
	 * Property::createPropToCatLink($propId, $catId);
	 * </code>
	 * @param int $propId id товара
	 * @param int $catId id категории
	 */
	public static function createPropToCatLink($propId, $catId) {
		// проверка наличия связки
		$res = DB::query('SELECT * FROM '.PREFIX.'category_user_property WHERE
			category_id = '.DB::quoteInt($catId).' AND property_id = '.DB::quoteInt($propId));
		// если связи нет, то создаем
		if(!$row = DB::fetchAssoc($res)) {
			DB::query('INSERT INTO '.PREFIX.'category_user_property (category_id, property_id) VALUES
				('.DB::quoteInt($catId).', '.DB::quoteInt($propId).')');
		}
	}

	/**
	 * Создает строковую характеристику для товара.
	 * <code>
	 * $text = 'Значение характеристики';
	 * $productId = 12;
	 * $propId = 1;
	 * Property::createProductStringProp($text, $productId, $propId);
	 * </code>
	 * @param string $text значение характеристики
	 * @param int $productId id товара
	 * @param int $propId id характеристики
	 */
	public static function createProductStringProp($text = '', $productId, $propId) {
		// проверяем тип на то, что это диапазон
		$res = DB::query('SELECT type FROM '.PREFIX.'property WHERE id = '.DB::quoteInt($propId));
		if($row = DB::fetchAssoc($res)) {
			if($row['type'] == 'diapason') {
				$tmp = explode('/', $text);
				$var = array();
				$var[] = trim($tmp[0]);
				$var[] = trim($tmp[1]);
				DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE product_id = '.DB::quoteInt($productId).' AND prop_id = '.DB::quoteInt($propId));
				foreach ($var as $key => $propVal) {
					// добавляем строку в характеристику
					$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($propId).' AND name = '.DB::quote($propVal));
					if(!$row = DB::fetchAssoc($res)) {
            $maxId = 1;
						$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property_data');
						if($row = DB::fetchAssoc($res)) {
							$maxId = $row['MAX(id)'];
							$maxId++;
						}
						DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, sort) VALUES
							('.DB::quoteInt($propId).', '.DB::quote($propVal).', '.DB::quoteInt($maxId).')');
						$propDataId = DB::insertId();
					} else {
						$propDataId = $row['id'];
					}
					// добавляем саму строку к характеристике
					DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, prop_data_id, product_id, name, active) VALUES
						('.DB::quoteInt($propId).', '.DB::quoteInt($propDataId).', '.DB::quoteInt($productId).', '.DB::quote($propVal).', 1)');
				}
				return true;
			}
		}
		// проверяем наличие для нее свойства
		$res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE
			prop_id = '.DB::quoteInt($propId).' AND product_id = '.DB::quoteInt($productId));
		if(!$row = DB::fetchAssoc($res)) {
			// добавляем строку в характеристику
			$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($propId).' AND name = '.DB::quote($text));
			if(!$row = DB::fetchAssoc($res)) {
        $maxId = 1;
				$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property_data');
				if($row = DB::fetchAssoc($res)) {
					$maxId = $row['MAX(id)'];
					$maxId++;
				}
				DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, sort) VALUES
					('.DB::quoteInt($propId).', '.DB::quote($text).', '.DB::quoteInt($maxId).')');
				$propDataId = DB::insertId();
			} else {
				$propDataId = $row['id'];
			}
			// добавляем саму строку к характеристике
			DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, prop_data_id, product_id, name, active) VALUES
				('.DB::quoteInt($propId).', '.DB::quoteInt($propDataId).', '.DB::quoteInt($productId).', '.DB::quote($text).', 1)');
		}
	}

	/**
	 * Добваления к характеристике ее свойств (к товару) работает по ссылке.
	 * <code>
	 * 	Property::addDataToProp($product['property'], $product['id']);
	 * 	viewData($property);
	 * </code>
	 * @param array $prop характеристика, является ссылкой
	 * @param int $productId id товара
	 */
	public static function addDataToProp(&$prop, $productId) {
		if (!is_array($prop) || empty($prop)) {
			return false;
		}

		if(MG::get('controller') == 'controllers_product') {
			$drop = true;
		} else {
			$drop = false;
		}

		$requests = $simpleProps = $hardProps = array();
		foreach ($prop as $key => $value) {
			if(!empty($value['property_id'])) {
				$propId = $value['property_id'];
			} else {
				$propId = $value['id'];
			}
			if(($value['type'] == 'string')||($value['type'] == 'textarea')||($value['type'] == 'diapason')) {
				$simpleProps[] = $propId;
			} else {
				$hardProps[] = $propId;
			}
		}

		if (!empty($simpleProps)) {
			$res = DB::query("SELECT pupd.*, p.activity AS act, prop_id 
				FROM ".PREFIX."product_user_property_data AS pupd LEFT JOIN ".PREFIX."property AS p ON p.id = pupd.prop_id
				WHERE product_id = ".DB::quoteInt($productId)." AND prop_id IN (".DB::quoteIN($simpleProps).') ORDER BY name ASC');
			while ($row = DB::fetchAssoc($res)) {
				$requests['simpleProps'][$row['prop_id']][] = $row;
			}
		}
		if (!empty($hardProps)) {
			$res = DB::query("SELECT pupd.*, pd.name AS name_orig, pd.margin AS margin_orig, pupd.prop_id 
				FROM ".PREFIX."property_data AS pd
				LEFT JOIN ".PREFIX."product_user_property_data AS pupd
					ON pupd.prop_data_id = pd.id
			    WHERE pupd.product_id = ".DB::quoteInt($productId)." AND pupd.prop_id IN (".DB::quoteIN($hardProps).') ORDER BY pd.sort');
			while ($row = DB::fetchAssoc($res)) {
				if (!empty($prop[$row['prop_id']]['unit']) && !in_array($prop[$row['prop_id']]['type_filter'], ['select', 'checkbox'])) {
					$tmpOrigName = $row['name_orig'];
					$row['name_orig'] .= ' ' . $prop[$row['prop_id']]['unit'];
					if(MG::get('controller')=="controllers_product"){
						$row['name_orig'] = $tmpOrigName;
					}
				}
				$row['unit_orig'] = $prop[$row['prop_id']]['unit'];
				$requests['hardProps'][$row['prop_id']][] = $row;
			}
		}

		foreach ($prop as $key => $value) {
			if(!empty($value['property_id'])) {
				$propId = $value['property_id'];
			} else {
				$propId = $value['id'];
			}
		  	$data = null;
		  	// viewdata($value);
		  	if(($value['type'] == 'string')||($value['type'] == 'textarea')||($value['type'] == 'diapason')) {
		  		if (isset($requests['simpleProps'][$propId])) {
		  			foreach ($requests['simpleProps'][$propId] as $userFieldsData) {
		  				if($drop && $userFieldsData['act'] == 0) break;
		  		  		$data[] = $userFieldsData;
		  			}
		  		}
	  			$table = 'product_user_property_data';
		  	} else {
		  		if (isset($requests['hardProps'][$propId])) {
		  			foreach ($requests['hardProps'][$propId] as $userFieldsData) {
		  		  		/*if(empty($userFieldsData['name']))*/ $userFieldsData['name'] = $userFieldsData['name_orig'];
		  		  		if(empty($userFieldsData['margin']) && $userFieldsData['margin'] === 0) $userFieldsData['margin'] = $userFieldsData['margin_orig'];
			    		$data[] = $userFieldsData;
		  		  	}
		  		}
	  		  	$table = 'property_data';
		  	}

		  	if((LANG != '')&&(LANG != 'LANG')&&(LANG != 'default')) {

		  	  if($data != null) {
		  	  	// viewdata($data);
		  	  	$idsVar = array();
		  	    foreach ($data as $item) {
		  	    	// if(empty($item['prop_data_id'])) {
		  	    	if($table == 'product_user_property_data') {
		  	    		$idsVar[] = $item['id'];
		  	    	} else {
		  	    		$idsVar[] = $item['prop_data_id'];
		  	    	}
		  	    }
		  	    $localeData = array();
		  	    $res = DB::query('SELECT `id_ent`, `field`, `text` FROM '.PREFIX.'locales WHERE 
		  	      `id_ent` IN ('.DB::quoteIN($idsVar).') AND `table` = '.DB::quote($table).' AND locale = '.DB::quote(LANG));
		  	    while($row = DB::fetchAssoc($res)) {
		  	      $localeData[$row['id_ent']][$row['field']] = $row['text'];
		  	    }
		  	    // viewdata($localeData);
		  	    foreach ($data as $keyIn => $item) {
		  	      foreach ($item as $keyIn2 => $item2) {
		  	        if($table == 'property_data') {
		  	        	if(!empty($localeData[$item['prop_data_id']][$keyIn2])) $data[$keyIn][$keyIn2] = $localeData[$item['prop_data_id']][$keyIn2];
		  	        } else {
		  	        	if(!empty($localeData[$item['id']][$keyIn2])) $data[$keyIn][$keyIn2] = $localeData[$item['id']][$keyIn2];
		  	        }
		  	        
		  	      }
		  	    }
		  	  }
		  	}

		  	if($data != null) {
		    	$prop[$key]['data'] = array();
		    	foreach ($data as $elem) {
	      			$prop[$key]['data'][] = $elem;
		    	}
		  	}

		  	// для текстареа подругому
		  	if($prop[$key]['type'] == 'textarea') {
		  		if($prop[$key]['data']) {
			    	$prop[$key]['value'] = htmlspecialchars_decode($prop[$key]['data'][0]['name']);
			    	unset($prop[$key]['data']);
			    }
		  	}
		}
	}

	/**
	 * Возвращает строку со значениями сложных характеристик для экспорта в CSV.
	 * <code>
	 * 	$productId = 13;
	 * 	$res = Property::getHardPropToCsv($productId);
	 * 	viewData($res);
	 * </code>
	 * @param int $productId id товара
	 * @return string
	 */
	public static function getHardPropToCsv($id) {
		// получаем списко свойств характеристик
		$res = DB::query("
			SELECT DISTINCT pupd.name, pupd.margin, pupd.active, pupd.type_view, pd.name AS name_orig, p.id,
				p.name AS prop_name, p.type, p.activity, p.filter, p.description, pd.margin AS margin_orig
			FROM ".PREFIX."product_user_property_data AS pupd
			LEFT JOIN ".PREFIX."property AS p
				ON p.id = pupd.prop_id
			LEFT JOIN ".PREFIX."property_data AS pd
				ON pupd.prop_data_id = pd.id
			WHERE pupd.product_id = ".DB::quoteInt($id)." AND ((p.type = 'assortment') OR (p.type = 'assortmentcheckbox'))");
		while ($row = DB::fetchAssoc($res)) {
			$prop[] = $row;
		}

		// массив с настройками сформированный
		$propArr = array();

		// структуируем полученные данные
		if (isset($prop)) {
			foreach ($prop as $item) {
				if(empty($propArr[$item['id']])) {
					$propArr[$item['id']]['prop_name'] = $item['prop_name'];
					$propArr[$item['id']]['type'] = $item['type'];
					$propArr[$item['id']]['activity'] = $item['activity'];
					$propArr[$item['id']]['filter'] = $item['filter'];
					$propArr[$item['id']]['description'] = $item['description'];
				}
				if($item['name'] == '') $item['name'] = $item['name_orig'];
				if($item['margin'] == '') $item['margin'] = $item['margin_orig'];

				if (!isset($propArr[$item['id']]['val'])) {
					$propArr[$item['id']]['val'] = '';
				}
				if (!isset($propArr[$item['id']]['margin'])) {
					$propArr[$item['id']]['margin'] = '';
				}

				$propArr[$item['id']]['val'] .= $item['name'].'#'.$item['margin'].'#'.$item['active'].'#'.$item['type_view'].'#|';
				$propArr[$item['id']]['margin'] .= $item['name_orig'].'#'.$item['margin_orig'].'#|';
			}
		}
		// формируем строку с результатом для записи в файл
		$res = '';
		foreach ($propArr as $item) {
			$val = mb_substr($item['val'], 0, -1);
			$margin = mb_substr($item['margin'], 0, -1);
			$res .= $item['prop_name']."=[type=".$item['type']." value=".$val." product_margin=".$margin.
				" activity=".$item['activity']." filter=".$item['filter']." description=".$item['description']."]&";
		}
		$result = mb_substr($res, 0, -1);

		return $result;
	}

	/**
	 * Возвращает массив с именами простых характеристик для оглавления столбцов в файле.
	 * <code>
	 * 	$res = Property::getEasyPropNameToCsv();
	 * 	viewData($res);
	 * </code>
	 * @return array
	 */
	public static function getEasyPropNameToCsv($listProductId = null) {
		unset($_SESSION['export']['propColumns']);
    $result = array();
		// подбор категорий если надо
		if($listProductId) {
      $catIds = $propIds = array();
			$res = DB::query('SELECT DISTINCT cat_id FROM '.PREFIX.'product WHERE id IN ('.DB::quoteIN($listProductId).')');
			while($row = DB::fetchAssoc($res)) {
				$catIds[] = $row['cat_id'];
			}
			$res = DB::query('SELECT DISTINCT property_id FROM '.PREFIX.'category_user_property WHERE category_id IN ('.DB::quoteIN($catIds).')');
			while($row = DB::fetchAssoc($res)) {
				$propIds[] = $row['property_id'];
			}
			$propWhere = ' AND id IN ('.DB::quoteIN($propIds).')';
		} else {
			$propWhere = '';
		}
		$count = 0;
		$res = DB::query("SELECT id, name, type, unit FROM ".PREFIX."property WHERE 
			type IN ('string', 'textarea', 'size', 'color', 'diapason', 'file')".$propWhere);
		while($row = DB::fetchAssoc($res)) {
			if(in_array($row['type'], array('size', 'color', 'textarea', 'diapason', 'file'))) {
				$type = '['.$row['type'].']';
			} else {
				$type = '';
			}
			$result[] = $row['name']." ".$type.($row['unit']?'[unit='.$row['unit'].']':'');

			// для запоминания порядка столбцов
			$_SESSION['export']['propColumns'][$row['id']] = $count++;
		}

		return $result;
	}

	/**
	 * Возвращает массив со значениями простых характеристик с учетом порядка их расположения.
	 * <code>
	 * 	$productId = 13;
	 * 	$colorId = 12;
	 * 	$sizeId = 4;
	 * 	$res = Property::getEasyPropToCsv($productId, $colorId, $sizeId);
	 * 	viewData($res);
	 * </code>
	 * @param int $id id товара
	 * @param int $color id цвета
	 * @param int $size id размера
	 * @return array
	 */
	public static function getEasyPropToCsv($id, $color, $size) {
		$names = $result = array();
		if(empty($color)||empty($size)) {
			if(empty($color)) {
				$neededProp = $size;
			} else {
				$neededProp = $color;
			}
		} else {
			if(!(empty($color)&&empty($size))) {
				$neededProp = $color.','.$size;
			} else {
				$neededProp = '';
			}
		}
		if ($neededProp === '0') {
			$neededProp = false;
		}
		if(!empty($neededProp))
			$neededProp = " OR (pupd.product_id = ".DB::quoteInt($id)." AND pd.id IN (".DB::quote($neededProp, true)."))";
		$res = DB::query("
			SELECT pupd.name, p.id, pd.name AS name_orig, pd.color
			FROM ".PREFIX."product_user_property_data AS pupd
				LEFT JOIN ".PREFIX."property AS p
					ON p.id = pupd.prop_id
				LEFT JOIN ".PREFIX."property_data AS pd
					ON pd.id = pupd.prop_data_id
			  WHERE (pupd.product_id = ".DB::quoteInt($id)." AND p.type IN ('string', 'textarea', 'file'))".$neededProp);
		while ($row = DB::fetchAssoc($res)) {
			$color = !empty($row['color']) ? ' ['.$row['color'].']' : '';
			if(empty($row['name'])) {
				$val = $row['name_orig'];
				$val = str_replace("&quot;", "'", $val);
				$val = str_replace(array("\r", "\n"), "", $val);
				$val = str_replace(';"', '"', $val);
				$val = str_replace('"', '\'', $val);
				$names[$_SESSION['export']['propColumns'][$row['id']]] = $val.$color;
			} else {
				$val = $row['name'];
				$val = str_replace("&quot;", "'", $val);
				$val = str_replace(array("\r", "\n"), "", $val);
				$val = str_replace(';"', '"', $val);
				$val = str_replace('"', '\'', $val);
				$names[$_SESSION['export']['propColumns'][$row['id']]] = $val.$color;
			}
		}

		$dataDiap = null;
		$res = DB::query('SELECT pupd.name, p.id
			FROM '.PREFIX.'product_user_property_data AS pupd
				LEFT JOIN '.PREFIX.'property AS p
					ON p.id = pupd.prop_id
				LEFT JOIN '.PREFIX.'property_data AS pd
					ON pd.id = pupd.prop_data_id
			  WHERE pupd.product_id = '.DB::quoteInt($id).' AND p.type = \'diapason\' ORDER BY name DESC');
		while ($row = DB::fetchAssoc($res)) {
			$dataDiap['id'] = $row['id'];
			$dataDiap['data'][] = $row['name'];
		}
		if($dataDiap) {
			$val = $dataDiap['data'][0].'/'.$dataDiap['data'][1];
			$names[$_SESSION['export']['propColumns'][$dataDiap['id']]] = $val;
		}

		// формируем строку для записи
		$propColumnsCount = 0;
		if (!empty($_SESSION['export']['propColumns'])) {
			$propColumnsCount = count($_SESSION['export']['propColumns']);
		}
		for($i = 0; $i < $propColumnsCount; $i++) {
			if (isset($names[$i])) {
				$result[] = $names[$i];
			} else {
				$result[] = '';
			}
		}

		return $result;
	}

	/**
	 * Создает сложные характеристики при импорте из CSV.
	 * <code>
	 * 	$productId = 13;
	 * 	$colorId = 12;
	 * 	$sizeId = 4;
	 * 	$res = Property::getEasyPropToCsv($productId, $colorId, $sizeId);
	 * 	viewData($res);
	 * </code>
	 * @param string $data строка с характеристикой
	 * @param int $productId id цвета
	 * @param int $catId id размера
	 * @return array
	 */
	public static function createHardPropFromCsv($data, $productId, $catId) {
		if(empty($data)) return false;
		// дробим данные для дальнейшей работы с ними
		$listProperty = str_replace('&amp;', '[[amp]]', $data);

		$params = explode('&', $listProperty);
		$paramsarr = $arrProperty = $product_margin = $property = array();
    $activity = $filter = $description = '';
		foreach($params as $value) {
		  $value = str_replace('[[amp]]', '&', $value);
		  if (stristr($value, '=[')!== FALSE&&$value[strlen($value)-1]==']'&&stristr($value, 'type')!== FALSE
		    &&stristr($value, 'value')!== FALSE&&stristr($value, 'product_margin')!== FALSE) {
		    $tmp = explode('=[', $value);
		    $tmp[1] = '['.$tmp[1];
		  } else {
		    $tmp = explode('=', $value);
		  }      
		  $arrProperty[$tmp[0]] = $tmp[1];
		}
		// разбиваем полученные характеристики на данные пригодные для работы
		// =========================================
		// 	   значения характеристик в формате
		// 	   для $value_prop, $product_margin
		// =========================================
		//	$value_prop[0] = значение
		//	$value_prop[1] = наценка
		//	$value_prop[2] = параметр активности
		//	$value_prop[3] = тип вывода
		// =========================================
    $value_prop = array();
		foreach($arrProperty as $key => $value) {
		  $type = 'string';
		  $data = '';
		  // Если характеристика сложная, то выделим параметры - тип, значение, наценки.
		  if ($value[0]=='['&&$value[strlen($value)-1]==']'&&stristr($value, 'type')!== FALSE
		    &&stristr($value, 'value')!== FALSE&&stristr($value, 'product_margin')!== FALSE) {
		    if(preg_match("/type=([^&]*)value/", $value, $matches))  {
		      $type = trim($matches[1]);
		    }
		    if(preg_match("/value=([^&]*)product_margin/", $value, $matches))  {
		      $tmp = explode('|', trim($matches[1]));
		      $value_prop = array();
		      foreach ($tmp as $item) {
		      	$value_prop[] = explode('#', $item);
		      }		      
		    }
		    if(preg_match("/product_margin=([^&]*)activity/", $value, $matches))  {
		    	$tmp = explode('|', trim($matches[1]));
		    	$product_margin = array();
		    	foreach ($tmp as $item) {
		    		$product_margin[] = explode('#', $item);
		    	}		      
		    }
		    if(preg_match("/activity=([^&]*)filter/", $value, $matches))  {
		      $activity = trim($matches[1]);
		    }
		    if(preg_match("/filter=([^&]*)description/", $value, $matches))  {
		      $filter = trim($matches[1]);
		    }
		    if(preg_match("/description=([^&]*)]/", $value, $matches))  {
		      $description = trim($matches[1]);
		    }
		    $value = $value_prop;
		  }

		  $info['name'] = $key; 
		  $info['type'] = $type; 
		  $info['userProp'] = $value_prop; 
		  $info['propData'] = $product_margin; 
		  $info['active'] = $activity; 
		  $info['filter'] = $filter; 
		  $info['description'] = $description; 
		  $property[] = $info;
		}

		// обрабатываем характеристики
		foreach ($property as $item) {
			if(empty($_SESSION['import']['hardPropertyId'][$item['name']])) {
				// проверяем наличие характеристики, в итоге получаем ее id, если ее не было, то вставляем
				$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE name = '.DB::quote($item['name']));
				if($id = DB::fetchAssoc($res)) {
					$_SESSION['import']['hardPropertyId'][$item['name']] = $id['id'];
				} else {
					DB::query('INSERT INTO '.PREFIX.'property (name, type, activity, filter, description) VALUES 
						('.DB::quote($item['name']).', '.DB::quote($item['type']).', '.DB::quoteInt($item['active']).', 
						'.DB::quoteInt($item['filter']).', '.DB::quote($item['description']).')');
					$_SESSION['import']['hardPropertyId'][$item['name']] = DB::insertId();
				}
			}
			// вставка данных для характеристики
			foreach ($item['propData'] as $propData) {
				if(empty($_SESSION['import']['hardPropertyDataId'][$item['name']][$propData[0]])) {
					$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).'
						AND name = '.DB::quote($propData[0]));
					if($propId = DB::fetchAssoc($res)) {
						$_SESSION['import']['hardPropertyDataId'][$item['name']][$propData[0]] = $propId['id'];
					} else {
            $sort = 1;
						$res = DB::query('SELECT MAX(sort) FROM '.PREFIX.'property_data');
						if($row = DB::fetchAssoc($res)) $sort = 1 + $row['MAX(sort)'];
						DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, margin, sort) VALUES 
							('.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).', 
							'.DB::quote($propData[0]).', '.DB::quote($propData[1]).', '.DB::quoteInt($sort).')');
						$_SESSION['import']['hardPropertyDataId'][$item['name']][$propData[0]] = DB::insertId();
					}
				}
			}
			// после того как характеристика точно создана и параметры для нее, создаем параметры для самого товара
			foreach ($item['userProp'] as $userProp) {
				$res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE prop_id = '.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).'
					AND name = '.DB::quote($userProp[0]).' AND product_id = '.DB::quoteInt($productId));
				if($userPropId = DB::fetchAssoc($res)) {
					DB::query('UPDATE '.PREFIX.'product_user_property_data SET margin = '.DB::quote($userProp[1]).', 
						active = '.DB::quote($userProp[2]).', type_view = '.DB::quote($userProp[3]).' WHERE id = '.DB::quoteInt($userPropId['id']));
				} else {
					DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, prop_data_id, product_id, name, margin, active, type_view) VALUES 
						('.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).', 
						'.DB::quoteInt($_SESSION['import']['hardPropertyDataId'][$item['name']][$userProp[0]]).', '.DB::quoteInt($productId).', 
						'.DB::quote($userProp[0]).', '.DB::quote($userProp[1]).', '.DB::quoteInt($userProp[2]).', '.DB::quote($userProp[3]).')');
				}
			}
			// привязываем характеристику к категории товара
			// проверяем наличие привязки
			self::createPropToCatLink($_SESSION['import']['hardPropertyId'][$item['name']], $catId);
		}
	} 

	/**
	 * Создает характеристику размера и цвета при импорте из CSV и сразу прикрепляет ее к товару.
	 * <code>
	 * 	$propName = 'Цвет корпуса';
	 * 	$val = 'Белый';
	 * 	$productId = '13';
	 * 	$variant = 'Белый';
	 * 	$catId = '5';
	 * 	Property::createSizeMapPropFromCsv($propName, $val, $productId, $variant, $catId);
	 * </code>
	 * @param string $propName название характеристики
	 * @param string $val значение характеристики
	 * @param int $productId id товара
	 * @param int $variant id цвета
	 * @param int $catId id размера
	 */
	public static function createSizeMapPropFromCsv($propName, $val, $productId, $variant, $catId) {
    $color = '';
		// определяем тип характеристики
		if(substr_count($propName, '[size]') == 1) {
			$type = 'size';
			$propName = trim(str_replace('[size]', '', $propName));
		}
		if(substr_count($propName, '[color]') == 1) {
			$type = 'color';
			$propName = trim(str_replace('[color]', '', $propName));
			$tmp = explode('[', $val);
			$val = trim($tmp[0]);
			$color = str_replace(']', '', $tmp[1]);
		}
		if(empty($type)) return false;

		if(substr_count($propName, '[unit=') == 1) {
			$tmp = explode('[unit=', $propName);
			$tmp = explode(']', $tmp[1]);
			$unit = $tmp[0];
			$propName = trim(str_replace('[unit='.$unit.']', '', $propName));
		}
		// создаем характеристику
		// проверяем наличие характеристики
		$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE name = '.DB::quote($propName));
		if(!$propId = DB::fetchAssoc($res)) {
			DB::query('INSERT INTO '.PREFIX.'property (name, type, unit) VALUES ('.DB::quote($propName).', '.DB::quote($type).', '.DB::quote($unit).')');
			$propId = DB::insertId();
		} else {
			$propId = $propId['id'];
		}
		// прикрепляем значение к характеристике
		$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE name = '.DB::quote($val).' AND prop_id = '.DB::quoteInt($propId));
		if(!$dataId = DB::fetchAssoc($res)) {
			DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, color) VALUES 
				('.DB::quoteInt($propId).', '.DB::quote($val).', '.DB::quote($color).')');
			$dataId = DB::insertId();
			DB::query("UPDATE `".PREFIX."property_data` SET `sort` = ".$dataId." WHERE `id` = ".$dataId);
		} else {
			$dataId = $dataId['id'];
		}
		// прикрепляем значение к товару
		$res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE name = '.DB::quote($val).' AND product_id = '.DB::quoteInt($productId));
		if($userPropId = DB::fetchAssoc($res)) {
			DB::query('UPDATE '.PREFIX.'product_user_property_data SET name = '.DB::quote($val).' WHERE id = '.$userPropId['id']);
		} else {
			DB::query('INSERT INTO '.PREFIX.'product_user_property_data (product_id, prop_id, prop_data_id, name) VALUES 
				('.DB::quoteInt($productId).', '.DB::quoteInt($propId).', '.DB::quoteInt($dataId).', '.DB::quote($val).')');
		}
		// привязываем характеристику к категории товара
		self::createPropToCatLink($propId, $catId);
		// добавляем варианту товара размерную сетку
		DB::query('UPDATE '.PREFIX.'product_variant SET '.DB::buildPartQuery(array($type => $dataId)).' WHERE 
			title_variant = '.DB::quote($variant).' AND product_id = '.DB::quote($productId));
	}
	
	/**
	 * Возвращает список всех групп характеристик.
	 * <code>
	 * 	$res = Property::getPropertyGroup();
	 *  viewData($res);
	 * </code>
	 * @param bool $mod
	 * @return array
	 */	
	public static function getPropertyGroup($mod = false) {
		$result = MG::get('propertyGroupCache'.$mod?'Mod':'');

		if ($result === null) {
			$result = array();
			$res = DB::query("SELECT * FROM ".PREFIX."property_group WHERE 1=1 ORDER BY sort ASC");
			while($row = DB::fetchAssoc($res)) {
				MG::loadLocaleData($row['id'], LANG, 'property_group', $row);
				if($mod) {
				 	$result[$row['id']] = $row;
				} else {
				 // для js не создаем ключи в массиве, иначе массив читается как объект
			  	 	$result[] = $row;
				}
			}
			MG::set('propertyGroupCache'.$mod?'Mod':'', $result);
		}
		return $result;
	}
	/**
	 * Добавляет группу характеристик.
	 * <code>
	 * 	$name = 'NewGropup';
	 * 	$res = Property::addPropertyGroup($name);
	 *  viewData($res);
	 * </code>
	 * @param string $name название группы
	 * @return bool true
	 */	
	public static function addPropertyGroup($name) {	
	  	DB::query('INSERT INTO '.PREFIX.'property_group (name, sort) VALUES ('.DB::quote($name).', 0)');
      	$id = DB::insertId();
	  	DB::query('UPDATE '.PREFIX.'property_group SET sort = '.DB::quoteInt($id).' WHERE id = '.DB::quoteInt($id));		
	  	return $id;
	}
		
	/**
	 * Удаляет группу характеристик.
	 * <code>
	 * $res = Property::addPropertyGroup(12);
	 * var_dump($res);
	 * </code>
	 * @param int $id группы характеристик
	 * @return bool true
	 */	
	public static function deletePropertyGroup($id) {	
	  	DB::query('DELETE FROM `'.PREFIX.'property_group` WHERE `'.PREFIX.'property_group`.`id` = '.DB::quoteInt($id));	
	  	return true;
	}	
	
	/**
	 * Сортирует список строковых характеристик на два массива, с группами и без. В соответствии с заданной сортировкой групп и характеристик в них.
	 * @param array $data массив строковых характеристик
	 * @param bool $returnArray
	 */
	public static function sortPropertyToGroup($data, $returnArray = false, $propertiesKey = 'stringsProperties', $layout = 'layout_prop_string') {	
		// viewData($data);
		$unGroupProperty = array();
		$groupProperty = array();

		foreach ($data[$propertiesKey] as $key=>$item) {		
			if(is_array($item) && (empty($item[0]['name']) && $item[0]['name'] != 0) || empty($item)) {
			 	continue;
			}
			
			if(!empty($item[0]['group_prop']) && $item[0]['name']!='') {
				$groupKey = $item[0]['group_prop']['name'];								  
				$groupProperty[$groupKey]['name_group'] = $item[0]['group_prop']['name'];			
				$groupProperty[$groupKey]['priority'] = $item[0]['group_prop']['sort'];	
				
				$groupProperty[$groupKey]['property'][] = array(
				'key_prop' => $key,
				'name_prop' => $item[0]['name'],
				'priority' => isset($item[0]['priority'])?$item[0]['priority']:null,
				'unit' => $item[0]['unit'],
				);								  
			} else {

				if(is_array($item)) {
					$item[0]['name_prop'] = $key;
					$unGroupProperty[] = $item[0];
				} else {
					$unGroupProperty[] = array('name_prop' => $key,'name' => $item);
				}
			}
		}

		if (!empty($groupProperty)) {
			foreach ($groupProperty as $key => $group) {
				if (isset($groupProperty[$key]['property'][0]['priority'])) {
					usort($groupProperty[$key]['property'], array("MG", "prioritet"));
					$groupProperty[$key]['property'] = array_reverse($groupProperty[$key]['property']); //А точно ли надо?
				}
			}
		}

		if (isset($unGroupProperty[0]['priority'])) {
			usort($unGroupProperty, array("MG", "prioritet"));
			$unGroupProperty = array_reverse($unGroupProperty); //А точно ли надо?
		}

		usort($groupProperty, function ($group1, $group2) {
			return ($group1['priority'] < $group2['priority']) ? -1 : (($group1['priority'] > $group2['priority']) ? 1 : 0);
		});
		foreach ($groupProperty as $key => $value) {
		    $counts = count($value['property']);
			if($counts > 1){
				usort($groupProperty[$key]['property'], function ($group11, $group22) {
				return ($group22['priority'] < $group11['priority']) ? -1 : (($group22['priority'] > $group11['priority']) ? 1 : 0);
				});
			}
		}

		if ($returnArray) {
			$result = array('groupProperty' => $groupProperty,'unGroupProperty' => $unGroupProperty);	
		} else {
			$result = MG::layoutManager($layout, array('groupProperty' => $groupProperty,'unGroupProperty' => $unGroupProperty));	
		}
    	return $result;
	}

	/**
	 * Сохраняет пользовательские характеристики для товара 
	 * (внутренний метод, используется только при сохранении товара).
	 * @param array $userProperty набор характеристик.
	 * @param int $id - id товара.
	 * @return bool
	 */
	public static function saveUserProperty($userProperty, $id, $lang = '') {
		if($lang == '') $lang = LANG;
	  	foreach ($userProperty as $key => $value) {
	    	$propertyId = (int)$key;
        $realVal = '';
	    	$res = DB::query('SELECT name FROM '.PREFIX.'product_user_property_data
	    		WHERE prop_id = '.DB::quoteInt($propertyId).' AND product_id = '.DB::quoteInt($id));
	    	if($row = DB::fetchAssoc($res)) {
	    		$realVal = $row['name'];
	    	}
	
	    	DB::query('DELETE FROM '.PREFIX.'product_user_property_data
	    	  WHERE prop_id = '.DB::quoteInt($propertyId).' AND product_id = '.DB::quoteInt($id));
	
	    	switch ($value['type']) {
	    	  	case 'select':
	    	  	case 'checkbox':
	    	    	unset($value['type']);
	    	    	foreach ($value as $keyIn => $item) {
	    	    	  	$data = explode('#', $item['val']);
	    	    	  	// данные в базу
	    	    	  	$toDB = array();

	    	    	  	$toDB['margin'] = '';
	    	    	  	$toDB['type_view'] = null;
	    	    	  	if (isset($data[1])) {
	    	    	  		$toDB['margin'] = $data[1];
	    	    	  	}
	    	    	  	if (isset($item['type-view'])) {
	    	    	  		$toDB['type_view'] = $item['type-view'];
	    	    	  	}

	    	    	  	$toDB['active'] = $item['active'];
	    	    	  	$toDB['id'] = $keyIn;
	    	    	  	$toDB['prop_id'] = $propertyId;
	    	    	  	$toDB['product_id'] = $id;
	    	    	  	$toDB['prop_data_id'] = $item['prop-data-id'];
	    	    	  	$toDB['name'] = '';
	    	    	  	// 
	    	    	  	// проерка на новизну
	    	    	  	if(substr_count($keyIn, 'temp') == 1) {
	    	    	  	  	unset($toDB['id']);
	    	    	  	} 
    	    	  		DB::query("INSERT INTO `".PREFIX."product_user_property_data` SET ".DB::buildPartQuery($toDB));
	    	    	}
	    	    break;
				case 'file':
	    	  	case 'input':
	    	  	case 'textarea':
	    	  		$type = $value['type'];
	    	    	unset($value['type']);
	    	    	foreach ($value as $keyIn => $item) {
	    	    		if($item['val'] == '') {continue;}
						$propDataId = null;
	    	    		// добавляем строку в характеристику
	    	    		if(empty($item['prop-data-id']) && $type != 'textarea') {
							// т. к. в большинстве кодировок база не учитывает регистр при поиске
							// строки, то нам необходимо перебрать все подходящие значения для характериситки
							// и сравнить их через строгое равенство, чтобы найти точное совпадение
							$existedPropertyDatasSql = 'SELECT `id`, `name` '.
								'FROM `'.PREFIX.'property_data` '.
								'WHERE `prop_id` = '.DB::quoteInt($propertyId).' AND '.
									'`name` = '.DB::quote($item['val']);
							$existedPropertyDatasResult = DB::query($existedPropertyDatasSql);
							$propDataId = null;
							while($existedPropertyDatasRow = DB::fetchAssoc($existedPropertyDatasResult)) {
								if ($existedPropertyDatasRow['name'] === $item['val']) {
									$propDataId = $existedPropertyDatasRow['id'];
									break;
								}
							}
							if (!$propDataId) {
								$maxId = 1;
								$maxPropDataIdSql = 'SELECT MAX(`id`) as maxId '.
									'FROM `'.PREFIX.'property_data`';
								$maxPropDataIdResult = DB::query($maxPropDataIdSql);
								if ($maxPropDataIdRow = DB::fetchAssoc($maxPropDataIdResult)) {
									$maxId = $maxPropDataIdRow['maxId'] + 1;
								}
								$insertPropertyDataSql = 'INSERT INTO `'.PREFIX.'property_data` '.
									'(`prop_id`, `name`, `sort`) VALUES '.
									'('.DB::quoteInt($propertyId).','.DB::quote($item['val']).','.DB::quoteInt($maxId).')';
								DB::query($insertPropertyDataSql);
								$propDataId = DB::insertId();
							}
	    	    		}
	    	    		// данные в базу
	    	    		$toDB = array();
	    	    		$toDB['margin'] = '';
	    	    		$toDB['active'] = 1;
	    	    		$toDB['type_view'] = isset($item['type-view'])?$item['type-view']:null;
	    	    		$toDB['id'] = $keyIn;
	    	    		$toDB['prop_id'] = $propertyId;
	    	    		$toDB['product_id'] = $id;
	    	    		$toDB['prop_data_id'] = !empty($item['prop-data-id'])?$item['prop-data-id']:$propDataId;
	    	    		if (isset($item['val'])) {
	    	    			$item['val'] = MG::moveCKimages($item['val'], 'product', $id, 'prop', 'product_user_property_data', 'name', $keyIn, $realVal);
						}
	    	    		$toDB['name'] = $item['val'];
	    	    		// 
	    	    		// сохраняем локализацию
	    	    		$filterProp = array('val');
	    	    		$localeDataVariants = MG::prepareLangData($item, $filterProp, $lang);
	    	    		if(!empty($localeDataVariants['val'])) {
	    	    		  	MG::saveLocaleData($keyIn, $lang, 'product_user_property_data', array('name' => $localeDataVariants['val']));
	    	    		}
	    	    		// 
	    	    		// проерка на новизну
	    	    	  	if(substr_count($keyIn, 'temp') == 1) {
	    	    	  		unset($toDB['id']);
	    	    	  	}

	    	    	  	if(empty($item['val'])) {
                    if(($lang != 'LANG') && ($lang != 'default')) {
                      // unset($toDB['name']);
                      $toDB['name'] = $realVal;
                    } else {
                      $toDB['name'] = '';
                    }				
	    	    	  	}					
						// fix при сохранении 0 в строковой характеристики
						if($item['val']==='0') {
							$toDB['name'] = '0';
						}
	    	    	  	DB::query("INSERT INTO `".PREFIX."product_user_property_data` SET ".DB::buildPartQuery($toDB));

	    	    	}
	    	    break;
	    	    case 'diapason':
	    	    	if($value['min']['val'] == '') continue;
	    	    	// добавляем строку в характеристику
	    	    	unset($toDB);
    	    		$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($propertyId).' AND name = '.DB::quote($value['min']['val']));
    	    		if(!$row = DB::fetchAssoc($res)) {
                $maxId = 1;
    	    			$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property_data');
    	    			if($row = DB::fetchAssoc($res)) {
    	    				$maxId = $row['MAX(id)'];
    	    				$maxId++;
    	    			}
    	    			DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, sort) VALUES
    	    				('.DB::quoteInt($propertyId).', '.DB::quote($value['min']['val']).', '.DB::quoteInt($maxId).')');
    	    			$propDataId = DB::insertId();
    	    		} else {
    	    			$propDataId = $row['id'];
    	    		}
    	    		$toDB['active'] = 1;
    	    		if (!isset($value['min']['id'])) {
    	    			$toDB['id'] = null;
    	    		} elseif(substr_count($value['min']['id'], 'tmp') == 0) {
    	    			$toDB['id'] = $value['min']['id'];
    	    		}
    	    		$toDB['prop_id'] = $propertyId;
    	    		$toDB['product_id'] = $id;
    	    		$toDB['prop_data_id'] = $propDataId;
    	    		$toDB['name'] = $value['min']['val'];
    	    		DB::query("INSERT INTO `".PREFIX."product_user_property_data` SET ".DB::buildPartQuery($toDB));
					// 
					unset($toDB);
    	    		$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($propertyId).' AND name = '.DB::quote($value['max']['val']));
    	    		if(!$row = DB::fetchAssoc($res)) {
                $maxId = 1;
    	    			$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property_data');
    	    			while($row = DB::fetchAssoc($res)) {
    	    				$maxId = $row['MAX(id)'];
    	    				$maxId++;
    	    			}
    	    			DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, sort) VALUES
    	    				('.DB::quoteInt($propertyId).', '.DB::quote($value['max']['val']).', '.DB::quoteInt($maxId).')');
    	    			$propDataId = DB::insertId();
    	    		} else {
    	    			$propDataId = $row['id'];
    	    		}
    	    		$toDB['active'] = 1;
    	    		if(substr_count($value['max']['id'], 'tmp') == 0) $toDB['id'] = $value['max']['id'];
    	    		$toDB['prop_id'] = $propertyId;
    	    		$toDB['product_id'] = $id;
    	    		$toDB['prop_data_id'] = $propDataId;
    	    		$toDB['name'] = $value['max']['val'];
    	    		DB::query("INSERT INTO `".PREFIX."product_user_property_data` SET ".DB::buildPartQuery($toDB));
	    	    break;
	    	}
	  	}
	}

	/**
	 * Привязывает характеристики к новой категории товара
	 * (внутренний метод, используется только при сохранении товара).
	 * @param int $oldCatId - id старой категории товара.
	 * @param int $newCatId - id новой категории товара.
	 * @param array $propertys - массив характеристик товара.
	 * @return void
	 */
	static function addCategoryBinds($oldCatId, $newCatId, $propertys) {
		if ($oldCatId == $newCatId || empty($propertys) || $oldCatId == 0 || $newCatId == 0) {return false;}

		$propIds = array();
		$blockColorSave = $blockSizeSave = true;

		foreach ($propertys as $propId => $prop) {
			if (in_array($prop['type'], array('select', 'checkbox')) && count($prop) < 2) {continue;}
			if (in_array($prop['type'], array('textarea', 'input'))) {
				$continue = true;
				foreach ($prop as $arr) {
					if (isset($arr['val']) && $arr['val'] !== '') {
						$continue = false;
					}
				}
				if ($continue) {continue;}
			}
			if ($prop['type'] == 'diapason' && $prop['min']['val'] === '' && $prop['max']['val'] === '') {continue;}
			$propIds[intval($propId)] = intval($propId);
		}

		$res = DB::query("SELECT * FROM `".PREFIX."category_user_property` WHERE `category_id` = ".DB::quoteInt($newCatId));
		while ($row = DB::fetchAssoc($res)) {
			if ($row['property_id'] == $_POST['variants'][0]['color'] || !$_POST['variants'][0]['color']) {
				$blockColorSave = false;
			}
			if ($row['property_id'] == $_POST['variants'][0]['size'] || !$_POST['variants'][0]['size']) {
				$blockSizeSave = false;
			}
			unset($propIds[intval($row['property_id'])]);
		}

		if (!empty($propIds)) {
			$sql = array();
			foreach ($propIds as $propId) {
				$sql[] = "(".DB::quoteInt($newCatId,1).", ".DB::quoteInt($propId,1).")";
			}
			DB::query("INSERT INTO `".PREFIX."category_user_property` (category_id, property_id) VALUES ".implode(', ', $sql));
		}

		if ($blockColorSave || $blockSizeSave) {
			MG::set('disableVariantSizeMapSave', 1);
		}
	}
}
