var p4push = (function() {
  
  var pluginName = "p4-push";
  
  return { 
    pluginName: pluginName,
    
    init: function() {
      
      // Показать форму отправки сообщения
      $(document).on('click', '.section-'+pluginName+' .send-message', function() {
        p4push.showSection('.message-form');
      });
      
      // Показать настройки
      $(document).on('click', '.section-'+pluginName+' .show-settings', function() {
        p4push.showSection('.settings-form');
      });
      
      // Показать логи
      $(document).on('click', '.section-'+pluginName+' .show-logs', function() {
        p4push.showSection('.logs-container');
        p4push.loadLogs();
      });
      
      // Показать отладку
      $(document).on('click', '.section-'+pluginName+' .show-debug', function() {
        p4push.showSection('.debug-container');
        p4push.loadDebugLogs();
      });
      
      // Загрузить логи отладки
      $(document).on('click', '.section-'+pluginName+' .load-debug-logs', function() {
        p4push.loadDebugLogs();
      });
      
      // Загрузить логи сообщений
      $(document).on('click', '.section-'+pluginName+' .load-logs', function() {
        p4push.loadLogs();
      });
      
      // Отправка сообщения
      $(document).on('click', '.section-'+pluginName+' .send-firebase-message', function() {
        p4push.sendMessage();
      });
      
      // Сохранение настроек
      $(document).on('click', '.section-'+pluginName+' .save-firebase-settings', function() {
        p4push.saveSettings();
      });
      
      // Тестирование соединения
      $(document).on('click', '.section-'+pluginName+' .test-connection', function() {
        p4push.testConnection();
      });
      
      // Очистка логов
      $(document).on('click', '.section-'+pluginName+' .clear-logs', function() {
        p4push.clearLogs();
      });

      // Проверка API ключа (модальное окно)
      $(document).on('click', '.section-'+pluginName+' .check-api-key', function() {
        p4push.checkApiKey();
      });

      // Закрытие модального окна
      $(document).on('click', '.p4-push-modal-close, .p4-push-modal-close-btn, .p4-push-modal-overlay', function(e) {
        if (e.target === this) {
          $('.p4-push-modal-overlay').fadeOut();
        }
      });
    },
    
    /**
     * Показать определенную секцию
     */
    showSection: function(sectionClass) {
      $('.section-'+this.pluginName+' .widget-panel-content').hide();
      $(sectionClass).show();
    },
    
    /**
     * Сохранение настроек Firebase
     */
    saveSettings: function() {
      var formData = {
        api_key: $('.section-'+this.pluginName+' input[name="api_key"]').val(),
        project_id: $('.section-'+this.pluginName+' input[name="project_id"]').val(),
        database_url: $('.section-'+this.pluginName+' input[name="database_url"]').val(),
        default_topic: $('.section-'+this.pluginName+' input[name="default_topic"]').val()
      };
      
      console.log('[p4-push] Saving settings for project:', formData.project_id);
      
      // Базовая валидация
      if (!formData.api_key || formData.api_key.trim() === '') {
        admin.indication('error', 'Введите API ключ Firebase');
        return;
      }
      
      if (!formData.project_id || formData.project_id.trim() === '') {
        admin.indication('error', 'Введите ID проекта Firebase');
        return;
      }
      
      var saveButton = $('.section-'+this.pluginName+' .save-firebase-settings');
      var originalText = saveButton.html();
      saveButton.html('<i class="fa fa-spinner fa-spin"></i> Сохранение...');
      
      admin.ajaxRequest({
        mguniqueurl: "action/saveFirebaseSettings",
        pluginHandler: this.pluginName,
        data: formData
      }, function(response) {
        console.log('[p4-push] Save settings response:', response);
        saveButton.html(originalText);
        if (response.status !== 'success') {
          console.error('[p4-push] Save failed:', response.msg);
        }
        admin.indication(response.status, response.msg);
      });
    },
    
    /**
     * Проверка API ключа с детальным отчетом
     */
    checkApiKey: function() {
      var apiKey = $('.section-'+this.pluginName+' input[name="api_key"]').val();
      var projectId = $('.section-'+this.pluginName+' input[name="project_id"]').val();
      
      if (!apiKey || !projectId) {
        admin.indication('error', 'Заполните API ключ и ID проекта');
        return;
      }
      
      // Открываем модальное окно
      $('.p4-push-modal-overlay').fadeIn();
      $('.p4-push-modal .diagnostic-loading').show();
      $('.p4-push-modal .diagnostic-details').hide();
      $('.p4-push-modal .diagnostic-status').html('');
      
      var formData = {
        api_key: apiKey,
        project_id: projectId
      };
      
      admin.ajaxRequest({
        mguniqueurl: "action/testFirebaseConnection",
        pluginHandler: this.pluginName,
        data: formData
      }, function(response) {
        $('.p4-push-modal .diagnostic-loading').hide();
        $('.p4-push-modal .diagnostic-details').show();
        
        var data = response.data || {};
        var success = data.success || (response.status === 'success');
        
        // Статус
        var statusHtml = success 
          ? '<div class="info-block" style="border-color: #4caf50; background: #e8f5e9;"><strong><i class="fa fa-check-circle"></i> Успех!</strong> ' + (data.message || response.msg) + '</div>'
          : '<div class="info-block" style="border-color: #f44336; background: #ffebee;"><strong><i class="fa fa-exclamation-circle"></i> Ошибка!</strong> ' + (data.error || response.msg) + '</div>';
        $('.p4-push-modal .diagnostic-status').html(statusHtml);
        
        // Детали
        $('.p4-push-modal .http-code').text(data.http_code || 'N/A');
        $('.p4-push-modal .result-status').html(success ? '<span class="status-badge status-success">УСПЕХ</span>' : '<span class="status-badge status-error">ОШИБКА</span>');
        
        // Ответ сервера
        var responseText = data.response || 'Нет ответа от сервера';
        try {
            // Пытаемся распарсить JSON для красивого вывода
            var json = JSON.parse(responseText);
            $('.p4-push-modal .server-response').text(JSON.stringify(json, null, 2));
        } catch (e) {
            $('.p4-push-modal .server-response').text(responseText);
        }
        
        // Рекомендации (только для успеха)
        if (success) {
          $('.p4-push-modal .recommendation-text').text('Соединение настроено правильно. Вы можете отправлять push-уведомления.');
        } else {
          $('.p4-push-modal .recommendation-text').text(data.error || response.msg || 'Ошибка соединения');
        }
      });
    },

    /**
     * Тестирование соединения
     */
    testConnection: function() {
      var formData = {
        api_key: $('.section-'+this.pluginName+' input[name="api_key"]').val(),
        project_id: $('.section-'+this.pluginName+' input[name="project_id"]').val()
      };
      
      if (!formData.api_key || !formData.project_id) {
        admin.indication('error', 'Заполните API ключ и ID проекта для тестирования');
        return;
      }
      
      console.log('[p4-push] Testing Firebase connection for project:', formData.project_id);
      
      var testButton = $('.section-'+this.pluginName+' .test-connection');
      var originalText = testButton.html();
      testButton.html('<i class="fa fa-spinner fa-spin"></i> Тестирование...');
      
      admin.ajaxRequest({
        mguniqueurl: "action/testFirebaseConnection",
        pluginHandler: this.pluginName,
        data: formData
      }, function(response) {
        console.log('[p4-push] Test connection response:', response);
        testButton.html(originalText);
        if (response.status !== 'success') {
          console.error('[p4-push] Connection test failed:', response.msg);
        }
        admin.indication(response.status, response.msg);
      });
    },
    
    /**
     * Отправка сообщения в Firebase
     */
    sendMessage: function() {
      var formData = {
        title: $('.section-'+this.pluginName+' input[name="title"]').val(),
        message: $('.section-'+this.pluginName+' textarea[name="message"]').val(),
        topic: $('.section-'+this.pluginName+' input[name="topic"]').val(),
        icon: $('.section-'+this.pluginName+' input[name="icon"]').val(),
        data: $('.section-'+this.pluginName+' textarea[name="data"]').val()
      };
      
      console.log('[p4-push] Sending message to topic:', formData.topic || 'default');
      
      // Валидация
      if (!formData.title || formData.title.trim() === '') {
        admin.indication('error', 'Заполните заголовок сообщения');
        return;
      }
      
      if (!formData.message || formData.message.trim() === '') {
        admin.indication('error', 'Заполните текст сообщения');
        return;
      }
      
      var sendButton = $('.section-'+this.pluginName+' .send-firebase-message');
      var originalText = sendButton.html();
      sendButton.html('<i class="fa fa-spinner fa-spin"></i> Отправка...');
      
      admin.ajaxRequest({
        mguniqueurl: "action/sendFirebaseMessage",
        pluginHandler: this.pluginName,
        data: formData
      }, function(response) {
        console.log('[p4-push] Send message response:', response);
        sendButton.html(originalText);
        if (response.status !== 'success') {
          console.error('[p4-push] Send message failed:', response.msg);
        }
        admin.indication(response.status, response.msg);
        if (response.status == "success") {
          // Очистка формы
          $('.section-'+pluginName+' .message-form')[0].reset();
        }
      });
    },
    
    /**
     * Загрузка логов сообщений
     */
    loadLogs: function() {
      var logsContainer = $('.section-'+this.pluginName+' .message-logs');
      logsContainer.html('<p><i class="fa fa-spinner fa-spin"></i> Загрузка логов...</p>');
      
      admin.ajaxRequest({
        mguniqueurl: "action/getMessageLogs",
        pluginHandler: this.pluginName
      }, function(response) {
        if (response.status == "success") {
          console.log('[p4-push] Loaded', response.data ? response.data.length : 0, 'log entries');
          p4push.displayLogs(response.data);
        } else {
          console.error('[p4-push] Failed to load logs:', response.msg);
          logsContainer.html('<p>Ошибка загрузки логов: ' + (response.msg || 'Неизвестная ошибка') + '</p>');
        }
      });
    },
    
    /**
     * Отображение логов
     */
    displayLogs: function(logs) {
      var html = '';
      
      if (!logs || logs.length === 0) {
        html = '<p>Нет отправленных сообщений</p>';
      } else {
        html = '<div class="table-wrapper">' +
               '<table class="widget-table main-table"><thead><tr>' +
               '<th>Дата</th>' +
               '<th>Заголовок</th>' +
               '<th>Сообщение</th>' +
               '<th>Статус</th>' +
               '<th>Пользователь</th>' +
               '</tr></thead><tbody>';
        
        logs.forEach(function(log) {
          var statusBadge = log.status === 'success' ? 
            '<span class="badge success">Успех</span>' : 
            '<span class="badge error">Ошибка</span>';
            
          var messagePreview = log.message && log.message.length > 100 ? 
            log.message.substring(0, 100) + '...' : 
            (log.message || '');
            
          html += '<tr>' +
                  '<td>' + (log.date || '') + '</td>' +
                  '<td>' + (log.title || '') + '</td>' +
                  '<td>' + messagePreview + '</td>' +
                  '<td>' + statusBadge + '</td>' +
                  '<td>' + (log.user || '') + '</td>' +
                  '</tr>';
        });
        
        html += '</tbody></table></div>' +
                '<div style="margin-top: 10px;">' +
                '<a role="button" href="javascript:void(0);" class="clear-logs button warning">' +
                '<i class="fa fa-trash"></i> Очистить логи</a>' +
                '</div>';
      }
      
      $('.section-'+this.pluginName+' .message-logs').html(html);
    },
    
    /**
     * Загрузка логов отладки
     */
    loadDebugLogs: function() {
      var debugContainer = $('.section-'+this.pluginName+' .debug-logs');
      debugContainer.html('<p><i class="fa fa-spinner fa-spin"></i> Загрузка логов отладки...</p>');
      
      admin.ajaxRequest({
        mguniqueurl: "action/getDebugLogs",
        pluginHandler: this.pluginName
      }, function(response) {
        if (response.status == "success") {
          console.log('[p4-push] Loaded', response.data ? response.data.length : 0, 'debug log entries');
          p4push.displayDebugLogs(response.data);
        } else {
          console.error('[p4-push] Failed to load debug logs:', response.msg);
          debugContainer.html('<p>Ошибка загрузки логов отладки: ' + (response.msg || 'Неизвестная ошибка') + '</p>');
        }
      });
    },
    
    /**
     * Отображение логов отладки
     */
    displayDebugLogs: function(logs) {
      var html = '<h4>Логи отладки:</h4>';
      
      if (!logs || logs.length === 0) {
        html += '<p>Логов отладки нет</p>';
      } else {
        logs.forEach(function(log) {
          html += '<div class="debug-entry" style="border: 1px solid #ddd; margin: 10px 0; padding: 10px; background: #f9f9f9;">' +
                  '<div><strong>' + (log.timestamp || '') + ':</strong> ' + (log.message || '') + '</div>';
          
          if (log.data) {
            html += '<div style="margin-top: 5px;"><strong>Данные:</strong>' +
                    '<pre style="background: #fff; padding: 5px; border: 1px solid #ccc; max-height: 200px; overflow: auto;">' + 
                    JSON.stringify(log.data, null, 2) + '</pre></div>';
          }
          
          html += '</div>';
        });
        
        html += '<div style="margin-top: 10px;">' +
                '<a role="button" href="javascript:void(0);" class="clear-logs button warning">' +
                '<i class="fa fa-trash"></i> Очистить логи отладки</a>' +
                '</div>';
      }
      
      $('.section-'+this.pluginName+' .debug-logs').html(html);
    },
    
    /**
     * Очистка логов
     */
    clearLogs: function() {
      if (!confirm('Вы уверены, что хотите очистить все логи?')) {
        return;
      }
      
      console.log('[p4-push] Clearing logs');
      admin.ajaxRequest({
        mguniqueurl: "action/clearLogs",
        pluginHandler: this.pluginName
      }, function(response) {
        admin.indication(response.status, response.msg);
        if (response.status == "success") {
          // Обновляем отображение логов
          $('.section-'+pluginName+' .message-logs').html('<p>Логи очищены</p>');
          $('.section-'+pluginName+' .debug-logs').html('<p>Логи очищены</p>');
        }
      });
    }
  };
})();

// Инициализация
$(document).ready(function() {
  console.log('[p4-push] Initializing plugin');
  p4push.init();
  
  // Показываем настройки по умолчанию при загрузке
  $('.section-p4_push .settings-form').show();
});