<?php
/**
 * Класс Seo - предназначен для работы с функционалом системы, относящимся к 
 * seo-оптимизации контента.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Seo {

  /**
   * Возвращает набор шаблонов, для формарования сео тегов, для переданного типа страницы.
   * <code>
   *   $res = Seo::getTemplateForMeta('catalog');
   *   viewData($res);
   * </code>
   * @param string $type тип страницы
   * @return array
   */
  public static function getTemplateForMeta($type) {
    $templates = array();
    switch($type) {
      case 'catalog':
        $templates = array(
          'meta_title' => MG::getSetting('catalog_meta_title'),
          'meta_desc' => MG::getSetting('catalog_meta_description'),
          'meta_keywords' => MG::getSetting('catalog_meta_keywords'),
        );
        break;
      case 'product':
        $templates = array(
          'meta_title' => MG::getSetting('product_meta_title'),
          'meta_desc' => MG::getSetting('product_meta_description'),
          'meta_keywords' => MG::getSetting('product_meta_keywords'),
        );
        break;
      case 'page':
        $templates = array(
          'meta_title' => MG::getSetting('page_meta_title'),
          'meta_desc' => MG::getSetting('page_meta_description'),
          'meta_keywords' => MG::getSetting('page_meta_keywords'),
        );
        break;
    }    
    
    return $templates;
  }
  
  /**
   * Возвращает массив со значениями метатегов, сформированных по шаблонам, 
   * заданным в настройках системы.
   * <code>
   *   $res = Seo::getMetaByTemplate('catalog', $data);
   *   viewData($res);
   * </code>
   * @param string $type - тип страницы(каталог/товар/страница)
   * @param array $data - массив данных, используемых в шаблоне
   * @return array
   */
  public static function getMetaByTemplate($type, $data) {
    mb_internal_encoding('UTF-8');
    if (isset($data['category_name'])) {
      $data['category_name'] = str_replace(array('  --  ', ' --  ', '--  '), '', $data['category_name']);
    }

    $return = array();
    $templates = self::getTemplateForMeta($type);

    // viewdata($data);
    // viewdata($templates);
    foreach($templates as $field => $template) {
      $matches = array();
      preg_match_all("/{[\pL\s\d():_'%\",]+}/u", $template, $matches);
      // viewdata($matches[0]);
      
      foreach($matches[0] as $cell=>$match) {
        $keys = mb_substr($match, 1, -1);       
        if (mb_strpos($match, ":")) {
          $keys = explode(":", $keys);
          //Автоматическая генерация SEO для характеристик товара
          if(!isset($data[$keys[0]][$keys[1]]) && $keys[0] == 'stringsProperties'){
            $result = DB::query('SELECT `name` FROM `'.PREFIX.'product_user_property_data` WHERE `prop_id` = '.DB::quote($keys[1]).' AND `product_id` = '.DB::quote($data['id']));
            $row = DB::fetchAssoc($result);
            if(!empty($row['name'])){
              $template = str_replace($match, $row['name'], $template);
            }else{
              $res = DB::query('SELECT `description` FROM `'.PREFIX.'property` WHERE `id` = '.DB::quote($keys[1]));
              $prop = DB::fetchAssoc($res);
              $template = str_replace($match, $prop['description'], $template);
            }
          }else{
            $template = str_replace($match, $data[$keys[0]][$keys[1]], $template);
          }
        } else if (mb_strpos($match, ",")) {
          $keys = explode(",", $keys);
          $desc = MG::nl2br($data[$keys[0]]);
          $desc = strip_tags($desc);
          $length = ($keys[1] > 160) ? 160 : $keys[1];	
          $tmplValue = mb_substr($desc, 0, $length);

          $template = str_replace($match, $tmplValue, $template);
		  
        } else if (mb_strpos($match, '%')) {
          $matchVar = mb_substr($match, 2, -1);
          $tmplValueFull = MG::nl2br($data[$matchVar]);
          $tmplValue = explode(" ", $tmplValueFull);
          $tmplValue = implode(", ", $tmplValue);
          $tmplValue = $tmplValueFull . ', ' . $tmplValue; 		
          $template = str_replace($match, $tmplValue, $template);
        } else {
          $matchVar = mb_substr($match, 1, -1);
		  $tmplValue = '';
		  if(isset($data[$matchVar])){
            $tmplValue = MG::nl2br($data[$matchVar]);
		  }
          $tmplValue = strip_tags($tmplValue);    
          $arTmpl = explode($match, $template);

          if ($arTmpl[0] == "" || mb_strpos($tmplValue, $arTmpl[0]) === 0) {
			  /*  echo "arTmpl[1]=".$arTmpl[1]."<br>";
			      echo "tmplValue=".$tmplValue."<br>";
			      echo "mb_strrpos(tmplValue, arTmpl[1])=".mb_strrpos($tmplValue, $arTmpl[1])."<br>";
				  echo "mb_strlen(tmplValue)=".mb_strlen($tmplValue)."<br>";
				  echo "++=".(mb_strrpos($tmplValue, $arTmpl[1])+mb_strlen($arTmpl[1]))."<br>";
				  var_dump(mb_strrpos($tmplValue, $arTmpl[1]));
			  */  
				  $flag = false;
				  //если длина строки шаблона не ровна длине заголовка категории, то поиск на вхождение соответствия
				  if(mb_strlen($arTmpl[1]) != mb_strlen($tmplValue)){					 
					  if(mb_strrpos($tmplValue, $arTmpl[1]) + mb_strlen($arTmpl[1]) == mb_strlen($tmplValue)){
						 $flag = true;
					  }
				  }
				  
            if (($arTmpl[1] == "" && $tmplValue == "") || $flag) {
              $template = '';			
              break;
            }
          }
          
          $template = str_replace($match, $tmplValue, $template);
		   
        }
      }
      
      $return[$field] = html_entity_decode(trim(str_replace('&nbsp;', ' ', $template)));
    }    
    return $return;
  }

  /**
   * Создает в корневой папке сайта карту в формате XML.
   * <code>
   *   $res = Seo::autoGenerateSitemap();
   *   viewData($res);
   * </code>
   * @return int возвращает количество записанных в файл страниц
   */
  public static function autoGenerateSitemap() {
    $urls = $langs = array();
    $urlsLimit = 50000;
    $tmpLangs = unserialize(stripslashes(MG::getSetting('multiLang')));
    foreach ($tmpLangs as $key => $value) {
      if($value['active'] == 'true') {
        $langs[] = $value['short'];
      }
    }
    if(MG::getSetting('productSitemapLocale') == 'false'){
      $langs = [];
    }
    // категории каталога     
    $result = DB::query('
      SELECT  url,  parent_url 
      FROM `'.PREFIX.'category` WHERE `invisible`=0'.(MG::getSetting('product404Sitemap') == 'true'?' AND `activity`=1':''));
    while ($row = DB::fetchAssoc($result)) {
      $urls[] = $row['parent_url'].$row['url'];
      foreach ($langs as $key => $value) {
        $urls[] = $value.'/'.$row['parent_url'].$row['url'];
      }
    }
    // страницы товаров, с учетом флага коротких ссылок,
    $productShortLinks = false;
    if ((defined('SHORT_LINK') && SHORT_LINK == 1) || MG::getSetting('shortLink') == 'true') {
      $result = DB::query('   
      SELECT url
      FROM `'.PREFIX.'product`'.(MG::getSetting('product404Sitemap') == 'true'?' WHERE `activity`=1':''));
      $productShortLinks = true;
    } else {
      $onlyActivity = MG::getSetting('product404Sitemap') == 'true';
      $onlyActivityWhereClause = ' WHERE p.`activity`=1';
      $sql = 'SELECT p.url as product_url, c.parent_url as category_parent_url, c.url as category_url '.
        'FROM `'.PREFIX.'product` as p '.
        'LEFT JOIN `'.PREFIX.'category` as c '.
        'ON p.cat_id = c.id'.($onlyActivity ? $onlyActivityWhereClause : '').';';
      $result = DB::query($sql);
    }
    while ($row = DB::fetchAssoc($result)) {
      if ($productShortLinks) {
        $fullProductUrl = $row['url'];
      } else {
        $categoryUrl = $row['category_parent_url'].$row['category_url'];
        $productUrl = $row['product_url'];
        $fullProductUrl = $categoryUrl ? $categoryUrl.'/'.$productUrl : 'catalog/'.$productUrl;
      }
      $urls[] = $fullProductUrl;
      foreach ($langs as $value) {
        $urls[] = $value.'/'.$fullProductUrl;
      }
    }
    // статические страницы сайта
    $result = DB::query('
      SELECT  parent_url, url
      FROM `'.PREFIX.'page` WHERE invisible = 0');
    while ($row = DB::fetchAssoc($result)) {
      if ($row['url'] != 'index') {
        $pattern = "/^(http|https):\/\/([a-z0-9\.-]+)\.([a-z\.]{2,6})(.*)$/";
        $matches = array();
        preg_match($pattern, $row['url'], $matches);
        if (!empty($matches)) {
          if (trim($row['parent_url'], '/') == trim($matches[count($matches) - 1], '/')) {
            continue;
          }
          $urls[] = trim($matches[count($matches) - 1], '/');
          foreach ($langs as $key => $value) {
            $urls[] = $value.'/'.trim($matches[count($matches) - 1], '/');
          }
          continue;
        }
        $urls[] = $row['parent_url'].$row['url'];
        foreach ($langs as $key => $value) {
          $urls[] = $value.'/'.$row['parent_url'].$row['url'];
        }
      }
    }
    $res = DB::query("SELECT *  FROM ".PREFIX."plugins WHERE folderName = 'news' and active = '1'");
    if (DB::numRows($res)) {
      DB::query("SHOW TABLES LIKE '".PREFIX."mpl_news'");
      if(DB::affectedRows() != 0){
        // страницы новостей  // 
        $result = DB::query('
         SELECT  url
         FROM `'.PREFIX.'mpl_news`');
        while ($row = DB::fetchAssoc($result)) {
          $urls[] = 'news/'.$row['url'];
        }
      }
    }
    $res = DB::query("SELECT *  FROM ".PREFIX."plugins WHERE folderName = 'blog' and active = '1'");
    if (DB::numRows($res)) {
      $result = DB::query("
       SELECT CONCAT(IFNULL(bc.url,''),'/',bi.url) as url
       FROM  ".PREFIX."blog_items as bi
	   LEFT JOIN  `".PREFIX."blog_item2category` as b2c ON b2c.`item_id` = bi.`id`
	   LEFT JOIN  `".PREFIX."blog_categories` as bc ON bc.`id` = b2c.`category_id`

	  ");
      while ($row = DB::fetchAssoc($result)) {
        $urls[] = str_replace('//', '/', 'blog/'.$row['url']);
      }
    }
    
    if (MG::getSetting('useSeoRewrites') == 'true') {
      $dbRes = DB::query("SELECT `short_url` FROM `".PREFIX."url_rewrite` WHERE `activity` = 1");
      if (DB::numRows($dbRes)) {
        while ($row = DB::fetchAssoc($dbRes)) {
          $urls[] = $row['short_url'];
        }
      }
    }
    
    // страницы из папки mg-pages  
    $files = scandir(PAGE_DIR);
    foreach ($files as $item) {
    $pathInfo = pathinfo($item);
    if ($pathInfo['extension'] == 'php' || $pathInfo['extension'] == 'html') {
        if ($pathInfo['filename'] != 'captcha' && $pathInfo['filename'] != 'mg-formvalid') {
            $urls[] = $pathInfo['filename'];
        }
     }
    }

    // страницы с применеными фильтрами
    $res = DB::query('SELECT short_url FROM '.PREFIX.'url_rewrite');
    while($row = DB::fetchAssoc($res)) {
      $urls[] = $row['short_url'];
      foreach ($langs as $key => $value) {
        $urls[] = $value.'/'.$row['short_url'];
      }
    }
    $urls = array_unique($urls);    
    $exl = explode(';', MG::getSetting('excludeUrl'));
    foreach ($exl as &$url) {
      $url = str_replace(SITE.'/', '', trim($url));
    }
    $urls = array_diff($urls, $exl);


    $byteSizeUrls = 0;
    $limitMB = 50;

    foreach ($urls as $key => $value) {
      $byteSizeUrls += strlen($value);
    }

    self::deleteSitemapBeforeCreate();

    $urlsSizeMB = $byteSizeUrls / 1024 / 1024; 

    if(count($urls) > $urlsLimit || $urlsSizeMB > $limitMB){ 

      //Массив ссылок $urls = array( http://test/1, http://test/2 ) ,
      //Оборачиванем $urls в массив [$urls] при передачи в функцию - $urls = array( array( http://test/1, http://test/2 ) ), 
      //При итерации foreach внутри функции в переменной $value работает с массивами, при передачи $urls без обертки, $values будет работать со строками 

      $arrNamesSitemaps = [];
      $namesSitemaps = self::splitSiteMap([$urls], $arrNamesSitemaps, $urlsLimit);

      $xmlSitemap = self::getXmlMainSiteMap($namesSitemaps);
      $string = $xmlSitemap;
      $f = fopen(SITE_DIR.'sitemap.xml', 'w');
      $result = fwrite($f, $string);
      fclose($f);  

      if ($result) {
        return count($urls);
      } else {
        return false;
      }

    } else { 


      $xmlSitemap = self::getXmlView(array_diff($urls, $exl));
      $string = $xmlSitemap;
      $f = fopen(SITE_DIR.'sitemap.xml', 'w');
      $result = fwrite($f, $string);
      fclose($f);
      if ($result) {
        return count($urls);
      } else {
        return false;
      }
      
    }

  }

    /**
   * Функция создания файлов sitemap'a, создает файлы по условию ограничения(масимальное количество ссылок в одном файле, максимальный размер одного файла).
   * Возвращает массив с именами созданных файлов.
   * <code>
   *   $urls = array(
   *      array(
   *        'http://test/1',
   *        'http://test/2',
   *      )
   *     array(
   *        'http://test/3',
   *        'http://test/4',
   *        )
   *    );
   *   $res = Seo::splitSiteMap();
   *   viewData($res);
   * </code>
   * @param array $urls массив ссылок на страницы
   * @param $namesSitemaps передается по ссылке, массив для наименований созданных файлов
   * @param $urlsLimit максимально возможное количество ссылок в одном файле, 
   * @param $maxReqCount счетчик, считает количество вложенных вызовов(рекурсии) функции
   * @param $megaByte максимально возможный вес одного файла в мегабайтах
   * @return string $namesSitemaps массив с наименованиями созданных файлов
   */

  public static function splitSiteMap($urls, &$namesSitemaps, $urlsLimit, $maxReqCount = 0, $megaByte = 50){
    $limitBytes = $megaByte * 1024 * 1024; 
    if($maxReqCount === 10){
      throw new Exception('Большое количество рекурсий.');
      return ;
    }
    foreach($urls as $key => $someUrls){
      $xmlSitemap = self::getXmlView($someUrls);
      
      $key = count($namesSitemaps) + 1;

      $namesSitemaps[$key] = 'sitemap'.$key.'.xml';
      $f = fopen($namesSitemaps[$key], 'w');
      $result = fwrite($f, $xmlSitemap);
      fclose($f);
      if($result > $limitBytes || count($someUrls) > $urlsLimit){
        unlink($namesSitemaps[$key]);
        array_pop($namesSitemaps);
        self::splitSiteMap(array_chunk($someUrls, round(count($someUrls) / 2)), $namesSitemaps, $urlsLimit, $maxReqCount + 1);
      } 
            
    }
  
    return $namesSitemaps;

  }


  /**
   * Функция создания sitemap.xml.
   * <code>
   *   $urls = array(
   *     'http://test/1',
   *     'http://test/2',
   *   );
   *   $res = Seo::getXmlView();
   *   viewData($res);
   * </code>
   * @param array $urls массив ссылок на страницы
   * @return string xml данные для карты сайта
   */
  public static function getXmlView($urls) {
    $nXML = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
';
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $date = date("Y-m-d");
    foreach ($urls as $url) {
      $xml->startElement("url");
      $xml->writeElement("loc", SITE.'/'.$url);
      $xml->writeElement("lastmod", $date);
      $partsUrl = URL::getSections($url);
      $priority = count($partsUrl);
      if ($priority >= 3) {
        $priority = '0.5';
        // исключение для главной страницы
        if ($partsUrl[2] == 'ajax') {
          $priority = '1.0';
        }
      }
      if ($priority == 2) {
        $priority = '0.8';
      }
      if ($priority == 1) {
        $priority = '1.0';
      }
      $xml->writeElement("priority", $priority);
      $xml->endElement();
    }
    $nXML .= $xml->outputMemory();
    $nXML .= '</urlset>';
    return mb_convert_encoding($nXML, "WINDOWS-1251", "UTF-8");
  }


  /**
   * Функция создания файла sitemap.xml, содержащего ссылки на другие sitemap'ы.
   * <code>
   *   $urls = array(
   *     'sitemap01.xml',
   *     'sitemap02.xml',
   *   );
   *   $res = Seo::getXmlMainSiteMap();
   *   viewData($res);
   * </code>
   * @param array $urls массив ссылок на файлы
   * @return string xml данные для карты сайта
   */
  public static function getXmlMainSiteMap($urls) {
    $nXML = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"> 
';
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $date = date("Y-m-d");
    foreach ($urls as $url) {
      $xml->startElement("sitemap");
      $xml->writeElement("loc", SITE.'/'.$url);
      $xml->writeElement("lastmod", $date);
      $xml->endElement();
    }
    $nXML .= $xml->outputMemory();
    $nXML .= '</sitemapindex>';
    return mb_convert_encoding($nXML, "WINDOWS-1251", "UTF-8");
  }



   /**
   * Удаляет файлы sitemap в корне сайта
   * <code>
   *   $res = Seo::deleteSitemapBeforeCreate();
   * </code>
   */
  public static function deleteSitemapBeforeCreate(){
    
    $rootFileData = scandir(SITE_DIR);
    $filePattern = '/sitemap[0-9]*\.xml/';

    foreach($rootFileData as $value){
      if(preg_match($filePattern, $value)){
        unlink(SITE_DIR.$value);
      }
    }

  }


  /**
   * Применения SEO настроек сразу ко всем сущностям одного типа.
   * Установка метатегов по шаблону.
   * <code>
   *   $res = Seo::getMetaByTemplateForAll('catalog');
   *   viewData($res);
   * </code>
   * @param string $type тип страницы
   * @return bool
   */
  public static function getMetaByTemplateForAll($type) {
    // создание процедуры для обработки html тегов (удаление)
    $res = mysqli_query(DB::$connection, "DROP FUNCTION IF EXISTS strip_tags");
    
    if(!$res) {
      return false;
    }
    
    //IF(ISNULL($start)) THEN RETURN ""; END IF; добавил 10 августа 22г. Марк. Фикс для NULL.

    DB::query('
    CREATE FUNCTION `strip_tags`($str text CHARSET utf8) RETURNS text CHARSET utf8
    LANGUAGE SQL NOT DETERMINISTIC READS SQL DATA
    BEGIN
        DECLARE $start, $end INT DEFAULT 1;
        LOOP
            IF(ISNULL($start)) THEN RETURN ""; END IF;
            SET $start = LOCATE("<", $str, $start);
            IF (!$start) THEN RETURN $str; END IF;
            SET $end = LOCATE(">", $str, $start);
            IF (!$end) THEN SET $end = $start; END IF;
            SET $str = INSERT($str, $start, $end - $start + 1, "");
        END LOOP;
    END;');
    $data = array();
    // составления соответсвия 
    switch ($type) {
      // товары
      case 'product':
        $templates = self::getTemplateForMeta('product');

        foreach($templates as $key => $template) {
          $templates[$key] = addslashes($template);
        }

        $dbRes = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` WHERE type=\"string\"");

        while ($row = DB::fetchAssoc($dbRes)) {
          $data[$row['name']] = $row['id'];
        }

        // определение данных в атрибутах
        foreach($templates as $field=>$template) {
          $matches = array();
          preg_match_all("/{[\pL\s\d():_'%\",]+}/u", $template, $matches);

          foreach($matches[0] as $cell=>$match) {
            $keys = mb_substr($match, 1, -1);
            
            if (mb_strpos($match, ":")) {
              $keys = explode(":", $keys);
              $arrayMatchProp['{stringsProperties:'.$keys[1].'}'] = '",(SELECT DISTINCT `name` FROM '.PREFIX.'product_user_property_data WHERE product_id = tProd.id AND prop_id = '.DB::quote($keys[1]).'),"';
            }
            if (mb_strpos($match, ",")) {
              $keys = explode(",", $keys);
              if (isset($data[$keys[0]])) {
                $desc = MG::nl2br($data[$keys[0]]);
                $desc = strip_tags($desc);
              } else {
                $desc = '';
              }
              
              $length = $keys[1];
              $arrayMatchDesc['{description,'.$length.'}'] = '",(SUBSTRING(strip_tags(description),1,'.DB::quoteInt($length, true).')),"';
            } 
            if (mb_strpos($match, '%')) {
              $matchVar = mb_substr($match, 2, -1);
              $title = MG::nl2br($data[$matchVar]);
              $keywords = explode(" ", $title);
              $keywords = implode(", ", $keywords);
              $keywords = $title . ', ' . $keywords; 
    
              $arrayMatchKeywords['{%'.$matchVar.'}'] = "\",(SELECT CONCAT(CONCAT(title, ', '), REPLACE(title, ' ', ', '))),\"";
            } 
          }      
        }

        $arrayMatch = array(
          '{title}' => '",title,"',
          '{category_name}' => '",(SELECT title FROM `'.PREFIX.'category` AS tCat WHERE tCat.id = tProd.cat_id),"',
          '{code}' => '",code,"',
          '{meta_title}' => '",meta_title,"',
          '{meta_keywords}' => '",meta_keywords,"',
          '{meta_desc}' => '",meta_desc,"',
          '{price}' => '",price,"',
          '{price_course}' => '",price_course,"',
        );
        $ca = '",CASE ';
        foreach (MG::getSetting('currencyShort') as $key => $value) {
          $ca .= 'WHEN currency_iso = '.DB::quote($key).' THEN '.DB::quote($value).' ';
        }
        $ca .= 'END,"';
        $arrayMatch['{currency}'] = $ca;

        $templates['meta_title'] = addslashes($templates['meta_title']);
        $templates['meta_keywords'] = addslashes($templates['meta_keywords']);
        $templates['meta_desc'] = addslashes($templates['meta_desc']);

        $title = strtr($templates['meta_title'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $title = strtr($title, $arrayMatchDesc);
        if(!empty($arrayMatchProp)) $title = strtr($title, $arrayMatchProp);
        if(!empty($arrayMatchKeywords)) $title = strtr($title, $arrayMatchKeywords);

        $keywords = strtr($templates['meta_keywords'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $keywords = strtr($keywords, $arrayMatchDesc);
        if(!empty($arrayMatchProp)) $keywords = strtr($keywords, $arrayMatchProp);
        if(!empty($arrayMatchKeywords)) $keywords = strtr($keywords, $arrayMatchKeywords);

        $desc = strtr($templates['meta_desc'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $desc = strtr($desc, $arrayMatchDesc);
        if(!empty($arrayMatchProp)) $desc = strtr($desc, $arrayMatchProp);
        if(!empty($arrayMatchKeywords)) $desc = strtr($desc, $arrayMatchKeywords);
        DB::query('UPDATE '.PREFIX.'product AS tProd SET meta_title = concat("'.$title.'"), meta_keywords = concat("'.$keywords.'"), meta_desc = concat("'.$desc.'")');
        // 
        break;
      // категории
      case 'catalog':
        $templates = self::getTemplateForMeta('catalog');

        foreach($templates as $key => $template) {
          $templates[$key] = addslashes($template);
        }

        $dbRes = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` WHERE type=\"string\"");

        while ($row = DB::fetchAssoc($dbRes)) {
          $data[$row['name']] = $row['id'];
        }

        // определение данных в атрибутах
        foreach($templates as $field=>$template) {
          $matches = array();
          preg_match_all("/{[\pL\s\d():_'\",]+}/u", $template, $matches);

          foreach($matches[0] as $cell=>$match) {
            $keys = mb_substr($match, 1, -1);
            
            if (mb_strpos($match, ",")) {
              $keys = explode(",", $keys);
              if (isset($data[$keys[0]])) {
                $desc = MG::nl2br($data[$keys[0]]);
                $desc = strip_tags($desc);
              } else {
                $desc = '';
              }
              
              $length = $keys[1];
              $arrayMatchDesc['{cat_desc,'.$length.'}'] = '",SUBSTRING(strip_tags(html_content),1,'.$length.'),"';
            }
          }      
        }

        $arrayMatch = array(
          '{titeCategory}' => '",title,"',
          '{meta_title}' => '",meta_title,"',
          '{meta_keywords}' => '",meta_keywords,"',
          '{meta_desc}' => '",meta_desc,"'
          );

        $title = strtr($templates['meta_title'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $title = strtr($title, $arrayMatchDesc);

        $keywords = strtr($templates['meta_keywords'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $keywords = strtr($keywords, $arrayMatchDesc);

        $desc = strtr($templates['meta_desc'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $desc = strtr($desc, $arrayMatchDesc);

        DB::query('UPDATE '.PREFIX.'category SET meta_title = concat("'.$title.'"), meta_keywords = concat("'.$keywords.'"), meta_desc = concat("'.$desc.'")');
        // 
        break;
      // страницы
      case 'page':
        $templates = self::getTemplateForMeta('page');

        foreach($templates as $key => $template) {
          $templates[$key] = addslashes($template);
        }

        $dbRes = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` WHERE type=\"string\"");

        while ($row = DB::fetchAssoc($dbRes)) {
          $data[$row['name']] = $row['id'];
        }

        // определение данных в атрибутах
        foreach($templates as $field=>$template) {
          $matches = array();
          preg_match_all("/{[\pL\s\d():_'\",]+}/u", $template, $matches);

          foreach($matches[0] as $cell=>$match) {
            $keys = mb_substr($match, 1, -1);
            
            if (mb_strpos($match, ",")) {
              $keys = explode(",", $keys);
              if (isset($data[$keys[0]])) {
                $desc = MG::nl2br($data[$keys[0]]);
                $desc = strip_tags($desc);
              } else {
                $desc = '';
              }
              $length = $keys[1];
              $arrayMatchDesc['{html_content,'.$length.'}'] = '",SUBSTRING(strip_tags(html_content),1,'.$length.'),"';
            }
          }      
        }

        $arrayMatch = array(
          '{title}' => '",title,"',
          '{meta_title}' => '",meta_title,"',
          '{meta_keywords}' => '",meta_keywords,"',
          '{meta_desc}' => '",meta_desc,"'
          );

        $title = strtr($templates['meta_title'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $title = strtr($title, $arrayMatchDesc);

        $keywords = strtr($templates['meta_keywords'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $keywords = strtr($keywords, $arrayMatchDesc);

        $desc = strtr($templates['meta_desc'], $arrayMatch);
        if(!empty($arrayMatchDesc)) $desc = strtr($desc, $arrayMatchDesc);

        DB::query('UPDATE '.PREFIX.'page SET meta_title = concat("'.$title.'"), meta_keywords = concat("'.$keywords.'"), meta_desc = concat("'.$desc.'")');
        // 
        break;
    }

    return true;
  }
}