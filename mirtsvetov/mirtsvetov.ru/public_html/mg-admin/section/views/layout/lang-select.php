<?php
$langs = MG::getSetting('multiLang', 1);
$print = false;
foreach ($langs as $item) {
	if ($item['enabled'] == 'true') {
		$print = true;
		break;
	}
}
if ($print) { ?>
	<!-- для мультиязычности -->
	<div class="select-lang__wrap <?php echo $data['class'] ?>"
		 flow="rightDown"
		 tooltip="Редактировать контент для других языков">
		<select class="select-lang" dir="rtl"
				aria-label="Редактировать контент для других языков">
			<option value="default"><?php echo $lang['TRANSLATION']; ?></option>
			<?php
			foreach ($langs as $item) {
				if ($item['enabled'] == 'true') {
          $item['full'] = htmlspecialchars($item['full']);
					echo '<option value="'.$item['short'].'">'.$item['full'].'</option>';
				}
			}
			?>
		</select>
	</div>
	<!-- для мультиязычности конец -->
<?php }
