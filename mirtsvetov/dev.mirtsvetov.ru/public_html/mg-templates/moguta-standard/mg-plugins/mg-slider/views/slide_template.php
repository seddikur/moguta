<?php
// Не стоит делать здесь автоформатирование –
// именно такие отступы нужны, чтобы всё это аккуратно выглядело в публичке
//
// Для отладки:
// console_log($slide);
?>
<div class="js-slide-item swiper-slide mg-slider__slide mg-slide <?php echo !empty($slide['position_content'])?'mg-slide_position_'.$slide['position_content']:''; ?> <?php echo !empty($slide['position_content_vertical'])?'mg-slide_position_'.$slide['position_content_vertical']:''; ?>"
     <?php echo $attrs ?>>
    <div class="mg-slide__outer" style="<?php echo (!empty($slider['options']['slider_max_width'])) ? 'max-width:'.$slider['options']['slider_max_width'].'px;' : ''; ?>">
        <!-- Контейнер с контентом слайда -->
        <?php if(!empty($slide['font'])):?>
        <?php $family = str_replace(' ', '+', $slide['font']); ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=<?php echo $family?>&subset=cyrillic">
        <?php endif; ?>

      <?php if (
        !empty($slide['img_path']) ||
        !empty($slide['md_img_path']) ||
        !empty($slide['t_img_path']) ||
        !empty($slide['m_img_path'])
      ):
        ?>
        <picture class="mg-slide__image">
            <?php if (!empty($slide['m_img_path'])): ?>
            <source media="(max-width:767px)" srcset="<?php echo SITE . '/' . urldecode($slide['m_img_path']); ?>">
            <?php endif; ?>

            <?php if (!empty($slide['t_img_path'])): ?>
            <source media="(max-width: 992px) and (min-width: 768px)" srcset="<?php echo SITE . '/' . urldecode($slide['t_img_path']); ?>">
            <?php endif; ?>

            <?php if (!empty($slide['md_img_path'])): ?>
            <source media="(max-width: 1200px) and (min-width: 992px)" srcset="<?php echo SITE . '/' . urldecode($slide['md_img_path']); ?>">
            <?php endif; ?>

            <img src="<?php echo SITE . '/' . urldecode($slide['img_path']); ?>"
                 width="1000"
                 height="600"
                 title="<?php echo $slide['title']; ?>"
                 alt="<?php echo $slide['title']; ?>">
        </picture>
      <?php endif; ?>

        <style>
<?php if(!empty($slide['font'])):?>
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner,
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner * {
            font-family: <?php echo $slide['font']; ?>, sans-serif !important;
        }
