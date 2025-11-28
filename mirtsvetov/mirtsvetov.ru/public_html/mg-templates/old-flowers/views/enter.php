<?php mgSEO($data);?>

<div class="max static">
    <div class="flex space-between">
        <div class="left-part sidebar">
            <ul class="left-menu">
                <li class="active"><a href="<?php echo SITE ?>/enter"><?php echo lang('enterTitle'); ?></a></li>
                <li><a href="<?php echo SITE ?>/registration"><?php echo lang('enterRegister'); ?></a></li>
                <li><a href="<?php echo SITE ?>/forgotpass"><?php echo lang('enterForgot'); ?></a></li>
            </ul>
        </div>
        <div class="right-part">
            <h1><?php echo lang('enterTitle'); ?></h1>
            <?php component('auth/login', $data);?>	
        </div>
    </div>
</div>