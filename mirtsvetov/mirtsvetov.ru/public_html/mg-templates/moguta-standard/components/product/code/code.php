<?php mgAddMeta('components/product/code/code.css'); ?>

<div class="c-product__code product-code">
  <span>
      <?php echo lang('productCode'); ?>
      <span class="c-product__code--span label-article code"
            itemprop="productID sku">
          <?php echo $data ?>
      </span>
  </span>
</div>