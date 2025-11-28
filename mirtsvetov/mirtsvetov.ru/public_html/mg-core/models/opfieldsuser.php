<?php
/**
 * Модель: OpFieldsUser
 *
 * Класс Models_OpFieldsUser реализует логику взаимодействия с дополнительными полями покупателей.
 *
 * @author Гайдис Михаил
 * @package moguta.cms
 * @subpackage Model
 */
class Models_OpFieldsUser {

	private $userId;
	private $content;

	function __construct($userId = null) {
		if($userId) {
			$this->userId = (int)$userId;

			$ids = array();

			$fields = MG::get('opUserFields');
			if ($fields === null) {
				$fields = array();
				$res = DB::query('SELECT * FROM '.PREFIX.'user_opt_fields');
				while($row = DB::fetchAssoc($res)) {
					$fields[] = $row;
				}
				MG::set('opUserFields', $fields);
			}

			$fieldsContent = MG::get('opUserFieldContent'.$this->userId);
			if ($fieldsContent === null) {
				$fieldsContent = array();
				$res = DB::query('SELECT * FROM '.PREFIX.'user_opt_fields_content WHERE user_id = '.DB::quote($this->userId));
				while($row = DB::fetchAssoc($res)) {
					$fieldsContent[] = $row;
				}
				MG::set('opUserFieldContent'.$this->userId, $fieldsContent);
			}

			foreach ($fieldsContent as $row) {
				$this->content[$row['field_id']]['id'] = $row['id'];
				$this->content[$row['field_id']]['value'] = $row['value'];
				$ids[] = $row['field_id'];
			}

			foreach ($fields as $row) {
				$this->content[$row['id']]['name'] = $row['name'];
				$this->content[$row['id']]['type'] = $row['type'];
				$this->content[$row['id']]['active'] = $row['active'];
				$this->content[$row['id']]['placeholder'] = $row['placeholder'];
				if($row['vars']) {
					$this->content[$row['id']]['vars'] = unserialize(stripslashes($row['vars']));
				}
			}
		}
	}

	/**
	 * Позволяет изменить значения дополнительных полей у выбранного покупателя
	 * <code>
	 * $userId = 1;
	 * $opFieldsM = new Models_OpFieldsUser($userId);
	 * // массив с дополнительным полями
	 * $data = array(
	 * 	1 => 'test',  // ключ являеться идентификатором дополнительного поля (id)
	 * 	2 => 'test2',
	 * );
	 * // будем считать что у этого покупателя есть еще поле с id '3' и оно имеет значение 'defaultValue'
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
	public function fill($data, $mode = false) {
		if (!empty($this->content) && is_array($this->content)) {
			// если надо заполнить поля все, если не были переданы часть полей, то сбросить их
			if($mode == 'full') {
				foreach ($this->content as $key => $value) {
					if(in_array($value['type'], array('checkbox'))) {
						$this->content[$key]['value'] = 'false';
					} else {
						$this->content[$key]['value'] = '';
					}
				}
			}
			// если надо заполнить поля все, если не были переданы часть полей, то сбросить их (с проверкой на скрытые поля для пользователя)
			if($mode == 'fullPublic') {
				foreach ($this->content as $key => $value) {
					if(in_array($value['type'], array('checkbox'))) {
						$this->content[$key]['value'] = 'false';
					} else {
						if($value['active'] == 1) {
							$this->content[$key]['value'] = '';
						}
					}
				}
			}
		}
		// заполнение полей
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

	/**
	 * Сохраняет текущее состояние объекта в базу данных, обычно используется после метода fill для фиксации изменений
	 * <code>
	 * $data = array(
	 * 	1 => 'test',  // ключ являеться идентификатором дополнительного поля (id)
	 * 	2 => 'test2',
	 * );
	 * $userId = 1;
	 * $opFieldsM = new Models_OpFieldsUser($userId);
	 * $opFieldsM->fill($data);
	 * $opFieldsM->save();
	 * </code>
	 * @return void
	 */
	public function save() {
		if (!empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $key => $item) {
				if(!empty($item['id'])) {
					DB::query('UPDATE '.PREFIX.'user_opt_fields_content SET value = '.DB::quote($item['value']).' WHERE id = '.DB::quoteInt($item['id']));
				} else {
					DB::query('INSERT INTO '.PREFIX.'user_opt_fields_content (user_id, field_id, value) VALUES 
						('.DB::quoteInt($this->userId).', '.DB::quoteInt($key).', '.DB::quote($item['value']).')');
					$this->content[$key]['id'] = DB::insertId();
				}
			}
		}

		// Необходимо сбросить данные в реестре, чтобы при повтороном вызове конструктора она собрались заного из базы с учетом обновленных.
		// Если без того, то в личном кабинете при сохранении нового значения в доп поле, оно будет отобразаться только после перезагрузки страницы.
		MG::set('opUserFields', NULL);
    MG::set('opUserFieldContent'.$this->userId, NULL);
	}

