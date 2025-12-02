<?php
/**
 ***********************************************************************************************
 * DeclarationOfMembership (ONLINE-Beitrittserklärung)
 *
 * Version 3.0.0
 *
 * This plugin creates an online - declaration of membership.
 * 
 * Author: rmb
 *
 * Compatible with Admidio version 5
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
use Admidio\Preferences\ValueObject\SettingsManager;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../system/common.php');
    require_once (__DIR__ . '/system/common_function.php');

    $gNavigation->addStartUrl(CURRENT_URL);

    $pPreferences = new ConfigTable();
    if ($pPreferences->checkforupdate()) {
        $pPreferences->init();
    }

    $pPreferences->read();

    // prüfen, ob die MenüItemId gespeichert ist
    // wenn nicht, dann wurde entweder der Menüpunkt wurde von Hand erzeugt oder in dieser Orga ist das Plugin noch nicht installiert --> Install aufrufen
    if ($pPreferences->config['install']['menu_item_id'] == 0) {

        $urlInst = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/install.php';
        $gMessage->show($gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_INSTALL_UPDATE_REQUIRED', array(
            '<a href="' . $urlInst . '">' . $urlInst . '</a>'
        )));
    }

    // wenn kein User angemeldet ist, dann die in der Konfiguration eingestellte Organisation auswählen
    if (! $gValidLogin && in_array($pPreferences->config['registration_org']['org_id'], $pPreferences->getAllOrgIds())) {

        $gCurrentOrganization->readDataById((int) $pPreferences->config['registration_org']['org_id']);
        $gCurrentOrgId = $gCurrentOrganization->getValue('org_id');

        $gProfileFields->readProfileFields($gCurrentOrgId);

        $gCurrentSession->setValue('ses_org_id', $gCurrentOrgId);
        $gCurrentSession->save();

        $gSettingsManager = new SettingsManager($gDb, $gCurrentOrgId);
    }

    admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/declaration_of_membership.php');
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
