<?php
/**
 * Класс Import - предназначен для импорта товаров в каталог магазина. Поддерживает две структуры файлов  в формате CSV. Упрощенная - с артикулами и ценами, а также полная со всей информацией о каждом товаре.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Import {
  private $typeCatalog = 'MogutaCMS';
  private $currentRowId = null;
  private $validError = null; 
  public static $iteration = 1; 
  public static $downloadLink = false;
  public static $complianceArray = array();
  public static $fullProduct = array();
  private static $notUpdate = array();
  public static $maskArray = array(
    'MogutaCMS' => array(
      0 => 'ID товара',
      1 => 'Артикул',
      2 => 'Категория',
      3 => 'URL категории',
      4 => 'Товар',
      5 => 'Вариант',
      6 => 'Краткое описание',
      7 => 'Описание',
      8 => 'Цена',
      9 => 'Старая цена',
      10 => 'URL товара',
      11 => 'Изображение',
      12 => 'Изображение варианта',
      13 => 'Количество',
      14 => 'Активность',
      15 => 'Заголовок [SEO]',
      16 => 'Ключевые слова [SEO]',
      17 => 'Описание [SEO]',
      18 => 'Рекомендуемый',
      19 => 'Новый',
      20 => 'Сортировка',
      21 => 'Вес',
      22 => 'Связанные артикулы',
      23 => 'Смежные категории',
      24 => 'Ссылка на товар',
      25 => 'Валюта',
      26 => 'Единицы измерения',
      27 => 'Единицы веса',
      28 => 'Кратность',

      29 => 'Оптовые цены начинаются с',
      30 => 'Склады',
      31 => 'Дополнительные поля',

      32 => 'Свойства начинаются с',
      33 => 'Сложные характеристики',
    ),
    'Category' => array(
      'Название категории',
      'URL категории',
      'ID родительской категории',
      'URL родительской категории',
      'Описание категории',
      'Изображение',
      'Иконка для меню',
      'Заголовок [SEO]',
      'Ключевые слова [SEO]',
      'Описание [SEO]',
      'SEO Описание',
      'Наценка',
      'Не выводить в меню',
      'Активность',
      'Не выгружать в YML',
      'Сортировка',
      'Внешний идентификатор',
      'ID категории',
      'Title изображения',
      'Alt изображения',
    ),
  );
  public static $fields = array(
    'MogutaCMS' => array(
      0  => 'id',
      1  => 'code',
      2  => 'cat_id',
      3  => 'cat_url',
      4  => 'title',
      5  => 'variant',
      6  => 'short_description',
      7  => 'description',
      8  => 'price',
      9  => 'old_price',
      10 => 'url',
      11 => 'image_url',
      12 => 'image_variant_url',
      13 => 'count',
      14 => 'activity',
      15 => 'meta_title',
      16 => 'meta_keywords',
      17 => 'meta_desc',
      18 => 'recommend',
      19 => 'new',
      20 => 'sort',
      21 => 'weight',
      22 => 'related',
      23 => 'inside_cat',
      24 => 'link_electro',
      25 => 'currency_iso',
      26 => 'category_unit',
      27 => 'weight_unit',
      28 => 'multiplicity',

      29 => 'wholesales',
      30 => 'storages',
      31 => 'opFields',

      32 => 'property',
      33 => 'hard_prop',
    ),
  );
  public static $fieldsInfo = array(
    'MogutaCMS' => array(
        // ID
        0 => "Уникальный ID товара в Moguta.CMS.\nБудет создан автоматически, если такого столбца нет в файле.\nЕсли столбец есть в файле, то по ID будет найден товар и обновлен.",
        // Артикул
        1 => "Уникальный артикул товара\n---\nПример: CN-21",
        // Категория
        2 => "Название категории товара.\n---\nПример: «Аксессуары»\nПример вложенной категории: «Аксессуары/Головные уборы»",
        // URL категории
        3 => "URL категории будет создан автоматически из названия категории.\n---\nПример: «aksessuary»\nПример вложенной категории: «aksessuary/golovnye-ubory»",
        // Това
        4 => "Название товара\n---\nПример: «Бейсболка мужская»",
        // Вариант
        5 => "Название варианта товара\n---\nПример: «Синий»\nПример: «Синий[src=imagename.jpg]»",
        // Краткое описание
        6 => "Карткое описание товара, выводится в миникарточке товара.\n---\nТекст должен быть в одну строку без переносов! Допускается HTML код в одну строку.",
        // Описание
        7 => "Полное описание товара.\n---\nТекст должен быть в одну строку без переносов! Допускается HTML код в одну строку.",
        // Цена
        8 => 'Цена товара или его варианта, если указан вариант',
        // Старая цена
        9 => 'Старая цена для товара. Товары со старой ценой попадают в блок «Акции»',
        // URL товара
        10 => 'URL товара будет создан автоматически из названия товара.',
        // Изображение
        11 => "Изображение товара или несколько изображений.\nПозволяет указать у товара список изображений через вертикальную черту |\n---\nПримеры с указанием ссылок на изображения:\nПример 1: «http://site/img1.jpg»\nПример 2: «http://site/img1.jpg| http://site/img2.jpg»\n---\nПримеры с указанием названий файлов изображений в архиве:\nПример 3: «img1.jpg|img2.jpg»\nПример 4 с атрибутами: «img1.jpg|img2.jpg[:param:][alt=Картинка][title=Картинка]»\n---\nАрхив с изображениями должен быть загружен отдельно, после импорта.",
        // Изображение варианта
        12 => 'Ссылка на изображение варианта или название файла в архиве',
        // Количество
        13 => 'Остаток товара. Если указать -1, то товар будет бесконечным',
        // Активность
        14 => "1 - товар будет отображатся в каталоге.\n0 - товар не будет отображатся в каталоге",
        // Заголовок SEO
        15 => "SEO Title.\n---\nПример: «Бейсболка мужская»",
        // Ключевое слово SEO
        16 => "SEO Keywords.\n---\nПример: «Бейсболка мужская купить, CN32, Бейсболка для мужчин»",
        // Описание SEO
        17 => "SEO Description.\n---\nПример: «Специальные канавки Flex Grooves позволяют подошве легко сгибаться.»",
        // Рекомендуемый
        18 => "1 - товар будет рекомендуемым.\n0 - товар не будет рекомендуемым",
        // Новый
        19 => "1 - товар будет в новинках.\n0 - товара не будет в новинках",
        // Сортировка
        20 => 'Устанавливает порядок сортировки в каталоге. Необходимо выбрать в настроках тип сортировки «По порядку»',
        // Вес
        21 => 'Вес товара',
        // Связанные артикулы
        22 => "Акртикулы похожих товаров.\n---\nПример: «CN17,CN18»",
        // Смежные категории
        23 => "Дополнительные категории, в которых будет отображаться товар. Нужно указать ID существующих категори.\n---\nПример: «13,16,67»",
        // Ссылка на скачивание электронного товара
        24 => "Если товар цифровой, можно указать ссылку на скачивание файла цифрового товара. Эта ссылка будет высылаться клиенту после оплаты заказа.",
        // Валюта
        25 => "Международный ISO код валюты.\n---\nПример: RUR",
        // Единица измерения
        26 => "Единица измерения товара.\n---\nПример: шт.",
        // Единица веса
        27 => "Единица измерения веса\n---\nПример: kg.",
        // Кратность
        28 => "Количество по сколько добавлять в корзину\n---\nПример: 1 или 1.5 или 25 и тд.",
        // Оптовые цены
        29 => "Оптовая стоимость товара. Один или несколько столбцов в файле, друг за другом.\nВ названии столбца должен быть обязательный параметр [оптовая цена].\n---\nПример названия столбца:\n«Количество от 10 для цены 1 [оптовая цена]»\n\nГде 10 - это количество товара, от которого будет применяться «оптовая цена 1»",
        // Склады
        30 => "Количество товара на каждом складе. Один или несколько столбцов в файле, друг за другом.\n---\nПример названия заголовка: Склад №1 [склад=Sklad-№1]\n\n«Склад №1» - названия склада.\n«Sklad-№1» - внутренний идентификатор склада, запрещена кириллица, пробелы и спец. символы.",
        // Дополнительные поля
        31 => "Дополнительные поля товара. Один или несколько столбцов в файле, друг за другом. Предварительно дополниельные поля должны быть созданы в настройках Moguta.CMS, в разеделе дополнительных полей.\n---\nПример названия столбца: «Название поля [op id=f_id]»\n\nГде f_id - числовыо значение, ID дополнительного поля",
        // характеристики
        32 => "Столбцы с характеристиками товара. Выберите первый столбец, после которого в файле указаны все остальные столбцы с характеристиками. В заголовках столбцов указывается название свойства товара, а в самом поле указываете значение свойства.\n---\nЕсли свойство является цветом или размером, то нужно после указания названия свойства, приписать еще [color] или [size] соответственно\n---\nЕсли поле является текстовым, то аналогичным способом нужно приписать [textarea]\n---\nПример записи заголовка:\n1) Производитель\n2) Цвет [color]\n3) Размер [size]\n4) Описание производства [textarea]\n---\n\nПример записи значений характеристики:\n1) Обычное строковое значение\n2) Белый цвет [#ffffff]",
        // сложные характеристики
        33 => 'Составные характеристики товаров созданные в панели управления Moguta.CMS. Предназначено для переноса каталога между сайтами, работающими на Moguta.CMS и создается автоматически при выгрузке каталога в CSV.',
    ),
  );

  public static $requiredFields = array(
      'MogutaCMS' => array(
        4, 8
      ),
    );

  public function __construct($typeCatalog = "MogutaCMS") {
    $this->typeCatalog = $typeCatalog;  
    self::$notUpdate = explode(',', MG::getSetting('csvImport-'.$typeCatalog.'-notUpdateCol'));
  }
  
  /**
   * Устанавливает тип импорта.
   * @param string тип
   */
  public function setTypeCatalog($type) {
    $this->typeCatalog = $type;
  }

  /**
   * Устанавливает поля для игнорирования в импорте.
   * @param array поля для игнора
   */
  public function setNotUpdateFields($notUpdate) {
    self::$notUpdate = $notUpdate;
  }
  
  /**
   * Возвращает ошибку при импорте.
   * @return string ошибка
   */
  public function getValidError() {
    return $this->validError;
  }
  
  /**
   * Получает заголовки столбцов из CSV файла.
   * @return array
   */
  public static function getTitleList($parseSeparator = ';') { 
    $titleList = array();
    if($_SESSION['importType'] != 'excel') {
      $file = new SplFileObject("uploads/importCatalog.csv");
      if(!$file->eof()) {
        $data = $file->fgetcsv($parseSeparator);
        foreach($data as $cell=>$value) {
          $encoding = mb_detect_encoding($value, 'UTF-8', true);
					$encoding = $encoding !== false ? $encoding : 'WINDOWS-1251';
          //WARNING в строке ниже находятся два разных символа пробела
          if(!($encoding === 'UTF-8')){
            $value = str_replace(' ',' ',iconv($encoding, 'UTF-8',  $value));
          }
          $titleList[$cell] = $value;
        } 
      }
    } else {
      include_once CORE_DIR.'script/excel/PHPExcel/IOFactory.php';
      include_once CORE_DIR.'script/excel/chunkReadFilter.php';  

      $file = "uploads/importCatalog.xlsx";

      $chunkFilter = new chunkReadFilter();    
      $chunkFilter->setRows(0,1);
      $objReader = PHPExcel_IOFactory::createReaderForFile($file);    
      $objReader->setReadFilter($chunkFilter);
      $objReader->setReadDataOnly(true);    
      $objPHPExcel = $objReader->load($file);
      $sheet = $objPHPExcel->getActiveSheet();
      $colNumber = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());

      for($i=0; $i<$colNumber; $i++) {
        $titleList[$i] = $sheet->getCellByColumnAndRow($i, 1)->getValue();
      }

      unset($objReader); 
      unset($objPHPExcel); 
    }
    return $titleList;
  }
  
  /**
   * Запускает загрузку товаров с заданной строки.
   * @param int $rowId - id строки для старта
   * @return array
   */
  public function startCategoryUpload($rowId = false) {
    if(!$rowId) {
      $rowId = 1;
    }
    
    if(empty($_SESSION['stopProcessImportCsv'])) {
      $data = $this->importFromCsv($rowId, "default");

      if($data === false) {
        $msg = 'Ошибка в CSV файле! '.$this->validError.' line:'.((int)$this->currentRowId+1);
        
        return array(
          'status' => 'error',
          'msg' => $msg
        );
      }
      
      return array(
        'percent' => $data['percent'],
        'status' => 'run',
        'rowId' => $data['rowId']       
      );
    } else {
      unset($_SESSION['stopProcessImportCsv']);
      
      return array(
        'percent' => 0,
        'status' => 'canseled',
        'rowId' => $rowId
      );
    }
  }

  /**
   * Запускает загрузку товаров с заданной строки.
   * @param int $rowId - id строки для старта
   * @return array
   */
  public function startUpload($rowId = false, $schemeType = 'default', $downloadLink = false, $iteration = 1) {    
    if(!$rowId) {
      $rowId = 1;
    }

    self::$iteration = $iteration;

    self::$downloadLink = ($downloadLink == "false")?false:true;

    if(empty($_SESSION['stopProcessImportCsv'])) {
      $data = $this->importFromCsv($rowId, $schemeType);

      if($data===false) {
        $msg = 'Ошибка в CSV файле! '.$this->validError.' line:'.((int)$this->currentRowId+1).'<br />Попробуйте использовать свою схему импорта данных.';
        return
        array(
          'status' => 'error',
          'msg' => $msg
        );
      }
      
      return
        array(
          'percent' => $data['percent'],
          'status' => 'run',
          'startGenerationImage' => ($data['percent']>=100 && $this->autoStartImageGen())?true:false,
          'downloadLink' => self::$downloadLink,
          'rowId' => $data['rowId'],
          'iteration' => ++self::$iteration   
        );
    } else {
      unset($_SESSION['stopProcessImportCsv']);
      return
        array(
          'percent' => 0,
          'status' => 'canseled',          
          'rowId' => $rowId,
          'iteration' => ++self::$iteration
      );
    }
  }

  /**
   * Останавливает процесс импорта.
   */
  public function stopProcess() {
    $_SESSION['stopProcessImportCsv'] = true;
  }

  /**
   * Основной метод импорта из CSV.
   * @param int $rowId - id строки для старта
   * @param string $schemeType - тип импорта
   * @return array
   */
  public function importFromCsv($rowId = 0, $schemeType = null) {
    // Костыль, чтобы правильно парсить числа
    if (version_compare(phpversion(), '7.1', '>=')) {
      ini_set('serialize_precision', -1);
      ini_set("precision", 12);
    }

		$parseSeparator = $_POST['parseSeparator'] === ',' ? ',' : ';';
    $this->maxExecTime = min(30, @ini_get("max_execution_time"));
    
    if(empty($this->maxExecTime)) {
      $this->maxExecTime = 30;
    }
    
    $startTimeSql = microtime(true);
    $infile = false;
    $objReader = new stdClass();
    $file = '';
    $validFormat = true;
    if($_SESSION['importType'] != 'excel') {
      $fileCSVcheck = new SplFileObject("uploads/importCatalog.csv");
      // сразу считаем количество строк в файле
      $percent100 = -1;
      $fileCSVcheck->seek(0);
      while(!$fileCSVcheck->eof()) {   
        $dataTmp = $fileCSVcheck->fgetcsv($parseSeparator); 
        if((isset($data) && count($data) == 1) || $dataTmp == '') break;
        $percent100++;
      }
      
      if($rowId === 1 || empty($rowId)) {
        $rowId = 0;
      }
    } else {
      include_once CORE_DIR.'script/excel/PHPExcel/IOFactory.php';
      include_once CORE_DIR.'script/excel/chunkReadFilter.php';  

      $file = 'uploads/importCatalog.xlsx';

      $inputFileType = PHPExcel_IOFactory::identify($file);

      $objReader = PHPExcel_IOFactory::createReader($inputFileType);

      $worksheetData = $objReader->listWorksheetInfo($file);
      $percent100 = $worksheetData[0]['totalRows'];
      $totalColumns = $worksheetData[0]['totalColumns'];
      if($rowId === 1 || empty($rowId)) {
        $rowId = 0;
      }
    }
  
    while(($rowId <= $percent100)&&!((microtime(true) - $startTimeSql) > $this->maxExecTime - 5)) {
      $data = array();
      $validFormat = true;
      if($_SESSION['importType'] != 'excel') {
        $file = new SplFileObject("uploads/importCatalog.csv");
        $file->seek($rowId);
        $line = $file->current();
        file_put_contents("uploads/tmp.csv", htmlspecialchars_decode($line)."\n"); 

        $fileCSV = new SplFileObject("uploads/tmp.csv");
        $fileCSV->seek(0);

        $this->currentRowId = $rowId;
        $infile = true;
        $data = $fileCSV->fgetcsv($parseSeparator);
      } else {
        $infile = true;
        $chunkFilter = new chunkReadFilter();
        $chunkFilter->setRows($rowId+1, 1);
	      $objReader->setReadFilter($chunkFilter);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($file);
        $sheet = $objPHPExcel->getActiveSheet();
        $colNumber = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        for($c = 0; $c < $colNumber; $c++) {
          $data[] = $sheet->getCellByColumnAndRow($c, $rowId+1)->getValue();
        }
      }

      if($rowId === 0) {
        if($schemeType == 'default') {
          $validFormat = $this->validateFormate(
            $data,
            self::$maskArray[$this->typeCatalog]
          );
          if(!$validFormat) {
            break;          
          }
        }        
        $rowId = 1;
        continue;
      }     
      
      $cData = array(); 
      if(empty(self::$complianceArray)) {
        self::$complianceArray = self::getCompliance($this->typeCatalog, $schemeType);  
      }
      
      $usedArray = array();
      foreach(self::$maskArray[$this->typeCatalog] as $key=>$title) {
        // фикс двойного использования одинаковых полей
        if(in_array(self::$complianceArray[$key], $usedArray)) {
          $cData[$key] = '';
          continue;
        }
        $usedArray[] = self::$complianceArray[$key];
        if (empty(self::$complianceArray)) {
          $v = trim($data[$key]);
        } else {
          if (isset($key) && isset(self::$complianceArray[$key]) && isset($data[self::$complianceArray[$key]])) {
            $v = trim($data[self::$complianceArray[$key]]);
          } else {
            $v = '';
          }
        }

        if(!empty($v) || $v == 0) {    
          if($_SESSION['importType'] != 'excel') {   
            $encoding = mb_detect_encoding($v, 'UTF-8', true);
					  $encoding = $encoding !== false ? $encoding : 'WINDOWS-1251';
            if(!($encoding === 'UTF-8')){
              $v = str_replace(' ',' ',iconv($encoding, 'UTF-8', $v));
            }
            $cData[$key] = $v;
          } else {
            $cData[$key] = $v;
          }
        } else {
          $cData[$key] = '';
        }
      }

      // $complianceArray - массив с установленными соответствиями столбцов (29 - свойства товара)
      // $cData - массив с прочитанными строками
      
      // собираем тайтлы столбцов, если их нет уже, для работы с характеристиками
      if(empty($_SESSION['import']['columnsTitles'])) {
        if($_SESSION['importType'] != 'excel') {
          $file2 = new SplFileObject("uploads/importCatalog.csv");
          $file2->seek(0);
          while(!$file2->eof()) {
            $data1 = $file2->fgetcsv($parseSeparator);    
            for($i = 0; $i < count($data1); $i++) {
              $value = $data1[$i];
              $encoding = mb_detect_encoding($value, 'UTF-8', true);
					    $encoding = $encoding !== false ? $encoding : 'WINDOWS-1251';
              if(!($encoding === 'UTF-8')){
                $value = str_replace(' ',' ',iconv($encoding, 'UTF-8', $value));
              }
              $_SESSION['import']['columnsTitles'][] = $value;
            }
            break;
          }
          unset($file2);
        } else {
          $chunkFilter = new chunkReadFilter();    
          $chunkFilter->setRows(0,1); 
          $objReader->setReadFilter($chunkFilter);
          $objReader->setReadDataOnly(true);    
          $objPHPExcel = $objReader->load($file);
          if (!empty($data)) {
            ini_set("precision", 12);
            foreach ($data as $dataKey => $dataValue) {
              if (gettype($dataValue) === 'double') {
                $data[$dataKey] = floatval($dataValue);
              }
            }
          }
          $sheet = $objPHPExcel->getActiveSheet();
          $colNumber = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
          for($c = 0; $c < $colNumber; $c++) {
            // $data1[] = $sheet->getCellByColumnAndRow($c, 1)->getValue();
            $_SESSION['import']['columnsTitles'][] = $sheet->getCellByColumnAndRow($c, 1)->getValue();
          }
        }
      }
      
      if($_SESSION['importType'] != 'excel') {
        foreach ($data as &$value) {
          $encoding = mb_detect_encoding($value, 'UTF-8', true);
					  $encoding = $encoding !== false ? $encoding : 'WINDOWS-1251';
          if(!($encoding === 'UTF-8')){
            $value = str_replace(' ',' ',iconv($encoding, 'UTF-8', $value));
          }
          
        }
      }

      if((count($_SESSION['import']['columnsTitles']) == count($data) || count($data) == 1) || (CSV_COLUMN_CHECK == 0)) {
        // если полная загрузка, то считываем все
        if($this->typeCatalog == 'MogutaCMS') {
          // читаем характеристики
          if (empty($property)) {$property = array();}
          if(self::$complianceArray[32] != 'none') {

            for($i = self::$complianceArray[32]; $i < count($data); $i++) {
              if(substr_count($_SESSION['import']['columnsTitles'][$i], 'Сложные характеристики') == 0) {
                $property[$_SESSION['import']['columnsTitles'][$i]] = $data[$i];
              }
            }
          }

	        $storages = $wholesales = $opFields = array();
          // читаем склады
          if(self::$complianceArray[30] != 'none') {
            unset($storage);
            for($i = /*0*/self::$complianceArray[30]; $i < count($data); $i++) {
              if(substr_count($_SESSION['import']['columnsTitles'][$i], '[склад') != 0) {
                $storages[$_SESSION['import']['columnsTitles'][$i]] = $data[$i];
              }
            }
          }

          // читаем цены оптовые
          if(self::$complianceArray[29] != 'none') {
            unset($storage);
            for($i = /*0*/self::$complianceArray[29]; $i < count($data); $i++) {
              if(substr_count($_SESSION['import']['columnsTitles'][$i], '[оптовая цена]') != 0) {
                $wholesales[$_SESSION['import']['columnsTitles'][$i]] = $data[$i];
              }
            }
          }

          // читаем доп поля
          if(self::$complianceArray[31] != 'none') {
            unset($storage);
            for($i = /*0*/self::$complianceArray[31]; $i < count($data); $i++) {
              if(substr_count($_SESSION['import']['columnsTitles'][$i], '[op') != 0) {
                $opFields[$_SESSION['import']['columnsTitles'][$i]] = $data[$i];
              }
            }
          }

          $cData['storages'] = array();
          $cData['storages'] = $storages;

          $cData['wholesales'] = array();
          $cData['wholesales'] = $wholesales;

          $cData['property'] = array();
          $cData['property'] = $property;

          $cData['opFields'] = array();
          $cData['opFields'] = $opFields;
        }
        
        $data = $cData;

        self::$fullProduct = $cData;
        $this->currentRowId = $rowId;
        switch($this->typeCatalog) {
          case "MogutaCMS":
            if(!$this->formateMogutaCMS($data)) {
              return false;
            }
            break;
          case "Category":
            if(!$this->formateCategoryMogutaCMS($data)) {
              return false;
            }
            break;
          default:
            if(!$this->formateMogutaCMS($data)) {
              return false;
            }
        }
        $rowId++;
      } else {
        unset($_SESSION['import']);
        self::log('Ошибка форматирования файла на '.($rowId+1).' строке');
        $this->validError = 'Нарушен порядок столбцов или кодировка! Импорт товаров прерван!';
        return false;
      }
    } 
    unset($fileCSV);

    if(!$validFormat) {
      $this->validError = 'Нарушен порядок столбцов или кодировка!';
      return false;
    }
    
    $fileCSV = null;    
    
    $percent = $rowId;
    $percent = $percent * 100 / $percent100;

    if(!$infile) {
      $percent = 100;
    }

    if($percent >= 100) {
      self::log('----------------------------------');
      self::log('Импорт завершен');
      self::log('Обработано '.$percent100.' строк');
      if (!empty($_SESSION['startImportTime'])) {
        self::log('Товары были импортированы/обновлены за '.date('i:s', microtime(true) - $_SESSION['startImportTime']));
      } else {
        self::log('Товары были импортированы/обновлены за '.date('i:s', microtime(true)));
      }
      unset($_SESSION['import']);
    } else {
      self::log('-  -  -  -  -  -  -  -  -  -  -  -');
      self::log('Результат импорта на шаге '.self::$iteration);
      self::log('Начало обработки со строки '.$_SESSION['iterationStartRow']);
      self::log('Обработано '.($rowId-$_SESSION['iterationStartRow']).' строк');
      self::log('Время выполнения шага '.date('i:s', microtime(true) - $_SESSION['iterationImportTime']));
      self::log('----------------------------------');
    }

    $data = array(
      'rowId' => $rowId,
      'percent' => floor($percent)
    );

    Storage::clear();

    return $data;
  }

  /**
   * Сопостовляет прочитанные стобцы из файла с настройками импорта.
   * @param string $importType - тип импорта
   * @param string $scheme - схема
   * @return array
   */
  function getCompliance($importType, $scheme) {
    $data = array();
    
    if($scheme != 'default') {
      $data = MG::getOption('csvImport-last'.$importType.'ColComp');
      $data = unserialize(stripslashes($data));
    } else {
      foreach(Import::$maskArray[$importType] as $id=>$title) {
        $data[$id] = $id;
      }
    }
    
    return $data;
  } 
  
  /**
   * Проверка валидности файла.
   * @param array $data массив считанных данных
   * @param array $maskArray формат построения данных
   * @return bool
   */
  public function validateFormate($data,$maskArray) {
    $result = true;
    if(!empty($maskArray[25])) {
      unset($maskArray[25]);
      }
    // Проверим на соответствие заголовки столбцов.
    foreach($data as $k => $v) {
      $encoding = mb_detect_encoding($v, 'UTF-8', true);
      $encoding = $encoding !== false ? $encoding : 'WINDOWS-1251';
      if(!($encoding === 'UTF-8')){
        $v = str_replace(' ',' ',iconv($encoding, 'UTF-8', $v));
      }
      if(isset($maskArray[$k])) {
        if($maskArray[$k]!=$v) {
          $result = false;      
          $this->validError = 'Столбец "'.$maskArray[$k].'" не обнаружен!';
          break;
        }      
      }
    }    
    return $result;    
  }
  
  /**
   * Импорт или обновление категории.
   * @param array $data массив считанных данных
   * @return bool true
   */
  public function formateCategoryMogutaCMS($data) {
    // Проверка на пустую строку
    if (!(bool) array_filter($data)) {
      return true;
    }
    $arFields = array(
      'title',
      'url',      
      'parent',
      'parent_url',
      'html_content',
      'image_url',
      'menu_icon',
      'meta_title',
      'meta_keywords',
      'meta_desc',
      'seo_content',
      'rate',
      'invisible',
      'activity',
      'export',
      'sort',
      '1c_id',
      'id',
      'seo_title',
      'seo_alt',
    );
    $itemsIn = array();
    
    foreach ($arFields as $key => $field) {
      $itemsIn[$field] = $data[$key];
    }
    
    $category = new Category();
    $itemsIn['csv'] = 1;
    if (!empty($itemsIn['id'])) {
      $category->updateCategory($itemsIn);
    } else {
      $category->addCategory($itemsIn);
    }
    
    return true;
  }
  
  /**
   * Полная выгрузка по формату Moguta.CMS.
   * @param array $data массив считанных данных
   * @param bool $new флаг о начале импорта
   * @return bool true
   */    
  public function formateMogutaCMS($data, $new = false) { 

    // выдераем характеристики, потом обратно засунем
    $property = $data['property'];
    unset($data['property']);

    // выдераем склады, потом обратно засунем
    $storages = $data['storages'];
    unset($data['storages']);
    // выдераем оптовые цены, потом обратно засунем
    $wholesales = $data['wholesales'];
    unset($data['wholesales']);

    $opFields = $data['opFields'];
    unset($data['opFields']);

    foreach($data as $cell => $value) {
      if(!$new && $_POST['schemeType'] != 'default' && self::$complianceArray[$cell] == 'none') {
        continue;
      }
      
      $itemsIn[self::$fields[$this->typeCatalog][$cell]] = trim($value);
    } 

    // костыль
    $itemsIn['cat_id'] = $data[2];

    // суем характеристики обратно
    if(self::$complianceArray[32] != 'none') $itemsIn['property'] = $property;

    // суем склады обратно
    if(self::$complianceArray[30] != 'none') $itemsIn['storages'] = $storages;
    // суем оптовые цены обратно
    if(self::$complianceArray[29] != 'none') $itemsIn['wholesales'] = $wholesales;

    if(self::$complianceArray[31] != 'none') $itemsIn['opFields'] = $opFields;

    if(!empty($data[5])) {
      if(strpos($data[5], '[:param:]')!==false) {
        $variant = explode('[:param:]', $data[5]);
        $itemsIn['variant'] = $variant[0];
        $itemsIn['image'] = str_replace(array('[src=', ']'),'', $variant[1]);
      } else {
        $itemsIn['variant'] = $data[5];
      }     
    }  

    if(self::isEndFile()) return true;

    if(empty($itemsIn['cat_id'])) {
      $itemsIn['cat_id'] = -1;
    }
    
    $itemsIn['price'] = str_replace(',','.',$itemsIn['price']); 
    $itemsIn['old_price'] = str_replace(',','.',$itemsIn['old_price']);
    if (!empty($itemsIn['weight_unit']) && $itemsIn['weight_unit'] != 'kg') {
      $itemsIn['weight'] = MG::getWeightUnit('convert', ['from'=>$itemsIn['weight_unit'],'to'=>'kg','value'=>$itemsIn['weight']]);
    }

    // создаем категорию если надо
    // viewdata($itemsIn['cat_id'].$itemsIn['cat_url']);
    if(empty($_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']])) {
      if($itemsIn['cat_url'] != '') {
        $categories = $this->parseCategoryPath($itemsIn['cat_url']);
        $tmp = array();
        $tmp = explode('/', $itemsIn['cat_id']);
        $i = 0;
        foreach ($categories as $key => $value) {
          $categories[$key]['title'] = $tmp[$i];
          $i++;
        }
        $this->createCategory($categories);
        // viewdata(1);
      } else {
        $categories = $this->parseCategoryPath($itemsIn['cat_id']);
        // viewdata($categories);
        $this->createCategory($categories);
        // viewdata(2);
      }
      $lastElem = array_pop($categories);
      // находим сам урл
      $res = DB::query('SELECT id FROM '.PREFIX.'category WHERE url = '.DB::quote($lastElem['url']).' 
        AND parent_url = '.DB::quote($lastElem['parent_url']=='/'?'':$lastElem['parent_url']));
      while($row = DB::fetchAssoc($res)) {
        $_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']] = $row['id'];
      }
      $itemsIn['category_unit'] = !empty($itemsIn['category_unit']) ? $itemsIn['category_unit'] : '';
      DB::query('UPDATE '.PREFIX.'category SET 
        unit = '.DB::quote($itemsIn['category_unit']).',
        weight_unit = '.DB::quote($itemsIn['weight_unit']).'
        WHERE id = '.DB::quoteInt($_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']]));
    }
    $itemsIn['cat_id'] = $_SESSION['import']['category'][$itemsIn['cat_id'].$itemsIn['cat_url']];

      // костыль если не указана валюта, то проставляем поумолчанию, валюту магазина
      $curSetting = MG::getSetting('currencyRate');  
      if(empty($itemsIn['currency_iso'])) {
        $itemsIn['currency_iso'] =  MG::getSetting('currencyShopIso'); 
      }
      
    // конвентируем старую цену
    $itemsIn['old_price'] = !empty($itemsIn['old_price']) ? intval($itemsIn['old_price']) : 0;
    $curSetting = MG::getSetting('currencyRate');     
    if($itemsIn['currency_iso']) {
      if($itemsIn['currency_iso'] != 'RUR' && $itemsIn['old_price'] != 0) {
        $itemsIn['old_price'] *= $curSetting[$itemsIn['currency_iso']];
      }   
    }  

    // заменяем запятые в весе на точки
    if ($itemsIn['weight']) {
      $itemsIn['weight'] = str_replace(',', '.', $itemsIn['weight']);
    }

    if($itemsIn['cat_id'] == '' && $itemsIn['title'] == '' && $itemsIn['variant'] == '') {
      $this->updateProduct($itemsIn);
    } else {
      $this->createProduct($itemsIn, $itemsIn['cat_id']);
      if($itemsIn['cat_id'] == '') {
        self::log('У товара отсутсвует категория, строка '.$this->currentRowId);
      }
      // проерка того, что товар есть
      $res = DB::query('SELECT id FROM '.PREFIX.'product WHERE cat_id = '.DB::quoteInt(htmlspecialchars($itemsIn['cat_id'])).' AND title = '.DB::quote(htmlspecialchars($itemsIn['title'])));
      if(!$row = DB::fetchAssoc($res)) {
        self::log('Товар с именем "'.$itemsIn['title'].'" не создан (id созданной для товара категории "'.$itemsIn['cat_id'].'")'.DB::quote($itemsIn['title']));
      }
    }

    return true;
  }

  /**
   * Убирает те поля из массива данных продукта, которые не обновляются при обновлении товаров по артикулу
   * @param array $productData - массив с данными о продукте.
   * @return void
   */
  private static function removeNonUpdatableFields(&$productData) {
    // Индексы полей, которые в $complianceArray содержат none (т.е. не требуют обновления)
    $nonUpdatableIndexes = array_keys(array_filter(self::$complianceArray, function ($value) {
      return $value === 'none';
    }));
    // По индексам $complianceArray определяем имена ключей массива $productData,
    // которые не требуют обновления. Пример: [5, 24] -> ['variant', 'recommended']
    $nonUpdatableKeys = [];
    foreach ($nonUpdatableIndexes as $index) {
      array_push($nonUpdatableKeys, self::$fields['MogutaCMS'][$index]);
    }
    // Убираем из данных продукта поля, не требующие обновления
    foreach ($nonUpdatableKeys as $key) {
      unset($productData[$key]);
    }
    // Поле unit убирается отдельно, т. к. приходит как category_unit
    if (array_search(self::$fields['MogutaCMS'][26], $nonUpdatableKeys) >= 0) {
      unset($productData['unit']);
    }
  }

  /**
   * Создает продукт в БД если его не было.
   * @param array $product - массив с данными о продукте.
   * @param int|null $catId - категория к которой относится продукт.
   * @return bool|void
   */
  public function createProduct($product, $catId = null) {
    $model = new Models_Product();
    $cleanedCode = str_replace(array(',','|','"',"'"), '', $product['code']);
    $variant = isset($product['variant'])?$product['variant']:null;
    $img_var = isset($product['image'])?$product['image']:null;
    $property = (isset($product['property'])&&is_array($product['property']))?$product['property']:array();
    $hardProp = isset($product['hard_prop'])?$product['hard_prop']:null;
    $image_variant_url = isset($product['image_variant_url'])?$product['image_variant_url']:null;
    $storages = isset($product['storages'])?$product['storages']:null;
    $wholesales = isset($product['wholesales'])?$product['wholesales']:NULL;
    $opFields = isset($product['opFields'])?$product['opFields']:null;
    //$color = $product['color'];
    //$size = $product['size'];
    $product['price'] = MG::numberDeFormat($product['price']);
    if($product['old_price']) {
      $product['old_price'] = MG::numberDeFormat($product['old_price']);
    } else {
      $product['old_price'] = '';
    }
    $product['unit'] = isset($product['category_unit'])?$product['category_unit']:null;
    unset($product['cat_url']);
    unset($product['variant']);
    unset($product['image']);
    unset($product['property']);
    unset($product['hard_prop']);
    unset($product['storages']);
    unset($product['wholesales']);
    unset($product['category_unit']);
    unset($product['color']);
    unset($product['size']);
    unset($product['opFields']);
    unset($product['image_variant_url']);

    if($product['activity'] == '') {
      if($_POST['defaultActive'] === "true") {
        $product['activity'] = 1;
      } else {
        $product['activity'] = 0;
      }
    }

    $product['activity'] = intval($product['activity']) ? 1 : 0;

    
    if($catId === null) {
      // 1 находим ID категории по заданному пути.
      $product['cat_id'] = MG::translitIt($product['cat_id'], 1);
      $product['cat_id'] = URL::prepareUrl($product['cat_id']);

      if($product['cat_id']) {
        $product['cat_id'] = (empty($product['cat_id'])) ? $product['cat_url'] : $product['cat_id'];
        
        $url = URL::parsePageUrl($product['cat_id']);
        $parentUrl = URL::parseParentUrl($product['cat_id']);
        $parentUrl = $parentUrl != '/' ? $parentUrl : '';                
        
        $cat = MG::get('category')->getCategoryByUrl($url, $parentUrl);     
        $product['cat_id'] = $cat['id'];
      }
    } else {
      $product['cat_id'] = $catId;
    }

    if($catId == -1) {
      unset($product['cat_id']);
    } else {
      $product['cat_id'] = !empty($product['cat_id']) ? $product['cat_id'] : 0;
    }

    if(!empty($product['id']) && is_numeric($product['id'])) {   
      $dbRes = DB::query('SELECT `id`, `url`, `title` FROM `'.PREFIX.'product` WHERE `id` = '.DB::quoteInt($product['id']));
      
      if($res = DB::fetchArray($dbRes)) {        
        if($res['title'] == $product['title']) {
          if($product['url'] == '') $product['url'] = $res['url'];
        }       
        // unset($product['id']);
      } else {
        if(empty($_SESSION['csv_import_full'])) { 
          $_SESSION['csv_import_full'] = 'y';
          $this->formateMogutaCMS(self::$fullProduct, true); 
          return;
        } else {
          unset($_SESSION['csv_import_full']);
        }   
        $arrProd = $model->addProduct($product);               
      }             
    }

    if(empty($arrProd)) {
      // 2 если URL не задан в файле, то транслитирируем его из названия товара.
      $product['url'] = !empty($product['url'])?$product['url']:preg_replace('~-+~','-',MG::translitIt($product['title'], 1));
      $product['url'] = str_replace(array(':', '/'),array('', '-'),$product['url']);
      $product['url'] = URL::prepareUrl($product['url'], true);  
      
      // сначала поиск по артикулу
      if(!empty($product['code'])) {
        $res = DB::query('
          SELECT id, url
          FROM `'.PREFIX.'product`
          WHERE code = '.DB::quote($product['code'])." OR `code` = ".DB::quote($cleanedCode)
        );
        
        $alreadyProduct = DB::fetchAssoc($res);
        
        if(empty($alreadyProduct['id'])) {
          $res = DB::query('
            SELECT p.id, p.url
            FROM `'.PREFIX.'product` p
              LEFT JOIN `'.PREFIX.'product_variant` pv
                ON pv.product_id = p.id
            WHERE pv.code = '.DB::quote($product['code'])." OR pv.code = ".DB::quote($cleanedCode)
          );
          
          $alreadyProduct = DB::fetchAssoc($res);
        } 
      }

      // если не нашли товар по артикулу, то тогда ищем по названию
      // если включена опция "Обновлять только по артикулу", то не заходим в это условие
      // Если сейчас не загружаются варианты, то тоже не заходим
      if(empty($alreadyProduct['id']) && !empty($variant) && (!isset($_POST['updateByArticle']) || $_POST['updateByArticle'] == 'false' || $_POST['updateByArticle'] == false)) {
        if(empty($product['cat_id']) || $product['cat_id'] == 0) {
          $alreadyProduct = $model->getProductByUrl($product['url']);
        } else {
          $alreadyProduct = $model->getProductByUrl($product['url'], $product['cat_id']);
        }
      }
      if(empty($alreadyProduct['id']) && !empty($variant) && (!isset($_POST['updateByArticle']) || $_POST['updateByArticle'] == 'false' || $_POST['updateByArticle'] == false)) {
        if($product['cat_id'] == 0) {
          $alreadyProduct = $model->getProductByUrl($product['url']);
        } else {
          $alreadyProduct = $model->getProductByUrl($product['url'], $product['cat_id']);
        }
      }

      // Если в базе найден этот продукт, то при обновлении будет сохранен ID и URL. 
      if(!empty($alreadyProduct['id'])) {
        $product['id'] = $alreadyProduct['id'];
        if($product['url'] == '') $product['url'] = $alreadyProduct['url'];
      }

      if(((empty($alreadyProduct['id']) || !empty($variant))) && empty($product['id'])) {
	      $productId = 0;
        $res = DB::query('SELECT MAX(id) FROM '.PREFIX.'product');
        while($row = DB::fetchAssoc($res)) {
          $productId = ++$row['MAX(id)'];
        }
        //Проверка на ункиальный url, чтобы не генерить товары с одинаковым урлом добавляем в конец урла id-шник по аналогии с клонированием
        $res = DB::query("SELECT `id` FROM `".PREFIX."product` WHERE `url` = ".DB::quote($product['url']));
        if($row = DB::fetchAssoc($res)){
          $product['url'].='_'.$productId;
        }

        $product['id'] = $productId;
        $model->addProduct($product);
      }
        // Убираем те поля, которые пользователь не пытается обновить (Для обновления по артикулу)
        if ($_POST['typeCatalog'] === 'MogutaCMS' && $_POST['updateByArticle'] === 'true') {
          self::removeNonUpdatableFields($product);
        }
        // обновляем товар, если его не было то метод вернет массив с параметрами вновь созданного товара, в том числе и ID. Иначе  вернет true
        $arrProd = $model->updateProduct($product);
    }
       
    $product_id = $product['id']?$product['id']:$arrProd['id'];   
    $categoryId = $product['cat_id'];
    $productId = $product_id;
    $listProperty = $property;

    if (empty($_SESSION['import_csv']['uploadedImages'][$product_id])) {
      $_SESSION['import_csv']['uploadedImages'] = [
        $product_id => [],
      ];
    }
    $alreadyUploadedImages = $_SESSION['import_csv']['uploadedImages'][$product_id];

    // если в строке содержится ссылка
    if (strpos($product['image_url'], "http://") !== false|| strpos($product['image_url'], "https://") !== false|| strpos($product['image_url'], "ftp://") !== false) {
      //self::$downloadLink = true;
      self::$downloadLink = false;
      $images = explode('|', $product['image_url']);
      $imagesToDB = [];
      $imagesToMove = [];

      foreach ($images as $image) {
        $urlHash = md5($image);

        foreach ($alreadyUploadedImages as $alreadyUploadedImage) {
          if (strpos($alreadyUploadedImage, $urlHash) === 0) {
            $imagesToDB[] = $alreadyUploadedImage;
            continue 2;
          }
        }

        $paths = $this->getUrlImagePaths($image);

        if (!empty($paths)) {
          $imagesToMove[] = $paths['pathToMove'];
          $imagesToDB[] = $paths['pathToDB'];
        }
      }
      if (!empty($imagesToDB)) {
        $alreadyUploadedImages = $imagesToDB + $alreadyUploadedImages;
        $_SESSION['import_csv']['uploadedImages'][$product_id] = $alreadyUploadedImages;
        $product['image_url'] = implode('|', $imagesToDB);
        $model->updateProduct($product);
      }
      if (!empty($imagesToMove)) {
        $model->movingProductImage($imagesToMove, $product_id, 'uploads/prodtmpimg');
        unset($imagesToMove);
      }
    } elseif(strpos($product['image_url'], '[:param:]')!==false) {
      // Парсим изображение, его alt и title.
      $images = $this->parseImgSeo($product['image_url']);
      $product['image_url'] = $images[0];
      $imagesToMove[] = $images[0];
      $product['image_alt'] = $images[1];
      $product['image_title'] = $images[2];
      $model->updateProduct($product);
    }


    // удаляем характеристики // TODO
    if(self::$complianceArray[32] != 'none') {
      DB::query('DELETE pupd FROM '.PREFIX.'product_user_property_data AS pupd
        INNER JOIN '.PREFIX.'property AS p ON p.id = pupd.prop_id 
        WHERE pupd.product_id = '.DB::quoteInt($product['id']).' AND p.type NOT IN (\'color\', \'size\')');
    }
    // добавляем характеристики к товарам
    foreach ($property as $key => $value) {
      if(!empty($value)||$value==='0') {
        // проверяем не является ли характеристика размерной сеткой, если да, то обрабатываем ее подругому
        if((substr_count($key, '[size]') == 1)||(substr_count($key, '[color]') == 1)) {
          Property::createSizeMapPropFromCsv($key, $value, $product_id, $variant, $product['cat_id']);
          // continue;
        } else {
          // создаем характеристики и получаем их id 
          if(empty($_SESSION['import']['property'][$key])) {
            $_SESSION['import']['property'][$key] = Property::createProp($key);
          }
          // связываем характеристику с категорией
          Property::createPropToCatLink($_SESSION['import']['property'][$key], $product['cat_id']);
          // записываем содержимое характеристики для товара
          Property::createProductStringProp($value, $product['id'], $_SESSION['import']['property'][$key]);
        }
      }
    }
    Property::createHardPropFromCsv($hardProp, $product_id, $product['cat_id']);

    // добавляем оптовые цены только для одиночного товара
    if(is_array($wholesales) && !empty($wholesales)) {
      DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
              product_id = '.DB::quoteInt($product_id).' AND variant_id = 0');
    
      foreach ($wholesales as $key => $value) {
        if(!empty($value) && (float)$value > 0) {
          // $count = preg_replace("/[^0-9]/", '', $key);
          
          $tmp = explode('Количество от ', $key);
          $tmpCount = $tmp[1];
          $tmpCount = explode(' для цены ', $tmpCount);
          $count = $tmpCount[0];
          $tmpGroup = explode(' [оптовая цена]', $tmpCount[1]);
          $group = $tmpGroup[0];

          DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
            ('.DB::quoteInt($product_id).', 0, 
            '.DB::quoteFloat($count).', '.DB::quote((float)$value, true).', '.DB::quoteInt($group).')');
        }
      }
    }

    // добавляем склады
    if(is_array($storages) && !empty($storages)) {
      if(empty($_SESSION['import']['storageArray'])) {
        $storageArray = array();
      } else {
        $storageArray = $_SESSION['import']['storageArray'];
      }

      $storageArray = unserialize(stripcslashes(MG::getSetting('storages')));
      foreach($storages as $key => $value) {
        // дробим ключ, чтобы все узнать
        $key = explode('[', str_replace(']', '', $key));
        $storageId = explode('=', $key[1]);
        $storageItem['id'] = $storageId[1];
        $storageItem['name'] = $key[0];
        if($storageItem['id'] == '') {
          $storageItem['id'] = substr(md5($storageItem['name']), 0, 10);
        }
        $findStorage = false;
        foreach ($storageArray as $item) {
          if($item['id'] == $storageItem['id']) {
            $findStorage = true;
          }
        }
        if(!$findStorage) {
          $storageArray[] = $storageItem;
        }
        // заполняем склад
        if(empty($variantId)) $variantId = 0;
        $productModel = new Models_Product();
        $productModel->updateStorageCount($product_id, $storageItem['id'], $value, $variantId);
        $productModel->recalculateStoragesById($product_id);
      }
      if(empty($_SESSION['import']['storageArray'])) {
        MG::setOption('storages', addslashes(serialize($storageArray)));
        $_SESSION['import']['storageArray'] = $storageArray;
      }
    }

    if(is_array($opFields) && !empty($opFields)) {
      $opFieldsM = new Models_OpFieldsProduct($product_id);
      $fields = $opFieldsM->get();
      foreach ($opFields as $key => $value) {
        $fieldId = 0;
        $tmp = explode('[op id=', $key);
        $fieldId = str_replace(']', '', $tmp[1]);
        if($fieldId <= 0 && $fieldId == '') continue;
        if ( isset($fields[$fieldId]['value']) ) {
          $fields[$fieldId]['value'] = $value;
        }
      }
      $opFieldsM->fill($fields);
      $opFieldsM->save();
    }

    // viewData($variant);
    
    if(!$variant) {
      return true;
    }
    
    $var = $model->getVariants($product['id']);

    $varUpdate = null;
    
    if(!empty($var)) {
      foreach($var as $k => $v) {
        if(($v['code'] == $product['code'] || $v['code'] == $cleanedCode)&& $v['code'] !== '' && $v['product_id'] == $product_id) {
          $varUpdate = $v['id'];
          break;
        }
        if($v['title_variant'] == $variant && $v['product_id'] == $product_id) {
          $varUpdate = $v['id'];
        }
      }
    }

    // Иначе обновляем существующую запись в таблице вариантов.
    $varFields = array(      
      'price',
      'old_price',
      'count',
      'code',  
      'weight',
      'activity',
      'currency_iso'
    );
    
    $newVariant = array(
      'product_id' => $product_id,
      'title_variant' => $variant,
    );
    
    if($img_var) {
      $newVariant['image'] = $img_var;
    }

    //Распарсивание картинки из поля "Изображение варианта"
    if($image_variant_url){
      $imagesDir = SITE_DIR.'uploads'.DS.'product'.DS.floor($product_id / 100).'00'.DS.$product_id;
      $imagesDirFiles = [];
      if (is_dir($imagesDir)) {
        $imagesDirFiles = array_diff(scandir($imagesDir), ['.', '..', 'thumbs']);
      }
      // если в строке содержится ссылка
      $uploadVariantImage = true;
      if (strpos($image_variant_url, "http://") !== false || strpos($image_variant_url, "https://") !== false || strpos($image_variant_url, "ftp://") !== false) {
        $urlHash = md5($image_variant_url);
        foreach ($alreadyUploadedImages as $alreadyUploadedImage) {
          if (strpos($alreadyUploadedImage, $urlHash) === 0) {
            $newVariant['image'] = $alreadyUploadedImage;
            $uploadVariantImage = false;
            break;
          }
        }
        if ($uploadVariantImage) {
          $paths = $this->getUrlImagePaths($image_variant_url);
          if (!empty($paths)) {
            $alreadyUploadedImages = $alreadyUploadedImages[] = $paths['pathToDb'];
            $_SESSION['import_csv']['uploadedImages'][$product_id] = $alreadyUploadedImages;
            $imagesToMove[] = $paths['pathToMove'];
            $newVariant['image'] = $paths['pathToDB'];
          }
        }
      } else {
        $newVariant['image'] = $image_variant_url;
      }
    }

    if (isset($imagesToMove)) {
      $model->movingProductImage($imagesToMove, $product_id, 'uploads/prodtmpimg');
    }

    foreach($varFields as $field) {
      if(isset($product[$field])) {
        $newVariant[$field] = $product[$field];   
      }
      if ($field == 'code') {
        $newVariant[$field] = $cleanedCode;
      }
    }

    $model->importUpdateProductVariant($varUpdate, $newVariant, $product_id);

    // Обновляем продукт по первому варианту.
    $res = DB::query('
      SELECT  pv.*
      FROM `'.PREFIX.'product_variant` pv    
      WHERE pv.product_id = '.DB::quote($product_id).'
      ORDER BY sort');
    if($row = DB::fetchAssoc($res)) {

      if(!empty($row)) {
        if($product['title']) {
          $row['title'] = $product['title'];
        }        
        
        $row['id'] = $row['product_id'];
		    unset($row['1c_id']);
        unset($row['image']); 
        unset($row['sort']);
        unset($row['title_variant']);
        unset($row['product_id']);
        $model->updateProduct($row);
      }
    }

    // добавляем оптовые цены
    if($wholesales != NULL) {
	    if(empty($variantId)) $variantId = 0;
      $res = DB::query('SELECT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($product_id).'
        AND title_variant = '.DB::quote($variant));
      while ($row = DB::fetchAssoc($res)) {
        $variantId = $row['id'];
      }
      DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
              product_id = '.DB::quoteInt($product_id).' AND variant_id = '.DB::quoteInt($variantId));
      foreach ($wholesales as $key => $value) {
        if(!empty($value) && (float)$value > 0) {
          // $count = preg_replace("/[^0-9]/", '', $key);
          $tmp = explode('Количество от ', $key);
          $tmpCount = $tmp[1];
          $tmpCount = explode(' для цены ', $tmpCount);
          $count = $tmpCount[0];
          $tmpGroup = explode(' [оптовая цена]', $tmpCount[1]);
          $group = $tmpGroup[0];

          DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
            ('.DB::quoteInt($product_id).', '.DB::quoteInt($variantId).', 
            '.DB::quoteFloat($count).', '.DB::quote((float)$value, true).', '.DB::quoteInt($group).')');
        }
      }
    }

    // добавляем склады
    if($storages !== NULL) {
      if(empty($variantId)) {
        $res = DB::query('SELECT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($product_id).'
          AND title_variant = '.DB::quote($variant));
        while ($row = DB::fetchAssoc($res)) {
          $variantId = $row['id'];
        }
      }
      if(empty($_SESSION['import']['storageArray'])) {
        $storageArray = array();
      } else {
        $storageArray = $_SESSION['import']['storageArray'];
      }

      $storageArray = unserialize(stripcslashes(MG::getSetting('storages')));
      if (is_array($storages) && !empty($storages)) {
        foreach($storages as $key => $value) {
          // дробим ключ, чтобы все узнать
          $key = explode('[', str_replace(']', '', $key));
          $storageId = explode('=', $key[1]);
          $storageItem['id'] = $storageId[1];
          $storageItem['name'] = $key[0];
          if($storageItem['id'] == '') {
            $storageItem['id'] = substr(md5($storageItem['name']), 0, 10);
          }
          $findStorage = false;
          foreach ($storageArray as $item) {
            if($item['id'] == $storageItem['id']) {
              $findStorage = true;
            }
          }
          if(!$findStorage) {
            $storageArray[] = $storageItem;
          }
          // заполняем склад
          if(empty($variantId)) $variantId = 0;
          $productModel = new Models_Product();
          $productModel->updateStorageCount($product_id, $storageItem['id'], $value, $variantId);
          $productModel->recalculateStoragesById($product_id);
        }
      }
      if(empty($_SESSION['import']['storageArray'])) {
        MG::setOption('storages', addslashes(serialize($storageArray)));
        $_SESSION['import']['storageArray'] = $storageArray;
      }
    }

    if($opFields !== NULL && $opFields !== '') {
	    if(empty($variantId)) $variantId = 0;
      $res = DB::query('SELECT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($product_id).'
        AND title_variant = '.DB::quote($variant));
      while ($row = DB::fetchAssoc($res)) {
        $variantId = $row['id'];
      }
      $opFieldsM = new Models_OpFieldsProduct($product_id);
      $fields = $opFieldsM->get();
      foreach ($opFields as $key => $value) {
        $fieldId = 0;
        $tmp = explode('[op id=', $key);
        $fieldId = str_replace(']', '', $tmp[1]);
        if($fieldId <= 0 && $fieldId == '') continue;
        if ( isset($fields[$fieldId]['variant'][$variantId]['value']) ) {
          $fields[$fieldId]['variant'][$variantId]['value'] = $value;
        }
      }
      $opFieldsM->fill($fields);
      $opFieldsM->save();
    }

    // добавляем характеристики к товарам // TODO 
    foreach ($property as $key => $value) {
      if(!empty($value)) {
        // проверяем не является ли характеристика размерной сеткой, если да, то обрабатываем ее подругому
        if((substr_count($key, '[size]') == 1)||(substr_count($key, '[color]') == 1)) {
          Property::createSizeMapPropFromCsv($key, $value, $product_id, $variant, $product['cat_id']);
          // continue;
        }
      }
    }
  }

  /**
   * Создает категории в БД если их небыло.
   * @param array $categories - массив категорий полученный из записи вида категория/субкатегория/субкатегория2.
   */
  public function createCategory($categories) {
    foreach($categories as $category) {
      $category['parent_url'] = $category['parent_url'] != '/'?$category['parent_url']:'';

      if($category['parent_url']) {
        $pUrl = URL::parsePageUrl($category['parent_url']);
        $parentUrl = URL::parseParentUrl($category['parent_url']);
        $parentUrl = $parentUrl != '/'?$parentUrl:'';
      } else {
        $pUrl = $category['url'];
        $parentUrl = $category['parent_url'];
      }

      $res = DB::query('SELECT COUNT(id) FROM '.PREFIX.'category WHERE url = '.DB::quote($category['url']).' 
        AND parent_url = '.DB::quote($category['parent_url']=='/'?'':$category['parent_url']));
      while ($catRow = DB::fetchAssoc($res)) {
        if($catRow['COUNT(id)'] == 0) {
          $parentId = 0;
          if($category['parent_url'] != '') {
            $sections = explode('/', $category['parent_url']);
            $lastSection = $sections[count($sections)-2];
            if(count($sections) > 2) {
              array_pop($sections);
              array_pop($sections);
              $parentTmpUrl = implode('/', $sections).'/';
            } else {
              $parentTmpUrl = '';
            }
            $parentIdRes = DB::query('SELECT id FROM '.PREFIX.'category WHERE url = '.DB::quote($lastSection).' AND parent_url = '.DB::quote($parentTmpUrl));
            while ($row = DB::fetchAssoc($parentIdRes)) {
              $parentId = $row['id'];
            }
          }
          if (!isset($_SESSION['import']['categoryCounter'])) {
            $_SESSION['import']['categoryCounter'] = 0;
          }
          DB::query('INSERT INTO '.PREFIX.'category (title, url, parent_url, parent, sort) VALUES
            ('.DB::quote($category['title']).', '.DB::quote($category['url']).', '.DB::quote($category['parent_url']).',
            '.DB::quoteInt($parentId).', '.DB::quoteInt(++$_SESSION['import']['categoryCounter']).')');
        }
      }
    }
  }

  /**
   * Парсит путь категории возвращает набор категорий.
   * @param string $path список категорий через / слэш.
   * @return array массив с данными о категории
   */
  public function parseCategoryPath($path) {
    $i = 1;

    $categories = array();
    if(!$path || $path == -1) {
      return $categories;
    }

    $parent = $path;
    $parentForUrl = str_replace(array('«', '»'), '', $parent);    
    $parentTranslit = MG::translitIt($parentForUrl, 1);
    $parentTranslit = URL::prepareUrl($parentTranslit);

    $parentTranslit = URL::clean($parentTranslit);

    $categories[$parent]['title'] = URL::parsePageUrl($parent);
    $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
    $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
    $categories[$parent]['parent'] = 0;

    while($parent != '/') {
      $parent = URL::parseParentUrl($parent);
      $parentForUrl = str_replace(array('«', '»'), '', $parent);
      $parentTranslit = MG::translitIt($parentForUrl, 1);
      $parentTranslit = URL::prepareUrl($parentTranslit);
      $parentTranslit = URL::clean($parentTranslit);
      if($parent != '/') {
        $categories[$parent]['title'] = URL::parsePageUrl($parent);
        $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
        $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
        $categories[$parent]['parent_url'] = $categories[$parent]['parent_url'] != '/'?$categories[$parent]['parent_url']:'';
        $categories[$parent]['parent'] = 0;
      }
    }

    $categories = array_reverse($categories);

    return $categories;
  }

  /**
   * Сравнивает создаваемую категорию, с имеющимися ранее.
   * Если обнаруживает, что аналогичная категория раньше существовала,то возвращает ее старый ID.   
   * @param string $title название товара.
   * @param string $path путь.
   * @return int|null id категории.
   */
  public function getCategoryId($title, $path) {
    $path = trim($path, '/');

    $sql = '
      SELECT cat_id
      FROM `'.PREFIX.'import_cat`
      WHERE `title` ='.DB::quote($title)." AND `parent` = ".DB::quote($path);

    $res = DB::query($sql);
    if($row = DB::fetchAssoc($res)) {
      return $row['cat_id'];
    }
    return null;
  }

  /**
   * Возвращает старый ID для товара.
   * то возвращает ее старый ID.
   * @param string $title - название товара.
   * @param int $cat_id - id категории.
   * @return int|null id товара.
   */
  public function getProductId($title, $cat_id) {
    $sql = '
      SELECT product_id
      FROM `'.PREFIX.'import_prod`
      WHERE `title` ='.DB::quote($title)." AND `category_id` = ".DB::quote($cat_id);

    $res = DB::query($sql);
    if($row = DB::fetchAssoc($res)) {
      return $row['product_id'];
    }
    return null;
  }
  /**
   * Возвращает массив из изображений и seo-настройки к ним - alt и title
   * @param string $listImg пример $listImg = 'noutbuk.png[:param:][alt=ноутбук][title=ноутбук]|noutbuk-Dell-Inspiron-N411Z-oneside.png[:param:][alt=ноутбук черного цвета][title=ноутбук черного цвета]';
   * @return array
   */
  function parseImgSeo($listImg) {  	
    $images_alt = '';
    $images_title = '';
	  $images_url = '';
    $images = explode('|', $listImg);
    foreach ($images as $value) {
      $item = explode('[:param:]', $value);
      $images_url .= $item[0].'|';
      if (isset($item[1])) {
        $seo = explode(']', $item[1]);
      } else {
        $seo = array('','');
      }
      $images_alt .= str_replace('[alt=','', $seo[0]).'|';
      $images_title .= str_replace('[title=','', $seo[1]).'|';  
    }
    $result = array(substr($images_url, 0, -1), substr($images_alt, 0, -1), substr($images_title, 0, -1));
    return $result;
  }

  /**
   * Загружает изображения с сайтов по ссылке.
   * @param string $url - местонахождение изображения в сети
   * @return bool|void
   */
  function downloadImgFromSite($url) {
    $tmp = str_replace(array('http://', 'https://'), '', $url);
    $tmp = explode('/', $tmp);
    $domain = $tmp[0];
    require_once('mg-core/lib/idna_convert.class.php');
    $idn = new idna_convert(array('idn_version'=>2008));
    $punycode = $domain;
    $newDomain = (stripos($punycode, 'xn--')!==false) ? $punycode : $idn->encode($punycode);
    if ($domain != $newDomain) {
      $url = str_replace($domain, $newDomain, $url);
    }
    
    if(!$this->autoStartImageGen()) {
      return false;
    } 

    $path = SITE_DIR.'uploads'.DS.'tempimage';
    @mkdir($path, 0777);

    $baseUrlImage = basename($url);
    $tmp = explode('_', $baseUrlImage, 2);
    $urlNew = str_replace(basename($url), $tmp[1], $url);

  	$ch = curl_init($urlNew);  

    $name = explode('?', basename($url));
    $name = $name[0];
    $name = urldecode($name);
  	$fp = fopen($path.'/'.$name, 'wb');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  	curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  	curl_setopt($ch, CURLOPT_HEADER, 0);
  	curl_exec($ch);
  	curl_close($ch);
  	fclose($fp);
  }

  /**
   * Определяет нужно ли производить загрузку изображений.
   * @return bool
   */
  function autoStartImageGen() {
    if(self::$downloadLink == true) {
      return true;
    }
    return false;
  }

  /**
   * Записывает лог импорта в директорию временных файлов сайта в отдельный файл .
   * @param string $text текст для записи
   * @param bool $new начинать ли новый файл
   */
  function log($text, $new = false) {
	  $string = '';
    $fileName = TEMP_DIR . 'data_csv' . DS . 'import_csv_log.txt';
    if($new) {
      if (file_exists($fileName)) {
        unlink($fileName);
      }
      $string = 'Лог для импорта каталога из CSV'."\r\n";
      $string .= 'Начало импорта - '.date('d.m.Y H:i:s')."\r\n";
      $string .= '----------------------------------'."\r\n";
    }
    if($text != '') {
      $string .= print_r($text, true)."\r\n";
    }
    mg::createTempDir('data_csv');
    $f = fopen($fileName, 'a+');
    fwrite($f, $string);
    fclose($f);
  }

  /**
   * Определяет при чтении CSV конец файла, для прерывания процесса импорта.
   * @return bool
   */
  function isEndFile() {
    foreach (self::$fullProduct as $item) {
      if(!is_array($item) && !empty($item)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Упрощенный метод импорта товаров, обновляет только цены и остатки.
   * @param array $data текст для записи
   * @return bool
   */
  public function updateProduct($data) { 

    $storages = $data['storages'];
    $wholesales = $data['wholesales'];
    $opFields = $data['opFields'];
    $itemsIn = array();
    $produncItemsIn = array();
    $arrayToUpdateFilter = array('code', 'price', 'old_price', 'count', 'weight', 'image_url', 'image_variant_url'); //Это для вариантов
    $productToUpdateFilter = array('storages', 'wholesales', 'opFields', 'image_variant_url'); //Это для самого товара ( Что необходимо убрать)
    foreach ($data as $key => $value) {
      if ($key === 'price' && $value === '') {continue;}
      if(in_array($key, $arrayToUpdateFilter)) {
        $itemsIn[$key] = $value;
      }
      if(!in_array($key, $productToUpdateFilter) && (!empty($value) || $value == '0')){
        $produncItemsIn[$key] = $value;
      }
    }

    if (empty($data['image_url'])) {
      unset($itemsIn['image_url']);
    }

    $cleanedCode = str_replace(array(',','|','"',"'"), '', $data['code']);
    // foreach ($itemsIn as $key => $item) {
    //   if($item == '') unset($itemsIn[$key]); 
    // }

    $tmp = DB::query('SELECT `id` FROM `'.PREFIX.'product` WHERE code = '.DB::quote($data['code']).' OR code = '.DB::quote($cleanedCode));
    $productId = null;
    if ($row = DB::fetchAssoc($tmp)) {
       $productId = $row['id'];
    }

    $model = new Models_Product();

    if ($productId!==null) {
      if (empty($_SESSION['import_csv']['uploadedImages'][$productId])) {
        $_SESSION['import_csv']['uploadedImages'] = [
          $productId => [],
        ];
      }
      $alreadyUploadedImages = $_SESSION['import_csv']['uploadedImages'][$productId];
  
      // если в строке содержится ссылка
      if (strpos($data['image_url'], "http://") !== false|| strpos($data['image_url'], "https://") !== false|| strpos($data['image_url'], "ftp://") !== false) {
        self::$downloadLink = true;
        $images = explode('|', $data['image_url']);
        $imagesToDB = [];
        $imagesToMove = [];
  
        foreach ($images as $image) {
          $urlHash = md5($image);
  
          foreach ($alreadyUploadedImages as $alreadyUploadedImage) {
            if (strpos($alreadyUploadedImage, $urlHash) === 0) {
              $imagesToDB[] = $alreadyUploadedImage;
              continue 2;
            }
          }
  
          $paths = $this->getUrlImagePaths($image);
  
          if (!empty($paths)) {
            $imagesToMove[] = $paths['pathToMove'];
            $imagesToDB[] = $paths['pathToDB'];
          }
        }
        if (!empty($imagesToDB)) {
          $alreadyUploadedImages = $imagesToDB + $alreadyUploadedImages;
          $_SESSION['import_csv']['uploadedImages'][$productId] = $alreadyUploadedImages;
          $produncItemsIn['image_url'] = implode('|', $imagesToDB);
        }
        if (!empty($imagesToMove)) {
          $model->movingProductImage($imagesToMove, $productId, 'uploads/prodtmpimg');
          unset($imagesToMove);
        }
      }

      if (isset($produncItemsIn['activity'])) {
        $produncItemsIn['activity'] = intval($produncItemsIn['activity']) ? 1 : 0;
      }

      DB::query('
      UPDATE `' . PREFIX . 'product`
      SET ' . DB::buildPartQuery($produncItemsIn) . '
      WHERE `id`=' . DB::quoteInt($productId)
      );
    }
    if (isset($imagesToMove)) {
      $model->movingProductImage($imagesToMove, $productId, 'uploads/prodtmpimg');
      unset($imagesToMove);
    }

    if (!empty($itemsIn['image_url'])) {
      $itemsIn['image'] = $itemsIn['image_url'];
      unset($itemsIn['image_url']);
    }

    if (isset($itemsIn['image_variant_url'])) {
      if ($productId == null) {
        $getProductIdSql = 'SELECT `product_id` FROM `'.PREFIX.'product_variant` '.
          'WHERE `code` = '.DB::quote($data['code']).';';
        $getProductIdQuery = DB::query($getProductIdSql);
        if ($getProductIdResult = DB::fetchAssoc($getProductIdQuery)) {
          $productId = $getProductIdResult['product_id'];
        }
      }

      if ($productId!==null) {

        if (empty($alreadyUploadedImages)) {
          if (empty($_SESSION['import_csv']['uploadedImages'][$productId])) {
            $_SESSION['import_csv']['uploadedImages'] = [
              $productId => [],
            ];
          }
          $alreadyUploadedImages = $_SESSION['import_csv']['uploadedImages'][$productId];
        }

        $image_variant_url = $itemsIn['image_variant_url'];
        if (strpos($image_variant_url, "http://") !== false || strpos($image_variant_url, "https://") !== false || strpos($image_variant_url, "ftp://") !== false) {
          $imagesToMove = [];
          $uploadVariantImage = true;
          $urlHash = md5($image_variant_url);
          foreach ($alreadyUploadedImages as $alreadyUploadedImage) {
            if (strpos($alreadyUploadedImage, $urlHash) === 0) {
              $itemsIn['image'] = $alreadyUploadedImage;
              $uploadVariantImage = false;
              break;
            }
          }
          if ($uploadVariantImage) {
            $paths = $this->getUrlImagePaths($image_variant_url);
            if (!empty($paths)) {
              $alreadyUploadedImages = $alreadyUploadedImages + $paths['pathToDb'];
              $_SESSION['import_csv']['uploadedImages'][$product_id] = $alreadyUploadedImages;
              $imagesToMove[] = $paths['pathToMove'];
              $itemsIn['image'] = $paths['pathToDB'];
            }
          }
        } else {
          $itemsIn['image'] = $image_variant_url;
        }

        if (!empty($imagesToMove)) {
          $model->movingProductImage($imagesToMove, $productId, 'uploads/prodtmpimg');
        }
      } else {
        unset($itemsIn['image']);
      }
      unset($itemsIn['image_variant_url']);
    }

    DB::query('
      UPDATE `'.PREFIX.'product_variant`
      SET '.DB::buildPartQuery($itemsIn).'
      WHERE code = '.DB::quote($data['code'])." OR code = ".DB::quote($cleanedCode)
    );

    $model = new Models_Product();
    $currencyShopIso = MG::getSetting('currencyShopIso');

    if($productId!==null) {
      $model->updatePriceCourse($currencyShopIso, array($row['id']));    
      $productId = $row['id'];
      $variantId = 0;
    } else {
      $res = DB::query('
        SELECT product_id
        FROM `'.PREFIX.'product_variant`
        WHERE code = '.DB::quote($data['code'])." OR code = ".DB::quote($cleanedCode)
      );
      
      if($row = DB::fetchAssoc($res)) {     
        $model->updatePriceCourse($currencyShopIso, array($row['product_id']));    
      }
    }

    // пытаемся достать id варианта, если раньше не получилось
    $res = DB::query('
      SELECT product_id, id
      FROM `'.PREFIX.'product_variant`
      WHERE code = '.DB::quote($data['code'])." OR code = ".DB::quote($cleanedCode)
    );
    if($row = DB::fetchAssoc($res)) {     
      $model->updatePriceCourse($currencyShopIso, array($row['product_id']));    
      $productId = $row['product_id'];
      $variantId = $row['id'];
    }

	  if (empty($productId)) {$productId = 0;}
	  if (empty($variantId)) {$variantId = 0;}
    // добавляем оптовые цены
    if($wholesales != NULL) {
      
      DB::query('DELETE FROM '.PREFIX.'wholesales_sys WHERE 
              product_id = '.DB::quoteInt($productId).' AND variant_id = '.DB::quoteInt($variantId));
      foreach ($wholesales as $key => $value) {
        if(!empty($value)) {
          // $count = preg_replace("/[^0-9]/", '', $key);
          $tmp = explode('Количество от ', $key);
          $tmpCount = $tmp[1];
          $tmpCount = explode(' для цены ', $tmpCount);
          $count = $tmpCount[0];
          $tmpGroup = explode(' [оптовая цена]', $tmpCount[1]);
          $group = $tmpGroup[0];

          DB::query('INSERT INTO '.PREFIX.'wholesales_sys (product_id, variant_id, count, price, `group`) VALUES
            ('.DB::quoteInt($productId).', '.DB::quoteInt($variantId).', 
            '.DB::quoteFloat($count).', '.DB::quoteInt($value).', '.DB::quoteInt($group).')');
        }
      }
    }

    //Доп поля
    if($opFields !== NULL && $opFields !== '') {
	    if(empty($variantId)) $variantId = 0;
      $res = DB::query('SELECT id FROM '.PREFIX.'product_variant WHERE product_id = '.DB::quoteInt($productId).'
        AND title_variant = '.DB::quote($variant));
      while ($row = DB::fetchAssoc($res)) {
        $variantId = $row['id'];
      }
      $opFieldsM = new Models_OpFieldsProduct($productId);
      $fields = $opFieldsM->get();
      foreach ($opFields as $key => $value) {
        $fieldId = 0;
        $tmp = explode('[op id=', $key);
        $fieldId = str_replace(']', '', $tmp[1]);
        if($fieldId <= 0 && $fieldId == '') continue;
        if ( isset($fields[$fieldId]['variant'][$variantId]['value']) ) {
          $fields[$fieldId]['variant'][$variantId]['value'] = $value;
        }
        // Подгрузка доп полей для товаров без вариантов
        if ( isset($fields[$fieldId]['productId']) ) {
          $fields[$fieldId]['value'] = $value;
        }
      }
      $opFieldsM->fill($fields);
      $opFieldsM->save();
    }
    
    
    // добавляем склады
    if($storages != NULL) {
      if(empty($_SESSION['import']['storageArray'])) {
        $storageArray = array();
      } else {
        $storageArray = $_SESSION['import']['storageArray'];
      }

      $storageArray = unserialize(stripcslashes(MG::getSetting('storages')));
      // viewData($storages);
      foreach($storages as $key => $value) {
        // дробим ключ, чтобы все узнать
        $key = explode('[', str_replace(']', '', $key));
        $storageId = explode('=', $key[1]);
        $storageItem['id'] = $storageId[1];
        $storageItem['name'] = $key[0];
        if($storageItem['id'] == '') {
          $storageItem['id'] = substr(md5($storageItem['name']), 0, 10);
        }
        $findStorage = false;
        foreach ($storageArray as $item) {
          if($item['id'] == $storageItem['id']) {
            $findStorage = true;
          }
        }
        if(!$findStorage) {
          $storageArray[] = $storageItem;
        }
        // заполняем склад
        $productModel = new Models_Product();
        $productModel->updateStorageCount($productId, $storageItem['id'], $value, $variantId);
        $productModel->recalculateStoragesById($productId);
      }
      if(empty($_SESSION['import']['storageArray'])) {
        MG::setOption('storages', addslashes(serialize($storageArray)));
        $_SESSION['import']['storageArray'] = $storageArray;
      }
    }
    
    return true;    
  }

  private function getUrlImagePaths($imageUrl) {
    $upload = new Upload(false);
		$temp = $upload->uploadImageFromUrl($imageUrl, true);

    if (!$temp['status']) {
      return null;
    }

    $image = explode('/', $temp['data']);
    unset($image[0]);
    $image = implode('/', $image);

    $pos = strpos($image, '_-_time_-_');
    $imageClear = $image;

    if ($pos) {
      if (MG::getSetting('addDateToImg') == 'true') {
        $tmp1 = explode('_-_time_-_', $image);
        $tmp2 = strrpos($tmp1[1], '.');
        $tmp1[0] = date("_Y-m-d_H-i-s", substr_replace($tmp1[0], '.', 10, 0));
        $imageClear = substr($tmp1[1], 0, $tmp2) . $tmp1[0] . substr($tmp1[1], $tmp2);
      } else {
        $imageClear = substr($image, ($pos + 10));
      }
    } 
    
    $name = $imageClear;

    return ['pathToDB' => $name, 'pathToMove' => $image];
  }
}
