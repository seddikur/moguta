<div class="legal-item js-order-address">

    <?php if (!empty($data['address'])): ?>

        <?php if (!is_null($data['legal']['selected'])): ?>

            <select name="select-legal-entity-address-order" class="select-legal-entity-address-order" autocomplete="off">
                <?php if (is_null($data['address']['selected'])): ?>
                    <option id="0">Выбрать</option>
                <?php endif; ?>
                <?php foreach ($data['address']['items'] as $item): ?>
                    <option id="<?php echo $item['id'];?>" <?php if ($data['address']['selected'] == $item['id']): ?>selected<?php endif; ?>><?php echo $item['address'];?></option>
                <?php endforeach; ?>
            </select>

            <?php if (!is_null($data['address']['selected'])): ?>
                <input type="hidden" name="address" value="<?php echo $data['address']['items'][$data['address']['selected']]['address']; ?>">
            <?php endif; ?>
        <?php else: ?>

            <div class="legals__empty">Пожалуйста, выберите юридическое лицо. </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="legals__empty">Нет доступного адреса доставки.</div>

    <?php endif; ?>
</div>