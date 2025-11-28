<div class="contacts-block" itemscope itemtype="http://schema.org/Store">
    <meta itemprop="address" content="<?php echo htmlspecialchars(MG::getSetting('shopAddress')) ?>">
    <meta itemprop="name" content="<?php echo htmlspecialchars(MG::getSetting('sitename')) ?>">
    <meta itemprop="priceRange" content="<?php echo MG::getSetting('currencyShopIso'); ?>">
    <link itemprop="logo" href="<?php echo SITE . MG::getSetting('shopLogo'); ?>">
    <link href="<?php echo SITE; ?>" itemprop="url">
    <img class="hidden" hidden src="/favicon.svg" alt="<?php echo htmlspecialchars(MG::getSetting('sitename')) ?>" itemprop="image">
    <div class="work-hours">
        <?php
            $workTime = explode(',', MG::getSetting('timeWork'));
            $workTimeDays = explode(',', MG::getSetting('timeWorkDays'));
            foreach ($workTime as $key => $time) { ?>
                <?php echo !empty($workTimeDays[$key]) ? htmlspecialchars($workTimeDays[$key]) : ''; ?>
            <?php echo htmlspecialchars($workTime[$key]); ?>
        <?php } ?>
    </div>
    <div class="phones">
        <?php $phones = explode(', ', MG::getSetting('shopPhone')); foreach ($phones as $phone) { ?>
            <a href="tel:<?php echo htmlspecialchars(str_replace(' ', '', $phone)); ?>"><span itemprop="telephone"><?php echo htmlspecialchars($phone); ?></span></a>
        <?php } ?>
    </div>
</div>