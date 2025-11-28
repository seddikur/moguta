<?php
   $hidden = '';
   if (class_exists('chdRequestPrice') && $data['price'] == 0) {
         $hidden = 'style="display: none;"';
   }
?>
<a href="<?php echo SITE . '/catalog?inCartProductId=' . $data["id"]; ?>" rel="nofollow" <?php echo $hidden ?> class="addToCart product-buy " data-item-id="<?php echo $data["id"]; ?>">
   <?php echo lang('buttonBuy'); ?>
</a>
<?php if (!empty($hidden)) : ?>
   <a class="a-button" href="<?php echo $data['link'] ?>">
      [chd-catalog-button-request-price]
   </a>
<?php endif; ?>
<?php if (!empty($data['variant_exist'])) : ?>
    <a style="display:none"
       href="<?php echo SITE . '/' . ((MG::getSetting('shortLink') != 'true') && ($data["category_url"] == '') ? 'catalog/' : $data["category_url"]) . $data["product_url"]; ?>"
       class="product-info action_buy_variant"><?php echo lang('buttonMore'); ?></a>
<?php endif; ?>
