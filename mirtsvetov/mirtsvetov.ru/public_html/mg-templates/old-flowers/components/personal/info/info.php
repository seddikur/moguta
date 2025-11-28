<?php
$userInfo = USER::getThis();
?>

<form action="<?php echo SITE ?>/personal" method="POST">
    <ul class="form-list grid">
        <li>
            <input type="text" aria-label="<?php echo lang('lname'); ?>" name="sname" value="<?php echo $userInfo->sname ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('lname'); ?></span>
        </li>
        <li>
            <input type="text" aria-label="<?php echo lang('fname'); ?>" name="name" value="<?php echo $userInfo->name ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('fname'); ?></span>
        </li>
        <li>
            <input type="text" aria-label="<?php echo lang('pname'); ?>" name="pname" value="<?php echo $userInfo->pname ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('pname'); ?></span>
        </li>
        <li>
            <input type="text" aria-label="<?php echo lang('email'); ?>" name="email" value="<?php echo $userInfo->email ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('email'); ?></span>
        </li>
        <li>
            <input type="text" aria-label="<?php echo lang('phone'); ?>" name="phone" value="<?php echo $userInfo->phone ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('phone'); ?></span>
        </li>
        <li>
            <textarea class="address-area" aria-label="<?php echo lang('orderPhAdres'); ?>" name="address" placeholder=" "><?php echo $userInfo->address ?></textarea>
            <span class="placeholder"><?php echo lang('orderPhAdres'); ?></span>
        </li>


        <?php $style = ''; if (!$data['showAddressParts']) {$style = 'style="display:none"';}?>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAddressIndex'); ?>" name="address_index" value="<?php echo $userInfo->address_index ?>"
               placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAddressIndex'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAddressCountry'); ?>" name="address_country" value="<?php echo $userInfo->address_country ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAddressCountry'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAddressRegion'); ?>" name="address_region" value="<?php echo $userInfo->address_region ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAddressRegion'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAddressCity'); ?>" name="address_city" value="<?php echo $userInfo->address_city ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAddressCity'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAddressStreet'); ?>" name="address_street" value="<?php echo $userInfo->address_street ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAddressStreet'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAddressHouse'); ?>" name="address_house" value="<?php echo $userInfo->address_house ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAddressHouse'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAddressFlat'); ?>" name="address_flat" value="<?php echo $userInfo->address_flat ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAddressFlat'); ?></span>
        </li>
        <li>
            <select name="customer" aria-label="<?php echo lang('orderFiz'); ?>">
                <?php $selected = $userInfo->inn ? 'selected' : ''; ?>
                <option value="fiz"><?php echo lang('orderFiz'); ?></option>
                <option value="yur" <?php echo $selected ?>><?php echo lang('orderYur'); ?></option>
            </select>
        </li>
        
        
        <?php $style = ''; if (!$userInfo->inn) {$style = 'style="display:none"';}?>
        <li <?php echo $style ?>>
            <input type="text"  aria-label="<?php echo lang('orderPhNameyur'); ?>" name="nameyur" value="<?php echo $userInfo->nameyur ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhNameyur'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhAdress'); ?>" name="adress" value="<?php echo $userInfo->adress ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhAdress'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhInn'); ?>" name="inn" value="<?php echo $userInfo->inn ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhInn'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhKpp'); ?>" name="kpp" value="<?php echo $userInfo->kpp ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhKpp'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhBank'); ?>" name="bank" value="<?php echo $userInfo->bank ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhBank'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhBik'); ?>" name="bik" value="<?php echo $userInfo->bik ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhBik'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhKs'); ?>" name="ks" value="<?php echo $userInfo->ks ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhKs'); ?></span>
        </li>
        <li <?php echo $style ?>>
            <input type="text" aria-label="<?php echo lang('orderPhRs'); ?>" name="rs" value="<?php echo $userInfo->rs ?>" placeholder=" ">
            <span class="placeholder"><?php echo lang('orderPhRs'); ?></span>
        </li>
    </ul>

    <?php component('personal/info/opt_fields', $data);?>
    <button type="submit" class="button" name="userData" value="save"><?php echo lang('save'); ?></button>
</form>