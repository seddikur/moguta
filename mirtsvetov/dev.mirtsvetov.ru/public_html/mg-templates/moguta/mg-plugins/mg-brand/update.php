<?php

$pluginName = PM::getFolderPlugin(__FILE__);

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX.$pluginName."` LIKE 'sort'");
if(!$row = DB::fetchArray($dbQuery)) {
    $sql = "ALTER TABLE `".PREFIX.$pluginName."`
    ADD `sort` int(11)";
    $result = DB::query($sql);
}

// Отчистка дублей созданных ранее
$usedUserProp = DB::query('SELECT `category_id`,`property_id` FROM `'.PREFIX.'category_user_property` GROUP BY `category_id`,`property_id` HAVING COUNT(*) > 1');
if($usedUserProp){
    $sql = 'INSERT INTO `'.PREFIX.'category_user_property` (`category_id`, `property_id`) VALUES ';
    $sqlDelete = 'DELETE FROM `'.PREFIX.'category_user_property` WHERE (`category_id` = "" AND `property_id` = "" )';
    $sqlPart = '';
    while($propRes = DB::fetchAssoc($usedUserProp)){
        $sqlPart .= '('.$propRes['category_id'].', '.$propRes['property_id'].'),';
        $sqlDelete .= ' OR (`category_id` = '.$propRes['category_id'].' AND `property_id` = '.$propRes['property_id'].')';
    }
    if(!empty($sqlPart)){
        DB::query($sqlDelete);
        $sql .= $sqlPart;
        $sql = substr($sql, 0, -1);
        DB::query($sql);
    }
}

$sql = "UPDATE `".PREFIX.$pluginName."` SET `sort` = id";
$result = DB::query($sql);
if (!$result) return false;
