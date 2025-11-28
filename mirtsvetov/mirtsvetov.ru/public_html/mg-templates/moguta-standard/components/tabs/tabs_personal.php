<?php mgAddMeta('components/tabs/tabs.css'); ?>
<?php mgAddMeta('components/tabs/tabs.js'); ?>

<div class="js-tabs-container c-tab">
    <div class="c-tab__nav"
         role="tablist"
         aria-label="<?php echo lang('tabsPersonalLabel'); ?>">
        <button class="js-open-tab c-tab__link c-tab__link--active"
                aria-selected="true"
                aria-controls="c-tab__orders"
                tabindex="-1"
                id="c-tab-btn-orders"
                role="tab"
                title="<?php echo lang('personalTab3'); ?>">
          <?php echo lang('personalTab3'); ?>
        </button>
        <button class="js-open-tab c-tab__link"
                aria-selected="false"
                aria-controls="c-tab__data"
                tabindex="0"
                id="c-tab-btn-data"
                role="tab"
                title="<?php echo lang('personalTab1'); ?>">
          <?php echo lang('personalTab1'); ?>
        </button>
        <button class="js-open-tab c-tab__link"
                aria-selected="false"
                aria-controls="c-tab__password"
                tabindex="0"
                id="c-tab-btn-password"
                role="tab"
                title="<?php echo lang('personalTab2'); ?>">
          <?php echo lang('personalTab2'); ?>
        </button>
        <a class="button c-tab__link c-tab__link--logout"
           tabindex="-1"
           title="<?php echo lang('personalTab4'); ?>"
           href="<?php echo SITE ?>/enter?logout=1">
          <?php echo lang('personalTab4'); ?>
        </a>
    </div>

    <!-- c-tab__orders - start -->
    <section class="js-tab-content c-tab__content c-tab__content--active"
             tabindex="0"
             role="tabpanel"
             aria-labelledby="c-tab-btn-orders"
             id="c-tab__orders">
      <?php
      // История заказов
      component('personal/history', $data);
      ?>
    </section>
    <!-- c-tab__orders - end -->

    <!-- c-tab__data - start -->
    <section class="js-tab-content c-tab__content"
             tabindex="0"
             role="tabpanel"
             aria-labelledby="c-tab__data"
             hidden
             id="c-tab__data">
      <?php
      // Информация о пользователе
      component('personal/info', $data);
      ?>
    </section>
    <!-- c-tab__data - end -->

    <!-- c-tab__password - start -->
    <section class="js-tab-content c-tab__content"
             tabindex="0"
             role="tabpanel"
             aria-labelledby="c-tab-btn-password"
             hidden
             id="c-tab__password">
      <?php
      // Смена пароля пользователя
      component('personal/password', $data);
      ?>
    </section>
    <!-- c-tab__password - end -->

</div>