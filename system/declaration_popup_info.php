<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster mit Plugininformationen
 *
 * @copyright The Admidio Team
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * ****************************************************************************
 * Parameters: none
 * ***************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // set headline of the script
    $headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_INFORMATION');

    $infoText = '
        <div class="row">
            <div class="col-4"><strong>' . $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_NAME') . ':</strong></div>
            <div class="col-8">' . $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_NAME') . ' (DeclarationOfMembership)' . '</div>
        </div>
        <div class="row">
            <div class="col-4"><strong>' . $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_VERSION') . ':</strong></div>
            <div class="col-8">' . $pPreferences->config['Plugininformationen']['version'] . '</div>
        </div>
        <div class="row">
            <div class="col-4"><strong>' . $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PLUGIN_DATE') . ':</strong></div>
            <div class="col-8">' . $pPreferences->config['Plugininformationen']['stand'] . '</div>
        </div>';

    $gMessage->showInModalWindow();
    $gMessage->show($infoText, $headline);
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

