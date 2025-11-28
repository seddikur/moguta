<?php
$docroot = URL::getDocumentRoot();
$template = MG::getSetting('templateName');
$templateActive = null;
$templateColorNames = [];
//Берем текущий цвет шаблона
$colorSchemeActive = URL::get('color') ? URL::get('color') : (isset($_COOKIE['color']) ? $_COOKIE['color'] : null);
$currentUrl = URL::getClearUri();

//Берем всё доступные для шаблона цвета
$from = '';
$dir = $docroot . PATH_TEMPLATE;
$pageLocale = MG::get('templateLocale');
$adminLocale = MG::getOption('languageLocale');
$templateConfig = SITE_DIR.DS."mg-templates".DS.$template.DS.'config.ini';
/*if($pageLocale != "" && $pageLocale != "default"){
  $templateConfigEn = SITE_DIR.DS."mg-templates".DS.$template.DS.'config-'.$pageLocale.'.ini';
  if(!file_exists($templateConfig)){
    if (!copy($templateConfig, $templateConfigEn)) {
			return false;
    }
    $templateConfig = $templateConfigEn;
  }

}*/
if (defined('TEMPLATE_INHERIT_FROM') && defined('TEMPLATE_COLOR_FILES') && TEMPLATE_COLOR_FILES == 'variable') {
  $templateColors = MG::get('templateColors');
  $userDefinedTemplateColors = MG::getSetting('userDefinedTemplateColors', 1);
  $userColors = $templateColors[key($templateColors)];
  foreach ($templateColors as $key => $value) {
    $schemes[] = ['name' => $key, 'color' => str_replace('#', '', $value[key($value)]), 'colors' => json_encode($value)];
    if (empty($templateColorNames)) {
      if(!empty($templateColors['colorTitles'])) {
        $templateColorNames = $templateColors['colorTitles'];
        unset($templateColors['colorTitles']);
      } else {
        $templateColorNames = array_combine(array_keys($value), array_keys($value));
      }
    }
  }
  if (!empty($userDefinedTemplateColors) && !empty($userDefinedTemplateColors[$template])) {
    $tmp = array_diff_key($userDefinedTemplateColors[$template], $userColors);
    if (empty($tmp)) {
      $userColors = $userDefinedTemplateColors[$template];
    }
  }
  $correctUserColors = [];
  foreach ($templateColorNames as $key => $value) {
    if(isset($userColors[$key])) $correctUserColors[$key] = $userColors[$key];
    else $correctUserColors[$key] = '#0a0a0a';
  }
  $userColors = $correctUserColors;
} else {
  $schemes = array();
  if (file_exists($dir . '/css/color-scheme')) {
    $colorScheme = array_diff(scandir($dir . '/css/color-scheme'), array('..', '.'));
    if (!empty($colorScheme)) {
      foreach ($colorScheme as $scheme) {
        if (strpos($scheme, 'color') === 0) {
          $color = str_replace(array('color_', '.css'), '', $scheme);
          $schemes[] = ['name' => $color, 'color' => $color, 'colors' => ''];
          $from = '/css/color-scheme/color_';
        }
      }
    }
  }
  if (empty($schemes)) {
    $colorScheme = array_diff(scandir($dir . '/css'), array('..', '.'));
    if (!empty($colorScheme)) {
      foreach ($colorScheme as $scheme) {
        if (strpos($scheme, 'style_') === 0) {
          $color = str_replace(array('style_', '.css'), '', $scheme);
          $schemes[] = ['name' => $color, 'color' => $color, 'colors' => ''];
          $from = '/css/style_';
        }
      }
    }
  }
}

//Загружаем шрифты
$fontList = MG::getGoogleFonts();

//Загружаем настройки
$color = MG::getSetting('colorScheme');
$fontSite = MG::getSetting('fontSite');
$texture = MG::getSetting('backgroundTextureSite');

#<Запоминаем фон>
$path = '/mg-admin/design/images/bg_textures/';
$backgr = (MG::getSetting('backgroundSite') != '')
  ? SITE . MG::getSetting('backgroundSite')
  : '';

$backgrTexture = (MG::getSetting('backgroundTextureSite') != '')
  ? MG::getSetting('backgroundTextureSite')
  : '';

$backgrColor = (MG::getSetting('backgroundColorSite') != '')
  ? MG::getSetting('backgroundColorSite')
  : '';

$jsonStyle = '';
if ($backgr) {
  if (MG::getSetting('backgroundSiteLikeTexture') == 'true') {
    $jsonStyle = "{'background': 'url(" . $backgr . ")'}";
  } else {
    $jsonStyle = "{'background': 'url(" . $backgr . ") no-repeat fixed center center /100% auto #fff'}";
  }
} else if ($backgrTexture) {
  $jsonStyle = "{'background': 'url(" . SITE . $path . $backgrTexture . ")'}";
} else if ($backgrColor) {
  $jsonStyle = "{'background-color': '" . $backgrColor . "', 'background-image': 'none'}";
}
#</Запоминаем фон>

$image = MG::getSetting('backgroundSite');
if (!empty($image)) {
  $texture = '';
}

//Активный класс
$active = 'template-set__item_active';
?>

<?php
  $activeClass = isset($_SESSION['user']->enabledSiteEditor) && $_SESSION['user']->enabledSiteEditor == "true";
?>

<button class="template-design__close js-design-popup-close" aria-label="<?php echo $lang['DESC_PUBLIC_BAR_CANCEL'] ?>" title="<?php echo $lang['DESC_PUBLIC_BAR_CANCEL'] ?>">
  <i class="fa fa-backward" aria-hidden="true"></i>
