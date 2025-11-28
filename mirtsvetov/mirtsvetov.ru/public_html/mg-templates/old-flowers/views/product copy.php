<?php mgSEO($data);?>

<div class="max product-details-block">
    [brcr]
    <div class="product-status js-product-page" itemscope itemtype="http://schema.org/Product">
        <div class="flex space-between">
            <div class="left-part ds">
                <?php component('product/images', $data);?>
            </div>
            <div class="right-part">
                <div class="sticky flex column">
                    <h1 itemprop="name"><?php echo $data['title'] ?></h1>
                    <div class="made-in flex align-center space-between">
                        <div class="country-logo-part flex align-center">
                            <?php
                                $brand = $data['thisUserFields'][48]['data'][0]['prop_data_id'];
                                if($brand) {
                                    $res = DB::query('SELECT * FROM `'.PREFIX.'mg-brand` WHERE data_id = '.$brand.' ORDER BY brand ASC LIMIT 31'); while($row = DB::fetchAssoc($res)) {
                                        echo '<a class="logo" href="/'.$row['short_url'].'"><img src="/'.$row['url'].'" alt="'.$row['brand'].'"></a>';
                                    }
                                }
                            ?>
                            <?php if($data['thisUserFields'][8]['data'][0]['name']) {
                                echo '<div class="country">'.$data['thisUserFields'][8]['data'][0]['name'].'</div>';
                            }?>
                        </div>
                    </div>
                    <?php
                        $series = substr($data['code'], 0, 2);
                        $cat = $data['cat_id'];
                        $catalog = new Models_Catalog;                        
                        $models = $catalog->getListByUserFilter(100, 'p.cat_id = "'.$cat.'" AND p.activity = 1 AND p.code LIKE "'.$series.'%" ORDER BY p.sort DESC')['catalogItems']; if ($models):?>
                            <div class="series-models">
                                <div class="title">Модели серии</div>
                                <div class="flex">
                                    <?php foreach($models as $model) {
                                        $url = SITE.'/'.$model['category_url'].$model['product_url'];
                                        $active = ($url == URL::getUrl())?'active':'';
                                        echo '<a class="'.$active.'" href="'.$url.'">'.$model['code'].'</a>';
                                    }?>
                                </div>
                            </div>
                        <?php endif;
                    ?>
                   
                    <ul class="properties">
                        <?php foreach (array_slice($data['thisUserFields'], 0, 4) as $prop) { ?>
                            <li class="prop-item flex space-between">
                                <span class="prop-name"><?php echo $prop['name'];?></span>
                                <span class="prop-separator"></span>
                                <span class="prop-spec"><?php echo $prop['data']['0']['name'];?></span>
                            </li>
                        <?php } ?>
                        <a class="link show-spec">Все характеристики</a>
                    </ul>
                    <div class="advantage content">
                        <?php echo $data['short_description'];?>
                    </div>
                </div>
            </div>
            <div class="buy-block flex column">
                <div class="price-block product-status-list" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                    <div class="title">
                        <?php if($data['price'] == '0'):?>
                            Цена по запросу
                        <?php else:?>
                            <div class="price js-change-product-price">
                                <span itemprop="price" content="<?php echo MG::numberDeFormat($data['price']); ?>">
                                    <?php echo $data['price'] ?>
                                </span>
                                <span itemprop="priceCurrency"
                                      content="<?php echo MG::getSetting('currencyShopIso'); ?>">
                                    <?php echo $data['currency']; ?>
                                </span>
                                <link itemprop="url" href="<?php echo SITE . URL::getClearUri() ?>">
                                <link itemprop="availability" href="http://schema.org/<?php echo ($data['count'] === 0 || $data['count'] === '0') ? "OutOfStock" : "InStock" ?>">
                            </div>
                            <?php if (!empty($data['price']) && !empty($data['old_price']) && intval( str_replace(' ', '', $data['old_price'])) > intval(str_replace(' ', '', $data['price']))):?>
                                <div class="old-price">
                                    <?php echo MG::numberFormat($data['old_price']) . " " . $data['currency']; ?>
                                </div>
                            <?php endif;?>
                        <?php endif;?>
                    </div>
                </div>
                <div class="btn-block">
                    <button class="button show-modal" data-modal="product-order">Получить предложение</button>
                    <div class="sub-btn">Мы свяжемся для уточнения задачи и предложим оптимальные варианты</div>
                </div>
                <ul class="flex column">
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_14004_4348)">
                                <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="#008A43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.579 9.96512 20.258 9.77251 19.9887C9.5799 19.7194 9.31074 19.5143 9 19.4C8.69838 19.2669 8.36381 19.2272 8.03941 19.286C7.71502 19.3448 7.41568 19.4995 7.18 19.73L7.12 19.79C6.93425 19.976 6.71368 20.1235 6.47088 20.2241C6.22808 20.3248 5.96783 20.3766 5.705 20.3766C5.44217 20.3766 5.18192 20.3248 4.93912 20.2241C4.69632 20.1235 4.47575 19.976 4.29 19.79C4.10405 19.6043 3.95653 19.3837 3.85588 19.1409C3.75523 18.8981 3.70343 18.6378 3.70343 18.375C3.70343 18.1122 3.75523 17.8519 3.85588 17.6091C3.95653 17.3663 4.10405 17.1457 4.29 16.96L4.35 16.9C4.58054 16.6643 4.73519 16.365 4.794 16.0406C4.85282 15.7162 4.81312 15.3816 4.68 15.08C4.55324 14.7842 4.34276 14.532 4.07447 14.3543C3.80618 14.1766 3.49179 14.0813 3.17 14.08H3C2.46957 14.08 1.96086 13.8693 1.58579 13.4942C1.21071 13.1191 1 12.6104 1 12.08C1 11.5496 1.21071 11.0409 1.58579 10.6658C1.96086 10.2907 2.46957 10.08 3 10.08H3.09C3.42099 10.0723 3.742 9.96512 4.0113 9.77251C4.28059 9.5799 4.48572 9.31074 4.6 9C4.73312 8.69838 4.77282 8.36381 4.714 8.03941C4.65519 7.71502 4.50054 7.41568 4.27 7.18L4.21 7.12C4.02405 6.93425 3.87653 6.71368 3.77588 6.47088C3.67523 6.22808 3.62343 5.96783 3.62343 5.705C3.62343 5.44217 3.67523 5.18192 3.77588 4.93912C3.87653 4.69632 4.02405 4.47575 4.21 4.29C4.39575 4.10405 4.61632 3.95653 4.85912 3.85588C5.10192 3.75523 5.36217 3.70343 5.625 3.70343C5.88783 3.70343 6.14808 3.75523 6.39088 3.85588C6.63368 3.95653 6.85425 4.10405 7.04 4.29L7.1 4.35C7.33568 4.58054 7.63502 4.73519 7.95941 4.794C8.28381 4.85282 8.61838 4.81312 8.92 4.68H9C9.29577 4.55324 9.54802 4.34276 9.72569 4.07447C9.90337 3.80618 9.99872 3.49179 10 3.17V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z" stroke="#008A43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </g>
                            <defs>
                                <clipPath id="clip0_14004_4348">
                                    <rect width="24" height="24" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                        Пуско-наладка <span>под ключ</span>
                    </li>
                    <li>

                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C12 22 20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" stroke="#008A43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Гарантия <span>12 месяцев</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16.5 9.4L7.5 4.21" stroke="#008A43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M21 16V8C20.9996 7.64928 20.9071 7.30481 20.7315 7.00116C20.556 6.69752 20.3037 6.44536 20 6.27L13 2.27C12.696 2.09446 12.3511 2.00205 12 2.00205C11.6489 2.00205 11.304 2.09446 11 2.27L4 6.27C3.69626 6.44536 3.44398 6.69752 3.26846 7.00116C3.09294 7.30481 3.00036 7.64928 3 8V16C3.00036 16.3507 3.09294 16.6952 3.26846 16.9988C3.44398 17.3025 3.69626 17.5546 4 17.73L11 21.73C11.304 21.9055 11.6489 21.998 12 21.998C12.3511 21.998 12.696 21.9055 13 21.73L20 17.73C20.3037 17.5546 20.556 17.3025 20.7315 16.9988C20.9071 16.6952 20.9996 16.3507 21 16Z" stroke="#008A43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M3.26953 6.96L11.9995 12.01L20.7295 6.96" stroke="#008A43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12 22.08V12" stroke="#008A43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Наличие <span>на складе</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="bottom-part" id="tabs">
            <?php component('product/tabs', $data);?>
        </div>
    </div>
