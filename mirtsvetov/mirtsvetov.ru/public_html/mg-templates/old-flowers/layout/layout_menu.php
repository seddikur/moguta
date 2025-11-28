<?php $categories = MG::get('category')->getHierarchyCategory(); if(!empty($categories)):?>
	<div class="cats flex space-between">
		<a href="<?php echo SITE;?>/catalog" class="item <?php echo (URL::getUri() == '/catalog')?'active':'';?>">Весь ассортимент</a>
		<a href="<?php echo SITE;?>/latest" class="item <?php echo (URL::getUri() == '/catalog?cat_id=&applyFilter=1&new=1')?'active':'';?>">Новинки</a>
		<?php foreach($categories as $category): ?>
            <?php if($category['activity']=="0") continue;?>
			<?php $active = (URL::getUri() == '/'.$category['url'])?'active':'';?>
			<a class="<?php echo $active;?>" href="<?php echo SITE.'/'.$category['parent_url'].$category['url']; ?>" class="item">
				<?php echo $category['title']; ?>
			</a>
		<?php endforeach; ?>
		<?php /*<div class="show-filter">Фильтры и сортировка</div>*/?>
	</div>
<?php endif; ?>