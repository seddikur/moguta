<?php if (!empty($data['msgError'])) : ?>
    <div class="l-col min-0--12">
        <div class="c-alert c-alert--red">
            <?php echo $data['msgError'] ?>
        </div>
    </div>
<?php endif; ?>

<div class="l-col min-0--12">
    <form class="c-form c-form--width"
          action="<?php echo SITE ?>/enter"
          method="POST">

        <div class="c-form__row">
            <input type="text"
                   name="email"
                   aria-label="E-mail <?= (edition())?'':'или '.lang('lphone'); ?>"
                   placeholder="E-mail <?= (edition())?'':'или '.lang('lphone'); ?>"
                   value="<?php echo !empty($_POST['email']) ? $_POST['email'] : '' ?>"
                   required>
        </div>

        <div class="c-form__row">
            <input type="password"
                   aria-label="<?php echo lang('enterPass'); ?>"
                   name="pass"
                   placeholder="<?php echo lang('enterPass'); ?>"
                   required>
        </div>

        <?php echo !empty($data['checkCapcha']) ? $data['checkCapcha'] : '' ?>

        <?php if (!empty($_REQUEST['location'])) : ?>
            <input type="hidden"
                   name="location"
                   value="<?php echo $_REQUEST['location']; ?>"/>
        <?php endif; ?>

        <div class="c-form__row c-form__row_flex">
            <button type="submit"
                    title="<?php echo lang('enterEnter'); ?>"
                    class="c-button">
                <?php echo lang('enterEnter'); ?>
            </button>

            <a class="c-button c-button--link"
               title="<?php echo lang('enterForgot'); ?>"
               href="<?php echo SITE ?>/forgotpass">
                <?php echo lang('enterForgot'); ?>
            </a>
        </div>

    </form>
    <?php if (class_exists('ULoginAuth')) { ?>
        <div style="margin-top: 15px;">
            [ulogin]
        </div>
    <?php } ?>
    <div class="c-form__row--line">
        <a class="c-button c-button--border"
           title="<?php echo lang('enterRegister'); ?>"
           href="<?php echo SITE ?>/registration">
            <?php echo lang('enterRegister'); ?>
        </a>
    </div>
</div>