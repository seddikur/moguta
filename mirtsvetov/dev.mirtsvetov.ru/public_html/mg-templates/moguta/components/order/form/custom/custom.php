<ul class="c-order__list form-list">
  <?php
  $orderFields = Models_OpFieldsOrder::getOrderFormPublicArr();
  $hide = '';
  if (!empty($orderFields['customer']['type'])) {
    $hide = 'style="display: none"';
  }
  foreach ($orderFields as $fieldName => $field) {
    switch ($field['type']) {
      case 'input': ?>
          <li class="c-order__list--item js-orderFromItem" <?php echo !empty($field['conditionType']) && $field['conditionType'] !== 'always'  ? $hide : ''; ?> >
              <input <?php echo ($fieldName === 'yur_info_inn') ? 'type="number"' : 'type="text"'; ?>
                     name="<?php echo $fieldName ?>"
                     placeholder="<?php echo $field['placeholder'] ? $field['placeholder'] : $field['title'] ?>"
                     title="<?php echo $field['placeholder'] ? $field['placeholder'] : $field['title'] ?>"
                     value="<?php echo isset($_POST[$fieldName]) ? $_POST[$fieldName] : '' ?>"
                <?php echo $field['required'] ?>>
          </li>
        <?php break;
      case 'textarea': ?>
          <li class="c-order__list--width js-orderFromItem" <?php echo !empty($field['conditionType']) && $field['conditionType'] !== 'always'  ? $hide : ''; ?>>
				<textarea placeholder="<?php echo $field['placeholder'] ? $field['placeholder'] : $field['title'] ?>"
                          title="<?php echo $field['placeholder'] ? $field['placeholder'] : $field['title'] ?>"
                          name="<?php echo $fieldName ?>"
					<?php echo $field['required'] ?>><?php echo isset($_POST[$fieldName]) ? $_POST[$fieldName] : '' ?></textarea>
          </li>
        <?php break;
      case 'checkbox': ?>
          <li class="c-order__list--item c-order__checkbox js-orderFromItem" <?php echo !empty($field['conditionType']) && $field['conditionType'] !== 'always'  ? $hide : ''; ?>>
              <label title="<?php echo $field['placeholder'] ?>">
                  <input
                          type="checkbox"
                          name="<?php echo $fieldName ?>"
                          title="<?php echo $field['placeholder'] ?>"
                    <?php echo $field['required'] ?>>
                <?php echo $field['title'] ?>
              </label>
          </li>
        <?php break;
      case 'select': ?>
          <li class="c-order__list--item c-order-field c-order__select js-orderFromItem flex100" <?php echo !empty($field['conditionType']) && $field['conditionType'] !== 'always'  ? $hide : ''; ?>>
              <h4 class="c-order-field__title"><?php echo $field['title'] ?></h4>
              <select name="<?php echo $fieldName ?>"
                      title="<?php echo $field['placeholder'] ? $field['placeholder'] : $field['title'] ?>" <?php echo $field['required'] ?>>
                <?php foreach ($field['vars'] as $option) { ?>
                  <?php $selected = (isset($_POST[$fieldName]) && $_POST[$fieldName] == $option['val']) ? 'selected' : ''; ?>
                    <option value="<?php echo $option['val'] ?>" <?php echo $selected ?>><?php echo $option['text'] ?></option>
                <?php } ?>
              </select>
          </li>
        <?php break;
      case 'radiobutton': ?>
          <li class="c-order__list--item c-order__radiobutton c-order-field js-orderFromItem flex100" <?php echo !empty($field['conditionType']) && $field['conditionType'] !== 'always'  ? $hide : ''; ?>>
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
          <li class="c-order__list--item js-orderFromItem"
              style="white-space: nowrap;" <?php echo !empty($field['conditionType']) && $field['conditionType'] !== 'always'  ? $hide : ''; ?>>
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
