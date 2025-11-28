<?php
$multiLang = unserialize(stripcslashes(MG::getSetting('multiLang')));
$count = 0;

if (MG::getSetting('printMultiLangSelector') == 'true' && is_array($multiLang) && !empty($multiLang)) {
	foreach ($multiLang as $mLang) {
		if ($mLang['active'] == 'true') $count++;
	}
}
if ($count) { ?>
    <span class="lang-select">
	    <?php $url = str_replace(url::getCutSection(), '', $_SERVER['REQUEST_URI']);?>
	    <svg class="icon icon--flag"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon--flag"></use></svg>

	    <select name="multiLang-selector">
		    <?php echo '<option value="'.SITE.$url.'" '.((LANG == 'LANG' || LANG == '')?'selected="selected"':"").'>'.lang('defaultLanguage').'</option>';
		    foreach ($multiLang as $mLang) {
		    	if ($mLang['active'] != 'true') {continue;}
		        echo '<option value="'.SITE.'/'.$mLang['short'].$url.'" '.((LANG == $mLang['short'])?'selected="selected"':"").'>'.$mLang['full'].'</option>';
		    } ?>
	    </select>
	</span>
<?php } ?>