<span class="count">
        <?php if ((string)$data['count'] == '0') : ?>
            <span class="c-product__stock c-product__stock--out">
                <?php echo lang('countOutOfStock'); ?>
            </span>
        <?php elseif ((float)$data['count'] > 0): ?>
            <span class="c-product__stock c-product__stock--in">
                <?php echo lang('countInStock'); ?>:
                <span class="c-product__stock--span label-black count">
                    <?php echo(!empty($data['count_hr']) ? $data['count_hr'] : $data['count'] . ' ' . $data['category_unit']); ?>
                </span>
            </span>
        <?php else : ?>
            <span class="c-product__stock c-product__stock--in count">
                <?php echo lang('countInStock'); ?>
            </span>
        <?php endif; ?>
    </span>
