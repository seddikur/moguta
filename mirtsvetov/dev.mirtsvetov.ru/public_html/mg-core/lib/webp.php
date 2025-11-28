<?php 
/**
 * Класс Webp - предназначен для конвертирования изображений в формат webp
 *
 * Реализован в виде синглтона, что исключает его дублирование.
 *
 * @author Шевченко Александр Станиславович
 * @package moguta.cms
 * @subpackage Libraries
 */

 class Webp{
    static private $_instance = null;
	  public static $retutn = true;
    private static $dirsBlacklist = [
      SITE_DIR.DS.'uploads'.DS.'webp',
    ];
    private function __construct(){
        self::getInstance();
    }

    /**
     * Возвращает единственный экземпляр данного класса.
     * @access private
     * @return object объект класса LoggerAction.
     */
    static public function getInstance() {
        if(is_null(self::$_instance)) {
          self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Метод для подсчёта количества изображений
     * 
     * @param array $objects Массив объектов (каталог или файл) которые нужно конвертировать в webp
     * @param string $path Абсолютный путь к директории, которая содержит переданные объекты
     * @param int $startTime Время начала работы скрипта в секундах
     * 
     * @return int|bool количество изображений, которые можно конвертировать webp, false в случае ошибки, true в случае исли превышен maxExecTime
     */
    public static function getCountImg($objects, $path = SITE_DIR, $startTime = null) {
      $path = rtrim($path, DS);
      // время начала работы метода
      if (!$startTime) {
        $startTime = microtime(true); 
      }
      $maxExecTime = 30; // максимальное время работы скрипта (по умолчанию 30)
      $maxExecIniTime = intval(ini_get('max_execution_time')); // максимальное время работы скрипта из директивы php
      if ($maxExecIniTime && $maxExecIniTime < $maxExecTime) {
        $maxExecTime = $maxExecIniTime; 
      }
      $execTimeShift = 5; // смещение максимального времени работы скрипта

      foreach ($objects as $object) {
        $execTime = microtime(true) - $startTime; // время работы метода
        // если время работы метода больше максимально (с учётом смещения)
        if ($execTime > ($maxExecTime - $execTimeShift)) {
          // возвращаем true
          $_SESSION['WEBP']['SKIP_COUNT'] = true;
          return true;
        }

        $objectAbsolutePath = $path.DS.$object;
        if (is_dir($objectAbsolutePath)) {
          if (in_array($objectAbsolutePath, self::$dirsBlacklist)) {
            continue;
          }
          $subObjects = array_diff(scandir($objectAbsolutePath), ['.', '..']);
          $subDirResult = self::getCountImg($subObjects, $objectAbsolutePath, $startTime);
          if (is_bool($subDirResult)) {
            return $subDirResult;
          }
          $_SESSION['WEBP']['COUNT_LAST_DIR'] = $objectAbsolutePath;
          continue;
        }

        if (is_file($objectAbsolutePath)) {
          if (!empty($_SESSION['WEBP']['SKIP_COUNT'])) {
            if ($_SESSION['WEBP']['COUNT_LAST_FILE'] !== $objectAbsolutePath) {
              continue;
            }
          }
          if (preg_match('/(.jpg|.jpeg|.png)$/is', $object)) {
            $_SESSION['WEBP']['TOTAL']++;
            $_SESSION['WEBP']['SKIP_COUNT'] = false;
            $_SESSION['WEBP']['COUNT_LAST_FILE'] = $objectAbsolutePath;
          } 
        }
      }

      $totalCount = $_SESSION['WEBP']['TOTAL'];
      return $totalCount;
    }


    /**
     * Метод для конвертации изображений в webp
     * 
     * @param array $objects Массив объектов (каталог или файл) которые нужно конвертировать в webp
     * @param string $path Абсолютный путь к директории, которая содержит переданные объекты
     * @param int $startTime Время начала работы скрипта в секундах
     * 
     * @return int|bool количество изображений, которые можно конвертировать webp, false в случае ошибки, true в случае исли превышен maxExecTime
     */
    public static function convertImgToWebp($objects, $path = SITE_DIR, $startTime = null) {
      $webpLogFile = SITE_DIR.'uploads'.DS.'temp'.DS.'lastWebpImage';
      $path = rtrim($path, DS);
      if (!$startTime) {
        $startTime = microtime(true);
      }
      $maxExecTime = 30; // максимальное время работы скрипта (по умолчанию 30)
      $maxExecIniTime = intval(ini_get('max_execution_time')); // максимальное время работы скрипта из директивы php
      if ($maxExecIniTime && $maxExecIniTime < $maxExecTime) {
        $maxExecTime = $maxExecIniTime; 
      }
      $execTimeShift = 5; // смещение максимального времени работы скрипта

      $allowedExts = [
        'jpg',
        'jpeg',
        'png',
      ];

      foreach ($objects as $object) {
        $execTime = microtime(true) - $startTime; // время работы метода
        // если время работы метода больше максимально (с учётом смещения)
        if ($execTime > ($maxExecTime - $execTimeShift)) {
          // возвращаем true
          return true;
        }

        $objectAbsolutePath = $path.DS.$object;
        if (is_dir($objectAbsolutePath)) {
          if (in_array($objectAbsolutePath, self::$dirsBlacklist)) {
            continue;
          }
          $subObjects = array_diff(scandir($objectAbsolutePath), ['.', '..']);
          $subDirResult = self::convertImgToWebp($subObjects, $objectAbsolutePath, $startTime);
          if (is_bool($subDirResult)) {
            return $subDirResult;
          }
          $_SESSION['WEBP']['LAST_DIR'] = $objectAbsolutePath;
          continue;
        }

        if (is_file($objectAbsolutePath)) {
          if (!empty($_SESSION['WEBP']['SKIP']) && !empty($_SESSION['WEBP']['LAST_FILE'])) {
            if ($_SESSION['WEBP']['LAST_FILE'] !== $objectAbsolutePath) {
              continue;
            }
            $_SESSION['WEBP']['SKIP'] = false;
          }
          if (preg_match('/(.jpg|.jpeg|.png)$/is', $object)) {
            // Базовая проверка расширения
            $objectParts = explode('.', $object);
            $extension = array_pop($objectParts);

            // Проверка расширения если есть exif
            if (function_exists('exif_imagetype')) {
              $extensionsNums = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
              ];
              $extensionNum = exif_imagetype($objectAbsolutePath);
              if ($extensionNum && !empty($extensionsNums[$extensionNum])) {
                $extension = $extensionsNums[$extensionNum];
              }
            }

            // Проверка что расширение в списке разрешенных
            if (!in_array($extension, $allowedExts)) {
              $_SESSION['WEBP']['LAST_FILE'] = $objectAbsolutePath;
              continue;
            }

            $webpObjectParts = $objectParts;
            $webpObjectParts[] = 'webp';
            $webpObject = implode('.', $webpObjectParts);
            $webpPath = str_replace(SITE_DIR, SITE_DIR.'uploads'.DS.'webp'.DS, $path);
            $webpObjectAbsolutePath = $webpPath.DS.$webpObject;

            if (is_file($webpObjectAbsolutePath)) {
              $_SESSION['WEBP']['COMPLETE']++;
              $_SESSION['WEBP']['LAST_FILE'] = $objectAbsolutePath;
              continue;
            }

            file_put_contents($webpLogFile, str_replace(SITE_DIR, '', $objectAbsolutePath));
  
            $objectImage = false;
            switch ($extension) {
              case 'jpeg':
              case 'jpg':
                $objectImage = imagecreatefromjpeg($objectAbsolutePath);
                break;
              case 'png':
                $objectImage = imagecreatefrompng($objectAbsolutePath);
                if ($objectImage !== false) {
                  imagepalettetotruecolor($objectImage);
                  imagealphablending($objectImage, true);
                  imagesavealpha($objectImage, true);
                }
                break;
            }

            if ($objectImage === false) {
              $invalidImageUrl = str_replace(DS, '/', str_replace(SITE_DIR, SITE.'/', $objectAbsolutePath));
              $_SESSION['WEBP']['LOG'] .= 'Не удалось обработать "'.$invalidImageUrl.'"! Изображение повреждено или расширение в названии файла не соответствует реальному.'."\n";
              $_SESSION['WEBP']['COMPLETE'] += 1;
              $_SESSION['WEBP']['LAST_FILE'] = $objectAbsolutePath;
              continue;
            }

            if (!is_dir($webpPath)) {
              mkdir($webpPath, 0755, true);
            }

            imagewebp($objectImage, $webpObjectAbsolutePath);
            imagedestroy($objectImage);
            $_SESSION['WEBP']['COMPLETE'] += 1;
            $_SESSION['WEBP']['LAST_FILE'] = $objectAbsolutePath;
          }
        }
      }

      $totalCount = $_SESSION['WEBP']['COMPLETED'];
      return $totalCount;
    }
	  
	/**
     * Преобразует изображения в webp
     * @param string $content html верстка компонетна
     * @return string
     */
    
    public static function changeImgForWebp($content){
      //Парсим регуляркой все изображения
      /*
      $regEx = '@[="|=\'|\(]( *[\.d\:|\\s\w+\+\-\/]*[\-\w]+\.(?>jpg|png|jpeg).*?)[\"|\'|\)]@S';
      */
      $currentUser = USER::getThis();
      if ($currentUser && $currentUser->enabledSiteEditor === 'true') {
        return $content;
      }

      $replaceArr = [];

      $regEx = '@[="|=\'|\(]( *[\.d\:|\\s\w+\+\-\/]*[\-\w\,№!\@\#\$\%\^\&\*×)\(]+\.(?>jpg|png|jpeg)).*?[\"|\'|\)]@iS';
      preg_match_all($regEx, $content, $mathes, PREG_PATTERN_ORDER);
      if(!empty($mathes[1])){
        foreach($mathes[1] as $origImageLink){
          $decodedImageLink = urldecode($origImageLink);
          $imageParts = explode('.', $decodedImageLink);
          array_pop($imageParts);
          $imageParts[] = 'webp';
          $webpImage = implode('.', $imageParts);
          $webpLink = str_replace(SITE, SITE.'/uploads/webp', $webpImage);
          $webpFile = rtrim(SITE_DIR, DS).str_replace('/', DS, str_replace(SITE, '', $webpLink));
          if (is_file($webpFile)) {
            $replaceArr[$origImageLink] = $webpLink;
          }
        }
      }

      if ($replaceArr) {
        $content = str_replace(array_keys($replaceArr), array_values($replaceArr), $content);
      }

      return $content;
    }

	  /**
     * Заменяет ссылку изображения на картинку webp. Если такой картинки нет, вернет ссылку обратно
     * @param string $imgUrl - заменяемый url
     * @return string 
     */
    public static function changeImg($imgUrl){
      $changeImgUrl = preg_replace('/(.jpg|.png|.jpeg)/is', '.webp', $imgUrl);
      $changeImgUrl = str_replace('uploads', '', substr($changeImgUrl, strpos($changeImgUrl, 'uploads')));
      $imgUrlNew = SITE.DS.'uploads'.DS.'webp'.$changeImgUrl;
      $dirImg = SITE_DIR.'uploads'.DS.'webp'.$changeImgUrl;
      if(file_exists($dirImg)){
        return $imgUrlNew;
      }else{
        return $imgUrl;
      }
    }

    /**
     * Проверяет, поддерживает ли браузер формат webp
     * @return bool 
     */
    public static function checkUserBrowser(){
      if(strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false ) {
        return true;
      }else{
        return false;
      }     
    }

    /**
     * Создает изображение в формате webp для определенной картинки
     * @param string $content html верстка компонетна
     * @return bool 
     */
    public static function createWebpImg($imgUrl){
      if(function_exists('imagewebp') && MG::getSetting('useWebpImg') == 'true'){
        @set_time_limit(0);
        list($width_uploaded, $height_uploaded) = getimagesize($imgUrl);
        $maxUploadWidth = MG::getSetting('maxUploadImgWidth');
        if (!$maxUploadWidth) {$maxUploadWidth = 1500;}
        $maxUploadHeight = MG::getSetting('maxUploadImgHeight');
        if (!$maxUploadHeight) {$maxUploadHeight = 1500;}

        if ($width_uploaded > $maxUploadWidth || $height_uploaded > $maxUploadHeight) {
          return false;
        }
        $dirOfImg = str_replace(SITE_DIR, '', dirname($imgUrl));
        $dirArr = explode(DS, $dirOfImg);

        if(!is_dir('uploads'.DS.'webp')){
          mkdir('uploads'.DS.'webp', 0755, true);
        }
        $mainDir = 'uploads'.DS.'webp';
        foreach($dirArr as $dir){
          if(!is_dir($mainDir.DS.$dir)){
            mkdir($mainDir.DS.$dir, 0755, true);
          }
          $mainDir = $mainDir.DS.$dir;
        }
        $webpImgPath = SITE_DIR.$mainDir.DS;
        $webpImgPath .= preg_replace('/(.jpg|.jpeg|.png)$/is', '.webp', basename($imgUrl));
        $pathInfo = strtolower(pathinfo($imgUrl, PATHINFO_EXTENSION));
        
        if(!file_exists($webpImgPath)){
          $img = false;
          if($pathInfo == 'jpeg' || $pathInfo == 'jpg'){
            $img = imagecreatefromjpeg($imgUrl);	
          }else if($pathInfo == 'png'){
            $img = imagecreatefrompng($imgUrl);
            if ($img !== false) {
              imagepalettetotruecolor($img);
              imagealphablending($img, true);
              imagesavealpha($img, true);
            }
          }
          if ($img !== false) {
            imagewebp($img, $webpImgPath);				  
          }
        }
        return true;  
      }else{
        return false;
      }
    }
       

 }