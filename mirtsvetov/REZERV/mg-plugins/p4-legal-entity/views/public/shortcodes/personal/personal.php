<div class="legals">
    <?php if ($data['legal']): ?>
        <div class="legals__section">
            <div class="h3-like">Юридические лица</div>
            <div class="legals__content">
                <?php foreach ($data['legal'] AS $legal): ?>
                    <?php
                        if ($legal['default']) {
                            $is_active = ' element_active';
                        }
                    ?>
                    <div class="element<?php echo $is_active; ?>">
                        <?php include(self::$path . '/views/public/shortcodes/personal/components/legal.php'); ?> 
                    </div>
                    <?php if ($is_active) unset($is_active); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="legals__section">
            <?php foreach ($data['address'] as $key => $addresses): ?> 
                <?php
                    if ($data['legal'][$key]['default']) {
                        $is_active = ' dropdown_active';
                    }
                ?>
                <div class="dropdown<?php echo $is_active; ?>" dropdown-group="legal-<?php echo $key; ?>">
                    <div class="h3-like">Адреса доставок</div>
                    <?php if ($addresses): ?>
                        <div class="legals__content">
                            <?php foreach ($addresses as $address): ?>
                                <div class="element">
                                    <?php include(self::$path . '/views/public/shortcodes/personal/components/address.php'); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="legals__empty">Нет доступного адреса доставки, пожалуйста, обратитесь к менеджеру.</div>
                    <?php endif; ?>
                </div>
                <?php if ($is_active) unset($is_active); ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="legals__empty">Нет доступного юридического лица, пожалуйста, обратитесь к менеджеру.</div>
    <?php endif; ?>
</div>