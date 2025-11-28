<?php if (!empty($data['manager_name']) && !empty($data['manager_phone'])): ?>
	<div class="manager-info">
		Ваш менеджер: <?php echo $data['manager_name']; ?>, <b><a href="tel:+<?php echo $data['manager_phone']; ?>"><?php echo $data['manager_phone']; ?></a></b>
	</div>
<?php endif; ?>