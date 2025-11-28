<?php foreach ($data['groupProperty'] as $item): ?>
    <li class="name-group"><?php echo $item['name_group']; ?></li>
    <?php foreach ($item['property'] as $prop): ?>
        <li class="prop-item flex nowrap space-between">
            <span class="prop-name"><?php echo $prop['key_prop'] ?></span>
            <span class="prop-separator"></span>
            <span class="prop-spec"><?php echo $prop['name_prop'] ?>&nbsp;<?php echo $prop['unit'] ?></span>
        </li>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php foreach ($data['unGroupProperty'] as $item): ?>
  <?php if (!empty($item['name'])||$item['name']==='0'): ?>
      <li class="prop-item flex nowrap space-between">
          <span class="prop-name"><?php echo $item['name_prop'] ?></span>
          <span class="prop-separator"></span>
          <span class="prop-spec"><?php echo $item['name'] ?>&nbsp;<?php echo $item['unit'] ?>
      </li>
  <?php endif;?>
<?php endforeach;?>