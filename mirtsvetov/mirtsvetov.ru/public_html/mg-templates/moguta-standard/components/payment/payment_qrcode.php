<div class="payment-form-block">
  <div class="payment-qr" >
    <?php echo lang('paymentQr'); ?><br>
    <?php if(USER::getThis()->email == $data['userInfo']->email){ ?>
      <img class="payment-qr" src="<?php echo SITE?>/payment?getQr=<?php echo $data['id'] ?>" alt='' style="display: block; margin-left: auto; margin-right: auto"/>
      <?php  }else{?>
          <?php echo lang('paymentQrComplete'); ?>
    <?php } ?>
  </div>
</div>