<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster mit Plugininformationen
 *
 * @copyright 2004-2019 The Admidio Team
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

$html = '';

// set headline of the script
$headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_INFORMATION');

// create html page object
$page = new HtmlPage($headline);

//$page = null;
header('Content-type: text/html; charset=utf-8');

$html .= '
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">'.$headline.'</h4>
</div>
<div class="modal-body">';
    $form = new HtmlForm('plugin_informations_form', null, $page);
    $form->addStaticControl('plg_name', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_NAME'), $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_HEADLINE'));
    $form->addStaticControl('plg_version', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
    $form->addStaticControl('plg_date', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
 
    $doclink = '<a class="btn" href="documentation.pdf" target="_blank">
        <img src="'. THEME_URL . '/icons/eye.png" alt="'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DOCUMENTATION_OPEN').'" />'.$gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DOCUMENTATION_OPEN').'</a>';
    $form->addCustomContent($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DOCUMENTATION'), $doclink);
  
    $html .= $form->show(false);

echo $html.'</div>';			// end-div class="modal-body"

