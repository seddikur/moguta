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
                            <div class="p-modal__title"><?php echo $this->lang['MODAL_ADD_SESSION']; ?></div>
                            <div class="p-modal__content">
                                <div class="days">
                                    <?php foreach ($days as $key => $day): ?>
                                        <div class="day">
                                            <div class="day__checkbox">
                                                <div class="checkbox inline">
                                                    <input id="active_<?php echo $key; ?>" type="checkbox" name="active_<?php echo $key; ?>" 
                                                            <?php echo ($day['active'] != 0) ? 'checked=cheked' : '' ?>>
                                                    <label for="active_<?php echo $key; ?>"></label>
                                                </div>
                                                <div class="day__name">
                                                    <span><?php echo $day['day']; ?></span>
                                                    <input type="hidden" name="day_<?php echo $key; ?>" value="<?php echo $day['day']; ?>">
                                                </div>
                                            </div>
                                            <div class="day__date">
                                                <div class="day__start">
                                                    <div class="day__label"><?php echo $this->lang['MODAL_SESSION_START']; ?></div>
                                                    <input type="text" name="start_<?php echo $key; ?>" 
                                                            class="date-from-input" autocomplete="off" 
                                                            value="<?php echo $day['start']; ?>" 
                                                            <?php echo ($day['active'] == 0) ? 'disabled' : '' ?> readonly>
                                                </div>
                                                <div class="day__end">
                                                    <div class="day__label"><?php echo $this->lang['MODAL_SESSION_END']; ?></div>
                                                    <input type="text" name="end_<?php echo $key; ?>" 
                                                            class="date-before-input" autocomplete="off" 
                                                            value="<?php echo $day['end']; ?>"
                                                            <?php echo ($day['active'] == 0) ? 'disabled' : '' ?> readonly>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="p-modal__item p-modal__action">
                            <button type="button" class="button-link js-modal-cancel">
                                <?php echo $this->lang['MODAL_CANCEL']; ?>
                            </button>
                            <button type="submit" class="button success js-save-session" 
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