<?php

$tableName = '`'.PREFIX.'trigger-guarantee-elements`';

$elementsSql = 'SELECT `id`, `icon` '.
    'FROM '.$tableName.' '.
    'WHERE `icon` LIKE "%'.DB::quote('src="'.SITE, true).'%"';
$elementsResult = DB::query($elementsSql);
while ($elementRow = DB::fetchAssoc($elementsResult)) {
    $id = $elementRow['id'];
    $icon = $elementRow['icon'];
    $newIcon = str_replace('src="'.SITE, 'src="#SITE#', $icon);
    $setNewIconSql = 'UPDATE '.$tableName.' '.
        'SET `icon` = '.DB::quote($newIcon).' '.
        'WHERE `id` = '.DB::quoteInt($id);
    DB::query($setNewIconSql);
}