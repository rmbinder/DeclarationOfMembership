<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster mit Plugininformationen
 *
 * @copyright 2004-2020 The Admidio Team
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:      none
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePDM();
$pPreferences->read();

// set headline of the script
$headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_INFORMATION');

// create html page object
$page = new HtmlPage('plg-declaration-of-membership-info', $headline);

header('Content-type: text/html; charset=utf-8');

$form = new HtmlForm('plugin_informations_form', null, $page);
$form->addHtml('
    <div class="modal-header">
        <h3 class="modal-title">'.$headline.'</h3>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
    ');
$form->addStaticControl('plg_name', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_NAME'), $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_HEADLINE'));
$form->addStaticControl('plg_version', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
$form->addStaticControl('plg_date', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
$html = '<a class="icon-text-link" href="documentation.pdf" target="_blank"><i class="fas fa-file-pdf"></i> '.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DOCUMENTATION_OPEN').'</a>';
$form->addCustomContent($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DOCUMENTATION'), $html);

$form->addHtml('</div>');
echo $form->show();


