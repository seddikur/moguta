<?php mgAddMeta('components/favorites/btns/btns.js') ?>
<?php if (in_array(EDITION, array('market', 'gipermarket', 'saas')) && MG::getSetting('useFavorites') == 'true') {
  $favorites = explode(',', $_COOKIE['favorites']);
  if (in_array($data['id'], $favorites)) {
    $_fav_style_add = 'display:none;';
    $_fav_style_remove = '';
  } else {
    $_fav_style_add = '';
    $_fav_style_remove = 'display:none;';
  }
?>

    <span data-item-id="<?php echo $data['id']; ?>" class="flex center align-center unlike js-remove-to-favorites" style="<?php echo $_fav_style_remove ?>"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/svg/icons.svg#unlike"></use></svg></span>
    <span data-item-id="<?php echo $data['id']; ?>" class="flex center align-center like js-add-to-favorites" style="<?php echo $_fav_style_add ?>"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/svg/icons.svg#like"></use></svg></span>
<?php } ?>