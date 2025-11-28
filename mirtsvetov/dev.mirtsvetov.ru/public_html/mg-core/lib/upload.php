<?php

/**
 * Класс для загрузки изображений на сервер, в том числе и через визуальный редактор ckeditor.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Upload {

  public static $lang = array();

  public function __construct($ckeditMode = true, $uploadDir = 'image-content') {
    if (!$uploadDir) {$uploadDir = 'image-content';}
    $uploadDirPath = SITE_DIR.'uploads'.DS.$uploadDir;
    if (!is_dir($uploadDirPath)) {
      mkdir($uploadDirPath, 0755, true);
    }
    $lang = array();
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
    self::$lang = $lang;
    if ($ckeditMode) {
      $arrData = self::addImage(false, false, $uploadDir);
      $msg = $arrData['msg'];
      if ($arrData['status'] == "error") {
        echo '<script>window.parent.CKEDITOR.tools.callFunction('.$_REQUEST['CKEditorFuncNum'].',  "","'.$arrData['msg'].'" );</script>';
      } else {
        $full_path = SITE.'/uploads/'.$arrData['actualImageName'];
        echo '<script>window.parent.CKEDITOR.tools.callFunction("'.$_REQUEST['CKEditorFuncNum'].'",  "'.$full_path.'","'.$arrData['msg'].'" );</script>';
      }
    }
  }

  /**
   * Загружает картинку из формы на сервер.
   * <code>
   * $uploader = new Upload(false);
   * $result = $uploader->addImage(true);
   * viewData($result);
   * </code>
   * @param bool $productImg изображения для товара
   * @param bool $watermark нужен ли водяной знак
   * @param string $addPath путь загрузки
   * @return array|bool массив с путем сохраненной картинки или ошибкой или false
   */
  public function addImage($productImg = false, $watermark = false, $addPath = '') {


    $path = 'uploads/';


    if (!empty($_FILES['landingBackground'])) {
      if(!is_dir('uploads/landings/')){
        $curDir = getcwd();
        chdir('uploads/'); //путь где создавать папку  
        mkdir('landings', 0755);  //Создаем папку для изображений
        chdir($curDir);
      }
      $addPath = 'landings';
    }

    if (!empty($_FILES['customBackground']) || !empty($_FILES['customAdminLogo'])) {
      if(!is_dir('uploads/customAdmin/')){
        $curDir = getcwd();
        chdir('uploads/'); //путь где создавать папку  
        mkdir('customAdmin', 0755);  //Создаем папку для изображений
        chdir($curDir);
      }
      $addPath = 'customAdmin';
    }
    
    $resizeType = MG::getSetting("imageResizeType");
    
    if(empty($resizeType)){
      $resizeType = 'PROPORTIONAL';
    }
    
    if($_COOKIE['type'] == 'plugin' && !isset($_REQUEST['CKEditor'])){
      //Если из плагина не задан параметр для обработки изображений как для товаров
      if(empty($_SESSION[$_COOKIE['section'].'-upload-to-product'])){
        $addPath = $_COOKIE['section'];
        $resizeType = 'PROPORTIONAL';
      }
    }

    $validFormats = array('jpeg', 'jpg', 'png', 'gif', 'JPG');

    if ($watermark) {
      $path.="watermark/";
      if (!file_exists('uploads/watermark/')) {
        if (is_writable('uploads/')) {
          $curDir = getcwd();
          chdir('uploads/'); //путь где создавать папку   
          mkdir('watermark', 0755); //имя папки и атрибуты на папку 
          chdir($curDir);
          return array('msg' => "Папка для знака была восстановлена. Теперь можно загрузить картинку.", 'status' => 'success');
        }
      }

      $validFormats = array('png');

      $resizeType = 'PROPORTIONAL';
    }

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {

      if (!empty($_FILES['upload'])) {
        $file_array = $_FILES['upload'];
      } elseif (!empty($_FILES['photoimg'])) {
        $file_array = $_FILES['photoimg'];
      } elseif (!empty($_FILES['landingBackground'])) {
        $file_array = $_FILES['landingBackground'];
      } elseif (!empty($_FILES['customBackground'])) {
        $file_array = $_FILES['customBackground'];
      } elseif (!empty($_FILES['customAdminLogo'])) {
        $file_array = $_FILES['customAdminLogo'];
      } else {
        $file_array = $_FILES['edit_photoimg'];
      }

      $name = str_replace(array('30_', '70_'), '', $file_array['name']);
      $size = $file_array['size'];
     
      if (strlen($name)) {
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $ext2 = str_replace('image/', '', $file_array['type']);
        $name = implode('.', $fullName);
        if ($ext == 'svg+xml') {
          $ext = 'svg';
        }

        if (strpos($ext2, 'svg') !== false || strtolower($ext) == 'svg' || strpos($ext2, 'webp') !== false || strtolower($ext) == 'webp') {
          $noMinis = true;
        }
        else{
          $noMinis = false;
        }

    
        if (in_array(strtolower($ext2), $validFormats) || $noMinis) {

          if (($size < (1024 * 100 * 1024) && !empty($file_array['tmp_name'])) || $noMinis) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
            $name = rawurldecode($name);
            $name = str_replace(array(" ", "%"), array("-", ""), $name);    
            $name = MG::translitIt($name);
            $actualImageName = self::prepareName($name, $ext);

            if ($watermark && empty($_FILES['landingBackground']) && !$noMinis) {
              $actualImageName = 'watermark.png';
            }
            $tmp = $file_array['tmp_name'];
            
            if($addPath == 'prodtmpimg' || ($productImg && !$watermark && empty($_FILES['landingBackground']))){
              $addPath = 'prodtmpimg';
              $actualImageName = str_replace('.', '', microtime(1)).'_-_time_-_'.$actualImageName;
            }
            
            if(!empty($addPath)){  //Если задана дополнительная директория для изображения
              if(!file_exists('uploads/'.$addPath.'/')){ //Проверяем наличие папки
                $curDir = getcwd();
                chdir('uploads/'); 
                mkdir($addPath, 0755);  //Создаем папку для изображений
                chdir($curDir);
              }
              $addPath .= '/';
              $path .= $addPath;
            }
            
            if (move_uploaded_file($tmp, $path.$actualImageName) || copy($tmp, $path.$actualImageName)) {
              @chmod($path.$actualImageName, 0777);

              if (!$noMinis) {
                list($width_uploaded, $height_uploaded) = getimagesize($path.$actualImageName);
                $maxUploadWidth = MG::getSetting('maxUploadImgWidth');
                if (!$maxUploadWidth) {$maxUploadWidth = 1500;}
                $maxUploadHeight = MG::getSetting('maxUploadImgHeight');
                if (!$maxUploadHeight) {$maxUploadHeight = 1500;}

                if ($width_uploaded > $maxUploadWidth || $height_uploaded > $maxUploadHeight) {
                  return array('msg' => "Изображение ".$actualImageName." не обработано. Слишком большое разрешение.", 'status' => 'error');
                }
              }
              if (MG::getSetting("waterMark") == "true" && !$watermark && $productImg && empty($_FILES['landingBackground']) && !$noMinis) {
                if (empty($_POST['noWaterMark'])) {
                  self::addWatterMark($path.$actualImageName);
                }
              }

              //если картинка заливаются для продукта, то делаем две миниатюры
              if ($productImg && !$watermark && empty($_FILES['landingBackground'])) {
                $bigImg = $smallImg = '';
                
                if(!file_exists('uploads/'.$addPath.'thumbs/')){
                  $curDir = getcwd();
                  chdir('uploads/'.$addPath); 
                  mkdir('thumbs', 0755);  //Создаем папку для изображений
                  chdir($curDir);
                }

                if (!$noMinis) {
                  //подготовка миниатюр с заданными в настройках размерами
                  // preview по заданным в настройках размерам
                  $widthPreview = MG::getSetting('widthPreview') ? MG::getSetting('widthPreview') : 200;
                  $widthSmallPreview = MG::getSetting('widthSmallPreview') ? MG::getSetting('widthSmallPreview') : 50;
                  $heightPreview = MG::getSetting('heightPreview') ? MG::getSetting('heightPreview') : 100;
                  $heightSmallPreview = MG::getSetting('heightSmallPreview') ? MG::getSetting('heightSmallPreview') : 50;
                  $bigImg = self::_reSizeImage('70_'.$actualImageName, $path.$actualImageName, $widthPreview, $heightPreview, $resizeType, 'uploads/'.$addPath.'thumbs/');
                  // миниатюра по размерам из БД (150*100)
                  $smallImg = self::_reSizeImage('30_'.$actualImageName, $path.$actualImageName, $widthSmallPreview, $heightSmallPreview, $resizeType, 'uploads/'.$addPath.'thumbs/');
                  if ($resizeType != 'EXACT') {
                    clearstatcache();
                    if (is_file('uploads/'.$addPath.$actualImageName) &&
                        is_file('uploads/'.$addPath.'thumbs/70_'.$actualImageName) &&
                        filesize('uploads/'.$addPath.'thumbs/70_'.$actualImageName) >
                        filesize('uploads/'.$addPath.$actualImageName)) {
                      copy('uploads/'.$addPath.$actualImageName, 'uploads/'.$addPath.'thumbs/70_'.$actualImageName);
                    }
                    if (is_file('uploads/'.$addPath.$actualImageName) &&
                        is_file('uploads/'.$addPath.'thumbs/30_'.$actualImageName) &&
                        filesize('uploads/'.$addPath.'thumbs/30_'.$actualImageName) >
                        filesize('uploads/'.$addPath.$actualImageName)) {
                      copy('uploads/'.$addPath.$actualImageName, 'uploads/'.$addPath.'thumbs/30_'.$actualImageName);
                    }
                  }
                  @chmod('uploads/'.$addPath.'thumbs/70_'.$actualImageName, 0777);
                  @chmod('uploads/'.$addPath.'thumbs/30_'.$actualImageName, 0777);

                  if (MG::getSetting('imageResizeRetina') == 'true') {
                    $retina30 = self::_reSizeImage('2x_30_'.$actualImageName, $path.$actualImageName, ($widthSmallPreview*2), ($heightSmallPreview*2), $resizeType, 'uploads/'.$addPath.'thumbs/');
                    $retina70 = self::_reSizeImage('2x_70_'.$actualImageName, $path.$actualImageName, ($widthPreview*2), ($heightPreview*2), $resizeType, 'uploads/'.$addPath.'thumbs/');
                    clearstatcache();
                    if (
                        (
                          $resizeType != 'EXACT' && 
                          is_file('uploads/'.$addPath.$actualImageName) &&
                          is_file('uploads/'.$addPath.'thumbs/2x_30_'.$actualImageName) &&
                          filesize('uploads/'.$addPath.'thumbs/2x_30_'.$actualImageName) > filesize('uploads/'.$addPath.$actualImageName)
                        ) ||
                        !$retina30
                    ) {
                      copy('uploads/'.$addPath.$actualImageName, 'uploads/'.$addPath.'thumbs/2x_30_'.$actualImageName);
                    }
                    if (
                        (
                          $resizeType != 'EXACT' &&
                          is_file('uploads/'.$addPath.$actualImageName) &&
                          is_file('uploads/'.$addPath.'thumbs/2x_70_'.$actualImageName) &&
                          filesize('uploads/'.$addPath.'thumbs/2x_70_'.$actualImageName) > filesize('uploads/'.$addPath.$actualImageName)
                        ) ||
                        !$retina70
                    ) {
                      copy('uploads/'.$addPath.$actualImageName, 'uploads/'.$addPath.'thumbs/2x_70_'.$actualImageName);
                    }
                  }
                }
                else{
                  if (copy($path.$actualImageName, 'uploads/'.$addPath.'thumbs/70_'.$actualImageName)) {
                    $bigImg = 'noMini';
                    @chmod('uploads/'.$addPath.'thumbs/70_'.$actualImageName, 0777);
                  }
                  if (copy($path.$actualImageName, 'uploads/'.$addPath.'thumbs/30_'.$actualImageName)) {
                    $smallImg = 'noMini';
                    @chmod('uploads/'.$addPath.'thumbs/30_'.$actualImageName, 0777);
                  }                  
                  if (MG::getSetting('imageResizeRetina') == 'true') {
                    copy($path.$actualImageName, 'uploads/'.$addPath.'thumbs/2x_30_'.$actualImageName);
                    copy($path.$actualImageName, 'uploads/'.$addPath.'thumbs/2x_70_'.$actualImageName);
                  }
                }
                
                if (!$bigImg || !$smallImg) {
                  return array('msg' => "Изображение ".$actualImageName." не обработано. Слишком большое разрешение.", 'status' => 'error');
                }
              }
              return array('msg' => self::$lang['ACT_IMG_UPLOAD'], 'actualImageName' => $addPath.$actualImageName, 'status' => 'success');
            } else {
              return array('msg' => self::$lang['ACT_IMG_NOT_UPLOAD'], 'status' => 'error');
            }
          } else {
            return array('msg' => self::$lang['ACT_IMG_NOT_UPLOAD1'], 'status' => 'error');
          }
        } else {
          return array('msg' => self::$lang['ACT_IMG_NOT_UPLOAD2'], 'status' => 'error');
        }
      } else {
        return array('msg' => self::$lang['ACT_IMG_NOT_UPLOAD3'], 'status' => 'error');
      }

    }
    return false;
  }

  /**
   * Проверяет существует ли уже в папке uploads файл с таким же именем.
   * Если существует, то имя текущего файла будет дополненно текущем временем.
   * <code>
   * echo Upload::prepareName('image', 'png');
   * </code>
   * @return string $name название
   * @return string $ext расширение файла
   * @return string
   */
  public function prepareName($name, $ext) {
    if (file_exists('uploads/'.$name.".".$ext)) {
      return $name.time().".".$ext;
    }
    return $name.".".$ext;
  }

  /**
   * Функция для масштабирования изображения.
   * <code>
   * $uploader = new Upload(false);
   * $uploader->_reSizeImage(
   *   '70_15216337030455_-_time_-_slide5.jpg',
   *   'uploads/prodtmpimg/15216337030455_-_time_-_slide5.jpg'
   *   540,
   *   348,
   *   'PROPORTIONAL',
   *   'uploads/prodtmpimg/thumbs/'
   * );
   * </code>
   * @param string $name имя файла
   * @param string $tmp исходный временный файл
   * @param int $widthSet заданная ширина изображения
   * @param int $heightSet заданная высота изображения
   * @param string $resizeType тип сжатия: PROPORTIONAL|EXACT
   * @param string $dirUpload папка для загрузки изображения
   * @return bool
   */
  public function _reSizeImage($name, $tmp, $widthSet, $heightSet, $resizeType="PROPORTIONAL", $dirUpload = 'uploads/thumbs/', $ignoreSize = false, $forCats = false){
    if (MG::getSetting('thumbsProduct') == 'false' && !$forCats) {
      return true;
    }
    
    @mkdir($dirUpload, 0755);
    $fullName = explode('.', $name);
    $ext = array_pop($fullName);
    $name = implode('.', $fullName);

    list($width_orig, $height_orig) = getimagesize($tmp);
    $start_x = 0;
    $start_y = 0;
    $sWidth = $width_orig;
    $sHeight = $height_orig;
    
    $maxUploadWidth = 1500;
    if (MG::getSetting('maxUploadImgWidth')) {$maxUploadWidth = MG::getSetting('maxUploadImgWidth');}
    $maxUploadHeight = 1500;
    if (MG::getSetting('maxUploadImgHeight')) {$maxUploadHeight = MG::getSetting('maxUploadImgHeight');}
    
    if (!$ignoreSize && ($width_orig > $maxUploadWidth || $height_orig > $maxUploadHeight)) {
      return false;
    }
    
    if($width_orig < 2 || $width_orig <= $widthSet && $height_orig <= $heightSet){
      if (count($fullName) === 0) { //Если без расширения
        copy($tmp, $dirUpload.$ext);
      } else {
        copy($tmp, $dirUpload.$name.'.'.$ext);
      }
      return true;
    }
    
    if($resizeType == "EXACT"){ //масштабируем в прямоугольник $widthSet*$heightSet c сохранением пропорций, обрезая лишнее
      $width = ($width_orig < $widthSet) ? $width_orig : $widthSet;
      $height = ($height_orig < $heightSet) ? $height_orig : $heightSet;         
     
      $scale = ($width_orig / $height_orig > $width / $height) ? 
        $height / $height_orig : $width / $width_orig;
      
      $start_x = max(0, round($width_orig / 2 - ($width / 2) / $scale));
      $start_y = max(0, round($height_orig / 2 - ($height / 2) / $scale));
      
      $sWidth = round($width / $scale, 0);
      $sHeight = round($height / $scale, 0);
    }else{  //масштабируем с сохранением пропорций, размер ограничивается заданными параметрами $widthSet и $heightSet
      $widthCoef = $widthSet / $width_orig;
      $heightCoef = $heightSet / $height_orig;
      
      $resizeCoef = min($widthCoef, $heightCoef);
      $resizeCoef = ((0 < $resizeCoef) && ($resizeCoef < 1) ? $resizeCoef : 1);
      
      $width = max(1, intval($resizeCoef * $width_orig));
      $height = max(1, intval($resizeCoef * $height_orig));
    }
    
    $quality = intval(MG::getSetting('imageSaveQuality'));
    
    $image_p = imagecreatetruecolor($width, $height);
    imageAlphaBlending($image_p, false);
    imageSaveAlpha($image_p, true);

    // вывод
    switch ($ext) {
      case 'png':
        $image = imagecreatefrompng($tmp);
        if (is_bool($image) && !$image) {
          imagedestroy($image_p);
          return false;
        }
        
        //делаем фон изображения белым, иначе в png при прозрачных рисунках фон черный
        //$black = imagecolorallocate($image, 0, 0, 0);
        // Сделаем фон прозрачным
        //imagecolortransparent($image, $black);

        imagealphablending($image_p, false);   
        $col = imagecolorallocate($image_p, 255, 255, 255);
        imagefilledrectangle($image_p, 0, 0, $width, $height, $col);
       // imageSaveAlpha($image_p, true);

        $quality = 10 - ceil($quality / 10);
        $quality = ($quality > 9) ? 9 : $quality;
        imagecopyresampled($image_p, $image, 0, 0, $start_x, $start_y, $width, $height, $sWidth, $sHeight);       
        
        if (count($fullName) === 0) { //Если без расширения
          imagepng($image_p, $dirUpload.$ext, $quality);
        } else {
          imagepng($image_p, $dirUpload.$name.'.'.$ext, $quality);
        }
        break;

      case 'gif':
        $image = imagecreatefromgif($tmp);
        if (is_bool($image) && !$image) {
          imagedestroy($image_p);
          return false;
        }
        imagecopyresampled($image_p, $image, 0, 0, $start_x, $start_y, $width, $height, $sWidth, $sHeight);

        if (count($fullName) === 0) { //Если без расширения
          imagegif($image_p, $dirUpload.$ext);
        } else {
          imagegif($image_p, $dirUpload.$name.'.'.$ext);
        }
        break;

      default:
        $image = imagecreatefromjpeg($tmp);
        if (is_bool($image) && !$image) {
          imagedestroy($image_p);
          return false;
        }
        imagecopyresampled($image_p, $image, 0, 0, $start_x, $start_y, $width, $height, $sWidth, $sHeight);
        $image_p = self::rotateImageByExif($image_p, $tmp);
        if (count($fullName) === 0) { //Если без расширения
          imagejpeg($image_p, $dirUpload.$ext, $quality);
        } else {
          imagejpeg($image_p, $dirUpload.$name.'.'.$ext, $quality);
        }
      // создаём новое изображение
    }
    imagedestroy($image_p);
    imagedestroy($image);

   //// echo "<br>return!";
    return true;
  }

  /**
   * Добавляет водяной знак к картинке.
   * <code>
   * Upload::addWatterMark('uploads/image.png');
   * </code>
   * @param string $image путь до картинки на сервере
   * @return bool
   */
  public function addWatterMark($image) {
    $filename = $image;
    if (!file_exists('uploads/watermark/watermark.png')) {
      return false;
    }
    $size_format = getimagesize($image);
    $format = strtolower(substr($size_format['mime'], strpos($size_format['mime'], '/') + 1));

    // создаём водяной знак
    $watermark = imagecreatefrompng('uploads/watermark/watermark.png');
    imagealphablending($watermark, false);
    imageSaveAlpha($watermark, true);
    // получаем значения высоты и ширины водяного знака
    $watermark_width = imagesx($watermark);
    $watermark_height = imagesy($watermark);

    // создаём jpg из оригинального изображения
    $image_path = $image;

    switch ($format) {
      case 'png':
        $image = imagecreatefrompng($image_path);
        $w = imagesx($image);
        $h = imagesy($image);
        $imageTrans = imagecreatetruecolor($w, $h);
        imagealphablending($imageTrans, false);
        imageSaveAlpha($imageTrans, true);

        $col = imagecolorallocate($imageTrans, 0, 0, 0);
        imagefilledrectangle($imageTrans, 0, 0, $w, $h, $col);
        imagealphablending($imageTrans, true);

        break;
      case 'gif':
        $image = imagecreatefromgif($image_path);
        break;
      default:
        $image = imagecreatefromjpeg($image_path);
    }

    //если что-то пойдёт не так
    if ($image === false) {
      return false;
    }
    $size = getimagesize($image_path);
    // помещаем водяной знак на изображение
    $position = MG::getSetting('waterMarkPosition');
    $margin = 10; //Отступ
    switch ($position) {
      case 'center':
        $dest_x = (($size[0]) / 2) - (($watermark_width) / 2);
        $dest_y = (($size[1]) / 2) - (($watermark_height) / 2);
        break;
      case 'top':
        $dest_x = (($size[0]) / 2) - (($watermark_width) / 2);
        $dest_y = $margin;
        break;
      case 'bottom':
        $dest_x = (($size[0]) / 2) - (($watermark_width) / 2);
        $dest_y = ($size[1]) - ($watermark_height) - $margin;
        break;
      case 'left':
        $dest_x = $margin;
        $dest_y = (($size[1]) / 2) - (($watermark_height) / 2);
        break;
      case 'right':
        $dest_x = ($size[0]) - ($watermark_width) - $margin;
        $dest_y = (($size[1]) / 2) - (($watermark_height) / 2);
        break;
      case 'topLeft':
        $dest_x = $margin;
        $dest_y = $margin;
        break;
      case 'topRight':
        $dest_x = ($size[0]) - ($watermark_width) - $margin;
        $dest_y = $margin;
        break;
      case 'bottomLeft':
        $dest_x = $margin;
        $dest_y = ($size[1]) - ($watermark_height) - $margin;
        break;
      case 'bottomRight':
        $dest_x = ($size[0]) - ($watermark_width) - $margin;
        $dest_y = ($size[1]) - ($watermark_height) - $margin;
        break;
      default:
        $dest_x = (($size[0]) / 2) - (($watermark_width) / 2);
        $dest_y = (($size[1]) / 2) - (($watermark_height) / 2);
        break;
    }

    imagealphablending($image, true);
    imagealphablending($watermark, true);

    imageSaveAlpha($image, true);
    // создаём новое изображение
    imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);

    $imageformat = 'image'.$format;
    if ($format = 'png') {
      $imageformat($image, $filename);
    } else {
      $imageformat($image, $filename, 100);
    }

    // освобождаем память
    imagedestroy($image);
    imagedestroy($watermark);
    return true;
  }

  /**
   * Загружает CSV файл для импорта каталога.
   * @access private
   * @return array|bool
   */
  public function addImportCatalogCSV() {


    $path = 'uploads/';
    $file_array = array();
    $validFormats = array('csv', 'zip', 'xlsx', 'xls');

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {

      if (!empty($_FILES['upload'])) {
        $file_array = $_FILES['upload'];
      }

      $name = $file_array['name'];
      $size = $file_array['size'];

      if (strlen($name)) {
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        if (in_array(strtolower($ext), $validFormats)) {
          if ($size && !empty($file_array['tmp_name'])) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
            if (strtolower($ext) == 'csv') {
              $name = 'importCatalog.csv';
              $_SESSION['importType'] = 'standart';
            }
            if (strtolower($ext) == 'zip') {
              $name = 'importCatalog.zip';
              $_SESSION['importType'] = 'standart';
            }
            if (strtolower($ext) == 'xlsx' || strtolower($ext) == 'xls') {
              $name = 'importCatalog.xlsx';
              $_SESSION['importType'] = 'excel';
            }

            $tmp = $file_array['tmp_name'];

            if (move_uploaded_file($tmp, $path.$name) || (is_file($tmp) && strpos($tmp, SITE_DIR) === 0 && copy($tmp, $path.$name))) {

              if (strtolower($ext) == 'zip') {
                if (file_exists($path.$name)) {
                  @unlink('uploads/importCatalog.csv');
                  $zip = new ZipArchive;
                  $res = $zip->open($path.$name, ZIPARCHIVE::CREATE);
                  
                  if ($res === TRUE) {
                    //$realDocumentRoot = str_replace(DS.'mg-core'.DS.'lib', '', dirname(__FILE__));
                    for($i = 0; $i < $zip->numFiles; $i++) {
                      $filename = $zip->getNameIndex($i);
                      $fullName = explode('.', $zip->getNameIndex($i));
                      $ext = array_pop($fullName);
                      if($ext=='csv'){
                        $zip->extractTo('uploads/', array($filename));
                        rename('uploads/'.$filename, 'uploads/importCatalog.csv');
                      } else if ($ext=='xlsx' || $ext=='xls') {
                        $zip->extractTo('uploads/', array($filename));
                        rename('uploads/'.$filename, 'uploads/importCatalog.xlsx');
                        $_SESSION['importType'] = 'excel';
                      } else {
                        return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD2'], 'status' => 'error');
                      }                    
                    }                
                    $zip->close();
                    unlink($path.$name);
                  }
                }
              }
              return array('msg' => self::$lang['ACT_FILE_UPLOAD'], 'actualImageName' => 'importCatalog.csv', 'status' => 'success');
            } else {
              return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD'], 'status' => 'error');
            }
          } else {
            return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD1'], 'status' => 'error');
          }
        } else {
          return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD2'], 'status' => 'error');
        }
      } else {
        return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD3'], 'status' => 'error');
      }
    }
    return false;
  }
  
   /**
   * Удаляет существующую картинку вместе с ее миниатюрами, если таковые имеются.
   * <code>
   * Upload::deleteImageProduct('10.jpg', 38);
   * </code>
   * @param string $filename имя файла.
   * @param int|bool $id id товара, необязательный параметр.
   * @return bool
   */
  public function deleteImageProduct($filename, $id = false) {
    $ds = DS;
    $filename = basename($filename); 
    // $documentroot = str_replace($ds.'mg-core'.$ds.'lib','',dirname(__FILE__)).$ds; 
    $documentroot = SITE_DIR; 

    if($id){
      $addPath = 'product'.$ds.floor($id/100).'00'.$ds.$id;

      if(is_file($documentroot."uploads".$ds.$addPath.$ds.$filename)){    
        unlink($documentroot."uploads".$ds.$addPath.$ds.$filename);
        
        if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."30_".$filename))
          unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."30_".$filename);

        if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_30_".$filename))
          unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_30_".$filename);
        
        if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."70_".$filename))
          unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."70_".$filename);

        if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_70_".$filename))
          unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_70_".$filename);        
        }elseif(is_file($documentroot."uploads".$ds.$filename)){
        unlink($documentroot."uploads".$ds.$filename);
        
        if(is_file($documentroot."uploads".$ds."thumbs".$ds."30_".$filename))
          unlink($documentroot."uploads".$ds."thumbs".$ds."30_".$filename);

        if(is_file($documentroot."uploads".$ds."thumbs".$ds."2x_30_".$filename))
          unlink($documentroot."uploads".$ds."thumbs".$ds."2x_30_".$filename);
        
        if(is_file($documentroot."uploads".$ds."thumbs".$ds."70_".$filename))
          unlink($documentroot."uploads".$ds."thumbs".$ds."70_".$filename);

        if(is_file($documentroot."uploads".$ds."thumbs".$ds."2x_70_".$filename))
          unlink($documentroot."uploads".$ds."thumbs".$ds."2x_70_".$filename);
        }
    }

    $addPath = 'prodtmpimg';
    
    if(is_file($documentroot."uploads".$ds.$addPath.$ds.$filename)){    
      unlink($documentroot."uploads".$ds.$addPath.$ds.$filename);
      
      if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."30_".$filename))
        unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."30_".$filename);

      if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_30_".$filename))
        unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_30_".$filename);
      
      if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."70_".$filename))
        unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."70_".$filename);
      
      if(is_file($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_70_".$filename))
        unlink($documentroot."uploads".$ds.$addPath.$ds."thumbs".$ds."2x_70_".$filename);      
    }elseif(is_file($documentroot."uploads".$ds.$filename)){
      unlink($documentroot."uploads".$ds.$filename);
      
      if(is_file($documentroot."uploads".$ds."thumbs".$ds."30_".$filename))
        unlink($documentroot."uploads".$ds."thumbs".$ds."30_".$filename);

      if(is_file($documentroot."uploads".$ds."thumbs".$ds."2x_30_".$filename))
        unlink($documentroot."uploads".$ds."thumbs".$ds."2x_30_".$filename);
      
      if(is_file($documentroot."uploads".$ds."thumbs".$ds."70_".$filename))
        unlink($documentroot."uploads".$ds."thumbs".$ds."70_".$filename);
      
      if(is_file($documentroot."uploads".$ds."thumbs".$ds."2x_70_".$filename))
        unlink($documentroot."uploads".$ds."thumbs".$ds."2x_70_".$filename);      
    }
    
    return true;
  }
  
  /**
   * Загружает картинку от пользователей с публичной части сайта на сервер. 
   * <code>
   * $uploader = new Upload(false);
   * $result = $uploader->uploadImage('form-designer/');
   * viewData($result);
   * </code>
   * @param string $subDir имя каталога куда будет загружено изображение 
   * @return string|bool
   */  
  public function uploadImage($subDir = '') {

    $file_array = $imageinfo = array();
    $validFormats = array('jpeg', 'jpg', 'png', 'gif');

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {

      if (!empty($_FILES['upload'])) {
        $imageinfo = getimagesize($_FILES['upload']['tmp_name']);
        $file_array = $_FILES['upload'];
      } elseif (!empty($_FILES['logo'])) {
        $imageinfo = getimagesize($_FILES['logo']['tmp_name']);
        $file_array = $_FILES['logo'];
      }
      $name = $file_array['name'];
      $size = $file_array['size'];
      $type = $file_array['type'];
      
      if (strlen($name)) {
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        // проверка соответствия расширения с разрешенными,
        if (in_array(strtolower($ext), $validFormats)) {  
          // проверка типа файла и  на количество типов 
          if(strpos($type,'image') !== false) {
            if($imageinfo['mime'] == 'image/gif' || $imageinfo['mime'] == 'image/jpeg' || 
              $imageinfo['mime'] == 'image/jpg' || $imageinfo['mime'] == 'image/png') {
              if(substr_count($type, '/') <= 1){
                // проверка на установленный размер файла и переименование латинским написанием
                if ($size < (1024 * 100 * 1024) && !empty($file_array['tmp_name'])) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
                  $name = str_replace(" ", "-", $name);
                  $name = MG::translitIt($name);
                  $actualImageName =  $name.".".$ext;
                   if (file_exists('uploads/'.$subDir.$name.".".$ext)) {
                    $actualImageName = $name.time().".".$ext;
                   }
                  
                  $tmp = $file_array['tmp_name'];
                  // пересохранение с помощью GD
                   if ($this -> resavingImageFromPublic($actualImageName, $tmp, $dirUpload = 'uploads/'.$subDir)) {
                    return SITE.'/'.$dirUpload.$actualImageName;   
                   }         
                } 
              } 
            }
          }
        }
      }
    }
    return false;
  }

  /**
   * Функция для пересохранения картинки, загруженной из публичной части.
   * @access private
   * @param string $name имя файла 
   * @param string $tmp исходный временный файл
   * @param string $dirUpload имя каталога 
   * @return bool
   */
  public function resavingImageFromPublic($name, $tmp, $dirUpload = 'uploads/') {
    $result = false;
    $fullName = explode('.', $name);
    $ext = array_pop($fullName);
    $name = implode('.', $fullName);
    // сохранение изображения
    switch ($ext) {
      case 'png':
        $image = imagecreatefrompng($tmp);
        imagealphablending($image, true);
        imageSaveAlpha($image, true);
        imagepng($image, $dirUpload.$name.'.'.$ext);
        if (imagepng($image, $dirUpload.$name.'.'.$ext)) {
          $result = true;
        }
        break;
      case 'gif':
        $image = imagecreatefromgif($tmp);
        if (imagegif($image, $dirUpload.$name.'.'.$ext)) {
           $result = true;          
        }
        break;
      default:
        $image = imagecreatefromjpeg($tmp);
        if (imagejpeg($image, $dirUpload.$name.'.'.$ext)) {
           $result = true;          
        }
    }
    imagedestroy($image);
    return $result;
  }

  /**
   * Загружает картинку favicon из формы на сервер.
   * @access private
   * @return array
   */
  public function addFavicon() {  

    $file_array = array();
    $validFormats = ['ico', 'png', 'gif', 'jpg', 'jpeg', 'apng', 'svg', 'bmp'];

    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {
      if (!empty($_FILES['favicon'])) {
        $file_array = $_FILES['favicon'];
      }
      $name = $file_array['name'];
      $size = $file_array['size'];
      if (strlen($name)) {
        //list($txt, $ext) = explode('.', $name);
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        if (in_array(strtolower($ext), $validFormats)) {
          if ($size < (1024 * 100 * 1024) && !empty($file_array['tmp_name'])) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
           $actualImageName = 'favicon-temp.'.$ext;
            $tmp = $file_array['tmp_name'];         
            if (move_uploaded_file($tmp, $actualImageName)) {
              return array('msg' => self::$lang['ACT_IMG_UPLOAD'], 'actualImageName' => $actualImageName, 'status' => 'success');
            } 
          } else {
            return array('msg' => self::$lang['ACT_IMG_NOT_UPLOAD1'], 'status' => 'error');
          }
        } 
      }
    }
    return array('msg' => self::$lang['ACT_IMG_NOT_UPLOAD'], 'status' => 'error');
  }
  
  /**
   * Загружает архив с изображениями товаров.
   * <code>
   * $result = Upload::addImagesArchive('/uploads/archive.zip');
   * viewData($result);
   * </code>
   * @param string|bool $filename путь к файлу на сервере
   * @return array|bool
   */
  public static function addImagesArchive($filename = false) {

    $validFormats = array('zip');          

    if($filename){
      $filename = str_replace(SITE,'',urldecode($filename));
      $zip = new ZipArchive;
      $res = $zip->open(SITE_DIR.$filename, ZIPARCHIVE::CREATE);

      if ($res === TRUE) {
        @mkdir(SITE_DIR.'uploads/tempimage/', 0755, true);
        $zip->extractTo(SITE_DIR.'uploads/tempimage/');
        $zip->close();
        return array('msg' => 'Файлы подготовлены', 'status' => 'success');
      }
      return array('msg' => 'ошибка архива', 'status' => 'error');
    }      
    
    if (empty(self::$lang)) {
      $lang = array();
      include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
      self::$lang = $lang;
    }
    
    if (isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']) {      
      if (!empty($_FILES['uploadImages'])) {
        $file_array = $_FILES['uploadImages'];
        $name = $file_array['name'];
        $size = $file_array['size'];
      }       

      if (!empty($name)) {
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        if (in_array(strtolower($ext), $validFormats)) {
          // mg::loger($size);
          if (isset($size) && !empty($file_array['tmp_name'])) { //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
              $zip = new ZipArchive;
              $res = $zip->open($file_array['tmp_name'], ZIPARCHIVE::CREATE);
              
              if ($res === TRUE) {            
                @mkdir(SITE_DIR.'uploads/tempimage/', 0755, true);           
                $zip->extractTo(SITE_DIR.'uploads/tempimage/');
                $zip->close();
                @unlink($file_array['tmp_name']);
                return array('msg' => 'Файлы подготовлены', 'status' => 'success');
              } else {
                return array('msg' => 'ошибка архива', 'status' => 'error');
              }
       
          } else {
            return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD1'], 'status' => 'error');
          }
        } else {
          return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD2'], 'status' => 'error');
        }
      } else {
        return array('msg' => self::$lang['ACT_FILE_NOT_UPLOAD3'], 'status' => 'error');
      }
    }
    return true;
  }

  /**
   * Удаляет миниатюры товаров
   * @return array
   */
  public function destroyThumbs($nextItem, $totalCount) {
    $startTime = microtime(true);
    $maxExecTime = min(25, ini_get('max_execution_time'));
    if (!$maxExecTime) {
      $maxExecTime = 25;
    }

    $count = $nextItem ? $nextItem : 1;

    if (!$totalCount) {
      $totalCountSql = 'SELECT COUNT(id) as count FROM `'.PREFIX.'product`';
      $totalCountResult = DB::query($totalCountSql);
      if ($totalCountRow = DB::fetchAssoc($totalCountResult)) {
        $totalCount = $totalCountRow['count'];
      }
    }

    $log = '';

    $productsIdsSql = 'SELECT `id` '.
      'FROM `'.PREFIX.'product` '.
      'LIMIT '.($count-1).', 100';
    $productsIdsResult = DB::query($productsIdsSql);
    while ($productsIdsRow = DB::fetchAssoc($productsIdsResult)) {
      $productId = $productsIdsRow['id'];
      $productImagesFolder = SITE_DIR.'uploads'.DS.'product'.DS.
        (floor($productId/100).'00').DS.$productId;
      $productImagesThumbsFolder = $productImagesFolder.DS.'thumbs';
      if (is_dir($productImagesThumbsFolder)) {
        MG::rrmdir($productImagesThumbsFolder);
      }
      $count += 1;
      $execTime = microtime(true) - $startTime;
      if ($execTime > $maxExecTime) {
        break;
      }
    }

    $percent = floor($count * 100 / $totalCount);

    if($percent > 100){
      $percent = 100;
    }

    $data = array(
      'percent' => $percent,
      'total_count' => $totalCount,
      'nextItem' => $count,
    );
    
    $result = array(
      'messageSucces' => "\nОбработано " . $percent . "% товаров",
      'data' => $data,
    );
    
    return $result;
  }
  
  /**
   * Создает миниатюры для изображений товаров.
   * <code>
   * $uploader = new Upload(false);
   * $result = $uploader->generatePreviewPhoto();
   * viewData($result);
   * </code>
   * @return array
   */
  public function generatePreviewPhoto($productFolder = false){
    $startTime = microtime(true);
    $maxExecTime = min(30, @ini_get("max_execution_time"));
    
    if (!defined('MAX_IMAGES_COUNT')) {
      $maxImagesInTime = 25;
    } else {
      $maxImagesInTime = MAX_IMAGES_COUNT;
    }
    
    if (empty($maxExecTime)) {
      $maxExecTime = 30;
    }
     
    $path = SITE_DIR.'uploads'.DS.'tempimage';

    $process = false; // флаг запуска процесса
    $count = !empty($_POST['nextItem']) ? $_POST['nextItem'] : 1; // сколько уже обработано файлов
    $imgCount = !empty($_POST['imgCount']) ? $_POST['imgCount'] : 1;
    $model = new Models_Product();
    $log = '';
    $percent100 = 0;
    
    if($count == 1){
      if($dbRes = DB::query('SELECT COUNT(id) as count FROM `'.PREFIX.'product`')){
        $res = DB::fetchAssoc($dbRes);
        $percent100 = $res['count'] + 1;
      }
    } else {
      $percent100 = intval($_POST['total_count']);
    }
    
    $sql = 'SELECT p.id, p.image_url, 
      (SELECT GROUP_CONCAT(DISTINCT image SEPARATOR \'|\') FROM `'.PREFIX.'product_variant` WHERE product_id = p.id) as var_image
      FROM `'.PREFIX.'product` AS p
      LIMIT '.($count-1).', 100';
    
    if ($dbRes = DB::query($sql)) {      
      $arSizes = array(
        'width70' => MG::getSetting('widthPreview'),
        'height70' => MG::getSetting('heightPreview'),
        'width30' => MG::getSetting('widthSmallPreview'),
        'height30' => MG::getSetting('heightSmallPreview'),
        'maxWidth' => MG::getSetting('maxUploadImgWidth'),
        'maxHeight' => MG::getSetting('maxUploadImgHeight'),
      );      
      
      $options['width70'] = $arSizes['width70'] ? $arSizes['width70'] : 300;
      $options['height70'] = $arSizes['height70'] ? $arSizes['height70'] : 225;
      $options['width30'] = $arSizes['width30'] ? $arSizes['width30'] : 70;
      $options['height30'] = $arSizes['height30'] ? $arSizes['height30'] : 70;
      $options['maxWidth'] = $arSizes['maxWidth'] ? $arSizes['maxWidth'] : 1500;
      $options['maxHeight'] = $arSizes['maxHeight'] ? $arSizes['maxHeight'] : 1500;
      $resizeType = MG::getSetting('imageResizeType');
      $imageResizeRetina = MG::getSetting('imageResizeRetina');
      
      $imagesCount = 0;

      while($product = DB::fetchAssoc($dbRes)){        
        if ($productFolder) {
          $path = SITE_DIR.'uploads'.DS.'product'.DS.(floor($product['id']/100).'00').DS.$product['id'];
        }
        $product['image_url'] .= '|'.$product['var_image'];
        $images = explode('|', trim($product['image_url'], '|'));
        
        foreach($images as $image){ 
          // Создаем оригинал            
          $imagesCount++;
          if(!empty($image) && file_exists($path . DS . $image)) {
            $thumbsDir = SITE_DIR.'uploads'.DS.'product'.DS.(floor($product['id']/100).'00').DS.$product['id'].DS.'thumbs'.DS;
            @mkdir($thumbsDir, 0755, true);
            if ($productFolder) {
              if (!is_writable($path.DS.$image)) {
                $log .= "Изображение ".$image." не обработано. Нет прав на запись оригинального изображения.\n";
                $imgCount++;
                continue;
              }
              $imgSizes = getimagesize($path.DS.$image);
              $imgWidth = $imgSizes[0];
              $imgHeight = $imgSizes[1];
              if (!in_array($imgSizes[2], [1,2,3,6])) {//'GIF','JPEG','PNG','BMP'
                copy($path.DS.$image, $thumbsDir.'30_'.$image);
                copy($path.DS.$image, $thumbsDir.'70_'.$image);
                if ($imageResizeRetina == 'true') {
                  copy($path.DS.$image, $thumbsDir.'2x_30_'.$image);
                  copy($path.DS.$image, $thumbsDir.'2x_70_'.$image);
                }
                $imgCount++;
                continue;
              }
              if ($imgWidth > $options['maxWidth'] || $imgHeight > $options['maxHeight']) {
                $log .= "Изображение " . $image . " не обработано. Слишком большое разрешение.\n";
                $imgCount++;
                continue;
              }
            }

            // Если необходимо, накладываем водяной знак
            if (!$productFolder && MG::getSetting('waterMark') == 'true') {
              self::addWatterMark($path.DS.$image);
            }
            
            // создаем две миниатюры
            $bigImg = self::_reSizeImage('70_'.$image, $path.DS.$image, $options['width70'], $options['height70'], $resizeType, $thumbsDir);
            $smallImg = self::_reSizeImage('30_'.$image, $path.DS.$image, $options['width30'], $options['height30'], $resizeType, $thumbsDir);
            if ($resizeType != 'EXACT') {
              clearstatcache();
              if (
                is_file($thumbsDir.'30_'.$image) &&
                filesize($thumbsDir.'30_'.$image) > filesize($path.DS.$image)
              ) {
                copy($path.DS.$image, $thumbsDir.'30_'.$image);
              }
              if (
                is_file($thumbsDir.'70_'.$image) &&
                filesize($thumbsDir.'70_'.$image) > filesize($path.DS.$image)
              ) {
                copy($path.DS.$image, $thumbsDir.'70_'.$image);
              }
            }
            
            if ($imageResizeRetina === 'true') {
              $retina30 = self::_reSizeImage('2x_30_'.$image, $path.DS.$image, ($options['width30']*2), ($options['height30']*2), $resizeType, $thumbsDir);
              $retina70 = self::_reSizeImage('2x_70_'.$image, $path.DS.$image, ($options['width70']*2), ($options['height70']*2), $resizeType, $thumbsDir);
              clearstatcache();
              if (
                !$retina30 ||
                (
                  $resizeType != 'EXACT' &&
                  is_file($thumbsDir.'2x_30_'.$image) &&
                  filesize($thumbsDir.'2x_30_'.$image) > filesize($path.DS.$image)
                )
              ) {
                copy($path.DS.$image, $thumbsDir.'2x_30_'.$image);
              }
              if (
                !$retina70 ||
                (
                  $resizeType != 'EXACT' &&
                  is_file($thumbsDir.'2x_70_'.$image) &&
                  filesize($thumbsDir.'2x_70_'.$image) > filesize($path.DS.$image)
                )
              ) {
                copy($path.DS.$image, $thumbsDir.'2x_70_'.$image);
              }
            }
            
            if (!$productFolder) {
              if (!$bigImg || !$smallImg) {
                $log .= "\n$imgCount Изображение " . $image . " не обработано. Слишком большое разрешение.";
              } else {
                $log .= "\n$imgCount Обработано изображение: " . $image;
              }
            }

            Webp::createWebpImg($path.DS.$image);
            if (is_file($thumbsDir.'30_'.$image) && !is_writable($thumbsDir.'30_'.$image)) {
              $log .= "Изображение ".$image." не обработано. Нет прав на запись маленькой миниатюры.\n";
              $imgCount++;
              continue;
            }
            Webp::createWebpImg($thumbsDir.'30_'.$image);
            if (is_file($thumbsDir.'70_'.$image) && !is_writable($thumbsDir.'70_'.$image)) {
              $log .= "Изображение ".$image." не обработано. Нет прав на запись большой миниатюры.\n";
              $imgCount++;
              continue;
            }
            Webp::createWebpImg($thumbsDir.'70_'.$image);
            if ($imageResizeRetina && is_file($thumbsDir.'2x_30_'.$image) && !is_writable($thumbsDir.'2x_30_'.$image)) {
              $log .= "Изображение ".$image." не обработано. Нет прав на запись маленькой миниатюры для дисплеев с высоким разрешением.\n";
              $imgCount++;
              continue;
            }
            Webp::createWebpImg($thumbsDir.'2x_30_'.$image);
            if ($imageResizeRetina && is_file($thumbsDir.'2x_70_'.$image) && !is_writable($thumbsDir.'2x_70_'.$image)) {
              $log .= "Изображение ".$image." не обработано. Нет прав на запись большой миниатюры для дисплеев с высоким разрешением.\n";
              $imgCount++;
              continue;
            }
            Webp::createWebpImg($thumbsDir.'2x_70_'.$image);
            
            $imgCount++;
          }
          if (defined('MASS_IMG_DELAY') && floatval(MASS_IMG_DELAY) > 0) {
            usleep((floatval(MASS_IMG_DELAY)*1000000));
          }
        }
        
        if (!$productFolder) {
          $model->movingProductImage($images, $product['id'], $path, false);
        }
                
        $count++;
        $execTime = microtime(true) - $startTime;
        
        if($execTime + 5 >= $maxExecTime || $imagesCount > $maxImagesInTime){
          $percent = floor(($count * 100) / $percent100);

          if($percent == 0) {
            $roundCount = 2;
            $percent = round(($count * 100) / $percent100, $roundCount);
            while($percent == 0) {
              $count++;
              $percent = round(($count * 100) / $percent100, $roundCount);
              if($count == 10) break;
            }
          }

          if($percent > 100){
            $percent = 100;
          }

          $data = array(
            'percent' => $percent,
            'total_count' => $percent100,
            'nextItem' => $count,
            'imgCount' => $imgCount,
            'log' => $log,
          );
          
          $arReturn = array(
            'messageSucces' => "\nОбработано " . $percent . "% товаров",
            'data' => $data,
          );
          
          return $arReturn;
        }
      }
    }
    
    $percent = ($percent100 != 0) ? floor(($count * 100) / $percent100) : 0;

    if($percent == 0) {
      $roundCount = 2;
      $percent = ($percent100 != 0) ? round(($count * 100) / $percent100, $roundCount) : 100;
      while($percent == 0) {
        $count++;
        $percent = round(($count * 100) / $percent100, $roundCount);
        if($count == 10) break;
      }
    }
    
    if($percent >= 100){
      $percent = 100;
      if (!$productFolder) {
        self::removeDirectory($path);
      }
    }
    
    $data = array(
      'percent' => $percent,
      'total_count' => $percent100,
      'nextItem' => $count,
      'imgCount' => $imgCount,
      'log' => $log,
    );
    
    $arReturn = array(
      'messageSucces' => "\nОбработано " . $percent . "% товаров",
      'data' => $data,
    );
       
    return $arReturn;
  }
  
  /**
   * Создает миниатюры для категорий.
   * <code>
   * $uploader = new Upload(false);
   * $result = $uploader->generatePreviewPhotoCategories();
   * viewData($result);
   * </code>
   * @return array
   */
  public function generatePreviewPhotoCategories($productFolder = false){
    $startTime = microtime(true);
    $maxExecTime = min(30, @ini_get("max_execution_time"));

    if (empty($maxExecTime)) {
      $maxExecTime = 30;
    }

    $path = SITE_DIR.'uploads'.DS.'tempimage';

    $process = false; // флаг запуска процесса
    $count = !empty($_POST['nextItem']) ? $_POST['nextItem'] : 1; // сколько уже обработано файлов
    $imgCount = !empty($_POST['imgCount']) ? $_POST['imgCount'] : 1;
    $model = new Category();
    $log = '';
    $percent100 = 0;

    if($count == 1){
      if($dbRes = DB::query('SELECT COUNT(id) as count FROM `'.PREFIX.'category`')){
        $res = DB::fetchAssoc($dbRes);
        $percent100 = $res['count'];
      }
    } else {
      $percent100 = intval($_POST['total_count']);
    }

    $sql = 'SELECT id, image_url, menu_icon 
      FROM `'.PREFIX.'category`
      LIMIT '.($count-1).', 100';

    if ($dbRes = DB::query($sql)) {

      while($category = DB::fetchAssoc($dbRes)){
        $imgFolder = SITE_DIR.'uploads'.DS.'category'.DS.$category['id'].DS;
        $mainImage = pathinfo($category['image_url'])['basename'];
        $menuImage = pathinfo($category['menu_icon'])['basename'];
        $menuImageNoPrefix = str_replace('menu_', '', $menuImage);

        if (!empty($mainImage) && file_exists($path . DS . $mainImage) && !file_exists($imgFolder . $mainImage)) {
          $newMainImage = $model->resizeCategoryImg('/uploads/tempimage/'.$mainImage, $category['id'], '');
          if (!empty($newMainImage)) {
            DB::query("UPDATE `".PREFIX."category` SET image_url = ".DB::quote($newMainImage)." WHERE id = ".DB::quoteInt($category['id']));
          }
        }
        if (!empty($menuImage) && file_exists($path . DS . $menuImageNoPrefix) && !file_exists($imgFolder . $menuImage)) {
          $newMenuImage = $model->resizeCategoryImg('/uploads/tempimage/'.$menuImageNoPrefix, $category['id'], 'menu_');
          if (!empty($newMenuImage)) {
            DB::query("UPDATE `".PREFIX."category` SET menu_icon = ".DB::quote($newMenuImage)." WHERE id = ".DB::quoteInt($category['id']));
          }
        }
        $imgCount++;

        if (defined('MASS_IMG_DELAY') && floatval(MASS_IMG_DELAY) > 0) {
          usleep((floatval(MASS_IMG_DELAY)*1000000));
        }

        $count++;
        $execTime = microtime(true) - $startTime;

        if($execTime + 5 >= $maxExecTime){
          $percent = floor(($count * 100) / $percent100);

          if($percent == 0) {
            $roundCount = 2;
            $percent = round(($count * 100) / $percent100, $roundCount);
            while($percent == 0) {
              $count++;
              $percent = round(($count * 100) / $percent100, $roundCount);
              if($count == 10) break;
            }
          }

          if($percent > 100){
            $percent = 100;
          }

          $data = array(
            'percent' => $percent,
            'total_count' => $percent100,
            'nextItem' => $count,
            'imgCount' => $imgCount,
            'log' => $log,
          );

          $arReturn = array(
            'messageSucces' => "\nОбработано " . $percent . "% товаров",
            'data' => $data,
          );

          return $arReturn;
        }
      }
    }

    $percent = floor(($count * 100) / $percent100);

    if($percent == 0) {
      $roundCount = 2;
      $percent = round(($count * 100) / $percent100, $roundCount);
      while($percent == 0) {
        $count++;
        $percent = round(($count * 100) / $percent100, $roundCount);
        if($count == 10) break;
      }
    }

    if($percent >= 100){
      $percent = 100;
      if (!$productFolder) {
        self::removeDirectory($path);
      }
    }

    $data = array(
      'percent' => $percent,
      'total_count' => $percent100,
      'nextItem' => $count,
      'imgCount' => $imgCount,
      'log' => $log,
    );

    $arReturn = array(
      'messageSucces' => "\nОбработано " . $percent . "% товаров",
      'data' => $data,
    );

    return $arReturn;
  }
  
  /**
   * Рекурсивно удаляет директории с картинками.
   * @access private
   * @param string $dir директория для удаления.
   */
  public function removeDirectory($dir) {    
    if ($objs = glob($dir."/*")) {
       foreach($objs as $obj) {
         is_dir($obj) ? self::removeDirectory($obj) : unlink($obj);
       }
    }
    if (is_dir($dir)) rmdir($dir);
  }

  /**
	 * Загружает картинки из url
	 * @return string
	 */
	public function uploadImageFromUrl($url, $isCatalog) {
		$tmp = str_replace(array('http://', 'https://'), '', $url);
		$tmp = explode('/', $tmp);
		$domain = $tmp[0];
		require_once('mg-core/lib/idna_convert.class.php');
		$idn = new idna_convert(array('idn_version'=>2008));
		$encoded = $idn->encode($domain);
		if (stripos($encoded, 'xn--')!==false) {
			$url = str_replace($domain, $encoded, $url);
		}
		if (!empty($url)) {
			//На случай https
			if (strpos($url, 'https://') !== false) {
				stream_context_set_default( [
					'ssl' => [
						'verify_peer' => false,
						'verify_peer_name' => false,
					],
				]);
			}

			$headers = get_headers($url, 1);
		} else {
			$headers = array('Content-Type'=>'');
		}
    // на случай редиректа
    if (is_array($headers['Content-Type'])) {
      $headers['Content-Type'] = array_pop($headers['Content-Type']);
    }
    if (isset($headers['Content-Length']) && is_array($headers['Content-Length'])) {
      $headers['Content-Length'] = array_pop($headers['Content-Length']);
    }
    if (isset($headers['Content-Size']) && is_array($headers['Content-Size'])) {
      $headers['Content-Size'] = array_pop($headers['Content-Size']);
    }
    if (isset($headers['Location'])) {
      $url = $headers['Location'];
    }

    $headers['Content-Type'] = strtolower($headers['Content-Type']);

    //если заголовка с Content-Type нет, то опредделяем маймтайп по расширению 
    if(empty($headers['Content-Type']) || $headers['Content-Type'] === 'image'){
      $pathInfo = pathinfo(strtolower($url));
      if(!empty($pathInfo['extension'])){
      switch ($pathInfo['extension']) {
        case 'webp':
          $headers['Content-Type'] = 'image/webp';
          break;
        case 'svg':
          $headers['Content-Type'] = 'image/svg';
          break;
        case 'jpeg':
          $headers['Content-Type'] = 'image/jpeg';
          break;
        case 'jpg':
          $headers['Content-Type'] = 'image/jpeg';
            break;
        case 'png':
          $headers['Content-Type'] = 'image/png';
           break;
        case 'gif':
          $headers['Content-Type'] = 'image/gif';
          break;
       }
      }
    };
    
    if (in_array($headers['Content-Type'], array('image/webp', 'image/svg', 'image/svg+xml', 'image/jpeg', 'image/png', 'image/gif'))) {
      $urlHash = md5($url);
      $ext = str_replace('image/', '.', $headers['Content-Type']);
      if ($ext === '.svg+xml') {
        $ext = '.svg';
      }
      $fileName = $urlHash.$ext;

			$_FILES = array();
			$_FILES['photoimg']['name'] = $fileName;
			$_FILES['photoimg']['type'] = $headers['Content-Type'];
			$_FILES['photoimg']['tmp_name'] = $url;
			$_FILES['photoimg']['error'] = 0;
      $headers['Content-Size'] = isset($headers['Content-Size'])?$headers['Content-Size']:NULL;
			$_FILES['photoimg']['size'] = isset($headers['Content-Length'])?$headers['Content-Length']:$headers['Content-Size'];
			if ($isCatalog == 'true') {
				$tempData = $this->addImage(true);
			}
			else{
				$tempData = $this->addImage(false, false, 'prodtmpimg');
			}
			if ($tempData['status'] == 'success') {
        $data = str_replace(array('30_', '70_'), '', $tempData['actualImageName']);
        return ['status'=>true, 'data' => $data, 'msg' => $tempData['msg'], 'newName' => $fileName];
			} else {
        return ['status'=>false, 'data' => null, 'msg' => $tempData['msg']];
			}
		} else {
      return ['status'=>false, 'data' => null, 'msg' => self::$lang['IMAGE_OPEN_ERROR']];
		}
	}

  /**
   * Метод заменяет содержимое uploads стартовым набором данных
   * 
   * @return bool
   */
  public static function starterSetOfUploads() {
    $uploadsZipPath = SITE_DIR.CORE_JS.'zip'.DS.'uploads.zip';
    $zip = new ZipArchive;
    if ($zip->open($uploadsZipPath) == false) {
      return false;
    }
    $uploadsDir = SITE_DIR.'uploads';
    $uploadsObjects = array_diff(scandir($uploadsDir), array('.', '..'));
    foreach ($uploadsObjects as $uploadsObject) {
      $objectPath = $uploadsDir.DS.$uploadsObject;
      if(is_dir($objectPath)) {
        MG::rrmdir($objectPath, true);
      } else {
        @unlink($objectPath);
      }
    }
    $extractResult = $zip->extractTo($uploadsDir);
    return true;
  }

  // Поворачивает изображения в соответствии с meta-информацией.
  // Работает только если подключена php-библиотека "Exif"
  public static function rotateImageByExif($image, $imagePath) {
    if (
      MG::getSetting('exifRotate') === 'true' &&
      function_exists('exif_read_data')
    ) {
      $exif = @exif_read_data($imagePath);
      if ($image && $exif && isset($exif['Orientation'])) {
        $ort = $exif['Orientation'];
        if ($ort == 6 || $ort == 5)
          $image = imagerotate($image, 270, 0);
        if ($ort == 3 || $ort == 4)
          $image = imagerotate($image, 180, 0);
        if ($ort == 8 || $ort == 7)
          $image = imagerotate($image, 90, 0);

        if ($ort == 5 || $ort == 4 || $ort == 7)
          imageflip($image, IMG_FLIP_HORIZONTAL);
      }
    }
    return $image;
  }
}
