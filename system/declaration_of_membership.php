<?php
/**
 ***********************************************************************************************
 * Creates the main view for the plugin DeclarationOfMembertship
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Users\Entity\User;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/common_function.php');

$pPreferences = new ConfigTable();
$pPreferences->read();

// read user data
$user = new User($gDb, $gProfileFields);

$headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_NAME');

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
    unset($_SESSION['profile_request']);
}

$gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-person-fill-add');

// create html page object
$page = new HtmlPage('plg-declaration-of-membership', $headline);

$page->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/zxcvbn/dist/zxcvbn.js');

if (isUserAuthorizedForPreferences())
{
    // show link to pluginpreferences
    $page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('SYS_SETTINGS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/system/preferences.php'),  'bi-gear-fill');
}

// create html form
$form = new HtmlForm('edit_profile_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/system/declaration_save.php'), $page);

// icon-link to info
$html = '<p align="right">
            <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/system/declaration_popup_info.php').'">'.'
                <i class="bi bi-info-circle" data-toggle="tooltip" title="' . $gL10n->get('SYS_INFORMATIONS') . '"></i>
            </a>
        </p>';
$form->addDescription($html);

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
        
        if ($field->getValue('cat_name') === $gL10n->get('SYS_BASIC_DATA') && $pPreferences->config['usr_login_name']['displayed'])
        {
            $fieldProperty = HtmlForm::FIELD_DEFAULT;
            
            if ($pPreferences->config['usr_login_name']['required'])
            {
                $fieldProperty = HtmlForm::FIELD_REQUIRED;
            }
            $form->addInput(
                'usr_login_name',
                $gL10n->get('SYS_USERNAME'),
                '',
                array('maxLength' => 254, 'property' => $fieldProperty, 'class' => 'form-control-small')
                );
            if (isset($pPreferences->config['usr_login_name']['fieldtext']))
            {
                $form->addCustomContent('', create_html($pPreferences->config['usr_login_name']['fieldtext']));
            }
            $form->addLine();
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
                    'helpTextId' => $helpId,
                    'icon'            => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database')
                )
            );
        }
        elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DROPDOWN' || $gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DROPDOWN_MULTISELECT' || $usfNameIntern === 'COUNTRY')
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
                $arrListValues = $gProfileFields->getProperty($usfNameIntern, 'ufo_usf_options', '', false);
                $defaultValue = $user->getValue($usfNameIntern, 'database');
                // if the field is a dropdown multiselect then convert the values to an array
                if ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DROPDOWN_MULTISELECT') {
                    // prevent adding an empty string to the selectbox
                    $defaultValue = ($defaultValue !== "") ? explode(',', $defaultValue) : array();
                }
            }
            
            $form->addSelectBox(
                'usf-'. $gProfileFields->getProperty($usfNameIntern, 'usf_id'),
                $gProfileFields->getProperty($usfNameIntern, 'usf_name'),
                $arrListValues,
                array(
                    'property'     => $fieldProperty,
                    'defaultValue' => $defaultValue,
                    'helpTextId'   => $helpId,
                    'icon'         => 'bi-' . $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database')
                )
            );
        }
        elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'RADIO_BUTTON')
        {
            $showDummyRadioButton = false;
            if ($gProfileFields->getProperty($usfNameIntern, 'usf_required_input') == 0)
            {
                $showDummyRadioButton = true;
            }

            $form->addRadioButton(
                'usf-'.$gProfileFields->getProperty($usfNameIntern, 'usf_id'),
                $gProfileFields->getProperty($usfNameIntern, 'usf_name'),
                $gProfileFields->getProperty($usfNameIntern, 'ufo_usf_options', 'html', false),
                array(
                    'property'          => $fieldProperty,
                    'defaultValue'      => $user->getValue($usfNameIntern, 'database'),
                    'showNoValueButton' => $showDummyRadioButton,
                    'helpTextId'   => $helpId,
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
                    'helpTextId' => $helpId,
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
                    'helpTextId' => $helpId,
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
    if ($gSettingsManager->getBool('registration_enable_captcha'))
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
    $form->addSubmitButton('btn_save', $gL10n->get('SYS_SEND'), array('icon' => 'bi-send'));
}

$page->addHtml($form->show(false));
$page->show();
