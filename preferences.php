<?php
/**
 ***********************************************************************************************
 * Erzeugt das Einstellungen-Menue fuer das Admidio-Plugin DeclarationOfMembership
 *
 * @copyright 2004-2019 The Admidio Team
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

$gNavigation->addUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage($headline);

if($showOption == 'refresh')
{
    $page->addJavascript('$("#tabs_nav_preferences").attr("class", "active");
        $("#tabs-preferences").attr("class", "tab-pane active");
        $("#collapse_displayed_fields").attr("class", "panel-collapse collapse in");
        location.hash = "#" + "panel_displayed_fields";', true);
}
else
{
    $page->addJavascript('$("#tabs_nav_preferences").attr("class", "active");
        $("#tabs-preferences").attr("class", "tab-pane active");
        ', true);
}

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        $("#"+id+" .form-alert").hide();

        // disable default form submit
        event.preventDefault();

        $.ajax({
            type:    "POST",
            url:     action,
            data:    $(this).serialize(),
            success: function(data) {
                if(data == "success" || data == "refresh") {
                    $("#"+id+" .form-alert").attr("class", "alert alert-success form-alert");
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-ok\"></span><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    $("#"+id+" .form-alert").fadeIn("slow");
                    $("#"+id+" .form-alert").animate({opacity: 1.0}, 2500);
                    $("#"+id+" .form-alert").fadeOut("slow");
                    if(data == "refresh") {
                        window.location.replace("'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?show_option=refresh");
                    }    
                }
                else {
                    $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                    $("#"+id+" .form-alert").fadeIn();
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span>"+data);
                }
            }
        });
    });
', true);

// create module menu with back link
$headerMenu = new HtmlNavbar('menu_preferences', $headline, $page);
$headerMenu->addItem('menu_item_back', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/declaration_of_membership.php'), $gL10n->get('SYS_BACK'), 'back.png');

$page->addHtml($headerMenu->show(false));

$page->addHtml('
<ul class="nav nav-tabs" id="preferences_tabs">
    <li id="tabs_nav_preferences"><a href="#tabs-preferences" data-toggle="tab">'.$gL10n->get('SYS_SETTINGS').'</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane" id="tabs-preferences">
        <div class="panel-group" id="accordion_preferences">

             <div class="panel panel-default" id="panel_displayed_fields">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_displayed_fields">
                            <img src="'. THEME_URL .'/icons/list.png" alt="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DISPLAYED_FIELDS').'" title="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DISPLAYED_FIELDS').'" />'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DISPLAYED_FIELDS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_displayed_fields" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('displayed_fields_form', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'displayed_fields')), $page, array('class' => 'form-preferences'));
                        $form->addDescription($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DISPLAYED_FIELDS_DESC'));

                        $form->addMultilineTextInput('main_pretext',$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PRETEXT'), (isset($pPreferences->config['main_texts']['main_pretext']) ? $pPreferences->config['main_texts']['main_pretext'] : '' ), 3, array('helpTextIdLabel' => 'PLG_DECLARATION_OF_MEMBERSHIP_PRE_AND_POST_TEXT_DESC' ));
                        $form->addLine();
                        $form->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');

                            $category = '';
                            foreach($gProfileFields->getProfileFields() as $field)
                            {
                                $usf_id     = $field->getValue('usf_id');
                                $cat_name   = $field->getValue('cat_name');
                                
                                // bei Kategorienwechsel den Kategorienheader anzeigen
                                if($category !== $cat_name)
                                {
                                    if($category !== '')
                                    {
                                        $form->closeGroupBox();
                                    }
                                    $category = $cat_name;
                                    $form->openGroupBox($cat_id, $category);
                                }
                                $form->addCheckbox($usf_id, $field->getValue('usf_name'), (in_array($usf_id, $pPreferences->config['fields']['profile_fields']) ? 1 : 0));
                            }
                            $form->closeGroupBox();
                        $form->addDescription('</div>');
                        $form->addLine();
                        $form->addMultilineTextInput('main_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['main_texts']['main_posttext']) ? $pPreferences->config['main_texts']['main_posttext'] : '' ), 3);
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_required_fields">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_required_fields">
                            <img src="'. THEME_URL .'/icons/bullet_red.png" alt="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_REQUIRED_FIELDS').'" title="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_REQUIRED_FIELDS').'" />'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_REQUIRED_FIELDS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_required_fields" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('displayed_fields_form', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'required_fields')), $page, array('class' => 'form-preferences'));
                        $form->addDescription($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_REQUIRED_FIELDS_DESC'));

                        $category = '';
                        $findFields = false;
                        foreach($gProfileFields->getProfileFields() as $field)
                        {
                            $usf_id     = $field->getValue('usf_id');
                            $cat_id     = $field->getValue('cat_id');
                            $cat_name   = $field->getValue('cat_name');
                            
                            if(in_array($usf_id, $pPreferences->config['fields']['profile_fields']))
                            {
                                $findFields = true;
                                
                                // bei Kategorienwechsel den Kategorienheader anzeigen
                                if($category !== $field->getValue('cat_name'))
                                {
                                    if($category !== '')
                                    {
                                        $form->addMultilineTextInput($cat_id.'_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['cat_texts'][$cat_id.'_posttext']) ? $pPreferences->config['cat_texts'][$cat_id.'_posttext'] : '' ), 3);
                                        $form->closeGroupBox();
                                    }
                                    $category = $cat_name;
                                    $form->openGroupBox($cat_id, $category);
                                    $form->addMultilineTextInput($cat_id.'_pretext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PRETEXT'), (isset($pPreferences->config['cat_texts'][$cat_id.'_pretext']) ? $pPreferences->config['cat_texts'][$cat_id.'_pretext'] : '' ), 3);  
                                }
                                $form->addCheckbox('rqd-'.$usf_id, $field->getValue('usf_name'), (in_array($usf_id, $pPreferences->config['fields']['required_fields']) ? 1 : 0));
                                $form->addMultilineTextInput($usf_id.'_fieldtext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_FIELDTEXT'), (isset($pPreferences->config['field_texts'][$usf_id.'_fieldtext']) ? $pPreferences->config['field_texts'][$usf_id.'_fieldtext'] : '' ), 1);
                            }
                        }
                        
                        if ($findFields)
                        {
                            $form->addMultilineTextInput($cat_id.'_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['cat_texts'][$cat_id.'_posttext']) ? $pPreferences->config['cat_texts'][$cat_id.'_posttext'] : '' ), 3);
                            $form->closeGroupBox();
                        }
                        
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_options">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_options">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_OPTIONS').'" title="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_OPTIONS').'" />'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_OPTIONS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_options" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                    $form = new HtmlForm('options_form', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'options')), $page, array('class' => 'form-preferences'));
                            
                        $sql = 'SELECT org_id, org_longname
                                  FROM '.TBL_ORGANIZATIONS.'
                              ORDER BY org_longname ASC, org_shortname ASC';
                        $form->addSelectBoxFromSql('org_id', $gL10n->get('SYS_ORGANIZATION'), $gDb, $sql, array('defaultValue' => $pPreferences->config['registration_org']['org_id'], 'helpTextIdInline' => 'PLG_DECLARATION_OF_MEMBERSHIP_ORGANIZATION_DESC'));
                        
                        $html = '<a id="deinstallation" class="icon-text-link" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?mode=2"><img
                                    src="'. THEME_URL . '/icons/delete.png" alt="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_LINK_TO').'" />'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_LINK_TO').'</a>';
                        $form->addCustomContent($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINSTALLATION'), $html, array('helpTextIdInline' => 'PLG_DECLARATION_OF_MEMBERSHIP_DEINSTALLATION_DESC'));
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_access_preferences">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_access_preferences">
                            <img src="'. THEME_URL .'/icons/lock.png" alt="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES').'" title="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES').'" />'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_access_preferences" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                    $form = new HtmlForm('access_preferences_form', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'access_preferences')), $page, array('class' => 'form-preferences'));
                    
                        $sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
                                FROM '.TBL_CATEGORIES.' AS cat, '.TBL_ROLES.' AS rol
                                WHERE cat.cat_id = rol.rol_cat_id
                                AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                                OR cat.cat_org_id IS NULL )';
                        $form->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextIdInline' => 'PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES_DESC', 'multiselect' => true, 'property' => FIELD_REQUIRED));
                        $form->addSubmitButton('btn_save_access_preferences', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

        </div>
    </div>
</div>
');

$page->show();
