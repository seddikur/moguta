<div class="p-modal">
	<div class="p-modal__container p-modal__container_big">
        <button class="p-modal-close js-modal-close" type="button">
            <i class="fa fa-times-circle-o"></i>
        </button>
        <div class="p-modal-body">
            <div class="p-modal-body__container">
                <div class="p-modal-body__inner p-modal-body__column">
                    <form class="p-modal-form">

                        <?php if ($legal): ?>
                            <div class="p-modal__item info">
                                <div class="p-modal__title"><?php echo $this->lang['MODAL_LEGAL_ENTITY']; ?></div>
                                <div class="p-modal__content">
                                    <div class="info__item">
                                        <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_ID']; ?></b> <?php echo $legal['id']; ?></span>
                                    </div>
                                    <div class="info__item">
                                        <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_NAME']; ?></b> <?php echo $legal['name']; ?></span>
                                    </div>
                                    <div class="info__item">
                                        <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_INN']; ?></b> <?php echo $legal['inn']; ?></span>
                                    </div>
                                    <?php if ($legal['kpp']): ?>
                                        <div class="info__item">
                                            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_KPP']; ?></b> <?php echo $legal['kpp']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($user): ?>
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
                        <?php endif; ?>

                        <div class="p-modal__item">
                            <div class="p-modal__title"><?php echo $this->lang['MODAL_USER_BINDING']; ?></div>
                            <div class="p-modal__content">
                                <div class="user">
                                    <input type="search" autocomplete="off" name="search_user" placeholder="<?php echo $this->lang['MODAL_USER_SEARCH_PLACEHOLDER']; ?>">
                                    <div class="user__search-result js-user-search-result" style="display: none;"></div>
                                    <div class="user__container js-user-container">
                                        <div class="user__empty"><?php echo $this->lang['MODAL_USER_EMPTY']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-modal__item p-modal__action">
                            <button type="button" class="button-link js-modal-cancel">
                                <?php echo $this->lang['MODAL_CANCEL']; ?>
                            </button>
                            <button type="submit" class="button success js-save-user-bind" 
                                    data-legal-id="<?php echo $legal_id; ?>">
                                <?php echo $this->lang['MODAL_SAVE']; ?>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
	</div>
</div>