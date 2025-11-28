<?php MG::enableTemplate();?>
<?php

  $res = deleteCurrentUser();
  echo $res;
  header('Location: '.SITE.'/enter?logout=1');//а теперь редирект

?>