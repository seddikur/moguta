<?php
	/**
     * 
	 * Страница плагина "Сессии".
     * 
	 * Доступны переменные:
     * 
	 * $pluginName - название плагина.
     * $path - пусть до папки плагина.
	 * $lang - массив фраз для выбранной локали.
     * $currentPage - текущая страница.
     * $filter - блок фильтров.
     * $userFilter - пользовательский фильтр.
     * $itemsCount - количество всех найденых записей.
     * $countPrintRows - количество записей на странице.
	 * $entities - массив данных из таблицы БД.
	 * $pagination - блок навигации.
	 * 
	 */
?>
<div class="tabs-content js-page-session">
    <div class="widget-body">
        <div class="widget-panel-holder">
            <div class="widget-panel" style="border-top: 0;">
                <div class="buttons-holder clearfix">
                    <button class="button mg-panel-toggle show-filters">
                        <i class="fa fa-filter" aria-hidden="true"></i>
                        <span><?php echo $lang['FILTER']; ?></span>
                    </button>
                    <div class="label-select small fl-right">
                        <span class="select-label"><?php echo $lang['SHOW_COUNT_ENTITY'];?>:</span>
                        <select class="no-search countPrintRowsEntity small">
                            <?php
                                foreach (array(10, 20, 30, 50, 100) as $value) {
                                    $selected = '';
                                    if ($value == $countPrintRows) {
                                        $selected = 'selected="selected"';
                                    }
                                    echo '<option value="'. $value .'" '. $selected .'>'. $value .'</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="widget-panel-content filter-container" <?php echo $userFilter['display'] ? 'style="display: block"' : 'style="display: none"'; ?>>
                <?php echo $filter ?>
                <div class="alert-block success text-center" style="margin: 15px 0 0 0;">
                    <span><?php echo $lang['FILTER_ITEM_COUNT']; ?>: <strong><?php echo $itemsCount; ?></strong></span>
                </div>
            </div>
            
            <div class="table-wrapper table-wrapper--overflow_auto">
                <table class="main-table table-with-arrow">
                    <thead>
                        <tr>
                            <th class="number text-left">
                                №
                            </th>
                            <th class="login_email">
                                <?php echo $lang['TH_USER_LOGIN_EMAIL']; ?>
                            </th>
                            <th class="email">
                                <?php echo $lang['TH_USER_CONTANT_EMAIL']; ?>
                            </th>
                            <th>
                                <?php echo $lang['TH_USER_INITIALS']; ?>
                            </th>
                            <th class="actions text-right">
                                <?php echo $lang['TH_USER_SESSION']; ?>
                            </th>
                            <th class="actions text-right">
                                <?php echo $lang['TH_ACTIONS'];?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="entity-table-tbody">
                        <?php if (empty($entities)): ?>
                            <tr class="no-results">
                                <td colspan="6" align="center">
                                    <?php echo $lang['ENTITY_NONE']; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entities as $entity): ?>
                                <tr data-id="<?php echo $entity['id']; ?>">
                                    <td class="number text-left">
                                        <?php echo $entity['id']; ?>
                                    </td>
                                    <td class="login_email">
                                        <?php echo $entity['login_email']; ?> 
                                    </td>
                                    <td class="email">
                                        <?php echo $entity['email']; ?> 
                                    </td>
                                    <td>
                                        <?php 
                                            $sname = $entity['sname'] ? $entity['sname'] . ' ' : '';
                                            $pname = $entity['pname'] ? ' ' . $entity['pname'] : '';
                                            $initials = $sname . $entity['name'] . $pname;

                                            echo $initials;                                    
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $days = unserialize($entity['days']);
                                            if ($days) {
                                                $active = null;
                                                foreach ($days as $day) {
                                                    if ($day['active']) {
                                                        echo 'Есть сессия';
                                                        $active = true;
                                                        break;
                                                    }
                                                }
                                                if (!$active) {
                                                    echo 'Нет сессии';
                                                }
                                            } else {
                                                echo 'Нет сессии';
                                            }  
                                        ?>
                                    </td>
                                    <td class="actions text-right">
                                        <ul class="action-list">
                                            <?php if ($entity['session_id']): ?>
                                                <li>
                                                    <a class="js-edit-session" href="javascript:void(0);"
                                                       data-id="<?php echo $entity['session_id']; ?>" 
                                                       data-user-id="<?php echo $entity['id']; ?>">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <a class="js-add-session" href="javascript:void(0);"
                                                       data-user-id="<?php echo $entity['id']; ?>">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="widget-footer">
            <div class="table-pagination clearfix">
                <?php echo $pagination ?>
            </div>
        </div>
    </div>
</div>