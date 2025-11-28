<?php mgSEO($data);?>
<div class="max">
    <h1><?php echo $data['titleCategory'] ?></h1>
    <?php if (!empty($data['descCategory'])): ?>
      <div class="top-description content">
          <?php echo $data['descCategory'] ?>
      </div>
    <?php endif; ?>
    <div class="products-wrapper js-products-container catalog">
        <div class="grid by-4">
            <?php foreach ($data['items'] as $item) { ?>
                <?php component('catalog/item', ['item' => $item]); ?>
            <?php } ?>
        </div>
    </div>
    <?php if (!empty($data['pager'])): ?>
        <?php component('pagination', $data['pager']); ?>
    <?php endif; ?>
</div>