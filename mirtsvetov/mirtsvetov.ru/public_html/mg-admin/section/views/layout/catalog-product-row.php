<?php
goto functionsStart;// запись функции в переменную
layoutStart:

$activeColumns = $data['activeColumns'];
$moreButtonHolder = $data['moreButtonHolder'];
$listCategories = $data['listCategories'];
$data = $data['product'];

$currencyShort = MG::getSetting('currencyShort');
$currencyShopIso = MG::getSetting('currencyShopIso');
$currency = MG::getSetting('currency');
$data['currency_iso'] = $data['currency_iso'] ? $data['currency_iso'] : $currencyShopIso;

// показывать ли кнопку "Показать все"
if (!empty($data['variants']) && count($data['variants']) > 3) {
	$showBtn = true;
} else {
	$showBtn = false;
}

// отступы между элементами при показе цены в другой валюте
$marginTop = $marginToRightColumn = 3;
if (array_key_exists('price', $activeColumns)) {
	if (
		empty($data['variants']) &&
		MG::numberDeFormat($data['price']) != MG::numberDeFormat($data['real_price'])
	) {
		$marginToRightColumn = 21;
	}
	if (
		!empty($data['variants']) &&
		MG::numberDeFormat($data['variants'][0]['price']) != MG::numberDeFormat($data['variants'][0]['price_course'])
	) {
		$marginTop = 20;
	}
}

// получение локали веса
$rawWeightUnit = 'kg';
if (array_key_exists('weight', $activeColumns)) {
	if ($data['product_weightUnit']) {
		$weightUnit = $data['product_weightUnit'];
	} elseif ($data['category_weightUnit']) {
		$weightUnit = $data['category_weightUnit'];
	} else {
		$weightUnit = 'kg';
	}
	$rawWeightUnit = $weightUnit;

	$weightUnit = $lang['weightUnit_short_'.$weightUnit];
}

// подготовка данных
if (empty($data['variants'])) {
	if (array_key_exists('count', $activeColumns)) {
		if (!$data['count']) {
			$data['count'] = 0;
		}
		if ($data['count'] < 0) {
			$data['count'] = '&#8734;';
		}
	}

	if (array_key_exists('old_price', $activeColumns) && $data['old_price']) {
		$data['old_price'] = MG::convertCustomPrice($data['old_price'], $data['currency_iso'], 'get');
	}

	if (array_key_exists('weight', $activeColumns)) {
		$data['weight'] = MG::getWeightUnit('convert', array('from'=>'kg','to'=>$rawWeightUnit,'value'=>$data['weight']));
	}
} else {
	foreach ($data['variants'] as $key => $item) {
		if (array_key_exists('count', $activeColumns)) {
			if (!$item['count']) {
				$data['variants'][$key]['count'] = 0;
			}
			if ($item['count'] < 0) {
				$data['variants'][$key]['count'] = '&#8734;';
			}
		}

		if (array_key_exists('old_price', $activeColumns) && $item['old_price']) {
			$data['variants'][$key]['old_price'] = MG::convertCustomPrice($item['old_price'], $data['currency_iso'], 'get');
		}

		if (array_key_exists('weight', $activeColumns)) {
			$data['variants'][$key]['weight'] = MG::getWeightUnit('convert', array('from'=>'kg','to'=>$data['weight_unit'],'value'=>$item['weight']));
		}
	}
}

