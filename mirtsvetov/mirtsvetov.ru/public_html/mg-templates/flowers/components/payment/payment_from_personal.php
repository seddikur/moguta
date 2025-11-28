<div class="l-row">
    <div class="l-col min-0--12">
        <?php
        if ($data['payMentView']) {
            component('payment', $data);
        } elseif ($data['pay'] == 12 || $data['pay'] == 13) { ?>

            <div class="l-col min-0--12">
                <div class="c-alert c-alert--blue">
                    <?php echo lang('orderPay3'); ?>
                    <b><?php echo $data['paramArray'][0]['value'] ?></b><?php echo lang('orderPay4'); ?>
                </div>
            </div>

        <?php } else { ?>

            <div class="l-col min-0--12">
                <div class="c-alert c-alert--blue">
                    <?php echo lang('orderPay5'); ?>
                    <br>
                    <?php echo lang('orderPay6'); ?>
                </div>
            </div>

        <?php } ?>
    </div>
</div>
