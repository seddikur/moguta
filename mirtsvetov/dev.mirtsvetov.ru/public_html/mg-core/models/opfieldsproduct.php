<?php
/**
 * Модель: OpFieldsProduct
 *
 * Класс Models_OpFieldsProduct реализует логику взаимодействия с дополнительными полями товаров.
 *
 * @author Гайдис Михаил
 * @package moguta.cms
 * @subpackage Model
 */
class Models_OpFieldsProduct {

	private $productId;
	private $content;

	function __construct($productId = null) {
		if($productId) {
			$this->productId = (int)$productId;

			$res = DB::query('SELECT * FROM '.PREFIX.'product_opt_fields ORDER BY sort ASC');
			while($row = DB::fetchAssoc($res)) {
				$this->content[$row['id']]['id'] = $row['id'];
				$this->content[$row['id']]['productId'] = $this->productId;
				$this->content[$row['id']]['name'] = $row['name'];
				$this->content[$row['id']]['active'] = $row['active'];
				$fields[] = 'opf_'.$row['id'];
			}

			if(!empty($fields)) {
				$res = DB::query('SELECT '.implode(',', $fields).' FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($this->productId));
				if($row = DB::fetchAssoc($res)) {
					foreach ($row as $key => $value) {
						$this->content[str_replace('opf_', '', $key)]['value'] = $value;
					}
				}

				$res = DB::query('SELECT '.implode(',', $fields).', id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($this->productId));
				while ($row = DB::fetchAssoc($res)) {
					foreach ($row as $key => $value) {
						if($key == 'id') continue;
						$this->content[str_replace('opf_', '', $key)]['variant'][$row['id']]['id'] = $row['id'];
						$this->content[str_replace('opf_', '', $key)]['variant'][$row['id']]['value'] = $value;
					}
				}
			}
		}
	}

	/**
	 * Позволяет получить дополнительные поля прикрепленные к товару
	 * <code>
	 * // выборка всех данных
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $data = $opFieldsM->get();
	 * viewData($data);
	 * 
	 * // выборка конкретного поля
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $fieldId = 1;
	 * $data = $opFieldsM->get($fieldId);
	 * viewData($data);
	 *
	 * // выборка значения поля и названия
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $fieldId = 1;
	 * $name = $opFieldsM->get($fieldId, 'name');
	 * $value = $opFieldsM->get($fieldId, 'value');
	 * viewData($name);
	 * viewData($value);
	 *
	 * // выборка вариантов
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $fieldId = 1;
	 * $variants = $opFieldsM->get($fieldId, 'variant');
	 * viewData($variants);
	 *
	 * // по аналогии можно достать любой интересующий эллемент массива
	 * </code>
	 * @param void|string|int - опциональные параметры метода, указываеться вложенность в массиве через запятую, для получения нужного значения
	 * @return array|string
	 */
	public function get() {
		$args = func_get_args();
		if($args) {
			$result = $this->content;
			foreach ($args as $item) {
				$result = $result[$item];
			}
			return $result;
		} else {
			return empty($this->content) ? array() : $this->content;
		}
	}

	/**
	 * Заполняет значения дополнительных полей переданным массивом данных
	 * <code>
	 * // заполнение одного поля
	 * $data = array(
	 * 	'id' => 1,
	 * 	'value' => 'test',
	 * );
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $opFieldsM->fill($data);
	 * $opFieldsM->save();
	 *
	 * // заполнение одного поля с вариантами
	 * $data = array(
	 * 	'id' => 1,
	 * 	'variant' => array(
	 * 		array(
	 * 			'id' => 1,
	 * 			'value' => 'test'
	 * 		),
	 * 		array(
	 * 			'id' => 2,
	 * 			'value' => 'test2'
	 * 		)
	 * 	 )
	 * 	);
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $opFieldsM->fill($data);
	 * $opFieldsM->save();
	 *
	 * // сохранение нескольких полей одновременно
	 * $data = array(
	 * 	array(
	 * 		'id' => 1,
	 *   	'value' => 'test',
	 * 	),
	 * 	array(
	 * 		'id' => 2,
	 *   	'value' => 'test2',
	 * 	),
	 * );
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $opFieldsM->fill($data);
	 * $opFieldsM->save();
	 * </code>
	 * @param $data array - массив данных
	 * @return void
	 */
	public function fill($data = array()) {
		if(!empty($data['value'])) {
			$data = array($data);
		}
		foreach ($data as $key => $item) {
			$this->content[$item['id']]['value'] = $item['value'];
			if($item['variant']) {
				foreach ($item['variant'] as $variant) {
					$this->content[$item['id']]['variant'][$variant['id']]['id'] = $variant['id'];
					$this->content[$item['id']]['variant'][$variant['id']]['value'] = $variant['value'];
				}
			}
		}
	}

	/**
	 * Сохраняет текущее состояние объекта в базу данных, обычно используется после метода fill для фиксации изменений
	 * <code>
	 * // заполнение одного поля
	 * $data = array(
	 * 	'id' => 1,
	 * 	'value' => 'test',
	 * );
	 * $productId = 16;
	 * $opFieldsM = new Models_OpFieldsProduct($productId);
	 * $opFieldsM->fill($data);
	 * $opFieldsM->save();
	 * </code>
	 * @return void
	 */
	public function save() {
		$toSave = $toSaveVar = array();
		if (!empty($this->content) && is_array($this->content)) {
			foreach ($this->content as $item) {
				$toSave['opf_'.$item['id']] = $item['value'];
				if($item['variant']) {
					foreach ($item['variant'] as $variant) {
						$toSaveVar[$variant['id']]['opf_'.$item['id']] = $variant['value'];
					}
				}
			}
		}
		if($toSave) {
			DB::query('UPDATE '.PREFIX.'product SET '.DB::buildPartQuery($toSave).' WHERE id = '.DB::quoteInt($this->productId));
		}
		if($toSaveVar) {
			foreach ($toSaveVar as $key => $item) {
				DB::query('UPDATE '.PREFIX.'product_variant SET '.DB::buildPartQuery($toSaveVar[$key]).' WHERE id = '.DB::quoteInt($key));
			}
		}
	}

	// ============================================================================================
	// ============================================================================================
	// ============================================================================================

	/**
	 * Получает список доступных дополнительных полей
	 * <code>
	 * $data = Models_OpFieldsProduct::getFields();
	 * viewData($data);
	 * </code>
	 * @return array
	 */
	public static function getFields($prefix = '') {
		$result = MG::get('opProductGetFields'.$prefix);
		if ($result === null) {
			$result = array();
			$res = DB::query('SELECT * FROM '.PREFIX.'product_opt_fields ORDER BY sort ASC');
			while ($row = DB::fetchAssoc($res)) {
				$result[$prefix.$row['id']] = $row;
			}
			MG::set('opProductGetFields'.$prefix, $result);
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
     *        'isPrice' => 0,   // добавляет возможность создания оптовой цены из этого поля
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
     *        'isPrice' => 0,
     *        'sort' => 2,
     *    )
	 * );
	 * Models_OpFieldsProduct::saveFields($data);
	 * </code>
	 * @param $data array - массив данных с информацией о дополнительных полях для сохранения
	 * @return void
	 */
	public static function saveFields($data = array()) {
		$ids = $newOrder = $oldOrder = $result = array();
		// удаляем те поля, которые не пришли на сохранение
		foreach ($data as $item) {
			$ids[] = $item['id'];
		}
		$res = DB::query('SELECT id FROM '.PREFIX.'product_opt_fields WHERE id NOT IN ('.DB::quoteIN($ids,0).')');
		while($row = DB::fetchAssoc($res)) {
			DB::query('ALTER TABLE '.PREFIX.'product DROP COLUMN `opf_'.DB::quoteInt($row['id'], true).'`', true);
			DB::query('ALTER TABLE '.PREFIX.'product_variant DROP COLUMN `opf_'.DB::quoteInt($row['id'], true).'`', true);
			DB::query('DELETE FROM '.PREFIX.'product_opt_fields WHERE id = '.DB::quoteInt($row['id']));
			DB::query('DELETE FROM '.PREFIX.'locales WHERE field = \'opf_'.DB::quoteInt($row['id'], true).'\'');
		}
		// сохраняем поля
		foreach ($data as $key => $item) {
			$item['name'] = htmlspecialchars($item['name']);

			if(!$item['id']) {
				$res = DB::query('SELECT id FROM '.PREFIX.'product_opt_fields WHERE name = '.DB::quote($item['name']));
				if($row = DB::fetchAssoc($res)) {
					$item['id'] = $row['id'];
				} else {
					if(!$item['sort']) {
						$res = DB::query('SELECT MAX(sort) FROM '.PREFIX.'product_opt_fields');
						if($row = DB::fetchAssoc($res)) {
							$item['sort'] = $row['MAX(sort)'] + 1;
						}
					}
				}
			}

			if(!$item['id']) {
				DB::query('INSERT INTO '.PREFIX.'product_opt_fields (`name`, `is_price`, `sort`, `active`) VALUES 
					('.DB::quote($item['name']).', '.DB::quote($item['isPrice']).', '.DB::quoteInt($item['sort']).', '.DB::quoteInt($item['active']).')');
				$item['id'] = $result[$key] = DB::insertId();
			} else {
				DB::query('UPDATE '.PREFIX.'product_opt_fields SET `name` = '.DB::quote($item['name']).',
					`is_price` = '.DB::quote($item['isPrice']).', `sort` = '.DB::quoteInt($item['sort']).', `active` = '.DB::quoteInt($item['active']).' 
					WHERE id = '.DB::quoteInt($item['id']));
			}
			$newOrder[] = 'opf_'.$item['id'];
		}

		// если есть новые поля, добавляем в структуру таблиц
		foreach ($result as $key => $value) {
			$column = 'opf_'.$value;
			DB::query("ALTER TABLE `".PREFIX."product` ADD `".DB::quote($column, true)."` TEXT NOT NULL");
			DB::query("ALTER TABLE `".PREFIX."product_variant` ADD `".DB::quote($column, true)."` TEXT NOT NULL");
		}

		$res = DB::query("SELECT `COLUMN_NAME` 
			FROM `INFORMATION_SCHEMA`.`COLUMNS` 
			WHERE `TABLE_NAME`='".PREFIX."product' AND `COLUMN_NAME` LIKE 'opf_%'");
		while ($row = DB::fetchAssoc($res)) {
			$oldOrder[] = $row['COLUMN_NAME'];
		}

		if (!empty($newOrder) && $newOrder !== $oldOrder) {
			for ($i=0; $i < count($newOrder); $i++) { 
				if ($i === 0) {
					DB::query("ALTER TABLE `".PREFIX."product` MODIFY ".DB::quote($newOrder[$i], true)." TEXT NOT NULL AFTER last_updated;", true);
					DB::query("ALTER TABLE `".PREFIX."product_variant` MODIFY ".DB::quote($newOrder[$i], true)." TEXT NOT NULL AFTER last_updated;", true);
				} else {
					DB::query("ALTER TABLE `".PREFIX."product` MODIFY ".DB::quote($newOrder[$i], true)." TEXT NOT NULL AFTER ".DB::quote($newOrder[($i-1)], true).";", true);
					DB::query("ALTER TABLE `".PREFIX."product_variant` MODIFY ".DB::quote($newOrder[$i], true)." TEXT NOT NULL AFTER ".DB::quote($newOrder[($i-1)], true).";", true);
				}
			}
		}

		return $result;
	}
}