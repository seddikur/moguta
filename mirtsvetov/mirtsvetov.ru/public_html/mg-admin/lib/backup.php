<?php 
class Backup {
	///////////////////////// Запаковка начало ////////////////////////////

	static function checkPack() {// проверка прав
		$root = SITE_DIR;
		$errors = '';
		$lang = MG::get('lang');

		if (!is_writable($root.'backups')) {
			$errors .= $lang['BACKUP_MSG_CHMOD'].$root.'backups'.PHP_EOL.PHP_EOL;
		}

		return $errors;
	}

	static function getDBtables() {// получение списка таблиц
		if(!is_dir(SITE_DIR.'backups'.DS)) {
			mkdir(SITE_DIR.'backups', 0755);
		}

		$errors = self::checkPack();

		if (!$errors) {
			$tables = array();
			$queryTables = DB::query('SHOW TABLES');

			while($row = DB::fetchArray($queryTables)) {
				if (!empty($_POST['prefixTables']) && strpos($row[0], PREFIX) !== 0) {
					continue;
				}
				$tables[] = '`'.$row[0].'`';
			}
			$tables = array_unique($tables);
			return $tables;
		}
		else{
			return array('errors' => $errors);
		}
	}

	static function createDBbackup($file = '', $tables = array(), $timeHave = 15) {// заполнение содержимого таблиц
		if (!$file) {
			$file = SITE_DIR.'backups'.DS.'mysqldump.sql';
		}
		if (empty($tables) && !empty($_POST['tables'])) {
			$tables = $_POST['tables'];
		}
		$startingLine = !empty($_POST['startingLine'])?$_POST['startingLine']:0;
		$timerSave = microtime(true);

		$memoryLimit = ini_get('memory_limit');
		if (!$memoryLimit || $memoryLimit == '-1') {
			$memoryLimit = 0;
		} else {
			$memoryLimit = trim($memoryLimit);
			$unit = strtolower(substr($memoryLimit, -1));
			switch($unit) {
				case 'g':
					$memoryLimit = substr($memoryLimit, 0, -1);
					$memoryLimit = $memoryLimit*1024*1024*1024;
					break;
				case 'm':
					$memoryLimit = substr($memoryLimit, 0, -1);
					$memoryLimit = $memoryLimit*1024*1024;
					break;
				case 'k':
					$memoryLimit = substr($memoryLimit, 0, -1);
					$memoryLimit = $memoryLimit*1024;
					break;
			}
		}
		if (memory_get_usage(true) > $memoryLimit) {
			$memoryLimit = 0;
		}

		while (count($tables) > 0) {

			$content = "\n";

			if (in_array('`'.PREFIX.'setting`', $tables)) {
				$table = '`'.PREFIX.'setting`';
				if (($key = array_search($table, $tables)) !== false) {
					unset($tables[$key]);
				}
			}
			else{
				$table = array_shift($tables);
			}

			if ($startingLine > 0) {
				$lim = ' LIMIT '.$startingLine.', 18446744073709551615';
			}
			else{// заголовки таблиц
				$content .= "DROP TABLE IF EXISTS ".$table.";\n\n";
				$res = DB::query('SHOW CREATE TABLE '.$table);
				$create = DB::fetchArray($res);
				$content .= $create[1].";\n\n";
				$lim = '';
			}

			if ($table == '`'.PREFIX.'cache`') {
				$handle = fopen($file, 'a');
				fwrite($handle, $content);
				fclose($handle);
				continue;
			}

			$result = DB::query('SELECT * FROM '.$table.$lim);

			$fields_amount=DB::$connection->field_count; 
	  
			$rows_num=DB::$connection->affected_rows;
				
			$st_counter = 0;
			while($row = DB::fetchArray($result))  {
				
				if ($st_counter%100 == 0 || $st_counter == 0 ) { //начало записи таблицы
					$content .= "\nINSERT INTO ".$table." VALUES";
				}
				$content .= "\n(";
				for($j=0; $j<$fields_amount; $j++) {
					$row[$j] = str_replace("\n","\\n", addslashes($row[$j]) );
					if (isset($row[$j])) {
						$content .= '"'.$row[$j].'"' ; 
					}
					else {
						$content .= '""';
					}
					if ($j<($fields_amount-1)) {
						$content.= ',';
					}
				}
				$content .=")";

				if (
					$memoryLimit &&
					($memoryLimit - memory_get_usage(true) < (20 * 1024 * 1024))
				) {// Следим за тем, чтобы мы не перевалили за доступную выделенную память
					$memory_limit_overflow = 1;
				} else {
					$memory_limit_overflow = 0;
				}
				
				if (
					(($st_counter+1)%100 == 0 && $st_counter != 0) || 
					$st_counter+1 == $rows_num || 
					($memory_limit_overflow && $st_counter != 0)
				) {// Каждые 100 строк, в конце или переизбыток памяти
					$content .= ";";

					$startingLine = $startingLine + 100;

					if ($st_counter+1==$rows_num) {
						$startingLine = 0;
					}

					$timeHave -= microtime(true) - $timerSave;
					$timerSave = microtime(true);
					if($timeHave < 0 || $memory_limit_overflow) {

						$handle = fopen($file, 'a');
						fwrite($handle, $content);
						fclose($handle);

						if (($st_counter+1)!=$rows_num) {
							array_unshift($tables, $table);
						}
						$tables = array_values($tables);
						return array('tables' => $tables, 'remaining' => count($tables), 'startingLine' => $startingLine);
					}
				} 
				else {
					$content .= ",";
				} 
				$st_counter++;
			}
			$startingLine = 0;

			$handle = fopen($file, 'a');
			fwrite($handle, $content);
			fclose($handle);
			$content ="\n";
		}
		$tables = array_values($tables);
		return array('tables' => $tables, 'remaining' => count($tables), 'startingLine' => $startingLine);
	}

	static function packDB() {// запаковка только базы данных
		$edition = EDITION;

    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randString = '_';
    for ($i = 0; $i < 10; $i++) {
      $randString .= $chars[rand(0, strlen($chars)-1)];
    }

		$zipName = 'backup_db_'.$edition.'_'.VER.'_'.date("d.m.Y-H.i.s").$randString;
		
		$za = new ZipArchive;
		$za->open(SITE_DIR.'backups'.DS.$zipName.'.zip',ZipArchive::CREATE|ZipArchive::OVERWRITE);
		$za->addFile(SITE_DIR.'backups'.DS.'mysqldump.sql', 'mysqldump.sql');
		$za->close();
		unlink(SITE_DIR.'backups'.DS.'mysqldump.sql');
		return $zipName;
	}

