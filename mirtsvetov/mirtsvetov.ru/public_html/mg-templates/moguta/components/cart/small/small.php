<?php
mgAddMeta('components/cart/cart.js');
//mgAddMeta('components/cart/cart.amount.js');

mgAddMeta('components/cart/small/small.css');
mgAddMeta('components/cart/cart.css');

$smallCartRow = function (
  $item = array(
    'product_url' => 0,
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
                       href="<?php echo $hrefAttr; ?>"><?php echo $item['title'] ?></a>
                    <span class="property js-smallCartProperty"><?php echo $item['property_html'] ?></span>
                </li>
                <li class="c-table__quantity qty">
                    <span class="qty__inner">x<span class="js-smallCartAmount"><?php echo $item['countInCart'] ?></span></span>
                    <span class="c-table__item-price js-cartPrice"><?php echo $item['priceInCart'] ?></span>
                </li>
            </ul>
        </td>
        <td class="c-table__remove small-cart-remove">
            <a href="javascript: void(0);"
               class="deleteItemFromCart js-delete-from-cart"
               title="<?php echo lang('delete'); ?>"
               data-delete-item-id="<?php echo $item['id'] ?>"
               data-property="<?php echo $item['property'] ?>"
               data-variant="<?php echo(!empty($item['variantId']) ? $item['variantId'] : 0); ?>">
                <div class="icon__cart-remove">
                    <svg class="icon icon--remove">
                        <use xlink:href="#icon--remove"></use>
                    </svg>
                </div>
            </a>
        </td>
    </tr>
<?php } ?>

<div class="c-cart mg-desktop-cart">
    <a class="c-cart__small cart"
       href="<?php echo SITE ?>/cart">
        <span class="small-cart-icon"></span>
        <div class="c-cart__small--icon">
            <svg class="icon icon--cart">
                <use xlink:href="#icon--cart"></use>
            </svg>
        </div>
        <ul class="c-cart__small--list cart-list">
            <li class="c-cart__small--count">
                <div class="c-cart__small--text">
                  <?php echo lang('cartCart'); ?>
                    <span class="countsht"><?php echo !empty($data['cart_count']) ? $data['cart_count'] : 0 ?></span>
                </div>
            </li>
            <li class="c-cart__small--price cart-qty">
                <span class="pricesht">
                  <?php echo !empty($data['cart_price']) ? $data['cart_price'] : 0 ?>
                </span>
              <?php echo (!empty($data['currency'])) ? $data['currency'] : ''; ?>
            </li>
        </ul>
    </a>
    <div class="c-cart__dropdown small-cart">
        <div class="l-row">
            <div class="l-col min-0--12">
                <div class="c-title"><?php echo lang('cartTitle'); ?></div>
            </div>
            <div class="l-col min-0--12">
                <div class="c-table c-table--scroll">
                    <template class="smallCartRowTemplate"
                              style="display:none;"><?php $smallCartRow(); ?></template>
                    <table class="small-cart-table">
                      <?php
                      if (!empty($data['dataCart'])) {
                        foreach ($data['dataCart'] as $item) {
                          $smallCartRow($item);
                        }
                      }
                      ?>
                    </table>
                </div>
                <ul class="c-table__footer total">
                    <li class="c-table__total total-sum">
                        <?php echo lang('cartPay'); ?>
                        <span class="total-payment">
                            <?php
                            if (!empty($data['cart_price_wc'])) {
                              echo $data['cart_price_wc'];
                            }
                            ?>
                        </span>
                    </li>
                    <li class="checkout-buttons">
                        <a href="<?php echo SITE ?>/cart"
                           class="c-button c-button--link">
                          <?php echo lang('cartLink'); ?>
                        </a>
                        <a href="<?php echo SITE ?>/order"
                           class="c-button">
                          <?php echo lang('cartCheckout'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
