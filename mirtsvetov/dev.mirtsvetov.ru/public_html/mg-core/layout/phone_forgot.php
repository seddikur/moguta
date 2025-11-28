<div class="js-phone-registry c-phone-registry c-form">
      <div class="c-title title c-phone-title__forgot">Восстановления пароля</div>
    <p class="custom-text">Код подтверждения был отправлен на номер телефона: <?php echo $data['userPhone']?></p>
        <ul class="form-list c-phone-registry__list ">
           <li class="c-form__row">
               <div class="name-input">
                   <input placeholder="Введите код подтверждения из смс"
                          pattern="[0-9]{6}" maxlength="6"
                          autofocus="autofocus" required="required"
                          autocomplete="new-password"
                          type="text" name="smspass"
                          value="">
               </div>
           </li>
            <?php  if(!empty($data['capcha'])){ ?>
            <li class="c-form__row">
                <p>Введите текст с картинки:</p>
                <img src="captcha.html?<?php echo 't='.time()?>" width="140" height="36">
                <input autocomplete="new-password" type="text" name="capcha" class="captcha" required="required">
            </li>
            <?php }?>
           <li><input type="hidden" name="ip" value="::1"></li>
        </ul>

    <div class="c-form__row">
        <button type="submit" class="save-btn default-btn" name="confirmSMS" value="confirm">Подтвердить</button>
        <?php  if(!empty($data['capcha'])){ ?>
        <button type="submit" class="c-button c-button--link c-modal__cart default-btn" name="resendSMS" value="<?php echo $data['capcha']?>">Отправить повторно</button>
        <?php } elseif(!empty($_SESSION['timeSMS'])){ ?>
            <?php
            $currentTime = new DateTime;
            $ss = $_SESSION['timeSMS'] - $currentTime->getTimestamp();
            if ($ss > 0) :?>
                <span class="c-phone-registry__tip" style="display: none;">
                    Повторная отправка смс будет доступна через
                        <span id="timeSMS"><?= $ss?></span> секунд
                </span>
            <?php endif; ?>
            <button type="submit" class="c-button c-button--link default-btn" style="display: none;" name="timeSMS" value="<?php echo $_SESSION['timeSMS'];?>">Отправить повторно</button>
        <?php }?>
    </div>
</div>

<script>
  $(document).ready(function(){
    const containerElem = $('.js-phone-registry');
    const countdownElement = containerElem.find('#timeSMS');
    const sendAgainBtnElement = containerElem.find('[name=timeSMS]');
    let counter = countdownElement.text();
    countdownElement.parent().show();

    const intervalId = setInterval(() => {
      counter -= 1;
      countdownElement.html(counter);
      if (counter <= 0) {
        countdownElement.parent().hide();
        sendAgainBtnElement.show();
        clearInterval(intervalId);
      }
    }, 1000);

    containerElem.on('click','button[name=resendSMS]', () => {
      containerElem.find('[name=smspass]').attr("required", false);
    });
    containerElem.on('click','button[name=timeSMS]', () => {
      containerElem.find('[name=smspass]').attr("required", false);
    });
  });
</script>
