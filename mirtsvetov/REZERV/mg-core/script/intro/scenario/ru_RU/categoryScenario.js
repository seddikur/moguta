var categoryScenario = {
  hits: [
    {
      selector: '.section-category .add-new-button',
      message: 'Чтобы создать категорию для товаров, нажмите на эту кнопку. Откроется карточка категории, в которой можно будет указать название, ссылку, описание, единицу измерения, изображение и другие данные.',
    },
    {
      selector: '.section-category .get-csv',
      message: 'Можно скачать категории в табличный файл с расширением .CSV, для массового редактирования категорий. Такой файл можно редактировать в популярных программах Excel, LibreOffice или в любом текстовом редакторе.'
    },
    {
      selector: '.section-category .import-csv',
      message: 'Moguta.CMS поддерживает загрузку категорий из .CSV файла. Можно наглядно подготовить структуру каталога в файле, а затем загрузить всё разом в интернет-магазин.'
    },
    {
      selector: '.section-category .sort-all-cat',
      message: 'Нажав на эту кнопку порядок отображения категорий будет изменён, и все категории выстроятся по алфавиту.'
    },
    {
      selector: '.section-category .main-table thead tr:first',
      message: '«Дерево категорий». Можно создавать категории любой вложенные друг в друга, перетаскивать их меняя порядок отображения, настраивать скидки и наценки на товары внутри категории. Не рекомендуется иметь вложенность более трех уровней.'
    },
    {
      selector: '.section-category .category-tree tr:first',
      message: 'В строках категорий отображаются: название, скидка и наценка, URL адрес, доступные действия.'
    },
    {
      selector: '.section-category .category-tree tr:first .mover',
      message: 'Чтобы поменять порядок отображения категорий в меню перетаскивайте строку за эту иконку.'
    },
    {
      selector: '.section-category .category-tree tr:first .action-list',
      message: 'В колонке «Действия» находятся элементы для управления категориями.\
<ul style="list-style-type: none; padding: 0px;">\
<li><i class="fa fa-pencil" style="font-size:16px;"></i> — Открывает карточку категории на редактирование;</li>\
<li><i class="fa fa-plus-circle" style="font-size:16px;"></i> — Создает вложенную категорию в выбранной;</li>\
<li><i class="fa fa-lightbulb-o" style="font-size:16px;"></i> — Делает категорию не активной на сайте, товары неактивных категорий не будут отображаться;</li>\
<li><i class="fa fa-list" style="font-size:16px;"></i> — Отображает категорию в меню каталога;</li>\
<li><i class="fa fa-shopping-cart" style="font-size:16px;"></i> — Отмечает какие категории будут участвовать в выгрузке на Яндекс.Маркет;</li>\
<li><i class="fa fa-search" style="font-size:16px;"></i> — Открывает раздел «Товары» с фильтром по категории;</li>\
<li><i class="fa fa-trash" style="font-size:16px; color:black!important;"></i> — Удаляет категорию;</li>\
</ul>'
},
    {
      selector: '.section-category .category-tree .show_sub_menu',
      message: 'Нажмите на эту иконку чтобы развернуть вложенные категории.'
    },
    {
      selector: '.section-category .label-select:first',
      message: '«Массовые действия» — это те действия, которые можно применить сразу к нескольким и более отмеченным галочками категориям. Например, удалить отмеченные категории включая все товары в них или поместить одну категорию в другую категорию.'
    },
    {
      selector: '.doc-link',
      message: 'Документация. Официальная документация с видеоуроками доступна по адресу wiki.moguta.ru '
    },

  ]
}
