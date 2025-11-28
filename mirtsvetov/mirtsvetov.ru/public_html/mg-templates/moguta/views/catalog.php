<?php
/**
 *  Файл представления Catalog - выводит сгенерированную движком информацию на странице сайта с каталогом товаров.
 *  В этом  файле доступны следующие данные:
 *   <code>
 *    $data['items'] => Массив товаров,
 *    $data['titleCategory'] => Название открытой категории,
 *    $data['cat_desc'] => Описание открытой категории,
 *    $data['pager'] => html верстка  для навигации страниц,
 *    $data['searchData'] => Результат поисковой выдачи,
 *    $data['meta_title'] => Значение meta тега для страницы,
 *    $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *    $data['meta_desc'] => Значение meta_desc тега для страницы,
 *    $data['currency'] => Текущая валюта магазина,
 *    $data['actionButton'] => Тип кнопки в мини карточке товара,
 *    $data['cat_desc_seo'] => SEO описание каталога,
 *    $data['seo_alt'] => Алтернативное подпись изображение категории,
 *    $data['seo_title'] => Title изображения категории
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['items']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['items']; ?>
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
<?php mgAddMeta('components/catalog/item/item.css'); ?>
<?php mgAddMeta('components/product/variants/sizemap/sizemap.css'); ?>
<?php mgAddMeta('components/product/variants/variants.js'); ?>
<?php mgAddMeta('components/product/product.js'); ?>
<?php mgAddMeta('components/product/variants/variants.css'); ?>
<?php mgAddMeta('components/favorites/btns/btns.css') ?>
<?php mgAddMeta('components/favorites/btns/btns.js');
mgAddMeta('components/amount/amount.js');
mgAddMeta('components/cart/btn/add/add.js');
?>

<?php if (empty($data['searchData'])): ?>

    <!-- catalog - start -->
    <div class="l-row">
        <!-- c-title - start -->
        <div class="l-col min-0--12 c-title__categoty">
            <h1 class="c-title">
              <?php echo $data['titleCategory'] ?>
            </h1>
        </div>
        <!-- c-title - end -->

        <!-- c-description - start -->
      <?php if ($cd = str_replace("&nbsp;", "", $data['cat_desc'])): ?>
          <div class="l-col min-0--12 c-description_category">
              <div class="c-description c-description__top">

                <?php if ($data['cat_img']): ?>
                <img class="c-description__img"
                     src="<?php echo SITE . $data['cat_img'] ?>"
                     alt="<?php echo $data['seo_alt'] ?>"
                     title="<?php echo $data['seo_title'] ?>">
                <?php endif; ?>

                <div class="c-description__txt">
                    <p><?php echo $data['cat_desc'] ?></p>
                </div>
              </div>
          </div>
      <?php endif; ?>
        <!-- c-description - end -->

        <!-- c-sub - start -->
      <?php if (MG::getSetting('picturesCategory') == 'true'): ?>
        <?php
        // Список категорий каталога
        component(
          'catalog/categories',
          $data['cat_id']
        );
        ?>
      <?php endif; ?>
        <!-- c-sub - end -->


        <!-- mobile filter - start -->
        <div class="l-col min-0--12 min-768--6 min-1025--hide ">
          <?php
          // Кнопка раскрытия фильтра на мобильной версии
          component('filter/mobile');
          ?>
        </div>
        <!-- mobile filter - end -->

        <!-- c-switcher - start -->
      <?php
      // Переключение режима просмотра товаров поиткой/списком
      component(
        'catalog/switcher'
      );
      ?>
        <!-- c-switcher - end -->

        <!-- c-apply - start -->
      <?php
      // Применённые фильтры
      component(
        'filter/applied',
        $data['applyFilter']
      );
      ?>
        <!-- c-apply - end -->

        <!-- c-goods - start -->
        <div class="l-col min-0--12">
            <div class="c-goods products-wrapper js-products-container catalog"
                 role='main'>
                <div class="l-row">
                  <?php foreach ($data['items'] as $item) { ?>
                      <div class="l-col min-0--12 min-375--6 min-768--4 min-990--3 min-1025--4 c-goods__trigger">
                        <?php
                        // Миникарточка товара
                        component(
                          'catalog/item',
                          ['item' => $item]
                        ); ?>
                      </div>
                  <?php } ?>

                  <?php if (count($data['items']) == 0 && $_GET['filter'] == 1) { ?>
                      <div class="l-col">
                        <?php echo lang('searchFail') ?>
                      </div>
                  <?php } ?>

                  <?php
                    /*
                    * Компонент постраничной навигации, если она требуется
                    * */
                    if (!empty($data['pager'])): ?>
                        <?php if (class_exists('showMore')  && empty($_GET['search'])):?>
                          <div class="l-col min-0--12">
                            <div class="show-more-container">
                                [show-more total=<?php echo $data['totalCountItems']?>  curclass=c-goods__trigger parent_class=l-row]
                            </div>
                          </div>
                        <?php else: ?>
                        <div class="l-col min-0--12">
                          <div class="c-pagination">
                            <?php component('pagination', $data['pager']); ?>
                          </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                  <?php if (!empty($data['pager'])): ?>
                      <!-- pager - start -->
                      
                      <!-- pager - end -->
                  <?php endif; ?>

                </div>
            </div>
        </div>
        <!-- c-goods - end -->

        <!-- seo - start -->
      <?php if ($data['cat_desc_seo']) { ?>
          <div class="l-col min-0--12">
              <div class="c-description c-description__bottom">
                <?php echo $data['cat_desc_seo'] ?>
              </div>
          </div>
      <?php } ?>
        <!-- seo - end -->

    </div>
    <!-- catalog - end -->


<?php else: ?>


    <!-- search - start -->
    <div class="l-row">
        <style>
            .daily-wrapper {
                display: none;
            }
        </style>

        <!-- c-title - start -->
        <div class="l-col min-0--12">
            <h1 class="c-title">
              <?php echo lang('search1'); ?>
                <b class="c-title__search">
                    «<?php echo $data['searchData']['keyword'] ?>»
                </b>
              <?php echo lang('search2'); ?>
                <b class="c-title__search">
                  <?php
                  echo mgDeclensionNum(
                    $data['searchData']['count'],
                    array(
                      lang('search3-1'),
                      lang('search3-2'),
                      lang('search3-3')
                    )
                  );
                  ?>
                </b>
            </h1>
        </div>
        <!-- c-title - end -->

        <!-- c-goods - start -->
        <div class="l-col min-0--12">
            <div class="c-goods products-wrapper js-products-container catalog">
                <div class="l-row">
                  <?php foreach ($data['items'] as $item) { ?>
                      <div class="l-col min-0--6 min-768--4 min-990--3 min-1025--4 c-goods__trigger">
                        <?php
                        // Миникарточка товара
                        component(
                          'catalog/item',
                          ['item' => $item]
                        ); ?>
                      </div>
                  <?php } ?>
                </div>
            </div>
        </div>
        <!-- c-goods - end -->

      <?php if (!empty($data['pager'])): ?>
          <!-- pager - start -->
          <div class="l-col min-0--12">
              <div class="c-pagination">
                <?php component('pagination', $data['pager']); ?>
              </div>
          </div>
          <!-- pager - end -->
      <?php endif; ?>

    </div>
    <!-- search - end -->

<?php endif; ?>