</button>
<dialog class="template-design js-design-popup template-design_hidden template-not-saas">
    <div class="template-design_promo">
      <button class="admin-bar-link--inner js-toggle-popup-design" title="<?php echo $lang['DESC_PUBLIC_BAR_9'] ?>">
          <svg class="admin-bar-link__icon admin-bar-link__icon_template" aria-hidden="true" enable-background="new 0 0 150 25" viewBox="0 0 40 25" xmlns="http://www.w3.org/2000/svg">
                              <g fill="#fff">
                                  <path d="m6.3 15.2-3.9-7.1c-.1 1.3-.3 2.6-.5 3.8.8 2 1.6 3.9 2.4 5.9l-2.1 5.4c-.5.3-.9.6-1.4.8-.1.4-.3.7-.4 1.1h4.5c.7-1 1.3-2.3 2-3.2 1.4-.9 2.8-1.9 4.2-2.8-1.7-1.3-4.8-3.9-4.8-3.9z"/>
                                  <path d="m11.7 3.5c-.8-.4-1.6-.7-2.4-1.1-.2-.4-.4-.7-.7-1.1-.3-.2-.7-.4-1-.6-.5.3-1 .6-1.4.9-.2.7-.4 1.4-.6 2.1 1-.1 2.9-.4 2.9-.4 1.1 0 2.1.1 3.2.2z"/>
                                  <path d="m24.1 23.6c-.3.2-.7.4-1 .7-.1.3-.2.5-.4.9h4.4c.3-2 .6-3.3.8-4.3-.3-.6-.6-1.2-.9-1.8-1.9.2-3.8.4-5.7.6.5.7.9 1.4 1.4 2.1.5.5 1.4 1.8 1.4 1.8z"/>
                                  <path d="m16.8 4.4c-.3-.4-.7-.8-1-1.3-1.1-.4-2.3-.8-3.4-1.3-.1-.3-.2-.7-.3-1-.2-.2-.5-.4-.7-.6-.3.1-.6.2-1 .4-.2.3-.4.6-.7.9 1.9.8 3.9 1.6 5.7 2.6 0 0 .6 1.3.9 2 .9.1 1.8.3 2.7.4.1.1.2.2.3.3v.1.1l-.6.3.4.5c-.2.4-.3.7-.5 1.1-.5.5-.9 1-1.4 1.6-.8-.1-1.5-.3-2.3-.4l.7.7c-.5.1-1.1.3-1.6.4-1.5-.3-3.9-.6-3.9-.6l4.2 12.2c-.5.4-1 .7-1.5 1.1-.1.4-.3.8-.4 1.2h5c.4-1.3 1.2-3.9 1.2-3.9l-.3-2.9 9.1-1.1s1.3-1.5 2-2.2c-.2 1.2-.5 2.4-.7 3.6.4.7.9 1.4 1.4 2.2s1.5 2.2 1.5 2.2c-.5.3-1 .7-1.4 1-.2.4-.4.8-.5 1.2h5.5c.5-1.3 1-2.5 1.6-3.8-.1-3-.2-5.5-.3-8.4h1.5c-.9-1.7-1.9-3.5-2.8-5.2-2.9-1.6-5.7-3.1-8.5-4.6-3.3.4-6.6.8-9.9 1.2z"/>
                                  <path d="m13 5.6c.5-.2.9-.4 1.4-.6.1.3.3.7.4 1-.6-.2-1.2-.3-1.8-.4z"/>
                              </g>
                          </svg>
          <span class="admin-bar-link__title">
              Настройка шаблона                    
          </span>
      </button>
      <div class="template-design_settings-btns">
        <ul class="settings-btns_list">
          <li class="settings-btns_item"><button class="settings-btns_btn settings-btns_btn--template js-admin-edit-template <?php echo ($activeClass) ? 'js-admin-edit-site admin-bar-link_edit_on' : 'active' ;?>" data-edit-mode="settings-mode"><span>Шаблон</span></button></li>
          <li class="settings-btns_item"><button class="settings-btns_btn settings-btns_btn--content js-admin-edit-site <?php echo ($activeClass) ? 'active admin-bar-link_edit_on' : 'admin-bar-link_edit_off' ;?>"><span>Контент</span></button></li>
        </ul>
      </div>
    </div>
    <?php if ($activeClass) { ?>
        <div class="template-set__inner template-set__warning">
          <p><?php echo $lang['CONSTRUCTOR_CONTENTMODE']; ?></p>
          <p><?php echo $lang['CONSTRUCTOR_CONTENT_1']; ?></p>
          <p><?php echo $lang['CONSTRUCTOR_CONTENT_2']; ?></p>
          <p><?php echo $lang['CONSTRUCTOR_CONTENT_3']; ?></p>
       </div>
     
    <?php } ?>
    <?php if(!file_exists($templateConfig) && !$activeClass): ?>
      <div class="template-design__inner template-design__inner--main">
          <div class="template-design__item template-set template-set_bg js-template-set_bg">
            <span class="template-set__title">
              <?php echo $lang['TEMPLATES_BACK'] ?>:
            </span>
                <div class="template-set__inner">
                  <?php
                  $patternsPath = SITE . $path;
                  $patternsSiteDir = SITE_DIR . $path;
                  $patterns = array_diff(scandir($patternsSiteDir), array('.', '..', 'bg_none.png'));
                  foreach ($patterns as $pattern) {
                    $path = $patternsPath . $pattern; ?>
                      <button class="template-set__item js-template-background<?php echo ($pattern == $texture) ? ' ' . $active : '' ?>"
                              aria-label="<?php echo $lang['TEMPLATES_BACK_SELECT'] ?>"
                              data-texture="<?php echo $pattern ?>"
                              data-texture-path="<?php echo $path; ?>"
                              style="background-image: url(<?php echo $path; ?>)"></button>
                  <?php } ?>
                    <input type="hidden"
                          name="backgroundTextureSite"
                          value="<?php echo $texture ?>">
                    <button class="template-set__item js-template-background template-set__item_blank<?php echo (empty($texture)) ? ' ' . $active : '' ?>"
                            aria-label="<?php echo $lang['TEMPLATES_BACK_DEL'] ?>"
                            data-texture="none"
                            data-texture-path=""
                            style="background-image: url(<?php echo $patternsPath . '/bg_none.png'; ?>)"></button>
                </div>
          </div>
          <?php if (!empty($schemes)): ?>
              <div class="template-design__item template-set template-set_color js-template-set_color">
                <span class="template-set__title">
                  <?php echo $lang['TEMPLATES_COLOR'] ?>:
                </span>
                  <div class="template-set__inner">
                    <div class="template-set__colors">
                      <?php
                      foreach ($schemes as $templateColor) {
                          if ($templateColor['name'] === 'colorTitles') continue; ?>
                          <button class="template-set__item template-set__item_color js-template-color <?php echo (strval($color) === strval($templateColor['name'])) ? $active : '' ?>"
                                  aria-label="<?php echo $lang['TEMPLATES_COLOR_SELECT'] ?>"
                                  data-color="<?php echo $templateColor['name']; ?>"
                                  data-colors='<?php echo $templateColor['colors']; ?>'
                                  style="background: linear-gradient(to bottom right, rgba(255, 255, 255, .25), rgba(0, 0, 0, .125));background-color: #<?php echo $templateColor['color']; ?>;"></button>
                      <?php } ?>
                    </div>                     
                    <?php if (!empty($userColors)) { ?>
                      <div class="template-set__colors">
                        <button class="template-set__item template-set__item_user-color template-set__item_color js-template-color <?php echo (strval($color) === 'user_defined') ? $active : '' ?>"
                                title="<?php echo $lang['TEMPLATES_COLOR_CUSTOM'] ?>"
                                data-color="user_defined"
                                data-colors='<?php echo json_encode($userColors); ?>'
                                data-colors_initial='<?php echo json_encode($userColors); ?>'
                                style="background: url(<?php echo SITE ?>/mg-admin/design/images/palette.png);"></button>


                        <div class="template-design__user-colors templ-user-colors"
                            id="js-templateCustomColorPopup">
                            <div class="templ-user-colors__inner">
                              <?php
                              $i = 0;
                              foreach ($userColors as $key => $value) { ?>
                                  <div class="templ-user-colors__item templ-user-color">
                                      <div class="templ-user-color__picker row"
                                          aria-label="<?php echo $lang['COLOR_SELECT'] ?>">
                                          <div id="customTemplateColor_<?php echo $i ?>"></div>
                                          <input type="hidden"
                                                class="js-customTemplateColor"
                                                id="customTemplateColor_<?php echo $i ?>"
                                                data-key="<?php echo $key ?>"
                                                name="customTemplateColor_<?php echo $i ?>"
                                                value="<?php echo $value ?>">
                                      </div>
                                      <label class="templ-user-color__var-name"
                                            for="customTemplateColor_<?php echo $i ?>">
                                          <span>– <?php echo $templateColorNames[$key] ?></span>
                                      </label>
                                  </div>
                                <?php
                                $i++;
                              } ?>
                            </div>
                              <div class="templ-user-colors__footer">
                                  <button id="js-cancelCustomColor"
                                    class="template-design__btn template-design__btn_cancel">
                                      <?php echo $lang['CANCEL'] ?>
                                  </button>
                                  <button id="js-applyCustomColor"
                                    class="template-design__btn template-design__btn_apply">
                                      <?php echo $lang['APPLY'] ?>
                                  </button>
                              </div>
                        </div>
                      </div>
                    <?php } ?>
                      <input type="hidden"
                            name="colorScheme"
                            value="<?php echo $color ?>">
                  </div>
              </div>
          <?php endif; ?>
            <div class="template-design__item template-set template-set_bg">
                <label for="template-font"
                      class="template-set__title">
                      <?php echo $lang['FONT_SITE'] ?>:
                </label>
                <div class="template-set__inner">
                  <?php if (isset($_SESSION['user']->enabledSiteEditor) && $_SESSION['user']->enabledSiteEditor == "true"): ?>
                    <?php echo $lang['DESC_TEMPLATES_FONT'] ?>
                  <?php else: ?>
                      <select name="fontSite"
                              class="template-set__select js-comboBox"
                              id="template-font">
                          <option value="default"><?php echo $lang['TEMPLATES_FONT'] ?></option>
                        <?php foreach ($fontList as $font): ?>
                            <option value="<?php echo $font ?>" <?php echo ($fontSite == $font) ? 'selected class="active"' : '' ?>><?php echo $font ?></option>
                        <?php endforeach; ?>
                      </select>
                  <?php endif; ?>
                </div>
            </div>
        </div>
        <?php  if(USER::access('setting') == 2): ?>
          <div class="admin-bar__bottom">
              <div class="settings-footer">
                  <button class="reset-config js-cancel button secondary template-design__btn template-design__btn_cancel" style="display:block"
                          title="<?php echo $lang['RESET_CONFIG']; ?>">
                      <span>
                          <i class="fa fa-reply-all" aria-hidden="true"></i>
                          <?php echo $lang['RESET_CONFIG']; ?>
                      </span>
                  </button>
                  <button class="save-config js-accept-settings button success template-design__btn template-design__btn_apply"
                          title="<?php echo $lang['SAVE_FILE']; ?>">
                      <span>
                          <i class="fa fa-floppy-o" aria-hidden="true"></i>
                          <?php echo $lang['SAVE_FILE']; ?>
                      </span>
                  </button>
              </div>
              <div class="empty-config alert-block warning" style="display:none">
                  <?php echo $lang['EMPTY_CONFIG']; ?>
              </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
        <!-- Проверка на config.ini start-->
        <?php if(file_exists($templateConfig) && !$activeClass): ?>
          <div class="template-design__item template-set mycustom-scroll js-settings-mode-container">
                <div class="template-set__inner">
                  
                <div class="admin-configeditor">
                  <div class="section-configeditor plugin-padding"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
                      <svg style="display: none;">
                          <symbol id="icon-upload" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 374.116 374.116"><path d="M344.058 207.506c-16.568 0-30 13.432-30 30v76.609h-254v-76.609c0-16.568-13.432-30-30-30-16.568 0-30 13.432-30 30v106.609c0 16.568 13.432 30 30 30h314c16.568 0 30-13.432 30-30V237.506c0-16.568-13.432-30-30-30z"/><path d="M123.57 135.915l33.488-33.488v111.775c0 16.568 13.432 30 30 30 16.568 0 30-13.432 30-30V102.426l33.488 33.488c5.857 5.858 13.535 8.787 21.213 8.787 7.678 0 15.355-2.929 21.213-8.787 11.716-11.716 11.716-30.71 0-42.426l-84.701-84.7c-11.715-11.717-30.711-11.717-42.426 0L81.144 93.489c-11.716 11.716-11.716 30.71 0 42.426 11.715 11.716 30.711 11.716 42.426 0z"/></symbol>
                      </svg>
                      <div class="admin-bar__top">
                        <div class="board-option js-templateConfig--default">
                          <div class="accordion__wrap">
                            <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true">
                              <li class="accordion-item is-active" data-accordion-item="">
                                  <a class="accordion-title content_blog_acc" href="javascript:void(0);"> <?php echo $lang['TEMPLATES_COLOR'] ?></a>
                                  <div class="accordion-content">
                                    <div class="template-design__item template-set template-set_color js-template-set_color">
                                        <div class="template-set__inner">
                                          <span class="template-set__colors-title"><?php echo $lang['TEMPLATES_COLOR_READY'] ?></span>
                                          <div class="template-set__colors">
                                            <?php ?>
                                            <?php foreach ($schemes as $templateColor) {
                                                if ($templateColor['name'] === 'colorTitles') continue; ?>
                                                <button class="template-set__item template-set__item_color js-template-color <?php echo (strval($color) === strval($templateColor['name'])) ? $active : '' ?>"
                                                        aria-label="<?php echo $lang['TEMPLATES_COLOR_SELECT'] ?>"
                                                        data-color="<?php echo $templateColor['name']; ?>"
                                                        data-colors='<?php echo $templateColor['colors']; ?>'
                                                        style="background: linear-gradient(to bottom right, rgba(255, 255, 255, .25), rgba(0, 0, 0, .125));background-color: #<?php echo $templateColor['color']; ?>;"></button>
                                            <?php } ?>
                                          </div>
                                          <?php if (!empty($userColors)) { ?>
                                            <span class="template-set__colors-title"><?php echo $lang['TEMPLATES_COLOR_CUSTOM'] ?></span>
                                            <div class="template-set__colors">
                                              <button class="template-set__item template-set__item_user-color template-set__item_color js-template-color <?php echo (strval($color) === 'user_defined') ? $active : '' ?>"
                                                      title="<?php echo $lang['TEMPLATES_COLOR_CUSTOM'] ?>"
                                                      data-color="user_defined"
                                                      data-colors='<?php echo json_encode($userColors); ?>'
                                                      data-colors_initial='<?php echo json_encode($userColors); ?>'
                                                      style="background: url(<?php echo SITE ?>/mg-admin/design/images/palette.png);"></button>


                                              <div class="template-design__user-colors templ-user-colors"
                                                  id="js-templateCustomColorPopup">
                                                  <div class="templ-user-colors__inner">
                                                    <?php
                                                    $i = 0;
                                                    foreach ($userColors as $key => $value) { ?>
                                                        <div class="templ-user-colors__item templ-user-color">
                                                            <div class="templ-user-color__picker row"
                                                                aria-label="<?php echo $lang['COLOR_SELECT'] ?>">
                                                                <div id="customTemplateColor_<?php echo $i ?>"></div>
                                                                <input type="hidden"
                                                                      class="js-customTemplateColor"
                                                                      id="customTemplateColor_<?php echo $i ?>"
                                                                      data-key="<?php echo $key ?>"
                                                                      name="customTemplateColor_<?php echo $i ?>"
                                                                      value="<?php echo $value ?>">
                                                            </div>
                                                            <label class="templ-user-color__var-name"
                                                                  for="customTemplateColor_<?php echo $i ?>">
                                                                <span><?php echo $templateColorNames[$key] ?></span>
                                                            </label>
                                                        </div>
                                                      <?php
                                                      $i++;
                                                    } ?>
                                                  </div>
                                                    <div class="templ-user-colors__footer">
                                                        <button id="js-cancelCustomColor"
                                                          class="template-design__btn template-design__btn_cancel">
                                                            <?php echo $lang['CANCEL'] ?>
                                                        </button>
                                                        <button id="js-applyCustomColor"
                                                          class="template-design__btn template-design__btn_apply">
                                                            <?php echo $lang['APPLY'] ?>
                                                        </button>
                                                    </div>
                                                    <div class="templ-user-color__reset-wrapper">
                                                      <a id="js-resetCustomColor" class="templ-user-color__reset" role="button" href="javascript:void(0);">Установить цвета по умолчанию</a>
                                                    </div>
                                              </div>
                                            </div>


                                          <?php } ?>
                                            <input type="hidden"
                                                  name="colorScheme"
                                                  value="<?php echo $color ?>">
                                        </div>
                                    </div>
                                  </div>
                              </li>
                            </ul>
                          </div>
                          <div class="accordion__wrap">
                            <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true">
                              <li class="accordion-item" data-accordion-item="">
                                <a class="accordion-title content_blog_acc" href="javascript:void(0);"> <?php echo $lang['TEMPLATES_BACK'] ?></a>
                                <div class="accordion-content" style="display: none;">
                                  <div class="template-design__item template-set template-set_bg js-template-set_bg">
                                    <div class="template-set__inner">
                                        <?php
                                        $patternsPath = SITE . $path;
                                        $patternsSiteDir = SITE_DIR . $path;
                                        
                                        $patterns = array_diff(scandir($patternsSiteDir), array('.', '..', 'bg_none.png'));
                                        foreach ($patterns as $pattern) {
                                          $path = $patternsPath . $pattern; ?>
                                            <button class="template-set__item js-template-background<?php echo ($pattern == $texture) ? ' ' . $active : '' ?>"
                                                    aria-label="<?php echo $lang['TEMPLATES_BACK_SELECT'] ?>"
                                                    data-texture="<?php echo $pattern ?>"
                                                    data-texture-path="<?php echo $path; ?>"
                                                    style="background-image: url(<?php echo $path; ?>)"></button>
                                        <?php } ?>
                                          <input type="hidden"
                                                name="backgroundTextureSite"
                                                value="<?php echo $texture ?>">
                                          <button class="template-set__item js-template-background template-set__item_blank<?php echo (empty($texture)) ? ' ' . $active : '' ?>"
                                                  aria-label="<?php echo $lang['TEMPLATES_BACK_DEL'] ?>"
                                                  data-texture="none"
                                                  data-texture-path=""
                                                  style="background-image: url(<?php echo $patternsPath . '/bg_none.png'; ?>)"></button>
                                      </div>
                                    </div>
                                  </div>
                              </li>
                            </ul>
                          </div>
                          <div class="accordion__wrap">
                            <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true">
                              <li class="accordion-item" data-accordion-item="">
                                <a class="accordion-title content_blog_acc" href="javascript:void(0);"> <?php echo $lang['FONT_SITE'] ?></a>
                                <div class="accordion-content" style="display: none;">
                                  <div class="template-design__item template-set template-set_bg">
                                      <div class="template-set__inner">
                                        <?php if (isset($_SESSION['user']->enabledSiteEditor) && $_SESSION['user']->enabledSiteEditor == "true"): ?>
                                            <?php echo $lang['DESC_TEMPLATES_FONT'] ?>
                                        <?php else: ?>
                                            <select name="fontSite"
                                                    class="template-set__select js-comboBox"
                                                    id="template-font">
                                                <option value="default"><?php echo $lang['TEMPLATES_FONT'] ?></option>
                                              <?php asort($fontList);
                                              foreach ($fontList as $font): ?>
                                                  <option value="<?php echo $font ?>" <?php echo ($fontSite == $font) ? 'selected class="active"' : '' ?>><?php echo $font ?></option>
                                              <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                      </div>
                                    </div>
                                </div>
                              </li>
                            </ul>
                          </div>
                        </div>
                        <div class="board-option js-templateConfig--custom"></div>
                      </div>
                  </div>
                  <?php  if(USER::access('setting') == 2): ?>
                      <div class="admin-bar__bottom section-configeditor">
                          <div class="settings-footer">
                              <button class="reset-config button secondary template-design__btn template-design__btn_cancel" style="display:block"
                                      title="<?php echo $lang['RESET_CONFIG']; ?>">
                                  <span>
                                      <i class="fa fa-reply-all" aria-hidden="true"></i>
                                      <?php echo $lang['RESET_CONFIG']; ?>
                                  </span>
                              </button>
                              <button class="save-config button success template-design__btn template-design__btn_apply" style="display:none"
                                      title="<?php echo $lang['SAVE_FILE']; ?>">
                                  <span>
                                      <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                      <?php echo $lang['SAVE_FILE']; ?>
                                  </span>
                              </button>
                          </div>
                          <div class="empty-config alert-block warning" style="display:none">
                              <?php echo $lang['EMPTY_CONFIG']; ?>
                          </div>
                      </div>
                      <?php endif; ?>
                  </div>
                </div>
        </div>
        <?php endif; ?>
        <!-- Проверка на config.ini end-->
