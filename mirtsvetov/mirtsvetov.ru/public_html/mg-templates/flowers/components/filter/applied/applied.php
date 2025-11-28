<?php

    //исключаем бренды из фильтров
    for ($i=0; $i < count($data); $i++) { 
        if ($data[$i]['name'] === "Бренды[prop attr=плагин]") {
            unset($data[$i]);
        }
    }

    if (empty($data)) {
        $style = ' style="display:none"';
    } else {
        $style = '';
    } 
?>

<div class="apply-filter-line">
    <form action="?" class="apply-filter-form" data-print-res="<?php echo MG::getSetting('printFilterResult') ?>" <?php echo $style ?>>
        <ul class="filter-tags flex">
            <?php foreach ($data as $property): $cellCount = 0; ?>
                <?php if($property['code']=='price_course') continue;?>
                <li class="apply-filter-item">
                    <span class="filter-property-name">
                        <?php echo $property['name'] .": "; ?>
                    </span>

                    <?php if (in_array($property['values'][0], array('slider|easy', 'slider|hard', 'slider'))): ?>
                        <span class="filter-price-range">
                            <?php echo lang('filterFrom') . "&nbsp;" . $property['values'][1] . "&nbsp;" . lang('filterTo') . "&nbsp;" . $property['values'][2]; ?>
                            <a role="button" href="javascript:void(0);" class="removeFilter">
                                <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#plus"></use></svg>
                            </a>
                        </span>

                        <?php if ($property['code'] != 'price_course'): ?>
                            <input name="<?php echo $property['code'] . "[" . $cellCount . "]" ?>"
                                   value="<?php echo $property['values'][0] ?>" type="hidden"/>
                            <?php $cellCount++; ?>
                        <?php endif; ?>

                        <input name="<?php echo $property['code'] . "[" . $cellCount . "]" ?>"
                               value="<?php echo $property['values'][1] ?>" type="hidden"/>
                        <input name="<?php echo $property['code'] . "[" . ($cellCount + 1) . "]" ?>"
                               value="<?php echo $property['values'][2] ?>" type="hidden"/>
                    <?php else: ?>
                        <ul class="filter-values flex">
                            <?php foreach ($property['values'] as $cell => $value): ?>
                                <li class="apply-filter-item-value flex align-center">
                                    <?php echo $value['name']; ?>
                                    <a role="button" href="javascript:void(0);" class="removeFilter"></a>
                                    <input name="<?php echo $property['code'] . "[" . $cell . "]" ?>"
                                           value="<?php echo $property['values'][$cell]['val'] ?>" type="hidden"/>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>

            <?php endforeach; ?>
            <a href="<?php echo SITE . URL::getClearUri(); ?>" class="refreshFilter"><?php echo lang('filterReset'); ?></a>
        </ul>
        <input type="hidden" name="applyFilter" value="1"/>
    </form>
</div>