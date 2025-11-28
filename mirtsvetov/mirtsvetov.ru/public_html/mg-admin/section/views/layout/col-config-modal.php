<?php $lang = MG::get('lang'); ?>
<div class="reveal-overlay" style="display:none;">
	<div class="reveal xssmall col-config-modal" id="<?php echo $addData ?>-col-config-modal" data-type="<?php echo $addData ?>">
		<button class="close-button closeModal" data-close="" type="button">
			<i class="fa fa-times-circle-o"></i>
		</button>
		<div class="reveal-header">
			<h2><i class="fa fa-cogs"></i> <?php echo $lang['LAYOUT_CATALOG_1']; ?></h2>
		</div>
		<div class="reveal-body">
			<div class="sortable-block columns-sorter">
				<div class="left-side columns-sorter__col">
					<h3><?php echo $lang['LAYOUT_CATALOG_7']; ?></h3>
					<ul class="sortable-list colFieldsList border-color inactiveColFields">
						<?php foreach ($data['inactiveColumns'] as $key => $name) {
							printCol($key, $name);
						} ?>
					</ul>
				</div>
				<div class="right-side columns-sorter__col">
					<h3><?php echo $lang['VISIBLE_FIELDS']; ?></h3>
					<ul class="sortable-list colFieldsList border-color activeColFields">
						<?php foreach ($data['activeColumns'] as $key => $name) {
							printCol($key, $name);
						} ?>
					</ul>
				</div>
			</div>
			<div class="clearfix"></div>
		</div>
		<div class="reveal-footer clearfix">
			<a class="button success fl-right save-button" href="javascript:void(0);">
				<i class="fa fa-floppy-o"></i> <?php echo $lang['SAVE']; ?>
			</a>
		</div>
	</div>
</div>

<?php 
function printCol($key, $name) { ?>
	<li class="titleField border-color" data-id="<?php echo $key ?>">
		<button aria-label="Переместить в неактивные"
				tooltip="Переместить в неактивные"
				class="moveToInactive tooltip--small tooltip--center">
			<i class="fa fa-arrow-left"></i>
		</button>
		<i class="fa fa-arrows"></i>
		<span class="titleField"><?php echo $name ?></span>
		<button aria-label="Переместить в активные"
				tooltip="Переместить в активные"
				class="moveToActive tooltip--small tooltip--center">
			<i class="fa fa-arrow-right"></i>
		</button>
	</li>
<?php } ?>