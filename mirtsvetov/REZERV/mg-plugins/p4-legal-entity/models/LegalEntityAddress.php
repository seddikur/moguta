<?php 
/**
 * 
 * Модель отвечает за адреса доставок юридического лица.
 * 
 */
class Model_LegalEntityAddress
{
    private static $pluginName = null;

    public function __construct($pluginName)
	{
		$this->pluginName = $pluginName;
	}

	/**
	 * 
	 * Создание таблицы при активации плагина.
	 * 
	 */
	public function createTables()
	{
		DB::query('
			CREATE TABLE IF NOT EXISTS `'. PREFIX . $this->pluginName .'_address` (
				`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT "Идентификатор записи",
				`1c_id` VARCHAR(40) DEFAULT NULL COMMENT "Идентификатор записи в 1С",
				`legal_id` INT(11) NOT NULL COMMENT "Идентификатор юр. лица",
				`user_id` INT(11) NOT NULL DEFAULT 0 COMMENT "Идентификатор пользователя",
				`address` VARCHAR(255) NOT NULL COMMENT "Адрес доставки",
				`default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT "По умолчанию",
				PRIMARY KEY (`id`),
				INDEX (`1c_id`),
				KEY (`legal_id`, `user_id`)
			) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
		');
    }

	/**
	 * 
	 * Возвращает массив данных адреса доставки.
	 * 
	 * @param int $id идентификатор адреса.
	 * @param int $legal_id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getAddress($id, $legal_id, $user_id)
	{
		$sql = 'SELECT `id`, `legal_id`, `address`, `default` 
					FROM `'. PREFIX . $this->pluginName .'_address` 
				WHERE 
					`id` = '. DB::quoteInt($id) .' 
				AND 
					`legal_id` = '. DB::quoteInt($legal_id) .' 
				AND 
					`user_id` = '. DB::quoteInt($user_id);

		$query = DB::query($sql);
		if (!$query->num_rows) return false;
		$result = DB::fetchAssoc($query);

		return $result;
	}

	/**
	 * 
	 * Возвращает массив данных всех доступных адресов доставки пользователя.
	 * 
	 * @param int $legal_id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getAddresses($legal_id, $user_id)
	{
		$sql = 'SELECT `id`, `legal_id`, `address`, `default` 
					FROM `'. PREFIX . $this->pluginName .'_address` 
				WHERE 
					`legal_id` = '. DB::quoteInt($legal_id) .' 
				AND 
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
	 * Возвращает все адреса юридического лица.
	 * 
	 * @param int $legal_id идентификатор юридического лица
	 * 
	 * @return bool|array
	 * 
	 */
	public function getAddressesLegalEntity($legal_id)
	{
		$sql = 'SELECT `id`, `legal_id`, `address`, `default` 
					FROM `'. PREFIX . $this->pluginName .'_address` 
				WHERE 
					`legal_id` = '. DB::quoteInt($legal_id);

        $query = DB::query($sql);
		if (!$query->num_rows) return false;

        while ($row = DB::fetchAssoc($query)) {
            $result[] = $row;
        }

		return $result;
	}

	/**
	 * 
	 * Возвращает массив данных адреса доставки по умолчанию.
	 * 
	 * @param int $legal_id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getDefaultAddress($legal_id, $user_id)
	{
		$sql = 'SELECT `id`, `legal_id`, `address`, `default` 
					FROM `'. PREFIX . $this->pluginName .'_address` 
				WHERE 
					`legal_id` = '. DB::quoteInt($legal_id) .' 
				AND 
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
	 * Добавляет запись в таблице address.
	 * 
	 * @param array $data массив данных.
	 * 
	 */
	public function insertAddress($data)
	{
		return DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'_address` SET ', $data);
	}

