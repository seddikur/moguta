<?php
/**
 * Модель: OpFieldsOrder
 *
 * Класс Models_OpFieldsOrder реализует логику взаимодействия с дополнительными полями заказов.
 *
 * @author Гайдис Михаил
 * @package moguta.cms
 * @subpackage Model
 */
class Models_OpFieldsOrder {

	private $orderId;
	private $content;

	function __construct($orderId = null) {
		if($orderId) {
			$orderId = preg_replace('~\D~', '', $orderId);
			$this->orderId = (int)$orderId;

			$ids = array();
			$res = DB::query('SELECT * FROM '.PREFIX.'order_opt_fields_content WHERE order_id = '.DB::quoteInt($this->orderId));
			while($row = DB::fetchAssoc($res)) {
				$this->content[$row['field_id']]['id'] = $row['id'];
				$this->content[$row['field_id']]['value'] = $row['value'];
				$ids[] = $row['field_id'];
			}
			$res = DB::query('SELECT * FROM '.PREFIX.'order_opt_fields WHERE id IN ('.DB::quoteIN($ids).') OR droped = 0');
			while ($row = DB::fetchAssoc($res)) {
				$this->content[$row['id']]['name'] = $row['name'];
				$this->content[$row['id']]['type'] = $row['type'];
				$this->content[$row['id']]['droped'] = $row['droped'];
				$this->content[$row['id']]['placeholder'] = $row['placeholder'];
				if($row['vars']) {
					$this->content[$row['id']]['vars'] = unserialize(stripslashes($row['vars']));
				}
			}
		}
	}  

