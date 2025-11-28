<?php mgSEO($data);?>

<div class="mobile-hide">
    <div class="buttons-block nowrap flex space-between align-center">
        <?php
            $cat_id = $data['cat_id'];
            $sort = $data['sort'];
            $auth = (User::isAuth()) ? '' : 'AND u.product_id IS NULL';
            $next_res = DB::query('
                SELECT p.* FROM mg_product p
                LEFT JOIN mg_product_user_property_data u ON p.id = u.product_id AND u.prop_id = 10
                WHERE p.sort < '.$sort.' AND p.activity = "1" '.$auth.'
                ORDER BY p.sort DESC LIMIT 1
            ');
            $next = DB::fetchAssoc($next_res);
            $disabled = ($next)?'':'disabled';
            $model = new Models_Product;
            $product = $model->getProduct($next['id']);
            $buttons = '<a class="prev flex align-center center '.$disabled.'" href="/'.$product['category_url'].'/'.$product['product_url'].'"><svg><use xlink:href="'.PATH_SITE_TEMPLATE.'/images/icons.svg#arrow"></use></svg></a>';
            $buttons .= '<a href="/catalog" class="button">В каталог</a>';
            $prev_res = DB::query('
                SELECT p.* FROM mg_product p
                LEFT JOIN mg_product_user_property_data u ON p.id = u.product_id AND u.prop_id = 10
                WHERE p.sort > '.$sort.' AND p.activity = "1" '.$auth.'
                ORDER BY p.sort ASC LIMIT 1
            ');
            $prev = DB::fetchAssoc($prev_res);
            $disabled = ($prev)?'':'disabled';
            $model = new Models_Product;
            $product = $model->getProduct($prev['id']);
            $buttons .= '<a class="next flex align-center center '.$disabled.'" href="/'.$product['category_url'].'/'.$product['product_url'].'"><svg><use xlink:href="'.PATH_SITE_TEMPLATE.'/images/icons.svg#arrow"></use></svg></a>';
        ?>
    </div>
</div>



<div class="product-details-block js-product-page" itemscope itemtype="http://schema.org/Product">
    <?php if($data['thisUserFields'][10]['data'][0]['name'] && !User::isAuth()):?>
        <div class="max first">
            <div class="item"></div>
            <div class="item text">
                <div class="h1-like">Авторизуйтесь для просмотра этой позиции</div>
                <div class="mobile-hide">
                    <div class="buttons-block nowrap flex space-between align-center"><?php echo $buttons;?></div>
                </div>
            </div>
        </div>
        <div class="mobile-show">
            <div class="buttons-block nowrap max flex space-between align-center"><?php echo $buttons;?></div>
        </div>
    <?php else:?> 
        <div class="max first">       
            <div class="item"><img src="[webp url="<?php echo  SITE.$data['thisUserFields'][1]['data'][0]['name'];?>"]"></div>
            <div class="item text">
                <h1 itemprop="name"><?php echo $data['title'] ?></h1>
                <div class="content"><?php echo $data['description'];?></div>
                <div class="mobile-hide">
                    <div class="buttons-block nowrap flex space-between align-center">
                        <?php echo $buttons;?>
                    </div>
                </div>
            </div>
            <div class="item"><img src="[webp url="<?php echo SITE.$data['thisUserFields'][2]['data'][0]['name'];?>"]"></div>
            <div class="item"><img src="[webp url="<?php echo SITE.$data['thisUserFields'][3]['data'][0]['name'];?>"]"></div>
        </div>
        <div class="mobile-show">
            <div class="buttons-block nowrap max flex space-between align-center">
                <?php echo $buttons;?>
            </div>
        </div>
        <?php if($data['thisUserFields'][4]):?>
            <div class="third"><img src="[webp url="<?php echo $data['thisUserFields'][4]['data'][0]['name'];?>"]"></div>
        <?php endif;?>
    <?php endif;?>
</div>