<?php

$lang = MG::get('lang');

if (!$data) echo '<tr class="toDel"><td style="text-align:center;" colspan="10">'.$lang['NO_USER_FIELD'].'</td></tr>';
foreach ($data as $key => $field) { ?>
	<tr data-id="<?= $key ?>">
		<td><i title="<?php echo $lang['OPFIELDS_ATR_TITLE2'] ?>"
			   aria-label="<?php echo $lang['OPFIELDS_ATR_TITLE2'] ?>" class="fa fa-arrows"></i></td>
		<td style="text-align: center;"><?php echo $field['id'] ?></td>
		<td><input type="text" tooltip="<?php echo $lang['OPFIELDS_ATR_TITLE1'] ?>"
				   aria-label="<?php echo $lang['OPFIELDS_ATR_TITLE1'] ?>"
                   name="name" class="no-margin dSave"
				   value="<?= $field['name'] ?>"></td>

		<?php if (in_array($addData, array('order', 'user'))) { ?>
		<td><input type="text" title="<?php echo $lang['OPFIELDS_ATR_TITLE1'] ?>"
				   aria-label="<?php echo $lang['OPFIELDS_ATR_TITLE1'] ?>" name="placeholder" class="no-margin dSave"
				   value="<?= htmlspecialchars($field['placeholder']) ?>"></td>
		<?php } ?>
		
		<?php if (in_array($addData, array('order', 'user'))) { ?>
		<td>
            <select aria-label="<?php echo $lang['OPFIELDS_ATR_TITLE'] ?>"
                   class="no-margin dSave" name="type" title="<?php echo $lang['OPFIELDS_ATR_TITLE'] ?>">
                <option value="input" <?= $field['type'] == 'input' ? 'selected' : '' ?>><?php echo $lang['USER_FIELD_INPUT'] ?></option>
                <option value="textarea" <?= $field['type'] == 'textarea' ? 'selected' : '' ?>><?php echo $lang['USER_FIELD_TEXTAREA'] ?></option>
                <option value="checkbox" <?= $field['type'] == 'checkbox' ? 'selected' : '' ?>><?php echo $lang['USER_FIELD_CHECKBOX'] ?></option>
                <option value="select" <?= $field['type'] == 'select' ? 'selected' : '' ?>><?php echo $lang['USER_FIELD_SELECT'] ?></option>
                <option value="radiobutton" <?= $field['type'] == 'radiobutton' ? 'selected' : '' ?>><?php echo $lang['USER_FIELD_RADIO'] ?></option>
                <?php if ($addData == 'order') { ?>
                    <option value="file" <?= $field['type'] == 'file' ? 'selected' : '' ?>><?php echo $lang['USER_FIELD_FILE'] ?></option>
                <?php } ?>
            </select>
		</td>
		<?php } ?>

		<?php if (in_array($addData, array('order', 'user'))) { ?>
		<td style="position: relative; width:40px;">
			<button class="button secondary openPopup"
					title="<?php echo $lang['OPFIELDS_ATR_TITLE3'] ?>"
					aria-label="<?php echo $lang['OPFIELDS_ATR_TITLE3'] ?>"
					style="margin:0px;<?= in_array($field['type'], array('select', 'radiobutton')) ? '' : 'display:none;' ?>">
				<span aria-hidden="true" class="fa fa-list-ul"></span>
			</button>
			<div class="custom-popup <?= !empty($field['active']) ? 'to-left' : '' ?> field-variant-popup"
				 style="top:7px;display:none;">
				<button class="custom-popup__close js-popup-close"
						title="<?php echo $lang['CLOSE'] ?>"
						aria-label="<?php echo $lang['CLOSE'] ?>">
					<i class="fa fa-times" aria-hidden="true"></i>
				</button>
				<h4><?php echo $lang['SELECT_VARIANTS'] ?></h4>
				<div class="field-variant">
						<?php if (empty($field['vars'])) { ?>
						<p class="field">
							<input type="text"
								   title="<?php echo $lang['SELECT_VARIANTS'] ?>"
								   placeholder="Введите новый вариант">
							<button title="<?php echo $lang['DELETE'] ?>"
									aria-label="<?php echo $lang['DELETE'] ?>"
									class="js-field-var-delete field__var-delete">
								<i class="fa fa-trash" aria-hidden="true"></i>
							</button>
						</p>
						<?php } else {
						 foreach ($field['vars'] as $var) { ?>
						<p class="field">
							<input type="text" value="<?= $var ?>">
							<button title="<?php echo $lang['DELETE'] ?>"
									aria-label="<?php echo $lang['DELETE'] ?>"
									class="js-field-var-delete field__var-delete">
								<i class="fa fa-trash" aria-hidden="true"></i>
							</button>
						</p>
						<?php }
					} ?>
				</div>
				<div class="row">
					<div class="large-12 columns">
						<button class="button primary add-popup-field"
								title="<?php echo $lang['USERFIELD_SETTINGS_9'] ?>"
								aria-label="<?php echo $lang['USERFIELD_SETTINGS_9'] ?>">
							<i class="fa fa-plus aria-hidden="
							   aria-hidden="true"></i>
							<?php echo $lang['USERFIELD_SETTINGS_9'] ?>
						</button>
						<button class="button success fl-right apply-popup"
								title="<?php echo $lang['STAT_LOCALE_4'] ?>"
								aria-label="<?php echo $lang['STAT_LOCALE_4'] ?>">
							<i class="fa fa-check" aria-hidden="true"></i>
							<?php echo $lang['STAT_LOCALE_4'] ?>
						</button>
					</div>
				</div>
			</div>
		</td>
		<?php } ?>

		<?php if (in_array($addData, array('product'))): ?>
		<td>
			<div class="checkbox fl-left" style="margin: 6px 10px;">
				<input type="checkbox" class="prodIsPrice-<?= $key ?> dSave" id="prodIsPrice-<?= $key ?>" name="isPrice" <?= $field['is_price']?'checked':'' ?>>
				<label for="prodIsPrice-<?= $key ?>"></label>
			</div>
		</td>
		<?php endif ?>

		<td class="text-right action-list">
			<?php if (isset($field['active'])) { ?>
                <button class="btn-eye tooltip--small tooltip--center"
                        flow="rightUp"
                        tooltip="Выводить на сайте">
                    <i class="fa fa-eye <?= $field['active'] == 1 ? 'active' : '' ?>"
                       aria-hidden="true"></i>
                </button>
			<?php } ?>
			<button class="js-field-delete field__delete tooltip--small tooltip--center"
                    flow="left"
					tooltip="<?php echo $lang['DELETE_ROW'] ?>"
					aria-label="<?php echo $lang['DELETE_ROW'] ?>">
				<i class="fa fa-trash" aria-hidden="true"></i>
			</button>
		</td>
	</tr>
<?php } ?>