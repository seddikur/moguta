<form class="c-form c-form--width"
      action="<?php echo SITE ?>/personal"
      method="POST">
    <div class="c-form__row">
        <input type="password"
               name="pass"
               aria-label="<?php echo lang('personalOldPass'); ?>"
               placeholder="<?php echo lang('personalOldPass'); ?>"
               required>
    </div>
    <div class="c-form__row">
        <input type="password"
               name="newPass"
               aria-label="<?php echo lang('forgotPass1'); ?>"
               placeholder="<?php echo lang('forgotPass1'); ?>"
               required>
    </div>
    <div class="c-form__row">
        <input type="password"
               name="pass2"
               aria-label="<?php echo lang('personalPassRepeat'); ?>"
               placeholder="<?php echo lang('personalPassRepeat'); ?>"
               required>
    </div>
    <div class="c-form__row">
        <button type="submit"
                class="c-button"
                name="chengePass"
                value="save">
          <?php echo lang('save'); ?>
        </button>
    </div>
</form>