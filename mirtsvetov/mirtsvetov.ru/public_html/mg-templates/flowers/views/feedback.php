<?php mgSEO($data); ?>
<div class="static">
    <div class="max">
        <div class="feedback-form-wrapper">
            <h1>Стать клиентом</h1>
            <?php if (!empty($data['error'])): ?><div class="alert"><?php echo $data['error']; ?></div><?php endif; ?>
            
            <?php if ($data['dislpayForm']) { ?>
                <?php if (!empty($data['html_content']) && $data['html_content'] != '&nbsp;'):?>
                    <div class="content">
                        <p>Пожалуйста, оставьте ваши контакты и краткую информацию о вашей компании.</p>
                        <p><b>Важно:</b> минимальный заказ от 3000 стеблей в неделю.</p>
                    <br></div>
                <?php endif; ?>
    
                <form action="" method="post" name="feedback">
                    <ul class="form-list grid">
                        <!-- <li>
                            <input type="text" name="fio" placeholder=" " value="<?php echo !empty($_POST['fio']) ? $_POST['fio'] : '' ?>">
                            <span class="placeholder">Контактное лицо</span>
                        </li> -->
                        <li>
                            <input type="text" name="email" placeholder=" " value="<?php echo !empty($_POST['email']) ? $_POST['email'] : '' ?>">
                            <span class="placeholder"><?php echo lang('email'); ?></span>
                        </li>
                        <li>
                            <input type="text" name="phone" placeholder=" " value="<?php echo !empty($_POST['phone']) ? $_POST['phone'] : '' ?>">
                            <span class="placeholder"><?php echo lang('phone'); ?></span>
                        </li>
                        <li class="wide">
                            <textarea class="address-area" placeholder="ИНН, Наименовае компании, описание деятельности" name="message"><?php echo !empty($_REQUEST['message']) ? $_REQUEST['message'] : '' ?></textarea>
                        </li>
                    </ul>
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
                    <input type="submit" name="send" class="button" value="<?php echo lang('send'); ?>">
                    <?php
                    echo MG::addAgreementCheckbox(
                    'register-btn',
                    array(
                        'text' => lang('okAgree'),
                        'textLink' => lang('privData')
                    ));
                    ?>
                </form>
    
                <?php mgFormValid('feedback', 'feedback'); ?>
    
            <?php } else { ?>
    
                <div class="alert success">
                    <?php echo $data['message'] ?>
                </div>
            <?php }; ?>
        </div>
    </div>
</div>