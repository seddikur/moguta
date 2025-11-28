<?php
/**
 * Модель: OpFieldsCategory
 *
 * Класс Models_OpFieldsCategory реализует логику взаимодействия с дополнительными полями категорий.
 *
 * @author Moguta
 * @package moguta.cms
 * @subpackage Model
 */
class Models_OpFieldsCategory {

	/** 
	 * Получает список дополнительных полей категорий
	 * <code>
	 * $data = Models_OpFieldsCategory::getFields();
	 * viewData($data);
	 * </code>
	 * @return array
	 */
	public static function getFields() {
		$result = MG::get('opCategoryFields');
		if ($result === null) {
			$result = array();
			$res = DB::query("SELECT * FROM `".PREFIX."category_opt_fields` ORDER BY `sort` ASC");
			while ($row = DB::fetchAssoc($res)) {
				$result[$row['id']] = $row;
			}
			MG::set('opCategoryFields', $result);
		}
		
		return $result;
	}

	/**
	 * Возвращает содержимое дополнительных полей определенной категории
	 * <code>
	 * $data = Models_OpFieldsCategory::getContent(1, true);
	 * viewData($data);
	 * </code>
	 * @param int $catId id категории, содержимое доп. полей которой будет найдено
	 * @param boolean $allowEmpty учитывать ли пустые значения
	 * @return array
	 */
	public static function getContent($catId, $allowEmpty = true) {
		$result = self::getFields();
		$res = DB::query("SELECT `field_id`, `value` FROM `".PREFIX."category_opt_fields_content` WHERE `category_id` = ".DB::quoteInt($catId));
		while ($row = DB::fetchAssoc($res)) {
			if (array_key_exists($row['field_id'], $result)) {
				$result[$row['field_id']]['value'] = $row['value'];
			}
		}
		if (!$allowEmpty) {
			foreach ($result as $key => $field) {
				unset($result[$key]['id']);
				unset($result[$key]['sort']);
				if (isset($field['value']) && !$field['value'] && $field['value'] !== 0 && $field['value'] !== '0') {
					unset($result[$key]);
				}
			}
		}
		return $result;
	}

	/**
	 * Метод для редактирования доступных дополнительных полей
	 * Принимает список полей, которые нужно сохранить, если существующие поля не были указаны, то они будут удалены
	 * <code>
	 * $data = array(
	 * 	Array(
     *        'id' => 7,        // идентификатор поля
     *        'name' => 'test', // название поля
     *        'sort' => 0,      // порядок сортировки
     *    ),
     *  // далее аналогично
     *	Array(
     *        'id' => 8,
     *        'name' => 'test2',
     *        'isPrice' => 1,
     *        'sort' => 1,
     *    ),
     *	Array(
     *        'id' => 9,
     *        'name' => 'test3',
     *        'sort' => 2,
     *    )
	 * );
	 * Models_OpFieldsCategory::saveFields($data);
	 * </code>
	 * @param $data array - массив данных с информацией о дополнительных полях для сохранения
	 * @return void
	 */
	public static function saveFields($data) {
		// удаляем те поля, которые не пришли на сохранение
		$ids = array();
		foreach ($data as $item) {
			$ids[] = $item['id'];
		}
		DB::query("DELETE FROM `".PREFIX."category_opt_fields` WHERE `id` NOT IN (".DB::quoteIN($ids,0).")");
		DB::query("DELETE FROM `".PREFIX."category_opt_fields_content` WHERE `field_id` NOT IN (".DB::quoteIN($ids,0).")");
		// сохраняем поля
		foreach ($data as $key => $item) {
			$item['name'] = htmlspecialchars($item['name']);
			
			if(!$item['id']) {
				DB::query("INSERT INTO `".PREFIX."category_opt_fields` (`name`) VALUES (".DB::quote($item['name']).")");
				$insertId = DB::insertId();
				DB::query("UPDATE `".PREFIX."category_opt_fields` SET `sort` = ".DB::quoteInt($insertId)." WHERE `id` = ".DB::quoteInt($insertId));
			} else {
				DB::query("UPDATE `".PREFIX."category_opt_fields` SET `name` = ".DB::quote($item['name']).", `sort` = ".DB::quoteInt($item['sort'])." 
					WHERE `id` = ".DB::quoteInt($item['id']));
			}
		}
	}

	/**
	 * Сохраняет значение дополнительных полей выбранной категории
	 * <code>
	 * $data = array(
	 * 	'op_1' => 'test1',
	 *  'op_2' => 'test2',
	 *  'op_3' => 'test3'
	 * );
	 * Models_OpFieldsCategory::saveContent(1, $data);
	 * </code>
	 * @param int $catId
	 * @param array $content
	 * @return void
	 */
	public static function saveContent($catId, $content) {
		if (!is_array($content) || empty($content)) {
			return false;
		}
		foreach ($content as $fieldId => $fieldValue) {
			$fieldId = str_replace('op_', '', $fieldId);
			if (!$fieldValue && $fieldValue !== 0 && $fieldValue !== '0') {
				DB::query("DELETE FROM `".PREFIX."category_opt_fields_content` WHERE `category_id` = ".DB::quoteInt($catId)." AND `field_id` = ".DB::quoteInt($fieldId));
			} else {
				DB::query("UPDATE `".PREFIX."category_opt_fields_content` SET `value` = ".DB::quote($fieldValue)."
					WHERE `category_id` = ".DB::quoteInt($catId)." AND `field_id` = ".DB::quoteInt($fieldId));
				if (DB::affectedRows() < 1) {
					DB::query("INSERT INTO `".PREFIX."category_opt_fields_content` (`field_id`, `category_id`, `value`) 
						VALUES (".DB::quoteInt($fieldId).", ".DB::quoteInt($catId).", ".DB::quote($fieldValue).")");
				}
			}
		}
	}

	/**
	 * Возвращает верстку значений доп. полей категории
	 * @access private
	 * @param int $catId
	 * @return string
	 */
	public static function getAdminHtml($catId) {
		$content = self::getContent($catId);
		$html = '';
		foreach ($content as $id => $item) {
			if (!isset($item['value'])) {
				$item['value'] = '';
			}
			$html .= 	'<div class="row">'.
							'<div class="large-4 columns">'.
								'<label class="dashed">'.$item['name'].'</label>'.
							'</div>'.
							'<div class="large-8 columns">'.
								'<input type="text" name="'.$id.'" class="categoryOpFields" value="'.$item['value'].'">'.
							'</div>'.
						'</div>';
		}
		return $html;
	}
}