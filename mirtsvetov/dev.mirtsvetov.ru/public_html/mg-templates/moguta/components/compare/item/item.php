<?php mgAddMeta('components/compare/item/item.css'); ?>

<div class="compare-item">
    <div class="compare-item__buttons">
        <a class="comare-item__remove js-compare-item-remove" href="<?php echo SITE ?>/compare?delCompareProductId=<?php echo $data['id'] ?>">
            <svg>
                <use xlink:href="#icon--close"></use>
            </svg>
        </a>
    </div>
    <a class="compare-item__image" href="<?php echo $data['link'] ?>" aria-label="<?php echo $data['title']; ?>">
        <?php echo mgImageProduct($data); ?>
    </a>
    <div class="compare-item__price-container">
        <span class="compare-item__price"><?php echo $data['price'] . $data['currency']; ?></span>
    </div>
    <a class="compare-item__title" href="<?php echo $data['link'] ?>"><?php echo $data['title']; ?></a>
</div>