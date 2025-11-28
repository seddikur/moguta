<div class="legals__title"><?php echo $this->lang['MODAL_USER']; ?></div>
<?php if ($user): ?>
    <div class="info__item">
        <span><b><?php echo $this->lang['MODAL_USER_ID']; ?></b> <?php echo $user['id']; ?></span>
    </div>
    <div class="info__item">
        <span><b><?php echo $this->lang['MODAL_USER_LOGIN_EMAIL']; ?></b> <?php echo $user['login_email']; ?></span>
    </div>
    <div class="info__item">
        <?php 
            $sname = $user['sname'] ? $user['sname'] . ' ' : '';
            $pname = $user['pname'] ? ' ' . $user['pname'] : '';
            $initials = $sname . $user['name'] . $pname;
        ?>
        <span><b><?php echo $this->lang['MODAL_USER_INITIALS']; ?></b> <?php echo $initials; ?></span>
    </div>

    <?php if ($page === 'legalEntities'): ?>
        <div class="info__button">
            <button type="button" class="button success js-user-bind" 
                    data-legal-id="<?php echo $id; ?>" 
                    data-user-id="<?php echo $user['id']; ?>">Изменить пользователя</button>
        </div>
    <?php endif; ?>
    
<?php else: ?>

    <?php if ($page === 'legalEntities'): ?>
        <div class="info__button">
            <button type="button" class="button success js-user-bind" 
                data-legal-id="<?php echo $id; ?>">Привязать пользователя</button>
        </div>
    <?php endif; ?>
<?php endif; ?>