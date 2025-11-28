var orderScenario = {
  hits: [
    {
      selector: '.section-order .add-new-button',
      message: 'Чтобы создать заказ вручную нажмите на эту кнопку. Откроется карточка заказа, в которой можно будет указать состав заказа, контактные данные покупателя, способ оплаты и доставки.',
    },
    {
      selector: '.section-order .show-filters',
      message: 'Заказы можно отфильтровать по дате, сумме, статусу, способу оплаты и другим параметрам.',
    },
    {
      selector: '.section-order .show-property-order',
      message: 'В настройках заказов указываются реквизиты юридического лица, от которого будут выставляться счета и закрывающие документы для бухгалтерии. Также можно установить статус заказа, который будет присвоен каждому заказу при его оформлении.'
    },
    {
      selector: '.section-order .js-from-intro-csv1',
      message: 'Можно скачать информацию о заказах в табличный файл с расширением .CSV.'
    },
    {
      selector: '.section-order .js-from-intro-csv2',
      message: 'Можно скачать информацию о составах заказов в табличный файл с расширением .CSV'
    },
    {
      selector: '.section-order .main-table thead tr:first',
      message: 'Таблица заказов. Тут отображается список заказов интерет-магазина. Нажав на заголовок колонки можно отсортировать по возрастанию и убыванию.'
    },
    {
      selector: '.section-order .open-col-config-modal',
      message: 'Нажмите на шестеренку, чтобы настроить видимость и порядок отображения колонок в таблице. Дополнительные поля товаров заказов, созданные в разделе настроек, также можно выводить в эту таблицу.'
    },
    {
      selector: '.section-order .order-tbody tr:first',
      message: 'Строка с информацией о заказе.'
    },
    {
      selector: '.section-order .order-tbody tr:first .action-list',
      message: 'В колонке «Действия» находятся элементы для управления заказами.\
<ul style="list-style-type: none; padding: 0px;">\
<li><i class="fa fa-pencil" style="font-size:16px;"></i> — Открывает карточку заказа на редактирование;</li>\
<li><i class="fa fa-download" style="font-size:16px;"></i> — Скачивает файл с содержимым заказа в формате .CSV;</li>\
<li><i class="fa fa-file-pdf-o" style="font-size:16px;"></i> — Скачивает бухгалтерские документы по заказу в формате .PDF;</li>\
<li><i class="fa fa-print" style="font-size:16px;"></i> — Отправляет на печать бухгалтерские документы;</li>\
<li><i class="fa fa-files-o" style="font-size:16px;"></i> — Делает копию заказа;</li>\
<li><i class="fa fa-trash" style="font-size:16px; color:black!important;"></i> — Удаляет заказ;</li>\
</ul>'
    },
    {
      selector: '.section-order .label-select:first',
      message: '«Массовые действия» — это те действия, которые можно применить сразу к нескольким и более отмеченным галочками заказам в таблице. Например, можно назначить ответственного за указанные заказы или скачать PDF счет сразу нескольких заказов.'
    },
    {
      selector: '.section-order .table-count-print',
      message: 'Количество строк в таблице заказов.'
    },

    {
      selector: '.section-order .mg-pager',
      message: 'Постраничная навигация.'
    },
    {
      selector: '.doc-link',
      message: 'Документация. Официальная документация с видеоуроками доступна по адресу wiki.moguta.ru '
    },

  ]
}
