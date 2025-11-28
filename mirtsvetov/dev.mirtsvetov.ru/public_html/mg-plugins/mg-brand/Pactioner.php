<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'mg-brand';        

  /**
   * Для дебага
   *
   * @return boolean
   */
  public function takeSnapshot() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    $request = $_POST;
    Brands::loger("========================[SNAPSHOT]================================");
    Brands::loger($request['comment']);
    Brands::loger("==================================================================");
    return true;
  }

  /**
   * Удаление сущности
   * @return boolean
   */
  public function deleteEntity() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Удаление записа');
    Brands::loger($_POST);
    $this->messageSucces = $this->lang['ENTITY_DEL'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT'];

    if (Brands::existStringProp($_POST['id'])) {
      return Brands::deleteProp($_POST['id']);
    }

    $request = $_POST;

    return Brands::deleteBrand($_POST['id']);
  }

  /**
   * Массовое удаление
   *
   * @return boolean
   */
  public function actionDelete() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__);
    Brands::loger($_POST);

    $request = $_POST;

    foreach ($request['list'] as $key => $value) {
      $_POST['id'] = $value; //HACK
      unset($_POST['list']); //HACk
      self::deleteEntity();
    }
    return true;
  }

  /**
   * Получает сущность
   * 
   * @return boolean
   */
  public function getEntity() {
    if (USER::access('plugin') < 1) {
      $this->messageError = $this->lang['ACCESS_VIEW'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Получение записи для редактирования');
    Brands::loger($_POST);
    $request = $_POST;

    $res = DB::query('
      SELECT * 
      FROM `'.PREFIX.$this->pluginName.'`
      WHERE `id` = '.DB::quoteInt($request['id']));

    if ($row = DB::fetchAssoc($res)) {
      $this->data = $row;
      return true;
    } else {
      return false;
    }

    return false;
  }

  /**
   * Получает перевод
   */
  public function getTranslate() {
    if (USER::access('plugin') < 1) {
      $this->messageError = $this->lang['ACCESS_VIEW'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Получить перевод');
    Brands::loger($_POST);
    $request = $_POST;
    if (!empty($request['data'])) {
      $this->data = Brands::getTranslate($request['id'], $request['locale'], $request['data']);
    } else {
      $this->data = Brands::getTranslate($request['id'], $request['locale'], '');
    }
    return true;
  }

  /** 
   * Сохраняет перевод
   */
  public function saveTranslate() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Сохранение перевода');
    Brands::loger($_POST);
    $this->messageSucces = $this->lang['TRANSLATE_SAVE'];
    $request = $_POST;
    Brands::saveTranslate($request['id'],$request['locale'],$request['data']);
    return true;
  }

  /**
   * Сохраняет и обновляет параметры записи
   * 
   * @return boolean
   */
  public function saveEntity() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Сохранение записи');
    Brands::loger($_POST);
    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['ENTITY_SAVE_NOT'];

    $request = $_POST['data'];
  
    if (!empty($request['id'])) {  // если передан ID, то обновляем
      $addDatetime = isset($request['add_datetime']) ? $request['add_datetime'] : '';
      
      if (!self::validationEdit($request)) return false;
      
      if (!Brands::editBrand($request)) return false;
    } else {      
      $request['add_datetime'] = date("Y-m-d H:i:s");
      unset($request['data_id']);

      if (!self::validation($request)) return false;

      if (!Brands::addBrand($request)) return false;
    }
    return true;
  }

  /**
   * Обновляет свойство характеристики
   * 
   * @return boolean
   */
  public function refreshEntity() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Создание свойства на основе бренда');
    Brands::loger($_POST);
    $request = $_POST;

    $array = Brands::getBrand($request['id']);
    //Проверка, есть ли такой бренд
    if (!$array) return false;
    //Проверка, есть ли такое свойство в характеристике
    if (Brands::existStringProp($array['data_id'])) return false;
    $brand = $array['brand'];

    $data_id_new = Brands::addStringProp($brand);
    
    return Brands::updateDataId($request['id'], $data_id_new);
  }

  /**
   * Массовое обновление характеристик у брендов
   *
   * @return boolean
   */
  public function actionRefresh()
  {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Групповое создание свойства');
    Brands::loger($_POST);

    $request = $_POST;

    foreach ($request['list'] as $key => $value) {
      $_POST['id'] = $value; //HACK
      unset($_POST['list']); //HACk
      self::refreshEntity();
    }
    return true;
  }

  private function validation($request) {
    if ($request['brand'] == "") {
      $this->messageError = $this->lang['VALID_NAME_ERROR'];
      return false;
    }

    if (Brands::existBrand($request['brand'])) {
      $this->messageError = $this->lang['VALID_NAME_EXIST_ERROR'];
      return false;
    }

    return true;
  }

  private function validationEdit($request) {
    if ($request['brand'] == "") {
      $this->messageError = $this->lang['VALID_NAME_ERROR'];
      return false;
    }

    if (Brands::existBrand($request['brand'])) {
      $tmp = Brands::getBrand($request['id']);
      if ($tmp['brand'] != $request['brand']) {
        $this->messageError = $this->lang['VALID_NAME_EXIST_ERROR'];
        return false;
      }
    }

    return true;
  }

  /**
   * Src: http://php.net/manual/ru/function.checkdate.php
   */
  private function validateDate($date, $format = 'Y-m-d H:i')
  {
    //Brands::loger('[Pactioner]'.__FUNCTION__);
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
  }

  /**
   * Устанавливает флаг активности  
   * @return type
   */
  public function visibleEntity() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Активация записи');
    Brands::loger($_POST);
    $this->messageSucces = $this->lang['ACT_V_ENTITY'];
    $this->messageError = $this->lang['ACT_UNV_ENTITY'];
    $request = $_POST;

    //обновление
    if (!empty($request['id'])) {
      $sql = "UPDATE `".PREFIX.$this->pluginName."`
              SET `invisible` = ".DB::quoteInt($request['invisible'])."
              WHERE id = ".DB::quoteInt($request['id']);
      Brands::loger('[SQL_QUERY]'.$sql);
      $result = DB::query($sql);
      if (!$result) return false;
    }

    if ($_POST['invisible']) {
      return true;
    }

    return false;
  }

  /**
   * Удаляет свойство характеристики
   *
   * @return boolena
   */
  public function deleteProp() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Удаление свойства');
    Brands::loger($_POST);
    $request = $_POST;
    $id = $request['id'];

    return Brands::deleteProp($id);
  }

  /**
   * Добавляет бренд
   *
   * @return boolena
   */
  public function addBrand() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Добавление бренда на основе свойства');
    Brands::loger($_POST);
    $request = $_POST;
    $id = $request['id'];

    $array = Brands::getBrand($id);
    //Проверка, есть ли такой бренд
    if ($array != false) return false;

    return Brands::addBrandOnProp($id);
  }

  /**
   * Массовое добавление на основе свойства
   *
   * @return boolena
   */
  public function actionAdd() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Групповое добавление на основе свойства');
    Brands::loger($_POST);

    $request = $_POST;

    foreach ($request['list'] as $key => $value) {
      $_POST['id'] = $value; //HACK
      unset($_POST['list']); //HACk
      self::addBrand();
    }
    return true;
  }

  /**
   * Экспорт из старого плагина
   *
   * @return boolena
   */
  public function export() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Экспорт из старого плагина');
    Brands::loger($_POST);
    return Brands::exportOldBrands();
  }

