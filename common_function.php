<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin DeclarationOfMembership
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1));
}

if(!defined('ORG_ID'))
{
	define('ORG_ID', (int) $gCurrentOrganization->getValue('org_id'));
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist, das Modul Preferences aufzurufen.
 * @param   none
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorizedForPreferences()
{
    global $gCurrentUser, $pPreferences;
    
    $userIsAuthorized = false;
    
    if ($gCurrentUser->isAdministrator())                   // Mitglieder der Rolle Administrator dürfen "Preferences" immer aufrufen
    {
        $userIsAuthorized = true;
    }
    else
    {
        foreach ($pPreferences->config['access']['preferences'] as $roleId)
        {
            if ($gCurrentUser->isMemberOfRole((int) $roleId))
            {
                $userIsAuthorized = true;
                continue;
            }
        }
    }
    
    return $userIsAuthorized;
}

/**
 * Funktion liest die Role-ID einer Rolle aus
 * @param   string  $role_name Name der zu pruefenden Rolle
 * @return  int     rol_id  Rol_id der Rolle, 0, wenn nicht gefunden
 */
function getRole_IDPDM($role_name)
{
    global $gDb;
    
    $sql    = 'SELECT rol_id
                 FROM '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE rol_name   = \''.$role_name.'\'
                  AND rol_valid  = 1
                  AND rol_cat_id = cat_id
                  AND ( cat_org_id = ?
                   OR cat_org_id IS NULL ) ';
    
    $statement = $gDb->queryPrepared($sql, array(ORG_ID));
    $row = $statement->fetchObject();
    
    if (isset($row->rol_id) && strlen($row->rol_id) > 0)
    {
        return $row->rol_id;
    }
    else
    {
        return 0;
    }
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

