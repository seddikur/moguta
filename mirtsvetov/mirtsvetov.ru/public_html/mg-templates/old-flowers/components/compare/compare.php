<?php mgAddMeta('components/compare/compare.js');?>

<?php if (!empty($data['catalogItems'])) { ?>
    <div class="mg-compare-products js-compare-page flex space-between">
        <!-- top - start -->
        <div class="top-part">
            <?php if (!empty($_SESSION['compareList'])) { ?>

                <div class="c-compare__top__select   mg-category-list-compare">
                    <?php if (MG::getSetting('compareCategory') != 'true') { ?>
                        <form class="c-form c-form--width">
                            <select name="viewCategory" onChange="this.form.submit()">
                                <?php foreach ($data['arrCategoryTitle'] as $id => $value): ?>
                                    <option value='<?php echo $id ?>' <?php
                                    if ($_GET['viewCategory'] == $id) {
                                        echo "selected=selected";
                                    }
                                    ?> ><?php echo $value ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php } ?>
                </div>

            <?php } ?>
        </div>
        <!-- top - end -->


        <!-- items - start -->
        <div class="mg-compare-product-wrapper">

            <?php if (!empty($data['catalogItems'])) {
                $dataProperty = [];
                foreach ($data['catalogItems'] as $item) { ?>
                    <div class="c-goods__item c-compare__item mg-compare-product js-compare-item">
                        <div class="c-goods__left">
                            <a class="c-compare__remove mp-remove-compared-product"
                               href="<?php echo SITE ?>/compare?delCompareProductId=<?php echo $item['id'] ?>">
                                удалить
                            </a>
                            <a class="img" href="<?php echo $item['link'] ?>">
                                <?php echo mgImageProduct($item); ?>
                            </a>
                        </div>
                        <div class="c-goods__right">
                            <a class="c-goods__title" href="<?php echo $item['link'] ?>">
                                <?php echo $item['title'] ?>
                            </a>
                            <?php 
                            foreach ($item['propertyForm']['stringsProperties']['unGroupProperty'] as $v) {
                                $dataProperty['unGroupProperty'][$v['name_prop']][$item['id']] = $v['name'];
                            }
                            foreach ($item['propertyForm']['stringsProperties']['groupProperty'] as $key => $val) {
                                foreach($val['property'] as $k => $v){
                                    $dataProperty[$val['name_group']][$v['key_prop']][$item['id']] = $v['name_prop'];
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php $prodIds[] = $item['id'];
                }
            } ?>
        </div>
        <!-- items - end -->
        <?php
            foreach ($dataProperty as $group => $propName) {
                foreach ($propName as $name => $prop) {
                    foreach ($prodIds as $id) {
                        if (empty($prop[$id])) {
                            $dataProperty[$group][$name][$id] = '-';
                            ksort($dataProperty[$group][$name]);
                        }
                    }
                }
            }
        ?>
        <!-- right table - start -->
        <div class="right-part">
            <?php $i=1; foreach ($dataProperty as $group => $propName) { 
                $nullCell = 0; // для пустых ячеек
                ?>
                <?php foreach ($propName as $name => $prop) { ?>
                    <?php if($nullCell == 0 && $group != 'unGroupProperty'):?>
                        <div class="mg-compare-fake-table-row group-name-cell">
                            <?php 
                                while($nullCell < count($prop)){
                                    echo '<div class="mg-compare-fake-table-cell"></div>';
                                    $nullCell++;
                                }
                            ?>
                        </div> 
                    <?php endif; ?>
                    <div data-row="<?php echo $i;?>" class="mg-compare-fake-table-row">
                        <?php foreach ($prop as $id => $value) { ?>
                            <div class="td mg-compare-fake-table-cell">
                                <?php echo $value;?>
                            </div>
                        <?php } ?>
                    </div>
                <?php $i++; } ?>
            <?php } ?>
        </div>
        <!-- right table - start -->


        <!-- left block - start -->
        <div class="left-part">
            <!-- left table - start -->
            <div class="mg-compare-fake-table">
                <div class="mg-compare-fake-table-left <?php echo $data['moreThanThree'] ?>">
                    <?php $i=1; 
                     foreach ($dataProperty as $group => $propName) { ?>
                          <?php if($group != 'unGroupProperty'): ?>
                              <div class="mg-compare-fake-table-row group-name-cell">
                                <div class="mg-compare-fake-table-cell">
                                    <?php echo $group ?>
                                </div>
                              </div>
                          <?php endif; ?>
                        <?php foreach ($propName as $name => $prop) { ?>
                            <div data-row="<?php echo $i;?>" class="mg-compare-fake-table-row">
                                <div class="mg-compare-fake-table-cell <?php if (trim($data['property'][$name]) !== '') : ?>with-tooltip<?php endif; ?>">
    
                                    <div class="compare-text" title="<?php echo $name ?>">
                                        <?php echo $name ?>
                                    </div>
                                    <?php if (trim($data['property'][$name]) !== '') : ?>
                                        <div class="mg-tooltip">?
                                            <div class="mg-tooltip-content"
                                                style="display:none;"><?php echo $data['property'][$name] ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php $i++; } ?>
                    <?php } ?>
                </div>
            </div>
            <!-- left table - end -->
        </div>
        <!-- left block - end -->
    </div>
<?php } ?>
<!-- compare - end -->