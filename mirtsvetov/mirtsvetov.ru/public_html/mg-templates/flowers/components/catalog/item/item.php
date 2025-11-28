<div class="product-wrapper js-catalog-item <?php echo ($data['item']['count']=="0")?'out-of-stock':'';?>">
    <div class="ds top-part">
        <div class="stickers flex column">
            <?php
                if($data['item']['thisUserFields'][9] && User::isAuth()) echo '<span class="flex align-center center sticker sale"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M374.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-320 320c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l320-320zM128 128A64 64 0 1 0 0 128a64 64 0 1 0 128 0zM384 384a64 64 0 1 0 -128 0 64 64 0 1 0 128 0z"/></svg></span>';
                if ($data['item']['new']) echo '<span class="flex align-center center sticker new"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M349.4 44.6c5.9-13.7 1.5-29.7-10.6-38.5s-28.6-8-39.9 1.8l-256 224c-10 8.8-13.6 22.9-8.9 35.3S50.7 288 64 288l111.5 0L98.6 467.4c-5.9 13.7-1.5 29.7 10.6 38.5s28.6 8 39.9-1.8l256-224c10-8.8 13.6-22.9 8.9-35.3s-16.6-20.7-30-20.7l-111.5 0L349.4 44.6z"/></svg></span>';
            
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
        <?php if(User::isAuth()) {
                component(
                    'catalog/item/variants', 
                    [
                        'unit' => $data['item']['product_unit'] ?: 'шт.',
                        'currency' => $data['item']['currency'],
                        'inCart' => $data['inCart'],
                        'variants' => $data['item']['variants']
                    ]
                ); 
        } ?>
    </div>
</div>