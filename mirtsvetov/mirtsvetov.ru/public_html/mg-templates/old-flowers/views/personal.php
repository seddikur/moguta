<?php mgSEO($data);?>
<div class="max">
    <?php switch ($data['status']) {
        case 1: ?>
            <div class="alert"><?php echo lang('personalBlocked'); ?></div>

            <?php break;
        case 2: ?>

            <div class="alert"><?php echo lang('personalNotActivated'); ?></div>

            <form action="<?php echo SITE ?>/registration" method="POST">
                <ul class="form-list">
                    <li>
                        <input type="text" name="activateEmail" value="" placeholder=" " required>
                        <span class="placehholder">E-mail или <?php echo lang('lphone'); ?></span>
                    </li>
                </ul>
                <input type="submit" class="button" name="reActivate" value="<?php echo lang('send'); ?>">
            </form>
            <?php break;
        case 3:
            $userInfo = $data['userInfo']; ?>

            <?php if ($data['message']): ?>
                <div class="alert success"><?php echo $data['message'] ?></div>
            <?php endif; ?>

            <?php if ($data['error']): ?>
                <div class="alert"><?php echo $data['error'] ?></div>
            <?php endif; ?>

            <div class="static">
                <div class="flex space-between">
                    <div class="left-part sidebar">
                        <ul class="left-menu">
                            <li class="tab-link active" data-tab="orders" data-group="personal"><a>Заказы</a></li>
                            <li class="tab-link" data-tab="data" data-group="personal"><a>Профиль</a></li>
                            <li class="tab-link" data-tab="password" data-group="personal"><a>Пароль</a></li>
                            <li class="tab-link show-modal" data-modal="modal-exit"><a>Выход</a></li>
                        </ul>
                    </div>
                    <div class="right-part">
                        <h1><?php echo lang('personalAccount'); ?></h1>
                        <div class="tabs">
                            <div class="tab active" data-tab="orders" data-group="personal">
                                <?php component('personal/history', $data);?>
                            </div>    
                            <div class="tab" data-tab="data" data-group="personal">
                                <?php component('personal/info', $data);?>
                            </div>    
                            <div class="tab" data-tab="password" data-group="personal">
                                <?php component('personal/password', $data);?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php break;
        case 4:
            ?>
        <div class="c-alert-account-form">
            <?php if($data['error']): ?>
            <div class="c-alert c-alert--red mg-error"><?php echo $data['error'] ?></div>
            <?php endif; ?>
            <?php if($data['message']): ?>
            <div  class="c-alert c-alert--green mg-success"><?php echo $data['message'] ?></div>
            <?php endif; ?>
        </div>
        <?php break;
        default : ?>
            <div class="static">
                <div class="flex space-between">
                    <div class="left-part sidebar">
                        <ul class="page-menu">
                            <li class="active"><a href="<?php echo SITE ?>/enter"><?php echo lang('enterTitle'); ?></a></li>
                            <li><a href="<?php echo SITE ?>/registration"><?php echo lang('enterRegister'); ?></a></li>
                            <li><a href="<?php echo SITE ?>/forgotpass"><?php echo lang('enterForgot'); ?></a></li>
                        </ul>
                    </div>
                    <div class="right-part white">
                        <h1><?php echo lang('enterTitle'); ?></h1>
                        <div class="alert"><?php echo lang('personalNotAuthorised'); ?></div>
                        <?php component('auth/login', $data);?>	
                    </div>
                </div>
            </div>            
    <?php } ?>
</div>

<div class="modal" data-modal="modal-exit">
    <div class="inner">
        <span class="close">закрыть</span>
        <div class="h2-like">Точно хотите выйти?</div>
        <br>
        <div class="buttons flex align-center">
            <a class="button" href="<?php echo SITE ?>/enter?logout=1">Да</a>
            <a class="button close-modal">Нет</a>
        </div>
    </div>
</div>