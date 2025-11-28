<li class="accordion-item" data-accordion-item="">
	<a class="accordion-title js-auto-meta"
	   href="javascript:void(0);"><?php echo $lang['SEO_BLOCK'] ?></a>
	<div class="accordion-content seo-wrapper" data-tab-content="">
		<div class="row js-meta-block meta-block">
			<div class="small-12 medium-3 columns">
				<label for="meta_title" class="with-tooltip">
                    <span><?php echo $lang['META_TITLE']; ?>:</span>
					<span tooltip="Укажите заголовок страницы, который будет отображаться в названии вкладки браузера. Рекомендуемая длина до 65 символов"
                          class="tooltip--small-left"
                          flow="right">
						<i class="fa fa-question-circle tip"
						   aria-hidden="true"></i>
					</span>
				</label>
			</div>
			<div class="small-12 medium-9 columns meta-block__row">
				<input id="meta_title"
				       type="text"
				       name="meta_title"
				       class="product-name-input meta-data js-meta-data">
				<div class="symbol-text">
					<span class="js-count-meta js-count-meta--title">0</span>
					<span> / </span>
					<span class="js-count-max">65</span>
				</div>
			</div>
		</div>
		<div class="row js-meta-block meta-block">
			<div class="small-12 medium-3 columns">
				<label class="with-tooltip" for="meta_keywords">
                    <span><?php echo $lang['META_KEYWORDS']; ?>:</span>
					<span tooltip="Укажите через запятую ключевые слова и фразы, характеризующие содержание страницы. Рекомендуемая длина до 250 символов"
					      class="tooltip--small-left"
                          flow="right">
						<i class="fa fa-question-circle tip"
						   aria-hidden="true"></i>
					</span>
				</label>
			</div>
			<div class="small-12 medium-9 columns meta-block__row">
				<input id="meta_keywords"
				       type="text"
				       name="meta_keywords"
				       class="product-name-input meta-data js-meta-data">
				<div class="symbol-text">
					<span class="js-count-meta js-count-meta--keyw">0</span>
					<span> / </span>
					<span class="js-count-max">250</span>
				</div>
			</div>
		</div>
		<div class="row js-meta-block meta-block row--textarea">
			<div class="small-12 medium-3 columns">
					<label for="meta_desc" class="with-tooltip">
                        <span><?php echo $lang['META_DESC']; ?>:</span>
						<span tooltip="Укажите краткое описание страницы для поисковых систем. Рекомендуемая длина до 200 символов"
                              class="tooltip--small-left"
                              flow="right">
							<i class="fa fa-question-circle tip"
							   aria-hidden="true"></i>
						</span>
					</label>
			</div>
			<div class="small-12 medium-9 columns meta-block__row">
				<textarea id="meta_desc"
						  class="product-meta-field js-meta-data"
				          name="meta_desc"></textarea>
				<div class="symbol-text">
					<span class="js-count-meta js-count-meta--desc">0</span>
					<span> / </span>
					<span class="js-count-max">200</span>
				</div>
			</div>
		</div>
		<div class="row text-right">
            <div class="small-12 medium-12 columns">
			<button class="button secondary tip seo-gen-tmpl"
                    aria-label="<?php echo $lang['SEO_GEN_TMPL']; ?>"
                    tooltip="<?php echo $lang['T_TIP_SEO_GEN_TMPL']; ?>"
                    flow="up">
				<i class="fa fa-refresh"
				   aria-hidden="true"></i>
				<?php echo $lang['SEO_GEN_TMPL']; ?>
			</button>
            </div>
		</div>
	</div>
</li>
