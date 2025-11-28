<?php 
/**
 * 
 * Модель отвечает за Юридические лица.
 * 
 */
class Model_LegalEntity
{
    private static $pluginName = null;
	private static $path = null;
	private static $lang = [];

    public function __construct($pluginName, $path, $lang)
	{
		$this->pluginName = $pluginName;
		$this->path = $path;
		$this->lang = $lang;
	}
	
    /**
	 * 
	 * Создание таблицы при активации плагина.
	 * 
	 */
	public function createTables()
	{
		DB::query('
			CREATE TABLE IF NOT EXISTS `'. PREFIX . $this->pluginName .'` (
				`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT "Идентификатор записи",
				`1c_id` VARCHAR(36) DEFAULT NULL COMMENT "Идентификатор записи в 1С",
				`user_id` INT(11) NOT NULL DEFAULT 0 COMMENT "Идентификатор пользователя",
				`name` VARCHAR(100) NOT NULL COMMENT "Наименование",
				`inn` VARCHAR(12) NOT NULL COMMENT "ИНН",
				`kpp` VARCHAR(9) DEFAULT NULL COMMENT "КПП",
				`default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT "По умолчанию",
				`manager_name` VARCHAR(50) DEFAULT NULL,
				`manager_phone` VARCHAR(50) DEFAULT NULL,
				PRIMARY KEY (`id`),
				INDEX (`1c_id`),
				KEY (`user_id`)
			) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
		');
	}

	/**
	 * 
	 * Возвращает SQL запрос для страницы "Пользователи".
	 * 
	 * @param string $filter условия выборки.
	 * 
	 * @return string
	 * 
	 */
	public function getSqlForUsersPage($filter)
	{
		$sql = 'SELECT `id`, `name`, `sname`, `pname`, `login_email`, `email`, `login_phone`, `phone` 
					FROM `'. PREFIX .'user` 
				WHERE '. $filter;

		return $sql;
	}

	/**
	 * 
	 * Возвращает SQL запрос для страницы "Юридические лица".
	 * 
	 * @param string $filter условия выборки.
	 * 
	 * @return string
	 * 
	 */
	public function getSqlForLegalEntitiesPage($filter)
	{
		$sql = 'SELECT le.`id`, le.`user_id`, le.`name`, le.`inn`, le.`kpp`, 
				u.`login_email`, u.`email`, u.`login_phone`, u.`phone`, le.`manager_phone`, le.`manager_name`
					FROM `'. PREFIX . $this->pluginName .'` AS le 
						LEFT JOIN `'. PREFIX .'user` u 
							ON le.`user_id` = u.`id` 
				WHERE '. $filter;

		return $sql;
	}

	/**
	 * 
	 * Возвращает данные юридического лица для обновления строки <tr> 
	 * в html таблице через ajax для страницы "Юридические лица".
	 * 
	 * @param int $id идентификатор организации.
	 * 
	 * @return array
	 * 
	 */
	public function getRowForLegalEntityPage($id)
	{
		$filter = 'le.`id` = '. DB::quoteInt($id);
		$sql = $this->getSqlForLegalEntitiesPage($filter);

		$query = DB::query($sql);
		$result = DB::fetchAssoc($query);

		return $result;
	}

	/**
	 * 
	 * Возвращает все данные о юридических лицах пользователя в шорткоде или через ajax.
	 * 
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return array
	 * 
	 */
	public function getUserLegalEntitiesAllData($user_id)
	{
		$sql = 'SELECT `id`, `name`, `inn`, `kpp`, `default` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`user_id` = '. DB::quoteInt($user_id);

		$query = DB::query($sql);

		while ($row = DB::fetchAssoc($query)) {
			$ids[] = $row['id'];
			$result['legal'][$row['id']] = $row;
		}

		if ($ids) {
			foreach ($ids AS $id) {

				$sql = 'SELECT * 
							FROM `'. PREFIX . $this->pluginName .'_debt` 
						WHERE 
							`legal_id` = '. DB::quoteInt($id);

				$query = DB::query($sql);

				if ($query->num_rows) {
					$result['debt'][$id] = DB::fetchAssoc($query);
				} else {
					$result['debt'][$id] = null;
				}

				$sql = 'SELECT `id`, `legal_id`, `address`, `default` 
							FROM `'. PREFIX . $this->pluginName .'_address` 
						WHERE 
							`legal_id` = '. DB::quoteInt($id);

				$query = DB::query($sql);

				if ($query->num_rows) {
					while ($row = DB::fetchAssoc($query)) {
						$result['address'][$id][] = $row;
					}
				} else {
					$result['address'][$id] = null;
				}
			}
		}

		return $result;
	}

	/**
     * 
     * Возвращает все данные о юридическом лице пользователя через ajax.
     * 
     * @param int $id идентификатор юридического лица.
     * @param int $user_id идентификатор пользователя.
     * 
     * @return array
     * 
     */
    public function getUserLegalEntityAllData($id, $user_id)
    {
        $result['legal'][$id] = $this->getUserLegalEntityData($id, $user_id);
		$result['debt'][$id] = LegalEntity::$debt->getEntityData($args = ['legal_id' => $id]);
		$result['address'][$id] = LegalEntity::$address->getAddressesLegalEntity($id);

        return $result;
    }

	/**
	 * 
	 * Возвращает данные юридического лица пользователя в шоркоде или через ajax.
	 * 
	 * @param int $id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getUserLegalEntityData($id, $user_id)
	{
		$sql = 'SELECT `id`, `user_id`, `name`, `inn`, `kpp`, `default` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`id` = '. DB::quoteInt($id) .' 
				AND 
					`user_id` = '. DB::quoteInt($user_id);

		$query = DB::query($sql);
		if (!$query->num_rows) return false;

		$result = DB::fetchAssoc($query);

		return $result;
	}

	/**
	 * 
	 * Возвращает данные о юридических лицах пользователя в шоркоде или через ajax.
	 * 
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getUserLegalEntitiesData($user_id)
	{
		$sql = 'SELECT `id`, `name`, `inn`, `kpp`, `default` 
                    FROM `'. PREFIX . $this->pluginName .'` 
                WHERE 
                    `user_id` = '. DB::quoteInt($user_id);

        $query = DB::query($sql);
		if (!$query->num_rows) return false;

        while ($row = DB::fetchAssoc($query)) {
            $result[] = $row;
        }

		return $result;
	}

	/**
	 * 
	 * Возвращает данные юридического лица пользователя по умолчанию в шоркоде.
	 * 
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getUserDefaultLegalEntityData($user_id)
	{
		$sql = 'SELECT `id`, `name`, `inn`, `kpp`, `default` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`user_id` = '. DB::quoteInt($user_id) .' 
				AND 
					`default` = 1';

		$query = DB::query($sql);
		if (!$query->num_rows) return false;

		$result = DB::fetchAssoc($query);

		return $result;
	}

	/**
	 * 
	 * Проверяет на существование записи юридического лица пользователя через ajax.
	 * 
	 * @param int $id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool
	 * 
	 */
	public function isUserLegalEntity($id, $user_id)
	{
		$sql = 'SELECT `id` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`id` = '. DB::quoteInt($id) .' 
				AND 
					`user_id` = '. DB::quoteInt($user_id);

		$query = DB::query($sql);
		if ($query->num_rows) return true;

		return false;
	}

	/**
	 * 
	 * Проверяет, есть ли на аккаунте юридические лица.
	 * 
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool
	 * 
	 */
	public function isLegalEntities($user_id)
	{
		$sql = 'SELECT `id` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`user_id` = '. DB::quoteInt($user_id);

		$query = DB::query($sql);
		if ($query->num_rows) return true;

		return false;
	}

	/**
	 * 
	 * Возвращает данные юридического лица через ajax.
	 * 
	 * @param int $id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getLegalEntityData($id)
	{
		$sql = 'SELECT `id`, `name`, `inn`, `kpp`, `default` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`id` = '. DB::quoteInt($id);

		$query = DB::query($sql);
		if (!$query->num_rows) return false;
		$result = DB::fetchAssoc($query);

		return $result;
	}

	/**
	 * 
	 * Проверяет на существование записи юридического лица через ajax.
	 * 
	 * @param int $id идентификатор юридического лица.
	 * 
	 * @return bool
	 * 
	 */
	public function isLegalEntity($id)
	{
		$sql = 'SELECT `id` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`id` = '. DB::quoteInt($id);

		$query = DB::query($sql);
		if ($query->num_rows) return true;

		return false;
	}

	/**
	 * 
	 * Добавляет строку юридического лица в таблицу БД через ajax.
	 * 
	 * @param array $data
	 * 
	 */
	public function insertLegalEntityRow($data)
	{
		return DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'` SET ', $data);
	}

