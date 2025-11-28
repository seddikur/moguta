<?php 
$showBlock = '';
//Спрятать блок с комментариями если там пусто
if (empty($data['comments'])) {
    $showBlock = 'order-edit-display';
}
?>
<div class="order-fieldset order-payment-sum__item order-payment-sum__item_wide shadow-block <?php echo $showBlock ?>">
    <h2 class="order-fieldset__h2">
        Чат менеджеров
        <span tooltip="Сообщения чата доступны только здесь, покупатели их не увидят. Добавить новое сообщение в чат вы можете в режиме редактирования заказа, нажав на кнопку «Редактировать» в левом верхнем углу. Удалять комментарии может только администратор."
              flow="up">
            <i class="fa fa-question-circle"
               aria-hidden="true"></i>
        </span>
    </h2>
    <div class="order-fieldset__inner">
        <div class="order-comments">
            <!-- Блок с комментариями -->
            <div class="order-comments__inner">
                <?php foreach ($data['comments'] as $comment): ?>
                    <div class="js-order-comment-block order-comments__item order-comment"
                         data-id="<?php echo $comment['id']; ?>">
                        <div class="order-comment__header">
                            <strong class="order-comment__name">
                                <?php echo $comment['user']['name']; ?>
                            </strong>
                            <span class="order-comment__delimeter">-</span>
                            <a href="mailTo:<?php echo $comment['user']['email']; ?>"
                               title="Отправить email пользователю"
                               class="order-comment__email">
                                <?php echo $comment['user']['email']; ?>
                            </a>
                            <span class="order-comment__delimeter">-</span>
                            <time class="order-comment__time">
                                <?php
                                $date = date("d.m.Y H:i:s", strtotime($comment['created_at']));
                                echo $date; ?>
                            </time>
                            <?php if (USER::AccessOnly('1')): ?>
                                <button class="js-delete-order-comment order-comment__btn order-comment__btn_delete"
                                        data-id="<?php echo $comment['id']; ?>">
                                    <?php echo $lang['DELETE']; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <p class="order-comment__text">
                            <?php echo $comment['text']; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($data['main_comment']) && empty($data['comments'])) : ?>
                <span class="order-comments__empty order-edit-visible">Комментарии отсутствуют</span>
                <?php endif; ?>
            </div>
            <!-- /Блок с комментариями -->

            <button class="order-edit-display js-show-comment-form link">
                <i class="fa fa-plus" aria-hidden="true"></i>
                <span>Добавить комментарий</span>
            </button>

            <!-- Блок с формой -->
            <div class="js-comment-form order-comments__new new-comment"
                 style="display: none;">
                <div class="new-comment__label">
                    <div class="new-comment__header">
                        <label for="new-order-comment"
                               class="new-comment__title">
                            Новый комментарий:
                            <span tooltip="Комментарий будет сохранён при сохранении заказа"
                                  flow="up">
                                <i class="fa fa-question-circle" aria-hidden="true"></i>
                            </span>
                        </label>
                        <button class="js-close-comment-form link new-comment__close">
                            <i class="fa fa-times" aria-hidden="true"></i>
                            <span><?php echo $lang['CLOSE']; ?></span>
                        </button>
                    </div>
                    <textarea name="commentExt"
                              id="new-order-comment"
                              class="new-comment__text-input cancel-order-reason"></textarea>
                </div>
            </div>
            <!-- /Блок с формой -->
        </div>
    </div>
</div>