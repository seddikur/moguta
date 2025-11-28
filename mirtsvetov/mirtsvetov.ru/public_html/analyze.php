<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Проверка функций Moguta.CMS</h3>";

$functions = [
	'mgActivateThisPlugin',
	'mgAddAction',
	'mgAddShortcode', 
	'mgAddMeta',
	'MG::getSetting',
	'MG::setOption',
	'DB::query',
	'DB::quote',
	'DB::insertId'
];

foreach ($functions as $function) {
	if (strpos($function, '::')) {
		// Проверка статических методов
		list($class, $method) = explode('::', $function);
		if (class_exists($class) && method_exists($class, $method)) {
			echo "<p style='color:green'>✓ $function доступна</p>";
		} else {
			echo "<p style='color:red'>✗ $function НЕ доступна</p>";
		}
	} else {
		// Проверка функций
		if (function_exists($function)) {
			echo "<p style='color:green'>✓ $function доступна</p>";
		} else {
			echo "<p style='color:red'>✗ $function НЕ доступна</p>";
		}
	}
}

// Проверим классы
echo "<h4>Доступные классы:</h4>";
$classes = ['DB', 'MG', 'URL', 'USER', 'Actions', 'Filter', 'Navigator'];
foreach ($classes as $class) {
	echo "<p>$class: " . (class_exists($class) ? '<span style="color:green">✓ существует</span>' : '<span style="color:red">✗ не существует</span>') . "</p>";
}