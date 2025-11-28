<?php mgSEO($data);?>
<div class="static">
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
    
                <h1><?php echo lang('personalAccount'); ?></h1>

                <ul class="tab-links flex">
                    <li class="tab-link"><a href="/catalog">В каталог</a></li>
                    <?php if (class_exists('LegalEntity')): ?>
                        <a class="tab-link active" data-tab="legal">
                            Мои компании
                        </a>
                    <?php endif; ?>
                    <li class="tab-link" data-tab="orders" data-group="personal"><a>Заказы</a></li>
                    <li class="tab-link" data-tab="password" data-group="personal"><a>Пароль</a></li>
                    <li class="tab-link"><a href="<?php echo SITE ?>/enter?logout=1">Выход</a></li>
                    <a class="tab-link app-show" data-group="personal" data-tab="delete">
                        Удалить аккаунт
                    </a>
                </ul>

                <div class="tabs">
                    <div class="tab" data-group="personal" data-tab="delete">
                        Если по каким-то причинам вы хотите удалить аккаунт, пожалуйста, нажмите эту кнопку:
                        <br><br>
                        <a class="button" href="<?php echo SITE ?>/delete-user">Удаление аккаунта</a>
                    </div>
                    
                    
                    <?php if (class_exists('LegalEntity')): ?>
                        <div class="tab active" data-tab="legal">
                            <?php component('personal/legal', $data);?>
                        </div>
                    <?php endif; ?>

                    <div class="tab" data-tab="orders" data-group="personal">
                        <?php component('personal/history', $data);?>
                    </div>    
                    <div class="tab" data-tab="data" data-group="personal">
                        <?php component('personal/info', $data);?>
                    </div>    
                    <div class="tab" data-tab="password" data-group="personal">
                        <?php component('personal/password', $data);?>
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
</div>