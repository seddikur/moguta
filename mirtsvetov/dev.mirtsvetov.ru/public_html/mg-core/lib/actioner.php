<?php
/**
 * Класс Actioner - предназначен для обработки административных действий,
 * совершаемых из панели управления сайтом, таких как добавление и удалени товаров,
 * категорий, и др. сущностей.
 *
 * Методы класса являются контролерами между AJAX запросами и логикой моделей движка, возвращают в конечном результате строку в JSON формате.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Actioner {

	/**
	 * @var string сообщение об успешнон результаnewTabYandexMarketте выполнения операции.
	 */
	public $messageSucces;

	/**
	 * @var string сообщение о неудачном результате выполнения операции.
	 */
	public $messageError;

	/**
	 * @var mixed массив с данными возвращаемый в ответ на AJAX запрос.
	 */
	public $data = array();

	/**
	 * @var mixed язык локали движка.
	 */
	public $lang = array();

	/**
	 * @var string префикс таблиц в базе сайта.
	 */
	public $prefix;

	/**
	 * Конструктор инициализирует поля клааса.
	 * @param bool $lang - массив дополняющий локаль движка. Используется для работы плагинов.
	 */
	public function __construct($lang = false) {
		$langMerge = array();
		if (!empty($lang)) {
			$langMerge = $lang;
		}// если $lang не пустой, значит он передан для работы в наследнике данного класса, например для обработки аяксовых запросов плагина
		include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');

		$lang = array_merge($lang, $langMerge);

		$this->messageSucces = $lang['ACT_SUCCESS'];
		$this->messageError = $lang['ACT_ERROR'];

		$this->lang = $lang;
		$this->prefix = PREFIX;

		// для удаления лишних пробелов в начале и конце
		foreach ($_POST as $key => $value) {
			// проверка на JSON (сгруппированный пакет данных)
			if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
				$tmp = json_decode($value, true);
				if($tmp !== null && $tmp !== $value) {
					$value = json_encode(self::reqursiveTrim($tmp));
				} else {
					$value = self::reqursiveTrim($value);
				}
			} else {
				$value = self::reqursiveTrim($value);
			}

			// обновление данных
			$_POST[$key] = $value;
		}
	}
	/**
	 * Установка флага на вывод подсказок только первый раз при входе в админку 
	 */
	public function getHitsFlag(){
		if(USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$flag = MG::getOption('hitsFlag');
		if(($flag == null)||($flag == 'false')){
		  MG::setOption('hitsFlag', 'true');
		}
		$this->data = $flag;
		return true;
    }
	
	public function checkIntroFlags(){
		if(USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$hitesType = $_POST['hitesType'];
		$updateOption = $_POST['updateOption'];
		$introFlagArray = json_decode(MG::getOption('introFlag'), true);
		if(!is_array($introFlagArray)){
		  $introFlagArray = [];
		}
		$this->data = $introFlagArray;
		if(in_array($hitesType, $introFlagArray)){
		  return false;
		}else{
		  if($updateOption == 'true'){
			$introFlagArray[] = $hitesType;
			$this->data = $introFlagArray;
			$introFlagArray = json_encode($introFlagArray);
			MG::setOption('introFlag', $introFlagArray);
		  }
		  return true;
		}
  }

	private static function reqursiveTrim($data) {
		if(is_array($data)) {
			foreach ($data as $key => $value) {
				if(is_array($value)) {
					$data[$key] = self::reqursiveTrim($value);
				} else {
					if(is_string($value)) {
						$data[$key] = trim($value);
					}
				}
			}
		} else {
			$data = trim($data);
		}
		return $data;
	}

	/**
	 * Запускает один из методов данного класса.
	 * @param string $action - название метода который нужно вызвать.
	 */
	public function runAction($action) {
		unset($_POST['mguniqueurl']);
		unset($_POST['mguniquetype']);
		//отсекаем все что после  знака ?
		$action = preg_replace("/\?.*/s", "", $action);

		if (!method_exists($this, $action)) {
			MG::response404();
			return;
		}
		$this->jsonResponse($this->$action());
		exit;
	}

	/**
	 * Добавляет продукт в базу.
	 * @return bool
	 */
	public function addProduct() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_CREATE_PRODUCT'];
			return false;
		}
		$model = new Models_Product;
		$this->data = $model->addProduct($_POST);
		$this->messageSucces = $this->lang['ACT_CREAT_PROD'].' "'.$_POST['name'].'"';
		$this->messageError = $this->lang['ACT_NOT_CREAT_PROD'];
		return true;
	}


	/**
	 * Клонирует  продукт.
	 * @return bool
	 */
	public function cloneProduct() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$model = new Models_Product;
		$model->clone = true;
		$tmpProd = $model->cloneProduct($_POST['id']);
		$this->messageSucces = $this->lang['ACT_CLONE_PROD'];

		// получение вёрстки
        $_POST['adminCatalogRowsFilter'] = " p.id = ".DB::quoteInt($tmpProd['id']);
		$this->getAdminCatalogRows();
		return true;
	}

	/**
	 * Клонирует  заказ.
	 * @return bool
	 */
	public function cloneOrder() {
		if(USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$model = new Models_Order;
		$this->messageSucces = $this->lang['ACT_CLONE_ORDER'];
		$this->messageError = $this->lang['ACT_NOT_CLONE_ORDER'];
		$this->data = $model->cloneOrder($_POST['id']);
		return $this->data;
	}

	/**
	 * Активирует плагин.
	 * @return bool
	 */
	public function activatePlugin() {
		$settingsBtn =  '
			<li>
			<a class="plugSettings tooltip--small"
			href="javascript:void(0);"
			aria-label="'.$this->lang["T_TIP_GOTO_PLUG"].'"
			flow="left"
			tooltip="'.$this->lang["T_TIP_GOTO_PLUG"].'">
			<i class="fa fa-cog" aria-hidden="true"></i>
			</a>
			</li>';
		$this->data['settings_btn'] = $settingsBtn;
		
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$titlePlugin = isset($_POST['pluginTitle']) ? $_POST['pluginTitle'] : '';
		$this->messageSucces = "{$this->lang['ACT_ACTIVE_PLUG']} $titlePlugin";
		$pluginFolder = $_POST['pluginFolder'];

		$result = PM::activatePlugin($pluginFolder);
					
		$this->data['havePage'] = PM::isHookInReg($pluginFolder);
		$informers = MG::createInformerPanel();
		$this->data['desktopInformer'] = $informers['desktop'];
		
		if (!$this->data['havePage']) {
			$this->data['settings_btn'] = false;
		}
		$this->data['mobileInformer'] = $informers['mobile'];
		$this->data['mobileInformersSumm'] = $informers['mobileInformersSumm'];

		return $result;
	}

	/**
	 * Деактивирует плагин.
	 * @return bool
	 */
	public function deactivatePlugin() {
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$pluginTitle = isset($_POST['pluginTitle']) ? $_POST['pluginTitle'] : '';
		$this->messageSucces = $this->lang['ACT_NOT_ACTIVE_PLUG'].' "'.$pluginTitle.'"';
		$this->data['settings_btn'] = false;
		$pluginFolder = $_POST['pluginFolder'];
		$result = PM::deactivatePlugin($pluginFolder);
		return $result;
	}

	/**
	 * Удаляет инсталятор.
	 * @return void
	 */
	public function delInstal() {
		$installDir = SITE_DIR.URL::getCutPath().'/install/';
		$this->removeDir($installDir);
		MG::redirect('');
	}

	/**
	 * Удаляет папку со всем ее содержимым.
	 * @param string $path путь к удаляемой папке.
	 * @return void
	 */
	private function removeDir($path) {
		if (file_exists($path) && is_dir($path)) {
			$dirHandle = opendir($path);

			while (false !== ($file = readdir($dirHandle))) {

				if ($file != '.' && $file != '..') {// Исключаем папки с назварием '.' и '..'
					$tmpPath = $path.'/'.$file;
					chmod($tmpPath, 0777);

					if (is_dir($tmpPath)) {  // Если папка.
						$this->removeDir($tmpPath);
					} else {

						if (file_exists($tmpPath)) {
							// Удаляем файл.
							unlink($tmpPath);
						}
					}
				}
			}
			closedir($dirHandle);

			// Удаляем текущую папку.
			if (file_exists($path)) {
				rmdir($path);
				return true;
			}
		}
	}

	/**
	 * Добавляет картинку для использования в визуальном редакторе.
	 * @return bool
	 */
	public function upload() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 2 &&
			USER::access('page') < 2 &&
			USER::access('product') < 2 &&
			USER::access('category') < 2 &&
			USER::access('setting') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		new Upload(true, $_REQUEST['upload_dir']);
	}

	/**
	 * Добавляет картинку во временную папку для использования в визуальном редакторе.
	 * @return bool
	 */
	public function upload_tmp() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 2 &&
			USER::access('page') < 2 &&
			USER::access('product') < 2 &&
			USER::access('category') < 2 &&
			USER::access('setting') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		new Upload(true, 'prodtmpimg');
	}

	/**
	 * Подключает elfinder.
	 * @return bool
	 */
	public function elfinder() {
		if (
			USER::access('admin_zone') < 1
		) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		require('mg-core/script/elfinder/php/connector.php');
	}

	/**
	 * Добавляет водяной знак.
	 * @return bool
	 */
	public function updateWaterMark() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$uploader = new Upload(false);

		$tempData = $uploader->addImage(false, true);
		$this->data = array('img' => $tempData['actualImageName']);

		if ($tempData['status'] == 'success') {
			$this->messageSucces = $tempData['msg'];
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Обрабатывает запрос на установку плагина.
	 * @return bool
	 */
	public function addNewPlugin() {
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_PLUGIN_INSTALL'];
			return false;
		}
		if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {
			$file_array = $_FILES['addPlugin'];
			$downloadResult = PM::downloadPlugin($file_array);

			if ($downloadResult['data']) {
				$this->messageSucces = $downloadResult['msg'];
				PM::extractPluginZip($downloadResult['data']);
				return true;
			} else {
				$this->messageError = $downloadResult['msg'];
			}
		}
		return false;
	}

	/**
	 * Обрабатывает запрос на установку шаблона.
	 * @return bool
	 */
	public function addNewTemplate() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_TEMPLATE_INSTALL'];
			return false;
		}
		if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {

			if (isset($_FILES['addLanding'])) {
				$file_array = $_FILES['addLanding'];
				$path = 'mg-templates/landings/';
			}
			else{
				$file_array = $_FILES['addTempl'];
				$path = 'mg-templates/';
			}

			//имя шаблона
			$name = $file_array['name'];
			//его размер
			$size = $file_array['size'];
			//поддерживаемые форматы
			$validFormats = array('zip');

			$lang = MG::get('lang');

			if (strlen($name)) {
				$fullName = explode('.', $name);
				$ext = array_pop($fullName);
				$name = implode('.', $fullName);
				if (in_array($ext, $validFormats)) {
					if ($size < (1024 * 1024 * 10)) {
						$actualName = $name.'.'.$ext;
						$tmp = $file_array['tmp_name'];
						if (move_uploaded_file($tmp, $path.$actualName)) {
							$data = $path.$actualName;
							$msg = $this->lang['TEMPL_UPLOAD'];
						} else {
							$msg = $this->lang['TEMPL_UPLOAD_ERR'];
						}
					} else {
						$msg = $this->lang['TEMPL_UPLOAD_ERR2'];
					}
				} else {
					$msg = $this->lang['TEMPL_UPLOAD_ERR3'];
				}
			} else {
				$msg = $this->lang['TEMPL_UPLOAD_ERR4'];
			}

			if (isset($data) && $data) {
				$this->messageSucces = $msg;

				if (file_exists($data)) {
					$zip = new ZipArchive;
					$res = $zip->open($data, ZIPARCHIVE::CREATE);
					if ($res === TRUE) {
						$zip->extractTo($path);
						$zip->close();
						unlink($data);
						return true;
					}
				}
				$this->messageError = $this->lang['TEMPLATE_UNZIP_FAIL'];
				return false;
			} else {
				$this->messageError = $msg;
			}
		}

		return false;
	}

	/*
	 * Проверяет наличие обновлени плагинов
	 * @return bool
	 */
	public function checkPluginsUpdate() {
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_UPDATE_VIEW'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_PLUGIN_CHECK_UPD_SUCCESS'];
		$this->messageError = $this->lang['ACT_PLUGIN_CHECK_UPD_ERR'];

		$libExistsError = MG::libExists();
		if (!empty($libExistsError[0])) {
			$this->messageError = $libExistsError[0];
			return false;
		}
		return PM::checkPluginsUpdate();
	}

	/*
	 * Выполняет обновление плагина
	 * @return bool
	 */
	public function updatePlugin() {
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_UPDATE_PLUGIN'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_PLUGIN_UPD'];
		$this->messageError = $this->lang['ACT_PLUGIN_UPD_ERR'];

		$libExistsError = MG::libExists();
		if (!empty($libExistsError[0])) {
			$this->messageError = $libExistsError[0];
			return false;
		}

		$update = true;
		$pluginName = $_POST['pluginName'];

		$data = PM::getPluginDir($pluginName);

		$update = PM::updatePlugin($pluginName, $data['dir'], $data['version']);

		if($data['last_version']) {
			$this->data['last_version'] = true;
		}

		if(!$update) {
			PM::failtureUpdate($pluginName, $data['version']);
			$failMsg = $this->lang['ACT_PLUGIN_UPD_ERR'];
			if (!is_writable(SITE_DIR.'mg-plugins'.DS.$pluginName)) {
				$failMsg .= ', нет прав на запись в папку плагина '.$pluginName;
			}
			$this->messageError = $failMsg;
			return false;
		}

		return true;
	}
	
	/**
	 * Обрабатывает запрос на удаление плагина.
	 * @return bool
	 */
	public function deletePlugin() {
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_REMOVE_PLUGIN'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_PLUGIN_DEL'].$_POST['id'];
		$this->messageError = $this->lang['ACT_PLUGIN_DEL_ERR'];

		// удаление плагина из папки.
		return PM::deletePlagin($_POST['id']);
	}
	/**
	 * Добавляет картинку товара.
	 * @return bool
	 */
	public function addImage() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 2 &&
			USER::access('page') < 2 &&
			USER::access('product') < 2 &&
			USER::access('category') < 2 &&
			USER::access('setting') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$uploader = new Upload(false);
		//$uploader->deleteImageProduct($_POST['currentImg']);
		$tempData = $uploader->addImage(true);
		if ($tempData['status'] == 'success') {
			$this->messageSucces = $tempData['msg'];
            $this->data = array('img' => str_replace(array('30_', '70_'), '', $tempData['actualImageName']));
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Добавляет картинки товаров.
	 * @return bool
	 */
	public function addImageMultiple() {
		if (USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$tmp = $_FILES;
		$_FILES = array();
		$total = 0;
		$succeeded = 0;
		$finalNames = array();
		foreach ($tmp['photoimg_multiple']['name'] as $key => $value) {
			$_FILES['photoimg']['name'] = $tmp['photoimg_multiple']['name'][$key];
			$_FILES['photoimg']['type'] = $tmp['photoimg_multiple']['type'][$key];
			$_FILES['photoimg']['tmp_name'] = $tmp['photoimg_multiple']['tmp_name'][$key];
			$_FILES['photoimg']['error'] = $tmp['photoimg_multiple']['error'][$key];
			$_FILES['photoimg']['size'] = $tmp['photoimg_multiple']['size'][$key];

			$uploader = new Upload(false);
			$tempData = $uploader->addImage(true);
			$total++;
			if (isset($tempData['actualImageName']) && $tempData['actualImageName']) {
				$finalNames[] = str_replace(array('30_', '70_'), '', $tempData['actualImageName']);
				$succeeded++;
			}

		}

		$this->data = $finalNames;

		if ($total == 1 && $succeeded == 1) {
			if (isset($tempData) && isset($tempData['msg'])) {
				$this->messageSucces = $tempData['msg'];
			}
			return true;
		}

		if ($total == 1 && $succeeded == 0) {
			if (isset($tempData) && isset($tempData['msg'])) {
				$this->messageError = $tempData['msg'];
			}
			return false;
		}

		if ($total > 1 && $succeeded == $total) {
			$this->messageSucces = 'Все изображения загружены';
			return true;
		}

		if ($total > 1 && $succeeded < $total) {
			$this->messageError = 'Загружено '.$succeeded.' из '.$total.' изображений.';
			return false;
		}

	}

	/**
	 * Добавляет картинки товаров.
	 * @return string
	 */
	public function addImageUrl() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 2 &&
			USER::access('page') < 2 &&
			USER::access('product') < 2 &&
			USER::access('category') < 2 &&
			USER::access('setting') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$upload = new Upload(false);
		$temp = $upload->uploadImageFromUrl($_POST['imgUrl'], $_POST['isCatalog']);

		if ($temp['status']) {
      $this->data = $temp['data'];
      $this->messageSucces = $temp['msg'];
		} else {
      $this->messageError = $temp['msg'];
		}

    return $temp['status'];
	}

	/**
	 * Добавляет картинки товаров.
	 * @return string
	 */
	public function addImageUploader() {
		if (USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		if ($_POST['imgType'] == 'image/jpeg' || $_POST['imgType'] == 'image/png' || $_POST['imgType'] == 'image/gif') {
			$_FILES = array();
			$_FILES['photoimg']['name'] = $_POST['imgName'];
			$_FILES['photoimg']['type'] = $_POST['imgType'];
			$_FILES['photoimg']['tmp_name'] = $_POST['imgUrl'];
			$_FILES['photoimg']['error'] = 0;
			$_FILES['photoimg']['size'] = $_POST['imgSize'];
			$uploader = new Upload(false);
			$tempData = $uploader->addImage(true);
			if ($tempData['status'] == 'success') {
				$this->data = str_replace(array('30_', '70_'), '', $tempData['actualImageName']);
				$this->messageSucces = $tempData['msg'];
				return true;
			} else {
				$this->messageError = $tempData['msg'];
				return false;
			}
		} else {
			$this->messageError = $this->lang['IS_NOT_IMAGE'];
			return false;
		}
	}

	 /**
	 * Удаляет картинку товара.
	 * @return bool
	 */
	public function deleteImageProduct() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$uploader = new Upload(false);
		$uploader->deleteImageProduct($_POST['imgFile'], $_POST['id']);
		$this->messageSucces = $this->lang['IMAGE_DELETE_FROM_SERVER'];
		return true;
	}

	/**
	 * Удаляет изображения из временной папки, если товар не был сохранен.
	 * 
	 * @depricated
	 * TODO del it? (not used anywhere)
	 */
	public function deleteTmpImages() {
		$arImages = explode('|', trim($_POST['images'], '|'));
		$product = new Models_Product();
		$product->deleteImagesProduct($arImages);
		return false;
	}

	/**
	 * Добавляет картинку без водяного знака.
	 * @return bool
	 */
	public function addImageNoWaterMark() {
		if (USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$uploader = new Upload(false);
		if (MG::getSetting('waterMarkVariants')=='false' || MG::getSetting('waterMarkVariants')===null) {
			$_POST['noWaterMark'] = true;
		}
		$tempData = $uploader->addImage(true);
		$this->data = array('img' => $tempData['actualImageName']);
		// $documentroot = str_replace('mg-core'.DS.'lib', '', dirname(__FILE__));
		$documentroot = SITE_DIR;
		if ($tempData['status'] == 'success') {
			$this->messageSucces = $tempData['msg'];

			if ($_GET['oldimage'] != 'undefined') {
				if (file_exists($documentroot.'uploads'.DS.$_GET['oldimage'])) {
					// если старая картинка используется только в одном варианте, то она будет удалена
					$res = DB::query('SELECT image FROM `'.PREFIX.'product_variant` WHERE image = '.DB::quote($_GET['oldimage']));
					if (DB::numRows($res) === 1) {
						unlink($documentroot.'uploads'.DS.$_GET['oldimage']);
					}
				}
			}
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Удаляет категорию.
	 * @return bool
	 */
	public function deleteCategory() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_REMOVE_CATEGORY'];
			return false;
		}
		if (empty($_POST['id'])) {
			$this->messageError = 'Не передан идентификатор категории!';
			return false;
		}
		$this->messageSucces = $this->lang['ACT_DEL_CAT'];
		$this->messageError = $this->lang['ACT_NOT_DEL_CAT'];
		if ($_POST['dropProducts'] == 'true') {
			$cats = MG::get('category')->getCategoryList($_POST['id']);
			$cats[] = $_POST['id'];
			$cats = implode(', ', $cats);
			$model = new Models_Product;
			$res = DB::query('SELECT `id` FROM `'.PREFIX.'product` WHERE `cat_id` IN ('.$cats.')');
			while($row = DB::fetchAssoc($res)) {
				$model->deleteProduct($row['id']);
			}
		}

		MG::removeLocaleDataByEntity($_POST['id'], 'category');

		return MG::get('category')->delCategory($_POST['id']);
	}

	/**
	 * Удаляет страницу.
	 * @return bool
	 */
	public function deletePage() {
		if(USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_REMOVE_PAGE'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_DEL_PAGE'];
		$this->messageError = $this->lang['ACT_NOT_DEL_PAGE'];
		MG::removeLocaleDataByEntity($_POST['id'], 'page');
		if(!MG::get('pages')->delPage($_POST['id'])){
			if(!empty(MG::get('pages')->messageError)){
				$this->messageError = MG::get('pages')->messageError;
			}
			return false;
		}
		return true;
	}

	/**
	 * Удаляет пользователя.
	 * @return bool
	 */
	public function deleteUser() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_REMOVE_USER'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_DEL_USER'];
		$this->messageError = $this->lang['ACT_NOT_DEL_USER'];
		return USER::delete($_POST['id']);
	}

	/**
	 * Удаляет товар.
	 * @return bool
	 */
	public function deleteProduct() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_REMOVE_PRODUCT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_DEL_PROD'];
		$this->messageError = $this->lang['ACT_NOT_DEL_PROD'];
		$model = new Models_Product;
		return $model->deleteProduct($_POST['id']);
	}

	/**
	 * Удаляет заказ.
	 * @return bool
	 */
	public function deleteOrder() {      
		if(USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_REMOVE_ORDER'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_DEL_ORDER'];
		$this->messageError = $this->lang['ACT_NOT_DEL_ORDER'];
		$model = new Models_Order;
		$model->refreshCountProducts($_POST['id'], 4);
		$this->data = array('count' => $model->getNewOrdersCount());
		return $model->deleteOrder($_POST['id']);
	}

	/**
	 * Удаляет пользовательскую характеристику товара.
	 * @return bool
	 */
	public function deleteUserProperty() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		// удаление локализации характеристики
		MG::removeLocaleDataByEntity($_POST['id'], 'property');
		// удаление привязанных параметров локализации к самой характеристики
		$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($_POST['id']));
		while($row = DB::fetchAssoc($res)) {
			MG::removeLocaleDataByEntity($row['id'], 'property_data');
		}
		// удаление локализаций для пользовательских характеристик
		$res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE prop_id = '.DB::quoteInt($_POST['id']));
		while($row = DB::fetchAssoc($res)) {
			MG::removeLocaleDataByEntity($row['id'], 'product_user_property_data');
		}

		$res = DB::query('SELECT `plugin` FROM `'.PREFIX.'property` WHERE `id`='.DB::quoteInt($_POST['id']));
		if ($row = DB::fetchArray($res)) {
			$pluginDirectory = PLUGIN_DIR.$row['plugin'].'/index.php';
			if ($row['plugin']&&  file_exists($pluginDirectory)) {
				$this->messageError = $this->lang['ACT_NOT_DEL_PROP_PLUGIN'];
				$result = false;
				return $result;
			}
		}
		$this->messageSucces = $this->lang['ACT_DEL_PROP'];
		$this->messageError = $this->lang['ACT_NOT_DEL_PROP'];
		$result = false;
		if (DB::query('
			DELETE FROM `'.PREFIX.'property`
			WHERE id = '.DB::quoteInt($_POST['id'], true)) &&
			DB::query('
			DELETE FROM `'.PREFIX.'product_user_property_data`
			WHERE prop_id = '.DB::quoteInt($_POST['id'], true)) &&
			DB::query('
			DELETE FROM `'.PREFIX.'category_user_property`
			WHERE property_id = '.DB::quoteInt($_POST['id'], true)) &&
			DB::query('
			DELETE FROM `'.PREFIX.'property_data`
			WHERE prop_id = '.DB::quoteInt($_POST['id'], true))
		) {
			$result = true;
		}
		return $result;
	}

	/**
	 * Удаляет категорию.
	 * @return bool
	 */
	public function editCategory() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_EDIT_CAT'].' "'.$_POST['title'].'"';
		$this->messageError = $this->lang['ACT_NOT_EDIT_CAT'];

		$id = $_POST['id'];
		unset($_POST['id']);
		// Если назначаемая категория, является тойже.
		if ($_POST['parent'] == $id) {
			$this->messageError = $this->lang['ACT_ERR_EDIT_CAT'];
			return false;
		}

		$childsCaterory = MG::get('category')->getCategoryList($id);
		// Если есть вложенные, и одна из них назначена родительской.
		if (!empty($childsCaterory)) {
			foreach ($childsCaterory as $cateroryId) {
				if ($_POST['parent'] == $cateroryId) {
					$this->messageError = $this->lang['ACT_ERR_EDIT_CAT'];
					return false;
				}
			}
		}

		if ($_POST['parent'] == $id) {
			$this->messageError = $this->lang['ACT_ERR_EDIT_CAT'];
			return false;
		}
		return MG::get('category')->editCategory($id, $_POST);
	}

	/**
	 * Сохраняет курс валют.
	 * @return bool
	 */
	public function saveCurrency() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SAVE_CURR'];
		$this->messageError = $this->lang['ACT_NOT_SAVE_CURR'];

		$currencyActive = $currencyShopRate = $currencyShopShort = array();

		foreach ($_POST['data'] as $currency) {
			if (!preg_match("#^[A-Z-]+$#", $currency['iso'])) {
				$this->messageError = $this->lang['ACT_NOT_SAVE_CURR_ISO'].$currency['iso'];
				return false;
			}
			if (!empty($currency['iso'])&&!empty($currency['short'])) {
				$currency['iso'] =  htmlspecialchars($currency['iso']);
				$currency['short'] =  htmlspecialchars($currency['short']);
				$currency['rate'] =  (float)($currency['rate']);
				if ($currency['active'] == 'true') {$currencyActive[] = $currency['iso'];}
				unset($currency['active']);
				$currencyShopRate[$currency['iso']] = $currency['rate'];
				$currencyShopShort[$currency['iso']] = $currency['short'];
			}
		}

		unset($currencyShopRate['']);
		unset($currencyShopShort['']);

		MG::setOption(array('option' => 'currencyRate', 'value' => addslashes(serialize($currencyShopRate))));
		MG::setOption(array('option' => 'currencyShort', 'value' => addslashes(serialize($currencyShopShort))));
		MG::setOption(array('option' => 'currencyActive', 'value' => addslashes(serialize($currencyActive))));

		$settings = MG::get('settings');
		$settings['currencyRate'] = $currencyShopRate;
		$settings['currencyShort'] = $currencyShopShort;
		MG::set('settings', $settings );


		$product = new Models_Product();
		$product->updatePriceCourse(MG::getSetting('currencyShopIso'));

		return true;
	}

	/** Применяет скидку/наценку ко всем вложенным подкатегориям.
	 */
	public function applyRateToSubCategory() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::get('category')->applyRateToSubCategory($_POST['id']);
		return true;
	}

	/**
	 * Отменяет скидку и наценку для выбраной категории.
	 * @return bool
	 */
	public function clearCategoryRate() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_CLEAR_CAT_RATE'];
		MG::get('category')->clearCategoryRate($_POST['id']);
		return true;
	}

	/**
	 * Сохраняет и обновляет параметры товара.
	 * @return bool
	 */
	public function saveProduct() {
		MG::resetAdminCurrency();
		$_POST = json_decode($_POST['data'], true);

		$this->messageSucces = $this->lang['ACT_SAVE_PROD'];
		$this->messageError = $this->lang['ACT_NOT_SAVE_PROD'];
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		if(MG::checkLockEntity('product', $_POST['id']) !== true) {
			return false;
		}

		// Костыль, который убирает цветоразмерку, если сменилась категория на ту, где цветоразмерки нет
		$colorPropId = null;
		$sizePropId = null;
		if (!empty($_POST['id']) && !empty($_POST['variants'])) {
			foreach ($_POST['variants'] as $varKey => $variant) {
				if ($variant['color']) {
					if (!$colorPropId) {
						$colorPropIdSql = 'SELECT `prop_id` '.
							'FROM `'.PREFIX.'property_data` '.
							'WHERE `id` = '.DB::quoteInt($variant['color']);
						$colorPropIdResult = DB::query($colorPropIdSql);
						if ($colorPropIdRow = DB::fetchAssoc($colorPropIdResult)) {
							$colorPropId = intval($colorPropIdRow['prop_id']);
						}
					}
					if (!$colorPropId) {
						unset($_POST['variants'][$varKey]['color']);
					} else {
						$checkColorPropSql = 'SELECT * '.
							'FROM `'.PREFIX.'category_user_property` '.
							'WHERE `property_id` = '.DB::quoteInt($colorPropId).' '.
								'AND `category_id` = '.DB::quoteInt($_POST['cat_id']);
						$checkColorPropResult = DB::query($checkColorPropSql);
						if (!DB::fetchAssoc($checkColorPropResult)) {
							unset($_POST['variants'][$varKey]['color']);
							$removePDataSql = 'DELETE FROM `'.PREFIX.'product_user_property_data` '.
								'WHERE `prop_id` = '.DB::quoteInt($colorPropId).' '.
									'AND `product_id` = '.DB::quoteInt($_POST['id']);
							DB::query($removePDataSql);
						}
					}
				}
				if ($variant['size']) {
					if (!$sizePropId) {
						$sizePropIdSql = 'SELECT `prop_id` '.
							'FROM `'.PREFIX.'property_data` '.
							'WHERE `id` = '.DB::quoteInt($variant['size']);
						$sizePropIdResult = DB::query($sizePropIdSql);
						if ($sizePropIdRow = DB::fetchAssoc($sizePropIdResult)) {
							$sizePropId = intval($sizePropIdRow['prop_id']);
						}
					}
					if (!$sizePropId) {
						unset($_POST['variants'][$varKey]['size']);
					} else {
						$checkSizePropSql = 'SELECT * '.
							'FROM `'.PREFIX.'category_user_property` '.
							'WHERE `property_id` = '.DB::quoteInt($sizePropId).' '.
								'AND `category_id` = '.DB::quoteInt($_POST['cat_id']);
						$checkSizePropResult = DB::query($checkSizePropSql);
						if (!DB::fetchAssoc($checkSizePropResult)) {
							unset($_POST['variants'][$varKey]['size']);
							$removePDataSql = 'DELETE FROM `'.PREFIX.'product_user_property_data` '.
								'WHERE `prop_id` = '.DB::quoteInt($sizePropId).' '.
									'AND `product_id` = '.DB::quoteInt($_POST['id']);
							DB::query($removePDataSql);
						}
					}
				}
			}
		}

		// Ещё костыль, если вариант 1 и у него нет ни цвета, ни размера, то переносим его параметры в товар, а вариант дезинтегрируем
		if (!empty($_POST['variants']) && count($_POST['variants']) === 1 && empty($_POST['variants'][0]['color']) && empty($_POST['variants'][0]['size'])) {
			$_POST['code'] = $_POST['variants'][0]['code'];
			$_POST['price'] = $_POST['variants'][0]['price'];
			$_POST['old_price'] = $_POST['variants'][0]['old_price'];
			$_POST['count'] = $_POST['variants'][0]['count'];
			$_POST['currency_iso'] = $_POST['variants'][0]['currency_iso'];
			unset($_POST['variants']);
		}

		if (isset($_POST['price']) && $_POST['price']) {
			$_POST['price'] = MG::numberDeFormat($_POST['price']);
		}
		if (isset($_POST['old_price']) && $_POST['old_price']) {
			$_POST['old_price'] = MG::numberDeFormat($_POST['old_price']);
			$_POST['old_price'] = MG::convertCustomPrice($_POST['old_price'], $_POST['currency_iso'], 'set');
		}
		if (isset($_POST['code']) && $_POST['code']) {
			$_POST['code'] = str_replace(array(',','|','"',"'"), '', $_POST['code']);
		}

		if (isset($_POST['multiplicity'])) {
		$_POST['multiplicity'] = round(MG::numberDeFormat($_POST['multiplicity']),2);
		$_POST['multiplicity'] = $_POST['multiplicity']>0?$_POST['multiplicity']:1;
		}

		// для автоматической конвертации оптовых цен, чтобы цены не разлетались
		if (!empty($_POST['id'])) {
			if(empty($_POST['variants'])) {
				MG::convertWholePrice($_POST['price'], $_POST['id']);
			} else {
				foreach ($_POST['variants'] as $key => $value) {
					MG::convertWholePrice($value['price'], isset($_POST['id'])?$_POST['id']:null, isset($value['id'])?$value['id']:null);
				}
			}
		}

		// конвертация веса в килограммы
		if (!empty($_POST['weight_unit_calc']) && $_POST['weight_unit_calc'] != 'kg') {
			if(!empty($_POST['weight'])) {
				$_POST['weight'] = MG::getWeightUnit('convert', ['from'=>$_POST['weight_unit_calc'],'to'=>'kg','value'=>$_POST['weight']]);
			}
			if(!empty($_POST['variants'])) {
				foreach ($_POST['variants'] as $key => $value) {
					$_POST['variants'][$key]['weight'] = MG::getWeightUnit('convert', ['from'=>$_POST['weight_unit_calc'],'to'=>'kg','value'=>$value['weight']]);
				}
			}
		}
		if (isset($_POST['weight_unit_calc'])) {
			unset($_POST['weight_unit_calc']);
		}

		$model = new Models_Product;
		$itemId = 0;
		//Перед сохранением удалим все помеченные  картинки продукта физически с диска.
		$_POST = $model->prepareImageName($_POST);


		$images = explode("|", $_POST['image_url']);

		// if(count($_POST['variants']) == 1) {
		//   unset($_POST['variants']);
		// }
		if (isset($_POST['variants']) && is_array($_POST['variants'])) {
			foreach($_POST['variants'] as $cell => $variant) {
				$_POST['variants'][$cell]['code'] = str_replace(array(',','|','"',"'"), '', $variant['code']);
				if ($variant['price']) {
					$_POST['variants'][$cell]['price'] = MG::numberDeFormat($variant['price']);
				}
				if ($variant['old_price']) {
					$variant['old_price'] = MG::numberDeFormat($variant['old_price']);
					$_POST['variants'][$cell]['old_price'] = MG::convertCustomPrice($variant['old_price'], $variant['currency_iso'], 'set');
				}
				unset($_POST['variants'][$cell]['undefined']);
				unset($_POST['variants'][$cell]['variant_url']);
				$images[] = $variant['image'];

				$pos = strpos($variant['image'], '_-_time_-_');

				if ($pos) {
					if (MG::getSetting('addDateToImg') == 'true') {
						$tmp1 = explode('_-_time_-_', $variant['image']);
						$tmp2 = strrpos($tmp1[1], '.');
						$tmp1[0] = date("_Y-m-d_H-i-s", substr_replace($tmp1[0], '.', 10, 0));
						$_POST['variants'][$cell]['image'] = substr($tmp1[1], 0, $tmp2).$tmp1[0].substr($tmp1[1], $tmp2);
					}
					else{
						$_POST['variants'][$cell]['image'] = substr($variant['image'], ($pos+10));
					}
				}
			}
		}


		foreach ($_POST['userProperty'] as $key => $value) {
			if($value['type'] == 'textarea') {
				foreach ($value as $key2 => $value2) {
					if($key2 != 'type') {
						$_POST['userProperty'][$key][$key2]['val'] = html_entity_decode(htmlspecialchars_decode($value2['val']));
						if (!empty($_POST['id']) && $_POST['userProperty'][$key][$key2]['val']) {
							$_POST['userProperty'][$key][$key2]['val'] = MG::moveCKimages($_POST['userProperty'][$key][$key2]['val'], 'product', $_POST['id'], 'prop', 'product_user_property_data', 'name', $key2);
						}
					}
				}
			}
		}

		if(!isset($_POST['count']) || !is_numeric($_POST['count'])) {
			$_POST['count'] = "-1";
		}

		// исключаем дублированные артикулы в строке связаных товаров
		if (!empty($_POST['related'])) {
			$_POST['related'] = implode(',', array_unique(explode(',', $_POST['related'])));
		}

		$clearImages = array();
		$clearImages2 = array();

		foreach ($images as $img) {
			$pos = strpos($img, '_-_time_-_');

			if ($pos) {
				if (MG::getSetting('addDateToImg') == 'true') {
					$tmp1 = explode('_-_time_-_', $img);
					$tmp2 = strrpos($tmp1[1], '.');
					$tmp1[0] = date("_Y-m-d_H-i-s", substr_replace($tmp1[0], '.', 10, 0));
					$clearImages[] = substr($tmp1[1], 0, $tmp2).$tmp1[0].substr($tmp1[1], $tmp2);
				}
				else{
					$clearImages[] = substr($img, ($pos+10));
				}
			}
			else{
				$clearImages[] = $img;
			}
		}

		$tmp = explode("|", $_POST['image_url']);

		foreach ($tmp as $img) {
			$pos = strpos($img, '_-_time_-_');

			if ($pos) {
				if (MG::getSetting('addDateToImg') == 'true') {
					$tmp1 = explode('_-_time_-_', $img);
					$tmp2 = strrpos($tmp1[1], '.');
					$tmp1[0] = date("_Y-m-d_H-i-s", substr_replace($tmp1[0], '.', 10, 0));
					$clearImages2[] = substr($tmp1[1], 0, $tmp2).$tmp1[0].substr($tmp1[1], $tmp2);
				}
				else{
					$clearImages2[] = substr($img, ($pos+10));
				}
			}
			else{
				$clearImages2[] = $img;
			}
		}
		if (!empty($clearImages2)) {
			$_POST['image_url'] = implode('|', $clearImages2);
		}

		//Обновление
		if (!empty($_POST['id'])) {
			$itemId = $_POST['id'];
			$_POST['updateFromModal'] = true; // флаг, чтобы отличить откуда было обновление  товара
			$model->updateProduct($_POST);
			$_POST['image_url'] = $clearImages[0];
			$_POST['currency'] = MG::getSetting('currency');

			// костыль
			$arrVar = $model->getVariants($_POST['id']);
			$minVarPrice = array();
			foreach ($arrVar as $key => $value) {
				if(empty($minVarPrice)) {
					$minVarPrice['price'] = $value['price'];
					$minVarPrice['price_course'] = $value['price_course'];
				} else {
					if($minVarPrice['price_course'] > $value['price_course']) {
						$minVarPrice['price'] = $value['price'];
						$minVarPrice['price_course'] = $value['price_course'];
					}
				}
			}
			if($minVarPrice)
				DB::query('UPDATE '.PREFIX.'product SET '.DB::buildPartQuery($minVarPrice).' WHERE id = '.DB::quoteInt($_POST['id']));

			if (empty($arrVar)) {
				$model->deleteStorageRecordsAllVariants(trim($_POST['id']), 'all');
			} else {
				$model->deleteStorageRecordsProductOnly(trim($_POST['id']), 'all');
			}
		} else {  // добавление
			unset($_POST['delete_image']);
			$newProd = $model->addProduct($_POST);
			if(empty($_POST['id'])) {
				$_POST['id'] = $newProd['id'];
			}
			$itemId = $newProd['id'];
		}

		if(isset($_POST['delete_image']) && $arImages = explode('|', $_POST['delete_image'])) {
			$model->deleteImagesProduct($arImages, $itemId);
		}

		// сохранение цветов и размеров в виде параметров для характеристики, чтобы работало в фильтре
		$propIdColor = $propIdSize = null;
		// узнаем id характеристики цвета
		if (isset($_POST['variants']) && !empty($_POST['variants']) && isset($_POST['variants'][0]['color'])) {
			$res = DB::query('SELECT prop_id FROM '.PREFIX.'property_data WHERE id = '.DB::quoteInt($_POST['variants'][0]['color']));
			while($row = DB::fetchAssoc($res)) {
				$propIdColor = $row['prop_id'];
			}
		}
		DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE prop_id = '.DB::quoteInt($propIdColor).' 
				AND product_id = '.DB::quoteInt($_POST['id']));
		// узнаем id характеристики размера
		if (isset($_POST['variants']) && !empty($_POST['variants']) && isset($_POST['variants'][0]['size'])) {
			$res = DB::query('SELECT prop_id FROM '.PREFIX.'property_data WHERE id = '.DB::quoteInt($_POST['variants'][0]['size']));
			while($row = DB::fetchAssoc($res)) {
				$propIdSize = $row['prop_id'];
			}
		}
		// чистим базу от предположительно устаревших параметров размера и цвета товаров
		DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE prop_id = '.DB::quoteInt($propIdSize).' 
			AND product_id = '.DB::quoteInt($_POST['id']));
		// забиваем новые параметры цвета и размера
		if (isset($_POST['variants']) && is_array($_POST['variants'])) {
			foreach ($_POST['variants'] as $item) {
				if (MG::get('disableVariantSizeMapSave') !== 1 && isset($item['color'])) {
					DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, prop_data_id, product_id, active) VALUES 
					('.DB::quoteInt($propIdColor).', '.DB::quoteInt($item['color']).', '.DB::quoteInt($_POST['id']).', 1)');
				}
				if (MG::get('disableVariantSizeMapSave') !== 1 && isset($item['size'])) {
					DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, prop_data_id, product_id, active) VALUES 
					('.DB::quoteInt($propIdSize).', '.DB::quoteInt($item['size']).', '.DB::quoteInt($_POST['id']).', 1)');
				}
			}
		}

		$model->movingProductImage($images, $itemId, 'uploads/prodtmpimg');

		Storage::clear('product-'.$_POST['id'], 'sizeMap-'.$_POST['id'], 'catalog', 'prop');

		MG::unlockEntity('product', $_POST['id']);

		// получение вёрстки
		$_POST['adminCatalogRowsFilter'] = "p.`id` = ".DB::quoteInt($_POST['id']);
		$this->getAdminCatalogRows();

		return true;
	}

	/**
	 * Обновляет параметры товара (быстрый вариант).
	 * @return bool
	 */
	public function fastSaveProduct() {
		MG::resetAdminCurrency();
		$this->messageSucces = $this->lang['ACT_SAVE_PROD'];
		$this->messageError = $this->lang['ACT_NOT_SAVE_PROD'];
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		if(MG::checkLockEntity('product', $_POST['product_id']) !== true) {
			return false;
		}

		$model = new Models_Product;
		$variant = $_POST['variant'];
		unset($_POST['variant']);

		if ($_POST['field'] == 'price' && $_POST['value']) {
			$_POST['value'] = MG::numberDeFormat($_POST['value']);
			if (substr($_POST['value'], -2, 1) === ',' || substr($_POST['value'], -2, 1) === '.') {
				$_POST['value'] .= '0';
			}
		}

		if ($_POST['field'] == 'old_price' && $_POST['value']) {
			$value = preg_replace('/[^0-9]/', '', $_POST['value']);
			$_POST['value'] = MG::numberDeFormat($value);
			$res = DB::query("SELECT `currency_iso` FROM `".PREFIX."product` WHERE `id` = ".DB::quoteInt($_POST['product_id']));
			if ($row = DB::fetchAssoc($res)) {
				$_POST['value'] = MG::convertCustomPrice($_POST['value'], $row['currency_iso'], 'set');
			}
		}

		if ($_POST['field'] == 'weight' && $_POST['value']) {
			$_POST['value'] = MG::numberDeFormat($_POST['value']);
			$res = DB::query("SELECT p.`weight_unit` as product_unit, c.`weight_unit` as category_unit
				FROM `".PREFIX."product` p 
				LEFT JOIN `".PREFIX."category` c
				ON p.`cat_id` = c.`id`
				WHERE p.`id` = ".DB::quoteInt($_POST['product_id']));
			if ($row = DB::fetchAssoc($res)) {
				if ($row['product_unit']) {
					$weightUnit = $row['product_unit'];
				} elseif ($row['category_unit']) {
					$weightUnit = $row['category_unit'];
				} else {
					$weightUnit = 'kg';
				}
				$_POST['value'] = MG::getWeightUnit('convert', array('from'=>$weightUnit,'to'=>'kg','value'=>$_POST['value']));
			}
		}

		$arr = array(
			$_POST['field'] => htmlspecialchars($_POST['value'])
		);

		// Обновление.
		if ($variant) {
			if ($_POST['field'] == 'price') {
				MG::convertWholePrice($_POST['value'], $_POST['product_id'], $_POST['id']);
			}

			$model->fastUpdateProductVariant($_POST['id'], $arr, $_POST['product_id']);
			$arrVar = $model->getVariants($_POST['product_id']);

			// костыль
			$minVarPrice = array();
			foreach ($arrVar as $key => $value) {
				if(empty($minVarPrice)) {
					$minVarPrice['price'] = $value['price'];
					$minVarPrice['price_course'] = $value['price_course'];
				} else {
					if($minVarPrice['price_course'] > $value['price_course']) {
						$minVarPrice['price'] = $value['price'];
						$minVarPrice['price_course'] = $value['price_course'];
					}
				}
			}
			if($minVarPrice) {
				if ($_POST['field'] == 'price') {
					MG::convertWholePrice($minVarPrice['price'], $_POST['product_id']);
				}
				DB::query('UPDATE '.PREFIX.'product SET '.DB::buildPartQuery($minVarPrice).' WHERE id = '.DB::quoteInt($_POST['product_id']));
			}
		} else {
			if ($_POST['field'] == 'price') {
				MG::convertWholePrice($_POST['value'], $_POST['product_id']);
			}
			$model->fastUpdateProduct($_POST['id'], $arr);
		}

		Storage::clear('product-'.$_POST['id'], 'sizeMap-'.$_POST['id'], 'catalog', 'prop');

		if ($_POST['field'] == 'price') {
			$this->data['shopCurrPrice'] = MG::convertCustomPrice($_POST['value'], $_POST['curr'], 'set').' '.MG::getSetting('currency');
		}

		return true;
	}

	/**
	 * Получение вёрстки строк товаров для раздела "Товары" в панели управления
	 * (для работы нужена переменная $catalog или WHERE часть запроса в $_POST['adminCatalogRowsFilter'])
	 * @param array $catalog результат работы функции getListByUserFilter из Models_Catalog или пусто
	 * @return bool
	 */
	private function getAdminCatalogRows($catalog = array()) {
		$html = '';

		$settingsColumns = unserialize(stripslashes(MG::getSetting('catalogColumns')));
		$activeColumns = array_flip($settingsColumns);

        $moreButtonHolder = false;
        foreach ($settingsColumns as $key) {
            if (
                (!$moreButtonHolder || $key == 'price') &&
                (
                    in_array($key, ['price', 'count', 'code', 'old_price', 'weight']) ||
                    strpos($key, 'opf_') === 0
                )
            ) {
                $moreButtonHolder = $key;
            }
        }

        if (empty($catalog)) {
        	$countPrintRowsProduct = MG::getSetting('countPrintRowsProduct');
        	$model = new Models_Catalog;
        	$catalog = $model->getListByUserFilter($countPrintRowsProduct, $_POST['adminCatalogRowsFilter'], true);
        }

        if (array_key_exists('category', $activeColumns)) {
        	$listCategories = MG::get('category')->getCategoryTitleList();
        } else {
        	$listCategories = array();
        }

		foreach ($catalog['catalogItems'] as $product) {
            $html .= MG::adminLayout(
                'catalog-product-row.php',
                array(
                	'activeColumns'=>$activeColumns,
                	'moreButtonHolder'=>$moreButtonHolder,
                	'listCategories'=>$listCategories,
                	'product'=>$product
                )
            );
        }
        $this->data['html'] = $html;
		$this->data['pager'] = $catalog['pager'];
		if (!empty($product['id'])) {
      $this->data['id'] = $product['id'];
    }
        return true;
	}

	/**
	 * Перезаписывает новым значением, любое поле в любой таблице, в зависимости от входящих параметров.
	 */
	public function fastSaveContent() {
		if(
			USER::access('page') < 2 &&
			USER::access('product') < 2 &&
			USER::access('category') < 2
		) {
			return false;
		}

		// Исключени для пустых данных (Может возникнуть при конфликте CKEDITORA в публичной части и включенном режиме редактирования)
		if(empty($_POST['table'])){
			return false;
		}
		if(empty($_POST['field'])){
			return false;
		}
		if(empty($_POST['id'])){
			return false;
		}

		// Обрабатываем изображения из ckeditor (Удаляем лишние, создаём webp)
		if (!empty($_POST['dir']) && !empty($_POST['cleanImages'])) {
			$imgsDir = DS.'uploads'.DS.$_POST['dir'].DS;

			// Достаём изображения из контента
			$contentImages = [];
			$imgRegex = '/<img[^>]*src=\"(.*?)\"/si';
			preg_match_all($imgRegex, $_POST['content'], $imgMatches);
			if (!empty($imgMatches[1])) {
				foreach ($imgMatches[1] as $matchedImgUrl) {
					$matchedImgPath = str_replace('/', DS, str_replace(SITE, '', $matchedImgUrl));
					if (strpos($matchedImgPath, $imgsDir) !== false) {
						$contentImages[] = str_replace($imgsDir, '', $matchedImgPath);
					}
				}
			}

			// Достаём изображения из папки
			$images = [];
			$imgsDirPath = SITE_DIR.$imgsDir;
			$webpImgsDirPath = str_replace(SITE_DIR.DS.'uploads'.DS, SITE_DIR.DS.'uploads'.DS.'webp'.DS, $imgsDirPath);
			if (is_dir($imgsDirPath)) {
				$images = array_diff(scandir($imgsDirPath), ['.', '..']);
			}

			// Удаляем лишние изображения
			if ($images) {
				$imagesForDeletion = array_diff($images, $contentImages);
				if ($imagesForDeletion) {
					foreach ($imagesForDeletion as $imageForDeletion) {
						unlink($imgsDirPath.$imageForDeletion);
						unlink($webpImgsDirPath.$imageForDeletion);
					}
				}
			}
		}

		// Проверяем на каком языке идёт сохранение контента
		$lang = false;
		$rawAllowedLangs = MG::getSetting('multiLang', true);
		$allowedLangs = [];
		foreach ($rawAllowedLangs as $rawAllowedLang) {
			$allowedLangs[] = $rawAllowedLang['short'];
		}

		if (in_array($_POST['lang'], $allowedLangs)) {
			$lang = $_POST['lang'];
			// Если сохранение идёт на языке отличном от стандартного (Русского)
			// То создаём/обновляем запись в таблице mg_locales, а не в целевой
			$table = mb_substr($_POST['table'], mb_strlen(PREFIX));
			$localeSql = 'SELECT `id` FROM `'.PREFIX.'locales` '.
				'WHERE `id_ent` = '.DB::quoteInt($_POST['id']).' '.
					'AND `table` = '.DB::quote($table).' '.
					'AND `field` = '.DB::quote($_POST['field']).' '.
					'AND `locale` = '.DB::quote($lang).';';
			$localeQuery = DB::query($localeSql);
			if ($localeIdResult = DB::fetchAssoc($localeQuery)) {
				// Если локаль для целевой таблицы и поля уже существует, то обновляем её контент
				$localeId = $localeIdResult['id'];
				$updateLocaleSql = 'UPDATE `'.PREFIX.'locales` '.
					'SET `text` = '.DB::quote($_POST['content']).' '.
					'WHERE `id` = '.DB::quoteInt($localeId).';';
				return DB::query($updateLocaleSql);
			} else {
				// Иначе создаём новую записть в таблице с локалями
				$newLocaleData = [
					DB::quoteInt($_POST['id']),
					DB::quote($lang),
					DB::quote($table),
					DB::quote($_POST['field']),
					DB::quote($_POST['content']),
				];
				$createLocaleSql = 'INSERT INTO `'.PREFIX.'locales` '.
					'(`id_ent`, `locale`, `table`, `field`, `text`) '.
					'VALUES ('.implode(', ', $newLocaleData).');';
				return DB::query($createLocaleSql);
			}
		} else {
			// Иначе обнавляем запись в целевой таблице
			if (!DB::query('
				UPDATE `'.DB::quote($_POST['table'], true).'`
				SET `'.DB::quote($_POST['field'], true).'` = '.DB::quote($_POST['content']).'
				WHERE id = '.DB::quoteInt($_POST['id'], true))) {
				return false;
			}
			if ($_POST['table'] == PREFIX.'product') {
				Storage::clear('product-'.$_POST['id'].'-'.(isset($_COOKIE['mg_to_script_langP'])?$_COOKIE['mg_to_script_langP']:LANG).'-'.MG::getSetting('currencyShopIso'));
			} else {
				Storage::clear();
			}
		}



		return true;
	}

	/**
	 * Устанавливает флаг для вывода продукта в блоке рекомендуемых товаров.
	 * @return bool
	 */
	public function recomendProduct() {
		$this->messageSucces = $this->lang['ACT_PRINT_RECOMEND'];
		$this->messageError = $this->lang['ACT_NOT_PRINT_RECOMEND'];
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		$model = new Models_Product;
		// Обновление.
		if (!empty($_POST['id'])) {
			$model->updateProduct($_POST);
		}

		if ($_POST['recommend']) {
			return true;
		}

		return false;
	}

	/**
	 * Устанавливает флаг активности продукта.
	 * @return bool
	 */
	public function visibleProduct() {
		if(USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT_PRODUCT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_V_PROD'];
		$this->messageError = $this->lang['ACT_UNV_PROD'];

		$model = new Models_Product;
		// Обновление.
		if (!empty($_POST['id'])) {
			$model->updateProduct($_POST);
		}

		if ($_POST['activity']) {
			return true;
		}

		return false;
	}

	/**
	 * Устанавливает флаг активности пользовательской характеристики.
	 * @return bool
	 */
	public function visibleProperty() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_V_PROP'];
		$this->messageError = $this->lang['ACT_UNV_PROP'];

		// Обновление.
		if (!empty($_POST['id'])) {
			DB::query("
				UPDATE `".PREFIX."property`
				SET `activity`= ".DB::quote($_POST['activity'])." 
				WHERE `id` = ".DB::quoteInt($_POST['id'], true)
			);
		}

		if ($_POST['activity']) {
			return true;
		}

		return false;
	}

	/**
	 * Устанавливает флаг использования в фильтрах указанных характеристик.
	 * @return bool
	 */
	public function filterProperty() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['PROP_VIEWED_IN_FILTER'];
		$this->messageError = '';

		// Обновление.
		if (!empty($_POST['id'])) {
			DB::query("
				UPDATE `".PREFIX."property`
				SET `filter`= ".DB::quote($_POST['filter'])." 
				WHERE `id` = ".DB::quoteInt($_POST['id'], true)
			);
		}

		if ($_POST['filter']) {
			return true;
		}

		return false;
	}

	/**
	 * Устанавливает флаг для использования характеристики в товарах.
	 * @return bool
	 */
	public function filterVisibleProperty() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_FILTER_PROP'];
		$this->messageError = $this->lang['ACT_UNFILTER_PROP'];

		// Обновление.
		if (!empty($_POST['id'])) {
			DB::query("
				UPDATE `".PREFIX."property`
				SET `filter`= ".DB::quote($_POST['filter'])." 
				WHERE `id` = ".DB::quoteInt($_POST['id'], true)
			);
		}

		if ($_POST['filter']) {
			return true;
		}

		return false;
	}

	/**
	 * Устанавливает флаг для вывода продукта в блоке новых товаров.
	 * @return bool
	 */
	public function newProduct() {
		$this->messageSucces = $this->lang['ACT_PRINT_NEW'];
		$this->messageError = $this->lang['ACT_NOT_PRINT_NEW'];
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$model = new Models_Product;
		// Обновление.
		if (!empty($_POST['id'])) {
			$model->updateProduct($_POST);
		}

		if ($_POST['new']) {
			return true;
		}

		return false;
	}

	/**
	 * Устанавливает флаг для выбранной страницы, чтобы выводить ее в главном меню.
	 * @return bool
	 */
	public function printMainMenu() {
		if(USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ADD_IN_MENU'];
		$this->messageError = $this->lang['NOT_ADD_IN_MENU'];


		// Обновление.
		if (!empty($_POST['id'])) {
			MG::get('pages')->updatePage($_POST);
		}

		if ($_POST['print_in_menu']) {
			return true;
		}

		return false;
	}

	/**
	 * Печать заказа.
	 */
	public function printOrder() {
		if(USER::access('order') == 0) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if (!$_POST['template']) {
			$_POST['template'] = "order";
		}
		if (!$_POST['usestamp']) {
			$_POST['usestamp'] = "false";
		}
		$this->messageSucces = $this->lang['ACT_PRINT_ORD'];
		$model = new Models_Order;
		$this->data = array('html' => $model->printOrder($_POST['id'], true, $_POST['template'], $_POST['usestamp']));
		return true;
	}

	/**
	 * Получает цены после скидки в админке.
	 */
	public function getDiscount() {
		if (USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->data = Models_Order::getOrderDiscount();
		return true;
	}

	/**
	 * Сохраняет и обновляет параметры заказа.
	 * @return bool
	 */
	public function saveOrder() {
		if(USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if(MG::checkLockEntity('order', $_POST['id']) !== true) {
			return false;
		}
		$_POST['order_content'] = json_decode($_POST['order_content'], true);
        foreach ($_POST['order_content'] as &$value){
          if(isset($value['storage_id'])){
            $storage_id = array();
            foreach ($value['storage_id'] as $val){
              $str = explode('_', $val);
              $storage_id[$str[0]] = trim($str[1]);
            }
            $value['storage_id'] = $storage_id;
          }
        }    
		$this->messageSucces = $this->lang['ACT_SAVE_ORD'];
		$this->messageError = $this->lang['ACT_SAVE_ORDER'];

		$orderContentCount = 0;
		if (!empty($_POST['order_content']) && is_array($_POST['order_content'])) {
			$orderContentCount = count($_POST['order_content']);
		}
		if (intval($orderContentCount) !== intval($_POST['orderPositionCount'])) {
			$this->messageError = $this->lang['ORDER_TO_BIG'];
			return false;
		}

		if (!empty($_POST['address_parts'])) {
			$_POST['address_parts'] = json_decode($_POST['address_parts'], true);
			$tmp = array_filter($_POST['address_parts']);
			if (empty($tmp)) {
				$_POST['address_parts'] = '';
			} else{
				$_POST['address_parts'] = addslashes(serialize($_POST['address_parts']));
				$_POST['address'] = implode(', ', $tmp);
			}
		}
		if (!empty($_POST['name_parts'])) {
			$_POST['name_parts'] = json_decode($_POST['name_parts'], true);
			$tmp = array_filter($_POST['name_parts']);
			if (empty($tmp)) {
				$_POST['name_parts'] = '';
			} else {
				$_POST['name_parts'] = addslashes(serialize($_POST['name_parts']));
				$_POST['name_buyer'] = trim(implode(' ', $tmp));
			}

		}
                $prodErrorArray = array();
                $modelOrder = new Models_Order;
        if(!MG::enabledStorage()){  
		foreach ($_POST['order_content'] as $key => $prod) {
                    //Флаг, отвечающий за запись в массив товаров, количество которых указано неверно
                    $flag = true;
                    //Если есть варианты товаров
                    if ($prod['variant_id']) {
                        $res_var = DB::query('
                    SELECT  pv.id, p.`title`, pv.`title_variant`, pv.count, p.link_electro
                    FROM `' . PREFIX . 'product_variant` pv   
                    LEFT JOIN `' . PREFIX . 'product` as p ON 
                    p.id = pv.product_id   
                    WHERE pv.id =' . DB::quote($prod['variant_id']));
                        if ($prodDB = DB::fetchArray($res_var)) {                           
							// Проверяем наличие электронных товаров в базе и устанавливаем соответствующий флаг у товаров в заказе
							if ($prodDB['link_electro']) {
								$_POST['order_content'][$key]['electro'] = 1;
							} else {
								$_POST['order_content'][$key]['electro'] = 0;
							}
                            if ($prodDB['count'] >= 0 && $prodDB['count'] < $prod['count']) {
                                //Если мы редактируем заказ, а не создаем
                                if ($_POST['id']) {
                                    $orderInfo = $modelOrder->getOrder('id = ' . $_POST['id']);
                                    $orderConten = unserialize(str_replace('\\', '', $orderInfo[$_POST['id']]['order_content']));
                                    foreach ($orderConten as $productFromOrderContent) {
                                        if ((trim($productFromOrderContent['code']) == trim($prod['code'])) && (($productFromOrderContent['count'] >= $prod['count']) || (($prod['count'] - $productFromOrderContent['count']) <= $prodDB['count']))) {
                                            $flag = true;
                                            break;
                                        }else{
                                            $flag = false;
                                        }
                                    }
                                } else {
                                    $flag = false;
                                }
                            }
                        }
                    } else {
                        $res_pr = DB::query('
                    SELECT id, title, count, link_electro
                    FROM `' . PREFIX . 'product` p 
                    WHERE id =' . DB::quote($prod['id']));
                        if ($prodDB = DB::fetchArray($res_pr)) {
							// Проверяем наличие электронных товаров в базе и устанавливаем соответствующий флаг у товаров в заказе
							if ($prodDB['link_electro']) {
								$_POST['order_content'][$key]['electro'] = 1;
							} else {
								$_POST['order_content'][$key]['electro'] = 0;
							}
                            if ($prodDB['count'] >= 0 && $prodDB['count'] < $prod['count']) {
                                //Если мы редактируем заказ, а не создаем
                                if ($_POST['id']) {
                                    $orderInfo = $modelOrder->getOrder('id = ' . $_POST['id']);
                                    $orderConten = unserialize(str_replace('\\', '', $orderInfo[$_POST['id']]['order_content']));
                                    foreach ($orderConten as $productFromOrderContent) {
                                        if ((trim($productFromOrderContent['code']) == trim($prod['code'])) && (($productFromOrderContent['count'] >= $prod['count']) || (($prod['count'] - $productFromOrderContent['count']) <= $prodDB['count']))) {
                                            $flag = true;
                                            break;
                                        }else{
                                            $flag = false;
                                        }
                                    }
                                } else {
                                    $flag = false;
                                }
                            }
                        }
                    }
                    if ($flag == false) {
                        $prodErrorArray[] = [
                            'id' => $prod['id'],
                            'count' => $prod['count'],
                            'code' => $prod['code']
                        ];
                    }
                    if (empty($prod['variant_id'])) {
                        $_POST['order_content'][$key]['variant_id'] = null;
                    }
                }
                if(count($prodErrorArray) > 0){
                    $this->data = json_encode($prodErrorArray);
                    $this->messageError = 'error product count';
                    return false; 
                }
        }  
		if ((!empty($_POST['storage']) && $_POST['storage'] == 'default') || !MG::enabledStorage()) {
			$_POST['storage'] = '';
		}

		$plugParams = isset($_POST['orderPluginsData'])?$_POST['orderPluginsData']:'';
    	unset($_POST['orderPluginsData']);

		// сигнал плагинам подготовить данные
    	MG::createHook('adminOrderSavePrepareData', '', array('pluginParams'=>$plugParams,'orderParams'=>$_POST));

		MG::resetAdminCurrency();
		unset($_POST['orderPositionCount']);

		$shopCurr = MG::getSetting('currencyShopIso');
		if ($_POST['currency_iso'] == $shopCurr) {
			$_POST['summ_shop_curr'] = $_POST['summ'];
			$_POST['delivery_shop_curr'] = $_POST['delivery_cost'];
		} else {
			$rates = MG::getSetting('currencyRate');
			$_POST['summ_shop_curr'] = (float)round($_POST['summ']*$rates[$_POST['currency_iso']],2);
			$_POST['delivery_shop_curr'] = (float)round($_POST['delivery_cost']*$rates[$_POST['currency_iso']],2);
		}

		// Cобираем воедино все параметры от юр. лица если они были переданы, для записи в информацию о заказе.
		$_POST['yur_info'] = '';
		$informUser = $_POST['inform_user'];
		if ($informUser == 'true') {
			$informUserText = $_POST['inform_user_text'];
		} else {
			$informUserText = '';
		}

		unset($_POST['inform_user']);
		unset($_POST['inform_user_text']);

		if (!empty($_POST['inn'])) {
			$_POST['yur_info'] = array(
				'nameyur' => htmlspecialchars($_POST['nameyur']),
				'adress' => htmlspecialchars($_POST['adress']),
				'inn' => htmlspecialchars($_POST['inn']),
				'kpp' => htmlspecialchars($_POST['kpp']),
				'bank' => htmlspecialchars($_POST['bank']),
				'bik' => htmlspecialchars($_POST['bik']),
				'ks' => htmlspecialchars($_POST['ks']),
				'rs' => htmlspecialchars($_POST['rs']),
			);
		}

		$customFields = isset($_POST['customFields'])?$_POST['customFields']:array();
		unset($_POST['customFields']);
		$adminComment = isset($_POST['commentExt'])?$_POST['commentExt']:array();
		unset($_POST['commentExt']);
        
		$id = $_POST['id'];

		$model = new Models_Order;
		// Обновление.
		if (!empty($_POST['id'])) {
			unset($_POST['inn']);
			unset($_POST['kpp']);
			unset($_POST['nameyur']);
			unset($_POST['adress']);
			unset($_POST['bank']);
			unset($_POST['bik']);
			unset($_POST['ks']);
			unset($_POST['rs']);
			unset($_POST['ogrn']);

			if (!empty($_POST['yur_info'])) {
				$_POST['yur_info'] = addslashes(serialize($_POST['yur_info']));
			}
			$_POST['delivery_cost'] = MG::numberDeFormat($_POST['delivery_cost']);
			// НОВЫЙ НОВЫЙ ВАРИАНТ
			if (MG::enabledStorage()) {
				$oldOrderArray = $model->getOrder('`id` = '.DB::quoteInt($_POST['id']));
				$oldOrder = array_shift($oldOrderArray);
				$oldOrderProducts = unserialize(stripslashes($oldOrder['order_content']));
				$newOrderProducts = $_POST['order_content'];
				$productsModel = new Models_Product();
				$storagesData = [];
				$productsKeys = [];
				foreach ($newOrderProducts as $productKey => $newProduct) {
					$newProductId = intval($newProduct['id']);
					$newVariantId = intval($newProduct['variant_id']);
					$productsKeys[$newProductId][$newVariantId] = $productKey;
				}
				foreach ($newOrderProducts as $newProduct) {
					$newProductId = intval($newProduct['id']);
					$newVariantId = intval($newProduct['variant_id']);
					foreach ($newProduct['storage_id'] as $storageId => $countFromStorage) {
						$storagesData[$newProductId][$newVariantId][$storageId]['new'] = $countFromStorage;
					}
				}
				foreach ($oldOrderProducts as $oldProduct) {
					$oldProductId = intval($oldProduct['id']);
					$oldVariantId = intval($oldProduct['variant_id']);
					foreach ($oldProduct['storage_id'] as $storageDataString) {
						$storageDataArray = explode('_', $storageDataString);
						$countFromStorage = floatval(array_pop($storageDataArray));
						$storageId = implode('_', $storageDataArray);
						$storagesData[$oldProductId][$oldVariantId][$storageId]['old'] = floatval($countFromStorage);
					}
				}

				$storagesErrors = [];
				foreach ($storagesData as $productId => $productData) {
					$productVariantData = reset($productData);
					$variantId = key($productData);
					foreach ($productVariantData as $storageId => $productStorageData) {
						if (!isset($productStorageData['new'])) {
							continue;
						}
						$oldCount = 0;
						if (!empty($productStorageData['old'])) {
							$oldCount = floatval($productStorageData['old']);
						}
						$newCount = floatval($productStorageData['new']);
						$countDiff = $newCount - $oldCount;
						if ($countDiff > 0) {
							$countOnStorage = $productsModel->getProductStorageCount($storageId, $productId, $variantId);
							if (floatval($countOnStorage) === floatval(-1)) {
								continue;
							}
							if ($countOnStorage < $countDiff) {
								$productKey = $productsKeys[$productId][$variantId];
								$storagesErrors[] = [
									'id' => $productKey,
									'prod_id' => $productId,
									'storage' => $storageId,
								];
							}
						}
					}
				}
				if (!empty($storagesErrors)) {
					$storagesErrorsJson = json_encode($storagesErrors, JSON_UNESCAPED_UNICODE);
					$this->data = $storagesErrorsJson;
					$this->messageError = 'error product count storage';
					return false;
				}
			}

			// НОВЫЙ ВАРИАНТ
			// if (MG::enabledStorage()) {
			// 	$oldOrderArray = $model->getOrder('`id` = '.DB::quoteInt($_POST['id']));
			// 	$oldOrder = array_shift($oldOrderArray);
			// 	$oldOrderProducts = unserialize(stripslashes($oldOrder['order_content']));
			// 	$newOrderProducts = $_POST['order_content'];
			// 	$productsModel = new Models_Product();
			// 	foreach ($newOrderProducts as $newOrderProduct) {
			// 		$newProductId = intval($newOrderProduct['id']);
			// 		$newVariantId = intval($newOrderProduct['variant_id']);
			// 		$storagesData = $productsModel->getProductStoragesData($newProductId, $newVariantId);
			// 		$storageTotalCount = $productsModel->getProductStorageTotalCount($newProductId, $newVariantId);
			// 		foreach ($oldOrderProducts as $oldOrderProduct) {
			// 			$oldProductId = intval($newOrderProduct['id']);
			// 			$oldVaraintId = intval($newOrderProduct['variant_id']);
			// 			if (
			// 				$newProductId === $oldProductId &&
			// 				$newVariantId === $oldVaraintId
			// 			) {
			// 				$oldProductCount = floatval($oldOrderProduct['count']);
			// 				$newProductCount = floatval($newOrderProduct['count']);
			// 				if (floatval($newProductCount) > floatval($oldProductCount)) {
			// 					$countDiff = $newProductCount - $oldProductCount;
			// 					if ($countDiff > $storageTotalCount) {
			// 						$this->data = json_encode($storagesData);
			// 						$this->messageError = 'error product count storage';
			// 					}
			// 					if ($oldOrderProduct['storage_id']) {
			// 						$storage = $oldOrderProduct['storage_id'];
			// 						if ($countDiff > $storagesData[$storage]) {
			// 							$this->data = json_encode($storagesData);
			// 							$this->messageError = 'error product count storage';
			// 						}
			// 					}
			// 				}
			// 				break;
			// 			}
			// 		}
			// 	}
			// }

			// СТАРЫЙ ВАРИАНТ
            //Проверка, хватает ли товара на складах
            // if(MG::enabledStorage()) {
            //   $sql = "SELECT `order_content` FROM `".PREFIX."order` WHERE `id` = ".db::quoteInt($_POST['id']);
            //   $row = db::fetchAssoc(db::query($sql)); 
            //   $orderContentOld = unserialize(stripslashes($row['order_content'])); 
            //   $dataStorageArray = array();
            //   //Идем по всем товарам 
            //   foreach ($_POST['order_content'] as $key => $prod){
            //     $sql = "SELECT `storage`, `product_id`, `count` FROM `".PREFIX."product_on_storage` WHERE `count` >= 0 AND `product_id` = ".DB::quoteInt($prod['id'])." AND `variant_id` = ".DB::quoteInt($prod['variant_id']);
            //     $storageProdArray = array();
            //     $storageNameArray = unserialize(stripslashes(MG::getSetting('storages')));
            //     $storName = array();
            //     foreach ($storageNameArray as $stor){
            //       $storName[$stor['id']] = $stor['name'];
            //     }
            //     $res = db::query($sql);
            //     //Получаем из БД количество товара, находящегося на всех складах
            //     while($row = DB::fetchAssoc($res)) {
            //       $storageProdArray[$row['storage']] = $row['count'];
            //     }
            //     //Смотрим сколько у нас товара было до покупки на складе
            //     foreach ($orderContentOld as $oldProd){
            //       if( isset($oldProd['storage_id']) && $prod['id'] == $oldProd['id']){
            //         foreach ($oldProd['storage_id'] as $storageid => $storageCount){
            //           if(isset($storageProdArray[$storageid])){
            //             $storageProdArray[$storageid] = $storageProdArray[$storageid] + trim($storageCount);
            //           }
            //         }
            //       }
            //     }
            //     //Проверяем, хватает ли у нас товара на складе после редактирования заказа
            //     if(isset($prod['storage_id'] )){
            //       foreach ($prod['storage_id'] as $storageid => $storageCount){
            //         if( isset($storageProdArray[$storageid]) && ($storageProdArray[$storageid] - $storageCount) < 0){
            //           $dataStorageArray[] = [ 'id' => $key, 'prod_id' => $prod['id'], 'storage' => $storageid];
            //         }
            //       }
            //     }
            //   }
            //   if(count($dataStorageArray) > 0){
            //     $this->data = json_encode($dataStorageArray);
            //     $this->messageError = 'error product count storage';
			// 	return false;                
            //   }
            // }
            $_POST['order_content'] = addslashes(serialize($_POST['order_content']));
            //Если статус заказа "Отменен" то не переопределяем отстаки на складах
            if( (MG::enabledStorage() && $_POST['status_id'] != 4) || (!MG::enabledStorage())){ 
              $model->refreshCountAfterEdit($_POST['id'], $_POST['order_content']);
            }
			// возвращаем товары на склад если заказ отменен
			// узнаем статус который был раньше
			$res = DB::query('SELECT status_id FROM '.PREFIX.'order WHERE id = '.DB::quoteInt($_POST['id']));
			$row = DB::fetchAssoc($res);
			$statusOld = $row['status_id'];
			// если был отменен, то возвращаем товары в заказ
			if(($statusOld == 4) && ($_POST['status_id'] != 4)) {

				// если включены склады
				if(MG::enabledStorage()) {
					// проверяем, есть ли товары для возврата
                    $dataStorageArray = array();
					foreach (unserialize(stripcslashes($_POST['order_content'])) as $prodCount => $item) {
                      foreach ($item['storage_id'] as $storage => $count){
                        $res = DB::query("SELECT `count` FROM ".PREFIX."product_on_storage WHERE product_id = ".DB::quoteInt($item['id']).
                                " AND variant_id = ".DB::quoteInt($item['variant_id'])." AND  `storage` = ".DB::quote($storage));
                        $row = DB::fetchAssoc($res);
						if (intval($row['count']) === -1) {
							continue;
						}
                        if($row['count'] < $count){
                          $dataStorageArray[] = [ 'id' => $prodCount, 'prod_id' => $item['id'], 'storage' => $storage];
                        }
                      }
					}
                    if(count($dataStorageArray) > 0){
                      $this->data = json_encode($dataStorageArray);
                      $this->messageError = 'error product count storage';
                      return false;                
                    }
				}

			}

			if($statusOld != $_POST['status_id']) {
				if ($statusOld === '4' && $_POST['status_id'] !== '4') {
					$productsIds = [];
					$variantsIds = [];
					foreach ($orderConten as $productData) {
						if ($productData['variant_id']) {
							$variantsIds[] = $productData['variant_id'];
							continue;
						}
						$productsIds[] = $productData['id'];
					}
					$finishedProductsIds = [];
					if ($productsIds) {
						$finishedProductsSql = 'SELECT `code` FROM `'.PREFIX.'product` '.
							'WHERE `count` = 0 AND `id` IN ('.DB::quoteIN($productsIds).');';
						$finishedProductsQuery = DB::query($finishedProductsSql);
						while ($finishedProductsResult = DB::fetchAssoc($finishedProductsQuery)) {
							$finishedProductsIds[] = ['code' => $finishedProductsResult['code']]; 
						}
					}
					if ($variantsIds) {
						$finishedVariantsSql = 'SELECT `code` FROM `'.PREFIX.'product_variant` '.
							'WHERE `count` = 0 AND `id` IN ('.DB::quoteIN($variantsIds).');';
						$finishedVariantsQuery = DB::query($finishedVariantsSql);
						while ($finishedVariantsResult = DB::fetchAssoc($finishedVariantsQuery)) {
							$finishedProductsIds[] = ['code' => $finishedVariantsResult['code']];
						}
					}

					if(!empty($finishedProductsIds)){
						$this->data = json_encode($finishedProductsIds);
						$this->messageError = 'error product count';
						return false;
				  }
					
				}

				$user = USER::getThis();
				$user_id = $user->id;

				$statusNewName = '';
				$statusOldName = '';

				$ls = Models_Order::$status;
				if (class_exists('statusOrder')) {
					$dbQuery = DB::query('SELECT `id_status`, `status` FROM `'.PREFIX.'mg-status-order` WHERE `id_status` IN ('.DB::quote($statusOld).','.DB::quote($_POST['status_id']).')');
					while ($dbRes = DB::fetchArray($dbQuery)) {
						if ($_POST['status_id'] == $dbRes['id_status']) {
							$statusNewName = $dbRes['status'];
						} else if ($statusOld == $dbRes['id_status']) {
							$statusOldName = $dbRes['status'];
						}
					}
				} else {
					foreach ($ls as $key => $value) {
						$statusNewName = $this->lang[ $ls[$_POST['status_id']] ];
						$statusOldName = $this->lang[ $ls[$statusOld] ];
					}
				}

				$model->addAdminCommentOrder($_POST['id'], 'Изменён статус заказа с <b>«'.$statusOldName.'»</b> на <b>«'.$statusNewName.'»</b>.', $user_id);
			}

			// $model->refreshCountProducts($_POST['id'], $_POST['status_id']); // TODO

			$model->updateOrder($_POST, $informUser, $informUserText);

			if (($statusOld != $_POST['status_id']) && in_array($_POST['status_id'], array(2, 5)) && method_exists($model, 'sendLinkForElectro')) {
				$model->sendLinkForElectro($_POST['id']);
			}
		} else {
            //Проверка, хватает ли товара на складах при создании заказа
			// TODO отрефакторить, вынести в модель
            if(MG::enabledStorage()) {
              $dataStorageArray = array();
              foreach ($_POST['order_content'] as $key => $prod){
                $sql = "SELECT `storage`, `product_id`, `count` FROM `".PREFIX."product_on_storage` WHERE `count` >= 0 AND `product_id` = ".DB::quoteInt($prod['id'])." AND `variant_id` = ".DB::quoteInt($prod['variant_id']);
                $storageProdArray = array();
                $storageNameArray = unserialize(stripslashes(MG::getSetting('storages')));
                $storName = array();
                foreach ($storageNameArray as $stor){
                  $storName[$stor['id']] = $stor['name'];
                }
                $res = db::query($sql);
                while($row = DB::fetchAssoc($res)) {
                  $storageProdArray[$row['storage']] = $row['count'];
                }
                $summOnStorage = 0;
                foreach ($storageProdArray as $keyStor => $countProdOnStorage){
                  if(array_key_exists($keyStor, $prod['storage_id']) && $countProdOnStorage < $prod['storage_id'][$keyStor]){
                    $dataStorageArray[] = [ 'id' => $key, 'prod_id' => $prod['id'], 'storage' => $keyStor];
                  }
                }
              }
              if(count($dataStorageArray) > 0){
                $this->data = json_encode($dataStorageArray);
                $this->messageError = 'error product count storage';
				return false;                
              }
            }
            if (!isset($_POST['user_email']) && !USER::getUserInfoByEmail($_POST['contact_email'], 'login_email')) {
            	$model->passNewUser = MG::genRandomWord(10);
				
				$newUserData = array(
					'email' => htmlspecialchars($_POST['contact_email']),
					'role' => 2,
					'name' => htmlspecialchars($_POST['name_buyer']),
					'pass' => $model->passNewUser,
					'address' => htmlspecialchars($_POST['address']),
					//'phone' => htmlspecialchars($_POST['phone']),
					'inn' => htmlspecialchars($_POST['inn']),
					'kpp' => htmlspecialchars($_POST['kpp']),
					'nameyur' => htmlspecialchars($_POST['nameyur']),
					'adress' => htmlspecialchars($_POST['adress']),
					'bank' => htmlspecialchars($_POST['bank']),
					'bik' => htmlspecialchars($_POST['bik']),
					'ks' => htmlspecialchars($_POST['ks']),
					'rs' => htmlspecialchars($_POST['rs']),
				);
				if ($_POST['contact_email'] != '') {
					$userCurrent = USER::add($newUserData);
					if(isset($_POST['adress'])){ // + проверить чтобы был только номер без букв
						$data['phone'] = htmlspecialchars($_POST['phone']);
						User::update($userCurrent, $data);	
					}

				}
			}

			if(!empty($_POST['contact_email']) && empty($_POST['user_email'])){
				$_POST['user_email'] = $_POST['contact_email'];
			}

			$orderArray = $model->addOrder($_POST);
			$id = $orderArray['id'];
			$orderNumber = $orderArray['orderNumber'];
			$this->messageSucces = $this->lang['ACT_SAVE_ORD'].' № '.$orderNumber;
			$_POST['id'] = $id;
			$_POST['newId'] = $id;
			$_POST['number'] = $orderNumber;
			$_POST['date'] = MG::dateConvert(date('d.m.Y H:i'));
		}


		if (isset($customFields) && !empty($customFields)) {
			$optionalFields = Models_OpFieldsOrder::getFields();
			foreach ($optionalFields as $item) {
				if ($item['type'] == 'file' && !empty($customFields[$item['id']])) {
					if (is_file(SITE_DIR.DS.$customFields[$item['id']])) {
						@mkdir(SITE_DIR.'uploads'.DS.'order');
						@mkdir(SITE_DIR.'uploads'.DS.'order'.DS.$id);
						$ordeFileName = explode('/', $customFields[$item['id']]);
						$ordeFileName = end($ordeFileName);
						copy(SITE_DIR.$customFields[$item['id']], SITE_DIR.'uploads'.DS.'order'.DS.$id.DS.$ordeFileName);
						$customFields[$item['id']] = $ordeFileName;
					}
				}
			}
		}
		$opFieldsM = new Models_OpFieldsOrder($_POST['id']);
		$opFieldsM->fill($customFields);
		$opFieldsM->save();

		if(!empty($adminComment)) {
			$adminComment = htmlspecialchars($adminComment);
			$user = USER::getThis();
			$user_id = $user->id;
			$model->addAdminCommentOrder($_POST['id'], $adminComment, $user_id);
		}

		$_POST['count'] = $model->getNewOrdersCount();
		$_POST['date'] = MG::dateConvert(date('d.m.Y H:i'));
		$this->data = $_POST;

		MG::unlockEntity('order', $_POST['id']);

		return true;
	}
        
        /**
	 * Сохраняет и обновляет параметры категории.
	 * @return bool
	 */
	public function saveCategory() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		if(MG::checkLockEntity('category', $_POST['id']) !== true) {
			return false;
		}

		if (isset($_POST['delImgs']) && is_array($_POST['delImgs'])) {
			foreach ($_POST['delImgs'] as $value) {
				$value = str_replace(SITE.'/', SITE_DIR, $value);
				if ($_POST['id'] && strpos($value, '/uploads/category/'.$_POST['id'].'/')) {
					@unlink($value);
					$webImagePath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', str_replace('/uploads/', '/uploads/webp/', $value));
					@unlink($webImagePath);
				}
			}
		}

		unset($_POST['delImgs']);
		$this->messageSucces = $this->lang['ACT_SAVE_CAT'];
		$this->messageError = $this->lang['ACT_NOT_SAVE_CAT'];
		$_POST['image_url'] = $_POST['image_url'] ? str_replace(SITE, '', $_POST['image_url']) : '';
		$_POST['menu_icon'] = $_POST['menu_icon'] ? str_replace(SITE, '', $_POST['menu_icon']) : '';
		$_POST['parent_url'] = MG::get('category')->getParentUrl($_POST['parent']);
		if (isset($_POST['opFields'])) {
			$opFields = $_POST['opFields'];
			unset($_POST['opFields']);
		}

		// Проверяем разрешение загружаемых изображений
		$imagesForSizeCheck = [];
		if (!empty($_POST['image_url'])) {
			$imagesForSizeCheck[] = $_POST['image_url'];
		}
		if (!empty($_POST['menu_icon'])) {
			$imagesForSizeCheck[] = $_POST['menu_icon'];
		}

		if ($imagesForSizeCheck) {
			$maxWidth = 1500;
			$maxHeight = 1500;
			$maxWidthSetting = MG::getSetting('maxUploadImgWidth');
			$maxHeightSetting = MG::getSetting('maxUploadImgHeight');

			if ($maxWidthSetting) {
				$maxWidth = $maxWidthSetting;
			}
			if ($maxHeightSetting) {
				$maxHeight = $maxHeightSetting;
			}

			foreach ($imagesForSizeCheck as $imageForSizeCheck) {
				$imageForSizeCheckFile = SITE_DIR.str_replace('/', DS, urldecode(trim($imageForSizeCheck, '/')));
				if (is_file($imageForSizeCheckFile)) {
					$imageForSizeCheck = $imageForSizeCheckFile;
				}
				$imageInfo = getimagesize($imageForSizeCheck);
				if (
					!empty($imageInfo[0]) &&
					!empty($imageInfo[1]) &&
					(
						$imageInfo[0] > $maxWidth ||
						$imageInfo[1] > $maxHeight
					)
				) {
					$this->messageError = $this->lang['ACT_NOT_SAVE_CAT_IMAGE'].' '.$maxWidth.'x'.$maxHeight.'px';
					return false;
				}
			}
		}

		// Обновление.
		if (!empty($_POST['id'])) {
			if (MG::get('category')->updateCategory($_POST)) {
				$this->data = $_POST;
			} else {
				return false;
			}
		} else {  // добавление
			unset($_POST['lang']);
			$tmp = MG::get('category')->addCategory($_POST);
			$_POST['id'] = $tmp['id'];
			$this->data = $tmp;
		}
		if (!empty($opFields)) {
			Models_OpFieldsCategory::saveContent($_POST['id'], $opFields);
		}
		MG::unlockEntity('category', $_POST['id']);

		return true;
	}

	/**
	 * Сохраняет и обновляет параметры страницы.
	 * @return bool
	 */
	public function savePage() {
		$this->messageSucces = $this->lang['ACT_SAVE_PAGE'];
		$this->messageError = $this->lang['ACT_NOT_SAVE_PAGE'];
		if(USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		if(MG::checkLockEntity('page', $_POST['id']) !== true) {
			return false;
		}

		$_POST['parent_url'] = MG::get('pages')->getParentUrl($_POST['parent']);
		
		$posParent = stripos($_POST['parent_url'], '?');
		if($posParent !== false) {
			$_POST['parent_url'] = substr($_POST['parent_url'], 0, $posParent).'/';
		}
		// Обновление.
		if (!empty($_POST['id'])) {
			if (MG::get('pages')->updatePage($_POST)) {
				$this->data = $_POST;
			} else {
				if(!empty(MG::get('pages')->messageError)){
					$this->messageError = MG::get('pages')->messageError;
				}
				return false;
			}
		} else {  // добавление
			unset($_POST['lang']);
			$_POST['title'] = htmlspecialchars($_POST['title']);
			$this->data = MG::get('pages')->addPage($_POST);
			$this->messageError = $this->lang['ACT_PAGE_ADD_ERROR'];
		}

		if(empty($this->data)){
			return false;
		}
		MG::unlockEntity('page', $_POST['id']);

		return true;
	}

	/**
	 * Делает страницу невидимой в меню.
	 * @return bool
	 */
	public function invisiblePage() {
		if(USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageError = $this->lang['ACT_NOT_SAVE_PAGE'];
		if ($_POST['invisible'] === "1") {
			$this->messageSucces = $this->lang['ACT_UNV_PAGE'];
		} else {
			$this->messageSucces = $this->lang['ACT_V_PAGE'];
		}
		// Обновление.
		if (!empty($_POST['id']) && isset($_POST['invisible'])) {
			MG::get('pages')->updatePage($_POST);
		} else {
			return false;
		}
		return true;
	}

	/**
	 * Делает категорию невидимой в меню.
	 * @return bool
	 */
	public function invisibleCat() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageError = $this->lang['ACT_NOT_SAVE_CAT'];
		if ($_POST['invisible'] === "1") {
			$this->messageSucces = $this->lang['ACT_UNV_CAT'];
		} else {
			$this->messageSucces = $this->lang['ACT_V_CAT'];
		}
		$array = $_POST;
		// Обновление.
		if (!empty($_POST['id']) && isset($_POST['invisible'])) {
			MG::get('category')->updateCategory($_POST);
			$arrayChildCat = MG::get('category')->getCategoryList($array['id']);
			if (!empty($arrayChildCat) && is_array($arrayChildCat)) {
                foreach ($arrayChildCat as $ch_id) {
                    $array['id'] = $ch_id;
                    MG::get('category')->updateCategory($array);
                }
            }
		} else {
			return false;
		}
		return true;
	}
	 /**
	 * Делает категорию активной/неактивной и товары в ней.
	 * @return bool
	 */
	public function activityCat() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageError = $this->lang['ACT_NOT_SAVE_CAT'];
		if ($_POST['activity'] === "1") {
			$this->messageSucces = $this->lang['ACT_V_CAT_ACT'];
		} else {
			$messageSuccess = $this->lang['ACT_UNV_CAT_ACT'];
			$messageSuccess .= ' '.$this->lang['ACT_UNV_CAT_ACT2'];
			$this->messageSucces = $messageSuccess;
		}
		// Обновление.
		if (!empty($_POST['id']) && isset($_POST['activity'])) {
			MG::get('category')->updateCategory($_POST);
			$arrayChildCat = MG::get('category')->getCategoryList($_POST['id']);
			if (!empty($arrayChildCat) && is_array($arrayChildCat)) {
                foreach ($arrayChildCat as $ch_id) {
                    $_POST['id'] = $ch_id;
                    MG::get('category')->updateCategory($_POST);
                }
            }
			//DB::query('UPDATE `'.PREFIX.'product` SET `activity`='.DB::quote($_POST['activity']).' WHERE `cat_id`='.DB::quoteInt($_POST['id']));
		} else {
			return false;
		}
		return true;
	}


	/**
	 * Делает все страницы видимыми в меню.
	 * @return bool
	 */
	public function refreshVisiblePage() {
		if(USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::get('pages')->refreshVisiblePage();
		$this->messageSucces = $this->lang['ACT_PINT_IN_MENU'];
		return true;
	}

	/**
	 * Сохраняет и обновляет параметры пользователя.
	 * @return bool
	 */
	public function saveUser() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SAVE_USER'];
		$this->messageError = $this->lang['ACT_NOT_SAVE_USER'];

		if(MG::checkLockEntity('user', $_POST['id']) !== true) {
			return false;
		}

		$userOp = isset($_POST['op'])?$_POST['op']:array();
		unset($_POST['op']);
		
		// Обновление.
		if (!empty($_POST['id'])) {

			$res = DB::query('SELECT `id` FROM `'.PREFIX.'user` WHERE `login_email` = '.db::quote($_POST['login_email']));
			if ($_POST['login_email'] != '' && ($row = DB::fetchAssoc($res))) {
				if ((int)$_POST['id'] !== (int)$row['id']) {
					$this->messageError = $this->lang['USER_DUPLICATE_EMAIL'];
					return false;
				}
			}
			if($row = USER::getUserInfoByPhone($_POST['login_phone'],'login_phone')){
				if ((int)$_POST['id'] !== (int)$row->id) {
					$this->messageError = $this->lang['USER_DUPLICATE_PHONE'];
					return false;
				}
			}
			// если пароль не передан значит не обновляем его
			if (empty($_POST['pass'])) {
				unset($_POST['pass']);
			} else {
				$_POST['pass'] = password_hash($_POST['pass'], PASSWORD_DEFAULT);
			}

			//вычисляем надо ли перезаписать данные текущего пользователя после обновления
			//(только в том случае если из админки меняется запись текущего пользователя)
			$authRewrite = $_POST['id'] != User::getThis()->id ? true : false;

			// если происходит попытка создания нового администратора от лица модератора, то вывести ошибку
			if ($_POST['role'] == '1') {
				if (!USER::AccessOnly('1')) {
					return false;
				}
			}
			if ($_POST['birthday']) {
				$_POST['birthday'] = date('Y-m-d', strtotime($_POST['birthday']));
			}
			if (User::update($_POST['id'], $_POST, $authRewrite)) {
				$this->data = $_POST;
			} else {
				return false;
			}
		} else {  // добавление
			if ($_POST['role'] == '1') {
				if (!USER::AccessOnly('1')) {
					return false;
				}
			}

			if (!empty($_POST['login_email']) && USER::getUserInfoByEmail($_POST['login_email'],'login_email')) {
				$this->messageError = $this->lang['USER_DUPLICATE_EMAIL'];
				return false;
			}
			if (!empty($_POST['login_phone']) && USER::getUserInfoByPhone($_POST['login_phone'],'login_phone')) {
				$this->messageError = $this->lang['USER_DUPLICATE_PHONE'];
				return false;
			}
			try {
				$_POST['id'] = User::add($_POST);
			} catch (Exception $exc) {
				$this->messageError = $this->lang['ACT_ERR_SAVE_USER'];
				return false;
			}

			$siteName = MG::getSetting('sitename');
			$userEmail = $_POST['email'];
			$messageData = array(
				"siteName" => $siteName,
				"userEmail" => $userEmail,
			);
			$message = MG::layoutManager('email_registry_independent',$messageData);
			$emailData = array(
				'nameFrom' => $siteName,
				'emailFrom' => MG::getSetting('noReplyEmail'),
				'nameTo' => 'Пользователю сайта '.$siteName,
				'emailTo' => $userEmail,
				'subject' => 'Активация пользователя на сайте '.$siteName,
				'body' => $message,
				'html' => true
			);
			Mailer::sendMimeMail($emailData);

			$_POST['date_add'] = date('d.m.Y H:i');
			$this->data = $_POST;
		}

		$opFieldsM = new Models_OpFieldsUser($_POST['id']);
		$opFieldsM->fill($userOp);
		$opFieldsM->save();

		MG::unlockEntity('user', $_POST['id']);

		return true;
	}

	/**
	 * Изменяет настройки.
	 * @return bool
	 */
	public function editSettings() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SAVE_SETNG'];
		Storage::clear();
		MG::resetAdminCurrency();
		$findOp = false;
		if (!empty($_POST['options'])) {
			$tmpOp = array();
			$tmpOpPrice = array();
			$tmpOpPriceWholesales = array();
			foreach ($_POST['options'] as $key => $item) {
				if(substr_count($key, 'op1c_') > 0) {
					$keyNotPrefix = str_replace('op1c_', '', $key);
					//Доп поля для 1С
					$tmpOp[$keyNotPrefix] = $item;
					$tmpOpPrice[$keyNotPrefix] = $_POST['options']['op1cPrice_'.$keyNotPrefix];
					if ($tmpOpPrice[$keyNotPrefix] == 'fromPrice'
						&& isset($_POST['options']['op1cGroup_'.$keyNotPrefix])
						&& $_POST['options']['op1cGroup_'.$keyNotPrefix] != 'none') {
						$data = array();
						$data['count'] = intval($_POST['options']['op1cCount_'.$keyNotPrefix]);
						$data['group'] = $_POST['options']['op1cGroup_'.$keyNotPrefix];
						$tmpOpPriceWholesales[$keyNotPrefix] = $data;
					}
					unset($_POST['options']['op1cPrice_'.$keyNotPrefix]);
					unset($_POST['options']['op1cCount_'.$keyNotPrefix]);
					unset($_POST['options']['op1cGroup_'.$keyNotPrefix]);
					unset($_POST['options'][$key]);
					$findOp = true;
				}
				if ($key === 'adminEmail') {
					//Если несколько эмейлов адмнистратора
					$tmp = explode(',', $item);
					foreach ($tmp as $ke => $va) {
						$tmp[$ke] = trim($va);
					}
					$tmp = array_filter($tmp);
					$_POST['options']['adminEmail'] = implode(',', $tmp);
				}
				if ($key == 'convertCountToHR') {
					//Убираем лишние символы из текстового обозначения количества товара
					$_POST['options']['convertCountToHR'] = htmlspecialchars($_POST['options']['convertCountToHR']);
				}

				if($key == 'sitetheme_select' && $item != 'Другое' && empty($_POST['options']['sitetheme_input'])){
					if($item == 'noSelect') $item = '';
					MG::setOption('sitetheme', $item);
				}

				if($key == 'sitetheme_input' && !empty($item)){
					if($item == 'noSelect') $item = '';
					MG::setOption('sitetheme', $item);
				}
			}

			if($findOp)  {
				MG::setOption('op1c', addslashes(serialize($tmpOp)));
				MG::setOption('op1cPrice', addslashes(serialize($tmpOpPrice)));
				MG::setOption('op1cPriceWholesales', addslashes(serialize($tmpOpPriceWholesales)));
			}

			//Подставляем статус
			if (isset($_POST['options']['status_0'])) {
				$listStatus = array();
				//Если есть плагин "Статусы заказов", то берем из них
				if (class_exists('statusOrder')) {
					$dbQuery = DB::query('SELECT `id_status`, `status` FROM `'.PREFIX.'mg-status-order`');
					while ($dbRes = DB::fetchArray($dbQuery)) {
						$listStatus[$dbRes['id_status']] = $_POST['options']['status_'.$dbRes['id_status']] != ""? $_POST['options']['status_'.$dbRes['id_status']] : $dbRes['status'];
					}
					//Иначе берем обычные
				} else {
					$lang = MG::get('lang');
					$ls = Models_Order::$status;
					foreach ($ls as $key => $value) {
					$listStatus[$key] = $_POST['options']['status_'.$key] != ""?  $_POST['options']['status_'.$key] : $lang[$value];
					}
				}

				MG::setOption(array('option' => 'listMatch1C', 'value' => addslashes(serialize($listStatus))));
			}

			if (isset($_POST['options']['1c_title'])) {

				$except1C = array(
					'1c_title' 			=> isset($_POST['options']['1c_title'])?$_POST['options']['1c_title']:'',
					'1c_code' 			=> isset($_POST['options']['1c_code'])?$_POST['options']['1c_code']:'',
					'1c_url' 			=> isset($_POST['options']['1c_url'])?$_POST['options']['1c_url']:'',
					'1c_weight' 		=> isset($_POST['options']['1c_weight'])?$_POST['options']['1c_weight']:'',
					'1c_count' 			=> isset($_POST['options']['1c_count'])?$_POST['options']['1c_count']:'',
					'1c_description' 	=> isset($_POST['options']['1c_description'])?$_POST['options']['1c_description']:'',
					'1c_image_url' 		=> isset($_POST['options']['1c_image_url'])?$_POST['options']['1c_image_url']:'',
					'1c_meta_title' 	=> isset($_POST['options']['1c_meta_title'])?$_POST['options']['1c_meta_title']:'',
					'1c_meta_keywords' 	=> isset($_POST['options']['1c_meta_keywords'])?$_POST['options']['1c_meta_keywords']:'',
					'1c_meta_desc' 		=> isset($_POST['options']['1c_meta_desc'])?$_POST['options']['1c_meta_desc']:'',
					'1c_activity' 		=> isset($_POST['options']['1c_activity'])?$_POST['options']['1c_activity']:'',
					'1c_old_price' => isset($_POST['options']['1c_old_price'])?$_POST['options']['1c_old_price']:'',
					'1c_cat_id' => isset($_POST['options']['1c_cat_id'])?$_POST['options']['1c_cat_id']:'',
                    '1c_multiplicity'	=> isset($_POST['options']['1c_multiplicity'])?$_POST['options']['1c_multiplicity']:''
                 );

				MG::setOption(array('option' => 'notUpdate1C', 'value' => addslashes(serialize($except1C))));
			}

			if (isset($_POST['options']['cat1c_title'])) {
				$exceptCat1C = array(
					'cat1c_title' => isset($_POST['options']['cat1c_title'])?$_POST['options']['cat1c_title']:'',
					'cat1c_url' => isset($_POST['options']['cat1c_url'])?$_POST['options']['cat1c_url']:'',
					'cat1c_parent' => isset($_POST['options']['cat1c_parent'])?$_POST['options']['cat1c_parent']:'',
					'cat1c_html_content' => isset($_POST['options']['cat1c_html_content'])?$_POST['options']['cat1c_html_content']:'',
				);
				
				MG::setOption(array('option' => 'notUpdateCat1C', 'value' => addslashes(serialize($exceptCat1C))));
			}


			////
			//// Переключение способов подтверждения регистрации
			////
			//
			//	Если подтверждение выключено, то выключаются все способы.
			//	Если подтверждение включено, но способы не включены, то подтверждение отключается
			//	Если подтверждение включено и включен один из способов, то ничего не меняется
			//	Если подтверждение включено и включены оба способа, то способ по телефону отключается
			//
			// $confirmRegistration = MG::getSetting('confirmRegistration') == 'true';
			// $confirmRegistrationPhone = MG::getSetting('confirmRegistrationPhone') == 'true';
			// $confirmRegistrationEmail = MG::getSetting('confirmRegistrationEmail') == 'true';
			$confirmRegistration = $_POST['options']['confirmRegistration'] == 'true';
			$confirmRegistrationPhone = $_POST['options']['confirmRegistrationPhone'] == 'true';
			$confirmRegistrationEmail = $_POST['options']['confirmRegistrationEmail'] == 'true';

			if ($confirmRegistration) {
				if ($confirmRegistrationEmail) {
					if ($confirmRegistrationPhone) {
						$_POST['options']['confirmRegistrationPhone'] = 'false';
					}
				}
			} else {
				if ($confirmRegistrationEmail) {
					$_POST['options']['confirmRegistrationEmail'] = 'false';
				}
				if ($confirmRegistrationPhone) {
					$_POST['options']['confirmRegistrationPhone'] = 'false';
				}
			}
			////
			////
			////

			//Добавляем цвета если стоит галочка "Создавать размеры и цвета из значений характеристик номенклатуры"
			if (!empty($_POST['options']['variantToSize1c']) && $_POST['options']['variantToSize1c'] == "true") {
				$path = SITE_DIR.'mg-core'.DS.'script'.DS.'zip'.DS.'color_set.zip';
				//Если есть файл с цветами, то создаем под нее таблицу
				if (file_exists($path)) {
					//Таблица соответствий цветов
					DB::query("CREATE TABLE IF NOT EXISTS ".PREFIX."color_list (
						`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Номер цвета',
						`hex` varchar(255) NOT NULL COMMENT 'Код цвета в HEX',
						`name` text NOT NULL COMMENT 'Соответсвующее цвету название',
						PRIMARY KEY (`id`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
				}
				//Загружаем цвета из csv, если таблица с цветами не пустая
				if (DB::fetchAssoc(DB::query("SELECT `id` FROM `".PREFIX."color_list` LIMIT 1")) == null) {
					$sql = "INSERT INTO `".PREFIX."color_list` (`hex`, `name`) VALUES ";
					$zip = zip_open($path);
					$zip_entry = zip_read($zip);
					$colors = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					$sql .= $colors;
					DB::query($sql);
					zip_entry_close($zip_entry);
					zip_close($zip);
				}
			}

			$optionsIntValue = array('categoryImgHeight','categoryImgWidth','heightPreview','widthPreview',
				'heightSmallPreview','widthSmallPreviews','countСatalogProduct','countNewProduct',
				'countRecomProduct','countSaleProduct');
			// если произошла смена валюты магазина, то пересчитываем курсы
			$currencyShopIso = MG::getSetting('currencyShopIso');
			if (!empty($_POST['options']['currencyShopIso']) && $_POST['options']['currencyShopIso'] != MG::getSetting('currencyShopIso')) {
				$currencyRate = MG::getSetting('currencyRate');
				$currencyShort = MG::getSetting('currencyShort');

				$_POST['options']['currency'] = $currencyShort[$_POST['options']['currencyShopIso']];

				$product = new Models_Product();
				$product->updatePriceCourse($_POST['options']['currencyShopIso']);

				//  $currencyRate[$currencyShopIso] = 1/$currencyRate[$_POST['options']['currencyShopIso']];
				$rate = $currencyRate[$_POST['options']['currencyShopIso']];
				$currencyRate[$_POST['options']['currencyShopIso']] = 1;
				foreach ($currencyRate as $iso => $value) {
					if ($iso != $_POST['options']['currencyShopIso']) {
						if (!empty($rate)) {
							$currencyRate[$iso] = $value / $rate;
						}
					}
				}
				unset($currencyRate['']);
				DB::query("UPDATE `".PREFIX."delivery` SET cost = ROUND(cost * ".DB::quoteFloat($currencyRate[$currencyShopIso]).", 3) , free = ROUND(free * ".DB::quoteFloat($currencyRate[$currencyShopIso]).', 3)');


				MG::setOption(array('option' => 'currencyRate', 'value' => addslashes(serialize($currencyRate))));

				// конвертация старой цены при смене валюты магазина
				$curs = MG::getSetting('currencyRate');
				$rate = $curs[MG::getSetting('currencyShopIso')] / $curs[$_POST['options']['currencyShopIso']];
				DB::query('UPDATE '.PREFIX.'product SET old_price = ROUND(old_price * '.DB::quote((float)$rate, true).',2)');
				DB::query('UPDATE '.PREFIX.'product_variant SET old_price = ROUND(old_price * '.DB::quote((float)$rate, true).',2)');
			}

			if (!empty($_POST['options']['useSearchEngineInfo']) && $_POST['options']['useSearchEngineInfo'] == 'true') {
				$shopname = $_POST['options']['shopName'];
				if (!$shopname) {
					$shopname = MG::getSetting('shopName');
				}
				file_put_contents(SITE_DIR.'mg-pages'.DS.'searchengineinfo.xml',
					'<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">'.
						'<ShortName>'.$shopname.'</ShortName>'.
						'<Description>Поиск по каталогу на сайте '.$shopname.'</Description>'.
						'<Image width="16" height="16" type="image/x-icon">'.
					        SITE.'/favicon.ico'.
						'</Image>'.
						'<Url type="text/html" template="'.SITE.'/catalog?search={searchTerms}"/>'.
						'<InputEncoding>UTF-8</InputEncoding>'.
					'</OpenSearchDescription>'
				);
			} elseif(!empty($_POST['options']['useSearchEngineInfo']) && $_POST['options']['useSearchEngineInfo'] == 'false') {
				@unlink(SITE_DIR.'mg-pages'.DS.'searchengineinfo.xml');
			}

			if (!empty($_POST['options']['ownerList'])) {
				$_POST['options']['ownerList'] = implode(',',$_POST['options']['ownerList']);
			}

			$errorMemcache = false;

			foreach ($_POST['options'] as $option => $value) {

				if($value == 'MEMCACHE') {
					$_POST['host'] = $_POST['options']['cacheHost'];
					$_POST['port'] = $_POST['options']['cachePort'];
					if (!self::testMemcacheConection()) {
						$value = 'DB';
						$errorMemcache = true;
					}
				}
				if ($option === 'favicon') {
					if (empty($value)) {
						continue;
					}
					$faviconFilePath = SITE_DIR.$value;
					if (is_file($faviconFilePath)) {
						$faviconFileParts = explode('.', $value);
						$ext = array_pop($faviconFileParts);
						$allowedExts = [
							'ico',
							'png',
							'gif',
							'jpg',
							'jpeg',
							'apng',
							'svg',
							'bmp',
						];
						if (in_array($ext, $allowedExts)) {
							if ($currentFaviconFile = MG::getSetting('favicon')) {
								unlink(SITE_DIR.$currentFaviconFile);
							}
							$newFaviconFile = 'favicon.'.$ext;
							$newFaviconFilePath = SITE_DIR.$newFaviconFile;
							unlink($newFaviconFilePath);
							rename($faviconFilePath, $newFaviconFilePath);
							$value = $newFaviconFile;
						}
					}
				}
				if($option == 'shopLogo' || $option == 'backgroundSite' || $option == 'openGraphLogoPath') {
					$value = str_replace(SITE, '', $value);
				}
				if ($option == 'robots' && !empty($value)) {
					$f = fopen('robots.txt', 'w');
					$result = fwrite($f, $value);
					fclose($f);
					unset($_POST['options']['robots']);
				}
				if ($option == 'smtpPass') {
					$value = CRYPT::mgCrypt($value);
				}
				if (in_array($option, $optionsIntValue)) {
					$value = intval($value);
				}
				
				//логирование изменеия настроек 
                $data['id'] = '';
                $data[$option]=$value;
                LoggerAction::logAction('Settings', __FUNCTION__, $data);
				
				if (!DB::query("UPDATE `".PREFIX."setting` SET `value`=".DB::quote($value)." WHERE `option`=".DB::quote($option)."")) {
					return false;
				}
				if ($option == 'licenceKey' && strlen($value) === 32 && 32 !== MG::getSetting('licenceKey')) {// авторегистрация триалок
					MG::setSetting('licenceKey', $value);
					MarketplaceMain::regTrialPlugs(false);
					MG::setOption('timeLastUpdata', '');
					Updata::checkUpdata(true);
				}
			}
			if ($errorMemcache) {
				return false;
			}
			$this->messageSucces = $this->lang['ACT_SAVE_SETNG'];
			return true;
		}
	}

	/**
	 * Сохранение настроек шаблона и получение данных для модалки с установкой плагинов и демоданными
	 * @return array
	 */
	public function saveTemplateSettings() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SAVE_SETNG'];
		Storage::clear();
		$result = array('showModal'=>0);

		if(MG::getSetting('isFirstStartHelperTemplate')==null){
			MG::setOption('isFirstStartHelperTemplate', '1');
		}

		if (
			!empty($_POST['settings']['templateName']) &&
			!empty($_POST['settings']['colorScheme'])
		) {
			if (!$userDefinedTemplateColors = MG::getSetting('userDefinedTemplateColors', 1)) {
				$userDefinedTemplateColors = array();
			}
			if (is_file(SITE_DIR.'mg-templates'.DS.$_POST['settings']['templateName'].DS.'config.ini')) {
					$templateColors = parse_ini_file(SITE_DIR.'mg-templates'.DS.$_POST['settings']['templateName'].DS.'config.ini',1);
        		$templateColors = !empty($templateColors['COLORS'])?$templateColors['COLORS']:[];
        	}
			if (
				$_POST['settings']['colorScheme'] == 'user_defined' &&
				!empty($_POST['settings']['userColors'])
			) {
				$writeColorFile = $_POST['settings']['userColors'];
			} elseif (
				!empty($templateColors) &&
				array_key_exists($_POST['settings']['colorScheme'], $templateColors)
			) {
				$writeColorFile = $templateColors[$_POST['settings']['colorScheme']];
			}

			if (!empty($writeColorFile)) {
				$userDefinedTemplateColors[$_POST['settings']['templateName']] = $writeColorFile;
				MG::setOption('userDefinedTemplateColors', $userDefinedTemplateColors, 1);

				$createColorsTemplateFileResult = MG::createTemplateColorsCssFile(
					$_POST['settings']['templateName'],
					$writeColorFile
				);

				if (!$createColorsTemplateFileResult) {
					$this->messageError = $this->lang['BACKUP_MSG_CHMOD'].'mg-templates'.DS.$_POST['settings']['templateName'].DS.'css'.DS.'colors.css';
					return false;
				}
			}
		}
		unset($_POST['settings']['userColors']);

		if (isset($_POST['settings']['landingName']) && !isset($_POST['settings']['colorSchemeLanding'])) {
			$_POST['settings']['colorSchemeLanding'] = 'none';
		}

		foreach ($_POST['settings'] as $setting => $value) {
			MG::setOption(array('option' => $setting, 'value' => $value));
		}

		if (!empty($_POST['settings']['templateName'])) {
			$newTemplate = $_POST['settings']['templateName'];
			$oldTemplate = MG::getSetting('templateName');
			if ($newTemplate != $oldTemplate) {
				Storage::clear();
				if (is_file(SITE_DIR.'mg-templates'.DS.$_POST['settings']['templateName'].DS.'config.ini')) {
					$templateSettings = parse_ini_file(SITE_DIR.'mg-templates'.DS.$_POST['settings']['templateName'].DS.'config.ini',1);
	        		$templateSettings = !empty($templateSettings['SETTINGS'])?$templateSettings['SETTINGS']:[];
	        	}
	        	if (!empty($templateSettings)) {
	        		$result['showModal'] = 1;
					$result['settings'] = 1;
	        	}
				
				$useTemplatePlugins = MG::getSetting('useTemplatePlugins');
				if ($useTemplatePlugins) {
					$clearPluginsTemplateSql = 'UPDATE `'.PREFIX.'plugins` '.
						'SET `template` = \'\';';
					DB::query($clearPluginsTemplateSql);
					$newTemplatePlugins = MG::getTemplatePlugins($newTemplate);
					if ($newTemplatePlugins) {
						if ($newTemplatePlugins) {
							$result['showModal'] = 1;
							$result['plugins'] = implode(',', $newTemplatePlugins);
						}
						$newTemplateDir = SITE_DIR.'mg-templates'.DS.$newTemplate;
						$newTemplatePluginsDir = $newTemplateDir.DS.'mg-plugins';
						$pluginsInNewTemplate = [];
						if (is_dir($newTemplatePluginsDir)) {
							$pluginsInNewTemplate = array_diff(scandir($newTemplatePluginsDir), ['.', '..']);
						}

						if ($pluginsInNewTemplate) {
							$setPluginsTemplateSql = 'UPDATE `'.PREFIX.'plugins` '.
								'SET `template` = '.DB::quote($newTemplate).' '.
								'WHERE `folderName` IN ('.DB::quoteIN($pluginsInNewTemplate).');';
							DB::query($setPluginsTemplateSql);
						}
					}
				}
				if (MarketplaceMain::templateHasQuickstart($newTemplate)) {
					$result['showModal'] = 1;
					$result['quickstart'] = 1;
				}
			} else {
				$templatePlugins = MG::getTemplatePlugins($newTemplate);
				if ($templatePlugins) {
					$result['plugins'] = implode(',', $templatePlugins);
				}
			}
		}
		$multiLang = MG::getSetting('multiLang', 1);
		if (!is_array($multiLang)) {$multiLang = array();}
		$currentTemplateLocalesPath = SITE_DIR.'mg-templates'.DS.$newTemplate.DS.'locales'.DS;
		$defaultTemplateLocalesPath = SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'locales'.DS;
		$coreLocalesPath = SITE_DIR.'mg-core'.DS.'locales'.DS;

		if (!empty($multiLang)) {
			if (!is_dir($currentTemplateLocalesPath)) {
				mkdir($currentTemplateLocalesPath);
			}
			$multiLang[] = array('short'=>'default');
			foreach ($multiLang as $lang) {
				foreach (['.php','.js'] as $ext) {
					if (!is_file($currentTemplateLocalesPath.$lang['short'].$ext)) {
						if (is_file($currentTemplateLocalesPath.'default'.$ext)) {
							copy($currentTemplateLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang['short'].$ext);
						} elseif (is_file($defaultTemplateLocalesPath.'default'.$ext)) {
							copy($defaultTemplateLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang['short'].$ext);
						} elseif (is_file($coreLocalesPath.'default'.$ext)) {
							copy($coreLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang['short'].$ext);
						} else {
							file_put_contents($currentTemplateLocalesPath.$lang['short'].$ext, '');
						}
					}
				}
			}
		}
		$this->data = $result;
		return true;
	}

	/**
	 * Активация (аренда для saas-сайтов) всех плагинов из таблицы mg_plugins
	 * после разворота квикстарта
	 * @return bool
	 */
	public function activateTemplatePlugins() {

		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::setOption('useTemplatePlugins', '1');
		MG::setSetting('useTemplatePlugins', '1');

		$currentTemplate = MG::getSetting('templateName');
		$pluginsForRent = $blockedPlugins = MG::getTemplatePlugins($currentTemplate);
		$templateDir = SITE_DIR.'mg-templates'.DS.$currentTemplate.DS;

		MarketplaceMain::regTrialPlugs(true, $pluginsForRent);
		$templatePluginsDir = $templateDir.'mg-plugins';
		$templatePlugins = [];
		if (is_dir($templatePluginsDir)) {
			$templatePlugins = array_diff(scandir($templatePluginsDir), ['.', '..']);
		}

		$plugins = [];
		$availablePlugins = MarketplaceMain::getAvailablePlugins();
		foreach ($availablePlugins as $availablePlugin) {
			if (in_array($availablePlugin['mpFolder'], $pluginsForRent)) {
				if (($pluginKey = array_search($availablePlugin['mpFolder'], $blockedPlugins)) !== false) {
					unset($blockedPlugins[$pluginKey]);
				}
			}
		}
		if ($blockedPlugins) {
			$pluginsForRent = array_diff($pluginsForRent, $blockedPlugins);
		}

		$dbPlugins = [];
		$enabledPlugins = [];
		$dbPluginsSql = 'SELECT `folderName`, `active` FROM `'.PREFIX.'plugins`;';
		$dbPluginsResult = DB::query($dbPluginsSql);
		while ($dbPluginsRow = DB::fetchAssoc($dbPluginsResult)) {
			$dbPlugins[] = $dbPluginsRow['folderName'];
			if ($dbPluginsRow['active']) {
				$enabledPlugins[] = $dbPluginsRow['folderName'];
			}
		}

		$insertPlugins = array_diff($pluginsForRent, $dbPlugins);
		if ($insertPlugins) {
			$insertParts = [];
			foreach ($insertPlugins as $insertPlugin) {
				$insertParts[] = '('.DB::quote($insertPlugin).', 0, \'\')';
			}
			$insertPluginsSql = 'INSERT INTO `'.PREFIX.'plugins` '.
				'(`folderName`, `active`, `template`) '.
				'VALUES '.implode(',', $insertParts).';';
			DB::query($insertPluginsSql);
		}

		$corePlugins = array_diff($pluginsForRent, $templatePlugins ? $templatePlugins : []);
		$corePluginsDir = SITE_DIR.'mg-plugins';
		$existedCorePlugins = array_diff(scandir($corePluginsDir), ['.', '..']);
		$missedCorePlugins = array_diff($corePlugins, $existedCorePlugins);
		if ($missedCorePlugins) {
			foreach ($availablePlugins as $availablePlugin) {
				if (in_array($availablePlugin['mpFolder'], $missedCorePlugins)) {
					MarketplaceMain::installPlugin($availablePlugin['mpCode'], 'yes');
				}
			}
		}

		if ($corePlugins) {
			$pluginsForRent = array_diff($pluginsForRent, $corePlugins);
			$setCorePluginsSql = 'UPDATE `'.PREFIX.'plugins` '.
				'SET `active` = 1, `template` = \'\' '.
				'WHERE `folderName` IN ('.DB::quoteIN($corePlugins).');';
			DB::query($setCorePluginsSql);
		}
		if ($pluginsForRent) {
			$enablePluginsForRentSql = 'UPDATE `'.PREFIX.'plugins` '.
				'SET `active` = 1, `template` = '.DB::quote($currentTemplate).' '.
				'WHERE `folderName` IN ('.DB::quoteIN($pluginsForRent).');';
			DB::query($enablePluginsForRentSql);
		}
		$corePluginsForActivationHook = array_diff($corePlugins, $enabledPlugins);
		$templatePluginsForActivationHook = $pluginsForRent;
		$pluginsForActivationHook = array_merge($corePluginsForActivationHook, $templatePluginsForActivationHook);
		foreach ($pluginsForActivationHook as $pluginForActivationHook) {
			MG::createActivationHook($pluginForActivationHook);
		}
		return true;
	}

	/**
	 * Включение массива плагинов
	 * @return void
	 */
	public function activatePlugins() {
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::setOption('useTemplatePlugins', '1');
		MG::setSetting('useTemplatePlugins', '1');
		if (empty($_POST['plugins'])) {return false;}
		MarketplaceMain::regTrialPlugs(true);
		$_POST['plugins'] = explode(',', $_POST['plugins']);


		$pluginsTemplate = isset($_POST['pluginsTemplate']) ? DB::quote($_POST['pluginsTemplate']) : "''";
		$insert = array();
		if ($_POST['plugins']) {
			foreach ($_POST['plugins'] as $pluginFolder) {
				MG::createActivationHook($pluginFolder);
				$insert[] = "(".DB::quote($pluginFolder).",1,".$pluginsTemplate.")";
			}
			DB::query("DELETE FROM `".PREFIX."plugins` WHERE `folderName` IN (".DB::quoteIN($_POST['plugins']).")");
			DB::query("INSERT INTO `".PREFIX."plugins` (`folderName`, `active`, `template`) VALUES ".implode(', ', $insert));
		}

		return true;
	}


	/**
	 * Скачивание архива демонстрационных данных для шаблона
	 * @return void
	 */
	public function quickstartDownload() {
		if (User::access('admin_zone') != 1 || USER::access('setting') < 2) {$this->messageError = $this->lang['ACCESS_EDIT'];return false;}
		Upload::starterSetOfUploads();
		$res = MarketplaceMain::downloadQuickstart($_POST['template']);
		if ($res === true) {
			return true;
		} else {
			$this->messageError = $res;
			return false;
		}
	}

	/**
	 * Очистка данных магазина
	 * @return void
	 */
	public function quickstartClear() {
		if (
			User::access('admin_zone') != 1 ||
			USER::access('product') < 2 ||
			USER::access('category') < 2 ||
			USER::access('page') < 2 ||
			USER::access('setting') < 2
		) {$this->messageError = $this->lang['ACCESS_EDIT'];return false;}

		$_POST['operation'] = 'fulldelete';
		$_POST['dropProducts'] = 'false';
		$this->operationProduct();
		$this->operationCategory();
		$this->operationPage();
		$this->operationProperty();
		// Очищаем корзину
		$cartModel = new Models_Cart;
		$cartModel->clearCart();
		SmalCart::setCartData();

		return true;
	}

	/**
	 * Копирование файлов демонстрационных данных
	 * @return void
	 */
	public function quickstartCopyFiles() {
		if (
			User::access('admin_zone') != 1 ||
			USER::access('product') < 2 ||
			USER::access('category') < 2 ||
			USER::access('page') < 2 ||
			USER::access('setting') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if ($_POST['type'] == 'product') {
			$dirFrom = SITE_DIR.'uploads'.DS.'quickstart'.DS.$_POST['template'].DS.'quickstart'.DS.'uploads'.DS.'product';
			$dirTo = SITE_DIR.'uploads'.DS.'product';
			MG::rMoveDir($dirFrom, $dirTo, true);
		}
		if ($_POST['type'] == 'misc') {
			$quickstartDir = SITE_DIR.'uploads'.DS.'quickstart'.DS.$_POST['template'].DS.'quickstart';
			if (is_dir($quickstartDir.DS.'mg-plugins')) {
				$dirFrom = $quickstartDir.DS.'mg-plugins';
				$dirTo = SITE_DIR.'mg-templates'.DS.$_POST['template'].DS.'mg-plugins';
				MG::rMoveDir($dirFrom, $dirTo, true);
			}
			$quickstartDir .= DS.'uploads';

			$shopLogoFound = false;
			$uploads = array_diff(scandir($quickstartDir), ['.','..']);
			foreach ($uploads as $upload) {
				$copyFrom = $quickstartDir.DS.$upload;
				$copyTo = SITE_DIR.'uploads'.DS.$upload;
				if (is_dir($quickstartDir.DS.$upload)) {
					if ($upload == 'product') {continue;}
					MG::rMoveDir($copyFrom, $copyTo, true);
				} else {
					if (substr($upload, 0, 9) == 'shoplogo.') {
						MG::setOption(array('option' => 'shopLogo', 'value' => '/uploads/'.$upload));
						$shopLogoFound = true;
					}
					if ($upload == 'favicon.ico') {
						copy($copyFrom, SITE_DIR.'favicon.ico');
						continue;
					}
					copy($copyFrom, $copyTo);
				}
			}
			if (!$shopLogoFound) {
				MG::setOption(array('option' => 'shopLogo', 'value' => ''));
			}
		}
		return true;
	}

	/**
	 * Регистрация триалок плагинов и переделка жс формы заказа
	 * @return void
	 */
	public function quickstartRegPlugins() {
		if(USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MarketplaceMain::regTrialPlugs(true);
		if (class_exists('Models_OpFieldsOrder')) {
			$fields = MG::getSetting('orderFormFields',true);
			if (is_array($fields) && !empty($fields)) {
				Models_OpFieldsOrder::rebuildJs($fields,true);
			}
		}
		MG::updateDBToUTF8MB4();
		Storage::clear();
		if (!empty($_POST['dropFolder']) && !empty($_POST['template'])) {
			MG::rrmdir(SITE_DIR.'uploads'.DS.'quickstart'.DS.$_POST['template'], true);
		}
		return true;
	}

	/**
	 * Метод возвращает соотношение столбцов в импортируемом файле (CSV) с настройками
	 * @return array
	 */
	public function getCsvCompliance($parseSeparator = ';') {
		if (
			USER::access('product') < 2 &&
			USER::access('category') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$parseSeparator = $_POST['parseSeparator'] === ',' ? ',' : ';';
		$importType = (empty($_POST['importType'])) ? 'MogutaCMS' : $_POST['importType'];
		$scheme = (empty($_POST['scheme'])) ? 'default' : $_POST['scheme'];
		$cmpData = array();

		if($scheme != 'default') {
			$cmpData = MG::getOption('csvImport-'.$scheme.$importType.'ColComp');
			$cmpData = unserialize(stripslashes($cmpData));
		}

		if(empty($cmpData)) {
			foreach(Import::$maskArray[$importType] as $id=>$title) {
				$cmpData[$id] = $id;
			}
		}

		$notUpdateList = MG::getOption('csvImport-'.$importType.'-notUpdateCol');
		$notUpdateColAr = explode(",", $notUpdateList);
		$notUpdateAr = array();

		foreach(Import::$fields[$importType] as $id=>$title) {
			$notUpdate = 0;

			if(in_array($id, $notUpdateColAr) && $scheme == 'last' && $importType == 'MogutaCMS') {
				$notUpdate = 1;
			}

			$notUpdateAr[$id] = $notUpdate;
		}

		// для превью
		$rowId = 0;
		$html = '';
		if($_SESSION['importType'] != 'excel') {
			$file = new SplFileObject("uploads/importCatalog.csv");
			$file->seek(0);
			$data = array();
			while(!$file->eof()) {
				if($rowId > 5) break;
				$rowId++;
				$data = $file->fgetcsv($parseSeparator);
				foreach($data as $k => $v) {
					$encoding = mb_detect_encoding($v, 'UTF-8', true);
					$encoding = $encoding !== false ? $encoding : 'WINDOWS-1251';
					if(!($encoding === 'UTF-8')){
						$data[$k] = str_replace(' ',' ',iconv($encoding, "UTF-8", $v));
					}
				}
				if($rowId == 1) {
					$html = '<thead>';
					foreach ($data as $item) {
						$encoding = mb_detect_encoding($item, 'UTF-8', true);
						$encoding = $encoding !== false ? $encoding : 'WINDOWS-1251';
						if(!($encoding === 'UTF-8')){
							$item = str_replace(' ',' ',iconv($encoding, "UTF-8", $item));
						}
						$html .= '<th style="white-space:nowrap;border-right:1px solid;padding-left:5px;background:#e6e6e6;" class="border-color">'.$item.'</th>';
					}
					$html .= '</thead>';
				} else {
					$html .= '<tr>';
					foreach ($data as $item) {
						$html .= '<td style="white-space:nowrap;border-right:1px solid;padding-left:5px;" class="border-color">'.htmlspecialchars($item).'</td>';
					}
					$html .= '</tr>';
				}
			}
		} else {
			include_once CORE_DIR.'script/excel/PHPExcel/IOFactory.php';
			include_once CORE_DIR.'script/excel/chunkReadFilter.php';

			$file = "uploads/importCatalog.xlsx";

            $chunkFilter = new chunkReadFilter();
			$chunkFilter->setRows(0, 7);
			$objReader = PHPExcel_IOFactory::createReaderForFile($file);
			$objReader->setReadFilter($chunkFilter);
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($file);
			$sheet = $objPHPExcel->getActiveSheet();
			$colNumber = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());

			$html = '<thead><tr>';
			for($c = 0; $c < $colNumber; $c++) {
				$html .= '<th style="white-space:nowrap;border-right:1px solid;padding-left:5px;background:#e6e6e6;" class="border-color">'.
					$sheet->getCellByColumnAndRow($c, 1)->getValue().'</th>';
			}
			$html .= '</tr></thead>';

			for($r = 2; $r <= 7; $r++) {
				$html .= '<tr>';
				for($c = 0; $c < $colNumber; $c++) {
					$html .= '<td style="white-space:nowrap;border-right:1px solid;padding-left:5px;" class="border-color">'.
						$sheet->getCellByColumnAndRow($c, $r)->getValue().'</td>';
				}
				$html .= '</tr>';
			}

			unset($objReader);
			unset($objPHPExcel);
		}

		if(!empty($html)) {
			$html = '<table class="main-table border-color" style="border: 1px solid;border-width:1px 0 1px 1px;">'.$html.'</table>';
		}

		$this->data['csvPreview'] = $html;
		$this->data['compliance'] = $cmpData;
		$this->data['notUpdate'] = $notUpdateAr;
		$this->data['maskArray'] = Import::$maskArray[$importType];
		$this->data['fieldsInfo'] = Import::$fieldsInfo[$importType];
		$this->data['requiredFields'] = Import::$requiredFields[$importType];
		$this->data['titleList'] = Import::getTitleList($parseSeparator);

		return true;
	}

	/**
	 * Устанавливает соответсвие столбцов при импорте из CSV
	 * @return bool true
	 */
	public function setCsvCompliance($parseSeparator = ';') {
		if (
			USER::access('product') < 2 &&
			USER::access('category') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$parseSeparator = $_POST['parseSeparator'] === ',' ? ',' : ';';
		$importType = (empty($_POST['importType'])) ? 'MogutaCMS' : $_POST['importType'];
		if(!empty($_POST['data'])) {
			$complianceArray = array();

			foreach($_POST['data']['compliance'] as $key=>$index) {
				$id = intval(substr($key, 8));
				$complianceArray[$id] = $index;
			}

			MG::setOption(array('option' => 'csvImport-last'.$importType.'ColComp', 'value' => addslashes(serialize($complianceArray))));

			if(!empty($_POST['data']['not_update'])) {
				$notUpdateList = '';

				foreach($_POST['data']['not_update'] as $key=>$index) {
					$id = intval(substr($key, 9));
					$notUpdateList .= $id.',';
				}

				$notUpdateList = substr($notUpdateList, 0, -1);

				MG::setOption(array('option' => 'csvImport-'.$importType.'-notUpdateCol', 'value' => $notUpdateList));
			}
		} else {
			$cpmArray = array();
			$colTitles = Import::$maskArray[$importType];
			$titleList = Import::getTitleList($parseSeparator);

			foreach($colTitles as $id=>$title) {
				$key = array_search($title, $titleList);
				if($key !== false) {
					$cpmArray[$id] = $key;
				}
			}

			MG::setOption(array('option' => 'csvImport-auto'.$importType.'ColComp', 'value' => addslashes(serialize($cpmArray))));
		}

		return true;
	}

	/**
	 * Получает параметры редактируемого продукта.
	 */
	public function getProductData() {
		if (USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		MG::resetAdminCurrency();

		$this->messageError = $this->lang['ACT_NOT_GET_POD'];

		$model = new Models_Product;
		// устанавливаем склад для загрузки количества

		if (isset($_POST['storage'])) {
			$model->storage = $_POST['storage'];
		}

		$product = $model->getProduct($_POST['id'], true, true);

		$maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');
		foreach ($product as $k => $v) {
			if(in_array($k, $maskField) || strpos($k, 'opf_') === 0) {
				$product[$k] = htmlspecialchars_decode($v);
			}
		}
		if (!$product['code']) {
			$product['code'] = MG::getSetting('prefixCode').$product['id'];
		}

		if (empty($product)) {
			return false;
		}
		$product['weight'] = $product['weightCalc'];
		$this->data = $product;

		foreach($this->data['images_product'] as $cell => $image) {
			$this->data['images_product'][$cell] = mgImageProductPath($image, $product['id']);
		}

		// Получаем весь набор пользовательских характеристик.
		$res = DB::query("SELECT * FROM `".PREFIX."property`");
		while ($userFields = DB::fetchAssoc($res)) {
			$this->data['allProperty'][] = $userFields;
		}

		$variants = $model->getVariants($_POST['id']);
		foreach ($variants as $variant) {
			$variant['image'] = mgImageProductPath($variant['image'], $product['id'], 'small');
			if (!$variant['code']) {
				$variant['code'] =  MG::getSetting('prefixCode').$product['id'].'_'.$variant['id'];
			}
			$variant['weight'] = $variant['weightCalc'];
			$this->data['variants'][] = $variant;
		}

		$stringRelated = ' null';
		$sortRelated = array();
		if (!empty($product['related'])) {
			foreach (explode(',', $product['related']) as $item) {
				$stringRelated .= ','.DB::quote($item);
				if (!empty($item)) {
					$sortRelated[$item] = $item;
				}
			}
			$stringRelated = substr($stringRelated, 1);
		}

		//$productsRelated = $model->getProductByUserFilter(' id IN ('.($product['related']?$product['related']:'0').')');
		$res = DB::query('
			SELECT  CONCAT(c.parent_url,c.url) as category_url,
				p.url as product_url, p.id, p.image_url,p.price_course as price,p.title,p.code,p.multiplicity
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
		//сортируем связанные товары в том порядке, в котором они идут в строке артикулов

		if (!empty($sortRelated)) {
			foreach ($sortRelated as $item) {
				if (is_array($item)) {
					$item['image_url'] = mgImageProductPath($item['image_url'], $item['id'], 'small');
					$productsRelated[] = $item;
				}
			}
		}
		$relatedCat = array();
		if ($product['related_cat']) {
			$res =  DB::query('SELECT `id`, `title`, `url`, `parent_url`, `image_url` FROM `'.PREFIX.'category` WHERE `id` IN ('.DB::quote($product['related_cat'], true).')');
			while ($row = DB::fetchArray($res)) {
				$relatedCat[] = $row;
			}
		}

		//загрузка лендингов
		$res =  DB::query("SELECT * FROM `".PREFIX."landings` WHERE `id` = ".DB::quoteInt($_POST['id']));
		if ($row = DB::fetchArray($res)) {
			$this->data['landingTemplate'] = $row['template'];
			$this->data['landingColor'] = $row['templateColor'];
			$this->data['landingImage'] = $row['image'];
			$this->data['landingSwitch'] = $row['buySwitch'];
			$langsL = array('ytp'=> $row['ytp']);
			MG::loadLocaleData($_POST['id'], LANG, 'landings', $langsL);
			$this->data['ytp'] = $langsL['ytp'];
		}

		$this->data['relatedCat'] = $relatedCat;
		$this->data['relatedArr'] = $productsRelated;
		$_POST['produtcId'] = $_POST['id'];
		$_POST['categoryId'] = $product['cat_id'];
		$tempDataResult = $this->data;
		$this->data = null;
		$this->getProdDataWithCat();
		$tempDataResult['prodData'] = $this->data;
		$tempDataResult['old_price'] = MG::convertCustomPrice($tempDataResult['old_price'], $tempDataResult['currency_iso'], 'get');
		if (isset($tempDataResult['variants']) && is_array($tempDataResult['variants'])) {
			foreach ($tempDataResult['variants'] as $key => $value) {
				$tempDataResult['variants'][$key]['old_price'] = MG::convertCustomPrice($value['old_price'], $value['currency_iso'], 'get');
			}
		}
		$this->data = $tempDataResult;
		//$this->data['prodData'] = $this->getProdDataWithCat();
		$this->data['weight'] = '' . $this->data['weight'];
		$this->data['weightCalc'] = '' . $this->data['weightCalc'];
		return true;
	}

	/**
	 * Получает параметры для категории продуктов.
	 */
	public function getProdDataWithCat() {
		$this->data['allProperty'] = array();
		$this->data['thisUserFields'] = array();
		$this->data['propertyGroup'] = array();
		//получаем старую категорию товара
		$res = DB::query("SELECT `cat_id` FROM `".PREFIX."product` WHERE id = ".DB::quoteInt($_POST['produtcId']));
        $row = DB::fetchAssoc($res);
        $oldCatId = intval($row['cat_id']);

		// Получаем заданные ранее пользовательские характеристики для редактируемого товара.
		$res = DB::query("
				SELECT pup.prop_id, pup.type_view, prop.*
				FROM `".PREFIX."product_user_property_data` as pup
				LEFT JOIN `".PREFIX."property` as prop ON pup.prop_id = prop.id
				WHERE pup.`product_id` = ".DB::quote($_POST['produtcId']));

		while ($userFields = DB::fetchAssoc($res)) {
			$this->data['thisUserFields'][] = $userFields;
		}

		// // Получаем набор пользовательских характеристик предназначенных для выбраной категории.
		$sql = "SELECT *
				FROM `".PREFIX."category_user_property` as сup
				LEFT JOIN `".PREFIX."property` as prop ON сup.property_id = prop.id
				WHERE сup.`category_id` = ".DB::quote($_POST['categoryId']);
		if ($oldCatId > 0) {
			$sql .= " OR (сup.`category_id` = ".DB::quote($oldCatId)." AND prop.`type` NOT IN('color', 'size'))";
		}
		$sql .= " ORDER BY sort DESC";
		$res = DB::query($sql);
		$alreadyProp = array();
		while ($userFields = DB::fetchAssoc($res)) {
			$this->data['allProperty'][] = $userFields;
			$alreadyProp[$userFields['property_id']] = true;
		}

		// получаем содержимое сложных настроек для пользовательских характеристик
		foreach ($this->data['allProperty'] as &$item) {
			$item['name'] = str_replace(array('prop attr=', '[', '  '), array('', ' [', ' '), $item['name']);
			$data = null;
			// не загружаем данные для размерной сетки (она работает подругому)
			if(($item['type'] != 'color')&&($item['type'] != 'size')) {
				$res = DB::query("SELECT pupd.*, pd.sort FROM ".PREFIX."product_user_property_data AS pupd
					LEFT JOIN ".PREFIX."property_data AS pd ON pd.id = pupd.prop_data_id
					WHERE pupd.`prop_id` = ".DB::quote($item['property_id'])." AND pupd.`product_id` = ".DB::quote($_POST['produtcId'])/*.' ORDER BY pd.name ASC'*/);
				while ($userFieldsData = DB::fetchAssoc($res)) {
					MG::loadLocaleData($userFieldsData['id'], LANG, 'product_user_property_data', $userFieldsData);
					$userFieldsData['name'] = htmlspecialchars_decode($userFieldsData['name']);
					$data[] = $userFieldsData;
				}
			}

			$ar = array();
			if (is_array($data) && !empty($data)) {
				foreach ($data as $value) {
					$ar[] = $value['prop_data_id'];
				}
			}
			$ar = implode(',', $ar);
			if(empty($ar)) $ar = '""';

			$res = DB::query("SELECT pd.* FROM ".PREFIX."property_data AS pd
				LEFT JOIN ".PREFIX."property AS p ON p.id = pd.prop_id
				WHERE pd.prop_id = ".DB::quote($item['property_id'])."
				AND pd.id NOT IN (".DB::quoteIN($ar).") AND p.type NOT IN ('string', 'textarea', 'diapason', 'file') GROUP BY pd.id ORDER BY pd.sort ASC");
			while ($userFieldsData = DB::fetchAssoc($res)) {
				MG::loadLocaleData($userFieldsData['id'], LANG, 'property_data', $userFieldsData);
				$userFieldsData['prop_data_id'] = $userFieldsData['id'];
				if(($item['type'] != 'color')&&($item['type'] != 'size')) {
					unset($userFieldsData['id']);
				}
				$data[] = $userFieldsData;
			}

			if($data == null) {
				$data = array(array(
					'prop_id' => $item['id'],
					'name' => '',
					'margin' => ''));
			}

			// подгрузка локализаций для сложных характеристик
			if(($item['type'] != 'string')&&($item['type'] != 'textarea')&&($item['type'] != 'diapason')&&($item['type'] != 'file')) {
				foreach ($data as &$val) {
					if (!isset($val['prop_data_id'])) {continue;}
					$res = DB::query("SELECT * FROM ".PREFIX."property_data WHERE `id` = ".DB::quote($val['prop_data_id']));
					while ($userFieldsData = DB::fetchAssoc($res)) {
						MG::loadLocaleData($userFieldsData['id'], LANG, 'property_data', $userFieldsData);
						if (!empty($item['unit'])) {
              $userFieldsData['name'] .= ' ' . $item['unit'];
            }
            $val['name'] = $userFieldsData['name'];
					}
				}
			}

			if($item['type'] != 'diapason') {
				$sort = array();
				foreach($data as $key => $arr){
					if (isset($arr['sort'])) {
						$sort[$key] = $arr['sort'];
					} else {
						if (!empty($sort)) {
							$sort[$key] = (min($sort)-2);
						} else {
							$sort[$key] = -2;
						}
					}
				}
				array_multisort($sort, SORT_NUMERIC, $data);
			} else {
				$sort = array();
				foreach($data as $key => $arr){
					$sort[$key] = $arr['name'];
				}
				// sort($sort)
				array_multisort($sort, SORT_NUMERIC, $data);
			}

			$item['data'] = null;
			foreach ($data as $elem) {
				$item['data'][] = $elem;
			}
		}
		// Получаем набор пользовательских характеристик.
		// Предназначенных для всех категорий и приплюсовываем его к уже имеющимя характеристикам выбраной категории.
		/* $res = DB::query("SELECT * FROM `".PREFIX."property` WHERE all_category = 1");
			while ($userFields = DB::fetchAssoc($res)) {
			if (empty($alreadyProp[$userFields['id']])) {
			$this->data['allProperty'][] = $userFields;
			$alreadyProp[$userFields['id']];
			}
			} */
		$tempUniqueProp = array();
		foreach ($this->data['allProperty'] as $key => $allProp) {
			if (empty($tempUniqueProp[trim($allProp['name'])])) {
				$tempUniqueProp[trim($allProp['name'])] = $allProp;
			} else {
				$this->data['allProperty'][$key]=array();
			}
		}
		// Достаем все группы характеристик и передаем только те, которые нужны (которые есть у данного товара)
		foreach(Property::getPropertyGroup(true) as $propGroup){
			$flag = false;
			foreach ($this->data['allProperty'] as $key => $allProp) {
				if(!isset($allProp['group_id'])){
					continue;
				}
				if($allProp['group_id'] == $propGroup['id'] && $allProp['type'] != 'color' && $allProp['type'] != 'size'){
					$flag = true;
					break;
				}
			}
			if($flag == true){
				$this->data['propertyGroup'][] = $propGroup;
			}
		}

		return true;
	}

	/**
	 * Получает пользовательские поля для добавления нового продукта.
	 */
	public function getUserProperty() {
		if (
			USER::access('setting') < 1 &&
			USER::access('category') < 1
		) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if (!empty($_POST['filter'])) {
			$filterAll = explode('&', $_POST['filter']);
			foreach ($filterAll as $param) {
				$filter = explode('=', $param);
				if (empty($_POST[$filter[0]])) {
					$_POST[str_replace('[]', '', $filter[0])] = $filter[1];
				}

			}
		}
		$lang = MG::get('lang');
		$listType = array(
			'null' => 'Не выбрано',
			'color' => $lang['COLOR'],
			'size' => $lang['SIZE'],
			'string' => $lang['STRING'],
			'select' => $lang['SELECT'],
			'assortment' => $lang['ASSORTMENT'],
			'assortmentCheckBox' => $lang['ASSORTMENTCHECKBOX'],
			'textarea' => $lang['TEXTAREA'],
		);
		$listGroup = [
			'null' => $lang['NO_SELECT'],
		];
		foreach (Property::getPropertyGroup(true) as $propertyGroup) {
			$listGroup[$propertyGroup['id']] = $propertyGroup['name'];
		}
		$property = array(
			'name' => array(
				'type' => 'text',
				'label' => $lang['STNG_USFLD_NAME'],
				'special' => 'like',
				'value' => !empty($_POST['name']) ? $_POST['name'] : null,
			),
			'type' => array(
				'type' => 'select',
				'option' => $listType,
				'selected' => (!empty($_POST['type'])) ? $_POST['type'] : 'null', // Выбранный пункт (сравнивается по значению)
				'label' => $lang['STNG_USFLD_TYPE']
			),
			'group_id' => array(
				'type' => 'select',
				'option' => $listGroup,
				'selected' => !empty($_POST['group_id']) ? $_POST['group_id'] : 'null',
				'label' => $lang['USERFIELD_SETTINGS_19'],
			),
		);
		if (isset($_POST['applyFilter'])) {
			$property['applyFilter'] = array(
				'type' => 'hidden', //текстовый инпут
				'label' => 'флаг примения фильтров',
				'value' => 1,
			);
		}
		$filter = new Filter($property);
		$arr = array(
			'type' => !empty($_POST['type']) ? $_POST['type'] : null,
			// 'name' => !empty($_POST['name']) ? $_POST['name'] : null,
			'group_id' => !empty($_POST['group_id']) ? $_POST['group_id'] : null,
		);

		$userFilter = $filter->getFilterSql($arr);
		if (empty($userFilter)) {
			$userFilter .= ' 1=1 ';
		}

		$page = !empty($_POST["page"]) ? $_POST["page"] : 0; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
		$countPrintRowsProperty = MG::getSetting('countPrintRowsProperty') ? MG::getSetting('countPrintRowsProperty') : 20;
		if (isset($_POST['cat_id']) && intval($_POST['cat_id'])) {
			$sql = "SELECT distinct prop.id, prop.*, cup.category_id FROM `".PREFIX."category_user_property` AS cup
				LEFT JOIN `".PREFIX."property` as prop ON cup.property_id = prop.id
				WHERE cup.category_id = ".DB::quote(intval($_POST['cat_id']))." AND name LIKE '%".$_POST['name']."%' AND ".$userFilter."
				ORDER BY sort DESC";
		} else {
			if (!isset($_POST['name'])) {
				$_POST['name'] = null;
			}
			$sql = "SELECT * FROM `".PREFIX."property`  WHERE name LIKE '%".$_POST['name']."%' AND ".$userFilter." ORDER BY sort DESC";
		}
		$navigator = new Navigator($sql, $page, $countPrintRowsProperty); //определяем класс
		$userFields = $navigator->getRowsSql();
		foreach ($userFields as $key => $item) {
			$tmp = explode('[prop attr=', $item['name']);
			if (isset($tmp[1])) {
				$userFields[$key]['mark'] = str_replace(']', '', $tmp[1]);
			}
			$userFields[$key]['name'] = $tmp[0];
		}
		$sortS = array();
		$minS = $maxS = $minId = $maxId = null;
		// для переноса на другие страницы
		foreach ($userFields as $key => $val) {
			$sortS[] = $val['sort'];
		}
		if (count($sortS) > 0) {
			$minS = min($sortS);
			$maxS = max($sortS);

			$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE sort < '.DB::quote($minS).' ORDER BY sort DESC LIMIT 1');
			if($row = DB::fetchAssoc($res)) {
				$minId = $row['id'];
			}
			$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE sort > '.DB::quote($maxS).' ORDER BY sort ASC LIMIT 1');
			if($row = DB::fetchAssoc($res)) {
				$maxId = $row['id'];
			}
		}

		$pagination = $navigator->getPager('forAjax');
		$pagination = str_replace("linkPage", "propLinkPage", $pagination);
		$this->data['pageSort']['max'] = $minId;
		$this->data['pageSort']['min'] = $maxId;
		$this->data['displayFilter'] = (isset($_POST['type']) && $_POST['type'] != "null" && !empty($_POST['type'])) || isset($_POST['applyFilter']); // так проверяем произошол ли запрос по фильтрам или нет
		$this->data['filter'] = $filter->getHtmlFilter();
		$this->data['allProperty'] = $userFields;
		$this->data['pagination'] = $pagination;
		return true;
	}

	/**
	 * Добавляет новую характеристику.
	 */
	public function addUserProperty() {
		if(USER::access('setting') < 2 && USER::access('product') < 2) {
			return false;
		}
		$this->messageSucces = $this->lang['ACT_ADD_POP'];
		$res = DB::query("
			 INSERT INTO `".PREFIX."property`
			 (`name`,`type`,`all_category`,`activity`,`description`,`type_filter`,`1c_id`,`plugin`,`unit`)
			 VALUES ('-',".DB::quote(!empty($_POST['type'])?$_POST['type']:'none').",'1','1','','checkbox','','', '')"
		);
		if ($id = DB::insertId()) {
			DB::query("
			 UPDATE `".PREFIX."property`
			 SET `sort`=`id` WHERE `id` = ".DB::quote($id)
			);

			DB::query("DELETE FROM `".PREFIX."product_user_property` WHERE `property_id` = ".DB::quoteInt($id));
			DB::query("DELETE FROM `".PREFIX."product_user_property_data` WHERE `prop_id` = ".DB::quoteInt($id));
			DB::query("DELETE FROM `".PREFIX."property_data` WHERE `prop_id` = ".DB::quoteInt($id));

			$this->data['allProperty'] = array(
				'id' => $id,
				'name' => '-',
				'type' => 'string',
				'activity' => '1',
				'description' => '',
				'unit' => '',
				'type_filter' => 'checkbox',
				'sort' => $id,
			);
		}
		return true;
	}

	/**
	 * Сохраняет пользовательские настройки для товаров.
	 */
	public function saveUserProperty() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$result = false;
		$this->messageSucces = $this->lang['ACT_EDIT_POP'];
		$id = $_POST['id'];
		if (isset($_POST['dataProp'])) {
			$_POST['dataProp'] = json_decode($_POST['dataProp'],1);
		}
		$array = $_POST;
		unset($array['category']);


		if (!empty($id)) {
			unset($array['id']);
			$res = DB::query('SELECT `plugin`, `type` FROM `'.PREFIX.'property` WHERE `id`='.DB::quote($_POST['id']));
			if ($row = DB::fetchArray($res)) {
				$pluginDirectory = PLUGIN_DIR.$row['plugin'].'/index.php';
				if ($row['plugin'] && file_exists($pluginDirectory)) {
					$this->messageSucces = $this->lang['ACT_EDIT_POP_PLUGIN'];
					$this->data['type'] = $row['type'];
					unset($array['type']);
					$result = true;
				}
			}

			$dataProp = array();
			if (isset($array['dataProp'])) {
				$dataProp = $array['dataProp'];
			}
			unset($array['dataProp']);

			// сохраняем локализацию самой  характеристики
			$lang = !empty($array['lang'])?$array['lang'] : 'LANG';
			unset($array['lang']);

			$filter = array('name', 'unit');
			$propertyLocale = MG::prepareLangData($array, $filter, $lang);
			MG::savelocaleData($id, $lang, 'property', $propertyLocale);

			$filterData = array('name');
			foreach ($dataProp as $item) {
				// сохраняем локализацию для доп полей
				$locale = MG::prepareLangData($item, $filterData, $lang);
				MG::savelocaleData($item['id'], $lang, 'property_data', $locale);

				if(empty($item['name'])) {
					$name = '';
				} else {
					$name = 'name = '.DB::quote($item['name']).', ';
				}
				if (!isset($item['color'])) {
					$item['color'] = '';
				}
				if (!isset($item['margin'])) {
					$item['margin'] = '';
				}
				DB::query('UPDATE `'.PREFIX.'property_data` SET '.$name.'
					margin = '.DB::quote($item['margin']).', color = '.DB::quote($item['color']).' WHERE `id`='.DB::quote($item['id']));
			}

			if(isset($array['mark']) && $array['mark'] != '') {
				$array['name'] .= '[prop attr='.$array['mark'].']';
			}
			unset($array['mark']);

			if(!empty($array['name'])) {
				$tmp = explode('[', $array['name']);
				if($tmp[0] != '') {
					DB::query('UPDATE '.PREFIX.'property SET name = '.DB::quote($array['name']).' WHERE id ='.DB::quoteInt($id));
				} else {
					unset($array['name']);
				}
			} else {
				unset($array['name']);
			}

			if(!empty($array['name']) && ($array['name'] != '-')) {
				$res = DB::query('SELECT COUNT(id) AS count FROM '.PREFIX.'property WHERE name = '.DB::quote($array['name']));
				$row = DB::fetchAssoc($res);
				if($row['count'] > 1) {
					$array['name'] .= ' [prop attr='.$id.']';
				}
			}

			// проверка возможности изменения типа характеристики
			$res = DB::query('SELECT type FROM '.PREFIX.'property WHERE id = '.DB::quoteInt($id));
			while($row = DB::fetchAssoc($res)) {
				if($row['type'] != 'none') {
					$array['type'] = $row['type'];
					$this->messageSucces = $this->lang['PROP_SAVE_TYPE_FAIL'];
				}
			}

			/////////////////////////////////////
			$category = array();
			if (!empty($_POST['category'])) {
				$category = explode("|", $_POST['category']);
			}

			// удалаляем все привязки характеристики к категориям сделанные ранее
			DB::query('
					DELETE FROM `'.PREFIX.'category_user_property`
					WHERE property_id = '.DB::quote($id));

			if (!empty($category)) {
				foreach ($category as $cat_id) {
					DB::query("INSERT IGNORE INTO `".PREFIX."category_user_property` VALUES ('%s', '%s')", $cat_id, $id);
				}
			}

			/////////////////////////////////////

			// обновление значений характеристики
			if (DB::query('
				UPDATE `'.PREFIX.'property`
				SET '.DB::buildPartQuery($array).'
				WHERE id ='.DB::quoteInt($id))) {
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * Привязка пользовательской настройки к категории.
	 */
	public function saveUserPropWithCat() {
		if (USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$id = $_POST['id'];
		$category = $_POST['category'];

		if ($category === '0') {
			return false;
		}

		DB::query('INSERT IGNORE INTO '.PREFIX.'category_user_property SET `category_id` = '.DB::quote($category).', property_id ='.DB::quote($id));

		return true;
	}

	/**
	 * Получает параметры редактируемого пользователя.
	 */
	public function getUserData() {
		if (USER::access('user') < 1) {
			$this->messageError= $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->messageError = $this->lang['ACT_GET_USER'];
		$response = USER::getUserById($_POST['id']);
		foreach ($response as $k => $v) {
			if($k!='pass') {
				$response->$k = htmlspecialchars_decode($v);
			}
		}

		$opFiledsM = new Models_OpFieldsUser($_POST['id']?$_POST['id']:'add');
		$html = $opFiledsM->createCustomFieldToAdmin();
		$response->htmlOp = $html;

		$this->data = $response;
		return false;
	}

	/**
	 * Получает параметры категории.
	 */
	public function getCategoryData() {
		if (USER::access('category') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->messageError = $this->lang['ACT_NOT_GET_CAT'];

		$result = DB::query("
			SELECT * FROM `".PREFIX."category`
			WHERE `id` =".DB::quote($_POST['id'])
		);
		if ($response = DB::fetchAssoc($result)) {
			MG::loadLocaleData($response['id'], LANG, 'category', $response);
			$maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');
			foreach ($response as $k => $v) {
				 if(in_array($k, $maskField)) {
					$response[$k] = htmlspecialchars_decode($v);
				 }
			}
			$response['opFields'] = Models_OpFieldsCategory::getAdminHtml($response['id']);
			$this->data = $response;
			return true;
		} else {
			return false;
		}

		return false;
	}

	/**
	 * Получает параметры редактируемой страницы.
	 */
	public function getPageData() {
		if (USER::access('page') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->messageError = $this->lang['ACT_SAVE_SETNG'];

		define(LANG, $_POST['lang']);

		$result = DB::query("
			SELECT * FROM `".PREFIX."page`
			WHERE `id` =".DB::quote($_POST['id'])
		);
		if ($response = DB::fetchAssoc($result)) {
			$maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');
			foreach ($response as $k => $v) {
				 if(in_array($k, $maskField)) {
					$response[$k] = htmlspecialchars_decode($v);
				 }
				 if ($k == 'url') {
                     $response[$k] = urldecode($v);
                 }
			}
			MG::loadLocaleData($_POST['id'], LANG, 'page', $response);
			$this->data = $response;
			return true;
		} else {
			return false;
		}

		return false;
	}

	/**
	 * Устанавливает порядок сортировки. Меняет местами две категории.
	 */
	public function changeSortCat() {
		if (USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$switchId = $_POST['switchId'];
		$sequence = explode(',', $_POST['sequence']);
		if (!empty($sequence)) {
			foreach ($sequence as $item) {
				MG::get('category')->changeSortCat($switchId, $item);
			}
		} else {
			$this->messageError = $this->lang['ACT_NOT_GET_CAT'];
			return false;
		}

		$this->messageSucces = $this->lang['ACT_SWITH_CAT'];
		return true;
	}

	/**
	 * Устанавливает порядок сортировки. Меняет местами две страницы.
	 */
	public function changeSortPage() {
		if (USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$switchId = $_POST['switchId'];
		$sequence = explode(',', $_POST['sequence']);
		if (!empty($sequence)) {
			foreach ($sequence as $item) {
				//MG::get('category')->changeSortCat($switchId, $item);
				MG::get('pages')->changeSortPage($switchId, $item);
			}
		} else {
			$this->messageError = $this->lang['ACT_NOT_GET_PAGE'];
			return false;
		}

		$this->messageSucces = $this->lang['ACT_SWITH_PAGE'];
		return true;
	}

	/**
	 * Устанавливает порядок сортировки. Меняет местами две записи.
	 */
	public function changeSortRow() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$switchId = $_POST['switchId'];
		$tablename = $_POST['tablename'];
		$sequence = explode(',', $_POST['sequence']);
		// if ($tablename =='product' && MG::getSetting('showSortFieldAdmin')=='true') {
		//   $this->messageError = 'Изменить порядок можно только в поле "Порядковый номер"';
		//   return false;
		// }
		if (!empty($sequence)) {
			foreach ($sequence as $item) {
				MG::changeRowsTable($tablename, $switchId, $item);
			}
		} else {
			return false;
		}

		$this->messageSucces = $this->lang['ACT_SWITH'];
		return true;
	}

	/**
	 * Возвращает ответ в формате JSON.
	 * @param bool $flag - если отработаный метод что-то вернул, то ответ считается успешным ждущей его фунции.
	 * @return bool
	 */
	private function jsonResponse($flag) {
		if ($flag === null) {
			return false;
		}
		if ($flag) {
			$this->jsonResponseSucces($this->messageSucces);
		} else {
			$this->jsonResponseError($this->messageError);
		}
	}

	/**
	 * Возвращает положительный ответ с сервера.
	 * @param string $message
	 */
	private function jsonResponseSucces($message) {
		$result = array(
			'data' => $this->data,
			'msg' => $message,
			'status' => 'success');
		echo json_encode($result);
	}

	/**
	 * Возвращает отрицательный ответ с сервера.
	 * @param string $message
	 */
	private function jsonResponseError($message) {
		$result = array(
			'data' => $this->data,
			'msg' => $message,
			'status' => 'error');
		echo json_encode($result);
	}


	/**
	 * Проверяет актуальность текущей версии системы.
	 * @return void возвращает в AJAX сообщение о результате операции.
	 */
	public function checkUpdata() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$msg = Updata::checkUpdata();

		if ($this->lang['ACT_THIS_LAST_VER'] == $msg['msg']) {
			$status = 'alert';
		} else {
			$status = 'success';
		}
		$response = array(
			'msg' => $msg['msg'],
			'status' => $status,
		);

		echo json_encode($response);
		exit;
	}

	/**
	 * Обновленяет верcию CMS.
	 *
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 * @return void возвращает в AJAX сообщение о результате операции.
	 */
	public function updata() {
		$version = $_POST['version'];

		if (Updata::updataSystem($version)) {
			$msg = $this->lang['ACT_UPDATE_VER'];
			$status = 'success';
		} else {
			$msg = $this->lang['ACT_ERR_UPDATE_VER'];
			$status = 'error';
		}

		$response = array(
			'msg' => $msg,
			'status' => $status,
		);

		echo json_encode($response);
	}

	/**
	 * Отключает публичную часть сайта. Обычно требуется для внесения изменений администратором.
	 * @return bool
	 */
	public function downTime() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$downtime = MG::getSetting('downtime');

		if ('Y' == $downtime) {
			$activ = 'N';
		} else {
			$activ = 'Y';
		}

		$res = DB::query('
			UPDATE `'.PREFIX.'setting`
			SET `value` = "'.$activ.'"
			WHERE `option` = "downtime"
		');

		if ($res) {
			return true;
		};
	}

	/**
	 * Функцию отправляет на сервер обновления информацию о системе и в случае одобрения скачивает архив с обновлением.
	 * @return void возвращает в AJAX сообщение загруженную в систему версию.
	 */
	public function preDownload() {
		if(USER::access('setting') < 2) { 
			$this->messageError = $this->lang['ACCESS_UPDATE_SYSTEM'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_UPLOAD_ZIP']." ".$_POST['version'];
		$this->messageError = $this->lang['ACT_NOT_UPLOAD_ZIP'];

		$result = Updata::preDownload($_POST['version']);

		if (!empty($result['status'])) {
			if ($result['status'] == 'error') {
				$this->messageError = $result['msg'];
				return false;
			}

			$backupBeforeUpdate = MG::getSetting('backupBeforeUpdate');
			if ($backupBeforeUpdate != 'true') {
				$backupBeforeUpdate = 'false';
			}
			$this->data = array('backupBeforeUpdate'=>$backupBeforeUpdate);
			return true;
		}
		return false;
	}

	/**
	 * Установливает загруженный ранее архив с обновлением.
	 * @return void возвращает в AJAX сообщение о результате операции.
	 */
	public function postDownload() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_UPDATE_SYSTEM'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_UPDATE_TRUE'].$_POST['version'];
		$this->messageError = $this->lang['ACT_NOT_UPDATE_TRUE'];

		$version = $_POST['version'];

		$error = Updata::checkZipChmod('update-m.zip');
		if ($error) {
			$this->messageError = $error;
			return false;
		}

		if (Updata::extractZip('update-m.zip')) {
			$this->messageSucces = $this->lang['ACT_UPDATE_VER'];
			// создание файла индетификации текущей версии кодировки
			file_put_contents(CORE_DIR.'lastPhpVersion.txt', PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION);

			$dir = SITE_DIR.'uploads'.DS.'product'.DS;
			$scan = [];
			if (is_dir($dir)) {
				$scan = array_diff(scandir($dir), array('..', '.'));
			}
			foreach ($scan as $value) {
				if(strpos($value, 'p_') === 0 && is_dir($dir.$value)) {
					$renameDir = rename($dir.$value, $dir.substr($value, 2));
					if (!$renameDir) {
						 MG::rMoveDir($dir.$value, $dir.substr($value, 2));
					}
				}
			}

			MG::setOption('timeLastUpdata', '');
			Updata::checkUpdata(true);

			return true;
		} else {
			$this->messageError = $this->lang['ACT_ERR_UPDATE_VER'];
			return false;
		}
		return false;
	}

	/**
	 * Устанавливает цветовую тему для меню в административном разделе.
	 * @return bool
	 */
	public function setTheme() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if ($_POST['color']) {
			MG::setOption(array('option' => 'themeColor', 'value' => $_POST['color']));
			MG::setOption(array('option' => 'themeBackground', 'value' => $_POST['background']));
		}
		return true;
	}

	/**
	 * Устанавливает количество отображаемых записей в разделе товаров.
	 * @return bool
	 */
	public function setCountPrintRowsProduct() {
		if (USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$count = 20;
		if (!empty($_POST['count'])) {
			$count = (int)$_POST['count'];
		}

		MG::setOption(array('option' => 'countPrintRowsProduct', 'value' => $count));
		return true;
	}

	/**
	 * Устанавливает количество отображаемых записей в разделе страницы.
	 * @return bool
	 */
	public function setCountPrintRowsPage() {
		if (USER::access('page') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$count = 20;
		if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
			$count = $_POST['count'];
		}


		MG::setOption(array('option' => 'countPrintRowsPage', 'value' => $count));
		return true;
	}

	/**
	 * Устанавливает количество отображаемых записей в разделе пользователей.
	 * @return bool
	 */
	public function setCountPrintRowsOrder() {
		if (USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$count = 20;
		if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
			$count = $_POST['count'];
		}

		MG::setOption(array('option' => 'countPrintRowsOrder', 'value' => $count));
		return true;
	}

	/**
	 * Устанавливает количество отображаемых записей в разделе заказов.
	 * @return bool
	 */
	public function setCountPrintRowsUser() {
		if (USER::access('user') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$count = 20;
		if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
			$count = $_POST['count'];
		}

		MG::setOption(array('option' => 'countPrintRowsUser', 'value' => $count));
		return true;
	}
	/**
	 * Устанавливает количество отображаемых записей в разделе характеристик.
	 * @return bool
	 */
	public function countPrintRowsProperty() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$count = 20;
		if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
			$count = $_POST['count'];
		}
		MG::setOption(array('option' => 'countPrintRowsProperty', 'value' => $count));
		return true;
	}

	/**
	 * Возвращает список найденых продуктов по ключевому слову.
	 * @return bool
	 */
	public function searchProduct() {
		if (USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->messageSucces = $this->lang['SEACRH_PRODUCT'];
		$model = new Models_Catalog;

		$_POST['mode']=$_POST['mode']?$_POST['mode']:false;
		$_POST['forcedPage']=$_POST['forcedPage']?$_POST['forcedPage']:false;
		$arr = $model->getListProductByKeyWord($_POST['keyword'], false, false, true, $_POST['mode'], $_POST['forcedPage']);

		if (empty($arr)) {
			$arr['catalogItems'] = array();
		}
		foreach ($arr['catalogItems'] as &$prod) {
			$prod['sortshow'] = 'true';
		}
		if (MG::getSetting('showCodeInCatalog')=='true') {
			foreach ($arr['catalogItems'] as &$prod) {
				$prod['codeshow'] = 'true';
			}
		}

		if (empty($_POST['returnHtml'])) {
			$this->data = $arr;
		} else {
			$this->getAdminCatalogRows($arr);
		}

		return true;
	}

	/**
	 * Устанавливает локаль для плагина, используется в JS плагинов.
	 * @return bool
	 */
	public function seLocalesToPlug() {
		$this->data = PM::plugLocales($_POST['pluginName']);
		return true;
	}

	/**
	 * Сохранение способа доставки.
	 */
	public function saveDeliveryMethod() {

		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		$weight = $msg = '';
		if (!empty($_POST['weight']) && is_array($_POST['weight'])) {
			$weightTmp = array();
			foreach ($_POST['weight'] as $value) {
				$tmp = str_replace(',', '.', str_replace(' ', '', $value['w']));
				$tmp2 = str_replace(',', '.', str_replace(' ', '', $value['p']));
				if ((float)$tmp == 0 && (float)$tmp2 == 0) {continue;}
				$weightTmp[] = array('w'=>(float)$tmp,'p'=>(float)$tmp2);
			}
			if (!empty($weightTmp)) {
				usort($weightTmp, function($a, $b) {
					if ($a["w"] == $b["w"]) {return 0;}
					return ($a["w"] < $b["w"]) ? -1 : 1;
				});
				$weight = json_encode($weightTmp);
			}
		}

		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];
		$status = $_POST['status'];
		$deliveryName = htmlspecialchars($_POST['deliveryName']);
        $deliveryDescriptionPublic = htmlspecialchars($_POST['deliveryDescriptionPublic']);
		$deliveryCost = (float)$_POST['deliveryCost'];
		$deliveryId = (int)$_POST['deliveryId'];
		$free = (float)MG::numberDeFormat($_POST['free']);
		$paymentMethod = $_POST['paymentMethod'];
		$paymentArray = json_decode($paymentMethod, true);
		$daysWeek = json_decode($_POST['daysWeek'], true);
		$monthWeek = json_decode($_POST['monthWeek'], true);
		$dateSettings = [
			'dateShift' => (int)$_POST['dateShift'],
			'daysWeek' => $daysWeek,
			'monthWeek' => $monthWeek
		];
		$deliveryDescription = htmlspecialchars($_POST['deliveryDescription']);
		$deliveryActivity = $_POST['deliveryActivity'];
		$deliveryDate = $_POST['deliveryDate'];
		$showStorages = $_POST['showStorages'];
		if (!empty($_POST['intervals'])) {
			$_POST['intervals'] = '["'.implode('","', array_filter($_POST['intervals'])).'"]';
		} else {
			$_POST['intervals'] = '';
		}
		$dateSettings = json_encode($dateSettings);
		switch ($status) {
			case 'createDelivery':
				$sql = "
					INSERT INTO `".PREFIX."delivery` (`name`, `description_public`, `cost`, `description`, `activity`,`free`, `date`".
					" ,`date_settings` ".
					" , `weight`, `interval`, `address_parts`".
                    ",`show_storages`".
					")VALUES (
						".DB::quote($deliveryName).", ".DB::quote($deliveryDescriptionPublic).",".DB::quote($deliveryCost).", ".DB::quote($deliveryDescription).", ".DB::quote($deliveryActivity).", ".DB::quote($free).", ".DB::quote($deliveryDate).
					", ".DB::quote($dateSettings).
					", ".DB::quote($weight).", ".DB::quote($_POST['intervals']).", ".DB::quote($_POST['useAddressParts']).
                    ", ".DB::quote($showStorages).
                    ");";

				$result = DB::query($sql);

				if ($deliveryId = DB::insertId()) {
					DB::query(" UPDATE `".PREFIX."delivery` SET `sort`=`id` WHERE `id` = ".DB::quote($deliveryId));
					$status = 'success';
					$msg = $this->lang['ACT_SUCCESS'];
				} else {
					$status = 'error';
					$msg = $this->lang['ACT_ERROR'];
				}

				foreach ($paymentArray as $paymentId => $compare) {
					$sql = "
						INSERT INTO `".PREFIX."delivery_payment_compare`
							(`compare`,`payment_id`, `delivery_id`)
						VALUES (
							".DB::quote($compare).", ".DB::quote($paymentId).", ".DB::quote($deliveryId)."
						);
					";
					$result = DB::query($sql);
				}

				break;
			case 'editDelivery':
				$fields = '';
				if($_POST['lang'] != 'default' && !empty($_POST['lang'])) {
					MG::savelocaleData($deliveryId, $_POST['lang'], 'delivery', array('name' => $deliveryName, 'description' => $deliveryDescription));
				} else {
					$fields = "`name` = ".DB::quote($deliveryName).",
										`description` = ".DB::quote($deliveryDescription).', ';
				}
				$sql = "
					UPDATE `".PREFIX."delivery`
					SET ".$fields."
                            `description_public` = ".DB::quote($deliveryDescriptionPublic).",
							`cost` = ".DB::quote($deliveryCost).",
							`activity` = ".DB::quote($deliveryActivity).",
							`free` = ".DB::quote($free).",
							`date` = ".DB::quote($deliveryDate).
							",`date_settings` = ".DB::quote($dateSettings).
							",`weight` = ".DB::quote($weight).",
							`interval` = ".DB::quote($_POST['intervals']).",
							`address_parts` = ".DB::quote($_POST['useAddressParts']).
							", `show_storages` = ".DB::quote($_POST['showStorages']).
					"WHERE id = ".DB::quote($deliveryId);
				$result = DB::query($sql);

				foreach ($paymentArray as $paymentId => $compare) {
					$result = DB::query("
						SELECT * 
						FROM `".PREFIX."delivery_payment_compare`         
						WHERE `payment_id` = ".DB::quote($paymentId)."
							AND `delivery_id` = ".DB::quote($deliveryId));
					if (!DB::numRows($result)) {
						$sql = "
								INSERT INTO `".PREFIX."delivery_payment_compare`
									(`compare`,`payment_id`, `delivery_id`)
								VALUES (
									".DB::quote($compare).", ".DB::quote($paymentId).", ".DB::quote($deliveryId)."
								);";
						$result = DB::query($sql);
					} else {
						$sql = "
							UPDATE `".PREFIX."delivery_payment_compare`
							SET `compare` = ".DB::quote($compare)."
							WHERE `payment_id` = ".DB::quote($paymentId)."
								AND `delivery_id` = ".DB::quote($deliveryId);
						$result = DB::query($sql);
					}
				}


				if ($result) {
					$status = 'success';
					$msg = $this->lang['ACT_SUCCESS'];
				} else {
					$status = 'error';
					$msg = $this->lang['ACT_ERROR'];
				}
		}

		$response = array(
			'data' => array(
				'id' => $deliveryId,
			),
			'status' => $status,
			'msg' => $msg,
		);
		echo json_encode($response);
	}

	/**
	 * Удаляет способ доставки.
	 * @return bool
	 */
	public function deleteDeliveryMethod() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];
		$res1 = DB::query('DELETE FROM `'.PREFIX.'delivery` WHERE `id`= '.DB::quote($_POST['id']));
		$res2 = DB::query('DELETE FROM `'.PREFIX.'delivery_payment_compare` WHERE `delivery_id`= '.DB::quote($_POST['id']));

		if ($res1 && $res2) {
			return true;
		}
		return false;
	}

	/**
	 * Сохраняет способ оплаты. (Новый алгоритм)
	 */
	public function savePaymentMethod() {
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		if (!MG::isNewPayment()) {
			return false;
		}

		$id = $_POST['id'];

		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		$paymentParamArray = Models_Payment::mergeParams($id, $_POST['paymentParam']);
		$data = [
			'name' => $_POST['name'],
			'rate' => $_POST['rate'],
			'icon' => $_POST['icon'],
			'logs' => $_POST['logs'],
			'activity' => $_POST['activity'],
			'paramArray' => $paymentParamArray,
			'permission' => $_POST['permission'],
			'public_name' => $_POST['publicName'],
			'deliveryMethod' => $_POST['deliveryMethod'],
		];
		if ($_POST['description']) {
			$data['description'] = $_POST['description'];
		}
		if ($id) {
			$pluginCode = null;
			if (!empty($_POST['pluginCode'])) {
				$pluginCode = $_POST['pluginCode'];
			}
			if(!empty($_POST['lang']) && $_POST['lang'] != 'default') {
				MG::saveLocaleData($id, $_POST['lang'], 'payment', [
					'name' => $data['name'],
					'public_name' => $data['public_name'],
				]);
				unset($data['name']);
				unset($data['public_name']);
			} else {
				$name = "`name` = ".DB::quote($_POST['name']).",";
			}
			$result = Models_Payment::updatePayment($id, $data, $pluginCode);
			if ($result === 'nonavailable') {
				$this->data = ['toMarketplace' => 1];
				$this->messageError = $this->lang['PAYMENT_PLUGIN_NOT_PURCHASED'];
				return false;
			}
		} else {
			$result = Models_Payment::createCustomPayment($data);
		}
		return $result;
	}

	/**
	 * Сохраняет способ оплаты. (Старый алгоритм)
	 */
	public function savePaymentMethodOld() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		$paymentParam = str_replace("'", "\'", $_POST['paymentParam']);

		$deliveryMethod = $_POST['deliveryMethod'];
		$deliveryArray = json_decode($deliveryMethod, true);
		$paymentActivity = $_POST['paymentActivity'];
		$paymentId = $_POST['paymentId'];

		if(empty($_POST['paymentId'])) {
			$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'payment');
			$tmpId = DB::fetchAssoc($res);
			$tmpId = $tmpId['MAX(id)'];
			if($tmpId < 1000) {
				$tmpId = 1000;
			} else {
				$tmpId++;
			}

			DB::query("INSERT INTO `".PREFIX."payment`
				SET `paramArray` = '',
						`activity` = ".DB::quote($paymentActivity).",
						`rate` = ".DB::quote($_POST['rate'], 1).",
						`permission` = ".DB::quote($_POST['permission']).',
						`id` = '.DB::quoteInt($tmpId).',
						`sort` = '.DB::quoteInt($tmpId));
			$paymentId = DB::insertId();
			$setCodeSql = 'UPDATE `'.PREFIX.'payment` '.
				'SET `code` = '.DB::quote('old#'.$paymentId).' '.
				'WHERE `id` = '.DB::quoteInt($paymentId);
			DB::query($setCodeSql);
		}

		if (is_array($deliveryArray)) {
			foreach ($deliveryArray as $deliveryId => $compare) {
				$sql = "
					DELETE FROM `".PREFIX."delivery_payment_compare`
					WHERE `payment_id` = ".DB::quote($paymentId)."
						AND `delivery_id` = ".DB::quote($deliveryId);
				$result = DB::query($sql);
				$sql = "
					INSERT INTO `".PREFIX."delivery_payment_compare`
					(payment_id, delivery_id, compare) VALUES 
					(".DB::quote($paymentId).", ".DB::quote($deliveryId).", ".DB::quote($compare).")";
				$result = DB::query($sql);
			}
		}
		$newparam = array();
		$param = json_decode($paymentParam);
		foreach ($param as $key=>$value) {
			if ($value != '') {
				$value = CRYPT::mgCrypt($value);
			}
			$newparam[$key] = $value;
		}
		$paymentParamEncoded = CRYPT::json_encode_cyr($newparam);

		if(!empty($_POST['lang']) && $_POST['lang'] != 'default') {
			MG::saveLocaleData($paymentId, $_POST['lang'], 'payment', array('name' => $_POST['name']));
			$name = '';
		} else {
			$name = "`name` = ".DB::quote($_POST['name']).",";
		}

		$sql = "
			UPDATE `".PREFIX."payment`
			SET ".$name."     
					`paramArray` = ".DB::quote($paymentParamEncoded).",
					`activity` = ".DB::quote($paymentActivity).",
					`rate` = ".DB::quoteFloat($_POST['rate']).",
					`permission` = ".DB::quote($_POST['permission'])."
			WHERE id = ".$paymentId;
		$result = DB::query($sql);

		if ($result) {
			$status = 'success';
			$msg = $this->lang['ACT_SUCCESS'];
		} else {
			$status = 'error';
			$msg = $this->lang['ACT_ERROR'];
		}

		$sql = "
			SELECT *
			FROM `".PREFIX."payment`     
			WHERE id = ".$paymentId;
		$result = DB::query($sql);
		if ($row = DB::fetchAssoc($result)) {
			$newparam = array();
			$param = json_decode($row['paramArray']);
			foreach ($param as $key=>$value) {
				if ($value != '') {
					$value = CRYPT::mgDecrypt($value);
				}
				$newparam[$key] = $value;
				}
			$paymentParam = CRYPT::json_encode_cyr($newparam);
		}

		$response = array(
			'status' => $status,
			'msg' => $msg,
			'data' => array('paymentParam' => $paymentParam)
		);
		echo json_encode($response);
	}

	/**
	 * Удаляет способ оплаты (не удаляет стандартные способы оплаты).
	 * @return bool true
	 */
	public function deletePayment() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if (intval($_POST['id']) < 1000) {
			return false;
		}
		DB::query('DELETE FROM '.PREFIX.'payment WHERE id = '.DB::quoteInt($_POST['id']));
		return true;
	}

	/**
	 * Удаляет способ оплаты, который работате в связке с плагином (в новой системе оплат)
	 */
	public function deletePluginPayment() {
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if (
			empty($_POST['code'])
		) {
			return false;
		}
		$code = $_POST['code'];
		$pluginCode = $_POST['pluginCode'];
		$result = Models_Payment::deletePluginPayment($code, $pluginCode);
		return $result;
	}

	/**
	 * Обновляет способов оплаты и доставки при переходе по вкладкам в админке.
	 */
	public function getMethodArray() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$mOrder = new Models_Order;
		$deliveryArray = Models_Order::getDeliveryMethod();
		$response['data']['deliveryArray'] = $deliveryArray;

		$paymentArray = array();
		/*
		$i = 1;
		while ($payment = $mOrder->getPaymentMethod($i)) {
			$paymentArray[$i] = $payment;
			$i++;
		}
		*/
		$res = DB::query("
			SELECT `payment_id`
			FROM `".PREFIX."delivery_payment_compare` GROUP BY `payment_id`
			");
		while($row = DB::fetchAssoc($res)){
			$paymentArray[$row['payment_id']] = $mOrder->getPaymentMethod($row['payment_id'], false);
		}
		$response['data']['paymentArray'] = $paymentArray;
		echo json_encode($response);
	}

	/**
	 * Верификация ApplePay
	 * @depricated
	 * TODO delte it?
	 */
	public function applePayVerify() {
		//MG::loger($_POST, 'apend', 'applePay');

		$modelOrder = new Models_Order();
		$paymentInfo = $modelOrder->getParamArray(25);
		$merchantidentifier = $paymentInfo[0]['value'];
		$displayName = $paymentInfo[1]['value'];
		$keyPass = $paymentInfo[2]['value'];
		$certPath = $paymentInfo[3]['value'];
		$keyPath = $paymentInfo[4]['value'];
		MG::loger($paymentInfo, 'apend', 'applePay');
		$ch = curl_init();
		$data = '{"merchantIdentifier":"'.$merchantidentifier.'", "domainName":"'.$_SERVER['SERVER_NAME'].'", "displayName":"'.$displayName.'"}';
		//$data = '{"merchantIdentifier" : "merchant.me.gleamlike","domainName":"gleamlike.me", "displayName":"test"}';
		curl_setopt($ch, CURLOPT_URL, $_POST['url']);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_CERTINFO, true); // For debug
		curl_setopt($ch, CURLOPT_VERBOSE, true); // For debug
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($ch, CURLOPT_SSLCERT, $_SERVER['DOCUMENT_ROOT'] . $certPath);
		curl_setopt($ch, CURLOPT_SSLKEY, $_SERVER['DOCUMENT_ROOT'] . $keyPath);
		curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $keyPass);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$res = curl_exec($ch);
		if($errno = curl_errno($ch)) {
			$error_message = curl_strerror($errno);
			MG::loger($errno, 'apend', 'applePay');
			MG::loger($error_message, 'apend', 'applePay');
		}

		curl_close($ch);
		echo json_encode($res);
	}

	/**
	 * Отправка криптограммы Apple Pay в Яндекс
	 * @depricated
	 * TODO delte it?
	 */
	public function sendApplePayToYandex() {
		$modelOrder = new Models_Order();
		$paymentInfo = $modelOrder->getParamArray(24);

		$postSend = '{
			"amount": {
				"value": "'.$_POST['summ'].'",
				"currency": "RUB"
			},
			"capture": true,
			"payment_method_data": {
				"type": "apple_pay",
				"payment_data": "'.base64_encode(json_encode($_POST['paymentToken']['paymentData'])).'"
			},
			"metadata":{ 
				"orderId": "'.$_POST['id'].'"
			}
		}';

		$username = $paymentInfo[0]['value'];
		$password = $paymentInfo[1]['value'];

		$ch = curl_init('https://api.yookassa.ru/v3/payments');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Idempotence-Key: '.md5(time())
		));
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postSend);

		$res = curl_exec($ch);

		curl_close($ch);
		echo json_encode($res);
	}

	/**
	 * Проверяет наличие подключенного модуля xmlwriter и библиотеки libxml.
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 */
	public function existXmlwriter() {
		$this->messageSucces = $this->lang['START_GENERATE_FILE'];
		$this->messageError = $this->lang['XMLWRITER_MISSING'];
		if (LIBXML_VERSION && extension_loaded('xmlwriter')) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Осуществляет импорт данных в таблицы продуктов и категорий.
	 */
	public function importFromCsv() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['IMPORT_SUCCESS'];
		$this->messageError = $this->lang['ERROR'];
		$importer = new Import();
		$importer->ImportFromCSV();
		return true;
	}

	/**
	 * Получает файл шаблона.
	 */
	public function getTemplateFile() {	
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}


		$this->messageError = $this->lang['NOT_FILE_TPL'];

		// если в запрашиваемом пути к файлу присутствуют .. , значит запрашивается файл не относящийся к шаблону
        if (strpos($_POST['path'], '..') != false) {
            return false;
        }
    
		// доступ к чтению файлов только у админа и модератора
		if (USER::access('setting') >= 1) {

			if ($_POST['type'] == '#ttab6') {
				$pathTemplate  = 'mg-templates'.DS.MG::getSetting('templateName');
				$pathTemplate  = 'mg-templates'.DS.'landings'.DS.MG::getSetting('landingName');
			}
			else{
				$pathTemplate  = 'mg-templates'.DS.MG::getSetting('templateName');
			}


      if ($_POST['type'] == '#ttab2' && !file_exists($pathTemplate.$_POST['path'])) {
        //$tempName = MG::getSetting('templateName');
        $filename = basename($_POST['path']);
        $res = DB::query("
          SELECT content FROM `".PREFIX."letters` 
          WHERE name = ".DB::quote($filename)." AND lang = ".DB::quote($_POST['lang'])
        );
        $row = DB::fetchAssoc($res);
        if (empty($row)) {
          $res = DB::query("
          SELECT content FROM `".PREFIX."letters` 
          WHERE name = ".DB::quote($filename)." AND lang = 'default'"
          );
          $row = DB::fetchAssoc($res);
        }
        if (!empty($row)) {
          $letterContent = $row['content'];
          $this->data['filecontent'] = $letterContent;
          $this->data['description'] = MG::replaceLetterTemplate(str_replace('.php', '', $filename), $letterContent, true);
          if (file_exists($pathTemplate.$_POST['path'])) {
            $this->data['warning'] = 'Для отправки письма приоритетным является файл из шаблона. Чтобы к письму применилась верстка из визуального редактора, необходимо удалить соответствующий файл из шаблона.';
          }
          return true;
        }
        elseif (file_exists($pathTemplate.$_POST['path']) && is_writable($pathTemplate.$_POST['path'])) {
          $this->data['filecontent'] = file_get_contents($pathTemplate.$_POST['path']);
          return true;
        } else {
          if (fileperms($pathTemplate.$_POST['path'])) {
            $this->data['filecontent'] = "CHMOD = ".substr(sprintf('%o', fileperms($pathTemplate.$_POST['path'])), -4);
          }
          return true;
        }
      }


			if (file_exists($pathTemplate.$_POST['path']) && is_writable($pathTemplate.$_POST['path'])) {
				$this->data['filecontent'] = file_get_contents($pathTemplate.$_POST['path']);
				return true;
			} else {
				$this->data['filecontent'] = "CHMOD = ".substr(sprintf('%o', fileperms($pathTemplate.$_POST['path'])), -4);
				return true;
			}
		}
		return false;
	}

	/**
	 * Сохраняет файл шаблона.
	 */
	public function saveTemplateFile() {
        // если в запрашиваемом пути к файлу присутствуют .. , значит запрашивается файл не относящийся к шаблону
        if (strpos($_POST['filename'], '..') != false) {
            return false;
        }

		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
    	$filename = str_replace(DS.'layout'.DS, "", $_POST['filename']);

		if (MG::getSetting('templateName') == 'moguta-standard' && ($_POST['type'] != '#ttab2' || $_POST['letterToDB'] == 'false')) {
			$this->messageError = $this->lang['REFERENCE_EDIT_FAIL'];
			return false;
		}

		$this->messageSucces = $this->lang['SAVE_FILE_TPL'];
		if ($_POST['type'] == '#ttab6') {
			$pathTemplate  = 'mg-templates'.DS.MG::getSetting('templateName');
			
			$pathTemplate  = 'mg-templates'.DS.'landings'.DS.MG::getSetting('landingName');
		}
		else{
			$pathTemplate  = 'mg-templates'.DS.MG::getSetting('templateName');
		}

		if ($_POST['type'] == '#ttab2' && $_POST['letterToDB'] == 'true') {
			if ($filename == 'email_template.php' || $filename == 'email_order_admin.php') {
				return true;
			}

			if (empty($_POST['lang'])) {
				$_POST['lang'] = 'default';
			}
			$sql = DB::query("SELECT * FROM `".PREFIX."letters` WHERE name = ".DB::quote($filename)." AND lang = ".DB::quote($_POST['lang']));
			$res = DB::fetchAssoc($sql);
			if (empty($res)) {
				DB::query("INSERT INTO `".PREFIX."letters` (content, name, lang) VALUES (".DB::quote($_POST['content']).", ".DB::quote($filename).", ".DB::quote($_POST['lang']).")");
			} else {
				DB::query("UPDATE `".PREFIX."letters` SET content = ".DB::quote($_POST['content'])." 
				WHERE name = ".DB::quote($filename)." AND lang = ".DB::quote($_POST['lang']));
			}

			return true;
		}
		if (file_exists($pathTemplate.$_POST['filename']) && is_writable($pathTemplate.$_POST['filename'])) {
			file_put_contents($pathTemplate.$_POST['filename'], $_POST['content']);
		} else {
			return false;
		}
		return true;
	}

  /**
   * Получает текст письма с другого языка
   */
	public function getLocaleLetter() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
    $filename = basename($_POST['path']);
    $lang = $_POST['lang'];
	  $res = DB::query("SELECT content FROM `".PREFIX."letters` WHERE name = ".DB::quote($filename)." AND lang = ".DB::quote($lang));
	  $row = DB::fetchAssoc($res);
	  if (!empty($row)) {
	    $content = $row['content'];
    } else {
      $res = DB::query("SELECT content FROM `".PREFIX."letters` WHERE name = ".DB::quote($filename)." AND lang = 'default'");
      $row = DB::fetchAssoc($res);
      $defaultContent = $row['content'];

      DB::query("INSERT INTO `".PREFIX."letters` (name, content, lang) VALUES (".DB::quote($filename).", ".DB::quote($defaultContent).", ".DB::quote($lang).")");
      $letterID = DB::insertId();
      $res = DB::query("SELECT content FROM `".PREFIX."letters` WHERE id = ".DB::quoteInt($letterID));
      $row = DB::fetchAssoc($res);
      $content = $row['content'];
    }
	  $this->data = $content;
	  return true;
  }

	/**
	 * Очищает кеш проверки версий и проверяет наличие новой.
	 */
	public function clearLastUpdate() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		MG::setOption('updateDowntime', 'false');
		if (!$checkLibs = MG::libExists()) {
			MG::setOption('timeLastUpdata', '');
			$newVer = Updata::checkUpdata(true);
			Storage::clear('mp-cache');
			MarketplaceMain::update();
			if (!$newVer) {
				$this->messageError = $this->lang['NOT_NEW_VERSION'];
				return false;
			}
			$this->messageSucces = $this->lang['AVAIBLE_NEW_VERSION'].' '.$newVer['lastVersion'];
			return true;
		} else {
			$this->messageError = implode('<br>', $checkLibs);
			return false;
		}
	}

	/**
	 * Удаляет все файлы логов в директории временных файлов сайта TEMP_DIR.
	 */
	public function logDataСlear() {
		if(USER::access('setting')<2){
			return false;
		}
		MG::rrmdir(SITE_DIR.TEMP_DIR, true);
		if(!is_dir(SITE_DIR.TEMP_DIR)){
			$this->messageSucces = $this->lang['ACT_SUCCESS'];
			MG::sizeTempFiles();
			return true;
		} else {
			$this->messageError = $this->lang['ERRORS_MESSAGE_14'];
			return false;
		}
	}

	/**
	 * Получает список продуктов при вводе в поле поиска товара при создании заказа через админку.
	 */
	public function getSearchData() {
		if (USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$keyword = URL::getQueryParametr('search');
		$adminOrder = URL::getQueryParametr('adminOrder');
		$searchCats = URL::getQueryParametr('searchCats');
		$useVariants = URL::getQueryParametr('useVariants');
		$userCurrency = URL::getQueryParametr('userCurrency');
		$items = array();
		$adminSearch = false;
		if ($adminOrder === 'yep') {
			$adminSearch = true;
		}
		if (!$searchCats && $searchCats !== 0) {
			$searchCats = -1;
		}

		if (!empty($keyword)) {
			$catalog = new Models_Catalog;
			$product = new Models_Product;
			$order = new Models_Order;
			$currencyRate = MG::getSetting('currencyRate');
			$currencyShort = MG::getSetting('currencyShort');
			$currencyShopIso = MG::getSetting('currencyShopIso');
			$items = $catalog->getListProductByKeyWord($keyword, true, false, $adminSearch, false, false, $searchCats);//добавление к заказу из админки товара, который не выводится в каталог.

			$blockedProp = $product->noPrintProperty();

			foreach ($items['catalogItems'] as $key => $item) {
				$prop = array();
				$res = DB::query('SELECT * FROM '.PREFIX.'property AS p LEFT JOIN
					'.PREFIX.'category_user_property AS cup ON cup.property_id = p.id
					WHERE cup.category_id = '.DB::quote($item['cat_id']));
				while($row = DB::fetchAssoc($res)) {
					$prop[] = $row;
				}

				Property::addDataToProp($prop, $item['id']);

				// MG::loger($prop);

				$items['catalogItems'][$key]['image_url'] = mgImageProductPath($item["image_url"], $item['id'], 'small');

				$propertyFormData = $product->createPropertyForm($param = array(
					'id' => $item['id'],
					'maxCount' => 999,
					'productUserFields' => $prop,//$item['thisUserFields'],
					'action' => "/catalog",
					'method' => "POST",
					'ajax' => true,
					'blockedProp' => $blockedProp,
					'noneAmount' => true,
					'titleBtn' => "<span>".$this->lang['EDIT_ORDER_14']."</span>",
					'blockVariants' => $product->getBlockVariants($item['id'],0,($adminOrder=='yep')?true:false),
					'classForButton' => 'addToCart buy-product buy custom-btn',
					'printCompareButton' => false,
					'currency_iso' => !empty($userCurrency)?$userCurrency:$item['currency_iso'],
					'showCount' => false,
				), $adminOrder);

				$items['catalogItems'][$key]['propertyForm'] = $propertyFormData['html'];
				$items['catalogItems'][$key]['notSet'] = $order->notSetGoods($item['id']);
			}
		}
		if (isset($items['catalogItems']) && is_array($items['catalogItems'])) {
			foreach ($items['catalogItems'] as $key => $product) {
				if ($useVariants == 'true' && !empty($product['variants'])) {
					$items['catalogItems'][$key]["price"] = MG::numberFormat($product['variants'][0]["price_course"]);
					$items['catalogItems'][$key]["old_price"] = $product['variants'][0]["old_price"];
					$items['catalogItems'][$key]["count"] = $product['variants'][0]["count"];
					$items['catalogItems'][$key]["code"] = $product['variants'][0]["code"];
					$items['catalogItems'][$key]["weight"] = $product['variants'][0]["weight"];
					$items['catalogItems'][$key]["price_course"] = $product['variants'][0]["price_course"];
				}
			}
		}

		$searchData = array(
			'status' => 'success',
			'item' => array(
				'keyword' => $keyword,
				'count' => $items['numRows'],
				'items' => $items,
			),
			'currency' => MG::getSetting('currency')
		);

		echo json_encode($searchData);
		exit;
	}

	/**
	 * Возвращает случайный продукт из ассортимента.
	 * @return bool
	 */
	public function getRandomProd() {
		if (USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$res = DB::query('
			SELECT id 
			FROM `'.PREFIX.'product` 
				WHERE 1=1 
			ORDER BY RAND() LIMIT 1');
		if ($row = DB::fetchAssoc($res)) {
			$product = new Models_Product();
			$prod = $product->getProduct($row['id']);
			$prod['image_url'] = mgImageProductPath($prod['image_url'], $prod['id']);
		} else {
			return false;
		}
		$this->data['product'] = $prod;
		return true;
	}

	/**
	 * Возвращает список заказов для вывода статистики по заданному периоду.
	 * @return bool
	 */
	public function getOrderPeriodStat() {
		if (USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$model = new Models_Order;
		$this->data = $model->getStatisticPeriod($_POST['from_date_stat'], $_POST['to_date_stat']);
		return true;
	}
    /**
	* Возвращает список складов в формате - склад)ключ) название
	* @return bool
	*/
    public function getStorages(){
		if (USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
      if(!MG::enabledStorage()){
        return false;
      }else{
        $storages = unserialize(stripcslashes(MG::getSetting('storages')));
        $storagesName = array();
        foreach ($storages as $v){
          $storagesName[$v['id']] = $v['name'];
        }
        $this->data = json_encode($storagesName);
        return true;    
      }
    }
    
    public function getProducStorage(){
		if (USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
      if(!MG::enabledStorage()){
        return false;
      }else{
		// NEW
		if (
			empty($_POST['prodId']) ||
			empty($_POST['prodCode'])
		) {
			return false;
		}
		$productId = intval(trim($_POST['prodId']));
		$productCode = trim($_POST['prodCode']);

		$productModel = new Models_Product();
		$variantId = $productModel->getVariantIdByCode($productCode);
		if (!$variantId) {
			$variantId = 0;
		}
		$productStorageData = $productModel->getProductStorageData($productId, $variantId);
		$this->data = $productStorageData;
		return true;
      }
    }

    /**
	 * Возвращает список заказов для вывода статистики.
	 * 
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 * 
	 * @return bool
	 */
	public function getOrderStat() {
		$model = new Models_Order;
		$this->data = $model->getOrderStat();
		return true;
	}

	/**
	 * Выполняет операцию над отмеченными заказами в админке.
	 * @return bool
	 */
	public function operationOrder() {
		if(USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		if (empty($_POST['orders_id']) && $_POST['operation'] != 'fulldelete') {
			$this->messageError = $this->lang['CHECK_ORDER'];
			return false;
		}
		$result = true;

		$model = new Models_Order;

		switch ($_POST['operation']) {
			case 'delete':
				foreach ($_POST['orders_id'] as $orderId) {
					$model->refreshCountProducts($orderId, 4);
				}
				$result = $model->deleteOrder(true, $_POST['orders_id']);
				break;
			case 'changeStatus':
				$error = false;
				foreach ($_POST['orders_id'] as $orderId) {
					$statusId = intval($_POST['param']);
					if ($statusId !== 4) {
						$checkResult = $model->checkOrderReturn($orderId);
						if (!$checkResult) {
							$error = true;
							continue;
						}
					}
					$result = $model->updateOrder(array('id' => $orderId, 'status_id' => $_POST['param']), $_POST['changeStatusEmailUser']);
				}
				if ($error) {
					$this->messageError = 'Не всем заказам удалось изменить статус, некоторых товаров нет в наличии.';
					return false;
				}
				break;
			case 'getcsvorder':
				$fileDir = $model->exportToCsvOrder($_POST['orders_id']);
				$filenameArr = explode('/',$fileDir);
				$filename = end($filenameArr);
				$this->data['filecsv'] = $filename;
				$this->data['filecsvpath'] = $fileDir;
				$this->messageSucces = $this->lang['IMPORT_TO_FILE_SUCCESS'].' '.$filename;
				$result = true;
				break;
			case 'csvorderfull':
				$fileDir = $model->exportToCsvOrder($_POST['orders_id'], true);
				$filenameArr = explode('/',$fileDir);
				$filename = end($filenameArr);
				$this->data['filecsv'] = $filename;
				$this->data['filecsvpath'] = $fileDir;
				$this->messageSucces = $this->lang['IMPORT_TO_FILE_SUCCESS'].' '.$filename;
				$result = true;
				break;
			case 'fulldelete':
				$res = DB::query("SELECT `id` FROM `".PREFIX."order`");
				while ($row = DB::fetchAssoc($res)) {
					$model->refreshCountProducts($row['id'], 4);
				}
				DB::query("TRUNCATE `".PREFIX."order`");
				$result = true;
				break;
			case 'changeOwner':
				DB::query('UPDATE '.PREFIX.'order SET owner = '.DB::quoteInt($_POST['param']).' WHERE id IN ('.DB::quoteIN($_POST['orders_id']).')');
				$result = true;
				break;
			case 'checkedPayYes':
				foreach ($_POST['orders_id'] as $orderId) {
					$result = $model->updateOrder(['id' => $orderId, 'paided' => 1]);
				}
				break;
			case 'checkedPayNo':
				foreach ($_POST['orders_id'] as $orderId) {
					$result = $model->updateOrder(['id' => $orderId, 'paided' => 0]);
				}
				break;
			case 'massPrint':
				$arrayKeys = array_keys($_POST['orders_id']);
				$firstArrayKey = array_shift($arrayKeys);
				$html = '';
				foreach ($_POST['orders_id'] as $key => $orderId) {
					if ($key === $firstArrayKey) {
						$html .= '<span>';
					} else {
						$html .= '<div style="page-break-before: always !important;">';
					}
					$html .= $model->printOrder($orderId, true, $_POST['param']);
					$html .= '</div>';
				}
				$this->data['html'] = $html;
				$result = true;
				break;
		}

		$this->data['count'] = $model->getNewOrdersCount();
		return $result;
	}

	/**
	 * Выполняет операцию над отмеченными характеристиками в админке.
	 * @return bool
	 */
	public function operationProperty() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$operation = $_POST['operation'];
		if (empty($_POST['property_id']) && $operation != 'fulldelete') {
			$this->messageError = $this->lang['CHECK_PROP'];
			return false;
		}
		if ($operation == 'delete') {
			foreach ($_POST['property_id'] as $propertyId) {
				$_POST['id'] = $propertyId;
				$this->deleteUserProperty();
			}
		} elseif (strpos($operation, 'activity') === 0 && !empty($_POST['property_id'])) {
			foreach ($_POST['property_id'] as $propertyId) {
				$_POST['id'] = $propertyId;
				$_POST['activity'] = substr($operation, -1, 1);
				$this->visibleProperty();
			}
		} elseif (strpos($operation, 'filter') === 0 && !empty($_POST['property_id'])) {
			foreach ($_POST['property_id'] as $propertyId) {
				$_POST['id'] = $propertyId;
				$_POST['filter'] = substr($operation, -1, 1);
				$this->filterProperty();
			}
		} elseif (strpos($operation, 'fulldelete') === 0) {
			$res = DB::query("SELECT `id` FROM `".PREFIX."property`");
			while ($row = DB::fetchAssoc($res)) {
				$_POST['id'] = $row['id'];
				$this->deleteUserProperty();
			}
		} elseif (strpos($operation, 'setCats') === 0) {
			foreach ($_POST['property_id'] as $propertyId) {
				DB::query("DELETE FROM `".PREFIX."category_user_property` WHERE `property_id` = ".DB::quote($propertyId));

				if (!empty($_POST['param'])) {
					$values = array();
					foreach ($_POST['param'] as $cat_id) {
						$values[] = "(".DB::quoteInt($cat_id).", ".DB::quoteInt($propertyId).")";
					}
					DB::query("INSERT IGNORE INTO `".PREFIX."category_user_property` VALUES ".implode(',', $values));
				}
			}
		} elseif (strpos($operation, 'addCats') === 0) {
			if (!empty($_POST['param'])) {
				foreach ($_POST['property_id'] as $propertyId) {
					DB::query("DELETE FROM `".PREFIX."category_user_property` WHERE 
						`property_id` = ".DB::quote($propertyId)." AND 
						`category_id` IN (".DB::quoteIN($_POST['param']).")");

					$values = array();
					foreach ($_POST['param'] as $cat_id) {
						$values[] = "(".DB::quoteInt($cat_id).", ".DB::quoteInt($propertyId).")";
					}
					DB::query("INSERT IGNORE INTO `".PREFIX."category_user_property` VALUES ".implode(',', $values));

				}
			}
		} elseif (strpos($operation, 'setGroup') === 0) {
			foreach ($_POST['property_id'] as $propertyId) {
				DB::query("UPDATE `".PREFIX."property` 
					SET `group_id` = ".DB::quoteInt($_POST['param'])."
					WHERE `id` = ".DB::quoteInt($propertyId)
				);
			}
		}
		return true;
	}

	/**
	 * Выполняет операцию над отмеченными товарами в админке.
	 * @return bool
	 */
	public function operationProduct() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$productModel = new Models_Product();
		$operation = $_POST['operation'];
		if (empty($_POST['products_id']) && $operation != 'fulldelete') {
			$this->messageError = $this->lang['CHECK_PRODUCT'];
			return false;
		}
		if ($operation == 'delete') {
			foreach ($_POST['products_id'] as $productId) {
				$productModel->deleteProduct($productId);
			}
		} elseif (strpos($operation, 'activity') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$productModel->updateProduct(array('id' => $product, 'activity' => substr($operation, -1, 1)));
			}
		} elseif (strpos($operation, 'recommend') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$productModel->updateProduct(array('id' => $product, 'recommend' => substr($operation, -1, 1)));
			}
		} elseif (strpos($operation, 'new') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$productModel->updateProduct(array('id' => $product, 'new' => substr($operation, -1, 1)));
			}
		} elseif (strpos($operation, 'clone') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$productModel->clone = true;
				$productModel->cloneProduct($product);
			}
		} elseif (strpos($operation, 'delete') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$productModel->deleteProduct($product);
			}
		} elseif (strpos($operation, 'fulldelete') === 0) {
			$res = DB::query("SELECT `id` FROM `".PREFIX."product`");
			while ($row = DB::fetchAssoc($res)) {
				$productModel->deleteProduct($row['id']);
			}
		} elseif (strpos($operation, 'changecur') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$part = explode('_', $operation);
				$iso = str_replace($part[0].'_','',$operation);

				$productModel->convertToIso($iso, $_POST['products_id']);
				$this->data['clearfilter'] = true;
				//$result = $model->updateOrder(array('id' => $orderId, 'status_id' => substr($operation, -1, 1)));
			}
		} elseif (strpos($operation, 'getcsv') === 0 && !empty($_POST['products_id'])) {
				$catalogModel = new Models_Catalog();
				$fileDir = $catalogModel->exportToCsv($_POST['products_id']);
				$filenameArr = explode('/',$fileDir);
				$filename = end($filenameArr);
				$this->data['filecsv'] = $filename;
				$this->data['filecsvpath'] = $fileDir;
				$this->messageSucces = $this->lang['IMPORT_TO_FILE_SUCCESS'].' '.$filename;
		} elseif (strpos($operation, 'getyml') === 0 && !empty($_POST['products_id'])) {
				if (LIBXML_VERSION && extension_loaded('xmlwriter')) {
					$ymlLib = new YML();
					$fileDir = $ymlLib->exportToYml($_POST['products_id']);
					$filenameArr = explode('/',$fileDir);
					$filename = end($filenameArr);
					$this->data['fileyml'] = $filename;
					$this->data['fileymlpath'] = $fileDir;
					$this->messageSucces = $this->lang['IMPORT_TO_FILE_SUCCESS'].' '.$filename;
				} else {
					$this->messageError = $this->lang['XMLWRITER_MISSING2'];
				}
		} elseif (strpos($operation, 'move_to_category') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$productModel->updateProduct(array('id' => $product, 'cat_id' => $_POST['data']['category_id']));
			}
		} elseif (strpos($operation, 'add_to_category') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $product) {
				$obj = $productModel->getProduct($product);
				if(!empty($obj['inside_cat'])){
					$insideArray = explode(',', $obj['inside_cat']);
					$merge =  array_unique(array_merge($_POST['data']['category_id'],$insideArray));
				} else {
					$merge =  $_POST['data']['category_id'];
				}
				$insideCat = implode(',',$merge);
				$productModel->updateProduct(array('id' => $product, 'inside_cat' => $insideCat));
			}
		} elseif (strpos($operation, 'set_zero_stock') === 0 && !empty($_POST['products_id'])) {
			foreach ($_POST['products_id'] as $productId) {
				$productModel->setZeroStock($productId);
			}
		} elseif (strpos($operation, 'createvar') === 0 && !empty($_POST['products_id']) && $_POST['data']['toProdId']) {
			$dropSizeMap = true;
			$catFrom = array();
			$res = DB::query("SELECT `cat_id` FROM `".PREFIX."product` WHERE `id` IN (".DB::quoteIN($_POST['products_id']).")");
			while ($row = DB::fetchAssoc($res)) {
				$catFrom[] = $row['cat_id'];
			}
			$catFrom = array_unique($catFrom);
			$catTo = -1;
			$res = DB::query("SELECT `cat_id` FROM `".PREFIX."product` WHERE `id` = ".DB::quoteInt($_POST['data']['toProdId']));
			if ($row = DB::fetchAssoc($res)) {
				$catTo = $row['cat_id'];
			}
			if (count($catFrom) == 1 && $catTo == end($catFrom)) {
				$res = DB::query("SELECT p.`id` FROM `".PREFIX."category_user_property` u 
					LEFT JOIN `".PREFIX."property` p 
					ON u.`property_id` = p.`id`
					WHERE u.`category_id` = ".DB::quote($catTo)." AND p.`type` IN ('color','size')");
				if ($row = DB::fetchAssoc($res)) {
					$dropSizeMap = false;
				}
				goto propCheckOk;
			}

			$colorsNsizes = array();
			$res = DB::query("SELECT `id` FROM `".PREFIX."property` WHERE `type` IN ('color', 'size')");
			while ($row = DB::fetchAssoc($res)) {
				$colorsNsizes[] = $row['id'];
			}
			if (empty($colorsNsizes)) {
				goto propCheckOk;
			}

			$sizemapTo = array();
			$res = DB::query("SELECT * FROM `".PREFIX."category_user_property` 
				WHERE `category_id` = ".DB::quoteInt($catTo)." AND `property_id` IN (".DB::quoteIN($colorsNsizes).")");
			while ($row = DB::fetchAssoc($res)) {
				$sizemapTo[] = $row['property_id'];
			}
			if (empty($sizemapTo)) {
				goto propCheckOk;
			} else {
				$dropSizeMap = false;
			}

			foreach ($catFrom as $catFromId) {
				$sizemapFrom = array();
				$res = DB::query("SELECT * FROM `".PREFIX."category_user_property` 
					WHERE `category_id` = ".DB::quoteInt($catFromId)." AND `property_id` IN (".DB::quoteIN($colorsNsizes).")");
				while ($row = DB::fetchAssoc($res)) {
					$sizemapFrom[] = $row['property_id'];
				}
				if ($sizemapTo !== array_intersect($sizemapTo, $sizemapFrom) || $sizemapFrom !== array_intersect($sizemapFrom, $sizemapTo)) {
					goto propCheckFail;
				}
			}
			goto propCheckOk;
			propCheckFail:
			$this->messageError = $this->lang['PRODUCT_CREATEVAR_FAIL'];
			return false;
			propCheckOk:
			// если товарв без вариантов, делаем ему базовый вариант
			$res = DB::query('SELECT COUNT(id) FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($_POST['data']['toProdId']));
			$row = DB::fetchAssoc($res);
			if($row['COUNT(id)'] == 0) {
				$res = DB::query('SELECT * FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($_POST['data']['toProdId']));
				if($row = DB::fetchAssoc($res)) {
					$toVar = array();
					$toVar['title_variant'] = $row['title'];
					$toVar['price'] = $row['price'];
					$toVar['price_course'] = $row['price_course'];
					$toVar['code'] = $row['code'];
					$toVar['weight'] = $row['weight'];
					$toVar['currency_iso'] = $row['currency_iso'];
					$toVar['count'] = $row['count'];
					$toVar['old_price'] = $row['old_price'];
					$toVar['product_id'] = $_POST['data']['toProdId'];
					// viewData('INSERT INTO '.PREFIX.'product_variant SET '.DB::buildPartQuery($toVar));
					DB::query('INSERT INTO '.PREFIX.'product_variant SET '.DB::buildPartQuery($toVar));
				}
			}
			// переделываем другие товары ему в варианты
			$productOriginal = array();
			$res = DB::query('SELECT * FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($_POST['data']['toProdId']));
			while($row = DB::fetchAssoc($res)) {
				$productOriginal = $row;
			}
			$cRate = MG::getSetting('currencyRate');
			foreach ($_POST['products_id'] as $product) {
				$resq = DB::query('SELECT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($product));
				if($rowq = DB::fetchAssoc($resq)) {
					$resIn = DB::query('SELECT cat_id FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($product));
					if($rowIn = DB::fetchAssoc($res)) {
						$catId = $rowIn['cat_id'];
					}
					$resIn = DB::query('SELECT * FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($product));
					while ($rowIn = DB::fetchAssoc($resIn)) {
						// проверка валюты
						if($rowIn['currency_iso'] != $productOriginal['currency_iso']) {
							$rate = $cRate[$rowIn['currency_iso']] / $cRate[$productOriginal['currency_iso']];
							$rowIn['price'] = round($rowIn['price'] * $rate, 2);
						}
						// меняем привязку товара
						$rowIn['product_id'] = $_POST['data']['toProdId'];
						// проверяем характеристику
						if ($dropSizeMap) {
							$rowIn['size'] = '';
							$rowIn['color'] = '';
						} else {
							$rrez = DB::query("SELECT `id` FROM `".PREFIX."product_variant` 
								WHERE `product_id` = ".DB::quoteInt($_POST['data']['toProdId'])." AND `color` = ".DB::quoteInt($rowIn['color'])." AND `size` = ".DB::quoteInt($rowIn['size']));
							if ($rrow = DB::fetchAssoc($rrez)) {
								// повтор размерной сетки - дропаем
								DB::query('DELETE FROM `'.PREFIX.'product_variant` WHERE `id` = '.DB::quoteInt($rowIn['id']));
								continue;
							}
						}
						// обновляем
						DB::query('UPDATE '.PREFIX.'product_variant SET '.DB::buildPartQuery($rowIn).' WHERE id = '.DB::quoteInt($rowIn['id']));
						$oldPicFolder = SITE_DIR.'uploads'.DS.'product'.DS.floor($product/100).'00'.DS.$product.DS;
						$newPicFolder = SITE_DIR.'uploads'.DS.'product'.DS.floor($_POST['data']['toProdId']/100).'00'.DS.$_POST['data']['toProdId'].DS;
						if ($rowIn['image'] && is_file($oldPicFolder.$rowIn['image'])) {
							copy($oldPicFolder.$rowIn['image'], $newPicFolder.$rowIn['image']);
						}
						if ($rowIn['image'] && is_file($oldPicFolder.'thumbs'.DS.'30_'.$rowIn['image'])) {
							copy($oldPicFolder.'thumbs'.DS.'30_'.$rowIn['image'], $newPicFolder.'thumbs'.DS.'30_'.$rowIn['image']);
						}
						if ($rowIn['image'] && is_file($oldPicFolder.'thumbs'.DS.'70_'.$rowIn['image'])) {
							copy($oldPicFolder.'thumbs'.DS.'70_'.$rowIn['image'], $newPicFolder.'thumbs'.DS.'70_'.$rowIn['image']);
						}
					}
				} else {
					$res = DB::query('SELECT * FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($product));
					while ($row = DB::fetchAssoc($res)) {
						$toVar = array();
						$toVar['title_variant'] = $row['title'];
						$toVar['price'] = $row['price'];
						$toVar['price_course'] = $row['price_course'];
						$toVar['code'] = $row['code'];
						$toVar['weight'] = $row['weight'];
						$toVar['currency_iso'] = $row['currency_iso'];
						$toVar['count'] = $row['count'];
						$toVar['old_price'] = $row['old_price'];
						$toVar['product_id'] = $_POST['data']['toProdId'];
						$toVar['image'] = explode('|', $row['image_url']);

						if (empty($toVar['image'])) {
							unset($toVar['image']);
						} else {
							$toVar['image'] = $toVar['image'][0];
							$oldPicFolder = SITE_DIR.'uploads'.DS.'product'.DS.floor($product/100).'00'.DS.$product.DS;
							$newPicFolder = SITE_DIR.'uploads'.DS.'product'.DS.floor($_POST['data']['toProdId']/100).'00'.DS.$_POST['data']['toProdId'].DS;
							if ($toVar['image'] && is_file($oldPicFolder.$toVar['image'])) {
								copy($oldPicFolder.$toVar['image'], $newPicFolder.$toVar['image']);
							}
							if ($toVar['image'] && is_file($oldPicFolder.'thumbs'.DS.'30_'.$toVar['image'])) {
								copy($oldPicFolder.'thumbs'.DS.'30_'.$toVar['image'], $newPicFolder.'thumbs'.DS.'30_'.$toVar['image']);
							}
							if ($toVar['image'] && is_file($oldPicFolder.'thumbs'.DS.'70_'.$toVar['image'])) {
								copy($oldPicFolder.'thumbs'.DS.'70_'.$toVar['image'], $newPicFolder.'thumbs'.DS.'70_'.$toVar['image']);
							}
						}

						// проверка валюты
						if($toVar['currency_iso'] != $productOriginal['currency_iso']) {
							$rate = $cRate[$toVar['currency_iso']] / $cRate[$productOriginal['currency_iso']];
							$toVar['price'] = round($toVar['price'] * $rate, 2);
						}
						// viewData('INSERT INTO '.PREFIX.'product_variant SET '.DB::buildPartQuery($toVar));
						DB::query('INSERT INTO '.PREFIX.'product_variant SET '.DB::buildPartQuery($toVar));
					}
				}
				DB::query('DELETE FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($product));
				DB::query('DELETE FROM '.PREFIX.'product_user_property WHERE product_id = '.DB::quoteInt($product));
				MG::rrmdir(SITE_DIR.'uploads'.DS.'product'.DS.floor($product/100).'00'.DS.$product);
			}
		}

		return true;
	}

	/**
	 * Выполняет операцию над отмеченными категориями в админке.
	 * @return bool
	 */
	public function operationCategory() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$operation = $_POST['operation'];

		if (empty($_POST['category_id']) && $operation != 'fulldelete') {
			$this->messageError = $this->lang['CHECK_CATEGORY'];
			return false;
		}
		if ($operation == 'delete') {
			foreach ($_POST['category_id'] as $catId) {
                            MG::get('category')->delCategory($catId);
			}
		} elseif ($operation == 'deleteproduct') {
			foreach ($_POST['category_id'] as $catId) {
				if ($_POST['dropProducts'] == 'true') {
					$model = new Models_Product;
					$cats = MG::get('category')->getCategoryList($catId);
					$cats[] = $catId;
					$cats = implode(', ', $cats);
					$res = DB::query('SELECT `id` FROM `'.PREFIX.'product` WHERE `cat_id` IN ('.$cats.')');
					while($row = DB::fetchAssoc($res)) {
						$model->deleteProduct($row['id']);
					}
				}
				MG::get('category')->delCategory($catId);
			}
		} elseif (strpos($operation, 'invisible') === 0 && !empty($_POST['category_id'])) {
			foreach ($_POST['category_id'] as $catId) {
				MG::get('category')->updateCategory(array('id' => $catId, 'invisible' => substr($operation, -1, 1)));
				$arrayChildCat = MG::get('category')->getCategoryList($catId);
				foreach ($arrayChildCat as $ch_id) {
					MG::get('category')->updateCategory(array('id' => $ch_id, 'invisible' => substr($operation, -1, 1)));
				}
			}
		} elseif (strpos($operation, 'fulldelete') === 0) {
			$cats = array();
			$res = DB::query("SELECT `id` FROM `".PREFIX."category`");
			while ($row = DB::fetchAssoc($res)) {
				$cats[] = $row['id'];
				MG::get('category')->delCategory($row['id']);
			}
			if ($_POST['dropProducts'] == 'true') {
				$model = new Models_Product;
				$cats = implode(', ', $cats);
				$res = DB::query('SELECT `id` FROM `'.PREFIX.'product` WHERE `cat_id` IN ('.$cats.')');
				while($row = DB::fetchAssoc($res)) {
					$model->deleteProduct($row['id']);
				}
			}
		} elseif (strpos($operation, 'activity') === 0 && !empty($_POST['category_id'])) {
			$act = substr($operation, -1, 1);
			if(strpos($operation, 'activity_with_products') === 0){
				foreach ($_POST['category_id'] as $catId) {
					MG::get('category')->updateCategory(array('id' => $catId, 'activity' => $act));
					DB::query('UPDATE `'.PREFIX.'product` SET `activity`='.DB::quote($act).' WHERE `cat_id`='.DB::quoteInt($catId));
				}
			}
			else{
				foreach ($_POST['category_id'] as $catId) {
					MG::get('category')->updateCategory(array('id' => $catId, 'activity' => $act));
				}
			}
		} elseif (strpos($operation, 'move') === 0) {
			foreach ($_POST['category_id'] as $catId) {
				MG::get('category')->moveCategory($catId, $_POST['param']);
			}
			Category::moveBrokenCats();
        	Category::startIndexation();
			Storage::clear();
		}
		Storage::clear('category');
		return true;
	}
 /**
	 * Выполняет операцию над отмеченными страницами в админке.
	 * @return bool
	 */
	public function operationPage() {
		if(USER::access('page') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$operation = $_POST['operation'];

		if (empty($_POST['page_id']) && $operation != 'fulldelete') {
			$this->messageError = $this->lang['CHECK_PAGE'];
			return false;
		}
		if ($operation == 'delete') {
			foreach ($_POST['page_id'] as $pageId) {
				MG::get('pages')->delPage($pageId);
			}
		} elseif (strpos($operation, 'invisible') === 0 && !empty($_POST['page_id'])) {
			foreach ($_POST['page_id'] as $pageId) {
				MG::get('pages')->updatePage(array('id' => $pageId, 'invisible' => substr($operation, -1, 1)));
			}
		} elseif (strpos($operation, 'fulldelete') === 0) {
			DB::query("TRUNCATE `".PREFIX."page`");
			MG::rrmdir(SITE_DIR.'uploads'.DS.'page');
		} elseif (strpos($operation, 'move') === 0) {
			foreach ($_POST['page_id'] as $pageId) {
				MG::get('pages')->movePage($pageId, $_POST['param']);
			}
			Storage::clear();
		}
		return true;
	}

	/**
	 * Получает параметры заказа.
	 */
	public function getOrderData() {
		if (USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		unset($_SESSION['deliveryAdmin']);
		$orderData = array('yur_info' => array());
		$model = new Models_Order();
		if (!empty($_POST['id'])) {
			$orderData = $model->getOrder(" id = ".DB::quote(intval($_POST['id'])));
			$orderData = $orderData[$_POST['id']];

			if ($orderData['number']=='') {
				$orderData['number'] = $orderData['id'];
				DB::query("UPDATE `".PREFIX."order` SET `number`= ".DB::quote($orderData['number'])." WHERE `id`=".DB::quote($orderData['id'])."");
			}

			$orderData['yur_info'] = unserialize(stripslashes($orderData['yur_info']));
			if(!empty($orderData['yur_info']['nameyur'])){
				$orderData['yur_info']['nameyur'] = htmlspecialchars_decode($orderData['yur_info']['nameyur']);
			}
            $sql = "SELECT `order_content` FROM ".PREFIX."order WHERE `id` = ".db::quote($_POST['id']);
            $row = db::fetchAssoc(db::query($sql));
			$orderData['order_content'] = unserialize(stripslashes($row['order_content']));      
		}

		if (!empty($orderData['order_content'])) {
			$product = new Models_Product();
		foreach ($orderData['order_content'] as &$item) {
          foreach ($item as $k => &$v) {
            //Для складов
            if ($k == 'storage_id') {
              $storage = array();
              $storageCountArr = array();
              foreach ($v as $storageId => $count) {
                $storage[] = $storageId . '_' . $count;     
              }
              $v = $storage;
            } else {
              $v = rawurldecode($v);
            }
          }
           //Остаток на складах по товарам, для возможности редактировать склады у товаров из админки  
          if (isset($item['storage_id'])) {
			$productModel = new Models_Product();
			$productId = intval($item['id']);
			$variantId = intval($item['variant_id']);
			$storageCountArr = [];
			$storagesData = $productModel->getProductStoragesData($productId, $variantId);
			foreach ($storagesData as $storageId => $storageCount) {
				$storageCountArr[] = [
					'storage' => $storageId,
					'count' => $storageCount,
				];
			}
            $item['storageCountArr'] = $storageCountArr;
          }
        }
      foreach ($orderData['order_content'] as &$items) {                                
								$res = $product->getProduct($items['id']);
                                $items['name'] = htmlspecialchars_decode($items['name']);
                            	$items['image_url'] = mgImageProductPath($res['image_url'], $items['id'], 'small');
				$items['property'] = htmlspecialchars_decode(str_replace('&amp;', '&', $items['property']));
				$response['discount'] = $items['discount'];//todo wtf
				$percent = $items['discount'];
				$items['maxCount'] = $res['count'];
				$items['category_unit'] = $res['category_unit'];
				if(empty($items['category_unit'])){ // Если в заказе присутствует удаленный товар, получаем данные из order_content
					$items['category_unit'] = 'шт.';
					if(!empty($items['unit'])){
						$items['category_unit'] = $items['unit'];
					}
				}
				
				$variants = DB::query("SELECT `id`, `count`, `image` FROM `".PREFIX."product_variant`
									WHERE `product_id`=".DB::quote($items['id'])." AND `code`=".DB::quote($items['code']));
				if ($variant = DB::fetchAssoc($variants)) {
					if ($variant['image']) {
						$items['image_url'] = mgImageProductPath($variant['image'], $items['id'], 'small');
					}
					$items['variant'] = !empty($items['variant_id'])?$items['variant_id']:$variant['id'];
					$items['maxCount'] = $variant['count'];
				}
				$items['notSet'] = $model->notSetGoods($items['id']);
				$items['price'] = MG::numberDeFormat($items['price']);
			}
		}
        

		$response['pluginForm'] = MG::createHook('getAdminOrderForm', '', $orderData);
		$response['order'] = $orderData;
		if(!empty($response['order']['user_email'])){
			$userInfoEmail = User::getUserInfoByEmail($response['order']['user_email'], 'login_email');
		}
		if (isset($response['order']['user_email']) && !empty($userInfoEmail)) {
			$userInfo = (array) User::getUserInfoByEmail($response['order']['user_email'], 'login_email');
			$userInfoEmail = ($userInfo['id']).'@'.$_SERVER['SERVER_NAME'];
			if ($response['order']['user_email'] == $userInfoEmail) {
                $response['order']['login_phone'] = $userInfo['login_phone'];
				$response['order']['login_email'] = $userInfo['login_email'];
				$response['order']['user_email'] = $userInfo['email'];
            }
		} else {
			$response['order']['login_phone'] = '';
			$response['order']['login_email'] = '';
			$response['order']['user_email'] = '';
		}
		$response['order']['date_delivery'] = !empty($orderData['date_delivery']) ? date('d.m.Y', strtotime($orderData['date_delivery'])) : '';
		$deliveryArray = Models_Order::getDeliveryMethod();

		foreach($deliveryArray as $delivery) {
			if(empty($delivery['plugin'])) {
				$delivery['plugin'] = '';
			}
			$response['deliveryArray'][] = $delivery;
		}

		$paymentArray = array();
		if (MG::isNewPayment()) {
			$payments = Models_Payment::getPayments();
			foreach($payments as &$payment) {
				if (!empty($payment['name']) && isset($payment['rate'])) {
					$payment['publicName'] .= mgGetPaymentRateTitle($payment['rate']);
					unset($payment['paramArray']);
					unset($payment['urlArray']);
				}
			}
			$paymentArray = $payments;
		} else {
			$i = 1;
			while ($payment = $model->getPaymentMethod($i)) {
				if (!empty($payment['name']) && isset($payment['rate'])) {
					$payment['name'] .= mgGetPaymentRateTitle($payment['rate']);
					unset($payment['paramArray']);
					unset($payment['urlArray']);
					$paymentArray[$i] = $payment;
				}
				$i++;
			}
		}

		//Сортировка
		function prioritet($a, $b) {
			if (!isset($a['sort']) || !isset($b['sort'])) {
				return false;
			}
			return $a['sort'] - $b['sort'];
		}

		//usort($paymentArray, 'prioritet');

		$response['paymentArray'] = $paymentArray;

		// опциональная полей загрузка
		$opFields = new Models_OpFieldsOrder(!empty($orderData['id'])?$orderData['id']:'add');
		$response['customFields'] = $opFields->createCustomFieldToAdmin();

		$storages = unserialize(stripcslashes(MG::getSetting('storages')));
		$change = false;

		$storageHtml = '';
		if (is_array($storages)) {
          foreach ($storages as $iteman) {
            if(isset($response['order']['storage']) && $response['order']['storage'] == $iteman['id']) {
              $response['order']['storage'] = $iteman['name'];
              $response['order']['storage_adress'] = $iteman['adress'];
            }
          }
        }
		// if(!$change) {
		//   $response['order']['storage'] = 'Не выбран';
		// }
		$response['order']['address_imploded'] = '';
		if (!empty($response['order']['address_parts'])) {
			$response['order']['address_parts'] = unserialize(stripcslashes($response['order']['address_parts']));
			$response['order']['address_imploded'] = array_filter($response['order']['address_parts']);
			foreach ($response['order']['address_imploded'] as $key => $value) {
				$response['order']['address_imploded'][$key] = htmlspecialchars_decode($value);
			}
			$response['order']['address_imploded'] = implode(', ', $response['order']['address_imploded']);
		}
		$response['order']['name_imploded'] = '';
		if (!empty($response['order']['name_parts'])) {
			$response['order']['name_parts'] = unserialize(stripcslashes($response['order']['name_parts']));
			$response['order']['name_imploded'] = array_filter($response['order']['name_parts']);
			foreach ($response['order']['name_imploded'] as $key => $value) {
				$response['order']['name_imploded'][$key] = htmlspecialchars_decode($value);
			}
			$response['order']['name_imploded'] = trim(implode(' ', $response['order']['name_imploded']));
		}

		$comments = $model->getOrderAdminComments($_POST['id']);

		$response['commentsBlock'] = MG::adminLayout('order-comments.php', array('comments' => $comments));
		$response['paymentAfterConfirm'] =  unserialize(stripcslashes(MG::getOption('propertyOrder')))['paymentAfterConfirm'];
		// MG::loger($response);
		$this->data = $response;
		return true;
	}

	public static function deleteAdminComment() {
		if(!USER::AccessOnly('1')) return false;
		return Models_Order::deleteAdminCommentOrder($_POST['id']);
	}

	/**
	 * Устанавливает флаг редактирования сайта.
	 * @return bool
	 */
	public function setSiteEdit() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$_SESSION['user']->enabledSiteEditor = $_POST['enabled'];
		return true;
	}

	/**
	 * Устанавливает флаги для отладки.
	 * @return bool
	 */

        public function setDebugVars() {
			if(USER::access('setting')<2){
				$this->messageError = $this->lang['ACCESS_EDIT'];
				return false;
			}
            Storage::clear();
            $_SESSION['debugDisablePlugin'] = $_POST['data']['debugDisablePlugin'];
            $_SESSION['debugDisablePluginName'] = $_POST['data']['debugDisablePluginName'];
            $_SESSION['debugDisableTemplate'] = $_POST['data']['debugDisableTemplate'];
            $_SESSION['debugDisableUserCss'] = $_POST['data']['debugDisableUserCss'];
            $_SESSION['debugLogSQL'] = $_POST['data']['debugLogSQL'];
            if($_POST['data']['debugLogger'] == 'true'){
              MG::setOption('logger','true'); 
              $res = LoggerAction::logAction('Test', 'test', 'test');
              if($res == false){
                $this->data = 'false_log';
                MG::setOption('logger','false');
              }
            } else {
                MG::setOption('logger','false');
            }
            return true;
        }

        /**
	 * Очишает таблицу с кэшем объектов.
	 * @return bool
	 */
	public function clearСache() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		Storage::clear();
		return true;
	}

        /**
	 * Завершает все сессии пользователей.
	 * @return bool
	 */
	public function clearSessionActive() {
		if(USER::access('setting')<2){
			return false;
		}
		ini_set('session.gc_max_lifetime', 0);
		ini_set('session.gc_probability', 1);
		ini_set('session.gc_divisor', 1);
		$sess_path = session_save_path();
		if ($handle = opendir($sess_path)) {
			while (false !== ($entry = readdir($handle))) {
				$file = $sess_path.DS.$entry;
				if(is_file($file)){
					unlink($file);
				}
			}
			closedir($handle);
		}
		DB::query('TRUNCATE `'.PREFIX.'user_logins`');
		DB::query('TRUNCATE `'.PREFIX.'sessions`');
		return true;
	}

	/**
	 * Удаляет папку с собранными картинками для минифицированного css.
	 * @return bool
	 */
	public function clearImageCssСache() {
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::clearMergeStaticFile(PATH_TEMPLATE.'/cache/');
		MG::createImagesForStaticFile();
		MG::createFontsForStaticFile();
		return true;
	}

	/**
	 * Выбор CSV файла на сервере
	 * @return bool
	 */
	public function selectCSV() {
		if (
			USER::access('product') < 2 &&
			USER::access('category') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$_FILES['upload'] = array(
			'name' => $_POST['name'],
			'size' => 1024,
			'tmp_name' => SITE_DIR.$_POST['file'],
		);
		return $this->uploadCsvToImport();
	}

	/**
	 * Загружает CSV файл
	 * @return bool
	 */
	public function uploadCsvToImport() {
		if (
			USER::access('product') < 2 &&
			USER::access('category') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$uploader = new Upload(false);
		$tempData = $uploader->addImportCatalogCSV();

		$tempData['actualImageName'] = !empty($tempData['actualImageName']) ? $tempData['actualImageName'] : '';
		$this->data = array('img' => $tempData['actualImageName']);

		if ($tempData['status'] == 'success') {
			$this->messageSucces = $this->lang['FILTER_UPLOADED'];
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Импортирует структуру категорий из CSV файла.
	 * @return bool
	 */
	public function startImportCategory() {

		//Отключение объединения во время импорта категорий
		$useAbsolutePath = MG::getSetting('useAbsolutePath');
		$cacheCssJs = MG::getSetting('cacheCssJs');
		if($useAbsolutePath == 'true'){
			MG::setOption('useAbsolutePath', 'false');
		}
		if($cacheCssJs == 'true'){
			MG::setOption('cacheCssJs', 'false');
		}

		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['PROCESS_START'];
		$this->messageError = $this->lang['IMPORT_START_FAIL'];

		unset($_SESSION['import']);

		$import = new Import("Category");

		if (empty($_POST['rowId'])) {
			unset($_SESSION['stopProcessImportCsv']);
		}

		if ($_POST['delCatalog'] !== null) {
			if ($_POST['delCatalog'] === "true") {
				DB::query('TRUNCATE TABLE `'.PREFIX.'cache`');

				if ($_POST['rowId'] == 0) {
					DB::query('TRUNCATE TABLE `'.PREFIX.'category`');
          MG::rrmdir(URL::getDocumentRoot() . DS . 'uploads' . DS . 'category');
				}
			}
		}

		$this->data = $import->startCategoryUpload($_POST['rowId']);

		MG::setOption('useAbsolutePath', $useAbsolutePath);
		MG::setOption('cacheCssJs', $cacheCssJs);

		unset($_SESSION['import']);

		if($this->data['status']=='error') {
			$this->messageError = $this->data['msg'].'';
			return false;
		}

		return true;
	}

	/**
	 * Импортирует данные из файла importCatalog.csv.
	 * @return bool
	 */
	public function startImport() {
		
		//Отключение объединения во время импорта каталога
		$useAbsolutePath = MG::getSetting('useAbsolutePath');
		$cacheCssJs = MG::getSetting('cacheCssJs');
		if($useAbsolutePath == 'true'){
			MG::setOption('useAbsolutePath', 'false');
		}
		if($cacheCssJs == 'true'){
			MG::setOption('cacheCssJs', 'false');
		}
		
		//логирование испорта 
        $data['id'] = '';
        $data['import'] = 'Excel/CSV';
        LoggerAction::logAction('Import', __FUNCTION__, $data);
		
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_IMPORT'];
			return false;
		}
		$this->messageSucces = $this->lang['PROCESS_START'];
		$this->messageError = $this->lang['IMPORT_START_FAIL'];

		// удаляем временный массив данных (в теории он как то больше отрабатывать должен) // TODO
		unset($_SESSION['import']);

		$import = new Import($_POST['typeCatalog']);
		if(empty($_POST['rowId'])) {
			$import->log('', true);
			$_SESSION['startImportTime'] = microtime(true);
			@unlink('uploads/tmp.csv');
		}
		$_SESSION['iterationImportTime'] = microtime(true);
		$_SESSION['iterationStartRow'] = $_POST['rowId'];

		if (empty($_POST['rowId'])) {
			unset($_SESSION['stopProcessImportCsv']);
			unset($_SESSION['import_csv']['uploadedImages']);
		}

		if ($_POST['delCatalog'] !== null) {
			if ($_POST['delCatalog'] === "true") {
				DB::query('TRUNCATE TABLE `'.PREFIX.'cache`');
				if ($_POST['rowId'] == 0) {
					DB::query('TRUNCATE TABLE `'.PREFIX.'product_variant`');
					DB::query('TRUNCATE TABLE `'.PREFIX.'product`');
					// DB::query('TRUNCATE TABLE `'.PREFIX.'product_user_property`');
					DB::query('TRUNCATE TABLE `'.PREFIX.'product_user_property_data`');
					DB::query('TRUNCATE TABLE `'.PREFIX.'property`');
					DB::query('TRUNCATE TABLE `'.PREFIX.'property_data`');
					DB::query('TRUNCATE TABLE `'.PREFIX.'property_group`');
					DB::query('TRUNCATE TABLE `'.PREFIX.'category`');
					DB::query('TRUNCATE TABLE `'.PREFIX.'category_user_property`');

					$productModel = new Models_Product();
					$productModel->clearStoragesTable();
					DB::query('TRUNCATE TABLE `'.PREFIX.'wholesales_sys`');
					DB::query('DELETE FROM `'.PREFIX.'locales` WHERE `table` IN 
						(\'product_variant\',\'product\',\'product_user_property_data\',\'property\',
						\'property_data\',\'category\',\'property_group\')');
					MG::setOption('storages', array(), 1);
				}
			}
		}

		// удаляем картинки
		if ($_POST['delImages'] !== null) {
			if ($_POST['delImages'] === "true") {
				if ($_POST['rowId'] == 0) {
					MG::rrmdir(SITE_DIR.'uploads/product');
					// MG::rrmdir(SITE_DIR.'uploads/thumbs');
					MG::rrmdir(SITE_DIR.'uploads/webp/product');
					MG::rrmdir(SITE_DIR.'uploads/webp/prodtmimg');
				}
			}
		}

		$this->data = $import->startUpload($_POST['rowId'], $_POST['schemeType'], $_POST['downloadLink'], $_POST['iteration']);

		MG::setOption('useAbsolutePath', $useAbsolutePath);
		MG::setOption('cacheCssJs', $cacheCssJs);

		if($this->data['status']=='error') {
			$this->messageError = $this->data['msg'].'';
			return false;
		}

		return true;
	}

	/**
	 * Останавливает процесс импорта каталога из файла importCatalog.csv.
	 * @return bool
	 */
	public function canselImport() {
		if (
			USER::access('product') < 2 &&
			USER::access('category') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['IMPORT_CANCEL'];
		$this->messageError = $this->lang['IMPORT_CANCEL_FAIL'];

		$import = new Import();
		$import->stopProcess();

		return true;
	}

	/**
	 * Сохраняет реквизиты в настройках заказа.
	 * @return bool
	 */
	public function savePropertyOrder() {
		if(USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SAVE_SETNG'];
		$this->messageError = $this->lang['SAVE_FAIL'];
		if ($_POST['tempRequisites'] == 'unset') {
			unset($_SESSION['tempRequisites']);
		} else {
			$_SESSION['tempRequisites'] = $_POST['tempRequisites'];
		}
		unset($_POST['tempRequisites']);
		$propertyOrder = serialize($_POST);
		$propertyOrder = addslashes($propertyOrder);
		MG::setOption(array('option' => 'propertyOrder', 'value' => $propertyOrder));

		return true;
	}

	/**
	 * Получает данные об ошибке произошедшей в админке и отправляет на support@moguta.ru.
	 * 
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 * 
	 * @return bool
	 */
	public function sendBugReport() {
		$this->messageSucces = $this->lang['ADMIN_LOCALE_2'];
		$this->messageError = $this->lang['REPORT_SEND_FAIL'];

		$body = 'Непредвиденная ошибка на сайте '.$_SERVER['SERVER_NAME'];
		$body .= '<br/><br/><br/><strong>Информация о системе</strong>';
		$body .= '<br/>Версия Moguta.CMS: '.VER;
		$body .= '<br/>Версия php: '.phpversion();
		$body .= '<br/>USER_AGENT: '.$_SERVER['HTTP_USER_AGENT'];
		$body .= '<br/>IP: '.$_SERVER['SERVER_ADDR'];

		$body .= '<br/><strong>Информация о магазине</strong>';
		$product = new Models_Product;
		$body .= '<br/>Количество товаров: '.$product->getProductsCount();
		$body .= '<br/>Количество категорий: '.MG::get('category')->getCategoryCount();
		$body .= '<br/>Шаблон: '.MG::getSetting('templateName');
		$body .= '<br/>E-mail администратора: '.MG::getSetting('adminEmail');

		$body .= '<br/><strong>Баг-репорт</strong>';
		$body .= '<br/>'.$_POST['text'];
		$body .= '<br/><br/><img alt="Embedded Image" src="data:'.$_POST['screen'].'" />';
		Mailer::addHeaders(array("Reply-to" => MG::getSetting('adminEmail')));
		Mailer::sendMimeMail(array(
			'nameFrom' => MG::getSetting('adminEmail'),
			'emailFrom' => MG::getSetting('adminEmail'),
			'nameTo' => "support@moguta.ru",
			'emailTo' => "support@moguta.ru",
			'subject' => "Отчет об ошибке с сайта ".$_SERVER['SERVER_NAME'],
			'body' => $body,
			'html' => true
		));

		return true;
	}

	/**
	 * Устанавливает тестовое соединение с сервером Memcache.
	 * @return bool
	 */
	public function testMemcacheConection() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if(class_exists('Memcached')) {
			$memcache_obj = new Memcached();
			$memcache_obj->addServer($_POST['host'], $_POST['port']);
			$ver = $memcache_obj->getVersion();
			if (!empty($ver)) {
				$this->messageSucces = $this->lang['MEMCACHE_CONNECT_SUCCESS'].' '.$ver[$_POST['host'].":".$_POST['port']];
				return true;
			}
			$this->messageError = $this->lang['MEMCACHE_CONNECT_FAIL'].' '.$_POST['host'].":".$_POST['port'];
			return false;
		}
		if (class_exists('Memcache')) {
			$memcacheObj = new Memcache();
			$memcacheObj->connect($_POST['host'], $_POST['port']);
			$ver = $memcacheObj->getVersion();
			if (!empty($ver)) {
				$this->messageSucces = $this->lang['MEMCACHE_CONNECT_SUCCESS'].' '.$ver;
				return true;
			}
			$this->messageError = $this->lang['MEMCACHE_CONNECT_FAIL'].' '.$_POST['host'].":".$_POST['port'];
			return false;
		}
		$this->messageError = $this->lang['MEMCACHE_MISSING'];
		return false;
	}

	/**
	 * Упорядочивает всё дерево категорий по алфавиту.
	 * @return bool
	 */
	public function sortToAlphabet() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_CREATE_PRODUCT'];
			return false;
		}
		MG::get('category')->sortToAlphabet();
		return true;
	}

	/**
	 * Выполняет операцию над отмеченными пользователями в админке.
	 * @return bool
	 */
	public function operationUser() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$operation = $_POST['operation'];
		if (empty($_POST['users_id']) && $operation != 'fulldelete') {
			$this->messageError = $this->lang['CHECK_USER'];
			return false;
		}
		$result = false;
		if ($operation == 'delete') {
			foreach ($_POST['users_id'] as $userId) {
				$del = USER::delete($userId);
				if (!$del) {
					$this->messageSucces = $this->lang['USER_DELETED_NOT_ADMIN'];
				}
				$result = true;
			}
		} elseif (strpos($operation, 'getcsvuser') === 0 && !empty($_POST['users_id'])) {
				$filepath = USER::exportToCsvUser($_POST['users_id']);
				$filenameArray = explode('/',$filepath);
				$filename = end($filenameArray);
				$this->data['filecsv'] = $filename;
				$this->data['filecsvpath'] = $filepath;
				$this->messageSucces = $this->lang['USER_IMPORT_SUCCESS'].' '.$filename;
				$result = true;
		} elseif (strpos($operation, 'fulldelete') === 0) {
				DB::query('DELETE FROM `'.PREFIX.'user` WHERE `role` > 1');
				$result = true;
		}
		elseif (strpos($operation, 'changeowner') === 0) {
			DB::query('UPDATE '.PREFIX.'user SET owner = '.DB::quoteInt($_POST['param']).' WHERE id IN ('.DB::quoteIN($_POST['users_id']).')');
			$result = true;
		}
		return $result;
	}
	/**
	 * Получает следующий id для таблицы продуктов.
	 * @return bool
	 */
	public function nextIdProduct() {
		$result['id'] = 0;
		if(USER::access('product') < 2) return false;
		$res = DB::query('SHOW TABLE STATUS WHERE Name =  "'.PREFIX.'product" ');
		if ($row = DB::fetchArray($res)) {
			$result['id'] = $row['Auto_increment'];
		}
		$result['prefix_code'] = 'CN';
		if (MG::getSetting('prefixCode')) {$result['prefix_code'] = MG::getSetting('prefixCode');}
		$this->data = $result;
		return true;
	}

	/**
	 * Добавляет новый favicon.
	 * @return bool
	 */
	public function updateFavicon() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$uploader = new Upload(false);
		$tempData = $uploader->addFavicon();
		$this->data = array('img' => $tempData['actualImageName']);

		if ($tempData['status'] == 'success') {
			$this->messageSucces = $tempData['msg'];
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Изменяет логотип в панели управления.
	 * @return bool
	 */
	public function updateCustomAdmin() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$uploader = new Upload(false);
		$tempData = $uploader->addImage(false, false);

		if (!empty($tempData['actualImageName'])) {
      		$this->data = array('img' => $tempData['actualImageName']);
    	}

		if ($tempData['status'] == 'success') {
			$this->messageSucces = $tempData['msg'];
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Функция для получения необходимых настроек из js скриптов.
	 * @param $options имя, или массив имен опций.
	 * @return bool
	 */
	public function getSetting() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$setting = $_POST['setting'];
		$this->data = array($setting => MG::getSetting($setting));
		return true;
	}

	/**
	 * Сохраняет настройки страницы с применными фильтрами.
	 * @return bool
	 */
	public function saveRewrite() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['url']) || empty($_POST['short_url'])) {
			return false;
		}

		$this->data = Urlrewrite::setUrlRewrite($_POST);
		return true;
	}

	/**
	 * Возвращает запись о странице с применеными фильтрами.
	 * @return bool
	 */
	public function getRewriteData() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['id'])) {
			$this->messageError = $this->lang['NONE_ID'];
			return false;
		}

		$this->data = Urlrewrite::getUrlRewriteData($_POST['id']);
		return true;
	}

	/**
	 * Меняет настройки активности страницы с применными фильтрами.
	 * @return bool
	 */
	public function setRewriteActivity() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['id'])) {
			$this->messageError = $this->lang['NONE_ID'];
			return false;
		}

		if(!isset($_POST['activity'])) {
			$this->messageError = $this->lang['NONE_ACTIVE_VALUE'];
			return false;
		}

		if(Urlrewrite::setActivity($_POST['id'], $_POST['activity'])) {
			return true;
		}

		return false;
	}

	/**
	 * Удаляет страницу с примененными фильтрами.
	 * @return bool
	 */
	public function deleteRewrite() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['id'])) {
			$this->messageError = $this->lang['NONE_ID'];
			return false;
		}

		if(Urlrewrite::deleteRewrite($_POST['id'])) {
			return true;
		}

		return false;
	}

	/**
   * Возвращает список найденых продуктов по ключевому слову.
   * @return bool
   */
  public function searchUrlRewrite() {
	if (USER::access('setting') < 1) {
		$this->messageError = $this->lang['ACCESS_VIEW'];
		return false;
	}
    $this->messageSucces = 'Поиск завершен';
    $searchArr = array();
    if (!empty($_POST['searchSeoUrl'])) {
      $sql = DB::query("SELECT id, titeCategory, url, short_url, activity FROM `".PREFIX."url_rewrite` WHERE titeCategory LIKE ".DB::quote('%'.$_POST['searchSeoUrl'].'%'));
      while ($res = DB::fetchAssoc($sql)) {
        $searchArr[] = $res;
      }
    }

    $tableRows = '';
    foreach ($searchArr as $row) {
      $tableRows .= "<tr class='rewrite-line' id='".$row['id']."'><td>".$row['titeCategory']."</td>
<td>".SITE.'/'.$row['short_url']."<a class='link-to-site tool-tip-bottom' target='_blank' href='".SITE.'/'.$row['short_url']."' title='".$this->lang['T_TIP_STNG_SEO_URL_REWRITE_GO']."'>
    <img alt='' src='".SITE."/mg-admin/design/images/icons/link.png'></a>
</td>
<td style='word-wrap: break-word;'>
  <span class='show-long-url'>".$this->lang['linkShow']."</span>
  <span style='display: none;' class='url-long'>".SITE.$row['url']."</span>
</td>
<td class='actions'>
  <ul class='action-list text-right'>
      <li class='edit-row' id='".$row['id']."'><a class='tool-tip-bottom fa fa-pencil' href='javascript:void(0);' title='".$this->lang['EDIT']."'></a></li>
      <li class='visible tool-tip-bottom' data-id='".$row['id']."' title='' ><a role='button' href='javascript:void(0);' class='fa fa-eye ".($row['activity'] ? 'active' : '')."'></a></li>
      <li class='delete-row' id='".$row['id']."'><a class='tool-tip-bottom fa fa-trash' href='javascript:void(0);'  title=''></a></li>
  </ul>
</td>
</tr>";
    }
    $this->data = $tableRows;
    return true;
  }

	/**
	 * Добавляет новую запись перенаправления.
	 */
	public function addUrlRedirect() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['STNG_SEO_URL_REDIRECT_ADD_SUCCESS'];
		$res = DB::query("
			INSERT INTO `".PREFIX."url_redirect`
			VALUES ('','','','',1)"
		);

		if($id = DB::insertId()) {
			$this->data = array(
				'id' => $id,
				'url_old' => '',
				'url_new' => '',
				'code' => '',
			);
		} else {
			$this->messageError = $this->lang['STNG_SEO_URL_REDIRECT_ADD_FAIL'];
			return false;
		}

		return true;
	}

	/**
	 * Добавляет новую запись Canonical.
	 */
	public function addUrlCanonical() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['STNG_SEO_URL_CANONICAL_ADD_SUCCESS'];
		$res = DB::query("
			INSERT INTO `".PREFIX."url_canonical`
			VALUES ('','','',1)"
		);

		if($id = DB::insertId()) {
			$this->data = array(
				'id' => $id,
				'url_page' => '',
				'url_canonical' => '',
			);
		} else {
			$this->messageError = $this->lang['STNG_SEO_URL_CANONICAL_ADD_FAIL'];
			return false;
		}

		return true;
	}

	/**
	 * Сохраняет запись Canonical.
	 */
	public function saveUrlCanonical() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_EDIT_CANONICAL'];
		$result = false;
		$id = $_POST['id'];
		$array = $_POST;
		$array['url_page'] = str_replace(SITE,'',$array['url_page']);
		$array['url_canonical'] = str_replace(SITE,'',$array['url_canonical']);

		if (!empty($id)) {
			if (DB::query('
				UPDATE `'.PREFIX.'url_canonical`
				SET '.DB::buildPartQuery($array).'
				WHERE id ='.DB::quoteInt($id))) {
				$result = true;
			}
		}
		return $result;
	}
	/**
	 * Удаление Canonical
	 * @return bool
	 */
	public function deleteUrlCanonical() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['id'])) {
			$this->messageError = $this->lang['NONE_ID'];
			return false;
		}

		if (DB::query('
			DELETE 
			FROM `'.PREFIX.'url_canonical`      
			WHERE id ='.DB::quoteInt($_POST['id'], 1))) {
			return true;
		}

		return false;
	}

	/**
	 * Изменяем активность записи перенаправления.
	 * @return bool
	 */
	public function setUrlCanonicalActivity() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['id'])) {
			$this->messageError = $this->lang['NONE_ID'];
			return false;
		}

		if(!isset($_POST['activity'])) {
			$this->messageError = $this->lang['NONE_ACTIVE_VALUE'];
			return false;
		}

		if (DB::query('
			UPDATE `'.PREFIX.'url_canonical`
			SET `activity` = '.DB::quoteInt($_POST['activity'], 1).'
			WHERE id ='.DB::quoteInt($_POST['id'], 1))) {
			return true;
		}

		return false;
	}


	/**
	 * Сохраняет запись перенаправления.
	 */
	public function saveUrlRedirect() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_EDIT_REDIRECT'];
		$result = false;
		$id = $_POST['id'];
		$array = $_POST;
		$array['url_new'] = URL::prepareUrl(htmlspecialchars($array['url_new']), false, false);

		if (!empty($id)) {
			if (DB::query('
				UPDATE `'.PREFIX.'url_redirect`
				SET '.DB::buildPartQuery($array).'
				WHERE id ='.DB::quote($id))) {
				$result = true;
			}
		}
		return $result;
	}
	/**
	 * Изменяем активность записи перенаправления.
	 * @return bool
	 */
	public function setUrlRedirectActivity() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['id'])) {
			$this->messageError = $this->lang['NONE_ID'];
			return false;
		}

		if(!isset($_POST['activity'])) {
			$this->messageError = $this->lang['NONE_ACTIVE_VALUE'];
			return false;
		}

		if (DB::query('
			UPDATE `'.PREFIX.'url_redirect`
			SET `activity` = '.DB::quoteInt($_POST['activity'], 1).'
			WHERE id ='.DB::quoteInt($_POST['id'], 1))) {
			return true;
		}

		return false;
	}

	/**
	 * Меняет настройки активности переадресаций.
	 * @return bool
	 */
	public function deleteUrlRedirect() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SUCCESS'];
		$this->messageError = $this->lang['ACT_ERROR'];

		if(empty($_POST['id'])) {
			$this->messageError = $this->lang['NONE_ID'];
			return false;
		}

		if (DB::query('
			DELETE 
			FROM `'.PREFIX.'url_redirect`      
			WHERE id ='.DB::quoteInt($_POST['id'], 1))) {
			return true;
		}

		return false;
	}
	/**
	 * Создает в корневой папке сайта карту в формате XML.
	 */
	public function generateSitemap() {
		if(USER::access('setting') < 2) {
			return false;
		}
		$this->messageSucces = $this->lang['SITEMAP_CREATED'];
		$this->messageError = $this->lang['SITEMAP_NOT_CREATED'];
		$urls = Seo::autoGenerateSitemap();
		if ($urls) {
			$msg = $this->lang['MSG_SITEMAP1']." ".MG::dateConvert(date("d.m.Y"), true).'. '.$this->lang['SITEMAP_COUNT_URL'].' '.$urls;
			$this->data = array('msg' => $msg);
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Функция для загрузки архива с изображениями.
	 */
	public function uploadImagesArchive() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$tempData = Upload::addImagesArchive();
        $actualImageName = isset($tempData['actualImageName'])?$tempData['actualImageName']:'';
		$this->data = array('file' => $actualImageName);

		if ($tempData['status'] == 'success') {
			$this->messageSucces = $tempData['msg'];
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Загружает архив изображений для каталога.
	 * @return bool
	 */
	public function selectImagesArchive() {
		if (
			USER::access('product') < 2 &&
			USER::access('category') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$tempData = Upload::addImagesArchive($_POST['data']['filename']);

		if ($tempData['status'] == 'success') {
			$this->messageSucces = $tempData['msg'];
			return true;
		} else {
			$this->messageError = $tempData['msg'];
			return false;
		}
	}

	/**
	 * Удаляет миниатюры товаров
	 */
	public function destroyThumbs() {
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		$nextItem = 0;
		$totalCount = 0;

		if (!empty($_POST['nextItem'])) {
			$nextItem = $_POST['nextItem'];
		}
		if (!empty($_POST['totalCount'])) {
			$totalCount = $_POST['totalCount'];
		}

		$upload = new Upload(false);
		if ($uploadResult = $upload->destroyThumbs($nextItem, $totalCount)) {
			$this->messageSucces = $uploadResult['messageSucces'];
			$this->data = $uploadResult['data'];
		} else {
			$this->messageError = 'Error!';
		}

		return true;
	}

	/**
	 * Запускает процесс генерации изображений для товаров.
	 * @return bool
	 */
	public function startGenerationImagePreview() {
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$upload = new Upload(false);

		if($uploadResult = $upload->generatePreviewPhoto( (!empty($_POST['productFolder'])?true:false) )) {
			$this->messageSucces = $uploadResult['messageSucces'];
			$this->data = $uploadResult['data'];
		} else {
			$this->messageError = "Error!";
		}

		return true;
	}
	/**
	 * Удаление папки с webp
	 * @return bool
	 */
	 public function deleteWebpDir(){
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::rrmdir(SITE_DIR.'uploads'.DS.'webp');
		if(is_dir(SITE_DIR.'uploads'.DS.'webp')){
			return false;
		}else{
			return true;
		}
	 }

	/**
   * Запускает процесс генерации изображений для товаров.
   * @return bool
   */
  public function startGenerationImageCategory() {
	if (USER::access('category') < 2) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}
    $upload = new Upload(false);

    if($uploadResult = $upload->generatePreviewPhotoCategories()) {
      $this->messageSucces = $uploadResult['messageSucces'];
      $this->data = $uploadResult['data'];
    } else {
      $this->messageError = "Error!";
    }

    return true;
  }
   /**
   * Запускает процесс подсчета изображений в WEBP.
   * @return bool
   */
  public function startGenerationImageWebp() {
	if (USER::access('setting') < 2) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}
	
	if(!function_exists('imagewebp')){
		$this->messageError = "Ваша версия PHP не поддерживает работу с изображениями 'WEBP'!";
		$this->data = [
			'error' => 'imageweb',
			'log' => "Ваша версия PHP не поддерживает работу с изображениями 'WEBP'! Получена PHP ошибка вида:\n\nFatal error: Call to undefined function imagewebp()\n\nДля устранения ошибки, обновите конфигурацию модуля GD в вашем PHP или обратитесь в техническую поддержку вашего хостинга.\n"
		];
		return false;
	} 
	$_SESSION['WEBP']['SKIP_COUNT'] = 1;
	if (!empty($_POST['reset'])) {
		$_SESSION['WEBP'] = [
			'TOTAL' => 0,
		];
	}

    // здесь можно настроить список директорий, изображения из которых будут конвертированы в webp
	// если не передан другой список
	$objects = [
		'uploads',
		'mg-templates'.DS.MG::getSetting('templateName'),
	];
	if (!empty($_POST['objects'])) {
		$objects = $_POST['objects'];
	}

	$result = Webp::getCountImg($objects);
	if (!is_bool($result)) {
		if (!$result) {
			return false;
		}
		$this->data = [
			'total' => $result,
		];
	}

	$webpLogFile = SITE_DIR.'uploads'.DS.'temp'.DS.'lastWebpImage';
	if (is_file($webpLogFile)) {
		$errorImage = trim(file_get_contents($webpLogFile));
		$errorImagePath = $errorImage;
		$errorImageSizeData = getimagesize($errorImagePath);
		$errorImageFilesize = filesize($errorImagePath);
		
		if (!$errorImageFilesize) {
			$errorImageFilesize = '';
		} else {
			$units = ['Б', 'КБ', 'МБ', 'ГБ'];
			$iteration = 0;
			if ($errorImageFilesize >= 1024) {
				do {
					$errorImageFilesize  /= 1024;
					$iteration++;
				} while (floor($errorImageFilesize) >= 1024);
			}
			$errorImageFilesize = round($errorImageFilesize, 2).' '.$units[$iteration];
		}
		$this->data['errorImage'] = $errorImage.
			' ('.$errorImageSizeData[0].'x'.$errorImageSizeData[1].'px) '.$errorImageFilesize;
		unlink($webpLogFile);
	}
	return true;	
  }

  public function convertImgToWebp() {
	if (USER::access('setting') < 2) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}

	if (!function_exists('imagewebp')) {
		$this->messageError = 'Ваша версия PHP не поддерживает работу с изображениями "WebP". Отсутствует функция imagewebp().';
		return false;
	}

	$_SESSION['WEBP']['SKIP'] = true;
	$_SESSION['WEBP']['LOG'] = '';
	if (!empty($_POST['reset']) && $_POST['reset'] === 'true') {
		$tempPath = SITE_DIR.'uploads'.DS.'temp';
		if (!is_dir($tempPath)) {
		  mkdir($tempPath, 0755, true);
		}
		$_SESSION['WEBP']['SKIP'] = false;
		$_SESSION['WEBP']['COMPLETE'] = 0;
		unset($_SESSION['WEBP']['LAST_DIR']);
		unset($_SESSION['WEBP']['LAST_FILE']);
	}

    // здесь можно настроить список директорий, изображения из которых будут конвертированы в webp
	// если не передан другой список
	$objects = [
		'uploads',
		'mg-templates'.DS.MG::getSetting('templateName'),
	];
	if (!empty($_POST['objects'])) {
		$objects = $_POST['objects'];
	}

	$result = Webp::convertImgToWebp($objects);
	if (is_bool($result)) {
		if (!$result) {
			return false;
		}
		$complete = $_SESSION['WEBP']['COMPLETE'];
		$total = $_SESSION['WEBP']['TOTAL'];
		$proportion = $complete / $total;
		$percent = floor($proportion * 100);
		$_SESSION['WEBP']['LOG'] .= 'Переведено '.$percent.'% изображений.'."\n";
		$this->data = [
			'percent' => $percent,
			'log' => $_SESSION['WEBP']['LOG'],
		];
		return true;
	}
	return true;
  }
	/**
	 * Создает в mg-pages файл getyml по обращению к которому происходит выгрузка каталога в yml формате.
	 * 
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 * 
	 * @return bool
	 */
	public function createYmlLink() {
		if (USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['YML_LINK_CREATE_SUCCESSFUL'];
		$this->messageError = $this->lang['YML_LINK_CREATE_ERROR'];
		$name = MG::getSetting('nameOfLinkyml') ? MG::getSetting('nameOfLinkyml') : 'getyml';
		if (!file_exists(PAGE_DIR.$name.'.php')) {
			$code = "<?php \$yml= new YML(); header(\"Content-type: text/xml; \");echo  \$yml->exportToYml(array(),true); ?>";
			$f = fopen(PAGE_DIR.$name.'.php', 'w');
			$result = fwrite($f, $code);
			fclose($f);
			if ($result) {
				$this->data = SITE.'/'.$name;
				return true;
			}
		} else {
			$this->data = SITE.'/'.$name;
			return true;
		}
		return false;
	}

	/**
	 * Сохраняет новое имя для файла с выгрузкой yml.
	 * @return bool
	 */
	public function renameYmlLink() {
		if (USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['YML_LINK_RENAME_SUCCESSFUL'];
		$this->messageError = $this->lang['YML_LINK_RENAME_ERROR'];
		$oldname = MG::getSetting('nameOfLinkyml') ? MG::getSetting('nameOfLinkyml') : 'getyml';
		$newname = !empty($_POST['name']) ? $_POST['name']: 'getyml';
		if (preg_match('/[^0-9a-zA-Z]/', $newname)) {
			$this->messageError = $this->lang['YML_LINK_NAME_ERROR'];
			return false;
		}
		if (rename(PAGE_DIR.$oldname.'.php', PAGE_DIR.$newname.'.php')) {
			MG::setOption('nameOfLinkyml', $newname);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Получает список адресов покупателей.
	 * @return bool
	 */
	public function getBuyerEmail() {
		if (USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$result = array();
		preg_match("/[a-zA-Z]/", $_POST['email'], $matches);
    if(empty($matches)){
		  $phone = preg_replace("/[^0-9]/", '', $_POST['email']); // Удаляем все кроме цифр для поиска по телефону
		}
		$sql = 'SELECT `login_email`';
		$sql .= ',`login_phone`';
		$sql .= ' FROM `'.PREFIX.'user` WHERE ';
		if(!empty($phone)){
			$sql .= '`login_phone` LIKE "%'.DB::quote($phone, true).'%" OR ';
		}
		$sql .= '`login_email` LIKE "%'.DB::quote($_POST['email'], true).'%"';
		$res = DB::query($sql);
		while ($row = DB::fetchArray($res)) {
			if(!empty($row['login_phone'])){
				$result[] = $row['login_phone'];	
			} else {
				$result[] = $row['login_email'];
			}
		}
		$this->data = $result;
		return true;
	}

	/**
	 * Получает информацию по email покупателя.
	 * @return bool
	 */
	public function getInfoBuyerEmail() {
		if (USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$result = array();
		$res = DB::query('SELECT * FROM `'.PREFIX.'user` WHERE `login_email` ='.DB::quote($_POST['email']));
		if(!preg_match("/@/",$_POST['email'])){
			$res = DB::query('SELECT * FROM `'.PREFIX.'user` WHERE `login_phone` ='.DB::quote($_POST['email']));
		}
		if ($row = DB::fetchArray($res)) {
			$result = $row;
		}
		$this->data = $result;
		return true;
	}

	/**
	 * Тестовая отправка письма администратору.
	 * @return bool
	 */
	public function testEmailSend() {
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['SEND_EMAIL_TEST_SUCCESSFUL'].' '.MG::getSetting('adminEmail');
		$this->messageError = $this->lang['SEND_EMAIL_TEST_ERROR'];
		$result = true;
		$sitename = MG::getSetting('sitename');
		$mails = explode(',', MG::getSetting('adminEmail'));
		if (empty($mails)) {
			$result = false;
		}
		$message = '
				Здравствуйте!<br>
					Вы получили данное письмо при тестировании отправки почты с сайта '.$sitename.'.<br>
						Если вы получили данное письмо, значит почта на сайте настроена корректно.
					Отвечать на данное сообщение не нужно.';

		foreach ($mails as $mail) {
			if (MG::checkEmail($mail)) {
				$res = Mailer::sendMimeMail(array(
					'nameFrom' => $sitename,
					'emailFrom' => MG::getSetting('noReplyEmail'),
					'nameTo' => "Администратору ".$sitename,
					'emailTo' => $mail,
					'subject' => 'Тестирование почты на сайте '.$sitename,
					'body' => $message,
					'html' => true
				));
				if (!$res) {
					$result = false;
				}
			}
			else{
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Информация о сопутствующих  категориях.
	 * @return bool
	 */
	public function getRelatedCategory() {
		$data = array();
		//$cats = implode(',', $_POST['cat']);
		$res =  DB::query('SELECT `id`, `title`, `url`, `parent_url`, `image_url` FROM `'.PREFIX.'category` WHERE `id` IN ('.DB::quoteIN($_POST['cat']).')');
		while ($row = DB::fetchArray($res)) {
			$data[] = $row;
		}
		return $this->data = $data;
	}
	/**
	 * Функция для AJAX запроса генерации SEO тегов по шаблонам,
	 * при заполнении карточки сущности(товар/категория/страница).
	 * @return bool
	 */
	public function generateSeoFromTmpl() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 2 &&
			USER::access('page') < 2 &&
			USER::access('product') < 2 &&
			USER::access('category') < 2 &&
			USER::access('setting') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
		}
		$this->messageSucces = $this->lang['SEO_GEN_TMPL_SUCCESS'];
		$this->messageError = $this->lang['SEO_GEN_TMPL_FAIL'];
		$data = $_POST['data'];

		if (!empty($data['userProperty'])) {
			// foreach ($data['userProperty'] as $key => $value) {
			//   if (intval($key) > 0) {
			//     $propIds[] = $key;
			//   }
			// }

			// $dbRes = DB::query("
			//         SELECT `prop_id`, `name`
			//         FROM `".PREFIX."product_user_property_data`
			//         WHERE `prop_id` IN (".  implode(",", $propIds).")"
			//         );

			// while ($arRes = DB::fetchAssoc($dbRes)) {
			//   $data['stringsProperties'][$arRes['prop_id']] = $arRes['name'];
			// }
			foreach ($data['userProperty'] as $key => $prop) {
				foreach ($prop as $keyIn => $value) {
					if(is_array($value)) {
						$data['stringsProperties'][$key] = $value['val'];
					}
				}
			}
			unset($data['userProperty']);
		}

		// viewData($data);

		$seoData = Seo::getMetaByTemplate($_POST['type'], $data);
		$this->data = $seoData;

		return true;
	}

	/**
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 */
	public function getSessionLifeTime() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 2 &&
			USER::access('page') < 2 &&
			USER::access('product') < 2 &&
			USER::access('category') < 2 &&
			USER::access('setting') < 2
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
		}
		$sessionLifeTime = Storage::getSessionLifeTime();

		if (isset($_POST['a']) && $_POST['a'] == 'ping') {
			$sessionExpires = Storage::getSessionExpired($_COOKIE['PHPSESSID']);
			$this->data['sessionLifeTime'] = $sessionExpires + $sessionLifeTime - time();
			$this->data['timeWithoutUser'] = time() - $sessionExpires;
		} else {
			$this->data['sessionLifeTime'] = $sessionLifeTime;
		}

		return true;
	}

	public function updateSession() {
		return true;
	}

	/**
	 * Возвращает информацию о том, авторизован ли пользователь.
	 * 
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 * 
	 * @return bool
	 */
	public function isUserAuth() {
		if(USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = array(
			'auth' => USER::getThis()
		);
		return true;
	}

	/**
	 * Сохранение нового значения характеристики.
	 * @return bool
	 */
	public function saveNewValueProp() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['CHANGES_ARE_SAVED'];
		$this->messageError = $this->lang['CHANGE_SAVE_FAIL'];
		$result = false;
		$property_id = $_POST['propid']; // id характеристики
		$string = html_entity_decode($_POST['newval']);
		$string = str_replace('$', '\$', $string); // новое значение
		$old = str_replace(array('[', ']', '(', ')', '$'), array('\[', '\]', '\(', '\)', '\$'), $_POST['oldval']); // старое значение
		$sql = " 
				SELECT *
				FROM `" . PREFIX . "product_user_property`
				WHERE `property_id` = " . DB::quote($property_id);
		$res = DB::query($sql);//запрос выбора БД

		while ($row = DB::fetchAssoc($res)) {//пробегаем по каждому значению полей product_margin И value
			$replacedvar = '';
			$replacedvarvalue = '';
			if ($row['product_margin']!= '') {
				 $replacedvar = preg_replace('~(^|\|)(' . $old . ')($|[#|\|])~', '${1}' . $string . '$3', $row['product_margin']);//замена на новую хар-ку
			}
			if ($row['value'] != '') {
				$replacedvarvalue = preg_replace('~(^|\|)(' . $old . ')($|[#|\|])~', '${1}' . $string . '$3', $row['value']);//замена в поле value
			}
			DB::query("UPDATE `" . PREFIX . "product_user_property` 
				SET `product_margin`= " . DB::quote($replacedvar) . ", `value`= " . DB::quote($replacedvarvalue) . " WHERE `property_id` = " . DB::quote($property_id) . " AND `product_id` = " . DB::quote($row['product_id']) . " ");//запрос замены
		 }

		$res = DB::query('SELECT `data` FROM `' . PREFIX . 'property` WHERE `id`=' . DB::quote($property_id));

		if ($row = DB::fetchArray($res)) {
			$replacedvar = '';
			if ($row['data'] != '') {
				$replacedvar = preg_replace('~(^|\|)(' . $old . ')($|[#|\|])~', '${1}' . $string . '$3', $row['data']);//замена на новую хар-ку
				DB::query("UPDATE `" . PREFIX . "property` 
				SET `data`= " . DB::quote($replacedvar) . " WHERE `id` = " . DB::quote($property_id));//запрос замены
			}
			$result = true;
		}

		$newdata = preg_replace('~(^|\|)(' . $old . ')($|[#|\|])~', '${1}' . $string . '$3', $_POST['olddata']);//замена на новую хар-ку
		$this->data = $newdata;

		return $result;
	}

	/**
	 * Сохраняет порядок строк в таблице с страницами и категориями.
	 * @return bool
	 */
	public function saveSortableTable() {
		$data = $_POST['data'];
		$sqlQueryTo = array();
		switch ($_POST['type']) {
			case 'page':
				if(USER::access('page') < 2) {
					$this->messageError = $this->lang['ACCESS_EDIT'];
					return false;
				}
				$this->messageSucces = $this->lang['CHANGE_SORT_PAGE'];
				break;
			case 'category':
				if(USER::access('category') < 2) {
					$this->messageError = $this->lang['ACCESS_EDIT'];
					return false;
				}
				$this->messageSucces = $this->lang['CHANGE_SORT_CAT'];
				break;
			default:
				if (USER::access('plugin') < 2) {
					$this->messageError = $this->lang['ACCESS_EDIT'];
					return false;
				}
				break;
		}

		// составления массива запросов для изменения порядка сортировки
		foreach ($data as $key => $id) {
			$sqlQueryTo[] = 'UPDATE `'.PREFIX.DB::quote($_POST['type'],true).'` SET sort = '.DB::quote($key).' WHERE id = '.DB::quote($id);
		}

		foreach ($sqlQueryTo as $sql) {
			DB::query($sql);
		}

		return true;
	}

	/**
	 * Заполняет SEO настройки для товаров, категорий и страниц по шаблону.
	 * @return bool
	 */
	public function setSeoForGroup() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['SEO_GEN_TMPL_SUCCESS'];
		$this->messageError = $this->lang['SEO_GEN_TMPL_FAIL'];

		$result = Seo::getMetaByTemplateForAll($_POST['data']);

		if(!$result) {
			$this->messageError = $this->lang['DB_USER_ACCESS_LOW'];
			return false;
		}

		return true;
	}

	/**
	 * Устанавливает стили панели управления.
	 * @return bool
	 */
	public function saveInterface() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		MG::setOption('languageLocale', $_POST['languageLocale']);
		MG::setOption('interface', addslashes(serialize($_POST['data'])));
		MG::setOption('themeBackground', $_POST['bg']);

		MG::setOption('customBackground', $_POST['customBG']);
		MG::setOption('bgfullscreen', $_POST['fullscreen']);
		MG::setOption('customAdminLogo', $_POST['customLogo']);

		$path = URL::getDocumentRoot().'uploads'.DS.'customAdmin';

		if (is_dir($path)) {
			$handle = opendir($path);
			while(false !== ($file = readdir($handle))) {
				if($file != '.' && $file != '..' && $file != $_POST['customBG'] && $file != $_POST['customLogo'] && !is_dir($file)) {
					unlink($path.DS.$file);
				}
			}
			closedir($handle);
		}

		return true;
	}

	/**
	 * Устанавливает стандартные стили панели управления.
	 * @return bool
	 */
	public function defaultInterface() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$data['colorMain'] = '#2773eb';
		$data['colorLink'] = '#1585cf';
		$data['colorSave'] = '#4caf50';
		$data['colorBorder'] = '#e6e6e6';
		$data['colorSecondary'] = '#ebebeb';
		$data["adminBar"] = "#F3F3F3";
		$data['adminBarFontColor'] = "#3c3c3c";
		/* 
		Альтернатива 
		#e6e6e675 - цвет рамок
		#e3e3e3 - цвет прочих кнопок
		#05a95c - цвет  кнопок сохранить и добавить
		#1585cf -  цвет ссылок
		#2773eb - основные цвета
		*/

		MG::setOption('interface', addslashes(serialize($data)));
		return true;
	}

	/**
	 * Сохраняет настройки API.
	 * @return bool
	 */
	public function saveApi() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['API_SAVED'];
		$this->messageError = $this->lang['ERROR'];
		if (empty($_POST['data'])) {
			$_POST['data'] = array();
		}
		
        //логирование API
        LoggerAction::logAction('API', __FUNCTION__, array('data' => $_POST['data'][0], 'id' =>''));
				
		MG::setOption('API', addslashes(serialize($_POST['data'])));
		return true;
	}

	/**
	 * Метод генерации токена для API.
	 * @return bool
	 */
	public function createToken() {
		$this->data = md5(microtime(true).SITE);
		return true;
	}

	/**
	 * Сохраняет настройки складов.
	 * @return bool
	 */
	public function saveStorage() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['STORAGE_SAVED'];
		$this->messageError = $this->lang['ERROR'];
		$storagesIds = $newStoragesIds = array();
		// правим склады, если они плохие
		foreach ($_POST['data'] as $key => $item) {
            $_POST['data'][$key]['pickupPoint'] = ($item['pickupPoint'] == '') ? 'false': $item['pickupPoint'];
			$_POST['data'][$key]['id'] = str_replace(' ', '-', $item['id']);
            }
		// операции для удаления записей лишних из базы
		$storages = unserialize(stripcslashes(MG::getSetting('storages')));
		foreach ($storages as $item) {
			$storagesIds[] = $item['id'];
		}
		foreach ($_POST['data'] as $item) {
			$newStoragesIds[] = $item['id'];
		}
        //Проврека, добавился ли или удалился склад. Если это произошло - сбрасываем настройки списывания со складов до дефолтных
		$difer = array_diff($storagesIds, $newStoragesIds);
        $difer2 = array_diff($newStoragesIds, $storagesIds);
        if(count($difer) > 0){
          MG::setOption('storages_settings', 'a:3:{s:12:\"writeOffProc\";s:1:\"1\";s:28:\"storagesAlgorithmWithoutMain\";s:0:\"\";s:11:\"mainStorage\";s:0:\"\";}');
          $this->data = 'delete';
        }
        if(count($difer2) > 0){
          MG::setOption('storages_settings', 'a:3:{s:12:\"writeOffProc\";s:1:\"1\";s:28:\"storagesAlgorithmWithoutMain\";s:0:\"\";s:11:\"mainStorage\";s:0:\"\";}');
          $this->data = 'add';
        }
        
		$productModel = new Models_Product();
		if (empty($_POST['data'])) {
			$productModel->clearStoragesTable();
		} else {
			foreach ($difer as $obsoleteStorage) {
				$productModel->destroyStorageStocks($obsoleteStorage);
			}
		}

		MG::setOption('storages', addslashes(serialize($_POST['data'])));
		return true;
	}

	/**
	 * Включает и выключает склады.
	 * @return bool
	 */
	public function onOffStorage() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageError = $this->lang['ERROR'];
		if($_POST['data'] == 'true') {
			$productModel = new Models_Product();
			$productModel->checkStoragesRecalculation();
			MG::setOption('onOffStorage', 'ON');
			$this->messageSucces = $this->lang['STORAGE_ON'];
		} else {
			MG::setOption('onOffStorage', 'OFF');
			$this->messageSucces = $this->lang['STORAGE_OFF'];
		}
		return true;
	}
    
    /**
	* Сохраняет настройки складов.
	* @return bool
	*/
    public function saveStorageSettigs(){
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if(isset($_POST['data']['useOneStorage'])){
			MG::setOption('useOneStorage', $_POST['data']['useOneStorage']);
			unset($_POST['data']['useOneStorage']);
		}
      	$setting = addslashes(serialize($_POST['data']));
      	MG::setOption('storages_settings', $setting);
      	return true;
    }
    /**
	* Выводить или ен выводить склад в публичку.
	* @return bool
	*/
    public function saveStorageShowPublic(){
	  if(USER::access('setting') < 2) {
		return false;
	  }
      $data = $_POST['data'];
      $storages = unserialize(stripcslashes(MG::getSetting('storages')));
      foreach ($storages as &$storage){
        if($storage['id'] == $data['storageId']){
          $storage['showPublic'] = $data['showPublic'];
        }
      }
      $storages = addslashes(serialize($storages));
      MG::setOption('storages', $storages);
      if($data['showPublic'] == 'true'){
        $this->messageSucces = "Включен вывод склада в публичку";
      }else if($data['showPublic'] == 'false'){
        $this->messageSucces = "Выключен вывод склада в публичку";
      }
      return true;
    }

    /**
	 * Включает и выключает вывод валют в публичной части сайта.
	 * @return bool
	 */
	public function onOffCurrency() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if($_POST['data'] == 'true') {
			$this->messageSucces = $this->lang['VIEW_CURR_ON'];
		} else {
			$this->messageSucces = $this->lang['VIEW_CURR_OFF'];
		}
		MG::setOption('printCurrencySelector', $_POST['data']);
		return true;
	}

	/**
	 * Включает и выключает вывод языков в публичной части сайта.
	 * @return bool
	 */
	public function onOffMultiLang() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['VIEW_CHANGE'];
		MG::setOption('printMultiLangSelector', $_POST['data']);
		return true;
	}

	/**
	 * Изменяет видимость способов доставки и оплаты по клику на лампочку.
	 * @return bool
	 */
	public function changeActivityDP() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['VIEW_CHANGE'];
		$this->messageError = $this->lang['ERROR'];

		switch ($_POST['tab']) {
			case 'delivery':
				$tab = 'delivery';
				break;
			case 'payment':
				$tab = 'payment';
				break;

			default:
				return false;
		}

		if (
			$_POST['tab'] === 'payment' &&
			MG::isNewPayment() &&
			!empty($_POST['plugin'])
		) {
			$plugin = $_POST['plugin'];
			$pluginCode = $_POST['pluginCode'];
			if ($pluginCode) {
				if (!Models_Payment::checkPaymentAvailable($pluginCode)) {
					$this->data = ['toMarketplace' => 1];
					$this->messageError = $this->lang['PAYMENT_PLUGIN_NOT_PURCHASED'];
					return false;
				}
			}
			if ($_POST['status']) {
				if (!PM::activatePlugin($plugin)) {
					return false;
				}
			} else {
				if (!PM::deactivatePlugin($plugin)) {
					return false;
				}
			}
		}

		$result = DB::query('UPDATE '.PREFIX.DB::quote($tab,true).' SET activity = '.DB::quote($_POST['status']).' WHERE id = '.DB::quote($_POST['id']));

		return $result;
	}

	/**
	 * В категориях подгружает запрашиваемые подкатегории.
	 * @return bool
	 */
	public function showSubCategory() {
		if(USER::access('category') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$array = array();
		$res = DB::query('SELECT DISTINCT * FROM '.PREFIX.'category WHERE parent = '.DB::quote($_POST['id']).' ORDER BY sort ASC');
		while($row = DB::fetchAssoc($res)) {
			$array[] = $row;
		}
		$this->data = MG::get('category')->getPages($array, $_POST['level']-1, $_POST['id']);

		return true;
	}

	/**
	 * В страницах подгружает запрашиваемые страницы.
	 * @return bool
	 */
	public function showSubPage() {
		if(USER::access('page') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$array = array();
		$res = DB::query('SELECT DISTINCT * FROM '.PREFIX.'page WHERE parent = '.DB::quote($_POST['id']).' ORDER BY sort ASC');
		while($row = DB::fetchAssoc($res)) {
			$array[] = $row;
		}
		$this->data = Page::getPages($array, $_POST['level']-1, $_POST['id']);

		return true;
	}

	/**
	 * В категориях подгружает запрашиваемые подкатегории.
	 * @return bool
	 */
	public function showSubCategorySimple() {
		if(
			USER::access('setting') < 1 &&
			USER::access('plugin') < 1
		) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}

		$array = array();
		$res = DB::query('SELECT DISTINCT * FROM '.PREFIX.'category WHERE parent = '.DB::quote($_POST['id']).' ORDER BY sort ASC');
		while($row = DB::fetchAssoc($res)) {
			$array[] = $row;
		}
		$this->data = MG::get('category')->getPagesSimple($array, $_POST['level']-1, $_POST['id']);

		return true;
	}

	//=========================================================
	// ДЛЯ ХАРАКТЕРИСТИК
	//=========================================================

	/**
	 * В разделе настроек характеристик загружает информацию о выбранной характеристики для модального окна.
	 * @return bool
	 */
	public function getProperty() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		$id = $_POST['id'];
		$categoryIds = array();
		// Получаем список выбраных категорий для данной характеристики.
		$res = DB::query("
				SELECT category_id
				FROM `".PREFIX."category_user_property` as сup
				WHERE сup.`property_id` = %s", $id);

		while ($row = DB::fetchAssoc($res)) {
			$categoryIds[] = $row['category_id'];
		}

		$this->data['selectedCatIds'] = $categoryIds;
		$property = array();
		$res = DB::query('SELECT * FROM '.PREFIX.'property WHERE id = '.DB::quote($_POST['id']));
		while($row = DB::fetchAssoc($res)) {
			$tmp = explode('[prop attr=', $row['name']);
			if (isset($tmp[1])) {
				$row['mark'] = str_replace(']', '', $tmp[1]);
			} else {
				$row['mark'] = '';
			}
			$row['name'] = $tmp[0];
			MG::loadLocaleData($row['id'], LANG, 'property', $row);
			$row['selectGroup'] = Property::getPropertyGroup();
			$property = $row;
		}

		$this->data['property'] = $property;

		return true;
	}

	/**
	 * Добавляет поле для характеристики.
	 * @return bool
	 */
	public function addPropertyMargin() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$maxSort = 1;
		$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property_data');
		while($row = DB::fetchAssoc($res)) {
			$maxSort = $row['MAX(id)'];
			$maxSort++;
		}
		DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, margin, sort) VALUES 
			('.DB::quote($_POST['propId']).', '.DB::quote($_POST['name']).', '.DB::quote($_POST['margin']).', '.DB::quote($maxSort).')');

		return true;
	}

	/**
	 * Загружает дополнительные поля для характеристики.
	 * @return bool
	 */
	public function loadPropertyMargin() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$propertyData = array();
		$res = DB::query('SELECT * FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quote($_POST['id']).' ORDER BY sort ASC');
		while($row = DB::fetchAssoc($res)) {
			MG::loadLocaleData($row['id'], LANG, 'property_data', $row);
			$propertyData[] = $row;
		}

		$this->data = $propertyData;

		return true;
	}

	/**
	 * Удаляет изображения цвета у характеристики.
	 * @return bool
	 */
	public function deleteImgMargin() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$res = DB::query('SELECT img FROM '.PREFIX.'property_data WHERE id = '.DB::quoteInt($_POST['id']));
		while($row = DB::fetchAssoc($res)) {
			unlink($row['img']);
		}
		DB::query('UPDATE '.PREFIX.'property_data SET img = \'\' WHERE id = '.DB::quoteInt($_POST['id']));
		return true;
	}

	/**
	 * Удаляет характеристику.
	 * @return bool
	 */
	public function deletePropertyMargin() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$res = DB::query('SELECT pd.img, p.type FROM '.PREFIX.'property_data AS pd 
			LEFT JOIN '.PREFIX.'property AS p ON p.id = pd.prop_id 
			WHERE pd.id = '.DB::quoteInt($_POST['id']));
		while ($row = DB::fetchAssoc($res)) {
			@unlink($row['img']);
			if($row['type'] == 'color') {
				DB::query('DELETE FROM '.PREFIX.'product_variant WHERE color = '.DB::quoteInt($_POST['id']));
			}
			if($row['type'] == 'size') {
				DB::query('DELETE FROM '.PREFIX.'product_variant WHERE size = '.DB::quoteInt($_POST['id']));
			}
		}
		DB::query('DELETE FROM '.PREFIX.'property_data WHERE id = '.DB::quoteInt($_POST['id']));
		DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE prop_data_id = '.DB::quoteInt($_POST['id']));
		return true;
	}

	/**
	 * Подгружаем размерную сетку для нового загружаемого товара в зависимости от выбранной категории.
	 * 
	 * @depricated
	 * TODO delete it? (not used anywhere)
	 * 
	 * @return bool
	 */
	public function loadSizeMapToNewProduct() {
		if(USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = $this->getProdDataWithCat();
		return true;
	}

	/**
	 * Устанавливает изображение для характеристики цвета.
	 * @return bool
	 */
	public function addImageToProp() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_UPLOAD_IMG'];
			return false;
		}

		if(substr_count($_FILES['propImg']['type'], 'image') == '1') {
			$res = DB::query('SELECT img FROM '.PREFIX.'property_data WHERE id = '.DB::quoteInt($_POST['propDataId']));
			while ($row = DB::fetchAssoc($res)) {
				@unlink($row['img']);
			}
			@mkdir('uploads/property-img', 0777);

			$propImgWidth = MG::getSetting('propImgWidth');
			$propImgHeight = MG::getSetting('propImgHeight');

			$type = explode('.', $_FILES['propImg']['name']);
			$type = end($type);
			$newName = substr(md5(time()), 0, 8);
			$path = 'uploads/property-img/'.$newName.'.'.$type;

			move_uploaded_file($_FILES['propImg']['tmp_name'], $path);

			$upload = new Upload(false);
			$upload->_reSizeImage($newName.'.'.$type, $path, $propImgWidth, $propImgHeight, "PROPORTIONAL", 'uploads'.DS.'property-img'.DS, true);

			DB::query('UPDATE '.PREFIX.'property_data SET img = '.DB::quote('uploads/property-img/'.$newName.'.'.$type).' 
				WHERE id = '.DB::quoteInt($_POST['propDataId']));
		} else {
			$this->messageError = $this->lang['UPLOAD_ONLY_IMG'];
			return false;
		}

		$this->messageSucces = $this->lang['ACT_IMG_UPLOAD'];
		$this->data = 'uploads/property-img/'.$_FILES['propImg']['name'];
		return true;
	}

	//=========================================================
	// ИНТЕГРАЦИИ
	//=========================================================

	/**
	 * Вызов страницы интеграции
	 * @return bool
	 */
	public function getIntegrationPage() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$content = '';
		ob_start();
		switch ($_POST['integration']) {
			case 'Avito':
				Avito::createPage();
				break;
			case 'VKUpload':
				//VKUpload::createPage();
				YandexMarket::createPage();
				break;
			case 'YandexMarket':
				YandexMarket::createPage();
				break;
			case 'GoogleMerchant':
				GoogleMerchant::createPage();
				break;
			/* case 'MailChimp':
				MailChimp::createPage();
				break; */
			case 'RetailCRM':
				RetailCRM::createPage();
				break;
			}
		$content = ob_get_contents();
		ob_end_clean();
		$this->data = $content;
		return true;
	}

	/**
	 * (Интеграция Avito) создание новой выгрузки
	 * @return bool
	 */
	public function newTabAvito() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->data = Avito::newTab($_POST['name']);
		return true;
	}
	/**
	 * (Интеграция Avito) сохранение настроек выгрузки
	 * @return bool
	 */
	public function saveTabAvito() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return Avito::saveTab($_POST['name'], $_POST['data']);
	}
	/**
	 * (Интеграция Avito) удаление существующей выгрузки
	 * @return bool
	 */
	public function deleteTabAvito() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return Avito::deleteTab($_POST['name']);
	}
	/**
	 * (Интеграция Avito) загрузка существующей выгрузки
	 * @return bool
	 */
	public function getTabAvito() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = Avito::getTab($_POST['name']);
		return true;
	}
	/**
	 * (Интеграция Avito) построение иерархии категорий Avito
	 * @return bool
	 */
	public function buildSelectsAvito() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = Avito::buildSelects($_POST['id'], $_POST['shopCatId'], $_POST['uploadName']);
		return true;
	}
	/**
	 * (Интеграция Avito) сохранение категории Avito
	 * @return bool
	 */
	public function saveCatAvito() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return Avito::saveCat($_POST['shopId'], $_POST['googleId'], $_POST['name'], isset($_POST['additional'])?$_POST['additional']:null, !empty($_POST['customOptions']) ? $_POST['customOptions'] : []);
	}
	/**
	 * (Интеграция Avito) получение категорий Avito
	 * @return bool
	 */
	public function getCatsAvito() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = Avito::getCats($_POST['name']);
		return true;
	}
	/**
	 * (Интеграция Avito) получение названия категории Avito по ID
	 * @return bool
	 */
	public function getCatNameAvito() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = Avito::getCatName($_POST['id']);
		return true;
	}
	/**
	 * (Интеграция Avito) рекурсивное применение категорий Avito
	 * @return bool
	 */
	public function updateCatsRecursAvito() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return Avito::updateCatsRecurs($_POST['shopId'], $_POST['googleId'], $_POST['name']);
	}
	/**
	 * (Интеграция Avito) создание базы категорий Avito
	 * @return bool
	 */
	public function updateDBAvito() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return Avito::updateDB();
	}
	/**
	 * (Интеграция Avito) получение списка городов Avito
	 * @return bool
	 */
	public function getCitysAvito() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = Avito::getCitys($_POST['region']);
		return true;
	}
	/**
	 * (Интеграция Avito) получение списка метро и районов Avito
	 * @return bool
	 */
	public function getSubwaysAvito() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = Avito::getSubways($_POST['city']);
		return true;
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * (Интеграция VKontakte) сохранение настроек
	 * @return bool
	 */
	public function saveVKUpload() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$tmp = array('vkGroupId' => $_POST['vkGroupId'], 'vkAppId' => $_POST['vkAppId'], 'vkApiKey' => $_POST['vkApiKey']);

		MG::setOption(array('option' => 'vkUpload', 'value'  => addslashes(serialize($tmp)), 'active' => 'N'));

		return true;
	}
	/**
	 * (Интеграция VKontakte) коннект и получение категорий ВК
	 * @return bool
	 */
	public function connectVKUpload() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = VKUpload::connect($_POST['token']);

		return true;
	}
	/**
	 * (Интеграция VKontakte) получение ID товаров для выгрузки
	 * @return bool
	 */
	public function getNumVKUpload() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = VKUpload::getNum($_POST['shopCats'], $_POST['inactiveToo'], $_POST['useAdditionalCats']);

		return true;
	}
	/**
	 * (Интеграция VKontakte) выгрузка товаров
	 * @return bool
	 */
	public function uploadVKUpload() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$accessToken = $_POST['access_token'];
		$vkCat = $_POST['vkCat'];
		$vkAlbum = $_POST['vkAlbum'];
		$useNull = $_POST['useNull'];
		$dimensionUnits = $_POST['dimensionUnits'];
		$widthPropId = $_POST['widthPropId'];
		$heightPropId = $_POST['heightPropId'];
		$lengthPropId = $_POST['lengthPropId'];
		$uploadStocks = $_POST['uploadStocks'];
		$this->data = VKUpload::upload(
			$accessToken,
			$vkCat,
			$vkAlbum,
			$useNull,
			$dimensionUnits,
			$widthPropId,
			$heightPropId,
			$lengthPropId,
			$uploadStocks
		);

		return true;
	}
	/**
	 * (Интеграция VKontakte) получение ID товаров для удаления
	 * @return bool
	 */
	public function getNumVKUploadDelete() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = VKUpload::getNumDelete($_POST['shopCats'], $_POST['useAdditionalCats']);

		return true;
	}
	/**
	 * (Интеграция VKontakte) удаление товаров
	 * @return bool
	 */
	public function deleteVKUpload() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->data = VKUpload::delete($_POST['access_token']);

		return true;
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * (Интеграция Yandex.Market) создание новой выгрузки
	 * @return bool
	 */
	public function newTabYandexMarket() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->data = YandexMarket::newTab($_POST['name']);
		return true;
	}
    /*
     * Находит товары и варианты для удаления из выгрузки 
     * @return bool
     */
    public function findProductProductRemove(){
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
        $request = '%'.$_POST["request"].'%';
        $model = new Models_Catalog;
        $products = $model->getListByUserFilter(10, " (p.title LIKE ".DB::quote($request)." AND p.count <> 0) OR (p.code LIKE ".DB::quote($request)." AND p.count <> 0) OR (pv.title_variant LIKE ".DB::quote($request)." AND pv.count <> 0) OR (pv.code LIKE ".DB::quote($request)." AND pv.count <> 0)");
        $products = $products["catalogItems"];
        $model = new Models_Product;
        foreach($products as &$item):
            $item["image_url"] = mgImageProductPath($item["image_url"], $item["id"], "small");
            $item["variants"] = $model->getVariants($item["id"]);
            foreach($item["variants"] as $a => $i):
                if($i["count"] == 0) {
                    unset($item["variants"][$a]);
                    continue;
                }
            	$item["variants"][$a]["image"] = mgImageProductPath($i["image"], $item["id"], "small");
            endforeach;
        endforeach;

        $this->data["items"] = $products;
        $this->data["currency"] = MG::getSetting("currency");
        return true;
    }
     /*
     * Добавление товра в выгрузку Яндекс 
     * @return bool
     */
    public function addProductProductRemove(){
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
        $id = $_POST["id"];
        $model = new Models_Product;
        $product = $model->getProduct($id);
        $variants = $model->getVariants($id);
        $product["variants"] = $variants;
        if(count($variants) > 0 && $_POST["var"] != 'false') {
        	$product["image_url"] = mgImageProductPath($variants[$_POST["var"]]["image"], $product["id"], "big");
        } else {
        	$product["image_url"] = mgImageProductPath($product["image_url"], $product["id"], "big");
    	}
        if($product["count"] == -1) $product["count"] = 999999;
        $product["title"] .= (count($product["variants"]) > 0 && $_POST["var"] != 'false')? " ".$product["variants"][$_POST["var"]]["title_variant"] : "";
        $product["code"] = (count($product["variants"]) > 0 && $_POST["var"] != 'false')? " ".$product["variants"][$_POST["var"]]["code"] : $product["code"];
        $this->data["product"] = $product;
        $this->data['currency'] = MG::getSetting("currency");
        return true;
    }

    /**
	 * (Интеграция Yandex.Market) сохранение настроек выгрузки
	 * @return bool
	 */
	public function saveTabYandexMarket() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return YandexMarket::saveTab($_POST['name'], $_POST['data']);
	}
	/**
	 * (Интеграция Yandex.Market) удаление существующей выгрузки
	 * @return bool
	 */
	public function deleteTabYandexMarket() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return YandexMarket::deleteTab($_POST['name']);
	}
	/**
	 * (Интеграция Yandex.Market) загрузка существующей выгрузки
	 * @return bool
	 */
	public function getTabYandexMarket() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = YandexMarket::getTab($_POST['name']);
		return true;
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * (Интеграция Google merchant) создание новой выгрузки
	 * @return bool
	 */
	public function newTabGoogleMerchant() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->data = GoogleMerchant::newTab($_POST['name']);
		return true;
	}
	/**
	 * (Интеграция Google merchant) сохранение настроек выгрузки
	 * @return bool
	 */
	public function saveTabGoogleMerchant() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return GoogleMerchant::saveTab($_POST['name'], $_POST['data']);
	}
	/**
	 * (Интеграция Google merchant) удаление существующей выгрузки
	 * @return bool
	 */
	public function deleteTabGoogleMerchant() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return GoogleMerchant::deleteTab($_POST['name']);
	}
	/**
	 * (Интеграция Google merchant) загрузка существующей выгрузки
	 * @return bool
	 */
	public function getTabGoogleMerchant() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = GoogleMerchant::getTab($_POST['name']);
		return true;
	}
	/**
	 * (Интеграция Google merchant) построение иерархии категорий google
	 * @return bool
	 */
	public function buildSelectsGoogleMerchant() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = GoogleMerchant::buildSelects($_POST['id']);
		return true;
	}
	/**
	 * (Интеграция Google merchant) сохранение категории google
	 * @return bool
	 */
	public function saveCatGoogleMerchant() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return GoogleMerchant::saveCat($_POST['shopId'], $_POST['googleId'], $_POST['name']);
	}
	/**
	 * (Интеграция Google merchant) получение категорий google
	 * @return bool
	 */
	public function getCatsGoogleMerchant() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = GoogleMerchant::getCats($_POST['name']);
		return true;
	}
	/**
	 * (Интеграция Google merchant) получение названия категории google по ID
	 * @return bool
	 */
	public function getCatNameGoogleMerchant() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = GoogleMerchant::getCatName($_POST['id']);
		return true;
	}
	/**
	 * (Интеграция Google merchant) рекурсивное применение категорий google
	 * @return bool
	 */
	public function updateCatsRecursGoogleMerchant() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return GoogleMerchant::updateCatsRecurs($_POST['shopId'], $_POST['googleId'], $_POST['name']);
	}
	/**
	 * (Интеграция Google merchant) удаление повторов категорий google
	 * @return bool
	 */
	public function clearTrashGoogleMerchant() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return GoogleMerchant::clearTrash($_POST['name']);
	}
	/**
	 * (Интеграция Google merchant) создание базы категорий google
	 * @return bool
	 */
	public function updateDBGoogleMerchant() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		return GoogleMerchant::updateDB();
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * (Интеграция MailChimp) массовая выгрузка
	 * @return bool
	 */
	public function uploadAllMailChimp() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['EXPORT_START'];
		$this->messageError = $this->lang['ERROR_CHECH_SETTING'];
		return MailChimp::uploadAll($_POST['API'], $_POST['listId'], $_POST['perm']);
	}
	/**
	 * (Интеграция MailChimp) сохранение настроек
	 * @return bool
	 */
	public function saveMailChimp() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return MailChimp::saveOptions($_POST['API'], $_POST['listId'], $_POST['perm'], $_POST['uploadNew']);
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * (Интеграция RetailCRM) сохранение настроек
	 * @return bool
	 */
	public function saveRetailCRM() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return RetailCRM::saveOptions();
	}
	/**
	 * (Интеграция RetailCRM) стартовая выгрузка
	 * @return bool
	 */
	public function uploadAllRetailCRM() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if (
			$_POST['uploadUsers'] != 'true' &&
			$_POST['uploadOrders'] != 'true'
		) {
			$this->messageError = 'Не выбраны опции выгрузки';
			return false;
		}
		$this->messageSucces = 'Выгрузка завершена успешно';
		$this->messageError = 'Произошла ошибка (подробнее в логе в временной директории сайта)';
		return RetailCRM::uploadAll($_POST['uploadUsers'], $_POST['uploadOrders']);
	}
	/**
	 * (Интеграция RetailCRM) синхронизация
	 * @return bool
	 */
	public function syncRetailCRM() {
		//логирование синхронизации с Ретейлом
        LoggerAction::logAction('integration', __FUNCTION__, '');
		
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		$options = unserialize(stripslashes(MG::getSetting('retailcrm')));
		if (
			$options['syncUsers'] != 'true' &&
			$options['syncOrders'] != 'true' &&
			$options['syncRemainsBack'] != 'true' &&
			$options['syncRemains'] != 'true'
		) {
			$this->messageError = 'Не выбраны опции синхронизации';
			return false;
		}

		$this->messageSucces = 'Синхронизация завершена успешно';
		$this->messageError = 'Произошла ошибка синхронизации (подробнее в логе в временной директории сайта)';
		return RetailCRM::syncAll();
	}
	//=========================================================
	// ИНТЕГРАЦИИ КОНЕЦ
	//=========================================================
	// сохранение языков для мультиязычности
	public function saveLang() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = 'Языки сохранены';

		$oldLangs = MG::getSetting('multiLang', 1);
		if (!is_array($oldLangs)) {$oldLangs = array();}
		$newLangs = $_POST['data'];
		$langsToDrop = $langsToCreate = array();
		$currentTemplateLocalesPath = SITE_DIR.'mg-templates'.DS.MG::getSetting('templateName').DS.'locales'.DS;
		$defaultTemplateLocalesPath = SITE_DIR.'mg-templates'.DS.'moguta-standard'.DS.'locales'.DS;
		$coreLocalesPath = SITE_DIR.'mg-core'.DS.'locales'.DS;

		foreach ($oldLangs as $oldLang) {
			$drop = true;
			foreach ($newLangs as $newLang) {
				if ($newLang['short'] == $oldLang['short']) {
					$drop = false;
					break;
				}
			}
			if ($drop) {
				$langsToDrop[] = $oldLang['short'];
			}
		}

		foreach ($newLangs as $newLang) {
			$create = true;
			foreach ($oldLangs as $oldLang) {
				if ($oldLang['short'] == $newLang['short']) {
					$create = false;
					break;
				}
			}
			if ($create) {
				$langsToCreate[] = $newLang['short'];
			}
		}
		if(!class_exists('CloudCore')){
			if (!empty($langsToDrop)) {
				DB::query("DELETE FROM `".PREFIX."locales` WHERE `locale` IN (".DB::quoteIN($langsToDrop).")");
				foreach ($langsToDrop as $lang) {
					if (is_file($currentTemplateLocalesPath.$lang.'.php')) {
						unlink($currentTemplateLocalesPath.$lang.'.php');
					}
					if (is_file($currentTemplateLocalesPath.$lang.'.js')) {
						unlink($currentTemplateLocalesPath.$lang.'.js');
					}
				}
			}

			if (!empty($langsToCreate)) {
				if (!is_dir($currentTemplateLocalesPath)) {
					mkdir($currentTemplateLocalesPath);
				}
				$langsToCreate[] = 'default';
				foreach ($langsToCreate as $lang) {
					foreach (['.php','.js'] as $ext) {
						if (!is_file($currentTemplateLocalesPath.$lang.$ext)) {
							if (is_file($currentTemplateLocalesPath.'default'.$ext)) {
								copy($currentTemplateLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang.$ext);
							} elseif (is_file($defaultTemplateLocalesPath.'default'.$ext)) {
								copy($defaultTemplateLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang.$ext);
							} elseif (is_file($coreLocalesPath.'default'.$ext)) {
								copy($coreLocalesPath.'default'.$ext, $currentTemplateLocalesPath.$lang.$ext);
							} else {
								file_put_contents($currentTemplateLocalesPath.$lang.$ext, '');
							}
						}
					}
				}
			}
		}
		MG::setOption('multiLang', $_POST['data'], 1);
		return true;
	}

	/**
	 * Сохранение столбцов в админке.
	 * @return bool
	 */
	public function saveAdminColumns() {
		$this->messageSucces = $this->lang['SUCCESS_SAVE'];
		$this->messageError  = $this->lang['ACCESS_EDIT'];
		switch ($_POST['data']['type']) {
			case 'catalog':
				if(USER::access('product') < 2) {
					return false;
				}
				$settingName = 'catalogColumns';
				break;
			case 'order':
				if(USER::access('order') < 2) {
					return false;
				}
				$settingName = 'orderColumns';
				break;
			case 'user':
				if(USER::access('user') < 2) {
					return false;
				}
				$settingName = 'userColumns';
				break;

			default:
				$this->messageError = $this->lang['RULE_NOT_FOUND'];
				return false;
				break;
		}
		if (empty($_POST['data']['activeColumns'])) {
			$_POST['data']['activeColumns'] = array();
		}

		MG::setOption(array(
			'option' => $settingName,
			'value'  => addslashes(serialize($_POST['data']['activeColumns'])),
			'active' => 'Y')
		);

		return true;
	}

	/**
	 * Возвращает уведомления движка.
	 * @return bool
	 */
	public function getEngineMessages() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if (isset($_POST['lang'])) {
			$locale = $_POST['lang'];
		}
		else{
			$locale = 'LANG';
		}
		$messages = array();
		$res = DB::query("SELECT `id`, `name`, `text`, `group` FROM `".PREFIX."messages`");
		while ($row = DB::fetchAssoc($res)) {
			MG::loadLocaleData($row['id'], $locale, 'messages', $row);
			$messages[$row['group']][$row['name']] = array('id' => $row['id'], 'text' => $row['text'], 'title' => $this->lang[$row['name']], 'tip' => $this->lang['DESC_'.$row['name']]);
		}

		$messages2 = array();

		$messages2['order'] = $messages['order'];
		$messages2['product'] = $messages['product'];
		$messages2['register'] = $messages['register'];
		$messages2['feedback'] = $messages['feedback'];
		$messages2['status'] = $messages['status'];

		$this->data = $messages2;
		return true;
	}

	/**
	 * Сброс уведомлений.
	 * @return bool
	 */
	public function resetMsgs() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$messages = array();
		$res = DB::query("SELECT `name`, `text_original` FROM `".PREFIX."messages` where `group` = ".DB::quote($_POST['type']));
		while ($row = DB::fetchAssoc($res)) {
			$messages[$row['name']] = $row['text_original'];
		}

		$this->data = $messages;
		return true;
	}

	/**
	 * Сохраняет уведомления движка.
	 * @return bool
	 */
	public function saveMsgs() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$locale = $_POST['lang'];
		if(($locale == '')||($locale == 'LANG')||($locale == 'default')) {
			foreach ($_POST['fields'] as $value) {
				DB::query("UPDATE `".PREFIX."messages` SET `text`=".DB::quote($value['val'])." WHERE `name`=".DB::quote($value['name']));
			}
		}
		else{
			foreach ($_POST['fields'] as $value) {
				MG::saveLocaleData($value['id'], $locale, 'messages', array('text' => $value['val']));
			}
		}
		return true;
	}

	/**
	 * Возвращает список пользовательских полей для заказа.
	 * @return bool
	 */
	public function loadFields() {
		if(USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$data = unserialize(stripcslashes(MG::getSetting('optionalFields')));
		// viewdata($data);
		foreach ($data as $key => $value) {
			if($value['type'] == 'select' || $value['type'] == 'radiobutton') {
				foreach ($value['variants'] as $key1 => $value1) {
					$data[$key]['variants'][$key1] = htmlspecialchars($value1);
				}
			}
		}
		$this->data = $data;
		return true;
	}

	/**
	 * Сохраняет список пользовательских полей для заказа.
	 * @return array
	 */
	public function saveOptionalFields() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['OP_SAVED'];
		$this->messageError = $this->lang['ERROR'];

		Models_OpFieldsOrder::saveFields(isset($_POST['data'])?$_POST['data']:array());
		$this->data = MG::adminLayout('op-fields.php', Models_OpFieldsOrder::getFields(), 'order');

		return true;
	}

	/**
	 * Для оптовых скидок админки.
	 * @return bool
	 */
	public function loadWholesaleList() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$producten = $productenVar = array();
		$category = $html = '';
		if(isset($_POST['only']) && $_POST['only'] == 'true') {
			$res = DB::query('SELECT DISTINCT product_id, variant_id FROM '.PREFIX.'wholesales_sys');
			while ($row = DB::fetchAssoc($res)) {
				$producten[] = $row['product_id'];
				$productenVar[] = $row['variant_id'];
			}
			$producten = implode(',', $producten);
			$productenVar = implode(',', $productenVar);
		}
		if($producten == '') $producten = '\'\'';
		if($productenVar == '') $productenVar = '\'\'';
		if($_POST['category'] != 0) $category = 'AND cat_id = '.DB::quote($_POST['category']);
		// если к вариантам одни и те же цены то
		if($_POST['variants'] == 1) {
			$groupAndOrder = 'GROUP BY p.id ORDER BY pv.id ASC';
		} else {
			$groupAndOrder = 'ORDER BY pv.id ASC';
		}

		$sql = 'SELECT p.id AS pid, p.title, p.code AS pcode, pv.id AS pvid, pv.title_variant, 
			pv.code as pvcode, p.price, pv.price AS pprice, p.currency_iso
			FROM '.PREFIX.'product AS p
			LEFT JOIN '.PREFIX.'product_variant AS pv 
				ON p.id = pv.product_id
			WHERE 
				(p.code LIKE \'%'.DB::quote(isset($_POST['search'])?$_POST['search']:'', true).'%\' OR
				p.title LIKE \'%'.DB::quote(isset($_POST['search'])?$_POST['search']:'', true).'%\' OR
				pv.code LIKE \'%'.DB::quote(isset($_POST['search'])?$_POST['search']:'', true).'%\' OR
				pv.title_variant LIKE \'%'.DB::quote(isset($_POST['search'])?$_POST['search']:'', true).'%\' OR
				p.id LIKE \'%'.DB::quote(isset($_POST['search'])?$_POST['search']:'', true).'%\') AND
				(p.id NOT IN ('.DB::quoteIN($producten, false).') OR pv.id NOT IN ('.DB::quoteIN($productenVar, false).'))
				'.$category.' '.$groupAndOrder;

		$navigator = new Navigator($sql, $_POST['page'], 15, 6, false, 'page'); //определяем класс
		$products = $navigator->getRowsSql();

		$wholesalesGroup = MG::getSetting('wholesalesGroup');
		$wholesalesGroup = unserialize(stripcslashes($wholesalesGroup));
		if(!$wholesalesGroup) {
			$wholesalesGroup = [];
		}

		$cur = MG::getSetting('currencyShort');

		foreach($products as $row) {
			if(empty($row['pvcode']) || ($_POST['variants'] == 1)) {
				$code = $row['pcode'];
			} else {
				$code = $row['pvcode'];
			}
			if(empty($row['pprice'])) {
				$price = $row['price'];
			} else {
				$price = $row['pprice'];
			}
			if(empty($row['pvid'])) $row['pvid'] = 0;
			if($_POST['variants'] == 1) {
				unset($row['title_variant']);
			}
			$html .= '<tr class="product" data-product-id="'.$row['pid'].'" data-variant-id="'.$row['pvid'].'" style="cursor:pointer;">';
			$html .= '<td>'.$row['pid'].'</td>';
			$html .= '<td>'.$code.'</td>';
			$html .= '<td>'.$row['title'];
			if (isset($row['title_variant'])) {
				$html .= ' '.$row['title_variant'];
			}
			$html .= '</td>';
			$html .= '<td>'.$price.' '.$cur[$row['currency_iso']].'</td>';
			foreach ($wholesalesGroup as $key => $value) {
				$html .= '<td><a class="link editWholeRule" data-group="'.$value.'" data-price="'.$price.'" data-name="'.$row['title'].'" data-cur="'.$cur[$row['currency_iso']].'"><span>Редактировать</span></a></td>';
			}
			$html .= '</tr>';
		}
		if(empty($html)) {
			$html = '<tr><td colspan=4 class="text-center">Товары не найдены</td></tr>';
		}
		$this->data['html'] = $html;
		$this->data['pager'] = $navigator->getPager('forAjax');
		return true;
	}

	/**
	 * Возвращает таблицу со оптовыми скидками для настройки.
	 * @return bool
	 */
	public function loadWholesaleRule() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$html = '';
		$res = DB::query('SELECT * FROM '.PREFIX.'wholesales_sys WHERE 
			product_id = '.DB::quoteInt($_POST['data']['productId']).' AND variant_id = '.DB::quoteInt($_POST['data']['variantId']).'
			AND `group` = '.DB::quoteInt($_POST['group']));
		while ($row = DB::fetchAssoc($res)) {
			$html .= '<tr class="rule">';
			$html .= '<td><input name="count" type="text" value="'.$row['count'].'"></td>';
			$html .= '<td><input name="price" type="text" value="'.$row['price'].'"></td>';
			$html .= '<td class="text-right"><button title="Удалить строку" class="deleteLineRule"><i class="fa fa-trash" aria-hidden="true"></i></button></td>';
			$html .= '</tr>';
		}
		if(empty($html)) {
			$html = '<tr><td colspan=3 class="text-center toDel">'.$this->lang['RULE_NOT_FOUND'].'</td></tr>';
		}
		$this->data = $html;
		return true;
	}

	/**
	 * Сохраняет настройки оптовых цен.
	 * @return bool
	 */
	public function saveWholesaleRule() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['PRICE_SAVED'];
		$this->messageError = $this->lang['ERROR'];
		if($_POST['variants'] == 1 && $_POST['variant'] != 0) {
			DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
				product_id = '.DB::quoteInt($_POST['product']).' AND 
				variant_id != 0 AND
				`group` = '.DB::quoteInt($_POST['group']));
			$varIds = array();
			$res = DB::query('SELECT DISTINCT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($_POST['product']));
			while($row = DB::fetchAssoc($res)) {
				$varIds[] = $row['id'];
			}
			foreach ($varIds as $id) {
				foreach ($_POST['data'] as $item) {
					if($item['price'] == 0) continue;
					DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
						('.DB::quoteInt($_POST['product']).', '.DB::quoteInt($id).', 
						'.DB::quoteFloat($item['count']).', '.DB::quoteFloat($item['price']).',
						'.DB::quoteInt($_POST['group']).')');
				}
			}
		} else {
			DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
				product_id = '.DB::quoteInt($_POST['product']).' AND 
				variant_id = '.DB::quoteInt($_POST['variant']).' AND
				`group` = '.DB::quoteInt($_POST['group']));
			foreach ($_POST['data'] as $item) {
				if($item['price'] == 0) continue;
				DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
					('.DB::quoteInt($_POST['product']).', '.DB::quoteInt($_POST['variant']).', 
					'.DB::quoteFloat($item['count']).', '.DB::quoteFloat($item['price']).',
					'.DB::quoteInt($_POST['group']).')');
			}
		}

		return true;
	}

	/**
	 * Сохраняет тип оптовых цен.
	 * @return bool
	 */
	public function saveWholesaleType() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['WHOLESALES_TYPE_CHANGE'];
		$this->messageError = $this->lang['ERROR'];
		// сохранение типа
		MG::setOption('wholesalesType', $_POST['type']);
		return true;
	}

	/**
	 * Добавляет группу оптовых цен.
	 * @return bool
	 */
	public function addWholesaleGroup() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageError = $this->lang['ERROR'];
		$htmlUser = $htmlPrice = '';

		// сохранение типа
		$data = MG::getSetting('wholesalesGroup');
		$data = unserialize(stripcslashes($data));
		if(!$data) {
			$data = array();
		}
		$max = !empty($data)?max($data):0;
		$max++;
		$data[] = $max;
		$data = array_unique($data);
		$dataClone = $data;
		$data = addslashes(serialize($data));
		MG::setOption('wholesalesGroup', $data);

		$res = DB::query('SELECT id, name, wholesales FROM '.PREFIX.'user_group');
		while($row = DB::fetchAssoc($res)) {
			$htmlUser .= '<tr class="whole-users-table__row" data-id="'.$row['id'].'">
				<td class="whole-users-table__cell whole-cell">
            <span class="whole-cell__inner">
                '.$row['name'].'
            </span>
        </td>
				<td class="whole-users-table__cell whole-cell">
					<select title="'.$this->lang['CHOOSE_WHOLE_SALE_TYPE'].'" class="whole-users-table__select setToWholesaleGroup whole-cell__inner">
						<option value="0" '.($row['wholesales']==0?'selected="selected"':'').'>'.$this->lang['WHOLESALE_SETTINGS_18'].'</option>';
			foreach ($dataClone as $key => $value) {
				$htmlUser .= '<option value="'.$value.'" '.($row['wholesales']==$value?'selected="selected"':'').'>'.$this->lang['WHOLESALE_SETTINGS_19'].' '.$value.'</option>';
			}
			$htmlUser .= '</select>
				</td>
			</tr>';
		}

		foreach ($dataClone as $key => $value) {
			$htmlPrice .= '<tr>
				<td>'.$this->lang['WHOLESALE_SETTINGS_19'].' '.$value.'</td>
				<td class="text-right"><i class="fa fa-trash deleteWholePrice" data-id="'.$value.'" style="cursor:pointer;"></i></td>
			</tr>';
		}

		$productsHead = '<th class="border-top">id</th>
										<th class="border-top">'.$this->lang['WHOLESALE_SETTINGS_9'].'</th>
										<th class="border-top">'.$this->lang['SETTING_LOCALE_19'].'</th>
										<th class="border-top">'.$this->lang['WHOLESALE_SETTINGS_5'].'</th>';

		foreach ($dataClone as $key => $value) {
			$productsHead .= '<th class="border-top">'.$this->lang['WHOLESALE_SETTINGS_19'].' '.$value.'</th>';
		}

		$this->data['htmlPrice'] = $htmlPrice;
		$this->data['htmlUser'] = $htmlUser;
		$this->data['productsHead'] = $productsHead;
		$this->data['max'] = $max;
		return true;
	}

	/**
	 * Удаляет группу оптовых цен.
	 * @return bool
	 */
	public function deleteWholesaleGroup() {
		if(USER::access('setting') < 2 || USER::access('product') < 2) {
			$this->messageError = $this->lang['ERROR'];	
			return false;
		}
		// сохранение типа
		$data = MG::getSetting('wholesalesGroup');
		$data = unserialize(stripcslashes($data));
		$htmlUser = $htmlPrice = '';
		if(!$data) {
			$data = array();
		}
		foreach ($data as $key => $value) {
			if($value == $_POST['id']) {
				unset($data[$key]);
				DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE `group` = '.DB::quoteInt($_POST['id']));
			}
		}
		$data = array_unique($data);
		$dataClone = $data;
		$data = addslashes(serialize($data));
		MG::setOption('wholesalesGroup', $data);

		$res = DB::query('SELECT id, name, wholesales FROM '.PREFIX.'user_group');
		while($row = DB::fetchAssoc($res)) {
			$htmlUser .= '<tr class="whole-users-table__row"  data-id="'.$row['id'].'">
				<td class="whole-users-table__cell whole-cell">
            <span class="whole-cell__inner">
                '.$row['name'].'
            </span>
				</td>
				<td class="whole-users-table__cell whole-cell">
					<select title="'.$this->lang['CHOOSE_WHOLE_SALE_TYPE'].'" class="whole-users-table__select setToWholesaleGroup whole-cell__inner">
						<option value="0" '.($row['wholesales']==0?'selected="selected"':'').'>'.$this->lang['WHOLESALE_SETTINGS_18'].'</option>';
			foreach ($dataClone as $key => $value) {
				$htmlUser .= '<option value="'.$value.'" '.($row['wholesales']==$value?'selected="selected"':'').'>'.$this->lang['WHOLESALE_SETTINGS_19'].' '.$value.'</option>';
			}
			$htmlUser .= '</select>
				</td>
			</tr>';
		}

		foreach ($dataClone as $key => $value) {
			$htmlPrice .= '<tr>
				<td>'.$this->lang['WHOLESALE_SETTINGS_19'].' '.$value.'</td>
				<td class="text-right"><i class="fa fa-trash deleteWholePrice" data-id="'.$value.'" style="cursor:pointer;"></i></td>
			</tr>';
		}

		$productsHead = '<th class="border-top">id</th>
										<th class="border-top">'.$this->lang['WHOLESALE_SETTINGS_9'].'</th>
										<th class="border-top">'.$this->lang['SETTING_LOCALE_19'].'</th>
										<th class="border-top">'.$this->lang['WHOLESALE_SETTINGS_5'].'</th>';

		foreach ($dataClone as $key => $value) {
			$productsHead .= '<th class="border-top">'.$this->lang['WHOLESALE_SETTINGS_19'].' '.$value.'</th>';
		}

		$this->data['htmlPrice'] = $htmlPrice;
		$this->data['htmlUser'] = $htmlUser;
		$this->data['productsHead'] = $productsHead;

		return true;
	}

	/**
	 * Прикрепляет группу оптовых цен.
	 * @return bool
	 */
	public function setToWholesaleGroup() {
		if(USER::access('setting') < 2 || USER::access('product') < 2) {
			$this->messageError = $this->lang['ERROR'];
			return false;
		} 

		DB::query('UPDATE '.PREFIX.'user_group SET wholesales = '.DB::quoteInt($_POST['group']).' WHERE id = '.DB::quoteInt($_POST['id']));
		return true;
	}

	/**
	 * Получает сокращеную валюту и включена ли кратность.
	 * @return bool
	 */
	public function getCurrencyShort() {
		if(USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = ['currencyShort' =>MG::getSetting('currencyShort'), 'useMultiplicity' => MG::getSetting('useMultiplicity')];
		return true;
	}
	/**
	 * Установка валюты для администратора.
	 * @return bool
	 */
	public function setAdminCurrency() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ERROR'];	
			return false;
		}
		if (MG::getSetting('printCurrencySelector') == 'true' && $_SESSION['userCurrency'] != $_POST['userCustomCurrency']) {
			$oldCurr = $_SESSION['userCurrency'];
			$_SESSION['userCurrency'] = $_POST['userCustomCurrency'];

			$settings = MG::get('settings');
			$result = DB::query("
				SELECT `option`, `value`
				FROM `".PREFIX."setting` 
				WHERE `option` = 'currencyRate'
				");

			while ($row = DB::fetchAssoc($result)) {
				$settings[$row['option']] = $row['value'];
			}

			$settings['currencyRate'] = unserialize(stripslashes($settings['currencyRate']));

			$settings['currencyShopIso'] = $_SESSION['userCurrency'];

			$rate = $settings['currencyRate'][$settings['currencyShopIso']];

			$settings['currencyRate'][$settings['currencyShopIso']] = 1;

			foreach ($settings['currencyRate'] as $iso => $value) {
				if ($iso != $settings['currencyShopIso']) {
					if (!empty($rate)) {
						$settings['currencyRate'][$iso] = $value / $rate;
					}
				}
			}

			$this->data = array('curr' => $settings['currencyShort'][$_SESSION['userCurrency']], 'multiplier' => $settings['currencyRate'][$oldCurr]);
			$settings['currency'] = $settings['currencyShort'][$settings['currencyShopIso']];
			MG::set('settings', $settings);
		}

		return true;
	}
	/**
	 * Сброс валюты для администратора.
	 * @return bool
	 */
	public function resetAdminCurrency() {
		if(
			USER::access('admin_zone') < 1 &&
			USER::access('product') < 1
		) {
			$this->messageError = $this->lang['ACCESS_EDIT'];	
			return false;
		}
		if(MG::getSetting('printCurrencySelector') == 'true') {
			$iso = MG::getOption('currencyShopIso');
			if($iso != $_SESSION['userCurrency']) {
				$settings = MG::get('settings');
				$settings['currencyRate'] = MG::get('dbCurrRates');
				$settings['currencyShopIso'] = MG::get('dbCurrency');
				$_SESSION['userCurrency'] = $settings['currencyShopIso'];
				$settings['currency'] = $settings['currencyShort'][$settings['currencyShopIso']];
				MG::set('settings', $settings);
				$this->data = array('currency' => $settings['currency'], 'currencyISO' => $settings['currencyShopIso']);
			} else {
				$this->data = array('currency' => MG::getSetting('currency'), 'currencyISO' => $iso);
			}
		} else {
			$this->data = array('currency' => MG::getSetting('currency'), 'currencyISO' => MG::getSetting('currencyShopIso'));
		}
		return true;
	}

	/**
	 * Получает перевод способов доставки.
	 * @return bool
	 */
	public function loadLocaleDelivery() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];	
			return false;
		}
		$data = array(
			'name' => '',
			'description' => '');
		MG::loadLocaleData($_POST['id'], $_POST['lang'], 'delivery', $data);
		$this->data = $data;
		return true;
	}

	/**
	 * Возвращает список групп характеристик.
	 * @return bool
	 */
	public function getTablePropertyGroup() {
		if(
			USER::access('setting') < 1 &&
			USER::access('product') < 1
		) {
			$this->messageError = $this->lang['ACCESS_VIEW'];	
			return false;
		}
		$this->data = Property::getPropertyGroup();

		return true;
	}

	/**
	 * Добавляет группу характеристик.
	 * @return bool
	 */
	public function addPropertyGroup() {
		if(USER::access('product') < 2 || USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->data = Property::addPropertyGroup($_POST['name']);
		return true;
	}

	/**
	 * Удаляет группу характеристик.
	 * @return bool
	 */
	public function deletePropertyGroup() {
		if(USER::access('product') < 2 || USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		Property::deletePropertyGroup(isset($_POST['id'])?$_POST['id']:null);

		return true;
	}

	/**
	 * Сохраняет группу характеристик.
	 * @return bool
	 */
	public function savePropertyGroup() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$locale = !empty($_POST['lang']) ? $_POST['lang'] : 'LANG';

		if(($locale == '')||($locale == 'LANG')||($locale == 'default')) {
			foreach ($_POST['fields'] as $value) {
				DB::query("UPDATE `".PREFIX."property_group` SET `name`=".DB::quote($value['val'])." WHERE `id`=".DB::quote($value['id']));
			}
		} else {
			foreach ($_POST['fields'] as $value) {
				MG::saveLocaleData($value['id'], $locale, 'property_group', array('name' => $value['val']));
			}
		}
		return true;
	}

	/**
	 * Получет настройки группы пользователей.
	 * @return bool
	 */
	public function getDataUserGroup() {
		if(USER::access('user') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$res = DB::query('SELECT * FROM '.PREFIX.'user_group WHERE id = '.DB::quoteInt($_POST['id']));
		while($row = DB::fetchAssoc($res)) {
			if (!empty($row['order_status'])) {
				$row['order_status'] = explode(',',$row['order_status']);
			}
			$this->data = $row;
		}
		return true;
	}

	/**
	 * Сохраняет настройки группы пользователей.
	 * @return bool
	 */
	public function saveUserGroup() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['GROUP_SAVE'];
		if (empty($_POST['data']['order_status'])) {
			$_POST['data']['order_status'] = NULL;
		} else {
			$_POST['data']['order_status'] = implode(',', $_POST['data']['order_status']);
		}
		DB::query('UPDATE '.PREFIX.'user_group SET '.DB::buildPartQuery($_POST['data']).' WHERE id = '.DB::quoteInt($_POST['id']));
		return true;
	}

	/**
	 * Добавляет новую группу пользователей.
	 * @return bool
	 */
	public function addGroup() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		DB::query('INSERT INTO '.PREFIX.'user_group (name) VALUES (\'Новая группа\')');
		$id = DB::insertId();
		$this->data['id'] = $id;
		return true;
	}

	/**
	 * Удаляет группу пользователей.
	 * @return bool
	 */
	public function dropGroup() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['GROUP_REMOVE'];
		$this->messageError = $this->lang['GROUP_REMOVE_FAIL_STANDART'];
		$res = DB::query('DELETE FROM '.PREFIX.'user_group WHERE id = '.DB::quoteInt($_POST['id']).' AND can_drop = 1');
		if(DB::affectedRows() == 0) {
			$this->data = 0;
			return false;
		}
		$this->data = 1;
		return true;
	}

	/**
	 * Загружает настройки способа оплаты.
	 * @return bool
	 */
	public function loadPayment() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$data = array();
		$res = DB::query('SELECT * FROM '.PREFIX.'payment WHERE id = '.DB::quote($_POST['id']));
		while ($row = DB::fetchAssoc($res)) {
			$data = $row;
		}
		MG::loadLocaleData($data['id'], $_POST['lang'], 'payment', $data);
		$this->data = $data;
		return true;
	}

	/**
	 * (Резервное копирование) все запросы.
	 * @return bool
	 */
	function callBackupMethod() {
		if (User::access('admin_zone') != 1 || USER::access('setting') < 2) {$this->messageError = $this->lang['ACCESS_EDIT'];return false;}

		if (!class_exists('Backup')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'backup.php';
		}

		$func = $_POST['func'];
		unset($_POST['func']);

		if($func == 'drawTable'){
		// логирование резервного копирования
		$data['id'] = '';
		$data['funcName'] = $func;
		LoggerAction::logAction('Backup', __FUNCTION__, $data);
		}

		if (!is_callable(array('Backup', $func))) {$this->messageError = 'noMethod';return false;}
		
		$res = call_user_func('Backup::'.$func);
		//$res = Backup::{$func}(); TODO

		if (is_array($res) && isset($res['messageError'])) {
			$this->messageError = $res['messageError'];
		}
		if (is_array($res) && isset($res['messageSuccess'])) {
			$this->messageSucces = $res['messageSuccess'];
		}
		if (is_array($res) && isset($res['ajaxData'])) {
			$this->data = $res['ajaxData'];
		}
		if (is_array($res) && isset($res['return'])) {
			return $res['return'];
		}

		// старая структура
		if (!is_array($res) || ($res !== null && $res !== false && !isset($res['messageError']) && !isset($res['messageSuccess']) && !isset($res['ajaxData']) && !isset($res['return']))) {
			$this->data = $res;
		}

		if ($res === false) {
			return false;
		}
		return true;
	}

	/**
	 * (1C) удаление файла импорта и экспорта 1С из логирования.
	 * @return bool
	 */
	public function dropLog() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if (!class_exists('Loger1C')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'loger1C.php';
		}
		$res = Loger1C::deleteFile($_POST['name']);
		if (!$res) {
			$this->messageError = "Нет прав доступа на удаление файла";
			return false;
		}
		$this->data = $res;

		return true;
	}
	/**
	 * (1C) список папок по дням
	 */
	public function getDays() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if (!class_exists('Loger1C')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'loger1C.php';
		}
		$list = Loger1C::getDaysList();
		$list = array_map(function($day){return str_replace('_','.',$day);}, $list);
		if (empty($list)) {
			$html = '<tr><td class="text-center" colspan="6">Файлы импорта и экспорта отсутствуют</td></tr>';
			$this->data = $html;
			return false;
		}
		$this->data = $list;
		return true;
	}

	/**
	 * (1C) список папок по времени
	 */
	public function getTimes() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if (!class_exists('Loger1C')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'loger1C.php';
		}
		$day = str_replace('.','_',$_POST['day']);
		$list = Loger1C::getTimesList($day);
		$list = array_map(function($time){return str_replace('_',':',$time);}, $list);
		$this->data = $list;
		return true;
	}

	/**
	 * (1C) HTML для таблицы с файлами
	 */
	public function getFiles() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if (!class_exists('Loger1C')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'loger1C.php';
		}
		$day = str_replace('.','_',$_POST['day']);
		$time = str_replace(':','_',$_POST['time']);
		$html = Loger1C::drawTable(TEMP_DIR.'log1c'.DS.$day.DS.$time);
		$this->data = $html;
		return true;
	}

	/**
	 * (1C) Подсчет размеров логов
	 */
	public function getSizeLogs(){
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		if (!class_exists('Loger1C')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'loger1C.php';
		}
		$size = Loger1C::getFilesSize(TEMP_DIR.'log1c');
		if ($size >= 1073741824){
			$size = number_format($size / 1073741824, 2) . ' GB';
		}
		elseif ($size >= 1048576){
			$size = number_format($size / 1048576, 2) . ' MB';
		}
		else{
			$size = number_format($size / 1024, 2) . ' KB';
		}
		$this->data = $size;
		return true;
	}

	/**
	 * (1C) Удаление всех файлов
	 */
	public function deleteAllLogs(){
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if (!class_exists('Loger1C')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'loger1C.php';
		}
		//Loger1C::deleteAllLogs();
		MG::rrmdir(TEMP_DIR.'log1c');
		return true;
	}

	/**
	 * Копирование отсутствующего блока шаблона из ядра движка в текущий шаблон.
	 * @return bool
	 */
	public function copyTemplateFile() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if (empty($_POST['file'])) {
		$_POST['file'] = basename($_POST['path']);
		}
		if (substr($_POST['file'], 0, 6) == 'email_' && empty($_POST['userAction']) && $_POST['file'] != 'email_template.php' && $_POST['file'] != 'email_order_admin.php') {
			return true;
		}
   
		$root = URL::getDocumentRoot();
		$template = MG::getSetting('templateName');
		$templateDir = $root.'mg-templates'.DS.$template.DS.$_POST['type'].DS;

		$fromDir = $root.'mg-core'.DS.$_POST['type'].DS;
		if (!is_dir($root.'mg-templates'.DS.$template.DS.$_POST['type'])) {
			mkdir($root.'mg-templates'.DS.$template.DS.$_POST['type'],0755);
		}

		if (is_file($fromDir.$_POST['file'])) {
			if (copy($fromDir.$_POST['file'], $templateDir.$_POST['file'])) {
        if (isset($_POST['userAction'])) {
          $content = file_get_contents($templateDir.$_POST['file']);
          $this->data = $content;
        }
				return true;
			}
		}
		return false;
		return true;
	}

  /**
   * Удаление файла для шаблона письма.
   * @return bool
   */
	public function deleteLetterFile() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
    $root = URL::getDocumentRoot();
    $template = MG::getSetting('templateName');
    $_POST['path'] = str_replace('/', DS, $_POST['path']);
    $templateFile = $root.'mg-templates'.DS.$template.$_POST['path'];

    if (file_exists($templateFile) && is_writable($templateFile)) {
      unlink($templateFile);
      $filename = basename($_POST['path']);
      $res = DB::query("SELECT content FROM `".PREFIX."letters` 
        WHERE name = ".DB::quote($filename));
      if ($row = DB::fetchAssoc($res)) {
        $letterContent = $row['content'];
        $this->data['filecontent'] = $letterContent;
        $this->data['description'] = MG::replaceLetterTemplate(str_replace('.php', '', $filename), $letterContent, true);
        return true;
      } elseif ($filename == 'email_order_admin.php' || $filename == 'email_template.php') {
        return true;
      }
    }
    return false;
  }

	/**
	 * Подтверждение прочтения информационного сообщения.
	 * @return bool
	 */
	public function confirmNotification() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		DB::query('UPDATE `'.PREFIX.'notification` SET `status` = 1 WHERE `id` = '.DB::quoteInt($_POST['id']));
		return true;
	}

	public function calcCountProdCat() {
		if (USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		// время
		$timeHave = 10;
		$timerSave = microtime(true);
		// достаем категории
		$cats = unserialize(stripcslashes(MG::getOption('catsCacheToCalc')));
		if(!$cats) {
			$res = DB::query('SELECT id FROM '.PREFIX.'category');
			while($row = DB::fetchAssoc($res)) {
				$cats[] = $row['id'];
			}
		}
		// узнаем количество категорий общее
		$allCats = 0;
		$res = DB::query('SELECT COUNT(id) FROM '.PREFIX.'category');
		while($row = DB::fetchAssoc($res)) {
			$allCats = $row['COUNT(id)'];
		}
		// считаем товары
		foreach ($cats as $key => $cat) {
      $sql = DB::query("SELECT left_key, right_key FROM ".PREFIX."category WHERE id = ".DB::quoteInt($cat));
      while ($res = DB::fetchAssoc($sql)) {
        $leftKey = $res['left_key'];
        $rightKey = $res['right_key'];
      }

      $products = array();
      $printNullProds = MG::getSetting('printProdNullRem') == 'true' ? ' AND p.count != 0' : '';
      $sql = DB::query("SELECT DISTINCT p.id FROM ".PREFIX."category c LEFT JOIN ".PREFIX."product p ON c.id = p.cat_id WHERE c.left_key >= $leftKey AND c.right_key <= $rightKey AND p.id >= 0 AND p.activity = 1 ".$printNullProds);
      while($res = DB::fetchAssoc($sql)) {
        $products[] = $res['id'];
      }
			// проверка количества товаров
      $products = MG::clearProductBlock($products);
      $count = count($products);
			DB::query('UPDATE '.PREFIX.'category SET countProduct = '.DB::quoteInt($count).' WHERE id = '.DB::quoteInt($cat));
			unset($cats[$key]);
			unset($catsChild);
			// время
			$timeHave -= microtime(true) - $timerSave;
			$timerSave = microtime(true);
			if($timeHave < 0) break;
		}
		MG::setOption('catsCacheToCalc', addslashes(serialize($cats)));
		$percent = ceil(($allCats - count($cats)) / ($allCats / 100));
		if($percent >= 100) {
			DB::query('DELETE FROM '.PREFIX.'setting WHERE `option` = \'catsCacheToCalc\'');
			$lang = MG::get('lang');
			$this->messageSucces = $lang['ACT_SUCCESS'];
		}
		$this->data = $percent;
		return true;
	}
	public function setKey() {
		if(User::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if(strlen($_POST['key']) !== 32) {
			return false;
		}
		MG::setOption('licenceKey', $_POST['key']);
		MG::setSetting('licenceKey', $_POST['key']);
		$post = 'version='.VER.
			'&sName='.$_SERVER['SERVER_NAME'].
			'&edition=giper'.
			'&sIP='.(($_SERVER['SERVER_ADDR'] == "::1") ? '127.0.0.1' : $_SERVER['SERVER_ADDR']).
			'&sKey='.$_POST['key'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, UPDATE_SERVER.'/updataserver');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		$resp = curl_exec($ch);
		curl_close($ch);

		if (stristr($resp,'error:')!==FALSE){
			$res = explode('error:', $resp);
		} else {
			$res = array($resp, 'false');
		}
		$data = json_decode($res[0], true);

		DB::query("
			UPDATE `".PREFIX."setting`
				SET `value`=".DB::quote($res[0])."
			WHERE `option`='currentVersion'
		");

		if (($res[1]!='false')) {
			return false;
		} else {
			$download = true;
			include CORE_LIB.'/encodeupdate.php';
			Updata::checkUpdata(true);
			return true;
		}
	}

	public function setDefaultMarginToEmptyMarginProduct() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['SET_DEFAULT_MARGIN'];
		DB::query('UPDATE '.PREFIX.'product_user_property_data 
			SET margin = (SELECT margin FROM '.PREFIX.'property_data WHERE id = prop_data_id) WHERE prop_data_id != 0 AND margin = \'\'');
		return true;
	}

	public function updateDB() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$timeLimit = 5;
		$data = array();
		if (!empty($_POST['data'])) {
			$data = $_POST['data'];
		}
		$time = microtime(true);
		$data['process'] = true;
		$data['step'] = !empty($data['step'])?$data['step']:1;
		$data['removeMessage'] = true;
		$data['line'] = 1;
		$sort = 0;

		// =========================================================================
		// переработка простых характеристик под сложные
		if($data['step'] == 1) {
			// предварительный сброс характеристик (делаеться единоразово)
			if(MG::getSetting('updateDB') < 2) {
				DB::query('UPDATE '.PREFIX.'product_user_property_data AS pupd LEFT JOIN '.PREFIX.'property AS p ON p.id = pupd.prop_id SET prop_data_id =0 WHERE p.type = \'string\'');
				MG::setOption('updateDB', 2);
			}
			// переработка простых характеристик под сложные
			$cache = array();
			$allCount = 0;
			$countSql = 'SELECT COUNT(pupd.id) AS counta FROM '.PREFIX.'product_user_property_data AS pupd
				LEFT JOIN '.PREFIX.'property AS p ON p.id = pupd.prop_id
				WHERE p.type = \'string\' AND pupd.prop_data_id = 0';
			$res = DB::query($countSql);
			if($row = DB::fetchAssoc($res)) {
				$allCount = $row['counta'];
				if(!isset($data['allCount']) || !$data['allCount']) $data['allCount'] = $allCount;
			}
			if($allCount > 0) {
				$res = DB::query('SELECT MAX(id) FROM '.PREFIX.'property_data');
				if($row = DB::fetchAssoc($res)) {
					$sort = $row['MAX(id)'];
				}

				/*

				// адаптизатор на спидах
				$res = DB::query('SELECT DISTINCT pupd.name AS pupd.name, pupd.prop_id FROM '.PREFIX.'product_user_property_data AS pupd
				 LEFT JOIN '.PREFIX.'property AS p ON p.id = pupd.prop_id
				 WHERE p.type = \'string\' AND pupd.prop_data_id = 0');
				while($row = DB::fetchAssoc($res)) {
					$names[$row['prop_id'].'/'.$row['name']]['name'] = $row['name'];
					$names[$row['prop_id'].'/'.$row['name']]['prop_id'] = $row['prop_id'];
				}

				foreach($names as $value) {
					if(!$cache[$value['name'].'/'.$value['prop_id']]) {
						$resIn = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE name = '.DB::quote($value['name']).'
							AND prop_id = '.DB::quoteInt($value['prop_id']));
						if(!$idC = DB::fetchAssoc($resIn)) {
							DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, sort) VALUES
								('.DB::quoteInt($value['prop_id']).', '.DB::quote($value['name']).', '.DB::quoteInt(++$sort).')');
							$cache[$value['name'].'/'.$value['prop_id']] = DB::insertId();
						} else {
							$cache[$value['name'].'/'.$value['prop_id']] = $idC['id'];
						}
					}

					DB::query('UPDATE '.PREFIX.'product_user_property_data SET active = 1, prop_data_id = '.DB::quoteInt($cache[$value['name'].'/'.$value['prop_id']]).'
						WHERE prop_data_id = 0 AND `name` = '.DB::quote($value['name']).' AND prop_id = '.DB::quoteInt($value['prop_id']));
					// mg::loger($value.'/'.$propId);
					// останавливаемся, если мало времени
					if(microtime(true) - $time > $timeLimit) {
						// mg::loger('exit');
						$res = DB::query($countSql);
						if($row = DB::fetchAssoc($res)) {
							$last = $row['counta'];
						}
						$data['message'] = $this->lang['UPDATE_STRING_PROP_BD'].' ('.number_format($data['allCount'] - $last).' / '.number_format($data['allCount']).')';
						$data['removeMessage'] = false;
						$this->data = $data;
						return true;
					}
				}*/

				// адаптизатор тормоз
				for($count = 0; $count < $allCount; $count+= 49) {
					if(microtime(true) - $time > $timeLimit) {
						$last = 0;
						$res = DB::query($countSql);
						if($row = DB::fetchAssoc($res)) {
							$last = $row['counta'];
						}
						$data['message'] = $this->lang['UPDATE_STRING_PROP_BD'].' ('.number_format($data['allCount'] - $last).' / '.number_format($data['allCount']).')';
						$data['removeMessage'] = false;
						$this->data = $data;
						return true;
					}
					$res = DB::query('SELECT pupd.* FROM '.PREFIX.'product_user_property_data AS pupd
						LEFT JOIN '.PREFIX.'property AS p ON p.id = pupd.prop_id
						WHERE p.type = \'string\' AND pupd.prop_data_id = 0 ORDER BY pupd.name ASC LIMIT 50');
					while($row = DB::fetchAssoc($res)) {
						if(!$cache[$row['name'].'/'.$row['prop_id']]) {
							$resIn = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE name = '.DB::quote($row['name']).'
								AND prop_id = '.DB::quoteInt($row['prop_id']));
							if(!$idC = DB::fetchAssoc($resIn)) {
								DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, sort) VALUES 
									('.DB::quoteInt($row['prop_id']).', '.DB::quote($row['name']).', '.DB::quoteInt(++$sort).')');
								$cache[$row['name'].'/'.$row['prop_id']] = DB::insertId();
							} else {
								$cache[$row['name'].'/'.$row['prop_id']] = $idC['id'];
							}
						}
						$row['prop_data_id'] = $cache[$row['name'].'/'.$row['prop_id']];
						$row['active'] = 1;
						DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE id = '.DB::quote($row['id']));
						DB::query('INSERT INTO '.PREFIX.'product_user_property_data SET '.DB::buildPartQuery($row));
					}
				}
				unset($data['allCount']);
				Storage::clear();
			}
			// для того, чтобы следующие операции уже по новому прогону пошли
			unset($data['allCount']);
			$data['removeMessage'] = true;
			$data['step'] = 2;
			$this->data = $data;
			return true;
		}
		// =========================================================================
		//
		// =========================================================================
		// восстановление и оптимизация хранения данных характеристик
		if($data['step'] == 2) {
			// активируем все строковые и текстовые характеристики
			$propIds = $propDataIds = array();
			$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE type IN (\'string\', \'textarea\')');
			while($row = DB::fetchAssoc($res)) {
				$propIds[] = $row['id'];
			}
			DB::query('UPDATE '.PREFIX.'product_user_property_data SET active = 1 WHERE prop_id IN ('.DB::quoteIN($propIds).')');
			$propIds = array();
			// удаляем все лишние характеристики
			DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE active = 0');
			DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE prop_id = 0');
			DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE product_id = 0');
			DB::query('DELETE FROM '.PREFIX.'property_data WHERE name = \'\'');
			// удаление текстаера из объединений
			$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE type = \'textarea\'');
			while($row = DB::fetchAssoc($res)) {
				$propIds[] = $row['id'];
			}
			DB::query('DELETE FROM '.PREFIX.'property_data WHERE prop_id IN ('.DB::quoteIN($propIds).')');
			$propIds = array();
			// удаление устаревших строковых из объединений
			$res = DB::query('SELECT prop_data_id FROM '.PREFIX.'product_user_property_data AS pupd
				LEFT JOIN '.PREFIX.'property AS p ON p.id = pupd.prop_id WHERE p.type = \'string\' GROUP BY pupd.prop_data_id');
			while($row = DB::fetchAssoc($res)) {
				$propDataIds[] = $row['prop_data_id'];
			}
			$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE type = \'string\'');
			while($row = DB::fetchAssoc($res)) {
				$propIds[] = $row['id'];
			}
			DB::query('DELETE FROM '.PREFIX.'property_data WHERE id NOT IN ('.DB::quoteIN($propDataIds).') AND prop_id IN ('.DB::quoteIN($propIds).')');
			unset($propDataIds);
			unset($propIds);
			// для того, чтобы следующие операции уже по новому прогону пошли
			$data['step'] = 3;
			$this->data = $data;
			return true;
		}
		// =========================================================================
		//
		// =========================================================================
		// поиск мусорных характеристик (ВСЕГДА ДОЛЖНО БЫТЬ ПОСЛЕДНИМ!)
		if($data['step'] == 3) {
			$catsAndProp = array();
			$res = DB::query('SELECT * FROM '.PREFIX.'category_user_property');
			while($row = DB::fetchAssoc($res)) {
				$catsAndProp[$row['property_id']][] = $row['category_id'];
			}
			$count = 0;
			foreach ($catsAndProp as $prop => $cat) {
				$res = DB::query('SELECT COUNT(pupd.id) AS toDel FROM '.PREFIX.'product_user_property_data AS pupd
					LEFT JOIN '.PREFIX.'product AS p ON p.id = pupd.product_id
					WHERE pupd.prop_id = '.DB::quoteInt($prop).' AND p.cat_id NOT IN ('.DB::quoteIN($cat).')');
				if($row = DB::fetchAssoc($res)) {
					$count += $row['toDel'];
				}
			}

			// отправляем инфу клиенту
			if(($count > 0)&&($_COOKIE['BannerUnused']!=='closed')) {
				$data['message'] = 'В базе найдено <b>'.$count.'</b> неиспользуемых значений характеристик! (Отвязанные характеристики <span tooltip="Значения характеристик, указанные у товара, но не привязанные к категории этого товара." flow="up"><i class="fa fa-question-circle tip" aria-hidden="true"></i></span>)<br> Их удаление ускорит и стабилизирует работу сайта и фильтрации по товарам. <b>Удалить?</b> 
					(<a role="button" href="javascript:void(0);" onclick="admin.deleteUnBindProp()" class="link deleteUnBindProp"><span>Удалить</span></a> / 
					<a role="button" href="javascript:void(0);" onclick="admin.closeBannerUnused()" class="link cancelUnBindProp"><span>Отменить</span></a>)';
				$data['removeMessage'] = false;
				$data['line'] = 2;
			}
			$data['process'] = false;
			$this->data = $data;
			return true;
		}

		return true;
	}

	public function deleteUnBindProp() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$catsAndProp = array();
		$res = DB::query('SELECT * FROM '.PREFIX.'category_user_property');
		while($row = DB::fetchAssoc($res)) {
			$catsAndProp[$row['property_id']][] = $row['category_id'];
		}
		$count = 0;
		foreach ($catsAndProp as $prop => $cat) {
			$ids = array();
			$res = DB::query('SELECT pupd.id AS toDel FROM '.PREFIX.'product_user_property_data AS pupd
				LEFT JOIN '.PREFIX.'product AS p ON p.id = pupd.product_id
				WHERE pupd.prop_id = '.DB::quoteInt($prop).' AND p.cat_id NOT IN ('.DB::quoteIN($cat).')');
			while($row = DB::fetchAssoc($res)) {
				$ids[] = $row['toDel'];
			}
			if(!empty($ids)) {
				DB::query('DELETE FROM '.PREFIX.'product_user_property_data WHERE id IN ('.DB::quoteIN($ids).')');
			}
		}
		return true;
	}

	public function getUserOrderContent() {
		if (USER::access('user') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$userEmail = '';
		$res = DB::query('SELECT IF(`email` = "", `login_email`, `email`) as email FROM '.PREFIX.'user WHERE id = '.DB::quoteInt($_POST['id']));
		if($row = DB::fetchAssoc($res)) {
			$userEmail = $row['email'];
		}
		$data = USER::getUserOrderContent($userEmail);
		$tmp = $data['products'];
		unset($data['products']);
		foreach ($tmp as $prod) {
			$prod['price'] = MG::numberFormat(round($prod['price'], 2)).' '.MG::getSetting('currency');
			$prod['add_date'] = date('d.m.Y H:i', strtotime($prod['add_date']));
			$data['products'][] = $prod;
		}
		$data['summ'] = MG::numberFormat(round($data['summ']), 2).' '.MG::getSetting('currency');
		$this->data = $data;
		return true;
	}

	public function setMassiveop() {
		if(USER::access('setting')<2){
			$data['percent'] = 100;
			$this->data = $data;
			return false;
		}
		$time = 7;
		$saveTime = microtime(true);
		$data = $_POST['data'];
		if($data['count'] == '') $data['count'] = 1;
		$tmp = array();

		if(empty($data['products'])) {
			// Этот флаг определяет, была ли создана хотя бы одна оптовая цена,
			// чтобы предупредить пользователя, если ни у одного товара не заполнено
			// дополнительное поле
			$_SESSION['setMassiveop']['opSet'] = 0;
			$res = DB::query('SELECT id FROM '.PREFIX.'product');
			while($row = DB::fetchAssoc($res)) {
				$tmp[] = $row['id'];
			}
			$data['products'] = serialize($tmp);
			$data['prodCount'] = count($tmp);
			unset($tmp);
		}

		$data['products'] = unserialize($data['products']);
		foreach ($data['products'] as $key => $productId) {
			if(microtime(true) - $saveTime > $time) {
				$data['percent'] = round(($data['prodCount'] - count($data['products'])) / ($data['prodCount'] / 100), 1);
				$data['products'] = serialize($data['products']);
				$this->data = $data;
				return true;
			}

			$opFieldsM = new Models_OpFieldsProduct($productId);
			$fields = $opFieldsM->get($data['field']);
			$skipProduct = true;
			if (!empty($fields['value'])) {
				$skipProduct = false;
			}
			if ($skipProduct && $fields['variant']) {
				foreach ($fields['variant'] as $variant) {
					if (!empty($variant['value'])) {
						$skipProduct = false;
						break;
					}
				}
				reset($fields['variant']);
			}
			if ($skipProduct) {
				continue;
			}
			$_SESSION['setMassiveop']['opSet'] = 1;
			$res = DB::query('SELECT id FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($productId).' 
				AND variant_id = 0 AND `group` = '.DB::quote($data['group']).' AND count = '.DB::quote($data['count']));
			if($row = DB::fetchAssoc($res)) {
				DB::query('UPDATE '.PREFIX.'wholesales_sys SET price = '.DB::quote($fields['value']).' WHERE id = '.DB::quoteInt($row['id']));
			} else {
				DB::query('INSERT INTO '.PREFIX.'wholesales_sys SET product_id = '.DB::quoteInt($productId).', variant_id = 0, 
					price = '.DB::quote($fields['value']).', count = '.DB::quoteFloat($data['count']).', `group` = '.DB::quote($data['group']));
			}
			if(!empty($fields['variant'])) {
				foreach ($fields['variant'] as $item) {
					$res = DB::query('SELECT id FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($productId).' 
						AND variant_id = '.DB::quoteInt($item['id']).' AND `group` = '.DB::quote($data['group']).' AND count = '.DB::quote($data['count']));
					if($row = DB::fetchAssoc($res)) {
						DB::query('UPDATE '.PREFIX.'wholesales_sys SET price = '.DB::quote($item['value']).' WHERE id = '.DB::quoteInt($row['id']));
					} else {
						DB::query('INSERT INTO '.PREFIX.'wholesales_sys SET product_id = '.DB::quoteInt($productId).', variant_id = '.DB::quoteInt($item['id']).', 
							price = '.DB::quote($item['value']).', count = '.DB::quoteFloat($data['count']).', `group` = '.DB::quote($data['group']));
					}
				}
			}
			unset($data['products'][$key]);
		}

		$this->messageSucces = $this->lang['WHOLESALES_MASSIVE_DONE'];
		$data['percent'] = 100;
		$data['products'] = serialize($data['products']);
		$this->data = $data;
		if (!$_SESSION['setMassiveop']['opSet']) {
			$this->messageError = $this->lang['WHOLESALES_MASSIVE_DONE_EMPTY'];
			return false;
		}
		return true;
	}

	public function setMassiveHoly() {
		if(USER::access('setting')<2) {
			$data['percent'] = 100;
			$this->data = $data;
			return false;
		}
		
		$time = 7;
		$saveTime = microtime(true);
		$data = $_POST['data'];
		$data['coof'] = str_replace('%', '', $data['coof']);
		$tmp = array();
		if($data['count'] == '') $data['count'] = 1;

		if(empty($data['products'])) {
			$cats = [];
			if ($data['cats'] && is_array($data['cats'])) {
				$cats = $data['cats'];
			}
			$catsWhereClause = ' WHERE `cat_id` IN ('.DB::quoteIN($data['cats']).')';
			$res = DB::query('SELECT id FROM '.PREFIX.'product'.$catsWhereClause);
			while($row = DB::fetchAssoc($res)) {
				$tmp[] = $row['id'];
			}
			$data['products'] = serialize($tmp);
			$data['prodCount'] = count($tmp);
			unset($tmp);
		}

		$data['products'] = unserialize($data['products']);
		foreach ($data['products'] as $key => $productId) {
			if(microtime(true) - $saveTime > $time) {
				$data['percent'] = round(($data['prodCount'] - count($data['products'])) / ($data['prodCount'] / 100), 1);
				$data['products'] = serialize($data['products']);
				$this->data = $data;
				return true;
			}
			$res = DB::query('SELECT id, price FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($productId));
			$variants = null;
			while($row = DB::fetchAssoc($res)) {
				$variants[$row['id']] = $row['price'];
			}
			if(!$variants) {
				$price = null;
				$res = DB::query('SELECT price FROM '.PREFIX.'product WHERE id = '.DB::quoteInt($productId));
				if($row = DB::fetchAssoc($res)) {
					$price = $row['price'];
				}
				$price *= 1 - $data['coof'] / 100;
				if($price) {
					$res = DB::query('SELECT id FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($productId).' 
						AND variant_id = 0 AND `group` = '.DB::quote($data['group']).' AND count = '.DB::quoteFloat($data['count']));
					if($row = DB::fetchAssoc($res)) {
						DB::query('UPDATE '.PREFIX.'wholesales_sys SET price = '.DB::quote($price).' WHERE id = '.DB::quoteInt($row['id']));
					} else {
						DB::query('INSERT INTO '.PREFIX.'wholesales_sys SET product_id = '.DB::quoteInt($productId).', variant_id = 0, price = '.DB::quote($price).',
							count = '.DB::quoteFloat($data['count']).', `group` = '.DB::quote($data['group']));
					}
				}
			} else {
				foreach ($variants as $varId => $varPrice) {
					$varPrice *= 1 - $data['coof'] / 100;
					if($varPrice) {
						$res = DB::query('SELECT id FROM '.PREFIX.'wholesales_sys WHERE product_id = '.DB::quoteInt($productId).' 
							AND variant_id = '.DB::quoteInt($varId).' AND `group` = '.DB::quote($data['group']).' AND count = '.DB::quote($data['count']));
						if($row = DB::fetchAssoc($res)) {
							DB::query('UPDATE '.PREFIX.'wholesales_sys SET price = '.DB::quote($varPrice).' WHERE id = '.DB::quoteInt($row['id']));
						} else {
							DB::query('INSERT INTO '.PREFIX.'wholesales_sys SET product_id = '.DB::quoteInt($productId).', variant_id = '.DB::quoteInt($varId).', 
								price = '.DB::quote($varPrice).', count = '.DB::quoteFloat($data['count']).', `group` = '.DB::quote($data['group']));
						}
					}
				}
			}
			unset($data['products'][$key]);
		}

		$this->messageSucces = $this->lang['WHOLESALES_MASSIVE_DONE'];
		$data['percent'] = 100;
		$data['products'] = serialize($data['products']);
		$this->data = $data;
		return true;
	}
	public function deleteMassiveWholesale() {
		if(USER::access('setting')<2){
			return false;
		}
		$count = $_POST['count'];
		$group = $_POST['group'];
		$sql = "DELETE FROM `".PREFIX."wholesales_sys` WHERE round(`count`,2)=".DB::quoteFloat($count)." AND `group`=".DB::quote($group);
		$result = DB::query($sql);
		if (!$result) {
			return false;
		}
		return true;
	}
	public function getMassiveWholesale() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$wholesalesList = array();
		$sql = 'SELECT DISTINCT `count`, `group` FROM `'.PREFIX.'wholesales_sys` ORDER BY `count`';
		$res = DB::query($sql);
		while ($row = DB::fetchAssoc($res)) {
			$wholesalesList[] = $row;
		}

		$this->data = $wholesalesList;
		return true;
	}
	public function mpInstallPlugin() {
		if (USER::access('plugin') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return MarketplaceMain::installPlugin($_POST['code'], $_POST['trial']);
	}
	
	public function mpVersionTemplate() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$data = MarketplaceMain::infoTemplates();
		if ($data) {
			$this->data = $data;
			return true;
		}
		return false;
	}
	
	public function mpUpdateTemplate() {
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		return MarketplaceMain::updateTemplate($_POST['code']);
	}
	public function resetMpCache() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
		}
		Storage::clear('mp-cache');
		return MarketplaceMain::update();
	}
	public function mpGetDescr() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
		}
		$data = MarketplaceMain::mpGetDescr($_POST['code']);
		if ($data) {
			$this->data = $data;
			return true;
		}
		return false;
	}
	public function mpGetResetSelect() {
		if (USER::access('admin_zone') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
		}
		$data = MarketplaceMain::getResetSelect();
		if ($data) {
			$this->data = $data;
			return true;
		}
		return false;
	}

	public function enterInUser() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$res = DB::query('SELECT * FROM '.PREFIX.'user WHERE id = '.DB::quoteInt($_POST['id']));
		if($row = DB::fetchObject($res)) {
			unset($_SESSION['user']);
			$_SESSION['user'] = new stdClass();
			$_SESSION['user']->role = -1;
			//
			$_SESSION['user'] = $row;
			$trash = MG::createHook('User_auth', $row, array());
			$this->data = SITE.'/personal';
			return true;
		}
		return false;
	}
	public function dropTemplate() {
		$this->messageSucces = $this->lang['DROP_TEMPLATE_SUCCESS'];
		$this->messageError = $this->lang['DROP_TEMPLATE_ERROR'];
		if(USER::access('setting') < 2 || $_POST['template'] == 'moguta-standard' || $_POST['template'] == MG::getSetting('templateName')) {return false;}
		$dir = SITE_DIR.'mg-templates'.DS.$_POST['template'];
		MG::rrmdir($dir);
		if (is_dir($dir)) {
			return false;
		}
		return true;
	}
	public function checkInnoSuport() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$support = false;
		$res = DB::query('SHOW ENGINES');
		while($row = DB::fetchAssoc($res)) {
		    if($row['Engine'] == 'InnoDB' && $row['Support'] != 'NO') {
		        $support = true;
		        break;
		    }
		}
		if($support) {
			DB::query('ALTER TABLE '.PREFIX.'sessions ENGINE = InnoDB', true);
		}
		$this->data = $support;
		return true;
	}

	public function setAndCheckBlock() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 1 &&
			USER::access('page') < 1 &&
			USER::access('product') < 1 &&
			USER::access('category') < 1 &&
			USER::access('setting') < 1
		) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = MG::setLockEntity($_POST['data']['table'], $_POST['data']['id']);
		return true;
	}

	public function unlockEntity() {
		if (
			USER::access('admin_zone') < 1 &&
			USER::access('plugin') < 1 &&
			USER::access('page') < 1 &&
			USER::access('product') < 1 &&
			USER::access('category') < 1 &&
			USER::access('setting') < 1
		) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = MG::unlockEntity($_POST['data']['table'], $_POST['data']['id']);
		return true;
	}

	public function saveProductOp() {
		if(USER::access('product') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		Models_OpFieldsProduct::saveFields(isset($_POST['data'])?$_POST['data']:array());
		$this->data = MG::adminLayout('op-fields.php', Models_OpFieldsProduct::getFields(), 'product');
		return true;
	}
	public function saveCategoryOp() {
		if(USER::access('category') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		Models_OpFieldsCategory::saveFields(isset($_POST['data'])?$_POST['data']:array());
		$this->data = MG::adminLayout('op-fields.php', Models_OpFieldsCategory::getFields(), 'category');
		return true;
	}
	public function saveUserOp() {
		if(USER::access('user') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		Models_OpFieldsUser::saveFields(isset($_POST['data'])?$_POST['data']:array());
		$this->data = MG::adminLayout('op-fields.php', Models_OpFieldsUser::getFields(), 'user');
		return true;
	}
	public function saveOrderFormFields() {
		if(USER::access('setting')<2){
			return false;
		}
		$orderFormFile = 'uploads'.DS.'generatedJs'.DS.'order_form.js';
		$this->messageError = $this->lang['FILE_CHMOD'].$orderFormFile;
		$this->messageSucces = $this->lang['SUCCESS_SAVE'];
		clearstatcache();
		if (!is_file(SITE_DIR.$orderFormFile)) {
			file_put_contents(SITE_DIR.$orderFormFile, '"123"');
		}
		if (!is_writable(SITE_DIR.$orderFormFile)) {
			return false;
		}
		Models_OpFieldsOrder::saveOrderFormFields();
		return true;
	}
	public function getOrderFieldsSelect() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$this->data = Models_OpFieldsOrder::getOrderFieldsSelect($_POST['data']['name']);
		return true;
	}
	public function saveOrderFormFieldConditions() {
		if(USER::access('setting')<2){
			return false;
		}
		$fields = MG::getSetting('orderFormFields',true);

		$fields[$_POST['data']['fieldId']]['conditionType'] = $_POST['data']['conditionType'];
		$fields[$_POST['data']['fieldId']]['conditions'] = !empty($_POST['data']['conditions'])?$_POST['data']['conditions']:array();

		if (!empty($_POST['data']['options'])) {
			$fields[$_POST['data']['fieldId']]['options'] = $_POST['data']['options'];
		}

		MG::setOption('orderFormFields', $fields, true);
		return true;
	}
	public function loadOrderFormConditions() {
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$fields = MG::getSetting('orderFormFields',true);
		$field = isset($fields[$_POST['data']['fieldId']])?$fields[$_POST['data']['fieldId']]:array('conditionType'=>'always');
		if ($field['conditionType'] != 'always') {
			foreach ($field['conditions'] as $key => $condi) {
				if (isset($condi['fieldType']) && in_array($condi['fieldType'], array('select','radiobutton'))) {
					$field['conditions'][$key]['html'] = Models_OpFieldsOrder::getOrderFieldsSelect($condi['fieldName']);
				}
			}
		}
		$this->data = $field;
		return true;
	}
	function dropOrderFile() {
		if(USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$order = explode('_', $_POST['file']);
		$orderId = $order[0];
		$opFieldId = $order[1];
		if (User::access('admin_zone') != 1 || !$orderId || !$opFieldId) {return false;}
		$res = DB::query("SELECT `value` FROM `".PREFIX."order_opt_fields_content` WHERE order_id = ".DB::quoteInt($orderId)." AND field_id = ".DB::quoteInt($opFieldId));
		if ($row = DB::fetchAssoc($res)) {
			$file = SITE_DIR.'uploads'.DS.'order'.DS.$orderId.DS.$row['value'];
			if (unlink($file)) {
				DB::query("DELETE FROM `".PREFIX."order_opt_fields_content` WHERE order_id = ".DB::quoteInt($orderId)." AND field_id = ".DB::quoteInt($opFieldId));
				return true;
			}
		}
		return false;
	}
	function loadAddRequisites() {
		if( USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$reqs = MG::getSetting('addRequisites',true);
		if (!empty($reqs) && is_array($reqs)) {
			foreach ($reqs as $key => $req) {
				$reqs[$key] = json_encode($req, JSON_UNESCAPED_UNICODE);
			}
		}
		$this->data = $reqs;
		return true;
	}
	function saveAddRequisites() {
		if( USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::setOption('addRequisites', $_POST['data'], true);
		unset($_SESSION['tempRequisites']);
		return true;
	}
	function adminOrderGetWholesales() {
		if( USER::access('order') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$html = '<ul class="wholesalesContainer__list">';
		$notEmpty = true;
		$res = DB::query("SELECT `wholesales` FROM `".PREFIX."user` u
			LEFT JOIN `".PREFIX."user_group` g
			ON u.`role` = g.`id`
			WHERE u.`email` = ".DB::quote($_POST['data']['email']));
		if ($row = DB::fetchAssoc($res)) {
			$curr = MG::getCurrencyShort();
			$wholesaleGroup = $row['wholesales'];
			switch (MG::getSetting('wholesalesType')) {
				case 'sum':
					$unit = $curr;
					break;
				case 'cartSum':
					$unit = $curr;
					break;
				default:
					$unit = $_POST['data']['unit'];
					break;
			}
			$res = DB::query("SELECT `count`, `price` FROM `".PREFIX."wholesales_sys` 
				WHERE `product_id` = ".DB::quoteInt($_POST['data']['id'])." AND
        		`variant_id` = ".DB::quoteInt($_POST['data']['variant'])." AND 
        		`group` = ".$wholesaleGroup." 
				ORDER BY count ASC");
			while ($row = DB::fetchAssoc($res)) {
				if ($notEmpty) {
					$notEmpty = false;
					$html = '<h4>Сетка оптовых цен:</h4>'.
							'<ul class="wholesalesContainer__list" data-wholesalegroup="'.$wholesaleGroup.'">'.
							'<a class="resetWholesales link" href="javascript:void(0);"><span>'.$this->lang['RESET_ORDER_WHOLESALES'].'</span></a>';
				}
				$html .= 	'<li class="wholesalesContainer__item">'.
								'<strong>От ' . $row['count'] . ' '.$unit.':</strong>'.
								'<span> ' . MG::numberFormat($row['price']) . ' '.$curr.'</span>'.
								' <a class="applyWholesale link" data-count="'.$row['count'].'" href="javascript:void(0);"><span>'.$this->lang['APPLY'].'</span></a>'.
							'</li>';
			}
		} else {
			$html .= '<li>'.$this->lang['USER_NOT_FOUND'].'</li>';
		}
		if ($html == '<ul class="wholesalesContainer__list">') {
			$html .= '<li>'.$this->lang['WHOLESALES_SEARCH_EMPTY'].'</li>';
		}
		$html .= '</ul>';
		$this->data = $html;
		return true;
	}
	//Метод для создании верстки списка плагинов в контекстном меню
	public function getPluginsMenu() {
		if( USER::access('plugin') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$pluginsList = PM::getPluginsInfo();
		$plugins = '';
		foreach ($pluginsList as $item) {
			if (PM::isHookInReg($item['folderName']) && $item['Active']) {
				$plugins .= '<li><a role="button" href="javascript:void(0)" class="'.$item['folderName'].'">'.$item['PluginName'].'</a></li>';
			}
		}
		$this->data['html'] = $plugins;
		return true;
	}
	function getWeightFromCat() {
		if (USER::access('product') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$res = DB::query("SELECT `weight_unit` FROM `".PREFIX."category` WHERE `id` = ".DB::quoteInt($_POST['catId']));
		if ($row = DB::fetchAssoc($res)) {
			$this->data = $row['weight_unit'];
		} else {
			$this->data = 'kg';
		}
		return true;
	}

	static function acceptDesignFromAdminbar() {
		// Проверка на попытку изменения настроек пользователем у которого нет прав
		if(USER::access('setting') != 2){
			return false;
		}
		if (!empty($_POST['colorScheme'])) {
			MG::setOption('colorScheme', $_POST['colorScheme']);
		}
		if (!empty($_POST['fontSite'])) {
			MG::setOption('fontSite', $_POST['fontSite']);
		}
		if (!empty($_POST['backgroundTextureSite'])) {
			MG::setOption('backgroundSite', '');
		}
		MG::setOption('backgroundTextureSite', $_POST['backgroundTextureSite']);

		if (!empty($_POST['colorScheme'])) {
			$template = MG::getSetting('templateName');
			if (!$userDefinedTemplateColors = MG::getSetting('userDefinedTemplateColors', 1)) {
				$userDefinedTemplateColors = array();
			}
			if (is_file(SITE_DIR.'mg-templates'.DS.$template.DS.'config.ini')) {
				$templateColors = parse_ini_file(SITE_DIR.'mg-templates'.DS.$template.DS.'config.ini',1);
        		$templateColors = !empty($templateColors['COLORS'])?$templateColors['COLORS']:[];
        	}
			if (
				$_POST['colorScheme'] == 'user_defined' &&
				!empty($_POST['userColors'])
			) {
				$userDefinedTemplateColors[$template] = $_POST['userColors'];
       			MG::setOption('userDefinedTemplateColors', $userDefinedTemplateColors, 1);

				$writeColorFile = $_POST['userColors'];
			} elseif (
				!empty($templateColors) &&
				array_key_exists($_POST['colorScheme'], $templateColors)
			) {
				$writeColorFile = $templateColors[$_POST['colorScheme']];
			}

			if (!empty($writeColorFile)) {
				$colorFileContent = ':root {'.PHP_EOL;
				foreach ($writeColorFile as $key => $value) {
					$colorFileContent .= '    --'.$key.': '.$value.';'.PHP_EOL;
				}
				$colorFileContent .= '}';
				if (!file_put_contents(SITE_DIR.'mg-templates'.DS.$template.DS.'css'.DS.'colors.css', $colorFileContent)) {
					$this->messageError = $this->lang['BACKUP_MSG_CHMOD'].'mg-templates'.DS.$template.DS.'css'.DS.'colors.css';
					return false;
				}
				if (is_dir(SITE_DIR.'mg-templates'.DS.$template.DS.'cache')) {
					MG::rrmdir(SITE_DIR.'mg-templates'.DS.$template.DS.'cache');
				}
			}
		}
		MG::clearMergeStaticFile('mg-cache/cache/');
		return true;
	}


	/**
	 * Повторная отправка письма об оформлении
	 */
	public function resendingEmailOrder() {
		if (USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$id = $_POST['id'];

		$model = new Models_Order;
		$order = $model->getOrder('id = '.$id);

		$delivery = Models_Order::getDeliveryMethod(false, $order[$id]["delivery_id"]);

		$paymentArray = $model->getPaymentMethod($order[$id]["payment_id"], false);

		$hash = $order[$id]["confirmation"];
		$link = 'ссылке <a href="'.SITE.'/order?sec='.$hash.'&id='.$id.'" target="blank">'.SITE.'/order?sec='.$hash.'&id='.$id.'</a>';

		$OFM = array();
		$opFieldsM = new Models_OpFieldsOrder($id);
		$OFM = $opFieldsM->getHumanView('all', true);
		$OFM = $opFieldsM->fixFieldsForMail($OFM);

		$productPositions = unserialize(stripslashes($order[$id]['order_content']));

		$orderWeight = 0;

		foreach ($productPositions as &$item) {
			$orderWeight += $item['count']*$item['weight'];
			$item['discountVal'] = round($item['fulPrice'], 2)-round($item['price'], 2);
			$item['discountPercent'] = round((1-round($item['price'], 2)/round($item['fulPrice'], 2))*100, 2);
			foreach ($item as &$v) {
			$v = rawurldecode($v);
			}
		}


		$phones = explode(', ', MG::getSetting('shopPhone'));

		$subj = 'Оформлен заказ №'.($order[$id]['number'] != "" ? $order[$id]['number'] : $order[$id]['id']).' на сайте '.MG::getSetting('sitename');

		$paramToMail = array(
		  'id' => $order[$id]['id'],
		  'orderNumber' => $order[$id]['number'],
		  'siteName' => MG::getSetting('sitename'),
		  'delivery' => $delivery['description'],
		  'delivery_interval' => isset($order[$id]['delivery_interval'])?$order[$id]['delivery_interval']:null,
		  'currency' => MG::getSetting('currency'),
		  'fio' => $order[$id]['name_buyer'],
		  'email' => $order[$id]['contact_email'],
		  'phone' => $order[$id]['phone'],
		  'address' => $order[$id]['address'],
		  'payment' => $paymentArray['name'],
		  'deliveryId' => $order[$id]["delivery_id"],
		  'paymentId' => $order[$id]["payment_id"],
		  'result' => $order[$id]["summ"],
		  'deliveryCost' => $order[$id]["delivery_cost"],
		  'date_delivery' => $order[$id]["date_delivery"],
		  'total' => $order[$id]["delivery_cost"] + $order[$id]["summ"],
		  'confirmLink' => $link,
		  'ip' => $order[$id]["ip"],
		  'lastvisit' => '',
		  'firstvisit' => '',
		  'supportEmail' => MG::getSetting('noReplyEmail'),
		  'shopName' => MG::getSetting('shopName'),
		  'shopPhone' => $phones[0],
		  'formatedDate' => date('Y-m-d H:i:s'),
		  'productPositions' => $productPositions,
		  'couponCode' => '',
		  'toKnowStatus' => '',
		  'userComment' => $order[$id]["user_comment"],
		  'yur_info' => unserialize(stripcslashes($order[$id]["yur_info"])),
		  'custom_fields' => $OFM,
		  'orderWeight' => $orderWeight,
		  'payHash' => $order[$id]['pay_hash'],
		  'approve_payment' => $order[$id]['approve_payment'],
		);

		if (!empty($order[$id]["address_parts"])) {
			$tmp = array_filter(unserialize(stripcslashes($order[$id]["address_parts"])));
			foreach ($tmp as $ke => $va) {
				$tmp[$ke] = htmlspecialchars_decode($va);
			}
			$paramToMail['address'] = implode(', ', $tmp);
		}

		if (!empty($order[$id]["name_parts"])) {
			$tmp = array_filter(unserialize(stripcslashes($order[$id]["name_parts"])));
			foreach ($tmp as $ke => $va) {
				$tmp[$ke] = htmlspecialchars_decode($va);
			}
			$paramToMail['fio'] = trim(implode(' ', $tmp));
		}

		$emailToUser = MG::layoutManager('email_order', $paramToMail);

		// Отправка заявки пользователю.
		Mailer::sendMimeMail(array(
			'nameFrom' => MG::getSetting('shopName'),
			'emailFrom' => MG::getSetting('noReplyEmail'),
			'nameTo' => $paramToMail['fio'],
			'emailTo' => $order[$id]["contact_email"],
			'subject' => $subj, //todo
			'body' => $emailToUser,
			'html' => true
		));

		$this->messageSucces = $this->lang['RESENDING_EMAIL_ORDER_NOTIFY'];
		return true;
	}
	/*
	* Отправка счета на почту
	* $_POST['attachment'] - вложения, какие надо приложить к письму:
	*	1 - Счет, 2 - Счет без печати, 3 - Акт по счету, 4 - Квитанция, 5 - Товарный чек, 6 - Без вложения
	*/
	public function sendingOrderPdf(){
		if (USER::access('order') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		//MG::loger($_POST);
		$id = $_POST['id'];
		$mailHeader = $_POST['header'];
		$mailBody = $_POST['text'];
		$attachment = $_POST['attachment'];
		$model = new Models_Order;
		$order = $model->getOrder('id = '.$id);
		// Если письмо без вложения
		if($attachment == 6){
			$res = Mailer::sendMimeMail(array(
				'nameFrom' => MG::getSetting('shopName'),
				'emailFrom' => MG::getSetting('noReplyEmail'),
				'nameTo' => $order[$id]['name_buyer'],
				'emailTo' => $order[$id]["contact_email"],
				'subject' => $mailHeader,
				'body' => $mailBody,
				'html' => true,
			));
			//Пишем в коммент к заказу тему сообщения	
			if($res){
				$user = USER::getThis();
				$user_id = $user->id;
				$model->addAdminCommentOrder($id, 'Отправлено письмо: '.$mailHeader, $user_id);
			}
			return $res;
		}

		//Счет 
		if($attachment == 1){
			$html = $model->printOrder($id, true, 'order', 'false');
			$fileName = 'Order-'.$order[$id]['number'].'.pdf';
		}
		//Счет без печати
		else if($attachment == 2){
			$html = $model->printOrder($id, true, 'order', 'true');
			$fileName = 'Order-'.$order[$id]['number'].'.pdf';
		}
		// Акт по счету 
		else if($attachment == 3){
			$html = $model->printOrder($id, true, 'order_act', 'false');
			$fileName = 'Order_act-'.$order[$id]['number'].'.pdf';
		}
		// Квитанция
		else if($attachment == 4){
			$html = $model->printOrder($id, true, 'qittance', 'false');
			$fileName = 'Qittance-'.$order[$id]['number'].'.pdf';
		}
		// Товарный чек
		else if($attachment == 5){
			$html = $model->printOrder($id, true, 'sales_receipt', 'false');
			$fileName = 'Sales_receipt-'.$order[$id]['number'].'.pdf';
		}

		// Подключаем библиотеку tcpdf.php
		Error_Reporting(E_ERROR | E_PARSE);
		require_once('mg-core/script/tcpdf/tcpdf.php');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->setImageScale(1.53);
		$pdf->SetFont('arial', '', 10);
		$pdf->AddPage();
		$pdf->writeHTML($html, true, false, true, false, '');
		
		$pathFile = SITE_DIR.'uploads'.DS.$fileName;
		
		$pdf->Output($pathFile, 'F');

		
		$res = Mailer::sendMimeMail(array(
			'nameFrom' => MG::getSetting('shopName'),
			'emailFrom' => MG::getSetting('noReplyEmail'),
			'nameTo' => $order[$id]['name_buyer'],
			'emailTo' => $order[$id]["contact_email"],
			'subject' => $mailHeader,
			'body' => $mailBody,
			'html' => true,
			'attach' => array(
			    array(
			    'filename' => 'uploads/'.$fileName, 		  
			    'new_name_filename' => $fileName,
			    'filetype' => "",
			    'disposition' => "attachment",
			    'resource' => '',
			    'content' =>'')
			),
		));
		
		if(file_exists($pathFile)){
			@unlink($pathFile);
		}	
		//Пишем в коммент к заказу тему сообщения	
		if($res){
			$user = USER::getThis();
			$user_id = $user->id;
			$model->addAdminCommentOrder($id, 'Отправлено письмо: '.$mailHeader, $user_id);
		}

		return $res;
	}	

	public function applyTemplateSettings() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->messageSucces = $this->lang['ACT_SAVE_SETNG'];
		$this->messageError = $this->lang['SAVE_FAIL'];
		if (is_file(SITE_DIR.'mg-templates'.DS.$_POST['template'].DS.'config.ini')) {
			$templateSettings = parse_ini_file(SITE_DIR.'mg-templates'.DS.$_POST['template'].DS.'config.ini',1);
            $templateSettingsColors = !empty($templateSettings['COLORS'])?$templateSettings['COLORS']:[];
    		$templateSettings = !empty($templateSettings['SETTINGS'])?$templateSettings['SETTINGS']:[];
    	}
    	if (!empty($templateSettings)) {

    	  // Список разрешенных опций
    	  $whiteListSettings = array(
    	    'sitename', 'backgroundColorSite', 'shopName', 'fontSite', 'shopPhone', 'shopAddress', 'priceFormat', // настройки отображения сайта
          'printSpecFilterBlock', 'printFilterResult', 'filterCountProp', 'filterMode', 'filterCountShow', 'filterSubcategory', 'searchType', 'searchInDefaultLang',
          'disabledPropFilter', 'filterCatalogMain', 'useSearchEngineInfo', 'productFilterPriceSliderStep', // фильтры и поиск по сайту
          'popupCart', 'connectZoom', 'printCompareButton', 'compareCategory', 'copyrightMoguta', 'copyrightMogutaLink', // опции сайта (общие)
          'catalogIndex', 'countСatalogProduct', 'productInSubcat', 'picturesCategory', 'showCountInCat', 'catalogPreCalcProduct', // опции сайта (категории)
          'useElectroLink','useMultiplicity', 'printProdNullRem', 'actionInCatalog', 'printRemInfo',
		  'printCount','printCode','printUnits','printBuy','printCost',
		  'printVariantsInMini', 'showVariantNull',
          'printQuantityInMini', 'printQuantityInMini', 'filterSort', 'filterSortVariant', 'prefixCode', 'useFavorites',
          'varHashProduct', 'catalogProp', 'outputMargin', 'sizeMapMod', 'printOneColor', 'recalcWholesale', 'recalcForeignCurrencyOldPrice', 'convertCountToHR', // опции сайта (товары)
          'orderNumber', 'prefixOrder', 'orderOwners', 'usePhoneMask', 'phoneMask', 'deliveryZero', 'enableDeliveryCur', // опции сайта (заказы)
          'mainPageIsCatalog', 'randomProdBlock', 'countNewProduct', 'countRecomProduct', 'countSaleProduct', // опции сайта (главная страница)
          'maxUploadImgWidth', 'maxUploadImgHeight', 'widthPreview', 'heightPreview', 'widthSmallPreview', 'heightSmallPreview',
          'categoryImgWidth', 'categoryImgHeight', 'categoryIconHeight', 'categoryIconWidth', 'propImgWidth', 'propImgHeight', // изображения (размеры картинок)
          'imageResizeType', 'imageSaveQuality', 'addDateToImg', 'waterMark', 'showMainImgVar', // изображения (прочие настройки)
	      'cacheCssJs' //Опция сохранения CSS и JS в один файл
		);
	
        $writeColorFile = MG::getOption('colorScheme');
        if (!empty($templateSettingsColors[$writeColorFile])) {
          $colorFileContent = ':root {'.PHP_EOL;
          foreach ($templateSettingsColors[$writeColorFile] as $key => $value) {
            $colorFileContent .= '    --'.$key.': '.$value.';'.PHP_EOL;
          }
          $colorFileContent .= '}';
          if (!file_put_contents(SITE_DIR.'mg-templates'.DS.$_POST['template'].DS.'css'.DS.'colors.css', $colorFileContent)) {
            $this->messageError = $this->lang['BACKUP_MSG_CHMOD'].'mg-templates'.DS.$_POST['template'].DS.'css'.DS.'colors.css';
            return false;
          }
        }

    	foreach ($templateSettings as $key => $value) {
    	  if (in_array($key, $whiteListSettings)) {
			MG::setOption($key, $value);
			if(($key == 'cacheCssJs')&&($value == 'true')){
              MG::clearMergeStaticFile(PATH_TEMPLATE.'/cache/');
              MG::createImagesForStaticFile();
              MG::createFontsForStaticFile();
            }
          }
    	}
    		return true;
    	}
		return false;
	}
	/*
	 * Настройки шаблона
	 */
	public function resetConfigTemplate() {
		USER::AccessOnly('1,4', 'exit()');
		if(USER::access('setting') != 2){
			return false;
		}
        $this->messageError = $this->lang['ERROR_RESET'];
        $templateName = MG::getSetting('templateName');
        $config = SITE_DIR.DS."mg-templates".DS.$templateName.DS.'config.ini';
        $configSample = SITE_DIR.DS."mg-templates".DS.$templateName.DS.'config-sample.ini';
        if(!file_exists($configSample)){
			return false;
        }
		
        if (!copy($configSample, $config)) {
			return false;
		}
		if (is_file(SITE_DIR.'mg-templates'.DS.$templateName.DS.'config.ini')) {
			$templateColors = parse_ini_file(SITE_DIR.'mg-templates'.DS.$templateName.DS.'config.ini',1);
			$templateColors = !empty($templateColors['COLORS'])?$templateColors['COLORS']:[];
			if(!empty($templateColors[1])){
				MG::setOption(array('option' => 'colorScheme', 'value' => 1));
			}
		}
        return true;
	}

	public function saveConfigTemplate() {
		//mg::loger($_POST['data']);
		if(!empty($_POST['data']) && USER::access('setting') == 2){
			USER::AccessOnly('1,4', 'exit()');
			$this->messageSucces = $this->lang['SAVE_OK'];
			$this->messageError = $this->lang['SAVE_FAIL'];
			$ConfigEditor = new ConfigEditorPublic();

			$templateName = MG::getSetting('templateName');
			$currentLocale = $_POST['locale'];
			$templateConfig = SITE_DIR.DS."mg-templates".DS.$templateName.DS.'config.ini';
			$templateLocale = SITE_DIR.DS."mg-templates".DS.$templateName.DS.'config-'.$currentLocale.'.ini';
			if(!empty($currentLocale) && $currentLocale != 'default'){
				if(!file_exists($templateLocale)){
					if (!copy($templateConfig, $templateLocale)) {
						return false;
					}
				}
				$templateConfig = $templateLocale;
			}

			if(!file_exists($templateConfig) || !is_readable($templateConfig)){
				return false;
			}

			if(!isset($_POST['data'])){
				$this->messageError = $this->lang['SAVE_FAIL2'];
				return false;
			}
			$ConfigEditor->init($templateConfig);
			foreach($_POST['data'] as $line){
				if(isset($line['section'])&&isset($line['key'])&&isset($line['value'])){
					$ConfigEditor->setOption($line['section'],$line['key'],$line['value']);
				}
			}
			$ConfigEditor->saveConfigIni();
		}
		Storage::clear();
		return true;
	}

	public function checkEngine(){
		if (USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_VIEW'];
			return false;
		}
		$res = CheckEngine::getSettingsFromBd();
		$this->data=['settingsEngine' => $res];
		return true;
	}

	//Применение стандартных настроек
	public function resetSettings(){
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		//Сперва делаем бэкапчик 

		if(!is_dir(SITE_DIR.'backups'.DS)) {
			mkdir(SITE_DIR.'backups', 0755);
		}
		if (!class_exists('Backup')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'backup.php';
		}
		$filename = SITE_DIR.'backups'.DS.'mysqldump.sql';
		Backup::createDBbackup($filename, ['`'.PREFIX.'setting`']);
		$dumpName = Backup::packDB();
		// После бэкапчика начинаем основной процесс
		DB::query("DROP TABLE IF EXISTS `".PREFIX."setting_default`");
		
		DB::query("CREATE TABLE `".PREFIX."setting_default`
					LIKE `".PREFIX."setting`");
		
		DB::query("INSERT INTO `".PREFIX."setting_default`
					SELECT *
					FROM `".PREFIX."setting`");	

		if(class_exists('CheckEngine')){
			$res = DB::query("SELECT `option`, `value` FROM `".PREFIX."setting_default`");
			while($row = DB::fetchAssoc($res)){
				if(in_array($row['option'], CheckEngine::$notUsedSettings)) continue;
				if(isset(CheckEngine::$settingDef[$row['option']])){
					$newVal = CheckEngine::$settingDef[$row['option']];
					DB::query("UPDATE `".PREFIX."setting_default` SET `value` = ".DB::quote($newVal)." WHERE `".PREFIX."setting_default`.`option` = ".DB::quote($row['option']));
				} 
			}
		}
		MG::setOption('backupSettingsFile', $dumpName);
		DB::query("UPDATE `".PREFIX."setting_default` SET `value` = 'true' WHERE `".PREFIX."setting_default`.`option` = 'useDefaultSettings'");
		DB::query("UPDATE `".PREFIX."setting` SET `value` = 'true' WHERE `".PREFIX."setting`.`option` = 'useDefaultSettings'");
		return true;
	}

	//Применение кастомных настроек после своих
	public function customSettings(){
		if (USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		/*
		if (!class_exists('Backup')) {
			include URL::getDocumentRoot().'mg-admin'.DS.'lib'.DS.'backup.php';
		}
		$filename = MG::getSetting('backupSettingsFile');
		if(!file_exists(SITE_DIR.'backups'.DS.$filename.'.zip')){
			return false;
		}
		$db = Backup::getZipType($filename.'.zip');
		if(isset($db['error'])){
			return false;
		}
		Backup::restoreDBbackup();*/
		DB::query("UPDATE `".PREFIX."setting` SET `value` = 'false' WHERE `".PREFIX."setting`.`option` = 'useDefaultSettings'");
		MG::setOption('useDefaultSettings', 'false');
		DB::query("DROP TABLE IF EXISTS `".PREFIX."setting_default`");
		//Нужно ли удалять файл?
		//@unlink(SITE_DIR.'backups'.DS.$filename.'.zip');
		return true;
	}


	public function toggleUseTemplatePluginsSetting() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$useTemplatePlugins = MG::getSetting('useTemplatePlugins');

		$plugins = PM::getPluginsInfo();

		$result = false;
		if ($useTemplatePlugins) {
			$removeTemplateFromPluginsSql = 'UPDATE `'.PREFIX.'plugins` '.
				'SET `template` = '.DB::quote('').';';
			$result = DB::query($removeTemplateFromPluginsSql);
			$newValue = '0';
		} else {
			$result = true;
			$templatePluginsPath = SITE_DIR.'mg-templates'.DS.MG::getSetting('templateName').DS.'mg-plugins'.DS;
			$templatePlugins = array_diff(scandir($templatePluginsPath), ['.','..']);
			$addTemplatePlugins = [];
			foreach ($plugins as $plugin) {
				if (in_array($plugin['folderName'], $templatePlugins)) {
					$addTemplatePlugins[] = $plugin['folderName'];
				}
			}
			if ($addTemplatePlugins) {
				$addTemplateSql = 'UPDATE `'.PREFIX.'plugins` '.
					'SET `template` = '.DB::quote(MG::getSetting('templateName')).' '.
					'WHERE `folderName` IN ('.DB::quoteIN($addTemplatePlugins).');';
				$result = DB::query($addTemplateSql);
			}
			$newValue = '1';
		}

		MG::setOption('useTemplatePlugins', $newValue);
		MG::setSetting('useTemplatePlugins', $newValue);

		$this->data = ['newValue' => $newValue];
		return $result;
	}

	/**
	 * Возвращает список доступных шаблону опций
	 */
	public function getTemplateOptions() {
		USER::AccessOnly('1,4', 'exit()');
		if (empty($_POST['templateName'])) {
			return false;
		}
		$templateName = $_POST['templateName'];
		$data = [
			'installQuickstart' => 0,
			'applySettings' => 0,
			'reinstallMakets' => 0,
			'resetUserCss' => 0,
			'resetLocales' => 1,
			'installTemplatePlugins' => 0
		];
		if (MarketplaceMain::templateHasQuickstart($templateName)) {
			$data['installQuickstart'] = 1;
		}

		if (EDITION === 'saas') {
			$cloudConfig = CloudCore::getConfigIni()['array'];
			if (!empty($cloudConfig['SETTINGS'])) {
				$data['applySettings'] = 1;
			}

			$data['reinstallMakets'] = 1;

			$cloudUserCSS = CloudCore::readUserCSS(); 
			if (!empty($cloudUserCSS)) {
				$data['resetUserCss'] = 1;
			}
		}

		if (MG::getTemplatePlugins($templateName)) {
				$data['installTemplatePlugins'] = 1;
		}

		$this->data = $data;
		return true;
	}

	public function applyPropValueToAllProducts() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		if (empty($_POST['propValueId'])) {
			return false;
		}
		$propValueId = $_POST['propValueId'];
		$propNewValue = '';
		
		if (!empty($_POST['propNewValue']) && floatval($_POST['propNewValue'])) {
			$propNewValue = floatval($_POST['propNewValue']);
		}

		$updateProductPropertyDataSql = 'UPDATE `'.PREFIX.'product_user_property_data` '.
			'SET `margin` = '.DB::quote($propNewValue).' WHERE `prop_data_id` = '.DB::quoteInt($propValueId).';';
		return DB::query($updateProductPropertyDataSql);
	}


	
	/**
	 *  Обновление статусов для дашборда новичка
	 */
	public function updateStatusDashboard() {
		if(USER::access('setting') < 2) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}

		$noviceList = array(
			'step1' => 0,
			'step2' => 0,
			'step3' => 0,
			'step4' => 0,
			'step5' => 0,
			'step6' => 0,
		);

		foreach($_POST['noviceList'] as $step){
			$stepName="step".intval($step);
			$noviceList[$stepName] = 1;
		}

		MG::setOption(array('option' => 'noviceList', 'value' => addslashes(serialize($noviceList))));

		$data['testresponse'] = 1;
		$this->data = $data;
		return true;
	}

	/**
	 *  Не выводить прогрессбар новичка для создания магазина
	 */
	public function progressBarNoviceDisable() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		MG::setOption(array('option' => 'noviceListProgressBar', 'value' => 'false'));
		return true;
	}

	/**
	 *  Выводить прогрессбар новичка для создания магазина
	 */
	public function progressBarNoviceEnable() {
		if(USER::access('setting') < 1) {
			$this->messageError = $this->lang['ACCESS_EDIT'];
			return false;
		}
		$this->updateStatusDashboard();
		MG::setOption(array('option' => 'noviceListProgressBar', 'value' => 'true'));
		return true;
	}

	/**
	 * YML импорт товаров
	 */

  /*
   * Очистка каталога при импорте YML
   */
  public function clearCatalogYML(){
	if(
		USER::access('admin_zone') < 1 &&
		USER::access('product') < 2 &&
		USER::access('category') < 2
	) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}
	
    $this->messageSucces = $this->lang['CLEAR_CATALOG_SUCCESS'];

	$result = ymlImport::clearCatalog();

	return $result;
  }

  /*
   * Загрузка файла импорта
   */
  public function uploadFileToImportYML()
  {
    USER::AccessOnly('1,4', 'exit()');

    $tempData = $this->addImportCatalogFileYML();
    $this->data = array('file' => $tempData['actualImageName']);

    if ($tempData['status'] == 'success') {
      $this->messageSucces = $tempData['msg'];
      return true;
    } else {
      $this->messageError = $tempData['msg'];
      return false;
    }
  }

  /**
   * Загружает  файл для импорта каталога
   * @param $filename - путь к файлу на сервере
   * @return boolean
   */
  public function addImportCatalogFileYML($url = false){
    USER::AccessOnly('1,4', 'exit()');
		ymlImport::init();
    $validFormats = array('xml', 'zip');
    //$realDocumentRoot = URL::getDocumentRoot(false);

    //$path = $realDocumentRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    $path = SITE_DIR.'uploads/';

    if ($url) {
			
      ymlImport::getFileByUrl($url);

      if (file_exists($path . 'yml.xml')) {
        return true;
      }

      return false;
    }

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {

      if (!empty($_FILES['upload_data_file'])) {
        $file_array = $_FILES['upload_data_file'];
      }

      $name = $file_array['name'];
      $size = $file_array['size'];

      if (strlen($name)) {
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        if (in_array(strtolower($ext), $validFormats)) {
          if (!empty($file_array['tmp_name'])) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
            copy($file_array['tmp_name'], $path . 'yml.' . $ext);
            unlink($file_array['tmp_name']);

            if (strtolower($ext) == 'zip') {
              if (file_exists($path . 'yml.zip')) {
                $zip = new ZipArchive;
                $res = $zip->open($path . 'yml.zip', ZIPARCHIVE::CREATE);

                if ($res === TRUE) {
                  for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $fullName = explode('.', $zip->getNameIndex($i));
                    $ext = array_pop($fullName);

                    if ($ext == 'xml') {
                      $zip->extractTo('uploads/', array($filename));
                      rename('uploads/' . $filename, "uploads/yml.xml");
                    }
                  }

                  $zip->close();
                  unlink($path . 'yml.zip');
                }
              }
            }

            return array('msg' => 'Файлы подготовлены', 'status' => 'success');
          } else {
            return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD1'], 'status' => 'error');
          }
        } else {
          return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD2'], 'status' => 'error');
        }
      } else {
        return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD3'], 'status' => 'error');
      }
    }
    return true;
  }

  public function importCatalogYML() {
	if(
		USER::access('admin_zone') < 1 &&
		USER::access('product') < 2 &&
		USER::access('category') < 2
	) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}
		ymlImport::init();
    $arParams = $_POST['data'];
    $this->messageSucces = $this->lang['SUCCESS_IMPORT'];
    $this->messageError = $this->lang['IMPORT_ERROR'];
    $this->data['message'] = '';

    $step = isset($_POST['step']) ? $_POST['step'] : 0;

    if (!empty($arParams['url_data_file']) && $_POST['step'] == 0) {
      $startAddFileTime = microtime(true);
      $maxAddFileTime = ymlImport::getMaxExecTime();
      if (!self::addImportCatalogFileYML(trim($arParams['url_data_file']))) {
        $this->messageError = $this->lang['UPLOAD_FILE_ERROR'];
        return false;
      } elseif ((microtime(true) - $startAddFileTime) > $maxAddFileTime) {
        $this->data = ['step' => $step, 'message' => $this->lang['UPLOAD_FILE_SUCCESS']];
        return true;
      }
    }

    $file = SITE_DIR . 'uploads' . DS . 'yml.xml';
    $priceModifier = isset($arParams['priceModifier']) ?
      floatval(str_replace(',', '.', $arParams['priceModifier'])) :
      1;

    $onlyPrice = (!empty($arParams['onlyPrice']) &&
      $arParams['onlyPrice'] != 'false') ?
      true : false;
    $importCategory = (!empty($arParams['import_category']) &&
      $arParams['import_category'] != 'false') ?
      true : false;
    $importInCategory = $arParams['import_in_category'];

    $newFileStructure = (!empty($arParams['new_file_structure']) &&
      $arParams['new_file_structure'] != 'false') ?
      true : false;

	$forceName = false;
	$forceCompany = false;
	if (!empty($arParams['forceName']) && !empty($arParams['forceCompany'])) {
		$forceName = trim($arParams['forceName']);
		$forceCompany = trim($arParams['forceCompany']);
	}

    if (($onlyPrice || !$importCategory) && $step < 3) {
      $step = 3;
    }
    if (!is_file($file) && $step < 4) {
      $this->messageError = $this->lang['UPLOAD_FILE_ERROR'];
      return false;
    }

    $resultData = false;
    switch ($step) {
      case 0:
        $resultData = ymlImport::setFileId($file, $forceName, $forceCompany);
        if (!empty($resultData['error'])) {
          $this->messageError = $resultData['error'];
          return false;
        }
		$skipRootCat = false;
		if ($arParams['skipRootCat'] === 'true') {
			$skipRootCat = true;
		}
        $resultData = ymlImport::parseCatsToDb($file, $skipRootCat);
        break;
      case 1:
        $resultData = ymlImport::importCats();
        break;
      case 2:
        $resultData = ymlImport::indexCatsAndClearCache();
        break;
      case 3:
		$onlyNewProductsImages = false;
		if ($arParams['onlyNewProductsImages'] === 'true') {
			$onlyNewProductsImages = true;
		}
		if (empty($_SESSION['yml_import']['fileId'])) {
			$resultData = ymlImport::setFileId($file, $forceName, $forceCompany);
			if (!empty($resultData['error'])) {
				$this->messageError = $resultData['error'];
				return false;
			}
		}
        $resultData = ymlImport::parseProductsToDb($file, $priceModifier, $onlyPrice, $importCategory, $importInCategory, $newFileStructure, $onlyNewProductsImages);
        break;
      case 4:
        $resultData = ['message' => $this->lang['SUCCESS_IMPORT']];
        unset($_SESSION['yml_import']);
				if(file_exists($file)){
          unlink($file);
			  }
        break;
      case 5:
        @set_time_limit(0);
        $resultData = ymlImport::parseImagesToProducts();
        break;
    }
    if (!$resultData) {
      return false;
    }
    $this->data = $resultData;
    return true;
  }


  /*
   * Получение лимита памяти для php скрипта
   */
  public function getMemorySizePhpYML(){
	if(
		USER::access('admin_zone') < 1 &&
		USER::access('product') < 1 &&
		USER::access('category') < 1
	) {
		$this->messageError = $this->lang['ACCESS_VIEW'];
		return false;
	}
    $memory_limit = ini_get('memory_limit');
    if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
        if ($matches[2] == 'M') {
            $memory_limit = $matches[1] * 1024 * 1024;
        } else if ($matches[2] == 'K') {
            $memory_limit = $matches[1] * 1024;
        }
    }
    $this->data['memorySize'] = intval($memory_limit);
    return true;
  }

  public function getImagesCountYML() {
	if(
		USER::access('admin_zone') < 1 &&
		USER::access('product') < 1 &&
		USER::access('category') < 1
	) {
		$this->messageError = $this->lang['ACCESS_VIEW'];
		return false;
	}
		ymlImport::init();
    $imagesCount = ymlImport::getImagesCount();
    $this->data = [
      'imagesCount' => $imagesCount
    ];
    return true;
  }

  public function togglePaymentLog() {
	if(USER::access('setting') < 2) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}
	if (
		empty($_POST['code']) ||
		empty($_POST['active'])
	) {
		return false;
	}
	$code = $_POST['code'];
	$active= $_POST['active'] === 'true';
	$result = Models_Payment::togglePaymentLog($code, $active);
	return $result;
  }

  public function clearPaymentLogs() {
	if (USER::access('setting') < 2) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}
	if (empty($_POST['code'])) {
		return false;
	}
	$code = $_POST['code'];
	$result = Models_Payment::clearLogs($code);
	return $result;
  }

  public function getLastOrderId() {
	if (USER::access('order') < 1) {
		$this->messageError = $this->lang['ACCESS_VIEW'];
		return false;
	}
	$lastOrderId = Models_Order::getLastOrderId();
	$this->data = [
		'lastOrderId' => $lastOrderId,
	];
	return true;
  }

  public function getProductFieldSAndPropsAvito() {
	if (USER::access('setting') < 1) {
		$this->messageError = $this->lang['ACCESS_VIEW'];
		return false;
	}

    $productFields = [
		[
			'value' => 'title',
			'title' => 'Название',
		],
		[
			'value' => 'description',
			'title' => 'Описание',
		],
		[
			'value' => 'price',
			'title' => 'Цена',
		],
		[
			'value' => 'url',
			'title' => 'Ссылка на товар',
		],
		[
			'value' => 'image_url',
			'title' => 'Ссылка на изображение',
		],
		[
			'value' => 'code',
			'title' => 'Артикул',
		],
		[
			'value' => 'count',
			'title' => 'Количество',
		],
		[
			'value' => 'old_price',
			'title' => 'Старая цена',
		],
		[
			'value' => 'weight',
			'title' => 'Вес',
		],
		[
			'value' => 'currency_iso',
			'title' => 'Валюта',
		],
		[
			'value' => 'short_description',
			'title' => 'Краткое описание',
		],
		[
			'value' => 'unit',
			'title' => 'Единицы измерения количества',
		],
		[
			'value' => 'weight_unit',
			'title' => 'Единицы измерения веса',
		],
	];
  
	$productOptFieldsSql = 'SELECT `id`, `name` '.
		'FROM `'.PREFIX.'product_opt_fields`';
	$productOptFieldsResult = DB::query($productOptFieldsSql);
	while($productOptFieldsRow = DB::fetchAssoc($productOptFieldsResult)) {
		$productFields[] = [
			'value' => 'opf_'.$productOptFieldsRow['id'],
			'title' => $productOptFieldsRow['name'].' (Дополнительное поле)',
		];
	}

	$props = [];
	$propsSql = 'SELECT `id`, `name` '.
		'FROM `'.PREFIX.'property`';
	$propsResult = DB::query($propsSql);
	while ($propsRow = DB::fetchAssoc($propsResult)) {
		$props[] = [
			'value' => $propsRow['id'],
			'title' => $propsRow['name'],
		];
	}

	$this->data = [
		'productFields' => $productFields,
		'props' => $props,
	];

	return true;
  }

  public function recalculateStorages() {
	if (USER::access('setting') < 2) {
		$this->messageError = $this->lang['ACCESS_EDIT'];
		return false;
	}

	if (!empty($_POST['reset'])) {
		unset($_SESSION['RECALCULATE_STORAGES']);
	}

	$offset = 0;
	if (!empty($_SESSION['RECALCULATE_STORAGES'])) {
		$offset = intval($_SESSION['RECALCULATE_STORAGES']);
	}

	$productModel = new Models_Product();
	$recalculationResult = $productModel->recalculateStoragesAll($offset);

	if ($recalculationResult['complete']) {
		$this->data = [
			'complete' => 1,
		];
		MG::setOption('showStoragesRecalculate', 'false');
		MG::setSetting('showStoragesRecalculate', 'false');
		return true;
	}

	if (
		$recalculationResult['total'] &&
		$recalculationResult['processed']
	) {
		$total = intval($recalculationResult['total']);
		$processed = intval($recalculationResult['processed']);

		$_SESSION['RECALCULATE_STORAGES'] = intval($processed);

		$rate = $processed / $total;
		$percents = floor(100 * $rate);

		$this->data = [
			'percents' => $percents,
		];
		return true;
	}

	return false;
  }

  public function searchTemplateFiles() {
	  if (USER::access('setting') < 1) {
		  $this->messageError = $this->lang['ACCESS_VIEW'];
		  return false;
	  }

	  if (empty($_POST['searchString'])) {
		  return false;
	  }

	  $views = 0;
	  if (!empty($_POST['views'])) {
		$views = 1;
	  }

	  $searchString = trim($_POST['searchString']);
	  $currentTemplate = MG::getSetting('templateName');
	  $templatesDir = SITE_DIR.'mg-templates';
	  $templateDir = $templatesDir.DS.$currentTemplate;

	  $lang = MG::get('lang');

	  if ($views) {
		$files_template = array( 
			'template.php'=> array('/template.php', $lang['layout__template']), 		
			'index.php'=> array('/views/index.php', $lang['layout__index']),	
			'product.php'=> array('/views/product.php',$lang['layout__productCart']),
			'catalog.php'=> array('/views/catalog.php', $lang['layout__catalog']),
			'order.php'=> array('/views/order.php', $lang['layout__orderPage']),
			'cart.php'=> array('/views/cart.php', $lang['layout__cart']),        
			'enter.php'=> array('/views/enter.php', $lang['layout__authPage']),
			'feedback.php'=> array('/views/feedback.php', $lang['layout__feedbackPage']),
			'forgotpass.php'=> array('/views/forgotpass.php', $lang['layout__forgotPass']),       
			'personal.php'=> array('/views/personal.php', $lang['layout__userAccount']),        
			'registration.php'=> array('/views/registration.php', $lang['layout__registrationPage']),        
			'compare.php'=> array('/views/compare.php', $lang['layout__comparePage']),
			'group.php'=> array('/views/group.php', $lang['layout__newRecSalePages']),
			'payment.php'=> array('/views/payment.php', $lang['layout__paymentOrderPage']),
			'ajaxuser.php'=> array('/ajaxuser.php', $lang['layout__userAjax']),
			'functions.php'=> array('/functions.php', $lang['layout__functions']),
			'404.php'=> array('/404.php', $lang['layout__404']),
			);
		$filestmp = scandir('mg-templates'.DS.MG::getSetting('templateName').'/views'); 
		foreach ($filestmp as $namefile) {
			if($namefile=='template.php'){continue;}// оставляем template.php на первом месте
		  if (!isset($files_template[$namefile])&&$namefile!='.'&&$namefile!='..') {
			$files_template[$namefile]= array('/views/'.$namefile, '');
		  }
		}

		foreach ($files_template as $filename => $path) {
			$filePath = $templateDir.$path[0];
			if (!file_exists($filePath)) {
				unset($files_template[$filename]);
			}
			$fileContent = file_get_contents($filePath);
			if (strpos($fileContent, $searchString) === false) {
				unset($files_template[$filename]);
			}
		}

		$this->data = [
			'views' => $files_template,
		];
  
		return true;
	  }

	  // стартовый набор компонентов для шаблона
	  $layout_template = [
		  'layout_cart.php' => ['/layout/layout_cart.php', $lang['layout__smallCart']],
		  'layout_contacts.php' => ['/layout/layout_contacts.php', $lang['layout__contacts']],
		  'layout_related.php' => ['/layout/layout_related.php', $lang['layout__connectedItem']],
		  'layout_search.php' => ['/layout/layout_search.php', $lang['layout__search']],
		  'layout_topmenu.php' => ['/layout/layout_topmenu.php', $lang['layout__topMenu']],
		  'layout_leftmenu.php' => ['/layout/layout_leftmenu.php', $lang['layout__leftMenu']],
		  'layout_images.php' => ['/layout/layout_images.php', $lang['layout__gallery']],
		  'layout_auth.php' => ['/layout/layout_auth.php', $lang['layout__auth']],
		  'layout_contacts_mobile.php' => ['/layout/layout_contacts_mobile.php', $lang['layout__mobileContacts']],
		  'layout_horizontmenu.php' => ['/layout/layout_horizontmenu.php', $lang['layout__horizontalMenu']],
		  'layout_property.php' => ['/layout/layout_property.php', $lang['layout__characteristicsFormAndBuyButton']],
		  'layout_relatedcart.php' => ['/layout/layout_relatedcart.php', $lang['layout__addSaleItems']],
		  'layout_subcategory.php' => ['/layout/layout_subcategory.php', $lang['layout__nestedCategories']],
		  'layout_variant.php' => ['/layout/layout_variant.php', $lang['layout__itemOptions']],
		  'layout_agreement.php' => ['/layout/layout_agreement.php', $lang['layout__userAgreement']],
		  'layout_apply_filter.php' => ['/layout/layout_apply_filter.php', $lang['layout__filters']],
		  'layout_contacts-bar.php' => ['/layout/layout_contacts-bar.php', $lang['layout__contacts']],
		  'layout_count_product.php' => ['/layout/layout_count_product.php', $lang['layout__numberOfItems']],
		  'layout_htmlproperty.php' => ['/layout/layout_htmlproperty.php', $lang['layout__itenChars']],
		  'layout_mockup_news.php' => ['/layout/layout_mockup_news.php', $lang['layout__news']],
		  'layout_pagination.php' => ['/layout/layout_pagination.php', $lang['layout__pagNav']],
		  'layout_op_fields.php' => ['/layout/layout_op_fields.php', $lang['layout__addFieldsInOrder']],
		  'layout_btn_buy.php' => ['/layout/layout_btn_buy.php', $lang['layout__buyButton']],
		  'layout_btn_compare.php' => ['/layout/layout_btn_compare.php', $lang['layout__compareButton']],
		  'layout_btn_more.php' => ['/layout/layout_btn_more.php', $lang['layout__viewMoreButton']],
		  'layout_currency_select.php' => ['/layout/layout_currency_select.php', $lang['layout__chooseCurrency']],
		  'layout_filter.php' => ['/layout/layout_filter.php', $lang['layout__filterblock']],
		  'layout_prop_filter.php' => ['/layout/layout_prop_filter.php', $lang['layout__filtersChars']],
		  'layout_layout_group.php' => ['/layout/layout_group.php', $lang['layout__linksToGroups']],
		  'layout_icons.php' => ['/layout/layout_icons.php', $lang['layout__templateIcons']],
		  'layout_language_select.php' => ['/layout/layout_language_select.php', $lang['layout__chooseLanguage']],
		  'layout_mini_product.php' => ['/layout/layout_mini_product.php', $lang['layout__miniCard']],
		  'layout_order_storage.php' => ['/layout/layout_order_storage.php', $lang['layout__stockInOrder']],
		  'layout_storage_info.php' => ['/layout/layout_storage_info.php', $lang['layout__stockInItem']],
		  'layout_prop_string.php' => ['/layout/layout_prop_string.php', $lang['layout__textChars']],
		  'payment_alfabank.php' => ['/layout/payment_alfabank.php', $lang['layout__paymentAlfa']],
		  'payment_interkassa.php' => ['/layout/payment_interkassa.php', $lang['layout__paymentInterkassa']],
		  'payment_liqpay.php' => ['/layout/payment_liqpay.php', $lang['layout__paymentLiqpay']],
		  'payment_payanyway.php' => ['/layout/payment_payanyway.php', $lang['layout__paymentPayanyway']],
		  'payment_paymaster.php' => ['/layout/payment_paymaster.php', $lang['layout__paymentPaymaster']],
		  'payment_paypal.php' => ['/layout/payment_paypal.php', $lang['layout__paymentPaypal']],
		  'payment_privat24.php' => ['/layout/payment_privat24.php', $lang['layout__paymentPrivat24']],
		  'payment_qiwi.php' => ['/layout/payment_qiwi.php', $lang['layout__paymentQiwi']],
		  'payment_quittance.php' => ['/layout/payment_quittance.php', $lang['layout__paymentByProps']],
		  'payment_robokassa.php' => ['/layout/payment_robokassa.php', $lang['layout__paymentRobok']],
		  'payment_sberbank.php' => ['/layout/payment_sberbank.php', $lang['layout__paymentSber']],
		  'payment_tinkoff.php' => ['/layout/payment_tinkoff.php', $lang['layout__paymentTinkoff']],
		  'payment_webmoney.php' => ['/layout/payment_webmoney.php', $lang['layout__paymentWebmoney']],
		  'payment_yandex-kassa.php' => ['/layout/payment_yandex-kassa.php', $lang['layout__paymentYandexK']],
		  'payment_yandex.php' => ['/layout/payment_yandex.php', $lang['layout__paymentYandexD']],
		  'payment_megakassa.php' => ['/layout/payment_megakassa.php', $lang['layout__paymentMegakassa']],
		  'payment_qiwi-api.php' => ['/layout/payment_qiwi-api.php', $lang['layout__paymentQiwiApi']],
		  'payment_qrcode.php' => ['/layout/payment_qrcode.php', $lang['layout__paymentqrcode']],
	  ];

	  // файлы из папки layouts шаблона
	  $templateLayoutsDir = $templateDir.DS.'layout';
	  if (is_dir($templateLayoutsDir)) {
		  $layouts = array_diff(scandir($templateLayoutsDir), ['.', '..']); 
	  } else {
		  $layouts = [];
	  }

	  // перебираем файлы из папки layouts шаблона и те из них, которые начинаются на layout_ или payment_
	  // записываем в компоненты шаблона
	  foreach ($layouts as $layoutFileName) {   
		  if (
			  (
				  strpos($layoutFileName, 'layout_') === 0 ||
				  strpos($layoutFileName, 'payment_') === 0
			  ) &&
			  !isset($layout_template[$layoutFileName])
		  ) {
			  $layout_template[$layoutFileName]= [DS.'layout'.DS.$layoutFileName, ''];
		  }
	  }

	  // сортировка компонентов шаблона
	  ksort($layout_template,SORT_STRING);

	  // перебираем содержимое components шаблона и записываем в компоненты те файлы, что имеют расширение php или html

	  $configIniFile = $templateDir.DS.'config.ini';
	  $componentsDir = $templateDir.DS.'components';
	  if (
		  is_file($configIniFile) && 
		  is_dir($componentsDir)
	  ) {
		  $files = MG::getFilesR($componentsDir, ['php', 'html']);
		  if (!empty($files)) {
			  foreach ($files as $file) {
				  $file['pathName'] = str_replace($componentsDir.DS, '', $file['path']);

				  $tmp = explode(DS, $file['pathName']);
				  $componentName = $tmp[0];

				  $file['path'] = str_replace($templateDir.DS, '', $file['path']);

				  if (
					  $file['ext'] === 'php' ||
					  $file['ext'] === 'html'
				  ) {
					  $layout_template[$file['pathName']] = [DS.$file['path'], $file['path'], $componentName];
				  }
			  }
		  }
	  }

	  foreach ($layout_template as $filename => $path) {
		  $filePath = $templateDir.DS.$path[0];
		  if (!file_exists($filePath)) {
			  unset($layout_template[$filename]);
		  }
		  $fileContent = file_get_contents($filePath);
		  if (strpos($fileContent, $searchString) === false) {
			  unset($layout_template[$filename]);
		  }
	  }

	  $this->data = [
		  'layouts' => $layout_template,
	  ];

	  return true;
  }
}
