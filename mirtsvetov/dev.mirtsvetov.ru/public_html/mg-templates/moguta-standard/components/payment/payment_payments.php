<?php if (empty($data['paymentViewContent']) && $data['step'] === 4) : ?>
<div class="c-alert c-alert--green alert alert-success">
    <?php echo lang('orderPaymentForm1'); ?>
    <strong>№ <?php echo $data['orderNumber'] ?></strong> <?php echo lang('orderPaymentForm2'); ?>
</div>

<?php
    else : 
        echo $data['paymentViewContent'];
    endif;
?>