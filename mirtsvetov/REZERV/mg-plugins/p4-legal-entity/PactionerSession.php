<?php

/**
 *
 * Класс PactionerSession предназначен для выполнения действий, AJAX запросов плагина.
 * 
 */
class PactionerSession extends Actioner
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
        $user_id = $_POST['user_id'];

        $user = LegalEntity::$legalEntity->getUserData($user_id);

        $days = [
            1 => [
                'day' => 'Понедельник',
                'active' => false,
                'start' => null,
                'end' => null,
            ],
            2 => [
                'day' => 'Вторник',
                'active' => false,
                'start' => null,
                'end' => null,
            ],
            3 => [
                'day' => 'Среда',
                'active' => false,
                'start' => null,
                'end' => null,
            ],
            4 => [
                'day' => 'Четверг',
                'active' => false,
                'start' => null,
                'end' => null,
            ],
            5 => [
                'day' => 'Пятница',
                'active' => false,
                'start' => null,
                'end' => null,
            ],
            6 => [
                'day' => 'Суббота',
                'active' => false,
                'start' => null,
                'end' => null,
            ],
            7 => [
                'day' => 'Воскресенье',
                'active' => false,
                'start' => null,
                'end' => null,
            ],
        ];
       
        ob_start();
        include(__DIR__ . '/views/admin/modal/session/add.php');
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
        $user_id = $_POST['user_id'];

        $user = LegalEntity::$legalEntity->getUserData($user_id);
        $data = LegalEntity::$session->getEntityData($args = ['id' => $id]);
        
        $data['days'] = unserialize($data['days']);
       
        ob_start();
        include(__DIR__ . '/views/admin/modal/session/edit.php');
        $html = ob_get_contents();
        ob_end_clean();

        $this->data['html'] = $html;

        return true;
    }

    /**
     * 
     * Сохраняет сессии юридического лица.
     * 
     * @return bool
     * 
     */
    public function saveEntity()
    {
        $request = $_POST;

        $id = $request['id'];

        if (!$id) {
            if ($this->insertEntity($request)) {
                $this->messageSucces = $this->lang['ENTITY_SAVE'];
                return true;
            }
        }

        if ($id) {
            if ($this->updateEntity($id, $request)) {
                $this->messageSucces = $this->lang['ENTITY_SAVE'];
                return true;
            }
        }
    }

    /**
     * 
     * Добавляет новую запись.
     * 
     * @param array $request данные из формы.
     * 
     * @return bool
     * 
     */
    public function insertEntity($request)
    {
        $days = LegalEntity::$session->getEntityDays($request);
        
        $data = [
            'user_id' => $request['user_id'],
            'days' => $days
        ];

        LegalEntity::$session->insert($data);

        return true;
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
        $days = LegalEntity::$session->getEntityDays($request);

        $data = ['days' => $days];

        LegalEntity::$session->update($id, $data);

        return true;
    }
}