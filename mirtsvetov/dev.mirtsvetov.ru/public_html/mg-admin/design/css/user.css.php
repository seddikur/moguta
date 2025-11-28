<?php
	$scheme = unserialize(stripslashes(MG::getSetting('interface')));
	$scheme['adminBar'] = (isset($scheme['adminBar']) && !empty($scheme['adminBar'])) ? $scheme['adminBar']:'#F3F3F3';
	$scheme['adminBarFontColor'] = (isset($scheme['adminBarFontColor']) && !empty($scheme['adminBarFontColor'])) ? $scheme['adminBarFontColor']:'#3c3c3c';
?>

<style>
	/* основная цветовая схема */
	.mg-admin-html table.main-table tr.selected td:first-child { 
		border-left-color: <?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .checkbox label:after {
		border-left:2px solid <?php echo $scheme['colorMain']; ?>;
		border-bottom:2px solid <?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .checkbox input[type=checkbox]:checked+label,
	.checkbox input[type=radio]:checked+label,
	.radio input[type=checkbox]:checked+label,
	.radio input[type=radio]:checked+label, {
		border-color:<?php echo $scheme['colorMain']; ?>!important;
	}
	.mg-admin-html .radio label:after,
	.mg-admin-html .jquery-ui-sorter-placeholder,
	.mg-admin-html button.templates-cards__card-button,
	.templates-cards__card-button {
		background:<?php echo $scheme['colorMain']; ?>!important;
	}
	.mg-admin-html .table-pagination .pagination li.current a {
		color:#fff;
        background:<?php echo $scheme['colorMain']; ?>;
		border-color:<?php echo $scheme['colorMain']; ?>;
	}

	.mg-admin-html button.templates-cards__card-demo,
	.templates-cards__card-demo {
		color:<?php echo $scheme['colorMain']; ?>!important;
	}

	.mg-admin-html .modal .fa {
		color:<?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .ui-slider .ui-slider-range {
		background:<?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .tabs.custom-tabs li a {
		color:<?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .tabs.custom-tabs li.is-active a {
		background:<?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .wrapper .header .header-top {
		background:<?php echo $scheme['colorMain']; ?>;
	}
	.balance-icon {
		fill: <?php echo $scheme['colorMain']; ?>;
	}

	.mp-balance-block__price,
	.mp-balance-block__currency {
		color: <?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .wrapper .header .header-nav .top-menu .mg-admin-mainmenu-item:before {
		background:<?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .section-settings .file-template.editing-file,
	.mg-admin-html .section-settings #customAdminLogo {
		background-color:<?php echo $scheme['colorMain']; ?>;
	}
	.mg-admin-html .button.primary,
	.mg-admin-html .button.primary:focus,
	.mg-admin-html .button.primary:hover,
	.mg-admin-html .button,
	.mg-admin-html .button:focus,
	.mg-admin-html .button:hover,
	.admin-top-menu,
	.mg-admin-html .sk-folding-cube .sk-cube:before {
		background:<?php echo $scheme['colorMain']; ?>;
	}
	/* цвета ссылок */
	.mg-admin-html .link,
	.mg-admin-html a,
    .mg-admin-html table.main-table tr .product-name .name-link {
		color: <?php echo $scheme['colorLink']; ?>;
	}
    .mg-admin-html .link span:not([tooltip]) {
        border-bottom: 1px dashed <?php echo $scheme['colorLink']; ?>;
    }
    .mg-admin-html .link:hover span {
        border-bottom-color: transparent;
    }

	/* кнопка сохранения */
	.mg-admin-html .button.success,
	.mg-admin-html .button.success:focus,
	.mg-admin-html .button.success:hover,
	.mg-admin-html .button.success:hover,
	.templates-cards__card._active .templates-cards__card-button,
	.templates-cards__constructor._active .templates-cards__card-button,
	.mg-admin-html button.templates-cards__card-button:hover,
	.templates-cards__card-button:hover {
		background-color: <?php echo $scheme['colorSave']; ?> !important;
	}

	.mg-admin-html .templates-cards__ready .template-active-info {
		border-color: <?php echo $scheme['colorSave']; ?> !important;
	}

	/* кнопка обновления способа оплаты */
	.mg-admin-html #tab-paymentMethod-settings .updatePlugin a {
		color: <?php echo $scheme['colorSave']; ?> !important;
	}

	/* рамки */
	.mg-admin-html .widget.add-order .widget-footer, 
	.mg-admin-html .widget.settings .widget-footer, 
	.mg-admin-html .widget.table .widget-footer,
	.mg-admin-html .widget-panel,
	.mg-admin-html .main-table td,
	.mg-admin-html .checkbox label,
	.mg-admin-html select,
	.mg-admin-html .linkPage,
	.mg-admin-html input,
	.mg-admin-html textarea,
	.mg-admin-html .reveal-header,
	.mg-admin-html .reveal-footer,
	.mg-admin-html label,
	.mg-admin-html .accordion-item,
	.mg-admin-html .price-settings,
	.mg-admin-html .price-footer,
	.mg-admin-html .color,
	.mg-admin-html .size-map th,
	.mg-admin-html .size-map td,
	.mg-admin-html .border-color,
	.integration-container .sideBorder,
	.mg-admin-html .filter-form .range-field .input-range,
	.mg-admin-html .border-top,
	.mg-admin-html .SumoSelect,
    .mg-admin-html .search-block__btn,
    #add-product-wrapper .userField .assortmentCheckBox,
    .mg-admin-html .reveal .cat-img,
    .mg-admin-html .checkbox input[type=checkbox]:checked + label,
    .mg-admin-html .reveal .symbol-text,
    .mg-admin-html .order-fieldset__h2,
    .mg-admin-html .status-table__title,
    .mg-admin-html .open-col-config-modal,
    .mg-admin-html .accordion .accordion-item,
    .mg-admin-html .settings-footer,
    .mg-admin-html .CodeMirror.cm-s-default,
    .mg-admin-html .dashed,
    .mg-admin-html .wrapper .edit-key,
    .mg-admin-html .step-update-li-1.current,
    .mg-admin-html .step-update-li-2.current,
    .mg-admin-html .step-update-li-3.current,
    .mg-admin-html .whole-modal-info__name,
    .mg-admin-html #tab-opfields-settings .field-variant i,
    .section-marketplace .showMore,
	.section-marketplace .showMore {
		border-color: <?php echo $scheme['colorBorder']; ?> !important;
	}
	.mg-admin-html .main-table td {
		border-left: 0 !important;
	}

	/* прочие кнопки */
	.mg-admin-html .button.secondary,
	.mg-admin-html .button.secondary:focus,
	.mg-admin-html .button.secondary:hover {
		background-color: <?php echo $scheme['colorSecondary']; ?> !important;
	}

	.admin-bar,
	.admin-bar-configeditor .template-design {
		background-color: <?php echo $scheme['adminBar'];?> !important;
	}
	
	.admin-bar span,
	.admin-bar .reset-config span,
	.admin-bar .row-wrapper,
	.admin-bar .row-value[type='text'],
	.admin-bar .accordion .accordion-item .accordion-title,
	.admin-bar .accordion .accordion-item .accordion-title .accordion-title__text,
	.admin-bar .template-set__title,
	.admin-bar .SumoSelect .select-all>label,
	.admin-bar .SumoSelect input::placeholder,
	.admin-bar .SumoSelect>.CaptionCont,
	.admin-bar .SumoSelect>.optWrapper>.options li.opt label{
		color: <?php echo $scheme['adminBarFontColor']; ?>!important;
	}

	html:not(.mg-admin-html) .reveal-overlay {
		background: -webkit-gradient(linear, left top, left bottom, from(<?php echo $scheme['adminBar']; ?>), to(rgba(255, 255, 255, 1)));
		background: -o-linear-gradient(top, <?php echo $scheme['adminBar']; ?> 0%, rgba(255, 255, 255, 1) 100%);
		background: linear-gradient(to bottom, <?php echo $scheme['adminBar']; ?> 0%, rgba(255, 255, 255, 1) 100%);
	}

	.admin-bar-link__icon {
		fill: <?php echo ($scheme['adminBarFontColor']) ? $scheme['adminBarFontColor']:'#3c3c3c'; ?>!important;
	}
	.admin-bar .modal-templates-button span,
	.admin-bar__item .admin-bar-link__title {
		color: <?php echo ($scheme['adminBarFontColor']) ? $scheme['adminBarFontColor']:'#3c3c3c'; ?>!important;
	}
</style>