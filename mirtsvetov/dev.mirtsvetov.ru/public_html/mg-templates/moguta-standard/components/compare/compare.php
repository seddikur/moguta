<!-- compare - start -->
<?php
mgAddMeta('lib/swiper/swiper-bundle.min.css');
mgAddMeta('lib/swiper/swiper-bundle.min.js');
mgAddMeta('components/compare/compare.js');
mgAddMeta('components/compare/compare.css');
//mg::loger($data);
?>
<div class="compare-page">
    <div class="container">
        <?php
        $compareTitle = !empty($data['catalogItems'])
            ? lang('compareProduct')
            : lang('compareProductEmpty');
        ?>
        <?php
        if (!empty($data['catalogItems'])) { ?>
            <h1 class="c-compare__title"><?php echo $compareTitle; ?></h1>
            <div class="c-compare mg-compare-products js-compare-page">
                <!-- top - start -->
                <div class="c-compare__top mg-compare-left-side">
                    <?php if (!empty($_SESSION['compareList'])) { ?>
                        <div class="c-compare__top-buttons">
                            <div class="c-compare__top__select mg-category-list-compare">
                                <?php if (MG::getSetting('compareCategory') != 'true') { ?>
                                    <form class="c-form c-form--width">
                                        <select class="c-compare-select" name="viewCategory" onChange="this.form.submit()">
                                            <?php foreach ($data['arrCategoryTitle'] as $id => $value) : ?>
                                                <option value='<?php echo $id ?>' <?php
                                                                                    if ($_GET['viewCategory'] == $id) {
                                                                                        echo "selected=selected";
                                                                                    }
                                                                                    ?>><?php echo $value ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php } ?>
                            </div>

                            <div class="c-compare__top__buttons">
                                <a class="default-btn c-compare__clear mg-clear-compared-products" href="<?php echo SITE ?>/compare?delCompare=1">
                                    <?php echo lang('compareClean'); ?>
                                </a>
                            </div>
                        </div>
                    <?php } ?>

                    <?php
                    if (!empty($data['catalogItems'])) {
                        $dataProperty = [];
                        foreach ($data['catalogItems'] as $item) {
                            foreach ($item['propertyForm']['stringsProperties']['unGroupProperty'] as $v) {
                                $dataProperty['unGroupProperty'][$v['name_prop']][$item['id']] = $v['name'];
                            }
                            foreach ($item['propertyForm']['stringsProperties']['groupProperty'] as $key => $val) {
                                foreach ($val['property'] as $k => $v) {
                                    $dataProperty['groupProperty'][$val['name_group']][$v['key_prop']][$item['id']] = $v['name_prop'];
                                }
                            }
                            $prodIds[] = $item['id'];
                        }
                    }
                    foreach ($dataProperty['unGroupProperty'] as $name => $prop) {
                        foreach ($prodIds as $id) {
                            if (empty($prop[$id])) {
                                $dataProperty['unGroupProperty'][$name][$id] = '-';
                                ksort($dataProperty['unGroupProperty'][$name]);
                            }
                        }
                    }
                    foreach ($dataProperty['groupProperty'] as $name => $props) {
                        foreach ($props as $key => $prop) {
                            foreach ($prodIds as $id) {
                                if (empty($prop[$id])) {
                                    $dataProperty['groupProperty'][$name][$key][$id] = '-';
                                    ksort($dataProperty['groupProperty'][$name][$key]);
                                }
                            }
                        }
                    }
                    if (!empty($data['catalogItems'])) {
                    ?>

                    <?php } ?>
                    <div class="c-compare__items-container">

                        <div class="c-compare__items swiper js-compare-swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($data['catalogItems'] as $item) { ?>
                                    <div class="swiper-slide">
                                        <?php component('compare/item', $item); ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="swiper-scrollbar"></div>
                        </div>
                    </div>
                </div>

                <div class="c-compare__right js-scroll-container">

                    <div class="c-compare__wrapper mg-compare-product-wrapper swiper js-compare-props-swiper">
                        <div class="swiper-wrapper">
                            <?php if (!empty($data['catalogItems'])) {
                                foreach ($data['catalogItems'] as $item) { ?>
                                    <div class="swiper-slide">
                                        <div class="compare-product__item c-compare__item mg-compare-product js-compare-item">
                                            <div class="c-compare__property">
                                                <?php echo $item['propertyForm'] ?>
                                            </div>
                                            <div class="c-compare__ungroup">
                                                <div class="c-compare__ungroup-title">
                                                    <?php echo lang('ungroupProps'); ?>
                                                </div>
                                                <?php
                                                foreach ($dataProperty['unGroupProperty']  as $name => $v) {
                                                ?>
                                                    <div class="c-compare__props">
                                                        <span class="c-compare__name"><?php echo $name; ?></span>
                                                        <span class="c-compare__value"><?php echo $v[$item['id']]; ?></span>
                                                    </div>
                                                <?php
                                                } ?>
                                            </div>
                                            <?php foreach ($dataProperty['groupProperty'] as $key => $val) {
                                            ?>
                                                <div class="c-compare__props">
                                                    <span class="c-compare__group-title">
                                                        <?php echo $key ?>
                                                    </span>
                                                </div>
                                                <?php
                                                foreach ($val as $k => $v) {
                                                ?>
                                                    <div class="c-compare__props">
                                                        <span class="c-compare__name"><?php echo $k ?></span>
                                                        <span class="c-compare__value"><?php echo $v[$item['id']] ?></span>
                                                    </div>
                                            <?php }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <div class="c-compare__empty">
                <?php if (MG::get('templateParams')['404_PAGE']['img_page'] != '') { ?>
                    <div class="c-compare-empty__img">
                        <img src="<?php echo SITE . '/' . MG::get('templateParams')['404_PAGE']['img_page'] ?>" alt="<?php echo lang('error404text'); ?>">
                    </div>
                <?php } ?>
                <h1 class="c-compare-empty__title">
                    <?php echo lang('compareProduct'); ?>
                </h1>
                <div class="c-compare-empty__content">
                    <span class="c-compare-empty__text"><?php echo lang('compareProductEmpty') ?></span>
                </div>
            <?php } ?>
            </div>
    </div>
</div>