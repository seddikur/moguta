<?php
    mgAddMeta('components/cart/catalog/cart.js');
    mgAddMeta('components/cart/catalog/cart.css');

    $data = mgGetCart(); 
?>

<?php
    // Шаблон товара для добавления в корзину через js и вывода через php
    $template = function ($product = ['count' => 1]) { ?>
        <tr class="c-cart__row js-c-cart-item" data-position="<?php echo $product['position'] ?: 0; ?>" 
            style="order: <?php echo $product['id'] . $product['variantId']; ?>">
            <?php 
                $parts = explode(" ", $product['title']);
                $parts = array_filter(array_map('trim', $parts));
                $variant_name = array_slice($parts, -2);
                $name = array_slice($parts, 0, -2);
                $variant_name = implode(" ", $variant_name);
                $name = implode(" ", $name);
            ?>
            <td class="c-cart__col c-cart__col_name js-c-cart-name"><?php echo $name; ?>, <?php echo $variant_name; ?></td>
            <td class="c-cart__col c-cart__col_count js-c-cart-count">
                <?php echo $product['countInCart']; ?> × <?php echo $product['opf_21']; ?> шт.
                <?php if ($product['count'] && $product['price'] != 0): ?>
                    <?php if ($product['countInCart'] >= $product['count']): ?>
                        <div class="js-c-cart-max-count">Max: <?php echo $product['countInCart']; ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td class="c-cart__col c-cart__col_price-in-cart js-c-cart-price-in-cart"><?php echo $product['priceInCart']; ?></td>
            <td class="c-cart__col">
                <button type="button" class='center align-center flex js-c-cart-delete' 
                        data-product="<?php echo $product['id']; ?>" 
                        data-variant="<?php echo $product['variantId']; ?>" 
                        data-property="<?php echo $product['property']; ?>"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE; ?>/images/icons.svg#close"></use></svg></button>
            </td>
        </tr>
<?php } ?>



<div class="right-part catalog-cart">
    <div class="inner flex column nowrap">
        <div class="overflow-y">

            <?php if (class_exists('LegalEntity')): ?>
                <?php if (!URL::isSection('order')): ?>
                    [p4-select-legal-entity]
                <?php endif; ?>
                [p4-manager-info]
                <div class="mobile-hide">
                    [p4-status-legal-entity]
                </div>
            <?php endif; ?>
            
            <table class="mobile-hide">
	            <thead class="c-cart__header js-c-cart-header" style="display:<?php echo ($data['cart_count']) ? '' : 'none'; ?>">
                    <tr class="c-cart__row">
                        <th class="c-cart__col c-cart__col_name">наименование</th>
                        <th class="c-cart__col c-cart__col_count">количество</th>
                        <th class="c-cart__col c-cart__col_price-in-cart">стоимость</th>
                        <th></th>
                    </tr>
	            </thead>

	            <tbody class="c-cart__body js-c-cart-body">
                    <?php if ($data['cart_count']): ?>
                            <?php 
                                foreach ($data['dataCart'] as $key => $product) {
                                    $product['position'] = $key;
                                    $template($product);
                                    $sumBak += $product['opf_21'] * $product['countInCart'];
                                } 
                            ?>
                    <?php else: ?>
                        <tr class="c-cart__empty">
                            <td>В корзине нет товара</td>
                        </tr>
                    <?php endif; ?>  
                </tbody>
                 <tfoot class="c-cart__footer js-c-cart-footer" style="display:<?php echo ($data['cart_count']) ? '' : 'none'; ?>">
                    <tr class="c-cart__row">
                        <td class="c-cart__col c-cart__col_name">Итого</td>
                        <td class="c-cart__col c-cart__col_count js-c-cart-footer-count"><?php echo $data['cart_count']; ?> (<?php echo $sumBak; ?>) шт.</td>
                        <td class="c-cart__col c-cart__col_price-in-cart js-c-cart-footer-price-in-cart"><?php echo $data['cart_price_wc']; ?></td>
                        <td class="c-cart__col"></td>
                     </tr>
                </tfoot>
                <template class="js-c-cart-template" style="display:none;">
                    <?php $template(); ?>
                </template>
            </table>
        </div>
        <a class="button" href="/order">Оформить заявку</a>
    </div>
</div>