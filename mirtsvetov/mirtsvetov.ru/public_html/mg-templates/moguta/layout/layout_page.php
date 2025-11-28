<div class="l-col min-0--12 l-main__right">
    <div class="l-row">
        <div class="l-col min-0--12 <?php echo (MG::get('isStaticPage')) ? "static-page-content" : ''; ?>">

            <?php if (class_exists('BreadCrumbs') && MG::get('controller') == "controllers_catalog"): ?>
                [brcr]
            <?php endif; ?>

            <!-- содержимое страниц из папки /views -->
            <?php layout('content'); ?>

        </div>
    </div>
</div>
<?php
    component('spoiler-from-cke-support');
 ?>
