<?php
/**
 ***********************************************************************************************
 * Saves the new member data
 *
 * This is a modified profile_save.php
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:      none
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Email;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Users\Entity\UserRegistration;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

require_once (__DIR__ . '/../../../system/common.php');
require_once (__DIR__ . '/common_function.php');

$pPreferences = new ConfigTable();
$pPreferences->read();

// save form data in session for back navigation
$_SESSION['profile_request'] = $_POST;

$headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_COMPLETE_ENTRY');

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);

$user = new UserRegistration($gDb, $gProfileFields);

// now check all profile fields
foreach ($gProfileFields->getProfileFields() as $field) {
    $postId = 'usf-' . $field->getValue('usf_id');
    $showField = false;

    // at registration, check if the field is enabled for registration
    if (in_array($field->getValue('usf_id'), $pPreferences->config['fields']['profile_fields'])) {
        $showField = true;
    }

    if ($showField) {
        if (isset($_POST[$postId])) {
            // if social network then extract username from url
            if (in_array($field->getValue('usf_name_intern'), array(
                'FACEBOOK',
                'GOOGLE_PLUS',
                'TWITTER',
                'XING'
            ), true)) {
                if (strValidCharacters($_POST[$postId], 'url') && admStrContains($_POST[$postId], '/')) {
                    if (strrpos($_POST[$postId], '/profile.php?id=') > 0) {
                        // extract facebook id (not facebook unique name) from url
                        $_POST[$postId] = substr($_POST[$postId], strrpos($_POST[$postId], '/profile.php?id=') + 16);
                    } else {
                        if (strrpos($_POST[$postId], '/posts') > 0) {
                            $_POST[$postId] = substr($_POST[$postId], 0, strrpos($_POST[$postId], '/posts'));
                        }

                        $_POST[$postId] = substr($_POST[$postId], strrpos($_POST[$postId], '/') + 1);
                        if (strrpos($_POST[$postId], '?') > 0) {
                            $_POST[$postId] = substr($_POST[$postId], 0, strrpos($_POST[$postId], '?'));
                        }
                    }
                }
            }

            // Wert aus Feld in das User-Klassenobjekt schreiben
            $returnCode = $user->setValue($field->getValue('usf_name_intern'), $_POST[$postId]);

            // Ausgabe der Fehlermeldung je nach Datentyp
            if (! $returnCode) {
                switch ($field->getValue('usf_type')) {
                    case 'CHECKBOX':
                        $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
                        // => EXIT
                        break;
                    case 'DATE':
                        $gMessage->show($gL10n->get('SYS_DATE_INVALID', array(
                            $field->getValue('usf_name'),
                            $gSettingsManager->getString('system_date')
                        )));
                        // => EXIT
                        break;
                    case 'EMAIL':
                        $gMessage->show($gL10n->get('SYS_EMAIL_INVALID', array(
                            $field->getValue('usf_name')
                        )));
                        // => EXIT
                        break;
                    case 'NUMBER':
                    case 'DECIMAL':
                        $gMessage->show($gL10n->get('PRO_FIELD_NUMERIC', array(
                            $field->getValue('usf_name')
                        )));
                        // => EXIT
                        break;
                    case 'PHONE':
                        $gMessage->show($gL10n->get('SYS_PHONE_INVALID_CHAR', array(
                            $field->getValue('usf_name')
                        )));
                        // => EXIT
                        break;
                    case 'URL':
                        $gMessage->show($gL10n->get('SYS_URL_INVALID_CHAR', array(
                            $field->getValue('usf_name')
                        )));
                        // => EXIT
                        break;
                }
            }
        } else {
            // Checkboxen uebergeben bei 0 keinen Wert, deshalb diesen hier setzen
            if ($field->getValue('usf_type') === 'CHECKBOX') {
                $user->setValue($field->getValue('usf_name_intern'), '0');
            } elseif ($field->getValue('usf_required_input') == 1) {
                $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array(
                    $field->getValue('usf_name')
                )));
                // => EXIT
            }
        }
    }
}

// User-Login-Name nur prüfen und speichern, wenn er auch abgefragt wurde
if ($pPreferences->config['usr_login_name']['displayed']) {
    if ($_POST['usr_login_name'] !== $user->getValue('usr_login_name')) {
        if (strlen($_POST['usr_login_name']) > 0) {
            // check if the username is already assigned
            $sql = 'SELECT usr_uuid
                      FROM ' . TBL_USERS . '
                     WHERE usr_login_name = ?';
            $pdoStatement = $gDb->queryPrepared($sql, array(
                $_POST['usr_login_name']
            ));

            if ($pdoStatement->rowCount() > 0) {
                $gMessage->show($gL10n->get('PRO_LOGIN_NAME_EXIST'));
                // => EXIT
            }
        }

        if (! $user->setValue('usr_login_name', $_POST['usr_login_name'])) {
            $gMessage->show($gL10n->get('SYS_FIELD_INVALID_CHAR', array(
                $gL10n->get('SYS_USERNAME')
            )));
            // => EXIT
        }
    }
}

// At user registration with activated captcha check the captcha input
if ($gSettingsManager->getBool('registration_enable_captcha')) {
    try {
        FormValidation::checkCaptcha($_POST['captcha_code']);
    } catch (AdmException $e) {
        $e->showHtml();
        // => EXIT
    }
}

/* ------------------------------------------------------------ */
// Save user data to database
/* ------------------------------------------------------------ */
$gDb->startTransaction();

