<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin DeclarationOfMembership
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(dirname(__DIR__),strrpos(dirname(__DIR__),DIRECTORY_SEPARATOR)+1));
}

spl_autoload_register('myAutoloader');

/**
 * Mein Autoloader
 * Script aus dem Netz
 * https://www.marcosimbuerger.ch/tech-blog/php-autoloader.html
 * @param   string  $className   Die übergebene Klasse
 * @return  string  Der überprüfte Klassenname
 */
function myAutoloader($className) {
    // Projekt spezifischer Namespace-Prefix.
    $prefix = 'Plugins\\';
    
    // Base-Directory für den Namespace-Prefix.
    $baseDir = __DIR__ . '/../../';
    
    // Check, ob die Klasse den Namespace-Prefix verwendet.
    $len = strlen($prefix);
    
    if (strncmp($prefix, $className, $len) !== 0) {
        // Wenn der Namespace-Prefix nicht verwendet wird, wird abgebrochen.
        return;
    }
    // Den relativen Klassennamen ermitteln.
    $relativeClassName = substr($className, $len);
    
    // Den Namespace-Präfix mit dem Base-Directory ergänzen,
    // Namespace-Trennzeichen durch Verzeichnis-Trennzeichen im relativen Klassennamen ersetzen,
    // .php anhängen.
    $file = $baseDir . str_replace('\\', '/', $relativeClassName) . '.php';
    // Pfad zur Klassen-Datei zurückgeben.
    if (file_exists($file)) {
        require $file;
    }
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist, das Modul Preferences aufzurufen.
 * @param   none
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorizedForPreferences()
{
    global $pPreferences;
    
    $userIsAuthorized = false;
    
    if ($GLOBALS['gCurrentUser']->isAdministrator())                   // Mitglieder der Rolle Administrator dürfen "Preferences" immer aufrufen
    {
        $userIsAuthorized = true;
    }
    else
    {
        foreach ($pPreferences->config['access']['preferences'] as $roleId)
        {
            if ($GLOBALS['gCurrentUser']->isMemberOfRole((int) $roleId))
            {
                $userIsAuthorized = true;
                continue;
            }
        }
    }
    
    return $userIsAuthorized;
}

/**
 * Funktion sucht für html definierte Zeichen im übergebenen String und ersetzt sie
 * @param   string  $string Der übergebene String
 * @return  string  $ret String mit ersetzten Zeichen
 */
function create_html($str)
{
   $defaultLink = '<a href="URL" target="_blank">TEXT</a>';
    
   preg_match_all("=##[^>](.*)##=siU", $str, $foundAll);
   
   // preg_match_all gibt als Rückgabe immer $foundAll[0] zurück. Wenn nichts gefunden wurde, ist es einfach leer
   foreach ($foundAll[0] as $found)
   {
       // Text zwischen den ersten ## und letzten ## extrahieren
       $foundCut = substr($found, 2, -2);
       
       // das # suchen, das ist das Trennzeichen zwischen URL und Text
       $cutPos = strpos($foundCut, '#');
       
       $stringURL = substr($foundCut, 0, $cutPos);               
       $stringTEXT = substr($foundCut, $cutPos+1);              
       
       //"URL" und "TEXT" ersetzen
       $tempLink = str_replace('URL', $stringURL, $defaultLink);
       $tempLink = str_replace('TEXT', $stringTEXT, $tempLink);
       
       //jetzt im übergebenen String den Bereich zwischen den ## mit dem neuen Link ersetzen 
       $str = str_replace($found, $tempLink, $str);
   }

   //jetzt noch die Sonderzeichen für "Fett" und "Zeilenvorschub" suchen und ersetzen
   $str = str_replace('$$', '<strong>', $str);
   $str = str_replace('%%', '</strong>', $str);
   $str = str_replace('§§', '</br>', $str);
     
   return $str;
}

