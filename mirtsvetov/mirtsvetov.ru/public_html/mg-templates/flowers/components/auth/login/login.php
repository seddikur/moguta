<?php if (!empty($data['msgError'])) : ?>
    <div class="alert"><?php echo $data['msgError'] ?></div>
<?php endif; ?>

<form action="<?php echo SITE ?>/enter" method="POST">
    <ul class="form-list grid">
        <li>
            <input type="text" name="email" placeholder=" " aria-label="E-mail <?= (edition())?'':'или '.lang('lphone'); ?>" value="<?php echo !empty($_POST['email']) ? $_POST['email'] : '' ?>" required>
            <span class="placeholder">E-mail <?= (edition())?'':'или '.lang('lphone'); ?></span>
        </li>
        <li>
            <input type="password" placeholder=" " aria-label="<?php echo lang('enterPass'); ?>" name="pass" required>
            <span class="placeholder"><?php echo lang('enterPass'); ?></span>
        </li>
    </ul>
    <?php echo !empty($data['checkCapcha']) ? $data['checkCapcha'] : '' ?>
    <?php if (!empty($_REQUEST['location'])) : ?>
        <input type="hidden" name="location" value="<?php echo $_REQUEST['location']; ?>"/>
    <?php endif; ?>    
    <div class="flex align-center">
        <button type="submit" title="<?php echo lang('enterEnter'); ?>" class="button"><?php echo lang('enterEnter');?></button>
        <a class="link" href="/feedback">Стать клиентом</a>
    </div>
</form>