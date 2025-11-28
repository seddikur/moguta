<?php
$modelOrder = new Models_Order();
$paymentInfo = $modelOrder->getParamArray(24); //Берем данные из Яндекс.Кассы
$username = $paymentInfo[0]['value'];
$password = $paymentInfo[1]['value'];
?>

<div style="padding: 10px;">
<div style="margin: 10px;" class="apple-pay-label">Нажмите кнопку Pay для оплаты через TouchID или FaceID.</div>

<button class="apple-pay-button apple-pay-button-white"></button>
<div class="apple-pay-success" style="display: none">Оплачено!</div>
<div class="apple-pay-fail" style="display: none">Ошибка оплаты</div>
</div>
<br>

<script>
var applePaySession = null;

if (window.ApplePaySession) {
    $('.apple-pay-button').show()
    $('body').on('click', '.apple-pay-button', function(){
        const paymentRequest = {
        total: {
            label: '<?php echo MG::getOption('sitename') ?>',
            amount: <?php echo $data['summ']?>
        },
        countryCode: 'RU',
        currencyCode: 'RUB',
        merchantCapabilities: ['supports3DS'],
        supportedNetworks: ['masterCard', 'visa']
        };

        applePaySession = new window.ApplePaySession(1, paymentRequest);

        applePaySession.onvalidatemerchant = (event) => {
            // отправляем запрос на валидацию сессии
            $.ajax({
            type: "POST",
            url: mgBaseDir + "/ajaxrequest",
            data: {
                actionerClass: "Ajaxuser",
              	action: "applePayVerify",
                url: event.validationURL
            },
            cache: false,
            dataType: "json",
            success: function (response) {
                merchantSession = JSON.parse(response);
                applePaySession.completeMerchantValidation(merchantSession);
            },
          	error: function(response) {
                applePaySession.abort();
            }
        });
        };

        applePaySession.onpaymentauthorized = (event) => {
            var paymentToken = event.payment.token

            var status;
            $.ajax({
                type: "POST",
                url: mgBaseDir + "/ajaxrequest",
                data: {
                    actionerClass: "Ajaxuser",
                    action: "sendApplePayToYandex",
                    summ: "<?php echo $data['summ'] ?>",
                    id: "<?php echo $data['id'] ?>",
                    paymentToken: paymentToken
                },
                cache: false,
                dataType: "json",
                success: function (response) {
                    status = ApplePaySession.STATUS_SUCCESS;
                    applePaySession.completePayment(status);
                    $('.apple-pay-success').show()
                },
                error: function(response) {
                    status = ApplePaySession.STATUS_FAILURE;
                    applePaySession.completePayment(status);
                    $('.apple-pay-fail').show()
                }
            });
            $('.apple-pay-button').hide()
        }
        applePaySession.begin()
    })
} else {
    $('.apple-pay-button').hide()
    $('.apple-pay-label').html('Ваш браузер не поддерживает Apple Pay. Попробуйте другой браузер или устройство с поддержкой Apple Pay. Ваш заказ сохранен в личном кабинете.')
    alert('Ваш браузер не поддерживает Apple Pay. Попробуйте другой браузер или устройство с поддержкой Apple Pay. Ваш заказ сохранен в личном кабинете.');
}
</script>

<style>
@supports (-webkit-appearance: -apple-pay-button) {
    .apple-pay-button {
        display: inline-block;
        -webkit-appearance: -apple-pay-button;
    }
    .apple-pay-button-black {
        -apple-pay-button-style: black;
    }
    .apple-pay-button-white {
        -apple-pay-button-style: white;
    }

    .apple-pay-button-white-with-line {
        -apple-pay-button-style: white-outline;
    }
}
@supports not (-webkit-appearance: -apple-pay-button) {
    .apple-pay-button {
        display: inline-block;
        background-size: 100% 60%;
        background-repeat: no-repeat;
        background-position: 50% 50%;
        border-radius: 5px;
        padding: 0px;
        box-sizing: border-box;
        min-width: 200px;
        min-height: 32px;
        max-height: 64px;
    }
    .apple-pay-button-black {
        background-image: -webkit-named-image(apple-pay-logo-white);
        background-color: black;
    }
    .apple-pay-button-white {
        background-image: -webkit-named-image(apple-pay-logo-black);
        background-color: white;
    }
    .apple-pay-button-white-with-line {
        background-image: -webkit-named-image(apple-pay-logo-black);
        background-color: white;
        border: .5px solid black;
    }
}
</style>
