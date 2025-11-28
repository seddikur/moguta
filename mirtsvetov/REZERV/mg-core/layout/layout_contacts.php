<?php 
$workTime = explode(',', MG::getSetting('timeWork'));
$workTimeDays = explode(',', MG::getSetting('timeWorkDays'));
if (empty($workTimeDays)) {
    $workTimeDays = array(lang('mon-fri'), lang('sat-sun'));
}
?>
<div class="c-contact" itemscope itemtype="http://schema.org/Store">
    <meta itemprop="address" content="<?php echo MG::getSetting('shopAddress') ?>">
    <meta itemprop="priceRange" content="<?php echo MG::getSetting('currencyShopIso'); ?>">
    <link rel="stylesheet" href="">
    <link itemprop="logo" href="<?php echo SITE.MG::getSetting('shopLogo'); ?>">
    <link href="<?php echo SITE; ?>" itemprop="url">
    <img class="hidden" src="<?php echo SITE; ?>/favicon.ico" alt="<?php echo MG::getSetting('sitename') ?>" itemprop="image">
    <div class="c-contact__column">
        <svg class="icon icon--time"><use xlink:href="#icon--time"></use></svg>
        <?php foreach ($workTime as $key => $value): ?>
            <div class="c-contact__row">
                <?php if (!$key): ?>
                    <span class="hidden" itemprop="name"><?php echo MG::getSetting('sitename') ?></span>
                <?php endif ?>
                <div class="c-contact__schedule"><span class="c-contact__span"><?php echo (!empty($workTimeDays[$key])) ? $workTimeDays[$key] : ''; ?></span> <?php echo $workTime[$key]; ?></div>
            </div>
        <?php endforeach ?>
    </div>
    <div class="c-contact__column">
        <svg class="icon icon--phone"><use xlink:href="#icon--phone"></use></svg>
        <?php $phones = explode(', ', MG::getSetting('shopPhone'));
        foreach ($phones as $phone) {?>
             <div class="c-contact__row">
                 <a class="c-contact__number" href="tel:<?php echo str_replace(' ', '', $phone); ?>"><span itemprop="telephone"><?php echo $phone; ?></span></a>
             </div>
        <?php } ?>
        <?php if (class_exists('BackRing')): ?>
            <div class="c-contact__row">
                <div class="wrapper-back-ring"><button type="submit" class="back-ring-button default-btn"><?php echo lang('backring'); ?></button></div>
            </div>
        <?php endif; ?>
    </div>
</div>