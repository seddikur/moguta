<div class="order-storage c-form" style="display: none;">
  <p class="c-order__title">
        <?php echo lang('storageShop'); ?>:
    </p>
  <?php
    unset($_SESSION['forDeferCart']);
    $mainStorage = MG::getMainStorage();
    $checkedStorage = $mainStorage;
    $mainStorageItem = current(array_filter($data, function($item) use ($mainStorage) { return $item['id'] == $mainStorage; }));
    if (!$mainStorageItem || !isset($mainStorageItem['data']) ||count($mainStorageItem['data']) < $cartCount) {
      $possibleStorages = array_filter($data, function($item) use ($cartCount) {
        $itemDataCount = 0;
        if (isset($item['data'])) {
          $itemDataCount = count($item['data']);
        }
        return $cartCount <= $itemDataCount;
      });
      if ($possibleStorages) {
        $checkedStorageItem = reset($possibleStorages);
        $checkedStorage = intval($checkedStorageItem['id']);
      }
    }
  ?>
    <?php
    foreach ($data as $item) {
      $checked = false;
      $_SESSION['forDeferCart'][] = $item;
      $isMainStorage = $item['id'] == $mainStorage;
      if($item['pickupPoint'] == 'true' || $isMainStorage){
        if ($item['id'] == $checkedStorage) {
          $checked = true;
        }
        echo "<label><input value='" . $item['id'] . "' type='radio' name='storage' required><span>" . htmlspecialchars($item['name']) . '</span></label>';
      }
    }
  ?>

</div>
