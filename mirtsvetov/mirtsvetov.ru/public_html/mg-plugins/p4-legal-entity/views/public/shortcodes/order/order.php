<div class="legal__content">
    <?php if (!empty($data['legal'])): ?>
        <div class="legal__section">
            
            <div class="h3-like">Юридическое лицо</div>
            <div class="legal__content">
                <?php include(LegalEntity::$path . '/views/public/shortcodes/order/components/legal.php'); ?>
            </div>

        </div>

        <div class="legal__section">

            <div class="h3-like">Адрес доставки</div>
            <div class="legal__content">
                <?php include(LegalEntity::$path . '/views/public/shortcodes/order/components/address.php'); ?> 
            </div>

        </div>

    <?php else: ?>

        <div class="legal__empty">Нет доступного юридического лица, пожалуйста, обратитесь к менеджеру.</div>

    <?php endif; ?>
</div>