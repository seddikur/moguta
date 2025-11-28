<!-- noindex -->
<div class="overlay"></div>
<div class="modal" data-modal="privacy-policy">
    <div class="inner">
        <span class="close flex align-center center">
            <svg viewBox="0 0 100 100">
                <path class="line top" d="m 30,33 h 40 c 3.722839,0 7.5,3.126468 7.5,8.578427 0,5.451959 -2.727029,8.421573 -7.5,8.421573 h -20"></path>
                <path class="line middle" d="m 30,50 h 40"></path>
                <path class="line bottom" d="m 70,67 h -40 c 0,0 -7.5,-0.802118 -7.5,-8.365747 0,-7.563629 7.5,-8.634253 7.5,-8.634253 h 20"></path>
            </svg>
        </span>
        <div class="content"><?php echo MG::get('pages')->getPageByUrl('privacy-policy')['html_content'];?></div>
    </div>
</div>

<div class="overlay"></div>
<div class="modal" data-modal="personal-data">
    <div class="inner">
        <span class="close flex align-center center">
            <svg viewBox="0 0 100 100">
                <path class="line top" d="m 30,33 h 40 c 3.722839,0 7.5,3.126468 7.5,8.578427 0,5.451959 -2.727029,8.421573 -7.5,8.421573 h -20"></path>
                <path class="line middle" d="m 30,50 h 40"></path>
                <path class="line bottom" d="m 70,67 h -40 c 0,0 -7.5,-0.802118 -7.5,-8.365747 0,-7.563629 7.5,-8.634253 7.5,-8.634253 h 20"></path>
            </svg>
        </span>
        <div class="content"><?php echo MG::get('pages')->getPageByUrl('politika-v-otnoshenii-obrabotki-personalnyh-dannyh')['html_content'];?></div>
    </div>
</div>
<!-- noindex -->

<!-- <div class="app-panel app-show">
    <div class="inner flex space-between nowrap align-center">
        <a href="/catalog"><i></i>Каталог</a>
        <a href="/content"><i></i>Контент</a>
        <a href="/personal"><i></i>Кабинет</a>
        <?php if (User::isAuth()):?><a href="/order"><i></i>Корзина</a><?php else:?><a href="/contacts"><i></i>Контакты</a><?php endif;?>
    </div>
</div> -->

<footer>
    <svg class="back"><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#logo"></use></svg>
    <div class="max">
        <div class="contacts-block flex">
            <div class="item flex align-center">
                <span class="flex center align-center"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#phone"></use></svg></span>
                <a href="tel:+7 8342 23 33 23">+7 8342 23 33 23</a>
            </div>
            <div class="item flex align-center">
                <span class="flex center align-center"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#email"></use></svg></span>
                <a href="mailto:mail@mirtsvetov.ru">mail@mirtsvetov.ru</a>
            </div>
        </div>
        <ul class="menu flex">
            <li><a href="/">Главная</a></li>
            <li><a href="/catalog">Каталог</a></li>
            <!-- <li><a>Личный кабинет</a></li> -->
            <li><a href="/content">Контент</a></li>
            <li><a href="/contacts">Контакты</a></li>
        </ul>
        <div class="copiright">&copy; <?php echo date('Y');?> АО "Мир Цветов". ОГРН 1051323019692. Пользуясь сайтом, вы даете согласие на использование файлов cookies<br><br><span class="show-modal" data-modal="privacy-policy">Политика конфиденциальности</span><br><span class="show-modal" data-modal="personal-data">Политика в отношении обработки персональных данных</span></div>
        <a class="belka" target="_blank" aria-label="belka.one" href="https://belka.one">
            <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#belka"></use></svg>
        </a>
    </div>
</footer>