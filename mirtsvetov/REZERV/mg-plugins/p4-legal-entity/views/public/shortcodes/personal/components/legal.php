<div class="element__inner">
    <button type="button" class="element__tab js-element-tab" 
            data-anchor="legal-<?php echo $legal['id']; ?>"></button>
    <div class="element__content">
        <div class="element__item">
            <div><b>Наименование:</b></div>
            <div><?php echo $legal['name']; ?></div>
        </div>
        <div class="element__item">
            <span><b>ИНН:</b></span>
            <span><?php echo $legal['inn']; ?></span>
        </div>
        <?php
            if (!$legal['kpp']) {
                $style = 'style = "visibility: hidden;"';
            }
        ?>
        <div class="element__item" <?php echo $style; ?>>
            <span><b>КПП:</b></span>
            <span><?php echo $legal['kpp']; ?></span>
        </div>
    </div>
    <?php if ($legal['default']): ?>
        <div class="element__default">
            <div class="element-default element-default_active">
                <span>По умолчанию</span>
            </div>
        </div>
    <?php else: ?>
        <div class="element__default">
            <div class="element-default">
                <button type="button" class="button-link element__button js-default-legal" 
                        data-id="<?php echo $legal['id']; ?>">Выбрать по умолчанию</button>
            </div>
        </div>
    <?php endif; ?> 
    <?php if ($style) unset($style); ?>
</div>