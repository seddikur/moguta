<?php
/**
 * Класс YandexMarket используется для создания и редактирования выгрузок для Yandex.Market
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class YandexMarket {
	static $nonRootCats = array();
	static $allIds = array();
	static $printedCats = array();
	static $allowedCurrs = array('RUR','RUB','UAH','BYN','KZT','USD','EUR');
   /**
	* Подготавливает данные для страницы интеграции
	* @access private
	*/
	static function createPage() {
		$names = $props = $customTagOptions = [];
		$rows = DB::query("SELECT `name` FROM `".PREFIX."yandexmarket` ORDER BY `edited` DESC");
		while ($row = DB::fetchAssoc($rows)) {
			$names[] = $row['name'];
		}

		$opfsSql = 'SELECT `id`, `name` '.
			'FROM `'.PREFIX.'product_opt_fields`';
		$opfsResult = DB::query($opfsSql);
		while ($opfsRow = DB::fetchAssoc($opfsResult)) {
			$customTagOptions['opf_'.$opfsRow['id']] = $opfsRow['name'].' (Дополнительное поле)';
		}

		$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` ORDER BY `sort` asc");
		while ($row = DB::fetchAssoc($rows)) {
			$props[$row['id']] = $row['name'];
			$customTagOptions['prop'.$row['id']] = $row['name'].' (Характеристика)';
		}

		$model = new Models_Catalog;
		$arrayCategories = $model->categoryId = MG::get('category')->getHierarchyCategory(0);
		$categoriesOptions = MG::get('category')->getTitleCategory($arrayCategories, URL::get('category_id'));

		$rows = DB::query("SELECT `title` FROM `".PREFIX."product` ORDER BY RAND() LIMIT 1");
		if ($row = DB::fetchAssoc($rows)) {
			$exampleName = $row['title'];
		} else {
			$exampleName = '';
		}

		include('mg-admin/section/views/integrations/'.basename(__FILE__));

		echo '<script>'.
			'includeJS("'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js", "YandexMarketModule.init");'.
			'</script>';
	}
   /**
	* Создает новую выгрузку.
	* @param string $name название выгрузки
	* @return string|bool название выгрузки, если оно уникально или false если повторяется
	*/
	static function newTab($name) {
		$dbRes = DB::query("SELECT `name` FROM `".PREFIX."yandexmarket` WHERE `name` = ".DB::quote($name));

		if(!$row = DB::fetchArray($dbRes)) {
			DB::query("INSERT IGNORE INTO  `".PREFIX."yandexmarket` (`name`) VALUES (".DB::quote($name).")");
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
		if ($data['descLength'] < 1 || $data['descLength'] > 2975) {
			$data['descLength'] = 2975;
		}
		$data = addslashes(serialize($data));
        
		DB::query("UPDATE `".PREFIX."yandexmarket` SET `settings`=".DB::quote($data)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Получает настройки выгрузки.
	* @param string $name название выгрузки
	* @return array массив с данными выгрузки
	*/
	static function getTab($name) {
    $res = '';
		$rows = DB::query("SELECT `settings` FROM `".PREFIX."yandexmarket` WHERE `name` = ".DB::quote($name));
		if ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}
		$options = unserialize(stripslashes($res));
		$options['ignoreProducts'] = !empty($options['ignoreProducts'])?self::getRelatedNew($options['ignoreProducts'], $options['useAsProdVariants'], $options['useAsProd']):array();
		$options['addProducts'] = !empty($options['addProducts'])?self::getRelated($options['addProducts']):array();
		if (empty($options['descLength'])) {
			$options['descLength'] = 2975;
		}
		$currencies = MG::getSetting('currencyShort');

		if (empty($options['uploadCurr'])) {
			$tmp = MG::getSetting('currencyShopIso');
			if (in_array($tmp, self::$allowedCurrs)){
				$options['uploadCurr'] = $tmp;
			}
			else{
				foreach ($currencies as $key => $value) {
					if (in_array($key, self::$allowedCurrs)) {
						$options['uploadCurr'] = $key;
						break;
					}
				}
			}
		}

		$options['currSelect'] = '';

		foreach ($currencies as $key => $value) {
			if (in_array($key, self::$allowedCurrs)) {
				if ($options['uploadCurr'] == $key) {
					$selected = 'selected';
				}
				else{
					$selected = '';
				}
				$options['currSelect'] .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
			}
		}
		return $options;
	}
   /**
	* Удаляет выгрузку.
	* @param string $name название выгрузки
	* @return bool
	*/
	static function deleteTab($name) {
		DB::query("DELETE FROM `".PREFIX."yandexmarket` WHERE `name`=".DB::quote($name));
		
		return true;
	}
   /**
	* Удаляет из URL все запрещенные спецсимволы, заменяет пробелы на тире.
	* @param string $str строка для операции
	* @return string
	*/
	static function prepareUrl($str) {

		$str = strtolower($str);
		$str = preg_replace('%\s%i', '-', $str);
		$str = str_replace('`', '', $str);
		$str = str_replace(array("\\","<",">"),"",$str);    
		$str = preg_replace('%[^/-a-zа-я#\.\d]%iu', '', $str);
		return $str;
	}
	
   /**
	* Возвращает данные игнорируемых или дополнительных товаров.
	* @param string $option артикулы товаров
	* @return array массив с данными игнорируемых или дополнительных товаров
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
	* Возвращает данные игнорируемых или дополнительных товаров.
	* @param string $option артикулы товаров
	* @return array массив с данными игнорируемых или дополнительных товаров
	*/
    	static function getRelatedNew($option, $useAsProdVariants, $useAsProd) {
		$stringRelated = ' null';
	    $sortRelated = array();
        $useAsProdVariants = explode(',', $useAsProdVariants);
        $useAsProd = explode(',', $useAsProd);
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
	        p.url as product_url, p.id, pv.image as pvimage,  p.image_url as pimage, p.price_course as price,p.title, pv.title_variant, p.code as pcode, pv.code as pvcode
	      FROM `'.PREFIX.'product` p
	        LEFT JOIN `'.PREFIX.'category` c
	        ON c.id = p.cat_id
	        LEFT JOIN `'.PREFIX.'product_variant` AS pv
	        ON pv.product_id = p.id
	      WHERE p.code IN ('.$stringRelated.') OR pv.code IN ('.$stringRelated.')'); 
      //Массив, куда записываем картинку и title варианта товара, который имеет одинаковый артикул с товаром в БД, но проигнорирован как вариант      
        $varianUrlAndTitle = [];
        
	    while ($row = DB::fetchAssoc($res)) {
	      $img = explode('|', $row['pimage']);
          if(!empty($row['pvimage'])  ){
            if(in_array($row['pcode'], $useAsProd)){
              $row['pimage'] = $img[0];
            }else{
              if(in_array($row['pcode'], $useAsProdVariants) && $row['pcode'] == $row['pvcode']){
                $varianUrlAndTitle[$row['pcode']] = [
                    'title_variant' => $row['title_variant'],
                    'pimage' => $row['pvimage'],
                ];
              }
              $row['pimage'] = $row['pvimage'];   
            }
          }else{
            $row['pimage'] = $img[0];
          }
          
          if(array_key_exists($row['pvcode'], $sortRelated)){
            if(!in_array($row['pcode'], $useAsProd)){
              $sortRelated[$row['pvcode']] = $row;
            }else if (in_array($row['pcode'], $useAsProdVariants)){
              $row['pvcode'] = $row['pcode'];
              $sortRelated[$row['pcode']] = $row;
            }else{
              $row['pvcode'] = 'false';
              $sortRelated[$row['pcode']] = $row;
            }
          }else{
            if (in_array($row['pcode'], $useAsProdVariants)){
              $row['pvcode'] = $row['pcode'];
            }else{
              $row['pvcode'] = 'false';
            }
            $sortRelated[$row['pcode']] = $row;
          }
	    }
        
	    $productsRelated = array();
	    if (!empty($sortRelated)) {
	      foreach ($sortRelated as $key => $item) {
            if(isset($varianUrlAndTitle[$key])){
              $item['title_variant'] = $varianUrlAndTitle[$key]['title_variant'];
              $item['pimage'] = $varianUrlAndTitle[$key]['pimage'];
            }
	        if (is_array($item)) {
	          $item['image_url'] = mgImageProductPath($item['pimage'], $item['id'], 'small');
	          $productsRelated[] = $item;
	        }
	      }
	    }
	    return $productsRelated;
	}
   /**
	* Получение значение характеристики товара по ID характеристики
	* @access private
	* @param int $idProduct ID товара, int $idProp ID характеристики
	* @return string значение характеристики
	*/
        static function getValueByIdProperty($idProduct,$idProp) {
            if (!empty($idProp)) {
                $res = DB::query('
                    SELECT pd.name as propValue FROM '.PREFIX.'product_user_property_data AS pupd
                    LEFT JOIN '.PREFIX.'property AS prop
                            ON prop.id = pupd.prop_id
                    LEFT JOIN '.PREFIX.'property_data AS pd
                            ON pupd.prop_data_id = pd.id
                    WHERE pupd.product_id = '.DB::quoteInt($idProduct).' AND prop.id = '.DB::quoteInt($idProp));
                while ($row = DB::fetchAssoc($res)) {
                    $props = $row['propValue'];
                }
                return $props;
            }
            else return '';
        }
   /**
	* Получение ID товаров по их артикулам
	* @access private
	* @param array $arr артикулы товаров
	* @return array массив ID товаров
	*/
	static function getIdByCode($arr) {

		if (!empty($arr)) {

			foreach ($arr as $key => $value) {
				$arr[$key] = DB::quote($value);
			}

			$databaseres = DB::query('SELECT `id` FROM `'.PREFIX.'product`  WHERE `code` IN ('.implode(', ', $arr).')');
			$arr = array();
			while ($databaserow = DB::fetchAssoc($databaseres)) {
				$arr[] = $databaserow['id'];
			}
		}
		return $arr;
	}
   /**
	* Приводит характеристики к нормальному виду
	* @access private
	* @param array $thisUserFields массив с характеристиками
	* @return array массив с характеристиками
	*/
	static function convertProps($thisUserFields) {
		$result = array();
		if (!empty($thisUserFields)) {
			foreach ($thisUserFields as $value) {
				if (!is_array($value['data']) || empty($value['data'])) {continue;}
				$tmp = array();

				$name = $value['name'];

				if (strpos($name, '[')) {
					$name = explode('[', $name);
					$name = $name[0];
				}

				$name = trim($name);
				$name = html_entity_decode($name);
				$name = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $name);
				
				if (strlen($name) < 1) {continue;}

				switch ($value['type']) {
					case 'string':
						if (strlen($value['data'][0]['name']) < 1) {break;}
						$value['data'][0]['name'] = html_entity_decode($value['data'][0]['name']);
						$value['data'][0]['name'] = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $value['data'][0]['name']);
						$result['string'][$value['prop_id']] = array(
							'id' => $value['prop_id'],
							'name' => $name,
							'unit' => $value['unit'],
							'active' => $value['activity'],
							'value' => $value['data'][0]['name']
						);
						break;
					
					case 'assortmentCheckBox':
						foreach ($value['data'] as $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$v['name'] = html_entity_decode($v['name']);
								$v['name'] = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $v['name']);
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
						foreach ($value['data'] as $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$v['name'] = html_entity_decode($v['name']);
								$v['name'] = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $v['name']);
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
						foreach ($value['data'] as $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$v['name'] = html_entity_decode($v['name']);
								$v['name'] = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $v['name']);
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
	 * Метод для создания ссылки на предложение. Собираем UTM-метки.
	 * @access private
	 */
	static function createURL($options, $product, $codde) {
		$utm = array();
		if (!empty($options['customUtm'])) {
			foreach ($options['customUtm'] as $key => $value) {
				if ($value['type'] == 'text') {
					
					$tmp = $value['name'];
					if (strlen($value['val']) > 0) {
						$tmp .= '='.$value['val'];
					}
					$utm[] = $tmp;
				}

				if ($value['type'] == 'prop' && strlen($product['thisUserFields']['string'][$value['val']]['value']) > 0) {
					$tmp = self::prepareUrl($product['thisUserFields']['string'][$value['val']]['value']);
					if (strlen($tmp) > 0) {
						$utm[] = $value['name'].'='.$tmp;
					}
				}

				if ($value['type'] == 'prop' && !is_numeric($value['val'])) {
					if ($value['val'] == 'catUrl') {
						$tmp = explode('/', $product['category_url']);
						$tmp = array_pop($tmp);
						if (strlen($tmp) > 0) {
							$utm[] = $value['name'].'='.$tmp;
						}
					}
					if ($value['val'] == 'prodUrl') {
						$utm[] = $value['name'].'='.$product['product_url'];
					}
					if ($value['val'] == 'idOffer') {
						$utm[] = $value['name'].'='.$codde;
					}
				}
			}
		}

		if (!empty($utm)) {
			$utm = implode('&', $utm);
			$utm = '?'.$utm;
		}
		else{
			$utm = '';
		}

		if (MG::getSetting('shortLink')=='true') {
			$url = SITE.'/'.$product['url'].$utm;
		}
		else {
			$url = SITE.'/'.(isset($product["category_url"]) ? $product["category_url"] : 'catalog').'/'.$product['url'].$utm;
		}

		return $url;
	}
   /**
	* Создает результат выгрузки.
	* @param string $name название выгрузки
	* @return string результат выгрузки
	*/
	static function constructYML($name){
		MG::resetAdminCurrency();
    	$res = '';
		$rows = DB::query("SELECT `settings` FROM `".PREFIX."yandexmarket` WHERE `name` = ".DB::quote($name));
		if ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}

		if (strlen($res) > 1) {
			$options = unserialize(stripslashes($res));
			if (!$options['descLength']) {
				$options['descLength'] = 2975;
			}
			if (empty($options['propDisable'])) {
				$options['propDisable'] = array();
			}
			$format = 'db';
			if ($options['format']) {
				$format = $options['format'];
			}

			// настройки для выгрузки вк
			if ($format === 'vk') {
				$options['skipName'] = 'false';
				$options['uploadCurr'] = 'RUR';
				$options['uploadType'] = 'simple';
				$options['useProps'] = 'false';
				$options['descLength'] = '256';
				$options['useCdata'] = 'false';
				$options['useMarket'] = 'false';
				$options['useRelated'] = 'false';
				$options['salesNotes'] = '';
				$options['useGroupId'] = 'false';
				$options['customUtm'] = [];
			}

			$priceMarkup = false;
			$percentMarkup = false;
			if (isset($options['priceRate']) && strlen($options['priceRate'])) {
				$priceMarkup = floatval(filter_var(str_replace(',', '.', $options['priceRate']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
				$percentMarkup = mb_strpos($options['priceRate'], '%') !== false;
				if ($percentMarkup) {
					$priceMarkup = 1 + ($priceMarkup / 100);
				}
			}
			$currencies = MG::getSetting('currencyRate');
			$mainCurr = MG::getSetting('currencyShopIso');
			$hashInVariantUrl = MG::getSetting('varHashProduct');

			if (!isset($options['customUtm'])) {$options['customUtm'] = array();}
			if (!isset($options['customTags'])) {$options['customTags'] = array();}
			if (!isset($options['customParams'])) {$options['customParams'] = array();}
			if (!isset($options['customTagNames'])) {$options['customTagNames'] = array();}

			if (!$options['uploadCurr']) {
				if (in_array($mainCurr, self::$allowedCurrs)){
					$options['uploadCurr'] = $mainCurr;
				}
				else{
					foreach ($currencies as $key => $value) {
						if (in_array($key, self::$allowedCurrs)) {
							$options['uploadCurr'] = $key;
							break;
						}
					}
				}
			}

			if (($options['uploadType'] == 'custom' || $options['uploadType'] == 'musicNvidio') && $options['skipName'] == 'true') {
				$skipName = true;
			}
			else{
				$skipName = false;
			}

			if ($options['useProps'] == 'true') {
				$useProps = true;
			}
			else{
				$useProps = false;
				foreach ($options['customUtm'] as $value) {
					if ($value['type'] == 'prop') {
						$useProps = true;
					}
				}
				foreach ($options['customTags'] as $value) {
					if ($value['type'] == 'prop') {
						$useProps = true;
					}
				}
				foreach ($options['customParams'] as $value) {
					if ($value['type'] == 'prop') {
						$useProps = true;
					}
				}

			}
			$salesNotesProps = $tmp = array();
			if (strpos($options['salesNotes'], '{prop=') !== false) {
				$useProps = true;
				preg_match_all("/{prop=(.*?)}/", $options['salesNotes'], $tmp);
				foreach ($tmp[0] as $key => $value) {
					$salesNotesProps[$value] = $tmp[1][$key];
				}
			}

			$xml = new XMLWriter();
			$xml->openMemory();
			$xml->startDocument('1.0', 'UTF-8');
			$xml->setIndent(true);
			$xml->startElement('yml_catalog');
			$xml->writeAttribute('date', date(DATE_ATOM));
			$xml->startElement('shop');
			$xml->writeElement('name', MG::getSetting('sitename'));
			$xml->writeElement('company', $options['company']);
			$xml->writeElement('url', SITE);
			if (in_array($format, ['db', 'fb']) && !empty($options['deliverys'])) {
				$xml->writeElement('platform', 'Moguta.CMS');
			}
			if($options['useMarket'] == 'true') {$xml->writeElement('cpa', '0');}
			$xml->startElement('currencies');///////////////////  start currencies
			
			$xml->startElement('currency');
			$xml->writeAttribute('id', $mainCurr);
			$xml->writeAttribute('rate', round($currencies[$mainCurr],6));
			$xml->endElement();

//			foreach ($currencies as $key => $value) {
//				if ($key == $mainCurr) {continue;}
//				if (in_array($key, self::$allowedCurrs) && (float)$value != 1) {
//					$xml->startElement('currency');
//					$xml->writeAttribute('id', $key);
////					$xml->writeAttribute('rate', round($value,6));
//					$xml->endElement();
//				}
//			}
			$xml->endElement();//////////////////////////////////  end currencies

			/////////////  start delivery-options
			if (in_array($format, ['db', 'ali']) && !empty($options['deliverys'])) {
				$generalDeliveries = array_filter($options['deliverys'], function($delivery) {
					return !$delivery['code'] ? true : false;
				});
				if ($generalDeliveries) {
					$xml->startElement('delivery-options');
					foreach ($generalDeliveries as $delivery) {
						if (strlen($delivery['cost']) > 0 && empty($delivery['code'])) {
							$xml->startElement('option');
							$xml->writeAttribute('cost', $delivery['cost']);
							if (strlen($delivery['time']) > 0) {$xml->writeAttribute('days', $delivery['time']);}
							if (strlen($delivery['before']) > 0) {$xml->writeAttribute('order-before', $delivery['before']);}
							$xml->endElement();
						}
						
					}
					$xml->endElement();
				}
			}
			///////////////////////////////////  end delivery-options

			//// start pickup-options
			if (in_array($format, ['db', 'ali']) && !empty($options['pickups'])) {
				$generalPickups = array_filter($options['pickups'], function($pickup) {
					return !$pickup['code'] ? true : false;
				});
				if ($generalPickups) {
					$cost = 1;
					$xml->startElement('pickup-options');
					foreach ($generalPickups as $pickup) {
						$xml->startElement('option');
						$xml->writeAttribute('cost', $cost);
						$xml->writeAttribute('days', $pickup['time']);
						if (strlen($pickup['before']) > 0) {
							$xml->writeAttribute('order-before', $pickup['before']);
						}
						$xml->endElement();
					}
					$xml->endElement();
				}
			}

			//// end pickup-options

			$usedProductIds = $usedProductCodes = $usedCatIds = array();
			$offersXml = new XMLWriter();////////////////////////////////  start offers
			$offersXml->openMemory();
			$offersXml->setIndent(true);
			$offersXml->startElement("offers");
			//MG::loger($options);
			$filter = 'WHERE ((';

			switch ($options['catsType']) {
				case 'fromCats':
					$catsForExport = array();
					$res = DB::query("SELECT `id` FROM `".PREFIX."category` WHERE `export` = 1");
					while ($row = DB::fetchAssoc($res)) {
						$catsForExport[] = $row['id'];
					}
					$tmp = implode(',', $catsForExport);
					$filter .= '`cat_id` IN ('.DB::quoteIN($tmp).'))';					
					break;
				case 'selected':
					if (!$options['catsSelect']) {
						$options['catsSelect'] = [];
					}
					$tmp = implode(',', $options['catsSelect']);
					$filter .= '`cat_id` IN ('.DB::quoteIN($tmp).'))';
					break;
				default:
					$filter .= '1 = 1)';
					break;
			}

			if ($options['useAdditionalCats'] == 'true') {
				switch ($options['catsType']) {
					case 'fromCats':
						foreach ($catsForExport as $key => $value) {
							$filter .= ' OR FIND_IN_SET('.DB::quote($value).', `inside_cat`)';
						}						
						break;
					case 'selected':
						foreach ($options['catsSelect'] as $key => $value) {
							$filter .= ' OR FIND_IN_SET('.DB::quote($value).', `inside_cat`)';
						}
						break;
					default:
						break;
				}
			}

			if (!empty($options['addProducts'])) {
				$filter .= ' OR `code` IN ('.DB::quoteIN($options['addProducts']).')';
			}
			$filter .= ')';
			if (!empty($options['ignoreProducts']) ) {
                //Проверка, используется ли у нас артикул как вариант или как товар.
                if(isset($options['useAsProdVariants'])){
                  $prodAsProdVariants =  explode(',',$options['useAsProdVariants']);
                  $ignoreProducts = explode(',', $options['ignoreProducts']);
                  //Бежим по массиву удаляемых кодов из выгрузки, и, если находим код который выгружен как вариант, ту удаляем его из массива
                  foreach ($ignoreProducts as $key => &$prod){
                    if(in_array($prod, $prodAsProdVariants)){
                      unset($ignoreProducts[$key]);
                    }
                  }
                  $ignoreProducts = implode(',', $ignoreProducts);
                }else{
                  $ignoreProducts = $options['ignoreProducts'];
                }
                if($ignoreProducts){
                  $filter .= ' AND `code` NOT IN ('.DB::quoteIN($ignoreProducts).')';
                }
			}
			$ignored = explode(',', $options['ignoreProducts']);

			if ($options['inactiveToo'] == "false") {
				$filter .= ' AND `activity` = 1';
			}

			// mg::loger("SELECT `id`, `code`, `count` FROM `".PREFIX."product` ".$filter);
			// exit;

			$model = new Models_Product;
			$catalogModel = new Models_Catalog;

			$res = DB::query("SELECT `id`, `code`, `count`, `inside_cat` FROM `".PREFIX."product` ".$filter);
			while ($row = DB::fetchAssoc($res)) {

				set_time_limit(30);

				if (
					!empty($options['storageCount']) &&
					$options['storageCount']
				) {
					$_POST['storage'] = $options['storageCount'];
				}
				$product = $model->getProduct($row['id'], $useProps);
				$variants = $model->getVariants($row['id']);

				// Эксклюзивный костыле-фикс для плагина комплекта товаров
				if (class_exists('SetGoods')) {
					$complectComponentsSql = 'SELECT `components` '.
						'FROM `'.PREFIX.'set-goods` '.
						'WHERE `id_product` = '.DB::quoteInt($product['id']);
					$complectComponentsResult = DB::query($complectComponentsSql);
					if ($complectComponentsRow = DB::fetchAssoc($complectComponentsResult)) {
						$complectComponents = explode(',', $complectComponentsRow['components']);
						$productsCodes = [];
						foreach ($complectComponents as $complectComponent) {
							$componentParts = explode('|', $complectComponent);
							$productsCodes[$componentParts[0]] = $componentParts[1];
						}
						if ($productsCodes) {
							$setGoodsProductsWhereClause = '(p.`code` IN ('.DB::quoteIN(array_keys($productsCodes)).') '.
								'OR pv.`code` IN ('.DB::quoteIN(array_keys($productsCodes)).'))';
							$setGoodsProducts = $catalogModel->getListByUserFilter(100, $setGoodsProductsWhereClause, false, true);
							if ($setGoodsProducts['catalogItems']) {
								$minCount = $product['count'] >= 0 ? $product['count'] : 100000;
								foreach ($setGoodsProducts['catalogItems'] as $setGoodProduct) {
									if ($setGoodProduct['variants']) {
										foreach ($setGoodProduct['variants'] as $setGoodVariant) {
											if (in_array($setGoodVariant['code'], array_keys($productsCodes))) {
												if ($setGoodVariant['count'] >= 0 && $setGoodVariant['count'] < $minCount) {
													$minCount = $setGoodVariant['count'];
												}
												continue 2;
											}
										}
									}
									if ($setGoodProduct['count'] >= 0 && $setGoodProduct['count'] < $minCount) {
										$minCount = $setGoodProduct['count'];
									}
									if (floatval($minCount) === floatval(0)) {
										break;
									}
								}
								$product['count'] = $minCount;
							}
						}
					}
					// $formattedProducts = SetGoods::formatProducts([$product]);
					// $setGoodsBlockInfo = SetGoods::getAvailable($product['id']);
					// if ($setGoodsBlockInfo) {
					// 	$test = 'test';
					// }
				}

				if (
					!empty($options['priceSource']) &&
					$options['priceSource']
				) {
					if (isset($product['opf_'.$options['priceSource']])) {
						$product['price_course'] = $product['opf_'.$options['priceSource']] ? $product['opf_'.$options['priceSource']] : $product['price_course'];
					}
					if (!empty($variants)) {
						foreach ($variants as &$variant) {
							if (isset($variant['opf_'.$options['priceSource']])) {
								$variant['price_course'] = $variant['opf_'.$options['priceSource']] ? $variant['opf_'.$options['priceSource']] : $variant['price_course'];
							}
						}
					}
				}

				
				if (empty($variants) || $options['useVariants'] == 'false') {
					$printMain = true;
					if (!empty($variants)) {
						$tmp = reset($variants);
						$product['code'] = $tmp['code'];
						$product['count'] = $tmp['count'];
						$product['price'] = $tmp['price'];
						$product['old_price'] = $tmp['old_price'];
						$product['price_course'] = $tmp['price_course'];
						$product['currency_iso'] = $tmp['currency_iso'];
						$product['weight'] = $tmp['weight'];
						foreach ($tmp as $fVariantField => $fVariantValue) {
							if (strpos($fVariantField, 'opf_') === 0) {
								$product[$fVariantField] = $fVariantValue;
							}
						}
					}
				}
				else{
					$printMain = false;
				}

				if ($printMain && $product['count'] == 0 && $options['useNull'] == 'false') {continue;}

				if ($printMain && $product['price'] == 0) {continue;}

				if (!$printMain && $options['useNull'] == 'false') {
					$continue = true;
					foreach ($variants as $var) {
						if ($var['count'] != 0) {
							$continue = false;
							break;
						}
					}
					if ($continue) {
						continue;
					}
				}

				if (!empty($product['thisUserFields'])) {
					$product['thisUserFields'] = self::convertProps($product['thisUserFields']);
				} else {
					$product['thisUserFields'] = array();
				}


				if ($printMain) {
					$offersXml->startElement('offer');
					$codde = ($options['useCodeLikeId'] == 'true' && $format !== 'ali')? preg_replace( "/[^a-zA-Z0-9\s]/", '', $product['code']):$product['id'];
					$offersXml->writeAttribute('id', $codde);
					if($options['useGroupId'] == 'false'){
						$offersXml->writeAttribute('group_id', $product['id']);
					}
				}
				switch ($options['uploadType']) {
					case 'custom':
						$typeUploading = 'vendor.model';
						break;
					case 'books':
						$typeUploading = 'book';
						break;
					case 'audiobooks':
						$typeUploading = 'audiobook';
						break;
					case 'musicNvidio':
						$typeUploading = 'artist.title';
						break;
					case 'medicine':
						$typeUploading = 'medicine';
						break;
					case 'tickets':
						$typeUploading = 'event-ticket';
						break;
					case 'tours':
						$typeUploading = 'tour';
						break;
					
					default:
						$typeUploading = '';
						break;
				}

				if (strlen($typeUploading) > 0) {
					if ($printMain) {
						$offersXml->writeAttribute('type', $typeUploading);
					}
				}
				//Индивидуальная доставка для самого товара
				if ($options['useVariants'] != 'true' || !$variants) {
					$productCode = $product['code'];

					if (in_array($format, ['db','ali','sber'])) {
						if ($product['count']) {
							$offersXml->writeAttribute('available', 'true');
						} else {
							$offersXml->writeAttribute('available', 'false');
						}
					}
					if ($options['useCount'] === 'true' && $format !== 'sber') {
						if ($format === 'ali') {
							$offersXml->writeElement('quantity', $product['count'] == '-1' ? 100000 : $product['count']);
						} else {
							$offersXml->writeElement('count', $product['count'] == '-1' ? 100000 : $product['count']);
						}
					} elseif ($format === 'ali') {
						$offersXml->writeElement('quantity', '0');
					}

					if (in_array($format, ['db', 'ali']) && !empty($options['deliverys'])) {
						/////////////  start delivery-options
						$productsDeliveries = array_filter($options['deliverys'], function($delivery) use ($productCode) {
							$deliveryProductsCodes = explode(',', $delivery['code']);
							if (in_array($productCode, $deliveryProductsCodes)) {
								return true;
							}
							return false;
						});
						if ($productsDeliveries) {
							$offersXml->writeElement('delivery', 'true');
							$offersXml->startElement('delivery-options');
							foreach ($productsDeliveries as $productDelivery) {
								$offersXml->startElement('option');
								$offersXml->writeAttribute('cost', $productDelivery['cost']);
								if (strlen($productDelivery['time']) > 0) {$offersXml->writeAttribute('days', $productDelivery['time']);}
								if (strlen($productDelivery['before']) > 0) {$offersXml->writeAttribute('order-before', $productDelivery['before']);}
								$offersXml->endElement();
							}
							$offersXml->endElement();
						}
						///////////////////////////////////  end delivery-options
					}

					if (in_array($format, ['db', 'ali']) && !empty($options['pickups'])) {
						/////////////  start pickup-options
						$productsPickups = array_filter($options['pickups'], function($pickup) use ($productCode) {
							$pickupProductsCodes = explode(',', $pickup['code']);
							if (in_array($productCode, $pickupProductsCodes)) {
								return true;
							}
							return false;
						});
						if ($productsPickups) {
							$offersXml->writeElement('pickup', 'true');
							$offersXml->startElement('pickup-options');
							$pickupCost = 1;
							foreach ($productsPickups as $productPickup) {
								$offersXml->startElement('option');
								$offersXml->writeAttribute('cost', $pickupCost);
								$offersXml->writeAttribute('days', $productPickup['time']);
								if (strlen($productPickup['before']) > 0) {$offersXml->writeAttribute('order-before', $productPickup['before']);}
								$offersXml->endElement();
							}
							$offersXml->endElement();
						}
						///////////////////////////////////  end pickup-options
					}
				}
				if ($options['useVariants'] == 'true') {
					$variantz = array_values($variants);
					if ($printMain && !$skipName) {
						$title = $product['title'];
						if (isset($variantz[0]['title_variant'])) {
							$title = $title.' '.$variantz[0]['title_variant'];
						}						
						$title = html_entity_decode($title);
						$title = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $title);
						$offersXml->writeElement('name', $title);
					}
				}
				else{
					if ($printMain && !$skipName) {
						$title = $product['title'];
						$title = html_entity_decode($title);
						$title = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $title);
						$offersXml->writeElement('name', $title);
					}
				}

				$tmpRec = '';

				if ($options['useRelated'] == "true" && strlen($product['related'])>0){//////  rec
					$tmpRec = explode(',', $product['related']);
					$tmpRec = self::getIdByCode($tmpRec);
				}
				if ($options['useAdditionalCats'] == 'true' && !in_array($product['cat_id'], $catsForExport)) {
					$tmp = explode(',', $row['inside_cat']);
					foreach ($tmp as $in_cat) {
						if (in_array($in_cat, $catsForExport)) {
							$product['cat_id'] = $in_cat;
							break;
						}
					}
				}
				if ($printMain) {

					if ($options['uploadCurr'] != $product['currency_iso']) {
						$product['price'] = $product['price_course']/$currencies[$options['uploadCurr']];
					}
					else{
						$product['price'] = $product['price_course'];
					}

					if ($priceMarkup) {
						if ($percentMarkup) {
							$product['price'] = floatval($product['price']) * $priceMarkup;
							$product['old_price'] = floatval($product['old_price']) * $priceMarkup;
						} else {
							$product['price'] = floatval($product['price']) + $priceMarkup;
							$product['old_price'] = floatval($product['old_price']) + $priceMarkup;
						}
					}

					if ($options['useCode'] == "true") {$offersXml->writeElement('vendorCode', $product['code']);}
					$url = self::createURL($options, $product, $codde);
					$offersXml->writeElement('url', $url);
					$offersXml->writeElement('price', round($product['price'],2));
					if ($options['useOldPrice'] == "true"){
						$product['old_price'] = $product['old_price']/$currencies[$options['uploadCurr']];
						// $product['old_price'] = mg::convertCustomPrice($product['old_price'], $product['currency_iso'], 'get');
						if ($product['price'] < $product['old_price']){$offersXml->writeElement("oldprice", round($product['old_price'],2));}
					}
					if (in_array($format, ['db','ali'])) {
						$offersXml->writeElement('currencyId', $options['uploadCurr']);
					}
					$offersXml->writeElement('categoryId', $product['cat_id']);
					$usedCatIds[] = $product['cat_id'];
					if ($options['useHook'] == 'true') {
						$usedProductIds[$codde] = $product['id'];
						$usedProductCodes[$codde] = $product['code'];
					}
				}

				$i=0;

				if ($options['skipDesc'] == "false" && !in_array('description', $options['customTagNames']) && 
					(strlen($product['description']) > 0 || ($options['useShortDesc'] == "true" && strlen($product['short_description']) > 0))) {
					
					if ($options['useShortDesc'] == "true" && strlen($product['short_description']) > 0) {
						$product['description'] = $product['short_description'];
					}

					$product['description'] = str_replace('&nbsp;', ' ', $product['description']);
					if ($options['useCdata'] == "true") {
						$product['description'] = strip_tags($product['description'], '<h1> <h2> <h3> <h4> <h5> <oll> <ul> <li> <p> <br> <div>');
					}
					else{
						$product['description'] = strip_tags($product['description']);
					}
					$product['description'] = html_entity_decode(htmlspecialchars_decode($product['description']));
					$product['description'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['description']);
					$product['description'] = preg_replace('/[[:cntrl:]]/', '', $product['description']);
					if ($options['useCdata'] == "true") {
						$product['description'] = strip_tags($product['description'], '<h1> <h2> <h3> <h4> <h5> <oll> <ul> <li> <p> <br> <div>');
					}
					else{
						$product['description'] = strip_tags($product['description']);
					}
					$product['description'] = preg_replace('/\s+/', ' ',$product['description']);
					$product['description'] = mb_substr(trim($product['description']), 0,$options['descLength'], 'utf-8');
					$product['description'] = html_entity_decode($product['description']);
					$product['description'] = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $product['description']);
				}

				if ($printMain) {
					foreach ($product['images_product'] as $key => $value) {
						if (substr($value, -10) == 'no-img.jpg') {continue;}
						if ($i < 10) {
							$value = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $value);
							$offersXml->writeElement('picture', SITE.'/uploads/'.$value);
						}
						$i++;
					}

					if (is_array($tmpRec) && count($tmpRec) > 0) {
						$tmp = array_slice($tmpRec, 0, 29);
						$tmp = implode(',', $tmp);
						$offersXml->writeElement('rec', $tmp);
					}

					if ($options['skipDesc'] == "false" && !in_array('description', $options['customTagNames']) && strlen($product['description']) > 0) {
						$offersXml->startElement('description');
						$offersXml->writeRaw('<![CDATA[ '.$product['description'].' ]]>');
						$offersXml->endElement();
					}
					$weightFrom = $options['weightFrom'];
					if (!$weightFrom) {
						$weightFrom = 'none';
					}
					if ($weightFrom !== 'none') {
						$countWeight = true;
						if ($weightFrom === 'standart') {
							$weight = $product['weight'];
							$countWeight = false;
						} elseif (strpos($weightFrom, 'opf') === 0) {
							$weight = $product[$weightFrom];
						} else {
							$weight = self::getValueByIdProperty($product['id'], $weightFrom);
						}
						if ($countWeight) {
							$weightUnits = $options['weightUnits'];
							switch ($weightUnits) {
								case 'g':
									$weight /= 1000;
									break;
								case 'mg':
									$weight /= 1000000;
									break;
							}
						}
						if ($weight <  0.001) {
							$weight = 0.001;
						}
						$offersXml->writeElement('weight', round(floatval($weight), 3));
					}
					if ($productDimensions = self::getProductDimensions($product['id'], $options)) {
						if ($format === 'ali') {
							$offersXml->writeElement('length', $productDimensions['length']);
							$offersXml->writeElement('width', $productDimensions['width']);
							$offersXml->writeElement('height', $productDimensions['height']);
						} else {
							$offersXml->writeElement('dimensions', implode('/', $productDimensions));
						}
					}
				}

				// custom tags
				if ($printMain) {
					$customTags = array();
					foreach ($options['customTags'] as $key => $value) {
						$value['name'] = preg_replace('/\s+/', '', $value['name']);
						$offersXml->startElement($value['name']);
						$customTagsAttributesString = $value['attributes'];
						if ($customTagsAttributesString) {
							$customTagsAttributes = explode(' ', $customTagsAttributesString);
							foreach ($customTagsAttributes as $customTagsAttribute) {
								preg_match('/([a-zA-Z\d\-_]*)\s?\=\s?"(.*)"/', $customTagsAttribute, $customTagsAttributeMatches);
								$attrName = $customTagsAttributeMatches[1];
								$attrValue = $customTagsAttributeMatches[2];
								if (empty($attrName) || empty($attrValue)) {
									continue;
								}
								preg_match('/\#\d+\#/is',$attrValue,$tagID);
								$tagID = (int) substr($tagID[0],1,-1);
								$tagValue = self::getValueByIdProperty($product['id'],$tagID);
								$attrValue = preg_replace('/\#.+\#/is', $tagValue, $attrValue);
								$offersXml->writeAttribute($attrName, $attrValue);
							}
						}
						if ($value['type'] == 'text' && strlen($value['val']) > 0) {
							if($value['name']=='sales_notes'){
								preg_match('/\#.+\#/is',$value['val'],$tagID); 
								$tagID = (int) substr($tagID[0],1,-1);
								$tagValue = self::getValueByIdProperty($product['id'],$tagID);
								$value['val'] = preg_replace('/\#.+\#/is', $tagValue, $value['val']);
							}
							$offersXml->text($value['val']);
							$customTags[$value['name']] = $value['val'];
						}

						if (
							$value['type'] == 'prop'
						) {
							if (strpos($value['val'], 'prop') === 0) {
								$propId = substr($value['val'], 4);
								if (
									isset($product['thisUserFields']['string'][$propId]['value']) &&
									strlen($product['thisUserFields']['string'][$propId]['value']) > 0
								) {
									if ($printMain && ($value['name'] != 'sales_notes' || ($value['name'] == 'sales_notes' && strlen($options['salesNotes']) < 1))) {
										$offersXml->text($product['thisUserFields']['string'][$propId]['value']);
									}
								}
								$customTags[$value['name']] = $product['thisUserFields']['string'][$propId]['value'];
							} elseif (strpos($value['val'], 'opf_') === 0) {
								$opf = $value['val'];
								if (isset($product[$opf]) && strlen($product[$opf]) > 0) {
									if ($printMain && ($value['name'] != 'sales_notes' || ($value['name'] == 'sales_notes' && strlen($options['salesNotes']) <1 ))) {
										$offersXml->text($product[$opf]);
									}
								}
							}
						}
						$offersXml->endElement();
					}
				}

				// sales notes
				if (strlen($options['salesNotes']) > 0) {
					$unsetSalesNotes = false;
					if (!empty($salesNotesProps)) {
						$replace = array();
						foreach ($salesNotesProps as $propId) {
							if (strlen($product['thisUserFields']['string'][$propId]['value']) > 0) {
								$replace[] = $product['thisUserFields']['string'][$propId]['value'];
							} else {
								$replace[] = '';
								$unsetSalesNotes = true;
							}
						}
						$customTags['sales_notes'] = str_replace(array_keys($salesNotesProps), $replace, $options['salesNotes']);
					} else {
						$customTags['sales_notes'] = $options['salesNotes'];
					}

					if ($unsetSalesNotes) {
						unset($customTags['sales_notes']);
					}

					if (isset($customTags['sales_notes'])) {
						$customTags['sales_notes'] = str_replace('&nbsp;', ' ', $customTags['sales_notes']);
						$customTags['sales_notes'] = strip_tags($customTags['sales_notes']);
						$customTags['sales_notes'] = html_entity_decode(htmlspecialchars_decode($customTags['sales_notes']));
						$customTags['sales_notes'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $customTags['sales_notes']);
						$customTags['sales_notes'] = preg_replace('/[[:cntrl:]]/', '', $customTags['sales_notes']);
						$customTags['sales_notes'] = strip_tags($customTags['sales_notes']);
						$customTags['sales_notes'] = preg_replace('/\s+/', ' ',$customTags['sales_notes']);
						$customTags['sales_notes'] = mb_substr(trim($customTags['sales_notes']), 0, 50, 'utf-8');
						$customTags['sales_notes'] = html_entity_decode($customTags['sales_notes']);
						$customTags['sales_notes'] = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $customTags['sales_notes']);
						if ($printMain) {
							$offersXml->writeElement('sales_notes', $customTags['sales_notes']);
						}
					}
				}

				// custom params
				$customParams = array();
				foreach ($options['customParams'] as $key => $value) {
					if ($value['type'] == 'text' && strlen($value['val']) > 0) {
						if ($printMain) {
							$offersXml->startElement('param');
							$offersXml->writeAttribute('name', $value['name']);
							$offersXml->text($value['val']);
							$offersXml->endElement();
						}

						$customParams[$value['name']] = array('val' => $value['val'], 'unit' => '');
					}
					if ($value['type'] == 'prop' && isset($product['thisUserFields']['string'][$value['val']]['value']) && strlen($product['thisUserFields']['string'][$value['val']]['value']) > 0) {

						if ($printMain) {
							$offersXml->startElement('param');
							$offersXml->writeAttribute('name', $value['name']);
							if (strlen($product['thisUserFields']['string'][$value['val']]['unit']) > 0) {$xml->writeAttribute('unit', $product['thisUserFields']['string'][$value['val']]['unit']);}
							$offersXml->text(strip_tags($product['thisUserFields']['string'][$value['val']]['value']));
							$offersXml->endElement();
						}
						$customParams[$value['name']] = array(
							'val' => strip_tags($product['thisUserFields']['string'][$value['val']]['value']),
							'unit' => $product['thisUserFields']['string'][$value['val']]['unit']
						);
					}
				}

				// params
				$params = array();
				if (!empty($product['thisUserFields']['string']) && !empty($options['useProps']) && $options['useProps'] == 'true') {

					foreach ($product['thisUserFields']['string'] as $key => $value) {

						if ((empty($options['propDisable']) || !in_array($key, $options['propDisable'])) && $value['active'] > 0) {

							if ($printMain) {
								$offersXml->startElement('param');
								$offersXml->writeAttribute('name', $value['name']);
								if (strlen($value['unit']) > 0) {$xml->writeAttribute('unit', $value['unit']);}
								$offersXml->text($value['value']);
								$offersXml->endElement();
							}
							$params[] = $value;
						}
					}
				}

				if ($printMain) {
					$offersXml->endElement();/////////////////////////////////////////////////////////  end offer
				}

				if ($options['useVariants'] == 'true') {////////////////////////////////////////  start variants
					// for ($i=1; $i < count($variants); $i++) {
					foreach ($variants as $variant) {
						if(($variant['count'] == 0 && $options['useNull'] == 'false') || (in_array($variant['code'], $ignored))) {continue;}

						if ($variant['price'] == 0) {continue;}

						$offersXml->startElement('offer');////////////////////////////////////////////  start offer
						$codeSeparator = 'V';
						if ($format === 'ali') {
							$codeSeparator = '00000';
						}
						$codde = ($options['useCodeLikeId'] == 'true' && $format !== 'ali')? preg_replace( "/[^a-zA-Z0-9\s]/", '', $product['code']).$codeSeparator.preg_replace( "/[^a-zA-Z0-9\s]/", '', $variant['code']) : $product['id'].$codeSeparator.$variant['id'];

						$variantCode = $variant['code'];
						if (in_array($format, ['db', 'ali']) && !empty($options['deliverys'])) {
							/////////////  start delivery-options
							$variantDeliveries = array_filter($options['deliverys'], function($delivery) use ($variantCode) {
								$deliveryProductsCodes = explode(',', $delivery['code']);
								if (in_array($variantCode, $deliveryProductsCodes)) {
									return true;
								}
								return false;
							});
							if ($variantDeliveries) {
								$offersXml->writeElement('delivery', 'true');
								$offersXml->startElement('delivery-options');
								foreach ($variantDeliveries as $variantDelivery) {
									$offersXml->startElement('option');
									$offersXml->writeAttribute('cost', $variantDelivery['cost']);
									if (strlen($variantDelivery['time']) > 0) {$offersXml->writeAttribute('days', $variantDelivery['time']);}
									if (strlen($variantDelivery['before']) > 0) {$offersXml->writeAttribute('order-before', $variantDelivery['before']);}
									$offersXml->endElement();
								}
								$offersXml->endElement();
							}
							///////////////////////////////////  end delivery-options
						}

						if (in_array($format, ['db', 'ali']) && !empty($options['pickups'])) {
							/////////////  start pickup-options
							$variantPickups = array_filter($options['pickups'], function($pickup) use ($variantCode) {
								$pickupProductsCodes = explode(',', $pickup['code']);
								if (in_array($variantCode, $pickupProductsCodes)) {
									return true;
								}
								return false;
							});
							if ($variantPickups) {
								$offersXml->writeElement('pickup', 'true');
								$offersXml->startElement('pickup-options');
								$pickupCost = 1;
								foreach ($variantPickups as $variantPickup) {
									$offersXml->startElement('option');
									$offersXml->writeAttribute('cost', $pickupCost);
									$offersXml->writeAttribute('days', $variantPickup['time']);
									if (strlen($variantPickup['before']) > 0) {$offersXml->writeAttribute('order-before', $variantPickup['before']);}
									$offersXml->endElement();
								}
								$offersXml->endElement();
							}
							///////////////////////////////////  end pickup-options
						}

						$offersXml->writeAttribute('id', $codde);

						if($options['useGroupId'] == 'false'){
							$offersXml->writeAttribute('group_id', $product['id']);
						}

						if (in_array($format, ['db','ali','sber'])) {
							if ($variant['count']) {
								$offersXml->writeAttribute('available', 'true');
							} else {
								$offersXml->writeAttribute('available', 'false');
							}
						}
						if ($options['useCount'] === 'true' && $format !== 'sber') {
							if ($format === 'ali') {
								$offersXml->writeElement('quantity', $variant['count'] == '-1' ? 100000 : $variant['count']);
							} else {
								$offersXml->writeElement('count', $variant['count'] == '-1' ? 100000 : $variant['count']);
							}
						} elseif ($format === 'ali') {
							$offersXml->writeElement('quantity', '0');
						}

						if (strlen($typeUploading) > 0) {
							$offersXml->writeAttribute('type', $typeUploading);
						}

						if (!$skipName) {
							$title = $product['title'].' '.$variant['title_variant'];
							$title = html_entity_decode($title);
							$title = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $title);
							$offersXml->writeElement('name', $title);
						}
						if ($options['useCode'] == "true") {$offersXml->writeElement('vendorCode', $variant['code']);}

						$url = self::createURL($options, $product, $codde);

						if ($hashInVariantUrl == "true") {
							$offersXml->writeElement('url', $url.'#'.$variant['code']);
						} else {
							$offersXml->writeElement('url', $url);
						}

						if ($options['uploadCurr'] != $variant['currency_iso']) {
							$variant['price'] = $variant['price_course']/$currencies[$options['uploadCurr']];
						}
						else{
							$variant['price'] = $variant['price_course'];
						}

						if ($priceMarkup) {
							if ($percentMarkup) {
								$variant['price'] = floatval($variant['price']) * $priceMarkup;
								$variant['old_price'] = floatval($variant['old_price']) * $priceMarkup;
							} else {
								$variant['price'] = floatval($variant['price']) + $priceMarkup;
								$variant['old_price'] = floatval($variant['old_price']) + $priceMarkup;
							}
						}

						$offersXml->writeElement('price', round($variant['price'],2));
						if ($options['useOldPrice'] == 'true'){
							$variant['old_price'] = $variant['old_price']/$currencies[$options['uploadCurr']];
							if ($variant['price'] < $variant['old_price']){$offersXml->writeElement('oldprice', round($variant['old_price'],2));}
						}
						if (in_array($format, ['db','ali'])) {
							$offersXml->writeElement('currencyId', $options['uploadCurr']);
						}
						$offersXml->writeElement('categoryId', $product['cat_id']);
						$usedCatIds[] = $product['cat_id'];
						if ($options['useHook'] == 'true') {
							$usedProductIds[$codde] = $product['id'];
							$usedProductCodes[$codde] = $product['code'];
						}

						if (strlen($variant['image']) > 0 && substr($variant['image'], -10) != 'no-img.jpg') {
							$j=1;
							$folder = floor($row['id']/100)*100;
							if ($folder == 0) {$folder = '000';}
							$imgPath = SITE.'/uploads/product/'.$folder.'/'.$row['id'].'/'.$variant['image'];
							$imgPath = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $imgPath);
							if ($format === 'ali') {
								$offersXml->writeElement('sku_picture', $imgPath);
							} else {
								$offersXml->writeElement('picture', $imgPath);
							}
						}
						else{
							$j=0;
						}

						foreach ($product['images_product'] as $key => $value) {
							if (substr($value, -10) == 'no-img.jpg') {continue;}
							if ($j < 10) {
								$value = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $value);
								$offersXml->writeElement('picture', SITE.'/uploads/'.$value);
							}
							$j++;
						}

						if ($options['useRelated'] == 'true' && strlen($product['related'])>0){//////  rec

							if (is_array($tmpRec) && count($tmpRec) > 0) {
								$tmp = array();
								foreach ($variants as $var) {
									foreach ($tmpRec as $rec) {
										$tmp[] = $rec.'V'.$var['id'];
									}
								}
								$tmp = array_slice($tmp, 0, 29);
								$tmp = implode(',', $tmp);
								$offersXml->writeElement('rec', $tmp);
							}
						}

						if ($options['skipDesc'] == "false" && !in_array('description', $options['customTagNames']) && strlen($product['description']) > 0) {
							$offersXml->startElement('description');
							$offersXml->writeRaw('<![CDATA[ '.$product['description'].' ]]>');
							$offersXml->endElement();
						}
						// if (!in_array('sales_notes', $options['customTagNames']) && strlen($product['yml_sales_notes']) > 0) {
						// 	$offersXml->writeElement('sales_notes', $product['yml_sales_notes']);
						// }
						$weightFrom = $options['weightFrom'];
						if (!$weightFrom) {
							$weightFrom = 'none';
						}
						if ($weightFrom !== 'none') {
							$countWeight = true;
							if ($weightFrom === 'standart') {
								$weight = $variant['weight'];
								$countWeight = false;
							} elseif (strpos($weightFrom, 'opf') === 0) {
								$weight = $variant[$weightFrom];
							} else {
								$weight = self::getValueByIdProperty($product['id'], $weightFrom);
							}
							if ($countWeight) {
								$weightUnits = $options['weightUnits'];
								switch ($weightUnits) {
									case 'g':
										$weight /= 1000;
										break;
									case 'mg':
										$weight /= 1000000;
										break;
								}
							}
							if ($weight <  0.001) {
								$weight = 0.001;
							}
							$offersXml->writeElement('weight', round(floatval($weight), 3));
						}
						// габариты
						if ($productDimensions = self::getProductDimensions($product['id'], $options)) {
							if ($format === 'ali') {
								$offersXml->writeElement('length', $productDimensions['length']);
								$offersXml->writeElement('width', $productDimensions['width']);
								$offersXml->writeElement('height', $productDimensions['height']);
							} else {
								$offersXml->writeElement('dimensions', implode('/', $productDimensions));
							}
						}


						//  custom tags
						foreach ($options['customTags'] as $key => $value) {
							$offersXml->startElement($value['name']);
							$customTagsAttributesString = $value['attributes'];
							if ($customTagsAttributesString) {
								$customTagsAttributes = explode(' ', $customTagsAttributesString);
								foreach ($customTagsAttributes as $customTagsAttribute) {
									preg_match('/([a-zA-Z\d\-_]*)\s?\=\s?"(.*)"/', $customTagsAttribute, $customTagsAttributeMatches);
									$attrName = $customTagsAttributeMatches[1];
									$attrValue = $customTagsAttributeMatches[2];
									if (empty($attrName) || empty($attrValue)) {
										continue;
									}
									preg_match('/\#\d+\#/is',$attrValue,$tagID);
									$tagID = (int) substr($tagID[0],1,-1);
									$tagValue = self::getValueByIdProperty($product['id'],$tagID);
									$attrValue = preg_replace('/\#.+\#/is', $tagValue, $attrValue);
									$offersXml->writeAttribute($attrName, $attrValue);
								}
							}
							if ($value['type'] == 'text' && strlen($value['val']) > 0) {
								if($value['name']=='sales_notes'){
									preg_match('/\#.+\#/is',$value['val'],$tagID); 
									$tagID = (int) substr($tagID[0],1,-1);
									$tagValue = self::getValueByIdProperty($product['id'],$tagID);
									$value['val'] = preg_replace('/\#.+\#/is', $tagValue, $value['val']);
								}
								$offersXml->text($value['val']);
								$customTags[$value['name']] = $value['val'];
							}
							if ($value['type'] == 'prop') {
								if (strpos($value['val'], 'prop') === 0) {
									$propId = substr($value['val'], 4);
									if (
										!empty($product['thisUserFields']['color'][$variant['color']]['value']) && 
										$product['thisUserFields']['color'][$variant['color']]['id'] == $propId
									) {
										$value['name'] = preg_replace('/\s+/', '', $value['name']);
										$offersXml->text($product['thisUserFields']['color'][$variant['color']]['value']);
									}
									if (
										!empty($product['thisUserFields']['size'][$variant['size']]['value']) && 
										$product['thisUserFields']['size'][$variant['size']]['id'] == $propId
									) {
										$value['name'] = preg_replace('/\s+/', '', $value['name']);
										$offersXml->text($product['thisUserFields']['size'][$variant['size']]['value']);
									}
									if (
										isset($product['thisUserFields']['string'][$propId]['value']) &&
										strlen($product['thisUserFields']['string'][$propId]['value']) > 0
									) {
										if ($value['name'] != 'sales_notes' || ($value['name'] == 'sales_notes' && strlen($options['salesNotes']) < 1)) {
											$offersXml->text($product['thisUserFields']['string'][$propId]['value']);
										}                                                
										$customTags[$value['name']] = $product['thisUserFields']['string'][$propId]['value'];
									}
								} elseif (strpos($value['val'], 'opf_') === 0) {
									$opf = $value['val'];
									if (isset($variant[$opf]) && strlen($variant[$opf]) > 0) {
										if ($value['name'] != 'sales_notes' || ($value['name'] == 'sales_notes' && strlen($options['salesNotes']) <1 )) {
											$offersXml->text($variant[$opf]);
										}
									}
								}

							}
							$offersXml->endElement();
						}

						//  custom params
						foreach ($customParams as $key => $value) {
							$offersXml->startElement('param');
							$offersXml->writeAttribute('name', $key);
							if (strlen($value['unit']) > 0) {$offersXml->writeAttribute('unit', $value['unit']);}
							$offersXml->text($value['val']);
							$offersXml->endElement();
						}
						foreach ($options['customParams'] as $key => $value) {
							if ($value['type'] == 'prop') {
								if (
									!empty($product['thisUserFields']['color'][$variant['color']]['value']) && 
									$product['thisUserFields']['color'][$variant['color']]['id'] == $value['val']
								) {
									$offersXml->startElement('param');
									$offersXml->writeAttribute('name', $value['name']);
									if (strlen($product['thisUserFields']['color'][$variant['color']]['unit']) > 0) {$offersXml->writeAttribute('unit', $product['thisUserFields']['color'][$variant['color']]['unit']);}
									$offersXml->text(strip_tags($product['thisUserFields']['color'][$variant['color']]['value']));
									$offersXml->endElement();
								}
								if (
									!empty($product['thisUserFields']['size'][$variant['size']]['value']) && 
									$product['thisUserFields']['size'][$variant['size']]['id'] == $value['val']
								) {
									$offersXml->startElement('param');
									$offersXml->writeAttribute('name', $value['name']);
									if (strlen($product['thisUserFields']['size'][$variant['size']]['unit']) > 0) {$offersXml->writeAttribute('unit', $product['thisUserFields']['size'][$variant['size']]['unit']);}
									$offersXml->text(strip_tags($product['thisUserFields']['size'][$variant['size']]['value']));
									$offersXml->endElement();
								}
							}
						}

						//  params
						foreach ($params as $key => $value) {
							$offersXml->startElement('param');
							$offersXml->writeAttribute('name', $value['name']);
							if (strlen($value['unit']) > 0) {$offersXml->writeAttribute('unit', $value['unit']);}
							$offersXml->text($value['value']);
							$offersXml->endElement();
						}

						// size+color
						// mg::loger($product['thisUserFields']['color'][$variant['color']]);
						if ($options['useProps'] == 'true' && 
							is_array($product['thisUserFields']['color'][$variant['color']]) && 
							!in_array($product['thisUserFields']['color'][$variant['color']]['id'], $options['propDisable'])
							) {
							if ($format === 'ali') {
								$offersXml->writeElement('cus_skucolor', $product['thisUserFields']['color'][$variant['color']]['value']);
							} else {
								$offersXml->startElement('param');
								$offersXml->writeAttribute('name', $product['thisUserFields']['color'][$variant['color']]['name']);
								$offersXml->text($product['thisUserFields']['color'][$variant['color']]['value']);
								$offersXml->endElement();
							}
						}

						if ($options['useProps'] == 'true' && 
							is_array($product['thisUserFields']['size'][$variant['size']]) && 
							!in_array($product['thisUserFields']['size'][$variant['size']]['id'], $options['propDisable'])) {
							if ($format === 'ali') {
								$offersXml->writeElement('size', $product['thisUserFields']['size'][$variant['size']]['value']);
							} else {
								$offersXml->startElement('param');
								$offersXml->writeAttribute('name', $product['thisUserFields']['size'][$variant['size']]['name']);
								if (strlen($product['thisUserFields']['size'][$variant['size']]['unit']) > 0) {$offersXml->writeAttribute('unit', $product['thisUserFields']['size'][$variant['size']]['unit']);}
								$offersXml->text($product['thisUserFields']['size'][$variant['size']]['value']);
								$offersXml->endElement();
							}
						}

						$offersXml->endElement();//////////////////////////////////////  end offer
					}
				}//////////////////////////////////////////////////////////////////////  end variants
			}
			$offersXml->endElement();
			$offersXml = $offersXml->outputMemory();///////////////////////////////////  end offers

			set_time_limit(30);
			$categoriesXml = new XMLWriter();//////////////////////////////////////////  start categories
			$categoriesXml->openMemory();
			$categoriesXml->setIndent(true);
			$categoriesXml->startElement('categories');

			$usedCatIds = array_unique($usedCatIds);
			$usedCatIdsKeys = array_flip($usedCatIds);
			$model = new Models_Catalog;
			$arrayCategories = $model->categoryId = MG::get('category')->getHierarchyCategory(0);
			$categories = MG::get('category')->getTitleCategory($arrayCategories, 0, true, '', true);

			foreach ($categories as $catId => $category) {
				$print = false;
				if (array_key_exists($catId, $usedCatIdsKeys)) {
					$print = true;
				}
				if (!$print) {
					foreach ($category['children'] as $value1) {
						if (array_key_exists($value1, $usedCatIdsKeys)) {
							$print = true;
							break;
						}
					}
				}
				
				if ($print) {
					$categoriesXml->startElement('category');
					$categoriesXml->writeAttribute('id', $catId);
					if ($category['parent'] > 0) {
						$categoriesXml->writeAttribute('parentId', $category['parent']);
					}
					$categoriesXml->text($category['title']);
					$categoriesXml->endElement();
				}
			}

			$categoriesXml->endElement();
			$categoriesXml = $categoriesXml->outputMemory();////////////////////////////  end categories

			$xml->writeRaw($categoriesXml);/////////////////////////////////////////////  write categories
			$xml->writeRaw($offersXml);/////////////////////////////////////////////////  write offers

			if (!empty($usedProductIds)) {// https://yandex.ru/support/partnermarket/elements/promos.html
				set_time_limit(30);

				$promos = MG::createHook('YandexMarket_getPromos', array(), array(
					'categoryIds' 	=> $usedCatIds,
					'productIds' 	=> $usedProductIds,
					'productCodes' 	=> $usedProductCodes,
				));

				if (!empty($promos)) {
					$promoCounter = 0;
					set_time_limit(30);
					$promosXml = new XMLWriter();//////////////////////////////////////  start promos
					$promosXml->openMemory();
					$promosXml->setIndent(true);
					$promosXml->startElement('promos');

					foreach ($promos as $promo) {
						if (
							!empty($promo['type']) &&
							$promo['type'] == 'promo code' &&
							!empty($promo['start-date']) &&
							!empty($promo['end-date']) &&
							!empty($promo['promo-code']) &&
							!empty($promo['products']) &&
							(!empty($promo['products']['products']) || !empty($promo['products']['categories'])) &&
							!empty($promo['discount']) &&
							!empty($promo['discount']['unit']) &&
							!empty($promo['discount']['value']) &&
							(
								$promo['discount']['unit'] == 'percent' || 
								(
									$promo['discount']['unit'] == 'currency' &&
									!empty($promo['discount']['currency']) &&
									in_array($promo['discount']['currency'], self::$allowedCurrs)
								)
							)
						) {// скидочный промокод
							$promosXml->startElement('promo');
								$promosXml->writeAttribute('id', 'promo_'.$promoCounter);
								$promosXml->writeAttribute('type', 'promo code');
								$promosXml->writeElement('start-date', $promo['start-date']);
								$promosXml->writeElement('end-date', $promo['end-date']);
								if (!empty($promo['description'])) {
									$promosXml->startElement('description');
									$promosXml->writeRaw('<![CDATA[ '.htmlspecialchars($promo['description'], ENT_QUOTES | ENT_XML1, 'UTF-8').' ]]>');
									$promosXml->endElement();
								}
								if (!empty($promo['url'])) {
									$promosXml->writeElement('url', $promo['url']);
								}
								$promosXml->writeElement('promo-code', $promo['promo-code']);
								if ($promo['discount']['unit'] == 'percent') {
									$promosXml->startElement('discount');
									$promosXml->writeAttribute('unit', 'percent');
									$promosXml->text($promo['discount']['value']);
									$promosXml->endElement();
								} else {
									$promosXml->startElement('discount');
									$promosXml->writeAttribute('unit', 'currency');
									$promosXml->writeAttribute('currency', $promo['discount']['currency']);
									$promosXml->text($promo['discount']['value']);
									$promosXml->endElement();
								}

								$promosXml->startElement('purchase');
									if (!empty($promo['products']['products'])) {
										foreach ($promo['products']['products'] as $item) {
											$promosXml->startElement('product');
											$promosXml->writeAttribute('offer-id', $item);
											$promosXml->endElement();
										}
									}
									if (!empty($promo['products']['categories'])) {
										foreach ($promo['products']['categories'] as $item) {
											$promosXml->startElement('product');
											$promosXml->writeAttribute('category-id', $item);
											$promosXml->endElement();
										}
									}
								$promosXml->endElement();
							$promosXml->endElement();
							$promoCounter++;
						}
					}
					$promosXml->endElement();
					$promosXml = $promosXml->outputMemory();////////////////////////////  end promos
					if ($promoCounter) {
						$xml->writeRaw($promosXml);/////////////////////////////////////  write promos
					}
				}
			}

			$xml->endElement();/////////////////////////////////////////////////////////  end shop
			$xml->endElement();/////////////////////////////////////////////////////////  end yml_catalog
			$YML = $xml->outputMemory();
			return $YML;
		}
	}

	static function getProductDimensions($productId, $options) {
		$dimensionUnits = 'cm';
		if ($options['dimensionUnits']) {
			$dimensionUnits = $options['dimensionUnits'];
		}
		$unitsRate = [
			'mm' => 0.1,
			'cm' => 1,
			'm' => 100,
		];
		$rate = $unitsRate[$dimensionUnits];
		$dimensions = [
			'length',
			'width',
			'height',
		];
		$dimensionsVals = [];
		foreach ($dimensions as $dimension) {
			if (!$options[$dimension]) {
				return false;
			}
			if (!$propValue = floatval(self::getValueByIdProperty($productId, $options[$dimension]))) {
				return false;
			}
			$dimensionVal = round($rate * floatval($propValue), 3);
			if (!$dimensionVal) {
				$dimensionVal = 0.001;
			}
			$dimensionsVals[$dimension] = $dimensionVal;
		}
		return $dimensionsVals;
	}
}
