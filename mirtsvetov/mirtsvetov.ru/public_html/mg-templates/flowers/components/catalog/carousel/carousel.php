<?php if (!empty($data['items'])): ?>
    <div class="swiper">
        <div class="swiper-wrapper">
            <?php foreach ($data['items'] as $item):?>
                <div class="swiper-slide">
                    <?php 
                        $item['price'] = floatval(MG::numberDeFormat($item['price']));
                        if (!empty($data['currency'])) {
                            $item['currency'] = $data['currency'];
                        }
                        component('catalog/item', ['item' => $item]);
                    ?>
                </div>
            <?php endforeach;?>
        </div>
        <div class="swiper-button-prev"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/icons.svg#arrow"></use></svg></div>
        <div class="swiper-button-next"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/icons.svg#arrow"></use></svg></div>
    </div>
<?php endif; ?>