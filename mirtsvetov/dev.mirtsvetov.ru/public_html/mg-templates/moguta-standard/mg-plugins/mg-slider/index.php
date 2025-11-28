<?php

/*
  Plugin Name: Конструктор слайдов
  Description: Данный плагин позволяет создавать неограниченное количество слайдеров и вставлять их в любые места на вашем сайте. Плагин поддерживает 3 типа слайдов – конструктор, баннер и HTML-вёрстка. Тип «Конструктор» имеет множество настроек, позволяющих создавать самые различные слайды.
  Author: Yakupov, Shevchenko
  Version: 1.2.5
  Edition: CLOUD
 */

new Slider;

class Slider
{
    private static $lang = array();
    private static $pluginName = '';
    public static $path = '';
    public static $styleSliders = [];
    private static $APIGoogleFont = 'AIzaSyB1rJDpxWCmDRr5Xg8zQcmFvlXmEztHXOY';
    private static $swiper_animations = array(
        'bounce', 'flash', 'pulse', 'rubberBand'
    , 'shake', 'headShake', 'swing', 'tada'
    , 'wobble', 'jello', 'bounceIn', 'bounceInDown'
    , 'bounceInLeft', 'bounceInRight', 'bounceInUp', 'bounceOut'
    , 'bounceOutDown', 'bounceOutLeft', 'bounceOutRight', 'bounceOutUp'
    , 'fadeIn', 'fadeInDown', 'fadeInDownBig', 'fadeInLeft'
    , 'fadeInLeftBig', 'fadeInRight', 'fadeInRightBig', 'fadeInUp'
    , 'fadeInUpBig', 'fadeOut', 'fadeOutDown', 'fadeOutDownBig'
    , 'fadeOutLeft', 'fadeOutLeftBig', 'fadeOutRight', 'fadeOutRightBig'
    , 'fadeOutUp', 'fadeOutUpBig', 'flipInX', 'flipInY'
    , 'flipOutX', 'flipOutY', 'lightSpeedIn', 'lightSpeedOut'
    , 'rotateIn', 'rotateInDownLeft', 'rotateInDownRight', 'rotateInUpLeft'
    , 'rotateInUpRight', 'rotateOut', 'rotateOutDownLeft', 'rotateOutDownRight'
    , 'rotateOutUpLeft', 'rotateOutUpRight', 'hinge', 'jackInTheBox'
    , 'rollIn', 'rollOut', 'zoomIn', 'zoomInDown'
    , 'zoomInLeft', 'zoomInRight', 'zoomInUp', 'zoomOut'
    , 'zoomOutDown', 'zoomOutLeft', 'zoomOutRight', 'zoomOutUp'
    , 'slideInDown', 'slideInLeft', 'slideInRight', 'slideInUp'
    , 'slideOutDown', 'slideOutLeft', 'slideOutRight', 'slideOutUp'
    , 'heartBeat'
    );

    private static $allValue = [];
    private static $relation = [];

