<div class="legal-item">

    <select name="select-legal-entity-order" class="select-legal-entity-order" autocomplete="off">
        <?php if (is_null($data['legal']['selected'])): ?>
            <option id="0" selected>Выбрать</option>
        <?php endif; ?>
        <?php foreach ($data['legal']['items'] as $item): ?>
            <option id="<?php echo $item['id'];?>" <?php if ($data['legal']['selected'] == $item['id']): ?>selected="selected"<?php endif; ?>><?php echo $item['name'];?></option>
        <?php endforeach; ?>
    </select>

    <?php if (!is_null($data['legal']['selected'])): ?>
        <input type="hidden" name="yur_info_nameyur" value="<?php echo $data['legal']['items'][$data['legal']['selected']]['name']; ?>">
        <input type="hidden" name="yur_info_inn" value="<?php echo $data['legal']['items'][$data['legal']['selected']]['inn']; ?>">
        <input type="hidden" name="yur_info_kpp" value="<?php echo $data['legal']['items'][$data['legal']['selected']]['kpp']; ?>">
    <?php endif; ?>
</div>