<?php mgAddMeta('components/favorites/btns/btns.css') ?>
<?php mgAddMeta('components/favorites/btns/btns.js') ?>

<?php
if (in_array(EDITION, array('market', 'gipermarket', 'saas')) && MG::getSetting('useFavorites') == 'true') {

  $favorites = explode(',', $_COOKIE['favorites']);
  if (in_array($data['id'], $favorites)) {
    $_fav_style_add = 'display:none;';
    $_fav_style_remove = '';
  } else {
    $_fav_style_add = '';
    $_fav_style_remove = 'display:none;';
  }
  ?>

    <a role="button"
       href="javascript:void(0);"
       data-item-id="<?php echo $data['id']; ?>"
       class="mg-remove-to-favorites js-remove-to-favorites <?php if (MG::get('controller') == "controllers_product"): ?>mg-remove-to-favorites--product<?php endif; ?>"
       style="<?php echo $_fav_style_remove ?>" aria-label="<?php echo lang('inFav'); ?>">
       <svg width="26px" height="25px" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg" icon="new_heart"><path d="M5.385 0C7.435 0 9.316 2.118 10 2.703 10.684 2.118 12.564 0 14.615 0 17.18 0 20 2.759 20 5.266c0 2.508-.77 6.465-10 12.734C.77 11.73 0 7.774 0 5.266 0 2.76 2.82 0 5.385 0z" fill="#df3f3f" ></path></svg>
        <span class="remove__text"><?php echo lang('inFav') ?></span>
        <span class="remove__hover"><?php echo lang('delFav') ?></span>
    </a>

    <a role="button"
       href="javascript:void(0);"
       data-item-id="<?php echo $data['id']; ?>"
       class="mg-add-to-favorites js-add-to-favorites <?php if (MG::get('controller') == "controllers_product"): ?>mg-add-to-favorites--product<?php endif; ?>"
       style="<?php echo $_fav_style_add ?>" aria-label="<?php echo lang('toFav'); ?>">
       <svg width="26px" height="25px" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg" icon="new_heart"><path d="M5.385 0C7.435 0 9.316 2.118 10 2.703 10.684 2.118 12.564 0 14.615 0 17.18 0 20 2.759 20 5.266c0 2.508-.77 6.465-10 12.734C.77 11.73 0 7.774 0 5.266 0 2.76 2.82 0 5.385 0z" fill="var(--icons-color, #AAADB2)"></path></svg>
      <span class="add-favorite__text"> 
        <?php echo lang('toFav') ?>
      </span>
    </a>

<?php } ?>