<?php endif; ?>

        /* Фон */
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
            justify-content: space-between;
            /* Цвет фона */
            <?php echo !empty($slide['color']) ? 'background-color: '.$slide['color'].';' : '' ?>
        }

        /* Заголовок */
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__title {
            /* Размер шрифта */
            font-size: <?php $title_font_size = (!empty( $slide['title_font_size'] )? $slide['title_font_size'].'px' : '60px' ); echo $title_font_size; ?> !important;
            /* Цвет */
            color: <?php echo (!empty( $slide['title_font_color'] )? $slide['title_font_color'] : '#fff' ) ?> !important;
        }

        /* Текст */
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__text-content {
            /* Размер шрифта */
            font-size: <?php $text_font_size = (!empty( $slide['text_font_size'] )? $slide['text_font_size'].'px' : '18px' ); echo $text_font_size; ?> !important;
            /* Цвет */
            color: <?php echo (!empty( $slide['content_font_color'] )? $slide['content_font_color'] : '#fff' ) ?> !important;
        }

        /* Кнопка */
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__btn {
            /* Фон кнопки */
            background-color: <?php echo (!empty( $slide['button_background_color_template'] ) && $slide['button_background_color_template'] == 'true' ) ? '#'.MG::getSetting('colorScheme') : (!empty( $slide['button_background_color'] ) ? $slide['button_background_color'] : '#f05a4f' ) ?> !important;
            /* Цвет шрифта кнопки  */
            color: <?php echo (!empty( $slide['button_font_color'] )? $slide['button_font_color'] : '#fff' ) ?> !important;
            /* Размер шрифта кнопки */
            font-size: <?php $button_font_size = (!empty( $slide['button_font_size'] )? $slide['button_font_size'].'px' : '18px' ); echo $button_font_size; ?> !important;
            <?php echo (isset($slide['button_border_radius']) && $slide['button_border_radius'] != "")?'border-radius:'.$slide['button_border_radius'].'px !important;' : ''?>
        }

        /* Маленькие десктопы */
        @media (max-width: 1200px) {
<?php if (!empty($slide['md_title_font_size'])): ?>
            /* Заголовок */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__title {
                /* Размер шрифта */
                font-size: <?php echo $slide['md_title_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['md_text_font_size'])): ?>
            /* Текст */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__text-content {
                /* Размер шрифта */
                font-size: <?php echo $slide['md_text_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['md_button_font_size'])): ?>
            /* Кнопка */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__btn {
                /* Размер шрифта кнопки */
                font-size: <?php echo $slide['md_button_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['md_color'])) : ?>
            /* Цвет фона */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
                background-color: <?php echo $slide['md_color'] ?> !important;
            }
<?php endif; ?>
        }

        /* Планшеты */
        @media (max-width: 992px) {
<?php if (!empty($slide['t_title_font_size'])): ?>
            /* Заголовок */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__title {
                /* Размер шрифта */
                font-size: <?php echo $slide['t_title_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['t_text_font_size'])): ?>
            /* Текст */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__text-content {
                /* Размер шрифта */
                font-size: <?php echo $slide['t_text_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['t_button_font_size'])): ?>
            /* Кнопка */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__btn {
                /* Размер шрифта кнопки */
                font-size: <?php echo $slide['t_button_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['t_color'])) : ?>
            /* Цвет фона */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
                background-color: <?php echo $slide['t_color'] ?> !important;
            }
<?php endif; ?>
        }

        /* Телефоны */
        @media (max-width: 767px) {
<?php if (!empty($slide['m_title_font_size'])): ?>
            /* Заголовок */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__title {
                /* Размер шрифта */
                font-size: <?php echo $slide['m_title_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['m_text_font_size'])): ?>
            /* Текст */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__text-content {
                /* Размер шрифта */
                font-size: <?php echo $slide['m_text_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['m_button_font_size'])): ?>
            /* Кнопка */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__btn {
                /* Размер шрифта кнопки */
                font-size: <?php echo $slide['m_button_font_size'] ?>px !important;
            }
<?php endif; ?>

<?php if (!empty($slide['m_color'])) : ?>
            /* Цвет фона */
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
                background-color: <?php echo $slide['m_color'] ?> !important;
            }
<?php endif; ?>
        }

        /* Цвет фона кнопки при наведении и фокусе */
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__btn:focus,
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__btn:hover {
            background-color: <?php echo (!empty( $slide['button_background_color_template'] ) && $slide['button_background_color_template'] == 'true' )  ? '#'. dechex((int)hexdec(MG::getSetting('colorScheme')) - 50) : (!empty( $slide['button_hover_color'] ) ? $slide['button_hover_color'] : '#ff493c' )?> !important;
        }

        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"]:after {
            content: '';
            background-color: <?php echo !empty($slide['overlay_color']) ? $slide['overlay_color'] : 'transparent'; ?>;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }
<?php
// Переназначаем цвет подложки
if (!empty($slide['md_overlay_color'])): ?>
        @media (max-width: 1200px) and (min-width: 992px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"]:after {
                background-color: <?php echo $slide['md_overlay_color']; ?>;
            }
        }
<?php endif; ?>
<?php if (!empty($slide['t_overlay_color'])): ?>
        @media (max-width: 992px) and (min-width: 768px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"]:after {
                background-color: <?php echo $slide['t_overlay_color']; ?>;
            }
        }
<?php endif; ?>
<?php if (!empty($slide['m_overlay_color'])): ?>
        @media (max-width: 767px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"]:after {
                background-color: <?php echo $slide['m_overlay_color']; ?>;
            }
        }
<?php endif; ?>
<?php if (!empty($slide['background_size'])): ?>
        <?php if ($slide['background_size'] !== 'original'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image img {
              width: 100%;
        }
<?php endif; ?>
<?php if ($slide['background_size'] === 'original'): ?>
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image img {
                width: auto;
                height: auto;
            }
<?php endif; ?>

<?php if ($slide['background_size'] === 'cover'): ?>
         <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
             object-fit: <?php echo $slide['background_size'] ?>;
         }
<?php endif; ?>
<?php if ($slide['background_size'] === 'contain'): ?>
         <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
             object-fit: fill;
         }
<?php endif; ?>
        <?php endif; ?>

<?php if (!empty($slide['md_background_size'])): ?>
        @media (max-width: 1200px) and (min-width: 992px) {
<?php if ($slide['md_background_size'] === 'cover'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
              object-fit: <?php echo $slide['md_background_size'] ?>;
            }
<?php endif; ?>
<?php if ($slide['md_background_size'] === 'contain'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
              object-fit: fill;
            }
<?php endif; ?>
<?php if ($slide['md_background_size'] === 'original'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                object-fit: contain;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php if (!empty($slide['t_background_size'])): ?>
        @media (max-width: 992px) and (min-width: 768px) {
<?php if ($slide['t_background_size'] === 'cover'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                object-fit: <?php echo $slide['t_background_size'] ?>;
            }
<?php endif; ?>
<?php if ($slide['t_background_size'] === 'contain'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                object-fit: fill;
            }
<?php endif; ?>
<?php if ($slide['t_background_size'] === 'original'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                object-fit: contain;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php if (!empty($slide['m_background_size'])): ?>
        @media (max-width: 767px) {
<?php if ($slide['m_background_size'] === 'cover'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                object-fit: <?php echo $slide['m_background_size'] ?>;
            }
<?php endif; ?>
<?php if ($slide['m_background_size'] === 'contain'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                object-fit: fill;
            }
<?php endif; ?>
<?php if ($slide['m_background_size'] === 'original'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                object-fit: contain;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php if (!empty($slide['position_x']) || !empty($slide['position_y'])): ?>
       <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
       <?php echo ($slide['position_x'] === 'center') ? 'justify-content: center; object-position: center;' : ''; ?>
       <?php echo ($slide['position_x'] === 'right') ? 'justify-content: flex-end; object-position: right;' : ''; ?>
       <?php echo ($slide['position_x'] === 'left') ? 'justify-content: flex-start; object-position: left;' : ''; ?>
       <?php echo ($slide['position_y'] === 'top') ? 'align-items: flex-start;' : ''; ?>
       <?php echo ($slide['position_y'] === 'bottom') ? 'align-items: flex-end;' : ''; ?>
    }
<?php endif; ?>

<?php
if (!empty($slide['md_position_x']) || !empty($slide['md_position_y'])) : ?>
        @media (max-width: 1200px) and (min-width: 992px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
            <?php echo ($slide['md_position_x'] === 'center') ? 'justify-content: center; object-position: center;' : ''; ?>
            <?php echo ($slide['md_position_x'] === 'right') ? 'justify-content: flex-end; object-position: right;' : ''; ?>
            <?php echo ($slide['md_position_x'] === 'left') ? 'justify-content: flex-start; object-position: left;' : ''; ?>
            <?php echo ($slide['md_position_y'] === 'top') ? 'align-items: flex-start;' : ''; ?>
            <?php echo ($slide['md_position_y'] === 'bottom') ? 'align-items: flex-end;' : ''; ?>
            }
        }
<?php endif; ?>

<?php if (!empty($slide['t_position_x']) || !empty($slide['t_position_y'])): ?>
        @media (max-width: 992px) and (min-width: 768px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
            <?php echo ($slide['t_position_x'] === 'center') ? 'justify-content: center; object-position: center;' : ''; ?>
            <?php echo ($slide['t_position_x'] === 'right') ? 'justify-content: flex-end; object-position: right;' : ''; ?>
            <?php echo ($slide['t_position_x'] === 'left') ? 'justify-content: flex-start; object-position: left;' : ''; ?>
            <?php echo ($slide['t_position_y'] === 'top') ? 'align-items: flex-start;' : ''; ?>
            <?php echo ($slide['t_position_y'] === 'bottom') ? 'align-items: flex-end;' : ''; ?>
            }
        }
<?php endif; ?>

<?php if (!empty($slide['m_position_x']) || !empty($slide['m_position_y'])): ?>
        @media (max-width: 767px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__image {
                <?php echo ($slide['m_position_x'] === 'center') ? 'justify-content: center; object-position: center;' : ''; ?>
                <?php echo ($slide['m_position_x'] === 'right') ? 'justify-content: flex-end; object-position: right;' : ''; ?>
                <?php echo ($slide['m_position_x'] === 'left') ? 'justify-content: flex-start; object-position: left;' : ''; ?>
                <?php echo ($slide['m_position_y'] === 'top') ? 'align-items: flex-start;' : ''; ?>
                <?php echo ($slide['m_position_y'] === 'bottom') ? 'align-items: flex-end;' : ''; ?>
            }
        }
<?php endif; ?>

<?php
// Отступ контента снизу/сверху
// Десктопы
$isTopOffset = !empty($slide['position_offset_top']);
$isBottomOffset = !empty($slide['position_offset_bottom']);
// Маленькие десктопы
$isTopOffset_md = !empty($slide['md_position_offset_top']);
$isBottomOffset_md = !empty($slide['md_position_offset_bottom']);
// Планшеты
$isTopOffset_t = !empty($slide['t_position_offset_top']);
$isBottomOffset_t = !empty($slide['t_position_offset_bottom']);
// Телефон
$isTopOffset_m = !empty($slide['m_position_offset_top']);
$isBottomOffset_m = !empty($slide['m_position_offset_bottom']);

if ($isTopOffset || $isBottomOffset): ?>
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            <?php echo $isTopOffset ? 'margin-top: ' . $slide['position_offset_top'] . 'px;' : ''; ?>
            <?php echo $isBottomOffset ? 'margin-bottom: ' . $slide['position_offset_bottom'] . 'px;' : ''; ?>
        }
<?php endif; ?>

<?php if ($isTopOffset_md || $isBottomOffset_md): ?>
        @media (max-width: 1200px) and (min-width: 992px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                <?php echo $isTopOffset_md ? 'margin-top: ' . $slide['md_position_offset_top'] . 'px;' : ''; ?>
                <?php echo $isBottomOffset_md ? 'margin-bottom: ' . $slide['md_position_offset_bottom'] . 'px;' : ''; ?>
            }
        }
<?php endif; ?>

<?php if ($isTopOffset_t || $isBottomOffset_t): ?>
        @media (max-width: 992px) and (min-width: 768px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                <?php echo $isTopOffset_t ? 'margin-top: ' . $slide['t_position_offset_top'] . 'px;' : ''; ?>
                <?php echo $isBottomOffset_t ? 'margin-bottom: ' . $slide['t_position_offset_bottom'] . 'px;' : ''; ?>
            }
        }
<?php endif; ?>

<?php if ($isTopOffset_m || $isBottomOffset_m): ?>
        @media (max-width: 767px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                <?php echo $isTopOffset_m ? 'margin-top: ' . $slide['m_position_offset_top'] . 'px;' : ''; ?>
                <?php echo $isBottomOffset_m ? 'margin-bottom: ' . $slide['m_position_offset_bottom'] . 'px;' : ''; ?>
            }
        }
<?php endif; ?>

<?php 
    // Отступ контента от центра
    // Декстопы
    $isCenterOffset = $slide['position_offset_center'];
    // Маленькие десктопы
    $isCenterOffset_md = $slide['md_position_offset_center'];
    // Планшеты
    $isCenterOffset_t = $slide['t_position_offset_center'];
    // Телефон
    $isCenterOffset_m = $slide['m_position_offset_center'];
    // Позиционирование контента
    // Декстопы
    $positionX = $slide['position_content'] === 'center';
    // Маленькие декстопы
    $positionX_md = $slide['md_position_content'] === 'center';
    // Планшеты
    $positionX_t = $slide['md_position_content'] === 'center';
    // Телефоны
    $positionX_m = $slide['md_position_content'] === 'center';
?>

<?php if (!empty($isCenterOffset) && $positionX && $isCenterOffset < 0): ?>
    <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
        <?php echo $isCenterOffset ? 'left: ' . ( 50 + $slide['position_offset_center']/2) . '%;' : ''; ?>
        text-align: left;
        align-items: flex-start;
        transform: translateX(<?php echo -(50 + $slide['position_offset_center']/2) . '%' ;?>);
    }
<?php endif;?>

<?php if (!empty($isCenterOffset) && $positionX && $isCenterOffset > 0): ?>
    <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
        <?php echo $isCenterOffset ? 'left: ' . ( 50 + $slide['position_offset_center']/2) . '%;' : ''; ?>
        text-align: right !important;
        align-items: flex-end !important;
        transform: translateX(<?php echo -(50 + $slide['position_offset_center']/2) . '%' ;?>);
    }
<?php endif;?>

<?php if (!empty($isCenterOffset_md) && $positionX_md && $isCenterOffset_md < 0): ?>
    @media (max-width: 1200px) and (min-width: 992px) {
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            <?php echo $isCenterOffset_md ? 'left: ' . ( 50 + $slide['md_position_offset_center']/2) . '% !important;' : ''; ?>
            text-align: left !important;
            align-items: flex-start !important;
            transform: translateX(<?php echo -(50 + $slide['md_position_offset_center']/2) . '%' ;?>) !important;
        }
    }
<?php endif;?>

<?php if (!empty($isCenterOffset_md) && $positionX_md && $isCenterOffset_md > 0): ?>
    @media (max-width: 1200px) and (min-width: 992px) {
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            <?php echo $isCenterOffset_md ? 'left: ' . ( 50 + $slide['md_position_offset_center']/2) . '% !important;' : ''; ?>
            text-align: right !important;
            align-items: flex-end !important;
            transform: translateX(<?php echo -(50 + $slide['md_position_offset_center']/2) . '%' ;?>) !important;
        }
    }
<?php endif;?>

<?php if (!empty($isCenterOffset_t) && $positionX_t && $isCenterOffset_t < 0): ?>
    @media (max-width: 992px) and (min-width: 768px) {
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            <?php echo $isCenterOffset_t ? 'left: ' . ( 50 + $slide['t_position_offset_center']/2) . '% !important;' : ''; ?>
            text-align: left !important;
            align-items: flex-start !important;
            transform: translateX(<?php echo -(50 + $slide['t_position_offset_center']/2) . '%' ;?>) !important;
        }
    }
<?php endif;?>

<?php if (!empty($isCenterOffset_t) && $positionX_t && $isCenterOffset_t > 0): ?>
    @media (max-width: 992px) and (min-width: 768px) {
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            <?php echo $isCenterOffset_t ? 'left: ' . ( 50 + $slide['t_position_offset_center']/2) . '% !important;' : ''; ?>
            text-align: right !important;
            align-items: flex-end !important;
            transform: translateX(<?php echo -(50 + $slide['t_position_offset_center']/2) . '%' ;?>) !important;
        }
    }
<?php endif;?>

<?php if (!empty($isCenterOffset_m) && $positionX_m && $isCenterOffset_m < 0): ?>
    @media (max-width: 767px) {
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            <?php echo $isCenterOffset_m ? 'left: ' . ( 50 + $slide['m_position_offset_center']/2) . '% !important;' : ''; ?>
            text-align: left !important;
            align-items: flex-start !important;
            transform: translateX(<?php echo -(50 + $slide['m_position_offset_center']/2) . '%' ;?>) !important;
        }
    }