</dialog>
<script id="configEditorIni">
<?php
  // Считываем и подготавливаем массив для дальнейшей работы с ним
  if(file_exists($templateConfig)){
    $ConfigEditor = new ConfigEditorPublic();
    $ConfigEditor->readIni = parse_ini_file($templateConfig,true);
    $ConfigEditor->configPreArray = file($templateConfig); //построчное чтение файла в массив
    $ConfigEditor->prepareConfigArray();

    echo 'let configEditorIni = '.json_encode($ConfigEditor).';';
    echo 'let configLang = "'.$pageLocale.'";';
    echo 'let adminLang = "'.$adminLocale.'";';
  }
?>
    $('#configEditorIni').remove();
</script>
<script>
      var TemplateSettings = (function () {
        return {
            sidebarActive: false,
            blockSidebar: false,
            btn: document.querySelectorAll('.js-toggle-popup-design'),
            popUp: document.querySelector('.js-design-popup'),
            popUpClose: document.querySelector('.js-design-popup-close'),
            templateLocale: [],
            firstAccrordion: 'true',
            newAccrodion: 'false',
            init: function () {
                const openSidebarBtn = document.querySelector('.settings-mode-open');
                for (var a = 0; a < this.btn.length; a++) {
                    this.btn[a].addEventListener('click', this.togglePopUp.bind(this));
                }
                if(this.popUpClose) {
                  this.popUpClose.addEventListener('click', this.closeDesignPopup.bind(this));
                }
                admin.includePickr();
                admin.initComboBoxes();
                if (typeof configEditorIni !== "undefined") {
                  this.readConfig();
                }
                if (openSidebarBtn) {
                  this.togglePopUp();
                }
            },
            togglePopUp: function () {
                if(this.blockSidebar) return false;
                this.sidebarActive = $('.js-design-popup').hasClass('template-design_hidden');

                this.popUp.classList.toggle('template-design_hidden');
                this.popUpClose.classList.toggle('template-design__close--able');
                if (
                    $('.js-template-color[data-color=user_defined]').length &&
                    !$('link[href="' + mgBaseDir + '/mg-core/script/pickr/themes/classic.min.css"]').length
                ) {
                    
                    includeJS(mgBaseDir + '/mg-core/script/pickr/pickr.min.js');
                    includeJS(mgBaseDir + '/mg-core/script/pickr/pickr.es5.min.js');
                    $('head').append('<link aaa rel="stylesheet" href="' + mgBaseDir + '/mg-core/script/pickr/themes/classic.min.css" type="text/css" />');
                    $('#js-templateCustomColorPopup').find('.js-customTemplateColor').each(function () {
                        admin.initPickr($(this).attr('name'));
                    });
                }
            },
            closeDesignPopup: function () {
              this.popUpClose.classList.remove('template-design__close--able');
              this.popUp.classList.add('template-design_hidden');
            },
            cancelUserColors: function () {
                if ($('.js-template-color[data-color=user_defined]').length) {
                    $('.js-template-color[data-color=user_defined]:first').data('colors', $('.js-template-color[data-color=user_defined]:first').data('colors_initial'));
                    var usercolors = $('.js-template-color[data-color=user_defined]:first').data('colors_initial');
                    if (Object.keys(usercolors).length == admin.PICKR_STORAGE.length) {
                        var i = 0;
                        $.each(usercolors, function (key, value) {
                            $('.js-customTemplateColor[data-key=' + key + ']').val(value);
                            eval('admin.PICKR_STORAGE[' + i + '].setColor(\'' + value + '\')');
                            i++;
                        });
                    }
                }
            },
            resetUserColors: function () {
              if ($('.js-template-color[data-color=user_defined]').length) {
                const firstColors = $('.template-set__colors .js-template-color:first').data('colors');
                if (!firstColors) {
                  console.error('Не удалось получить цвета первого набора');
                  return false;
                }
                $('.js-template-color[data-color=user_defined]:first').data('colors', firstColors);
                var i = 0;
                $.each(firstColors, function (key, value) {
                  $('.js-customTemplateColor[data-key=' + key + ']').val(value);
                  eval('admin.PICKR_STORAGE[' + i + '].setColor(\'' + value + '\')');
                  i++;
                });

                return true;
              }
              console.error('Не удалось найти элемент пользовательских цветов');
              return false;
            },

            acceptSettings: function (reload = true){
              var colorScheme = $('input[name="colorScheme"]').val();
              var userColors = {};
              if (colorScheme === 'user_defined') {
                  $('#js-templateCustomColorPopup .js-customTemplateColor').each(function () {
                      userColors[$(this).data('key')] = $(this).val();
                  });
              }

              var data = {
                  'mguniqueurl': "action/acceptDesignFromAdminbar",
                  'colorScheme': colorScheme,
                  'fontSite': $('select[name="fontSite"]').val(),
                  'backgroundTextureSite': $('input[name="backgroundTextureSite"]').val(),
                  userColors: userColors
              };

              admin.ajaxRequest(data, function (response) {
                if(reload == true){
                  location.reload();
                }
              });
            },
            /**
             * функция для приема файла из аплоадера
             */
            getFile: function(file) {
                $('.section-configeditor input[name="' + uploader.PARAM1.rowKey + '"][data-section="' + uploader.PARAM1.rowSection + '"]').val(file.path);
            },

            customlang: function (key) {
              return TemplateSettings.templateLocale['CONFIG_'+key]?TemplateSettings.templateLocale['CONFIG_'+key]:key;
            },

            inputType: function(section,key,value) {
              let res = '';
              let type = key.substring(0,key.indexOf('_')); // первая часть названия ключа, до символа _

              switch (type) {
                case 'checkbox':
                  break;
                case 'img':
                  break;
                case 'color':
                  break;
                case 'option':
                  break;
                case 'select':
                  break;
                default:
                  type = 'text';
              }

              if(type == 'text'){
                res ='<input class="row-value"  type="text" name="'+key+'" value="'+admin.htmlspecialchars(value.replace(/&#039;/g, "\'"))+'" data-section="'+section+'">'
              }

              /*Отрисовка чекбоксов*/
              let checked = '';
              if(value=='true'){
                checked = 'checked="checked"';
              }
              if(type=='checkbox'){
                res = '<div class="checkbox">' +
                  '  <input class="row-value" type="checkbox" name="'+key+'" id="setting-'+section+'_'+key+'" '+checked+'  data-section="'+section+'"> ' +
                  ' <span></span> ' +
                  '  <label for="setting-'+section+'_'+key+'"></label>' +
                  '</div>';
              }
              /*Отрисовка изображений*/
              if(type=='img'){
                res ='<input class="row-value" type="text"  name="'+key+'" value="'+value+'"  data-section="'+section+'"><button tooltip="Загрузить изображение" flow="leftUp" class="tooltip--small open-uploader link"><svg><use xlink:href="#icon-upload"></use></svg></button>'
              }
              /*Отрисовка цвета*/
              if(type=='color'){
                res ='<input class="option row-value color-input" type="text" name="'+section+'_'+key+'" value="'+((value[0] != '#')?('#' + value):value)+'" data-section="'+section+'">';
                res +='<div id="'+section+'_'+key+'"></div>';
              }
              /*Отрисовка опций селекта*/
              if(type=='option'){
                let options = value.split('|');
                res = '<select class="row-value row-value_select js-comboBox" name="'+section+'_'+key+'">';
                for (var i = 0; i < options.length; i++) {
                  res += '<option value="'+options[i]+'">'+options[i]+'</option>';
                }
                res += '</select>';
              }
              /*Отрисовка поля с выбранным значением селекта*/
              if(type=='select'){
                res ='<input class="row-value"  type="text" name="'+section+'_'+key+'" value="'+value+'" data-section="'+section+'">';
              }
              return res;
            },

            startAccordion: function (title,desc) {
              if(adminLang != "ru_RU"){
                desc = '';
              }
              let res ='';
              res += '<div class="accordion__wrap">\
                <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true">\
                <li class="accordion-item" data-accordion-item="">\
                <a class="accordion-title content_blog_acc" href="javascript:void(0);">\
                <span class="accordion-title__text">'+admin.htmlspecialchars(title.replace(/&#039;/g, "\'"))+'</span>';
                if(desc){
                  res += '<i class="fa fa-question" aria-hidden="true"></i>\
                  <span class="section__desc alert-block">'+desc.replace(/\[EOL\]/g, '<br/>')+'</span>';
                };
                res += '</a>\
                <div class="accordion-content" style="display: none;">';
              return res;
            },
            endAccordion: function () {
              let res ='';
              res += '</div><!--accordion-content-->\
                </li>\
                </ul>\
                </div><!--accordion__wrap-->';
              return res;
            },
        
            readConfig: function () {
              if (!!configEditorIni) {
                  $('.section-configeditor .empty-config').hide();
                  TemplateSettings.templateLocale = configEditorIni.locales;
                  // отрисовывает строки с элементами редактирования
                  $('.board-option.js-templateConfig--custom').html('');
                  let comment = '';
                  let html = '';
                  let lastCommentInSettingsSection = '';
                  $.each(configEditorIni.configArray, function(index, obj){
                    if(obj.section=='MAIN'||obj.section=='COLORS'||obj.section=='SETTINGS') {
                      comment = '';
                      //сохраняем последний комментарий в секции SETTINGS, чтобы вывести его в первом позываемом блоке					  
                      if(obj.type=='comment'){
                        lastCommentInSettingsSection = obj.line.slice(1);
                      }
                      return true;
                    }
                    let row = '';
                    if(obj.line!==""){
                      // Восстанавливаем последний комментарий из последней системной секции
                      if(lastCommentInSettingsSection!=''){
                        comment = lastCommentInSettingsSection;
                        lastCommentInSettingsSection='';
                      }
                      if(obj.type=='comment')  {
                        comment += obj.line.slice(1);
                        return true;
                      }
                      if(obj.type=='section') {
                        let titleBlock = TemplateSettings.customlang(obj.line.replace('[', '').replace(']', ''));
                        let desc = null;
                        if(comment!=''){
                          desc = comment;							 
                        }
                        if (TemplateSettings.newAccrodion == 'true') {
                          TemplateSettings.newAccrodion = 'false';
                          row += TemplateSettings.endAccordion();                          
                          TemplateSettings.newAccrodion = 'true';
                          row += TemplateSettings.startAccordion(titleBlock, desc);
                        } else {
                          TemplateSettings.newAccrodion = 'true';
                          row += TemplateSettings.startAccordion(titleBlock, desc);
                        }
                      }
                      row +='\
                        <div class="row-config row-type-'+obj.type+'">\
                        <div class="row-info" \
                        data-row-section="'+obj.section+'"\
                        data-row-type="'+obj.type+'"\
                        data-row-key="'+obj.key+'"\
                        data-row-value="'+admin.htmlspecialchars(obj.value)+'"\
                        ></div>';
                      if(obj.type=='option')  {
                        row +='\
                          <div class="row-wrapper">\
                          <div class="row-option-name">'+TemplateSettings.customlang(obj.key)+':';
                        if(comment!='' && adminLang == "ru_RU"){
                          row += '<span class="tooltop--with-line-break" tooltip="'+admin.htmlspecialchars(comment.replace(/&#039;/g, "\'"))+'" flow="leftUp"><i class="fa fa-question-circle" aria-hidden="true"></i></span>\
                                  <span class="section__desc alert-block"> '+admin.htmlspecialchars(comment)+'</span>';
                        }
                        row +='</div>';
                        row += TemplateSettings.inputType(obj.section,obj.key,obj.value);
                        row +='</div>';
                      }else{
                      }
                      row +='\
                        </div>';
                      comment = '';
                    }
                    html+=row;
                  });
                  $('.board-option.js-templateConfig--custom').html(html);
                  $('.board-option.js-templateConfig--custom').append(TemplateSettings.endAccordion());
                  admin.tooltipEOL();
                  // навешивание колорпикеров
                  $('.section-configeditor .option').each(function() {
                    admin.initPickr($(this).attr('name'));
                  });
                  //установка выбранных позиций в селектах
                  $('.section-configeditor select').each(function() {
                    let nameSelect = $(this).attr('name').replace('option','select');
                    let valueSelect = $('.section-configeditor input[name="'+nameSelect+'"]').val();
                    $(this).val(valueSelect).change();
                    $('.section-configeditor input[name="'+nameSelect+'"]').parents('.row-config').hide();
                  });
                }else{
                  $('.section-configeditor .save-config').hide();
                  $('.section-configeditor .empty-config').show();
                }
            },

            // отправляет команду на сброс конфига
            resetConfig: function () {
              admin.ajaxRequest({
                mguniqueurl: "action/resetConfigTemplate",
              },
              function (response) {
                admin.indication(response.status, response.msg);
                $('input[name="colorScheme"]').val($('.template-set__colors button:first').attr('data-color'));
                $('#template-font option').attr('selected',false).removeClass('active');
                $('#template-font option:first').attr('selected',true);
                $('.js-template-set_bg .js-template-background[data-texture="none"]').click();
                TemplateSettings.acceptSettings();
              });
            },

            // Собирает все измененеия и отправляет команду на перезапись конфига
            saveConfig: function(){
                $(".js-design-popup-close").toggleClass('template-design__close--able');
                // массив с измененеиями
                let data = [];

                // проходим по всем полям чтобы найти в каких были изменения
                $('.board-option .row-value').each(function(){
                    let key = $(this).parents('.row-config').find('.row-info').data('row-key');
                    // если в названии поля есть option_ , который только у селектов встречается, то не пропускать его чтобы не затереть
                    if(key.indexOf('option_')!==-1){
                        return true;
                    }

                    let section = $(this).parents('.row-config').find('.row-info').data('row-section');
                    let value = $(this).val();
                    let originalValue = $(this).parents('.row-config').find('.row-info').data('row-value');

                    // fix чекбоксов если value = on
                    if($(this).attr('type')=='checkbox'){
                        value = $(this).prop('checked');
                    }

                    data.push({
                        section:section,
                        key:key,
                        value:value,
                    });
                    
                });

                // массив с изменениями отправляем на сервер для перезаписи файла
                // console.log(data);
                admin.ajaxRequest({
                        mguniqueurl: "action/saveConfigTemplate",
                        data: data,
                        locale: configLang
                    },
                    function (response) {
                        admin.indication(response.status, response.msg);
                    });
                TemplateSettings.acceptSettings();
              }
        };
    })();
    document.addEventListener("DOMContentLoaded", function () {
        TemplateSettings.init();
        let configeditorAlreadyInit = false;
        $('body').on('click', '.js-toggle-popup-design', function (e) {
            if(!configeditorAlreadyInit){
              TemplateSettings.init();
              configeditorAlreadyInit = true;
              if(!TemplateSettings.blockSidebar){
                $(".js-design-popup").toggleClass('template-design_hidden');
                $(".js-design-popup-close").toggleClass('template-design__close--able');
              }
              $('body').on('click', 'button.save-current-config', function() {
                $('<div class="render-preloader"></div>').insertBefore('.main-render');
                TemplateSettings.saveConfig();
                  $.ajax({
                      type: "POST",
                      url: window.location.href,
                      cache: false,
                  success:function(response){
                      if($('#tempNodes').length == 0){
                          $("html").append('<div id="tempNodes" style="display: none;"></div>');
                        }
                          $('#tempNodes').html('').append(response.match( /\<body.+\<\/body\>/is));
                          var clone = $('#tempNodes div.main-render').children().clone(true);
                          $('#tempNodes').remove();
                          $('body div.main-render').html(clone);
                          $('.render-preloader').remove();
                          
                      }
                  });     
                                
              });
            }
            if(!TemplateSettings.blockSidebar){
              TemplateSettings.sidebarActive = !TemplateSettings.sidebarActive;
              $(".js-design-popup").toggleClass('template-design_hidden');
              $(".js-design-popup-close").toggleClass('template-design__close--able');
            }else{
              TemplateSettings.sidebarActive = $('.js-design-popup').hasClass('template-design_hidden');
            }
            
        });
        
        //ездилка для аккордионов и фикс стрелок
        $('.js-design-popup .section-configeditor').on('click', '.accordion-title', function () {
            if (!$(this).closest('.accordion-item').hasClass('is-active') && $(this).closest('ul.accordion').data('multi-expand') === false) {
                $(this).closest('ul.accordion').find('.accordion-content').slideUp();
                $(this).closest('ul.accordion').find('.accordion-item').removeClass('is-active');
            }
            $(this).closest('.accordion-item').find('.accordion-content').slideToggle();
            $(this).closest('.accordion-item').toggleClass('is-active');
        });

        $('.js-design-popup .section-configeditor .save-config').show();
        <?php if(USER::access('setting')==2): ?>
        // Сохраняет конфиг
        $('.section-configeditor').on('click', '.save-config', function () {
          TemplateSettings.saveConfig();
        });

        // Выбор изображения с сервера
        $('.section-configeditor').on('click',  '.open-uploader', function() {
            let rowInfo = $(this).parents('.row-config').find('.row-info').data();
            if($('dialog').hasClass('template-design')){
                $('.js-design-popup').addClass('template-design_hidden');
                $(".js-design-popup-close").removeClass('template-design__close--able');
                admin.cloneModal('configeditor');
                admin.PULIC_MODE = true;
            }
            admin.openUploader('TemplateSettings.getFile', rowInfo, 'uploads');
        });

        $('body').on('click',  '#modal-elfinder .uploader-modal_close', function() {
            admin.closeModal($('#modal-elfinder'));     //закрываем окно
        });

        // Сброс конфига
        $('.section-configeditor').on('click',  '.reset-config', function() {
          if (confirm("Вы действительно хотите сбросить все настройки шаблона безвозвратно?")) {
            TemplateSettings.resetConfig();
            TemplateSettings.readConfig(TemplateSettings.templateName);
          }
        });
      <?php endif; ?>
        // Построение селектов
        $('.section-configeditor').on('change',  'select', function() {
            let nameSelect = $(this).attr('name').replace('option','select');
            $('.section-configeditor input[name="'+nameSelect+'"]').val($(this).val());
        });
        
        //Переключение шрифтов
        $('select[name="fontSite"]').change(function () {
            var font = $(this).val();
            if(!font){
              font = $('#template-font option.active').val();
            }
            if (font == "default" || typeof font == "undefined") {
                $('.customFont').detach();
                $('select[name="fontSite"]').val('');
            } else {
                $('.customFont').detach();
                $(document.body).append('' +
                '<link class="customFont" rel="stylesheet" href="https://fonts.googleapis.com/css?family=' + font.replace(' ', '+') + '&subset=cyrillic">' +
                '<style class="customFont">*:not(i){font-family:' + font + ';}</style>');
            }

            $('select[name="fontSite"]').val(font);
        });

        //Переключение цвета
        $('.js-template-color').click(function () {
            var color = $(this).data('color');
            $('.js-template-set_color .template-set__item_active').removeClass('template-set__item_active');
            $(this).addClass('template-set__item_active');

            if ('<?php echo $from ?>') {
                $('#tmpCSS').detach();
                $(document.body).append('<div id="tmpCSS">\
                <link rel="stylesheet" href="<?php echo PATH_SITE_TEMPLATE . $from ?>' + color + '.css" type="text/css" /></div>');
            } else {
                if (color == 'user_defined') {
                    $('#js-templateCustomColorPopup').show();
                } else {
                    $('#js-templateCustomColorPopup').hide();
                }
                $.each($(this).data('colors'), function (index, value) {
                    document.documentElement.style.setProperty('--' + index, value);
                });
            }

            $('input[name="colorScheme"]').val(color);
            $('select[name="fontSite"]').change();
        });

        //Применение пользовательского цвета
        $('#js-applyCustomColor').click(function () {
            var colors = {};
            $('#js-templateCustomColorPopup .js-customTemplateColor').each(function () {
                document.documentElement.style.setProperty('--' + $(this).data('key'), $(this).val());
                colors[$(this).data('key')] = $(this).val();
            });
            $('#js-templateCustomColorPopup').hide();
            $('.js-template-color[data-color=user_defined]').data('colors', colors);
        });

        //Отмена пользовательского цвета
        $('#js-cancelCustomColor').click(function () {
            $('#js-templateCustomColorPopup').hide();
            TemplateSettings.cancelUserColors();
        });

        // Сброс пользовательских цветов
        $('#js-resetCustomColor').click(function () {
            TemplateSettings.resetUserColors();
        });

        //Переключение заднего фона
        $('.js-template-background').click(function () {
            var bg = $(this).data('texture');
            var bgPath = $(this).data('texture-path');

            $('.js-template-set_bg .template-set__item_active').removeClass('template-set__item_active');
            $(this).addClass('template-set__item_active');

            if (bg == 'none') {
                $(document.body).css({'backgroundImage': 'none'});
                $('input[name="backgroundTextureSite"]').val('');
            } else {
                $(document.body).css({'background': 'none'});
                $(document.body).css({'backgroundImage': 'url("' + bgPath + '")'});
                $('input[name="backgroundTextureSite"]').val(bg);
            }
        });

        //Применение настроек
        $('.js-accept-settings').click(function () {
          TemplateSettings.acceptSettings();
        });

        //Отменить настройки
        $('.js-cancel').click(function () {
            $('input[name="colorScheme"]').val('<?php echo $color ?>');
            $('select[name="fontSite"]').val('<?php echo ($fontSite != '') ? $fontSite : 'default' ?>');
            $('input[name="backgroundTextureSite"]').val('<?php echo $texture ?>');
            TemplateSettings.cancelUserColors();

            $('[data-color="<?php echo $color ?>"]').click();
            $('select[name="fontSite"]').change();
            $('[data-texture="<?php echo !empty($texture) ? $texture : 'none' ?>"]').click();

            <?php if ($jsonStyle) {
              echo '$(document.body).css('.$jsonStyle.');';
            } ?>
        });


        $('body').on('mouseenter','input.row-value', function(){
          let urlImg = $(this).val();
          if(urlImg.match(/\.[svg|webp|png|gif|ico|swf|jpe?g]/gi)){
            if($('#tempImg').length == 0){
              let offsetTop = $(this).offset().top + 20 - $('.admin-bar-configeditor').offset().top;
              let offsetLeft = parseInt($('.template-design').width()) + 5;
              let imageObj = new Image();
              imageObj.src = mgBaseDir + '/' + $(this).val();
              imageObj.onload = function() {
                let request = new XMLHttpRequest();
                request.open("HEAD", imageObj.src, false);
                request.send(null);
                let headerText = request.getAllResponseHeaders();
                let re = /Content\-Length\s*:\s*(\d+)/i;
                re.exec(headerText);
                let weghtImg =  parseInt(RegExp.$1);
                let sizeImg = this.width + 'x' + this.height;

                $('.admin-bar-configeditor').append('<div id="tempImg"><div class="tempImg__info"><span class="tempImg_size"><?php echo $lang['SIZE'] ?>: ' + sizeImg + '</span><span class="tempImg_weght">' + admin.sizeFormat(weghtImg) + "</span></div><img src='" + imageObj.src + "'></div>");
                
                let maxWidth = parseInt($('#tempImg img').css('max-width'));
                if(typeof maxWidth  !== "undefined"){
                  reSize = this.height * ( maxWidth * 100 / this.width / 100);
                } else {
                  reSize = this.height;
                }
                
                let scrollPosition = $(window).height() - (reSize + offsetTop);
                if(scrollPosition < 0){
                  $('#tempImg').css( "position", "fixed" ).css( "left", offsetLeft + "px").css( "bottom","5px");
                } else {
                  $('#tempImg').css( "position", "fixed" ).css( "left", offsetLeft + "px").css( "top", offsetTop );
                }

              };
            }
          }
        }).on('mouseleave','input.row-value', function(){
          $('#tempImg').remove();
        });

        $('body').on('click','.content_blog_acc',function(){
          $('#template-font').parent().find('p.search span').text($('#template-font option.active').val())
        });

        
    });
</script>