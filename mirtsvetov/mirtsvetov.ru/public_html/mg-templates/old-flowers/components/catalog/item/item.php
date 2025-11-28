<?php mgAddMeta('components/product/variants/variants.js'); ?>
<?php mgAddMeta('components/product/product.js'); ?>

<div class="product-wrapper js-catalog-item">
    <div class="ds top-part">
        <div class="stickers flex column">
            <?php
                if (!empty($data['item']['price']) && !empty($data['item']['old_price']) && intval( str_replace(' ', '', $data['item']['old_price'])) > intval(str_replace(' ', '', $data['item']['price']))) {
                $price = floatval(MG::numberDeFormat($data['item']['price']));
                $oldprice = floatval(MG::numberDeFormat($data['item']['old_price']));
                $calculate = ($oldprice - $price) / ($oldprice / 100);
                $result = round($calculate);
                echo '<span class="sticker sale js-discount-sticker">-' . $result . '%</span>';
            }
            if ($data['item']['new']) echo '<span class="sticker new">Новинка</span>';
            #if ($data['item']['recommend']) echo '<span class="sticker hit">Хит</span>';
            ?>
        </div>
        <a class="img" href="<?php echo $data['item']["link"] ?>">
            <picture>
                <?php $thumbsArr1 = getThumbsFromUrl($data["item"]["images_product"][0], $data['item']['id']); ?>
                <source srcset="[webp url="<?php echo $thumbsArr1[30]['2x'] ?>"] 2x">
                <img class="js-catalog-item-image" src="[webp url="<?php echo $thumbsArr1[30]['main'] ?>"]" alt="<?php echo $title;?>" data-transfer="true" data-product-id="<?php echo $data['item']['id'] ?>" loading="lazy" width="200" height="200">
            </picture>
        </a>
    </div>
    <div class="bottom-part">
        <div class="text">
            <a href="<?php echo $data['item']["link"] ?>" class="title"><?php echo $data['item']["title"] ?></a>
        </div>
    </div>
</div>