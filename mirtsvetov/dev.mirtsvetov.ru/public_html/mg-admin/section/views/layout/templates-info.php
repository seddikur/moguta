<?php
// Получаем список шаблонов, доступных для установки:
$dir = SITE_DIR . "mg-templates";
$folderTemplate = array_diff(scandir($dir), array('.', '..'));
$templates = array();
foreach ($folderTemplate as $foldername) {
    if (file_exists(SITE_DIR.'mg-templates/'.$foldername.'/images/mg-preview-full.jpg')) {
        $imagePreview = SITE.'/'.'mg-templates/'.$foldername.'/images/mg-preview-full.jpg';
    } else {
        $imagePreview = '';
    }

    if (file_exists(SITE_DIR.'mg-templates/'.$foldername.'/images/mg-preview-full_2x.jpg')) {
        $imagePreview2x = SITE.'/'.'mg-templates/'.$foldername.'/images/mg-preview-full_2x.jpg';
    } else {
        $imagePreview2x = '';
    }

    if ($foldername[0] == '.') continue;
    if (file_exists($dir . DS . $foldername . DS . 'template.php')) {
        // Массив с названиями шаблонов и их превью-изображениями
        $templates[$foldername] = array(
            'foldername' => $foldername,
            'image-preview' => $imagePreview,
            'image-preview_2x' => $imagePreview2x
        );
    }
}
// Активный шаблон
$tempSelected = MG::getSetting('templateName');

