<?php mgAddMeta('components/search/search.js'); ?>
<div class="search-block tablet-show">
    <form role="search" method="GET" action="<?php echo SITE ?>/catalog">
        <input type="search" autocomplete="off" name="search" placeholder="<?php echo lang('searchPh'); ?>" value="<?php if (isset($_GET['search'])) {echo $_GET['search'];} ?>">
        <button aria-label="<?php echo lang('search'); ?>" class="flex align-center center" type="submit"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE;?>/images/icons.svg#search"></use></svg></button>
    </form>
</div>