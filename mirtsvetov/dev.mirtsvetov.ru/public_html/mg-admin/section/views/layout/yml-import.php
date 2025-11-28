

 <!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам
-->
<script>
        includeJS("<?php echo SITE.'/mg-core/script/admin/yml-import.js'; ?>");  
</script> 

 <style>
			.base-settings .list-option li {
				padding: 5px 0;
		}
		
		.base-setting-save {
				float: none!important;
		}
		
		.base-settings .list-option li label span {
				width: 280px;
				vertical-align: top;
				float: left;
		}
		
		.base-settings .list-option li input[type="text"] {
				margin: 0;
				width: 270px;
		}
		
		.base-settings .list-option li input[type="text"].search-txt {
				width: 100%;
		}
		
		.base-settings .list-option li select {
				margin: 0;
				width: 293px;
		}
		
		.base-settings .list-option li.section {
				font-weight: bold;
				font-size: 14px;
				margin: 5px 0;
		}
		
		.admin-wrapper .my-view-action {
				background: url("/mg-admin/design/images/loader.gif") no-repeat scroll 10px center #f8b706;
				border: 1px solid #e49023;
				border-radius: 3px;
				bottom: 0;
				color: #fff;
				display: inline-block;
				left: 0;
				padding: 30px 30px 30px 50px;
				position: fixed;
				text-shadow: 1px 1px 1px #444;
				z-index: 100;
		}
		
		.yml-upload-form__btn {
				margin: 0 !important;
		}
        .yml-upload-form__container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
		.yml-upload-input {
            display: flex;
            align-items: center;
            margin-right: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .yml-upload-form {
            margin-right: 15px;
            margin-bottom: 15px;
        }
		.file-name {
				margin-left: 5px;
		}
		
		.file-name_hidden {
				display: none;
		}
		
		.file-name .fa-times {
				color: red;
		}
		
		.mg-yml-tooltip {
				width: auto !important;
				float: none !important;
				vertical-align: middle !important;
		}
		
		.section-mg-yml-import-core .base-settings label {
				display: inline;
		}
		.section-mg-yml-import-core ul{
			list-style-type: none;
		}
	
			</style>
<!-- $pluginName - задает название секции для разграничения JS скрипта -->
<div class="section-mg-yml-import-core">

    <!-- Тут начинается Верстка модального окна -->
    <div class="reveal-overlay"
         style="display:none;">
        <div class="reveal xssmall  ad-editor"
             id="yml-modal"
             style="display:block;"><!-- блок для контента модального окна -->
            <button class="close-button closeModal"
                    type="button">
                <i class="fa fa-times-circle-o"
                   aria-hidden="true"></i>
            </button>
            <div class="reveal-header"><!-- Заголовок модального окна -->
                <h4 class="pages-table-icon"
                    id="modalTitle"><?php echo $lang['MODAL_TITLE']; ?>
                    Соответствие полей импорта
                </h4>
            </div>
            <div class="reveal-body">
                <div class="add-product-form-wrapper">
                    <ul></ul>
                </div>
            </div>
            <div class="reveal-footer clearfix">
                <div class="clear"></div>
                <button class="save-button tool-tip-bottom button success"
                        title="<?php echo $lang['T_TIP_SAVE']; ?>">
                    <i class="fa fa-floppy-o">
                        <span><?php echo $lang['SAVE']; ?></span>
                    </i>
                </button>
                <div class="clear"></div>
            </div>
        </div>
    </div>
    <!-- Тут заканчивается Верстка модального окна -->

    <!-- Тут начинается верстка видимой части станицы настроек плагина-->
    <div class="widget-table-body">
        <div class="wrapper-entity-setting">
        <span class="section__desc" style="background:#f7f7f7;">
            Товары можно загрузить из прайса поставщика в формате YML или XML. Если вы используете XML файл, то он должен соответствовать стандарту Яндекс.Маркет.
            <br>Вы можете частично обновлять данные имеющихся товаров, например, только цены.
        </span>
            <!-- Тут начинается  Верстка базовых настроек  плагина (опций из таблицы  setting)-->
            <div class="widget-table-action base-settings">
                <ul class="list-option" style="padding:0px;"><!-- список опций из таблицы setting-->
        
                    <li class="yml-upload-form__container">
                        <form method="post"
                              noengine="true"
                              enctype="multipart/form-data"
                              class="yml-upload-form">
                            <a href="javascript:void(0);" class="js-browse-image button yml-upload-form__btn">
                                <i class="fa fa-file-code-o" aria-hidden="true"></i> Загрузите файл с товарами
                            </a>
                            <span class="js-uploaded-file file-name file-name_hidden">
                                <span class="js-file-name"></span>
                                <button class="js-clear-url-input file-name__btn_delete"
                                        title="Удалить файл">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </button>
                            </span>
                            <span class="upload_file_success"
                                  style="display:none;"><?php echo $lang['UPLOAD_FILE_SUCCESS'] ?></span>
                            <br>
                        </form>
                        <div class="yml-upload-input">
                            <span style="margin-right:20px;" class="custom-text fl-left"><?php echo $lang['URL_FILE'] ?>:</span>
                            <input  type="text"
                            class="js-insert-yml-file-url"
                            placeholder="http://site.ru/products.xml"
                            name="url_data_file"
                            tooltip="<?php echo $lang['T_TIP_URL_FILE'] ?>"/>
                        </div>
                    </li>
            
          
                    <li>
                        <label>
                            <span class="custom-text fl-left"><?php echo $lang['IMPORT_CATEGORY'] ?>:</span>
                            <input type="checkbox"
                                   name="import_category"
                                   checked="checked"
                                   tooltip="<?php echo $lang['T_TIP_IMPORT_CATEGORY'] ?>">
                        </label>
                    </li>
                    <li>
                        <label>
                            <span class="custom-text fl-left"><?php echo $lang['SKIP_SINGLE_ROOT_CAT']; ?>:</span>
                            <input type="checkbox"
                                   name="skipRootCat"
                                   value=""/>
                        </label>
                    </li>
                    <br />
                    <li>
                        <label>
                            <span class="custom-text fl-left"><?php echo $lang['IMPORT_IN_CATEGORY'] ?>:</span>
                            <?php
                                $categoryLib = MG::get('category');
                                $arrayCategories = $categoryLib->getHierarchyCategory(0);
                                $data['category'] = $categoryLib->getTitleCategory($arrayCategories, 0, true);
                            ?>
                            <select 
                                    id="import_in_category"
                                    class="no-search js-comboBox"
                                    style="margin-top: -5px;"
                                    name="import_in_category"
                                    value="<?php echo $options['import_in_category']; ?>"
                                    title="<?php echo $lang['T_TIP_CAT_PROD']; ?>"
                                    tooltip="<?php echo $lang['T_TIP_IMPORT_IN_CATEGORY'] ?>">
                                <option value="0"><?php echo $lang['ROOT_CATEGORY']; ?></option>
                                <?php foreach ($data['category'] as $id => $catName): ?>
                                    <option value="<?php echo $id; ?>"><?php echo '-' . $catName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </li>
           
                    <li>
                        <label>
                            <span class="custom-text fl-left"><?php echo $lang['CLEAR_CATALOG_MODE']; ?>:</span>
                            <input type="checkbox"
                                   name="clearCatalog"
                                   value=""
                                   tooltip="<?php echo $lang['T_TIP_CLEAR_CATALOG_IMPORT'] ?>"/>
                        </label>
                    </li>
                    <li>
                        <label>
                            <span class="custom-text fl-left"><?php echo $lang['UPDATE_PRICE_MODE']; ?>:</span>
                            <input type="checkbox"
                                   name="onlyPrice"
                                   value=""
                                   tooltip="<?php echo $lang['T_TIP_ONLY_UPDATE_PRICE'] ?>"/>
                        </label>
                    </li>
                    <li>
                        <label>
                            <div class="custom-text fl-left" style="margin-right: 3px;"><?php echo $lang['DONT_UPDATE_EXISTED_PRODUCTS_IMAGES']; ?>:</div>
                            <input type="checkbox"
                                   name="onlyNewProductsImages"
                                   value=""
                                   tooltip="<?php echo $lang['T_TIP_DONT_UPDATE_EXISTED_PRODUCTS_IMAGES'] ?>"/>
                        </label>
                    </li>
             
                    <li>
                        <label>
                            <span class="custom-text fl-left">
                                <?php echo $lang['MULTIPLE_PRICE'] ?>:
                                <span tooltip="Коэфициент, на который будет увеличена цена каждого товара, импортированного из YML-файла. Например, чтобы увеличить цену в полтора раза – укажите 1.5, а для того, чтобы снизить в два раза – 0.5"
                                      class="mg-yml-tooltip"
                                      flow="rightUp">
                                    <i class="fa fa-question-circle tip"
                                       aria-hidden="true"></i>
                                </span>
                            </span>
                            <input type="text"
                                   name="priceModifier"
                                   value="1"
                                   styel="width:50px!important;"
                                   style="width: 50px; margin-top: -5px;"
                                   tooltip="<?php echo $lang['MULTIPLE_PRICE_INFO'] ?>"/>
                        </label>
                    </li>

                    <li>
                        <label>
                            <span class="custom-text fl-left">
                                <?php echo $lang['FORCE_NAME'] ?>:
                                <span tooltip="Теги name и company в файле yml - обязательные. По ним система понимает какие товары нужно создать, а какие обновить. Если вы хотите импортировать файл, в котором этих тегов нет, вы можете указать их в этой настройке самостоятельно. В таком случае, если вам повторно потребуется загрузить этот же файл (даже если данные по товарам изменились), то необходимо будет указать такие же значение, как и при первом импорте, чтобы товары не дублировались, а обновлялись."
                                      class="mg-yml-tooltip"
                                      flow="rightUp">
                                    <i class="fa fa-question-circle tip"
                                       aria-hidden="true"></i>
                                </span>
                            </span>
                            <input type="text"
                                   name="forceName"
                                   value=""
                                   style="width: 250px; margin-top: -5px;"
                                   placeholder="instrument"
                                   tooltip="Теги name и company в файле yml - обязательные. По ним система понимает какие товары нужно создать, а какие обновить. Если вы хотите импортировать файл, в котором этих тегов нет, вы можете указать их в этой настройке самостоятельно. В таком случае, если вам повторно потребуется загрузить этот же файл (даже если данные по товарам изменились), то необходимо будет указать такие же значение, как и при первом импорте, чтобы товары не дублировались, а обновлялись."/>
                        </label>
                    </li>

                    <li>
                        <label>
                            <span class="custom-text fl-left">
                                <?php echo $lang['FORCE_COMPANY'] ?>:
                                <span tooltip="Теги name и company в файле yml - обязательные. По ним система понимает какие товары нужно создать, а какие обновить. Если вы хотите импортировать файл, в котором этих тегов нет, вы можете указать их в этой настройке самостоятельно. В таком случае, если вам повторно потребуется загрузить этот же файл (даже если данные по товарам изменились), то необходимо будет указать такие же значение, как и при первом импорте, чтобы товары не дублировались, а обновлялись."
                                    class="mg-yml-tooltip"
                                    flow="rightUp">
                                    <i class="fa fa-question-circle tip"
                                    aria-hidden="true"></i>
                                </span>
                            </span>
                            <input type="text"
                                name="forceCompany"
                                value=""
                                placeholder="ooo-instrument-vsem"
                                style="width: 250px; margin-top: -5px;"
                                tooltip="Теги name и company в файле yml - обязательные. По ним система понимает какие товары нужно создать, а какие обновить. Если вы хотите импортировать файл, в котором этих тегов нет, вы можете указать их в этой настройке самостоятельно. В таком случае, если вам повторно потребуется загрузить этот же файл (даже если данные по товарам изменились), то необходимо будет указать такие же значение, как и при первом импорте, чтобы товары не дублировались, а обновлялись."/>
                        </label>
                    </li>
                                        <!-- <li class="section"><?php echo $lang['SECTION_FILE_STRUCTURE']; ?></li>
          <li><label>
            <span class="custom-text fl-left"><?php echo $lang['NEW_FILE_STRUCTURE']; ?>:</span>
          <ooltipel></li> -->
                    <li>
                        <button class="tool-tip-bottom base-setting-save save-button custom-btn button success js-start-import-yml"
                                data-id=""
                                title="<?php echo $lang['START_IMPORT'] ?>">
                            <span><i class="fa fa-play" aria-hidden="true"></i> <?php echo $lang['START_IMPORT'] ?></span>
                            <!-- кнопка применения настроек -->
                        </button>
                        <button class="tool-tip-bottom base-import-images save-button custom-btn button success "
                                data-id=""
                                title="Импорт изображений"
                                style="background-color: #eea24b !important;"
                                disabled>
                            <span><i class="fa fa-picture-o" aria-hidden="true"></i> Начать импорт изображений</span>
                            <!-- кнопка применения настроек -->
                        </button>
                    </li>
                </ul>
                <div class="block-console"
                     style="margin-top:20px; margin-left: -10px;">
                    <textarea style="width:600px; height:200px;margin-left: 10px;border-radius: 2px"
                              disabled="disabled"> </textarea>
                </div>
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>
