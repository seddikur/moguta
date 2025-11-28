<?php 
  if (!$data) {
    return;
  }
?>

<?php mgAddMeta('components/pagination/pagination.css'); ?>

<nav class="mg-pager"
     aria-label="<?php echo lang('pagination'); ?>">

    <div class="allPages">
      <?php echo lang('totalPages'); ?>
        <span><?php echo $data['totalPages'] ?></span>
    </div>


    <ul class="clearfix">
      <?php

      if ($data['first']['needed'] == true) {
        echo "<li" . $data['first']['liClass'] . "><a aria-label='" . lang('paginationLabel3') . "' class='" . $data['first']['class'] . "' " . $data['first']['href'] . " >" . $data['first']['ancor'] . "</a></li>";
      }

      if ($data['prev']['needed'] == true) {
        echo "<li" . $data['prev']['liClass'] . "><a aria-label='" . lang('paginationLabel4') . "' class='" . $data['prev']['class'] . "' " . $data['prev']['href'] . " >" . $data['prev']['ancor'] . "</a></li>";
      }

      if (!empty($data['leftpoint'])) {
        echo "<span class='point'>...</span>";
      }


      // Добавляем доступности к пагинации
      $ariaAttrsFirst = ' aria-label="' . lang('paginationLabel') . ' ' . $data['firstPages']['ancor'] . '"';

      if ($data['firstPages']['needed'] == true) {
        echo "<li" . $data['firstPages']['liClass'] . "><a class='" . $data['firstPages']['class'] . "' " . $data['firstPages']['href'] . $ariaAttrsFirst . " >" . $data['firstPages']['ancor'] . "</a></li>";
      }

      foreach ($data['pager'] as $pager) {
        // Добавляем доступности к пагинации
        $ariaAttrs = ' aria-label="' . lang('paginationLabel') . ' ' . $pager['ancor'] . '"';
        if ($pager['class'] === 'active') {
          $ariaAttrs = ' aria-label="' . lang('paginationLabel2') . ', ' . lang('paginationLabel') . ' ' . $pager['ancor'] . '"';
          $ariaAttrs .= ' aria-current="true"';
          $pager['href'] = 'href="javascript:void(0);"';
          echo "<li" . $pager['liClass'] . "><button class='" . $pager['class'] . "' " . $ariaAttrs . " >" . $pager['ancor'] . "</button></li>";
        } else {
          echo "<li" . $pager['liClass'] . "><a class='" . $pager['class'] . "' " . $pager['href'] . $ariaAttrs . " >" . $pager['ancor'] . "</a></li>";
        }

      }

      // Добавляем доступности к пагинации
      $ariaAttrsLast = ' aria-label="' . lang('paginationLabel') . ' ' . $data['lastPages']['ancor'] . '"';
      if ($data['lastPages']['needed'] == true) {
        echo "<li" . $data['lastPages']['liClass'] . "><a class='" . $data['lastPages']['class'] . "' " . $data['lastPages']['href'] . $ariaAttrsLast . " >" . $data['lastPages']['ancor'] . "</a></li>";
      }

      if (!empty($data['rightpoint'])) {
        echo "<span class='point'>...</span>";
      }

      if ($data['next']['needed'] == true) {
        echo "<li" . $data['next']['liClass'] . "><a aria-label='" . lang('paginationLabel4') . "' class='" . $data['next']['class'] . "' " . $data['next']['href'] . " >" . $data['next']['ancor'] . "</a></li>";
      }

      if ($data['last']['needed'] == true) {
        echo "<li" . $data['last']['liClass'] . "><a aria-label='" . lang('paginationLabel5') . "' class='" . $data['last']['class'] . "' " . $data['last']['href'] . " >" . $data['last']['ancor'] . "</a></li>";
      }

      ?>
    </ul>
</nav>