	/**
	 * Позволяет изменить значения дополнительных полей у выбранного заказа
	 * <code>
	 * $orderId = 1;
	 * $opFieldsM = new Models_OpFieldsOrder($orderId);
	 * // массив с дополнительным полями
	 * $data = array(
	 * 	1 => 'test',  // ключ являеться идентификатором дополнительного поля (id)
	 * 	2 => 'test2',
	 * );
	 * // будем считать что у этого заказа есть еще поле с id '3' и оно имеет значение 'defaultValue'
	 * // в дынном случае, это поле не изменит свое значение, 
	 * // а первое и второе примут значения из входящего массива
	 * $opFieldsM->fill($data);
	 * // при указании второго параметра в виду 'full', поле с id '3', 
	 * // примет значение '', так как мы не передали его в массиве
	 * $opFieldsM->fill($data, 'full');
	 * </code>
	 * @param $data array - массив со значениями дополнительных полей
	 * @param $mode string - режим работы метода (по умолчанию пусто), для полной перезаписи значений со сбросом текущих значений измениете на 'full'
	 * @return void
	 */
	public function fill($data = array(), $mode = false) {

		// если надо заполнить поля все, если не были переданы часть полей, то сбросить их
		if($mode == 'full' && !empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $key => $value) {
				if(in_array($value['type'], array('checkbox'))) {
					$this->content[$key]['value'] = 'false';
				} else {
					$this->content[$key]['value'] = '';
				}
			}
		}
		// заполнение полей
		if (!empty($data) && is_array($data)) {
			foreach ($data as $key => $item) {
				if(isset($item['type']) && in_array($item['type'], array('checkbox'))) {
					if($item && ($item != 'false')) {
						$this->content[$key]['value'] = 'true';
					} else {
						$this->content[$key]['value'] = 'false';
					}
				} else {
					$this->content[$key]['value'] = htmlspecialchars(htmlspecialchars_decode($item));
				}
			}
		}
	}

	/**
	 * Сохраняет текущее состояние объекта в базу данных, обычно используется после метода fill для фиксации изменений
	 * <code>
	 * $data = array(
	 * 	1 => 'test',  // ключ являеться идентификатором дополнительного поля (id)
	 * 	2 => 'test2',
	 * );
	 * $orderId = 1;
	 * $opFieldsM = new Models_OpFieldsOrder($orderId);
	 * $opFieldsM->fill($data);
	 * $opFieldsM->save();
	 * </code>
	 * @return void
	 */
	public function save() {
		if (!empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $key => $item) {
				if(!empty($item['id'])) {
					DB::query('UPDATE '.PREFIX.'order_opt_fields_content SET value = '.DB::quote($item['value']).' WHERE id = '.DB::quoteInt($item['id']));
				} else {
					DB::query('INSERT INTO '.PREFIX.'order_opt_fields_content (order_id, field_id, value) VALUES 
						('.DB::quoteInt($this->orderId).', '.DB::quoteInt($key).', '.DB::quote(isset($item['value'])?$item['value']:'').')');
					$this->content[$key]['id'] = DB::insertId();
				}
			}
		}
	}

	/**
	 * Метод возвращает верстку для модального окна с заказом в панели администрирования
	 * <code>
	 * $orderId = 1;
	 * $opFieldsM = new Models_OpFieldsOrder($orderId);
	 * $data = $opFieldsM->createCustomFieldToAdmin();
	 * viewData($data);
	 * </code>
	 * @return string
	 */
	public function createCustomFieldToAdmin() {

		$lang = MG::get('lang');
		$html = $toHtml = $hide = '';
		if (!empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $key => $item) {
				if (!isset($item['value'])) {$item['value'] = '';}
				$editableField = '';
				switch ($item['type']) {
					case 'input':
						$editableField = '<input name="'.$key.'" class="order-edit-display customField" type="text" value="'.$item['value'].'">';
						break;
					case 'textarea':
						$editableField = '<textarea name="'.$key.'" class="order-edit-display customField" type="text">'.$item['value'].'</textarea>';
						break;
					case 'checkbox':
						$editableField = '<div class="checkbox">
											<input type="checkbox" id="custom-'.$key.'" name="'.$key.'" '.($item['value']=="true"?'checked':'').' class="customField">
											<label for="custom-'.$key.'"></label>
										</div>';
						break;
					case 'select':
					case 'radiobutton':
						$editableField = '<select name="'.$key.'" class="order-edit-display customField">
							<option value="" '.($item['value']==''?'selected':'').'>Не выбрано</option>';
						
						$find = false;
						$variant = '';
						if (!empty($item['vars'])) {
              foreach ($item['vars'] as $variant) {
                if(htmlspecialchars_decode($item['value']) == $variant) $find = true;
                $editableField .= '<option value="'.htmlspecialchars($variant).'" '.
                  (htmlspecialchars_decode($item['value']) == $variant ? 'selected' : '')
                  .'>'.htmlspecialchars($variant).'</option>';
              }
            }

						if(!$find && $item['value'] != '') $editableField .= '<option value="'.htmlspecialchars($variant).'" selected>'.htmlspecialchars($variant).'</option>';
						$editableField .= '</select>';
						break;
					case 'file':
						if (is_file(SITE_DIR.'uploads'.DS.'order'.DS.$this->orderId.DS.$item['value'])) {
							$editableField = '<a class="downloadOrderFile" title="'.$lang['SYSTEM_SETTINGS_3'].'" href="'.SITE.'/mg-admin/?downloadOrderAttachment='.$this->orderId.'_'.$key.'">'.$item['value'].'</a>'.
								'<i class="fa fa-times deleteOrderFile order-edit-display" title="'.$lang['DELETE'].'" data-file="'.$this->orderId.'_'.$key.'"></i>';
						} elseif($item['value']) {
							$editableField = '<span>Файл удалён или нет прав на запись';
						} else {
							$editableField = '<a class="js-addOrderFile order-edit-display" data-op-id="'.$key.'" title="Добавить файл" href="javascript:void(0);"><i class="fa fa-plus"></i> Добавить файл</a><input name="'.$key.'" class="order-edit-display customField" type="hidden" value="'.$item['value'].'">';
						}
						break;
					
					default:
						$editableField = '<input name="'.$key.'" class="order-edit-display customField" type="text" value="'.$item['value'].'">';
						break;
				}

				if($item['type'] == 'checkbox' || $item['type'] == 'file') {
//				    viewData($item);
					$inFields = $editableField;
				} else {
					$inFields = '<span style="display: block" tooltip="Для редактирования заказа нажмите кнопку «Редактировать» в левом верхнем углу."><input type="text" disabled class="order-edit-visible" value="'.$item['value'].'"></span>'.$editableField;
				}

				$toHtml .= '<div class="row sett-line">
                                <div class="row">
                                    <div class="large-6 medium-12 columns">
								        <label>'.$item['name'].($item['droped']==1?' ('.$lang['DELETED'].')':'').'</label>
							        </div>
                                    <div class="large-6 medium-12 columns">
                                        '.$inFields.'
                                    </div>
                                </div>
                            </div>';
			}
		}

		if($toHtml) {
			$html = $toHtml;
		}

		return $html;
	}

	/**
	 * Метод возвращает массив с данными о дополнительных полях выбранного заказа
	 * <code>
	 * $orderId = 1;
	 * $opFieldsM = new Models_OpFieldsOrder($orderId);
	 * // выведет все дополнительные поля заказа
	 * $data = $opFieldsM->get();
	 * viewData($data);
	 * // выведет только указанное дополнительное поле
	 * $fieldId = 1;
	 * $data = $opFieldsM->get($fieldId);
	 * viewData($data);
	 * </code>
	 * @param $elem int - идентификатор поля
	 * @return array
	 */
	public function get($elem = null) {
		if($elem) {
			return $this->content[$elem];
		} else {
			return $this->content;
		}
	}

	/**
	 * Метод возвращает массив с данными о дополнительных полях выбранного заказа в более понятном для человека виде
	 * <code>
	 * $orderId = 1;
	 * $opFieldsM = new Models_OpFieldsOrder($orderId);
	 * // выведет все дополнительные поля заказа
	 * $data = $opFieldsM->getHumanView();
	 * viewData($data);
	 * // выведет только указанное дополнительное поле
	 * $fieldId = 1;
	 * $data = $opFieldsM->getHumanView($fieldId);
	 * viewData($data);
	 * // при указании второго атрибута, выведет сразу строку со значением, а не массив
	 * </code>
	 * @param $elem string|int - если 'all', то вернет все поля, если указать id поля, то только его значение
	 * @param $onlyVal bool - если указать 'true', то вернет строку со значением
	 * @return array|string
	 */
	public function getHumanView($elem = 'all', $onlyVal = false) {
		if($elem != 'all') {
			if($onlyVal) {
				return $this->setHumanViewValue($this->content[$elem]);
			} else {
				$tmp = $this->content;
				$tmp['value'] = $this->setHumanViewValue($this->content[$elem]);
				return $tmp;
			}
		} else {
			$res = array();
			if (!empty($this->content) && is_array($this->content)) {
				foreach ($this->content as $key => $item) {
					if($onlyVal) {
						$res[$key] = $this->setHumanViewValue($this->content[$key]);
					} else {
						$tmp = $this->content[$key];
						$tmp['value'] = $this->setHumanViewValue($this->content[$key]);
						$res[$key] = $tmp;
					}
				}
			}
			return $res;
		}
	}
	
	private function setHumanViewValue($data) {
		$lang = MG::get('lang');
		if($data['type'] == 'checkbox') {
			if(isset($data['value']) && $data['value'] == 'true') {
				return $lang['YES'];
			} else {
				return $lang['NO'];
			}
		} elseif($data['type'] == 'radiobutton') {
			if(empty($data['value'])) {
				return $lang['NO_SELECT'];
			} else {
				return $data['value'];
			}
		} else {
			if (!isset($data['value'])) {
				return '';
			}
			return $data['value'];
		}
	}

	/**
	 * Метод оставляет из массива значений доп.полей только те, которые активны
	 * @access private
	 * @param array $opFields
	 * @return array
	 */
	public function fixFieldsForMail($opFields = array()) {
		$orderFormFields = MG::getSetting('orderFormFields',true);
		$result = array();
		foreach ($opFields as $key => $value) {
			if (!empty($orderFormFields['opf_'.$key]['active'])) {
				$result[$this->content[$key]['name']] = $value;
			}
		}
		return $result;
	}

	/**
	 * Возвращает только значения полей, без дополнительной информации (ключ id поля и его значение)
	 * <code>
	 * $orderId = 1;
	 * $opFieldsM = new Models_OpFieldsOrder($orderId);
	 * $data = $opFieldsM->getValues();
	 * viewData($data);
	 * </code>
	 * @return array
	 */
	public function getValues() {
		$result = array();
		if (!empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $key => $item) {
	          	$result[$key] = $item['value'];
	        }
	    }
        return $result;
	}

	/**
	 * Проверяет поле на сущесвование перед выводом в таблице заказов, отсеивает из списка то, чего уже нет 
	 * (только для панели управления)
	 * <code>
	 * $opFieldsM = new Models_OpFieldsOrder('get');
	 * $array = array(1, 2, 3);
	 * $data = $opFieldsM->checkAdminColumnsTable($array);
	 * viewData($data);
	 * </code>
	 * @return array
	 */
	public function checkAdminColumnsTable($data = array()) {
		$ids = array();
		if (!empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $id => $item) {
				$ids[] = $id;
			}
		}
		if (empty($data) || empty($ids) || !is_array($data) || !is_array($ids)) {
			return array();
		}
		return array_intersect($data, $ids);
	}


	// ============================================================================================
	// ============================================================================================
	// ============================================================================================

	/**
	 * Получает список дополнительных полей
	 * <code>
	 * $data = Models_OpFieldsOrder::getFields();
	 * viewData($data);
	 * </code>
	 * @return array
	 */
	public static function getFields() {
		$result = MG::get('opOrderGetFields');
		if ($result === null) {
			$result = array();
			$res = DB::query('SELECT * FROM '.PREFIX.'order_opt_fields WHERE droped = 0 ORDER BY sort ASC');
			while ($row = DB::fetchAssoc($res)) {
				$row['vars'] = unserialize(stripslashes($row['vars']));
				$result[$row['id']] = $row;
			}
			MG::set('opOrderGetFields', $result);
		}

		return $result;
	} 

	/**
	 * Метод позвоялет редактировать дополнительные поля заказов
	 * Принимает список полей, которые нужно сохранить, если существующие поля не были указаны, то они будут удалены
	 * Возвращает идентификаторы новых полей
	 * <code>
	 * $data = array(
	 * 	array(
	 *             'name' => 'test-input',
	 *             'type' => 'input',
	 *             'sort' => 0,
	 *             'id' => 1,
	 *         ),
	 *     array(
	 *             'name' => 'test-textarea',
	 *             'type' => 'textarea',
	 *             'sort' => 1,
	 *             'id' => 2,
	 *         ),
	 *     array(
	 *             'name' => 'test-checkbox',
	 *             'type' => 'checkbox',
	 *             'sort' => 2,
	 *             'id' => 3,
	 *         ),
	 *     array(
	 *             'name' => 'test-radio',
	 *             'type' => 'radiobutton',
	 *             'sort' => 3,
	 *             'id' => 4,
	 *             'vars' => array(
	 *                     0 => 1,
	 *                     1 => 2,
	 *                     2 => 3,
	 *                 )
	 *         ),
	 *     array(
	 *             'name' => 'test-select',
	 *             'type' => 'select',
	 *             'sort' => 4,
	 *             'id' => 5,
	 *             'vars' => array(
	 *                     0 => 'q1',
	 *                     1 => 'q2',
	 *                     2 => 'q3',
	 *                 )
	 *         ) 
	 * );
	 * Models_OpFieldsOrder::saveFields($data);
	 * </code>
	 * @param $data array - массив с полями для сохранения
	 * @return array
	 */
	public static function saveFields($data = array()) {
		$droped = $result = array();
		$res = DB::query('SELECT id FROM '.PREFIX.'order_opt_fields WHERE droped = 0');
		while($row = DB::fetchAssoc($res)) {
			$droped['opf_'.$row['id']] = 'opf_'.$row['id'];
		}

		DB::query('UPDATE '.PREFIX.'order_opt_fields SET `droped` = 1');

		foreach ($data as $key => $item) {
			$item['name'] = htmlspecialchars($item['name']);

			if(!empty($item['vars'])) {
				foreach ($item['vars'] as $keyV => $valueV) {
					$item['vars'][$keyV] = htmlspecialchars(htmlspecialchars_decode($valueV));
				}
				$serDat = addslashes(serialize($item['vars']));
			} else {
				$serDat = '';
			}

			if(!$item['id']) {
				$res = DB::query('SELECT id FROM '.PREFIX.'order_opt_fields WHERE name = '.DB::quote($item['name']).' AND droped = 0');
				if($row = DB::fetchAssoc($res)) {
					$item['id'] = $row['id'];
				} else {
					if(!$item['sort']) {
						$res = DB::query('SELECT MAX(sort) FROM '.PREFIX.'order_opt_fields');
						if($row = DB::fetchAssoc($res)) {
							$item['sort'] = $row['MAX(sort)'] + 1;
						}
					}
				}
			} else {
				unset($droped['opf_'.$item['id']]);
			}
			
			if(!$item['id']) {
				DB::query('INSERT INTO '.PREFIX.'order_opt_fields (`name`, `type`, `vars`, `sort`, `placeholder`) VALUES 
					('.DB::quote($item['name']).', '.DB::quote($item['type']).', '.DB::quote($serDat).', '.DB::quoteInt($item['sort']).',
						'.DB::quote($item['placeholder']).')');
				$result[$key] = DB::insertId();
			} else {
				DB::query('UPDATE '.PREFIX.'order_opt_fields SET `name` = '.DB::quote($item['name']).', `type` = '.DB::quote($item['type']).', 
					`vars` = '.DB::quote($serDat).', `droped` = 0, placeholder = '.DB::quote($item['placeholder']).',
					`sort` = '.DB::quoteInt($item['sort']).' WHERE id = '.DB::quoteInt($item['id']));
			}
		}

		// удаление лишних полей, которые не имеют никаких привязок
		DB::query('DELETE FROM '.PREFIX.'order_opt_fields WHERE droped = 1 AND 
			(SELECT COUNT(oofc.id) FROM '.PREFIX.'order_opt_fields_content AS oofc WHERE oofc.field_id = '.PREFIX.'order_opt_fields.id) = 0');

		if (!empty($droped)) {
			self::clearDropedFieldsFromOrderForm($droped);
		}

		return $result;
	}

	// ============================================================================================
	// =================================для редактора формы заказа=================================
	// ============================================================================================

	/**
	 * Очистка формы заказа от удаленных доп полей заказа
	 * @access private
	 * @param array $droped
	 * @return void
	 */ 
	static function clearDropedFieldsFromOrderForm($droped) {
		$rebuildJs = false;
		$fields = MG::getSetting('orderFormFields',true);
		foreach ($fields as $fieldName => $field) {
			if (in_array($fieldName, $droped)) {
				unset($fields[$fieldName]);
				$rebuildJs = true;
				continue;
			}
			if (!empty($field['conditions'])) {
				foreach ($field['conditions'] as $key => $condition) {
					if ($condition['type'] == 'orderField' && in_array($condition['fieldName'], $droped)) {
						unset($fields[$fieldName]['conditions'][$key]);
						if (empty($fields[$fieldName]['conditions'])) {
							$fields[$fieldName]['conditionType'] = 'always';
							$rebuildJs = true;
						}
					}
				}
			}
		}

		if ($rebuildJs) {
			uasort($fields, function($a, $b) {if ($a['sort'] == $b['sort']) {return 0;}return ($a['sort'] < $b['sort']) ? -1 : 1;});
			MG::setOption('orderFormFields', $fields, true);
			self::rebuildJs($fields,true);
		}	
	}

	/**
	 * Верстка для условия в админке
	 * @access private
	 * @param string $fieldName
	 * @return void
	 */
	static function getOrderFieldsSelect($fieldName) {
		$options = array();
		if ($fieldName == 'customer') {
			$options[] = '<option value="fiz">Физическое лицо</option>';
			$options[] = '<option value="yur">Юридическое лицо</option>';
		} elseif (strpos($fieldName, 'opf_') === 0) {
			$opfId = str_replace('opf_', '', $fieldName);
			$res = DB::query("SELECT `vars` FROM `".PREFIX."order_opt_fields` WHERE `id` = ".DB::quoteInt($opfId));
			if ($row = DB::fetchAssoc($res)) {
				$vars = unserialize(stripslashes($row['vars']));
				foreach ($vars as $value) {
					$options[] = '<option value="'.$value.'">'.$value.'</option>';
				}
			}
		} elseif (strpos($fieldName, 'uopf_') === 0) {
			$opfId = str_replace('uopf_', '', $fieldName);
			$res = DB::query("SELECT `vars` FROM `".PREFIX."user_opt_fields` WHERE `id` = ".DB::quoteInt($opfId));
			if ($row = DB::fetchAssoc($res)) {
				$vars = unserialize(stripslashes($row['vars']));
				foreach ($vars as $value) {
					$options[] = '<option value="'.$value.'">'.$value.'</option>';
				}
			}
		}
		
		return '<select name="select" multiple size="'.count($options).'">'.implode('', $options).'</select>';
	}

	/**
	 * Сохранение формы заказа
	 * @access private
	 * @return void
	 */ 
	static function saveOrderFormFields() {
		$fields = MG::getSetting('orderFormFields',true);
		foreach ($fields as $key => $opf) {
			$fields[$key]['active'] = 0;
			$fields[$key]['sort'] = -1;
		}

		$rebuildJs = $addEnctype = false;

		if (!empty($_POST['data']) && is_array($_POST['data'])) {
			foreach($_POST['data'] as $key => $opf) {
				$fields[$key]['active'] = $_POST['data'][$key]['active'];
				$fields[$key]['sort'] = $_POST['data'][$key]['sort'];
				$fields[$key]['required'] = $_POST['data'][$key]['required'];

				if (empty($opf['conditionType']) && empty($fields[$key]['conditionType'])) {
					$fields[$key]['conditionType'] = 'always';
				}
				if ($fields[$key]['conditionType'] != 'always') {
					$rebuildJs = true;
				}
				if ($opf['type'] == 'file' && $opf['active'] != 0) {
					$addEnctype = true;
				}
			}
		}

		foreach ($fields as $key => $opf) {
			if ($opf['active'] == 0) {
				unset($fields[$key]);
			}
		}

		uasort($fields, function($a, $b) {if ($a['sort'] == $b['sort']) {return 0;}return ($a['sort'] < $b['sort']) ? -1 : 1;});
		MG::setOption('orderFormFields', $fields, true);

		if ($rebuildJs) {
			self::rebuildJs($fields, $addEnctype);
		}
	}

	/**
	 * Возвращает название доп. поля
	 * <code>
	 * $data = Models_OpFieldsOrder::getFieldTitle('address');
	 * viewData($data); //'Адрес доставки'
	 * </code>
	 * @param string $fieldName
	 * @return string
	 */
	static function getFieldTitle($fieldName) {
		switch ($fieldName) {
			case 'email': return lang('orderPhEmail');
			case 'phone': return lang('phone');
			case 'fio': return lang('fio');
			case 'info': return lang('orderPhComment');
			case 'address': return lang('orderPhAdres');
			case 'customer': return lang('orderPayer');

			case 'fio_sname': return lang('lname');
			case 'fio_name': return lang('fname');
			case 'fio_pname': return lang('pname');

			case 'address_index': return lang('orderPhAddressIndex');
			case 'address_country': return lang('orderPhAddressCountry');
			case 'address_region': return lang('orderPhAddressRegion');
			case 'address_city': return lang('orderPhAddressCity');
			case 'address_street': return lang('orderPhAddressStreet');
			case 'address_house': return lang('orderPhAddressHouse');
			case 'address_flat': return lang('orderPhAddressFlat');

			case 'yur_info_nameyur': return lang('orderPhNameyur');
			case 'yur_info_adress': return lang('orderPhAdress');
			case 'yur_info_inn': return lang('orderPhInn');
			case 'yur_info_kpp': return lang('orderPhKpp');
			case 'yur_info_bank': return lang('orderPhBank');
			case 'yur_info_bik': return lang('orderPhBik');
			case 'yur_info_ks': return lang('orderPhKs');
			case 'yur_info_rs': return lang('orderPhRs');

			default: break;
		}
		if (strpos($fieldName, 'opf_') === 0) {
			$fields = self::getFields();
			$id = str_replace('opf_', '', $fieldName);
			foreach ($fields as $field) {
				if ($field['id'] == $id) {
					return $field['name'];
				}
			}
		}
		return $fieldName;
	}

	/**
	 * Возвращает массив с полями заказа для страницы оформления заказа
	 *
	 * @return void
	 */
	static function getOrderFormPublicArr() {
		$fields = MG::getSetting('orderFormFields',true);
		$fields = self::fixFieldTypes($fields);
		
		$fields['customer']['vars'] = array(array('val'=>'fiz','text'=>lang('orderFiz')),array('val'=>'yur','text'=>lang('orderYur')));

		foreach (array(
				'email',
				'phone',
				'fio',
				'info',
				'address',
				'customer',

				'fio_sname',
				'fio_name',
				'fio_pname',

				'address_index',
				'address_country',
				'address_region',
				'address_city',
				'address_street',
				'address_house',
				'address_flat',

				'yur_info_nameyur',
				'yur_info_adress',
				'yur_info_inn',
				'yur_info_kpp',
				'yur_info_bank',
				'yur_info_bik',
				'yur_info_ks',
				'yur_info_rs',
			) as $field) {
			$fields[$field]['title'] = self::getFieldTitle($field);
		}

		$tmp = self::getFields();

		foreach ($tmp as $opf) {
			$fields['opf_'.$opf['id']]['type'] = $opf['type'];
			if ($opf['type'] == 'select' || $opf['type'] == 'radiobutton') {
				$fields['opf_'.$opf['id']]['vars'] = $opf['vars'];
			}
			$fields['opf_'.$opf['id']]['title'] = $opf['name'];
			$fields['opf_'.$opf['id']]['placeholder'] = $opf['placeholder'];
		}
		uasort($fields, function($a, $b) {if ($a['sort'] == $b['sort']) {return 0;}return ($a['sort'] < $b['sort']) ? -1 : 1;});
		$user = null;
		$orderFormFieldsIfAny = array();
		foreach ($fields as $key => $field) {
			if (!$field['active'] || ($field['conditionType'] != 'always' && empty($field['conditions']))) {
				unset($fields[$key]);
			}
			if ($field['type'] == 'select' || $field['type'] == 'radiobutton') {
				foreach ($field['vars'] as $varsKey => $varsVal) {
					if (!is_array($varsVal)) {
						$fields[$key]['vars'][$varsKey] = array('val'=>htmlspecialchars($varsVal),'text'=>htmlspecialchars($varsVal));
					}
				}
			}
			if (!$field['title']) {
				$fields[$key]['title'] = lang('orderFormPh_'.$key);
			}
			unset($fields[$key]['sort']);
			unset($fields[$key]['active']);
			if ($field['required']) {
				$fields[$key]['required'] = 'required';
			} else{
				$fields[$key]['required'] = '';
			}
			
			if ($field['conditionType'] == 'ifAll') {
				foreach ($field['conditions'] as $condition) {
					if ($condition['type'] == 'userGroup' && !in_array($_SESSION['user']->role, $condition['value'])) {
						unset($fields[$key]);
						continue;
					}
					if ($condition['type'] == 'userAddField') {
						if (!$user) {
							$user = new Models_OpFieldsUser($_SESSION['user']->id);
						}
						$opfId = str_replace('uopf_', '', $condition['fieldName']);
						$tmp = $user->get($opfId);
						if ($condition['value'] == 'notEmpty' && !preg_match('/\S/', $tmp['value'])) {
							unset($fields[$key]);
							continue;
						}
						if (is_array($condition['value']) && !in_array($tmp['value'], $condition['value'])) {
							unset($fields[$key]);
							continue;
						}
						if (($condition['value'] == 'checked' && $tmp['value'] !== 'true') ||
							($condition['value'] == 'unchecked' && $tmp['value'] !== 'false')) {
							unset($fields[$key]);
							continue;
						}
					}
				}
			}
			elseif ($field['conditionType'] == 'ifAny') {
				foreach ($field['conditions'] as $condition) {
					if ($condition['type'] == 'userGroup' && in_array($_SESSION['user']->role, $condition['value'])) {
						$orderFormFieldsIfAny[] = $key;
						continue;
					}
					if ($condition['type'] == 'userAddField') {
						if (!$user) {
							$user = new Models_OpFieldsUser($_SESSION['user']->id);
						}
						$opfId = str_replace('uopf_', '', $condition['fieldName']);
						$tmp = $user->get($opfId);
						if ($condition['value'] == 'notEmpty' && preg_match('/\S/', $tmp['value'])) {
							$orderFormFieldsIfAny[] = $key;
							continue;
						}
						if (is_array($condition['value']) && in_array($tmp['value'], $condition['value'])) {
							$orderFormFieldsIfAny[] = $key;
							continue;
						}
						if (($condition['value'] == 'checked' && $tmp['value'] === 'true') ||
							($condition['value'] == 'unchecked' && $tmp['value'] === 'false')) {
							$orderFormFieldsIfAny[] = $key;
							continue;
						}
					}
				}
			}
		}
		$orderFormFieldsIfAny = array_unique($orderFormFieldsIfAny);
		MG::addJsVar('orderFormFieldsIfAny', $orderFormFieldsIfAny);
		return $fields;
	}

	/**
	 * Возвращает типы стандартных полей заказа
	 * @access private
	 * @param array $fields
	 * @return array
	 */
	static function fixFieldTypes($fields) {
		foreach (array(
			'email',
			'phone',
			'fio',
			'fio_sname',
			'fio_name',
			'fio_pname',
			'address',
			'address_index',
			'address_country',
			'address_region',
			'address_city',
			'address_street',
			'address_house',
			'address_flat',
			'yur_info_nameyur',
			'yur_info_adress',
			'yur_info_inn',
			'yur_info_kpp',
			'yur_info_bank',
			'yur_info_bik',
			'yur_info_ks',
			'yur_info_rs',
		) as $val) {
			if (isset($fields[$val]) && !isset($fields[$val]['type'])) {
				$fields[$val]['type'] = 'input';
			}
		}

		foreach (array(
			'info',
		) as $val) {
			if (isset($fields[$val]) && !isset($fields[$val]['type'])) {
				$fields[$val]['type'] = 'textarea';
			}
		}

		foreach (array(
			'customer',
		) as $val) {
			if (isset($fields[$val]) && !isset($fields[$val]['type'])) {
				$fields[$val]['type'] = 'select';
			}
		}

		return $fields;
	}

	/**
	 * Возвращает список обязательных к заполнению полей для проверки при оформлении заказа
	 *
	 * @return array
	 */
	static function getRequiredFields() {
		$fields = MG::getSetting('orderFormFields',true);

		$requiredFields = array();
		foreach ($fields as $fieldName => $field) {
			if (!$field['required']) {continue;}
			if ($field['conditionType'] == 'always') {
				$requiredFields[] = $fieldName;
				continue;
			}
			if ($field['conditionType'] == 'ifAny') {
				foreach ($field['conditions'] as $condition) {
					if ($condition['type'] == 'userGroup' && in_array($_SESSION['user']->role, $condition['value'])) {
						$requiredFields[] = $fieldName;
						continue;
					}
					elseif ($condition['type'] == 'userAddField') {
						if (empty($user)) {
							$user = new Models_OpFieldsUser($_SESSION['user']->id);
						}
						$opfId = str_replace('uopf_', '', $condition['fieldName']);
						$tmp = $user->get($opfId);
						if ($condition['value'] == 'notEmpty' && preg_match('/\S/', $tmp['value'])) {
							$requiredFields[] = $fieldName;
							continue;
						}
						if (is_array($condition['value']) && in_array($tmp['value'], $condition['value'])) {
							$requiredFields[] = $fieldName;
							continue;
						}
						if (($condition['value'] == 'checked' && $tmp['value'] === 'true') ||
							($condition['value'] == 'unchecked' && $tmp['value'] === 'false')) {
							$requiredFields[] = $fieldName;
							continue;
						}
					}
					elseif ($condition['type'] == 'orderField' && $condition['value'] == 'notEmpty') {
						if (isset($_POST[$condition['fieldName']]) && $_POST[$condition['fieldName']] !== '') {
							$requiredFields[] = $fieldName;
							continue;
						}
					}
					elseif ($condition['type'] == 'orderField' && is_array($condition['value'])) {
						if (in_array($_POST[$condition['fieldName']], $condition['value'])) {
							$requiredFields[] = $fieldName;
							continue;
						}
					}
					elseif ($condition['type'] == 'delivery') {
						if (in_array(intval($_POST['delivery']), $condition['value'])) {
							$requiredFields[] = $fieldName;
							continue;
						}
					}
					elseif ($condition['type'] == 'orderField' && $condition['value'] == 'checked' && isset($_POST[$condition['fieldName']])) {
						$requiredFields[] = $fieldName;
						continue;
					}
					elseif ($condition['type'] == 'orderField' && $condition['value'] == 'unchecked' && !isset($_POST[$condition['fieldName']])) {
						$requiredFields[] = $fieldName;
						continue;
					}
				}
			}
			if ($field['conditionType'] == 'ifAll') {
				$conditionPassed = true;
				foreach ($field['conditions'] as $condition) {
					if ($condition['type'] == 'userGroup' && !in_array($_SESSION['user']->role, $condition['value'])) {
						$conditionPassed = false;
					} 
					if ($condition['type'] == 'userAddField') {
						if (empty($user)) {
							$user = new Models_OpFieldsUser($_SESSION['user']->id);
						}
						$opfId = str_replace('uopf_', '', $condition['fieldName']);
						$tmp = $user->get($opfId);
						if ($condition['value'] == 'notEmpty' && !preg_match('/\S/', $tmp['value'])) {
							$conditionPassed = false;
						}
						if (is_array($condition['value']) && !in_array($tmp['value'], $condition['value'])) {
							$conditionPassed = false;
						}
						if (($condition['value'] == 'checked' && $tmp['value'] !== 'true') ||
							($condition['value'] == 'unchecked' && $tmp['value'] !== 'false')) {
							$conditionPassed = false;
						}
					}
					if ($condition['type'] == 'orderField' && $condition['value'] == 'notEmpty') {
						if (!isset($_POST[$condition['fieldName']]) || $_POST[$condition['fieldName']] === '') {
							$conditionPassed = false;
						}
					}
					if ($condition['type'] == 'orderField' && is_array($condition['value'])) {
						if (!in_array($_POST[$condition['fieldName']], $condition['value'])) {
							$conditionPassed = false;
						}
					}
					if ($condition['type'] == 'delivery') {
						if (!in_array(intval($_POST['delivery']), $condition['value'])) {
							$conditionPassed = false;
						}
					}
					if ($condition['type'] == 'orderField' && $condition['value'] == 'checked' && !isset($_POST[$condition['fieldName']])) {
						$conditionPassed = false;
					}
					if ($condition['type'] == 'orderField' && $condition['value'] == 'unchecked' && isset($_POST[$condition['fieldName']])) {
						$conditionPassed = false;
					}
				}
				if ($conditionPassed) {
					$requiredFields[] = $fieldName;
				}
			}
		}
		$requiredFields = array_unique($requiredFields);
		return $requiredFields;
	}

	/**
	 * Генерация js-скрипта для формы заказа
	 * @access private
	 * @param $fields доп. поля
	 * @param $addEnctype файлы 
	 * @return void 
	 */ 
	static function rebuildJs($fields, $addEnctype=false) {
		$fields = self::fixFieldTypes($fields);
		$writeFile = false;
		$js = 'var orderForm = (function () {'.PHP_EOL.
			  '	return {'.PHP_EOL.
			  '		init: function() {'.PHP_EOL;

		$changeOnDelivery = false;
		$changeOnFields = $changeString = array(); 
		foreach ($fields as $fieldName => $field) {
			if (!empty($field['conditionType']) && ($field['conditionType'] == 'ifAll' || $field['conditionType'] == 'ifAny') && !empty($field['conditions']) && is_array($field['conditions'])) {
				foreach ($field['conditions'] as $key => $condition) {
					if ($condition['type'] != 'delivery' && (!array_key_exists($condition['fieldName'], $fields) || (is_array($condition['value']) && empty($condition['value'])))) {
						unset($fields[$fieldName]['conditions'][$key]);
						continue;
					}
					if ($condition['type'] == 'orderField') {
						$writeFile = true;
						$changeOnFields[] = $condition['fieldName'];
					}
					if ($condition['type'] == 'delivery') {
						$writeFile = true;
						$changeOnDelivery = true;
					}
				}
			}
		}

		$changeOnFields = array_unique($changeOnFields);

		if ($changeOnDelivery) {
			$changeString[] = 'form[action*="/order?creation=1"] input[name="delivery"]'; 
		}

		foreach ($changeOnFields as $field) {
			$changeString[] = 'form[action*="/order?creation=1"] [name='.$field.']';
		}

		$changeString = implode(', ', $changeString);

		if ($changeString) {
			$js .= '			$(\'body\').on(\'change\', \''.$changeString.'\', function() {'.PHP_EOL;
			$js .= '				orderForm.redrawForm();'.PHP_EOL;
			$js .= '			});'.PHP_EOL;
			$js .= '			$(\'form[action*="/order?creation=1"] *\').removeAttr(\'data-delivery-address\');'.PHP_EOL;
			$js .= '			orderForm.redrawForm();'.PHP_EOL;
			$js .= '		},'.PHP_EOL;
			$js .= '		redrawForm: function() {'.PHP_EOL;
			$js .= '			var delivId = 0;'.PHP_EOL;
			$js .= '			if ($(\'form[action*="/order?creation=1"] input[name=delivery]:checked\').length) {'.PHP_EOL;
			$js .= '				delivId = $(\'form[action*="/order?creation=1"] input[name=delivery]:checked\').val();'.PHP_EOL;
			$js .= '			}'.PHP_EOL;
		}

		foreach ($fields as $fieldName => $field) {
			$condiStr = array();
			$show = $hide = '';
			if (!empty($field['conditions']) && is_array($field['conditions'])) {
				foreach ($field['conditions'] as $condition) {
					if ($condition['type'] == 'orderField' && $condition['value'] == 'notEmpty') {
						$condiStr[] = '$(\'form[action*="/order?creation=1"] [name='.$condition['fieldName'].']:first\').val() !== \'\'';
					}
					if ($condition['type'] == 'orderField' && is_array($condition['value'])) {
						$condiStr[] = '$.inArray($(\'form[action*="/order?creation=1"] [name='.$condition['fieldName'].']:first\').val(), [\''.implode('\',\'', $condition['value']).'\']) !== -1';
					}
					if ($condition['type'] == 'orderField' && $condition['value'] == 'checked') {
						$condiStr[] = '$(\'form[action*="/order?creation=1"] [name='.$condition['fieldName'].']:first\').is(\':checked\')';
					}
					if ($condition['type'] == 'orderField' && $condition['value'] == 'unchecked') {
						$condiStr[] = '!$(\'form[action*="/order?creation=1"] [name='.$condition['fieldName'].']:first\').is(\':checked\')';
					}
					if ($condition['type'] == 'delivery') {
						$condiStr[] = '$.inArray(parseInt(delivId), ['.implode(',', $condition['value']).']) !== -1';
					}
				}
			}

			if (!isset($field['type']) || $field['type'] != 'set') {
				$show = '				$(\'form[action*="/order?creation=1"] [name='.$fieldName.']\').prop(\'disabled\', false);'.PHP_EOL.
						'				$(\'form[action*="/order?creation=1"] [name='.$fieldName.']\').closest(\'.js-orderFromItem\').show();'.PHP_EOL;
				$hide = '				$(\'form[action*="/order?creation=1"] [name='.$fieldName.']\').prop(\'disabled\', true);'.PHP_EOL.
						'				$(\'form[action*="/order?creation=1"] [name='.$fieldName.']\').closest(\'.js-orderFromItem\').hide();'.PHP_EOL;
			}

			if (!empty($field['conditionType']) && $field['conditionType'] == 'ifAll' && !empty($condiStr)) {
				$js .= '			if('.implode(' && ', $condiStr).') {//'.$fieldName.PHP_EOL;
				$js .= 					$show;
				$js .= '			} else {'.PHP_EOL;
				$js .= 					$hide;
				$js .= '			}'.PHP_EOL;
			}
			elseif (!empty($field['conditionType']) && $field['conditionType'] == 'ifAny') {
				$condiStr[] = '$.inArray(\''.$fieldName.'\', window.orderFormFieldsIfAny) !== -1';
				$js .= '			if('.implode(' || ', $condiStr).') {//'.$fieldName.PHP_EOL;
				$js .= 					$show;
				$js .= '			} else {'.PHP_EOL;
				$js .= 					$hide;
				$js .= '			}'.PHP_EOL;
			}
		}

		$js .= '		},'.PHP_EOL.
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
		'         				break;'.PHP_EOL. 
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
		'         				break;'.PHP_EOL. 
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
		'         				break;'.PHP_EOL.                                                                                                                                                             
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
		'            					break;'.PHP_EOL.                                                                                                                                        
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
			'	if (location.pathname.indexOf(\'/order\') > -1) {'.PHP_EOL;
			if ($addEnctype) {
				$js .= '		$(\'form[action*="/order?creation=1"]\').attr(\'enctype\', \'multipart/form-data\');'.PHP_EOL;
				$js .= '		$(\'body\').on(\'change\', \'form[action*="/order?creation=1"] .orderFileContainer .orderFileInput\', function(e) {'.PHP_EOL;
				$js .= '			$(this).closest(\'.orderFileContainer\').find(\'.orderFileText\').text(e.target.files[0].name);'.PHP_EOL;
				$js .= '			$(this).closest(\'.orderFileContainer\').find(\'.removeOrderFile\').show();'.PHP_EOL;
				$js .= '		});'.PHP_EOL;
				$js .= '		$(\'body\').on(\'click\', \'form[action*="/order?creation=1"] .orderFileContainer .removeOrderFile\', function() {'.PHP_EOL;
				$js .= '			$(this).closest(\'.orderFileContainer\').find(\'.orderFileInput\').val(\'\');'.PHP_EOL;
				$js .= '			var elem = $(this).closest(\'.orderFileContainer\').find(\'.orderFileText\');'.PHP_EOL;
				$js .= '			elem.text(elem.data(\'initialtext\'));'.PHP_EOL;
				$js .= '			$(this).hide();'.PHP_EOL;
				$js .= '			orderForm.redrawForm();'.PHP_EOL;
				$js .= '			return false;'.PHP_EOL;
				$js .= '		});'.PHP_EOL;
			}
		$js .='		orderForm.init();'.PHP_EOL.
			'	}'.PHP_EOL.
			'});';

		if (!$writeFile) {
			$js = '';
		}

		$orderFormFile = 'uploads'.DS.'generatedJs'.DS.'order_form.js';

		file_put_contents(SITE_DIR.$orderFormFile, $js);

		if (MG::getSetting('cacheCssJs') == 'true' || MG::getSetting('useAbsolutePath') == 'true') {
			MG::clearMergeStaticFile();
		}
	}

	/**
	 * Сортировка доп. полей
	 * @access private
	 * @param array $orderFormFields
	 * @return array
	 */
	static function sortOrderFields($orderFormFields) {
		$result = $tmp = $res = array();

		foreach ($orderFormFields as $name => $field) {
			$tmp[$field['sort']][$name] = $field;
		}
		ksort($tmp);
		foreach ($tmp as $arr) {
			$res += $arr;
		}
		return $res;
	}
}
