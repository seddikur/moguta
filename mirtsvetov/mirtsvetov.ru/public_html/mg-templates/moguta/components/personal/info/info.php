<?php mgAddMeta('lib/datepicker.css'); ?>
<?php mgAddMeta('lib/jquery-ui.min.js'); ?>
<?php mgAddMeta('components/personal/info/info.js'); ?>
<?php
$userInfo = USER::getThis();
?>

<form class="c-form c-form--width"
      action="<?php echo SITE ?>/personal"
      method="POST">
    <div class="c-form__row" style="line-height: 1.6; margin-bottom: 20px;">
        <div>
            <b><?php echo lang('auth'); ?>:</b>
        </div>
        <?php $failEmail = $userInfo->id.'@'.$_SERVER['SERVER_NAME'];
              if($userInfo->login_email != $failEmail): ?>
        <div><b>Email:</b>
            <span class="normal-text">
                <?php echo $userInfo->login_email ?>
            </span>
        </div>
        <?php endif; ?>
        <?php if($userInfo->login_phone == '' && MG::getSetting('confirmRegistrationPhone') == 'true'): ?>
          <div>
              <b><?php echo lang('lphone'); ?></b>:
              <label for="add-login-phone">
                  <?php echo lang('addphone'); ?>
              </label>
          </div>
          <input type="checkbox" id="add-login-phone">
          <div class="to-be-changed">
            <div class="c-form__row">
              <input type="text" name="phoneLogin">
            </div>
            <div class="c-form__row">
              <button type="submit" class="default-btn" name="addPhone" value ="addPhone"><?php echo lang('confirm');?></button>
            </div>
          </div>
        <?php elseif($userInfo->login_phone !=  ''): ?>
          <div><?php echo lang('lphone'); ?><span class="normal-text">:
          <?php echo $userInfo->login_phone; ?></span></div>
        <?php endif; ?>
    </div>
    <div class="c-form__row">
        <b><?php echo lang('personalRegisterDate'); ?></b>
      <?php echo date('d.m.Y', strtotime($userInfo->date_add)) ?>
    </div>
    <div class="c-form__row">
        <input type="text"
               aria-label="<?php echo lang('lname'); ?>"
               name="sname"
               value="<?php echo $userInfo->sname ?>"
               placeholder="<?php echo lang('lname'); ?>">
    </div>
    <div class="c-form__row">
        <input type="text"
               aria-label="<?php echo lang('fname'); ?>"
               name="name"
               value="<?php echo $userInfo->name ?>"
               placeholder="<?php echo lang('fname'); ?>">
    </div>
    <div class="c-form__row">
        <input type="text"
               aria-label="<?php echo lang('pname'); ?>"
               name="pname"
               value="<?php echo $userInfo->pname ?>"
               placeholder="<?php echo lang('pname'); ?>">
    </div>
    <div class="c-form__row">
        <input class="birthday"
               aria-label="<?php echo lang('personalBirthday'); ?>"
               type="text"
               name="birthday"
               value="<?php echo $userInfo->birthday ? date('d.m.Y', strtotime($userInfo->birthday)) : '' ?>"
               placeholder="<?php echo lang('personalBirthday'); ?>">
    </div>
    <div class="c-form__row">
        <input type="text"
               aria-label="<?php echo lang('email'); ?>"
               name="email"
               value="<?php echo $userInfo->email ?>"
               placeholder="<?php echo lang('email'); ?>">
    </div>
    <div class="c-form__row">
        <input type="text"
               aria-label="<?php echo lang('phone'); ?>"
               name="phone"
               value="<?php echo $userInfo->phone ?>"
               placeholder="<?php echo lang('phone'); ?>">
    </div>
    <div class="c-form__row">
                <textarea class="address-area"
                          aria-label="<?php echo lang('orderPhAdres'); ?>"
                          name="address"
                          placeholder="<?php echo lang('orderPhAdres'); ?>">
                    <?php echo $userInfo->address ?>
                </textarea>
    </div>

  <?php $style = '';
  if (!$data['showAddressParts']) {
    $style = 'style="display:none"';
  } ?>
    <div class="c-form__row" <?php echo $style ?>>
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressIndex'); ?>"
               name="address_index"
               value="<?php echo $userInfo->address_index ?>"
               placeholder="<?php echo lang('orderPhAddressIndex'); ?>">
    </div>
    <div class="c-form__row" <?php echo $style ?>>
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressCountry'); ?>"
               name="address_country"
               value="<?php echo $userInfo->address_country ?>"
               placeholder="<?php echo lang('orderPhAddressCountry'); ?>">
    </div>
    <div class="c-form__row" <?php echo $style ?>>
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressRegion'); ?>"
               name="address_region"
               value="<?php echo $userInfo->address_region ?>"
               placeholder="<?php echo lang('orderPhAddressRegion'); ?>">
    </div>
    <div class="c-form__row" <?php echo $style ?>>
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressCity'); ?>"
               name="address_city"
               value="<?php echo $userInfo->address_city ?>"
               placeholder="<?php echo lang('orderPhAddressCity'); ?>">
    </div>
    <div class="c-form__row" <?php echo $style ?>>
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressStreet'); ?>"
               name="address_street"
               value="<?php echo $userInfo->address_street ?>"
               placeholder="<?php echo lang('orderPhAddressStreet'); ?>">
    </div>
    <div class="c-form__row" <?php echo $style ?>>
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressHouse'); ?>"
               name="address_house"
               value="<?php echo $userInfo->address_house ?>"
               placeholder="<?php echo lang('orderPhAddressHouse'); ?>">
    </div>
    <div class="c-form__row" <?php echo $style ?>>
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressFlat'); ?>"
               name="address_flat"
               value="<?php echo $userInfo->address_flat ?>"
               placeholder="<?php echo lang('orderPhAddressFlat'); ?>">
    </div>

    <div class="c-form__row">
        <select name="customer"
                aria-label="<?php echo lang('orderFiz'); ?>">
          <?php $selected = $userInfo->inn ? 'selected' : ''; ?>
            <option value="fiz">
              <?php echo lang('orderFiz'); ?>
            </option>
            <option value="yur" <?php echo $selected ?>>
              <?php echo lang('orderYur'); ?>
            </option>
        </select>
    </div>

  <?php $style = '';
  if (!$userInfo->inn) {
    $style = 'style="display:none"';
  } ?>
    <div class="c-form__row yur-field" <?php echo $style ?>>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhNameyur'); ?>"
                   name="nameyur"
                   value="<?php echo $userInfo->nameyur ?>"
                   placeholder="<?php echo lang('orderPhNameyur'); ?>">
        </div>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhAdress'); ?>"
                   name="adress"
                   value="<?php echo $userInfo->adress ?>"
                   placeholder="<?php echo lang('orderPhAdress'); ?>">
        </div>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhInn'); ?>"
                   name="inn"
                   value="<?php echo $userInfo->inn ?>"
                   placeholder="<?php echo lang('orderPhInn'); ?>">
        </div>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhKpp'); ?>"
                   name="kpp"
                   value="<?php echo $userInfo->kpp ?>"
                   placeholder="<?php echo lang('orderPhKpp'); ?>">
        </div>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhBank'); ?>"
                   name="bank"
                   value="<?php echo $userInfo->bank ?>"
                   placeholder="<?php echo lang('orderPhBank'); ?>">
        </div>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhBik'); ?>"
                   name="bik"
                   value="<?php echo $userInfo->bik ?>"
                   placeholder="<?php echo lang('orderPhBik'); ?>">
        </div>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhKs'); ?>"
                   name="ks"
                   value="<?php echo $userInfo->ks ?>"
                   placeholder="<?php echo lang('orderPhKs'); ?>">
        </div>
        <div class="c-form__row">
            <input type="text"
                   aria-label="<?php echo lang('orderPhRs'); ?>"
                   name="rs"
                   value="<?php echo $userInfo->rs ?>"
                   placeholder="<?php echo lang('orderPhRs'); ?>">
        </div>
    </div>

  <?php
  // Дополнительные поля пользователя
  component('personal/info/opt_fields', $data);
  ?>

    <div class="c-form__row">
        <button type="submit"
                class="c-button"
                name="userData"
                value="save">
          <?php echo lang('save'); ?>
        </button>
    </div>
</form>
  <style>
	.to-be-changed, input[type=checkbox]#add-login-phone {display: none;}
	input[type=checkbox]:checked#add-login-phone ~ .to-be-changed{display: block;}
  label[for=add-login-phone]{display: inline; color: #417ebd; font-weight: bold;}
  label[for=add-login-phone]:before {display: none;}
  </style>
