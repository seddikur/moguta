<?php
/**
 * 
 * Модель отвечает за пользовательскую сессию на доступ к оформлению заказа.
 * 
 */
class Model_LegalEntityUserSession
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
			CREATE TABLE IF NOT EXISTS `'. PREFIX . $this->pluginName .'_session` (
				`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT "Идентификатор записи",
				`user_id` INT(11) NOT NULL COMMENT "Идентификатор пользователя",
				`days` BLOB NOT NULL COMMENT "Массив данных",
				PRIMARY KEY (`id`),
				KEY (`user_id`)
			) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
		');
	}

    /**
	 * 
	 * Возвращает SQL запрос для страницы "Сессии" плагина.
	 * 
	 * @param string $filter условия выборки.
	 * 
	 * @return string
	 * 
	 */
	public function getSqlForSessionPage($filter)
	{
		$sql = 'SELECT u.`id`, u.`name`, u.`sname`, u.`pname`, u.`login_email`, u.`email`, u.`login_phone`, u.`phone`, 
                s.`id` AS session_id, s.`days` 
					FROM `'. PREFIX .'user` AS u 
                        LEFT JOIN `'. PREFIX . $this->pluginName .'_session` AS s 
                            ON u.`id` = s.`user_id` 
                WHERE '. $filter;

		return $sql;
	}

	/**
	 * 
	 * Возвращает запись сессии.
	 * 
	 * @param array $args идентификаторы.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getEntityData($args)
	{
		if ($args['id']) {
			$filter = '`id` = '. DB::quoteInt($args['id']);
		}

		if ($args['user_id']) {
			$filter = '`user_id` = '. DB::quoteInt($args['user_id']);
		}

		$sql = 'SELECT * 
					FROM `'. PREFIX .$this->pluginName .'_session` 
				WHERE '. $filter;
					

		$query = DB::query($sql);
		if (!$query->num_rows) return false;

		$result = DB::fetchAssoc($query);

		return $result;
	}

	/**
	 * 
	 * Возвращает дни недели для последующего добавления в БД.
	 * 
	 * @param array $request данные из формы.
	 * 
	 * @return string
	 * 
	 */
	public function getEntityDays($request)
	{
		for ($i = 1; $i <= 7; $i++) {

            $day = [
                'day' => $request['day_' . $i],
                'active' => isset($request['active_' . $i]) ? 1 : 0,
                'start' => !empty($request['start_' . $i]) ? $request['start_' . $i] :  null,
                'end' => !empty($request['end_' . $i]) ? $request['end_' . $i] : null,
            ];

            $days[$i] = $day;
        }

		$result = serialize($days);

		return $result;
	}

	/**
	 * 
	 * Проверяет дату сессии пользователя на доступ к оформлению заказа.
	 * 
	 * Используется в шаблоне сайта.
	 * 
	 * @return bool
	 * 
	 */
	public function isSession()
	{
		if (!User::isAuth()) return false;

		$user_id = $_SESSION['user']->id;

		if (!$user_id) return false;

		$entity = $this->getEntityData($args = ['user_id' => $user_id]);
		if (!$entity) return false;

		$date = $this->getDate($entity['days']);
		if ($date['current']['active'] && (($date['time'] >= $date['start']) && ($date['time'] < $date['end']))) {
			return true;
		}

		return false;
	}

	/**
	 * 
	 * Возвращает данные сессии пользователя.
	 * 
	 * Используется в шоркоде [p4-status-legal-entity] и в шаблоне сайта.
	 * 
	 * @return bool|array
	 * 
	 */
	public function getSessionData()
	{
		if (!User::isAuth()) return false;
		
		$user_id = $_SESSION['user']->id;

		$entity = $this->getEntityData($args = ['user_id' => $user_id]);
		if (!$entity) return false;

		$date = $this->getDate($entity['days']);
		if (!$date) return false;

		// До начала сессии текущего дня.
		if ($date['current']['active'] && ($date['time'] < $date['start'])) {

			$result['current'] = $date['current'];
			$result['current']['date'] = $date['date'];
			$result['current']['date_convert'] = MG::dateConvert($date['date']);
			$result['current']['diff_start'] = LegalEntity::$features->getTimeDiff($date['current']['start']);
			
			return $result;

		// Во время сессии текущего дня.
		} else if ($date['current']['active'] && (($date['time'] >= $date['start']) && ($date['time'] < $date['end']))) {

			$result['next'] = $this->getNextSessionDay($date['date'], $date['days']);

			$result['current'] = $date['current'];
			$result['current']['date'] = $date['date'];
			$result['current']['date_convert'] = MG::dateConvert($date['date']);
			$result['current']['diff_end'] = LegalEntity::$features->getTimeDiff($date['current']['end']);

			return $result;

		// До начала сессии следующего дня.
		} else {

			if ($result['next'] = $this->getNextSessionDay($date['date'], $date['days'])) {
				return $result;
			}

			return false;
		}
	}

	/**
	 * 
	 * Возвращает дату следующей сессии.
	 * 
	 * @param string $date текущая дата, пример: 31-07-2023.
	 * @param array $days дни недели.
	 * 
	 * @return array|bool
	 * 
	 */
	public function getNextSessionDay($date, $days)
	{
		for ($i = 1; $i <= 7; $i++) {

			$date = date('d-m-Y', strtotime($date . '+'. $i .' day'));
			$day = $days[date('N', strtotime($date))];

			if ($day['active']) {

				$day['date'] = $date;
				$day['date_convert'] = MG::dateConvert($date);
				$day['diff_start'] = LegalEntity::$features->getTimeDiff($day['date'] . $day['start']);
				
				return $day;
			}

			unset($date);
		}

		return false;
	}

	/**
	 * 
	 * Возвращает данные даты.
	 * 
	 * @param array $days дни недели.
	 * 
	 * @return array
	 * 
	 */
	public function getDate($days)
	{
		$date = date("d-m-Y");
		$time = strtotime(date("H:i:s"));

		$days = unserialize($days);
		$current = $days[(date('N', strtotime($date)))];

		$start = strtotime($current['start']);
		$end = strtotime($current['end']);

		$result = [
			'date' => $date,
			'time' => $time,
			'days' => $days,
			'current' => $current,
			'start' => $start,
			'end' => $end
		];

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
		return DB::buildQuery('INSERT INTO `'. PREFIX . $this->pluginName .'_session` SET ', $data);
	}

	/**
	 * 
	 * Обновляет запись.
	 * 
	 * @param int $id идентификатор сессии.
	 * @param array $data массив данных для обновления. 
	 * 
	 */
	public function update($id, $data)
	{
		$sql = 'UPDATE `'. PREFIX . $this->pluginName .'_session` 
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

		if ($args['user_id']) {
			$filter = '`user_id` = '. DB::quoteInt($args['user_id']);
		}

		$sql = 'DELETE FROM `'. PREFIX . $this->pluginName .'_session` 
				WHERE '. $filter;

		return DB::query($sql);
    }
}