<?php mgSEO($data);?>

<div class="static">
    <div class="max">
        <h1><?php echo lang('enterTitle'); ?></h1>
        <?php component('auth/login', $data);?>
        <div class="links flex">
            <a class="link" href="<?php echo SITE ?>/registration"><?php echo lang('enterRegister'); ?></a>
            <a class="link" href="<?php echo SITE ?>/forgotpass"><?php echo lang('enterForgot'); ?></a>
        </div>
    </div>
</div>