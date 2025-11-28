<?php
mgAddMeta('components/cart/cart.js');

$popupCartRow = function (
  $item = array('product_url' => 0,
    'image_url_new' => 0,
    'title' => 0,
    'property_html' => 0,
    'countInCart' => 0,
    'priceInCart' => 0,
    'id' => 0,
    'property' => 0,
    'variantId' => 0)
) {

  // Получаем массив миниатюр изображений
  $thumbsArr = getThumbsFromUrl($item['image_url_new'], $item['id']);
  $hrefAttr = $item['product_url'] === 0 ? '#' : SITE . "/" . (isset($item['category_url']) ? $item['category_url'] : 'catalog/') . $item['product_url'];
  ?>

    <tr>
        <td class="c-table__img small-cart-img">
            <a class="js-smallCartImgAnchor"
               href="<?php echo $hrefAttr; ?>">
                <img class="js-smallCartImg"
                     src="<?php echo $thumbsArr[30]['main'] ?>"
                     srcset="<?php echo $thumbsArr[30]['2x'] ?> 2x"
                     alt="<?php echo $item['title'] ?>">
            </a>
        </td>
        <td class="c-table__name small-cart-name">
            <ul class="small-cart-list">
                <li>
                    <a class="c-table__link js-smallCartProdAnchor"
                       href="<?php echo $hrefAttr; ?>">
                      <?php echo $item['title'] ?>
                    </a>
                    <span class="property js-smallCartProperty">
                        <?php echo $item['property_html'] ?>
                    </span>
                </li>
                <li class="c-table__quantity qty">
                    <span class="qty__inner">x<span class="js-smallCartAmount"><?php echo $item['countInCart'] ?></span></span>
                    <span class="c-table__item-price js-cartPrice"><?php echo $item['priceInCart'] ?></span>
                </li>
            </ul>
        </td>
        <td class="c-table__remove small-cart-remove">
            <a href="#"
               class="deleteItemFromCart js-delete-from-cart"
               title="<?php echo lang('delete'); ?>"
               data-delete-item-id="<?php echo $item['id'] ?>"
               data-property="<?php echo $item['property'] ?>"
               data-variant="<?php echo (!empty($item['variantId']) ? $item['variantId'] : 0); ?>">
                <div class="icon__cart-remove">
                    <svg class="icon icon--remove">
                        <use xlink:href="#icon--remove"></use>
                    </svg>
                </div>
            </a>
        </td>
    </tr>
<?php } ?>

<div class="c-title"><?php echo lang('cartTitle'); ?></div>
<div class="c-table popup-body">
    <template class="popupCartRowTemplate"
              style="display:none;">
        <?php $popupCartRow(); ?>
    </template>
    <table class="small-cart-table js-popup-cart-table">
      <?php
      if (!empty($data['dataCart'])) {
        foreach ($data['dataCart'] as $item) {
          $popupCartRow($item);
        }
      }
      ?>
    </table>
</div>
<div class="popup-footer">
    <ul class="c-table__footer total sum-list">
        <li class="c-table__total total-sum">
          <?php echo lang('toPayment') ?>:
            <span class="total-payment">
                <?php
                if (!empty($data['cart_price_wc'])) {
                  echo $data['cart_price_wc'];
                }
                ?>
            </span>
        </li>
        <li class="checkout-buttons">
            <button class="c-button c-button--link c-modal__cart"
               >
              <?php echo lang('cartContinue'); ?>
            </button>
            <a class="c-button"
               href="<?php echo SITE ?>/order">
              <?php echo lang('cartCheckout'); ?>
            </a>
        </li>
    </ul>
</div>
