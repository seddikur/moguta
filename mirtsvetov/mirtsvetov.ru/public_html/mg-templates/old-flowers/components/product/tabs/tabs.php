<div class="tab-links ds flex">
    <?php if (!empty($data['description']) && $data['description'] !== '&nbsp;'): ?>
        <span class="tab-link active" data-tab="data" data-group="product"><?php echo lang('productDescription'); ?></span>
    <?php endif; ?>
  
    <?php if (!empty($data['stringsProperties']) || $data['weight']): ?>
        <span class="tab-link" data-tab="properties" data-group="product"><?php echo lang('productCharacteristics'); ?></span>
    <?php endif; ?>
    
    <?php if ($data['thisUserFields'][102]):?>
        <span class="tab-link" data-tab="video" data-group="product">Видео</span>
    <?php endif; ?>	

    <?php if (class_exists('ProductCommentsRating')): ?>
        <span class="tab-link" data-tab="comments" data-group="product">Отзывы</span>
    <?php endif; ?>
</div>
  
<?php if (!empty($data['description']) && $data['description'] !== '&nbsp;'): ?>
    <div class="tab content active" itemprop="description" data-tab="data" data-group="product">
        <?php echo $data['description'] ?>
    </div>
<?php endif; ?>

<?php if ($data['thisUserFields'][102]):?>
    <div class="tab" data-tab="video" data-group="product">
        <div class="videos">
            <?php echo $data['thisUserFields'][102]['value'];?>
        </div>
    </div>
<?php endif; ?>

  
<?php if (!empty($data['stringsProperties']) || $data['weight']): ?>
    <div class="tab" data-tab="properties" data-group="product">
        <ul class="properties">
            <?php if($data['weight']):?>
                <li class="prop-item flex space-between nowrap">
                    <span class="prop-name"><?php echo lang('productWeight1'); ?></span>
                    <span class="prop-separator"></span>
                    <span class="prop-spec"><span class="weight"><?php echo $data['weightCalc'] ?></span> <?php echo lang('weightUnit_' . $data['weightUnit']); ?></span>            
                </li>
            <?php endif;?>
            <?php component('product/string-properties', $data['stringPropertiesSorted']);?>
        </ul>
    </div>
<?php endif; ?>
  
<?php if (class_exists('ProductCommentsRating')): ?>
    <div class="tab content" data-tab="comments" data-group="product">
        [mg-product-comments-rating id="<?php echo $data['id'] ?>"]
    </div>
<?php endif; ?>