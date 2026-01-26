<?php
/**
 ***********************************************************************************************
 * Creates the main view for the plugin DeclarationOfMembertship
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Infrastructure\Exception;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\User;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // read user data
    $user = new User($gDb, $gProfileFields);

    $headline = $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_NAME');

    // Formular wurde ueber "Nein"-Button aufgerufen, also alle Felder mit den vorherigen Werten fuellen
    if (isset($_SESSION['profile_request']) && StringUtils::strContains($gNavigation->getUrl(), 'declaration_save.php')) {
        $user->noValueCheck();

        foreach ($gProfileFields->getProfileFields() as $field) {
            $fieldName = 'usf-' . $field->getValue('usf_id');
            if (isset($_SESSION['profile_request'][$fieldName])) {
                $user->setProfileFieldsValue($field->getValue('usf_name_intern'), stripslashes($_SESSION['profile_request'][$fieldName]));
            }
        }
        unset($_SESSION['profile_request']);
    }

    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-person-fill-add');

    // create html page object
    $page = PagePresenter::withHtmlIDAndHeadline('plg-declaration-of-membership');
    $page->setHeadline($headline);
    $page->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/zxcvbn/dist/zxcvbn.js');

    if (isUserAuthorizedForPreferences()) {
        // show link to pluginpreferences
        $page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('SYS_SETTINGS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php'), 'bi-gear-fill');
    }

    // create html form
    $form = new FormPresenter('edit_profile_form', '../templates/profile.edit.plugin.declarationofmembership.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/declaration_save.php', array(
        'headline' => $headline
    )), $page);

    // *******************************************************************************
    // Loop over all categories and profile fields
    // *******************************************************************************

    $category = '';
    $findFields = false;

    foreach ($gProfileFields->getProfileFields() as $field) {
        $showFields = false;
        $category = $field->getValue('cat_name');
        $catid = $field->getValue('cat_id');
        $usfid = $field->getValue('usf_id');

        if (in_array($field->getValue('usf_id'), $pPreferences->config['fields']['profile_fields'])) {
            $showFields = true;
            $findFields = true;
        }

        if ($field->getValue('cat_name') === $gL10n->get('SYS_BASIC_DATA') && $pPreferences->config['usr_login_name']['displayed']) {
            $fieldProperty = FormPresenter::FIELD_DEFAULT;

            if ($pPreferences->config['usr_login_name']['required']) {
                $fieldProperty = FormPresenter::FIELD_REQUIRED;
            }
            $form->addInput('usr_login_name', $gL10n->get('SYS_USERNAME'), '', array(
                'maxLength' => 254,
                'property' => $fieldProperty,
                'category' => $gL10n->get('SYS_BASIC_DATA'),
                'popover' => '',
                'catid' => $catid,
                'usfid' => ''
            ));
        }

        if ($showFields) {
            // add profile fields to form
            $fieldProperty = FormPresenter::FIELD_DEFAULT;
            $helpId = '';
            $usfNameIntern = $field->getValue('usf_name_intern');

            if (in_array($field->getValue('usf_id'), $pPreferences->config['fields']['required_fields'])) {
                $fieldProperty = FormPresenter::FIELD_REQUIRED;
            }

            if (strlen($gProfileFields->getProperty($usfNameIntern, 'usf_description')) > 0) {
                $helpId = $gProfileFields->getProperty($gProfileFields->getProperty($usfNameIntern, 'usf_name_intern'), 'usf_description');
            }

            // code for different field types
            if ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'CHECKBOX') {
                $form->addCheckbox('usf-' . $gProfileFields->getProperty($usfNameIntern, 'usf_id'), $gProfileFields->getProperty($usfNameIntern, 'usf_name'), (bool) $user->getValue($usfNameIntern), array(
                    'property' => $fieldProperty,
                    'popover' => $helpId,
                    'icon' => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database'),
                    'category' => $category,
                    'catid' => $catid,
                    'usfid' => $usfid
                ));
            } elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DROPDOWN' || $gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DROPDOWN_MULTISELECT' || $usfNameIntern === 'COUNTRY') {
                // set array with values and set default value
                if ($usfNameIntern === 'COUNTRY') {
                    $arrListValues = $gL10n->getCountries();
                    $defaultValue = null;

                    if ((int) $user->getValue('usr_id') === 0 && strlen($gSettingsManager->getString('default_country')) > 0) {
                        $defaultValue = $gSettingsManager->getString('default_country');
                    } elseif ($user->getValue('usr_id') > 0 && strlen($user->getValue($usfNameIntern)) > 0) {
                        $defaultValue = $user->getValue($usfNameIntern, 'database');
                    }
                } else {
                    $arrListValues = $gProfileFields->getProperty($usfNameIntern, 'ufo_usf_options', '', false);
                    $defaultValue = $user->getValue($usfNameIntern, 'database');
                    // if the field is a dropdown multiselect then convert the values to an array
                    if ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DROPDOWN_MULTISELECT') {
                        // prevent adding an empty string to the selectbox
                        $defaultValue = ($defaultValue !== "") ? explode(',', $defaultValue) : array();
                    }
                }

                $form->addSelectBox('usf-' . $gProfileFields->getProperty($usfNameIntern, 'usf_id'), $gProfileFields->getProperty($usfNameIntern, 'usf_name'), $arrListValues, array(
                    'property' => $fieldProperty,
                    'defaultValue' => $defaultValue,
                    'popover' => $helpId,
                    'icon' => 'bi-' . $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database'),
                    'category' => $category,
                    'catid' => $catid,
                    'usfid' => $usfid
                ));
            } elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'RADIO_BUTTON') {
                $showDummyRadioButton = false;
                if ($gProfileFields->getProperty($usfNameIntern, 'usf_required_input') == 0) {
                    $showDummyRadioButton = false;
                }

                $form->addRadioButton('usf-' . $gProfileFields->getProperty($usfNameIntern, 'usf_id'), $gProfileFields->getProperty($usfNameIntern, 'usf_name'), $gProfileFields->getProperty($usfNameIntern, 'ufo_usf_options', 'html', false), array(
                    'property' => $fieldProperty,
                    'defaultValue' => $user->getValue($usfNameIntern, 'database'),
                    'showNoValueButton' => $showDummyRadioButton,
                    'popover' => $helpId,
                    'icon' => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database'),
                    'category' => $category,
                    'catid' => $catid,
                    'usfid' => $usfid
                ));
            } elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'TEXT_BIG') {
                $form->addMultilineTextInput('usf-' . $gProfileFields->getProperty($usfNameIntern, 'usf_id'), $gProfileFields->getProperty($usfNameIntern, 'usf_name'), $user->getValue($usfNameIntern), 3, array(
                    'maxLength' => 4000,
                    'property' => $fieldProperty,
                    'popover' => $helpId,
                    'icon' => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database'),
                    'category' => $category,
                    'catid' => $catid,
                    'usfid' => $usfid
                ));
            } else {
                $fieldType = 'text';

                if ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'DATE') {
                    if ($usfNameIntern === 'BIRTHDAY') {
                        $fieldType = 'birthday';
                    } else {
                        $fieldType = 'date';
                    }
                    $maxlength = '10';
                } elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'EMAIL') {
                    // email could not be longer than 254 characters
                    $fieldType = 'email';
                    $maxlength = '254';
                } elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'URL') {
                    // maximal browser compatible url length will be 2000 characters
                    $maxlength = '2000';
                } elseif ($gProfileFields->getProperty($usfNameIntern, 'usf_type') === 'NUMBER') {
                    $fieldType = 'number';
                    $maxlength = array(
                        0,
                        9999999999,
                        1
                    );
                } elseif ($gProfileFields->getProperty($usfNameIntern, 'cat_name_intern') === 'SOCIAL_NETWORKS') {
                    $maxlength = '255';
                } else {
                    $maxlength = '50';
                }

                $form->addInput('usf-' . $gProfileFields->getProperty($usfNameIntern, 'usf_id'), $gProfileFields->getProperty($usfNameIntern, 'usf_name'), $user->getValue($usfNameIntern), array(
                    'type' => $fieldType,
                    'maxLength' => $maxlength,
                    'property' => $fieldProperty,
                    'popover' => $helpId,
                    'icon' => $gProfileFields->getProperty($usfNameIntern, 'usf_icon', 'database'),
                    'category' => $category,
                    'catid' => $catid,
                    'usfid' => $usfid
                ));
            }
        }
    }

    if ($findFields) {
        // if captchas are enabled then visitors of the website must resolve this
        if ($gSettingsManager->getBool('registration_enable_captcha')) {
            $form->addCaptcha('adm_captcha_code');
        }
        $form->addSubmitButton('adm_button_save', $gL10n->get('SYS_SEND'), array(
            'icon' => 'bi-send'
        ));
    }

    $page->assignSmartyVariable('urlPopup', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/declaration_popup_info.php'));
    $page->assignSmartyVariable('pPreferences', $pPreferences);

    $form->addToHtmlPage(false);

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
