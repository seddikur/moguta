# Компонент «Выбор количества товаров»

Для пересчёта цены при использовании компонента в корзине, 
необходимо передать в компонент значение массива 'type' => 'cart'.

### Пример вызова:

```<?php
 component(
     'amount',
     [
         'id' => $product['id'],
         'maxCount' => $data['maxCount'],
         'count' => $product['countInCart'],
         'type' => 'cart',
         'increment'=>'1',
     ]
 ); ?>
```
