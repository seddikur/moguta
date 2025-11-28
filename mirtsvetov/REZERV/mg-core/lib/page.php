<?php

/**
 * Класс Page - совершает все возможные операции со страницами сайта.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Page {

  // Массив страниц.
  private $page;
  private $listCategoryId;
  public $systemPages = ['catalog','feedback','index','group?type=latest','group?type=sale','group?type=recommend'];
  private $langLocale;

  public function __construct() {
    $this->langLocale = MG::get('lang');
    // получаем список страниц   
    $result = DB::query('SELECT id, title, url, parent, parent_url, sort, meta_title, meta_keywords, meta_desc,invisible FROM `'.PREFIX.'page` ORDER BY sort');
    $listId = "";
    while ($page = DB::fetchAssoc($result)) {   
      if(strpos($page['url'],'http') === 0 || strpos($page['url'],'https') === 0) {
        $link = $page['url'];
      } else {
        $link = SITE.'/'.$page['parent_url'].$page['url'];        
      }
      if($link == SITE.'/index') {
        $link = SITE;
      }
      $page['link'] = $link;
      MG::loadLocaleData($page['id'], LANG, 'page', $page);
      $this->page[$page['id']] = $page;
    }

  }

  /**
   * Возвращает url страницы по ее id.
   * <code>
   *   $res = MG::get('pages')->getParentUrl(2);
   *   viewData($res);
   * </code>
   * @param int $parentId - id страницы для которой нужно найти URL.
   * @return string
   */
  public function getParentUrl($parentId) {
    $cat = $this->getPageById($parentId, true);
    $res = !empty($cat) ? $cat['parent_url'].$cat['url'] : '';
    return $res ? $res.'/' : '';
  }

  /**
   * Создает новую страницу.
   * <code>
   *   $array = array(,
   *     'id' => ,
   *     'title' => '123',
   *     'url' => '123',
   *     'parent' => 0,
   *     'html_content' => ,
   *     'meta_title' => ,
   *     'meta_keywords' => ,
   *     'meta_desc' => ,
   *     'invisible' => 0,
   *     'parent_url' => ,
   *   );
   *   $res = MG::get('pages')->addPage($array);
   *   viewData($res);
   * </code>
   * @param array $array массив с данными о страницах.
   * @return bool|int в случае успеха возвращает id добавленной страницы.
   */
  public function addPage($array) {
    unset($array['id']);
    $result = array();

    // Запрет создания системных страниц
    if(in_array($array['url'], $this->systemPages) && !$array['parent_url']){
      return false;
    }
    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');

    foreach ($array as $k => $v) {
       if(in_array($k, $maskField)) {
        $v = htmlspecialchars_decode($v);
        $array[$k] = htmlspecialchars($v);       
       }
    }
    // Исключает дублирование.
    $dublicatUrl = false;

    $tempArray = $this->getPageByUrl($array['url'], $array['parent_url']);
    if (!empty($tempArray)) {
      $dublicatUrl = true;
    }

    if (DB::buildQuery('INSERT INTO `'.PREFIX.'page` SET ', $array)) {
      $id = DB::insertId();
      // Если url дублируется, то дописываем к нему id продукта.
      $arr = array(
        'id' => $id,
        'sort' => $id,
        'url' => $array['url'],
        'html_content' => $array['html_content']
      );
      if ($dublicatUrl) {
        $arr['url'] = $array['url'].'_'.$id;
      }
      $this->updatePage($arr);
      $array['id'] = $id;
      $result = $array;
    }

    //логирование
    LoggerAction::logAction('Page',__FUNCTION__, $id);
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Изменяет данные о странице.
   * <code>
   *   $array = array(,
   *     'id' => ,
   *     'title' => '123',
   *     'url' => '123',
   *     'parent' => 0,
   *     'html_content' => ,
   *     'meta_title' => ,
   *     'meta_keywords' => ,
   *     'meta_desc' => ,
   *     'invisible' => 0,
   *     'parent_url' => ,
   *   );
   *   $res = MG::get('pages')->updatePage($array);
   *   viewData($res);
   * </code>
   * @param array $array массив с данными о станице.
   * @return bool
   */
  public function updatePage($array) {
    $id = $array['id'];
    $result = false;
    // Запрещаем заменение URL у системных страниц
    $currentPage = $this->getPageById($array['id'], true);
    $isSystem = in_array($currentPage['url'],$this->systemPages) && (!$currentPage['parent_url'] || !$array['parent_url']);
    if(!empty($array['url']) && $array['url'] != $currentPage['url'] && $isSystem){
      $this->messageError = $this->langLocale['ACT_PAGE_SYS_EDIT'];
      return false;
    }
    // Запрещаем перемещение системных страниц
    if(isset($array['parent_url']) && $array['parent_url'] !== $currentPage['parent_url'] && $isSystem){
      $this->messageError = $this->langLocale['ACT_PAGE_SYS_CHILD'];
      return false;
    }

    if (!empty($array['html_content']) && strpos($array['html_content'], 'src="'.SITE.'/uploads')) {
      @mkdir(SITE_DIR.'uploads'.DS.'page', 0755);
      @mkdir(SITE_DIR.'uploads'.DS.'page'.DS.$id, 0755);
    }
    if (!empty($array['html_content'])) {
      $array['html_content'] = MG::moveCKimages($array['html_content'], 'page', $id, '', 'page', 'html_content');
    }

    if (!empty($array['url']) && strpos($array['url'], 'http:') !== 0) {
      $array['url'] = URL::prepareUrl($array['url']);
    }

    // перехватываем данные для записи, если выбран другой язык
    if (isset($array['lang'])) {
      $lang = $array['lang'];
    } else {
      $lang = null;
    }
    
    unset($array['lang']);

    $filter = array('title','meta_title','meta_keywords','meta_desc','html_content');
    $localeData = MG::prepareLangData($array, $filter, $lang);
    
    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');

    foreach ($array as $k => $v) {
       if(in_array($k, $maskField)) {
        $array[$k] = htmlspecialchars($v);
       }
    }

    // Если назначаемая категория, является той же.
    if (isset($array['parent']) && $array['parent'] == $id) {
      $this->messageError = $this->langLocale['ACT_PAGE_ADD_ERROR'];
      return false;
    }

    $childsPage = $this->getPagesInside($id);
    // Если есть вложенные, и одна из них назначена родительской.
    if (!empty($childsPage)) {
      foreach ($childsPage as $cateroryId) {
        if (isset($array['parent']) && $array['parent'] == $cateroryId) {
          $this->messageError = $this->langLocale['ACT_PAGE_ADD_ERROR'];
          return false;
        }
      }
    }

    if (isset($_POST['parent']) && $_POST['parent'] == $id) {
      $this->messageError = $this->langLocale['ACT_PAGE_ADD_ERROR'];
      return false;
    }

    //логирование
    LoggerAction::logAction('Page',__FUNCTION__, $array);
    if (!empty($id)) {
      
      // обновляем выбранную страницу
      if (DB::query('
        UPDATE `'.PREFIX.'page`
        SET '.DB::buildPartQuery($array).'
        WHERE id =  '.DB::quote(intval($id), true)
        )) {
        $result = true;
      }

      // сохраняем локализацию
      MG::saveLocaleData($id, $lang, 'page', $localeData);
      
      // находим список всех вложенных в нее страниц
      if (isset($array['parent'])) {
        $arrayChildCat = $this->getPagesInside($array['parent']);
      }
     
      
      if (!empty($arrayChildCat)) {
        
        // обновляем parent_url у всех вложенных категорий, т.к. корень поменялся
        foreach ($arrayChildCat as $childCat) {
          $childCat = $this->getPageById($childCat, true);
          $upParentUrl = $this->getParentUrl($childCat['parent']);
          $posParent = stripos($upParentUrl, '?');
          if($posParent !== false) {
            $upParentUrl = substr($upParentUrl, 0, $posParent).'/';
          }

          if (DB::query('
            UPDATE `'.PREFIX.'page`
            SET parent_url='.DB::quote($upParentUrl).'
            WHERE id = '.DB::quoteInt($childCat['id'], true)
            ));
        }
      }
    } else {
      $result = $this->addPage($array);
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Удаляет Страницу.
   * <code>
   *   $res = MG::get('pages')->delPage(9);
   *   var_dump($res);
   * </code>
   * @param int $id id удаляемой страницы.
   * @return bool
   */
  public function delPage($id) {
    $pages = $this->getPagesInside($id);
    $pages[] = $id;

    //логирование
    LoggerAction::logAction('Page',__FUNCTION__, $id);

    $currentPage = $this->getPageById($id, true);
    if(in_array($currentPage['url'],$this->systemPages) && !$currentPage['parent_url']){
      $this->messageError = $this->langLocale['ACT_PAGE_SYS_DEL'];
      return false;
    }

    foreach ($pages as $pageID) {
      DB::query('
        DELETE FROM `'.PREFIX.'page`
        WHERE id = %d
      ', $pageID);
      $pageUploads = SITE_DIR.'uploads/page/'.$pageID;
      $pageWebpUploads = str_replace('/uploads/', '/uploads/webp/', $pageUploads);
      MG::rrmdir($pageUploads);
      MG::rrmdir($pageWebpUploads);
    }

    $args = func_get_args();
    $result = true;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает древовидный список страниц, пригодный для использования в меню.
   * <code>
   *   $res = MG::get('pages')->getPagesUl(9);
   *   var_dump($res);
   * </code>
   * @param int $parent id категории, для которой надо вернуть список.
   * @param string $type тип списка (для публичной части, либо для админки).
   * @return string
   */
  public function getPagesUl($parent = 0, $type = 'public') {
    // получаем данные об открытых страницах из куков  
    if (empty($this->openedPage)) {
      if ('admin' == $type && isset($_COOKIE['openedPageAdmin'])) {
        $this->openedPage = json_decode($_COOKIE['openedPageAdmin']);
      } elseif (isset($_COOKIE['openedPage'])) {
        $this->openedPage = json_decode($_COOKIE['openedPage']);
      }
      if (empty($this->openedPage)) {
        $this->openedPage = array();
      }
    }

    $print = '';
    if (empty($this->page)) {
      $print = '';
    } else {
      $lang = MG::get('lang');
      $gategoryArr = $this->page;
      //для публичной части убираем из меню закрытые страницы
      if ('public' == $type) {
        foreach ($gategoryArr as $key => $val) {
          if ($val['invisible'] == 1) {
            unset($gategoryArr[$key]);
          }
        }
      }

      foreach ($gategoryArr as $page) {

        if ($parent == $page['parent']) {

          $flag = false;

          $mover = '';

          if ('admin' == $type) {
            $class = 'active';
            $title = $lang['ACT_V_CAT'];
            if ($page['invisible'] == 1) {
              $class = '';
              $title = $lang['ACT_UNV_CAT'];
            }

            if (strpos($page['url'], 'http:') !== 0) {
              $url = SITE.'/'.$page['parent_url'].$page['url'];
            } else {
              $url = $page['url'];
            }
            $checkbox = '<input type="checkbox" name="page-check"> ';
            $mover .= $checkbox.'<div class="visible tool-tip-bottom '.$class.'" title="'.$title.'" data-category-id="'.$page['id'].'"></div><div class="mover"></div><div class="link-to-site tool-tip-bottom" title="'.$lang['MOVED_TO_PAGE'].'" data-href="'.$url.'"></div>';
          }

          $slider = '>'.$mover;

          foreach ($this->page as $sub_category) {
            if ($page['id'] == $sub_category['parent']) {
              $slider = ' class="slider">'.$mover.'<div class="slider_btn"></div>';
              $style = "";
			  $opened = " closed ";
              if (in_array($page['id'], $this->openedPage)) {
                $opened = " opened ";
                $style = ' style="background-position: 0 0"';
              }

              $slider = ' class="slider">'.$mover.'<div class="slider_btn '.$opened.'" '.$style.'></div>';
              $flag = true;
              break;
            }
          }

          if ('admin' == $type) {
            $print.= '<li'.$slider.'<a role="button" href="javascript:void(0);" onclick="return false;" rel="pageTree" class="pageTree" id="'.$page['id'].'" parent_id="'.$page["parent"].'">'.$page['title'].'</a>
              <span style="display:none"> [id='.$page['id'].'] </span>';
          } else {
            $hotFix1 = false;
            if ($page['parent_url'] == "" && ($page['url'] == 'index' || $page['url'] == 'index.html')) {
              $hotFix1 = true;
            }
            if ($page['invisible'] != 1) {
              $active = '';
              if (URL::isSection($page['parent_url'].$page['url'])) {
                $active = 'class="active"';
              }
              $page['title'] = MG::contextEditor('page', $page['title'], $page["id"], "page");

              if (strpos($page['url'], 'http://') === false) {
                $url = SITE.'/'.$page['parent_url'].$page['url'];
              } else {
                $url = $page['url'];
              }

              if ($hotFix1) {
                $print.= '<li'.$slider.'<a href="'.SITE.'"><span '.$active.'>'.$page['title'].'</span></a>';
              } else {
                $print.= '<li'.$slider.'<a href="'.$url.'"><span '.$active.'>'.$page['title'].'</span></a>';
              }
            }
          }

          if ($flag) {
            $display = "display:none";
            if (in_array($page['id'], $this->openedPage)) {
              $display = "display:block";
            }

            $sub_menu = '
              <ul class="sub_menu" style="'.$display.'">
                [li]
              </ul>';

            //Если страница  скрыта, то не идем вглубь.                
            $li = $this->getPagesUl($page['id'], $type);
            $print .= strlen($li) > 0 ? str_replace('[li]', $li, $sub_menu) : "";

            $print .= '</li>';
          } else {
            $print .= '</li>';
          }
        }
      }
    }

    $args = func_get_args();
    $result = $print;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает древовидный список страниц, пригодный для использования в футере.
   * Вернет заданное количество списков.
   * <code>
   *   $res = MG::get('pages')->getFooterPagesUl();
   *   viewData($res);
   * </code>
   * @param string $type тип списка (для публичной части, либо для админки).
   * @param int $column во сколько столбцов вывести результат.
   * @return string
   */
  public function getFooterPagesUl($type = 'public', $column = 3) {

    $print = '';
    if (empty($this->page)) {
      $print = '';
    } else {
      $lang = MG::get('lang');
      $gategoryArr = $this->page;
      //для публичной части убираем из меню закрытые страницы

      foreach ($gategoryArr as $key => $val) {
        if ($val['invisible'] == 1) {
          unset($gategoryArr[$key]);
        }
      }

      $countPage = $inColumn = 0;
      foreach ($gategoryArr as $page) {
        if ($page['parent'] == 0) {
          $countPage++;
        }
      }

      if ($countPage > 1 && $column > 0) {
        $inColumn = floor($countPage / $column);
      }

      $newColumn = true;
      $i = 0;
      foreach ($gategoryArr as $page) {

        if ($page['parent'] == 0) {

          if ($newColumn == true) {

            if ($i > 0) {
              $i = 0;
              $print.= "</ul><ul class='footer-column'>";
            } else {
              $i = 0;
              $print.= "<ul class='footer-column'>";
            }
          }

          if ($i < $inColumn) {
            $newColumn = false;
            $i++;
          } else {
            $newColumn = true;
          }

          $hotFix1 = false;
          if ($page['parent_url'] == "" && ($page['url'] == 'index' || $page['url'] == 'index.html')) {
            $hotFix1 = true;
          }

          if ($page['invisible'] != 1) {
            $active = '';
            if (URL::isSection($page['parent_url'].$page['url'])) {
              $active = 'class="active"';
            }
            $page['title'] = MG::contextEditor('page', $page['title'], $page["id"], "page");

            if (strpos($page['url'], 'http://') === false) {
              $url = SITE.'/'.$page['parent_url'].$page['url'];
            } else {
              $url = $page['url'];
            }

            if ($hotFix1) {
              $print.= '<li><a href="'.SITE.'"><span '.$active.'>'.$page['title'].'</span></a>';
            } else {
              $print.= '<li><a href="'.$url.'"><span '.$active.'>'.$page['title'].'</span></a>';
            }
          }
        }
      }
      $print.= "</ul>";
    }

    $args = func_get_args();
    $result = $print;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив вложенных страниц на заданном уровне.
   * <code>
   *   $res = MG::get('pages')->getChildPageIds(8);
   *   viewData($res);
   * </code>
   * @param int $parentId  id родительской страницы.
   * @return array
   */
  public function getChildPageIds($parentId = 0) {
    $result = array();

    $res = DB::query('
      SELECT id
      FROM `'.PREFIX.'page`
      WHERE parent = %d
      ORDER BY id
    ', $parentId);

    while ($row = DB::fetchArray($res)) {
      $result[] = $row['id'];
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает список только id всех вложенных страниц.
   * <code>
   *   $res = MG::get('pages')->getPagesInside(8);
   *   viewData($res);
   * </code>
   * @param int $parent id родительской страницы.
   * @return array
   */
  public function getPagesInside($parent = 0) {
    if (!empty($this->page))
      foreach ($this->page as $page) {
        if ($parent == $page['parent']) {
          $this->listCategoryId[] = $page['id'];
          $this->getPagesInside($page['id']);
        }
      }
    $args = func_get_args();
    if (!empty($this->listCategoryId)) {
      $this->listCategoryId = array_flip(array_flip($this->listCategoryId)); //удаление дублей
    }
    $result = $this->listCategoryId;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив id страниц и ее заголовок.
   * <code>
   *   $res = MG::get('pages')->getCategoryTitleList();
   *   viewData($res);
   * </code>
   * @return array
   */
  public function getCategoryTitleList() {
    $titleList[0] = 'Корень каталога';
    if (!empty($this->page))
      foreach ($this->page as $page) {
        $titleList[$page['id']] = $page['title'];
      }

    $args = func_get_args();
    $result = $titleList;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает иерархический массив страниц.
   * <code>
   *   $res = MG::get('pages')->getHierarchyPage();
   *   viewData($res);
   * </code>
   * @param int $parent id родительской страницы.
   * @return array
   */
  public function getHierarchyPage($parent = 0) {
    $catArray = array();
    if (!empty($this->page))
      foreach ($this->page as $page) {
        if ($parent == $page['parent']) {
          $child = $this->getHierarchyPage($page['id']);

          if (!empty($child)) {
            $array = $page;
            usort($child, array(__CLASS__, "sort"));
            $array['child'] = $child;
          } else {
            $array = $page;
          }

          $catArray[] = $array;
        }
      }

    $args = func_get_args();
    $result = $catArray;
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив дочерних страниц для заданной страницы.
   * <code>
   *   $res = MG::get('pages')->getSubPages('aktsiya-skidka-26-na-ves-assortiment-tovarov');
   *   viewData($res);
   * </code>
   * @param string|bool $pageUrl заданная страница
   * @return array
   */
  public function getSubPages($pageUrl = false) {
    $result = array();
    if (!$pageUrl) {
      $pageUrl = URL::getClearUri();
    }
    $pageUrl = trim($pageUrl, '/').'/';

    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'page`
      WHERE parent_url = '.DB::quote($pageUrl).'
      ORDER BY id
    ');

    while ($row = DB::fetchAssoc($res)) {
      $result[] = array(
        'title' => $row['title'],
        'url' => $row['parent_url'].$row['url']);
    }


    $args = func_get_args();

    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает массив страниц на том же уровне что и заданная страница.
   * <code>
   *   $res = MG::get('pages')->getParallelslPage('index/1');
   *   viewData($res);
   * </code>
   * @param string|bool $pageUrl  заданная страница
   * @return array
   */
  public function getParallelslPage($pageUrl = false) {
    $result = array();
    if (!$pageUrl) {
      $pageUrl = URL::getClearUri();
    }
    $pageUrl = URL::parseParentUrl($pageUrl);
    $result = $this->getSubPages($pageUrl);
    $args = func_get_args();

    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает li список дочерних страниц.
   * <code>
   *   $res = MG::get('pages')->getListSubPage('index', '<span class="#INDEX#">#TITLE#</span>');
   *   viewData($res);
   * </code>
   * @param string|bool $pageUrl  заданная страница.
   * @param string $pattern - шаблон вывода подстраниц.
   * @return array
   */
  public function getListSubPage($pageUrl = false, $pattern = '<span class="#INDEX#">#TITLE#</span>') {
    $result = '';
    $pages = $this->getSubPages($pageUrl);
    $i = 1;
    foreach ($pages as $page) {
      $inside = str_replace('#TITLE#', $page['title'], $pattern);
      $inside = str_replace('#INDEX#', 'lp'.$i++, $inside);
      $result .= '
        <li><a href="'.SITE.'/'.$page['url'].'">'.$inside.'</a></li>';
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает li список страниц этого же уровня стрaниц.
   * <code>
   *   $res = MG::get('pages')->getListParallelslPage('index/1', '<span class="#INDEX#">#TITLE#</span>');
   *   viewData($res);
   * </code>
   * @param string|bool $pageUrl  заданная страница.
   * @param string $pattern - шаблон вывода страниц.
   * @return array
   */
  public function getListParallelslPage($pageUrl = false, $pattern = '<span class="#INDEX#">#TITLE#</span>') {
    $result = '';
    $pages = $this->getParallelslPage($pageUrl);
    $i = 1;
    $thisUrl = URL::getClearUri();

    foreach ($pages as $page) {
      $inside = str_replace('#TITLE#', $page['title'], $pattern);
      $inside = str_replace('#INDEX#', 'lp'.$i++, $inside);
      $active = '';
      if ('/'.$page['url'] == $thisUrl) {
        $active = 'class="active"';
      }
      $result .= '
        <li><a href="'.SITE.'/'.$page['url'].'" '.$active.'>'.$inside.'</a></li>';
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Возвращает отдельные пункты списка заголовков страниц.
   * <code>
   *   $array = MG::get('pages')->getHierarchyPage();
   *   $res = MG::get('pages')->getTitlePage($array);
   *   viewData($res);
   * </code>
   * @param array $arrayPages массив со страницами.
   * @param int $selectedPage выбранная страница.
   * @param bool $modeArray - если установлен этот флаг, то  результат вернет массив а не HTML список.
   * @return string
   */
  public function getTitlePage($arrayPages, $selectedPage = 0, $modeArray = false, $printChildIds = false) {
    if ($modeArray) {
      global $catArr;
    }
    if (empty($catArr)) {$catArr = array();}
    global $lvl;
    $option = '';
    foreach ($arrayPages as $page) {
      $select = '';
      if ($selectedPage == $page['id']) {
        $select = 'selected = "selected"';
      }

      if ($printChildIds) {
        if (!empty($page['child'])) {
          $childIds = 'data-childids=['.implode(',', self::getChildIds($page['child'])).']';
        } else {
          $childIds = 'data-childids=[]';
        }
      } else {
        $childIds = '';
      }

      $option .= '<option '.$childIds.' value='.$page['id'].' '.$select.' >';
      $option .= str_repeat('-', $lvl);
      $option .= $page['title'];
      $option .= '</option>';
      $catArr[$page['id']] = str_repeat('-', $lvl).$page['title'];
      if (isset($page['child'])) {
        $lvl++;
        $option .= $this->getTitlePage($page['child'], $selectedPage, $modeArray, $printChildIds);
        $lvl--;
      }
    }
    $args = func_get_args();

    $result = $option;
    if ($modeArray) {
      $result = $catArr;
    }

    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  private static function getChildIds($children, $childIds = array()) {
    foreach ($children as $child) {
      $childIds[] = $child['id'];
      if (!empty($child['child'])) {
        $childIds = array_merge($childIds, self::getChildIds($child['child']));
      }
    }
    return $childIds;
  }

  /**
   * Перемещает страницу
   * @param string $pageId id перемещаемой страницы.
   * @param string $parentId id страницы, в которую перемещать.
   * @return void
   */
  public function movePage($pageId, $parentId) {
    $parentUrl = $this->getParentUrl($parentId);

    $currentPage = $this->getPageById($pageId, true);
    if(in_array($currentPage['url'],$this->systemPages)){
      return false;
    }

    $posParent = stripos($parentUrl, '?');
    if($posParent !== false) {
      $parentUrl = substr($parentUrl, 0, $posParent).'/';
    }

    DB::query("UPDATE `".PREFIX."page` SET 
      `parent_url` = ".DB::quote($parentUrl).",
      `parent` = ".DB::quoteInt($parentId)."
      WHERE `id` = ".DB::quoteInt($pageId));
    $res = DB::query("SELECT `id` FROM `".PREFIX."page` 
      WHERE `parent` = ".DB::quote($pageId));
    while ($row = DB::fetchAssoc($res)) {
      $this->movePage($row['id'], $pageId);
    }
  }

  /**
   * Получает параметры страницы по его URL.
   * <code>
   *   $res = MG::get('pages')->getPageByUrl('index');
   *   viewData($res);
   * </code>
   * @param string $url url запрашиваемой страницы.
   * @param string $parentUrl url родительской страницы.
   * @return array массив с данными о странице.
   */
  public function getPageByUrl($url, $parentUrl = "") {
    $result = array();
    $sql = "SELECT * FROM `".PREFIX."page` WHERE url = ".DB::quote($url);
    if ($parentUrl !== '') {
      $sql .= "AND parent_url = ".DB::quote($parentUrl);
    }
    $res = DB::query($sql);

    if (!empty($res)) {
      if ($cat = DB::fetchAssoc($res)) {
        $result = $cat;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает параметры страницы по её Id.
   * <code>
   *   $res = MG::get('pages')->getPageById('1');
   *   viewData($res);
   * </code>
   * @param string $id запрашиваемой  страницы.
   * @param bool $fromDb загрузка из базы данных.
   * @return array массив с данными о странице.
   */
  public function getPageById($id, $fromDb = false) {
    $result = array();
    // получаем данные из памяти
    if(!$fromDb) {
      if(!empty($this->page[$id])) {
        $result = $this->page[$id];
      }
    } else {
      // получаем данные из базы , необходимо при сортировке
      $res = DB::query('
       SELECT *
       FROM `'.PREFIX.'page`
       WHERE id = '.DB::quote($id)
      );

      if (!empty($res)) {
        if ($cat = DB::fetchArray($res)) {
          $result = $cat;
        }
      }
    } 
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }


  /**
   * Получает содержание страницы.
   * <code>
   *   $res = MG::get('pages')->getDesctiption('1');
   *   viewData($res);
   * </code>
   * @param int $id - id страницы
   * @return string
   */
  public function getDesctiption($id) {
    $result = null;
    $res = DB::query('
      SELECT html_content
      FROM `'.PREFIX.'page`
      WHERE id = "%d"
    ', $id);

    if (!empty($res)) {
      if ($cat = DB::fetchArray($res)) {
        $result = $cat['html_content'];
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   *  Упорядочивает страницы по сортировке.
   *  @param array $a первая страница
   *  @param array $b вторая страница
   *  @return int
   */
  public function sort($a, $b) {
    return $a['sort'] - $b['sort'];
  }

  /**
   * Меняем местами параметры сортировки двух страниц.
   * @param int $oneId - первый ID 
   * @param int $twoId - второй ID 
   * @return bool
   */
  public function changeSortPage($oneId, $twoId) {
    $cat1 = $this->getPageById($oneId, true);
    $cat2 = $this->getPageById($twoId, true);
    if (!empty($cat1) && !empty($cat2)) { 
      
      $res = DB::query('
       UPDATE `'.PREFIX.'page`
       SET  `sort` = '.DB::quote($cat1['sort']).'  
       WHERE  `id` ='.DB::quote($cat2['id']).'
     ');      
   
      $res = DB::query('
       UPDATE `'.PREFIX.'page`
       SET  `sort` = '.DB::quote($cat2['sort']).'  
       WHERE  `id` ='.DB::quote($cat1['id']).'
     ');
      return true;
    }
    return false;
  
    
  }

  /**
   * Делает все страницы видимыми в меню.
   * <code>
   *   $res = MG::get('pages')->refreshVisiblePage();
   *   viewData($res);
   * </code>
   * @return bool true
   */
  public function refreshVisiblePage() {
    $res = DB::query('
       UPDATE `'.PREFIX.'page`
       SET  `invisible` = 0  
       WHERE  1 = 1
     ');
    return true;
  }

  /**
   * Возвращает страницы, которые должны быть выведены в меню.
   * <code>
   *   $res = MG::get('pages')->getPageInMenu();
   *   viewData($res);
   * </code>
   * @return array массив страниц.
   */
  public function getPageInMenu() {
    $result = array();
    $res = DB::query('
      SELECT id, title, url, sort
      FROM `'.PREFIX.'page`
      WHERE `invisible` = 0
      ORDER BY `sort` ASC
    ');

    if (!empty($res)) {
      while ($page = DB::fetchAssoc($res)) {
        $result[] = $page;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
  /**
   * Возвращает общее количество страниц в базе.
   * <code>
   *   $res = MG::get('pages')->getCountPages();
   *   viewData($res);
   * </code>
   * @return int количество страниц
   */
  public static function getCountPages() {
    $count = DB::query('SELECT COUNT(`id`) as count FROM `'.PREFIX.'page`');
    $count = DB::fetchAssoc($count);
    $countPages = $count['count'];
    return $countPages;
  }

  /**
   * Возвращает строки для таблицы со страницами.
   * <code>
   *   $array = MG::get('pages')->getHierarchyPage();
   *   $res = MG::get('pages')->getPages($array);
   *   viewData($res);
   * </code>
   * @param array $pagesArray массив со страницами
   * @param int $parentLevel уровень вложенности родительской таблицы
   * @param int $parent выводить ли полный массив с информацией
   * @return string
   */
  public static function getPages($pagesArray, $parentLevel, $parent) {
    $pages = '';
    foreach($pagesArray as $page) { 
      $pages .= self::getHtmlPageRow($pagesArray, $page['id'], $parentLevel);
    }
    return $pages;
  }

  /**
   * Возвращает строку для таблицы со страницами.
   * <code>
   *   $array = MG::get('pages')->getHierarchyPage();
   *   $res = MG::get('pages')->getPages($array);
   *   viewData($res);
   * </code>
   * @param array $pages массив со страницами
   * @param int $id id страницы
   * @param int $level уровень вложенности родительской таблицы
   * @return string
   */
  public static function getHtmlPageRow($pages, $id, $level) {
    $pageCount = MG::get('pageCountToAdmin');
    foreach($pages as $page) {
      if($page['id'] == $id) {
        $result = 0;
        // группировка для сортировки
        if($level == 0) {
          $group = 'main';
        } else {
          $group = 'group-'.$page['parent'];
        }
        // отображать ли кнопку для выпадающего списка
        $res = DB::query('SELECT id FROM '.PREFIX.'page WHERE parent = '.DB::quote($page['id']).' ORDER BY sort ASC LIMIT 1');
        while($row = DB::fetchAssoc($res)) {
          $result = $row['id'];
        }
        if(!empty($result)) {
          $circlePlus = '<button class="tip show_sub_menu" id="toHide-'.$page['id'].'" title="Показать/скрыть вложенные категории"><i class="fa fa-plus-circle"></i></button> ';
        } else {
          $circlePlus = '';
        }
        // отображать ли иконку вложенности
        $levelArrow = '';
        for($i = 0; $i < $level; $i++) {
          $levelArrow .= '<i class="fa fa-long-arrow-right" aria-hidden="true"></i>';
        }
        // отмечен ли чекбокс показа
        if($page['invisible'] == '0') {
          $checkbox = 'active';
        } else {
          $checkbox = '';
        } 
        if(USER::access('page') > 1) { 
          $actions = '<li><a class="add-sub-cat" href="javascript:void(0);" aria-label="Добавить вложенную страницу" tooltip="Добавить вложенную страницу" flow="left"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></li>
                    <li><a class="'.$checkbox.' visible" aria-label="Отображать/Не отображать страницу на сайте" tooltip="Отображать/Не отображать страницу на сайте" flow="left" href="javascript:void(0);"><i class="fa fa-lightbulb-o" aria-hidden="true"></i></a></li>
                    <li><a class="delete-sub-cat tooltip--small" aria-label="Удалить страницу" tooltip="Удалить страницу" flow="left" href="javascript:void(0);"><i class="fa fa-trash" aria-hidden="true"></i></a></li>';
        } else {
          $actions = '';
        }
        $url = urldecode('/'.$page['parent_url'].$page['url']);
        $fullUrl = SITE.$url;
        if (strpos($url, 'http://') !== false) {
          $url = explode('http://', $url);
          $url = 'http://'.array_pop($url);
          $fullUrl = $url;
        }
        if (strpos($url, 'https://') !== false) {
          $url = explode('https://', $url);
          $url = 'https://'.array_pop($url);
          $fullUrl = $url;
        }

        return '
              <tr class="level-'.($level+1).' '.$group.'" data-id="'.$page['id'].'" data-group="'.$group.'" data-level="'.($level+1).'" data-sort="'.$pageCount.'">
                <td class="checkbox">
                  <div class="checkbox">
                    <input type="checkbox" id="c'.$page['id'].'" name="page-check">
                    <label class="select-row shiftSelect" for="c'.$page['id'].'"></label>
                  </div>
                </td>
                <td class="sort hide-for-small-only">                
                    <a class="mover"
                       tooltip="Нажмите и перетащите страницу для изменения порядка сортировки в меню"
                       flow="right"
                       href="javascript:void(0);"
                       aria-label="Сортировать"
                       role="button">
                       <i class="fa fa-arrows" aria-hidden="true"></i>
                    </a>
                </td>
                <td class="number">'.$page['id'].'</td>
                <td class="name"><div class="name-container">'.$circlePlus.$levelArrow.'
                    <span class="product-name">
                        <button class="name-link link edit-sub-cat tooltip--center" aria-label="Редактировать страницу" tooltip="Редактировать страницу" flow="up" ><span>'.$page['title'].'</span></button>
                        <a class="tooltip--center tooltip--small" href="'.$fullUrl.'" aria-label="Перейти на страницу" tooltip="Перейти на страницу" target="_blank">
                            <i class="fa fa-external-link" aria-hidden="true"></i>
                        </a>
                    </span>
                    <div>
                </td>
                <td><a class="tooltip--center tooltip--small" href="'.$fullUrl.'" target="blank" aria-label="Перейти на страницу" tooltip="Перейти на страницу">'.$url.'</a></td>
                <td class="text-right actions">
                  <ul class="action-list">
                    <li><a class="edit-sub-cat tooltip--center" aria-label="Редактировать страницу" tooltip="Редактировать страницу" flow="left" href="javascript:void(0);" tabindex="0"><i class="fa fa-pencil" aria-hidden="true"></i></a></li>
                    '.$actions.'
                  </ul>
                </td>
              </tr>
              ';
      }
    }
  }
}