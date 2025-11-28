<?php
mgAddMeta('components/cart/cart.css');
mgAddMeta('components/cart/cart.js');
?>

<div class="l-col min-0--12">
    <div class="c-title">
      <?php echo lang('productCart'); ?>
    </div>
</div>

<?php if (class_exists('MinOrder')): ?>
    <div class="l-col min-0--12">
        [min-order]
    </div>
<?php endif; ?>

<div class="l-col min-0--12">
    <div class="product-cart"
         style="display:<?php echo $data['isEmpty'] ? 'block' : 'none'; ?>">
        <div class="c-form cart-wrapper">
            <form class="cart-form js-cart-form"
                  method="post"
                  action="<?php echo SITE ?>/cart">
                <div class="c-table">
                    <table class="cart-table">
                      <?php $i = 1;
                      foreach ($data['productPositions'] as $product): ?>
                          <tr>
                              <td class="c-table__img img-cell">
                                  <a href="<?php echo $product["link"] ?>"
                                     title="<?php echo $product["title"] ?>"
                                     target="_blank"
                                     class="cart-img">

                                      <img src="<?php echo mgImageProductPath($product["image_url"], $product['id'], 'small') ?>"
                                           title="<?php echo $product["title"] ?>"
                                           alt="<?php echo $product["title"] ?>">

                                  </a>
                              </td>
                              <td class="c-table__name name-cell">
                                  <a class="c-table__link"
                                     title="<?php echo $product["title"] ?>"
                                     href="<?php echo $product["link"] ?>"
                                     target="_blank">
                                    <?php echo $product['title'] ?>
                                  </a>
                                  <br>
                                <?php echo $product['property_html'] ?>
                              </td>
                              <td class="c-table__count count-cell">

                                <?php

                                // Компонент выбора количества товара
                                component(
                                  'amount',
                                  [
                                    'id' => $product['id'],
                                    'maxCount' => $product['count'],
                                    'count' => $product['countInCart'],
                                    'type' => 'cart',
                                    'increment'=> MG::get('settings')['useMultiplicity'] == 'true' ? $product['multiplicity'] : '1',
                                  ]
                                ); ?>

                                  <input type="hidden"
                                         name="property_<?php echo $product['id'] ?>[]"
                                         value="<?php echo $product['property'] ?>"/>
                              </td>
                              <td class="c-table__price price-cell js-cartPrice">
                                <?php echo MG::numberFormat($product['countInCart'] * $product['price']) ?>
                                <?php echo $data['currency']; ?>
                              </td>
                              <td class="c-table__remove remove-cell">
                                  <a class="deleteItemFromCart delete-btn js-delete-from-cart"
                                     href="<?php echo SITE ?>/cart"
                                     data-delete-item-id="<?php echo $product['id'] ?>"
                                     data-property="<?php echo $product['property'] ?>"
                                     data-variant="<?php echo(!empty($product['variantId']) ? $product['variantId'] : 0); ?>"
                                     title="<?php echo lang('deleteProduct'); ?>">
                                      <div class="icon__cart">
                                          <svg class="icon icon--remove">
                                              <use xlink:href="#icon--remove"></use>
                                          </svg>
                                      </div>
                                  </a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                    </table>
                </div>
            </form>

          <?php if ((class_exists('OikDisountCoupon')) ||
            (class_exists('PromoCode'))): ?>
              <div class="c-promo-code">
                  [promo-code]
              </div>
          <?php endif; ?>

            <div class="c-table__footer total-price-block">
                <?php if(class_exists('bonusCard')) { ?>
                  <div class="cart-bonuses">
                    <i class="fa fa-gift cart-bonuses-icon" aria-hidden="true"></i>
                    [bonuses]
                  </div>
                <?php } ?>

                <div class="c-table__total">
                    <span class="title">
                        <?php echo lang('toPayment'); ?>:
                    </span>
                    <span class="total-sum">
                        <strong>
                            <?php echo priceFormat($data['totalSumm']) ?>&nbsp;
                            <?php echo $data['currency']; ?>
                        </strong>
                    </span>
                </div>

              <?php if (!URL::isSection('order')): ?>
                  <form action="<?php echo SITE ?>/order"
                        method="post"
                        class="checkout-form">
                      <button type="submit"
                              class="checkout-btn default-btn success"
                              name="order"
                              title="Оформить заказ"
                              value="<?php echo lang('checkout'); ?>">
                        <?php echo lang('checkout'); ?>
                      </button>
                  </form>
              <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="c-alert c-alert--blue empty-cart-block alert-info"
         style="display:<?php echo !$data['isEmpty'] ? 'block' : 'none'; ?>">
      <?php echo lang('cartIsEmpty'); ?>
    </div>
</div>