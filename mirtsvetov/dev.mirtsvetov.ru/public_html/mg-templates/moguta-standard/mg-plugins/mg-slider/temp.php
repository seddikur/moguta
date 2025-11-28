<?php foreach ($list as $section): ?>
    <?php foreach ($section as $name => $option): ?>
        <?php echo (!isset($option['li'])) ? '<li>' : '' ?>
        <?php if ($option['type'] == 'select'): ?>
            <?php $prefix = !empty($option['name']) ? $option['name'] : $name; ?>
            <div class="slider-option js-slider-option">
                <label for="<?php echo $name ?>"
                       class="slider-option__title">
                    <span><?php echo $lang[strtoupper($name)]; ?>:
                        <?php if (!empty($option['tooltip'])) {
                            addTooltip($option['tooltip']);
                        } ?></span>
                </label>
                <div class="slider-option__value">
                    <?php
                    $classes = '';
                    if ($name === 'font') {
                        $classes .= 'js-font-select ';
                    }
                    if ($option['combobox'] == true) {
                        $classes .= 'js-comboBox ';
                    }
                    if (!empty($classes)) {
                        $classes = 'class="' . trim($classes) . '"';
                    }
                    ?>
                    <select name="<?php echo $name ?>"
                            id="<?php echo $name ?>"
                      <?php echo $classes ?>
                      <?php echo $option['combobox'] == true ? 'data-width="100%"' : '' ?>>
                        <?php if (isset($option['default']) && $option['default'] !== false): ?>
                            <option value="<?php echo !empty($option['default']) ? $option['default'] : '' ?>"
                                    selected>
                                <?php echo $lang[strtoupper($prefix) . '_DEFAULT']; ?>
                            </option>
                        <?php endif; ?>
                        <?php foreach ($option['list'] as $value) : ?>
                            <option value="<?php echo $value ?>"><?php echo(isset($option['not_locale']) ? $value : $lang[strtoupper($prefix . '_' . $value)]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php elseif ($option['type'] == 'checkbox'): ?>
            <div class="slider-option js-slider-option">
                <span class="slider-option__title">
                    <?php echo $lang[strtoupper($name)]; ?>:
                    <?php if (!empty($option['tooltip'])) {
                        addTooltip($option['tooltip']);
                    } ?>
                </span>
                <div class="checkbox margin slider-option__value">
                    <input type="checkbox"
                           name="<?php echo $name ?>"
                           id="<?php echo $name ?>">
                    <label for="<?php echo $name ?>"></label>
                </div>
            </div>
        <?php elseif ($option['type'] == 'color'): ?>
            <label class="slider-option js-slider-option">
                <span class="slider-option__title">
                    <?php echo $lang[strtoupper($name)]; ?>:
                    <?php if (!empty($option['tooltip'])) {
                        addTooltip($option['tooltip']);
                    } ?>
                </span>
                <div class="slider-option__value">
                    <div id="<?php echo $name ?>"></div>
                    <input type="hidden"
                           class="option"
                           name="<?php echo $name ?>">
                </div>
            </label>
        <?php elseif ($option['type'] == 'text'): ?>
            <label class="slider-option js-slider-option">
                <span class="slider-option__title">
                    <?php echo $lang[strtoupper($name)]; ?>:
                    <?php if (!empty($option['tooltip'])) {
                        addTooltip($option['tooltip']);
                    } ?>
                </span>
                <input type="text"
                       class="slider-option__value js-input-<?php echo $name ?>"
                  <?php echo !empty($option['placeholder']) ? 'placeholder="' . $option['placeholder'] . '"' : '' ?>
                       name="<?php echo $name ?>">
            </label>
        <?php elseif ($option['type'] == 'number'): ?>
            <label class="slider-option js-slider-option">
                 <span class="slider-option__title">
                     <?php echo $lang[strtoupper($name)]; ?>:
                     <?php if (!empty($option['tooltip'])) {
                         addTooltip($option['tooltip']);
                     } ?>
                 </span>
                <input type="number"
                       min=<?php echo $option['min'] ?>
                       <?php echo !empty($option['max']) ? 'max=' . $option['max'] : '' ?>
                       step=<?php echo $option['step'] ?>
                       class="slider-option__value"
                  <?php echo !empty($option['placeholder']) ? 'placeholder="' . $option['placeholder'] . '"' : '' ?>
                       name="<?php echo $name ?>">
            </label>
        <?php elseif ($option['type'] == 'textarea'): ?>
            <div>
                <?php if (!empty($option['ckeditor'])): ?>
                    <ul class="accordion"
                        data-accordion=""
                        data-multi-expand="true"
                        data-allow-all-closed="true">
                        <li class="accordion-item"
                            data-accordion-item="">
                            <a class="accordion-title js-ckeditor-<?php echo $name; ?>"
                               href="javascript:void(0);">
                                <?php echo $lang[strtoupper($name)]; ?>
                            </a>
                            <div class="accordion-content">
                            <textarea class="js-input-<?php echo $name ?>"
                                      name="<?php echo $name; ?>"
                                      id="<?php echo $name; ?>"></textarea>
                            </div>
                        </li>
                    </ul>
                    <script>
                        $('.admin-center').on('click', '.section-<?php echo self::$pluginName ?> .js-ckeditor-<?php echo $name; ?>', function() {
                            if (!CKEDITOR.instances['<?php echo $name; ?>']) {
                                CKEDITOR.replace('<?php echo $name; ?>', {
                                    toolbar: [
                                        {
                                            name: 'basicstyles',
                                            groups: ['basicstyles', 'cleanup'],
                                            items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'],
                                        },
                                        { name: 'colors', items: ['TextColor', 'BGColor'] },
                                        { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
                                        { name: 'document', items: ['Sourcedialog'] },
                                    ],
                                });
                            } else {
                                CKEDITOR.instances['<?php echo $name; ?>'].setData($('#<?php echo $name; ?>').val());
                            }
                        });
                    </script>
                <?php else: ?>
                    <label for="<?php echo $name; ?>"
                           class="slider-option__title"
                           style="margin: 0 0 10px;">
                        <?php echo $lang[strtoupper($name)]; ?>:
                        <?php if (!empty($option['tooltip'])) {
                            addTooltip($option['tooltip']);
                        } ?>
                    </label>
                    <textarea name="<?php echo $name; ?>"
                              id="<?php echo $name; ?>"></textarea>
                <?php endif; ?>
            </div>
        <?php elseif ($option['type'] == 'title'): ?>
            <h<?php echo $option['h'] ?>>
                <?php echo $option['value'] ?>
                <?php if (!empty($option['tooltip'])) {
                    addTooltip($option['tooltip']);
                } ?>
            </h<?php echo $option['h'] ?>>
        <?php elseif ($option['type'] == 'desc'): ?>
            <span class="section__desc">
                <?php echo $option['value'] ?>
            </span>
        <?php elseif ($option['type'] == 'img'): ?>
            <label class="slider-option js-slider-option">
                <span class="slider-option__title">
                    <?php echo $lang['CHANGE_IMG']; ?>:
                </span>
                <span class="slider-option__value">
                    <input type="hidden"
                           name="<?php echo $name ?>"
                           class="slider-option__value">
                    <button data-name="<?php echo $name ?>"
                            class="js-browseImage link">
                        <span><?php echo $lang['SELECT']; ?></span>
                    </button>
                </span>
            </label>
            <?php echo (!isset($option['li'])) ? '</li>' : '' ?>
            <?php echo (!isset($option['li'])) ? '<li>' : '' ?>

            <img alt="Превью изображения слайда"
                 src=""
                 style="display: none;"
                 class="slide-img-preview js-image-preview-<?php echo $name ?>">

        <?php elseif ($option['type'] == 'start_ac'): ?>
            <li class="accordion__wrap">
            <ul class="accordion"
                data-accordion=""
                data-multi-expand="true"
                data-allow-all-closed="true">
                <li class="accordion-item"
                    data-accordion-item="">
            <a class="accordion-title content_blog_acc"
               href="javascript:void(0);">
                <?php echo $option['value'] ?>
            </a>
            <div class="accordion-content">
        <?php elseif ($option['type'] == 'end_ac'): ?>
            </div>
            </li>
            </ul>
            </li>
        <?php elseif ($option['type'] == 'start_ul'): ?>
            <ul <?php echo !empty($option['classes']) ? 'class="' . $option['classes'] . '"' : ''; ?>>
        <?php elseif ($option['type'] == 'end_ul'): ?>
            </ul>
        <?php endif; ?>
        <?php echo (!isset($option['li'])) ? '</li>' : '' ?>
    <?php endforeach; ?>
<?php endforeach; ?>
