<?php
if (MG::getSetting('printCurrencySelector') == 'true') {
  mgAddMeta('components/select/currency/currency.js');
  mgAddMeta('components/select/select.css');

  $currencyActive = MG::getSetting('currencyActive');
  $currencyShopIso = MG::get('dbCurrency'); ?>

    <label class="select__wrap">
        <svg class="select__icon icon icon--currency">
            <use xmlns:xlink="http://www.w3.org/1999/xlink"
                 xlink:href="#icon--currency"></use>
        </svg>

        <select name="userCustomCurrency"
                class="select"
                id="js-currency-select"
                aria-label="Выбор валюты сайта">
          <?php foreach (MG::getSetting('currencyShort') as $k => $v) {
            if (!in_array($k, $currencyActive) && $k != $currencyShopIso) {
              continue;
            } ?>
              <option value="<?php echo $k ?>" <?php echo ($k == $_SESSION['userCurrency']) ? 'selected' : '' ?>>
                <?php echo $v ?>
              </option>
          <?php } ?>
        </select>
    </label>

<?php } ?>