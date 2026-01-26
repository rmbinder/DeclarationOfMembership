<?php
namespace Plugins\DeclarationOfMembership\classes\Service;

use Admidio\Infrastructure\Exception;
use Securimage;

/**
 * @brief Validate various content of form elements
 *
 * This class can be used to validate form input. Therefore, the methods can be called and get the
 * form input as parameter. The method will return **true** if validation was successful. Otherwise,
 * an Exception will be thrown. To catch this exception all method calls of this class should
 * be within a try and catch structure. Also, all method are declared static.
 *
 * Das ist die Original FormValidation-Klasse aus Admidio 4
 * Unter Admidio 5 ist diese Klasse "deprecated", man soll deshalb die Methode validateCaptcha aus der FormPresenter-Klasse verwenden
 * Die Methode validateCaptcha ist aber nicht als static deklariert und kann deshalb nicht über FormPresenter::validateCaptcha aufgerufen werden
 * 
 * **Code example**
 * ```
 * // validate the captcha code
 * try
 * {
 *     FormValidation::checkCaptcha($_POST['adm_captcha_code']);
 * }
 * catch(Throwable $e)
 * {
 *     $e->showHtml();
 * }
 * ```
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */

class FormValidation
{
    /**
     * Checks if the value of the captcha input matches with the captcha image.
     * @param string $value Value of the captcha input field.
     * @return true Returns **true** if the value matches the captcha image.
     *              Otherwise, throw an exception SYS_CAPTCHA_CODE_INVALID.
     * @throws Exception SYS_CAPTCHA_CALC_CODE_INVALID, SYS_CAPTCHA_CODE_INVALID
     */
    public static function checkCaptcha(string $value): bool
    {
        global $gSettingsManager;

        $secureImage = new Securimage();

        if ($secureImage->check($value)) {
            return true;
        }

        if ($gSettingsManager->getString('captcha_type') === 'calc') {
            throw new Exception('SYS_CAPTCHA_CALC_CODE_INVALID');
        }

        throw new Exception('SYS_CAPTCHA_CODE_INVALID');
    }
}
