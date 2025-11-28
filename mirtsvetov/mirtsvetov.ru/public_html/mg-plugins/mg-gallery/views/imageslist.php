<div class="gallery-page">
    <?php $i=1; foreach ($imgList as $img) { ?>
        <div class="item">
            <img src="[webp url="<?php echo SITE.$img['image_url']?>"]" alt="<?php echo $img['title']?>"/>
            <?php if(User::getThis()->role == '1'):?>
                <div style="font-size: 20px; background: #000; color: #fff; text-align: center; position: relative; margin-top: -40px">page <?php echo $i;?> / sort <?php echo $img['alt']?></div>
            <?php endif;?>
        </div>
    <?php $i++; } ?>
</div>