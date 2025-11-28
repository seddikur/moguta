<div class="element__inner element__inner__colum">
    <?php $legal_id = $address['legal_id']; ?>
    <div class="element__content">
        <div class="element__item">
            <?php echo $address['address']; ?>
        </div>
    </div>
    <?php if ($data['legal'][$legal_id]['default']): ?>
        <?php if ($address['default']): ?>
            <div class="element__default">
                <div class="element-default element-default_active">
                    <span>По умолчанию</span>
                </div>
            </div>
        <?php else: ?>
            <div class="element__default">
                <div class="element-default element-default_active">
                    <button type="button" class="button-link element__button js-default-address" 
                            data-id="<?php echo $address['id']; ?>" 
                            data-legal-id="<?php echo $address['legal_id']; ?>">Выбрать по умолчанию</button>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>