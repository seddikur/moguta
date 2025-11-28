<?php 
/**
 * 
 * Модель отвечает за задолженность юридического лица.
 * 
 */
class Model_LegalEntityDebt
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
			CREATE TABLE IF NOT EXISTS `'. PREFIX . $this->pluginName .'_debt` (
				`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT "Идентификатор записи",
				`legal_id` INT(11) NOT NULL COMMENT "Идентификатор юр. лица",
				`mir` DOUBLE DEFAULT 0 COMMENT "Организация МИР",
				`rm` DOUBLE DEFAULT 0 COMMENT "Организация РМ",
				`tk` DOUBLE DEFAULT 0 COMMENT "Организация ТК",
				`tare_tank` INT(11) DEFAULT 0 COMMENT "Тара баки",
				`tare_cover` INT(11) DEFAULT 0 COMMENT "Тара крышка",
				PRIMARY KEY (`id`),
				KEY (`legal_id`)
			) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
		');
    }

	/**
	 * 
	 * Проверяет запись задолженности юридического лица через ajax.
	 * 
	 * @param int $id идентификатор задолженности.
	 * @param int $legal_id идентификатор юридического лица.
	 * 
	 * @return bool
	 * 
	 */
	public function isEntity($id, $legal_id)
	{
		$sql = 'SELECT `id` 
					FROM `'. PREFIX . $this->pluginName .'_debt` 
				WHERE 
					`id` = '. DB::quoteInt($id). ' 
				AND 
					`legal_id` = '. DB::quoteInt($legal_id);

		$query = DB::query($sql);
		if ($query->num_rows) return true;

		return false;
	}

   /**
	 * 
	 * Возвращает запись о задолженности юридического лица.
	 * 
	 * Вызывается в шоркоде [p4-status-legal-entity] и через ajax.
     * 
     * @param array $args идентификаторы.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getEntityData($args, $public = false)
	{
		if ($args['id']) {
			$filter = '`id` = '. DB::quoteInt($args['id']);
		}

		if ($public == true) {
			$filter = '`legal_id` = '. DB::quoteInt($args['legal_id']);
		} else {
			if ($args['legal_id']) {
				$filter = '`legal_id` = '. DB::quoteInt($args['legal_id']);
			}
		}

		$sql = 'SELECT * 
					FROM `'. PREFIX .$this->pluginName .'_debt` 
				WHERE '. $filter;

		$query = DB::query($sql);
		if (!$query->num_rows) return false;

		$result = DB::fetchAssoc($query);

		return $result;
	}

	/**
	 * 
	 * Добавляет запись.
	 * 
	 * @param array $data
	 * 
	 */
	public function insert($data)
	{
		return DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'_debt` SET ', $data);
	}

	/**
	 * 
	 * Обновляет запись.
	 * 
	 * @param int $id идентификатор задолженности.
	 * @param array $data массив данных для обновления. 
	 * 
	 */
	public function update($id, $data)
	{
		$sql = 'UPDATE `'. PREFIX . $this->pluginName .'_debt` 
					SET '. DB::buildPartQuery($data) .' 
				WHERE 
					`id` = '. DB::quoteInt($id);

		return DB::query($sql);
	}

    /**
     * 
	 * Удаляет запись.
     * 
	 * @param array $args идентификаторы.
     * 
     */
    public function delete($args)
    {
		if ($args['id']) {
			$filter = '`id` = '. DB::quoteInt($args['id']);
		}

		if ($args['legal_id']) {
			$filter = '`legal_id` = '. DB::quoteInt($args['legal_id']);
		}

		$sql = 'DELETE FROM `'. PREFIX . $this->pluginName .'_debt` 
				WHERE '. $filter;

		return DB::query($sql);
    }
}