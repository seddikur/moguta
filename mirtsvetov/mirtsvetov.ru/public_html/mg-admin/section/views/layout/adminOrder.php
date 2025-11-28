<?php
MG::set('controller', 'controllers_product'); // Да простят меня боги за этот костыль (с) Санёк
MG::setSizeMapToData($data);
MG::set('controller', 'controllers_ajax');
?>

<?php if (!empty($data['blockVariants'])) { ?>
    <div class="c-variant block-variants">
        <div class="c-variant__title">
            <?php if ($data['sizeMap'] == '') {
                echo lang('variantTitle');
            } ?>
        </div>
        <div class="c-variant__scroll">
            <?php if ($data['sizeMap'] != '') {
                echo '<div class="sizeMap-row">';
                $color = '';
                $countColor = 0;
                foreach ($data['sizeMap'] as $item) {
                    MG::loadLocaleData($item['id'], LANG, 'property_data', $item);
                    if ($item['type'] == 'color') {
                        $countColor++;
                        if ($item['img']) {
                            $color .= '<div class="color" data-id="' . $item['id'] . '" style="background:url(' . SITE . '/' . $item['img'] . ');background-size:cover;" title="' . $item['name'] . '"></div>';
                        } else {
                            $color .= '<div class="color" data-id="' . $item['id'] . '" style="background-color:' . $item['color'] . ';" title="' . $item['name'] . '"></div>';
                        }
                        $colorName = $item['pName'];
                    }
                }

                if (($color != '')) {
                    $colorTmp = explode('[prop attr=', $colorName);
                    if (isset($colorTmp[1])) {
                        $colorTmp2 = explode(']', $colorTmp[1]);
                        if ($colorTmp2[0]) {
                            $colorTmp2 = ' (' . $colorTmp2[0] . ')';
                        } else {
                            unset($colorTmp2);
                        }
                        $colorName = $colorTmp[0] . $colorTmp2;
                    }
                    $colorFull = '<div class="color-block"><span class="color-block__title">' . $colorName . ':</span>' . $color . '</div>';
                } else {
                    $colorFull = '';
                }

                $size = '';
                foreach ($data['sizeMap'] as $item) {
                    MG::loadLocaleData($item['id'], LANG, 'property_data', $item);
                    if ($item['type'] == 'size') {
                        $size .= '<div class="size" data-id="' . $item['id'] . '"><span>' . $item['name'] . '</span></div>';
                        $sizeName = $item['pName'];
                    }
                }
                if ($size != '') {
                    $sizeTmp = explode('[prop attr=', $sizeName);
                    if (isset($sizeTmp[1])) {
                        $sizeTmp2 = explode(']', $sizeTmp[1]);
                        if ($sizeTmp2[0]) {
                            $sizeTmp2 = ' (' . $sizeTmp2[0] . ')';
                        } else {
                            unset($sizeTmp2);
                        }
                        $sizeName = $sizeTmp[0] . $sizeTmp2;
                    }
                    $sizeFull = '<div class="size-block"><span class="size-block__title">' . $sizeName . ':</span>' . $size . '</div>';
                } else {
                    $sizeFull = '';
                }

                echo $sizeFull;
                echo $colorFull;

                echo '</div>';
            }
            $i = 0;
            ?>
        </div>
    </div>
<?php } ?>


