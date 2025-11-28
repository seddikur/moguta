<?php //mgAddMeta('<script src="'.SITE. '/' . self::$path . '/js/initBrands.js"></script>') ;?>
<div class="brands">
	<?php if (!empty($options['slider_options']['head'])): ?>
	<div class="c-carousel__title">
		<span class="c-carousel__title--span"><?=$options['slider_options']['head']?></span>
	</div>
	<?php endif; ?>
	<div class="brands__carousel" data-init-name="InitBrands">
		<?php foreach ($entity as $row): ?>
			<?php
				//Обратная совместимость с полными путями на картинки
				if (stripos($row['url'], 'http') !== FALSE) {
					$img_src = $row['url'];
				} else {
					$img_src = SITE.'/'.$row['url'];
				}
			?>
			<div class="brands__item">
				<a class="brands__link"
				   href="<?php echo SITE.'/'.$row['short_url'] ?>"
				   title="<?php echo $row['brand'] ?>">
					<img class="brands__img"
						 src="<?php echo $img_src ?>"
						 alt="<?php echo $row['img_alt']?>"
						 title="<?php echo $row['img_title']?>"
						 aria-label="<?php echo $row['img_title']?>">
				</a>
			</div>
        <?php endforeach; ?>
	</div>
</div>
<script>
var brandSliderConfig = {
    items: <?php echo $options['slider_options']['items']?$options['slider_options']['items']:"3"?>,
    <?php if ($responsive != null): ?>
    responsive: {
    <?php foreach ($responsive as $key => $value): ?>
        <?php echo $key ?> : { items   : <?php echo $value ?> },
    <?php endforeach; ?>
    },
    <?php endif; ?>
    <?php if (!empty($options['slider_options']['nav'])): ?>
    nav         : <?php echo $options['slider_options']['nav']?>,
    <?php endif; ?>
    margin		: <?php echo $options['slider_options']['margin']?$options['slider_options']['margin']:"10"?>,
    <?php if (!empty($options['slider_options']['mouseDrag'])): ?>
    mouseDrag   : <?php echo $options['slider_options']['mouseDrag']?>,
    <?php endif; ?>
    <?php if (!empty($options['slider_options']['touchDrag'])): ?>
    touchDrag   : <?php echo $options['slider_options']['touchDrag']?>,
    <?php endif; ?>
    <?php if (!empty($options['slider_options']['loop'])): ?>
    loop        : <?php echo $options['slider_options']['loop']?>,
    <?php endif; ?>
    <?php if (!empty($options['slider_options']['autoplay'])): ?>
    autoplay    : <?php echo $options['slider_options']['autoplay']?>,
    <?php endif; ?>
}
</script>
