<?php 
    /**
     * 
     * Компонент для страницы "Юридические лица".
     * Данный компонент используется для динамического обновления данных в таблице через ajax.
     * 
     */
?>

<td class="number text-left">
    <?php echo $entity['id']; ?>
</td>

<td>
    <?php if ($entity['login_email']): ?>
        <?php echo $entity['login_email']; ?>
    <?php else: ?>
        <button type="button" class="button-link js-user-bind" 
                data-legal-id="<?php echo $entity['id']; ?>">
            <?php echo $lang['TD_USER_BIND']; ?>
        </button>
    <?php endif; ?>
</td>

<td>
    <?php echo $entity['name']; ?>
</td>

<td>
    <?php echo $entity['address']; ?>
</td>

<td>
    <?php echo $entity['inn']; ?>
</td>

<td>
    <?php echo $entity['kpp']; ?>
</td>
<td>
    <?php echo $entity['manager_name']; ?> <?php echo $entity['manager_phone']; ?>
</td>
<td class="actions text-right">
    <ul class="action-list">
        <li>
            <a class="js-view-legal" href="javascript:void(0);" 
               data-id="<?php echo $entity['id']; ?>" 
               data-user-id="<?php echo $entity['user_id']; ?>">
                <i class="fa fa-eye"></i>
            </a>
        </li>
    </ul>
</td>