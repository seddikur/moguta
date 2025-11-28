<div class="section-<?php echo $pluginName ?>">
  <div class="widget-body">
    
    <div class="widget-panel">
      <a role="button" href="javascript:void(0);" class="send-message button success">
        <span><i class="fa fa-paper-plane"></i> Отправить сообщение</span>
      </a>
      <a role="button" href="javascript:void(0);" class="show-settings button info">
        <span><i class="fa fa-cogs"></i> Настройки</span>
      </a>
      <a role="button" href="javascript:void(0);" class="show-logs button">
        <span><i class="fa fa-history"></i> История сообщений</span>
      </a>
      <a role="button" href="javascript:void(0);" class="show-debug button warning">
        <span><i class="fa fa-bug"></i> Отладка</span>
      </a>
    </div>

    <!-- Форма отправки сообщения -->
    <div class="message-form widget-panel-content" style="display: none;">
      <h3>Отправка нового сообщения</h3>
      <form class="message-form" name="message-form">
        <ul class="list-option">
          <li>
            <label>
              <span class="custom-text">Заголовок сообщения: *</span>
              <input type="text" name="title" placeholder="Введите заголовок сообщения" required>
            </label>
          </li>
          <li>
            <label>
              <span class="custom-text">Текст сообщения: *</span>
              <textarea name="message" rows="4" placeholder="Введите текст сообщения" required></textarea>
            </label>
          </li>
          <li>
            <label>
              <span class="custom-text">Топик:</span>
              <input type="text" name="topic" value="<?php echo htmlspecialchars($options['default_topic']); ?>" placeholder="notifications">
              <small>Топик Firebase для отправки (по умолчанию: <?php echo htmlspecialchars($options['default_topic']); ?>)</small>
            </label>
          </li>
          <li>
            <label>
              <span class="custom-text">URL иконки:</span>
              <input type="text" name="icon" placeholder="/images/icon.png">
              <small>URL иконки для уведомления (опционально)</small>
            </label>
          </li>
          <li>
            <label>
              <span class="custom-text">Дополнительные данные:</span>
              <textarea name="data" rows="3" placeholder='{"key": "value"}'></textarea>
              <small>Дополнительные данные в формате JSON (опционально)</small>
            </label>
          </li>
        </ul>
        <div class="form-actions">
          <a role="button" href="javascript:void(0);" class="send-firebase-message button success">
            <i class="fa fa-paper-plane"></i> Отправить сообщение
          </a>
          <button type="reset" class="button secondary">
            <i class="fa fa-undo"></i> Очистить форму
          </button>
        </div>
      </form>
    </div>

    <!-- Настройки Firebase -->
    <div class="settings-form widget-panel-content">
      <h3>Настройки Firebase</h3>
      <form class="firebase-settings" name="firebase-settings">
        <ul class="list-option">
          <li>
            <label>
              <span class="custom-text">API ключ Firebase: *</span>
              <div style="display: flex; align-items: center;">
                  <input type="text" name="api_key" value="<?php echo htmlspecialchars($options['api_key']); ?>" required 
                         placeholder="Введите Server Key из Firebase Console"
                         style="font-family: monospace; font-size: 12px; flex-grow: 1;">
                  <a href="javascript:void(0);" class="check-api-key button" title="Проверить API ключ и получить детальный отчет" style="margin-left: 10px; white-space: nowrap;">
                    <i class="fa fa-key"></i> Проверить ключ
                  </a>
              </div>
              <small>Server Key из Firebase Console (Проект → Настройки → Cloud Messaging)</small>
            </label>
          </li>
          <li>
            <label>
              <span class="custom-text">ID проекта: *</span>
              <input type="text" name="project_id" value="<?php echo htmlspecialchars($options['project_id']); ?>" required
                     placeholder="mircvetov-41088">
              <small>ID вашего Firebase проекта</small>
            </label>
          </li>
          <li>
            <label>
              <span class="custom-text">URL базы данных:</span>
              <input type="text" name="database_url" value="<?php echo htmlspecialchars($options['database_url']); ?>" 
                     placeholder="https://mircvetov-41088.firebaseio.com">
              <small>URL базы данных Firebase (опционально)</small>
            </label>
          </li>
          <li>
            <label>
              <span class="custom-text">Топик по умолчанию:</span>
              <input type="text" name="default_topic" value="<?php echo htmlspecialchars($options['default_topic']); ?>"
                     placeholder="notifications">
              <small>Топик по умолчанию для отправки сообщений</small>
            </label>
          </li>
        </ul>
        <div class="form-actions">
          <a role="button" href="javascript:void(0);" class="save-firebase-settings button success">
            <i class="fa fa-save"></i> Сохранить настройки
          </a>
          <a role="button" href="javascript:void(0);" class="test-connection button">
            <i class="fa fa-plug"></i> Тестировать соединение
          </a>
        </div>
      </form>
      
      <?php if (!empty($options['api_key']) && !empty($options['project_id'])): ?>
      <div class="settings-status" style="margin-top: 20px; padding: 15px; background: #f8f8f8; border-radius: 4px;">
        <h4>Текущие настройки:</h4>
        <ul>
          <li><strong>Project ID:</strong> <?php echo htmlspecialchars($options['project_id']); ?></li>
          <li><strong>API Key:</strong> <?php echo substr(htmlspecialchars($options['api_key']), 0, 20); ?>...</li>
          <li><strong>Топик по умолчанию:</strong> <?php echo htmlspecialchars($options['default_topic']); ?></li>
          <?php if (!empty($options['database_url'])): ?>
          <li><strong>Database URL:</strong> <?php echo htmlspecialchars($options['database_url']); ?></li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <!-- Логи сообщений -->
    <div class="logs-container widget-panel-content" style="display: none;">
      <h3>История отправленных сообщений</h3>
      <div class="logs-header" style="margin-bottom: 15px;">
        <p>Последние 50 отправленных сообщений</p>
      </div>
      <div class="message-logs">
        <p><i class="fa fa-info-circle"></i> Нажмите кнопку "Загрузить логи" для просмотра истории сообщений</p>
      </div>
      <div class="logs-actions" style="margin-top: 15px;">
        <a role="button" href="javascript:void(0);" class="load-logs button">
          <i class="fa fa-refresh"></i> Загрузить логи
        </a>
        <a role="button" href="javascript:void(0);" class="clear-logs button warning">
          <i class="fa fa-trash"></i> Очистить логи
        </a>
      </div>
    </div>

    <!-- Отладочная информация -->
    <div class="debug-container widget-panel-content" style="display: none;">
      <h3>Отладочная информация</h3>
      
      <div class="debug-current-settings" style="margin-bottom: 20px;">
        <h4>Текущие настройки плагина:</h4>
        <div style="background: #f8f8f8; padding: 15px; border-radius: 4px;">
          <pre style="margin: 0; font-size: 12px; max-height: 200px; overflow: auto;"><?php 
            echo htmlspecialchars(print_r($options, true)); 
          ?></pre>
        </div>
      </div>

      <div class="debug-actions" style="margin-bottom: 20px;">
        <div class="button-group">
          <a role="button" href="javascript:void(0);" class="load-debug-logs button">
            <i class="fa fa-refresh"></i> Загрузить логи отладки
          </a>
          <a role="button" href="javascript:void(0);" class="clear-logs button warning">
            <i class="fa fa-trash"></i> Очистить все логи
          </a>
        </div>
      </div>

      <div class="debug-info">
        <h4>Информация о системе:</h4>
        <ul>
          <li><strong>Версия PHP:</strong> <?php echo phpversion(); ?></li>
          <li><strong>Расширение cURL:</strong> <?php echo extension_loaded('curl') ? 'Доступно' : 'Не доступно'; ?></li>
          <li><strong>Лимит времени выполнения:</strong> <?php echo ini_get('max_execution_time'); ?> сек</li>
        </ul>
      </div>

      <div class="debug-logs" style="margin-top: 20px;">
        <p><i class="fa fa-info-circle"></i> Логи отладки будут загружены после нажатия кнопки "Загрузить логи отладки"</p>
      </div>
    </div>

  </div>
