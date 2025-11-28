<?php $i=0; foreach ($data as $page) : ?>
    <?php if ($page['invisible'] == "1") {continue;} ?>
    <?php if (URL::getUrl() == $page['link'] || URL::getUrl() == $page['link'] . '/') {$active = 'active';} else {$active = '';}?>
    <?php if (isset($page['child'])): ?>
        <li class="<?php if($i>4) echo 'hidden';?> has-sub <?php echo $active;?>">
            <a href="<?php echo $page['link']; ?>">
                <?php echo MG::contextEditor('page', $page['title'], $page["id"], "page"); ?>
            </a>
            <ul class="level-2">            
                <?php foreach ($page['child'] as $pageLevel1):?>
                    <?php if ($pageLevel1['invisible'] == "1") {continue;} ?>
                    <?php if (isset($pageLevel1['child'])):?>
                        <?php foreach ($pageLevel1['child'] as $pageLevel2):?>
                            <li class="has-sub <?php echo $active;?>">
                                <a href="<?php echo $pageLevel1['link']; ?>">
                                    <?php echo MG::contextEditor('page', $pageLevel1['title'], $pageLevel1["id"], "page"); ?>
                                </a>
                                <ul class="level-3">
                                    <?php foreach ($pageLevel1['child'] as $pageLevel2) : ?>
                                        <?php if ($pageLevel2['invisible'] == "1") {continue;} ?>
                                        <li class="<?php echo $active;?>">
                                            <a href="<?php echo $pageLevel2['link']; ?>">
                                                <?php echo MG::contextEditor('page', $pageLevel2['title'], $pageLevel2["id"], "page"); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php endforeach;?>
                    <?php else:?>
                        <li>
                            <a href="<?php echo $pageLevel1['link']; ?>">
                                <?php echo MG::contextEditor('page', $pageLevel1['title'], $pageLevel1["id"], "page"); ?>
                            </a>
                        </li>
                    <?php endif;?>
                <?php endforeach;?>
            </ul>
        </li>
    <?php else: ?>
        <li class="<?php if($i>4) echo 'hidden'; echo $active;?>">
            <a href="<?php echo urldecode($page['link']); ?>">
                <?php echo MG::contextEditor('page', $page['title'], $page["id"], "page"); ?>
            </a>
        </li>
    <?php endif; ?>
<?php $i++; endforeach; ?>