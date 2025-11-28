<?php 
	/**
     * 
	 * Страница плагина "Юридические лица".
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
<div class="tabs-content js-page-legal-entities">
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
                            <th>
                                <?php echo $lang['TH_LEGAL_ENTITY_NAME']; ?>
                            </th>
                            <th>
                                <?php echo $lang['TH_LEGAL_ENTITY_ADDRESS']; ?>
                            </th>
                            <th>
                                <?php echo $lang['TH_LEGAL_ENTITY_INN']; ?>
                            </th>
                            <th>
                                <?php echo $lang['TH_LEGAL_ENTITY_KPP']; ?>
                            </th>
                            <th>
                                <?php echo $lang['TH_MANAGER']; ?>
                            </th>
                            <th class="actions text-right">
                                <?php echo $lang['TH_ACTIONS']; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="entity-table-tbody">
                        <?php if (empty($entities)): ?>
                            <tr class="no-results">
                                <td colspan="8" align="center">
                                    <?php echo $lang['ENTITY_NONE']; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entities as $entity): ?>
                                <tr data-id="<?php echo $entity['id']; ?>">
                                    <?php include($path . '/views/admin/page/components/entities/td.php'); ?>
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