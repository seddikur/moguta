<?php mgAddMeta('components/favorites/informer/informer.css'); ?>

<?php
if (in_array(EDITION, array('market', 'gipermarket', 'saas')) && MG::getSetting('useFavorites') == 'true') {
    $showFavorite = $_COOKIE['favorites'] ? 'favourite--open' : '';
    ?>
    <a href="<?php echo SITE ?>/favorites"
       class="js-favorites-informer favourite <?php echo $showFavorite ?>">
        <span class="favourite__text">
        <svg width="26px" height="25px" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg" icon="new_heart"><path d="M5.385 0C7.435 0 9.316 2.118 10 2.703 10.684 2.118 12.564 0 14.615 0 17.18 0 20 2.759 20 5.266c0 2.508-.77 6.465-10 12.734C.77 11.73 0 7.774 0 5.266 0 2.76 2.82 0 5.385 0z" fill="var(--icons-color, #AAADB2)" ></path></svg>
            <span class="favourite__text-container">
                <?php echo lang('favoriteTitle') ?>
            </span>
            <span class="favourite__count js-favourite-count" <?php echo $_COOKIE['favorites'] == '' ? 'style="display: none;"' : '' ?>>
                <?php 
                if ($_COOKIE['favorites'] == '') {
                    echo 0;
                } else {
                    echo substr_count($_COOKIE['favorites'], ',') + 1;
                } ?>
            </span>
        </span>
    </a>
<?php } ?>