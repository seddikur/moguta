<?php mgSEO($data);
$prodIds = array();
$propTable = array(); ?>

<div class="max">
    <h1 style="text-align: center"><?php echo lang('compareProduct'); ?></h1>
    <div class="alert js-compare-alert" style="display: none"><?php echo lang('compareProductEmpty'); ?></div>
    <?php if (!empty($data['catalogItems'])):?>
        <div class="links flex center">
            <a class="link check-difference">Показать/скрыть одинаковые характеристики</a>
            <a class="link remove-all mg-clear-compared-products" href="<?php echo SITE ?>/compare?delCompare=1">Удалить все</a>
        </div>
    <?php else:?>
        <div class="alert">Список сравнения пуст</div>
    <?php endif;?>
    <?php component('compare', $data);?>
</div>

<script>
$(document).ready(function() {
    $('.check-difference').click(function() {
        $('.mg-compare-fake-table-row').each(function() {
            var textToMatch = $(this).find('.td:first').text();
            $(this).find('.td').each(function() {
                var text = $(this).text();
                if (textToMatch !== text) {
                    var data = $(this).parent().data('row');
                    $('.mg-compare-fake-table-row[data-row='+data+']').addClass('different');
                }
            });
        });
        $('.mg-compare-fake-table-row:not(.different').slideToggle();
    });
});
  
</script>