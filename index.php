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

use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

try {
    require_once(__DIR__ . '/../../system/common.php');
    require_once(__DIR__ . '/system/common_function.php');
    
    //Konfiguration initialisieren
    $pPreferences = new ConfigTable();
    if ($pPreferences->checkforupdate())
    {
        $pPreferences->init();
    }
   
   admRedirect(ADMIDIO_URL . FOLDER_PLUGINS. PLUGIN_FOLDER . '/system/declaration_of_membership.php'); 
    
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
