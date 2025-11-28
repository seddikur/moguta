<?php
	/**
     * 
	 * Страница плагина "Пользователи".
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
<div class="tabs-content js-page-users">
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
                                <?php echo $lang['TH_ACTIONS'];?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="entity-table-tbody">
                        <?php if (empty($entities)): ?>
                            <tr class="no-results">
                                <td colspan="5" align="center">
                                    <?php echo $lang['ENTITY_NONE']; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entities as $user): ?>
                                <tr data-id="<?php echo $user['id']; ?>">
                                    <td class="number text-left">
                                        <?php echo $user['id']; ?>
                                    </td>
                                    <td class="login_email">
                                        <?php echo $user['login_email']; ?> 
                                    </td>
                                    <td class="email">
                                        <?php echo $user['email']; ?> 
                                    </td>
                                    <td>
                                        <?php 
                                            $sname = $user['sname'] ? $user['sname'] . ' ' : '';
                                            $pname = $user['pname'] ? ' ' . $user['pname'] : '';
                                            $initials = $sname . $user['name'] . $pname;

                                            echo $initials;                                    
                                        ?>
                                    </td>
                                    <td class="actions text-right">
                                        <ul class="action-list">
                                            <li>
                                                <a class="js-view-legal tip tooltip--small" flow="left" 
                                                   data-user-id="<?php echo $user['id']; ?>"
                                                   href="javascript:void(0);">
                                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                                </a>
                                            </li>
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