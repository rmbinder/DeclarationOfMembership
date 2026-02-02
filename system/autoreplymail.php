<?php
/**
 ***********************************************************************************************
 * Edit autoreplay mail of the Admidio plugin DeclarationOfMembership
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : edit - edit subject and body
 *            save - save subject and body
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only administrators are allowed to start this module
    if (! $gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'edit',
        'validValues' => array(
            'edit',
            'save'
        )
    ));

    switch ($getMode) {
        case 'edit':

            global $gL10n;

            $title = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_AUTOREPLYMAIL');
            $headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_AUTOREPLYMAIL');

            $gNavigation->addUrl(CURRENT_URL, $headline);

            // create html page object
            $page = PagePresenter::withHtmlIDAndHeadline('plg-declarationofmembership-autoreplymail-html');
            $page->setTitle($title);
            $page->setHeadline($headline);

            $formEditAutoreplymail = new FormPresenter('declarationofmembership_edit_autoreply__form', '../templates/edit.autoreplymail.plugin.declarationofmembership.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/autoreplymail.php', array(
                'mode' => 'save'
            )), $page, array(
                'type' => 'default',
                'method' => 'post',
                'setFocus' => false
            ));

            $formEditAutoreplymail->addInput('msg_subject', $gL10n->get('SYS_SUBJECT'), $pPreferences->config['emailnotification']['msg_subject'], array(
                'maxLength' => 77,
                'property' => FormPresenter::FIELD_REQUIRED
            ));

            $formEditAutoreplymail->addEditor('msg_body', '', $pPreferences->config['emailnotification']['msg_body'], array(
                'toolbar' => 'AdmidioDefault',
                'property' => FormPresenter::FIELD_REQUIRED
            ));

            $formEditAutoreplymail->addSubmitButton('adm_button_save', $gL10n->get('SYS_SAVE'), array(
                'icon' => 'bi-check-lg'
            ));

            $formEditAutoreplymail->addToHtmlPage(false);
            $page->show();

            break;

        case 'save':

            if (isset($_POST['msg_subject'])) {
                $pPreferences->config['emailnotification']['msg_subject'] = $_POST['msg_subject'];
            }
            if (isset($_POST['msg_body'])) {
                $pPreferences->config['emailnotification']['msg_body'] = $_POST['msg_body'];
            }
            $pPreferences->save();

            $gMessage->setForwardUrl($gNavigation->getPreviousUrl(), 2000);
            $gMessage->show($gL10n->get('SYS_SAVE_DATA'));

            break;
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}