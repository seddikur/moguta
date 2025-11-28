<?php
mgAddMeta('components/modal/modal.js');
mgAddMeta('components/modal/modal.css');
?>
<div class="c-modal c-modal--700 mg-fake-cart"
     id="js-modal__cart">
    <div class="c-modal__wrap">
        <div class="c-modal__content">
            <div class="c-modal__close">
                <svg class="icon icon--close">
                    <use xlink:href="#icon--close"></use>
                </svg>
            </div>

            <?php
            // Всплывающая корзина
            component('cart/popup', $data);
            ?>

        </div>
    </div>
</div>
