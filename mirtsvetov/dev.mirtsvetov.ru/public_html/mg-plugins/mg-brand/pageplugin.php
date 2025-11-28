<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
  $filter - блок филтров
-->

<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->

  <!-- Тут начинается Верстка модального окна -->


  <div class="reveal-overlay" style="display:none;">
      <div class="reveal xssmall" id="plug-modal" style="display:block;">
        <?php
            echo MG::adminLayout(
                'lang-select.php', 
                array('class'=>'js-select-lang ')
            );
        ?>
        <button class="close-button closeModal" type="button"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
        <div class="reveal-header">
          <h4 class="pages-table-icon" id="modalTitle"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php echo $lang['HEADER_MODAL_ADD']; ?></h4>
        </div>
        <div class="reveal-body">

          <div class="widget-body slide-editor"><!-- Содержимое окна, управляющие элементы -->
            <div class="product-text-inputs" >
              <ul class="list-option">
                <li class="option-value"><label><span class="custom-text"><?php echo $lang['NAME']; ?>:<a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_NAME']; ?>"></a></span><input type="text" name="brand" id="brand"></label></li>
                <li class="option-value"><label><span class="custom-text"><?php echo $lang['SHORT_URL']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_SHORT_URL']; ?>"></a></span><input type="text" name="short_url" id="short-url"></label></li>
                
                <li class="option-value"><label>
                <span class="custom-text"><?php echo $lang['IMG_PATH']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_IMG_PATH']; ?>"></a></span>
                <a role="button" href="javascript:void(0);" class="browseImage button tip" title="<?php echo $lang['CHANGE_IMG']; ?>"><?php echo $lang['SELECT']; ?></a>
                <div class="brand-img brand-img-block">
                  <input type="hidden" name="url" id="url" class="imgSrc" disabled>
                  <a class="fa fa-trash tip remove-img del-image-to-brand img-button" href="javascript:void(0);" aria-hidden="true" title="<?php echo $lang['DELETE_IMAGE']; ?>"></a>
                  <a class="fa fa-cogs img-button tip set-seo" href="javascript:void(0);" aria-hidden="true" title="<?php echo $lang['SEO_SET']; ?>"></a>
                  <div class="popup-holder" id="seoImgBrand" style="display:none;">
                    <div class="modal-custom-popup" style="display:block;top:-18px;left:18px;">
                      <div class="row">
                        <div class="large-12 columns">
                          <label>Аттрибут title:</label>
                        </div>
                      </div>
                      <div class="row">
                        <div class="large-12 columns">
                          <input type="text" name="image_title" value="">
                        </div>
                      </div>
                      <div class="row">
                        <div class="large-12 columns">
                          <label>Аттрибут alt:</label>
                        </div>
                      </div>
                      <div class="row">
                        <div class="large-12 columns">
                          <input type="text" name="image_alt" value="">
                        </div>
                      </div>
                      <div class="row">
                        <div class="large-12 columns">
                          <a class="button fl-left seo-image-block-close" href="javascript:void(0);"><i class="fa fa-times" aria-hidden="true"></i> <?php echo $lang['CANCEL'] ?></a>
                          <a class="button success fl-right apply-seo-image" href="javascript:void(0);"><i class="fa fa-check" aria-hidden="true"></i> <?php echo $lang['APPLY'] ?></a>
                        </div>
                      </div>
                    </div>
                  </div>
                  <img src="<?php echo SITE.'/mg-admin/design/images/100x100.png' ?>" class="brand-image">
                </div>
                </label></li>
                <li id="data_id" class="option-value"><label for="data_id"><span class="custom-text"><?php echo $lang['SELECT_DATA']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_SELECT_DATA']; ?>"></a></span><select name="data_id" id="data-id">
                  <?php foreach($data_id_list as $data_id): ?>
                    <option value="<?php echo $data_id['id'] ?>"><?php echo $data_id['name'] ?></option>
                  <?php endforeach; ?>
                </select></label></li>
                <li>
                <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true" style="margin-bottom: 10px;">
                  <li class="accordion-item" data-accordion-item="">
                    <a class="accordion-title html-content-edit" href="javascript:void(0);"><?php echo $lang['DESC']; ?></a>
                    <div class="accordion-content" data-tab-content="" style="padding: 0px; display: none;">
                      <textarea name="html_desc" id="html-desc" cols="30" rows="10"></textarea>  
                    </div>
                  </li>
                  <li class="accordion-item" data-accordion-item="">
                    <a class="accordion-title auto-meta" href="javascript:void(0);"><?php echo $lang['SEO_DESC']; ?></a>
                    <div class="accordion-content" data-tab-content="" style="display: none;">  
                    <div class="row">
                      <div class="small-12 medium-3 columns">
                        <label class=" middle"><?php echo $lang['META_TITLE']; ?>:</label>
                      </div>
                      <div class="small-12 medium-9 columns">
                        <input type="text" name="seo_title" id="seo-title" title="<?php echo $lang['T_TIP_META_TITLE']; ?>">
                      </div>
                    </div>
                    <div class="row">
                      <div class="small-12 medium-3 columns">
                        <label class=" middle"><?php echo $lang['META_KEYWORDS']; ?>:</label>
                      </div>
                      <div class="small-12 medium-9 columns">
                        <input type="text" name="seo_keywords" id="seo_keywords" title="<?php echo $lang['T_TIP_META_KEYWORDS']; ?>">
                      </div>
                    </div>
                    <div class="row">
                      <div class="small-12 medium-3 columns">
                        <div class="">
                          <label class="middle"><?php echo $lang['META_DESC']; ?>:</label>
                          <div class="symbol-text" style="width: 90px;padding-right: 5px;"><?php echo $lang['LENGTH_META_DESC']; ?>: <strong class="symbol-count">0</strong></div>
                        </div>
                      </div>
                      <div class="small-12 medium-9 columns">
                        <textarea class="product-meta-field " name="seo_desc" title="<?php echo $lang['T_TIP_META_DESC']; ?>"></textarea>
                      </div>
                    </div>
                    <div class="row text-right">
                      <div class="large-12 columns">
                        <a class="button secondary tip generate_seo" href="javascript:void(0);" title="<?php echo $lang['T_TIP_GENERATE']; ?>"><i class="fa fa-refresh" aria-hidden="true"></i> <?php echo $lang['LABEL_GENERATE']; ?></a>
                      </div>
                    </div>
                    </div>
                  </li>
                  <li class="accordion-item" data-accordion-item="">
                    <a class="accordion-title html-content-edit" href="javascript:void(0);"><?php echo $lang['DESC_SEO']; ?></a>
                    <div class="accordion-content" data-tab-content="" style="padding: 0px; display: none;">
                      <textarea name="html_desc_seo" id="html-desc-seo" cols="30" rows="10"></textarea>  
                    </div>
                  </li>
                </ul>
                </li>
              </ul>
            </div>
          </div>
          <input type="hidden" name="id" value="">
        </div>
        <div class="reveal-footer clearfix">        
        <a class="button success fl-right save-button" href="javascript:void(0);"><i class="fa fa-floppy-o" aria-hidden="true"></i> <?php echo $lang['SAVE'] ?></a>
      </div>
    </div>
  </div>      
  <!-- Тут заканчивается Верстка модального окна -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-body">
     <?php if ($withoutProp > 0): ?>
      <div class="alert-block warning"><span><?= $withoutProp ?><?php echo $lang['WITHOUT_PROP']; ?><i class="fa fa-refresh"></i></span></div>
     <?php endif; ?>
     <?php if ($withoutBrand > 0): ?>
      <div class="alert-block warning"><span><?=$withoutBrand?><?php echo $lang['WITHOUT_BRAND']; ?><i class="fa fa-plus"></i></span></div>
     <?php endif; ?>
     <?php if ($activityProp == 0): ?>
      <div class="alert-block warning"><span><?php echo $lang['ACTIVITY_OFF']; ?> (ID = <?=$options['propertyId']?>)</span></div>
     <?php endif; ?>
     <div class="widget-panel">
       <a role="button" href="javascript:void(0);" class="add-entity button success"><span><?php echo $lang['ADD_MODAL']; ?></span></a>
       <a role="button" href="javascript:void(0);" class="show-filters tool-tip-top button plugin-padding" title="<?php echo $lang['T_TIP_SHOW_FILTER'];?>"><span><i class="fa fa-filter" aria-hidden="true"></i> <?php echo $lang['FILTER'];?></span></a>
       <a role="button" href="javascript:void(0);" class="show-property-order tool-tip-top button info" title="<?php echo $lang['T_TIP_OPTIONS'];?>"><span><i class="fa fa-cogs" aria-hidden="true"></i> <?php echo $lang['OPTIONS'];?></span></a>
       <div class="alert-block success" style="display:inline"><span>Шорткод для карусели брендов: <b>[mg-brand]</b></span></div> 
       
               
        <div class="filter fl-right">
          <span class="last-items"><?php echo $lang['SHOW_COUNT_ENTITY'];?></span>
          <select class="last-items-dropdown countPrintRowsEntity small">
            <?php
            foreach(array(10, 20, 30, 50, 100) as $value){
              $selected = '';
              if($value == $countPrintRows){
                $selected = 'selected="selected"';
              }
              echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
            ?>
          </select>
        </div>
        <div class="clear"></div>
      </div>
      
      <div class="filter-container widget-panel-content" <?php if($displayFilter){echo "style='display:block'";} else { echo "style='display:none'"; } ?>>
        <?php echo $filter ?>
        
        <div class="clear"></div>
      </div>
      
      <div class="property-order-container widget-panel-content" style="display: none;">    
        <h2><?php echo $lang['OPTIONS_TITLE']; ?>:</h2>
          <form  class="base-setting" name="base-setting" method="POST">       
              <ul class="list-option">
                <li><label><span class="custom-text"><?php echo $lang['PROPERTY_ID']; ?>:<a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_PROPERTY_ID']; ?>"></a></span>
                <!-- <input type="text" name="property_id" id="property-id" value="<?php echo $options['propertyId'] ?>"> -->
                <select name="property_id" id="property-id">
                  <?php foreach ($listprops as $key => $value): ?>
                    <option value="<?= $value['id'] ?>" <?= $value['id'] == $options['propertyId']? "selected" : ""?> ><?= $value['name'] ?></option>
                  <?php endforeach; ?>
                </select>
                </label></li>
                <li><h3><?php echo $lang['SLIDER_TITLE']; ?></h3></li>
                <li><label><span class="custom-text"><?php echo $lang['SLIDER_HEAD']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_SLIDER_HEAD']; ?>"></a></span><input type="text" name="sl_head" id="sl-head" value="<?=$options['slider_options']['head']?>"></li>
                <li><label><span class="custom-text"><?php echo $lang['SLIDER_ITEMS']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_SLIDER_ITEMS']; ?>"></a></span><input type="number" name="sl_items" id="sl-items" min="1" value="<?=$options['slider_options']['items']?>"></label></li>
                <li><label><span class="custom-text"><?php echo $lang['SLIDER_NAV']; ?>:<a class="tool-tip-top fa fa-question-circle fl-right" title=" <?php echo $lang['T_TIP_SLIDER_NAV']; ?>"></a></span><div class="checkbox inline"><input type="checkbox" name="sl_nav" id="sl-nav" <?=$options['slider_options']['nav']=="true"?"checked":""?>><label for="sl-nav"></label></div></label></li>
                <li><label><span class="custom-text"><?php echo $lang['SLIDER_MOUSEDRAG']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_SLIDER_MOUSEDRAG']; ?>"></a></span><div class="checkbox inline"><input type="checkbox" name="sl_mousedrag" id="sl-mousedrag" <?=$options['slider_options']['mouseDrag']=="true"?"checked":""?>><label for="sl-mousedrag"></label></div></label></li>
                <li><label><span class="custom-text"><?php echo $lang['SLIDER_AUTOPLAY']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_SLIDER_AUTOPLAY']; ?>"></a></span><div class="checkbox inline"><input type="checkbox" name="sl_autoplay" id="sl-autoplay" <?=$options['slider_options']['autoplay']=="true"?"checked":""?>><label for="sl-autoplay"></label></div></label></li>
                <li><label><span class="custom-text"><?php echo $lang['SLIDER_LOOP']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_SLIDER_LOOP']; ?>"></a></span><div class="checkbox inline"><input type="checkbox" name="sl_loop" id="sl-loop" <?=$options['slider_options']['loop']=="true"?"checked":""?>><label for="sl-loop"></label></div></label></li>
                <li><label><span class="custom-text"><?php echo $lang['SLIDER_MARGIN']; ?> (px): <a class='tool-tip-top fa fa-question-circle fl-right' title="<?php echo $lang['T_TIP_SLIDER_MARGIN']; ?>"></a></span><input type="number" name="sl_margin" id="sl-margin" value="<?=$options['slider_options']['margin']?>""></label></li>
                <li><label for="responsive"><span class="custom-text"><?php echo $lang['SLIDER_RESPONSIVE']; ?> <px:items>: <a class='tool-tip-top fa fa-question-circle fl-right' title="<?php echo $lang['T_TIP_SLIDER_RESPONSIVE']; ?>"></a></span>
                <input type="hidden" name="responsive" id="responsive" value="<?php echo $options['slider_options']['responsive'] ?>">
                <div class="adapt-wrapper">
                <a role="button" href="javascript:void(0);" class="button secondary openPopup"><i class="fa fa-cog"></i></a>
                <div class="custom-popup field-adaptive-popup" style="display: none;">
                  <div class="list-adaptive">
                  <?php foreach ($responsive as $key => $value): ?>
                  <div class="adaptive-item">
                    <input type="text" name="a_width" placeholder="Ширина" value="<?= $key ?>"> 
                    <input type="text" name="a_items" placeholder="Кол. карточек" value="<?= $value ?>"> 
                    <a role="button" href="javascript:void(0);" class="button secondary delete-item"><i class="fa fa-trash"></i></a>
                  </div>
                  <?php endforeach; ?>
                  </div>
                  <a class="button primary add-field" href="javascript:void(0);"><i class="fa fa-plus" aria-hidden="true"></i> <?php echo $lang['POPUP_ADD']; ?></a>
                  <a class="button success popup-accept" href="javascript:void(0);"><?php echo $lang['POPUP_ACCEPT']; ?></a>  
                </div>
                </div>
                </label></li>
                <li><h3><?php echo $lang['EXPORT_TITLE']; ?></h3></li>
                <li><label><span class="custom-text"><?php echo $lang['EXPORT_LABEL']; ?>: <a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_EXPORT']; ?>"></a></span><a role="button" href="javascript:void(0);" class="export-old button success"><span><?php echo $lang['EXPORT']; ?></span></a></label></li>
              </ul>
              <div class="clear"></div>
          </form>
          <div class="clear"></div>
        <a role="button" href="javascript:void(0);" class="base-setting-save custom-btn button success"><span><i class="fa fa-floppy-o" aria-hidden="true"></i><?php echo $lang['SAVE']; ?></span></a>
        <div class="clear"></div>
      </div>
    <div class="wrapper-entity-setting">

      
      <div class="clear"></div>
      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">                
        <div class="clear"></div>
        <div class="table-wrapper">
          <div class="brand-wrapper">

          <table class="widget-table main-table">          
            <thead>
            <tr>
              <th style="width:15px"><div class="checkbox inline"><input type="checkbox" name="all_rows" id="all-rows"><label for="all-rows"></label></div></th>
              <th class="id-width" style="width:15px"><a role="button" href="javascript:void(0);" class="order <?php echo ($sorterData[0]=="id") ? 'field-sorter '.$sorterData[3]:'field-sorter asc' ?>" data-sort="<?php echo ($sorterData[0]=="id") ? $sorterData[1]*(-1) : 1 ?>" data-field="id">№</a></th>
              <th style="width:15%">                
               <a role="button" href="javascript:void(0);" class="order <?php echo ($sorterData[0]=="add_datetime") ? 'field-sorter '.$sorterData[3]:'field-sorter asc' ?>" data-sort="<?php echo ($sorterData[0]=="add_datetime") ? $sorterData[1]*(-1) : 1 ?>" data-field="add_datetime"><?php echo $lang['ADD_DATE']; ?></a>
              </th>              
              <th>
               <a role="button" href="javascript:void(0);" class="order <?php echo ($sorterData[0]=="brand") ? 'field-sorter '.$sorterData[3]:'field-sorter asc' ?>" data-sort="<?php echo ($sorterData[0]=="name") ? $sorterData[1]*(-1) : 1 ?>" data-field="brand"><?php echo $lang['NAME']; ?></a>
              </th>
              <th>
                <?php echo $lang['LOGO']; ?>
              </th>
              <th style="width: 30%">
                <?php echo $lang['DESC']; ?>
              </th>
              <th class="actions" style="width:50px"><?php echo $lang['ACTIONS'];?>
              </th>
            </tr>
          </thead>
            <tbody class="entity-table-tbody"> 
              <?php 
              if (empty($entity)): ?>
                <tr class="no-results">
                  <td colspan="10" align="center"><?php echo $lang['ENTITY_NONE']; ?></td>
                </tr>
                  <?php else: ?>
                    <?php foreach ($entity as $row): ?>
                    <?php if ($row['no-brand'] == 'true'): ?>
                    <tr data-id="<?php echo $row['id']; ?>" class="no-brand">
                      <td><div class="checkbox inline"><input type="checkbox" class="select-row" name="select-row" id="row<?= $row['id']?>" data-id="<?=$row['id']?>"><label class="shiftSelect" for="row<?=$row['id']?>"></label></div></td>
                      <td><?php echo $row['id']; ?></td>   
                            
                      <td>                                  
                        <?php echo $row['name'] ?>                    
                      </td>

                      <td colspan="3">
                        <?php echo $lang['NO_BRAND']; ?>
                      </td>

                      <td class="actions">
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="add-no-brand" 
                              data-id="<?php echo $row['id'] ?>" >
                            <a class="tool-tip-bottom fa fa-plus" href="javascript:void(0);" 
                               title="<?php echo $lang['ADD']; ?>"></a>
                          </li>
                          <li class="delete-no-brand" 
                              data-id="<?php echo $row['id'] ?>">
                            <a class="tool-tip-bottom fa fa-trash" href="javascript:void(0);"  
                               title="<?php echo $lang['DELETE']; ?>"></a>
                          </li>
                        </ul>
                      </td>
                    </tr>
                    <?php else: ?>
                      <?php
                        if (stripos($row['url'], 'http') !== FALSE) {
                          $img_src = $row['url'];
                        } else {
                          $img_src = SITE.'/'.$row['url'];
                        }
                      ?>
                    <tr data-id="<?php echo $row['id']; ?>" <?php echo $row['refresh'] == "true"? 'class="refresh"' : "" ?>>
                      <td><div class="checkbox inline"><input type="checkbox" class="select-row" name="select-row" id="row<?= $row['id']?>" data-id="<?=$row['id']?>"><label class="shiftSelect" for="row<?=$row['id']?>"></label></div></td>
                      <td class="mover"><i class="fa fa-arrows"></i><?php echo $row['id']; ?></td>      
                      <td class="add_datetime"> 
                        <?php echo $row['add_datetime']; ?>   
                      </td>
                      <td class="brand">                                  
                        <?php echo $row['brand'] ?> 
                        <a class="fa fa-external-link tip" href="<?= SITE.'/'.$row['short_url'] ?>" aria-hidden="true" title="<?php echo $lang['T_TIP_LINK']; ?>" target="_blank"></a>                   
                      </td>

                      <td>
                        <?= !empty($row['url']) ? '<img class="logo" src="'.$img_src.'" alt="Нет изображения">' : 'Нет изображения'; ?>
                      </td>
                      
                      <td class="desc">                                  
                        <?php echo MG::textMore($row['desc'],250) ?>                    
                      </td>
                  
                      <td class="actions">
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="edit-row" 
                              data-id="<?php echo $row['id'] ?>" 
                              data-type="<?php echo $row['type']; ?>">
                            <a class="tool-tip-bottom fa fa-pencil" href="javascript:void(0);" 
                               title="<?php echo $lang['EDIT']; ?>"></a>
                          </li>
                          <li class="visible tool-tip-bottom  <?php echo ($row['invisible']) ? 'active' : '' ?>" 
                              data-id="<?php echo $row['id'] ?>" 
                              title="<?php echo $lang['INVISIBLE']; ?>">
                            <a class="fa fa-lightbulb-o <?php echo ($row['invisible']) ? '' : 'active' ?>" href="javascript:void(0);"></a>
                          </li>
                          <li class="delete-row" 
                              data-id="<?php echo $row['id'] ?>">
                            <a class="tool-tip-bottom fa fa-trash" href="javascript:void(0);"  
                               title="<?php echo $lang['DELETE']; ?>"></a>
                          </li>
                          <?php if ($row['refresh'] == "true"): ?>
                          <li class="refresh-row" 
                              data-id="<?php echo $row['id'] ?>">
                            <a class="tool-tip-bottom fa fa-refresh" href="javascript:void(0);"  
                               title="<?php echo $lang['REFRESH']; ?>"></a>
                          </li>
                          <?php endif; ?>
                        </ul>
                      </td>
                    </tr>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
          </table>
          </div>
          <div class="clear"></div>
          <div class="widget-footer">
            <div class="table-pagination clearfix">  
              <div class="label-select fl-left">
                <span><?php echo $lang['ACTION_LABEL']; ?>:</span>
                <select name="action" id="action" style="width: 250px;">
                  <option default><?php echo $lang['ACTION_DEFAULT']; ?></option>
                  <option value="delete"><?php echo $lang['ACTION_DELETE']; ?></option>
                  <option value="refresh"><?php echo $lang['ACTION_REFRESH']; ?></option>
                  <option value="add"><?php echo $lang['ACTION_ADD']; ?></option>
                </select>
                <span class="j-for-action"></span>
                <button class="button secondary j-run-action"><i class="fa fa-check"></i> <?php echo $lang['ACTION']; ?></button>
              </div>
              <?php echo $pagination ?>  
            </div>
          </div>  
        </div>
      </div>
      
    </div>
  </div>
<?php if ($debug == true) : ?>
  <div style="
    z-index: 9999999;
    padding: 10px;
    position: fixed;
    bottom: 0px;
    left: 0px;
    background: #cc202021;
    width: 350px;
  "><input type="text" name="debug_comment" id="debug-comment" style="
    height: 25px;
    display: inline;
    width: 200px;
  "><button class="button take-snapshot" style="
    height: 25px;
    display: inline;
    line-height: 25px;
    margin-top: 14px;
    margin-left: 10px;
  "">SNAPSHOT</button></div>
  <?php endif; ?>
  <script>
  $.timepicker.regional['ru'] = {
    prevText: '<Пред',
    nextText: 'След>',
    monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь',
      'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
    monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн',
      'Июл','Авг','Сен','Окт','Ноя','Дек'],
    dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
    dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
    dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
    timeText: 'Время:',
    hourText: 'Часы',
    minuteText: 'Минуты',
    secondText: 'Секунды',
    millisecText: 'Миллисекунды',
    currentText: 'Сейчас',
    closeText: 'Применить',
    dateFormat: 'yy-mm-dd',
    isRTL: false
  };

   $('.admin-center .section-<?php echo $pluginName ?>  .product-text-inputs input[name="date"]').datetimepicker($.timepicker.regional['ru']);
   $('.section-<?php echo $pluginName ?>  .filter-container .from-date').datetimepicker($.timepicker.regional['ru']);
   $('.section-<?php echo $pluginName ?>  .filter-container .to-date').datetimepicker($.timepicker.regional['ru']);
</script>