	/**
	 * Метод возвращает верстку для модального окна с покупателем в панели администрирования
	 * <code>
	 * $userId = 1;
	 * $opFieldsM = new Models_OpFieldsUser($userId);
	 * $data = $opFieldsM->createCustomFieldToAdmin();
	 * viewData($data);
	 * </code>
	 * @return string
	 */
	public function createCustomFieldToAdmin() {
		$html = '';
		if (!empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $key => $item) {
				if (!isset($item['value'])) {$item['value'] = '';}
				$html .= '<div class="row">
				          	<div class="large-4 columns">
				            	<label class="dashed">'.$item['name'].'</label>
				          	</div>
				          	<div class="large-8 columns checkbox">';
				switch ($item['type']) {
					case 'input':
						$html .= '<input type="text" name="'.$key.'" class="rule userOpFields" value="'.$item['value'].'">';
						break;
					case 'number':
						$html .= '<input type="number" name="'.$key.'" class="rule userOpFields" value="'.$item['value'].'">';
						break;
					case 'textarea':
						$html .= '<textarea name="'.$key.'" class="userOpFields">'.$item['value'].'</textarea>';
						break;
					case 'checkbox':
						$html .= '<div class="checkbox" style="margin:5px 0 15px;">
	                  				<input type="checkbox" name="'.$key.'" id="uopf_'.$key.'" class="userOpFields" '.($item['value'] == 'true'?'checked':'').'>
	                  				<label for="uopf_'.$key.'"></label>
	                			</div>';
						break;
					case 'select':
					case 'radiobutton':
						$tmp = '<option value="" '.($item['value']==''?'selected':'').'>Не выбрано</option>';
						if (!empty($item['vars'])) {
              foreach ($item['vars'] as $keyIn => $var) {
                $tmp .= '<option value="'.$var.'" '.($item['value'] == $var?'selected':'').'>'.$var.'</option>';
              }
            }
						$html .= '<select class="userOpFields" name="'.$key.'">'.$tmp.'</select>';
						break;
				}			            	
				$html .= '	</div>
						</div>';
			}
		}

		if($html) {
			$html = '<h4>Дополнительные поля</h4>'.$html;
		}

