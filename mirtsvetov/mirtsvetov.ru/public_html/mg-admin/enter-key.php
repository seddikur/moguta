<div class="container-to-key" style="max-width:300px;margin:10px auto;background:white;padding:15px;">
	<p class="text-center">Введите ваш лицензионный ключ</p>
	<p class="text-center keyError" style="color:red;display:none;">Ключ не подходит!</p>
	<input type="text" class="licenseKey" placeholder="ключ">
	<button class="setKey button success" style="width:100%">Сохранить ключ</button>
	
	<br><br>
	<p class="text-center">Приобрести ключ можно на сайте <a href="https://moguta.ru" target="blank">moguta.ru</a></p>
</div>

<script>
	$('body').on('click', '.setKey', function() {
		admin.ajaxRequest({
			mguniqueurl: "action/setKey", // действия для выполнения на сервере
			key: $('.licenseKey').val(),
		},
		function(response) {
			if(response == true) {
				location.reload();
			} else {
				$('.keyError').show();
			}
		});
		return false;
	});
</script>