<form action="<?php echo SITE ?>/personal" method="POST">
    <ul class="form-list">
        <li>
            <input type="password" name="pass" aria-label="<?php echo lang('personalOldPass'); ?>" placeholder=" " required>
            <span class="placeholder"><?php echo lang('personalOldPass'); ?></span>
        </li>
        <li>
            <input type="password" name="newPass" aria-label="<?php echo lang('forgotPass1'); ?>" placeholder=" " required>
            <span class="placeholder"><?php echo lang('forgotPass1'); ?></span>
        </li>
        <li>
            <input type="password" name="pass2" aria-label="<?php echo lang('personalPassRepeat'); ?>" placeholder=" " required>
            <span class="placeholder"><?php echo lang('personalPassRepeat'); ?></span>
        </li>
    </ul>
    <button type="submit" class="button" name="chengePass" value="save"><?php echo lang('save'); ?></button>
</form>