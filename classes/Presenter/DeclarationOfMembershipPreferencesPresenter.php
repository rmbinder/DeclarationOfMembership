<?php
namespace Plugins\DeclarationOfMembership\classes\Presenter;

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\DeclarationOfMembership\classes\Config\ConfigTable;

/**
 *
 * @brief Class with methods to display the preference page and helpful functions.
 *
 * This class adds some functions that are used in the formfiller-preferences module to keep the
 * code easy to read and short
 *
 * DeclarationOfMembershipPreferencesPresenter is a modified (Admidio)PreferencesPresenter
 *
 * **Code example**
 * ```
 * // generate html output
 * $page = new DeclarationOfMembershipPreferencesPresenter('Options', $headline);
 * $page->createOptionsForm();
 * $page->show();
 * ```
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class DeclarationOfMembershipPreferencesPresenter extends PagePresenter
{

    /**
     *
     * @var array Array with all possible entries for the preferences.
     *      Each entry consists of an array that has the following structure:
     *      array ('key' => 'xzy', 'label' => 'xyz', 'panels' => array('id' => 'xyz', 'title' => 'xyz', 'icon' => 'xyz'))
     *     
     *      There are thwo different visualizations of the preferences:
     *      1) a nested tab structure (main tabs created by 'key' and 'label' and sub tabs created by 'panels')
     *      2) a accordion structure when the @media query (max-width: 768px) is active ('key' and 'label' are used for card header
     *      and 'panels' for accordions inside the card)
     */
    protected array $preferenceTabs = array();

    /**
     *
     * @var string Name of the preference panel that should be shown after page loading.
     *      If this parameter is empty, then show the common preferences.
     */
    protected string $preferencesPanelToShow = '';

    /**
     * Constructor that initializes the class member parameters
     *
     * @throws Exception
     */
    public function __construct(string $panel = '')
    {
        global $gL10n;

        $this->initialize();
        $this->setPanelToShow($panel);

        $this->setHtmlID('adm_preferences');
        $this->setHeadline($gL10n->get('SYS_SETTINGS'));

        parent::__construct();
    }

    /**
     *
     * @throws Exception
     */
    private function initialize(): void
    {
        global $gL10n;
        $this->preferenceTabs = array(
            // === 1) Configuration ===
            array(
                'key' => 'configuration',
                'label' => $gL10n->get('PLG_FORMFILLER_CONFIGURATIONS'),
                'panels' => array(
                    array(
                        'id' => 'displayed_fields',
                        'title' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_DISPLAYED_FIELDS'),
                        'icon' => 'bi-sliders2',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'required_fields',
                        'title' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_REQUIRED_FIELDS'),
                        'icon' => 'bi-sliders2',
                        'subcards' => false
                    )
                )
            ),

            // === 2) System ===
            array(
                'key' => 'system',
                'label' => $gL10n->get('SYS_SYSTEM'),
                'panels' => array(
                    array(
                        'id' => 'options',
                        'title' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_OPTIONS'),
                        'icon' => 'bi-gear',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'autoreply_mail',
                        'title' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_AUTOREPLYMAIL'),
                        'icon' => 'bi-envelope-arrow-down',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'uninstallation',
                        'title' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_UNINSTALLATION'),
                        'icon' => 'bi-trash',
                        'subcards' => false
                    )
                )
            ),

            // === 3) Security ===
            array(
                'key' => 'security',
                'label' => $gL10n->get('SYS_SECURITY'),
                'panels' => array(
                    array(
                        'id' => 'access',
                        'title' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES'),
                        'icon' => 'bi-key',
                        'subcards' => false
                    )
                )
            )
        );
    }

    /**
     * Generates the html of the form from the configurations preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the configurations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createDisplayedFieldsForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gProfileFields, $gCurrentOrganization;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formValues = $gSettingsManager->getAll();

        $formDisplayedFields = new FormPresenter('adm_preferences_form_displayedfields', '../templates/preferences.displayedfields.plugin.declarationofmembership.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'DisplayedFields'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formDisplayedFields->addMultilineTextInput('main_pretext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PRETEXT'), (isset($pPreferences->config['main_texts']['main_pretext']) ? $pPreferences->config['main_texts']['main_pretext'] : ''), 5, array(
            'helpTextId' => 'PLG_DECLARATION_OF_MEMBERSHIP_PRE_AND_POST_TEXT_DESC'
        ));

        $formDisplayedFields->addCheckbox('usr_login_name', $gL10n->get('SYS_USERNAME'), $pPreferences->config['usr_login_name']['displayed']);

        $category = '';
        foreach ($gProfileFields->getProfileFields() as $field) {
            $usfid = $field->getValue('usf_id');
            $catid = $field->getValue('cat_id');
            $category = $field->getValue('cat_name');

            $noteText = '';
            if ($field->getValue('cat_org_id') == NULL) {
                $noteText .= '- ' . $gL10n->get('SYS_DATA_MULTI_ORGA') . ' -';
            } else {
                $noteText .= '- ' . $gL10n->get('SYS_VISIBLE_FOR') . ' ' . $gCurrentOrganization->getValue('org_longname') . ' -';
            }

            $formDisplayedFields->addCheckbox($usfid, $field->getValue('usf_name'), (in_array($usfid, $pPreferences->config['fields']['profile_fields']) ? 1 : 0), array(
                'category' => $category,
                'notetext' => $noteText
            ));
        }

        $formDisplayedFields->addMultilineTextInput('main_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['main_texts']['main_posttext']) ? $pPreferences->config['main_texts']['main_posttext'] : ''), 5);

        $formDisplayedFields->addSubmitButton('adm_button_save', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formDisplayedFields->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formDisplayedFields);
        return $smarty->fetch('../templates/preferences.displayedfields.plugin.declarationofmembership.tpl');
    }

    /**
     * Generates the html of the form from the configurations preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the configurations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createRequiredFieldsForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gProfileFields;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formValues = $gSettingsManager->getAll();

        $formRequiredFields = new FormPresenter('adm_preferences_form_requiredfields', '../templates/preferences.requiredfields.plugin.declarationofmembership.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'RequiredFields'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $category = '';
        $findField = false;
        foreach ($gProfileFields->getProfileFields() as $field) {
            $usfid = $field->getValue('usf_id');
            $catid = $field->getValue('cat_id');
            $category = $field->getValue('cat_name');
            $lastCategory = '';

            if (in_array($usfid, $pPreferences->config['fields']['profile_fields'])) {
                $findField = true;

                if ($lastCategory !== $category) {
                    $formRequiredFields->addMultilineTextInput($catid . '_posttext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_POSTTEXT'), (isset($pPreferences->config['cat_texts'][$catid . '_posttext']) ? $pPreferences->config['cat_texts'][$catid . '_posttext'] : ''), 3, array(
                        'catid' => $catid,
                        'category' => $category
                    ));

                    $formRequiredFields->addMultilineTextInput($catid . '_pretext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_PRETEXT'), (isset($pPreferences->config['cat_texts'][$catid . '_pretext']) ? $pPreferences->config['cat_texts'][$catid . '_pretext'] : ''), 3, array(
                        'catid' => $catid,
                        'category' => $category
                    ));
                    $lastCategory = $category;
                }

                if ($category === $gL10n->get('SYS_BASIC_DATA') && $pPreferences->config['usr_login_name']['displayed']) {
                    $formRequiredFields->addCheckbox('usr_login_name_rqd', $gL10n->get('SYS_USERNAME'), $pPreferences->config['usr_login_name']['required'], array(
                        'catid' => $catid,
                        'category' => $category
                    ));
                    $formRequiredFields->addMultilineTextInput('usr_login_name_fieldtext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_FIELDTEXT'), (isset($pPreferences->config['usr_login_name']['fieldtext']) ? $pPreferences->config['usr_login_name']['fieldtext'] : ''), 1, array(
                        'catid' => $catid,
                        'category' => $category
                    ));
                }

                $formRequiredFields->addCheckbox('rqd-' . $usfid, $field->getValue('usf_name'), (in_array($usfid, $pPreferences->config['fields']['required_fields']) ? 1 : 0), array(
                    'catid' => $catid,

                    'category' => $category
                ));
                $formRequiredFields->addMultilineTextInput($usfid . '_fieldtext', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_FIELDTEXT'), (isset($pPreferences->config['field_texts'][$usfid . '_fieldtext']) ? $pPreferences->config['field_texts'][$usfid . '_fieldtext'] : ''), 1, array(
                    'catid' => $catid,
                    'category' => $category
                ));
            }
        }

        $formRequiredFields->addSubmitButton('adm_button_save', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formRequiredFields->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formRequiredFields);
        return $smarty->fetch('../templates/preferences.requiredfields.plugin.declarationofmembership.tpl');
    }

    /**
     * Generates the html of the form from the options preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the options preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createOptionsForm(): string
    {
        global $gL10n, $gDb, $gSettingsManager, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formValues = $gSettingsManager->getAll();

        $formOptions = new FormPresenter('adm_preferences_form_options', '../templates/preferences.options.plugin.declarationofmembership.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'Options'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $sql = 'SELECT org_id, org_longname
          FROM ' . TBL_ORGANIZATIONS . '
      ORDER BY org_longname ASC, org_shortname ASC';

        $formOptions->addSelectBoxFromSql('org_id', $gL10n->get('SYS_ORGANIZATION'), $gDb, $sql, array(
            'defaultValue' => $pPreferences->config['registration_org']['org_id'],
            'helpTextId' => 'PLG_DECLARATION_OF_MEMBERSHIP_ORGANIZATION_DESC',
            'showContextDependentFirstEntry' => false
        ));

        $formOptions->addRadioButton('kiosk_mode', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_KIOSK_MODE'), array(
            0 => $gL10n->get('SYS_NO'),
            1 => $gL10n->get('SYS_YES')
        ), array(
            'defaultValue' => $pPreferences->config['options']['kiosk_mode'],
            'helpTextId' => 'PLG_DECLARATION_OF_MEMBERSHIP_KIOSK_MODE_DESC'
        ));

        $selectBoxEntries = array(
            '0' => $gL10n->get('SYS_DISABLED'),
            '1' => $gL10n->get('SYS_ENABLED')
        );

        $formOptions->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formOptions->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formOptions);
        return $smarty->fetch('../templates/preferences.options.plugin.declarationofmembership.tpl');
    }

    /**
     * Generates the html of the form from the configurations preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the configurations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAutoReplyMailForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formValues = $gSettingsManager->getAll();

        $formAutoreplymail = new FormPresenter('adm_preferences_form_autoreplymail', '../templates/preferences.autoreplymail.plugin.declarationofmembership.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'AutoreplyMail'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formAutoreplymail->addCheckbox('autoreplymail_module_enabled', $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_TO_MODULE_AUTOREPLYMAIL'), (bool) $pPreferences->config['emailnotification']['access_to_module'], array(
            'helpTextId' => 'PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_TO_MODULE_AUTOREPLYMAIL_DESC'
        ));

        $html = '<a class="btn btn-secondary" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/autoreplymail.php') . '">
            <i class="bi bi-pencil-square"></i>' . $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_SWITCH_TO_AUTOREPLYMAIL_KONFIGURATION') . '</a>';
        $formAutoreplymail->addCustomContent('autoreplymail_link', '', $html, array(
            'helpTextId' => $gL10n->get('PLG_DECLARATION_OF_MEMBERSHIP_SWITCH_TO_AUTOREPLYMAIL_KONFIGURATION_DESC'),
            'alertWarning' => $gL10n->get('ORG_NOT_SAVED_SETTINGS_LOST')
        ));

        $formAutoreplymail->addSubmitButton('adm_button_save_autoreply', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formAutoreplymail->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formAutoreplymail);
        return $smarty->fetch('../templates/preferences.autoreplymail.plugin.declarationofmembership.tpl');
    }

    /**
     * Generates the html of the form from the deinstallation preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the configurations preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createUninstallationForm(): string
    {
        $this->assignSmartyVariable('open_uninstall', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/uninstall.php'));
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.uninstall.plugin.declarationofmembership.tpl');
    }

    /**
     * Generates the html of the form from the access preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the access preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAccessForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentOrgId, $gDb;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formValues = $gSettingsManager->getAll();

        $formAccess = new FormPresenter('adm_preferences_form_access', '../templates/preferences.access.plugin.declarationofmembership.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'Access'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $sql = 'SELECT rol_id, rol_name, cat_name
                  FROM ' . TBL_CATEGORIES . ' , ' . TBL_ROLES . '
                 WHERE cat_id = rol_cat_id
                   AND ( cat_org_id = ' . $gCurrentOrgId . '
                    OR cat_org_id IS NULL )
              ORDER BY cat_sequence, rol_name ASC';

        $formAccess->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array(
            'defaultValue' => $pPreferences->config['access']['preferences'],
            'helpTextId' => 'PLG_DECLARATION_OF_MEMBERSHIP_ACCESS_PREFERENCES_DESC',
            'multiselect' => true
        ));

        // Warning: Undefined array key "inventory_profile_view"
        $formAccess->addInput('inventory_profile_view', '', '', array(
            'property' => FormPresenter::FIELD_HIDDEN
        ));

        $formAccess->addSubmitButton('adm_button_save_access', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formAccess->addToSmarty($smarty);
        return $smarty->fetch('../templates/preferences.access.plugin.declarationofmembership.tpl');
    }

    /**
     * Set a panel name that should be opened at a page load.
     *
     * @param string $panelName
     *            Name of the panel that should be opened at a page load.
     * @return void
     */
    public function setPanelToShow(string $panelName): void
    {
        $this->preferencesPanelToShow = $panelName;
    }

    /**
     * Read all available registrations from the database and create the HTML content of this
     * page with the Smarty template engine and write the HTML output to the internal
     * parameter **$pageContent**.
     * If no registration is found, then show a message to the user.
     */
    public function show(): void
    {
        global $gL10n;

        if ($this->preferencesPanelToShow !== '') {
            // open the selected panel
            if ($this->preferencesPanelToShow !== '') {
                $this->addJavascript('
                    // --- Reset Tab active states for large screens
                    $("#adm_preferences_tabs .nav-link").removeClass("active");
                    $("#adm_preferences_tab_content .tab-pane").removeClass("active show");

                    // --- Reset Accordion active states for small screens
                    $("#adm_preferences_accordion [aria-expanded=\'true\']").attr("aria-expanded", "false");
                    $("#adm_preferences_accordion .accordion-button").addClass("collapsed");
                    $("#adm_preferences_accordion .accordion-item").removeClass("show");
                    $("#adm_preferences_accordion .accordion-collapse").removeClass("show");
                    
                    // --- Activate the selected Tab and its content
                    $("#adm_tab_' . $this->preferencesPanelToShow . '").addClass("active");
                    $("#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    
                    // --- For Mobile Accordion: open the desired accordion panel
                    $("#collapse_' . $this->preferencesPanelToShow . '").addClass("show");
                                        
                    // --- Desktop vs. Mobile via jQuery visibility
                    if ($(".d-none.d-md-block").is(":visible")) {
                        // Desktop mode
                        $("#adm_preferences_tabs .nav-link[data-bs-target=\'#adm_tab_' . $this->preferencesPanelToShow . '_content\']").addClass("active");
                        $("#adm_preferences_tab_content .tab-pane#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    } else {
                        // Mobile mode
                        $("#collapse_' . $this->preferencesPanelToShow . '").addClass("show").attr("aria-expanded", "true");
                        $("#heading_' . $this->preferencesPanelToShow . ' .accordion-button").removeClass("collapsed").attr("aria-expanded", "true");
                        // --- Hash setzen, damit Bookmark/Scroll stimmt und zum Element scrollen
                        location.hash = "#heading_' . $this->preferencesPanelToShow . '";
                    }
                ', true);
            }
        }

        $this->addJavascript('
            // === 1) Panel laden und Events binden ===
            function loadPreferencesPanel(panelId) {
                var panelContainers = $("[data-preferences-panel=\"" + panelId + "\"]");
                // only load the panel to the container that is currently visible
                var panelContainer = panelContainers.filter(":visible").first();

                if (!panelContainer.length) return;

                // Schritt 1: Spinner einfügen
                panelContainer.html("<div class=\"d-flex justify-content-center align-items-center\" style=\"height: 200px;\"><div class=\"spinner-border text-primary\" role=\"status\"><span class=\"visually-hidden\">Lade...</span></div></div>");

                $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php", {
               mode: "html_form",
                    panel: panelId
                }, function(htmlContent) {
                    panelContainer.html(htmlContent);
                    initializePanelInteractions(panelId);
                }).fail(function() {
                    panelContainer.html("<div class=\"text-danger\">Fehler beim Laden</div>");
                });
            }

            // === 2) Innerhalb eines Panels die Klick-Handler anmelden ===
            function initializePanelInteractions(panelId) {
                var panelContainer = $("[data-preferences-panel=\"" + panelId + "\"]");

                // Captcha-Refresh
                panelContainer.off("click", "#adm_captcha_refresh").on("click", "#adm_captcha_refresh", function(event) {
                    event.preventDefault();
                    var captchaImg = panelContainer.find("#adm_captcha");
                    if (captchaImg.length) {
                        captchaImg.attr("src", "' . ADMIDIO_URL . FOLDER_LIBS . '/securimage/securimage_show.php" + "?" + Math.random());
                    }
                });

                // Update-Check
                panelContainer.off("click", "#adm_link_check_update").on("click", "#adm_link_check_update", function(event) {
                    event.preventDefault();
                    var versionInfoContainer = panelContainer.find("#adm_version_content");
                    versionInfoContainer.html("<i class=\"spinner-border spinner-border-sm\"></i>").show();
                    $.get("' . ADMIDIO_URL . FOLDER_MODULES . '/preferences.php", { mode: "update_check" }, function(htmlVersion) {
                        versionInfoContainer.html(htmlVersion);
                    });
                });

                // Verzeichnis-Schutz prüfen
                panelContainer.off("click", "#link_directory_protection").on("click", "#link_directory_protection", function(event) {
                    event.preventDefault();
                    var statusContainer = panelContainer.find("#directory_protection_status");
                    statusContainer.html("<i class=\"spinner-border spinner-border-sm\"></i>").show();
                    $.get("' . ADMIDIO_URL . FOLDER_MODULES . '/preferences.php", { mode: "htaccess" }, function(statusText) {
                        var directoryProtection = panelContainer.find("#directoryProtection");
                        directoryProtection.html("<span class=\"text-success\"><strong>" + statusText + "</strong></span>");
                    });
                });

                // Module Settings visibility
                // Universal handling for module enabled toggle within the current panel container

                // define additional ids that should also be considered for visibility toggling
                var additionalIds = [\'#system_notifications_enabled\'];
                // Look for any input whose id ends with "_module_enabled"
                var selectors = ["[id$=\'_module_enabled\']"].concat(additionalIds);

                var moduleEnabledField = panelContainer.find(selectors.join(", ")).filter(":visible");
                if (moduleEnabledField.length > 0) {
                    // Get all row elements inside the form, excluding the row containing the module enabled field
                    var formElementGroups = panelContainer.find("form div.row")
                        .not(moduleEnabledField.closest("div.row"));

                    // Function to update visibility based on the fields type and state
                    var updateVisibility = function(initialCall) {
                        var isEnabled;
                        if (moduleEnabledField.attr("type") === "checkbox") {
                            isEnabled = moduleEnabledField.is(":checked");
                        } else {
                            isEnabled = moduleEnabledField.val() != 0;
                        }

                        if (initialCall === true) {
                            if (isEnabled) {
                                formElementGroups.show();
                            } else {
                                formElementGroups.hide();
                            }
                        } else {
                            if (isEnabled) {
                                formElementGroups.slideDown("slow");
                            } else {
                                formElementGroups.slideUp("slow");
                            }
                        }
                    };

                    // Set initial state without animation
                    updateVisibility(true);

                    // Update visibility on change
                    moduleEnabledField.on("change", updateVisibility);
                }
            }

            // === 3) Hooks für Desktop-Tabs ===
            $(document).on("shown.bs.tab", "ul#adm_preferences_tabs button.nav-link", function(e) {
                var target = e.target.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
                // scroll to the top of the page
                $("html, body").animate({
                    scrollTop: 0
                }, 500);
            });
            // initial: load the active tab panel
            $("ul#adm_preferences_tabs button.nav-link.active").each(function() {
                var target = this.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
            });

            // === 4) Hooks für Mobile-Accordion ===
            $(document).on("shown.bs.collapse", "#adm_preferences_accordion .accordion-collapse", function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);

                // scroll to the top of the accordion panel header
                var checkLoaded = setInterval(function(){
                    if ($("#collapse_" + panelId).find(".spinner-border").length === 0) {
                        clearInterval(checkLoaded);
                        $("html, body").animate({
                            scrollTop: $("#heading_" + panelId).offset().top
                        }, 500);
                    }
                }, 100);
            });
            // initial: geöffnetes Accordion-Panel laden
            $("#adm_preferences_accordion .accordion-collapse.show").each(function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);
            });

            // === 5) Formular-Submit per AJAX ===
            $(document).on("submit", "form[id^=\"adm_preferences_form_\"]", formSubmit);
      ', true);

        // Load the select2 in case any of the form uses a select box. Unfortunately, each section
        // is loaded on-demand, when there is no HTML page anymore to insert the css/JS file loading,
        // so we need to do it here, even when no selectbox will be used...
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/css/select2.css');
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2-bootstrap-theme/select2-bootstrap-5-theme.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/select2.js');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/i18n/' . $gL10n->getLanguageLibs() . '.js');

        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/css/bootstrap-tabs-x-admidio.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/js/bootstrap-tabs-x-admidio.js');

        $this->assignSmartyVariable('preferenceTabs', $this->preferenceTabs);
        $this->addTemplateFile('preferences/preferences.tpl');

        parent::show();
    }
}
