<?php
$data['meta_title'] = lang('orderPaymentConfirmTitle');
mgSEO($data);
?>

<div class="l-row">
    <?php if ($data['msg']): ?>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--green text-success">
                <?php echo $data['msg'] ?>
            </div>
        </div>
    <?php endif;

    if ($data['id']): ?>
        <div class="l-col min-0--12">
            <div class="c-title"><?php echo lang('orderPaymentConfirmTitle'); ?></div>
        </div>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--green auth-text">
                <?php echo lang('orderPaymentConfirm1'); ?>
                <?php echo $data['orderNumber'] ?>
                <?php echo lang('orderPaymentConfirm2'); ?>
            </div>
        </div>
    <?php endif;

    // если пользователь не активизирован, то показываем форму задания пароля
    if ($data['active']): ?>

        <div class="l-col min-0--12">
            <div class="c-alert c-alert--green text-success">
                <?php echo lang('orderPaymentRegister1'); ?>
                <strong><?php echo SITE ?></strong> <?php echo lang('orderPaymentRegister2'); ?>
            </div>
        </div>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--blue get-login">
                <?php echo lang('orderPaymentRegister3'); ?> <strong><?php echo $data['active'] ?></strong>.
            </div>
        </div>
        <div class="user-login">
            <div class="l-col min-0--12">
                <div class="c-alert c-alert--blue custom-text"><?php echo lang('orderPaymentRegister4'); ?></div>
            </div>
            <div class="l-col min-0--12">
                <form class="c-form c-form--width" action="<?php echo SITE ?>/forgotpass" method="POST">
                    <div class="c-form__row">
                        <input type="password" name="newPass" placeholder="<?php echo lang('forgotPass1'); ?>"
                               required>
                    </div>
                    <div class="c-form__row">
                        <input type="password" name="pass2" placeholder="<?php echo lang('forgotPass2'); ?>"
                               required>
                    </div>
                    <div class="c-form__row">
                        <input type="submit" class="c-button" name="chengePass"
                               value="<?php echo lang('save'); ?>">
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>