		return $html;
	}

	/**
	 * Метод возвращает массив с данными о дополнительных полях выбранного покупателя
	 * <code>
	 * $userId = 1;
	 * $opFieldsM = new Models_OpFieldsUser($userId);
	 * // выведет все дополнительные поля покупателя
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
	 * Метод возвращает массив с данными о дополнительных полях выбранного покупателя в более понятном для человека виде
	 * <code>
	 * $userId = 1;
	 * $opFieldsM = new Models_OpFieldsUser($userId);
	 * // выведет все дополнительные поля покупятеля
	 * $data = $opFieldsM->getHumanView();
	 * viewData($data);
	 * // выведет только указанное дополнительное поле
	 * $fieldId = 1;
	 * $data = $opFieldsM->getHumanView($fieldId);
	 * viewData($data);
	 * // при указании второго атрибута, выведет сразу строку со значением, а не массив
	 * </code>
	 * @param $elem string|int - если 'all', то вернет все поля, если указать id поля, то только егозначение
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
	 * Возвращает только значения полей, без дополнительной информации (ключ id поля и его значение)
	 * <code>
	 * $userId = 1;
	 * $opFieldsM = new Models_OpFieldsUser($userId);
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
	 * Проверяет поле на сущесвование перед выводом в таблице покупателей, отсеивает из списка то, чего уже нет 
	 * (только для панели управления)
	 * <code>
	 * $opFieldsM = new Models_OpFieldsUser('get');
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
		return array_intersect($data, $ids);
	}


	// ============================================================================================
	// ============================================================================================
	// ============================================================================================

	/**
	 * Получает список дополнительных полей
	 * <code>
	 * $data = Models_OpFieldsUser::getFields();
	 * viewData($data);
	 * </code>
	 * @return array
	 */
	public static function getFields($public = false) {
		if ($public) {
			$suffix = 'Pub';
		} else {
			$suffix = '';
		}
		$result = MG::get('opUserGetFields'.$suffix);
		if ($result === null) {
			$result = array();
			$res = DB::query('SELECT * FROM '.PREFIX.'user_opt_fields '.($public ? DB::quote('WHERE active = 1', true) : '').' ORDER BY sort ASC');
			while ($row = DB::fetchAssoc($res)) {
				$row['vars'] = unserialize(stripslashes($row['vars']));
				$result[$row['id']] = $row;
			}
			MG::set('opUserGetFields'.$suffix, $result);
		}
		return $result;
	} 

	/**
	 * Метод позвоялет редактировать дополнительные поля покупателей
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
	 * Models_OpFieldsUser::saveFields($data);
	 * </code>
	 * @param $data array - массив с полями для сохранения
	 * @return array
	 */
	public static function saveFields($data) {
		// удаляем те поля, которые не пришли на сохранение
		$ids = $result = array();
		foreach ($data as $item) {
			$ids[] = $item['id'];
		}
		$res = DB::query('SELECT id FROM '.PREFIX.'user_opt_fields WHERE id NOT IN ('.DB::quoteIN($ids,0).')');
		while($row = DB::fetchAssoc($res)) {
			DB::query('DELETE FROM '.PREFIX.'user_opt_fields WHERE id = '.DB::quoteInt($row['id']));
			DB::query('DELETE FROM '.PREFIX.'user_opt_fields_content WHERE field_id = '.DB::quoteInt($row['id']));
		}
		// сохраняем поля
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
				$res = DB::query('SELECT id FROM '.PREFIX.'user_opt_fields WHERE name = '.DB::quote($item['name']));
				if($row = DB::fetchAssoc($res)) {
					$item['id'] = $row['id'];
				} else {
					if(!$item['sort']) {
						$res = DB::query('SELECT MAX(sort) FROM '.PREFIX.'user_opt_fields');
						if($row = DB::fetchAssoc($res)) {
							$item['sort'] = $row['MAX(sort)'] + 1;
						}
					}
				}
			}
			
			if(!$item['id']) {
				DB::query('INSERT INTO '.PREFIX.'user_opt_fields (`name`, `type`, `vars`, `sort`, `active`, `placeholder`) VALUES 
					('.DB::quote($item['name']).', '.DB::quote($item['type']).', '.DB::quote($serDat).', '.DB::quoteInt($item['sort']).',
						'.DB::quoteInt($item['active']).', '.DB::quote($item['placeholder']).')');
				$result[$key] = DB::insertId();
			} else {
				DB::query('UPDATE '.PREFIX.'user_opt_fields SET `name` = '.DB::quote($item['name']).', `type` = '.DB::quote($item['type']).', 
					`vars` = '.DB::quote($serDat).', `active` = '.DB::quoteInt($item['active']).', `placeholder` = '.DB::quote($item['placeholder']).',
					`sort` = '.DB::quoteInt($item['sort']).' WHERE id = '.DB::quoteInt($item['id']));
			}
		}

		return $result;
	}
}