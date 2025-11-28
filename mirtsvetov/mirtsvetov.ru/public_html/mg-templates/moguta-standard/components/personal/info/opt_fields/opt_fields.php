<?php
if (in_array(EDITION, array('gipermarket', 'saas'))) {
    $opFieldsM = new Models_OpFieldsUser($data['userInfo']->id);
    $op = $opFieldsM->get();

    foreach ($op as $key => $value) {
        if ($value['active'] == 0) continue;
        $html .= '<div class="c-form__row user-field"><h4 class="user-field__title">' . $value['name'] . '</h4>';
        switch ($value['type']) {
            case 'input':
                $html .= '<input type="text" name="op_' . $key . '" class="rule userOpFields" value="' . $value['value'] . '" placeholder="' . $value['name'] . '">';
                break;
            case 'textarea':
                $html .= '<textarea placeholder="' . $value['placeholder'] . '" name="op_' . $key . '" class="userOpFields" >' . $value['value'] . '</textarea>';
                break;
            case 'checkbox':
                // $html .= '<input type="checkbox" name="'.$key.'" id="'.$key.'" class="userOpFields" '.($value['value'] == 'true'?'checked':'').'>';
                $html .= '	<label style="margin-bottom:8px;" class="op-field-check ' . ($value['value'] == 'true' ? 'active' : 'nonactive') . '">
				              	<input type="checkbox" name="op_' . $key . '" ' . ($value['value'] == 'true' ? 'checked' : '') . ' value="true">
				              	<span class="value-name">' . $value['name'] . '</span>
				              	<span class="cbox"> </span>
				            </label>';
                break;
            case 'select':
                $tmp = '';
                foreach ($value['vars'] as $var) {
                    $tmp .= '<option value="' . $var . '" ' . ($value['value'] == $var ? 'selected' : '') . '>' . $var . '</option>';
                }
                $html .= '<select class="userOpFields" name="op_' . $key . '">' . $tmp . '</select>';
                break;
            case 'radiobutton':
                $tmp = '';
                foreach ($value['vars'] as $var) {
                    $html .= '	<label style="margin-bottom:8px;" class="op-field-check ' . ($value['value'] == $var ? 'active' : 'nonactive') . '">
					              	<input type="radio" name="op_' . $key . '" ' . ($value['value'] == $var ? 'checked' : '') . ' value="' . $var . '">
					              	<span class="value-name">' . $var . '</span>
					              	<span class="cbox"> </span>
					            </label>';
                }
                break;
        }
        $html .= '</div>';
    }

    echo $html;
}
?>