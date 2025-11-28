<?php
if (in_array(EDITION, array('market', 'gipermarket', 'saas')) && MG::getSetting('useFavorites') == 'true') {
    $showFavorite = $_COOKIE['favorites'] ? 'favourite--open' : '';
    ?>
    <a href="<?php echo SITE ?>/favorites" class="js-favorites-informer informer favorites-informer flex align-center <?php echo $showFavorite ?>">
        <div class="img"></div>
        <div class="text"><div class="title"></div>добавлен в избранное</div>
    </a>
<?php } ?>