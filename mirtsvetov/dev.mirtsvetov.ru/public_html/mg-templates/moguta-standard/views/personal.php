<?php
/**
 *  Файл представления Personal - выводит сгенерированную движком информацию на странице личного кабинета.
 *  В этом файле доступны следующие данные:
 *   <code>
 *     $data['error'] => Сообщение об ошибке.
 *     $data['message'] =>  Информационное сообщение.
 *     $data['status'] => Статус пользователя.
 *     $data['userInfo'] => Информация о пользователе.
 *     $data['orderInfo'] => Информация о заказе.
 *     $data['currency'] => $settings['currency'],
 *     $data['paymentList'] => $paymentList,
 *     $data['meta_title'] => Значение meta тега для страницы,
 *     $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *     $data['meta_desc'] => Значение meta_desc тега для страницы
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['userInfo']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['message']; ?>
 *   </code>
 *
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Views
 */
// Установка значений в метатеги title, keywords, description.
mgSEO($data);
?>

<div class="l-row">
    <?php switch ($data['status']) {
        case 1: ?>

            <div class="l-col min-0--12">
                <div class="c-alert c-alert--red"><?php echo lang('personalBlocked'); ?></div>
            </div>

            <?php break;
        case 2: ?>

            <div class="l-col min-0--12">
                <div class="c-alert c-alert--red"><?php echo lang('personalNotActivated'); ?></div>
            </div>

            <div class="l-col min-0--12">
                <form class="c-form" action="<?php echo SITE ?>/registration" method="POST">
                    <div class="c-form__row">
                        <input type="text" name="activateEmail" value="" placeholder="E-mail или <?php echo lang('lphone'); ?>" required>
                    </div>
                    <div class="c-form__row">
                        <input type="submit" class="c-button" name="reActivate" value="<?php echo lang('send'); ?>">
                        <a class="c-button" title="<?php echo lang('personalTab4'); ?>" href="<?php echo SITE ?>/enter?logout=1"><?php echo lang('personalTab4'); ?></a>
                    </div>
                </form>
            </div>

            <?php break;
        case 3:
            $userInfo = $data['userInfo']; ?>

            <div class="l-col min-0--12">
                <div class="c-title c-title--no-border"><?php echo lang('personalAccount'); ?>
                    «<?php echo $userInfo->name ?>»
                </div>
            </div>

            <?php if ($data['message']): ?>
            <div class="l-col min-0--12">
                <div class="c-alert c-alert--green mg-success"><?php echo $data['message'] ?></div>
            </div>
        <?php endif; ?>

            <?php if ($data['error']): ?>
            <div class="l-col min-0--12">
                <div class="c-alert c-alert--red mg-error"><?php echo $data['error'] ?></div>
            </div>
        <?php endif; ?>


            <div class="l-col min-0--12">
                <?php
                component('tabs', $data, 'tabs_personal');
                ?>
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

            <div class="l-col min-0--12">
                <div class="c-alert c-alert--red msgError"><?php echo lang('personalNotAuthorised'); ?></div>
            </div>

        <?php } ?>
</div>
