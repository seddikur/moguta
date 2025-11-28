<style>
    .yandex-kassa-payment-methods{
        list-style: none;
        padding: 0;
        font-size: 0;
        margin: -1%;
    }

    .yandex-kassa-payment-methods li:hover{
        box-shadow: 0px 1px 4px 0px rgba(0, 0, 0, 0.4);
    }

    .yandex-kassa-payment-methods li{
        width: 23%;
        margin: 1%;
        box-sizing: border-box;
        background: #fff;
        display: inline-block;
        vertical-align: top;
        box-shadow: 0px 1px 4px 0px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 550px){
        .yandex-kassa-payment-methods li{
            width: 30%;
        }
    }

    @media (max-width: 420px){
        .yandex-kassa-payment-methods li{
            width: 48%;
        }
    }

    .yandex-kassa-payment-methods li a img{
        vertical-align: middle;
        max-width: 100%;
        margin: 0 0 10px 0;
    }

    .yandex-kassa-payment-methods li a{
        display: block;
        font-size: 12px;
        line-height: 15px;
        padding: 15px;
        text-decoration: none;
        text-align: center;
        color: #8B8B8B;
    }

    .yandex-kassa-payment-methods li a span{
        display: block;
        height: 45px;
        overflow: hidden;
    }
    
    .yandex-kassa-payment-methods a:hover{
        text-decoration: none;
    }

    .pay-info{
        color: #92862e;
        border: 1px solid #e1d260;
        background: #fff6ae;
        padding: 10px;
        margin: 20px 0;
        font-size: 14px;;
    }

    .payment-form-block h2{
        font-size: 16px;
        line-height: 16px;;
    }

    .payment-item h3{
        font-size: 14px;
    }
</style>
<script>
$(function(){
  $('.yandex-kassa-payment-method').click(function(){
    var paymentMethod = $(this).attr('data-id');
    $('input#paymentMethod').val(paymentMethod);
    $(this).parents('form').submit();
  });
});
</script>
<div class="payment-form-block">
  <form method="POST" action="<?php echo $data['paramArray'][0]['value']?>">
    <!-- Обязательные параметры -->
    <input name="shopId" value="<?php echo $data['paramArray'][1]['value']?>" type="hidden" />
    <input name="scid" value="<?php echo $data['paramArray'][2]['value']?>" type="hidden" />
    <input name="sum" value="<?php echo $data['summ'] ?>" type="hidden" />
    <input name="customerNumber" value="<?php echo $data['userInfo']->email ?>" type="hidden" />
    <!-- Необязательные параметры -->
    <input name="orderMId" value="<?php echo $data['id']?>" type="hidden" />
    <input name="orderNumber" value="<?php echo $data['orderNumber']?>" type="hidden" />
    <input name="cps_email" value="<?php echo $data['userInfo']->email ?>" type="hidden" /> 
    <input name="cps_phone" value="<?php echo $data['userInfo']->phone ?>" type="hidden" /> 
    <input name="paymentType" id="paymentMethod" value="" type="hidden" /> 



 <div class="payment-item">
        <h3>Оплатить частями с помощью яндекс кассы</h3>
        <ul class="yandex-kassa-payment-methods">
            <li>
                <a data-id="CR" class="yandex-kassa-payment-method" href="javascript:void(0);">
                    <img  src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIoAAABJCAYAAADi+75+AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MjZGNzVCRkFGN0VBMTFFNEFDMDZFOEY5OTAwODlCNEIiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MjZGNzVCRkJGN0VBMTFFNEFDMDZFOEY5OTAwODlCNEIiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoyNkY3NUJGOEY3RUExMUU0QUMwNkU4Rjk5MDA4OUI0QiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoyNkY3NUJGOUY3RUExMUU0QUMwNkU4Rjk5MDA4OUI0QiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PqBUq80AABYdSURBVHja7Z1JjCXZ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" />
                    <span>Перейти к оформлению кредита или рассрочки</span>
                </a>
            </li>
        </ul>
    </div>   

  </form>

</div>