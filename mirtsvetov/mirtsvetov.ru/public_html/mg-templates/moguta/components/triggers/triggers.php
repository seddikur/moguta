<?php
// Отключаем стандартные стили плагина

use Slim\View;
mgExcludeMeta('/mg-plugins/trigger-guarantee/css/style.css');
// Подключаем стили компонентв
mgAddMeta('lib/owlcarousel/owl.carousel.min.js');
mgAddMeta('components/triggers/triggers.css');
mgAddMeta('components/triggers/triggers.js');

$triggerId = MG::get('templateParams')['TRIGGERS']['trigger_id'];


// Получаем элементы триггеров из базы
$result = DB::query("SELECT * FROM `" . PREFIX . "trigger-guarantee-elements` WHERE parent=" . DB::quote($triggerId));
while ($trigger = DB::fetchAssoc($result)) {
  $triggers[] = $trigger;
}

if ($triggers) {
  // Сортируем по полю sort
  usort($triggers, function ($a, $b) {
    return $a['sort'] - $b['sort'];
  });
}

// Для отладки
// console_log($triggerInfo);
// console_log($triggers);

?>
<div class="advantage-overflow">
  <div class="advantage">
    <div class="advantage__wrapper block-wrapper js-triggers-carousel">
      <?php $triggersCount = $triggers ? count($triggers) : 0; ?>
      <?php foreach ($triggers as $trigger) : ?>
        <div class="advantage__card">
          <div class="advantage__image">
            <?php echo str_replace('#SITE#', SITE, $trigger['icon']); ?>
          </div>
          <div class="advantage__content">
            <div class="advantage__subtitle">
              <?php echo htmlspecialchars_decode($trigger['text']) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
  </div>
</div>