<?php
class Loger1C {

	static function DrawTable($pathDay){// построение таблицы с файлами
		$root = URL::getDocumentRoot();
		$html = '';
		$filesXML = scandir($pathDay);
		$files = array();
		foreach ($filesXML as $file) {
			$ext = explode('.', $file);
			$ext = array_pop($ext);
			if ($ext == 'xml' || $ext == 'txt') {
				$files[$file] = @filemtime($root.TEMP_DIR.'log1c'.DS.$file);
			}
		}

		//arsort($files);
		$files = array_keys($files);

		foreach ($files as $key => $file) {
			if ($file == "info.txt") { //Делаем общий лог первым
				$tmp = $files[0];
				$files[0] = $file;
				$files[$key] = $tmp;
			} else if ($file == "log.txt") { //Делаем полный лог вторым
				$tmp = $files[1];
				$files[1] = $file;
				$files[$key] = $tmp;
			}
		}

		foreach ($files as $file) {
			$path = $pathDay.DS.$file;
			$download = SITE.DS.$pathDay.DS.$file;
			$size = '';
			$size = filesize($pathDay.DS.$file);
			if ($size >= 1073741824){
				$size = number_format($size / 1073741824, 2) . ' GB';
			}
			elseif ($size >= 1048576){
				$size = number_format($size / 1048576, 2) . ' MB';
			}
			else{
				$size = number_format($size / 1024, 2) . ' KB';
			}
			$time = '';
			$type = '';
			$partsFile = explode('.',$file);
			switch ($partsFile[0]) {
				case 'exportQuery':
					$type = "Экспорт";
					break;
				case 'orders':
					$type = "Заказы";
					break;
				case 'log':
					$type = "Лог";
					break;
				case 'info':
					$type = "Общая информация";
					break;
				default:
					$type = "Unknown";
					break;
			}
			if (strpos($partsFile[0], 'import') === 0) {
				$type = 'Каталог';
			}
			if (strpos($partsFile[0], 'offers') === 0) {
				$type = 'Остатки';
			}
			if (strpos($partsFile[0], 'orders-') === 0) {
				$type = 'Импорт заказов';
			}
			$html .= '<tr>
					<td>'.$file.'</td>
					<td>'.$type.'</td>
					<td>'.$size.'</td>
					<td><a href="'.$download.'" class="down-log primary download" log="'.$file.'" title="Скачать" download><i class="fa fa-download"></i></a>
					<button class="primary drop" log="'.$path.'" title="Удалить"><i class="fa fa-trash"></i></button></td>
					</tr>';
		}

		if ($html == '') {
			$html = '<tr><td class="text-center" colspan="6">Файлы импорта и экспорта отсутствуют</td></tr>';
		}
		return $html;
	}

	static public function deleteFile($name) {
		if (!unlink($name)) return false;
		$pathPart = explode('/',$name);
		array_pop($pathPart);
		$path = implode('/',$pathPart);
		return self::DrawTable($path);
	}

	/**
	 * Возвращает список дней, у которых есть папка с логами
	 *
	 * @return array
	 */
	static public function getDaysList() {
		function date_sort($a, $b) {
			//Меняем на дефисы, чтобы strtotime воспринял дату в европейском формате HACK
			return strtotime(str_replace('_','-',$a)) - strtotime(str_replace('_','-',$b));
		}

		$dir = TEMP_DIR.'log1c';
		if (is_dir($dir)) {
			$list = array_diff(scandir($dir), array('..', '.'));
			usort($list, "date_sort");
		} else {
			$list = array();
		}

		$result = array();
		foreach ($list as $key => $file) {
			if (is_dir($dir.DS.$file)) {
				$result[] = $file;
			}
		}
		$result = array_reverse($result);
		return $result;
	}

	/**
	 * Возвращает список логов по времени у выбранного дня
	 *
	 * @param string $day
	 * @return array
	 */
	static public function getTimesList($day) {
		$dir = TEMP_DIR.'log1c'.DS.$day;
		$list = array_diff(scandir($dir), array('..', '.'));
		$result = array();
		foreach ($list as $key => $file) {
			if (is_dir($dir.DS.$file)) {
				$result[] = $file;
			}
		}
		$result = array_reverse($result);
		return $result;
	}

	public static function getFilesSize($path){
		//https://ru.stackoverflow.com/questions/96930/%D0%9E%D0%BF%D1%80%D0%B5%D0%B4%D0%B5%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5-%D1%80%D0%B0%D0%B7%D0%BC%D0%B5%D1%80%D0%B0-%D0%BF%D0%B0%D0%BF%D0%BA%D0%B8-%D1%81%D1%80%D0%B5%D0%B4%D1%81%D1%82%D0%B2%D0%B0%D0%BC%D0%B8-php
		$fileSize = 0;

		if (is_dir($path)) {
			$dir = scandir($path);
		} else {
			$dir = array();
		}

		foreach($dir as $file)
		{
			if (($file!='.') && ($file!='..'))
				if(is_dir($path . '/' . $file))
					$fileSize += self::getFilesSize($path.'/'.$file);
				else
					$fileSize += filesize($path . '/' . $file);
		}

		return $fileSize;
	}
}