<!-- Страница настройки плагина - -->
<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div>           
      <div>
        <h2><?php echo $lang['HEADER_PLUGIN']?>:</h2>
        <button class="addNewStatus button success"><span><i class="fa fa-plus-circle"></i> <?php echo $lang['ADD_STATUS'] ?></span></button>
         <div style="overflow: auto;">
          <table class="widget-table main-table status-table">          
          <thead>
            <tr>
              <th style="width: 6%;">
              </th>              
              <th style="width: 6%;">
               id
              </th>      
              <th style="width: 28%;">
               <?php echo $lang['STATUS']?> 
              </th>
              <th style="width: 10%;">
               Цвет фона
              </th>  
               <th style="width: 10%;">
               Цвет текста
              </th>  
               <th style="width: 15%;padding-left: 1.6%;">
               Предпросмотр
              </th> 
              <th class="actions" style="width: 10%;">
              <?php echo $lang['ACTIONS'];?>
              </th> 
            </tr>
          </thead>                    
          <tbody class="status-tbody order-tbody">
            <?php 
              if (empty($status)): ?>
                <tr class="no-results">
                  <td colspan="3" align="center"><?php echo $lang['NO_RESULT']?></td>
                </tr>
            <?php else: ?>
            <?php foreach ($status as $row): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                      <td class="mover"><i class="fa fa-arrows"></i></td>
                      <td>  
                          <?php echo $row['id_status']?>
                      </td> 
                      <td data-status="<?php echo ($row['status']); ?>">                          
                          <input type="text" name="status" value="<?php echo $row['status']?>">
                      </td>
                      <td class="bgColor">
                        <div class="colorSelector">
                          <div style="background-color: <?php echo $row['bgColor'] ?>;"></div>
                        </div>
                      </td>
                      <td class="textColor">
                        <div class="colorSelector">
                          <div style="background-color: <?php echo $row['textColor'] ?>;"></div>
                        </div>
                      </td>
                      <td class="preview">
                        <span <?php 
                          switch ($row['id_status']) {
                            case '0':
                              echo 'class="badge dont-confirmed"';
                              break;
                            case '1':
                              echo 'class="badge get-paid"';
                              break;
                            case '2':
                              echo 'class="badge paid"';
                              break;
                            case '3':
                              echo 'class="badge in-delivery"';
                              break;
                            case '4':
                              echo 'class="badge dont-paid"';
                              break;
                            case '5':
                              echo 'class="badge performed"';
                              break;
                            case '6':
                              echo 'class="badge processed"';
                              break;
                            default:
                              echo 'class="badge"';
                              break;
                          }
                        ?> style="background-color:<?php echo $row['bgColor'] ?>; color:<?php echo $row['textColor'] ?>;" bgColor="<?php echo $row['bgColor'] ?>" textColor="<?php echo $row['textColor'] ?>"><?php echo $row['status']?></span>
                      </td>
                      <td class="actions">                         
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="save-row tool-tip-bottom" 
                              data-id="<?php echo $row['id'] ?>" 
                              title="<?php echo $lang['SAVE_MODAL']; ?>">
                              <a role="button" href="javascript:void(0);"><i class="fa fa-floppy-o"></i></a>
                          </li>
                          <li class="reset-row">
                            <a class=" fa fa-refresh tool-tip-bottom" href="javascript:void(0);"  
                               title="Сбросить цвета"></a>                         
                          </li>
                          <?php if (!in_array($row['locale'], $statusDfl)): ?>
                          <li class="delete-row" 
                              data-id="<?php echo $row['id'] ?>">
                            <a class=" fa fa-trash tool-tip-bottom" href="javascript:void(0);"  
                               title="<?php echo $lang['DELETE']; ?>"></a>                      
                          </li>
                          <?php endif; ?> 
                        </ul>
                          <span class="change error"><?php echo $lang['DONT_SAVED']?></span>
                          <span class="change success"><?php echo $lang['SAVED']?></span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
          </table>
         </div>
        <div class="clear"></div>
      </div>     
  </div>
  </div>
<script>
  admin.sortable('.status-tbody', 'mg-status-order');
  $('.colorSelector').each(function(){statusOrder.initColorPicker($(this))});
</script>
