<?php

/**
 * Файл mg-start.php расположен в корне ядра, запускает движок и выводит на 
 * экран сгенерированную им страницу сайта.
 *
 * Инициализирует компоненты CMS, доступные из любой точки программы.
 * - DB - класс для работы с БД;
 * - Storage - класс для работы с кэшем;
 * - MG - класс содержащий функционал системы;
 * - URL - класс для работы со ссылками;
 * - PM - класс для работы с плагинами.
 * - User - класс для работы с профайлами пользователей;
 * - Mailer - класс для отправки писем.
 * 
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Files
 */

// Инициализация компонентов CMS.

// Время и память в начале работы движка
$start = microtime(true);
$memory = memory_get_usage();

MG::getConfigIni();
DB::init();
MG::fillSettings();
User::init();
URL::init();
MG::init();

$actualVer = str_replace('v', '', VER);
$needUpdateDB = version_compare(MG::getSetting('lastModVersion'), $actualVer, '<');
if($needUpdateDB && file_exists(SITE_DIR.'mg-core'.DS.'modificator.php')) {
	@include SITE_DIR.'mg-core'.DS.'modificator.php';
}

function mgShutdown() {
    Storage::close();
    session_write_close();
    DB::close();
}
register_shutdown_function('mgShutdown');

// Запоминает откуда пришел пользователь.
MG::logReffererInfo();

// Подключить index.php всех плагинов.
PM::includePlugins();

// Подключает файл с функциями шаблона, если таковой существует.
if(file_exists(PATH_TEMPLATE.'/functions.php')) {
	require_once PATH_TEMPLATE.'/functions.php';
}
Urlrewrite::init();

// Хук выполняющийся до запуска движка.
MG::createHook('mg_start');

// Запуск движка.
$moguta = new Moguta;
$moguta = $moguta->run();

// Вывод результата на экран, предварительно обработав все возможные шорткоды.

if(MG::getSetting('useWebpImg') == 'true' && Webp::checkUserBrowser()){
	$html =  Webp::changeImgForWebp(URL::multiLangLink(PM::doShortcode(MG::printGui($moguta))));
}else{
$html =  URL::multiLangLink(PM::doShortcode(MG::printGui($moguta)));
}


// перед выводом всей верстки даём плагинам возможность на нее повлиять
echo MG::createHook('mg_print_html', $html, $moguta);

// Хук выполняющийся после того как отработал движок.
MG::createHook('mg_end', true, $moguta);

// Ввывод консоли запросов к БД.
if (DB::$_debugMode) {
  echo DB::console('', $memory, $start);
}

// Завершение процесса кэширвания.

Storage::close();
session_write_close();

// echo microtime(true) - $_SERVER['REQUEST_TIME'];