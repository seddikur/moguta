var pageScenario = {
  hits: [
    {
      selector: '.section-page .add-new-button',
      message: 'Чтобы создать новую страницу, нажмите на эту кнопку. Откроется карточка страницы, в которой можно будет указать её название адрес и содержание.',
    },
    {
      selector: '.section-page .main-table thead tr:first',
      message:'«Дерево страниц». Можно создавать страницы вложенные друг в друга, перетаскивать их меняя порядок отображения в меню, настраивать видимость на сайте.'
    },
    {
      selector: '.section-page .page-tree tr:first',
      message: 'В строках категорий отображаются: название, URL адрес и доступные действия.'
    },
    {
      selector: '.section-page .page-tree tr:first .mover',
      message: 'Чтобы поменять порядок отображения страниц в меню перетаскивайте строку за эту иконку.'
    },
    {
      selector: '.section-page .page-tree tr:first .action-list',
      message: 'В колонке «Действия» находятся элементы для управления страницами.\
<ul style="list-style-type: none; padding: 0px;">\
<li><i class="fa fa-pencil" style="font-size:16px;"></i> — Открывает карточку страницы для редактирования;</li>\
<li><i class="fa fa-plus-circle" style="font-size:16px;"></i> — Создает вложенную страницу в выбранной;</li>\
<li><i class="fa fa-lightbulb-o" style="font-size:16px;"></i> — Делает страницу не активной на сайте;</li>\
<li><i class="fa fa-trash" style="font-size:16px; color:black!important;"></i> — Удаляет страницу;</li>\
</ul>'
    },
    {
      selector: '.section-page .page-tree .show_sub_menu',
      message: 'Нажмите на эту иконку, чтобы увидеть вложенные страницы.'
    },
    {
      selector: '.section-category .label-select:first',
      message: '«Массовые действия» — это те действия, которые можно применить сразу к нескольким и более отмеченным галочками страницам. Например, можно удалить или переместить выбранные страницы.'
    },
    {
      selector: '.doc-link',
      message: 'Документация. Официальная документация с видеоуроками доступна по адресу wiki.moguta.ru '
    },

  ]
}

