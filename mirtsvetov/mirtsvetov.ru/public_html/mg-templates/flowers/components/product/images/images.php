<?php 
    mgAddMeta('css/jquery.fancybox.min.css');
    mgAddMeta('js/jquery.fancybox.min.js');
?>
<div class="sticky">
    <div class="ds stickers flex column">
        <?php
          if (!empty($data['price']) && !empty($data['old_price']) && intval( str_replace(' ', '', $data['old_price'])) > intval(str_replace(' ', '', $data['price']))) {
              $price = floatval(MG::numberDeFormat($data['price']));
              $oldprice = floatval(MG::numberDeFormat($data['old_price']));
              $calculate = ($oldprice - $price) / ($oldprice / 100);
              $result = "" . round($calculate) . " %";
              echo '<div class="sale js-discount-sticker">-' . $result . ' </div>';
          }
          if ($data['new']) echo '<div class="new">'.lang('stickerNew').'</div>';
          if ($data['recommend']) echo '<div class="hit">'.lang('stickerHit').'</div>';
          ?>
    </div>
    <div class="icon-block flex column align-center">
        <?php component('favorites/btns', $data);?>
        <?php component('compare/btn/add', $data);?>
    </div>
    <div class="product-slider">
        <div class="swiper">
            <div class="swiper-wrapper">
                <?php foreach ($data["images_product"] as $key => $image) {$thumbsArr = getThumbsFromUrl($image, $data['id']);?>
                    <div class="swiper-slide js-images-slide">
                        <a class="fancy-modal js-images-link" href="<?php echo $thumbsArr['orig']; ?>" data-fancybox="mainProduct" data-url="<?php echo $thumbsArr['orig']; ?>">
                            <picture>
                                <img class="js-product-img" loading="lazy" width="674" height="398" itemprop="image" alt="<?php echo ($data['image_alt']) ? $data['image_alt'] : $data['title']; ?>" src="<?php echo $thumbsArr[70]['main'] ?>" srcset="<?php echo $thumbsArr[70]['2x'] ?> 2x">
                                <div class="swiper-lazy-preloader"></div>
                            </picture>
                        </a>
                    </div>
                <?php } ?>
            </div>
            <div class="swiper-pagination mobile-show"></div>
            <div class="swiper-button-prev mobile-hide"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/icons.svg#arrow"></use></svg></div>
            <div class="swiper-button-next mobile-hide"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/icons.svg#arrow"></use></svg></div>
        </div>
    </div>
    <div class="product-thumbs-slider__wrapper">
        <div class="product-thumbs-slider mobile-hide">
            <div class="swiper">
                <div class="swiper-wrapper">            
                    <?php foreach ($data["images_product"] as $key => $image) {$thumbsArr = getThumbsFromUrl($image, $data['id']);?>
                        <div class="swiper-slide" data-slide-index="<?php echo $key ?>">
                            <img class="js-img-preview" width="180" height="100" src="<?php echo $thumbsArr['30']['main'] ?>" srcset="<?php echo $thumbsArr['30']['2x'] ?> 2x" alt="<?php echo !empty($data["images_alt"][$key]) ? $data["images_alt"][$key] : $data["title"]; ?>">
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>