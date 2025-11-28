<?php $userArr = (array)USER::getThis(); if (!empty($userArr)): ?>
    <a href="<?php echo SITE ?>/personal" class="flex align-center nowrap">
        <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/svg/icons.svg#user"></use></svg>Кабинет
    </a>
<?php else: ?>
    <a href="<?php echo SITE ?>/enter" class="flex align-center nowrap">
        <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/svg/icons.svg#user"></use></svg>Войти
    </a>
<?php endif; ?>