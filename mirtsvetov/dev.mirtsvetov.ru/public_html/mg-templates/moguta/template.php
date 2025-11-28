<?php /*
Template Name: Moguta
Author: Moguta
Version: 1.2.18
Edition: CLOUD
*/ ?>
<!DOCTYPE html>
<html <?php getHtmlAttributes() ?>>
<head>
    <?php
    // Выводим метатеги страницы, стили шаблона и плагинов, подключенные через mgAddMeta,
    // а также jquery из mg-core/scripts
    mgMeta("meta", "css", "jquery");
	?>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <!--[if lte IE 9]>
    <link rel="stylesheet" type="text/css" href="<?php echo PATH_SITE_TEMPLATE ?>/css/reject/reject.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo PATH_SITE_TEMPLATE ?>/css/style-ie9.css"/>
    <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
    <![endif]-->
    <?php
    // Добавляем предзагрузку файла стилей
    $mainStyleUrl = getMainStyleLink();
    if (!empty($mainStyleUrl) && $mainStyleUrl !== null) { ?><link rel="preload" href="<?php echo $mainStyleUrl ?>" as="style"><?php } ?>
    <?php mgAddMeta('css/common.css'); ?>
    <?php mgAddMeta('css/layout.css'); ?>
    <?php mgAddMeta('css/grid.css'); ?>
    <?php mgAddMeta('css/animate.css'); ?>
    <?php
    // Полифил для css-переменных
    mgAddMeta('lib/css-vars-ponyfill.js'); ?>
    <?php if (MG::get('templateParams')['DESIGN']['checkbox_new_design'] === 'false') { ?>
    <style>
        @font-face {
          font-family: "JB Sans";
          src: url("<?= SITE ?>/mg-templates/moguta-standard/fonts/JB_Sans_DemiBold.woff2") format("woff2");
          font-weight: 600;
          font-style: normal;
          font-display: swap;
        }

        @font-face {
          font-family: "JB Sans";
          src: url("<?= SITE ?>/mg-templates/moguta-standard/fonts/JB_Sans_Medium.woff2") format("woff2");
          font-weight: 500;
          font-style: normal;
          font-display: swap;
        }

        @font-face {
          font-family: "JB Sans";
          src: url("<?= SITE ?>/mg-templates/moguta-standard/fonts/JB_Sans_Bold.woff2") format("woff2");
          font-weight: 700;
          font-style: normal;
          font-display: swap;
        }
    </style>
    <?php } ?>
    <?php
    if (class_exists('trigger')) {
    $triggerId = MG::get('templateParams')['TRIGGERS']['trigger_id'];

    // Получаем настройки триггеров из базы
    $triggerInfo = DB::fetchAssoc(DB::query("SELECT * FROM `" . PREFIX . "trigger-guarantee` WHERE id=" . DB::quote($triggerId)));
    $triggerInfo['settings'] = unserialize(stripslashes($triggerInfo['settings']));
    ?>
    <style>
    
    .advantage__image i {
      <?php if (!empty($triggerInfo['settings']['color_icon'])) : ?>
        color: <?php echo '#' . $triggerInfo['settings']['color_icon'] ?>;
      <?php endif; ?>
      <?php if (!empty($triggerInfo['settings']['fontSize'])) : ?>
        font-size: <?php echo $triggerInfo['settings']['fontSize'] . 'em' ?>;
      <?php endif; ?>
    }
    .advantage__image {
      <?php if (!empty($triggerInfo['settings']['place']) && $triggerInfo['settings']['place'] == 'top') : ?>
        margin: 0 0 10px 0;
      <?php endif; ?>
      <?php if (!empty($triggerInfo['settings']['fontSize'])) : ?>
        width: calc(<?php echo $triggerInfo['settings']['fontSize'] . 'em' ?> + 30px);
        height: calc(<?php echo $triggerInfo['settings']['fontSize'] . 'em' ?> + 30px);
      <?php endif; ?>
      <?php if (!empty($triggerInfo['settings']['background_icon'])) : ?>
        background-color: <?php echo '#' . $triggerInfo['settings']['background_icon'] ?>;
      <?php endif; ?>
    }

    .advantage__image {
      <?php if (!empty($triggerInfo['settings']['form']) && $triggerInfo['settings']['form'] == 'circle') : ?>
        border-radius: 50%;
      <?php endif; ?>
    }
    .advantage__image img {
      <?php if (!empty($triggerInfo['settings']['fontSize'])) : ?>
        width: <?php echo $triggerInfo['settings']['fontSize'] . 'em' ?>;
        height: <?php echo $triggerInfo['settings']['fontSize'] . 'em' ?>;
      <?php endif; ?>
    }
    .advantage__card {
      <?php if (!empty($triggerInfo['settings']['place']) && $triggerInfo['settings']['place'] == 'top') : ?>
        flex-direction: column;
        justify-content:center;
      <?php endif; ?>
      
    }
    .advantage__content h3, 
    .advantage__content p {
      <?php if (!empty($triggerInfo['settings']['place']) && $triggerInfo['settings']['place'] != 'top') :
      ?>
        text-align: left;
      <?php endif; ?>
    }
  </style>
    <?php } ?>
