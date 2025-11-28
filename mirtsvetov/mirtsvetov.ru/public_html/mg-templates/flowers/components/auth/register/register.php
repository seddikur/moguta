<?php mgAddMeta('js/jquery.maskedinput.min.js');?>
<?php if ($data['form']){?>
    <?php
    // Успешная регистрация
    if ($data['message']): ?>
        <div class="alert success"><?php echo $data['message'] ?></div>
    <?php endif; ?>


    <?php
    // Ошибка при регистрации
    if ($data['error']): ?>
        <div class="alert"><?php echo $data['error'] ?></div>
    <?php endif; ?>

    <form action="<?php echo SITE ?>/registration" method="POST">
        <ul class="form-list">
            <li>
                <input type="text" name="email" placeholder=" " aria-label="E-mail <?= (edition())?'':'или '.lang('lphone'); ?>" value="<?php echo $_POST['email'] ?>" required>
                <span class="placeholder">E-mail <?= (edition())?'':'или '.lang('lphone'); ?></span>
            </li>
            <li>
                <input type="password" placeholder=" " aria-label="<?php echo lang('enterPass'); ?>" name="pass" required>
                <span class="placeholder"><?php echo lang('enterPass'); ?></span>
            </li>
            <li>
                <input type="password" placeholder=" " aria-label="<?php echo lang('registrationConfirmPass'); ?>" name="pass2" required>
                <span class="placeholder"><?php echo lang('registrationConfirmPass'); ?></span>
            </li>
            <li>
                <input type="text" placeholder=" " aria-label="<?php echo lang('fname'); ?>" name="name" value="<?php echo $_POST['name'] ?>" required>
                <span class="placeholder"><?php echo lang('fname'); ?></span>
            </li>
            <?php if (MG::getSetting('useCaptcha') == "true" && MG::getSetting('useReCaptcha') != 'true'):?>
                <li>
                    <?php echo lang('captcha'); ?>
                    <img style="background: url('<?php echo PATH_TEMPLATE ?>/images/cap.png');" alt="captcha" src="captcha.html" width="140" height="36">
                    <input type="text" aria-label="capcha" name="capcha" class="captcha" required>
                </li>
            <?php endif; ?>
            <?php echo MG::printReCaptcha(); ?>
        </ul>
        <input type="hidden" aria-label="<?php echo $_SERVER['REMOTE_ADDR'] ?>" name="ip" value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" required>
        <button type="submit" class="button" name="registration"><?php echo lang('registrationButton'); ?></button>
    </form>

<?php } else { ?>

    <?php if ($data['error']): ?>
        <div class="alert"><?php echo $data['error'] ?></div>
    <?php endif; ?>
    <?php if ($data['message']): ?>
        <div class="alert success"><?php echo $data['message'] ?></div>
    <?php endif; ?>
<?php } ?>