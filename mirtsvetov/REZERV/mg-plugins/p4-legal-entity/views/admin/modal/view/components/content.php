<?php if ($data['legal']): ?>
    <div class="legals__section">  

        <?php if ($page === 'users'): ?>
            <div class="legals__title"><?php echo $this->lang['MODAL_LEGAL_ENTITIES']; ?></div>
            <div class="legals__add">
                <button type="button" class="button success js-add-legal" 
                        data-user-id="<?php echo $user_id; ?>"><?php echo $this->lang['MODAL_ADD_LEGAL_ENTITY']; ?></button>
            </div>
        <?php elseif ($page === 'legalEntities'): ?>
            <div class="legals__title"><?php echo $this->lang['MODAL_LEGAL_ENTITY']; ?></div>
        <?php endif; ?>

        <div class="legals__content">
            <?php foreach ($data['legal'] AS $legal): ?>
                <?php
                    if ($page === 'users' && $legal['default']) {
                        $is_active = ' element_active';
                    } else if ($page === 'legalEntities') {
                        $is_active = ' element_active';
                    }
                ?>
                <div class="element js-legal-item<?php echo $is_active; ?>">
                    <?php include(LegalEntity::$path . '/views/admin/modal/view/components/legal.php'); ?>
                </div>
                <?php if ($is_active) unset($is_active); ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="legals__section">
        <?php foreach ($data['debt'] as $key => $debt): ?>
            <?php
                if ($page === 'users' && $data['legal'][$key]['default']) {
                    $is_active = ' dropdown_active';
                } else if ($page === 'legalEntities') {
                    $is_active = ' dropdown_active';
                }
            ?>
            <div class="dropdown<?php echo $is_active; ?>" dropdown-group="legal-<?php echo $key; ?>">
                <div class="legals__title"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT']; ?></div>
                <div class="legals__content js-debt-content">
                    <?php if ($debt): ?>
                        <div class="element js-debt-item">
                            <?php include(LegalEntity::$path . '/views/admin/modal/view/components/debt.php'); ?>
                        </div>
                    <?php else: ?>
                        <div class="legals__empty" style="padding: 15px;">
                            <?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_EMPTY']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($is_active) unset($is_active); ?>
        <?php endforeach; ?>
    </div>

    <div class="legals__section">
        <?php foreach ($data['address'] as $key => $addresses): ?>
            <?php
                if ($page === 'users' && $data['legal'][$key]['default']) {
                    $is_active = ' dropdown_active';
                } else if ($page === 'legalEntities') {
                    $is_active = ' dropdown_active';
                }
            ?>
            <div class="dropdown<?php echo $is_active; ?>" dropdown-group="legal-<?php echo $key; ?>">
                <div class="legals__title"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DELIVERY_ADDRESSES']; ?></div>
                <div class="legals__add">
                    <button type="button" class="button success js-add-address" 
                            data-legal-id="<?php echo $key; ?>" 
                            data-user-id="<?php echo $user_id; ?>"><?php echo $this->lang['MODAL_LEGAL_ENTITY_ADD_ADDRESS']; ?></button>
                </div>
                <div class="legals__content js-address-content">
                    <?php foreach ($addresses as $address): ?> 
                        <div class="element js-address-item">
                            <?php include(LegalEntity::$path . '/views/admin/modal/view/components/address.php'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($is_active) unset($is_active); ?>
        <?php endforeach; ?>
    </div>

<?php else: ?>

    <div class="legals__section">
        <div class="legal">
            <div class="legals__title"><?php echo $this->lang['MODAL_LEGAL_ENTITIES']; ?></div>
            <div class="legals__add">
                <button type="button" class="button success js-add-legal" 
                        data-user-id="<?php echo $user_id; ?>"><?php echo $this->lang['MODAL_ADD_LEGAL_ENTITY']; ?></button>
            </div>
        </div>
    </div>

<?php endif; ?>