</head>

<body class="l-body <?php MG::addBodyClass('l-'); ?>" <?php backgroundSite(); ?>>

<?php

//phpinfo();
// Спрайт SVG иконок
// Необходим для корректного отображения большинства стандартных компонентов
component('svg');
?>

<?php
// Шапка сайта для ie9
layout('ie9');
?>

<?php
// Шапка сайта
// layout/layout_header.php
layout('header', $data);
?>

<?php
// Если это главная страница, выводим либо плагин «Слайдер акций», либо «Конструктор слайдов»
if (URL::isSection(null)) { ?>
    <?php if (class_exists('SliderAction')) { ?>
        [slider-action]
    <?php } elseif (class_exists('Slider')) {
        // Параметр id в шорткоде плагина может отличаться
        // Скопируйте шорткод из слайдера, который вы хотите вставить
        ?>
        [mgslider id="<?php echo !empty(MG::get('templateParams')['SLIDER']['slider_id']) ? MG::get('templateParams')['SLIDER']['slider_id'] : '1' ; ?>"]
    <?php } ?>
<?php } ?>
<main class="l-main">
    <?php if(class_exists('TemporarySale')) { ?>
        <?php mgAddMeta('components/plugin-style/temporary-sale.css') ;?>
    <?php } ?>
    <div class="l-container">
        <div class="l-row">
            <?php
            if (MG::get('controller') == "controllers_catalog") {
                // Сайдбар
                // layout/layout_sidebar.php
                layout('sidebar');
            }
            ?>

            <?php
            // Главный контейнер с контентом страницы
            // layout/layout_page.php
            layout('page'); ?>
        </div>
    </div>
</main>
<?php
// Шапка сайта
// layout/layout_footer.php
layout('footer');
?>

<?php
// Плагин «Обратный звонок»
if (class_exists('BackRing')): ?>
<div class="back-ring-hidden">

    [back-ring]
</div>
<?php endif; ?>

<?php if (class_exists('ScrollTop')) { ?>
    [scroll-top]
<?php } ?>
<?php if (class_exists('InfoNotice')) { ?>
    [banner id=1]
<?php } ?>
<?php if (class_exists('AgeConfirm')) { ?>
    [mg-age-confirm]
<?php } ?>
<?php mgAddMeta('js/script.js'); ?>

<?php
// Подключение всех js-скриптов движка, плагинов, компонентов
// а также всех скриптов, подключенных через функции addScript и mgAddMeta
mgMeta('js'); ?>

    <?php
    if (MG::get('templateParams')['DESIGN']['checkbox_new_design'] === 'false') {
        mgAddMeta('css/new-style.css');
    } 
    ?>

</body>
</html>
