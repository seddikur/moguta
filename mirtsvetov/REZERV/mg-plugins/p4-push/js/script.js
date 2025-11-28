var p4push = (function() {
  
  var pluginName = "p4-push";
  
  return { 
    pluginName: pluginName,
    
    init: function() {      
      console.log('p4push init for: ' + pluginName);
      
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
    },
    
    /**
     * Показать определенную секцию
     */
    showSection: function(sectionClass) {
      console.log('Showing section: ' + sectionClass);
      $('.section-'+this.pluginName+' .widget-panel-content').hide();
      $(sectionClass).show();
    },
    
    /**
     * Сохранение настроек Firebase
     */
    saveSettings: function() {
      console.log('saveSettings called');
      
      var formData = {
        api_key: $('.section-'+this.pluginName+' input[name="api_key"]').val(),
        project_id: $('.section-'+this.pluginName+' input[name="project_id"]').val(),
        database_url: $('.section-'+this.pluginName+' input[name="database_url"]').val(),
        default_topic: $('.section-'+this.pluginName+' input[name="default_topic"]').val()
      };
      
      console.log('Saving settings:', formData);
      
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
        console.log('Save response:', response);
        saveButton.html(originalText);
        admin.indication(response.status, response.msg);
      });
    },
    
    /**
     * Тестирование соединения
     */
    testConnection: function() {
      console.log('testConnection called');
      
      var formData = {
        api_key: $('.section-'+this.pluginName+' input[name="api_key"]').val(),
        project_id: $('.section-'+this.pluginName+' input[name="project_id"]').val()
      };
      
      if (!formData.api_key || !formData.project_id) {
        admin.indication('error', 'Заполните API ключ и ID проекта для тестирования');
        return;
      }
      
      var testButton = $('.section-'+this.pluginName+' .test-connection');
      var originalText = testButton.html();
      testButton.html('<i class="fa fa-spinner fa-spin"></i> Тестирование...');
      
      admin.ajaxRequest({
        mguniqueurl: "action/testFirebaseConnection",
        pluginHandler: this.pluginName,
        data: formData
      }, function(response) {
        testButton.html(originalText);
        admin.indication(response.status, response.msg);
      });
    },
    
    /**
     * Отправка сообщения в Firebase
     */
    sendMessage: function() {
      console.log('sendMessage called');
      
      var formData = {
        title: $('.section-'+this.pluginName+' input[name="title"]').val(),
        message: $('.section-'+this.pluginName+' textarea[name="message"]').val(),
        topic: $('.section-'+this.pluginName+' input[name="topic"]').val(),
        icon: $('.section-'+this.pluginName+' input[name="icon"]').val(),
        data: $('.section-'+this.pluginName+' textarea[name="data"]').val()
      };
      
      console.log('Message data:', formData);
      
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
        sendButton.html(originalText);
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
      console.log('loadLogs called');
      
      var logsContainer = $('.section-'+this.pluginName+' .message-logs');
      logsContainer.html('<p><i class="fa fa-spinner fa-spin"></i> Загрузка логов...</p>');
      
      admin.ajaxRequest({
        mguniqueurl: "action/getMessageLogs",
        pluginHandler: this.pluginName
      }, function(response) {
        console.log('Logs response:', response);
        if (response.status == "success") {
          p4push.displayLogs(response.data);
        } else {
          logsContainer.html('<p>Ошибка загрузки логов: ' + (response.msg || 'Неизвестная ошибка') + '</p>');
        }
      });
    },
    
    /**
     * Отображение логов
     */
    displayLogs: function(logs) {
      console.log('displayLogs called with:', logs);
      
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
      console.log('loadDebugLogs called');
      
      var debugContainer = $('.section-'+this.pluginName+' .debug-logs');
      debugContainer.html('<p><i class="fa fa-spinner fa-spin"></i> Загрузка логов отладки...</p>');
      
      admin.ajaxRequest({
        mguniqueurl: "action/getDebugLogs",
        pluginHandler: this.pluginName
      }, function(response) {
        console.log('Debug logs response:', response);
        if (response.status == "success") {
          p4push.displayDebugLogs(response.data);
        } else {
          debugContainer.html('<p>Ошибка загрузки логов отладки: ' + (response.msg || 'Неизвестная ошибка') + '</p>');
        }
      });
    },
    
    /**
     * Отображение логов отладки
     */
    displayDebugLogs: function(logs) {
      console.log('displayDebugLogs called with:', logs);
      
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
      console.log('clearLogs called');
      
      if (!confirm('Вы уверены, что хотите очистить все логи?')) {
        return;
      }
      
      admin.ajaxRequest({
        mguniqueurl: "action/clearLogs",
        pluginHandler: this.pluginName
      }, function(response) {
        console.log('Clear logs response:', response);
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
  console.log('Document ready, initializing p4push...');
  p4push.init();
  
  // Показываем настройки по умолчанию при загрузке
  $('.section-p4_push .settings-form').show();
});