<?php

/**
 * 
 * Класс PactionerLegalEntity предназначен для выполнения действий, AJAX запросов плагина.
 * 
 */
class PactionerLegalEntity extends Actioner
{
    /**
     * 
     * Возвращает модальное окно c юридическими лицами и адресами пользователя.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function getModalViewLegalEntities()
    {
        $id = $_POST['id'];
        $user_id = $_POST['user_id'];
        $page = $_POST['page'];

        if (!$html = $this->getHtmlModalViewLegalEntities($id, $user_id, $page)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Шаблон модального окна с юридическими лицами и адресами пользователя.
     * 
     * @param int $id идентификатор юридического лица.
     * @param int $user_id идентификатор пользователя.
     * @param int $page страница плагина.
     * 
     * @return string
     * 
     */
    private function getHtmlModalViewLegalEntities($id, $user_id, $page)
    {
        $user = LegalEntity::$legalEntity->getUserData($user_id);

        if (!$id) {
            $data = LegalEntity::$legalEntity->getUserLegalEntitiesAllData($user_id);
        } else {
            if (!LegalEntity::$legalEntity->isUserLegalEntity($id, $user_id)) {
                return false;
            }
            $data = LegalEntity::$legalEntity->getUserLegalEntityAllData($id, $user_id);
        }

        ob_start();
        include(__DIR__ . '/views/admin/modal/view/view.php');
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * 
     * Возвращает модальное окно для добавления юридического лица, задолженности и адреса доставки.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function getModalAddLegalEntity()
    {
        $user_id = $_POST['user_id'];

        $user = LegalEntity::$legalEntity->getUserData($user_id);

        ob_start();
        include(__DIR__ . '/views/admin/modal/legal/add.php');
        $html = ob_get_contents();
        ob_end_clean();
        
        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Возвращает модальное окно для редактирования юридического лица.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function getModalEditLegalEntity()
    {
        $id = $_POST['id'];
        $user_id = $_POST['user_id'];

        if (!$data = LegalEntity::$legalEntity->getUserLegalEntityData($id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }

        $user = LegalEntity::$legalEntity->getUserData($user_id);

        ob_start();
        include(__DIR__ . '/views/admin/modal/legal/edit.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Сохраняет новую или измененную запись юридического лица.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function saveLegalEntity()
    {
        $request = $_POST;

        $id = $request['id'];
        $user_id = $request['user_id'];
        $page = $request['page'];

        if ($id) {
            
            if (!LegalEntity::$legalEntity->isUserLegalEntity($id, $user_id)) {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
                $this->messageError = $this->lang['ENTITY_SAVE_NOT'];
                return false;
            }

            if ($errors = LegalEntity::$validation->validation($request)) {
                $this->data['errors'] = $errors;
                $this->messageError = $this->lang['VALIDATION_ERROR'];
                return false;
            }

            $legal = [
                'user_id' => $user_id,
                'name' => htmlspecialchars(trim($request['legal_name'])),
                'inn' => htmlspecialchars(trim($request['legal_inn'])),
                'kpp' => htmlspecialchars(trim($request['legal_kpp']))
            ];

            LegalEntity::$legalEntity->updateLegalEntityRow($id, $legal);

            $legal['id'] = $id;
            $legal['default'] = htmlspecialchars($request['legal_default']);

            ob_start();
            include(__DIR__ . '/views/admin/modal/view/components/legal.php');
            $html = ob_get_contents();
            ob_end_clean();

            $this->data['action'] = 'edit';
            $this->data['html'] = $html;

            $entity = LegalEntity::$legalEntity->getRowForLegalEntityPage($id);
            
            ob_start();
            include(__DIR__ . '/views/admin/page/components/entities/td.php');
            $html = ob_get_contents();
            ob_end_clean();
            
            $this->data['legal']['id'] = $id;
            $this->data['html_td'] = $html;

            $this->messageSucces = $this->lang['ENTITY_SAVE'];

            return true;

        } else {

            if ($errors = LegalEntity::$validation->validation($request)) {
                $this->data['errors'] = $errors;
                $this->messageError = $this->lang['VALIDATION_ERROR'];
                return false;
            }

            $isLegalEntities = LegalEntity::$legalEntity->isLegalEntities($user_id);

            $legal = [
                'user_id' => $user_id,
                'name' => htmlspecialchars(trim($request['legal_name'])),
                'inn' => htmlspecialchars(trim($request['legal_inn']))
            ];

            if ($request['legal_kpp']) {
                $legal['kpp'] = htmlspecialchars(trim($request['legal_kpp']));
            }

            if (!$isLegalEntities) {
                $legal['default'] = 1;
            }

            LegalEntity::$legalEntity->insertLegalEntityRow($legal);

            $legal_id = DB::insertId();

            $debt = [
                'legal_id' => $legal_id,
                'mir' => htmlspecialchars(trim($request['legal_mir'])),
                'rm' => htmlspecialchars(trim($request['legal_rm'])),
                'tk' => htmlspecialchars(trim($request['legal_tk'])),
                'tare_tank' => htmlspecialchars(trim($request['legal_tare_tank'])),
                'tare_cover' => htmlspecialchars(trim($request['legal_tare_cover'])),
            ];

            LegalEntity::$debt->insert($debt);

            $address = [
                'user_id' => $user_id,
                'legal_id' => $legal_id,
                'address' => htmlspecialchars(trim($request['legal_address']))
            ];

            if (!$isLegalEntities) {
                $address['default'] = 1;
            }

            LegalEntity::$address->insertAddress($address);

            $this->data['action'] = 'add';
            $this->data['html'] = $this->getHtmlContentLegalEntities($legal_id, $user_id, $page);
            $this->messageSucces = $this->lang['ENTITY_SAVE'];

            return true;
        }
    }

    /**
     * 
     * Модальное окно для подтверждения удаления юридического лица.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function getModalDeleteLegalEntity()
    {
        $id = $_POST['id'];
        $user_id = $_POST['user_id'];

        if (!$data = LegalEntity::$legalEntity->getUserLegalEntityData($id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }

        ob_start();
        include(__DIR__ . '/views/admin/modal/legal/delete.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
	 * Удаляет юридическое лицо из БД.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function deleteLegalEntity()
    {
        $id = $_POST['id'];
        $user_id = $_POST['user_id'];
        $page = $_POST['page'];

        if (!LegalEntity::$legalEntity->isUserLegalEntity($id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_DELETE_NOT'];
            return false;
        }

        LegalEntity::$legalEntity->deleteLegalEntityRow($id);

		$this->data['html'] = $this->getHtmlContentLegalEntities($id, $user_id, $page);
        $this->messageSucces = $this->lang['ENTITY_DELETE'];

		return true;
    }

    /**
     * 
     * Устанавливает юридическое лицо умолчанию.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function defaultLegalEntity()
    {
        $id = $_POST['id'];

        if (!$_POST['user_id']) {
            $user_id = $_SESSION['user']->id;
        } else {
            $user_id = $_POST['user_id'];
        }
       
        $page = $_POST['page'];

        if (!LegalEntity::$legalEntity->isUserLegalEntity($id, $user_id)) {
            if (!$_POST['user_id']) {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError($admin = false);
            } else {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
                $this->messageError = $this->lang['ENTITY_SELECTED_NOT'];
            }
            return false;
        }

        LegalEntity::$legalEntity->updateUserDefaultLegalEntity($id, $user_id);

        if (!$_POST['user_id']) {
            $this->data['html'] = LegalEntity::personalHtml();
        } else {
            $this->data['html'] = $this->getHtmlContentLegalEntities($id, $user_id, $page);
            $this->messageSucces = $this->lang['ENTITY_SELECTED'];
        }
       
        return true;
    }

    /**
     * 
     * Модальное окно для выбора юридического лица на странице order.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function getModalChangeLegalEnity()
    {
        $user_id = $_SESSION['user']->id;

        if (!$data = LegalEntity::$legalEntity->getUserLegalEntitiesData($user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError($admin = false);
            return false;
        }

        ob_start();
        include(__DIR__ . '/views/public/modal/order/legal/change.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Выбор пользователем юридического лица на странице order.
     * 
     * ajax type POST
     * 
     * @return bool
     * 
     */
    public function selectLegalEntity()
    {
        $id = $_POST['id'];
        $user_id = $_SESSION['user']->id;

        if (!$LegalEntity = LegalEntity::$legalEntity->getUserLegalEntityData($id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError($admin = false);
            return false;
        }

        $_SESSION['LegalEntity']['id'] = $LegalEntity['id'];

        $defaultAddress = LegalEntity::$address->getDefaultAddress($id, $user_id);

        if ($defaultAddress) {
            $_SESSION['LegalEntity']['address_id'] = $defaultAddress['id'];
        } else {
            if ($_SESSION['LegalEntity']['address_id']) {
                unset($_SESSION['LegalEntity']['address_id']);
            }
            $isAddresses = LegalEntity::$address->isAddresses($id, $user_id);
        }

        $legal = $LegalEntity;
        $address = $defaultAddress;

        ob_start();
        include(__DIR__ . '/views/public/shortcodes/order/order.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html']['content'] = $html;

        return true;	
    }

    /**
     * 
     * Верстка для компонента модального окна views 
     * с данными юридических лиц и адресов доставок.
     * 
     * @param int $id идентификатор юридического лица.
     * @param int $user_id идентификатор пользователя.
     * @param string $page страница плагина.
     * 
     * @return string
     * 
     */
    private function getHtmlContentLegalEntities($id, $user_id, $page)
    {
        if ($page === 'users') {
            $data = LegalEntity::$legalEntity->getUserLegalEntitiesAllData($user_id);
        }

        if ($page === 'legalEntities') {
            $data = LegalEntity::$legalEntity->getUserLegalEntityAllData($id, $user_id);
        }

        ob_start();
        include(__DIR__ . '/views/admin/modal/view/components/content.php');
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * Выбор Юр лица на странице оформления заказа.
     */
    public function selectLegalEntityOrder()
    {
        $id = $_POST['id'];
        $user_id = $_SESSION['user']->id;

        $_SESSION['LegalEntity']['id'] = $id;
        unset($_SESSION['LegalEntity']['address_id']);
        
        $data['legal'] = LegalEntity::$legalEntity->getUserLegalEntities($user_id);
		$data['address'] = LegalEntity::$address->getUserLegalEntitiesAddresses($data['legal']['selected'], $user_id);
        
        ob_start();
        include(__DIR__ . '/views/public/shortcodes/order/order.php');
        $content = ob_get_contents();
        ob_end_clean();

        $this->data['html']['content'] = $content;

        // Блок задолженности.
        $data = LegalEntity::$debt->getEntityData($args = ['legal_id' => $id]);
		$data['session'] = LegalEntity::$session->getSessionData();

		ob_start();
		include(__DIR__ . '/views/public/shortcodes/status.php');
		$html = ob_get_contents();
		ob_end_clean();

        $this->data['html']['statusBlock'] = $html;


        // Данные менеджера.
        $data = LegalEntity::$legalEntity->getManagerLegal($id, $user_id);

        if (!empty($data['manager_phone'])) {
            $data['manager_phone'] = LegalEntity::$features->phoneFormat($data['manager_phone']);
        }

        ob_start();
        include(__DIR__ . '/views/public/shortcodes/manager.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html']['manager_info'] = $html;

        return true;	
    }

     /**
     * 
     * Выбор пользователем юридического лица в шапке сайта.
     * 
     * ajax type POST
     * 
     * 
     * @return bool
     * 
     */
    public function selectHeaderLegalEntity()
    {
        $id = $_POST['id'];
        $user_id = $_SESSION['user']->id;

        if (!$LegalEntity = LegalEntity::$legalEntity->getUserLegalEntityData($id, $user_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError($admin = false);
            return false;
        }

        $_SESSION['LegalEntity']['id'] =  $LegalEntity['id'];
        unset($_SESSION['LegalEntity']['address_id']);

        return true;	
    }
}