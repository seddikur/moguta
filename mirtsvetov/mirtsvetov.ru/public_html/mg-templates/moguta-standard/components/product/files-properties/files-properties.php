<?php mgAddMeta('components/product/files-properties/files-properties.css'); ?>

<ul class="prop-string">
  <?php
  // Группированные хар-ки  
  $separator = '|';
  foreach ($data['groupProperty'] as $item): ?>

      <li class="name-group">
        <?php echo $item['name_group']; ?>
      </li>

    <?php foreach ($item['property'] as $prop): 
          if(empty($prop['name_prop'])){ continue; }
    ?>
        <span class="prop-name">
          <span class="prop-name__inner">
            <?php echo $prop['key_prop'] ?>&nbsp;
          </span>
        </span>

      <?php
       $arrFiles = explode($separator, $prop['name_prop']);
       foreach($arrFiles as $fileRelLink): 
        
         $fileLinkParts = explode('/', $fileRelLink);
         $fileName = array_pop($fileLinkParts);
         $fileAbsoluteLink = SITE.$fileRelLink;
       ?>             
          <li>
            <span>
                <span class="prop-spec__inner">
                  <a style="margin-left: 5px;" href="<?php echo $fileAbsoluteLink; ?>" target="_blank"><?php echo $fileName; ?></a>
                </span>
            </span>
          </li>
      <?php endforeach; ?>
    <?php endforeach; ?>
  <?php endforeach; ?>


  <?php
  // Негруппированные хар-ки  
  foreach ($data['unGroupProperty'] as $item): ?>
  <?php
    if (!empty($item['name'])||$item['name']==='0'): ?>
    
      <span class="prop-name">
        <span class="prop-name__inner">
          <?php echo $item['name_prop'] ?>&nbsp;
        </span>
      </span>

    <?php
      $arrFiles = explode($separator, $item['name']);
      foreach($arrFiles as $fileRelLink): 
       
        $fileLinkParts = explode('/', $fileRelLink);
        $fileName = array_pop($fileLinkParts);
        $fileAbsoluteLink = SITE.$fileRelLink;
    ?>        
        <li>
          <span>
              <span class="prop-spec__inner">
                <a style="margin-left: 5px;" href="<?php echo $fileAbsoluteLink; ?>" target="_blank"><?php echo $fileName; ?></a>
              </span>
          </span>
      </li>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endforeach; ?>
</ul>