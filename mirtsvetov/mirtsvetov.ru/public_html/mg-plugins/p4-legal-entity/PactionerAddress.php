<?php

/**
 * 
 * Класс PactionerAddress предназначен для выполнения действий, AJAX запросов плагина.
 * 
 */
class PactionerAddress extends Actioner
{
	/**
     * 
     * Возвращает модальное окно для добавления записи.
     * 
     * @return bool
     * 
     */
    public function getModalAddWindow()
    {
        $legal_id = $_POST['legal_id'];
        $user_id = $_POST['user_id'];

        if (!$legalEntity = LegalEntity::$legalEntity->getUserLegalEntityData($legal_id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }
        
        ob_start();
        include(__DIR__ . '/views/admin/modal/address/add.php');
        $html = ob_get_contents();
        ob_end_clean();
        
        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Возвращает модальное окно для редактирования записи.
     * 
     * @return bool
     * 
     */
    public function getModalEditWindow()
    {
        $id = $_POST['id'];
        $legal_id = $_POST['legal_id'];
        $user_id = $_POST['user_id'];

        if (!$address = LegalEntity::$address->getAddress($id, $legal_id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }

        $legalEntity = LegalEntity::$legalEntity->getUserLegalEntityData($legal_id, $user_id);

        ob_start();
        include(__DIR__ . '/views/admin/modal/address/edit.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Возвращает модальное окно для подтверждения удаления записи.
     * 
     * @return bool
     * 
     */
    public function getModalDeleteWindow()
    {
        $id = $_POST['id'];
        $legal_id = $_POST['legal_id'];
        $user_id = $_POST['user_id'];

        if (!$data = LegalEntity::$address->getAddress($id, $legal_id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }
   
        ob_start();
        include(__DIR__ . '/views/admin/modal/address/delete.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Сохраняет адрес доставки.
     * 
     * @return bool
     * 
     */
    public function saveEntity()
    {
        $request = $_POST;

        $id = $request['id'];
        $legal_id = $request['legal_id'];
        $user_id = $request['user_id'];

        if (!$id) {
            if (!LegalEntity::$legalEntity->isUserLegalEntity($legal_id, $user_id)) {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
                $this->messageError = $this->lang['ENTITY_SAVE_NOT'];
                return false;
            }

            if ($errors = LegalEntity::$validation->validation($request)) {
                $this->data['errors'] = $errors;
                $this->messageError = $this->lang['VALIDATION_ERROR'];
                return false;
            }

            $address = [
                'legal_id' => $legal_id,
                'user_id' => $user_id,
                'address' => htmlspecialchars(trim($request['legal_address']))
            ];

            if (!LegalEntity::$address->isAddresses($legal_id, $user_id)) {
                $address['default'] = 1;
            }
    
            LegalEntity::$address->insertAddress($address);

            $address['id'] = DB::insertId();

            $data['legal'][$legal_id] = LegalEntity::$legalEntity->getUserLegalEntityData($legal_id, $user_id);

            ob_start();
            include(__DIR__ . '/views/admin/modal/view/components/address.php');
            $html = ob_get_contents();
            ob_end_clean();

            $this->data['action'] = 'add';
            $this->data['html'] = $html;
            $this->messageSucces = $this->lang['ENTITY_SAVE'];

            return true;
        }

        if ($id) {
            if (!LegalEntity::$address->isAddress($id, $legal_id, $user_id)) {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
                $this->messageError = $this->lang['ENTITY_SAVE_NOT'];
                return false;
            }

            if ($errors = LegalEntity::$validation->validation($request)) {
                $this->data['errors'] = $errors;
                $this->messageError = $this->lang['VALIDATION_ERROR'];
                return false;
            }

            $address = [
                'address' => htmlspecialchars(trim($request['legal_address']))
            ];
            
            LegalEntity::$address->updateAddress($id, $address);

            $address['id'] = $id;
            $address['legal_id'] = $legal_id;
            $address['default'] = htmlspecialchars($request['legal_default']);

            $data['legal'][$legal_id] = LegalEntity::$legalEntity->getUserLegalEntityData($legal_id, $user_id);

            ob_start();
            include(__DIR__ . '/views/admin/modal/view/components/address.php');
            $html = ob_get_contents();
            ob_end_clean();

            $this->data['action'] = 'edit';
            $this->data['html'] = $html;
            $this->messageSucces = $this->lang['ENTITY_SAVE'];

            return true;
        }
    }

    /**
     * 
     * Удаляет адрес пользователя из БД.
     * 
     * @return bool
     * 
     */
    public function deleteEntity()
    {
        $id = $_POST['id'];
        $legal_id = $_POST['legal_id'];
        $user_id = $_POST['user_id'];

        if (!LegalEntity::$address->isAddress($id, $legal_id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_DELETE_NOT'];
            return false;
        }

        LegalEntity::$address->deleteAddress($id);

        $this->messageSucces = $this->lang['ENTITY_DELETE'];

		return true;
    }

    /**
     * 
     * Устанавливает запись по умолчанию.
     * 
     * Используется в публичной части сайта и в приватной.
     * 
     * @return bool
     * 
     */
    public function defaultAddress()
    {
        $id = $_POST['id'];
        $legal_id = $_POST['legal_id'];

        // Публичная часть.
        if (!$_POST['user_id']) {
            $user_id = $_SESSION['user']->id;
        }
        
        // Приватная часть.
        if ($_POST['user_id']) {
            $user_id = $_POST['user_id'];
        }

        $page = $_POST['page'];

        if (!LegalEntity::$address->isAddress($id, $legal_id, $user_id)) {
            // Публичная часть.
            if (!$_POST['user_id']) {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError($admin = false);
            }

            // Приватная часть.
            if ($_POST['user_id']) {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
                $this->messageError = $this->lang['ENTITY_SELECTED_NOT'];
            }

            return false;
        }

        LegalEntity::$address->updateDefaultAddress($id, $legal_id);
        
        // Публичная часть.
        if (!$_POST['user_id']) {
            $this->data['html'] = LegalEntity::personalHtml();
        }

        // Приватная часть.
        if ($_POST['user_id']) {

            if ($page === 'users') {
                $data = LegalEntity::$legalEntity->getUserLegalEntitiesAllData($user_id);
            }

            if ($page === 'legalEntities') {
                $data = LegalEntity::$legalEntity->getUserLegalEntityAllData($legal_id, $user_id);
            }
    
            ob_start();
            include(__DIR__ . '/views/admin/modal/view/components/content.php');
            $html = ob_get_contents();
            ob_end_clean();

            $this->data['html'] = $html;
            $this->messageSucces = $this->lang['ENTITY_SELECTED'];
        }

        return true;
    }

    /**
     * 
     * Модальное окно для выбора адреса доставки на странице order
     * 
     * Используется в публичной части сайта.
     * 
     * @return bool
     * 
     */
    public function getModalChangeAddress()
    {
        $legal_id = $_POST['legal_id'];
        $user_id = $_SESSION['user']->id;

        if (!$data = LegalEntity::$address->getAddresses($legal_id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError($admin = false);
            return false;
        }

        ob_start();
        include(__DIR__ . '/views/public/modal/order/address/change.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Выбор адреса доставки на странице order.
     * 
     * Используется в публичной части сайта.
     * 
     * @return bool
     * 
     */
    public function selectAddress()
    {
        $id = $_POST['id'];
        $legal_id = $_POST['legal_id'];
        $user_id = $_SESSION['user']->id;

        if (!$address = LegalEntity::$address->getAddress($id, $legal_id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError($admin = false);
            return false;
        }

        $_SESSION['LegalEntity']['address_id'] = $address['id'];

        ob_start();
        include(__DIR__ . '/views/public/shortcodes/order/components/address.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;	
    }

    /**
     * Выбор адреса на странице оформления заказа.
     */
    public function selectAddressOrder()
    {
        $id = $_POST['id'];
        $user_id = $_SESSION['user']->id;
        $_SESSION['LegalEntity']['address_id'] = $id;
        
        $data['address'] = LegalEntity::$address->getUserLegalEntitiesAddresses($_SESSION['LegalEntity']['id'], $user_id);
        $data['legal']['selected'] = $_SESSION['LegalEntity']['id'];

        ob_start();
        include(__DIR__ . '/views/public/shortcodes/order/components/address.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;	
    }
}