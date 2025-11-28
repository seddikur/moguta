<footer class="l-footer">
    <div class="l-container">
        <?php component('payment-icons'); ?>
        <div class="l-row">
            <div class="l-col min-0--12 min-768--5">
                <!-- копирайт -->
                <div class="c-copyright">
                    <?php echo date('Y') . '&nbsp;' . lang('copyright'); ?>
                </div>
            </div>

            <div class="l-col min-0--12 min-768--2 min-0--flex min-0--align-center min-0--justify-center max-767--order-end">
                <div class="c-widget">
                    <!-- Виджеты, добавленные через панель управления в опции «Коды счетчиков и виджетов»-->
                    <?php layout('widget'); ?>
                </div>
            </div>

            <div class="l-col min-0--12 min-768--5">
                <!-- копирайт -->
                <div class="c-copyright c-copyright__moguta">
                    <?php copyrightMoguta(); ?>
                </div>
            </div>
        </div>
        <?php if (class_exists('SocIcons')){ ?>
                    [soc-icons]
        <?php } ?>
        <?php if (class_exists('EmailSubscribe')) { ?>
            [eml-subscribe]
            [eml-subscribelink] 
        <?php } ?>
    </div>
</footer>

