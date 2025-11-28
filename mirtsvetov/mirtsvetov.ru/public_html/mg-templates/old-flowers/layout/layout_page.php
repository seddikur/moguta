<?php if (MG::get('isStaticPage')):?>
    <div class="static content">
        <div class="max">
            <?php layout('content');?>
        </div>
        <?php if(URL::getClearUri()=='/contacts'): ?>                
            <div class="max">
                <div class="contacts-block page flex column">
                    <h2>АО «Мир цветов»</h2>
                    <div class="item flex align-center nowrap">
                        <span class="flex center align-center"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#phone"></use></svg></span>
                        <a href="tel:+7 8342 23 33 23">+7 8342 23 33 23</a>
                    </div>
                    <div class="item flex align-center nowrap">
                        <span class="flex center align-center"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#email"></use></svg></span>
                        <a href="mailto:mail@mirtsvetov.ru">mail@mirtsvetov.ru</a>
                    </div>
                    <div class="item flex align-center nowrap">
                        <span class="flex center align-center"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#telegram"></use></svg></span>
                        <a target="_blank" href="https://t.me/mirtsvetovrm">telegram</a>
                    </div>
                    <div class="item flex align-center nowrap">
                        <span class="flex center align-center"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#address"></use></svg></span>
                        <div>431900, Республика Мордовия, п. Кадошкино, ул. Гражданская, д. 47</div>
                    </div>
                </div>
            </div>
        <?php elseif(URL::getClearUri()=='/content'): ?>
            <div class="max">
                <div class="gallery-list">
                    <a class="item" href="/content/01">
                        <img src="/uploads/gallery/1/03.jpg" alt="">
                        <div class="h2-like title">CATALOG</div>
                    </a>
                    <a class="item" href="/content/02">
                        <img src="<?php echo PATH_TEMPLATE;?>/images/02.jpg" alt="">
                        <div class="h2-like title">FALL / WINTER 2024</div>
                    </a>
                </div>
            </div>
        <?php endif;?>
    </div>
    <?php if(URL::getClearUri()=='/contacts'): ?>
        <img style="width: 100%" src="<?php echo PATH_TEMPLATE;?>/images/contacts.jpg" alt="">
    <?php endif;?>
<?php else:?>
    <?php layout('content'); ?>
<?php endif;?>