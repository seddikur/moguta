<div class="p-modal p-modal-change js-modal-change">
	<div class="p-modal__container p-modal__container_big">
        <div class="p-modal__inner p-modal__inner_big">
            <button class="p-modal-close js-modal-close" type="button">Закрыть</button>
            <div class="p-modal-body">
                <div class="p-modal-body__container">
                    <div class="p-modal-body__inner p-modal-body__column">
                        <div class="p-modal__item">
                            <div class="p-modal__title">Юридические лица</div>
                            <div class="p-modal__content p-modal__content_element">
                                <?php 
                                    $isSession = false;
                                    if ($_SESSION['LegalEntity']['id']) {
                                        $isSession = true;
                                        $session_id = $_SESSION['LegalEntity']['id'];
                                    } else {
                                        $disabled = true;
                                    }
                                ?>
                                <?php foreach ($data as $legal): ?>
                                    <?php 
                                        if ($session_id ===  $legal['id']) {
                                            $legal_id = $legal['id'];
                                            $is_active = ' element_active';
                                        } else if (!$isSession && $legal['default']) {
                                            if ($disabled) unset($disabled);
                                            $legal_id = $legal['id'];
                                            $is_active = ' element_active';
                                        }
                                    ?>
                                    <div class="element<?php echo $is_active; ?>">
                                        <div class="element__inner">

                                            <button type="button" class="element__select js-element-select" 
                                                    data-id="<?php echo $legal['id']; ?>"></button>

                                            <div class="element__content">
                                                <div class="element__item">
                                                    <div><b>Наименование:</b></div>
                                                    <div><?php echo $legal['name']; ?></div>
                                                </div>
                                                <div class="element__item">
                                                    <span><b>ИНН:</b></span>
                                                    <span><?php echo $legal['inn']; ?></span>
                                                </div>
                                                <?php
                                                    if (!$legal['kpp']) {
                                                        $style = 'style = "visibility: hidden;"';
                                                    }
                                                ?>
                                                <div class="element__item" <?php echo $style; ?>>
                                                    <span><b>КПП:</b></span>
                                                    <span><?php echo $legal['kpp']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($is_active) unset($is_active); ?>
                                    <?php if ($style) unset($style); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="p-modal__item p-modal-change__action">
                            <?php if ($disabled): ?>
                                <button type="submit" class="p-modal__button js-select-legal-entity" disabled>Выбрать</button>
                            <?php else: ?>
                                <button type="submit" class="p-modal__button js-select-legal-entity" 
                                    data-id="<?php echo $legal_id; ?>">Выбрать</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
		</div>
	</div>
</div>