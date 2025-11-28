<?php

$sqlQueryTo = array();

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."mg-status-order` LIKE 'sort'");
if(!$row = DB::fetchArray($dbQuery)) {
	$sqlQueryTo[] = "ALTER TABLE `".PREFIX."mg-status-order` ADD `sort` int(11) NOT NULL";
}

$sqlQueryTo[] = "UPDATE `".PREFIX."mg-status-order` SET `sort`=`id_status` WHERE 1=1";

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."mg-status-order` LIKE 'bgColor'");
if(!$row = DB::fetchArray($dbQuery)) {
	$sqlQueryTo[] = "ALTER TABLE `".PREFIX."mg-status-order` ADD `bgColor` varchar(7) NOT NULL";
}

$dbQuery = DB::query("SHOW COLUMNS FROM `".PREFIX."mg-status-order` LIKE 'textColor'");
if(!$row = DB::fetchArray($dbQuery)) {
	$sqlQueryTo[] = "ALTER TABLE `".PREFIX."mg-status-order` ADD `textColor` varchar(7) NOT NULL";
}

// выполнение запросов
if (is_array($sqlQueryTo)) {
  foreach ($sqlQueryTo as $sql) {
    DB::query($sql);
  }
}

?>