<div class="section-<?php echo $pluginName?>">
    <div class="wrapper-slider-setting" style="margin:1em">
       <?php if (function_exists('imagewebp') == true):?>
           <h2>Ваш сервер <b>поддерживает</b> WebP</h2>
           <p>Примеры шорткодов:</p>
           <xmp><img src="[webp url="полный_путь_до_картинки"]"></xmp>
           <xmp><div style="background-image:url([webp url="полный_путь_до_картинки"])"></div></xmp>
      <?php else:?>
          <h2>Ваш сервер <b>не поддерживает</b> WebP</h2>
       <?php endif; ?>
    </div>
</div>