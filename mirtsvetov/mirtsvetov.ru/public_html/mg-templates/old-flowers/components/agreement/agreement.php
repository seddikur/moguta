<div class="agreement">
    <label class="agreement__label">
        <input class="agreement__checkbox js-agreement-checkbox-<?php echo $data['button']; ?>" type="checkbox" checked>
        <?php echo $data['text']; ?>
        <a class="show-modal" data-modal="modal-agreement"><?php echo $data['textLink']; ?></a>
    </label>
    <div class="modal" data-modal="modal-agreement">
        <div class="inner">
            <span class="close">Закрыть</span>
            <div class="h2-like">Соглашение на обработку персональных данных</div>
            <?php MG::get('pages')->getPageByUrl('polzovatelskoe-soglashenie');?>
        </div>
    </div>
  <div class="modal" data-modal="personal-data">
        <div class="inner">
            <span class="close">Закрыть</span>
            <div class="h2-like">Политика в отношении обработки персональных данных</div>
            <?php MG::get('pages')->getPageByUrl('politika-v-otnoshenii-obrabotki-personalnyh-dannyh');?>
        </div>
    </div>
</div>