<?php $categories = MG::get('category')->getHierarchyCategory($data, true); if(!empty($categories)):?>
    <div class="cats">
        <div class="grid">
            <?php foreach($categories as $category): ?>
                <a href="<?php echo SITE.'/'.$category['parent_url'].$category['url']; ?>" class="item">
                    <?php echo $category['title']; ?>
                </a>
            <?php endforeach; ?>
            <div class="show-filter">Фильтры и сортировка</div>
        </div>
    </div>
<?php endif; ?>