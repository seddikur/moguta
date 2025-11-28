<?php mgSEO($data);?>

<div class="product-details-block js-product-page" itemscope itemtype="http://schema.org/Product">
    <div class="mobile-show2">
        <div class="max first">            
            <div class="item"><img src="[webp url="<?php echo  SITE.$data['thisUserFields'][1]['data'][0]['name'];?>"]"></div>
            <div class="item text">
                <h1 itemprop="name"><?php echo $data['title'] ?></h1>
                <div class="content"><?php echo $data['description'];?></div>
                <div class="mobile-hide">
                    <div class="buttons-block nowrap flex space-between align-center">
                        <?php
                            $cat_id = $data['cat_id'];
                            $sort = $data['sort'];
                            $next_res = DB::query('SELECT * FROM mg_product WHERE sort < '.$sort.' AND activity = "1" ORDER BY sort DESC LIMIT 1');                $next = DB::fetchAssoc($next_res);
                            $disabled = ($next)?'':'disabled';
                            $model = new Models_Product;
                            $product = $model->getProduct($next['id']);
                            echo '<a class="prev flex align-center center '.$disabled.'" href="/'.$product['category_url'].'/'.$product['product_url'].'"><svg><use xlink:href="'.PATH_SITE_TEMPLATE.'/images/icons.svg#arrow"></use></svg></a>';
                            echo '<a href="/catalog" class="button">В каталог</a>';
                            $prev_res = DB::query('SELECT * FROM mg_product WHERE sort > '.$sort.' AND activity = "1" ORDER BY sort ASC LIMIT 1');
                            $prev = DB::fetchAssoc($prev_res);
                            $disabled = ($prev)?'':'disabled';
                            $model = new Models_Product;
                            $product = $model->getProduct($prev['id']);
                            echo '<a class="next flex align-center center '.$disabled.'" href="/'.$product['category_url'].'/'.$product['product_url'].'"><svg><use xlink:href="'.PATH_SITE_TEMPLATE.'/images/icons.svg#arrow"></use></svg></a>';
                        ?>
                    </div>
                </div>
            </div>
            <div class="item"><img src="[webp url="<?php echo SITE.$data['thisUserFields'][2]['data'][0]['name'];?>"]"></div>
            <div class="item"><img src="[webp url="<?php echo SITE.$data['thisUserFields'][3]['data'][0]['name'];?>"]"></div>
        </div>
        <div class="mobile-show">
            <div class="buttons-block nowrap max flex space-between align-center">
                <?php
                    $cat_id = $data['cat_id'];
                    $sort = $data['sort'];
                    $next_res = DB::query('SELECT * FROM mg_product WHERE sort < '.$sort.' AND activity = "1" ORDER BY sort DESC LIMIT 1');                $next = DB::fetchAssoc($next_res);
                    $disabled = ($next)?'':'disabled';
                    $model = new Models_Product;
                    $product = $model->getProduct($next['id']);
                    echo '<a class="prev flex align-center center '.$disabled.'" href="/'.$product['category_url'].'/'.$product['product_url'].'"><svg><use xlink:href="'.PATH_SITE_TEMPLATE.'/images/icons.svg#arrow"></use></svg></a>';
                    echo '<a href="/catalog" class="button">В каталог</a>';
                    $prev_res = DB::query('SELECT * FROM mg_product WHERE sort > '.$sort.' AND activity = "1" ORDER BY sort ASC LIMIT 1');
                    $prev = DB::fetchAssoc($prev_res);
                    $disabled = ($prev)?'':'disabled';
                    $model = new Models_Product;
                    $product = $model->getProduct($prev['id']);
                    echo '<a class="next flex align-center center '.$disabled.'" href="/'.$product['category_url'].'/'.$product['product_url'].'"><svg><use xlink:href="'.PATH_SITE_TEMPLATE.'/images/icons.svg#arrow"></use></svg></a>';
                ?>
            </div>
        </div>
        <div class="third"><img src="<?php echo $data['thisUserFields'][4]['data'][0]['name'];?>"></div>
    </div>
</div>