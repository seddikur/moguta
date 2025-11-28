<?php
/**
 *  Файл представления Product - выводит сгенерированную движком информацию на странице личного кабинета.
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
 *   $data['liteFormData'] => Упрощенная форма для карточки товара
 *   $data['meta_title'] => Значение meta тега для страницы,
 *   $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *   $data['meta_desc'] => Значение meta_desc тега для страницы
 *   $data['landingUTO'] => Значение строки "Уникальное торговое предложение" из карточки товара
 *   $data['landingImage'] => Ссылка на изображение для лендинга
 *   $data['landingSwitch'] => Переключатель формы
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data); ?>
 *   </code>
 */

// Установка значений в метатеги title, keywords, description.
mgAddMeta('<link href="' . SCRIPT . 'standard/css/layout.related.css" rel="stylesheet" type="text/css" />');
mgAddMeta('<script src="' . SCRIPT . 'standard/js/layout.related.js"></script>');
mgSEO($data); ?>

<?php mgAddMeta('<link href="' . PATH_SITE_TEMPLATE . '/css/foundation.css" rel="stylesheet" type="text/css" />'); ?>
<link href="<?php echo PATH_SITE_TEMPLATE ?>/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
<?php mgAddMeta('<link href="' . PATH_SITE_TEMPLATE . '/css/owl.carousel.min.css" rel="stylesheet" type="text/css" />'); ?>
<?php mgAddMeta('<script  src="' . PATH_SITE_TEMPLATE . '/js/script.js"></script>'); ?>
<?php mgAddMeta('<script  src="' . PATH_SITE_TEMPLATE . '/js/owl.carousel.min.js"></script>'); ?>
<?php
    $payments = [];
    if(MG::isNewPayment()){
        $payments = Models_Payment::getPayments(false, false, true);
    } else {
        $payments = Models_Payment::getOldPayments();
    }
?>

<section class="cd-hero">
    <div class="cd-hero-content cover" style="
    <?php if (!empty($data['landingImage'])): ?>
            background: url(<?php echo $data['landingImage']; ?>);
    <?php else: ?>
            background: url('<?php echo PATH_SITE_TEMPLATE  ?>/img/bg.jpg');
    <?php endif ?>">
        <div class="row align-top align-center">

            <div class="column text-left large-12 small-12 " style="margin-top:10%;">

                <h2 class="black-color text-center main-title" style="font-weight: 700;">Не упусти свой шанс!</h1>
                    <h1 class="black-color text-center" style="font-weight:300;"><?php echo $data['title']; ?></h2>
                <div class="text-center"><p style="color:#9b9b9b;"><?php echo $data['landingUTO'] ?></p></div>
                <div class="row align-middle ">

                    <div class="column large-6 small-12  text-center large-text-right">
                        <!--
						<div class="old-price" <?php echo (!$data['old_price']) ? 'style="display:none"' : 'style="display:inline-block"' ?>>
							<span><?php echo MG::numberFormat($data['old_price'], '1 234,56') . " " . $data['currency']; ?></span>
						</div>
						<div class="price"><?php echo $data['price'] ?> <?php echo $data['currency']; ?></div>	
						-->
                        <a href="#first" class="btn-sec" style="margin-top:1rem;">Подробней</a>
                    </div>

                    <div class="column large-6 small-12  text-center large-text-left">
                        <!--
						<div class="old-price" <?php echo (!$data['old_price']) ? 'style="display:none"' : 'style="display:inline-block"' ?>>
							<span><?php echo MG::numberFormat($data['old_price'], '1 234,56') . " " . $data['currency']; ?></span>
						</div>
						<div class="price"><?php echo $data['price'] ?> <?php echo $data['currency']; ?></div>	
						-->
                        <a href="#zakaz" class="btn-zakaz" style="margin-top:1rem;">Заказать</a>
                    </div>

                </div>

            </div>

        </div>
    </div>
</section>
<!-- .cd-hero -->

