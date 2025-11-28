<div class="element__inner element__inner__colum">

    <?php $legal_id = $address['legal_id']; ?>

    <div class="element__content">
        <div class="element__item">
            <?php echo $address['address']; ?>
        </div>
    </div>

    <?php if ($user_id): ?>
        <?php if ($data['legal'][$legal_id]['default']): ?>
            <?php if ($address['default']): ?>
                <div class="element__default">
                    <div class="element-default element-default_active">
                        <span><?php echo $this->lang['MODAL_DEFAULT']; ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="element__default">
                    <div class="element-default element-default_active">
                        <button type="button" class="button-link element__button js-default-address" 
                                data-id="<?php echo $address['id']; ?>" 
                                data-legal-id="<?php echo $address['legal_id']; ?>" 
                                data-user-id="<?php echo $user_id; ?>"><?php echo $this->lang['MODAL_SELECT_DEFAULT']; ?></button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="element__action">
        <div class="element-action">
            <div class="element-action__item">
                <button type="button" class="element__button js-edit-address" 
                        data-id="<?php echo $address['id']; ?>"
                        data-legal-id="<?php echo $address['legal_id']; ?>"
                        data-user-id="<?php echo $user_id; ?>">
                    <i class="fa fa-pencil"></i>
                </button>
            </div>
            <div class="element-action__item">
                <button type="button" class="element__button js-delete-address" 
                    data-id="<?php echo $address['id']; ?>"
                    data-legal-id="<?php echo $address['legal_id']; ?>"
                    data-user-id="<?php echo $user_id; ?>">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </div>

</div>