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
                            <div class="p-modal__title"><?php echo $this->lang['MODAL_LEGAL_ENTITY']; ?></div>
                            <div class="p-modal__content">
                                <div class="info__item">
                                    <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_ID']; ?></b> <?php echo $legalEntity['id']; ?></span>
                                </div>
                                <div class="info__item">
                                    <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_NAME']; ?></b> <?php echo $legalEntity['name']; ?></span>
                                </div>
                                <div class="info__item">
                                    <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_INN']; ?></b> <?php echo $legalEntity['inn']; ?></span>
                                </div>
                                <?php if ($legalEntity['kpp']): ?>
                                    <div class="info__item">
                                        <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_KPP']; ?></b> <?php echo $legalEntity['kpp']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-modal__item">
                            <div class="p-modal__title"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT']; ?></div>
                            <div class="p-modal__content">
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_MIR']; ?></div>
                                    <input type="number" class="p-modal-form__input" name="legal_mir" autocomplete="off" value="<?php echo $debt['mir']; ?>">
                                </div>
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_RM']; ?></div>
                                    <input type="number" class="p-modal-form__input" name="legal_rm" autocomplete="off" value="<?php echo $debt['rm']; ?>">
                                </div>
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_TK']; ?></div>
                                    <input type="number" class="p-modal-form__input" name="legal_tk" autocomplete="off" value="<?php echo $debt['tk']; ?>">
                                </div>
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_TARE_TANK']; ?></div>
                                    <input type="number" class="p-modal-form__input" name="legal_tare_tank" autocomplete="off" value="<?php echo $debt['tare_tank']; ?>">
                                </div>
                                <div class="p-modal-form__field">
                                    <div class="p-modal-form__lable"><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_TARE_COVER']; ?></div>
                                    <input type="number" class="p-modal-form__input" name="legal_tare_cover" autocomplete="off" value="<?php echo $debt['tare_cover']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="p-modal__item p-modal__action">
                            <button type="button" class="button-link js-modal-cancel">
                                <?php echo $this->lang['MODAL_CANCEL']; ?>
                            </button>
                            <button type="submit" class="button success js-save-debt" 
                                    data-id="<?php echo $debt['id']; ?>"
                                    data-legal-id="<?php echo $legalEntity['id']; ?>">
                                <?php echo $this->lang['MODAL_SAVE']; ?>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
	</div>
</div>