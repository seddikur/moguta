<div class="js-phone-registry c-phone-registry c-form">
    <div class="c-title title c-phone-title__registry">Подтверждение номера телефона</div>
    <p class="custom-text">В течение нескольких минут на номер <?php echo $data['userPhone']?> будет совершен звонок. Введите 4 последние цифры, позвонившего вам номера:</p>
    <ul class="c-phone-registry__list form-list">
        <li class="c-form__row">
            <div class="name-input">
                <input placeholder="4 последние цифры"
                       pattern="[0-9]{4}" maxlength="4"
                       autofocus="autofocus" required="required"
                       autocomplete="new-password"
                       type="text" name="callpass"
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
        <button type="submit" class="save-btn default-btn" name="confirmSMS" value = "confirm">Подтвердить</button>
        <?php if (!empty($data['capcha'])) { ?>
            <button type="submit" class="c-button c-button--link  default-btn" name="reCallBtn" value="<?php echo $data['capcha']?>">Позвонить повторно</button>
        <?php } elseif(!empty($_SESSION['timeCall'])){ ?>
            <span class="c-phone-registry__tip" style="display: none;">
                <?php
                    $currentTime = new DateTime;
                    $ss = $_SESSION['timeCall'] - $currentTime->getTimestamp();
                    if ($ss > 0) : ?>
                Повторный звонок будет доступен через
                <span id="timeCall"><?= $ss?></span> секунд
                <?php endif; ?>
            </span>
            <button type="submit" class="c-button c-button--link c-modal__cart default-btn" style="display: none;" name="timeCall" value="<?php echo $_SESSION['timeCall'];?>">Позвонить повторно</button>
        <?php }?>
    </div>
</div>

<script>
$(document).ready(function(){
    const containerElem = $('.js-phone-registry');
    const countdownElement = containerElem.find('#timeCall');
    const sendAgainBtnElement = containerElem.find('[name=timeCall]');
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

    containerElem.on('click','button[name=reCallBtn]', () => {
        containerElem.find('[name=callpass]').attr("required", false);
    });
    containerElem.on('click','button[name=timeCall]', () => {
        containerElem.find('[name=callpass]').attr("required", false);
    });
});
</script>
