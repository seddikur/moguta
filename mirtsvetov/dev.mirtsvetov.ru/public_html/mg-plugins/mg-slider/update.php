<?php

$updateSlidesColumnTypeSql = 'ALTER TABLE `'.PREFIX.'mg-slider` MODIFY `slides` longtext NOT NULL COMMENT '.DB::quote('Содержимое слайдов').';';
DB::query($updateSlidesColumnTypeSql);