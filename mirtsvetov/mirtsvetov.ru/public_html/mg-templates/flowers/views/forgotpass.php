<?php mgSEO($data);?>

<div class="max static">
    <div class="flex space-between">
        <div class="left-part sidebar">
            <ul class="left-menu">
                <li><a href="<?php echo SITE ?>/enter"><?php echo lang('enterTitle'); ?></a></li>
                <li><a href="<?php echo SITE ?>/registration"><?php echo lang('enterRegister'); ?></a></li>
                <li class="active"><a href="<?php echo SITE ?>/forgotpass"><?php echo lang('enterForgot'); ?></a></li>
            </ul>
        </div>
        <div class="right-part">
            <h1><?php echo lang('forgotTitle'); ?></h1>
            <?php if ($data['message']): ?><div class="alert success"><?php echo $data['message'] ?></div><?php endif; ?>
            <?php if ($data['error']): ?><div class="alert"><?php echo $data['error'] ?></div><?php endif; ?>
            <?php switch ($data['form']) { case 1: ?>
                <form action="<?php echo SITE ?>/forgotpass" method="POST">
                    <ul class="form-list">
                        <li>
                            <input placeholder=" " type="text" name="email" required>
                            <span class="placeholder">E-mail <?= (edition())?'':'или '.lang('lphone'); ?></span>
                        </li>
                    </ul>
                    <input type="submit" class="button" name="forgotpass" value="<?php echo lang('send'); ?>">
                </form>
        
                <?php break; case 2: ?>
        
                <form action="<?php echo SITE ?>/forgotpass" method="POST">
                    <ul class="form-list">
                        <li>
                            <input placeholder=" " type="password" name="newPass" required>
                            <span class="placeholder"><?php echo lang('forgotPass1'); ?></span>
                        </li>
                        <li>
                            <input placeholder=" " type="password" name="pass2" required>
                            <span class="placeholder"><?php echo lang('forgotPass2'); ?></span>
                        </li>
                    </ul>
                    <input type="submit" class="button" name="chengePass" value="<?php echo lang('save'); ?>">
                </form>
            <?php } ?>	
        </div>
    </div>
</div>