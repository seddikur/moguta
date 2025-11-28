<table>
    <thead class="mobile-hide">
        <tr>
            <th>высота</th>
            <th>цена</th>
            <th>в 1 баке</th>
            <th></th>
        </tr>
    </thead>
    <tbody>

        <?php
            // Сортировка по порядку 
            usort($data['variants'], function($a, $b) {
                return ($a['sort'] - $b['sort']);
            });
        ?>

        <?php foreach ($data['variants'] as $variant): ?>
            <?php 
                // Не выводим вариант с названием 90 см или 90см
                if ($variant['title_variant'] == '90 см' || $variant['title_variant'] == '90см') continue;

                $inCart = $data['inCart'][$variant['product_id'] .'|'. $variant['id']]['count'] ?: 0; 

                if ($inCart) {
                    $class = 'class="active"'; 
                }

                if ($variant['count'] == 0) {
                    $class = 'class="non-available"'; 
                }
            ?>
            <tr <?php echo $class; ?>>
                <td><?php echo $variant['title_variant']; ?></td>
                <?php if ($variant['opf_21']): ?>
                    <td><?php echo MG::priceCourse($variant['price'] / $variant['opf_21']) .' '. $data['currency']; ?></td>
                <?php else: ?>
                    <td>-</td>
                <?php endif;?>
                <td class="mobile-hide"><?php echo ($variant['opf_21']) ? $variant['opf_21'] : '-'; ?></td>
                <td class="amount-cell">
                    <?php
                        if ($variant['count']) {
                            component(
                                'catalog/item/amount',
                                [
                                    'id' => $variant['product_id'],
                                    'variant' => $variant['id'],
                                    'maxCount' => $variant['count'],
                                    'inCart' => $inCart,
                                    'increment' => MG::get('settings')['useMultiplicity'] == 'true' ? $data['item']['multiplicity'] : '1',
                                ]
                            );
                        }
                    ?>
                </td>
                <td class="mobile-td"><?php echo ($variant['opf_21']) ? '× '.$variant['opf_21'].' шт.': '-'; ?></td>
            </tr>

            <?php unset($class); ?>

        <?php endforeach; ?>
    </tbody>
</table>