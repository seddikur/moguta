<?php foreach ($data as $category): ?>
	<?php if ($category['invisible'] == "1") {continue;} ?>
	<?php if (SITE . URL::getClearUri() === $category['link']) {$active = 'active';} else {$active = '';} ?>
	<?php if (isset($category['child'])): ?>
		<li class="has-sub <?php echo $active;?>">
			<a href="<?php echo $category['link']; ?>">
				<?php if (!empty($category['menu_icon'])): ?><img src="<?php echo SITE . $category['menu_icon']; ?>" alt="<?php echo $category['menu_seo_alt']; ?>"><?php endif; ?>
				<div class="text"><?php echo MG::contextEditor('category', $category['menu_title'] ? $category['menu_title'] : $category['title'], $category["id"], "category"); ?></div>
			</a>
			<div class="level-2">
				<ul>
				<?php foreach ($category['child'] as $categoryLevel1): ?>
					<?php if ($categoryLevel1['invisible'] == "1") {continue;} ?>
					<?php if (SITE . URL::getClearUri() === $categoryLevel1['link']) {$active = 'active';} else {$active = '';} ?>
					<li class="<?php echo $active;?>">
						<a href="<?php echo $categoryLevel1['link']; ?>">
							<?php if (!empty($categoryLevel1['menu_icon'])): ?><img src="<?php echo SITE . $categoryLevel1['menu_icon']; ?>" alt="<?php echo $categoryLevel1['menu_seo_alt']; ?>"><?php endif; ?>
							<div class="text"><?php echo MG::contextEditor('category', $categoryLevel1['menu_title'] ? $categoryLevel1['menu_title'] : $categoryLevel1['title'], $categoryLevel1["id"], "category"); ?></div>
						</a>
					</li>
				<?php endforeach; ?>
				</ul>
			</div>
		</li>
	<?php else: ?>
		<li class="<?php echo $active;?>">
			<a href="<?php echo $category['link']; ?>">
				<?php if (!empty($category['menu_icon'])): ?><img src="<?php echo SITE . $category['menu_icon']; ?>" alt="<?php echo $category['menu_seo_alt']; ?>"><?php endif; ?>
				<div class="text"><?php echo MG::contextEditor('category', $category['menu_title'] ? $category['menu_title'] : $category['title'], $category["id"], "category"); ?></div>
			</a>
		</li>
	<?php endif; ?>
<?php endforeach; ?>
<li><a href="/skladskie-pozitsii"><img src="<?php echo PATH_SITE_TEMPLATE;?>/images/box.svg" alt="Складские позиции"><div class="text">Складские позиции</div></a></li>