<?php endif;?>

<?php if (!empty($isCenterOffset_m) && $positionX_m && $isCenterOffset_m > 0): ?>
    @media (max-width: 767px) {
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            <?php echo $isCenterOffset_m ? 'left: ' . ( 50 + $slide['m_position_offset_center']/2) . '% !important;' : ''; ?>
            text-align: right !important;
            align-items: flex-end !important;
            transform: translateX(<?php echo -(50 + $slide['m_position_offset_center']/2) . '%' ;?>) !important;
        }
    }
<?php endif;?>

<?php
// Переназначаем положение контента по горизонтали
if (!empty($slide['md_position_content'])): ?>
        @media (max-width: 1200px) and (min-width: 992px) {
<?php if ($slide['md_position_content'] === 'left'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: start;
                align-items: flex-start;
                text-align: left;
                left: 0;
                right: auto;
                transform: none;
            }
<?php endif; ?>
<?php if ($slide['md_position_content'] === 'center'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: center;
                align-items: center;
                text-align: center;
                left: 50%;
                transform: translateX(-50%);
                right: auto;
            }
<?php endif; ?>
<?php if ($slide['md_position_content'] === 'right'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: end;
                align-items: flex-end;
                text-align: right;
                left: auto;
                right: 0;
                transform: none;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php if (!empty($slide['t_position_content'])): ?>
        @media (max-width: 992px) and (min-width: 768px) {
<?php if ($slide['t_position_content'] === 'left'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: start;
                align-items: flex-start;
                text-align: left;
                left: 0;
                right: auto;
                transform: none;
            }
<?php endif; ?>
<?php if ($slide['t_position_content'] === 'center'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: center;
                align-items: center;
                text-align: center;
                left: 50%;
                transform: translateX(-50%);
                right: auto;
            }
<?php endif; ?>
<?php if ($slide['t_position_content'] === 'right'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: end;
                align-items: flex-end;
                text-align: right;
                left: auto;
                right: 0;
                transform: none;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php if (!empty($slide['m_position_content'])): ?>
        @media (max-width: 767px) {
<?php if ($slide['m_position_content'] === 'left'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: start;
                align-items: flex-start;
                text-align: left;
                left: 0;
                right: auto;
                transform: none;
            }
<?php endif; ?>
<?php if ($slide['m_position_content'] === 'center'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: center;
                align-items: center;
                text-align: center;
                left: 0;
                right: 0;
                transform: none;
            }
<?php endif; ?>
<?php if ($slide['m_position_content'] === 'right'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                -webkit-box-align: end;
                align-items: flex-end;
                text-align: right;
                left: auto;
                right: 0;
                transform: none;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php
// Переназначаем положение контента по вертикали
if (!empty($slide['md_position_content_vertical'])): ?>
        @media (max-width: 1200px) and (min-width: 992px) {
<?php if ($slide['md_position_content_vertical'] === 'top'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: 0;
                bottom: auto;
            }
<?php endif; ?>
<?php if ($slide['md_position_content_vertical'] === 'middle'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: auto;
                bottom: auto;
            }
<?php endif; ?>
<?php if ($slide['md_position_content_vertical'] === 'bottom'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: auto;
                bottom: 0;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php if (!empty($slide['t_position_content_vertical'])): ?>
        @media (max-width: 992px) and (min-width: 768px) {
<?php if ($slide['t_position_content_vertical'] === 'top'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: 0;
                bottom: auto;
            }
<?php endif; ?>
<?php if ($slide['t_position_content_vertical'] === 'middle'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
               top: auto;
               bottom: auto;
            }
<?php endif; ?>
<?php if ($slide['t_position_content_vertical'] === 'bottom'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: auto;
                bottom: 0;
            }
<?php endif; ?>
        }
<?php endif; ?>

<?php if (!empty($slide['m_position_content_vertical'])): ?>
        @media (max-width: 767px) {
<?php if ($slide['m_position_content_vertical'] === 'top'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: 0;
                bottom: auto;
            }
<?php endif; ?>
<?php if ($slide['m_position_content_vertical'] === 'middle'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: auto;
                bottom: auto;
            }
<?php endif; ?>
<?php if ($slide['m_position_content_vertical'] === 'bottom'): ?>
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
                top: auto;
                bottom: 0;
            }
<?php endif; ?>
        }
<?php endif; ?>
<?php if (!empty($slide['content_max_width'])): ?>
        /* Максимальная ширина контента */
        <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] .mg-slide__inner {
            max-width: <?php echo $slide['content_max_width']?>px;
        }
<?php endif; ?>
        </style>
        <?php
        // Если это не админка, удаляем слайды, которые должны быть скрыты
        if (!MG::isAdmin()): ?>
            <?php
                $isInvisibleOnDesktop = !empty($slide['invisible']) && $slide['invisible'] !== 'false';
                $isInvisibleOnLaptop = !empty($slide['md_invisible']) && $slide['md_invisible'] !== 'false';
                $isInvisibleOnTablet = !empty($slide['t_invisible']) && $slide['t_invisible'] !== 'false';
                $isInvisibleOnMobile = !empty($slide['m_invisible']) && $slide['m_invisible'] !== 'false';
            ?>
        <style>
<?php if ($isInvisibleOnDesktop): ?>
        @media screen and (min-width: 1201px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
                display: none;
            }
        }
<?php endif; ?>
<?php if ($isInvisibleOnLaptop): ?>
        @media screen and (max-width: 1200px) and (min-width: 992px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
                display: none;
            }
        }
<?php endif; ?>
<?php if ($isInvisibleOnTablet): ?>
        @media screen and (max-width: 992px) and (min-width: 768px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
                display: none;
            }
        }
<?php endif; ?>
<?php if ($isInvisibleOnMobile): ?>
        @media screen and (max-width: 767px) {
            <?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"] {
                display: none;
            }
        }
<?php endif; ?>
        </style>

        <script>
        $(document).ready(function() {
            var windowWidth = $(window).width();

<?php if ($isInvisibleOnDesktop): ?>
            if (windowWidth > 1200) removeSlide();
<?php endif; ?>
<?php if ($isInvisibleOnLaptop): ?>
            if (windowWidth <= 1200 && windowWidth > 992) removeSlide();
<?php endif; ?>
<?php if ($isInvisibleOnTablet): ?>
            if (windowWidth <= 992 && windowWidth > 768) removeSlide();
<?php endif; ?>
<?php if ($isInvisibleOnMobile): ?>
            if (windowWidth <= 767) removeSlide();
<?php endif; ?>
        });

        var removeSlide = function () {
            $('<?php echo $sliderClassSelector; ?> [data-slide-id="<?php echo $id ?>"]').each(function () {
                $(this).remove();
            });

            if (!$('<?php echo $sliderClassSelector; ?> .mg-slide').length) {
                $('<?php echo $sliderClassSelector; ?>').remove();
            }
        }
        </script>
<?php endif; ?>

        <?php
        // Атрибуты анимации для всего блока контента (mg-slide__inner)
        $slideInnerAttrs = '';
        $slideInnerAttrs .= ' ' . (!empty($slide['inner_data_swiper_animation']) ? 'data-swiper-animation="' . $slide['inner_data_swiper_animation'] . '"' : '');
        $slideInnerAttrs .= ' ' . (!empty($slide['inner_data_duration']) ? 'data-duration="' . $slide['inner_data_duration'] . 's"' : '');
        $slideInnerAttrs .= ' ' . (!empty($slide['inner_data_delay']) ? 'data-delay="' . $slide['inner_data_delay'] . 's"' : 'data-delay="0s"');
        $slideInnerAttrs .= ' ' . (!empty($slide['inner_data_swiper_out_animation']) ? 'data-swiper-out-animation="' . $slide['inner_data_swiper_out_animation'] . '"' : '');
        $slideInnerAttrs .= ' ' . (!empty($slide['inner_data_out_duration']) ? 'data-out-duration="' . $slide['inner_data_out_duration'] . 's"' : '');
        $slideInnerAttrs = trim($slideInnerAttrs);

        // Тип заголовка
        $titleType = !empty($slide['title_heading']) ? $slide['title_heading'] : '2';

        // Атрибуты анимации для заголовка (mg-slide__title)
        $slideTitleAttrs = '';
        $slideTitleAttrs .= ' ' . (!empty($slide['title_data_swiper_animation']) ? 'data-swiper-animation="' . $slide['title_data_swiper_animation'] . '"' : '');
        $slideTitleAttrs .= ' ' . (!empty($slide['title_data_duration']) ? 'data-duration="' . $slide['title_data_duration'] . 's"' : '');
        $slideTitleAttrs .= ' ' . (!empty($slide['title_data_delay']) ? 'data-delay="' . $slide['title_data_delay'] . 's"' : 'data-delay="0s"');
        $slideTitleAttrs .= ' ' . (!empty($slide['title_data_swiper_out_animation']) ? 'data-swiper-out-animation="' . $slide['title_data_swiper_out_animation'] . '"' : '');
        $slideTitleAttrs .= ' ' . (!empty($slide['title_data_out_duration']) ? 'data-out-duration="' . $slide['title_data_out_duration'] . 's"' : '');
        $slideTitleAttrs = trim($slideTitleAttrs);

        // Атрибуты анимации для текста (mg-slide__text-content)
        $slideTextAttrs = '';
        $slideTextAttrs .= ' ' . (!empty($slide['content_data_swiper_animation']) ? 'data-swiper-animation="' . $slide['content_data_swiper_animation'] . '"' : '');
        $slideTextAttrs .= ' ' . (!empty($slide['content_data_duration']) ? 'data-duration="' . $slide['content_data_duration'] . 's"' : '');
        $slideTextAttrs .= ' ' . (!empty($slide['content_data_delay']) ? 'data-delay="' . $slide['content_data_delay'] . 's"' : 'data-delay="0s"');
        $slideTextAttrs .= ' ' . (!empty($slide['content_data_swiper_out_animation']) ? 'data-swiper-out-animation="' . $slide['content_data_swiper_out_animation'] . '"' : '');
        $slideTextAttrs .= ' ' . (!empty($slide['content_data_out_duration']) ? 'data-out-duration="' . $slide['content_data_out_duration'] . 's"' : '');
        $slideTextAttrs = trim($slideTextAttrs);

        // Атрибуты кнопки (mg-slide__btn)
        $slideImgAttrs = '';
        $slideImgAttrs .= ' ' . (!empty($slide['button_data_swiper_animation']) ? 'data-swiper-animation="' . $slide['button_data_swiper_animation'] . '"' : '');
        $slideImgAttrs .= ' ' . (!empty($slide['button_data_duration']) ? 'data-duration="' . $slide['button_data_duration'] . 's"' : '');
        $slideImgAttrs .= ' ' . (!empty($slide['button_data_delay']) ? 'data-delay="' . $slide['button_data_delay'] . 's"' : 'data-delay="0s"');
        $slideImgAttrs .= ' ' . (!empty($slide['button_data_swiper_out_animation']) ? 'data-swiper-out-animation="' . $slide['button_data_swiper_out_animation'] . '"' : '');
        $slideImgAttrs .= ' ' . (!empty($slide['button_data_out_duration']) ? 'data-out-duration="' . $slide['button_data_out_duration'] . 's"' : '');
        $slideImgAttrs = trim($slideImgAttrs);
        ?>

        <!-- Контент слайда -->
        <div class="mg-slide__inner" <?php echo $slideInnerAttrs ?>>
            <!--  Заголовок  -->
            <h<?php echo $titleType; ?> class="mg-slide__title <?php echo empty($slide['title_data_swiper_animation']) ? 'not-animated' : '' ?>" <?php echo $slideTitleAttrs; ?>>
                <?php echo $slide['title']; ?>
            </h<?php echo $titleType ?>>
            <!--  Текст слайда  -->
            <p class="mg-slide__text-content <?php echo empty($slide['content_data_swiper_animation']) ? 'not-animated' : '' ?>" <?php echo $slideTextAttrs; ?>>
                <?php echo $slide['content']; ?>
            </p>
            <!--  Кнопка  -->
            <?php if (!empty($slide['button'])): ?>
            <a href="<?php echo (!empty($slide['button_href']) && isset($shortcode)) ? $slide['button_href'] : "javascript:void(0);" ?>" <?php echo $slideImgAttrs; ?>
               class="mg-slide__btn js-animate-btn <?php echo empty($slide['button_data_swiper_animation']) ? 'not-animated' : '' ?> <?php echo !empty($slide['button_selector']) ? $slide['button_selector'] : ''; ?>">
                <?php echo $slide['button']; ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
