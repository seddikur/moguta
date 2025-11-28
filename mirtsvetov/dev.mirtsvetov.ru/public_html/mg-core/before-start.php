<?php
/**
 * Файл before-start.php позволяет дополнить/изменить логику работы контейнера user.site
 *
 * @author Skvortsov Evgenii
 * @package moguta.cloud
 * @subpackage Files
 */

function get_ini_file($filename, $process_sections = false){
    $config = parse_ini_file($filename, $process_sections);
    return $config;
}


function loger($text, $mode = 'append', $file = 'log', $time = true, $dump = false) {
    switch ($mode) {
      case 'dump':
        $mode = 'a+';
        $dump = true;
        break;
      case 'dumpNew':
        $mode = 'w';
        $dump = true;
        break;
      case 'new':
      case 'w':
        $mode = 'w';
        break;
      case 'add':
      case 'apend':
      case 'append':
      case 'a+':
      default:
        $mode = 'a+';
        break;
    }
    $date = date('Y_m_d');
    $tempPath = '';
    $fileName = $tempPath.$file.($time ? '_'.$date : '').'.txt';
    ob_start();
    if($dump) {
      var_dump($text);
    } else {
      print_r($text);
    }
    $content = ob_get_contents();
    ob_end_clean();
    $content = str_replace('=>'."\n ", ' =>', $content);
    $string = date('d.m.Y H:i:s').' => '.$content."\r\n";
    $f = fopen($fileName, $mode);
    fwrite($f, $string);
    fclose($f);
    chmod($fileName, 0777);
}