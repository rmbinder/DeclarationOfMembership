<?php
/**
 ***********************************************************************************************
 * Verarbeiten der Einstellungen des Admidio-Plugins DeclarationOfMembership
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * form     : The name of the form preferences that were submitted.
 * 
 * mode     : 1 - Save preferences
 *            2 - show dialog for deinstallation
 *            3 - deinstall
 *
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Utils\SecurityUtils;

require_once(__DIR__ . '/../../system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePDM();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!isUserAuthorizedForPreferences())
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'numeric', array('defaultValue' => 1));
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');

// in ajax mode only return simple text on error
if ($getMode == 1 && $getForm != 'emailnotification')
{
    $gMessage->showHtmlTextOnly(true);
}

$ret_message = 'success';

switch ($getMode)
{
    case 1:
        
    try
    {
        switch ($getForm)
        {
            case 'displayed_fields':
                unset($pPreferences->config['main_texts']);
                unset($pPreferences->config['fields']['profile_fields']);

                foreach ($gProfileFields->getProfileFields() as $field)
                {
                    if (isset($_POST[$field->getValue('usf_id')]))
                    {
                        $pPreferences->config['fields']['profile_fields'][] = $field->getValue('usf_id');  
                    }
                }
                
                if ($_POST['main_pretext'] <> '')
                {
                    $pPreferences->config['main_texts']['main_pretext'] = $_POST['main_pretext'];
                }
                if ($_POST['main_posttext'] <> '')
                {
                    $pPreferences->config['main_texts']['main_posttext'] = $_POST['main_posttext'];
                }
                
                $pPreferences->config['usr_login_name']['displayed'] = isset($_POST['usr_login_name']) ? 1 : 0;
                
                $ret_message = 'refresh';
                break;

            case 'required_fields':
                unset($pPreferences->config['cat_texts']);
                unset($pPreferences->config['field_texts']);
                unset($pPreferences->config['fields']['required_fields']);
               
                foreach ($gProfileFields->getProfileFields() as $field)
                {
                    if (isset($_POST['rqd-'.$field->getValue('usf_id')]))
                    {
                        $pPreferences->config['fields']['required_fields'][] = $field->getValue('usf_id');
                    }
                    if (isset($_POST[$field->getValue('cat_id'). '_pretext']))
                    {
                        $pPreferences->config['cat_texts'][$field->getValue('cat_id'). '_pretext'] = $_POST[$field->getValue('cat_id'). '_pretext'];
                    }
                    if (isset($_POST[$field->getValue('cat_id'). '_posttext']))
                    {
                        $pPreferences->config['cat_texts'][$field->getValue('cat_id'). '_posttext'] = $_POST[$field->getValue('cat_id'). '_posttext'];
                    }
                    if (isset($_POST[$field->getValue('usf_id'). '_fieldtext']))
                    {
                        $pPreferences->config['field_texts'][$field->getValue('usf_id'). '_fieldtext'] = $_POST[$field->getValue('usf_id'). '_fieldtext'];
                    }
                }
                $pPreferences->config['usr_login_name']['required'] = isset($_POST['usr_login_name_rqd']) ? 1 : 0;
                if (isset($_POST['usr_login_name_fieldtext']))
                {
                    $pPreferences->config['usr_login_name']['fieldtext'] = $_POST['usr_login_name_fieldtext'];
                }
                break;
                
            case 'emailnotification':
                $pPreferences->config['emailnotification']['access_to_module'] = $_POST['enable_emailnotification'];
                    
                if (isset($_POST['msg_subject']))
                {
                    $pPreferences->config['emailnotification']['msg_subject'] = $_POST['msg_subject'];
                }
                if (isset($_POST['msg_body']))
                {
                    $pPreferences->config['emailnotification']['msg_body'] = $_POST['msg_body'];
                }
                $pPreferences->save();
                    
                $gMessage->setForwardUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', 1000);
                $gMessage->show($gL10n->get('SYS_SAVE_DATA'));
                break;
                
            case 'access_preferences':
                if (isset($_POST['access_preferences']))
                {
                    $pPreferences->config['access']['preferences'] = array_filter($_POST['access_preferences']);
                }
                else 
                {
                    $pPreferences->config['access']['preferences'] = array();
                }
                break;
                
            case 'options':
                $pPreferences->config['registration_org']['org_id'] = $_POST['org_id'];
                $pPreferences->config['options']['kiosk_mode'] = $_POST['kiosk_mode'];
                break;
            
            default:
                $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
        }
    }
    catch(AdmException $e)
    {
        $e->showText();
    }

    $pPreferences->save();
    echo $ret_message;
    break;

    case 2:
        
        $headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINSTALLATION');
        
        // add current url to navigation stack
        $gNavigation->addUrl(CURRENT_URL, $headline);
        
        // create html page object
        $page = new HtmlPage('plg-declaration-of-membership-deinstallation', $headline);
        
        // add current url to navigation stack
        $gNavigation->addUrl(CURRENT_URL, $headline);
        
        $page->addHtml('<p class="lead">'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINSTALLATION_FORM_DESC').'</p>');
        
        // show form
        $form = new HtmlForm('deinstallation_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('mode' => 3)), $page);
        $radioButtonEntries = array('0' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_ACTORGONLY'), '1' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_ALLORG') );
        $form->addRadioButton('deinst_org_select',$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_ORG_CHOICE'),$radioButtonEntries, array('defaultValue' => '0'));
        $form->addSubmitButton('btn_deinstall', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINSTALLATION'), array('icon' => 'bi-trash', 'class' => ' col-sm-offset-3'));
        
        // add form to html page and show page
        $page->addHtml($form->show(false));
        $page->show();
        break;
        
    case 3:
        
        $gNavigation->clear();
        $gMessage->setForwardUrl($gHomepage);
        
        $gMessage->show($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_STARTMESSAGE').$pPreferences->delete($_POST['deinst_org_select']) );
        break;
}

