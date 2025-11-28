<div class="section-<?php echo $pluginName ?> slider-admin slider-admin_is_loading js-slider-toggle" style="display: none">
    <!--  Прелоадер  -->
<!--    <div class="slider-admin__preloader">-->
<!--        <div class="sk-folding-cube">-->
<!--            <div class="sk-cube1 sk-cube"></div>-->
<!--            <div class="sk-cube2 sk-cube"></div>-->
<!--            <div class="sk-cube4 sk-cube"></div>-->
<!--            <div class="sk-cube3 sk-cube"></div>-->
<!--        </div>-->
<!--    </div>-->

    <div class="reveal-overlay slider-admin__modal slider-modal" style="display:none;">
        <div class="reveal xssmall" id="slide-modal" style="display:block;">
            <button class="close-button closeModal js-close-slide-modal" type="button"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button> <!-- Кнопка закрытия модалки -->
            <div class="reveal-header">
                <!-- заголовок модалки -->
                <h4 class="pages-table-icon" id="modalTitle"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php echo $lang['HEADER_MODAL_ADD']; ?></h4>
            </div>
            <div class="reveal-body slider-modal__content">
                <!-- тело модалки -->
                <div class="widget-body slide-editor">
                    <!-- Содержимое окна, управляющие элементы -->
                    <?php $list = $data['modal_template']; ?>
                    <div class="js-slide-template-options">
                        <?php include('temp.php') ?>
                    </div>
                    <?php $list = $data['modal_image']; ?>
                    <div class="js-slide-image-options">
                    <ul class="slider-opt-list">
                    <?php include('temp.php') ?>
                    </ul>
                    </div>
                    <?php $list = $data['modal_html']; ?>
                    <div class="js-slide-html-options">
                    <?php include('temp.php') ?>
                    </div>
                </div>
            </div>
            <div class="reveal-footer widget-footer">
                <label class="widget-footer__option footer-option" style="margin: 0;">
                    <strong class="footer-option__title">
                        <?php echo $lang['SLIDE_TYPE_DEFAULT']; ?>:
                    </strong>
                    <select name="type" class="footer-option__select">
<!--                        <option value="none" selected disabled>Не выбран</option>-->
                        <option value="template"><?php echo $lang['SLIDE_TYPE_TEMPLATE']; ?></option>
                        <option value="image"><?php echo $lang['SLIDE_TYPE_IMAGE']; ?></option>
                        <option value="html"><?php echo $lang['SLIDE_TYPE_HTML']; ?></option>
                    </select>
                </label>
                <button class="button success js-save-slide"
                        data-id="new">
                    <i class="fa fa-floppy-o"
                       aria-hidden="true"></i>
                    <?php echo $lang['SAVE'] ?>
                </button>
            </div>
        </div>
    </div>

    <ul class="slider-admin__tabs slider-tabs template-tabs-menu inline-list tabs custom-tabs">
        <li class="template-tabs tabs-title">
            <a class="new-slider js-select-slider slider-tabs__item_new"
               href="javascript:void(0);"
               role="button"
               data-id="new">
                <i class="fa fa-plus" aria-hidden="true"
                   style="font-size: 12px;color: #4caf50;"></i>&nbsp;
                <?php echo $lang['NEW_SLIDER']; ?>
            </a>
        </li>
        <?php foreach ($sliders as $slide) : ?>
        <li class="template-tabs tabs-title">
            <a class="js-select-slider slider-tabs__item"
               href="javascript:void(0);"
               role="button"
               data-id="<?php echo $slide['id'] ?>">
                <?php echo $slide['name_slider'] ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="preview-slide__wrap">
        <div class="slider-admin__preview preview-slide js-preview-container">
            <span class="preview-slide__placeholder">
                <a role="button" href="javascript:void(0)" class="preview-slide__empty js-new-slide">
                    <?php echo $lang['EMPTY_SLIDER']; ?>
                </a>
            </span>
        </div>

        <div class="preview-slide__btns slide-btns">
            <button class="tooltip--small tooltip--center slide-btns__item slide-btns__item_edit js-edit-slide"
                    style="display: none;"
                    tooltip="<?php echo $lang['EDIT_SLIDE']; ?>"
                    flow="left">
                <i class="fa fa-2x fa-edit" aria-hidden="true"></i>
            </button>
            <button class="tooltip--xs tooltip--center slide-btns__item js-new-slide"
                    tooltip="<?php echo $lang['NEW_SLIDE']; ?>"
                    flow="left">
                <i class="fa fa-2x fa-plus" aria-hidden="true"></i>
            </button>
            <button class="tooltip--small tooltip--center slide-btns__item js-copy-slide"
                    tooltip="<?php echo $lang['COPY_SLIDE']; ?>"
                    flow="left">
                <i class="fa fa-2x fa-copy" aria-hidden="true"></i>
            </button>
            <button class="tooltip--xs tooltip--center slide-btns__item js-delete-slide"
                    style="display: none;"
                    tooltip="<?php echo $lang['DELETE_SLIDE']; ?>"
                    flow="left">
                <i class="fa fa-2x fa-trash" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <ul class="slider-admin__sort js-slider-sort slides-sorter"></ul>

    <ul class="accordion"
        data-accordion=""
        data-multi-expand="true"
        data-allow-all-closed="true">
        <li class="accordion-item"
            data-accordion-item="">
            <a class="accordion-title content_blog_acc js-options"
               href="javascript:void(0);">
                Настройки слайдера
            </a>
            <div class="accordion-content">
                <div class="settings-btns">
                    <button class="link js-delete-slider settings-btns__item settings-btns__item_delete" data-id="new" style="display: none">
                        <span>
                            <?php echo $lang['DELETE_SLIDER']; ?>
                        </span>
                    </button>

                    <button class="link js-reset-slider settings-btns__item">
                        <span>
                            <?php echo $lang['RESET_SLIDER']; ?>
                        </span>
                    </button>
                </div>

                <?php $list = $data['options'] ?>
                <ul class="slider-opt-list slider-admin__options js-slider-opt">
                <?php include('temp.php'); ?>
                </ul>
            </div>
        </li>
    </ul>

    <div class="slider-admin__footer">
        <div class="slider-admin__shortcode slider-empty-short-code js-slider-empty-shortcode">
            <span>Сохраните слайдер, чтобы получить шорткод</span>
        </div>
        <div class="slider-admin__shortcode slider-short-code js-slider-shortcode">
            <label class="slider-short-code__label">
                Шорткод для вставки слайдера на сайт:
                <input type="text" readonly
                       class="slider-short-code__input js-short-code-copy-from">
            </label>
            <button class="slider-short-code__btn link js-short-code-copy tooltip--small tooltip--center"
                    flow="up">
                <i class="fa fa-copy" aria-hidden="true"></i>
                <span>Скопировать</span>
            </button>
        </div>
        <button class="button js-save-slider success" data-id="new"><?php echo $lang['SAVE_SLIDER']; ?></button>
    </div>

</div>
<script>
    $(document).ready(function() {
        var lastSliderID = cookie('lastSlider'),
            lastSlider = $('.js-select-slider[data-id="'+lastSliderID+'"]');

        if (lastSlider.length) {
            lastSlider.click();
        } else {
            $('.js-select-slider[data-id="new"]').click();
            cookie('lastSlider', '');
        }

        // Прелоадер
        setTimeout(function(){
                $('.js-slider-toggle').slideDown();
            },
            1
        );
    });
</script>




<!-- Element where elFinder will be created (REQUIRED) -->
