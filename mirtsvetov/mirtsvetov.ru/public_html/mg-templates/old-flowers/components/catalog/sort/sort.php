<?php 
    mgAddMeta('/components/catalog/sort/sort.js');

    $sorts = [
        'recommend|1' => 'сортировать по рекомендуемым',
        'sort|1' => 'сортировать по популярности',
        'price_course|1' => 'сортировать по цене убывания',
        'price_course|-1' => 'сортировать по цене возрастания',
    ];
?>

<select id="sort" class="fast-sort sort mobile-hide">
    <?php foreach ($sorts as $sort => $text): ?>
        <?php 
            if ($_SESSION['filters'] === $sort) {
                $selected = 'selected';
            } else {
                unset($selected);
            }
        ?>
        <option value="<?php echo $sort; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
    <?php endforeach; ?>
</select>