<div class="c-form">
    <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true">
        <?php if (!empty($data['blockVariants'])) {
            $j = 0 ?>
            <li class="accordion-item" data-accordion-item="" <?php if ($data['sizeMap'] != '') echo "style='display:none;'" ?>><a class="accordion-title"
                                                                 href="javascript:void(0);"><?php echo $lang['options']; ?></a>
                <div class="accordion-content" data-tab-content="">
                    <div class="c-variant block-variants">
                        <div class="c-variant__scroll">
                            <table class="variants-table">
                                <?php
                                $i = 0;
                                foreach ($data['blockVariants'] as $variant) {

                                    $count = MG::getProductCountOnStorage($variant['count'], $variant['product_id'], $variant['id'], 'all');
                                    ?>
                                    <tr class="js-check-variant c-variant__row variant-tr <?php echo !$j++ ? 'active-var' : '' ?>"
                                        data-count="<?php echo $count; ?>" data-color="<?php echo $variant['color'] ?>"
                                        data-size="<?php echo $variant['size'] ?>">
                                        <td class="c-variant__column c-variant__column_radio">
                                            <input type="radio" id="variant-<?php echo $variant['id']; ?>"
                                                   class="js-variant-radio-btn"
                                                   aria-label="Выбрать вариант"
                                                   data-count="<?php echo $count; ?>" name="variant"
                                                   value="<?php echo $variant['id']; ?>"
                                                <?php echo !$i++ ? 'checked=checked' : '' ?>>
                                        </td>
                                        <td>
                                            <?php $src = mgImageProductPath($variant['image'], $variant['product_id'], 'small');
                                            echo !empty($variant['image']) ? '
                                              <span class="c-variant__img"><img src="' . $src . '" alt="image"></span>
                                          ' : '' ?>
                                        </td>
                                        <td>
                                          <span class="c-variant__name">
                                              <?php echo $variant['title_variant'] ?>
                                          </span>
                                        </td>
                                        <td>
                                          <span class="c-variant__price <?php if ($variant['activity'] === "0" || $variant['count'] == 0) {
                                              echo 'c-variant__price--none';
                                          } ?>">
                                              <span class="c-variant__price--current">
                                                  <?php echo $variant['price'] ?><?php echo MG::getSetting('currency') ?>
                                              </span>
                                          </span>
                                        </td>
                                        <td>
                                          <span class="c-variant__count">
                                              Кол-во: <?php echo ($variant['count'] != '-1') ? $variant['count'] : '∞' ?>
                                          </span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                </div>
            </li>
        <?php } ?>
        <li class="accordion-item additprop addiProps" data-accordion-item="">
            <a class="accordion-title" href="javascript:void(0);">
                <?php echo $lang['additionalChars']; ?>
            </a>
            <div class="accordion-content" data-tab-content="">
                <?php
                foreach ($data['htmlProperty'] as $prop) {
                    $tmp = explode('[', $prop['name']);
                    $prop['name'] = $tmp[0];
                    switch ($prop['type']) {
                        case 'assortment-select': ?>

                            <div class="select-type">
                                <strong class="property-title">
                                    <?php echo $prop['name']; ?><span class="property-delimiter">:</span>
                                </strong><br>
                                <select aria-label="Выберите дополнительную характеристику"
                                        name="<?php echo $prop['name']; ?>"
                                        class="last-items-dropdown mg__prop_select">
                                    <?php foreach ($prop['additional'] as $option) {
                                        echo '<option value="' . $option['value'] . '" ' . $option['selected'] . '>' . $option['itemName'] . $option['price'] . '</option>';
                                    } ?>
                                </select>
                            </div>

                            <?php break;
                        case 'assortment-radio': ?>
                            <p class="mg__prop_p_radio">
                                <strong class="property-title">
                                    <?php echo $prop['name']; ?><span class="property-delimiter">:</span>
                                </strong>
                                <br/>
                                <?php foreach ($prop['additional'] as $option) { ?>
                                    <label class="mg__prop_label_radio <?php ($option['checked'] ? 'active' : ''); ?>">
                                        <input class="mg__prop_radio" type="radio"
                                               name="<?php echo $option['name']; ?>" <?php echo $option['checked']; ?>>
                                        <span class="label-black"><?php echo $option['itemName'] . "<b>".$option['price']."</b>"; ?></span>
                                    </label>
                                <?php } ?>
                            </p>
                            <?php break;
                        case 'assortment-checkBox': ?>

                            <div>
                                <strong class="property-title">
                                    <?php echo $prop['name']; ?>
                                </strong>
                                <span class="property-delimiter">:</span>
                                <br/>

                                <?php foreach ($prop['additional'] as $option) { ?>
                                    <div class="sett-line product-info__property">
                                        <label class="dashed" style="margin-right: 5px;"
                                               for="input-<?php echo $option['name']; ?>">
                                            <?php echo $option['itemName'] . $option['price']; ?>
                                        </label>
                                        <div class="checkbox">
                                            <input class="mg__prop_check"
                                                   type="checkbox"
                                                   id="input-<?php echo $option['name']; ?>"
                                                   name="<?php echo $option['name']; ?>">
                                            <label for="input-<?php echo $option['name']; ?>"></label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                            <?php break;
                        default:
                            break;
                    }
                }
                ?>
            </div>
        </li>
    </ul>
    <ul class="propsFrom">
        <?php foreach ($data['stringsProperties']['groupProperty'] as $item): ?>
            <li class="name-group"><?php echo $item['name_group']; ?></li>

            <?php foreach ($item['property'] as $prop): ?>
                <li class="prop-item">
                    <span class="prop-name"><?php echo trim($prop['key_prop']) ?></span>
                    <span class="prop-spec">:
                            <?php echo $prop['name_prop'] ?>
                            <span class="prop-unit"><?php echo $prop['unit'] ?></span>
                        </span>
                </li>
            <?php endforeach; ?>

        <?php endforeach; ?>

        <?php foreach ($data['stringsProperties']['unGroupProperty'] as $item): ?>
            <li class="prop-item nogroup">
                <strong class="prop-name">
                    <?php echo $item['name_prop'] ?>:
                </strong>
                <span class="prop-spec">
                        <?php echo $item['name'] ?>
                        <span class="prop-unit">
                            <?php echo $item['unit'] ?>
                        </span>
                    </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<div class="buy-container">
    <div class="c-buy hidder-element">
        <input type="hidden" name="inCartProductId" value="<?php echo $data['id'] ?>">
        <div class="c-buy__buttons ">
            <div>
                <input aria-label="Количество товара" type="number"
                       class="amount_input amount_input_margin_none small"
                       name="amount_input"
                       data-max-count="<?php echo $data['maxCount'] ?>" value="1"/>
                <span class="orderUnit"></span>
            </div>
            <a rel="nofollow" class="addToCart product-buy"
               data-item-id="<?php echo $data["id"]; ?>">
                <?php echo $lang['addToOrder'] ?>
            </a>
        </div>
    </div>
</div>