	static function getFileList() {// создание архива
		$timeLimit = 15;
		if (!is_file(SITE_DIR.'backups'.DS.'mysqldump.sql')) {
			return false;
		}
		if (empty($_POST['zipName'])) {
			$edition = EDITION;

			$zipName = 'backup_';
			if ($_POST['backupType'] == 'core') {
				$zipName .= 'core_';
			}

			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randString = '_';
			for ($i = 0; $i < 10; $i++) {
        $randString .= $chars[rand(0, strlen($chars)-1)];
      }

			$zipName .= $edition.'_'.VER.'_'.date("d.m.Y-H.i.s");
		} else {
			$zipName = $_POST['zipName'];
		}
    $zipName .= $randString;

		$zipFolder = SITE_DIR.'backups'.DS.$zipName;
		if (!is_dir($zipFolder)) {
			mkdir($zipFolder, 0755);
		}

		$whiteList = array();
		if ($_POST['backupType'] == 'core') {
			$whiteList = array(
				realpath(SITE_DIR.'mg-admin').DS,
				realpath(SITE_DIR.'mg-core').DS,
				realpath(SITE_DIR.'mg-templates'.DS.MG::getSetting('templateName')).DS,
			);

			foreach (PM::getPluginsInfo() as $plugin) {
				if (!empty($plugin['Active']) && empty($plugin['fromTemplate'])) {
					$whiteList[] = realpath(SITE_DIR.'mg-plugins'.DS.$plugin['folderName']).DS;
				}
			}
			foreach ($whiteList as $key => $value) {
				if (in_array($value, ['/','\\']) || !$value) {
					unset($whiteList[$key]);
				}
			}
		}		
		
		if (!empty($_POST['buildFolderList'])) {
			$folderList = array();
			$files = array();
			$scan = array_diff(scandir(SITE_DIR), ['.','..']);
			foreach ($scan as $item) {
				if (is_dir(SITE_DIR.$item)) {
					if (in_array($item, array(
						'mg-pages',
						'mg-admin',
						'mg-core',
						'mg-plugins',
						'mg-templates',
					))) {
						if (empty($whiteList) || in_array(realpath(SITE_DIR.$item), $whiteList)) {
							$folderList[] = $item;
						}
					} elseif ($item == 'uploads' && $_POST['backupType'] != 'core') {
						$scanUploads = array_diff(scandir(SITE_DIR.'uploads'), ['.','..']);
						foreach ($scanUploads as $itemUploads) {
							if (is_dir(SITE_DIR.'uploads'.DS.$itemUploads)) {
								if (in_array($itemUploads, [
									'prodtmpimg', 
									'tempimage',
									'.quarantine',
									'.tmb',
								])) {
									continue;
								}
								if ($itemUploads == 'product') {
									$scanProduct = array_diff(scandir(SITE_DIR.'uploads'.DS.'product'), ['.','..']);
									foreach ($scanProduct as $itemProduct) {
										if (is_dir(SITE_DIR.'uploads'.DS.'product'.DS.$itemProduct)) {
											$folderList[] = 'uploads'.DS.'product'.DS.$itemProduct;
										}
									}
								} else {
									$folderList[] = 'uploads'.DS.$itemUploads;
								}
							} else {
								$filePath = SITE_DIR.'uploads'.DS.$itemUploads;
								if (
									filesize($filePath) > (BACKUP_MAX_FILE_SIZE*1048576) || // > 30 mb
									(!empty($whiteList) && str_replace($whiteList, '', $filePath) === $filePath)
								) {
									$files[] = $filePath;
								}
							}
						}
					}
				} else {
					if (
						in_array($item, array(
							'index.php',
							'.htaccess',
							'config.ini',
						)) ||
						(
							$_POST['backupType'] != 'core' &&
							in_array($item, array(
								'sitemap.xml',
								'robots.txt',
								'favicon.ico',
								'downTime.html',
							))
						)
					) {
						$files[] = SITE_DIR.$item;
					}
				}
			}
			if (!empty($whiteList)) {
				foreach ($whiteList as $item) {
					if (is_dir($item)) {
						$item = str_replace(SITE_DIR, '', $item);
						if (!in_array($item, $folderList)) {
							$folderList[] = $item;
						}
					}
				}
			}
		} else {
			$folderList = file_get_contents($zipFolder.DS.'folders.txt');
			$folderList = unserialize($folderList);
			$files = file_get_contents($zipFolder.DS.'files.txt');
			$files = unserialize($files);
		}

		while (count($folderList) > 0) {
			$folder = array_shift($folderList);

			$flags = FilesystemIterator::SKIP_DOTS;


			foreach(new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator(realpath(SITE_DIR.$folder), $flags)
			) as $object){
				$filePath = $object->getRealPath();
				if (
					filesize($filePath) > (BACKUP_MAX_FILE_SIZE*1048576) || // > 30 mb
					(!empty($whiteList) && str_replace($whiteList, '', $filePath) === $filePath)
				) {
					continue;
				}

				$files[] = $filePath;
			}

			if ((microtime(true) - $_SERVER['REQUEST_TIME']) > $timeLimit) {
				file_put_contents($zipFolder.DS.'folders.txt', serialize($folderList));
				file_put_contents($zipFolder.DS.'files.txt', serialize($files));
				return array('zipFolder' => $zipFolder, 'zipName' => $zipName, 'totalFiles' => count($files), 'foldersRemaining' => count($folderList));
			}
		}

