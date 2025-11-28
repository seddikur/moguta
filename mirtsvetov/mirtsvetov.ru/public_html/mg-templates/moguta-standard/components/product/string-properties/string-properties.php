<?php mgAddMeta('components/product/string-properties/string-properties.css'); ?>

<ul class="prop-string">
  <?php
  // Группированные хар-ки
  foreach ($data['groupProperty'] as $item): ?>

      <li class="name-group">
        <?php echo $item['name_group']; ?>
      </li>

    <?php foreach ($item['property'] as $prop): ?>
          <li class="prop-item">
            <span class="prop-name">
                <span class="prop-name__inner">
                    <?php echo $prop['key_prop'] ?>&nbsp;
                </span>
            </span>
              <span class="prop-spec">
                <span class="prop-spec__inner">
                    &nbsp;<?php echo $prop['name_prop'] ?>
                    <span class="prop-unit">
                        <?php echo $prop['unit'] ?>
                    </span>
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
    if (!empty($item['name'])||$item['name']==='0'): ?>
      <li class="prop-item nogroup">
            <span class="prop-name">
                <span class="prop-name__inner">
                    <?php echo $item['name_prop'] ?>&nbsp;
                </span>
            </span>
        <span class="prop-spec">
            <span class="prop-spec__inner">
                &nbsp;<?php echo $item['name'] ?>
                <span class="prop-unit">
                    <?php echo $item['unit'] ?>
                </span>
            </span>
        </span>
      </li>
  <?php endif; ?>
  <?php
  endforeach; ?>
</ul>