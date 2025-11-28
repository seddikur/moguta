<?php

/**
 * 
 * Класс PactionerDebt предназначен для выполнения действий, AJAX запросов плагина.
 * 
 */
class PactionerBind extends Actioner
{
    /**
     * 
     * Возвращает модельное окно привязки пользователя к оргиназиции.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function getModalUserBind()
    {
        $legal_id = $_POST['legal_id'];
        $user_id = $_POST['user_id'];

        if (!$legal = LegalEntity::$legalEntity->getLegalEntityData($legal_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }

        $user = LegalEntity::$legalEntity->getUserData($user_id);

        ob_start();
        include(__DIR__ . '/views/admin/modal/user/view.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Возвращает результат поиска пользователя.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function searchUser()
    {
        $request = '%'. $_POST['text'] .'%';

		$this->data = LegalEntity::$legalEntity->searchUser($request);

		return true;
    }

    /**
     * 
     * Возвращает массив данных пользователя.
     * 
     * @return bool
     * 
     */
    public function getUserData()
    {
        $user_id = $_POST['user_id'];

        $user = LegalEntity::$legalEntity->getUserData($user_id);

        $this->data = $user;

        return true;
    }

    /**
     * 
     * Сохраняет привязку пользователя к юридическому лицу и адресам доставки.
     * 
     * @return bool
     * 
     */
    public function saveUserBind()
    {
        $id = $_POST['legal_id'];
        $user_id = $_POST['user_id'];
        $page = $_POST['page'];

        if (!LegalEntity::$legalEntity->isLegalEntity($id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }

        $updateData = [
            'user_id' => $user_id,
            'default' => 0
        ];

        LegalEntity::$legalEntity->updateLegalEntityRow($id, $updateData);
        LegalEntity::$address->updateAddressesLegalEntity($id, $updateData);

        $user = LegalEntity::$legalEntity->getUserData($user_id);
        $data = LegalEntity::$legalEntity->getUserLegalEntityAllData($id, $user_id);

        
        ob_start();
        include(__DIR__ . '/views/admin/modal/view/components/user.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html']['user'] = $html;
        $this->data['user'] = $user;
        
        ob_start();
        include(__DIR__ . '/views/admin/modal/view/components/content.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html']['legal'] = $html;

        $entity = LegalEntity::$legalEntity->getRowForLegalEntityPage($id);
        
        ob_start();
        include(__DIR__ . '/views/admin/page/components/entities/td.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html']['td'] = $html;

        $this->data['legal']['id'] = $id;
        $this->messageSucces = $this->lang['ENTITY_SAVE'];

        return true;
    }
}