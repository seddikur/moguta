<div class="section-<?php echo $pluginName ?>">
<div class="widget-body no-found">
<div class="widget-panel">
    <div class="alert-block warning no-found-alert">
        <span><?php echo $lang['NOT_FOUND']; ?></span>
    </div>
    <ul class="list-option">
        <li><label class="list-option__label"><span class="custom-text"><?php echo $lang['GENERATE_PROP']; ?>:<a class="tool-tip-top fa fa-question-circle fl-right" title="<?php echo $lang['T_TIP_GENERATE_PROP']; ?>"></a></span><button class="button generate-new"><?php echo $lang['GENERATE_BUTTON']; ?></button></label></li>
        <li><?php echo $lang['OR']; ?></li>
        <li><label class="list-option__label"><span class="custom-text"><?php echo $lang['SELECT_PROP']; ?>:<a class="tool-tip-top fa fa-question-circle fl-right" title="T_TIP_SELECT_PROP"></a></span>
        <select name="property_id" id="property-id">
            <option default><?php echo $lang['DEFAULT_SELECT']; ?></option>
            <?php foreach ($listprops as $key => $value): ?>
            <option value="<?= $value['id'] ?>"><?= $value['name'] ?></option>
            <?php endforeach; ?>
        </select><button class="button success select-property"><?php echo $lang['SELECT_PROPERTY']; ?></button>
        </label></li>
    </ul>
</div>
</div>
</div>

