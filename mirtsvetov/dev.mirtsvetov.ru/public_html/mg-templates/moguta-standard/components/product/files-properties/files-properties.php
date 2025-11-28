<?php mgAddMeta('components/product/files-properties/files-properties.css'); ?>

<ul class="prop-string">
  <?php
  // Группированные хар-ки
  foreach ($data['groupProperty'] as $item): ?>

      <li class="name-group">
        <?php echo $item['name_group']; ?>
      </li>

    <?php foreach ($item['property'] as $prop):
      
        $fileRelLink = $prop['name_prop'];
        $fileLinkParts = explode('/', $fileRelLink);
        $fileName = array_pop($fileLinkParts);
        $fileAbsoluteLink = SITE.$fileRelLink;
      ?>
          <li class="prop-item">
            <span class="prop-name">
                <span class="prop-name__inner">
                    <?php echo $prop['key_prop'] ?>&nbsp;
                </span>
            </span>
            <span class="prop-spec">
                <span class="prop-spec__inner">
                  <a style="margin-left: 5px;" href="<?php echo $fileAbsoluteLink; ?>" target="_blank"><?php echo $fileName; ?></a>

                </span>
            </span>
          </li>
    <?php
    endforeach; ?>

  <?php
  endforeach; ?>


  <?php
  // Негруппированные хар-ки
  foreach ($data['unGroupProperty'] as $item): ?>
  <?php
    if (!empty($item['name'])||$item['name']==='0'):
    
    $fileRelLink = $item['name'];
    $fileLinkParts = explode('/', $fileRelLink);
    $fileName = array_pop($fileLinkParts);
    $fileAbsoluteLink = SITE.$fileRelLink;
  ?>
      <li class="prop-item nogroup">
            <span class="prop-name">
                <span class="prop-name__inner">
                    <?php echo $item['name_prop'] ?>&nbsp;
                </span>
            </span>
            <span class="prop-spec">
                <span class="prop-spec__inner">
                  <a style="margin-left: 5px;" href="<?php echo $fileAbsoluteLink; ?>" target="_blank"><?php echo $fileName; ?></a>
                </span>
            </span>
      </li>
  <?php endif; ?>
  <?php
  endforeach; ?>
</ul>