</div>
<div class="related max">
    <div class="catalog-slider">
        <div class="h2-like">Возможно, вам понравится</div>
        <?php component(
            'catalog/carousel',
            [
                'items' => $data['related']['products'],
                'title' => lang('relatedAdd'),
                'currency' => $data['related']['currency']
            ]
        );?>
    </div>
</div>
<?php if (class_exists('RecentlyViewed')) { ?>
    <div class="related max">
        <div class="h2-like">Недавно просмотренные товары</div>
        [recently-viewed countPrint=4 count=5 random=1]
    </div>
<?php } ?>
<div class="max">
    <div class="advantages-leasing__wrapper">
        <div class="advantages-leasing flex column">
            <div class="title">Лизинг - большое будущее Вашего бизнеса!</div>
            <div class="sub-title">Лизинг - это возможность приобрести Оборудование с минимальными <br> единовременными затратами</div>
            <ul>
                <li class="flex align-center">
                    <div class="svg-block flex align-center center">
                        <svg version="1.1" id="Слой_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 10 8" style="enable-background:new 0 0 10 8;" xml:space="preserve">
                            <path d="M3.5,7.5C3.2,7.5,3,7.4,2.8,7.2L0.3,4.7c-0.4-0.4-0.4-1,0-1.4s1-0.4,1.4,0l1.8,1.8l4.8-4.8c0.4-0.4,1-0.4,1.4,0s0.4,1,0,1.4 L4.2,7.2C4,7.4,3.8,7.5,3.5,7.5z"></path>
                        </svg>
                    </div>
                    Минимальный взнос
                </li>
                <li class="flex align-center">
                    <div class="svg-block flex align-center center">
                        <svg version="1.1" id="Слой_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 10 8" style="enable-background:new 0 0 10 8;" xml:space="preserve">
                            <path d="M3.5,7.5C3.2,7.5,3,7.4,2.8,7.2L0.3,4.7c-0.4-0.4-0.4-1,0-1.4s1-0.4,1.4,0l1.8,1.8l4.8-4.8c0.4-0.4,1-0.4,1.4,0s0.4,1,0,1.4 L4.2,7.2C4,7.4,3.8,7.5,3.5,7.5z"></path>
                        </svg>
                    </div>
                    От 150 000 р
                </li>
                <li class="flex align-center">
                    <div class="svg-block flex align-center center">
                        <svg version="1.1" id="Слой_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 10 8" style="enable-background:new 0 0 10 8;" xml:space="preserve">
                            <path d="M3.5,7.5C3.2,7.5,3,7.4,2.8,7.2L0.3,4.7c-0.4-0.4-0.4-1,0-1.4s1-0.4,1.4,0l1.8,1.8l4.8-4.8c0.4-0.4,1-0.4,1.4,0s0.4,1,0,1.4 L4.2,7.2C4,7.4,3.8,7.5,3.5,7.5z"></path>
                        </svg>
                    </div>
                    Лизинг до 5 лет
                </li>
                <li class="flex align-center">
                    <div class="svg-block flex align-center center">
                        <svg version="1.1" id="Слой_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 10 8" style="enable-background:new 0 0 10 8;" xml:space="preserve">
                            <path d="M3.5,7.5C3.2,7.5,3,7.4,2.8,7.2L0.3,4.7c-0.4-0.4-0.4-1,0-1.4s1-0.4,1.4,0l1.8,1.8l4.8-4.8c0.4-0.4,1-0.4,1.4,0s0.4,1,0,1.4 L4.2,7.2C4,7.4,3.8,7.5,3.5,7.5z"></path>
                        </svg>
                    </div>
                    Покупка за неделю
                </li>
            </ul>
        </div>

        <div class="more-btn flex align-center ds center">
            <svg version="1.1" id="Слой_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                viewBox="0 0 24 24" style="enable-background:new 0 0 24 24;" xml:space="preserve">
                <path d="M17.9,6.6c-0.1-0.2-0.3-0.4-0.5-0.5C17.3,6,17.1,6,17,6H7C6.4,6,6,6.4,6,7s0.4,1,1,1h7.6l-8.3,8.3c-0.4,0.4-0.4,1,0,1.4
                    C6.5,17.9,6.7,18,7,18s0.5-0.1,0.7-0.3L16,9.4V17c0,0.6,0.4,1,1,1s1-0.4,1-1V7C18,6.9,18,6.7,17.9,6.6z" />
            </svg>
            Подробнее
        </div>
    </div>
</div>

<div class="modal" data-modal="product-order">
    <div class="inner">
        <span class="close"></span>
        <div class="h3-like">Отправим Коммерческое предложение Вам на WhatsApp!</div>
        <script data-b24-form="inline/43/sosb07" data-skip-moving="true">
        (function(w,d,u){
        var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
        var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.ru/b30317264/crm/form/loader_43.js');
        </script>
    </div>
</div>