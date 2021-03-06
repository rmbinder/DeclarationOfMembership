<?php
/**
 ***********************************************************************************************
 * Erzeugt das Einstellungen-Menue fuer das Admidio-Plugin DeclarationOfMembership
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:      
 * 
 * show_option      : refresh - Refresh the page if changes were made in the panel displayed_fields
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// Initialize and check the parameters
$showOption = admFuncVariableIsValid($_GET, 'show_option', 'string');

$pPreferences = new ConfigTablePDM();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!isUserAuthorizedForPreferences())
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_HEADLINE');

if (!StringUtils::strContains($gNavigation->getUrl(), 'preferences.php'))
{
    $gNavigation->addUrl(CURRENT_URL);
}

// create html page object
$page = new HtmlPage('plg-declaration-of-membership-preferences', $headline);

if ($showOption == 'refresh')
{
    $page->addJavascript('
        $("#tabs_nav_common").attr("class", "nav-link active");
        $("#tabs-common").attr("class", "tab-pane fade show active");
        $("#collapse_displayed_fields").attr("class", "collapse show");
        location.hash = "#" + "panel_displayed_fields";',
        true
        );
}
else
{
    $page->addJavascript('
        $("#tabs_nav_common").attr("class", "active");
        $("#tabs-common").attr("class", "tab-pane active");',
        true
        );
}

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        var formAlert = $("#" + id + " .form-alert");
        formAlert.hide();
    
        // disable default form submit
        event.preventDefault();
    
        $.post({
            url: action,
            data: $(this).serialize(),
            success: function(data) {
                if (data === "success" || data === "refresh") {
    
                    formAlert.attr("class", "alert alert-success form-alert");
                    formAlert.html("<i class=\"fas fa-check\"></i><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                    if(data === "refresh") {
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('show_option' => 'refresh')).'");
                    } 
                } else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    formAlert.html("<i class=\"fas fa-exclamation-circle\"></i>" + data);
                }
            }
        });
    });',
    true
    );

/**
 * @param string $group
 * @param string $id
 * @param string $title
 * @param string $icon
 * @param string $body
 * @return string
 */
function getPreferencePanel($group, $id, $title, $icon, $body)
{
    $html = '
        <div class="card" id="panel_' . $id . '">
            <div class="card-header">
                <a type="button" data-toggle="collapse" data-target="#collapse_' . $id . '">
                    <i class="' . $icon . ' fa-fw"></i>' . $title . '
                </a>
            </div>
            <div id="collapse_' . $id . '" class="collapse" aria-labelledby="headingOne" data-parent="#accordion_preferences">
                <div class="card-body">
                    ' . $body . '
                </div>
            </div>
        </div>
    ';
    return $html;
}