// Массив с необходимой информацией о шаблонах:
$tempsInfo = array(
    
    'mg-leader' => array(
        'link' => 'https://leader.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_LEADER'],
        'name' => 'Leader',
        'market' => 'https://moguta.ru/templates/shablon-leader'
    ),
    'mg-friend' => array(
        'link' => 'https://friend.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_FRIEND'],
        'name' => 'Friend',
        'market' => 'https://moguta.ru/templates/shablon-friend'
    ),
    'mg-storm' => array(
        'link' => 'https://storm.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_STORM'],
        'name' => 'Storm',
        'market' => 'https://moguta.ru/templates/shablon-storm'
    ),
    'mg-adriana' => array(
        'link' => 'https://adriana.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_ADRIANA'],
        'name' => 'Adriana',
        'market' => 'https://moguta.ru/templates/shablon-adriana'
    ),
    'mg-tasty' => array(
        'link' => 'https://tasty.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_TASTY'],
        'name' => 'Tasty',
        'market' => 'https://moguta.ru/templates/shablon-tasty'
    ),
    'mg-rose' => array(
        'link' => 'https://rose.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_ROSE'],
        'name' => 'Rose',
        'market' => 'https://moguta.ru/templates/shablon-rose'
    ),
    'mg-gears' => array(
        'link' => 'https://gears.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_GEARS'],
        'name' => 'Gears',
        'market' => 'https://moguta.ru/templates/gears'
    ),
    'mg-oasis' => array(
        'link' => 'https://oasis.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_OASIS'],
        'name' => 'Oasis',
        'market' => 'https://moguta.ru/templates/oasis'
    ),
    'mg-air' => array(
        'link' => 'https://air.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_AIR'],
        'name' => 'Air',
        'market' => 'https://moguta.ru/templates/shablon-air'
    ),
    'mg-elegant' => array(
        'link' => 'https://elegant.template.moguta.cloud', 
        'color' => '1', 
        'description' => $lang['TEMPLATE_DSCRIPTION_ELEGANT'],
        'name' => 'Elegant',
        'market' => 'https://moguta.ru/templates/shablon-elegant'
    ),
    'mg-beauty' => array(
        'link' => 'https://beauty.template.moguta.cloud',
        'color' => '1',
        'description' => $lang['TEMPLATE_DSCRIPTION_BEAUTY'],
        'name' => 'Beauty',
        'market' => 'https://moguta.ru/templates/shablon-beauty'
    ),
    'mg-william' => array(
        'link' => 'https://william.template.moguta.cloud', 
        'color' => '', 
        'description' => $lang['TEMPLATE_DSCRIPTION_WILLIAM'],
        'name' => 'William', 
        'market' => 'https://moguta.ru/templates/william'
    ),
    'mg-honey' => array(
        'link' => 'https://honey.template.moguta.cloud', 
        'color' => '1', 
        'description' => $lang['TEMPLATE_DSCRIPTION_HONEY'],
        'name' => 'Honey', 
        'market' => 'https://moguta.ru/templates/honey'
    ),
    'mg-platos' => array(
        'link' => 'https://platos.template.moguta.cloud', 
        'color' => '', 
        'description' => $lang['TEMPLATE_DSCRIPTION_PLATOS'],
        'name' => 'Platos', 
        'market' => 'https://moguta.ru/templates/shablon-platos'
    ),
    'mg-sunshine' => array(
        'link' => 'https://sunshine.template.moguta.cloud', 
        'color' => '1', 
        'description' => $lang['TEMPLATE_DSCRIPTION_SUNSHINE'],
        'name' => 'Sunshine', 
        'market' => 'https://moguta.ru/templates/shablon-sunshine'
    ),
    'mg-jasmine' => array(
        'link' => 'https://jasmine.template.moguta.cloud', 
        'color' => '', 
        'description' => $lang['TEMPLATE_DSCRIPTION_JASMINE'],
        'name' => 'Jasmine', 
        'market' => 'https://moguta.ru/templates/jasmine'
    ),
    'mg-victoria' => array(
        'link' => 'https://victoria.template.moguta.cloud', 
        'color' => '22184c', 
        'description' => $lang['TEMPLATE_DSCRIPTION_VICTORIA'],
        'name' => 'Victoria', 
        'market' => 'https://moguta.ru/templates/victoria'
    ),
    'mg-amelia' => array(
        'link' => 'https://amelia.template.moguta.cloud', 
        'color' => '333333', 
        'description' => $lang['TEMPLATE_DSCRIPTION_AMELIA'],
        'name' => 'Amelia', 
        'market' => 'https://moguta.ru/templates/shablon-amelia'
    ),
    'mg-boutique' => array(
        'link' => 'https://boutique.template.moguta.cloud', 
        'color' => '009688', 
        'description' => $lang['TEMPLATE_DSCRIPTION_BOUNTIQUE'],
        'name' => 'Boutique', 
        'market' => 'https://moguta.ru/templates/boutique'
    ),
    'p11-impressive' => array(
        'link' => 'https://impressive.template.moguta.cloud', 
        'color' => '00818a', 
        'description' => $lang['TEMPLATE_DSCRIPTION_IMPRESSIVE'],
        'name' => 'Impressive', 
        'market' => 'https://moguta.ru/templates/shablon-impressive'
    ),
    'elektroshop' => array(
        'link' => 'https://elektroshop.template.moguta.cloud', 
        'color' => '0000FF', 
        'description' => $lang['TEMPLATE_DSCRIPTION_ELEKTROSHOP'],
        'name' => 'Elektroshop', 
        'market' => 'https://moguta.ru/templates/shablon-elektroshop'
    ),
    'moguta-standard' => array(
        'link' => 'https://mogutastandard.template.moguta.cloud', 
        'color' => '1', 
        'description' => (EDITION == 'saas')?$lang['TEMPLATE_DSCRIPTION_MOGUTA']:$lang['TEMPLATE_DSCRIPTION_MOGUTA_STANDARD'],
        'name' => 'Moguta-standard', 
        'market' => 'https://moguta.ru/templates/moguta'
    ),
    'moguta' => array(
        'link' => 'https://mogutastandard.template.moguta.cloud', 
        'color' => '1', 
        'description' => $lang['TEMPLATE_DSCRIPTION_MOGUTA'],
        'name' => 'Moguta', 
        'market' => 'https://moguta.ru/templates/moguta'
    ),
    'mg-coleos' => array(
        'link' => 'https://coleos.template.moguta.cloud', 
        'color' => '009688', 
        'description' => $lang['TEMPLATE_DSCRIPTION_COLEOS'],
        'name' => 'Coleos', 
        'market' => 'https://moguta.ru/templates/coleos'
    ),
    'mg-deep-blue-template' => array(
        'link' => 'https://deepbluetemplate.template.moguta.cloud', 
        'color' => '', 
        'description' => $lang['TEMPLATE_DSCRIPTION_DEEP_BLUE'],
        'name' => 'Deep blue', 
        'market' => 'https://moguta.ru/templates/deep-blue-template'
    ),
    'mg-default' => array(
        'link' => 'https://default.template.moguta.cloud', 
        'color' => '1f776f', 
        'description' => $lang['TEMPLATE_DSCRIPTION_DEFAULT'],
        'name' => 'Default', 
        'market' => 'https://moguta.ru/templates/mg-default'
    ),
    'mg-flatty' => array(
        'link' => 'https://flatty.template.moguta.cloud', 
        'color' => '00A9A8', 
        'description' => $lang['TEMPLATE_DSCRIPTION_FLATTY'],
        'name' => 'Flatty', 
        'market' => 'https://moguta.ru/templates/stilniy-zeleniy'
    ),
    'mg-grace' => array(
        'link' => 'https://grace.template.moguta.cloud', 
        'color' => '222222',
        'description' => $lang['TEMPLATE_DSCRIPTION_GRACE'],
        'name' => 'Grace', 
        'market' => 'https://moguta.ru/templates/grace'
    ),
    'mg-lucy' => array(
        'link' => 'https://lucy.template.moguta.cloud', 
        'color' => '264566', 
        'description' => $lang['TEMPLATE_DSCRIPTION_LUCY'],
        'name' => 'Lucy', 
        'market' => 'https://moguta.ru/templates/lucy'
    ),
    'mg-market' => array(
        'link' => 'https://market.template.moguta.cloud', 
        'color' => '129EDD', 
        'description' => $lang['TEMPLATE_DSCRIPTION_MARKET'],
        'name' => 'Market', 
        'market' => 'https://moguta.ru/templates/mg-market'
    ),
    'mg-megastore' => array(
        'link' => 'https://megastore.template.moguta.cloud', 
        'color' => '0A93AC', 
        'description' => $lang['TEMPLATE_DSCRIPTION_MEGASTORE'],
        'name' => 'Megastore', 
        'market' => 'https://moguta.ru/templates/shablon-megastore'
    ),
    'mg-porto' => array(
        'link' => 'https://porto.template.moguta.cloud', 
        'color' => '175659', 
        'description' => $lang['TEMPLATE_DSCRIPTION_PORTO'],
        'name' => 'Porto', 
        'market' => 'https://moguta.ru/templates/shablon-moguta-cms-porto'
    ),
    'mg-selling' => array(
        'link' => 'https://selling.template.moguta.cloud', 
        'color' => '00A3A3', 
        'description' => $lang['TEMPLATE_DSCRIPTION_SELLING'],
        'name' => 'Selling', 
        'market' => 'https://moguta.ru/templates/selling'
    ),
    'mg-store' => array(
        'link' => 'https://store.template.moguta.cloud', 
        'color' => '6DBCDB', 
        'description' => $lang['TEMPLATE_DSCRIPTION_STORE'],
        'name' => 'Store', 
        'market' => 'https://moguta.ru/templates/shablon-moguta-cms-store'
    ),
    'mg-style' => array(
        'link' => 'https://style.template.moguta.cloud', 
        'color' => '2FB991', 
        'description' => $lang['TEMPLATE_DSCRIPTION_STYLE'],
        'name' => 'Style', 
        'market' => 'https://moguta.ru/templates/shablon-moguta-cms-mg-style'
    ),
    'p55-organic-mini' => array(
        'link' => 'https://organicmini.template.moguta.cloud', 
        'color' => '1', 
        'description' => $lang['TEMPLATE_DSCRIPTION_ORGANIC'],
        'name' => 'Organic', 
        'market' => 'https://moguta.ru/templates/shablon-organic'
    ),
    'p55-universal' => array(
        'link' => 'https://universal.template.moguta.cloud', 
        'color' => '', 
        'description' => $lang['TEMPLATE_DSCRIPTION_UNIVERSAL'],
        'name' => 'Universal',
        'market' => 'https://moguta.ru/templates/shablon-universal'
    ),
    'mg-automobiles' => array(
        'link' => 'https://automobiles.template.moguta.cloud', 
        'color' => '2587C4', 
        'description' => $lang['TEMPLATE_DSCRIPTION_AUTOMOBILES'],
        'name' => 'Automobiles', 
        'market' => 'https://moguta.ru/templates/automobiles'
    ),
    'mg-burgundy' => array(
        'link' => 'https://burgundy.template.moguta.cloud', 
        'color' => '', 
        'description' => $lang['TEMPLATE_DSCRIPTION_BURGUNDY'],
        'name' => 'Burgundy',
        'market' => 'https://moguta.ru/templates/burgundy'
    ),
    'mg-children-store' => array(
        'link' => 'https://childrenstore.template.moguta.cloud', 
        'color' => '4D484E', 
        'description' => $lang['TEMPLATE_DSCRIPTION_CHILDREN'],
        'name' => 'Children', 
        'market' => 'https://moguta.ru/templates/children-store'
    ),
    'mg-classic' => array(
        'link' => 'https://classic.template.moguta.cloud', 
        'color' => '7abcff', 
        'description' => $lang['TEMPLATE_DSCRIPTION_CLASSIC'],
        'name' => 'Classic', 
        'market' => 'https://moguta.ru/templates/shablon-classic'
    ),
);

if (EDITION === 'saas') {
    $templates = array_merge_recursive($tempsInfo, $templates);
}
?>