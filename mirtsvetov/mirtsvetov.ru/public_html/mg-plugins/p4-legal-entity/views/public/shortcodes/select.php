<?php if (!is_null($data['legal'])): ?>

    <select name="legal_entity" class="select-legal-entity" autocomplete="off">
        <?php if (is_null($data['legal']['selected'])): ?>
            <option selected>Выберите юр лицо</option>
        <?php endif; ?>
        <?php foreach ($data['legal']['items'] as $item): ?>
            <option value="<?php echo $item['id'];?>" <?php if ($data['legal']['selected'] == $item['id']): ?>selected="selected"<?php endif; ?>><?php echo $item['name'];?></option>
        <?php endforeach; ?>
    </select>
<?php endif; ?>