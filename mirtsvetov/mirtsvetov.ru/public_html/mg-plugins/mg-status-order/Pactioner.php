<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'mg-status-order';        
  private $systemStatus = array(0,1,2,3,4,5,6);
  /**
   * Сохранение изменений в таблице
   * @return boolean
   */
  public function saveEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['ENTITY_NOT_SAVE'];
    unset($_POST['pluginHandler']);
    unset($_POST['mguniqueurl']);
    if (empty($_POST['status'])) {
      $this->messageError = $this->lang['ENTITY_EMPTY'];
      return false;
    }
    if (isset($_POST['id'])) {
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET `status`='.DB::quote($_POST['status']).',
        `bgColor`='.DB::quote(isset($_POST['bgColor'])?$_POST['bgColor']:'').',
        `textColor`='.DB::quote(isset($_POST['textColor'])?$_POST['textColor']:'').'
        WHERE id ='.DB::quoteInt($_POST['id']))) {
        $this->data = $_POST;
        return true;
      } 
    } else {
      unset($_POST['id']);
      if (DB::query('INSERT INTO `'.PREFIX.$this->pluginName.'` SET `status` = '.DB::quote($_POST['status']).', `bgColor` = '.DB::quote(isset($_POST['bgColor'])?$_POST['bgColor']:'').', `textColor` = '.DB::quote(isset($_POST['textColor'])?$_POST['textColor']:''))) {
        $_POST['id'] = DB::insertId();
        $_POST['id_status'] = $_POST['id'] -1;
        DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET `id_status`='.DB::quoteInt($_POST['id_status']).', `sort` = '.DB::quoteInt($_POST['id']).'
        WHERE id ='.DB::quoteInt($_POST['id']));
        $this->data = $_POST;
        return true;
      }
    }
    return false;
  }
  /**
   * Удаление сущности
   * @return boolean
   */
  public function deleteEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = $this->lang['ENTITY_DEL'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT'];
    if($_POST['id']) {
      $id_status = $_POST['id']-1;
      if (in_array($id_status, $this->systemStatus)) {
        $this->messageError = $this->lang['ENTITY_DEL_SYS'];
        return false;
      }
      if (DB::query('DELETE FROM `'.PREFIX.$this->pluginName.'` WHERE `id`= '.DB::quote($_POST['id']))) {
        return true;
      }    
      return false;
    }
  }
    
 

}