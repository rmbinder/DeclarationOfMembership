<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster mit Plugininformationen
 *
 * @copyright The Admidio Team
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:      none
 *****************************************************************************/

use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/common_function.php');

$pPreferences = new ConfigTable();
$pPreferences->read();

// set headline of the script
$headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_INFORMATION');

// create html page object
$page = new HtmlPage('plg-declaration-of-membership-info', $headline);

header('Content-type: text/html; charset=utf-8');

$form = new HtmlForm('plugin_informations_form', '', $page);
$form->addHtml('
    <div class="modal-header">
        <h3 class="modal-title">'.$headline.'</h3>
        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
    ');
$form->addStaticControl('plg_name', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_NAME'), $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_NAME').' (DeclarationOfMembership)');
$form->addStaticControl('plg_version', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
$form->addStaticControl('plg_date', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);

if (!$pPreferences->config['options']['kiosk_mode'])
{
    $html = '<a class="icon-text-link" href="documentation.pdf" target="_blank"><i class="bi bi-file-earmark-pdf"></i> '.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DOCUMENTATION_OPEN').'</a>';
    $form->addCustomContent($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DOCUMENTATION'), $html);
}

$form->addHtml('</div>');
echo $form->show();


