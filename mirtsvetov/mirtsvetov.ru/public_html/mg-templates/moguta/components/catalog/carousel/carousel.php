<?php mgAddMeta('lib/owlcarousel/owl.carousel.min.js'); ?>
<?php mgAddMeta('components/catalog/carousel/carousel.css'); ?>
<?php mgAddMeta('components/catalog/carousel/carousel.js'); ?>
<!-- new - start -->
<?php if (!empty($data['items'])): ?>
    <div class="l-col min-0--12">
        <div class="c-carousel c-carousel--first c-carousel--index">
            <div class="c-carousel__title">
                <?php if (isset($data['link'])) { ?>
                    <a href="<?php echo $data['link']; ?>">
                    <span class="c-carousel__title--span">
                        <?php echo $data['title']; ?>
                        <span class="c-carousel__title--more">
                            <?php echo lang('indexViewAll'); ?>
                        </span>
                    </span>
                    </a>
                <?php } else { ?>
                    <span class="c-carousel__title--span">
                        <?php echo $data['title']; ?>
                    </span>
                <?php } ?>
            </div>
            <div class="c-carousel__content <?php echo count($data['items']) > 0 ? "js-catalog-top-carousel" : "" ?>">
                <?php foreach ($data['items'] as $item) {
                    $item['price'] = floatval(MG::numberDeFormat($item['price']));
                    // Если в компонент передана валюта, то подставляем её
                    if (!empty($data['currency'])) {
                        $item['currency'] = $data['currency'];
                    }
                    // Миникарточки товара
                    component(
                        'catalog/item',
                        ['item' => $item]
                    );
                } ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<!-- new - end -->