</div>

<!-- Модальное окно диагностики -->
<div class="p4-push-modal-overlay" style="display: none;">
  <div class="p4-push-modal">
    <div class="p4-push-modal-header">
      <h3><i class="fa fa-stethoscope"></i> Диагностика API ключа</h3>
      <button class="p4-push-modal-close">&times;</button>
    </div>
    <div class="p4-push-modal-body">
      <div class="diagnostic-status"></div>
      
      <div class="diagnostic-details" style="display: none;">
        <h4>Детали запроса:</h4>
        <table class="widget-table">
          <tr>
            <td width="150"><strong>HTTP Код:</strong></td>
            <td class="http-code"></td>
          </tr>
          <tr>
            <td><strong>Результат:</strong></td>
            <td class="result-status"></td>
          </tr>
        </table>

        <h4>Ответ сервера Firebase:</h4>
        <pre class="server-response"></pre>

        <div class="diagnostic-recommendation info-block">
          <strong><i class="fa fa-lightbulb-o"></i> Рекомендация:</strong>
          <p class="recommendation-text"></p>
        </div>
      </div>
      
      <div class="diagnostic-loading" style="text-align: center; padding: 20px;">
        <i class="fa fa-spinner fa-spin fa-3x"></i>
        <p>Проверка соединения с Firebase...</p>
      </div>
    </div>
    <div class="p4-push-modal-footer">
      <button class="button secondary p4-push-modal-close-btn">Закрыть</button>
    </div>
  </div>
