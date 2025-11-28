<?php if ($data): ?>
    <div class="status-block">
        <div class="item">
            <span>Задолженность:</span>
            <p><b>Мир</b> - <?php echo MG::numberFormat($data['mir']) . ' ' . MG::getSetting('currency'); ?></p>
            <p><b>РМ</b> - <?php echo MG::numberFormat($data['rm']) . ' ' . MG::getSetting('currency'); ?></p>
            <p><b>ТК</b> - <?php echo MG::numberFormat($data['tk']) . ' ' . MG::getSetting('currency'); ?></p>
        </div>

        <div class="item">
            <span>Не возвращено тары:</span>
            <p><b>Баков</b> - <?php echo  MG::numberFormat($data['tare_tank']); ?></p>
            <p><b>Крышек</b> - <?php echo  MG::numberFormat($data['tare_cover']);?></p>
        </div>

    </div>
<?php endif; ?>