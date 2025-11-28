<?php mgAddMeta('components/menu/groups/groups.css'); ?>

<nav class="c-group">
    <a class="c-group__link"
       title="<?php echo lang('groupSale'); ?>"
       href="<?php echo SITE; ?>/group?type=sale">
        <?php echo lang('groupSale'); ?>
    </a>
    <a class="c-group__link"
       title="<?php echo lang('groupNew'); ?>"
       href="<?php echo SITE; ?>/group?type=latest">
        <?php echo lang('groupNew'); ?>

    </a>
    <a class="c-group__link"
       title="<?php echo lang('groupHit'); ?>"
       href="<?php echo SITE; ?>/group?type=recommend">
        <?php echo lang('groupHit'); ?>
    </a>
</nav>