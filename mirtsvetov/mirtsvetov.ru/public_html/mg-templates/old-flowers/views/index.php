<?php mgSEO($data);?>

<?php $rand = rand(1,5);?>
<div class="home-first">
	<picture class="back">
		<source media="(min-width: 768px)" srcset="[webp url="<?php echo PATH_SITE_TEMPLATE.'/images/desktop-'.$rand;?>.jpg"]">
		<img src="[webp url="<?php echo PATH_SITE_TEMPLATE.'/images/mobile-'.$rand;?>.jpg"]">
	</picture>
	<div class="inner flex center column align-center">
		<h1>МИР <br>ЦВЕТОВ</h1>
		<a class="button" href="/catalog">Каталог</a>
	</div>
	<h2 class="absolute">Крупнейший  в России тепличный комплекс по выращиванию роз</h2>
</div>

<div class="home-second">
	<div class="max">
		<h2>Мы начали свое дело <span>18 лет назад</span></h2>
		<div class="chess flex align-center space-between">
			<img src="[webp url="<?php echo PATH_SITE_TEMPLATE;?>/images/home-1.jpg"]" alt="">
			<div class="text content">
				<p>С любовью и ответственностью мы занимаемся своим делом уже 18 лет.</p>
				<p>В представленном каталоге вы можете ознакомиться с полным ассортиментом выращиваемых нами сортов роз, часть из которых эксклюзивно представлена в России.</p>
			</div>
		</div>
		<div class="chess flex align-center space-between">
			<img src="[webp url="<?php echo PATH_SITE_TEMPLATE;?>/images/home-2.jpg"]" alt="">
			<div class="text content">
				<p>Наши розы доступны круглый год. Каждый сорт тестируется и изучается в течение нескольких лет перед тем, как быть высаженным в теплице. Мы делаем все, чтобы вы получали лучшее качество, идеальные размеры и оттенки бутонов роз.</p>
				<p>Постоянный контроль качества и упаковки обеспечивает свежесть цветка при доставке в разные регионы нашей страны от Смоленска до Благовещенска, от Мурманска до Сочи.</p>
			</div>
		</div>
	</div>
</div>

<div class="home-third">
	<img src="[webp url="<?php echo PATH_SITE_TEMPLATE;?>/images/home-3.jpg"]" alt="">
</div>

<div class="home-last">
	<picture class="back">
		<source media="(min-width: 768px)" srcset="[webp url="<?php echo PATH_SITE_TEMPLATE;?>/images/desktop-last.jpg"]">
		<img src="[webp url="<?php echo PATH_SITE_TEMPLATE;?>/images/mobile-last.jpg"]">
	</picture>
	<div class="inner flex center column align-center">
		<div class="h1-like">МИР <br>ЦВЕТОВ</div>
		<a class="button" href="/catalog">Каталог</a>
	</div>
	<div class="h2-like absolute">Крупнейший  в России тепличный комплекс по выращиванию роз</div>
</div>