<div <?php echo $attrs ?> class="swiper-slide mg-slider__slide mg-slide mg-slide-banner">
    <a class="mg-slide-banner__link"
       title="<?php echo $slide['seo_title'] ?>"
       href="<?php echo $slide['href'] ?>">
        <img src="<?php echo SITE.DS.$slide['src'] ?>"
             loading="lazy"
             class="mg-slide-banner__img"
             title="<?php echo $slide['seo_title'] ?>"
             alt="<?php echo $slide['seo_alt'] ?>">
    </a>
</div>