<div class="nofull-slide colorblock">
    <div class="row align-center" id="first">
        <div class="column large-12 small-12  ">
            <h2 class="subtitle">Качество и сервис</h2>
            <div class="text-center"><p>Мы ценим каждого клиента и предлагаем вам качественный товар и высокий
                    сервис</p></div>
            <div class="row" style="margin-top:1.5rem;">

                <div class="column large-4 small-12 small-centered text-center  ">

                    <div class="row">

                        <div class="large-12 small-12  columns">
                            <i class="fa iconcolor fa-thumbs-o-up fa-2x"></i>
                        </div>

                        <div class="large-12 small-12 columns" style="margin-top:1rem;">
                            <h5 class="icon-title">Гарантия качества</h5>
                            <p class="icon-text">Обменяем товар если есть брак</p>
                        </div>
                    </div>

                </div>

                <div class="column large-4 small-12 small-centered text-center  ">

                    <div class="row">

                        <div class="large-12 small-12  columns">
                            <i class="fa iconcolor fa-credit-card fa-2x"></i>
                        </div>

                        <div class="large-12 small-12 columns" style="margin-top:1rem;">
                            <h5 class="icon-title">Безналичная оплата</h5>
                            <p class="icon-text">Можете оплатить товар сразу на сайте</p>
                        </div>
                    </div>

                </div>

                <div class="column large-4 small-12 small-centered text-center  ">

                    <div class="row">

                        <div class="large-12 small-12  columns">
                            <i class="fa iconcolor fa-rocket  fa-2x"></i>
                        </div>

                        <div class="large-12 small-12 columns" style="margin-top:1rem;">
                            <h5 class="icon-title">Быстрая доставка</h5>
                            <p class="icon-text">Доставка товара на следующий день</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>


</div>


<div class="fird-slide">
    <div class="row align-top">
        <div class="column large-text-left text-center large-7 small-12">
            <h3 style="font-size: 2rem;font-weight:600;margin-bottom: 1.5rem;">Пару слов
                о «<?php echo $data['title']; ?>»</h3>
            <div><?php echo $data['description']; ?></div>
        </div>
        <div class="column large-5 small-12 fird-slide__sticky">
            <div class="owl-carousel owl-theme">
                <?php
                foreach ($data["images_product"] as $key => $image) {
                    ?>
                    <div class="item columns">
                        <img src="<?php echo mgImageProductPath($image, $data["id"]) ?>">
                    </div>
                    <?php
                }
                ?>
            </div>

        </div>

    </div>

</div>


<div class="second-slide colorblock">

    <div class="row align-middle align-center" id="zakaz">
        <div class="columns large-12 small-12">
            <h1 style="font-weight: 300;"><?php echo $data['title']; ?></h1>
        </div>
        <?php if ($data["images_product"][1]) {
            $lastImgSrc = mgImageProductPath($data["images_product"][1], $data["id"]);
        } else {
            $lastImgSrc = mgImageProductPath($data["images_product"][0], $data["id"]);
        } ?>

        <div class="column large-7 small-12 ">
            <div class="owl-carousel owl-theme">
                <img alt="<?php echo $data['title']; ?>"
                     style="border-radius: 2px"
                     src="<?php echo $lastImgSrc ?>">
            </div>
        </div>

        <div class="column text-left large-5 small-12 ">
            <div class="in-stock">
                <i class="fa fa-check" aria-hidden="true"></i>
                <span class="in-stock__text-content">В наличии</span>
            </div>
            <div class="row align-middle">
                <div class="column large-12 small-12 ">
                    <div class="large-12 small-12">
                        <div class="old-price" <?php echo (!$data['old_price']) ? 'style="display:none"' : 'style="display:inline-block"' ?>>
                            <span><?php echo MG::numberFormat($data['old_price'], '1 234,56') . " " . $data['currency']; ?></span>
                        </div>
                        <strong class="price"><?php echo $data['price'] ?><?php echo $data['currency']; ?></strong>
                    </div>
                    <?php
                    echo $data['propertyForm'];

                    if ($data['landingSwitch'] < 0 && !empty($payments)) {
                        ?>
                        <div class="side-payment__wrap">
                        <span class="side-payment__title">Варианты оплаты:</span>
                        <ul class="side-payment">
                            <?php foreach($payments as $key => $payment): ?>
                                <li class="icon-style <?php echo "pay-" . $payment['id']; ?>"><?php echo $payment['name']; ?></li>
                            <?php endforeach; ?>
                        </ul></div>
                    <?php

                        $style = '';

                        if ($data['count'] != 0) {
                            $style = 'style="display:none;"';
                        }

                        echo '<a class="depletedLanding" ' . $style . ' rel="nofollow" href="' . SITE . '/feedback?message=' . $data['noneMessage'] . '&code= '. $data['code'] . '">Сообщить когда будет</a>';
                    }

                    if ($data['landingSwitch'] > 0) {
                        if (class_exists('formDesigner')) {
                            echo '[contact-form id=' . $data['landingSwitch'] . ']';
                        } else {
                            echo 'Установите плагин "Конструктор форм" или включите кнопку "купить" вместо формы!';
                        }
                    }
                    ?>

                </div>

            </div>

        </div>

    </div>

</div>

<script>
    $(document).ready(function () {
        $('.owl-carousel').owlCarousel({
            items: 1,
            margin: 10,
            autoHeight: true
        });

    })
</script>