	/**
	 * 
	 * Обновляет запись в таблице address.
	 * 
	 * @param int $id - идентификатор адреса.
	 * @param array $data - массив данных.
	 * 
	 */
	public function updateAddress($id, $data)
	{
		DB::query('UPDATE `'. PREFIX . $this->pluginName .'_address` 
						SET '. DB::buildPartQuery($data) .' 
					WHERE 
						`id` = '. DB::quoteInt($id));
	}

	/**
	 * 
	 * Обновляет адреса юридического лица.
	 * 
	 * @param int $legal_id идентификатор юридического лица.
	 * @param array $data массив данных.
	 * 
	 */
	public function updateAddressesLegalEntity($legal_id, $data)
	{
		DB::query('UPDATE `'. PREFIX . $this->pluginName .'_address`
						SET '. DB::buildPartQuery($data) .' 
					WHERE 
						`legal_id` = '. DB::quoteInt($legal_id));
	}

	/**
	 * 
	 * Удаляет запись из БД.
	 * 
	 * @param int $id идентификатор адреса.
	 * 
	 */
	public function deleteAddress($id)
	{
		DB::query('DELETE 
                        FROM `'. PREFIX . $this->pluginName .'_address` 
                    WHERE 
                        `id` = '. DB::quoteInt($id));
	}

	/**
	 * 
	 * Обновляет запись адреса по умолчанию в БД.
	 * 
	 * @param int $id идентификатор адреса.
	 * @param int $legal_id идентификатор юридического лица.
	 * 
	 */
	public function updateDefaultAddress($id, $legal_id) 
	{
		DB::query('UPDATE `'. PREFIX . $this->pluginName .'_address` 
						SET `default` = 0 
					WHERE 
						`legal_id` = '. DB::quoteInt($legal_id) .' 
					AND 
						`default` = 1');

		DB::query('UPDATE `'. PREFIX . $this->pluginName .'_address` 
						SET `default` = 1 
					WHERE `id` = '. DB::quoteInt($id));
	}

	/**
	 * 
	 * Проверяет адрес юридического лица пользователя.
	 * 
	 * @param int $id идентификатор адреса.
	 * @param int $legal_id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool
	 * 
	 */
	public function isAddress($id, $legal_id, $user_id)
	{
		$sql = 'SELECT `id` 
					FROM `'. PREFIX . $this->pluginName .'_address` 
				WHERE 
					`id` = '. DB::quoteInt($id) .' 
				AND 
					`legal_id` = '. DB::quoteInt($legal_id) .' 
				AND 
					`user_id` = '. DB::quoteInt($user_id);

		$query = DB::query($sql);
		if ($query->num_rows) return true;

		return false;
	}

	/**
	 * 
	 * Проверяет, есть ли на аккаунте адреса юридического лица.
	 * 
	 * @param int $legal_id идентификатор юридического лица.
	 * @param int $user_id идентификатор пользователя.
	 * 
	 * @return bool
	 * 
	 */
	public function isAddresses($legal_id, $user_id)
	{
		$sql = 'SELECT `id` 
					FROM `'. PREFIX . $this->pluginName .'_address` 
				WHERE 
					`legal_id` = '. DB::quoteInt($legal_id) .' 
				AND 
					`user_id` = '. DB::quoteInt($user_id);

		$query = DB::query($sql);
		if (!$query->num_rows) return false;

		return true;
	}

	/**
	 * Возвращает адреса юр лица пользователя в select на странице оформления заказа
	 */
	public function getUserLegalEntitiesAddresses($legal_id, $user_id)
	{
		$sql = 'SELECT `id`, `address`, `default` 
				FROM `'. PREFIX . $this->pluginName .'_address` 
				WHERE `legal_id` = '. DB::quoteInt($legal_id) .' AND `user_id` = '. DB::quoteInt($user_id);

        $query = DB::query($sql);
		if (!$query->num_rows) return null;

		$address = [];
    	$defaultEntity = null;

        while ($row = DB::fetchAssoc($query)) {
            $address[$row['id']] = $row;
			// Запоминаем юрлицо по умолчанию
			if ($row['default'] == 1) {
				$defaultEntity = $row['id'];
			}
        }

		$isAddressSelected = !empty($_SESSION['LegalEntity']['address_id']) 
                       && isset($_SESSION['LegalEntity']['address_id']);

		$selected = $isAddressSelected 
                ? $_SESSION['LegalEntity']['address_id'] 
                : $defaultEntity;

		return [
			'items' => $address,
			'selected' => $selected,
		];
	}
}