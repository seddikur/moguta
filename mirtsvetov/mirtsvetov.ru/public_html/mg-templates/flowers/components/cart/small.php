<div style="display: none"><?php
	mgAddMeta('components/cart/cart.js');
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
			<td class="img-cell">
				<a class="js-smallCartImgAnchor" href="<?php echo $hrefAttr; ?>">
					<img class="js-smallCartImg" src="<?php echo $thumbsArr[30]['main'] ?>" srcset="<?php echo $thumbsArr[30]['2x'] ?> 2x" alt="<?php echo $item['title'] ?>"/>
				</a>
			</td>
			<td class="name-cell">
				<a class="js-smallCartProdAnchor" href="<?php echo $hrefAttr; ?>" target="_blank">
					<?php echo $item['title'] ?>
				</a>
				<span class="property js-smallCartProperty"><?php echo $item['property_html'] ?></span>
				<div class="price-cell">
				<span class="js-cartPrice"><?php echo $item['priceInCart'] ?></span>
				</div>
			</td>
			<td class="remove-cell">
				<a class="deleteItemFromCart js-delete-from-cart flex-center" data-delete-item-id="<?php echo $item['id'] ?>" data-property="<?php echo $item['property'] ?>" data-variant="<?php echo(!empty($item['variantId']) ? $item['variantId'] : 0); ?>">×</a>
			</td>
		</tr>
	<?php } ?>

	<a href="/order" class="flex align-center center relative icon-cart">
		<span class="countsht quantity-basket absolute  flex align-center center "><?php echo !empty($data['cart_count']) ? $data['cart_count'] : 0 ?></span>
	</a>
		
	<div class="small-cart" style="display: none">
		<template class="smallCartRowTemplate" style="display:none;"><?php $smallCartRow(); ?></template>
		<table class="small-cart-table">
		<?php
		if (!empty($data['dataCart'])) {
			foreach ($data['dataCart'] as $item) {
			$smallCartRow($item);
			}
		}
		?>
		</table>
		<ul class="total">
			<li class="total-sum">
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
				<a href="<?php echo SITE ?>/cart">
				<?php echo lang('cartLink'); ?>
				</a>
				<a href="<?php echo SITE ?>/order">
				<?php echo lang('cartCheckout'); ?>
				</a>
			</li>
		</ul>
	</div>

<?php $data = MG::getSmalCart(); ?>

<table>
	<thead>
		<tr>
			<th>Сорт</th>
			<th>См</th>
			<th>Кол-во шт.</th>
			<th>Кол-во баков.</th>
			<th>Стоимость.</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>
</div>