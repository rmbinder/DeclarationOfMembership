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

try {
    require_once(__DIR__ . '/../../system/common.php');
    require_once(__DIR__ . '/system/common_function.php');
    require_once(__DIR__ . '/classes/configtable.php');
    
    //script_name ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/mitgliedsbeitrag...
    $_SESSION['pDeclarationOfMembership']['script_name'] = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));
    
    $pPreferences = new ConfigTablePDM();
    if ($pPreferences->checkforupdate())
    {
        $pPreferences->init();
    }
    else
    {
        $pPreferences->read();
    }
   
   admRedirect(ADMIDIO_URL . FOLDER_PLUGINS. PLUGIN_FOLDER . '/system/declaration_of_membership.php'); 
    
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
