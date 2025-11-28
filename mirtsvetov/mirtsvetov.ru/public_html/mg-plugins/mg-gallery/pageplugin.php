<div class="section-news">
    <!-- Тут начинается Верстка модального окна -->

    <!--    <div class="b-modal hidden-form" id="add-news-wrapper">
      <div class="product-table-wrapper">
        <div class="widget-table-title">
          <h4 class="pages-table-icon" id="modalTitle">Создание галереи</h4>
          <div class="b-modal_close tool-tip-bottom" title="Создание галереи"></div>
        </div>
        <div class="widget-table-body"> -->

    <div class="reveal-overlay"
         style="display:none;"
         id="add-news-wrapper">
        <div class="reveal xssmall"
             id="add-plug-modal"
             style="display:block;">
            <button class="close-button closeModal"
                    type="button">
                <i class="fa fa-times-circle-o"
                   aria-hidden="true"></i>
            </button>
            <div class="reveal-header">
                <h4>
                    <i class="fa fa-plus-circle"
                       aria-hidden="true"></i>
                    Создание галереи
                </h4>
            </div>
            <div class="reveal-body">

                <section class="gal-sets-wrap">
                    <div class="base-settings shadow-block">
                        <h2 class="order-fieldset__h2">Настройки галереи</h2>
                        <div class="order-fieldset__inner">
                            <div class="add-img-form">
                                <div class="product-text-inputs">
                                    <label for="title">
                                        <div class="add-text">Название:</div>
                                        <input style="width:250px;"
                                               type="text"
                                               name="title"
                                               class="product-name-input tool-tip-right"
                                               id="gal-name"
                                               title="Служит для определения галлереи">
                                    </label>
                                    <label>
                                        <div class="add-text">Высота:</div>
                                        <input style="width:250px;"
                                               type="text"
                                               name="title"
                                               class="product-name-input tool-tip-right"
                                               id="gal-height"
                                               title="Высота выводимых изображений">
                                    </label>
                                    <label>
                                        <div class="add-text">Изображений в ряд:</div>
                                        <input style="width:250px;"
                                               type="text"
                                               name="url"
                                               class="product-name-input qty tool-tip-right"
                                               id="gal-in-line"
                                               title="Количество изображений в ряд">
                                    </label>
                                </div>
                            </div>
                            <div class="clear"></div>
                        </div>
                    </div>

                    <div class="img-settings shadow-block">
                        <h2 class="order-fieldset__h2">Настройки изображения</h2>
                        <div class="order-fieldset__inner">
                            <div class="add-img-form">
                                <div class="product-text-inputs">
                                    <p class="section__desc">
                                        Выберите изображение из галереи ниже, чтобы задать настройки
                                        для него
                                    </p>
                                    <label>
                                        <div class="add-text">Title</div>
                                        <input style="width:250px;"
                                               type="text"
                                               name="title"
                                               class="product-name-input tool-tip-right"
                                               id="img-title"
                                               title="Title изображения">
                                    </label>
                                    <label>
                                        <div class="add-text">Alt</div>
                                        <input style="width:250px;"
                                               type="text"
                                               name="url"
                                               class="product-name-input qty tool-tip-right"
                                               id="img-alt"
                                               title="Alt изображения">
                                    </label>
                                </div>
                            </div>
                            <button class="save-button tool-tip-bottom img-save button success fl-right"
                                    title="Применить изменения">
                                <span>
                                    <i class="fa fa-floppy-o"
                                       aria-hidden="true"></i>
                                    Применить
                                </span>
                            </button>
                            <div class="clear"></div>
                        </div>
                    </div>

                    <div class="add-product-form-wrapper shadow-block">
                        <h2 class="order-fieldset__h2">Изображения галереи</h2>
                        <div class="order-fieldset__inner">
                            <button class="save-button button success fl-right"
                                    id="browseImage"
                                    style="margin-top:0;"
                                    title="Выбрать ихображение">
                            <span>
                                <i class="fa fa-plus-circle"
                                   aria-hidden="true"></i>
                                Добавить изображение
                            </span>
                            </button>
                            <div class="clear"></div>
                            <div id="mg-gallery"></div>
                            <div class="clear"></div>
                        </div>
                    </div>
                </section>

            </div>
            <div class="reveal-footer clearfix">
                <a class="button success fl-right save-button gallery-save"
                   title="Сохранить настройки"
                   href="javascript:void(0);">
                    <i class="fa fa-floppy-o"
                       aria-hidden="true"></i>
                    Сохранить
                </a>
            </div>
        </div>
    </div>


    <!-- Тут заканчивается Верстка модального окна -->


    <!-- Тут начинается  Верстка таблицы товаров -->
    <div class="widget-table-body">
        <div class="widget-table-action">
            <div class="add-new-button tool-tip-bottom button success"
                 id="add-new-gallery">
                <span><i class="fa fa-plus-circle"
                         aria-hidden="true"></i> Создать галерею</span>
            </div>

            <div class="clear"></div>
        </div>

        <div class="main-settings-container">
            <table class="widget-table product-table main-table">
                <thead>
                <tr>
                    <th class="shortcode">шорткод</th>
                    <th class="gal-name">название галлереи</th>
                    <th>действие</th>
                </tr>
                </thead>
                <tbody class="gallery-tbody">
                <!-- вывод списка галерей -->
                <?php
                $res = DB::query('SELECT id, gal_name FROM `' . PREFIX . 'all_galleries` ORDER BY id DESC');

                while ($row = DB::fetchAssoc($res)) {
                  if ($row['gal_name'] == "Новая галерея") $row['gal_name'] = '<b>' . $row['gal_name'] . '</b>';
                  echo '<tr>';
                  echo '<td>[gallery id=' . $row['id'] . ']</td>';
                  echo '<td style="cursor:pointer;" class="gallery-edit" data-id="' . $row['id'] . '">' . $row['gal_name'] . '</td>';
                  echo '<td><ul class="edit">
                          <li class="edit-row gallery-edit" data-id="' . $row['id'] . '">
                            <a class="tool-tip-bottom fa fa-pencil" href="#" title="Редактировать галерею">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
                          </li>
                          <li style="list-style-type: none;" class="delete-order">
                            <a class="tool-tip-bottom delete-gallery fa fa-trash" data-id="' . $row['id'] . '" href="#" title="Удалить галерею">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
                          </li></ul></td>';
                  echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
        <div class="clear"></div>
    </div>
</div>