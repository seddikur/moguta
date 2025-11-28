<?php mgAddMeta('components/catalog/item/item.css'); ?>
<?php mgAddMeta('components/product/variants/sizemap/sizemap.css'); ?>
<?php mgAddMeta('components/product/variants/variants.js'); ?>
<?php mgAddMeta('components/product/product.js'); ?>
<?php mgAddMeta('components/product/variants/variants.css'); ?>

<div class="c-goods__item mini-item product-wrapper js-catalog-item">

  <?php
  // Кнопки добавлени товара в избранное
  component(
    'favorites/btns',
    $data['item']
  );
  ?>

    <span class="hidden">
        <?php echo $data['item']["title"] ?>
    </span>

    <div class="c-goods__left">
        <a class="c-goods__img"
           title="<?php echo $data['item']["title"] ?>"
           href="<?php echo $data['item']["link"] ?>">

            <div class="c-ribbon">
              <?php
              $hide = '';
              if ($data['item']['price'] == 0 && class_exists('chdRequestPrice')) {
                $hide = 'style="display:none"';
              }
              if (!empty($data['item']['price']) && !empty($data['item']['old_price']) && intval( str_replace(' ', '', $data['item']['old_price'])) > intval(str_replace(' ', '', $data['item']['price']))) {
                  $price = floatval(MG::numberDeFormat($data['item']['price']));
                  $oldprice = floatval(MG::numberDeFormat($data['item']['old_price']));
                  $calculate = ($oldprice - $price) / ($oldprice / 100);
                  $result = "" . round($calculate) . " %";
                  echo '<div class="price-box" ' . $hide . '><div class="c-ribbon__sale js-discount-sticker"> -' . $result . ' </div></div>';
              } else {
                echo '<div class="price-box" ' . $hide . '><div class="c-ribbon__sale js-discount-sticker" style="display:none;"></div></div>';
              }

              echo $data['item']['new'] ? '       <div class="c-ribbon__new">' . lang('stickerNew') . '</div>' : '';
              echo $data['item']['recommend'] ? ' <div class="c-ribbon__hit">' . lang('stickerHit') . '</div>' : '';
              ?>

            </div>


            <!-- Изображение товара -->
          <?php
          // Получаем массив миниатюр
          $thumbsArr = getThumbsFromUrl(explode('|', $data['item']['image_url'])[0], $data['item']['id']); ?>

            <img class="mg-product-image js-catalog-item-image"
                 src="<?php echo $thumbsArr[30]['main'] ?>"
                 srcset="<?php echo $thumbsArr[30]['2x'] ?> 2x"
                 alt="<?php echo $data['item']['images_alt'][0] ?>"
                 title="<?php echo $data['item']['images_title'][0] ?>"
                 data-transfer="true"
                 data-product-id="<?php echo $data['item']['id'] ?>"
                 loading="lazy"
                 width="200"
                 height="200">
            <!--   Изображение товара – конец   -->

        </a>

      <?php
      if (class_exists('ProductCommentsRating')) { ?>
        [mg-product-rating id="<?php echo $data['id'] ?>"]
      <?php } else if (class_exists('Rating')) { ?>
          [rating id = "<?php echo $data['item']['id'] ?>"]
      <?php } ?>

    </div>

    <div class="c-goods__right">
        <div class="c-goods__price price-box" style="<?php echo $data['item']["price"] == 0 && class_exists('chdRequestPrice') ? 'display:none' : '' ?>">
          <?php if (!empty($data['item']['price']) && !empty($data['item']['old_price']) && intval( str_replace(' ', '', $data['item']['old_price'])) > intval(str_replace(' ', '', $data['item']['price']))) { ?>
              <s class="c-goods__price--old product-old-price old-price">
                <?php echo $data['item']['old_price'] . $data['item']['currency']; ?>
              </s>
          <?php } else { ?>
            <s class="c-goods__price--old product-old-price old-price" style="display: none;">
              </s>
          <?php } ?>
            <div class="c-goods__price--current product-price js-change-product-price price">
                <span><?php echo MG::priceCourse($data['item']["price"]); ?></span>
                <span><?php echo $data['item']['currency']; ?></span>
            </div>
        </div>

        <a class="c-goods__title"
           title="<?php echo $data['item']["title"] ?>"
           href="<?php echo $data['item']["link"] ?>">
            <span><?php echo $data['item']["title"] ?></span>
        </a>

        <?php if(class_exists('bonusCard')) { ?>
            <div class="cart-bonuses c-goods__bonuses">
                [bonuses product=<?php echo MG::numberDeFormat($data['item']["price"])?>]
            </div>
        <?php } ?>


        <div class="c-goods__description">
          <?php
          if ($data['item']["short_description"]) {
            echo MG::textMore($data['item']["short_description"], 80);
          } else {
            echo MG::textMore($data['item']["description"], 80);
          }
          ?>
        </div>

        <div class="c-goods__footer">
          <?php
           $size = '';
           $color = '';
           foreach ($data['item']['variants'] as $variant) {
             if ($variant['id'] == $data['item']['variant_exist']) {
               $size = $variant['size'];
               $color = $variant['color'];
             }
           }
           ?>
            <form action="<?php echo SITE . $data['item']['liteFormData']['action'] ?>"
                  method="<?php echo $data['item']['liteFormData']['method'] ?>"
                  class="property-form js-product-form <?php echo $data['item']['liteFormData']['catalogAction'] ?>"
                  data-product-id='<?php echo $data['item']['id'] ?>'
                  data-product-variant="<?php echo $data['item']['variant_exist']; ?>" data-product-color="<?php echo $color; ?>" data-product-size="<?php echo $size; ?>">

                <div class="c-form">
                  <?php
                  // Варианты товара, если разрешены в настройках
                  if (MG::getSetting('printVariantsInMini') == 'true') {
                    component(
                      'product/variants',
                      $data['item']
                    );
                  }
                  ?>
                </div>

                <div class="buy-container">
                  
                    <div class="c-buy js-product-controls">
                    <?php if (class_exists('chdRequestPrice')) : ?>
                          [chd-catalog-button-request-price link="<?php echo $data['item']['link'] ?>"]
                    <?php endif; ?>
                        <?php if (MG::getSetting('printQuantityInMini') == 'true') {
                          component(
                            'amount',
                            [
                              'id' => $data['item']['id'],
                              'maxCount' => $data['item']['liteFormData']['maxCount'],
                              'count' => MG::get('settings')['useMultiplicity'] == 'true' ? $data['item']['multiplicity'] : '1',
                              'increment'=> MG::get('settings')['useMultiplicity'] == 'true' ? $data['item']['multiplicity'] : '1',
                            ]
                          );
                        }
                        ?>
                          <div class="c-buy__buttons">
                            <?php
                            // Кнопка добавления товара в корзину
                            $data['item']['isMiniCard'] = 'true';
                            component(
                              'cart/btn/add',
                              $data['item']
                            );
                            ?>

                            <?php
                            if (
                              (in_array(EDITION, array('market', 'gipermarket', 'saas'))) &&
                              ($data['item']['liteFormData']['printCompareButton'] == 'true')
                            ) {
                              component(
                                'compare/btn/add',
                                $data['item']
                              );
                            }
                            ?>
                          </div>

                          <!-- Плагин купить одним кликом-->
                        <?php if (class_exists('BuyClick')): ?>
                            [buy-click id="<?php echo $data['item']['id'] ?>"]
                        <?php endif; ?>
                          <!--/ Плагин купить одним кликом-->
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
