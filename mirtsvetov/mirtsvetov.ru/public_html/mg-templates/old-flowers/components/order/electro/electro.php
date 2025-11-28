<div class="l-row">

    <div class="l-col min-0--12">
        <div class="c-title"><?php echo $data['fileToOrder']['infoMsg'] ?></div>
    </div>

  <?php if (!empty($data['fileToOrder']['electroInfo'])) { ?>
      <div class="l-col min-0--12">
          <ul class="c-history__list">
            <?php foreach ($data['fileToOrder']['electroInfo'] as $item) { ?>
                <li class="c-history__list--item">
                    <a class="c-history__list--link"
                       href="<?php echo $item['link'] ?>">
                        <b><?php echo lang('orderDownload'); ?></b>
                      <?php echo $item['title'] ?>
                    </a>
                </li>
            <?php } ?>
          </ul>
      </div>
  <?php } ?>

</div>