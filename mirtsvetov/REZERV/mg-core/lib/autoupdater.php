<?php

/*
	Plugin Name: Автообновление базы данных
	Description: Автообновление базы данных
	Author: Mark
	Version: 1.0.0
 */

class AutoUpdater{
	public static function init(){
		//Авторизация по ссылке при первом входе на сайт по ссылке ?login=hash
		if (empty($_SESSION['user']->email) && !empty($_GET['login'])) {
	      $tmp = md5(MG::getSetting('adminEmail').MG::getSetting('licenceKey').MG::getSetting('cacheTime'));
	   
	      if ($_GET['login'] == $tmp) {
	        $res = DB::query('SELECT * FROM '.PREFIX.'user WHERE `login_email` = "'.MG::getSetting('adminEmail').'"');
	        if($row = DB::fetchObject($res)) {
	          $_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
	          unset($_SESSION['user']);
	          $_SESSION['user'] = new stdClass();
	          $_SESSION['user']->role = 1;
	          $_SESSION['user'] = $row;
	          MG::setOption(array('option' => 'cacheTime', 'value' => 86400));  
	          MG::redirect('');
	        }
	      }
	    }

		$actualVer = str_replace('v', '', VER);
		$needUpdateDB = version_compare(MG::getSetting('lastModVersion'), $actualVer, '<');
    	$debug = false;
		$updateCore = true;
		$updatePlugin = true;

	

		if($debug){
			viewData(MG::getSetting('lastModVersion'));
			viewData($actualVer);
			var_dump(version_compare(MG::getSetting('lastModVersion'), $actualVer, '<'));
			echo "<br>";
			echo CORE_DIR.'modificator.php';
			echo "<br>";
		    viewData(MG::get('pluginsInfo'));
	    }

		if($updateCore){
	
		

			$modificatorPath = CORE_DIR.'modificator.php';

			if ($needUpdateDB && is_file($modificatorPath)){
				include $modificatorPath;
			}
		}

		if($updatePlugin && $needUpdateDB){
			foreach (MG::get('pluginsInfo') as $plugin) {
				if ($plugin['Active'] && is_file(SITE_DIR.'mg-plugins'.DS.$plugin['folderName'].DS.'update.php')) {
					include SITE_DIR.'mg-plugins'.DS.$plugin['folderName'].DS.'update.php';
				}
			}
		}

		// синхронизация информации о тарифе
		//
		//


		// Фикс удаления плагинов из шаблонов
		// Запускается вместе с модификатором
		if ($needUpdateDB) {
			$usedTemplates = [];
			$usedTemplatesSql = 'SELECT DISTINCT `template` '.
				'FROM `'.PREFIX.'plugins` '.
				'WHERE `template` != \'\'';
			$usedTemplatesResult = DB::query($usedTemplatesSql);
			while ($usedTemplatesRow = DB::fetchAssoc($usedTemplatesResult)) {
				$usedTemplates[] = $usedTemplatesRow['template'];
			}
			if ($usedTemplates) {
				foreach ($usedTemplates as $templateName) {
					$templateDir = SITE_DIR.'mg-templates'.DS.$templateName;
					$templatePluginsDir = $templateDir.DS.'mg-plugins';
					if (!is_dir($templatePluginsDir)) {
						continue;
					}
					$templatePlugins = array_diff(scandir($templatePluginsDir), ['.', '..']);
					
					$dbTemplatePlugins = [];
					$dbTemplatePluginsSql = 'SELECT `folderName` '.
						'FROM `'.PREFIX.'plugins` '.
						'WHERE `template` = '.DB::quote($templateName);
					$dbTemplatePluginsResult = DB::query($dbTemplatePluginsSql);
					while ($dbTemplatePluginsRow = Db::fetchAssoc($dbTemplatePluginsResult)) {
						$dbTemplatePlugins[] = $dbTemplatePluginsRow['folderName'];
					}

					if (empty($dbTemplatePlugins)) {
						continue;
					}

					$invalidPlugins = array_diff($dbTemplatePlugins, $templatePlugins);

					if (empty($invalidPlugins)) {
						continue;
					}

					if ($invalidPlugins) {
						$fixPluginsSql = 'UPDATE `'.PREFIX.'plugins` '.
							'SET `template` = \'\' '.
							'WHERE `template` = '.DB::quote($templateName).' AND '.
								'`folderName` IN ('.DB::quoteIN($invalidPlugins).')';
						DB::query($fixPluginsSql);
					}
				}
			}
		}

		// Прослушивание события распаковки квикстарта
		mgAddAction('after_unpack_quickstart_tables', array(__CLASS__, 'afterUnpackQuickstartTables'), 1); 
	}

	public static function afterUnpackQuickstartTables($args) {
		$result = $args['result'];
		// Место для всяких вещей, которые будут выполняться сразу после распаковки таблиц из квикстарта

		// Отключаем нарезку миниатюр и webp
		MG::setOption(['option' => 'thumbsProduct', 'value' => 'false']);
		MG::setOption(['option' => 'useWebpImg', 'value' => 'false']);

		MG::setOption(['option' => 'useAbsolutePath', 'value' => 'true']);

		// После разворота квикстарта отключаем склады, чтобы отображались товары на сайте
		if (MG::enabledStorage()) {
			MG::setOption('onOffStorage', 'OFF');
			MG::setSetting('onOffStorage', 'OFF');
		}
		
		return $result;
	}
}
