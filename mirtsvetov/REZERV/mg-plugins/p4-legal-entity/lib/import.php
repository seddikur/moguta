<?php

class LegalEntityImport
{
    private $pluginName = null;

    public function __construct($pluginName)
	{
		$this->pluginName = $pluginName;
	}

	/**
	 * 
	 * Загружает юридические лица в БД.
	 * 
	 * @param array $data
	 * 
	 */
    public function startUploadLegalEntities($data)
	{
		foreach ($data as $row) {

			$sql = 'SELECT `id` 
						FROM `'. PREFIX . $this->pluginName .'` 
					WHERE 
						`1c_id` = '. DB::quote($row['legal']['1c_id']);

			$query = DB::query($sql);

			if ($query->num_rows) {

				unset($row['legal']['1c_id']);

				$legal = DB::fetchAssoc($query);

				DB::query('UPDATE `'. PREFIX . $this->pluginName .'` 
							SET '. DB::buildPartQuery($row['legal']) .' 
						WHERE 
							`id` = '. DB::quoteInt($legal['id']));

				$sql = 'SELECT id 
							FROM `'. PREFIX . $this->pluginName .'_debt` 
						WHERE 
							`legal_id` = '. DB::quote($legal['id']);

				$query = DB::query($sql);

				if ($query->num_rows) {

					$debt = DB::fetchAssoc($query);

					DB::query('UPDATE `'. PREFIX . $this->pluginName .'_debt` 
									SET '. DB::buildPartQuery($row['debt']) .' 
								WHERE 
									`id` = '. DB::quoteInt($debt['id']));
				} else {
					$row['debt']['legal_id'] = $legal['id'];
					DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'_debt` SET ', $row['debt']);
				}
			} else {
				DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'` SET ', $row['legal']);
				$row['debt']['legal_id'] = DB::insertId();
				DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'_debt` SET ', $row['debt']);
			}
		}
	}

	/**
	 * 
	 * Загружает адреса доставки юридических лиц в БД.
	 * 
	 * @param array $data
	 * 
	 */
	public function startUploadAddresses($data)
	{
		foreach ($data as $row) {
			$sql = 'SELECT `id` 
						FROM `'. PREFIX . $this->pluginName .'_address` 
					WHERE 
						`1c_id` = '. DB::quote($row['1c_id']);

			$query = DB::query($sql);

			if ($query->num_rows) {

				$address = DB::fetchAssoc($query);

				DB::query('UPDATE `'. PREFIX . $this->pluginName .'_address` 
							SET `address` = '. DB::quote($row['address']) .' 
						WHERE 
							`id` = '. DB::quoteInt($address['id']));

			} else {

				$sql = 'SELECT `id`, `user_id`
						FROM `'. PREFIX . $this->pluginName .'` 
					WHERE 
						`1c_id` = '. DB::quote($row['legal_1c_id']);
				
				$query = DB::query($sql);

				if ($query->num_rows) {

					unset($row['legal_1c_id']);

					$legal = DB::fetchAssoc($query);

					$row['legal_id'] = $legal['id'];
					$row['user_id'] = $legal['user_id'];

					DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'_address` SET ', $row);
				}
			}
		}
	}

	/**
	 * 
	 * Пересобирает массив компании.
	 * 
	 * @param array $data
	 * 
	 * @return array
	 * 
	 */
	public function rebuildCompaniesArray($data)
	{
		foreach ($data as $row) {
			$item['legal'] = [
				'1c_id' => $row[0],
				'name' => $row[1],
				'inn' => $row[2],
				'kpp' => $row[3],
				'manager_name' => $row[9],
				'manager_phone' => $row[10],
			];
			
			$item['debt'] = [
				'mir' => $row[4],
				'rm' => $row[5],
				'tk' => $row[6],
				'tare_tank' => $row[7],
				'tare_cover' => $row[8],
			];
			
			$result[] = $item;
		}
		
		return $result;
	}

	/**
	 * 
	 * Пересобирает массив адресов.
	 * 
	 * @param array $data
	 * 
	 * @return array
	 * 
	 */
	public function rebuildAddressesArray($data)
	{
		foreach ($data as $row) {
			$result[] = [
				'legal_1c_id' => $row[0],
				'1c_id' => $row[1],
				'address' => $row[2],
			];
		}
		
		return $result;
	}

	/**
	 * 
	 * Читает CSV файл и возвращает данные в виде массива.
	 * 
	 * @param string $file_path Путь до csv файла.
	 * @param array  $file_encodings Кодеровка файла.
	 * @param string $col_delimiter Разделитель колонки (по умолчанию автоопределине).
	 * @param string $row_delimiter Разделитель строки (по умолчанию автоопределине).
	 * 
	 * @return array
	 * 
	 */
	public function importFromCsv($file_path, $file_encodings = ['cp1251', 'UTF-8'], $col_delimiter = null, $row_delimiter = null)
	{
		if (!file_exists($file_path)) {
			return false;
		}
		
		$cont = trim(file_get_contents($file_path));
		
		$encoded_cont = mb_convert_encoding($cont, 'UTF-8', mb_detect_encoding($cont, $file_encodings));
		
		unset($cont);
		
		// определим разделитель
		if (!$row_delimiter) {
			$row_delimiter = "\r\n";
			if (false === strpos($encoded_cont, "\r\n")) {
				$row_delimiter = "\n";
			}
		}
		
		$lines = explode($row_delimiter, trim($encoded_cont));
		$lines = array_filter($lines);
		$lines = array_map('trim', $lines);
		
		// авто-определим разделитель из двух возможных: ';' или ','.
		// для расчета берем не больше 30 строк
		if (!$col_delimiter) {
			
			$lines30 = array_slice($lines, 0, 30);
			
			// если в строке нет одного из разделителей, то значит другой точно он...
			foreach ($lines30 as $line) {
				if (!strpos($line, ',')) $col_delimiter = ';';
				if (!strpos($line, ';')) $col_delimiter = ',';
				if ($col_delimiter) break;
			}
			
			// если первый способ не дал результатов, то погружаемся в задачу и считаем кол разделителей в каждой строке.
			// где больше одинаковых количеств найденного разделителя, тот и разделитель...
			if (!$col_delimiter) {
				$delim_counts = array(';' => array(), ',' => array());
				foreach ($lines30 as $line) {
					$delim_counts[','][] = substr_count($line, ',');
					$delim_counts[';'][] = substr_count($line, ';');
				}
				
				$delim_counts = array_map('array_filter', $delim_counts); // уберем нули
				
				// кол-во одинаковых значений массива - это потенциальный разделитель
				$delim_counts = array_map('array_count_values', $delim_counts);
				
				$delim_counts = array_map('max', $delim_counts); // берем только макс. значения вхождений
				
				if ($delim_counts[';'] === $delim_counts[',']) {
					return array('Не удалось определить разделитель колонок.');
				}
				
				$col_delimiter = array_search(max($delim_counts), $delim_counts);
			}
		}
		
		// заголовки таблицы.
		unset($lines[0]);
		
		$result = [];
		
		foreach ($lines as $key => $line) {
			$result[] = str_getcsv($line, $col_delimiter);
			unset($lines[$key]);
		}
		
		return $result;
	}

	/**
	 * 
	 * Копирует файлы.
	 * 
	 * @param string $path путь до папки.
	 * @param array $files путь до файлов.
	 * 
	 */
	public function copyFiles($path, $files)
	{
		if (!is_dir($path)) {
			mkdir($path, 0755);
		}

		foreach ($files as $file) {
			copy($file['path'], $path . time() .'_'. $file['name'] .'.csv');
		}	
	}

	/**
	 * 
	 * Удаляет файлы.
	 * 
	 * @param array $files путь до файлов.
	 * 
	 */
	public function deleteFiles($files)
	{
		foreach ($files as $file) {
			unlink($file['path']);
		}
	}
}