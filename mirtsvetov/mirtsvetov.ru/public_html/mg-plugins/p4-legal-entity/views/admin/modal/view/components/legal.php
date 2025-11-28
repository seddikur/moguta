<div class="element__inner">

    <button type="button" class="element__tab js-element-tab" 
            data-anchor="legal-<?php echo $legal['id']; ?>"></button>

    <div class="element__content">
        <div class="element__item">
            <div><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_NAME']; ?></b></div>
            <div><?php echo $legal['name']; ?></div>
        </div>

        <div class="element__item">
            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_INN']; ?></b></span>
            <span><?php echo $legal['inn']; ?></span>
        </div>

        <?php
            if (!$legal['kpp']) {
                $style = 'style = "visibility: hidden;"';
            }
        ?>
        <div class="element__item" <?php echo $style; ?>>
            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_KPP']; ?></b></span>
            <span><?php echo $legal['kpp']; ?></span>
        </div>
    </div>
    
    <?php if ($user_id): ?>
        <?php if ($legal['default']): ?>
            <div class="element__default">
                <div class="element-default element-default_active">
                    <span><?php echo $this->lang['MODAL_DEFAULT']; ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="element__default">
                <div class="element-default">
                    <button type="button" class="button-link element__button js-default-legal" 
                            data-id="<?php echo $legal['id']; ?>" 
                            data-user-id="<?php echo $user_id; ?>"><?php echo $this->lang['MODAL_SELECT_DEFAULT']; ?></button>
                </div>
            </div>
        <?php endif; ?> 
    <?php endif; ?>

    <div class="element__action">
        <div class="element-action">
            <div class="element-action__item">
                <button type="button" class="element__button js-edit-legal" 
                        data-id="<?php echo $legal['id']; ?>" 
                        data-user-id="<?php echo $user_id; ?>">
                    <i class="fa fa-pencil"></i>
                </button>
            </div>
            <div class="element-action__item">
                <button type="button" class="element__button js-delete-legal" 
                        data-id="<?php echo $legal['id']; ?>" 
                        data-user-id="<?php echo $user_id; ?>">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php if ($style) unset($style); ?>
</div>