		file_put_contents($zipFolder.DS.'files.txt', serialize($files));
		return array('zipFolder' => $zipFolder, 'zipName' => $zipName, 'totalFiles' => count($files), 'foldersRemaining' => 0);
	}

	static function addDumpToZip() {
		$zipFolder = $_POST['zipFolder'];
		if (!is_file(SITE_DIR.'backups'.DS.'mysqldump.sql')) {
			if (is_file($zipFolder.DS.'part_0.zip') && filesize($zipFolder.DS.'part_0.zip') > 2) {
				return true;
			} else {
				return false;
			}
		}
		
		file_put_contents($zipFolder.DS.'currentpart.txt', '0');
		$za = new ZipArchive;
		$za->open($zipFolder.DS.'part_0.zip',ZipArchive::CREATE|ZipArchive::OVERWRITE);
		$za->addFile(SITE_DIR.'backups'.DS.'mysqldump.sql', 'backups/mysqldump.sql');
		$za->close();
		unlink(SITE_DIR.'backups'.DS.'mysqldump.sql');
		return true;
	}

	static function zipCoreFiles() { //запаковка ядра движка
		$zipFolder = $_POST['zipFolder'];
		$lang = MG::get('lang');
		$files = file_get_contents($zipFolder.DS.'files.txt');
		$files = unserialize($files);
		$coreFiles = $miscFiles = $addedFiles = array();
		$errors = '';

		foreach ($files as $file) {
			if(
				in_array($file, array(SITE_DIR.'.htaccess', SITE_DIR.'config.ini', SITE_DIR.'index.php', )) ||
				(
					(substr($file, -4) == '.php' || substr($file, -4) == '.txt') &&
					(strpos($file, 'mg-core'.DS) !== false || strpos($file, 'mg-admin'.DS) !== false)
				)
			) {
				$coreFiles[] = $file;
			}
			else{
				$miscFiles[] = $file;
			}
		}

		unset($files);
		file_put_contents($zipFolder.DS.'files.txt', serialize($miscFiles));

		$za = new ZipArchive;
		$za->open($zipFolder.DS.'part_0.zip',ZipArchive::CREATE);
		foreach ($coreFiles as $file) {
			$fileto = str_replace(DS, '/', str_replace(SITE_DIR, '', $file));

			$addedFiles[] = $fileto;
			$za->addFile($file, $fileto);
		}
		$za->close();
		$za->open($zipFolder.DS.'part_0.zip');
		$failedFiles = array();
		foreach ($addedFiles as $val) {
			if ($za->locateName($val) === false) {
				$failedFiles[] = $val;
			}
		}
		$za->close();
		if (!empty($failedFiles)) {
			$za->open($zipFolder.DS.'part_0.zip');
			foreach ($failedFiles as $failedFile) {
				$za->addFromString($failedFile, file_get_contents(SITE_DIR.$failedFile));
			}
			$za->close();
			$za->open($zipFolder.DS.'part_0.zip');
			foreach ($failedFiles as $val) {
				if ($za->locateName($val) === false) {
					$errors .= $lang['BACKUP_MSG_ADD_ERROR_1'].$val.$lang['BACKUP_MSG_ADD_ERROR_2'].PHP_EOL.PHP_EOL;
				}
			}
			$za->close();
		}

		return array('remainingFiles' => count($miscFiles), 'errors' => $errors);
	}

	static function zipFiles() {//  заполнение архива
		$zipName = $_POST['zipName'];
		$zipFolder = $_POST['zipFolder'];
		$lang = MG::get('lang');
		$root = SITE_DIR;

		$maxZipSize = 157286400;// 150 mb
		$timer = 15;
		$tmpPartSize = 20*1048576; //(mb)
		$tmpPartCount = 511;
		$errors = '';
		$zipSize = 0;
		$fileCount = 0;
		$addedFiles = array();
		$nextFile = false;

		if(BACKUP_MAX_FILE_SIZE == 'BACKUP_MAX_FILE_SIZE') {
			define('BACKUP_MAX_FILE_SIZE', 30);
		}

		$files = $fileSizes = array();
		if (is_file($zipFolder.DS.'files.txt')) {
			$files = file_get_contents($zipFolder.DS.'files.txt');
			$files = unserialize($files);
		}
		if (is_file($zipFolder.DS.'filesizes.txt')) {
			$fileSizes = file_get_contents($zipFolder.DS.'filesizes.txt');
			$fileSizes = unserialize($fileSizes);
		}

		if (file_exists($zipFolder.DS.'currentpart.txt')) {
      $part = file_get_contents($zipFolder.DS.'currentpart.txt');
      $zipFile = $zipFolder.DS.'part_'.$part.'.zip';
    } else {
		  $part = '';
      $zipFile = null;
    }

		$za = new ZipArchive;
		@$za->open($zipFile,ZipArchive::CREATE);
		$zaState = 'open';

		while (count($files) > 0) {

			if($zipSize > $tmpPartSize || $fileCount > $tmpPartCount) {
				$za->close();
				$zaState = 'closed';
				$zipSize = 0;
				$fileCount = 0;

				clearstatcache();
				if (filesize($zipFile) > $maxZipSize) {
					$nextFile = true;
				}
			}

			if(($_SERVER['REQUEST_TIME'] + $timer - time()) < 0 || $nextFile) {
				clearstatcache();
				if ($nextFile === false) {
					if (filesize($zipFile) > $maxZipSize) {
						$nextFile = true;
					}
				}

				if ($zaState == 'open') {
					$za->close();
					$zaState = 'closed';
				}

				if ($zaState == 'closed') {
					$za->open($zipFile);
					$zaState = 'open';
				}
				
				$failedFiles = array();
				foreach ($addedFiles as $val) {
					if ($za->locateName($val) === false) {
						$failedFiles[] = $val;
					}
				}
				if ($zaState == 'open') {
					$za->close();
				}
				if (!empty($failedFiles)) {
					$za->open($zipFile);
					foreach ($failedFiles as $failedFile) {
						$za->addFromString($failedFile, file_get_contents(SITE_DIR.$failedFile));
					}
					$za->close();
					$za->open($zipFile);
					foreach ($failedFiles as $val) {
						if ($za->locateName($val) === false) {
							$errors .= $lang['BACKUP_MSG_ADD_ERROR_1'].$val.$lang['BACKUP_MSG_ADD_ERROR_2'].PHP_EOL.PHP_EOL;
						}
					}
					$za->close();
				}

				if (count($files) == 0) {
					if ($part > 0) {
						$fileSizes[] = filesize($zipFile);
						self::implodeZip($zipFile, $zipFolder.DS.'imploded.zip');

						$zipFile0 = $zipFolder.DS.'part_0.zip';
						$fileSizes[] = filesize($zipFile0);
						self::implodeZip($zipFile0, $zipFolder.DS.'imploded.zip');

						rename($zipFolder.DS.'imploded.zip', SITE_DIR.'backups'.DS.$zipName.'.zip');
					} else {
						rename($zipFile, SITE_DIR.'backups'.DS.$zipName.'.zip');
					}

					$writeHandle=fopen(SITE_DIR.'backups'.DS.$zipName.'.zip', 'a');
					fwrite($writeHandle, 'delim'.serialize(
						array(
							'sizes'		=> $fileSizes,
							'type'		=> $_POST['backupType'],
							'php'		=> PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
							'version'	=> VER,
							'edition'	=> EDITION,
						)
					));
					fclose($writeHandle);

					MG::rrmdir($zipFolder);
				}
				else{
					file_put_contents($zipFolder.DS.'files.txt', serialize($files));
					if ($nextFile) {
						if ($part == 1) {
							$fileSizes[] = filesize($zipFile);
							rename($zipFile, $zipFolder.DS.'imploded.zip');
							file_put_contents($zipFolder.DS.'filesizes.txt', serialize($fileSizes));
						}
						if ($part > 1) {
							$fileSizes[] = filesize($zipFile);
							self::implodeZip($zipFile, $zipFolder.DS.'imploded.zip');
							file_put_contents($zipFolder.DS.'filesizes.txt', serialize($fileSizes));
						}

						$part++;
						file_put_contents($zipFolder.DS.'currentpart.txt', $part);
					}
				}

				return array('remainingFiles' => count($files), 'errors' => $errors);
			}

			if ($zaState == 'closed') {
				$za->open($zipFile,ZipArchive::CREATE);
				$zaState = 'open';
			}

			$file = array_shift($files);

			if (strpos($file, $root.'backups') !== false || 
				strpos($file, $root.'mg-cache') !== false ||
				strpos($file, $root.'uploads'.DS.'prodtmpimg'.DS) !== false ||
				strpos($file, $root.'uploads'.DS.'tempimage'.DS) !== false ||
				strpos($file, DS.'.quarantine'.DS) !== false ||
				strpos($file, DS.'.tmb'.DS) !== false) {
				continue;
			}

			if (filesize($file) > (BACKUP_MAX_FILE_SIZE*1048576)) {// > 30 mb
				$errors .= $lang['BACKUP_MSG_SIZE_1'].str_replace($root, '', $file).$lang['BACKUP_MSG_SIZE_2'].PHP_EOL.PHP_EOL;
				continue;
			}

			$fileto = str_replace(DS, '/', str_replace($root, '', $file));
			
			if (is_dir($file)) {
				$za->addEmptyDir($fileto);
			} 
			else{
				$zipSize += filesize($file);
				$fileCount++;
				$addedFiles[] = $fileto;
				$za->addFile($file, $fileto);
			}
		}

		if (count($files) == 0) {
			if ($zaState == 'open') {
				@$za->close();
				$zaState = 'closed';
			}

			if ($zaState == 'closed') {
				@$za->open($zipFile);
				$zaState = 'open';
			}

			$failedFiles = array();
			foreach ($addedFiles as $val) {
				if ($za->locateName($val) === false) {
					$failedFiles[] = $val;
				}
			}
			if ($zaState == 'open') {
				@$za->close();
			}
			if (!empty($failedFiles)) {
				$za->open($zipFile);
				foreach ($failedFiles as $failedFile) {
					$za->addFromString($failedFile, file_get_contents(SITE_DIR.$failedFile));
				}
				$za->close();
				$za->open($zipFile);
				foreach ($failedFiles as $val) {
					if ($za->locateName($val) === false) {
						$errors .= $lang['BACKUP_MSG_ADD_ERROR_1'].$val.$lang['BACKUP_MSG_ADD_ERROR_2'].PHP_EOL.PHP_EOL;
					}
				}
				$za->close();
			}

			if ($part > 0) {
				$fileSizes[] = filesize($zipFile);
				self::implodeZip($zipFile, $zipFolder.DS.'imploded.zip');

				$zipFile0 = $zipFolder.DS.'part_0.zip';
				$fileSizes[] = filesize($zipFile0);
				self::implodeZip($zipFile0, $zipFolder.DS.'imploded.zip');

				rename($zipFolder.DS.'imploded.zip', SITE_DIR.'backups'.DS.$zipName.'.zip');
			} else {
				@rename($zipFile, SITE_DIR.'backups'.DS.$zipName.'.zip');
			}

			$writeHandle=fopen(SITE_DIR.'backups'.DS.$zipName.'.zip', 'a');
			fwrite($writeHandle, 'delim'.serialize(
				array(
					'sizes'		=> $fileSizes,
					'type'		=> $_POST['backupType'],
					'php'		=> PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
					'version'	=> VER,
					'edition'	=> EDITION,
				)
			));
			fclose($writeHandle);

			MG::rrmdir($zipFolder);
			return array('remainingFiles' => count($files), 'errors' => $errors);
		}
	}

	static function implodeZip($zipFile, $implodedZip) { // склейка архивов
		$dataChunkSize = 5242880;// 1048576*5=5mb

		clearstatcache();
		$writeHandle = fopen($implodedZip, 'ab');

		$filesize = filesize($zipFile);
		$readHandle = fopen($zipFile, 'rb');

		$readBytesLeft = $filesize;
		while ($readBytesLeft > 0) {
			fwrite($writeHandle, fread($readHandle, $dataChunkSize));
			$readBytesLeft -= ($dataChunkSize+1);

		}
		fclose($readHandle);
		fclose($writeHandle);
		unlink($zipFile);
	}
	///////////////////////// Запаковка конец  ////////////////////////////
	///////////////////////// Распаковка начало ///////////////////////////

	static function getZipType($filename=false) {
		$result = array();
		$zip = SITE_DIR.'backups'.DS.(!$filename?$_POST['zip']:$filename);
		$dir = SITE_DIR.'backups'.DS.str_replace('.zip', '', (!$filename?$_POST['zip']:$filename));
		if (!is_readable($zip)) {
			$lang = MG::get('lang');
			$error = $lang['BACKUP_ARCHIVE_UPLOAD_CHECK_ERROR'];
			return array('error' => $error);
		}

		if (!is_writable(SITE_DIR.'backups')) {
			$lang = MG::get('lang');
			return array('error' => $lang['BACKUP_MSG_CHMOD'].SITE_DIR.'backups');
		}

		$params = self::readZipParams($zip);

		if (!empty($params) && !empty($params['php']) && $params['php'] != PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION) {
			$lang = MG::get('lang');
			return array('error' => $lang['BACKUP_VERSION_MISMATCH']);
		}

		if (empty($params) || empty($params['sizes'])) {
			$result['type'] = 'normal';

			$za = new ZipArchive;
			$za->open($zip);

			if ($za->locateName('mg-admin') === false && $za->locateName('mysqldump.sql') !== false) {
				$result['type'] = 'db';
				$za->extractTo(SITE_DIR.'backups');
			}
			$za->close();
		} else {
			$result['type'] = 'imploded';
			if (!is_array($params['sizes']) || count($params['sizes']) < 2) {
				$lang = MG::get('lang');
				return array('error' => $lang['BACKUP_ARCHIVE_UPLOAD_CHECK_ERROR']);
			} else {
				$result['partSizes'] = $params['sizes'];
				$result['explodedZip'] = $dir.DS.'exploded.zip';
			}
		}

		if ($result['type'] != 'db') {
			mkdir($dir, 0755);
			$result['dir'] = $dir;
		}

		return $result;
	}

	static function readZipParams($zipFile) {
		$line = $error = '';
		$cursor = -1;
		$found = false;

		$readHandle = fopen($zipFile, 'r');
		fseek($readHandle, $cursor, SEEK_END);
		$char = fgetc($readHandle);

		while (!$found && $cursor>(-1500)) {
			fseek($readHandle, $cursor--, SEEK_END);
		    $char = fgetc($readHandle);

		    $line = $char.$line;

		    if (strpos($line, 'delima:') === 0) {
		    	$found = true;
		    	break;
		    }

		    if ($cursor == -3 && $line != ';}') {
		    	break;
		    }
		}

		fclose($readHandle);

		if (!$found) {
			return array();
		}

		$array = str_replace('delim', '', $line);
		$array = unserialize($array);

		if (empty($array)) {
			return array();
		}

		if (!isset($array['sizes'])) {
			return array('sizes'=>$array['sizes']);
		}
		return $array;
	}

	static function explodeZip() {
		$dataChunkSize = 5242880;// 1048576*5=5mb
		$partSizes = $_POST['partSizes'];
		$zip = SITE_DIR.'backups'.DS.$_POST['zip'];
		$dir = $_POST['dir'];
		$partNum = $_POST['partNum'];

		$readHandle = fopen($zip, 'rb');
		$bytesRemaining = $partSizes[0];

		if ($partNum > 0) {
			$i = $seekPosition = 0;
			foreach ($partSizes as $partSize) {
				if ($i == $partNum) {
					$bytesRemaining = $partSize;
					break;
				}
				$seekPosition += $partSize;
				$i++;
			}
			fseek($readHandle, $seekPosition);
		}

		@unlink($dir.DS.'exploded.zip');
		$writeHandle = fopen($dir.DS.'exploded.zip', 'ab');

		while (!feof($readHandle)) {

			$contentLength = $bytesRemaining>=$dataChunkSize?$dataChunkSize:$bytesRemaining;

		    $content = fread($readHandle, $contentLength);

		    $bytesRemaining -= strlen($content);
		    fwrite($writeHandle, $content);

		    if ($bytesRemaining <= 0) {
		    	fclose($writeHandle);
		    	fclose($readHandle);
		    	break;
		    }
		}

		return self::getZipArrays($dir.DS.'exploded.zip');
	}

	static function getZipArrays($zip = null) {// создание списка файлов
		if (!$zip) {
			$zip = SITE_DIR.'backups'.DS.$_POST['zip'];
		}
		$dir = $_POST['dir'];
	
		$coreFiles = array();
		$miscFiles = array();
		$za = new ZipArchive;
		$za->open($zip);

		for($i = 0; $i < $za->numFiles; $i++) {

			$entry = $za->getNameIndex($i);


			if (strpos($entry, '.htaccess') === 0 || strpos($entry, 'config.ini') === 0 || strpos($entry, 'index.php') === 0) {
				$coreFiles[] = $entry;
			}
			elseif(strpos($entry, 'mg-core/') !== false || 
				strpos($entry, 'mg-admin/') !== false || 
				strpos($entry, 'mg-core'.DS) !== false || 
				strpos($entry, 'mg-admin'.DS) !== false){
				$ext = explode('.', $entry);
				$ext = array_pop($ext);
				if ($ext == 'php' || $ext == 'txt') {
					$coreFiles[] = $entry;
				}
				else{
					$miscFiles[] = $entry;
				}
			}
			else{
				$miscFiles[] = $entry;
			}
		}
		$za->close();

		if (!empty($coreFiles)) {
			file_put_contents($dir.DS.'corefiles.txt', serialize($coreFiles));
		} else {
			@unlink($dir.DS.'corefiles.txt');
		}
		if (!empty($miscFiles)) {
			file_put_contents($dir.DS.'miscfiles.txt', serialize($miscFiles));
		} else {
			@unlink($dir.DS.'miscfiles.txt');
		}
		return array('corefiles' => count($coreFiles), 'miscfiles' => count($miscFiles));
	}

	static function restoreFromZip() {// распаковка архива
		$zip = $_POST['zip'];
		$mode = $_POST['mode'];
		$dir = $_POST['dir'];
		$lang = MG::get('lang');
		$root = SITE_DIR;
		$errors = '';
		if (strpos($zip, SITE_DIR) !== 0) {
			$zip = $root.'backups'.DS.$zip;
		}
		
		if ($mode == 'core') {
			$listFile = 'corefiles.txt';
			$timeHave = 200;
			if (is_file($root.'index.php')) {unlink($root.'index.php');}
		}
		else{
			$listFile = 'miscfiles.txt';
			$timeHave = 10;
		}

		$za = new ZipArchive;
		$za->open($zip);
		
		$files = file_get_contents($dir.DS.$listFile);
		$files = unserialize($files);

		while (count($files) > 0) {
			$restoreConfig = false;
			$config = false;
			$file = array_shift($files);
			if ($file == '.htaccess' && is_file($root.'.htaccess')) {
				continue;
			}

			if ($file == 'config.ini' && is_file($root.'config.ini')) {
				$config = parse_ini_file($root.'config.ini', true);
				$restoreConfig = true;
				@unlink($root.'config.ini');
			}

			@chmod(dirname($root.$file) , 0775);
			if (!$za->extractTo($root, $file)) {
				$errors .= $lang['BACKUP_MSG_UNPACK'].$file.PHP_EOL.PHP_EOL;
			}
			if ($restoreConfig && $config) {
				self::setDBfromConfig($root.'config.ini', $config, $root);
			}
			if(($_SERVER['REQUEST_TIME'] + $timeHave - time()) < 1) {
				$za->close();
				file_put_contents($dir.DS.$listFile, serialize($files));
				return array('remainingFiles' => count($files), 'errors' => $errors);
			}
		}
		if (count($files) == 0) {
			$za->close();
			unlink($dir.DS.$listFile);
			if ($mode == 'core') {
				MG::rrmdir($dir);
			}
			return array('remainingFiles' => 0, 'errors' => $errors);
		}
	}

	static function setDBfromConfig($file, $config, $root) {// изменения в конфиге
		$target = $root.'backups'.DS.'configTemp.ini';

		$readFile = fopen($file, 'r');
		$saveFile = fopen($target, 'w');

		$linenum = 0;
		while (!feof($readFile)) {
			$linenum++;
			$line=fgets($readFile);

			if (strpos($line, 'HOST') !== false && $linenum < 15) {
				$line = 'HOST = "'.$config['DB']['HOST'].'"'.PHP_EOL;
			}
			if (strpos($line, 'USER') !== false && $linenum < 15) {
				$line = 'USER = "'.$config['DB']['USER'].'"'.PHP_EOL;
			}
			if (strpos($line, 'PASSWORD') !== false && $linenum < 15) {
				$line = 'PASSWORD = "'.$config['DB']['PASSWORD'].'"'.PHP_EOL;
			}
			if (strpos($line, 'NAME_BD') !== false && $linenum < 15) {
				$line = 'NAME_BD = "'.$config['DB']['NAME_BD'].'"'.PHP_EOL;
			}
			fwrite($saveFile, $line);
		}
		fclose($readFile);
		fclose($saveFile);

		rename($target, $file);
	}

	static function restoreDBbackup($dumpFile = '', $timeHave = 15, $unlink = true) {// база данных
		if (!$dumpFile) {
			$dumpFile = SITE_DIR.'backups'.DS.'mysqldump.sql';
		}
		if (isset($_POST['lineNum'])) {
			$lineNum = $_POST['lineNum'];
		} else {
			$lineNum = 0;
		}
		
		$timerSave = microtime(true);
		$templine = '';

		$file = new SplFileObject($dumpFile);
		$file->seek(PHP_INT_MAX);
		$linesTotal = $file->key();
		$file->seek($lineNum);

		while (!$file->eof()) {

			$line=$file->current();
			$file->next();
			$lineNum++;

			if (substr($line, 0, 2) == '--' || $line == ''){// коммент или пустая строчка
				continue;
			}

			$templine .= $line;

			if (substr(trim($line), -1, 1) == ';'){// конец запроса

				DB::query($templine);

				$timeHave -= microtime(true) - $timerSave;
				$timerSave = microtime(true);
				if ($unlink && ($linesTotal - $lineNum - 1) < 1) {
					@unlink($dumpFile);
				}
				if($timeHave < 0) {
					return array('currentLine' => $lineNum, 'remaining' => ($linesTotal - $lineNum - 1), 'total' => $linesTotal);
				}
				$templine = '';
			}
		}
		if ($unlink && ($linesTotal - $lineNum - 1) < 1) {
			@unlink($dumpFile);
		}

		return array('currentLine' => ($lineNum - 1), 'remaining' => ($linesTotal - $lineNum - 1), 'total' => $linesTotal);
	}

	///////////////////////// Распаковка конец  ///////////////////////////
	///////////////////////// Разное  /////////////////////////////////////

	static function drawTable(){// построение таблицы с бэкапами
		$lang = MG::get('lang');
		$root = SITE_DIR;
		$html = '';
		if (is_dir($root.'backups')) {
			$files = scandir($root.'backups');
		} else {
			$files = array();
		}
		$zipFiles = array();
		foreach ($files as $file) {
			$ext = explode('.', $file);
			$ext = array_pop($ext);
			if ($ext == 'zip') {
				$zipFiles[$file] = filemtime($root.'backups'.DS.$file);
			}
		}

		arsort($zipFiles);
		$zipFiles = array_keys($zipFiles);

		foreach ($zipFiles as $file) {

			$pieces = explode('.', $file);
			$trash = array_pop($pieces);
			$pieces = implode('.', $pieces);
			$pieces = explode('_', $pieces);

			$edition = $version = $time = $php = '';
			$type = 'full';

			foreach ($pieces as $value) {
				if ($value == 'gipermarket' || $value == 'saas' || $value == 'market' || $value == 'minimarket' || $value == 'vitrina' || $value == 'rent') {
					$edition = $value;
				}
				elseif ($value[0] == 'v' && is_numeric($value[1])) {
					$version = $value;
				}
				elseif (is_numeric($value[0]) && substr_count($value, ".") == 4) {
					$time = explode('-', $value);
					$time[1] = str_replace('.', ':', $time[1]);
					$time = implode(' ', $time);
				}
				elseif ($value == 'db') {
					$type = 'db';
				}
				elseif ($value == 'core') {
					$type = 'core';
				}
			}

			if (!$time) {
				$time = date("d.m.Y H:i:s", filectime($root.'backups'.DS.$file));
			}

			$size = filesize($root.'backups'.DS.$file);
			if ($size >= 1073741824){
				$size = number_format($size / 1073741824, 2) . ' GB';
			}
			elseif ($size >= 1048576){
				$size = number_format($size / 1048576, 2) . ' MB';
			}
			else{
				$size = number_format($size / 1024, 2) . ' KB';
			}

			$params = self::readZipParams(SITE_DIR.'backups'.DS.$file);

			if (!empty($params)) {
				$type = $params['type'];
				$edition = $params['edition'];
				$version = $params['version'];
				$php = $params['php'];
			}

			switch ($edition) {
				case 'saas':
					$edition = $lang['BACKUP_MSG_SAAS'];
					break;
				case 'gipermarket':
					$edition = $lang['BACKUP_MSG_GIPER'];
					break;
				case 'market':
					$edition = $lang['BACKUP_MSG_MARKET'];
					break;
				case 'minimarket':
					$edition = $lang['BACKUP_MSG_MINI'];
					break;
				case 'vitrina':
					$edition = $lang['BACKUP_MSG_FREE'];
					break;
				case 'rent':
					$edition = 'Магазин в аренду';
					break;
			}

			switch ($type) {
				case 'full':
					$type = $lang['BACKUP_TABLE_TYPE_SITE'];
					break;
				case 'core':
					$type = $lang['BACKUP_TABLE_TYPE_CORE'];
					break;
				case 'db':
					$type = $lang['BACKUP_TABLE_TYPE_DB'];
					break;
			}

			$fileName = pathinfo($file)['filename'];
			$stringToCut = explode('_', $fileName);
      $stringToCut = end($stringToCut);
			$fileToShow = str_replace('_'.$stringToCut, '', $file);

			$html .= '<tr>
					<td><span class="backupTable__filename"><i class="fa fa-file-archive-o"></i> '.$fileToShow.'</span></td>
					<td>'.$type.'</td>
					<td>'.$size.'</td>
					<td>'.$edition.'</td>
					<td>'.$version.'</td>
					<td>'.$php.'</td>
					<td>'.$time.'</td>
					<td><button class="primary unpack" zip="'.$file.'" title="'.$lang['BACKUP_TABLE_RESTORE'].'"><i class="fa fa-undo"></i></button>
					<button class="primary download" zip="'.$file.'" title="'.$lang['BACKUP_TABLE_DOWNLOAD'].'"><i class="fa fa-download"></i></button>
					<button class="primary drop" zip="'.$file.'" title="'.$lang['BACKUP_TABLE_DELETE'].'"><i class="fa fa-trash"></i></button></td>
					</tr>';
		}

		if ($html == '') {
			$html = '<tr><td class="text-center" colspan="8">'.$lang['BACKUP_TABLE_EMPTY'].'</td></tr>';
		}
		return $html;
	}

	static function checkSystem() {// вывод информации о сервере
		$picOk = '<img src="data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAAICAYAAAAvOAWIAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MzRENEU4OTkzNEE0MTFFMjkyMDg4QkYwNDQ1ODEzNzUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MzRENEU4OUEzNEE0MTFFMjkyMDg4QkYwNDQ1ODEzNzUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDozNEQ0RTg5NzM0QTQxMUUyOTIwODhCRjA0NDU4MTM3NSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDozNEQ0RTg5ODM0QTQxMUUyOTIwODhCRjA0NDU4MTM3NSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PljBVMgAAACaSURBVHjaYvz//z8DLtC4kZEdSB0D4u9A7MxCQOFuIDaCCpUwQSWUgVgdSSEXkDoIxLZQIZCmiYwNGxgMgYwTQMwKxJL1/v9fAhVfALL1oQrXAcWCQQyQMwKBmA0qsR+o8BeSwiVAhbEwGxlBHgQqmAJkZ6M5ezZQYRqyANjNQMEcINWFJD4ZXSHcZCSP9YOcBFSYjS2EAAIMAEVuM9o+OC6/AAAAAElFTkSuQmCC"/>';
		$picError = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJPSURBVDjLpZPLS5RhFMYfv9QJlelTQZwRb2OKlKuINuHGLlBEBEOLxAu46oL0F0QQFdWizUCrWnjBaDHgThCMoiKkhUONTqmjmDp2GZ0UnWbmfc/ztrC+GbM2dXbv4ZzfeQ7vefKMMfifyP89IbevNNCYdkN2kawkCZKfSPZTOGTf6Y/m1uflKlC3LvsNTWArr9BT2LAf+W73dn5jHclIBFZyfYWU3or7T4K7AJmbl/yG7EtX1BQXNTVCYgtgbAEAYHlqYHlrsTEVQWr63RZFuqsfDAcdQPrGRR/JF5nKGm9xUxMyr0YBAEXXHgIANq/3ADQobD2J9fAkNiMTMSFb9z8ambMAQER3JC1XttkYGGZXoyZEGyTHRuBuPgBTUu7VSnUAgAUAWutOV2MjZGkehgYUA6O5A0AlkAyRnotiX3MLlFKduYCqAtuGXpyH0XQmOj+TIURt51OzURTYZdBKV2UBSsOIcRp/TVTT4ewK6idECAihtUKOArWcjq/B8tQ6UkUR31+OYXP4sTOdisivrkMyHodWejlXwcC38Fvs8dY5xaIId89VlJy7ACpCNCFCuOp8+BJ6A631gANQSg1mVmOxxGQYRW2nHMha4B5WA3chsv22T5/B13AIicWZmNZ6cMchTXUe81Okzz54pLi0uQWp+TmkZqMwxsBV74Or3od4OISPr0e3SHa3PX0f3HXKofNH/UIG9pZ5PeUth+CyS2EMkEqs4fPEOBJLsyske48/+xD8oxcAYPzs4QaS7RR2kbLTTOTQieczfzfTv8QPldGvTGoF6/8AAAAASUVORK5CYII=" class="alert"/>';
		$lang = MG::get('lang');
		$err = false;
		$requireExtentions = array('zip', 'mysqli', 'gd', 'json', 'session', 'curl', 'xmlwriter', 'xmlreader');
		$requireList = array();

		$current_dir = SITE_DIR;
		if(!@mkdir($current_dir.DS."test", 0777)){
			$err = true;
		}elseif(!@chmod($current_dir.DS."test", 0777)){
			$err = true;
		}elseif(!$tf=@fopen($current_dir.DS."test".DS."test.txt", 'w')){
			$err = true;
		}else{
			@fclose($tf);
		}
		if(!@chmod($current_dir.DS."test".DS."test.txt", 0777)){
			$err = true;
		}elseif(!@unlink($current_dir.DS."test".DS."test.txt")){
			$err = true;
		}elseif(!@rmdir($current_dir.DS."test")){
			$err = true;
		}

		if ($err) {
			$fileAccess = $picError;
		}
		else{
			$fileAccess = $picOk;
		}

		foreach($requireExtentions as $ext){
			if(extension_loaded($ext)){
				$requireList[$ext] = $picOk;
			}
			else{
				$requireList[$ext] = $picError;
			}
		}

		$mysqlVersion = mysqli_get_server_version(DB::$connection);//50503
		if ($mysqlVersion >= 50503) {
			$utf8mb4Support = $picOk;
		} else {
			$utf8mb4Support = $picError;
		}

		if(function_exists('imagewebp')){
			$imagewebp = $picOk;
		}else{
			$imagewebp = $picError;
		}

		if (function_exists('exif_read_data')) {
			$exif = $picOk;
		} else {
			$exif = $picError;
		}

		$tmp = floor($mysqlVersion/10000);
		$mysqlVersion -= $tmp*10000;
		$mysqlVersionText = $tmp.'.';
		$tmp = floor($mysqlVersion/100);
		$mysqlVersion -= $tmp*100;
		$mysqlVersionText .= $tmp.'.'.floor($mysqlVersion);

		$checkEngine = CheckEngine::CheckEngine(true);

		$results = array(
			array('text' => $lang['CHECK_PHP'], 'pic' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION),
			array('text' => $lang['CHECK_SQL'], 'pic' => $requireList['mysqli']),
			array('text' => $lang['CHECK_SQL_VERSION'], 'pic' => $mysqlVersionText),
			array('text' => $lang['CHECK_SQL_UTF8MB4'], 'pic' => $utf8mb4Support),
			array('text' => $lang['CHECK_ZIP'], 'pic' => $requireList['zip']),
			array('text' => $lang['CHECK_PIC'], 'pic' => $requireList['gd']),
			array('text' => $lang['CHECK_XMLR'], 'pic' => $requireList['xmlreader']),
			array('text' => $lang['CHECK_XMLW'], 'pic' => $requireList['xmlwriter']),
			array('text' => $lang['CHECK_JSON'], 'pic' => $requireList['json']),
			array('text' => $lang['CHECK_CHMOD'], 'pic' => $fileAccess),
			array('text' => $lang['CHECK_CURL'], 'pic' => $requireList['curl']),
			array('text' => $lang['CHECK_IMAGEWEB'], 'pic' => $imagewebp),
			array('text' => $lang['CHECK_EXIF'], 'pic' => $exif),
			array('text' => $lang['CHECK_URL_FOPEN'].' (allow_url_fopen)', 'pic' => (ini_get('allow_url_fopen') === 'On' || ini_get('allow_url_fopen') === '1')?$picOk:$picError),
			array('text' => $lang['CHECK_MEMORY'].' (memory_limit)', 'pic' => str_replace(array('M','m'),'',ini_get('memory_limit')).' MB'),
			array('text' => $lang['CHECK_POST'].' (post_max_size)', 'pic' => str_replace(array('M','m'),'',ini_get('post_max_size')).' MB'),
			array('text' => $lang['CHECK_UPLOAD'].' (upload_max_filesize)', 'pic' => str_replace(array('M','m'),'',ini_get('upload_max_filesize')).' MB'),
			array('text' => $lang['CHECK_FILE'].' (max_file_uploads)', 'pic' => ini_get('max_file_uploads')),
			array('text' => $lang['CHECK_INPUT'].' (max_input_vars)', 'pic' => ini_get('max_input_vars')),
			array('text' => $lang['CHECK_EXEC'].' (max_execution_time)', 'pic' => ini_get('max_execution_time')),
			array('text' => $lang['CHECK_INPUT_TIME'].' (max_input_time)', 'pic' => ini_get('max_input_time')),
			array('text' => $lang['CHECK_UPLOADS_ROOT'], 'pic' => $checkEngine['checkEngine']['uploads']),
			array('text' => $lang['CHECK_MGCACHE_ROOT'], 'pic' => $checkEngine['checkEngine']['mg-cache']),			
			array('text' => $lang['CHECK_TEMPLATE_ROOT'], 'pic' => $checkEngine['checkEngine']['template-cache']),
			array('text' => $lang['CHECK_FILES_ROOT'], 'pic' => $checkEngine['checkEngine']['file-create']),
		);

		$html = '<div class="row"><div class="large-6 small-12 columns server-info">';

		foreach ($results as $key => $value) {
			$html .= '<div class="row sett-line js-settline-toggle server-info__row">
				<div class="large-8 small-9 columns">
					<div class="dashed"><span>'.$value['text'].'</span></div>
				</div>
				<div class="large-4 small-3 columns">
					'.$value['pic'].'
				</div>
			</div>';
		}

		$html .= '</div></div>';
		$html .= ' <div class="row">
					<div class="small-2 medium-7 columns checkbox">
						<button class="checkEngine button" style="margin-bottom: 0;"><i class="fa fa-debug"></i> <span>'.$lang['CHECK_ENGINE'].'</span></button>';
		$html .= '<br>';
		if(MG::getSetting('useDefaultSettings') == 'false'){			
			$html .=  '<a class="link openLanding resetSettings " style="margin-bottom: 0; margin-top: 5px;"><span>'.$lang['RESET_SETTINGS'].'</span></a>';
			$html .= '<span class="question__wrap" flow="leftUp" tooltip="'.$lang['RESET_SETTINGS_TOOLTIP'].'">
						<i class="fa fa-question-circle" aria-hidden="true"></i>
					</span>';
		} else {
			$html .=  '<a class="link openLanding customSettings" style="margin-bottom: 0; margin-top: 5px;"><span>'.$lang['CUSTOM_SETTINGS'].'</span></a>';
			$html .= '<span class="question__wrap" flow="leftUp" tooltip="'.$lang['CUSTOM_SETTINGS_TOOLTIP'].'">
						<i class="fa fa-question-circle" aria-hidden="true"></i>
					</span>';
		}
			$html .=  '<div class="small-12 medium-7 columns"></div>
				</div>
			</div>
			<div class="row sett-line js-settline-toggle">
				<div class="checkEngineDiv columns">
				</div>
			</div>';
		return $html;
	}

	static function getDumpSize() {// вычисление размера сайта
		$root = SITE_DIR;
		$size = 0;

		$path = realpath($root);
		if($path!==false && $path!='' && file_exists($path)){
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
					if (
						strpos($object->getPathName(), $root.'backups') !== false ||
						strpos($object->getPathName(), $root.'mg-cache') !== false ||
						strpos($object->getPathName(), $root.'uploads'.DS.'prodtmpimg'.DS) !== false ||
						strpos($object->getPathName(), $root.'uploads'.DS.'tempimage'.DS) !== false ||
						strpos($object->getPathName(), DS.'.quarantine'.DS) !== false ||
						strpos($object->getPathName(), DS.'.tmb'.DS) !== false ||
						'link' == filetype($object->getPathName()) ||
						filesize($object->getPathName()) > (BACKUP_MAX_FILE_SIZE*1048576)// > 30 mb
						) {
						continue;
					}
					$size += $object->getSize();
				}
		}

		$res = DB::query("SHOW TABLE STATUS");

		while($row = DB::fetchAssoc($res)) {
			$size += $row["Data_length"] + $row["Index_length"];
		}

		if ($size > 150*1048576) {
			$size += 150*1048576;
		}

		return number_format($size / 1048576, 2);
	}

	static function dropBackup() {// удаление бэкапа
		$zip = $_POST['zip'];
		@unlink(SITE_DIR.'backups'.DS.$zip);
		@unlink($zip);
		$tmp = explode(DS, $zip);
		$zip = end($tmp);
		$zip = str_replace('.zip', '', $zip);
		MG::rrmdir(SITE_DIR.'backups'.DS.$zip);
		$html = self::drawTable();
		return $html;
	}

	static function addNewBackup() {
		$lang = MG::get('lang');

		if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_FILES['backupInput'])) {

			$backupsDir = SITE_DIR.'backups'.DS;
			if (!is_dir($backupsDir)) {
				mkdir($backupsDir, 0755);
			}
			
			$file_array = $_FILES['backupInput'];
			$path = $backupsDir;

			$name = explode('.', $file_array['name']);
			$ext = array_pop($name);

			if ($ext != 'zip') {
				return array('messageError' => $lang['BACKUP_ARCHIVE_UPLOAD_ONLYZIP'], 'return' => false);
			}

			if (move_uploaded_file($file_array['tmp_name'], $path.$file_array['name'])) {
				return array('ajaxData' => self::drawTable(), 'return' => true);
			}
		}
		return array('messageError' => $lang['BACKUP_ARCHIVE_UPLOAD_ERROR'], 'return' => false);
	}

	// распаковка базы демонстрационных данных шаблона
	static function unpackQuickstartTables() {
		$dir = SITE_DIR.'uploads'.DS.'quickstart'.DS.$_POST['template'].DS.'quickstart'.DS.'sql';
		$tmpFile = $dir.DS.'tmp.txt';
		if (!is_dir($dir)) {
			$lang = MG::get('lang');
			return array('messageError' => $lang['BACKUP_MSG_UNPACK'].$dir, 'return' => false);
		}

		$blackListTables = MarketplaceMain::getQuickstartBlacklist('tables');

		$tableFiles = array_diff(scandir($dir), ['.','..']);
		if ($_POST['tables'] == 'scan') {
			$tables = array();
			foreach ($tableFiles as $tableFile) {
				$file = explode('.', $tableFile);
				if ($file[1] == 'sql') {
					$tables[] = $file[0];
				}
			}
		} else {
			$tables = $_POST['tables'];
		}

		DB::query("SET sql_mode = '';", 1);

		while (count($tables) > 0) {
			$table = array_shift($tables);

			if (in_array($table, $blackListTables)) {
				continue;
			}

			$file = $dir.DS.$table.'.sql';
			//костыль для префиксов и абсолютных ссылок
			$content = file_get_contents($file);
			$content = str_replace(
				array(
					'DROP TABLE IF EXISTS `PREFIX_',
					'CREATE TABLE `PREFIX_',
					'INSERT INTO `PREFIX_',
					'CURRENT_SITE_URL',
				),
				array(
					'DROP TABLE IF EXISTS `'.PREFIX,
					'CREATE TABLE `'.PREFIX,
					'INSERT INTO `'.PREFIX,
					SITE,
				),
				$content
			);
			file_put_contents($tmpFile, $content);
			//сохранение старых настроек
			if ($table == 'setting') {
				$blackListSettings = MarketplaceMain::getQuickstartBlacklist('settings');
				$oldSettings = array();
				$res = DB::query("SELECT * FROM `".PREFIX."setting`");
				while ($row = DB::fetchAssoc($res)) {
					$oldSettings[] = $row;
				}
			}

			// Костыль для opf_ полей таблицы товаров
			$clearOpf = '';
			if ($table === 'product' || $table === 'product_variant') {
				$clearOpf = ' AND `COLUMN_NAME` NOT LIKE \'opf_%\'';
			}

			//распаковка новой таблицы
			self::restoreDBbackup($tmpFile, 600);

			//дозаполнение старыми настройками
			if ($table == 'setting') {
				$insertIgnoreValues = array();
				foreach ($oldSettings as $oldSetting) {
					$insertIgnoreValues[] = "(".DB::quote($oldSetting['option']).",".DB::quote($oldSetting['value']).",".DB::quote($oldSetting['active']).",".DB::quote($oldSetting['name']).")";
				}

				DB::query("DELETE FROM `".PREFIX."setting` WHERE `option` IN (".DB::quoteIN($blackListSettings).")");
				DB::query("INSERT IGNORE INTO `".PREFIX."setting`(`option`, `value`, `active`, `name`) VALUES ".implode(', ', $insertIgnoreValues));
				//сброс id

				DB::query('START TRANSACTION');
				DB::query('ALTER TABLE `'.PREFIX.'setting` ADD `temp_id` INT(11) DEFAULT NULL');
				DB::query('SET @reset = 0');
				DB::query('UPDATE `'.PREFIX.'setting` SET `temp_id` = @reset:= @reset + 1');
				DB::query('ALTER TABLE `'.PREFIX.'setting` MODIFY `id` INT(11)');
				DB::query('ALTER TABLE `'.PREFIX.'setting` DROP PRIMARY KEY');
				DB::query('ALTER TABLE `'.PREFIX.'setting` MODIFY `id` INT(11) DEFAULT NULL');
				DB::query('UPDATE `'.PREFIX.'setting` SET `id` = `temp_id`');
				DB::query('ALTER TABLE `'.PREFIX.'setting` DROP `temp_id`');
				DB::query('ALTER TABLE `'.PREFIX.'setting` ADD PRIMARY KEY(`id`)');
				DB::query('ALTER TABLE `'.PREFIX.'setting` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT');
				DB::query('COMMIT');

				$lastSettingIdSql = 'SELECT MAX(`id`) as lastId '.
					'FROM `'.PREFIX.'setting`';
				$lastSettingIdResult = DB::query($lastSettingIdSql);
				if ($lastSettingIdRow = DB::fetchAssoc(($lastSettingIdResult))) {
					$lastSettingId = intval($lastSettingIdRow['lastId']);

					$setAutoIncrementSql = 'ALTER TABLE `'.PREFIX.'setting` AUTO_INCREMENT = '.DB::quoteInt($lastSettingId + 1, true);
					DB::query($setAutoIncrementSql);
				}
			}


			//таймер
			if ((microtime(true) - $_SERVER['REQUEST_TIME']) > 15) {
				return array('totalTables' => count($tableFiles), 'remaining' => count($tables), 'tables' => $tables);
			}
		}
		             
                
        // Проверка типов полей `count` 
        $tablesCheckFields = ['wholesales_sys', 'product', 'product_variant'];
        foreach ($tablesCheckFields as $tableField){
			$fields = DB::fetchAssoc(DB::query("SHOW fields FROM `".PREFIX.$tableField."` WHERE Field like 'count'"));
			if($fields['Type'] != 'float'){
				DB::query("ALTER TABLE `".PREFIX.$tableField."` CHANGE `count` `count` FLOAT(11) NOT NULL");
			}
        }
		if (empty($tablesFiles)) {
			$tablesFiles = [];
		}
		$result = [
			'totalTables' => count($tablesFiles),
			'remaining' => count($tables),
			'tables' => $tables,
		];

		return MG::createHook('after_unpack_quickstart_tables', $result, func_get_args());
	}
}