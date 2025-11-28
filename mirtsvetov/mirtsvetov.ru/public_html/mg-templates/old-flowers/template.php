<?php /*
Template Name: Belka
Author: Belka.one
Version: 2.0.0
*/ ?>

<!DOCTYPE html>
<html <?php getHtmlAttributes() ?>>
    <head>
        <?php mgMeta("meta", "css", "jquery");?>
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="icon" href="/favicon.svg" type="image/x-icon">
        <link rel="preload" href="<?php echo getMainStyleLink(); ?>" as="style">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400&display=swap" rel="stylesheet">
    </head>
    
    <body class="loading <?php MG::addBodyClass(); ?>">
        <?php layout('header', $data);?>
        <main>
            <?php layout('page'); ?>
        </main>
        
        <?php layout('footer', $data);?>
        <?php mgMeta('js'); ?>
        <?php mgAddMeta('js/script.js'); ?>
        <?php layout('widget'); ?>
    </body>
</html>