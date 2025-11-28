<div class="p-modal p-modal_min">
	<div class="p-modal__container p-modal__container_min">
        <button class="p-modal-close js-modal-close" type="button">
            <i class="fa fa-times-circle-o"></i>
        </button>
        <div class="p-modal-body">
            <div class="p-modal-body__container">
                <div class="p-modal-body__inner p-modal-body__inner_min p-modal-body__column">
                    
                    <div class="p-modal__item">
                        <div class="p-modal__title"><?php echo $title; ?></div>
                        <div class="p-modal__content">
                            <div><?php echo $message; ?></div>
                        </div>
                    </div>

                    <div class="p-modal__item p-modal__action">
                        <button type="button" class="button-link js-modal-cancel">
                            <?php echo $this->lang['MODAL_CLOSE']; ?>
                        </button>
                    </div>

				</div>
			</div>
		</div>
	</div>
</div>