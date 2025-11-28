<?php mgAddMeta('components/tabs/tabs.css'); ?>
<?php mgAddMeta('components/tabs/tabs.js'); ?>

<div class="js-tabs-container c-tab">
    <div class="c-tab__nav"
         role="tablist"
         aria-label="<?php echo lang('tabsLabel'); ?>">
        <?php if (!empty($data['description']) && $data['description'] !== '&nbsp;'): ?>
        <button class="js-open-tab c-tab__link c-tab__link--active"
                role="tab"
                id="c-tab-btn-tab1"
                aria-selected="true"
                tabindex="-1"
                aria-controls="c-tab__tab1">
          <?php echo lang('productDescription'); ?>
        </button>
        <?php endif; ?>

      <?php if (!empty($data['stringsProperties'])): ?>
          <button class="js-open-tab c-tab__link"
                  role="tab"
                  id="c-tab-btn-property"
                  aria-selected="false"
                  tabindex="0"
                  aria-controls="c-tab__property">
            <?php echo lang('productCharacteristics'); ?>
          </button>
      <?php endif; ?>

      <?php if (!empty($data['filesProperties'])) : ?>
          <button class="js-open-tab c-tab__link"
                  role="tab"
                  id="c-tab-btn-files-property"
                  aria-selected="false"
                  tabindex="0"
                  aria-controls="c-tab__files-property">
            <?php echo lang('productFilesCharacteristics'); ?>
          </button>
      <?php endif; ?>

      <?php if (class_exists('ProductCommentsRating')) { ?>
          <button class="js-open-tab c-tab__link"
                  role="tab"
                  id="c-tab-btn-comments-rating"
                  aria-selected="false"
                  tabindex="0"
                  aria-controls="c-tab__comments-rating">
            <?php echo lang('productCommentsRating'); ?>
              <span class="reviews__count">
                    [mg-product-count-comments item="<?php echo (MG::getSetting('shortLink') == 'true' ? '' : $data['category_url']) . '/' . $data['url'] ?>"]
                </span>
          </button>
      <?php } else if (class_exists('CommentsToMoguta')) { ?>
          <button class="js-open-tab c-tab__link"
                  role="tab"
                  id="c-tab-btn-comments-mg"
                  aria-selected="false"
                  tabindex="0"
                  aria-controls="c-tab__comments-mg">
            <?php echo lang('productComments'); ?>
          </button>
      <?php } else if (class_exists('mgTreelikeComments')) {?>
          <button class="js-open-tab c-tab__link"
                  role="tab"
                  id="c-tab-btn-tree-comments"
                  aria-selected="false"
                  tabindex="0"
                  aria-controls="c-tab__tree-comments">
            <?php echo lang('productComments'); ?>
          </button>
      <?php } ?>

      

      <?php foreach ($data['thisUserFields'] as $key => $value) {
        if ($value['type'] == 'textarea' && $value['value']) { ?>
            <button class="js-open-tab c-tab__link"
                    role="tab"
                    id="c-tab-btn-<?php echo $key ?>"
                    aria-selected="false"
                    tabindex="0"
                    aria-controls="c-tab__tab<?php echo $key ?>">
              <?php echo $value['name'] ?>
            </button>
        <?php }
      } ?>
    </div>

    <?php if (!empty($data['description']) && $data['description'] !== '&nbsp;'): ?>
    <div class="js-tab-content c-tab__content c-tab__content--active"
             tabindex="0"
             role="tabpanel"
             aria-labelledby="c-tab-btn-tab1"
             id="c-tab__tab1"
             itemprop="description">
      <?php
      // Для работы спойлеров, созданный через визуальный редактор в панели управления
      component('spoiler-from-cke-support'); ?>
      <?php echo $data['description'] ?>
    </div>
    <?php endif; ?>


  <?php if (!empty($data['stringsProperties'])): ?>
      <div class="js-tab-content c-tab__content"
               tabindex="0"
               role="tabpanel"
               aria-labelledby="c-tab-btn-property"
               hidden
               id="c-tab__property">
        <?php
        // Вывод строковых характеристик
        component(
          'product/string-properties',
          $data['stringPropertiesSorted']
        );
        ?>
      </div>
  <?php endif; ?>

<?php if (!empty($data['filesProperties'])): ?>
    <div class="js-tab-content c-tab__content"
             tabindex="0"
             role="tabpanel"
             aria-labelledby="c-tab-btn-files-property"
             hidden
             id="c-tab__files-property">
      <?php
      // Вывод строковых характеристик
      component(
        'product/files-properties',
        $data['filesPropertiesSorted']
      );
      ?>
    </div>
<?php endif; ?>

  <?php if (class_exists('ProductCommentsRating')) {  ?>
      <div class="js-tab-content c-tab__content"
               tabindex="0"
               role="tabpanel"
               aria-labelledby="c-tab__comments-rating"
               hidden
               id="c-tab__comments-rating">
          [mg-product-comments-rating id="<?php echo $data['id'] ?>"]
      </div>
  <?php } else if (class_exists('CommentsToMoguta')){ ?>
      <div class="js-tab-content c-tab__content"
               tabindex="0"
               role="tabpanel"
               aria-labelledby="c-tab-btn-comments-mg"
               hidden
               id="c-tab__comments-mg">
          [comments]
      </div>
  <?php } else if (class_exists('mgTreelikeComments')) { ?>
      <div class="js-tab-content c-tab__content"
               tabindex="0"
               role="tabpanel"
               aria-labelledby="c-tab-btn-tree-comments"
               hidden
               id="c-tab__tree-comments">
          [mg-treelike-comments type="product"]
      </div>
  <?php } ?>

  <?php foreach ($data['thisUserFields'] as $key => $value) {
    if ($value['type'] == 'textarea') { ?>
        <div class="js-tab-content c-tab__content"
                 tabindex="0"
                 role="tabpanel"
                 aria-labelledby="c-tab-btn-<?php echo $key ?>"
                 hidden
                 id="c-tab__tab<?php echo $key ?>">
          <?php echo preg_replace('/\<br(\s*)?\/?\>/i', "\n", $value['value']) ?>
        </div>
    <?php }
  } ?>
</div>
