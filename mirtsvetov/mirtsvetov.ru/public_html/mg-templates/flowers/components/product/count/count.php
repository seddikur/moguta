<span class="count">
    <?php if ($data['count'] === 0 || $data['count'] === '0') : ?>
        <?php echo lang('countOutOfStock'); ?>
    <?php elseif ((float)$data['count'] > 0): ?>
            <?php echo lang('countInStock'); ?>:
            <?php echo(!empty($data['count_hr']) ? $data['count_hr'] : $data['count'] . ' ' . $data['category_unit']); ?>
    <?php else : ?>
            В наличии
    <?php endif; ?>
</span>