$tableRow = array();
?>
<tr id="<?php echo $data['id'] ?>"
	data-id="<?php echo $data['id'] ?>"
	class="product-row">
	<?php ob_start() ?>
	<td class="check-align text-center">
		<div class="checkbox">
			<input type="checkbox" id="prod-<?php echo $data['id'] ?>"
				   name="product-check">
			<label for="prod-<?php echo $data['id'] ?>"
				   class="shiftSelect"></label>
		</div>
	</td>
	<?php if (!array_key_exists('order', $activeColumns)) { ?>
		<td class="mover" <?php if (empty($_REQUEST['sorter']) || strpos($_REQUEST['sorter'], 'sort') !== 0) {echo 'style="display:none"';}?>>
			<span aria-label="Зажмите для перетаскивания"
					role="button"
					flow="leftUp"
					tooltip="Зажмите и перетащите товар для изменения порядка вывода в каталоге">
				<i class="fa fa-arrows"></i>
			</span>
		</td>
	<?php } ?>

	<?php $tableRow['checkboxes'] = ob_get_clean(); ?>

	<?php if (array_key_exists('number', $activeColumns)) {
		ob_start();
	?>
		<td class="id text-center"><?php echo $data['id'] ?></td>
	<?php
		$tableRow['number'] = ob_get_clean();
	} ?>

	<?php if (array_key_exists('code', $activeColumns)) {
		$tableRow['code'] = $variantDiv(
			'code', //field
			'min-small', //inputClass
			'', //unit
			true, //allowSave
			$data,
			$marginToRightColumn,
			$marginTop,
			$showBtn,
			$moreButtonHolder
		);
	} ?>

	<?php if (array_key_exists('category', $activeColumns)) {
		$path = (substr_count($data['category_url'], '/') > 1) ? '<span class="parentCat tooltip--small tooltip--center" tooltip="" flow="right" style="cursor:pointer;">../</span>' : '';
		ob_start();
	?>
		<td id="<?php echo $data['cat_id'] ?>" class="cat_id prod-cat">
			<?php
			echo $listCategories[$data['cat_id']] ? $path . "<a target='_blank' flow=\"right\" class='tooltip--center tooltip--small' tooltip='Перейти в категорию на сайте' href=\"" . SITE ."/". $data['category_url'] ."\">".$listCategories[$data['cat_id']]."</a>" : $lang['LAYOUT_CATALOG_63']; ?>
		</td>
	<?php
		$tableRow['category'] = ob_get_clean();
	} ?>

	<?php if (array_key_exists('img', $activeColumns)) {
		ob_start();
	?>
		<td class="product-picture image_url">
			<?php
			$imagesUrl = explode("|", $data['image_url']);

			if (!empty($imagesUrl[0])) {
				$src = mgImageProductPath($imagesUrl[0], $data["id"], 'small');
			}
			?>
			<img class="uploads" src="<?php echo $src ?>"/>
		</td>
	<?php
		$tableRow['img'] = ob_get_clean();
	} ?>


	<?php if (array_key_exists('title', $activeColumns)) {
		ob_start();
	$showcode = '';
	if (MG::getSetting('showCodeInCatalog') == 'true') {
		$showcode = $data['variant_exist'] ? ' ' : '[' . $data['code'] . '] ';
	} ?>
	<td class="name">
		<span class="product-name">
		  <button class="product-name__edit link name-link edit-row tooltip--small tooltip--center"
				  href="javascript:void(0);"
				  id="<?php echo $data['id'] ?>"
				  aria-label="<?php echo $lang['LAYOUT_CATALOG_64']; ?>"
				  tooltip="<?php echo $lang['LAYOUT_CATALOG_64']; ?>"
				  flow="up">
			  <span><?php echo $showcode . $data['title'] ?></span>
		  </button>
		  <a class="product-name__external-link js-external-link tooltip--small tooltip--center"
			 href="<?php echo $data['link'] ?>"
			 aria-label="<?php echo $lang['PRODUCT_VIEW_SITE']; ?>"
			 tooltip="<?php echo $lang['PRODUCT_VIEW_SITE']; ?>"
			 flow="up"
			 target="_blank">
			  <i class="fa fa-external-link tip"></i>
		  </a>
		</span>
	</td>
	<?php
		$tableRow['title'] = ob_get_clean();
	} ?>

	<?php if (array_key_exists('price', $activeColumns)) {
		ob_start();
	?>
		<td class="price <?php if($showBtn && $moreButtonHolder != 'price'){echo 'moreVariantsCount';} ?>">
			<div>
				<div class="variant-row-table">

					<?php if (MG::numberDeFormat($data['price']) != MG::numberDeFormat($data['real_price']) && empty($data['variants'])): ?>
						<div class="variant-price-container">
							<div colspan="3" class="text-right">
								<span class="view-price"
									  data-productId="<?php echo $data['id'] ?>"
									  style="color: <?php echo (MG::numberDeFormat($data['price']) > MG::numberDeFormat($data['real_price'])) ? '#1C9221' : '#B42020'; ?>; cursor: pointer; font-size: 12px;"
									  title="<?php echo $lang['LAYOUT_CATALOG_65']; ?>"><?php echo MG::priceCourse($data['price_course']) . ' ' . $currency ?></span>
								<div class="clear"></div>
							</div>
						</div>
					<?php endif; ?>
					<?php if (!empty($data['variants'])) { ?>
						<div>
						<?php
						foreach ($data['variants'] as $count => $item) {
							if ($count > 2) { ?>

								</div>
								<div class="second-block-varians" style="display:none;">
							<?php } ?>
							<?php if (MG::numberDeFormat($item['price']) != MG::numberDeFormat($item['price_course'])): ?>
								<div class="variant-price-container">
									<div colspan="3" class="text-right">
												<span class="view-price "
													  data-productId="<?php echo $item['id'] ?>"
													  style="color: <?php echo (MG::numberDeFormat($item['price']) < MG::numberDeFormat($item['price_course'])) ? '#1C9221' : '#B42020'; ?>; cursor: pointer;  font-size: 12px;"
													  title="<?php echo $lang['LAYOUT_CATALOG_65']; ?>"><?php echo MG::priceCourse($item['price_course']) . ' ' . $currency ?></span>
										<div class="clear"></div>
									</div>
								</div>
							<?php else: ?>
							<?php endif; ?>
							<?php $showcode = '';
							if (MG::getSetting('showCodeInCatalog') == 'true') {
								$showcode = '[' . $item['code'] . '] ';
							} ?>
							<?php
							echo '<div class="variant-price-row"><div class="text-right"><span class="price-help" style="white-space:nowrap;">' . $showcode . $item['title_variant'] . '</span></div><div><input  class="variant-price fastsave small variant-price" type="text" value="' . MG::numberDeFormat($item['price']) . '"  data-packet="{variant:1,id:' . $item['id'] . ',field:\'price\',curr:\''.$data['currency_iso'].'\'}"/></div><div>' . $currencyShort[$data['currency_iso']] . '</div></div>';
						}
						?>
						</div>
						<?php
					} else {
						echo ' <div class="variant-price-row"><div></div><div><input type="text" value="' . MG::numberDeFormat($data['real_price']) . '" class="fastsave small variant-price"  data-packet="{variant:0,id:' . $data['id'] . ',field:\'price\',curr:\''.$data['currency_iso'].'\'}"/></div><div> ' . $currencyShort[$data['currency_iso']] . '</div></div>';
					}
					?>
				</div>
			</div>
			<?php
			if ($showBtn && $moreButtonHolder == 'price') {
				echo '<div class="text-right"><button tooltip="Раскрыть все варианты" aria-label="Раскрыть все варианты" flow="up" class="link showAllVariants tooltip--center tooltip--small"><span>Показать все</span></button></div>';
			}
			?>
		</td>
	<?php
		$tableRow['price'] = ob_get_clean();
	} ?>

	<?php if (array_key_exists('old_price', $activeColumns)) {
		$tableRow['old_price'] = $variantDiv(
			'old_price', //field
			'small', //inputClass
			$currencyShort[$data['currency_iso']], //unit
			true, //allowSave
			$data,
			$marginToRightColumn,
			$marginTop,
			$showBtn,
			$moreButtonHolder
		);
	} ?>

	<?php if (array_key_exists('weight', $activeColumns)) {
		$tableRow['weight'] = $variantDiv(
			'weight', //field
			'small', //inputClass
			$weightUnit, //unit
			true, //allowSave
			$data,
			$marginToRightColumn,
			$marginTop,
			$showBtn,
			$moreButtonHolder
		);
	} ?>

	<?php if (array_key_exists('count', $activeColumns)) {
		$tableRow['count'] = $variantDiv(
			'count', //field
			'tiny js-fast-count', //inputClass
			$data['product_unit'] ? $data['product_unit'] : $data['category_unit'], //unit
			!MG::enabledStorage(), //allowSave
			$data,
			$marginToRightColumn,
			$marginTop,
			$showBtn,
			$moreButtonHolder
		);
	} ?>

	<?php if (array_key_exists('order', $activeColumns)) {
		ob_start();
	?>
		<td class="sort hide-for-small-only" style="padding-top:<?php echo $marginToRightColumn; ?>px">
			<input class="fastsave tiny" type="text"
				   value="<?php echo($data['sort']) ?>"
				   data-packet="{variant:0,id:<?php echo $data['id'] ?>,field:'sort'}"/>
		</td>
	<?php
		$tableRow['order'] = ob_get_clean();
	}

	$opf = Models_OpFieldsProduct::getFields();
	foreach ($opf as $op) {
		if (!array_key_exists('opf_'.$op['id'], $activeColumns)) {continue;}
		$tableRow['opf_'.$op['id']] = $variantDiv(
			'opf_'.$op['id'], //field
			'small', //inputClass
			'', //unit
			true, //allowSave
			$data,
			$marginToRightColumn,
			$marginTop,
			$showBtn,
			$moreButtonHolder
		);
	}
	?>

	<?php ob_start() ?>
	<td class="actions">
		<ul class="action-list fl-right">
			<li class="edit-row" id="<?php echo $data['id'] ?>">
				<a class="mg-open-modal tooltip--small tooltip--center"
				   href="javascript:void(0);"
				   aria-label="<?php echo $lang['EDIT']; ?>"
				   tooltip="<?php echo $lang['EDIT']; ?>"
				   flow="left">
					<i class="fa fa-pencil"></i>
				</a>
			</li>

			<?php if (USER::access('product') > 1) { ?>
				<li class="new" data-id="<?php echo $data['id'] ?>">
					<a role="button" href="javascript:void(0);"
					   flow="left"
					   aria-label="<?php echo $lang['PRINT_IN_NEW']; ?>"
					   tooltip="<?php echo $lang['PRINT_IN_NEW']; ?>"
					   class="tooltip--small tooltip--center <?php echo ($data['new']) ? 'active' : '' ?>">
						<i class="fa fa-tag"></i>
					</a>
				</li>
				<li class="recommend " data-id="<?php echo $data['id'] ?>">
					<a role="button" href="javascript:void(0);"
					   flow="left"
					   tooltip="<?php echo $lang['PRINT_IN_RECOMEND']; ?>"
					   aria-label="<?php echo $lang['PRINT_IN_RECOMEND']; ?>"
					   class="tooltip--small tooltip--center <?php echo ($data['recommend']) ? 'active' : '' ?>">
						<i class="fa fa-star"></i>
					</a>
				</li>
				<li class="clone-row" id="<?php echo $data['id'] ?>">
					<a class="tooltip--small tooltip--center"
					   href="javascript:void(0);"
					   aria-label="<?php echo $lang['CLONE']; ?>"
					   tooltip="<?php echo $lang['CLONE']; ?>"
					   flow="left">
						<i class="fa fa-files-o"></i>
					</a>
				</li>
				<li class="visible" data-id="<?php echo $data['id'] ?>">
					<a role="button" href="javascript:void(0);"
					   aria-label="<?php echo ($data['activity'] == 1) ? $lang['ACT_V_PROD'] : $lang['ACT_UNV_PROD']; ?>"
					   tooltip="<?php echo ($data['activity'] == 1) ? $lang['ACT_V_PROD'] : $lang['ACT_UNV_PROD']; ?>"
					   flow="left"
					   class="tooltip--center <?php echo ($data['activity'] == 1) ? 'active' : '' ?>">
						<i class="fa fa-lightbulb-o"></i>
					</a>
				</li>
				<li class="delete-order " id="<?php echo $data['id'] ?>">
					<a class="tooltip--xs tooltip--center"
					   href="javascript:void(0);"
					   aria-label="<?php echo $lang['DELETE']; ?>"
					   tooltip="<?php echo $lang['DELETE']; ?>"
					   flow="left">
						<i class="fa fa-trash"></i>
					</a>
				</li>
			<?php } ?>
		</ul>
	</td>
	<?php $tableRow['actions'] = ob_get_clean(); ?>
	<?php
		echo $tableRow['checkboxes'];
		foreach ($activeColumns as $key => $trash) {
			echo $tableRow[$key];
		}
		echo $tableRow['actions'];
	?>
