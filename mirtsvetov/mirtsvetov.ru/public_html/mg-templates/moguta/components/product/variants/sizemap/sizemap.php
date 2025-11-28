<?php mgAddMeta('components/product/variants/sizemap/sizemap.css'); ?>

<?php if (!empty($data['sizeMap'])) {
  $color = $colorFull = $sizeFull = $size = '';
  $countColor = 0;

  foreach ($data['sizeMap'] as $item) {
    MG::loadLocaleData($item['id'], LANG, 'property_data', $item);
    if ($item['type'] == 'color') {
      $countColor++;
      if ($item['img']) {
        ob_start(); ?>
        <div class="color"
             data-id="<?php echo $item['id']?>"
             style="background: url(<?php echo SITE.'/'.$item['img']?>);background-size:cover;"
             title="<?php echo $item['name']?>">
        </div>
        <?php
        $color .= ob_get_clean();
      } else {
        ob_start(); ?>
        <div class="color"
             data-id="<?php echo $item['id']?>"
             style="background-color:<?php echo $item['color']?>"
             title="<?php echo $item['name']?>">
        </div>
        <?php
        $color .= ob_get_clean();
      }
      $colorName = $item['pName'];
    }
    if ($item['type'] == 'size') {
      ob_start(); ?>
      <div class="size" data-id="<?php echo $item['id']?>">
        <span><?php echo $item['name']?></span>
      </div>
      <?php
      $size .= ob_get_clean();
      $sizeName = $item['pName'];
    }
  }

  if ($color && ($countColor > 1 || MG::getSetting('printOneColor') == 'true')) {
    $colorFull = '<div class="color-block"><span>' . $colorName . ':</span>' . $color . '</div>';
  }
  if ($size) {
    $sizeFull = '<div class="size-block"><span>' . $sizeName . ':</span>' . $size . '</div>';
  }

  if (MG::getSetting('sizeMapMod') == 'size') {
    $sizeMap = $sizeFull.$colorFull;
  } else {
    $sizeMap = $colorFull.$sizeFull;
  }
  ?>
  <div class="sizeMap-row">
    <?php echo $sizeMap?>
  </div>
<?php } ?>