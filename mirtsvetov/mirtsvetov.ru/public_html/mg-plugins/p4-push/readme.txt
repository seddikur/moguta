Мобильные Push-Уведомления
==========================

Версия: 1.0.0
Автор: Avdeev Mark

Описание:
Плагин для отправки push-уведомлений в мобильное приложение через Firebase Cloud Messaging (FCM).

Особенности:
- Отправка уведомлений на iOS и Android устройства
- Простой интерфейс управления
- Статистика по зарегистрированным устройствам
- REST API для регистрации токенов из приложения

Установка:
1. Загрузите файлы плагина в папку /mg-plugins/mg-push-notifications/
2. Активируйте плагин в админке
3. Настройте Firebase в разделе плагина

Настройка Firebase:
1. Создайте проект в Firebase Console
2. Добавьте приложения iOS и Android
3. Загрузите Service Account credentials
4. Укажите Project ID, API Key и App ID

API для мобильного приложения:
URL: /plugins/mg-push-notifications/mobile-api.php
Метод: POST
Параметры:
  - action=register_token
  - token=TOKEN_DEVICE
  - platform=ios|android
  - app_version=1.0.0 (опционально)

Пример кода для iOS (Swift):
let token = "device_token_here"
let url = URL(string: "https://your-site.com/plugins/mg-push-notifications/mobile-api.php")!
var request = URLRequest(url: url)
request.httpMethod = "POST"
request.setValue("application/x-www-form-urlencoded", forHTTPHeaderField: "Content-Type")

let body = "action=register_token&token=\(token)&platform=ios&app_version=1.0.0"
request.httpBody = body.data(using: .utf8)

URLSession.shared.dataTask(with: request) { data, response, error in
	// Обработка ответа
}.resume()

Пример кода для Android (Kotlin):
val token = "device_token_here"
val url = "https://your-site.com/plugins/mg-push-notifications/mobile-api.php"
val params = "action=register_token&token=$token&platform=android&app_version=1.0.0"

val request = Request.Builder()
	.url(url)
	.post(params.toRequestBody("application/x-www-form-urlencoded".toMediaType()))
	.build()

OkHttpClient().newCall(request).enqueue(object : Callback {
	override fun onResponse(call: Call, response: Response) {
		// Обработка ответа
	}
	
	override fun onFailure(call: Call, e: IOException) {
		// Обработка ошибки
	}
})

Поддерживаемые платформы:
- iOS 10.0+
- Android 4.4+

Требования:
- Moguta.CMS 4.0+
- PHP 7.4+
- HTTPS для работы FCM