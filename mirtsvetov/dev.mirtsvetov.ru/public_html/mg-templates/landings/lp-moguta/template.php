<!-- Файл template.php является каркасом шаблона, содержит основную верстку шаблона. -->
<!DOCTYPE html>
<html lang="ru">
	<head>
		<?php mgMeta(); ?>
		<meta name="viewport" content="width=device-width">
	</head>
	<body <?php backgroundSite(); ?>>
			<?php if (!isCatalog() && !isIndex())  {
					layout('content');
			} ?>

	</body>
</html>
