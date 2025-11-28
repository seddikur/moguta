<div class="element__inner element__inner__colum">

    <div class="element__content">
        <div class="element__item">
            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_MIR']; ?></b></span>
            <span><?php echo MG::numberFormat($debt['mir']) . ' ' . MG::getSetting('currency'); ?></span>
        </div>
        <div class="element__item">
            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_RM']; ?></b></span>
            <span><?php echo MG::numberFormat($debt['rm']) . ' ' . MG::getSetting('currency'); ?></span>
        </div>
        <div class="element__item">
            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_TK']; ?></b></span>
            <span><?php echo MG::numberFormat($debt['tk']) . ' ' . MG::getSetting('currency'); ?></span>
        </div>
        <div class="element__item">
            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_TARE_TANK']; ?></b></span>
            <span><?php echo MG::numberFormat($debt['tare_tank']); ?></span>
        </div>
        <div class="element__item">
            <span><b><?php echo $this->lang['MODAL_LEGAL_ENTITY_DEBT_TARE_COVER']; ?></b></span>
            <span><?php echo MG::numberFormat($debt['tare_cover']); ?></span>
        </div>
    </div>

    <div class="element__action">
        <div class="element-action">
            <div class="element-action__item">
                <button type="button" class="element__button js-edit-debt" 
                        data-id="<?php echo $debt['id']; ?>"
                        data-legal-id="<?php echo $debt['legal_id']; ?>">
                    <i class="fa fa-pencil"></i>
                </button>
            </div>
        </div>
    </div>

</div>