<?php

/**
 * 
 * Класс PactionerDebt предназначен для выполнения действий, AJAX запросов плагина.
 * 
 */
class PactionerDebt extends Actioner
{
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

        if (!LegalEntity::$debt->isEntity($id, $legal_id)) {
            $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
            $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
            return false;
        }

        $legalEntity = LegalEntity::$legalEntity->getLegalEntityData($legal_id);
        $debt = LegalEntity::$debt->getEntityData($args = ['id' => $id]);

        ob_start();
        include(__DIR__ . '/views/admin/modal/debt/edit.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Сохраняет информацию о задолженности юридического лица.
     * 
     * @return bool
     * 
     */
    public function saveEntity()
    {
        $request = $_POST;

        $id = $request['id'];
        $legal_id = $request['legal_id'];

        if ($id) {
            if (!LegalEntity::$debt->isEntity($id, $legal_id)) {
                $this->data['html'] = LegalEntity::$validation->getHtmlModalError();
                $this->messageError = $this->lang['ENTITY_NOT_FOUND'];
                return false;
            }

            if ($this->updateEntity($id, $request)) {
                $debt = LegalEntity::$debt->getEntityData($args = ['id' => $id]);

                ob_start();
                include(__DIR__ . '/views/admin/modal/view/components/debt.php');
                $html = ob_get_contents();
                ob_end_clean();

                $this->data['html'] = $html;
                $this->messageSucces = $this->lang['ENTITY_SAVE'];

                return true;
            }
        }
    }

    /**
     * 
     * Обновляет данные отредактированной записи.
     * 
     * @param int $id идентификатор записи.
     * @param array $request данные из формы.
     * 
     * @return bool
     * 
     */
    public function updateEntity($id, $request)
    {   
        $data = [
            'mir' => htmlspecialchars(trim($request['legal_mir'])),
            'rm' => htmlspecialchars(trim($request['legal_rm'])),
            'tk' => htmlspecialchars(trim($request['legal_tk'])),
            'tare_tank' => htmlspecialchars(trim($request['legal_tare_tank'])),
            'tare_cover' => htmlspecialchars(trim($request['legal_tare_cover']))
        ];

        LegalEntity::$debt->update($id, $data);

        return true;
    }
}