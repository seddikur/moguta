<?php

/*
	Plugin Name: Кнопка 'В корзине'
	Description: Если товар в корзине - заменяет кнопку купить на 'В корзине' и добавляет класс 'alreadyInCart', при удалении из мини корзины возвращает старое название кнопки купить и убирает класс. <br>Текст 'В корзине' можно изменить, добавив в js локализацию текущего шаблона элемент pluginInCartText.

	Author: Nikita F
	Version: 1.0.1
 */

new InCart;

class InCart{

	public function __construct() {

		if (!URL::isSection('mg-admin')) {
			mgAddMeta('<script src="'.SITE.'/'.PLUGIN_DIR.PM::getFolderPlugin(__FILE__).'/js/script.js"></script>');
		}
	}
}
?>