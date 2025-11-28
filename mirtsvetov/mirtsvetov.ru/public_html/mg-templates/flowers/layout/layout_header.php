<header>
    <div class="max flex end align-center">
        <div class="menu-block">
            <div class="logo"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#logo"></use></svg></div>
            <div class="menu-close flex align-center center">
                <svg viewBox="0 0 100 100">
                    <path class="line top" d="m 30,33 h 40 c 3.722839,0 7.5,3.126468 7.5,8.578427 0,5.451959 -2.727029,8.421573 -7.5,8.421573 h -20"></path>
                    <path class="line middle" d="m 30,50 h 40"></path>
                    <path class="line bottom" d="m 70,67 h -40 c 0,0 -7.5,-0.802118 -7.5,-8.365747 0,-7.563629 7.5,-8.634253 7.5,-8.634253 h 20"></path>
                </svg>
            </div>
            <nav class="mobile-menu">
                <a href="/">Главная</a>
                <a href="/catalog">Каталог</a>
                <?php if (User::isAuth()):?>
                    <a href="/personal">Кабинет</a>
                    <?php component('cart', $data['cartData'], 'small'); ?>
                <?php else:?>
                    <a href="/enter">Кабинет</a>
                <?php endif;?>
                <?php if (User::isAuth()):?>
                    <a href="/order">Корзина</a>
                <?php endif;?>    
                <a href="/content">Контент</a>
                <a href="/contacts">Контакты</a>
            </ul>
        </div>
    </div>
</header>