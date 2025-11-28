<?php
if (in_array(EDITION, array('gipermarket', 'saas'))) {
    $fields = Models_OpFieldsOrder::getFields();

    foreach ($fields as $item) {
        switch ($item['type']) {
            case 'input':
                echo '<li class="c-order__list--item c-order__input"><input name='.$item['id'].' placeholder="'.$item['name'].'" type="text"></li>';
                break;
            case 'textarea':
                echo '<li class="c-order__list--width c-order__textarea"><textarea name='.$item['id'].' placeholder="'.$item['name'].'"></textarea></li>';
                break;
            case 'radiobutton':
                echo '<li class="c-order__list--item c-order__radiobutton">';
                foreach ($item['vars'] as $variant) {
                    echo '<label><input name='.$item['id'].' value="'.htmlspecialchars($variant).'" type="radio" '.$required.'>'.htmlspecialchars($variant).'</label>';
                }
                echo '</li>';
                break;
            case 'checkbox':
                echo '<li class="c-order__list--item c-order__checkbox"><label><input name='.$item['id'].' value="'.$item['name'].'" type="checkbox">'.$item['name'].'</label></li>';
                break;
            case 'select':
                echo '<li class="c-order__list--item c-order__select"><select name='.$item['id'].'>';
                foreach ($item['vars'] as $variant) {
                    echo '<option placeholder="'.$item['name'].'" '.$required.' value="'.htmlspecialchars($variant).'">'.htmlspecialchars($variant).'</option>';
                }
                echo '</select></li>';
                break;

            default:
                echo '<li class="c-order__list--item c-order__default"><input name='.$item['id'].' placeholder="'.$item['name'].'" type="text"></li>';
                break;
        }
    }
}
?>