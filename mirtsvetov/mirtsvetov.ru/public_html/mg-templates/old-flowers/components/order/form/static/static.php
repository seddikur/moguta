<ul class="c-order__list form-list">
    <li class="c-order__list--item">
        <input type="text"
               name="email"
               placeholder="Email"
               value="<?php echo $_POST['email'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="text"
               name="phone"
               placeholder="<?php echo lang('phone'); ?>"
               value="<?php echo $_POST['phone'] ?>">
    </li>

    <?php if (MG::getSetting('useNameParts') == 'true') { ?>
        <li class="c-order__list--item">
            <input type="text"
                   name="fio_sname"
                   placeholder="<?php echo lang('fname') ?>"
                   value="<?php echo $_POST['fio_sname'] ?>">
        </li>
        <li class="c-order__list--item">
            <input type="text"
                   name="fio_name"
                   placeholder="<?php echo lang('lname') ?>"
                   value="<?php echo $_POST['fio_name'] ?>">
        </li>
        <li class="c-order__list--item">
            <input type="text"
                   name="fio_pname"
                   placeholder="<?php echo lang('pname') ?>"
                   value="<?php echo $_POST['fio_pname'] ?>">
        </li>
    <?php } else { ?>
        <li class="c-order__list--item">
            <input type="text"
                   name="fio"
                   placeholder="<?php echo lang('fio') ?>"
                   value="<?php echo $_POST['fio'] ?>">
        </li>
    <?php } ?>

    <li class="c-order__list--item">
        <input type="text"
               class="address-area"
               placeholder="<?php echo lang('orderPhAdres'); ?>"
               name="address"
               value="<?php echo $_POST['address'] ?>">
    </li>

    <li class="addressPartsTitle">
        <?php echo lang('orderPhAdres'); ?>:
    </li>
    <li class="c-order__list--item addressPartsTitle"></li>
    <li class="c-order__list--item addressPartsContainer">
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressIndex'); ?>"
               placeholder="<?php echo lang('orderPhAddressIndex'); ?>"
               name="address_index"
               value="<?php echo $_POST['address_index'] ?>">
    </li>
    <li class="c-order__list--item addressPartsContainer">
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressCountry'); ?>"
               placeholder="<?php echo lang('orderPhAddressCountry'); ?>"
               name="address_country"
               value="<?php echo $_POST['address_country'] ?>">
    </li>
    <li class="c-order__list--item addressPartsContainer">
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressRegion'); ?>"
               placeholder="<?php echo lang('orderPhAddressRegion'); ?>"
               name="address_region"
               value="<?php echo $_POST['address_region'] ?>">
    </li>
    <li class="c-order__list--item addressPartsContainer">
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressCity'); ?>"
               placeholder="<?php echo lang('orderPhAddressCity'); ?>"
               name="address_city"
               value="<?php echo $_POST['address_city'] ?>">
    </li>
    <li class="c-order__list--item addressPartsContainer">
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressStreet'); ?>"
               placeholder="<?php echo lang('orderPhAddressStreet'); ?>"
               name="address_street"
               value="<?php echo $_POST['address_street'] ?>">
    </li>
    <li class="c-order__list--item addressPartsContainer">
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressHouse'); ?>"
               placeholder="<?php echo lang('orderPhAddressHouse'); ?>"
               name="address_house"
               value="<?php echo $_POST['address_house'] ?>">
    </li>
    <li class="c-order__list--item addressPartsContainer">
        <input type="text"
               aria-label="<?php echo lang('orderPhAddressFlat'); ?>"
               placeholder="<?php echo lang('orderPhAddressFlat'); ?>"
               name="address_flat"
               value="<?php echo $_POST['address_flat'] ?>">
    </li>

    <li class="c-order__list--width">
         <textarea class="address-area"
                   aria-label="<?php echo lang('orderPhComment'); ?>"
                   placeholder="<?php echo lang('orderPhComment'); ?>"
                   name="info"><?php echo $_POST['info'] ?></textarea>
    </li>

    <?php if (class_exists('GsSDEC')): ?>
        [sdec_adress]
    <?php endif; ?>

    <?php
    // Дополнительные поля заказа
    component('order/op_fields');
    ?>

    <li class="c-order__list--width c-order__title--small">
        <?php echo lang('orderPayer'); ?>
    </li>
    <li class="c-order__list--width c-order__list--item">
        <select name="customer"
                aria-label="<?php echo lang('orderPayer'); ?>">
            <?php $selected = $_POST['customer'] == "yur" ? 'selected' : ''; ?>
            <option value="fiz">
                <?php echo lang('orderFiz'); ?>
            </option>
            <option value="yur" <?php echo $selected ?>>
                <?php echo lang('orderYur'); ?>
            </option>
        </select>
    </li>
</ul>
<?php if ($_POST['customer'] != "yur") {
    $style = 'style="display:none"';
} ?>
<ul class="c-order__list form-list yur-field" <?php echo $style ?>>
    <li class="c-order__list--item">
        <input type="text"
               name="yur_info[nameyur]"
               aria-label="<?php echo lang('orderPhNameyur'); ?>"
               placeholder="<?php echo lang('orderPhNameyur'); ?>"
               value="<?php echo $_POST['yur_info']['nameyur'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="text"
               name="yur_info[adress]"
               aria-label="<?php echo lang('orderPhAdress'); ?>"
               placeholder="<?php echo lang('orderPhAdress'); ?>"
               value="<?php echo $_POST['yur_info']['adress'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="number"
               name="yur_info[inn]"
               aria-label="<?php echo lang('orderPhInn'); ?>"
               placeholder="<?php echo lang('orderPhInn'); ?>"
               value="<?php echo $_POST['yur_info']['inn'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="text"
               name="yur_info[kpp]"
               aria-label="<?php echo lang('orderPhKpp'); ?>"
               placeholder="<?php echo lang('orderPhKpp'); ?>"
               value="<?php echo $_POST['yur_info']['kpp'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="text"
               name="yur_info[bank]"
               aria-label="<?php echo lang('orderPhBank'); ?>"
               placeholder="<?php echo lang('orderPhBank'); ?>"
               value="<?php echo $_POST['yur_info']['bank'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="text"
               name="yur_info[bik]"
               aria-label="<?php echo lang('orderPhBik'); ?>"
               placeholder="<?php echo lang('orderPhBik'); ?>"
               value="<?php echo $_POST['yur_info']['bik'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="text"
               name="yur_info[ks]"
               aria-label="<?php echo lang('orderPhKs'); ?>"
               placeholder="<?php echo lang('orderPhKs'); ?>"
               value="<?php echo $_POST['yur_info']['ks'] ?>">
    </li>
    <li class="c-order__list--item">
        <input type="text"
               name="yur_info[rs]"
               aria-label="<?php echo lang('orderPhRs'); ?>"
               placeholder="<?php echo lang('orderPhRs'); ?>"
               value="<?php echo $_POST['yur_info']['rs'] ?>">
    </li>
</ul>