</div>

<style>
.p4-push-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
}

.p4-push-modal {
  background: #fff;
  width: 700px;
  max-width: 90%;
  max-height: 90vh;
  border-radius: 5px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  display: flex;
  flex-direction: column;
}

.p4-push-modal-header {
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.p4-push-modal-header h3 {
  margin: 0;
  font-size: 18px;
}

.p4-push-modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
}

.p4-push-modal-close:hover {
  color: #333;
}

.p4-push-modal-body {
  padding: 20px;
  overflow-y: auto;
}

.p4-push-modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #eee;
  text-align: right;
}

.server-response {
  background: #f5f5f5;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 3px;
  max-height: 200px;
  overflow: auto;
  white-space: pre-wrap;
  font-size: 12px;
  font-family: monospace;
}

.info-block {
  background: #e3f2fd;
  border-left: 4px solid #2196f3;
  padding: 10px 15px;
  margin-top: 15px;
}

.status-badge {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 4px;
  font-weight: bold;
  color: #fff;
}

.status-success {
  background: #4caf50;
}

.status-error {
  background: #f44336;
}

.section-<?php echo $pluginName ?> .widget-panel-content {
  margin-top: 20px;
  padding: 20px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  background: #fff;
}

.section-<?php echo $pluginName ?> .list-option {
  list-style: none;
  margin: 0;
  padding: 0;
}

.section-<?php echo $pluginName ?> .list-option li {
  margin-bottom: 15px;
  padding: 10px;
  border-bottom: 1px solid #f0f0f0;
}

.section-<?php echo $pluginName ?> .list-option li:last-child {
  border-bottom: none;
}

.section-<?php echo $pluginName ?> .custom-text {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
  color: #333;
}

.section-<?php echo $pluginName ?> input[type="text"],
.section-<?php echo $pluginName ?> textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.section-<?php echo $pluginName ?> input[type="text"]:focus,
.section-<?php echo $pluginName ?> textarea:focus {
  border-color: #007cba;
  outline: none;
  box-shadow: 0 0 0 1px #007cba;
}

.section-<?php echo $pluginName ?> .form-actions {
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px solid #e0e0e0;
}

.section-<?php echo $pluginName ?> .button-group {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.section-<?php echo $pluginName ?> .badge.success {
  background: #4CAF50;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
}

.section-<?php echo $pluginName ?> .badge.error {
  background: #f44336;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
}

.section-<?php echo $pluginName ?> .debug-entry {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  margin-bottom: 10px;
  padding: 15px;
}

.section-<?php echo $pluginName ?> .debug-entry pre {
  background: #f8f8f8;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 10px;
  margin: 5px 0 0 0;
  font-size: 12px;
  line-height: 1.4;
  max-height: 300px;
  overflow: auto;
}

.section-<?php echo $pluginName ?> small {
  color: #666;
  font-size: 12px;
  display: block;
  margin-top: 4px;
}

.section-<?php echo $pluginName ?> .table-wrapper {
  overflow-x: auto;
}

.section-<?php echo $pluginName ?> .widget-table {
  width: 100%;
  border-collapse: collapse;
}

.section-<?php echo $pluginName ?> .widget-table th {
  background: #f5f5f5;
  padding: 10px;
  text-align: left;
  border-bottom: 2px solid #e0e0e0;
  font-weight: bold;
}

.section-<?php echo $pluginName ?> .widget-table td {
  padding: 10px;
  border-bottom: 1px solid #e0e0e0;
}

.section-<?php echo $pluginName ?> .widget-table tr:hover {
  background: #f9f9f9;
}

.section-<?php echo $pluginName ?> .settings-status {
  background: #e8f5e8 !important;
  border: 1px solid #4CAF50;
}

.section-<?php echo $pluginName ?> .settings-status h4 {
  margin: 0 0 10px 0;
  color: #2E7D32;
}

.section-<?php echo $pluginName ?> .settings-status ul {
  margin: 0;
  padding: 0;
  list-style: none;
}

.section-<?php echo $pluginName ?> .settings-status li {
  padding: 5px 0;
  border-bottom: 1px solid #c8e6c9;
}

.section-<?php echo $pluginName ?> .settings-status li:last-child {
  border-bottom: none;
}
</style>


<script>
// Автоматически показываем настройки при загрузке страницы
$(document).ready(function() {
  $('.section-<?php echo $pluginName ?> .settings-form').show();
});

// Обработчик для кнопки загрузки логов сообщений
$('.admin-center').on('click', '.section-<?php echo $pluginName ?> .load-logs', function() {
  p4push.loadLogs();
});
</script>