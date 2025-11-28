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

	$apiKey = trim($request['api_key']);
	
	// Проверяем формат API ключа
	if (strlen($apiKey) < 10) {
		$this->messageError = 'API ключ слишком короткий (минимум 10 символов). Server Key обычно длиннее 100 символов';
		return false;
	}

	// Проверяем, что это похоже на Server Key (обычно начинается с букв и содержит подчеркивания)
	if (!preg_match('/^[A-Za-z0-9_-]+$/', $apiKey)) {
		$this->messageError = 'API ключ содержит недопустимые символы. Используйте только буквы, цифры, дефисы и подчеркивания';
		return false;
	}
	
	// Server Key обычно длиннее 100 символов
	if (strlen($apiKey) < 50) {
		$this->messageError = 'API ключ слишком короткий. Server Key из Firebase обычно длиннее 100 символов. Убедитесь, что используете Server Key, а не Web API Key';
		return false;
	}

	// Тест соединения с Firebase
	$result = $this->sendTestToFirebase($apiKey);
	
	// Сохраняем детальный результат для модального окна диагностики
	$this->data = $result;
	
	if ($result['success']) {
	  $this->messageSucces = '✅ Соединение с Firebase установлено успешно!';
	  return true;
	} else {
	  $errorMsg = $result['error'] ?? 'Неизвестная ошибка';
	  $this->messageError = '❌ Ошибка соединения с Firebase: ' . $errorMsg;
	  return false;
	}
  }

  /**
   * Отправка тестового сообщения в Firebase
   */
  private function sendTestToFirebase($apiKey) {
	// Проверяем наличие cURL
	if (!function_exists('curl_init')) {
		return array(
			'success' => false,
			'error' => 'Расширение cURL не установлено на сервере'
		);
	}

	// Тестовые данные - используем простой запрос для проверки авторизации
	// Пробуем отправить на несуществующий топик - это проверит валидность API ключа
	// даже если топик не существует, валидный ключ вернет success=0, failure=1, а не 404
	$testData = array(
	  'to' => '/topics/test_connection_' . time(),
	  'notification' => array(
		'title' => 'Test Connection',
		'body' => 'Testing Firebase connection'
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
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	$curlErrno = curl_errno($ch);
	curl_close($ch);
	
	// Логирование в debug.log
	$logFile = dirname(__FILE__) . '/debug.log';
	$logEntry = date('Y-m-d H:i:s') . " - Firebase test - HTTP Code: " . $httpCode . "\n";
	$logEntry .= date('Y-m-d H:i:s') . " - Firebase test - URL: " . $url . "\n";
	$logEntry .= date('Y-m-d H:i:s') . " - Firebase test - API Key length: " . strlen($apiKey) . "\n";
	$logEntry .= date('Y-m-d H:i:s') . " - Firebase test - API Key preview: " . substr($apiKey, 0, 20) . "...\n";
	$logEntry .= date('Y-m-d H:i:s') . " - Firebase test - Request data: " . json_encode($testData) . "\n";
	$logEntry .= date('Y-m-d H:i:s') . " - Firebase test - Response: " . $response . "\n";
	if ($curlError) {
		$logEntry .= date('Y-m-d H:i:s') . " - Firebase test - cURL Error: " . $curlError . " (Code: " . $curlErrno . ")\n";
	}
	file_put_contents($logFile, $logEntry, FILE_APPEND);
	
	// Также логируем в error_log
	error_log("Firebase test - HTTP Code: " . $httpCode);
	error_log("Firebase test - Response: " . $response);
	if ($curlError) {
		error_log("Firebase test - cURL Error: " . $curlError . " (Code: " . $curlErrno . ")");
	}
	
	// Обработка ошибок cURL
	if ($curlErrno !== 0) {
		$errorMessages = array(
			CURLE_COULDNT_CONNECT => 'Не удалось подключиться к серверу Firebase',
			CURLE_OPERATION_TIMEOUTED => 'Превышено время ожидания ответа от Firebase',
			CURLE_SSL_CONNECT_ERROR => 'Ошибка SSL соединения с Firebase'
		);
		return array(
			'success' => false,
			'error' => $errorMessages[$curlErrno] ?? 'Ошибка cURL: ' . $curlError
		);
	}
	
	// Проверка HTTP кода
	if ($httpCode !== 200) {
		$errorMessages = array(
			400 => 'Неверный формат запроса к Firebase',
			401 => 'Неверный API ключ',
			403 => 'Доступ запрещен',
			404 => 'API endpoint не найден',
			500 => 'Внутренняя ошибка сервера Firebase',
			503 => 'Сервис Firebase временно недоступен'
		);
		
		$errorMsg = $errorMessages[$httpCode] ?? 'Ошибка HTTP ' . $httpCode;
		
		// Пытаемся извлечь детали из ответа
		if ($response) {
			$responseData = json_decode($response, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				if (isset($responseData['error'])) {
					$errorMsg .= ': ' . $responseData['error'];
				}
				if (isset($responseData['message'])) {
					$errorMsg .= ' (' . $responseData['message'] . ')';
				}
			}
		}
		
		return array(
			'success' => false,
			'error' => $errorMsg,
			'http_code' => $httpCode,
			'response' => $response
		);
	}
	
	// Парсим ответ Firebase
	if ($response) {
		$responseData = json_decode($response, true);
		
		if (json_last_error() !== JSON_ERROR_NONE) {
			return array(
				'success' => false,
				'error' => 'Не удалось разобрать ответ от Firebase'
			);
		}
		
		// Проверяем наличие ошибок в ответе (даже при HTTP 200)
		if (isset($responseData['error'])) {
			$errorCode = $responseData['error'];
			$errorMessages = array(
				'InvalidRegistration' => 'Неверный формат регистрации',
				'NotRegistered' => 'Устройство не зарегистрировано',
				'MismatchSenderId' => 'Неверный Sender ID',
				'Unauthorized' => 'Неверный API ключ. Проверьте Server Key в Firebase Console',
				'InvalidApiKey' => 'Неверный API ключ',
				'MessageTooBig' => 'Сообщение слишком большое'
			);
			
			return array(
				'success' => false,
				'error' => $errorMessages[$errorCode] ?? 'Ошибка Firebase: ' . $errorCode
			);
		}
		
		// Проверяем успешность отправки
		if (isset($responseData['success']) && $responseData['success'] == 1) {
			return array(
				'success' => true,
				'message' => 'Соединение установлено успешно'
			);
		}
		
		// Если есть failure, но не было error - это странно, но считаем успехом для теста
		if (isset($responseData['failure']) && $responseData['failure'] == 0) {
			return array(
				'success' => true,
				'message' => 'Соединение установлено (топик может не существовать, но API ключ валиден)'
			);
		}
	}
	
	// Если дошли сюда и HTTP 200 - считаем успехом
	return array(
		'success' => true,
		'message' => 'Соединение установлено'
	);
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
	  $this->messageError = 'Ошибка отправки сообщения. Проверьте настройки Firebase и логи отладки';
	  return false;
	}
  }
  
  /**
   * Отправка запроса к Firebase
   */
  private function sendToFirebase($data, $apiKey) {
	if (!function_exists('curl_init')) {
		error_log("Firebase send - cURL not available");
		return false;
	}

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
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);
	
	if ($curlError) {
		error_log("Firebase send - cURL Error: " . $curlError);
		return false;
	}
	
	if ($httpCode !== 200) {
		error_log("Firebase send - HTTP Code: " . $httpCode . ", Response: " . $response);
		return false;
	}
	
	// Проверяем ответ на наличие ошибок
	if ($response) {
		$responseData = json_decode($response, true);
		if (isset($responseData['error'])) {
			error_log("Firebase send - Error in response: " . $responseData['error']);
			return false;
		}
	}
	
	return true;
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
   * Получение логов отладки
   */
  public function getDebugLogs() {
	if (USER::access('plugin') < 1) {
	  $this->messageError = 'Недостаточно прав';
	  return false;
	}
	
	// Читаем файл debug.log если он существует
	$logFile = dirname(__FILE__) . '/debug.log';
	$logs = array();
	
	if (file_exists($logFile) && is_readable($logFile)) {
		$content = file_get_contents($logFile);
		$lines = explode("\n", $content);
		
		// Берем последние 50 строк
		$lines = array_slice($lines, -50);
		
		foreach ($lines as $line) {
			$line = trim($line);
			if (!empty($line)) {
				$logs[] = array(
					'timestamp' => date('Y-m-d H:i:s'),
					'message' => $line
				);
			}
		}
	}
	
	// Добавляем системную информацию
	$logs[] = array(
		'timestamp' => date('Y-m-d H:i:s'),
		'message' => 'PHP Version: ' . phpversion(),
		'data' => array(
			'curl_available' => function_exists('curl_init'),
			'max_execution_time' => ini_get('max_execution_time'),
			'memory_limit' => ini_get('memory_limit')
		)
	);
	
	$this->data = $logs;
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