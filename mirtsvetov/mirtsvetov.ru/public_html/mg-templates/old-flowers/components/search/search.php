<?php mgAddMeta('components/search/search.js'); ?>
<div class="search-block">
    <div class="menu-button center flex align-center"><div class="lines"><span></span><span></span><span></span></div>Каталог</div>
    <form role="search" method="GET" action="<?php echo SITE ?>/catalog" class="flex nowrap tablet-hide" aria-label="Поиск по сайту">
        <input type="search" autocomplete="off" aria-label="Поиск по сайту" name="search" placeholder="Поиск по сайту" value="<?php if (isset($_GET['search'])) {echo $_GET['search'];} ?>">
        <button class="flex align-center center" type="submit" aria-label="search"><svg><use xlink:href="<?php echo PATH_SITE_TEMPLATE ?>/images/icons.svg#search"></use></svg></button>
    </form>
    <div class="wraper-fast-result tablet-hide">
        <div class="fastResult"></div>
    </div>
</div>