<?php

class Pactioner extends Actioner {

  private $pluginName = 'p4-push';
  
  /**
   * Сохранение настроек Firebase
   */
  public function saveFirebaseSettings() {
	error_log("=== FIREBASE SAVE SETTINGS ===");
	
	if (USER::access('plugin') < 2) {
	  $this->messageError = 'Недостаточно прав';
	  return false;
	}

	$this->messageSucces = 'Настройки сохранены';
	$this->messageError = 'Ошибка сохранения';

	// Данные приходят в $_POST['data']
	$request = $_POST['data'] ?? array();
	
	error_log("Request data: " . print_r($request, true));

	if (empty($request['api_key'])) {
	  $this->messageError = 'API ключ обязателен для заполнения';
	  return false;
	}

	if (empty($request['project_id'])) {
	  $this->messageError = 'ID проекта обязателен для заполнения';
	  return false;
	}

	// Сохраняем настройки - УПРОЩЕННАЯ ВЕРСИЯ
	$settings = array(
	  "api_key" => $request['api_key'],
	  "project_id" => $request['project_id'],
	  "database_url" => $request['database_url'] ?? "",
	  "default_topic" => $request['default_topic'] ?? "notifications"
	);

	error_log("Saving settings: " . print_r($settings, true));

	// Пробуем разные варианты сохранения
	try {
		// Вариант 1: Стандартный способ
		$result = MG::setOption(array('option' => $this->pluginName.'-option', 'value' => addslashes(serialize($settings))));
		
		if (!$result) {
			// Вариант 2: Альтернативный способ
			$result = DB::query("
				INSERT INTO `".PREFIX."setting` (`name`, `value`) 
				VALUES ('".$this->pluginName."-option', '".addslashes(serialize($settings))."')
				ON DUPLICATE KEY UPDATE `value` = '".addslashes(serialize($settings))."'
			");
		}
		
		if ($result) {
			error_log("SETTINGS SAVED SUCCESSFULLY");
			$this->messageSucces = 'Настройки успешно сохранены!';
			return true;
		} else {
			error_log("SETTINGS SAVE FAILED");
			$this->messageError = 'Ошибка сохранения в базу данных';
			return false;
		}
	} catch (Exception $e) {
		error_log("SETTINGS SAVE EXCEPTION: " . $e->getMessage());
		$this->messageError = 'Ошибка: ' . $e->getMessage();
		return false;
	}
  }

  /**
   * Тестирование соединения с Firebase
   */
  public function testFirebaseConnection() {
	error_log("=== TEST CONNECTION ===");
	
	if (USER::access('plugin') < 1) {
	  $this->messageError = 'Недостаточно прав';
	  return false;
	}

	// Данные приходят в $_POST['data']
	$request = $_POST['data'] ?? $_POST;
	
	if (empty($request['api_key'])) {
	  $this->messageError = 'API ключ обязателен';
	  return false;
	}

	$apiKey = $request['api_key'];
	
	// Проверяем формат API ключа
	if (strlen($apiKey) < 10) {
		$this->messageError = 'API ключ слишком короткий';
		return false;
	}

	// Тест соединения с Firebase
	$result = $this->sendTestToFirebase($apiKey);
	
	if ($result) {
	  $this->messageSucces = '✅ Соединение с Firebase установлено успешно!';
	  return true;
	} else {
	  $this->messageError = '❌ Ошибка соединения с Firebase. Проверьте API ключ';
	  return false;
	}
  }

  /**
   * Отправка тестового сообщения в Firebase
   */
  private function sendTestToFirebase($apiKey) {
	// Тестовые данные
	$testData = array(
	  'to' => '/topics/test',
	  'notification' => array(
		'title' => 'Test Connection - Moguta CMS',
		'body' => 'Testing Firebase connection',
		'icon' => '/images/logo.png'
	  ),
	  'data' => array(
		'test' => 'true',
		'source' => 'moguta_cms'
	  )
	);

	$url = 'https://fcm.googleapis.com/fcm/send';
	
	$headers = array(
	  'Authorization: key=' . $apiKey,
	  'Content-Type: application/json'
	);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);
	
	error_log("Firebase test - HTTP Code: " . $httpCode);
	error_log("Firebase test - Response: " . $response);
	if ($error) {
		error_log("Firebase test - Error: " . $error);
	}
	
	// Для Firebase успешный ответ - 200
	return $httpCode == 200;
  }

