<?php $docRoot = URL::getDocumentRoot(); ?>

<link href="<?php echo SITE . '/mg-admin/design/css/generalStyles.css?rev=' . filemtime($docRoot . '/mg-admin/design/css/generalStyles.css')?>" rel="stylesheet"></link>
<link rel="stylesheet" href="<?php echo SITE . '/mg-admin/design/css/modal-templates.css?rev=' . filemtime($docRoot . '/mg-admin/design/css/modal-templates.css') ?>">
<?php
// Необходимая информация о шаблонах
include_once ADMIN_DIR . '/section/views/layout/templates-info.php';
?>

<div class="first-templates-popup _show" id="first-templates-popup">
    <div class="first-templates-popup__body">
        <div class="first-templates-popup__content">
            <div class="templates-cards">
            <?php /* <button class="first-templates-popup__close js-first-templates-popup__close" data-vis-click="startChoiceClose">
                    <span class="visually-hidden" data-vis-click="startChoiceClose">Закрыть</span>
                </button> */ ?>
                <div class="first-templates-popup__container" style="width: 100%;">
                    <?php  include_once ADMIN_DIR . '/section/views/layout/quiz.php'; ?>
                    
                    <?php /* <div class="first-templates-popup__title">
                        Создайте свой уникальный внешний вид сайта
                    </div>
                    <div class="templates-cards__constructor <?php echo $tempSelected === 'mg-cloud-air' ? '_active' : '' ?>">
                        <picture class="templates-cards__constructor-image">
                            <source srcset="<?php echo SITE.'/'.'mg-templates/mg-cloud-air/images/mg-preview-desc.jpg' ?>, <?php echo SITE.'/'.'mg-templates/mg-cloud-air/images/mg-preview-desc_2x.jpg' ?> 2x">
                            <img loading="lazy" width="210" height="210"
                                src="<?php echo SITE . '/mg-admin/design/images/1x1.png' ?>"
                                alt="Изображение главной страницы шаблона-конструктора">
                        </picture>
                    </div>
                    <button type="button" class="first-templates-popup__button js-first-templates-popup__close" data-vis-click="startChoiceConstructor">
                        Начать работу с конструктором
                    </button>
                    */ ?>
                </div>
                <div class="first-templates-popup__wrapper js-first-templates-popup-wrapper">

                <div class="first-templates-popup__title">
                    Рекомендуем готовые шаблоны
                </div>
                <div class="first-templates-popup__subtitle js-first-templates-popup__subtitle">
                Спасибо за ответы на все вопросы! 
                <br/>Наш робот уже подобрал специально для вас лучшие шаблоны, на них будет проще и легче работать.
                <br/>Осталось только выбрать и установить!
                </div>
                <div class="templates-cards__ready"  style="max-width: 1300px!important;">
                    <?php
                    $count = 0;
 
                    $recomendTemplate = [
                        'mg-friend' => 'Товары для животных',
                        'mg-leader' => 'Автозапчасти',
                        'mg-rose' => 'Цветы',
                        'mg-storm' => 'Электроника',
                        'mg-adriana' => 'Растения',
                        'mg-gears' => 'Строительный',
                        'mg-oasis' => 'Бытовой',
                        'mg-air' => 'Одежда и обувь',
                        'mg-elegant' => 'Стильные товары',
                        'mg-beauty' => 'Красота и здоровье',
                        'mg-william' => 'Мебель',
                        'mg-honey' => 'Детский',
                        'mg-tasty' => 'Еда',
                        /* 'moguta-standard' => 'Стандартный',*/
                        /*'mg-platos' => 'Спорт',*/
                       /* 'mg-sunshine' => 'Спорт',
                        'mg-jasmine' => '18+',*/
                    ];

                    foreach ($templates as $template) :
                        $count += 1;
                        $tempName = $template['foldername'];

                        if(!array_key_exists($tempName,$recomendTemplate)){
                            continue;
                        }

                        if (!$tempName || $tempName === 'mg-cloud-air'  || $tempName === 'p55-universal' || $tempName === 'moguta') continue;  ?>
                        
                        <div class="templates-cards__card templates-cards__card_big js-first-templates-popup__card <?php echo $tempSelected === $tempName ? '_active' : '' ?>" data-vis-click="startChoiceDemo">
                            <div class="templates-cards__container" data-vis-click="startChoiceDemo">
                                <span class="templates-cards__card-title templates-cards__card-title_description" data-vis-click="startChoiceDemo">
                                    <?php  // echo !empty($tempsInfo[$tempName]['description']) ? $tempsInfo[$tempName]['description'] : 'Шаблон № ' . $count ?>
                                    <?php echo $recomendTemplate[$tempName]; ?>
                                 
                                </span>
                                <div class="templates-cards__card-image" data-vis-click="startChoiceDemo">
                                    <img loading="lazy" width="210" height="210" data-vis-click="startChoiceDemo"
                                        class="<?php echo $template['image-preview'] !== '' && $tempName !== 'mg-jasmine' ? '_slide' : '' ?>"
                                        src="<?php echo SITE.'/mg-templates/'.$tempName.'/images/template_prev_x1.jpg' ?>"
                                        srcset="<?php echo SITE.'/mg-templates/'.$tempName.'/images/template_prev_x2.jpg' ?> 2x"
                                        alt="Изображение шаблона <?php echo $tempName ?>">
                                </div>
             
                            </div>
                            <div class="templates-cards__card-buttons">
                                <?php if ($tempsInfo[$tempName]['link']) : ?>
                                    <button type="button" class="templates-cards__card-demo js-first-templates-popup__demo" data-temp-name="<?php echo $tempName ?>" data-src-demo="<?php echo $tempsInfo[$tempName]['link'] ?>" data-vis-click="startChoiceDemo">Демо</button>
                                <?php endif; ?>
                                <button type="button" class="templates-cards__card-button js-first-templates-popup__activation" data-temp-name="<?php echo $tempName ?>" data-color="<?php echo $tempsInfo[$tempName]['color'] ?>" data-vis-click="startChoiceTemplate">
                                    <?php echo $tempSelected === $tempName ? 'Установлен' : 'Установить' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
                <style>
                    .first-templates-popup__button_more:hover {
                        background: #03be81 !important;
                        color: #FFF !important;
                        transition: all 0.3s ease-in-out;
                    }
                </style>
                <div class="first-templates-popup__title" style="margin-top:30px;">
                    Ничего не подошло?
                </div>
                <div class="first-templates-popup__subtitle js-first-templates-popup__subtitle">
                    Посмотрите другие готовые шаблоны из нашей коллекции.
                </div>
                <button style="height:80px; background-color: #fefefe; margin-top:10px; margin-bottom: 100px; color: #3b6ebb;" type="button" class="first-templates-popup__button first-templates-popup__button_more" onclick="$('.js-more-templates').show(); $(this).hide();" data-vis-click="showMoreTemplatesToStart">
                    Хочу посмотреть ещё!
                </button>

                <div class="templates-cards__ready js-more-templates" style="display:none; max-width: 1300px!important;">
                    <?php
                    foreach ($templates as $template) :
                        $count += 1;
                        $tempName = $template['foldername'];

                        if(array_key_exists($tempName,$recomendTemplate)){
                            continue;
                        }

                        if (!$tempName || $tempName === 'mg-cloud-air' || $tempName === 'p55-universal' || $tempName === 'moguta') continue; 
                        

                        ?>
                        <div class="templates-cards__card js-first-templates-popup__card <?php echo $tempSelected === $tempName ? '_active' : '' ?>">
                            <div class="templates-cards__container">
                                <span class="templates-cards__card-title templates-cards__card-title_description">
                                    <?php echo !empty($tempsInfo[$tempName]['description']) ? $tempsInfo[$tempName]['description'] : 'Шаблон № ' . $count ?>    
                                </span>
                                <div class="templates-cards__card-image">
                                    <img loading="lazy" width="210" height="210"
                                        class="<?php echo $template['image-preview'] !== '' && $tempName !== 'mg-jasmine' ? '_slide' : '' ?>"
                                        src="<?php echo $template['image-preview'] ?>"
                                        srcset="<?php echo $template['image-preview_2x'] ?> 2x"
                                        alt="Изображение шаблона <?php echo $tempName ?>">
                                </div>
             
                            </div>
                            <div class="templates-cards__card-buttons">
                                <?php if ($tempsInfo[$tempName]['link']) : ?>
                                    <button type="button" class="templates-cards__card-demo js-first-templates-popup__demo" data-temp-name="<?php echo $tempName ?>" data-src-demo="<?php echo $tempsInfo[$tempName]['link'] ?>" data-vis-click="startChoiceDemo">Демо</button>
                                <?php endif; ?>
                                <button type="button" class="templates-cards__card-button js-first-templates-popup__activation" data-temp-name="<?php echo $tempName ?>" data-color="<?php echo $tempsInfo[$tempName]['color'] ?>" data-vis-click="startChoiceTemplate">
                                    <?php echo $tempSelected === $tempName ? 'Установлен' : 'Установить' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                    <div class="first-templates-popup__title"  style="margin-top:30px;">
                    Универсальный шаблон
                    </div>
                    <div style="clear:both;"></div>
                    <div class="first-templates-popup__subtitle js-first-templates-popup__subtitle">
                        Подойдёт опытным пользователям, желающим самостоятельно запрограммировать структуру шаблона и создавать собственные блоки контента.
                    </div>
              
          

                    <div class="templates-cards__constructor <?php echo $tempSelected === 'mg-cloud-air' ? '_active' : '' ?>">
                        <picture class="templates-cards__constructor-image">
                            <source srcset="<?php echo SITE.'/'.'mg-templates/mg-cloud-air/images/mg-preview-desc.jpg' ?>, <?php echo SITE.'/'.'mg-templates/mg-cloud-air/images/mg-preview-desc_2x.jpg' ?> 2x">
                            <img loading="lazy" width="210" height="210"
                                src="<?php echo SITE . '/mg-admin/design/images/no-image.png' ?>"
                                alt="Изображение главной страницы шаблона-конструктора">
                        </picture>
                    </div>

                    <button style="height:80px; margin-bottom: 100px;" type="button" class="first-templates-popup__button js-first-templates-popup__close" data-vis-click="startChoiceConstructor">
                        Использовать универсальный шаблон
                    </button> 

             

             
            </div>
            </div>
            
            <div class="templates-iframe js-first-templates-iframe">
                <div class="templates-iframe__header">
                    <div class="templates-iframe__container">
                        <div class="templates-iframe__temp-name js-first-templates-iframe__name"></div>
                        <div class="templates-iframe__text">
                            Демонстрационный сайт<span>. Установите этот шаблон, чтобы ваш сайт выглядел также.</span>
                        </div>
                    </div>
                    <button class="templates-iframe__close js-firsttemplates-iframe__close">
                        <span class="first-templates-popup__close_text"> Закрыть </span>
                        <span class="first-templates-popup__close templates-iframe__cross"></span>
                    </button>
                </div>
                <iframe src="" frameborder="0" class="js-first-templates-popup__iframe"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    includeJS("<?php echo SITE ?>/mg-core/script/admin/first-modal-templates.js");
</script>