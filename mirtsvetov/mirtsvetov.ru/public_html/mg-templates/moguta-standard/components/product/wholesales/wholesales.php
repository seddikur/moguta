<?php mgAddMeta('components/product/string-properties/string-properties.css'); ?>
<?php if(!empty($data['data'])){
    if(USER::access('wholesales') > 0 && $data['data']) { ?>
    <div class="whole-info">
        <ul class="prop-string">
            <li class="name-group">
                <?php
                switch ($data['type']) {
                    case 'sum':
                        echo lang('wholesalesTypeSum');
                        $unit = MG::getSetting('currency');
                        break;
                    case 'cartSum':
                        echo lang('wholesalesTypeCartSum');
                        $unit = MG::getSetting('currency');
                        break;

                    default:
                        echo lang('wholesalesTypeCount');
                        $unit = $data['unit'];
                        break;
                }
                ?>
                <span class="prop-price"><?php echo lang('applyFilterPrice'); ?></span>
            </li>
            <?php foreach ($data['data'] as $item) { ?>
                <li class="prop-item">
                    <span class="prop-name"><?php echo $item['count'].' '.$unit; ?></span>
                    <span class="prop-unit"><?php echo $item['price']; ?></span>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php }
} ?>
