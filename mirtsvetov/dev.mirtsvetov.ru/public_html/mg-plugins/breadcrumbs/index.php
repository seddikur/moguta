<?php
    /*
      Plugin Name: Хлебные крошки
      Description: Выводит навигационную цепочку в каталоге товаров.
      Author: Дмитрий Гринчевский, Авдеев Марк, KNVLV
      Version: 2.4.3
      Edition: CLOUD
     */


    new BreadCrumbs;
    class BreadCrumbs {
        private static $pluginName = ''; // название плагина (соответствует названию папки)
        private static $path = ''; //путь до файлов плагина
        public function __construct() {
            mgAddShortcode('brcr', array(__CLASS__, 'breadcrumbs'));
            self::$pluginName = PM::getFolderPlugin(__FILE__);
            self::$path = PLUGIN_DIR.self::$pluginName;
            if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
                mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.str_replace(DIRECTORY_SEPARATOR, '/', self::$path).'/css/style.css" type="text/css" />');
            }
        }

        static function breadcrumbs() {
            if (
                    MG::get('controller') == 'controllers_catalog' && URL::getLastSection() != 'catalog'
                    || MG::get('controller') == 'controllers_product'
                    || MG::get('controller') == 'controllers_ajaxrequest'
                ) {
                $lang = PM::plugLocales(self::$pluginName);
                $hash = 'breadcrumbs'.URL::getUrl();
                if (LANG != "LANG") {
                    $hash .= LANG;
                }
                $breadcrumbs = Storage::get(md5($hash));
                if ($breadcrumbs == null) {
                    $sections = URL::getSections();
                    array_splice($sections, 0, 1);
                    if (((defined('SHORT_LINK') && SHORT_LINK == 1) || MG::getSetting('shortLink') == 'true') && MG::get('controller') == 'controllers_product') {
                        $product_url = URL::getLastSection();
                        $res = DB::query('
                        SELECT CONCAT(c.`parent_url`, c.`url`) as fullurl
                        FROM `'.PREFIX.'product` p LEFT JOIN `'.PREFIX.'category` c 
                        ON p.cat_id  = c.id WHERE p.url = '.DB::quote($product_url));
                        $cat = DB::fetchArray($res);
                        $sections = explode('/', $cat['fullurl']);
                        $sections[] = $product_url;
                    }

                    $breadcrumbs = "<li class='bread-crumbs__item' itemprop='itemListElement' itemscope itemtype='http://schema.org/ListItem'><a itemprop='item' href='".SITE."/catalog'><span itemprop='name'>"."__CATALOG_REPLACE__"."</span><meta itemprop='position' content='1'></a></li>";
                    $max = count($sections);
                    $i = 0;
                    $par = '';
                    foreach ($sections as $section) {
                        $url = $section;
                        $cat = 'title';
                        $i++;
                        if ($url != 'catalog') {
                            $data = self::checkURLname('*', 'category', $section, 'url', $par);
                            if (empty($data[0])) {$data[0] = array('parent_url'=>null,'id'=>null,'title'=>null);}

                            $url = $data[0]['parent_url'].$section;
                            $par = $data[0]['id'];

                            MG::loadLocaleData($par, LANG, 'category', $data[0]);
                            $res = $data[0]['title'];

                            if (empty($data[0]['title'])) {
                                $cat = 'name';
                                $n = '';
                                $result = self::checkURLname('*', 'product', $section, 'url', $n);
    //                            $url = $data[0]['parent_url'].$sections[1].(isset($sections[2])?('/'.$sections[2]):'');
                                $categoryRes = self::checkURLname('url, parent_url', 'category', $result[0]['cat_id'], 'id');
                                if (empty($categoryRes[0])) {$categoryRes[0] = array('parent_url'=>null,'url'=>null);}
    //                            $url = $categoryRes[0]['parent_url'].$categoryRes[0]['url'].$result[0]['url'];

                                $url = implode('/', $sections);
                                MG::loadLocaleData($result[0]['id'], LANG, 'product', $result[0]);

                                if (MG::getSetting('shortLink') == 'true') {
                                    $url = $result[0]['url'];
                                }

                                $res = $result[0]['title'];
                                if(empty($res)){
                                    $env = $_ENV['HTTP_REFERER'];
                                    $location = explode('/',$_ENV['HTTP_REFERER']);
                                    $url = array_pop($location);
                                    $locationName = MG::get('category')->getCategoryByUrl($url);
                                    $res = $locationName['title'];
                                }                        
                            }
                            $num = $i + 1;
                            if ($max == $i) {
                                $breadcrumbs .= ' <li class="bread-crumbs__item separator">&nbsp;/&nbsp;</li> <li class="bread-crumbs__item"  itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><span itemprop="name">'.$res.'</span><meta itemprop="position" content="'.$num.'">';
                            } else {
                                $breadcrumbs .= ' <li class="bread-crumbs__item separator">&nbsp;/&nbsp;</li> <li class="bread-crumbs__item"  itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a itemprop="item" href="'.SITE.'/'.$url.'"><span itemprop="name">'.$res.'</span><meta itemprop="position" content="'.$num.'"></a></li>';
                            }
                        }
                    }
                    $breadcrumbs = "<nav aria-label='Breadcrumb'><ul itemscope itemtype='http://schema.org/BreadcrumbList' class='bread-crumbs'>".$breadcrumbs."</ul></nav>";
                    //сохраняем объект в кэш
                    Storage::save(md5($hash), $breadcrumbs);
                }
                $breadcrumbs = str_replace("__CATALOG_REPLACE__", $lang["CATALOG"],$breadcrumbs);
                if (EDITION === 'saas') {
                    $breadcrumbs = CloudCoreBase::$CloudLocales->decryptionContentToRaw($breadcrumbs);
                }
                return $breadcrumbs;
            }
        }

        /**
         * Метод работает с БД, получая значение по передаваемым параметрам.
         *
         * @param string $col что.
         * @param string $table от куда.
         * @param string $name условие соответствие.
         * @return array массив с результатом.
         */
        public static function checkURLname($col, $table, $name, $where1, $parent_id = '') {
            $categories = array();
            if ($parent_id || $table == 'category') {

                if (empty($parent_id)) {
                    $parent_id = 0;
                }

                $where2 = 'parent';
                $sql = 'SELECT '.DB::quote($col, true).' FROM '.PREFIX.DB::quote($table, true).
                    ' WHERE '.DB::quote($where1, true).'='.DB::quote($name).'  AND '.DB::quote($where2, true).'='.DB::quote($parent_id).'';
                $result = DB::query($sql);
            } else {
                $sql = 'SELECT '.DB::quote($col, true).' FROM '.PREFIX.DB::quote($table, true).'  WHERE '.DB::quote($where1, true).'='.DB::quote($name).'  ';
                $result = DB::query($sql);
            }
            while ($row = DB::fetchArray($result)) {
                $categories[] = $row;
            }
            if ($result) {
                return $categories;
            }
        }
       

    }
