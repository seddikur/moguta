<div class="modal modal-auth" data-modal="modal-auth">
	<div class="inner">
		<span class="close">Закрыть</span>
		<div class="h2-like">Войти в кабинет</div>
		<form action="<?php echo SITE ?>/enter" method="POST">
			<ul class="form-list">
				<li>
					<input class="check" placeholder=" " type="text" name="email" aria-label="E-mail <?= (edition())?'':'или '.lang('lphone'); ?>" value="<?php echo !empty($_POST['email']) ? $_POST['email'] : '' ?>" required>
					<span class="placeholder">E-mail <?= (edition())?'':'или '.lang('lphone'); ?></span>
				</li>
				<li>
					<input type="password" placeholder=" " aria-label="<?php echo lang('enterPass'); ?>" name="pass" required>
					<span class="placeholder"><?php echo lang('enterPass'); ?></span>
				</li>
				<?php echo !empty($data['checkCapcha']) ? $data['checkCapcha'] : '' ?>
			</ul>
			<?php if (!empty($_REQUEST['location'])) : ?>
				<input type="hidden" name="location" value="<?php echo $_REQUEST['location']; ?>"/>
			<?php endif; ?>
			<button type="submit" title="<?php echo lang('enterEnter'); ?>" class="button"><?php echo lang('enterEnter'); ?></button>
		</form>
		<div class="links">
			<a class="link" href="<?php echo SITE ?>/registration"><?php echo lang('enterRegister'); ?></a>
			<a class="link" href="<?php echo SITE ?>/forgotpass"><?php echo lang('enterForgot'); ?></a>
		</div>
	</div>
</div>