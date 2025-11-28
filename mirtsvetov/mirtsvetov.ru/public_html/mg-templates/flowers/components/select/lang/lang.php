<?php
$multiLangRender = MG::getSetting(('printMultiLangSelector'));
if ($multiLangRender !== 'false') {

mgAddMeta('components/select/lang/lang.js');
mgAddMeta('components/select/select.css');

$multiLang = unserialize(stripcslashes(MG::getSetting('multiLang')));
$count = 0;

if (is_array($multiLang) && !empty($multiLang)) {
    foreach ($multiLang as $mLang) {
        if ($mLang['active'] == 'true') $count++;
    }
}
if ($count) { ?>
    <label class="select__wrap">
        <?php $url = str_replace(url::getCutSection(), '', $_SERVER['REQUEST_URI']);?>
        <svg class="select__icon icon icon--flag"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon--flag"></use></svg>

        <select name="multiLang-selector"
                class="select"
                id="js-lang-select"
                aria-label="Выбор языка сайта">
            <?php echo '<option value="'.SITE.$url.'" '.((LANG == 'LANG' || LANG == '')?'selected="selected"':"").'>'.lang('defaultLanguage').'</option>';
            foreach ($multiLang as $mLang) {
                if ($mLang['active'] != 'true') {continue;}
                echo '<option value="'.SITE.'/'.$mLang['short'].$url.'" '.((LANG == $mLang['short'])?'selected="selected"':"").'>'.htmlspecialchars($mLang['full']).'</option>';
            } ?>
        </select>
    </label>
<?php } } ?>