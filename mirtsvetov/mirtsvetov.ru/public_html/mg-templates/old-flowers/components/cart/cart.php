<?php mgAddMeta('components/cart/cart.js');?>
<div class="product-cart" style="display:<?php echo $data['isEmpty'] ? 'block' : 'none'; ?>">
    <div class="cart-wrapper">
        <form class="cart-form js-cart-form" method="post" action="<?php echo SITE ?>/cart">
            <table class="cart-table">
              <?php $i = 1;
              foreach ($data['productPositions'] as $product): ?>
                  <tr>
                      <td class="img-cell">
                          <a href="<?php echo $product["link"] ?>" target="_blank" class="cart-img">
                              <img src="<?php echo mgImageProductPath($product["image_url"], $product['id'], 'small') ?>"
                                   title="<?php echo $product["title"] ?>"
                                   alt="<?php echo $product["title"] ?>">
                          </a>
                      </td>
                      <td class="name-cell">
                          <a href="<?php echo $product["link"] ?>" target="_blank"><?php echo $product['title'] ?></a>
                        <?php echo $product['property_html'] ?>
                        <div class="price-cell js-cartPrice">
                            <?php echo MG::numberFormat($product['countInCart'] * $product['price']) ?>
                            <?php echo $data['currency']; ?>
                          </div>
                        <div class="count-cell">
                            <?php
                        
                            // Компонент выбора количества товара
                            component(
                              'amount',
                              [
                                'id' => $product['id'],
                                'maxCount' => $product['count'],
                                'count' => $product['countInCart'],
                                'type' => 'cart',
                                'increment'=> $product['multiplicity'],
                              ]
                            ); ?>
                        
                              <input type="hidden" name="property_<?php echo $product['id'] ?>[]" value="<?php echo $product['property'] ?>"/>
                          </div>
                      </td>
                      <td class="remove-cell">
                          <a class="deleteItemFromCart delete-btn js-delete-from-cart"
                             href="<?php echo SITE ?>/cart"
                             data-delete-item-id="<?php echo $product['id'] ?>"
                             data-property="<?php echo $product['property'] ?>"
                             data-variant="<?php echo(!empty($product['variantId']) ? $product['variantId'] : 0); ?>">
                            ×
                          </a>
                      </td>
                  </tr>
              <?php endforeach; ?>
            </table>
        </form>

      <?php if ((class_exists('OikDisountCoupon')) || (class_exists('PromoCode'))): ?>[promo-code]<?php endif; ?>

        <div class="total-price-block">
            <?php echo lang('toPayment'); ?>:
            <span class="total-sum">
                <strong>
                    <?php echo priceFormat($data['totalSumm']) ?>&nbsp;
                    <?php echo $data['currency']; ?>
                </strong>
            </span>

          <?php if (!URL::isSection('order')): ?>
              <form action="<?php echo SITE ?>/order" method="post" class="checkout-form">
                  <button type="submit" class="checkout-btn button success" name="order" value="<?php echo lang('checkout'); ?>"><?php echo lang('checkout'); ?></button>
              </form>
          <?php endif; ?>
        </div>
    </div>
</div>
<div class="empty-cart-block alert" style="display:<?php echo !$data['isEmpty'] ? 'block' : 'none'; ?>"><?php echo lang('cartIsEmpty');?></div>