$page->addHtml('
<ul id="preferences_tabs" class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a id="tabs_nav_common" class="nav-link" href="#tabs-common" data-toggle="tab" role="tab">'.$gL10n->get('SYS_SETTINGS').'</a>
    </li>
</ul>
    
<div class="tab-content">
    <div class="tab-pane fade" id="tabs-common" role="tabpanel">
        <div class="accordion" id="accordion_preferences">');

// PANEL: DISPLAYED_FIELDS

$formDisplayedFields = new HtmlForm('displayed_fields_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'displayed_fields')), $page, array('class' => 'form-preferences'));
$formDisplayedFields->addDescription($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DISPLAYED_FIELDS_DESC'));
$formDisplayedFields->addMultilineTextInput('main_pretext',$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PRETEXT'), (isset($pPreferences->config['main_texts']['main_pretext']) ? $pPreferences->config['main_texts']['main_pretext'] : '' ), 3, array('helpTextIdLabel' => 'PLG_DECLARATION_OF_MEMBERSHIP_PRE_AND_POST_TEXT_DESC' ));
$formDisplayedFields->addLine();
$formDisplayedFields->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');

$category = '';
foreach ($gProfileFields->getProfileFields() as $field)
{
    $usf_id     = $field->getValue('usf_id');
    $cat_id     = $field->getValue('cat_id');
    $cat_name   = $field->getValue('cat_name');
    
    // bei Kategorienwechsel den Kategorienheader anzeigen
    if ($category !== $cat_name)
    {
        if ($category !== '')
        {
            $formDisplayedFields->closeGroupBox();
        }
        $category = $cat_name;
        $formDisplayedFields->openGroupBox($cat_id, $category);
    }
    $formDisplayedFields->addCheckbox($usf_id, $field->getValue('usf_name'), (in_array($usf_id, $pPreferences->config['fields']['profile_fields']) ? 1 : 0));
}
$formDisplayedFields->closeGroupBox();
$formDisplayedFields->addDescription('</div>');
$formDisplayedFields->addLine();
$formDisplayedFields->addMultilineTextInput('main_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['main_texts']['main_posttext']) ? $pPreferences->config['main_texts']['main_posttext'] : '' ), 3);
$formDisplayedFields->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));


$page->addHtml(getPreferencePanel('common', 'displayed_fields', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DISPLAYED_FIELDS'), 'fas fa-cogs', $formDisplayedFields->show()));
                        
// PANEL: REQUIRED_FIELDS

$formRequiredFields = new HtmlForm('required_fields_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'required_fields')), $page, array('class' => 'form-preferences'));
$formRequiredFields->addDescription($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_REQUIRED_FIELDS_DESC'));

$category = '';
$findFields = false;
foreach ($gProfileFields->getProfileFields() as $field)
{
    $usf_id     = $field->getValue('usf_id');
    $cat_id     = $field->getValue('cat_id');
    $cat_name   = $field->getValue('cat_name');
    
    if (in_array($usf_id, $pPreferences->config['fields']['profile_fields']))
    {
        $findFields = true;
        
        // bei Kategorienwechsel den Kategorienheader anzeigen
        if ($category !== $field->getValue('cat_name'))
        {
            if ($category !== '')
            {
                $formRequiredFields->addMultilineTextInput($cat_id.'_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['cat_texts'][$cat_id.'_posttext']) ? $pPreferences->config['cat_texts'][$cat_id.'_posttext'] : '' ), 3);
                $formRequiredFields->closeGroupBox();
            }
            $category = $cat_name;
            $formRequiredFields->openGroupBox($cat_id, $category);
            $formRequiredFields->addMultilineTextInput($cat_id.'_pretext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PRETEXT'), (isset($pPreferences->config['cat_texts'][$cat_id.'_pretext']) ? $pPreferences->config['cat_texts'][$cat_id.'_pretext'] : '' ), 3);  
        }
        $formRequiredFields->addCheckbox('rqd-'.$usf_id, $field->getValue('usf_name'), (in_array($usf_id, $pPreferences->config['fields']['required_fields']) ? 1 : 0));
        $formRequiredFields->addMultilineTextInput($usf_id.'_fieldtext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_FIELDTEXT'), (isset($pPreferences->config['field_texts'][$usf_id.'_fieldtext']) ? $pPreferences->config['field_texts'][$usf_id.'_fieldtext'] : '' ), 1);
    }
}

if ($findFields)
{
    $formRequiredFields->addMultilineTextInput($cat_id.'_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['cat_texts'][$cat_id.'_posttext']) ? $pPreferences->config['cat_texts'][$cat_id.'_posttext'] : '' ), 3);
    $formRequiredFields->closeGroupBox();
}

$formRequiredFields->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getPreferencePanel('common', 'required_fields', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_REQUIRED_FIELDS'), 'fas fa-cogs', $formRequiredFields->show()));
                        
// PANEL: OPTIONS

$formOptions = new HtmlForm('options_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'options')), $page, array('class' => 'form-preferences'));
    
$sql = 'SELECT org_id, org_longname
          FROM '.TBL_ORGANIZATIONS.'
      ORDER BY org_longname ASC, org_shortname ASC';
$formOptions->addSelectBoxFromSql('org_id', $gL10n->get('SYS_ORGANIZATION'), $gDb, $sql, array('defaultValue' => $pPreferences->config['registration_org']['org_id'], 'helpTextIdInline' => 'PLG_DECLARATION_OF_MEMBERSHIP_ORGANIZATION_DESC'));

$html = '<a id="deinstallation" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('mode' => 2)).'">
    <i class="fas fa-trash-alt"></i> '.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_LINK_TO').'</a>';
$formOptions->addCustomContent($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINSTALLATION'), $html, array('helpTextIdInline' => 'PLG_DECLARATION_OF_MEMBERSHIP_DEINSTALLATION_DESC'));
$formOptions->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getPreferencePanel('common', 'options', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_OPTIONS'), 'fas fa-cog', $formOptions->show()));
                        
// PANEL: ACCESS_PREFERENCES
                        
$formAccessPreferences = new HtmlForm('access_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'access_preferences')), $page, array('class' => 'form-preferences'));

$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' AS cat, '.TBL_ROLES.' AS rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND ( cat.cat_org_id = '.ORG_ID.'
            OR cat.cat_org_id IS NULL )';
$formAccessPreferences->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextIdInline' => 'PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES_DESC', 'multiselect' => true, 'property' => HtmlForm::FIELD_REQUIRED));
$formAccessPreferences->addSubmitButton('btn_save_access_preferences', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getPreferencePanel('common', 'access_preferences', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES'), 'fas fa-unlock', $formAccessPreferences->show()));
                        
$page->addHtml('
        </div>
    </div>
</div>');
                        
$page->show();
