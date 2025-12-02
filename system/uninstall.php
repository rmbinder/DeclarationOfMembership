<?php
/**
 ***********************************************************************************************
 * Uninstallation of the Admidio plugin DeclarationOfMembership
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : security_check - security check
 *            uninst - uninstallation procedure
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Exception;
use Admidio\Menu\Entity\MenuEntry;
use Admidio\Roles\Entity\RolesRights;
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

    $result = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_STARTMESSAGE');

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'security_check',
        'validValues' => array(
            'security_check',
            'uninst'
        )
    ));

    switch ($getMode) {
        case 'security_check':
            // Sicherheitsabfrage, ob wirklich alles gelöscht werden soll

            global $gL10n;

            $title = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINSTALLATION');
            $headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINSTALLATION');

            $gNavigation->addUrl(CURRENT_URL, $headline);

            // create html page object
            $page = PagePresenter::withHtmlIDAndHeadline('plg-declarationofmembership-uninstall-html');
            $page->setTitle($title);
            $page->setHeadline($headline);

            $form = new FormPresenter('declarationofmembership_uninstall__form', '../templates/uninstall.plugin.declarationofmembership.tpl', '', $page, array(
                'type' => 'default',
                'method' => 'post',
                'setFocus' => false
            ));

            $form->addButton('btn_exit', $gL10n->get('SYS_YES'), array(
                'icon' => 'bi-check-square',
                'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/uninstall.php', array(
                    'mode' => 'uninst'
                )),
                'class' => 'btn-primary'
            ));

            $form->addButton('btn_continue', $gL10n->get('SYS_BACK'), array(
                'icon' => 'bi-backspace',
                'link' => $gNavigation->getPreviousUrl(),
                'class' => 'btn-primary'
            ));

            $form->addToHtmlPage(false);

            $page->show();

            break;

        case 'uninst':

            // Sicherheitsabfrage wurde bestätigt, es darf alles gelöscht werden

            // Menüpunkt löschen
            if ($pPreferences->config['install']['menu_item_id'] == 0) {
                // nur zur Sicherheit; dass 'menu_item_id' Null ist, dürfte eigentlich nicht vorkommen
                $result .= $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_NO_INST_ID_FOUND');
            } else {
                $result_menu = false;
                $menu = new MenuEntry($gDb, (int) $pPreferences->config['install']['menu_item_id']);
                $result_menu = $menu->delete();
                $result .= ($result_menu ? $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_MENU_ITEM_SUCCESS') : $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_MENU_ITEM_ERROR'));
            }

            // Konfigurationsdaten löschen
            $result_data = false;
            $result_db = false;

            $sql = 'DELETE FROM ' . $pPreferences->getTableName() . '
        		          WHERE plp_name LIKE ?  ';

            $result_data = $gDb->queryPrepared($sql, array(
                $pPreferences->getShortcut() . '__%'
            ));

            // wenn die Tabelle nur Eintraege dieses Plugins hatte, sollte sie jetzt leer sein und kann geloescht werden
            $sql = 'SELECT * FROM ' . $pPreferences->getTableName() . ' ';

            $statement = $gDb->queryPrepared($sql);

            if ($statement->rowCount() == 0) {
                $sql = 'DROP TABLE ' . $pPreferences->getTableName() . ' ';
                $result_db = $gDb->queryPrepared($sql);
            }

            $result .= ($result_data ? $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_DATA_DELETE_SUCCESS') : $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_DATA_DELETE_ERROR'));
            $result .= ($result_db ? $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_TABLE_DELETE_SUCCESS') : $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINST_TABLE_DELETE_ERROR'));

            $gNavigation->clear();
            $gMessage->setForwardUrl($gHomepage);

            $gMessage->show($result);

            break;
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}