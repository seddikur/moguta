<div class="order-storage c-form" style="display: none;">
    <p class="c-order__title">
        <?php echo lang('storageShop'); ?>:
    </p>
  <?php $mainStorage = MG::getMainStorage(); ?>
    <?php
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