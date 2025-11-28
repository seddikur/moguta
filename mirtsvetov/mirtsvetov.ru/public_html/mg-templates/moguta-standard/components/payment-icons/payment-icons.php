<?php
if (MG::get('templateParams')['FOOTER']['checkbox_paymentShow'] === 'true') :
  mgAddMeta('components/payment-icons/payment-icons.css');
  if (method_exists('MG', 'isNewPayment') && MG::isNewPayment()) {
    $paymentsSql = 'SELECT `id`, `name`, `icon` '.
        'FROM `'.PREFIX.'payment` '.
        'WHERE `code` NOT LIKE \'old#%\' AND '.
        '`activity` = 1 '. 
        'ORDER BY `sort` DESC';
    $paymentsResult = DB::query($paymentsSql);
    $payments = [];
    while ($paymentsRow = DB::fetchAssoc($paymentsResult)) {
        $paymentId = $paymentsRow['id'];
        $payments[$paymentId]['name'] = $paymentsRow['name'];
        $icon = SITE.'/mg-admin/design/images/icons/cash.png';
        if ($paymentsRow['icon']) {
            $icon = SITE.$paymentsRow['icon'];
        }
        $payments[$paymentId]['icon'] = $icon;
    }
  } else {
    $paymentIdToIconName = [
        '1' => 'webmoney.png',
        '2' => 'ya.png',
        '12' => 'ya.png',
        '5' => 'robo.png',
        '6' => 'qiwi.png',
        '8' => 'sci.png',
        '9' => 'payanyway.png',
        '10' => 'paymaster.png',
        '11' => 'alfabank.png',
        '14' => 'yandexkassa.png',
        '15' => 'privat24.png',
        '16' => 'liqpay.png',
        '17' => 'sber.png',
        '18' => 'tinkoff.png',
        '19' => 'paypal.png',
        '21' => 'paykeeper.png',
        '20' => 'comepay.svg',
        '22' => 'cloudpayments.png',
        '23' => 'ya-pay-parts.svg',
        '24' => 'yandexkassa.png',
        '25' => 'apple.png',
        '26' => 'free-kassa.png',
        '27' => 'megakassa.png',
        '28' => 'qiwi.png',
    ];
    
    $paymentsSql = 'SELECT `id`, `name` '.
        'FROM `'.PREFIX.'payment` '.
        'WHERE `activity` = 1 ';
    if (method_exists('MG', 'isNewPayment')) {
        $paymentsSql .= 'AND `code` LIKE \'old#%\' ';
    }
    $paymentSql .= 'ORDER BY `id`';
    $paymentsResult = DB::query($paymentsSql);
    $payments = [];
    while ($paymentsRow = DB::fetchAssoc($paymentsResult)) {
        $paymentId = $paymentsRow['id'];
        $payments[$paymentId]['name'] = $paymentsRow['name'];
        $iconFile = 'cash.png';
        if ($paymentIdToIconName[$paymentId]) {
            $iconFile = $paymentIdToIconName[$paymentId];
        }
        $payments[$paymentId]['icon'] = SITE.'/mg-admin/design/images/icons/'.$iconFile;
    }
  }
?>
  <ul class="footer__payments">
    <?php foreach ($payments as $paymentId => $payment) : ?>
      <li class="footer__pay payments__item_id_<?php echo $paymentId; ?>" title="«<?php echo $payment['name']; ?>»">
        <img width="15" height="16" src="<?php echo $payment['icon']; ?>" alt="«<?php echo $payment['name']; ?>»">
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>