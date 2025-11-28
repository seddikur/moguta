<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Gaydis Mikhail
 */
class Pactioner extends Actioner {

  private $pluginName = 'mg-gallery';

  // добавление новой галереи
  public function addGallery() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    $sql = 'INSERT INTO `'.PREFIX.'all_galleries` 
      (gal_name, height, in_line) 
      VALUES ("Новая галерея" ,"150" ,"3")';

    DB::query($sql);

    return true;
  }

  // получение списка галерей
  public function loadGalleries() {
    if (USER::access('plugin') < 1) {
      $this->messageError = $this->lang['ACCESS_VIEW'];
      return false;
    }
    $res = DB::query('SELECT id, gal_name FROM `'.PREFIX.'all_galleries` ORDER BY id DESC');

    while ($row = DB::fetchAssoc($res)) {
      $galleries[] = $row;
    }

    $this->data = $galleries;

    return true;
  }

  // получение информации о галереи
  public function loadGalleryInfo() {
    if (USER::access('plugin') < 1) {
      $this->messageError = $this->lang['ACCESS_VIEW'];
      return false;
    }
    $res = DB::query('SELECT gal_name, height, in_line FROM `'.PREFIX.'all_galleries` WHERE id = '.DB::quote($_POST['id']));

    while ($row = DB::fetchAssoc($res)) {
      $galleryInfo = $row;
    }

    $this->data = $galleryInfo;

    return true;
  }

  // сохранение настроек галереи
  public function saveGallery() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    DB::query('UPDATE `'.PREFIX.'all_galleries` SET '.DB::buildPartQuery($_POST['data']).' WHERE id = '.DB::quote($_POST['id']));

    return true;
  }

  // сохранение настроек картинки
  public function saveImg() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    DB::query('UPDATE `'.PREFIX.'galleries_img` SET '.DB::buildPartQuery($_POST['data']).' WHERE id = '.DB::quote($_POST['id']));

    return true;
  }

  // добавление новой картинки в галерею
  public function addNewImg() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    $_POST['url'] = str_replace(SITE,'',$_POST['url']);
    $sql = 'INSERT INTO `'.PREFIX.'galleries_img` 
      (id_gal, image_url) 
      VALUES ('.DB::quote($_POST['id']).' ,'.DB::quote($_POST['url']).')';

    DB::query($sql);

    return true;
  }

  // получения списка картинок для галереи
  public function getImgList() {
    if (USER::access('plugin') < 1) {
      $this->messageError = $this->lang['ACCESS_VIEW'];
      return false;
    }
    $imgList = array();
    $res = DB::query('SELECT id, id_gal, image_url, alt, title 
      FROM `'.PREFIX.'galleries_img` WHERE id_gal = '.DB::quote($_POST['id']));

    while ($row = DB::fetchAssoc($res)) {
      if(!empty($row['image_url'])) $row['image_url'] = SITE.$row['image_url'];
      $imgList[] = $row;
    }

    $this->data = $imgList;

    return true;
  }

  // Удаление картинки из списка
  public function removeImg() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    DB::query('DELETE FROM `'.PREFIX.'galleries_img` WHERE id = '.DB::quote($_POST['id']));

    return true;
  }

  // Удаление картинки из списка
  public function deleteGallery() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    DB::query('DELETE FROM `'.PREFIX.'all_galleries` WHERE id = '.DB::quote($_POST['id']));

    return true;
  }

}