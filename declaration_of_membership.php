<?php
/**
 ***********************************************************************************************
 * DeclarationOfMembership (ONLINE-Beitrittserklärung)
 *
 * Version 2.0.0
 *
 * This plugin creates an online - declaration of membership.
 * 
 *
 * Author: rmb
 *
 * Compatible with Admidio version 4
 *
 * @copyright 2004-2022 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

//script_name ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/mitgliedsbeitrag...
$_SESSION['pDeclarationOfMembership']['script_name'] = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

$registrationOrgId = '';

$pPreferences = new ConfigTablePDM();
if ($pPreferences->checkforupdate())
{
    $pPreferences->init();
}
else
{
    $pPreferences->read();
}

// read user data
$user = new User($gDb, $gProfileFields);

$headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_HEADLINE');

// Formular wurde ueber "Nein"-Button aufgerufen, also alle Felder mit den vorherigen Werten fuellen
if (isset($_SESSION['profile_request']) && StringUtils::strContains($gNavigation->getUrl(), 'declaration_save.php'))
{
    $user->noValueCheck();
    
    foreach($gProfileFields->getProfileFields() as $field)
    {
        $fieldName = 'usf-'. $field->getValue('usf_id');
        if (isset($_SESSION['profile_request'][$fieldName]))
        {
            $user->setProfileFieldsValue($field->getValue('usf_name_intern'), stripslashes($_SESSION['profile_request'][$fieldName]));
        }
    }
    if (isset($_SESSION['profile_request']['reg_org_id']))
    {
        $registrationOrgId = $_SESSION['profile_request']['reg_org_id'];
    }
    unset($_SESSION['profile_request']);
}

if (!StringUtils::strContains($gNavigation->getUrl(), 'declaration_of_membership.php'))
{
    $gNavigation->addStartUrl(CURRENT_URL, $headline);
}

// create html page object
$page = new HtmlPage('plg-declaration-of-membership', $headline);

$page->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS_CLIENT . '/zxcvbn/dist/zxcvbn.js');

if (isUserAuthorizedForPreferences())
{
    // show link to pluginpreferences
    $page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('SYS_SETTINGS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php'),  'fa-cog');
}

// create html form
$form = new HtmlForm('edit_profile_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/declaration_save.php'), $page);

// icon-link to info
$html = '<p align="right">
            <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/declaration_popup_info.php').'">'.'
                <i class="fas fa-info-circle" data-toggle="tooltip" title="' . $gL10n->get('SYS_INFORMATIONS') . '"></i>
            </a>
        </p>';
$form->addDescription($html);

if (strlen($pPreferences->config['registration_org']['org_id']) == 0)
{
    $sql = 'SELECT org_id, org_longname
              FROM '.TBL_ORGANIZATIONS.'
          ORDER BY org_longname ASC, org_shortname ASC';
    $form->addSelectBoxFromSql('reg_org_id', $gL10n->get('SYS_ORGANIZATION'), $gDb, $sql, array('property' => HtmlForm::FIELD_REQUIRED, 'defaultValue' => $registrationOrgId));
    $form->addLine();
}

// *******************************************************************************
// Loop over all categories and profile fields
// *******************************************************************************

if (isset($pPreferences->config['main_texts']['main_pretext']))
{
    $form->addDescription(create_html($pPreferences->config['main_texts']['main_pretext']));
}

$category = '';
$findFields = false;

foreach ($gProfileFields->getProfileFields() as $field)
{
    $showField = false;
      
    if (in_array($field->getValue('usf_id'), $pPreferences->config['fields']['profile_fields']))       
    {
        $showField = true;
        $findFields = true;
    }
    
    // bei Kategorienwechsel den Kategorienheader anzeigen
    if ($category !== $field->getValue('cat_name') && $showField)
    {
        if ($category !== '')
        {
            if (isset($pPreferences->config['cat_texts'][$field->getValue('cat_id').'_posttext']))
            {
                $form->addDescription(create_html($pPreferences->config['cat_texts'][$field->getValue('cat_id').'_posttext']));
            }
            
            // div-Container admGroupBoxBody und admGroupBox schliessen
            $form->closeGroupBox();
        }
        $category = $field->getValue('cat_name');
        
        $form->addHtml('<a id="cat-'. $field->getValue('cat_id'). '"></a>');
        $form->openGroupBox('gb_category_'.$field->getValue('cat_name_intern'), $field->getValue('cat_name'));
        
        if (isset($pPreferences->config['cat_texts'][$field->getValue('cat_id').'_pretext']))
        {
            $form->addDescription(create_html($pPreferences->config['cat_texts'][$field->getValue('cat_id').'_pretext']));        
        }
    }
    
    if ($showField)
    {
        // add profile fields to form
        $fieldProperty = HtmlForm::FIELD_DEFAULT;
        $helpId        = '';
        $usfNameIntern = $field->getValue('usf_name_intern');
       
        if (in_array($field->getValue('usf_id'), $pPreferences->config['fields']['required_fields']))
        {
            $fieldProperty = HtmlForm::FIELD_REQUIRED;
        }
        
        if (strlen($gProfileFields->getProperty($usfNameIntern, 'usf_description')) > 0)
        {
            $helpId = $gProfileFields->getProperty($gProfileFields->getProperty($usfNameIntern, 'usf_name_intern'), 'usf_description');
        }
        
        // code for different field types
        if ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'CHECKBOX')
        {
            $form->addCheckbox(
                'usf-'. $gProfileFields->getProperty($usfNameIntern, 'usf_id'),
                $gProfileFields->getProperty($usfNameIntern, 'usf_name'),
                (bool) $user->getValue($usfNameIntern),
                array(
                    'property'        => $fieldProperty,
                    'helpTextIdLabel' => $helpId,
                    'icon'            => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database')
                )
            );
        }
        elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DROPDOWN' || $usfNameIntern === 'COUNTRY')
        {
            // set array with values and set default value
            if ($usfNameIntern === 'COUNTRY')
            {
                $arrListValues = $gL10n->getCountries();
                $defaultValue  = null;
                
                if ((int) $user->getValue('usr_id') === 0 && strlen($gSettingsManager->getString('default_country')) > 0)
                {
                    $defaultValue = $gSettingsManager->getString('default_country');
                }
                elseif ($user->getValue('usr_id') > 0 && strlen($user->getValue($usfNameIntern)) > 0)
                {
                    $defaultValue = $user->getValue($usfNameIntern, 'database');
                }
            }
            else
            {
                $arrListValues = $gProfileFields->getProperty($usfNameIntern, 'usf_value_list');
                $defaultValue  = $user->getValue($usfNameIntern, 'database');
            }
            
            $form->addSelectBox(
                'usf-'. $gProfileFields->getProperty($usfNameIntern, 'usf_id'),
                $gProfileFields->getProperty($usfNameIntern, 'usf_name'),
                $arrListValues,
                array(
                    'property'        => $fieldProperty,
                    'defaultValue'    => $defaultValue,
                    'helpTextIdLabel' => $helpId,
                    'icon'            => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database')
                )
            );
        }
        elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'RADIO_BUTTON')
        {
            $showDummyRadioButton = false;
            if ($gProfileFields->getProperty($usfNameIntern, 'usf_mandatory') == 0)
            {
                $showDummyRadioButton = true;
            }
            
            $form->addRadioButton(
                'usf-'.$gProfileFields->getProperty($usfNameIntern, 'usf_id'),
                $gProfileFields->getProperty($usfNameIntern, 'usf_name'),
                $gProfileFields->getProperty($usfNameIntern, 'usf_value_list'),
                array(
                    'property'          => $fieldProperty,
                    'defaultValue'      => $user->getValue($usfNameIntern, 'database'),
                    'showNoValueButton' => $showDummyRadioButton,
                    'helpTextIdLabel'   => $helpId,
                    'icon'              => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database')
                )
            );
        }
        elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'TEXT_BIG')
        {
            $form->addMultilineTextInput(
                'usf-'. $gProfileFields->getProperty($usfNameIntern, 'usf_id'),
                $gProfileFields->getProperty($usfNameIntern, 'usf_name'),
                $user->getValue($usfNameIntern),
                3,
                array(
                    'maxLength'       => 4000,
                    'property'        => $fieldProperty,
                    'helpTextIdLabel' => $helpId,
                    'icon'            => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database')
                )
            );
        }
        else
        {
            $fieldType = 'text';
            
            if ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DATE')
            {
                if ($usfNameIntern === 'BIRTHDAY')
                {
                    $fieldType = 'birthday';
                }
                else
                {
                    $fieldType = 'date';
                }
                $maxlength = '10';
            }
            elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'EMAIL')
            {
                // email could not be longer than 254 characters
                $fieldType = 'email';
                $maxlength = '254';
            }
            elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'URL')
            {
                // maximal browser compatible url length will be 2000 characters
                $maxlength = '2000';
            }
            elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'NUMBER')
            {
                $fieldType = 'number';
                $maxlength = array(0, 9999999999, 1);
            }
            elseif ($gProfileFields->getProperty($usfNameIntern, 'cat_name_intern') === 'SOCIAL_NETWORKS')
            {
                $maxlength = '255';
            }
            else
            {
                $maxlength = '50';
            }
            
            $form->addInput(
                'usf-'. $gProfileFields->getProperty($usfNameIntern, 'usf_id'), 
                $gProfileFields->getProperty($usfNameIntern, 'usf_name'), 
                $user->getValue($usfNameIntern),
                array(
                    'type'            => $fieldType,
                    'maxLength'       => $maxlength,
                    'property'        => $fieldProperty,
                    'helpTextIdLabel' => $helpId,
                    'icon'            => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database')
                )
            );
        }
        if (isset($pPreferences->config['field_texts'][$field->getValue('usf_id').'_fieldtext']))
        {
            $form->addCustomContent('', create_html($pPreferences->config['field_texts'][$field->getValue('usf_id').'_fieldtext']));
        }
    }
}

if ($findFields)
{
    // div-Container admGroupBoxBody und admGroupBox schliessen
    $form->closeGroupBox();

    // if captchas are enabled then visitors of the website must resolve this
    if ($gSettingsManager->getBool('enable_registration_captcha'))
    {
        $form->openGroupBox('gb_confirmation_of_input', $gL10n->get('SYS_CONFIRMATION_OF_INPUT'));
        $form->addCaptcha('captcha_code');
        $form->closeGroupBox();
    }

    if (isset($pPreferences->config['main_texts']['main_posttext']))
    {
        $form->addDescription(create_html($pPreferences->config['main_texts']['main_posttext']));
    }
    
    // Daten senden
    $form->addSubmitButton('btn_save', $gL10n->get('SYS_SEND'), array('icon' => 'fa-paper-plane'));
}

$page->addHtml($form->show(false));
$page->show();
