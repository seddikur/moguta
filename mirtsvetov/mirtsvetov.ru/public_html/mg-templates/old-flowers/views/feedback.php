<?php mgSEO($data); ?>
<div class="max">
    <div class="feedback-form-wrapper">
        <h1><?php echo lang('feedbackTitle'); ?></h1>
        <?php if (!empty($data['error'])): ?><div class="alert"><?php echo $data['error']; ?></div><?php endif; ?>

        <?php if ($data['dislpayForm']) { ?>
            <?php if (!empty($data['html_content']) && $data['html_content'] != '&nbsp;'):?>
                <?php echo $data['html_content'] ?>
            <?php endif; ?>

            <form action="" method="post" name="feedback">
                <div class="c-form__row">
                    <input type="text" name="fio" placeholder="<?php echo lang('fio'); ?>" value="<?php echo !empty($_POST['fio']) ? $_POST['fio'] : '' ?>">
                </div>
                <div class="c-form__row">
                    <input type="text" name="email" placeholder="<?php echo lang('email'); ?>" value="<?php echo !empty($_POST['email']) ? $_POST['email'] : '' ?>">
                </div>
                <div class="c-form__row">
                    <input type="text" name="phone" placeholder="<?php echo lang('phone'); ?>" value="<?php echo !empty($_POST['phone']) ? $_POST['phone'] : '' ?>">
                </div>
                <div class="c-form__row">
                    <textarea class="address-area" placeholder="<?php echo lang('feedbackMessage'); ?>" name="message"><?php echo !empty($_REQUEST['message']) ? $_REQUEST['message'] : '' ?></textarea>
                </div>
                <?php if (MG::getSetting('useCaptcha') == "true" && MG::getSetting('useReCaptcha') != 'true'): ?>
                    <div class="c-form__row">
                        <b><?php echo lang('captcha'); ?></b>
                    </div>
                    <div class="c-form__row">
                        <img src="captcha.html" width="140" height="36">
                    </div>
                    <div class="c-form__row">
                        <input type="text" name="capcha" class="captcha">
                    </div>
                <?php endif; ?>
                <?php echo MG::printReCaptcha(); ?>
                <input type="submit" name="send" class="c-button" value="<?php echo lang('send'); ?>">
            </form>

            <?php mgFormValid('feedback', 'feedback'); ?>

        <?php } else { ?>

            <div class="alert success">
                <?php echo $data['message'] ?>
            </div>
        <?php }; ?>
    </div>
</div>
