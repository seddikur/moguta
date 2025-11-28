<?php
mgAddMeta('components/compare/link/link.js');
?>

<a aria-label="compare" href="/compare" class="js-to-compare-link mobile-hide icon icon-favorites flex align-center center">
   <svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/icons.svg#bar"></use></svg>
   <div class="count js-compare-count" style="<?php echo ($_SESSION['compareCount']) ? 'display:block;' : 'display:none;'; ?>">
      <div class="c-compare__link--number">
            <?php if (isset($_SESSION['compareCount'])) {
                echo $_SESSION['compareCount'];
            } else {
                echo 0;
            } ?>
      </div>
   </div>
</a>