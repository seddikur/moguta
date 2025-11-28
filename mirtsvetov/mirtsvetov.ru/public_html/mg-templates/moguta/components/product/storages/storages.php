<?php
mgAddMeta('components/product/storages/storages.css');
if (MG::enabledStorage()) {
    $unit = $data['category_unit'];
    if (MG::get('controller') != 'controllers_product') {
        $displayNone = 'style="display:none"';
    }
    echo '<div class="storage-field a-storage" ' . $displayNone . '><table>';
    $storages = unserialize(stripcslashes(MG::getSetting('storages')));
    if (!empty($storages)) {
        foreach ($storages as $item) {
            if($item['showPublic'] == 'true'){
              $count = MG::getProductCountOnStorage(0, $data['id'], $data['variant'], $item['id']);
              echo "<tr class='sklad'>";
              echo "<td><p>" . htmlspecialchars($item['name']) . " " . htmlspecialchars($item['adress']) . "<span>" . htmlspecialchars($item['desc']) . "</span></p>" . "</td>" . "<td><p><strong class='count-on-storage' data-id='" . $item['id'] . "'>" . ($count != -1 ? $count : lang('countMany')) . "</strong><strong> " . ($count != -1 ? $unit : '') . "</strong></p></td>";
              echo "</tr>";           
            }
        }
    }
    echo '</table></div>';
}