    public function __construct()
    {
        self::$allValue = [
            'options' => [
                'main' => [
                    'name_slider' => [
                        'type' => 'text',
                        'value' => '',
                        'tooltip' => 'Название слайдера используется только для идентификации в панели администратора',
                        'placeholder' => 'Мой слайдер',
                    ],
                ],
                'slider' => [
                    'slider_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Элементы управления',
                    ],
                    'pagination' => [
                        'type' => 'checkbox',
                        'value' => 'true',
                    ],
                    'pagination_type' => [
                        'type' => 'select',
                        'list' => ['progressbar', 'bullets'],
                        'default' => false,
                        'name' => 'paginations',
                        'value' => 'bullets',
                    ],
                    'navigation' => [
                        'type' => 'checkbox',
                        'value' => 'true',
                    ],
                    'scrollbar' => [
                        'type' => 'checkbox',
                    ],
                    'color_interface' => [
                        'type' => 'color',
                        'value' => '515252',
                    ],
                    'slider_listing_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Перелистывание',
                    ],
                    'direction' => [
                        'type' => 'select',
                        'list' => ['horizontal', 'vertical'],
                        'default' => false,
                        'value' => 'horizontal',
                        'name' => 'directions',
                    ],
                    'autoplay' => [
                        'type' => 'number',
                        'min' => '0',
                        'step' => '0.1',
                        'value' => '3',
                        'tooltip' => 'Время показа каждого слайда в секундах. При установленном значении «0» слайдер автоматически не листается.',
                    ],
                    'speed' => [
                        'type' => 'number',
                        'min' => '0',
                        'max' => '100',
                        'step' => '0.1',
                        'value' => '0.3',
                        'tooltip' => 'Время в секундах, за которое один слайд поменяется на другой, после завершения времени показа слайда',
                    ],
                    'effect' => [
                        'type' => 'select',
                        'list' => ['slide', 'cube', 'flip', 'fade', 'coverflow'],
                        'default' => false,
                        'value' => 'slide',
                        'name' => 'effects',
                    ],
                    'keyboard' => [
                        'type' => 'checkbox',
                    ],
                    'mousewheel' => [
                        'type' => 'checkbox',
                    ],
                    'loop' => [
                        'type' => 'checkbox',
                        'value' => 'true',
                    ],
                    'autoplay_disable_on_interaction' => [
                        'type' => 'checkbox',
                        'value' => 'true',
                        'tooltip' => 'Отключить автопрокрутку, если пользователь самостоятельно переключает слайды',
                    ],
                ],
                'other' => [
                    'slider_other_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Размеры слайдера',
                    ],
                    'slider_max_width' => [
                        'type' => 'number',
                        'min' => '0',
                        'step' => '1',
                        'value' => '1200',
                        'placeholder' => '1200',
                    ],
                    'slider_height' => [
                        'type' => 'number',
                        'min' => '0',
                        'step' => '1',
                        'value' => '586',
                        'placeholder' => '586',
                    ],
                    'slider_height_all' => [
                        'type' => 'checkbox',
                        'tooltip' => 'При включённой опции высота слайдера будет равна высоте экрана устройства, на котором открыта страница',
                    ],
                    'height_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Высота слайдера на различных устройствах',
                        'li' => false,
                    ],
                    'md_slider_height_desc' => [
                        'type' => 'desc',
                        'value' => 'Если значения не указаны – на всех устройствах будет использоваться высота, указанная выше.',
                        'li' => false,
                    ],
                    'md_slider_height_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Ноутбуки',
                        'li' => false,
                        'tooltip' => 'Ширина экрана от 992 до 1200px',
                    ],
                    'md_slider_height_all' => [
                        'type' => 'checkbox',
                        'li' => false,
                        'tooltip' => 'При включённой опции высота слайдера будет равна высоте экрана устройства, на котором открыта страница',
                    ],
                    'md_slider_height' => [
                        'type' => 'number',
                        'min' => '0',
                        'step' => '1',
                        'placeholder' => '586',
                        'li' => false,
                    ],
                    't_slider_height_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Планшеты',
                        'li' => false,
                        'tooltip' => 'Ширина экрана от 768 до 992px',
                    ],
                    't_slider_height_all' => [
                        'type' => 'checkbox',
                        'li' => false,
                        'tooltip' => 'При включённой опции высота слайдера будет равна высоте экрана устройства, на котором открыта страница',
                    ],
                    't_slider_height' => [
                        'type' => 'number',
                        'min' => '0',
                        'step' => '1',
                        'li' => false,
                        'placeholder' => '586',
                    ],
                    'm_slider_height_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Мобильные',
                        'li' => false,
                        'tooltip' => 'Ширина экрана до 768px',
                    ],
                    'm_slider_height_all' => [
                        'type' => 'checkbox',
                        'li' => false,
                        'tooltip' => 'При включённой опции высота слайдера будет равна высоте экрана устройства, на котором открыта страница',
                    ],
                    'm_slider_height' => [
                        'type' => 'number',
                        'min' => '0',
                        'step' => '1',
                        'li' => false,
                        'placeholder' => '586',
                    ],
                    'end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ]
                ],
            ],
            'modal_template' => [
                'main' => [
                    'main_start_ul' => [
                        'type' => 'start_ul',
                        'classes' => 'slider-opt-list',
                        'li' => false,
                    ],
                        'main_title' => [
                            'type' => 'title',
                            'h' => '4',
                            'value' => 'Контент слайда',
                        ],
                        'title' => [
                            'type' => 'text',
                            'value' => 'Заголовок слайда'
                        ],
                        'content' => [
                            'type' => 'textarea',
                            'ckeditor' => 'true',
                            'value' => 'Подзаголовок слайда'
                        ],
                        'button' => [
                            'type' => 'text',
                            'value' => 'Кнопка со ссылкой'
                        ],
                        'button_href' => [
                            'type' => 'text',
                            'placeholder' => SITE.'/catalog',
                            'tooltip' => 'Ссылка на страницу, которая будет открыта при нажатии на кнопку',
                        ],
                    'main_end_ul' => [
                        'type' => 'end_ul',
                        'li' => false,
                    ],
                    'bg_start_ul' => [
                      'type' => 'start_ul',
                      'classes' => 'slider-opt-list',
                      'li' => false,
                    ],
                        'bg_title' => [
                          'type' => 'title',
                          'h' => '4',
                          'value' => 'Настройки фона',
                        ],
                        'img_path' => [
                          'type' => 'img',
                        ],
                        'color' => [
                          'type' => 'color',
                          'value' => '#ffffff',
                          'default' => '#ffffff',
                        ],
                        'overlay_color' => [
                          'type' => 'color',
                          'value' => 'transparent',
                          'default' => 'transparent',
                          'tooltip' => 'Полупрозрачный фон между основным фоном и контентом слайда. Будет полезен, например, если текст слайда плохо виден на фоне. Не забудьте выставить прозрачность в крайнем правом ползунке.',
                          'opacity' => true,
                        ],
                        'background_size' => [
                          'type' => 'select',
                          'list' => ['original', 'cover', 'contain'],
                          'tooltip' => 'По умолчанию, фоновое изображение используется в своём оригинальном размере. Также вы можете растянуть его на всю ширину с охранением пропорций и без',
                          'value' => 'original',
                        ],
                        'position_x' => [
                          'type' => 'select',
                          'list' => ['left','right','center'],
                          'value' => 'left'
                        ],
                        'position_y' => [
                          'type' => 'select',
                          'list' => ['top','bottom','center'],
                          'value' => 'center',
                        ],
                        'slider_bg_ac' => [
                          'type' => 'start_ac',
                          'value' => 'Для ноутбуков',
                          'li' => false,
                        ],
                            'md_img_path' => [
                              'type' => 'img',
                              'li' => false,
                            ],
                            'md_color' => [
                              'type' => 'color',
                              'value' => '#ebebeb',
                              'li' => false,
                            ],
                            'md_overlay_color' => [
                              'type' => 'color',
                              'value' => 'transparent',
                              'opacity' => true,
                              'li' => false,
                            ],
                            'md_background_size' => [
                              'type' => 'select',
                              'list' => ['original', 'cover', 'contain'],
                              'tooltip' => 'По умолчанию, фоновое изображение используется в своём оригинальном размере. Также вы можете растянуть его на весю ширину, либо заполнить им весь слайд',
                              'default' => '',
                              'li' => false,
                            ],
                            'md_position_x' => [
                              'type' => 'select',
                              'list' => ['left','right','center'],
                              'default' => '',
                              'li' => false,
                            ],
                            'md_position_y' => [
                              'type' => 'select',
                              'list' => ['top','bottom','center'],
                              'default' => '',
                              'li' => false,
                            ],
                        'slider_bg_end_ac' => [
                          'type' => 'end_ac',
                          'li' => false,
                        ],
                        'е_slider_bg_ac' => [
                          'type' => 'start_ac',
                          'value' => 'Для планшетов',
                          'li' => false,
                        ],
                            't_img_path' => [
                              'type' => 'img',
                              'li' => false,
                            ],
                            't_color' => [
                              'type' => 'color',
                              'value' => '#ebebeb',
                              'li' => false,
                            ],
                            't_overlay_color' => [
                              'type' => 'color',
                              'value' => 'transparent',
                              'opacity' => true,
                              'li' => false,
                            ],
                            't_background_size' => [
                              'type' => 'select',
                              'list' => ['original', 'cover', 'contain'],
                              'tooltip' => 'По умолчанию, фоновое изображение используется в своём оригинальном размере. Также вы можете растянуть его на весю ширину, либо заполнить им весь слайд',
                              'default' => '',
                              'li' => false,
                            ],
                            't_position_x' => [
                              'type' => 'select',
                              'list' => ['left','right','center'],
                              'default' => '',
                              'li' => false,
                            ],
                            't_position_y' => [
                              'type' => 'select',
                              'list' => ['top','bottom','center'],
                              'default' => '',
                              'li' => false,
                            ],
                        't_slider_bg_end_ac' => [
                          'type' => 'end_ac',
                          'li' => false,
                        ],
                        'm_slider_bg_ac' => [
                          'type' => 'start_ac',
                          'value' => 'Для мобильных',
                          'li' => false,
                        ],
                            'm_img_path' => [
                              'type' => 'img',
                              'li' => false,
                            ],
                            'm_color' => [
                              'type' => 'color',
                              'value' => '#ebebeb',
                              'li' => false,
                            ],
                            'm_overlay_color' => [
                              'type' => 'color',
                              'value' => 'transparent',
                              'opacity' => true,
                              'li' => false,
                            ],
                            'm_background_size' => [
                              'type' => 'select',
                              'list' => ['original', 'cover', 'contain'],
                              'tooltip' => 'По умолчанию, фоновое изображение используется в своём оригинальном размере. Также вы можете растянуть его на весю ширину, либо заполнить им весь слайд',
                              'default' => '',
                              'li' => false,
                            ],
                            'm_position_x' => [
                              'type' => 'select',
                              'list' => ['left','right','center'],
                              'default' => '',
                              'li' => false,
                            ],
                            'm_position_y' => [
                              'type' => 'select',
                              'list' => ['top','bottom','center'],
                              'default' => '',
                              'li' => false,
                            ],
                        'slider_bg_end_ac' => [
                          'type' => 'end_ac',
                          'li' => false,
                        ],
                    'bg_end_ul' => [
                      'type' => 'end_ul',
                      'li' => false,
                    ],
                    'content_start_ul' => [
                        'type' => 'start_ul',
                        'classes' => 'slider-opt-list',
                        'li' => false,
                    ],
                        'content_title' => [
                            'type' => 'title',
                            'h' => '4',
                            'value' => 'Настройки контента',
                        ],
                        'invisible' => [
                            'type' => 'checkbox',
                            'tooltip' => 'Если опция активна – слайд не будет виден пользователям',
                        ],
                        'font' => [
                            'type' => 'select',
                            'list' => self::getGoogleFonts(),
                            'not_locale' => true,
                            'combobox' => true,
                            'default' => '',
                            'tooltip' => 'Вам доступны все бесплатные кириллические шрифты с сервиса Google Fonts',
                        ],
                        'content_max_width' => [
                          'type' => 'number',
                          'step' => '1',
                          'min' => '0',
                          'placeholder' => 'По умолчанию – 730px',
                          'tooltip' => 'Максимальная ширина контейнера с контентом слайда'
                        ],
                        'position_content' => [
                            'type' => 'select',
                            'list' => ['center', 'right', 'left'],
                            'name' => 'positions',
                            'value' => 'left',
                            'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде по горизонтили',
                        ],
                        'position_content_vertical' => [
                          'type' => 'select',
                          'list' => ['top', 'middle', 'bottom'],
                          'name' => 'positions',
                          'value' => 'center',
                          'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде по вертикали',
                        ],
                        'position_offset_top' => [
                             'type' => 'number',
                             'step' => '1',
                             'min' => '0',
                             'tooltip' => 'Отступ в пикселях сверху'
                         ],
                        'position_offset_bottom' => [
                             'type' => 'number',
                             'step' => '1',
                             'min' => '0',
                             'tooltip' => 'Отступ в пикселях снизу'
                         ],
                         'position_offset_center' => [
                            'type' => 'number',
                            'step' => '1',
                            'min' => '-100',
                            'max' => '100',
                            'tooltip' => 'Отступ от центра в процентах (Если значение отрицательное, отступ будет влево, если положительное - вправо)',
                        ],
                          'content_md_ac' => [
                            'type' => 'start_ac',
                            'value' => 'Для ноутбуков',
                            'li' => false,
                          ],
                              'md_invisible' => [
                                'type' => 'checkbox',
                                'tooltip' => 'Если опция активна – слайд не будет виден пользователям',
                                'li' => false,
                              ],
                              'md_position_content' => [
                                'type' => 'select',
                                'list' => ['center', 'right', 'left'],
                                'name' => 'positions',
                                'default' => '',
                                'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде',
                                'li' => false,
                              ],
                              'md_position_content_vertical' => [
                                'type' => 'select',
                                'list' => ['top', 'middle', 'bottom'],
                                'name' => 'positions',
                                'default' => '',
                                'value' => 'center',
                                'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде по вертикали',
                                'li' => false,
                              ],
                              'md_position_offset_top' => [
                                   'type' => 'number',
                                   'step' => '1',
                                   'min' => '0',
                                   'tooltip' => 'Отступ в пикселях сверху. Если поле пустое, используется значения с большего размера экрана.',
                                   'li' => false,
                               ],
                              'md_position_offset_bottom' => [
                                   'type' => 'number',
                                   'step' => '1',
                                   'min' => '0',
                                   'tooltip' => 'Отступ в пикселях снизу. Если поле пустое, используется значения с большего размера экрана.',
                                   'li' => false,
                               ],
                               'md_position_offset_center' => [
                                    'type' => 'number',
                                    'step' => '1',
                                    'min' => '-100',
                                    'max' => '100',
                                    'tooltip' => 'Отступ от центра в процентах (Если значение отрицательное, отступ будет влево, если положительное - вправо)',
                                    'li' => false,
                                ],
                          'content_md_end_ac' => [
                            'type' => 'end_ac',
                            'li' => false,
                          ],
                          'content_t_ac' => [
                            'type' => 'start_ac',
                            'value' => 'Для планшетов',
                            'li' => false,
                          ],
                              't_invisible' => [
                                'type' => 'checkbox',
                                'tooltip' => 'Если опция активна – слайд не будет виден пользователям',
                                'li' => false,
                              ],
                              't_position_content' => [
                                'type' => 'select',
                                'list' => ['center', 'right', 'left'],
                                'name' => 'positions',
                                'default' => '',
                                'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде',
                                'li' => false,
                              ],
                              't_position_content_vertical' => [
                                'type' => 'select',
                                'list' => ['top', 'middle', 'bottom'],
                                'name' => 'positions',
                                'value' => 'center',
                                'default' => '',
                                'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде по вертикали',
                                'li' => false,
                              ],
                              't_position_offset_top' => [
                                   'type' => 'number',
                                   'step' => '1',
                                   'min' => '0',
                                   'tooltip' => 'Отступ в пикселях сверху. Если поле пустое, используется значения с большего размера экрана.',
                                   'li' => false,
                               ],
                              't_position_offset_bottom' => [
                                   'type' => 'number',
                                   'step' => '1',
                                   'min' => '0',
                                   'tooltip' => 'Отступ в пикселях снизу. Если поле пустое, используется значения с большего размера экрана.',
                                   'li' => false,
                               ],
                               't_position_offset_center' => [
                                    'type' => 'number',
                                    'step' => '1',
                                    'min' => '-100',
                                    'max' => '100',
                                    'tooltip' => 'Отступ от центра в процентах (Если значение отрицательное, отступ будет влево, если положительное - вправо)',
                                    'li' => false,
                                ],
                          'content_t_end_ac' => [
                            'type' => 'end_ac',
                            'li' => false,
                          ],
                          'content_m_ac' => [
                            'type' => 'start_ac',
                            'value' => 'Для мобильных',
                            'li' => false,
                          ],
                              'm_invisible' => [
                                'type' => 'checkbox',
                                'tooltip' => 'Если опция активна – слайд не будет виден пользователям',
                                'li' => false,
                              ],
                              'm_position_content' => [
                                'type' => 'select',
                                'list' => ['center', 'right', 'left'],
                                'name' => 'positions',
                                'default' => '',
                                'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде',
                                'li' => false,
                              ],
                              'm_position_content_vertical' => [
                                'type' => 'select',
                                'list' => ['top', 'middle', 'bottom'],
                                'name' => 'positions',
                                'value' => 'center',
                                'default' => '',
                                'tooltip' => 'Расположение блока с заголовком, подзаголовком и кнопкой на слайде по вертикали',
                                'li' => false,
                              ],
                              'm_position_offset_top' => [
                                   'type' => 'number',
                                   'step' => '1',
                                   'min' => '0',
                                   'tooltip' => 'Отступ в пикселях сверху. Если поле пустое, используется значения с большего размера экрана.',
                                   'li' => false,
                               ],
                              'm_position_offset_bottom' => [
                                   'type' => 'number',
                                   'step' => '1',
                                   'min' => '0',
                                   'tooltip' => 'Отступ в пикселях снизу. Если поле пустое, используется значения с большего размера экрана.',
                                   'li' => false,
                               ],
                               'm_position_offset_center' => [
                                    'type' => 'number',
                                    'step' => '1',
                                    'min' => '-100',
                                    'max' => '100',
                                    'tooltip' => 'Отступ от центра в процентах (Если значение отрицательное, отступ будет влево, если положительное - вправо)',
                                    'li' => false,
                                ],
                          'content_m_end_ac' => [
                            'type' => 'end_ac',
                            'li' => false,
                          ],
                        'inner_anim_ac' => [
                            'type' => 'start_ac',
                            'value' => 'Анимация блока с контентом',
                            'li' => false,
                        ],
                            'desc_anim' => [
                                'type' => 'desc',
                                'value' => 'Используя поля ниже, вы можете настроить анимацию появления и исчезновения 
                                            блока с элементами слайда (заголовком, подзаголовком и кнопкой). <br><br>
                                            Если вы хотите настроить анимацию для каждого элемента отдельно, то сделать это можно в следующих разделах. <br><br>
                                            Тип анимации проще всего выбрать, используя
                                                <a rel="nofollow" target="_blank" href="https://daneden.github.io/animate.css/">
                                                    демо
                                                    <i class="fa fa-external-link"
                                                       style="vertical-align: middle"
                                                       aria-hidden="true"></i>
                                                </a>',
                                'li' => false,
                            ],
                            'inner_data_swiper_animation' => [
                                'type' => 'select',
                                'list' => self::$swiper_animations,
                                'name' => 'swiper_animation',
                                'combobox' => true,
                                'li' => false,
                                'default' => '',
                                'tooltip' => 'Анимация, с которой будет появляться блок с контентом слайда.',
                            ],
                            'inner_data_duration' => [
                                'type' => 'number',
                                'step' => '0.1',
                                'min' => '0',
                                'placeholder' => '0.5',
                                'li' => false,
                            ],
                            'inner_data_delay' => [
                                'type' => 'number',
                                'step' => '0.1',
                                'min' => '0',
                                'li' => false,
                                'placeholder' => '0.3',
                                'tooltip' => 'Время в секундах, с момента открытия слайда, через которое начнёт воспроизводиться анимация.',
                            ],
                            'inner_data_swiper_out_animation' => [
                                'type' => 'select',
                                'list' => self::$swiper_animations,
                                'name' => 'swiper_animation',
                                'combobox' => true,
                                'li' => false,
                                'default' => '',
                                'tooltip' => 'Анимация исчезновения блока с контентом при переключении на другой слайд.',
                            ],
                            'inner_data_out_duration' => [
                                'type' => 'number',
                                'step' => '0.1',
                                'min' => '0',
                                'li' => false,
                                'placeholder' => '0.5',
                            ],
                        'inner_anim_end_ac' => [
                            'type' => 'end_ac',
                            'li' => false,
                        ],
                    'con_end_ul' => [
                        'type' => 'end_ul',
                        'li' => false,
                    ]
                ],
                'title' => [
                    'title_start_ul' => [
                        'type' => 'start_ul',
                        'classes' => 'slider-opt-list',
                        'li' => false,
                    ],
                    'title_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Настройки заголовка слайда',
                    ],
                    'title_desc' => [
                        'type' => 'desc',
                        'value' => 'Все настройки заголовка ниже <strong>необязательны к заполнению</strong>. <br>
                                    Если их значения не указаны – будут использоваться стандартные настройки плагина.',
                    ],

                    'title_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Оформление заголовка',
                        'li' => false,
                    ],
                        'title_heading' => [
                            'type' => 'number',
                            'min' => '1',
                            'max' => '6',
                            'step' => '1',
                            'li' => false,
                            'value' => '2',
                            'placeholder' => '2',
                            'tooltip' => 'Значение «1» – заголовок первого уровня (h1), «2» – заголовок второго уровня (h2) и т.д.',
                        ],
                        'title_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'value' => '60',
                            'placeholder' => '60',
                        ],
                        'title_font_color' => [
                            'type' => 'color',
                            'li' => false,
                            'value' => '#333333',
                        ],
                    'end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],

                    'title_mobile_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Размер текста заголовка для мобильных устройств',
                        'li' => false,
                    ],
                        'title_mobile_desc' => [
                            'type' => 'desc',
                            'value' => 'Если значения не указаны – на всех устройствах будет использоваться размер шрифта, указанный выше.',
                            'li' => false,
                        ],
                        'md_title_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста заголовка при ширине экрана от 992 до 1200px',
                            'placeholder' => '50',
                        ],
                        't_title_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста заголовка при ширине экрана от 768 до 992px',
                            'placeholder' => '40',
                        ],
                        'm_title_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста заголовка при ширине экрана до 768px',
                            'placeholder' => '30',
                        ],
                    'title_mobile_end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],

                    'title_animation_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Анимация заголовка',
                        'li' => false,
                    ],
                        'desc_anim' => [
                            'type' => 'desc',
                            'value' => 'Используя поля ниже, вы можете настроить анимацию появления и исчезновения заголовка
                                        при переключении слайда. Тип анимации проще всего выбрать, используя
                                        <a rel="nofollow" target="_blank" href="https://daneden.github.io/animate.css/">
                                            демо
                                            <i class="fa fa-external-link"
                                               style="vertical-align: middle"
                                               aria-hidden="true"></i>
                                        </a>',
                            'li' => false,
                        ],
                        'title_data_swiper_animation' => [
                            'type' => 'select',
                            'list' => self::$swiper_animations,
                            'name' => 'swiper_animation',
                            'combobox' => true,
                            'li' => false,
                            'default' => '',
                            'tooltip' => 'Анимация, с которой будет появляться заголовок слайда.',
                        ],
                        'title_data_duration' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.5',
                        ],
                        'title_data_delay' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.3',
                            'tooltip' => 'Время в секундах, с момента открытия слайда, через которое начнёт воспроизводиться анимация. Удобно использовать для последовательного воспроизведения анимаций, например сначала появляется заголовок, потом через пол секунды подзаголовок и через секунду кнопка.',
                        ],
                        'title_data_swiper_out_animation' => [
                            'type' => 'select',
                            'list' => self::$swiper_animations,
                            'name' => 'swiper_animation',
                            'combobox' => true,
                            'li' => false,
                            'default' => '',
                            'tooltip' => 'Анимация исчезновения заголовка при переключении на другой слайд.',
                        ],
                        'title_data_out_duration' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.5',
                        ],
                    'title_animation_end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],

                    'title_end_ul' => [
                        'type' => 'end_ul',
                        'li' => false,
                    ],
                ],
                'content' => [
                    'content_start_ul' => [
                        'type' => 'start_ul',
                        'classes' => 'slider-opt-list',
                        'li' => false,
                    ],
                    'content_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Настройки подзаголовка слайда',
                    ],
                    'content_desc' => [
                        'type' => 'desc',
                        'value' => 'Все настройки подзаголовка ниже <strong>необязательны к заполнению</strong>. <br>
                                    Если их значения не указаны – будут использоваться стандартные настройки плагина.',
                    ],
                    'content_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Оформление подзаголовка',
                        'li' => false,
                    ],
                        'text_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'value' => '22',
                            'placeholder' => '22',
                        ],
                        'content_font_color' => [
                            'type' => 'color',
                            'li' => false,
                            'value' => '#333333',
                            'tooltip' => 'Цвет, указанный в этой настройке, может перебиваться цветом, указанном в редакторе заголовка выше.',
                        ],
                    'end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],

                    'content_mobile_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Размер текста подзаголовка для мобильных устройств',
                        'li' => false,
                    ],
                        'content_mobile_desc' => [
                            'type' => 'desc',
                            'value' => 'Если значения не указаны – на всех устройствах будет использоваться размер шрифта, указанный выше.',
                            'li' => false,
                        ],
                        'md_text_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста подзаголовка при ширине экрана от 992 до 1200px',
                            'placeholder' => '20',
                        ],
                        't_text_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста подзаголовка при ширине экрана от 768 до 992px',
                            'placeholder' => '18',
                        ],
                        'm_text_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста подзаголовка при ширине экрана до 768px',
                            'placeholder' => '16',
                        ],
                    'end_mobile_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],

                    'content_anim_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Анимация подзаголовка',
                        'li' => false,
                    ],
                        'desc_anim' => [
                            'type' => 'desc',
                            'value' => 'Используя поля ниже, вы можете настроить анимацию появления и исчезновения подзаголовка
                                        при переключении слайда. Тип анимации проще всего выбрать, используя
                                        <a rel="nofollow" target="_blank" href="https://daneden.github.io/animate.css/">
                                            демо
                                            <i class="fa fa-external-link"
                                               style="vertical-align: middle"
                                               aria-hidden="true"></i>
                                        </a>',
                            'li' => false,
                        ],
                        'content_data_swiper_animation' => [
                            'type' => 'select',
                            'list' => self::$swiper_animations,
                            'name' => 'swiper_animation',
                            'combobox' => true,
                            'li' => false,
                            'default' => '',
                            'tooltip' => 'Анимация, с которой будет появляться подзаголовок слайда.',
                        ],
                        'content_data_duration' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.5',
                        ],
                        'content_data_delay' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.3',
                            'tooltip' => 'Время в секундах, с момента открытия слайда, через которое начнёт воспроизводиться анимация. Удобно использовать для последовательного воспроизведения анимаций, например сначала появляется заголовок, потом через пол секунды подзаголовок и через секунду кнопка.',
                        ],
                        'content_data_swiper_out_animation' => [
                            'type' => 'select',
                            'list' => self::$swiper_animations,
                            'name' => 'swiper_animation',
                            'combobox' => true,
                            'li' => false,
                            'default' => '',
                            'tooltip' => 'Анимация исчезновения подзаголовка при переключении на другой слайд.',
                        ],
                        'content_data_out_duration' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.5',
                        ],
                    'end_anim_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],
                    'content_end_ul' => [
                        'type' => 'end_ul',
                        'li' => false,
                    ],
                ],
                'button' => [
                    'button_start_ul' => [
                        'type' => 'start_ul',
                        'classes' => 'slider-opt-list',
                        'li' => false,
                    ],
                    'button_title' => [
                        'type' => 'title',
                        'h' => '4',
                        'value' => 'Настройки кнопки слайда',
                    ],
                    'button_selector' => [
                      'type' => 'text',
                      'placeholder' => 'Необязательный параметр',
                      'tooltip' => 'Уникальный класс, который будет добавлен к кнопке. Может быть использован для назначения js-событий или css-стилей. Точку перед классом ставить не нужно.',
                    ],
                    'button_desc' => [
                        'type' => 'desc',
                        'value' => 'Все настройки кнопки ниже <strong>необязательны к заполнению</strong>. <br>
                                    Если их значения не указаны – будут использоваться стандартные настройки плагина.',
                    ],
                    'button_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Оформление кнопки',
                        'li' => false,
                    ],
                        'button_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'value' => '18',
                            'placeholder' => '18',
                        ],
                        'button_font_color' => [
                            'type' => 'color',
                            'li' => false,
                            'value' => '#fff',
                        ],
                        'button_background_color_template' => [
                            'type' => 'checkbox',
                            'tooltip' => 'Стиль кнопки на слайдере будет аналогичен кнопкам текущего шаблона',
                            'li' => false,
                        ],
                        'button_background_color' => [
                            'type' => 'color',
                            'li' => false,
                            'value' => '#f05a4f',
                        ],
                        'button_hover_color' => [
                            'type' => 'color',
                            'li' => false,
                            'value' => '#ff493c',
                        ],
                        'button_border_radius' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'value' => '80',
                            'placeholder' => '80',
                            'tooltip' => 'Чем больше значение, тем более округлая получится кнопка.',
                        ],
                    'end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],
                    'button_mobile_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Размер текста кнопки для мобильных устройств',
                        'li' => false,
                    ],
                        'button_mobile_desc' => [
                            'type' => 'desc',
                            'value' => 'Если значения не указаны – на всех устройствах будет использоваться размер шрифта, указанный выше.',
                            'li' => false,
                        ],
                        'md_button_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста кнопки при ширине экрана от 992 до 1200px',
                            'placeholder' => '18',
                        ],
                        't_button_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста кнопки при ширине экрана от 768 до 992px',
                            'placeholder' => '17',
                        ],
                        'm_button_font_size' => [
                            'type' => 'number',
                            'min' => '0',
                            'step' => '1',
                            'li' => false,
                            'tooltip' => 'Размер текста кнопки при ширине экрана до 768px',
                            'placeholder' => '16',
                        ],
                    'button_mobile_end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],
                    'button_anim_ac' => [
                        'type' => 'start_ac',
                        'value' => 'Анимация кнопки',
                        'li' => false,
                    ],
                        'desc_anim' => [
                            'type' => 'desc',
                            'value' => 'Используя поля ниже, вы можете настроить анимацию появления и исчезновения заголовка
                                        при переключении слайда. Тип анимации проще всего выбрать, используя
                                        <a rel="nofollow" target="_blank" href="https://daneden.github.io/animate.css/">
                                            демо
                                            <i class="fa fa-external-link"
                                               style="vertical-align: middle"
                                               aria-hidden="true"></i>
                                        </a>',
                            'li' => false,
                        ],
                        'button_data_swiper_animation' => [
                            'type' => 'select',
                            'list' => self::$swiper_animations,
                            'name' => 'swiper_animation',
                            'combobox' => true,
                            'li' => false,
                            'default' => '',
                            'tooltip' => 'Анимация, с которой будет появляться кнопка.',
                        ],
                        'button_data_duration' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.5',
                        ],
                        'button_data_delay' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.3',
                            'tooltip' => 'Время в секундах, с момента открытия слайда, через которое начнёт воспроизводиться анимация. Удобно использовать для последовательного воспроизведения анимаций, например сначала появляется заголовок, потом через пол секунды подзаголовок и через секунду кнопка.',
                        ],
                        'button_data_swiper_out_animation' => [
                            'type' => 'select',
                            'list' => self::$swiper_animations,
                            'name' => 'swiper_animation',
                            'combobox' => true,
                            'li' => false,
                            'default' => '',
                            'tooltip' => 'Анимация исчезновения кнопки при переключении на другой слайд.',
                        ],
                        'button_data_out_duration' => [
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'li' => false,
                            'placeholder' => '0.5',
                        ],
                    'button_anim_end_ac' => [
                        'type' => 'end_ac',
                        'li' => false,
                    ],

                    'button_end_ul' => [
                        'type' => 'end_ul',
                        'li' => false,
                    ],
                ],
            ],
            'modal_image' => [
                'main' => [
                    'src' => [
                        'type' => 'img',
                    ],
                    'href' => [
                        'type' => 'text',
                        'tooltip' => 'Ссылка на страницу, которая будет открываться при нажатии на слайд',
                        'placeholder' => '/catalog',
                    ]
                ],
                'seo' => [
                    'seo_title' => [
                        'type' => 'text',
                        'placeholder' => 'Название изображения',
                        'tooltip' => 'Название изображения. Выводится в подсказке при наведении на изображение, а также используется поисковыми системами для получения информации о нём.',
                    ],
                    'seo_alt' => [
                        'type' => 'text',
                        'placeholder' => 'Альтернативное описание',
                        'tooltip' => 'Текст, который будет выведен вместо изображения, если оно по какой-то причине не загрузилось. Также используется поисковыми системами для получения информации об изображении.',
                    ],
                ],
            ],
            'modal_html' => [
                'main' => [
                    'html_start_ul' => [
                        'type' => 'start_ul',
                        'classes' => 'slider-opt-list',
                        'li' => false,
                    ],
                    'html' => [
                        'type' => 'textarea',
                        'codemirror' => 'true',
                        'li' => false,
                    ],
                    'main_end_ul' => [
                        'type' => 'end_ul',
                        'li' => false,
                    ],

                ],
            ],
        ];

        self::$relation = array(
            'autoheight' => array(
                'slider_height',
            ),
            'pagination' => array(
                'pagination_type'
            ),
        );

        mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate'));
        mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin'));
        mgAddShortcode('mgslider', array(__CLASS__, 'slider'));

        mgAddAction('mg_print_html', array(__CLASS__, 'printHtml'), 1); 

        self::$pluginName = PM::getFolderPlugin(__FILE__);
        self::$lang = PM::plugLocales(self::$pluginName);
        
        $explode = explode(str_replace('/', DS, PLUGIN_DIR), dirname(__FILE__));
        if (strpos($explode[0], 'mg-templates') === false) {
            self::$path = str_replace('\\', '/', PLUGIN_DIR.DS.$explode[1]);
        } else {
            $templatePath = str_replace('\\', '/', $explode[0]);
            $templatePathParts = explode('/', $templatePath);
            $templatePathParts = array_filter($templatePathParts, function($pathPart) {
            if (trim($pathPart)) {
                return true;
            }
                return false;
            });
            $templateName = end($templatePathParts);
            self::$path = 'mg-templates/'.$templateName.'/mg-plugins/'.$explode[1];
        }

        if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
            mgAddMeta('<script src="' . SITE . '/' . self::$path . '/js/bundle.js"></script>');
            mgAddMeta('<script src="' . SITE . '/' . self::$path . '/js/initSlider.js"></script>');
        }
    }
    /**
     * После формирования всей верстки добавляет стили слайдеров в секцию, перед </head>
     */
    static function printHtml($args){
        $result = $args['result'];
        if (empty(self::$styleSliders)) {
            return $result;
        }
        foreach (array_values(self::$styleSliders) as $sliderStyle) {
            $result = str_replace('</head>', $sliderStyle.'</head>', $result);
        }
        return $result;
    }

    /**
     * Метод выполняющийся при активации палагина
     */
    static function activate()
    {
        self::createDateBase();
        self::moveExampleImages();
        self::createExample();
    }

    static function moveExampleImages()
    {
        $dirPath = SITE_DIR.'uploads/mg-slider/'; //Куда
        $imgPath = SITE_DIR.PLUGIN_DIR.'mg-slider/img/'; //Откуда
        $images = ['slide-blue.jpg', 'slide-card.jpg', 'slide-child.jpg', 'slide-sale.jpg']; //Какие картинки

        if (!file_exists($dirPath)) {
            @mkdir($dirPath);
            @chmod($dirPath, 0777);
        }

        $files = array_diff(scandir($dirPath), array('.', '..'));
        foreach ($images as $image) {
            @copy($imgPath.$image, $dirPath.$image);
        }

    }

    static function createExample()
    {
        $row = DB::fetchAssoc(DB::query("SELECT `id` FROM `" . PREFIX . self::$pluginName . "` LIMIT 1"));
        if (empty($row['id'])) {
            include('example.php');
            DB::query($sql);
            setcookie('lastSlider', '1');
        }
    }

    /**
     * Создает таблицу плагина в БД
     */
    static function createDateBase()
    {
        DB::query("
     CREATE TABLE IF NOT EXISTS `" . PREFIX . self::$pluginName . "` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер слайдера',
	    `name_slider` text NOT NULL COMMENT 'Название слайдера',
      `slides` longtext NOT NULL COMMENT 'Содержимое слайдов', 
      `options` text NOT NULL COMMENT 'Настройки сладера',
      `invisible` int(1) NOT NULL DEFAULT '0' COMMENT 'видимость',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    }

    /**
     * Метод выполняющийся перед генерацией страницы настроек плагина
     */
    static function preparePageSettings()
    {
        echo '
      <link rel="stylesheet" href="' . SITE . '/' . self::$path . '/css/admin.css" />
      <script src="' . SITE . '/' . self::$path . '/js/bundle.js"></script>
      <script>
        includeJS("' . SITE . '/' . self::$path . '/js/script.js");
      </script> 
    ';
    }

    /**
     * Выводит страницу настроек плагина в админке
     */
    static function pageSettingsPlugin()
    {
        $lang = self::$lang;
        $pluginName = self::$pluginName;

        $sliders = array();
        $res = DB::query("
      SELECT * 
      FROM `" . PREFIX . self::$pluginName . "` 
            ");
        while ($row = DB::fetchAssoc($res)) {
            $sliders[] = $row;
        }

        $effects = array('slide', 'cube', 'flip', 'fade', 'coverflow');
        $directions = array('horizontal', 'vertical');
        $swiper_animations = array(
            'bounce', 'flash', 'pulse', 'rubberBand'
        , 'shake', 'headShake', 'swing', 'tada'
        , 'wobble', 'jello', 'bounceIn', 'bounceInDown'
        , 'bounceInLeft', 'bounceInRight', 'bounceInUp', 'bounceOut'
        , 'bounceOutDown', 'bounceOutLeft', 'bounceOutRight', 'bounceOutUp'
        , 'fadeIn', 'fadeInDown', 'fadeInDownBig', 'fadeInLeft'
        , 'fadeInLeftBig', 'fadeInRight', 'fadeInRightBig', 'fadeInUp'
        , 'fadeInUpBig', 'fadeOut', 'fadeOutDown', 'fadeOutDownBig'
        , 'fadeOutLeft', 'fadeOutLeftBig', 'fadeOutRight', 'fadeOutRightBig'
        , 'fadeOutUp', 'fadeOutUpBig', 'flipInX', 'flipInY'
        , 'flipOutX', 'flipOutY', 'lightSpeedIn', 'lightSpeedOut'
        , 'rotateIn', 'rotateInDownLeft', 'rotateInDownRight', 'rotateInUpLeft'
        , 'rotateInUpRight', 'rotateOut', 'rotateOutDownLeft', 'rotateOutDownRight'
        , 'rotateOutUpLeft', 'rotateOutUpRight', 'hinge', 'jackInTheBox'
        , 'rollIn', 'rollOut', 'zoomIn', 'zoomInDown'
        , 'zoomInLeft', 'zoomInRight', 'zoomInUp', 'zoomOut'
        , 'zoomOutDown', 'zoomOutLeft', 'zoomOutRight', 'zoomOutUp'
        , 'slideInDown', 'slideInLeft', 'slideInRight', 'slideInUp'
        , 'slideOutDown', 'slideOutLeft', 'slideOutRight', 'slideOutUp'
        , 'heartBeat'
        );

        $data = self::$allValue;
        $relation = self::$relation;
        $event = ''; //TODO
        foreach ($relation as $option => $arr) {
            $hide = '';
            foreach ($arr as $name) {
                $hide .= "
                if ($(this).prop('checked') == true) {
                    $('.admin-center [name=".$name."]').parents('.js-slider-option').slideDown();
                } else {
                    $('.admin-center [name=".$name."]').parents('.js-slider-option').slideUp();
                }
                ";
            }
            $event .= "
            $('.admin-center').on('click', '.section-".self::$pluginName." [name=".$option."]', function () {
                ".$hide."
            });
            ";
        }
        echo '
        <script>
        var main = ' . json_encode($data) . ';
        var relation = ' . json_encode($relation) . ';
        </script>
        ';

        $positions = array('center', 'right', 'left'); //mg-slide_position_
        $paginations = array('progressbar', 'bullets');


        self::preparePageSettings();

        $option = MG::getSetting(self::$pluginName);
        $option = stripslashes($option);
        $options = unserialize($option);

        function addTooltip($value, $flow = 'right', $type = 'default') {
            $html = '';
            switch ($type) {
                case 'default':
                    $html = '
                    <span tooltip="'.$value.'"
                        flow="'.$flow.'">
                    <i class="fa fa-question-circle tip"
                        aria-hidden="true"></i>
                    </span>
                    ';
                    break;

                default:
                    $html = '';
                    break;
            }
            echo $html;
        }

        include('pageplugin.php');
    }


    /**
     * Обработчик шотркода вида [mg-slider]
     * выполняется когда при генерации страницы встречается [mg-slider]
     */
    static function slider($arg)
    {
        $id = $arg['id'];

        $slider = DB::fetchAssoc(DB::query("
            SELECT `slides`, `options` 
            FROM `" . PREFIX . self::$pluginName . "` WHERE id = " . DB::quote($id)
        ));

        $shortcode = 'true';

        if( !empty($slider['options']) ) {
            $slider['options'] = unserialize(stripslashes($slider['options']));
        } else {
            $slider['options'] = '';
        }
        if ( !empty($slider['slides']) ) {
            $slider['slides'] = unserialize(stripslashes($slider['slides']));
        } else {
            $slider['slides'] = '';
        }
        $path = self::$path;

        ob_start();
        include 'views/slider.php';
        $html = ob_get_clean();
        $currentVersion=(int)preg_replace('/[^0-9]/', '', VER);
        if(EDITION == 'saas' || $currentVersion > 1072){
            //вырезаем все сформированные стили слайдов и объединяем их в одну строку, чтобы в будущем подставить перед серкцией </head>
            preg_match_all('#<style>(.*?)</style>#is', $html, $matches, PREG_SET_ORDER);
            $html = preg_replace('#<style>(.*?)</style>#is', '', $html);
            $sliderStyle="<style>";
            foreach($matches as $m){
                $sliderStyle.=$m[1];
            }
            $sliderStyle.="</style>";
        }

        self::$styleSliders[] = $sliderStyle;
        return $html;
    }

    public static function addSlider($name_slider, $options, $slides)
    {
        $sql = 'INSERT INTO `' . PREFIX . self::$pluginName . '` VALUES (NULL, '. DB::quote($name_slider).', ' . DB::quote($slides) . ', ' . DB::quote($options) . ', 0)';
        $result = DB::query($sql);

        //Костыль
        $id = DB::insertId();
        if (empty($name_slider)) {
            $name_slider = 'Безымянный слайдер '.$id;
        }
        $sql = 'UPDATE `' . PREFIX . self::$pluginName . '` SET `name_slider` = ' . DB::quote($name_slider).' WHERE `id` = ' . DB::quote($id);
        $result = DB::query($sql);

        return $id;
    }

    public static function updateSlider($id, $name_slider, $options, $slides)
    {
        if (empty($name_slider)) {
            $name_slider = 'Слайдер ' . $id;
        }
        $sql = 'UPDATE `' . PREFIX . self::$pluginName . '` SET `name_slider` = ' . DB::quote($name_slider) . ', `options` = ' . DB::quote($options) . ', `slides` = ' . DB::quote($slides) . ', `invisible` = 0 WHERE `id` = ' . DB::quote($id);
        $result = DB::query($sql);
        return true;
    }

    public static function deleteSlider($id)
    {
        $sql = 'DELETE FROM `' . PREFIX . self::$pluginName . '` WHERE `id` = ' . DB::quote($id);
        $result = DB::query($sql);
        if (!$result) {
            return false;
        }

        $sql = 'SELECT `id` FROM `' . PREFIX . self::$pluginName . '` LIMIT 0,1';
        $result = DB::fetchAssoc(DB::query($sql));

        return true;
    }

    public static function getGoogleFonts($cache = true)
    {
        if(method_exists('MG','getGoogleFonts')){
          return MG::getGoogleFonts();    
        }

        if ($cache && $result = Storage::get('google-fonts')) {
            return json_decode($result, true);
        }

        $ch = curl_init('https://www.googleapis.com/webfonts/v1/webfonts?key=' . self::$APIGoogleFont . '&sort=popularity');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if ($errno = curl_errno($ch)) {
            echo $errno;
            curl_close($ch);
            return false;
        }
        curl_close($ch);

        $result = json_decode($result, true);
        if (!isset($result['error'])) {
            $result = $result['items'];
        } else {
            $list = MG::getGoogleFonts();
            Storage::save('google-fonts', json_encode($list));
            return $list;
        }

        $list = array();
        foreach ($result as $key => $font) {
            if (in_array("cyrillic", $font['subsets']) == -1
                || in_array("cyrillic-ext", $font['subsets']) == -1) {
                $list[] = $font['family'];
            }
        }

        Storage::save('google-fonts', json_encode($list));
        return $list;
    }

}