//#region [rgba(0,100,0,0.3)]
  //==========[no_found.php]================

  public function generateNew() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' НОТФАУНД - Генерация новой характеристики');
    Brands::loger($_POST);
    return Brands::generateNew();
  }

  public function selectPropertyId() {
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' НОТФАУНД - Выбор из существующих');
    Brands::loger($_POST);
    $request = $_POST;
    MG::setOption(array('option' => $this->pluginName.'-option', 'value' => addslashes(serialize($request['data']))));
    return true;
  }
//#endregion


  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    Brands::loger('[Pactioner]'.__FUNCTION__.' Сохранение настроек');
    Brands::loger($_POST);
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4,3','exit()');
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    $request = $_POST;

    if (!empty($request['data'])) {
      if ($request['data']['propertyId'] == "") {
        $this->messageError = $this->lang['VALID_OPTION1_ERROR'];
        return false;
      }

      MG::setOption(array('option' => $this->pluginName.'-option', 'value' => addslashes(serialize($request['data']))));
    }   
    return true;
  }
  
   /**
   * Устанавливает количество отображаемых записей в разделе 
   * @return boolean
   */
  public function setCountPrintRowsEnity(){  
    if (USER::access('plugin') < 2) {
      $this->messageError = $this->lang['ACCESS_EDIT'];
      return false;
    }
    Brands::loger('[Pactioner]'.__FUNCTION__.' Переключение кол. страниц');
    Brands::loger($_POST);
    $count = 20;
    if(is_numeric($_POST['count'])&& !empty($_POST['count'])){
      $count = $_POST['count'];
    }

    MG::setOption(array('option'=>$_POST['option'], 'value'=>$count));
    return true;
  }

  
}