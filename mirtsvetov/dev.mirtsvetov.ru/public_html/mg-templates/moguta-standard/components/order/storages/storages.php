<?php
$render = false;
foreach ($data as $item) {
  if ($item['pickupPoint'] == 'true') {
    $render = true;
    break;
  }
} ?>

<div class="order-storage c-form" style="display: none;">
  <?php if ($render) { ?>
  <p class="c-order__title">
        <?php echo lang('storageShop'); ?>:
    </p>
    <?php } ?>
  <?php $mainStorage = MG::getMainStorage(); ?>
    <?php
    console_log($data);
    unset($_SESSION['forDeferCart']);
    foreach ($data as $item) {
        if($item['pickupPoint'] == 'true'){
          $_SESSION['forDeferCart'][] = $item;
          echo "<label><input value='" . $item['id'] . "' type='radio' name='storage'><span>" . htmlspecialchars($item['name']) . '</span></label>';
        }
    }
   echo "<label class='active' style = 'display:none;'><input value='" . $mainStorage . "' type='radio' id = 'main_storage_id' name='storage' autofocus checked><span>" . $mainStorage . '</span></label>';
    ?>

</div>
