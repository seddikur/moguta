<?php
/**
 *  Файл представления Product - выводит сгенерированную движком информацию на странице карточки товара.
 *  В этом файле доступны следующие данные:
 *   <code>
 *   $data['category_url'] => URL категории в которой находится продукт
 *   $data['product_url'] => Полный URL продукта
 *   $data['id'] => id продукта
 *   $data['sort'] => порядок сортировки в каталоге
 *   $data['cat_id'] => id категории
 *   $data['title'] => Наименование товара
 *   $data['description'] => Описание товара
 *   $data['price'] => Стоимость
 *   $data['url'] => URL продукта
 *   $data['image_url'] => Главная картинка товара
 *   $data['code'] => Артикул товара
 *   $data['count'] => Количество товара на складе
 *   $data['activity'] => Флаг активности товара
 *   $data['old_price'] => Старая цена товара
 *   $data['recommend'] => Флаг рекомендуемого товара
 *   $data['new'] => Флаг новинок
 *   $data['thisUserFields'] => Пользовательские характеристики товара
 *   $data['images_product'] => Все изображения товара
 *   $data['currency'] => Валюта магазина.
 *   $data['propertyForm'] => Форма для карточки товара
 *     $data['liteFormData'] => Упрощенная форма для карточки товара
 *   $data['meta_title'] => Значение meta тега для страницы,
 *   $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *   $data['meta_desc'] => Значение meta_desc тега для страницы,
 *   $data['wholesalesData'] => Информация об оптовых скидках,
 *   $data['storages'] => Информация о складах,
 *   $data['remInfo'] => Информация при отсутсвии товара,
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['thisUserFields']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['thisUserFields']; ?>
 *   </code>
 *
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику логику.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Views
 */
// Установка значений в метатеги title, keywords, description.
mgSEO($data);
?>

