<?php
/**
 * Класс ConfigEditorPublic - предназначен для редактирования настроек шаблона, содержимого файлов config.ini
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class ConfigEditorPublic{
    private static $lang = array(); // массив с переводом плагина
    private static $templateName = ''; // название шаблона (соответствует названию папки)
    
    //private static $pluginName = ''; // название плагина (соответствует названию папки)
    //private static $path = ''; //путь до файлов плагина
    
    public $filename = '';//название редактируемого файла
    public $fileContent = '';//конечное содержание файла
    public $readIni = []; //массив содержимого файла полученный через parse_ini_file
    public $configArray = []; // массив для работы с ним, из которого потом будет собираться файл
    public $configPreArray = []; //подготавливаемый массив
    public $loger = 0;
    
    public function __construct(){
        //mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина
        //self::$pluginName = PM::getFolderPlugin(__FILE__);
        self::$templateName = MG::getSetting('templateName');
        // self::$path = PLUGIN_DIR.self::$pluginName;
        // if (URL::isSection('mg-admin')) {
        //     MG::addInformer(array('count' => 0, 'class' => 'count-wrap', 'classIcon' => 'fa-paint-brush', 'isPlugin' => true, 'section' => 'mg-config-editor', 'priority' => 80));
        // }
    }

    /**
     * Чтение файла конфигурации шаблона
     * @param $filename
     */
	public function init($filename) {
		$this->filename = $filename;
		if(!file_exists($filename) || !is_readable($filename)){
			echo $filename.' - ошибка чтения!';
			exit();
		}
		$this->readIni = parse_ini_file($filename,true);
		$this->configPreArray = file($filename); //построчное чтение файла в массив
		$this->prepareConfigArray();
	}

	/**
     * Возвращает массив данных из конфига шаблона
     */
	public function getConfig() {
		return $this->configArray;
	}

    /**
     * Демонстрационная перезапись массива данных из конфига
     */
     public function demoPlugin($filename) {

            $this->filename = $filename;
            if(!file_exists($filename)){
                echo $filename.' не существует';
                exit();
            }
            $this->readIni = parse_ini_file($filename,true);
            //$this->viewArr($this->readIni);
            $this->configPreArray = file($filename); //построчное чтение файла в массив
            $this->prepareConfigArray();
            //$this->viewArr($this->configArray);
            $this->setOption('SETTINGS','widthPreview','6000');
            $this->setOption('COLORS','1[main-color]','#30000');
            //$this->viewArr($this->configArray);
            $this->saveConfigIni();
    }

    /**
     * Подготавливает массив для дальнейшей работы с ним
     * проходим по каждой строке в массиве и парсим ее на части
     * 'section' => $curSection,
     *  'line' => $line,
     * 'type' => $type,
     *  'key' => $key,
     *  'value' => $value,
     *  'newline' => $key.' = \''.htmlspecialchars_decode($value.time()).'\'',
     */
    public function prepareConfigArray(){

        $curSection = '';

        foreach ($this->configPreArray as $lineNum => $line) {
            $line = trim($line);
            $firstChar = mb_substr($line, 0, 1);
            switch ($firstChar):
                case '':
                    $type = '';
                    break;
                case ';':
                    $type = 'comment';
                    break;
                case '[':
                    $type = 'section';
                    $curSection = trim(substr($line, 1, strpos($line, "]")-1));
                    break;
                default:
                    $type = 'option';
            endswitch;

            if($type=='option'){

                // ключом будет все что до знака =
                $key = trim(substr($line, 0, strpos($line, "=")));
                //берем значение из массива полученного parse_ini_file, т.к. он точно корректно все распарсил
                $value = isset($this->readIni[$curSection][$key])?$this->readIni[$curSection][$key]:NULL;

                if(empty($value)) {

                    // регулярка для подобных названий директив 1[main-color]
                    // получить число и остальное
                    preg_match_all('/(\d+|\w+)\[(.*)\]/s', $key, $matches);

                    if (isset($matches[1][0]) && isset($matches[2][0])) {
                        $value = $this->readIni[$curSection][$matches[1][0]][$matches[2][0]];
                    }
                }

                $this->configArray[$curSection.'_'.$key]=[
                    'section' => $curSection,
                    'line' => $line,
                    'type' => $type,
                    'key' => $key,
                    'value' => ''.htmlspecialchars_decode(htmlspecialchars_decode($value, ENT_QUOTES),ENT_COMPAT).'',
                    'newline' => $key.' = \''.htmlspecialchars_decode(htmlspecialchars_decode($value, ENT_QUOTES),ENT_COMPAT).'\'',
                ];

            }else{
                $this->configArray[$curSection.'_line'.$lineNum]=[
                    'section' => $curSection,
                    'line' => $line,
                    'type' => $type,
                    'newline' => $line,
                ];
            }
        }
        
        $languageLocale = MG::getOption('languageLocale');
        $languageLocaleArr = explode('_',$languageLocale);
        
        $templateLocaledefault = SITE_DIR.DS."mg-templates".DS.self::$templateName.DS.'locales'.DS.'default.php';
        $languageLocaleRu = SITE_DIR.DS."mg-templates".DS.self::$templateName.DS.'locales'.DS.reset($languageLocaleArr).'.php';
        $languageLocaleRuRU = SITE_DIR.DS."mg-templates".DS.self::$templateName.DS.'locales'.DS.$languageLocale.'.php';

        if(file_exists($languageLocaleRu)){
            $templateLocaledefault = $languageLocaleRu;
        } elseif(file_exists($languageLocaleRuRU)){
            $templateLocaledefault = $languageLocaleRuRU;
        }

        if(!file_exists($templateLocaledefault)){
            //echo 'Err: '.$templateLocale.' не возможно открыть Файл с локалью!';
            $this->messageError = $this->lang['ERROR_OPEN_FILE_LOCALES'];
            return false;
        }

        include($templateLocaledefault);

        // возвращаем только локали касающиеся файла конфигурации
        foreach($locale as $key => $value){
            if (strpos($key,'CONFIG') === false) {
                unset($locale[$key]);
            }
        }
        $this->locales = $locale;
    }

    /**
     * Перезапись файла новым содержимым
     */
    public function saveConfigIni(){
        $this->fileContent = '';

        foreach($this->configArray as $lineNum => $val){
            $this->fileContent .= $val['newline']."\n";
        }

        file_put_contents($this->filename, $this->fileContent);
    }

    /**
     * Установка нового значения директивы
     */
    public function setOption($section,$key,$value){
        $this->configArray[$section.'_'.$key]['newline'] = $key.' = \''.htmlspecialchars(htmlspecialchars(self::shortCodeToHtmlEntity($value), ENT_QUOTES),ENT_COMPAT).'\'';
        $this->configArray[$section.'_'.$key]['value'] = htmlspecialchars(htmlspecialchars(self::shortCodeToHtmlEntity($value), ENT_QUOTES),ENT_COMPAT);
    }

    public static function shortCodeToHtmlEntity($string) {
        return str_replace('[', '&#91;', str_replace(']', '&#93;', $string));
    }
}