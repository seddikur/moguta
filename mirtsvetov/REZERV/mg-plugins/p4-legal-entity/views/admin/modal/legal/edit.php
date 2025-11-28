<div class="p-modal">
	<div class="p-modal__container p-modal__container_big">
        <button class="p-modal-close js-modal-close" type="button">
            <i class="fa fa-times-circle-o"></i>
        </button>
        <div class="p-modal-body">
            <div class="p-modal-body__container">
                <div class="p-modal-body__inner p-modal-body__column">
                    <form class="p-modal-form">

                        <div class="p-modal__item info">
                            <div class="p-modal__title"><?php echo $this->lang['MODAL_USER']; ?></div>
                            <div class="p-modal__content">
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
                            </div>
                        </div>

                        <div class="p-modal__item">
                            <div class="p-modal__title"><?php echo $this->lang['MODAL_LEGAL_ENTITY']; ?></div>
                            <div class="p-modal__content">
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_NAME']; ?></div>
                                    <input type="text" class="p-modal-form__input" name="legal_name" autocomplete="off" value="<?php echo $data['name']; ?>">
                                </div>
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_INN']; ?></div>
                                    <input type="text" class="p-modal-form__input" 
                                            name="legal_inn" 
                                            autocomplete="off" 
                                            maxlength="12" 
                                            value="<?php echo $data['inn']; ?>">
                                </div>
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_KPP']; ?></div>
                                    <input type="text" class="p-modal-form__input" 
                                            name="legal_kpp" 
                                            autocomplete="off" 
                                            maxlength="9"
                                            value="<?php echo $data['kpp']; ?>">
                                </div>
                            </div>
                            <input type="hidden" name="legal_default" value="<?php echo $data['default']; ?>">
                        </div>
                        
                        <div class="p-modal__item p-modal__action">
                            <button type="button" class="button-link js-modal-cancel">
                                <?php echo $this->lang['MODAL_CANCEL']; ?>
                            </button>
                            <button type="submit" class="button success js-save-legal" 
                                    data-id="<?php echo $data['id']; ?>"
                                    data-user-id="<?php echo $user_id; ?>">
                                <?php echo $this->lang['MODAL_SAVE']; ?>
                            </button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
	</div>
</div>