try {
    $user->save();
    $user->notifyAuthorizedMembers();

    // eine automatische Antwortmail nur senden, wenn
    // 1. das entsprechende Modul aktiviert ist
    // und 2., wenn entwender eine "Absender E-Mail" oder eine "Administrator E-Mail" definiert ist
    if ($pPreferences->config['emailnotification']['access_to_module'] && ((strlen($gSettingsManager->getString('mail_sendmail_address')) > 0) || (strlen($gSettingsManager->getString('email_administrator')) > 0))) {
        $senderEmail = $gSettingsManager->getString('email_administrator');
        $senderName = $gCurrentOrganization->getValue('org_longname');
        $receiverEmail = $user->getValue('EMAIL');
        $receiverName = $user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME');
        $msg_subject = $pPreferences->config['emailnotification']['msg_subject'];
        $msg_body = $pPreferences->config['emailnotification']['msg_body'];

        $email = new Email();
        $email->setSender($senderEmail, $senderName);
        $email->addRecipient($receiverEmail, $receiverName);
        $email->setSubject($msg_subject);

        // replace parameters in email text
        $replaces = array(
            '#user_first_name#' => $user->getValue('FIRST_NAME'),
            '#user_last_name#' => $user->getValue('LAST_NAME'),
            '#organization_name#' => $gCurrentOrganization->getValue('org_longname'),
            '#organization_shortname#' => $gCurrentOrganization->getValue('org_shortname')
        );
        $msg_body = StringUtils::strMultiReplace($msg_body, $replaces);

        $email->setText($msg_body);
        $email->setHtmlMail();
        $email->sendEmail();
    }
} catch (AdmException $e) {
    unset($_SESSION['profile_request']);
    $gMessage->setForwardUrl($gHomepage);
    $e->showHtml();
    // => EXIT
}

$gDb->endTransaction();

if ($pPreferences->config['options']['kiosk_mode']) {
    unset($_SESSION['profile_request']);
    $gMessage->setForwardUrl($gNavigation->getPreviousUrl(), 2000);
    $gMessage->show($gL10n->get('SYS_SAVE_DATA'));
} else {
    // $gMessage->setForwardYesNo($gHomepage);
    // $gMessage->show($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_SAVED'));

    // @deprecated 5.0.0:5.1.0 Method "setYesNoButton" is deprecated, use modal "admidio-messagebox" instead.

    // ich habe es nicht geschafft, die JA/Nein-Abfrage über ein Modal-Fenster (ohne einen Button zu betätigen) zu realisieren
    // deshalb die Abfrage über HtmlForm und zwei einzelne Buttons
    // toDo: beim Umbau auf die Presenter-Klassen nochmal überarbeiten

    $page = new HtmlPage('plg-declaration-of-membership-exit-or-continue', $headline);
    $form = new HtmlForm('exit-or-continue-form', '', $page);
    $form->addDescription($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_SAVED'));
    $form->addButton('btn_exit', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_EXIT'), array(
        'icon' => 'bi-x-square',
        'link' => $gHomepage,
        'class' => 'btn-primary'
    ));
    $form->addDescription($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_EXIT_DESC'));
    $form->addButton('btn_continue', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_CONTINUE'), array(
        'icon' => 'bi-pencil-square',
        'link' => $gNavigation->getPreviousUrl(),
        'class' => 'btn-primary'
    ));
    $form->addDescription($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_CONTINUE_DESC'));
    $page->addHtml($form->show(false));
    $page->show();
}
