<?php mgAddMeta('components/cart/cart.js');?>
<div class="product-cart" style="display:<?php echo $data['isEmpty'] ? 'block' : 'none'; ?>">
    <div class="cart-wrapper">
        <form class="cart-form js-cart-form" method="post" action="<?php echo SITE ?>/cart">
            <table class="cart-table">
                <thead>
                    <tr class="mobile-hide">
                        <th class="img-cell"></th>
                        <th class="name-cell">наименование</th>
                        <th class="count-cell">количество</th>
                        <th class="price-cell">стоимость</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
              <?php $i = 1; $sumCount = 0; $sumBak = 0;
              foreach ($data['productPositions'] as $product): ?>
                  <tr style="order: <?php echo $product['id'] .''. $product['variantId']; ?>">
                      <td class="img-cell mobile-hide">
                          <img src="<?php echo mgImageProductPath($product["image_url"], $product['id'], 'small') ?>"
                             title="<?php echo $product["title"] ?>"
                             alt="<?php echo $product["title"] ?>">
                        </td>
                      <td class="name-cell flex align-center">
                            <?php echo $product['title'] ?> <?php echo $product['property_html'] ?>
                      </td>

                      <?php if ($product['count'] && $product['price'] != 0): ?>
                        <td class="count-cell flex align-center">
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
                            ); ?> × <?php $opFieldsM = new Models_OpFieldsProduct($product['id']);
                                          $optFieldValue = '';
                                          if(!empty($product['variantId'])){
                                              $optFieldValue = $opFieldsM->get(21)['variant'][$product['variantId']]['value'];
                                          } else {
                                              $optFieldValue = $opFieldsM->get(21)['value'];
                                          }
                                          echo $optFieldValue; ?> шт                          
                            <input type="hidden" name="property_<?php echo $product['id'] ?>[]" value="<?php echo $product['property'] ?>"/>
                            <?php if ($product['count'] && $product['price'] != 0): ?>
                                <?php if ($product['countInCart'] >= $product['count']): ?>
                                  <div class="js-c-cart-max-count">Max: <?php echo $product['countInCart']; ?></div>
                                <?php endif; ?>
                             <?php endif; ?>
                        </td>

                        <td class="price-cell js-cartPrice flex align-center">
                            <?php echo MG::numberFormat($product['countInCart'] * $product['price']) ?>
                            <?php echo $data['currency']; ?>
                        </td>
                      <?php else: ?>
                        <td class="flex align-center">Товара нет в наличии</td>
                        <td></td>
                      <?php endif; ?>

                      <td class="remove-cell flex align-center">
                          <a class="deleteItemFromCart delete-btn js-delete-from-cart flex align-center center"
                             href="<?php echo SITE ?>/cart"
                             data-delete-item-id="<?php echo $product['id'] ?>"
                             data-property="<?php echo $product['property'] ?>"
                             data-variant="<?php echo(!empty($product['variantId']) ? $product['variantId'] : 0); ?>">
                            <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#close"></use></svg>
                          </a>
                      </td>
                  </tr>

                  <?php 
                    $sumCount += $product['countInCart']; 
                    $sumBak += $product['opf_21'] * $product['countInCart'];
                  ?>
              <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total-price-block">
                Количество: <b class="cart_bak_total"><?php echo $sumCount; ?></b> / <b class="cart_qty_total"><?php echo $sumBak; ?></b> <b>шт.</b><br>
                Стоимость:
                <span class="total-sum">
                    <strong><?php echo priceFormat($data['totalSumm']) ?>&nbsp;<?php echo $data['currency']; ?></strong>
                </span>
            </div>
        </form>
    </div>
</div>
<div class="empty-cart-block alert" style="display:<?php echo !$data['isEmpty'] ? 'block' : 'none'; ?>"><?php echo lang('cartIsEmpty');?></div>