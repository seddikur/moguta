<!-- Тут начинается Верстка модального окна для csv выгрузки -->
<div class="reveal-overlay">
  <div class="reveal xssmall" id="csv-export-modal" style="display:block;">
    <button id="exportCsvCloseModal" class="close-button closeModal" type="button"><i class="fa fa-times-circle-o"></i></button>
    <div class="reveal-header">
      <h2><span id="modalTitle"><?php echo $lang['IN_CSV']; ?></span></h2>
    </div>
    <div class="reveal-body">
      <div class="large-12 columns inline-label">

        <div class="row sett-line js-settline-toggle">
          <div class="small-10 medium-6 columns">
            <label for="exportCsvFromAllCats" class="dashed"><?php echo $lang['IN_CSV_ALL_CATS']; ?></label>
          </div>
          <div class="small-2 medium-6 columns checkbox margin">
            <input type="checkbox" name="exportCsvFromAllCats" id="exportCsvFromAllCats" checked>
            <label class="label-checkbox-bottom" for="exportCsvFromAllCats"></label>
          </div>
        </div>

        <div id="exportCsvCategoriesBlock" class="row sett-line js-settline-toggle" style="display: none;">
          <div class="small-10 medium-6 columns">
            <label for="exportCsvCategoriesSelect" class="dashed"><?php echo $lang['IN_CSV_CATS']; ?></label>
          </div>
          <div class="small-12 medium-6 columns">
            <div class="medium-12">
              <select id="exportCsvCategoriesSelect" class="category-select js-comboBox" width="100%" style="height:30px;" name="exportCsvCategoriesSelect" multiple>
                <?php
                $arrayCategories = MG::get('category')->getHierarchyCategory(0);
                $selectCategories = MG::get('category')->getTitleCategory($arrayCategories, 0);
                $selectCategories = '<option  data-parent=0 value=0  >'.$lang['CAT_ROOT'].'</option>' . $selectCategories;
                echo $selectCategories;
                ?>
              </select>
            </div>
            <div class="small-12 medium-12 mg-debug-plugins-btns">
              <button id="exportCsvCategoriesNo" class="mg-debug-plugins-btns__item link">
                <i aria-hidden="true" class="fa fa-close"></i>
                <span><?php echo $lang['EDIT_ORDER_15']; ?></span>
              </button>
              <button id="exportCsvCategoriesAll" class="mg-debug-plugins-btns__item link">
                <i aria-hidden="true" class="fa fa-check"></i>
                <span><?php echo $lang['VK_LOCALE_12']; ?></span>
              </button>
            </div>
          </div>
        </div>

        <div class="row sett-line js-settline-toggle">
          <div class="small-10 medium-6 columns">
            <label for="exportCsvEncoding" class="dashed"><?php echo $lang['CSV_ENCODING']; ?></label>
          </div>
          <div class="small-2 medium-6 columns">
            <select name="exportCsvEncoding" id="exportCsvEncoding">
              <option value="win1251" selected>WINDOWS-1251</option>
              <option value="utf8">UTF-8</option>
            </select>
          </div>
        </div>

      </div>
    </div>
    <div class="reveal-footer">
      <button id="exportCsvButton" class="button success"><i class="fa fa-download"></i> <span><?php echo $lang['IN_CSV']; ?></span></button>
    </div>
  </div>
</div>
<!-- Тут заканчивается Верстка модального окна -->