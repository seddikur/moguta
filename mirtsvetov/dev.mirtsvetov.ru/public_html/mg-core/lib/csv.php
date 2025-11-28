<?php 

/**
 * Класс CSV - предназначен для экспорта в CSV массива данных
 *
 * @package moguta.cms
 * @subpackage Libraries
 */

class CSV {

	/**
	 * Экспортирует входной массив в CSV файл.
	 * <code>
	 *  $array = array(
	 *		array('key1' => 'value1Row1', 'key2' => 'value2Row1'),
	 *		array('key1' => 'value1Row2', 'key2' => 'value2Row2')
	 *	);
	 *	CSV::export($array);
     * </code>
	 * @param array $data массив для экспорта
	 */
	public static function export($data) {
		$start = true;
		$csv = '';
		foreach ($data as $value) {
			set_time_limit(30);
			if($start) {
				$csv = self::createColumnName($value);
				$start = false;
			}
			$csv .= self::writeData($value);
		}	
		return $csv;
	}

	/**
	 * Подготавливает массив для записи в CSV файле заголовки столбцов.
	 * @param array $data массив для для подготовки к экспорту
	 */
	private static function createColumnName($data) {
		$array = array();
		foreach ($data as $key => $value) {
			$array[] = $key;
		}
		return self::printCsv($array, true);
	}

	/**
	 * Подготавливает массив для записи в CSV файле заголовки столбцов.
	 * @param array $data массив для для подготовки к экспорту
	 */
	private static function writeData($data) {
		$array = array();
		foreach ($data as $key => $value) {
			$array[] = $value;
		}
		return self::printCsv($array);
	}

	/**
	 * Производит запись в CSV файл массива данных.
	 * @param array $data массив для записи новой строки в CSV
	 * @param bool $new переключатель добавления в файл
	 */
	private static function printCsv($csvText, $new = false) {
	  	foreach ($csvText as $key => $value) {
	    	$csvText[$key] = mb_convert_encoding(stripcslashes(trim(preg_replace('/\s\s+/', ' ', $value))), "WINDOWS-1251", "UTF-8");
	  	}

		$f = fopen('php://memory', 'r+');
		fputcsv($f, $csvText, ';');
		rewind($f);
		return stream_get_contents($f);
	}

}

?>