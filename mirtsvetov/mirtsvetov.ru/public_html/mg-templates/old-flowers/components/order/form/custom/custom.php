<ul class="form-list flex space-between">
  <?php
  $orderFields = Models_OpFieldsOrder::getOrderFormPublicArr();
  foreach ($orderFields as $fieldName => $field) {
    switch ($field['type']) {
      case 'input': ?>
          <li class="js-orderFromItem">
              <input placeholder=" " <?php echo ($fieldName === 'yur_info_inn') ? 'type="number"' : 'type="text"'; ?> name="<?php echo $fieldName ?>" value="<?php echo isset($_POST[$fieldName]) ? $_POST[$fieldName] : '' ?>" <?php echo $field['required'] ?>>
              <span class="placeholder"><?php echo $field['placeholder'] ? $field['placeholder'] : $field['title'] ?></span>
          </li>
        <?php break;
      case 'textarea': ?>
          <li class="js-orderFromItem wide">
				<textarea placeholder=" " name="<?php echo $fieldName ?>" <?php echo $field['required'] ?>><?php echo isset($_POST[$fieldName]) ? $_POST[$fieldName] : '' ?></textarea>
            <span class="placeholder"><?php echo $field['placeholder'] ? $field['placeholder'] : $field['title'] ?></span>
          </li>
        <?php break;
      case 'checkbox': ?>
          <li class="js-orderFromItem">
              <input type="checkbox" name="<?php echo $fieldName ?>" <?php echo $field['required'] ?>>
                <?php echo $field['title'] ?>
              <span class="placeholder"></span>
          </li>
        <?php break;
      case 'select': ?>
          <li class="js-orderFromItem">
              <select name="<?php echo $fieldName ?>" <?php echo $field['required'] ?>>
                <?php foreach ($field['vars'] as $option) { ?>
                  <?php $selected = (isset($_POST[$fieldName]) && $_POST[$fieldName] == $option['val']) ? 'selected' : ''; ?>
                    <option value="<?php echo $option['val'] ?>" <?php echo $selected ?>><?php echo $option['text'] ?></option>
                <?php } ?>
              </select>
              <span class="placeholder"><?php echo $field['title'] ?></span>
          </li>
        <?php break;
      case 'radiobutton': ?>
          <li class="js-orderFromItem">
              <h4 class="c-order-field__title"><?php echo $field['title'] ?></h4>
            <?php
            $checked = 'checked';
            $active = 'class="active"';
            foreach ($field['vars'] as $option) { ?>
                <label <?php echo $active ?> title="<?php echo $field['placeholder'] ?>">
                    <input name="<?php echo $fieldName ?>"
                           value="<?php echo $option['val'] ?>"
                           title="<?php echo $field['placeholder'] ?>"
                           type="radio" <?php echo $field['required'] ?> <?php echo $checked ?>>
                  <?php echo $option['text'] ?>
                </label>
              <?php
              $checked = $active = '';
            } ?>
          </li>
        <?php break;
      case 'file': ?>
          <li class="js-orderFromItem"
              style="white-space: nowrap;">
              <span><?php echo $field['title'] ?> :</span>
              <label class="orderFileContainer">
                  <span class="orderFileText"
                        data-initialtext="<?php echo lang('orderFormAddFile'); ?>"><?php echo lang('orderFormAddFile'); ?></span>
                  <input style="display:none"
                         class="orderFileInput"
                         type="file"
                         name="<?php echo $fieldName ?>">
                  <button class="removeOrderFile"
                          style="display:none">×
                  </button>
              </label>
          </li>
        <?php break;
    }
  }
  ?>
  <?php if (class_exists('GsSDEC')): ?>
      [sdec_adress]
  <?php endif; ?>
  <?php if (class_exists('OzonRocket')): ?>
    [ozon_rocket]
    [ozon-rocket-widget]
  <?php endif; ?>
</ul>
