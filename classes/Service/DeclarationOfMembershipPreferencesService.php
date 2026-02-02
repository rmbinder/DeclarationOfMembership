<?php
namespace Plugins\DeclarationOfMembership\classes\Service;

use Admidio\Infrastructure\Exception;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

/**
 *
 * @brief Class with methods to display the module pages.
 *
 * This class adds some functions that are used in the preferences module to keep the
 * code easy to read and short
 *
 * DeclarationOfMembershipPreferencesService is a modified (Admidio)PreferencesService
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class DeclarationOfMembershipPreferencesService
{

    /**
     * Save all form data of the panel to the database.
     *
     * @param string $panel
     *            Name of the panel for which the data should be saved.
     * @param array $formData
     *            All form data of the panel.
     * @return void
     * @throws Exception
     */
    public function save(string $panel, array $formData)
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gDb, $gCurrentOrgId, $gProfileFields, $gLogger;

        require_once (__DIR__ . '/../../system/common_function.php');
        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $result = $gL10n->get('SYS_SAVE_DATA');

        // first check the fields of the submitted form
        switch ($panel) {

            case 'Options':
                $pPreferences->config['registration_org']['org_id'] = $formData['org_id'];
                $pPreferences->updateOrgId();

                $pPreferences->config['options']['kiosk_mode'] = $formData['kiosk_mode'];
                break;

            case 'AutoreplyMail':
                $pPreferences->config['emailnotification']['access_to_module'] = isset($formData['autoreplymail_module_enabled']) ? 1 : 0;
                break;

            case 'Access':
                if (isset($formData['access_preferences'])) {
                    $pPreferences->config['access']['preferences'] = array_values(array_filter($formData['access_preferences']));
                } else {
                    $pPreferences->config['access']['preferences'] = array();
                }
                break;

            case 'DisplayedFields':
                unset($pPreferences->config['main_texts']);
                unset($pPreferences->config['fields']['profile_fields']);

                foreach ($gProfileFields->getProfileFields() as $field) {
                    if (isset($formData[$field->getValue('usf_id')])) {
                        $pPreferences->config['fields']['profile_fields'][] = $field->getValue('usf_id');
                    }
                }

                if ($formData['main_pretext'] != '') {
                    $pPreferences->config['main_texts']['main_pretext'] = $formData['main_pretext'];
                }
                if ($formData['main_posttext'] != '') {
                    $pPreferences->config['main_texts']['main_posttext'] = $formData['main_posttext'];
                }

                $pPreferences->config['usr_login_name']['displayed'] = isset($formData['usr_login_name']) ? 1 : 0;
                break;

            case 'RequiredFields':
                unset($pPreferences->config['cat_texts']);
                unset($pPreferences->config['field_texts']);
                unset($pPreferences->config['fields']['required_fields']);

                foreach ($gProfileFields->getProfileFields() as $field) {
                    if (isset($formData['rqd-' . $field->getValue('usf_id')])) {
                        $pPreferences->config['fields']['required_fields'][] = $field->getValue('usf_id');
                    }
                    if (isset($formData[$field->getValue('cat_id') . '_pretext'])) {
                        $pPreferences->config['cat_texts'][$field->getValue('cat_id') . '_pretext'] = $formData[$field->getValue('cat_id') . '_pretext'];
                    }
                    if (isset($formData[$field->getValue('cat_id') . '_posttext'])) {
                        $pPreferences->config['cat_texts'][$field->getValue('cat_id') . '_posttext'] = $formData[$field->getValue('cat_id') . '_posttext'];
                    }
                    if (isset($formData[$field->getValue('usf_id') . '_fieldtext'])) {
                        $pPreferences->config['field_texts'][$field->getValue('usf_id') . '_fieldtext'] = $formData[$field->getValue('usf_id') . '_fieldtext'];
                    }
                }
                $pPreferences->config['usr_login_name']['required'] = isset($formData['usr_login_name_rqd']) ? 1 : 0;
                if (isset($formData['usr_login_name_fieldtext'])) {
                    $pPreferences->config['usr_login_name']['fieldtext'] = $formData['usr_login_name_fieldtext'];
                }
                break;
        }
        $pPreferences->save();
        return $result;

        // clean up
        $gCurrentSession->reloadAllSessions();
    }
}
