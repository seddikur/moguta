<div class="p-modal p-modal-change js-modal-change">
	<div class="p-modal__container p-modal__container_big">
        <div class="p-modal__inner p-modal__inner_big">
            <button class="p-modal-close js-modal-close" type="button">Закрыть</button>
            <div class="p-modal-body">
                <div class="p-modal-body__container">
                    <div class="p-modal-body__inner p-modal-body__column">
                        <div class="p-modal__item">
                            <div class="p-modal__title">Адреса доставок</div>
                            <div class="p-modal__content p-modal__content_element">
                                <?php 
                                    $isSession = false;
                                    if ($_SESSION['LegalEntity']['address_id']) {
                                        $isSession = true;
                                        $session_id = $_SESSION['LegalEntity']['address_id'];
                                    } else {
                                        $disabled = true;
                                    }
                                ?>
                                <?php foreach ($data as $address): ?>
                                    <?php
                                        if ($session_id ===  $address['id']) {
                                            $id = $address['id'];
                                            $legal_id = $address['legal_id'];
                                            $is_active = ' element_active';
                                        } else if (!$isSession && $address['default']) {
                                            if ($disabled) unset($disabled);
                                            $id = $address['id'];
                                            $legal_id = $address['legal_id'];
                                            $is_active = ' element_active';
                                        }
                                    ?>
                                    <div class="element<?php echo $is_active; ?>">
                                        <div class="element__inner">
                                            <button type="button" class="element__select js-element-select" 
                                                    data-id="<?php echo $address['id']; ?>"
                                                    data-legal-id="<?php echo $address['legal_id']; ?>"></button>
                                            <div class="element__content">
                                                <div class="element__item">
                                                    <div><?php echo $address['address']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($is_active) unset($is_active); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="p-modal__item p-modal-change__action">
                            <?php if ($disabled): ?>
                                <button type="submit" class="p-modal__button js-select-address" disabled>Выбрать</button>
                            <?php else: ?>
                                <button type="submit" class="p-modal__button js-select-address" 
                                    data-id="<?php echo $id; ?>"
                                    data-legal-id="<?php echo $legal_id; ?>">Выбрать</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>