<div class="p-modal p-modal_min">
	<div class="p-modal__container p-modal__container_min">
        <button class="p-modal-close js-modal-close" type="button">
            <i class="fa fa-times-circle-o"></i>
        </button>
        <div class="p-modal-body">
            <div class="p-modal-body__container">
                <div class="p-modal-body__inner p-modal-body__inner_min p-modal-body__column">
					<form class="p-modal-form">

						<div class="p-modal__item">
							<div class="p-modal__title"><?php echo $this->lang['MODAL_DELETE_LEGAL_ENTITY']; ?></div>
                            <div class="p-modal__content">
								<div><?php echo $this->lang['MODAL_DELETE_LEGAL_ENTITY_TEXT_1']; ?> <b><?php echo $data['name']; ?></b> <?php echo $this->lang['MODAL_DELETE_LEGAL_ENTITY_TEXT_2']; ?></div>
							</div>
						</div>

						<div class="p-modal__item p-modal__action">
                            <button type="button" class="button-link js-modal-cancel">
								<?php echo $this->lang['MODAL_CANCEL']; ?>
							</button>
							<button type="button" class="button success js-confirm-delete-legal" 
									data-id="<?php echo $id ?>" 
									data-user-id="<?php echo $user_id; ?>">
								<?php echo $this->lang['MODAL_DELETE']; ?>
							</button>
						</div>

					</form>
				</div>
			</div>
		</div>
	</div>
</div>