	/**
	 * 
	 * Обновляет строку юридического лица в таблице БД через ajax.
	 * 
	 * @param int $id идентификатор юридического лица.
	 * @param array $data массив данных для обновления.
	 * 
	 */
	public function updateLegalEntityRow($id, $data)
	{
		$sql = 'UPDATE `'. PREFIX . $this->pluginName .'` 
					SET '. DB::buildPartQuery($data) .' 
				WHERE `id` = '. DB::quoteInt($id);

		return DB::query($sql);
	}

	/**
	 * 
	 * Обновляет юридическое лицо пользователя через ajax.
	 * 
	 * @param int $id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 */
	public function updateUserDefaultLegalEntity($id, $user_id) 
	{
		if ($default = $this->getUserDefaultLegalEntityData($user_id)) {
			$data = [
				'default' => 0,
			];
			$this->updateLegalEntityRow($default['id'], $data);
		}

		$data = [
			'default' => 1,
		];
		$this->updateLegalEntityRow($id, $data);
	}

	/**
	 * 
	 * Удаляет строку юридического лица и все привязанные данные из таблицы БД через ajax.
	 * 
	 * @param int $id идентификатор юридического лица.
	 * 
	 */
	public function deleteLegalEntityRow($id)
	{
		DB::query('DELETE 
						FROM `'. PREFIX . $this->pluginName .'` 
					WHERE 
						`id` = '. DB::quoteInt($id));

		DB::query('DELETE 
						FROM `'. PREFIX . $this->pluginName .'_debt` 
					WHERE 
						`legal_id` = '. DB::quoteInt($id));

		DB::query('DELETE 
						FROM `'. PREFIX . $this->pluginName .'_address` 
					WHERE 
						`legal_id` = '. DB::quoteInt($id));
	}

	/**
     * 
     * Возвращает массив данных пользователя через ajax.
     * 
     * @param int $user_id идентификатор пользователя.
     * 
     * @return bool|array
     * 
     */
    public function getUserData($user_id)
    {
        $sql = 'SELECT `id`, `name`, `sname`, `pname`, `login_email`, `login_phone`, `email`, `phone`
                    FROM `'. PREFIX .'user` 
                WHERE 
                    `id` = '. DB::quoteInt($user_id);

        $query = DB::query($sql);
		if (!$query->num_rows) return false;
        $result =  DB::fetchAssoc($query);

        return $result;
    }

	/**
	 * 
	 * Поиск пользователя по логину E-mail адреса или телефона.
	 * 
	 * @param string $request текст ввода в input.
	 * 
	 * @return array|null
	 * 
	 */
	public function searchUser($request)
	{
		$filter = '(`login_email` LIKE '. DB::quote($request) .') 
						OR 
				   (`login_phone` LIKE '. DB::quote($request) .')';

		$sql = 'SELECT DISTINCT `id`, `login_email`, `name`, `sname`, `pname` 
					FROM `'. PREFIX .'user` 
				WHERE '. $filter .' GROUP BY `id` LIMIT 10';

		$query = DB::query($sql);

		while ($row = DB::fetchAssoc($query)) {
			$result[] = $row;
		}
		
		return $result;
	}

	/**
	 * Возвращает юр лица пользователя в select на странице оформления заказа
	 */
	public function getUserLegalEntities($user_id)
	{
		$sql = 'SELECT `id`, `name`, `inn`, `kpp`, `default` 
				FROM `'. PREFIX . $this->pluginName .'` 
                WHERE  `user_id` = '. DB::quoteInt($user_id);

        $query = DB::query($sql);
		if (!$query->num_rows) return null;

		$legal = [];
    	$defaultEntity = null;

        while ($row = DB::fetchAssoc($query)) {
            $legal[$row['id']] = $row;
			// Запоминаем юрлицо по умолчанию
			if ($row['default'] == 1) {
				$defaultEntity = $row['id'];
			}
        }
		
		$isLegalSelected = !empty($_SESSION['LegalEntity']['id']) 
                       && isset($_SESSION['LegalEntity']['id']);

		$selected = $isLegalSelected 
                ? $_SESSION['LegalEntity']['id'] 
                : $defaultEntity;
		
		return [
			'items' => $legal,
			'selected' => $selected,
		];
	}


	/**
	 * Возвращает данные менеджера юр лица.
	 * 
	 * @param int $legalId Идентификатор юр лица.
	 * @param int $userId Идентификатор пользователя.
	 * 
	 * @return array
	 */
	public function getManagerLegal(int $legalId, int $userId): array
	{
		$sql = 'SELECT `manager_name`, `manager_phone` 
				FROM `'. PREFIX . $this->pluginName .'` 
                WHERE `id` = '. DB::quoteInt($legalId) .' 
                	AND `user_id` = '. DB::quoteInt($userId);

        $query = DB::query($sql);
		if (!$query->num_rows) return [];

        return DB::fetchAssoc($query);
	}

	/**
	 * 
	 * Возвращает данные менеджера по умолчанию от юр лица.
	 * 
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getUserDefaultManagerLegal($user_id)
	{
		$sql = 'SELECT `manager_name`, `manager_phone` 
					FROM `'. PREFIX . $this->pluginName .'` 
				WHERE 
					`user_id` = '. DB::quoteInt($user_id) .' 
				AND 
					`default` = 1';

		$query = DB::query($sql);
		if (!$query->num_rows) return [];

		$result = DB::fetchAssoc($query);

		return $result;
	}
}