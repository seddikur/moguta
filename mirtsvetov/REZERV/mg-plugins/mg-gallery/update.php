<?php
 	DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX."all_galleries` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `gal_name` varchar(255) NOT NULL,
      `height` text NOT NULL,
      `in_line` int(5) NOT NULL,      
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

 	DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX."galleries_img` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `id_gal` int(11) NOT NULL,
      `image_url` text NOT NULL,
      `alt` text NOT NULL,      
      `title` text NOT NULL,      
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

  $res = DB::query("SELECT `id`,`image_url` FROM `".PREFIX."galleries_img` WHERE `image_url` LIKE '%http%'");
  while($row = DB::fetchArray($res)){
    DB::query("UPDATE `".PREFIX."galleries_img` SET `image_url` = ".DB::quote(URL::clearingUrl($row['image_url']))." WHERE `id` = ".DB::quoteInt($row['id']));
  }