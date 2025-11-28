<div class="payment-form-block">
  <form action="https://www.liqpay.ua/api/3/checkout" method="POST" accept-charset="UTF-8">
    <input type="hidden" name="data" value="<?php echo $data['paramArray']['data']?>"/>
    <input type="hidden" name="signature" value="<?php echo $data['paramArray']['signature']?>" />
    <input type="image" src="//static.liqpay.ua/buttons/p1ru.radius.png" name="btn_text" />
  </form>
</div>