<div class="c-product product-details-block">
    <div class="l-row" itemscope itemtype="http://schema.org/Product">
        <div class="l-col min-0--12">
            <div class="product-status js-product-page product-wrapper">

                <?php if (class_exists('BreadCrumbs')): ?>
                    [brcr]
                <?php endif; ?>

                <div class="l-row">

                    <div class="l-col min-0--12 min-768--6">
                        <?php
                        // Карусель изображений товара
                        component(
                            'product/images',
                            $data
                        );
                        ?>
                    </div>

                    <div class="l-col min-0--12 min-768--6">

                        <div class="c-product__content buy-block">
                            <div class="buy-block-inner">
                                <div class="product-bar">
                                    <div class="c-product__row">
                                        <h1 class="c-title" itemprop="name">
                                            <?php echo $data['title'] ?>
                                        </h1>
                                    </div>

                                    <div class="c-product__row">
                                        <div class="c-product__block">
                                            <div class="c-product__block--left">
                                                <div class="c-product__row">

                                                    <?php
                                                    if (MG::getSetting('printCode') !== 'false') {
                                                        // Блок с артикулом товара
                                                        component(
                                                            'product/code',
                                                            $data['code']
                                                        );
                                                    }
                                                    ?>

                                                    <?php if (MG::getSetting('printCount') !== 'false') { ?>
                                                    <div class="available">
                                                        <?php
                                                        // Блок с количеством товара
                                                        component(
                                                            'product/count',
                                                            $data
                                                        );
                                                        ?>
                                                    </div>
                                                    <?php } ?>
                                                </div>

                                                <?php if (class_exists('NonAvailable')): ?>
                                                    <div class="c-product__row">
                                                        [non-available id="<?php echo $data['id'] ?>"]
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (MG::getSetting('printUnits') !== 'false') { ?>
                                                <div class="c-product__row">
                                                    <ul class="product-status-list">
                                                        <li style="display:<?php echo (!$data['weight']) ? 'none' : 'block'; ?>">
                                                            <?php echo lang('productWeight1'); ?>
                                                            <span class="label-black weight">
                                                                <?php echo $data['weightCalc'] ?>
                                                            </span>
                                                            <?php echo lang('weightUnit_' . $data['weightUnit']); ?>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <?php } ?>
                                            </div>
                                            <div class="c-product__block--right">
                                                <div class="c-product__row" style="<?php echo MG::getSetting('printCost') !== 'false' ? '' : 'display: none;' ?>">
                                                    <div class="default-price price-box" style="<?php echo class_exists('chdRequestPrice') && MG::priceCourse($data['price']) == '0' ? 'display:none' : '' ?>">
                                                        <div class="product-price">
                                                            <ul itemprop="offers" itemscope
                                                                itemtype="http://schema.org/Offer"
                                                                class="product-status-list">

                                                                <li>
                                                                    <div class="c-product__price c-product__price--current product-default-price normal-price">
                                                                        <div class="c-product__price--title">
                                                                            <?php echo lang('productPrice'); ?>
                                                                        </div>
                                                                        <span class="c-product__price--value price js-change-product-price">
                                                                            <span itemprop="price"
                                                                                  content="<?php echo MG::numberDeFormat($data['price']); ?>">
                                                                                <?php echo $data['price'] ?>
                                                                            </span>
                                                                            <span itemprop="priceCurrency"
                                                                                  content="<?php echo MG::getSetting('currencyShopIso'); ?>">
                                                                                <?php echo $data['currency']; ?>
                                                                            </span>

                                                                            <link itemprop="url"
                                                                                  href="<?php echo SITE . URL::getClearUri() ?>">
							                                                <link itemprop="availability" href="http://schema.org/<?php echo ($data['count'] === 0 || $data['count'] === '0') ? "OutOfStock" : "InStock" ?>">

                                                                        </span>
                                                                    </div>
                                                                </li>
                                                                <?php if(class_exists('bonusCard')) { ?>
                                                                    <li class="c-product__bonuses">
                                                                        <div class="cart-bonuses">
                                                                            [bonuses product=<?php echo MG::numberDeFormat($data['price'])?>]
                                                                        </div>
                                                                    </li>
                                                                <?php } ?>
                                                                <?php if (!empty($data['price']) && !empty($data['old_price']) && intval( str_replace(' ', '', $data['old_price'])) > intval(str_replace(' ', '', $data['price']))) { ?>
                                                                <li class="js-old-price-container">
                                                                    <div class="c-product__price c-product__price--old old">
                                                                        <div class="c-product__price--title">
                                                                            <?php echo lang('productOldPrice'); ?>
                                                                        </div>
                                                                        <s class="c-product__price--value old-price">
                                                                            <?php echo MG::numberFormat($data['old_price']) . " " . $data['currency']; ?>
                                                                        </s>
                                                                    </div>
                                                                </li>
                                                                <?php } else { ?>
                                                                    <li class="js-old-price-container" style="display: none;">
                                                                    <div class="c-product__price c-product__price--old old">
                                                                        <div class="c-product__price--title">
                                                                            <?php echo lang('productOldPrice'); ?>
                                                                        </div>
                                                                        <s class="c-product__price--value old-price">
                                                                        </s>
                                                                    </div>
                                                                </li>
                                                                <?php } ?>
                                                            </ul>

                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                                <div class="c-product__row">

                                                    <?php if (class_exists('Rating')): ?>
                                                        <div class="c-product__row">
                                                            [rating id ="<?php echo $data['id'] ?>"]
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (class_exists('ProductCommentsRating')): ?>
                                                        <div class="c-product__row">
                                                            [mg-product-rating id="<?php echo $data['id'] ?>"]
                                                        </div>
                                                    <?php endif; ?>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="c-product__row product-opfields-data">
                                        <?php
                                        // Дополнительные поля товара
                                        component(
                                            'product/opfields',
                                            $data
                                        );
                                        ?>
                                    </div>

                                    <div class="c-product__row wholesales-data">
                                        <?php
                                        // Оптовые цены
                                        component(
                                            'product/wholesales',
                                            $data['wholesalesData']
                                        );
                                        ?>
                                    </div>

                                    <div class="c-product__row">
                                        <?php
                                        // Склады
                                        component(
                                            'product/storages',
                                            $data
                                        );
                                        ?>


                                        <form action="<?php echo SITE . $data['liteFormData']['action'] ?>" method="<?php echo $data['liteFormData']['method'] ?>" class="property-form js-product-form <?php echo $data['liteFormData']['catalogAction'] ?>" data-product-id='<?php echo $data['liteFormData']['id'] ?>' data-product-variant="<?php echo $data['variant']; ?>" data-product-color="<?php echo $data['variants'][$data['variant']]['color']; ?>" data-product-size="<?php echo $data['variants'][$data['variant']]['size']; ?>">

                                            <div class="c-goods__footer">
                                                <div class="c-form">
                                                    <?php
                                                    // Варианты товара
                                                    component(
                                                        'product/variants',
                                                        $data
                                                    );
                                                    ?>

                                                    <?php
                                                    // Сложные характеристики – чекбоксы, радиокнопки, селекты
                                                    component(
                                                        'product/html-properties',
                                                        $data['propertyForm']['htmlProperty']
                                                    );
                                                    ?>

                                                </div>

                                                <div class="c-buy js-product-controls">

                                                    

                                                    <div class="c-buy__buttons">
                                                    <?php
                                                        component(
                                                            'amount',
                                                            [
                                                                'id' => $data['id'],
                                                                'maxCount' => $data['liteFormData']['maxCount'],
                                                                'count' => MG::get('settings')['useMultiplicity'] == 'true' ? $data['multiplicity'] : '1',
                                                                'increment'=> MG::get('settings')['useMultiplicity'] == 'true' ? $data['multiplicity'] : '1',
                                                            ]
                                                        );
                                                        ?>
                                                        <?php
                                                        // Кнопка добавления товара в корзину
                                                        if (MG::getSetting('printBuy') !== 'false') {
                                                            component(
                                                                'cart/btn/add',
                                                                $data
                                                            );
                                                        }
                                                        ?>

                                                        <?php
                                                        if (
                                                            (in_array(EDITION, array('market', 'gipermarket', 'saas'))) &&
                                                            ($data['liteFormData']['printCompareButton'] == 'true')
                                                        ) {
                                                            // Кнопка добавления товара в сравнение
                                                            component(
                                                                'compare/btn/add',
                                                                $data
                                                            );
                                                        }
                                                        ?>
                                                        <?php if (class_exists('WantDiscountPlugin')) { ?>
                                                            [want-discount_addtowishlist product=<?php echo $data['id']; ?>]
                                                        <?php } ?>
                                                        
                                                        <?php if (class_exists('chdRequestPrice')): ?>
                                                            [chd-request-price id=<?php echo $data['id']?>]
                                                        <?php endif; ?>

                                                        <!-- Плагин купить одним кликом-->
                                                        <?php if (class_exists('BuyClick')): ?>
                                                            [buy-click id="<?php echo $data['id'] ?>"]
                                                        <?php endif; ?>
                                                        <!--/ Плагин купить одним кликом-->
                                                        
                                                    </div>
                                                    
                                                </div>
                                                <?php if (class_exists('YandexShare')) { ?>
                                                    <div class="ya-share-wrapper">
                                                        [yandex-share]
                                                    </div>  
                                                <?php } ?>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="l-col min-0--12">
                        <?php
                        component('tabs', $data, 'tabs_product');
                        ?>
                    </div>

                </div>

            </div>
        </div>

        <?php
        // Карусель «С этим товаром покупают»
        if(!empty($data['related'])){
        $relatedProducts = null;
        $relatedCurrency = null;
        if (isset($data['related']['products'])) {
          $relatedProducts = $data['related']['products'];
        }
        if (isset($data['related']['currency'])) {
          $relatedCurrency = $data['related']['currency'];
        }
        component(
            'catalog/carousel',
            [
                'items' => $relatedProducts,
                'title' => lang('relatedAdd'),
                'currency' => $relatedCurrency
            ]
         );}
        ?>

        <?php if (class_exists('RecentlyViewed')) { ?>
            <div class="l-col min-0--12">
                <div class="c-carousel__title">
                <span class="c-carousel__title--span">
                    <?php echo lang('RecentlyViewed'); ?>
                </span>
                </div>
                [recently-viewed countPrint=6 count=6 random=1]
            </div>
        <?php } ?>

        <div class="l-col min-0--12">
            <?php if (class_exists('SetGoods')): ?>
                [set-goods id="<?php echo $data['id'] ?>"]
            <?php endif; ?>
        </div>
    </div>
</div>
