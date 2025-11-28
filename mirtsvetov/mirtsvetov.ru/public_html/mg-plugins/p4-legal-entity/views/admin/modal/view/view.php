<div class="p-modal js-modal-view">
	<div class="p-modal__container p-modal__container_big">
        <button class="p-modal-close js-modal-close" type="button">
            <i class="fa fa-times-circle-o" aria-hidden="true"></i>
        </button>
        <div class="p-modal-body">
            <div class="p-modal-body__container">
                <div class="p-modal-body__inner">
                    <div class="legals">
                        <div class="legals__section info js-info-user-content">
                            <?php include(PLUGIN_DIR . 'p4-legal-entity' . '/views/admin/modal/view/components/user.php'); ?>
                        </div>
                        <div class="legals__section js-legals-content">
                            <?php include(PLUGIN_DIR . 'p4-legal-entity' . '/views/admin/modal/view/components/content.php'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>