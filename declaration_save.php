<?php
/**
 ***********************************************************************************************
 * Saves the new member data
 *
 * This is a modified profile_save.php
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:      none
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePDM();
$pPreferences->read();

// save form data in session for back navigation
$_SESSION['profile_request'] = $_POST;

if (!isset($_POST['reg_org_id']))
{
    $_POST['reg_org_id'] = $gCurrentOrganization->getValue('org_id');
}

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL);

$user = new UserRegistration($gDb, $gProfileFields);
$user->setOrganization((int) $_POST['reg_org_id']);

// now check all profile fields
foreach ($gProfileFields->getProfileFields() as $field)
{
    $postId    = 'usf-'. $field->getValue('usf_id');
    $showField = false;

    // at registration, check if the field is enabled for registration
    if (in_array($field->getValue('usf_id'), $pPreferences->config['fields']['profile_fields']) )    
    {
        $showField = true;
    }
       
    if ($showField)
    {
        if (isset($_POST[$postId]))
        {
            // if social network then extract username from url
            if (in_array($field->getValue('usf_name_intern'), array('FACEBOOK', 'GOOGLE_PLUS', 'TWITTER', 'XING'), true))
            {
                if (strValidCharacters($_POST[$postId], 'url') && admStrContains($_POST[$postId], '/'))
                {
                    if (strrpos($_POST[$postId], '/profile.php?id=') > 0)
                    {
                        // extract facebook id (not facebook unique name) from url
                        $_POST[$postId] = substr($_POST[$postId], strrpos($_POST[$postId], '/profile.php?id=') + 16);
                    }
                    else
                    {
                        if (strrpos($_POST[$postId], '/posts') > 0)
                        {
                            $_POST[$postId] = substr($_POST[$postId], 0, strrpos($_POST[$postId], '/posts'));
                        }

                        $_POST[$postId] = substr($_POST[$postId], strrpos($_POST[$postId], '/') + 1);
                        if (strrpos($_POST[$postId], '?') > 0)
                        {
                            $_POST[$postId] = substr($_POST[$postId], 0, strrpos($_POST[$postId], '?'));
                        }
                    }
                }
            }

            // Wert aus Feld in das User-Klassenobjekt schreiben
            $returnCode = $user->setValue($field->getValue('usf_name_intern'), $_POST[$postId]);

            // Ausgabe der Fehlermeldung je nach Datentyp
            if (!$returnCode)
            {
                switch ($field->getValue('usf_type'))
                {
                    case 'CHECKBOX':
                        $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
                        // => EXIT
                        break;
                    case 'DATE':
                        $gMessage->show($gL10n->get('SYS_DATE_INVALID', array($field->getValue('usf_name'), $gSettingsManager->getString('system_date'))));
                        // => EXIT
                        break;
                    case 'EMAIL':
                        $gMessage->show($gL10n->get('SYS_EMAIL_INVALID', array($field->getValue('usf_name'))));
                        // => EXIT
                        break;
                    case 'NUMBER':
                    case 'DECIMAL':
                        $gMessage->show($gL10n->get('PRO_FIELD_NUMERIC', array($field->getValue('usf_name'))));
                        // => EXIT
                        break;
                    case 'PHONE':
                        $gMessage->show($gL10n->get('SYS_PHONE_INVALID_CHAR', array($field->getValue('usf_name'))));
                        // => EXIT
                        break;
                    case 'URL':
                        $gMessage->show($gL10n->get('SYS_URL_INVALID_CHAR', array($field->getValue('usf_name'))));
                        // => EXIT
                        break;
                }
            }
        }
        else
        {
            // Checkboxen uebergeben bei 0 keinen Wert, deshalb diesen hier setzen
            if ($field->getValue('usf_type') === 'CHECKBOX')
            {
                $user->setValue($field->getValue('usf_name_intern'), '0');
            }
            elseif ($field->getValue('usf_mandatory') == 1)
            {
                $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($field->getValue('usf_name'))));
                // => EXIT
            }
        }
    }
}

    // At user registration with activated captcha check the captcha input
    if ($gSettingsManager->getBool('enable_registration_captcha'))
    {
        try
        {
            FormValidation::checkCaptcha($_POST['captcha_code']);
        }
        catch(AdmException $e)
        {
            $e->showHtml();
            // => EXIT
        }
    }

/*------------------------------------------------------------*/
// Save user data to database
/*------------------------------------------------------------*/
$gDb->startTransaction();

try
{
    $user->save();
}
catch(AdmException $e)
{
    unset($_SESSION['profile_request']);
    $gMessage->setForwardUrl($gHomepage);
    $e->showHtml();
    // => EXIT
}

$gDb->endTransaction();

$gMessage->setForwardYesNo($gHomepage);
$gMessage->show($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_SAVED'));


