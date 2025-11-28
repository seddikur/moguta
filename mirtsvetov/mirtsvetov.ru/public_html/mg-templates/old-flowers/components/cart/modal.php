<div class="modal modal-cart" data-modal="modal-cart">
	<div class="inner">
	    <div class="h2-like">Ваш заказ <span>/<span class="countsht"><?php echo $data['cart_count']; ?></span> шт.</span></div>
		<span class="close">Закрыть</span>
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
		  ?>
		
		    <tr>
		        <td class="img-cell">
					<a class="js-smallCartImgAnchor" href="<?php echo SITE . "/" . (isset($item['category_url']) ? $item['category_url'] : 'catalog/') . $item['product_url'] ?>" target="_blank">
						<img class="js-smallCartImg" src="<?php echo $thumbsArr[30]['main'] ?>"
					alt="<?php echo $item["title"] ?>">
					</a>
					<span class="count js-smallCartAmount"><?php echo $item['countInCart'] ?></span>
                </td>
                <td class="name-cell">
					<a href="<?php echo SITE . "/" . (isset($item['category_url']) ? $item['category_url'] : 'catalog/') . $item['product_url'] ?>" target="_blank">
						<?php echo $item['title'] ?>
					</a>
					<span class="property js-smallCartProperty"><?php echo $item['property_html'] ?></span>
					<div class="price-cell">
						<span class="js-cartPrice"><?php echo $item['priceInCart'] ?></span>
					</div>
                </td>
		        <td class="remove-cell">
		            <a href="#"
		               class="deleteItemFromCart js-delete-from-cart flex-center"
		               title="<?php echo lang('delete'); ?>"
		               data-delete-item-id="<?php echo $item['id'] ?>"
		               data-property="<?php echo $item['property'] ?>"
		               data-variant="<?php echo (!empty($item['variantId']) ? $item['variantId'] : 0); ?>"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE;?>/images/icons.svg#plus"></use></svg></a>
		        </td>
		    </tr>
		<?php } ?>
		
	    <template class="popupCartRowTemplate"
	              style="display:none;">
	        <?php $popupCartRow(); ?>
	    </template>
	    <div class="overflow-y">
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
	    <div class="total sum-list">
	        <div class="total-sum">
	          <?php echo lang('toPayment') ?>:
	            <span class="total-payment">
	                <?php
	                if (!empty($data['cart_price_wc'])) {
	                  echo $data['cart_price_wc'];
	                }
	                ?>
	            </span>
	        </div>
	    </div>
        <a class="button" href="<?php echo SITE ?>/order">Перейти к оформлению</a>
	</div>
</div>