</tr><?php

functionsStart:
$variantDiv = function ($field, $inputClass, $unit, $allowSave, $data, $marginToRightColumn, $marginTop, $showBtn, $moreButtonHolder) {
  ob_start(); ?>
    <td class="text-center <?php if ($showBtn && $moreButtonHolder != $field) {
      echo 'moreVariantsCount';
    } ?>"
        style="padding-top:<?php echo $marginToRightColumn; ?>px">
      <?php
      if (!empty($data['variants'])) { ?>
          <div>
        <?php foreach ($data['variants'] as $count => $item) {
          if ($count > 2) { ?>
              </div><div class="second-block-varians" style="display:none;">
          <?php } ?>
          <?php if ($allowSave) { ?>
                  <div style="margin:<?php echo $marginTop ?>px 0 4px 0;"
                       class="count nowrap">
                      <input class="fastsave <?php echo $inputClass ?>"
                             type="text"
                             value="<?php echo $item[$field] ?>"
                             data-packet="{variant:1,id:'<?php echo $item['id'] ?>',field:'<?php echo $field ?>'}"
                  />
                    <?php if ($unit) { echo $unit; } ?>
                  </div>
          <?php } else { ?>
                  <div class="count count_on-storage">
                    <?php
                    $prinval = str_replace('.00','',$item[$field]);
                    if(substr($prinval, -1)==='0' && strlen($prinval)>1 && strpos($prinval,',')!==false){
                      $prinval = substr($prinval,0,-1);
                    }
                    echo $prinval; ?>
                    <?php if ($unit) { echo $unit; } ?>
                  </div>
          <?php } ?>
        <?php } ?>
          </div>
      <?php } else { ?>
          <div style="margin: 2px 0; display: flex; align-items: center"
               class="count">
            <?php if ($allowSave) { ?>
                <input type="text"
                       value="<?php echo $data[$field] ?>"
                       class="fastsave <?php echo $inputClass ?>"
                       data-packet="{variant:0,id:'<?php echo $data['id'] ?>',field:'<?php echo $field ?>'}"
                />
              <?php if ($unit) {
                echo $unit;
              } ?>
            <?php } else { ?>

              <?php
              $prinval = str_replace('.00','',$data[$field]);
              if(substr($prinval, -1)==='0' && strlen($prinval)>1 && strpos($prinval,',')!==false){
                $prinval = substr($prinval,0,-1);
              }
              echo $prinval; ?>

              <?php if ($unit) {
                echo $unit;
              } ?>
            <?php } ?>
          </div>
      <?php }
      if ($showBtn && $moreButtonHolder == $field) { ?>
          <div class="text-right">
              <button tooltip="Раскрыть все варианты"
                      aria-label="Раскрыть все варианты"
                      flow="up"
                      class="link showAllVariants tooltip--center tooltip--small">
                  <span>Показать все</span>
              </button>
          </div>
      <?php } ?>
    </td>
  <?php return ob_get_clean();
};

if (!isset($currencyShopIso)) {goto layoutStart;}