  /**
   * Отправка сообщения в Firebase
   */
  public function sendFirebaseMessage() {
	error_log("=== SEND MESSAGE ===");
	
	if (USER::access('plugin') < 2) {
	  $this->messageError = 'Недостаточно прав';
	  return false;
	}

	$this->messageSucces = 'Сообщение успешно отправлено';
	$this->messageError = 'Ошибка отправки сообщения';

	// Данные приходят в $_POST['data']
	$request = $_POST['data'] ?? $_POST;
	
	// Получаем настройки плагина
	$options = $this->getOptions();
	
	// Валидация данных
	if (empty($request['title']) || empty($request['message'])) {
	  $this->messageError = 'Заполните заголовок и текст сообщения';
	  return false;
	}
	
	if (empty($options['api_key']) || empty($options['project_id'])) {
	  $this->messageError = 'Firebase не настроен. Проверьте настройки плагина';
	  return false;
	}

	// Подготовка данных для отправки
	$messageData = array(
	  'notification' => array(
		'title' => $request['title'],
		'body' => $request['message'],
		'icon' => !empty($request['icon']) ? $request['icon'] : '/images/logo.png'
	  ),
	  'to' => !empty($request['topic']) ? '/topics/'.$request['topic'] : '/topics/'.$options['default_topic']
	);

	// Добавляем дополнительные данные если есть
	if (!empty($request['data'])) {
	  $jsonData = json_decode($request['data'], true);
	  if ($jsonData !== null) {
		$messageData['data'] = $jsonData;
	  }
	}

	error_log("Sending message: " . print_r($messageData, true));

	// Отправка в Firebase
	$result = $this->sendToFirebase($messageData, $options['api_key']);
	
	if ($result) {
	  // Логируем отправку
	  $this->logMessage($request['title'], $request['message'], 'success');
	  return true;
	} else {
	  $this->logMessage($request['title'], $request['message'], 'error');
	  return false;
	}
  }
  
  /**
   * Отправка запроса к Firebase
   */
  private function sendToFirebase($data, $apiKey) {
	$url = 'https://fcm.googleapis.com/fcm/send';
	
	$headers = array(
	  'Authorization: key=' . $apiKey,
	  'Content-Type: application/json'
	);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	return $httpCode == 200;
  }

  /**
   * Получение логов сообщений
   */
  public function getMessageLogs() {
	if (USER::access('plugin') < 1) {
	  $this->messageError = 'Недостаточно прав';
	  return false;
	}
	
	$logs = MG::getSetting($this->pluginName.'-logs');
	if ($logs) {
		$this->data = unserialize(stripslashes($logs));
	} else {
		$this->data = array();
	}
	
	return true;
  }

  /**
   * Логирование отправленных сообщений
   */
  private function logMessage($title, $message, $status) {
	$logData = array(
	  'title' => $title,
	  'message' => $message,
	  'status' => $status,
	  'date' => date('Y-m-d H:i:s'),
	  'user' => USER::get('name')
	);
	
	$existingLogs = MG::getSetting($this->pluginName.'-logs');
	$logs = $existingLogs ? unserialize(stripslashes($existingLogs)) : array();
	
	array_unshift($logs, $logData);
	$logs = array_slice($logs, 0, 50);
	
	MG::setOption(array('option' => $this->pluginName.'-logs', 'value' => addslashes(serialize($logs))));
  }

  /**
   * Очистка логов
   */
  public function clearLogs() {
	if (USER::access('plugin') < 2) {
	  $this->messageError = 'Недостаточно прав';
	  return false;
	}

	MG::setOption(array('option' => $this->pluginName.'-logs', 'value' => ''));
	$this->messageSucces = 'Логи очищены';
	return true;
  }
  
  /**
   * Получение настроек плагина
   */
  private function getOptions() {
	$option = MG::getSetting($this->pluginName.'-option');
	if($option) {
		$option = stripslashes($option);
		return unserialize($option);
	}
	return array(
	  "api_key" => "",
	  "project_id" => "",
	  "database_url" => "",
	  "default_topic" => "notifications"
	);
  }
}