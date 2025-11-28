<?php
    if (MG::enabledStorage()) {
        $unit = $data['category_unit'];
        if(MG::get('controller') != 'controllers_product') {
            $displayNone = 'style="display:none"';
        }
        $storages = unserialize(stripcslashes(MG::getSetting('storages')));
        $showBlock = false;
        foreach ($storages as $item) {
            if($item['showPublic'] == 'true'){
                $showBlock = true;
                break;
            }
        }
        echo $showBlock === true ? '<div class="storage-field" '.$displayNone.'><table>' : '';
        if(!empty($storages)) {
            foreach ($storages as $item) {
                if($item['showPublic'] == 'true'){
                    $count = MG::getProductCountOnStorage(0, $data['id'], $data['variant'], $item['id']);
                    echo "<tr class='sklad'>";	           
                    echo "<td><p>".$item['name']." ".$item['adress']."<span>".$item['desc']."</span></p>"."</td>"."<td><p><strong class='count-on-storage' data-id='".$item['id']."'>".($count!=-1?$count:lang('countMany'))."</strong><strong> ".($count!=-1?$unit:'')."</strong></p></td>";
                    echo "</tr>";
                }
			}
        }
        echo '</table></div>';
    }
?>
