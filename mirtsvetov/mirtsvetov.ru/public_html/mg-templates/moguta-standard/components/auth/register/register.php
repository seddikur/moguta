<?php 
mgAddMeta('lib/jquery.maskedinput.min.js'); 
?>

<?php mgAddMeta('components/auth/register/register.css'); ?>
<?php mgAddMeta('components/auth/register/register.js'); ?>

<?php if ($data['form']){?>
    <div class="l-row login__container">
        <div class="l-col min-0--12">
            <div class="c-title">
                <?php echo lang('registrationTitle'); ?>
            </div>
        </div>

        <?php
        // Успешная регистрация
        if ($data['message']): ?>
            <div class="l-col min-0--12">
                <div class="c-alert c-alert--green mg-success">
                    <?php echo $data['message'] ?>
                </div>
            </div>
        <?php endif; ?>


        <?php
        // Ошибка при регистрации
        if ($data['error']): ?>
            <div class="l-col min-0--12">
                <div class="c-alert c-alert--red msgError">
                    <?php echo $data['error'] ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="l-col min-0--12">
            <form class="registration__form c-form c-form--width"
                  action="<?php echo SITE ?>/registration"
                  method="POST">

                <?php
                $emailFieldText = 'Email';
                if (!edition()) {
                    $emailFieldText = 'Email '.lang('or').lang('lphone');
                    $registrationMethod = MG::getSetting('registrationMethod');
                    if ($registrationMethod) {
                        switch ($registrationMethod) {
                            case 'email':
                                $emailFieldText = 'Email';
                                break;
                            case 'phone':
                                $emailFieldText = lang('lphone');
                                break;
                        }
                    }
                }
                ?>

                <div class="c-form__row">
                    <input type="text"
                           name="email"
                           aria-label="<?php echo $emailFieldText; ?>"
                           placeholder="<?php echo $emailFieldText; ?>"
                           value="<?php echo $_POST['email'] ?>"
                           required>
                </div>

                <div class="c-form__row">
                    <input type="password"
                           aria-label="<?php echo lang('enterPass'); ?>"
                           placeholder="<?php echo lang('enterPass'); ?>"
                           name="pass"
                           required>
                </div>

                <div class="c-form__row">
                    <input type="password"
                           aria-label="<?php echo lang('registrationConfirmPass'); ?>"
                           placeholder="<?php echo lang('registrationConfirmPass'); ?>"
                           name="pass2"
                           required>
                </div>

                <div class="c-form__row">
                    <input type="text"
                           aria-label="<?php echo lang('fname'); ?>"
                           name="name"
                           placeholder="<?php echo lang('fname'); ?>"
                           value="<?php echo $_POST['name'] ?>"
                           required>
                </div>

                <div class="c-form__row">
                    <input type="hidden"
                           aria-label="<?php echo $_SERVER['REMOTE_ADDR'] ?>"
                           name="ip"
                           value="<?php echo $_SERVER['REMOTE_ADDR'] ?>"
                           required>
                </div>

                <?php
                // Подключаем captcha, если reCaptcha отключена в настройках
                if (
                    MG::getSetting('useCaptcha') == "true" &&
                    MG::getSetting('useReCaptcha') != 'true'
                ):?>
                    <div class="c-form__row">
                        <b><?php echo lang('captcha'); ?></b>
                    </div>

                    <div class="c-form__row">
                        <img style="background: url('<?php echo PATH_TEMPLATE ?>/images/cap.png');"
                             alt="captcha"
                             src="captcha.html"
                             width="140" height="36">
                    </div>

                    <div class="c-form__row">
                        <input type="text"
                               aria-label="capcha"
                               name="capcha"
                               class="captcha"
                               required>
                    </div>
                <?php endif; ?>

                <?php
                // Подключаем ReCaptcha, если включено в настройках
                echo MG::printReCaptcha(); 

                echo MG::addAgreementCheckbox(
                    'register-btn',
                    array(
                        'text' => lang('okAgree'),
                        'textLink' => lang('privData')
                    ));
                ?>

                <div class="c-form__row">
                    <button type="submit" <?php echo MG::get('settings')['printAgreement'] === 'true' ? 'disabled="disabled"' : '' ?>
                            class="c-button register-btn"
                            name="registration">
                        <?php echo lang('registrationButton'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php } else { ?>

        <?php if ($data['error']): ?>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--red msgError">
                <?php echo $data['error'] ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($data['message']): ?>
        <div class="l-col min-0--12">
            <div class="c-alert c-alert--green mg-success">
               <?php echo $data['message'] ?>
            </div>
        </div>
    <?php endif; ?>

<?php } ?>
