<ul class="fast-result-list">
	<?php  
	foreach ($data['items'] as $item) {
		$title = preg_replace('/'.preg_quote($data['keyword'], "/").'/i', "<b style=\"background:rgb(172, 207, 165)\">\$0</b>", $item['title']);
		if (!$item['category_url']) {$item['category_url'] = 'catalog/';}
		?>
		<li>
			<?php
			//Мультиязычность
			$selectLang = isset($_COOKIE["mg_to_script_langP"])? $_COOKIE["mg_to_script_langP"] : LANG;
			if ($selectLang != 'default' && $selectLang != 'LANG') {
				$item['category_url'] = $selectLang.'/'.$item['category_url'];
			}
			?>
			<a href="<?php echo SITE.'/'.preg_replace('~/+~', '/', $item['category_url'].'/'.$item['url']);?>">
				<div class="fast-result-img">
					<img src="<?php echo $item['image_url']; ?>"/>
				</div>
				<div class="fast-result-info">
					<?php echo $title; ?>
					<span><?php echo $item['price'].' '.$data['currency']; ?></span>
					<span class="variant-text"><?php if($item['variant_exist']) {lang('variantsExist');}?></span>
				</div>
			</a>
		</li>
	<?php } ?>
</ul>