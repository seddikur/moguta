<?php
/*
 * Ссылка, ведущая в личный кабинет, если пользователь авторизован,
 * либо на страницу авторизрации, если нет.
 *
 * */

mgAddMeta('components/auth/login/link/link.css');

// Получаем информацию о пользователе
$userArr = (array)USER::getThis();

if (!empty($userArr)): ?>

    <a class="c-login"
       title="<?php echo lang('authAccount'); ?>"
       href="<?php echo SITE ?>/personal">
        <div class="c-login__icon">
            <svg class="icon icon--user">
                <use xlink:href="#icon--user"></use>
            </svg>
        </div>
        <div class="c-login__text">
            <?php
            echo (!empty($userArr['name'])) ? $userArr['name'] : lang('authAccount');
            ?>
        </div>
    </a>

<?php else: ?>

    <a class="c-login"
       href="<?php echo SITE ?>/enter"
       title="<?php echo lang('authAccount'); ?>">
        <div class="c-login__icon">
            <svg class="icon icon--user">
                <use xlink:href="#icon--user"></use>
            </svg>
        </div>
        <div class="c-login__text">
            <?php echo lang('enterEnter'); ?>
        </div>
    </a>

<?php endif; ?>