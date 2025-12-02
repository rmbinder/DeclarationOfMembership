<?php
/**
 ***********************************************************************************************
 * Installation routine for the Admidio plugin DeclarationOfMembership
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Menu\Entity\MenuEntry;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only administrators are allowed to start this module
    if (! $gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // prüfen, ob ein Menüpunkt vorhanden ist und ggf. neu anlegen

    // dazu zuerst die Id (men_id) der Menüebene 'Erweiterungen' ermitteln
    $menuParent = new MenuEntry($gDb);
    $menuParent->readDataByColumns(array(
        'men_name_intern' => 'extensions'
    ));
    $menIdParent = $menuParent->getValue('men_id');

    $menu = new MenuEntry($gDb);

    // einen eventuell vorhandenen Menüpunkt einlesen
    $menu->readDataByColumns(array(
        'men_url' => FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php'
    ));

    // Daten für diesen Menüpunkt eingeben
    $menu->setValue('men_men_id_parent', $menIdParent);
    $menu->setValue('men_url', FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php');
    $menu->setValue('men_icon', 'person-fill-add');
    $menu->setValue('men_name', 'PLG_DECLARATION_OF_MEMBERSHIP_NAME');
    $menu->setValue('men_description', 'PLG_DECLARATION_OF_MEMBERSHIP_NAME_DESC');
    $menu->save();

    // damit am Bildschirm die Menüeinträge aktualisiert werden: alle Sesssions neu laden
    $gCurrentSession->reloadAllSessions();

    // im letzten Schritt die Konfigurationsdaten bearbeiten

    $pPreferences = new ConfigTable();

    // prüfen, ob die Konfigurationstabelle bereits vorhanden ist und ggf. neu anlegen oder aktualisieren
    if ($pPreferences->checkforupdate()) {
        $pPreferences->init();
    }

    // für die Uninstall-Routine: die ID des Menüpunktes in der Konfigurationstabelle speichern
    $pPreferences->config['install']['menu_item_id'] = $menu->getValue('men_id');           
    
    $pPreferences->save();

    admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/declaration_of_membership.php');
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}